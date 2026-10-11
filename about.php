<?php
/**
 * FastBite - Customer About Us Page
 * STEP 23: FastBite About Us Page
 * 
 * Features:
 * - Hero section with single H1 & CTA buttons
 * - Two-column Story section with image & key statistics
 * - 4 Why Choose FastBite feature cards
 * - Mission & Vision cards
 * - 4-step How FastBite Works process
 * - 10 Category exploration cards with getCategoryImage()
 * - FastBite statistics highlight ribbon
 * - Ready to Order CTA section
 * - Integrated Pink + White FastBite Design System
 */

require_once __DIR__ . '/includes/image-helper.php';

$currentPage = 'about';
$projectName = 'FastBite';

$heroBanner = function_exists('getBannerImage') ? getBannerImage('about-banner.jpg') : 'assets/images/placeholders/banner-placeholder.jpg';
$storyImage = function_exists('getAboutImage') ? getAboutImage('about-banner.jpg') : 'assets/images/placeholders/about-placeholder.jpg';

$categories = [
    ['name' => 'Pizza', 'slug' => 'pizza', 'hash' => 'cat-pizza', 'image' => 'pizza.jpg'],
    ['name' => 'Burgers', 'slug' => 'burgers', 'hash' => 'cat-burgers', 'image' => 'burgers.jpg'],
    ['name' => 'Shawarma', 'slug' => 'shawarma', 'hash' => 'cat-shawarma', 'image' => 'shawarma.jpg'],
    ['name' => 'Drinks', 'slug' => 'drinks', 'hash' => 'cat-drinks', 'image' => 'drinks.jpg'],
    ['name' => 'Coffee', 'slug' => 'coffee', 'hash' => 'cat-coffee', 'image' => 'coffee.jpg'],
    ['name' => 'Fried Chicken', 'slug' => 'chicken', 'hash' => 'cat-chicken', 'image' => 'chicken.jpg'],
    ['name' => 'Pasta', 'slug' => 'pasta', 'hash' => 'cat-pasta', 'image' => 'pasta.jpg'],
    ['name' => 'Sandwiches', 'slug' => 'sandwiches', 'hash' => 'cat-sandwiches', 'image' => 'sandwiches.jpg'],
    ['name' => 'Fries & Sides', 'slug' => 'fries', 'hash' => 'cat-fries', 'image' => 'fries.jpg'],
    ['name' => 'Desserts', 'slug' => 'desserts', 'hash' => 'cat-desserts', 'image' => 'desserts.jpg']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | FastBite</title>
    <meta name="description" content="Learn more about FastBite, our mission, our food, and how we make ordering delicious meals simple and convenient.">

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
<body class="d-flex flex-column min-vh-100 bg-light">

    <!-- Main Navbar Component -->
    <?php include_once __DIR__ . '/includes/navbar.php'; ?>

    <main id="main-content" class="flex-grow-1">

        <!-- 1. About Hero Section -->
        <header class="about-hero py-5 bg-white border-bottom position-relative overflow-hidden" aria-label="About FastBite Hero">
            <div class="container py-2 py-lg-4">
                <div class="row align-items-center g-4 g-lg-5">
                    <div class="col-12 col-lg-7 text-center text-lg-start">
                        <span class="menu-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent mb-2">ABOUT FASTBITE</span>
                        <h1 class="display-5 fw-bold text-dark mb-3">Good Food. Fast Delivery. Happy Customers.</h1>
                        <p class="lead text-muted mb-4" style="font-size: 1.1rem; max-width: 580px;">
                            FastBite makes ordering your favorite meals simple, fast, and convenient. We bring freshly made fast food right to your doorstep.
                        </p>
                        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start gap-3">
                            <a href="menu.php" class="btn btn-auth-primary d-inline-flex align-items-center gap-2 shadow-sm px-4 py-2">
                                <i class="bi bi-bag-plus"></i>
                                <span>Order Food</span>
                            </a>
                            <a href="contact.php" class="btn btn-outline-pink d-inline-flex align-items-center gap-2 px-4 py-2">
                                <i class="bi bi-chat-dots"></i>
                                <span>Contact Us</span>
                            </a>
                        </div>
                    </div>
                    <div class="col-12 col-lg-5 text-center">
                        <div class="about-hero-image-wrap rounded-4 overflow-hidden shadow-sm border border-pink-subtle position-relative">
                            <img src="<?= htmlspecialchars($heroBanner, ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="Fresh Delicious FastBite Food" 
                                 class="img-fluid w-100 object-fit-cover" 
                                 style="max-height: 360px;" 
                                 loading="lazy" 
                                 decoding="async">
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- 2. Our Story Section -->
        <section class="about-story py-5 bg-light" aria-label="Our Story">
            <div class="container py-2 py-lg-3">
                <div class="row align-items-center g-4 g-lg-5">
                    <!-- Left: Story Image -->
                    <div class="col-12 col-lg-6">
                        <div class="rounded-4 overflow-hidden shadow-sm border position-relative">
                            <img src="<?= htmlspecialchars($storyImage, ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="FastBite Kitchen and Food Preparation" 
                                 class="img-fluid w-100 object-fit-cover" 
                                 style="max-height: 420px;" 
                                 loading="lazy" 
                                 decoding="async">
                            <div class="position-absolute bottom-0 start-0 m-3 p-3 bg-white rounded-3 shadow-sm border border-pink-subtle d-inline-flex align-items-center gap-2">
                                <i class="bi bi-patch-check-fill text-primary-accent fs-4"></i>
                                <div>
                                    <span class="d-block fw-bold text-dark small">100% Quality Guaranteed</span>
                                    <span class="d-block extra-small text-muted">Fresh daily ingredients</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Story Content -->
                    <div class="col-12 col-lg-6">
                        <span class="menu-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent mb-2">OUR JOURNEY</span>
                        <h2 class="h2 fw-bold text-dark mb-3">Our Story</h2>
                        <p class="text-muted mb-3">
                            FastBite was created with a clear mission: to make delicious food ordering effortless, fast, and reliable for everyone in our community.
                        </p>
                        <p class="text-muted mb-4">
                            Whether you are craving sizzling cheesy pizza, gourmet handcrafted burgers, tender shawarma, or refreshing drinks, FastBite brings all your favorite flavors into one seamless digital experience. We focus on culinary craftsmanship, swift delivery, and exceptional customer care.
                        </p>

                        <!-- Highlights Grid -->
                        <div class="row g-3 pt-2 border-top">
                            <div class="col-6 col-sm-3">
                                <div class="p-3 bg-white rounded-3 border text-center about-stat">
                                    <span class="d-block h4 fw-bold text-primary-accent mb-1">2026</span>
                                    <span class="extra-small text-muted text-uppercase fw-semibold">Founded</span>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="p-3 bg-white rounded-3 border text-center about-stat">
                                    <span class="d-block h4 fw-bold text-primary-accent mb-1">40+</span>
                                    <span class="extra-small text-muted text-uppercase fw-semibold">Food Choices</span>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="p-3 bg-white rounded-3 border text-center about-stat">
                                    <span class="d-block h4 fw-bold text-primary-accent mb-1">10</span>
                                    <span class="extra-small text-muted text-uppercase fw-semibold">Categories</span>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="p-3 bg-white rounded-3 border text-center about-stat">
                                    <span class="d-block h4 fw-bold text-primary-accent mb-1">100%</span>
                                    <span class="extra-small text-muted text-uppercase fw-semibold">Customer Focus</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Why Choose FastBite -->
        <section class="py-5 bg-white border-top border-bottom" aria-label="Why Choose FastBite">
            <div class="container py-2">
                <div class="text-center mb-5">
                    <span class="menu-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent mb-2">OUR PROMISE</span>
                    <h2 class="h2 fw-bold text-dark mb-2">Why Choose FastBite?</h2>
                    <p class="text-muted small mx-auto mb-0" style="max-width: 540px;">
                        We are committed to delivering the highest quality meals with unmatched speed and customer-first service.
                    </p>
                </div>

                <div class="row g-4">
                    <!-- Feature 1: Quality Food -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 p-4 border-0 shadow-sm text-center about-feature-card">
                            <div class="about-icon-circle mx-auto mb-3 bg-pink-soft text-primary-accent rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-award fs-3"></i>
                            </div>
                            <h3 class="h6 fw-bold text-dark mb-2">Quality Food</h3>
                            <p class="text-muted small mb-0">We focus on fresh, delicious meals prepared with care.</p>
                        </div>
                    </div>

                    <!-- Feature 2: Fast Delivery -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 p-4 border-0 shadow-sm text-center about-feature-card">
                            <div class="about-icon-circle mx-auto mb-3 bg-pink-soft text-primary-accent rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-lightning-charge fs-3"></i>
                            </div>
                            <h3 class="h6 fw-bold text-dark mb-2">Fast Delivery</h3>
                            <p class="text-muted small mb-0">Get your favorite food delivered quickly and conveniently.</p>
                        </div>
                    </div>

                    <!-- Feature 3: Easy Ordering -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 p-4 border-0 shadow-sm text-center about-feature-card">
                            <div class="about-icon-circle mx-auto mb-3 bg-pink-soft text-primary-accent rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-phone fs-3"></i>
                            </div>
                            <h3 class="h6 fw-bold text-dark mb-2">Easy Ordering</h3>
                            <p class="text-muted small mb-0">Browse the menu, customize your order, and checkout with ease.</p>
                        </div>
                    </div>

                    <!-- Feature 4: Customer Support -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 p-4 border-0 shadow-sm text-center about-feature-card">
                            <div class="about-icon-circle mx-auto mb-3 bg-pink-soft text-primary-accent rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-headset fs-3"></i>
                            </div>
                            <h3 class="h6 fw-bold text-dark mb-2">Customer Support</h3>
                            <p class="text-muted small mb-0">Our team is available to help with your questions and orders.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. Our Mission & Vision -->
        <section class="py-5 bg-light" aria-label="Our Mission and Vision">
            <div class="container py-2">
                <div class="text-center mb-5">
                    <span class="menu-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent mb-2">OUR PURPOSE</span>
                    <h2 class="h2 fw-bold text-dark mb-2">Our Mission &amp; Vision</h2>
                    <p class="text-muted small mx-auto mb-0" style="max-width: 500px;">
                        Guiding our growth and our everyday passion for great food and happy customers.
                    </p>
                </div>

                <div class="row g-4 justify-content-center">
                    <!-- Mission Card -->
                    <div class="col-12 col-md-6 col-lg-5">
                        <div class="card h-100 p-4 p-md-5 border-0 shadow-sm bg-white about-mission-card">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="bg-pink-soft text-primary-accent rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                                    <i class="bi bi-bullseye fs-3"></i>
                                </div>
                                <h3 class="h5 fw-bold text-dark mb-0">Our Mission</h3>
                            </div>
                            <p class="text-muted mb-0">
                                FastBite's mission is to make quality food accessible through a simple and convenient ordering experience. We strive to provide hot, freshly prepared meals with transparent pricing and speedy delivery.
                            </p>
                        </div>
                    </div>

                    <!-- Vision Card -->
                    <div class="col-12 col-md-6 col-lg-5">
                        <div class="card h-100 p-4 p-md-5 border-0 shadow-sm bg-white about-mission-card">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="bg-pink-soft text-primary-accent rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                                    <i class="bi bi-eye fs-3"></i>
                                </div>
                                <h3 class="h5 fw-bold text-dark mb-0">Our Vision</h3>
                            </div>
                            <p class="text-muted mb-0">
                                FastBite aims to become a trusted and convenient food-ordering experience for customers, setting new standards for culinary taste, digital convenience, and reliable customer satisfaction.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. How FastBite Works -->
        <section class="about-process py-5 bg-white border-top border-bottom" aria-label="How FastBite Works">
            <div class="container py-2">
                <div class="text-center mb-5">
                    <span class="menu-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent mb-2">SIMPLE &amp; FAST</span>
                    <h2 class="h2 fw-bold text-dark mb-2">How FastBite Works</h2>
                    <p class="text-muted small mx-auto mb-0" style="max-width: 520px;">
                        Enjoy your favorite meals in four straightforward, hassle-free steps.
                    </p>
                </div>

                <div class="row g-4">
                    <!-- Step 01 -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 p-4 border-0 shadow-sm text-center position-relative about-step-card">
                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small fw-bold position-absolute top-0 end-0 m-3">01</span>
                            <div class="bg-pink-soft text-primary-accent rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                <i class="bi bi-search fs-4"></i>
                            </div>
                            <h3 class="h6 fw-bold text-dark mb-2">01 — Browse</h3>
                            <p class="text-muted small mb-0">Explore our food categories and discover something you love.</p>
                        </div>
                    </div>

                    <!-- Step 02 -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 p-4 border-0 shadow-sm text-center position-relative about-step-card">
                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small fw-bold position-absolute top-0 end-0 m-3">02</span>
                            <div class="bg-pink-soft text-primary-accent rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                <i class="bi bi-cart-check fs-4"></i>
                            </div>
                            <h3 class="h6 fw-bold text-dark mb-2">02 — Choose</h3>
                            <p class="text-muted small mb-0">Select your favorite food and add it to your cart.</p>
                        </div>
                    </div>

                    <!-- Step 03 -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 p-4 border-0 shadow-sm text-center position-relative about-step-card">
                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small fw-bold position-absolute top-0 end-0 m-3">03</span>
                            <div class="bg-pink-soft text-primary-accent rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                <i class="bi bi-credit-card fs-4"></i>
                            </div>
                            <h3 class="h6 fw-bold text-dark mb-2">03 — Checkout</h3>
                            <p class="text-muted small mb-0">Review your order and provide your delivery information.</p>
                        </div>
                    </div>

                    <!-- Step 04 -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 p-4 border-0 shadow-sm text-center position-relative about-step-card">
                            <span class="badge bg-pink-soft text-primary-accent border border-pink-subtle extra-small fw-bold position-absolute top-0 end-0 m-3">04</span>
                            <div class="bg-pink-soft text-primary-accent rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                <i class="bi bi-emoji-smile fs-4"></i>
                            </div>
                            <h3 class="h6 fw-bold text-dark mb-2">04 — Enjoy</h3>
                            <p class="text-muted small mb-0">Relax while your order is prepared and delivered.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. Our Food Categories -->
        <section class="py-5 bg-light" aria-label="Explore Our Categories">
            <div class="container py-2">
                <div class="text-center mb-5">
                    <span class="menu-eyebrow d-inline-block text-uppercase fw-bold text-primary-accent mb-2">OUR MENU</span>
                    <h2 class="h2 fw-bold text-dark mb-2">Explore Our Categories</h2>
                    <p class="text-muted small mx-auto mb-0" style="max-width: 540px;">
                        From oven-baked pizzas to sizzling burgers and cooling beverages, explore all 10 mouth-watering categories.
                    </p>
                </div>

                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3 g-md-4">
                    <?php foreach ($categories as $cat): 
                        $catImg = function_exists('getCategoryImage') ? getCategoryImage($cat['image']) : 'assets/images/placeholders/category-placeholder.jpg';
                    ?>
                        <div class="col">
                            <a href="menu.php#<?= htmlspecialchars($cat['hash'], ENT_QUOTES, 'UTF-8'); ?>" class="card h-100 border-0 shadow-sm text-decoration-none text-center overflow-hidden about-category-card">
                                <div class="about-cat-img-wrap position-relative">
                                    <img src="<?= htmlspecialchars($catImg, ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="<?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?> Category" 
                                         class="card-img-top object-fit-cover" 
                                         style="height: 120px;" 
                                         loading="lazy" 
                                         decoding="async">
                                </div>
                                <div class="card-body p-3">
                                    <h3 class="h6 fw-bold text-dark mb-0 about-cat-title"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-4 pt-2">
                    <a href="menu.php" class="btn btn-outline-pink d-inline-flex align-items-center gap-2 px-4 py-2">
                        <span>View Full Menu</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- 7. FastBite Highlights -->
        <section class="py-5 bg-white border-top border-bottom" aria-label="FastBite Highlights">
            <div class="container py-2">
                <div class="row g-4 text-center">
                    <div class="col-6 col-md-3">
                        <div class="p-3">
                            <span class="display-6 fw-bold text-primary-accent d-block mb-1">40+</span>
                            <span class="text-dark fw-semibold small d-block">Food Items</span>
                            <span class="text-muted extra-small">Across diverse cuisines</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3">
                            <span class="display-6 fw-bold text-primary-accent d-block mb-1">10</span>
                            <span class="text-dark fw-semibold small d-block">Categories</span>
                            <span class="text-muted extra-small">Pizza, burgers, drinks &amp; more</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3">
                            <span class="display-6 fw-bold text-primary-accent d-block mb-1">4</span>
                            <span class="text-dark fw-semibold small d-block">Easy Ordering Steps</span>
                            <span class="text-muted extra-small">Browse, choose, checkout, enjoy</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3">
                            <span class="display-6 fw-bold text-primary-accent d-block mb-1">24/7</span>
                            <span class="text-dark fw-semibold small d-block">Online Ordering</span>
                            <span class="text-muted extra-small">Always open for your orders</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. Customer Experience CTA Section -->
        <section class="about-cta py-5 bg-pink-soft border-top position-relative overflow-hidden text-center" aria-label="Order Food Call to Action">
            <div class="container py-3 position-relative" style="z-index: 2; max-width: 680px;">
                <span class="badge bg-white text-primary-accent border border-pink-subtle extra-small fw-bold mb-3 px-3 py-2 shadow-sm">
                    <i class="bi bi-fire text-danger me-1"></i>TASTE THE DIFFERENCE
                </span>
                <h2 class="h2 fw-bold text-dark mb-3">Ready to Order Your Favorite Food?</h2>
                <p class="text-muted mb-4 lead" style="font-size: 1.05rem;">
                    Discover delicious meals, choose your favorites, and place your order in just a few simple steps.
                </p>
                <div class="d-flex flex-wrap align-items-center justify-content-center gap-3">
                    <a href="menu.php" class="btn btn-auth-primary d-inline-flex align-items-center gap-2 shadow-sm px-4 py-2">
                        <i class="bi bi-grid-fill"></i>
                        <span>Browse Menu</span>
                    </a>
                    <a href="contact.php" class="btn btn-outline-pink d-inline-flex align-items-center gap-2 px-4 py-2 bg-white">
                        <i class="bi bi-envelope-fill"></i>
                        <span>Contact Us</span>
                    </a>
                </div>
            </div>
        </section>

    </main>

    <!-- Main Footer Component -->
    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
