<?php
/**
 * FastBite - Why Choose Us Section Component
 * Step 6: Why Choose Us Section (Frontend-Only Static Demo Data)
 * Design Identity: Pink & White
 */

$whyFeatures = [
    [
        'id' => 'feature-fast-delivery',
        'icon' => 'bi bi-truck',
        'title' => 'Fast Delivery',
        'description' => 'Get your favorite meals delivered quickly while they\'re still fresh and delicious.'
    ],
    [
        'id' => 'feature-fresh-ingredients',
        'icon' => 'bi bi-leaf',
        'title' => 'Fresh Ingredients',
        'description' => 'We use quality ingredients to prepare fresh and flavorful meals every day.'
    ],
    [
        'id' => 'feature-quality-food',
        'icon' => 'bi bi-award',
        'title' => 'Quality Food',
        'description' => 'Every meal is prepared with care to deliver great taste and consistent quality.'
    ],
    [
        'id' => 'feature-easy-ordering',
        'icon' => 'bi bi-bag-check',
        'title' => 'Easy Ordering',
        'description' => 'Enjoy a simple and convenient way to explore our menu and choose your favorite meals.'
    ],
    [
        'id' => 'feature-great-variety',
        'icon' => 'bi bi-grid',
        'title' => 'Great Variety',
        'description' => 'Choose from burgers, pizza, shawarma, drinks, coffee, chicken, pasta and more.'
    ],
    [
        'id' => 'feature-trusted-service',
        'icon' => 'bi bi-heart',
        'title' => 'Trusted Service',
        'description' => 'We focus on friendly service and a great experience every time you order.'
    ]
];
?>
<section class="why-choose-us-section py-5" id="why-choose-us" aria-label="Why Choose Us">
    <div class="container py-2 py-lg-4">
        <!-- Section Header -->
        <div class="why-header text-center mb-4 mb-lg-5">
            <span class="why-eyebrow d-inline-block">WHY FASTBITE?</span>
            <h2 class="why-title fw-bold mb-2">Why Choose Us?</h2>
            <p class="why-desc text-muted mb-0">Great food, fresh ingredients, and fast service &mdash; everything you need for a better meal.</p>
        </div>

        <!-- Features Grid (3 on desktop, 2 on tablet, 1 on mobile) -->
        <div class="row g-3 g-md-4 why-features-grid">
            <?php foreach ($whyFeatures as $feature): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="why-feature-card card h-100 text-center p-4 p-lg-4" id="<?= htmlspecialchars($feature['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="why-icon-box mx-auto mb-3">
                            <i class="<?= htmlspecialchars($feature['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                        </div>
                        <h3 class="why-feature-title mb-2"><?= htmlspecialchars($feature['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="why-feature-desc text-muted mb-0"><?= htmlspecialchars($feature['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
