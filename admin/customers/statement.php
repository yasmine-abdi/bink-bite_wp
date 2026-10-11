<?php
/**
 * FastBite - Customer Account Statement & Financial Spending Receipt
 * Admin Operations & Customer Account Management
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(dirname(__DIR__)));
}

require_once FASTBITE_ROOT_DIR . '/config/database.php';
require_once FASTBITE_ROOT_DIR . '/includes/auth.php';
require_once FASTBITE_ROOT_DIR . '/includes/image-helper.php';
require_once FASTBITE_ROOT_DIR . '/includes/payment.php';

// Require Admin Access
requireAdmin('../../login.php');

$customerId = (int)($_GET['id'] ?? 0);
$format = trim($_GET['format'] ?? 'a4'); // 'a4' or 'thermal'
$autoPrint = !empty($_GET['print']);

if ($customerId <= 0) {
    header("Location: index.php?error=" . urlencode("Invalid customer ID specified."));
    exit;
}

$data = dbGetCustomerDetails($customerId);
if (!$data || empty($data['profile'])) {
    http_response_code(404);
    die("Customer record not found.");
}

$profile = $data['profile'];
$kpi = $data['kpi'] ?? [];
$orders = $data['orders'] ?? [];
$addresses = $data['addresses'] ?? [];

// Authoritative Restaurant Settings
$restName = dbGetSetting('restaurant_name', 'FastBite Fast Food');
$restPhone = dbGetSetting('restaurant_phone', '+252 63 444 5555');
$restEmail = dbGetSetting('restaurant_email', 'support@fastbite.com');
$restAddress = dbGetSetting('restaurant_address', 'Main Hospitality Blvd, City Center, Hargeisa');
$restRegNo = dbGetSetting('restaurant_reg_number', 'FB-HAR-2026-998');

$totalSpent = (float)($kpi['lifetime_spent'] ?? 0);
$totalOrdersCount = (int)($kpi['total_orders'] ?? 0);
$completedOrdersCount = (int)($kpi['completed_orders'] ?? 0);
$avgOrderValue = (float)($kpi['avg_order_value'] ?? 0);
$statementDate = date('F j, Y  h:i A');

// Calculate total paid vs pending
$totalPaid = 0.0;
$totalPending = 0.0;
foreach ($orders as $ord) {
    if (($ord['payment_status'] ?? '') === 'paid') {
        $totalPaid += (float)$ord['total'];
    } else {
        $totalPending += (float)$ord['total'];
    }
}

// Log audit event
if (function_exists('dbLogSecurityEvent')) {
    dbLogSecurityEvent('customer_statement_viewed', "Customer spending statement viewed for Customer #{$customerId} ({$profile['email']})", $customerId, currentUserId());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Spending Statement - <?= htmlspecialchars($profile['name']); ?> - FastBite</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
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

        .statement-toolbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }

        .statement-a4-container {
            max-width: 900px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border: 1px solid #eaeaea;
            padding: 40px;
        }

        .statement-thermal-container {
            width: 320px;
            max-width: 100%;
            margin: 30px auto;
            background: #ffffff;
            border: 1px dashed #bbb;
            padding: 20px 16px;
            font-family: 'Space Mono', 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .statement-thermal-container .thermal-divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .statement-thermal-container .thermal-double-divider {
            border-top: 2px solid #000;
            margin: 10px 0;
        }

        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .d-print-none, .statement-toolbar {
                display: none !important;
            }
            .statement-a4-container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 15px !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            .statement-thermal-container {
                width: 78mm !important;
                max-width: 78mm !important;
                margin: 0 auto !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                font-size: 10pt !important;
            }
            @page {
                margin: 10mm;
                size: auto;
            }
        }
    </style>
</head>
<body <?= $autoPrint ? 'onload="window.print();"' : ''; ?>>

    <!-- Screen Navigation & Action Toolbar -->
    <div class="statement-toolbar py-2 px-3 d-print-none shadow-sm">
        <div class="container-fluid max-w-1200 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="view.php?id=<?= $customerId; ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i>Back to Customer Profile
                </a>
                <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-3 py-1 rounded-pill extra-small fw-bold">
                    CUSTOMER FINANCIAL STATEMENT
                </span>
                <span class="text-muted small d-none d-sm-inline"><?= htmlspecialchars($profile['name']); ?> (#<?= $customerId; ?>)</span>
            </div>

            <!-- Format Toggle & Print Controls -->
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group btn-group-sm rounded-pill p-1 bg-light border" role="group">
                    <a href="statement.php?id=<?= $customerId; ?>&format=a4" class="btn btn-sm rounded-pill px-3 <?= $format === 'a4' ? 'btn-white shadow-sm fw-bold text-primary-accent' : 'text-muted'; ?>">
                        <i class="bi bi-file-earmark-text me-1"></i>A4 Statement
                    </a>
                    <a href="statement.php?id=<?= $customerId; ?>&format=thermal" class="btn btn-sm rounded-pill px-3 <?= $format === 'thermal' ? 'btn-white shadow-sm fw-bold text-primary-accent' : 'text-muted'; ?>">
                        <i class="bi bi-receipt-cutoff me-1"></i>80mm Thermal Slip
                    </a>
                </div>

                <button type="button" class="btn btn-auth-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm d-inline-flex align-items-center gap-1" onclick="window.print();">
                    <i class="bi bi-printer-fill"></i>
                    <span>Print Statement</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN STATEMENT CONTAINER -->
    <main class="py-4">
        <div class="container">

            <?php if ($format === 'thermal'): ?>
                <!-- ========================================================= -->
                <!-- 80MM THERMAL SLIP CUSTOMER SPENDING SUMMARY -->
                <!-- ========================================================= -->
                <div class="statement-thermal-container" id="thermalStatement">
                    <div class="text-center mb-2">
                        <h4 class="fw-bold mb-0" style="letter-spacing: 1px;"><?= strtoupper(htmlspecialchars($restName)); ?></h4>
                        <div style="font-size: 10px;"><?= htmlspecialchars($restAddress); ?></div>
                        <div style="font-size: 10px;">TEL: <?= htmlspecialchars($restPhone); ?></div>
                        <div style="font-size: 9px; opacity: 0.8;">REG: <?= htmlspecialchars($restRegNo); ?></div>
                    </div>

                    <div class="thermal-double-divider"></div>

                    <div class="text-center fw-bold" style="font-size: 12px; margin-bottom: 6px;">
                        CUSTOMER ACCOUNT STATEMENT
                    </div>

                    <div class="d-flex justify-content-between">
                        <span>CUSTOMER:</span>
                        <strong><?= htmlspecialchars($profile['name']); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>ACCOUNT ID:</span>
                        <span>#<?= (int)$profile['id']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>PHONE:</span>
                        <span><?= htmlspecialchars($profile['phone'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>EMAIL:</span>
                        <span class="text-truncate" style="max-width: 180px;"><?= htmlspecialchars($profile['email']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>PRINT DATE:</span>
                        <span><?= date('d/m/Y H:i'); ?></span>
                    </div>

                    <div class="thermal-divider"></div>

                    <div class="d-flex justify-content-between">
                        <span>TOTAL ORDERS:</span>
                        <strong><?= $totalOrdersCount; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>COMPLETED:</span>
                        <strong><?= $completedOrdersCount; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>TOTAL SPENT:</span>
                        <strong>$<?= number_format($totalSpent, 2); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>TOTAL PAID:</span>
                        <span>$<?= number_format($totalPaid, 2); ?></span>
                    </div>
                    <?php if ($totalPending > 0): ?>
                        <div class="d-flex justify-content-between">
                            <span>UNPAID/PENDING:</span>
                            <span style="color: #000; font-weight: bold;">$<?= number_format($totalPending, 2); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="thermal-divider"></div>
                    <div class="fw-bold mb-1" style="font-size: 10px;">ORDER HISTORY (<?= count($orders); ?>):</div>

                    <table style="width: 100%; font-size: 10px;">
                        <thead>
                            <tr style="border-bottom: 1px solid #000;">
                                <th style="text-align: left; padding: 2px 0;">ORDER #</th>
                                <th style="text-align: left; padding: 2px 0;">DATE</th>
                                <th style="text-align: right; padding: 2px 0;">STATUS</th>
                                <th style="text-align: right; padding: 2px 0;">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $ord): ?>
                                <tr>
                                    <td style="padding: 2px 0; font-weight: bold;">#<?= htmlspecialchars($ord['order_number']); ?></td>
                                    <td style="padding: 2px 0; font-size: 9px;"><?= date('d/m/y', strtotime($ord['created_at'])); ?></td>
                                    <td style="padding: 2px 0; text-align: right; font-size: 9px;"><?= strtoupper(substr($ord['order_status'], 0, 4)); ?></td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: bold;">$<?= number_format((float)$ord['total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="thermal-double-divider"></div>

                    <div class="d-flex justify-content-between" style="font-size: 12px; font-weight: bold;">
                        <span>LIFETIME SPENDING:</span>
                        <span>$<?= number_format($totalSpent, 2); ?></span>
                    </div>

                    <div class="thermal-divider"></div>

                    <div class="text-center mt-3" style="font-size: 10px;">
                        <p class="mb-1 fw-bold">FASTBITE OFFICIAL ACCOUNT STATEMENT</p>
                        <p class="mb-0" style="font-size: 8px; opacity: 0.7;">Printed by Admin &bull; <?= htmlspecialchars($restEmail); ?></p>
                    </div>
                </div>

            <?php else: ?>
                <!-- ========================================================= -->
                <!-- STANDARD A4 CUSTOMER FINANCIAL STATEMENT & ORDER SUMMARY -->
                <!-- ========================================================= -->
                <div class="statement-a4-container" id="a4Statement">
                    <!-- Brand & Statement Header -->
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
                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-3 py-1 rounded-pill small fw-bold mb-1">CUSTOMER ACCOUNT STATEMENT</span>
                            <h3 class="h5 fw-bold text-dark mb-0">Customer ID: #<?= (int)$profile['id']; ?></h3>
                            <span class="extra-small text-muted d-block">Generated: <?= $statementDate; ?></span>
                            <span class="extra-small text-muted">Tax/Reg: <?= htmlspecialchars($restRegNo); ?></span>
                        </div>
                    </div>

                    <!-- Customer Account Information Snapshot -->
                    <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-12 col-sm-6">
                            <h4 class="extra-small text-uppercase text-muted fw-bold mb-2">Customer Details</h4>
                            <strong class="text-dark d-block fs-6 mb-1"><?= htmlspecialchars($profile['name']); ?></strong>
                            <span class="extra-small text-muted d-block"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($profile['email']); ?></span>
                            <span class="extra-small text-muted d-block"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($profile['phone'] ?? 'No phone'); ?></span>
                            <span class="extra-small text-muted d-block"><i class="bi bi-calendar-check me-1"></i>Member Since: <?= date('F j, Y', strtotime($profile['created_at'])); ?></span>
                            <div class="mt-1">
                                <span class="badge <?= ($profile['status'] === 'active') ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'; ?> extra-small fw-bold">
                                    Account Status: <?= ucfirst($profile['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <h4 class="extra-small text-uppercase text-muted fw-bold mb-2">Financial Account Summary</h4>
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Total Orders:</span>
                                    <strong class="text-dark"><?= $totalOrdersCount; ?> orders</strong>
                                </div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Completed Orders:</span>
                                    <strong class="text-success"><?= $completedOrdersCount; ?> orders</strong>
                                </div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Total Paid:</span>
                                    <strong class="text-dark">$<?= number_format($totalPaid, 2); ?></strong>
                                </div>
                                <?php if ($totalPending > 0): ?>
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-muted">Unpaid/Pending:</span>
                                        <strong class="text-danger">$<?= number_format($totalPending, 2); ?></strong>
                                    </div>
                                <?php endif; ?>
                                <hr class="my-2 opacity-25">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark small">Lifetime Spending:</span>
                                    <span class="fs-5 fw-bold text-primary-accent">$<?= number_format($totalSpent, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Order History & Invoices Table -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h4 class="h6 fw-bold text-dark mb-0">Order Receipts &amp; Spending History (<?= count($orders); ?>)</h4>
                            <span class="extra-small text-muted">All historical transactions</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" style="font-size: 13px;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 extra-small text-uppercase text-muted">Order #</th>
                                        <th class="extra-small text-uppercase text-muted">Date &amp; Time</th>
                                        <th class="extra-small text-uppercase text-muted text-center">Items</th>
                                        <th class="extra-small text-uppercase text-muted">Payment Method</th>
                                        <th class="extra-small text-uppercase text-muted text-center">Payment</th>
                                        <th class="extra-small text-uppercase text-muted text-center">Fulfillment</th>
                                        <th class="extra-small text-uppercase text-muted text-end">Amount</th>
                                        <th class="extra-small text-uppercase text-muted text-end d-print-none pe-3" style="width: 140px;">Print Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">No orders recorded for this customer account.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $ord): 
                                            $stBadge = match($ord['order_status']) {
                                                'confirmed' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                                'preparing' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                                'out_for_delivery' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                                'delivered' => 'bg-success-subtle text-success border border-success-subtle',
                                                'cancelled' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                                default => 'bg-secondary-subtle text-secondary border'
                                            };
                                        ?>
                                            <tr>
                                                <td class="ps-3 fw-bold font-monospace text-dark">
                                                    #<?= htmlspecialchars($ord['order_number']); ?>
                                                </td>
                                                <td class="extra-small text-muted">
                                                    <?= date('M d, Y h:i A', strtotime($ord['created_at'])); ?>
                                                </td>
                                                <td class="text-center extra-small">
                                                    <span class="badge bg-light text-dark border"><?= count($ord['items'] ?? []); ?> item(s)</span>
                                                </td>
                                                <td class="extra-small text-muted">
                                                    <?= htmlspecialchars(getPaymentMethodLabel($ord['payment_method'] ?? 'cash_on_delivery')); ?>
                                                </td>
                                                <td class="text-center extra-small">
                                                    <?= getPaymentStatusBadge($ord['payment_status'] ?? 'pending'); ?>
                                                </td>
                                                <td class="text-center extra-small">
                                                    <span class="badge <?= $stBadge; ?> extra-small fw-bold">
                                                        <?= ucfirst(str_replace('_', ' ', $ord['order_status'])); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end fw-bold text-dark font-monospace">
                                                    $<?= number_format((float)$ord['total'], 2); ?>
                                                </td>
                                                <td class="text-end d-print-none pe-3">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="../../receipt.php?order=<?= urlencode($ord['order_number']); ?>&print=1" target="_blank" class="btn btn-outline-dark btn-sm py-0 px-2 extra-small" title="Print Official Receipt A4">
                                                            <i class="bi bi-printer"></i> A4
                                                        </a>
                                                        <a href="../../receipt.php?order=<?= urlencode($ord['order_number']); ?>&format=thermal&print=1" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2 extra-small" title="Print Thermal Slip">
                                                            <i class="bi bi-receipt-cutoff"></i> Slip
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td colspan="6" class="ps-3 text-end text-dark">TOTAL LIFETIME CUSTOMER SPENDING:</td>
                                        <td class="text-end text-primary-accent fs-6 font-monospace">$<?= number_format($totalSpent, 2); ?></td>
                                        <td class="d-print-none"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Statement Sign-off Footer -->
                    <div class="row pt-4 mt-4 border-top">
                        <div class="col-8">
                            <p class="extra-small text-muted mb-1">This official document reflects all purchases, payments, and account activity recorded for this customer in the FastBite database system.</p>
                            <p class="extra-small text-muted mb-0">For accounting and billing inquiries, contact: <strong class="text-dark"><?= htmlspecialchars($restEmail); ?></strong> &bull; Tel: <strong class="text-dark"><?= htmlspecialchars($restPhone); ?></strong></p>
                        </div>
                        <div class="col-4 text-end">
                            <div class="border-top pt-2 mt-4 text-center d-inline-block" style="width: 180px;">
                                <span class="extra-small text-muted d-block">Authorized Restaurant Sign / Stamp</span>
                                <strong class="small text-dark"><?= htmlspecialchars($restName); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
