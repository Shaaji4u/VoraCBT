<?php

declare(strict_types=1);

namespace App\Question\Service;

use App\Core\Service\BaseService;
use App\Core\Database\DatabaseManager;
use App\Question\Type\QuestionTypeFactory;
use App\Question\Exception\QuestionException;
use Ramsey\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use Exception;
use JsonException;

class QuestionService extends BaseService
{
    private function getConnection(): Connection
    {
        return DatabaseManager::getConnection();
    }

    /**
     * Create a new question.
     *
     * @param array $data Expected keys: type, content, metadata (optional), created_by (optional)
     * @return string The UUID of the created question
     * @throws QuestionException
     */
    public function createQuestion(array $data): string
    {
        $conn = $this->getConnection();

        $conn->beginTransaction();
        try {
            $id = $this->createQuestionInternal($conn, $data);
            $conn->commit();
            return $id;
        } catch (Exception $e) {
            $conn->rollBack();
            // If it's already a QuestionException, rethrow it
            if ($e instanceof QuestionException) {
                throw $e;
            }
            throw new QuestionException("Failed to create question: " . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Internal create question logic without transaction management.
     *
     * @param Connection $conn
     * @param array $data
     * @return string
     * @throws QuestionException
     */
    private function createQuestionInternal(Connection $conn, array $data): string
    {
        if (empty($data['type'])) {
            throw new QuestionException("Field 'type' is required.");
        }

        if (empty($data['content'])) {
            throw new QuestionException("Field 'content' is required.");
        }

        $typeHandler = QuestionTypeFactory::create($data['type']);

        // Validate and Normalize content
        $typeHandler->validate($data['content']);
        $normalizedContent = $typeHandler->normalize($data['content']);

        $id = Uuid::uuid4()->toString();
        $version = 1;
        $now = date('Y-m-d H:i:s');

        try {
            $conn->insert('questions', [
                'id' => $id,
                'type' => $data['type'],
                'content' => json_encode($normalizedContent, JSON_THROW_ON_ERROR),
                'metadata' => isset($data['metadata']) ? json_encode($data['metadata'], JSON_THROW_ON_ERROR) : null,
                'version' => $version,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => $data['created_by'] ?? null,
                'updated_by' => $data['created_by'] ?? null, // Initially same as creator
            ]);

            $conn->insert('question_versions', [
                'id' => Uuid::uuid4()->toString(),
                'question_id' => $id,
                'version' => $version,
                'type' => $data['type'],
                'content' => json_encode($normalizedContent, JSON_THROW_ON_ERROR),
                'metadata' => isset($data['metadata']) ? json_encode($data['metadata'], JSON_THROW_ON_ERROR) : null,
                'created_at' => $now,
                'created_by' => $data['created_by'] ?? null,
            ]);
        } catch (Exception $e) {
            throw new QuestionException("Database error: " . $e->getMessage(), 500, $e);
        }

        return $id;
    }

    /**
     * Update an existing question.
     *
     * @param string $id
     * @param array $data Expected keys: content (optional), metadata (optional), updated_by (optional)
     * @return void
     * @throws QuestionException
     */
    public function updateQuestion(string $id, array $data): void
    {
        $conn = $this->getConnection();

        // Fetch current question
        $question = $conn->fetchAssociative('SELECT * FROM questions WHERE id = ?', [$id]);

        if (!$question) {
            throw new QuestionException("Question not found with ID: $id", 404);
        }

        if ($question['archived_at'] !== null) {
            throw new QuestionException("Cannot update archived question.", 400);
        }

        $type = $question['type'];
        try {
            $currentContent = json_decode($question['content'], true, 512, JSON_THROW_ON_ERROR);
            $currentMetadata = $question['metadata'] ? json_decode($question['metadata'], true, 512, JSON_THROW_ON_ERROR) : [];
        } catch (JsonException $e) {
             throw new QuestionException("JSON error: " . $e->getMessage(), 500, $e);
        }

        $newContent = $data['content'] ?? $currentContent;

        $typeHandler = QuestionTypeFactory::create($type);
        if (isset($data['content'])) {
            $typeHandler->validate($newContent);
            $newContent = $typeHandler->normalize($newContent);
        }

        $newMetadata = isset($data['metadata']) ? $data['metadata'] : $currentMetadata;
        $newVersion = (int)$question['version'] + 1;
        $now = date('Y-m-d H:i:s');
        $updatedBy = $data['updated_by'] ?? null;

        $conn->beginTransaction();
        try {
            $encodedContent = json_encode($newContent, JSON_THROW_ON_ERROR);
            $encodedMetadata = json_encode($newMetadata, JSON_THROW_ON_ERROR);

            $conn->update('questions', [
                'content' => $encodedContent,
                'metadata' => $encodedMetadata,
                'version' => $newVersion,
                'updated_at' => $now,
                'updated_by' => $updatedBy,
            ], ['id' => $id]);

            $conn->insert('question_versions', [
                'id' => Uuid::uuid4()->toString(),
                'question_id' => $id,
                'version' => $newVersion,
                'type' => $type,
                'content' => $encodedContent,
                'metadata' => $encodedMetadata,
                'created_at' => $now,
                'created_by' => $updatedBy,
            ]);

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            throw new QuestionException("Failed to update question: " . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Get a question by ID.
     *
     * @param string $id
     * @return array
     * @throws QuestionException
     */
    public function getQuestion(string $id): array
    {
        $conn = $this->getConnection();
        $question = $conn->fetchAssociative('SELECT * FROM questions WHERE id = ?', [$id]);

        if (!$question) {
            throw new QuestionException("Question not found with ID: $id", 404);
        }

        try {
            $question['content'] = json_decode($question['content'], true, 512, JSON_THROW_ON_ERROR);
            $question['metadata'] = $question['metadata'] ? json_decode($question['metadata'], true, 512, JSON_THROW_ON_ERROR) : [];
        } catch (JsonException $e) {
            throw new QuestionException("JSON error: " . $e->getMessage(), 500, $e);
        }

        return $question;
    }

    /**
     * Get a specific version of a question.
     *
     * @param string $id
     * @param int $version
     * @return array
     * @throws QuestionException
     */
    public function getQuestionVersion(string $id, int $version): array
    {
        $conn = $this->getConnection();
        $questionVersion = $conn->fetchAssociative(
            'SELECT * FROM question_versions WHERE question_id = ? AND version = ?',
            [$id, $version]
        );

        if (!$questionVersion) {
            throw new QuestionException("Question version $version not found for ID: $id", 404);
        }

        try {
            $questionVersion['content'] = json_decode($questionVersion['content'], true, 512, JSON_THROW_ON_ERROR);
            $questionVersion['metadata'] = $questionVersion['metadata'] ? json_decode($questionVersion['metadata'], true, 512, JSON_THROW_ON_ERROR) : [];
        } catch (JsonException $e) {
             throw new QuestionException("JSON error: " . $e->getMessage(), 500, $e);
        }

        return $questionVersion;
    }

    /**
     * Soft delete a question.
     *
     * @param string $id
     * @return void
     * @throws QuestionException
     */
    public function deleteQuestion(string $id): void
    {
        $conn = $this->getConnection();

        $question = $conn->fetchAssociative('SELECT id FROM questions WHERE id = ?', [$id]);

        if (!$question) {
            throw new QuestionException("Question not found with ID: $id", 404);
        }

        try {
            $conn->update('questions', [
                'archived_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id]);
        } catch (Exception $e) {
            throw new QuestionException("Failed to delete question: " . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Bulk import questions.
     *
     * @param array $questions Array of question data
     * @return array Array of created IDs
     * @throws QuestionException
     */
    public function bulkImport(array $questions): array
    {
        $createdIds = [];
        $conn = $this->getConnection();

        $conn->beginTransaction();
        try {
            foreach ($questions as $index => $data) {
                // Call createQuestionInternal which doesn't commit, so we can commit all at once.
                $id = $this->createQuestionInternal($conn, $data);
                $createdIds[] = $id;
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            // If it's already a QuestionException, rethrow it
            if ($e instanceof QuestionException) {
                throw $e;
            }
            throw new QuestionException("Bulk import failed at index " . (isset($index) ? $index : '?') . ": " . $e->getMessage(), 500, $e);
        }

        return $createdIds;
    }

    /**
     * Bulk export questions.
     *
     * @param array $filter Optional filters (type, from_date, to_date)
     * @return array
     */
    public function bulkExport(array $filter = []): array
    {
        $conn = $this->getConnection();
        $qb = $conn->createQueryBuilder();
        $qb->select('*')->from('questions')->where('archived_at IS NULL');

        if (isset($filter['type'])) {
            $qb->andWhere('type = :type')->setParameter('type', $filter['type']);
        }

        if (isset($filter['from_date'])) {
            $qb->andWhere('created_at >= :from_date')->setParameter('from_date', $filter['from_date']);
        }

        if (isset($filter['to_date'])) {
            $qb->andWhere('created_at <= :to_date')->setParameter('to_date', $filter['to_date']);
        }

        $results = $qb->fetchAllAssociative();

        // Decode JSON fields
        foreach ($results as &$row) {
            try {
                $row['content'] = json_decode($row['content'], true, 512, JSON_THROW_ON_ERROR);
                $row['metadata'] = $row['metadata'] ? json_decode($row['metadata'], true, 512, JSON_THROW_ON_ERROR) : [];
            } catch (JsonException $e) {
                // In bulk export, maybe log and continue, or fail?
                // For now, fail loud as per other methods.
                throw new QuestionException("JSON error in bulk export for ID " . $row['id'] . ": " . $e->getMessage(), 500, $e);
            }
        }

        return $results;
    }
}
