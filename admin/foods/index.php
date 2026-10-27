<?php
/**
 * FastBite - Admin Foods Management
 * STEP 21: FastBite Admin Menu & Food Management System
 * 
 * Displays list of all foods with dynamic pricing, real-time filters,
 * quick availability toggling, discount badges, and responsive design.
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(dirname(__DIR__)));
}
require_once FASTBITE_ROOT_DIR . '/config/database.php';
require_once FASTBITE_ROOT_DIR . '/includes/auth.php';
require_once FASTBITE_ROOT_DIR . '/includes/image-helper.php';

requireAdmin('../login.php');

// Handle POST actions (Availability toggle, Delete)
$msg = isset($_GET['msg']) ? trim($_GET['msg']) : '';
$error = isset($_GET['error']) ? trim($_GET['error']) : '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';
        $foodId = (int)($_POST['food_id'] ?? 0);

        if ($action === 'toggle_availability' && $foodId > 0) {
            $newStatus = (int)($_POST['is_available'] ?? 1);
            if (dbToggleFoodAvailability($foodId, $newStatus)) {
                $statusText = $newStatus === 1 ? 'marked as Available' : 'marked as SOLD OUT';
                header("Location: index.php?msg=" . urlencode("Food item successfully {$statusText}."));
                exit;
            } else {
                $error = 'Failed to update food availability.';
            }
        } elseif ($action === 'delete_food' && $foodId > 0) {
            if (dbDeleteFood($foodId)) {
                header("Location: index.php?msg=deleted");
                exit;
            } else {
                $error = 'Failed to delete food item.';
            }
        }
    }
}

// Fetch categories for filter dropdown
$categories = dbGetAllCategories();

// Parse filter parameters from GET
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$availFilter = isset($_GET['availability']) ? trim($_GET['availability']) : 'all';
$discountFilter = isset($_GET['discount']) ? trim($_GET['discount']) : 'all';

// Build filter criteria
$filters = [];
if (!empty($searchQuery)) {
    $filters['search'] = $searchQuery;
}
if (!empty($categoryFilter) && $categoryFilter !== 'all') {
    $filters['category_id'] = $categoryFilter;
}
if ($availFilter === 'available') {
    $filters['is_available'] = 1;
} elseif ($availFilter === 'sold_out') {
    $filters['is_available'] = 0;
}
if ($discountFilter === 'discounted') {
    $filters['has_discount'] = 'yes';
} elseif ($discountFilter === 'regular') {
    $filters['has_discount'] = 'no';
}

// Fetch foods from DB with pricing and discounts
$foods = dbGetAllFoodsWithPricing($filters);

// Quick summary metrics (unfiltered)
$allFoods = dbGetAllFoodsWithPricing([]);
$totalCount = count($allFoods);
$availableCount = count(array_filter($allFoods, fn($f) => $f['is_available'] === true));
$soldOutCount = count(array_filter($allFoods, fn($f) => $f['is_available'] === false));
$discountCount = count(array_filter($allFoods, fn($f) => $f['has_discount'] === true));
$categoryCount = count($categories);

$pageTitle = 'Food Management';
$activeMenu = 'foods';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Admin Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0">

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block text-uppercase fw-bold text-primary mb-1">FOOD MANAGEMENT</span>
                <h1 class="admin-title fw-bold mb-1">Food Management</h1>
                <p class="admin-desc text-muted mb-0">Manage food items, prices, discounts, availability, and customer menu visibility.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="create.php" class="btn btn-auth-primary d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>+ Add New Food</span>
                </a>
                <a href="../discounts/index.php" class="btn btn-outline-pink d-inline-flex align-items-center gap-2">
                    <i class="bi bi-percent"></i>
                    <span>Manage Discounts</span>
                </a>
            </div>
        </div>

        <!-- Notification Alerts -->
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div>
                    <?php if ($msg === 'created'): ?>
                        <strong>Success!</strong> New food item created and image stored successfully.
                    <?php elseif ($msg === 'updated'): ?>
                        <strong>Success!</strong> Food item details, pricing, and image updated successfully.
                    <?php elseif ($msg === 'deleted'): ?>
                        <strong>Success!</strong> Food item deleted successfully from the menu.
                    <?php else: ?>
                        <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><strong>Error!</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- 5 Summary Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl">
                <div class="card stat-hover-card h-100 border-0 shadow-sm p-3 rounded-4 border-start border-4 border-dark">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted extra-small text-uppercase fw-bold d-block">Total Foods</span>
                            <h3 class="fw-bold mb-0 text-dark"><?= $totalCount; ?></h3>
                        </div>
                        <div class="stat-icon-wrap bg-pink-soft text-primary-accent rounded-circle p-2">
                            <i class="bi bi-egg-fried fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl">
                <div class="card stat-hover-card h-100 border-0 shadow-sm p-3 rounded-4 border-start border-4 border-success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted extra-small text-uppercase fw-bold d-block">Available</span>
                            <h3 class="fw-bold mb-0 text-success"><?= $availableCount; ?></h3>
                        </div>
                        <div class="stat-icon-wrap bg-success-subtle text-success rounded-circle p-2">
                            <i class="bi bi-check-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl">
                <div class="card stat-hover-card h-100 border-0 shadow-sm p-3 rounded-4 border-start border-4 border-danger">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted extra-small text-uppercase fw-bold d-block">Sold Out</span>
                            <h3 class="fw-bold mb-0 text-danger"><?= $soldOutCount; ?></h3>
                        </div>
                        <div class="stat-icon-wrap bg-danger-subtle text-danger rounded-circle p-2">
                            <i class="bi bi-x-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl">
                <div class="card stat-hover-card h-100 border-0 shadow-sm p-3 rounded-4 border-start border-4 border-warning">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted extra-small text-uppercase fw-bold d-block">On Discount</span>
                            <h3 class="fw-bold mb-0 text-warning-emphasis"><?= $discountCount; ?></h3>
                        </div>
                        <div class="stat-icon-wrap bg-warning-subtle text-warning-emphasis rounded-circle p-2">
                            <i class="bi bi-percent fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 col-xl">
                <div class="card stat-hover-card h-100 border-0 shadow-sm p-3 rounded-4 border-start border-4 border-info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted extra-small text-uppercase fw-bold d-block">Categories</span>
                            <h3 class="fw-bold mb-0 text-info-emphasis"><?= $categoryCount; ?></h3>
                        </div>
                        <div class="stat-icon-wrap bg-info-subtle text-info-emphasis rounded-circle p-2">
                            <i class="bi bi-tags fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <form method="GET" action="index.php" class="row g-3 align-items-end">
                    <div class="col-12 col-md-4 col-lg-4">
                        <label for="adminFoodSearch" class="form-label extra-small text-muted text-uppercase fw-bold mb-1">Search Food</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" id="adminFoodSearch" class="form-control border-start-0 ps-0" placeholder="Search name, description..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="adminCatFilter" class="form-label extra-small text-muted text-uppercase fw-bold mb-1">Category</label>
                        <select name="category" id="adminCatFilter" class="form-select" onchange="this.form.submit()">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id']; ?>" <?= ($categoryFilter == $c['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="adminAvailFilter" class="form-label extra-small text-muted text-uppercase fw-bold mb-1">Availability</label>
                        <select name="availability" id="adminAvailFilter" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= ($availFilter === 'all') ? 'selected' : ''; ?>>All Status</option>
                            <option value="available" <?= ($availFilter === 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="sold_out" <?= ($availFilter === 'sold_out') ? 'selected' : ''; ?>>Sold Out</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="adminDiscFilter" class="form-label extra-small text-muted text-uppercase fw-bold mb-1">Discount</label>
                        <select name="discount" id="adminDiscFilter" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= ($discountFilter === 'all') ? 'selected' : ''; ?>>All Pricing</option>
                            <option value="discounted" <?= ($discountFilter === 'discounted') ? 'selected' : ''; ?>>On Discount</option>
                            <option value="regular" <?= ($discountFilter === 'regular') ? 'selected' : ''; ?>>Regular Price</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-dark flex-grow-1">Filter</button>
                        <a href="index.php" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Food Items Table (Desktop & Tablet) -->
        <div class="card border-0 shadow-sm d-none d-lg-block mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-uppercase text-muted extra-small fw-bold" style="width: 70px;">Image</th>
                            <th class="py-3 text-uppercase text-muted extra-small fw-bold">Food Details</th>
                            <th class="py-3 text-uppercase text-muted extra-small fw-bold">Category</th>
                            <th class="py-3 text-uppercase text-muted extra-small fw-bold">Price</th>
                            <th class="py-3 text-uppercase text-muted extra-small fw-bold">Discount</th>
                            <th class="py-3 text-uppercase text-muted extra-small fw-bold">Final Price</th>
                            <th class="py-3 text-uppercase text-muted extra-small fw-bold">Availability</th>
                            <th class="pe-4 py-3 text-uppercase text-muted extra-small fw-bold text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($foods)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                                    <p class="mb-0 fw-semibold">No food items found matching your filter criteria.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($foods as $food): ?>
                                <?php
                                    $imgSrc = function_exists('getFoodImage') ? getFoodImage($food['image'] ?: $food['name']) : 'assets/images/placeholders/food-placeholder.jpg';
                                ?>
                                <tr>
                                    <!-- Image -->
                                    <td class="ps-4">
                                        <div class="rounded overflow-hidden border bg-light position-relative" style="width: 52px; height: 52px;">
                                            <img src="../../<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?>" class="w-100 h-100 object-fit-cover" loading="lazy">
                                        </div>
                                    </td>

                                    <!-- Details -->
                                    <td>
                                        <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <p class="text-muted extra-small text-truncate mb-0" style="max-width: 260px;"><?= htmlspecialchars($food['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                        <?php if (!empty($food['is_featured'])): ?>
                                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small mt-1">Featured</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Category -->
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($food['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>

                                    <!-- Original Price -->
                                    <td>
                                        <span class="fw-bold text-muted">$<?= number_format($food['original_price'], 2); ?></span>
                                    </td>

                                    <!-- Discount -->
                                    <td>
                                        <?php if ($food['has_discount']): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">
                                                -<?= $food['discount_percent']; ?>% OFF
                                            </span>
                                            <span class="d-block extra-small text-muted"><?= ucfirst($food['discount_source']); ?> promo</span>
                                        <?php else: ?>
                                            <span class="text-muted extra-small">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Final Price -->
                                    <td>
                                        <span class="fw-bold text-primary-accent fs-6">$<?= number_format($food['final_price'], 2); ?></span>
                                    </td>

                                    <!-- Availability Toggle -->
                                    <td>
                                        <form method="POST" action="index.php" class="d-inline">
            <?= csrfField(); ?>
                                            <input type="hidden" name="action" value="toggle_availability">
                                            <input type="hidden" name="food_id" value="<?= $food['id']; ?>">
                                            <?php if ($food['is_available']): ?>
                                                <input type="hidden" name="is_available" value="0">
                                                <button type="submit" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1" title="Click to mark SOLD OUT">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    <span>Available</span>
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="is_available" value="1">
                                                <button type="submit" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1" title="Click to mark AVAILABLE">
                                                    <i class="bi bi-x-circle-fill"></i>
                                                    <span>SOLD OUT</span>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>

                                    <!-- Actions -->
                                    <td class="pe-4 text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="../../food-details.php?food=<?= urlencode($food['slug']); ?>" target="_blank" class="btn btn-light border" title="Preview on Store">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?= $food['id']; ?>" class="btn btn-light border text-primary" title="Edit Food">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-light border text-danger" title="Delete Food" onclick="openDeleteModal(<?= $food['id']; ?>, '<?= htmlspecialchars(addslashes($food['name']), ENT_QUOTES, 'UTF-8'); ?>')">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Food Cards (under 992px) -->
        <div class="d-lg-none">
            <?php if (empty($foods)): ?>
                <div class="card border-0 shadow-sm text-center py-5">
                    <p class="text-muted mb-0">No food items found.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($foods as $food): ?>
                        <?php
                            $imgSrc = function_exists('getFoodImage') ? getFoodImage($food['image'] ?: $food['name']) : 'assets/images/placeholders/food-placeholder.jpg';
                        ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm p-3">
                                <div class="d-flex gap-3 align-items-start mb-3">
                                    <div class="rounded overflow-hidden border flex-shrink-0" style="width: 70px; height: 70px;">
                                        <img src="../../<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?>" class="w-100 h-100 object-fit-cover">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                            <?php if ($food['is_available']): ?>
                                                <span class="badge bg-success-subtle text-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger">Sold Out</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-muted extra-small d-block mb-1"><?= htmlspecialchars($food['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <div class="d-flex align-items-baseline gap-2">
                                            <span class="fw-bold text-primary-accent">$<?= number_format($food['final_price'], 2); ?></span>
                                            <?php if ($food['has_discount']): ?>
                                                <span class="text-muted text-decoration-line-through extra-small">$<?= number_format($food['original_price'], 2); ?></span>
                                                <span class="badge bg-danger extra-small"><?= $food['discount_percent']; ?>% OFF</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between pt-2 border-top gap-2">
                                    <form method="POST" action="index.php" class="d-inline">
            <?= csrfField(); ?>
                                        <input type="hidden" name="action" value="toggle_availability">
                                        <input type="hidden" name="food_id" value="<?= $food['id']; ?>">
                                        <?php if ($food['is_available']): ?>
                                            <input type="hidden" name="is_available" value="0">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Mark Sold Out</button>
                                        <?php else: ?>
                                            <input type="hidden" name="is_available" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-success">Mark Available</button>
                                        <?php endif; ?>
                                    </form>

                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?= $food['id']; ?>" class="btn btn-light border text-primary">Edit</a>
                                        <button type="button" class="btn btn-light border text-danger" onclick="openDeleteModal(<?= $food['id']; ?>, '<?= htmlspecialchars(addslashes($food['name']), ENT_QUOTES, 'UTF-8'); ?>')">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- Delete Food Confirmation Modal -->
<div class="modal fade" id="deleteFoodModal" tabindex="-1" aria-labelledby="deleteFoodModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="deleteFoodModalLabel">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Delete Food Item?</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-1">Are you sure you want to delete <strong id="deleteFoodName" class="text-dark"></strong>?</p>
                <p class="text-muted small mb-0">This will remove the food item and its pricing rules from the store menu immediately.</p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <form method="POST" action="index.php">
            <?= csrfField(); ?>
                    <input type="hidden" name="action" value="delete_food">
                    <input type="hidden" name="food_id" id="deleteFoodId" value="">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Food</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openDeleteModal(id, name) {
    document.getElementById('deleteFoodId').value = id;
    document.getElementById('deleteFoodName').textContent = name;
    var modal = new bootstrap.Modal(document.getElementById('deleteFoodModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
