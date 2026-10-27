<?php
/**
 * FastBite - Admin Promotional Coupons Management
 * STEP 30: FastBite Customer Experience, Reviews, Favorites & Promotions
 */

require_once __DIR__ . '/../../includes/auth.php';

requireAdmin('../../login.php');

$activeMenu = 'coupons';
$projectName = 'FastBite Admin';
$adminUser = currentUser();
$adminId = (int)$adminUser['id'];

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Actions (Toggle Active, Delete)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = "Security validation failed. Please refresh.";
    } else {
        $action = $_POST['action'] ?? '';
        $couponId = (int)($_POST['coupon_id'] ?? 0);

        if ($action === 'toggle' && $couponId > 0) {
            $status = (int)($_POST['is_active'] ?? 0);
            dbAdminToggleCoupon($couponId, $status, $adminId);
            header("Location: index.php?msg=" . urlencode("Coupon status updated successfully."));
            exit;
        } elseif ($action === 'delete' && $couponId > 0) {
            dbAdminDeleteCoupon($couponId, $adminId);
            header("Location: index.php?msg=" . urlencode("Coupon deleted successfully."));
            exit;
        }
    }
}

// Search & Filter Parameters
$statusFilter = trim($_GET['is_active'] ?? 'all');
$typeFilter = trim($_GET['discount_type'] ?? 'all');
$searchFilter = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$filters = [
    'is_active' => $statusFilter,
    'discount_type' => $typeFilter,
    'search' => $searchFilter
];

$couponData = dbAdminGetCoupons($filters, $limit, $offset);
$coupons = $couponData['coupons'];
$totalCoupons = $couponData['total'];
$totalPages = ceil($totalCoupons / $limit);

// Overall Metrics
$pdo = getDbConnection();
$totalCount = (int)$pdo->query("SELECT COUNT(*) FROM `coupons`")->fetchColumn();
$activeCount = (int)$pdo->query("SELECT COUNT(*) FROM `coupons` WHERE `is_active` = 1")->fetchColumn();
$totalRedeemed = (int)$pdo->query("SELECT COUNT(*) FROM `coupon_usages`")->fetchColumn();
$totalSavingsGranted = (float)$pdo->query("SELECT COALESCE(SUM(discount_amount), 0.00) FROM `coupon_usages`")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotional Coupons - <?= htmlspecialchars($projectName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="admin-dashboard-body">

    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="admin-layout d-flex min-vh-100">
        <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="admin-main-content flex-grow-1 p-3 p-md-4 p-lg-5">
            <div class="container-fluid px-0">

                <!-- Page Header -->
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
                    <div>
                        <span class="dashboard-eyebrow d-inline-block">MARKETING &amp; PROMOTIONS</span>
                        <h1 class="h3 fw-bold text-dark mb-1">Promotional Coupons Engine</h1>
                        <p class="text-muted extra-small mb-0">Create, manage, and track promo discount vouchers for marketing campaigns.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="create.php" class="btn btn-auth-primary d-inline-flex align-items-center gap-1 shadow-sm">
                            <i class="bi bi-plus-lg"></i>
                            <span>Create New Coupon</span>
                        </a>
                    </div>
                </div>

                <!-- Feedback Alerts -->
                <?php if (!empty($msg)): ?>
                    <div class="alert alert-success py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <div><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                        <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Metrics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-4 border-dark">
                            <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Total Coupons</span>
                            <span class="h3 fw-bold text-dark mb-0"><?= $totalCount; ?></span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-4 border-success">
                            <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Active Promos</span>
                            <span class="h3 fw-bold text-success mb-0"><?= $activeCount; ?></span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-4 border-primary">
                            <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Total Redeemed</span>
                            <span class="h3 fw-bold text-primary-accent mb-0"><?= $totalRedeemed; ?></span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-4 border-warning">
                            <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Total Discount Given</span>
                            <span class="h3 fw-bold text-dark mb-0">$<?= number_format($totalSavingsGranted, 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Filters & Search -->
                <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                    <form method="GET" action="index.php" class="row g-3 align-items-center">
                        <div class="col-12 col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" value="<?= htmlspecialchars($searchFilter, ENT_QUOTES, 'UTF-8'); ?>" class="form-control bg-light border-start-0" placeholder="Search coupon code or description...">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <select name="is_active" class="form-select form-select-sm" onchange="this.form.submit();">
                                <option value="all" <?= ($statusFilter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="1" <?= ($statusFilter === '1') ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?= ($statusFilter === '0') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <select name="discount_type" class="form-select form-select-sm" onchange="this.form.submit();">
                                <option value="all" <?= ($typeFilter === 'all') ? 'selected' : ''; ?>>All Types</option>
                                <option value="percentage" <?= ($typeFilter === 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                                <option value="fixed" <?= ($typeFilter === 'fixed') ? 'selected' : ''; ?>>Fixed Amount ($)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-auth-primary btn-sm flex-grow-1">Filter</button>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
                        </div>
                    </form>
                </div>

                <!-- Coupons Table -->
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <?php if (empty($coupons)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-ticket-perforated text-muted display-4 mb-2 d-block" style="opacity: 0.5;"></i>
                            <h3 class="h5 fw-bold text-dark mb-1">No promo coupons found</h3>
                            <p class="text-muted small mb-3">No coupons matched your filter criteria.</p>
                            <a href="create.php" class="btn btn-auth-primary btn-sm">Create New Coupon</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Discount</th>
                                        <th>Conditions</th>
                                        <th>Redemptions</th>
                                        <th>Validity</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($coupons as $cpn): 
                                        $valFormatted = ($cpn['discount_type'] === 'percentage') ? ((float)$cpn['discount_value'] . '%') : ('$' . number_format((float)$cpn['discount_value'], 2));
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-2 py-1 fs-6 font-monospace fw-bold">
                                                        <?= htmlspecialchars($cpn['code'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                </div>
                                                <span class="text-muted extra-small d-block mt-1"><?= htmlspecialchars($cpn['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark"><?= $valFormatted; ?></span>
                                                <span class="d-block extra-small text-muted text-capitalize"><?= htmlspecialchars($cpn['discount_type']); ?></span>
                                            </td>
                                            <td>
                                                <span class="d-block extra-small">Min Order: <strong>$<?= number_format((float)$cpn['minimum_order'], 2); ?></strong></span>
                                                <?php if (!empty($cpn['maximum_discount'])): ?>
                                                    <span class="d-block extra-small text-muted">Max Cap: $<?= number_format((float)$cpn['maximum_discount'], 2); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark"><?= $cpn['used_count']; ?></span>
                                                <span class="text-muted extra-small">/ <?= $cpn['usage_limit'] ? $cpn['usage_limit'] : '&infin;'; ?></span>
                                                <span class="d-block extra-small text-muted"><?= $cpn['usage_per_customer']; ?> per user</span>
                                            </td>
                                            <td>
                                                <?php if (!empty($cpn['start_at']) || !empty($cpn['end_at'])): ?>
                                                    <span class="extra-small d-block"><?= !empty($cpn['start_at']) ? date('M d, Y', strtotime($cpn['start_at'])) : 'Any'; ?> &rarr;</span>
                                                    <span class="extra-small text-muted d-block"><?= !empty($cpn['end_at']) ? date('M d, Y', strtotime($cpn['end_at'])) : 'No expiry'; ?></span>
                                                <?php else: ?>
                                                    <span class="text-success extra-small fw-semibold">Always Valid</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" action="index.php" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="coupon_id" value="<?= $cpn['id']; ?>">
                                                    <input type="hidden" name="is_active" value="<?= $cpn['is_active'] ? 0 : 1; ?>">
                                                    <button type="submit" class="badge <?= $cpn['is_active'] ? 'bg-success-subtle text-success border-success-subtle' : 'bg-secondary-subtle text-muted border'; ?> border px-2 py-1 text-uppercase extra-small fw-bold" style="cursor: pointer;" title="Click to toggle active">
                                                        <?= $cpn['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <a href="edit.php?id=<?= $cpn['id']; ?>" class="btn btn-sm btn-outline-pink px-2 py-1" title="Edit Coupon">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form method="POST" action="index.php" class="d-inline" onsubmit="return confirm('Permanently delete coupon <?= htmlspecialchars($cpn['code']); ?>?');">
                                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="coupon_id" value="<?= $cpn['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" title="Delete Coupon">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4" aria-label="Coupons pagination">
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= ($page === $p) ? 'active' : ''; ?>">
                                    <a class="page-link" href="index.php?page=<?= $p; ?>&is_active=<?= urlencode($statusFilter); ?>&discount_type=<?= urlencode($typeFilter); ?>&search=<?= urlencode($searchFilter); ?>">
                                        <?= $p; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
