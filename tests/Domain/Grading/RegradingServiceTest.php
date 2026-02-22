<?php

declare(strict_types=1);

namespace Tests\Domain\Grading;

use App\Core\Database\DatabaseManager;
use App\Core\Database\Migration\MigrationRunner;
use App\Domain\Grading\Job\GradingJob;
use App\Domain\Grading\Service\RegradingService;
use App\Infrastructure\Queue\QueueInterface;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class RegradingServiceTest extends TestCase
{
    private Connection $db;
    private RegradingService $service;
    private $queueMock;

    protected function setUp(): void
    {
        ob_start();
        $runner = new MigrationRunner();
        $runner->migrate();
        ob_end_clean();

        $this->db = DatabaseManager::getConnection();
        $this->truncateTables();

        $this->queueMock = $this->createMock(QueueInterface::class);
        $this->service = new RegradingService($this->db, $this->queueMock);
    }

    private function truncateTables(): void
    {
        $this->db->executeStatement('PRAGMA foreign_keys = OFF');
        $this->db->executeStatement('DELETE FROM exam_results');
        $this->db->executeStatement('DELETE FROM exam_session_answers');
        $this->db->executeStatement('DELETE FROM exam_sessions');
        $this->db->executeStatement('DELETE FROM exam_questions');
        $this->db->executeStatement('DELETE FROM exam_sections');
        $this->db->executeStatement('DELETE FROM exam_templates');
        $this->db->executeStatement('DELETE FROM questions');
        $this->db->executeStatement('DELETE FROM users');
        $this->db->executeStatement('PRAGMA foreign_keys = ON');
    }

    public function testRegradeSession(): void
    {
        $userId = Uuid::uuid4()->toString();
        $this->db->insert('users', [
            'id' => $userId,
            'email' => 'student@test.com',
            'first_name' => 'Student',
            'last_name' => 'One',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'password' => 'secret',
        ]);

        $examId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', [
            'id' => $examId,
            'title' => 'Test Exam',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $sessionId = Uuid::uuid4()->toString();
        $this->db->insert('exam_sessions', [
            'id' => $sessionId,
            'exam_template_id' => $examId,
            'user_id' => $userId,
            'status' => 'graded', // Initially graded
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Mock an answer with marks
        $qId = Uuid::uuid4()->toString();
        $this->db->insert('questions', ['id' => $qId, 'type' => 'mcq', 'content' => '{}', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);

        $this->db->insert('exam_session_answers', [
            'id' => Uuid::uuid4()->toString(),
            'exam_session_id' => $sessionId,
            'question_id' => $qId,
            'marks_obtained' => 10.0,
            'is_correct' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Mock existing result
        $this->db->insert('exam_results', [
            'id' => Uuid::uuid4()->toString(),
            'exam_session_id' => $sessionId,
            'total_score' => 10.0,
            'max_score' => 10.0,
            'percentage' => 100.0,
            'is_passed' => 1,
            'graded_at' => date('Y-m-d H:i:s'),
        ]);

        // Expect job queue
        $this->queueMock->expects($this->once())
            ->method('push')
            ->with(GradingJob::class, ['session_id' => $sessionId]);

        // Act
        $this->service->regradeSession($sessionId);

        // Assert
        // 1. Session status reset
        $session = $this->db->fetchAssociative("SELECT status FROM exam_sessions WHERE id = ?", [$sessionId]);
        $this->assertEquals('submitted', $session['status']);

        // 2. Answer marks cleared
        $answer = $this->db->fetchAssociative("SELECT marks_obtained, is_correct FROM exam_session_answers WHERE exam_session_id = ?", [$sessionId]);
        $this->assertNull($answer['marks_obtained']);
        $this->assertNull($answer['is_correct']);

        // 3. Result deleted
        $result = $this->db->fetchAssociative("SELECT * FROM exam_results WHERE exam_session_id = ?", [$sessionId]);
        $this->assertFalse($result);
    }
}
