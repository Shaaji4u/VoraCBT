<?php

declare(strict_types=1);

namespace App\Domain\Grading\Service;

use App\Domain\Grading\Job\GradingJob;
use App\Infrastructure\Queue\QueueInterface;
use Doctrine\DBAL\Connection;
use Exception;

class RegradingService
{
    public function __construct(
        private Connection $db,
        private QueueInterface $queue
    ) {}

    public function regradeSession(string $sessionId): void
    {
        $this->db->beginTransaction();

        try {
            // Reset answers
            $this->db->executeStatement("
                UPDATE exam_session_answers
                SET marks_obtained = NULL,
                    is_correct = NULL,
                    comments = NULL
                WHERE exam_session_id = ?
            ", [$sessionId]);

            // Delete results
            $this->db->delete('exam_results', ['exam_session_id' => $sessionId]);

            // Reset session status to 'submitted' so it gets picked up by grading service logic if needed,
            // or explicitly just for status correctness.
            // GradingJob calls AutoGradingService.
            $this->db->update('exam_sessions', ['status' => 'submitted'], ['id' => $sessionId]);

            $this->db->commit();

            // Re-queue grading job
            $this->queue->push(GradingJob::class, ['session_id' => $sessionId]);

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function regradeExam(string $examTemplateId): void
    {
        // Fetch all sessions for this exam that are graded or published
        $sessions = $this->db->fetchAllAssociative(
            "SELECT id FROM exam_sessions WHERE exam_template_id = ? AND status IN ('graded', 'published')",
            [$examTemplateId]
        );

        foreach ($sessions as $session) {
            $this->regradeSession($session['id']);
        }

        // Invalidate analytics
        $this->db->delete('exam_analytics', ['exam_template_id' => $examTemplateId]);
    }
}
