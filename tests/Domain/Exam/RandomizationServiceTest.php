<?php

declare(strict_types=1);

namespace Tests\Domain\Exam;

use App\Domain\Exam\Service\ExamTemplateService;
use App\Domain\Exam\Service\ExamSectionService;
use App\Domain\Exam\Service\RandomizationService;
use App\Core\Database\Migration\MigrationRunner;
use App\Core\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use DateTime;

class RandomizationServiceTest extends TestCase
{
    private RandomizationService $randomizationService;
    private ExamTemplateService $templateService;
    private ExamSectionService $sectionService;
    private Connection $db;

    protected function setUp(): void
    {
        ob_start();
        $runner = new MigrationRunner();
        $runner->migrate();
        ob_end_clean();

        $this->db = DatabaseManager::getConnection();
        $this->truncateTables();

        $this->randomizationService = new RandomizationService();
        $this->templateService = new ExamTemplateService();
        $this->sectionService = new ExamSectionService();
    }

    private function truncateTables(): void
    {
        // Disable foreign key checks for SQLite
        $this->db->executeStatement('PRAGMA foreign_keys = OFF');
        $this->db->executeStatement('DELETE FROM exam_session_questions');
        $this->db->executeStatement('DELETE FROM exam_sessions');
        $this->db->executeStatement('DELETE FROM exam_questions');
        $this->db->executeStatement('DELETE FROM exam_sections');
        $this->db->executeStatement('DELETE FROM exam_templates');
        $this->db->executeStatement('DELETE FROM questions');
        $this->db->executeStatement('PRAGMA foreign_keys = ON');
    }

    public function testGenerateQuestions(): void
    {
        // 1. Create Template
        $templateId = $this->templateService->createTemplate([
            'title' => 'Test Exam',
            'duration_minutes' => 60
        ]);

        // 2. Create Section with Rules
        $rules = [
            ['type' => 'mcq', 'count' => 2, 'difficulty' => 'easy'],
            ['type' => 'essay', 'count' => 1]
        ];

        $sectionId = $this->sectionService->addSection($templateId, [
            'title' => 'Section A',
            'question_selection_rules' => $rules
        ]);

        // 3. Seed Questions
        // 5 MCQ Easy, 2 Essay
        for ($i = 0; $i < 5; $i++) {
            $this->createQuestion('mcq', ['difficulty' => 'easy']);
        }
        for ($i = 0; $i < 2; $i++) {
            $this->createQuestion('essay', []);
        }

        // 4. Create Mock Session ID
        $userId = Uuid::uuid4()->toString();
        $this->createUser($userId);

        $sessionId = Uuid::uuid4()->toString();
        $this->createSession($sessionId, $templateId, $userId);

        // 5. Run Randomization
        $seed = 12345;
        $this->randomizationService->generateQuestions($templateId, $sessionId, $seed);

        // 6. Verify
        $questions = $this->db->fetchAllAssociative(
            'SELECT * FROM exam_session_questions WHERE exam_session_id = ? ORDER BY question_order ASC',
            [$sessionId]
        );

        $this->assertCount(3, $questions); // 2 MCQ + 1 Essay

        // Check types via join
        $types = [];
        foreach ($questions as $q) {
            $type = $this->db->fetchOne('SELECT type FROM questions WHERE id = ?', [$q['question_id']]);
            $types[] = $type;
        }

        $mcqCount = count(array_filter($types, fn($t) => $t === 'mcq'));
        $essayCount = count(array_filter($types, fn($t) => $t === 'essay'));

        $this->assertEquals(2, $mcqCount);
        $this->assertEquals(1, $essayCount);
    }

    public function testDeterministicSeed(): void
    {
        // Setup similar to above
        $templateId = $this->templateService->createTemplate(['title' => 'Seed Test']);
        $rules = [['type' => 'mcq', 'count' => 5]];
        $this->sectionService->addSection($templateId, ['title' => 'S1', 'question_selection_rules' => $rules]);

        // 10 MCQs
        for ($i = 0; $i < 10; $i++) {
            $this->createQuestion('mcq', [], "Q{$i}");
        }

        $userId = Uuid::uuid4()->toString();
        $this->createUser($userId);

        $sessionId1 = Uuid::uuid4()->toString();
        $this->createSession($sessionId1, $templateId, $userId);

        $sessionId2 = Uuid::uuid4()->toString();
        $this->createSession($sessionId2, $templateId, $userId);

        $seed = 9999;

        $this->randomizationService->generateQuestions($templateId, $sessionId1, $seed);
        $this->randomizationService->generateQuestions($templateId, $sessionId2, $seed);

        $q1 = $this->db->fetchAllAssociative('SELECT question_id FROM exam_session_questions WHERE exam_session_id = ? ORDER BY question_order', [$sessionId1]);
        $q2 = $this->db->fetchAllAssociative('SELECT question_id FROM exam_session_questions WHERE exam_session_id = ? ORDER BY question_order', [$sessionId2]);

        $this->assertEquals($q1, $q2, "Same seed should produce same questions in same order.");
    }

    private function createQuestion(string $type, array $metadata = [], string $prompt = 'Test'): string
    {
        $id = Uuid::uuid4()->toString();
        $now = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->insert('questions', [
            'id' => $id,
            'type' => $type,
            'content' => json_encode(['prompt' => $prompt]),
            'metadata' => json_encode($metadata),
            'created_at' => $now,
            'updated_at' => $now
        ]);

        return $id;
    }

    private function createUser(string $userId): void
    {
        $this->db->insert('users', [
            'id' => $userId,
            'email' => "user-{$userId}@example.com",
            'password' => 'secret',
            'first_name' => 'Test',
            'last_name' => 'User',
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    private function createSession(string $sessionId, string $templateId, string $userId): void
    {
        $this->db->insert('exam_sessions', [
            'id' => $sessionId,
            'exam_template_id' => $templateId,
            'user_id' => $userId,
            'status' => 'in_progress',
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);
    }
}
