<?php
/**
 * FastBite - Customer Profile & Account Settings Page
 * STEP 24: Backend Foundation & Database Integration
 * 
 * Features:
 * - Real user profile retrieval from database (requireLogin)
 * - Real Profile update handling (UPDATE users)
 * - Real Password change handling with password_verify() and password_hash()
 * - Preserves complete Pink + White UI design & accessibility
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';

requireLogin('login.php');

$user = currentUser();
if (!$user) {
    header("Location: login.php");
    exit;
}

$currentPage = 'profile';
$projectName = 'FastBite';
$userId = (int)$user['id'];

$successMsg = '';
$errorMsg = '';

// Handle POST submissions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['form_action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrfToken)) {
        $errorMsg = 'Invalid or expired security token. Please refresh the page and try again.';
    } elseif ($action === 'update_profile') {
        $fullName = trim($_POST['fullName'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $gender = trim($_POST['gender'] ?? 'unspecified');
        $lang = trim($_POST['language'] ?? 'en');

        if (empty($fullName)) {
            $errorMsg = 'Full name is required.';
        } else {
            $updated = dbUpdateUserProfile($userId, [
                'name' => $fullName,
                'phone' => $phone,
                'date_of_birth' => $dob,
                'gender' => $gender,
                'preferred_language' => $lang
            ]);

            if ($updated) {
                $_SESSION['user_name'] = $fullName;
                $user = currentUser();
                $successMsg = 'Your profile information has been updated successfully.';
            } else {
                $errorMsg = 'Failed to update profile. Please try again.';
            }
        }
    } elseif ($action === 'change_password') {
        $currentPw = $_POST['currentPassword'] ?? '';
        $newPw = $_POST['newPassword'] ?? '';
        $confirmPw = $_POST['confirmNewPassword'] ?? '';

        if (empty($currentPw) || empty($newPw) || empty($confirmPw)) {
            $errorMsg = 'Please fill in all password fields.';
        } elseif (!password_verify($currentPw, $user['password'])) {
            $errorMsg = 'Current password is incorrect. Please try again.';
        } elseif (strlen($newPw) < 8) {
            $errorMsg = 'New password must be at least 8 characters long.';
        } elseif ($newPw !== $confirmPw) {
            $errorMsg = 'New passwords do not match.';
        } else {
            $updated = dbUpdateUserPassword($userId, $newPw);
            if ($updated) {
                $successMsg = 'Your password has been changed successfully.';
            } else {
                $errorMsg = 'Failed to update password. Please try again.';
            }
        }
    } elseif ($action === 'update_notifications') {
        $newPrefs = [
            'order_updates' => isset($_POST['pref_order_updates']),
            'promotions' => isset($_POST['pref_promotions']),
            'account_security' => isset($_POST['pref_account_security']),
            'newsletter' => isset($_POST['pref_newsletter'])
        ];
        
        $saved = dbUpdateUserNotificationPreferences($userId, $newPrefs);
        if ($saved) {
            $successMsg = 'Your communication preferences have been updated.';
        } else {
            $errorMsg = 'Failed to save communication preferences. Please try again.';
        }
    }
}

// Fetch user notification preferences
$userPrefs = dbGetUserNotificationPreferences($userId);

$avatarImg = function_exists('getAvatarImage') ? getAvatarImage($user['avatar'] ?? 'avatar-placeholder.jpg') : 'assets/images/placeholders/avatar-placeholder.jpg';
$memberSince = date('F Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile & Settings - <?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?> - Fast Food Online</title>
    <meta name="description" content="Manage your FastBite personal profile, password security, notifications, and account preferences.">
    
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
<body class="dashboard-body">
    <!-- Main Navbar Component -->
    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main id="main-content" class="dashboard-main-wrapper py-4 py-md-5">
        <div class="container">
            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb extra-small mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted"><i class="bi bi-house me-1" aria-hidden="true"></i>Home</a></li>
                    <li class="breadcrumb-item"><a href="customer-dashboard.php" class="text-decoration-none text-muted">My Account</a></li>
                    <li class="breadcrumb-item active text-primary-accent fw-bold" aria-current="page">Profile</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="dashboard-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <span class="dashboard-eyebrow d-inline-block">MY PROFILE</span>
                    <h1 class="dashboard-title fw-bold mb-1">My Profile</h1>
                    <p class="dashboard-desc text-muted mb-0">Manage your personal information, password, notifications, and account preferences from one place.</p>
                </div>

                <!-- Quick Action Buttons -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="customer-dashboard.php" class="btn btn-sm btn-outline-pink d-inline-flex align-items-center gap-1" title="Customer Dashboard">
                        <i class="bi bi-grid-1x2" aria-hidden="true"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="my-orders.php" class="btn btn-sm btn-outline-pink d-inline-flex align-items-center gap-1" title="Order History">
                        <i class="bi bi-bag-check" aria-hidden="true"></i>
                        <span>My Orders</span>
                    </a>
                    <a href="menu.php" class="btn btn-sm btn-auth-primary d-inline-flex align-items-center gap-1" title="Browse Menu">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        <span>Order Food</span>
                    </a>
                </div>
            </div>

            <!-- Feedback Notifications -->
            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                    <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endif; ?>

            <!-- Two-Column Account Layout -->
            <div class="row g-4">
                <!-- Left Sidebar Navigation -->
                <aside class="col-12 col-lg-4 col-xl-3" aria-label="Account Navigation">
                    <div class="dashboard-sidebar card p-3 p-md-4 border-0 bg-white shadow-sm sticky-lg-top" style="top: 85px; z-index: 10;">
                        <!-- User Mini Card -->
                        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom sidebar-user-brief">
                            <img src="<?= htmlspecialchars($avatarImg, ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?> Avatar" 
                                 class="sidebar-user-avatar"
                                 id="sidebarUserAvatar">
                            <div class="text-truncate">
                                <span class="d-block fw-bold text-dark text-truncate" id="sidebarUserName"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="d-block extra-small text-muted text-truncate" id="sidebarUserEmail"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>

                        <!-- Sidebar Nav Links -->
                        <nav class="dashboard-sidebar-nav d-flex flex-column gap-1" aria-label="Account Sections">
                            <a href="customer-dashboard.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-grid-1x2" aria-hidden="true"></i>
                                <span>Dashboard</span>
                            </a>
                            <a href="my-orders.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-bag" aria-hidden="true"></i>
                                <span>My Orders</span>
                            </a>
                            <a href="addresses.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                <span>Saved Addresses</span>
                            </a>
                            <a href="profile.php" class="dashboard-nav-link active text-decoration-none d-flex align-items-center gap-2" aria-current="page">
                                <i class="bi bi-person" aria-hidden="true"></i>
                                <span>Profile</span>
                            </a>
                            <a href="profile.php#settings" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-gear" aria-hidden="true"></i>
                                <span>Account Settings</span>
                            </a>
                            <a href="notifications.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-bell" aria-hidden="true"></i>
                                <span>Notifications</span>
                            </a>

                            <div class="sidebar-nav-divider my-2 pt-2 border-top"></div>

                            <a href="logout.php" class="dashboard-nav-link text-danger text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                <span>Logout</span>
                            </a>
                        </nav>
                    </div>
                </aside>

                <!-- Right Main Content Panel -->
                <div class="col-12 col-lg-8 col-xl-9 d-flex flex-column gap-4">

                    <!-- Personal Information Section -->
                    <section class="card p-4 border-0 bg-white shadow-sm" id="personal-info" aria-labelledby="heading-personal-info">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div>
                                <h2 class="h5 fw-bold text-dark mb-1" id="heading-personal-info">Personal Information</h2>
                                <p class="text-muted extra-small mb-0">Update your personal details and contact information.</p>
                            </div>
                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-2 py-1 extra-small">Customer Profile</span>
                        </div>

                        <!-- Top Profile Header (Avatar + Name + Member Since) -->
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 p-3 rounded bg-light border mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="position-relative flex-shrink-0">
                                    <img src="<?= htmlspecialchars($avatarImg, ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?> Avatar" 
                                         class="profile-card-avatar"
                                         id="mainProfileAvatar">
                                </div>
                                <div>
                                    <h3 class="h6 fw-bold text-dark mb-0" id="profileHeaderName"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <span class="d-block extra-small text-muted" id="profileHeaderEmail"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="extra-small text-primary-accent fw-semibold d-inline-flex align-items-center gap-1 mt-1">
                                        <i class="bi bi-stars" aria-hidden="true"></i>
                                        <span>Member since <?= htmlspecialchars($memberSince, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Information Form -->
                        <form id="profileInfoForm" method="POST" action="profile.php" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="form_action" value="update_profile">
                            <div class="row g-3">
                                <!-- Full Name -->
                                <div class="col-12 col-md-6">
                                    <label for="profFullName" class="form-label auth-label">Full Name <span class="text-danger">*</span></label>
                                    <div class="input-group auth-input-group">
                                        <span class="input-group-text auth-icon-addon"><i class="bi bi-person" aria-hidden="true"></i></span>
                                        <input type="text" class="form-control auth-input" id="profFullName" name="fullName" value="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                    <div class="invalid-feedback extra-small">Please enter your full name.</div>
                                </div>

                                <!-- Email Address (Read-only identifier) -->
                                <div class="col-12 col-md-6">
                                    <label for="profEmail" class="form-label auth-label">Email Address</label>
                                    <div class="input-group auth-input-group">
                                        <span class="input-group-text auth-icon-addon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                                        <input type="email" class="form-control auth-input bg-light" id="profEmail" name="email" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                    </div>
                                    <div class="form-text extra-small text-muted">Email address is your unique account ID.</div>
                                </div>

                                <!-- Phone Number -->
                                <div class="col-12 col-md-6">
                                    <label for="profPhone" class="form-label auth-label">Phone Number</label>
                                    <div class="input-group auth-input-group">
                                        <span class="input-group-text auth-icon-addon"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                                        <input type="tel" class="form-control auth-input" id="profPhone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="+252 63 000 0000">
                                    </div>
                                </div>

                                <!-- Date of Birth -->
                                <div class="col-12 col-md-6">
                                    <label for="profDob" class="form-label auth-label">Date of Birth</label>
                                    <div class="input-group auth-input-group">
                                        <span class="input-group-text auth-icon-addon"><i class="bi bi-calendar3" aria-hidden="true"></i></span>
                                        <input type="date" class="form-control auth-input" id="profDob" name="dob" value="<?= htmlspecialchars($user['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>

                                <!-- Gender -->
                                <div class="col-12 col-md-6">
                                    <label for="profGender" class="form-label auth-label">Gender</label>
                                    <div class="input-group auth-input-group">
                                        <span class="input-group-text auth-icon-addon"><i class="bi bi-gender-ambiguous" aria-hidden="true"></i></span>
                                        <select class="form-select auth-input" id="profGender" name="gender">
                                            <option value="unspecified" <?= ($user['gender'] ?? '') === 'unspecified' ? 'selected' : ''; ?>>Prefer not to say</option>
                                            <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Preferred Language -->
                                <div class="col-12 col-md-6">
                                    <label for="profLang" class="form-label auth-label">Preferred Language</label>
                                    <div class="input-group auth-input-group">
                                        <span class="input-group-text auth-icon-addon"><i class="bi bi-translate" aria-hidden="true"></i></span>
                                        <select class="form-select auth-input" id="profLang" name="language">
                                            <option value="en" <?= ($user['preferred_language'] ?? '') === 'en' ? 'selected' : ''; ?>>English (Default)</option>
                                            <option value="so" <?= ($user['preferred_language'] ?? '') === 'so' ? 'selected' : ''; ?>>Somali</option>
                                            <option value="ar" <?= ($user['preferred_language'] ?? '') === 'ar' ? 'selected' : ''; ?>>Arabic</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Save Personal Information Button -->
                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-auth-primary d-inline-flex align-items-center gap-2" id="btnSaveProfile">
                                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                    <span>Save Changes</span>
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Security & Password Section -->
                    <section class="card p-4 border-0 bg-white shadow-sm" id="security" aria-labelledby="heading-security">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div>
                                <h2 class="h5 fw-bold text-dark mb-1" id="heading-security">Password &amp; Security</h2>
                                <p class="text-muted extra-small mb-0">Ensure your account is using a strong, unique password.</p>
                            </div>
                            <i class="bi bi-shield-lock-fill text-primary-accent fs-4" aria-hidden="true"></i>
                        </div>

                        <!-- Change Password Form -->
                        <form id="profilePasswordForm" method="POST" action="profile.php" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="form_action" value="change_password">
                            <div class="row g-3">
                                <!-- Current Password -->
                                <div class="col-12">
                                    <label for="currentPassword" class="form-label auth-label">Current Password <span class="text-danger">*</span></label>
                                    <div class="input-group auth-input-group">
                                        <span class="input-group-text auth-icon-addon"><i class="bi bi-lock" aria-hidden="true"></i></span>
                                        <input type="password" class="form-control auth-input password-field" id="currentPassword" name="currentPassword" placeholder="Enter current password" required>
                                        <button type="button" class="btn btn-outline-secondary auth-pw-toggle" aria-label="Toggle current password visibility" tabindex="0">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <div class="invalid-feedback extra-small">Please enter your current password.</div>
                                    </div>
                                </div>

                                <!-- New Password -->
                                <div class="col-12 col-md-6">
                                    <label for="newPassword" class="form-label auth-label">New Password <span class="text-danger">*</span></label>
                                    <div class="input-group auth-input-group">
                                        <span class="input-group-text auth-icon-addon"><i class="bi bi-key" aria-hidden="true"></i></span>
                                        <input type="password" class="form-control auth-input password-field" id="newPassword" name="newPassword" placeholder="Enter new password (min 8 chars)" minlength="8" required>
                                        <button type="button" class="btn btn-outline-secondary auth-pw-toggle" aria-label="Toggle new password visibility" tabindex="0">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <div class="invalid-feedback extra-small">Password must be at least 8 characters.</div>
                                    </div>
                                </div>

                                <!-- Confirm New Password -->
                                <div class="col-12 col-md-6">
                                    <label for="confirmNewPassword" class="form-label auth-label">Confirm New Password <span class="text-danger">*</span></label>
                                    <div class="input-group auth-input-group">
                                        <span class="input-group-text auth-icon-addon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                                        <input type="password" class="form-control auth-input password-field" id="confirmNewPassword" name="confirmNewPassword" placeholder="Confirm new password" required>
                                        <button type="button" class="btn btn-outline-secondary auth-pw-toggle" aria-label="Toggle confirm new password visibility" tabindex="0">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <div class="invalid-feedback extra-small">Passwords do not match.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Update Password Button -->
                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-auth-primary d-inline-flex align-items-center gap-2" id="btnUpdatePassword">
                                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                                    <span>Update Password</span>
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Notification & Communication Preferences Section -->
                    <section class="card p-4 border-0 bg-white shadow-sm" id="settings" aria-labelledby="heading-settings">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div>
                                <h2 class="h5 fw-bold text-dark mb-1" id="heading-settings">Communication Preferences</h2>
                                <p class="text-muted extra-small mb-0">Choose how and when FastBite communicates with you.</p>
                            </div>
                            <i class="bi bi-bell-gear text-primary-accent fs-4" aria-hidden="true"></i>
                        </div>

                        <form id="profileNotificationsForm" method="POST" action="profile.php#settings">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="form_action" value="update_notifications">

                            <div class="d-flex flex-column gap-3">
                                <!-- Order Updates -->
                                <div class="p-3 rounded border bg-light d-flex align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="rounded-circle p-2 bg-pink-soft text-primary-accent flex-shrink-0">
                                            <i class="bi bi-bag-check fs-5" aria-hidden="true"></i>
                                        </div>
                                        <div>
                                            <label for="pref_order_updates" class="fw-bold text-dark mb-0 d-block cursor-pointer">Order Status Updates</label>
                                            <span class="text-muted extra-small d-block">Receive instant updates when your order is confirmed, prepared, out for delivery, and delivered.</span>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch fs-5 mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="pref_order_updates" name="pref_order_updates" value="1" <?= !empty($userPrefs['order_updates']) ? 'checked' : ''; ?>>
                                    </div>
                                </div>

                                <!-- Special Offers & Promotions -->
                                <div class="p-3 rounded border bg-light d-flex align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="rounded-circle p-2 bg-pink-soft text-primary-accent flex-shrink-0">
                                            <i class="bi bi-tag fs-5" aria-hidden="true"></i>
                                        </div>
                                        <div>
                                            <label for="pref_promotions" class="fw-bold text-dark mb-0 d-block cursor-pointer">Promotions &amp; Discounts</label>
                                            <span class="text-muted extra-small d-block">Receive coupons, flash sale notifications, and exclusive FastBite member deals.</span>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch fs-5 mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="pref_promotions" name="pref_promotions" value="1" <?= !empty($userPrefs['promotions']) ? 'checked' : ''; ?>>
                                    </div>
                                </div>

                                <!-- Account & Security Alerts -->
                                <div class="p-3 rounded border bg-light d-flex align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="rounded-circle p-2 bg-pink-soft text-primary-accent flex-shrink-0">
                                            <i class="bi bi-shield-check fs-5" aria-hidden="true"></i>
                                        </div>
                                        <div>
                                            <label for="pref_account_security" class="fw-bold text-dark mb-0 d-block cursor-pointer">Account &amp; Security Alerts</label>
                                            <span class="text-muted extra-small d-block">Critical notifications regarding password changes, login activity, and security notices.</span>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch fs-5 mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="pref_account_security" name="pref_account_security" value="1" <?= !empty($userPrefs['account_security']) ? 'checked' : ''; ?>>
                                    </div>
                                </div>

                                <!-- Newsletter -->
                                <div class="p-3 rounded border bg-light d-flex align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="rounded-circle p-2 bg-pink-soft text-primary-accent flex-shrink-0">
                                            <i class="bi bi-envelope-paper fs-5" aria-hidden="true"></i>
                                        </div>
                                        <div>
                                            <label for="pref_newsletter" class="fw-bold text-dark mb-0 d-block cursor-pointer">FastBite Digest Newsletter</label>
                                            <span class="text-muted extra-small d-block">Monthly round-up of trending menu items, new restaurant locations, and chef specials.</span>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch fs-5 mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="pref_newsletter" name="pref_newsletter" value="1" <?= !empty($userPrefs['newsletter']) ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>

                            <!-- Save Notification Preferences Button -->
                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-auth-primary d-inline-flex align-items-center gap-2" id="btnSavePreferences">
                                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                    <span>Save Preferences</span>
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <!-- Main Footer Component -->
    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
