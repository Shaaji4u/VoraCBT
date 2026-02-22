<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use DateTime;

class AuditLogService extends BaseService
{
    private Connection $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getConnection();
    }

    public function log(
        string $eventType,
        string $description,
        ?string $userId = null,
        array $metadata = [],
        string $severity = 'info'
    ): void {
        try {
            $this->db->insert('security_logs', [
                'id' => Uuid::uuid4()->toString(),
                'user_id' => $userId,
                'event_type' => $eventType,
                'ip_address' => $metadata['ip_address'] ?? null,
                'user_agent' => $metadata['user_agent'] ?? null,
                'device_hash' => $metadata['device_hash'] ?? null,
                'description' => $description,
                'severity' => $severity,
                'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Silently fail or log to file?
            // "Must not block submission flow unless critical" - Logging failure shouldn't crash the app.
            error_log('Failed to write security log: ' . $e->getMessage());
        }
    }
}
