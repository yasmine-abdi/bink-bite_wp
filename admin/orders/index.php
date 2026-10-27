<?php
/**
 * FastBite - Admin Orders Management & Fulfillment Operations
 * STEP 29: FastBite Final Order, Delivery & Fulfillment System
 */

$pageTitle = 'Orders Management';
$activeMenu = 'orders';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../../includes/image-helper.php';
require_once __DIR__ . '/../../includes/payment.php';

$successMsg = $_GET['msg'] ?? '';
$errorMsg = $_GET['error'] ?? '';
$currentAdmin = currentUser();
$adminId = $currentAdmin ? (int)$currentAdmin['id'] : null;

// Handle Status Update POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = "Security validation failed. Please refresh and try again.";
    } else {
        $action = $_POST['action'] ?? '';
        $orderId = (int)($_POST['order_id'] ?? 0);

        if ($action === 'update_status' && $orderId > 0) {
            $newStatus = trim($_POST['new_status'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            $updated = dbUpdateOrderStatus($orderId, $newStatus, $adminId, $reason);
            if ($updated) {
                header("Location: index.php?msg=" . urlencode("Order status updated to '" . ucfirst(str_replace('_', ' ', $newStatus)) . "' successfully."));
                exit;
            } else {
                $errorMsg = "Invalid order status transition or update failed. Transition rejected by business state machine.";
            }
        } elseif ($action === 'mark_payment_paid' && $orderId > 0) {
            $paid = dbMarkPaymentPaid($orderId, 'Admin Orders List', $adminId);
            if ($paid) {
                header("Location: index.php?msg=" . urlencode("Payment for Order #{$orderId} recorded as PAID."));
                exit;
            } else {
                $errorMsg = "Failed to update payment status.";
            }
        } elseif ($action === 'send_receipt' && $orderId > 0) {
            $dispatchRes = dbDispatchOrderReceiptEmail($orderId, $adminId);
            if (!empty($dispatchRes['sent'])) {
                header("Location: index.php?msg=" . urlencode("Official receipt for Order #{$orderId} dispatched to customer."));
                exit;
            } else {
                $errorMsg = $dispatchRes['reason'] ?? "Email delivery service is not configured. Receipt was not sent.";
            }
        }
    }
}

// KPI Metrics from Database
$metrics = dbGetAdminOrderMetrics();

// Filtering & Pagination
$filters = [
    'status' => trim($_GET['status'] ?? 'all'),
    'payment_status' => trim($_GET['payment_status'] ?? 'all'),
    'payment_method' => trim($_GET['payment_method'] ?? 'all'),
    'search' => trim($_GET['search'] ?? ''),
    'date_range' => trim($_GET['date_range'] ?? ''),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to' => trim($_GET['date_to'] ?? '')
];

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$orderData = dbGetFilteredOrdersAdmin($filters, $limit, $offset);
$orders = $orderData['orders'];
$totalCount = $orderData['total'];
$totalPages = ceil($totalCount / $limit);
?>

<!-- Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5 bg-light">
    <div class="container-fluid p-0">
        
        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent" style="font-size: 0.75rem; letter-spacing: 0.08em;">OPERATIONS &amp; FULFILLMENT</span>
                <h1 class="admin-title fw-bold mb-1 text-dark">Customer Orders Management</h1>
                <p class="admin-desc text-muted mb-0 small">Track live orders, dispatch kitchen fulfillment stages, and monitor delivery workflows.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge bg-white text-dark border p-2 shadow-sm small">
                    <i class="bi bi-cash-stack text-success me-1"></i> Total Volume: <strong class="text-primary-accent">$<?= number_format($metrics['total_revenue'], 2); ?></strong>
                </span>
                <span class="badge bg-white text-dark border p-2 shadow-sm small">
                    <i class="bi bi-calendar-check text-primary me-1"></i> Today: <strong><?= number_format($metrics['today_orders']); ?> orders ($<?= number_format($metrics['today_revenue'], 2); ?>)</strong>
                </span>
            </div>
        </div>

        <!-- Feedback Notifications -->
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- KPI Metrics Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3 col-xl-1-5">
                <a href="index.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 stat-hover-card <?= ($filters['status'] === 'all' && empty($filters['payment_status'])) ? 'border-primary-accent border-2' : ''; ?>">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="text-muted extra-small fw-bold text-uppercase">Total Orders</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['total_orders']); ?></h3>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3 col-xl-1-5">
                <a href="index.php?status=pending" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 stat-hover-card <?= ($filters['status'] === 'pending') ? 'border-warning border-2' : ''; ?>">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-clock text-warning fs-6"></i>
                            <span class="text-muted extra-small fw-bold text-uppercase">Pending</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['pending_orders']); ?></h3>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3 col-xl-1-5">
                <a href="index.php?status=confirmed" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 stat-hover-card <?= ($filters['status'] === 'confirmed') ? 'border-primary border-2' : ''; ?>">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-check2-circle text-primary fs-6"></i>
                            <span class="text-muted extra-small fw-bold text-uppercase">Confirmed</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['confirmed_orders']); ?></h3>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3 col-xl-1-5">
                <a href="index.php?status=preparing" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 stat-hover-card <?= ($filters['status'] === 'preparing') ? 'border-warning-emphasis border-2' : ''; ?>">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-fire text-danger fs-6"></i>
                            <span class="text-muted extra-small fw-bold text-uppercase">Preparing</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['preparing_orders']); ?></h3>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3 col-xl-1-5">
                <a href="index.php?status=out_for_delivery" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 stat-hover-card <?= ($filters['status'] === 'out_for_delivery') ? 'border-info border-2' : ''; ?>">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-truck text-info fs-6"></i>
                            <span class="text-muted extra-small fw-bold text-uppercase">On Delivery</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['out_for_delivery_orders']); ?></h3>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3 col-xl-1-5">
                <a href="index.php?status=delivered" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 stat-hover-card <?= ($filters['status'] === 'delivered') ? 'border-success border-2' : ''; ?>">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-check-circle-fill text-success fs-6"></i>
                            <span class="text-muted extra-small fw-bold text-uppercase">Delivered</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['delivered_orders']); ?></h3>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3 col-xl-1-5">
                <a href="index.php?status=cancelled" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 stat-hover-card <?= ($filters['status'] === 'cancelled') ? 'border-danger border-2' : ''; ?>">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-x-circle-fill text-danger fs-6"></i>
                            <span class="text-muted extra-small fw-bold text-uppercase">Cancelled</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['cancelled_orders']); ?></h3>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3 col-xl-1-5">
                <a href="index.php?payment_status=pending" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 stat-hover-card <?= ($filters['payment_status'] === 'pending') ? 'border-warning border-2' : ''; ?>">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-cash-coin text-warning-emphasis fs-6"></i>
                            <span class="text-muted extra-small fw-bold text-uppercase">Unpaid/COD</span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0 fs-4"><?= number_format($metrics['pending_payments']); ?></h3>
                    </div>
                </a>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-body p-3">
                <form method="GET" action="index.php" id="orderFilterForm" class="row g-2 align-items-center">
                    <!-- Search Input -->
                    <div class="col-12 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control bg-light border-start-0" name="search" placeholder="Search order #, customer, phone, address..." value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-6 col-md-2">
                        <select name="status" class="form-select form-select-sm bg-light" onchange="this.form.submit()">
                            <option value="all">All Order Statuses</option>
                            <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending (<?= $metrics['pending_orders']; ?>)</option>
                            <option value="confirmed" <?= $filters['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed (<?= $metrics['confirmed_orders']; ?>)</option>
                            <option value="preparing" <?= $filters['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing (<?= $metrics['preparing_orders']; ?>)</option>
                            <option value="out_for_delivery" <?= $filters['status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery (<?= $metrics['out_for_delivery_orders']; ?>)</option>
                            <option value="delivered" <?= $filters['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered (<?= $metrics['delivered_orders']; ?>)</option>
                            <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled (<?= $metrics['cancelled_orders']; ?>)</option>
                        </select>
                    </div>

                    <!-- Payment Status Filter -->
                    <div class="col-6 col-md-2">
                        <select name="payment_status" class="form-select form-select-sm bg-light" onchange="this.form.submit()">
                            <option value="all">All Payments</option>
                            <option value="pending" <?= $filters['payment_status'] === 'pending' ? 'selected' : ''; ?>>Unpaid / Pending</option>
                            <option value="paid" <?= $filters['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="col-6 col-md-3">
                        <select name="date_range" id="orderDateRangeSelect" class="form-select form-select-sm bg-light" onchange="if(this.value==='custom'){ document.getElementById('orderCustomDateRow').classList.remove('d-none'); } else { document.getElementById('orderCustomDateRow').classList.add('d-none'); this.form.submit(); }">
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
                            <option value="custom" <?= $filters['date_range'] === 'custom' ? 'selected' : ''; ?>>Custom Date Range...</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-6 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-auth-primary btn-sm w-100">Filter</button>
                        <a href="index.php" class="btn btn-outline-secondary btn-sm" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>

                    <!-- Custom Date Picker Fields (Visible if Custom Date Range selected) -->
                    <div class="col-12 pt-2 border-top mt-2 <?= ($filters['date_range'] === 'custom' || (!empty($filters['date_from']) || !empty($filters['date_to']))) ? '' : 'd-none'; ?>" id="orderCustomDateRow">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="extra-small text-muted fw-bold text-uppercase me-1"><i class="bi bi-calendar3 me-1"></i>Date Range:</span>
                            <div style="max-width: 170px;">
                                <input type="date" name="date_from" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="From Date">
                            </div>
                            <span class="extra-small text-muted">&rarr;</span>
                            <div style="max-width: 170px;">
                                <input type="date" name="date_to" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="To Date">
                            </div>
                            <button type="submit" class="btn btn-dark btn-sm px-3">Apply Date Range</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table Card -->
        <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
            <div class="card-header bg-white py-3 px-3 border-bottom d-flex align-items-center justify-content-between">
                <h2 class="h6 fw-bold text-dark mb-0">Orders List (<?= number_format($totalCount); ?>)</h2>
                <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-2 py-1 extra-small">Live Kitchen &amp; Courier Queue</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 extra-small text-uppercase text-muted" style="width: 120px;">Order #</th>
                            <th class="extra-small text-uppercase text-muted">Customer</th>
                            <th class="extra-small text-uppercase text-muted">Items &amp; Details</th>
                            <th class="extra-small text-uppercase text-muted">Total</th>
                            <th class="extra-small text-uppercase text-muted">Payment</th>
                            <th class="extra-small text-uppercase text-muted">Fulfillment Status</th>
                            <th class="extra-small text-uppercase text-muted">Placed Date</th>
                            <th class="pe-3 text-end extra-small text-uppercase text-muted" style="width: 130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-funnel fs-1 d-block mb-2 text-muted"></i>
                                    <p class="mb-1 fw-bold text-dark fs-5">No orders found.</p>
                                    <span class="extra-small d-block text-muted mb-3">No order records matched your active filter or search criteria.</span>
                                    <a href="index.php" class="btn btn-outline-pink btn-sm rounded-pill px-3"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset All Filters</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): 
                                $st = $o['order_status'];
                                $paySt = $o['payment_status'] ?? 'pending';
                            ?>
                                <tr>
                                    <!-- Order Number -->
                                    <td class="ps-3">
                                        <a href="view.php?order=<?= urlencode($o['order_number']); ?>" class="fw-bold text-dark text-decoration-none text-primary-hover small">
                                            #<?= htmlspecialchars($o['order_number'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                        <span class="d-block extra-small text-muted"><?= htmlspecialchars($o['estimated_delivery'] ?? '25–35 min'); ?></span>
                                        <?php
                                        $recSt = $o['receipt_send_status'] ?? 'not_sent';
                                        if ($recSt === 'sent') {
                                            echo '<span class="badge bg-success-subtle text-success border border-success-subtle extra-small mt-1 d-inline-block" title="Receipt dispatched to customer"><i class="bi bi-envelope-check me-1"></i>Receipt Sent</span>';
                                        } elseif ($recSt === 'failed') {
                                            echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small mt-1 d-inline-block" title="Receipt email failed or SMTP inactive"><i class="bi bi-envelope-x me-1"></i>Receipt Failed</span>';
                                        }
                                        ?>
                                    </td>

                                    <!-- Customer Details -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-placeholder rounded-circle bg-light border d-flex align-items-center justify-content-center text-primary-accent fw-bold small flex-shrink-0" style="width: 32px; height: 32px;">
                                                <?= strtoupper(substr($o['customer_name'] ?? 'C', 0, 1)); ?>
                                            </div>
                                            <div class="text-truncate" style="max-width: 150px;">
                                                <span class="d-block fw-semibold text-dark small text-truncate"><?= htmlspecialchars($o['customer_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8'); ?></span>
                                                <span class="d-block text-muted extra-small text-truncate"><?= htmlspecialchars($o['customer_phone'] ?? $o['customer_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Items Breakdown -->
                                    <td>
                                        <span class="badge bg-light text-dark border extra-small fw-semibold">
                                            <?= count($o['items'] ?? []); ?> item(s)
                                        </span>
                                        <?php if (!empty($o['items'])): ?>
                                            <span class="extra-small text-muted d-block text-truncate mt-1" style="max-width: 180px;" title="<?= htmlspecialchars($o['items'][0]['food_name'] ?? ''); ?>">
                                                <?= htmlspecialchars($o['items'][0]['food_name'] ?? ''); ?><?= count($o['items']) > 1 ? ', ...' : ''; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Total Amount -->
                                    <td>
                                        <span class="fw-bold text-dark small">$<?= number_format((float)$o['total'], 2); ?></span>
                                        <?php if ((float)$o['discount'] > 0): ?>
                                            <span class="d-block extra-small text-success">-$<?= number_format((float)$o['discount'], 2); ?> promo</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Payment Status -->
                                    <td>
                                        <div class="d-flex flex-column align-items-start gap-1">
                                            <?= getPaymentStatusBadge($paySt); ?>
                                            <span class="extra-small text-muted"><?= htmlspecialchars(getPaymentMethodLabel($o['payment_method'])); ?></span>
                                            <?php if ($paySt !== 'paid' && $st !== 'cancelled'): ?>
                                                <form method="POST" action="index.php" onsubmit="return confirm('Mark payment as PAID for Order #<?= htmlspecialchars($o['order_number']); ?>?');">
                                                    <?= csrfField(); ?>
                                                    <input type="hidden" name="action" value="mark_payment_paid">
                                                    <input type="hidden" name="order_id" value="<?= (int)$o['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-success btn-xs p-0 px-1 extra-small text-decoration-none" title="Mark Paid">
                                                        <i class="bi bi-check-lg"></i> Mark Paid
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Order Status Badge -->
                                    <td>
                                        <?php
                                        $stBadge = match($st) {
                                            'pending' => '<span class="badge bg-warning text-dark border border-warning px-2 py-1 extra-small"><i class="bi bi-clock me-1"></i>Pending Review</span>',
                                            'confirmed' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 extra-small"><i class="bi bi-check2-circle me-1"></i>Confirmed</span>',
                                            'preparing' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 extra-small"><i class="bi bi-fire me-1"></i>In Kitchen</span>',
                                            'out_for_delivery' => '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 extra-small"><i class="bi bi-truck me-1"></i>On Way</span>',
                                            'delivered' => '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 extra-small"><i class="bi bi-check-circle-fill me-1"></i>Delivered</span>',
                                            'cancelled' => '<span class="badge bg-secondary-subtle text-muted border px-2 py-1 extra-small"><i class="bi bi-x-circle-fill me-1"></i>Cancelled</span>',
                                            default => '<span class="badge bg-light text-dark extra-small">' . htmlspecialchars($st) . '</span>'
                                        };
                                        echo $stBadge;
                                        ?>
                                    </td>

                                    <!-- Placed Date -->
                                    <td class="extra-small text-muted">
                                        <?= date('M d, Y', strtotime($o['created_at'])); ?><br>
                                        <span class="opacity-75"><?= date('h:i A', strtotime($o['created_at'])); ?></span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="pe-3 text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <a href="view.php?order=<?= urlencode($o['order_number']); ?>" class="btn btn-outline-pink btn-sm p-1 px-2 extra-small" title="Manage Order">
                                                <i class="bi bi-eye"></i> Manage
                                            </a>
                                            <a href="../../receipt.php?order=<?= urlencode($o['order_number']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm p-1 px-2 extra-small" title="Open Receipt">
                                                <i class="bi bi-receipt text-primary-accent"></i> Receipt
                                            </a>

                                            <!-- Quick Actions & Receipt Dropdown -->
                                            <div class="dropdown d-inline">
                                                <button class="btn btn-light btn-sm p-1 rounded-circle border" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 28px; height: 28px;" title="Order & Receipt Options">
                                                    <i class="bi bi-three-dots-vertical extra-small"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                                                    <li>
                                                        <a class="dropdown-item py-1 text-dark fw-semibold" href="view.php?order=<?= urlencode($o['order_number']); ?>">
                                                            <i class="bi bi-eye me-2 text-primary"></i>Manage &amp; View Details
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider my-1"></li>
                                                    <li class="dropdown-header extra-small text-uppercase text-muted py-1">Receipt &amp; Invoicing</li>
                                                    <li>
                                                        <a href="../../receipt.php?order=<?= urlencode($o['order_number']); ?>" target="_blank" class="dropdown-item py-1 text-dark">
                                                            <i class="bi bi-receipt me-2 text-primary-accent"></i>Open Receipt
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="index.php">
                                                            <?= csrfField(); ?>
                                                            <input type="hidden" name="action" value="send_receipt">
                                                            <input type="hidden" name="order_id" value="<?= (int)$o['id']; ?>">
                                                            <button type="submit" class="dropdown-item py-1 text-primary"><i class="bi bi-envelope-paper me-2"></i><?= ((int)($o['receipt_send_count'] ?? 0) > 0) ? 'Resend Receipt' : 'Send Receipt'; ?></button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <a href="../../receipt.php?order=<?= urlencode($o['order_number']); ?>&print=1" target="_blank" class="dropdown-item py-1 text-dark">
                                                            <i class="bi bi-printer me-2"></i>Print Official Receipt (A4)
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="../../receipt.php?order=<?= urlencode($o['order_number']); ?>&format=thermal&print=1" target="_blank" class="dropdown-item py-1 text-secondary">
                                                            <i class="bi bi-receipt-cutoff me-2"></i>Print Thermal Slip (80mm)
                                                        </a>
                                                    </li>

                                                    <?php if ($st !== 'delivered' && $st !== 'cancelled'): ?>
                                                        <li><hr class="dropdown-divider my-1"></li>
                                                        <li class="dropdown-header extra-small text-uppercase text-muted py-1">Fulfillment Workflow</li>
                                                        <?php if ($st === 'pending'): ?>
                                                            <li>
                                                                <form method="POST" action="index.php">
                                                                    <?= csrfField(); ?>
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="order_id" value="<?= (int)$o['id']; ?>">
                                                                    <input type="hidden" name="new_status" value="confirmed">
                                                                    <button type="submit" class="dropdown-item py-1 text-primary"><i class="bi bi-check2-circle me-2"></i>Confirm Order</button>
                                                                </form>
                                                            </li>
                                                        <?php elseif ($st === 'confirmed'): ?>
                                                            <li>
                                                                <form method="POST" action="index.php">
                                                                    <?= csrfField(); ?>
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="order_id" value="<?= (int)$o['id']; ?>">
                                                                    <input type="hidden" name="new_status" value="preparing">
                                                                    <button type="submit" class="dropdown-item py-1 text-danger"><i class="bi bi-fire me-2"></i>Send to Kitchen</button>
                                                                </form>
                                                            </li>
                                                        <?php elseif ($st === 'preparing'): ?>
                                                            <li>
                                                                <form method="POST" action="index.php">
                                                                    <?= csrfField(); ?>
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="order_id" value="<?= (int)$o['id']; ?>">
                                                                    <input type="hidden" name="new_status" value="out_for_delivery">
                                                                    <button type="submit" class="dropdown-item py-1 text-info"><i class="bi bi-truck me-2"></i>Dispatch Courier</button>
                                                                </form>
                                                            </li>
                                                        <?php elseif ($st === 'out_for_delivery'): ?>
                                                            <li>
                                                                <form method="POST" action="index.php">
                                                                    <?= csrfField(); ?>
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="order_id" value="<?= (int)$o['id']; ?>">
                                                                    <input type="hidden" name="new_status" value="delivered">
                                                                    <button type="submit" class="dropdown-item py-1 text-success"><i class="bi bi-check-circle-fill me-2"></i>Mark Delivered</button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                        <li>
                                                            <form method="POST" action="index.php" onsubmit="return confirm('Are you sure you want to CANCEL Order #<?= htmlspecialchars($o['order_number']); ?>?');">
                                                                <?= csrfField(); ?>
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="order_id" value="<?= (int)$o['id']; ?>">
                                                                <input type="hidden" name="new_status" value="cancelled">
                                                                <input type="hidden" name="reason" value="Cancelled by admin from orders list">
                                                                <button type="submit" class="dropdown-item py-1 text-danger"><i class="bi bi-x-circle me-2"></i>Cancel Order</button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
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

            <!-- Pagination Footer -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white py-3 px-3 d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 border-top">
                    <span class="extra-small text-muted">
                        Showing page <strong><?= $page; ?></strong> of <strong><?= $totalPages; ?></strong> (<?= number_format($totalCount); ?> total orders)
                    </span>
                    <nav aria-label="Orders list pagination">
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
