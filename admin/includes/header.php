<?php
/**
 * FastBite - Admin Header Component
 * STEP 24: Backend Foundation & Database Integration
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(dirname(__DIR__)));
}

require_once FASTBITE_ROOT_DIR . '/config/database.php';
require_once FASTBITE_ROOT_DIR . '/includes/auth.php';
require_once FASTBITE_ROOT_DIR . '/includes/image-helper.php';

// Determine relative root path to project root
if (!isset($relRoot)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
    $rootDir = str_replace('\\', '/', FASTBITE_ROOT_DIR);
    $rel = trim(str_replace($rootDir, '', $scriptDir), '/');
    $depth = empty($rel) ? 0 : count(explode('/', $rel));
    $relRoot = ($depth > 0) ? str_repeat('../', $depth) : './';
}

// Enforce admin authentication
requireAdmin($relRoot . 'login.php');

$currentAdmin = currentUser();
$projectName = 'FastBite Admin';
$adminUser = [
    'name' => $currentAdmin ? $currentAdmin['name'] : 'Admin Manager',
    'email' => $currentAdmin ? $currentAdmin['email'] : 'admin@fastbite.com',
    'role' => 'Administrator',
    'avatar' => function_exists('getAvatarImage') ? getAvatarImage($currentAdmin['avatar'] ?? 'avatar-placeholder.jpg') : 'assets/images/placeholders/avatar-placeholder.jpg'
];

if (!isset($activeMenu)) {
    $activeMenu = 'menu';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Menu Management', ENT_QUOTES, 'UTF-8'); ?> - FastBite Admin Portal</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- FastBite Custom Stylesheet -->
    <link rel="stylesheet" href="<?= $relRoot; ?>assets/css/style.css">
</head>
<body class="admin-body">

    <!-- Admin Top Navbar -->
    <header class="admin-topbar navbar navbar-expand navbar-light bg-white border-bottom sticky-top py-2 px-3 px-lg-4">
        <div class="container-fluid p-0 d-flex align-items-center justify-content-between">
            
            <!-- Left: Toggle & Brand -->
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sm btn-outline-pink d-lg-none" id="adminSidebarToggle" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark m-0" href="<?= $relRoot; ?>admin/index.php">
                    <span class="brand-logo-circle">
                        <i class="bi bi-fire text-white"></i>
                    </span>
                    <span class="brand-text">Fast<span class="text-primary-accent">Bite</span> <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small ms-1">ADMIN</span></span>
                </a>
            </div>

            <!-- Right: Storefront Quick Link & User Profile -->
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <a href="<?= $relRoot; ?>admin/security/index.php" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-1" title="Security Activity Center">
                    <i class="bi bi-shield-lock"></i>
                    <span class="d-none d-md-inline">Security</span>
                </a>

                <a href="<?= $relRoot; ?>menu.php" target="_blank" class="btn btn-sm btn-outline-pink d-none d-sm-inline-flex align-items-center gap-1" title="Preview Live Menu">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>Live Store Menu</span>
                </a>

                <div class="admin-user-dropdown d-flex align-items-center gap-2">
                    <img src="<?= $relRoot . htmlspecialchars($adminUser['avatar'], ENT_QUOTES, 'UTF-8'); ?>" 
                         alt="Admin Avatar" 
                         class="admin-nav-avatar rounded-circle border">
                    <div class="d-none d-md-block text-start leading-none">
                        <span class="d-block fw-bold text-dark extra-small"><?= htmlspecialchars($adminUser['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="text-muted extra-small" style="font-size: 0.72rem;"><?= htmlspecialchars($adminUser['role'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </div>

        </div>
    </header>

    <!-- Admin Layout Wrapper -->
    <div class="admin-layout d-flex">
