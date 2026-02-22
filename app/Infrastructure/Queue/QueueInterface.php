<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue;

interface QueueInterface
{
    public function push(string $jobClass, array $data = []): string;
    public function pop(): ?JobInterface;
}
