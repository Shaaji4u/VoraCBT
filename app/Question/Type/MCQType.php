<?php

declare(strict_types=1);

namespace App\Question\Type;

use App\Question\Exception\QuestionValidationException;

class MCQType extends AbstractQuestionType
{
    public function getTypeName(): string
    {
        return 'mcq';
    }

    public function validate(array $payload): void
    {
        $this->validateRequiredKeys($payload, ['prompt', 'options', 'correct_options']);

        if (!is_array($payload['options'])) {
            throw new QuestionValidationException("Field 'options' must be an array.");
        }

        if (empty($payload['options'])) {
            throw new QuestionValidationException("Field 'options' cannot be empty.");
        }

        if (!is_array($payload['correct_options'])) {
            throw new QuestionValidationException("Field 'correct_options' must be an array.");
        }

        if (empty($payload['correct_options'])) {
            throw new QuestionValidationException("Field 'correct_options' cannot be empty.");
        }

        // Validate that each option has an ID
        foreach ($payload['options'] as $index => $option) {
            if (!isset($option['id'])) {
                throw new QuestionValidationException("Option at index $index missing 'id' field.");
            }
        }

        // Validate that correct options exist in options
        $optionIds = array_column($payload['options'], 'id');
        foreach ($payload['correct_options'] as $correctId) {
            if (!in_array($correctId, $optionIds)) {
                throw new QuestionValidationException("Correct option '$correctId' not found in options.");
            }
        }
    }

    public function normalize(array $payload): array
    {
        $payload['randomize_options'] = $payload['randomize_options'] ?? false;
        $payload['allow_multiple_selection'] = $payload['allow_multiple_selection'] ?? false;

        return $payload;
    }
}
