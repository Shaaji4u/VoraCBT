<?php
$title = "Sign In - CBT Enterprise";
ob_start();
?>
<main
    class="auth-wrapper min-vh-100 d-flex flex-column align-items-center justify-content-center p-3 p-md-4 py-5 py-md-5">
    <!-- Abstract Clean Background -->
    <div class="auth-bg-elements">
        <div class="auth-shape shape-1"></div>
        <div class="auth-shape shape-2"></div>
        <div class="auth-shape shape-3"></div>
    </div>

    <!-- Login Card -->
    <div class="auth-card card border-0 z-1 w-100 max-w-420 rounded-1-5">
        <div class="card-body p-4 p-sm-5">

            <div class="text-center mb-4 pb-2">
                <div
                    class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 mb-3 shadow-sm size-48">
                    <span class="material-symbols-outlined fs-3">grid_view</span>
                </div>
                <h1 class="h4 fw-bold mb-1 tracking-tight">Welcome Back</h1>
                <p class="text-muted-adaptive small mb-0">Sign in to CBT Enterprise Platform</p>
            </div>

            <form id="loginForm" class="d-flex flex-column gap-3" aria-label="Login form">
                <!-- Email Input -->
                <div>
                    <label for="identity" class="form-label fw-medium small text-muted-adaptive mb-1 ms-1">School Email
                        Address</label>
                    <input id="identity" name="identity" type="text" class="form-control auth-input-clean w-100"
                        placeholder="name@school.edu" required>
                </div>

                <!-- Password Input -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1 ms-1">
                        <label for="password"
                            class="form-label fw-medium small text-muted-adaptive mb-0">Password</label>
                        <a href="#"
                            class="small text-primary fw-medium text-decoration-none hover-scale font-size-80">Forgot?</a>
                    </div>
                    <input id="password" name="password" type="password" class="form-control auth-input-clean w-100"
                        placeholder="••••••••" required>
                </div>

                <!-- Remember Me -->
                <div class="form-check d-flex align-items-center gap-2 mt-1 ms-1 mb-2">
                    <input class="form-check-input mt-0 cursor-pointer auth-checkbox-size" type="checkbox"
                        id="rememberMe" name="remember">
                    <label class="form-check-label small fw-medium text-muted-adaptive cursor-pointer pt-2px"
                        for="rememberMe">Keep me signed in</label>
                </div>

                <!-- Error Message Container -->
                <div id="loginError"
                    class="alert alert-danger py-2 mb-0 d-none text-center rounded-3 border-0 small fw-medium"
                    role="alert"></div>

                <!-- Action Buttons -->
                <div class="d-flex flex-column gap-3 mt-1">
                    <button type="submit" id="loginBtn"
                        class="auth-btn-primary d-flex align-items-center justify-content-center gap-2 w-100">
                        <span id="btnText">Sign In</span>
                        <span id="btnIcon" class="material-symbols-outlined fs-5">arrow_forward</span>
                        <div id="btnSpinner" class="spinner-border spinner-border-sm text-light d-none" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </button>


                    <div class="d-flex align-items-center text-muted-adaptive small my-1">
                        <hr class="flex-grow-1 opacity-25">
                        <span class="px-3 text-uppercase fw-semibold font-size-70 letter-spacing-sm">Or continue
                            with</span>
                        <hr class="flex-grow-1 opacity-25">
                    </div>

                    <button type="button"
                        class="auth-btn-secondary d-flex align-items-center justify-content-center gap-2 w-100 mb-2">
                        <span class="material-symbols-outlined fs-5">perm_phone_msg</span>
                        <span>SMS Verification</span>
                    </button>

                    <div class="alert alert-info py-2 mb-0 d-none text-center rounded-3 border-0" id="login-help"
                        role="status">
                        <span class="small fw-medium">Identity auto-populated after SMS.</span>
                    </div>
                </div>
            </form>

        </div>

        <div class="card-footer bg-transparent border-top border-secondary border-opacity-10 p-4 text-center">
            <p class="text-muted-adaptive mb-0 font-size-75">
                Don't have an account? <a href="#" class="text-primary text-decoration-none fw-bold">Contact Support</a>
            </p>
        </div>
    </div>

    <div class="mt-4 text-center z-1">
        <p class="text-muted-adaptive mb-0 font-size-75">
            &copy; <?= date('Y') ?> CBT Enterprise Solutions.
            <a href="#" class="text-muted-adaptive text-decoration-underline ms-2">Privacy</a>
            <a href="#" class="text-muted-adaptive text-decoration-underline ms-2">Terms</a>
        </p>
    </div>
</main>

<script type="module" src="/assets/js/pages/login.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/auth.php';
?>