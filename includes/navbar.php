<?php
/**
 * FastBite - Main Navigation Component
 * STEP 30: FastBite Customer Experience, Reviews, Favorites & Promotions
 */

require_once __DIR__ . '/auth.php';

$currentPage = $currentPage ?? 'home';
$loggedInUser = currentUser();
$cartItemCount = 0;
$userFavoritesCount = 0;

if ($loggedInUser) {
    $userCart = dbGetCartItems((int)$loggedInUser['id']);
    foreach ($userCart as $ci) {
        $cartItemCount += (int)$ci['quantity'];
    }
    if (function_exists('dbGetUserFavoritesCount')) {
        $userFavoritesCount = dbGetUserFavoritesCount((int)$loggedInUser['id']);
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top main-navbar" aria-label="Main Navigation">
    <div class="container">
        <!-- Brand / Logo Area -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php" id="navbar-brand">
            <span class="brand-icon d-flex align-items-center justify-content-center">
                <i class="bi bi-fire text-white"></i>
            </span>
            <span class="brand-text">Fast<span class="text-primary-accent">Bite</span></span>
        </a>

        <!-- Mobile Menu Toggle Button -->
        <button class="navbar-toggler custom-toggler" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#navbarMainContent" 
                aria-controls="navbarMainContent" 
                aria-expanded="false" 
                aria-label="Toggle navigation"
                id="navbar-toggle-btn">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Collapse Content -->
        <div class="collapse navbar-collapse" id="navbarMainContent">
            <!-- Center Navigation Links -->
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 navbar-links-list">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'home') ? 'active' : ''; ?>" 
                       <?= ($currentPage === 'home') ? 'aria-current="page"' : ''; ?> 
                       href="index.php" 
                       id="nav-link-home">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'menu') ? 'active' : ''; ?>" 
                       <?= ($currentPage === 'menu') ? 'aria-current="page"' : ''; ?> 
                       href="menu.php" 
                       id="nav-link-menu">Menu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'about') ? 'active' : ''; ?>" 
                       <?= ($currentPage === 'about') ? 'aria-current="page"' : ''; ?> 
                       href="about.php" 
                       id="nav-link-about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'contact') ? 'active' : ''; ?>" 
                       <?= ($currentPage === 'contact') ? 'aria-current="page"' : ''; ?> 
                       href="contact.php" 
                       id="nav-link-contact">Contact</a>
                </li>
            </ul>

            <!-- Right Side Action Controls -->
            <div class="d-flex align-items-center gap-2 navbar-action-buttons">
                <!-- Search Button (UI Only) -->
                <a href="menu.php" class="btn btn-icon-nav" aria-label="Search Menu" id="nav-search-btn" title="Search Menu">
                    <i class="bi bi-search"></i>
                </a>

                <!-- Saved Favorites Button for Customers -->
                <?php if ($loggedInUser && $loggedInUser['role'] === 'customer'): ?>
                    <a href="favorites.php" 
                       class="btn btn-icon-nav position-relative <?= ($currentPage === 'favorites') ? 'active-nav-icon' : ''; ?>" 
                       aria-label="Favorites" 
                       id="nav-fav-btn" 
                       title="Saved Favorites">
                        <i class="bi bi-heart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge" 
                              id="nav-fav-count" 
                              style="<?= ($userFavoritesCount > 0) ? '' : 'display: none;'; ?>">
                            <?= ($userFavoritesCount > 99) ? '99+' : $userFavoritesCount; ?>
                            <span class="visually-hidden">saved favorites</span>
                        </span>
                    </a>
                <?php endif; ?>

                <!-- Notifications Button for Customers -->
                <?php if ($loggedInUser && $loggedInUser['role'] === 'customer'): ?>
                    <?php $unreadNotifCount = function_exists('dbGetUnreadNotificationCount') ? dbGetUnreadNotificationCount((int)$loggedInUser['id']) : 0; ?>
                    <a href="notifications.php" 
                       class="btn btn-icon-nav position-relative <?= ($currentPage === 'notifications') ? 'active-nav-icon' : ''; ?>" 
                       aria-label="Notifications" 
                       id="nav-notif-btn" 
                       title="Notifications">
                        <i class="bi bi-bell"></i>
                        <?php if ($unreadNotifCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge" id="nav-notif-count">
                                <?= ($unreadNotifCount > 99) ? '99+' : $unreadNotifCount; ?>
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <!-- Cart Button -->
                <a href="cart.php" 
                   class="btn btn-icon-nav position-relative <?= ($currentPage === 'cart') ? 'active-nav-icon' : ''; ?>" 
                   aria-label="Shopping Cart" 
                   id="nav-cart-btn" 
                   title="Shopping Cart">
                    <i class="bi bi-bag"></i>
                    <?php if ($cartItemCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge" id="nav-cart-count">
                            <?= $cartItemCount; ?>
                            <span class="visually-hidden">items in cart</span>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Authentication State Buttons -->
                <?php if ($loggedInUser): ?>
                    <?php if ($loggedInUser['role'] === 'admin'): ?>
                        <a href="admin/foods/index.php" 
                           class="btn btn-navbar-login ms-1 active-auth-btn d-inline-flex align-items-center gap-1" 
                           title="Admin Panel">
                            <i class="bi bi-speedometer2"></i>
                            <span>Admin</span>
                        </a>
                        <a href="logout.php" class="btn btn-sm btn-outline-danger ms-1" title="Logout">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    <?php else: ?>
                        <div class="dropdown">
                            <button class="btn btn-navbar-login ms-1 dropdown-toggle d-inline-flex align-items-center gap-1 <?= in_array($currentPage, ['dashboard', 'orders', 'profile', 'addresses', 'notifications', 'favorites']) ? 'active-auth-btn' : ''; ?>" 
                                    type="button" 
                                    id="navUserDropdown" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false">
                                <i class="bi bi-person-circle"></i>
                                <span class="d-none d-md-inline"><?= htmlspecialchars(explode(' ', $loggedInUser['name'])[0], ENT_QUOTES, 'UTF-8'); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" aria-labelledby="navUserDropdown">
                                <li>
                                    <div class="px-3 py-2 border-bottom">
                                        <span class="d-block fw-bold small text-dark"><?= htmlspecialchars($loggedInUser['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="d-block extra-small text-muted"><?= htmlspecialchars($loggedInUser['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </li>
                                <li><a class="dropdown-item small py-2 d-flex align-items-center gap-2" href="customer-dashboard.php"><i class="bi bi-grid-1x2 text-primary-accent"></i> Dashboard</a></li>
                                <li><a class="dropdown-item small py-2 d-flex align-items-center gap-2" href="my-orders.php"><i class="bi bi-bag text-primary-accent"></i> My Orders</a></li>
                                <li><a class="dropdown-item small py-2 d-flex align-items-center gap-2" href="favorites.php"><i class="bi bi-heart text-primary-accent"></i> Saved Favorites</a></li>
                                <li><a class="dropdown-item small py-2 d-flex align-items-center gap-2" href="addresses.php"><i class="bi bi-geo-alt text-primary-accent"></i> Saved Addresses</a></li>
                                <li><a class="dropdown-item small py-2 d-flex align-items-center gap-2" href="notifications.php"><i class="bi bi-bell text-primary-accent"></i> Notifications</a></li>
                                <li><a class="dropdown-item small py-2 d-flex align-items-center gap-2" href="support.php"><i class="bi bi-headset text-primary-accent"></i> Support &amp; Help</a></li>
                                <li><a class="dropdown-item small py-2 d-flex align-items-center gap-2" href="profile.php"><i class="bi bi-person-gear text-primary-accent"></i> Profile Settings</a></li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li><a class="dropdown-item small py-2 d-flex align-items-center gap-2 text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-navbar-login ms-1" id="nav-login-btn">Log In</a>
                    <a href="register.php" class="btn btn-navbar-signup ms-1 d-none d-sm-inline-flex" id="nav-signup-btn">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
