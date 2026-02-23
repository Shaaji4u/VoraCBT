<?php
$title = 'Proctor Dashboard';
ob_start();
?>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Proctor Dashboard</h1>
            <p class="text-secondary mb-0">Real-time monitoring of active exam sessions.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="small text-secondary">Auto-refresh: manual</span>
            <button id="btn-refresh-sessions" class="btn btn-outline-secondary btn-sm" type="button">Refresh now</button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-2">
            <input class="form-control form-control-sm w-auto" placeholder="Search student" aria-label="Search student">
            <select class="form-select form-select-sm w-auto" aria-label="Filter exams"><option>All Exams</option></select>
            <select class="form-select form-select-sm w-auto" aria-label="Filter classes"><option>All Classes</option></select>
            <select class="form-select form-select-sm w-auto" aria-label="Filter severities"><option>All Severities</option></select>
            <select class="form-select form-select-sm w-auto" aria-label="Filter flag types"><option>All Flag Types</option></select>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student/User</th>
                        <th>Source</th>
                        <th>Exam</th>
                        <th>Event Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="session-table-body">
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">Loading recent events…</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="dashboard-status" class="alert alert-light border mt-3 mb-0" role="status">
        Drill-down view includes timeline, event history, device fingerprint, and supervisor notes.
    </div>
</div>
<?php
$scripts = '<script type="module" src="/js/pages/admin-dashboard.js"></script>';
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
