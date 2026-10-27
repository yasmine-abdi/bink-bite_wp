<?php
/**
 * FastBite - Central Payment System & Gateway Abstraction
 * STEP 26: Payment System & Checkout Finalization
 * 
 * Features:
 * - Payment method definition & availability status
 * - Strict security: Cash on Delivery fully operational, external gateways require real verified credentials
 * - Zero fake transactions & Zero storage of raw card/PIN/CVV/OTP credentials
 * - Payment transactions logging & status synchronization
 * - Reusable status badges and user-facing labels
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(__DIR__));
}

require_once FASTBITE_ROOT_DIR . '/config/database.php';
require_once FASTBITE_ROOT_DIR . '/includes/auth.php';

/**
 * Returns list of all defined payment methods and their current operational status.
 *
 * @return array
 */
function getAvailablePaymentMethods() {
    return [
        'cash_on_delivery' => [
            'id' => 'cash_on_delivery',
            'name' => 'Cash on Delivery',
            'is_available' => true,
            'icon' => 'bi-cash-coin',
            'badge' => 'Available',
            'badge_class' => 'bg-success-subtle text-success border border-success-subtle',
            'description' => 'Pay with cash or local payment upon food delivery at your door.',
            'requires_online_gateway' => false
        ],
        'mobile_money' => [
            'id' => 'mobile_money',
            'name' => 'Mobile Money (EVC Plus / Zaad / Sahal)',
            'is_available' => false, // External provider credentials not configured
            'icon' => 'bi-phone',
            'badge' => 'Coming Soon',
            'badge_class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            'description' => 'Direct mobile money checkout gateway is currently not configured.',
            'requires_online_gateway' => true
        ],
        'card' => [
            'id' => 'card',
            'name' => 'Credit / Debit Card (Visa / Mastercard)',
            'is_available' => false, // Tokenized card provider not configured
            'icon' => 'bi-credit-card',
            'badge' => 'Coming Soon',
            'badge_class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            'description' => 'Secure tokenized card payments are currently not configured.',
            'requires_online_gateway' => true
        ]
    ];
}

/**
 * Checks whether a specific payment method is permitted for checkout.
 *
 * @param string $method
 * @return bool
 */
function isPaymentMethodAllowed($method) {
    $methods = getAvailablePaymentMethods();
    return isset($methods[$method]) && $methods[$method]['is_available'] === true;
}

/**
 * Returns human-readable payment method name.
 *
 * @param string $method
 * @return string
 */
function getPaymentMethodLabel($method) {
    $methods = getAvailablePaymentMethods();
    if (isset($methods[$method])) {
        return $methods[$method]['name'];
    }
    
    // Normalization fallback
    $m = strtolower(trim($method));
    if ($m === 'cash' || $m === 'cod' || $m === 'cash_on_delivery') return 'Cash on Delivery';
    if ($m === 'mobile' || $m === 'mobile_money' || $m === 'evc' || $m === 'zaad') return 'Mobile Money';
    if ($m === 'card' || $m === 'credit_card') return 'Credit / Debit Card';
    return ucfirst(str_replace('_', ' ', $method));
}

/**
 * Returns stylized HTML badge for a payment status.
 *
 * @param string $status
 * @return string
 */
function getPaymentStatusBadge($status) {
    $st = strtolower(trim($status));
    switch ($st) {
        case 'paid':
            return '<span class="badge bg-success text-white extra-small"><i class="bi bi-check-circle-fill me-1"></i>Paid</span>';
        case 'processing':
            return '<span class="badge bg-info text-dark extra-small"><i class="bi bi-arrow-repeat me-1"></i>Processing</span>';
        case 'failed':
            return '<span class="badge bg-danger text-white extra-small"><i class="bi bi-x-circle-fill me-1"></i>Failed</span>';
        case 'refunded':
            return '<span class="badge bg-secondary text-white extra-small"><i class="bi bi-arrow-counterclockwise me-1"></i>Refunded</span>';
        case 'cancelled':
            return '<span class="badge bg-dark text-white extra-small"><i class="bi bi-slash-circle me-1"></i>Cancelled</span>';
        case 'pending':
        default:
            return '<span class="badge bg-warning text-dark extra-small"><i class="bi bi-clock-history me-1"></i>Payment Pending</span>';
    }
}

/**
 * Generates a unique secure transaction reference.
 *
 * @return string
 */
function generatePaymentReference() {
    return 'TXN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Sanitizes request/response payloads before saving to database (strips sensitive payment values).
 *
 * @param mixed $payload
 * @return string
 */
function sanitizePaymentPayload($payload) {
    if (empty($payload)) return '';
    if (is_string($payload)) {
        $decoded = json_decode($payload, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $payload = $decoded;
        } else {
            return mb_substr($payload, 0, 1000);
        }
    }

    if (is_array($payload)) {
        $sensitiveKeys = ['card_number', 'card_num', 'cvv', 'cvc', 'pin', 'otp', 'password', 'secret', 'api_key', 'token'];
        array_walk_recursive($payload, function(&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $value = '[REDACTED]';
            }
        });
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    return '';
}

/**
 * Retrieves payment transaction record for a specific order.
 *
 * @param int $orderId
 * @return array|null
 */
function dbGetPaymentForOrder($orderId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM `payment_transactions` WHERE `order_id` = ? ORDER BY `id` DESC LIMIT 1");
    $stmt->execute([(int)$orderId]);
    return $stmt->fetch() ?: null;
}

/**
 * Marks payment as paid for an order and syncs payment_transactions table.
 *
 * @param int $orderId
 * @param string $collectedBy Note or admin identifier
 * @return bool
 */
function dbMarkPaymentPaid($orderId, $collectedBy = 'Admin') {
    $pdo = getDbConnection();
    try {
        $pdo->beginTransaction();

        // 1. Update order
        $stmtOrder = $pdo->prepare("UPDATE `orders` SET `payment_status` = 'paid', `updated_at` = NOW() WHERE `id` = ?");
        $stmtOrder->execute([(int)$orderId]);

        // 2. Update transaction
        $stmtTxn = $pdo->prepare("UPDATE `payment_transactions` SET `status` = 'paid', `paid_at` = NOW(), `updated_at` = NOW() WHERE `order_id` = ?");
        $stmtTxn->execute([(int)$orderId]);

        // 3. Send notification to user
        $ordStmt = $pdo->prepare("SELECT `user_id`, `order_number`, `total` FROM `orders` WHERE `id` = ?");
        $ordStmt->execute([(int)$orderId]);
        $ord = $ordStmt->fetch();
        if ($ord) {
            dbCreateNotification(
                (int)$ord['user_id'],
                "Payment Received for Order #{$ord['order_number']}",
                "Payment of $" . number_format((float)$ord['total'], 2) . " has been confirmed and marked as Paid.",
                'order',
                "order-details.php?order={$ord['order_number']}"
            );
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Marks payment as failed.
 *
 * @param int $orderId
 * @param string $reason
 * @return bool
 */
function dbMarkPaymentFailed($orderId, $reason = '') {
    $pdo = getDbConnection();
    try {
        $pdo->beginTransaction();

        $stmtOrder = $pdo->prepare("UPDATE `orders` SET `payment_status` = 'failed', `updated_at` = NOW() WHERE `id` = ?");
        $stmtOrder->execute([(int)$orderId]);

        $stmtTxn = $pdo->prepare("UPDATE `payment_transactions` SET `status` = 'failed', `failure_reason` = ?, `updated_at` = NOW() WHERE `order_id` = ?");
        $stmtTxn->execute([$reason, (int)$orderId]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Retrieves all payment transactions with filters.
 *
 * @param array $filters
 * @return array
 */
function dbGetAllPaymentTransactions($filters = []) {
    $pdo = getDbConnection();
    $sql = "SELECT pt.*, o.order_number, o.order_status, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
            FROM `payment_transactions` pt 
            JOIN `orders` o ON pt.order_id = o.id 
            JOIN `users` u ON pt.user_id = u.id 
            WHERE 1=1";
    $params = [];

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $sql .= " AND pt.status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['method']) && $filters['method'] !== 'all') {
        $sql .= " AND pt.payment_method = ?";
        $params[] = $filters['method'];
    }

    if (!empty($filters['search'])) {
        $sql .= " AND (pt.transaction_reference LIKE ? OR o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $s = '%' . trim($filters['search']) . '%';
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }

    if (function_exists('dbBuildDateFilterSql')) {
        $dateFilter = dbBuildDateFilterSql($filters['date_range'] ?? '', $filters['date_from'] ?? '', $filters['date_to'] ?? '', 'pt.created_at');
        if (!empty($dateFilter['sql'])) {
            $sql .= $dateFilter['sql'];
            foreach ($dateFilter['params'] as $p) {
                $params[] = $p;
            }
        }
    }

    $sql .= " ORDER BY pt.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
