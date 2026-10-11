<?php
/**
 * FastBite - Shopping Cart Page
 * STEP 30: FastBite Customer Experience, Reviews, Favorites & Promotions
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';

$currentPage = 'cart';
$projectName = 'FastBite';
$user = currentUser();
$userId = $user ? (int)$user['id'] : null;

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch Cart Items
$cartItems = $userId ? dbGetCartItems($userId) : [];

// Handle POST Cart Actions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    $foodId = (int)($_POST['food_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 1);

    if ($userId) {
        if ($action === 'add' && $foodId > 0) {
            $added = dbAddToCart($userId, $foodId, $qty);
            if ($added) {
                header("Location: cart.php?msg=" . urlencode("Item added to your cart."));
                exit;
            } else {
                header("Location: cart.php?error=" . urlencode("Item is currently sold out or unavailable."));
                exit;
            }
        } elseif ($action === 'update' && $foodId > 0) {
            dbUpdateCartQuantity($userId, $foodId, $qty);
            header("Location: cart.php");
            exit;
        } elseif ($action === 'remove' && $foodId > 0) {
            dbRemoveFromCart($userId, $foodId);
            header("Location: cart.php?msg=" . urlencode("Item removed from cart."));
            exit;
        } elseif ($action === 'clear') {
            dbClearCart($userId);
            unset($_SESSION['applied_coupon']);
            header("Location: cart.php?msg=" . urlencode("Cart cleared."));
            exit;
        } elseif ($action === 'apply_coupon') {
            $csrf = $_POST['csrf_token'] ?? '';
            if (!verifyCsrfToken($csrf)) {
                header("Location: cart.php?error=" . urlencode("Security validation failed. Please try again."));
                exit;
            }
            $couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));
            if (empty($couponCode)) {
                header("Location: cart.php?error=" . urlencode("Please enter a coupon code."));
                exit;
            }
            $tempTotals = dbCalculateCartTotals($cartItems);
            $validation = dbValidateCoupon($couponCode, $tempTotals['subtotal'], $userId);
            if ($validation['valid']) {
                $_SESSION['applied_coupon'] = [
                    'id' => $validation['coupon']['id'],
                    'code' => $validation['coupon']['code'],
                    'discount' => $validation['discount'],
                    'description' => $validation['coupon']['description'] ?? ''
                ];
                header("Location: cart.php?msg=" . urlencode("Promo coupon '{$validation['coupon']['code']}' applied successfully!"));
                exit;
            } else {
                header("Location: cart.php?error=" . urlencode($validation['error']));
                exit;
            }
        } elseif ($action === 'remove_coupon') {
            $csrf = $_POST['csrf_token'] ?? '';
            if (verifyCsrfToken($csrf)) {
                unset($_SESSION['applied_coupon']);
                header("Location: cart.php?msg=" . urlencode("Coupon removed."));
                exit;
            }
        }
    } else {
        header("Location: login.php?return_url=" . urlencode('cart.php') . "&msg=" . urlencode("Please log in to manage your cart and proceed to checkout."));
        exit;
    }
}

// Check / re-validate applied coupon from session
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

// Calculate cart totals with coupon
$cartTotals = dbCalculateCartTotals($cartItems, 2.00, $appliedCoupon);
$initialSubtotal = $cartTotals['subtotal'];
$totalSavings = $cartTotals['savings'];
$couponDiscount = $cartTotals['coupon_discount'];
$couponCode = $cartTotals['coupon_code'];
$deliveryFee = $cartTotals['delivery_fee'];
$initialTotal = $cartTotals['total'];
$hasSoldOutItems = false;

foreach ($cartItems as $ci) {
    if (!$ci['is_available']) {
        $hasSoldOutItems = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?> - Fast &amp; Delicious</title>
    <meta name="description" content="Review your selected meals before placing your order on FastBite. Fresh fast food prepared to order.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main id="main-content" class="py-4 py-lg-5">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item"><a href="menu.php" class="text-decoration-none">Menu</a></li>
                    <li class="breadcrumb-item active text-primary-accent" aria-current="page">Shopping Cart</li>
                </ol>
            </nav>

            <?php if (!empty($msg)): ?>
                <div class="alert alert-success py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <div><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($hasSoldOutItems): ?>
                <div class="alert alert-warning py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-circle-fill text-warning fs-5"></i>
                    <div>One or more items in your cart are currently <strong>SOLD OUT</strong>. Please remove them to proceed to checkout.</div>
                </div>
            <?php endif; ?>

            <div class="cart-header mb-4 pb-2 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                <div>
                    <h1 class="h2 fw-bold text-dark mb-1">Your Shopping Cart</h1>
                    <p class="text-muted mb-0">Review your selected dishes, apply discount coupons, and proceed to checkout.</p>
                </div>
                <?php if (!empty($cartItems)): ?>
                    <form method="POST" action="cart.php" class="d-inline">
            <?= csrfField(); ?>
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" onclick="return confirm('Clear all items from your cart?');">
                            <i class="bi bi-trash3"></i>
                            <span>Clear Cart</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (empty($cartItems)): ?>
                <div id="emptyCartState" class="text-center py-5 bg-white rounded-4 shadow-sm border p-4">
                    <div class="empty-cart-icon-wrap mb-3">
                        <i class="bi bi-bag-x text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="h4 fw-bold text-dark mb-2">Your Cart is Currently Empty</h2>
                    <p class="text-muted mb-4">Looks like you haven't added any delicious food yet.</p>
                    <a href="menu.php" class="btn btn-hero-primary d-inline-flex align-items-center gap-2">
                        <span>Explore Delicious Menu</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4" id="cartContentGrid">
                    <div class="col-12 col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white">
                            <div class="table-responsive">
                                <table class="table align-middle cart-table mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="border-0">Food Item</th>
                                            <th scope="col" class="border-0 text-center">Price</th>
                                            <th scope="col" class="border-0 text-center">Quantity</th>
                                            <th scope="col" class="border-0 text-end">Subtotal</th>
                                            <th scope="col" class="border-0 text-center" style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cartTableBody">
                                        <?php foreach ($cartItems as $item): 
                                            $foodImg = function_exists('getFoodImage') ? getFoodImage($item['image']) : 'assets/images/placeholders/food-placeholder.jpg';
                                        ?>
                                            <tr class="cart-item-row" id="cartRow-<?= $item['id']; ?>">
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="<?= htmlspecialchars($foodImg, ENT_QUOTES, 'UTF-8'); ?>" 
                                                             alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                             class="cart-item-img rounded-3" 
                                                             style="width: 65px; height: 65px; object-fit: cover;">
                                                        <div>
                                                            <h2 class="h6 fw-bold text-dark mb-1"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small">
                                                                    <?= htmlspecialchars($item['category_name'] ?? 'Food', ENT_QUOTES, 'UTF-8'); ?>
                                                                </span>
                                                                <?php if (!$item['is_available']): ?>
                                                                    <span class="badge bg-danger extra-small">SOLD OUT</span>
                                                                <?php elseif ($item['has_discount']): ?>
                                                                    <span class="badge bg-success extra-small">-<?= $item['discount_percent']; ?>% Off</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="fw-bold text-dark">$<?= htmlspecialchars(number_format($item['final_price'], 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php if ($item['has_discount']): ?>
                                                        <span class="d-block extra-small text-muted text-decoration-line-through">$<?= htmlspecialchars(number_format($item['original_price'], 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <form method="POST" action="cart.php" class="d-inline-flex align-items-center gap-1">
            <?= csrfField(); ?>
                                                        <input type="hidden" name="action" value="update">
                                                        <input type="hidden" name="food_id" value="<?= $item['id']; ?>">
                                                        <button type="submit" name="quantity" value="<?= max(0, $item['quantity'] - 1); ?>" class="btn btn-sm btn-outline-secondary px-2 py-1" aria-label="Decrease quantity">
                                                            <i class="bi bi-dash"></i>
                                                        </button>
                                                        <span class="px-2 fw-bold text-dark"><?= $item['quantity']; ?></span>
                                                        <button type="submit" name="quantity" value="<?= $item['quantity'] + 1; ?>" class="btn btn-sm btn-outline-secondary px-2 py-1" aria-label="Increase quantity">
                                                            <i class="bi bi-plus"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold text-primary-accent">$<?= htmlspecialchars(number_format($item['item_subtotal'], 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <form method="POST" action="cart.php" class="d-inline">
            <?= csrfField(); ?>
                                                        <input type="hidden" name="action" value="remove">
                                                        <input type="hidden" name="food_id" value="<?= $item['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Remove item from cart">
                                                            <i class="bi bi-trash fs-5"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                <a href="menu.php" class="btn btn-outline-pink d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-arrow-left"></i>
                                    <span>Continue Shopping</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-lg-top" style="top: 90px;">
                            <h2 class="h5 fw-bold text-dark mb-3 pb-2 border-bottom">Order Summary</h2>

                            <!-- Promo Coupon Box -->
                            <div class="mb-3 pb-3 border-bottom">
                                <label class="form-label small fw-bold text-dark d-flex align-items-center gap-1 mb-2">
                                    <i class="bi bi-ticket-perforated text-primary-accent"></i>
                                    <span>Promo Code / Coupon</span>
                                </label>
                                <?php if ($appliedCoupon): ?>
                                    <div class="p-2 rounded-3 bg-pink-soft border border-pink-subtle d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2 text-truncate">
                                            <span class="badge bg-primary-accent font-monospace fs-6 px-2"><?= htmlspecialchars($appliedCoupon['code']); ?></span>
                                            <span class="text-success small fw-semibold text-truncate">Saved $<?= number_format($couponDiscount, 2); ?></span>
                                        </div>
                                        <form method="POST" action="cart.php" class="d-inline ms-2">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                            <input type="hidden" name="action" value="remove_coupon">
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Remove promo code">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <form method="POST" action="cart.php" class="d-flex gap-2">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="apply_coupon">
                                        <input type="text" 
                                               name="coupon_code" 
                                               class="form-control form-control-sm text-uppercase" 
                                               placeholder="Enter FAST10 / YUMMY5" 
                                               required>
                                        <button type="submit" class="btn btn-sm btn-auth-primary text-nowrap px-3">Apply</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex flex-column gap-2 mb-3">
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Items (<?= $cartTotals['item_count']; ?>)</span>
                                    <span class="fw-semibold text-dark">$<?= htmlspecialchars(number_format($initialSubtotal, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Delivery Fee</span>
                                    <span class="fw-semibold text-dark">$<?= htmlspecialchars(number_format($deliveryFee, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <?php if ($totalSavings > 0): ?>
                                    <div class="d-flex justify-content-between text-success small">
                                        <span>Menu Discounts</span>
                                        <span class="fw-semibold">-$<?= htmlspecialchars(number_format($totalSavings, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($couponDiscount > 0): ?>
                                    <div class="d-flex justify-content-between text-success small">
                                        <span>Promo Coupon (<?= htmlspecialchars($couponCode); ?>)</span>
                                        <span class="fw-semibold">-$<?= htmlspecialchars(number_format($couponDiscount, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                    <span class="fw-bold text-dark fs-5">Total</span>
                                    <span class="fw-bold text-primary-accent fs-4" id="cartGrandTotal">$<?= htmlspecialchars(number_format($initialTotal, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>

                            <?php if ($hasSoldOutItems): ?>
                                <button type="button" class="btn btn-auth-primary w-100 py-2 d-inline-flex align-items-center justify-content-center gap-2 disabled" disabled>
                                    <span>Cannot Proceed (Sold Out Items)</span>
                                </button>
                            <?php else: ?>
                                <a href="checkout.php" class="btn btn-auth-primary w-100 py-2 d-inline-flex align-items-center justify-content-center gap-2 shadow-sm" id="btnProceedCheckout">
                                    <span>Proceed to Checkout</span>
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            <?php endif; ?>

                            <div class="p-3 bg-light rounded-3 border mt-3 extra-small text-muted text-center">
                                <i class="bi bi-shield-check text-success me-1"></i>
                                <span>Safe and encrypted fast food checkout.</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
