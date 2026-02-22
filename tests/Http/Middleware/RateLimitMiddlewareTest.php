<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Core\Http\Response;
use App\Http\Middleware\RateLimitMiddleware;
use App\Infrastructure\Cache\FileCache;
use PHPUnit\Framework\TestCase;

class RateLimitMiddlewareTest extends TestCase
{
    public function testBlocksAfterLimit(): void
    {
        $cacheDir = sys_get_temp_dir() . '/voracbt_rl_' . uniqid('', true);
        $middleware = new RateLimitMiddleware(new FileCache($cacheDir), 2, 60);

        $next = static fn(array $request): bool => true;

        $this->assertTrue($middleware->handle(['ip' => '1.2.3.4'], $next));
        $this->assertTrue($middleware->handle(['ip' => '1.2.3.4'], $next));

        $third = $middleware->handle(['ip' => '1.2.3.4'], $next);
        $this->assertInstanceOf(Response::class, $third);
        $this->assertSame(429, $third->status);

        array_map('unlink', glob($cacheDir . '/*') ?: []);
        @rmdir($cacheDir);
    }
}
