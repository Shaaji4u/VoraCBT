<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

class AuditLogger
{
    private Connection $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getConnection();
    }

    /**
     * Log an identity action.
     *
     * @param string $action The action performed (e.g., 'import_started', 'user_linked')
     * @param string|null $userId The ID of the user affected
     * @param string|null $performedBy The ID of the admin/user performing the action
     * @param int $tenantId The tenant ID
     * @param array|null $details Additional details
     */
    public function log(string $action, ?string $userId = null, ?string $performedBy = null, int $tenantId = 1, ?array $details = null): void
    {
        $this->db->insert('identity_logs', [
            'id' => Uuid::uuid4()->toString(),
            'user_id' => $userId,
            'action' => $action,
            'performed_by' => $performedBy,
            'timestamp' => date('Y-m-d H:i:s'),
            'tenant_id' => $tenantId,
            'details' => $details ? json_encode($details, JSON_THROW_ON_ERROR) : null
        ]);
    }
}
