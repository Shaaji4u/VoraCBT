<?php

declare(strict_types=1);

namespace Tests\Domain\Identity;

use App\Domain\Identity\StaffImportService;
use App\Core\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;

class StaffImportServiceTest extends TestCase
{
    private Connection $db;
    private StaffImportService $service;
    private string $csvPath;

    protected function setUp(): void
    {
        $this->db = DatabaseManager::getConnection();

        // Clean up tables
        $this->db->executeStatement("DELETE FROM student_import_logs"); // used for staff too
        $this->db->executeStatement("DELETE FROM user_credentials_buffer");
        $this->db->executeStatement("DELETE FROM identity_logs");
        $this->db->executeStatement("DELETE FROM users");
        $this->db->executeStatement("DELETE FROM roles");

        // Seed roles
        $this->db->insert('roles', ['id' => '1', 'name' => 'Teacher', 'slug' => 'teacher', 'created_at' => date('Y-m-d'), 'updated_at' => date('Y-m-d')]);
        $this->db->insert('roles', ['id' => '2', 'name' => 'Admin', 'slug' => 'admin', 'created_at' => date('Y-m-d'), 'updated_at' => date('Y-m-d')]);

        $this->service = new StaffImportService();
        $this->csvPath = sys_get_temp_dir() . '/test_staff.csv';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->csvPath)) {
            unlink($this->csvPath);
        }
    }

    public function testPreviewValidCsv(): void
    {
        $file = fopen($this->csvPath, 'w');
        fputcsv($file, ['first_name', 'last_name', 'email', 'staff_id', 'role', 'subjects', 'department']);
        fputcsv($file, ['John', 'Doe', 'john@school.com', 'STF001', 'Teacher', 'Math, Science', 'Science Dept']);
        fputcsv($file, ['Jane', 'Smith', 'jane@school.com', 'STF002', 'Admin', '', 'Administration']);
        fclose($file);

        $report = $this->service->preview($this->csvPath);

        $this->assertEquals(2, $report['total_rows']);
        $this->assertEquals(2, $report['valid_rows']);
        $this->assertEmpty($report['errors']);
    }

    public function testCommitImportsStaff(): void
    {
        $file = fopen($this->csvPath, 'w');
        fputcsv($file, ['first_name', 'last_name', 'email', 'staff_id', 'role', 'subjects', 'department']);
        fputcsv($file, ['John', 'Doe', 'john@school.com', 'STF001', 'Teacher', 'Math, Science', 'Science Dept']);
        fclose($file);

        $result = $this->service->commit($this->csvPath, 'admin-id');

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['success']);

        $user = $this->db->fetchAssociative("SELECT * FROM users WHERE email = 'john@school.com'");
        $this->assertNotNull($user);
        $this->assertEquals('STF001', $user['staff_id']);

        // Check Metadata
        $meta = json_decode($user['metadata'], true);
        $this->assertEquals(['Math', 'Science'], $meta['subjects']);
        $this->assertEquals('Science Dept', $meta['department']);

        // Check Role
        $role = $this->db->fetchAssociative("SELECT * FROM roles WHERE id = ?", [$user['role_id']]);
        $this->assertEquals('teacher', $role['slug']);
    }
}
