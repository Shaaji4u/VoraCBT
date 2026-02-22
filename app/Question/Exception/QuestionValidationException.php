<?php

declare(strict_types=1);

namespace App\Question\Exception;

class QuestionValidationException extends QuestionException
{
    public function __construct(string $message, int $statusCode = 400, ?\Exception $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}
