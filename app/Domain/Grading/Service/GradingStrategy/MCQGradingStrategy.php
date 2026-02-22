<?php

declare(strict_types=1);

namespace App\Domain\Grading\Service\GradingStrategy;

use App\Domain\Grading\DTO\GradingResult;

class MCQGradingStrategy implements GradingStrategyInterface
{
    public function canGrade(string $questionType): bool
    {
        return $questionType === 'mcq';
    }

    public function grade(array $questionContent, array $answerPayload, float $maxMarks, array $config = []): GradingResult
    {
        $correctOptions = $questionContent['correct_options'] ?? [];
        // Handle case where answerPayload might not have 'selected_options' key if raw submission
        // Assuming payload structure is consistent with what frontend sends.
        // Usually frontend sends { "selected_options": ["id1", "id2"] } or just ["id1"]?
        // Let's assume standard object structure.
        $selectedOptions = $answerPayload['selected_options'] ?? [];

        if (!is_array($correctOptions)) {
             return new GradingResult(0.0, false, "Invalid question configuration.");
        }

        // Ensure selectedOptions is array (handle single value submission if any)
        if (!is_array($selectedOptions)) {
            $selectedOptions = [$selectedOptions];
        }

        $correctCount = count($correctOptions);
        $selectedCount = count($selectedOptions);

        if ($correctCount === 0) {
            return new GradingResult(0.0, false, "Invalid question configuration: no correct options defined.");
        }

        // Calculate matches
        $matches = array_intersect($selectedOptions, $correctOptions);
        $matchCount = count($matches);
        $wrongCount = $selectedCount - $matchCount;

        // Exact match
        $isExactMatch = ($matchCount === $correctCount) && ($wrongCount === 0);

        if ($isExactMatch) {
            return new GradingResult($maxMarks, true);
        }

        // Partial scoring
        // Only applies if configured AND there are multiple correct options
        $partialScoring = $config['partial_scoring'] ?? false;

        if ($partialScoring && $correctCount > 1) {
            // Formula: (Matches - Wrongs) / TotalCorrect * MaxMarks
            // This penalizes guessing all options.
            $netCorrect = $matchCount - $wrongCount;
            if ($netCorrect < 0) {
                $netCorrect = 0;
            }

            $score = ($netCorrect / $correctCount) * $maxMarks;
            $score = round($score, 2);

            return new GradingResult($score, false, "Partial score: $matchCount correct, $wrongCount wrong.");
        }

        return new GradingResult(0.0, false);
    }
}
