<?php
/**
 * FastBite - Customer Dashboard
 * STEP 30: FastBite Customer Experience, Reviews, Favorites & Promotions
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';

requireLogin('login.php');

$user = currentUser();
if (!$user) {
    header("Location: login.php");
    exit;
}

$currentPage = 'dashboard';
$projectName = 'FastBite';
$userId = (int)$user['id'];

// Fetch real user data
$customerData = [
    'name' => $user['name'],
    'firstName' => explode(' ', $user['name'])[0],
    'email' => $user['email'],
    'phone' => !empty($user['phone']) ? $user['phone'] : '+252 63 000 0000',
    'memberSince' => date('F Y', strtotime($user['created_at'])),
    'avatar' => function_exists('getAvatarImage') ? getAvatarImage($user['avatar'] ?? 'avatar-placeholder.jpg') : 'assets/images/placeholders/avatar-placeholder.jpg'
];

// Fetch real orders, addresses, notifications & favorites
$userOrders = dbGetUserOrders($userId);
$userAddresses = dbGetUserAddresses($userId);
$userNotifs = dbGetUserNotifications($userId);
$userFavoritesCount = function_exists('dbGetUserFavoritesCount') ? dbGetUserFavoritesCount($userId) : 0;
$userReviewsCount = function_exists('dbGetUserReviews') ? count(dbGetUserReviews($userId)) : 0;

// Calculate real metrics
$totalOrdersCount = count($userOrders);
$completedOrdersCount = 0;
$pendingOrdersCount = 0;

foreach ($userOrders as $ord) {
    if ($ord['order_status'] === 'delivered') {
        $completedOrdersCount++;
    } elseif (in_array($ord['order_status'], ['pending', 'confirmed', 'preparing', 'out_for_delivery'])) {
        $pendingOrdersCount++;
    }
}

$stats = [
    ['title' => 'Total Orders', 'value' => (string)$totalOrdersCount, 'icon' => 'bi-bag-check', 'link' => 'my-orders.php'],
    ['title' => 'Saved Favorites', 'value' => (string)$userFavoritesCount, 'icon' => 'bi-heart', 'link' => 'favorites.php'],
    ['title' => 'Pending Orders', 'value' => (string)$pendingOrdersCount, 'icon' => 'bi-clock', 'link' => 'my-orders.php'],
    ['title' => 'Saved Addresses', 'value' => (string)count($userAddresses), 'icon' => 'bi-geo-alt', 'link' => 'addresses.php']
];

// Slice 4 most recent orders
$recentOrders = array_slice($userOrders, 0, 4);

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - <?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?> - Fast Food Online</title>
    <meta name="description" content="View your FastBite account overview, track active food orders, and manage your profile.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">
    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main id="main-content" class="dashboard-main-wrapper py-4 py-md-5">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb extra-small mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted"><i class="bi bi-house me-1" aria-hidden="true"></i>Home</a></li>
                    <li class="breadcrumb-item active text-primary-accent fw-bold" aria-current="page">Dashboard</li>
                </ol>
            </nav>

            <?php if (!empty($msg)): ?>
                <div class="alert alert-success py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <div><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endif; ?>

            <div class="dashboard-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <span class="dashboard-eyebrow d-inline-block">OVERVIEW</span>
                    <h1 class="dashboard-title fw-bold mb-1">Welcome back, <?= htmlspecialchars($customerData['firstName'], ENT_QUOTES, 'UTF-8'); ?>!</h1>
                    <p class="dashboard-desc text-muted mb-0">Track your active meals, reorder past favorites, and manage your account details.</p>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="menu.php" class="btn btn-auth-primary d-inline-flex align-items-center gap-1 shadow-sm" title="Explore Menu & Order">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        <span>Order Food</span>
                    </a>
                    <a href="favorites.php" class="btn btn-outline-pink d-inline-flex align-items-center gap-1" title="Saved Favorites">
                        <i class="bi bi-heart" aria-hidden="true"></i>
                        <span>Favorites</span>
                    </a>
                </div>
            </div>

            <div class="row g-4">
                <aside class="col-12 col-lg-4 col-xl-3" aria-label="Account Navigation">
                    <div class="dashboard-sidebar card p-3 p-md-4 border-0 bg-white shadow-sm sticky-lg-top" style="top: 85px; z-index: 10;">
                        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom sidebar-user-brief">
                            <img src="<?= htmlspecialchars($customerData['avatar'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="<?= htmlspecialchars($customerData['name'], ENT_QUOTES, 'UTF-8'); ?> Avatar" 
                                 class="sidebar-user-avatar">
                            <div class="text-truncate">
                                <span class="d-block fw-bold text-dark text-truncate"><?= htmlspecialchars($customerData['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="d-block extra-small text-muted text-truncate"><?= htmlspecialchars($customerData['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>

                        <nav class="dashboard-sidebar-nav d-flex flex-column gap-1" aria-label="Account Sections">
                            <a href="customer-dashboard.php" class="dashboard-nav-link active text-decoration-none d-flex align-items-center gap-2" aria-current="page">
                                <i class="bi bi-grid-1x2" aria-hidden="true"></i>
                                <span>Dashboard</span>
                            </a>
                            <a href="my-orders.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-bag" aria-hidden="true"></i>
                                <span>My Orders</span>
                                <?php if ($totalOrdersCount > 0): ?>
                                    <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle ms-auto extra-small"><?= $totalOrdersCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="favorites.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-heart" aria-hidden="true"></i>
                                <span>Saved Favorites</span>
                                <?php if ($userFavoritesCount > 0): ?>
                                    <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle ms-auto extra-small"><?= $userFavoritesCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="addresses.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                <span>Saved Addresses</span>
                            </a>
                            <a href="profile.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-person" aria-hidden="true"></i>
                                <span>Profile</span>
                            </a>
                            <a href="profile.php#settings" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-gear" aria-hidden="true"></i>
                                <span>Account Settings</span>
                            </a>
                            <a href="notifications.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-bell" aria-hidden="true"></i>
                                <span>Notifications</span>
                            </a>

                            <div class="sidebar-nav-divider my-2 pt-2 border-top"></div>

                            <a href="logout.php" class="dashboard-nav-link text-danger text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                <span>Logout</span>
                            </a>
                        </nav>
                    </div>
                </aside>

                <div class="col-12 col-lg-8 col-xl-9">
                    <div class="dashboard-welcome-banner card border-0 bg-white p-4 mb-4 shadow-sm position-relative overflow-hidden">
                        <div class="row align-items-center g-3">
                            <div class="col-auto">
                                <div class="welcome-avatar-wrapper position-relative">
                                    <img src="<?= htmlspecialchars($customerData['avatar'], ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="<?= htmlspecialchars($customerData['name'], ENT_QUOTES, 'UTF-8'); ?> Profile Picture" 
                                         class="welcome-avatar">
                                    <span class="welcome-status-dot" title="Active Account"></span>
                                </div>
                            </div>
                            <div class="col">
                                <h2 class="welcome-name h4 fw-bold text-dark mb-1"><?= htmlspecialchars($customerData['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <div class="welcome-meta d-flex flex-wrap align-items-center gap-3 text-muted extra-small">
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-envelope text-primary-accent" aria-hidden="true"></i>
                                        <?= htmlspecialchars($customerData['email'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-telephone text-primary-accent" aria-hidden="true"></i>
                                        <?= htmlspecialchars($customerData['phone'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-calendar3 text-primary-accent" aria-hidden="true"></i>
                                        Member since <?= htmlspecialchars($customerData['memberSince'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <section class="dashboard-stats-grid mb-4" aria-label="Account Summary Metrics">
                        <div class="row g-3">
                            <?php foreach ($stats as $stat): ?>
                                <div class="col-6 col-md-3">
                                    <a href="<?= $stat['link']; ?>" class="text-decoration-none">
                                        <div class="dashboard-stat-card card h-100 p-3 border-0 bg-white shadow-sm text-center transition-all hover-shadow">
                                            <div class="stat-icon-box bg-pink-soft text-primary-accent mx-auto mb-2 d-flex align-items-center justify-content-center">
                                                <i class="bi <?= htmlspecialchars($stat['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                            </div>
                                            <span class="stat-value h4 fw-bold text-dark d-block mb-1"><?= htmlspecialchars($stat['value'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="stat-title text-muted extra-small text-uppercase fw-semibold"><?= htmlspecialchars($stat['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="dashboard-recent-orders card border-0 bg-white p-4 mb-4 shadow-sm" aria-label="Recent Orders">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div>
                                <h2 class="h5 fw-bold text-dark mb-0">Recent Orders</h2>
                                <span class="text-muted extra-small">Your latest order activities and delivery status</span>
                            </div>
                            <a href="my-orders.php" class="btn btn-sm btn-link text-primary-accent text-decoration-none fw-semibold">
                                View All (<?= $totalOrdersCount; ?>) <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </a>
                        </div>

                        <?php if (empty($recentOrders)): ?>
                            <div class="text-center py-4">
                                <div class="empty-icon-wrap mb-2 text-muted" style="font-size: 2.5rem; opacity: 0.6;">
                                    <i class="bi bi-basket" aria-hidden="true"></i>
                                </div>
                                <h3 class="h6 fw-bold text-dark mb-1">No Orders Placed Yet</h3>
                                <p class="text-muted small mb-3">Explore our menu and place your first delicious FastBite order.</p>
                                <a href="menu.php" class="btn btn-sm btn-hero-primary d-inline-flex align-items-center gap-1">
                                    <span>Browse Delicious Menu</span>
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle dashboard-table mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="border-0">Order ID</th>
                                            <th scope="col" class="border-0">Date</th>
                                            <th scope="col" class="border-0">Status</th>
                                            <th scope="col" class="border-0">Total</th>
                                            <th scope="col" class="border-0 text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentOrders as $order): 
                                            $badgeClass = 'bg-secondary';
                                            $statusLabel = ucfirst(str_replace('_', ' ', $order['order_status']));
                                            if ($order['order_status'] === 'delivered') {
                                                $badgeClass = 'bg-success';
                                            } elseif (in_array($order['order_status'], ['pending', 'confirmed', 'preparing'])) {
                                                $badgeClass = 'bg-warning text-dark';
                                            } elseif ($order['order_status'] === 'out_for_delivery') {
                                                $badgeClass = 'bg-primary-accent';
                                            } elseif (in_array($order['order_status'], ['cancelled', 'rejected'])) {
                                                $badgeClass = 'bg-danger';
                                            }
                                        ?>
                                            <tr>
                                                <td class="fw-bold text-dark">#<?= htmlspecialchars($order['order_number'] ?? $order['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-muted small"><?= date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                <td><span class="badge rounded-pill <?= $badgeClass; ?> extra-small"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                <td class="fw-bold text-dark">$<?= number_format((float)($order['total'] ?? $order['total_amount'] ?? $order['grand_total'] ?? 0.00), 2); ?></td>
                                                <td class="text-end">
                                                    <a href="order-details.php?id=<?= $order['id']; ?>" class="btn btn-sm btn-outline-pink extra-small py-1 px-2">
                                                        <span>View Details</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
