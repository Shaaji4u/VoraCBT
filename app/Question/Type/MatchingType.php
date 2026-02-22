<?php

declare(strict_types=1);

namespace App\Question\Type;

use App\Question\Exception\QuestionValidationException;

class MatchingType extends AbstractQuestionType
{
    public function getTypeName(): string
    {
        return 'matching';
    }

    public function validate(array $payload): void
    {
        $this->validateRequiredKeys($payload, ['text', 'pairs']);

        if (!is_array($payload['pairs'])) {
            throw new QuestionValidationException("Field 'pairs' must be an array.");
        }

        if (empty($payload['pairs'])) {
            throw new QuestionValidationException("Field 'pairs' cannot be empty.");
        }

        foreach ($payload['pairs'] as $index => $pair) {
            if (!isset($pair['left']) || !isset($pair['right'])) {
                throw new QuestionValidationException("Pair at index $index must have 'left' and 'right' keys.");
            }
        }
    }
}
