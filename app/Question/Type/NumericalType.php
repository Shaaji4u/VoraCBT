<?php

declare(strict_types=1);

namespace App\Question\Type;

use App\Question\Exception\QuestionValidationException;

class NumericalType extends AbstractQuestionType
{
    public function getTypeName(): string
    {
        return 'numerical';
    }

    public function validate(array $payload): void
    {
        $this->validateRequiredKeys($payload, ['text', 'answer']);

        if (!is_numeric($payload['answer'])) {
            throw new QuestionValidationException("Field 'answer' must be a number.");
        }

        if (isset($payload['tolerance']) && !is_numeric($payload['tolerance'])) {
            throw new QuestionValidationException("Field 'tolerance' must be a number.");
        }
    }
}
