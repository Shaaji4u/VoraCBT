<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Integration\Service\SyncService;
use App\Integration\Service\SmsApiClient;
use App\Core\Database\DatabaseManager;
use App\Core\Database\Migration\MigrationRunner;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;

class SyncServiceTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        ob_start();
        $runner = new MigrationRunner();
        $runner->migrate();
        ob_end_clean();

        $this->db = DatabaseManager::getConnection();
        // Use 'users' table which exists from migration 2023_10_27_000001
        $this->db->executeStatement('DELETE FROM users');
    }

    public function testSyncStudentsUpserts()
    {
        $mockApi = $this->createMock(SmsApiClient::class);
        $mockApi->method('request')->willReturn([
            'status' => 200,
            'body' => json_encode([
                ['email' => 'student1@example.com', 'first_name' => 'John', 'last_name' => 'Doe'],
                ['email' => 'student2@example.com', 'first_name' => 'Jane', 'last_name' => 'Doe']
            ])
        ]);

        $service = new SyncService($mockApi);
        $result = $service->syncStudents();

        $this->assertEquals(2, $result['synced']);

        $count = $this->db->fetchOne("SELECT COUNT(*) FROM users");
        $this->assertEquals(2, $count);
    }
}
