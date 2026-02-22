<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Exception;
use RuntimeException;

class CredentialExportService
{
    private Connection $db;
    private PasswordGenerator $passwordGenerator;

    public function __construct(PasswordGenerator $passwordGenerator = null)
    {
        $this->db = DatabaseManager::getConnection();
        $this->passwordGenerator = $passwordGenerator ?? new PasswordGenerator();
    }

    /**
     * Export credentials.
     *
     * @param array $filters ['class_id', 'import_id', 'student_ids', 'staff_ids', 'type', 'role', 'department']
     * @param string $format 'csv' | 'html'
     * @param bool $regenerate Whether to regenerate passwords
     * @param string|null $adminId The admin performing the export
     * @return string The exported content
     */
    public function export(array $filters, string $format = 'csv', bool $regenerate = false, ?string $adminId = null): string
    {
        $users = $this->fetchUsers($filters);

        if (empty($users)) {
            throw new RuntimeException("No users found for the given filters.");
        }

        $credentials = [];

        foreach ($users as $user) {
            $creds = $this->getCredentials($user, $regenerate);
            if ($creds) {
                $credentials[] = $creds;
            }
        }

        if (empty($credentials)) {
             throw new RuntimeException("No available credentials to export. Use regeneration to create new ones.");
        }

        // Mark as exported
        $this->markAsExported(array_column($credentials, 'user_id'));

        // Log Export
        $this->logExport($filters, $adminId);

        if ($format === 'html') {
            return $this->generateHtml($credentials);
        }

        return $this->generateCsv($credentials);
    }

    private function logExport(array $filters, ?string $adminId): void
    {
        $this->db->insert('credential_export_logs', [
            'id' => Uuid::uuid4()->toString(),
            'admin_id' => $adminId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'filters' => json_encode($filters),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function fetchUsers(array $filters): array
    {
        $qb = $this->db->createQueryBuilder();
        $qb->select('u.*', 'c.name as class_name', 'r.slug as role_slug')
           ->from('users', 'u')
           ->leftJoin('u', 'classes', 'c', 'u.class_id = c.id')
           ->leftJoin('u', 'roles', 'r', 'u.role_id = r.id');

        if (!empty($filters['type'])) {
            if ($filters['type'] === 'student') {
                $qb->andWhere('r.slug = :role_slug OR r.slug IS NULL'); // Assuming default is student or strictly check?
                // Better: check for admission_number or class_id presence? Or rely on role.
                // If type is student, usually role is student.
                $qb->setParameter('role_slug', 'student');
            } elseif ($filters['type'] === 'staff') {
                $qb->andWhere('r.slug != :role_student'); // Not student
                $qb->setParameter('role_student', 'student');
            }
        }

        if (!empty($filters['class_id'])) {
            $qb->andWhere('u.class_id = :class_id')
               ->setParameter('class_id', $filters['class_id']);
        }

        if (!empty($filters['student_ids'])) {
            $qb->andWhere('u.id IN (:user_ids)')
               ->setParameter('user_ids', $filters['student_ids'], Connection::PARAM_STR_ARRAY);
        }

        // Support generic user_ids if passed
        if (!empty($filters['user_ids'])) {
            $qb->andWhere('u.id IN (:generic_user_ids)')
               ->setParameter('generic_user_ids', $filters['user_ids'], Connection::PARAM_STR_ARRAY);
        }

        if (!empty($filters['import_id'])) {
            $qb->andWhere('u.import_log_id = :import_id')
               ->setParameter('import_id', $filters['import_id']);
        }

        // Staff specific filters
        if (!empty($filters['role'])) {
            $qb->andWhere('r.slug = :role')
               ->setParameter('role', $filters['role']);
        }

        $users = $qb->executeQuery()->fetchAllAssociative();

        // Filter by department (JSON) in PHP as SQLite/MySQL JSON syntax differs and might be complex for portable SQL here.
        if (!empty($filters['department'])) {
            $users = array_filter($users, function($user) use ($filters) {
                $meta = json_decode($user['metadata'] ?? '{}', true);
                return isset($meta['department']) && stripos($meta['department'], $filters['department']) !== false;
            });
        }

        return $users;
    }

    private function getCredentials(array $user, bool $regenerate): ?array
    {
        // Check buffer
        $buffer = $this->db->fetchAssociative(
            "SELECT * FROM user_credentials_buffer WHERE user_id = ?",
            [$user['id']]
        );

        $password = null;

        if ($regenerate) {
            $password = $this->passwordGenerator->generate();
            $hash = password_hash($password, PASSWORD_ARGON2ID);

            $this->db->update('users', ['password' => $hash, 'must_change_password' => 1], ['id' => $user['id']]);

            // Update or Insert Buffer
            if ($buffer) {
                $this->db->update('user_credentials_buffer', [
                    'password_plaintext' => $password,
                    'created_at' => date('Y-m-d H:i:s'),
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                    'is_exported' => 0
                ], ['user_id' => $user['id']]);
            } else {
                $this->db->insert('user_credentials_buffer', [
                    'user_id' => $user['id'],
                    'password_plaintext' => $password,
                    'created_at' => date('Y-m-d H:i:s'),
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                    'is_exported' => 0
                ]);
            }
        } elseif ($buffer && !$buffer['is_exported'] && strtotime($buffer['expires_at']) > time()) {
            $password = $buffer['password_plaintext'];
        }

        if ($password) {
            $group = $user['class_name'];
            if (!$group) {
                $meta = json_decode($user['metadata'] ?? '{}', true);
                $group = $meta['department'] ?? $user['role_slug'] ?? 'Staff';
            }

            return [
                'user_id' => $user['id'],
                'full_name' => $user['first_name'] . ' ' . $user['last_name'],
                'login_id' => $user['admission_number'] ?? $user['staff_id'] ?? $user['email'],
                'email' => $user['email'],
                'group' => $group,
                'password' => $password,
                'academic_session' => $user['academic_session'] ?? '',
                'sms_oauth_id' => $user['sms_oauth_id'] ?? ''
            ];
        }

        return null;
    }

    private function markAsExported(array $userIds): void
    {
        $this->db->executeQuery(
            "UPDATE user_credentials_buffer SET is_exported = 1, password_plaintext = NULL WHERE user_id IN (?)",
            [$userIds],
            [Connection::PARAM_STR_ARRAY]
        );
    }

    private function generateCsv(array $credentials): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Full Name', 'Login ID', 'Email', 'Group', 'Password', 'Session', 'OAuth ID']);

        foreach ($credentials as $cred) {
            fputcsv($stream, [
                $cred['full_name'],
                $cred['login_id'],
                $cred['email'],
                $cred['group'],
                'REDACTED (Use HTML for printing)',
                $cred['academic_session'],
                $cred['sms_oauth_id']
            ]);
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content;
    }

    private function generateHtml(array $credentials): string
    {
        $html = '<!DOCTYPE html><html><head><title>Credentials</title>';
        $html .= '<style>
            body { font-family: sans-serif; }
            .card { border: 1px dashed #333; padding: 20px; margin: 10px; width: 300px; float: left; page-break-inside: avoid; }
            .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); opacity: 0.1; font-size: 5em; z-index: -1; }
            .disclaimer { background: #fff3cd; border: 1px solid #ffeeba; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
            .secret { background-color: #000; color: #000; border-radius: 3px; padding: 0 4px; transition: background 0.3s; cursor: help; }
            .secret:hover { background-color: transparent; color: inherit; }
            @media print {
                .no-print { display: none; }
                .secret { background-color: transparent !important; color: inherit !important; }
            }
        </style>';
        $html .= '</head><body>';
        $html .= '<div class="watermark">Confidential — Destroy After Distribution</div>';
        $html .= '<h1>User Credentials</h1>';
        $html .= '<div class="disclaimer no-print">';
        $html .= '<strong>Security Notice:</strong> These credentials contain temporary passwords. ';
        $html .= 'Passcards should be printed, distributed securely, and students must be advised to change their passwords upon first login. ';
        $html .= 'Passwords are masked below for screen security; hover to reveal or print to see clearly.';
        $html .= '</div>';

        foreach ($credentials as $cred) {
            $html .= '<div class="card">';
            $html .= '<h3>' . htmlspecialchars($cred['full_name']) . '</h3>';
            $html .= '<p><strong>Institution:</strong> CBT Platform</p>';
            $html .= '<p><strong>Group:</strong> ' . htmlspecialchars($cred['group']) . '</p>';
            $html .= '<p><strong>Login ID:</strong> ' . htmlspecialchars($cred['login_id']) . '</p>';
            $html .= '<p><strong>Temporary Password:</strong> <span class="secret">' . htmlspecialchars($cred['password']) . '</span></p>';
             if ($cred['sms_oauth_id']) {
                $html .= '<p><small>Linked OAuth: Yes</small></p>';
            }
            $html .= '<p><small>Login URL: ' . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') . '</small></p>';
            $html .= '</div>';
        }

        $html .= '</body></html>';
        return $html;
    }
}
