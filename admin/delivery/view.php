<?php
/**
 * FastBite - Delivery Tracking & Status Action Manager
 * STEP 32: Delivery Management & Courier Operations Center
 */

$pageTitle = 'Delivery Details & Management';
$activeMenu = 'delivery';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    header("Location: index.php?error=" . urlencode("Invalid order ID."));
    exit;
}

$order = dbGetOrderWithFullDetails($orderId);
if (!$order) {
    header("Location: index.php?error=" . urlencode("Order not found."));
    exit;
}

$delivery = dbGetOrderDelivery($orderId);
$allDrivers = dbGetAllDrivers();
$currentAdmin = currentUser();
$adminId = $currentAdmin ? (int)$currentAdmin['id'] : null;

$successMsg = $_GET['msg'] ?? '';
$errorMsg = $_GET['error'] ?? '';

// Handle POST updates
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = "Security validation failed. Please refresh and try again.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'assign_driver') {
            $driverId = (int)($_POST['driver_id'] ?? 0);
            $notes = trim($_POST['delivery_notes'] ?? '');
            $res = dbAssignDriverToOrder($orderId, $driverId, $notes, $adminId);
            if ($res['success']) {
                header("Location: view.php?order_id={$orderId}&msg=" . urlencode($res['message']));
                exit;
            } else {
                $errorMsg = $res['message'];
            }
        } elseif ($action === 'update_status') {
            $newStatus = trim($_POST['new_status'] ?? '');
            $reason = trim($_POST['failure_reason'] ?? '');
            $notes = trim($_POST['delivery_notes'] ?? '');
            $res = dbUpdateDeliveryStatus($orderId, $newStatus, ['failure_reason' => $reason, 'notes' => $notes], $adminId);
            if ($res['success']) {
                header("Location: view.php?order_id={$orderId}&msg=" . urlencode($res['message']));
                exit;
            } else {
                $errorMsg = $res['message'];
            }
        }
    }
}

// Refresh delivery details
$delivery = dbGetOrderDelivery($orderId);
$delivSt = $delivery ? $delivery['delivery_status'] : 'unassigned';
?>

<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5 bg-light">
    <div class="container-fluid p-0">
        
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb extra-small mb-1">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Delivery Ops</a></li>
                        <li class="breadcrumb-item active text-primary-accent fw-bold" aria-current="page">Order #<?= htmlspecialchars($order['order_number']); ?></li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-dark mb-1">Delivery Management #<?= htmlspecialchars($order['order_number']); ?></h1>
                <p class="text-muted small mb-0">Manage courier assignment, transit milestones, and delivery issues.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="../orders/view.php?id=<?= $orderId; ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1">
                    <i class="bi bi-bag-check"></i>
                    <span>Order Details</span>
                </a>
                <a href="index.php" class="btn btn-light btn-sm rounded-pill border px-3 d-inline-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Queue</span>
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

        <div class="row g-4">
            
            <!-- Left Column: Status Stepper & Courier Details -->
            <div class="col-12 col-lg-8">
                
                <!-- Lifecycle Stepper Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-truck text-primary-accent fs-5"></i>
                            <h2 class="h6 fw-bold text-dark mb-0">Delivery Lifecycle Progress</h2>
                        </div>
                        <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-3 py-1 rounded-pill small fw-semibold">
                            Status: <?= ucfirst(str_replace('_', ' ', $delivSt)); ?>
                        </span>
                    </div>

                    <?php if ($delivSt === 'failed'): ?>
                        <div class="alert alert-danger rounded-4 p-3 d-flex align-items-start gap-3 mb-4">
                            <i class="bi bi-exclamation-octagon-fill fs-3 text-danger flex-shrink-0 mt-1"></i>
                            <div>
                                <h3 class="h6 fw-bold text-danger mb-1">Delivery Failed / Issue Encountered</h3>
                                <p class="small text-dark mb-1"><strong>Reason:</strong> <?= htmlspecialchars($delivery['failure_reason'] ?? 'Not specified'); ?></p>
                                <p class="extra-small text-muted mb-0">Logged at: <?= date('F j, Y  h:i A', strtotime($delivery['failed_at'] ?? $delivery['updated_at'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Stepper Visual -->
                    <div class="row g-2 text-center my-2">
                        <?php 
                        $stepMap = [
                            'unassigned' => 0,
                            'assigned' => 1,
                            'picked_up' => 2,
                            'out_for_delivery' => 3,
                            'delivered' => 4
                        ];
                        $curStepIdx = $stepMap[$delivSt] ?? ($delivSt === 'failed' ? 1 : 0);

                        $steps = [
                            1 => ['name' => 'Courier Assigned', 'icon' => 'bi-person-check', 'time' => $delivery['assigned_at'] ?? null],
                            2 => ['name' => 'Picked Up', 'icon' => 'bi-box-seam', 'time' => $delivery['picked_up_at'] ?? null],
                            3 => ['name' => 'Out for Delivery', 'icon' => 'bi-truck', 'time' => $delivery['out_for_delivery_at'] ?? null],
                            4 => ['name' => 'Delivered', 'icon' => 'bi-check2-circle', 'time' => $delivery['delivered_at'] ?? null]
                        ];

                        foreach ($steps as $idx => $st):
                            $isDone = ($curStepIdx >= $idx && $delivSt !== 'failed');
                            $isCurrent = ($curStepIdx === $idx && $delivSt !== 'failed');
                        ?>
                            <div class="col">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-2 <?= $isCurrent ? 'bg-primary-accent text-white shadow' : ($isDone ? 'bg-success text-white' : 'bg-light text-muted border'); ?>" style="width: 46px; height: 46px;">
                                        <i class="bi <?= $st['icon']; ?> fs-5"></i>
                                    </div>
                                    <span class="extra-small fw-bold <?= $isDone ? 'text-dark' : 'text-muted'; ?>"><?= $st['name']; ?></span>
                                    <span class="extra-small text-muted" style="font-size: 0.70rem;">
                                        <?= !empty($st['time']) ? date('h:i A', strtotime($st['time'])) : '—'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Assigned Courier Profile Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-badge text-primary-accent fs-5"></i>
                            <h2 class="h6 fw-bold text-dark mb-0">Assigned Courier Information</h2>
                        </div>
                        <?php if (!empty($delivery['driver_name'])): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill small">
                                Courier Status: <?= ucfirst($delivery['driver_status'] ?? 'Active'); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($delivery['driver_name'])): ?>
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 p-3 rounded-4 bg-light">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-pink-soft text-primary-accent d-flex align-items-center justify-content-center flex-shrink-0" style="width: 54px; height: 54px; font-size: 1.5rem;">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <div>
                                    <h3 class="h6 fw-bold text-dark mb-1"><?= htmlspecialchars($delivery['driver_name']); ?></h3>
                                    <div class="extra-small text-muted mb-1">
                                        <i class="bi bi-telephone text-primary-accent me-1"></i><?= htmlspecialchars($delivery['driver_phone'] ?? 'N/A'); ?>
                                        <?php if (!empty($delivery['driver_email'])): ?>
                                            • <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($delivery['driver_email']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-white text-dark border extra-small">
                                        <i class="bi bi-bicycle me-1"></i><?= ucfirst($delivery['vehicle_type']); ?> (<?= htmlspecialchars($delivery['vehicle_number'] ?? 'Standard'); ?>)
                                    </span>
                                </div>
                            </div>

                            <div>
                                <a href="drivers/view.php?id=<?= $delivery['driver_id']; ?>" class="btn btn-outline-pink btn-sm rounded-pill px-3 extra-small">
                                    View Courier Profile
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center bg-light rounded-4 text-muted">
                            <i class="bi bi-person-x display-6 d-block mb-2 text-muted opacity-50"></i>
                            <p class="small fw-semibold mb-1">No courier assigned yet</p>
                            <p class="extra-small text-muted mb-0">Use the dispatch panel on the right to assign an available courier.</p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($delivery['delivery_notes'])): ?>
                        <div class="mt-3 p-3 bg-pink-soft rounded-3 border border-pink-subtle extra-small">
                            <strong class="text-dark d-block mb-1"><i class="bi bi-sticky me-1"></i>Delivery Notes:</strong>
                            <span class="text-muted"><?= nl2br(htmlspecialchars($delivery['delivery_notes'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Right Column: State Machine Actions & Order Meta -->
            <div class="col-12 col-lg-4">
                
                <!-- Dispatch & Status Action Form Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <i class="bi bi-sliders text-primary-accent fs-5"></i>
                        <h2 class="h6 fw-bold text-dark mb-0">Dispatch &amp; Status Actions</h2>
                    </div>

                    <!-- Assign / Reassign Driver Form -->
                    <form method="POST" action="view.php?order_id=<?= $orderId; ?>" class="mb-4 pb-3 border-bottom">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="assign_driver">

                        <label class="form-label small fw-bold text-dark"><?= empty($delivery['driver_id']) ? 'Assign Courier' : 'Reassign Courier'; ?></label>
                        <div class="mb-2">
                            <select name="driver_id" class="form-select form-select-sm" required>
                                <option value="">-- Choose Courier --</option>
                                <?php foreach ($allDrivers as $drv): ?>
                                    <option value="<?= $drv['id']; ?>" <?= ($delivery && (int)$delivery['driver_id'] === (int)$drv['id']) ? 'selected' : ''; ?> <?= ($drv['status'] === 'suspended') ? 'disabled' : ''; ?>>
                                        <?= htmlspecialchars($drv['full_name']); ?> (<?= ucfirst($drv['vehicle_type']); ?> - <?= ucfirst($drv['status']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-2">
                            <textarea name="delivery_notes" class="form-control form-control-sm" rows="2" placeholder="Special delivery notes..."><?= htmlspecialchars($delivery['delivery_notes'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-auth-primary btn-sm rounded-pill w-100 fw-semibold">
                            <i class="bi bi-send me-1"></i><?= empty($delivery['driver_id']) ? 'Assign & Dispatch' : 'Update Courier'; ?>
                        </button>
                    </form>

                    <!-- Transition Status Form -->
                    <form method="POST" action="view.php?order_id=<?= $orderId; ?>" id="statusTransitionForm">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="update_status">

                        <label class="form-label small fw-bold text-dark">Transition Delivery Status</label>
                        
                        <div class="mb-3">
                            <select name="new_status" id="newStatusSelect" class="form-select form-select-sm" required onchange="toggleFailureReason()">
                                <option value="">-- Select New Status --</option>
                                <?php if ($delivSt === 'unassigned'): ?>
                                    <option value="assigned">Assigned</option>
                                    <option value="cancelled">Cancelled</option>
                                <?php elseif ($delivSt === 'assigned'): ?>
                                    <option value="picked_up">Picked Up (From Kitchen)</option>
                                    <option value="out_for_delivery">Out for Delivery</option>
                                    <option value="failed">Failed / Problem</option>
                                    <option value="cancelled">Cancelled</option>
                                <?php elseif ($delivSt === 'picked_up'): ?>
                                    <option value="out_for_delivery">Out for Delivery</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="failed">Failed / Problem</option>
                                    <option value="cancelled">Cancelled</option>
                                <?php elseif ($delivSt === 'out_for_delivery'): ?>
                                    <option value="delivered">Delivered</option>
                                    <option value="failed">Failed / Problem</option>
                                    <option value="cancelled">Cancelled</option>
                                <?php elseif ($delivSt === 'failed'): ?>
                                    <option value="assigned">Retry / Reassign Courier</option>
                                    <option value="cancelled">Cancelled</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div id="failureReasonGroup" class="mb-3 d-none">
                            <label class="form-label small fw-bold text-danger">Failure Reason *</label>
                            <input type="text" name="failure_reason" id="failureReasonInput" class="form-control form-control-sm border-danger" placeholder="e.g. Customer unreachable, wrong address, rejected...">
                        </div>

                        <div class="mb-3">
                            <textarea name="delivery_notes" class="form-control form-control-sm" rows="2" placeholder="Status update notes..."><?= htmlspecialchars($delivery['delivery_notes'] ?? ''); ?></textarea>
                        </div>

                        <?php if (!in_array($delivSt, ['delivered', 'cancelled'], true)): ?>
                            <button type="submit" class="btn btn-dark btn-sm rounded-pill w-100 fw-semibold">
                                <i class="bi bi-arrow-repeat me-1"></i>Update Delivery Status
                            </button>
                        <?php else: ?>
                            <div class="alert alert-light border small text-muted text-center mb-0">
                                This delivery is in terminal state: <strong><?= ucfirst($delivSt); ?></strong>.
                            </div>
                        <?php endif; ?>
                    </form>

                </div>

                <!-- Order Summary Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <i class="bi bi-receipt text-primary-accent fs-5"></i>
                        <h2 class="h6 fw-bold text-dark mb-0">Customer &amp; Order Info</h2>
                    </div>

                    <div class="mb-3 extra-small">
                        <span class="text-muted text-uppercase fw-bold d-block mb-1">Customer Details</span>
                        <strong class="text-dark d-block"><?= htmlspecialchars($order['customer_name'] ?? 'Customer'); ?></strong>
                        <span class="text-muted d-block"><?= htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></span>
                        <span class="text-muted d-block"><?= htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></span>
                    </div>

                    <div class="mb-3 extra-small">
                        <span class="text-muted text-uppercase fw-bold d-block mb-1">Delivery Destination</span>
                        <span class="text-dark d-block"><?= htmlspecialchars($order['delivery_address']); ?></span>
                    </div>

                    <div class="p-3 bg-light rounded-3 extra-small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Subtotal:</span>
                            <span class="fw-semibold text-dark">$<?= number_format($order['subtotal'], 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Delivery Fee:</span>
                            <span class="fw-semibold text-dark">$<?= number_format($order['delivery_fee'], 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-1 mt-1">
                            <strong class="text-dark">Total:</strong>
                            <strong class="text-primary-accent fs-6">$<?= number_format($order['total'], 2); ?></strong>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</main>

<script>
function toggleFailureReason() {
    var sel = document.getElementById('newStatusSelect');
    var group = document.getElementById('failureReasonGroup');
    var input = document.getElementById('failureReasonInput');
    if (sel.value === 'failed') {
        group.classList.remove('d-none');
        input.required = true;
    } else {
        group.classList.add('d-none');
        input.required = false;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
