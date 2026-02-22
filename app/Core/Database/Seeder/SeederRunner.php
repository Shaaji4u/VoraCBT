<?php

declare(strict_types=1);

namespace App\Core\Database\Seeder;

use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Connection;
use RuntimeException;

class SeederRunner
{
    private Connection $connection;
    private string $seedsPath;

    public function __construct()
    {
        $this->connection = DatabaseManager::getConnection();
        $this->seedsPath = __DIR__ . '/../../../../database/seeds';
    }

    public function run(?string $className = null): void
    {
        if ($className) {
            $this->runSeeder($className);
            return;
        }

        // Run all seeders in the directory
        $files = glob($this->seedsPath . '/*.php');
        sort($files);

        foreach ($files as $file) {
            require_once $file;
            $class = basename($file, '.php');
            $this->runSeeder($class);
        }
    }

    private function runSeeder(string $className): void
    {
        if (!class_exists($className)) {
            // Try to find file if not loaded
            $file = $this->seedsPath . '/' . $className . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (!class_exists($className)) {
            throw new RuntimeException("Seeder class $className not found");
        }

        echo "Seeding: $className\n";

        $seeder = new $className();
        if (!$seeder instanceof SeederInterface) {
            throw new RuntimeException("Seeder $className must implement SeederInterface");
        }

        $seeder->run($this->connection);

        echo "Seeded:  $className\n";
    }
}
