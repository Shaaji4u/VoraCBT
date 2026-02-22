<?php
$title = 'Exam Workspace';
ob_start();
?>
<div class="container-fluid py-3">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 mb-1">Biology Midterm</h1>
                <p class="small text-secondary mb-0">Section B • Question 12 of 50</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge text-bg-dark p-2" aria-live="polite">Time Remaining: 00:42:11</span>
                <button class="btn btn-outline-secondary btn-sm">Full Screen</button>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <aside class="col-lg-3">
                    <div class="border rounded-3 p-3 h-100">
                        <h2 class="h6">Question Navigator</h2>
                        <div class="d-grid" style="grid-template-columns:repeat(5,minmax(0,1fr));gap:.4rem;">
                            <?php for ($i = 1; $i <= 20; $i++): ?>
                                <button class="btn btn-sm <?= $i === 12 ? 'btn-primary' : 'btn-outline-secondary' ?>" aria-label="Question <?= $i ?>"><?= str_pad((string)$i, 2, '0', STR_PAD_LEFT) ?></button>
                            <?php endfor; ?>
                        </div>
                    </div>
                </aside>

                <main class="col-lg-9">
                    <div class="border rounded-3 p-3 p-md-4">
                        <p class="fw-semibold">Which process is responsible for transporting water from roots to leaves in plants?</p>
                        <div class="d-grid gap-2" role="radiogroup" aria-label="Answer choices">
                            <label class="border rounded p-2"><input type="radio" name="answer" class="me-2"> Diffusion</label>
                            <label class="border rounded p-2"><input type="radio" name="answer" class="me-2"> Transpiration pull</label>
                            <label class="border rounded p-2"><input type="radio" name="answer" class="me-2"> Fermentation</label>
                            <label class="border rounded p-2"><input type="radio" name="answer" class="me-2"> Digestion</label>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between mt-4 gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="markReview">
                                <label class="form-check-label" for="markReview">Mark for review</label>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary">Previous</button>
                                <button class="btn btn-primary">Save & Next</button>
                                <button class="btn btn-danger">Submit Exam</button>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" role="status">
                        Session alert: Your exam activity was flagged for review. Please continue.
                    </div>
                    <p class="small text-secondary mt-2 mb-0">No AI hints or auto-modification are shown during exam runtime.</p>
                </main>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/exam.php';
?>
