<?php
/**
 * FastBite - Categories Section Component
 * Step 4: Categories Section (Frontend-Only Static Demo Data)
 */

$categories = [
    [
        'id' => 'cat-pizza',
        'name' => 'Pizza',
        'description' => 'Freshly baked pizzas with delicious toppings.',
        'image' => function_exists('getCategoryImage') ? getCategoryImage('pizza.jpg') : 'assets/images/placeholders/category-placeholder.jpg',
        'alt' => 'Freshly baked pizza category'
    ],
    [
        'id' => 'cat-burgers',
        'name' => 'Burgers',
        'description' => 'Juicy burgers prepared with quality ingredients.',
        'image' => function_exists('getCategoryImage') ? getCategoryImage('burgers.jpg') : 'assets/images/placeholders/category-placeholder.jpg',
        'alt' => 'Juicy gourmet burgers category'
    ],
    [
        'id' => 'cat-shawarma',
        'name' => 'Shawarma',
        'description' => 'Flavorful shawarma made fresh to order.',
        'image' => function_exists('getCategoryImage') ? getCategoryImage('shawarma.jpg') : 'assets/images/placeholders/category-placeholder.jpg',
        'alt' => 'Delicious shawarma wraps category'
    ],
    [
        'id' => 'cat-drinks',
        'name' => 'Drinks',
        'description' => 'Refreshing drinks for every meal.',
        'image' => function_exists('getCategoryImage') ? getCategoryImage('drinks.jpg') : 'assets/images/placeholders/category-placeholder.jpg',
        'alt' => 'Refreshing drinks and beverages category'
    ],
    [
        'id' => 'cat-coffee',
        'name' => 'Coffee',
        'description' => 'Freshly prepared coffee and hot favorites.',
        'image' => function_exists('getCategoryImage') ? getCategoryImage('coffee.jpg') : 'assets/images/placeholders/category-placeholder.jpg',
        'alt' => 'Freshly brewed coffee category'
    ],
    [
        'id' => 'cat-chicken',
        'name' => 'Fried Chicken',
        'description' => 'Crispy, flavorful chicken made fresh.',
        'image' => function_exists('getCategoryImage') ? getCategoryImage('chicken.jpg') : 'assets/images/placeholders/category-placeholder.jpg',
        'alt' => 'Crispy fried chicken category'
    ],
    [
        'id' => 'cat-pasta',
        'name' => 'Pasta',
        'description' => 'Delicious pasta prepared with rich flavors.',
        'image' => function_exists('getCategoryImage') ? getCategoryImage('pasta.jpg') : 'assets/images/placeholders/category-placeholder.jpg',
        'alt' => 'Italian pasta dishes category'
    ],
    [
        'id' => 'cat-sandwiches',
        'name' => 'Sandwiches',
        'description' => 'Fresh sandwiches packed with delicious fillings.',
        'image' => function_exists('getCategoryImage') ? getCategoryImage('sandwiches.jpg') : 'assets/images/placeholders/category-placeholder.jpg',
        'alt' => 'Fresh sandwiches and subs category'
    ],
    [
        'id' => 'cat-fries',
        'name' => 'Fries & Sides',
        'description' => 'Crispy sides and tasty extras.',
        'image' => function_exists('getCategoryImage') ? getCategoryImage('fries.jpg') : 'assets/images/placeholders/category-placeholder.jpg',
        'alt' => 'Crispy fries and tasty sides category'
    ],
    [
        'id' => 'cat-desserts',
        'name' => 'Desserts',
        'description' => 'Sweet treats to finish your meal.',
        'image' => function_exists('getCategoryImage') ? getCategoryImage('desserts.jpg') : 'assets/images/placeholders/category-placeholder.jpg',
        'alt' => 'Delicious sweet desserts category'
    ]
];
?>
<section class="categories-section py-5" id="categories" aria-label="Food Categories">
    <div class="container py-2 py-lg-4">
        <!-- Section Header -->
        <div class="categories-header text-center mb-4 mb-lg-5">
            <span class="categories-eyebrow d-inline-block">EXPLORE OUR MENU</span>
            <h2 class="categories-title fw-bold mb-2">What Are You Craving?</h2>
            <p class="categories-desc text-muted mb-0">Choose from our delicious selection of freshly prepared favorites.</p>
        </div>

        <!-- Categories Grid -->
        <div class="row g-3 g-md-4 categories-grid justify-content-center">
            <?php foreach ($categories as $cat): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="menu.php#<?= htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>" class="category-card card h-100" id="<?= htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="category-image-wrap">
                            <img src="<?= htmlspecialchars($cat['image'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="<?= htmlspecialchars($cat['alt'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 class="category-image" 
                                 loading="lazy">
                        </div>
                        <div class="category-body card-body">
                            <h3 class="category-title"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="category-desc"><?= htmlspecialchars($cat['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="category-footer">
                                <span class="category-explore-text">Explore</span>
                                <span class="category-arrow">
                                    <i class="bi bi-arrow-right"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
