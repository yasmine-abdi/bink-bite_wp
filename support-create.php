<?php
/**
 * FastBite - Open New Support Ticket
 * STEP 33: Complete Business Platform, Payments, Analytics, Support, Courier Portal & Production Security
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';

requireCustomer('login.php');

$user = currentUser();
$userId = (int)$user['id'];
$currentPage = 'support';
$projectName = 'FastBite';

// Fetch customer's recent orders for optional order dropdown
$userOrders = dbGetUserOrders($userId);

$preselectedOrder = trim($_GET['order'] ?? '');
$preselectedOrderId = 0;
if (!empty($preselectedOrder)) {
    foreach ($userOrders as $uo) {
        if ($uo['order_number'] === $preselectedOrder) {
            $preselectedOrderId = (int)$uo['id'];
            break;
        }
    }
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security validation failed. Please refresh the page and try again.';
    } else {
        $subject = trim($_POST['subject'] ?? '');
        $category = trim($_POST['category'] ?? 'general');
        $priority = trim($_POST['priority'] ?? 'medium');
        $orderId = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
        $message = trim($_POST['message'] ?? '');

        $result = dbCreateSupportTicket($userId, [
            'subject' => $subject,
            'category' => $category,
            'priority' => $priority,
            'order_id' => $orderId
        ], $message);

        if ($result['success']) {
            header("Location: support-view.php?id=" . $result['ticket_id'] . "&msg=" . urlencode($result['message']));
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open Support Ticket - <?= htmlspecialchars($projectName); ?></title>
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
        <div class="container" style="max-width: 800px;">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item"><a href="support.php" class="text-decoration-none text-muted">Support Desk</a></li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Open Ticket</li>
                </ol>
            </nav>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <div class="d-flex align-items-center gap-3 mb-4 pb-2 border-bottom">
                    <div class="rounded-circle bg-pink-soft text-primary-accent d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; font-size: 1.4rem;">
                        <i class="bi bi-chat-square-text-fill"></i>
                    </div>
                    <div>
                        <h1 class="h4 fw-bold text-dark mb-1">Submit a Support Ticket</h1>
                        <p class="text-muted small mb-0">Fill in the details below and our customer care team will assist you shortly.</p>
                    </div>
                </div>

                <form method="POST" action="support-create.php">
                    <?= csrfField(); ?>

                    <div class="mb-3">
                        <label for="subject" class="form-label small fw-bold text-dark">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="subject" class="form-control rounded-3 py-2" placeholder="e.g. Order delivery status inquiry, payment receipt help..." required value="<?= htmlspecialchars($_POST['subject'] ?? ''); ?>">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="category" class="form-label small fw-bold text-dark">Inquiry Category <span class="text-danger">*</span></label>
                            <select name="category" id="category" class="form-select rounded-3 py-2" required>
                                <option value="order_issue" <?= (($_POST['category'] ?? '') === 'order_issue') ? 'selected' : ''; ?>>Order Issue</option>
                                <option value="delivery" <?= (($_POST['category'] ?? '') === 'delivery') ? 'selected' : ''; ?>>Delivery &amp; Courier</option>
                                <option value="payment" <?= (($_POST['category'] ?? '') === 'payment') ? 'selected' : ''; ?>>Payment &amp; Billing</option>
                                <option value="food_quality" <?= (($_POST['category'] ?? '') === 'food_quality') ? 'selected' : ''; ?>>Food Quality / Missing Item</option>
                                <option value="account" <?= (($_POST['category'] ?? '') === 'account') ? 'selected' : ''; ?>>Account &amp; Security</option>
                                <option value="general" <?= (($_POST['category'] ?? '') === 'general' || empty($_POST['category'])) ? 'selected' : ''; ?>>General Inquiry</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="priority" class="form-label small fw-bold text-dark">Priority Level <span class="text-danger">*</span></label>
                            <select name="priority" id="priority" class="form-select rounded-3 py-2" required>
                                <option value="low" <?= (($_POST['priority'] ?? '') === 'low') ? 'selected' : ''; ?>>Low Priority</option>
                                <option value="medium" <?= (($_POST['priority'] ?? '') === 'medium' || empty($_POST['priority'])) ? 'selected' : ''; ?>>Medium (Normal)</option>
                                <option value="high" <?= (($_POST['priority'] ?? '') === 'high') ? 'selected' : ''; ?>>High</option>
                                <option value="urgent" <?= (($_POST['priority'] ?? '') === 'urgent') ? 'selected' : ''; ?>>Urgent (Active Order)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="order_id" class="form-label small fw-bold text-dark">Related Order <span class="text-muted fw-normal">(Optional)</span></label>
                        <select name="order_id" id="order_id" class="form-select rounded-3 py-2">
                            <option value="">-- No Specific Order / General Support --</option>
                            <?php foreach ($userOrders as $uo): ?>
                                <option value="<?= $uo['id']; ?>" <?= ($preselectedOrderId === (int)$uo['id'] || (int)($_POST['order_id'] ?? 0) === (int)$uo['id']) ? 'selected' : ''; ?>>
                                    Order #<?= htmlspecialchars($uo['order_number']); ?> — $<?= number_format((float)$uo['total'], 2); ?> (<?= date('M j, Y', strtotime($uo['created_at'])); ?>) - <?= ucfirst($uo['order_status']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label small fw-bold text-dark">Description Message <span class="text-danger">*</span></label>
                        <textarea name="message" id="message" rows="5" class="form-control rounded-3" placeholder="Please describe your question or issue in detail..." required><?= htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <a href="support.php" class="btn btn-light rounded-pill px-4 border">Cancel</a>
                        <button type="submit" class="btn btn-auth-primary rounded-pill px-4 shadow-sm">
                            <i class="bi bi-send-fill me-2"></i>Submit Ticket
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </main>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
