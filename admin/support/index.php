<?php
/**
 * FastBite - Admin Customer Support Center
 * STEP 33: Complete Business Platform, Payments, Analytics, Support, Courier Portal & Production Security
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(dirname(__DIR__)));
}

require_once FASTBITE_ROOT_DIR . '/config/database.php';
require_once FASTBITE_ROOT_DIR . '/includes/auth.php';

requireAdmin('../../login.php');

$page = max(1, (int)($_GET['page'] ?? 1));
$status = trim($_GET['status'] ?? 'all');
$category = trim($_GET['category'] ?? 'all');
$priority = trim($_GET['priority'] ?? 'all');
$search = trim($_GET['search'] ?? '');

$ticketsData = dbGetAllSupportTickets([
    'status' => $status,
    'category' => $category,
    'priority' => $priority,
    'search' => $search
], $page, 15);

$supportAnalytics = dbGetSupportAnalytics();

$pageTitle = 'Support Tickets Desk';
$activeMenu = 'support';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0">

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent" style="font-size: 0.75rem; letter-spacing: 0.08em;">CUSTOMER CARE</span>
                <h1 class="admin-title fw-bold mb-1 text-dark">Support Tickets &amp; Inquiries</h1>
                <p class="admin-desc text-muted mb-0 small">
                    Manage customer inquiries, order assistance requests, and resolution threads.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle px-3 py-2 rounded-pill">
                    <?= $supportAnalytics['open']; ?> Open Tickets
                </span>
            </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-warning">
                    <span class="text-muted extra-small text-uppercase fw-bold">Open Tickets</span>
                    <div class="h3 fw-bold text-warning my-1"><?= $supportAnalytics['open']; ?></div>
                    <span class="extra-small text-muted">Awaiting initial staff response</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-info">
                    <span class="text-muted extra-small text-uppercase fw-bold">In Progress</span>
                    <div class="h3 fw-bold text-info my-1"><?= $supportAnalytics['in_progress']; ?></div>
                    <span class="extra-small text-muted">Active staff conversation</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-success">
                    <span class="text-muted extra-small text-uppercase fw-bold">Resolved / Closed</span>
                    <div class="h3 fw-bold text-success my-1"><?= $supportAnalytics['resolved'] + $supportAnalytics['closed']; ?></div>
                    <span class="extra-small text-muted">Successfully handled inquiries</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-4 stat-hover-card border-start border-4 border-primary">
                    <span class="text-muted extra-small text-uppercase fw-bold">Avg Resolution Time</span>
                    <div class="h3 fw-bold text-dark my-1"><?= $supportAnalytics['avg_resolution_hours']; ?> hrs</div>
                    <span class="extra-small text-muted">From creation to resolution</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card p-3 border-0 bg-white shadow-sm rounded-4 mb-4">
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label for="statusSelect" class="form-label extra-small text-muted fw-bold text-uppercase mb-1">Status</label>
                    <select name="status" id="statusSelect" class="form-select rounded-3" onchange="this.form.submit()">
                        <option value="all" <?= ($status === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="open" <?= ($status === 'open') ? 'selected' : ''; ?>>Open</option>
                        <option value="in_progress" <?= ($status === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?= ($status === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?= ($status === 'closed') ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label for="catSelect" class="form-label extra-small text-muted fw-bold text-uppercase mb-1">Category</label>
                    <select name="category" id="catSelect" class="form-select rounded-3" onchange="this.form.submit()">
                        <option value="all" <?= ($category === 'all') ? 'selected' : ''; ?>>All Categories</option>
                        <option value="order_issue" <?= ($category === 'order_issue') ? 'selected' : ''; ?>>Order Issue</option>
                        <option value="delivery" <?= ($category === 'delivery') ? 'selected' : ''; ?>>Delivery</option>
                        <option value="payment" <?= ($category === 'payment') ? 'selected' : ''; ?>>Payment</option>
                        <option value="food_quality" <?= ($category === 'food_quality') ? 'selected' : ''; ?>>Food Quality</option>
                        <option value="account" <?= ($category === 'account') ? 'selected' : ''; ?>>Account</option>
                        <option value="general" <?= ($category === 'general') ? 'selected' : ''; ?>>General</option>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label for="prioSelect" class="form-label extra-small text-muted fw-bold text-uppercase mb-1">Priority</label>
                    <select name="priority" id="prioSelect" class="form-select rounded-3" onchange="this.form.submit()">
                        <option value="all" <?= ($priority === 'all') ? 'selected' : ''; ?>>All Priorities</option>
                        <option value="urgent" <?= ($priority === 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                        <option value="high" <?= ($priority === 'high') ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?= ($priority === 'medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?= ($priority === 'low') ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>

                <div class="col-12 col-md-3 d-flex gap-2">
                    <input type="text" name="search" class="form-control rounded-3" placeholder="Search ticket / customer..." value="<?= htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-auth-primary btn-sm px-3 rounded-pill">Filter</button>
                    <a href="index.php" class="btn btn-outline-pink btn-sm px-3 rounded-pill" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>

        <!-- Tickets Table -->
        <div class="card border-0 bg-white shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between border-bottom">
                <h2 class="h6 fw-bold mb-0 text-dark">Tickets Queue</h2>
                <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small rounded-pill px-2">
                    <?= $ticketsData['total']; ?> Tickets Total
                </span>
            </div>

            <?php if (empty($ticketsData['tickets'])): ?>
                <div class="p-5 text-center text-muted small">No support tickets match the current filter criteria.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 extra-small">
                        <thead class="table-light text-uppercase text-muted">
                            <tr>
                                <th scope="col" class="ps-4">Ticket #</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Category</th>
                                <th scope="col" class="text-center">Priority</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col">Last Updated</th>
                                <th scope="col" class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ticketsData['tickets'] as $t): ?>
                                <?php
                                    $st = $t['status'];
                                    $badgeClass = 'bg-secondary text-white';
                                    if ($st === 'open') $badgeClass = 'bg-warning text-dark';
                                    elseif ($st === 'in_progress') $badgeClass = 'bg-info text-dark';
                                    elseif ($st === 'resolved') $badgeClass = 'bg-success text-white';
                                    elseif ($st === 'closed') $badgeClass = 'bg-secondary text-white';

                                    $prio = $t['priority'];
                                    $prioClass = 'bg-light text-muted border';
                                    if ($prio === 'urgent') $prioClass = 'bg-danger text-white';
                                    elseif ($prio === 'high') $prioClass = 'bg-warning text-dark';
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold font-monospace text-primary-accent">
                                        #<?= htmlspecialchars($t['ticket_number']); ?>
                                        <?php if (!empty($t['order_number'])): ?>
                                            <span class="d-block text-muted" style="font-size: 0.65rem;">Ord: #<?= htmlspecialchars($t['order_number']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="d-block fw-bold text-dark"><?= htmlspecialchars($t['customer_name']); ?></span>
                                        <span class="text-muted" style="font-size: 0.70rem;"><?= htmlspecialchars($t['customer_email']); ?></span>
                                    </td>
                                    <td>
                                        <span class="d-block fw-semibold text-dark text-truncate" style="max-width: 260px;">
                                            <?= htmlspecialchars($t['subject']); ?>
                                        </span>
                                        <span class="text-muted" style="font-size: 0.70rem;"><?= (int)$t['message_count']; ?> messages</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= ucwords(str_replace('_', ' ', $t['category'])); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $prioClass; ?> extra-small text-uppercase rounded-pill px-2"><?= $prio; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $badgeClass; ?> extra-small text-uppercase rounded-pill px-2"><?= str_replace('_', ' ', $st); ?></span>
                                    </td>
                                    <td><?= date('M j, Y, H:i', strtotime($t['updated_at'])); ?></td>
                                    <td class="text-end pe-4">
                                        <a href="view.php?id=<?= $t['id']; ?>" class="btn btn-outline-pink btn-sm rounded-pill px-3 extra-small">
                                            Manage
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($ticketsData['pages'] > 1): ?>
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3 px-4 border-top">
                        <span class="extra-small text-muted">Page <?= $ticketsData['page']; ?> of <?= $ticketsData['pages']; ?> (<?= $ticketsData['total']; ?> total)</span>
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($i = 1; $i <= $ticketsData['pages']; $i++): ?>
                                <li class="page-item <?= ($i === $ticketsData['page']) ? 'active' : ''; ?>">
                                    <a class="page-link" href="index.php?<?= http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
