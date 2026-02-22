<?php
$title = "Sign In";
ob_start();
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="row g-0">
                    <div class="col-lg-6 p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-2">CBT Platform Login</h1>
                        <p class="text-secondary mb-4">Sign in using your password or SMS verification.</p>

                        <form class="d-grid gap-3" aria-label="Login form">
                            <div>
                                <label for="identity" class="form-label fw-semibold">Email or Username</label>
                                <input id="identity" type="text" class="form-control form-control-lg" placeholder="student@school.edu" aria-describedby="login-help">
                            </div>

                            <div>
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <input id="password" type="password" class="form-control form-control-lg" placeholder="Enter password">
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="rememberMe">
                                    <label class="form-check-label" for="rememberMe">Remember me</label>
                                </div>
                                <a href="#" class="small text-decoration-none" aria-label="Reset password">Forgot password?</a>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg">Sign In</button>

                            <div class="text-center text-secondary small">or</div>

                            <button type="button" class="btn btn-outline-primary btn-lg">Sign in with SMS Verification</button>

                            <div class="alert alert-info py-2 mb-0" id="login-help" role="status">
                                <strong>Signed in via SMS Verification</strong> will appear after successful OAuth.
                            </div>
                        </form>
                    </div>

                    <div class="col-lg-6 bg-light p-4 p-md-5 border-start">
                        <h2 class="h5 fw-bold">Identity Context Preview</h2>
                        <p class="text-secondary small mb-3">Auto-populated after SMS authentication.</p>
                        <ul class="list-group list-group-flush border rounded-3">
                            <li class="list-group-item d-flex justify-content-between"><span>Name</span><strong>—</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Class</span><strong>—</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Term</span><strong>—</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Session</span><strong>—</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Role</span><strong>—</strong></li>
                        </ul>
                        <p class="small text-secondary mt-3 mb-0">If password reset is disabled by admin, users are instructed to contact support.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/auth.php';
?>
