<?php

declare(strict_types=1);

namespace App\Question\Type;

use App\Question\Exception\QuestionValidationException;

class PassageType extends AbstractQuestionType
{
    public function getTypeName(): string
    {
        return 'passage';
    }

    public function validate(array $payload): void
    {
        $this->validateRequiredKeys($payload, ['passage', 'questions']);

        if (!is_array($payload['questions'])) {
            throw new QuestionValidationException("Field 'questions' must be an array.");
        }

        foreach ($payload['questions'] as $index => $question) {
            if (!isset($question['type']) || !isset($question['content'])) {
                throw new QuestionValidationException("Question at index $index inside passage must have 'type' and 'content'.");
            }

            // Recursive validation
            try {
                $handler = QuestionTypeFactory::create($question['type']);
                $handler->validate($question['content']);
            } catch (\Exception $e) {
                throw new QuestionValidationException("Validation failed for question at index $index: " . $e->getMessage(), 400, $e);
            }
        }
    }
}
