<?php

declare(strict_types=1);

namespace Tests\Unit\Proctoring;

use App\Core\Service\RateLimitService;
use App\Infrastructure\Cache\CacheInterface;
use PHPUnit\Framework\TestCase;

class RateLimitServiceTest extends TestCase
{
    public function testCheckRateLimit(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $service = new RateLimitService($cache);

        $cache->method('get')->willReturn(0);
        $cache->expects($this->once())->method('set')->with('key', 1, 60);

        $this->assertTrue($service->check('key', 5, 60));
    }

    public function testCheckRateLimitExceeded(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $service = new RateLimitService($cache);

        $cache->method('get')->willReturn(5);
        $cache->expects($this->never())->method('set');

        $this->assertFalse($service->check('key', 5, 60));
    }
}
