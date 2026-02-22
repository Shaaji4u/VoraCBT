<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Http\MiddlewareInterface;
use App\Core\Http\ApiResponse;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use RuntimeException;

class AuthMiddleware implements MiddlewareInterface
{
    private string $secretKey;

    public function __construct(?string $secretKey = null)
    {
        $resolved = $secretKey ?? ($_ENV['JWT_SECRET'] ?? '');
        if ($resolved === '') {
            throw new RuntimeException('JWT_SECRET is not configured.');
        }

        $this->secretKey = $resolved;
    }

    public function handle(array $request, callable $next): mixed
    {
        $headers = $request['headers'] ?? [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return ApiResponse::error('Unauthorized', 401);
        }

        $jwt = $matches[1];

        try {
            $decoded = JWT::decode($jwt, new Key($this->secretKey, 'HS256'));
            // Add user info to request
            $request['user'] = (array) $decoded;
        } catch (Exception $e) {
            return ApiResponse::error('Invalid Token', 401);
        }

        return $next($request);
    }
}
