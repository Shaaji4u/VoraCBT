<?php

declare(strict_types=1);

namespace App\Domain\Exam\Service;

use App\Core\Service\BaseService;
use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use DateTime;

class ExamSectionService extends BaseService
{
    private Connection $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getConnection();
    }

    public function addSection(string $examTemplateId, array $data): string
    {
        $id = Uuid::uuid4()->toString();
        $this->db->insert('exam_sections', [
            'id' => $id,
            'exam_template_id' => $examTemplateId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'section_order' => $data['section_order'] ?? 0,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'weight' => $data['weight'] ?? 1.0,
            'question_selection_rules' => isset($data['question_selection_rules'])
                ? json_encode($data['question_selection_rules'], JSON_THROW_ON_ERROR)
                : null,
        ]);

        return $id;
    }

    public function updateSection(string $sectionId, array $data): void
    {
        $updateData = [];
        if (isset($data['title'])) $updateData['title'] = $data['title'];
        if (isset($data['description'])) $updateData['description'] = $data['description'];
        if (isset($data['section_order'])) $updateData['section_order'] = $data['section_order'];
        if (isset($data['duration_minutes'])) $updateData['duration_minutes'] = $data['duration_minutes'];
        if (isset($data['weight'])) $updateData['weight'] = $data['weight'];
        if (isset($data['question_selection_rules'])) {
            $updateData['question_selection_rules'] = json_encode($data['question_selection_rules'], JSON_THROW_ON_ERROR);
        }

        $this->db->update('exam_sections', $updateData, ['id' => $sectionId]);
    }
}
