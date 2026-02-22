<?php

declare(strict_types=1);

namespace App\Domain\Grading\Service;

use App\Domain\Grading\Job\ScoreAggregationJob;
use App\Infrastructure\Queue\QueueInterface;
use Doctrine\DBAL\Connection;

class ManualGradingService
{
    public function __construct(
        private Connection $db,
        private QueueInterface $queue
    ) {}

    /**
     * Get sessions that require manual grading.
     *
     * @param string|null $teacherId Optional teacher ID to filter by assigned classes (not implemented yet).
     * @param int $limit
     * @return array List of sessions with student details.
     */
    public function getPendingSessions(string $teacherId = null, int $limit = 20): array
    {
        $sql = "
            SELECT DISTINCT
                s.id as session_id,
                s.user_id,
                s.exam_template_id,
                t.title as exam_title,
                u.first_name,
                u.last_name,
                u.email as student_email,
                s.end_time as submitted_at
            FROM exam_sessions s
            JOIN exam_session_answers a ON s.id = a.exam_session_id
            JOIN exam_templates t ON s.exam_template_id = t.id
            JOIN users u ON s.user_id = u.id
            WHERE a.marks_obtained IS NULL
            AND s.status IN ('submitted', 'completed')
            ORDER BY s.end_time ASC
            LIMIT :limit
        ";

        return $this->db->fetchAllAssociative($sql, ['limit' => $limit]);
    }

    /**
     * Get specific ungraded answers for a session.
     */
    public function getUngradedAnswers(string $sessionId): array
    {
        $sql = "
            SELECT
                a.id as answer_id,
                a.answer_payload,
                q.content as question_content,
                q.type as question_type,
                eq.marks as max_marks
            FROM exam_session_answers a
            JOIN questions q ON a.question_id = q.id
            JOIN exam_sessions s ON a.exam_session_id = s.id
            JOIN exam_sections sec ON sec.exam_template_id = s.exam_template_id
            JOIN exam_questions eq ON eq.question_id = q.id AND eq.exam_section_id = sec.id
            WHERE a.exam_session_id = :session_id
            AND a.marks_obtained IS NULL
        ";

        return $this->db->fetchAllAssociative($sql, ['session_id' => $sessionId]);
    }

    /**
     * Submit a manual grade for an answer.
     */
    public function submitGrade(string $answerId, float $marks, string $comments): void
    {
        $resultLockSql = "
            SELECT r.is_locked
            FROM exam_session_answers a
            JOIN exam_results r ON r.exam_session_id = a.exam_session_id
            WHERE a.id = ?
        ";
        $isLocked = $this->db->fetchOne($resultLockSql, [$answerId]);
        if ((bool) $isLocked === true) {
            throw new \RuntimeException('Result is finalized and locked. Admin override is required before regrading.');
        }

        $this->db->update('exam_session_answers', [
            'marks_obtained' => $marks,
            'comments' => $comments,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $answerId]);

        // Check if session is fully graded
        $answer = $this->db->fetchAssociative("SELECT exam_session_id FROM exam_session_answers WHERE id = ?", [$answerId]);

        if ($answer) {
            $sessionId = $answer['exam_session_id'];

            // Check count of remaining ungraded answers
            $countSql = "SELECT COUNT(*) FROM exam_session_answers WHERE exam_session_id = ? AND marks_obtained IS NULL";
            $ungradedCount = $this->db->fetchOne($countSql, [$sessionId]);

            if ($ungradedCount == 0) {
                // All graded, trigger aggregation
                $this->queue->push(ScoreAggregationJob::class, ['session_id' => $sessionId]);
            }
        }
    }
}
