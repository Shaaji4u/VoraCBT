<?php
$title = 'Exam Runtime';
ob_start();
?>
<div class="container-fluid py-3">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 id="exam-title" class="h5 mb-1">Loading exam…</h1>
                <p id="section-title" class="small text-secondary mb-0">Preparing section details…</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span id="exam-timer" class="badge text-bg-dark p-2" aria-live="polite">00:00:00</span>
                <span id="question-type-badge" class="badge text-bg-light text-dark p-2">-</span>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <aside class="col-lg-3">
                    <div class="border rounded-3 p-3 h-100">
                        <h2 class="h6 mb-2">Question Navigator</h2>
                        <p id="nav-total-questions" class="small text-secondary mb-3">0 Questions</p>
                        <div id="navigator-grid" class="d-grid" style="grid-template-columns:repeat(5,minmax(0,1fr));gap:.4rem;"></div>
                    </div>
                </aside>

                <main class="col-lg-9">
                    <div class="border rounded-3 p-3 p-md-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <p id="question-number" class="fw-semibold mb-0">Question 0 of 0</p>
                            <button id="btn-flag" class="btn btn-outline-warning btn-sm" type="button">
                                <span class="material-symbols-outlined align-middle">flag</span> Flag
                            </button>
                        </div>

                        <div id="question-container" class="mb-3"></div>

                        <div class="progress" role="progressbar" aria-label="Exam progress" aria-valuemin="0" aria-valuemax="100">
                            <div id="exam-progress" class="progress-bar" style="width: 0%"></div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between mt-4 gap-2">
                            <button id="btn-prev" class="btn btn-outline-secondary" type="button">Previous</button>
                            <div class="d-flex gap-2">
                                <button id="btn-next" class="btn btn-primary" type="button">Next</button>
                                <button id="btn-submit" class="btn btn-danger" type="button">Submit Exam</button>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</div>
<?php
$scripts = '<script type="module" src="/js/pages/exam.js"></script>';
$content = ob_get_clean();
include __DIR__ . '/../layouts/exam.php';
?>
