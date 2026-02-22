<?php

declare(strict_types=1);

namespace App\Question\Type;

use App\Question\Exception\QuestionValidationException;

class FillInTheBlankType extends AbstractQuestionType
{
    public function getTypeName(): string
    {
        return 'fill_in_the_blank';
    }

    public function validate(array $payload): void
    {
        $this->validateRequiredKeys($payload, ['text', 'blanks']);

        if (!is_array($payload['blanks'])) {
            throw new QuestionValidationException("Field 'blanks' must be an array.");
        }

        if (empty($payload['blanks'])) {
            throw new QuestionValidationException("Field 'blanks' cannot be empty.");
        }

        // Validate that placeholders in text match blanks
        preg_match_all('/\[\[(.*?)\]\]/', $payload['text'], $matches);
        $placeholders = $matches[1];

        if (empty($placeholders)) {
            throw new QuestionValidationException("No blanks (e.g., [[id]]) found in 'text'.");
        }

        $blankKeys = array_keys($payload['blanks']);
        foreach ($placeholders as $placeholder) {
            if (!in_array($placeholder, $blankKeys)) {
                throw new QuestionValidationException("Blank '$placeholder' found in text but not defined in 'blanks'.");
            }
        }
    }
}
