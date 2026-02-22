<?php

declare(strict_types=1);

namespace Tests\Domain\Grading;

use App\Core\Database\DatabaseManager;
use App\Core\Database\Migration\MigrationRunner;
use App\Domain\Grading\Service\AnalyticsService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class AnalyticsServiceTest extends TestCase
{
    private Connection $db;
    private AnalyticsService $service;

    protected function setUp(): void
    {
        ob_start();
        $runner = new MigrationRunner();
        $runner->migrate();
        ob_end_clean();

        $this->db = DatabaseManager::getConnection();
        $this->truncateTables();

        $this->service = new AnalyticsService($this->db);
    }

    private function truncateTables(): void
    {
        $this->db->executeStatement('PRAGMA foreign_keys = OFF');
        $this->db->executeStatement('DELETE FROM exam_analytics');
        $this->db->executeStatement('DELETE FROM exam_results');
        $this->db->executeStatement('DELETE FROM exam_session_answers');
        $this->db->executeStatement('DELETE FROM exam_sessions');
        $this->db->executeStatement('DELETE FROM exam_questions');
        $this->db->executeStatement('DELETE FROM exam_sections');
        $this->db->executeStatement('DELETE FROM exam_templates');
        $this->db->executeStatement('DELETE FROM questions');
        $this->db->executeStatement('DELETE FROM users');
        $this->db->executeStatement('PRAGMA foreign_keys = ON');
    }

    public function testCalculateExamAnalytics(): void
    {
        // 1. Create Exam
        $examId = Uuid::uuid4()->toString();
        $this->db->insert('exam_templates', [
            'id' => $examId,
            'title' => 'Stats Exam',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $userId1 = Uuid::uuid4()->toString();
        $this->db->insert('users', [
            'id' => $userId1,
            'email' => 'u1@test.com', 'password' => 'secret', 'first_name' => 'A', 'last_name' => 'B',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $userId2 = Uuid::uuid4()->toString();
        $this->db->insert('users', [
            'id' => $userId2,
            'email' => 'u2@test.com', 'password' => 'secret', 'first_name' => 'A', 'last_name' => 'B',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // 2. Create Sessions & Results
        // Student 1: Score 80, Passed
        $s1 = Uuid::uuid4()->toString();
        $this->db->insert('exam_sessions', [
            'id' => $s1, 'exam_template_id' => $examId, 'user_id' => $userId1, 'status' => 'graded',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->insert('exam_results', [
            'id' => Uuid::uuid4()->toString(), 'exam_session_id' => $s1,
            'total_score' => 80, 'max_score' => 100, 'percentage' => 80, 'is_passed' => 1, 'graded_at' => date('Y-m-d H:i:s')
        ]);

        // Student 2: Score 60, Failed
        $s2 = Uuid::uuid4()->toString();
        $this->db->insert('exam_sessions', [
            'id' => $s2, 'exam_template_id' => $examId, 'user_id' => $userId2, 'status' => 'graded',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->insert('exam_results', [
            'id' => Uuid::uuid4()->toString(), 'exam_session_id' => $s2,
            'total_score' => 60, 'max_score' => 100, 'percentage' => 60, 'is_passed' => 0, 'graded_at' => date('Y-m-d H:i:s')
        ]);

        // 3. Create Answers for Question Analytics
        $qId = Uuid::uuid4()->toString();
        $this->db->insert('questions', ['id' => $qId, 'type' => 'mcq', 'content' => '{}', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);

        // S1: Correct
        $this->db->insert('exam_session_answers', [
            'id' => Uuid::uuid4()->toString(), 'exam_session_id' => $s1, 'question_id' => $qId,
            'is_correct' => 1, 'marks_obtained' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')
        ]);

        // S2: Incorrect
        $this->db->insert('exam_session_answers', [
            'id' => Uuid::uuid4()->toString(), 'exam_session_id' => $s2, 'question_id' => $qId,
            'is_correct' => 0, 'marks_obtained' => 0, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')
        ]);

        // 4. Calculate
        $this->service->calculateExamAnalytics($examId);

        // 5. Verify
        $analytics = $this->db->fetchAssociative("SELECT * FROM exam_analytics WHERE exam_template_id = ?", [$examId]);
        $this->assertNotEmpty($analytics);

        $metrics = json_decode($analytics['metrics'], true);

        // Count: 2
        $this->assertEquals(2, $metrics['count']);
        // Avg: 70
        $this->assertEquals(70.0, $metrics['avg_score']);
        // Max: 80
        $this->assertEquals(80.0, $metrics['max_score']);
        // Min: 60
        $this->assertEquals(60.0, $metrics['min_score']);
        // Median: (60+80)/2 = 70
        $this->assertEquals(70.0, $metrics['median']);
        // Std Dev: sqrt( ((80-70)^2 + (60-70)^2) / (2-1) ) = sqrt( (100+100)/1 ) = sqrt(200) = 14.14
        $this->assertEquals(14.14, $metrics['std_dev']);
        // Pass Rate: 1/2 = 50%
        $this->assertEquals(50.0, $metrics['pass_rate']);

        // Question Stats
        // Difficulty: 1 correct / 2 total = 0.5
        $this->assertArrayHasKey($qId, $metrics['questions']);
        $this->assertEquals(0.5, $metrics['questions'][$qId]['difficulty_index']);
    }
}
