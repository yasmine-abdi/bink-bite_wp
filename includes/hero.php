<?php
/**
 * FastBite - Hero Section Component
 * Step 3: Hero Section
 */
?>
<section class="hero-section" id="hero" aria-label="Hero Introduction">
    <div class="container py-4 py-lg-5">
        <div class="row align-items-center g-4 g-lg-5">
            <!-- Left Column: Content -->
            <div class="col-lg-6 hero-content-col">
                <div class="hero-content">
                    <!-- Eyebrow Badge -->
                    <div class="hero-badge d-inline-flex align-items-center gap-2 mb-3" id="hero-badge">
                        <i class="bi bi-fire"></i>
                        <span>FRESH • FAST • DELICIOUS</span>
                    </div>

                    <!-- Main Headline -->
                    <h1 class="hero-title mb-3" id="hero-title">
                        Delicious Food, <br class="d-none d-sm-inline">
                        <span class="text-primary-accent">Delivered Fast.</span>
                    </h1>

                    <!-- Supporting Description -->
                    <p class="hero-description mb-4" id="hero-desc">
                        Enjoy freshly prepared burgers, pizza, shawarma, drinks and more &mdash; made with quality ingredients and delivered straight to your door.
                    </p>

                    <!-- Primary & Secondary CTA Buttons -->
                    <div class="hero-actions d-flex flex-wrap align-items-center gap-3 mb-4 mb-lg-5">
                        <a href="menu.php" class="btn btn-hero-primary d-inline-flex align-items-center gap-2" id="hero-order-btn" title="Order Now from our Menu">
                            <span>Order Now</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                        <a href="menu.php" class="btn btn-hero-secondary d-inline-flex align-items-center gap-2" id="hero-menu-btn" title="Explore Food Menu">
                            <span>Explore Menu</span>
                        </a>
                    </div>

                    <!-- Trust / Quality Features -->
                    <div class="hero-features d-flex flex-wrap align-items-center gap-3 gap-sm-4 pt-3 border-top">
                        <div class="hero-feature-item d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Fresh Ingredients</span>
                        </div>
                        <div class="hero-feature-item d-flex align-items-center gap-2">
                            <i class="bi bi-lightning-charge-fill text-warning"></i>
                            <span>Fast Delivery</span>
                        </div>
                        <div class="hero-feature-item d-flex align-items-center gap-2">
                            <i class="bi bi-star-fill text-warning"></i>
                            <span>Quality Food</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Hero Image -->
            <div class="col-lg-6 hero-image-col">
                <div class="hero-image-wrapper">
                    <img src="<?= htmlspecialchars(function_exists('getBannerImage') ? getBannerImage('home-banner.jpg') : 'assets/images/placeholders/banner-placeholder.jpg', ENT_QUOTES, 'UTF-8'); ?>" 
                         alt="Delicious Fresh Fast Food - FastBite" 
                         class="img-fluid hero-image" 
                         id="hero-banner-img"
                         loading="eager"
                         decoding="async">
                </div>
            </div>
        </div>
    </div>
</section>
