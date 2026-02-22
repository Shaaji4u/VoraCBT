<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Http\MiddlewareInterface;
use App\Core\Http\ApiResponse;

class RoleMiddleware implements MiddlewareInterface
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(array $request, callable $next): mixed
    {
        $user = $request['user'] ?? null;

        if (!$user) {
            return ApiResponse::error('Unauthorized', 401);
        }

        if (!in_array($user['role'] ?? '', $this->allowedRoles)) {
            return ApiResponse::error('Forbidden', 403);
        }

        return $next($request);
    }
}
