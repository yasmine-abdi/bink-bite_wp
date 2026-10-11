<?php
/**
 * FastBite - Create Promo Coupon
 * STEP 30: FastBite Customer Experience, Reviews, Favorites & Promotions
 */

require_once __DIR__ . '/../../includes/auth.php';

requireAdmin('../../login.php');

$activeMenu = 'coupons';
$projectName = 'FastBite Admin';
$adminUser = currentUser();
$adminId = (int)$adminUser['id'];

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = "Security validation failed. Please refresh.";
    } else {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $discountType = $_POST['discount_type'] ?? 'percentage';
        $discountValue = (float)($_POST['discount_value'] ?? 0.00);
        $minimumOrder = (float)($_POST['minimum_order'] ?? 0.00);
        $maximumDiscount = !empty($_POST['maximum_discount']) ? (float)$_POST['maximum_discount'] : null;
        $usageLimit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
        $usagePerCustomer = max(1, (int)($_POST['usage_per_customer'] ?? 1));
        $startAt = !empty($_POST['start_at']) ? str_replace('T', ' ', $_POST['start_at']) : null;
        $endAt = !empty($_POST['end_at']) ? str_replace('T', ' ', $_POST['end_at']) : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($code)) {
            $error = "Please enter a coupon code.";
        } elseif ($discountValue <= 0) {
            $error = "Please provide a valid discount value greater than 0.";
        } elseif ($discountType === 'percentage' && $discountValue > 100) {
            $error = "Percentage discount cannot exceed 100%.";
        } else {
            // Check uniqueness
            $existing = dbGetCouponByCode($code);
            if ($existing) {
                $error = "Coupon code '{$code}' already exists. Please choose a unique code.";
            } else {
                $couponId = dbAdminCreateCoupon([
                    'code' => $code,
                    'description' => $description,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'minimum_order' => $minimumOrder,
                    'maximum_discount' => $maximumDiscount,
                    'usage_limit' => $usageLimit,
                    'usage_per_customer' => $usagePerCustomer,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'is_active' => $isActive
                ], $adminId);

                if ($couponId) {
                    header("Location: index.php?msg=" . urlencode("Coupon '{$code}' created successfully!"));
                    exit;
                } else {
                    $error = "Failed to create coupon. Please check inputs.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Coupon - <?= htmlspecialchars($projectName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="admin-dashboard-body">

    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="admin-layout d-flex min-vh-100">
        <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="admin-main-content flex-grow-1 p-3 p-md-4 p-lg-5">
            <div class="container-fluid px-0" style="max-width: 800px;">

                <!-- Header -->
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                    <div>
                        <span class="dashboard-eyebrow d-inline-block">NEW PROMOTION</span>
                        <h1 class="h3 fw-bold text-dark mb-1">Create Promo Coupon</h1>
                    </div>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                        <i class="bi bi-arrow-left"></i>
                        <span>Back to Coupons</span>
                    </a>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                        <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <form method="POST" action="create.php">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="code" class="form-label small fw-bold text-dark">Coupon Code *</label>
                                <input type="text" name="code" id="code" class="form-control text-uppercase font-monospace fw-bold" placeholder="e.g. FLASH20" required value="<?= htmlspecialchars($_POST['code'] ?? ''); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="description" class="form-label small fw-bold text-dark">Description</label>
                                <input type="text" name="description" id="description" class="form-control" placeholder="e.g. 20% off for weekend pizza lovers" value="<?= htmlspecialchars($_POST['description'] ?? ''); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="discount_type" class="form-label small fw-bold text-dark">Discount Type *</label>
                                <select name="discount_type" id="discount_type" class="form-select">
                                    <option value="percentage" <?= (($_POST['discount_type'] ?? '') === 'percentage') ? 'selected' : ''; ?>>Percentage Discount (%)</option>
                                    <option value="fixed" <?= (($_POST['discount_type'] ?? '') === 'fixed') ? 'selected' : ''; ?>>Fixed Amount Discount ($)</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="discount_value" class="form-label small fw-bold text-dark">Discount Value *</label>
                                <input type="number" step="0.01" min="0.01" name="discount_value" id="discount_value" class="form-control" placeholder="e.g. 20 (for 20% or $20)" required value="<?= htmlspecialchars($_POST['discount_value'] ?? ''); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="minimum_order" class="form-label small fw-bold text-dark">Minimum Order Subtotal ($)</label>
                                <input type="number" step="0.01" min="0" name="minimum_order" id="minimum_order" class="form-control" placeholder="0.00" value="<?= htmlspecialchars($_POST['minimum_order'] ?? '0.00'); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="maximum_discount" class="form-label small fw-bold text-dark">Maximum Discount Cap ($) <small class="text-muted">(Percentage only)</small></label>
                                <input type="number" step="0.01" min="0" name="maximum_discount" id="maximum_discount" class="form-control" placeholder="Leave blank for no cap" value="<?= htmlspecialchars($_POST['maximum_discount'] ?? ''); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="usage_limit" class="form-label small fw-bold text-dark">Global Usage Limit</label>
                                <input type="number" min="1" name="usage_limit" id="usage_limit" class="form-control" placeholder="Leave blank for unlimited" value="<?= htmlspecialchars($_POST['usage_limit'] ?? ''); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="usage_per_customer" class="form-label small fw-bold text-dark">Usage Per Customer</label>
                                <input type="number" min="1" name="usage_per_customer" id="usage_per_customer" class="form-control" value="<?= htmlspecialchars($_POST['usage_per_customer'] ?? '1'); ?>" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="start_at" class="form-label small fw-bold text-dark">Start Date/Time</label>
                                <input type="datetime-local" name="start_at" id="start_at" class="form-control" value="<?= htmlspecialchars($_POST['start_at'] ?? ''); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="end_at" class="form-label small fw-bold text-dark">Expiry Date/Time</label>
                                <input type="datetime-local" name="end_at" id="end_at" class="form-control" value="<?= htmlspecialchars($_POST['end_at'] ?? ''); ?>">
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active" value="1" <?= (!isset($_POST['code']) || !empty($_POST['is_active'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label small fw-bold text-dark" for="is_active">Activate Coupon Immediately</label>
                                </div>
                            </div>

                            <div class="col-12 pt-3 border-top d-flex justify-content-end gap-2">
                                <a href="index.php" class="btn btn-light btn-sm">Cancel</a>
                                <button type="submit" class="btn btn-auth-primary btn-sm px-4">Create Coupon</button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </main>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
