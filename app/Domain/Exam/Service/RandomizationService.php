<?php

declare(strict_types=1);

namespace App\Domain\Exam\Service;

use App\Core\Service\BaseService;
use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use DateTime;
use Random\Randomizer;
use Random\Engine\Mt19937;

class RandomizationService extends BaseService
{
    private Connection $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getConnection();
    }

    public function generateQuestions(string $examTemplateId, string $examSessionId, int $seed): void
    {
        // Initialize deterministic randomizer
        // Mt19937 seeded is deterministic in PHP 8.2+
        $engine = new Mt19937($seed);
        $randomizer = new Randomizer($engine);

        // Fetch sections
        $sections = $this->db->fetchAllAssociative(
            'SELECT * FROM exam_sections WHERE exam_template_id = ? ORDER BY section_order ASC',
            [$examTemplateId]
        );

        $selectedIds = [];

        foreach ($sections as $section) {
            $rules = isset($section['question_selection_rules'])
                ? json_decode($section['question_selection_rules'], true, 512, JSON_THROW_ON_ERROR)
                : [];

            if (empty($rules)) {
                continue;
            }

            $questionsForSection = [];

            foreach ($rules as $rule) {
                $count = (int)($rule['count'] ?? 0);
                if ($count <= 0) {
                    continue;
                }

                $qb = $this->db->createQueryBuilder();
                $qb->select('*')
                   ->from('questions', 'q')
                   ->where('q.archived_at IS NULL')
                   ->orderBy('q.id', 'ASC');

                if (!empty($rule['type'])) {
                    $qb->andWhere('q.type = :type')
                       ->setParameter('type', $rule['type']);
                }

                if (!empty($selectedIds)) {
                     $qb->andWhere('q.id NOT IN (:selectedIds)')
                        ->setParameter('selectedIds', $selectedIds, Connection::PARAM_STR_ARRAY);
                }

                // Metadata filtering using LIKE for basic JSON match
                if (!empty($rule['difficulty'])) {
                    // Try exact match in JSON first or relaxed LIKE
                    // Assuming metadata like {"difficulty":"easy",...}
                    // LIKE '%"difficulty":"easy"%'
                    $qb->andWhere('q.metadata LIKE :difficulty')
                       ->setParameter('difficulty', '%"difficulty":"' . $rule['difficulty'] . '"%');
                }

                if (!empty($rule['tags'])) {
                    foreach ($rule['tags'] as $idx => $tag) {
                        $paramName = "tag_{$idx}";
                        $qb->andWhere('q.metadata LIKE :' . $paramName)
                           ->setParameter($paramName, '%"' . $tag . '"%');
                    }
                }

                // Fetch candidates
                $candidates = $qb->fetchAllAssociative();

                // Deterministic Shuffle
                // shuffleArray returns a shuffled copy
                $shuffledCandidates = $randomizer->shuffleArray($candidates);

                // Pick top N
                $selected = array_slice($shuffledCandidates, 0, $count);

                foreach ($selected as $q) {
                    $questionsForSection[] = $q;
                    $selectedIds[] = $q['id'];
                }
            }

            // Randomize order within the section
            $shuffledSection = $randomizer->shuffleArray($questionsForSection);

            // Insert into exam_session_questions
            $order = 1;
            foreach ($shuffledSection as $q) {
                // Determine options order if applicable (e.g. MCQ)
                $content = json_decode($q['content'], true);
                $optionsOrder = null;
                if (isset($content['options']) && is_array($content['options'])) {
                    $indices = array_keys($content['options']);
                    $indices = $randomizer->shuffleArray($indices);
                    $optionsOrder = $indices;
                }

                $this->db->insert('exam_session_questions', [
                    'id' => Uuid::uuid4()->toString(),
                    'exam_session_id' => $examSessionId,
                    'question_id' => $q['id'],
                    'exam_section_id' => $section['id'],
                    'question_order' => $order++,
                    'options_order' => $optionsOrder ? json_encode($optionsOrder) : null,
                    'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
                    'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
                    'status' => 'unseen',
                    'is_flagged' => 0
                ]);
            }
        }
    }
}
