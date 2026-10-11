<?php
/**
 * FastBite - Admin Order Details, Fulfillment Operations & Print Receipt
 * STEP 31: Authentication Finalization, Google Sign-In & Professional Receipts
 */

$pageTitle = 'Order Operations & Receipt';
$activeMenu = 'orders';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../../includes/image-helper.php';
require_once __DIR__ . '/../../includes/payment.php';

$orderIdent = trim($_GET['order'] ?? $_GET['id'] ?? '');
$successMsg = $_GET['msg'] ?? '';
$errorMsg = $_GET['error'] ?? '';
$currentAdmin = currentUser();
$adminId = $currentAdmin ? (int)$currentAdmin['id'] : null;

// Authoritative restaurant info
$restName = dbGetSetting('restaurant_name', 'FastBite Fast Food');
$restPhone = dbGetSetting('restaurant_phone', '+252 63 444 5555');
$restEmail = dbGetSetting('restaurant_email', 'support@fastbite.com');
$restAddress = dbGetSetting('restaurant_address', 'Main Hospitality Blvd, City Center, Hargeisa');

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = "Security validation failed. Please refresh and try again.";
    } else {
        $action = $_POST['action'] ?? '';
        $orderId = (int)($_POST['order_id'] ?? 0);

        if ($action === 'update_status' && $orderId > 0) {
            $newStatus = trim($_POST['new_status'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            if (dbUpdateOrderStatus($orderId, $newStatus, $adminId, $reason)) {
                $successMsg = "Order fulfillment status updated to '" . ucfirst(str_replace('_', ' ', $newStatus)) . "' and customer notified.";
            } else {
                $errorMsg = "Invalid order status transition. Rejected by state machine.";
            }
        } elseif ($action === 'mark_payment_paid' && $orderId > 0) {
            if (dbMarkPaymentPaid($orderId, 'Admin Order Details', $adminId)) {
                $successMsg = "Payment marked as PAID and transaction logged.";
            } else {
                $errorMsg = "Failed to update payment status.";
            }
        } elseif ($action === 'send_receipt' && $orderId > 0) {
            $dispatchRes = dbDispatchOrderReceiptEmail($orderId, $adminId);
            if (!empty($dispatchRes['sent'])) {
                $successMsg = $dispatchRes['reason'] ?? "Official receipt successfully emailed to customer.";
            } else {
                $errorMsg = $dispatchRes['reason'] ?? "Email delivery service is not configured. Receipt was not sent.";
            }
        }
    }
}

$order = dbGetOrderWithFullDetails($orderIdent);
?>
<style>
    @media print {
        body, html { background: #fff !important; color: #000 !important; font-size: 11pt !important; margin: 0 !important; padding: 0 !important; }
        .admin-sidebar, .admin-navbar, .admin-sidebar-backdrop, .breadcrumb, .alert, .btn, .modal, .d-print-none, .workflow-controls, .timeline-stepper { display: none !important; }
        .admin-main { padding: 0 !important; margin: 0 !important; width: 100% !important; }
        .admin-layout { display: block !important; }
        .card { border: none !important; box-shadow: none !important; padding: 0 !important; }
        .admin-print-receipt { display: block !important; width: 100% !important; padding: 20px !important; border: 1px solid #111 !important; margin: 0 auto !important; }
        .table { width: 100% !important; border-collapse: collapse !important; }
        .table th, .table td { border-bottom: 1px solid #ddd !important; padding: 6px 8px !important; color: #000 !important; }
    }
    @media screen {
        .admin-print-receipt { display: none; }
    }
</style>

<!-- Main Content Area -->
<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5 bg-light">
    <div class="container-fluid p-0">

        <!-- Breadcrumb & Top Bar -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom d-print-none">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 extra-small">
                        <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none text-muted"><i class="bi bi-house me-1"></i>Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Orders</a></li>
                        <li class="breadcrumb-item active text-primary-accent fw-bold" aria-current="page">#<?= htmlspecialchars($order['order_number'] ?? $orderIdent); ?></li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-dark mb-0">Order Operations &amp; Receipt</h1>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group btn-group-sm">
                    <a href="../../receipt.php?order=<?= urlencode($order['order_number'] ?? $orderIdent); ?>&print=1" target="_blank" class="btn btn-outline-dark btn-sm rounded-start-pill px-3 shadow-sm d-flex align-items-center gap-1">
                        <i class="bi bi-printer"></i>
                        <span>Print A4</span>
                    </a>
                    <a href="../../receipt.php?order=<?= urlencode($order['order_number'] ?? $orderIdent); ?>&format=thermal&print=1" target="_blank" class="btn btn-outline-dark btn-sm rounded-end-pill px-3 shadow-sm d-flex align-items-center gap-1">
                        <i class="bi bi-receipt-cutoff"></i>
                        <span>Print Thermal</span>
                    </a>
                </div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i>Back to Orders
                </a>
            </div>
        </div>

        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4 d-print-none" role="alert">
                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4 d-print-none" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!$order): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4 bg-white">
                <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                    <i class="bi bi-receipt fs-2"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2">Order Not Found</h4>
                <p class="text-muted small mb-4">We could not locate this order record in the database.</p>
                <div>
                    <a href="index.php" class="btn btn-auth-primary rounded-pill px-4">Back to Orders</a>
                </div>
            </div>
        <?php else: 
            $currSt = $order['order_status'];
            $stepIndices = [
                'pending' => 1,
                'confirmed' => 2,
                'preparing' => 3,
                'out_for_delivery' => 4,
                'delivered' => 5,
                'cancelled' => 0
            ];
            $currentStep = $stepIndices[$currSt] ?? 1;

            $nextOptions = [];
            if ($currSt === 'pending') {
                $nextOptions = ['confirmed' => 'Confirm Order'];
            } elseif ($currSt === 'confirmed') {
                $nextOptions = ['preparing' => 'Send to Kitchen'];
            } elseif ($currSt === 'preparing') {
                $nextOptions = ['out_for_delivery' => 'Dispatch Courier'];
            } elseif ($currSt === 'out_for_delivery') {
                $nextOptions = ['delivered' => 'Mark Delivered'];
            }
        ?>

            <!-- Top Order Summary Banner (Screen View) -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white d-print-none">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small fw-bold">ORDER FULFILLMENT</span>
                            <h2 class="h4 fw-bold text-dark mb-0">Order #<?= htmlspecialchars($order['order_number']); ?></h2>
                            
                            <?php
                            $stBadge = match($currSt) {
                                'pending' => '<span class="badge bg-warning text-dark border border-warning extra-small"><i class="bi bi-clock me-1"></i>Pending Review</span>',
                                'confirmed' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small"><i class="bi bi-check2-circle me-1"></i>Confirmed</span>',
                                'preparing' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small"><i class="bi bi-fire me-1"></i>In Kitchen</span>',
                                'out_for_delivery' => '<span class="badge bg-info-subtle text-info border border-info-subtle extra-small"><i class="bi bi-truck me-1"></i>On Way</span>',
                                'delivered' => '<span class="badge bg-success-subtle text-success border border-success-subtle extra-small"><i class="bi bi-check-circle-fill me-1"></i>Delivered</span>',
                                'cancelled' => '<span class="badge bg-secondary-subtle text-muted border extra-small"><i class="bi bi-x-circle-fill me-1"></i>Cancelled</span>',
                                default => '<span class="badge bg-light text-dark extra-small">' . htmlspecialchars($currSt) . '</span>'
                            };
                            echo $stBadge;
                            ?>

                            <?= getPaymentStatusBadge($order['payment_status']); ?>
                        </div>
                        <p class="text-muted extra-small mb-0">
                            Placed on <strong><?= date('F j, Y  h:i A', strtotime($order['created_at'])); ?></strong> &bull; 
                            Last Updated: <strong><?= date('M d, Y  h:i A', strtotime($order['updated_at'] ?? $order['created_at'])); ?></strong>
                        </p>
                    </div>

                    <!-- Workflow Action Controls -->
                    <div class="d-flex flex-wrap align-items-center gap-2 workflow-controls">
                        <?php if (!empty($nextOptions)): ?>
                            <?php foreach ($nextOptions as $ns => $lbl): ?>
                                <form action="view.php?order=<?= urlencode($order['order_number']); ?>" method="POST" onsubmit="return confirm('Advance order status to <?= $lbl; ?>?');">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                                    <input type="hidden" name="new_status" value="<?= $ns; ?>">
                                    <button type="submit" class="btn btn-auth-primary btn-sm rounded-pill px-3 shadow-sm fw-semibold d-flex align-items-center gap-1">
                                        <i class="bi bi-arrow-right-circle"></i>
                                        <span><?= $lbl; ?></span>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if ($currSt !== 'delivered' && $currSt !== 'cancelled'): ?>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                                <i class="bi bi-x-circle"></i>
                                <span>Cancel Order</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 5-Step Timeline Stepper -->
                <div class="mt-4 pt-3 border-top timeline-stepper">
                    <?php if ($currSt === 'cancelled'): ?>
                        <div class="alert alert-danger mb-0 rounded-3 d-flex align-items-center gap-3">
                            <i class="bi bi-x-octagon-fill fs-3 text-danger flex-shrink-0"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-danger">Order Cancelled</h6>
                                <p class="extra-small mb-0 text-muted">This order was cancelled on <?= date('M d, Y  h:i A', strtotime($order['updated_at'] ?? $order['created_at'])); ?>.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row g-2 text-center">
                            <?php
                            $stepperItems = [
                                1 => ['name' => '01 Placed', 'icon' => 'bi-receipt'],
                                2 => ['name' => '02 Confirmed', 'icon' => 'bi-check2-circle'],
                                3 => ['name' => '03 In Kitchen', 'icon' => 'bi-fire'],
                                4 => ['name' => '04 On Way', 'icon' => 'bi-truck'],
                                5 => ['name' => '05 Delivered', 'icon' => 'bi-bag-check-fill']
                            ];
                            foreach ($stepperItems as $stepNum => $stItem):
                                $isCompleted = ($currentStep >= $stepNum);
                                $isCurrent = ($currentStep === $stepNum);
                            ?>
                                <div class="col">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center mb-1 <?= $isCurrent ? 'bg-primary-accent text-white shadow' : ($isCompleted ? 'bg-success text-white' : 'bg-light text-muted border'); ?>" style="width: 38px; height: 38px;">
                                            <i class="bi <?= $stItem['icon']; ?> fs-6"></i>
                                        </div>
                                        <span class="extra-small fw-bold <?= $isCompleted ? 'text-dark' : 'text-muted opacity-75'; ?>"><?= $stItem['name']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Two Column Layout (Screen View) -->
            <div class="row g-4 d-print-none">
                <div class="col-12 col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <h3 class="h6 fw-bold text-dark mb-0">Ordered Items (<?= count($order['items']); ?>)</h3>
                            <span class="extra-small text-muted">Immutable snapshot of items at checkout</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="extra-small text-uppercase text-muted">Food Item</th>
                                        <th class="extra-small text-uppercase text-muted text-center">Unit Price</th>
                                        <th class="extra-small text-uppercase text-muted text-center">Qty</th>
                                        <th class="extra-small text-uppercase text-muted text-center">Discount</th>
                                        <th class="extra-small text-uppercase text-muted text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?= htmlspecialchars(getFoodImage($item['image'] ?? '')); ?>" alt="<?= htmlspecialchars($item['food_name']); ?>" class="rounded-2 object-fit-cover flex-shrink-0" style="width: 44px; height: 44px;" onerror="handleImageError(this, 'food')">
                                                    <div>
                                                        <span class="d-block fw-bold text-dark small"><?= htmlspecialchars($item['food_name']); ?></span>
                                                        <span class="badge bg-light text-muted border extra-small"><?= htmlspecialchars($item['food_category'] ?? 'Fast Food'); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center small">$<?= number_format((float)$item['unit_price'], 2); ?></td>
                                            <td class="text-center fw-bold small">&times; <?= (int)$item['quantity']; ?></td>
                                            <td class="text-center small text-success">
                                                <?= (float)$item['discount'] > 0 ? '-$' . number_format((float)$item['discount'], 2) : '$0.00'; ?>
                                            </td>
                                            <td class="text-end fw-bold text-dark small">$<?= number_format((float)$item['subtotal'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Financial Summary -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="row justify-content-end">
                                <div class="col-12 col-md-6 col-xl-5">
                                    <div class="vstack gap-2">
                                        <div class="d-flex justify-content-between small text-muted">
                                            <span>Order Subtotal</span>
                                            <strong class="text-dark">$<?= number_format((float)$order['subtotal'], 2); ?></strong>
                                        </div>
                                        <?php if ((float)$order['discount'] > 0): ?>
                                            <div class="d-flex justify-content-between small text-success">
                                                <span>Menu Item Discounts</span>
                                                <strong class="text-success">-$<?= number_format((float)$order['discount'], 2); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($order['coupon_discount']) && (float)$order['coupon_discount'] > 0): ?>
                                            <div class="d-flex justify-content-between small text-primary-accent fw-bold">
                                                <span>Promo Coupon (<?= htmlspecialchars($order['coupon_code'] ?? 'PROMO'); ?>)</span>
                                                <strong class="text-primary-accent">-$<?= number_format((float)$order['coupon_discount'], 2); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between small text-muted">
                                            <span>Delivery Fee</span>
                                            <strong class="text-dark"><?= (float)$order['delivery_fee'] > 0 ? '$' . number_format((float)$order['delivery_fee'], 2) : '<span class="text-success">FREE</span>'; ?></strong>
                                        </div>
                                        <?php if (isset($order['tax']) && (float)$order['tax'] > 0): ?>
                                            <div class="d-flex justify-content-between small text-muted">
                                                <span>Estimated Tax</span>
                                                <strong class="text-dark">$<?= number_format((float)$order['tax'], 2); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                        <hr class="my-1 text-muted opacity-25">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-dark fs-6">Grand Total</span>
                                            <span class="fs-5 fw-bold text-primary-accent">$<?= number_format((float)$order['total'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($order['notes'])): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                            <h3 class="h6 fw-bold text-dark mb-2"><i class="bi bi-chat-left-quote me-2 text-primary-accent"></i>Customer Special Instructions</h3>
                            <div class="p-3 bg-light rounded-3 border small text-muted">
                                <?= nl2br(htmlspecialchars($order['notes'], ENT_QUOTES, 'UTF-8')); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-lg-4">
                    <!-- Receipt Center Card -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <h3 class="h6 fw-bold text-dark mb-0"><i class="bi bi-receipt me-2 text-primary-accent"></i>Receipt Center</h3>
                            <?php
                            $receiptStatus = $order['receipt_send_status'] ?? 'not_sent';
                            if ($receiptStatus === 'sent') {
                                echo '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 extra-small fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Sent</span>';
                            } elseif ($receiptStatus === 'failed') {
                                echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 extra-small fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Send Failed</span>';
                            } else {
                                echo '<span class="badge bg-secondary-subtle text-muted border px-2 py-1 extra-small"><i class="bi bi-clock me-1"></i>Not Sent</span>';
                            }
                            ?>
                        </div>

                        <div class="vstack gap-2 mb-3 extra-small text-muted">
                            <div class="d-flex justify-content-between">
                                <span>Receipt Status:</span>
                                <span class="text-dark fw-bold"><?= ucfirst(str_replace('_', ' ', $receiptStatus)); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Receipt Sent At:</span>
                                <strong class="text-dark"><?= !empty($order['receipt_sent_at']) ? date('M d, Y h:i A', strtotime($order['receipt_sent_at'])) : 'Never'; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Send Count:</span>
                                <strong class="text-dark"><?= (int)($order['receipt_send_count'] ?? 0); ?> time(s)</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Customer Email:</span>
                                <?php if (!empty($order['customer_email'])): ?>
                                    <strong class="text-dark text-truncate ms-2" style="max-width: 160px;" title="<?= htmlspecialchars($order['customer_email']); ?>"><?= htmlspecialchars($order['customer_email']); ?></strong>
                                <?php else: ?>
                                    <span class="text-danger extra-small fw-semibold">Customer email is not available.</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="vstack gap-2">
                            <?php if (!empty($order['customer_email'])): ?>
                                <form action="view.php?order=<?= urlencode($order['order_number']); ?>" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="send_receipt">
                                    <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                                    <button type="submit" class="btn btn-auth-primary btn-sm w-100 rounded-pill fw-semibold shadow-sm">
                                        <i class="bi bi-envelope-paper me-1"></i><?= ((int)($order['receipt_send_count'] ?? 0) > 0) ? 'Resend Receipt to Customer' : 'Send Receipt to Customer'; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary btn-sm w-100 rounded-pill fw-semibold opacity-75" disabled title="Customer has no registered email address">
                                    <i class="bi bi-envelope-x me-1"></i>Customer email is not available.
                                </button>
                            <?php endif; ?>

                            <a href="../../receipt.php?order=<?= urlencode($order['order_number']); ?>&print=1" target="_blank" class="btn btn-outline-dark btn-sm w-100 rounded-pill">
                                <i class="bi bi-printer me-1"></i>Print Official Receipt (A4)
                            </a>

                            <a href="../../receipt.php?order=<?= urlencode($order['order_number']); ?>&format=thermal&print=1" target="_blank" class="btn btn-outline-secondary btn-sm w-100 rounded-pill">
                                <i class="bi bi-receipt-cutoff me-1"></i>Print Thermal Slip (80mm)
                            </a>

                            <a href="../../receipt.php?order=<?= urlencode($order['order_number']); ?>" target="_blank" class="btn btn-light border btn-sm w-100 rounded-pill extra-small text-dark fw-semibold">
                                <i class="bi bi-receipt me-1 text-primary-accent"></i>Open Receipt
                            </a>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                        <h3 class="h6 fw-bold text-dark mb-3 pb-2 border-bottom">Customer Details</h3>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar-placeholder rounded-circle bg-pink-soft text-primary-accent fw-bold fs-5 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <?= strtoupper(substr($order['customer_name'] ?? 'C', 0, 1)); ?>
                            </div>
                            <div>
                                <h4 class="h6 fw-bold text-dark mb-0"><?= htmlspecialchars($order['customer_name'] ?? 'Customer'); ?></h4>
                                <span class="extra-small text-muted d-block"><?= htmlspecialchars($order['customer_email'] ?? ''); ?></span>
                                <span class="extra-small text-muted d-block"><?= htmlspecialchars($order['customer_phone'] ?? 'No phone'); ?></span>
                            </div>
                        </div>
                        <a href="../customers/view.php?id=<?= (int)$order['user_id']; ?>" class="btn btn-outline-secondary btn-sm w-100 rounded-pill">
                            <i class="bi bi-person-lines-fill me-1"></i>View Customer Profile
                        </a>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                        <h3 class="h6 fw-bold text-dark mb-3 pb-2 border-bottom">Delivery Address Snapshot</h3>
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle bg-primary-subtle text-primary p-2 flex-shrink-0">
                                <i class="bi bi-geo-alt-fill fs-5"></i>
                            </div>
                            <div>
                                <strong class="d-block text-dark small mb-1"><?= htmlspecialchars($order['customer_name'] ?? ''); ?></strong>
                                <p class="small text-muted mb-0"><?= htmlspecialchars($order['delivery_address']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <h3 class="h6 fw-bold text-dark mb-0">Payment Details</h3>
                            <?= getPaymentStatusBadge($order['payment_status']); ?>
                        </div>

                        <div class="vstack gap-2 mb-3">
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Method</span>
                                <strong class="text-dark"><?= htmlspecialchars(getPaymentMethodLabel($order['payment_method'])); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Amount</span>
                                <strong class="text-dark">$<?= number_format((float)$order['total'], 2); ?></strong>
                            </div>
                        </div>

                        <?php if ($order['payment_status'] !== 'paid' && $currSt !== 'cancelled'): ?>
                            <form action="view.php?order=<?= urlencode($order['order_number']); ?>" method="POST" onsubmit="return confirm('Mark this order payment as PAID?');">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="mark_payment_paid">
                                <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                                <button type="submit" class="btn btn-success btn-sm w-100 rounded-pill fw-semibold">
                                    <i class="bi bi-check-circle me-1"></i>Mark Payment as PAID
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Print-Only Dedicated Official Receipt Layout -->
            <div class="admin-print-receipt">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h2 style="margin: 0; font-size: 20pt; font-weight: bold; letter-spacing: 1px;">FASTBITE</h2>
                    <p style="margin: 2px 0; font-size: 9pt; font-style: italic;">Good Food. Fast Delivery. Happy Customers.</p>
                    <p style="margin: 2px 0; font-size: 9pt;"><?= htmlspecialchars($restName); ?> &bull; <?= htmlspecialchars($restAddress); ?></p>
                    <p style="margin: 2px 0; font-size: 9pt;">Tel: <?= htmlspecialchars($restPhone); ?> | Email: <?= htmlspecialchars($restEmail); ?></p>
                </div>

                <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; font-size: 10pt;">
                        <div>
                            <strong>ORDER RECEIPT: #<?= htmlspecialchars($order['order_number']); ?></strong><br>
                            Date: <?= date('F j, Y  h:i A', strtotime($order['created_at'])); ?><br>
                            Customer: <?= htmlspecialchars($order['customer_name'] ?? 'Customer'); ?><br>
                            Phone: <?= htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?>
                        </div>
                        <div style="text-align: right;">
                            Payment: <?= htmlspecialchars(getPaymentMethodLabel($order['payment_method'])); ?><br>
                            Payment Status: <strong><?= strtoupper($order['payment_status']); ?></strong><br>
                            Order Status: <strong><?= strtoupper(str_replace('_', ' ', $order['order_status'])); ?></strong>
                        </div>
                    </div>
                    <div style="margin-top: 8px; font-size: 9pt;">
                        <strong>Delivery Address:</strong> <?= htmlspecialchars($order['delivery_address']); ?>
                    </div>
                </div>

                <table class="table" style="width: 100%; font-size: 10pt; margin-bottom: 15px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #000;">
                            <th style="text-align: left;">Item</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Unit Price</th>
                            <th style="text-align: right;">Discount</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $it): ?>
                            <tr>
                                <td><?= htmlspecialchars($it['food_name']); ?></td>
                                <td style="text-align: center;"><?= (int)$it['quantity']; ?></td>
                                <td style="text-align: right;">$<?= number_format((float)$it['unit_price'], 2); ?></td>
                                <td style="text-align: right;"><?= (float)$it['discount'] > 0 ? '-$' . number_format((float)$it['discount'], 2) : '$0.00'; ?></td>
                                <td style="text-align: right; font-weight: bold;">$<?= number_format((float)$it['subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="border-top: 1px dashed #000; padding-top: 10px; margin-bottom: 15px;">
                    <table style="width: 50%; margin-left: auto; font-size: 10pt;">
                        <tr>
                            <td>Subtotal:</td>
                            <td style="text-align: right; font-weight: bold;">$<?= number_format((float)$order['subtotal'], 2); ?></td>
                        </tr>
                        <?php if ((float)$order['discount'] > 0): ?>
                            <tr>
                                <td>Item Discounts:</td>
                                <td style="text-align: right;">-$<?= number_format((float)$order['discount'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (isset($order['coupon_discount']) && (float)$order['coupon_discount'] > 0): ?>
                            <tr>
                                <td>Coupon (<?= htmlspecialchars($order['coupon_code'] ?? 'PROMO'); ?>):</td>
                                <td style="text-align: right;">-$<?= number_format((float)$order['coupon_discount'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td>Delivery Fee:</td>
                            <td style="text-align: right;"><?= (float)$order['delivery_fee'] > 0 ? '$' . number_format((float)$order['delivery_fee'], 2) : 'FREE'; ?></td>
                        </tr>
                        <?php if (isset($order['tax']) && (float)$order['tax'] > 0): ?>
                            <tr>
                                <td>Tax:</td>
                                <td style="text-align: right;">$<?= number_format((float)$order['tax'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr style="font-size: 12pt; font-weight: bold; border-top: 1px solid #000;">
                            <td>TOTAL DUE:</td>
                            <td style="text-align: right;">$<?= number_format((float)$order['total'], 2); ?></td>
                        </tr>
                    </table>
                </div>

                <?php if (!empty($order['notes'])): ?>
                    <div style="font-size: 9pt; margin-bottom: 15px;">
                        <strong>Special Customer Instructions:</strong> <?= htmlspecialchars($order['notes']); ?>
                    </div>
                <?php endif; ?>

                <div style="text-align: center; font-size: 9pt; border-top: 1px dashed #000; padding-top: 10px;">
                    <p style="margin: 0;">Thank you for ordering from FastBite!</p>
                </div>
            </div>

            <!-- Cancellation Modal -->
            <div class="modal fade d-print-none" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <form action="view.php?order=<?= urlencode($order['order_number']); ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                            <input type="hidden" name="new_status" value="cancelled">
                            
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold text-danger" id="cancelOrderModalLabel">Cancel Order #<?= htmlspecialchars($order['order_number']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body py-3">
                                <p class="small text-muted mb-3">Are you sure you want to cancel this order? An automated cancellation notification will be sent to the customer.</p>
                                <div class="mb-3">
                                    <label for="adminCancelReason" class="form-label small fw-bold text-dark">Cancellation Reason (Optional)</label>
                                    <input type="text" class="form-control form-control-sm" name="reason" id="adminCancelReason" placeholder="e.g. Item unavailable, customer request">
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 fw-semibold">Confirm Cancellation</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</main>

<div class="d-print-none">
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div>
