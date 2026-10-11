<?php
/**
 * FastBite - Admin Password Reset Workflow
 * STEP 27: Admin Users, Roles & Security Management
 */

$activeMenu = 'users';
$pageTitle = 'Reset User Password';

require_once dirname(__DIR__) . '/includes/header.php';

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    header("Location: index.php?error=" . urlencode("Invalid user ID specified."));
    exit;
}

$user = dbFindUserById($userId);
if (!$user) {
    header("Location: index.php?error=" . urlencode("User not found."));
    exit;
}

$currentAdmin = currentUser();
$error = '';
$msg = '';

// Handle POST password reset
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security verification failed. Please try again.';
    } else {
        $newPassword = $_POST['newPassword'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        if (empty($newPassword) || empty($confirmPassword)) {
            $error = 'Please provide and confirm the new password.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'The password confirmation does not match.';
        } else {
            $resetResult = dbResetUserPassword($userId, $newPassword, $currentAdmin['id']);
            if ($resetResult['success']) {
                header("Location: view.php?id=" . $userId . "&msg=" . urlencode("Password reset successfully."));
                exit;
            } else {
                $error = $resetResult['error'] ?? 'Failed to reset password.';
            }
        }
    }
}
?>

<!-- Main Content -->
<main class="admin-main p-3 p-lg-4 flex-grow-1">
    <div class="container-fluid p-0">
        
        <!-- Breadcrumbs -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-muted text-decoration-none">Users Management</a></li>
                    <li class="breadcrumb-item"><a href="view.php?id=<?= $userId; ?>" class="text-muted text-decoration-none"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Reset Password</li>
                </ol>
            </nav>
            <a href="view.php?id=<?= $userId; ?>" class="btn btn-light btn-sm border">Back to Profile</a>
        </div>

        <!-- Feedback Alerts -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <h2 class="h5 fw-bold text-dark mb-0"><i class="bi bi-key-fill text-warning me-2"></i>Reset User Password</h2>
                    </div>
                    <div class="card-body p-4">
                        
                        <!-- User Target Badge -->
                        <div class="p-3 rounded-3 bg-pink-soft border border-pink-subtle mb-4 d-flex align-items-center gap-3">
                            <div class="stat-icon-wrap bg-white text-primary-accent rounded-circle d-flex align-items-center justify-content-center border" style="width: 42px; height: 42px;">
                                <i class="bi bi-person-lock fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                <span class="extra-small text-muted"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> • <?= ucfirst($user['role']); ?></span>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 extra-small mb-4">
                            <i class="bi bi-shield-check me-1"></i>
                            <strong>Security Notice:</strong> Passwords are automatically hashed with <code>PASSWORD_DEFAULT</code>. Plain-text passwords are never revealed, logged, or retained.
                        </div>

                        <form method="POST" action="reset-password.php?id=<?= $userId; ?>" id="resetPasswordForm">
                            <?= csrfField(); ?>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="newPassword" id="newPassword" class="form-control" placeholder="Enter new password" required minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn"><i class="bi bi-eye"></i></button>
                                </div>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar bg-danger" id="strengthBar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <div class="extra-small text-muted mt-1" id="strengthText">Minimum 6 characters required.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" name="confirmPassword" id="confirmPassword" class="form-control" placeholder="Re-type new password" required minlength="6">
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                <a href="view.php?id=<?= $userId; ?>" class="btn btn-light btn-sm border px-3">Cancel</a>
                                <button type="submit" class="btn btn-primary-accent btn-sm px-4">Save New Password</button>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pwInput = document.getElementById('newPassword');
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');

    if (toggleBtn && pwInput) {
        toggleBtn.addEventListener('click', function() {
            const type = pwInput.getAttribute('type') === 'password' ? 'text' : 'password';
            pwInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });
    }

    if (pwInput && strengthBar && strengthText) {
        pwInput.addEventListener('input', function() {
            const val = pwInput.value;
            let score = 0;
            if (val.length >= 6) score += 25;
            if (val.length >= 8) score += 25;
            if (/[0-9]/.test(val)) score += 25;
            if (/[A-Z]/.test(val) || /[^A-Za-z0-9]/.test(val)) score += 25;

            strengthBar.style.width = score + '%';
            if (score <= 25) {
                strengthBar.className = 'progress-bar bg-danger';
                strengthText.innerText = 'Strength: Weak (Add more characters)';
            } else if (score <= 50) {
                strengthBar.className = 'progress-bar bg-warning';
                strengthText.innerText = 'Strength: Moderate';
            } else if (score <= 75) {
                strengthBar.className = 'progress-bar bg-info';
                strengthText.innerText = 'Strength: Good';
            } else {
                strengthBar.className = 'progress-bar bg-success';
                strengthText.innerText = 'Strength: Strong ✓';
            }
        });
    }
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
