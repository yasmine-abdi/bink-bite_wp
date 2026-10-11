<?php
/**
 * FastBite - Google OAuth 2.0 Login Endpoint
 * STEP 31: Authentication Finalization & Google Sign-In
 */

require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect based on role
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/index.php");
        exit;
    } else {
        header("Location: customer-dashboard.php");
        exit;
    }
}

$returnUrl = trim($_GET['return_url'] ?? '');

// Check if Google OAuth credentials are configured
if (!function_exists('isGoogleOAuthEnabled') || !isGoogleOAuthEnabled()) {
    // Clear friendly notification when OAuth is not configured
    $msg = "Google Sign-In is currently unavailable. Please sign in with your FastBite email and password.";
    header("Location: login.php?error=" . urlencode($msg) . (!empty($returnUrl) ? "&return_url=" . urlencode($returnUrl) : ""));
    exit;
}

// Generate official Google OAuth authorization URL with CSRF state token
$authUrl = generateGoogleOAuthUrl($returnUrl);
if (!$authUrl) {
    header("Location: login.php?error=" . urlencode("Unable to initialize Google Sign-In session."));
    exit;
}

header("Location: " . $authUrl);
exit;
