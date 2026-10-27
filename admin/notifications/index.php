<?php
/**
 * FastBite - Admin Notification & Broadcast Center Dashboard
 * STEP 28: FastBite Notifications & Communication Center
 */

$activeMenu = 'notifications';
$pageTitle = 'Notification & Communication Center';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$currentAdmin = currentUser();
$error = $_GET['error'] ?? '';
$msg = $_GET['msg'] ?? '';

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $notifId = (int)($_POST['notif_id'] ?? 0);

        if ($action === 'delete_notification' && $notifId > 0) {
            dbDeleteNotification($notifId);
            $msg = 'Notification record deleted successfully.';
        }
    }
}

// KPI Metrics
$metrics = dbGetAdminNotificationMetrics();

// Filtering and Pagination
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'type' => trim($_GET['type'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'date_range' => trim($_GET['date_range'] ?? '')
];

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$adminNotifsData = dbGetAllNotificationsAdmin($filters, $limit, $offset);
$notifsList = $adminNotifsData['notifications'];
$totalCount = $adminNotifsData['total'];
$totalPages = ceil($totalCount / $limit);
?>

<!-- Main Content -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0">
        
        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent" style="font-size: 0.75rem; letter-spacing: 0.08em;">COMMUNICATION CENTER</span>
                <h1 class="admin-title fw-bold mb-1 text-dark">Notification &amp; Communication Center</h1>
                <p class="admin-desc text-muted mb-0 small">Broadcast promotional announcements, audit customer notifications, and monitor delivery status.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="create.php" class="btn btn-auth-primary btn-sm d-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-send-fill"></i>
                    <span>Send Notification</span>
                </a>
            </div>
        </div>

        <!-- Feedback Alerts -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <div class="small"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <div class="small"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- KPI Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 stat-hover-card border-start border-4 border-dark">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-pink-soft text-primary-accent rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted extra-small fw-semibold d-block text-uppercase">Total Sent</span>
                            <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['total_notifications']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 stat-hover-card border-start border-4 border-warning">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-warning-subtle text-warning-emphasis rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-envelope-exclamation-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted extra-small fw-semibold d-block text-uppercase">Unread</span>
                            <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['unread_notifications']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 stat-hover-card border-start border-4 border-success">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-calendar2-check-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted extra-small fw-semibold d-block text-uppercase">Sent Today</span>
                            <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['today_notifications']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 stat-hover-card border-start border-4 border-primary">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-bag-check-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted extra-small fw-semibold d-block text-uppercase">Order Alerts</span>
                            <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['order_notifications']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-body p-3">
                <form method="GET" action="index.php" class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control bg-light border-start-0" name="search" placeholder="Search by title, message, customer..." value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <div class="col-6 col-md-2">
                        <select name="type" class="form-select form-select-sm bg-light" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="order" <?= $filters['type'] === 'order' ? 'selected' : ''; ?>>Order Updates</option>
                            <option value="promo" <?= $filters['type'] === 'promo' ? 'selected' : ''; ?>>Promotions</option>
                            <option value="account" <?= $filters['type'] === 'account' ? 'selected' : ''; ?>>Account Alerts</option>
                            <option value="system" <?= $filters['type'] === 'system' ? 'selected' : ''; ?>>System Notices</option>
                            <option value="support" <?= $filters['type'] === 'support' ? 'selected' : ''; ?>>Support Inquiries</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <select name="status" class="form-select form-select-sm bg-light" onchange="this.form.submit()">
                            <option value="">All Read Status</option>
                            <option value="unread" <?= $filters['status'] === 'unread' ? 'selected' : ''; ?>>Unread</option>
                            <option value="read" <?= $filters['status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <select name="date_range" class="form-select form-select-sm bg-light" onchange="this.form.submit()">
                            <option value="">All Time</option>
                            <option value="today" <?= $filters['date_range'] === 'today' ? 'selected' : ''; ?>>Today (Maanta)</option>
                            <option value="yesterday" <?= $filters['date_range'] === 'yesterday' ? 'selected' : ''; ?>>Yesterday (Shalay)</option>
                            <option value="this_week" <?= $filters['date_range'] === 'this_week' ? 'selected' : ''; ?>>This Week (Asbuucan)</option>
                            <option value="last_week" <?= $filters['date_range'] === 'last_week' ? 'selected' : ''; ?>>Last Week (Asbuucii Hore)</option>
                            <option value="7days" <?= $filters['date_range'] === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="this_month" <?= $filters['date_range'] === 'this_month' ? 'selected' : ''; ?>>This Month (Bishan)</option>
                            <option value="last_month" <?= $filters['date_range'] === 'last_month' ? 'selected' : ''; ?>>Last Month (Bishii Hore)</option>
                            <option value="30days" <?= $filters['date_range'] === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="this_year" <?= $filters['date_range'] === 'this_year' ? 'selected' : ''; ?>>This Year (Sannadkan)</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-auth-primary btn-sm w-100">Filter</button>
                        <a href="index.php" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notifications Table -->
        <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
            <div class="card-header bg-white py-3 px-3 border-bottom d-flex align-items-center justify-content-between">
                <h2 class="h6 fw-bold text-dark mb-0">Notification History (<?= number_format($totalCount); ?>)</h2>
                <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-2 py-1 extra-small">Live Feed</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 extra-small text-uppercase text-muted" style="width: 70px;">ID</th>
                            <th class="extra-small text-uppercase text-muted">Customer</th>
                            <th class="extra-small text-uppercase text-muted">Type</th>
                            <th class="extra-small text-uppercase text-muted">Title &amp; Message</th>
                            <th class="extra-small text-uppercase text-muted">Status</th>
                            <th class="extra-small text-uppercase text-muted">Date Sent</th>
                            <th class="pe-3 text-end extra-small text-uppercase text-muted" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notifsList)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-bell-slash fs-1 d-block mb-2 text-muted"></i>
                                    <p class="mb-0 fw-semibold">No notifications found.</p>
                                    <span class="extra-small">No notification records matched your filter criteria.</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($notifsList as $n): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-muted small">#<?= (int)$n['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-placeholder rounded-circle bg-light border d-flex align-items-center justify-content-center text-primary-accent fw-bold small" style="width: 32px; height: 32px;">
                                                <?= strtoupper(substr($n['user_name'] ?? 'U', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <span class="d-block fw-semibold text-dark small text-truncate" style="max-width: 140px;"><?= htmlspecialchars($n['user_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8'); ?></span>
                                                <span class="d-block text-muted extra-small text-truncate" style="max-width: 140px;"><?= htmlspecialchars($n['user_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $typeBadge = match($n['type']) {
                                            'order' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 extra-small"><i class="bi bi-bag-check me-1"></i>Order</span>',
                                            'promo', 'promotion' => '<span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-2 py-1 extra-small"><i class="bi bi-tag me-1"></i>Promo</span>',
                                            'account' => '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 extra-small"><i class="bi bi-shield me-1"></i>Account</span>',
                                            'support' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 extra-small"><i class="bi bi-headset me-1"></i>Support</span>',
                                            default => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 extra-small"><i class="bi bi-info-circle me-1"></i>System</span>'
                                        };
                                        echo $typeBadge;
                                        ?>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 280px;">
                                            <span class="fw-bold text-dark d-block small"><?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="text-muted extra-small text-truncate d-block"><?= htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ((int)$n['is_read'] === 1): ?>
                                            <span class="badge bg-light text-secondary border px-2 py-1 extra-small"><i class="bi bi-check2-all text-success me-1"></i>Read</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1 extra-small"><i class="bi bi-dot"></i>Unread</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="extra-small text-muted">
                                        <?= date('M d, Y', strtotime($n['created_at'])); ?><br>
                                        <span class="text-muted opacity-75"><?= date('h:i A', strtotime($n['created_at'])); ?></span>
                                    </td>
                                    <td class="pe-3 text-end">
                                        <form method="POST" action="index.php" onsubmit="return confirm('Delete this notification log entry?');" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete_notification">
                                            <input type="hidden" name="notif_id" value="<?= (int)$n['id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm p-1 rounded-circle" style="width: 28px; height: 28px; line-height: 1;" title="Delete Log">
                                                <i class="bi bi-trash extra-small"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white py-3 px-3 d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 border-top">
                    <span class="extra-small text-muted">
                        Showing page <strong><?= $page; ?></strong> of <strong><?= $totalPages; ?></strong> (<?= number_format($totalCount); ?> total items)
                    </span>
                    <nav aria-label="Notification list pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-pill px-3 me-1" href="index.php?<?= http_build_query(array_merge($filters, ['page' => $page - 1])); ?>">Previous</a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= ($p === $page) ? 'active' : ''; ?>">
                                    <a class="page-link rounded-circle text-center mx-1" style="width: 32px; height: 32px; line-height: 20px;" href="index.php?<?= http_build_query(array_merge($filters, ['page' => $p])); ?>"><?= $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-pill px-3 ms-1" href="index.php?<?= http_build_query(array_merge($filters, ['page' => $page + 1])); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
