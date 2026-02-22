<?php

declare(strict_types=1);

namespace App\Domain\Grading\Service;

use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class ResultService
{
    public function __construct(
        private Connection $db
    ) {}

    public function publishResult(string $sessionId): void
    {
        $lockState = $this->db->fetchOne(
            "SELECT is_locked FROM exam_results WHERE exam_session_id = ?",
            [$sessionId]
        );

        if ((bool) $lockState === true) {
            throw new RuntimeException('Result is finalized and locked. Publishing changes require admin override.');
        }

        // Check if result exists
        $result = $this->db->fetchAssociative("SELECT id FROM exam_results WHERE exam_session_id = ?", [$sessionId]);
        if (!$result) {
            throw new RuntimeException("Result not found for session $sessionId");
        }

        $this->db->update('exam_sessions', ['status' => 'published'], ['id' => $sessionId]);

        // TODO: Trigger 'onResultPublished' hook/event
    }

    public function publishExamResults(string $examId): void
    {
        // Publish all graded results for an exam
        $sql = "
            UPDATE exam_sessions
            SET status = 'published'
            WHERE exam_template_id = ?
            AND status = 'graded'
        ";
        $this->db->executeStatement($sql, [$examId]);
    }

    public function getStudentResult(string $sessionId): ?array
    {
        // Return detailed result if published
        $session = $this->db->fetchAssociative("SELECT status FROM exam_sessions WHERE id = ?", [$sessionId]);

        if (!$session || $session['status'] !== 'published') {
             return null;
        }

        $result = $this->db->fetchAssociative("SELECT * FROM exam_results WHERE exam_session_id = ?", [$sessionId]);
        return $result ?: null;
    }

    public function finalizeResult(string $sessionId, ?string $approvedBy, ?string $reason = null): void
    {
        $result = $this->db->fetchAssociative('SELECT id, is_locked FROM exam_results WHERE exam_session_id = ?', [$sessionId]);
        if (!$result) {
            throw new RuntimeException("Result not found for session $sessionId");
        }

        if ((bool) ($result['is_locked'] ?? false) === true) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->update('exam_results', [
            'is_locked' => 1,
            'locked_at' => $now,
            'locked_by' => $approvedBy,
            'lock_reason' => $reason,
        ], ['id' => $result['id']]);

        $this->db->insert('result_lock_audit_logs', [
            'id' => Uuid::uuid4()->toString(),
            'exam_result_id' => $result['id'],
            'actor_user_id' => $approvedBy,
            'action' => 'LOCKED',
            'notes' => $reason,
            'created_at' => $now,
        ]);
    }

    public function adminOverrideUnlock(string $sessionId, string $adminUserId, string $reason): void
    {
        $result = $this->db->fetchAssociative('SELECT id FROM exam_results WHERE exam_session_id = ?', [$sessionId]);
        if (!$result) {
            throw new RuntimeException("Result not found for session $sessionId");
        }

        $now = date('Y-m-d H:i:s');

        $this->db->update('exam_results', [
            'is_locked' => 0,
            'locked_at' => null,
            'locked_by' => null,
            'lock_reason' => null,
        ], ['id' => $result['id']]);

        $this->db->insert('result_lock_audit_logs', [
            'id' => Uuid::uuid4()->toString(),
            'exam_result_id' => $result['id'],
            'actor_user_id' => $adminUserId,
            'action' => 'ADMIN_OVERRIDE_UNLOCK',
            'notes' => $reason,
            'created_at' => $now,
        ]);
    }
}
