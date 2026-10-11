<?php
/**
 * FastBite - Admin Food Form (Dedicated Add / Edit Page)
 * Step 23: Admin Menu/Food Management System
 * 
 * Supports adding new food, editing existing food items, live discount calculation,
 * time-limited discount scheduling, and live customer card preview.
 */

$pageTitle = isset($_GET['id']) ? 'Edit Food Item' : 'Add New Food';
$activeMenu = 'menu';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$foodId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = !empty($foodId);

// Pre-fill demo data if editing
$editData = [
    'name' => 'Chicken Supreme Pizza',
    'slug' => 'chicken-supreme-pizza',
    'category' => 'pizza',
    'description' => 'Tender seasoned chicken breast, red bell peppers, onions, and melted mozzarella cheese on freshly baked golden crust.',
    'price' => 10.00,
    'discount_percent' => 20,
    'discount_start' => '2026-08-25T18:00',
    'discount_end' => '2026-08-28T23:59',
    'availability' => 'available',
    'is_featured' => 1,
    'is_popular' => 1,
    'image' => 'chicken-supreme-pizza.jpg'
];
?>

<!-- Admin Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0">

        <!-- Breadcrumb & Header -->
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb extra-small">
                <li class="breadcrumb-item"><a href="menu.php" class="text-primary-accent text-decoration-none">Menu Management</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Edit Food' : 'Add New Food'; ?></li>
            </ol>
        </nav>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block">FOOD AUTHORING</span>
                <h1 class="admin-title fw-bold mb-1"><?= $isEdit ? 'Edit Food Item' : 'Add New Food'; ?></h1>
                <p class="admin-desc text-muted mb-0">Configure food details, pricing, time-limited discounts, and availability.</p>
            </div>
            <div>
                <a href="menu.php" class="btn btn-outline-pink btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Menu List</span>
                </a>
            </div>
        </div>

        <form id="standaloneFoodForm" novalidate>
            <input type="hidden" id="formFoodId" value="<?= $isEdit ? $foodId : ''; ?>">

            <div class="row g-4">
                
                <!-- Left Column: Form Inputs -->
                <div class="col-12 col-lg-8 d-flex flex-column gap-4">
                    
                    <!-- Basic Information Card -->
                    <div class="card p-4 border-0 bg-white shadow-sm">
                        <h2 class="h6 fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle text-primary-accent"></i>
                            <span>Basic Food Information</span>
                        </h2>

                        <div class="row g-3">
                            <!-- Food Name -->
                            <div class="col-12 col-md-8">
                                <label for="formFoodName" class="form-label auth-label">Food Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="formFoodName" 
                                       placeholder="e.g. Chicken Supreme Pizza" 
                                       value="<?= $isEdit ? htmlspecialchars($editData['name'], ENT_QUOTES, 'UTF-8') : ''; ?>" 
                                       required>
                                <div class="invalid-feedback extra-small">Please enter a food title.</div>
                            </div>

                            <!-- Category -->
                            <div class="col-12 col-md-4">
                                <label for="formFoodCategory" class="form-label auth-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="formFoodCategory" required>
                                    <option value="" disabled <?= !$isEdit ? 'selected' : ''; ?>>Choose Category...</option>
                                    <option value="pizza" <?= ($isEdit && $editData['category'] === 'pizza') ? 'selected' : ''; ?>>Pizza</option>
                                    <option value="burgers" <?= ($isEdit && $editData['category'] === 'burgers') ? 'selected' : ''; ?>>Burgers</option>
                                    <option value="shawarma" <?= ($isEdit && $editData['category'] === 'shawarma') ? 'selected' : ''; ?>>Shawarma</option>
                                    <option value="drinks" <?= ($isEdit && $editData['category'] === 'drinks') ? 'selected' : ''; ?>>Drinks</option>
                                    <option value="coffee" <?= ($isEdit && $editData['category'] === 'coffee') ? 'selected' : ''; ?>>Coffee</option>
                                    <option value="chicken" <?= ($isEdit && $editData['category'] === 'chicken') ? 'selected' : ''; ?>>Chicken</option>
                                    <option value="pasta" <?= ($isEdit && $editData['category'] === 'pasta') ? 'selected' : ''; ?>>Pasta</option>
                                    <option value="sandwiches" <?= ($isEdit && $editData['category'] === 'sandwiches') ? 'selected' : ''; ?>>Sandwiches</option>
                                    <option value="fries" <?= ($isEdit && $editData['category'] === 'fries') ? 'selected' : ''; ?>>Fries &amp; Sides</option>
                                    <option value="desserts" <?= ($isEdit && $editData['category'] === 'desserts') ? 'selected' : ''; ?>>Desserts</option>
                                </select>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label for="formFoodDesc" class="form-label auth-label">Description</label>
                                <textarea class="form-control" id="formFoodDesc" rows="3" placeholder="Describe the ingredients, savory flavors, crust, and preparation..."><?= $isEdit ? htmlspecialchars($editData['description'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing & Time-Limited Discount Card -->
                    <div class="card p-4 border-0 bg-white shadow-sm">
                        <h2 class="h6 fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                            <i class="bi bi-tag text-primary-accent"></i>
                            <span>Pricing &amp; Discount Configuration</span>
                        </h2>

                        <div class="row g-3 mb-3">
                            <div class="col-6 col-md-6">
                                <label for="formFoodPrice" class="form-label auth-label">Original Price ($) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">$</span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="formFoodPrice" placeholder="10.00" value="<?= $isEdit ? $editData['price'] : ''; ?>" required>
                                </div>
                                <div class="extra-small text-muted mt-1">Base menu price</div>
                            </div>
                            <div class="col-6 col-md-6">
                                <label for="formFoodDiscount" class="form-label auth-label">Discount Percentage (%)</label>
                                <div class="input-group">
                                    <input type="number" min="0" max="100" class="form-control" id="formFoodDiscount" placeholder="0" value="<?= $isEdit ? $editData['discount_percent'] : '0'; ?>">
                                    <span class="input-group-text bg-white">%</span>
                                </div>
                                <div class="extra-small text-muted mt-1">Between 0% and 100%</div>
                            </div>
                        </div>

                        <!-- Time-Limited Schedule -->
                        <div class="p-3 bg-light rounded border">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold extra-small text-dark text-uppercase"><i class="bi bi-clock-history me-1 text-primary-accent"></i> Time-Limited Discount Window</span>
                                <span class="badge bg-pink-soft text-primary-accent extra-small">Automated Expiry</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label for="formDiscountStart" class="form-label extra-small text-muted mb-1">Starts At</label>
                                    <input type="datetime-local" class="form-control form-control-sm" id="formDiscountStart" value="<?= $isEdit ? $editData['discount_start'] : ''; ?>">
                                </div>
                                <div class="col-6">
                                    <label for="formDiscountEnd" class="form-label extra-small text-muted mb-1">Ends At</label>
                                    <input type="datetime-local" class="form-control form-control-sm" id="formDiscountEnd" value="<?= $isEdit ? $editData['discount_end'] : ''; ?>">
                                </div>
                            </div>
                            <div class="extra-small text-muted mt-2" style="font-size: 0.70rem;">
                                Formula: <code class="text-primary-accent">Final Price = Price - (Price &times; Discount / 100)</code>. Expired discounts revert immediately to original price.
                            </div>
                        </div>

                    </div>

                    <!-- Availability & Marketing Badges -->
                    <div class="card p-4 border-0 bg-white shadow-sm">
                        <h2 class="h6 fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
                            <i class="bi bi-toggle-on text-primary-accent"></i>
                            <span>Availability &amp; Visibility</span>
                        </h2>

                        <div class="row g-3 align-items-center">
                            <div class="col-12 col-md-6">
                                <label for="formFoodAvailability" class="form-label auth-label">Availability Status</label>
                                <select class="form-select" id="formFoodAvailability">
                                    <option value="available" <?= ($isEdit && $editData['availability'] === 'available') ? 'selected' : ''; ?>>🟢 Available (Can be ordered)</option>
                                    <option value="sold_out" <?= ($isEdit && $editData['availability'] === 'sold_out') ? 'selected' : ''; ?>>🔴 Sold Out (Visible, order disabled)</option>
                                    <option value="hidden" <?= ($isEdit && $editData['availability'] === 'hidden') ? 'selected' : ''; ?>>⚪ Hidden (Not visible on menu)</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 d-flex align-items-center gap-4 pt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="formFoodFeatured" <?= ($isEdit && $editData['is_featured']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label extra-small fw-bold text-dark" for="formFoodFeatured">Featured Food</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="formFoodPopular" <?= ($isEdit && $editData['is_popular']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label extra-small fw-bold text-dark" for="formFoodPopular">Popular Choice</label>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Live Calculation & Preview Sidebar -->
                <div class="col-12 col-lg-4 d-flex flex-column gap-4">
                    
                    <!-- Live Pricing Summary -->
                    <div class="card p-3 border-0 bg-white shadow-sm">
                        <h3 class="h6 fw-bold text-dark mb-2">Live Price Calculation</h3>
                        <div class="p-3 bg-pink-soft rounded border border-pink-subtle mb-3">
                            <div class="d-flex align-items-baseline gap-2 mb-1">
                                <span class="h3 fw-bold text-primary-accent mb-0" id="livePreviewFinalPrice">$<?= number_format($editData['price'] * 0.8, 2); ?></span>
                                <span class="text-muted text-decoration-line-through small" id="livePreviewOriginalPrice">$<?= number_format($editData['price'], 2); ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small fw-bold" id="livePreviewDiscountBadge">20% OFF</span>
                                <span class="extra-small text-muted" id="livePreviewSavedAmount">Saves $2.00</span>
                            </div>
                        </div>

                        <!-- Live Countdown Preview -->
                        <div class="p-2 rounded bg-light border extra-small text-dark mb-2" id="liveCountdownWidget">
                            <i class="bi bi-stopwatch text-primary-accent me-1"></i>
                            <span id="liveCountdownText">Active Scheduled Offer</span>
                        </div>
                    </div>

                    <!-- Food Image Upload -->
                    <div class="card p-3 border-0 bg-white shadow-sm">
                        <h3 class="h6 fw-bold text-dark mb-2">Food Image</h3>
                        <div class="admin-image-dropzone text-center p-3 rounded border border-dashed mb-2" id="adminImageDropzone">
                            <div id="dropzoneEmptyState" class="<?= $isEdit ? 'd-none' : ''; ?>">
                                <i class="bi bi-cloud-arrow-up fs-2 text-primary-accent mb-2 d-block"></i>
                                <span class="d-block fw-bold small text-dark">Click or Drag &amp; Drop Image</span>
                                <span class="extra-small text-muted d-block">JPG, PNG, WEBP up to 5MB</span>
                            </div>
                            <div id="dropzonePreviewState" class="<?= $isEdit ? '' : 'd-none'; ?> position-relative">
                                <?php $previewImg = $isEdit ? getFoodImage($editData['image']) : 'assets/images/placeholders/food-placeholder.jpg'; ?>
                                <img src="../<?= htmlspecialchars($previewImg, ENT_QUOTES, 'UTF-8'); ?>" alt="Preview" class="img-fluid rounded border mb-2 object-fit-cover" id="formImagePreview" style="max-height: 150px; width: 100%;">
                                <button type="button" class="btn btn-sm btn-danger py-0 px-2 position-absolute top-0 end-0 m-1" id="btnClearImage" title="Remove selected image">
                                    <i class="bi bi-x"></i>
                                </button>
                                <span class="d-block extra-small text-truncate text-muted" id="formImageFilename"><?= $isEdit ? $editData['image'] : 'image.jpg'; ?></span>
                            </div>
                            <input type="file" id="formImageInput" class="d-none" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                        <div class="extra-small text-muted text-center">
                            <i class="bi bi-camera me-1"></i> High-resolution food photography recommended
                        </div>
                    </div>

                    <!-- Customer Card Live Preview -->
                    <div class="card p-3 border-0 bg-white shadow-sm">
                        <h3 class="h6 fw-bold text-dark mb-2">Customer Card Preview</h3>
                        <div class="card border rounded shadow-sm bg-white overflow-hidden">
                            <div class="position-relative">
                                <img src="../<?= htmlspecialchars($previewImg, ENT_QUOTES, 'UTF-8'); ?>" alt="Preview" class="w-100 object-fit-cover" id="prevCardImage" style="height: 160px;">
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small fw-bold" id="prevCardCategory">Pizza</span>
                                </div>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-danger text-white extra-small fw-bold d-none" id="prevCardSoldOutBadge">SOLD OUT</span>
                                    <span class="badge bg-danger extra-small fw-bold" id="prevCardDiscountBadge">20% OFF</span>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <h4 class="h6 fw-bold text-dark mb-1" id="prevCardTitle"><?= $isEdit ? $editData['name'] : 'Food Item Name'; ?></h4>
                                <p class="text-muted extra-small mb-2 line-clamp-2" id="prevCardDesc"><?= $isEdit ? $editData['description'] : 'Food description appears here...'; ?></p>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                    <div>
                                        <span class="h6 fw-bold text-primary-accent mb-0" id="prevCardFinalPrice">$8.00</span>
                                        <span class="text-muted text-decoration-line-through extra-small ms-1" id="prevCardOriginalPrice">$10.00</span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-auth-primary py-1 px-2 extra-small" id="prevCardBtnAction">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save & Cancel Buttons -->
                    <div class="d-flex gap-2">
                        <a href="menu.php" class="btn btn-light border btn-sm w-50 py-2">Cancel</a>
                        <button type="button" class="btn btn-auth-primary btn-sm w-50 py-2 d-inline-flex align-items-center justify-content-center gap-1" id="btnSaveStandaloneFood">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>

                </div>

            </div>
        </form>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
