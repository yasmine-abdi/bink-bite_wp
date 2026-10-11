<?php
/**
 * FastBite - Popular Foods Section Component
 * Step 7: Popular Foods Section (Frontend-Only Static Demo Data)
 * 
 * Conceptual Hierarchy: Category -> Type/Subcategory -> Food Item
 * Design System: Pink + White
 */

$popularFoods = [
    [
        'id' => 'pop-double-beef-burger',
        'name' => 'Double Beef Burger',
        'category' => 'Burgers',
        'type' => 'Beef Burger',
        'description' => 'Two juicy beef patties with fresh vegetables, cheese and our special sauce.',
        'price' => 6.50,
        'rating' => 4.9,
        'popularity' => 'Best Seller',
        'image' => function_exists('getFoodImage') ? getFoodImage('double-beef-burger.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Double Beef Burger with two juicy beef patties'
    ],
    [
        'id' => 'pop-pepperoni-pizza',
        'name' => 'Pepperoni Pizza',
        'category' => 'Pizza',
        'type' => 'Pepperoni Pizza',
        'description' => 'Freshly baked pizza topped with pepperoni, mozzarella and rich tomato sauce.',
        'price' => 7.00,
        'rating' => 4.9,
        'popularity' => 'Popular',
        'image' => function_exists('getFoodImage') ? getFoodImage('classic-pepperoni-pizza.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Pepperoni Pizza freshly baked with mozzarella'
    ],
    [
        'id' => 'pop-shawarma-wrap',
        'name' => 'Chicken Shawarma Wrap',
        'category' => 'Shawarma',
        'type' => 'Chicken Shawarma',
        'description' => 'Tender grilled chicken wrapped with fresh vegetables and creamy garlic sauce.',
        'price' => 4.50,
        'rating' => 4.8,
        'popularity' => 'Best Seller',
        'image' => function_exists('getFoodImage') ? getFoodImage('chicken-shawarma.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Chicken Shawarma Wrap with garlic sauce'
    ],
    [
        'id' => 'pop-chicken-bucket',
        'name' => 'Crispy Chicken Bucket',
        'category' => 'Fried Chicken',
        'type' => 'Chicken Bucket',
        'description' => 'Crispy golden chicken prepared with our flavorful signature seasoning.',
        'price' => 8.00,
        'rating' => 4.9,
        'popularity' => 'Popular',
        'image' => function_exists('getFoodImage') ? getFoodImage('crispy-chicken-bucket.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Crispy Chicken Bucket with signature seasoning'
    ],
    [
        'id' => 'pop-iced-coffee',
        'name' => 'Iced Coffee',
        'category' => 'Coffee',
        'type' => 'Cold Coffee',
        'description' => 'Smooth chilled coffee with creamy milk and a refreshing finish.',
        'price' => 3.00,
        'rating' => 4.8,
        'popularity' => 'Popular',
        'image' => function_exists('getFoodImage') ? getFoodImage('iced-coffee.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Smooth Iced Coffee served chilled'
    ],
    [
        'id' => 'pop-strawberry-juice',
        'name' => 'Fresh Strawberry Juice',
        'category' => 'Drinks',
        'type' => 'Fresh Juice',
        'description' => 'Refreshing strawberry juice prepared with fresh ingredients.',
        'price' => 2.50,
        'rating' => 4.7,
        'popularity' => 'Popular',
        'image' => function_exists('getFoodImage') ? getFoodImage('fresh-strawberry-juice.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Fresh Strawberry Juice chilled'
    ],
    [
        'id' => 'pop-creamy-pasta',
        'name' => 'Creamy Chicken Pasta',
        'category' => 'Pasta',
        'type' => 'Chicken Pasta',
        'description' => 'Rich creamy pasta with tender chicken and delicious herbs.',
        'price' => 6.00,
        'rating' => 4.8,
        'popularity' => 'Best Seller',
        'image' => function_exists('getFoodImage') ? getFoodImage('creamy-chicken-penne.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Creamy Chicken Pasta with rich sauce and herbs'
    ],
    [
        'id' => 'pop-cheese-fries',
        'name' => 'Loaded Cheese Fries',
        'category' => 'Fries & Sides',
        'type' => 'Loaded Fries',
        'description' => 'Crispy fries topped with melted cheese and flavorful sauces.',
        'price' => 3.50,
        'rating' => 4.8,
        'popularity' => 'Popular',
        'image' => function_exists('getFoodImage') ? getFoodImage('loaded-cheese-fries.jpg') : 'assets/images/placeholders/food-placeholder.jpg',
        'alt' => 'Loaded Cheese Fries with melted cheese'
    ]
];
?>
<section class="popular-foods-section py-5" id="popular-foods" aria-label="Popular Customer Favorites">
    <div class="container py-2 py-lg-4">
        <!-- Section Header -->
        <div class="popular-foods-header text-center mb-4 mb-lg-5">
            <span class="popular-foods-eyebrow d-inline-block">WHAT CUSTOMERS LOVE</span>
            <h2 class="popular-foods-title fw-bold mb-2">Popular Foods</h2>
            <p class="popular-foods-desc text-muted mb-0">Discover the favorites our customers keep coming back for.</p>
        </div>

        <!-- Popular Food Grid -->
        <div class="row g-3 g-md-4 popular-foods-grid">
            <?php foreach ($popularFoods as $food): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="popular-food-card card h-100" id="<?= htmlspecialchars($food['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <!-- Image Container with Popularity Badge -->
                        <div class="popular-food-image-wrap position-relative">
                            <img src="<?= htmlspecialchars($food['image'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="<?= htmlspecialchars($food['alt'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 class="popular-food-image" 
                                 loading="lazy">
                            
                            <!-- Popularity Badge -->
                            <div class="popular-badge-wrap">
                                <?php if ($food['popularity'] === 'Best Seller'): ?>
                                    <span class="popular-food-badge badge-bestseller">
                                        <i class="bi bi-fire me-1"></i> Best Seller
                                    </span>
                                <?php else: ?>
                                    <span class="popular-food-badge badge-popular">
                                        <i class="bi bi-heart-fill me-1"></i> Popular
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Category & Subcategory Overlay -->
                            <div class="popular-category-overlay">
                                <span class="popular-food-category-tag">
                                    <?= htmlspecialchars($food['category'], ENT_QUOTES, 'UTF-8'); ?> &bull; <?= htmlspecialchars($food['type'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="popular-food-body card-body d-flex flex-column">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <h3 class="popular-food-title mb-0"><?= htmlspecialchars($food['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <div class="popular-food-rating d-inline-flex align-items-center gap-1" title="Rating: <?= htmlspecialchars(number_format($food['rating'], 1), ENT_QUOTES, 'UTF-8'); ?> / 5">
                                    <i class="bi bi-star-fill text-warning"></i>
                                    <span><?= htmlspecialchars(number_format($food['rating'], 1), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>
                            <p class="popular-food-description mb-3 flex-grow-1"><?= htmlspecialchars($food['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            
                            <!-- Price & Add to Cart (UI Only) -->
                            <div class="popular-food-footer d-flex align-items-center justify-content-between pt-2 border-top">
                                <div class="popular-food-price-wrap">
                                    <span class="popular-food-price-label d-block text-muted">Price</span>
                                    <span class="popular-food-price">$<?= htmlspecialchars(number_format($food['price'], 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <button type="button" 
                                        class="btn btn-popular-cart d-inline-flex align-items-center gap-1" 
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
