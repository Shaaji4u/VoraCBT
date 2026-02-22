<?php

declare(strict_types=1);

namespace App\Question\Type;

use App\Question\Exception\QuestionValidationException;

interface QuestionTypeInterface
{
    /**
     * Get the unique name of the question type.
     */
    public function getTypeName(): string;

    /**
     * Validate the question payload.
     *
     * @param array $payload
     * @throws QuestionValidationException
     */
    public function validate(array $payload): void;

    /**
     * Normalize the payload structure (e.g., set defaults).
     *
     * @param array $payload
     * @return array
     */
    public function normalize(array $payload): array;
}
