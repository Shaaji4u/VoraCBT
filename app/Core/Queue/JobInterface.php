<?php

declare(strict_types=1);

namespace App\Core\Queue;

interface JobInterface
{
    public function handle(): void;
}
