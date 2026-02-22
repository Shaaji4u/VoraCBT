<?php

declare(strict_types=1);

namespace Tests\Unit\Proctoring;

use App\Domain\Proctoring\Service\ProctoringService;
use App\Core\Service\AuditLogService;
use App\Domain\Proctoring\Service\DeviceFingerprintService;
use App\Core\Database\Migration\MigrationRunner;
use App\Core\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use DateTime;

class ProctoringServiceTest extends TestCase
{
    private ProctoringService $service;
    private Connection $db;

    protected function setUp(): void
    {
        $runner = new MigrationRunner();
        $runner->migrate();

        $this->db = DatabaseManager::getConnection();
        $this->truncateTables();

        $auditLog = $this->createMock(AuditLogService::class);
        $fingerprint = $this->createMock(DeviceFingerprintService::class);
        $fingerprint->method('generate')->willReturn('hash-123');

        $this->service = new ProctoringService($auditLog, $fingerprint);
    }

    private function truncateTables(): void
    {
        $this->db->executeStatement('PRAGMA foreign_keys = OFF');
        $this->db->executeStatement('DELETE FROM proctoring_logs');
        $this->db->executeStatement('DELETE FROM proctoring_sessions');
        $this->db->executeStatement('DELETE FROM exam_sessions');
        $this->db->executeStatement('DELETE FROM exam_templates');
        $this->db->executeStatement('DELETE FROM users');
        $this->db->executeStatement('PRAGMA foreign_keys = ON');
    }

    public function testStartProctoringSession(): void
    {
        // Setup data
        $examSessionId = Uuid::uuid4()->toString();
        $studentId = Uuid::uuid4()->toString();
        $this->db->insert('users', ['id' => $studentId, 'email' => 's@test.com', 'password' => 'pass', 'first_name' => 'T', 'last_name' => 'U', 'created_at' => '2023-01-01 00:00:00', 'updated_at' => '2023-01-01 00:00:00']);

        $examTemplateId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', ['id' => $examTemplateId, 'title' => 'Test Exam', 'created_at' => '2023-01-01 00:00:00', 'updated_at' => '2023-01-01 00:00:00']);

        $this->db->insert('exam_sessions', [
            'id' => $examSessionId,
            'exam_template_id' => $examTemplateId,
            'user_id' => $studentId,
            'status' => 'in_progress',
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00'
        ]);

        $token = $this->service->startProctoringSession($examSessionId, $studentId, []);

        $this->assertNotNull($token);

        $session = $this->db->fetchAssociative('SELECT * FROM proctoring_sessions WHERE exam_session_id = ?', [$examSessionId]);
        $this->assertNotFalse($session);
        $this->assertEquals($token, $session['session_token']);
        $this->assertEquals('hash-123', $session['device_hash']);
    }

    public function testLogEventSuspicious(): void
    {
         // Setup
        $examSessionId = Uuid::uuid4()->toString();
        $studentId = Uuid::uuid4()->toString();
        $this->db->insert('users', ['id' => $studentId, 'email' => 's@test.com', 'password' => 'pass', 'first_name' => 'T', 'last_name' => 'U', 'created_at' => '2023-01-01 00:00:00', 'updated_at' => '2023-01-01 00:00:00']);

        $examTemplateId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', ['id' => $examTemplateId, 'title' => 'Test Exam', 'created_at' => '2023-01-01 00:00:00', 'updated_at' => '2023-01-01 00:00:00']);

        $this->db->insert('exam_sessions', [
            'id' => $examSessionId,
            'exam_template_id' => $examTemplateId,
            'user_id' => $studentId,
            'status' => 'in_progress',
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00'
        ]);

        $token = $this->service->startProctoringSession($examSessionId, $studentId, []);
        $sessionId = $this->service->getSessionIdByToken($token);

        // Log suspicious event
        $this->service->logEvent($sessionId, 'tab_switch', [], 'warning');

        $session = $this->db->fetchAssociative('SELECT * FROM proctoring_sessions WHERE id = ?', [$sessionId]);
        $this->assertEquals(1, $session['suspicious_events_count']);
    }
}
