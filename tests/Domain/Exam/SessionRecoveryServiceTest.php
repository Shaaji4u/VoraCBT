<?php

declare(strict_types=1);

namespace Tests\Domain\Exam;

use App\Domain\Exam\Service\SessionRecoveryService;
use App\Domain\Exam\Service\TimerService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class SessionRecoveryServiceTest extends TestCase
{
    public function testAutosaveUpsertsCheckpoint(): void
    {
        $db = $this->createMock(Connection::class);
        $timer = $this->createMock(TimerService::class);

        $db->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls(
                ['status' => 'in_progress'],
                ['id' => 's1', 'status' => 'in_progress', 'start_time' => '2026-01-01 00:00:00']
            );
        $db->method('fetchOne')->willReturn('existing-id');
        $timer->method('getRemainingTime')->willReturn(1200);

        $db->expects($this->once())->method('update')->with(
            'exam_session_checkpoints',
            $this->callback(fn(array $row): bool => isset($row['checksum']) && isset($row['draft_payload'])),
            ['id' => 'existing-id']
        );

        $service = new SessionRecoveryService($db, $timer);
        $result = $service->autosave('s1', null, ['a' => 1], '2026-01-01 00:00:10');

        $this->assertSame('s1', $result['session_id']);
        $this->assertSame(1200, $result['remaining_seconds']);
    }
}
