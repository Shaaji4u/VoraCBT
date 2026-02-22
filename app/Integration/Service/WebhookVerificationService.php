<?php

declare(strict_types=1);

namespace App\Integration\Service;

use App\Core\Config\Environment;

class WebhookVerificationService
{
    private Environment $env;

    public function __construct()
    {
        $this->env = Environment::getInstance();
    }

    public function verify(string $payload, string $signature): bool
    {
        $secret = $this->env->get('SMS_WEBHOOK_SECRET');

        if (!$secret) {
            return false;
        }

        // Assuming HMAC SHA256 as standard
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
