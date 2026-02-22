<?php

declare(strict_types=1);

namespace App\Domain\Grading\Job;

use App\Core\Container\Container;
use App\Domain\Grading\Service\AutoGradingService;
use App\Infrastructure\Queue\JobInterface;

class GradingJob implements JobInterface
{
    private string $sessionId;

    public function __construct(array $payload)
    {
        if (!isset($payload['session_id'])) {
            throw new \InvalidArgumentException("GradingJob requires 'session_id' in payload.");
        }
        $this->sessionId = $payload['session_id'];
    }

    public function handle(): void
    {
        /** @var AutoGradingService $service */
        $service = Container::getInstance()->get(AutoGradingService::class);
        $service->gradeSession($this->sessionId);
    }
}
