<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Http\MiddlewareInterface;
use App\Core\Http\ApiResponse;
use App\Infrastructure\Cache\CacheInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    private CacheInterface $cache;
    private int $limit;
    private int $window; // in seconds

    public function __construct(CacheInterface $cache, int $limit = 60, int $window = 60)
    {
        $this->cache = $cache;
        $this->limit = $limit;
        $this->window = $window;
    }

    public function handle(array $request, callable $next): mixed
    {
        $ip = $request['ip'] ?? '127.0.0.1';
        $key = 'rate_limit:' . $ip;

        $data = $this->cache->get($key, ['count' => 0, 'start' => time()]);

        if (!is_array($data)) {
             $data = ['count' => 0, 'start' => time()];
        }

        // Check if window expired manually (in case cache didn't expire yet)
        if (time() - $data['start'] >= $this->window) {
             $data = ['count' => 0, 'start' => time()];
        }

        if ($data['count'] >= $this->limit) {
            return ApiResponse::error('Too Many Requests', 429);
        }

        $data['count']++;

        // We update the cache. We can reset TTL to window size to ensure it sticks around.
        $this->cache->set($key, $data, $this->window);

        return $next($request);
    }
}
