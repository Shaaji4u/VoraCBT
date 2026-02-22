<?php
$title = "Active Exam Interface - Student View";
ob_start();
?>
    <!-- Top Navigation Bar -->
    <header class="navbar navbar-expand bg-surface border-bottom px-4 py-2 position-relative shadow-sm z-3 flex-shrink-0">
        <!-- Left: Exam Title -->
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center rounded bg-primary-soft text-primary" style="width: 40px; height: 40px;">
                <span class="material-symbols-outlined fs-4">school</span>
            </div>
            <div class="lh-1">
                <h6 class="mb-0 fw-bold text-body tracking-tight" id="exam-title">Loading Exam...</h6>
                <span class="small text-secondary" id="section-title">Loading Section...</span>
            </div>
        </div>

        <!-- Center: Timer -->
        <div class="position-absolute top-50 start-50 translate-middle d-none d-md-flex align-items-center gap-2 bg-body px-3 py-1 rounded-pill border">
            <span class="material-symbols-outlined text-secondary fs-5">timer</span>
            <span class="fs-5 fw-bold font-monospace timer-warning text-body" id="exam-timer">00:00:00</span>
            <span class="badge bg-warning-subtle text-warning text-uppercase fw-bold d-none" id="timer-warning" style="font-size: 0.65rem;">Low Time</span>
        </div>

        <!-- Right: Actions -->
        <div class="ms-auto d-flex align-items-center gap-3">
            <button class="btn btn-light d-none d-sm-flex align-items-center gap-2 text-secondary fw-medium">
                <span class="material-symbols-outlined fs-5">help</span> Help
            </button>
            <button class="btn btn-primary fw-bold shadow-sm px-4" id="btn-submit">Submit Exam</button>
        </div>
    </header>

    <!-- Progress Bar -->
    <div class="progress" style="height: 4px; border-radius: 0;">
        <div class="progress-bar bg-primary transition-width" role="progressbar" id="exam-progress" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
    </div>

    <!-- Main Content Area -->
    <main class="d-flex flex-grow-1 overflow-hidden position-relative">

        <!-- Question Content -->
        <div class="flex-grow-1 overflow-auto custom-scrollbar bg-body p-4 p-md-5">
            <div class="mx-auto d-flex flex-column gap-4 pb-5" style="max-width: 900px;" id="question-wrapper">

                <!-- Question Header -->
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-outline-secondary btn-sm d-lg-none d-flex align-items-center gap-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#questionNavigator" aria-controls="questionNavigator">
                            <span class="material-symbols-outlined fs-6">grid_view</span> Navigator
                        </button>
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider d-none d-sm-inline" id="question-number">Question - of -</span>
                        <span class="badge bg-primary-soft text-primary border border-primary-subtle fw-medium px-2 py-1" id="question-type-badge">Type</span>
                    </div>
                    <button class="btn btn-link text-decoration-none text-secondary hover-text-warning d-flex align-items-center gap-1 p-0 fw-medium group" id="btn-flag">
                        <span class="material-symbols-outlined fs-5 group-hover-fill">flag</span>
                        Flag for review
                    </button>
                </div>

                <!-- Question Container -->
                <div id="question-container">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-secondary">Loading question...</p>
                    </div>
                </div>

                <!-- Footer Navigation Buttons -->
                <div class="d-flex justify-content-between pt-3">
                    <button class="btn btn-white border d-flex align-items-center gap-2 px-4 py-2 fw-medium hover-bg-body text-body" id="btn-prev" disabled>
                        <span class="material-symbols-outlined fs-5">arrow_back</span> Previous
                    </button>
                    <button class="btn btn-primary d-flex align-items-center gap-2 px-5 py-2 fw-bold shadow-lg hover-scale" id="btn-next">
                        Next <span class="material-symbols-outlined fs-5">arrow_forward</span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Sidebar: Question Navigator -->
        <aside class="offcanvas-lg offcanvas-end flex-column border-start bg-surface z-2 shadow-lg flex-shrink-0" tabindex="-1" id="questionNavigator" aria-labelledby="questionNavigatorLabel" style="width: 320px;">
            <div class="p-4 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-body mb-0" id="questionNavigatorLabel">Question Navigator</h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-body text-secondary border fw-medium px-2 py-1" id="nav-total-questions">0 Questions</span>
                    <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#questionNavigator" aria-label="Close"></button>
                </div>
            </div>

            <div class="flex-grow-1 overflow-auto p-4 custom-scrollbar">
                <div class="d-grid gap-2" style="grid-template-columns: repeat(5, 1fr);" id="navigator-grid">
                    <!-- Dynamic Grid -->
                </div>
            </div>

            <!-- Legend -->
            <div class="p-4 border-top bg-body bg-opacity-50">
                <div class="row g-2">
                    <div class="col-6 d-flex align-items-center gap-2">
                        <div class="bg-success rounded-circle" style="width: 10px; height: 10px;"></div>
                        <span class="small text-secondary">Answered</span>
                    </div>
                    <div class="col-6 d-flex align-items-center gap-2">
                        <div class="bg-primary rounded-circle" style="width: 10px; height: 10px;"></div>
                        <span class="small text-secondary">Current</span>
                    </div>
                    <div class="col-6 d-flex align-items-center gap-2">
                        <div class="bg-warning rounded-circle" style="width: 10px; height: 10px;"></div>
                        <span class="small text-secondary">Flagged</span>
                    </div>
                    <div class="col-6 d-flex align-items-center gap-2">
                        <div class="bg-secondary bg-opacity-25 rounded-circle" style="width: 10px; height: 10px;"></div>
                        <span class="small text-secondary">Not Visited</span>
                    </div>
                </div>
            </div>
        </aside>

    </main>

<?php
$scripts = '<script type="module" src="/js/pages/exam.js"></script>';
$content = ob_get_clean();
include __DIR__ . '/../layouts/exam.php';
?>
