<?php

declare(strict_types=1);

namespace App\Domain\Exam\Service;

use DateTime;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class ExamSnapshotService
{
    public function __construct(
        private Connection $db,
        private string $snapshotRoot = __DIR__ . '/../../../../storage/app/exam_snapshots'
    ) {}

    public function createSnapshot(string $examTemplateId, ?string $createdBy = null): string
    {
        $template = $this->db->fetchAssociative('SELECT * FROM exam_templates WHERE id = ?', [$examTemplateId]);
        if (!$template) {
            throw new RuntimeException("Exam template $examTemplateId not found.");
        }

        $sections = $this->db->fetchAllAssociative(
            'SELECT * FROM exam_sections WHERE exam_template_id = ? ORDER BY section_order ASC',
            [$examTemplateId]
        );

        $questions = $this->db->fetchAllAssociative(
            'SELECT eq.*, q.type, q.content, q.metadata FROM exam_questions eq INNER JOIN exam_sections es ON es.id = eq.exam_section_id INNER JOIN questions q ON q.id = eq.question_id WHERE es.exam_template_id = ? ORDER BY eq.question_order ASC',
            [$examTemplateId]
        );

        $payload = [
            'schema_version' => 1,
            'generated_at' => (new DateTime())->format(DATE_ATOM),
            'exam_template' => $template,
            'sections' => $sections,
            'questions' => $questions,
        ];

        if (!is_dir($this->snapshotRoot) && !mkdir($concurrentDirectory = $this->snapshotRoot, 0775, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException('Unable to create exam snapshot directory.');
        }

        $filename = sprintf('%s_%s.json', $examTemplateId, date('Ymd_His'));
        $path = rtrim($this->snapshotRoot, '/') . '/' . $filename;
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false || file_put_contents($path, $json) === false) {
            throw new RuntimeException('Unable to write exam snapshot file.');
        }

        $hash = hash('sha256', $json);
        $relativePath = 'storage/app/exam_snapshots/' . $filename;

        $this->db->insert('exam_snapshot_backups', [
            'id' => Uuid::uuid4()->toString(),
            'exam_template_id' => $examTemplateId,
            'snapshot_path' => $relativePath,
            'snapshot_hash' => $hash,
            'created_by' => $createdBy,
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        return $relativePath;
    }
}
