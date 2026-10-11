<?php
/**
 * FastBite - Admin Contact Inquiries & Messages
 * STEP 25: FastBite Admin Dashboard & Business Control Center
 */

$pageTitle = 'Contact Inquiries & Support Messages';
$activeMenu = 'messages';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$msg = '';
$error = '';

// Handle POST actions (Update Status, Delete)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed.';
    } else {
    $action = $_POST['action'] ?? '';
    $msgId = (int)($_POST['msg_id'] ?? 0);

    if ($action === 'update_status' && $msgId > 0) {
        $newStatus = trim($_POST['status'] ?? '');
        if (dbUpdateContactMessageStatus($msgId, $newStatus)) {
            $msg = "Message status updated to '" . ucfirst($newStatus) . "'.";
        } else {
            $error = "Failed to update message status.";
        }
    } elseif ($action === 'delete_message' && $msgId > 0) {
        if (dbDeleteContactMessage($msgId)) {
            $msg = "Message deleted successfully.";
        } else {
            $error = "Failed to delete message.";
        }
    }
    }
}

// Search & Filter
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');

$messages = dbGetAllContactMessages([
    'status' => $statusFilter,
    'search' => $searchQuery
]);

// Summary counts
$allMessages = dbGetAllContactMessages();
$totalMessages = count($allMessages);
$newMessages = count(array_filter($allMessages, fn($m) => $m['status'] === 'new'));
$readMessages = count(array_filter($allMessages, fn($m) => $m['status'] === 'read'));
$resolvedMessages = count(array_filter($allMessages, fn($m) => $m['status'] === 'resolved'));
?>

<!-- Admin Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0">

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent" style="font-size: 0.75rem; letter-spacing: 0.08em;">CUSTOMER SUPPORT</span>
                <h1 class="admin-title fw-bold mb-1 text-dark">Contact Inquiries &amp; Messages</h1>
                <p class="admin-desc text-muted mb-0 small">
                    Manage direct messages, feedback, and customer inquiries sent via contact.php.
                </p>
            </div>
            <div>
                <a href="../../contact.php" target="_blank" class="btn btn-outline-pink btn-sm d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>Preview Contact Form</span>
                </a>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Messages Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-3 stat-hover-card">
                    <span class="text-muted extra-small text-uppercase fw-bold">Total Inquiries</span>
                    <div class="h3 fw-bold text-dark my-1"><?= $totalMessages; ?></div>
                    <span class="extra-small text-muted">All customer messages</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-3 stat-hover-card <?= ($newMessages > 0) ? 'border-start border-danger border-4' : ''; ?>">
                    <span class="text-muted extra-small text-uppercase fw-bold">New (Unread)</span>
                    <div class="h3 fw-bold text-danger my-1"><?= $newMessages; ?></div>
                    <span class="extra-small text-muted">Awaiting response</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-3 stat-hover-card">
                    <span class="text-muted extra-small text-uppercase fw-bold">In Review (Read)</span>
                    <div class="h3 fw-bold text-info my-1"><?= $readMessages; ?></div>
                    <span class="extra-small text-muted">Under investigation</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-0 bg-white shadow-sm h-100 rounded-3 stat-hover-card">
                    <span class="text-muted extra-small text-uppercase fw-bold">Resolved</span>
                    <div class="h3 fw-bold text-success my-1"><?= $resolvedMessages; ?></div>
                    <span class="extra-small text-muted">Completed inquiries</span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card p-3 border-0 bg-white shadow-sm rounded-3 mb-4">
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="msgSearch" class="form-label extra-small text-muted fw-bold text-uppercase mb-1">Search Messages</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="msgSearch" class="form-control border-start-0" placeholder="Search by name, email, subject, text..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3">
                    <label for="msgStatus" class="form-label extra-small text-muted fw-bold text-uppercase mb-1">Status</label>
                    <select name="status" id="msgStatus" class="form-select">
                        <option value="all" <?= ($statusFilter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="new" <?= ($statusFilter === 'new') ? 'selected' : ''; ?>>New (Unread)</option>
                        <option value="read" <?= ($statusFilter === 'read') ? 'selected' : ''; ?>>Read</option>
                        <option value="resolved" <?= ($statusFilter === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>

                <div class="col-6 col-md-2 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-auth-primary w-100 btn-sm py-2">Filter</button>
                    <?php if (!empty($searchQuery) || $statusFilter !== 'all'): ?>
                        <a href="index.php" class="btn btn-outline-pink btn-sm py-2" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Messages Table -->
        <div class="card border-0 bg-white shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <h2 class="h6 fw-bold mb-0 text-dark">Customer Inquiries Feed</h2>
                    <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small"><?= count($messages); ?> Messages</span>
                </div>
            </div>

            <?php if (empty($messages)): ?>
                <div class="text-center py-5 px-3">
                    <div class="stat-icon-wrap bg-pink-soft text-primary-accent rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-envelope-open fs-3"></i>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-1">No Messages Found</h3>
                    <p class="text-muted extra-small mb-3">There are no customer inquiries matching your criteria.</p>
                    <a href="index.php" class="btn btn-outline-pink btn-sm">Reset Filters</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 extra-small">
                        <thead class="table-light text-uppercase text-muted">
                            <tr>
                                <th scope="col" class="ps-4">Sender</th>
                                <th scope="col">Subject</th>
                                <th scope="col" style="max-width: 300px;">Message Snippet</th>
                                <th scope="col">Date Received</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col" class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $m): ?>
                                <?php
                                    $st = $m['status'];
                                    $badge = 'bg-secondary';
                                    if ($st === 'new') $badge = 'bg-danger text-white fw-bold';
                                    elseif ($st === 'read') $badge = 'bg-info-subtle text-info border border-info-subtle';
                                    elseif ($st === 'resolved') $badge = 'bg-success-subtle text-success border border-success-subtle';
                                ?>
                                <tr class="<?= ($st === 'new') ? 'table-light fw-semibold' : ''; ?>">
                                    <td class="ps-4">
                                        <span class="d-block fw-bold text-dark"><?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="text-muted" style="font-size: 0.70rem;"><?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if (!empty($m['phone'])): ?>
                                            <span class="text-muted d-block" style="font-size: 0.68rem;"><?= htmlspecialchars($m['phone'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?= htmlspecialchars($m['subject'] ?? 'General Inquiry', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </td>
                                    <td style="max-width: 300px;">
                                        <p class="text-muted text-truncate mb-0" style="max-width: 280px;">
                                            <?= htmlspecialchars($m['message'], ENT_QUOTES, 'UTF-8'); ?>
                                        </p>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?= date('M j, Y, g:i A', strtotime($m['created_at'])); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $badge; ?> extra-small text-uppercase">
                                            <?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <!-- View Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-outline-pink py-1 px-2" data-bs-toggle="modal" data-bs-target="#viewMessageModal-<?= $m['id']; ?>" title="Read Message">
                                                <i class="bi bi-eye"></i>
                                            </button>

                                            <!-- Status Quick Update Dropdown -->
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-outline-secondary py-1 px-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Change Status">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm extra-small">
                                                    <li>
                                                        <form method="POST" action="index.php"><?= csrfField(); ?>
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="msg_id" value="<?= $m['id']; ?>">
                                                            <input type="hidden" name="status" value="new">
                                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2"><i class="bi bi-envelope text-danger"></i> Mark as New</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="index.php"><?= csrfField(); ?>
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="msg_id" value="<?= $m['id']; ?>">
                                                            <input type="hidden" name="status" value="read">
                                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2"><i class="bi bi-envelope-open text-info"></i> Mark as Read</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="index.php"><?= csrfField(); ?>
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="msg_id" value="<?= $m['id']; ?>">
                                                            <input type="hidden" name="status" value="resolved">
                                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2"><i class="bi bi-check2-circle text-success"></i> Mark as Resolved</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>

                                            <!-- Delete Button -->
                                            <form method="POST" action="index.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this message?');">
            <?= csrfField(); ?>
                                                <input type="hidden" name="action" value="delete_message">
                                                <input type="hidden" name="msg_id" value="<?= $m['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Message View Modal -->
                                        <div class="modal fade" id="viewMessageModal-<?= $m['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered text-start">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header border-bottom py-3 px-4 bg-white">
                                                        <h5 class="modal-title fw-bold text-dark h6 mb-0 d-flex align-items-center gap-2">
                                                            <i class="bi bi-envelope-open text-primary-accent"></i>
                                                            <span>Customer Message</span>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4 bg-light-subtle">
                                                        <div class="mb-3 pb-3 border-bottom">
                                                            <span class="d-block extra-small text-muted text-uppercase fw-bold">From</span>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                            <div class="extra-small text-muted">
                                                                <a href="mailto:<?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8'); ?>" class="text-primary-accent text-decoration-none"><?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8'); ?></a>
                                                                <?php if (!empty($m['phone'])): ?>
                                                                    • <?= htmlspecialchars($m['phone'], ENT_QUOTES, 'UTF-8'); ?>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="extra-small text-muted mt-1">Received on <?= date('F j, Y 	 g:i A', strtotime($m['created_at'])); ?></div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <span class="d-block extra-small text-muted text-uppercase fw-bold mb-1">Subject</span>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($m['subject'] ?? 'General Inquiry', ENT_QUOTES, 'UTF-8'); ?></div>
                                                        </div>
                                                        <div>
                                                            <span class="d-block extra-small text-muted text-uppercase fw-bold mb-1">Message Content</span>
                                                            <div class="p-3 bg-white rounded border extra-small text-dark text-break" style="white-space: pre-wrap; line-height: 1.6;">
                                                                <?= htmlspecialchars($m['message'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top bg-white py-2 px-4 justify-content-between">
                                                        <a href="mailto:<?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8'); ?>?subject=Re:%20<?= urlencode($m['subject'] ?? 'FastBite Inquiry'); ?>" class="btn btn-auth-primary btn-sm d-inline-flex align-items-center gap-1">
                                                            <i class="bi bi-reply-fill"></i> Reply via Email
                                                        </a>
                                                        <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
