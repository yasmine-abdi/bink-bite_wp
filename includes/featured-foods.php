<?php
/**
 * FastBite - Featured Foods Section Component
 * Step 5: Featured Foods Section (Frontend-Only Static Demo Data)
 * 
 * Conceptual Hierarchy: Category -> Type/Subcategory -> Food Item
 */

$featuredFoods = [
    [
        'id' => 'food-chicken-pizza',
        'name' => 'Chicken Pizza',
        'category' => 'Pizza',
        'type' => 'Chicken Pizza',
        'description' => 'Freshly baked pizza topped with tender chicken and melted cheese.',
        'price' => 6.00,
        'rating' => 4.8,
        'image' => function_exists('getFoodImage') ? getFoodImage('chicken-supreme-pizza.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Chicken Pizza freshly baked with tender chicken'
    ],
    [
        'id' => 'food-beef-burger',
        'name' => 'Classic Beef Burger',
        'category' => 'Burgers',
        'type' => 'Beef Burger',
        'description' => 'Juicy grilled beef burger with fresh vegetables and special sauce.',
        'price' => 5.00,
        'rating' => 4.9,
        'image' => function_exists('getFoodImage') ? getFoodImage('classic-beef-burger.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Classic Beef Burger with grilled patty and fresh vegetables'
    ],
    [
        'id' => 'food-chicken-shawarma',
        'name' => 'Chicken Shawarma',
        'category' => 'Shawarma',
        'type' => 'Chicken Shawarma',
        'description' => 'Tender chicken wrapped with fresh vegetables and creamy sauce.',
        'price' => 4.00,
        'rating' => 4.8,
        'image' => function_exists('getFoodImage') ? getFoodImage('chicken-shawarma.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Chicken Shawarma wrap with tender chicken'
    ],
    [
        'id' => 'food-mango-juice',
        'name' => 'Fresh Mango Juice',
        'category' => 'Drinks',
        'type' => 'Fresh Juice',
        'description' => 'Refreshing mango juice prepared from fresh ingredients.',
        'price' => 2.50,
        'rating' => 4.7,
        'image' => function_exists('getFoodImage') ? getFoodImage('fresh-mango-juice.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Fresh Mango Juice served chilled'
    ],
    [
        'id' => 'food-cappuccino',
        'name' => 'Cappuccino',
        'category' => 'Coffee',
        'type' => 'Cappuccino',
        'description' => 'Smooth espresso topped with rich steamed milk foam.',
        'price' => 2.50,
        'rating' => 4.8,
        'image' => function_exists('getFoodImage') ? getFoodImage('cappuccino.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Hot Cappuccino with milk foam'
    ],
    [
        'id' => 'food-fried-chicken',
        'name' => 'Crispy Fried Chicken',
        'category' => 'Fried Chicken',
        'type' => 'Fried Chicken',
        'description' => 'Crispy golden chicken with a flavorful seasoned coating.',
        'price' => 5.50,
        'rating' => 4.9,
        'image' => function_exists('getFoodImage') ? getFoodImage('crispy-chicken-bucket.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Crispy Fried Chicken golden brown'
    ],
    [
        'id' => 'food-chicken-pasta',
        'name' => 'Creamy Chicken Pasta',
        'category' => 'Pasta',
        'type' => 'Chicken Pasta',
        'description' => 'Creamy pasta prepared with tender chicken and rich sauce.',
        'price' => 5.50,
        'rating' => 4.7,
        'image' => function_exists('getFoodImage') ? getFoodImage('creamy-chicken-penne.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Creamy Chicken Pasta with rich sauce'
    ],
    [
        'id' => 'food-loaded-fries',
        'name' => 'Loaded French Fries',
        'category' => 'Fries & Sides',
        'type' => 'Loaded Fries',
        'description' => 'Crispy fries topped with delicious sauces and fresh ingredients.',
        'price' => 3.00,
        'rating' => 4.6,
        'image' => function_exists('getFoodImage') ? getFoodImage('loaded-cheese-fries.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Loaded French Fries with toppings'
    ]
];
?>
<section class="featured-foods-section py-5" id="featured-foods" aria-label="Featured Foods">
    <div class="container py-2 py-lg-4">
        <!-- Section Header -->
        <div class="featured-foods-header text-center mb-4 mb-lg-5">
            <span class="featured-foods-eyebrow d-inline-block">OUR SPECIALS</span>
            <h2 class="featured-foods-title fw-bold mb-2">Featured Favorites</h2>
            <p class="featured-foods-desc text-muted mb-0">Discover some of our most-loved dishes, freshly prepared just for you.</p>
        </div>

        <!-- Food Grid -->
        <div class="row g-3 g-md-4 featured-foods-grid">
            <?php foreach ($featuredFoods as $food): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="food-card card h-100" id="<?= htmlspecialchars($food['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <!-- Image & Category Badge -->
                        <div class="food-image-wrap position-relative">
                            <img src="<?= htmlspecialchars($food['image'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="<?= htmlspecialchars($food['alt'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 class="food-image" 
                                 loading="lazy">
                            <div class="food-badge-wrapper">
                                <span class="food-category-badge">
                                    <?= htmlspecialchars($food['category'], ENT_QUOTES, 'UTF-8'); ?> &bull; <?= htmlspecialchars($food['type'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="food-body card-body d-flex flex-column">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <h3 class="food-title mb-0"><?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <div class="food-rating d-inline-flex align-items-center gap-1" title="Rating: <?= htmlspecialchars(number_format($food['rating'], 1), ENT_QUOTES, 'UTF-8'); ?> / 5">
                                    <i class="bi bi-star-fill text-warning"></i>
                                    <span><?= htmlspecialchars(number_format($food['rating'], 1), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>
                            <p class="food-description mb-3 flex-grow-1"><?= htmlspecialchars($food['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            
                            <!-- Price & Add to Cart (UI Only) -->
                            <div class="food-footer d-flex align-items-center justify-content-between pt-2 border-top">
                                <div class="food-price-wrap">
                                    <span class="food-price-label d-block text-muted">Price</span>
                                    <span class="food-price">$<?= htmlspecialchars(number_format($food['price'], 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <button type="button" 
                                        class="btn btn-food-cart d-inline-flex align-items-center gap-1" 
                                        aria-label="Add <?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?> to cart" 
                                        title="Add to cart (Demo UI)">
                                    <i class="bi bi-bag-plus"></i>
                                    <span>Add</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
