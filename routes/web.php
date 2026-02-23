<?php

use FastRoute\RouteCollector;
use App\Http\Controllers\Web\PageController;

return function (RouteCollector $r) {
    $r->get('/', [PageController::class, 'home']);
    $r->get('/login', [PageController::class, 'login']);

    $r->get('/admin/dashboard', [PageController::class, 'adminDashboard']);
    $r->get('/admin/exams/create', [PageController::class, 'adminExamCreate']);

    $r->get('/student/dashboard', [PageController::class, 'studentDashboard']);
    $r->get('/student/exam', [PageController::class, 'studentExam']);
    $r->get('/student/results', [PageController::class, 'studentResults']);
};
