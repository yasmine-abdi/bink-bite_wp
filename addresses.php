<?php
/**
 * FastBite - Customer Saved Addresses Management
 * STEP 24: Backend Foundation & Database Integration
 * 
 * Features:
 * - Real user address persistence in MySQL addresses table
 * - Strict User Ownership & Authorization checks (user_id = current_user_id)
 * - Single default address guarantee
 * - Complete Add, Edit, Delete, Set Default actions
 * - Preserves Pink + White UI design & modals
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';

requireLogin('login.php');

$user = currentUser();
if (!$user) {
    header("Location: login.php");
    exit;
}

$currentPage = 'addresses';
$projectName = 'FastBite';
$userId = (int)$user['id'];

$successMsg = '';
$errorMsg = '';

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = 'Security validation failed. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_address') {
        $fullName = trim($_POST['fullName'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $addressType = trim($_POST['addressType'] ?? 'Home');
        $city = trim($_POST['city'] ?? 'Hargeisa');
        $area = trim($_POST['area'] ?? '');
        $streetAddress = trim($_POST['streetAddress'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $isDefault = !empty($_POST['isDefault']) ? 1 : 0;

        if (empty($fullName) || empty($phone) || empty($area) || empty($streetAddress)) {
            $errorMsg = 'Please fill in all required address fields.';
        } else {
            $newId = dbCreateAddress($userId, [
                'full_name' => $fullName,
                'phone' => $phone,
                'address_type' => $addressType,
                'city' => $city,
                'area' => $area,
                'street_address' => $streetAddress,
                'instructions' => $instructions,
                'is_default' => $isDefault
            ]);

            if ($newId) {
                $successMsg = 'New delivery address added successfully.';
            } else {
                $errorMsg = 'Failed to add address. Please try again.';
            }
        }
    } elseif ($action === 'update_address') {
        $addressId = (int)($_POST['address_id'] ?? 0);
        $fullName = trim($_POST['fullName'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $addressType = trim($_POST['addressType'] ?? 'Home');
        $city = trim($_POST['city'] ?? 'Hargeisa');
        $area = trim($_POST['area'] ?? '');
        $streetAddress = trim($_POST['streetAddress'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $isDefault = !empty($_POST['isDefault']) ? 1 : 0;

        if (empty($fullName) || empty($phone) || empty($area) || empty($streetAddress)) {
            $errorMsg = 'Please fill in all required address fields.';
        } else {
            $updated = dbUpdateAddress($addressId, $userId, [
                'full_name' => $fullName,
                'phone' => $phone,
                'address_type' => $addressType,
                'city' => $city,
                'area' => $area,
                'street_address' => $streetAddress,
                'instructions' => $instructions,
                'is_default' => $isDefault
            ]);

            if ($updated) {
                $successMsg = 'Delivery address updated successfully.';
            } else {
                $errorMsg = 'Failed to update address.';
            }
        }
    } elseif ($action === 'delete_address') {
        $addressId = (int)($_POST['address_id'] ?? 0);
        $deleted = dbDeleteAddress($addressId, $userId);
        if ($deleted) {
            $successMsg = 'Delivery address deleted successfully.';
        } else {
            $errorMsg = 'Failed to delete address.';
        }
    } elseif ($action === 'set_default') {
        $addressId = (int)($_POST['address_id'] ?? 0);
        $setDefault = dbSetDefaultAddress($addressId, $userId);
        if ($setDefault) {
            $successMsg = 'Default delivery address updated.';
        } else {
            $errorMsg = 'Failed to set default address.';
        }
    }
    }
}

// Fetch real user addresses from DB
$userAddresses = dbGetUserAddresses($userId);
$avatarImg = function_exists('getAvatarImage') ? getAvatarImage($user['avatar'] ?? 'avatar-placeholder.jpg') : 'assets/images/placeholders/avatar-placeholder.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Addresses - <?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?> - Fast Food Online</title>
    <meta name="description" content="Manage your saved delivery addresses for fast and effortless online food ordering at FastBite.">
    
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
                    <li class="breadcrumb-item active text-primary-accent fw-bold" aria-current="page">Saved Addresses</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="dashboard-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <span class="dashboard-eyebrow d-inline-block">MY ADDRESSES</span>
                    <h1 class="dashboard-title fw-bold mb-1">Saved Addresses</h1>
                    <p class="dashboard-desc text-muted mb-0">Manage your delivery addresses for quick and effortless checkout.</p>
                </div>

                <!-- Action Controls -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-auth-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addAddressModal" id="btnOpenAddAddressModal">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        <span>Add New Address</span>
                    </button>
                    <a href="customer-dashboard.php" class="btn btn-outline-pink d-inline-flex align-items-center gap-1" title="Back to Dashboard">
                        <i class="bi bi-grid-1x2" aria-hidden="true"></i>
                        <span>Dashboard</span>
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

            <!-- Two-Column Layout -->
            <div class="row g-4">
                <!-- Left Sidebar Navigation -->
                <aside class="col-12 col-lg-4 col-xl-3" aria-label="Account Navigation">
                    <div class="dashboard-sidebar card p-3 p-md-4 border-0 bg-white shadow-sm sticky-lg-top" style="top: 85px; z-index: 10;">
                        <!-- User Mini Card -->
                        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom sidebar-user-brief">
                            <img src="<?= htmlspecialchars($avatarImg, ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?> Avatar" 
                                 class="sidebar-user-avatar">
                            <div class="text-truncate">
                                <span class="d-block fw-bold text-dark text-truncate"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="d-block extra-small text-muted text-truncate"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></span>
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
                            <a href="addresses.php" class="dashboard-nav-link active text-decoration-none d-flex align-items-center gap-2" aria-current="page">
                                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                <span>Saved Addresses</span>
                            </a>
                            <a href="profile.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
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
                <div class="col-12 col-lg-8 col-xl-9">
                    <!-- Address Cards Grid -->
                    <div class="row g-4" id="addressCardsContainer">
                        <?php if (empty($userAddresses)): ?>
                            <div class="col-12 text-center py-5 bg-white rounded-3 shadow-sm">
                                <i class="bi bi-geo-alt text-muted display-4 d-block mb-3"></i>
                                <h2 class="h5 text-dark fw-bold mb-1">No Saved Addresses Found</h2>
                                <p class="text-muted small mb-3">Add your home or office address to enable 1-click delivery checkout.</p>
                                <button type="button" class="btn btn-auth-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                    <i class="bi bi-plus-lg me-1"></i> Add First Address
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($userAddresses as $addr): 
                                $isDef = (bool)$addr['is_default'];
                                $icon = ($addr['address_type'] === 'Home') ? 'bi-house-door' : (($addr['address_type'] === 'Work') ? 'bi-briefcase' : 'bi-geo-alt');
                            ?>
                                <div class="col-12 col-md-6" id="address-card-<?= $addr['id']; ?>">
                                    <div class="address-item-card card h-100 p-4 border-0 bg-white shadow-sm position-relative d-flex flex-column justify-content-between">
                                        <div>
                                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                                <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-2 py-1 extra-small fw-bold d-inline-flex align-items-center gap-1">
                                                    <i class="bi <?= $icon; ?>" aria-hidden="true"></i>
                                                    <span><?= htmlspecialchars($addr['address_type'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                </span>
                                                <?php if ($isDef): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle extra-small fw-semibold">
                                                        <i class="bi bi-check-circle-fill me-1"></i> Default Address
                                                    </span>
                                                <?php else: ?>
                                                    <form method="POST" action="addresses.php" class="d-inline">
            <?= csrfField(); ?>
                                                        <input type="hidden" name="action" value="set_default">
                                                        <input type="hidden" name="address_id" value="<?= $addr['id']; ?>">
                                                        <button type="submit" class="btn btn-link p-0 extra-small text-decoration-none text-muted" title="Set as default address">
                                                            Set as Default
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>

                                            <h3 class="h6 fw-bold text-dark mb-1"><?= htmlspecialchars($addr['full_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                            <p class="text-muted extra-small mb-2">
                                                <i class="bi bi-telephone text-primary-accent me-1"></i>
                                                <?= htmlspecialchars($addr['phone'], ENT_QUOTES, 'UTF-8'); ?>
                                            </p>
                                            <p class="small text-dark mb-2">
                                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                                <?= htmlspecialchars($addr['street_address'], ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars($addr['area'], ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars($addr['city'], ENT_QUOTES, 'UTF-8'); ?>
                                            </p>
                                            <?php if (!empty($addr['instructions'])): ?>
                                                <p class="extra-small text-muted bg-light p-2 rounded mb-3 border">
                                                    <i class="bi bi-info-circle text-primary-accent me-1"></i>
                                                    <em><?= htmlspecialchars($addr['instructions'], ENT_QUOTES, 'UTF-8'); ?></em>
                                                </p>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                                            <!-- Edit Button -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-pink d-inline-flex align-items-center gap-1"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editAddressModal<?= $addr['id']; ?>">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                <span>Edit</span>
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteAddressModal<?= $addr['id']; ?>">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                                <span>Delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit Modal for this Address -->
                                <div class="modal fade" id="editAddressModal<?= $addr['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <form method="POST" action="addresses.php">
            <?= csrfField(); ?>
                                                <input type="hidden" name="action" value="update_address">
                                                <input type="hidden" name="address_id" value="<?= $addr['id']; ?>">
                                                <div class="modal-header border-bottom">
                                                    <h4 class="modal-title h5 fw-bold text-dark">Edit Delivery Address</h4>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="row g-3">
                                                        <div class="col-12 col-sm-6">
                                                            <label class="form-label auth-label">Address Type</label>
                                                            <select class="form-select auth-input" name="addressType">
                                                                <option value="Home" <?= $addr['address_type'] === 'Home' ? 'selected' : ''; ?>>Home</option>
                                                                <option value="Work" <?= $addr['address_type'] === 'Work' ? 'selected' : ''; ?>>Work</option>
                                                                <option value="Other" <?= $addr['address_type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-12 col-sm-6">
                                                            <label class="form-label auth-label">City</label>
                                                            <input type="text" class="form-control auth-input" name="city" value="<?= htmlspecialchars($addr['city'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label auth-label">Recipient Full Name</label>
                                                            <input type="text" class="form-control auth-input" name="fullName" value="<?= htmlspecialchars($addr['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label auth-label">Phone Number</label>
                                                            <input type="tel" class="form-control auth-input" name="phone" value="<?= htmlspecialchars($addr['phone'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label auth-label">District / Area</label>
                                                            <input type="text" class="form-control auth-input" name="area" value="<?= htmlspecialchars($addr['area'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label auth-label">Street / Building Address</label>
                                                            <input type="text" class="form-control auth-input" name="streetAddress" value="<?= htmlspecialchars($addr['street_address'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label auth-label">Delivery Notes / Landmarks</label>
                                                            <textarea class="form-control auth-input" name="instructions" rows="2"><?= htmlspecialchars($addr['instructions'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="isDefault" value="1" id="isDef<?= $addr['id']; ?>" <?= $isDef ? 'checked' : ''; ?>>
                                                                <label class="form-check-label small text-muted user-select-none" for="isDef<?= $addr['id']; ?>">
                                                                    Set as default delivery address
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-auth-primary btn-sm">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Confirmation Modal -->
                                <div class="modal fade" id="deleteAddressModal<?= $addr['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-sm">
                                        <div class="modal-content border-0 shadow">
                                            <form method="POST" action="addresses.php">
            <?= csrfField(); ?>
                                                <input type="hidden" name="action" value="delete_address">
                                                <input type="hidden" name="address_id" value="<?= $addr['id']; ?>">
                                                <div class="modal-body text-center p-4">
                                                    <i class="bi bi-exclamation-triangle text-danger display-5 d-block mb-3"></i>
                                                    <h4 class="h6 fw-bold text-dark mb-2">Delete Address?</h4>
                                                    <p class="text-muted extra-small mb-4">Are you sure you want to remove this address? This action cannot be undone.</p>
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger btn-sm px-3">Delete</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add New Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="addresses.php">
            <?= csrfField(); ?>
                    <input type="hidden" name="action" value="create_address">
                    <div class="modal-header border-bottom">
                        <h4 class="modal-title h5 fw-bold text-dark" id="addAddressModalTitle">Add New Delivery Address</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label auth-label">Address Type</label>
                                <select class="form-select auth-input" name="addressType">
                                    <option value="Home" selected>Home</option>
                                    <option value="Work">Work</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label auth-label">City</label>
                                <input type="text" class="form-control auth-input" name="city" value="Hargeisa" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label auth-label">Recipient Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control auth-input" name="fullName" value="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label auth-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control auth-input" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label auth-label">District / Area <span class="text-danger">*</span></label>
                                <input type="text" class="form-control auth-input" name="area" placeholder="e.g. Jigjiga Yar, 26 June, City Center" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label auth-label">Street / Building Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control auth-input" name="streetAddress" placeholder="e.g. Street 24, Near Al-Nour Mosque" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label auth-label">Delivery Notes / Landmarks</label>
                                <textarea class="form-control auth-input" name="instructions" rows="2" placeholder="e.g. Ring doorbell at green gate"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="isDefault" value="1" id="isDefaultNew">
                                    <label class="form-check-label small text-muted user-select-none" for="isDefaultNew">
                                        Set as default delivery address
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-auth-primary btn-sm">Save Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Footer Component -->
    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
