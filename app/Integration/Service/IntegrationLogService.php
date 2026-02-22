<?php

declare(strict_types=1);

namespace App\Integration\Service;

use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

class IntegrationLogService
{
    private Connection $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getConnection();
    }

    public function logRequest(string $idempotencyKey, ?array $payload): string
    {
        $existing = $this->db->fetchAssociative("SELECT id FROM integration_logs WHERE idempotency_key = ?", [$idempotencyKey]);

        if ($existing) {
            $this->db->executeQuery("UPDATE integration_logs SET retry_count = retry_count + 1, last_attempt_at = :now, updated_at = :now WHERE id = :id", [
                'now' => date('Y-m-d H:i:s'),
                'id' => $existing['id']
            ]);
            return $existing['id'];
        }

        $id = Uuid::uuid4()->toString();
        $this->db->insert('integration_logs', [
            'id' => $id,
            'idempotency_key' => $idempotencyKey,
            'request_payload' => $payload ? json_encode($payload, JSON_THROW_ON_ERROR) : null,
            'status' => 'PENDING',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'retry_count' => 0,
        ]);
        return $id;
    }

    public function logResponse(string $logId, string $status, ?array $responsePayload): void
    {
        $this->db->update('integration_logs', [
            'status' => $status,
            'response_payload' => $responsePayload ? json_encode($responsePayload, JSON_THROW_ON_ERROR) : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $logId]);
    }

    public function logRetry(string $logId): void
    {
        // Increment retry count
        $sql = "UPDATE integration_logs SET retry_count = retry_count + 1, last_attempt_at = :now, updated_at = :now WHERE id = :id";
        $this->db->executeQuery($sql, ['id' => $logId, 'now' => date('Y-m-d H:i:s')]);
    }

    public function getLogByIdempotencyKey(string $key): ?array
    {
        $result = $this->db->fetchAssociative("SELECT * FROM integration_logs WHERE idempotency_key = ?", [$key]);
        return $result ?: null;
    }
}
