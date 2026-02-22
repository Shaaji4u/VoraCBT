<?php

declare(strict_types=1);

namespace Tests\Domain\Exam;

use App\Domain\Exam\Service\ExamSessionService;
use App\Domain\Exam\Service\RandomizationService;
use App\Domain\Exam\Service\TimerService;
use App\Domain\Proctoring\Service\ProctoringService;
use App\Domain\Proctoring\Service\SessionIntegrityService;
use App\Core\Service\RateLimitService;
use App\Core\Database\Migration\MigrationRunner;
use App\Core\Database\DatabaseManager;
use App\Infrastructure\Queue\QueueInterface;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use DateTime;
use Exception;

class ExamSessionServiceTest extends TestCase
{
    private ExamSessionService $sessionService;
    private Connection $db;

    protected function setUp(): void
    {
        $runner = new MigrationRunner();
        $runner->migrate();

        $this->db = DatabaseManager::getConnection();
        $this->truncateTables();

        $this->createUser('user-1');

        $randomizationService = new RandomizationService();
        $timerService = new TimerService();
        $queue = $this->createMock(QueueInterface::class);
        $proctoringService = $this->createMock(ProctoringService::class);
        $proctoringService->method('startProctoringSession')->willReturn('test-token');
        $proctoringService->method('getSessionToken')->willReturn('test-token');

        $integrityService = $this->createMock(SessionIntegrityService::class);
        $rateLimitService = $this->createMock(RateLimitService::class);
        $rateLimitService->method('check')->willReturn(true);

        $this->sessionService = new ExamSessionService(
            $randomizationService,
            $timerService,
            $queue,
            $proctoringService,
            $integrityService,
            $rateLimitService
        );
    }

    private function truncateTables(): void
    {
        $this->db->executeStatement('PRAGMA foreign_keys = OFF');
        $this->db->executeStatement('DELETE FROM exam_session_sections');
        $this->db->executeStatement('DELETE FROM exam_session_answers');
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

    public function testStartSectionIdempotency(): void
    {
        // 1. Setup
        $templateId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', [
            'id' => $templateId,
            'title' => 'T1',
            'duration_minutes' => 60,
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        $sectionId = Uuid::uuid4()->toString();
        $this->db->insert('exam_sections', [
            'id' => $sectionId,
            'exam_template_id' => $templateId,
            'title' => 'S1',
            'weight' => 1
        ]);

        $session = $this->sessionService->startSession($templateId, 'user-1');
        $sessionId = $session['id'];

        // 2. Start Section First Time
        $this->sessionService->startSection($sessionId, $sectionId);

        $firstStart = $this->db->fetchAssociative(
            'SELECT * FROM exam_session_sections WHERE exam_session_id = ? AND exam_section_id = ?',
            [$sessionId, $sectionId]
        );
        $this->assertNotEmpty($firstStart);
        $this->assertNotNull($firstStart['started_at']);

        // 3. Start Section Second Time (simulate re-entry)
        sleep(1); // Ensure time tick
        $this->sessionService->startSection($sessionId, $sectionId);

        $secondStart = $this->db->fetchAssociative(
            'SELECT * FROM exam_session_sections WHERE exam_session_id = ? AND exam_section_id = ?',
            [$sessionId, $sectionId]
        );

        // Started At should be SAME
        $this->assertEquals($firstStart['started_at'], $secondStart['started_at']);
        // Updated At should be DIFFERENT (newer)
        $this->assertNotEquals($firstStart['updated_at'], $secondStart['updated_at']);
    }

    public function testLockedSectionAccess(): void
    {
        // 1. Setup with locking enabled
        $templateId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', [
            'id' => $templateId,
            'title' => 'Locked Exam',
            'duration_minutes' => 60,
            'lock_sections' => true,
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        $sectionId1 = Uuid::uuid4()->toString();
        $this->db->insert('exam_sections', [
            'id' => $sectionId1,
            'exam_template_id' => $templateId,
            'title' => 'S1'
        ]);

        $session = $this->sessionService->startSession($templateId, 'user-1');
        $sessionId = $session['id'];

        // 2. Start and Complete section using service
        $this->sessionService->startSection($sessionId, $sectionId1);
        $this->sessionService->completeSection($sessionId, $sectionId1);

        // 3. Try to enter completed section
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Section is locked');

        $this->sessionService->startSection($sessionId, $sectionId1);
    }
}
