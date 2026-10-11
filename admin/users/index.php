<?php
/**
 * FastBite - Admin Users & Roles Management Directory
 * STEP 27: Admin Users, Roles & Security Management
 */

$activeMenu = 'users';
$pageTitle = 'Users Management';

require_once dirname(__DIR__) . '/includes/header.php';

$currentAdmin = currentUser();
$error = $_GET['error'] ?? '';
$msg = $_GET['msg'] ?? '';

// Handle POST State-Changing Actions with CSRF Protection
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security check failed. Please refresh the page and try again.';
    } else {
        $postAction = $_POST['action'] ?? '';
        $targetUserId = (int)($_POST['user_id'] ?? 0);

        if ($targetUserId <= 0) {
            $error = 'Invalid user specified.';
        } elseif ($postAction === 'toggle_status') {
            $newStatus = trim($_POST['status'] ?? '');
            $result = dbSetUserStatus($targetUserId, $newStatus, $currentAdmin['id']);
            if ($result['success']) {
                $msg = "User status successfully updated to '$newStatus'.";
            } else {
                $error = $result['error'] ?? 'Failed to update user status.';
            }
        } elseif ($postAction === 'change_role') {
            $newRole = trim($_POST['role'] ?? '');
            $result = dbChangeUserRole($targetUserId, $newRole, $currentAdmin['id']);
            if ($result['success']) {
                $msg = "User role successfully changed to '$newRole'.";
            } else {
                $error = $result['error'] ?? 'Failed to update user role.';
            }
        } elseif ($postAction === 'delete_user') {
            $result = dbDeleteUserSafely($targetUserId, $currentAdmin['id']);
            if ($result['success']) {
                $msg = 'User account deleted successfully.';
            } else {
                $error = $result['error'] ?? 'Failed to delete user account.';
            }
        }
    }
}

// Fetch KPIs
$metrics = dbGetUsersMetrics();

// Filtering and Pagination
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'role' => trim($_GET['role'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'date_range' => trim($_GET['date_range'] ?? '')
];

$sort = trim($_GET['sort'] ?? 'created_at');
$order = trim($_GET['order'] ?? 'DESC');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$usersData = dbGetAllUsers($filters, $limit, $offset, $sort, $order);
$users = $usersData['users'];
$totalUsersCount = $usersData['total'];
$totalPages = ceil($totalUsersCount / $limit);
$activeAdminsCount = dbCountActiveAdmins();
?>

<!-- Main Content -->
<main class="admin-main p-3 p-lg-4 flex-grow-1">
    <div class="container-fluid p-0">
        
        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Users Management</h1>
                <p class="text-muted small mb-0">Manage customer accounts, assign system roles, and monitor administrative security.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="../security/index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check"></i>
                    <span>Security Center</span>
                </a>
            </div>
        </div>

        <!-- Feedback Alerts -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2" role="alert">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <div><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- KPI Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2dot4">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-pink-soft text-primary-accent rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted extra-small fw-semibold d-block text-uppercase">Total Users</span>
                            <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['total_users']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2dot4">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted extra-small fw-semibold d-block text-uppercase">Active Users</span>
                            <h3 class="fw-bold text-success mb-0 fs-4"><?= number_format($metrics['active_users']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2dot4">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-danger-subtle text-danger rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-person-x-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted extra-small fw-semibold d-block text-uppercase">Inactive</span>
                            <h3 class="fw-bold text-danger mb-0 fs-4"><?= number_format($metrics['inactive_users']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-6 col-xl-2dot4">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-person-lines-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted extra-small fw-semibold d-block text-uppercase">Customers</span>
                            <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['customers']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-2dot4">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-warning-subtle text-warning-emphasis rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted extra-small fw-semibold d-block text-uppercase">Admins</span>
                            <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['administrators']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3 p-lg-4">
                <form method="GET" action="index.php" class="row g-2 g-md-3 align-items-center">
                    
                    <div class="col-12 col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Search name, email, phone..." value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <div class="col-6 col-md-2">
                        <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Roles</option>
                            <option value="customer" <?= ($filters['role'] === 'customer') ? 'selected' : ''; ?>>Customer</option>
                            <option value="admin" <?= ($filters['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="active" <?= ($filters['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?= ($filters['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <select name="date_range" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Registrations</option>
                            <option value="today" <?= ($filters['date_range'] === 'today') ? 'selected' : ''; ?>>Today</option>
                            <option value="this_week" <?= ($filters['date_range'] === 'this_week') ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="this_month" <?= ($filters['date_range'] === 'this_month') ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="this_year" <?= ($filters['date_range'] === 'this_year') ? 'selected' : ''; ?>>This Year</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary-accent btn-sm flex-grow-1">Filter</button>
                        <a href="index.php" class="btn btn-light btn-sm border" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Directory Table & Cards -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-3 px-lg-4 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark">Directory (<?= number_format($totalUsersCount); ?> total)</span>
                <span class="badge bg-light text-muted border">Page <?= $page; ?> of <?= max(1, $totalPages); ?></span>
            </div>

            <?php if (empty($users)): ?>
                <div class="p-5 text-center">
                    <i class="bi bi-people display-4 text-muted mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">No users match your criteria</h5>
                    <p class="text-muted small">Try modifying your search or filter settings.</p>
                    <a href="index.php" class="btn btn-outline-pink btn-sm">Clear Filters</a>
                </div>
            <?php else: ?>
                <!-- Desktop / Tablet Table View -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light extra-small text-uppercase text-muted">
                            <tr>
                                <th class="ps-4">User</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Joined</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <?php
                                $userAvatar = !empty($u['avatar']) ? getAvatarImage($u['avatar']) : 'assets/images/placeholders/avatar-placeholder.jpg';
                                $isLastActiveAdmin = ($u['role'] === 'admin' && $u['status'] === 'active' && $activeAdminsCount <= 1);
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= $relRoot . htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8'); ?>" 
                                                 alt="Avatar" 
                                                 class="rounded-circle border flex-shrink-0" 
                                                 style="width: 38px; height: 38px; object-fit: cover;">
                                            <div>
                                                <a href="view.php?id=<?= $u['id']; ?>" class="fw-bold text-dark text-decoration-none d-block hover-pink">
                                                    <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                                <span class="extra-small text-muted">ID: #<?= $u['id']; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-block small text-dark"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="extra-small text-muted"><?= htmlspecialchars($u['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-2 py-1"><i class="bi bi-shield-lock me-1"></i> Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border px-2 py-1"><i class="bi bi-person me-1"></i> Customer</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($u['status'] === 'active'): ?>
                                            <span class="badge bg-success-subtle text-success px-2 py-1"><i class="bi bi-check-circle me-1"></i> Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger px-2 py-1"><i class="bi bi-dash-circle me-1"></i> Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= (int)$u['total_orders']; ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-primary-accent">$<?= number_format((float)$u['total_spent'], 2); ?></span>
                                    </td>
                                    <td>
                                        <span class="extra-small text-muted d-block"><?= date('M d, Y', strtotime($u['created_at'])); ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                <li>
                                                    <a class="dropdown-item py-1 extra-small" href="view.php?id=<?= $u['id']; ?>">
                                                        <i class="bi bi-eye text-primary me-2"></i> View Profile
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-1 extra-small" href="edit.php?id=<?= $u['id']; ?>">
                                                        <i class="bi bi-pencil text-secondary me-2"></i> Edit User
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-1 extra-small" href="reset-password.php?id=<?= $u['id']; ?>">
                                                        <i class="bi bi-key text-warning me-2"></i> Reset Password
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                
                                                <!-- Role Toggle -->
                                                <li>
                                                    <button type="button" class="dropdown-item py-1 extra-small" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#roleModal<?= $u['id']; ?>">
                                                        <i class="bi bi-arrow-left-right text-info me-2"></i> Change Role
                                                    </button>
                                                </li>

                                                <!-- Status Toggle -->
                                                <li>
                                                    <button type="button" class="dropdown-item py-1 extra-small" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#statusModal<?= $u['id']; ?>">
                                                        <?php if ($u['status'] === 'active'): ?>
                                                            <i class="bi bi-slash-circle text-danger me-2"></i> Deactivate Account
                                                        <?php else: ?>
                                                            <i class="bi bi-check-circle text-success me-2"></i> Activate Account
                                                        <?php endif; ?>
                                                    </button>
                                                </li>

                                                <li><hr class="dropdown-divider my-1"></li>

                                                <!-- Delete Option -->
                                                <li>
                                                    <button type="button" class="dropdown-item py-1 extra-small text-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteModal<?= $u['id']; ?>">
                                                        <i class="bi bi-trash me-2"></i> Delete User
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Modals for this User -->
                                        <!-- Role Modal -->
                                        <div class="modal fade text-start" id="roleModal<?= $u['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header border-bottom">
                                                        <h5 class="modal-title fw-bold fs-6">Change Role for <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="POST" action="index.php">
                                                        <?= csrfField(); ?>
                                                        <input type="hidden" name="action" value="change_role">
                                                        <input type="hidden" name="user_id" value="<?= $u['id']; ?>">
                                                        <div class="modal-body">
                                                            <?php if ($isLastActiveAdmin): ?>
                                                                <div class="alert alert-warning py-2 extra-small">
                                                                    <i class="bi bi-shield-slash-fill me-1"></i>
                                                                    <strong>Last Admin Account:</strong> This user is currently the only active administrator. You cannot change their role to Customer.
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="alert alert-info py-2 extra-small">
                                                                    <i class="bi bi-info-circle-fill me-1"></i>
                                                                    Changing a user's role alters the permissions they have throughout the FastBite system.
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold">Select New Role</label>
                                                                <select name="role" class="form-select form-select-sm" <?= $isLastActiveAdmin ? 'disabled' : ''; ?>>
                                                                    <option value="customer" <?= ($u['role'] === 'customer') ? 'selected' : ''; ?>>Customer (Standard Storefront Access)</option>
                                                                    <option value="admin" <?= ($u['role'] === 'admin') ? 'selected' : ''; ?>>Administrator (Full Control Center Access)</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-top">
                                                            <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-sm btn-primary-accent" <?= $isLastActiveAdmin ? 'disabled' : ''; ?>>Confirm Role Change</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Status Modal -->
                                        <div class="modal fade text-start" id="statusModal<?= $u['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header border-bottom">
                                                        <h5 class="modal-title fw-bold fs-6">
                                                            <?= ($u['status'] === 'active') ? 'Deactivate Account' : 'Activate Account'; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="POST" action="index.php">
                                                        <?= csrfField(); ?>
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?= $u['id']; ?>">
                                                        <input type="hidden" name="status" value="<?= ($u['status'] === 'active') ? 'inactive' : 'active'; ?>">
                                                        <div class="modal-body">
                                                            <?php if ($u['status'] === 'active'): ?>
                                                                <?php if ($isLastActiveAdmin): ?>
                                                                    <div class="alert alert-danger py-2 extra-small">
                                                                        <i class="bi bi-shield-exclamation me-1"></i>
                                                                        You cannot deactivate the last active administrator account.
                                                                    </div>
                                                                <?php else: ?>
                                                                    <p class="small text-muted mb-2">Are you sure you want to deactivate <strong><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></strong>?</p>
                                                                    <p class="extra-small text-danger mb-0"><i class="bi bi-exclamation-circle me-1"></i> This user will no longer be able to log in until their account is activated again. Order history remains intact.</p>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <p class="small text-muted mb-0">Reactivating this account will immediately restore login privileges for <strong><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer border-top">
                                                            <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-sm <?= ($u['status'] === 'active') ? 'btn-danger' : 'btn-success'; ?>" <?= ($u['status'] === 'active' && $isLastActiveAdmin) ? 'disabled' : ''; ?>>
                                                                <?= ($u['status'] === 'active') ? 'Deactivate Account' : 'Activate Account'; ?>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete Modal -->
                                        <div class="modal fade text-start" id="deleteModal<?= $u['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header border-bottom">
                                                        <h5 class="modal-title fw-bold fs-6 text-danger">Delete User Account</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="POST" action="index.php">
                                                        <?= csrfField(); ?>
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?= $u['id']; ?>">
                                                        <div class="modal-body">
                                                            <?php if ($isLastActiveAdmin): ?>
                                                                <div class="alert alert-danger py-2 extra-small">
                                                                    <i class="bi bi-shield-x me-1"></i>
                                                                    You cannot delete the last active administrator account.
                                                                </div>
                                                            <?php elseif ($u['total_orders'] > 0): ?>
                                                                <div class="alert alert-warning py-2 extra-small">
                                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                                    <strong>Historical Order Integrity:</strong> This user has <strong><?= (int)$u['total_orders']; ?> order(s)</strong> on record. The system prevents deleting users with orders to protect order records. Please use <strong>Deactivate Account</strong> instead.
                                                                </div>
                                                            <?php else: ?>
                                                                <p class="small text-muted mb-2">Are you sure you want to permanently delete user <strong><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></strong> (<?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?>)?</p>
                                                                <p class="extra-small text-danger mb-0">This action cannot be undone.</p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer border-top">
                                                            <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-sm btn-danger" <?= ($isLastActiveAdmin || $u['total_orders'] > 0) ? 'disabled' : ''; ?>>
                                                                Delete Permanently
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards View -->
                <div class="d-md-none p-3 d-flex flex-column gap-3">
                    <?php foreach ($users as $u): ?>
                        <?php
                        $userAvatar = !empty($u['avatar']) ? getAvatarImage($u['avatar']) : 'assets/images/placeholders/avatar-placeholder.jpg';
                        $isLastActiveAdmin = ($u['role'] === 'admin' && $u['status'] === 'active' && $activeAdminsCount <= 1);
                        ?>
                        <div class="card border rounded-3 p-3 bg-light">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= $relRoot . htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="Avatar" 
                                         class="rounded-circle border" 
                                         style="width: 40px; height: 40px; object-fit: cover;">
                                    <div>
                                        <a href="view.php?id=<?= $u['id']; ?>" class="fw-bold text-dark text-decoration-none d-block">
                                            <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                        <span class="extra-small text-muted"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>
                                <div>
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success extra-small">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger extra-small">Inactive</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row g-2 extra-small text-muted py-2 border-top border-bottom my-2">
                                <div class="col-6">
                                    <span class="d-block text-uppercase fw-semibold">Role</span>
                                    <span class="text-dark fw-bold"><?= ucfirst($u['role']); ?></span>
                                </div>
                                <div class="col-6">
                                    <span class="d-block text-uppercase fw-semibold">Orders / Spent</span>
                                    <span class="text-primary-accent fw-bold"><?= (int)$u['total_orders']; ?> orders ($<?= number_format((float)$u['total_spent'], 2); ?>)</span>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-2">
                                <a href="view.php?id=<?= $u['id']; ?>" class="btn btn-light btn-sm border flex-grow-1 extra-small">View Profile</a>
                                <a href="edit.php?id=<?= $u['id']; ?>" class="btn btn-outline-pink btn-sm flex-grow-1 extra-small">Edit</a>
                                <a href="reset-password.php?id=<?= $u['id']; ?>" class="btn btn-outline-secondary btn-sm extra-small"><i class="bi bi-key"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer bg-white py-3 px-4 border-top d-flex justify-content-between align-items-center">
                        <span class="extra-small text-muted">Showing <?= $offset + 1; ?>–<?= min($offset + $limit, $totalUsersCount); ?> of <?= $totalUsersCount; ?> users</span>
                        <nav aria-label="Users pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Prev</a>
                                </li>
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <li class="page-item <?= ($p === $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])); ?>"><?= $p; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>

    </div>
</main>

<style>
.col-xl-2dot4 {
    flex: 0 0 auto;
    width: 20%;
}
@media (max-width: 1199.98px) {
    .col-xl-2dot4 {
        width: 33.3333%;
    }
}
@media (max-width: 767.98px) {
    .col-xl-2dot4 {
        width: 50%;
    }
}
</style>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
