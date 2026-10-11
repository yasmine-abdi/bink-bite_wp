<?php
/**
 * FastBite - Official Order Receipt & Thermal Printing System
 * STEP 34: Receipt Delivery & Admin Receipt Dispatch
 * 
 * Standalone, customer-isolated, print-optimized official receipt.
 * Supports standard A4 invoice format and POS 80mm thermal roll slip format.
 */

define('FASTBITE_ROOT_DIR', __DIR__);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';
require_once __DIR__ . '/includes/payment.php';

// Access Control: Require Authentication
requireLogin('login.php');

$orderIdent = trim($_GET['order'] ?? $_GET['order_id'] ?? $_GET['id'] ?? '');
$format = trim($_GET['format'] ?? $_GET['type'] ?? 'a4'); // 'a4' or 'thermal'
$autoPrint = !empty($_GET['print']);

if (empty($orderIdent)) {
    header("Location: my-orders.php?error=" . urlencode("No order specified for receipt view."));
    exit;
}

$order = dbGetOrderWithFullDetails($orderIdent);

if (!$order) {
    http_response_code(404);
    $errorTitle = "Receipt Not Found";
    $errorMsg = "We could not find an official receipt for order identifier: " . htmlspecialchars($orderIdent, ENT_QUOTES, 'UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Receipt Not Found - FastBite</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="assets/css/style.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 text-center bg-white" style="max-width: 480px;">
            <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px;">
                <i class="bi bi-file-earmark-x fs-2"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2"><?= $errorTitle; ?></h4>
            <p class="text-muted small mb-4"><?= $errorMsg; ?></p>
            <a href="<?= isAdmin() ? 'admin/orders/index.php' : 'my-orders.php'; ?>" class="btn btn-auth-primary rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i><?= isAdmin() ? 'Back to Orders List' : 'Back to My Orders'; ?>
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Customer Isolation: Verify user has permission to view this receipt
$currentUserId = currentUserId();
$isUserAdmin = isAdmin();
$isOwner = ((int)$order['user_id'] === (int)$currentUserId);

// Drivers can view orders assigned to them
$isAssignedDriver = false;
if (isDriver()) {
    $driverRec = dbGetDriverByUserId($currentUserId);
    if ($driverRec) {
        $delivery = dbGetOrderDelivery((int)$order['id']);
        if ($delivery && (int)($delivery['driver_id'] ?? 0) === (int)$driverRec['id']) {
            $isAssignedDriver = true;
        }
    }
}

if (!$isUserAdmin && !$isOwner && !$isAssignedDriver) {
    // Log unauthorized receipt access attempt
    if (function_exists('dbLogSecurityEvent')) {
        dbLogSecurityEvent('unauthorized_receipt_access_attempt', "User #{$currentUserId} attempted to access receipt for Order #{$order['order_number']}", $currentUserId);
    }
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied - FastBite</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="assets/css/style.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 text-center bg-white" style="max-width: 480px;">
            <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px;">
                <i class="bi bi-shield-lock-fill fs-2"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2">Access Denied</h4>
            <p class="text-muted small mb-4">You do not have permission to view this order receipt. Receipts are strictly confidential to the purchasing customer and authorized staff.</p>
            <a href="my-orders.php" class="btn btn-auth-primary rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i>Back to My Orders
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Log audit event for receipt view / print
if (function_exists('dbLogSecurityEvent')) {
    dbLogSecurityEvent('receipt_viewed', "Official receipt viewed for Order #{$order['order_number']}" . ($autoPrint ? " (print mode)" : ""), $order['user_id'], $isUserAdmin ? $currentUserId : null);
}

// Authoritative Restaurant Settings
$restName = dbGetSetting('restaurant_name', 'FastBite Fast Food');
$restPhone = dbGetSetting('restaurant_phone', '+252 63 444 5555');
$restEmail = dbGetSetting('restaurant_email', 'support@fastbite.com');
$restAddress = dbGetSetting('restaurant_address', 'Main Hospitality Blvd, City Center, Hargeisa');
$restRegNo = dbGetSetting('restaurant_reg_number', 'FB-HAR-2026-998');

$orderNum = htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8');
$orderDateFormatted = date('d M Y', strtotime($order['created_at']));
$orderTimeFormatted = date('h:i A', strtotime($order['created_at']));
$payMethod = getPaymentMethodLabel($order['payment_method'] ?? 'cash_on_delivery');
$payStatus = ucfirst($order['payment_status'] ?? 'pending');
$orderStatus = strtoupper(str_replace('_', ' ', $order['order_status'] ?? 'PENDING'));

$backUrl = 'order-details.php?order=' . urlencode($order['order_number']);
if ($isUserAdmin) {
    $backUrl = 'admin/orders/view.php?order=' . urlencode($order['order_number']);
} elseif ($isAssignedDriver) {
    $backUrl = 'driver/delivery.php?order_id=' . (int)$order['id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Receipt #<?= $orderNum; ?> - <?= htmlspecialchars($restName); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --fb-pink: #ff2b85;
            --fb-pink-light: #fff0f6;
            --fb-dark: #1a1a1a;
        }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f4f6f8;
            color: #212529;
            -webkit-font-smoothing: antialiased;
        }

        /* Screen toolbar */
        .receipt-toolbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }

        /* Standard A4 Container */
        .receipt-a4-container {
            max-width: 800px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border: 1px solid #eaeaea;
            padding: 40px;
        }

        /* 80mm Thermal Receipt Container */
        .receipt-thermal-container {
            width: 330px;
            max-width: 100%;
            margin: 25px auto;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            padding: 24px 18px;
            font-family: 'Space Mono', 'Courier New', Courier, monospace;
            font-size: 11.5px;
            line-height: 1.35;
            color: #000000;
            box-shadow: 0 6px 24px rgba(0,0,0,0.08);
            border-radius: 4px;
        }

        .receipt-thermal-container .thermal-divider {
            border-top: 1px dashed #000000;
            margin: 8px 0;
        }

        .receipt-thermal-container .thermal-double-divider {
            border-top: 2px solid #000000;
            border-bottom: 2px solid #000000;
            padding: 5px 0;
            margin: 8px 0;
        }

        .receipt-thermal-container .thermal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin: 4px 0;
        }

        .receipt-thermal-container .thermal-table th {
            border-bottom: 1px dashed #000000;
            padding: 4px 0;
            font-weight: 700;
            text-transform: uppercase;
        }

        .receipt-thermal-container .thermal-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .d-print-none, .receipt-toolbar {
                display: none !important;
            }
            .receipt-a4-container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 15px !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            .receipt-thermal-container {
                width: 76mm !important;
                max-width: 76mm !important;
                margin: 0 auto !important;
                padding: 2mm 0 !important;
                border: none !important;
                box-shadow: none !important;
                font-size: 10pt !important;
                line-height: 1.3 !important;
            }
            @page {
                margin: 4mm;
                size: auto;
            }
        }
    </style>
</head>
<body <?= $autoPrint ? 'onload="window.print();"' : ''; ?>>

    <!-- Screen Navigation & Action Toolbar -->
    <div class="receipt-toolbar py-2 px-3 d-print-none shadow-sm">
        <div class="container-fluid max-w-1200 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="<?= htmlspecialchars($backUrl); ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
                <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-3 py-1 rounded-pill extra-small fw-bold">
                    OFFICIAL RECEIPT
                </span>
                <span class="text-muted small d-none d-sm-inline">#<?= $orderNum; ?></span>
            </div>

            <!-- Format Toggle & Print Controls -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="btn-group btn-group-sm rounded-pill p-1 bg-light border" role="group">
                    <a href="receipt.php?order=<?= urlencode($order['order_number']); ?>&format=a4" class="btn btn-sm rounded-pill px-3 <?= $format === 'a4' ? 'btn-white shadow-sm fw-bold text-primary-accent' : 'text-muted'; ?>">
                        <i class="bi bi-file-earmark-text me-1"></i>A4 Invoice
                    </a>
                    <a href="receipt.php?order=<?= urlencode($order['order_number']); ?>&format=thermal" class="btn btn-sm rounded-pill px-3 <?= $format === 'thermal' ? 'btn-white shadow-sm fw-bold text-primary-accent' : 'text-muted'; ?>">
                        <i class="bi bi-receipt-cutoff me-1"></i>80mm Thermal Slip
                    </a>
                </div>

                <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-semibold shadow-sm d-inline-flex align-items-center gap-1" onclick="window.print();">
                    <i class="bi bi-file-earmark-pdf"></i>
                    <span>Save as PDF</span>
                </button>

                <button type="button" class="btn btn-auth-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm d-inline-flex align-items-center gap-1" onclick="window.print();">
                    <i class="bi bi-printer-fill"></i>
                    <span>Print Receipt</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN RECEIPT DISPLAY CONTAINER -->
    <main class="py-4">
        <div class="container">

            <?php if ($format === 'thermal'): ?>
                <!-- ========================================================= -->
                <!-- 80MM POS THERMAL RECEIPT SLIP LAYOUT (EXACT REFERENCE)   -->
                <!-- ========================================================= -->
                <div class="receipt-thermal-container" id="thermalReceipt">
                    <!-- 1. Restaurant Brand Header -->
                    <div class="text-center mb-2">
                        <div class="fw-bold fs-5" style="letter-spacing: 2px;">FASTBITE</div>
                        <div style="font-size: 11px; font-weight: bold;"><?= htmlspecialchars($restName); ?></div>
                        <div style="font-size: 10px;"><?= htmlspecialchars($restAddress); ?></div>
                        <div style="font-size: 10px;">Tel: <?= htmlspecialchars($restPhone); ?></div>
                        <div style="font-size: 10px;"><?= htmlspecialchars($restEmail); ?></div>
                        <div class="mt-2 fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                            THANK YOU FOR<br>YOUR ORDER!
                        </div>
                    </div>

                    <!-- 2. Order Metadata Header -->
                    <div class="thermal-divider"></div>
                    <div>
                        <div class="d-flex justify-content-between">
                            <span>ORDER #:</span>
                            <strong>#<?= $orderNum; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>DATE:</span>
                            <span><?= $orderDateFormatted; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>TIME:</span>
                            <span><?= $orderTimeFormatted; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>CUSTOMER:</span>
                            <strong class="text-truncate" style="max-width: 180px;"><?= htmlspecialchars($order['customer_name'] ?? 'Customer'); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>PHONE:</span>
                            <span><?= htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                    <div class="thermal-divider"></div>

                    <!-- 3. Itemized Items Table -->
                    <table class="thermal-table">
                        <thead>
                            <tr>
                                <th style="width: 12%; text-align: left;">QTY</th>
                                <th style="width: 58%; text-align: left;">ITEM</th>
                                <th style="width: 30%; text-align: right;">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                                <tr>
                                    <td style="text-align: left; font-weight: bold;">
                                        <?= str_pad((string)(int)$item['quantity'], 2, '0', STR_PAD_LEFT); ?>
                                    </td>
                                    <td style="text-align: left; word-break: break-word; padding-right: 4px;">
                                        <?= htmlspecialchars($item['food_name']); ?>
                                        <?php if ((float)$item['discount'] > 0): ?>
                                            <div style="font-size: 9.5px; opacity: 0.85;">(Disc: -$<?= number_format((float)$item['discount'], 2); ?>)</div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; font-weight: bold; white-space: nowrap;">
                                        $<?= number_format((float)$item['subtotal'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- 4. Financial Summary Breakdown -->
                    <div class="thermal-divider"></div>
                    <div>
                        <div class="d-flex justify-content-between">
                            <span>SUBTOTAL</span>
                            <span>$<?= number_format((float)$order['subtotal'], 2); ?></span>
                        </div>
                        <?php if ((float)$order['discount'] > 0): ?>
                            <div class="d-flex justify-content-between">
                                <span>DISCOUNT</span>
                                <span>-$<?= number_format((float)$order['discount'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($order['coupon_discount']) && (float)$order['coupon_discount'] > 0): ?>
                            <div class="d-flex justify-content-between">
                                <span>COUPON (<?= htmlspecialchars($order['coupon_code'] ?? 'PROMO'); ?>)</span>
                                <span>-$<?= number_format((float)$order['coupon_discount'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between">
                            <span>DELIVERY</span>
                            <span><?= (float)$order['delivery_fee'] > 0 ? '$' . number_format((float)$order['delivery_fee'], 2) : '$0.00'; ?></span>
                        </div>
                        <?php if (isset($order['tax']) && (float)$order['tax'] > 0): ?>
                            <div class="d-flex justify-content-between">
                                <span>TAX</span>
                                <span>$<?= number_format((float)$order['tax'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Prominent Total -->
                    <div class="thermal-double-divider">
                        <div class="d-flex justify-content-between align-items-center" style="font-size: 13.5px; font-weight: 800;">
                            <span>TOTAL</span>
                            <span>$<?= number_format((float)$order['total'], 2); ?></span>
                        </div>
                    </div>

                    <!-- 5. Payment & Fulfillment Details -->
                    <div style="margin-top: 6px;">
                        <div class="d-flex justify-content-between">
                            <span>PAYMENT:</span>
                            <strong><?= strtoupper($payMethod); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>STATUS:</span>
                            <strong><?= strtoupper($payStatus); ?></strong>
                        </div>
                        <div class="mt-2" style="font-size: 10px;">
                            <span>DELIVERY:</span><br>
                            <strong style="word-break: break-word;"><?= htmlspecialchars($order['delivery_address']); ?></strong>
                        </div>
                    </div>

                    <?php if (!empty($order['notes'])): ?>
                        <div class="thermal-divider"></div>
                        <div style="font-size: 10px;">
                            <span>NOTES:</span><br>
                            <?= htmlspecialchars($order['notes']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- 6. FastBite Thank You Footer -->
                    <div class="thermal-divider"></div>
                    <div class="text-center mt-3" style="font-size: 10.5px;">
                        <div class="fw-bold mb-1">
                            THANK YOU FOR CHOOSING<br>FASTBITE
                        </div>
                        <div class="mb-1">We appreciate your order!</div>
                        <div class="mt-2" style="font-size: 9px; opacity: 0.8;">
                            Please visit us again ❤️
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- ========================================================= -->
                <!-- STANDARD A4 INVOICE / OFFICIAL CUSTOMER RECEIPT LAYOUT -->
                <!-- ========================================================= -->
                <div class="receipt-a4-container" id="a4Receipt">
                    <!-- Brand Header -->
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start pb-4 mb-4 border-bottom gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="brand-icon d-flex align-items-center justify-content-center bg-primary-accent text-white rounded-4 p-3" style="width: 52px; height: 52px;">
                                <i class="bi bi-fire fs-3"></i>
                            </span>
                            <div>
                                <h2 class="h4 fw-bold text-dark mb-0">Fast<span class="text-primary-accent">Bite</span></h2>
                                <p class="extra-small text-muted mb-0">Good Food. Fast Delivery. Happy Customers.</p>
                                <span class="extra-small text-muted d-block"><?= htmlspecialchars($restAddress); ?></span>
                                <span class="extra-small text-muted">Tel: <?= htmlspecialchars($restPhone); ?> &bull; Email: <?= htmlspecialchars($restEmail); ?></span>
                            </div>
                        </div>
                        <div class="text-sm-end">
                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-3 py-1 rounded-pill small fw-bold mb-1">OFFICIAL RECEIPT</span>
                            <h3 class="h5 fw-bold text-dark mb-0">#<?= $orderNum; ?></h3>
                            <span class="extra-small text-muted d-block">Date: <?= date('F j, Y  h:i A', strtotime($order['created_at'])); ?></span>
                            <span class="extra-small text-muted">Tax/Reg: <?= htmlspecialchars($restRegNo); ?></span>
                        </div>
                    </div>

                    <!-- Customer & Transaction Information -->
                    <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-12 col-sm-6">
                            <h4 class="extra-small text-uppercase text-muted fw-bold mb-2">Billed &amp; Delivered To</h4>
                            <strong class="text-dark d-block small mb-1"><?= htmlspecialchars($order['customer_name'] ?? 'Customer'); ?></strong>
                            <p class="extra-small text-muted mb-1"><?= htmlspecialchars($order['delivery_address']); ?></p>
                            <span class="extra-small text-muted d-block"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></span>
                            <span class="extra-small text-muted d-block"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($order['customer_email'] ?? ''); ?></span>
                        </div>
                        <div class="col-12 col-sm-6 text-sm-end">
                            <h4 class="extra-small text-uppercase text-muted fw-bold mb-2">Order &amp; Payment Status</h4>
                            <div class="mb-1">
                                <span class="extra-small text-muted">Order Status:</span>
                                <strong class="small text-dark ms-1"><?= $orderStatus; ?></strong>
                            </div>
                            <div class="mb-1">
                                <span class="extra-small text-muted">Payment Method:</span>
                                <strong class="small text-dark ms-1"><?= htmlspecialchars($payMethod); ?></strong>
                            </div>
                            <div class="mb-1">
                                <span class="extra-small text-muted">Payment Status:</span>
                                <?= getPaymentStatusBadge($order['payment_status'] ?? 'pending'); ?>
                            </div>
                            <?php if (!empty($order['estimated_delivery'])): ?>
                                <div>
                                    <span class="extra-small text-muted">Estimated Delivery:</span>
                                    <strong class="small text-dark ms-1"><?= htmlspecialchars($order['estimated_delivery']); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Itemized Items Table -->
                    <div class="table-responsive mb-4">
                        <table class="table align-middle receipt-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="extra-small text-uppercase text-muted">Food Item</th>
                                    <th class="extra-small text-uppercase text-muted text-center">Unit Price</th>
                                    <th class="extra-small text-uppercase text-muted text-center">Qty</th>
                                    <th class="extra-small text-uppercase text-muted text-center">Discount</th>
                                    <th class="extra-small text-uppercase text-muted text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div>
                                                    <strong class="text-dark small d-block"><?= htmlspecialchars($item['food_name']); ?></strong>
                                                    <span class="badge bg-light text-muted border extra-small"><?= htmlspecialchars($item['food_category'] ?? 'Fast Food'); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center small text-muted">$<?= number_format((float)$item['unit_price'], 2); ?></td>
                                        <td class="text-center fw-bold small">&times; <?= (int)$item['quantity']; ?></td>
                                        <td class="text-center small text-success">
                                            <?= (float)$item['discount'] > 0 ? '-$' . number_format((float)$item['discount'], 2) : '$0.00'; ?>
                                        </td>
                                        <td class="text-end fw-bold text-dark small">$<?= number_format((float)$item['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Financial Summary -->
                    <div class="row justify-content-end mb-4">
                        <div class="col-12 col-md-6 col-lg-5">
                            <div class="vstack gap-2">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>Subtotal</span>
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
                                    <span class="fs-5 fw-bold text-primary-accent">$<?= number_format((float)$order['total'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($order['notes'])): ?>
                        <div class="p-3 bg-light rounded-3 border extra-small text-muted mb-4">
                            <strong class="text-dark d-block mb-1"><i class="bi bi-chat-left-text me-1 text-primary-accent"></i>Customer Special Instructions:</strong>
                            <?= nl2br(htmlspecialchars($order['notes'], ENT_QUOTES, 'UTF-8')); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Receipt Footer -->
                    <div class="border-top pt-4 text-center">
                        <p class="small fw-semibold text-dark mb-1">Thank you for ordering with FastBite!</p>
                        <p class="extra-small text-muted mb-0">For questions regarding this receipt, please contact support at <strong class="text-dark"><?= htmlspecialchars($restPhone); ?></strong> or <strong class="text-dark"><?= htmlspecialchars($restEmail); ?></strong>.</p>
                        <p class="extra-small text-muted mt-2 mb-0">&copy; 2026 FastBite Fast Food. All rights reserved.</p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
