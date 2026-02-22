<?php

declare(strict_types=1);

namespace App\Question\Type;

use App\Question\Exception\QuestionValidationException;

abstract class AbstractQuestionType implements QuestionTypeInterface
{
    /**
     * Helper to validate required keys in payload.
     *
     * @param array $payload
     * @param array $requiredKeys
     * @throws QuestionValidationException
     */
    protected function validateRequiredKeys(array $payload, array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $payload)) {
                throw new QuestionValidationException(sprintf("Missing required field '%s' for type '%s'", $key, $this->getTypeName()));
            }
        }
    }

    /**
     * Default normalization implementation (no-op).
     */
    public function normalize(array $payload): array
    {
        return $payload;
    }
}
