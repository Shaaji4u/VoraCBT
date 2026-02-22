<?php

declare(strict_types=1);

namespace App\Domain\Grading\Job;

use App\Core\Container\Container;
use App\Domain\Grading\Service\ScoreAggregationService;
use App\Infrastructure\Queue\JobInterface;
use InvalidArgumentException;

class ScoreAggregationJob implements JobInterface
{
    private string $sessionId;

    public function __construct(array $payload)
    {
        if (!isset($payload['session_id'])) {
            throw new InvalidArgumentException("ScoreAggregationJob requires 'session_id' in payload.");
        }
        $this->sessionId = $payload['session_id'];
    }

    public function handle(): void
    {
        /** @var ScoreAggregationService $service */
        $service = Container::getInstance()->get(ScoreAggregationService::class);
        $service->aggregateSession($this->sessionId);
    }
}
