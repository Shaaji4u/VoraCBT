<header class="d-flex align-items-center justify-content-between px-4 py-3 bg-surface border-bottom flex-shrink-0" style="height: 80px;">
    <div class="d-flex flex-column">
        <h5 class="fw-bold text-body mb-0"><?= $pageTitle ?? 'Exam Portal' ?></h5>
        <small class="text-secondary"><?= $pageSubtitle ?? 'Manage your upcoming assessments' ?></small>
    </div>
    <div class="d-flex align-items-center gap-3">
        <!-- Search -->
        <div class="position-relative d-none d-md-block">
            <span class="material-symbols-outlined position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary fs-5">search</span>
            <input type="text" class="form-control bg-body-secondary border-0 ps-5" placeholder="Search exams..." style="width: 260px;">
        </div>
        <!-- Notifications -->
        <button class="btn btn-light btn-sm position-relative text-secondary p-2">
            <span class="material-symbols-outlined fs-4">notifications</span>
            <span class="position-absolute top-0 end-0 m-2 p-1 bg-danger border border-light rounded-circle"></span>
        </button>
    </div>
</header>
