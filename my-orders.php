<?php
/**
 * FastBite - Customer Orders History Page
 * STEP 29: FastBite Final Order, Delivery & Fulfillment System
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
$successMsg = $_GET['msg'] ?? '';
$errorMsg = $_GET['error'] ?? '';

// Handle Cancellation POST from my-orders page
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'cancel_order') {
    $csrf = $_POST['csrf_token'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = "Security validation failed. Please refresh and try again.";
    } else {
        $res = dbCustomerCancelOrder($orderId, $userId);
        if ($res['success']) {
            header("Location: my-orders.php?msg=" . urlencode("Order #{$res['order_number']} has been cancelled."));
            exit;
        } else {
            $errorMsg = $res['error'] ?? "Failed to cancel order.";
        }
    }
}

$orders = dbGetUserOrders($userId);

$statusConfigs = [
    'pending' => [
        'label' => 'Order Received',
        'badge' => 'bg-warning text-dark border border-warning',
        'icon' => 'bi-clock'
    ],
    'confirmed' => [
        'label' => 'Confirmed',
        'badge' => 'bg-primary-subtle text-primary border border-primary-subtle',
        'icon' => 'bi-check2-circle'
    ],
    'preparing' => [
        'label' => 'Preparing',
        'badge' => 'bg-danger-subtle text-danger border border-danger-subtle',
        'icon' => 'bi-fire'
    ],
    'out_for_delivery' => [
        'label' => 'Out for Delivery',
        'badge' => 'bg-info-subtle text-info border border-info-subtle',
        'icon' => 'bi-truck'
    ],
    'delivered' => [
        'label' => 'Delivered',
        'badge' => 'bg-success-subtle text-success border border-success-subtle',
        'icon' => 'bi-check-circle-fill'
    ],
    'cancelled' => [
        'label' => 'Cancelled',
        'badge' => 'bg-secondary-subtle text-muted border',
        'icon' => 'bi-x-circle-fill'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders — <?= htmlspecialchars($projectName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body d-flex flex-column min-vh-100">

    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-grow-1 py-4 py-lg-5">
        <div class="container">
            
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb extra-small mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted"><i class="bi bi-house me-1"></i>Home</a></li>
                    <li class="breadcrumb-item"><a href="customer-dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-primary-accent fw-bold" aria-current="page">My Orders</li>
                </ol>
            </nav>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <span class="dashboard-eyebrow d-inline-block">MY ORDERS</span>
                    <h1 class="h3 fw-bold text-dark mb-1">Order History &amp; Tracking</h1>
                    <p class="text-muted small mb-0">View past meals, track active kitchen fulfillment, and reorder favorites.</p>
                </div>
                <div>
                    <a href="menu.php" class="btn btn-auth-primary rounded-pill px-4 fw-semibold shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-plus-lg"></i>
                        <span>Order New Meal</span>
                    </a>
                </div>
            </div>

            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <div class="small fw-semibold"><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                    <div class="small fw-semibold"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4 bg-white">
                    <div class="rounded-circle bg-pink-soft text-primary-accent d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-bag-x fs-2"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">No orders placed yet</h4>
                    <p class="text-muted small mb-4">You haven't ordered any delicious meals from FastBite yet. Start exploring our menu!</p>
                    <div>
                        <a href="menu.php" class="btn btn-auth-primary rounded-pill px-4">Browse Menu</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="vstack gap-4">
                    <?php foreach ($orders as $ord): 
                        $st = $ord['order_status'];
                        $statusInfo = $statusConfigs[$st] ?? $statusConfigs['pending'];
                    ?>
                        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                            <div class="card-header bg-light border-0 p-3 px-md-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-white p-2 text-primary-accent shadow-sm border">
                                        <i class="bi bi-receipt fs-5"></i>
                                    </div>
                                    <div>
                                        <h2 class="h6 fw-bold text-dark mb-0">Order #<?= htmlspecialchars($ord['order_number']); ?></h2>
                                        <span class="text-muted extra-small"><?= date('M d, Y 	 h:i A', strtotime($ord['created_at'])); ?></span>
                                    </div>
                                </div>

                                <?php 
                                $ordDeliv = function_exists('dbGetOrderDelivery') ? dbGetOrderDelivery((int)$ord['id']) : null;
                                ?>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <?php if ($ordDeliv && !empty($ordDeliv['driver_name'])): ?>
                                        <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small fw-semibold d-none d-sm-inline-flex align-items-center gap-1">
                                            <i class="bi bi-bicycle"></i>
                                            <span><?= htmlspecialchars($ordDeliv['driver_name']); ?></span>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($ordDeliv && $ordDeliv['delivery_status'] === 'failed'): ?>
                                        <span class="badge bg-danger text-white extra-small fw-bold">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Delivery Issue
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge <?= $statusInfo['badge']; ?> extra-small fw-bold">
                                        <i class="bi <?= $statusInfo['icon']; ?> me-1"></i><?= $statusInfo['label']; ?>
                                    </span>
                                    <?= getPaymentStatusBadge($ord['payment_status']); ?>
                                </div>
                            </div>

                            <div class="card-body p-3 p-md-4">
                                <div class="row align-items-center g-3">
                                    <div class="col-12 col-md-7">
                                        <div class="d-flex flex-column gap-2">
                                            <?php foreach ($ord['items'] as $idx => $it): 
                                                if ($idx > 2) {
                                                    echo '<span class="text-muted extra-small">+' . (count($ord['items']) - 3) . ' more items...</span>';
                                                    break;
                                                }
                                            ?>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?= htmlspecialchars(getFoodImage($it['image'] ?? '')); ?>" alt="<?= htmlspecialchars($it['food_name']); ?>" class="rounded-2 object-fit-cover flex-shrink-0" style="width: 36px; height: 36px;" onerror="handleImageError(this, 'food')">
                                                    <span class="small fw-semibold text-dark"><?= htmlspecialchars($it['food_name']); ?></span>
                                                    <span class="text-muted extra-small">&times; <?= (int)$it['quantity']; ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-5 d-flex flex-column flex-sm-row align-items-sm-center justify-content-md-end gap-3 pt-3 pt-md-0 border-top border-md-top-0">
                                        <div class="text-md-end">
                                            <span class="d-block extra-small text-muted">Total Amount</span>
                                            <span class="fs-5 fw-bold text-primary-accent">$<?= number_format((float)$ord['total'], 2); ?></span>
                                        </div>

                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <a href="order-details.php?order=<?= urlencode($ord['order_number']); ?>" class="btn btn-outline-pink btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-geo-alt"></i>
                                                <span>Track Order</span>
                                            </a>
                                            <a href="receipt.php?order=<?= urlencode($ord['order_number']); ?>" class="btn btn-light border btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-1 text-dark shadow-sm">
                                                <i class="bi bi-receipt text-primary-accent"></i>
                                                <span>View Receipt</span>
                                            </a>
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-light btn-sm border rounded-pill px-2 dropdown-toggle d-inline-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Print options">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                                                    <li>
                                                        <a class="dropdown-item py-2 px-3 small" href="receipt.php?order=<?= urlencode($ord['order_number']); ?>">
                                                            <i class="bi bi-receipt me-2 text-primary-accent"></i>View Official Receipt (A4)
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2 px-3 small" href="receipt.php?order=<?= urlencode($ord['order_number']); ?>&print=1" target="_blank">
                                                            <i class="bi bi-printer me-2 text-dark"></i>Print Official Receipt
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2 px-3 small" href="receipt.php?order=<?= urlencode($ord['order_number']); ?>&format=thermal&print=1" target="_blank">
                                                            <i class="bi bi-receipt-cutoff me-2 text-secondary"></i>Print Thermal Slip (80mm)
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <?php if ($st === 'pending'): ?>
                                                <form method="POST" action="my-orders.php" onsubmit="return confirm('Cancel Order #<?= htmlspecialchars($ord['order_number']); ?>?');">
                                                    <?= csrfField(); ?>
                                                    <input type="hidden" name="action" value="cancel_order">
                                                    <input type="hidden" name="order_id" value="<?= (int)$ord['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold">
                                                        Cancel
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
