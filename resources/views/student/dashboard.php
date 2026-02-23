<?php
$title = 'Student Exam Dashboard';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="fw-bold text-body mb-1">Exam Dashboard</h2>
        <p class="text-secondary mb-0">Start, continue, and review your exam sessions.</p>
    </div>
    <a href="/student/results" class="btn btn-outline-primary btn-sm">View Results</a>
</div>

<div id="student-dashboard-status" class="alert alert-secondary" role="status">Loading dashboard…</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100">
            <div class="card-header bg-white"><h5 class="mb-0">In Progress</h5></div>
            <div id="in-progress-list" class="list-group list-group-flush"></div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100">
            <div class="card-header bg-white"><h5 class="mb-0">Upcoming Exams</h5></div>
            <div class="card-body">
                <div id="upcoming-list" class="row row-cols-1 row-cols-md-2 g-3"></div>
            </div>
        </div>
    </div>
</div>

<div class="card border shadow-sm mt-4">
    <div class="card-header bg-white"><h5 class="mb-0">Recent Results</h5></div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Exam</th>
                    <th>Completed</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody id="history-list"></tbody>
        </table>
    </div>
</div>
<?php
$scripts = '<script type="module" src="/js/pages/student-dashboard.js"></script>';
$content = ob_get_clean();
include __DIR__ . '/../layouts/student.php';
?>
