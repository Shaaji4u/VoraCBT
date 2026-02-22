<?php

declare(strict_types=1);

namespace Tests\Domain\Grading;

use App\Core\Database\DatabaseManager;
use App\Core\Database\Migration\MigrationRunner;
use App\Domain\Grading\Service\ResultService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class ResultLockingServiceTest extends TestCase
{
    private Connection $db;
    private ResultService $service;

    protected function setUp(): void
    {
        ob_start();
        (new MigrationRunner())->migrate();
        ob_end_clean();

        $this->db = DatabaseManager::getConnection();
        $this->truncateTables();
        $this->service = new ResultService($this->db);
    }

    private function truncateTables(): void
    {
        $this->db->executeStatement('DELETE FROM result_lock_audit_logs');
        $this->db->executeStatement('DELETE FROM exam_results');
        $this->db->executeStatement('DELETE FROM exam_sessions');
        $this->db->executeStatement('DELETE FROM exam_templates');
        $this->db->executeStatement('DELETE FROM users');
    }

    public function testFinalizeLocksAndPreventsPublishMutation(): void
    {
        $now = date('Y-m-d H:i:s');
        $teacherId = Uuid::uuid4()->toString();
        $studentId = Uuid::uuid4()->toString();
        $examId = Uuid::uuid4()->toString();
        $sessionId = Uuid::uuid4()->toString();
        $resultId = Uuid::uuid4()->toString();

        foreach ([
            [$teacherId, 'teacher@example.com'],
            [$studentId, 'student@example.com'],
        ] as [$id, $email]) {
            $this->db->insert('users', [
                'id' => $id,
                'email' => $email,
                'password' => 'x',
                'first_name' => 'First',
                'last_name' => 'Last',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->db->insert('exam_templates', [
            'id' => $examId,
            'title' => 'Mathematics',
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $teacherId,
            'updated_by' => $teacherId,
            'lifecycle_state' => 'under_review',
        ]);

        $this->db->insert('exam_sessions', [
            'id' => $sessionId,
            'exam_template_id' => $examId,
            'user_id' => $studentId,
            'status' => 'graded',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->db->insert('exam_results', [
            'id' => $resultId,
            'exam_session_id' => $sessionId,
            'total_score' => 70,
            'max_score' => 100,
            'percentage' => 70,
            'grade' => 'C',
            'is_passed' => 1,
            'graded_at' => $now,
            'graded_by' => $teacherId,
        ]);

        $this->service->finalizeResult($sessionId, $teacherId, 'approved by academic board');

        $this->expectException(RuntimeException::class);
        $this->service->publishResult($sessionId);
    }
}
