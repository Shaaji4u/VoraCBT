<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Integration\Service\TokenManager;
use App\Core\Database\DatabaseManager;
use App\Core\Database\Migration\MigrationRunner;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;

class TokenManagerTest extends TestCase
{
    private TokenManager $tokenManager;
    private Connection $db;

    protected function setUp(): void
    {
        ob_start();
        $runner = new MigrationRunner();
        $runner->migrate();
        ob_end_clean();

        $this->db = DatabaseManager::getConnection();
        $this->db->executeStatement('DELETE FROM oauth_tokens');

        $this->tokenManager = new TokenManager();
    }

    public function testStoreAndGetToken()
    {
        $this->tokenManager->storeToken('sms', 'access_123', 'refresh_123', 3600);

        $token = $this->tokenManager->getAccessToken('sms');
        $this->assertEquals('access_123', $token);

        $refreshToken = $this->tokenManager->getRefreshToken('sms');
        $this->assertEquals('refresh_123', $refreshToken);
    }

    public function testGetTokenReturnsNullIfExpired()
    {
        $this->tokenManager->storeToken('sms', 'access_expired', 'refresh_expired', -10);

        $token = $this->tokenManager->getAccessToken('sms');
        $this->assertNull($token);
    }

    public function testUpdateToken()
    {
        $this->tokenManager->storeToken('sms', 'access_1', 'refresh_1', 3600);
        $this->tokenManager->storeToken('sms', 'access_2', 'refresh_2', 3600);

        $token = $this->tokenManager->getAccessToken('sms');
        $this->assertEquals('access_2', $token);
    }
}
