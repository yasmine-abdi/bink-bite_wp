<?php
/**
 * FastBite - Admin Business & Storefront Settings
 * STEP 25: FastBite Admin Dashboard & Business Control Center
 */

$pageTitle = 'Business & Storefront Settings';
$activeMenu = 'settings';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$msg = '';
$error = '';

// Handle POST Settings Save
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'save_settings') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed.';
    } else {
    $settingsToSave = [
        'restaurant_name' => trim($_POST['restaurant_name'] ?? 'FastBite Fast Food'),
        'restaurant_phone' => trim($_POST['restaurant_phone'] ?? '+252 63 444 5555'),
        'restaurant_email' => trim($_POST['restaurant_email'] ?? 'support@fastbite.com'),
        'restaurant_address' => trim($_POST['restaurant_address'] ?? 'Main Hospitality Blvd, City Center, Hargeisa'),
        'opening_time' => trim($_POST['opening_time'] ?? '10:00 AM'),
        'closing_time' => trim($_POST['closing_time'] ?? '11:00 PM'),
        'delivery_fee' => number_format((float)($_POST['delivery_fee'] ?? 2.00), 2, '.', ''),
        'min_order_amount' => number_format((float)($_POST['min_order_amount'] ?? 5.00), 2, '.', ''),
        'free_delivery_threshold' => number_format((float)($_POST['free_delivery_threshold'] ?? 30.00), 2, '.', ''),
        'tax_percent' => number_format((float)($_POST['tax_percent'] ?? 0.00), 1, '.', ''),
        'website_status' => trim($_POST['website_status'] ?? 'online'),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'announcement_banner' => trim($_POST['announcement_banner'] ?? '')
    ];

    $allSaved = true;
    foreach ($settingsToSave as $k => $v) {
        if (!dbSaveSetting($k, $v)) {
            $allSaved = false;
        }
    }

    if ($allSaved) {
        $msg = 'Business and storefront settings updated successfully.';
    } else {
        $error = 'Some settings could not be saved.';
    }
    }
}

// Fetch all current settings
$currentSettings = dbGetAllSettings();
$get = function($key, $default = '') use ($currentSettings) {
    return $currentSettings[$key] ?? $default;
};
?>

<!-- Admin Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0">

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent" style="font-size: 0.75rem; letter-spacing: 0.08em;">CONFIGURATION</span>
                <h1 class="admin-title fw-bold mb-1 text-dark">Business &amp; Storefront Settings</h1>
                <p class="admin-desc text-muted mb-0 small">
                    Configure restaurant contact details, delivery fee thresholds, operational hours, and storefront availability.
                </p>
            </div>
            <div>
                <a href="../../menu.php" target="_blank" class="btn btn-outline-pink btn-sm d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>Preview Storefront</span>
                </a>
            </div>
        </div>

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

        <form method="POST" action="index.php">
            <?= csrfField(); ?>
            <input type="hidden" name="action" value="save_settings">

            <div class="row g-4">
                
                <!-- Left Column: Restaurant Identity & Operating Hours -->
                <div class="col-12 col-xl-6 d-flex flex-column gap-4">
                    
                    <!-- 1. Restaurant Profile -->
                    <div class="card border-0 bg-white shadow-sm rounded-3 p-4">
                        <h2 class="h6 fw-bold mb-3 text-dark d-flex align-items-center gap-2 border-bottom pb-2">
                            <i class="bi bi-shop text-primary-accent"></i> Restaurant Profile &amp; Contact
                        </h2>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="restName" class="form-label auth-label">Restaurant Brand Name</label>
                                <input type="text" name="restaurant_name" id="restName" class="form-control" value="<?= htmlspecialchars($get('restaurant_name', 'FastBite Fast Food'), ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="restPhone" class="form-label auth-label">Official Phone</label>
                                <input type="text" name="restaurant_phone" id="restPhone" class="form-control" value="<?= htmlspecialchars($get('restaurant_phone', '+252 63 444 5555'), ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="restEmail" class="form-label auth-label">Support Email</label>
                                <input type="email" name="restaurant_email" id="restEmail" class="form-control" value="<?= htmlspecialchars($get('restaurant_email', 'support@fastbite.com'), ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="col-12">
                                <label for="restAddress" class="form-label auth-label">Physical Location Address</label>
                                <textarea name="restaurant_address" id="restAddress" class="form-control" rows="2" required><?= htmlspecialchars($get('restaurant_address', 'Main Hospitality Blvd, City Center, Hargeisa'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Operating Hours -->
                    <div class="card border-0 bg-white shadow-sm rounded-3 p-4">
                        <h2 class="h6 fw-bold mb-3 text-dark d-flex align-items-center gap-2 border-bottom pb-2">
                            <i class="bi bi-clock-history text-primary-accent"></i> Operating &amp; Kitchen Hours
                        </h2>
                        
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="openTime" class="form-label auth-label">Opening Time</label>
                                <input type="text" name="opening_time" id="openTime" class="form-control" placeholder="e.g. 10:00 AM" value="<?= htmlspecialchars($get('opening_time', '10:00 AM'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="closeTime" class="form-label auth-label">Closing Time</label>
                                <input type="text" name="closing_time" id="closeTime" class="form-control" placeholder="e.g. 11:00 PM" value="<?= htmlspecialchars($get('closing_time', '11:00 PM'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Delivery Rules, Status & Announcements -->
                <div class="col-12 col-xl-6 d-flex flex-column gap-4">
                    
                    <!-- 3. Delivery & Ordering Rules -->
                    <div class="card border-0 bg-white shadow-sm rounded-3 p-4">
                        <h2 class="h6 fw-bold mb-3 text-dark d-flex align-items-center gap-2 border-bottom pb-2">
                            <i class="bi bi-bicycle text-primary-accent"></i> Delivery &amp; Ordering Rules
                        </h2>
                        
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="deliveryFee" class="form-label auth-label">Standard Delivery Fee ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">$</span>
                                    <input type="number" step="0.01" name="delivery_fee" id="deliveryFee" class="form-control" value="<?= htmlspecialchars($get('delivery_fee', '2.00'), ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="minOrder" class="form-label auth-label">Minimum Order Amount ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">$</span>
                                    <input type="number" step="0.01" name="min_order_amount" id="minOrder" class="form-control" value="<?= htmlspecialchars($get('min_order_amount', '5.00'), ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="freeDelivery" class="form-label auth-label">Free Delivery Over ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">$</span>
                                    <input type="number" step="0.01" name="free_delivery_threshold" id="freeDelivery" class="form-control" value="<?= htmlspecialchars($get('free_delivery_threshold', '30.00'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <span class="extra-small text-muted">Set 0 to disable free delivery rule</span>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="taxRate" class="form-label auth-label">Sales Tax Rate (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="tax_percent" id="taxRate" class="form-control" value="<?= htmlspecialchars($get('tax_percent', '0.0'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="input-group-text bg-light">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Storefront Operational Status -->
                    <div class="card border-0 bg-white shadow-sm rounded-3 p-4">
                        <h2 class="h6 fw-bold mb-3 text-dark d-flex align-items-center gap-2 border-bottom pb-2">
                            <i class="bi bi-toggle-on text-primary-accent"></i> Storefront Visibility &amp; Banner
                        </h2>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="webStatus" class="form-label auth-label">Website Accepting Orders</label>
                                <select name="website_status" id="webStatus" class="form-select">
                                    <option value="online" <?= ($get('website_status', 'online') === 'online') ? 'selected' : ''; ?>>🟢 Online (Accepting Orders)</option>
                                    <option value="closed" <?= ($get('website_status', 'online') === 'closed') ? 'selected' : ''; ?>>🔴 Temporarily Closed (Store Closed Message)</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" role="switch" name="maintenance_mode" id="maintMode" <?= ($get('maintenance_mode', '0') === '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label extra-small fw-bold text-dark" for="maintMode">Enable Maintenance Mode</label>
                                </div>
                                <span class="extra-small text-muted d-block mt-1">When enabled, customers see a temporary maintenance message.</span>
                            </div>

                            <div class="col-12">
                                <label for="announcementBanner" class="form-label auth-label">Store Announcement Banner</label>
                                <input type="text" name="announcement_banner" id="announcementBanner" class="form-control" placeholder="e.g. Free Delivery on all weekend orders over $30!" value="<?= htmlspecialchars($get('announcement_banner', ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <span class="extra-small text-muted">Displays on customer-facing navbar banner if filled.</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Bottom Save Button Bar -->
                <div class="col-12">
                    <div class="card border-0 bg-white shadow-sm rounded-3 p-3 d-flex flex-row align-items-center justify-content-between">
                        <span class="extra-small text-muted">
                            <i class="bi bi-info-circle text-primary-accent me-1"></i> Changes take effect immediately across all customer views and order processing.
                        </span>
                        <button type="submit" class="btn btn-auth-primary d-inline-flex align-items-center gap-2 shadow-sm px-4">
                            <i class="bi bi-check-lg"></i>
                            <span>Save Business Settings</span>
                        </button>
                    </div>
                </div>

            </div>
        </form>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
