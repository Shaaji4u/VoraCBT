<?php

declare(strict_types=1);

namespace App\Domain\Grading\Service\GradingStrategy;

use App\Domain\Grading\DTO\GradingResult;

class FillInTheBlankGradingStrategy implements GradingStrategyInterface
{
    public function canGrade(string $questionType): bool
    {
        return $questionType === 'fill_in_the_blank';
    }

    public function grade(array $questionContent, array $answerPayload, float $maxMarks, array $config = []): GradingResult
    {
        $blanks = $questionContent['blanks'] ?? [];
        // Map blank_id -> user_answer
        $answers = $answerPayload['answers'] ?? [];

        if (empty($blanks)) {
            return new GradingResult(0.0, false, "Invalid question configuration: no blanks defined.");
        }

        $totalBlanks = count($blanks);
        $correctBlanks = 0;
        $feedbackParts = [];

        foreach ($blanks as $blankId => $blankConfig) {
            $userAnswer = isset($answers[$blankId]) ? trim((string)$answers[$blankId]) : '';
            $isCorrect = false;

            // Determine expected answer and matching strategy
            // Assume blankConfig can be string (exact) or array (complex)
            if (is_string($blankConfig)) {
                // Simple exact match (case-insensitive default)
                if (strcasecmp($userAnswer, trim($blankConfig)) === 0) {
                    $isCorrect = true;
                }
            } elseif (is_array($blankConfig)) {
                $expected = isset($blankConfig['answer']) ? trim((string)$blankConfig['answer']) : '';
                $regex = $blankConfig['regex'] ?? null;
                $caseSensitive = $blankConfig['case_sensitive'] ?? false;

                if ($regex) {
                    // Regex match
                    // Use @ to suppress errors if regex is invalid (though creation should validate it)
                    if (@preg_match($regex, $userAnswer)) {
                        $isCorrect = true;
                    }
                } else {
                    // String match
                    if ($caseSensitive) {
                        if (strcmp($userAnswer, $expected) === 0) {
                            $isCorrect = true;
                        }
                    } else {
                        if (strcasecmp($userAnswer, $expected) === 0) {
                            $isCorrect = true;
                        }
                    }
                }
            }

            if ($isCorrect) {
                $correctBlanks++;
            } else {
                // Don't expose correct answers in feedback
                $feedbackParts[] = "Blank '$blankId': Incorrect.";
            }
        }

        if ($totalBlanks === 0) {
            return new GradingResult(0.0, false);
        }

        $score = ($correctBlanks / $totalBlanks) * $maxMarks;
        $score = round($score, 2);

        $isFullyCorrect = ($correctBlanks === $totalBlanks);
        $feedback = $isFullyCorrect ? null : implode(" ", $feedbackParts);

        return new GradingResult($score, $isFullyCorrect, $feedback);
    }
}
