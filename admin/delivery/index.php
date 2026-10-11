<?php
/**
 * FastBite - Delivery Operations Center
 * STEP 32: Delivery Management & Courier Operations Center
 */

$pageTitle = 'Delivery Operations Center';
$activeMenu = 'delivery';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$successMsg = $_GET['msg'] ?? '';
$errorMsg = $_GET['error'] ?? '';
$currentAdmin = currentUser();
$adminId = $currentAdmin ? (int)$currentAdmin['id'] : null;

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = "Security validation failed. Please refresh and try again.";
    } else {
        $action = $_POST['action'] ?? '';
        $orderId = (int)($_POST['order_id'] ?? 0);

        if ($action === 'assign_driver' && $orderId > 0) {
            $driverId = (int)($_POST['driver_id'] ?? 0);
            $notes = trim($_POST['delivery_notes'] ?? '');
            $res = dbAssignDriverToOrder($orderId, $driverId, $notes, $adminId);
            if ($res['success']) {
                header("Location: index.php?msg=" . urlencode($res['message']));
                exit;
            } else {
                $errorMsg = $res['message'];
            }
        } elseif ($action === 'update_status' && $orderId > 0) {
            $newStatus = trim($_POST['new_status'] ?? '');
            $reason = trim($_POST['failure_reason'] ?? '');
            $notes = trim($_POST['delivery_notes'] ?? '');
            $res = dbUpdateDeliveryStatus($orderId, $newStatus, ['failure_reason' => $reason, 'notes' => $notes], $adminId);
            if ($res['success']) {
                header("Location: index.php?msg=" . urlencode($res['message']));
                exit;
            } else {
                $errorMsg = $res['message'];
            }
        }
    }
}

// Analytics & KPIs
$analytics = dbGetDeliveryAnalytics();
$availableDrivers = dbGetAvailableDrivers();
$allDrivers = dbGetAllDrivers();

// Filter parameters
$filters = [
    'status' => trim($_GET['status'] ?? 'active'),
    'driver_id' => (int)($_GET['driver_id'] ?? 0),
    'search' => trim($_GET['search'] ?? '')
];

$operationsQueue = dbGetDeliveryOperations($filters);
?>

<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5 bg-light">
    <div class="container-fluid p-0">
        
        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent" style="font-size: 0.75rem; letter-spacing: 0.08em;">LOGISTICS &amp; DISPATCH</span>
                <h1 class="h3 fw-bold text-dark mb-1">Delivery Operations Center</h1>
                <p class="text-muted small mb-0">Dispatch couriers, monitor transit milestones, and manage order fulfillment.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="drivers/index.php" class="btn btn-outline-pink rounded-pill px-3 py-2 small fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-bicycle"></i>
                    <span>Couriers Fleet (<?= $analytics['total_drivers']; ?>)</span>
                </a>
                <a href="history.php" class="btn btn-outline-secondary rounded-pill px-3 py-2 small fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-clock-history"></i>
                    <span>Delivery Archive</span>
                </a>
            </div>
        </div>

        <!-- Flash Alerts -->
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

        <!-- Operational KPI Cards Grid -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-warning">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Unassigned</span>
                    <h3 class="h4 fw-bold text-dark mb-0"><?= $analytics['unassigned_count']; ?></h3>
                    <span class="extra-small text-muted">Awaiting courier</span>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Assigned</span>
                    <h3 class="h4 fw-bold text-dark mb-0"><?= $analytics['assigned_count']; ?></h3>
                    <span class="extra-small text-muted">Awaiting pickup</span>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-info">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Picked Up</span>
                    <h3 class="h4 fw-bold text-dark mb-0"><?= $analytics['picked_up_count']; ?></h3>
                    <span class="extra-small text-muted">From kitchen</span>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">In Transit</span>
                    <h3 class="h4 fw-bold text-dark mb-0"><?= $analytics['out_for_delivery_count']; ?></h3>
                    <span class="extra-small text-muted">Out for delivery</span>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Delivered Today</span>
                    <h3 class="h4 fw-bold text-success mb-0"><?= $analytics['delivered_today']; ?></h3>
                    <span class="extra-small text-muted"><?= $analytics['total_delivered']; ?> all-time</span>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Fleet Ready</span>
                    <h3 class="h4 fw-bold text-dark mb-0"><?= $analytics['available_drivers']; ?> / <?= $analytics['total_drivers']; ?></h3>
                    <span class="extra-small text-muted"><?= $analytics['busy_drivers']; ?> busy on road</span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <form method="GET" action="index.php" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-0" placeholder="Search Order #, Customer, Courier, Address..." value="<?= htmlspecialchars($filters['search']); ?>">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <select name="status" class="form-select form-select-sm bg-light border-0">
                        <option value="active" <?= ($filters['status'] === 'active') ? 'selected' : ''; ?>>Active Deliveries (All Live)</option>
                        <option value="all" <?= ($filters['status'] === 'all') ? 'selected' : ''; ?>>All Deliveries</option>
                        <option value="unassigned" <?= ($filters['status'] === 'unassigned') ? 'selected' : ''; ?>>Unassigned (Needs Dispatch)</option>
                        <option value="assigned" <?= ($filters['status'] === 'assigned') ? 'selected' : ''; ?>>Assigned (At Kitchen)</option>
                        <option value="picked_up" <?= ($filters['status'] === 'picked_up') ? 'selected' : ''; ?>>Picked Up</option>
                        <option value="out_for_delivery" <?= ($filters['status'] === 'out_for_delivery') ? 'selected' : ''; ?>>Out for Delivery</option>
                        <option value="delivered" <?= ($filters['status'] === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                        <option value="failed" <?= ($filters['status'] === 'failed') ? 'selected' : ''; ?>>Failed / Issues</option>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <select name="driver_id" class="form-select form-select-sm bg-light border-0">
                        <option value="0">All Couriers</option>
                        <?php foreach ($allDrivers as $drv): ?>
                            <option value="<?= $drv['id']; ?>" <?= ($filters['driver_id'] === (int)$drv['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($drv['full_name']); ?> (<?= ucfirst($drv['vehicle_type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-auth-primary btn-sm rounded-pill w-100">Filter</button>
                    <a href="index.php" class="btn btn-light btn-sm rounded-pill border" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>

        <!-- Operations Queue Table -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-list-task text-primary-accent fs-5"></i>
                    <h2 class="h6 fw-bold text-dark mb-0">Delivery Queue</h2>
                </div>
                <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-3 py-1 rounded-pill small fw-semibold">
                    <?= count($operationsQueue); ?> Deliveries
                </span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light extra-small text-uppercase text-muted">
                        <tr>
                            <th scope="col" class="ps-4">Order #</th>
                            <th scope="col">Customer &amp; Destination</th>
                            <th scope="col">Order Status</th>
                            <th scope="col">Delivery Status</th>
                            <th scope="col">Courier / Vehicle</th>
                            <th scope="col">Timestamps</th>
                            <th scope="col" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($operationsQueue)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-box2-heart text-muted opacity-50 display-6 mb-2"></i>
                                        <span class="fw-semibold">No deliveries matching selected criteria.</span>
                                        <span class="extra-small text-muted">Check different filters or view the delivery archive.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($operationsQueue as $item): 
                                $delivSt = $item['delivery_status'];
                                $badgeClass = 'bg-secondary-subtle text-secondary';
                                $icon = 'bi-clock';
                                if ($delivSt === 'unassigned') {
                                    $badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                    $icon = 'bi-exclamation-circle';
                                } elseif ($delivSt === 'assigned') {
                                    $badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                                    $icon = 'bi-person-check';
                                } elseif ($delivSt === 'picked_up') {
                                    $badgeClass = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                                    $icon = 'bi-box-seam';
                                } elseif ($delivSt === 'out_for_delivery') {
                                    $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                    $icon = 'bi-truck';
                                } elseif ($delivSt === 'delivered') {
                                    $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                    $icon = 'bi-check2-circle';
                                } elseif ($delivSt === 'failed') {
                                    $badgeClass = 'bg-danger text-white';
                                    $icon = 'bi-x-circle';
                                }
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <a href="view.php?order_id=<?= $item['order_id']; ?>" class="fw-bold text-dark text-decoration-none hover-pink d-block">
                                            #<?= htmlspecialchars($item['order_number']); ?>
                                        </a>
                                        <span class="extra-small text-muted">$<?= number_format($item['total'], 2); ?> • <?= ucfirst($item['payment_method']); ?></span>
                                    </td>
                                    <td>
                                        <strong class="d-block text-dark"><?= htmlspecialchars($item['customer_name'] ?? 'Guest Customer'); ?></strong>
                                        <span class="extra-small text-muted d-block text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($item['delivery_address']); ?>">
                                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($item['delivery_address']); ?>
                                        </span>
                                        <span class="extra-small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($item['customer_phone'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border extra-small">
                                            <?= ucfirst(str_replace('_', ' ', $item['order_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?= $badgeClass; ?> extra-small d-inline-flex align-items-center gap-1">
                                            <i class="bi <?= $icon; ?>"></i>
                                            <span><?= ucfirst(str_replace('_', ' ', $delivSt)); ?></span>
                                        </span>
                                        <?php if ($delivSt === 'failed' && !empty($item['failure_reason'])): ?>
                                            <span class="d-block extra-small text-danger mt-1 text-truncate" style="max-width: 160px;" title="<?= htmlspecialchars($item['failure_reason']); ?>">
                                                <?= htmlspecialchars($item['failure_reason']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['driver_name'])): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-pink-soft text-primary-accent d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                    <i class="bi bi-bicycle"></i>
                                                </div>
                                                <div>
                                                    <a href="drivers/view.php?id=<?= $item['driver_id']; ?>" class="fw-semibold text-dark text-decoration-none hover-pink d-block extra-small">
                                                        <?= htmlspecialchars($item['driver_name']); ?>
                                                    </a>
                                                    <span class="extra-small text-muted d-block"><?= ucfirst($item['vehicle_type']); ?> <?= $item['vehicle_number'] ? '(' . htmlspecialchars($item['vehicle_number']) . ')' : ''; ?></span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border extra-small">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="extra-small text-muted">
                                        <div>Placed: <?= date('M d, H:i', strtotime($item['order_placed_at'])); ?></div>
                                        <?php if (!empty($item['assigned_at'])): ?>
                                            <div>Assigned: <?= date('H:i', strtotime($item['assigned_at'])); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($item['delivered_at'])): ?>
                                            <div class="text-success fw-bold">Delivered: <?= date('H:i', strtotime($item['delivered_at'])); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <a href="view.php?order_id=<?= $item['order_id']; ?>" class="btn btn-light btn-sm rounded-pill border px-2 py-1 extra-small" title="Manage Delivery & Timeline">
                                                <i class="bi bi-eye text-primary-accent"></i>
                                                <span class="d-none d-lg-inline ms-1">Manage</span>
                                            </a>

                                            <?php if (in_array($delivSt, ['unassigned', 'failed'], true)): ?>
                                                <button type="button" class="btn btn-auth-primary btn-sm rounded-pill px-2 py-1 extra-small" data-bs-toggle="modal" data-bs-target="#assignModal<?= $item['order_id']; ?>">
                                                    <i class="bi bi-person-plus"></i>
                                                    <span>Assign</span>
                                                </button>
                                            <?php elseif ($delivSt === 'assigned'): ?>
                                                <form method="POST" action="index.php" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="order_id" value="<?= $item['order_id']; ?>">
                                                    <input type="hidden" name="new_status" value="picked_up">
                                                    <button type="submit" class="btn btn-info btn-sm text-white rounded-pill px-2 py-1 extra-small" title="Mark Picked Up by Courier">
                                                        <i class="bi bi-box-seam me-1"></i>Picked Up
                                                    </button>
                                                </form>
                                            <?php elseif ($delivSt === 'picked_up'): ?>
                                                <form method="POST" action="index.php" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="order_id" value="<?= $item['order_id']; ?>">
                                                    <input type="hidden" name="new_status" value="out_for_delivery">
                                                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-2 py-1 extra-small" title="Mark Out for Delivery">
                                                        <i class="bi bi-truck me-1"></i>In Transit
                                                    </button>
                                                </form>
                                            <?php elseif ($delivSt === 'out_for_delivery'): ?>
                                                <form method="POST" action="index.php" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="order_id" value="<?= $item['order_id']; ?>">
                                                    <input type="hidden" name="new_status" value="delivered">
                                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-2 py-1 extra-small" title="Mark Delivered to Customer">
                                                        <i class="bi bi-check2-circle me-1"></i>Delivered
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Quick Assign Modal -->
                                        <div class="modal fade" id="assignModal<?= $item['order_id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <div class="modal-header bg-pink-soft border-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-dark fs-6">Assign Courier to Order #<?= htmlspecialchars($item['order_number']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="POST" action="index.php">
                                                        <div class="modal-body text-start p-4">
                                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                                            <input type="hidden" name="action" value="assign_driver">
                                                            <input type="hidden" name="order_id" value="<?= $item['order_id']; ?>">

                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold text-dark">Select Courier</label>
                                                                <select name="driver_id" class="form-select small" required>
                                                                    <option value="">-- Choose Courier --</option>
                                                                    <?php foreach ($allDrivers as $drv): 
                                                                        $isAvail = ($drv['status'] === 'available');
                                                                    ?>
                                                                        <option value="<?= $drv['id']; ?>" <?= ($drv['status'] === 'suspended') ? 'disabled' : ''; ?>>
                                                                            <?= htmlspecialchars($drv['full_name']); ?> — <?= ucfirst($drv['vehicle_type']); ?> (<?= ucfirst($drv['status']); ?><?= $drv['active_deliveries_count'] > 0 ? ", {$drv['active_deliveries_count']} active" : ''; ?>)
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold text-dark">Delivery Notes (Optional)</label>
                                                                <textarea name="delivery_notes" class="form-control small" rows="2" placeholder="e.g. Ring gate bell, handle hot items carefully..."></textarea>
                                                            </div>

                                                            <div class="p-3 bg-light rounded-3 extra-small text-muted">
                                                                <strong class="d-block text-dark mb-1"><i class="bi bi-geo-alt text-primary-accent me-1"></i>Destination:</strong>
                                                                <?= htmlspecialchars($item['delivery_address']); ?>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-light rounded-pill px-3 extra-small" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-auth-primary rounded-pill px-4 extra-small">Confirm &amp; Dispatch</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
