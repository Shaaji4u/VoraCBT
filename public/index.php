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
header('Content-Type: application/json');

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

// Define route collector callback
$dispatcher = simpleDispatcher(function(RouteCollector $r) {
    // Load routes from routes/api.php
    $routeDefinition = require __DIR__ . '/../routes/api.php';
    if (is_callable($routeDefinition)) {
        $routeDefinition($r);
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

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
    case Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed', 'allowed' => $allowedMethods]);
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
