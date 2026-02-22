<?php

declare(strict_types=1);

namespace Tests\Domain\Exam;

use App\Core\Database\DatabaseManager;
use App\Core\Database\Migration\MigrationRunner;
use App\Domain\Exam\Service\ExamTemplateService;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class ExamLifecycleServiceTest extends TestCase
{
    private ExamTemplateService $service;

    protected function setUp(): void
    {
        ob_start();
        (new MigrationRunner())->migrate();
        ob_end_clean();

        $this->truncateTables();
        $this->service = new ExamTemplateService();
    }

    private function truncateTables(): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('DELETE FROM exam_lifecycle_logs');
        $conn->executeStatement('DELETE FROM exam_templates');
        $conn->executeStatement('DELETE FROM users');
    }

    public function testLifecycleTransitionAndEditGuard(): void
    {
        $conn = DatabaseManager::getConnection();
        $userId = Uuid::uuid4()->toString();
        $now = date('Y-m-d H:i:s');

        $conn->insert('users', [
            'id' => $userId,
            'email' => 'teacher@example.com',
            'password' => 'x',
            'first_name' => 'Teach',
            'last_name' => 'Er',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $examId = $this->service->createTemplate([
            'title' => 'Biology Midterm',
            'created_by' => $userId,
        ]);

        $this->service->transitionLifecycle($examId, 'scheduled', $userId, 'calendar approved');
        $this->service->transitionLifecycle($examId, 'open', $userId, 'exam started');

        $this->expectException(RuntimeException::class);
        $this->service->updateTemplate($examId, ['title' => 'Should Fail']);
    }
}
