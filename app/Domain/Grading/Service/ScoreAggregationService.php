<?php

declare(strict_types=1);

namespace App\Domain\Grading\Service;

use App\Domain\Grading\Job\AnalyticsJob;
use App\Infrastructure\Queue\QueueInterface;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

class ScoreAggregationService
{
    public function __construct(
        private Connection $db,
        private QueueInterface $queue
    ) {}

    public function aggregateSession(string $sessionId): void
    {
        // Fetch answers with section info to apply weights
        $sql = "
            SELECT
                a.marks_obtained,
                eq.marks as max_marks,
                sec.weight as section_weight
            FROM exam_session_answers a
            JOIN questions q ON a.question_id = q.id
            JOIN exam_sessions s ON a.exam_session_id = s.id
            JOIN exam_sections sec ON sec.exam_template_id = s.exam_template_id
            JOIN exam_questions eq ON eq.question_id = q.id AND eq.exam_section_id = sec.id
            WHERE a.exam_session_id = :session_id
        ";

        $rows = $this->db->fetchAllAssociative($sql, ['session_id' => $sessionId]);

        // If no answers, score is 0
        $totalScore = 0.0;
        $totalMaxScore = 0.0;

        foreach ($rows as $row) {
            $obtained = isset($row['marks_obtained']) ? (float)$row['marks_obtained'] : 0.0;
            $max = (float)$row['max_marks'];
            $weight = isset($row['section_weight']) ? (float)$row['section_weight'] : 1.0;

            $totalScore += ($obtained * $weight);
            $totalMaxScore += ($max * $weight);
        }

        $percentage = ($totalMaxScore > 0) ? ($totalScore / $totalMaxScore) * 100 : 0.0;

        // Fetch exam config for passing score
        $examConfig = $this->db->fetchAssociative(
            "SELECT t.passing_score, t.publish_strategy FROM exam_templates t JOIN exam_sessions s ON s.exam_template_id = t.id WHERE s.id = ?",
            [$sessionId]
        );

        $passingScore = isset($examConfig['passing_score']) ? (float)$examConfig['passing_score'] : null;
        $isPassed = false;

        if ($passingScore !== null) {
            // Assume passing score is raw score unless percentage logic is implied (usually raw)
            $isPassed = $totalScore >= $passingScore;
        } else {
            $isPassed = $percentage >= 50.0; // Default fallback
        }

        $grade = $this->calculateGrade($percentage);

        // Store Result
        $existingResult = $this->db->fetchAssociative("SELECT id FROM exam_results WHERE exam_session_id = ?", [$sessionId]);

        $resultId = $existingResult ? $existingResult['id'] : Uuid::uuid4()->toString();

        if ($existingResult && (bool) ($this->db->fetchOne('SELECT is_locked FROM exam_results WHERE id = ?', [$resultId]) ?? false) === true) {
            return;
        }

        $data = [
            'exam_session_id' => $sessionId,
            'total_score' => $totalScore,
            'max_score' => $totalMaxScore,
            'percentage' => $percentage,
            'grade' => $grade,
            'is_passed' => $isPassed ? 1 : 0,
            'graded_at' => date('Y-m-d H:i:s'),
        ];

        if ($existingResult) {
            $this->db->update('exam_results', $data, ['id' => $resultId]);
        } else {
            $data['id'] = $resultId;
            $this->db->insert('exam_results', $data);
        }

        // Update Session Status
        // If publish strategy is 'auto', we might set to 'published' or handle in ResultService?
        // Prompt says "Result states: ... PUBLISHED".
        // I'll set status to 'graded' here. ResultService can promote to 'published'.
        $this->db->update('exam_sessions', ['status' => 'graded'], ['id' => $sessionId]);

        // Trigger Analytics
        $this->queue->push(AnalyticsJob::class, ['session_id' => $sessionId]);

        // TODO: Trigger Result Publishing check (ResultService)
        // Since we don't have ResultService injected, and no job for it, maybe AnalyticsJob or another job handles it?
        // Or we just update status here if 'auto'?
        if (($examConfig['publish_strategy'] ?? 'manual') === 'auto') {
             $this->db->update('exam_sessions', ['status' => 'published'], ['id' => $sessionId]);
        }
    }

    private function calculateGrade(float $percentage): string
    {
        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        return 'F';
    }
}
