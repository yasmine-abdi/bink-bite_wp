<?php
/**
 * FastBite - Google OAuth 2.0 Callback Handler
 * STEP 31: Authentication Finalization & Google Sign-In
 */

require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/index.php");
        exit;
    } else {
        header("Location: customer-dashboard.php");
        exit;
    }
}

$error = $_GET['error'] ?? '';
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (!empty($error)) {
    $errorMsg = ($error === 'access_denied') 
        ? "Google sign-in was cancelled or access was denied." 
        : "Google authentication failed: " . htmlspecialchars($error);
    header("Location: login.php?error=" . urlencode($errorMsg));
    exit;
}

if (empty($code) || empty($state)) {
    header("Location: login.php?error=" . urlencode("Invalid response from Google authentication service."));
    exit;
}

// Process OAuth token exchange & verified customer authentication server-side
$result = processGoogleOAuthCallback($code, $state);

if ($result['success']) {
    $user = $result['user'];
    $target = !empty($result['return_url']) ? $result['return_url'] : 'customer-dashboard.php';
    header("Location: " . $target);
    exit;
} else {
    header("Location: login.php?error=" . urlencode($result['error'] ?? "Google authentication failed."));
    exit;
}
