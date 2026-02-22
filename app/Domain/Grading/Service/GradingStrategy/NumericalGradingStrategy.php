<?php

declare(strict_types=1);

namespace App\Domain\Grading\Service\GradingStrategy;

use App\Domain\Grading\DTO\GradingResult;

class NumericalGradingStrategy implements GradingStrategyInterface
{
    public function canGrade(string $questionType): bool
    {
        return $questionType === 'numerical';
    }

    public function grade(array $questionContent, array $answerPayload, float $maxMarks, array $config = []): GradingResult
    {
        $correctValue = $questionContent['answer'] ?? null;
        $tolerance = isset($questionContent['tolerance']) ? (float)$questionContent['tolerance'] : 0.0;

        // Handle answerPayload structure
        // Assuming payload has 'value' key
        $userValue = $answerPayload['value'] ?? null;

        if ($correctValue === null) {
            return new GradingResult(0.0, false, "Invalid question configuration: no correct answer defined.");
        }

        if ($userValue === null || trim((string)$userValue) === '') {
            return new GradingResult(0.0, false, "No answer provided.");
        }

        if (!is_numeric($userValue)) {
            return new GradingResult(0.0, false, "Answer is not a valid number.");
        }

        $userValue = (float)$userValue;
        $correctValue = (float)$correctValue;

        // Check if tolerance is percentage?
        // Usually, if tolerance is explicitly '5%', it's string.
        // But validation said `is_numeric`. So likely absolute.
        // If we want percentage, we might check another field `tolerance_type`.
        // I'll stick to absolute tolerance as per validation.

        if (abs($userValue - $correctValue) <= $tolerance) {
            return new GradingResult($maxMarks, true);
        }

        return new GradingResult(0.0, false, "Incorrect value.");
    }
}
