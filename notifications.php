<?php
/**
 * FastBite - Customer Notifications & Communication Center
 * STEP 28: FastBite Notifications & Communication Center
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';

requireLogin('login.php');

$user = currentUser();
if (!$user) {
    header("Location: login.php");
    exit;
}

$currentPage = 'notifications';
$projectName = 'FastBite';
$userId = (int)$user['id'];
$successMsg = $_GET['msg'] ?? '';
$errorMsg = $_GET['error'] ?? '';

// Handle POST State-Changing Actions with CSRF Protection
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = "Security check failed. Please refresh and try again.";
    } else {
        $action = $_POST['action'] ?? '';
        $notifId = (int)($_POST['notif_id'] ?? 0);

        if ($action === 'mark_read' && $notifId > 0) {
            dbMarkNotificationRead($notifId, $userId);
            header("Location: notifications.php?msg=" . urlencode("Notification marked as read."));
            exit;
        } elseif ($action === 'mark_unread' && $notifId > 0) {
            dbMarkNotificationUnread($notifId, $userId);
            header("Location: notifications.php?msg=" . urlencode("Notification marked as unread."));
            exit;
        } elseif ($action === 'delete_notif' && $notifId > 0) {
            dbDeleteNotification($notifId, $userId);
            header("Location: notifications.php?msg=" . urlencode("Notification removed."));
            exit;
        } elseif ($action === 'mark_all_read') {
            dbMarkAllNotificationsRead($userId);
            header("Location: notifications.php?msg=" . urlencode("All notifications marked as read."));
            exit;
        } elseif ($action === 'clear_all') {
            dbClearAllNotifications($userId);
            header("Location: notifications.php?msg=" . urlencode("Notification inbox cleared."));
            exit;
        }
    }
}

// Filtering & Pagination
$categoryFilter = trim($_GET['category'] ?? 'all');
$searchQuery = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$notifData = dbGetCustomerNotificationsFiltered($userId, [
    'category' => $categoryFilter,
    'search' => $searchQuery
], $limit, $offset);

$rawNotifs = $notifData['notifications'];
$totalCount = $notifData['total'];
$unreadCount = $notifData['unread_count'];
$totalPages = ceil($totalCount / $limit);

$avatarImg = function_exists('getAvatarImage') ? getAvatarImage($user['avatar'] ?? 'avatar-placeholder.jpg') : 'assets/images/placeholders/avatar-placeholder.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?> Fast Food</title>
    <meta name="description" content="View your order updates, special discount announcements, and account messages.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">

    <!-- Navbar -->
    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="dashboard-main py-4 py-md-5" id="main-content">
        <div class="container">

            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb extra-small mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item"><a href="customer-dashboard.php" class="text-muted text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Notifications</li>
                </ol>
            </nav>

            <!-- Alerts -->
            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                    <div class="small"><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <div class="small"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
                <div>
                    <span class="text-primary-accent fw-bold extra-small text-uppercase tracking-wider d-block mb-1">NOTIFICATIONS</span>
                    <h1 class="h3 fw-bold text-dark mb-1">Notifications</h1>
                    <p class="text-muted small mb-0">Stay informed with real-time order tracking updates, special offers, and account alerts.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="customer-dashboard.php" class="btn btn-light btn-sm border d-flex align-items-center gap-1">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="my-orders.php" class="btn btn-outline-pink btn-sm d-flex align-items-center gap-1">
                        <i class="bi bi-bag"></i>
                        <span>My Orders</span>
                    </a>
                    <?php if ($unreadCount > 0): ?>
                        <form method="POST" action="notifications.php" class="d-inline">
                            <?= csrfField(); ?>
                            <input type="hidden" name="action" value="mark_all_read">
                            <button type="submit" class="btn btn-primary-accent btn-sm d-flex align-items-center gap-1">
                                <i class="bi bi-check2-all"></i>
                                <span>Mark All as Read</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Two-Column Layout -->
            <div class="row g-4">
                
                <!-- Left Column Navigation (Matching Profile/Dashboard Style) -->
                <aside class="col-12 col-lg-4 col-xl-3" aria-label="Account Navigation">
                    <div class="dashboard-sidebar card p-3 p-md-4 border-0 bg-white shadow-sm sticky-lg-top" style="top: 85px; z-index: 10;">
                        <!-- User Mini Card -->
                        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                            <img src="<?= htmlspecialchars($avatarImg, ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="Avatar" 
                                 class="rounded-circle border"
                                 style="width: 48px; height: 48px; object-fit: cover;">
                            <div class="text-truncate">
                                <span class="d-block fw-bold text-dark small text-truncate"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="d-block extra-small text-muted text-truncate"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>

                        <!-- Sidebar Nav Links -->
                        <nav class="dashboard-sidebar-nav d-flex flex-column gap-1">
                            <a href="customer-dashboard.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-grid-1x2"></i>
                                <span>Dashboard</span>
                            </a>
                            <a href="my-orders.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-bag"></i>
                                <span>My Orders</span>
                            </a>
                            <a href="addresses.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-geo-alt"></i>
                                <span>Saved Addresses</span>
                            </a>
                            <a href="profile.php" class="dashboard-nav-link text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-person"></i>
                                <span>Profile</span>
                            </a>
                            <a href="notifications.php" class="dashboard-nav-link active text-decoration-none d-flex align-items-center justify-content-between gap-2" aria-current="page">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-bell"></i>
                                    <span>Notifications</span>
                                </div>
                                <?php if ($unreadCount > 0): ?>
                                    <span class="badge bg-danger rounded-pill extra-small"><?= $unreadCount; ?></span>
                                <?php endif; ?>
                            </a>

                            <div class="sidebar-nav-divider my-2 pt-2 border-top"></div>

                            <a href="logout.php" class="dashboard-nav-link text-danger text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Logout</span>
                            </a>
                        </nav>
                    </div>
                </aside>

                <!-- Right Column Content Panel -->
                <div class="col-12 col-lg-8 col-xl-9 d-flex flex-column gap-3">

                    <!-- Filter Toolbar Card -->
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            
                            <!-- Category Pills Filter (Scrollable on Mobile) -->
                            <div class="notif-filter-pills d-flex align-items-center gap-1 overflow-x-auto pb-1 pb-md-0">
                                <a href="notifications.php?category=all<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                                   class="btn btn-sm <?= ($categoryFilter === 'all') ? 'btn-primary-accent' : 'btn-light border text-muted'; ?> rounded-pill px-3 extra-small text-nowrap">
                                    All (<?= $totalCount; ?>)
                                </a>
                                <a href="notifications.php?category=unread<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                                   class="btn btn-sm <?= ($categoryFilter === 'unread') ? 'btn-primary-accent' : 'btn-light border text-muted'; ?> rounded-pill px-3 extra-small text-nowrap">
                                    Unread (<?= $unreadCount; ?>)
                                </a>
                                <a href="notifications.php?category=orders<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                                   class="btn btn-sm <?= ($categoryFilter === 'orders') ? 'btn-primary-accent' : 'btn-light border text-muted'; ?> rounded-pill px-3 extra-small text-nowrap">
                                    Order Updates
                                </a>
                                <a href="notifications.php?category=account<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                                   class="btn btn-sm <?= ($categoryFilter === 'account') ? 'btn-primary-accent' : 'btn-light border text-muted'; ?> rounded-pill px-3 extra-small text-nowrap">
                                    Account
                                </a>
                                <a href="notifications.php?category=promotions<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                                   class="btn btn-sm <?= ($categoryFilter === 'promotions') ? 'btn-primary-accent' : 'btn-light border text-muted'; ?> rounded-pill px-3 extra-small text-nowrap">
                                    Promotions
                                </a>
                                <a href="notifications.php?category=system<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                                   class="btn btn-sm <?= ($categoryFilter === 'system') ? 'btn-primary-accent' : 'btn-light border text-muted'; ?> rounded-pill px-3 extra-small text-nowrap">
                                    System
                                </a>
                            </div>

                            <!-- Search Form -->
                            <form method="GET" action="notifications.php" class="d-flex gap-1 flex-shrink-0" style="min-width: 200px;">
                                <input type="hidden" name="category" value="<?= htmlspecialchars($categoryFilter, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="search" class="form-control border-end-0" placeholder="Search..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-light border border-start-0 text-muted"><i class="bi bi-search"></i></button>
                                </div>
                                <?php if (!empty($searchQuery)): ?>
                                    <a href="notifications.php?category=<?= htmlspecialchars($categoryFilter, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-light border text-danger" title="Clear Search"><i class="bi bi-x"></i></a>
                                <?php endif; ?>
                            </form>

                        </div>
                    </div>

                    <!-- Notification Cards Container -->
                    <div class="d-flex flex-column gap-3">
                        
                        <?php if (empty($rawNotifs)): ?>
                            <!-- Empty State Card -->
                            <div class="card border-0 shadow-sm rounded-4 bg-white p-5 text-center my-3">
                                <div class="mb-3">
                                    <div class="stat-icon-wrap bg-pink-soft text-primary-accent rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px; font-size: 1.8rem;">
                                        <i class="bi bi-bell-slash"></i>
                                    </div>
                                </div>
                                <h4 class="fw-bold text-dark mb-1">No Notifications</h4>
                                <p class="text-muted small mb-4">You're all caught up! When you place orders or receive promotions, updates will appear right here.</p>
                                <div>
                                    <a href="menu.php" class="btn btn-primary-accent btn-sm px-4">Explore Our Menu</a>
                                </div>
                            </div>
                        <?php else: ?>

                            <?php foreach ($rawNotifs as $n): ?>
                                <?php
                                $isRead = (bool)$n['is_read'];
                                $type = $n['type'] ?? 'order';
                                
                                // Dynamic iconography and badges
                                $icon = 'bi-bell-fill';
                                $iconBg = 'bg-pink-soft text-primary-accent';
                                $badgeClass = 'bg-light text-secondary border';
                                $badgeLabel = ucfirst($type);
                                $actionLabel = 'View Details';

                                if ($type === 'order') {
                                    $badgeClass = 'bg-primary-subtle text-primary';
                                    $badgeLabel = 'Order Update';
                                    if (stripos($n['title'], 'delivered') !== false) {
                                        $icon = 'bi-check-circle-fill';
                                        $iconBg = 'bg-success-subtle text-success';
                                        $actionLabel = 'View Order';
                                    } elseif (stripos($n['title'], 'way') !== false || stripos($n['title'], 'delivery') !== false) {
                                        $icon = 'bi-truck';
                                        $iconBg = 'bg-info-subtle text-info';
                                        $actionLabel = 'Track Order';
                                    } elseif (stripos($n['title'], 'prepared') !== false || stripos($n['title'], 'preparing') !== false) {
                                        $icon = 'bi-egg-fried';
                                        $iconBg = 'bg-warning-subtle text-warning-emphasis';
                                        $actionLabel = 'Track Order';
                                    } elseif (stripos($n['title'], 'cancelled') !== false) {
                                        $icon = 'bi-x-circle-fill';
                                        $iconBg = 'bg-danger-subtle text-danger';
                                        $actionLabel = 'View Order';
                                    } else {
                                        $icon = 'bi-bag-check-fill';
                                        $iconBg = 'bg-primary-subtle text-primary';
                                        $actionLabel = 'View Order';
                                    }
                                } elseif ($type === 'promo' || $type === 'promotion') {
                                    $icon = 'bi-tag-fill';
                                    $iconBg = 'bg-pink-soft text-primary-accent';
                                    $badgeClass = 'bg-pink-soft text-primary-accent border border-pink-subtle';
                                    $badgeLabel = 'Special Offer';
                                    $actionLabel = 'View Menu';
                                } elseif ($type === 'account') {
                                    $icon = 'bi-person-check-fill';
                                    $iconBg = 'bg-success-subtle text-success';
                                    $badgeClass = 'bg-success-subtle text-success';
                                    $badgeLabel = 'Account Alert';
                                    $actionLabel = 'View Profile';
                                } elseif ($type === 'support') {
                                    $icon = 'bi-headset';
                                    $iconBg = 'bg-info-subtle text-info';
                                    $badgeClass = 'bg-info-subtle text-info';
                                    $badgeLabel = 'Customer Support';
                                    $actionLabel = 'View Support';
                                }
                                ?>
                                <div class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4 transition-all <?= !$isRead ? 'border-start border-4 border-primary-accent bg-pink-soft-light' : ''; ?>">
                                    <div class="d-flex align-items-start gap-3">
                                        
                                        <!-- Icon Circle -->
                                        <div class="stat-icon-wrap <?= $iconBg; ?> rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.25rem;">
                                            <i class="bi <?= $icon; ?>"></i>
                                        </div>

                                        <!-- Content Body -->
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge <?= $badgeClass; ?> extra-small px-2 py-1"><?= $badgeLabel; ?></span>
                                                    <?php if (!$isRead): ?>
                                                        <span class="badge bg-danger rounded-pill extra-small" style="font-size: 0.65rem;">New</span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="extra-small text-muted d-flex align-items-center gap-1">
                                                    <i class="bi bi-clock"></i>
                                                    <?= date('M j, Y • g:i A', strtotime($n['created_at'])); ?>
                                                </span>
                                            </div>

                                            <h5 class="h6 <?= !$isRead ? 'fw-bold text-dark' : 'fw-semibold text-secondary'; ?> mb-1">
                                                <?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8'); ?>
                                            </h5>
                                            
                                            <p class="small text-muted mb-3 leading-relaxed">
                                                <?= htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8'); ?>
                                            </p>

                                            <!-- Bottom Actions Row -->
                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top">
                                                <div class="d-flex gap-2">
                                                    <?php if (!empty($n['link'])): ?>
                                                        <a href="<?= htmlspecialchars($n['link'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-pink btn-sm py-1 px-3 extra-small fw-semibold d-inline-flex align-items-center gap-1">
                                                            <span><?= $actionLabel; ?></span>
                                                            <i class="bi bi-arrow-right"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="d-flex align-items-center gap-2">
                                                    <!-- Toggle Read/Unread Form -->
                                                    <form method="POST" action="notifications.php" class="d-inline">
                                                        <?= csrfField(); ?>
                                                        <input type="hidden" name="notif_id" value="<?= $n['id']; ?>">
                                                        <?php if (!$isRead): ?>
                                                            <input type="hidden" name="action" value="mark_read">
                                                            <button type="submit" class="btn btn-light btn-sm py-1 px-2 border text-muted extra-small" title="Mark as Read">
                                                                <i class="bi bi-check2"></i> <span class="d-none d-sm-inline">Mark as Read</span>
                                                            </button>
                                                        <?php else: ?>
                                                            <input type="hidden" name="action" value="mark_unread">
                                                            <button type="submit" class="btn btn-light btn-sm py-1 px-2 border text-muted extra-small" title="Mark as Unread">
                                                                <i class="bi bi-envelope"></i> <span class="d-none d-sm-inline">Mark as Unread</span>
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>

                                                    <!-- Delete Form -->
                                                    <form method="POST" action="notifications.php" class="d-inline" onsubmit="return confirm('Remove this notification?');">
                                                        <?= csrfField(); ?>
                                                        <input type="hidden" name="action" value="delete_notif">
                                                        <input type="hidden" name="notif_id" value="<?= $n['id']; ?>">
                                                        <button type="submit" class="btn btn-light btn-sm py-1 px-2 border text-danger extra-small" title="Delete Notification">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                        </div>

                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <div class="card border-0 shadow-sm rounded-4 bg-white py-3 px-4 d-flex flex-row justify-content-between align-items-center mt-2">
                                    <span class="extra-small text-muted">Showing page <?= $page; ?> of <?= $totalPages; ?></span>
                                    <nav aria-label="Notifications pagination">
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                            </li>
                                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                                <li class="page-item <?= ($p === $page) ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])); ?>"><?= $p; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>
    </main>

    <style>
    .bg-pink-soft-light {
        background-color: #fff8fb !important;
    }
    .notif-filter-pills::-webkit-scrollbar {
        height: 4px;
    }
    .notif-filter-pills::-webkit-scrollbar-thumb {
        background-color: #e9ecef;
        border-radius: 4px;
    }
    </style>

    <!-- Footer -->
    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
