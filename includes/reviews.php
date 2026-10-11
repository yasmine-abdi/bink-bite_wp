<?php
/**
 * FastBite - Customer Reviews Section Component
 * Step 8: Customer Reviews / Testimonials (Frontend-Only Static Demo Data)
 * Design Identity: Pink & White
 */

$reviews = [
    [
        'id' => 'review-amina-hassan',
        'name' => 'Amina Hassan',
        'label' => 'Regular Customer',
        'rating' => 5.0,
        'stars' => 5,
        'review' => 'The food was fresh, delicious, and arrived faster than I expected. The chicken pizza was amazing!',
        'avatar' => 'assets/images/placeholders/avatar-placeholder.jpg',
        'alt' => 'Amina Hassan customer avatar'
    ],
    [
        'id' => 'review-mohamed-ali',
        'name' => 'Mohamed Ali',
        'label' => 'Happy Customer',
        'rating' => 4.9,
        'stars' => 5,
        'review' => 'I really enjoyed the burger. The website was easy to understand and the food quality was excellent.',
        'avatar' => 'assets/images/placeholders/avatar-placeholder.jpg',
        'alt' => 'Mohamed Ali customer avatar'
    ],
    [
        'id' => 'review-sahra-ahmed',
        'name' => 'Sahra Ahmed',
        'label' => 'Regular Customer',
        'rating' => 5.0,
        'stars' => 5,
        'review' => 'The shawarma was delicious and the portion was great. I will definitely order again.',
        'avatar' => 'assets/images/placeholders/avatar-placeholder.jpg',
        'alt' => 'Sahra Ahmed customer avatar'
    ],
    [
        'id' => 'review-abdi-noor',
        'name' => 'Abdi Noor',
        'label' => 'Happy Customer',
        'rating' => 4.8,
        'stars' => 5,
        'review' => 'Great variety of food and drinks. I especially liked the coffee and the fast service.',
        'avatar' => 'assets/images/placeholders/avatar-placeholder.jpg',
        'alt' => 'Abdi Noor customer avatar'
    ],
    [
        'id' => 'review-maryan-yusuf',
        'name' => 'Maryan Yusuf',
        'label' => 'Regular Customer',
        'rating' => 4.9,
        'stars' => 5,
        'review' => 'Everything tasted fresh and the presentation was great. FastBite has become one of my favorite places.',
        'avatar' => 'assets/images/placeholders/avatar-placeholder.jpg',
        'alt' => 'Maryan Yusuf customer avatar'
    ],
    [
        'id' => 'review-ahmed-omar',
        'name' => 'Ahmed Omar',
        'label' => 'Happy Customer',
        'rating' => 5.0,
        'stars' => 5,
        'review' => 'Very good food and a simple ordering experience. The fried chicken was crispy and tasty.',
        'avatar' => 'assets/images/placeholders/avatar-placeholder.jpg',
        'alt' => 'Ahmed Omar customer avatar'
    ]
];
?>
<section class="reviews-section py-5" id="reviews" aria-label="Customer Reviews">
    <div class="container py-2 py-lg-4">
        <!-- Section Header -->
        <div class="reviews-header text-center mb-4 mb-lg-5">
            <span class="reviews-eyebrow d-inline-block">WHAT OUR CUSTOMERS SAY</span>
            <h2 class="reviews-title fw-bold mb-2">Happy Customers</h2>
            <p class="reviews-desc text-muted mb-0">See what our customers say about their favorite meals and ordering experience.</p>
        </div>

        <!-- Reviews Grid (3 on desktop, 2 on tablet, 1 on mobile) -->
        <div class="row g-3 g-md-4 reviews-grid">
            <?php foreach ($reviews as $rev): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="review-card card h-100 p-4" id="<?= htmlspecialchars($rev['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <!-- Header with Avatar & Details & Decorative Quote -->
                        <div class="review-header d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?= htmlspecialchars($rev['avatar'], ENT_QUOTES, 'UTF-8'); ?>" 
                                     alt="<?= htmlspecialchars($rev['alt'], ENT_QUOTES, 'UTF-8'); ?>" 
                                     class="review-avatar" 
                                     loading="lazy">
                                <div class="review-customer">
                                    <h3 class="review-name mb-0"><?= htmlspecialchars($rev['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <span class="review-label text-muted small"><?= htmlspecialchars($rev['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>
                            <div class="review-quote-icon" aria-hidden="true">
                                <i class="bi bi-quote"></i>
                            </div>
                        </div>

                        <!-- Star Rating -->
                        <div class="review-rating d-flex align-items-center gap-2 mb-3">
                            <div class="review-stars d-inline-flex gap-1" aria-label="Rating: <?= htmlspecialchars(number_format($rev['rating'], 1), ENT_QUOTES, 'UTF-8'); ?> out of 5 stars">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="bi bi-star-fill"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="review-score fw-bold"><?= htmlspecialchars(number_format($rev['rating'], 1), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>

                        <!-- Review Text -->
                        <p class="review-text text-muted mb-0 flex-grow-1">
                            &ldquo;<?= htmlspecialchars($rev['review'], ENT_QUOTES, 'UTF-8'); ?>&rdquo;
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
