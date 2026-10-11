<?php
/**
 * FastBite - Courier Performance & Metrics Profile
 * STEP 32: Delivery Management & Courier Operations Center
 */

$pageTitle = 'Courier Performance Profile';
$activeMenu = 'drivers';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$driverId = (int)($_GET['id'] ?? 0);
if ($driverId <= 0) {
    header("Location: index.php?error=" . urlencode("Invalid courier ID."));
    exit;
}

$perf = dbGetDriverPerformance($driverId);
$driver = $perf['driver'];
if (!$driver) {
    header("Location: index.php?error=" . urlencode("Courier not found."));
    exit;
}

$successMsg = $_GET['msg'] ?? '';
$errorMsg = $_GET['error'] ?? '';

// Handle Status Toggle POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = "Security validation failed. Please refresh and try again.";
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'toggle_status') {
            $newStatus = trim($_POST['status'] ?? '');
            if (dbUpdateDriverStatus($driverId, $newStatus)) {
                header("Location: view.php?id={$driverId}&msg=" . urlencode("Courier status updated to '" . ucfirst($newStatus) . "' successfully."));
                exit;
            } else {
                $errorMsg = "Failed to update courier status.";
            }
        }
    }
}

// Refresh performance
$perf = dbGetDriverPerformance($driverId);
$driver = $perf['driver'];
?>

<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5 bg-light">
    <div class="container-fluid p-0">
        
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb extra-small mb-1">
                        <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none text-muted">Delivery Ops</a></li>
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Couriers</a></li>
                        <li class="breadcrumb-item active text-primary-accent fw-bold" aria-current="page"><?= htmlspecialchars($driver['full_name']); ?></li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-dark mb-1"><?= htmlspecialchars($driver['full_name']); ?></h1>
                <p class="text-muted small mb-0">Courier Profile &bull; <?= ucfirst($driver['vehicle_type']); ?> (<?= htmlspecialchars($driver['vehicle_number'] ?? 'Standard'); ?>)</p>
            </div>
            <div class="d-flex gap-2">
                <a href="edit.php?id=<?= $driverId; ?>" class="btn btn-outline-pink btn-sm rounded-pill px-3">
                    <i class="bi bi-pencil me-1"></i>Edit Profile
                </a>
                <a href="index.php" class="btn btn-light btn-sm rounded-pill border px-3">
                    <i class="bi bi-arrow-left me-1"></i>Fleet List
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($successMsg); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($errorMsg); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Courier Overview Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Delivered Orders</span>
                    <h3 class="h4 fw-bold text-success mb-0"><?= $perf['completed_deliveries']; ?></h3>
                    <span class="extra-small text-muted">Successful fulfillment</span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Success Rate</span>
                    <h3 class="h4 fw-bold text-dark mb-0"><?= $perf['success_rate']; ?>%</h3>
                    <span class="extra-small text-muted"><?= $perf['failed_deliveries']; ?> failed attempts</span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Avg Transit Time</span>
                    <h3 class="h4 fw-bold text-dark mb-0"><?= $perf['avg_minutes']; ?> min</h3>
                    <span class="extra-small text-muted">Delivery duration</span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Current Status</span>
                    <h3 class="h5 fw-bold text-primary-accent mb-0"><?= ucfirst($driver['status']); ?></h3>
                    <span class="extra-small text-muted"><?= count($perf['active_deliveries']); ?> active deliveries</span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            
            <!-- Left: Profile & Quick Status -->
            <div class="col-12 col-lg-4">
                
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <div class="text-center pb-3 mb-3 border-bottom">
                        <div class="rounded-circle bg-pink-soft text-primary-accent d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 70px; height: 70px; font-size: 2rem;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <h2 class="h5 fw-bold text-dark mb-1"><?= htmlspecialchars($driver['full_name']); ?></h2>
                        <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-3 py-1 rounded-pill extra-small fw-semibold">
                            <?= ucfirst($driver['vehicle_type']); ?> Courier
                        </span>
                    </div>

                    <div class="extra-small text-muted mb-3">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Phone:</span>
                            <strong class="text-dark"><?= htmlspecialchars($driver['phone']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Email:</span>
                            <strong class="text-dark"><?= htmlspecialchars($driver['email'] ?? 'N/A'); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Vehicle Plate:</span>
                            <strong class="text-dark"><?= htmlspecialchars($driver['vehicle_number'] ?? 'N/A'); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Rating:</span>
                            <strong class="text-warning"><i class="bi bi-star-fill me-1"></i><?= number_format($driver['rating'], 1); ?> / 5.0</strong>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Joined:</span>
                            <strong class="text-dark"><?= date('M d, Y', strtotime($driver['created_at'])); ?></strong>
                        </div>
                    </div>

                    <form method="POST" action="view.php?id=<?= $driverId; ?>" class="pt-2 border-top">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="toggle_status">
                        <label class="form-label extra-small fw-bold text-dark">Update Duty Status</label>
                        <div class="input-group input-group-sm">
                            <select name="status" class="form-select">
                                <option value="available" <?= ($driver['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                                <option value="busy" <?= ($driver['status'] === 'busy') ? 'selected' : ''; ?>>Busy</option>
                                <option value="offline" <?= ($driver['status'] === 'offline') ? 'selected' : ''; ?>>Offline</option>
                                <option value="suspended" <?= ($driver['status'] === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                            <button type="submit" class="btn btn-auth-primary">Update</button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- Right: Active & Recent Deliveries -->
            <div class="col-12 col-lg-8">
                
                <!-- Active Deliveries -->
                <?php if (!empty($perf['active_deliveries'])): ?>
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4 border-start border-4 border-danger">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-truck text-danger fs-5"></i>
                                <h3 class="h6 fw-bold text-dark mb-0">Active Deliveries in Progress</h3>
                            </div>
                            <span class="badge bg-danger text-white rounded-pill extra-small">Live Dispatch</span>
                        </div>

                        <?php foreach ($perf['active_deliveries'] as $act): ?>
                            <div class="p-3 bg-light rounded-4 mb-2 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                                <div>
                                    <a href="../view.php?order_id=<?= $act['order_id']; ?>" class="fw-bold text-dark text-decoration-none hover-pink">
                                        Order #<?= htmlspecialchars($act['order_number']); ?>
                                    </a>
                                    <span class="extra-small text-muted d-block"><?= htmlspecialchars($act['customer_name']); ?> &bull; <?= htmlspecialchars($act['delivery_address']); ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small">
                                        <?= ucfirst(str_replace('_', ' ', $act['delivery_status'])); ?>
                                    </span>
                                    <a href="../view.php?order_id=<?= $act['order_id']; ?>" class="btn btn-auth-primary btn-sm rounded-pill extra-small px-3">Manage</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Recent Delivery History Table -->
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <h3 class="h6 fw-bold text-dark mb-0">Recent Completed Deliveries</h3>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead class="table-light extra-small text-uppercase text-muted">
                                <tr>
                                    <th scope="col" class="ps-4">Order #</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Duration</th>
                                    <th scope="col" class="text-end pe-4">Date</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php if (empty($perf['recent_deliveries'])): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No completed deliveries on record for this courier yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($perf['recent_deliveries'] as $rd): 
                                        $rdSt = $rd['delivery_status'];
                                        $rdBadge = ($rdSt === 'delivered') ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger';
                                    ?>
                                        <tr>
                                            <td class="ps-4">
                                                <a href="../view.php?order_id=<?= $rd['order_id']; ?>" class="fw-bold text-dark text-decoration-none hover-pink">
                                                    #<?= htmlspecialchars($rd['order_number']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="d-block text-dark fw-semibold"><?= htmlspecialchars($rd['customer_name'] ?? 'Customer'); ?></span>
                                                <span class="extra-small text-muted"><?= htmlspecialchars($rd['delivery_address']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill <?= $rdBadge; ?> extra-small">
                                                    <?= ucfirst($rdSt); ?>
                                                </span>
                                            </td>
                                            <td class="extra-small">
                                                <?= !empty($rd['delivery_duration_minutes']) ? $rd['delivery_duration_minutes'] . ' mins' : '—'; ?>
                                            </td>
                                            <td class="text-end pe-4 extra-small text-muted">
                                                <?= date('M d, H:i', strtotime($rd['updated_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
