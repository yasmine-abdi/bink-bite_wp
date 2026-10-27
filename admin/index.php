<?php
/**
 * FastBite - Admin Dashboard & Business Control Center
 * STEP 25: FastBite Admin Dashboard & Business Control Center
 */

$pageTitle = 'Dashboard & Business Control Center';
$activeMenu = 'dashboard';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Fetch Live Database Analytics & Statistics
$stats = dbGetDashboardStats();
$chart7Days = dbGetSalesChartData('7_days');
$chartToday = dbGetSalesChartData('today');
$chart30Days = dbGetSalesChartData('30_days');
$chartYear = dbGetSalesChartData('this_year');

$allOrders = dbGetAllOrders();
$recentOrders = array_slice($allOrders, 0, 7);
$topFoods = dbGetTopSellingFoods(5);
$alerts = dbGetFoodAvailabilityAlerts();
$recentCustomers = dbGetRecentCustomers(5);
$recentMessages = dbGetAllContactMessages();
$newMessagesCount = count(array_filter($recentMessages, fn($m) => $m['status'] === 'new'));
$activeDiscounts = dbGetAllDiscounts(['status' => 'active']);
?>

<!-- Admin Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0">

        <!-- 1. Top Greeting & Control Bar -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent" style="font-size: 0.75rem; letter-spacing: 0.08em;">CONTROL CENTER</span>
                <h1 class="admin-title fw-bold mb-1 text-dark">Business Dashboard</h1>
                <p class="admin-desc text-muted mb-0 small">
                    Welcome back, <strong><?= htmlspecialchars($adminUser['name'], ENT_QUOTES, 'UTF-8'); ?></strong>! Here is what's happening in your restaurant today (<?= date('l, F j, Y'); ?>).
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="reports/index.php" class="btn btn-outline-pink btn-sm d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>View Full Reports</span>
                </a>
                <a href="foods/create.php" class="btn btn-auth-primary btn-sm d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>+ Add New Food</span>
                </a>
            </div>
        </div>

        <!-- 2. Real-Time KPI Statistics Grid (8 Cards) -->
        <div class="row g-3 mb-4">
            
            <!-- 1. Total Revenue -->
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-danger">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted extra-small text-uppercase fw-bold">Total Revenue</span>
                        <div class="stat-icon-wrap bg-pink-soft text-primary-accent rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-cash-stack fs-5"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold text-dark mb-1">$<?= number_format($stats['total_revenue'], 2); ?></div>
                    <div class="extra-small text-muted d-flex align-items-center gap-1">
                        <span class="text-success fw-bold"><i class="bi bi-graph-up-arrow me-1"></i>All-Time</span>
                        <span>completed orders</span>
                    </div>
                </div>
            </div>

            <!-- 2. Today's Revenue -->
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-success">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted extra-small text-uppercase fw-bold">Today's Sales</span>
                        <div class="stat-icon-wrap bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-wallet2 fs-5"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold text-dark mb-1">$<?= number_format($stats['today_revenue'], 2); ?></div>
                    <div class="extra-small text-muted d-flex align-items-center gap-1">
                        <span class="badge bg-success-subtle text-success extra-small">Live</span>
                        <span>earned today</span>
                    </div>
                </div>
            </div>

            <!-- 3. Total Orders -->
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-primary">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted extra-small text-uppercase fw-bold">Total Orders</span>
                        <div class="stat-icon-wrap bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-bag-check fs-5"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold text-dark mb-1"><?= $stats['total_orders']; ?></div>
                    <div class="extra-small text-muted d-flex align-items-center gap-1">
                        <a href="orders/index.php" class="text-primary text-decoration-none fw-bold">Manage orders &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- 4. Today's Orders -->
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-info">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted extra-small text-uppercase fw-bold">Today's Orders</span>
                        <div class="stat-icon-wrap bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-calendar-event fs-5"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold text-dark mb-1"><?= $stats['today_orders']; ?></div>
                    <div class="extra-small text-muted d-flex align-items-center gap-1">
                        <span class="badge bg-info-subtle text-dark extra-small"><?= $stats['today_orders']; ?> placed</span>
                        <span>since midnight</span>
                    </div>
                </div>
            </div>

            <!-- 5. Pending Orders -->
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-danger">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted extra-small text-uppercase fw-bold">Pending Orders</span>
                        <div class="stat-icon-wrap bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-hourglass-split fs-5"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold text-dark mb-1"><?= $stats['pending_orders']; ?></div>
                    <div class="extra-small text-muted">
                        <?php if ($stats['pending_orders'] > 0): ?>
                            <a href="orders/index.php?status=pending" class="text-danger fw-bold text-decoration-none">
                                <i class="bi bi-exclamation-circle-fill me-1"></i> Requires confirmation
                            </a>
                        <?php else: ?>
                            <span class="text-success"><i class="bi bi-check2-all me-1"></i> Kitchen queue clear</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 6. Total Customers -->
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-warning">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted extra-small text-uppercase fw-bold">Customers</span>
                        <div class="stat-icon-wrap bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-people fs-5"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold text-dark mb-1"><?= $stats['total_customers']; ?></div>
                    <div class="extra-small text-muted">
                        <a href="customers/index.php" class="text-muted text-decoration-none">
                            <strong>+<?= $stats['today_customers']; ?></strong> new joined today
                        </a>
                    </div>
                </div>
            </div>

            <!-- 7. Available Foods -->
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-success">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted extra-small text-uppercase fw-bold">Live Foods</span>
                        <div class="stat-icon-wrap bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-egg-fried fs-5"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold text-dark mb-1"><?= $stats['available_foods']; ?></div>
                    <div class="extra-small text-muted">
                        <span>active in storefront</span>
                    </div>
                </div>
            </div>

            <!-- 8. Sold Out Foods -->
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 <?= ($stats['sold_out_foods'] > 0) ? 'border-danger' : 'border-secondary'; ?>">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted extra-small text-uppercase fw-bold">Sold Out Items</span>
                        <div class="stat-icon-wrap bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-slash-circle fs-5"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold text-dark mb-1"><?= $stats['sold_out_foods']; ?></div>
                    <div class="extra-small text-muted">
                        <?php if ($stats['sold_out_foods'] > 0): ?>
                            <a href="foods/index.php?availability=sold_out" class="text-warning fw-bold text-decoration-none">
                                Restock items &rarr;
                            </a>
                        <?php else: ?>
                            <span class="text-success">All items in stock</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- 3. Quick Action Hub -->
        <div class="card border-0 bg-white shadow-sm rounded-3 p-3 p-md-4 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h6 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge-fill text-warning"></i> Quick Operations Command
                </h2>
                <span class="extra-small text-muted">One-click restaurant shortcuts</span>
            </div>
            <div class="row g-2">
                <div class="col-6 col-sm-4 col-md-3 col-xl">
                    <a href="orders/index.php" class="btn btn-outline-light text-dark border w-100 py-2 d-flex flex-column align-items-center gap-1 shadow-sm text-decoration-none">
                        <i class="bi bi-bag-check-fill fs-5 text-primary-accent"></i>
                        <span class="extra-small fw-bold">Manage Orders</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-md-3 col-xl">
                    <a href="foods/create.php" class="btn btn-outline-light text-dark border w-100 py-2 d-flex flex-column align-items-center gap-1 shadow-sm text-decoration-none">
                        <i class="bi bi-plus-circle fs-5 text-success"></i>
                        <span class="extra-small fw-bold">New Food Item</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-md-3 col-xl">
                    <a href="categories/index.php" class="btn btn-outline-light text-dark border w-100 py-2 d-flex flex-column align-items-center gap-1 shadow-sm text-decoration-none">
                        <i class="bi bi-tags-fill fs-5 text-info"></i>
                        <span class="extra-small fw-bold">Categories</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-md-3 col-xl">
                    <a href="discounts/index.php" class="btn btn-outline-light text-dark border w-100 py-2 d-flex flex-column align-items-center gap-1 shadow-sm text-decoration-none">
                        <i class="bi bi-percent fs-5 text-danger"></i>
                        <span class="extra-small fw-bold">Promotions</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-md-3 col-xl">
                    <a href="customers/index.php" class="btn btn-outline-light text-dark border w-100 py-2 d-flex flex-column align-items-center gap-1 shadow-sm text-decoration-none">
                        <i class="bi bi-people-fill fs-5 text-primary"></i>
                        <span class="extra-small fw-bold">Customers</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-md-3 col-xl">
                    <a href="messages/index.php" class="btn btn-outline-light text-dark border w-100 py-2 d-flex flex-column align-items-center gap-1 shadow-sm text-decoration-none position-relative">
                        <i class="bi bi-envelope-fill fs-5 text-warning"></i>
                        <span class="extra-small fw-bold">Inquiries</span>
                        <?php if ($newMessagesCount > 0): ?>
                            <span class="position-absolute top-0 end-0 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-md-3 col-xl">
                    <a href="reports/index.php" class="btn btn-outline-light text-dark border w-100 py-2 d-flex flex-column align-items-center gap-1 shadow-sm text-decoration-none">
                        <i class="bi bi-file-earmark-spreadsheet-fill fs-5 text-success"></i>
                        <span class="extra-small fw-bold">Export Sales</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-md-3 col-xl">
                    <a href="settings/index.php" class="btn btn-outline-light text-dark border w-100 py-2 d-flex flex-column align-items-center gap-1 shadow-sm text-decoration-none">
                        <i class="bi bi-gear-fill fs-5 text-secondary"></i>
                        <span class="extra-small fw-bold">Settings</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4. Interactive Sales Chart & Order Status Breakdown -->
        <div class="row g-4 mb-4">
            
            <!-- Left: Sales & Revenue Analytics Chart -->
            <div class="col-12 col-xl-8">
                <div class="card border-0 bg-white shadow-sm rounded-3 p-3 p-md-4 h-100">
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                        <div>
                            <h2 class="h6 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-graph-up text-primary-accent"></i> Sales &amp; Revenue Trends
                            </h2>
                            <span class="extra-small text-muted">Real-time financial performance curve</span>
                        </div>
                        
                        <!-- Period Selector Tabs -->
                        <div class="btn-group btn-group-sm shadow-sm" role="group" id="chartPeriodGroup">
                            <button type="button" class="btn btn-outline-pink btn-chart-period" data-period="today">Today</button>
                            <button type="button" class="btn btn-outline-pink btn-chart-period active" data-period="7_days">7 Days</button>
                            <button type="button" class="btn btn-outline-pink btn-chart-period" data-period="30_days">30 Days</button>
                            <button type="button" class="btn btn-outline-pink btn-chart-period" data-period="this_year">This Year</button>
                        </div>
                    </div>

                    <!-- Chart Canvas Container -->
                    <div class="position-relative" style="min-height: 280px; max-height: 320px;">
                        <canvas id="salesTrendsChart"></canvas>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between pt-3 mt-2 border-top extra-small text-muted">
                        <div class="d-flex align-items-center gap-3">
                            <span class="d-flex align-items-center gap-1">
                                <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background: #e11d48;"></span>
                                Revenue ($)
                            </span>
                            <span class="d-flex align-items-center gap-1">
                                <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background: #f43f5e; opacity: 0.5;"></span>
                                Order Volume
                            </span>
                        </div>
                        <div>
                            <i class="bi bi-shield-check text-success me-1"></i> Data synced live from MySQL orders
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Order Fulfillment Status Breakdown -->
            <div class="col-12 col-xl-4">
                <div class="card border-0 bg-white shadow-sm rounded-3 p-3 p-md-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <div>
                            <h2 class="h6 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-pie-chart-fill text-primary-accent"></i> Order Fulfillment
                            </h2>
                            <span class="extra-small text-muted">Current order pipeline status</span>
                        </div>
                        <a href="orders/index.php" class="extra-small text-primary-accent fw-bold text-decoration-none">View All</a>
                    </div>

                    <?php
                        $stCounts = $stats['status_counts'];
                    ?>

                    <div class="d-flex flex-column gap-3">
                        
                        <!-- Pending -->
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="extra-small fw-bold text-dark d-flex align-items-center gap-1">
                                    <span class="d-inline-block rounded-circle bg-warning" style="width: 8px; height: 8px;"></span>
                                    Pending Approval
                                </span>
                                <span class="extra-small fw-bold text-warning"><?= $stCounts['pending']; ?> orders</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= round(($stCounts['pending'] / max(1, $stats['total_orders'])) * 100); ?>%"></div>
                            </div>
                        </div>

                        <!-- Confirmed -->
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="extra-small fw-bold text-dark d-flex align-items-center gap-1">
                                    <span class="d-inline-block rounded-circle bg-info" style="width: 8px; height: 8px;"></span>
                                    Confirmed
                                </span>
                                <span class="extra-small fw-bold text-info"><?= $stCounts['confirmed']; ?> orders</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?= round(($stCounts['confirmed'] / max(1, $stats['total_orders'])) * 100); ?>%"></div>
                            </div>
                        </div>

                        <!-- Preparing -->
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="extra-small fw-bold text-dark d-flex align-items-center gap-1">
                                    <span class="d-inline-block rounded-circle bg-primary" style="width: 8px; height: 8px;"></span>
                                    In Kitchen (Preparing)
                                </span>
                                <span class="extra-small fw-bold text-primary"><?= $stCounts['preparing']; ?> orders</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= round(($stCounts['preparing'] / max(1, $stats['total_orders'])) * 100); ?>%"></div>
                            </div>
                        </div>

                        <!-- Out for Delivery -->
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="extra-small fw-bold text-dark d-flex align-items-center gap-1">
                                    <span class="d-inline-block rounded-circle bg-secondary" style="width: 8px; height: 8px;"></span>
                                    Out for Delivery
                                </span>
                                <span class="extra-small fw-bold text-dark"><?= $stCounts['out_for_delivery']; ?> orders</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-secondary" role="progressbar" style="width: <?= round(($stCounts['out_for_delivery'] / max(1, $stats['total_orders'])) * 100); ?>%"></div>
                            </div>
                        </div>

                        <!-- Delivered -->
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="extra-small fw-bold text-dark d-flex align-items-center gap-1">
                                    <span class="d-inline-block rounded-circle bg-success" style="width: 8px; height: 8px;"></span>
                                    Delivered
                                </span>
                                <span class="extra-small fw-bold text-success"><?= $stCounts['delivered']; ?> orders</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= round(($stCounts['delivered'] / max(1, $stats['total_orders'])) * 100); ?>%"></div>
                            </div>
                        </div>

                        <!-- Cancelled -->
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="extra-small fw-bold text-muted d-flex align-items-center gap-1">
                                    <span class="d-inline-block rounded-circle bg-danger" style="width: 8px; height: 8px;"></span>
                                    Cancelled
                                </span>
                                <span class="extra-small fw-bold text-danger"><?= $stCounts['cancelled']; ?> orders</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?= round(($stCounts['cancelled'] / max(1, $stats['total_orders'])) * 100); ?>%"></div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </div>

        <!-- 5. Real-Time Alert & System Notifications -->
        <?php if (!empty($alerts['sold_out_foods']) || !empty($alerts['inactive_categories']) || !empty($alerts['expired_discounts'])): ?>
            <div class="card border-0 bg-white shadow-sm rounded-3 p-3 p-md-4 mb-4 border-start border-warning border-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                    <h3 class="h6 fw-bold mb-0 text-dark">Business &amp; Inventory Attention Alerts</h3>
                </div>
                <div class="row g-2">
                    <?php if (!empty($alerts['sold_out_foods'])): ?>
                        <div class="col-12 col-md-4">
                            <div class="p-2 rounded bg-light border extra-small">
                                <strong class="text-danger"><i class="bi bi-x-circle me-1"></i><?= count($alerts['sold_out_foods']); ?> Sold Out Items:</strong>
                                <span class="text-muted d-block text-truncate">
                                    <?= htmlspecialchars(implode(', ', array_column($alerts['sold_out_foods'], 'name')), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <a href="foods/index.php?availability=sold_out" class="text-primary-accent fw-bold text-decoration-none mt-1 d-inline-block">Update Stock &rarr;</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($alerts['inactive_categories'])): ?>
                        <div class="col-12 col-md-4">
                            <div class="p-2 rounded bg-light border extra-small">
                                <strong class="text-warning"><i class="bi bi-tag me-1"></i><?= count($alerts['inactive_categories']); ?> Inactive Categories:</strong>
                                <span class="text-muted d-block text-truncate">
                                    <?= htmlspecialchars(implode(', ', array_column($alerts['inactive_categories'], 'name')), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <a href="categories/index.php" class="text-primary-accent fw-bold text-decoration-none mt-1 d-inline-block">Manage Categories &rarr;</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($alerts['expired_discounts'])): ?>
                        <div class="col-12 col-md-4">
                            <div class="p-2 rounded bg-light border extra-small">
                                <strong class="text-secondary"><i class="bi bi-percent me-1"></i><?= count($alerts['expired_discounts']); ?> Expired Discounts:</strong>
                                <span class="text-muted d-block text-truncate">
                                    <?= htmlspecialchars(implode(', ', array_column($alerts['expired_discounts'], 'name')), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <a href="discounts/index.php" class="text-primary-accent fw-bold text-decoration-none mt-1 d-inline-block">Review Promos &rarr;</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 6. Main 2-Column Split: Orders / Foods + Customers / Messages -->
        <div class="row g-4">
            
            <!-- Left 8 Columns -->
            <div class="col-12 col-xl-8 d-flex flex-column gap-4">
                
                <!-- Recent Customer Orders Table -->
                <div class="card border-0 bg-white shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <h2 class="h6 fw-bold mb-0 text-dark">Recent Customer Orders</h2>
                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small">Last 7</span>
                        </div>
                        <a href="orders/index.php" class="btn btn-sm btn-outline-pink py-1 px-2 extra-small">
                            <span>All Orders</span>
                            <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <?php if (empty($recentOrders)): ?>
                        <div class="p-4 text-center text-muted small">
                            <i class="bi bi-inbox fs-3 d-block mb-2 text-muted"></i>
                            No orders placed yet.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 extra-small">
                                <thead class="table-light text-uppercase text-muted">
                                    <tr>
                                        <th scope="col" class="ps-4">Order #</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Items</th>
                                        <th scope="col">Total</th>
                                        <th scope="col" class="text-center">Status</th>
                                        <th scope="col" class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $ro): ?>
                                        <?php
                                            $st = $ro['order_status'];
                                            $badgeClass = 'bg-secondary';
                                            if ($st === 'pending') $badgeClass = 'bg-warning text-dark';
                                            elseif ($st === 'confirmed') $badgeClass = 'bg-info text-dark';
                                            elseif ($st === 'preparing') $badgeClass = 'bg-primary';
                                            elseif ($st === 'out_for_delivery') $badgeClass = 'bg-secondary';
                                            elseif ($st === 'delivered') $badgeClass = 'bg-success';
                                            elseif ($st === 'cancelled') $badgeClass = 'bg-danger';
                                        ?>
                                        <tr>
                                            <td class="ps-4 fw-bold font-monospace text-dark">
                                                <a href="orders/view.php?order=<?= urlencode($ro['order_number']); ?>" class="text-dark text-decoration-none text-primary-hover">
                                                    #<?= htmlspecialchars($ro['order_number'], ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="d-block fw-bold text-dark"><?= htmlspecialchars($ro['customer_name'] ?? 'Guest', ENT_QUOTES, 'UTF-8'); ?></span>
                                                <span class="text-muted" style="font-size: 0.70rem;"><?= htmlspecialchars($ro['customer_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= count($ro['items']); ?> items</span>
                                            </td>
                                            <td class="fw-bold text-primary-accent">
                                                $<?= number_format((float)$ro['total'], 2); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $badgeClass; ?> extra-small text-uppercase">
                                                    <?= htmlspecialchars(str_replace('_', ' ', $st), ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="orders/view.php?order=<?= urlencode($ro['order_number']); ?>" class="btn btn-sm btn-outline-pink py-1 px-2 extra-small" title="Manage Order &amp; Receipt Center">
                                                    <i class="bi bi-eye"></i> Manage
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Top Selling Foods Performance -->
                <div class="card border-0 bg-white shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <h2 class="h6 fw-bold mb-0 text-dark">Top Performing Foods</h2>
                            <span class="badge bg-success-subtle text-success border border-success-subtle extra-small">Best Sellers</span>
                        </div>
                        <a href="foods/index.php" class="btn btn-sm btn-outline-pink py-1 px-2 extra-small">
                            <span>Manage Menu</span>
                            <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <div class="p-3">
                        <div class="row g-3">
                            <?php foreach ($topFoods as $idx => $tf): ?>
                                <?php
                                    $tfImg = getFoodImage(!empty($tf['image']) ? $tf['image'] : ($tf['slug'] ?? 'food-placeholder.jpg'));
                                ?>
                                <div class="col-12 col-md-6 col-xxl">
                                    <div class="p-2 border rounded bg-light-subtle d-flex align-items-center gap-2 h-100">
                                        <div class="position-relative flex-shrink-0">
                                            <img src="../<?= htmlspecialchars($tfImg, ENT_QUOTES, 'UTF-8'); ?>" 
                                                 alt="<?= htmlspecialchars($tf['food_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                 class="rounded object-fit-cover border" 
                                                 style="width: 44px; height: 44px;">
                                            <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger extra-small" style="font-size: 0.65rem;">
                                                #<?= $idx + 1; ?>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 text-truncate">
                                            <span class="d-block fw-bold text-dark extra-small text-truncate"><?= htmlspecialchars($tf['food_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="text-muted extra-small d-block" style="font-size: 0.70rem;"><?= htmlspecialchars($tf['food_category'] ?? 'Fast Food', ENT_QUOTES, 'UTF-8'); ?></span>
                                            <div class="d-flex align-items-center justify-content-between mt-1">
                                                <span class="badge bg-pink-soft text-primary-accent extra-small" style="font-size: 0.68rem;"><?= $tf['total_sold']; ?> sold</span>
                                                <strong class="text-success extra-small" style="font-size: 0.70rem;">$<?= number_format((float)$tf['total_revenue'], 2); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right 4 Columns -->
            <div class="col-12 col-xl-4 d-flex flex-column gap-4">
                
                <!-- Recent Customers -->
                <div class="card border-0 bg-white shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <h2 class="h6 fw-bold mb-0 text-dark">Recent Customers</h2>
                            <span class="badge bg-primary-subtle text-primary extra-small"><?= count($recentCustomers); ?> latest</span>
                        </div>
                        <a href="customers/index.php" class="extra-small text-primary-accent fw-bold text-decoration-none">All Customers</a>
                    </div>
                    
                    <div class="p-3 d-flex flex-column gap-2">
                        <?php if (empty($recentCustomers)): ?>
                            <p class="text-muted extra-small mb-0">No registered customers yet.</p>
                        <?php else: ?>
                            <?php foreach ($recentCustomers as $rc): ?>
                                <?php
                                    $rcAvatar = getAvatarImage($rc['avatar'] ?? 'avatar-placeholder.jpg');
                                ?>
                                <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light border">
                                    <div class="d-flex align-items-center gap-2 text-truncate">
                                        <img src="../<?= htmlspecialchars($rcAvatar, ENT_QUOTES, 'UTF-8'); ?>" 
                                             alt="<?= htmlspecialchars($rc['name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                             class="rounded-circle border object-fit-cover" 
                                             style="width: 36px; height: 36px;">
                                        <div class="text-truncate">
                                            <span class="d-block fw-bold text-dark extra-small text-truncate"><?= htmlspecialchars($rc['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="text-muted extra-small d-block" style="font-size: 0.70rem;"><?= htmlspecialchars($rc['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    </div>
                                    <span class="badge <?= ($rc['status'] === 'active') ? 'bg-success-subtle text-success' : 'bg-light text-muted'; ?> extra-small">
                                        <?= ucfirst($rc['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Customer Inquiries & Messages -->
                <div class="card border-0 bg-white shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <h2 class="h6 fw-bold mb-0 text-dark">Customer Inquiries</h2>
                            <?php if ($newMessagesCount > 0): ?>
                                <span class="badge bg-danger extra-small"><?= $newMessagesCount; ?> new</span>
                            <?php endif; ?>
                        </div>
                        <a href="messages/index.php" class="extra-small text-primary-accent fw-bold text-decoration-none">View All</a>
                    </div>
                    
                    <div class="p-3 d-flex flex-column gap-2">
                        <?php if (empty($recentMessages)): ?>
                            <p class="text-muted extra-small mb-0">No customer messages received yet.</p>
                        <?php else: ?>
                            <?php foreach (array_slice($recentMessages, 0, 4) as $msg): ?>
                                <div class="p-2 rounded bg-light border">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <strong class="extra-small text-dark"><?= htmlspecialchars($msg['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span class="badge <?= ($msg['status'] === 'new') ? 'bg-danger-subtle text-danger' : (($msg['status'] === 'read') ? 'bg-info-subtle text-info' : 'bg-success-subtle text-success'); ?> extra-small" style="font-size: 0.65rem;">
                                            <?= ucfirst($msg['status']); ?>
                                        </span>
                                    </div>
                                    <p class="extra-small text-muted mb-1 text-truncate" style="font-size: 0.72rem;">
                                        <strong><?= htmlspecialchars($msg['subject'] ?? 'Inquiry', ENT_QUOTES, 'UTF-8'); ?>:</strong> <?= htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                    <div class="d-flex align-items-center justify-content-between" style="font-size: 0.68rem;">
                                        <span class="text-muted"><?= date('M j, H:i', strtotime($msg['created_at'])); ?></span>
                                        <a href="messages/index.php?search=<?= urlencode($msg['email']); ?>" class="text-primary-accent text-decoration-none fw-bold">Read &rarr;</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Active Promotions Mini-List -->
                <div class="card border-0 bg-white shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <h2 class="h6 fw-bold mb-0 text-dark">Active Discounts</h2>
                            <span class="badge bg-danger-subtle text-danger extra-small"><?= count($activeDiscounts); ?> Live</span>
                        </div>
                        <a href="discounts/index.php" class="extra-small text-primary-accent fw-bold text-decoration-none">Manage</a>
                    </div>
                    
                    <div class="p-3 d-flex flex-column gap-2">
                        <?php if (empty($activeDiscounts)): ?>
                            <p class="text-muted extra-small mb-0">No active discounts right now.</p>
                        <?php else: ?>
                            <?php foreach (array_slice($activeDiscounts, 0, 3) as $ad): ?>
                                <div class="d-flex align-items-center justify-content-between p-2 rounded bg-pink-soft border border-pink-subtle">
                                    <div>
                                        <span class="d-block fw-bold text-dark extra-small"><?= htmlspecialchars($ad['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="text-muted extra-small" style="font-size: 0.68rem;">Target: <?= htmlspecialchars(ucfirst($ad['type']), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <span class="badge bg-danger text-white extra-small fw-bold">
                                        <?= (int)$ad['discount_percent']; ?>% OFF
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>

    </div>
</main>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart Datasets from PHP Database Queries
    const chartDatasets = {
        'today': <?= json_encode($chartToday, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
        '7_days': <?= json_encode($chart7Days, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
        '30_days': <?= json_encode($chart30Days, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
        'this_year': <?= json_encode($chartYear, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
    };

    const ctx = document.getElementById('salesTrendsChart');
    if (!ctx) return;

    let initialData = chartDatasets['7_days'];

    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: initialData.labels,
            datasets: [
                {
                    label: 'Revenue ($)',
                    data: initialData.revenue,
                    borderColor: '#e11d48',
                    backgroundColor: 'rgba(225, 29, 72, 0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#e11d48',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    yAxisID: 'y'
                },
                {
                    label: 'Orders Count',
                    data: initialData.orders,
                    borderColor: '#f43f5e',
                    backgroundColor: 'rgba(244, 63, 94, 0.25)',
                    type: 'bar',
                    borderRadius: 4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 10,
                    titleFont: { size: 12, weight: 'bold' },
                    bodyFont: { size: 12 },
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                return ' Revenue: $' + context.parsed.y.toFixed(2);
                            } else {
                                return ' Orders: ' + context.parsed.y + ' orders';
                            }
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11, family: 'Plus Jakarta Sans' } }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        callback: function(value) { return '$' + value; },
                        font: { size: 11, family: 'Plus Jakarta Sans' }
                    }
                },
                y1: {
                    type: 'linear',
                    display: false,
                    position: 'right',
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });

    // Handle Period Switch Buttons
    document.querySelectorAll('.btn-chart-period').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-chart-period').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const period = this.getAttribute('data-period');
            const dataObj = chartDatasets[period];

            if (dataObj && salesChart) {
                salesChart.data.labels = dataObj.labels;
                salesChart.data.datasets[0].data = dataObj.revenue;
                salesChart.data.datasets[1].data = dataObj.orders;
                salesChart.update();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
