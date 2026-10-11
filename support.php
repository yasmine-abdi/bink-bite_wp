<?php
/**
 * FastBite - Customer Support Center
 * STEP 33: Complete Business Platform, Payments, Analytics, Support, Courier Portal & Production Security
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';

requireCustomer('login.php');

$user = currentUser();
$userId = (int)$user['id'];
$currentPage = 'support';
$projectName = 'FastBite';

$statusFilter = trim($_GET['status'] ?? 'all');
$tickets = dbGetSupportTicketsByUser($userId, ['status' => $statusFilter]);

// Counts by status
$allTickets = dbGetSupportTicketsByUser($userId, ['status' => 'all']);
$countAll = count($allTickets);
$countOpen = 0;
$countInProgress = 0;
$countResolved = 0;
$countClosed = 0;

foreach ($allTickets as $t) {
    if ($t['status'] === 'open') $countOpen++;
    elseif ($t['status'] === 'in_progress') $countInProgress++;
    elseif ($t['status'] === 'resolved') $countResolved++;
    elseif ($t['status'] === 'closed') $countClosed++;
}

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Support - <?= htmlspecialchars($projectName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-grow-1 py-4 py-lg-5">
        <div class="container">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item"><a href="customer-dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Support Desk</li>
                </ol>
            </nav>

            <?php if (!empty($msg)): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Page Header Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-pink-soft text-primary-accent d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; font-size: 1.5rem;">
                            <i class="bi bi-headset"></i>
                        </div>
                        <div>
                            <h1 class="h4 fw-bold text-dark mb-1">Customer Support &amp; Help Desk</h1>
                            <p class="text-muted small mb-0">Need help with an order, delivery, or account inquiry? Our team is here for you.</p>
                        </div>
                    </div>
                    <div>
                        <a href="support-create.php" class="btn btn-auth-primary d-inline-flex align-items-center gap-2 rounded-pill px-4 shadow-sm">
                            <i class="bi bi-plus-lg"></i>
                            <span>Open New Ticket</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-2">
                <ul class="nav nav-pills gap-1">
                    <li class="nav-item">
                        <a class="nav-link rounded-pill extra-small px-3 <?= ($statusFilter === 'all') ? 'active bg-primary-accent text-white' : 'text-muted'; ?>" href="support.php?status=all">
                            All Tickets (<?= $countAll; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill extra-small px-3 <?= ($statusFilter === 'open') ? 'active bg-primary-accent text-white' : 'text-muted'; ?>" href="support.php?status=open">
                            Open (<?= $countOpen; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill extra-small px-3 <?= ($statusFilter === 'in_progress') ? 'active bg-primary-accent text-white' : 'text-muted'; ?>" href="support.php?status=in_progress">
                            In Progress (<?= $countInProgress; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill extra-small px-3 <?= ($statusFilter === 'resolved') ? 'active bg-primary-accent text-white' : 'text-muted'; ?>" href="support.php?status=resolved">
                            Resolved (<?= $countResolved; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill extra-small px-3 <?= ($statusFilter === 'closed') ? 'active bg-primary-accent text-white' : 'text-muted'; ?>" href="support.php?status=closed">
                            Closed (<?= $countClosed; ?>)
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Tickets List -->
            <?php if (empty($tickets)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                    <div class="rounded-circle bg-pink-soft text-primary-accent d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 72px; height: 72px; font-size: 2rem;">
                        <i class="bi bi-chat-heart"></i>
                    </div>
                    <h2 class="h5 fw-bold text-dark mb-2">No Support Tickets Found</h2>
                    <p class="text-muted small mb-4">You have no <?= ($statusFilter !== 'all') ? htmlspecialchars($statusFilter) : ''; ?> support requests at this moment.</p>
                    <div>
                        <a href="support-create.php" class="btn btn-auth-primary rounded-pill px-4">Open Support Ticket</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($tickets as $t): ?>
                        <?php
                            $st = $t['status'];
                            $badgeClass = 'bg-secondary text-white';
                            if ($st === 'open') $badgeClass = 'bg-warning text-dark';
                            elseif ($st === 'in_progress') $badgeClass = 'bg-info text-dark';
                            elseif ($st === 'resolved') $badgeClass = 'bg-success text-white';
                            elseif ($st === 'closed') $badgeClass = 'bg-secondary text-white';

                            $prio = $t['priority'];
                            $prioClass = 'bg-light text-muted border';
                            if ($prio === 'urgent') $prioClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                            elseif ($prio === 'high') $prioClass = 'bg-warning-subtle text-dark border border-warning-subtle';
                        ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white stat-hover-card">
                                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="rounded-circle bg-pink-soft text-primary-accent d-flex align-items-center justify-content-center flex-shrink-0 mt-1" style="width: 44px; height: 44px; font-size: 1.2rem;">
                                            <i class="bi bi-ticket-perforated"></i>
                                        </div>
                                        <div>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                <span class="font-monospace fw-bold text-primary-accent extra-small">#<?= htmlspecialchars($t['ticket_number']); ?></span>
                                                <span class="badge <?= $badgeClass; ?> extra-small rounded-pill px-2"><?= ucwords(str_replace('_', ' ', $st)); ?></span>
                                                <span class="badge <?= $prioClass; ?> extra-small rounded-pill px-2"><?= ucfirst($prio); ?> Priority</span>
                                                <span class="badge bg-light text-dark border extra-small rounded-pill px-2"><?= ucwords(str_replace('_', ' ', $t['category'])); ?></span>
                                            </div>
                                            <h2 class="h6 fw-bold text-dark mb-1">
                                                <a href="support-view.php?id=<?= $t['id']; ?>" class="text-decoration-none text-dark hover-accent">
                                                    <?= htmlspecialchars($t['subject']); ?>
                                                </a>
                                            </h2>
                                            <div class="d-flex flex-wrap align-items-center gap-3 text-muted extra-small">
                                                <?php if (!empty($t['order_number'])): ?>
                                                    <span><i class="bi bi-bag me-1 text-primary-accent"></i>Order #<?= htmlspecialchars($t['order_number']); ?></span>
                                                <?php endif; ?>
                                                <span><i class="bi bi-chat-dots me-1"></i><?= (int)$t['message_count']; ?> messages</span>
                                                <span><i class="bi bi-clock me-1"></i>Updated <?= date('M j, Y, H:i', strtotime($t['updated_at'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                        <a href="support-view.php?id=<?= $t['id']; ?>" class="btn btn-outline-pink btn-sm rounded-pill px-3">
                                            <span>View Thread</span>
                                            <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
