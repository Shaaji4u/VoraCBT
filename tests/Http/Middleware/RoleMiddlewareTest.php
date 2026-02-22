<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Core\Http\Response;
use App\Http\Middleware\RoleMiddleware;
use PHPUnit\Framework\TestCase;

class RoleMiddlewareTest extends TestCase
{
    public function testUnauthorizedWhenUserIsMissing(): void
    {
        $middleware = new RoleMiddleware(['admin']);
        $request = [];
        $next = function ($req) {
            return 'success';
        };

        $response = $middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(401, $response->status);
        $this->assertEquals('Unauthorized', $response->content['data']['message']);
    }

    public function testForbiddenWhenRoleIsNotAllowed(): void
    {
        $middleware = new RoleMiddleware(['admin']);
        $request = ['user' => ['role' => 'student']];
        $next = function ($req) {
            return 'success';
        };

        $response = $middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(403, $response->status);
        $this->assertEquals('Forbidden', $response->content['data']['message']);
    }

    public function testAllowedWhenRoleMatches(): void
    {
        $middleware = new RoleMiddleware(['admin', 'staff']);
        $request = ['user' => ['role' => 'admin']];
        $next = function ($req) {
            return 'success';
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals('success', $response);
    }

    public function testAllowedWhenRoleMatchesAnotherAllowedRole(): void
    {
        $middleware = new RoleMiddleware(['admin', 'staff']);
        $request = ['user' => ['role' => 'staff']];
        $next = function ($req) {
            return 'success';
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals('success', $response);
    }

    public function testForbiddenWhenRoleIsMissingInUser(): void
    {
        $middleware = new RoleMiddleware(['admin']);
        $request = ['user' => ['id' => 1]]; // no role
        $next = function ($req) {
            return 'success';
        };

        $response = $middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(403, $response->status);
    }

    public function testForbiddenWhenAllowedRolesIsEmpty(): void
    {
        $middleware = new RoleMiddleware([]);
        $request = ['user' => ['role' => 'admin']];
        $next = function ($req) {
            return 'success';
        };

        $response = $middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(403, $response->status);
    }
}
