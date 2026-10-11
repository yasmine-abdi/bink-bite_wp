<?php
/**
 * FastBite - Courier Portal Dashboard
 * STEP 33: Complete Business Platform, Payments, Analytics, Support, Courier Portal & Production Security
 */

$pageTitle = 'Courier Dashboard';
$activePage = 'dashboard';

require_once __DIR__ . '/includes/header.php';

$error = $_GET['error'] ?? '';
$msg = $_GET['msg'] ?? '';

// Handle Duty Status Toggle POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'toggle_duty') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $newStatus = trim($_POST['new_status'] ?? '');
        $res = dbUpdateDriverDutyStatus($driverId, $newStatus);
        if ($res['success']) {
            header("Location: index.php?msg=" . urlencode($res['message']));
            exit;
        } else {
            $error = $res['message'];
        }
    }
}

// Handle Delivery Quick Action POST (Pick Up, Out for Delivery, Delivered)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && in_array(($_POST['action'] ?? ''), ['picked_up', 'out_for_delivery', 'delivered'], true)) {
    $csrf = $_POST['csrf_token'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    $actionStatus = $_POST['action'];

    if (!verifyCsrfToken($csrf)) {
        $error = 'Security token invalid.';
    } else {
        // Enforce driver assignment
        $chk = $pdo->prepare("SELECT order_id FROM `order_deliveries` WHERE `order_id` = ? AND `driver_id` = ?");
        $chk->execute([$orderId, $driverId]);
        if (!$chk->fetch()) {
            $error = 'This delivery is not assigned to your account.';
        } else {
            $res = dbUpdateDeliveryStatus($orderId, $actionStatus, [], $userId);
            if ($res['success']) {
                header("Location: index.php?msg=" . urlencode($res['message']));
                exit;
            } else {
                $error = $res['message'];
            }
        }
    }
}

// Fetch Active Delivery
$activeDelivery = dbGetDriverActiveDelivery($driverId);

// Fetch Driver Stats
$stats = dbGetDriverPortalStats($driverId);

// Fetch Next Assigned Orders in Queue
$queue = dbGetDriverDeliveriesQueue($driverId);
?>

<main class="flex-grow-1 py-4">
    <div class="container" style="max-width: 960px;">

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Welcome Banner Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-pink-soft text-primary-accent d-flex align-items-center justify-content-center flex-shrink-0" style="width: 54px; height: 54px; font-size: 1.4rem;">
                        <i class="bi bi-bicycle"></i>
                    </div>
                    <div>
                        <h1 class="h4 fw-bold text-dark mb-1">Welcome back, <?= htmlspecialchars($driver['full_name']); ?>!</h1>
                        <p class="text-muted small mb-0">Vehicle: <strong class="text-dark"><?= ucfirst($driver['vehicle_type']); ?> (<?= htmlspecialchars($driver['vehicle_number'] ?? 'N/A'); ?>)</strong> • FastBite Logistics</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge <?= $dutyBadgeClass; ?> px-3 py-2 rounded-pill fs-6 fw-semibold">
                        <?= $dutyLabel; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- 4-Stat KPI Grid -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card">
                    <span class="text-muted extra-small text-uppercase fw-bold">Delivered Today</span>
                    <div class="h3 fw-bold text-success my-1"><?= $stats['delivered_today']; ?></div>
                    <span class="extra-small text-muted">Successful drop-offs</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card">
                    <span class="text-muted extra-small text-uppercase fw-bold">Active Deliveries</span>
                    <div class="h3 fw-bold text-primary-accent my-1"><?= $stats['active_count']; ?></div>
                    <span class="extra-small text-muted">Currently in progress</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card">
                    <span class="text-muted extra-small text-uppercase fw-bold">Courier Rating</span>
                    <div class="h3 fw-bold text-warning my-1">
                        <i class="bi bi-star-fill text-warning fs-5"></i> <?= $stats['rating']; ?>
                    </div>
                    <span class="extra-small text-muted">Customer feedback</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card">
                    <span class="text-muted extra-small text-uppercase fw-bold">Total Completed</span>
                    <div class="h3 fw-bold text-dark my-1"><?= $stats['total_delivered']; ?></div>
                    <span class="extra-small text-muted">Lifetime completed</span>
                </div>
            </div>
        </div>

        <!-- ACTIVE DELIVERY HERO CARD -->
        <?php if ($activeDelivery): ?>
            <?php
                $adStatus = $activeDelivery['delivery_status'];
                $stageBadge = 'bg-primary text-white';
                if ($adStatus === 'assigned') $stageBadge = 'bg-info text-dark';
                elseif ($adStatus === 'picked_up') $stageBadge = 'bg-warning text-dark';
                elseif ($adStatus === 'out_for_delivery') $stageBadge = 'bg-danger text-white';
            ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white driver-card-active">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 pb-3 border-bottom mb-3">
                    <div>
                        <span class="badge bg-danger extra-small rounded-pill px-3 py-1 mb-2 text-uppercase fw-bold animate-pulse">
                            <i class="bi bi-record-circle-fill me-1"></i> Active Order in Transit
                        </span>
                        <h2 class="h5 fw-bold text-dark mb-1">Order #<?= htmlspecialchars($activeDelivery['order_number']); ?></h2>
                        <span class="text-muted extra-small">Assigned at <?= date('H:i, M j', strtotime($activeDelivery['assigned_at'] ?? $activeDelivery['created_at'])); ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge <?= $stageBadge; ?> fs-6 rounded-pill px-3 py-2">
                            Stage: <?= ucwords(str_replace('_', ' ', $adStatus)); ?>
                        </span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Customer &amp; Contact</span>
                            <strong class="d-block text-dark mb-1"><?= htmlspecialchars($activeDelivery['customer_name']); ?></strong>
                            <?php if (!empty($activeDelivery['customer_phone'])): ?>
                                <a href="tel:<?= urlencode($activeDelivery['customer_phone']); ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 extra-small">
                                    <i class="bi bi-telephone-fill me-1"></i> Call <?= htmlspecialchars($activeDelivery['customer_phone']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Delivery Address</span>
                            <div class="text-dark fw-semibold small mb-1">
                                <i class="bi bi-geo-alt-fill text-primary-accent me-1"></i>
                                <?= htmlspecialchars($activeDelivery['delivery_address']); ?>
                            </div>
                            <?php if (!empty($activeDelivery['order_notes'])): ?>
                                <span class="extra-small text-danger d-block"><strong>Note:</strong> <?= htmlspecialchars($activeDelivery['order_notes']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Amount & COD Info -->
                <div class="p-3 rounded-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2 <?= ($activeDelivery['payment_method'] === 'cash_on_delivery' && $activeDelivery['payment_status'] !== 'paid') ? 'bg-warning-subtle text-dark border border-warning' : 'bg-success-subtle text-success border border-success'; ?>">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= ($activeDelivery['payment_method'] === 'cash_on_delivery' && $activeDelivery['payment_status'] !== 'paid') ? 'bi-cash-coin' : 'bi-check-circle-fill'; ?> fs-4"></i>
                        <div>
                            <?php if ($activeDelivery['payment_method'] === 'cash_on_delivery' && $activeDelivery['payment_status'] !== 'paid'): ?>
                                <strong class="d-block small">COLLECT CASH ON DELIVERY</strong>
                                <span class="extra-small">Please collect exact amount from customer upon drop-off.</span>
                            <?php else: ?>
                                <strong class="d-block small">PAID ONLINE</strong>
                                <span class="extra-small">No cash collection needed. Simply hand over the order.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="extra-small d-block text-muted">Total Amount</span>
                        <strong class="fs-5">$<?= number_format((float)$activeDelivery['total'], 2); ?></strong>
                    </div>
                </div>

                <!-- Stage Transition Quick Actions -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-3 border-top">
                    <a href="delivery.php?id=<?= $activeDelivery['order_id']; ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="bi bi-info-circle me-1"></i> Full Order Details
                    </a>

                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($adStatus === 'assigned'): ?>
                            <form method="POST" action="index.php">
                                <?= csrfField(); ?>
                                <input type="hidden" name="action" value="picked_up">
                                <input type="hidden" name="order_id" value="<?= $activeDelivery['order_id']; ?>">
                                <button type="submit" class="btn btn-warning rounded-pill px-4 shadow-sm text-dark fw-bold">
                                    <i class="bi bi-box-seam me-1"></i> Pick Up from Kitchen
                                </button>
                            </form>
                        <?php elseif ($adStatus === 'picked_up'): ?>
                            <form method="POST" action="index.php">
                                <?= csrfField(); ?>
                                <input type="hidden" name="action" value="out_for_delivery">
                                <input type="hidden" name="order_id" value="<?= $activeDelivery['order_id']; ?>">
                                <button type="submit" class="btn btn-primary-accent text-white rounded-pill px-4 shadow-sm fw-bold">
                                    <i class="bi bi-bicycle me-1"></i> Start Transit / Out for Delivery
                                </button>
                            </form>
                        <?php elseif ($adStatus === 'out_for_delivery'): ?>
                            <form method="POST" action="index.php" onsubmit="return confirm('Confirm order delivered to customer?');">
                                <?= csrfField(); ?>
                                <input type="hidden" name="action" value="delivered">
                                <input type="hidden" name="order_id" value="<?= $activeDelivery['order_id']; ?>">
                                <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold">
                                    <i class="bi bi-check-circle-fill me-1"></i> Confirm Delivered
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- No Active Delivery Card -->
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white mb-4">
                <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 72px; height: 72px; font-size: 2rem;">
                    <i class="bi bi-check2-all"></i>
                </div>
                <h2 class="h5 fw-bold text-dark mb-2">No Active Delivery in Transit</h2>
                <p class="text-muted small mb-3">You are ready to receive new delivery dispatches. Keep your shift duty set to <strong>Online</strong>.</p>
                <a href="orders.php" class="btn btn-outline-pink btn-sm rounded-pill px-4">View Deliveries Queue</a>
            </div>
        <?php endif; ?>

        <!-- Assigned Deliveries Queue Overview -->
        <?php if (!empty($queue)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <h2 class="h6 fw-bold text-dark mb-0">Assigned Orders Queue</h2>
                    <span class="badge bg-pink-soft text-primary-accent border extra-small rounded-pill px-2"><?= count($queue); ?> Orders</span>
                </div>

                <div class="list-group list-group-flush">
                    <?php foreach ($queue as $q): ?>
                        <div class="list-group-item px-0 py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <strong class="font-monospace text-primary-accent">#<?= htmlspecialchars($q['order_number']); ?></strong>
                                    <span class="badge bg-light text-dark border extra-small rounded-pill px-2"><?= ucwords(str_replace('_', ' ', $q['delivery_status'])); ?></span>
                                </div>
                                <span class="small text-dark d-block"><?= htmlspecialchars($q['delivery_address']); ?></span>
                                <span class="extra-small text-muted">Customer: <?= htmlspecialchars($q['customer_name']); ?> • Total: $<?= number_format((float)$q['total'], 2); ?></span>
                            </div>
                            <div class="flex-shrink-0">
                                <a href="delivery.php?id=<?= $q['order_id']; ?>" class="btn btn-outline-pink btn-sm rounded-pill px-3 extra-small">
                                    Manage Delivery
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
