<?php
/**
 * FastBite - Delivery Drivers & Couriers Fleet Directory
 * STEP 32: Delivery Management & Courier Operations Center
 */

$pageTitle = 'Couriers Fleet Management';
$activeMenu = 'drivers';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$successMsg = $_GET['msg'] ?? '';
$errorMsg = $_GET['error'] ?? '';

// Handle Status Toggle POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = "Security validation failed. Please refresh and try again.";
    } else {
        $action = $_POST['action'] ?? '';
        $driverId = (int)($_POST['driver_id'] ?? 0);

        if ($action === 'toggle_status' && $driverId > 0) {
            $newStatus = trim($_POST['status'] ?? '');
            if (dbUpdateDriverStatus($driverId, $newStatus)) {
                header("Location: index.php?msg=" . urlencode("Courier status updated to '" . ucfirst($newStatus) . "' successfully."));
                exit;
            } else {
                $errorMsg = "Failed to update courier status.";
            }
        }
    }
}

$filters = [
    'status' => trim($_GET['status'] ?? 'all'),
    'vehicle_type' => trim($_GET['vehicle_type'] ?? 'all'),
    'search' => trim($_GET['search'] ?? '')
];

$drivers = dbGetAllDrivers($filters);
$analytics = dbGetDeliveryAnalytics();
?>

<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5 bg-light">
    <div class="container-fluid p-0">
        
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb extra-small mb-1">
                        <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none text-muted">Delivery Ops</a></li>
                        <li class="breadcrumb-item active text-primary-accent fw-bold" aria-current="page">Couriers</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-dark mb-1">Couriers &amp; Delivery Drivers</h1>
                <p class="text-muted small mb-0">Manage delivery personnel, vehicles, duty status, and delivery performance.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="create.php" class="btn btn-auth-primary rounded-pill px-4 py-2 small fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle"></i>
                    <span>Add New Courier</span>
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

        <!-- Fleet KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-dark">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Total Fleet</span>
                    <h3 class="h4 fw-bold text-dark mb-0"><?= $analytics['total_drivers']; ?></h3>
                    <span class="extra-small text-muted">Registered couriers</span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Available (Ready)</span>
                    <h3 class="h4 fw-bold text-success mb-0"><?= $analytics['available_drivers']; ?></h3>
                    <span class="extra-small text-muted">Ready for dispatch</span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Busy (On Road)</span>
                    <h3 class="h4 fw-bold text-primary mb-0"><?= $analytics['busy_drivers']; ?></h3>
                    <span class="extra-small text-muted">Delivering orders</span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-secondary">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Offline</span>
                    <h3 class="h4 fw-bold text-muted mb-0"><?= $analytics['offline_drivers']; ?></h3>
                    <span class="extra-small text-muted">Off duty</span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <form method="GET" action="index.php" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-0" placeholder="Search courier name, phone, plate..." value="<?= htmlspecialchars($filters['search']); ?>">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <select name="status" class="form-select form-select-sm bg-light border-0">
                        <option value="all" <?= ($filters['status'] === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="available" <?= ($filters['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                        <option value="busy" <?= ($filters['status'] === 'busy') ? 'selected' : ''; ?>>Busy (On Delivery)</option>
                        <option value="offline" <?= ($filters['status'] === 'offline') ? 'selected' : ''; ?>>Offline</option>
                        <option value="suspended" <?= ($filters['status'] === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <select name="vehicle_type" class="form-select form-select-sm bg-light border-0">
                        <option value="all" <?= ($filters['vehicle_type'] === 'all') ? 'selected' : ''; ?>>All Vehicles</option>
                        <option value="motorcycle" <?= ($filters['vehicle_type'] === 'motorcycle') ? 'selected' : ''; ?>>Motorcycle</option>
                        <option value="car" <?= ($filters['vehicle_type'] === 'car') ? 'selected' : ''; ?>>Car</option>
                        <option value="scooter" <?= ($filters['vehicle_type'] === 'scooter') ? 'selected' : ''; ?>>Scooter</option>
                        <option value="bicycle" <?= ($filters['vehicle_type'] === 'bicycle') ? 'selected' : ''; ?>>Bicycle</option>
                        <option value="van" <?= ($filters['vehicle_type'] === 'van') ? 'selected' : ''; ?>>Van</option>
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-auth-primary btn-sm rounded-pill w-100">Filter</button>
                    <a href="index.php" class="btn btn-light btn-sm rounded-pill border"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>

        <!-- Couriers Fleet Table Card -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                <h2 class="h6 fw-bold text-dark mb-0">Couriers Directory (<?= count($drivers); ?>)</h2>
                <a href="create.php" class="btn btn-sm btn-outline-pink rounded-pill px-3">
                    <i class="bi bi-plus-lg me-1"></i>New Courier
                </a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light extra-small text-uppercase text-muted">
                        <tr>
                            <th scope="col" class="ps-4">Courier Name</th>
                            <th scope="col">Phone &amp; Contact</th>
                            <th scope="col">Vehicle &amp; Plate</th>
                            <th scope="col">Duty Status</th>
                            <th scope="col">Active Task</th>
                            <th scope="col">Total Delivered</th>
                            <th scope="col" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($drivers)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No couriers found matching criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($drivers as $drv): 
                                $st = $drv['status'];
                                $badge = 'bg-secondary-subtle text-secondary';
                                if ($st === 'available') $badge = 'bg-success-subtle text-success border border-success-subtle';
                                elseif ($st === 'busy') $badge = 'bg-primary-subtle text-primary border border-primary-subtle';
                                elseif ($st === 'offline') $badge = 'bg-secondary-subtle text-muted';
                                elseif ($st === 'suspended') $badge = 'bg-danger-subtle text-danger border border-danger-subtle';
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-pink-soft text-primary-accent d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-size: 1rem;">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <div>
                                                <a href="view.php?id=<?= $drv['id']; ?>" class="fw-bold text-dark text-decoration-none hover-pink d-block">
                                                    <?= htmlspecialchars($drv['full_name']); ?>
                                                </a>
                                                <span class="extra-small text-warning"><i class="bi bi-star-fill me-1"></i><?= number_format($drv['rating'], 1); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-block text-dark fw-semibold"><?= htmlspecialchars($drv['phone']); ?></span>
                                        <span class="extra-small text-muted"><?= htmlspecialchars($drv['email'] ?? 'No email'); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border extra-small">
                                            <i class="bi bi-bicycle me-1"></i><?= ucfirst($drv['vehicle_type']); ?>
                                        </span>
                                        <span class="d-block extra-small text-muted mt-1"><?= htmlspecialchars($drv['vehicle_number'] ?? 'No plate'); ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" action="index.php" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="driver_id" value="<?= $drv['id']; ?>">
                                            <select name="status" class="form-select form-select-sm extra-small py-0 ps-2 pe-4 rounded-pill <?= $badge; ?>" onchange="this.form.submit()" style="width: auto; height: 26px;">
                                                <option value="available" <?= ($st === 'available') ? 'selected' : ''; ?>>Available</option>
                                                <option value="busy" <?= ($st === 'busy') ? 'selected' : ''; ?>>Busy</option>
                                                <option value="offline" <?= ($st === 'offline') ? 'selected' : ''; ?>>Offline</option>
                                                <option value="suspended" <?= ($st === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if (!empty($drv['active_order_number'])): ?>
                                            <a href="../view.php?order_id=<?= $drv['active_order_id']; ?>" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none extra-small">
                                                <i class="bi bi-truck me-1"></i>#<?= htmlspecialchars($drv['active_order_number']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="extra-small text-muted">None (Idle)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?= (int)$drv['completed_deliveries_count']; ?></strong>
                                        <span class="extra-small text-muted">completed</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex gap-1">
                                            <a href="view.php?id=<?= $drv['id']; ?>" class="btn btn-light btn-sm rounded-pill border px-2 py-1 extra-small" title="View Performance">
                                                <i class="bi bi-bar-chart"></i>
                                            </a>
                                            <a href="edit.php?id=<?= $drv['id']; ?>" class="btn btn-light btn-sm rounded-pill border px-2 py-1 extra-small" title="Edit Courier">
                                                <i class="bi bi-pencil"></i>
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
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
