<?php

declare(strict_types=1);

namespace Tests\Unit\Proctoring;

use App\Domain\Proctoring\Service\SessionIntegrityService;
use App\Core\Database\Migration\MigrationRunner;
use App\Core\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

class SessionIntegrityTest extends TestCase
{
    private SessionIntegrityService $service;
    private Connection $db;

    protected function setUp(): void
    {
        $runner = new MigrationRunner();
        $runner->migrate();

        $this->db = DatabaseManager::getConnection();
        $this->truncateTables();

        $this->service = new SessionIntegrityService();
    }

    private function truncateTables(): void
    {
        $this->db->executeStatement('PRAGMA foreign_keys = OFF');
        $this->db->executeStatement('DELETE FROM proctoring_sessions');
        $this->db->executeStatement('DELETE FROM exam_sessions');
        $this->db->executeStatement('DELETE FROM exam_session_answers');
        $this->db->executeStatement('DELETE FROM exam_templates');
        $this->db->executeStatement('DELETE FROM users');
        $this->db->executeStatement('PRAGMA foreign_keys = ON');
    }

    public function testLockSession(): void
    {
        // Setup
        $sessionId = Uuid::uuid4()->toString();
        $templateId = Uuid::uuid4()->toString();
        $userId = Uuid::uuid4()->toString();

        $this->db->insert('users', ['id' => $userId, 'email' => 'u@test.com', 'password' => 'pass', 'first_name' => 'F', 'last_name' => 'L', 'created_at' => '2023-01-01', 'updated_at' => '2023-01-01']);
        $this->db->insert('exam_templates', ['id' => $templateId, 'title' => 'T', 'created_at' => '2023-01-01', 'updated_at' => '2023-01-01']);
        $this->db->insert('exam_sessions', [
            'id' => $sessionId,
            'exam_template_id' => $templateId,
            'user_id' => $userId,
            'status' => 'in_progress',
            'created_at' => '2023-01-01',
            'updated_at' => '2023-01-01'
        ]);

        $this->service->lockSession($sessionId);

        $session = $this->db->fetchAssociative('SELECT * FROM exam_sessions WHERE id = ?', [$sessionId]);
        $this->assertEquals('submitted', $session['status']);
        $this->assertNotNull($session['locked_at']);
    }

    public function testIntegrityHash(): void
    {
        // Setup
        $sessionId = Uuid::uuid4()->toString();
        $templateId = Uuid::uuid4()->toString();
        $userId = Uuid::uuid4()->toString();
        $qId = Uuid::uuid4()->toString();

        $this->db->insert('users', ['id' => $userId, 'email' => 'u@test.com', 'password' => 'pass', 'first_name' => 'F', 'last_name' => 'L', 'created_at' => '2023-01-01', 'updated_at' => '2023-01-01']);
        $this->db->insert('exam_templates', ['id' => $templateId, 'title' => 'T', 'created_at' => '2023-01-01', 'updated_at' => '2023-01-01']);
        $this->db->insert('exam_sessions', [
            'id' => $sessionId,
            'exam_template_id' => $templateId,
            'user_id' => $userId,
            'status' => 'in_progress',
            'created_at' => '2023-01-01',
            'updated_at' => '2023-01-01'
        ]);

        // Need to insert question first due to FK
        $this->db->insert('questions', ['id' => $qId, 'content' => '{}', 'type' => 'mcq', 'metadata' => '{}', 'created_at' => '2023-01-01', 'updated_at' => '2023-01-01', 'version' => 1]);

        $this->db->insert('exam_session_answers', [
            'id' => Uuid::uuid4()->toString(),
            'exam_session_id' => $sessionId,
            'question_id' => $qId,
            'answer_payload' => '{"option": "A"}',
            'created_at' => '2023-01-01',
            'updated_at' => '2023-01-01'
        ]);

        $hash = $this->service->generateIntegrityHash($sessionId);

        $this->assertNotNull($hash);

        $session = $this->db->fetchAssociative('SELECT * FROM exam_sessions WHERE id = ?', [$sessionId]);
        $this->assertEquals($hash, $session['integrity_hash']);
    }
}
