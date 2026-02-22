<?php

declare(strict_types=1);

namespace App\Question\Service;

use App\Core\Service\BaseService;

class QuestionService extends BaseService
{
    public function getQuestion(int $id): array
    {
        // Mock implementation
        return ['id' => $id, 'text' => 'Sample Question'];
    }

    public function createQuestion(array $data): int
    {
        // Mock creation logic
        return 123;
    }
}
