<?php

declare(strict_types=1);

namespace App\Domain\Exam\Service;

use App\Core\Service\BaseService;
use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use DateTime;

class ExamTemplateService extends BaseService
{
    private Connection $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getConnection();
    }

    public function createTemplate(array $data): string
    {
        $id = Uuid::uuid4()->toString();
        $now = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->insert('exam_templates', [
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'start_window' => $data['start_window'] ?? null,
            'end_window' => $data['end_window'] ?? null,
            'passing_score' => $data['passing_score'] ?? null,
            'total_score' => $data['total_score'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'class_id' => $data['class_id'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'term' => $data['term'] ?? null,
            'academic_session' => $data['academic_session'] ?? null,
            'publish_strategy' => $data['publish_strategy'] ?? 'manual',
            'proctoring_enabled' => $data['proctoring_enabled'] ?? false,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    public function updateTemplate(string $id, array $data): void
    {
        $updateData = array_merge($data, [
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        // Remove ID if present in data to prevent PK update error (though DBAL might handle it)
        unset($updateData['id']);

        $this->db->update('exam_templates', $updateData, ['id' => $id]);
    }

    public function archiveTemplate(string $id): void
    {
        $this->db->update('exam_templates', [
            'archived_at' => (new DateTime())->format('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }
}
