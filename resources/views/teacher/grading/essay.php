<?php
$title = "AI-Assisted Essay Grading";
ob_start();
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Essay Grading Workspace</h1>
            <p class="text-secondary mb-0">Student: Amina Yusuf • Exam: Civic Studies • Question 3</p>
        </div>
        <span class="badge text-bg-light border">Queue: 18 pending</span>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><strong>Student Answer</strong></div>
                <div class="card-body">
                    <p>
                        Democracy allows citizens to choose leaders and hold them accountable through elections.
                        It promotes fairness because every eligible person has a vote and can influence governance...
                    </p>
                </div>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>AI Suggestion</strong>
                    <span class="badge text-bg-info">Confidence: 0.82 (Medium-High)</span>
                </div>
                <div class="card-body d-grid gap-3">
                    <div class="border rounded p-3">
                        <div class="small text-secondary">Proposed Score</div>
                        <div class="h4 mb-0">14 / 20</div>
                    </div>
                    <div>
                        <div class="small text-secondary mb-1">Feedback draft</div>
                        <ul class="mb-0">
                            <li>Strong thesis statement.</li>
                            <li>Needs more evidence from civic institutions.</li>
                            <li>Conclusion is clear but brief.</li>
                        </ul>
                    </div>
                    <div class="alert alert-warning py-2 mb-0">AI does not auto-commit. Teacher approval is required.</div>
                </div>
            </section>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-success">Approve</button>
        <button class="btn btn-primary">Edit & Save</button>
        <button class="btn btn-outline-danger">Reject</button>
        <button class="btn btn-outline-secondary ms-auto">Next Student</button>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
?>
