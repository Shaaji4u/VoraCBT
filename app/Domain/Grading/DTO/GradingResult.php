<?php

declare(strict_types=1);

namespace App\Domain\Grading\DTO;

class GradingResult
{
    public function __construct(
        public float $marksObtained,
        public bool $isCorrect,
        public ?string $feedback = null
    ) {}
}
