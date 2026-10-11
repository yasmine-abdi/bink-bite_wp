<?php
/**
 * FastBite - Admin Support Ticket Conversation & Resolution
 * STEP 33: Complete Business Platform, Payments, Analytics, Support, Courier Portal & Production Security
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(dirname(__DIR__)));
}

require_once FASTBITE_ROOT_DIR . '/config/database.php';
require_once FASTBITE_ROOT_DIR . '/includes/auth.php';

requireAdmin('../../login.php');

$admin = currentUser();
$adminId = (int)$admin['id'];
$ticketId = (int)($_GET['id'] ?? 0);

$ticket = dbGetSupportTicketById($ticketId);
if (!$ticket) {
    header("Location: index.php?error=" . urlencode("Ticket not found."));
    exit;
}

$error = '';
$msg = $_GET['msg'] ?? '';

// Handle POST actions (Reply, Status Update, Priority Update)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security token invalid. Please refresh the page.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'admin_reply') {
            $replyMsg = trim($_POST['message'] ?? '');
            $res = dbAddSupportMessage($ticketId, $adminId, 'admin', $replyMsg);
            if ($res['success']) {
                header("Location: view.php?id={$ticketId}&msg=" . urlencode("Reply sent to customer successfully."));
                exit;
            } else {
                $error = $res['message'];
            }
        } elseif ($action === 'update_status') {
            $newStatus = trim($_POST['status'] ?? '');
            $res = dbUpdateSupportTicketStatus($ticketId, $newStatus, $adminId);
            if ($res['success']) {
                header("Location: view.php?id={$ticketId}&msg=" . urlencode($res['message']));
                exit;
            } else {
                $error = $res['message'];
            }
        } elseif ($action === 'update_priority') {
            $newPrio = trim($_POST['priority'] ?? '');
            $res = dbUpdateSupportTicketPriority($ticketId, $newPrio, $adminId);
            if ($res['success']) {
                header("Location: view.php?id={$ticketId}&msg=" . urlencode($res['message']));
                exit;
            } else {
                $error = $res['message'];
            }
        }
    }
}

$messages = dbGetSupportMessages($ticketId);
$ticket = dbGetSupportTicketById($ticketId);

$pageTitle = "Ticket #{$ticket['ticket_number']}";
$activeMenu = 'support';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0">

        <!-- Breadcrumb / Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <a href="index.php" class="text-decoration-none text-muted extra-small d-inline-flex align-items-center gap-1 mb-1">
                    <i class="bi bi-arrow-left"></i> Back to Support Desk
                </a>
                <h1 class="admin-title fw-bold mb-1 text-dark">
                    Ticket #<?= htmlspecialchars($ticket['ticket_number']); ?>
                </h1>
                <span class="text-muted small"><?= htmlspecialchars($ticket['subject']); ?></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                    <?= ucwords(str_replace('_', ' ', $ticket['category'])); ?>
                </span>
            </div>
        </div>

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

        <div class="row g-4">
            
            <!-- Main Conversation Column (8 cols) -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 bg-white shadow-sm rounded-4 p-3 p-md-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                        <h2 class="h6 fw-bold text-dark mb-0">Conversation History</h2>
                        <span class="extra-small text-muted"><?= count($messages); ?> messages</span>
                    </div>

                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($messages as $m): ?>
                            <?php $isAdminSender = ($m['sender_role'] === 'admin'); ?>
                            <div class="p-3 rounded-3 <?= $isAdminSender ? 'bg-pink-soft border border-pink-subtle ms-4' : 'bg-light border me-4'; ?>">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi <?= $isAdminSender ? 'bi-shield-check text-primary-accent' : 'bi-person-circle text-muted'; ?>"></i>
                                        <strong class="extra-small text-dark">
                                            <?= $isAdminSender ? 'Support Staff (' . htmlspecialchars($m['sender_name']) . ')' : htmlspecialchars($m['sender_name']); ?>
                                        </strong>
                                    </div>
                                    <span class="text-muted" style="font-size: 0.70rem;">
                                        <?= date('M j, Y, H:i', strtotime($m['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="small text-dark" style="white-space: pre-wrap; word-break: break-word;">
                                    <?= htmlspecialchars($m['message']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Admin Reply Form -->
                    <div class="pt-3 border-top">
                        <h3 class="h6 fw-bold text-dark mb-3">Respond as Support Agent</h3>
                        <form method="POST" action="view.php?id=<?= $ticketId; ?>">
                            <?= csrfField(); ?>
                            <input type="hidden" name="action" value="admin_reply">

                            <div class="mb-3">
                                <textarea name="message" rows="4" class="form-control rounded-3" placeholder="Type your response to the customer..." required></textarea>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-auth-primary rounded-pill px-4 shadow-sm">
                                    <i class="bi bi-reply-fill me-1"></i>Send Response to Customer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Management Sidebar Column (4 cols) -->
            <div class="col-12 col-lg-4">
                
                <!-- Status & Priority Control Card -->
                <div class="card border-0 bg-white shadow-sm rounded-4 p-3 p-md-4 mb-4">
                    <h2 class="h6 fw-bold text-dark mb-3 pb-2 border-bottom">Ticket Status &amp; Priority</h2>

                    <!-- Update Status -->
                    <form method="POST" action="view.php?id=<?= $ticketId; ?>" class="mb-3">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="update_status">
                        <label for="ticketStatus" class="form-label extra-small text-muted fw-bold text-uppercase mb-1">Status</label>
                        <div class="input-group">
                            <select name="status" id="ticketStatus" class="form-select rounded-start-3">
                                <option value="open" <?= ($ticket['status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                                <option value="in_progress" <?= ($ticket['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                <option value="resolved" <?= ($ticket['status'] === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                                <option value="closed" <?= ($ticket['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                            </select>
                            <button type="submit" class="btn btn-outline-pink btn-sm rounded-end-3 px-3">Update</button>
                        </div>
                    </form>

                    <!-- Update Priority -->
                    <form method="POST" action="view.php?id=<?= $ticketId; ?>">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="update_priority">
                        <label for="ticketPriority" class="form-label extra-small text-muted fw-bold text-uppercase mb-1">Priority</label>
                        <div class="input-group">
                            <select name="priority" id="ticketPriority" class="form-select rounded-start-3">
                                <option value="low" <?= ($ticket['priority'] === 'low') ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?= ($ticket['priority'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?= ($ticket['priority'] === 'high') ? 'selected' : ''; ?>>High</option>
                                <option value="urgent" <?= ($ticket['priority'] === 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                            <button type="submit" class="btn btn-outline-pink btn-sm rounded-end-3 px-3">Update</button>
                        </div>
                    </form>
                </div>

                <!-- Customer Details Card -->
                <div class="card border-0 bg-white shadow-sm rounded-4 p-3 p-md-4 mb-4">
                    <h2 class="h6 fw-bold text-dark mb-3 pb-2 border-bottom">Customer Information</h2>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-pink-soft text-primary-accent d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.2rem;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div>
                            <strong class="d-block text-dark small"><?= htmlspecialchars($ticket['customer_name']); ?></strong>
                            <span class="text-muted extra-small d-block"><?= htmlspecialchars($ticket['customer_email']); ?></span>
                            <?php if (!empty($ticket['customer_phone'])): ?>
                                <span class="text-muted extra-small d-block"><?= htmlspecialchars($ticket['customer_phone']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="../customers/index.php" class="btn btn-light btn-sm rounded-pill border w-100 extra-small">
                        Customer Directory
                    </a>
                </div>

                <!-- Linked Order Card if any -->
                <?php if (!empty($ticket['order_number'])): ?>
                    <div class="card border-0 bg-white shadow-sm rounded-4 p-3 p-md-4">
                        <h2 class="h6 fw-bold text-dark mb-3 pb-2 border-bottom">Linked Order Details</h2>
                        <div class="mb-2">
                            <span class="text-muted extra-small d-block">Order Number</span>
                            <strong class="font-monospace text-primary-accent">#<?= htmlspecialchars($ticket['order_number']); ?></strong>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted extra-small d-block">Order Total &amp; Status</span>
                            <strong class="text-dark">$<?= number_format((float)$ticket['order_total'], 2); ?></strong>
                            <span class="badge bg-light text-dark border extra-small ms-1"><?= ucfirst($ticket['order_status']); ?></span>
                        </div>
                        <a href="../orders/view.php?order=<?= urlencode($ticket['order_number']); ?>" class="btn btn-outline-pink btn-sm rounded-pill w-100 mt-2 extra-small">
                            Manage Order
                        </a>
                    </div>
                <?php endif; ?>

            </div>

        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
