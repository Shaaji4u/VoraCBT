<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Core\Http\Response;
use App\Http\Middleware\CsrfMiddleware;
use PHPUnit\Framework\TestCase;

class CsrfMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }

        @session_id('csrf-test-' . uniqid());
        session_start();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public function testAllowsSafeMethodsWithoutToken(): void
    {
        $middleware = new CsrfMiddleware();

        $result = $middleware->handle([
            'method' => 'GET',
            'headers' => [],
            'post' => [],
        ], static fn(array $request) => 'ok');

        $this->assertSame('ok', $result);
    }

    public function testBlocksWhenSessionTokenMissing(): void
    {
        $middleware = new CsrfMiddleware();

        $result = $middleware->handle([
            'method' => 'POST',
            'headers' => ['X-CSRF-Token' => 'token'],
            'post' => [],
        ], static fn(array $request) => 'ok');

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(419, $result->status);
    }

    public function testAllowsWhenHeaderTokenMatchesSession(): void
    {
        $_SESSION['_csrf_token'] = 'known-token';
        $middleware = new CsrfMiddleware();

        $result = $middleware->handle([
            'method' => 'POST',
            'headers' => ['X-CSRF-Token' => 'known-token'],
            'post' => [],
        ], static fn(array $request) => 'ok');

        $this->assertSame('ok', $result);
    }

    public function testAllowsWhenBodyTokenMatchesSession(): void
    {
        $_SESSION['_csrf_token'] = 'known-token';
        $middleware = new CsrfMiddleware();

        $result = $middleware->handle([
            'method' => 'DELETE',
            'headers' => [],
            'post' => ['_csrf' => 'known-token'],
        ], static fn(array $request) => 'ok');

        $this->assertSame('ok', $result);
    }

    public function testBlocksWhenTokenMismatches(): void
    {
        $_SESSION['_csrf_token'] = 'known-token';
        $middleware = new CsrfMiddleware();

        $result = $middleware->handle([
            'method' => 'PATCH',
            'headers' => ['X-CSRF-Token' => 'bad-token'],
            'post' => [],
        ], static fn(array $request) => 'ok');

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(419, $result->status);
    }
}
