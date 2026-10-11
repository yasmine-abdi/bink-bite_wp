<?php
/**
 * FastBite - Single Delivery Detail & Actions
 * STEP 33: Complete Business Platform, Payments, Analytics, Support, Courier Portal & Production Security
 */

$pageTitle = 'Delivery Operation';
$activePage = 'orders';

require_once __DIR__ . '/includes/header.php';

$orderId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT od.*,
    o.order_number,
    o.subtotal,
    o.tax,
    o.delivery_fee,
    o.discount,
    o.coupon_discount,
    o.total,
    o.payment_method,
    o.payment_status,
    o.order_status,
    o.delivery_address,
    o.notes as order_notes,
    o.created_at as order_created_at,
    u.name as customer_name,
    u.phone as customer_phone,
    u.email as customer_email
    FROM `order_deliveries` od
    JOIN `orders` o ON od.order_id = o.id
    JOIN `users` u ON o.user_id = u.id
    WHERE od.order_id = ? AND od.driver_id = ?");
$stmt->execute([$orderId, $driverId]);
$delivery = $stmt->fetch();

if (!$delivery) {
    header("Location: orders.php?error=" . urlencode("Delivery not found or not assigned to you."));
    exit;
}

// Fetch order items
$stmtItems = $pdo->prepare("SELECT * FROM `order_items` WHERE `order_id` = ?");
$stmtItems->execute([$orderId]);
$orderItems = $stmtItems->fetchAll();

$error = '';
$msg = $_GET['msg'] ?? '';

// Handle Delivery State Transitions & Failure Reporting
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if (in_array($action, ['picked_up', 'out_for_delivery', 'delivered'], true)) {
            $notes = trim($_POST['delivery_notes'] ?? '');
            $res = dbUpdateDeliveryStatus($orderId, $action, ['notes' => $notes], $userId);
            if ($res['success']) {
                if ($action === 'delivered') {
                    header("Location: index.php?msg=" . urlencode("Order #{$delivery['order_number']} marked as Delivered. Great job!"));
                    exit;
                } else {
                    header("Location: delivery.php?id={$orderId}&msg=" . urlencode($res['message']));
                    exit;
                }
            } else {
                $error = $res['message'];
            }
        } elseif ($action === 'report_failed') {
            $reason = trim($_POST['failure_reason'] ?? '');
            $notes = trim($_POST['delivery_notes'] ?? '');
            $res = dbUpdateDeliveryStatus($orderId, 'failed', ['failure_reason' => $reason, 'notes' => $notes], $userId);
            if ($res['success']) {
                header("Location: index.php?msg=" . urlencode("Delivery issue logged for Order #{$delivery['order_number']}."));
                exit;
            } else {
                $error = $res['message'];
            }
        }
    }
}

// Refresh record after POST
$stmt->execute([$orderId, $driverId]);
$delivery = $stmt->fetch();
$adStatus = $delivery['delivery_status'];
?>

<main class="flex-grow-1 py-4">
    <div class="container" style="max-width: 800px;">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0 extra-small">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="orders.php" class="text-decoration-none text-muted">Deliveries</a></li>
                <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">#<?= htmlspecialchars($delivery['order_number']); ?></li>
            </ol>
        </nav>

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

        <!-- Order Header Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 pb-3 border-bottom mb-3">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Order #<?= htmlspecialchars($delivery['order_number']); ?></h1>
                    <span class="text-muted extra-small">Placed on <?= date('M j, Y, H:i', strtotime($delivery['order_created_at'])); ?></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary extra-small rounded-pill px-3 py-2 text-uppercase">
                        Status: <?= str_replace('_', ' ', $adStatus); ?>
                    </span>
                </div>
            </div>

            <!-- Customer & Destination Box -->
            <div class="p-3 bg-light rounded-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="text-dark small"><i class="bi bi-person-fill me-1 text-primary-accent"></i> <?= htmlspecialchars($delivery['customer_name']); ?></strong>
                    <?php if (!empty($delivery['customer_phone'])): ?>
                        <a href="tel:<?= urlencode($delivery['customer_phone']); ?>" class="btn btn-sm btn-success rounded-pill px-3 extra-small">
                            <i class="bi bi-telephone-fill me-1"></i> Call Customer
                        </a>
                    <?php endif; ?>
                </div>
                <div class="text-dark small fw-semibold">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($delivery['delivery_address']); ?>
                </div>
                <?php if (!empty($delivery['order_notes'])): ?>
                    <div class="extra-small text-danger mt-2">
                        <strong>Customer Instructions:</strong> <?= htmlspecialchars($delivery['order_notes']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payment Summary -->
            <div class="p-3 rounded-3 mb-3 <?= ($delivery['payment_method'] === 'cash_on_delivery' && $delivery['payment_status'] !== 'paid') ? 'bg-warning-subtle text-dark border border-warning' : 'bg-success-subtle text-success border border-success'; ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="small d-block">
                            <?= ($delivery['payment_method'] === 'cash_on_delivery' && $delivery['payment_status'] !== 'paid') ? 'CASH ON DELIVERY TO COLLECT' : 'PAID IN ADVANCE (ONLINE)'; ?>
                        </strong>
                        <span class="extra-small">Payment Method: <?= ucwords(str_replace('_', ' ', $delivery['payment_method'])); ?></span>
                    </div>
                    <strong class="fs-4">$<?= number_format((float)$delivery['total'], 2); ?></strong>
                </div>
            </div>

            <!-- Itemized Food Items List -->
            <h2 class="h6 fw-bold text-dark mb-3">Food Items Checklist</h2>
            <div class="list-group list-group-flush mb-4">
                <?php foreach ($orderItems as $item): ?>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center extra-small">
                        <div>
                            <span class="badge bg-pink-soft text-primary-accent me-2"><?= (int)$item['quantity']; ?>x</span>
                            <strong class="text-dark"><?= htmlspecialchars($item['food_name']); ?></strong>
                            <span class="text-muted ms-1">(<?= htmlspecialchars($item['food_category'] ?? ''); ?>)</span>
                        </div>
                        <span class="fw-bold text-dark">$<?= number_format((float)$item['subtotal'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Action Controls -->
            <div class="pt-3 border-top d-flex flex-column flex-sm-row justify-content-between gap-2">
                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#reportIssueModal">
                    <i class="bi bi-exclamation-octagon me-1"></i> Report Issue / Failed
                </button>

                <div class="d-flex gap-2">
                    <?php if ($adStatus === 'assigned'): ?>
                        <form method="POST" action="delivery.php?id=<?= $orderId; ?>">
                            <?= csrfField(); ?>
                            <input type="hidden" name="action" value="picked_up">
                            <button type="submit" class="btn btn-warning rounded-pill px-4 shadow-sm text-dark fw-bold">
                                <i class="bi bi-box-seam me-1"></i> Pick Up from Kitchen
                            </button>
                        </form>
                    <?php elseif ($adStatus === 'picked_up'): ?>
                        <form method="POST" action="delivery.php?id=<?= $orderId; ?>">
                            <?= csrfField(); ?>
                            <input type="hidden" name="action" value="out_for_delivery">
                            <button type="submit" class="btn btn-primary-accent text-white rounded-pill px-4 shadow-sm fw-bold">
                                <i class="bi bi-bicycle me-1"></i> Start Out for Delivery
                            </button>
                        </form>
                    <?php elseif ($adStatus === 'out_for_delivery'): ?>
                        <form method="POST" action="delivery.php?id=<?= $orderId; ?>" onsubmit="return confirm('Confirm complete delivery to customer?');">
                            <?= csrfField(); ?>
                            <input type="hidden" name="action" value="delivered">
                            <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold">
                                <i class="bi bi-check-circle-fill me-1"></i> Confirm Delivered
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Report Issue Modal -->
<div class="modal fade" id="reportIssueModal" tabindex="-1" aria-labelledby="reportIssueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark fs-6" id="reportIssueModalLabel">Report Delivery Issue</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="delivery.php?id=<?= $orderId; ?>">
                <?= csrfField(); ?>
                <input type="hidden" name="action" value="report_failed">
                <div class="modal-body">
                    <p class="text-muted extra-small mb-3">If you cannot complete this delivery, select the issue reason. FastBite dispatch will be notified immediately.</p>
                    
                    <div class="mb-3">
                        <label for="failureReason" class="form-label small fw-bold text-dark">Failure Reason <span class="text-danger">*</span></label>
                        <select name="failure_reason" id="failureReason" class="form-select rounded-3" required>
                            <option value="Customer unreachable by phone">Customer unreachable by phone</option>
                            <option value="Incorrect delivery address">Incorrect / unlocatable delivery address</option>
                            <option value="Customer cancelled at door">Customer cancelled / refused at door</option>
                            <option value="Courier vehicle breakdown">Courier vehicle mechanical breakdown</option>
                            <option value="Kitchen package damaged">Order package damaged in transit</option>
                            <option value="Other delivery impediment">Other delivery impediment</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="deliveryNotes" class="form-label small fw-bold text-dark">Additional Notes</label>
                        <textarea name="delivery_notes" id="deliveryNotes" rows="3" class="form-control rounded-3" placeholder="Provide extra context if necessary..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Submit Failure Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
