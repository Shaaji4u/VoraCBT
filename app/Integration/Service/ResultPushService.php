<?php

declare(strict_types=1);

namespace App\Integration\Service;

use App\Infrastructure\Queue\QueueInterface;
use App\Core\Config\Environment;
use App\Infrastructure\Queue\ResultPushJob;
use Ramsey\Uuid\Uuid;

class ResultPushService
{
    private QueueInterface $queue;
    private Environment $env;

    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
        $this->env = Environment::getInstance();
    }

    public function pushResult(string $examSessionId, array $resultData): void
    {
        if (!$this->env->isConnectedMode()) {
            return;
        }

        $idempotencyKey = Uuid::uuid4()->toString();

        $jobData = [
            'exam_session_id' => $examSessionId,
            'result_data' => $resultData,
            'idempotency_key' => $idempotencyKey,
            'attempt' => 1,
        ];

        // Push to queue
        $this->queue->push(ResultPushJob::class, $jobData);
    }
}
