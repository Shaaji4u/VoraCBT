<?php

declare(strict_types=1);

namespace Tests\Domain\Exam;

use App\Domain\Exam\Service\TimerService;
use App\Core\Database\Migration\MigrationRunner;
use App\Core\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use DateTime;

class TimerServiceTest extends TestCase
{
    private TimerService $timerService;
    private Connection $db;

    protected function setUp(): void
    {
        ob_start();
        $runner = new MigrationRunner();
        $runner->migrate();
        ob_end_clean();

        $this->db = DatabaseManager::getConnection();
        $this->truncateTables();

        $this->timerService = new TimerService();
    }

    private function truncateTables(): void
    {
        $this->db->executeStatement('PRAGMA foreign_keys = OFF');
        $this->db->executeStatement('DELETE FROM exam_session_sections');
        $this->db->executeStatement('DELETE FROM exam_sessions');
        $this->db->executeStatement('DELETE FROM exam_templates');
        $this->db->executeStatement('DELETE FROM exam_sections');
        $this->db->executeStatement('PRAGMA foreign_keys = ON');
    }

    public function testGetRemainingTimeExamDuration(): void
    {
        $userId = Uuid::uuid4()->toString();
        $this->createUser($userId);

        // Exam with 60 mins duration
        $templateId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', [
            'id' => $templateId,
            'title' => 'T1',
            'duration_minutes' => 60,
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        $sessionId = Uuid::uuid4()->toString();
        $start = (new DateTime())->modify('-30 minutes'); // Started 30 mins ago

        $this->db->insert('exam_sessions', [
            'id' => $sessionId,
            'exam_template_id' => $templateId,
            'user_id' => $userId,
            'status' => 'in_progress',
            'start_time' => $start->format('Y-m-d H:i:s'),
            'created_at' => $start->format('Y-m-d H:i:s'),
            'updated_at' => $start->format('Y-m-d H:i:s')
        ]);

        $remaining = $this->timerService->getRemainingTime($sessionId);

        // Expected ~30 mins * 60 = 1800s
        $this->assertEqualsWithDelta(1800, $remaining, 5); // Allow 5s delta
    }

    public function testGetRemainingTimeSectionDuration(): void
    {
        $userId = Uuid::uuid4()->toString();
        $this->createUser($userId);

        // Exam Unlimited
        $templateId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', [
            'id' => $templateId,
            'title' => 'T2',
            'duration_minutes' => 0,
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        // Section 10 mins
        $sectionId = Uuid::uuid4()->toString();
        $this->db->insert('exam_sections', [
            'id' => $sectionId,
            'exam_template_id' => $templateId,
            'title' => 'S1',
            'duration_minutes' => 10,
            'weight' => 1
        ]);

        $sessionId = Uuid::uuid4()->toString();
        $start = (new DateTime())->modify('-5 minutes'); // Started 5 mins ago

        $this->db->insert('exam_sessions', [
            'id' => $sessionId,
            'exam_template_id' => $templateId,
            'user_id' => $userId,
            'status' => 'in_progress',
            'start_time' => (new DateTime())->format('Y-m-d H:i:s'),
            'current_section_id' => $sectionId,
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        $this->db->insert('exam_session_sections', [
            'id' => Uuid::uuid4()->toString(),
            'exam_session_id' => $sessionId,
            'exam_section_id' => $sectionId,
            'status' => 'in_progress',
            'started_at' => $start->format('Y-m-d H:i:s'),
            'created_at' => $start->format('Y-m-d H:i:s'),
            'updated_at' => $start->format('Y-m-d H:i:s')
        ]);

        $remaining = $this->timerService->getRemainingTime($sessionId);

        // Expected ~5 mins * 60 = 300s
        $this->assertEqualsWithDelta(300, $remaining, 5);
    }

    public function testGetRemainingTimeBothDurations(): void
    {
        $userId = Uuid::uuid4()->toString();
        $this->createUser($userId);

        // Exam 60 mins (started 30 mins ago -> 30 rem)
        // Section 10 mins (started 1 min ago -> 9 rem)
        // Expected: 9 mins (stricter limit for section? No, min(exam_rem, section_rem))

        $templateId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', [
            'id' => $templateId,
            'title' => 'T3',
            'duration_minutes' => 60,
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        $sectionId = Uuid::uuid4()->toString();
        $this->db->insert('exam_sections', [
            'id' => $sectionId,
            'exam_template_id' => $templateId,
            'title' => 'S1',
            'duration_minutes' => 10,
            'weight' => 1
        ]);

        $sessionId = Uuid::uuid4()->toString();
        $startExam = (new DateTime())->modify('-30 minutes');
        $startSection = (new DateTime())->modify('-1 minutes'); // 9 mins remaining

        $this->db->insert('exam_sessions', [
            'id' => $sessionId,
            'exam_template_id' => $templateId,
            'user_id' => $userId,
            'status' => 'in_progress',
            'start_time' => $startExam->format('Y-m-d H:i:s'),
            'current_section_id' => $sectionId,
            'created_at' => $startExam->format('Y-m-d H:i:s'),
            'updated_at' => $startExam->format('Y-m-d H:i:s')
        ]);

        $this->db->insert('exam_session_sections', [
            'id' => Uuid::uuid4()->toString(),
            'exam_session_id' => $sessionId,
            'exam_section_id' => $sectionId,
            'status' => 'in_progress',
            'started_at' => $startSection->format('Y-m-d H:i:s'),
            'created_at' => $startSection->format('Y-m-d H:i:s'),
            'updated_at' => $startSection->format('Y-m-d H:i:s')
        ]);

        $remaining = $this->timerService->getRemainingTime($sessionId);

        // Expected 9 mins * 60 = 540s
        $this->assertEqualsWithDelta(540, $remaining, 5);
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
}
