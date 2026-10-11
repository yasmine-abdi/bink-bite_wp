<?php
/**
 * FastBite / Bink Bite Fast Food - Project Entry Point
 * Step 1: Project Setup • Step 2: Navbar • Step 3: Hero • Step 4: Categories • Step 5: Featured Foods • Step 6: Why Choose Us • Step 7: Popular Foods • Step 8: Customer Reviews • Step 9: About Us • Step 10: Contact Us • Step 22: Image Management
 */

require_once __DIR__ . '/includes/image-helper.php';

$projectName = 'FastBite';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?> - Delicious Food, Delivered Fast</title>
    <meta name="description" content="FastBite - Modern Fast Food Restaurant. Order freshly prepared burgers, pizza, shawarma, drinks and more with fast delivery.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS (Pink + White Design System) -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Step 2: Navbar Component -->
    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main id="main-content">
        <!-- Step 3: Hero Section Component -->
        <?php include_once __DIR__ . '/includes/hero.php'; ?>

        <!-- Step 4: Categories Section Component -->
        <?php include_once __DIR__ . '/includes/categories.php'; ?>

        <!-- Step 5: Featured Foods Section Component -->
        <?php include_once __DIR__ . '/includes/featured-foods.php'; ?>

        <!-- Step 6: Why Choose Us Section Component -->
        <?php include_once __DIR__ . '/includes/why-choose-us.php'; ?>

        <!-- Step 7: Popular Foods Section Component -->
        <?php include_once __DIR__ . '/includes/popular-foods.php'; ?>

        <!-- Step 8: Customer Reviews Section Component -->
        <?php include_once __DIR__ . '/includes/reviews.php'; ?>

        <!-- Step 9: About Us Section Component -->
        <?php include_once __DIR__ . '/includes/about.php'; ?>

        <!-- Step 10: Contact Us Section Component -->
        <?php include_once __DIR__ . '/includes/contact.php'; ?>
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
