<?php

declare(strict_types=1);

namespace App\Core\Config;

use Dotenv\Dotenv;

class Environment
{
    private static ?self $instance = null;
    private array $env = [];

    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $this->env = $dotenv->safeLoad();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->env[$key] ?? $_ENV[$key] ?? $default;
    }

    public function isConnectedMode(): bool
    {
        return ($this->get('SYSTEM_MODE') === 'connected');
    }

    public function isStandaloneMode(): bool
    {
        return ($this->get('SYSTEM_MODE') === 'standalone');
    }
}
