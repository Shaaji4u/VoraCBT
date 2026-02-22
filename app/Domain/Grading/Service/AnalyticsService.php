<?php

declare(strict_types=1);

namespace App\Domain\Grading\Service;

use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

class AnalyticsService
{
    public function __construct(
        private Connection $db
    ) {}

    public function calculateExamAnalytics(string $examId): void
    {
        // 1. Fetch all graded results for this exam
        $sql = "
            SELECT r.total_score, r.percentage, r.is_passed
            FROM exam_results r
            JOIN exam_sessions s ON r.exam_session_id = s.id
            WHERE s.exam_template_id = :exam_id
            AND s.status IN ('graded', 'published')
        ";

        $results = $this->db->fetchAllAssociative($sql, ['exam_id' => $examId]);

        if (empty($results)) {
            return;
        }

        $scores = array_column($results, 'total_score');
        $count = count($scores);

        // Calculate metrics
        $avgScore = array_sum($scores) / $count;
        $maxScore = max($scores);
        $minScore = min($scores);

        // Median
        sort($scores);
        $middle = (int)floor(($count - 1) / 2);
        if ($count % 2) {
            $median = $scores[$middle];
        } else {
            $median = ($scores[$middle] + $scores[$middle + 1]) / 2.0;
        }

        // Std Dev
        $variance = 0.0;
        foreach ($scores as $score) {
            $variance += pow($score - $avgScore, 2);
        }
        $stdDev = ($count > 1) ? sqrt($variance / ($count - 1)) : 0.0; // Sample std dev

        // Pass Rate
        $passedCount = 0;
        foreach ($results as $r) {
            if ($r['is_passed']) $passedCount++;
        }
        $passRate = ($passedCount / $count) * 100;

        $metrics = [
            'count' => $count,
            'avg_score' => round($avgScore, 2),
            'max_score' => round($maxScore, 2),
            'min_score' => round($minScore, 2),
            'median' => round($median, 2),
            'std_dev' => round($stdDev, 2),
            'pass_rate' => round($passRate, 2),
            'calculated_at' => date('Y-m-d H:i:s'),
        ];

        // Store in exam_analytics
        $existing = $this->db->fetchAssociative("SELECT id FROM exam_analytics WHERE exam_template_id = ?", [$examId]);

        $data = [
            'exam_template_id' => $examId,
            'metrics' => json_encode($metrics, JSON_THROW_ON_ERROR),
            'calculated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->db->update('exam_analytics', $data, ['id' => $existing['id']]);
        } else {
            $data['id'] = Uuid::uuid4()->toString();
            $this->db->insert('exam_analytics', $data);
        }

        $this->calculateQuestionAnalytics($examId);
    }

    private function calculateQuestionAnalytics(string $examId): void
    {
        // Difficulty Index: % of students who answered correctly.
        // Fetch all answers for this exam
        $sql = "
            SELECT a.question_id, a.is_correct
            FROM exam_session_answers a
            JOIN exam_sessions s ON a.exam_session_id = s.id
            WHERE s.exam_template_id = :exam_id
            AND s.status IN ('graded', 'published')
        ";

        $answers = $this->db->fetchAllAssociative($sql, ['exam_id' => $examId]);

        $stats = []; // question_id -> [total, correct]

        foreach ($answers as $row) {
            $qid = $row['question_id'];
            if (!isset($stats[$qid])) {
                $stats[$qid] = ['total' => 0, 'correct' => 0];
            }
            $stats[$qid]['total']++;
            if ($row['is_correct']) {
                $stats[$qid]['correct']++;
            }
        }

        // Fetch existing metrics to append
        $analytics = $this->db->fetchAssociative("SELECT id, metrics FROM exam_analytics WHERE exam_template_id = ?", [$examId]);
        if (!$analytics) return;

        $metrics = json_decode($analytics['metrics'], true);
        $metrics['questions'] = [];

        foreach ($stats as $qid => $stat) {
            $difficulty = ($stat['total'] > 0) ? ($stat['correct'] / $stat['total']) : 0;
            $metrics['questions'][$qid] = [
                'difficulty_index' => round($difficulty, 2),
                'attempts' => $stat['total'],
                'correct_count' => $stat['correct']
            ];
        }

        $this->db->update('exam_analytics', [
            'metrics' => json_encode($metrics, JSON_THROW_ON_ERROR),
            'calculated_at' => date('Y-m-d H:i:s')
        ], ['id' => $analytics['id']]);
    }
}
