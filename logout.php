<?php
/**
 * FastBite - Secure Logout Handler
 * STEP 27: Admin Users, Roles & Security Management
 */

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $u = currentUser();
    if ($u && function_exists('dbLogSecurityEvent')) {
        dbLogSecurityEvent('logout', "User {$u['name']} ({$u['email']}) logged out securely", $u['id'], ($u['role'] === 'admin' ? $u['id'] : null));
    }
}

logoutUser();

header("Location: login.php?msg=" . urlencode("You have been securely logged out."));
exit;
