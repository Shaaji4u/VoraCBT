<?php

declare(strict_types=1);

/**
 * CBT Enterprise Platform - Entry Point
 *
 * This is the single entry point for all incoming HTTP requests.
 * It handles the bootstrapping of the application, including:
 * 1. Autoloading via Composer
 * 2. Environment variable loading
 * 3. Error handling
 * 4. Routing dispatch
 */

// 1. Load Composer Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// 3. Set Error Reporting
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// 4. Routing Dispatch
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Http\Middleware\RateLimitMiddleware;
use App\Infrastructure\Cache\FileCache;
use App\Core\Http\Response;

// Define route collector callback
$dispatcher = simpleDispatcher(function(RouteCollector $r) {
    // Load routes from routes/api.php
    $apiRoutes = require __DIR__ . '/../routes/api.php';
    if (is_callable($apiRoutes)) {
        $apiRoutes($r);
    }

    // Load routes from routes/web.php
    if (file_exists(__DIR__ . '/../routes/web.php')) {
        $webRoutes = require __DIR__ . '/../routes/web.php';
        if (is_callable($webRoutes)) {
            $webRoutes($r);
        }
    }
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

// Set default content type based on URI prefix
if (strpos($uri, '/api') === 0) {
    header('Content-Type: application/json');
} else {
    header('Content-Type: text/html; charset=utf-8');
}

// Global API rate limiting for live exam stability.
if (strpos($uri, '/api') === 0) {
    $rateLimit = (int) ($_ENV['API_RATE_LIMIT'] ?? 120);
    $rateWindow = (int) ($_ENV['API_RATE_LIMIT_WINDOW'] ?? 60);

    $middleware = new RateLimitMiddleware(new FileCache(__DIR__ . '/../storage/cache'), $rateLimit, $rateWindow);

    $request = [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'headers' => function_exists('getallheaders') ? getallheaders() : [],
    ];

    $result = $middleware->handle($request, static fn(array $req) => true);
    if ($result instanceof Response) {
        $result->send();
        exit;
    }
}

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        http_response_code(404);
        if (strpos($uri, '/api') === 0) {
            echo json_encode(['error' => 'Not Found']);
        } else {
            echo "<h1>404 Not Found</h1>";
        }
        break;
    case Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        if (strpos($uri, '/api') === 0) {
            echo json_encode(['error' => 'Method Not Allowed', 'allowed' => $allowedMethods]);
        } else {
             echo "<h1>405 Method Not Allowed</h1>";
        }
        break;
    case Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        // Handle Closure
        if ($handler instanceof Closure) {
            call_user_func_array($handler, $vars);
        } else {
             // Handle Controller@method or array
             // This is a simplified dispatcher logic for the skeleton
             echo json_encode(['handler' => 'controller_dispatch_placeholder', 'vars' => $vars]);
        }
        break;
}
