<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Http\MiddlewareInterface;

class LogMiddleware implements MiddlewareInterface
{
    public function handle(array $request, callable $next): mixed
    {
        $start = microtime(true);
        $method = $request['method'] ?? 'GET';
        $uri = $request['uri'] ?? '/';

        // Assuming a simple logger or just echo for now, but usually would use a LoggerInterface
        // error_log("[$method] $uri");

        $response = $next($request);

        $duration = microtime(true) - $start;
        // error_log("Completed in " . number_format($duration, 4) . "s");

        return $response;
    }
}
