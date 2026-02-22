<?php
$title = "CBT Admin Overview Dashboard";
$breadcrumb = "Dashboard";
ob_start();
?>
                    <!-- Welcome Section -->
                    <div>
                        <h2 class="h3 fw-bold text-body mb-1 tracking-tight">Overview</h2>
                        <p class="text-secondary mb-0">Here's what's happening in your school today.</p>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="row g-4">
                        <!-- Card 1 -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border shadow-sm rounded-4 h-100 bg-surface">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-soft text-primary" style="width: 48px; height: 48px;">
                                            <span class="material-symbols-outlined fs-4">assignment</span>
                                        </div>
                                        <span class="badge bg-success-subtle text-success d-flex align-items-center gap-1 rounded-pill px-2 py-1">
                                            <span class="material-symbols-outlined fs-6">trending_up</span> +5%
                                        </span>
                                    </div>
                                    <p class="text-secondary small fw-medium mb-1">Total Exams</p>
                                    <h3 class="fw-bold text-body mb-0">124</h3>
                                </div>
                            </div>
                        </div>
                        <!-- Card 2 -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border shadow-sm rounded-4 h-100 bg-surface">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center justify-content-center rounded-3 bg-purple-subtle text-purple" style="width: 48px; height: 48px; background-color: #f3e8ff; color: #9333ea;">
                                            <span class="material-symbols-outlined fs-4">groups</span>
                                        </div>
                                        <span class="badge bg-success-subtle text-success d-flex align-items-center gap-1 rounded-pill px-2 py-1">
                                            <span class="material-symbols-outlined fs-6">trending_up</span> +12%
                                        </span>
                                    </div>
                                    <p class="text-secondary small fw-medium mb-1">Active Students</p>
                                    <h3 class="fw-bold text-body mb-0">842</h3>
                                </div>
                            </div>
                        </div>
                        <!-- Card 3 -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border shadow-sm rounded-4 h-100 bg-surface">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center justify-content-center rounded-3 bg-warning-subtle text-warning-emphasis" style="width: 48px; height: 48px;">
                                            <span class="material-symbols-outlined fs-4">library_books</span>
                                        </div>
                                        <span class="badge bg-success-subtle text-success d-flex align-items-center gap-1 rounded-pill px-2 py-1">
                                            <span class="material-symbols-outlined fs-6">trending_up</span> +8%
                                        </span>
                                    </div>
                                    <p class="text-secondary small fw-medium mb-1">Questions Bank</p>
                                    <h3 class="fw-bold text-body mb-0">3,500+</h3>
                                </div>
                            </div>
                        </div>
                        <!-- Card 4 -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border shadow-sm rounded-4 h-100 bg-surface">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center justify-content-center rounded-3 bg-danger-subtle text-danger" style="width: 48px; height: 48px;">
                                            <span class="material-symbols-outlined fs-4">rate_review</span>
                                        </div>
                                        <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1">Needs Action</span>
                                    </div>
                                    <p class="text-secondary small fw-medium mb-1">Pending Grading</p>
                                    <h3 class="fw-bold text-body mb-0">18</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts & Recent Activity Split -->
                    <div class="row g-4">

                        <!-- Chart Section -->
                        <div class="col-lg-8">
                            <div class="card border shadow-sm rounded-4 h-100 bg-surface">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <h5 class="fw-bold text-body mb-1">Class Performance</h5>
                                            <p class="text-secondary small mb-0">Average scores by grade level this semester</p>
                                        </div>
                                        <div class="bg-body-secondary p-1 rounded-3 d-flex">
                                            <button class="btn btn-white btn-sm shadow-sm fw-medium text-body px-3 py-1">Term 1</button>
                                            <button class="btn btn-link btn-sm text-decoration-none text-secondary fw-medium px-3 py-1">Term 2</button>
                                        </div>
                                    </div>

                                    <!-- Chart Canvas -->
                                    <div class="h-100 pt-2" style="min-height: 240px;">
                                        <canvas id="performanceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity List -->
                        <div class="col-lg-4">
                            <div class="card border shadow-sm rounded-4 h-100 bg-surface">
                                <div class="card-body p-4 d-flex flex-column">
                                    <h5 class="fw-bold text-body mb-4">Recent Activity</h5>
                                    <div class="d-flex flex-column gap-4 flex-grow-1">
                                        <!-- Item 1 -->
                                        <div class="d-flex gap-3">
                                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-primary-soft text-primary" style="width: 40px; height: 40px;">
                                                <span class="material-symbols-outlined fs-5">task_alt</span>
                                            </div>
                                            <div class="d-flex flex-column lh-sm">
                                                <span class="fw-medium text-body small">Exam Submitted</span>
                                                <span class="text-secondary x-small">John Doe finished <span class="fw-bold">Math Mid-Term</span></span>
                                                <span class="text-secondary x-small opacity-75 mt-1">2 mins ago</span>
                                            </div>
                                        </div>
                                        <!-- Item 2 -->
                                        <div class="d-flex gap-3">
                                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success" style="width: 40px; height: 40px;">
                                                <span class="material-symbols-outlined fs-5">person_add</span>
                                            </div>
                                            <div class="d-flex flex-column lh-sm">
                                                <span class="fw-medium text-body small">New Registration</span>
                                                <span class="text-secondary x-small">Sarah Smith joined JSS2 class</span>
                                                <span class="text-secondary x-small opacity-75 mt-1">1 hour ago</span>
                                            </div>
                                        </div>
                                        <!-- Item 3 -->
                                        <div class="d-flex gap-3">
                                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning-emphasis" style="width: 40px; height: 40px;">
                                                <span class="material-symbols-outlined fs-5">report_problem</span>
                                            </div>
                                            <div class="d-flex flex-column lh-sm">
                                                <span class="fw-medium text-body small">Error Reported</span>
                                                <span class="text-secondary x-small">Question #42 flagged in Physics</span>
                                                <span class="text-secondary x-small opacity-75 mt-1">3 hours ago</span>
                                            </div>
                                        </div>
                                        <!-- Item 4 -->
                                        <div class="d-flex gap-3">
                                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-purple-subtle text-purple" style="width: 40px; height: 40px; color: #9333ea; background-color: #f3e8ff;">
                                                <span class="material-symbols-outlined fs-5">upload_file</span>
                                            </div>
                                            <div class="d-flex flex-column lh-sm">
                                                <span class="fw-medium text-body small">Bulk Upload</span>
                                                <span class="text-secondary x-small">50 questions added to English</span>
                                                <span class="text-secondary x-small opacity-75 mt-1">5 hours ago</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn btn-white border w-100 fw-medium text-secondary text-sm mt-4 hover-bg-light">View All Activity</button>
                                </div>
                            </div>
                        </div>
                    </div>
<?php
$scripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="module" src="/js/pages/admin-dashboard.js"></script>
';
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
