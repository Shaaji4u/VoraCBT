<?php

declare(strict_types=1);

namespace App\Domain\Grading\Service\GradingStrategy;

use App\Domain\Grading\DTO\GradingResult;

class MatchingGradingStrategy implements GradingStrategyInterface
{
    public function canGrade(string $questionType): bool
    {
        return $questionType === 'matching';
    }

    public function grade(array $questionContent, array $answerPayload, float $maxMarks, array $config = []): GradingResult
    {
        $correctPairs = $questionContent['pairs'] ?? [];
        $userPairs = $answerPayload['pairs'] ?? [];

        if (empty($correctPairs)) {
            return new GradingResult(0.0, false, "Invalid question configuration: no pairs defined.");
        }

        // Convert correct pairs to map for easier lookup: left -> right
        $correctMap = [];
        foreach ($correctPairs as $pair) {
            $left = isset($pair['left']) ? trim((string)$pair['left']) : '';
            $right = isset($pair['right']) ? trim((string)$pair['right']) : '';
            if ($left !== '') {
                $correctMap[$left] = $right;
            }
        }

        if (empty($correctMap)) {
            return new GradingResult(0.0, false, "Invalid pairs configuration.");
        }

        $totalPairs = count($correctMap);
        $correctMatches = 0;
        $gradedLefts = [];

        // Iterate user pairs
        foreach ($userPairs as $pair) {
            $uLeft = isset($pair['left']) ? trim((string)$pair['left']) : '';
            $uRight = isset($pair['right']) ? trim((string)$pair['right']) : '';

            // Skip if left is empty or already graded (prevent duplicate scoring for same item)
            if ($uLeft === '' || isset($gradedLefts[$uLeft])) {
                continue;
            }
            $gradedLefts[$uLeft] = true;

            if (isset($correctMap[$uLeft])) {
                // Exact string match for right side
                // Maybe case insensitive? Prompt implies "Pair validation".
                // I'll assume exact match for IDs/Keys.
                if ($correctMap[$uLeft] === $uRight) {
                    $correctMatches++;
                }
            }
        }

        $score = ($correctMatches / $totalPairs) * $maxMarks;
        $score = round($score, 2);

        $isFullyCorrect = ($correctMatches === $totalPairs);

        $feedback = $isFullyCorrect ? null : "Matched $correctMatches out of $totalPairs correctly.";

        return new GradingResult($score, $isFullyCorrect, $feedback);
    }
}
