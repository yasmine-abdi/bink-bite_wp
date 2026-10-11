<?php
/**
 * FastBite - Admin Edit User Profile & Roles
 * STEP 27: Admin Users, Roles & Security Management
 */

$activeMenu = 'users';
$pageTitle = 'Edit User Profile';

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
$activeAdminsCount = dbCountActiveAdmins();
$isLastActiveAdmin = ($user['role'] === 'admin' && $user['status'] === 'active' && $activeAdminsCount <= 1);

// Handle POST submission
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security check failed. Please refresh the page and try again.';
    } else {
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'role' => $_POST['role'] ?? 'customer',
            'status' => $_POST['status'] ?? 'active',
            'date_of_birth' => $_POST['date_of_birth'] ?? null,
            'gender' => $_POST['gender'] ?? 'unspecified',
            'preferred_language' => $_POST['preferred_language'] ?? 'en'
        ];

        $updateResult = dbUpdateUserByAdmin($userId, $data, $currentAdmin['id']);
        if ($updateResult['success']) {
            header("Location: view.php?id=" . $userId . "&msg=" . urlencode("User profile updated successfully."));
            exit;
        } else {
            $error = $updateResult['error'] ?? 'Failed to update user profile.';
            // Retain form input on failure
            $user = array_merge($user, $data);
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
                    <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Edit Profile</li>
                </ol>
            </nav>
            <a href="view.php?id=<?= $userId; ?>" class="btn btn-light btn-sm border">Cancel & Return</a>
        </div>

        <!-- Feedback Alert -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($isLastActiveAdmin): ?>
            <div class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-4" role="alert">
                <i class="bi bi-shield-lock-fill fs-5 flex-shrink-0"></i>
                <div>
                    <strong>Last Active Administrator Account:</strong> You cannot change this user's role to Customer or set their account status to Inactive. Another active administrator must exist first.
                </div>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 max-w-750 mx-auto">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h2 class="h5 fw-bold text-dark mb-0">Edit User Account: <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>
            <div class="card-body p-4">
                
                <form method="POST" action="edit.php?id=<?= $userId; ?>" class="row g-3">
                    <?= csrfField(); ?>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="+252 ..." value="<?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($user['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="unspecified" <?= (($user['gender'] ?? '') === 'unspecified') ? 'selected' : ''; ?>>Unspecified</option>
                            <option value="male" <?= (($user['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?= (($user['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?= (($user['gender'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Preferred Language</label>
                        <select name="preferred_language" class="form-select">
                            <option value="en" <?= (($user['preferred_language'] ?? 'en') === 'en') ? 'selected' : ''; ?>>English</option>
                            <option value="so" <?= (($user['preferred_language'] ?? '') === 'so') ? 'selected' : ''; ?>>Somali</option>
                            <option value="ar" <?= (($user['preferred_language'] ?? '') === 'ar') ? 'selected' : ''; ?>>Arabic</option>
                        </select>
                    </div>

                    <div class="col-12"><hr class="my-2 text-muted"></div>

                    <!-- Role & Status Controls -->
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">User Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select <?= $isLastActiveAdmin ? 'bg-light' : ''; ?>" <?= $isLastActiveAdmin ? 'readonly' : ''; ?>>
                            <option value="customer" <?= ($user['role'] === 'customer') ? 'selected' : ''; ?>>Customer (Frontend Order Access)</option>
                            <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : ''; ?>>Administrator (Control Center Access)</option>
                        </select>
                        <div class="form-text extra-small">Changing role alters system access immediately.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Account Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select <?= $isLastActiveAdmin ? 'bg-light' : ''; ?>" <?= $isLastActiveAdmin ? 'readonly' : ''; ?>>
                            <option value="active" <?= ($user['status'] === 'active') ? 'selected' : ''; ?>>Active (Can Log In)</option>
                            <option value="inactive" <?= ($user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive (Login Blocked)</option>
                        </select>
                        <div class="form-text extra-small">Inactive accounts cannot sign in to the platform.</div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 pt-3 border-top mt-4">
                        <a href="view.php?id=<?= $userId; ?>" class="btn btn-light btn-sm border px-3">Cancel</a>
                        <button type="submit" class="btn btn-primary-accent btn-sm px-4">Save Changes</button>
                    </div>
                </form>

            </div>
        </div>

    </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
