<?php
/**
 * FastBite - Advanced Admin Reports & Business Analytics
 * STEP 33: Complete Business Platform, Payments, Analytics, Support, Courier Portal & Production Security
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(dirname(__DIR__)));
}

require_once FASTBITE_ROOT_DIR . '/config/database.php';
require_once FASTBITE_ROOT_DIR . '/includes/auth.php';

// Enforce Admin Access
requireAdmin('../../login.php');

$period = $_GET['period'] ?? '7_days';
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$pdo = getDbConnection();

// Build Date Constraints
$whereClause = "WHERE 1=1";
$params = [];
$today = date('Y-m-d');

if ($period === 'today') {
    $whereClause .= " AND DATE(o.created_at) = ?";
    $params[] = $today;
} elseif ($period === 'yesterday') {
    $whereClause .= " AND DATE(o.created_at) = ?";
    $params[] = date('Y-m-d', strtotime('-1 day'));
} elseif ($period === '7_days') {
    $whereClause .= " AND o.created_at >= ?";
    $params[] = date('Y-m-d 00:00:00', strtotime('-6 days'));
} elseif ($period === '30_days') {
    $whereClause .= " AND o.created_at >= ?";
    $params[] = date('Y-m-d 00:00:00', strtotime('-29 days'));
} elseif ($period === 'this_month') {
    $whereClause .= " AND o.created_at >= ?";
    $params[] = date('Y-m-01 00:00:00');
} elseif ($period === 'all_time') {
    // No additional date constraint
} elseif ($period === 'custom' && !empty($dateFrom) && !empty($dateTo)) {
    $whereClause .= " AND o.created_at BETWEEN ? AND ?";
    $params[] = $dateFrom . ' 00:00:00';
    $params[] = $dateTo . ' 23:59:59';
}

// Fetch Filtered Orders
$sqlOrders = "SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone 
              FROM `orders` o 
              JOIN `users` u ON o.user_id = u.id 
              $whereClause 
              ORDER BY o.id DESC";
$stmtOrders = $pdo->prepare($sqlOrders);
$stmtOrders->execute($params);
$reportOrders = $stmtOrders->fetchAll();

// CSV EXPORT HANDLER
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "FastBite_Financial_Analytics_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order Number', 'Date', 'Customer Name', 'Customer Email', 'Customer Phone', 'Payment Method', 'Payment Status', 'Order Status', 'Subtotal ($)', 'Discount ($)', 'Delivery Fee ($)', 'Tax ($)', 'Total ($)']);
    
    foreach ($reportOrders as $row) {
        $disc = (float)($row['discount'] ?? 0) + (float)($row['coupon_discount'] ?? 0);
        fputcsv($output, [
            $row['order_number'],
            $row['created_at'],
            $row['customer_name'],
            $row['customer_email'],
            $row['customer_phone'],
            $row['payment_method'],
            $row['payment_status'],
            $row['order_status'],
            number_format((float)$row['subtotal'], 2, '.', ''),
            number_format($disc, 2, '.', ''),
            number_format((float)$row['delivery_fee'], 2, '.', ''),
            number_format((float)($row['tax'] ?? 0), 2, '.', ''),
            number_format((float)$row['total'], 2, '.', '')
        ]);
    }
    fclose($output);
    exit;
}

// Calculate Financial Metrics
$totalOrders = count($reportOrders);
$grossSales = 0.00;
$totalDiscounts = 0.00;
$totalDeliveryFees = 0.00;
$totalTaxes = 0.00;
$netRevenue = 0.00;
$completedOrders = 0;
$cancelledOrders = 0;
$paymentBreakdown = ['cash' => 0, 'card' => 0, 'mobile' => 0];

foreach ($reportOrders as $ro) {
    if ($ro['order_status'] !== 'cancelled') {
        $grossSales += (float)$ro['subtotal'];
        $disc = (float)($ro['discount'] ?? 0) + (float)($ro['coupon_discount'] ?? 0);
        $totalDiscounts += $disc;
        $totalDeliveryFees += (float)$ro['delivery_fee'];
        $totalTaxes += (float)($ro['tax'] ?? 0);
        $netRevenue += (float)$ro['total'];
        
        if ($ro['order_status'] === 'delivered') {
            $completedOrders++;
        }
    } else {
        $cancelledOrders++;
    }

    $pm = strtolower($ro['payment_method'] ?? 'cash');
    if (strpos($pm, 'card') !== false) {
        $paymentBreakdown['card']++;
    } elseif (strpos($pm, 'mobile') !== false || strpos($pm, 'evc') !== false || strpos($pm, 'zaad') !== false) {
        $paymentBreakdown['mobile']++;
    } else {
        $paymentBreakdown['cash']++;
    }
}

$validOrdersCount = $totalOrders - $cancelledOrders;
$aov = ($validOrdersCount > 0) ? ($netRevenue / $validOrdersCount) : 0.00;

// Daily Sales Trends
$dailyTrendsSql = "SELECT DATE(o.created_at) as sale_date, 
                          COUNT(o.id) as order_count, 
                          SUM(CASE WHEN o.order_status != 'cancelled' THEN o.subtotal ELSE 0 END) as gross_amount,
                          SUM(CASE WHEN o.order_status != 'cancelled' THEN o.delivery_fee ELSE 0 END) as delivery_fees,
                          SUM(CASE WHEN o.order_status != 'cancelled' THEN o.total ELSE 0 END) as daily_revenue
                   FROM `orders` o 
                   $whereClause 
                   GROUP BY DATE(o.created_at) 
                   ORDER BY sale_date DESC 
                   LIMIT 14";
$stmtTrends = $pdo->prepare($dailyTrendsSql);
$stmtTrends->execute($params);
$dailyTrends = $stmtTrends->fetchAll();

// Category Performance in Filtered Range
$catSql = "SELECT c.name as category_name, COUNT(oi.id) as item_count, SUM(oi.subtotal) as category_revenue 
           FROM `order_items` oi 
           JOIN `orders` o ON oi.order_id = o.id 
           LEFT JOIN `categories` c ON (oi.food_category = c.name OR oi.food_category = c.slug) 
           $whereClause AND o.order_status != 'cancelled' 
           GROUP BY c.name 
           ORDER BY category_revenue DESC";
$stmtCat = $pdo->prepare($catSql);
$stmtCat->execute($params);
$categoryPerformance = $stmtCat->fetchAll();

// Top Foods in Filtered Range
$foodSql = "SELECT oi.food_name, oi.food_category, SUM(oi.quantity) as sold_qty, SUM(oi.subtotal) as food_revenue 
            FROM `order_items` oi 
            JOIN `orders` o ON oi.order_id = o.id 
            $whereClause AND o.order_status != 'cancelled' 
            GROUP BY oi.food_name 
            ORDER BY sold_qty DESC 
            LIMIT 10";
$stmtFood = $pdo->prepare($foodSql);
$stmtFood->execute($params);
$topReportFoods = $stmtFood->fetchAll();

// Customer Insights (Top Spenders in range)
$custSql = "SELECT u.id, u.name, u.email, u.phone, COUNT(o.id) as customer_orders, SUM(o.total) as total_spent
            FROM `orders` o
            JOIN `users` u ON o.user_id = u.id
            $whereClause AND o.order_status != 'cancelled'
            GROUP BY u.id
            ORDER BY total_spent DESC
            LIMIT 5";
$stmtCust = $pdo->prepare($custSql);
$stmtCust->execute($params);
$topSpenders = $stmtCust->fetchAll();

// Customer Aggregations
$totalCustomersCount = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'customer'")->fetchColumn();
$repeatCustomersCount = (int)$pdo->query("SELECT COUNT(*) FROM (SELECT user_id FROM `orders` WHERE `order_status` != 'cancelled' GROUP BY user_id HAVING COUNT(id) > 1) as repeats")->fetchColumn();

// Logistics Analytics
$delivAnalytics = function_exists('dbGetDeliveryAnalytics') ? dbGetDeliveryAnalytics() : [];

// Support Ticket Analytics
$supportAnalytics = function_exists('dbGetSupportAnalytics') ? dbGetSupportAnalytics() : [];

$pageTitle = 'Sales & Business Analytics';
$activeMenu = 'reports';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Print Styles -->
<style>
@media print {
    .admin-sidebar, .admin-topbar, #reportFilterBar, .btn-print-hide {
        display: none !important;
    }
    .admin-main {
        padding: 0 !important;
        margin: 0 !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
}
</style>

<!-- Admin Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0">

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent" style="font-size: 0.75rem; letter-spacing: 0.08em;">EXECUTIVE INTELLIGENCE</span>
                <h1 class="admin-title fw-bold mb-1 text-dark">Business Platform &amp; Sales Analytics</h1>
                <p class="admin-desc text-muted mb-0 small">
                    Authoritative financial reporting, sales trends, delivery performance metrics, and support ticket insights.
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 btn-print-hide">
                <button type="button" class="btn btn-outline-pink btn-sm d-inline-flex align-items-center gap-2 shadow-sm rounded-pill px-3" onclick="window.print()">
                    <i class="bi bi-printer"></i>
                    <span>Print Report</span>
                </button>
                <a href="index.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-auth-primary btn-sm d-inline-flex align-items-center gap-2 shadow-sm rounded-pill px-3">
                    <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                    <span>Export CSV</span>
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card p-3 border-0 bg-white shadow-sm rounded-4 mb-4 btn-print-hide" id="reportFilterBar">
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="periodSelect" class="form-label extra-small text-muted fw-bold text-uppercase mb-1">Timeframe</label>
                    <select name="period" id="periodSelect" class="form-select rounded-3">
                        <option value="today" <?= ($period === 'today') ? 'selected' : ''; ?>>Today</option>
                        <option value="yesterday" <?= ($period === 'yesterday') ? 'selected' : ''; ?>>Yesterday</option>
                        <option value="7_days" <?= ($period === '7_days') ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="30_days" <?= ($period === '30_days') ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="this_month" <?= ($period === 'this_month') ? 'selected' : ''; ?>>This Month</option>
                        <option value="all_time" <?= ($period === 'all_time') ? 'selected' : ''; ?>>All Time</option>
                        <option value="custom" <?= ($period === 'custom') ? 'selected' : ''; ?>>Custom Date Range</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-3" id="dateFromCol" style="<?= ($period === 'custom') ? '' : 'display:none;'; ?>">
                    <label for="dateFrom" class="form-label extra-small text-muted fw-bold text-uppercase mb-1">Date From</label>
                    <input type="date" name="date_from" id="dateFrom" class="form-control rounded-3" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-6 col-md-3 col-lg-3" id="dateToCol" style="<?= ($period === 'custom') ? '' : 'display:none;'; ?>">
                    <label for="dateTo" class="form-label extra-small text-muted fw-bold text-uppercase mb-1">Date To</label>
                    <input type="date" name="date_to" id="dateTo" class="form-control rounded-3" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-12 col-md-2 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-auth-primary btn-sm py-2 px-3 w-100 rounded-pill">Apply Filter</button>
                    <a href="index.php" class="btn btn-outline-pink btn-sm py-2 px-3 rounded-pill" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>

        <!-- 8-Metric Financial Summary Grid -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted extra-small text-uppercase fw-bold">Net Revenue</span>
                        <i class="bi bi-cash-stack text-success fs-5"></i>
                    </div>
                    <div class="h3 fw-bold text-success my-1">$<?= number_format($netRevenue, 2); ?></div>
                    <span class="extra-small text-muted">Excluding cancelled orders</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted extra-small text-uppercase fw-bold">Gross Food Sales</span>
                        <i class="bi bi-tag-fill text-primary-accent fs-5"></i>
                    </div>
                    <div class="h3 fw-bold text-dark my-1">$<?= number_format($grossSales, 2); ?></div>
                    <span class="extra-small text-muted">Subtotal before discounts</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted extra-small text-uppercase fw-bold">Total Discounts</span>
                        <i class="bi bi-percent text-danger fs-5"></i>
                    </div>
                    <div class="h3 fw-bold text-danger my-1">-$<?= number_format($totalDiscounts, 2); ?></div>
                    <span class="extra-small text-muted">Promotions &amp; coupons</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted extra-small text-uppercase fw-bold">Avg Order Value</span>
                        <i class="bi bi-graph-up-arrow text-info fs-5"></i>
                    </div>
                    <div class="h3 fw-bold text-primary-accent my-1">$<?= number_format($aov, 2); ?></div>
                    <span class="extra-small text-muted">Per fulfilled order</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted extra-small text-uppercase fw-bold">Total Orders</span>
                        <i class="bi bi-bag-check-fill text-primary fs-5"></i>
                    </div>
                    <div class="h3 fw-bold text-dark my-1"><?= $totalOrders; ?></div>
                    <span class="extra-small text-muted"><?= $completedOrders; ?> delivered • <?= $cancelledOrders; ?> cancelled</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted extra-small text-uppercase fw-bold">Delivery Fees</span>
                        <i class="bi bi-truck text-warning fs-5"></i>
                    </div>
                    <div class="h3 fw-bold text-dark my-1">$<?= number_format($totalDeliveryFees, 2); ?></div>
                    <span class="extra-small text-muted">Logistics revenue</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted extra-small text-uppercase fw-bold">Payment Methods</span>
                        <i class="bi bi-credit-card-fill text-secondary fs-5"></i>
                    </div>
                    <div class="d-flex align-items-center gap-1 my-1">
                        <span class="badge bg-light text-dark border extra-small">COD: <?= $paymentBreakdown['cash']; ?></span>
                        <span class="badge bg-light text-dark border extra-small">Card: <?= $paymentBreakdown['card']; ?></span>
                        <span class="badge bg-light text-dark border extra-small">Mob: <?= $paymentBreakdown['mobile']; ?></span>
                    </div>
                    <span class="extra-small text-muted">Gateway distribution</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted extra-small text-uppercase fw-bold">Customer Base</span>
                        <i class="bi bi-people-fill text-primary-accent fs-5"></i>
                    </div>
                    <div class="h3 fw-bold text-dark my-1"><?= $totalCustomersCount; ?></div>
                    <span class="extra-small text-muted"><?= $repeatCustomersCount; ?> repeat buyers</span>
                </div>
            </div>
        </div>

        <!-- Daily Sales Trend Table -->
        <div class="card border-0 bg-white shadow-sm rounded-4 mb-4 p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar3 text-primary-accent fs-5"></i>
                    <h2 class="h6 fw-bold mb-0 text-dark">Time-Series Daily Revenue Trends</h2>
                </div>
                <span class="extra-small text-muted">Recent daily transaction breakdown</span>
            </div>

            <?php if (empty($dailyTrends)): ?>
                <p class="text-muted extra-small mb-0">No order trends found for this period.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 extra-small">
                        <thead class="table-light text-uppercase text-muted">
                            <tr>
                                <th>Date</th>
                                <th class="text-center">Orders</th>
                                <th class="text-end">Gross Sales ($)</th>
                                <th class="text-end">Delivery Fees ($)</th>
                                <th class="text-end">Net Revenue ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dailyTrends as $dt): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= date('M j, Y (D)', strtotime($dt['sale_date'])); ?></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$dt['order_count']; ?></span></td>
                                    <td class="text-end text-muted">$<?= number_format((float)$dt['gross_amount'], 2); ?></td>
                                    <td class="text-end text-muted">$<?= number_format((float)$dt['delivery_fees'], 2); ?></td>
                                    <td class="text-end fw-bold text-success">$<?= number_format((float)$dt['daily_revenue'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- 2-Column Split: Top Best-Selling Foods & Category Breakdown -->
        <div class="row g-4 mb-4">
            
            <!-- Category Performance -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 bg-white shadow-sm rounded-4 p-3 p-md-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <h2 class="h6 fw-bold mb-0 text-dark">Category Sales Volume</h2>
                        <span class="extra-small text-muted">Revenue contribution</span>
                    </div>

                    <?php if (empty($categoryPerformance)): ?>
                        <p class="text-muted extra-small mb-0">No sales recorded for this timeframe.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 extra-small">
                                <thead class="table-light text-uppercase text-muted">
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-center">Items Sold</th>
                                        <th class="text-end">Revenue ($)</th>
                                        <th class="text-end">% Share</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categoryPerformance as $cp): ?>
                                        <?php
                                            $catRev = (float)$cp['category_revenue'];
                                            $pct = ($netRevenue > 0) ? round(($catRev / $netRevenue) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($cp['category_name'] ?? 'General', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$cp['item_count']; ?></span></td>
                                            <td class="text-end fw-bold text-primary-accent">$<?= number_format($catRev, 2); ?></td>
                                            <td class="text-end text-muted"><?= $pct; ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top 10 Best-Selling Foods -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 bg-white shadow-sm rounded-4 p-3 p-md-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <h2 class="h6 fw-bold mb-0 text-dark">Top 10 Food Items</h2>
                        <span class="extra-small text-muted">Ranked by units sold</span>
                    </div>

                    <?php if (empty($topReportFoods)): ?>
                        <p class="text-muted extra-small mb-0">No food item sales recorded for this timeframe.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 extra-small">
                                <thead class="table-light text-uppercase text-muted">
                                    <tr>
                                        <th>Food Item</th>
                                        <th>Category</th>
                                        <th class="text-center">Units Sold</th>
                                        <th class="text-end">Revenue ($)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topReportFoods as $trf): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($trf['food_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($trf['food_category'] ?? 'Menu', ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td class="text-center"><span class="badge bg-pink-soft text-primary-accent fw-bold"><?= (int)$trf['sold_qty']; ?></span></td>
                                            <td class="text-end fw-bold text-success">$<?= number_format((float)$trf['food_revenue'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- 2-Column Split: Delivery Fleet Performance & Customer Support Metrics -->
        <div class="row g-4 mb-4">
            
            <!-- Delivery Logistics Performance -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 bg-white shadow-sm rounded-4 p-3 p-md-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-truck text-primary-accent fs-5"></i>
                            <h2 class="h6 fw-bold mb-0 text-dark">Logistics &amp; Courier Operations</h2>
                        </div>
                        <a href="../delivery/index.php" class="btn btn-outline-pink btn-sm rounded-pill px-3 extra-small">
                            Delivery Center
                        </a>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="text-muted extra-small d-block mb-1">Delivered Today</span>
                                <strong class="fs-5 text-success"><?= $delivAnalytics['delivered_today'] ?? 0; ?> orders</strong>
                                <span class="extra-small text-muted d-block"><?= $delivAnalytics['total_delivered'] ?? 0; ?> all-time delivered</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="text-muted extra-small d-block mb-1">Active in Transit</span>
                                <strong class="fs-5 text-danger"><?= $delivAnalytics['out_for_delivery_count'] ?? 0; ?> couriers</strong>
                                <span class="extra-small text-muted d-block"><?= $delivAnalytics['picked_up_count'] ?? 0; ?> at kitchen</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="text-muted extra-small d-block mb-1">Avg Transit Time</span>
                                <strong class="fs-5 text-dark"><?= $delivAnalytics['avg_delivery_minutes'] ?? 24.5; ?> min</strong>
                                <span class="extra-small text-muted d-block">Kitchen to doorstep</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="text-muted extra-small d-block mb-1">Fleet Available</span>
                                <strong class="fs-5 text-primary-accent"><?= $delivAnalytics['available_drivers'] ?? 0; ?> / <?= $delivAnalytics['total_drivers'] ?? 0; ?></strong>
                                <span class="extra-small text-muted d-block"><?= $delivAnalytics['busy_drivers'] ?? 0; ?> couriers on duty</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Support Resolution Metrics -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 bg-white shadow-sm rounded-4 p-3 p-md-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-headset text-primary-accent fs-5"></i>
                            <h2 class="h6 fw-bold mb-0 text-dark">Customer Support Performance</h2>
                        </div>
                        <a href="../support/index.php" class="btn btn-outline-pink btn-sm rounded-pill px-3 extra-small">
                            Support Desk
                        </a>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="text-muted extra-small d-block mb-1">Active / Open Tickets</span>
                                <strong class="fs-5 text-warning"><?= $supportAnalytics['active_tickets'] ?? 0; ?></strong>
                                <span class="extra-small text-muted d-block"><?= $supportAnalytics['urgent_count'] ?? 0; ?> urgent priority</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="text-muted extra-small d-block mb-1">Resolved Tickets</span>
                                <strong class="fs-5 text-success"><?= $supportAnalytics['resolved'] ?? 0; ?></strong>
                                <span class="extra-small text-muted d-block"><?= $supportAnalytics['total'] ?? 0; ?> total inquiries</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="text-muted extra-small d-block mb-1">Avg Resolution Time</span>
                                <strong class="fs-5 text-dark"><?= $supportAnalytics['avg_resolution_hours'] ?? 4.5; ?> hrs</strong>
                                <span class="extra-small text-muted d-block">First reply to resolve</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="text-muted extra-small d-block mb-1">Support Resolution Rate</span>
                                <?php 
                                    $totTkts = $supportAnalytics['total'] ?? 0;
                                    $resTkts = ($supportAnalytics['resolved'] ?? 0) + ($supportAnalytics['closed'] ?? 0);
                                    $rate = ($totTkts > 0) ? round(($resTkts / $totTkts) * 100, 1) : 100;
                                ?>
                                <strong class="fs-5 text-primary-accent"><?= $rate; ?>%</strong>
                                <span class="extra-small text-muted d-block">Closed inquiry ratio</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Detailed Transaction Table -->
        <div class="card border-0 bg-white shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <h2 class="h6 fw-bold mb-0 text-dark">Order Transactions Audit Log</h2>
                    <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small rounded-pill px-2"><?= count($reportOrders); ?> Orders</span>
                </div>
            </div>

            <?php if (empty($reportOrders)): ?>
                <div class="p-4 text-center text-muted small">No transactions found for the selected timeframe.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 extra-small">
                        <thead class="table-light text-uppercase text-muted">
                            <tr>
                                <th scope="col" class="ps-4">Order #</th>
                                <th scope="col">Date &amp; Time</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Payment</th>
                                <th scope="col" class="text-center">Order Status</th>
                                <th scope="col" class="text-end pe-4">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportOrders as $ro): ?>
                                <?php
                                    $st = $ro['order_status'];
                                    $badge = 'bg-secondary';
                                    if ($st === 'pending') $badge = 'bg-warning text-dark';
                                    elseif ($st === 'confirmed') $badge = 'bg-info text-dark';
                                    elseif ($st === 'preparing') $badge = 'bg-primary';
                                    elseif ($st === 'out_for_delivery') $badge = 'bg-secondary';
                                    elseif ($st === 'delivered') $badge = 'bg-success';
                                    elseif ($st === 'cancelled') $badge = 'bg-danger';
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold font-monospace text-dark">#<?= htmlspecialchars($ro['order_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= date('M j, Y, H:i', strtotime($ro['created_at'])); ?></td>
                                    <td>
                                        <span class="d-block fw-bold text-dark"><?= htmlspecialchars($ro['customer_name'] ?? 'Guest', ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="text-muted" style="font-size: 0.70rem;"><?= htmlspecialchars($ro['customer_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $ro['payment_method'] ?? 'cash')), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($ro['payment_status'] === 'paid'): ?>
                                            <span class="badge bg-success text-white extra-small ms-1">Paid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $badge; ?> extra-small text-uppercase rounded-pill px-2"><?= htmlspecialchars(str_replace('_', ' ', $st), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td class="text-end pe-4 fw-bold text-primary-accent">
                                        $<?= number_format((float)$ro['total'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
document.getElementById('periodSelect').addEventListener('change', function() {
    const isCustom = this.value === 'custom';
    document.getElementById('dateFromCol').style.display = isCustom ? 'block' : 'none';
    document.getElementById('dateToCol').style.display = isCustom ? 'block' : 'none';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
