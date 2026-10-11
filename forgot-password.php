<?php
/**
 * FastBite - Forgot Password Page
 * Step 15: Login / Register UI (Frontend-Only Static Demo)
 * 
 * Design Identity: Pink & White
 */

$currentPage = 'forgot-password';
$projectName = 'FastBite';
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
                        <div class="auth-header text-center mb-4">
                            <span class="auth-eyebrow d-inline-block">ACCOUNT RECOVERY</span>
                            <h1 class="auth-title fw-bold mb-2">Forgot Your Password?</h1>
                            <p class="auth-desc text-muted small mb-0">Enter your email address and we'll show you how password recovery will work in the future.</p>
                        </div>

                        <!-- Demo Alert Notice -->
                        <div id="forgotDemoNotice" class="auth-demo-notice alert alert-info py-2 px-3 mb-4 small text-center d-none" role="alert">
                            <i class="bi bi-info-circle me-1"></i> Password reset is currently a frontend demonstration. Real email recovery will be connected during the backend phase.
                        </div>

                        <form id="forgotForm" class="needs-validation" novalidate>
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
