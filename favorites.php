<?php
/**
 * FastBite - Customer Saved Favorites & Wishlist
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

$currentPage = 'favorites';
$projectName = 'FastBite';
$userId = (int)$user['id'];

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Handle POST Actions (Remove, Add to Cart, Add All)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    $foodId = (int)($_POST['food_id'] ?? 0);
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf)) {
        $error = "Security token validation failed. Please refresh and try again.";
    } else {
        if ($action === 'remove' && $foodId > 0) {
            dbRemoveFavorite($userId, $foodId);
            header("Location: favorites.php?msg=" . urlencode("Item removed from your favorites."));
            exit;
        } elseif ($action === 'add_to_cart' && $foodId > 0) {
            $added = dbAddToCart($userId, $foodId, 1);
            if ($added) {
                header("Location: cart.php?msg=" . urlencode("Favorite item added to your cart!"));
                exit;
            } else {
                $error = "This item is currently sold out or unavailable.";
            }
        } elseif ($action === 'add_all_to_cart') {
            $favs = dbGetUserFavorites($userId);
            $addedCount = 0;
            foreach ($favs as $fav) {
                if ($fav['is_available']) {
                    dbAddToCart($userId, (int)$fav['id'], 1);
                    $addedCount++;
                }
            }
            if ($addedCount > 0) {
                header("Location: cart.php?msg=" . urlencode("{$addedCount} favorite items added to your cart!"));
                exit;
            } else {
                $error = "No available items to add to cart.";
            }
        }
    }
}

// Fetch user's saved favorites
$favorites = dbGetUserFavorites($userId);
$favoriteCount = count($favorites);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Saved Favorites - <?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="View and manage your favorite FastBite meals. Quick re-ordering and saved food wishlist.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body d-flex flex-column min-vh-100">
    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main id="main-content" class="flex-grow-1 py-4 py-lg-5">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb extra-small mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted"><i class="bi bi-house me-1"></i>Home</a></li>
                    <li class="breadcrumb-item"><a href="customer-dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-primary-accent fw-bold" aria-current="page">Saved Favorites</li>
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

            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <span class="dashboard-eyebrow d-inline-block">YOUR WISHLIST</span>
                    <h1 class="h2 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-heart-fill text-primary-accent"></i>
                        <span>Saved Favorites</span>
                        <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle fs-6 rounded-pill"><?= $favoriteCount; ?></span>
                    </h1>
                    <p class="text-muted mb-0">Your personally curated list of favorite FastBite meals for fast one-click ordering.</p>
                </div>

                <?php if ($favoriteCount > 0): ?>
                    <div class="d-flex align-items-center gap-2">
                        <form method="POST" action="favorites.php" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="add_all_to_cart">
                            <button type="submit" class="btn btn-auth-primary d-inline-flex align-items-center gap-2 shadow-sm">
                                <i class="bi bi-bag-plus"></i>
                                <span>Add All to Cart</span>
                            </button>
                        </form>
                        <a href="menu.php" class="btn btn-outline-pink d-inline-flex align-items-center gap-1">
                            <i class="bi bi-plus-lg"></i>
                            <span>Explore Menu</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($favorites)): ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm border p-4">
                    <div class="empty-cart-icon-wrap mb-3">
                        <i class="bi bi-heart text-muted" style="font-size: 4rem; opacity: 0.5;"></i>
                    </div>
                    <h2 class="h4 fw-bold text-dark mb-2">No Favorites Saved Yet</h2>
                    <p class="text-muted mb-4">Click the heart icon on any delicious meal to save it here for quick re-ordering.</p>
                    <a href="menu.php" class="btn btn-hero-primary d-inline-flex align-items-center gap-2">
                        <span>Explore Delicious Menu</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-3 g-md-4">
                    <?php foreach ($favorites as $food): 
                        $price = (float)$food['price'];
                        $originalPrice = (float)($food['original_price'] ?? $price);
                        $finalPrice = (float)($food['final_price'] ?? $price);
                        $discountPercent = (int)($food['discount_percent'] ?? 0);
                        $hasDiscount = !empty($food['has_discount']) && ($discountPercent > 0);
                        $isSoldOut = empty($food['is_available']);
                        $foodImage = function_exists('getFoodImage') ? getFoodImage($food['image'] ?: $food['name']) : 'assets/images/placeholders/food-placeholder.jpg';
                        $foodSlug = $food['slug'] ?? 'food-' . $food['id'];
                        $foodRating = (float)($food['rating'] ?? 4.8);
                        $ratingCount = (int)($food['rating_count'] ?? 0);
                    ?>
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                            <div class="menu-food-card card h-100 <?= $isSoldOut ? 'sold-out' : ''; ?>">
                                <div class="menu-food-image-wrap position-relative">
                                    <a href="food-details.php?food=<?= urlencode($foodSlug); ?>" class="d-block w-100 h-100">
                                        <img src="<?= htmlspecialchars($foodImage, ENT_QUOTES, 'UTF-8'); ?>" 
                                             alt="<?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                             class="menu-food-image" 
                                             loading="lazy">
                                    </a>
                                    
                                    <div class="menu-food-tag-overlay">
                                        <span class="menu-food-badge">
                                            <?= htmlspecialchars($food['category_name'] ?? 'Food', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>

                                    <form method="POST" action="favorites.php" class="position-absolute top-0 start-0 m-2" style="z-index: 5;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="food_id" value="<?= $food['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-light rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center text-danger border-0" 
                                                style="width: 34px; height: 34px;" 
                                                title="Remove from favorites">
                                            <i class="bi bi-heart-fill fs-6"></i>
                                        </button>
                                    </form>

                                    <?php if ($isSoldOut): ?>
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <span class="sold-out-badge shadow-sm">SOLD OUT</span>
                                        </div>
                                    <?php elseif ($hasDiscount): ?>
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <span class="discount-badge-ribbon shadow-sm"><?= $discountPercent; ?>% OFF</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="menu-food-body card-body d-flex flex-column">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                        <h2 class="menu-food-name h6 mb-0">
                                            <a href="food-details.php?food=<?= urlencode($foodSlug); ?>" class="text-decoration-none text-dark fw-bold">
                                                <?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </h2>
                                        <div class="menu-food-rating d-inline-flex align-items-center gap-1 extra-small" title="Rating: <?= number_format($foodRating, 1); ?> (<?= $ratingCount; ?> reviews)">
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <span class="fw-bold"><?= number_format($foodRating, 1); ?></span>
                                        </div>
                                    </div>
                                    <p class="menu-food-desc mb-3 flex-grow-1 text-muted small"><?= htmlspecialchars($food['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                    
                                    <div class="menu-food-footer d-flex align-items-center justify-content-between pt-2 border-top">
                                        <div class="menu-food-price-wrap">
                                            <?php if ($hasDiscount): ?>
                                                <div class="d-flex align-items-baseline gap-1">
                                                    <span class="menu-food-price fw-bold text-primary-accent">$<?= number_format($finalPrice, 2); ?></span>
                                                    <span class="text-muted text-decoration-line-through extra-small">$<?= number_format($originalPrice, 2); ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="menu-food-price fw-bold">$<?= number_format($finalPrice, 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($isSoldOut): ?>
                                            <button type="button" class="btn btn-menu-cart disabled d-inline-flex align-items-center gap-1" disabled>
                                                <i class="bi bi-x-circle"></i>
                                                <span>Sold Out</span>
                                            </button>
                                        <?php else: ?>
                                            <form method="POST" action="favorites.php" class="d-inline m-0 p-0">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                                <input type="hidden" name="action" value="add_to_cart">
                                                <input type="hidden" name="food_id" value="<?= $food['id']; ?>">
                                                <button type="submit" class="btn btn-menu-cart d-inline-flex align-items-center gap-1" title="Add to cart">
                                                    <i class="bi bi-bag-plus"></i>
                                                    <span>Add to Cart</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
