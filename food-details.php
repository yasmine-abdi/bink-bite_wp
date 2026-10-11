<?php
/**
 * FastBite - Food Details & Customer Reviews Page
 * STEP 30: FastBite Customer Experience, Reviews, Favorites & Promotions
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/image-helper.php';

$currentPage = 'menu';
$projectName = 'FastBite';
$user = currentUser();
$userId = $user ? (int)$user['id'] : null;

$requestedParam = isset($_GET['food']) ? trim($_GET['food']) : (isset($_GET['id']) ? trim($_GET['id']) : 'chicken-supreme-pizza');

// Attempt to load from database
$dbFood = is_numeric($requestedParam) ? dbGetFoodById((int)$requestedParam) : dbGetFoodBySlug($requestedParam);

if (!$dbFood) {
    $allFoods = dbGetAllFoodsWithPricing([]);
    $dbFood = $allFoods[0] ?? null;
}

if ($dbFood) {
    $foodId = (int)$dbFood['id'];
    $foodName = $dbFood['name'];
    $foodSlug = $dbFood['slug'];
    $categoryName = $dbFood['category_name'];
    $categorySlug = $dbFood['category_slug'];
    $foodDescription = $dbFood['description'] ?: 'Freshly prepared with authentic ingredients, herbs, and delicious signature seasonings.';
    $originalPrice = (float)$dbFood['original_price'];
    $finalPrice = (float)$dbFood['final_price'];
    $discountPercent = (int)$dbFood['discount_percent'];
    $hasDiscount = !empty($dbFood['has_discount']) && ($discountPercent > 0);
    $isAvailable = (bool)$dbFood['is_available'];
    $isSoldOut = !$isAvailable;
    $rating = (float)($dbFood['rating'] ?? 4.8);
    $ratingCount = (int)($dbFood['rating_count'] ?? 0);
    $foodImage = function_exists('getFoodImage') ? getFoodImage($dbFood['image'] ?: $foodName) : 'assets/images/placeholders/food-placeholder.jpg';

    $allCategoryFoods = dbGetAllFoodsWithPricing(['category_id' => $dbFood['category_id']]);
    $relatedFoods = [];
    foreach ($allCategoryFoods as $rf) {
        if ($rf['id'] != $foodId) {
            $relatedFoods[] = $rf;
        }
    }
} else {
    header("Location: menu.php");
    exit;
}

// Check favorites status
$isFavorited = $userId ? dbIsFavorite($userId, $foodId) : false;

// Handle Review Submission POST
$reviewSuccessMsg = '';
$reviewErrorMsg = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'submit_review') {
    if (!$userId) {
        $reviewErrorMsg = "Please log in to submit a review.";
    } else {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($csrf)) {
            $reviewErrorMsg = "Invalid security token. Please refresh the page.";
        } else {
            $ratingVal = (int)($_POST['rating'] ?? 5);
            $reviewComment = trim($_POST['review_comment'] ?? '');
            $orderIdVal = (int)($_POST['order_id'] ?? 0);

            if ($ratingVal < 1 || $ratingVal > 5) {
                $reviewErrorMsg = "Please select a valid rating between 1 and 5 stars.";
            } elseif (empty($orderIdVal)) {
                $reviewErrorMsg = "Verification error: Order identifier missing.";
            } else {
                $revResult = dbCreateOrUpdateReview($userId, $foodId, $orderIdVal, $ratingVal, $reviewComment, 'approved');
                if ($revResult['success']) {
                    $reviewSuccessMsg = $revResult['message'];
                    $dbFood = dbGetFoodById($foodId);
                    $rating = (float)$dbFood['rating'];
                    $ratingCount = (int)$dbFood['rating_count'];
                } else {
                    $reviewErrorMsg = $revResult['error'];
                }
            }
        }
    }
}

// Review eligibility check
$reviewEligibility = $userId ? dbCanUserReviewFood($userId, $foodId) : ['can_review' => false, 'order_id' => null, 'existing_review' => null, 'reason' => 'Please log in to leave a review.'];

// Rating summary breakdown
$ratingSummary = dbGetFoodRatingSummary($foodId);
$approvedReviews = dbGetFoodReviews($foodId, 'approved');

$ingredients = [
    'Fresh Dough / Core Ingredients',
    'Specialty Cheese & Herbs',
    'House Signature Sauce',
    'Authentic Seasonings'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($foodName, ENT_QUOTES, 'UTF-8'); ?> - <?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?= htmlspecialchars($foodDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main id="main-content">
        <nav class="details-breadcrumb-nav py-3" aria-label="Breadcrumb">
            <div class="container">
                <ol class="breadcrumb mb-0 align-items-center extra-small">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted"><i class="bi bi-house me-1"></i>Home</a></li>
                    <li class="breadcrumb-item"><a href="menu.php" class="text-decoration-none text-muted">Menu</a></li>
                    <li class="breadcrumb-item"><a href="menu.php?category=<?= urlencode($categorySlug); ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li class="breadcrumb-item active text-primary-accent fw-bold text-truncate" aria-current="page"><?= htmlspecialchars($foodName, ENT_QUOTES, 'UTF-8'); ?></li>
                </ol>
            </div>
        </nav>

        <section class="food-details-section py-4 py-lg-5" aria-label="Product Information">
            <div class="container">
                <div class="row g-4 g-lg-5 align-items-start">
                    <div class="col-12 col-lg-6">
                        <div class="food-details-image-card card p-2 p-md-3 border-0 shadow-sm rounded-4 bg-white">
                            <div class="food-details-image-wrap position-relative rounded-4 overflow-hidden">
                                <img src="<?= htmlspecialchars($foodImage, ENT_QUOTES, 'UTF-8'); ?>" 
                                     alt="<?= htmlspecialchars($foodName, ENT_QUOTES, 'UTF-8'); ?>" 
                                     class="food-details-image w-100" 
                                     style="max-height: 440px; object-fit: cover;"
                                     loading="eager">
                                <div class="food-details-badge-overlay position-absolute bottom-0 start-0 m-3">
                                    <span class="badge-tag-category badge bg-dark bg-opacity-75 text-white px-3 py-2 rounded-pill">
                                        <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>

                                <?php if ($userId): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-light rounded-circle shadow p-0 position-absolute top-0 end-0 m-3 d-flex align-items-center justify-content-center border-0 js-toggle-favorite <?= $isFavorited ? 'text-danger' : 'text-muted'; ?>" 
                                            style="width: 40px; height: 40px; z-index: 5;"
                                            data-food-id="<?= $foodId; ?>"
                                            data-csrf="<?= generateCsrfToken(); ?>"
                                            title="<?= $isFavorited ? 'Remove from favorites' : 'Add to favorites'; ?>">
                                        <i class="bi <?= $isFavorited ? 'bi-heart-fill' : 'bi-heart'; ?> fs-5"></i>
                                    </button>
                                <?php else: ?>
                                    <a href="login.php?return_url=<?= urlencode('food-details.php?food=' . $foodSlug); ?>" 
                                       class="btn btn-sm btn-light rounded-circle shadow p-0 position-absolute top-0 end-0 m-3 d-flex align-items-center justify-content-center border-0 text-muted" 
                                       style="width: 40px; height: 40px; z-index: 5;"
                                       title="Login to save favorite">
                                        <i class="bi bi-heart fs-5"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="food-details-info">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <span class="food-details-eyebrow text-uppercase fw-bold text-primary-accent small">
                                    <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <?php if ($isSoldOut): ?>
                                    <span class="badge bg-danger text-white px-3 py-1 extra-small fw-bold d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-x-circle-fill"></i>
                                        <span>Sold Out</span>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 extra-small fw-bold d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>In Stock &bull; Freshly Prepared</span>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h1 class="food-details-title fw-bold mb-2 text-dark"><?= htmlspecialchars($foodName, ENT_QUOTES, 'UTF-8'); ?></h1>

                            <div class="d-flex align-items-center gap-3 mb-3">
                                <a href="#reviews-section" class="text-decoration-none d-inline-flex align-items-center gap-2">
                                    <div class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded bg-warning-subtle text-dark border border-warning-subtle extra-small fw-bold">
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <span><?= number_format($rating, 1); ?></span>
                                    </div>
                                    <span class="text-primary-accent small text-decoration-underline"><?= $ratingCount; ?> customer review<?= ($ratingCount === 1) ? '' : 's'; ?></span>
                                </a>
                            </div>

                            <div class="food-details-price-wrap mb-3 pb-3 border-bottom d-flex align-items-baseline gap-2">
                                <?php if ($hasDiscount): ?>
                                    <span class="food-details-price text-primary-accent fw-bold fs-2">$<?= number_format($finalPrice, 2); ?></span>
                                    <span class="text-muted text-decoration-line-through fs-5">$<?= number_format($originalPrice, 2); ?></span>
                                    <span class="badge bg-danger text-white extra-small fw-bold ms-1"><?= $discountPercent; ?>% OFF</span>
                                <?php else: ?>
                                    <span class="food-details-price text-dark fw-bold fs-2">$<?= number_format($finalPrice, 2); ?></span>
                                <?php endif; ?>
                            </div>

                            <p class="food-details-description text-muted mb-4">
                                <?= htmlspecialchars($foodDescription, ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <div class="food-details-ingredients-wrap mb-4">
                                <h2 class="food-details-subheading h6 fw-bold mb-2 text-uppercase text-dark">Fresh Ingredients</h2>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($ingredients as $ingredient): ?>
                                        <span class="badge bg-pink-soft text-dark border border-pink-subtle px-3 py-2 rounded-pill small fw-normal d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-check2 text-primary-accent"></i>
                                            <span><?= htmlspecialchars($ingredient, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <form method="POST" action="cart.php" class="food-details-actions d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 pt-3 border-top">
            <?= csrfField(); ?>
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="food_id" value="<?= $foodId; ?>">

                                <div class="quantity-control-wrapper d-inline-flex align-items-center border rounded-pill p-1 bg-light">
                                    <button type="button" class="btn btn-sm btn-link text-dark p-1" id="btnQtyMinus" aria-label="Decrease quantity" <?= $isSoldOut ? 'disabled' : ''; ?>>
                                        <i class="bi bi-dash fs-5"></i>
                                    </button>
                                    <input type="number" 
                                           class="form-control quantity-input text-center border-0 bg-transparent fw-bold" 
                                           id="detailsQuantityInput" 
                                           name="quantity"
                                           value="1" 
                                           min="1" 
                                           max="99" 
                                           style="width: 50px;"
                                           readonly>
                                    <button type="button" class="btn btn-sm btn-link text-dark p-1" id="btnQtyPlus" aria-label="Increase quantity" <?= $isSoldOut ? 'disabled' : ''; ?>>
                                        <i class="bi bi-plus fs-5"></i>
                                    </button>
                                </div>

                                <?php if ($isSoldOut): ?>
                                    <button type="button" class="btn btn-auth-primary flex-grow-1 disabled d-inline-flex align-items-center justify-content-center gap-2 py-2" disabled>
                                        <i class="bi bi-x-circle-fill"></i>
                                        <span>Sold Out (Unavailable)</span>
                                    </button>
                                <?php else: ?>
                                    <button type="submit" 
                                            class="btn btn-auth-primary flex-grow-1 d-inline-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" 
                                            id="btnDetailsAddToCart">
                                        <i class="bi bi-bag-plus"></i>
                                        <span>Add to Cart &bull; $<span id="detailsBtnPrice"><?= number_format($finalPrice, 2); ?></span></span>
                                    </button>
                                <?php endif; ?>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Reviews & Ratings Section -->
        <section class="reviews-details-section py-5 bg-white border-top border-bottom" id="reviews-section" aria-label="Customer Reviews">
            <div class="container">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
                    <div>
                        <span class="dashboard-eyebrow d-inline-block">COMMUNITY FEEDBACK</span>
                        <h2 class="h3 fw-bold text-dark mb-1">Customer Reviews &amp; Ratings</h2>
                        <p class="text-muted mb-0">Genuine feedback from verified customers who enjoyed this meal.</p>
                    </div>
                </div>

                <?php if (!empty($reviewSuccessMsg)): ?>
                    <div class="alert alert-success py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <div><?= htmlspecialchars($reviewSuccessMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($reviewErrorMsg)): ?>
                    <div class="alert alert-danger py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                        <div><?= htmlspecialchars($reviewErrorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <div class="col-12 col-lg-4">
                        <div class="card border-0 bg-pink-soft p-4 rounded-4 text-center">
                            <div class="rating-score-box mb-2">
                                <span class="display-3 fw-bold text-primary-accent"><?= number_format($ratingSummary['average_rating'], 1); ?></span>
                                <span class="text-muted fs-5">/ 5</span>
                            </div>
                            <div class="rating-stars mb-2 fs-5 text-warning">
                                <?php 
                                    $fullStars = floor($ratingSummary['average_rating']);
                                    for ($s = 1; $s <= 5; $s++) {
                                        if ($s <= $fullStars) {
                                            echo '<i class="bi bi-star-fill"></i> ';
                                        } elseif ($s - $ratingSummary['average_rating'] <= 0.5) {
                                            echo '<i class="bi bi-star-half"></i> ';
                                        } else {
                                            echo '<i class="bi bi-star"></i> ';
                                        }
                                    }
                                ?>
                            </div>
                            <p class="text-muted small mb-4">Based on <?= $ratingSummary['total_reviews']; ?> verified review<?= ($ratingSummary['total_reviews'] === 1) ? '' : 's'; ?></p>

                            <div class="rating-breakdown-bars text-start">
                                <?php for ($star = 5; $star >= 1; $star--): 
                                    $starCount = $ratingSummary['breakdown'][$star] ?? 0;
                                    $pct = ($ratingSummary['total_reviews'] > 0) ? round(($starCount / $ratingSummary['total_reviews']) * 100) : 0;
                                ?>
                                    <div class="d-flex align-items-center gap-2 mb-1 extra-small">
                                        <span style="width: 45px;"><?= $star; ?> stars</span>
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-primary-accent" role="progressbar" style="width: <?= $pct; ?>%;" aria-valuenow="<?= $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span class="text-muted" style="width: 25px; text-align: right;"><?= $starCount; ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm rounded-4 p-4 mt-4 bg-light">
                            <h3 class="h6 fw-bold text-dark mb-2">Write a Review</h3>
                            <?php if (!$userId): ?>
                                <p class="text-muted small mb-3">Have you ordered this delicious meal? Log in to leave your rating and feedback.</p>
                                <a href="login.php?return_url=<?= urlencode('food-details.php?food=' . $foodSlug . '#reviews-section'); ?>" class="btn btn-outline-pink btn-sm w-100">
                                    <i class="bi bi-person-fill me-1"></i>
                                    <span>Log In to Review</span>
                                </a>
                            <?php elseif (!empty($reviewEligibility['can_review'])): ?>
                                <?php 
                                    $existingRev = $reviewEligibility['existing_review'];
                                    $currentScore = $existingRev ? (int)$existingRev['rating'] : 5;
                                    $currentComment = $existingRev ? $existingRev['review'] : '';
                                ?>
                                <p class="text-success extra-small mb-3"><i class="bi bi-shield-check me-1"></i>Verified Purchase (Order #<?= htmlspecialchars($reviewEligibility['order_number'] ?? ''); ?>)</p>
                                <form method="POST" action="food-details.php?food=<?= urlencode($foodSlug); ?>#reviews-section">
                                    <input type="hidden" name="action" value="submit_review">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                    <input type="hidden" name="order_id" value="<?= $reviewEligibility['order_id']; ?>">

                                    <div class="mb-3">
                                        <label for="rating" class="form-label extra-small fw-bold text-dark">Your Rating</label>
                                        <select name="rating" id="rating" class="form-select form-select-sm">
                                            <option value="5" <?= ($currentScore === 5) ? 'selected' : ''; ?>>★★★★★ (5 Stars - Excellent)</option>
                                            <option value="4" <?= ($currentScore === 4) ? 'selected' : ''; ?>>★★★★☆ (4 Stars - Very Good)</option>
                                            <option value="3" <?= ($currentScore === 3) ? 'selected' : ''; ?>>★★★☆☆ (3 Stars - Average)</option>
                                            <option value="2" <?= ($currentScore === 2) ? 'selected' : ''; ?>>★★☆☆☆ (2 Stars - Below Average)</option>
                                            <option value="1" <?= ($currentScore === 1) ? 'selected' : ''; ?>>★☆☆☆☆ (1 Star - Poor)</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="review_comment" class="form-label extra-small fw-bold text-dark">Your Experience</label>
                                        <textarea name="review_comment" id="review_comment" rows="3" class="form-control form-control-sm" placeholder="Tell us what you loved about this dish..." required><?= htmlspecialchars($currentComment, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-auth-primary btn-sm w-100 shadow-sm">
                                        <i class="bi bi-send-fill me-1"></i>
                                        <span><?= $existingRev ? 'Update My Review' : 'Submit Review'; ?></span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="p-3 bg-white rounded-3 border extra-small text-muted">
                                    <i class="bi bi-info-circle text-primary-accent me-1"></i>
                                    <span><?= htmlspecialchars($reviewEligibility['reason'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-12 col-lg-8">
                        <div class="reviews-list-wrapper">
                            <?php if (empty($approvedReviews)): ?>
                                <div class="text-center py-5 bg-light rounded-4 border p-4">
                                    <i class="bi bi-chat-heart text-muted display-4 mb-3 d-block" style="opacity: 0.5;"></i>
                                    <h3 class="h5 fw-bold text-dark mb-1">No reviews yet for this dish</h3>
                                    <p class="text-muted small mb-0">Be the first verified customer to order and share your feedback!</p>
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-3">
                                    <?php foreach ($approvedReviews as $rev): 
                                        $revAvatar = function_exists('getAvatarImage') ? getAvatarImage($rev['user_avatar'] ?? '') : 'assets/images/placeholders/avatar-placeholder.jpg';
                                        $revStars = (int)$rev['rating'];
                                    ?>
                                        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white">
                                            <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?= htmlspecialchars($revAvatar, ENT_QUOTES, 'UTF-8'); ?>" 
                                                         alt="<?= htmlspecialchars($rev['user_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                         class="rounded-circle" 
                                                         style="width: 44px; height: 44px; object-fit: cover;">
                                                    <div>
                                                        <h4 class="h6 fw-bold text-dark mb-0"><?= htmlspecialchars($rev['user_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle extra-small">
                                                            <i class="bi bi-patch-check-fill me-1"></i>Verified Buyer
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="text-warning extra-small mb-1">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi <?= ($i <= $revStars) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="text-muted extra-small d-block"><?= date('M d, Y', strtotime($rev['created_at'])); ?></span>
                                                </div>
                                            </div>
                                            <p class="text-muted small mb-0 mt-2"><?= nl2br(htmlspecialchars($rev['review'] ?? '', ENT_QUOTES, 'UTF-8')); ?></p>

                                            <?php if (!empty($rev['admin_response'])): ?>
                                                <div class="mt-3 p-3 bg-pink-soft rounded-3 border border-pink-subtle extra-small">
                                                    <span class="fw-bold text-primary-accent d-block mb-1"><i class="bi bi-reply-fill me-1"></i>FastBite Kitchen Response:</span>
                                                    <span class="text-dark"><?= htmlspecialchars($rev['admin_response'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php if (!empty($relatedFoods)): ?>
            <section class="related-foods-section py-5 bg-light" aria-label="Related Foods">
                <div class="container">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <span class="menu-eyebrow d-inline-block">RECOMMENDED FOR YOU</span>
                            <h2 class="h4 fw-bold mb-0">More From <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></h2>
                        </div>
                        <a href="menu.php?category=<?= urlencode($categorySlug); ?>" class="btn btn-outline-pink btn-sm d-inline-flex align-items-center gap-1">
                            <span>View All</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>

                    <div class="row g-3 g-md-4">
                        <?php foreach (array_slice($relatedFoods, 0, 4) as $rel): ?>
                            <?php
                                $relImage = function_exists('getFoodImage') ? getFoodImage($rel['image'] ?: $rel['name']) : 'assets/images/placeholders/food-placeholder.jpg';
                                $relPrice = (float)$rel['final_price'];
                                $relOrigPrice = (float)$rel['original_price'];
                                $relHasDisc = !empty($rel['has_discount']);
                                $relSoldOut = empty($rel['is_available']);
                                $relSlug = $rel['slug'] ?? 'food-' . $rel['id'];
                            ?>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="menu-food-card card h-100 <?= $relSoldOut ? 'sold-out' : ''; ?>">
                                    <div class="menu-food-image-wrap position-relative">
                                        <a href="food-details.php?food=<?= urlencode($relSlug); ?>" class="d-block w-100 h-100">
                                            <img src="<?= htmlspecialchars($relImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($rel['name'], ENT_QUOTES, 'UTF-8'); ?>" class="menu-food-image" loading="lazy">
                                        </a>
                                        <?php if ($relSoldOut): ?>
                                            <div class="position-absolute top-0 end-0 m-2">
                                                <span class="sold-out-badge shadow-sm">SOLD OUT</span>
                                            </div>
                                        <?php elseif ($relHasDisc): ?>
                                            <div class="position-absolute top-0 end-0 m-2">
                                                <span class="discount-badge-ribbon shadow-sm"><?= $rel['discount_percent']; ?>% OFF</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="menu-food-body card-body d-flex flex-column">
                                        <h3 class="menu-food-name mb-1">
                                            <a href="food-details.php?food=<?= urlencode($relSlug); ?>" class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($rel['name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </h3>
                                        <p class="menu-food-desc mb-3 flex-grow-1 text-muted small"><?= htmlspecialchars($rel['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                        <div class="menu-food-footer d-flex align-items-center justify-content-between pt-2 border-top">
                                            <div class="menu-food-price-wrap">
                                                <?php if ($relHasDisc): ?>
                                                    <span class="menu-food-price fw-bold text-primary-accent">$<?= number_format($relPrice, 2); ?></span>
                                                    <span class="text-muted text-decoration-line-through extra-small">$<?= number_format($relOrigPrice, 2); ?></span>
                                                <?php else: ?>
                                                    <span class="menu-food-price fw-bold">$<?= number_format($relPrice, 2); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <a href="food-details.php?food=<?= urlencode($relSlug); ?>" class="btn btn-menu-cart">View</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
    const qtyInput = document.getElementById('detailsQuantityInput');
    const btnMinus = document.getElementById('btnQtyMinus');
    const btnPlus = document.getElementById('btnQtyPlus');
    const btnPriceSpan = document.getElementById('detailsBtnPrice');
    const unitPrice = <?= json_encode($finalPrice); ?>;

    if (qtyInput && btnMinus && btnPlus && btnPriceSpan) {
        btnMinus.addEventListener('click', () => {
            let val = parseInt(qtyInput.value) || 1;
            if (val > 1) {
                val--;
                qtyInput.value = val;
                btnPriceSpan.textContent = (unitPrice * val).toFixed(2);
            }
        });

        btnPlus.addEventListener('click', () => {
            let val = parseInt(qtyInput.value) || 1;
            if (val < 99) {
                val++;
                qtyInput.value = val;
                btnPriceSpan.textContent = (unitPrice * val).toFixed(2);
            }
        });
    }

    document.querySelectorAll('.js-toggle-favorite').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const foodId = this.getAttribute('data-food-id');
            const csrf = this.getAttribute('data-csrf');
            const icon = this.querySelector('i');
            const self = this;

            const formData = new FormData();
            formData.append('food_id', foodId);
            formData.append('csrf_token', csrf);

            fetch('toggle-favorite.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (data.is_favorite) {
                        icon.classList.remove('bi-heart');
                        icon.classList.add('bi-heart-fill');
                        self.classList.remove('text-muted');
                        self.classList.add('text-danger');
                    } else {
                        icon.classList.remove('bi-heart-fill');
                        icon.classList.add('bi-heart');
                        self.classList.remove('text-danger');
                        self.classList.add('text-muted');
                    }
                    const navBadge = document.getElementById('nav-fav-count');
                    if (navBadge) {
                        navBadge.textContent = data.favorites_count;
                        navBadge.style.display = (data.favorites_count > 0) ? 'inline-block' : 'none';
                    }
                }
            })
            .catch(err => console.error('Favorites toggle error:', err));
        });
    });
    </script>
</body>
</html>
