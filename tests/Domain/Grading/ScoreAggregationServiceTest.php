<?php

declare(strict_types=1);

namespace Tests\Domain\Grading;

use App\Core\Database\DatabaseManager;
use App\Core\Database\Migration\MigrationRunner;
use App\Domain\Grading\Job\AnalyticsJob;
use App\Domain\Grading\Service\ScoreAggregationService;
use App\Infrastructure\Queue\QueueInterface;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ScoreAggregationServiceTest extends TestCase
{
    private Connection $db;
    private ScoreAggregationService $service;
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
        $this->service = new ScoreAggregationService($this->db, $this->queueMock);
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

    public function testAggregateSessionScores(): void
    {
        // 1. Setup Data
        $userId = Uuid::uuid4()->toString();
        $this->db->insert('users', [
            'id' => $userId,
            'email' => 'student@test.com',
            'first_name' => 'Student',
            'last_name' => 'One',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'password' => 'secret', // Add missing required field if any (migration allows nulls?)
            // Migration: password is NOT NULL.
        ]);

        $examId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', [
            'id' => $examId,
            'title' => 'Test Exam',
            'passing_score' => 50.0, // Raw score threshold
            'publish_strategy' => 'manual',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Section 1: Weight 1.0
        $sec1 = Uuid::uuid4()->toString();
        $this->db->insert('exam_sections', [
            'id' => $sec1,
            'exam_template_id' => $examId,
            'title' => 'Section 1',
            'weight' => 1.0
        ]);

        // Section 2: Weight 0.5
        $sec2 = Uuid::uuid4()->toString();
        $this->db->insert('exam_sections', [
            'id' => $sec2,
            'exam_template_id' => $examId,
            'title' => 'Section 2',
            'weight' => 0.5
        ]);

        // Questions
        $q1 = Uuid::uuid4()->toString();
        $this->db->insert('questions', ['id' => $q1, 'type' => 'mcq', 'content' => '{}', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        $this->db->insert('exam_questions', ['id' => Uuid::uuid4()->toString(), 'exam_section_id' => $sec1, 'question_id' => $q1, 'marks' => 10.0]);

        $q2 = Uuid::uuid4()->toString();
        $this->db->insert('questions', ['id' => $q2, 'type' => 'essay', 'content' => '{}', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        $this->db->insert('exam_questions', ['id' => Uuid::uuid4()->toString(), 'exam_section_id' => $sec2, 'question_id' => $q2, 'marks' => 20.0]);

        // Session
        $sessionId = Uuid::uuid4()->toString();
        $this->db->insert('exam_sessions', [
            'id' => $sessionId,
            'exam_template_id' => $examId,
            'user_id' => $userId,
            'status' => 'submitted',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Answers
        // Q1 (Sec 1, Weight 1.0): Marks 10. Obtained 8.
        $this->db->insert('exam_session_answers', [
            'id' => Uuid::uuid4()->toString(),
            'exam_session_id' => $sessionId,
            'question_id' => $q1,
            'marks_obtained' => 8.0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Q2 (Sec 2, Weight 0.5): Marks 20. Obtained 10.
        $this->db->insert('exam_session_answers', [
            'id' => Uuid::uuid4()->toString(),
            'exam_session_id' => $sessionId,
            'question_id' => $q2,
            'marks_obtained' => 10.0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Expectation:
        // Total Max Score = (10 * 1.0) + (20 * 0.5) = 10 + 10 = 20.
        // Total Obtained = (8 * 1.0) + (10 * 0.5) = 8 + 5 = 13.
        // Percentage = 13 / 20 * 100 = 65%.
        // Grade: 60-70 is D.
        // Passed: 13 < 50 => Fail.

        $this->queueMock->expects($this->once())
            ->method('push')
            ->with(AnalyticsJob::class, ['session_id' => $sessionId]);

        // 2. Execute
        $this->service->aggregateSession($sessionId);

        // 3. Verify
        $result = $this->db->fetchAssociative("SELECT * FROM exam_results WHERE exam_session_id = ?", [$sessionId]);
        $this->assertNotEmpty($result);
        $this->assertEquals(13.0, (float)$result['total_score']);
        $this->assertEquals(20.0, (float)$result['max_score']);
        $this->assertEquals(65.0, (float)$result['percentage']);
        $this->assertEquals('D', $result['grade']);
        $this->assertEquals(0, $result['is_passed']);

        // Verify session status updated to graded
        $session = $this->db->fetchAssociative("SELECT status FROM exam_sessions WHERE id = ?", [$sessionId]);
        $this->assertEquals('graded', $session['status']);
    }
}
