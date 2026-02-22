<?php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\Config\Environment;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class DatabaseManager
{
    private static ?Connection $connection = null;

    public static function getConnection(): Connection
    {
        if (self::$connection === null) {
            $params = self::getConnectionParams();
            $config = new Configuration();

            self::$connection = DriverManager::getConnection($params, $config);
        }

        return self::$connection;
    }

    private static function getConnectionParams(): array
    {
        $env = Environment::getInstance();
        $dbConnection = $env->get('DB_CONNECTION', 'mysql');

        $driverMap = [
            'mysql' => 'pdo_mysql',
            'pgsql' => 'pdo_pgsql',
            'sqlite' => 'pdo_sqlite',
        ];

        $driver = $driverMap[$dbConnection] ?? $dbConnection;

        $params = [
            'driver'   => $driver,
            'host'     => $env->get('DB_HOST', '127.0.0.1'),
            'user'     => $env->get('DB_USERNAME', 'root'),
            'password' => $env->get('DB_PASSWORD', ''),
            'dbname'   => $env->get('DB_DATABASE', 'cbt_platform'),
            'port'     => (int)$env->get('DB_PORT', 3306),
            'charset'  => 'utf8mb4',
        ];

        if ($driver === 'pdo_sqlite') {
             // If DB_DATABASE is just a filename, assume storage path. If full path, use it.
             $dbName = $env->get('DB_DATABASE', 'database.sqlite');
             if (strpos($dbName, '/') === false) {
                 $params['path'] = __DIR__ . '/../../../storage/' . $dbName;
             } else {
                 $params['path'] = $dbName;
             }
             unset($params['dbname'], $params['host'], $params['user'], $params['password'], $params['port']);
        }

        return $params;
    }
}
