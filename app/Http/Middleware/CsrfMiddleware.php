<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Http\ApiResponse;
use App\Core\Http\MiddlewareInterface;

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(array $request, callable $next): mixed
    {
        $method = strtoupper((string) ($request['method'] ?? 'GET'));
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $sessionToken = isset($_SESSION['_csrf_token']) ? (string) $_SESSION['_csrf_token'] : '';
        if ($sessionToken === '') {
            return ApiResponse::error('CSRF token is not initialized', 419);
        }

        $headers = $request['headers'] ?? [];
        $headerToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
        $bodyToken = $request['post']['_csrf'] ?? '';
        $providedToken = is_string($headerToken) && $headerToken !== '' ? $headerToken : (is_string($bodyToken) ? $bodyToken : '');

        if ($providedToken === '' || !hash_equals($sessionToken, $providedToken)) {
            return ApiResponse::error('CSRF token mismatch', 419);
        }

        return $next($request);
    }
}
