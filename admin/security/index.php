<?php
/**
 * FastBite - Admin Security Activity Center & Audit Trail
 * STEP 27: Admin Users, Roles & Security Management
 */

$activeMenu = 'security';
$pageTitle = 'Security Activity Center';

require_once dirname(__DIR__) . '/includes/header.php';

$secMetrics = dbGetSecurityMetrics();

// Filtering and Pagination
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'action' => trim($_GET['action'] ?? ''),
    'user_id' => trim($_GET['user_id'] ?? ''),
    'date_range' => trim($_GET['date_range'] ?? '')
];

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$logsData = dbGetSecurityLogs($filters, $limit, $offset);
$logs = $logsData['logs'];
$totalLogsCount = $logsData['total'];
$totalPages = ceil($totalLogsCount / $limit);
?>

<!-- Main Content -->
<main class="admin-main p-3 p-lg-4 flex-grow-1">
    <div class="container-fluid p-0">
        
        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Security Activity Center</h1>
                <p class="text-muted small mb-0">System audit logs, login monitor, access enforcement, and administrative audit trails.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="../users/index.php" class="btn btn-outline-pink btn-sm d-flex align-items-center gap-2">
                    <i class="bi bi-people"></i>
                    <span>Users Directory</span>
                </a>
            </div>
        </div>

        <!-- KPI Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-dark">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-pink-soft text-primary-accent rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-shield-fill-check"></i>
                        </div>
                        <div>
                            <span class="text-muted extra-small fw-semibold d-block text-uppercase">Total Events</span>
                            <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($secMetrics['total_events']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-calendar-event-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted extra-small fw-semibold d-block text-uppercase">Today's Activity</span>
                            <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($secMetrics['today_events']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </div>
                        <div>
                            <span class="text-muted extra-small fw-semibold d-block text-uppercase">Successful Logins</span>
                            <h3 class="fw-bold text-success mb-0 fs-4"><?= number_format($secMetrics['successful_logins']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-danger-subtle text-danger rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-shield-slash-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted extra-small fw-semibold d-block text-uppercase">Failed / Blocked</span>
                            <h3 class="fw-bold text-danger mb-0 fs-4"><?= number_format($secMetrics['failed_logins']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3 p-lg-4">
                <form method="GET" action="index.php" class="row g-2 g-md-3 align-items-center">
                    
                    <div class="col-12 col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Search event description, IP, email..." value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <select name="action" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Security Actions</option>
                            <option value="login_success" <?= ($filters['action'] === 'login_success') ? 'selected' : ''; ?>>Login Success</option>
                            <option value="login_failed" <?= ($filters['action'] === 'login_failed') ? 'selected' : ''; ?>>Login Failed</option>
                            <option value="login_blocked" <?= ($filters['action'] === 'login_blocked') ? 'selected' : ''; ?>>Login Blocked (Inactive)</option>
                            <option value="logout" <?= ($filters['action'] === 'logout') ? 'selected' : ''; ?>>Logout</option>
                            <option value="role_change" <?= ($filters['action'] === 'role_change') ? 'selected' : ''; ?>>Role Change</option>
                            <option value="status_change" <?= ($filters['action'] === 'status_change') ? 'selected' : ''; ?>>Status Change</option>
                            <option value="admin_password_reset" <?= ($filters['action'] === 'admin_password_reset') ? 'selected' : ''; ?>>Password Reset</option>
                            <option value="user_profile_updated" <?= ($filters['action'] === 'user_profile_updated') ? 'selected' : ''; ?>>Profile Updated</option>
                            <option value="user_deleted" <?= ($filters['action'] === 'user_deleted') ? 'selected' : ''; ?>>User Deleted</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <select name="date_range" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Dates</option>
                            <option value="today" <?= ($filters['date_range'] === 'today') ? 'selected' : ''; ?>>Today (Maanta)</option>
                            <option value="yesterday" <?= ($filters['date_range'] === 'yesterday') ? 'selected' : ''; ?>>Yesterday (Shalay)</option>
                            <option value="this_week" <?= ($filters['date_range'] === 'this_week') ? 'selected' : ''; ?>>This Week (Asbuucan)</option>
                            <option value="last_week" <?= ($filters['date_range'] === 'last_week') ? 'selected' : ''; ?>>Last Week (Asbuucii Hore)</option>
                            <option value="7days" <?= ($filters['date_range'] === '7days') ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="this_month" <?= ($filters['date_range'] === 'this_month') ? 'selected' : ''; ?>>This Month (Bishan)</option>
                            <option value="last_month" <?= ($filters['date_range'] === 'last_month') ? 'selected' : ''; ?>>Last Month (Bishii Hore)</option>
                            <option value="30days" <?= ($filters['date_range'] === '30days') ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="this_year" <?= ($filters['date_range'] === 'this_year') ? 'selected' : ''; ?>>This Year (Sannadkan)</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary-accent btn-sm flex-grow-1">Filter</button>
                        <a href="index.php" class="btn btn-light btn-sm border" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Logs Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-3 px-lg-4 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark">Audit Logs (<?= number_format($totalLogsCount); ?> total)</span>
                <span class="badge bg-light text-muted border">Page <?= $page; ?> of <?= max(1, $totalPages); ?></span>
            </div>

            <?php if (empty($logs)): ?>
                <div class="p-5 text-center">
                    <i class="bi bi-shield-check display-4 text-muted mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">No security events found</h5>
                    <p class="text-muted small">Try modifying your search or action filter.</p>
                    <a href="index.php" class="btn btn-outline-pink btn-sm">Clear Filters</a>
                </div>
            <?php else: ?>
                
                <!-- Desktop & Tablet Table View -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light extra-small text-uppercase text-muted">
                            <tr>
                                <th class="ps-4">Timestamp</th>
                                <th>Action</th>
                                <th>User / Target</th>
                                <th>Admin Actor</th>
                                <th>Description</th>
                                <th class="text-end pe-4">IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <?php
                                $badgeClass = 'bg-light text-secondary border';
                                $icon = 'bi-info-circle';
                                if (strpos($log['action'], 'success') !== false) {
                                    $badgeClass = 'bg-success-subtle text-success';
                                    $icon = 'bi-check-circle-fill';
                                } elseif (strpos($log['action'], 'failed') !== false || strpos($log['action'], 'blocked') !== false || strpos($log['action'], 'deleted') !== false) {
                                    $badgeClass = 'bg-danger-subtle text-danger';
                                    $icon = 'bi-exclamation-triangle-fill';
                                } elseif (strpos($log['action'], 'role') !== false || strpos($log['action'], 'password') !== false) {
                                    $badgeClass = 'bg-warning-subtle text-warning-emphasis';
                                    $icon = 'bi-shield-lock-fill';
                                } elseif (strpos($log['action'], 'updated') !== false || strpos($log['action'], 'status') !== false) {
                                    $badgeClass = 'bg-primary-subtle text-primary';
                                    $icon = 'bi-pencil-square';
                                }
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="d-block small text-dark fw-semibold"><?= date('M d, Y', strtotime($log['created_at'])); ?></span>
                                        <span class="extra-small text-muted"><?= date('H:i:s', strtotime($log['created_at'])); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass; ?> extra-small px-2 py-1">
                                            <i class="bi <?= $icon; ?> me-1"></i><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['user_id']) && !empty($log['user_name'])): ?>
                                            <a href="../users/view.php?id=<?= $log['user_id']; ?>" class="fw-bold text-dark text-decoration-none d-block extra-small hover-pink">
                                                <?= htmlspecialchars($log['user_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                            <span class="extra-small text-muted"><?= htmlspecialchars($log['user_email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else: ?>
                                            <span class="extra-small text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['admin_id']) && !empty($log['admin_name'])): ?>
                                            <span class="fw-semibold text-dark extra-small d-block"><?= htmlspecialchars($log['admin_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="extra-small text-muted">Admin #<?= $log['admin_id']; ?></span>
                                        <?php else: ?>
                                            <span class="extra-small text-muted">System / Self</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="small text-dark"><?= htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <code class="extra-small text-muted bg-light px-2 py-1 rounded"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8'); ?></code>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="d-md-none p-3 d-flex flex-column gap-3">
                    <?php foreach ($logs as $log): ?>
                        <div class="card border rounded-3 p-3 bg-light extra-small">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="text-muted"><?= date('M d, H:i:s', strtotime($log['created_at'])); ?></span>
                            </div>
                            <p class="text-dark fw-semibold mb-2"><?= htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="d-flex justify-content-between text-muted border-top pt-2 mt-1">
                                <span>IP: <code><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8'); ?></code></span>
                                <span><?= !empty($log['admin_name']) ? 'By ' . htmlspecialchars($log['admin_name'], ENT_QUOTES, 'UTF-8') : 'System'; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer bg-white py-3 px-4 border-top d-flex justify-content-between align-items-center">
                        <span class="extra-small text-muted">Showing <?= $offset + 1; ?>–<?= min($offset + $limit, $totalLogsCount); ?> of <?= $totalLogsCount; ?> events</span>
                        <nav aria-label="Security logs pagination">
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

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
