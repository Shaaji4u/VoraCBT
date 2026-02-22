<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue;

interface JobInterface
{
    public function handle(): void;
}
