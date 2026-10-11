<?php
/**
 * FastBite - Admin Sidebar Navigation Component
 * STEP 30: FastBite Customer Experience, Reviews, Favorites & Promotions
 */

if (!isset($activeMenu)) {
    $activeMenu = 'dashboard';
}

if (!isset($relRoot)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
    $rootDir = str_replace('\\', '/', defined('FASTBITE_ROOT_DIR') ? FASTBITE_ROOT_DIR : dirname(dirname(__DIR__)));
    $rel = trim(str_replace($rootDir, '', $scriptDir), '/');
    $depth = empty($rel) ? 0 : count(explode('/', $rel));
    $relRoot = ($depth > 0) ? str_repeat('../', $depth) : './';
}

// Sidebar quick notification counts
$sidebarPendingOrders = 0;
$sidebarNewMessages = 0;
$sidebarPendingReviews = 0;
$sidebarPendingPayments = 0;
$sidebarActiveDeliveries = 0;
$sidebarAvailableDrivers = 0;
$sidebarOpenTickets = 0;

if (function_exists('getDbConnection')) {
    try {
        $pdo = getDbConnection();
        $sidebarPendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `order_status` = 'pending'")->fetchColumn();
        $sidebarNewMessages = (int)$pdo->query("SELECT COUNT(*) FROM `contact_messages` WHERE `status` = 'new'")->fetchColumn();
        $sidebarPendingPayments = (int)$pdo->query("SELECT COUNT(*) FROM `payment_transactions` WHERE `status` = 'pending'")->fetchColumn();
        if (function_exists('dbGetPendingReviewsCount')) {
            $sidebarPendingReviews = dbGetPendingReviewsCount();
        }
        $sidebarActiveDeliveries = (int)$pdo->query("SELECT COUNT(*) FROM `order_deliveries` WHERE `delivery_status` IN ('unassigned', 'assigned', 'picked_up', 'out_for_delivery')")->fetchColumn();
        $sidebarAvailableDrivers = (int)$pdo->query("SELECT COUNT(*) FROM `delivery_drivers` WHERE `status` = 'available'")->fetchColumn();
        $sidebarOpenTickets = (int)$pdo->query("SELECT COUNT(*) FROM `support_tickets` WHERE `status` = 'open'")->fetchColumn();
    } catch (Exception $e) {
        // Suppress on sidebar
    }
}
?>
<!-- Admin Sidebar Overlay for Mobile -->
<div class="admin-sidebar-backdrop d-lg-none" id="adminSidebarBackdrop"></div>

<!-- Admin Sidebar Navigation -->
<aside class="admin-sidebar bg-white border-end" id="adminSidebar">
    <div class="sidebar-inner p-3 d-flex flex-column h-100">
        
        <!-- Sidebar Brand / Mini Profile -->
        <div class="sidebar-user-block d-flex align-items-center gap-3 p-2 mb-3 rounded bg-pink-soft border border-pink-subtle">
            <div class="stat-icon-wrap flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.1rem;">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <div class="text-truncate">
                <span class="d-block fw-bold text-dark small text-truncate">FastBite Control</span>
                <span class="text-muted extra-small d-block">Admin Portal • Live DB</span>
            </div>
        </div>

        <div class="sidebar-nav-title text-muted text-uppercase fw-bold px-2 mb-2" style="font-size: 0.70rem; letter-spacing: 0.05em;">
            Core Operations
        </div>

        <!-- Navigation Links -->
        <nav class="nav admin-nav flex-column gap-1 mb-auto">
            <a href="<?= $relRoot; ?>admin/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'dashboard') ? 'active' : ''; ?>" title="Dashboard Overview">
                <i class="bi bi-speedometer2 me-2"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= $relRoot; ?>admin/orders/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'orders') ? 'active' : ''; ?>" title="Orders Management">
                <i class="bi bi-bag-check me-2"></i>
                <span>Orders</span>
                <?php if ($sidebarPendingOrders > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto extra-small"><?= $sidebarPendingOrders; ?> new</span>
                <?php endif; ?>
            </a>

            <a href="<?= $relRoot; ?>admin/orders/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'receipts') ? 'active' : ''; ?>" title="Receipts & Invoicing">
                <i class="bi bi-receipt me-2 text-primary-accent"></i>
                <span>Receipts &amp; Invoicing</span>
            </a>

            <a href="<?= $relRoot; ?>admin/delivery/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'delivery' || $activeMenu === 'deliveries') ? 'active' : ''; ?>" title="Delivery Operations Center">
                <i class="bi bi-truck me-2"></i>
                <span>Delivery Ops</span>
                <?php if (!empty($sidebarActiveDeliveries) && $sidebarActiveDeliveries > 0): ?>
                    <span class="badge bg-primary-accent rounded-pill ms-auto extra-small"><?= $sidebarActiveDeliveries; ?> active</span>
                <?php endif; ?>
            </a>

            <a href="<?= $relRoot; ?>admin/delivery/drivers/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'drivers') ? 'active' : ''; ?>" title="Couriers & Drivers Fleet">
                <i class="bi bi-bicycle me-2"></i>
                <span>Couriers Fleet</span>
                <?php if (!empty($sidebarAvailableDrivers) && $sidebarAvailableDrivers > 0): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill ms-auto extra-small"><?= $sidebarAvailableDrivers; ?> ready</span>
                <?php endif; ?>
            </a>

            <a href="<?= $relRoot; ?>admin/payments/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'payments') ? 'active' : ''; ?>" title="Payment Transactions">
                <i class="bi bi-credit-card me-2"></i>
                <span>Payments</span>
                <?php if (!empty($sidebarPendingPayments) && $sidebarPendingPayments > 0): ?>
                    <span class="badge bg-warning text-dark rounded-pill ms-auto extra-small"><?= $sidebarPendingPayments; ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= $relRoot; ?>admin/foods/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'foods' || $activeMenu === 'menu') ? 'active' : ''; ?>" title="Foods Catalog">
                <i class="bi bi-egg-fried me-2"></i>
                <span>Foods Catalog</span>
            </a>

            <a href="<?= $relRoot; ?>admin/categories/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'categories') ? 'active' : ''; ?>" title="Categories Management">
                <i class="bi bi-tags me-2"></i>
                <span>Categories</span>
            </a>

            <a href="<?= $relRoot; ?>admin/reviews/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'reviews') ? 'active' : ''; ?>" title="Customer Reviews Moderation">
                <i class="bi bi-chat-square-quote me-2"></i>
                <span>Reviews</span>
                <?php if ($sidebarPendingReviews > 0): ?>
                    <span class="badge bg-warning text-dark rounded-pill ms-auto extra-small"><?= $sidebarPendingReviews; ?></span>
                <?php endif; ?>
            </a>

            <div class="sidebar-nav-title text-muted text-uppercase fw-bold px-2 mt-3 mb-1" style="font-size: 0.70rem; letter-spacing: 0.05em;">
                Promotions &amp; Marketing
            </div>

            <a href="<?= $relRoot; ?>admin/coupons/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'coupons') ? 'active' : ''; ?>" title="Promo Coupons">
                <i class="bi bi-ticket-perforated me-2"></i>
                <span>Coupons</span>
            </a>

            <a href="<?= $relRoot; ?>admin/discounts/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'discounts') ? 'active' : ''; ?>" title="Discounts & Promos">
                <i class="bi bi-percent me-2"></i>
                <span>Discounts</span>
            </a>

            <div class="sidebar-nav-title text-muted text-uppercase fw-bold px-2 mt-3 mb-1" style="font-size: 0.70rem; letter-spacing: 0.05em;">
                Users &amp; Security
            </div>

            <a href="<?= $relRoot; ?>admin/users/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'users') ? 'active' : ''; ?>" title="Users & Roles Management">
                <i class="bi bi-people me-2"></i>
                <span>Users &amp; Roles</span>
            </a>

            <a href="<?= $relRoot; ?>admin/customers/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'customers') ? 'active' : ''; ?>" title="Customers Directory">
                <i class="bi bi-person-badge me-2"></i>
                <span>Customers</span>
            </a>

            <a href="<?= $relRoot; ?>admin/security/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'security') ? 'active' : ''; ?>" title="Security & Activity Center">
                <i class="bi bi-shield-lock me-2"></i>
                <span>Security Center</span>
            </a>

            <a href="<?= $relRoot; ?>admin/notifications/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'notifications') ? 'active' : ''; ?>" title="Notifications & Broadcast Center">
                <i class="bi bi-bell me-2"></i>
                <span>Notifications</span>
            </a>

            <a href="<?= $relRoot; ?>admin/messages/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'messages') ? 'active' : ''; ?>" title="Contact Messages">
                <i class="bi bi-envelope me-2"></i>
                <span>Messages</span>
                <?php if ($sidebarNewMessages > 0): ?>
                    <span class="badge bg-primary-accent rounded-pill ms-auto extra-small"><?= $sidebarNewMessages; ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= $relRoot; ?>admin/support/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'support') ? 'active' : ''; ?>" title="Customer Support Desk">
                <i class="bi bi-headset me-2"></i>
                <span>Support Desk</span>
                <?php if (!empty($sidebarOpenTickets) && $sidebarOpenTickets > 0): ?>
                    <span class="badge bg-warning text-dark rounded-pill ms-auto extra-small"><?= $sidebarOpenTickets; ?> open</span>
                <?php endif; ?>
            </a>

            <div class="sidebar-nav-title text-muted text-uppercase fw-bold px-2 mt-3 mb-1" style="font-size: 0.70rem; letter-spacing: 0.05em;">
                Business Insights
            </div>

            <a href="<?= $relRoot; ?>admin/reports/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'reports') ? 'active' : ''; ?>" title="Sales & Performance Reports">
                <i class="bi bi-bar-chart-line me-2"></i>
                <span>Reports</span>
            </a>

            <a href="<?= $relRoot; ?>admin/settings/index.php" class="nav-link admin-nav-link <?= ($activeMenu === 'settings') ? 'active' : ''; ?>" title="Business Settings">
                <i class="bi bi-gear me-2"></i>
                <span>Settings</span>
            </a>
        </nav>

        <!-- Sidebar Footer Actions -->
        <div class="sidebar-footer pt-3 border-top mt-3">
            <a href="<?= $relRoot; ?>menu.php" target="_blank" class="btn btn-outline-pink btn-sm w-100 mb-2 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-shop"></i>
                <span>Live Store Menu</span>
            </a>
            <a href="<?= $relRoot; ?>logout.php" class="btn btn-light btn-sm text-danger w-100 d-flex align-items-center justify-content-center gap-2 border">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>

    </div>
</aside>
