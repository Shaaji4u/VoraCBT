<?php

declare(strict_types=1);

namespace App\Domain\Grading\Service;

use App\Domain\Grading\Job\ScoreAggregationJob;
use App\Domain\Grading\Service\GradingStrategy\FillInTheBlankGradingStrategy;
use App\Domain\Grading\Service\GradingStrategy\GradingStrategyInterface;
use App\Domain\Grading\Service\GradingStrategy\MatchingGradingStrategy;
use App\Domain\Grading\Service\GradingStrategy\MCQGradingStrategy;
use App\Domain\Grading\Service\GradingStrategy\NumericalGradingStrategy;
use App\Infrastructure\Queue\QueueInterface;
use Doctrine\DBAL\Connection;
use Exception;

class AutoGradingService
{
    private array $strategies = [];

    public function __construct(
        private Connection $db,
        private QueueInterface $queue
    ) {
        $this->registerStrategy(new MCQGradingStrategy());
        $this->registerStrategy(new FillInTheBlankGradingStrategy());
        $this->registerStrategy(new NumericalGradingStrategy());
        $this->registerStrategy(new MatchingGradingStrategy());
    }

    private function registerStrategy(GradingStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    private function getStrategy(string $type): ?GradingStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canGrade($type)) {
                return $strategy;
            }
        }
        return null;
    }

    public function gradeSession(string $sessionId): void
    {
        $sql = "
            SELECT
                a.id as answer_id,
                a.answer_payload,
                q.id as question_id,
                q.type as question_type,
                q.content as question_content,
                eq.marks as max_marks
            FROM exam_session_answers a
            JOIN exam_sessions s ON a.exam_session_id = s.id
            JOIN questions q ON a.question_id = q.id
            JOIN exam_sections sec ON sec.exam_template_id = s.exam_template_id
            JOIN exam_questions eq ON eq.question_id = q.id AND eq.exam_section_id = sec.id
            WHERE a.exam_session_id = :session_id
        ";

        $answers = $this->db->fetchAllAssociative($sql, ['session_id' => $sessionId]);

        foreach ($answers as $row) {
            $type = $row['question_type'];
            $strategy = $this->getStrategy($type);

            if (!$strategy) {
                continue;
            }

            try {
                $questionContent = json_decode($row['question_content'], true, 512, JSON_THROW_ON_ERROR);
                $answerPayload = json_decode($row['answer_payload'], true, 512, JSON_THROW_ON_ERROR);
                $maxMarks = (float)$row['max_marks'];

                // Default config, ideally fetched from exam template settings
                $config = ['partial_scoring' => true];

                $result = $strategy->grade($questionContent, $answerPayload, $maxMarks, $config);

                $this->db->update('exam_session_answers', [
                    'marks_obtained' => $result->marksObtained,
                    'is_correct' => $result->isCorrect,
                    'comments' => $result->feedback,
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['id' => $row['answer_id']]);

            } catch (Exception $e) {
                // Log error but continue grading other questions
                // Ideally use a logger service
            }
        }

        // Trigger Score Aggregation
        $this->queue->push(ScoreAggregationJob::class, ['session_id' => $sessionId]);
    }
}
