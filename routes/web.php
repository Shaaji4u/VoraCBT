<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $r->get('/', function () {
        // Redirect to dashboard or login
        // For now, load dashboard
        include __DIR__ . '/../resources/views/admin/dashboard.php';
    });

    $r->get('/admin/dashboard', function () {
        include __DIR__ . '/../resources/views/admin/dashboard.php';
    });

    $r->get('/login', function () {
        include __DIR__ . '/../resources/views/auth/login.php';
    });

    // Add other routes as placeholders
    $r->get('/student/exam', function () {
        include __DIR__ . '/../resources/views/student/exam.php';
    });

    $r->get('/admin/exams/create', function () {
        include __DIR__ . '/../resources/views/admin/exams/create.php';
    });

    $r->get('/student/results', function () {
        include __DIR__ . '/../resources/views/student/results.php';
    });
};
