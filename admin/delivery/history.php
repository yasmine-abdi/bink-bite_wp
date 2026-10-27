<?php
/**
 * FastBite - Delivery Archive & Analytics History
 * STEP 32: Delivery Management & Courier Operations Center
 */

$pageTitle = 'Delivery History & Archive';
$activeMenu = 'delivery';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$allDrivers = dbGetAllDrivers();
$analytics = dbGetDeliveryAnalytics();

$filters = [
    'status' => trim($_GET['status'] ?? 'all'),
    'driver_id' => (int)($_GET['driver_id'] ?? 0),
    'date_range' => trim($_GET['date_range'] ?? 'all'),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to' => trim($_GET['date_to'] ?? ''),
    'search' => trim($_GET['search'] ?? '')
];

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$historyData = dbGetDeliveryHistory($filters, $page, $limit);
$deliveries = $historyData['deliveries'];
$totalPages = $historyData['pages'];
$totalCount = $historyData['total'];
?>

<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5 bg-light">
    <div class="container-fluid p-0">
        
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb extra-small mb-1">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Delivery Ops</a></li>
                        <li class="breadcrumb-item active text-primary-accent fw-bold" aria-current="page">History</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-dark mb-1">Delivery Archive &amp; History</h1>
                <p class="text-muted small mb-0">Searchable historical log of completed, failed, and transit deliveries.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-pink btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1">
                    <i class="bi bi-speedometer2"></i>
                    <span>Live Operations</span>
                </a>
            </div>
        </div>

        <!-- KPI Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Total Completed</span>
                    <h3 class="h4 fw-bold text-success mb-0"><?= $analytics['total_delivered']; ?></h3>
                    <span class="extra-small text-muted">Successful orders</span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Delivery Issues</span>
                    <h3 class="h4 fw-bold text-danger mb-0"><?= $analytics['total_failed']; ?></h3>
                    <span class="extra-small text-muted">Failed delivery attempts</span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Avg Transit Time</span>
                    <h3 class="h4 fw-bold text-dark mb-0"><?= $analytics['avg_delivery_minutes']; ?> min</h3>
                    <span class="extra-small text-muted">Kitchen to doorstep</span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-info">
                    <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Courier Fleet</span>
                    <h3 class="h4 fw-bold text-primary-accent mb-0"><?= $analytics['total_drivers']; ?> Active</h3>
                    <span class="extra-small text-muted"><?= $analytics['available_drivers']; ?> currently available</span>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <form method="GET" action="history.php" class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-0" placeholder="Search Order #, Customer, Courier..." value="<?= htmlspecialchars($filters['search']); ?>">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <select name="status" class="form-select form-select-sm bg-light border-0" onchange="this.form.submit()">
                        <option value="all" <?= ($filters['status'] === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="delivered" <?= ($filters['status'] === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                        <option value="failed" <?= ($filters['status'] === 'failed') ? 'selected' : ''; ?>>Failed</option>
                        <option value="out_for_delivery" <?= ($filters['status'] === 'out_for_delivery') ? 'selected' : ''; ?>>Out for Delivery</option>
                        <option value="picked_up" <?= ($filters['status'] === 'picked_up') ? 'selected' : ''; ?>>Picked Up</option>
                        <option value="assigned" <?= ($filters['status'] === 'assigned') ? 'selected' : ''; ?>>Assigned</option>
                        <option value="cancelled" <?= ($filters['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <select name="driver_id" class="form-select form-select-sm bg-light border-0" onchange="this.form.submit()">
                        <option value="0">All Couriers</option>
                        <?php foreach ($allDrivers as $drv): ?>
                            <option value="<?= $drv['id']; ?>" <?= ($filters['driver_id'] === (int)$drv['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($drv['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <select name="date_range" class="form-select form-select-sm bg-light border-0" onchange="this.form.submit()">
                        <option value="all" <?= ($filters['date_range'] === 'all' || empty($filters['date_range'])) ? 'selected' : ''; ?>>All Time</option>
                        <option value="today" <?= ($filters['date_range'] === 'today') ? 'selected' : ''; ?>>Today (Maanta)</option>
                        <option value="yesterday" <?= ($filters['date_range'] === 'yesterday') ? 'selected' : ''; ?>>Yesterday (Shalay)</option>
                        <option value="this_week" <?= ($filters['date_range'] === 'this_week') ? 'selected' : ''; ?>>This Week (Asbuucan)</option>
                        <option value="last_week" <?= ($filters['date_range'] === 'last_week') ? 'selected' : ''; ?>>Last Week (Asbuucii Hore)</option>
                        <option value="7days" <?= ($filters['date_range'] === '7days') ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="this_month" <?= ($filters['date_range'] === 'this_month') ? 'selected' : ''; ?>>This Month (Bishan)</option>
                        <option value="last_month" <?= ($filters['date_range'] === 'last_month') ? 'selected' : ''; ?>>Last Month (Bishii Hore)</option>
                        <option value="30days" <?= ($filters['date_range'] === '30days') ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="this_year" <?= ($filters['date_range'] === 'this_year') ? 'selected' : ''; ?>>This Year (Sannadkan)</option>
                    </select>
                </div>

                <div class="col-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-auth-primary btn-sm rounded-pill w-100">Filter</button>
                    <a href="history.php" class="btn btn-light btn-sm rounded-pill border"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>

        <!-- History Table Card -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                <h2 class="h6 fw-bold text-dark mb-0">Delivery Records (<?= $totalCount; ?>)</h2>
                <button type="button" class="btn btn-light btn-sm rounded-pill border px-3" onclick="window.print();">
                    <i class="bi bi-printer me-1"></i>Print Log
                </button>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light extra-small text-uppercase text-muted">
                        <tr>
                            <th scope="col" class="ps-4">Order #</th>
                            <th scope="col">Customer</th>
                            <th scope="col">Courier</th>
                            <th scope="col">Status</th>
                            <th scope="col">Duration</th>
                            <th scope="col">Delivered / Updated</th>
                            <th scope="col" class="text-end pe-4">Details</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($deliveries)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No historical delivery records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($deliveries as $item): 
                                $st = $item['delivery_status'];
                                $badge = 'bg-secondary-subtle text-secondary';
                                if ($st === 'delivered') $badge = 'bg-success-subtle text-success border border-success-subtle';
                                elseif ($st === 'failed') $badge = 'bg-danger text-white';
                                elseif ($st === 'out_for_delivery') $badge = 'bg-danger-subtle text-danger border border-danger-subtle';
                                elseif ($st === 'picked_up') $badge = 'bg-info-subtle text-info';
                                elseif ($st === 'assigned') $badge = 'bg-primary-subtle text-primary';
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <a href="view.php?order_id=<?= $item['order_id']; ?>" class="fw-bold text-dark text-decoration-none hover-pink">
                                            #<?= htmlspecialchars($item['order_number']); ?>
                                        </a>
                                        <span class="extra-small text-muted d-block">$<?= number_format($item['total'], 2); ?></span>
                                    </td>
                                    <td>
                                        <strong class="d-block text-dark"><?= htmlspecialchars($item['customer_name'] ?? 'Customer'); ?></strong>
                                        <span class="extra-small text-muted"><?= htmlspecialchars($item['delivery_address']); ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['driver_name'])): ?>
                                            <strong class="text-dark d-block"><?= htmlspecialchars($item['driver_name']); ?></strong>
                                            <span class="extra-small text-muted"><?= ucfirst($item['vehicle_type']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted extra-small">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?= $badge; ?> extra-small">
                                            <?= ucfirst(str_replace('_', ' ', $st)); ?>
                                        </span>
                                        <?php if ($st === 'failed' && !empty($item['failure_reason'])): ?>
                                            <span class="d-block extra-small text-danger mt-1"><?= htmlspecialchars($item['failure_reason']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="extra-small">
                                        <?php if ($st === 'delivered' && !empty($item['transit_minutes'])): ?>
                                            <span class="badge bg-light text-dark border"><?= $item['transit_minutes']; ?> mins</span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="extra-small text-muted">
                                        <?= date('M d, Y  h:i A', strtotime($item['delivered_at'] ?? $item['updated_at'])); ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="view.php?order_id=<?= $item['order_id']; ?>" class="btn btn-light btn-sm rounded-pill border px-2 py-1 extra-small">
                                            <i class="bi bi-arrow-right-short fs-6"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white p-3 border-top d-flex justify-content-between align-items-center">
                    <span class="extra-small text-muted">Showing Page <?= $page; ?> of <?= $totalPages; ?></span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $i; ?>&status=<?= urlencode($filters['status']); ?>&driver_id=<?= $filters['driver_id']; ?>&date_range=<?= urlencode($filters['date_range']); ?>&search=<?= urlencode($filters['search']); ?>">
                                        <?= $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
