<?php
/**
 * FastBite - Order Confirmation & Professional Customer Receipt
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

$currentPage = 'confirmation';
$projectName = 'FastBite';
$userId = (int)$user['id'];
$isAdminUser = isAdmin();

$orderNum = trim($_GET['order'] ?? '');
$rawNum = preg_replace('/^#/', '', $orderNum);

// Fetch order from DB: strictly scoped to current user if not admin
$order = !empty($rawNum) ? dbGetOrderByNumber($rawNum, $isAdminUser ? null : $userId) : null;
$paymentTxn = $order ? dbGetPaymentForOrder($order['id']) : null;

// Fetch authoritative restaurant details
$restName = dbGetSetting('restaurant_name', 'FastBite Fast Food');
$restPhone = dbGetSetting('restaurant_phone', '+252 63 444 5555');
$restEmail = dbGetSetting('restaurant_email', 'support@fastbite.com');
$restAddress = dbGetSetting('restaurant_address', 'Main Hospitality Blvd, City Center, Hargeisa');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation #<?= htmlspecialchars($orderNum); ?> — <?= htmlspecialchars($projectName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @media print {
            body { background: #fff !important; color: #000 !important; font-size: 12pt; }
            .no-print, .main-navbar, .main-footer, .breadcrumb, .alert, .btn, .d-print-none { display: none !important; }
            .receipt-print-container { box-shadow: none !important; border: 1px solid #ddd !important; width: 100% !important; margin: 0 !important; padding: 15px !important; }
            .card { border: none !important; box-shadow: none !important; }
            a { text-decoration: none !important; color: #000 !important; }
            .badge { border: 1px solid #999 !important; color: #000 !important; background: transparent !important; }
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <div class="d-print-none">
        <?php include_once __DIR__ . '/includes/navbar.php'; ?>
    </div>

    <main class="flex-grow-1 py-4 py-lg-5">
        <div class="container">
            <?php if (!$order): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-5 bg-white">
                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-question-diamond-fill fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-2">Order Not Found</h3>
                    <p class="text-muted small mb-4">We could not locate the requested order details in your account.</p>
                    <div>
                        <a href="my-orders.php" class="btn btn-auth-primary rounded-pill px-4">View My Orders</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Stepper (Hidden in Print) -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none bg-white">
                    <div class="card-body p-3 p-md-4">
                        <div class="row text-center position-relative g-0">
                            <div class="col-4 position-relative">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mb-2 shadow-sm" style="width: 44px; height: 44px;">
                                        <i class="bi bi-check-lg fs-5"></i>
                                    </span>
                                    <span class="small fw-semibold text-muted">1. Cart</span>
                                </div>
                            </div>
                            <div class="col-4 position-relative">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mb-2 shadow-sm" style="width: 44px; height: 44px;">
                                        <i class="bi bi-check-lg fs-5"></i>
                                    </span>
                                    <span class="small fw-semibold text-muted">2. Delivery & Payment</span>
                                </div>
                            </div>
                            <div class="col-4 position-relative">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle bg-primary-accent text-white d-flex align-items-center justify-content-center mb-2 shadow-sm" style="width: 44px; height: 44px;">
                                        <i class="bi bi-check-circle-fill fs-5"></i>
                                    </div>
                                    <span class="small fw-bold text-primary-accent">3. Confirmation</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hero Success Banner (Hidden in Print) -->
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 text-center mb-4 bg-white d-print-none">
                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" style="width: 80px; height: 80px;">
                        <i class="bi bi-check2-all fs-1"></i>
                    </div>
                    <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-3 py-1 rounded-pill extra-small fw-bold mb-2">ORDER RECEIVED</span>
                    <h1 class="h3 fw-bold text-dark mb-1">Thank you! Your order has been placed.</h1>
                    <p class="text-muted small mb-3">Order Number: <strong class="text-primary-accent fs-6">#<?= htmlspecialchars($order['order_number']); ?></strong></p>
                    <p class="text-muted extra-small mb-4">Our kitchen has received your order and delivery preparation is underway.</p>

                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="order-details.php?order=<?= urlencode($order['order_number']); ?>" class="btn btn-auth-primary rounded-pill px-4 fw-semibold shadow-sm">
                            <i class="bi bi-truck me-2"></i>Live Track Order
                        </a>
                        <a href="receipt.php?order=<?= urlencode($order['order_number']); ?>" class="btn btn-outline-pink rounded-pill px-4 fw-semibold">
                            <i class="bi bi-receipt me-2"></i>View Official Receipt
                        </a>
                        <a href="receipt.php?order=<?= urlencode($order['order_number']); ?>&print=1" target="_blank" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-printer me-2"></i>Print Receipt (A4)
                        </a>
                        <a href="receipt.php?order=<?= urlencode($order['order_number']); ?>&format=thermal&print=1" target="_blank" class="btn btn-outline-dark rounded-pill px-4">
                            <i class="bi bi-receipt-cutoff me-2"></i>Thermal Receipt (80mm)
                        </a>
                        <a href="my-orders.php" class="btn btn-light border rounded-pill px-4">
                            <i class="bi bi-list-check me-2"></i>My Orders
                        </a>
                    </div>
                </div>

                <!-- Professional Order Receipt Container -->
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white receipt-print-container" id="orderReceiptBlock">
                    <!-- Receipt Header -->
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start pb-4 mb-4 border-bottom gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="brand-icon d-flex align-items-center justify-content-center bg-primary-accent text-white rounded-3 p-2" style="width: 38px; height: 38px;">
                                    <i class="bi bi-fire fs-5"></i>
                                </span>
                                <h2 class="h4 fw-bold mb-0 text-dark">Fast<span class="text-primary-accent">Bite</span></h2>
                            </div>
                            <p class="extra-small text-muted mb-0"><?= htmlspecialchars($restName); ?></p>
                            <p class="extra-small text-muted mb-0"><?= htmlspecialchars($restAddress); ?></p>
                            <p class="extra-small text-muted mb-0">Phone: <?= htmlspecialchars($restPhone); ?> | Email: <?= htmlspecialchars($restEmail); ?></p>
                        </div>
                        <div class="text-sm-end">
                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-3 py-1 rounded-pill small fw-bold mb-1">OFFICIAL RECEIPT</span>
                            <h3 class="h6 fw-bold text-dark mb-1">Order #<?= htmlspecialchars($order['order_number']); ?></h3>
                            <p class="extra-small text-muted mb-0">Date: <?= date('F j, Y  h:i A', strtotime($order['created_at'])); ?></p>
                            <p class="extra-small text-muted mb-0">Est. Delivery: <?= htmlspecialchars($order['estimated_delivery'] ?? '25–35 minutes'); ?></p>
                        </div>
                    </div>

                    <!-- Customer & Order Meta -->
                    <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-12 col-sm-6">
                            <span class="extra-small text-muted text-uppercase fw-bold d-block mb-1">Customer Details</span>
                            <strong class="d-block text-dark small"><?= htmlspecialchars($order['customer_name'] ?? $user['name']); ?></strong>
                            <span class="d-block extra-small text-muted"><?= htmlspecialchars($order['customer_email'] ?? $user['email']); ?></span>
                            <span class="d-block extra-small text-muted"><?= htmlspecialchars($order['customer_phone'] ?? $user['phone'] ?? '+252 63 000 0000'); ?></span>
                        </div>
                        <div class="col-12 col-sm-6 text-sm-end">
                            <span class="extra-small text-muted text-uppercase fw-bold d-block mb-1">Delivery Address &amp; Payment</span>
                            <span class="d-block small text-dark"><?= htmlspecialchars($order['delivery_address']); ?></span>
                            <div class="mt-1 d-flex gap-2 justify-content-sm-end">
                                <span class="badge bg-light text-dark border extra-small"><?= htmlspecialchars(getPaymentMethodLabel($order['payment_method'])); ?></span>
                                <?= getPaymentStatusBadge($order['payment_status']); ?>
                            </div>
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
                                                     style="width: 40px; height: 40px;" 
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Financial Summary & Notes -->
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <?php if (!empty($order['notes'])): ?>
                                <div class="p-3 bg-light rounded-3 border extra-small text-muted">
                                    <strong class="text-dark d-block mb-1"><i class="bi bi-chat-left-text me-1"></i>Special Instructions:</strong>
                                    <?= htmlspecialchars($order['notes']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="mt-3 extra-small text-muted">
                                <p class="mb-1"><strong>FastBite Satisfaction Guarantee:</strong> Prepared with fresh ingredients, packed with care, and delivered fast.</p>
                                <p class="mb-0">Need help with your order? Contact us at <strong><?= htmlspecialchars($restPhone); ?></strong>.</p>
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

                    <!-- Receipt Footer -->
                    <div class="text-center pt-4 mt-4 border-top">
                        <p class="extra-small text-muted mb-0">Thank you for dining with FastBite! Good Food. Fast Delivery. Happy Customers.</p>
                    </div>
                </div>
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
