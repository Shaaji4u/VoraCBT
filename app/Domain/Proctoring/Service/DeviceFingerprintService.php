<?php

declare(strict_types=1);

namespace App\Domain\Proctoring\Service;

class DeviceFingerprintService
{
    /**
     * Generate a fingerprint based on available request data.
     * In a real implementation, this would combine frontend signals (canvas fingerprint, etc.)
     * with server-side signals.
     */
    public function generate(array $headers, string $ip): string
    {
        // Simple hash of critical headers
        $data = [
            $ip,
            $headers['User-Agent'] ?? '',
            $headers['Accept-Language'] ?? '',
            $headers['Sec-Ch-Ua'] ?? '', // Client hints if available
        ];

        return hash('sha256', implode('|', $data));
    }

    public function validate(string $storedHash, string $currentHash): bool
    {
        return hash_equals($storedHash, $currentHash);
    }
}
