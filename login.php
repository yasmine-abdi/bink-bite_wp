<?php
/**
 * FastBite - Customer Login Page
 * STEP 31: Authentication Finalization, Google Sign-In & Professional Receipts
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';

$currentPage = 'login';
$projectName = 'FastBite';

// If already logged in, redirect based on role
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/index.php");
        exit;
    } elseif (isDriver()) {
        header("Location: driver/index.php");
        exit;
    } else {
        header("Location: customer-dashboard.php");
        exit;
    }
}

$error = $_GET['error'] ?? '';
$msg = $_GET['msg'] ?? '';
$returnUrl = $_GET['return_url'] ?? '';

// Handle POST Login
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = trim($_POST['loginEmail'] ?? '');
    $password = $_POST['loginPassword'] ?? '';
    $returnUrl = trim($_POST['return_url'] ?? $returnUrl);
    $csrf = $_POST['csrf_token'] ?? '';

    // Verify CSRF token
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed. Please refresh the page and try again.';
    } else {
        // Check brute-force throttling
        $throttleStatus = function_exists('isLoginThrottled') ? isLoginThrottled($email) : false;
        if ($throttleStatus && !empty($throttleStatus['throttled'])) {
            $mins = $throttleStatus['remaining_minutes'];
            $error = "Too many failed login attempts. Please wait {$mins} minute(s) before trying again.";
            if (function_exists('dbLogSecurityEvent')) {
                dbLogSecurityEvent('login_throttled', "Rate limit triggered for email {$email} ({$mins} min remaining)");
            }
        } elseif (empty($email) || empty($password)) {
            $error = 'Please enter both your email address and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $user = dbFindUserByEmail($email);
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Your account has been deactivated. Please contact support.';
                    if (function_exists('dbLogSecurityEvent')) {
                        dbLogSecurityEvent('login_blocked', "Blocked login on deactivated account: {$email}", $user['id']);
                    }
                    if (function_exists('recordFailedLoginAttempt')) {
                        recordFailedLoginAttempt($email);
                    }
                } else {
                    if (function_exists('clearLoginAttempts')) {
                        clearLoginAttempts($email);
                    }
                    loginUser($user);
                    if (function_exists('dbLogSecurityEvent')) {
                        dbLogSecurityEvent('login_success', "User {$user['name']} ({$user['email']}) logged in successfully", $user['id'], ($user['role'] === 'admin' ? $user['id'] : null));
                    }

                    if ($user['role'] === 'admin') {
                        $target = !empty($returnUrl) ? $returnUrl : 'admin/index.php';
                        header("Location: " . $target);
                        exit;
                    } elseif ($user['role'] === 'driver') {
                        $target = !empty($returnUrl) ? $returnUrl : 'driver/index.php';
                        header("Location: " . $target);
                        exit;
                    } else {
                        $target = !empty($returnUrl) ? $returnUrl : 'customer-dashboard.php';
                        header("Location: " . $target);
                        exit;
                    }
                }
            } else {
                if (function_exists('recordFailedLoginAttempt')) {
                    recordFailedLoginAttempt($email);
                }
                if (function_exists('dbLogSecurityEvent')) {
                    dbLogSecurityEvent('login_failed', "Failed login attempt for email: {$email}", ($user ? $user['id'] : null));
                }
                // Avoid username enumeration
                $error = 'Invalid email address or password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?> - Fast Food Online</title>
    <meta name="description" content="Sign in to your FastBite account to order delicious meals, manage favorites, and track deliveries.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main id="main-content" class="auth-main-wrapper py-4 py-md-5">
        <div class="container">
            <div class="row justify-content-center align-items-center">
                <div class="col-12 col-lg-10 col-xl-9">
                    <div class="auth-card-container card overflow-hidden border-0 shadow-sm">
                        <div class="row g-0">
                            <!-- Left Column: FastBite Brand Panel -->
                            <div class="col-12 col-lg-5 auth-brand-panel d-none d-lg-flex flex-column justify-content-between p-4 p-xl-5 text-white">
                                <div class="brand-panel-top">
                                    <div class="d-inline-flex align-items-center gap-2 mb-4">
                                        <span class="auth-brand-icon">
                                            <i class="bi bi-fire"></i>
                                        </span>
                                        <span class="fs-4 fw-bold">Fast<span class="text-white-50">Bite</span></span>
                                    </div>
                                    <h2 class="auth-brand-heading h3 fw-bold mb-3">Delicious Food, Anytime &amp; Anywhere.</h2>
                                    <p class="auth-brand-lead text-white-50 small mb-0">Sign in to save your favorite orders, apply promo coupons, and enjoy lightning-fast checkout.</p>
                                </div>

                                <div class="brand-panel-features d-flex flex-column gap-3 my-4">
                                    <div class="d-flex align-items-center gap-2 small">
                                        <i class="bi bi-check-circle-fill text-white"></i>
                                        <span>Fresh &amp; hot meals delivered in 30 mins</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 small">
                                        <i class="bi bi-check-circle-fill text-white"></i>
                                        <span>Exclusive daily promo codes and deals</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 small">
                                        <i class="bi bi-check-circle-fill text-white"></i>
                                        <span>Real-time live order fulfillment tracking</span>
                                    </div>
                                </div>

                                <div class="brand-panel-footer pt-3 border-top border-white-25 small text-white-50">
                                    &copy; <?= date('Y'); ?> FastBite Fast Food. All rights reserved.
                                </div>
                            </div>

                            <!-- Right Column: Login Form Panel -->
                            <div class="col-12 col-lg-7 p-4 p-md-5 auth-form-panel bg-white">
                                <div class="auth-header text-center text-lg-start mb-4">
                                    <span class="auth-eyebrow d-inline-block">WELCOME BACK</span>
                                    <h1 class="auth-title fw-bold mb-2">Login to FastBite</h1>
                                    <p class="auth-desc text-muted small mb-0">Sign in to continue ordering your favorite meals.</p>
                                </div>

                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm" role="alert">
                                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0"></i>
                                        <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($msg)): ?>
                                    <div class="alert alert-success py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm" role="alert">
                                        <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0"></i>
                                        <div><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                <?php endif; ?>

                                <!-- Google Sign-In Button -->
                                <a href="google-login.php<?= !empty($returnUrl) ? '?return_url=' . urlencode($returnUrl) : ''; ?>" 
                                   class="btn btn-outline-secondary w-100 py-2 d-flex align-items-center justify-content-center gap-2 rounded-3 mb-3 text-dark fw-semibold shadow-sm" 
                                   id="btnGoogleLogin"
                                   style="border-color: #dadce0; background-color: #fff;">
                                    <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.616z" fill="#4285F4"/>
                                        <path d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z" fill="#34A853"/>
                                        <path d="M3.964 10.707c-.18-.54-.282-1.116-.282-1.707s.102-1.167.282-1.707V4.961H.957C.347 6.173 0 7.547 0 9s.347 2.827.957 4.039l3.007-2.332z" fill="#FBBC05"/>
                                        <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.961L3.964 7.293C4.672 5.166 6.656 3.58 9 3.58z" fill="#EA4335"/>
                                    </svg>
                                    <span>Continue with Google</span>
                                </a>

                                <!-- Styled OR Divider -->
                                <div class="position-relative text-center my-3">
                                    <hr class="text-muted opacity-25">
                                    <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 extra-small text-muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">
                                        or with email
                                    </span>
                                </div>

                                <form id="loginForm" method="POST" action="login.php" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                    <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>">

                                    <!-- Email Field -->
                                    <div class="mb-3">
                                        <label for="loginEmail" class="form-label auth-label">Email Address <span class="text-danger">*</span></label>
                                        <div class="input-group auth-input-group">
                                            <span class="input-group-text auth-icon-addon">
                                                <i class="bi bi-envelope"></i>
                                            </span>
                                            <input type="email" 
                                                   class="form-control auth-input" 
                                                   id="loginEmail" 
                                                   name="loginEmail" 
                                                   placeholder="Enter your email address" 
                                                   value="<?= htmlspecialchars($_POST['loginEmail'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                   required>
                                            <div class="invalid-feedback">Please enter a valid email address.</div>
                                        </div>
                                    </div>

                                    <!-- Password Field -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <label for="loginPassword" class="form-label auth-label mb-0">Password <span class="text-danger">*</span></label>
                                            <a href="forgot-password.php" class="auth-forgot-link small text-decoration-none">Forgot Password?</a>
                                        </div>
                                        <div class="input-group auth-input-group">
                                            <span class="input-group-text auth-icon-addon">
                                                <i class="bi bi-lock"></i>
                                            </span>
                                            <input type="password" 
                                                   class="form-control auth-input password-field" 
                                                   id="loginPassword" 
                                                   name="loginPassword" 
                                                   placeholder="Enter your password" 
                                                   required>
                                            <button type="button" 
                                                    class="btn btn-outline-secondary auth-pw-toggle" 
                                                    id="btnTogglePassword"
                                                    aria-label="Toggle password visibility" 
                                                    tabindex="0">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <div class="invalid-feedback">Please enter your password.</div>
                                        </div>
                                    </div>

                                    <!-- Remember Me Checkbox -->
                                    <div class="form-check mb-4">
                                        <input class="form-check-input auth-checkbox" type="checkbox" id="rememberMe" name="rememberMe">
                                        <label class="form-check-label small text-muted user-select-none" for="rememberMe">
                                            Remember me on this device
                                        </label>
                                    </div>

                                    <!-- Submit Button -->
                                    <button type="submit" 
                                            class="btn btn-auth-primary w-100 d-inline-flex align-items-center justify-content-center gap-2 mb-4 shadow-sm" 
                                            id="btnLoginSubmit">
                                        <i class="bi bi-box-arrow-in-right"></i>
                                        <span>Sign In</span>
                                    </button>
                                    <!-- Register Redirect -->
                                    <div class="auth-footer text-center pt-3 border-top">
                                        <p class="text-muted small mb-0">
                                            Don't have an account? 
                                            <a href="register.php<?= !empty($returnUrl) ? '?return_url=' . urlencode($returnUrl) : ''; ?>" class="auth-link-bold text-decoration-none fw-bold">Register</a>
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
    document.querySelectorAll('.auth-pw-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const input = this.closest('.input-group').querySelector('.password-field');
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });
    </script>
</body>
</html>
