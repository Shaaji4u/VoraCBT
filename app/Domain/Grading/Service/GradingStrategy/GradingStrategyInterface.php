<?php

declare(strict_types=1);

namespace App\Domain\Grading\Service\GradingStrategy;

use App\Domain\Grading\DTO\GradingResult;

interface GradingStrategyInterface
{
    /**
     * Determine if this strategy can grade the given question type.
     */
    public function canGrade(string $questionType): bool;

    /**
     * Grade the answer against the question.
     *
     * @param array $questionContent The content of the question (containing correct answers).
     * @param array $answerPayload The student's answer payload.
     * @param float $maxMarks The maximum marks for this question.
     * @param array $config Optional configuration (e.g., partial scoring).
     * @return GradingResult
     */
    public function grade(array $questionContent, array $answerPayload, float $maxMarks, array $config = []): GradingResult;
}
