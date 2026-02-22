<?php

use FastRoute\RouteCollector;
use App\Http\Controllers\Admin\IdentityController;
use App\Http\Controllers\Api\ProctoringController;

return function (RouteCollector $r) {
    // Identity Routes
    $r->addGroup('/api', function (RouteCollector $r) {
        $r->post('/admin/students/import/preview', function() {
            (new IdentityController())->preview();
        });
        $r->post('/admin/students/import/commit', function() {
            (new IdentityController())->commit();
        });
        $r->get('/admin/students/credentials/export', function() {
            (new IdentityController())->export();
        });

        // Proctoring Routes
        $r->post('/proctoring/event', function() {
            (new ProctoringController())->logEvent();
        });
        $r->post('/proctoring/heartbeat', function() {
            (new ProctoringController())->heartbeat();
        });
    });

    $r->get('/health', function () {
        header('Content-Type: application/json');

        $checks = [
            'db' => ['status' => 'down'],
            'cache' => ['status' => 'unsupported', 'driver' => 'none'],
            'queue' => ['status' => 'unsupported', 'backlog' => null, 'failed_jobs' => null],
            'storage' => ['status' => 'down', 'writable' => false],
        ];

        $dbStatus = 'down';
        $pdo = null;

        try {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $name = $_ENV['DB_DATABASE'] ?? '';
            $user = $_ENV['DB_USERNAME'] ?? '';
            $pass = $_ENV['DB_PASSWORD'] ?? '';

            if ($name !== '' && $user !== '') {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
                $start = microtime(true);
                $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo->query('SELECT 1');
                $checks['db'] = [
                    'status' => 'ok',
                    'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                ];
                $dbStatus = 'ok';
            }
        } catch (Throwable $e) {
            $checks['db'] = ['status' => 'down'];
        }

        $cacheDriver = $_ENV['CACHE_DRIVER'] ?? 'none';
        if ($cacheDriver === 'redis' && class_exists('Redis')) {
            try {
                $redis = new Redis();
                $redis->connect($_ENV['REDIS_HOST'] ?? '127.0.0.1', (int) ($_ENV['REDIS_PORT'] ?? 6379), 1.0);
                $redis->ping();
                $checks['cache'] = ['status' => 'ok', 'driver' => 'redis'];
            } catch (Throwable $e) {
                $checks['cache'] = ['status' => 'degraded', 'driver' => 'redis'];
            }
        } elseif ($cacheDriver === 'file') {
            $cacheDir = __DIR__ . '/../storage/cache';
            $checks['cache'] = ['status' => is_dir($cacheDir) ? 'ok' : 'degraded', 'driver' => 'file'];
        }

        if ($pdo instanceof PDO) {
            try {
                $pending = (int) $pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn();
                $failed = 0;
                try {
                    $failed = (int) $pdo->query('SELECT COUNT(*) FROM failed_jobs')->fetchColumn();
                } catch (Throwable $e) {
                    $failed = 0;
                }
                $checks['queue'] = [
                    'status' => 'ok',
                    'backlog' => $pending,
                    'failed_jobs' => $failed,
                ];
            } catch (Throwable $e) {
                $checks['queue'] = ['status' => 'unsupported', 'backlog' => null, 'failed_jobs' => null];
            }
        }

        $storagePath = __DIR__ . '/../storage/cache';
        $tempFile = $storagePath . '/.healthcheck.tmp';
        try {
            if (is_dir($storagePath) && file_put_contents($tempFile, 'ok') !== false) {
                @unlink($tempFile);
                $checks['storage'] = ['status' => 'ok', 'writable' => true];
            }
        } catch (Throwable $e) {
            $checks['storage'] = ['status' => 'down', 'writable' => false];
        }

        $status = 'ok';
        $code = 200;

        if ($dbStatus !== 'ok' || ($checks['storage']['writable'] ?? false) !== true) {
            $status = 'down';
            $code = 503;
        } elseif (($checks['cache']['status'] ?? 'ok') !== 'ok') {
            $status = 'degraded';
            $code = 206;
        }

        http_response_code($code);
        echo json_encode([
            'status' => $status,
            'timestamp' => gmdate('c'),
            'checks' => $checks,
        ]);
    });
};
