<?php
$title = "Teacher Question Repository";
$breadcrumb = "Questions";
ob_start();
?>
                <!-- Page Title & Primary Action -->
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                    <div>
                        <h1 class="h2 fw-bold text-body mb-1 tracking-tight">Question Repository</h1>
                        <p class="text-secondary mb-0">Manage, filter, and organize your assessment questions.</p>
                    </div>
                    <button class="btn btn-primary fw-medium d-flex align-items-center gap-2 shadow-sm px-4 py-2 hover-scale">
                        <span class="material-symbols-outlined fs-5">add</span> Create Question
                    </button>
                </div>

                <!-- Filters & Search Toolbar -->
                <div class="card border rounded-3 shadow-sm p-3">
                    <div class="d-flex flex-column flex-lg-row align-items-center gap-3">
                        <!-- Search -->
                        <div class="position-relative w-100 w-lg-auto" style="min-width: 320px;">
                            <span class="material-symbols-outlined position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary fs-5">search</span>
                            <input type="text" class="form-control bg-body-secondary ps-5" placeholder="Search questions by text or ID...">
                        </div>

                        <!-- Dropdowns -->
                        <div class="d-flex flex-wrap gap-2 w-100 w-lg-auto">
                            <select class="form-select bg-body text-secondary small" style="width: auto; min-width: 140px;">
                                <option selected>All Subjects</option>
                                <option value="math">Mathematics</option>
                                <option value="science">Physics</option>
                                <option value="history">History</option>
                            </select>
                            <select class="form-select bg-body text-secondary small" style="width: auto; min-width: 140px;">
                                <option selected>All Classes</option>
                                <option value="10">Grade 10</option>
                                <option value="11">Grade 11</option>
                                <option value="12">Grade 12</option>
                            </select>
                            <select class="form-select bg-body text-secondary small" style="width: auto; min-width: 140px;">
                                <option selected>Difficulty</option>
                                <option value="easy">Easy</option>
                                <option value="medium">Medium</option>
                                <option value="hard">Hard</option>
                            </select>
                            <button class="btn btn-link text-decoration-none text-secondary fw-medium small">Clear</button>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="card border rounded-3 shadow-sm overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-body border-bottom">
                                <tr>
                                    <th class="px-4 py-3" style="width: 48px;">
                                        <input class="form-check-input" type="checkbox">
                                    </th>
                                    <th class="px-4 py-3 small fw-bold text-secondary text-uppercase" style="min-width: 300px;">Question Text</th>
                                    <th class="px-4 py-3 small fw-bold text-secondary text-uppercase">Type</th>
                                    <th class="px-4 py-3 small fw-bold text-secondary text-uppercase">Subject</th>
                                    <th class="px-4 py-3 small fw-bold text-secondary text-uppercase">Difficulty</th>
                                    <th class="px-4 py-3 small fw-bold text-secondary text-uppercase">Status</th>
                                    <th class="px-4 py-3 small fw-bold text-secondary text-uppercase text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <!-- Row 1 -->
                                <tr>
                                    <td class="px-4 py-3"><input class="form-check-input" type="checkbox"></td>
                                    <td class="px-4 py-3">
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium text-body text-truncate" style="max-width: 300px;">Calculate the velocity of an object in free fall...</span>
                                            <span class="text-secondary x-small">ID: Q-8832 • Grade 11</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1 fw-medium px-2 py-1">
                                            <span class="material-symbols-outlined fs-6">calculate</span> Problem
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-body">Physics</td>
                                    <td class="px-4 py-3">
                                        <span class="d-inline-flex align-items-center gap-1 text-warning-emphasis fw-medium small">
                                            <span class="rounded-circle bg-warning" style="width: 6px; height: 6px;"></span> Medium
                                        </span>
                                    </td>
                                    <td class="px-4 py-3"><span class="badge bg-success-subtle text-success rounded-pill fw-medium px-2">Active</span></td>
                                    <td class="px-4 py-3 text-end">
                                        <button class="btn btn-link btn-sm text-secondary hover-bg-body rounded p-1"><span class="material-symbols-outlined fs-5">more_vert</span></button>
                                    </td>
                                </tr>
                                <!-- Row 2 -->
                                <tr>
                                    <td class="px-4 py-3"><input class="form-check-input" type="checkbox"></td>
                                    <td class="px-4 py-3">
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium text-body text-truncate" style="max-width: 300px;">Explain the significance of the Treaty of Versailles...</span>
                                            <span class="text-secondary x-small">ID: Q-9102 • Grade 10</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge bg-purple-subtle text-purple border border-purple-subtle d-inline-flex align-items-center gap-1 fw-medium px-2 py-1" style="background-color: #f3e8ff; color: #7e22ce; border-color: #e9d5ff;">
                                            <span class="material-symbols-outlined fs-6">edit_note</span> Essay
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-body">History</td>
                                    <td class="px-4 py-3">
                                        <span class="d-inline-flex align-items-center gap-1 text-danger fw-medium small">
                                            <span class="rounded-circle bg-danger" style="width: 6px; height: 6px;"></span> Hard
                                        </span>
                                    </td>
                                    <td class="px-4 py-3"><span class="badge bg-secondary-subtle text-secondary rounded-pill fw-medium px-2">Draft</span></td>
                                    <td class="px-4 py-3 text-end">
                                        <button class="btn btn-link btn-sm text-secondary hover-bg-body rounded p-1"><span class="material-symbols-outlined fs-5">more_vert</span></button>
                                    </td>
                                </tr>
                                <!-- Row 3 -->
                                <tr>
                                    <td class="px-4 py-3"><input class="form-check-input" type="checkbox"></td>
                                    <td class="px-4 py-3">
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium text-body text-truncate" style="max-width: 300px;">What is the value of Pi to 2 decimal places?</span>
                                            <span class="text-secondary x-small">ID: Q-2219 • Grade 9</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1 fw-medium px-2 py-1">
                                            <span class="material-symbols-outlined fs-6">check_circle</span> MCQ
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-body">Math</td>
                                    <td class="px-4 py-3">
                                        <span class="d-inline-flex align-items-center gap-1 text-success fw-medium small">
                                            <span class="rounded-circle bg-success" style="width: 6px; height: 6px;"></span> Easy
                                        </span>
                                    </td>
                                    <td class="px-4 py-3"><span class="badge bg-success-subtle text-success rounded-pill fw-medium px-2">Active</span></td>
                                    <td class="px-4 py-3 text-end">
                                        <button class="btn btn-link btn-sm text-secondary hover-bg-body rounded p-1"><span class="material-symbols-outlined fs-5">more_vert</span></button>
                                    </td>
                                </tr>
                                <!-- Row 4 -->
                                <tr>
                                    <td class="px-4 py-3"><input class="form-check-input" type="checkbox"></td>
                                    <td class="px-4 py-3">
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium text-body text-truncate" style="max-width: 300px;">Identify the correct chemical formula for Glucose.</span>
                                            <span class="text-secondary x-small">ID: Q-4401 • Grade 11</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1 fw-medium px-2 py-1">
                                            <span class="material-symbols-outlined fs-6">check_circle</span> MCQ
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-body">Chemistry</td>
                                    <td class="px-4 py-3">
                                        <span class="d-inline-flex align-items-center gap-1 text-warning-emphasis fw-medium small">
                                            <span class="rounded-circle bg-warning" style="width: 6px; height: 6px;"></span> Medium
                                        </span>
                                    </td>
                                    <td class="px-4 py-3"><span class="badge bg-warning-subtle text-warning-emphasis rounded-pill fw-medium px-2">Review</span></td>
                                    <td class="px-4 py-3 text-end">
                                        <button class="btn btn-link btn-sm text-secondary hover-bg-body rounded p-1"><span class="material-symbols-outlined fs-5">more_vert</span></button>
                                    </td>
                                </tr>
                                <!-- Row 5 -->
                                <tr>
                                    <td class="px-4 py-3"><input class="form-check-input" type="checkbox"></td>
                                    <td class="px-4 py-3">
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium text-body text-truncate" style="max-width: 300px;">True or False: The Amazon River is the longest river.</span>
                                            <span class="text-secondary x-small">ID: Q-5592 • Grade 9</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle d-inline-flex align-items-center gap-1 fw-medium px-2 py-1">
                                            <span class="material-symbols-outlined fs-6">toggle_on</span> Boolean
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-body">Geography</td>
                                    <td class="px-4 py-3">
                                        <span class="d-inline-flex align-items-center gap-1 text-success fw-medium small">
                                            <span class="rounded-circle bg-success" style="width: 6px; height: 6px;"></span> Easy
                                        </span>
                                    </td>
                                    <td class="px-4 py-3"><span class="badge bg-danger-subtle text-danger rounded-pill fw-medium px-2">Archived</span></td>
                                    <td class="px-4 py-3 text-end">
                                        <button class="btn btn-link btn-sm text-secondary hover-bg-body rounded p-1"><span class="material-symbols-outlined fs-5">more_vert</span></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-surface border-top px-4 py-3 d-flex align-items-center justify-content-between">
                        <small class="text-secondary">Showing <span class="fw-medium text-body">1</span> to <span class="fw-medium text-body">5</span> of <span class="fw-medium text-body">128</span> results</small>
                        <div class="btn-group">
                            <button class="btn btn-white border btn-sm text-secondary disabled"><span class="material-symbols-outlined fs-6">chevron_left</span></button>
                            <button class="btn btn-white border btn-sm text-secondary"><span class="material-symbols-outlined fs-6">chevron_right</span></button>
                        </div>
                    </div>
                </div>
<?php
$scripts = '<script type="module" src="/js/pages/question-manager.js"></script>';
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
?>
