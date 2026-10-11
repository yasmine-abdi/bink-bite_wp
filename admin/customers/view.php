<?php
/**
 * FastBite - Admin Customer Details & Profile View
 * STEP 27: Admin Customer Management & Production Hardening
 */

$pageTitle = 'Customer Profile';
$activeMenu = 'customers';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../../includes/image-helper.php';
require_once __DIR__ . '/../../includes/payment.php';

$customerId = (int)($_GET['id'] ?? 0);
$currentAdminId = currentUserId();

$msg = '';
$error = '';

// Handle Status Toggle POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = "Security token validation failed.";
    } elseif ($customerId === $currentAdminId) {
        $error = "You cannot modify your own administrative account status.";
    } else {
        $newStatus = trim($_POST['new_status'] ?? 'active');
        if (dbSetCustomerStatus($customerId, $newStatus)) {
            $msg = "Customer account status updated to '" . ucfirst($newStatus) . "'.";
        } else {
            $error = "Failed to update account status.";
        }
    }
}

$data = dbGetCustomerDetails($customerId);
$profile = $data['profile'] ?? null;
$kpi = $data['kpi'] ?? [];
$orders = $data['orders'] ?? [];
$addresses = $data['addresses'] ?? [];
?>

<!-- Admin Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5 bg-light">
    <div class="container-fluid p-0">

        <!-- Breadcrumb & Top Bar -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none text-muted">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Customers</a></li>
                        <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page"><?= htmlspecialchars($profile['name'] ?? 'Customer'); ?></li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-dark mb-0">Customer Business Profile</h1>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i>Back to Customers
                </a>
                <?php if ($profile): ?>
                    <div class="btn-group btn-group-sm">
                        <a href="statement.php?id=<?= $customerId; ?>&print=1" target="_blank" class="btn btn-outline-dark btn-sm rounded-start-pill px-3 shadow-sm d-flex align-items-center gap-1">
                            <i class="bi bi-printer"></i>
                            <span>Print Statement (A4)</span>
                        </a>
                        <a href="statement.php?id=<?= $customerId; ?>&format=thermal&print=1" target="_blank" class="btn btn-outline-dark btn-sm rounded-end-pill px-3 shadow-sm d-flex align-items-center gap-1">
                            <i class="bi bi-receipt-cutoff"></i>
                            <span>Thermal Slip</span>
                        </a>
                    </div>
                    <a href="../orders/index.php?search=<?= urlencode($profile['email']); ?>" class="btn btn-primary-accent btn-sm rounded-pill px-3 text-white">
                        <i class="bi bi-bag-check me-1"></i>All Orders
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!$profile): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4 bg-white">
                <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                    <i class="bi bi-person-x fs-2"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2">Customer Not Found</h4>
                <p class="text-muted small mb-4">The requested customer record does not exist or has an administrator role.</p>
                <div>
                    <a href="index.php" class="btn btn-primary-accent rounded-pill px-4 text-white">Return to Directory</a>
                </div>
            </div>
        <?php else: 
            $avatar = function_exists('getAvatarImage') ? getAvatarImage($profile['avatar'] ?? '') : 'assets/images/placeholders/avatar-placeholder.jpg';
            $isActive = ($profile['status'] === 'active');
        ?>
            
            <!-- Customer Identity Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                    <div class="d-flex align-items-center gap-4">
                        <img src="<?= htmlspecialchars($avatar); ?>" alt="<?= htmlspecialchars($profile['name']); ?>" class="rounded-circle object-fit-cover border shadow" style="width: 80px; height: 80px;" onerror="handleImageError(this, 'avatar')">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h3 class="h4 fw-bold text-dark mb-0"><?= htmlspecialchars($profile['name']); ?></h3>
                                <?php if ($isActive): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle extra-small fw-bold">Active Account</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small fw-bold">Suspended / Inactive</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted extra-small mb-1"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($profile['email']); ?> &bull; <i class="bi bi-telephone ms-2 me-1"></i><?= htmlspecialchars($profile['phone'] ?? 'No phone'); ?></p>
                            <span class="text-muted extra-small">Customer since <?= date('F j, Y', strtotime($profile['created_at'])); ?></span>
                        </div>
                    </div>

                    <!-- Status Action Button -->
                    <div>
                        <form action="view.php?id=<?= $customerId; ?>" method="POST" onsubmit="return confirm('Confirm updating status for <?= htmlspecialchars(addslashes($profile['name'])); ?>?');">
                            <?= csrfField(); ?>
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="new_status" value="<?= $isActive ? 'inactive' : 'active'; ?>">
                            <?php if ($isActive): ?>
                                <button type="submit" class="btn btn-outline-danger rounded-pill px-4 btn-sm fw-semibold shadow-sm">
                                    <i class="bi bi-slash-circle me-1"></i>Deactivate Account
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-success rounded-pill px-4 btn-sm fw-semibold shadow-sm text-white">
                                    <i class="bi bi-check-circle me-1"></i>Activate Account
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 5 KPI Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-xl">
                    <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-3 stat-hover-card">
                        <span class="text-muted extra-small text-uppercase fw-bold">Total Orders</span>
                        <div class="h3 fw-bold text-dark my-1"><?= (int)($kpi['total_orders'] ?? 0); ?></div>
                        <span class="extra-small text-muted">All-time orders</span>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-3 stat-hover-card">
                        <span class="text-muted extra-small text-uppercase fw-bold">Completed</span>
                        <div class="h3 fw-bold text-success my-1"><?= (int)($kpi['completed_orders'] ?? 0); ?></div>
                        <span class="extra-small text-muted">Delivered orders</span>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-3 stat-hover-card">
                        <span class="text-muted extra-small text-uppercase fw-bold">Cancelled</span>
                        <div class="h3 fw-bold text-danger my-1"><?= (int)($kpi['cancelled_orders'] ?? 0); ?></div>
                        <span class="extra-small text-muted">Cancelled orders</span>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-3 stat-hover-card">
                        <span class="text-muted extra-small text-uppercase fw-bold">Lifetime Spent</span>
                        <div class="h3 fw-bold text-primary-accent my-1">$<?= number_format((float)($kpi['lifetime_spent'] ?? 0), 2); ?></div>
                        <span class="extra-small text-muted">Gross expenditure</span>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-3 stat-hover-card">
                        <span class="text-muted extra-small text-uppercase fw-bold">Avg Order Value</span>
                        <div class="h3 fw-bold text-info my-1">$<?= number_format((float)($kpi['avg_order_value'] ?? 0), 2); ?></div>
                        <span class="extra-small text-muted">Per order average</span>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                
                <!-- Left 8 Columns: Customer Order History -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white">
                        <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold text-dark mb-0">Order History (<?= count($orders); ?>)</h5>
                            <span class="text-muted extra-small">Lifetime records</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light extra-small">
                                    <tr>
                                        <th class="ps-4">Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Order Status</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                                This customer has not placed any orders yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $ord): 
                                            $stBadge = 'bg-secondary-subtle text-secondary';
                                            if ($ord['order_status'] === 'confirmed') $stBadge = 'bg-primary-subtle text-primary';
                                            if ($ord['order_status'] === 'preparing') $stBadge = 'bg-warning-subtle text-warning-emphasis';
                                            if ($ord['order_status'] === 'out_for_delivery') $stBadge = 'bg-info-subtle text-info-emphasis';
                                            if ($ord['order_status'] === 'delivered') $stBadge = 'bg-success-subtle text-success';
                                            if ($ord['order_status'] === 'cancelled') $stBadge = 'bg-danger-subtle text-danger';
                                        ?>
                                            <tr>
                                                <td class="ps-4 font-monospace fw-bold text-dark small">
                                                    #<?= htmlspecialchars($ord['order_number']); ?>
                                                </td>
                                                <td class="extra-small text-muted">
                                                    <?= date('M d, Y h:i A', strtotime($ord['created_at'])); ?>
                                                </td>
                                                <td class="extra-small">
                                                    <span class="badge bg-light text-dark border"><?= count($ord['items'] ?? []); ?> item(s)</span>
                                                </td>
                                                <td class="fw-bold text-primary-accent small">
                                                    $<?= number_format((float)$ord['total'], 2); ?>
                                                </td>
                                                <td class="extra-small">
                                                    <div class="d-flex flex-column">
                                                        <span><?= htmlspecialchars(getPaymentMethodLabel($ord['payment_method'])); ?></span>
                                                        <div><?= getPaymentStatusBadge($ord['payment_status']); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $stBadge; ?> extra-small fw-bold">
                                                        <?= ucfirst(str_replace('_', ' ', $ord['order_status'])); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="d-inline-flex align-items-center gap-1">
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="../../receipt.php?order=<?= urlencode($ord['order_number']); ?>&print=1" target="_blank" class="btn btn-outline-dark btn-xs px-2 py-1 extra-small" title="Print Official Receipt A4">
                                                                <i class="bi bi-printer me-1"></i>Receipt
                                                            </a>
                                                            <a href="../../receipt.php?order=<?= urlencode($ord['order_number']); ?>&format=thermal&print=1" target="_blank" class="btn btn-outline-secondary btn-xs px-1 py-1 extra-small" title="Print Thermal Slip">
                                                                <i class="bi bi-receipt-cutoff"></i>
                                                            </a>
                                                        </div>
                                                        <a href="../orders/view.php?order=<?= urlencode($ord['order_number']); ?>" class="btn btn-light btn-sm rounded-pill border px-2 extra-small">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
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

                <!-- Right 4 Columns: Profile Attributes & Saved Addresses -->
                <div class="col-lg-4 d-flex flex-column gap-4">
                    
                    <!-- Demographic & Profile Info -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">Profile Attributes</h5>
                        
                        <div class="vstack gap-2 extra-small text-muted">
                            <div class="d-flex justify-content-between">
                                <span>Date of Birth:</span>
                                <strong class="text-dark"><?= !empty($profile['date_of_birth']) ? date('M d, Y', strtotime($profile['date_of_birth'])) : 'Not provided'; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Gender:</span>
                                <strong class="text-dark"><?= ucfirst($profile['gender'] ?? 'Unspecified'); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Preferred Language:</span>
                                <strong class="text-dark"><?= strtoupper($profile['preferred_language'] ?? 'EN'); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Account ID:</span>
                                <strong class="text-dark">#<?= (int)$profile['id']; ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Saved Delivery Addresses (Read Only) -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">Saved Addresses (<?= count($addresses); ?>)</h5>
                        
                        <?php if (empty($addresses)): ?>
                            <p class="text-muted extra-small mb-0">No saved addresses on file for this customer.</p>
                        <?php else: ?>
                            <div class="vstack gap-3">
                                <?php foreach ($addresses as $addr): ?>
                                    <div class="p-3 rounded-3 bg-light border extra-small">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="badge bg-primary-subtle text-primary extra-small"><?= htmlspecialchars($addr['address_type'] ?? 'Home'); ?></span>
                                            <?php if (!empty($addr['is_default'])): ?>
                                                <span class="badge bg-success-subtle text-success extra-small">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <strong class="d-block text-dark mb-1"><?= htmlspecialchars($addr['street_address']); ?></strong>
                                        <span class="text-muted d-block"><?= htmlspecialchars($addr['area'] . ', ' . $addr['city']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            </div>

        <?php endif; ?>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
