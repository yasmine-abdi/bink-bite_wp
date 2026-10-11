<?php
/**
 * FastBite - Edit Food Item
 * STEP 21: FastBite Admin Menu & Food Management System
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(dirname(__DIR__)));
}
require_once FASTBITE_ROOT_DIR . '/config/database.php';
require_once FASTBITE_ROOT_DIR . '/includes/auth.php';
require_once FASTBITE_ROOT_DIR . '/includes/image-helper.php';

requireAdmin('../login.php');

$foodId = (int)($_GET['id'] ?? 0);
$food = dbGetFoodById($foodId);

if (!$food) {
    header("Location: index.php?error=Food+item+not+found");
    exit;
}

$categories = dbGetAllCategories();
$error = '';

// Check if this food has an existing discount
$pdo = getDbConnection();
$discStmt = $pdo->prepare("SELECT * FROM `discounts` WHERE `type` = 'food' AND `food_id` = ?");
$discStmt->execute([$foodId]);
$existingDisc = $discStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed. Please refresh the page and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $isAvailable = isset($_POST['is_available']) ? (int)$_POST['is_available'] : 1;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isPopular = isset($_POST['is_popular']) ? 1 : 0;
    $discountPercent = max(0, min(100, (int)($_POST['discount_percent'] ?? 0)));
    $discountStart = !empty($_POST['discount_start']) ? trim($_POST['discount_start']) : null;
    $discountEnd = !empty($_POST['discount_end']) ? trim($_POST['discount_end']) : null;

    if (empty($name)) {
        $error = 'Please enter a valid food name.';
    } elseif ($categoryId <= 0) {
        $error = 'Please select a food category.';
    } elseif ($price <= 0) {
        $error = 'Please enter a valid price greater than $0.00.';
    } else {
        $data = [
            'name' => $name,
            'category_id' => $categoryId,
            'description' => $description,
            'price' => $price,
            'is_available' => $isAvailable,
            'is_featured' => $isFeatured,
            'is_popular' => $isPopular,
            'discount_percent' => $discountPercent,
            'discount_start' => $discountStart,
            'discount_end' => $discountEnd
        ];

        $uploadedFile = isset($_FILES['food_image']) && $_FILES['food_image']['error'] === UPLOAD_ERR_OK ? $_FILES['food_image'] : null;

        if (dbUpdateFood($foodId, $data, $uploadedFile)) {
            header("Location: index.php?msg=updated");
            exit;
        } else {
            $error = 'Failed to update food item.';
        }
    }
    }
}

// Format discount timestamps for HTML datetime-local input
$discountStartVal = !empty($existingDisc['start_at']) ? date('Y-m-d\TH:i', strtotime($existingDisc['start_at'])) : '';
$discountEndVal = !empty($existingDisc['end_at']) ? date('Y-m-d\TH:i', strtotime($existingDisc['end_at'])) : '';
$discountPercentVal = $existingDisc['discount_percent'] ?? 0;

$currentImage = function_exists('getFoodImage') ? getFoodImage($food['image'] ?: $food['name']) : 'assets/images/placeholders/food-placeholder.jpg';

$pageTitle = 'Edit Food';
$activeMenu = 'foods';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Admin Main Content -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0" style="max-width: 1000px;">

        <!-- Breadcrumb & Header -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb extra-small">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Food Management</a></li>
                <li class="breadcrumb-item active text-primary" aria-current="page">Edit Food #<?= $food['id']; ?></li>
            </ol>
        </nav>

        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
            <div>
                <h1 class="admin-title fw-bold mb-1">Edit Food: <?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="text-muted small mb-0">Modify food specifications, price, promotional discount, availability, or image.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="../../food-details.php?food=<?= urlencode($food['slug']); ?>" target="_blank" class="btn btn-outline-pink btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-eye"></i>
                    <span>Store Preview</span>
                </a>
                <a href="index.php" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Foods</span>
                </a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><strong>Error!</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Form Content -->
        <form method="POST" action="edit.php?id=<?= $food['id']; ?>
            <?= csrfField(); ?>" enctype="multipart/form-data" class="needs-validation" id="foodForm">
            <div class="row g-4">

                <!-- Left Column: Primary Details -->
                <div class="col-12 col-lg-7">
                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <h5 class="fw-bold mb-3 pb-2 border-bottom text-dark">Basic Information</h5>

                        <div class="mb-3">
                            <label for="foodName" class="form-label small fw-bold">Food Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="foodName" class="form-control" required value="<?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="foodCategory" class="form-label small fw-bold">Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="foodCategory" class="form-select" required>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= $c['id']; ?>" <?= ($food['category_id'] == $c['id']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="foodPrice" class="form-label small fw-bold">Base Price ($) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">$</span>
                                    <input type="number" name="price" id="foodPrice" step="0.01" min="0.01" class="form-control" required value="<?= htmlspecialchars(number_format($food['original_price'], 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="foodDescription" class="form-label small fw-bold">Description</label>
                            <textarea name="description" id="foodDescription" rows="3" class="form-control"><?= htmlspecialchars($food['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="foodAvailability" class="form-label small fw-bold">Store Availability</label>
                                <select name="is_available" id="foodAvailability" class="form-select">
                                    <option value="1" <?= ($food['is_available'] == 1) ? 'selected' : ''; ?>>Available (In Stock)</option>
                                    <option value="0" <?= ($food['is_available'] == 0) ? 'selected' : ''; ?>>SOLD OUT</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 d-flex flex-column justify-content-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" <?= !empty($food['is_featured']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label small fw-semibold" for="isFeatured">Featured Food</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_popular" id="isPopular" <?= !empty($food['is_popular']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label small fw-semibold" for="isPopular">Popular Item</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Discount Scheduling -->
                    <div class="card border-0 shadow-sm p-4">
                        <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                            <h5 class="fw-bold mb-0 text-dark">Promotional Discount</h5>
                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small">Auto-Calculating</span>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-4">
                                <label for="discountPercent" class="form-label small fw-bold">Discount Percentage</label>
                                <div class="input-group">
                                    <input type="number" name="discount_percent" id="discountPercent" min="0" max="100" class="form-control" placeholder="0" value="<?= htmlspecialchars((string)$discountPercentVal, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="input-group-text bg-light">%</span>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="discountStart" class="form-label small fw-bold">Start Date & Time</label>
                                <input type="datetime-local" name="discount_start" id="discountStart" class="form-control" value="<?= htmlspecialchars($discountStartVal, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="discountEnd" class="form-label small fw-bold">End Date & Time</label>
                                <input type="datetime-local" name="discount_end" id="discountEnd" class="form-control" value="<?= htmlspecialchars($discountEndVal, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <p class="text-muted extra-small mb-0">
                            <i class="bi bi-info-circle me-1"></i> Changes made here update the customer store menu immediately.
                        </p>
                    </div>
                </div>

                <!-- Right Column: Image Upload & Live Preview Card -->
                <div class="col-12 col-lg-5">
                    <!-- Image Upload -->
                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <h5 class="fw-bold mb-3 pb-2 border-bottom text-dark">Food Image</h5>

                        <div class="admin-image-dropzone text-center p-4 border rounded bg-light mb-3 position-relative" id="imageDropzone">
                            <input type="file" name="food_image" id="foodImageInput" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" accept="image/jpeg,image/png,image/webp">
                            <div id="dropzonePlaceholder" class="d-none">
                                <i class="bi bi-cloud-arrow-up fs-1 text-primary mb-2 d-block"></i>
                                <span class="d-block fw-bold text-dark small">Drag and drop food image here</span>
                                <span class="text-muted extra-small d-block mb-2">or click to browse from device</span>
                                <span class="badge bg-white text-muted border extra-small">JPG, PNG, WEBP</span>
                            </div>
                            <div id="imagePreviewContainer">
                                <img id="imagePreview" src="../../<?= htmlspecialchars($currentImage, ENT_QUOTES, 'UTF-8'); ?>" alt="Current Image" class="img-fluid rounded shadow-sm mb-2" style="max-height: 180px; object-fit: cover;">
                                <div class="d-flex justify-content-center gap-2">
                                    <span class="badge bg-light text-muted border extra-small">Click area to upload replacement</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Pricing Preview Card -->
                    <div class="card border-0 shadow-sm p-4 bg-pink-soft border border-pink-subtle">
                        <h6 class="fw-bold text-primary-accent mb-3 text-uppercase extra-small">Live Store Pricing Preview</h6>
                        
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small">Original Price:</span>
                            <span class="fw-bold text-dark" id="previewOrigPrice">$0.00</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small">Discount Deducted:</span>
                            <span class="fw-bold text-danger" id="previewDiscountDeduction">-$0.00 (0%)</span>
                        </div>

                        <hr class="my-2 border-pink-subtle">

                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="fw-bold text-dark">Final Store Price:</span>
                            <span class="fw-bold fs-4 text-primary-accent" id="previewFinalPrice">$0.00</span>
                        </div>

                        <div id="previewBadgeContainer" class="text-center">
                            <span class="badge bg-success w-100 py-2" id="previewStatusBadge">AVAILABLE IN STORE</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Submit Button Footer -->
            <div class="d-flex align-items-center justify-content-end gap-3 mt-4 pt-3 border-top">
                <a href="index.php" class="btn btn-light border px-4">Cancel</a>
                <button type="submit" class="btn btn-auth-primary px-4 shadow-sm">Save Changes</button>
            </div>
        </form>

    </div>
</main>

<script>
// Image Preview Script
const foodImageInput = document.getElementById('foodImageInput');
const dropzonePlaceholder = document.getElementById('dropzonePlaceholder');
const imagePreviewContainer = document.getElementById('imagePreviewContainer');
const imagePreview = document.getElementById('imagePreview');

if (foodImageInput) {
    foodImageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                dropzonePlaceholder.classList.add('d-none');
                imagePreviewContainer.classList.remove('d-none');
            }
            reader.readAsDataURL(file);
        }
    });
}

// Live Pricing Calculation
const priceInput = document.getElementById('foodPrice');
const discountInput = document.getElementById('discountPercent');
const availSelect = document.getElementById('foodAvailability');
const previewOrig = document.getElementById('previewOrigPrice');
const previewDeduct = document.getElementById('previewDiscountDeduction');
const previewFinal = document.getElementById('previewFinalPrice');
const previewBadge = document.getElementById('previewStatusBadge');

function updateLivePricing() {
    const price = parseFloat(priceInput.value) || 0;
    const disc = parseInt(discountInput.value) || 0;
    const isAvail = availSelect.value === '1';

    const deductAmount = (price * disc) / 100;
    const finalPrice = Math.max(0, price - deductAmount);

    previewOrig.textContent = '$' + price.toFixed(2);
    previewDeduct.textContent = '-$' + deductAmount.toFixed(2) + ' (' + disc + '%)';
    previewFinal.textContent = '$' + finalPrice.toFixed(2);

    if (!isAvail) {
        previewBadge.className = 'badge bg-danger w-100 py-2';
        previewBadge.textContent = 'SOLD OUT (ADD TO CART DISABLED)';
    } else if (disc > 0) {
        previewBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning w-100 py-2';
        previewBadge.textContent = 'AVAILABLE ON PROMO (' + disc + '% OFF)';
    } else {
        previewBadge.className = 'badge bg-success w-100 py-2';
        previewBadge.textContent = 'AVAILABLE IN STORE';
    }
}

if (priceInput && discountInput && availSelect) {
    priceInput.addEventListener('input', updateLivePricing);
    discountInput.addEventListener('input', updateLivePricing);
    availSelect.addEventListener('change', updateLivePricing);
    updateLivePricing();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
