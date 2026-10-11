<?php
/**
 * FastBite - Secure Payment Gateway Callback & Webhook Endpoint
 * STEP 33: Complete Business Platform, Payments, Analytics, Support, Courier Portal & Production Security
 * 
 * Provider-agnostic listener for:
 * - Stripe Webhooks & Redirect Callbacks
 * - PayPal IPN / Webhooks
 * - Mobile Money (EVC Plus, Zaad, Sahal) IPN Callbacks
 * - Custom gateway notifications
 * 
 * Features:
 * - Transaction reference and order verification
 * - Amount validation against MySQL order records
 * - Idempotency protection (safely ignores duplicate webhook deliveries)
 * - Zero storage of raw/sensitive card numbers, CVVs, PINs
 * - Automated synchronization of orders and payment_transactions tables
 * - Dispatches notifications to customer and logs audit entries in security_logs
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', __DIR__);
}

require_once FASTBITE_ROOT_DIR . '/config/database.php';
require_once FASTBITE_ROOT_DIR . '/includes/auth.php';
require_once FASTBITE_ROOT_DIR . '/includes/payment.php';

// Ensure defensive security headers
sendSecurityHeaders();

// Determine provider and request format
$provider = strtolower(trim($_GET['provider'] ?? $_POST['provider'] ?? 'gateway'));
$rawBody = file_get_contents('php://input');
$isJsonRequest = false;
$payload = [];

if (!empty($rawBody)) {
    $decoded = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $payload = $decoded;
        $isJsonRequest = true;
    }
}

if (empty($payload)) {
    $payload = !empty($_POST) ? $_POST : $_GET;
}

// Check if request is an API/webhook call or a browser redirect
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$isWebhook = $isJsonRequest || (strpos($acceptHeader, 'application/json') !== false) || (isset($_GET['format']) && $_GET['format'] === 'json');

// Process callback with authoritative DB handler
$result = dbHandlePaymentCallback($payload, $provider);

if ($isWebhook) {
    // Return structured JSON response with appropriate HTTP status code
    http_response_code($result['code'] ?? ($result['success'] ? 200 : 400));
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $result['success'],
        'message' => $result['message'],
        'provider' => $provider,
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Browser Redirect Flow (for hosted payment redirects)
$orderNumber = trim($payload['order_number'] ?? $_GET['order'] ?? '');
if (!empty($orderNumber)) {
    $redirectUrl = "confirmation.php?order=" . urlencode($orderNumber);
    if ($result['success']) {
        $redirectUrl .= "&msg=" . urlencode("Payment received successfully!");
    } else {
        $redirectUrl .= "&error=" . urlencode($result['message']);
    }
    header("Location: " . $redirectUrl);
    exit;
}

// Fallback HTML response if no order specified
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status - FastBite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 text-center" style="max-width: 500px; width: 90%;">
        <div class="mb-3">
            <?php if ($result['success']): ?>
                <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-check-circle-fill fs-2"></i>
                </div>
                <h1 class="h4 fw-bold text-dark">Payment Processed</h1>
            <?php else: ?>
                <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-exclamation-triangle-fill fs-2"></i>
                </div>
                <h1 class="h4 fw-bold text-dark">Payment Notice</h1>
            <?php endif; ?>
            <p class="text-muted small mb-4"><?= htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="d-flex gap-2 justify-content-center">
            <a href="customer-dashboard.php" class="btn btn-auth-primary btn-sm px-4">Go to Dashboard</a>
            <a href="menu.php" class="btn btn-outline-pink btn-sm px-4">Browse Menu</a>
        </div>
    </div>
</body>
</html>
