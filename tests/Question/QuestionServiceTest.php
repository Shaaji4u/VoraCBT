<?php

declare(strict_types=1);

namespace Tests\Question;

use App\Question\Service\QuestionService;
use App\Question\Exception\QuestionException;
use App\Core\Database\Migration\MigrationRunner;
use App\Core\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;

class QuestionServiceTest extends TestCase
{
    private QuestionService $service;

    protected function setUp(): void
    {
        // Suppress output
        ob_start();
        $runner = new MigrationRunner();
        $runner->migrate();
        ob_end_clean();

        $this->truncateTables();

        $this->service = new QuestionService();
    }

    private function truncateTables(): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('DELETE FROM question_versions');
        $conn->executeStatement('DELETE FROM questions');
    }

    public function testCreateQuestion(): void
    {
        $data = [
            'type' => 'mcq',
            'content' => [
                'prompt' => 'What is 2+2?',
                'options' => [
                    ['id' => '1', 'text' => '3'],
                    ['id' => '2', 'text' => '4']
                ],
                'correct_options' => ['2']
            ],
            'metadata' => ['subject' => 'Math']
        ];

        $id = $this->service->createQuestion($data);

        $this->assertIsString($id);

        $question = $this->service->getQuestion($id);
        $this->assertEquals($id, $question['id']);
        $this->assertEquals('mcq', $question['type']);
        $this->assertEquals(1, $question['version']);
        $this->assertEquals('Math', $question['metadata']['subject']);
    }

    public function testUpdateQuestion(): void
    {
        $id = $this->service->createQuestion([
            'type' => 'essay',
            'content' => ['text' => 'Write about AI.']
        ]);

        $this->service->updateQuestion($id, [
            'content' => ['text' => 'Write about AGI.']
        ]);

        $question = $this->service->getQuestion($id);
        $this->assertEquals(2, $question['version']);
        $this->assertEquals('Write about AGI.', $question['content']['text']);

        // Check history
        $v1 = $this->service->getQuestionVersion($id, 1);
        $this->assertEquals('Write about AI.', $v1['content']['text']);
    }

    public function testDeleteQuestion(): void
    {
        $id = $this->service->createQuestion([
            'type' => 'essay',
            'content' => ['text' => 'Delete me.']
        ]);

        $this->service->deleteQuestion($id);

        $this->expectException(QuestionException::class);
        $this->service->updateQuestion($id, ['content' => ['text' => 'Cannot update']]);
    }

    public function testBulkImport(): void
    {
        $questions = [
            [
                'type' => 'essay',
                'content' => ['text' => 'Q1']
            ],
            [
                'type' => 'essay',
                'content' => ['text' => 'Q2']
            ]
        ];

        $ids = $this->service->bulkImport($questions);
        $this->assertCount(2, $ids);

        $this->assertIsString($ids[0]);
        $this->assertIsString($ids[1]);
    }

    public function testBulkExport(): void
    {
        $this->service->createQuestion(['type' => 'essay', 'content' => ['text' => 'Q1']]);
        $this->service->createQuestion(['type' => 'mcq', 'content' => [
            'prompt' => 'Q2',
            'options' => [['id'=>'1', 'text'=>'A']],
            'correct_options' => ['1']
        ]]);

        $export = $this->service->bulkExport(['type' => 'essay']);
        $this->assertCount(1, $export);
        $this->assertEquals('essay', $export[0]['type']);

        $exportAll = $this->service->bulkExport();
        $this->assertCount(2, $exportAll);
    }
}
