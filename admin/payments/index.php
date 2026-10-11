<?php
/**
 * FastBite - Admin Payment Transactions Management
 * STEP 26: Payment System & Checkout Finalization
 */

$pageTitle = 'Payment Transactions';
$activeMenu = 'payments';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/payment.php';

$successMsg = '';
$errorMsg = '';

// Handle Mark Paid POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'mark_paid') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = "Security token validation failed. Please try again.";
    } else {
        $orderId = (int)($_POST['order_id'] ?? 0);
        if ($orderId > 0) {
            $paid = dbMarkPaymentPaid($orderId, 'Admin Portal');
            if ($paid) {
                $successMsg = "Payment marked as PAID and customer notified.";
            } else {
                $errorMsg = "Failed to update payment transaction.";
            }
        }
    }
}

// Handle Mark Failed POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'mark_failed') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = "Security token validation failed.";
    } else {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Payment collection failed');
        if ($orderId > 0) {
            $failed = dbMarkPaymentFailed($orderId, $reason);
            if ($failed) {
                $successMsg = "Payment marked as FAILED.";
            } else {
                $errorMsg = "Failed to update status.";
            }
        }
    }
}

// Filters
$statusFilter = $_GET['status'] ?? 'all';
$methodFilter = $_GET['method'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');

$transactions = dbGetAllPaymentTransactions([
    'status' => $statusFilter,
    'method' => $methodFilter,
    'search' => $searchQuery
]);

// Summary KPI calculations
$allTxnsRaw = dbGetAllPaymentTransactions();
$totalCollected = 0.00;
$totalPending = 0.00;
$countPaid = 0;
$countPending = 0;
$countFailed = 0;
$countCod = 0;

foreach ($allTxnsRaw as $tx) {
    $amt = (float)$tx['amount'];
    if ($tx['status'] === 'paid') {
        $totalCollected += $amt;
        $countPaid++;
    } elseif ($tx['status'] === 'pending') {
        $totalPending += $amt;
        $countPending++;
    } elseif ($tx['status'] === 'failed') {
        $countFailed++;
    }

    if ($tx['payment_method'] === 'cash_on_delivery') {
        $countCod++;
    }
}
$codPercent = count($allTxnsRaw) > 0 ? round(($countCod / count($allTxnsRaw)) * 100) : 100;
?>

<!-- Sidebar Navigation -->
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5 bg-light">
    <div class="container-fluid p-0">
        
        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-2 py-1 mb-1 extra-small fw-bold">FINANCE & GATEWAYS</span>
                <h1 class="h3 fw-bold text-dark mb-1">Payment Transactions</h1>
                <p class="text-muted small mb-0">Audit live payment logs, monitor Cash on Delivery collections, and oversee settlement statuses.</p>
            </div>
            <div>
                <a href="../orders/index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-bag-check me-1"></i>View Orders
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

        <!-- KPI Metric Cards -->
        <div class="row g-3 g-md-4 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted extra-small fw-bold text-uppercase">Total Collected</span>
                        <div class="rounded-circle bg-success-subtle text-success p-2">
                            <i class="bi bi-cash-stack fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-extrabold text-dark mb-1">$<?= number_format($totalCollected, 2); ?></h3>
                    <span class="text-success extra-small fw-semibold"><i class="bi bi-check-circle me-1"></i><?= $countPaid; ?> Paid Orders</span>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted extra-small fw-bold text-uppercase">Pending Payments</span>
                        <div class="rounded-circle bg-warning-subtle text-warning-emphasis p-2">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-extrabold text-dark mb-1">$<?= number_format($totalPending, 2); ?></h3>
                    <span class="text-muted extra-small"><i class="bi bi-hourglass-split me-1"></i><?= $countPending; ?> Awaiting Collection</span>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted extra-small fw-bold text-uppercase">COD Share</span>
                        <div class="rounded-circle bg-primary-subtle text-primary-accent p-2">
                            <i class="bi bi-truck fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-extrabold text-dark mb-1"><?= $codPercent; ?>%</h3>
                    <span class="text-muted extra-small"><i class="bi bi-geo-alt me-1"></i>Doorstep Cash Flow</span>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted extra-small fw-bold text-uppercase">Online Gateways</span>
                        <div class="rounded-circle bg-secondary-subtle text-secondary p-2">
                            <i class="bi bi-shield-check fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Sandbox / Offline</h4>
                    <span class="badge bg-secondary-subtle text-secondary extra-small">No Live API Keys Configured</span>
                </div>
            </div>
        </div>

        <!-- Filters & Transactions Table -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h5 class="fw-bold text-dark mb-0">Payment Logs (<?= count($transactions); ?>)</h5>
                
                <form action="index.php" method="GET" class="d-flex flex-wrap gap-2">
                    <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <option value="all" <?= $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>

                    <select name="method" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <option value="all" <?= $methodFilter === 'all' ? 'selected' : ''; ?>>All Methods</option>
                        <option value="cash_on_delivery" <?= $methodFilter === 'cash_on_delivery' ? 'selected' : ''; ?>>Cash on Delivery</option>
                        <option value="mobile_money" <?= $methodFilter === 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
                        <option value="card" <?= $methodFilter === 'card' ? 'selected' : ''; ?>>Card</option>
                    </select>

                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Ref # / Order / Customer" value="<?= htmlspecialchars($searchQuery); ?>" style="width: 180px;">
                    <button type="submit" class="btn btn-primary-accent btn-sm rounded-pill px-3 text-white">Filter</button>
                    <?php if (!empty($searchQuery) || $statusFilter !== 'all' || $methodFilter !== 'all'): ?>
                        <a href="index.php" class="btn btn-light btn-sm rounded-pill border">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Reference</th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-credit-card-2-front fs-2 d-block mb-2 text-secondary"></i>
                                    No payment transactions recorded.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                                <tr>
                                    <td class="ps-4 font-monospace small fw-bold text-dark">
                                        <?= htmlspecialchars($tx['transaction_reference']); ?>
                                    </td>
                                    <td>
                                        <a href="../orders/index.php?search=<?= urlencode($tx['order_number']); ?>" class="fw-bold text-primary-accent text-decoration-none">
                                            #<?= htmlspecialchars($tx['order_number']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <strong class="d-block small text-dark"><?= htmlspecialchars($tx['customer_name']); ?></strong>
                                        <span class="text-muted extra-small"><?= htmlspecialchars($tx['customer_email']); ?></span>
                                    </td>
                                    <td class="small">
                                        <?= htmlspecialchars(getPaymentMethodLabel($tx['payment_method'])); ?>
                                    </td>
                                    <td class="fw-bold text-dark">
                                        $<?= number_format((float)$tx['amount'], 2); ?>
                                    </td>
                                    <td>
                                        <?= getPaymentStatusBadge($tx['status']); ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= date('M d, Y h:i A', strtotime($tx['created_at'])); ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if ($tx['status'] === 'pending'): ?>
                                            <form action="index.php" method="POST" class="d-inline-block">
                                                <?= csrfField(); ?>
                                                <input type="hidden" name="action" value="mark_paid">
                                                <input type="hidden" name="order_id" value="<?= (int)$tx['order_id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm fw-semibold" onclick="return confirm('Confirm payment received for order #<?= $tx['order_number']; ?>?')">
                                                    <i class="bi bi-check-circle me-1"></i>Mark Paid
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border extra-small">Settled</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
