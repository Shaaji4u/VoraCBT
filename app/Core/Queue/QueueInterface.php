<?php

declare(strict_types=1);

namespace App\Core\Queue;

interface QueueInterface
{
    public function push(string $jobClass, array $data = []): string; // Returns Job ID

    public function pop(): ?JobInterface;
}
