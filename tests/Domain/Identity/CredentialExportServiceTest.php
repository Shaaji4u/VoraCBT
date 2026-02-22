<?php

declare(strict_types=1);

namespace Tests\Domain\Identity;

use App\Domain\Identity\CredentialExportService;
use App\Core\Database\DatabaseManager;
use App\Core\Database\Migration\MigrationRunner;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

class CredentialExportServiceTest extends TestCase
{
    private Connection $db;
    private CredentialExportService $service;

    protected function setUp(): void
    {
        ob_start();
        $runner = new MigrationRunner();
        $runner->migrate();
        ob_end_clean();

        $this->db = DatabaseManager::getConnection();

        $this->db->executeStatement("DELETE FROM credential_export_logs");
        $this->db->executeStatement("DELETE FROM user_credentials_buffer");
        $this->db->executeStatement("DELETE FROM users");
        $this->db->executeStatement("DELETE FROM classes");

        $this->service = new CredentialExportService();
    }

    public function testExportReturnsCsvAndLogs(): void
    {
        // 1. Setup Data
        $classId = Uuid::uuid4()->toString();
        $this->db->insert('classes', [
            'id' => $classId,
            'name' => 'Class A',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $userId = Uuid::uuid4()->toString();
        $this->db->insert('users', [
            'id' => $userId,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'admission_number' => 'ADM001',
            'class_id' => $classId,
            'password' => 'hash',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $this->db->insert('user_credentials_buffer', [
            'user_id' => $userId,
            'password_plaintext' => 'plaintext123',
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            'is_exported' => 0
        ]);

        // 2. Export
        $csv = $this->service->export(['class_id' => $classId], 'csv', false, 'admin-123');

        // 3. Verify Content
        $this->assertStringContainsString('John Doe', $csv);
        $this->assertStringContainsString('plaintext123', $csv);
        $this->assertStringContainsString('Class A', $csv);

        // 4. Verify Marked as Exported
        $buffer = $this->db->fetchAssociative("SELECT * FROM user_credentials_buffer WHERE user_id = ?", [$userId]);
        $this->assertEquals(1, $buffer['is_exported']);

        // 5. Verify Audit Log
        $log = $this->db->fetchAssociative("SELECT * FROM credential_export_logs");
        $this->assertNotFalse($log);
        $this->assertEquals('admin-123', $log['admin_id']);
        $this->assertStringContainsString($classId, $log['filters']);
    }

    public function testExportRegeneratesPasswords(): void
    {
        // 1. Setup Data with expired/missing buffer
        $userId = Uuid::uuid4()->toString();
        $this->db->insert('users', [
            'id' => $userId,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'oldhash',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // 2. Export with regenerate=true
        $csv = $this->service->export(['student_ids' => [$userId]], 'csv', true, 'admin-123');

        // 3. Verify
        $this->assertStringContainsString('Jane Doe', $csv);
        // Password should be new (random), so hard to assert value, but check structure
        $lines = explode("\n", trim($csv));
        $this->assertCount(2, $lines); // Header + 1 row

        // Check Buffer
        $buffer = $this->db->fetchAssociative("SELECT * FROM user_credentials_buffer WHERE user_id = ?", [$userId]);
        $this->assertNotEmpty($buffer);
        $this->assertEquals(1, $buffer['is_exported']); // Marked exported immediately
    }
}
