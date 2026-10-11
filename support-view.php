<?php
/**
 * FastBite - Support Ticket Conversation View
 * STEP 33: Complete Business Platform, Payments, Analytics, Support, Courier Portal & Production Security
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';

requireCustomer('login.php');

$user = currentUser();
$userId = (int)$user['id'];
$currentPage = 'support';
$projectName = 'FastBite';

$ticketId = (int)($_GET['id'] ?? 0);
$ticket = dbGetSupportTicketById($ticketId, $userId);

if (!$ticket) {
    header("Location: support.php?error=" . urlencode("Ticket not found or access restricted."));
    exit;
}

$error = '';
$msg = $_GET['msg'] ?? '';

// Handle Customer Reply or Close Ticket
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed. Please refresh the page.';
    } else {
        $action = $_POST['action'] ?? 'reply';
        
        if ($action === 'close_ticket') {
            $upd = dbUpdateSupportTicketStatus($ticketId, 'resolved');
            if ($upd['success']) {
                header("Location: support-view.php?id={$ticketId}&msg=" . urlencode("Ticket marked as resolved. Thank you!"));
                exit;
            } else {
                $error = $upd['message'];
            }
        } elseif ($action === 'reply') {
            $replyMessage = trim($_POST['message'] ?? '');
            $result = dbAddSupportMessage($ticketId, $userId, 'customer', $replyMessage);
            if ($result['success']) {
                header("Location: support-view.php?id={$ticketId}&msg=" . urlencode("Reply posted successfully."));
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

$messages = dbGetSupportMessages($ticketId);

// Refresh ticket details after actions
$ticket = dbGetSupportTicketById($ticketId, $userId);

$st = $ticket['status'];
$badgeClass = 'bg-secondary text-white';
if ($st === 'open') $badgeClass = 'bg-warning text-dark';
elseif ($st === 'in_progress') $badgeClass = 'bg-info text-dark';
elseif ($st === 'resolved') $badgeClass = 'bg-success text-white';
elseif ($st === 'closed') $badgeClass = 'bg-secondary text-white';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?= htmlspecialchars($ticket['ticket_number']); ?> - <?= htmlspecialchars($projectName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .msg-bubble-customer {
            background-color: #fff0f5;
            border: 1px solid #ffd1dc;
            border-radius: 16px 16px 4px 16px;
        }
        .msg-bubble-admin {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 16px 16px 16px 4px;
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-grow-1 py-4 py-lg-5">
        <div class="container" style="max-width: 900px;">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item"><a href="support.php" class="text-decoration-none text-muted">Support Desk</a></li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">#<?= htmlspecialchars($ticket['ticket_number']); ?></li>
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

            <!-- Ticket Details Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 pb-3 border-bottom">
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="font-monospace fw-bold text-primary-accent extra-small">#<?= htmlspecialchars($ticket['ticket_number']); ?></span>
                            <span class="badge <?= $badgeClass; ?> extra-small rounded-pill px-3"><?= ucwords(str_replace('_', ' ', $st)); ?></span>
                            <span class="badge bg-light text-dark border extra-small rounded-pill px-2"><?= ucfirst($ticket['priority']); ?> Priority</span>
                            <span class="badge bg-light text-dark border extra-small rounded-pill px-2"><?= ucwords(str_replace('_', ' ', $ticket['category'])); ?></span>
                        </div>
                        <h1 class="h4 fw-bold text-dark mb-1"><?= htmlspecialchars($ticket['subject']); ?></h1>
                        <span class="text-muted extra-small">Created on <?= date('M j, Y 	 H:i', strtotime($ticket['created_at'])); ?></span>
                    </div>

                    <?php if ($st !== 'resolved' && $st !== 'closed'): ?>
                        <form method="POST" action="support-view.php?id=<?= $ticketId; ?>" class="flex-shrink-0" onsubmit="return confirm('Mark this support ticket as resolved?');">
                            <?= csrfField(); ?>
                            <input type="hidden" name="action" value="close_ticket">
                            <button type="submit" class="btn btn-outline-success btn-sm rounded-pill px-3">
                                <i class="bi bi-check2-circle me-1"></i>Mark Resolved
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Linked Order Information if any -->
                <?php if (!empty($ticket['order_number'])): ?>
                    <div class="mt-3 p-3 bg-light rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-bag-check-fill text-primary-accent fs-5"></i>
                            <div>
                                <span class="small fw-bold text-dark">Linked to Order #<?= htmlspecialchars($ticket['order_number']); ?></span>
                                <span class="extra-small text-muted d-block">$<?= number_format((float)$ticket['order_total'], 2); ?> • Status: <?= ucfirst($ticket['order_status']); ?></span>
                            </div>
                        </div>
                        <a href="order-details.php?order=<?= urlencode($ticket['order_number']); ?>" class="btn btn-outline-pink btn-sm rounded-pill px-3 extra-small">
                            View Order
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Messages Thread -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h2 class="h6 fw-bold text-dark mb-4 pb-2 border-bottom">
                    <i class="bi bi-chat-left-dots text-primary-accent me-2"></i>Conversation History
                </h2>

                <div class="d-flex flex-column gap-3 mb-4">
                    <?php foreach ($messages as $m): ?>
                        <?php $isAdmin = ($m['sender_role'] === 'admin'); ?>
                        <div class="d-flex <?= $isAdmin ? 'justify-content-start' : 'justify-content-end'; ?>">
                            <div class="p-3 <?= $isAdmin ? 'msg-bubble-admin' : 'msg-bubble-customer'; ?>" style="max-width: 80%;">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-1">
                                    <span class="fw-bold extra-small text-dark">
                                        <?php if ($isAdmin): ?>
                                            <i class="bi bi-shield-check text-primary-accent me-1"></i>FastBite Support Team
                                        <?php else: ?>
                                            <i class="bi bi-person-fill text-muted me-1"></i>You (<?= htmlspecialchars($user['name']); ?>)
                                        <?php endif; ?>
                                    </span>
                                    <span class="text-muted" style="font-size: 0.70rem;">
                                        <?= date('M j, H:i', strtotime($m['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="text-dark small" style="white-space: pre-wrap; word-break: break-word;">
                                    <?= htmlspecialchars($m['message']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Reply Box -->
                <div class="pt-3 border-top">
                    <h3 class="h6 fw-bold text-dark mb-3">Post a Reply</h3>
                    <form method="POST" action="support-view.php?id=<?= $ticketId; ?>">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="reply">
                        
                        <div class="mb-3">
                            <textarea name="message" rows="4" class="form-control rounded-3" placeholder="Type your response here..." required></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="extra-small text-muted">Posting a reply will reopen closed tickets automatically.</span>
                            <button type="submit" class="btn btn-auth-primary rounded-pill px-4 shadow-sm">
                                <i class="bi bi-send-fill me-2"></i>Send Reply
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
