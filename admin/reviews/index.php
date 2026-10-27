<?php
/**
 * FastBite - Admin Customer Reviews & Moderation Center
 * STEP 30: FastBite Customer Experience, Reviews, Favorites & Promotions
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/image-helper.php';

requireAdmin('../../login.php');

$activeMenu = 'reviews';
$projectName = 'FastBite Admin';
$adminUser = currentUser();
$adminId = (int)$adminUser['id'];

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Moderation Actions (Approve, Reject, Reply, Delete)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = "Security validation failed. Please refresh.";
    } else {
        $action = $_POST['action'] ?? '';
        $reviewId = (int)($_POST['review_id'] ?? 0);

        if ($action === 'approve' && $reviewId > 0) {
            $reply = trim($_POST['admin_response'] ?? '');
            dbAdminModerateReview($reviewId, 'approved', !empty($reply) ? $reply : null, $adminId);
            header("Location: index.php?msg=" . urlencode("Review #{$reviewId} approved successfully."));
            exit;
        } elseif ($action === 'reject' && $reviewId > 0) {
            dbAdminModerateReview($reviewId, 'rejected', null, $adminId);
            header("Location: index.php?msg=" . urlencode("Review #{$reviewId} marked as rejected."));
            exit;
        } elseif ($action === 'reply' && $reviewId > 0) {
            $reply = trim($_POST['admin_response'] ?? '');
            $status = $_POST['status'] ?? 'approved';
            dbAdminModerateReview($reviewId, $status, $reply, $adminId);
            header("Location: index.php?msg=" . urlencode("Response saved for review #{$reviewId}."));
            exit;
        } elseif ($action === 'delete' && $reviewId > 0) {
            dbAdminDeleteReview($reviewId, $adminId);
            header("Location: index.php?msg=" . urlencode("Review #{$reviewId} deleted."));
            exit;
        }
    }
}

// Search & Filter Parameters
$statusFilter = trim($_GET['status'] ?? 'all');
$ratingFilter = (int)($_GET['rating'] ?? 0);
$searchFilter = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$filters = [
    'status' => $statusFilter,
    'rating' => $ratingFilter,
    'search' => $searchFilter
];

$reviewData = dbAdminGetReviews($filters, $limit, $offset);
$reviews = $reviewData['reviews'];
$totalReviews = $reviewData['total'];
$totalPages = ceil($totalReviews / $limit);

// Moderation Metrics
$pdo = getDbConnection();
$totalCount = (int)$pdo->query("SELECT COUNT(*) FROM `food_reviews`")->fetchColumn();
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM `food_reviews` WHERE `status` = 'pending'")->fetchColumn();
$approvedCount = (int)$pdo->query("SELECT COUNT(*) FROM `food_reviews` WHERE `status` = 'approved'")->fetchColumn();
$avgRating = (float)$pdo->query("SELECT AVG(rating) FROM `food_reviews` WHERE `status` = 'approved'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews &amp; Ratings - <?= htmlspecialchars($projectName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="admin-dashboard-body">

    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="admin-layout d-flex min-vh-100">
        <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="admin-main-content flex-grow-1 p-3 p-md-4 p-lg-5">
            <div class="container-fluid px-0">

                <!-- Page Header -->
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
                    <div>
                        <span class="dashboard-eyebrow d-inline-block">MODERATION &amp; QUALITY</span>
                        <h1 class="h3 fw-bold text-dark mb-1">Customer Reviews &amp; Ratings</h1>
                        <p class="text-muted extra-small mb-0">Review, moderate, and reply to genuine customer ratings for all catalog foods.</p>
                    </div>
                </div>

                <!-- Feedback Alerts -->
                <?php if (!empty($msg)): ?>
                    <div class="alert alert-success py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <div><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 px-3 mb-4 small d-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                        <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Metrics Row -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-4 border-dark">
                            <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Total Reviews</span>
                            <span class="h3 fw-bold text-dark mb-0"><?= $totalCount; ?></span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-4 border-warning">
                            <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Pending Approval</span>
                            <span class="h3 fw-bold text-warning mb-0"><?= $pendingCount; ?></span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-4 border-success">
                            <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Approved Public</span>
                            <span class="h3 fw-bold text-success mb-0"><?= $approvedCount; ?></span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-4 border-info">
                            <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">Average Score</span>
                            <span class="h3 fw-bold text-primary-accent mb-0"><?= $avgRating > 0 ? number_format($avgRating, 1) : '4.8'; ?> <small class="fs-6 text-muted">/ 5</small></span>
                        </div>
                    </div>
                </div>

                <!-- Filters & Search Bar -->
                <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                    <form method="GET" action="index.php" class="row g-3 align-items-center">
                        <div class="col-12 col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" value="<?= htmlspecialchars($searchFilter, ENT_QUOTES, 'UTF-8'); ?>" class="form-control bg-light border-start-0" placeholder="Search customer, food, order #...">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit();">
                                <option value="all" <?= ($statusFilter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : ''; ?>>Pending Approval</option>
                                <option value="approved" <?= ($statusFilter === 'approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?= ($statusFilter === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <select name="rating" class="form-select form-select-sm" onchange="this.form.submit();">
                                <option value="0" <?= ($ratingFilter === 0) ? 'selected' : ''; ?>>All Ratings</option>
                                <option value="5" <?= ($ratingFilter === 5) ? 'selected' : ''; ?>>5 Stars</option>
                                <option value="4" <?= ($ratingFilter === 4) ? 'selected' : ''; ?>>4 Stars</option>
                                <option value="3" <?= ($ratingFilter === 3) ? 'selected' : ''; ?>>3 Stars</option>
                                <option value="2" <?= ($ratingFilter === 2) ? 'selected' : ''; ?>>2 Stars</option>
                                <option value="1" <?= ($ratingFilter === 1) ? 'selected' : ''; ?>>1 Star</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-auth-primary btn-sm flex-grow-1">Filter</button>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                        </div>
                    </form>
                </div>

                <!-- Reviews Table Card -->
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <?php if (empty($reviews)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-chat-square-text text-muted display-4 mb-2 d-block" style="opacity: 0.5;"></i>
                            <h3 class="h5 fw-bold text-dark mb-1">No reviews found</h3>
                            <p class="text-muted small mb-0">No customer reviews match your active filter criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Food Item</th>
                                        <th>Customer</th>
                                        <th>Order #</th>
                                        <th>Rating</th>
                                        <th>Review Comment</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reviews as $rev): 
                                        $foodImg = function_exists('getFoodImage') ? getFoodImage($rev['food_image'] ?: $rev['food_name']) : '../../assets/images/placeholders/food-placeholder.jpg';
                                        $custAvatar = function_exists('getAvatarImage') ? getAvatarImage($rev['customer_avatar'] ?? '') : '../../assets/images/placeholders/avatar-placeholder.jpg';
                                        $statusClass = ($rev['status'] === 'approved') ? 'bg-success-subtle text-success border-success-subtle' : (($rev['status'] === 'rejected') ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-warning-subtle text-warning-emphasis border-warning-subtle');
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?= htmlspecialchars($foodImg, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded-2" style="width: 42px; height: 42px; object-fit: cover;">
                                                    <div>
                                                        <a href="../../food-details.php?food=<?= urlencode($rev['food_slug'] ?? 'food'); ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                                                            <?= htmlspecialchars($rev['food_name'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?= htmlspecialchars($custAvatar, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                                    <div>
                                                        <span class="d-block fw-semibold text-dark"><?= htmlspecialchars($rev['customer_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                        <span class="d-block text-muted extra-small"><?= htmlspecialchars($rev['customer_email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="../orders/index.php?search=<?= urlencode($rev['order_number']); ?>" class="text-decoration-none fw-semibold">
                                                    #<?= htmlspecialchars($rev['order_number'], ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <div class="text-warning extra-small">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="bi <?= ($i <= (int)$rev['rating']) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <span class="extra-small text-muted fw-bold"><?= (int)$rev['rating']; ?> / 5</span>
                                            </td>
                                            <td style="max-width: 250px;">
                                                <p class="mb-1 text-truncate" title="<?= htmlspecialchars($rev['review'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?= htmlspecialchars($rev['review'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                                </p>
                                                <?php if (!empty($rev['admin_response'])): ?>
                                                    <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small text-truncate d-inline-block" style="max-width: 200px;" title="Reply: <?= htmlspecialchars($rev['admin_response'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <i class="bi bi-reply-fill me-1"></i><?= htmlspecialchars($rev['admin_response'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $statusClass; ?> border text-uppercase extra-small fw-bold">
                                                    <?= htmlspecialchars($rev['status'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td class="text-muted extra-small">
                                                <?= date('M d, Y', strtotime($rev['created_at'])); ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <?php if ($rev['status'] !== 'approved'): ?>
                                                        <form method="POST" action="index.php" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="review_id" value="<?= $rev['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-success px-2 py-1" title="Approve Review">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <?php if ($rev['status'] !== 'rejected'): ?>
                                                        <form method="POST" action="index.php" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="review_id" value="<?= $rev['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-warning px-2 py-1" title="Reject Review">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <!-- Reply Button triggers Modal -->
                                                    <button type="button" class="btn btn-sm btn-outline-pink px-2 py-1" data-bs-toggle="modal" data-bs-target="#replyModal<?= $rev['id']; ?>" title="Reply to Review">
                                                        <i class="bi bi-reply"></i>
                                                    </button>

                                                    <!-- Delete Form -->
                                                    <form method="POST" action="index.php" class="d-inline" onsubmit="return confirm('Permanently delete review #<?= $rev['id']; ?>?');">
                                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="review_id" value="<?= $rev['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" title="Delete Review">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>

                                                <!-- Reply Modal -->
                                                <div class="modal fade text-start" id="replyModal<?= $rev['id']; ?>" tabindex="-1" aria-labelledby="replyModalLabel<?= $rev['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content border-0 shadow">
                                                            <form method="POST" action="index.php">
                                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                                                <input type="hidden" name="action" value="reply">
                                                                <input type="hidden" name="review_id" value="<?= $rev['id']; ?>">
                                                                <div class="modal-header bg-pink-soft border-0">
                                                                    <h5 class="modal-title h6 fw-bold text-dark" id="replyModalLabel<?= $rev['id']; ?>">Reply to Review #<?= $rev['id']; ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body p-4">
                                                                    <div class="mb-3 p-3 bg-light rounded-3">
                                                                        <span class="d-block fw-bold text-dark extra-small mb-1"><?= htmlspecialchars($rev['customer_name']); ?> reviewed <?= htmlspecialchars($rev['food_name']); ?>:</span>
                                                                        <p class="small text-muted mb-0">"<?= htmlspecialchars($rev['review'] ?? ''); ?>"</p>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label extra-small fw-bold text-dark">Kitchen Response</label>
                                                                        <textarea name="admin_response" rows="3" class="form-control form-control-sm" placeholder="Write a public response to this customer..."><?= htmlspecialchars($rev['admin_response'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label extra-small fw-bold text-dark">Status</label>
                                                                        <select name="status" class="form-select form-select-sm">
                                                                            <option value="approved" <?= ($rev['status'] === 'approved') ? 'selected' : ''; ?>>Approved (Public)</option>
                                                                            <option value="pending" <?= ($rev['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                                            <option value="rejected" <?= ($rev['status'] === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer border-0 pt-0">
                                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-auth-primary btn-sm">Save Response</button>
                                                                </div>
                                                            </form>
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

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4" aria-label="Reviews pagination">
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= ($page === $p) ? 'active' : ''; ?>">
                                    <a class="page-link" href="index.php?page=<?= $p; ?>&status=<?= urlencode($statusFilter); ?>&rating=<?= $ratingFilter; ?>&search=<?= urlencode($searchFilter); ?>">
                                        <?= $p; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
