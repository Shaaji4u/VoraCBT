<?php
$title = "Student Exam Selection Portal";
$pageTitle = "Exam Portal";
$pageSubtitle = "Manage your upcoming assessments";
ob_start();
?>
                <!-- Welcome/Hero Section -->
                <div>
                    <h2 class="fw-bold text-body mb-1">Exam Selection</h2>
                    <p class="text-secondary">Select an exam to start or resume your progress. Good luck!</p>
                </div>

                <!-- Section: In Progress (Priority) -->
                <section>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold text-body d-flex align-items-center gap-2">
                            <span class="material-symbols-outlined text-primary">pending_actions</span>
                            In Progress Exams
                        </h5>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card border border shadow-sm rounded-4 overflow-hidden hover-shadow-md transition-all h-100">
                                <div class="row g-0 h-100">
                                    <div class="col-sm-4 bg-cover bg-center position-relative min-vh-25 min-vh-sm-0" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuCTDz21w5oGJW4JQR6eRmw8ps8KmBq5D6-3JZg7WtMPTxs1gRcWtujV2eyet64WwfPxCsEar802noPPuHhMPxl2AQ-T6sNi4RKeuRxFLjZkS4PXwfUkCVjnXUbNMJdgSc8iWdNGl2XsqFV2em6Jjg86LezjL7s_nIluFrY3gSPa_1geVNVAVNEva5WD1DfMdplvK5zeFeYU2hH2VuvXjnzJWmzsJSdxZSBIyEwjTAQBlIRMf54t6RygduVfn4ta3bPCWAERKSO1r1Q');">
                                        <div class="position-absolute top-0 start-0 m-2">
                                            <span class="badge bg-surface bg-opacity-90 text-primary backdrop-blur-md small">Mathematics</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-8 p-4 d-flex flex-column justify-content-between">
                                        <div>
                                            <h5 class="fw-bold text-body mb-1">Advanced Calculus Mid-Term</h5>
                                            <p class="text-secondary small mb-3">Section B - Prof. Alan Turing</p>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-3 text-secondary small mb-3">
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="material-symbols-outlined fs-6">schedule</span> 45m remaining
                                                </div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="material-symbols-outlined fs-6">calendar_today</span> Today
                                                </div>
                                            </div>
                                            <a href="/student/exam" class="btn btn-primary btn-sm fw-medium d-flex align-items-center gap-2 px-3">
                                                <span class="material-symbols-outlined fs-5">play_arrow</span> Resume Exam
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section: Upcoming Exams -->
                <section>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold text-body d-flex align-items-center gap-2">
                            <span class="material-symbols-outlined text-secondary">event_upcoming</span>
                            Upcoming Exams
                        </h5>
                        <select class="form-select form-select-sm w-auto bg-surface border text-secondary fw-medium">
                            <option>Sort by Date</option>
                            <option>Sort by Subject</option>
                        </select>
                    </div>

                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
                        <!-- Exam Card 1 -->
                        <div class="col">
                            <div class="card border border shadow-sm rounded-4 h-100 overflow-hidden hover-border-primary transition-all group">
                                <div class="ratio ratio-16x9 bg-cover bg-center position-relative" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuC0VezTRbjhmEZKce0l6XBbgY6VRoHpaBpTgchXG_3Am239WzCC-NFCuPjYdjpvHVsyg0zAmjQ34svNsjChCYEEsRaGrwGvuapgxtJbc6hjttDoKe2d4giPfNvUAoJKfujK-ky2vBIhoGUj3s1otKyF9yM7MhEbbugFRuGIu1_ql2MeD0zDknw2udgRCqhCkdAcizL7w-uuTKYxsZYgu13l2OFYKVtvBWx75p65FIa-4dM3Ka1hcaXs4aveaDuf3FxCh3nc-DR5iBk');">
                                    <div class="position-absolute inset-0 bg-gradient-to-t-black opacity-50"></div>
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Physics</span>
                                    </div>
                                    <div class="position-absolute bottom-0 end-0 m-2">
                                        <span class="badge bg-dark bg-opacity-75 backdrop-blur-md">60 Mins</span>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-body mb-2 group-hover-text-primary transition-colors">Thermodynamics Final</h6>
                                        <div class="d-flex align-items-center gap-2 small text-secondary">
                                            <span class="material-symbols-outlined fs-6">calendar_month</span> Oct 24, 10:00 AM
                                        </div>
                                    </div>
                                    <div class="mt-auto pt-3 border-top">
                                        <button class="btn btn-outline-secondary btn-sm w-100 fw-medium hover-bg-primary hover-text-white hover-border-primary d-flex align-items-center justify-content-center gap-2 group-hover-bg-primary group-hover-text-white group-hover-border-primary transition-all">
                                            Start Exam
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Exam Card 2 -->
                        <div class="col">
                            <div class="card border border shadow-sm rounded-4 h-100 overflow-hidden hover-border-primary transition-all group">
                                <div class="ratio ratio-16x9 bg-cover bg-center position-relative" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuBOd3EgX6w_52hrWMZVTzDBsUD2qLqt039Acw_WOaxjLpBW5IIwrMApPylA7EoK5ASSK0Uz1_wHYj44OJhlFkwcignWV6cXt7mUBBeQ_4F5Ss2kBU3f-zjqXGAalvdaOChRgciNRm8jOPyU4ElNtRJSVwlKXkJls4f9NWNuFAMdxNha3cHK_aIEyy0JOyKKLE5jD7nEt0u_Ky-zZ9sMmxegAg_BwUeAl3MqBRQIG_u52wE0Qf4d02exxDAUULAsO0CjtXoSWt6SOvo');">
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-purple-subtle text-purple border border-purple-subtle" style="background-color: #e9d5ff; color: #7e22ce;">Computer Science</span>
                                    </div>
                                    <div class="position-absolute bottom-0 end-0 m-2">
                                        <span class="badge bg-dark bg-opacity-75 backdrop-blur-md">90 Mins</span>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-body mb-2 group-hover-text-primary transition-colors">Data Structures Quiz</h6>
                                        <div class="d-flex align-items-center gap-2 small text-secondary">
                                            <span class="material-symbols-outlined fs-6">calendar_month</span> Oct 25, 02:00 PM
                                        </div>
                                    </div>
                                    <div class="mt-auto pt-3 border-top">
                                        <button class="btn btn-light border btn-sm w-100 fw-medium text-secondary disabled d-flex align-items-center justify-content-center gap-2 cursor-not-allowed">
                                            <span class="material-symbols-outlined fs-6">lock</span> Locked
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Exam Card 3 -->
                        <div class="col">
                            <div class="card border border shadow-sm rounded-4 h-100 overflow-hidden hover-border-primary transition-all group">
                                <div class="ratio ratio-16x9 bg-cover bg-center position-relative" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuDatJYX96XYG_FeEtqlJzDQpiEX3iin5TLGdh-ik_g3fYw8Ie4sjW0UD87DPwtTtyvtyHxwFPjGvpxoXjleWFdjnhs5J_algVMHPpngb_zh_nGNgofuyoTPZALi1z2F1IfUHyOAeMhEWF8qjq96xEF55O-IS8ehx2K28yn6QBDcd1RQmzHPBykgGD4WxESIdyMGb_kivI9pUyOHs0qh0PQPdmdzMFjWaQ36nCUIxyanHJZV3R-mwC3KTRd6DczNNedn0d7Z6a7u5aQ');">
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">History</span>
                                    </div>
                                    <div class="position-absolute bottom-0 end-0 m-2">
                                        <span class="badge bg-dark bg-opacity-75 backdrop-blur-md">45 Mins</span>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-body mb-2 group-hover-text-primary transition-colors">World War II Overview</h6>
                                        <div class="d-flex align-items-center gap-2 small text-secondary">
                                            <span class="material-symbols-outlined fs-6">calendar_month</span> Oct 26, 09:30 AM
                                        </div>
                                    </div>
                                    <div class="mt-auto pt-3 border-top">
                                        <button class="btn btn-light border btn-sm w-100 fw-medium text-secondary disabled d-flex align-items-center justify-content-center gap-2 cursor-not-allowed">
                                            <span class="material-symbols-outlined fs-6">lock</span> Locked
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Exam Card 4 -->
                        <div class="col">
                            <div class="card border border shadow-sm rounded-4 h-100 overflow-hidden hover-border-primary transition-all group">
                                <div class="ratio ratio-16x9 bg-cover bg-center position-relative" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuA6Uqijud0MQTwFC4AltDPARag6G1uhWI4sgdDVqJ82ckX5bSOyFxjmiSlm7zSAtJiNdg1HSI2b3c2SudREPzSVuG3Fqr01q9vXTR0beP8lFsuYjoLKto9aSmNwJKHhwcBHIZLtxYdInBzBrfHMKAgkHyUJc4lByfcipon7Xd7xQYQ8kP-ShlecEEbrweGZsKHzpuO3GTXhfzPWE4iCH_QgXIARXrNVWrsnhOox-Xay00E36-M4ulgsisPfXyRNmK0pqHU6BxZCmcg');">
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">English Lit</span>
                                    </div>
                                    <div class="position-absolute bottom-0 end-0 m-2">
                                        <span class="badge bg-dark bg-opacity-75 backdrop-blur-md">120 Mins</span>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-body mb-2 group-hover-text-primary transition-colors">Shakespearean Analysis</h6>
                                        <div class="d-flex align-items-center gap-2 small text-secondary">
                                            <span class="material-symbols-outlined fs-6">calendar_month</span> Oct 28, 11:00 AM
                                        </div>
                                    </div>
                                    <div class="mt-auto pt-3 border-top">
                                        <button class="btn btn-light border btn-sm w-100 fw-medium text-secondary disabled d-flex align-items-center justify-content-center gap-2 cursor-not-allowed">
                                            <span class="material-symbols-outlined fs-6">lock</span> Locked
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- History Section -->
                <section class="opacity-75 hover-opacity-100 transition-opacity">
                    <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
                        <h5 class="fw-bold text-body d-flex align-items-center gap-2">
                            <span class="material-symbols-outlined text-secondary">history</span>
                            Past Exams (Last 30 days)
                        </h5>
                        <a href="#" class="small text-primary text-decoration-none fw-medium hover-underline">View All</a>
                    </div>
                    <div class="card border border rounded-4 overflow-hidden shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="bg-body">
                                    <tr>
                                        <th class="px-4 py-3 small fw-bold text-secondary text-uppercase">Subject</th>
                                        <th class="px-4 py-3 small fw-bold text-secondary text-uppercase">Title</th>
                                        <th class="px-4 py-3 small fw-bold text-secondary text-uppercase">Date Taken</th>
                                        <th class="px-4 py-3 small fw-bold text-secondary text-uppercase">Score</th>
                                        <th class="px-4 py-3 small fw-bold text-secondary text-uppercase text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="px-4 py-3 fw-medium text-body">Chemistry</td>
                                        <td class="px-4 py-3">Periodic Table Quiz</td>
                                        <td class="px-4 py-3 text-secondary">Oct 12, 2023</td>
                                        <td class="px-4 py-3"><span class="badge bg-success-subtle text-success">85%</span></td>
                                        <td class="px-4 py-3 text-end"><button class="btn btn-link btn-sm text-primary p-0 fw-medium text-decoration-none">View Results</button></td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 fw-medium text-body">Biology</td>
                                        <td class="px-4 py-3">Cell Structure</td>
                                        <td class="px-4 py-3 text-secondary">Oct 05, 2023</td>
                                        <td class="px-4 py-3"><span class="badge bg-warning-subtle text-warning-emphasis">72%</span></td>
                                        <td class="px-4 py-3 text-end"><button class="btn btn-link btn-sm text-primary p-0 fw-medium text-decoration-none">View Results</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/student.php';
?>
