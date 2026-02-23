<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

class PageController
{
    public function home(): void
    {
        include __DIR__ . '/../../../../resources/views/auth/login.php';
    }

    public function login(): void
    {
        include __DIR__ . '/../../../../resources/views/auth/login.php';
    }

    public function adminDashboard(): void
    {
        include __DIR__ . '/../../../../resources/views/admin/dashboard.php';
    }

    public function studentDashboard(): void
    {
        include __DIR__ . '/../../../../resources/views/student/dashboard.php';
    }

    public function studentExam(): void
    {
        include __DIR__ . '/../../../../resources/views/student/exam.php';
    }

    public function adminExamCreate(): void
    {
        include __DIR__ . '/../../../../resources/views/admin/exams/create.php';
    }

    public function studentResults(): void
    {
        include __DIR__ . '/../../../../resources/views/student/results.php';
    }
}
