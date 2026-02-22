<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Integration\Service\ResultPushService;
use App\Infrastructure\Queue\QueueInterface;
use App\Infrastructure\Queue\ResultPushJob;
use PHPUnit\Framework\TestCase;

class ResultPushServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['SYSTEM_MODE'] = 'connected';
    }

    public function testPushResultEnqueuesJob()
    {
        $mockQueue = $this->createMock(QueueInterface::class);
        $mockQueue->expects($this->once())
            ->method('push')
            ->with(
                $this->equalTo(ResultPushJob::class),
                $this->callback(function ($data) {
                    return $data['exam_session_id'] === 'session_123'
                        && isset($data['idempotency_key'])
                        && $data['attempt'] === 1;
                })
            );

        $service = new ResultPushService($mockQueue);
        $service->pushResult('session_123', ['score' => 100]);
    }

    public function testPushResultDoesNothingInStandalone()
    {
        $_ENV['SYSTEM_MODE'] = 'standalone';
        $mockQueue = $this->createMock(QueueInterface::class);
        $mockQueue->expects($this->never())->method('push');

        $service = new ResultPushService($mockQueue);
        $service->pushResult('session_123', []);
    }
}
