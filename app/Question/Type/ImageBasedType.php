<?php

declare(strict_types=1);

namespace App\Question\Type;

use App\Question\Exception\QuestionValidationException;

class ImageBasedType extends AbstractQuestionType
{
    public function getTypeName(): string
    {
        return 'image_based';
    }

    public function validate(array $payload): void
    {
        $this->validateRequiredKeys($payload, ['image_url', 'prompt']);

        // Optional answer key if it's auto-graded
        // if (!isset($payload['answer'])) ...
    }
}
