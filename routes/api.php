<?php

declare(strict_types=1);

/**
 * Route Definitions
 *
 * Use $r (FastRoute\RouteCollector) to define routes.
 */

return function (FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', function () {
        echo json_encode(['message' => 'Welcome to CBT Enterprise Platform API']);
    });

    $r->addRoute('GET', '/status', function () {
        echo json_encode(['status' => 'ok', 'timestamp' => time()]);
    });

    // Example of grouped routes
    $r->addGroup('/api/v1', function (FastRoute\RouteCollector $r) {
        $r->addRoute('GET', '/users', function () {
            echo json_encode(['users' => []]);
        });

        $r->addRoute('POST', '/login', function () {
            // Authentication logic here
            echo json_encode(['token' => 'example_token']);
        });
    });
};
