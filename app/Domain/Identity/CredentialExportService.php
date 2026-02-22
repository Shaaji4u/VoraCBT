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
     * @param array $filters ['class_id' => ..., 'import_id' => ..., 'student_ids' => [...]]
     * @param string $format 'csv' | 'html'
     * @param bool $regenerate Whether to regenerate passwords
     * @param string|null $adminId The admin performing the export
     * @return string The exported content
     */
    public function export(array $filters, string $format = 'csv', bool $regenerate = false, ?string $adminId = null): string
    {
        $users = $this->fetchUsers($filters);

        if (empty($users)) {
            throw new RuntimeException("No students found for the given filters.");
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
        $qb->select('u.*', 'c.name as class_name')
           ->from('users', 'u')
           ->leftJoin('u', 'classes', 'c', 'u.class_id = c.id');

        if (!empty($filters['class_id'])) {
            $qb->andWhere('u.class_id = :class_id')
               ->setParameter('class_id', $filters['class_id']);
        }

        if (!empty($filters['student_ids'])) {
            $qb->andWhere('u.id IN (:student_ids)')
               ->setParameter('student_ids', $filters['student_ids'], Connection::PARAM_STR_ARRAY);
        }

        if (!empty($filters['import_id'])) {
            $qb->andWhere('u.import_log_id = :import_id')
               ->setParameter('import_id', $filters['import_id']);
        }

        return $qb->executeQuery()->fetchAllAssociative();
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

            $this->db->update('users', ['password' => $hash], ['id' => $user['id']]);

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
            return [
                'user_id' => $user['id'],
                'full_name' => $user['first_name'] . ' ' . $user['last_name'],
                'admission_number' => $user['admission_number'],
                'email' => $user['email'],
                'class_name' => $user['class_name'] ?? 'N/A',
                'password' => $password
            ];
        }

        return null;
    }

    private function markAsExported(array $userIds): void
    {
        $this->db->executeQuery(
            "UPDATE user_credentials_buffer SET is_exported = 1 WHERE user_id IN (?)",
            [$userIds],
            [Connection::PARAM_STR_ARRAY]
        );
    }

    private function generateCsv(array $credentials): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Full Name', 'Admission Number', 'Email', 'Class', 'Password']);

        foreach ($credentials as $cred) {
            fputcsv($stream, [
                $cred['full_name'],
                $cred['admission_number'],
                $cred['email'],
                $cred['class_name'],
                $cred['password']
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
            @media print { .no-print { display: none; } }
        </style>';
        $html .= '</head><body>';
        $html .= '<div class="watermark">Confidential — Destroy After Distribution</div>';
        $html .= '<h1>Student Credentials</h1>';

        foreach ($credentials as $cred) {
            $html .= '<div class="card">';
            $html .= '<h3>' . htmlspecialchars($cred['full_name']) . '</h3>';
            $html .= '<p><strong>Institution:</strong> CBT Platform</p>'; // TODO: Configurable name
            $html .= '<p><strong>Class:</strong> ' . htmlspecialchars($cred['class_name']) . '</p>';
            $html .= '<p><strong>Login ID:</strong> ' . htmlspecialchars($cred['admission_number'] ?? $cred['email']) . '</p>';
            $html .= '<p><strong>Password:</strong> ' . htmlspecialchars($cred['password']) . '</p>';
            $html .= '<p><small>Login URL: ' . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') . '</small></p>';
            $html .= '</div>';
        }

        $html .= '</body></html>';
        return $html;
    }
}
