<?php
/**
 * FastBite - Customer Reset Password Page
 * STEP 31: Secure Password Reset Workflow
 * 
 * Design Identity: Pink & White
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$currentPage = 'reset-password';
$projectName = 'FastBite';

// If already logged in, redirect
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

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$verification = dbVerifyPasswordResetToken($token);
$isValidToken = $verification['valid'];
$tokenError = $verification['error'] ?? '';
$user = $verification['user'] ?? null;

$error = '';
$success = false;

// Handle POST Password Reset
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $isValidToken) {
    $csrf = $_POST['csrf_token'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed. Please refresh the page and try again.';
    } elseif (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please enter and confirm your new password.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Password confirmation does not match.';
    } else {
        $reset = dbResetPasswordWithToken($token, $newPassword);
        if ($reset['success']) {
            header("Location: login.php?msg=" . urlencode("Your password has been reset successfully! Please log in with your new password."));
            exit;
        } else {
            $error = $reset['error'] ?? 'Could not reset password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="Set a new secure password for your FastBite account.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS (Pink + White Design System) -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
    <!-- Main Navbar Component -->
    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main id="main-content" class="auth-main-wrapper py-4 py-md-5">
        <div class="container">
            <div class="row justify-content-center align-items-center">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <!-- Auth Container Card -->
                    <div class="auth-card-container card p-4 p-md-5 bg-white border-0 shadow-sm">

                        <?php if (!$isValidToken): ?>
                            <!-- INVALID OR EXPIRED TOKEN VIEW -->
                            <div class="text-center">
                                <div class="bg-danger-subtle text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 64px; height: 64px;">
                                    <i class="bi bi-shield-x fs-2"></i>
                                </div>
                                <span class="auth-eyebrow d-inline-block text-uppercase fw-bold text-danger mb-1">INVALID LINK</span>
                                <h1 class="auth-title h4 fw-bold mb-2">Reset Link Expired</h1>
                                <p class="text-muted small mb-4">
                                    <?= htmlspecialchars($tokenError ?: 'This password reset link is invalid or has expired.', ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <a href="forgot-password.php" class="btn btn-auth-primary w-100 py-2 fw-bold mb-3 shadow-sm d-inline-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-arrow-repeat"></i>
                                    <span>Request a New Reset Link</span>
                                </a>
                                <div class="pt-3 border-top">
                                    <a href="login.php" class="auth-link-bold text-decoration-none small fw-bold">
                                        <i class="bi bi-arrow-left me-1"></i>Back to Login
                                    </a>
                                </div>
                            </div>

                        <?php else: ?>
                            <!-- VALID TOKEN: SET NEW PASSWORD FORM -->
                            <div class="auth-header text-center mb-4">
                                <span class="auth-eyebrow d-inline-block">CREATE NEW PASSWORD</span>
                                <h1 class="auth-title fw-bold mb-1">Set New Password</h1>
                                <p class="auth-desc text-muted small mb-0">
                                    Resetting password for: <br>
                                    <strong class="text-dark"><?= htmlspecialchars($verification['email'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                </p>
                            </div>

                            <!-- Error Alert -->
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0"></i>
                                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="reset-password.php" id="resetPasswordForm" novalidate>
                                <?= csrfField(); ?>
                                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                                <!-- New Password Field -->
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label auth-label">New Password <span class="text-danger">*</span></label>
                                    <div class="input-group auth-input-group">
                                        <span class="input-group-text auth-icon-addon">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input type="password" 
                                               class="form-control auth-input password-field" 
                                               id="newPassword" 
                                               name="newPassword" 
                                               placeholder="Minimum 6 characters" 
                                               minlength="6"
                                               required>
                                        <button type="button" 
                                                class="btn btn-outline-secondary auth-toggle-pwd" 
                                                tabindex="-1"
                                                aria-label="Toggle password visibility">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="extra-small text-muted mt-1">Must be at least 6 characters long.</div>
                                </div>

                                <!-- Confirm Password Field -->
                                <div class="mb-4">
                                    <label for="confirmPassword" class="form-label auth-label">Confirm New Password <span class="text-danger">*</span></label>
                                    <div class="input-group auth-input-group">
                                        <span class="input-group-text auth-icon-addon">
                                            <i class="bi bi-shield-check"></i>
                                        </span>
                                        <input type="password" 
                                               class="form-control auth-input password-field" 
                                               id="confirmPassword" 
                                               name="confirmPassword" 
                                               placeholder="Re-enter your new password" 
                                               minlength="6"
                                               required>
                                        <button type="button" 
                                                class="btn btn-outline-secondary auth-toggle-pwd" 
                                                tabindex="-1"
                                                aria-label="Toggle password visibility">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" 
                                        class="btn btn-auth-primary w-100 py-2 d-inline-flex align-items-center justify-content-center gap-2 mb-4" 
                                        id="btnResetSubmit">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Update Password</span>
                                </button>

                                <!-- Back to Login Redirect -->
                                <div class="auth-footer text-center pt-3 border-top">
                                    <a href="login.php" class="auth-link-bold d-inline-flex align-items-center gap-1 text-decoration-none small fw-bold">
                                        <i class="bi bi-arrow-left"></i>
                                        <span>Back to Login</span>
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    <script>
        // Password visibility toggling for reset page
        document.querySelectorAll('.auth-toggle-pwd').forEach(btn => {
            btn.addEventListener('click', function() {
                const group = this.closest('.input-group');
                const input = group ? group.querySelector('.password-field') : null;
                const icon = this.querySelector('i');
                if (input && icon) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                }
            });
        });
    </script>
</body>
</html>
