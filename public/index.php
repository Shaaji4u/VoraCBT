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
if ($_ENV['APP_DEBUG'] === 'true') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// 4. Routing Dispatch (Placeholder)
// fast-route dispatching logic will go here.
// For now, we just output a confirmation message.

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'CBT Enterprise Platform API is running.',
    'env' => $_ENV['APP_ENV'] ?? 'unknown',
    'mode' => $_ENV['SYSTEM_MODE'] ?? 'unknown'
]);
