<?php
/**
 * FastBite - Customer Registration Page
 * STEP 31: Authentication Finalization, Google Sign-In & Professional Receipts
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';

$currentPage = 'register';
$projectName = 'FastBite';

// If already logged in, redirect
if (isLoggedIn()) {
    header("Location: customer-dashboard.php");
    exit;
}

$error = '';
$returnUrl = $_GET['return_url'] ?? '';

// Handle POST Registration
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = trim($_POST['regName'] ?? '');
    $email = trim(strtolower($_POST['regEmail'] ?? ''));
    $phone = trim($_POST['regPhone'] ?? '');
    $password = $_POST['regPassword'] ?? '';
    $confirmPassword = $_POST['regConfirmPassword'] ?? '';
    $agreeTerms = isset($_POST['agreeTerms']);
    $returnUrl = trim($_POST['return_url'] ?? $returnUrl);
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed. Please refresh the page and try again.';
    } elseif (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match. Please verify and try again.';
    } elseif (!$agreeTerms) {
        $error = 'You must agree to the Terms & Conditions to create an account.';
    } else {
        // Check for duplicate email
        $existing = dbFindUserByEmail($email);
        if ($existing) {
            $error = 'An account with this email address already exists. Please log in.';
        } else {
            $userId = dbCreateUser([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'role' => 'customer'
            ]);

            if ($userId > 0) {
                $user = dbFindUserById($userId);
                loginUser($user);

                if (function_exists('dbLogSecurityEvent')) {
                    dbLogSecurityEvent('customer_registered', "New customer account registered: {$email}", $userId);
                }

                // Create Welcome Notification
                if (function_exists('dbCreateNotification')) {
                    dbCreateNotification(
                        $userId,
                        "Welcome to FastBite, {$name}!",
                        "Your account has been created. Start exploring our delicious food menu and saving your favorites!",
                        "account",
                        "menu.php"
                    );
                }

                $target = !empty($returnUrl) ? $returnUrl : 'customer-dashboard.php?msg=' . urlencode("Welcome to FastBite! Your account has been created successfully.");
                header("Location: " . $target);
                exit;
            } else {
                $error = 'Failed to create your account. Please try again.';
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
    <title>Register - <?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?> - Fast Food Online</title>
    <meta name="description" content="Join FastBite to enjoy quick ordering, exclusive promo coupons, wishlist favorites, and real-time delivery tracking.">
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
                                    <h2 class="auth-brand-heading h3 fw-bold mb-3">Join the FastBite Family Today.</h2>
                                    <p class="auth-brand-lead text-white-50 small mb-0">Create an account to order your favorite meals with 1-click checkout, save favorite dishes, and track real-time delivery.</p>
                                </div>

                                <div class="brand-panel-features d-flex flex-column gap-3 my-4">
                                    <div class="d-flex align-items-center gap-2 small">
                                        <i class="bi bi-check-circle-fill text-white"></i>
                                        <span>Personalized wishlist and food favorites</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 small">
                                        <i class="bi bi-check-circle-fill text-white"></i>
                                        <span>Exclusive promo coupons and combo deals</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 small">
                                        <i class="bi bi-check-circle-fill text-white"></i>
                                        <span>Save multiple home and work addresses</span>
                                    </div>
                                </div>

                                <div class="brand-panel-footer pt-3 border-top border-white-25 small text-white-50">
                                    &copy; <?= date('Y'); ?> FastBite Fast Food. All rights reserved.
                                </div>
                            </div>

                            <!-- Right Column: Registration Form Panel -->
                            <div class="col-12 col-lg-7 p-4 p-md-5 auth-form-panel bg-white">
                                <div class="auth-header text-center text-lg-start mb-4">
                                    <span class="auth-eyebrow d-inline-block">CREATE AN ACCOUNT</span>
                                    <h1 class="auth-title fw-bold mb-2">Join FastBite</h1>
                                    <p class="auth-desc text-muted small mb-0">Fill in your details below to start ordering fresh fast food.</p>
                                </div>

                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm" role="alert">
                                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0"></i>
                                        <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                <?php endif; ?>

                                <!-- Google Sign-Up Button -->
                                <a href="google-login.php<?= !empty($returnUrl) ? '?return_url=' . urlencode($returnUrl) : ''; ?>" 
                                   class="btn btn-outline-secondary w-100 py-2 d-flex align-items-center justify-content-center gap-2 rounded-3 mb-3 text-dark fw-semibold shadow-sm" 
                                   id="btnGoogleRegister"
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

                                <form id="registerForm" method="POST" action="register.php" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                    <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>">

                                    <!-- Full Name Field -->
                                    <div class="mb-3">
                                        <label for="regName" class="form-label auth-label">Full Name <span class="text-danger">*</span></label>
                                        <div class="input-group auth-input-group">
                                            <span class="input-group-text auth-icon-addon">
                                                <i class="bi bi-person"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control auth-input" 
                                                   id="regName" 
                                                   name="regName" 
                                                   placeholder="e.g. John Doe" 
                                                   value="<?= htmlspecialchars($_POST['regName'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                   required>
                                            <div class="invalid-feedback">Please enter your full name.</div>
                                        </div>
                                    </div>

                                    <!-- Email Address Field -->
                                    <div class="mb-3">
                                        <label for="regEmail" class="form-label auth-label">Email Address <span class="text-danger">*</span></label>
                                        <div class="input-group auth-input-group">
                                            <span class="input-group-text auth-icon-addon">
                                                <i class="bi bi-envelope"></i>
                                            </span>
                                            <input type="email" 
                                                   class="form-control auth-input" 
                                                   id="regEmail" 
                                                   name="regEmail" 
                                                   placeholder="name@example.com" 
                                                   value="<?= htmlspecialchars($_POST['regEmail'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                   required>
                                            <div class="invalid-feedback">Please enter a valid email address.</div>
                                        </div>
                                    </div>

                                    <!-- Phone Number Field -->
                                    <div class="mb-3">
                                        <label for="regPhone" class="form-label auth-label">Phone Number (Optional)</label>
                                        <div class="input-group auth-input-group">
                                            <span class="input-group-text auth-icon-addon">
                                                <i class="bi bi-telephone"></i>
                                            </span>
                                            <input type="tel" 
                                                   class="form-control auth-input" 
                                                   id="regPhone" 
                                                   name="regPhone" 
                                                   placeholder="+252 63 000 0000" 
                                                   value="<?= htmlspecialchars($_POST['regPhone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                    </div>

                                    <!-- Password Field -->
                                    <div class="mb-3">
                                        <label for="regPassword" class="form-label auth-label">Password <span class="text-danger">*</span></label>
                                        <div class="input-group auth-input-group">
                                            <span class="input-group-text auth-icon-addon">
                                                <i class="bi bi-lock"></i>
                                            </span>
                                            <input type="password" 
                                                   class="form-control auth-input password-field" 
                                                   id="regPassword" 
                                                   name="regPassword" 
                                                   placeholder="At least 8 characters" 
                                                   minlength="8" 
                                                   required>
                                            <button type="button" 
                                                    class="btn btn-outline-secondary auth-pw-toggle" 
                                                    aria-label="Toggle password visibility">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <div class="invalid-feedback">Password must be at least 8 characters.</div>
                                        </div>
                                    </div>

                                    <!-- Confirm Password Field -->
                                    <div class="mb-3">
                                        <label for="regConfirmPassword" class="form-label auth-label">Confirm Password <span class="text-danger">*</span></label>
                                        <div class="input-group auth-input-group">
                                            <span class="input-group-text auth-icon-addon">
                                                <i class="bi bi-lock-fill"></i>
                                            </span>
                                            <input type="password" 
                                                   class="form-control auth-input password-field" 
                                                   id="regConfirmPassword" 
                                                   name="regConfirmPassword" 
                                                   placeholder="Re-enter your password" 
                                                   required>
                                            <button type="button" 
                                                    class="btn btn-outline-secondary auth-pw-toggle" 
                                                    aria-label="Toggle password visibility">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <div class="invalid-feedback">Please confirm your password.</div>
                                        </div>
                                    </div>

                                    <!-- Terms & Privacy Checkbox -->
                                    <div class="form-check mb-4">
                                        <input class="form-check-input auth-checkbox" type="checkbox" id="agreeTerms" name="agreeTerms" required>
                                        <label class="form-check-label small text-muted user-select-none" for="agreeTerms">
                                            I agree to the <a href="about.php" class="auth-terms-link text-decoration-none">Terms of Service</a> and <a href="about.php" class="auth-terms-link text-decoration-none">Privacy Policy</a>.
                                        </label>
                                        <div class="invalid-feedback">You must agree before submitting.</div>
                                    </div>

                                    <!-- Submit Button -->
                                    <button type="submit" 
                                            class="btn btn-auth-primary w-100 d-inline-flex align-items-center justify-content-center gap-2 mb-4 shadow-sm" 
                                            id="btnRegisterSubmit">
                                        <i class="bi bi-person-plus-fill"></i>
                                        <span>Create Account</span>
                                    </button>

                                    <!-- Login Redirect -->
                                    <div class="auth-footer text-center pt-3 border-top">
                                        <p class="text-muted small mb-0">
                                            Already have an account? 
                                            <a href="login.php<?= !empty($returnUrl) ? '?return_url=' . urlencode($returnUrl) : ''; ?>" class="auth-link-bold text-decoration-none fw-bold">Sign In</a>
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
