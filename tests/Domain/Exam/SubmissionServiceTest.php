<?php

declare(strict_types=1);

namespace Tests\Domain\Exam;

use App\Domain\Exam\Service\ExamSessionService;
use App\Domain\Exam\Service\SubmissionService;
use App\Domain\Exam\Service\RandomizationService;
use App\Domain\Exam\Service\TimerService;
use App\Core\Database\Migration\MigrationRunner;
use App\Core\Database\DatabaseManager;
use App\Infrastructure\Queue\QueueInterface;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use DateTime;
use Exception;

class SubmissionServiceTest extends TestCase
{
    private ExamSessionService $sessionService;
    private SubmissionService $submissionService;
    private Connection $db;

    protected function setUp(): void
    {
        ob_start();
        $runner = new MigrationRunner();
        $runner->migrate();
        ob_end_clean();

        $this->db = DatabaseManager::getConnection();
        $this->truncateTables();

        $this->createUser('user-1');

        // Use real services
        $randomizationService = new RandomizationService();
        $timerService = new TimerService();

        // Mock Queue
        $queue = $this->createMock(QueueInterface::class);
        $queue->method('push')->willReturn('job-id');

        $this->sessionService = new ExamSessionService($randomizationService, $timerService, $queue);
        $this->submissionService = new SubmissionService($timerService, $queue);
    }

    private function truncateTables(): void
    {
        $this->db->executeStatement('PRAGMA foreign_keys = OFF');
        $this->db->executeStatement('DELETE FROM exam_session_answers'); // Was answers
        $this->db->executeStatement('DELETE FROM exam_session_questions');
        $this->db->executeStatement('DELETE FROM exam_sessions');
        $this->db->executeStatement('DELETE FROM exam_questions');
        $this->db->executeStatement('DELETE FROM exam_sections');
        $this->db->executeStatement('DELETE FROM exam_templates');
        $this->db->executeStatement('DELETE FROM questions');
        $this->db->executeStatement('DELETE FROM users');
        $this->db->executeStatement('PRAGMA foreign_keys = ON');
    }

    private function createUser(string $userId): void
    {
        $this->db->insert('users', [
            'id' => $userId,
            'email' => "user-{$userId}@example.com",
            'password' => 'secret',
            'first_name' => 'Test',
            'last_name' => 'User',
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    public function testStartSession(): void
    {
        $templateId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', [
            'id' => $templateId,
            'title' => 'T1',
            'duration_minutes' => 60,
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        $sessionId = $this->sessionService->startSession($templateId, 'user-1');

        $this->assertIsString($sessionId);

        $session = $this->db->fetchAssociative('SELECT * FROM exam_sessions WHERE id = ?', [$sessionId]);
        $this->assertEquals('in_progress', $session['status']);
        $this->assertNotNull($session['seed']);
    }

    public function testSaveAnswer(): void
    {
        $templateId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', [
            'id' => $templateId,
            'title' => 'T1',
            'duration_minutes' => 60,
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        // Add section and questions to ensure session start works and we have questions
        $sectionId = Uuid::uuid4()->toString();
        $this->db->insert('exam_sections', [
            'id' => $sectionId,
            'exam_template_id' => $templateId,
            'title' => 'S1',
            'question_selection_rules' => json_encode([['type' => 'mcq', 'count' => 1]])
        ]);

        $questionId = Uuid::uuid4()->toString();
        $this->db->insert('questions', [
            'id' => $questionId,
            'type' => 'mcq',
            'content' => json_encode(['prompt' => 'Q1']),
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        $sessionId = $this->sessionService->startSession($templateId, 'user-1');

        // Check session questions
        $sessionQ = $this->db->fetchOne('SELECT question_id FROM exam_session_questions WHERE exam_session_id = ?', [$sessionId]);
        $this->assertEquals($questionId, $sessionQ);

        // Save answer
        $this->sessionService->saveAnswer($sessionId, $questionId, ['option_id' => 1]);

        $answer = $this->db->fetchAssociative('SELECT * FROM exam_session_answers WHERE exam_session_id = ? AND question_id = ?', [$sessionId, $questionId]);
        $this->assertNotEmpty($answer);
        $this->assertStringContainsString('option_id', $answer['answer_payload']);

        // Check status updated
        $status = $this->db->fetchOne('SELECT status FROM exam_session_questions WHERE exam_session_id = ? AND question_id = ?', [$sessionId, $questionId]);
        $this->assertEquals('answered', $status);
    }

    public function testSubmitSession(): void
    {
        $templateId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', [
            'id' => $templateId,
            'title' => 'T1',
            'duration_minutes' => 60,
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        $sessionId = $this->sessionService->startSession($templateId, 'user-1');

        $this->submissionService->submitSession($sessionId);

        $session = $this->db->fetchAssociative('SELECT * FROM exam_sessions WHERE id = ?', [$sessionId]);
        $this->assertEquals('submitted', $session['status']);
        $this->assertNotNull($session['end_time']);
    }

    public function testSubmitSessionExpired(): void
    {
        $templateId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', [
            'id' => $templateId,
            'title' => 'T1',
            'duration_minutes' => 10,
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        $sessionId = $this->sessionService->startSession($templateId, 'user-1');

        // Manipulate start time to be 20 mins ago (expired)
        $this->db->update('exam_sessions', [
            'start_time' => (new DateTime())->modify('-20 minutes')->format('Y-m-d H:i:s')
        ], ['id' => $sessionId]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Time expired');

        $this->submissionService->submitSession($sessionId);
    }
}
