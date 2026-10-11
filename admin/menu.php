<?php
header("Location: foods/index.php");
exit;


$pageTitle = 'Menu Management';
$activeMenu = 'menu';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Demo & Backend-Ready Food Catalog
$adminFoods = [
    [
        'id' => 1,
        'name' => 'Chicken Supreme Pizza',
        'slug' => 'chicken-supreme-pizza',
        'category_id' => 'pizza',
        'category_name' => 'Pizza',
        'description' => 'Tender seasoned chicken breast, red bell peppers, onions, and melted mozzarella cheese on freshly baked golden crust.',
        'price' => 10.00,
        'discount_percent' => 20,
        'discount_start' => '2026-08-25T18:00',
        'discount_end' => '2026-08-28T23:59',
        'availability' => 'available', // available | sold_out | hidden
        'status' => 'active',          // active | inactive
        'is_featured' => 1,
        'is_popular' => 1,
        'image' => 'chicken-supreme-pizza.jpg'
    ],
    [
        'id' => 2,
        'name' => 'Classic Beef Burger',
        'slug' => 'classic-beef-burger',
        'category_id' => 'burgers',
        'category_name' => 'Burgers',
        'description' => 'Juicy handcrafted grilled beef patty with crisp lettuce, ripe tomatoes, pickles, and signature house burger sauce.',
        'price' => 7.50,
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'availability' => 'available',
        'status' => 'active',
        'is_featured' => 1,
        'is_popular' => 0,
        'image' => 'classic-beef-burger.jpg'
    ],
    [
        'id' => 3,
        'name' => 'Double Beef Burger',
        'slug' => 'double-beef-burger',
        'category_id' => 'burgers',
        'category_name' => 'Burgers',
        'description' => 'Two juicy seasoned beef patties layered with melted American cheese, caramelized onions, and relish.',
        'price' => 9.00,
        'discount_percent' => 10,
        'discount_start' => '2026-08-25T12:00',
        'discount_end' => '2026-08-30T23:59',
        'availability' => 'available',
        'status' => 'active',
        'is_featured' => 0,
        'is_popular' => 1,
        'image' => 'double-beef-burger.jpg'
    ],
    [
        'id' => 4,
        'name' => 'Classic Pepperoni Pizza',
        'slug' => 'classic-pepperoni-pizza',
        'category_id' => 'pizza',
        'category_name' => 'Pizza',
        'description' => 'Generous layers of spicy beef pepperoni over melted mozzarella, Italian herbs, and rich tomato sauce.',
        'price' => 11.00,
        'discount_percent' => 10,
        'discount_start' => '2026-08-25T10:00',
        'discount_end' => '2026-08-29T20:00',
        'availability' => 'available',
        'status' => 'active',
        'is_featured' => 0,
        'is_popular' => 1,
        'image' => 'classic-pepperoni-pizza.jpg'
    ],
    [
        'id' => 5,
        'name' => 'Chicken Shawarma',
        'slug' => 'chicken-shawarma',
        'category_id' => 'shawarma',
        'category_name' => 'Shawarma',
        'description' => 'Tender spiced grilled chicken wrapped in toasted pita with crisp vegetables, pickles, and creamy garlic tahini.',
        'price' => 4.50,
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'availability' => 'available',
        'status' => 'active',
        'is_featured' => 1,
        'is_popular' => 1,
        'image' => 'chicken-shawarma.jpg'
    ],
    [
        'id' => 6,
        'name' => 'Crispy Chicken Bucket',
        'slug' => 'crispy-chicken-bucket',
        'category_id' => 'chicken',
        'category_name' => 'Chicken',
        'description' => 'Signature golden crispy fried chicken pieces seasoned with our 11-spice secret herb blend.',
        'price' => 11.00,
        'discount_percent' => 15,
        'discount_start' => '2026-08-25T08:00',
        'discount_end' => '2026-08-28T22:00',
        'availability' => 'sold_out',
        'status' => 'active',
        'is_featured' => 1,
        'is_popular' => 1,
        'image' => 'crispy-chicken-bucket.jpg'
    ],
    [
        'id' => 7,
        'name' => 'Fresh Mango Juice',
        'slug' => 'fresh-mango-juice',
        'category_id' => 'drinks',
        'category_name' => 'Drinks',
        'description' => 'Pure chilled tropical mango juice freshly squeezed with zero artificial additives.',
        'price' => 3.50,
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'availability' => 'available',
        'status' => 'active',
        'is_featured' => 1,
        'is_popular' => 0,
        'image' => 'fresh-mango-juice.jpg'
    ],
    [
        'id' => 8,
        'name' => 'Fresh Strawberry Juice',
        'slug' => 'fresh-strawberry-juice',
        'category_id' => 'drinks',
        'category_name' => 'Drinks',
        'description' => 'Sweet, refreshing ripe strawberry juice served with ice cubes and lemon zest.',
        'price' => 3.50,
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'availability' => 'available',
        'status' => 'active',
        'is_featured' => 0,
        'is_popular' => 1,
        'image' => 'fresh-strawberry-juice.jpg'
    ],
    [
        'id' => 9,
        'name' => 'Cappuccino',
        'slug' => 'cappuccino',
        'category_id' => 'coffee',
        'category_name' => 'Coffee',
        'description' => 'Rich artisan espresso topped with velvety steamed whole milk foam and dusted with cocoa.',
        'price' => 3.50,
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'availability' => 'available',
        'status' => 'active',
        'is_featured' => 1,
        'is_popular' => 0,
        'image' => 'cappuccino.jpg'
    ],
    [
        'id' => 10,
        'name' => 'Iced Coffee',
        'slug' => 'iced-coffee',
        'category_id' => 'coffee',
        'category_name' => 'Coffee',
        'description' => 'Smooth cold-brewed espresso poured over ice and balanced with creamy whole milk.',
        'price' => 4.00,
        'discount_percent' => 15,
        'discount_start' => '2026-08-25T09:00',
        'discount_end' => '2026-08-29T18:00',
        'availability' => 'available',
        'status' => 'active',
        'is_featured' => 0,
        'is_popular' => 1,
        'image' => 'iced-coffee.jpg'
    ],
    [
        'id' => 11,
        'name' => 'Creamy Chicken Penne',
        'slug' => 'creamy-chicken-penne',
        'category_id' => 'pasta',
        'category_name' => 'Pasta',
        'description' => 'Al dente penne pasta tossed in rich garlic parmesan Alfredo cream sauce with sliced grilled chicken.',
        'price' => 8.50,
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'availability' => 'available',
        'status' => 'active',
        'is_featured' => 1,
        'is_popular' => 1,
        'image' => 'creamy-chicken-penne.jpg'
    ],
    [
        'id' => 12,
        'name' => 'Loaded Cheese Fries',
        'slug' => 'loaded-cheese-fries',
        'category_id' => 'fries',
        'category_name' => 'Fries & Sides',
        'description' => 'Crispy golden french fries smothered in hot melted cheddar sauce, bacon bits, and jalapeños.',
        'price' => 5.00,
        'discount_percent' => 20,
        'discount_start' => '2026-08-25T14:00',
        'discount_end' => '2026-08-27T23:59',
        'availability' => 'available',
        'status' => 'active',
        'is_featured' => 1,
        'is_popular' => 1,
        'image' => 'loaded-cheese-fries.jpg'
    ],
    [
        'id' => 13,
        'name' => 'Triple-Decker Club',
        'slug' => 'triple-decker-club',
        'category_id' => 'sandwiches',
        'category_name' => 'Sandwiches',
        'description' => 'Toasted sourdough stacked with sliced roasted turkey, crisp beef bacon, fresh lettuce, tomato, and mayo.',
        'price' => 6.50,
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'availability' => 'available',
        'status' => 'active',
        'is_featured' => 0,
        'is_popular' => 0,
        'image' => 'triple-decker-club.jpg'
    ],
    [
        'id' => 14,
        'name' => 'Belgian Chocolate Fudge Cake',
        'slug' => 'belgian-chocolate-fudge-cake',
        'category_id' => 'desserts',
        'category_name' => 'Desserts',
        'description' => 'Decadent multi-layered Belgian dark chocolate fudge cake with silky chocolate ganache.',
        'price' => 5.50,
        'discount_percent' => 10,
        'discount_start' => '2026-08-25T11:00',
        'discount_end' => '2026-08-31T23:59',
        'availability' => 'available',
        'status' => 'active',
        'is_featured' => 0,
        'is_popular' => 1,
        'image' => 'belgian-chocolate-fudge-cake.jpg'
    ],
    [
        'id' => 15,
        'name' => 'Crispy Golden Onion Rings',
        'slug' => 'crispy-golden-onion-rings',
        'category_id' => 'fries',
        'category_name' => 'Fries & Sides',
        'description' => 'Thick-cut sweet onion rings battered in panko breadcrumbs and fried golden crispy.',
        'price' => 4.00,
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'availability' => 'hidden',
        'status' => 'inactive',
        'is_featured' => 0,
        'is_popular' => 0,
        'image' => 'crispy-golden-onion-rings.jpg'
    ]
];

// Calculate Initial Summary Statistics
$statTotal = count($adminFoods);
$statAvailable = count(array_filter($adminFoods, fn($f) => $f['availability'] === 'available'));
$statSoldOut = count(array_filter($adminFoods, fn($f) => $f['availability'] === 'sold_out'));
$statOnDiscount = count(array_filter($adminFoods, fn($f) => (float)$f['discount_percent'] > 0));
$statCategories = 10;
?>

<!-- Admin Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0">

        <!-- 1. Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block">MENU MANAGEMENT</span>
                <h1 class="admin-title fw-bold mb-1">Manage Menu</h1>
                <p class="admin-desc text-muted mb-0">Add, edit, organize, price, discount, and control the availability of every food item on your FastBite menu.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-outline-pink d-inline-flex align-items-center gap-2 shadow-sm" onclick="window.location.href='categories.php'">
                    <i class="bi bi-tags"></i>
                    <span>Manage Categories</span>
                </button>
                <button type="button" class="btn btn-auth-primary d-inline-flex align-items-center gap-2 shadow-sm" id="btnOpenAddFoodModal">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>+ Add New Food</span>
                </button>
            </div>
        </div>

        <!-- Global Toast / Action Alert -->
        <div id="adminMenuToast" class="alert alert-success alert-dismissible fade show d-none align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5" id="adminToastIcon"></i>
            <div id="adminToastMessage">Action completed successfully.</div>
            <button type="button" class="btn-close ms-auto" onclick="document.getElementById('adminMenuToast').classList.add('d-none')"></button>
        </div>

        <!-- 3. Menu Summary Statistics (5 Cards) -->
        <div class="row g-3 mb-4" id="statsCardsContainer">
            <!-- Total Foods -->
            <div class="col-6 col-md-4 col-xl">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 stat-hover-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-pink-soft text-primary-accent rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                            <i class="bi bi-egg-fried fs-5"></i>
                        </div>
                        <div>
                            <span class="d-block extra-small text-muted text-uppercase fw-semibold">Total Foods</span>
                            <span class="h4 fw-bold mb-0 text-dark" id="statTotalFoods"><?= $statTotal; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available -->
            <div class="col-6 col-md-4 col-xl">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 stat-hover-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                            <i class="bi bi-check-circle fs-5"></i>
                        </div>
                        <div>
                            <span class="d-block extra-small text-muted text-uppercase fw-semibold">Available</span>
                            <span class="h4 fw-bold mb-0 text-dark" id="statAvailableFoods"><?= $statAvailable; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sold Out -->
            <div class="col-6 col-md-4 col-xl">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 stat-hover-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                            <i class="bi bi-x-circle fs-5"></i>
                        </div>
                        <div>
                            <span class="d-block extra-small text-muted text-uppercase fw-semibold">Sold Out</span>
                            <span class="h4 fw-bold mb-0 text-dark" id="statSoldOutFoods"><?= $statSoldOut; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- On Discount -->
            <div class="col-6 col-md-6 col-xl">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 stat-hover-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                            <i class="bi bi-percent fs-5"></i>
                        </div>
                        <div>
                            <span class="d-block extra-small text-muted text-uppercase fw-semibold">On Discount</span>
                            <span class="h4 fw-bold mb-0 text-dark" id="statDiscountFoods"><?= $statOnDiscount; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div class="col-12 col-md-6 col-xl">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 stat-hover-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrap bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                            <i class="bi bi-grid fs-5"></i>
                        </div>
                        <div>
                            <span class="d-block extra-small text-muted text-uppercase fw-semibold">Categories</span>
                            <span class="h4 fw-bold mb-0 text-dark"><?= $statCategories; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Search and Filter Toolbar -->
        <div class="card p-3 p-md-4 border-0 bg-white shadow-sm mb-4">
            <div class="row g-3 align-items-end">
                
                <!-- Search Field -->
                <div class="col-12 col-md-4 col-xl-3">
                    <label for="foodSearchInput" class="form-label extra-small text-muted fw-semibold text-uppercase mb-1">Search Food</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" 
                               id="foodSearchInput" 
                               class="form-control border-start-0" 
                               placeholder="Search foods by name...">
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="col-6 col-md-3 col-xl-3">
                    <label for="adminMenuCategoryFilter" class="form-label extra-small text-muted fw-semibold text-uppercase mb-1">Category</label>
                    <select id="adminMenuCategoryFilter" class="form-select">
                        <option value="all">All Categories</option>
                        <option value="pizza">Pizza</option>
                        <option value="burgers">Burgers</option>
                        <option value="shawarma">Shawarma</option>
                        <option value="drinks">Drinks</option>
                        <option value="coffee">Coffee</option>
                        <option value="chicken">Chicken</option>
                        <option value="pasta">Pasta</option>
                        <option value="sandwiches">Sandwiches</option>
                        <option value="fries">Fries &amp; Sides</option>
                        <option value="desserts">Desserts</option>
                    </select>
                </div>

                <!-- Availability Filter -->
                <div class="col-6 col-md-2 col-xl-2">
                    <label for="adminMenuAvailabilityFilter" class="form-label extra-small text-muted fw-semibold text-uppercase mb-1">Availability</label>
                    <select id="adminMenuAvailabilityFilter" class="form-select">
                        <option value="all">All Availability</option>
                        <option value="available">Available</option>
                        <option value="sold_out">Sold Out</option>
                        <option value="hidden">Hidden</option>
                    </select>
                </div>

                <!-- Discount Filter -->
                <div class="col-6 col-md-2 col-xl-2">
                    <label for="adminMenuDiscountFilter" class="form-label extra-small text-muted fw-semibold text-uppercase mb-1">Discounts</label>
                    <select id="adminMenuDiscountFilter" class="form-select">
                        <option value="all">All Discounts</option>
                        <option value="discounted">On Discount</option>
                        <option value="normal">No Discount</option>
                    </select>
                </div>

                <!-- Clear Filters -->
                <div class="col-6 col-md-1 col-xl-2 d-flex gap-2">
                    <button type="button" class="btn btn-outline-pink w-100 d-inline-flex align-items-center justify-content-center gap-1" id="btnClearMenuFilters" title="Clear Filters">
                        <i class="bi bi-x-circle"></i>
                        <span class="d-none d-xl-inline">Clear Filters</span>
                    </button>
                </div>

            </div>
        </div>

        <!-- 5. Food Management Table & Mobile Cards -->
        <div class="card border-0 bg-white shadow-sm overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <h2 class="h6 fw-bold mb-0 text-dark">Menu Catalog</h2>
                    <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle" id="filteredCountBadge"><?= count($adminFoods); ?> items shown</span>
                </div>
                <div class="extra-small text-muted d-none d-sm-block">
                    <i class="bi bi-info-circle me-1 text-primary-accent"></i> Changes update preview in real-time
                </div>
            </div>

            <!-- Desktop / Tablet Table View -->
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover align-middle mb-0 admin-food-table" id="adminMenuTable">
                    <thead class="table-light">
                        <tr class="extra-small text-uppercase text-muted">
                            <th scope="col" style="width: 75px;" class="ps-4">Image</th>
                            <th scope="col">Food Name</th>
                            <th scope="col">Category</th>
                            <th scope="col">Price</th>
                            <th scope="col">Discount</th>
                            <th scope="col">Final Price</th>
                            <th scope="col" class="text-center">Availability</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adminFoodTableBody">
                        <?php foreach ($adminFoods as $food): ?>
                            <?php
                                $imgSrc = getFoodImage(!empty($food['image']) ? $food['image'] : $food['slug']);
                                $price = (float)$food['price'];
                                $discountPercent = (float)$food['discount_percent'];
                                $discountAmount = round(($price * $discountPercent) / 100, 2);
                                $finalPrice = max(0, $price - $discountAmount);
                                $hasDiscount = ($discountPercent > 0);
                            ?>
                            <tr class="food-item-row" 
                                id="food-row-<?= $food['id']; ?>"
                                data-id="<?= $food['id']; ?>"
                                data-name="<?= htmlspecialchars(strtolower($food['name']), ENT_QUOTES, 'UTF-8'); ?>"
                                data-category="<?= htmlspecialchars($food['category_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-availability="<?= htmlspecialchars($food['availability'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-discount="<?= $hasDiscount ? 'discounted' : 'normal'; ?>"
                                data-discount-percent="<?= $discountPercent; ?>"
                                data-price="<?= $price; ?>"
                                data-final-price="<?= $finalPrice; ?>"
                                data-discount-start="<?= htmlspecialchars($food['discount_start'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-discount-end="<?= htmlspecialchars($food['discount_end'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-desc="<?= htmlspecialchars($food['description'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-image="<?= htmlspecialchars($food['image'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-status="<?= htmlspecialchars($food['status'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-featured="<?= $food['is_featured']; ?>"
                                data-popular="<?= $food['is_popular']; ?>">

                                <!-- 1. Image -->
                                <td class="ps-4">
                                    <div class="admin-food-thumb-wrap position-relative">
                                        <img src="../<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>" 
                                             alt="<?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                             class="admin-food-thumb rounded border object-fit-cover"
                                             loading="lazy">
                                    </div>
                                </td>

                                <!-- 2. Food Name & Slug -->
                                <td>
                                    <span class="d-block fw-bold text-dark small text-truncate food-row-title" style="max-width: 200px;"><?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="text-muted extra-small font-monospace d-block" style="font-size: 0.72rem;"><?= htmlspecialchars($food['slug'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>

                                <!-- 3. Category -->
                                <td>
                                    <span class="badge bg-light text-dark border extra-small food-row-category">
                                        <?= htmlspecialchars($food['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>

                                <!-- 4. Original Price -->
                                <td>
                                    <span class="fw-bold text-dark small food-row-price">$<?= number_format($price, 2); ?></span>
                                </td>

                                <!-- 5. Discount -->
                                <td>
                                    <?php if ($hasDiscount): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small fw-bold food-row-discount">
                                            <i class="bi bi-tag-fill me-1"></i><?= $discountPercent; ?>% OFF
                                        </span>
                                        <?php if (!empty($food['discount_end'])): ?>
                                            <span class="d-block extra-small text-muted countdown-chip" data-end="<?= htmlspecialchars($food['discount_end'], ENT_QUOTES, 'UTF-8'); ?>" style="font-size: 0.70rem;">
                                                Ends: <?= date('M d, H:i', strtotime($food['discount_end'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted extra-small">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- 6. Final Price -->
                                <td>
                                    <span class="fw-bold text-primary-accent small food-row-final">$<?= number_format($finalPrice, 2); ?></span>
                                    <?php if ($hasDiscount): ?>
                                        <span class="text-muted text-decoration-line-through extra-small d-block" style="font-size: 0.70rem;">$<?= number_format($price, 2); ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- 7. Availability Control -->
                                <td class="text-center">
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-sm dropdown-toggle py-1 px-2 availability-btn-badge <?= ($food['availability'] === 'available') ? 'btn-success-subtle text-success border border-success-subtle' : (($food['availability'] === 'sold_out') ? 'btn-danger-subtle text-danger border border-danger-subtle' : 'btn-secondary-subtle text-secondary border border-secondary-subtle'); ?> extra-small fw-bold" 
                                                type="button" 
                                                data-bs-toggle="dropdown" 
                                                aria-expanded="false"
                                                id="availDropdown-<?= $food['id']; ?>">
                                            <?php if ($food['availability'] === 'available'): ?>
                                                <i class="bi bi-check-circle-fill me-1"></i> Available
                                            <?php elseif ($food['availability'] === 'sold_out'): ?>
                                                <i class="bi bi-x-circle-fill me-1"></i> Sold Out
                                            <?php else: ?>
                                                <i class="bi bi-eye-slash-fill me-1"></i> Hidden
                                            <?php endif; ?>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm extra-small">
                                            <li><a class="dropdown-item d-flex align-items-center gap-2 btn-set-availability" href="#" data-id="<?= $food['id']; ?>" data-avail="available"><i class="bi bi-check-circle-fill text-success"></i> Mark as Available</a></li>
                                            <li><a class="dropdown-item d-flex align-items-center gap-2 btn-set-availability" href="#" data-id="<?= $food['id']; ?>" data-avail="sold_out"><i class="bi bi-x-circle-fill text-danger"></i> Mark as Sold Out</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item d-flex align-items-center gap-2 btn-set-availability" href="#" data-id="<?= $food['id']; ?>" data-avail="hidden"><i class="bi bi-eye-slash-fill text-muted"></i> Hide from Customers</a></li>
                                        </ul>
                                    </div>
                                </td>

                                <!-- 8. Status -->
                                <td class="text-center">
                                    <?php if ($food['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle extra-small">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border extra-small">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <!-- 9. Actions -->
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 btn-action-preview" data-id="<?= $food['id']; ?>" title="Live Customer Preview">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-pink py-1 px-2 btn-action-edit" data-id="<?= $food['id']; ?>" title="Edit Food">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 btn-action-delete" data-id="<?= $food['id']; ?>" data-name="<?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?>" title="Delete Food">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Responsive Card Grid (360px - 1024px) -->
            <div class="d-lg-none p-3" id="adminFoodCardsContainer">
                <div class="row g-3">
                    <?php foreach ($adminFoods as $food): ?>
                        <?php
                            $imgSrc = getFoodImage(!empty($food['image']) ? $food['image'] : $food['slug']);
                            $price = (float)$food['price'];
                            $discountPercent = (float)$food['discount_percent'];
                            $discountAmount = round(($price * $discountPercent) / 100, 2);
                            $finalPrice = max(0, $price - $discountAmount);
                            $hasDiscount = ($discountPercent > 0);
                        ?>
                        <div class="col-12 col-md-6 food-item-card-col" 
                             id="food-card-<?= $food['id']; ?>"
                             data-id="<?= $food['id']; ?>"
                             data-name="<?= htmlspecialchars(strtolower($food['name']), ENT_QUOTES, 'UTF-8'); ?>"
                             data-category="<?= htmlspecialchars($food['category_id'], ENT_QUOTES, 'UTF-8'); ?>"
                             data-availability="<?= htmlspecialchars($food['availability'], ENT_QUOTES, 'UTF-8'); ?>"
                             data-discount="<?= $hasDiscount ? 'discounted' : 'normal'; ?>"
                             data-discount-percent="<?= $discountPercent; ?>"
                             data-price="<?= $price; ?>"
                             data-final-price="<?= $finalPrice; ?>"
                             data-discount-start="<?= htmlspecialchars($food['discount_start'], ENT_QUOTES, 'UTF-8'); ?>"
                             data-discount-end="<?= htmlspecialchars($food['discount_end'], ENT_QUOTES, 'UTF-8'); ?>"
                             data-desc="<?= htmlspecialchars($food['description'], ENT_QUOTES, 'UTF-8'); ?>"
                             data-image="<?= htmlspecialchars($food['image'], ENT_QUOTES, 'UTF-8'); ?>"
                             data-status="<?= htmlspecialchars($food['status'], ENT_QUOTES, 'UTF-8'); ?>"
                             data-featured="<?= $food['is_featured']; ?>"
                             data-popular="<?= $food['is_popular']; ?>">

                            <div class="card p-3 border rounded shadow-sm bg-white h-100">
                                <div class="d-flex gap-3 align-items-start mb-2">
                                    <div class="admin-food-thumb-wrap flex-shrink-0">
                                        <img src="../<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>" 
                                             alt="<?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                             class="admin-food-thumb rounded border object-fit-cover"
                                             loading="lazy">
                                    </div>
                                    <div class="flex-grow-1 text-truncate">
                                        <span class="badge bg-light text-dark border extra-small mb-1"><?= htmlspecialchars($food['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <h3 class="h6 fw-bold text-dark mb-1 text-truncate card-food-title"><?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                        
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="fw-bold text-primary-accent small card-food-final">$<?= number_format($finalPrice, 2); ?></span>
                                            <?php if ($hasDiscount): ?>
                                                <span class="text-muted text-decoration-line-through extra-small card-food-price">$<?= number_format($price, 2); ?></span>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small fw-bold card-food-discount"><?= $discountPercent; ?>% OFF</span>
                                            <?php else: ?>
                                                <span class="fw-bold text-dark extra-small card-food-price">$<?= number_format($price, 2); ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($hasDiscount && !empty($food['discount_end'])): ?>
                                            <div class="extra-small text-muted countdown-chip" data-end="<?= htmlspecialchars($food['discount_end'], ENT_QUOTES, 'UTF-8'); ?>" style="font-size: 0.70rem;">
                                                <i class="bi bi-clock me-1"></i>Ends: <?= date('M d, H:i', strtotime($food['discount_end'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 mt-auto border-top">
                                    <!-- Availability Dropdown -->
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle py-1 px-2 card-avail-badge <?= ($food['availability'] === 'available') ? 'btn-success-subtle text-success border border-success-subtle' : (($food['availability'] === 'sold_out') ? 'btn-danger-subtle text-danger border border-danger-subtle' : 'btn-secondary-subtle text-secondary border border-secondary-subtle'); ?> extra-small fw-bold" 
                                                type="button" 
                                                data-bs-toggle="dropdown">
                                            <?php if ($food['availability'] === 'available'): ?>
                                                🟢 Available
                                            <?php elseif ($food['availability'] === 'sold_out'): ?>
                                                🔴 Sold Out
                                            <?php else: ?>
                                                ⚪ Hidden
                                            <?php endif; ?>
                                        </button>
                                        <ul class="dropdown-menu shadow-sm extra-small">
                                            <li><a class="dropdown-item btn-set-availability" href="#" data-id="<?= $food['id']; ?>" data-avail="available">🟢 Mark as Available</a></li>
                                            <li><a class="dropdown-item btn-set-availability" href="#" data-id="<?= $food['id']; ?>" data-avail="sold_out">🔴 Mark as Sold Out</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item btn-set-availability" href="#" data-id="<?= $food['id']; ?>" data-avail="hidden">⚪ Hide from Menu</a></li>
                                        </ul>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="d-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 btn-action-preview" data-id="<?= $food['id']; ?>" title="Preview">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-pink py-1 px-2 btn-action-edit" data-id="<?= $food['id']; ?>" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 btn-action-delete" data-id="<?= $food['id']; ?>" data-name="<?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?>" title="Delete">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Empty State for Filters -->
            <div id="adminMenuEmptyState" class="text-center py-5 px-3 d-none">
                <div class="stat-icon-wrap bg-pink-soft text-primary-accent rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-search fs-2"></i>
                </div>
                <h3 class="h5 fw-bold text-dark mb-1">No Food Items Match Filter</h3>
                <p class="text-muted extra-small mb-3 max-w-sm mx-auto">Try adjusting your search query or reset the filters to see all foods.</p>
                <button type="button" class="btn btn-outline-pink btn-sm" id="btnResetEmptyFilters">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset All Filters
                </button>
            </div>

        </div>

    </div>
</main>

<!-- ============================================================== -->
<!-- 7. ADD / EDIT FOOD MODAL (Reusable Form with Live Calculation) -->
<!-- ============================================================== -->
<div class="modal fade" id="foodFormModal" tabindex="-1" aria-labelledby="foodFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            
            <div class="modal-header border-bottom py-3 px-4 bg-white">
                <div class="d-flex align-items-center gap-2">
                    <div class="stat-icon-wrap bg-pink-soft text-primary-accent rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-egg-fried" id="foodModalHeaderIcon"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="foodFormModalLabel">Add New Food</h5>
                        <span class="text-muted extra-small">FastBite Menu Management System</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="adminFoodForm" novalidate>
                <input type="hidden" id="formFoodId" value="">

                <div class="modal-body p-4 bg-light-subtle">
                    <div class="row g-4">
                        
                        <!-- Left Column: Form Fields -->
                        <div class="col-12 col-md-7 d-flex flex-column gap-3">
                            
                            <!-- Food Name -->
                            <div>
                                <label for="formFoodName" class="form-label auth-label">Food Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="formFoodName" placeholder="e.g. Chicken Supreme Pizza" required>
                                <div class="invalid-feedback extra-small">Please enter a food title.</div>
                            </div>

                            <!-- Category -->
                            <div>
                                <label for="formFoodCategory" class="form-label auth-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="formFoodCategory" required>
                                    <option value="" disabled selected>Select category...</option>
                                    <option value="pizza">Pizza</option>
                                    <option value="burgers">Burgers</option>
                                    <option value="shawarma">Shawarma</option>
                                    <option value="drinks">Drinks</option>
                                    <option value="coffee">Coffee</option>
                                    <option value="chicken">Chicken</option>
                                    <option value="pasta">Pasta</option>
                                    <option value="sandwiches">Sandwiches</option>
                                    <option value="fries">Fries &amp; Sides</option>
                                    <option value="desserts">Desserts</option>
                                </select>
                                <div class="invalid-feedback extra-small">Please choose a category.</div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="formFoodDesc" class="form-label auth-label">Description</label>
                                <textarea class="form-control" id="formFoodDesc" rows="3" placeholder="Describe the ingredients, savory flavors, crust, and preparation..."></textarea>
                            </div>

                            <!-- Price & Discount Row -->
                            <div class="row g-3">
                                <div class="col-6">
                                    <label for="formFoodPrice" class="form-label auth-label">Original Price ($) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">$</span>
                                        <input type="number" step="0.01" min="0" class="form-control" id="formFoodPrice" placeholder="10.00" required>
                                    </div>
                                    <div class="invalid-feedback extra-small">Valid price required.</div>
                                </div>
                                <div class="col-6">
                                    <label for="formFoodDiscount" class="form-label auth-label">Discount (%)</label>
                                    <div class="input-group">
                                        <input type="number" min="0" max="100" class="form-control" id="formFoodDiscount" placeholder="0" value="0">
                                        <span class="input-group-text bg-white">%</span>
                                    </div>
                                    <div class="extra-small text-muted mt-1">0% = No discount</div>
                                </div>
                            </div>

                            <!-- Schedule Discount Accordion / Toggle -->
                            <div class="p-3 bg-white rounded border">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="fw-bold extra-small text-dark text-uppercase"><i class="bi bi-clock-history me-1 text-primary-accent"></i> Time-Limited Discount</span>
                                    <span class="badge bg-pink-soft text-primary-accent extra-small">Optional Schedule</span>
                                </div>
                                
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label for="formDiscountStart" class="form-label extra-small text-muted mb-1">Start Date &amp; Time</label>
                                        <input type="datetime-local" class="form-control form-control-sm" id="formDiscountStart">
                                    </div>
                                    <div class="col-6">
                                        <label for="formDiscountEnd" class="form-label extra-small text-muted mb-1">End Date &amp; Time</label>
                                        <input type="datetime-local" class="form-control form-control-sm" id="formDiscountEnd">
                                    </div>
                                </div>
                                <div class="extra-small text-muted mt-2" style="font-size: 0.70rem;">
                                    When the end time passes, the discount automatically expires and restores original price.
                                </div>
                            </div>

                            <!-- Availability & Active Status -->
                            <div class="row g-3">
                                <div class="col-6">
                                    <label for="formFoodAvailability" class="form-label auth-label">Availability</label>
                                    <select class="form-select form-select-sm" id="formFoodAvailability">
                                        <option value="available">🟢 Available</option>
                                        <option value="sold_out">🔴 Sold Out</option>
                                        <option value="hidden">⚪ Hidden</option>
                                    </select>
                                </div>
                                <div class="col-6 d-flex align-items-center pt-3 gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="formFoodFeatured">
                                        <label class="form-check-label extra-small fw-bold text-dark" for="formFoodFeatured">Featured</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="formFoodPopular">
                                        <label class="form-check-label extra-small fw-bold text-dark" for="formFoodPopular">Popular</label>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Right Column: Live Image Preview & Live Pricing Calculation Card -->
                        <div class="col-12 col-md-5 d-flex flex-column gap-3">
                            
                            <!-- 10. Live Discount & Price Preview Card -->
                            <div class="card p-3 border-0 bg-white shadow-sm">
                                <span class="extra-small text-muted text-uppercase fw-bold mb-2 d-block">Live Pricing Preview</span>
                                <div class="d-flex align-items-baseline gap-2 mb-1">
                                    <span class="h3 fw-bold text-primary-accent mb-0" id="livePreviewFinalPrice">$0.00</span>
                                    <span class="text-muted text-decoration-line-through small d-none" id="livePreviewOriginalPrice">$0.00</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small fw-bold d-none" id="livePreviewDiscountBadge">0% OFF</span>
                                    <span class="extra-small text-muted" id="livePreviewSavedAmount">Discount: $0.00</span>
                                </div>
                                
                                <!-- Live Countdown Widget -->
                                <div class="p-2 rounded bg-pink-soft border border-pink-subtle extra-small text-primary-accent fw-semibold d-none" id="liveCountdownWidget">
                                    <i class="bi bi-stopwatch me-1"></i>
                                    <span id="liveCountdownText">Offer active</span>
                                </div>
                            </div>

                            <!-- Food Image Upload & Dropzone Area -->
                            <div class="card p-3 border-0 bg-white shadow-sm">
                                <label class="form-label auth-label mb-1">Food Image</label>
                                <div class="admin-image-dropzone text-center p-3 rounded border border-dashed mb-2" id="adminImageDropzone">
                                    <div id="dropzoneEmptyState">
                                        <i class="bi bi-cloud-arrow-up fs-2 text-primary-accent mb-2 d-block"></i>
                                        <span class="d-block fw-bold small text-dark">Click or Drag &amp; Drop Image</span>
                                        <span class="extra-small text-muted d-block">JPG, PNG, WEBP up to 5MB</span>
                                    </div>
                                    <div id="dropzonePreviewState" class="d-none position-relative">
                                        <img src="../assets/images/placeholders/food-placeholder.jpg" alt="Preview" class="img-fluid rounded border mb-2 object-fit-cover" id="formImagePreview" style="max-height: 140px; width: 100%;">
                                        <button type="button" class="btn btn-sm btn-danger py-0 px-2 position-absolute top-0 end-0 m-1" id="btnClearImage" title="Remove selected image">
                                            <i class="bi bi-x"></i>
                                        </button>
                                        <span class="d-block extra-small text-truncate text-muted" id="formImageFilename">image.jpg</span>
                                    </div>
                                    <input type="file" id="formImageInput" class="d-none" accept=".jpg,.jpeg,.png,.webp">
                                </div>
                                <div class="extra-small text-muted text-center" style="font-size: 0.70rem;">
                                    <i class="bi bi-camera me-1"></i> Recommended: High-quality food photography
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

                <div class="modal-footer border-top py-2 px-4 bg-white d-flex justify-content-between">
                    <button type="button" class="btn btn-light border btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-auth-primary btn-sm px-4 d-inline-flex align-items-center gap-1" id="btnSaveFoodItem">
                        <i class="bi bi-check2-circle"></i>
                        <span id="btnSaveFoodLabel">Save Food</span>
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- 21. LIVE CUSTOMER MENU PREVIEW MODAL -->
<!-- ============================================================== -->
<div class="modal fade" id="foodPreviewModal" tabindex="-1" aria-labelledby="foodPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content border-0 shadow">
            
            <div class="modal-header border-bottom py-2 px-3 bg-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-eye text-primary-accent"></i>
                    <h6 class="modal-title fw-bold text-dark mb-0">Customer Menu Card Preview</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3 bg-light">
                <!-- Customer Card Replica -->
                <div class="card border rounded shadow-sm bg-white overflow-hidden" id="previewCustomerCard">
                    
                    <div class="position-relative">
                        <img src="../assets/images/placeholders/food-placeholder.jpg" 
                             alt="Food Preview" 
                             class="w-100 object-fit-cover" 
                             id="prevCardImage" 
                             style="height: 190px;">
                        
                        <div class="position-absolute top-0 start-0 m-2">
                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small fw-bold" id="prevCardCategory">
                                Category
                            </span>
                        </div>

                        <div class="position-absolute top-0 end-0 m-2">
                            <span class="badge bg-danger text-white extra-small fw-bold d-none" id="prevCardSoldOutBadge">
                                SOLD OUT
                            </span>
                            <span class="badge bg-danger extra-small fw-bold d-none" id="prevCardDiscountBadge">
                                20% OFF
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-3 d-flex flex-column">
                        <h4 class="h6 fw-bold text-dark mb-1" id="prevCardTitle">Chicken Supreme Pizza</h4>
                        <p class="text-muted extra-small mb-3 line-clamp-2" id="prevCardDesc">Freshly baked pizza with tender chicken and melted mozzarella.</p>

                        <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-auto">
                            <div>
                                <span class="h5 fw-bold text-primary-accent mb-0" id="prevCardFinalPrice">$8.00</span>
                                <span class="text-muted text-decoration-line-through extra-small ms-1 d-none" id="prevCardOriginalPrice">$10.00</span>
                            </div>
                            
                            <button type="button" class="btn btn-sm btn-auth-primary py-1 px-3 d-inline-flex align-items-center gap-1" id="prevCardBtnAction">
                                <i class="bi bi-bag-plus"></i>
                                <span id="prevCardBtnLabel">Add to Cart</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer border-top py-2 px-3 bg-white">
                <button type="button" class="btn btn-outline-pink btn-sm w-100" data-bs-dismiss="modal">Close Preview</button>
            </div>

        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- 13. DELETE FOOD CONFIRMATION MODAL -->
<!-- ============================================================== -->
<div class="modal fade" id="deleteFoodModal" tabindex="-1" aria-labelledby="deleteFoodModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow">
            
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-2 text-danger">
                    <div class="stat-icon-wrap bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-trash3-fill fs-5"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-dark" id="deleteFoodModalLabel">Delete Food Item?</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <p class="text-dark mb-2">Are you sure you want to delete <strong id="deleteFoodNamePrompt">Chicken Supreme Pizza</strong>? This action cannot be undone.</p>
                <div class="p-2 rounded bg-light border extra-small text-muted">
                    <i class="bi bi-info-circle me-1 text-primary-accent"></i>
                    Tip: If this item is simply out of stock today, consider marking it as <strong>Sold Out</strong> instead of deleting it.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light border btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm px-3 d-inline-flex align-items-center gap-1" id="btnConfirmDeleteFood">
                    <i class="bi bi-trash3-fill"></i>
                    <span>Delete Food</span>
                </button>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
