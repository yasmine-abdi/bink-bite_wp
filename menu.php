<?php
/**
 * FastBite - Dedicated Menu Page
 * STEP 30: Customer Experience, Reviews, Favorites & Promotions
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';

$currentPage = 'menu';
$projectName = 'FastBite';
$user = currentUser();
$userId = $user ? (int)$user['id'] : null;

// User favorite food IDs for fast lookup
$userFavoriteIds = $userId ? dbGetUserFavoriteIds($userId) : [];

// Handle Search & Sort Filters
$searchQuery = trim($_GET['search'] ?? '');
$currentSort = trim($_GET['sort'] ?? 'recommended');
$activeCategorySlug = trim($_GET['category'] ?? '');

$filterParams = [
    'search' => $searchQuery,
    'sort' => $currentSort
];
if (!empty($activeCategorySlug) && $activeCategorySlug !== 'all') {
    $filterParams['category_slug'] = $activeCategorySlug;
}

try {
    $dbCategories = dbGetAllCategoriesWithCounts(true);
    $dbFoods = dbGetAllFoodsWithPricing($filterParams);

    $groupedFoods = [];
    foreach ($dbFoods as $f) {
        $slug = $f['category_slug'];
        if (!isset($groupedFoods[$slug])) {
            $groupedFoods[$slug] = [];
        }
        $groupedFoods[$slug][] = $f;
    }

    $menuCategories = [];
    foreach ($dbCategories as $cat) {
        $cSlug = $cat['slug'];
        if (isset($groupedFoods[$cSlug]) || empty($searchQuery)) {
            $menuCategories[] = [
                'id' => $cSlug,
                'name' => $cat['name'],
                'icon' => $cat['icon'],
                'description' => $cat['description'],
                'has_discount' => $cat['has_discount'],
                'discount_percent' => $cat['discount_percent'],
                'foods' => $groupedFoods[$cSlug] ?? []
            ];
        }
    }
} catch (Exception $e) {
    $menuCategories = [];
    $dbFoods = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - <?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?> - Fresh &amp; Delicious Fast Food</title>
    <meta name="description" content="Explore FastBite's complete fast food menu. Delicious pizzas, juicy burgers, tasty shawarma, drinks, coffee, chicken, pasta, sides, and desserts.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main id="main-content">
        <header class="menu-hero-section py-5 text-center">
            <div class="container py-2">
                <span class="menu-eyebrow d-inline-block">OUR COMPLETE CATALOG</span>
                <h1 class="menu-title fw-bold mb-2">Choose Your Favorite Meal</h1>
                <p class="menu-desc text-muted mb-0">Explore our delicious selection of freshly prepared foods, chef specials, and hot promos.</p>
            </div>
        </header>

        <section class="menu-nav-section py-3 sticky-top menu-sticky-nav" aria-label="Menu Filters and Search">
            <div class="container">
                <form method="GET" action="menu.php" class="row g-3 align-items-center justify-content-between mb-2" id="menuFilterForm">
                    <div class="col-12 col-md-5 col-lg-4">
                        <div class="menu-search-wrap position-relative">
                            <i class="bi bi-search menu-search-icon"></i>
                            <input type="text" 
                                   name="search"
                                   value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>"
                                   class="form-control menu-search-input" 
                                   id="menuSearchInput" 
                                   placeholder="Search foods, pizza, burgers..." 
                                   aria-label="Search food menu">
                            <?php if (!empty($searchQuery)): ?>
                                <a href="menu.php?sort=<?= urlencode($currentSort); ?>" class="position-absolute end-0 top-50 translate-middle-y me-3 text-muted text-decoration-none" title="Clear search">
                                    <i class="bi bi-x-circle-fill"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-12 col-md-4 col-lg-3">
                        <div class="d-flex align-items-center gap-2">
                            <label for="sortSelect" class="small fw-bold text-muted text-nowrap mb-0 d-none d-sm-inline"><i class="bi bi-sort-down me-1"></i>Sort:</label>
                            <select name="sort" id="sortSelect" class="form-select form-select-sm rounded-pill border shadow-none" onchange="document.getElementById('menuFilterForm').submit();">
                                <option value="recommended" <?= ($currentSort === 'recommended') ? 'selected' : ''; ?>>Recommended</option>
                                <option value="popular" <?= ($currentSort === 'popular') ? 'selected' : ''; ?>>Most Popular</option>
                                <option value="rating" <?= ($currentSort === 'rating') ? 'selected' : ''; ?>>Highest Rated</option>
                                <option value="price_asc" <?= ($currentSort === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_desc" <?= ($currentSort === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="newest" <?= ($currentSort === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="menu-category-pills d-flex align-items-center gap-2 overflow-auto py-1">
                            <a href="menu.php?sort=<?= urlencode($currentSort); ?><?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn btn-menu-pill <?= empty($activeCategorySlug) || $activeCategorySlug === 'all' ? 'active' : ''; ?>">
                                <span>All Items</span>
                            </a>
                            <?php foreach ($dbCategories as $cat): ?>
                                <a href="menu.php?category=<?= urlencode($cat['slug']); ?>&sort=<?= urlencode($currentSort); ?><?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                                   class="btn btn-menu-pill <?= ($activeCategorySlug === $cat['slug']) ? 'active' : ''; ?>" 
                                   data-category="<?= htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="<?= htmlspecialchars($cat['icon'], ENT_QUOTES, 'UTF-8'); ?> me-1"></i>
                                    <span><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if (!empty($cat['has_discount'])): ?>
                                        <span class="badge bg-danger ms-1 extra-small"><?= $cat['discount_percent']; ?>% OFF</span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <section class="menu-listing-section py-5" aria-label="Food Listing">
            <div class="container">
                <?php if (empty($dbFoods)): ?>
                    <div class="text-center py-5 bg-white rounded-4 shadow-sm border p-4" id="menuNoResults">
                        <div class="menu-no-results-icon mb-3">
                            <i class="bi bi-search text-muted display-4"></i>
                        </div>
                        <h3 class="fw-bold mb-2">No menu items found</h3>
                        <p class="text-muted mb-4">We couldn't find any dishes matching your criteria. Try searching for "pizza", "burger", or browse all categories.</p>
                        <a href="menu.php" class="btn btn-hero-primary d-inline-flex align-items-center gap-2">
                            <span>Reset Filters</span>
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($menuCategories as $cat): ?>
                        <?php if (!empty($cat['foods'])): ?>
                            <div class="menu-category-group mb-5" id="cat-<?= htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>" data-category-group="<?= htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="menu-category-header mb-4 pb-2 border-bottom d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="menu-cat-icon-badge">
                                            <i class="<?= htmlspecialchars($cat['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                                        </span>
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <h2 class="menu-category-title mb-0 fw-bold"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                                <?php if (!empty($cat['has_discount'])): ?>
                                                    <span class="badge bg-danger shadow-sm"><?= $cat['discount_percent']; ?>% PROMO</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="menu-category-subtitle text-muted mb-0 small"><?= htmlspecialchars($cat['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-muted border px-3 py-2 rounded-pill"><?= count($cat['foods']); ?> items</span>
                                </div>

                                <div class="row g-3 g-md-4 menu-food-grid">
                                    <?php foreach ($cat['foods'] as $food): ?>
                                        <?php
                                            $foodId = (int)$food['id'];
                                            $price = (float)$food['price'];
                                            $originalPrice = (float)($food['original_price'] ?? $price);
                                            $finalPrice = (float)($food['final_price'] ?? $price);
                                            $discountPercent = (int)($food['discount_percent'] ?? 0);
                                            $hasDiscount = !empty($food['has_discount']) && ($discountPercent > 0);
                                            $isSoldOut = empty($food['is_available']);
                                            $foodImage = function_exists('getFoodImage') ? getFoodImage($food['image'] ?: $food['name']) : 'assets/images/placeholders/food-placeholder.jpg';
                                            $foodSlug = $food['slug'] ?? 'food-' . $foodId;
                                            $foodCategory = $food['category_name'] ?? $cat['name'];
                                            $foodRating = (float)($food['rating'] ?? 4.8);
                                            $ratingCount = (int)($food['rating_count'] ?? 0);
                                            $isFav = in_array($foodId, $userFavoriteIds);
                                        ?>
                                        <div class="col-12 col-sm-6 col-lg-3 menu-food-col" 
                                             data-category="<?= htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                             data-food-name="<?= strtolower(htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8')); ?>" 
                                             data-food-type="<?= strtolower(htmlspecialchars($foodCategory, ENT_QUOTES, 'UTF-8')); ?>">
                                            <div class="menu-food-card card h-100 <?= $isSoldOut ? 'sold-out' : ''; ?>" 
                                                 id="<?= htmlspecialchars($foodSlug, ENT_QUOTES, 'UTF-8'); ?>"
                                                 data-category="<?= htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                
                                                <div class="menu-food-image-wrap position-relative">
                                                    <a href="food-details.php?food=<?= urlencode($foodSlug); ?>" class="d-block w-100 h-100">
                                                        <img src="<?= htmlspecialchars($foodImage, ENT_QUOTES, 'UTF-8'); ?>" 
                                                             alt="<?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                             class="menu-food-image" 
                                                             loading="lazy"
                                                             decoding="async">
                                                    </a>
                                                    
                                                    <div class="menu-food-tag-overlay">
                                                        <span class="menu-food-badge">
                                                            <?= htmlspecialchars($foodCategory, ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                    </div>

                                                    <?php if ($userId): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-light rounded-circle shadow-sm p-0 position-absolute top-0 start-0 m-2 d-flex align-items-center justify-content-center border-0 js-toggle-favorite <?= $isFav ? 'text-danger' : 'text-muted'; ?>" 
                                                                style="width: 34px; height: 34px; z-index: 5;"
                                                                data-food-id="<?= $foodId; ?>"
                                                                data-csrf="<?= generateCsrfToken(); ?>"
                                                                title="<?= $isFav ? 'Remove from favorites' : 'Add to favorites'; ?>">
                                                            <i class="bi <?= $isFav ? 'bi-heart-fill' : 'bi-heart'; ?> fs-6"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="login.php?return_url=<?= urlencode('menu.php'); ?>" 
                                                           class="btn btn-sm btn-light rounded-circle shadow-sm p-0 position-absolute top-0 start-0 m-2 d-flex align-items-center justify-content-center border-0 text-muted" 
                                                           style="width: 34px; height: 34px; z-index: 5;"
                                                           title="Login to add to favorites">
                                                            <i class="bi bi-heart fs-6"></i>
                                                        </a>
                                                    <?php endif; ?>

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
                                                        <h3 class="menu-food-name mb-0">
                                                            <a href="food-details.php?food=<?= urlencode($foodSlug); ?>" class="text-decoration-none text-dark">
                                                                <?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </a>
                                                        </h3>
                                                        <div class="menu-food-rating d-inline-flex align-items-center gap-1 extra-small" title="Rating: <?= number_format($foodRating, 1); ?> / 5 (<?= $ratingCount; ?> reviews)">
                                                            <i class="bi bi-star-fill text-warning"></i>
                                                            <span class="fw-bold"><?= number_format($foodRating, 1); ?></span>
                                                        </div>
                                                    </div>
                                                    <p class="menu-food-desc mb-3 flex-grow-1 text-muted small"><?= htmlspecialchars($food['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                    
                                                    <div class="menu-food-footer d-flex align-items-center justify-content-between pt-2 border-top">
                                                        <div class="menu-food-price-wrap">
                                                            <span class="menu-food-price-label d-block text-muted">Price</span>
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
                                                            <button type="button" 
                                                                    class="btn btn-menu-cart disabled d-inline-flex align-items-center gap-1" 
                                                                    disabled>
                                                                <i class="bi bi-x-circle"></i>
                                                                <span>Sold Out</span>
                                                            </button>
                                                        <?php else: ?>
                                                            <form method="POST" action="cart.php" class="d-inline m-0 p-0">
            <?= csrfField(); ?>
                                                                <input type="hidden" name="action" value="add">
                                                                <input type="hidden" name="food_id" value="<?= $foodId; ?>">
                                                                <input type="hidden" name="quantity" value="1">
                                                                <button type="submit" 
                                                                        class="btn btn-menu-cart d-inline-flex align-items-center gap-1 js-add-to-cart" 
                                                                        data-food-id="<?= $foodId; ?>"
                                                                        data-food-name="<?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                        data-food-price="<?= number_format($finalPrice, 2, '.', ''); ?>"
                                                                        data-food-image="<?= htmlspecialchars($foodImage, ENT_QUOTES, 'UTF-8'); ?>"
                                                                        aria-label="Add <?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?> to cart" 
                                                                        title="Add to cart">
                                                                    <i class="bi bi-bag-plus"></i>
                                                                    <span>Add</span>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.js-toggle-favorite').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const foodId = this.getAttribute('data-food-id');
                const csrf = this.getAttribute('data-csrf');
                const icon = this.querySelector('i');
                const self = this;

                const formData = new FormData();
                formData.append('food_id', foodId);
                formData.append('csrf_token', csrf);

                fetch('toggle-favorite.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (data.is_favorite) {
                            icon.classList.remove('bi-heart');
                            icon.classList.add('bi-heart-fill');
                            self.classList.remove('text-muted');
                            self.classList.add('text-danger');
                        } else {
                            icon.classList.remove('bi-heart-fill');
                            icon.classList.add('bi-heart');
                            self.classList.remove('text-danger');
                            self.classList.add('text-muted');
                        }
                        const navBadge = document.getElementById('nav-fav-count');
                        if (navBadge) {
                            navBadge.textContent = data.favorites_count;
                            navBadge.style.display = (data.favorites_count > 0) ? 'inline-block' : 'none';
                        }
                    } else if (data.requires_login) {
                        window.location.href = 'login.php?return_url=' + encodeURIComponent(window.location.pathname + window.location.search);
                    }
                })
                .catch(err => console.error('Favorites toggle error:', err));
            });
        });
    });
    </script>
</body>
</html>
