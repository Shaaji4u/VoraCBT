<?php

declare(strict_types=1);

namespace App\Domain\Grading\Service;

use Doctrine\DBAL\Connection;
use RuntimeException;

class ResultService
{
    public function __construct(
        private Connection $db
    ) {}

    public function publishResult(string $sessionId): void
    {
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
}
