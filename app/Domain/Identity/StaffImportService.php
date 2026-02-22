<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Core\Database\DatabaseManager;
use App\Core\Config\Environment;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Exception;
use RuntimeException;

class StaffImportService
{
    private Connection $db;
    private PasswordGenerator $passwordGenerator;
    private AuditLogger $auditLogger;
    private string $loginFieldConfig;
    private bool $emailResetEnabled;
    private int $tenantId = 1;

    public function __construct(PasswordGenerator $passwordGenerator = null, AuditLogger $auditLogger = null)
    {
        $this->db = DatabaseManager::getConnection();
        $this->passwordGenerator = $passwordGenerator ?? new PasswordGenerator();
        $this->auditLogger = $auditLogger ?? new AuditLogger();

        $env = Environment::getInstance();
        $this->loginFieldConfig = $env->get('AUTH_STAFF_LOGIN_FIELD', 'email'); // default to email
        $this->emailResetEnabled = filter_var($env->get('AUTH_EMAIL_RESET_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN);
    }

    public function setTenantId(int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

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

        $header = array_map('trim', $header);
        $header = array_map('strtolower', $header);

        $report = [
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'errors' => [],
            'preview_data' => []
        ];

        $seenEmails = [];
        $seenStaffIds = [];
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

            // Duplicate Detection
            if (!empty($data['email'])) {
                if (in_array($data['email'], $seenEmails)) {
                    $errors[] = "Row $rowIndex: Duplicate email in file ({$data['email']})";
                } else {
                    $seenEmails[] = $data['email'];
                }
            }
            if (!empty($data['staff_id'])) {
                if (in_array($data['staff_id'], $seenStaffIds)) {
                     $errors[] = "Row $rowIndex: Duplicate Staff ID in file ({$data['staff_id']})";
                } else {
                    $seenStaffIds[] = $data['staff_id'];
                }
            }
            if (!empty($data['sms_oauth_id'])) {
                if (in_array($data['sms_oauth_id'], $seenOAuthIds)) {
                     $errors[] = "Row $rowIndex: Duplicate SMS OAuth ID in file ({$data['sms_oauth_id']})";
                } else {
                    $seenOAuthIds[] = $data['sms_oauth_id'];
                }
            }

            if ($this->isDuplicate($data)) {
                $errors[] = "Row $rowIndex: Staff already exists in database";
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

        $this->auditLogger->log('import_started', null, $adminId, $this->tenantId, ['type' => 'staff', 'file' => basename($filePath)]);

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

        // Ideally we should have staff_import_logs too, but we can reuse student_import_logs or create a generic one.
        // The table is named 'student_import_logs'. I'll use it but maybe the name is misleading.
        // For now, I'll use it as it's the only one available.
        // Or I should rename it? No, schema change.
        // I'll use it.
        $this->db->insert('student_import_logs', [
            'id' => $importLogId,
            'admin_id' => $adminId,
            'file_name' => basename($filePath),
            'total_rows' => $totalProcessed,
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'created_at' => $startTime->format('Y-m-d H:i:s'),
            'metadata' => json_encode(['login_field' => $this->loginFieldConfig, 'type' => 'staff'])
        ]);

        $this->auditLogger->log('import_completed', null, $adminId, $this->tenantId, [
            'type' => 'staff',
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

        try {
            foreach ($batch as $data) {
                $errors = $this->validateRow($data, 0);
                if (!empty($errors) || $this->isDuplicate($data)) {
                    $failed++;
                    continue;
                }

                try {
                    $userId = Uuid::uuid4()->toString();
                    $passwordPlain = $this->passwordGenerator->generate();
                    $passwordHash = password_hash($passwordPlain, PASSWORD_ARGON2ID);

                    // Resolve Role
                    $roleSlug = strtolower($data['role'] ?? 'teacher');
                    $roleId = $this->resolveRoleId($roleSlug);

                    // Metadata
                    $metadata = [];
                    if (!empty($data['subjects'])) {
                        $metadata['subjects'] = array_map('trim', explode(',', $data['subjects']));
                    }
                    if (!empty($data['department'])) {
                        $metadata['department'] = $data['department'];
                    }

                    $this->db->insert('users', [
                        'id' => $userId,
                        'email' => $data['email'] ?? null,
                        'staff_id' => $data['staff_id'] ?? null,
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'password' => $passwordHash,
                        'must_change_password' => 1,
                        'tenant_id' => $this->tenantId,
                        'role_id' => $roleId,
                        'import_log_id' => $importLogId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'sms_oauth_id' => $data['sms_oauth_id'] ?? null,
                        'metadata' => !empty($metadata) ? json_encode($metadata) : null,
                    ]);

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

        if ($this->loginFieldConfig === 'email' || $this->loginFieldConfig === 'both') {
            if (empty($data['email'])) {
                $errors[] = "Row $rowIndex: Email is required";
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row $rowIndex: Invalid email format";
            }
        }

        if ($this->loginFieldConfig === 'staff_id' || $this->loginFieldConfig === 'both') {
            if (empty($data['staff_id'])) {
                $errors[] = "Row $rowIndex: Staff ID is required";
            }
        }

        if ($this->emailResetEnabled && empty($data['email'])) {
             $errors[] = "Row $rowIndex: Email is required for password reset";
        }

        return $errors;
    }

    private function isDuplicate(array $data): bool
    {
        if (!empty($data['email'])) {
            $exists = $this->db->fetchOne("SELECT 1 FROM users WHERE email = ?", [$data['email']]);
            if ($exists) return true;
        }

        if (!empty($data['staff_id'])) {
            $exists = $this->db->fetchOne("SELECT 1 FROM users WHERE staff_id = ?", [$data['staff_id']]);
            if ($exists) return true;
        }

        if (!empty($data['sms_oauth_id'])) {
            $exists = $this->db->fetchOne("SELECT 1 FROM users WHERE sms_oauth_id = ?", [$data['sms_oauth_id']]);
            if ($exists) return true;
        }

        return false;
    }

    private function resolveRoleId(string $slug): ?string
    {
        // Valid roles: teacher, examiner, admin, etc.
        // Map common terms
        $map = [
            'staff' => 'teacher',
            'tutor' => 'teacher',
        ];
        $slug = $map[$slug] ?? $slug;

        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE slug = ?", [$slug]);

        if (!$roleId) {
             // Fallback to teacher? or null?
             // Prompt says "Map roles...". If undefined, maybe default to teacher?
             // I'll try 'teacher' as default if not found.
             if ($slug !== 'teacher') {
                  $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE slug = 'teacher'");
             }
        }

        return $roleId ?: null;
    }
}
