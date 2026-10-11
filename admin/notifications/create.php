<?php
/**
 * FastBite - Admin Send Notification / Broadcast
 * STEP 28: FastBite Notifications & Communication Center
 */

$activeMenu = 'notifications';
$pageTitle = 'Compose Notification';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$currentAdmin = currentUser();
$error = '';
$msg = '';

// Fetch all customers for single recipient dropdown
$pdo = getDbConnection();
$customers = $pdo->query("SELECT id, name, email FROM `users` WHERE `role` = 'customer' AND `status` = 'active' ORDER BY `name` ASC")->fetchAll();

// Handle POST send
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security verification failed. Please try again.';
    } else {
        $recipientType = $_POST['recipient_type'] ?? 'single';
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type = trim($_POST['type'] ?? 'system');
        $link = trim($_POST['link'] ?? '');

        if (empty($title) || empty($message)) {
            $error = 'Please provide both a notification title and message content.';
        } elseif ($recipientType === 'single' && $targetUserId <= 0) {
            $error = 'Please select a valid recipient customer.';
        } else {
            $result = dbAdminCreateNotification($recipientType, $targetUserId, $title, $message, $type, $link, $currentAdmin['id']);
            if (!empty($result['success'])) {
                $countSent = $result['count'];
                header("Location: index.php?msg=" . urlencode("Successfully sent notification to {$countSent} recipient(s)."));
                exit;
            } else {
                $error = $result['error'] ?? 'Failed to send notification.';
            }
        }
    }
}
?>

<!-- Main Content -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5">
    <div class="container-fluid p-0" style="max-width: 900px;">
        
        <!-- Breadcrumb & Header -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb extra-small mb-0">
                <li class="breadcrumb-item"><a href="../index.php" class="text-muted text-decoration-none"><i class="bi bi-house me-1"></i>Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php" class="text-muted text-decoration-none">Notifications</a></li>
                <li class="breadcrumb-item active text-primary-accent fw-bold" aria-current="page">Compose</li>
            </ol>
        </nav>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <span class="admin-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent" style="font-size: 0.75rem; letter-spacing: 0.08em;">DISPATCHER</span>
                <h1 class="admin-title fw-bold mb-1 text-dark">Compose Notification</h1>
                <p class="admin-desc text-muted mb-0 small">Send real-time in-app notifications to an individual customer or broadcast to all active customers.</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to List</span>
                </a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <div class="small"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Composer Card -->
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
            <form method="POST" action="create.php" id="notifComposerForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">

                <!-- Recipient Type Switcher -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark small mb-2">Recipient Scope <span class="text-danger">*</span></label>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="card border p-3 rounded-3 cursor-pointer d-flex align-items-start gap-3 h-100 recipient-option" id="label-single">
                                <input class="form-check-input mt-1" type="radio" name="recipient_type" value="single" id="recip-single" checked onchange="toggleRecipientType()">
                                <div>
                                    <span class="fw-bold text-dark d-block small">Single Customer</span>
                                    <span class="text-muted extra-small">Target a specific customer for personal updates or direct announcements.</span>
                                </div>
                            </label>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="card border p-3 rounded-3 cursor-pointer d-flex align-items-start gap-3 h-100 recipient-option" id="label-all">
                                <input class="form-check-input mt-1" type="radio" name="recipient_type" value="all" id="recip-all" onchange="toggleRecipientType()">
                                <div>
                                    <span class="fw-bold text-dark d-block small">Broadcast to All Active Customers</span>
                                    <span class="text-muted extra-small">Send promotional campaigns or system notices to all registered users (<?= count($customers); ?> active).</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Single Recipient Dropdown -->
                <div class="mb-3" id="singleRecipientGroup">
                    <label for="target_user_id" class="form-label fw-bold text-dark small">Select Customer <span class="text-danger">*</span></label>
                    <select name="target_user_id" id="target_user_id" class="form-select">
                        <option value="">-- Choose Customer --</option>
                        <?php foreach ($customers as $cust): ?>
                            <option value="<?= (int)$cust['id']; ?>">
                                <?= htmlspecialchars($cust['name'], ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars($cust['email'], ENT_QUOTES, 'UTF-8'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Notification Type -->
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="notif_type" class="form-label fw-bold text-dark small">Notification Type <span class="text-danger">*</span></label>
                        <select name="type" id="notif_type" class="form-select" required>
                            <option value="promo">Promotions &amp; Offers</option>
                            <option value="system" selected>System Notice</option>
                            <option value="account">Account Alert</option>
                            <option value="order">Order Update</option>
                            <option value="support">Support Response</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="notif_link" class="form-label fw-bold text-dark small">Action Link (Optional)</label>
                        <input type="text" name="link" id="notif_link" class="form-control" placeholder="e.g. menu.php or my-orders.php">
                        <div class="form-text extra-small text-muted">Destination page when customer clicks the notification.</div>
                    </div>
                </div>

                <!-- Title -->
                <div class="mb-3">
                    <label for="notif_title" class="form-label fw-bold text-dark small">Notification Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="notif_title" class="form-control" placeholder="e.g. Weekend Flash Sale: 25% Off All Burgers!" maxlength="150" required>
                </div>

                <!-- Message Content -->
                <div class="mb-4">
                    <label for="notif_message" class="form-label fw-bold text-dark small">Message Content <span class="text-danger">*</span></label>
                    <textarea name="message" id="notif_message" rows="4" class="form-control" placeholder="Write clear, engaging notification copy for your customers..." required></textarea>
                </div>

                <!-- Submit & Actions -->
                <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-auth-primary d-flex align-items-center gap-2" id="btnDispatch">
                        <i class="bi bi-send-fill"></i>
                        <span>Dispatch Notification</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>

<script>
function toggleRecipientType() {
    const isAll = document.getElementById('recip-all').checked;
    const singleGroup = document.getElementById('singleRecipientGroup');
    const singleSelect = document.getElementById('target_user_id');
    
    if (isAll) {
        singleGroup.style.display = 'none';
        singleSelect.removeAttribute('required');
    } else {
        singleGroup.style.display = 'block';
        singleSelect.setAttribute('required', 'required');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
