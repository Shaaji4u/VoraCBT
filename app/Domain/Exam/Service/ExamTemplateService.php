<?php

declare(strict_types=1);

namespace App\Domain\Exam\Service;

use App\Core\Database\DatabaseManager;
use App\Core\Service\BaseService;
use DateTime;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class ExamTemplateService extends BaseService
{
    private const ALLOWED_STATES = [
        'draft',
        'scheduled',
        'open',
        'closed',
        'under_review',
        'finalized',
        'archived',
    ];

    private const STATE_TRANSITIONS = [
        'draft' => ['scheduled', 'archived'],
        'scheduled' => ['open', 'archived'],
        'open' => ['closed'],
        'closed' => ['under_review', 'archived'],
        'under_review' => ['finalized', 'archived'],
        'finalized' => ['archived'],
        'archived' => [],
    ];

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
            'lifecycle_state' => 'draft',
            'state_changed_at' => $now,
            'state_changed_by' => $data['created_by'] ?? null,
        ]);

        return $id;
    }

    public function updateTemplate(string $id, array $data): void
    {
        $template = $this->db->fetchAssociative('SELECT lifecycle_state FROM exam_templates WHERE id = ?', [$id]);
        if (!$template) {
            throw new RuntimeException("Exam template $id not found.");
        }

        if (in_array($template['lifecycle_state'], ['open', 'closed', 'under_review', 'finalized', 'archived'], true)) {
            throw new RuntimeException(sprintf(
                'Exam template %s is in %s state and cannot be edited.',
                $id,
                $template['lifecycle_state']
            ));
        }

        $updateData = array_merge($data, [
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        unset($updateData['id']);

        $this->db->update('exam_templates', $updateData, ['id' => $id]);
    }

    public function archiveTemplate(string $id): void
    {
        $this->transitionLifecycle($id, 'archived', null, 'Template archived');
    }

    public function transitionLifecycle(string $id, string $toState, ?string $changedBy = null, ?string $reason = null): void
    {
        if (!in_array($toState, self::ALLOWED_STATES, true)) {
            throw new RuntimeException("Unsupported lifecycle state: $toState");
        }

        $template = $this->db->fetchAssociative('SELECT lifecycle_state FROM exam_templates WHERE id = ?', [$id]);
        if (!$template) {
            throw new RuntimeException("Exam template $id not found.");
        }

        $fromState = $template['lifecycle_state'] ?? 'draft';
        if ($fromState === $toState) {
            return;
        }

        $allowedTargets = self::STATE_TRANSITIONS[$fromState] ?? [];
        if (!in_array($toState, $allowedTargets, true)) {
            throw new RuntimeException("Invalid lifecycle transition from $fromState to $toState");
        }

        $now = (new DateTime())->format('Y-m-d H:i:s');

        $updateData = [
            'lifecycle_state' => $toState,
            'state_changed_at' => $now,
            'state_changed_by' => $changedBy,
            'updated_at' => $now,
        ];

        if ($toState === 'archived') {
            $updateData['archived_at'] = $now;
        }

        $this->db->update('exam_templates', $updateData, ['id' => $id]);

        $this->db->insert('exam_lifecycle_logs', [
            'id' => Uuid::uuid4()->toString(),
            'exam_template_id' => $id,
            'old_state' => $fromState,
            'new_state' => $toState,
            'changed_by' => $changedBy,
            'reason' => $reason,
            'created_at' => $now,
        ]);
    }
}
