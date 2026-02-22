<?php

declare(strict_types=1);

namespace Tests\Domain\Identity;

use App\Domain\Identity\OAuthLinker;
use App\Core\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;

class OAuthLinkerTest extends TestCase
{
    private Connection $db;
    private OAuthLinker $linker;

    protected function setUp(): void
    {
        $this->db = DatabaseManager::getConnection();

        $this->db->executeStatement("DELETE FROM users");
        $this->db->executeStatement("DELETE FROM identity_logs");

        $this->linker = new OAuthLinker();

        // Create a user
        $this->db->insert('users', [
            'id' => 'user-1',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'admission_number' => 'ADM001',
            'password' => 'hash',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function testLinkAccount(): void
    {
        $this->linker->linkAccount('user-1', 'oauth-123', 'admin-1', [
            'academic_session' => '2023-2024',
            'term' => 'Term 1'
        ]);

        $user = $this->db->fetchAssociative("SELECT * FROM users WHERE id = 'user-1'");
        $this->assertEquals('oauth-123', $user['sms_oauth_id']);
        $this->assertEquals('2023-2024', $user['academic_session']);
        $this->assertEquals('Term 1', $user['term']);

        $logs = $this->db->fetchAllAssociative("SELECT * FROM identity_logs WHERE user_id = 'user-1'");
        $this->assertCount(1, $logs);
        $this->assertEquals('oauth_linked', $logs[0]['action']);
    }

    public function testUnlinkAccount(): void
    {
        // First link
        $this->db->update('users', ['sms_oauth_id' => 'oauth-123'], ['id' => 'user-1']);

        $this->linker->unlinkAccount('user-1', 'admin-1');

        $user = $this->db->fetchAssociative("SELECT * FROM users WHERE id = 'user-1'");
        $this->assertNull($user['sms_oauth_id']);

        $logs = $this->db->fetchAllAssociative("SELECT * FROM identity_logs WHERE user_id = 'user-1' AND action = 'oauth_unlinked'");
        $this->assertCount(1, $logs);
    }
}
