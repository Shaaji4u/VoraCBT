<?php

declare(strict_types=1);

namespace Tests\Domain\Identity;

use App\Domain\Identity\StudentImportService;
use App\Core\Database\DatabaseManager;
use App\Core\Database\Migration\MigrationRunner;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;

class StudentImportServiceTest extends TestCase
{
    private Connection $db;
    private StudentImportService $service;
    private string $csvPath;

    protected function setUp(): void
    {
        // Run Migrations for Test DB
        ob_start();
        $runner = new MigrationRunner();
        $runner->migrate();
        ob_end_clean();

        $this->db = DatabaseManager::getConnection();

        // Clean up tables
        $this->db->executeStatement("DELETE FROM student_import_logs");
        $this->db->executeStatement("DELETE FROM user_credentials_buffer");
        // Due to foreign keys, delete in order or disable FK checks
        $this->db->executeStatement("DELETE FROM users"); // This might fail if referenced
        $this->db->executeStatement("DELETE FROM classes");

        $this->service = new StudentImportService();
        $this->csvPath = sys_get_temp_dir() . '/test_students.csv';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->csvPath)) {
            unlink($this->csvPath);
        }
    }

    public function testPreviewValidCsv(): void
    {
        $csvContent = "first_name,last_name,email,admission_number,class\n" .
                      "John,Doe,john@example.com,ADM001,Class A\n" .
                      "Jane,Smith,jane@example.com,ADM002,Class B";
        file_put_contents($this->csvPath, $csvContent);

        $report = $this->service->preview($this->csvPath);

        $this->assertEquals(2, $report['total_rows']);
        $this->assertEquals(2, $report['valid_rows']);
        $this->assertEmpty($report['errors']);
        $this->assertCount(2, $report['preview_data']);
    }

    public function testPreviewInvalidRow(): void
    {
        $csvContent = "first_name,last_name,email,admission_number\n" .
                      "John,,john@example.com,ADM001\n" . // Missing last_name
                      "Jane,Smith,invalid-email,ADM002"; // Invalid email

        file_put_contents($this->csvPath, $csvContent);

        $report = $this->service->preview($this->csvPath);

        $this->assertEquals(2, $report['total_rows']);
        $this->assertEquals(0, $report['valid_rows']); // Both invalid
        $this->assertEquals(2, $report['invalid_rows']);
        $this->assertNotEmpty($report['errors']);
    }

    public function testCommitImportsStudents(): void
    {
        $csvContent = "first_name,last_name,email,admission_number,class\n" .
                      "John,Doe,john@example.com,ADM001,Class A\n" .
                      "Jane,Smith,jane@example.com,ADM002,Class A"; // Same class
        file_put_contents($this->csvPath, $csvContent);

        $result = $this->service->commit($this->csvPath, 'admin-id');

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['success']);
        $this->assertEquals(0, $result['failed']);

        // Check DB
        $users = $this->db->fetchAllAssociative("SELECT * FROM users");
        $this->assertCount(2, $users);

        // Find John
        $john = null;
        foreach ($users as $user) {
            if ($user['email'] === 'john@example.com') {
                $john = $user;
                break;
            }
        }
        $this->assertNotNull($john);

        // Check Classes
        $classes = $this->db->fetchAllAssociative("SELECT * FROM classes");
        $this->assertCount(1, $classes);
        $this->assertEquals('Class A', $classes[0]['name']);

        // Check John's class
        $this->assertEquals($classes[0]['id'], $john['class_id']);

        // Check Import Log ID linkage
        $this->assertNotNull($john['import_log_id']);
        $this->assertEquals($result['import_id'], $john['import_log_id']);

        // Check Buffer
        $buffer = $this->db->fetchAllAssociative("SELECT * FROM user_credentials_buffer");
        $this->assertCount(2, $buffer);
    }

    public function testPreviewHandlesDuplicates(): void
    {
        // 1. Setup DB Duplicate
        $this->db->insert('users', [
            'id' => 'existing-id',
            'first_name' => 'Existing',
            'last_name' => 'User',
            'email' => 'john@example.com',
            'admission_number' => null,
            'password' => 'hash',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // 2. CSV with DB duplicate AND File duplicate
        $csvContent = "first_name,last_name,email,admission_number\n" .
                      "John,Doe,john@example.com,ADM001\n" . // DB Duplicate
                      "Jane,Smith,jane@example.com,ADM002\n" . // Valid
                      "Jane,Doe,jane@example.com,ADM003"; // File Duplicate

        file_put_contents($this->csvPath, $csvContent);

        $report = $this->service->preview($this->csvPath);

        $this->assertEquals(3, $report['total_rows']);
        $this->assertEquals(1, $report['valid_rows']); // Only middle one valid
        $this->assertEquals(2, $report['invalid_rows']);

        $this->assertStringContainsString('Student already exists', implode(',', $report['errors']));
        $this->assertStringContainsString('Duplicate email in file', implode(',', $report['errors']));
    }

    public function testCommitHandlesDuplicates(): void
    {
        // Insert one user first
        $this->db->insert('users', [
            'id' => 'existing-id',
            'first_name' => 'Existing',
            'last_name' => 'User',
            'email' => 'john@example.com', // Duplicate email
            'admission_number' => null,
            'password' => 'hash',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $csvContent = "first_name,last_name,email,admission_number\n" .
                      "John,Doe,john@example.com,ADM001\n" . // Duplicate email
                      "Jane,Smith,jane@example.com,ADM002"; // OK

        file_put_contents($this->csvPath, $csvContent);

        $result = $this->service->commit($this->csvPath);

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['success']);
        $this->assertEquals(1, $result['failed']);
    }
}
