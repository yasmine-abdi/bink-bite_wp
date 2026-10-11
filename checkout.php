<?php
/**
 * FastBite - Secure Checkout Page
 * STEP 26: Payment System & Checkout Finalization
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';
require_once __DIR__ . '/includes/payment.php';

requireLogin('login.php');

$user = currentUser();
if (!$user) {
    header("Location: login.php");
    exit;
}

$currentPage = 'checkout';
$projectName = 'FastBite';
$userId = (int)$user['id'];

// Fetch Cart Items from DB
$cartItems = dbGetCartItems($userId);

// If cart is empty, redirect to cart page
if (empty($cartItems)) {
    header("Location: cart.php?error=" . urlencode("Your cart is empty. Please select food items before checkout."));
    exit;
}

// Calculate applied coupon from session (Step 30)
$appliedCoupon = $_SESSION['applied_coupon'] ?? null;
if ($appliedCoupon && !empty($cartItems)) {
    $tempTotals = dbCalculateCartTotals($cartItems);
    $checkVal = dbValidateCoupon($appliedCoupon['code'], $tempTotals['subtotal'], $userId);
    if ($checkVal['valid']) {
        $appliedCoupon['discount'] = $checkVal['discount'];
        $_SESSION['applied_coupon'] = $appliedCoupon;
    } else {
        unset($_SESSION['applied_coupon']);
        $appliedCoupon = null;
    }
}

// Calculate server-side totals using authoritative restaurant settings
$cartTotals = dbCalculateCartTotals($cartItems, null, $appliedCoupon);
$subtotal = $cartTotals['subtotal'];
$totalSavings = $cartTotals['savings'];
$couponDiscount = $cartTotals['coupon_discount'];
$couponCode = $cartTotals['coupon_code'];
$deliveryFee = $cartTotals['delivery_fee'];
$tax = $cartTotals['tax'];
$taxPercent = $cartTotals['tax_percent'];
$minOrderAmount = $cartTotals['min_order_amount'];
$isMinMet = $cartTotals['is_min_met'];
$freeThreshold = $cartTotals['free_delivery_threshold'];
$total = $cartTotals['total'];

// Restaurant status check
$webStatus = dbGetSetting('website_status', 'online');
$maintenance = dbGetSetting('maintenance_mode', '0');
$isStoreClosed = ($webStatus === 'closed' || $maintenance === '1');

// Fetch user's saved addresses
$savedAddresses = dbGetUserAddresses($userId);

// Generate / retrieve idempotency checkout token
if (empty($_SESSION['checkout_idempotency_token'])) {
    $_SESSION['checkout_idempotency_token'] = bin2hex(random_bytes(16));
}
$checkoutToken = $_SESSION['checkout_idempotency_token'];

$error = '';

// Handle POST Place Order
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'place_order') {
    // 1. CSRF Verification
    $postedCsrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($postedCsrf)) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    }

    // 2. Idempotency Verification
    $postedToken = $_POST['checkout_token'] ?? '';
    if (empty($error) && $postedToken !== $checkoutToken) {
        $error = 'This checkout attempt has already been processed or expired. Please review your orders.';
    }

    // 3. Store Status Check
    if (empty($error) && $isStoreClosed) {
        $error = 'We are temporarily not accepting online orders. Please check back later.';
    }

    // 4. Minimum Order Amount Check
    if (empty($error) && !$isMinMet && $minOrderAmount > 0) {
        $error = 'Minimum order amount is $' . number_format($minOrderAmount, 2) . '. Please add more items to your cart.';
    }

    // 5. Payment Method Validation
    $paymentMethod = trim($_POST['paymentMethod'] ?? 'cash_on_delivery');
    if (empty($error)) {
        if (!isPaymentMethodAllowed($paymentMethod)) {
            $error = 'The selected payment method is currently unavailable. Please choose Cash on Delivery.';
        }
    }

    // 6. Address Validation
    $selectedAddrType = $_POST['addressSelection'] ?? 'saved';
    $deliveryAddress = '';

    if (empty($error)) {
        if ($selectedAddrType === 'saved') {
            $savedId = (int)($_POST['savedAddressId'] ?? 0);
            $addrRow = dbGetAddressById($savedId, $userId);
            if ($addrRow) {
                $deliveryAddress = "{$addrRow['street_address']}, {$addrRow['area']}, {$addrRow['city']}";
            } else {
                if (!empty($savedAddresses)) {
                    $deliveryAddress = "{$savedAddresses[0]['street_address']}, {$savedAddresses[0]['area']}, {$savedAddresses[0]['city']}";
                } else {
                    $deliveryAddress = "City Center, Hargeisa";
                }
            }
        } else {
            $street = trim($_POST['newStreet'] ?? '');
            $area = trim($_POST['newArea'] ?? '');
            $city = trim($_POST['newCity'] ?? 'Hargeisa');
            if (empty($street) || empty($area)) {
                $error = 'Please provide complete delivery street and area details.';
            } else {
                $deliveryAddress = "{$street}, {$area}, {$city}";
                if (!empty($_POST['saveNewAddress'])) {
                    dbCreateAddress($userId, [
                        'full_name' => $user['name'],
                        'phone' => $user['phone'] ?? '+252 63 000 0000',
                        'address_type' => 'Home',
                        'city' => $city,
                        'area' => $area,
                        'street_address' => $street,
                        'is_default' => 0
                    ]);
                }
            }
        }
    }

    $orderNotes = trim($_POST['orderNotes'] ?? '');

    if (empty($error)) {
        $orderData = [
            'delivery_address' => $deliveryAddress,
            'payment_method' => $paymentMethod,
            'notes' => $orderNotes,
            'coupon_code' => $couponCode
        ];

        $orderNumber = dbCreateOrderTransaction($userId, $orderData, $cartItems);

        if ($orderNumber) {
            unset($_SESSION['checkout_idempotency_token']);
            header("Location: confirmation.php?order=" . urlencode($orderNumber));
            exit;
        } else {
            $error = 'An error occurred while processing your order. Please check food availability and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — <?= htmlspecialchars($projectName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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
            
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item"><a href="cart.php" class="text-decoration-none text-muted">Cart</a></li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Checkout</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm rounded-4 mb-4 mb-lg-5">
                <div class="card-body p-3 p-md-4">
                    <div class="row text-center position-relative g-0">
                        <div class="col-4 position-relative">
                            <div class="d-flex flex-column align-items-center">
                                <a href="cart.php" class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center mb-2 text-decoration-none shadow-sm" style="width: 44px; height: 44px;">
                                    <i class="bi bi-check-lg fs-5"></i>
                                </a>
                                <span class="small fw-semibold text-dark">1. Review Cart</span>
                            </div>
                        </div>
                        <div class="col-4 position-relative">
                            <div class="d-flex flex-column align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mb-2 shadow-sm" style="width: 44px; height: 44px;">
                                    <span class="fs-6 fw-bold">2</span>
                                </div>
                                <span class="small fw-bold text-primary">2. Delivery & Payment</span>
                            </div>
                        </div>
                        <div class="col-4 position-relative">
                            <div class="d-flex flex-column align-items-center opacity-50">
                                <div class="rounded-circle bg-light border text-muted d-flex align-items-center justify-content-center mb-2" style="width: 44px; height: 44px;">
                                    <span class="fs-6">3</span>
                                </div>
                                <span class="small fw-medium text-muted">3. Confirmation</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($isStoreClosed): ?>
                <div class="alert alert-warning border-0 shadow-sm rounded-4 p-4 mb-4 text-center">
                    <i class="bi bi-exclamation-triangle-fill fs-2 text-warning mb-2 d-block"></i>
                    <h5 class="fw-bold text-dark mb-1">Restaurant Temporarily Offline</h5>
                    <p class="text-muted small mb-0">We are currently not accepting online orders due to store hours or maintenance. Please browse our menu and check back soon.</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-4 p-3 mb-4 d-flex align-items-center">
                    <i class="bi bi-exclamation-octagon-fill fs-4 me-3 text-danger flex-shrink-0"></i>
                    <div>
                        <strong class="d-block text-danger">Checkout Notice:</strong>
                        <span class="small text-muted"><?= htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-4 g-lg-5">
                
                <div class="col-lg-7 col-xl-8">
                    <form action="checkout.php" method="POST" id="checkoutForm">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="place_order">
                        <input type="hidden" name="checkout_token" value="<?= htmlspecialchars($checkoutToken); ?>">

                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-3 bg-primary-subtle text-primary p-2 me-3">
                                        <i class="bi bi-person-badge fs-5"></i>
                                    </div>
                                    <h5 class="card-title fw-bold text-dark mb-0">1. Customer Contact Details</h5>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted">Full Name</label>
                                        <input type="text" class="form-control bg-light border-0" value="<?= htmlspecialchars($user['name']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted">Email Address</label>
                                        <input type="email" class="form-control bg-light border-0" value="<?= htmlspecialchars($user['email']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted">Phone Number</label>
                                        <input type="text" class="form-control" name="customerPhone" value="<?= htmlspecialchars($user['phone'] ?? '+252 63 4567890'); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-3 bg-primary-subtle text-primary p-2 me-3">
                                        <i class="bi bi-geo-alt-fill fs-5"></i>
                                    </div>
                                    <h5 class="card-title fw-bold text-dark mb-0">2. Delivery Address</h5>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <?php if (!empty($savedAddresses)): ?>
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold text-dark small mb-3">Choose Delivery Address</label>
                                        <div class="row g-3">
                                            <?php foreach ($savedAddresses as $idx => $addr): ?>
                                                <div class="col-md-6">
                                                    <label class="card h-100 border-2 p-3 rounded-4 cursor-pointer <?= $idx === 0 ? 'border-primary bg-primary-subtle bg-opacity-10' : 'border-light-subtle'; ?>" style="cursor: pointer;">
                                                        <div class="d-flex align-items-start">
                                                            <input class="form-check-input me-3 mt-1" type="radio" name="addressSelection" value="saved" <?= $idx === 0 ? 'checked' : ''; ?> onchange="toggleAddressFields(false)">
                                                            <div>
                                                                <span class="badge bg-primary-subtle text-primary extra-small mb-1"><?= htmlspecialchars($addr['address_type'] ?? 'Home'); ?></span>
                                                                <p class="fw-bold text-dark mb-1 small"><?= htmlspecialchars($addr['street_address']); ?></p>
                                                                <p class="text-muted extra-small mb-0"><?= htmlspecialchars($addr['area'] . ', ' . $addr['city']); ?></p>
                                                                <input type="hidden" name="savedAddressId" value="<?= (int)$addr['id']; ?>">
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="addressSelection" id="useNewAddressRadio" value="new" onchange="toggleAddressFields(true)">
                                        <label class="form-check-label fw-semibold text-dark small" for="useNewAddressRadio">
                                            Deliver to a different address
                                        </label>
                                    </div>
                                <?php endif; ?>

                                <div id="newAddressFields" class="<?= !empty($savedAddresses) ? 'd-none' : ''; ?>">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold text-muted">Street Address / Landmark <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="newStreet" placeholder="e.g. 26 June Street, Near Telecom Tower">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold text-muted">District / Area <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="newArea" placeholder="e.g. Koodbuur District">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold text-muted">City</label>
                                            <input type="text" class="form-control" name="newCity" value="Hargeisa">
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="saveNewAddress" value="1" id="saveNewAddrCheck" checked>
                                                <label class="form-check-label small text-muted" for="saveNewAddrCheck">
                                                    Save this address to my profile for future orders
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-3 bg-primary-subtle text-primary p-2 me-3">
                                        <i class="bi bi-credit-card-2-front fs-5"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title fw-bold text-dark mb-0">3. Select Payment Method</h5>
                                        <p class="text-muted extra-small mb-0">Select your preferred payment method</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="vstack gap-3">
                                    
                                    <label class="card border-2 border-primary bg-primary-subtle bg-opacity-10 p-3 rounded-4 cursor-pointer" style="cursor: pointer;">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <input class="form-check-input me-3" type="radio" name="paymentMethod" id="payCod" value="cash_on_delivery" checked>
                                                <div class="rounded-3 bg-white p-2 text-primary me-3 shadow-sm border">
                                                    <i class="bi bi-cash-coin fs-4"></i>
                                                </div>
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="fw-bold text-dark small">Cash on Delivery</span>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle extra-small">Available</span>
                                                    </div>
                                                    <p class="text-muted extra-small mb-0">Pay with cash or local money upon food arrival at your doorstep.</p>
                                                </div>
                                            </div>
                                            <i class="bi bi-check-circle-fill text-primary fs-5"></i>
                                        </div>
                                    </label>

                                    <label class="card border-1 border-light-subtle bg-light p-3 rounded-4 opacity-75">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <input class="form-check-input me-3" type="radio" name="paymentMethod" id="payMobile" value="mobile_money" disabled>
                                                <div class="rounded-3 bg-white p-2 text-secondary me-3 border">
                                                    <i class="bi bi-phone fs-4"></i>
                                                </div>
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="fw-bold text-secondary small">Mobile Money (EVC Plus / Zaad / Sahal)</span>
                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle extra-small">Coming Soon</span>
                                                    </div>
                                                    <p class="text-muted extra-small mb-0">Direct automated mobile money payment gateway is under integration.</p>
                                                </div>
                                            </div>
                                            <i class="bi bi-lock-fill text-muted fs-5"></i>
                                        </div>
                                    </label>

                                    <label class="card border-1 border-light-subtle bg-light p-3 rounded-4 opacity-75">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <input class="form-check-input me-3" type="radio" name="paymentMethod" id="payCard" value="card" disabled>
                                                <div class="rounded-3 bg-white p-2 text-secondary me-3 border">
                                                    <i class="bi bi-credit-card fs-4"></i>
                                                </div>
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="fw-bold text-secondary small">Credit / Debit Card (Visa / Mastercard)</span>
                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle extra-small">Coming Soon</span>
                                                    </div>
                                                    <p class="text-muted extra-small mb-0">Secure tokenized online card checkout gateway is currently not configured.</p>
                                                </div>
                                            </div>
                                            <i class="bi bi-lock-fill text-muted fs-5"></i>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-3 bg-primary-subtle text-primary p-2 me-3">
                                        <i class="bi bi-chat-left-text fs-5"></i>
                                    </div>
                                    <h5 class="card-title fw-bold text-dark mb-0">4. Special Instructions (Optional)</h5>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <textarea class="form-control" name="orderNotes" rows="3" placeholder="e.g. Please ring the bell or leave near the front gate. Extra napkins appreciated!"></textarea>
                            </div>
                        </div>

                        <div class="d-grid mb-4">
                            <?php if ($isStoreClosed): ?>
                                <button type="button" class="btn btn-secondary btn-lg rounded-pill fw-bold py-3" disabled>
                                    <i class="bi bi-slash-circle me-2"></i>Store Currently Offline
                                </button>
                            <?php elseif (!$isMinMet && $minOrderAmount > 0): ?>
                                <button type="button" class="btn btn-warning btn-lg rounded-pill fw-bold py-3 text-dark" disabled>
                                    <i class="bi bi-exclamation-circle me-2"></i>Minimum Order is $<?= number_format($minOrderAmount, 2); ?> (Add $<?= number_format($minOrderAmount - $subtotal, 2); ?> more)
                                </button>
                            <?php else: ?>
                                <button type="submit" id="submitOrderBtn" class="btn btn-primary btn-lg rounded-pill fw-bold py-3 shadow">
                                    <i class="bi bi-bag-check-fill me-2"></i>Place Order ($<?= number_format($total, 2); ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="col-lg-5 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 100px;">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="fw-bold text-dark mb-0">Order Summary</h5>
                                <span class="badge bg-primary-subtle text-primary extra-small"><?= count($cartItems); ?> Items</span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            
                            <div class="overflow-auto mb-3" style="max-height: 240px;">
                                <div class="vstack gap-3">
                                    <?php foreach ($cartItems as $item): ?>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= htmlspecialchars(getFoodImage($item['image'] ?? '')); ?>" alt="<?= htmlspecialchars($item['name']); ?>" class="rounded-3 object-fit-cover me-3 flex-shrink-0" style="width: 50px; height: 50px;" onerror="handleImageError(this, 'food')">
                                            <div class="flex-grow-1 min-w-0">
                                                <h6 class="fw-semibold text-dark text-truncate small mb-0"><?= htmlspecialchars($item['name']); ?></h6>
                                                <span class="text-muted extra-small">Qty: <?= (int)$item['quantity']; ?> &times; $<?= number_format((float)$item['final_price'], 2); ?></span>
                                            </div>
                                            <span class="fw-bold text-dark small ms-2">$<?= number_format((float)$item['final_price'] * (int)$item['quantity'], 2); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <hr class="text-muted opacity-25 my-3">

                            <div class="vstack gap-2">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>Food Subtotal</span>
                                    <span class="fw-semibold text-dark">$<?= number_format($subtotal, 2); ?></span>
                                </div>

                                <?php if ($totalSavings > 0): ?>
                                    <div class="d-flex justify-content-between small text-success">
                                        <span>Menu Item Discounts</span>
                                        <span class="fw-semibold">-$<?= number_format($totalSavings, 2); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($couponDiscount) && $couponDiscount > 0): ?>
                                    <div class="d-flex justify-content-between small text-primary fw-bold">
                                        <span>Promo Coupon (<?= htmlspecialchars($couponCode ?? ''); ?>)</span>
                                        <span class="fw-semibold">-$<?= number_format($couponDiscount, 2); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between small text-muted">
                                    <div class="d-flex align-items-center">
                                        <span>Delivery Fee</span>
                                        <?php if ($freeThreshold > 0 && $subtotal >= $freeThreshold): ?>
                                            <span class="badge bg-success-subtle text-success extra-small ms-1">Free Delivery</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="fw-semibold text-dark">
                                        <?= $deliveryFee > 0 ? '$' . number_format($deliveryFee, 2) : '<span class="text-success fw-bold">FREE</span>'; ?>
                                    </span>
                                </div>

                                <?php if ($taxPercent > 0): ?>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>Estimated Tax (<?= number_format($taxPercent, 1); ?>%)</span>
                                        <span class="fw-semibold text-dark">$<?= number_format($tax, 2); ?></span>
                                    </div>
                                <?php endif; ?>

                                <hr class="text-muted opacity-25 my-2">

                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark">Total Due</span>
                                    <span class="fs-4 fw-extrabold text-primary">$<?= number_format($total, 2); ?></span>
                                </div>
                            </div>

                            <div class="card bg-light border-0 rounded-3 p-3 mt-4">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-shield-lock-fill text-success fs-3"></i>
                                    <div>
                                        <p class="fw-bold text-dark extra-small mb-0">Safe & Authentic Checkout</p>
                                        <p class="text-muted extra-small mb-0">Verified prices, strict CSRF protection & transactional integrity.</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function toggleAddressFields(showNew) {
            const container = document.getElementById('newAddressFields');
            if (container) {
                if (showNew) {
                    container.classList.remove('d-none');
                } else {
                    container.classList.add('d-none');
                }
            }
        }

        const form = document.getElementById('checkoutForm');
        const submitBtn = document.getElementById('submitOrderBtn');
        if (form && submitBtn) {
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing Order...';
            });
        }
    </script>
</body>
</html>
