<?php
/**
 * FastBite - Admin Category Management
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

// Handle POST actions (Create, Update, Delete, Toggle)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_category') {
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? 'bi bi-grid');
            $description = trim($_POST['description'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($name)) {
                $error = 'Please provide a category name.';
            } else {
                $newId = dbCreateCategory([
                    'name' => $name,
                    'icon' => $icon,
                    'description' => $description,
                    'is_active' => $isActive
                ]);
                if ($newId > 0) {
                    header("Location: index.php?msg=" . urlencode("Category created successfully."));
                    exit;
                } else {
                    $error = 'Failed to create category.';
                }
            }
        } elseif ($action === 'update_category') {
            $id = (int)($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? 'bi bi-grid');
            $description = trim($_POST['description'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($id > 0 && !empty($name)) {
                if (dbUpdateCategory($id, [
                    'name' => $name,
                    'icon' => $icon,
                    'description' => $description,
                    'is_active' => $isActive
                ])) {
                    header("Location: index.php?msg=" . urlencode("Category updated successfully."));
                    exit;
                } else {
                    $error = 'Failed to update category.';
                }
            } else {
                $error = 'Invalid category parameters.';
            }
        } elseif ($action === 'delete_category') {
            $id = (int)($_POST['category_id'] ?? 0);
            $cat = dbGetCategoryById($id);
            if ($cat) {
                $pdo = getDbConnection();
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `foods` WHERE `category_id` = ?");
                $stmt->execute([$id]);
                $foodCount = (int)$stmt->fetchColumn();

                if ($foodCount > 0) {
                    $error = "Cannot delete category '{$cat['name']}' because it still contains {$foodCount} food item(s). Reassign or delete the food items first.";
                } else {
                    if (dbDeleteCategory($id)) {
                        header("Location: index.php?msg=" . urlencode("Category deleted successfully."));
                        exit;
                    } else {
                        $error = 'Failed to delete category.';
                    }
                }
            }
        }
    }
}

// Fetch all categories with food counts and discounts
$categories = dbGetAllCategoriesWithCounts();

$pageTitle = 'Category Management';
$activeMenu = 'categories';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Admin Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0">

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block text-uppercase fw-bold text-primary mb-1">MENU CATEGORIES</span>
                <h1 class="admin-title fw-bold mb-1">Category Management</h1>
                <p class="admin-desc text-muted mb-0">Organize food categories, icons, active visibility, and category promotions.</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-auth-primary d-inline-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>+ Add New Category</span>
                </button>
                <a href="../discounts/index.php?type=category" class="btn btn-outline-pink d-inline-flex align-items-center gap-2">
                    <i class="bi bi-percent"></i>
                    <span>Category Discounts</span>
                </a>
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

        <!-- Categories Grid -->
        <div class="row g-3 g-md-4">
            <?php foreach ($categories as $cat): ?>
                <?php
                    $imgSrc = function_exists('getCategoryImage') ? getCategoryImage($cat['slug'] . '.jpg') : 'assets/images/placeholders/category-placeholder.jpg';
                ?>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm category-admin-card p-3 position-relative">
                        <!-- Thumbnail & Icon -->
                        <div class="rounded overflow-hidden border bg-light position-relative mb-3" style="aspect-ratio: 16/10;">
                            <img src="../../<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>" class="w-100 h-100 object-fit-cover">
                            
                            <div class="position-absolute top-0 start-0 m-2">
                                <span class="badge bg-white text-dark shadow-sm d-inline-flex align-items-center gap-1">
                                    <i class="<?= htmlspecialchars($cat['icon'], ENT_QUOTES, 'UTF-8'); ?> text-primary"></i>
                                    <span><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                            </div>

                            <?php if ($cat['has_discount']): ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-danger shadow-sm"><?= $cat['discount_percent']; ?>% PROMO</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Info -->
                        <div class="d-flex align-items-start justify-content-between mb-1">
                            <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                            <span class="badge bg-light text-muted border"><?= $cat['food_count']; ?> foods</span>
                        </div>
                        <p class="text-muted extra-small text-truncate mb-3"><?= htmlspecialchars($cat['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>

                        <!-- Actions -->
                        <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-auto">
                            <div>
                                <?php if ($cat['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success extra-small">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary extra-small">Hidden</span>
                                <?php endif; ?>
                            </div>

                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-light border text-primary" 
                                        onclick="openEditCategoryModal(<?= $cat['id']; ?>, '<?= htmlspecialchars(addslashes($cat['name']), ENT_QUOTES, 'UTF-8'); ?>', '<?= htmlspecialchars(addslashes($cat['icon']), ENT_QUOTES, 'UTF-8'); ?>', '<?= htmlspecialchars(addslashes($cat['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>', <?= $cat['is_active']; ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-light border text-danger" 
                                        onclick="openDeleteCategoryModal(<?= $cat['id']; ?>, '<?= htmlspecialchars(addslashes($cat['name']), ENT_QUOTES, 'UTF-8'); ?>', <?= $cat['food_count']; ?>)">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</main>

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="index.php">
            <?= csrfField(); ?>
                <input type="hidden" name="action" value="create_category">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="createCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label for="catName" class="form-label small fw-bold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="catName" class="form-control" placeholder="e.g. Seafood & Platters" required>
                    </div>
                    <div class="mb-3">
                        <label for="catIcon" class="form-label small fw-bold">Bootstrap Icon Class</label>
                        <input type="text" name="icon" id="catIcon" class="form-control" placeholder="e.g. bi bi-egg-fried" value="bi bi-grid">
                    </div>
                    <div class="mb-3">
                        <label for="catDesc" class="form-label small fw-bold">Description</label>
                        <textarea name="description" id="catDesc" rows="2" class="form-control" placeholder="Brief summary of foods in this category..."></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="catActive" checked>
                        <label class="form-check-label small fw-semibold" for="catActive">Category is Active on Store Menu</label>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-auth-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="index.php">
            <?= csrfField(); ?>
                <input type="hidden" name="action" value="update_category">
                <input type="hidden" name="category_id" id="editCatId" value="">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label for="editCatName" class="form-label small fw-bold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editCatName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCatIcon" class="form-label small fw-bold">Bootstrap Icon Class</label>
                        <input type="text" name="icon" id="editCatIcon" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="editCatDesc" class="form-label small fw-bold">Description</label>
                        <textarea name="description" id="editCatDesc" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="editCatActive">
                        <label class="form-check-label small fw-semibold" for="editCatActive">Category is Active on Store Menu</label>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-auth-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="deleteCategoryModalLabel">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Delete Category?</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-1">Are you sure you want to delete <strong id="deleteCategoryName" class="text-dark"></strong>?</p>
                <div id="deleteCategoryWarning" class="alert alert-warning extra-small py-2 mt-2 d-none">
                    <i class="bi bi-info-circle me-1"></i> This category has assigned food items. You must delete or reassign foods first.
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <form method="POST" action="index.php">
            <?= csrfField(); ?>
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="category_id" id="deleteCategoryId" value="">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmDeleteCategory">Delete Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openEditCategoryModal(id, name, icon, desc, isActive) {
    document.getElementById('editCatId').value = id;
    document.getElementById('editCatName').value = name;
    document.getElementById('editCatIcon').value = icon;
    document.getElementById('editCatDesc').value = desc;
    document.getElementById('editCatActive').checked = (isActive == 1);
    var modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

function openDeleteCategoryModal(id, name, foodCount) {
    document.getElementById('deleteCategoryId').value = id;
    document.getElementById('deleteCategoryName').textContent = name;
    const warning = document.getElementById('deleteCategoryWarning');
    const deleteBtn = document.getElementById('btnConfirmDeleteCategory');
    if (foodCount > 0) {
        warning.classList.remove('d-none');
        deleteBtn.disabled = true;
    } else {
        warning.classList.add('d-none');
        deleteBtn.disabled = false;
    }
    var modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
