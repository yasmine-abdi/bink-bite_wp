<?php
/**
 * FastBite - Central Authentication & Authorization Module
 * STEP 24: Backend Foundation & Database Integration
 * 
 * Provides session lifecycle management, user identity resolution,
 * and role-based access control (RBAC) helpers.
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(__DIR__));
}

require_once FASTBITE_ROOT_DIR . '/config/database.php';

/**
 * Starts a secure session if not already active.
 */
function startFastBiteSession() {
    if (ob_get_level() === 0) {
        ob_start();
    }
    if (session_status() === PHP_SESSION_NONE) {
        // Set safe cookie parameters
        $lifetime = 60 * 60 * 24 * 7; // 7 days
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
        sendSecurityHeaders();
    }
}

// Automatically ensure session is active
startFastBiteSession();

/**
 * Checks if a user is currently logged in.
 *
 * @return bool
 */
function isLoggedIn() {
    return !empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

/**
 * Returns the currently authenticated user's ID, or null.
 *
 * @return int|null
 */
function currentUserId() {
    return isLoggedIn() ? (int)$_SESSION['user_id'] : null;
}

/**
 * Returns the full user record from database for the logged-in user.
 *
 * @return array|null
 */
function currentUser() {
    $userId = currentUserId();
    if (!$userId) {
        return null;
    }

    static $cachedUser = null;
    if ($cachedUser === null || (int)$cachedUser['id'] !== $userId) {
        $cachedUser = dbFindUserById($userId);
    }

    if ($cachedUser && isset($cachedUser['status']) && $cachedUser['status'] !== 'active') {
        logoutUser();
        return null;
    }

    return $cachedUser ?: null;
}

/**
 * Sends defensive HTTP security headers to protect against clickjacking, MIME-sniffing, etc.
 */
function sendSecurityHeaders() {
    if (!headers_sent()) {
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
}

/**
 * Checks if current user is an admin.
 *
 * @return bool
 */
function isAdmin() {
    if (!isLoggedIn()) return false;
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

/**
 * Checks if current user is a customer.
 *
 * @return bool
 */
function isCustomer() {
    if (!isLoggedIn()) return false;
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer');
}

/**
 * Checks if current user is a driver/courier.
 *
 * @return bool
 */
function isDriver() {
    if (!isLoggedIn()) return false;
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'driver');
}

/**
 * Sets session credentials for a user.
 *
 * @param array $user
 */
function loginUser($user) {
    startFastBiteSession();
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in_at'] = time();
}

/**
 * Destroys session and logs out current user.
 */
function logoutUser() {
    startFastBiteSession();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Requires login. Redirects unauthenticated visitors to login page.
 *
 * @param string $redirectUrl
 */
function requireLogin($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $target = $redirectUrl;
        if (!empty($currentUri) && strpos($currentUri, 'login.php') === false && strpos($currentUri, 'register.php') === false) {
            $target .= (strpos($target, '?') !== false ? '&' : '?') . 'return_url=' . urlencode($currentUri);
        }
        header("Location: " . $target);
        exit;
    }
}

/**
 * Requires customer access.
 *
 * @param string $redirectUrl
 */
function requireCustomer($redirectUrl = 'login.php') {
    requireLogin($redirectUrl);
    if (isAdmin()) {
        header("Location: admin/foods/index.php");
        exit;
    } elseif (isDriver()) {
        header("Location: driver/index.php");
        exit;
    }
}

/**
 * Requires admin access. Blocks non-admin users.
 *
 * @param string $redirectUrl
 */
function requireAdmin($redirectUrl = '../login.php', $customerRedirectUrl = null) {
    if (!isLoggedIn()) {
        header("Location: " . $redirectUrl . '?error=' . urlencode('Please log in as an administrator.'));
        exit;
    }

    if (!isAdmin()) {
        // Forbidden for standard customers and drivers
        if (isDriver()) {
            header("Location: ../driver/index.php?error=" . urlencode('Access restricted to administrators.'));
            exit;
        }
        if ($customerRedirectUrl === null) {
            $prefix = (strpos($redirectUrl, '../../') !== false) ? '../../' : '../';
            $customerRedirectUrl = $prefix . 'customer-dashboard.php';
        }
        header("Location: " . $customerRedirectUrl . '?error=' . urlencode('Access restricted to administrators.'));
        exit;
    }
}

/**
 * Requires driver/courier access. Blocks non-driver users.
 *
 * @param string $redirectUrl
 */
function requireDriver($redirectUrl = '../login.php', $fallbackUrl = null) {
    if (!isLoggedIn()) {
        header("Location: " . $redirectUrl . '?error=' . urlencode('Please log in as a FastBite courier.'));
        exit;
    }

    if (!isDriver() && !isAdmin()) {
        if ($fallbackUrl === null) {
            $fallbackUrl = (strpos($redirectUrl, '../') !== false) ? '../customer-dashboard.php' : 'customer-dashboard.php';
        }
        header("Location: " . $fallbackUrl . '?error=' . urlencode('Courier portal access required.'));
        exit;
    }
}


/**
 * CSRF PROTECTION HELPERS
 */

function getCsrfToken() {
    startFastBiteSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    startFastBiteSession();
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}


function generateCsrfToken() {
    return getCsrfToken();
}

function validateCsrfToken($token) {
    return verifyCsrfToken($token);
}

function csrfField() {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}


/**
 * BRUTE-FORCE PROTECTION HELPERS
 */

function getClientIdentifier($email = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $emailKey = strtolower(trim($email));
    return md5($ip . '|' . $emailKey);
}

function isLoginThrottled($email = '') {
    startFastBiteSession();
    $key = 'throttle_' . getClientIdentifier($email);
    if (!isset($_SESSION[$key])) {
        return false;
    }
    $record = $_SESSION[$key];
    $attempts = $record['attempts'] ?? 0;
    $lastAttempt = $record['last_attempt'] ?? 0;
    $lockoutTime = 300; // 5 minutes lockout

    if ($attempts >= 5) {
        if (time() - $lastAttempt < $lockoutTime) {
            $remaining = ceil(($lockoutTime - (time() - $lastAttempt)) / 60);
            return [
                'throttled' => true,
                'remaining_minutes' => max(1, (int)$remaining)
            ];
        } else {
            // Lockout expired, reset attempts
            unset($_SESSION[$key]);
            return false;
        }
    }
    return false;
}

function recordFailedLoginAttempt($email = '') {
    startFastBiteSession();
    $key = 'throttle_' . getClientIdentifier($email);
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'last_attempt' => time()
        ];
    } else {
        $_SESSION[$key]['attempts'] += 1;
        $_SESSION[$key]['last_attempt'] = time();
    }
}

function clearLoginAttempts($email = '') {
    startFastBiteSession();
    $key = 'throttle_' . getClientIdentifier($email);
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}
