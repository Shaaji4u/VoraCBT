<?php

declare(strict_types=1);

namespace Tests\Domain\Monitoring;

use App\Domain\Monitoring\Service\AdminLogViewerService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class AdminLogViewerServiceTest extends TestCase
{
    public function testListLogsUsesFiltersAndLimitCap(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with(
                $this->callback(function (string $sql): bool {
                    return str_contains($sql, 'LIMIT 500')
                        && str_contains($sql, 'event_source')
                        && str_contains($sql, 'exam_template_id');
                }),
                $this->equalTo([
                    'from' => '2026-01-01 00:00:00',
                    'to' => null,
                    'user_id' => 'user-1',
                    'exam_template_id' => 'exam-1',
                    'event_source' => 'proctoring',
                ])
            )
            ->willReturn([]);

        $service = new AdminLogViewerService($connection);
        $service->listLogs([
            'from' => '2026-01-01 00:00:00',
            'user_id' => 'user-1',
            'exam_template_id' => 'exam-1',
            'event_source' => 'proctoring',
            'limit' => 999,
        ]);
    }
}
