<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Config\Environment;
use App\Core\Http\HttpClientInterface;
use App\Integration\Service\OAuthService;
use App\Integration\Service\TokenManager;
use PHPUnit\Framework\TestCase;

class OAuthServiceTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset Environment singleton if possible, or just set $_ENV
        $_ENV['SYSTEM_MODE'] = 'connected';
        $_ENV['SMS_API_BASE_URL'] = 'http://sms.local';
        $_ENV['SMS_CLIENT_ID'] = 'client';
        $_ENV['SMS_CLIENT_SECRET'] = 'secret';
    }

    public function testGetAccessTokenReturnsStoredToken()
    {
        $mockTokenManager = $this->createMock(TokenManager::class);
        $mockTokenManager->method('getAccessToken')->willReturn('stored_token');

        $service = new OAuthService(null, $mockTokenManager);
        $token = $service->getAccessToken();

        $this->assertEquals('stored_token', $token);
    }

    public function testGetAccessTokenAuthenticatesWhenNoToken()
    {
        $mockTokenManager = $this->createMock(TokenManager::class);
        $mockTokenManager->method('getAccessToken')->willReturn(null);
        $mockTokenManager->method('getRefreshToken')->willReturn(null);
        $mockTokenManager->expects($this->once())->method('storeToken');

        $mockHttp = $this->createMock(HttpClientInterface::class);
        $mockHttp->method('request')->willReturn([
            'status' => 200,
            'body' => json_encode(['access_token' => 'new_token', 'expires_in' => 3600])
        ]);

        $service = new OAuthService($mockHttp, $mockTokenManager);
        $token = $service->getAccessToken();

        $this->assertEquals('new_token', $token);
    }

    public function testGetAccessTokenReturnsNullInStandaloneMode()
    {
        $_ENV['SYSTEM_MODE'] = 'standalone';
        $service = new OAuthService();
        $this->assertNull($service->getAccessToken());
    }
}
