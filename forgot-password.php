<?php
/**
 * FastBite - Forgot Password Page
 * STEP 15 / STEP 31: Customer Password Recovery System
 * 
 * Design Identity: Pink & White
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

$currentPage = 'forgot-password';
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

$error = '';
$success = false;
$resetUrl = '';
$submittedEmail = '';

// Handle POST Request
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $email = strtolower(trim($_POST['forgotEmail'] ?? ''));
    $submittedEmail = $email;

    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed. Please refresh the page and try again.';
    } elseif (empty($email)) {
        $error = 'Please enter your account email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Create password reset token
        $result = dbCreatePasswordResetToken($email);
        if ($result['success']) {
            $success = true;
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $baseDir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $resetUrl = "{$protocol}://{$host}{$baseDir}/reset-password.php?token=" . urlencode($result['token']);

            // Attempt email delivery
            $userName = $result['user']['name'] ?? 'FastBite Customer';
            sendPasswordResetEmail($email, $userName, $resetUrl);
        } else {
            $error = $result['error'] ?? 'Could not create password reset request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?> - Fast Food Online</title>
    <meta name="description" content="Reset your FastBite account password safely and securely.">
    
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
                        
                        <?php if ($success): ?>
                            <!-- SUCCESS VIEW: Link Generated -->
                            <div class="text-center">
                                <div class="bg-pink-soft text-primary-accent rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 64px; height: 64px;">
                                    <i class="bi bi-shield-check fs-2"></i>
                                </div>
                                <span class="auth-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent mb-1">LINK GENERATED</span>
                                <h1 class="auth-title h4 fw-bold mb-2">Reset Link Ready!</h1>
                                <p class="text-muted small mb-4">
                                    We have generated a password reset link for <br>
                                    <strong class="text-dark"><?= htmlspecialchars($submittedEmail, ENT_QUOTES, 'UTF-8'); ?></strong>.
                                    This link is valid for <strong>60 minutes</strong>.
                                </p>

                                <!-- Direct Action Button for Immediate Testing/Reset -->
                                <a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-auth-primary w-100 py-3 fw-bold mb-3 shadow-sm d-inline-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-key-fill"></i>
                                    <span>Reset Password Now</span>
                                </a>

                                <!-- Copyable Link Box -->
                                <div class="mb-4 text-start">
                                    <label class="form-label extra-small text-muted fw-semibold mb-1">Reset Link URL:</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-sm bg-light" id="resetUrlInput" value="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                        <button class="btn btn-outline-secondary btn-sm" type="button" id="btnCopyResetUrl" onclick="navigator.clipboard.writeText(document.getElementById('resetUrlInput').value); this.innerHTML='<i class=\'bi bi-check2\'></i> Copied!'; setTimeout(() => this.innerHTML='<i class=\'bi bi-clipboard\'></i> Copy', 2000);">
                                            <i class="bi bi-clipboard"></i> Copy
                                        </button>
                                    </div>
                                    <span class="extra-small text-muted mt-1 d-block">You can also copy this link and paste it into your browser.</span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-3 border-top small">
                                    <a href="forgot-password.php" class="text-decoration-none text-muted">
                                        <i class="bi bi-arrow-repeat me-1"></i>Try another email
                                    </a>
                                    <a href="login.php" class="auth-link-bold text-decoration-none fw-bold">
                                        <i class="bi bi-arrow-left me-1"></i>Back to Login
                                    </a>
                                </div>
                            </div>

                        <?php else: ?>
                            <!-- FORM VIEW -->
                            <div class="auth-header text-center mb-4">
                                <span class="auth-eyebrow d-inline-block">ACCOUNT RECOVERY</span>
                                <h1 class="auth-title fw-bold mb-2">Forgot Your Password?</h1>
                                <p class="auth-desc text-muted small mb-0">Enter your email address and we'll generate a secure link to reset your password.</p>
                            </div>

                            <!-- Error Alert -->
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0"></i>
                                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                            <?php endif; ?>

                            <form id="forgotForm" method="POST" action="forgot-password.php" novalidate>
                                <?= csrfField(); ?>

                                <!-- Email Field -->
                                <div class="mb-4">
                                    <label for="forgotEmail" class="form-label auth-label">Email Address <span class="text-danger">*</span></label>
                                    <div class="input-group auth-input-group">
                                        <span class="input-group-text auth-icon-addon">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                        <input type="email" 
                                               class="form-control auth-input" 
                                               id="forgotEmail" 
                                               name="forgotEmail" 
                                               placeholder="Enter your email address" 
                                               value="<?= htmlspecialchars($submittedEmail, ENT_QUOTES, 'UTF-8'); ?>"
                                               required>
                                        <div class="invalid-feedback">Please enter a valid email address.</div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" 
                                        class="btn btn-auth-primary w-100 d-inline-flex align-items-center justify-content-center gap-2 mb-4" 
                                        id="btnForgotSubmit">
                                    <i class="bi bi-send"></i>
                                    <span>Send Reset Link</span>
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
</body>
</html>
