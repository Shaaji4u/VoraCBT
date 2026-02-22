<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Connection;
use RuntimeException;

class OAuthLinker
{
    private Connection $db;
    private AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger = null)
    {
        $this->db = DatabaseManager::getConnection();
        $this->auditLogger = $auditLogger ?? new AuditLogger();
    }

    public function linkAccount(string $userId, string $oauthId, ?string $adminId = null, array $context = []): void
    {
        // Check if oauth ID is already taken
        $existing = $this->db->fetchOne("SELECT id FROM users WHERE sms_oauth_id = ?", [$oauthId]);
        if ($existing && $existing !== $userId) {
            throw new RuntimeException("OAuth ID already linked to another user.");
        }

        $user = $this->db->fetchAssociative("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) {
             throw new RuntimeException("User not found.");
        }

        // Update User
        $updateData = ['sms_oauth_id' => $oauthId];

        // Update context if provided
        if (isset($context['academic_session'])) {
            $updateData['academic_session'] = $context['academic_session'];
        }
        if (isset($context['term'])) {
            $updateData['term'] = $context['term'];
        }

        $this->db->update('users', $updateData, ['id' => $userId]);

        $this->auditLogger->log('oauth_linked', $userId, $adminId, 1, [
            'oauth_id' => $oauthId,
            'context' => $context
        ]);
    }

    public function unlinkAccount(string $userId, ?string $adminId = null): void
    {
        $user = $this->db->fetchAssociative("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) {
             throw new RuntimeException("User not found.");
        }

        $this->db->update('users', ['sms_oauth_id' => null], ['id' => $userId]);

        $this->auditLogger->log('oauth_unlinked', $userId, $adminId, 1, []);
    }

    public function findLocalAccount(string $identifier): ?array
    {
        // Identifier can be email, admission_number, or staff_id
        $qb = $this->db->createQueryBuilder();
        $qb->select('*')->from('users')
           ->where('email = :identifier')
           ->orWhere('admission_number = :identifier')
           ->orWhere('staff_id = :identifier')
           ->setParameter('identifier', $identifier);

        $result = $qb->executeQuery()->fetchAssociative();
        return $result ?: null;
    }

    public function findByOAuthId(string $oauthId): ?array
    {
        $result = $this->db->fetchAssociative("SELECT * FROM users WHERE sms_oauth_id = ?", [$oauthId]);
        return $result ?: null;
    }
}
