<?php

declare(strict_types=1);

namespace App\Integration\Service;

use App\Core\Service\BaseService;
use App\Core\Config\Environment;

class SmsService extends BaseService
{
    private Environment $config;

    public function __construct(Environment $config)
    {
        $this->config = $config;
    }

    public function syncUser(int $userId): bool
    {
        if (!$this->config->isConnectedMode()) {
            return false;
        }
        // Logic to sync with SMS
        return true;
    }
}
