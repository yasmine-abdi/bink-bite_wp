<?php
/**
 * FastBite - Toggle Favorite AJAX Endpoint
 * STEP 30: FastBite Customer Experience, Reviews, Favorites & Promotions
 */

require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$user = currentUser();
if (!$user) {
    echo json_encode([
        'success' => false,
        'requires_login' => true,
        'message' => 'Please log in to save items to your favorites.'
    ]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!verifyCsrfToken($csrf)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid security token. Please refresh.'
        ]);
        exit;
    }

    $foodId = (int)($_POST['food_id'] ?? 0);
    if ($foodId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid food item.'
        ]);
        exit;
    }

    $res = dbToggleFavorite((int)$user['id'], $foodId);
    echo json_encode($res);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
