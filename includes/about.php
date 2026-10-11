<?php
/**
 * FastBite - About Us Section Component
 * Step 9: About Us Section (Frontend-Only Static Demo Data)
 * Design Identity: Pink & White
 */

$aboutHighlights = [
    [
        'id' => 'highlight-freshly-prepared',
        'icon' => 'bi bi-stars',
        'title' => 'Freshly Prepared',
        'description' => 'Meals prepared with care using quality ingredients.'
    ],
    [
        'id' => 'highlight-made-for-everyone',
        'icon' => 'bi bi-people',
        'title' => 'Made for Everyone',
        'description' => 'A variety of favorites for different tastes and cravings.'
    ],
    [
        'id' => 'highlight-customer-first',
        'icon' => 'bi bi-heart',
        'title' => 'Customer First',
        'description' => 'We focus on quality, service, and a better dining experience.'
    ]
];
?>
<section class="about-section py-5" id="about" aria-label="About FastBite">
    <div class="container py-2 py-lg-4">
        <div class="row align-items-center g-4 g-lg-5 about-wrapper">
            <!-- Left Column: About Image with Decorative Accent -->
            <div class="col-lg-6 about-image-col">
                <div class="about-image-wrapper position-relative">
                    <div class="about-image-card">
                        <img src="<?= htmlspecialchars(function_exists('getAboutImage') ? getAboutImage('about-banner.jpg') : 'assets/images/placeholders/about-placeholder.jpg', ENT_QUOTES, 'UTF-8'); ?>" 
                             alt="FastBite Fresh Food Quality and Kitchen Story" 
                             class="about-image" 
                             loading="lazy"
                             decoding="async">
                    </div>
                    <!-- Decorative Pink Floating Badge -->
                    <div class="about-floating-badge d-inline-flex align-items-center gap-2">
                        <i class="bi bi-fire"></i>
                        <span>Quality &amp; Taste First</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: About Content -->
            <div class="col-lg-6 about-content">
                <span class="about-eyebrow d-inline-block">ABOUT FASTBITE</span>
                <h2 class="about-title fw-bold mb-3">Good Food. Great Moments.</h2>
                
                <p class="about-description lead-desc mb-3">
                    At FastBite, we believe great food should be fresh, flavorful, affordable, and easy to enjoy. We prepare your favorite meals with quality ingredients and serve them with care.
                </p>
                
                <p class="about-description text-muted mb-4">
                    From juicy burgers and freshly baked pizza to flavorful shawarma, refreshing drinks, coffee, chicken, pasta, and more, our goal is to make every meal a satisfying experience.
                </p>

                <!-- 3 Highlights -->
                <div class="about-highlights row g-3 mb-4">
                    <?php foreach ($aboutHighlights as $hl): ?>
                        <div class="col-12 col-sm-4">
                            <div class="about-highlight card h-100 p-3" id="<?= htmlspecialchars($hl['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="about-highlight-icon mb-2">
                                    <i class="<?= htmlspecialchars($hl['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                                </div>
                                <h3 class="about-highlight-title mb-1"><?= htmlspecialchars($hl['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="about-highlight-text text-muted mb-0 small"><?= htmlspecialchars($hl['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Trust / Value Statement Badges -->
                <div class="about-trust-row d-flex flex-wrap align-items-center gap-2 pt-3 border-top">
                    <span class="about-trust-tag"><i class="bi bi-check-circle-fill text-success me-1"></i> Fresh</span>
                    <span class="about-trust-tag"><i class="bi bi-award-fill text-warning me-1"></i> Quality</span>
                    <span class="about-trust-tag"><i class="bi bi-lightning-charge-fill text-danger me-1"></i> Fast Service</span>
                </div>
            </div>
        </div>
    </div>
</section>
