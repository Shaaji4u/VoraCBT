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
            <span class="small text-secondary">Auto-refresh: every 15s</span>
            <button class="btn btn-outline-secondary btn-sm">Refresh now</button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-2">
            <input class="form-control form-control-sm w-auto" placeholder="Search student">
            <select class="form-select form-select-sm w-auto"><option>All Exams</option></select>
            <select class="form-select form-select-sm w-auto"><option>All Classes</option></select>
            <select class="form-select form-select-sm w-auto"><option>All Severities</option></select>
            <select class="form-select form-select-sm w-auto"><option>All Flag Types</option></select>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Flags</th>
                        <th>Time Remaining</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Chinedu Okafor</td>
                        <td><span class="badge text-bg-success">In Progress</span></td>
                        <td>Tab switching (2)</td>
                        <td>00:37:02</td>
                        <td><button class="btn btn-sm btn-outline-primary">Session Log</button></td>
                    </tr>
                    <tr>
                        <td>Grace Muli</td>
                        <td><span class="badge text-bg-danger">Flagged</span></td>
                        <td><span class="badge text-bg-danger">High</span> Suspicious timing</td>
                        <td>00:35:40</td>
                        <td><button class="btn btn-sm btn-outline-primary">Session Log</button></td>
                    </tr>
                    <tr>
                        <td>Kelvin Obasi</td>
                        <td><span class="badge text-bg-warning">Flagged</span></td>
                        <td><span class="badge text-bg-warning">Medium</span> Webcam anomaly</td>
                        <td>00:30:55</td>
                        <td><button class="btn btn-sm btn-outline-primary">Session Log</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="alert alert-light border mt-3 mb-0">
        Drill-down view includes timeline, event history, device fingerprint, and supervisor notes.
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
