<?php
/**
 * FastBite - Admin User Profile & Security Details View
 * STEP 27: Admin Users, Roles & Security Management
 */

$activeMenu = 'users';
$pageTitle = 'User Details';

require_once dirname(__DIR__) . '/includes/header.php';

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    header("Location: index.php?error=" . urlencode("Invalid user ID specified."));
    exit;
}

$userDetails = dbGetUserDetails($userId);
if (!$userDetails) {
    header("Location: index.php?error=" . urlencode("User not found."));
    exit;
}

$u = $userDetails['profile'];
$kpi = $userDetails['kpi'];
$orders = $userDetails['orders'];
$addresses = $userDetails['addresses'];
$unreadNotifs = $userDetails['unread_notifications'];
$secLogs = $userDetails['security_logs'];

$userAvatar = !empty($u['avatar']) ? getAvatarImage($u['avatar']) : 'assets/images/placeholders/avatar-placeholder.jpg';
$activeAdminsCount = dbCountActiveAdmins();
$isLastActiveAdmin = ($u['role'] === 'admin' && $u['status'] === 'active' && $activeAdminsCount <= 1);
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!-- Main Content -->
<main class="admin-main p-3 p-lg-4 flex-grow-1">
    <div class="container-fluid p-0">
        
        <!-- Navigation Breadcrumbs -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-muted text-decoration-none">Users Management</a></li>
                    <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></li>
                </ol>
            </nav>
            <div class="d-flex gap-2">
                <a href="edit.php?id=<?= $u['id']; ?>" class="btn btn-outline-pink btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-pencil"></i>
                    <span>Edit Profile</span>
                </a>
                <a href="reset-password.php?id=<?= $u['id']; ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-key"></i>
                    <span>Reset Password</span>
                </a>
                <a href="index.php" class="btn btn-light btn-sm border">Back to Users</a>
            </div>
        </div>

        <!-- Feedback Alerts -->
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 mb-4" role="alert">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <div><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Header Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                    <div class="d-flex align-items-center gap-3">
                        <img src="<?= $relRoot . htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8'); ?>" 
                             alt="Avatar" 
                             class="rounded-circle border p-1" 
                             style="width: 72px; height: 72px; object-fit: cover;">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h2 class="h4 fw-bold text-dark mb-0"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle"><i class="bi bi-shield-lock me-1"></i> Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border"><i class="bi bi-person me-1"></i> Customer</span>
                                <?php endif; ?>
                                <?php if ($u['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i> Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger"><i class="bi bi-dash-circle me-1"></i> Inactive</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small d-flex flex-wrap gap-3">
                                <span><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($u['phone'] ?: 'No phone provided', ENT_QUOTES, 'UTF-8'); ?></span>
                                <span><i class="bi bi-calendar3 me-1"></i>Member since <?= date('M d, Y', strtotime($u['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($isLastActiveAdmin): ?>
                        <div class="badge bg-warning-subtle text-warning-emphasis p-2 border border-warning-subtle text-start">
                            <i class="bi bi-shield-fill-check me-1"></i> Protected: Sole Active Admin
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Order Statistics KPIs (Section B) -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <span class="text-muted extra-small fw-semibold text-uppercase d-block">Total Orders</span>
                    <h3 class="fw-bold text-dark mb-0 fs-4"><?= (int)$kpi['total_orders']; ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <span class="text-muted extra-small fw-semibold text-uppercase d-block">Lifetime Spending</span>
                    <h3 class="fw-bold text-primary-accent mb-0 fs-4">$<?= number_format((float)$kpi['lifetime_spent'], 2); ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <span class="text-muted extra-small fw-semibold text-uppercase d-block">Avg Order Value</span>
                    <h3 class="fw-bold text-dark mb-0 fs-4">$<?= number_format((float)$kpi['avg_order_value'], 2); ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <span class="text-muted extra-small fw-semibold text-uppercase d-block">Completed Orders</span>
                    <h3 class="fw-bold text-success mb-0 fs-4"><?= (int)$kpi['completed_orders']; ?></h3>
                </div>
            </div>
        </div>

        <div class="row g-4">
            
            <!-- Left Column: User Information & Addresses -->
            <div class="col-12 col-lg-5">
                
                <!-- Section A: User Information -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <h5 class="fw-bold text-dark mb-0 fs-6"><i class="bi bi-person-lines-fill me-2 text-primary-accent"></i>User Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <ul class="list-group list-group-flush extra-small">
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted">User ID</span>
                                <span class="fw-semibold text-dark">#<?= $u['id']; ?></span>
                            </li>
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted">Full Name</span>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted">Email Address</span>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted">Phone Number</span>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($u['phone'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted">Date of Birth</span>
                                <span class="fw-semibold text-dark"><?= !empty($u['date_of_birth']) ? date('M d, Y', strtotime($u['date_of_birth'])) : 'Not provided'; ?></span>
                            </li>
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted">Gender</span>
                                <span class="fw-semibold text-dark"><?= ucfirst($u['gender'] ?? 'Unspecified'); ?></span>
                            </li>
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted">Language</span>
                                <span class="fw-semibold text-dark text-uppercase"><?= htmlspecialchars($u['preferred_language'] ?? 'en', ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted">Registered At</span>
                                <span class="fw-semibold text-dark"><?= date('Y-m-d H:i', strtotime($u['created_at'])); ?></span>
                            </li>
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted">Last Profile Update</span>
                                <span class="fw-semibold text-dark"><?= !empty($u['updated_at']) ? date('Y-m-d H:i', strtotime($u['updated_at'])) : 'Never'; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Section D: Saved Addresses -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="fw-bold text-dark mb-0 fs-6"><i class="bi bi-geo-alt-fill me-2 text-primary-accent"></i>Saved Addresses (<?= count($addresses); ?>)</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($addresses)): ?>
                            <p class="text-muted extra-small mb-0">No delivery addresses saved by this user.</p>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($addresses as $addr): ?>
                                    <div class="p-3 border rounded-3 bg-light extra-small">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($addr['address_type'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php if (!empty($addr['is_default'])): ?>
                                                <span class="badge bg-pink-soft text-primary-accent">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted"><?= htmlspecialchars($addr['street_address'] . ', ' . $addr['area'] . ', ' . $addr['city'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php if (!empty($addr['instructions'])): ?>
                                            <div class="text-secondary mt-1 fst-italic">"<?= htmlspecialchars($addr['instructions'], ENT_QUOTES, 'UTF-8'); ?>"</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section F: Security Information -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <h5 class="fw-bold text-dark mb-0 fs-6"><i class="bi bi-shield-check me-2 text-primary-accent"></i>Security & Access Information</h5>
                    </div>
                    <div class="card-body p-4 extra-small">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Account Status:</span>
                            <span class="fw-semibold <?= ($u['status'] === 'active') ? 'text-success' : 'text-danger'; ?>"><?= ucfirst($u['status']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">System Role:</span>
                            <span class="fw-semibold text-dark"><?= ucfirst($u['role']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Password State:</span>
                            <span class="fw-semibold text-muted">Bcrypt Hash (Secure)</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Sole Active Admin:</span>
                            <span class="fw-semibold <?= $isLastActiveAdmin ? 'text-warning' : 'text-muted'; ?>"><?= $isLastActiveAdmin ? 'Yes (Protected)' : 'No'; ?></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Recent Orders & Security Activity Logs -->
            <div class="col-12 col-lg-7">
                
                <!-- Section C: Recent Orders -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="fw-bold text-dark mb-0 fs-6"><i class="bi bi-bag-check-fill me-2 text-primary-accent"></i>Recent Orders</h5>
                        <span class="extra-small text-muted"><?= count($orders); ?> orders</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($orders)): ?>
                            <div class="p-4 text-center text-muted extra-small">No orders placed by this user yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 extra-small">
                                    <thead class="table-light text-muted text-uppercase">
                                        <tr>
                                            <th class="ps-3">Order #</th>
                                            <th>Date</th>
                                            <th>Items</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th class="text-end pe-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $ord): ?>
                                            <tr>
                                                <td class="ps-3 fw-bold">
                                                    <a href="../orders/view.php?id=<?= $ord['id']; ?>" class="text-dark text-decoration-none hover-pink">
                                                        <?= htmlspecialchars($ord['order_number'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </a>
                                                </td>
                                                <td class="text-muted"><?= date('M d, Y', strtotime($ord['created_at'])); ?></td>
                                                <td><?= (int)$ord['item_count']; ?> item(s)</td>
                                                <td class="fw-bold text-primary-accent">$<?= number_format((float)$ord['total'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?= ucfirst($ord['order_status']); ?></span>
                                                </td>
                                                <td class="text-end pe-3">
                                                    <a href="../orders/view.php?id=<?= $ord['id']; ?>" class="btn btn-outline-pink btn-sm py-0 px-2 extra-small">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section E: Account Activity & Security Logs -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="fw-bold text-dark mb-0 fs-6"><i class="bi bi-clock-history me-2 text-primary-accent"></i>Recent Security Activity</h5>
                        <a href="../security/index.php?user_id=<?= $u['id']; ?>" class="btn btn-link text-primary-accent p-0 extra-small text-decoration-none">View All in Security Center &rarr;</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($secLogs)): ?>
                            <div class="p-4 text-center text-muted extra-small">No logged security events for this user.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 extra-small">
                                    <thead class="table-light text-muted text-uppercase">
                                        <tr>
                                            <th class="ps-3">Time</th>
                                            <th>Action</th>
                                            <th>Description</th>
                                            <th class="text-end pe-3">IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($secLogs as $log): ?>
                                            <tr>
                                                <td class="ps-3 text-muted"><?= date('M d, H:i', strtotime($log['created_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-light text-secondary border"><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-end pe-3 text-muted"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>

    </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
