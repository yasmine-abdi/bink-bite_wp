<?php
/**
 * FastBite - Driver / Courier Portal Header
 * STEP 33: Complete Business Platform, Payments, Analytics, Support, Courier Portal & Production Security
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(dirname(__DIR__)));
}

require_once FASTBITE_ROOT_DIR . '/config/database.php';
require_once FASTBITE_ROOT_DIR . '/includes/auth.php';

// Enforce Courier RBAC
requireDriver('../login.php');

$user = currentUser();
$userId = (int)$user['id'];
$driver = dbGetDriverByUserId($userId);

if (!$driver) {
    header("Location: ../login.php?error=" . urlencode("Courier profile not linked. Please contact admin."));
    exit;
}

$driverId = (int)$driver['id'];
$driverStatus = $driver['status'] ?? 'available';

// Status badge styling
$dutyBadgeClass = 'bg-success text-white';
$dutyLabel = 'Online / Ready';
if ($driverStatus === 'busy') {
    $dutyBadgeClass = 'bg-warning text-dark';
    $dutyLabel = 'On Delivery';
} elseif ($driverStatus === 'offline') {
    $dutyBadgeClass = 'bg-secondary text-white';
    $dutyLabel = 'Offline';
} elseif ($driverStatus === 'suspended') {
    $dutyBadgeClass = 'bg-danger text-white';
    $dutyLabel = 'Suspended';
}

$activePage = $activePage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Courier Portal'); ?> - FastBite Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .driver-navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .driver-card-active {
            border-left: 5px solid var(--primary-accent, #ff2a5f) !important;
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Top Navigation Bar for Couriers -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top driver-navbar">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <span class="brand-icon d-flex align-items-center justify-content-center bg-primary-accent text-white rounded-circle" style="width: 34px; height: 34px;">
                    <i class="bi bi-bicycle"></i>
                </span>
                <span class="fw-bold fs-5">Fast<span class="text-primary-accent">Bite</span> <span class="badge bg-secondary extra-small fw-normal text-uppercase ms-1">Courier</span></span>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#driverNavContent" aria-controls="driverNavContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Links & Driver Status -->
            <div class="collapse navbar-collapse" id="driverNavContent">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1">
                    <li class="nav-item">
                        <a class="nav-link py-2 px-3 <?= ($activePage === 'dashboard') ? 'active fw-bold text-white' : 'text-light'; ?>" href="index.php">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-2 px-3 <?= ($activePage === 'orders') ? 'active fw-bold text-white' : 'text-light'; ?>" href="orders.php">
                            <i class="bi bi-bag-check me-1"></i> My Deliveries
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-2 px-3 <?= ($activePage === 'profile') ? 'active fw-bold text-white' : 'text-light'; ?>" href="profile.php">
                            <i class="bi bi-person-gear me-1"></i> Profile &amp; Stats
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Duty Status Badge / Quick Form -->
                    <div class="dropdown">
                        <button class="btn btn-sm <?= $dutyBadgeClass; ?> dropdown-toggle rounded-pill px-3 py-1 extra-small" type="button" id="dutyDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.55rem;"></i> <?= $dutyLabel; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" aria-labelledby="dutyDropdown">
                            <li><h6 class="dropdown-header extra-small text-uppercase">Update Shift Duty</h6></li>
                            <li>
                                <form method="POST" action="index.php">
                                    <?= csrfField(); ?>
                                    <input type="hidden" name="action" value="toggle_duty">
                                    <input type="hidden" name="new_status" value="available">
                                    <button type="submit" class="dropdown-item small py-2 d-flex align-items-center gap-2 text-success">
                                        <i class="bi bi-check-circle-fill"></i> Go Online (Available)
                                    </button>
                                </form>
                            </li>
                            <li>
                                <form method="POST" action="index.php">
                                    <?= csrfField(); ?>
                                    <input type="hidden" name="action" value="toggle_duty">
                                    <input type="hidden" name="new_status" value="busy">
                                    <button type="submit" class="dropdown-item small py-2 d-flex align-items-center gap-2 text-warning">
                                        <i class="bi bi-slash-circle-fill"></i> Set as Busy
                                    </button>
                                </form>
                            </li>
                            <li>
                                <form method="POST" action="index.php">
                                    <?= csrfField(); ?>
                                    <input type="hidden" name="action" value="toggle_duty">
                                    <input type="hidden" name="new_status" value="offline">
                                    <button type="submit" class="dropdown-item small py-2 d-flex align-items-center gap-2 text-secondary">
                                        <i class="bi bi-power"></i> Go Offline
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>

                    <div class="vr bg-secondary d-none d-lg-block my-1" style="height: 24px;"></div>

                    <!-- Driver User & Logout -->
                    <span class="text-light small fw-medium d-none d-md-inline">
                        <?= htmlspecialchars($driver['full_name']); ?>
                    </span>
                    <a href="../logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>
