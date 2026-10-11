<?php
/**
 * FastBite - Order Details, Fulfillment Tracking & Professional Receipt
 * STEP 31: Authentication Finalization, Google Sign-In & Professional Receipts
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';
require_once __DIR__ . '/includes/payment.php';

requireLogin('login.php');

$user = currentUser();
if (!$user) {
    header("Location: login.php");
    exit;
}

$currentPage = 'orders';
$projectName = 'FastBite';
$userId = (int)$user['id'];
$isAdminUser = isAdmin();

$orderNum = trim($_GET['order'] ?? '');
$rawNum = preg_replace('/^#/', '', $orderNum);

// Fetch order from DB: strictly scoped to current user if not admin
$order = !empty($rawNum) ? dbGetOrderByNumber($rawNum, $isAdminUser ? null : $userId) : null;
$paymentTxn = $order ? dbGetPaymentForOrder($order['id']) : null;

// Authoritative restaurant contact
$restName = dbGetSetting('restaurant_name', 'FastBite Fast Food');
$restPhone = dbGetSetting('restaurant_phone', '+252 63 444 5555');
$restEmail = dbGetSetting('restaurant_email', 'support@fastbite.com');
$restAddress = dbGetSetting('restaurant_address', 'Main Hospitality Blvd, City Center, Hargeisa');

$successMsg = $_GET['msg'] ?? '';
$errorMsg = $_GET['error'] ?? '';

// Handle Customer Cancellation POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'cancel_order' && $order) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = "Security validation failed. Please refresh and try again.";
    } else {
        $res = dbCustomerCancelOrder($order['id'], $userId);
        if ($res['success']) {
            header("Location: order-details.php?order=" . urlencode($order['order_number']) . "&msg=" . urlencode("Your order has been cancelled successfully."));
            exit;
        } else {
            $errorMsg = $res['error'] ?? "Failed to cancel order.";
        }
    }
}

// Handle Reorder POST with live availability verification
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'reorder' && $order) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = "Security validation failed. Please refresh and try again.";
    } else {
        if (!empty($order['items'])) {
            $addedCount = 0;
            $soldOutCount = 0;
            foreach ($order['items'] as $it) {
                if (!empty($it['food_id'])) {
                    $foodCheck = dbGetFoodById((int)$it['food_id']);
                    if ($foodCheck && $foodCheck['is_available']) {
                        dbAddToCart($userId, (int)$it['food_id'], (int)$it['quantity']);
                        $addedCount++;
                    } else {
                        $soldOutCount++;
                    }
                }
            }
            if ($addedCount > 0) {
                $msg = "{$addedCount} item(s) from order {$order['order_number']} added to your cart at current prices.";
                if ($soldOutCount > 0) {
                    $msg .= " ({$soldOutCount} sold-out item(s) were skipped).";
                }
                header("Location: cart.php?msg=" . urlencode($msg));
                exit;
            } else {
                $errorMsg = "Unable to reorder: The items in this order are currently out of stock.";
            }
        }
    }
}

// Status configurations
$statusConfigs = [
    'pending' => [
        'step' => 1,
        'label' => 'Order Received',
        'badge' => 'bg-warning text-dark border border-warning',
        'icon' => 'bi-clock',
        'message' => 'Your order has been received and is waiting for kitchen confirmation.'
    ],
    'confirmed' => [
        'step' => 2,
        'label' => 'Order Confirmed',
        'badge' => 'bg-primary-subtle text-primary border border-primary-subtle',
        'icon' => 'bi-check2-circle',
        'message' => 'Your order has been confirmed by the restaurant.'
    ],
    'preparing' => [
        'step' => 3,
        'label' => 'Preparing in Kitchen',
        'badge' => 'bg-danger-subtle text-danger border border-danger-subtle',
        'icon' => 'bi-fire',
        'message' => 'Our chefs are freshly cooking your delicious meal right now.'
    ],
    'out_for_delivery' => [
        'step' => 4,
        'label' => 'Out for Delivery',
        'badge' => 'bg-info-subtle text-info border border-info-subtle',
        'icon' => 'bi-truck',
        'message' => 'Your food is on the way with our fast delivery courier.'
    ],
    'delivered' => [
        'step' => 5,
        'label' => 'Delivered',
        'badge' => 'bg-success-subtle text-success border border-success-subtle',
        'icon' => 'bi-check-circle-fill',
        'message' => 'Your order was delivered successfully. Enjoy your meal!'
    ],
    'cancelled' => [
        'step' => 0,
        'label' => 'Cancelled',
        'badge' => 'bg-secondary-subtle text-muted border',
        'icon' => 'bi-x-circle-fill',
        'message' => 'This order was cancelled.'
    ]
];

$orderStatus = $order ? $order['order_status'] : 'pending';
$statusInfo = $statusConfigs[$orderStatus] ?? $statusConfigs['pending'];
$currentStep = $statusInfo['step'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking #<?= htmlspecialchars($orderNum); ?> — <?= htmlspecialchars($projectName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @media print {
            body { background: #fff !important; color: #000 !important; font-size: 12pt; }
            .no-print, .main-navbar, .main-footer, .breadcrumb, .alert, .btn, .modal, .d-print-none, .dashboard-eyebrow, .live-tracking-card { display: none !important; }
            .receipt-print-wrapper { box-shadow: none !important; border: 1px solid #ccc !important; width: 100% !important; margin: 0 !important; padding: 15px !important; }
            .card { border: none !important; box-shadow: none !important; }
            a { text-decoration: none !important; color: #000 !important; }
            .badge { border: 1px solid #999 !important; color: #000 !important; background: transparent !important; }
        }
    </style>
</head>
<body class="dashboard-body d-flex flex-column min-vh-100">

    <div class="d-print-none">
        <?php include_once __DIR__ . '/includes/navbar.php'; ?>
    </div>

    <main id="main-content" class="flex-grow-1 py-4 py-lg-5">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-4 d-print-none">
                <ol class="breadcrumb extra-small mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted"><i class="bi bi-house me-1"></i>Home</a></li>
                    <li class="breadcrumb-item"><a href="customer-dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="my-orders.php" class="text-decoration-none text-muted">My Orders</a></li>
                    <li class="breadcrumb-item active text-primary-accent fw-bold" aria-current="page">#<?= htmlspecialchars($order['order_number'] ?? $orderNum); ?></li>
                </ol>
            </nav>

            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm d-print-none" role="alert">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm d-print-none" role="alert">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                    <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!$order): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4 bg-white">
                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-receipt fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-2">Order Not Found</h3>
                    <p class="text-muted small mb-4">We could not locate this order record in your account.</p>
                    <div>
                        <a href="my-orders.php" class="btn btn-auth-primary rounded-pill px-4">Back to My Orders</a>
                    </div>
                </div>
            <?php else: ?>

                <!-- Page Header with Actions (Hidden in Print) -->
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-3 border-bottom d-print-none">
                    <div>
                        <span class="dashboard-eyebrow d-inline-block">ORDER STATUS</span>
                        <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                            <span>Order #<?= htmlspecialchars($order['order_number']); ?></span>
                            <span class="badge rounded-pill <?= $statusInfo['badge']; ?> fs-6"><?= $statusInfo['label']; ?></span>
                        </h1>
                        <p class="text-muted small mb-0">Placed on <?= date('F j, Y  h:i A', strtotime($order['created_at'])); ?></p>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <a href="receipt.php?order=<?= urlencode($order['order_number']); ?>" class="btn btn-outline-pink rounded-pill px-3 d-inline-flex align-items-center gap-1 shadow-sm fw-semibold">
                            <i class="bi bi-receipt"></i>
                            <span>Official Receipt</span>
                        </a>
                        <a href="receipt.php?order=<?= urlencode($order['order_number']); ?>&print=1" target="_blank" class="btn btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1 shadow-sm">
                            <i class="bi bi-printer"></i>
                            <span>Print Receipt</span>
                        </a>
                        <a href="receipt.php?order=<?= urlencode($order['order_number']); ?>&format=thermal&print=1" target="_blank" class="btn btn-outline-dark rounded-pill px-3 d-inline-flex align-items-center gap-1 shadow-sm">
                            <i class="bi bi-receipt-cutoff"></i>
                            <span>Print Thermal Receipt</span>
                        </a>
                        
                        <form method="POST" action="order-details.php?order=<?= urlencode($order['order_number']); ?>" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="reorder">
                            <button type="submit" class="btn btn-auth-primary rounded-pill px-3 d-inline-flex align-items-center gap-1 shadow-sm">
                                <i class="bi bi-arrow-repeat"></i>
                                <span>Reorder Items</span>
                            </button>
                        </form>

                        <?php if ($orderStatus === 'pending'): ?>
                            <button type="button" class="btn btn-outline-danger rounded-pill px-3 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#customerCancelModal">
                                <i class="bi bi-x-circle"></i>
                                <span>Cancel</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (($order['receipt_send_status'] ?? '') === 'sent'): ?>
                    <div class="alert alert-info border-0 rounded-4 shadow-sm p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2 d-print-none bg-info-subtle text-info-emphasis">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-envelope-check-fill fs-5 text-info"></i>
                            <span class="small">Your official receipt was sent to <strong><?= htmlspecialchars($order['customer_email'] ?? currentUser()['email'] ?? 'your email'); ?></strong><?= !empty($order['receipt_sent_at']) ? ' on ' . date('M d, Y h:i A', strtotime($order['receipt_sent_at'])) : ''; ?>.</span>
                        </div>
                        <a href="receipt.php?order=<?= urlencode($order['order_number']); ?>" class="btn btn-sm btn-info text-white rounded-pill px-3 extra-small fw-semibold">
                            View Receipt &rarr;
                        </a>
                    </div>
                <?php endif; ?>

                <!-- 5-Step Fulfillment Stepper Card (Hidden in Print) -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white live-tracking-card d-print-none">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi <?= $statusInfo['icon']; ?> text-primary-accent fs-5"></i>
                            <h2 class="h6 fw-bold text-dark mb-0">Fulfillment Progress</h2>
                        </div>
                        <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small fw-semibold">
                            Est. Delivery: <?= htmlspecialchars($order['estimated_delivery'] ?? '25–35 minutes'); ?>
                        </span>
                    </div>

                    <?php if ($orderStatus === 'cancelled'): ?>
                        <div class="alert alert-danger mb-0 rounded-3 d-flex align-items-center gap-3">
                            <i class="bi bi-x-octagon-fill fs-3 text-danger flex-shrink-0"></i>
                            <div>
                                <h3 class="h6 fw-bold mb-0 text-danger">Order Cancelled</h3>
                                <p class="extra-small mb-0 text-muted">This order was cancelled on <?= date('M d, Y  h:i A', strtotime($order['updated_at'] ?? $order['created_at'])); ?>.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row g-2 text-center">
                            <?php 
                            $steps = [
                                1 => ['name' => '01 Placed', 'icon' => 'bi-receipt'],
                                2 => ['name' => '02 Confirmed', 'icon' => 'bi-check2-circle'],
                                3 => ['name' => '03 Preparing', 'icon' => 'bi-fire'],
                                4 => ['name' => '04 On the Way', 'icon' => 'bi-truck'],
                                5 => ['name' => '05 Delivered', 'icon' => 'bi-bag-check-fill']
                            ];
                            foreach ($steps as $num => $st): 
                                $isActive = ($currentStep >= $num);
                                $isCurrent = ($currentStep === $num);
                            ?>
                                <div class="col">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center mb-2 <?= $isCurrent ? 'bg-primary-accent text-white shadow' : ($isActive ? 'bg-success text-white' : 'bg-light text-muted border'); ?>" style="width: 44px; height: 44px;">
                                            <i class="bi <?= $st['icon']; ?> fs-5"></i>
                                        </div>
                                        <span class="extra-small fw-bold <?= $isActive ? 'text-dark' : 'text-muted opacity-75'; ?>"><?= $st['name']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Delivery Courier & Milestone Tracker Card (Hidden in Print) -->
                <?php 
                $delivery = function_exists('dbGetOrderDelivery') && $order ? dbGetOrderDelivery((int)$order['id']) : null;
                $delivStatus = $delivery ? $delivery['delivery_status'] : 'unassigned';
                ?>
                <?php if ($order && $orderStatus !== 'cancelled'): ?>
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white d-print-none">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-bicycle text-primary-accent fs-5"></i>
                                <h2 class="h6 fw-bold text-dark mb-0">Delivery &amp; Courier Details</h2>
                            </div>
                            <?php 
                            $delivBadge = 'bg-secondary-subtle text-secondary';
                            if ($delivStatus === 'unassigned') $delivBadge = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                            elseif ($delivStatus === 'assigned') $delivBadge = 'bg-primary-subtle text-primary border border-primary-subtle';
                            elseif ($delivStatus === 'picked_up') $delivBadge = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                            elseif ($delivStatus === 'out_for_delivery') $delivBadge = 'bg-danger-subtle text-danger border border-danger-subtle';
                            elseif ($delivStatus === 'delivered') $delivBadge = 'bg-success-subtle text-success border border-success-subtle';
                            elseif ($delivStatus === 'failed') $delivBadge = 'bg-danger text-white';
                            ?>
                            <span class="badge rounded-pill <?= $delivBadge; ?> extra-small fw-semibold">
                                <?= ucfirst(str_replace('_', ' ', $delivStatus)); ?>
                            </span>
                        </div>

                        <?php if (!empty($delivery['driver_name'])): ?>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 p-3 rounded-4 bg-light mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-pink-soft text-primary-accent d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; font-size: 1.3rem;">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                    <div>
                                        <strong class="d-block text-dark small"><?= htmlspecialchars($delivery['driver_name']); ?></strong>
                                        <div class="extra-small text-muted">
                                            <span><i class="bi bi-bicycle text-primary-accent me-1"></i><?= ucfirst($delivery['vehicle_type']); ?> (<?= htmlspecialchars($delivery['vehicle_number'] ?? 'Standard'); ?>)</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <a href="tel:<?= htmlspecialchars($delivery['driver_phone']); ?>" class="btn btn-outline-pink btn-sm rounded-pill px-3 extra-small fw-semibold d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-telephone-fill"></i>
                                        <span><?= htmlspecialchars($delivery['driver_phone']); ?></span>
                                    </a>
                                </div>
                            </div>
                        <?php elseif ($delivStatus === 'unassigned'): ?>
                            <div class="p-3 bg-light rounded-4 text-center mb-3">
                                <span class="extra-small text-muted d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-clock-history text-warning fs-6"></i>
                                    <span>Preparing your delivery assignment — A FastBite courier will be dispatched to pick up your fresh meal shortly.</span>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if ($delivStatus === 'failed' && !empty($delivery['failure_reason'])): ?>
                            <div class="alert alert-danger py-2 px-3 rounded-3 extra-small mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                                <div><strong>Delivery Issue:</strong> <?= htmlspecialchars($delivery['failure_reason']); ?>. Our support team is assisting.</div>
                            </div>
                        <?php endif; ?>

                        <!-- Delivery Timestamps Timeline -->
                        <div class="row g-2 text-center pt-2">
                            <div class="col-3">
                                <span class="d-block extra-small text-muted fw-bold">Assigned</span>
                                <span class="extra-small text-dark"><?= !empty($delivery['assigned_at']) ? date('h:i A', strtotime($delivery['assigned_at'])) : '—'; ?></span>
                            </div>
                            <div class="col-3">
                                <span class="d-block extra-small text-muted fw-bold">Picked Up</span>
                                <span class="extra-small text-dark"><?= !empty($delivery['picked_up_at']) ? date('h:i A', strtotime($delivery['picked_up_at'])) : '—'; ?></span>
                            </div>
                            <div class="col-3">
                                <span class="d-block extra-small text-muted fw-bold">Out for Delivery</span>
                                <span class="extra-small text-dark"><?= !empty($delivery['out_for_delivery_at']) ? date('h:i A', strtotime($delivery['out_for_delivery_at'])) : '—'; ?></span>
                            </div>
                            <div class="col-3">
                                <span class="d-block extra-small text-muted fw-bold">Delivered</span>
                                <span class="extra-small text-dark"><?= !empty($delivery['delivered_at']) ? date('h:i A', strtotime($delivery['delivered_at'])) : '—'; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Professional FastBite Order Receipt Container -->
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white receipt-print-wrapper mb-4" id="orderReceiptPrintBlock">
                    <!-- Receipt Header -->
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start pb-4 mb-4 border-bottom gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="brand-icon d-flex align-items-center justify-content-center bg-primary-accent text-white rounded-3 p-2" style="width: 38px; height: 38px;">
                                    <i class="bi bi-fire fs-5"></i>
                                </span>
                                <span class="fs-4 fw-bold text-dark">Fast<span class="text-primary-accent">Bite</span></span>
                            </div>
                            <p class="extra-small text-muted mb-0"><?= htmlspecialchars($restName); ?></p>
                            <p class="extra-small text-muted mb-0"><?= htmlspecialchars($restAddress); ?></p>
                            <p class="extra-small text-muted mb-0">Phone: <?= htmlspecialchars($restPhone); ?> | Email: <?= htmlspecialchars($restEmail); ?></p>
                        </div>
                        <div class="text-sm-end">
                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-3 py-1 rounded-pill small fw-bold mb-1">OFFICIAL RECEIPT</span>
                            <h2 class="h6 fw-bold text-dark mb-1">Order #<?= htmlspecialchars($order['order_number']); ?></h2>
                            <p class="extra-small text-muted mb-0">Date: <?= date('F j, Y  h:i A', strtotime($order['created_at'])); ?></p>
                            <p class="extra-small text-muted mb-0">Payment: <?= htmlspecialchars(getPaymentMethodLabel($order['payment_method'])); ?> (<?= ucfirst($order['payment_status']); ?>)</p>
                        </div>
                    </div>

                    <!-- Customer & Delivery Meta -->
                    <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-12 col-sm-6">
                            <span class="extra-small text-muted text-uppercase fw-bold d-block mb-1">Customer Information</span>
                            <strong class="d-block text-dark small"><?= htmlspecialchars($order['customer_name'] ?? $user['name']); ?></strong>
                            <span class="d-block extra-small text-muted"><?= htmlspecialchars($order['customer_email'] ?? $user['email']); ?></span>
                            <span class="d-block extra-small text-muted"><?= htmlspecialchars($order['customer_phone'] ?? $user['phone'] ?? '+252 63 000 0000'); ?></span>
                        </div>
                        <div class="col-12 col-sm-6 text-sm-end">
                            <span class="extra-small text-muted text-uppercase fw-bold d-block mb-1">Delivery Destination</span>
                            <span class="d-block small text-dark"><?= htmlspecialchars($order['delivery_address']); ?></span>
                            <span class="d-block extra-small text-muted mt-1">Status: <strong><?= ucfirst(str_replace('_', ' ', $order['order_status'])); ?></strong></span>
                        </div>
                    </div>

                    <!-- Ordered Items Breakdown Table -->
                    <div class="table-responsive mb-4">
                        <table class="table align-middle receipt-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="border-0 extra-small text-uppercase">Item Description</th>
                                    <th scope="col" class="border-0 text-center extra-small text-uppercase">Unit Price</th>
                                    <th scope="col" class="border-0 text-center extra-small text-uppercase">Qty</th>
                                    <th scope="col" class="border-0 text-center extra-small text-uppercase">Discount</th>
                                    <th scope="col" class="border-0 text-end extra-small text-uppercase">Total</th>
                                    <th scope="col" class="border-0 text-center extra-small text-uppercase d-print-none" style="width: 80px;">Review</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?= htmlspecialchars(getFoodImage($item['image'] ?? '')); ?>" 
                                                     alt="<?= htmlspecialchars($item['food_name']); ?>" 
                                                     class="rounded-2 object-fit-cover d-print-none" 
                                                     style="width: 44px; height: 44px;" 
                                                     onerror="handleImageError(this, 'food')">
                                                <div>
                                                    <strong class="d-block small text-dark"><?= htmlspecialchars($item['food_name']); ?></strong>
                                                    <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small"><?= htmlspecialchars($item['food_category'] ?? 'Food'); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center small text-dark">$<?= number_format((float)$item['unit_price'], 2); ?></td>
                                        <td class="text-center small text-dark fw-bold">&times; <?= (int)$item['quantity']; ?></td>
                                        <td class="text-center small text-success">
                                            <?= (float)$item['discount'] > 0 ? '-$' . number_format((float)$item['discount'], 2) : '$0.00'; ?>
                                        </td>
                                        <td class="text-end small fw-bold text-dark">$<?= number_format((float)$item['subtotal'], 2); ?></td>
                                        <td class="text-center d-print-none">
                                            <?php if ($order['order_status'] === 'delivered' && !empty($item['food_id'])): ?>
                                                <a href="food-details.php?id=<?= $item['food_id']; ?>#reviews-section" class="btn btn-outline-pink btn-sm py-1 px-2 extra-small text-nowrap d-inline-flex align-items-center gap-1" title="Write a review">
                                                    <i class="bi bi-star"></i>
                                                    <span>Review</span>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted extra-small">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Financial Summary & Notes -->
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <?php if (!empty($order['notes'])): ?>
                                <div class="p-3 bg-light rounded-3 border extra-small text-muted mb-3">
                                    <strong class="text-dark d-block mb-1"><i class="bi bi-chat-left-text me-1"></i>Special Instructions:</strong>
                                    <?= htmlspecialchars($order['notes']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="extra-small text-muted">
                                <p class="mb-1"><strong>FastBite Kitchen &amp; Delivery:</strong> Every meal is cooked to order and transported in temperature-controlled bags.</p>
                                <p class="mb-0">Questions regarding this receipt? Call <strong><?= htmlspecialchars($restPhone); ?></strong>.</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-5 ms-auto">
                            <div class="vstack gap-2">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>Food Subtotal</span>
                                    <strong class="text-dark">$<?= number_format((float)$order['subtotal'], 2); ?></strong>
                                </div>
                                <?php if ((float)$order['discount'] > 0): ?>
                                    <div class="d-flex justify-content-between small text-success">
                                        <span>Menu Item Discounts</span>
                                        <strong class="text-success">-$<?= number_format((float)$order['discount'], 2); ?></strong>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($order['coupon_discount']) && (float)$order['coupon_discount'] > 0): ?>
                                    <div class="d-flex justify-content-between small text-primary-accent fw-bold">
                                        <span>Promo Coupon (<?= htmlspecialchars($order['coupon_code'] ?? 'PROMO'); ?>)</span>
                                        <strong class="text-primary-accent">-$<?= number_format((float)$order['coupon_discount'], 2); ?></strong>
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>Delivery Fee</span>
                                    <strong class="text-dark"><?= (float)$order['delivery_fee'] > 0 ? '$' . number_format((float)$order['delivery_fee'], 2) : '<span class="text-success">FREE</span>'; ?></strong>
                                </div>
                                <?php if (isset($order['tax']) && (float)$order['tax'] > 0): ?>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>Estimated Tax</span>
                                        <strong class="text-dark">$<?= number_format((float)$order['tax'], 2); ?></strong>
                                    </div>
                                <?php endif; ?>
                                <hr class="my-1 text-muted opacity-25">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark fs-6">Grand Total</span>
                                    <span class="fs-4 fw-bold text-primary-accent">$<?= number_format((float)$order['total'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Receipt Actions Toolbar (Hidden in Print) -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-3 mt-4 border-top d-print-none bg-light p-3 rounded-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check text-success fs-5"></i>
                            <span class="small fw-semibold text-dark">Official Customer Receipt &amp; Tax Invoice</span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <a href="receipt.php?order=<?= urlencode($order['order_number']); ?>" class="btn btn-outline-pink btn-sm rounded-pill px-3 fw-semibold">
                                <i class="bi bi-receipt me-1"></i>Official Receipt
                            </a>
                            <a href="receipt.php?order=<?= urlencode($order['order_number']); ?>&print=1" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                <i class="bi bi-printer me-1"></i>Print Receipt (A4)
                            </a>
                            <a href="receipt.php?order=<?= urlencode($order['order_number']); ?>&format=thermal&print=1" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                                <i class="bi bi-receipt-cutoff me-1"></i>Print Thermal Receipt
                            </a>
                        </div>
                    </div>

                    <!-- Receipt Footer -->
                    <div class="text-center pt-3 mt-3 border-top">
                        <p class="extra-small text-muted mb-0">Thank you for dining with FastBite! Good Food. Fast Delivery. Happy Customers.</p>
                    </div>
                </div>

                <!-- Customer Cancellation Modal -->
                <?php if ($orderStatus === 'pending'): ?>
                    <div class="modal fade d-print-none" id="customerCancelModal" tabindex="-1" aria-labelledby="customerCancelModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <form action="order-details.php?order=<?= urlencode($order['order_number']); ?>" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="cancel_order">
                                    
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold text-danger" id="customerCancelModalLabel">Cancel Order #<?= htmlspecialchars($order['order_number']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body py-3">
                                        <p class="small text-muted mb-0">Are you sure you want to cancel this order? This action will halt kitchen processing and cancel your delivery.</p>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Keep Order</button>
                                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 fw-semibold">Yes, Cancel Order</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </main>

    <div class="d-print-none">
        <?php include_once __DIR__ . '/includes/footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
