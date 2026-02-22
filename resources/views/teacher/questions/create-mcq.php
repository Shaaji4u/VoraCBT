<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Question MCQ Form</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/tokens.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
</head>
<body class="bg-body text-body font-display d-flex flex-column min-vh-100">

    <!-- Top Navigation Bar -->
    <header class="bg-surface border-bottom sticky-top z-3">
        <div class="container-xxl px-4">
            <div class="d-flex align-items-center justify-content-between py-2" style="height: 64px;">
                <!-- Logo & Brand -->
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded bg-primary-soft text-primary" style="width: 32px; height: 32px;">
                        <span class="material-symbols-outlined fs-5">school</span>
                    </div>
                    <h6 class="mb-0 fw-bold tracking-tight text-body">CBT Enterprise</h6>
                </div>

                <!-- Nav Links -->
                <nav class="d-none d-md-flex align-items-center gap-4">
                    <a href="#" class="nav-link text-secondary small fw-medium">Dashboard</a>
                    <a href="#" class="nav-link active text-primary small fw-bold">Question Bank</a>
                    <a href="#" class="nav-link text-secondary small fw-medium">Tests</a>
                    <a href="#" class="nav-link text-secondary small fw-medium">Reports</a>
                    <a href="#" class="nav-link text-secondary small fw-medium">Students</a>
                </nav>

                <!-- Profile -->
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-link text-secondary hover-text-primary p-0">
                        <span class="material-symbols-outlined fs-4">notifications</span>
                    </button>
                    <div class="avatar-circle border" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuAeVYR8JPZ6vjYgjlT9XiSVXdh2Y_rxYEHmseiwe3DBtQyUnMBlW9uwQAUCfEB8YvApnarhKVOpMRTV3VJf1oe8rQoI8gLcioVx-i8EfRlvpvSJMaCuQc3z9vlJ8upK2MV3niEIj3hgfYu43ArSgohTpzIxolbg8v-_cfIB8qByPY57A2_JBzHA07IFEbcAYkLh8PS5fa-H7Nu2FGayqeyFXslfSyeZGZbaUKgDdDOMHmNciluDrqOqeQJfpNIyu2yTvGd_64JQJKA');"></div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow-1 w-100 py-4 py-lg-5">
        <div class="container-xxl px-4">

            <!-- Breadcrumbs & Header -->
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-5">
                <div>
                    <nav class="d-flex align-items-center gap-2 small text-secondary mb-2">
                        <a href="#" class="text-decoration-none text-secondary hover-text-primary">Question Bank</a>
                        <span class="material-symbols-outlined fs-6">chevron_right</span>
                        <span class="fw-medium text-body">New Question</span>
                    </nav>
                    <h2 class="h3 fw-bold text-body tracking-tight mb-0">Create Multiple Choice Question</h2>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-white border shadow-sm btn-sm fw-bold text-body hover-bg-body">
                        Cancel
                    </button>
                    <button class="btn btn-primary btn-sm fw-bold shadow-sm d-flex align-items-center gap-2 px-3">
                        <span class="material-symbols-outlined fs-5">save</span>
                        Save & Close
                    </button>
                </div>
            </div>

            <div class="row g-4 align-items-start">

                <!-- Left Column: Question Editor -->
                <div class="col-lg-8 d-flex flex-column gap-4">

                    <!-- Question Stem Card -->
                    <div class="card border rounded-3 shadow-sm overflow-hidden">
                        <div class="card-header bg-body bg-opacity-50 border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-body mb-0">Question Stem</h6>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fw-medium">Rich Text Supported</span>
                        </div>
                        <div class="card-body p-4">
                            <!-- Toolbar Placeholder -->
                            <div class="d-flex flex-wrap align-items-center gap-1 mb-3 pb-2 border-bottom">
                                <button class="btn btn-sm btn-light text-secondary border-0 p-1 lh-1"><span class="material-symbols-outlined fs-5">format_bold</span></button>
                                <button class="btn btn-sm btn-light text-secondary border-0 p-1 lh-1"><span class="material-symbols-outlined fs-5">format_italic</span></button>
                                <button class="btn btn-sm btn-light text-secondary border-0 p-1 lh-1"><span class="material-symbols-outlined fs-5">format_underlined</span></button>
                                <div class="vr mx-2 h-50 align-self-center"></div>
                                <button class="btn btn-sm btn-light text-secondary border-0 p-1 lh-1"><span class="material-symbols-outlined fs-5">format_list_bulleted</span></button>
                                <button class="btn btn-sm btn-light text-secondary border-0 p-1 lh-1"><span class="material-symbols-outlined fs-5">format_list_numbered</span></button>
                                <div class="vr mx-2 h-50 align-self-center"></div>
                                <button class="btn btn-sm btn-light text-secondary border-0 p-1 lh-1"><span class="material-symbols-outlined fs-5">image</span></button>
                                <button class="btn btn-sm btn-light text-secondary border-0 p-1 lh-1"><span class="material-symbols-outlined fs-5">functions</span></button>
                            </div>
                            <!-- Text Area -->
                            <textarea class="form-control border-0 shadow-none p-0 fs-6 text-body" rows="6" placeholder="Type your question content here..."></textarea>
                        </div>
                    </div>

                    <!-- Answer Options Card -->
                    <div class="card border rounded-3 shadow-sm">
                        <div class="card-header bg-body bg-opacity-50 border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-body mb-0">Answer Options</h6>
                            <div class="d-flex align-items-center gap-1 small text-secondary">
                                <span class="material-symbols-outlined fs-6 text-primary">info</span>
                                Select the radio button to mark the correct answer.
                            </div>
                        </div>
                        <div class="card-body p-4 d-flex flex-column gap-3">

                            <!-- Option A -->
                            <div class="d-flex align-items-start gap-3 p-3 rounded-2 border border-transparent hover-bg-body hover-border-secondary-subtle transition-all group">
                                <div class="pt-2 d-flex align-items-center justify-content-center">
                                    <input class="form-check-input mt-0 cursor-pointer" type="radio" name="correct_answer" style="width: 1.25em; height: 1.25em;">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="small fw-bold text-secondary text-uppercase tracking-wider">Option A</label>
                                        <button class="btn btn-link text-danger p-0 d-flex align-items-center opacity-0 group-hover-opacity-100 transition-opacity" title="Remove Option">
                                            <span class="material-symbols-outlined fs-5">delete</span>
                                        </button>
                                    </div>
                                    <input type="text" class="form-control form-control-sm" value="Photosynthesis">
                                </div>
                            </div>

                            <!-- Option B -->
                            <div class="d-flex align-items-start gap-3 p-3 rounded-2 border border-primary border-opacity-25 bg-primary-soft position-relative">
                                <div class="position-absolute start-0 top-0 bottom-0 bg-primary rounded-start-2" style="width: 4px;"></div>
                                <div class="pt-2 d-flex align-items-center justify-content-center">
                                    <input class="form-check-input mt-0 cursor-pointer" type="radio" name="correct_answer" checked style="width: 1.25em; height: 1.25em;">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="small fw-bold text-primary text-uppercase tracking-wider">Option B (Correct)</label>
                                        <button class="btn btn-link text-danger p-0 d-flex align-items-center opacity-0 group-hover-opacity-100 transition-opacity" title="Remove Option">
                                            <span class="material-symbols-outlined fs-5">delete</span>
                                        </button>
                                    </div>
                                    <input type="text" class="form-control form-control-sm border-primary focus-border-primary fw-medium" value="Cellular Respiration">
                                </div>
                            </div>

                            <!-- Option C -->
                            <div class="d-flex align-items-start gap-3 p-3 rounded-2 border border-transparent hover-bg-body hover-border-secondary-subtle transition-all group">
                                <div class="pt-2 d-flex align-items-center justify-content-center">
                                    <input class="form-check-input mt-0 cursor-pointer" type="radio" name="correct_answer" style="width: 1.25em; height: 1.25em;">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="small fw-bold text-secondary text-uppercase tracking-wider">Option C</label>
                                        <button class="btn btn-link text-danger p-0 d-flex align-items-center opacity-0 group-hover-opacity-100 transition-opacity" title="Remove Option">
                                            <span class="material-symbols-outlined fs-5">delete</span>
                                        </button>
                                    </div>
                                    <input type="text" class="form-control form-control-sm" value="Mitosis">
                                </div>
                            </div>

                            <!-- Option D -->
                            <div class="d-flex align-items-start gap-3 p-3 rounded-2 border border-transparent hover-bg-body hover-border-secondary-subtle transition-all group">
                                <div class="pt-2 d-flex align-items-center justify-content-center">
                                    <input class="form-check-input mt-0 cursor-pointer" type="radio" name="correct_answer" style="width: 1.25em; height: 1.25em;">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="small fw-bold text-secondary text-uppercase tracking-wider">Option D</label>
                                        <button class="btn btn-link text-danger p-0 d-flex align-items-center opacity-0 group-hover-opacity-100 transition-opacity" title="Remove Option">
                                            <span class="material-symbols-outlined fs-5">delete</span>
                                        </button>
                                    </div>
                                    <input type="text" class="form-control form-control-sm" placeholder="Enter option text...">
                                </div>
                            </div>

                            <button class="btn btn-outline-secondary border-2 border-dashed w-100 py-2 fw-medium hover-border-primary hover-text-primary d-flex align-items-center justify-content-center gap-2 mt-2">
                                <span class="material-symbols-outlined">add_circle</span>
                                Add Another Option
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Sidebar (Settings) -->
                <aside class="col-lg-4 d-flex flex-column gap-4">

                    <!-- Question Properties -->
                    <div class="card border rounded-3 shadow-sm">
                        <div class="card-header bg-body bg-opacity-50 border-bottom px-4 py-3">
                            <h6 class="fw-bold text-body mb-0">Properties</h6>
                        </div>
                        <div class="card-body p-4 d-flex flex-column gap-3">
                            <!-- Subject -->
                            <div>
                                <label class="form-label small fw-bold text-body">Subject</label>
                                <select class="form-select form-select-sm">
                                    <option>Biology</option>
                                    <option>Physics</option>
                                    <option>Mathematics</option>
                                    <option>History</option>
                                </select>
                            </div>
                            <!-- Topic -->
                            <div>
                                <label class="form-label small fw-bold text-body">Topic / Chapter</label>
                                <select class="form-select form-select-sm">
                                    <option>Cell Structure</option>
                                    <option>Genetics</option>
                                    <option>Ecology</option>
                                </select>
                            </div>
                            <!-- Difficulty -->
                            <div>
                                <label class="form-label small fw-bold text-body">Difficulty Level</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="difficulty" id="diff1" autocomplete="off">
                                    <label class="btn btn-outline-secondary btn-sm fw-medium" for="diff1">Easy</label>

                                    <input type="radio" class="btn-check" name="difficulty" id="diff2" autocomplete="off" checked>
                                    <label class="btn btn-outline-primary btn-sm fw-medium bg-primary-soft text-primary border-primary" for="diff2">Medium</label>

                                    <input type="radio" class="btn-check" name="difficulty" id="diff3" autocomplete="off">
                                    <label class="btn btn-outline-secondary btn-sm fw-medium" for="diff3">Hard</label>
                                </div>
                            </div>
                            <!-- Scoring -->
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-body">Marks</label>
                                    <input type="number" class="form-control form-control-sm" value="1.0" step="0.5">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-body">Negative</label>
                                    <input type="number" class="form-control form-control-sm text-danger" value="0.25" step="0.25">
                                </div>
                            </div>
                            <!-- Tags -->
                            <div>
                                <label class="form-label small fw-bold text-body">Tags</label>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge bg-body text-secondary border fw-normal d-flex align-items-center gap-1">
                                        #Grade10 <span class="material-symbols-outlined fs-6 cursor-pointer hover-text-danger">close</span>
                                    </span>
                                    <span class="badge bg-body text-secondary border fw-normal d-flex align-items-center gap-1">
                                        #MidTerm <span class="material-symbols-outlined fs-6 cursor-pointer hover-text-danger">close</span>
                                    </span>
                                </div>
                                <input type="text" class="form-control form-control-sm" placeholder="Add tag...">
                            </div>
                        </div>
                    </div>

                    <!-- Explanation / Solution -->
                    <div class="card border rounded-3 shadow-sm flex-grow-1">
                        <div class="card-header bg-body bg-opacity-50 border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-body mb-0">Explanation</h6>
                            <span class="material-symbols-outlined fs-5 text-secondary" title="Shown to students after test completion" data-bs-toggle="tooltip">help</span>
                        </div>
                        <div class="card-body p-4">
                            <textarea class="form-control form-control-sm mb-2" rows="5" placeholder="Explain why the answer is correct..."></textarea>
                            <button class="btn btn-link btn-sm text-decoration-none text-primary fw-bold p-0 d-flex align-items-center gap-1">
                                <span class="material-symbols-outlined fs-5">add_photo_alternate</span>
                                Add Image to Solution
                            </button>
                        </div>
                    </div>

                </aside>
            </div>
        </div>
    </main>

    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tooltip Initialization
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>
