<?php
/**
 * FastBite - Courier Profile & Performance
 * STEP 33: Complete Business Platform, Payments, Analytics, Support, Courier Portal & Production Security
 */

$pageTitle = 'Courier Profile';
$activePage = 'profile';

require_once __DIR__ . '/includes/header.php';

$stats = dbGetDriverPortalStats($driverId);
$error = '';
$msg = $_GET['msg'] ?? '';

// Handle Password Change
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'update_password') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed.';
    } else {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPass, $user['password'])) {
            $error = 'Current password entered is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } elseif ($newPass !== $confirmPass) {
            $error = 'New password and confirmation do not match.';
        } else {
            dbUpdateUserPassword($userId, $newPass);
            header("Location: profile.php?msg=" . urlencode("Password updated successfully."));
            exit;
        }
    }
}
?>

<main class="flex-grow-1 py-4">
    <div class="container" style="max-width: 800px;">

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Courier Profile Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="rounded-circle bg-pink-soft text-primary-accent d-flex align-items-center justify-content-center flex-shrink-0" style="width: 64px; height: 64px; font-size: 1.8rem;">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1"><?= htmlspecialchars($driver['full_name']); ?></h1>
                    <span class="badge bg-pink-soft text-primary-accent extra-small rounded-pill px-3 py-1">
                        FastBite Certified Courier
                    </span>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3">
                        <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Vehicle Type</span>
                        <strong class="text-dark fs-6"><?= ucfirst($driver['vehicle_type']); ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3">
                        <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Vehicle Plate / Tag</span>
                        <strong class="text-dark fs-6 font-monospace"><?= htmlspecialchars($driver['vehicle_number'] ?? 'FB-MOT-101'); ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3">
                        <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Phone Number</span>
                        <strong class="text-dark fs-6"><?= htmlspecialchars($driver['phone']); ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3">
                        <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Courier Rating</span>
                        <strong class="text-warning fs-6"><i class="bi bi-star-fill text-warning"></i> <?= $stats['rating']; ?> / 5.00</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security / Password Update Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h2 class="h6 fw-bold text-dark mb-3 pb-2 border-bottom">Change Password</h2>
            <form method="POST" action="profile.php">
                <?= csrfField(); ?>
                <input type="hidden" name="action" value="update_password">

                <div class="mb-3">
                    <label for="currentPassword" class="form-label small fw-bold text-dark">Current Password</label>
                    <input type="password" name="current_password" id="currentPassword" class="form-control rounded-3" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="newPassword" class="form-label small fw-bold text-dark">New Password</label>
                        <input type="password" name="new_password" id="newPassword" class="form-control rounded-3" minlength="6" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="confirmPassword" class="form-label small fw-bold text-dark">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-control rounded-3" minlength="6" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-auth-primary rounded-pill px-4 shadow-sm">
                        Update Password
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
