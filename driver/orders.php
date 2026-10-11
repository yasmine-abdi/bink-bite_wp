<?php
/**
 * FastBite - Courier Deliveries Queue & History
 * STEP 33: Complete Business Platform, Payments, Analytics, Support, Courier Portal & Production Security
 */

$pageTitle = 'My Deliveries';
$activePage = 'orders';

require_once __DIR__ . '/includes/header.php';

$tab = $_GET['tab'] ?? 'queue';

// Fetch active queue and completed history
$queue = dbGetDriverDeliveriesQueue($driverId);
$completed = dbGetDriverCompletedDeliveries($driverId, 30);
?>

<main class="flex-grow-1 py-4">
    <div class="container" style="max-width: 960px;">

        <!-- Header -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">My Courier Deliveries</h1>
                    <p class="text-muted small mb-0">Track your assigned orders, active shipments, and completed delivery history.</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-outline-pink btn-sm rounded-pill px-3">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-2">
            <ul class="nav nav-pills gap-1">
                <li class="nav-item">
                    <a class="nav-link rounded-pill extra-small px-4 <?= ($tab === 'queue') ? 'active bg-primary-accent text-white' : 'text-muted'; ?>" href="orders.php?tab=queue">
                        Active Queue (<?= count($queue); ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill extra-small px-4 <?= ($tab === 'history') ? 'active bg-primary-accent text-white' : 'text-muted'; ?>" href="orders.php?tab=history">
                        Completed History (<?= count($completed); ?>)
                    </a>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <?php if ($tab === 'queue'): ?>
            <?php if (empty($queue)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                    <div class="rounded-circle bg-pink-soft text-primary-accent d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px; font-size: 1.8rem;">
                        <i class="bi bi-inbox-fill"></i>
                    </div>
                    <h2 class="h5 fw-bold text-dark mb-2">No Active Deliveries in Queue</h2>
                    <p class="text-muted small mb-0">You have no pending deliveries assigned at the moment.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($queue as $q): ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white stat-hover-card">
                                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                    <div>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <strong class="font-monospace text-primary-accent fs-6">#<?= htmlspecialchars($q['order_number']); ?></strong>
                                            <span class="badge bg-light text-dark border extra-small rounded-pill px-2"><?= ucwords(str_replace('_', ' ', $q['delivery_status'])); ?></span>
                                            <span class="badge bg-pink-soft text-primary-accent extra-small rounded-pill px-2">$<?= number_format((float)$q['total'], 2); ?></span>
                                        </div>
                                        <div class="text-dark small fw-semibold mb-1">
                                            <i class="bi bi-geo-alt-fill text-primary-accent me-1"></i>
                                            <?= htmlspecialchars($q['delivery_address']); ?>
                                        </div>
                                        <div class="text-muted extra-small">
                                            <span>Customer: <strong><?= htmlspecialchars($q['customer_name']); ?></strong></span>
                                            <?php if (!empty($q['customer_phone'])): ?>
                                                <span class="ms-2">• Phone: <?= htmlspecialchars($q['customer_phone']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <a href="delivery.php?id=<?= $q['order_id']; ?>" class="btn btn-auth-primary btn-sm rounded-pill px-4 shadow-sm">
                                            Start Delivery
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- History Tab -->
            <?php if (empty($completed)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                    <h2 class="h5 fw-bold text-dark mb-2">No Completed Deliveries Yet</h2>
                    <p class="text-muted small mb-0">Your completed deliveries will appear here.</p>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 extra-small">
                            <thead class="table-light text-uppercase text-muted">
                                <tr>
                                    <th scope="col" class="ps-4">Order #</th>
                                    <th scope="col">Destination</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col" class="text-center">Status</th>
                                    <th scope="col">Transit Time</th>
                                    <th scope="col" class="text-end pe-4">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed as $c): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold font-monospace text-primary-accent">#<?= htmlspecialchars($c['order_number']); ?></td>
                                        <td class="text-truncate" style="max-width: 240px;"><?= htmlspecialchars($c['delivery_address']); ?></td>
                                        <td><?= htmlspecialchars($c['customer_name']); ?></td>
                                        <td class="text-center">
                                            <?php if ($c['delivery_status'] === 'delivered'): ?>
                                                <span class="badge bg-success text-white rounded-pill px-2">Delivered</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger text-white rounded-pill px-2">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= !empty($c['transit_minutes']) ? $c['transit_minutes'] . ' min' : '20 min'; ?></td>
                                        <td class="text-end pe-4 fw-bold text-dark">$<?= number_format((float)$c['total'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
