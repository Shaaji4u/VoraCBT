<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Core\Database\DatabaseManager;
use App\Core\Config\Environment;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Exception;
use RuntimeException;

class StudentImportService
{
    private Connection $db;
    private PasswordGenerator $passwordGenerator;
    private AuditLogger $auditLogger;
    private string $loginFieldConfig;
    private bool $emailResetEnabled;
    private array $classCache = [];
    private int $tenantId = 1;

    public function __construct(PasswordGenerator $passwordGenerator = null, AuditLogger $auditLogger = null)
    {
        $this->db = DatabaseManager::getConnection();
        $this->passwordGenerator = $passwordGenerator ?? new PasswordGenerator();
        $this->auditLogger = $auditLogger ?? new AuditLogger();

        $env = Environment::getInstance();
        $this->loginFieldConfig = $env->get('AUTH_STUDENT_LOGIN_FIELD', 'email'); // default to email
        $this->emailResetEnabled = filter_var($env->get('AUTH_EMAIL_RESET_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN);
    }

    public function setTenantId(int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Preview the CSV import.
     *
     * @param string $filePath
     * @return array Validation report
     */
    public function preview(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File not found: $filePath");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Unable to open file: $filePath");
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw new RuntimeException("Empty CSV file");
        }

        // Normalize header
        $header = array_map('trim', $header);
        $header = array_map('strtolower', $header);

        $report = [
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'errors' => [],
            'preview_data' => []
        ];

        // For duplicate detection within file
        $seenEmails = [];
        $seenAdmissions = [];
        $seenOAuthIds = [];

        $rowIndex = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $rowIndex++;
            $report['total_rows']++;

            if (count($row) !== count($header)) {
                $report['invalid_rows']++;
                $report['errors'][] = "Row $rowIndex: Column count mismatch";
                continue;
            }

            $data = array_combine($header, $row);
            $errors = $this->validateRow($data, $rowIndex);

            // Duplicate Detection (File Level)
            if (!empty($data['email'])) {
                if (in_array($data['email'], $seenEmails)) {
                    $errors[] = "Row $rowIndex: Duplicate email in file ({$data['email']})";
                } else {
                    $seenEmails[] = $data['email'];
                }
            }
            if (!empty($data['admission_number'])) {
                if (in_array($data['admission_number'], $seenAdmissions)) {
                     $errors[] = "Row $rowIndex: Duplicate admission number in file ({$data['admission_number']})";
                } else {
                    $seenAdmissions[] = $data['admission_number'];
                }
            }
            if (!empty($data['sms_oauth_id'])) {
                if (in_array($data['sms_oauth_id'], $seenOAuthIds)) {
                     $errors[] = "Row $rowIndex: Duplicate SMS OAuth ID in file ({$data['sms_oauth_id']})";
                } else {
                    $seenOAuthIds[] = $data['sms_oauth_id'];
                }
            }

            // Duplicate Detection (DB Level)
            if ($this->isDuplicate($data)) {
                $errors[] = "Row $rowIndex: Student already exists in database";
            }

            if (!empty($errors)) {
                $report['invalid_rows']++;
                $report['errors'] = array_merge($report['errors'], $errors);
            } else {
                $report['valid_rows']++;
                if (count($report['preview_data']) < 5) {
                    $report['preview_data'][] = $data;
                }
            }
        }

        fclose($handle);

        return $report;
    }

    /**
     * Commit the CSV import.
     *
     * @param string $filePath
     * @param string|null $adminId
     * @return array Import summary
     */
    public function commit(string $filePath, ?string $adminId = null): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File not found: $filePath");
        }

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);
        $header = array_map('trim', $header);
        $header = array_map('strtolower', $header);

        $batchSize = 200;
        $batch = [];
        $totalProcessed = 0;
        $successful = 0;
        $failed = 0;

        $importLogId = Uuid::uuid4()->toString();
        $startTime = new \DateTimeImmutable();

        // Load Class Cache
        $this->loadClassCache();

        // Log start
        $this->auditLogger->log('import_started', null, $adminId, $this->tenantId, ['type' => 'student', 'file' => basename($filePath)]);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                $failed++;
                continue;
            }
            $batch[] = array_combine($header, $row);

            if (count($batch) >= $batchSize) {
                $results = $this->processBatch($batch, $importLogId);
                $successful += $results['success'];
                $failed += $results['failed'];
                $totalProcessed += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $results = $this->processBatch($batch, $importLogId);
            $successful += $results['success'];
            $failed += $results['failed'];
            $totalProcessed += count($batch);
        }

        fclose($handle);

        // Log Import Record
        $this->db->insert('student_import_logs', [
            'id' => $importLogId,
            'admin_id' => $adminId,
            'file_name' => basename($filePath),
            'total_rows' => $totalProcessed,
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'created_at' => $startTime->format('Y-m-d H:i:s'),
            'metadata' => json_encode(['login_field' => $this->loginFieldConfig])
        ]);

        // Log completion
        $this->auditLogger->log('import_completed', null, $adminId, $this->tenantId, [
            'type' => 'student',
            'total' => $totalProcessed,
            'success' => $successful,
            'failed' => $failed
        ]);

        return [
            'import_id' => $importLogId,
            'total' => $totalProcessed,
            'success' => $successful,
            'failed' => $failed
        ];
    }

    private function processBatch(array $batch, string $importLogId): array
    {
        $this->db->beginTransaction();
        $success = 0;
        $failed = 0;
        $studentRoleId = $this->db->fetchOne("SELECT id FROM roles WHERE slug = 'student'") ?: null;

        try {
            foreach ($batch as $data) {
                // Validate again
                $errors = $this->validateRow($data, 0);
                if (!empty($errors) || $this->isDuplicate($data)) {
                    $failed++;
                    continue;
                }

                try {
                    $userId = Uuid::uuid4()->toString();
                    $passwordPlain = $this->passwordGenerator->generate();
                    $passwordHash = password_hash($passwordPlain, PASSWORD_ARGON2ID);

                    // Resolve Class ID
                    $classId = null;
                    if (!empty($data['class'])) {
                        $classId = $this->resolveClassId($data['class']);
                    }

                    $this->db->insert('users', [
                        'id' => $userId,
                        'email' => $data['email'] ?? null,
                        'admission_number' => $data['admission_number'] ?? null,
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'password' => $passwordHash,
                        'must_change_password' => 1,
                        'tenant_id' => $this->tenantId,
                        'class_id' => $classId,
                        'role_id' => $studentRoleId,
                        'import_log_id' => $importLogId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'sms_oauth_id' => $data['sms_oauth_id'] ?? null,
                        'academic_session' => $data['academic_session'] ?? null,
                        'term' => $data['term'] ?? null,
                    ]);

                    // Store Plaintext in Buffer
                    $this->db->insert('user_credentials_buffer', [
                        'user_id' => $userId,
                        'password_plaintext' => $passwordPlain,
                        'created_at' => date('Y-m-d H:i:s'),
                        'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                        'is_exported' => 0
                    ]);

                    $success++;
                } catch (Exception $e) {
                    $failed++;
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            $failed = count($batch);
            $success = 0;
        }

        return ['success' => $success, 'failed' => $failed];
    }

    private function validateRow(array $data, int $rowIndex): array
    {
        $errors = [];

        if (empty($data['first_name'])) {
            $errors[] = "Row $rowIndex: First name is required";
        }
        if (empty($data['last_name'])) {
            $errors[] = "Row $rowIndex: Last name is required";
        }

        // Login Field Validation
        if ($this->loginFieldConfig === 'email' || $this->loginFieldConfig === 'both') {
            if (empty($data['email'])) {
                $errors[] = "Row $rowIndex: Email is required";
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row $rowIndex: Invalid email format";
            }
        }

        if ($this->loginFieldConfig === 'admission_number' || $this->loginFieldConfig === 'both') {
            if (empty($data['admission_number'])) {
                $errors[] = "Row $rowIndex: Admission number is required";
            }
        }

        if ($this->emailResetEnabled && empty($data['email'])) {
             $errors[] = "Row $rowIndex: Email is required for password reset";
        }

        return $errors;
    }

    private function loadClassCache(): void
    {
        $this->classCache = [];
        $classes = $this->db->fetchAllAssociative("SELECT name, id FROM classes WHERE tenant_id = ?", [$this->tenantId]);
        foreach ($classes as $class) {
            $this->classCache[$class['name']] = $class['id'];
        }
    }

    private function isDuplicate(array $data): bool
    {
        // Check Email
        if (!empty($data['email'])) {
            $exists = $this->db->fetchOne("SELECT 1 FROM users WHERE email = ?", [$data['email']]);
            if ($exists) return true;
        }

        // Check Admission Number
        if (!empty($data['admission_number'])) {
            $exists = $this->db->fetchOne("SELECT 1 FROM users WHERE admission_number = ?", [$data['admission_number']]);
            if ($exists) return true;
        }

        // Check SMS OAuth ID
        if (!empty($data['sms_oauth_id'])) {
            $exists = $this->db->fetchOne("SELECT 1 FROM users WHERE sms_oauth_id = ?", [$data['sms_oauth_id']]);
            if ($exists) return true;
        }

        return false;
    }

    private function resolveClassId(string $className): string
    {
        // Check Cache
        if (isset($this->classCache[$className])) {
            return $this->classCache[$className];
        }

        // Try to find in DB (fallback for concurrent additions or misses)
        $class = $this->db->fetchAssociative("SELECT id FROM classes WHERE name = ? AND tenant_id = ?", [$className, $this->tenantId]);

        if ($class) {
            $this->classCache[$className] = $class['id'];
            return $class['id'];
        }

        // Create
        $id = Uuid::uuid4()->toString();
        $this->db->insert('classes', [
            'id' => $id,
            'name' => $className,
            'tenant_id' => $this->tenantId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Update Cache
        $this->classCache[$className] = $id;

        return $id;
    }
}
