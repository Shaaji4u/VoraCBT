<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Infrastructure\Cache\CacheInterface;

class RateLimitService
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Check if the key is within the limit.
     * Returns true if request is allowed, false if rate limited.
     *
     * @param string $key Identifier (e.g. "ip:127.0.0.1:login")
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @return bool
     */
    public function check(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $current = (int) $this->cache->get($key, 0);

        if ($current >= $maxAttempts) {
            return false;
        }

        $this->cache->set($key, $current + 1, $decaySeconds);
        return true;
    }

    /**
     * Get remaining attempts.
     */
    public function attempts(string $key): int
    {
        return (int) $this->cache->get($key, 0);
    }

    /**
     * Reset the rate limiter for a key.
     */
    public function clear(string $key): void
    {
        $this->cache->delete($key);
    }
}
