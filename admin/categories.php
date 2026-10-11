<?php
header("Location: categories/index.php");
exit;


$pageTitle = 'Category Management';
$activeMenu = 'categories';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Demo & Backend-Ready Categories
$categories = [
    [
        'id' => 1,
        'name' => 'Pizza',
        'slug' => 'pizza',
        'image' => 'pizza.jpg',
        'food_count' => 4,
        'status' => 'active',
        'discount_percent' => 15,
        'discount_start' => '2026-08-25T18:00',
        'discount_end' => '2026-08-27T20:00',
        'description' => 'Freshly baked pizzas with gourmet crusts, rich tomato sauce, and savory toppings.'
    ],
    [
        'id' => 2,
        'name' => 'Burgers',
        'slug' => 'burgers',
        'image' => 'burgers.jpg',
        'food_count' => 4,
        'status' => 'active',
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'description' => 'Juicy handcrafted patties grilled to perfection with crisp garden vegetables.'
    ],
    [
        'id' => 3,
        'name' => 'Shawarma',
        'slug' => 'shawarma',
        'image' => 'shawarma.jpg',
        'food_count' => 3,
        'status' => 'active',
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'description' => 'Tender spiced meat rolled in warm toasted pita with creamy garlic tahini sauce.'
    ],
    [
        'id' => 4,
        'name' => 'Drinks',
        'slug' => 'drinks',
        'image' => 'drinks.jpg',
        'food_count' => 4,
        'status' => 'active',
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'description' => 'Chilled fresh fruit juices, iced beverages, and cold refreshments.'
    ],
    [
        'id' => 5,
        'name' => 'Coffee',
        'slug' => 'coffee',
        'image' => 'coffee.jpg',
        'food_count' => 4,
        'status' => 'active',
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'description' => 'Freshly roasted artisan espresso, creamy cappuccinos, and iced lattes.'
    ],
    [
        'id' => 6,
        'name' => 'Chicken',
        'slug' => 'chicken',
        'image' => 'chicken.jpg',
        'food_count' => 4,
        'status' => 'active',
        'discount_percent' => 10,
        'discount_start' => '2026-08-25T10:00',
        'discount_end' => '2026-08-28T22:00',
        'description' => 'Signature golden crispy chicken seasoned with secret herb blends.'
    ],
    [
        'id' => 7,
        'name' => 'Pasta',
        'slug' => 'pasta',
        'image' => 'pasta.jpg',
        'food_count' => 4,
        'status' => 'active',
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'description' => 'Al dente Italian pastas tossed in rich Alfredo, Pomodoro, and cheese sauces.'
    ],
    [
        'id' => 8,
        'name' => 'Sandwiches',
        'slug' => 'sandwiches',
        'image' => 'sandwiches.jpg',
        'food_count' => 4,
        'status' => 'active',
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'description' => 'Toasted club sandwiches and subs packed with fresh deli meats.'
    ],
    [
        'id' => 9,
        'name' => 'Fries & Sides',
        'slug' => 'fries',
        'image' => 'fries.jpg',
        'food_count' => 4,
        'status' => 'active',
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'description' => 'Crispy golden fries, cheesy loaded sides, and dipping sauces.'
    ],
    [
        'id' => 10,
        'name' => 'Desserts',
        'slug' => 'desserts',
        'image' => 'desserts.jpg',
        'food_count' => 4,
        'status' => 'active',
        'discount_percent' => 0,
        'discount_start' => '',
        'discount_end' => '',
        'description' => 'Warm walnut brownies, cheesecakes, and velvety vanilla ice creams.'
    ]
];
?>

<!-- Admin Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0">

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block">CATEGORY MANAGEMENT</span>
                <h1 class="admin-title fw-bold mb-1">Category Management</h1>
                <p class="admin-desc text-muted mb-0">Organize FastBite food categories, manage thumbnails, and schedule category-wide discounts.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="menu.php" class="btn btn-outline-pink btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-egg-fried"></i>
                    <span>Manage Foods</span>
                </a>
                <button type="button" class="btn btn-auth-primary btn-sm d-inline-flex align-items-center gap-1 shadow-sm" id="btnOpenAddCategoryModal">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>+ Add Category</span>
                </button>
            </div>
        </div>

        <!-- Category Discount Priority Notice Alert -->
        <div class="alert alert-info d-flex align-items-start gap-2 shadow-sm py-3 px-3 mb-4 rounded border-0 bg-pink-soft text-dark" role="alert">
            <i class="bi bi-info-circle-fill text-primary-accent fs-5 flex-shrink-0 mt-1"></i>
            <div>
                <strong class="text-primary-accent">Category Discount Priority Rule:</strong>
                When a category discount is applied (e.g. <em>15% OFF Pizza</em>), all foods in that category receive the discount unless an individual food already has its own specific discount. <strong>The individual food discount always takes priority over the category discount.</strong> (No stacking).
            </div>
        </div>

        <!-- Global Toast Alert -->
        <div id="categoryToast" class="alert alert-success alert-dismissible fade show d-none align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div id="categoryToastMessage">Action completed successfully.</div>
            <button type="button" class="btn-close ms-auto" onclick="document.getElementById('categoryToast').classList.add('d-none')"></button>
        </div>

        <!-- Category Grid Cards -->
        <div class="row g-3 g-md-4" id="categoryCardsGrid">
            <?php foreach ($categories as $cat): ?>
                <?php
                    $imgSrc = getCategoryImage($cat['slug'] . '.jpg');
                    $hasDiscount = ((float)$cat['discount_percent'] > 0);
                ?>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 category-card-col" id="cat-card-<?= $cat['id']; ?>">
                    <div class="card border rounded shadow-sm bg-white h-100 overflow-hidden d-flex flex-column">
                        
                        <!-- Thumbnail Header -->
                        <div class="position-relative">
                            <img src="../<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="<?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 class="w-100 object-fit-cover cat-thumb-img" 
                                 style="height: 140px;"
                                 loading="lazy">
                            
                            <div class="position-absolute top-0 start-0 m-2">
                                <span class="badge bg-white text-dark border shadow-sm extra-small fw-bold">
                                    <i class="bi bi-egg-fried me-1 text-primary-accent"></i><?= $cat['food_count']; ?> Foods
                                </span>
                            </div>

                            <div class="position-absolute top-0 end-0 m-2">
                                <?php if ($cat['status'] === 'active'): ?>
                                    <span class="badge bg-success text-white extra-small fw-bold">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary text-white extra-small fw-bold">Hidden</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-3 d-flex flex-column flex-grow-1">
                            <h3 class="h6 fw-bold text-dark mb-1"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="text-muted extra-small mb-3 line-clamp-2" style="min-height: 32px;"><?= htmlspecialchars($cat['description'], ENT_QUOTES, 'UTF-8'); ?></p>

                            <!-- Category Discount Indicator -->
                            <div class="p-2 rounded bg-light border extra-small mb-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="text-muted"><i class="bi bi-percent me-1 text-primary-accent"></i>Promo Discount:</span>
                                    <?php if ($hasDiscount): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold"><?= $cat['discount_percent']; ?>% OFF</span>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($hasDiscount && !empty($cat['discount_end'])): ?>
                                    <div class="extra-small text-muted mt-1 text-truncate" style="font-size: 0.70rem;">
                                        Ends: <?= date('M d, H:i', strtotime($cat['discount_end'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Actions Footer -->
                            <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-auto">
                                <button type="button" class="btn btn-sm btn-outline-pink py-1 px-2 extra-small btn-cat-discount" data-id="<?= $cat['id']; ?>" data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>" data-discount="<?= $cat['discount_percent']; ?>" data-start="<?= $cat['discount_start']; ?>" data-end="<?= $cat['discount_end']; ?>">
                                    <i class="bi bi-percent me-1"></i> Discount
                                </button>
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 extra-small btn-cat-edit" data-id="<?= $cat['id']; ?>" data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>" data-desc="<?= htmlspecialchars($cat['description'], ENT_QUOTES, 'UTF-8'); ?>" data-status="<?= $cat['status']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 extra-small btn-cat-delete" data-id="<?= $cat['id']; ?>" data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</main>

<!-- ============================================================== -->
<!-- ADD / EDIT CATEGORY MODAL -->
<!-- ============================================================== -->
<div class="modal fade" id="categoryFormModal" tabindex="-1" aria-labelledby="categoryFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            
            <div class="modal-header border-bottom py-3 px-4 bg-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-tags text-primary-accent fs-5"></i>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="categoryFormModalLabel">Add Category</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="adminCategoryForm" novalidate>
                <input type="hidden" id="formCatId" value="">
                
                <div class="modal-body p-4 bg-light-subtle d-flex flex-column gap-3">
                    <div>
                        <label for="formCatName" class="form-label auth-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="formCatName" placeholder="e.g. Gourmet Pizza" required>
                        <div class="invalid-feedback extra-small">Category name is required.</div>
                    </div>

                    <div>
                        <label for="formCatDesc" class="form-label auth-label">Description</label>
                        <textarea class="form-control" id="formCatDesc" rows="2" placeholder="Brief category description..."></textarea>
                    </div>

                    <div>
                        <label for="formCatStatus" class="form-label auth-label">Status</label>
                        <select class="form-select" id="formCatStatus">
                            <option value="active">Active (Visible to customers)</option>
                            <option value="hidden">Hidden (Not shown on menu)</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label auth-label">Category Thumbnail</label>
                        <div class="p-3 bg-white rounded border text-center">
                            <i class="bi bi-image text-muted fs-3 mb-1 d-block"></i>
                            <span class="extra-small text-muted d-block">Thumbnail mapped to <code>assets/images/categories/</code></span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top py-2 px-4 bg-white d-flex justify-content-between">
                    <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-auth-primary btn-sm px-4">Save Category</button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- CATEGORY-WIDE DISCOUNT MODAL -->
<!-- ============================================================== -->
<div class="modal fade" id="categoryDiscountModal" tabindex="-1" aria-labelledby="categoryDiscountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            
            <div class="modal-header border-bottom py-3 px-4 bg-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-percent text-primary-accent fs-5"></i>
                    <h5 class="modal-title fw-bold text-dark mb-0">Apply Category Discount</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="adminCategoryDiscountForm">
                <input type="hidden" id="discountCatId" value="">

                <div class="modal-body p-4 bg-light-subtle d-flex flex-column gap-3">
                    <div class="p-3 bg-pink-soft rounded border border-pink-subtle">
                        <span class="d-block extra-small text-muted text-uppercase fw-semibold">Target Category</span>
                        <h4 class="h6 fw-bold text-dark mb-0" id="discountCatNameTitle">Pizza</h4>
                    </div>

                    <div>
                        <label for="formCatDiscountPercent" class="form-label auth-label">Discount Percentage (%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" min="0" max="100" class="form-control" id="formCatDiscountPercent" placeholder="15" value="0">
                            <span class="input-group-text bg-white">% OFF</span>
                        </div>
                        <div class="extra-small text-muted mt-1">Set 0% to remove category discount.</div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label for="formCatDiscountStart" class="form-label extra-small text-muted mb-1">Start Date &amp; Time</label>
                            <input type="datetime-local" class="form-control form-control-sm" id="formCatDiscountStart">
                        </div>
                        <div class="col-6">
                            <label for="formCatDiscountEnd" class="form-label extra-small text-muted mb-1">End Date &amp; Time</label>
                            <input type="datetime-local" class="form-control form-control-sm" id="formCatDiscountEnd">
                        </div>
                    </div>

                    <div class="p-2 rounded bg-white border extra-small text-muted" style="font-size: 0.72rem;">
                        <i class="bi bi-shield-check text-primary-accent me-1"></i>
                        Priority reminder: Individual food discounts override this category discount.
                    </div>
                </div>

                <div class="modal-footer border-top py-2 px-4 bg-white d-flex justify-content-between">
                    <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-auth-primary btn-sm px-4">Apply Promo</button>
                </div>
            </form>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
