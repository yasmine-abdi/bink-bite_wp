<?php
/**
 * FastBite - Admin Customers Management
 * STEP 27: Admin Customer Management & Production Hardening
 */

$pageTitle = 'Customer Management';
$activeMenu = 'customers';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../../includes/image-helper.php';

$msg = '';
$error = '';
$currentAdminId = currentUserId();

// Handle Status Mutation POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = "Security token validation failed. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';
        $targetUserId = (int)($_POST['user_id'] ?? 0);

        if ($targetUserId === $currentAdminId) {
            $error = "You cannot modify your own administrative account status.";
        } elseif ($action === 'activate' && $targetUserId > 0) {
            if (dbSetCustomerStatus($targetUserId, 'active')) {
                $msg = "Customer account activated successfully.";
            } else {
                $error = "Failed to activate customer account.";
            }
        } elseif ($action === 'deactivate' && $targetUserId > 0) {
            if (dbSetCustomerStatus($targetUserId, 'inactive')) {
                $msg = "Customer account deactivated successfully.";
            } else {
                $error = "Failed to deactivate customer account.";
            }
        }
    }
}

// 6 Summary Metrics
$metrics = dbGetCustomerMetrics();

// Search & Filter
$filter = $_GET['filter'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');

$filterParams = ['search' => $searchQuery];
if ($filter === 'active' || $filter === 'inactive') {
    $filterParams['status'] = $filter;
} elseif ($filter === 'has_orders') {
    $filterParams['order_filter'] = 'has_orders';
} elseif ($filter === 'no_orders') {
    $filterParams['order_filter'] = 'no_orders';
}

$allFilteredCustomers = dbGetAllCustomers($filterParams);
$totalRecords = count($allFilteredCustomers);

// Pagination (20 per page)
$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalPages = max(1, (int)ceil($totalRecords / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;
$customers = array_slice($allFilteredCustomers, $offset, $perPage);
?>

<!-- Admin Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5 bg-light">
    <div class="container-fluid p-0">

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent" style="font-size: 0.75rem; letter-spacing: 0.08em;">USER MANAGEMENT</span>
                <h1 class="admin-title fw-bold mb-1 text-dark">Customer Directory</h1>
                <p class="admin-desc text-muted mb-0 small">
                    Monitor registered customer profiles, review order frequencies, and safely manage account access statuses.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="../orders/index.php" class="btn btn-outline-pink btn-sm d-inline-flex align-items-center gap-1 shadow-sm rounded-pill px-3" title="View & Print All Customer Order Receipts">
                    <i class="bi bi-receipt"></i>
                    <span>Order Receipts</span>
                </a>
                <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 shadow-sm rounded-pill px-3" onclick="window.print();">
                    <i class="bi bi-printer"></i>
                    <span>Print Directory</span>
                </button>
                <a href="../reports/index.php" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2 shadow-sm rounded-pill px-3">
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                    <span>Customer Spending Report</span>
                </a>
            </div>
        </div>

        <!-- Flash Feedback -->
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- 6 KPI Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-dark">
                    <span class="text-muted extra-small text-uppercase fw-bold">Total</span>
                    <div class="h3 fw-bold text-dark my-1"><?= $metrics['total']; ?></div>
                    <span class="extra-small text-muted">All registered</span>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-success">
                    <span class="text-muted extra-small text-uppercase fw-bold">Active</span>
                    <div class="h3 fw-bold text-success my-1"><?= $metrics['active']; ?></div>
                    <span class="extra-small text-muted">Permitted to log in</span>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-danger">
                    <span class="text-muted extra-small text-uppercase fw-bold">Inactive</span>
                    <div class="h3 fw-bold text-danger my-1"><?= $metrics['inactive']; ?></div>
                    <span class="extra-small text-muted">Access suspended</span>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-primary">
                    <span class="text-muted extra-small text-uppercase fw-bold">New Today</span>
                    <div class="h3 fw-bold text-primary my-1">+<?= $metrics['new_today']; ?></div>
                    <span class="extra-small text-muted">Joined today</span>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-info">
                    <span class="text-muted extra-small text-uppercase fw-bold">With Orders</span>
                    <div class="h3 fw-bold text-info my-1"><?= $metrics['with_orders']; ?></div>
                    <span class="extra-small text-muted">Active buyers</span>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-secondary">
                    <span class="text-muted extra-small text-uppercase fw-bold">No Orders</span>
                    <div class="h3 fw-bold text-secondary my-1"><?= $metrics['without_orders']; ?></div>
                    <span class="extra-small text-muted">Zero orders yet</span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    
                    <!-- Filter Pills -->
                    <div class="d-flex flex-wrap gap-2">
                        <a href="index.php?filter=all<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn btn-sm rounded-pill <?= $filter === 'all' ? 'btn-primary-accent text-white' : 'btn-white border text-dark'; ?>">
                            All (<?= $metrics['total']; ?>)
                        </a>
                        <a href="index.php?filter=active<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn btn-sm rounded-pill <?= $filter === 'active' ? 'btn-success text-white' : 'btn-white border text-dark'; ?>">
                            Active (<?= $metrics['active']; ?>)
                        </a>
                        <a href="index.php?filter=inactive<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn btn-sm rounded-pill <?= $filter === 'inactive' ? 'btn-danger text-white' : 'btn-white border text-dark'; ?>">
                            Inactive (<?= $metrics['inactive']; ?>)
                        </a>
                        <a href="index.php?filter=has_orders<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn btn-sm rounded-pill <?= $filter === 'has_orders' ? 'btn-info text-white' : 'btn-white border text-dark'; ?>">
                            Has Orders (<?= $metrics['with_orders']; ?>)
                        </a>
                        <a href="index.php?filter=no_orders<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn btn-sm rounded-pill <?= $filter === 'no_orders' ? 'btn-secondary text-white' : 'btn-white border text-dark'; ?>">
                            No Orders (<?= $metrics['without_orders']; ?>)
                        </a>
                    </div>

                    <!-- Search Input -->
                    <form action="index.php" method="GET" class="d-flex gap-2" style="max-width: 360px;">
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter); ?>">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, email, phone..." value="<?= htmlspecialchars($searchQuery); ?>">
                        <button type="submit" class="btn btn-primary-accent btn-sm rounded-pill px-3 text-white">Search</button>
                        <?php if (!empty($searchQuery) || $filter !== 'all'): ?>
                            <a href="index.php" class="btn btn-light btn-sm rounded-pill border">Reset</a>
                        <?php endif; ?>
                    </form>

                </div>
            </div>
        </div>

        <!-- Customers Table Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <h5 class="fw-bold text-dark mb-0">Customer Accounts (<?= $totalRecords; ?>)</h5>
                <span class="text-muted extra-small">Page <?= $page; ?> of <?= $totalPages; ?></span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th>Orders</th>
                            <th>Lifetime Spending</th>
                            <th>Last Order Date</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-2 d-block mb-2 text-secondary"></i>
                                    No customers found matching the criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $c): 
                                $avatar = function_exists('getAvatarImage') ? getAvatarImage($c['avatar'] ?? '') : 'assets/images/placeholders/avatar-placeholder.jpg';
                                $isActive = ($c['status'] === 'active');
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= htmlspecialchars($avatar); ?>" alt="<?= htmlspecialchars($c['name']); ?>" class="rounded-circle object-fit-cover border shadow-sm" style="width: 40px; height: 40px;" onerror="handleImageError(this, 'avatar')">
                                            <div>
                                                <a href="view.php?id=<?= (int)$c['id']; ?>" class="fw-bold text-dark text-decoration-none small d-block">
                                                    <?= htmlspecialchars($c['name']); ?>
                                                </a>
                                                <span class="text-muted extra-small">ID: #<?= (int)$c['id']; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small text-muted">
                                        <?= htmlspecialchars($c['email']); ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= htmlspecialchars($c['phone'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= date('M d, Y', strtotime($c['created_at'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle extra-small fw-bold">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small fw-bold">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border extra-small fw-bold"><?= (int)$c['total_orders']; ?> order(s)</span>
                                    </td>
                                    <td class="fw-bold text-primary-accent small">
                                        $<?= number_format((float)$c['total_spent'], 2); ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= !empty($c['last_order_date']) ? date('M d, Y', strtotime($c['last_order_date'])) : '<span class="text-muted opacity-50">Never</span>'; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <a href="view.php?id=<?= (int)$c['id']; ?>" class="btn btn-outline-pink btn-sm p-1 px-2 extra-small fw-semibold" title="View Customer Profile & Orders">
                                                <i class="bi bi-person-badge"></i> Profile
                                            </a>
                                            <a href="statement.php?id=<?= (int)$c['id']; ?>&print=1" target="_blank" class="btn btn-outline-dark btn-sm p-1 px-2 extra-small" title="Print Customer Statement / Receipt">
                                                <i class="bi bi-receipt text-primary-accent"></i> Receipt
                                            </a>
                                            <a href="statement.php?id=<?= (int)$c['id']; ?>&format=thermal&print=1" target="_blank" class="btn btn-outline-secondary btn-sm p-1 px-2 extra-small" title="Print 80mm Thermal Slip">
                                                <i class="bi bi-receipt-cutoff"></i> Thermal
                                            </a>
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-light btn-sm p-1 rounded-circle border" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 28px; height: 28px;" title="More options">
                                                    <i class="bi bi-three-dots-vertical extra-small"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <li>
                                                        <a class="dropdown-item py-1 px-3 small" href="view.php?id=<?= (int)$c['id']; ?>">
                                                            <i class="bi bi-person-badge me-2 text-primary"></i>View Full Profile
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-1 px-3 small" href="statement.php?id=<?= (int)$c['id']; ?>" target="_blank">
                                                            <i class="bi bi-receipt me-2 text-primary-accent"></i>View Official Statement
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-1 px-3 small" href="statement.php?id=<?= (int)$c['id']; ?>&print=1" target="_blank">
                                                            <i class="bi bi-printer me-2 text-dark"></i>Print Statement (A4)
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-1 px-3 small" href="statement.php?id=<?= (int)$c['id']; ?>&format=thermal&print=1" target="_blank">
                                                            <i class="bi bi-receipt-cutoff me-2 text-secondary"></i>Print Thermal Slip (80mm)
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-1 px-3 small" href="../orders/index.php?search=<?= urlencode($c['email']); ?>">
                                                            <i class="bi bi-bag-check me-2 text-info"></i>View Customer Orders &amp; Receipts
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <?php if ($isActive): ?>
                                                            <form action="index.php?filter=<?= urlencode($filter); ?>&search=<?= urlencode($searchQuery); ?>&page=<?= $page; ?>" method="POST" class="p-0" onsubmit="return confirm('Deactivate account for <?= htmlspecialchars(addslashes($c['name'])); ?>? They will not be able to log in.');">
                                                                <?= csrfField(); ?>
                                                                <input type="hidden" name="action" value="deactivate">
                                                                <input type="hidden" name="user_id" value="<?= (int)$c['id']; ?>">
                                                                <button type="submit" class="dropdown-item py-1 px-3 small text-danger">
                                                                    <i class="bi bi-person-x me-2"></i>Deactivate Account
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form action="index.php?filter=<?= urlencode($filter); ?>&search=<?= urlencode($searchQuery); ?>&page=<?= $page; ?>" method="POST" class="p-0" onsubmit="return confirm('Activate account for <?= htmlspecialchars(addslashes($c['name'])); ?>?');">
                                                                <?= csrfField(); ?>
                                                                <input type="hidden" name="action" value="activate">
                                                                <input type="hidden" name="user_id" value="<?= (int)$c['id']; ?>">
                                                                <button type="submit" class="dropdown-item py-1 px-3 small text-success">
                                                                    <i class="bi bi-person-check me-2"></i>Activate Account
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="text-muted extra-small">
                        Showing <?= $offset + 1; ?> to <?= min($totalRecords, $offset + $perPage); ?> of <?= $totalRecords; ?> customers
                    </span>
                    <nav aria-label="Customer list pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-pill px-3 me-1" href="index.php?filter=<?= urlencode($filter); ?>&search=<?= urlencode($searchQuery); ?>&page=<?= $page - 1; ?>">Previous</a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= ($p === $page) ? 'active' : ''; ?>">
                                    <a class="page-link rounded-circle text-center mx-1" style="width: 32px; height: 32px; line-height: 20px;" href="index.php?filter=<?= urlencode($filter); ?>&search=<?= urlencode($searchQuery); ?>&page=<?= $p; ?>"><?= $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-pill px-3 ms-1" href="index.php?filter=<?= urlencode($filter); ?>&search=<?= urlencode($searchQuery); ?>&page=<?= $page + 1; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>

        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
