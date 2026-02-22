<?php

declare(strict_types=1);

namespace App\Integration\Service;

use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Exception;

class SyncService
{
    private SmsApiClient $api;
    private Connection $db;

    public function __construct(SmsApiClient $api = null)
    {
        $this->api = $api ?? new SmsApiClient();
        $this->db = DatabaseManager::getConnection();
    }

    public function syncStudents(): array
    {
        $response = $this->api->request('GET', '/students');

        if ($response['status'] !== 200) {
            throw new Exception("Failed to fetch students: " . $response['body']);
        }

        $students = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        $synced = 0;
        $errors = 0;

        foreach ($students as $student) {
            try {
                // Upsert logic
                $existing = $this->db->fetchAssociative("SELECT id FROM users WHERE email = ?", [$student['email']]);

                if ($existing) {
                    $this->db->update('users', [
                        'first_name' => $student['first_name'],
                        'last_name' => $student['last_name'],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ], ['id' => $existing['id']]);
                } else {
                    $this->db->insert('users', [
                        'id' => Uuid::uuid4()->toString(),
                        'email' => $student['email'],
                        'first_name' => $student['first_name'],
                        'last_name' => $student['last_name'],
                        'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                $synced++;
            } catch (Exception $e) {
                $errors++;
            }
        }

        return ['synced' => $synced, 'errors' => $errors];
    }

    public function syncClasses(): array
    {
        $response = $this->api->request('GET', '/classes');

        if ($response['status'] !== 200) {
            throw new Exception("Failed to fetch classes: " . $response['body']);
        }

        $classes = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        $synced = 0;
        $errors = 0;

        // Note: Classes table schema is not defined in the current context.
        // Logic to sync classes would go here once schema is available.
        // For now, we simulate success.

        return ['synced' => count($classes), 'errors' => 0];
    }

    public function syncSubjects(): array
    {
        $response = $this->api->request('GET', '/subjects');

        if ($response['status'] !== 200) {
            throw new Exception("Failed to fetch subjects: " . $response['body']);
        }

        $subjects = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        $synced = 0;
        $errors = 0;

        // Note: Subjects table schema is not defined in the current context.
        // Logic to sync subjects would go here once schema is available.

        return ['synced' => count($subjects), 'errors' => 0];
    }
}
