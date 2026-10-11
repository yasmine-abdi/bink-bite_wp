<?php
/**
 * FastBite - Admin Discounts Management
 * STEP 21: FastBite Admin Menu & Food Management System
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(dirname(__DIR__)));
}
require_once FASTBITE_ROOT_DIR . '/config/database.php';
require_once FASTBITE_ROOT_DIR . '/includes/auth.php';
require_once FASTBITE_ROOT_DIR . '/includes/image-helper.php';

requireAdmin('../login.php');

$msg = isset($_GET['msg']) ? trim($_GET['msg']) : '';
$error = isset($_GET['error']) ? trim($_GET['error']) : '';

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['discount_id'] ?? 0);

        if ($action === 'create_discount') {
            $name = trim($_POST['name'] ?? '');
            $type = ($_POST['type'] === 'category') ? 'category' : 'food';
            $foodId = ($type === 'food') ? (int)$_POST['food_id'] : null;
            $categoryId = ($type === 'category') ? (int)$_POST['category_id'] : null;
            $percent = max(1, min(100, (int)$_POST['discount_percent']));
            $startAt = !empty($_POST['start_at']) ? trim($_POST['start_at']) : null;
            $endAt = !empty($_POST['end_at']) ? trim($_POST['end_at']) : null;

            if (empty($name)) {
                $error = 'Please provide a discount promotion name.';
            } elseif ($type === 'food' && !$foodId) {
                $error = 'Please select a valid food target.';
            } elseif ($type === 'category' && !$categoryId) {
                $error = 'Please select a valid category target.';
            } else {
                $newId = dbCreateDiscount([
                    'name' => $name,
                    'type' => $type,
                    'food_id' => $foodId,
                    'category_id' => $categoryId,
                    'discount_percent' => $percent,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'is_active' => 1
                ]);
                if ($newId > 0) {
                    header("Location: index.php?msg=" . urlencode("Discount promotion created and scheduled successfully."));
                    exit;
                } else {
                    $error = 'Failed to create discount.';
                }
            }
        } elseif ($action === 'toggle_active' && $id > 0) {
            $isActive = (int)($_POST['is_active'] ?? 1);
            if (dbToggleDiscountActive($id, $isActive)) {
                header("Location: index.php?msg=" . urlencode("Discount status updated."));
                exit;
            } else {
                $error = 'Failed to toggle discount.';
            }
        } elseif ($action === 'delete_discount' && $id > 0) {
            if (dbDeleteDiscount($id)) {
                header("Location: index.php?msg=" . urlencode("Discount promotion removed."));
                exit;
            } else {
                $error = 'Failed to delete discount.';
            }
        }
    }
}

// Filter by type
$typeFilter = $_GET['type'] ?? 'all';
$filters = [];
if ($typeFilter !== 'all') {
    $filters['type'] = $typeFilter;
}

$discounts = dbGetAllDiscounts($filters);
$allFoods = dbGetAllFoodsWithPricing([]);
$allCategories = dbGetAllCategories();

$pageTitle = 'Discount Management';
$activeMenu = 'discounts';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Admin Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0">

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block text-uppercase fw-bold text-primary mb-1">PROMOTIONAL PRICING</span>
                <h1 class="admin-title fw-bold mb-1">Discount Management</h1>
                <p class="admin-desc text-muted mb-0">Create food-specific and category-wide percentage discounts with automated expiry timers.</p>
            </div>
            <div>
                <button type="button" class="btn btn-auth-primary d-inline-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#createDiscountModal">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>+ Create Promotion</span>
                </button>
            </div>
        </div>

        <!-- Priority Rule Info Banner -->
        <div class="alert alert-info border-info-subtle d-flex align-items-start gap-3 shadow-sm mb-4">
            <i class="bi bi-info-circle-fill fs-5 text-info mt-1"></i>
            <div class="small">
                <strong>Discount Priority Rule:</strong>
                Individual food item discounts always take precedence over category discounts. Discounts do not stack. Once a promotion's timer expires, the server immediately and automatically restores the item's original base price.
            </div>
        </div>

        <!-- Notification Alerts -->
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Discounts Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-uppercase text-muted extra-small fw-bold">Promotion Name</th>
                            <th class="py-3 text-uppercase text-muted extra-small fw-bold">Type</th>
                            <th class="py-3 text-uppercase text-muted extra-small fw-bold">Target</th>
                            <th class="py-3 text-uppercase text-muted extra-small fw-bold">Discount</th>
                            <th class="py-3 text-uppercase text-muted extra-small fw-bold">Schedule Period</th>
                            <th class="py-3 text-uppercase text-muted extra-small fw-bold">Status</th>
                            <th class="pe-4 py-3 text-uppercase text-muted extra-small fw-bold text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($discounts)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-percent fs-1 d-block mb-2 text-muted"></i>
                                    <p class="mb-0 fw-semibold">No active or scheduled promotions found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($discounts as $d): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($d['type'] === 'food'): ?>
                                            <span class="badge bg-light text-dark border">Specific Food</span>
                                        <?php else: ?>
                                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle">Category Wide</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($d['target_label'] ?? (($d['type'] === 'food') ? ($d['food_name'] ?? 'Specific Food') : ($d['category_name'] ?? 'Category Wide')), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger fs-6">-<?= $d['discount_percent']; ?>% OFF</span>
                                    </td>
                                    <td>
                                        <div class="extra-small text-muted">
                                            <div><strong>Start:</strong> <?= !empty($d['start_at']) ? date('M d, Y h:i A', strtotime($d['start_at'])) : 'Immediate'; ?></div>
                                            <div><strong>End:</strong> <?= !empty($d['end_at']) ? date('M d, Y h:i A', strtotime($d['end_at'])) : 'No Expiry'; ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= $d['status_badge']; ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="btn-group btn-group-sm">
                                            <form method="POST" action="index.php" class="d-inline">
            <?= csrfField(); ?>
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="discount_id" value="<?= $d['id']; ?>">
                                                <input type="hidden" name="is_active" value="<?= (int)$d['is_active'] === 1 ? 0 : 1; ?>">
                                                <button type="submit" class="btn btn-light border text-muted" title="<?= (int)$d['is_active'] === 1 ? 'Disable Promo' : 'Enable Promo'; ?>">
                                                    <i class="bi bi-power"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="index.php" class="d-inline" onsubmit="return confirm('Delete this discount promotion?');">
            <?= csrfField(); ?>
                                                <input type="hidden" name="action" value="delete_discount">
                                                <input type="hidden" name="discount_id" value="<?= $d['id']; ?>">
                                                <button type="submit" class="btn btn-light border text-danger" title="Delete Promo">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<!-- Create Discount Modal -->
<div class="modal fade" id="createDiscountModal" tabindex="-1" aria-labelledby="createDiscountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="index.php">
            <?= csrfField(); ?>
                <input type="hidden" name="action" value="create_discount">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="createDiscountModalLabel">Create New Promotional Discount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label for="discName" class="form-label small fw-bold">Promotion Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="discName" class="form-control" placeholder="e.g. Weekend Flash Sale" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Discount Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="typeFood" value="food" checked onchange="toggleDiscountTargetSelect()">
                                <label class="form-check-label small" for="typeFood">Individual Food</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="typeCategory" value="category" onchange="toggleDiscountTargetSelect()">
                                <label class="form-check-label small" for="typeCategory">Entire Category</label>
                            </div>
                        </div>
                    </div>

                    <!-- Target: Food -->
                    <div class="mb-3" id="foodTargetGroup">
                        <label for="targetFoodId" class="form-label small fw-bold">Select Food Item <span class="text-danger">*</span></label>
                        <select name="food_id" id="targetFoodId" class="form-select">
                            <option value="">Choose Food...</option>
                            <?php foreach ($allFoods as $f): ?>
                                <option value="<?= $f['id']; ?>">
                                    <?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8'); ?> ($<?= number_format($f['original_price'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Target: Category -->
                    <div class="mb-3 d-none" id="catTargetGroup">
                        <label for="targetCatId" class="form-label small fw-bold">Select Category <span class="text-danger">*</span></label>
                        <select name="category_id" id="targetCatId" class="form-select">
                            <option value="">Choose Category...</option>
                            <?php foreach ($allCategories as $c): ?>
                                <option value="<?= $c['id']; ?>">
                                    <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="discPercent" class="form-label small fw-bold">Discount Percentage <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="discount_percent" id="discPercent" min="1" max="100" class="form-control" placeholder="15" required>
                            <span class="input-group-text bg-light">%</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="startAt" class="form-label small fw-bold">Start Date/Time</label>
                            <input type="datetime-local" name="start_at" id="startAt" class="form-control">
                        </div>
                        <div class="col-6">
                            <label for="endAt" class="form-label small fw-bold">End Date/Time</label>
                            <input type="datetime-local" name="end_at" id="endAt" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-auth-primary">Create Discount</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDiscountTargetSelect() {
    const isCategory = document.getElementById('typeCategory').checked;
    const foodGroup = document.getElementById('foodTargetGroup');
    const catGroup = document.getElementById('catTargetGroup');
    if (isCategory) {
        foodGroup.classList.add('d-none');
        catGroup.classList.remove('d-none');
    } else {
        foodGroup.classList.remove('d-none');
        catGroup.classList.add('d-none');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
