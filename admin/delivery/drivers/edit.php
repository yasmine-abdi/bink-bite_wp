<?php
/**
 * FastBite - Edit Courier Details
 * STEP 32: Delivery Management & Courier Operations Center
 */

$pageTitle = 'Edit Courier Details';
$activeMenu = 'drivers';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$driverId = (int)($_GET['id'] ?? 0);
if ($driverId <= 0) {
    header("Location: index.php?error=" . urlencode("Invalid courier ID."));
    exit;
}

$driver = dbGetDriverById($driverId);
if (!$driver) {
    header("Location: index.php?error=" . urlencode("Courier not found."));
    exit;
}

$errorMsg = '';
$fullName = $driver['full_name'];
$phone = $driver['phone'];
$email = $driver['email'] ?? '';
$vehicleType = $driver['vehicle_type'];
$vehicleNumber = $driver['vehicle_number'] ?? '';
$status = $driver['status'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $errorMsg = "Security validation failed. Please refresh and try again.";
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $vehicleType = trim($_POST['vehicle_type'] ?? 'motorcycle');
        $vehicleNumber = trim($_POST['vehicle_number'] ?? '');
        $status = trim($_POST['status'] ?? 'available');

        if (empty($fullName)) {
            $errorMsg = "Courier full name is required.";
        } elseif (empty($phone)) {
            $errorMsg = "Contact phone number is required.";
        } else {
            $updated = dbUpdateDriver($driverId, [
                'full_name' => $fullName,
                'phone' => $phone,
                'email' => $email,
                'vehicle_type' => $vehicleType,
                'vehicle_number' => $vehicleNumber,
                'status' => $status
            ]);

            if ($updated) {
                header("Location: index.php?msg=" . urlencode("Courier '{$fullName}' updated successfully."));
                exit;
            } else {
                $errorMsg = "Failed to update courier details.";
            }
        }
    }
}
?>

<main class="admin-main flex-grow-1 p-3 p-md-4 p-xl-5 bg-light">
    <div class="container-fluid p-0" style="max-width: 800px;">
        
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb extra-small mb-1">
                        <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none text-muted">Delivery Ops</a></li>
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Couriers</a></li>
                        <li class="breadcrumb-item active text-primary-accent fw-bold" aria-current="page">Edit Courier</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-dark mb-1">Edit Courier: <?= htmlspecialchars($driver['full_name']); ?></h1>
                <p class="text-muted small mb-0">Update contact info, vehicle details, or duty status.</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-light btn-sm rounded-pill border px-3">
                    <i class="bi bi-arrow-left me-1"></i>Back to Fleet
                </a>
            </div>
        </div>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($errorMsg); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <form method="POST" action="edit.php?id=<?= $driverId; ?>">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-bold text-dark">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($fullName); ?>" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-bold text-dark">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone); ?>" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-bold text-dark">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email); ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-bold text-dark">Vehicle Type <span class="text-danger">*</span></label>
                        <select name="vehicle_type" class="form-select" required>
                            <option value="motorcycle" <?= ($vehicleType === 'motorcycle') ? 'selected' : ''; ?>>Motorcycle / Bike</option>
                            <option value="bicycle" <?= ($vehicleType === 'bicycle') ? 'selected' : ''; ?>>Bicycle</option>
                            <option value="scooter" <?= ($vehicleType === 'scooter') ? 'selected' : ''; ?>>Electric Scooter</option>
                            <option value="car" <?= ($vehicleType === 'car') ? 'selected' : ''; ?>>Car / Sedan</option>
                            <option value="van" <?= ($vehicleType === 'van') ? 'selected' : ''; ?>>Delivery Van</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-bold text-dark">Vehicle Plate / Reg Number</label>
                        <input type="text" name="vehicle_number" class="form-control" value="<?= htmlspecialchars($vehicleNumber); ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-bold text-dark">Duty Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="available" <?= ($status === 'available') ? 'selected' : ''; ?>>Available (Ready for Dispatch)</option>
                            <option value="busy" <?= ($status === 'busy') ? 'selected' : ''; ?>>Busy (On Delivery)</option>
                            <option value="offline" <?= ($status === 'offline') ? 'selected' : ''; ?>>Offline (Off Duty)</option>
                            <option value="suspended" <?= ($status === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-4">
                    <a href="index.php" class="btn btn-light rounded-pill px-4">Cancel</a>
                    <button type="submit" class="btn btn-auth-primary rounded-pill px-5 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i>Update Courier
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
