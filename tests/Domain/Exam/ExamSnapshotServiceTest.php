<?php

declare(strict_types=1);

namespace Tests\Domain\Exam;

use App\Domain\Exam\Service\ExamSnapshotService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class ExamSnapshotServiceTest extends TestCase
{
    public function testCreateSnapshotWritesFileAndAuditRecord(): void
    {
        $tmpDir = sys_get_temp_dir() . '/voracbt_snapshot_test_' . uniqid('', true);

        $db = $this->createMock(Connection::class);
        $db->method('fetchAssociative')->willReturn(['id' => 'exam-1', 'title' => 'Exam']);
        $db->method('fetchAllAssociative')->willReturnOnConsecutiveCalls(
            [['id' => 'sec-1', 'exam_template_id' => 'exam-1', 'section_order' => 1]],
            [['id' => 'eq-1', 'question_id' => 'q-1', 'type' => 'mcq']]
        );
        $db->expects($this->once())->method('insert')->with(
            'exam_snapshot_backups',
            $this->callback(fn(array $row): bool => ($row['exam_template_id'] ?? null) === 'exam-1' && str_starts_with((string)($row['snapshot_path'] ?? ''), 'storage/app/exam_snapshots/'))
        );

        $service = new ExamSnapshotService($db, $tmpDir);
        $path = $service->createSnapshot('exam-1', 'admin-1');

        $this->assertStringStartsWith('storage/app/exam_snapshots/', $path);
        $this->assertNotFalse(glob($tmpDir . '/*.json'));

        array_map('unlink', glob($tmpDir . '/*.json') ?: []);
        @rmdir($tmpDir);
    }
}
