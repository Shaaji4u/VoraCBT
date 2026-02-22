<?php
$title = "Exam Results";
$pageTitle = "Exam Results";
$pageSubtitle = "View your performance";
ob_start();
?>
<div class="row g-4 justify-content-center">
    <!-- Score Card -->
    <div class="col-md-8 col-lg-6">
        <div class="card border rounded-4 shadow-sm h-100">
            <div class="card-body p-5 text-center d-flex flex-column align-items-center justify-content-center">
                <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center mb-4" style="width: 120px; height: 120px;">
                    <span class="display-4 fw-bold">85%</span>
                </div>
                <h2 class="h3 fw-bold text-body mb-2">Excellent Work!</h2>
                <p class="text-secondary mb-4">You have successfully completed the exam. Your performance indicates a strong understanding of the core concepts.</p>

                <div class="d-flex gap-3 w-100 justify-content-center">
                    <a href="/student/dashboard" class="btn btn-outline-secondary px-4 fw-medium">Back to Dashboard</a>
                    <button class="btn btn-primary px-4 fw-medium shadow-sm">Download Report</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="col-md-8 col-lg-6">
        <div class="card border rounded-4 shadow-sm h-100">
            <div class="card-header bg-surface border-bottom p-4">
                <h6 class="fw-bold text-body mb-0">Performance Breakdown</h6>
            </div>
            <div class="card-body p-4">
                <div class="d-flex flex-column gap-4">
                    <!-- Stat 1 -->
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold text-body">Physics</span>
                            <span class="small fw-bold text-primary">90%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 90%" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <!-- Stat 2 -->
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold text-body">History</span>
                            <span class="small fw-bold text-warning">75%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <!-- Stat 3 -->
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold text-body">General Knowledge</span>
                            <span class="small fw-bold text-success">100%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-top">
                    <div class="row text-center">
                        <div class="col-4">
                            <h5 class="fw-bold text-body mb-0">28</h5>
                            <small class="text-secondary x-small text-uppercase fw-bold">Correct</small>
                        </div>
                        <div class="col-4 border-start border-end">
                            <h5 class="fw-bold text-body mb-0">2</h5>
                            <small class="text-secondary x-small text-uppercase fw-bold">Incorrect</small>
                        </div>
                        <div class="col-4">
                            <h5 class="fw-bold text-body mb-0">0</h5>
                            <small class="text-secondary x-small text-uppercase fw-bold">Skipped</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/student.php';
?>
