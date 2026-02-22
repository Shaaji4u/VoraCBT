<?php

declare(strict_types=1);

namespace App\Question\Type;

use App\Question\Exception\QuestionValidationException;

class EssayType extends AbstractQuestionType
{
    public function getTypeName(): string
    {
        return 'essay';
    }

    public function validate(array $payload): void
    {
        $this->validateRequiredKeys($payload, ['text']);

        if (isset($payload['min_words']) && isset($payload['max_words'])) {
            if ($payload['min_words'] > $payload['max_words']) {
                throw new QuestionValidationException("Field 'min_words' cannot be greater than 'max_words'.");
            }
        }
    }
}
