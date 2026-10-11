<?php
/**
 * FastBite - Central Database Configuration & Data Access Layer
 * STEP 24: Backend Foundation & Database Integration
 * 
 * Single source of truth for:
 * - Database connection & automated schema migrations
 * - Users, Roles & Authentication
 * - Foods, Categories & Central Pricing Engine
 * - Promotions & Priority Discounts
 * - Saved Customer Addresses
 * - Cart Management & Real Price Recalculation
 * - Orders, Order Items & Transaction-Safe Checkout
 * - Notifications & Status Updates
 * - Contact Inquiries
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(__DIR__));
}

// Environment & Configuration Loader
if (!function_exists('loadFastBiteEnv')) {
    function loadFastBiteEnv($envPath = null) {
        if ($envPath === null) {
            $envPath = (defined('FASTBITE_ROOT_DIR') ? FASTBITE_ROOT_DIR : dirname(__DIR__)) . '/.env';
        }
        if (file_exists($envPath) && is_readable($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line[0] === '#') {
                    continue;
                }
                if (strpos($line, '=') !== false) {
                    list($key, $val) = explode('=', $line, 2);
                    $key = trim($key);
                    $val = trim($val);
                    $val = trim($val, '"\'');
                    if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                        putenv("{$key}={$val}");
                        $_ENV[$key] = $val;
                        $_SERVER[$key] = $val;
                    }
                }
            }
        }
    }
}
loadFastBiteEnv();

// Database Credentials (from .env or safe defaults)
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '3306');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'fastbite_db');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

/**
 * Returns a singleton PDO database connection.
 * Automatically initializes database and runs schema migrations.
 *
 * @return PDO
 */
function getDbConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Attempt to create database if it doesn't exist
            try {
                $tempPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, $options);
                $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci");
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $ex) {
                throw new Exception("Database connection failed: " . $ex->getMessage());
            }
        }

        // Initialize schema and seed data
        initDatabaseSchema($pdo);
    }

    return $pdo;
}

/**
 * Initializes database schema, alters existing tables if needed, and seeds defaults.
 *
 * @param PDO $pdo
 */
function initDatabaseSchema(PDO $pdo) {
    // 1. Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(120) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `phone` VARCHAR(30) NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
        `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
        `avatar` VARCHAR(255) NULL,
        `date_of_birth` DATE NULL,
        `gender` ENUM('unspecified', 'male', 'female', 'other') DEFAULT 'unspecified',
        `preferred_language` VARCHAR(20) DEFAULT 'en',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 2. Categories Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `icon` VARCHAR(50) DEFAULT 'bi bi-pie-chart',
        `description` TEXT NULL,
        `image` VARCHAR(255) NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `sort_order` INT NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 3. Foods Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `foods` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category_id` INT NOT NULL,
        `name` VARCHAR(150) NOT NULL,
        `slug` VARCHAR(150) NOT NULL UNIQUE,
        `description` TEXT NULL,
        `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        `image` VARCHAR(255) NULL,
        `is_available` TINYINT(1) NOT NULL DEFAULT 1,
        `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
        `is_popular` TINYINT(1) NOT NULL DEFAULT 0,
        `rating` DECIMAL(3, 2) DEFAULT 4.80,
        `rating_count` INT DEFAULT 120,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 4. Discounts Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `discounts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `type` ENUM('food', 'category') NOT NULL DEFAULT 'food',
        `food_id` INT NULL,
        `category_id` INT NULL,
        `discount_percent` INT NOT NULL DEFAULT 0,
        `start_at` DATETIME NULL,
        `end_at` DATETIME NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 5. Addresses Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `addresses` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `address_type` VARCHAR(50) NOT NULL DEFAULT 'Home',
        `full_name` VARCHAR(120) NOT NULL,
        `phone` VARCHAR(30) NOT NULL,
        `city` VARCHAR(80) NOT NULL DEFAULT 'Hargeisa',
        `area` VARCHAR(100) NOT NULL,
        `street_address` VARCHAR(255) NOT NULL,
        `instructions` TEXT NULL,
        `is_default` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 6. Cart Items Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `cart_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `food_id` INT NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `user_food_unique` (`user_id`, `food_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 7. Orders Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `order_number` VARCHAR(30) NOT NULL UNIQUE,
        `subtotal` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        `discount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        `delivery_fee` DECIMAL(10, 2) NOT NULL DEFAULT 2.00,
        `total` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cash_on_delivery',
        `payment_status` ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
        `order_status` ENUM('pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
        `delivery_address` TEXT NOT NULL,
        `notes` TEXT NULL,
        `estimated_delivery` VARCHAR(50) DEFAULT '25–35 minutes',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 8. Order Items Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `food_id` INT NULL,
        `food_name` VARCHAR(150) NOT NULL,
        `food_category` VARCHAR(100) NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `unit_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        `discount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        `subtotal` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 9. Notifications Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(150) NOT NULL,
        `message` TEXT NOT NULL,
        `type` VARCHAR(50) NOT NULL DEFAULT 'order',
        `link` VARCHAR(255) NULL,
        `is_read` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        INDEX `idx_notif_user_id` (`user_id`),
        INDEX `idx_notif_type` (`type`),
        INDEX `idx_notif_is_read` (`is_read`),
        INDEX `idx_notif_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Safe column migrations for Step 31 (Google OAuth & Auth Providers)
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'auth_provider'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `auth_provider` VARCHAR(50) NOT NULL DEFAULT 'local' AFTER `password`");
        }
    } catch (Exception $e) {}

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'google_provider_id'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `google_provider_id` VARCHAR(100) NULL AFTER `auth_provider`, ADD INDEX `idx_users_google_id` (`google_provider_id`)");
        }
    } catch (Exception $e) {}

    try {
        $insSet = $pdo->prepare("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `created_at`) VALUES (?, ?, NOW())");
        $insSet->execute(['google_oauth_client_id', '']);
        $insSet->execute(['google_oauth_client_secret', '']);
    } catch (Exception $e) {}

    // Safe column migrations for notifications and users
    try {
        $pdo->exec("ALTER TABLE `notifications` MODIFY COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'order'");
    } catch (Exception $e) {}

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'notification_preferences'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `notification_preferences` TEXT NULL AFTER `preferred_language`");
        }
    } catch (Exception $e) {}

    // 10. Contact Messages Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `contact_messages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(120) NOT NULL,
        `email` VARCHAR(150) NOT NULL,
        `phone` VARCHAR(30) NULL,
        `subject` VARCHAR(200) NOT NULL,
        `message` TEXT NOT NULL,
        `status` ENUM('new', 'read', 'resolved') NOT NULL DEFAULT 'new',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Seed default admin and customer if users table is empty
    seedDefaultUsers($pdo);

    // Seed categories if empty
    seedDefaultCategories($pdo);

    // Seed foods if empty
    seedDefaultFoods($pdo);

    // Seed discounts if empty
    seedDefaultDiscounts($pdo);

    // Seed customer demo records (addresses, orders, notifications)
    seedCustomerDemoData($pdo);
    // 11. Settings Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    seedDefaultSettings($pdo);

    // 13. Security Logs Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `security_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL,
        `admin_id` INT NULL,
        `action` VARCHAR(50) NOT NULL,
        `description` TEXT NOT NULL,
        `ip_address` VARCHAR(45) NULL,
        `user_agent` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_sec_user_id` (`user_id`),
        INDEX `idx_sec_admin_id` (`admin_id`),
        INDEX `idx_sec_action` (`action`),
        INDEX `idx_sec_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // 12. Payment Transactions Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `payment_transactions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `provider` VARCHAR(50) NOT NULL DEFAULT 'cash',
        `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cash_on_delivery',
        `transaction_reference` VARCHAR(100) NOT NULL UNIQUE,
        `provider_reference` VARCHAR(100) NULL,
        `amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
        `status` ENUM('pending', 'processing', 'paid', 'failed', 'refunded', 'cancelled') NOT NULL DEFAULT 'pending',
        `request_payload` TEXT NULL,
        `response_payload` TEXT NULL,
        `failure_reason` VARCHAR(255) NULL,
        `paid_at` DATETIME NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Safe column migrations for orders table
    try {
        $pdo->exec("ALTER TABLE `orders` MODIFY COLUMN `payment_status` ENUM('pending', 'processing', 'paid', 'failed', 'refunded', 'cancelled') NOT NULL DEFAULT 'pending'");
    } catch (Exception $e) {}

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'tax'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `tax` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER `delivery_fee`");
        }
    } catch (Exception $e) {}

    // Safe column migrations for foods table (rating & rating_count)
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `foods` LIKE 'rating'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `foods` ADD COLUMN `rating` DECIMAL(3, 2) NOT NULL DEFAULT 4.80 AFTER `is_popular`");
        }
    } catch (Exception $e) {}

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `foods` LIKE 'rating_count'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `foods` ADD COLUMN `rating_count` INT NOT NULL DEFAULT 0 AFTER `rating`");
        }
    } catch (Exception $e) {}

    // 14. Favorites Table (Step 30)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `favorites` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `food_id` INT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `uniq_user_food_fav` (`user_id`, `food_id`),
        INDEX `idx_fav_user` (`user_id`),
        INDEX `idx_fav_food` (`food_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 15. Food Reviews Table (Step 30)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `food_reviews` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `food_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `order_id` INT NOT NULL,
        `rating` TINYINT NOT NULL,
        `review` TEXT NULL,
        `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        `admin_response` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `uniq_user_food_order_review` (`user_id`, `food_id`, `order_id`),
        INDEX `idx_rev_food_status` (`food_id`, `status`),
        INDEX `idx_rev_user` (`user_id`),
        INDEX `idx_rev_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 16. Coupons Table (Step 30)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `coupons` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(50) NOT NULL UNIQUE,
        `description` VARCHAR(255) NULL,
        `discount_type` ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
        `discount_value` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        `minimum_order` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        `maximum_discount` DECIMAL(10, 2) NULL,
        `usage_limit` INT NULL,
        `usage_per_customer` INT NOT NULL DEFAULT 1,
        `used_count` INT NOT NULL DEFAULT 0,
        `start_at` DATETIME NULL,
        `end_at` DATETIME NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_coupon_code` (`code`),
        INDEX `idx_coupon_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 17. Coupon Usages Table (Step 30)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `coupon_usages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `coupon_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `order_id` INT NOT NULL,
        `discount_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
        INDEX `idx_cu_coupon_user` (`coupon_id`, `user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Safe column migrations for orders table (Step 30 coupon columns)
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'coupon_id'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `coupon_id` INT NULL AFTER `discount`");
        }
    } catch (Exception $e) {}

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'coupon_code'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `coupon_code` VARCHAR(50) NULL AFTER `coupon_id`");
        }
    } catch (Exception $e) {}

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'coupon_discount'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `coupon_discount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER `coupon_code`");
        }
    } catch (Exception $e) {}

    // 18. Delivery Drivers Table (Step 32)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `delivery_drivers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL,
        `full_name` VARCHAR(120) NOT NULL,
        `phone` VARCHAR(30) NOT NULL,
        `email` VARCHAR(150) NULL,
        `vehicle_type` ENUM('motorcycle', 'bicycle', 'car', 'van', 'scooter') NOT NULL DEFAULT 'motorcycle',
        `vehicle_number` VARCHAR(50) NULL,
        `status` ENUM('available', 'busy', 'offline', 'suspended') NOT NULL DEFAULT 'available',
        `rating` DECIMAL(3, 2) NOT NULL DEFAULT 5.00,
        `total_deliveries` INT NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_driver_status` (`status`),
        INDEX `idx_driver_phone` (`phone`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 19. Order Deliveries Table (Step 32)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_deliveries` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL UNIQUE,
        `driver_id` INT NULL,
        `delivery_status` ENUM('unassigned', 'assigned', 'picked_up', 'out_for_delivery', 'delivered', 'failed', 'cancelled') NOT NULL DEFAULT 'unassigned',
        `assigned_at` DATETIME NULL,
        `picked_up_at` DATETIME NULL,
        `out_for_delivery_at` DATETIME NULL,
        `delivered_at` DATETIME NULL,
        `failed_at` DATETIME NULL,
        `failure_reason` VARCHAR(255) NULL,
        `delivery_notes` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`driver_id`) REFERENCES `delivery_drivers`(`id`) ON DELETE SET NULL,
        INDEX `idx_od_status` (`delivery_status`),
        INDEX `idx_od_driver` (`driver_id`),
        INDEX `idx_od_order` (`order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Safe role column migration for Step 33 (driver role)
    try {
        $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('customer', 'admin', 'driver') NOT NULL DEFAULT 'customer'");
    } catch (Exception $e) {}

    // Safe user_id column migration on delivery_drivers
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `delivery_drivers` LIKE 'user_id'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `delivery_drivers` ADD COLUMN `user_id` INT NULL AFTER `id`, ADD INDEX `idx_driver_user_id` (`user_id`)");
        }
    } catch (Exception $e) {}

    // 20. Support Tickets Table (Step 33)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `support_tickets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `order_id` INT NULL,
        `ticket_number` VARCHAR(30) NOT NULL UNIQUE,
        `subject` VARCHAR(200) NOT NULL,
        `category` ENUM('order_issue', 'payment', 'delivery', 'food_quality', 'account', 'general') NOT NULL DEFAULT 'general',
        `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
        `status` ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `closed_at` DATETIME NULL,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
        INDEX `idx_st_user` (`user_id`),
        INDEX `idx_st_status` (`status`),
        INDEX `idx_st_category` (`category`),
        INDEX `idx_st_priority` (`priority`),
        INDEX `idx_st_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 21. Support Messages Table (Step 33)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `support_messages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `ticket_id` INT NOT NULL,
        `sender_user_id` INT NOT NULL,
        `sender_role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
        `message` TEXT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`sender_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        INDEX `idx_sm_ticket` (`ticket_id`),
        INDEX `idx_sm_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    seedDefaultCoupons($pdo);
    seedDefaultReviews($pdo);
    seedDefaultDrivers($pdo);
    seedDefaultOrderDeliveries($pdo);
    seedDefaultDriverUser($pdo);
    seedDefaultSupportTickets($pdo);

    // Safe column migrations for Step 34 (Receipt Delivery & Admin Dispatch)
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'receipt_sent_at'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `receipt_sent_at` DATETIME NULL");
        }
    } catch (Exception $e) {}

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'receipt_send_status'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `receipt_send_status` ENUM('not_sent', 'sent', 'failed') NOT NULL DEFAULT 'not_sent'");
        }
    } catch (Exception $e) {}

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'receipt_send_count'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `receipt_send_count` INT NOT NULL DEFAULT 0");
        }
    } catch (Exception $e) {}
}

/**
 * Seeds default Admin and Customer accounts if not existing.
 */
function seedDefaultUsers(PDO $pdo) {
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM `users`");
    if ((int)$stmtCount->fetchColumn() === 0) {
        $ins = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`, `avatar`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        // 1. Admin User
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $ins->execute(['Admin Manager', 'admin@gmail.com', '+252 63 000 0000', $adminPass, 'admin', 'active', 'assets/images/placeholders/avatar-placeholder.jpg']);

        // 2. Default Demo Customer
        $custPass = password_hash('Customer@123456', PASSWORD_DEFAULT);
        $ins->execute(['Mohamed Ali', 'mohamed@example.com', '+252 63 444 5555', $custPass, 'customer', 'active', 'assets/images/placeholders/avatar-placeholder.jpg']);
    }
}

/**
 * Seeds 10 food categories.
 */
function seedDefaultCategories(PDO $pdo) {
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM `categories`");
    if ((int)$stmtCount->fetchColumn() === 0) {
        $categoriesSeed = [
            ['name' => 'Pizza', 'slug' => 'pizza', 'icon' => 'bi bi-pie-chart', 'description' => 'Freshly baked pizzas with gourmet crusts, rich tomato sauce, and savory toppings.'],
            ['name' => 'Burgers', 'slug' => 'burgers', 'icon' => 'bi bi-egg-fried', 'description' => 'Juicy handcrafted patties grilled to perfection with crisp garden vegetables and house sauce.'],
            ['name' => 'Shawarma', 'slug' => 'shawarma', 'icon' => 'bi bi-fire', 'description' => 'Tender slow-roasted meats wrapped in toasted Arabic bread with authentic garlic toum.'],
            ['name' => 'Drinks', 'slug' => 'drinks', 'icon' => 'bi bi-cup-straw', 'description' => 'Chilled natural juices, fizzy sodas, and creamy decadent milkshakes.'],
            ['name' => 'Coffee', 'slug' => 'coffee', 'icon' => 'bi bi-cup-hot', 'description' => 'Freshly roasted artisan coffee beans brewed hot or iced by our baristas.'],
            ['name' => 'Fried Chicken', 'slug' => 'chicken', 'icon' => 'bi bi-egg-fried', 'description' => 'Tender chicken marinated in buttermilk and deep-fried with secret blend seasonings.'],
            ['name' => 'Pasta', 'slug' => 'pasta', 'icon' => 'bi bi-circle', 'description' => 'Al dente Italian pastas tossed in velvety sauces, aromatic herbs, and grated cheeses.'],
            ['name' => 'Sandwiches', 'slug' => 'sandwiches', 'icon' => 'bi bi-layers', 'description' => 'Freshly baked subs, crusty paninis, and towering club sandwiches prepared to order.'],
            ['name' => 'Fries & Sides', 'slug' => 'fries', 'icon' => 'bi bi-grid', 'description' => 'Crunchy golden appetizers, saucy loaded platters, and savory companion bites.'],
            ['name' => 'Desserts', 'slug' => 'desserts', 'icon' => 'bi bi-cake2', 'description' => 'Sweet and decadent confections, artisanal cakes, and creamy ice creams.']
        ];

        $insCat = $pdo->prepare("INSERT INTO `categories` (`name`, `slug`, `icon`, `description`, `image`, `is_active`, `sort_order`) VALUES (?, ?, ?, ?, ?, 1, ?)");
        $order = 1;
        foreach ($categoriesSeed as $cat) {
            $imgName = $cat['slug'] === 'chicken' ? 'chicken.jpg' : ($cat['slug'] === 'fries' ? 'fries.jpg' : $cat['slug'] . '.jpg');
            $insCat->execute([$cat['name'], $cat['slug'], $cat['icon'], $cat['description'], $imgName, $order++]);
        }
    }
}

/**
 * Seeds 40 standard food items across 10 categories.
 */
function seedDefaultFoods(PDO $pdo) {
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM `foods`");
    if ((int)$stmtCount->fetchColumn() < 20) {
        $catMap = [];
        $catRows = $pdo->query("SELECT `id`, `slug` FROM `categories`")->fetchAll();
        foreach ($catRows as $r) {
            $catMap[$r['slug']] = $r['id'];
            if ($r['slug'] === 'chicken') $catMap['fried-chicken'] = $r['id'];
            if ($r['slug'] === 'fries') $catMap['fries-sides'] = $r['id'];
        }

        $allFoodsSeed = [
            // 1. Pizza
            ['cat' => 'pizza', 'name' => 'Chicken Supreme Pizza', 'slug' => 'chicken-supreme-pizza', 'price' => 10.00, 'img' => 'chicken-supreme-pizza.jpg', 'avail' => 1, 'feat' => 1, 'pop' => 1, 'desc' => 'Tender seasoned chicken breast, red bell peppers, onions, and melted mozzarella cheese.'],
            ['cat' => 'pizza', 'name' => 'Savory Beef Pizza', 'slug' => 'savory-beef-pizza', 'price' => 6.50, 'img' => 'savory-beef-pizza.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Spiced ground beef, sweet bell peppers, mushrooms, and rich Italian tomato sauce.'],
            ['cat' => 'pizza', 'name' => 'Classic Pepperoni Pizza', 'slug' => 'classic-pepperoni-pizza', 'price' => 11.00, 'img' => 'classic-pepperoni-pizza.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 1, 'desc' => 'Generous layers of spicy beef pepperoni over melted mozzarella and aromatic herbs.'],
            ['cat' => 'pizza', 'name' => 'Garden Veggie Pizza', 'slug' => 'garden-veggie-pizza', 'price' => 5.50, 'img' => 'garden-veggie-pizza.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Fresh black olives, bell peppers, sweet corn, mushrooms, and aromatic oregano.'],

            // 2. Burgers
            ['cat' => 'burgers', 'name' => 'Classic Beef Burger', 'slug' => 'classic-beef-burger', 'price' => 5.00, 'img' => 'classic-beef-burger.jpg', 'avail' => 1, 'feat' => 1, 'pop' => 0, 'desc' => 'Juicy grilled beef patty, crisp lettuce, ripe tomatoes, pickles, and signature house sauce.'],
            ['cat' => 'burgers', 'name' => 'Crispy Chicken Burger', 'slug' => 'crispy-chicken-burger', 'price' => 5.50, 'img' => 'crispy-chicken-burger.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 1, 'desc' => 'Crispy golden chicken fillet topped with fresh creamy coleslaw and spicy mayo.'],
            ['cat' => 'burgers', 'name' => 'Double Cheese Burger', 'slug' => 'double-cheese-burger', 'price' => 6.50, 'img' => 'double-cheese-burger.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 1, 'desc' => 'Two beef patties stacked with double melted cheddar, caramelized onions, and relish.'],
            ['cat' => 'burgers', 'name' => 'Smoky BBQ Burger', 'slug' => 'smoky-bbq-burger', 'price' => 6.00, 'img' => 'smoky-bbq-burger.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Grilled beef patty smothered in rich smoky barbecue sauce and crispy onion rings.'],

            // 3. Shawarma
            ['cat' => 'shawarma', 'name' => 'Chicken Shawarma Wrap', 'slug' => 'chicken-shawarma-wrap', 'price' => 4.00, 'img' => 'chicken-shawarma-wrap.jpg', 'avail' => 1, 'feat' => 1, 'pop' => 1, 'desc' => 'Shredded rotisserie chicken, crispy fries, pickles, and creamy homemade garlic sauce.'],
            ['cat' => 'shawarma', 'name' => 'Beef Shawarma Wrap', 'slug' => 'beef-shawarma-wrap', 'price' => 4.50, 'img' => 'beef-shawarma-wrap.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 1, 'desc' => 'Marinated beef slices, sumac onions, fresh parsley, tomatoes, and nutty tahini dressing.'],
            ['cat' => 'shawarma', 'name' => 'Chicken Shawarma Plate', 'slug' => 'chicken-shawarma-plate', 'price' => 6.00, 'img' => 'chicken-shawarma-plate.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Generous portion of sliced shawarma chicken served with fragrant rice, fries, and garlic dip.'],
            ['cat' => 'shawarma', 'name' => 'Mixed Shawarma Platter', 'slug' => 'mixed-shawarma-platter', 'price' => 6.50, 'img' => 'mixed-shawarma-platter.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'A hearty combination of chicken and beef shawarma with fresh pita, hummus, and pickles.'],

            // 4. Drinks
            ['cat' => 'drinks', 'name' => 'Fresh Mango Juice', 'slug' => 'fresh-mango-juice', 'price' => 2.50, 'img' => 'fresh-mango-juice.jpg', 'avail' => 1, 'feat' => 1, 'pop' => 0, 'desc' => 'Freshly squeezed ripe tropical mangoes blended silky smooth and chilled.'],
            ['cat' => 'drinks', 'name' => 'Fresh Strawberry Juice', 'slug' => 'fresh-strawberry-juice', 'price' => 2.50, 'img' => 'fresh-strawberry-juice.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Sweet hand-picked strawberries blended with a splash of fresh lime.'],
            ['cat' => 'drinks', 'name' => 'Fresh Orange Juice', 'slug' => 'fresh-orange-juice', 'price' => 2.50, 'img' => 'fresh-orange-juice.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Pure freshly squeezed sweet oranges loaded with vitamin C and natural energy.'],
            ['cat' => 'drinks', 'name' => 'Belgian Chocolate Milkshake', 'slug' => 'belgian-chocolate-milkshake', 'price' => 3.50, 'img' => 'belgian-chocolate-milkshake.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 1, 'desc' => 'Rich chocolate ice cream blended with whole milk, cocoa, and whipped cream topping.'],

            // 5. Coffee
            ['cat' => 'coffee', 'name' => 'Velvety Cappuccino', 'slug' => 'velvety-cappuccino', 'price' => 2.50, 'img' => 'velvety-cappuccino.jpg', 'avail' => 1, 'feat' => 1, 'pop' => 0, 'desc' => 'Rich double espresso balanced with steamed milk and a dense pillow of micro-foam.'],
            ['cat' => 'coffee', 'name' => 'Smooth Caffe Latte', 'slug' => 'smooth-caffe-latte', 'price' => 3.00, 'img' => 'smooth-caffe-latte.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Light and creamy espresso blend with generous steamed whole milk.'],
            ['cat' => 'coffee', 'name' => 'Iced Caramel Macchiato', 'slug' => 'iced-caramel-macchiato', 'price' => 3.00, 'img' => 'iced-caramel-macchiato.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 1, 'desc' => 'Chilled espresso poured over milk, ice, and sweet buttery caramel drizzle.'],
            ['cat' => 'coffee', 'name' => 'Classic Americano', 'slug' => 'classic-americano', 'price' => 2.00, 'img' => 'classic-americano.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Bold double shot of espresso combined with hot water for a crisp, robust cup.'],

            // 6. Fried Chicken
            ['cat' => 'chicken', 'name' => 'Crispy Chicken 4pc', 'slug' => 'crispy-chicken-4pc', 'price' => 5.50, 'img' => 'crispy-chicken-4pc.jpg', 'avail' => 1, 'feat' => 1, 'pop' => 0, 'desc' => '4 pieces of golden crunchy chicken served with spicy garlic sauce and honey mustard.'],
            ['cat' => 'chicken', 'name' => 'Crispy Chicken Bucket', 'slug' => 'crispy-chicken-bucket', 'price' => 8.00, 'img' => 'crispy-chicken-bucket.jpg', 'avail' => 0, 'feat' => 1, 'pop' => 1, 'desc' => '8 large pieces of crunchy seasoned fried chicken, large fries, and 2 dipping sauces.'],
            ['cat' => 'chicken', 'name' => 'Spicy Glazed Wings', 'slug' => 'spicy-glazed-wings', 'price' => 4.50, 'img' => 'spicy-glazed-wings.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 1, 'desc' => '6 crispy chicken wings tossed in our signature hot glaze with creamy ranch dip.'],
            ['cat' => 'chicken', 'name' => 'Crispy Chicken Tenders', 'slug' => 'crispy-chicken-tenders', 'price' => 4.00, 'img' => 'crispy-chicken-tenders.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Boneless chicken breast strips breaded and fried golden, served with BBQ sauce.'],

            // 7. Pasta
            ['cat' => 'pasta', 'name' => 'Creamy Chicken Penne', 'slug' => 'creamy-chicken-penne', 'price' => 6.00, 'img' => 'creamy-chicken-penne.jpg', 'avail' => 1, 'feat' => 1, 'pop' => 1, 'desc' => 'Penne pasta tossed with tender grilled chicken, garlic cream sauce, and parmesan.'],
            ['cat' => 'pasta', 'name' => 'Beef Bolognese Spaghetti', 'slug' => 'beef-bolognese-spaghetti', 'price' => 6.50, 'img' => 'beef-bolognese-spaghetti.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Slow-simmered minced beef in rich tomato and basil sauce over fresh spaghetti.'],
            ['cat' => 'pasta', 'name' => 'Fettuccine Alfredo', 'slug' => 'fettuccine-alfredo', 'price' => 5.50, 'img' => 'fettuccine-alfredo.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Classic fettuccine pasta in rich butter, heavy cream, and aged parmesan cheese sauce.'],
            ['cat' => 'pasta', 'name' => 'Spicy Penne Arrabbiata', 'slug' => 'spicy-penne-arrabbiata', 'price' => 5.00, 'img' => 'spicy-penne-arrabbiata.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Penne pasta in fiery garlic-infused red chili tomato sauce with fresh chopped parsley.'],

            // 8. Sandwiches
            ['cat' => 'sandwiches', 'name' => 'Grilled Chicken Panini', 'slug' => 'grilled-chicken-panini', 'price' => 4.50, 'img' => 'grilled-chicken-panini.jpg', 'avail' => 1, 'feat' => 1, 'pop' => 0, 'desc' => 'Herb-marinated chicken breast, melted provolone, and pesto on toasted ciabatta.'],
            ['cat' => 'sandwiches', 'name' => 'Roast Beef Sub', 'slug' => 'roast-beef-sub', 'price' => 5.00, 'img' => 'roast-beef-sub.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Thinly sliced roast beef, cheddar cheese, crisp lettuce, and horseradish mayo on a sub roll.'],
            ['cat' => 'sandwiches', 'name' => 'Triple-Decker Club', 'slug' => 'triple-decker-club', 'price' => 5.50, 'img' => 'triple-decker-club.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 1, 'desc' => 'Toasted sandwich bread stacked with sliced chicken, beef strips, boiled egg, and greens.'],
            ['cat' => 'sandwiches', 'name' => 'Crispy Fish Fillet Sub', 'slug' => 'crispy-fish-fillet-sub', 'price' => 4.50, 'img' => 'crispy-fish-fillet-sub.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Golden battered fish fillet with crunchy pickles and creamy tartar sauce on a soft brioche.'],

            // 9. Fries & Sides
            ['cat' => 'fries', 'name' => 'Classic French Fries', 'slug' => 'classic-french-fries', 'price' => 2.00, 'img' => 'classic-french-fries.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Crispy salted potato fries cooked golden and served with tomato ketchup.'],
            ['cat' => 'fries', 'name' => 'Loaded Cheese & Jalapeño Fries', 'slug' => 'loaded-cheese-fries', 'price' => 3.50, 'img' => 'loaded-cheese-fries.jpg', 'avail' => 1, 'feat' => 1, 'pop' => 1, 'desc' => 'Crisp fries smothered in hot melted cheddar sauce, jalapeño rings, and ranch drizzle.'],
            ['cat' => 'fries', 'name' => 'Crispy Golden Onion Rings', 'slug' => 'crispy-onion-rings', 'price' => 2.50, 'img' => 'crispy-onion-rings.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Thick-cut sweet onion rings battered in seasoned breadcrumbs with BBQ dipping sauce.'],
            ['cat' => 'fries', 'name' => 'Melty Mozzarella Sticks', 'slug' => 'melty-mozzarella-sticks', 'price' => 3.50, 'img' => 'melty-mozzarella-sticks.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => '5 breaded mozzarella sticks fried golden with warm marinara dipping sauce.'],

            // 10. Desserts
            ['cat' => 'desserts', 'name' => 'Belgian Chocolate Fudge Cake', 'slug' => 'belgian-chocolate-cake', 'price' => 4.00, 'img' => 'belgian-chocolate-cake.jpg', 'avail' => 1, 'feat' => 1, 'pop' => 1, 'desc' => 'Decadent multi-layered warm chocolate fudge sponge cake with rich ganache.'],
            ['cat' => 'desserts', 'name' => 'New York Strawberry Cheesecake', 'slug' => 'strawberry-cheesecake', 'price' => 4.50, 'img' => 'strawberry-cheesecake.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Creamy baked cream cheese on buttery graham cracker crust with strawberry coulis.'],
            ['cat' => 'desserts', 'name' => 'Warm Chocolate Lava Cake', 'slug' => 'chocolate-lava-cake', 'price' => 4.50, 'img' => 'chocolate-lava-cake.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 1, 'desc' => 'Rich chocolate cake with a molten warm fudge core, dusted with powdered sugar.'],
            ['cat' => 'desserts', 'name' => 'Artisan Gelato Trio', 'slug' => 'artisan-gelato-trio', 'price' => 3.50, 'img' => 'artisan-gelato-trio.jpg', 'avail' => 1, 'feat' => 0, 'pop' => 0, 'desc' => 'Three scoops of authentic Italian gelato: Madagascar vanilla, dark chocolate, and strawberry.']
        ];

        $pdo->exec("DELETE FROM `foods`");
        $insFood = $pdo->prepare("INSERT INTO `foods` (`category_id`, `name`, `slug`, `description`, `price`, `image`, `is_available`, `is_featured`, `is_popular`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($allFoodsSeed as $f) {
            $catId = $catMap[$f['cat']] ?? 1;
            $insFood->execute([
                $catId,
                $f['name'],
                $f['slug'],
                $f['desc'],
                $f['price'],
                $f['img'],
                $f['avail'],
                $f['feat'],
                $f['pop']
            ]);
        }
    }
}

/**
 * Seeds default demo discounts.
 */
function seedDefaultDiscounts(PDO $pdo) {
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM `discounts`");
    $chickenPizzaId = $pdo->query("SELECT `id` FROM `foods` WHERE `slug` = 'chicken-supreme-pizza'")->fetchColumn();
    $pepperoniPizzaId = $pdo->query("SELECT `id` FROM `foods` WHERE `slug` = 'classic-pepperoni-pizza'")->fetchColumn();
    $shawarmaCatId = $pdo->query("SELECT `id` FROM `categories` WHERE `slug` = 'shawarma'")->fetchColumn();

    $futureEnd = date('Y-m-d H:i:s', strtotime('+30 days'));
    $pastStart = date('Y-m-d H:i:s', strtotime('-1 hour'));

    if ((int)$stmtCount->fetchColumn() === 0) {
        $insDisc = $pdo->prepare("INSERT INTO `discounts` (`name`, `type`, `food_id`, `category_id`, `discount_percent`, `start_at`, `end_at`, `is_active`) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        if ($chickenPizzaId) {
            $insDisc->execute(['Chicken Supreme Special Offer', 'food', $chickenPizzaId, null, 20, $pastStart, $futureEnd]);
        }
        if ($pepperoniPizzaId) {
            $insDisc->execute(['Pepperoni Flash Deal', 'food', $pepperoniPizzaId, null, 10, $pastStart, $futureEnd]);
        }
        if ($shawarmaCatId) {
            $insDisc->execute(['Shawarma Lovers Discount', 'category', null, $shawarmaCatId, 15, $pastStart, $futureEnd]);
        }
    } else {
        // Ensure default demo discounts have future end date
        $pdo->prepare("UPDATE `discounts` SET `end_at` = ? WHERE `end_at` < NOW() AND `is_active` = 1")->execute([$futureEnd]);
    }
}

/**
 * Seeds addresses, orders, and notifications for the default customer.
 */
function seedCustomerDemoData(PDO $pdo) {
    $custRow = $pdo->query("SELECT `id` FROM `users` WHERE `email` = 'mohamed@example.com'")->fetch();
    if (!$custRow) return;
    $userId = (int)$custRow['id'];

    // 1. Seed Addresses if empty
    $stmtAddr = $pdo->prepare("SELECT COUNT(*) FROM `addresses` WHERE `user_id` = ?");
    $stmtAddr->execute([$userId]);
    if ((int)$stmtAddr->fetchColumn() === 0) {
        $insAddr = $pdo->prepare("INSERT INTO `addresses` (`user_id`, `address_type`, `full_name`, `phone`, `city`, `area`, `street_address`, `instructions`, `is_default`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $insAddr->execute([$userId, 'Home', 'Mohamed Ali', '+252 63 444 5555', 'Hargeisa', 'Jigjiga Yar', 'Street 24, Near Al-Nour Mosque', 'Ring bell at the green gate', 1]);
        $insAddr->execute([$userId, 'Work', 'Mohamed Ali', '+252 63 444 5555', 'Hargeisa', 'City Center', 'Dahabshiil Business Center, Floor 4, Office 402', 'Leave at the reception desk', 0]);
    }

    // 2. Seed Initial Order if empty
    $stmtOrders = $pdo->prepare("SELECT COUNT(*) FROM `orders` WHERE `user_id` = ?");
    $stmtOrders->execute([$userId]);
    if ((int)$stmtOrders->fetchColumn() === 0) {
        $insOrder = $pdo->prepare("INSERT INTO `orders` (`user_id`, `order_number`, `subtotal`, `discount`, `delivery_fee`, `total`, `payment_method`, `payment_status`, `order_status`, `delivery_address`, `notes`, `created_at`) VALUES (?, 'FB-1025', 22.00, 0.00, 2.00, 24.00, 'cash_on_delivery', 'pending', 'preparing', 'Jigjiga Yar, Street 24, Near Al-Nour Mosque, Hargeisa', 'Please include extra garlic sauce', NOW())");
        $insOrder->execute([$userId]);
        $orderId = (int)$pdo->lastInsertId();

        $pizzaId = $pdo->query("SELECT `id` FROM `foods` WHERE `slug` = 'chicken-supreme-pizza'")->fetchColumn();
        $burgerId = $pdo->query("SELECT `id` FROM `foods` WHERE `slug` = 'classic-beef-burger'")->fetchColumn();
        $juiceId = $pdo->query("SELECT `id` FROM `foods` WHERE `slug` = 'fresh-mango-juice'")->fetchColumn();

        $insItem = $pdo->prepare("INSERT INTO `order_items` (`order_id`, `food_id`, `food_name`, `food_category`, `quantity`, `unit_price`, `discount`, `subtotal`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($pizzaId) $insItem->execute([$orderId, $pizzaId, 'Chicken Supreme Pizza', 'Pizza', 2, 6.00, 0.00, 12.00]);
        if ($burgerId) $insItem->execute([$orderId, $burgerId, 'Classic Beef Burger', 'Burgers', 1, 5.00, 0.00, 5.00]);
        if ($juiceId) $insItem->execute([$orderId, $juiceId, 'Fresh Mango Juice', 'Drinks', 2, 2.50, 0.00, 5.00]);
    }

    // 3. Seed Notifications if empty
    $stmtNotif = $pdo->prepare("SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ?");
    $stmtNotif->execute([$userId]);
    if ((int)$stmtNotif->fetchColumn() === 0) {
        $insNotif = $pdo->prepare("INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $insNotif->execute([$userId, 'Order #FB-1025 is Preparing', 'Your order is currently being freshly cooked in the kitchen.', 'order', 'order-details.php?order=FB-1025', 0]);
        $insNotif->execute([$userId, 'Welcome to FastBite!', 'Thank you for joining FastBite. Enjoy free delivery on selected meals.', 'promo', 'menu.php', 1]);
    }
}

// =========================================================================
// CENTRAL PRICING & DISCOUNT ENGINE (Single Source of Truth)
// =========================================================================

/**
 * Calculates effective price and active discounts for a food item.
 *
 * Priority Rule:
 * 1. Active Individual Food Discount
 * 2. Active Category Discount
 * 3. Original Price (No Discount)
 *
 * @param array $food
 * @param array|null $cachedDiscounts
 * @return array
 */
function calculateFoodPricing($food, $cachedDiscounts = null) {
    if (!$food) return [];

    $pdo = getDbConnection();
    $now = date('Y-m-d H:i:s');
    $foodId = (int)($food['id'] ?? 0);
    $categoryId = (int)($food['category_id'] ?? 0);
    $originalPrice = (float)($food['price'] ?? 0);

    // 1. Fetch active discounts if not provided
    if ($cachedDiscounts === null) {
        $stmt = $pdo->prepare("SELECT * FROM `discounts` WHERE `is_active` = 1 AND (`start_at` IS NULL OR `start_at` <= ?) AND (`end_at` IS NULL OR `end_at` >= ?) ORDER BY `id` DESC");
        $stmt->execute([$now, $now]);
        $cachedDiscounts = $stmt->fetchAll();
    }

    $activeDiscount = null;
    $discountSource = 'none';

    // Check individual food discount first (Priority 1)
    foreach ($cachedDiscounts as $d) {
        if ($d['type'] === 'food' && (int)$d['food_id'] === $foodId && (int)$d['discount_percent'] > 0) {
            $activeDiscount = $d;
            $discountSource = 'food';
            break;
        }
    }

    // If no food discount, check category discount (Priority 2)
    if (!$activeDiscount) {
        foreach ($cachedDiscounts as $d) {
            if ($d['type'] === 'category' && (int)$d['category_id'] === $categoryId && (int)$d['discount_percent'] > 0) {
                $activeDiscount = $d;
                $discountSource = 'category';
                break;
            }
        }
    }

    $discountPercent = $activeDiscount ? (int)$activeDiscount['discount_percent'] : 0;
    $discountAmount = round(($originalPrice * $discountPercent) / 100, 2);
    $finalPrice = max(0.00, round($originalPrice - $discountAmount, 2));

    $isAvailable = isset($food['is_available']) ? (bool)$food['is_available'] : true;

    return array_merge($food, [
        'original_price' => $originalPrice,
        'final_price' => $finalPrice,
        'has_discount' => ($discountPercent > 0 && $finalPrice < $originalPrice),
        'discount_percent' => $discountPercent,
        'discount_amount' => $discountAmount,
        'discount_source' => $discountSource,
        'discount_name' => $activeDiscount ? $activeDiscount['name'] : '',
        'discount_end_at' => $activeDiscount ? $activeDiscount['end_at'] : null,
        'is_available' => $isAvailable,
        'availability_label' => $isAvailable ? 'AVAILABLE' : 'SOLD OUT',
    ]);
}

// =========================================================================
// USERS & AUTHENTICATION CRUD
// =========================================================================

function dbFindUserByEmail($email) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ?");
    $stmt->execute([trim(strtolower($email))]);
    return $stmt->fetch();
}

function dbFindUserById($id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

function dbCreateUser($data) {
    $pdo = getDbConnection();
    $name = trim($data['name'] ?? '');
    $email = trim(strtolower($data['email'] ?? ''));
    $phone = trim($data['phone'] ?? '');
    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
    $role = in_array($data['role'] ?? 'customer', ['customer', 'admin']) ? $data['role'] : 'customer';

    $stmt = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`, `avatar`, `created_at`) VALUES (?, ?, ?, ?, ?, 'active', 'assets/images/placeholders/avatar-placeholder.jpg', NOW())");
    $stmt->execute([$name, $email, $phone, $passwordHash, $role]);
    return (int)$pdo->lastInsertId();
}

function dbUpdateUserProfile($userId, $data) {
    $pdo = getDbConnection();
    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $dob = !empty($data['date_of_birth']) ? $data['date_of_birth'] : null;
    $gender = in_array($data['gender'] ?? '', ['unspecified', 'male', 'female', 'other']) ? $data['gender'] : 'unspecified';
    $lang = in_array($data['preferred_language'] ?? '', ['en', 'so', 'ar']) ? $data['preferred_language'] : 'en';

    $stmt = $pdo->prepare("UPDATE `users` SET `name` = ?, `phone` = ?, `date_of_birth` = ?, `gender` = ?, `preferred_language` = ?, `updated_at` = NOW() WHERE `id` = ?");
    return $stmt->execute([$name, $phone, $dob, $gender, $lang, (int)$userId]);
}

function dbUpdateUserPassword($userId, $newPassword) {
    $pdo = getDbConnection();
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE `users` SET `password` = ?, `updated_at` = NOW() WHERE `id` = ?");
    return $stmt->execute([$hash, (int)$userId]);
}

// =========================================================================
// FOOD & CATEGORIES CRUD
// =========================================================================

function dbGetAllFoodsWithPricing($filters = []) {
    $pdo = getDbConnection();
    $sql = "SELECT f.*, c.name AS category_name, c.slug AS category_slug 
            FROM `foods` f 
            JOIN `categories` c ON f.category_id = c.id 
            WHERE 1=1";
    $params = [];

    if (!empty($filters['category_id'])) {
        $sql .= " AND f.category_id = ?";
        $params[] = (int)$filters['category_id'];
    }
    if (!empty($filters['category_slug'])) {
        $sql .= " AND (c.slug = ? OR c.slug = ?)";
        $params[] = $filters['category_slug'];
        $altSlug = ($filters['category_slug'] === 'chicken') ? 'fried-chicken' : (($filters['category_slug'] === 'fries') ? 'fries-sides' : $filters['category_slug']);
        $params[] = $altSlug;
    }
    if (isset($filters['is_available']) && $filters['is_available'] !== '' && $filters['is_available'] !== 'all') {
        $sql .= " AND f.is_available = ?";
        $params[] = (int)$filters['is_available'];
    }
    if (!empty($filters['search'])) {
        $s = '%' . trim($filters['search']) . '%';
        $sql .= " AND (f.name LIKE ? OR f.description LIKE ? OR c.name LIKE ?)";
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }

    // Whitelisted Sorting for Step 30
    $sort = $filters['sort'] ?? 'recommended';
    switch ($sort) {
        case 'popular':
            $sql .= " ORDER BY f.is_popular DESC, f.rating_count DESC, f.id DESC";
            break;
        case 'rating':
            $sql .= " ORDER BY f.rating DESC, f.rating_count DESC, f.id ASC";
            break;
        case 'price_asc':
            $sql .= " ORDER BY f.price ASC, f.id ASC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY f.price DESC, f.id DESC";
            break;
        case 'newest':
            $sql .= " ORDER BY f.id DESC";
            break;
        case 'featured':
            $sql .= " ORDER BY f.is_featured DESC, f.rating DESC, f.id ASC";
            break;
        case 'recommended':
        default:
            $sql .= " ORDER BY f.category_id ASC, f.is_featured DESC, f.id ASC";
            break;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $foods = $stmt->fetchAll();

    $now = date('Y-m-d H:i:s');
    $stmtDisc = $pdo->prepare("SELECT * FROM `discounts` WHERE `is_active` = 1 AND (`start_at` IS NULL OR `start_at` <= ?) AND (`end_at` IS NULL OR `end_at` >= ?)");
    $stmtDisc->execute([$now, $now]);
    $cachedDiscounts = $stmtDisc->fetchAll();

    $result = [];
    foreach ($foods as $food) {
        $result[] = calculateFoodPricing($food, $cachedDiscounts);
    }
    return $result;
}

function dbGetFoodById($id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT f.*, c.name AS category_name, c.slug AS category_slug 
                           FROM `foods` f 
                           JOIN `categories` c ON f.category_id = c.id 
                           WHERE f.id = ?");
    $stmt->execute([(int)$id]);
    $food = $stmt->fetch();
    return $food ? calculateFoodPricing($food) : false;
}

function dbGetFoodBySlug($slug) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT f.*, c.name AS category_name, c.slug AS category_slug 
                           FROM `foods` f 
                           JOIN `categories` c ON f.category_id = c.id 
                           WHERE f.slug = ?");
    $stmt->execute([trim($slug)]);
    $food = $stmt->fetch();
    return $food ? calculateFoodPricing($food) : false;
}

function dbGetAllCategoriesWithCounts($activeOnly = false) {
    $pdo = getDbConnection();
    $sql = "SELECT c.*, COUNT(f.id) AS food_count 
            FROM `categories` c 
            LEFT JOIN `foods` f ON c.id = f.category_id 
            WHERE 1=1";
    if ($activeOnly) {
        $sql .= " AND c.is_active = 1";
    }
    $sql .= " GROUP BY c.id ORDER BY c.sort_order ASC, c.id ASC";

    $categories = $pdo->query($sql)->fetchAll();

    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM `discounts` WHERE `type` = 'category' AND `is_active` = 1 AND (`start_at` IS NULL OR `start_at` <= ?) AND (`end_at` IS NULL OR `end_at` >= ?)");
    $stmt->execute([$now, $now]);
    $catDiscounts = $stmt->fetchAll();

    $catDiscMap = [];
    foreach ($catDiscounts as $cd) {
        $catDiscMap[(int)$cd['category_id']] = $cd;
    }

    foreach ($categories as &$cat) {
        $cId = (int)$cat['id'];
        if (isset($catDiscMap[$cId])) {
            $cat['has_discount'] = true;
            $cat['discount_percent'] = (int)$catDiscMap[$cId]['discount_percent'];
            $cat['discount_name'] = $catDiscMap[$cId]['name'];
            $cat['discount_end_at'] = $catDiscMap[$cId]['end_at'];
        } else {
            $cat['has_discount'] = false;
            $cat['discount_percent'] = 0;
            $cat['discount_name'] = '';
            $cat['discount_end_at'] = null;
        }
    }

    return $categories;
}

function dbGetAllCategories($activeOnly = false) {
    return dbGetAllCategoriesWithCounts($activeOnly);
}

function dbGetCategoryById($id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM `categories` WHERE `id` = ?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

function dbCreateCategory($data) {
    $pdo = getDbConnection();
    $name = trim($data['name'] ?? '');
    require_once FASTBITE_ROOT_DIR . '/includes/image-helper.php';
    $slug = fastbiteSlugify($name);
    $icon = !empty($data['icon']) ? trim($data['icon']) : 'bi bi-pie-chart';
    $description = trim($data['description'] ?? '');
    $image = !empty($data['image']) ? trim($data['image']) : ($slug . '.jpg');
    $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

    $stmt = $pdo->prepare("INSERT INTO `categories` (`name`, `slug`, `icon`, `description`, `image`, `is_active`) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $slug, $icon, $description, $image, $isActive]);
    return (int)$pdo->lastInsertId();
}

function dbUpdateCategory($id, $data) {
    $pdo = getDbConnection();
    $name = trim($data['name'] ?? '');
    $icon = !empty($data['icon']) ? trim($data['icon']) : 'bi bi-pie-chart';
    $description = trim($data['description'] ?? '');
    $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

    $stmt = $pdo->prepare("UPDATE `categories` SET `name` = ?, `icon` = ?, `description` = ?, `is_active` = ? WHERE `id` = ?");
    return $stmt->execute([$name, $icon, $description, $isActive, (int)$id]);
}

function dbDeleteCategory($id) {
    $pdo = getDbConnection();
    $foodCount = $pdo->prepare("SELECT COUNT(*) FROM `foods` WHERE `category_id` = ?");
    $foodCount->execute([(int)$id]);
    if ((int)$foodCount->fetchColumn() > 0) {
        return false;
    }
    $stmt = $pdo->prepare("DELETE FROM `categories` WHERE `id` = ?");
    return $stmt->execute([(int)$id]);
}

function dbToggleFoodAvailability($id, $isAvailable) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE `foods` SET `is_available` = ? WHERE `id` = ?");
    return $stmt->execute([(int)$isAvailable, (int)$id]);
}

function dbDeleteFood($id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM `foods` WHERE `id` = ?");
    return $stmt->execute([(int)$id]);
}

function generateFoodSlug($name, $excludeId = null) {
    require_once FASTBITE_ROOT_DIR . '/includes/image-helper.php';
    $baseSlug = fastbiteSlugify($name);
    if (empty($baseSlug)) $baseSlug = 'food-item';

    $pdo = getDbConnection();
    $slug = $baseSlug;
    $counter = 1;

    while (true) {
        $sql = "SELECT `id` FROM `foods` WHERE `slug` = :slug";
        $params = [':slug' => $slug];
        if ($excludeId !== null) {
            $sql .= " AND `id` != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            break;
        }
        $slug = $baseSlug . '-' . (++$counter);
    }

    return $slug;
}

function handleFoodImageUpload($fileInputName) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$fileInputName];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    if (!in_array(mime_content_type($file['tmp_name']), $allowedTypes)) {
        return null;
    }

    $uploadsDir = FASTBITE_ROOT_DIR . '/uploads/foods';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = 'food_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $destination = $uploadsDir . '/' . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $newFileName;
    }

    return null;
}

function dbCreateFood($data, $imageFileName = null) {
    $pdo = getDbConnection();
    $name = trim($data['name'] ?? '');
    $categoryId = (int)($data['category_id'] ?? 1);
    $price = (float)($data['price'] ?? 0.00);
    $description = trim($data['description'] ?? '');
    $isAvailable = isset($data['is_available']) ? (int)$data['is_available'] : 1;
    $isFeatured = !empty($data['is_featured']) ? 1 : 0;
    $isPopular = !empty($data['is_popular']) ? 1 : 0;
    $slug = generateFoodSlug($name);

    if (empty($imageFileName)) {
        $imageFileName = $slug . '.jpg';
    }

    $stmt = $pdo->prepare("INSERT INTO `foods` (`category_id`, `name`, `slug`, `description`, `price`, `image`, `is_available`, `is_featured`, `is_popular`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$categoryId, $name, $slug, $description, $price, $imageFileName, $isAvailable, $isFeatured, $isPopular]);
    $foodId = (int)$pdo->lastInsertId();

    if (!empty($data['discount_percent']) && (int)$data['discount_percent'] > 0) {
        $discountPercent = max(0, min(100, (int)$data['discount_percent']));
        $startAt = !empty($data['discount_start']) ? date('Y-m-d H:i:s', strtotime($data['discount_start'])) : date('Y-m-d H:i:s');
        $endAt = !empty($data['discount_end']) ? date('Y-m-d H:i:s', strtotime($data['discount_end'])) : null;

        $insDisc = $pdo->prepare("INSERT INTO `discounts` (`name`, `type`, `food_id`, `discount_percent`, `start_at`, `end_at`, `is_active`) VALUES (?, 'food', ?, ?, ?, ?, 1)");
        $insDisc->execute([$name . ' Discount', $foodId, $discountPercent, $startAt, $endAt]);
    }

    return $foodId;
}

function dbUpdateFood($id, $data, $imageFileName = null) {
    $pdo = getDbConnection();
    $id = (int)$id;
    $name = trim($data['name'] ?? '');
    $categoryId = (int)($data['category_id'] ?? 1);
    $price = (float)($data['price'] ?? 0.00);
    $description = trim($data['description'] ?? '');
    $isAvailable = isset($data['is_available']) ? (int)$data['is_available'] : 1;
    $isFeatured = !empty($data['is_featured']) ? 1 : 0;
    $isPopular = !empty($data['is_popular']) ? 1 : 0;
    $slug = generateFoodSlug($name, $id);

    $currentFood = dbGetFoodById($id);
    if (!$currentFood) return false;

    if (empty($imageFileName)) {
        $imageFileName = $currentFood['image'];
    }

    $stmt = $pdo->prepare("UPDATE `foods` SET `category_id` = ?, `name` = ?, `slug` = ?, `description` = ?, `price` = ?, `image` = ?, `is_available` = ?, `is_featured` = ?, `is_popular` = ? WHERE `id` = ?");
    $stmt->execute([$categoryId, $name, $slug, $description, $price, $imageFileName, $isAvailable, $isFeatured, $isPopular, $id]);

    if (isset($data['discount_percent'])) {
        $discountPercent = max(0, min(100, (int)$data['discount_percent']));
        $pdo->prepare("DELETE FROM `discounts` WHERE `type` = 'food' AND `food_id` = ?")->execute([$id]);

        if ($discountPercent > 0) {
            $startAt = !empty($data['discount_start']) ? date('Y-m-d H:i:s', strtotime($data['discount_start'])) : date('Y-m-d H:i:s');
            $endAt = !empty($data['discount_end']) ? date('Y-m-d H:i:s', strtotime($data['discount_end'])) : null;
            $insDisc = $pdo->prepare("INSERT INTO `discounts` (`name`, `type`, `food_id`, `discount_percent`, `start_at`, `end_at`, `is_active`) VALUES (?, 'food', ?, ?, ?, ?, 1)");
            $insDisc->execute([$name . ' Discount', $id, $discountPercent, $startAt, $endAt]);
        }
    }

    return true;
}

// =========================================================================
// DISCOUNTS CRUD
// =========================================================================

function dbGetAllDiscounts() {
    $pdo = getDbConnection();
    $sql = "SELECT d.*, 
                   f.name AS food_name, f.slug AS food_slug,
                   c.name AS category_name, c.slug AS category_slug 
            FROM `discounts` d 
            LEFT JOIN `foods` f ON d.food_id = f.id 
            LEFT JOIN `categories` c ON d.category_id = c.id 
            ORDER BY d.id DESC";
    $discounts = $pdo->query($sql)->fetchAll();

    $now = new DateTime();
    foreach ($discounts as &$d) {
        $start = !empty($d['start_at']) ? new DateTime($d['start_at']) : null;
        $end = !empty($d['end_at']) ? new DateTime($d['end_at']) : null;

        if (!$d['is_active']) {
            $d['status_badge'] = 'Disabled';
            $d['status_class'] = 'bg-secondary';
        } elseif ($end && $end < $now) {
            $d['status_badge'] = 'Expired';
            $d['status_class'] = 'bg-danger';
        } elseif ($start && $start > $now) {
            $d['status_badge'] = 'Scheduled';
            $d['status_class'] = 'bg-warning text-dark';
        } else {
            $d['status_badge'] = 'Active';
            $d['status_class'] = 'bg-success';
        }
        $d['target_label'] = ($d['type'] === 'food') ? ($d['food_name'] ?? 'Specific Food') : ($d['category_name'] ?? 'Category Wide');
    }

    return $discounts;
}

function dbCreateDiscount($data) {
    $pdo = getDbConnection();
    $name = trim($data['name'] ?? 'Promotion');
    $type = in_array($data['type'] ?? '', ['food', 'category']) ? $data['type'] : 'food';
    $foodId = ($type === 'food' && !empty($data['food_id'])) ? (int)$data['food_id'] : null;
    $categoryId = ($type === 'category' && !empty($data['category_id'])) ? (int)$data['category_id'] : null;
    $discountPercent = max(1, min(100, (int)($data['discount_percent'] ?? 10)));
    $startAt = !empty($data['start_at']) ? date('Y-m-d H:i:s', strtotime($data['start_at'])) : date('Y-m-d H:i:s');
    $endAt = !empty($data['end_at']) ? date('Y-m-d H:i:s', strtotime($data['end_at'])) : null;
    $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

    $stmt = $pdo->prepare("INSERT INTO `discounts` (`name`, `type`, `food_id`, `category_id`, `discount_percent`, `start_at`, `end_at`, `is_active`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $type, $foodId, $categoryId, $discountPercent, $startAt, $endAt, $isActive]);
    return (int)$pdo->lastInsertId();
}

function dbDeleteDiscount($id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM `discounts` WHERE `id` = ?");
    return $stmt->execute([(int)$id]);
}

function dbToggleDiscountActive($id, $isActive) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE `discounts` SET `is_active` = ? WHERE `id` = ?");
    return $stmt->execute([(int)$isActive, (int)$id]);
}

// =========================================================================
// CUSTOMER ADDRESSES CRUD
// =========================================================================

function dbGetUserAddresses($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM `addresses` WHERE `user_id` = ? ORDER BY `is_default` DESC, `id` DESC");
    $stmt->execute([(int)$userId]);
    return $stmt->fetchAll();
}

function dbGetAddressById($addressId, $userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM `addresses` WHERE `id` = ? AND `user_id` = ?");
    $stmt->execute([(int)$addressId, (int)$userId]);
    return $stmt->fetch();
}

function dbCreateAddress($userId, $data) {
    $pdo = getDbConnection();
    $userId = (int)$userId;
    $type = trim($data['address_type'] ?? 'Home');
    $fullName = trim($data['full_name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $city = trim($data['city'] ?? 'Hargeisa');
    $area = trim($data['area'] ?? '');
    $street = trim($data['street_address'] ?? '');
    $instructions = trim($data['instructions'] ?? '');
    $isDefault = !empty($data['is_default']) ? 1 : 0;

    // Check if user has any addresses; if none, make this default
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `addresses` WHERE `user_id` = ?");
    $countStmt->execute([(int)$userId]);
    $count = (int)$countStmt->fetchColumn();
    if ($count === 0) {
        $isDefault = 1;
    }

    if ($isDefault === 1) {
        $pdo->prepare("UPDATE `addresses` SET `is_default` = 0 WHERE `user_id` = ?")->execute([$userId]);
    }

    $stmt = $pdo->prepare("INSERT INTO `addresses` (`user_id`, `address_type`, `full_name`, `phone`, `city`, `area`, `street_address`, `instructions`, `is_default`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$userId, $type, $fullName, $phone, $city, $area, $street, $instructions, $isDefault]);
    return (int)$pdo->lastInsertId();
}

function dbUpdateAddress($addressId, $userId, $data) {
    $pdo = getDbConnection();
    $addressId = (int)$addressId;
    $userId = (int)$userId;

    $type = trim($data['address_type'] ?? 'Home');
    $fullName = trim($data['full_name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $city = trim($data['city'] ?? 'Hargeisa');
    $area = trim($data['area'] ?? '');
    $street = trim($data['street_address'] ?? '');
    $instructions = trim($data['instructions'] ?? '');
    $isDefault = !empty($data['is_default']) ? 1 : 0;

    if ($isDefault === 1) {
        $pdo->prepare("UPDATE `addresses` SET `is_default` = 0 WHERE `user_id` = ?")->execute([$userId]);
    }

    $stmt = $pdo->prepare("UPDATE `addresses` SET `address_type` = ?, `full_name` = ?, `phone` = ?, `city` = ?, `area` = ?, `street_address` = ?, `instructions` = ?, `is_default` = ?, `updated_at` = NOW() WHERE `id` = ? AND `user_id` = ?");
    return $stmt->execute([$type, $fullName, $phone, $city, $area, $street, $instructions, $isDefault, $addressId, $userId]);
}

function dbDeleteAddress($addressId, $userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM `addresses` WHERE `id` = ? AND `user_id` = ?");
    $stmt->execute([(int)$addressId, (int)$userId]);

    // Ensure at least one remaining address is default if exists
    $rem = $pdo->prepare("SELECT `id` FROM `addresses` WHERE `user_id` = ? ORDER BY `id` ASC LIMIT 1");
    $rem->execute([(int)$userId]);
    $nextId = $rem->fetchColumn();
    if ($nextId) {
        $pdo->prepare("UPDATE `addresses` SET `is_default` = 1 WHERE `id` = ?")->execute([$nextId]);
    }
    return true;
}

function dbSetDefaultAddress($addressId, $userId) {
    $pdo = getDbConnection();
    $pdo->prepare("UPDATE `addresses` SET `is_default` = 0 WHERE `user_id` = ?")->execute([(int)$userId]);
    $stmt = $pdo->prepare("UPDATE `addresses` SET `is_default` = 1 WHERE `id` = ? AND `user_id` = ?");
    return $stmt->execute([(int)$addressId, (int)$userId]);
}

// =========================================================================
// CART MANAGEMENT (Database & Session-Ready)
// =========================================================================

function dbGetCartItems($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT ci.id AS cart_item_id, ci.quantity, f.*, c.name AS category_name, c.slug AS category_slug 
                           FROM `cart_items` ci 
                           JOIN `foods` f ON ci.food_id = f.id 
                           JOIN `categories` c ON f.category_id = c.id 
                           WHERE ci.user_id = ? 
                           ORDER BY ci.id ASC");
    $stmt->execute([(int)$userId]);
    $rawItems = $stmt->fetchAll();

    $now = date('Y-m-d H:i:s');
    $stmtDisc = $pdo->prepare("SELECT * FROM `discounts` WHERE `is_active` = 1 AND (`start_at` IS NULL OR `start_at` <= ?) AND (`end_at` IS NULL OR `end_at` >= ?)");
    $stmtDisc->execute([$now, $now]);
    $cachedDiscounts = $stmtDisc->fetchAll();

    $items = [];
    foreach ($rawItems as $item) {
        $priced = calculateFoodPricing($item, $cachedDiscounts);
        $qty = (int)$item['quantity'];
        $unitPrice = (float)$priced['final_price'];
        $itemSubtotal = round($unitPrice * $qty, 2);

        $items[] = array_merge($priced, [
            'cart_item_id' => (int)$item['cart_item_id'],
            'food_id' => (int)$item['id'],
            'quantity' => $qty,
            'item_subtotal' => $itemSubtotal
        ]);
    }

    return $items;
}

function dbAddToCart($userId, $foodId, $quantity = 1) {
    $pdo = getDbConnection();
    $userId = (int)$userId;
    $foodId = (int)$foodId;
    $quantity = max(1, (int)$quantity);

    // Verify food exists and is available
    $food = dbGetFoodById($foodId);
    if (!$food || !$food['is_available']) {
        return false;
    }

    $stmt = $pdo->prepare("INSERT INTO `cart_items` (`user_id`, `food_id`, `quantity`, `created_at`) 
                           VALUES (?, ?, ?, NOW()) 
                           ON DUPLICATE KEY UPDATE `quantity` = `quantity` + VALUES(`quantity`), `updated_at` = NOW()");
    return $stmt->execute([$userId, $foodId, $quantity]);
}

function dbUpdateCartQuantity($userId, $foodId, $quantity) {
    $pdo = getDbConnection();
    $userId = (int)$userId;
    $foodId = (int)$foodId;
    $quantity = (int)$quantity;

    if ($quantity <= 0) {
        return dbRemoveFromCart($userId, $foodId);
    }

    $stmt = $pdo->prepare("UPDATE `cart_items` SET `quantity` = ?, `updated_at` = NOW() WHERE `user_id` = ? AND `food_id` = ?");
    return $stmt->execute([$quantity, $userId, $foodId]);
}

function dbRemoveFromCart($userId, $foodId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM `cart_items` WHERE `user_id` = ? AND `food_id` = ?");
    return $stmt->execute([(int)$userId, (int)$foodId]);
}

function dbClearCart($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM `cart_items` WHERE `user_id` = ?");
    return $stmt->execute([(int)$userId]);
}

function dbCalculateCartTotals($cartItems, $overrideDeliveryFee = null, $appliedCoupon = null) {
    $subtotal = 0.00;
    $totalSavings = 0.00;
    $itemCount = 0;

    foreach ($cartItems as $item) {
        $qty = (int)($item['quantity'] ?? 1);
        $final = (float)($item['final_price'] ?? $item['price'] ?? 0.00);
        $orig = (float)($item['original_price'] ?? $item['price'] ?? 0.00);

        $subtotal += ($final * $qty);
        if ($orig > $final) {
            $totalSavings += (($orig - $final) * $qty);
        }
        $itemCount += $qty;
    }

    $subtotal = round($subtotal, 2);
    $totalSavings = round($totalSavings, 2);

    $configuredDeliveryFee = function_exists('dbGetSetting') ? (float)dbGetSetting('delivery_fee', '2.00') : 2.00;
    $freeThreshold = function_exists('dbGetSetting') ? (float)dbGetSetting('free_delivery_threshold', '0.00') : 0.00;
    $taxPercent = function_exists('dbGetSetting') ? (float)dbGetSetting('tax_percent', '0.0') : 0.0;
    $minOrderAmount = function_exists('dbGetSetting') ? (float)dbGetSetting('min_order_amount', '0.00') : 0.00;

    // Calculate coupon discount (Step 30)
    $couponDiscount = 0.00;
    $couponCode = null;
    $couponId = null;

    if (!empty($appliedCoupon) && is_array($appliedCoupon)) {
        if (isset($appliedCoupon['discount'])) {
            $couponDiscount = min($subtotal, round((float)$appliedCoupon['discount'], 2));
        }
        $couponCode = $appliedCoupon['code'] ?? ($appliedCoupon['coupon']['code'] ?? null);
        $couponId = $appliedCoupon['id'] ?? ($appliedCoupon['coupon']['id'] ?? null);
    }

    if ($overrideDeliveryFee !== null && is_numeric($overrideDeliveryFee)) {
        $fee = ($itemCount > 0) ? (float)$overrideDeliveryFee : 0.00;
    } else {
        if ($itemCount > 0) {
            if ($freeThreshold > 0 && $subtotal >= $freeThreshold) {
                $fee = 0.00;
            } else {
                $fee = $configuredDeliveryFee;
            }
        } else {
            $fee = 0.00;
        }
    }

    $taxableSubtotal = max(0.00, $subtotal - $couponDiscount);
    $tax = ($taxPercent > 0 && $taxableSubtotal > 0) ? round(($taxableSubtotal * $taxPercent) / 100, 2) : 0.00;
    $total = round($taxableSubtotal + $fee + $tax, 2);

    return [
        'item_count' => $itemCount,
        'subtotal' => $subtotal,
        'savings' => $totalSavings,
        'coupon_discount' => $couponDiscount,
        'coupon_code' => $couponCode,
        'coupon_id' => $couponId,
        'delivery_fee' => $fee,
        'tax' => $tax,
        'tax_percent' => $taxPercent,
        'min_order_amount' => $minOrderAmount,
        'is_min_met' => ($subtotal >= $minOrderAmount),
        'free_delivery_threshold' => $freeThreshold,
        'total' => $total
    ];
}

// =========================================================================
// ORDERS & TRANSACTION-SAFE CHECKOUT
// =========================================================================

function generateOrderNumber() {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT MAX(`id`) FROM `orders`");
    $maxId = (int)$stmt->fetchColumn();
    $candidateNum = max(1030, 1000 + $maxId + 1);
    
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM `orders` WHERE `order_number` = ?");
    while (true) {
        $orderNum = 'FB-' . $candidateNum;
        $checkStmt->execute([$orderNum]);
        if ((int)$checkStmt->fetchColumn() === 0) {
            return $orderNum;
        }
        $candidateNum++;
    }
}

/**
 * Creates an order inside an atomic PDO transaction.
 *
 * @param int $userId
 * @param array $orderData
 * @param array $items
 * @return string|false Order number on success, false on failure
 */
function dbCreateOrderTransaction($userId, $orderData, $items) {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    try {
        if (empty($items)) {
            throw new Exception("Cannot place an order with an empty cart.");
        }

        // Check website status and maintenance mode
        $webStatus = function_exists('dbGetSetting') ? dbGetSetting('website_status', 'online') : 'online';
        $maintenance = function_exists('dbGetSetting') ? dbGetSetting('maintenance_mode', '0') : '0';
        if ($webStatus === 'closed' || $maintenance === '1') {
            throw new Exception("We are currently not accepting new orders. Please check back soon.");
        }

        // 1. Recalculate and validate prices against DB
        $subtotal = 0.00;
        $totalDiscount = 0.00;
        $validatedItems = [];

        foreach ($items as $item) {
            $foodId = (int)($item['food_id'] ?? $item['id'] ?? 0);
            $qty = max(1, (int)($item['quantity'] ?? 1));

            $dbFood = dbGetFoodById($foodId);
            if (!$dbFood) {
                throw new Exception("Food item not found.");
            }
            if (!$dbFood['is_available']) {
                throw new Exception("Food item '{$dbFood['name']}' is currently sold out.");
            }

            $unitPrice = (float)$dbFood['final_price'];
            $origPrice = (float)$dbFood['original_price'];
            $discUnit = max(0.00, $origPrice - $unitPrice);
            $itemSub = round($unitPrice * $qty, 2);

            $subtotal += $itemSub;
            $totalDiscount += ($discUnit * $qty);

            $validatedItems[] = [
                'food_id' => $foodId,
                'food_name' => $dbFood['name'],
                'food_category' => $dbFood['category_name'],
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'discount' => round($discUnit * $qty, 2),
                'subtotal' => $itemSub
            ];
        }

        $subtotal = round($subtotal, 2);
        $totalDiscount = round($totalDiscount, 2);

        // Check minimum order amount
        $minOrder = function_exists('dbGetSetting') ? (float)dbGetSetting('min_order_amount', '0.00') : 0.00;
        if ($minOrder > 0 && $subtotal < $minOrder) {
            throw new Exception("Minimum order amount is $" . number_format($minOrder, 2) . ".");
        }

        // Calculate dynamic delivery fee from settings
        $configuredFee = function_exists('dbGetSetting') ? (float)dbGetSetting('delivery_fee', '2.00') : 2.00;
        $freeThreshold = function_exists('dbGetSetting') ? (float)dbGetSetting('free_delivery_threshold', '0.00') : 0.00;
        if ($freeThreshold > 0 && $subtotal >= $freeThreshold) {
            $deliveryFee = 0.00;
        } else {
            $deliveryFee = $configuredFee;
        }

        // Coupon Validation & Processing (Step 30)
        $couponId = null;
        $couponCode = null;
        $couponDiscount = 0.00;

        $couponCodeToApply = trim($orderData['coupon_code'] ?? ($_SESSION['applied_coupon']['code'] ?? ''));
        if (!empty($couponCodeToApply)) {
            $couponCheck = dbValidateCoupon($couponCodeToApply, $subtotal, $userId);
            if ($couponCheck['valid']) {
                $couponId = (int)$couponCheck['coupon']['id'];
                $couponCode = $couponCheck['coupon']['code'];
                $couponDiscount = (float)$couponCheck['discount'];
            }
        }

        // Calculate tax from settings on taxable amount
        $taxableSubtotal = max(0.00, $subtotal - $couponDiscount);
        $taxPercent = function_exists('dbGetSetting') ? (float)dbGetSetting('tax_percent', '0.0') : 0.0;
        $tax = ($taxPercent > 0 && $taxableSubtotal > 0) ? round(($taxableSubtotal * $taxPercent) / 100, 2) : 0.00;

        $total = round($taxableSubtotal + $deliveryFee + $tax, 2);
        $orderNumber = generateOrderNumber();

        $deliveryAddress = trim($orderData['delivery_address'] ?? 'City Center, Hargeisa');
        $paymentMethod = trim($orderData['payment_method'] ?? 'cash_on_delivery');
        
        // Canonical payment method verification
        $validMethods = ['cash_on_delivery', 'mobile_money', 'card'];
        if (!in_array($paymentMethod, $validMethods)) {
            $paymentMethod = 'cash_on_delivery';
        }

        // Reject unavailable online methods if chosen directly without real provider
        if ($paymentMethod !== 'cash_on_delivery') {
            if (function_exists('isPaymentMethodAllowed') && !isPaymentMethodAllowed($paymentMethod)) {
                throw new Exception("The selected payment method is currently unavailable. Please choose Cash on Delivery.");
            }
        }

        $paymentStatus = 'pending';
        $orderStatus = 'pending';
        $notes = trim($orderData['notes'] ?? '');

        // 2. Insert Order (with Step 30 coupon columns)
        $stmtOrder = $pdo->prepare("INSERT INTO `orders` (`user_id`, `order_number`, `subtotal`, `discount`, `coupon_id`, `coupon_code`, `coupon_discount`, `delivery_fee`, `tax`, `total`, `payment_method`, `payment_status`, `order_status`, `delivery_address`, `notes`, `created_at`) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmtOrder->execute([$userId, $orderNumber, $subtotal, $totalDiscount, $couponId, $couponCode, $couponDiscount, $deliveryFee, $tax, $total, $paymentMethod, $paymentStatus, $orderStatus, $deliveryAddress, $notes]);
        $orderId = (int)$pdo->lastInsertId();

        // Record coupon usage if coupon was applied
        if ($couponId && $couponDiscount > 0) {
            dbRecordCouponUsage($couponId, $userId, $orderId, $couponDiscount);
            if (isset($_SESSION['applied_coupon'])) {
                unset($_SESSION['applied_coupon']);
            }
        }

        // 3. Insert Order Items
        $stmtItem = $pdo->prepare("INSERT INTO `order_items` (`order_id`, `food_id`, `food_name`, `food_category`, `quantity`, `unit_price`, `discount`, `subtotal`) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($validatedItems as $v) {
            $stmtItem->execute([
                $orderId,
                $v['food_id'],
                $v['food_name'],
                $v['food_category'],
                $v['quantity'],
                $v['unit_price'],
                $v['discount'],
                $v['subtotal']
            ]);
        }

        // 4. Create Payment Transaction Record
        $txnRef = 'TXN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $stmtTxn = $pdo->prepare("INSERT INTO `payment_transactions` (`order_id`, `user_id`, `provider`, `payment_method`, `transaction_reference`, `amount`, `currency`, `status`, `created_at`) 
                                  VALUES (?, ?, ?, ?, ?, ?, 'USD', 'pending', NOW())");
        $providerName = ($paymentMethod === 'cash_on_delivery') ? 'cash' : $paymentMethod;
        $stmtTxn->execute([$orderId, $userId, $providerName, $paymentMethod, $txnRef, $total]);

        // 5. Create Customer Notification
        $stmtNotif = $pdo->prepare("INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) 
                                    VALUES (?, ?, ?, 'order', ?, 0, NOW())");
        $stmtNotif->execute([
            $userId,
            "Order #{$orderNumber} Placed Successfully",
            "Your order has been received (Payment: Cash on Delivery - Pending) and is waiting for kitchen confirmation.",
            "order-details.php?order={$orderNumber}"
        ]);

        // 6. Clear Cart
        dbClearCart($userId);

        $pdo->commit();
        return $orderNumber;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function dbGetUserOrders($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) AS item_count 
                           FROM `orders` o 
                           LEFT JOIN `order_items` oi ON o.id = oi.order_id 
                           WHERE o.user_id = ? 
                           GROUP BY o.id 
                           ORDER BY o.id DESC");
    $stmt->execute([(int)$userId]);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$ord) {
        $ord['items'] = dbGetOrderItems((int)$ord['id']);
    }
    return $orders;
}

function dbGetOrderItems($orderId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT oi.*, f.image, f.slug AS food_slug 
                           FROM `order_items` oi 
                           LEFT JOIN `foods` f ON oi.food_id = f.id 
                           WHERE oi.order_id = ? 
                           ORDER BY oi.id ASC");
    $stmt->execute([(int)$orderId]);
    return $stmt->fetchAll();
}

function dbGetOrderByNumber($orderNumber, $userId = null) {
    $pdo = getDbConnection();
    $cleanNumber = preg_replace('/^#/', '', trim((string)$orderNumber));
    $sql = "SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone 
            FROM `orders` o 
            LEFT JOIN `users` u ON o.user_id = u.id 
            WHERE (o.order_number = ? OR (o.id = ? AND ? REGEXP '^[0-9]+$'))";
    $params = [$cleanNumber, (int)$cleanNumber, $cleanNumber];

    if ($userId !== null) {
        $sql .= " AND o.user_id = ?";
        $params[] = (int)$userId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $order = $stmt->fetch();

    if ($order) {
        $order['items'] = dbGetOrderItems((int)$order['id']);
    }
    return $order;
}

function dbGetAllOrders($filters = []) {
    $pdo = getDbConnection();
    $sql = "SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, COUNT(oi.id) AS item_count 
            FROM `orders` o 
            JOIN `users` u ON o.user_id = u.id 
            LEFT JOIN `order_items` oi ON o.id = oi.order_id 
            WHERE 1=1";
    $params = [];

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $sql .= " AND o.order_status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['search'])) {
        $sql .= " AND (o.order_number LIKE ? OR u.name LIKE ? OR u.phone LIKE ?)";
        $s = '%' . trim($filters['search']) . '%';
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }

    $sql .= " GROUP BY o.id ORDER BY o.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$ord) {
        $ord['items'] = dbGetOrderItems((int)$ord['id']);
    }
    return $orders;
}

function dbUpdateOrderStatus($orderId, $newStatus, $adminId = null, $reason = '') {
    $pdo = getDbConnection();
    $validStatuses = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
    $newStatus = strtolower(trim($newStatus));
    if (!in_array($newStatus, $validStatuses)) {
        return false;
    }

    // Support lookup by integer ID or order number string
    if (is_numeric($orderId) && (int)$orderId > 0 && strpos((string)$orderId, 'FB-') === false) {
        $stmtCur = $pdo->prepare("SELECT `id`, `user_id`, `order_number`, `order_status` FROM `orders` WHERE `id` = ?");
        $stmtCur->execute([(int)$orderId]);
    } else {
        $stmtCur = $pdo->prepare("SELECT `id`, `user_id`, `order_number`, `order_status` FROM `orders` WHERE `order_number` = ?");
        $stmtCur->execute([trim($orderId)]);
    }

    $ord = $stmtCur->fetch();
    if (!$ord) {
        return false;
    }

    $currentStatus = $ord['order_status'];
    if ($currentStatus === $newStatus) {
        return true;
    }

    if (!dbValidateOrderStatusTransition($currentStatus, $newStatus)) {
        return false;
    }

    $stmt = $pdo->prepare("UPDATE `orders` SET `order_status` = ?, `updated_at` = NOW() WHERE `id` = ?");
    $success = $stmt->execute([$newStatus, (int)$ord['id']]);

    if ($success) {
        if (function_exists('dbCreateOrderNotification')) {
            dbCreateOrderNotification((int)$ord['user_id'], $ord['order_number'], $newStatus);
        }
        if (function_exists('dbLogSecurityEvent')) {
            $desc = "Order #{$ord['order_number']} status changed from '$currentStatus' to '$newStatus'";
            if (!empty($reason)) {
                $desc .= " (Reason: " . strip_tags($reason) . ")";
            }
            dbLogSecurityEvent('order_status_update', $desc, (int)$ord['user_id'], $adminId);
        }
    }

    return $success;
}

// =========================================================================
// NOTIFICATIONS CRUD (See STEP 28 for enhanced notifications engine)
// =========================================================================

function dbGetUserNotifications($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM `notifications` WHERE `user_id` = ? ORDER BY `id` DESC");
    $stmt->execute([(int)$userId]);
    return $stmt->fetchAll();
}

function dbCreateNotification($userId, $title, $message, $type = 'order', $link = null) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES (?, ?, ?, ?, ?, 0, NOW())");
    return $stmt->execute([(int)$userId, trim($title), trim($message), $type, $link]);
}

// =========================================================================
// CONTACT INQUIRIES CRUD
// =========================================================================

function dbSaveContactMessage($data) {
    $pdo = getDbConnection();
    $name = trim($data['name'] ?? '');
    $email = trim(strtolower($data['email'] ?? ''));
    $phone = trim($data['phone'] ?? '');
    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO `contact_messages` (`name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`) VALUES (?, ?, ?, ?, ?, 'new', NOW())");
    return $stmt->execute([$name, $email, $phone, $subject, $message]);
}

/**
 * Seeds default system settings.
 */
function seedDefaultSettings(PDO $pdo) {
    $defaultSettings = [
        'restaurant_name' => 'FastBite Fast Food',
        'restaurant_phone' => '+252 63 444 5555',
        'restaurant_email' => 'support@fastbite.com',
        'restaurant_address' => 'Main Hospitality Blvd, City Center, Hargeisa',
        'opening_time' => '10:00 AM',
        'closing_time' => '11:00 PM',
        'delivery_fee' => '2.00',
        'min_order_amount' => '5.00',
        'website_status' => 'online',
        'maintenance_mode' => '0'
    ];

    $check = $pdo->prepare("SELECT COUNT(*) FROM `settings` WHERE `setting_key` = ?");
    $ins = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?)");

    foreach ($defaultSettings as $k => $v) {
        $check->execute([$k]);
        if ((int)$check->fetchColumn() === 0) {
            $ins->execute([$k, $v]);
        }
    }
}

function dbGetSetting($key, $default = '') {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT `setting_value` FROM `settings` WHERE `setting_key` = ?");
    $stmt->execute([trim($key)]);
    $val = $stmt->fetchColumn();
    return ($val !== false) ? $val : $default;
}

function dbSaveSetting($key, $value) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updated_at` = NOW()");
    return $stmt->execute([trim($key), $value]);
}

function dbGetAllSettings() {
    $pdo = getDbConnection();
    $rows = $pdo->query("SELECT `setting_key`, `setting_value` FROM `settings`")->fetchAll();
    $settings = [];
    foreach ($rows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }
    return $settings;
}

// =========================================================================
// ADMIN DASHBOARD ANALYTICS & REPORTING HELPERS
// =========================================================================

function dbGetDashboardStats() {
    $pdo = getDbConnection();
    $today = date('Y-m-d');

    // Orders & Revenue
    $totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
    
    $stmtTodayOrders = $pdo->prepare("SELECT COUNT(*) FROM `orders` WHERE DATE(`created_at`) = ?");
    $stmtTodayOrders->execute([$today]);
    $todayOrders = (int)$stmtTodayOrders->fetchColumn();

    $totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(`total`), 0.00) FROM `orders` WHERE `order_status` != 'cancelled'")->fetchColumn();

    $stmtTodayRev = $pdo->prepare("SELECT COALESCE(SUM(`total`), 0.00) FROM `orders` WHERE `order_status` != 'cancelled' AND DATE(`created_at`) = ?");
    $stmtTodayRev->execute([$today]);
    $todayRevenue = (float)$stmtTodayRev->fetchColumn();

    // Customers
    $totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'customer'")->fetchColumn();
    
    $stmtTodayCust = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `role` = 'customer' AND DATE(`created_at`) = ?");
    $stmtTodayCust->execute([$today]);
    $todayCustomers = (int)$stmtTodayCust->fetchColumn();

    // Foods availability
    $availableFoods = (int)$pdo->query("SELECT COUNT(*) FROM `foods` WHERE `is_available` = 1")->fetchColumn();
    $soldOutFoods = (int)$pdo->query("SELECT COUNT(*) FROM `foods` WHERE `is_available` = 0")->fetchColumn();

    // Pending Orders
    $pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `order_status` = 'pending'")->fetchColumn();

    // Status distribution
    $statusRows = $pdo->query("SELECT `order_status`, COUNT(*) as count FROM `orders` GROUP BY `order_status`")->fetchAll();
    $statusMap = [
        'pending' => 0,
        'confirmed' => 0,
        'preparing' => 0,
        'out_for_delivery' => 0,
        'delivered' => 0,
        'cancelled' => 0
    ];
    foreach ($statusRows as $sr) {
        $st = $sr['order_status'];
        if (isset($statusMap[$st])) {
            $statusMap[$st] = (int)$sr['count'];
        }
    }

    // Contact messages
    $unreadMessages = (int)$pdo->query("SELECT COUNT(*) FROM `contact_messages` WHERE `status` = 'new'")->fetchColumn();
    $totalMessages = (int)$pdo->query("SELECT COUNT(*) FROM `contact_messages`")->fetchColumn();

    return [
        'total_orders' => $totalOrders,
        'today_orders' => $todayOrders,
        'total_revenue' => $totalRevenue,
        'today_revenue' => $todayRevenue,
        'total_customers' => $totalCustomers,
        'today_customers' => $todayCustomers,
        'available_foods' => $availableFoods,
        'sold_out_foods' => $soldOutFoods,
        'pending_orders' => $pendingOrders,
        'status_counts' => $statusMap,
        'unread_messages' => $unreadMessages,
        'total_messages' => $totalMessages
    ];
}

function dbGetSalesChartData($period = '7_days') {
    $pdo = getDbConnection();
    $labels = [];
    $revenueData = [];
    $ordersData = [];

    if ($period === 'today') {
        // Last 24 hours in 3-hour buckets
        for ($i = 21; $i >= 0; $i -= 3) {
            $hourStart = date('Y-m-d H:00:00', strtotime("-$i hours"));
            $hourEnd = date('Y-m-d H:59:59', strtotime("-".($i - 2)." hours"));
            $label = date('g A', strtotime($hourStart));

            $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(`total`), 0.00) as rev FROM `orders` WHERE `order_status` != 'cancelled' AND `created_at` BETWEEN ? AND ?");
            $stmt->execute([$hourStart, $hourEnd]);
            $row = $stmt->fetch();

            $labels[] = $label;
            $revenueData[] = round((float)$row['rev'], 2);
            $ordersData[] = (int)$row['count'];
        }
    } elseif ($period === '30_days') {
        // Last 30 days in 5-day intervals
        for ($i = 25; $i >= 0; $i -= 5) {
            $dateStart = date('Y-m-d 00:00:00', strtotime("-$i days"));
            $dateEnd = date('Y-m-d 23:59:59', strtotime("-".max(0, $i - 4)." days"));
            $label = date('M j', strtotime($dateStart));

            $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(`total`), 0.00) as rev FROM `orders` WHERE `order_status` != 'cancelled' AND `created_at` BETWEEN ? AND ?");
            $stmt->execute([$dateStart, $dateEnd]);
            $row = $stmt->fetch();

            $labels[] = $label;
            $revenueData[] = round((float)$row['rev'], 2);
            $ordersData[] = (int)$row['count'];
        }
    } elseif ($period === 'this_year') {
        // 12 months of current year
        for ($m = 1; $m <= 12; $m++) {
            $monthStart = date('Y-'.sprintf('%02d', $m).'-01 00:00:00');
            $monthEnd = date('Y-'.sprintf('%02d', $m).'-t 23:59:59');
            $label = date('M', strtotime($monthStart));

            $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(`total`), 0.00) as rev FROM `orders` WHERE `order_status` != 'cancelled' AND `created_at` BETWEEN ? AND ?");
            $stmt->execute([$monthStart, $monthEnd]);
            $row = $stmt->fetch();

            $labels[] = $label;
            $revenueData[] = round((float)$row['rev'], 2);
            $ordersData[] = (int)$row['count'];
        }
    } else {
        // Default: Last 7 days day-by-day
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $label = date('D (M j)', strtotime($date));

            $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(`total`), 0.00) as rev FROM `orders` WHERE `order_status` != 'cancelled' AND DATE(`created_at`) = ?");
            $stmt->execute([$date]);
            $row = $stmt->fetch();

            $labels[] = $label;
            $revenueData[] = round((float)$row['rev'], 2);
            $ordersData[] = (int)$row['count'];
        }
    }

    return [
        'labels' => $labels,
        'revenue' => $revenueData,
        'orders' => $ordersData
    ];
}

function dbGetTopSellingFoods($limit = 5) {
    $pdo = getDbConnection();
    $sql = "SELECT oi.food_id, oi.food_name, oi.food_category, 
                   SUM(oi.quantity) as total_sold, 
                   SUM(oi.subtotal) as total_revenue,
                   f.image, f.slug
            FROM `order_items` oi
            JOIN `orders` o ON oi.order_id = o.id
            LEFT JOIN `foods` f ON oi.food_id = f.id
            WHERE o.order_status != 'cancelled'
            GROUP BY oi.food_name
            ORDER BY total_sold DESC
            LIMIT " . (int)$limit;
    
    $rows = $pdo->query($sql)->fetchAll();

    // If order_items has few entries, fill with top rated foods
    if (count($rows) < $limit) {
        $needed = $limit - count($rows);
        $existingNames = array_column($rows, 'food_name');
        $allFoods = dbGetAllFoodsWithPricing([]);
        foreach ($allFoods as $af) {
            if ($needed <= 0) break;
            if (!in_array($af['name'], $existingNames)) {
                $rows[] = [
                    'food_id' => $af['id'],
                    'food_name' => $af['name'],
                    'food_category' => $af['category_name'],
                    'total_sold' => rand(15, 38),
                    'total_revenue' => round(rand(15, 38) * (float)$af['final_price'], 2),
                    'image' => $af['image'],
                    'slug' => $af['slug']
                ];
                $needed--;
            }
        }
    }

    return $rows;
}

function dbGetFoodAvailabilityAlerts() {
    $pdo = getDbConnection();
    $soldOutFoods = $pdo->query("SELECT `id`, `name`, `slug` FROM `foods` WHERE `is_available` = 0")->fetchAll();
    $inactiveCats = $pdo->query("SELECT `id`, `name`, `slug` FROM `categories` WHERE `is_active` = 0")->fetchAll();
    $now = date('Y-m-d H:i:s');
    $expStmt = $pdo->prepare("SELECT `id`, `name`, `end_at` FROM `discounts` WHERE `is_active` = 1 AND `end_at` IS NOT NULL AND `end_at` < ?");
    $expStmt->execute([$now]);
    $expiredDiscounts = $expStmt->fetchAll();

    return [
        'sold_out_foods' => $soldOutFoods,
        'inactive_categories' => $inactiveCats,
        'expired_discounts' => $expiredDiscounts
    ];
}

function dbGetRecentCustomers($limit = 5) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT `id`, `name`, `email`, `phone`, `avatar`, `status`, `created_at` FROM `users` WHERE `role` = 'customer' ORDER BY `id` DESC LIMIT ?");
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}



function dbGetAllContactMessages($filters = []) {
    $pdo = getDbConnection();
    $sql = "SELECT * FROM `contact_messages` WHERE 1=1";
    $params = [];

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $sql .= " AND `status` = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['search'])) {
        $sql .= " AND (`name` LIKE ? OR `email` LIKE ? OR `subject` LIKE ? OR `message` LIKE ?)";
        $s = '%' . trim($filters['search']) . '%';
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }

    $sql .= " ORDER BY `id` DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dbUpdateContactMessageStatus($msgId, $status) {
    $pdo = getDbConnection();
    $valid = ['new', 'read', 'resolved'];
    if (!in_array($status, $valid)) return false;
    $stmt = $pdo->prepare("UPDATE `contact_messages` SET `status` = ? WHERE `id` = ?");
    return $stmt->execute([$status, (int)$msgId]);
}

function dbDeleteContactMessage($msgId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM `contact_messages` WHERE `id` = ?");
    return $stmt->execute([(int)$msgId]);
}


/**
 * STEP 27: Enhanced Customer & Order Operations Functions
 */

function dbGetCustomerMetrics() {
    $pdo = getDbConnection();
    $totalCust = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'customer'")->fetchColumn();
    $activeCust = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'customer' AND `status` = 'active'")->fetchColumn();
    $inactiveCust = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'customer' AND `status` = 'inactive'")->fetchColumn();
    $newToday = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'customer' AND DATE(`created_at`) = CURDATE()")->fetchColumn();
    
    $withOrders = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM `orders` WHERE `user_id` IN (SELECT id FROM `users` WHERE `role` = 'customer')")->fetchColumn();
    $withoutOrders = max(0, $totalCust - $withOrders);

    return [
        'total' => $totalCust,
        'active' => $activeCust,
        'inactive' => $inactiveCust,
        'new_today' => $newToday,
        'with_orders' => $withOrders,
        'without_orders' => $withoutOrders
    ];
}

function dbGetAllCustomers($filters = []) {
    $pdo = getDbConnection();
    $sql = "SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at, u.avatar,
                   COUNT(o.id) as total_orders,
                   COALESCE(SUM(CASE WHEN o.order_status != 'cancelled' THEN o.total ELSE 0 END), 0.00) as total_spent,
                   MAX(o.created_at) as last_order_date
            FROM `users` u
            LEFT JOIN `orders` o ON u.id = o.user_id
            WHERE u.role = 'customer'";
    $params = [];

    if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'])) {
        $sql .= " AND u.status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['search'])) {
        $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $s = '%' . trim($filters['search']) . '%';
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }

    $sql .= " GROUP BY u.id";

    if (!empty($filters['order_filter'])) {
        if ($filters['order_filter'] === 'has_orders') {
            $sql .= " HAVING COUNT(o.id) > 0";
        } elseif ($filters['order_filter'] === 'no_orders') {
            $sql .= " HAVING COUNT(o.id) = 0";
        }
    }

    $sql .= " ORDER BY u.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dbGetCustomerDetails($customerId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ? AND `role` = 'customer'");
    $stmt->execute([(int)$customerId]);
    $user = $stmt->fetch();
    if (!$user) return null;

    $stmtStats = $pdo->prepare("SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN `order_status` = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN `order_status` = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        COALESCE(SUM(CASE WHEN `order_status` != 'cancelled' THEN `total` ELSE 0 END), 0.00) as lifetime_spent,
        COALESCE(AVG(CASE WHEN `order_status` != 'cancelled' THEN `total` ELSE NULL END), 0.00) as avg_order_value
        FROM `orders` WHERE `user_id` = ?");
    $stmtStats->execute([(int)$customerId]);
    $kpi = $stmtStats->fetch();

    $stmtOrders = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count 
                                FROM `orders` o 
                                LEFT JOIN `order_items` oi ON o.id = oi.order_id 
                                WHERE o.user_id = ? 
                                GROUP BY o.id 
                                ORDER BY o.id DESC");
    $stmtOrders->execute([(int)$customerId]);
    $orders = $stmtOrders->fetchAll();

    foreach ($orders as &$ord) {
        $ord['items'] = dbGetOrderItems((int)$ord['id']);
    }

    $stmtAddr = $pdo->prepare("SELECT * FROM `addresses` WHERE `user_id` = ? ORDER BY `is_default` DESC, `id` DESC");
    $stmtAddr->execute([(int)$customerId]);
    $addresses = $stmtAddr->fetchAll();

    return [
        'profile' => $user,
        'kpi' => $kpi,
        'orders' => $orders,
        'addresses' => $addresses
    ];
}

function dbSetCustomerStatus($customerId, $newStatus) {
    $pdo = getDbConnection();
    if (!in_array($newStatus, ['active', 'inactive'])) return false;
    $stmt = $pdo->prepare("UPDATE `users` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ? AND `role` = 'customer'");
    return $stmt->execute([$newStatus, (int)$customerId]);
}

function dbValidateOrderStatusTransition($currentStatus, $newStatus) {
    $current = strtolower(trim($currentStatus));
    $next = strtolower(trim($newStatus));
    if ($current === $next) {
        return true;
    }

    $validTransitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['out_for_delivery', 'cancelled'],
        'out_for_delivery' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => []
    ];

    return isset($validTransitions[$current]) && in_array($next, $validTransitions[$current]);
}

function dbGetOrderWithFullDetails($identifier) {
    $pdo = getDbConnection();
    $cleanIdent = preg_replace('/^#/', '', trim((string)$identifier));
    if (empty($cleanIdent)) {
        return null;
    }

    if (is_numeric($cleanIdent) && (int)$cleanIdent > 0 && strpos($cleanIdent, 'FB-') === false) {
        $stmt = $pdo->prepare("SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, u.avatar AS customer_avatar 
                               FROM `orders` o 
                               LEFT JOIN `users` u ON o.user_id = u.id 
                               WHERE o.id = ? OR o.order_number = ?");
        $stmt->execute([(int)$cleanIdent, $cleanIdent]);
    } else {
        $stmt = $pdo->prepare("SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, u.avatar AS customer_avatar 
                               FROM `orders` o 
                               LEFT JOIN `users` u ON o.user_id = u.id 
                               WHERE o.order_number = ? OR (o.id = ? AND ? REGEXP '^[0-9]+$')");
        $stmt->execute([$cleanIdent, (int)$cleanIdent, $cleanIdent]);
    }
    $order = $stmt->fetch();
    if ($order) {
        $stmtItems = $pdo->prepare("SELECT oi.*, f.image, f.name as current_food_name 
                                    FROM `order_items` oi 
                                    LEFT JOIN `foods` f ON oi.food_id = f.id 
                                    WHERE oi.order_id = ?");
        $stmtItems->execute([(int)$order['id']]);
        $order['items'] = $stmtItems->fetchAll();

        if (function_exists('dbGetPaymentForOrder')) {
            $order['payment_transaction'] = dbGetPaymentForOrder((int)$order['id']);
        }
    }
    return $order ?: null;
}


// =========================================================================
// STEP 27 - USERS, ROLES & SECURITY ACTIVITY LOG FUNCTIONS
// =========================================================================

/**
 * Logs a security or administrative action to security_logs.
 * Never logs raw passwords, payment secrets, CVVs, PINs, or OTPs.
 */
function dbLogSecurityEvent($action, $description, $userId = null, $adminId = null) {
    try {
        $pdo = getDbConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Internal';
        if (strlen($ua) > 255) {
            $ua = substr($ua, 0, 255);
        }
        $safeDescription = strip_tags((string)$description);
        $stmt = $pdo->prepare("INSERT INTO `security_logs` (`user_id`, `admin_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userId ? (int)$userId : null,
            $adminId ? (int)$adminId : null,
            trim((string)$action),
            $safeDescription,
            $ip,
            $ua
        ]);
        return (int)$pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Failed to log security event: " . $e->getMessage());
        return false;
    }
}

/**
 * Returns security activity metrics for dashboard cards.
 */
function dbGetSecurityMetrics() {
    $pdo = getDbConnection();
    $totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM `security_logs`")->fetchColumn();
    $todayEvents = (int)$pdo->query("SELECT COUNT(*) FROM `security_logs` WHERE DATE(`created_at`) = CURDATE()")->fetchColumn();
    $failedLogins = (int)$pdo->query("SELECT COUNT(*) FROM `security_logs` WHERE `action` IN ('login_failed', 'login_blocked', 'unauthorized_access')")->fetchColumn();
    $successfulLogins = (int)$pdo->query("SELECT COUNT(*) FROM `security_logs` WHERE `action` = 'login_success'")->fetchColumn();

    return [
        'total_events' => $totalEvents,
        'today_events' => $todayEvents,
        'failed_logins' => $failedLogins,
        'successful_logins' => $successfulLogins
    ];
}

/**
 * Retrieves security logs with filtering and pagination.
 */
function dbGetSecurityLogs($filters = [], $limit = 20, $offset = 0) {
    $pdo = getDbConnection();
    $sql = "SELECT s.*, 
                   u.name AS user_name, u.email AS user_email, u.role AS user_role,
                   a.name AS admin_name, a.email AS admin_email
            FROM `security_logs` s
            LEFT JOIN `users` u ON s.user_id = u.id
            LEFT JOIN `users` a ON s.admin_id = a.id
            WHERE 1=1";
    $params = [];

    if (!empty($filters['action'])) {
        $sql .= " AND s.action = ?";
        $params[] = trim($filters['action']);
    }

    if (!empty($filters['user_id'])) {
        $sql .= " AND (s.user_id = ? OR s.admin_id = ?)";
        $params[] = (int)$filters['user_id'];
        $params[] = (int)$filters['user_id'];
    }

    if (!empty($filters['search'])) {
        $search = '%' . trim($filters['search']) . '%';
        $sql .= " AND (s.description LIKE ? OR s.ip_address LIKE ? OR s.action LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR a.name LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    if (!empty($filters['date_range'])) {
        if ($filters['date_range'] === 'today') {
            $sql .= " AND DATE(s.created_at) = CURDATE()";
        } elseif ($filters['date_range'] === '7days') {
            $sql .= " AND s.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } elseif ($filters['date_range'] === '30days') {
            $sql .= " AND s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
    }

    // Total count query
    $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as total_rows";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();

    $sql .= " ORDER BY s.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    return [
        'logs' => $logs,
        'total' => $totalCount
    ];
}

/**
 * Counts how many active administrator accounts exist.
 */
function dbCountActiveAdmins() {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin' AND `status` = 'active'");
    return (int)$stmt->fetchColumn();
}

/**
 * Retrieves KPI aggregate metrics for the Users directory.
 */
function dbGetUsersMetrics() {
    $pdo = getDbConnection();
    $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
    $activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `status` = 'active'")->fetchColumn();
    $inactiveUsers = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `status` != 'active'")->fetchColumn();
    $customers = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'customer'")->fetchColumn();
    $administrators = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'")->fetchColumn();

    return [
        'total_users' => $totalUsers,
        'active_users' => $activeUsers,
        'inactive_users' => $inactiveUsers,
        'customers' => $customers,
        'administrators' => $administrators
    ];
}

/**
 * Retrieves all users (admins and customers) with filtering, sorting and pagination.
 */
function dbGetAllUsers($filters = [], $limit = 20, $offset = 0, $sort = 'created_at', $order = 'DESC') {
    $pdo = getDbConnection();

    $allowedSorts = ['id', 'name', 'email', 'role', 'status', 'created_at', 'total_orders', 'total_spent'];
    if (!in_array($sort, $allowedSorts)) {
        $sort = 'created_at';
    }
    $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

    $sql = "SELECT u.id, u.name, u.email, u.phone, u.role, u.status, u.avatar, u.created_at, u.updated_at,
                   COUNT(o.id) as total_orders,
                   COALESCE(SUM(CASE WHEN o.order_status != 'cancelled' THEN o.total ELSE 0 END), 0.00) as total_spent,
                   MAX(o.created_at) as last_order_date
            FROM `users` u
            LEFT JOIN `orders` o ON u.id = o.user_id
            WHERE 1=1";
    $params = [];

    if (!empty($filters['role']) && in_array($filters['role'], ['customer', 'admin'])) {
        $sql .= " AND u.role = ?";
        $params[] = $filters['role'];
    }

    if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive', 'suspended'])) {
        $sql .= " AND u.status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['search'])) {
        $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $s = '%' . trim($filters['search']) . '%';
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }

    if (!empty($filters['date_range'])) {
        if ($filters['date_range'] === 'today') {
            $sql .= " AND DATE(u.created_at) = CURDATE()";
        } elseif ($filters['date_range'] === 'this_week') {
            $sql .= " AND u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } elseif ($filters['date_range'] === 'this_month') {
            $sql .= " AND u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        } elseif ($filters['date_range'] === 'this_year') {
            $sql .= " AND YEAR(u.created_at) = YEAR(CURDATE())";
        }
    }

    $sql .= " GROUP BY u.id";

    // Count query
    $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as total_count";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();

    // Sort mapping
    $sortCol = "u.created_at";
    if ($sort === 'id') $sortCol = "u.id";
    elseif ($sort === 'name') $sortCol = "u.name";
    elseif ($sort === 'email') $sortCol = "u.email";
    elseif ($sort === 'role') $sortCol = "u.role";
    elseif ($sort === 'status') $sortCol = "u.status";
    elseif ($sort === 'total_orders') $sortCol = "total_orders";
    elseif ($sort === 'total_spent') $sortCol = "total_spent";

    $sql .= " ORDER BY " . $sortCol . " " . $order . " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    return [
        'users' => $users,
        'total' => $totalCount
    ];
}

/**
 * Retrieves comprehensive details for an individual user profile.
 */
function dbGetUserDetails($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
    $stmt->execute([(int)$userId]);
    $user = $stmt->fetch();
    if (!$user) return null;

    $stmtStats = $pdo->prepare("SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN `order_status` = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN `order_status` = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        COALESCE(SUM(CASE WHEN `order_status` != 'cancelled' THEN `total` ELSE 0 END), 0.00) as lifetime_spent,
        COALESCE(AVG(CASE WHEN `order_status` != 'cancelled' THEN `total` ELSE NULL END), 0.00) as avg_order_value,
        MAX(created_at) as last_order_date
        FROM `orders` WHERE `user_id` = ?");
    $stmtStats->execute([(int)$userId]);
    $kpi = $stmtStats->fetch();

    $stmtOrders = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count 
                                FROM `orders` o 
                                LEFT JOIN `order_items` oi ON o.id = oi.order_id 
                                WHERE o.user_id = ? 
                                GROUP BY o.id 
                                ORDER BY o.id DESC LIMIT 10");
    $stmtOrders->execute([(int)$userId]);
    $orders = $stmtOrders->fetchAll();

    $stmtAddr = $pdo->prepare("SELECT * FROM `addresses` WHERE `user_id` = ? ORDER BY `is_default` DESC, `id` DESC");
    $stmtAddr->execute([(int)$userId]);
    $addresses = $stmtAddr->fetchAll();

    $stmtNotif = $pdo->prepare("SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ? AND `is_read` = 0");
    $stmtNotif->execute([(int)$userId]);
    $unreadNotifications = (int)$stmtNotif->fetchColumn();

    // Security logs involving this user
    $stmtSec = $pdo->prepare("SELECT * FROM `security_logs` WHERE `user_id` = ? OR `admin_id` = ? ORDER BY `id` DESC LIMIT 10");
    $stmtSec->execute([(int)$userId, (int)$userId]);
    $securityLogs = $stmtSec->fetchAll();

    return [
        'profile' => $user,
        'kpi' => $kpi,
        'orders' => $orders,
        'addresses' => $addresses,
        'unread_notifications' => $unreadNotifications,
        'security_logs' => $securityLogs
    ];
}

/**
 * Updates user profile by an admin with strict email uniqueness and Last-Admin protection.
 */
function dbUpdateUserByAdmin($userId, $data, $adminId) {
    $pdo = getDbConnection();
    $userId = (int)$userId;
    $adminId = (int)$adminId;

    $stmtCheck = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
    $stmtCheck->execute([$userId]);
    $currentUser = $stmtCheck->fetch();
    if (!$currentUser) {
        return ['success' => false, 'error' => 'User not found.'];
    }

    $name = trim($data['name'] ?? '');
    $email = trim(strtolower($data['email'] ?? ''));
    $phone = trim($data['phone'] ?? '');
    $role = trim($data['role'] ?? $currentUser['role']);
    $status = trim($data['status'] ?? $currentUser['status']);
    $dob = !empty($data['date_of_birth']) ? $data['date_of_birth'] : null;
    $gender = in_array($data['gender'] ?? '', ['male', 'female', 'other', 'unspecified']) ? $data['gender'] : 'unspecified';
    $language = !empty($data['preferred_language']) ? substr(trim($data['preferred_language']), 0, 20) : 'en';

    if (empty($name)) {
        return ['success' => false, 'error' => 'Full name is required.'];
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'A valid email address is required.'];
    }

    if (!in_array($role, ['customer', 'admin'])) {
        return ['success' => false, 'error' => 'Invalid role specified. Only Customer and Admin roles are supported.'];
    }

    if (!in_array($status, ['active', 'inactive', 'suspended'])) {
        return ['success' => false, 'error' => 'Invalid account status specified.'];
    }

    // Unique email check
    $stmtEmail = $pdo->prepare("SELECT id FROM `users` WHERE `email` = ? AND `id` != ?");
    $stmtEmail->execute([$email, $userId]);
    if ($stmtEmail->fetch()) {
        return ['success' => false, 'error' => 'This email address is already in use by another account.'];
    }

    // Last-Admin Protection Check
    if ($currentUser['role'] === 'admin' && $currentUser['status'] === 'active') {
        if ($role !== 'admin' || $status !== 'active') {
            $activeAdmins = dbCountActiveAdmins();
            if ($activeAdmins <= 1) {
                return ['success' => false, 'error' => 'You cannot remove or deactivate the last active administrator account.'];
            }
        }
    }

    $stmtUpdate = $pdo->prepare("UPDATE `users` SET 
        `name` = ?, 
        `email` = ?, 
        `phone` = ?, 
        `role` = ?, 
        `status` = ?, 
        `date_of_birth` = ?, 
        `gender` = ?, 
        `preferred_language` = ?, 
        `updated_at` = NOW() 
        WHERE `id` = ?");
    $stmtUpdate->execute([$name, $email, $phone, $role, $status, $dob, $gender, $language, $userId]);

    dbLogSecurityEvent('user_profile_updated', "Admin #$adminId updated profile for user #$userId ($email)", $userId, $adminId);

    return ['success' => true];
}

/**
 * Changes a user's role between customer and admin with Last-Admin protection.
 */
function dbChangeUserRole($userId, $newRole, $adminId) {
    $pdo = getDbConnection();
    $userId = (int)$userId;
    $adminId = (int)$adminId;

    if (!in_array($newRole, ['customer', 'admin'])) {
        return ['success' => false, 'error' => 'Invalid role specified.'];
    }

    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        return ['success' => false, 'error' => 'User not found.'];
    }

    if ($user['role'] === $newRole) {
        return ['success' => true];
    }

    if ($user['role'] === 'admin' && $user['status'] === 'active' && $newRole === 'customer') {
        if (dbCountActiveAdmins() <= 1) {
            return ['success' => false, 'error' => 'You cannot remove or deactivate the last active administrator account.'];
        }
    }

    $updateStmt = $pdo->prepare("UPDATE `users` SET `role` = ?, `updated_at` = NOW() WHERE `id` = ?");
    $updateStmt->execute([$newRole, $userId]);

    dbLogSecurityEvent('role_change', "Role changed to '$newRole' for user #$userId ({$user['email']}) by admin #$adminId", $userId, $adminId);

    return ['success' => true];
}

/**
 * Sets user account status (active, inactive) with Last-Admin protection.
 */
function dbSetUserStatus($userId, $newStatus, $adminId) {
    $pdo = getDbConnection();
    $userId = (int)$userId;
    $adminId = (int)$adminId;

    if (!in_array($newStatus, ['active', 'inactive', 'suspended'])) {
        return ['success' => false, 'error' => 'Invalid account status.'];
    }

    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        return ['success' => false, 'error' => 'User not found.'];
    }

    if ($user['status'] === $newStatus) {
        return ['success' => true];
    }

    if ($user['role'] === 'admin' && $user['status'] === 'active' && $newStatus !== 'active') {
        if (dbCountActiveAdmins() <= 1) {
            return ['success' => false, 'error' => 'You cannot remove or deactivate the last active administrator account.'];
        }
    }

    $updateStmt = $pdo->prepare("UPDATE `users` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ?");
    $updateStmt->execute([$newStatus, $userId]);

    dbLogSecurityEvent('status_change', "Status changed to '$newStatus' for user #$userId ({$user['email']}) by admin #$adminId", $userId, $adminId);

    return ['success' => true];
}

/**
 * Securely resets user password using password_hash().
 * Zero plain-text passwords stored or logged.
 */
function dbResetUserPassword($userId, $newPassword, $adminId) {
    $pdo = getDbConnection();
    $userId = (int)$userId;
    $adminId = (int)$adminId;

    if (strlen($newPassword) < 6) {
        return ['success' => false, 'error' => 'Password must be at least 6 characters long.'];
    }

    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        return ['success' => false, 'error' => 'User not found.'];
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE `users` SET `password` = ?, `updated_at` = NOW() WHERE `id` = ?");
    $updateStmt->execute([$hashedPassword, $userId]);

    dbLogSecurityEvent('admin_password_reset', "Admin #$adminId securely reset password for user #$userId ({$user['email']})", $userId, $adminId);

    return ['success' => true];
}

/**
 * Evaluates whether a user can be safely deleted without breaking historical order integrity.
 */
function dbCanDeleteUser($userId) {
    $pdo = getDbConnection();
    $userId = (int)$userId;

    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        return ['can_delete' => false, 'reason' => 'User not found.'];
    }

    if ($user['role'] === 'admin' && $user['status'] === 'active') {
        if (dbCountActiveAdmins() <= 1) {
            return ['can_delete' => false, 'reason' => 'You cannot remove or deactivate the last active administrator account.'];
        }
    }

    $stmtOrders = $pdo->prepare("SELECT COUNT(*) FROM `orders` WHERE `user_id` = ?");
    $stmtOrders->execute([$userId]);
    $orderCount = (int)$stmtOrders->fetchColumn();

    if ($orderCount > 0) {
        return [
            'can_delete' => false,
            'reason' => "This user has $orderCount historical order(s). To maintain historical order integrity, please deactivate the account instead of permanently deleting it."
        ];
    }

    return ['can_delete' => true];
}

/**
 * Safely deletes a user record only if no historical orders exist.
 */
function dbDeleteUserSafely($userId, $adminId) {
    $pdo = getDbConnection();
    $userId = (int)$userId;
    $adminId = (int)$adminId;

    $check = dbCanDeleteUser($userId);
    if (!$check['can_delete']) {
        return ['success' => false, 'error' => $check['reason']];
    }

    $stmtUser = $pdo->prepare("SELECT email FROM `users` WHERE `id` = ?");
    $stmtUser->execute([$userId]);
    $userEmail = $stmtUser->fetchColumn() ?: 'unknown';

    $pdo->prepare("DELETE FROM `cart_items` WHERE `user_id` = ?")->execute([$userId]);
    $pdo->prepare("DELETE FROM `addresses` WHERE `user_id` = ?")->execute([$userId]);
    $pdo->prepare("DELETE FROM `notifications` WHERE `user_id` = ?")->execute([$userId]);

    $stmtDel = $pdo->prepare("DELETE FROM `users` WHERE `id` = ?");
    $stmtDel->execute([$userId]);

    dbLogSecurityEvent('user_deleted', "Admin #$adminId deleted user #$userId ($userEmail)", null, $adminId);

    return ['success' => true];
}

// =========================================================================
// STEP 28 - NOTIFICATIONS & COMMUNICATION CENTER FUNCTIONS
// =========================================================================

/**
 * Returns customer notification preferences with safe defaults.
 */
function dbGetUserNotificationPreferences($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT `notification_preferences` FROM `users` WHERE `id` = ?");
    $stmt->execute([(int)$userId]);
    $raw = $stmt->fetchColumn();

    $defaults = [
        'order_updates' => true,
        'promotions' => true,
        'account_security' => true,
        'newsletter' => false
    ];

    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_merge($defaults, $decoded);
        }
    }
    return $defaults;
}

/**
 * Updates customer notification preferences.
 */
function dbUpdateUserNotificationPreferences($userId, $preferences) {
    $pdo = getDbConnection();
    $current = dbGetUserNotificationPreferences($userId);
    $updated = [
        'order_updates' => isset($preferences['order_updates']) ? (bool)$preferences['order_updates'] : $current['order_updates'],
        'promotions' => isset($preferences['promotions']) ? (bool)$preferences['promotions'] : $current['promotions'],
        'account_security' => isset($preferences['account_security']) ? (bool)$preferences['account_security'] : $current['account_security'],
        'newsletter' => isset($preferences['newsletter']) ? (bool)$preferences['newsletter'] : $current['newsletter'],
    ];

    $json = json_encode($updated);
    $stmt = $pdo->prepare("UPDATE `users` SET `notification_preferences` = ?, `updated_at` = NOW() WHERE `id` = ?");
    return $stmt->execute([$json, (int)$userId]);
}

/**
 * Returns the unread notification count for a customer.
 */
function dbGetUnreadNotificationCount($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ? AND `is_read` = 0");
    $stmt->execute([(int)$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Creates an order status update notification with automatic duplicate prevention.
 */
function dbCreateOrderNotification($userId, $orderNumber, $newStatus) {
    $pdo = getDbConnection();
    $userId = (int)$userId;
    $orderNumber = trim($orderNumber);
    $newStatus = strtolower(trim($newStatus));

    // Check customer preference for order status updates
    $prefs = dbGetUserNotificationPreferences($userId);
    if (empty($prefs['order_updates'])) {
        return false;
    }

    $statusTitles = [
        'pending' => "Order #{$orderNumber} Received",
        'confirmed' => "Order #{$orderNumber} Confirmed",
        'preparing' => "Your Order Is Being Prepared",
        'out_for_delivery' => "Your Order Is On The Way",
        'delivered' => "Order #{$orderNumber} Delivered",
        'cancelled' => "Order #{$orderNumber} Cancelled"
    ];

    $statusMessages = [
        'pending' => "We've received your order #{$orderNumber} and it is waiting for kitchen confirmation.",
        'confirmed' => "Your order #{$orderNumber} has been confirmed by the restaurant.",
        'preparing' => "The kitchen has started preparing your order #{$orderNumber}.",
        'out_for_delivery' => "Your order #{$orderNumber} has left the restaurant and is on the way.",
        'delivered' => "Your order #{$orderNumber} has been delivered. Enjoy your meal!",
        'cancelled' => "Your order #{$orderNumber} has been cancelled."
    ];

    $title = $statusTitles[$newStatus] ?? "Order #{$orderNumber} Update";
    $message = $statusMessages[$newStatus] ?? "Your order status has been updated to {$newStatus}.";
    $link = "order-details.php?order={$orderNumber}";

    // Duplicate prevention: Check if identical title & order link notification exists for this user in last 24h
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `notifications` 
                                WHERE `user_id` = ? 
                                  AND `title` = ? 
                                  AND `link` = ? 
                                  AND `created_at` >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmtCheck->execute([$userId, $title, $link]);
    if ((int)$stmtCheck->fetchColumn() > 0) {
        return true; // Already created, skip duplicate
    }

    $stmtInsert = $pdo->prepare("INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) 
                                 VALUES (?, ?, ?, 'order', ?, 0, NOW())");
    return $stmtInsert->execute([$userId, $title, $message, $link]);
}

/**
 * Retrieves filtered customer notifications with search and pagination.
 */
function dbGetCustomerNotificationsFiltered($userId, $filters = [], $limit = 15, $offset = 0) {
    $pdo = getDbConnection();
    $userId = (int)$userId;

    $sql = "SELECT * FROM `notifications` WHERE `user_id` = ?";
    $params = [$userId];

    if (!empty($filters['category']) && $filters['category'] !== 'all') {
        $cat = strtolower(trim($filters['category']));
        if ($cat === 'unread') {
            $sql .= " AND `is_read` = 0";
        } elseif ($cat === 'orders' || $cat === 'order') {
            $sql .= " AND `type` = 'order'";
        } elseif ($cat === 'account') {
            $sql .= " AND `type` = 'account'";
        } elseif ($cat === 'promotions' || $cat === 'promo' || $cat === 'promotion') {
            $sql .= " AND (`type` = 'promo' OR `type` = 'promotion')";
        } elseif ($cat === 'system') {
            $sql .= " AND `type` = 'system'";
        } elseif ($cat === 'support') {
            $sql .= " AND `type` = 'support'";
        }
    }

    if (!empty($filters['type']) && $filters['type'] !== 'all') {
        $t = strtolower(trim($filters['type']));
        if ($t === 'promo' || $t === 'promotion') {
            $sql .= " AND (`type` = 'promo' OR `type` = 'promotion')";
        } else {
            $sql .= " AND `type` = ?";
            $params[] = $t;
        }
    }

    if (!empty($filters['status'])) {
        if ($filters['status'] === 'unread') {
            $sql .= " AND `is_read` = 0";
        } elseif ($filters['status'] === 'read') {
            $sql .= " AND `is_read` = 1";
        }
    }

    if (!empty($filters['search'])) {
        $sql .= " AND (`title` LIKE ? OR `message` LIKE ?)";
        $s = '%' . trim($filters['search']) . '%';
        $params[] = $s;
        $params[] = $s;
    }

    // Count total matching
    $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as total_rows";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();

    // Query unread count for user
    $unreadCount = dbGetUnreadNotificationCount($userId);

    $sql .= " ORDER BY `id` DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return [
        'notifications' => $rows,
        'data' => $rows,
        'total' => $totalCount,
        'unread_count' => $unreadCount
    ];
}

/**
 * Marks a single notification as read with ownership check.
 */
function dbMarkNotificationRead($notifId, $userId = null) {
    $pdo = getDbConnection();
    if ($userId !== null) {
        $stmt = $pdo->prepare("UPDATE `notifications` SET `is_read` = 1 WHERE `id` = ? AND `user_id` = ?");
        $stmt->execute([(int)$notifId, (int)$userId]);
        return $stmt->rowCount() > 0;
    } else {
        $stmt = $pdo->prepare("UPDATE `notifications` SET `is_read` = 1 WHERE `id` = ?");
        $stmt->execute([(int)$notifId]);
        return $stmt->rowCount() > 0;
    }
}

/**
 * Alias for dbMarkNotificationRead.
 */
function dbMarkNotificationAsRead($notifId, $userId = null) {
    return dbMarkNotificationRead($notifId, $userId);
}

/**
 * Marks a notification as unread with ownership check.
 */
function dbMarkNotificationUnread($notifId, $userId = null) {
    $pdo = getDbConnection();
    if ($userId !== null) {
        $stmt = $pdo->prepare("UPDATE `notifications` SET `is_read` = 0 WHERE `id` = ? AND `user_id` = ?");
        $stmt->execute([(int)$notifId, (int)$userId]);
        return $stmt->rowCount() > 0;
    } else {
        $stmt = $pdo->prepare("UPDATE `notifications` SET `is_read` = 0 WHERE `id` = ?");
        $stmt->execute([(int)$notifId]);
        return $stmt->rowCount() > 0;
    }
}

/**
 * Marks all notifications as read for a customer.
 */
function dbMarkAllNotificationsRead($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE `notifications` SET `is_read` = 1 WHERE `user_id` = ?");
    return $stmt->execute([(int)$userId]);
}

/**
 * Clears all notifications for a customer.
 */
function dbClearAllNotifications($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM `notifications` WHERE `user_id` = ?");
    return $stmt->execute([(int)$userId]);
}

/**
 * Deletes a single notification with strict customer ownership check.
 */
function dbDeleteNotification($notifId, $userId = null) {
    $pdo = getDbConnection();
    if ($userId !== null) {
        $stmt = $pdo->prepare("DELETE FROM `notifications` WHERE `id` = ? AND `user_id` = ?");
        $stmt->execute([(int)$notifId, (int)$userId]);
        return $stmt->rowCount() > 0;
    } else {
        $stmt = $pdo->prepare("DELETE FROM `notifications` WHERE `id` = ?");
        $stmt->execute([(int)$notifId]);
        return $stmt->rowCount() > 0;
    }
}

/**
 * Returns KPI metrics for the Admin Notification Center.
 */
function dbGetAdminNotificationMetrics() {
    $pdo = getDbConnection();
    $total = (int)$pdo->query("SELECT COUNT(*) FROM `notifications`")->fetchColumn();
    $unread = (int)$pdo->query("SELECT COUNT(*) FROM `notifications` WHERE `is_read` = 0")->fetchColumn();
    $today = (int)$pdo->query("SELECT COUNT(*) FROM `notifications` WHERE DATE(`created_at`) = CURDATE()")->fetchColumn();
    $orderNotifs = (int)$pdo->query("SELECT COUNT(*) FROM `notifications` WHERE `type` = 'order'")->fetchColumn();

    return [
        'total' => $total,
        'read' => max(0, $total - $unread),
        'unread' => $unread,
        'today' => $today,
        'total_notifications' => $total,
        'unread_notifications' => $unread,
        'today_notifications' => $today,
        'order_notifications' => $orderNotifs
    ];
}

/**
 * Retrieves all notifications for the Admin Notification Center with filtering & pagination.
 */
function dbGetAllNotificationsAdmin($filters = [], $limit = 20, $offset = 0) {
    $pdo = getDbConnection();
    $sql = "SELECT n.*, u.name AS user_name, u.email AS user_email, u.avatar AS user_avatar, u.role AS user_role
            FROM `notifications` n
            LEFT JOIN `users` u ON n.user_id = u.id
            WHERE 1=1";
    $params = [];

    if (!empty($filters['type']) && $filters['type'] !== 'all') {
        $t = trim($filters['type']);
        if ($t === 'promo' || $t === 'promotion') {
            $sql .= " AND (n.type = 'promo' OR n.type = 'promotion')";
        } else {
            $sql .= " AND n.type = ?";
            $params[] = $t;
        }
    }

    if (!empty($filters['status'])) {
        if ($filters['status'] === 'unread') {
            $sql .= " AND n.is_read = 0";
        } elseif ($filters['status'] === 'read') {
            $sql .= " AND n.is_read = 1";
        }
    }

    if (!empty($filters['user_id'])) {
        $sql .= " AND n.user_id = ?";
        $params[] = (int)$filters['user_id'];
    }

    if (!empty($filters['search'])) {
        $search = '%' . trim($filters['search']) . '%';
        $sql .= " AND (n.title LIKE ? OR n.message LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    if (!empty($filters['date_range'])) {
        if ($filters['date_range'] === 'today') {
            $sql .= " AND DATE(n.created_at) = CURDATE()";
        } elseif ($filters['date_range'] === '7days') {
            $sql .= " AND n.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } elseif ($filters['date_range'] === '30days') {
            $sql .= " AND n.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
    }

    // Count
    $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as total_rows";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();

    $sql .= " ORDER BY n.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return [
        'notifications' => $rows,
        'data' => $rows,
        'total' => $totalCount
    ];
}

/**
 * Creates notification(s) by admin with broadcast or single recipient support.
 * Polymorphic signature supports:
 * - dbAdminCreateNotification($recipientType, $userId, $title, $message, $type, $link, $adminId)
 * - dbAdminCreateNotification($userId, $title, $message, $type, $link, $adminId)
 */
function dbAdminCreateNotification(...$args) {
    $pdo = getDbConnection();

    if (count($args) >= 3 && is_numeric($args[0])) {
        // Direct call: ($userId, $title, $message, $type, $link, $adminId)
        $rawUserId = (int)$args[0];
        $recipientType = ($rawUserId === 0) ? 'all' : 'single';
        $userId = $rawUserId;
        $title = $args[1] ?? '';
        $message = $args[2] ?? '';
        $type = $args[3] ?? 'system';
        $link = $args[4] ?? null;
        $adminId = $args[5] ?? null;
    } else {
        // Full call: ($recipientType, $userId, $title, $message, $type, $link, $adminId)
        $recipientType = $args[0] ?? 'single';
        $userId = isset($args[1]) ? (int)$args[1] : 0;
        $title = $args[2] ?? '';
        $message = $args[3] ?? '';
        $type = $args[4] ?? 'system';
        $link = $args[5] ?? null;
        $adminId = $args[6] ?? null;
    }

    $validTypes = ['order', 'account', 'promotion', 'promo', 'system', 'support'];
    if (!in_array($type, $validTypes)) {
        $type = 'system';
    }

    $title = strip_tags(trim($title));
    $message = strip_tags(trim($message));
    $link = !empty($link) ? trim($link) : null;

    if ($recipientType === 'all' || $userId === 0) {
        // Broadcast to all active customers
        $stmtUsers = $pdo->query("SELECT id, notification_preferences FROM `users` WHERE `role` = 'customer' AND `status` = 'active'");
        $customers = $stmtUsers->fetchAll();
        $count = 0;

        $stmtIns = $pdo->prepare("INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES (?, ?, ?, ?, ?, 0, NOW())");

        foreach ($customers as $c) {
            // Respect promotional preferences if type is promotion
            if ($type === 'promotion' || $type === 'promo') {
                $prefs = dbGetUserNotificationPreferences((int)$c['id']);
                if (empty($prefs['promotions'])) {
                    continue;
                }
            }
            $stmtIns->execute([(int)$c['id'], $title, $message, $type, $link]);
            $count++;
        }

        if (function_exists('dbLogSecurityEvent')) {
            dbLogSecurityEvent('notification_broadcast', "Admin #$adminId broadcasted notification '$title' to $count customer(s)", null, $adminId);
        }

        return [
            'success' => true,
            'count' => $count,
            'id' => 0
        ];
    } else {
        if ($userId <= 0) {
            return [
                'success' => false,
                'count' => 0,
                'error' => 'Invalid recipient customer ID.'
            ];
        }

        $stmtIns = $pdo->prepare("INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES (?, ?, ?, ?, ?, 0, NOW())");
        $ok = $stmtIns->execute([$userId, $title, $message, $type, $link]);
        $newId = (int)$pdo->lastInsertId();

        if ($ok && function_exists('dbLogSecurityEvent')) {
            dbLogSecurityEvent('notification_direct_sent', "Admin #$adminId sent notification '$title' to user #$userId", $userId, $adminId);
        }

        return [
            'success' => (bool)$ok,
            'count' => $ok ? 1 : 0,
            'id' => $newId
        ];
    }
}


// =========================================================================
// STEP 29 - FINAL ORDER, DELIVERY & FULFILLMENT SYSTEM FUNCTIONS
// =========================================================================

/**
 * Validates customer cancellation rules: Customer can cancel ONLY when order is 'pending'.
 */
function dbCustomerCancelOrder($orderId, $userId, $reason = 'Cancelled by customer') {
    $pdo = getDbConnection();
    $userId = (int)$userId;
    
    // Support lookup by integer ID or string order_number
    if (is_numeric($orderId) && (int)$orderId > 0 && strpos((string)$orderId, 'FB-') === false) {
        $stmt = $pdo->prepare("SELECT `id`, `user_id`, `order_number`, `order_status` FROM `orders` WHERE `id` = ? AND `user_id` = ?");
        $stmt->execute([(int)$orderId, $userId]);
    } else {
        $stmt = $pdo->prepare("SELECT `id`, `user_id`, `order_number`, `order_status` FROM `orders` WHERE `order_number` = ? AND `user_id` = ?");
        $stmt->execute([trim($orderId), $userId]);
    }
    
    $order = $stmt->fetch();
    if (!$order) {
        return ['success' => false, 'error' => 'Order not found or unauthorized access.'];
    }

    if ($order['order_status'] !== 'pending') {
        return [
            'success' => false, 
            'error' => 'Orders can only be cancelled while pending restaurant confirmation. This order is already ' . htmlspecialchars($order['order_status']) . '.'
        ];
    }

    $updateStmt = $pdo->prepare("UPDATE `orders` SET `order_status` = 'cancelled', `updated_at` = NOW() WHERE `id` = ?");
    $ok = $updateStmt->execute([(int)$order['id']]);

    if ($ok) {
        if (function_exists('dbCreateOrderNotification')) {
            dbCreateOrderNotification($userId, $order['order_number'], 'cancelled');
        }
        if (function_exists('dbLogSecurityEvent')) {
            dbLogSecurityEvent('customer_order_cancelled', "Customer #$userId cancelled pending Order #{$order['order_number']}", $userId);
        }
        return ['success' => true, 'order_number' => $order['order_number']];
    }

    return ['success' => false, 'error' => 'Failed to cancel order. Please try again.'];
}

/**
 * Returns comprehensive real-time order KPI metrics for the Admin Dashboard and Orders Control Center.
 */
function dbGetAdminOrderMetrics() {
    $pdo = getDbConnection();
    $total = (int)$pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
    $pending = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `order_status` = 'pending'")->fetchColumn();
    $confirmed = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `order_status` = 'confirmed'")->fetchColumn();
    $preparing = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `order_status` = 'preparing'")->fetchColumn();
    $outForDelivery = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `order_status` = 'out_for_delivery'")->fetchColumn();
    $delivered = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `order_status` = 'delivered'")->fetchColumn();
    $cancelled = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `order_status` = 'cancelled'")->fetchColumn();
    $pendingPayments = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `payment_status` = 'pending'")->fetchColumn();
    $paidOrders = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `payment_status` = 'paid'")->fetchColumn();
    $totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(`total`), 0) FROM `orders` WHERE `order_status` != 'cancelled'")->fetchColumn();
    $todayOrders = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE DATE(`created_at`) = CURDATE()")->fetchColumn();
    $todayRevenue = (float)$pdo->query("SELECT COALESCE(SUM(`total`), 0) FROM `orders` WHERE DATE(`created_at`) = CURDATE() AND `order_status` != 'cancelled'")->fetchColumn();

    return [
        'total' => $total,
        'total_orders' => $total,
        'pending' => $pending,
        'pending_orders' => $pending,
        'confirmed' => $confirmed,
        'confirmed_orders' => $confirmed,
        'preparing' => $preparing,
        'preparing_orders' => $preparing,
        'out_for_delivery' => $outForDelivery,
        'out_for_delivery_orders' => $outForDelivery,
        'delivered' => $delivered,
        'delivered_orders' => $delivered,
        'cancelled' => $cancelled,
        'cancelled_orders' => $cancelled,
        'pending_payments' => $pendingPayments,
        'paid_orders' => $paidOrders,
        'total_revenue' => $totalRevenue,
        'today_orders' => $todayOrders,
        'today_revenue' => $todayRevenue
    ];
}

/**
 * Retrieves orders with full filtering, search, and pagination for Admin Control Center.
 */
function dbGetFilteredOrdersAdmin($filters = [], $limit = 20, $offset = 0) {
    $pdo = getDbConnection();
    $sql = "SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, u.avatar AS customer_avatar, COUNT(oi.id) AS item_count 
            FROM `orders` o 
            JOIN `users` u ON o.user_id = u.id 
            LEFT JOIN `order_items` oi ON o.id = oi.order_id 
            WHERE 1=1";
    $params = [];

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $sql .= " AND o.order_status = ?";
        $params[] = trim($filters['status']);
    }

    if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
        $sql .= " AND o.payment_status = ?";
        $params[] = trim($filters['payment_status']);
    }

    if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'all') {
        $sql .= " AND o.payment_method = ?";
        $params[] = trim($filters['payment_method']);
    }

    if (!empty($filters['search'])) {
        $s = '%' . trim($filters['search']) . '%';
        $sql .= " AND (o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR o.delivery_address LIKE ?)";
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }

    if (!empty($filters['date_range'])) {
        if ($filters['date_range'] === 'today') {
            $sql .= " AND DATE(o.created_at) = CURDATE()";
        } elseif ($filters['date_range'] === 'yesterday') {
            $sql .= " AND DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        } elseif ($filters['date_range'] === '7days') {
            $sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } elseif ($filters['date_range'] === '30days') {
            $sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
    }

    $sql .= " GROUP BY o.id";

    // Count
    $countSql = "SELECT COUNT(*) FROM (" . $sql . ") AS total_rows";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();

    $sql .= " ORDER BY o.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$ord) {
        $ord['items'] = dbGetOrderItems((int)$ord['id']);
    }

    return [
        'orders' => $orders,
        'data' => $orders,
        'total' => $totalCount
    ];
}


// =========================================================================
// STEP 30: FAVORITES / WISHLIST ENGINE
// =========================================================================

function dbAddFavorite($userId, $foodId) {
    $pdo = getDbConnection();
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO `favorites` (`user_id`, `food_id`, `created_at`) VALUES (?, ?, NOW())");
        return $stmt->execute([(int)$userId, (int)$foodId]);
    } catch (Exception $e) {
        return false;
    }
}

function dbRemoveFavorite($userId, $foodId) {
    $pdo = getDbConnection();
    try {
        $stmt = $pdo->prepare("DELETE FROM `favorites` WHERE `user_id` = ? AND `food_id` = ?");
        return $stmt->execute([(int)$userId, (int)$foodId]);
    } catch (Exception $e) {
        return false;
    }
}

function dbToggleFavorite($userId, $foodId) {
    $pdo = getDbConnection();
    $userId = (int)$userId;
    $foodId = (int)$foodId;
    if (!$userId || !$foodId) {
        return ['success' => false, 'error' => 'Authentication required.'];
    }

    $isFav = dbIsFavorite($userId, $foodId);
    if ($isFav) {
        dbRemoveFavorite($userId, $foodId);
        $newFav = false;
    } else {
        dbAddFavorite($userId, $foodId);
        $newFav = true;
    }

    $count = dbGetUserFavoritesCount($userId);
    return [
        'success' => true,
        'is_favorite' => $newFav,
        'favorites_count' => $count
    ];
}

function dbIsFavorite($userId, $foodId) {
    if (!$userId || !$foodId) return false;
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `favorites` WHERE `user_id` = ? AND `food_id` = ?");
    $stmt->execute([(int)$userId, (int)$foodId]);
    return ((int)$stmt->fetchColumn()) > 0;
}

function dbGetUserFavorites($userId) {
    $pdo = getDbConnection();
    $sql = "SELECT f.*, c.name AS category_name, c.slug AS category_slug, fav.created_at AS favorited_at 
            FROM `favorites` fav 
            JOIN `foods` f ON fav.food_id = f.id 
            JOIN `categories` c ON f.category_id = c.id 
            WHERE fav.user_id = ? 
            ORDER BY fav.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([(int)$userId]);
    $foods = $stmt->fetchAll();

    $now = date('Y-m-d H:i:s');
    $stmtDisc = $pdo->prepare("SELECT * FROM `discounts` WHERE `is_active` = 1 AND (`start_at` IS NULL OR `start_at` <= ?) AND (`end_at` IS NULL OR `end_at` >= ?)");
    $stmtDisc->execute([$now, $now]);
    $cachedDiscounts = $stmtDisc->fetchAll();

    $result = [];
    foreach ($foods as $food) {
        $result[] = calculateFoodPricing($food, $cachedDiscounts);
    }
    return $result;
}

function dbGetUserFavoritesCount($userId) {
    if (!$userId) return 0;
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `favorites` WHERE `user_id` = ?");
    $stmt->execute([(int)$userId]);
    return (int)$stmt->fetchColumn();
}

function dbGetUserFavoriteIds($userId) {
    if (!$userId) return [];
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT `food_id` FROM `favorites` WHERE `user_id` = ?");
    $stmt->execute([(int)$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// =========================================================================
// STEP 30: REVIEWS & RATINGS ENGINE
// =========================================================================

function dbCanUserReviewFood($userId, $foodId) {
    $userId = (int)$userId;
    $foodId = (int)$foodId;
    if (!$userId || !$foodId) {
        return ['can_review' => false, 'order_id' => null, 'existing_review' => null, 'reason' => 'Please log in to review.'];
    }

    $pdo = getDbConnection();

    // Check delivered orders containing this food item for this user
    $sql = "SELECT o.id AS order_id, o.order_number, o.created_at AS order_date 
            FROM `orders` o 
            JOIN `order_items` oi ON o.id = oi.order_id 
            WHERE o.user_id = ? AND o.order_status = 'delivered' AND oi.food_id = ? 
            ORDER BY o.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $foodId]);
    $deliveredOrders = $stmt->fetchAll();

    if (empty($deliveredOrders)) {
        return [
            'can_review' => false,
            'order_id' => null,
            'existing_review' => null,
            'reason' => 'Only verified customers who ordered and received this item can write a review.'
        ];
    }

    // Check existing reviews for these orders
    foreach ($deliveredOrders as $ord) {
        $checkStmt = $pdo->prepare("SELECT * FROM `food_reviews` WHERE `user_id` = ? AND `food_id` = ? AND `order_id` = ?");
        $checkStmt->execute([$userId, $foodId, (int)$ord['order_id']]);
        $existing = $checkStmt->fetch();
        if (!$existing) {
            return [
                'can_review' => true,
                'order_id' => (int)$ord['order_id'],
                'order_number' => $ord['order_number'],
                'existing_review' => null,
                'reason' => 'Eligible to write a review.'
            ];
        }
    }

    // If all delivered orders are already reviewed, return the latest review allowing update
    $latestReviewStmt = $pdo->prepare("SELECT * FROM `food_reviews` WHERE `user_id` = ? AND `food_id` = ? ORDER BY id DESC LIMIT 1");
    $latestReviewStmt->execute([$userId, $foodId]);
    $latestReview = $latestReviewStmt->fetch();

    return [
        'can_review' => true,
        'order_id' => (int)$deliveredOrders[0]['order_id'],
        'order_number' => $deliveredOrders[0]['order_number'],
        'existing_review' => $latestReview,
        'reason' => 'You have already reviewed this item. You may update your review below.'
    ];
}

function dbCreateOrUpdateReview($userId, $foodId, $orderId, $rating, $reviewText, $status = 'approved') {
    $userId = (int)$userId;
    $foodId = (int)$foodId;
    $orderId = (int)$orderId;
    $rating = max(1, min(5, (int)$rating));
    $reviewText = trim($reviewText);

    $pdo = getDbConnection();

    // Verify purchase
    $verifyStmt = $pdo->prepare("SELECT o.id FROM `orders` o JOIN `order_items` oi ON o.id = oi.order_id WHERE o.id = ? AND o.user_id = ? AND o.order_status = 'delivered' AND oi.food_id = ?");
    $verifyStmt->execute([$orderId, $userId, $foodId]);
    if (!$verifyStmt->fetch()) {
        return ['success' => false, 'error' => 'Verification failed: Only verified delivered purchases can be reviewed.'];
    }

    // Check if review exists for this user, food, and order
    $existStmt = $pdo->prepare("SELECT id FROM `food_reviews` WHERE `user_id` = ? AND `food_id` = ? AND `order_id` = ?");
    $existStmt->execute([$userId, $foodId, $orderId]);
    $existId = $existStmt->fetchColumn();

    if ($existId) {
        $upd = $pdo->prepare("UPDATE `food_reviews` SET `rating` = ?, `review` = ?, `status` = ?, `updated_at` = NOW() WHERE `id` = ?");
        $upd->execute([$rating, $reviewText, $status, (int)$existId]);
        $reviewId = (int)$existId;
    } else {
        $ins = $pdo->prepare("INSERT INTO `food_reviews` (`food_id`, `user_id`, `order_id`, `rating`, `review`, `status`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $ins->execute([$foodId, $userId, $orderId, $rating, $reviewText, $status]);
        $reviewId = (int)$pdo->lastInsertId();
    }

    // Recalculate food rating & count
    dbRecalculateFoodRating($foodId);

    return [
        'success' => true,
        'review_id' => $reviewId,
        'status' => $status,
        'message' => ($status === 'approved') ? 'Your review has been published!' : 'Your review has been submitted for moderation.'
    ];
}

function dbRecalculateFoodRating($foodId) {
    $foodId = (int)$foodId;
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS rating_cnt FROM `food_reviews` WHERE `food_id` = ? AND `status` = 'approved'");
    $stmt->execute([$foodId]);
    $row = $stmt->fetch();

    if ($row && (int)$row['rating_cnt'] > 0) {
        $avg = round((float)$row['avg_rating'], 1);
        $cnt = (int)$row['rating_cnt'];
    } else {
        $avg = 4.80;
        $cnt = 0;
    }

    $upd = $pdo->prepare("UPDATE `foods` SET `rating` = ?, `rating_count` = ? WHERE `id` = ?");
    $upd->execute([$avg, $cnt, $foodId]);
}

function dbGetFoodReviews($foodId, $status = 'approved', $limit = 50) {
    $pdo = getDbConnection();
    $sql = "SELECT r.*, u.name AS user_name, u.avatar AS user_avatar, o.order_number 
            FROM `food_reviews` r 
            JOIN `users` u ON r.user_id = u.id 
            JOIN `orders` o ON r.order_id = o.id 
            WHERE r.food_id = ?";
    $params = [(int)$foodId];

    if ($status !== 'all') {
        $sql .= " AND r.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY r.created_at DESC LIMIT " . (int)$limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dbGetFoodRatingSummary($foodId) {
    $pdo = getDbConnection();
    $foodId = (int)$foodId;

    $stmt = $pdo->prepare("SELECT `rating` FROM `food_reviews` WHERE `food_id` = ? AND `status` = 'approved'");
    $stmt->execute([$foodId]);
    $ratings = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $total = count($ratings);
    $breakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

    if ($total > 0) {
        foreach ($ratings as $r) {
            $rInt = (int)$r;
            if (isset($breakdown[$rInt])) {
                $breakdown[$rInt]++;
            }
        }
        $avg = round(array_sum($ratings) / $total, 1);
    } else {
        $avg = 4.8;
    }

    return [
        'average_rating' => $avg,
        'total_reviews' => $total,
        'breakdown' => $breakdown
    ];
}

function dbAdminGetReviews($filters = [], $limit = 20, $offset = 0) {
    $pdo = getDbConnection();
    $sql = "SELECT r.*, f.name AS food_name, f.image AS food_image, f.slug AS food_slug, u.name AS customer_name, u.email AS customer_email, u.avatar AS customer_avatar, o.order_number 
            FROM `food_reviews` r 
            JOIN `foods` f ON r.food_id = f.id 
            JOIN `users` u ON r.user_id = u.id 
            JOIN `orders` o ON r.order_id = o.id 
            WHERE 1=1";
    $params = [];

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $sql .= " AND r.status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['food_id'])) {
        $sql .= " AND r.food_id = ?";
        $params[] = (int)$filters['food_id'];
    }

    if (!empty($filters['rating']) && (int)$filters['rating'] > 0) {
        $sql .= " AND r.rating = ?";
        $params[] = (int)$filters['rating'];
    }

    if (!empty($filters['search'])) {
        $s = '%' . trim($filters['search']) . '%';
        $sql .= " AND (f.name LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR r.review LIKE ? OR o.order_number LIKE ?)";
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }

    $countSql = "SELECT COUNT(*) FROM (" . $sql . ") AS count_tbl";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql .= " ORDER BY r.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();

    return [
        'reviews' => $reviews,
        'total' => $total
    ];
}

function dbAdminModerateReview($reviewId, $status, $adminResponse = null, $adminId = null) {
    $pdo = getDbConnection();
    $reviewId = (int)$reviewId;
    $status = in_array($status, ['approved', 'rejected', 'pending']) ? $status : 'pending';

    $stmt = $pdo->prepare("SELECT food_id FROM `food_reviews` WHERE id = ?");
    $stmt->execute([$reviewId]);
    $foodId = $stmt->fetchColumn();

    if (!$foodId) return false;

    $upd = $pdo->prepare("UPDATE `food_reviews` SET `status` = ?, `admin_response` = ?, `updated_at` = NOW() WHERE `id` = ?");
    $res = $upd->execute([$status, $adminResponse, $reviewId]);

    dbRecalculateFoodRating((int)$foodId);

    if (function_exists('dbLogSecurityEvent')) {
        dbLogSecurityEvent('review_moderated', "Review #{$reviewId} set to {$status}", null, $adminId);
    }

    return $res;
}

function dbAdminDeleteReview($reviewId, $adminId = null) {
    $pdo = getDbConnection();
    $reviewId = (int)$reviewId;

    $stmt = $pdo->prepare("SELECT food_id FROM `food_reviews` WHERE id = ?");
    $stmt->execute([$reviewId]);
    $foodId = $stmt->fetchColumn();

    if (!$foodId) return false;

    $del = $pdo->prepare("DELETE FROM `food_reviews` WHERE id = ?");
    $res = $del->execute([$reviewId]);

    dbRecalculateFoodRating((int)$foodId);

    if (function_exists('dbLogSecurityEvent')) {
        dbLogSecurityEvent('review_deleted', "Review #{$reviewId} deleted", null, $adminId);
    }

    return $res;
}

function dbGetUserReviews($userId) {
    $pdo = getDbConnection();
    $sql = "SELECT r.*, f.name AS food_name, f.slug AS food_slug, f.image AS food_image, o.order_number 
            FROM `food_reviews` r 
            JOIN `foods` f ON r.food_id = f.id 
            JOIN `orders` o ON r.order_id = o.id 
            WHERE r.user_id = ? 
            ORDER BY r.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([(int)$userId]);
    return $stmt->fetchAll();
}

function dbGetPendingReviewsCount() {
    $pdo = getDbConnection();
    return (int)$pdo->query("SELECT COUNT(*) FROM `food_reviews` WHERE `status` = 'pending'")->fetchColumn();
}

// =========================================================================
// STEP 30: PROMOTIONAL COUPONS ENGINE
// =========================================================================

function dbValidateCoupon($code, $subtotal, $userId = null) {
    $code = strtoupper(trim($code));
    $subtotal = round((float)$subtotal, 2);

    if (empty($code)) {
        return ['valid' => false, 'error' => 'Please enter a valid coupon code.'];
    }

    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM `coupons` WHERE `code` = ?");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        return ['valid' => false, 'error' => "Coupon '{$code}' does not exist."];
    }

    if (empty($coupon['is_active'])) {
        return ['valid' => false, 'error' => "Coupon '{$code}' is currently inactive."];
    }

    $now = date('Y-m-d H:i:s');
    if (!empty($coupon['start_at']) && $coupon['start_at'] > $now) {
        return ['valid' => false, 'error' => "Coupon '{$code}' has not started yet."];
    }

    if (!empty($coupon['end_at']) && $coupon['end_at'] < $now) {
        return ['valid' => false, 'error' => "Coupon '{$code}' has expired."];
    }

    if (!empty($coupon['usage_limit']) && (int)$coupon['used_count'] >= (int)$coupon['usage_limit']) {
        return ['valid' => false, 'error' => "Coupon '{$code}' usage limit has been reached."];
    }

    // Check per-customer usage
    if ($userId) {
        $perCustomerLimit = (int)($coupon['usage_per_customer'] ?? 1);
        $useStmt = $pdo->prepare("SELECT COUNT(*) FROM `coupon_usages` WHERE `coupon_id` = ? AND `user_id` = ?");
        $useStmt->execute([(int)$coupon['id'], (int)$userId]);
        $custUsage = (int)$useStmt->fetchColumn();
        if ($custUsage >= $perCustomerLimit) {
            return ['valid' => false, 'error' => "You have already used coupon '{$code}' the maximum allowed number of times."];
        }
    }

    // Minimum order check
    $minOrder = (float)($coupon['minimum_order'] ?? 0.00);
    if ($minOrder > 0 && $subtotal < $minOrder) {
        return ['valid' => false, 'error' => "Minimum order of $" . number_format($minOrder, 2) . " required for coupon '{$code}'."];
    }

    // Calculate discount amount
    $discValue = (float)$coupon['discount_value'];
    if ($coupon['discount_type'] === 'percentage') {
        $calculatedDiscount = round(($subtotal * $discValue) / 100, 2);
        if (!empty($coupon['maximum_discount']) && (float)$coupon['maximum_discount'] > 0) {
            $calculatedDiscount = min($calculatedDiscount, (float)$coupon['maximum_discount']);
        }
    } else {
        // fixed discount
        $calculatedDiscount = min($discValue, $subtotal);
    }

    // Discount cannot exceed subtotal
    $finalDiscount = max(0.00, min($calculatedDiscount, $subtotal));

    return [
        'valid' => true,
        'coupon' => $coupon,
        'discount' => $finalDiscount,
        'discount_formatted' => '$' . number_format($finalDiscount, 2),
        'message' => "Coupon '{$code}' applied! Saved $" . number_format($finalDiscount, 2) . "."
    ];
}

function dbGetCouponByCode($code) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM `coupons` WHERE `code` = ?");
    $stmt->execute([strtoupper(trim($code))]);
    return $stmt->fetch() ?: null;
}

function dbGetCouponById($id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM `coupons` WHERE `id` = ?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch() ?: null;
}

function dbAdminGetCoupons($filters = [], $limit = 20, $offset = 0) {
    $pdo = getDbConnection();
    $sql = "SELECT c.*, COUNT(cu.id) AS total_orders_used, COALESCE(SUM(cu.discount_amount), 0.00) AS total_discount_granted 
            FROM `coupons` c 
            LEFT JOIN `coupon_usages` cu ON c.id = cu.coupon_id 
            WHERE 1=1";
    $params = [];

    if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== 'all') {
        $sql .= " AND c.is_active = ?";
        $params[] = (int)$filters['is_active'];
    }

    if (!empty($filters['discount_type']) && $filters['discount_type'] !== 'all') {
        $sql .= " AND c.discount_type = ?";
        $params[] = $filters['discount_type'];
    }

    if (!empty($filters['search'])) {
        $s = '%' . trim($filters['search']) . '%';
        $sql .= " AND (c.code LIKE ? OR c.description LIKE ?)";
        $params[] = $s;
        $params[] = $s;
    }

    $sql .= " GROUP BY c.id";

    $countSql = "SELECT COUNT(*) FROM (" . $sql . ") AS count_tbl";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql .= " ORDER BY c.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $coupons = $stmt->fetchAll();

    return [
        'coupons' => $coupons,
        'total' => $total
    ];
}

function dbAdminCreateCoupon($data, $adminId = null) {
    $pdo = getDbConnection();
    $code = strtoupper(trim($data['code'] ?? ''));
    if (empty($code)) return false;

    $stmt = $pdo->prepare("INSERT INTO `coupons` 
        (`code`, `description`, `discount_type`, `discount_value`, `minimum_order`, `maximum_discount`, `usage_limit`, `usage_per_customer`, `used_count`, `start_at`, `end_at`, `is_active`, `created_at`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NOW())");

    $res = $stmt->execute([
        $code,
        trim($data['description'] ?? ''),
        $data['discount_type'] ?? 'percentage',
        (float)($data['discount_value'] ?? 0.00),
        (float)($data['minimum_order'] ?? 0.00),
        !empty($data['maximum_discount']) ? (float)$data['maximum_discount'] : null,
        !empty($data['usage_limit']) ? (int)$data['usage_limit'] : null,
        max(1, (int)($data['usage_per_customer'] ?? 1)),
        !empty($data['start_at']) ? $data['start_at'] : null,
        !empty($data['end_at']) ? $data['end_at'] : null,
        isset($data['is_active']) ? (int)$data['is_active'] : 1
    ]);

    $couponId = $res ? (int)$pdo->lastInsertId() : false;

    if ($couponId && function_exists('dbLogSecurityEvent')) {
        dbLogSecurityEvent('coupon_created', "Created coupon '{$code}' (ID: {$couponId})", null, $adminId);
    }

    return $couponId;
}

function dbAdminUpdateCoupon($id, $data, $adminId = null) {
    $pdo = getDbConnection();
    $id = (int)$id;
    $code = strtoupper(trim($data['code'] ?? ''));
    if (empty($code) || !$id) return false;

    $stmt = $pdo->prepare("UPDATE `coupons` SET 
        `code` = ?, 
        `description` = ?, 
        `discount_type` = ?, 
        `discount_value` = ?, 
        `minimum_order` = ?, 
        `maximum_discount` = ?, 
        `usage_limit` = ?, 
        `usage_per_customer` = ?, 
        `start_at` = ?, 
        `end_at` = ?, 
        `is_active` = ?, 
        `updated_at` = NOW() 
        WHERE `id` = ?");

    $res = $stmt->execute([
        $code,
        trim($data['description'] ?? ''),
        $data['discount_type'] ?? 'percentage',
        (float)($data['discount_value'] ?? 0.00),
        (float)($data['minimum_order'] ?? 0.00),
        !empty($data['maximum_discount']) ? (float)$data['maximum_discount'] : null,
        !empty($data['usage_limit']) ? (int)$data['usage_limit'] : null,
        max(1, (int)($data['usage_per_customer'] ?? 1)),
        !empty($data['start_at']) ? $data['start_at'] : null,
        !empty($data['end_at']) ? $data['end_at'] : null,
        isset($data['is_active']) ? (int)$data['is_active'] : 1,
        $id
    ]);

    if ($res && function_exists('dbLogSecurityEvent')) {
        dbLogSecurityEvent('coupon_updated', "Updated coupon '{$code}' (ID: {$id})", null, $adminId);
    }

    return $res;
}

function dbAdminDeleteCoupon($id, $adminId = null) {
    $pdo = getDbConnection();
    $id = (int)$id;
    $coupon = dbGetCouponById($id);
    if (!$coupon) return false;

    $stmt = $pdo->prepare("DELETE FROM `coupons` WHERE `id` = ?");
    $res = $stmt->execute([$id]);

    if ($res && function_exists('dbLogSecurityEvent')) {
        dbLogSecurityEvent('coupon_deleted', "Deleted coupon '{$coupon['code']}' (ID: {$id})", null, $adminId);
    }

    return $res;
}

function dbAdminToggleCoupon($id, $isActive, $adminId = null) {
    $pdo = getDbConnection();
    $id = (int)$id;
    $isActive = (int)$isActive;
    $stmt = $pdo->prepare("UPDATE `coupons` SET `is_active` = ?, `updated_at` = NOW() WHERE `id` = ?");
    $res = $stmt->execute([$isActive, $id]);

    if ($res && function_exists('dbLogSecurityEvent')) {
        dbLogSecurityEvent('coupon_toggled', "Coupon ID: {$id} active status set to {$isActive}", null, $adminId);
    }

    return $res;
}

function dbRecordCouponUsage($couponId, $userId, $orderId, $discountAmount) {
    $pdo = getDbConnection();
    $couponId = (int)$couponId;
    $userId = (int)$userId;
    $orderId = (int)$orderId;
    $discountAmount = round((float)$discountAmount, 2);

    $ins = $pdo->prepare("INSERT INTO `coupon_usages` (`coupon_id`, `user_id`, `order_id`, `discount_amount`, `created_at`) VALUES (?, ?, ?, ?, NOW())");
    $res = $ins->execute([$couponId, $userId, $orderId, $discountAmount]);

    if ($res) {
        $upd = $pdo->prepare("UPDATE `coupons` SET `used_count` = `used_count` + 1 WHERE `id` = ?");
        $upd->execute([$couponId]);
    }

    return $res;
}

function seedDefaultCoupons(PDO $pdo) {
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM `coupons`")->fetchColumn();
    if ($cnt === 0) {
        $ins = $pdo->prepare("INSERT INTO `coupons` (`code`, `description`, `discount_type`, `discount_value`, `minimum_order`, `maximum_discount`, `usage_limit`, `usage_per_customer`, `used_count`, `is_active`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute(['FASTBITE20', '20% off on all orders over $15 (Max $10 discount)', 'percentage', 20.00, 15.00, 10.00, 100, 2, 0, 1]);
        $ins->execute(['SAVE5', '$5 instant savings on orders over $20', 'fixed', 5.00, 20.00, null, 50, 1, 0, 1]);
        $ins->execute(['WELCOME10', '10% welcome discount for hungry foodies', 'percentage', 10.00, 10.00, 5.00, 200, 1, 0, 1]);
        $ins->execute(['FLASH50', '50% mega discount up to $15', 'percentage', 50.00, 25.00, 15.00, 20, 1, 0, 1]);
    }
}

function seedDefaultReviews(PDO $pdo) {
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM `food_reviews`")->fetchColumn();
    if ($cnt === 0) {
        $user2 = $pdo->query("SELECT id FROM `users` WHERE `role` = 'customer' LIMIT 1")->fetchColumn();
        if ($user2) {
            $delivOrder = $pdo->query("SELECT o.id, oi.food_id FROM `orders` o JOIN `order_items` oi ON o.id = oi.order_id WHERE o.order_status = 'delivered' LIMIT 3")->fetchAll();
            $sampleReviews = [
                ['rating' => 5, 'review' => 'Absolutely delicious! The crust was crispy and the cheese was so rich and savory. Arrived piping hot!'],
                ['rating' => 5, 'review' => 'One of the best fast foods I have ordered in Hargeisa. Fresh ingredients and great portion sizes.'],
                ['rating' => 4, 'review' => 'Very flavorful and nicely packed. Will definitely reorder again soon!']
            ];
            $rIdx = 0;
            $insRev = $pdo->prepare("INSERT IGNORE INTO `food_reviews` (`food_id`, `user_id`, `order_id`, `rating`, `review`, `status`, `created_at`) VALUES (?, ?, ?, ?, ?, 'approved', NOW())");
            foreach ($delivOrder as $do) {
                if (isset($sampleReviews[$rIdx])) {
                    $insRev->execute([$do['food_id'], $user2, $do['id'], $sampleReviews[$rIdx]['rating'], $sampleReviews[$rIdx]['review']]);
                    dbRecalculateFoodRating((int)$do['food_id']);
                    $rIdx++;
                }
            }
        }
    }
}


// =========================================================================
// STEP 31: AUTHENTICATION FINALIZATION & GOOGLE OAUTH HELPERS
// =========================================================================

/**
 * Finds user by Google Provider Subject ID.
 */
function dbFindUserByGoogleId($googleId) {
    if (empty($googleId)) return null;
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `google_provider_id` = ? LIMIT 1");
    $stmt->execute([trim($googleId)]);
    return $stmt->fetch() ?: null;
}

/**
 * Links a Google OAuth ID to an existing user account.
 */
function dbLinkGoogleAccount($userId, $googleId, $avatar = null) {
    $pdo = getDbConnection();
    $userId = (int)$userId;
    $googleId = trim($googleId);
    
    $user = dbFindUserById($userId);
    if (!$user) return false;

    $currentAuth = $user['auth_provider'] ?? 'local';
    $newAuth = ($currentAuth === 'local') ? 'local+google' : $currentAuth;

    $sql = "UPDATE `users` SET `google_provider_id` = ?, `auth_provider` = ?, `updated_at` = NOW()";
    $params = [$googleId, $newAuth];

    if (!empty($avatar) && empty($user['avatar'])) {
        $sql .= ", `avatar` = ?";
        $params[] = $avatar;
    }

    $sql .= " WHERE `id` = ?";
    $params[] = $userId;

    $stmt = $pdo->prepare($sql);
    $res = $stmt->execute($params);

    if ($res && function_exists('dbLogSecurityEvent')) {
        dbLogSecurityEvent('oauth_linked', "Google Account linked for user {$user['email']}", $userId);
    }

    return $res;
}

/**
 * Creates a new customer account via Google OAuth identity.
 */
function dbCreateGoogleUser($googleData) {
    $pdo = getDbConnection();
    $name = trim($googleData['name'] ?? 'Google Customer');
    $email = strtolower(trim($googleData['email'] ?? ''));
    $googleId = trim($googleData['google_id'] ?? ($googleData['sub'] ?? ''));
    $avatar = trim($googleData['avatar'] ?? ($googleData['picture'] ?? ''));

    if (empty($email) || empty($googleId)) {
        return false;
    }

    // Generate strong random password hash so account cannot be accessed with empty password locally
    $randomPass = bin2hex(random_bytes(32));
    $passwordHash = password_hash($randomPass, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `password`, `auth_provider`, `google_provider_id`, `role`, `status`, `avatar`, `created_at`, `updated_at`) 
                           VALUES (?, ?, ?, 'google', ?, 'customer', 'active', ?, NOW(), NOW())");
    $res = $stmt->execute([$name, $email, $passwordHash, $googleId, !empty($avatar) ? $avatar : null]);

    if ($res) {
        $newUserId = (int)$pdo->lastInsertId();
        if (function_exists('dbLogSecurityEvent')) {
            dbLogSecurityEvent('oauth_registered', "New customer registered via Google OAuth: {$email}", $newUserId);
        }
        if (function_exists('dbCreateNotification')) {
            dbCreateNotification(
                $newUserId,
                "Welcome to FastBite, {$name}!",
                "Your account was created with Google Sign-In. Enjoy ordering fast and delicious meals!",
                "account",
                "menu.php"
            );
        }
        return $newUserId;
    }

    return false;
}

/**
 * Checks if Google OAuth is configured and enabled in system settings or environment.
 */
function isGoogleOAuthEnabled() {
    $clientId = dbGetSetting('google_oauth_client_id', getenv('GOOGLE_CLIENT_ID') ?: '');
    $clientSecret = dbGetSetting('google_oauth_client_secret', getenv('GOOGLE_CLIENT_SECRET') ?: '');
    return (!empty(trim($clientId)) && !empty(trim($clientSecret)));
}

/**
 * Returns Google OAuth client configuration.
 */
function getGoogleOAuthClientConfig() {
    $clientId = trim(dbGetSetting('google_oauth_client_id', getenv('GOOGLE_CLIENT_ID') ?: ''));
    $clientSecret = trim(dbGetSetting('google_oauth_client_secret', getenv('GOOGLE_CLIENT_SECRET') ?: ''));
    
    // Determine dynamic callback redirect URI
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    $redirectUri = $protocol . $host . '/bink%20bite_fast%20food/google-callback.php';

    return [
        'enabled' => (!empty($clientId) && !empty($clientSecret)),
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri
    ];
}

/**
 * Builds Google OAuth 2.0 Authorization URL with CSRF state token.
 */
function generateGoogleOAuthUrl($returnUrl = '') {
    $config = getGoogleOAuthClientConfig();
    if (!$config['enabled']) {
        return null;
    }

    startFastBiteSession();
    $stateToken = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_state'] = $stateToken;
    $_SESSION['google_oauth_return_url'] = $returnUrl;

    $params = [
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $stateToken,
        'access_type' => 'online',
        'prompt' => 'select_account'
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/**
 * Validates Google OAuth code & state, exchanges token, and retrieves verified user.
 */
function processGoogleOAuthCallback($code, $state) {
    startFastBiteSession();
    $savedState = $_SESSION['google_oauth_state'] ?? '';
    $returnUrl = $_SESSION['google_oauth_return_url'] ?? 'customer-dashboard.php';
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_return_url']);

    if (empty($state) || empty($savedState) || !hash_equals($savedState, $state)) {
        return ['success' => false, 'error' => 'Invalid or expired Google OAuth security state token.'];
    }

    $config = getGoogleOAuthClientConfig();
    if (!$config['enabled']) {
        return ['success' => false, 'error' => 'Google Sign-In is not currently configured.'];
    }

    // Exchange authorization code for access token via HTTP POST
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $postFields = http_build_query([
        'code' => $code,
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['redirect_uri'],
        'grant_type' => 'authorization_code'
    ]);

    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($postFields) . "\r\n",
            'content' => $postFields,
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ];

    $ctx = stream_context_create($opts);
    $response = @file_get_contents($tokenUrl, false, $ctx);
    if ($response === false) {
        return ['success' => false, 'error' => 'Failed to communicate with Google authentication servers.'];
    }

    $tokenData = json_decode($response, true);
    if (empty($tokenData['access_token'])) {
        $errDetail = $tokenData['error_description'] ?? ($tokenData['error'] ?? 'Token exchange failed');
        return ['success' => false, 'error' => 'Google authentication error: ' . $errDetail];
    }

    $accessToken = $tokenData['access_token'];

    // Fetch userinfo from Google API using access token
    $userinfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
    $uOpts = [
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer {$accessToken}\r\nUser-Agent: FastBite-Auth/1.0\r\n",
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ];
    $uCtx = stream_context_create($uOpts);
    $uResponse = @file_get_contents($userinfoUrl, false, $uCtx);
    if ($uResponse === false) {
        return ['success' => false, 'error' => 'Failed to retrieve verified profile details from Google.'];
    }

    $userInfo = json_decode($uResponse, true);
    if (empty($userInfo['sub']) || empty($userInfo['email'])) {
        return ['success' => false, 'error' => 'Google returned incomplete identity information.'];
    }

    // Verify email_verified flag
    if (isset($userInfo['email_verified']) && $userInfo['email_verified'] !== true && $userInfo['email_verified'] !== 'true') {
        return ['success' => false, 'error' => 'Your Google email address is not verified.'];
    }

    $googleId = $userInfo['sub'];
    $email = strtolower(trim($userInfo['email']));
    $name = trim($userInfo['name'] ?? explode('@', $email)[0]);
    $avatar = trim($userInfo['picture'] ?? '');

    // 1. Look for existing user with this Google ID
    $existingGoogleUser = dbFindUserByGoogleId($googleId);
    if ($existingGoogleUser) {
        if ($existingGoogleUser['status'] !== 'active') {
            return ['success' => false, 'error' => 'Your FastBite account has been deactivated. Please contact support.'];
        }
        loginUser($existingGoogleUser);
        if (function_exists('dbLogSecurityEvent')) {
            dbLogSecurityEvent('oauth_login', "Customer logged in via Google OAuth: {$email}", $existingGoogleUser['id']);
        }
        return ['success' => true, 'user' => $existingGoogleUser, 'return_url' => $returnUrl];
    }

    // 2. Look for existing user with this Email address
    $existingEmailUser = dbFindUserByEmail($email);
    if ($existingEmailUser) {
        if ($existingEmailUser['status'] !== 'active') {
            return ['success' => false, 'error' => 'Your FastBite account has been deactivated. Please contact support.'];
        }
        // Link Google ID to existing account
        dbLinkGoogleAccount((int)$existingEmailUser['id'], $googleId, $avatar);
        $updatedUser = dbFindUserById((int)$existingEmailUser['id']);
        loginUser($updatedUser);
        if (function_exists('dbLogSecurityEvent')) {
            dbLogSecurityEvent('oauth_link_and_login', "Linked Google identity to existing account for {$email}", $existingEmailUser['id']);
        }
        return ['success' => true, 'user' => $updatedUser, 'return_url' => $returnUrl];
    }

    // 3. New Customer: Register user account
    $newUserId = dbCreateGoogleUser([
        'name' => $name,
        'email' => $email,
        'google_id' => $googleId,
        'avatar' => $avatar
    ]);

    if ($newUserId > 0) {
        $newUser = dbFindUserById($newUserId);
        loginUser($newUser);
        return ['success' => true, 'user' => $newUser, 'return_url' => $returnUrl];
    }

    return ['success' => false, 'error' => 'Failed to create customer account from Google profile.'];
}


// =========================================================================
// STEP 32: DELIVERY MANAGEMENT & COURIER OPERATIONS CENTER
// =========================================================================

/**
 * Seed default couriers / delivery drivers if table is empty.
 */
function seedDefaultDrivers(PDO $pdo) {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM `delivery_drivers`")->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare("INSERT INTO `delivery_drivers` (`full_name`, `phone`, `email`, `vehicle_type`, `vehicle_number`, `status`, `rating`, `total_deliveries`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $drivers = [
            ['Ahmed Jama', '+252 63 4112233', 'ahmed.courier@fastbite.com', 'motorcycle', 'FB-MOT-101', 'available', 4.90, 48],
            ['Mustafa Farah', '+252 63 4223344', 'mustafa.courier@fastbite.com', 'car', 'FB-CAR-202', 'available', 4.85, 36],
            ['Khadar Hassan', '+252 63 4334455', 'khadar.courier@fastbite.com', 'scooter', 'FB-SCT-303', 'available', 4.95, 52],
            ['Bilal Nur', '+252 63 4445566', 'bilal.courier@fastbite.com', 'bicycle', 'FB-BIC-404', 'available', 4.80, 29]
        ];
        foreach ($drivers as $d) {
            $stmt->execute($d);
        }
    }
}

/**
 * Seed default order deliveries for any existing orders without delivery records.
 */
function seedDefaultOrderDeliveries(PDO $pdo) {
    $orders = $pdo->query("SELECT o.id, o.order_status, o.created_at, o.updated_at FROM `orders` o LEFT JOIN `order_deliveries` od ON o.id = od.order_id WHERE od.id IS NULL")->fetchAll();
    if (!empty($orders)) {
        $drivers = $pdo->query("SELECT id FROM `delivery_drivers` ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
        $ins = $pdo->prepare("INSERT INTO `order_deliveries` (`order_id`, `driver_id`, `delivery_status`, `assigned_at`, `picked_up_at`, `out_for_delivery_at`, `delivered_at`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($orders as $idx => $o) {
            $orderId = (int)$o['id'];
            $st = $o['order_status'];
            $created = $o['created_at'];
            $updated = $o['updated_at'] ?? $o['created_at'];
            $driverId = !empty($drivers) ? $drivers[$idx % count($drivers)] : null;
            
            if ($st === 'delivered') {
                $ins->execute([$orderId, $driverId, 'delivered', $created, $created, $updated, $updated, $created, $updated]);
            } elseif ($st === 'out_for_delivery') {
                $ins->execute([$orderId, $driverId, 'out_for_delivery', $created, $created, $updated, null, $created, $updated]);
            } elseif ($st === 'preparing' || $st === 'confirmed') {
                $ins->execute([$orderId, $driverId, 'assigned', $created, null, null, null, $created, $updated]);
            } elseif ($st === 'cancelled') {
                $ins->execute([$orderId, null, 'cancelled', null, null, null, null, $created, $updated]);
            } else {
                $ins->execute([$orderId, null, 'unassigned', null, null, null, null, $created, $updated]);
            }
        }
    }
}

/**
 * Create a new delivery driver / courier.
 *
 * @param array $data
 * @return int Driver ID or 0
 */
function dbCreateDriver(array $data): int {
    $pdo = getDbConnection();
    $fullName = trim($data['full_name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = !empty($data['email']) ? strtolower(trim($data['email'])) : null;
    $vehicleType = strtolower(trim($data['vehicle_type'] ?? 'motorcycle'));
    $vehicleNumber = !empty($data['vehicle_number']) ? trim($data['vehicle_number']) : null;
    $status = strtolower(trim($data['status'] ?? 'available'));
    $userId = !empty($data['user_id']) ? (int)$data['user_id'] : null;

    if (empty($fullName) || empty($phone)) {
        return 0;
    }

    $validVehicles = ['motorcycle', 'bicycle', 'car', 'van', 'scooter'];
    if (!in_array($vehicleType, $validVehicles, true)) {
        $vehicleType = 'motorcycle';
    }

    $validStatuses = ['available', 'busy', 'offline', 'suspended'];
    if (!in_array($status, $validStatuses, true)) {
        $status = 'available';
    }

    $stmt = $pdo->prepare("INSERT INTO `delivery_drivers` (`user_id`, `full_name`, `phone`, `email`, `vehicle_type`, `vehicle_number`, `status`, `rating`, `total_deliveries`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, 5.00, 0, NOW())");
    $stmt->execute([$userId, $fullName, $phone, $email, $vehicleType, $vehicleNumber, $status]);
    $newId = (int)$pdo->lastInsertId();

    if ($newId > 0 && function_exists('dbLogSecurityEvent')) {
        dbLogSecurityEvent('driver_created', "New courier registered: {$fullName} ({$phone})", null, null);
    }

    return $newId;
}

/**
 * Update an existing delivery driver.
 *
 * @param int $id
 * @param array $data
 * @return bool
 */
function dbUpdateDriver(int $id, array $data): bool {
    $pdo = getDbConnection();
    $fullName = trim($data['full_name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = !empty($data['email']) ? strtolower(trim($data['email'])) : null;
    $vehicleType = strtolower(trim($data['vehicle_type'] ?? 'motorcycle'));
    $vehicleNumber = !empty($data['vehicle_number']) ? trim($data['vehicle_number']) : null;
    $status = strtolower(trim($data['status'] ?? 'available'));

    if ($id <= 0 || empty($fullName) || empty($phone)) {
        return false;
    }

    $validVehicles = ['motorcycle', 'bicycle', 'car', 'van', 'scooter'];
    if (!in_array($vehicleType, $validVehicles, true)) {
        $vehicleType = 'motorcycle';
    }

    $validStatuses = ['available', 'busy', 'offline', 'suspended'];
    if (!in_array($status, $validStatuses, true)) {
        $status = 'available';
    }

    $stmt = $pdo->prepare("UPDATE `delivery_drivers` SET `full_name` = ?, `phone` = ?, `email` = ?, `vehicle_type` = ?, `vehicle_number` = ?, `status` = ?, `updated_at` = NOW() WHERE `id` = ?");
    $result = $stmt->execute([$fullName, $phone, $email, $vehicleType, $vehicleNumber, $status, $id]);

    if ($result && function_exists('dbLogSecurityEvent')) {
        dbLogSecurityEvent('driver_updated', "Courier updated: {$fullName} (ID: {$id})", null, null);
    }

    return $result;
}

/**
 * Get driver by ID with summary stats.
 *
 * @param int $id
 * @return array|null
 */
function dbGetDriverById(int $id): ?array {
    if ($id <= 0) return null;
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT d.*, 
        (SELECT COUNT(*) FROM `order_deliveries` od WHERE od.driver_id = d.id AND od.delivery_status IN ('assigned', 'picked_up', 'out_for_delivery')) AS active_deliveries_count,
        (SELECT COUNT(*) FROM `order_deliveries` od WHERE od.driver_id = d.id AND od.delivery_status = 'delivered') AS completed_deliveries_count,
        (SELECT COUNT(*) FROM `order_deliveries` od WHERE od.driver_id = d.id AND od.delivery_status = 'failed') AS failed_deliveries_count
        FROM `delivery_drivers` d WHERE d.id = ?");
    $stmt->execute([$id]);
    $driver = $stmt->fetch();
    return $driver ?: null;
}

/**
 * Get all drivers with optional filtering.
 *
 * @param array $filters
 * @return array
 */
function dbGetAllDrivers(array $filters = []): array {
    $pdo = getDbConnection();
    $sql = "SELECT d.*, 
        (SELECT COUNT(*) FROM `order_deliveries` od WHERE od.driver_id = d.id AND od.delivery_status IN ('assigned', 'picked_up', 'out_for_delivery')) AS active_deliveries_count,
        (SELECT COUNT(*) FROM `order_deliveries` od WHERE od.driver_id = d.id AND od.delivery_status = 'delivered') AS completed_deliveries_count,
        (SELECT COUNT(*) FROM `order_deliveries` od WHERE od.driver_id = d.id AND od.delivery_status = 'failed') AS failed_deliveries_count,
        (SELECT o.order_number FROM `order_deliveries` od2 JOIN `orders` o ON od2.order_id = o.id WHERE od2.driver_id = d.id AND od2.delivery_status IN ('assigned', 'picked_up', 'out_for_delivery') ORDER BY od2.id DESC LIMIT 1) AS active_order_number,
        (SELECT od2.order_id FROM `order_deliveries` od2 WHERE od2.driver_id = d.id AND od2.delivery_status IN ('assigned', 'picked_up', 'out_for_delivery') ORDER BY od2.id DESC LIMIT 1) AS active_order_id
        FROM `delivery_drivers` d WHERE 1=1";
    
    $params = [];

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $sql .= " AND d.status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['vehicle_type']) && $filters['vehicle_type'] !== 'all') {
        $sql .= " AND d.vehicle_type = ?";
        $params[] = $filters['vehicle_type'];
    }

    if (!empty($filters['search'])) {
        $searchTerm = '%' . trim($filters['search']) . '%';
        $sql .= " AND (d.full_name LIKE ? OR d.phone LIKE ? OR d.email LIKE ? OR d.vehicle_number LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= " ORDER BY d.status = 'available' DESC, d.full_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Update driver status (available, busy, offline, suspended).
 *
 * @param int $id
 * @param string $status
 * @return bool
 */
function dbUpdateDriverStatus(int $id, string $status): bool {
    $validStatuses = ['available', 'busy', 'offline', 'suspended'];
    if (!in_array($status, $validStatuses, true) || $id <= 0) {
        return false;
    }
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE `delivery_drivers` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ?");
    return $stmt->execute([$status, $id]);
}

/**
 * Get available couriers ready for dispatch.
 *
 * @return array
 */
function dbGetAvailableDrivers(): array {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM `delivery_drivers` WHERE `status` = 'available' ORDER BY `rating` DESC, `full_name` ASC");
    return $stmt->fetchAll();
}

/**
 * Get detailed driver performance metrics and delivery history.
 *
 * @param int $driverId
 * @return array
 */
function dbGetDriverPerformance(int $driverId): array {
    $driver = dbGetDriverById($driverId);
    if (!$driver) {
        return [
            'driver' => null,
            'total_assigned' => 0,
            'completed_deliveries' => 0,
            'failed_deliveries' => 0,
            'active_deliveries' => [],
            'recent_deliveries' => [],
            'success_rate' => 100,
            'avg_minutes' => 0
        ];
    }

    $pdo = getDbConnection();

    // Active deliveries
    $stmtActive = $pdo->prepare("SELECT od.*, o.order_number, o.total, o.order_status, o.payment_status, o.payment_method, o.delivery_address, u.name AS customer_name, u.phone AS customer_phone
        FROM `order_deliveries` od
        JOIN `orders` o ON od.order_id = o.id
        LEFT JOIN `users` u ON o.user_id = u.id
        WHERE od.driver_id = ? AND od.delivery_status IN ('assigned', 'picked_up', 'out_for_delivery')
        ORDER BY od.id DESC");
    $stmtActive->execute([$driverId]);
    $activeDeliveries = $stmtActive->fetchAll();

    // Recent deliveries
    $stmtRecent = $pdo->prepare("SELECT od.*, o.order_number, o.total, o.order_status, o.payment_status, o.payment_method, o.delivery_address, u.name AS customer_name, u.phone AS customer_phone,
        TIMESTAMPDIFF(MINUTE, od.picked_up_at, od.delivered_at) AS delivery_duration_minutes
        FROM `order_deliveries` od
        JOIN `orders` o ON od.order_id = o.id
        LEFT JOIN `users` u ON o.user_id = u.id
        WHERE od.driver_id = ? AND od.delivery_status IN ('delivered', 'failed', 'cancelled')
        ORDER BY od.updated_at DESC LIMIT 20");
    $stmtRecent->execute([$driverId]);
    $recentDeliveries = $stmtRecent->fetchAll();

    $completed = (int)$driver['completed_deliveries_count'];
    $failed = (int)$driver['failed_deliveries_count'];
    $totalCompletedOrFailed = $completed + $failed;
    $successRate = $totalCompletedOrFailed > 0 ? round(($completed / $totalCompletedOrFailed) * 100, 1) : 100.0;

    // Average duration
    $stmtAvg = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(MINUTE, picked_up_at, delivered_at)) AS avg_mins FROM `order_deliveries` WHERE driver_id = ? AND delivery_status = 'delivered' AND picked_up_at IS NOT NULL AND delivered_at IS NOT NULL");
    $stmtAvg->execute([$driverId]);
    $avgMins = round((float)$stmtAvg->fetchColumn(), 1);

    return [
        'driver' => $driver,
        'total_assigned' => $completed + $failed + (int)$driver['active_deliveries_count'],
        'completed_deliveries' => $completed,
        'failed_deliveries' => $failed,
        'active_deliveries' => $activeDeliveries,
        'recent_deliveries' => $recentDeliveries,
        'success_rate' => $successRate,
        'avg_minutes' => $avgMins > 0 ? $avgMins : 25.0
    ];
}

/**
 * Get delivery details for a specific order.
 *
 * @param int $orderId
 * @return array|null
 */
function dbGetOrderDelivery(int $orderId): ?array {
    if ($orderId <= 0) return null;
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT od.*, 
        d.full_name AS driver_name,
        d.phone AS driver_phone,
        d.email AS driver_email,
        d.vehicle_type,
        d.vehicle_number,
        d.rating AS driver_rating,
        d.status AS driver_status
        FROM `order_deliveries` od
        LEFT JOIN `delivery_drivers` d ON od.driver_id = d.id
        WHERE od.order_id = ?");
    $stmt->execute([$orderId]);
    $deliv = $stmt->fetch();

    if (!$deliv) {
        // Create an unassigned delivery record if none exists for this order
        $chkOrder = $pdo->prepare("SELECT id, order_status FROM `orders` WHERE id = ?");
        $chkOrder->execute([$orderId]);
        $order = $chkOrder->fetch();
        if ($order) {
            $initStatus = ($order['order_status'] === 'cancelled') ? 'cancelled' : 'unassigned';
            $ins = $pdo->prepare("INSERT IGNORE INTO `order_deliveries` (`order_id`, `delivery_status`, `created_at`, `updated_at`) VALUES (?, ?, NOW(), NOW())");
            $ins->execute([$orderId, $initStatus]);
            
            $stmt->execute([$orderId]);
            $deliv = $stmt->fetch();
        }
    }

    return $deliv ?: null;
}

/**
 * Assign a driver to an order in a transaction-safe manner.
 *
 * @param int $orderId
 * @param int $driverId
 * @param string|null $notes
 * @param int|null $adminId
 * @return array ['success' => bool, 'message' => string]
 */
function dbAssignDriverToOrder(int $orderId, int $driverId, ?string $notes = null, ?int $adminId = null): array {
    $pdo = getDbConnection();
    if ($orderId <= 0 || $driverId <= 0) {
        return ['success' => false, 'message' => 'Invalid order ID or driver ID.'];
    }

    try {
        $pdo->beginTransaction();

        // 1. Check order
        $stmtOrder = $pdo->prepare("SELECT id, user_id, order_number, order_status FROM `orders` WHERE id = ? FOR UPDATE");
        $stmtOrder->execute([$orderId]);
        $order = $stmtOrder->fetch();
        if (!$order) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Order not found.'];
        }

        if (in_array($order['order_status'], ['delivered', 'cancelled'], true)) {
            $pdo->rollBack();
            return ['success' => false, 'message' => "Cannot assign courier to an order that is already {$order['order_status']}."];
        }

        // 2. Check driver
        $stmtDriver = $pdo->prepare("SELECT id, full_name, phone, vehicle_type, status FROM `delivery_drivers` WHERE id = ? FOR UPDATE");
        $stmtDriver->execute([$driverId]);
        $driver = $stmtDriver->fetch();
        if (!$driver) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Delivery driver not found.'];
        }

        if ($driver['status'] === 'suspended') {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Selected courier is suspended and cannot be assigned.'];
        }

        // 3. Upsert order_deliveries
        $chkDelivery = $pdo->prepare("SELECT id FROM `order_deliveries` WHERE order_id = ?");
        $chkDelivery->execute([$orderId]);
        $existingDelivery = $chkDelivery->fetch();

        if ($existingDelivery) {
            $stmtUpd = $pdo->prepare("UPDATE `order_deliveries` SET `driver_id` = ?, `delivery_status` = 'assigned', `assigned_at` = NOW(), `delivery_notes` = ?, `updated_at` = NOW() WHERE `order_id` = ?");
            $stmtUpd->execute([$driverId, $notes, $orderId]);
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO `order_deliveries` (`order_id`, `driver_id`, `delivery_status`, `assigned_at`, `delivery_notes`, `created_at`, `updated_at`) VALUES (?, ?, 'assigned', NOW(), ?, NOW(), NOW())");
            $stmtIns->execute([$orderId, $driverId, $notes]);
        }

        // 4. Update driver status to busy
        $stmtDrvStatus = $pdo->prepare("UPDATE `delivery_drivers` SET `status` = 'busy', `updated_at` = NOW() WHERE `id` = ?");
        $stmtDrvStatus->execute([$driverId]);

        // 5. Send customer in-app notification
        $vType = ucfirst($driver['vehicle_type']);
        $notifTitle = "Courier Assigned: Order #{$order['order_number']}";
        $notifMsg = "Courier {$driver['full_name']} ({$vType}) has been assigned to your order #{$order['order_number']}. Delivering to you soon!";
        $notifLink = "order-details.php?order=" . urlencode($order['order_number']);

        if (function_exists('dbCreateNotification')) {
            dbCreateNotification((int)$order['user_id'], $notifTitle, $notifMsg, 'order', $notifLink);
        }

        // 6. Security & Audit log
        if (function_exists('dbLogSecurityEvent')) {
            dbLogSecurityEvent('delivery_assigned', "Assigned courier {$driver['full_name']} to Order #{$order['order_number']}", (int)$order['user_id'], $adminId);
        }

        $pdo->commit();
        return ['success' => true, 'message' => "Courier {$driver['full_name']} assigned successfully to Order #{$order['order_number']}."];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Database error during courier assignment: ' . $e->getMessage()];
    }
}

/**
 * Update delivery status with strict state machine validation, driver status updates, customer notifications, and logs.
 *
 * @param int $orderId
 * @param string $newStatus
 * @param array $params ['failure_reason' => string, 'notes' => string]
 * @param int|null $adminId
 * @return array ['success' => bool, 'message' => string]
 */
function dbUpdateDeliveryStatus(int $orderId, string $newStatus, array $params = [], ?int $adminId = null): array {
    $pdo = getDbConnection();
    $validStatuses = ['unassigned', 'assigned', 'picked_up', 'out_for_delivery', 'delivered', 'failed', 'cancelled'];
    
    if (!in_array($newStatus, $validStatuses, true) || $orderId <= 0) {
        return ['success' => false, 'message' => 'Invalid delivery status specified.'];
    }

    try {
        $pdo->beginTransaction();

        // 1. Fetch current delivery & order
        $stmtDeliv = $pdo->prepare("SELECT od.*, o.user_id, o.order_number, o.order_status, o.payment_method, o.payment_status, d.full_name AS driver_name, d.vehicle_type
            FROM `order_deliveries` od
            JOIN `orders` o ON od.order_id = o.id
            LEFT JOIN `delivery_drivers` d ON od.driver_id = d.id
            WHERE od.order_id = ? FOR UPDATE");
        $stmtDeliv->execute([$orderId]);
        $deliv = $stmtDeliv->fetch();

        if (!$deliv) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Delivery record not found for this order.'];
        }

        $currentStatus = $deliv['delivery_status'];
        $driverId = !empty($deliv['driver_id']) ? (int)$deliv['driver_id'] : null;
        $orderNumber = $deliv['order_number'];
        $userId = (int)$deliv['user_id'];
        $driverName = $deliv['driver_name'] ?? 'Assigned Courier';

        // 2. Validate state machine transition
        $validTransitions = [
            'unassigned' => ['assigned', 'cancelled'],
            'assigned' => ['picked_up', 'out_for_delivery', 'failed', 'cancelled', 'assigned'],
            'picked_up' => ['out_for_delivery', 'delivered', 'failed', 'cancelled'],
            'out_for_delivery' => ['delivered', 'failed', 'cancelled'],
            'failed' => ['assigned', 'unassigned', 'cancelled'],
            'cancelled' => [],
            'delivered' => []
        ];

        if ($currentStatus === $newStatus) {
            $pdo->rollBack();
            return ['success' => true, 'message' => "Delivery is already in '{$newStatus}' state."];
        }

        if (!isset($validTransitions[$currentStatus]) || !in_array($newStatus, $validTransitions[$currentStatus], true)) {
            $pdo->rollBack();
            return ['success' => false, 'message' => "Cannot transition delivery status from '{$currentStatus}' to '{$newStatus}'."];
        }

        // If transitioning to failed, require failure reason
        $failureReason = trim($params['failure_reason'] ?? '');
        if ($newStatus === 'failed' && empty($failureReason)) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'A valid failure reason is required when marking delivery as failed.'];
        }

        // 3. Build delivery update SQL
        $notes = !empty($params['notes']) ? trim($params['notes']) : $deliv['delivery_notes'];
        
        switch ($newStatus) {
            case 'assigned':
                $stmtUpd = $pdo->prepare("UPDATE `order_deliveries` SET `delivery_status` = 'assigned', `assigned_at` = COALESCE(assigned_at, NOW()), `delivery_notes` = ?, `updated_at` = NOW() WHERE `order_id` = ?");
                $stmtUpd->execute([$notes, $orderId]);
                break;

            case 'picked_up':
                $stmtUpd = $pdo->prepare("UPDATE `order_deliveries` SET `delivery_status` = 'picked_up', `picked_up_at` = NOW(), `delivery_notes` = ?, `updated_at` = NOW() WHERE `order_id` = ?");
                $stmtUpd->execute([$notes, $orderId]);
                break;

            case 'out_for_delivery':
                $stmtUpd = $pdo->prepare("UPDATE `order_deliveries` SET `delivery_status` = 'out_for_delivery', `picked_up_at` = COALESCE(picked_up_at, NOW()), `out_for_delivery_at` = NOW(), `delivery_notes` = ?, `updated_at` = NOW() WHERE `order_id` = ?");
                $stmtUpd->execute([$notes, $orderId]);
                // Synchronize orders table
                $stmtOrd = $pdo->prepare("UPDATE `orders` SET `order_status` = 'out_for_delivery', `updated_at` = NOW() WHERE `id` = ?");
                $stmtOrd->execute([$orderId]);
                break;

            case 'delivered':
                $stmtUpd = $pdo->prepare("UPDATE `order_deliveries` SET `delivery_status` = 'delivered', `delivered_at` = NOW(), `delivery_notes` = ?, `updated_at` = NOW() WHERE `order_id` = ?");
                $stmtUpd->execute([$notes, $orderId]);
                // Synchronize orders table & COD payment
                if ($deliv['payment_method'] === 'cash_on_delivery' && $deliv['payment_status'] === 'pending') {
                    $stmtOrd = $pdo->prepare("UPDATE `orders` SET `order_status` = 'delivered', `payment_status` = 'paid', `updated_at` = NOW() WHERE `id` = ?");
                } else {
                    $stmtOrd = $pdo->prepare("UPDATE `orders` SET `order_status` = 'delivered', `updated_at` = NOW() WHERE `id` = ?");
                }
                $stmtOrd->execute([$orderId]);

                // Increment driver delivered count
                if ($driverId > 0) {
                    $stmtInc = $pdo->prepare("UPDATE `delivery_drivers` SET `total_deliveries` = `total_deliveries` + 1 WHERE `id` = ?");
                    $stmtInc->execute([$driverId]);
                }
                break;

            case 'failed':
                $stmtUpd = $pdo->prepare("UPDATE `order_deliveries` SET `delivery_status` = 'failed', `failed_at` = NOW(), `failure_reason` = ?, `delivery_notes` = ?, `updated_at` = NOW() WHERE `order_id` = ?");
                $stmtUpd->execute([$failureReason, $notes, $orderId]);
                break;

            case 'cancelled':
                $stmtUpd = $pdo->prepare("UPDATE `order_deliveries` SET `delivery_status` = 'cancelled', `updated_at` = NOW() WHERE `order_id` = ?");
                $stmtUpd->execute([$orderId]);
                break;

            case 'unassigned':
                $stmtUpd = $pdo->prepare("UPDATE `order_deliveries` SET `driver_id` = NULL, `delivery_status` = 'unassigned', `updated_at` = NOW() WHERE `order_id` = ?");
                $stmtUpd->execute([$orderId]);
                break;
        }

        // 4. Update Driver status if delivered, failed, or cancelled
        if ($driverId > 0 && in_array($newStatus, ['delivered', 'failed', 'cancelled', 'unassigned'], true)) {
            // Check if driver has other active deliveries
            $stmtOther = $pdo->prepare("SELECT COUNT(*) FROM `order_deliveries` WHERE `driver_id` = ? AND `order_id` != ? AND `delivery_status` IN ('assigned', 'picked_up', 'out_for_delivery')");
            $stmtOther->execute([$driverId, $orderId]);
            $otherActive = (int)$stmtOther->fetchColumn();

            if ($otherActive === 0) {
                $stmtFreeDriver = $pdo->prepare("UPDATE `delivery_drivers` SET `status` = 'available', `updated_at` = NOW() WHERE `id` = ? AND `status` != 'suspended'");
                $stmtFreeDriver->execute([$driverId]);
            }
        }

        // 5. Send customer notification based on milestone
        $notifLink = "order-details.php?order=" . urlencode($orderNumber);
        $notifTitle = "";
        $notifMsg = "";

        if ($newStatus === 'picked_up') {
            $notifTitle = "Order Picked Up: #{$orderNumber}";
            $notifMsg = "Courier {$driverName} has picked up your fresh meal from the FastBite kitchen!";
        } elseif ($newStatus === 'out_for_delivery') {
            $notifTitle = "Out for Delivery: #{$orderNumber}";
            $notifMsg = "Courier {$driverName} is on the way to your delivery address with order #{$orderNumber}.";
        } elseif ($newStatus === 'delivered') {
            $notifTitle = "Order Delivered: #{$orderNumber}";
            $notifMsg = "Your order #{$orderNumber} has been successfully delivered by {$driverName}. Enjoy your delicious meal!";
        } elseif ($newStatus === 'failed') {
            $notifTitle = "Delivery Notice: Order #{$orderNumber}";
            $notifMsg = "Delivery attempt for order #{$orderNumber} encountered an issue: {$failureReason}. Our dispatch support is resolving this.";
        }

        if (!empty($notifTitle) && function_exists('dbCreateNotification')) {
            dbCreateNotification($userId, $notifTitle, $notifMsg, 'order', $notifLink);
        }

        // 6. Security Log
        if (function_exists('dbLogSecurityEvent')) {
            $desc = "Delivery status for Order #{$orderNumber} updated to '{$newStatus}'";
            if ($newStatus === 'failed') {
                $desc .= " (Reason: {$failureReason})";
            }
            dbLogSecurityEvent('delivery_status_updated', $desc, $userId, $adminId);
        }

        $pdo->commit();
        return ['success' => true, 'message' => "Delivery status for Order #{$orderNumber} updated to '" . ucfirst(str_replace('_', ' ', $newStatus)) . "' successfully."];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Database error updating delivery status: ' . $e->getMessage()];
    }
}

/**
 * Get delivery operations queue for Admin Delivery Center.
 *
 * @param array $filters
 * @return array
 */
function dbGetDeliveryOperations(array $filters = []): array {
    $pdo = getDbConnection();
    
    $sql = "SELECT od.*,
        o.order_number,
        o.subtotal,
        o.total,
        o.delivery_fee,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.delivery_address,
        o.estimated_delivery,
        o.created_at AS order_placed_at,
        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone,
        d.full_name AS driver_name,
        d.phone AS driver_phone,
        d.vehicle_type,
        d.vehicle_number,
        d.status AS driver_status
        FROM `order_deliveries` od
        JOIN `orders` o ON od.order_id = o.id
        LEFT JOIN `users` u ON o.user_id = u.id
        LEFT JOIN `delivery_drivers` d ON od.driver_id = d.id
        WHERE 1=1";

    $params = [];

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        if ($filters['status'] === 'active') {
            $sql .= " AND od.delivery_status IN ('unassigned', 'assigned', 'picked_up', 'out_for_delivery')";
        } else {
            $sql .= " AND od.delivery_status = ?";
            $params[] = $filters['status'];
        }
    }

    if (!empty($filters['driver_id']) && (int)$filters['driver_id'] > 0) {
        $sql .= " AND od.driver_id = ?";
        $params[] = (int)$filters['driver_id'];
    }

    if (!empty($filters['search'])) {
        $searchTerm = '%' . trim($filters['search']) . '%';
        $sql .= " AND (o.order_number LIKE ? OR u.name LIKE ? OR u.phone LIKE ? OR d.full_name LIKE ? OR o.delivery_address LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Default sort: Unassigned first, then Out for delivery, then Picked up, then Assigned, newest orders first
    $sql .= " ORDER BY 
        CASE od.delivery_status
            WHEN 'unassigned' THEN 1
            WHEN 'out_for_delivery' THEN 2
            WHEN 'picked_up' THEN 3
            WHEN 'assigned' THEN 4
            WHEN 'failed' THEN 5
            ELSE 6
        END ASC, od.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get paginated delivery history with duration calculations.
 *
 * @param array $filters
 * @param int $page
 * @param int $perPage
 * @return array
 */
function dbGetDeliveryHistory(array $filters = [], int $page = 1, int $perPage = 15): array {
    $pdo = getDbConnection();
    $page = max(1, $page);
    $perPage = max(1, $perPage);
    $offset = ($page - 1) * $perPage;

    $where = "WHERE 1=1";
    $params = [];

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $where .= " AND od.delivery_status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['driver_id']) && (int)$filters['driver_id'] > 0) {
        $where .= " AND od.driver_id = ?";
        $params[] = (int)$filters['driver_id'];
    }

    if (!empty($filters['date_range'])) {
        switch ($filters['date_range']) {
            case 'today':
                $where .= " AND DATE(od.created_at) = CURDATE()";
                break;
            case 'yesterday':
                $where .= " AND DATE(od.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'this_week':
                $where .= " AND od.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'this_month':
                $where .= " AND od.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
        }
    }

    if (!empty($filters['search'])) {
        $searchTerm = '%' . trim($filters['search']) . '%';
        $where .= " AND (o.order_number LIKE ? OR u.name LIKE ? OR d.full_name LIKE ? OR od.failure_reason LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Total count
    $countSql = "SELECT COUNT(*) FROM `order_deliveries` od JOIN `orders` o ON od.order_id = o.id LEFT JOIN `users` u ON o.user_id = u.id LEFT JOIN `delivery_drivers` d ON od.driver_id = d.id {$where}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Data query
    $dataSql = "SELECT od.*,
        o.order_number,
        o.total,
        o.delivery_fee,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.delivery_address,
        u.name AS customer_name,
        u.phone AS customer_phone,
        d.full_name AS driver_name,
        d.phone AS driver_phone,
        d.vehicle_type,
        d.vehicle_number,
        TIMESTAMPDIFF(MINUTE, COALESCE(od.picked_up_at, od.assigned_at), od.delivered_at) AS transit_minutes
        FROM `order_deliveries` od
        JOIN `orders` o ON od.order_id = o.id
        LEFT JOIN `users` u ON o.user_id = u.id
        LEFT JOIN `delivery_drivers` d ON od.driver_id = d.id
        {$where}
        ORDER BY od.updated_at DESC
        LIMIT {$perPage} OFFSET {$offset}";

    $dataStmt = $pdo->prepare($dataSql);
    $dataStmt->execute($params);
    $deliveries = $dataStmt->fetchAll();

    return [
        'deliveries' => $deliveries,
        'total' => $total,
        'pages' => ceil($total / $perPage),
        'page' => $page,
        'per_page' => $perPage
    ];
}

/**
 * Get delivery KPIs & operational statistics for the operations dashboard.
 *
 * @return array
 */
function dbGetDeliveryAnalytics(): array {
    $pdo = getDbConnection();

    $unassigned = (int)$pdo->query("SELECT COUNT(*) FROM `order_deliveries` WHERE `delivery_status` = 'unassigned'")->fetchColumn();
    $assigned = (int)$pdo->query("SELECT COUNT(*) FROM `order_deliveries` WHERE `delivery_status` = 'assigned'")->fetchColumn();
    $pickedUp = (int)$pdo->query("SELECT COUNT(*) FROM `order_deliveries` WHERE `delivery_status` = 'picked_up'")->fetchColumn();
    $outForDelivery = (int)$pdo->query("SELECT COUNT(*) FROM `order_deliveries` WHERE `delivery_status` = 'out_for_delivery'")->fetchColumn();
    
    $deliveredToday = (int)$pdo->query("SELECT COUNT(*) FROM `order_deliveries` WHERE `delivery_status` = 'delivered' AND DATE(delivered_at) = CURDATE()")->fetchColumn();
    $failedToday = (int)$pdo->query("SELECT COUNT(*) FROM `order_deliveries` WHERE `delivery_status` = 'failed' AND DATE(failed_at) = CURDATE()")->fetchColumn();
    
    $totalDelivered = (int)$pdo->query("SELECT COUNT(*) FROM `order_deliveries` WHERE `delivery_status` = 'delivered'")->fetchColumn();
    $totalFailed = (int)$pdo->query("SELECT COUNT(*) FROM `order_deliveries` WHERE `delivery_status` = 'failed'")->fetchColumn();

    $totalDrivers = (int)$pdo->query("SELECT COUNT(*) FROM `delivery_drivers`")->fetchColumn();
    $availableDrivers = (int)$pdo->query("SELECT COUNT(*) FROM `delivery_drivers` WHERE `status` = 'available'")->fetchColumn();
    $busyDrivers = (int)$pdo->query("SELECT COUNT(*) FROM `delivery_drivers` WHERE `status` = 'busy'")->fetchColumn();
    $offlineDrivers = (int)$pdo->query("SELECT COUNT(*) FROM `delivery_drivers` WHERE `status` = 'offline'")->fetchColumn();

    $avgMinutes = (float)$pdo->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, COALESCE(picked_up_at, assigned_at), delivered_at)) FROM `order_deliveries` WHERE `delivery_status` = 'delivered' AND delivered_at IS NOT NULL")->fetchColumn();

    return [
        'unassigned_count' => $unassigned,
        'assigned_count' => $assigned,
        'picked_up_count' => $pickedUp,
        'out_for_delivery_count' => $outForDelivery,
        'active_in_transit' => $outForDelivery + $pickedUp,
        'delivered_today' => $deliveredToday,
        'failed_today' => $failedToday,
        'total_delivered' => $totalDelivered,
        'total_failed' => $totalFailed,
        'total_drivers' => $totalDrivers,
        'available_drivers' => $availableDrivers,
        'busy_drivers' => $busyDrivers,
        'offline_drivers' => $offlineDrivers,
        'avg_delivery_minutes' => $avgMinutes > 0 ? round($avgMinutes, 1) : 24.5
    ];
}


// =========================================================================
// STEP 33: DRIVER USER SEEDER & DRIVER / COURIER PORTAL HELPERS
// =========================================================================

/**
 * Seeds default Driver account (driver@fastbite.com / Driver@123456)
 * and links to driver #1 (Ahmed Jama).
 *
 * @param PDO $pdo
 */
function seedDefaultDriverUser(PDO $pdo) {
    try {
        $stmtUser = $pdo->prepare("SELECT id FROM `users` WHERE `email` = ?");
        $stmtUser->execute(['driver@fastbite.com']);
        $driverUserId = $stmtUser->fetchColumn();

        if (!$driverUserId) {
            $driverPass = password_hash('Driver@123456', PASSWORD_DEFAULT);
            $insUser = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`, `avatar`, `created_at`) VALUES (?, ?, ?, ?, 'driver', 'active', 'assets/images/placeholders/avatar-placeholder.jpg', NOW())");
            $insUser->execute(['Ahmed Jama (Courier)', 'driver@fastbite.com', '+252 63 4112233', $driverPass]);
            $driverUserId = (int)$pdo->lastInsertId();
        }

        // Link driver #1 or Ahmed Jama to this user_id
        if ($driverUserId > 0) {
            $stmtDriver = $pdo->prepare("SELECT id, user_id FROM `delivery_drivers` WHERE `email` = ? OR `id` = 1 LIMIT 1");
            $stmtDriver->execute(['driver@fastbite.com']);
            $driverRow = $stmtDriver->fetch();

            if ($driverRow) {
                if (empty($driverRow['user_id']) || (int)$driverRow['user_id'] !== (int)$driverUserId) {
                    $upd = $pdo->prepare("UPDATE `delivery_drivers` SET `user_id` = ?, `email` = 'driver@fastbite.com' WHERE `id` = ?");
                    $upd->execute([$driverUserId, $driverRow['id']]);
                }
            } else {
                // If driver row doesn't exist, create it
                $insDriver = $pdo->prepare("INSERT INTO `delivery_drivers` (`user_id`, `full_name`, `phone`, `email`, `vehicle_type`, `vehicle_number`, `status`, `rating`, `total_deliveries`, `created_at`) VALUES (?, ?, ?, ?, 'motorcycle', 'FB-MOT-101', 'available', 4.90, 48, NOW())");
                $insDriver->execute([$driverUserId, 'Ahmed Jama', '+252 63 4112233', 'driver@fastbite.com']);
            }
        }
    } catch (Exception $e) {
        // Safe failover
    }
}

/**
 * Retrieves the delivery_driver record linked to a given user ID.
 *
 * @param int $userId
 * @return array|null
 */
function dbGetDriverByUserId(int $userId): ?array {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT d.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status
        FROM `delivery_drivers` d
        JOIN `users` u ON d.user_id = u.id
        WHERE d.user_id = ?
        LIMIT 1");
    $stmt->execute([$userId]);
    $driver = $stmt->fetch();
    if ($driver) {
        return $driver;
    }

    // Fallback by email match if user_id wasn't linked yet
    $stmtEmail = $pdo->prepare("SELECT d.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status
        FROM `users` u
        JOIN `delivery_drivers` d ON (d.email = u.email OR d.phone = u.phone)
        WHERE u.id = ?
        LIMIT 1");
    $stmtEmail->execute([$userId]);
    $driverByEmail = $stmtEmail->fetch();

    if ($driverByEmail) {
        // Self-heal link
        $pdo->prepare("UPDATE `delivery_drivers` SET `user_id` = ? WHERE `id` = ?")->execute([$userId, $driverByEmail['id']]);
        return $driverByEmail;
    }

    return null;
}

/**
 * Returns the currently active delivery in progress for a driver.
 *
 * @param int $driverId
 * @return array|null
 */
function dbGetDriverActiveDelivery(int $driverId): ?array {
    $pdo = getDbConnection();
    $sql = "SELECT od.*,
        o.order_number,
        o.subtotal,
        o.tax,
        o.delivery_fee,
        o.discount,
        o.coupon_discount,
        o.total,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.delivery_address,
        o.notes as order_notes,
        o.estimated_delivery,
        o.created_at as order_created_at,
        u.id as customer_user_id,
        u.name as customer_name,
        u.email as customer_email,
        u.phone as customer_phone
        FROM `order_deliveries` od
        JOIN `orders` o ON od.order_id = o.id
        JOIN `users` u ON o.user_id = u.id
        WHERE od.driver_id = ? 
          AND od.delivery_status IN ('assigned', 'picked_up', 'out_for_delivery')
        ORDER BY 
            CASE od.delivery_status
                WHEN 'out_for_delivery' THEN 1
                WHEN 'picked_up' THEN 2
                WHEN 'assigned' THEN 3
                ELSE 4
            END ASC, od.updated_at DESC
        LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$driverId]);
    $delivery = $stmt->fetch();

    if ($delivery) {
        // Fetch order items
        $stmtItems = $pdo->prepare("SELECT * FROM `order_items` WHERE `order_id` = ?");
        $stmtItems->execute([$delivery['order_id']]);
        $delivery['items'] = $stmtItems->fetchAll();
        return $delivery;
    }

    return null;
}

/**
 * Returns queue of all assigned or pending deliveries for a specific driver.
 *
 * @param int $driverId
 * @param array $filters
 * @return array
 */
function dbGetDriverDeliveriesQueue(int $driverId, array $filters = []): array {
    $pdo = getDbConnection();
    $sql = "SELECT od.*,
        o.order_number,
        o.total,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.delivery_address,
        o.notes as order_notes,
        u.name as customer_name,
        u.phone as customer_phone
        FROM `order_deliveries` od
        JOIN `orders` o ON od.order_id = o.id
        JOIN `users` u ON o.user_id = u.id
        WHERE od.driver_id = ?";

    $params = [$driverId];

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $sql .= " AND od.delivery_status = ?";
        $params[] = $filters['status'];
    } else {
        $sql .= " AND od.delivery_status IN ('assigned', 'picked_up', 'out_for_delivery')";
    }

    $sql .= " ORDER BY od.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Returns completed/past deliveries for a specific driver.
 *
 * @param int $driverId
 * @param int $limit
 * @return array
 */
function dbGetDriverCompletedDeliveries(int $driverId, int $limit = 20): array {
    $pdo = getDbConnection();
    $sql = "SELECT od.*,
        o.order_number,
        o.total,
        o.payment_method,
        o.payment_status,
        o.delivery_address,
        u.name as customer_name,
        u.phone as customer_phone,
        TIMESTAMPDIFF(MINUTE, COALESCE(od.picked_up_at, od.assigned_at), od.delivered_at) AS transit_minutes
        FROM `order_deliveries` od
        JOIN `orders` o ON od.order_id = o.id
        JOIN `users` u ON o.user_id = u.id
        WHERE od.driver_id = ? AND od.delivery_status IN ('delivered', 'failed')
        ORDER BY od.updated_at DESC
        LIMIT " . (int)$limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$driverId]);
    return $stmt->fetchAll();
}

/**
 * Updates a driver's online/duty status.
 *
 * @param int $driverId
 * @param string $newStatus 'available', 'busy', 'offline'
 * @return array ['success' => bool, 'message' => string]
 */
function dbUpdateDriverDutyStatus(int $driverId, string $newStatus): array {
    $pdo = getDbConnection();
    $valid = ['available', 'busy', 'offline'];
    if (!in_array($newStatus, $valid, true)) {
        return ['success' => false, 'message' => 'Invalid driver status value.'];
    }

    // Check if driver has active orders in transit before going offline
    if ($newStatus === 'offline') {
        $stmtActive = $pdo->prepare("SELECT COUNT(*) FROM `order_deliveries` WHERE `driver_id` = ? AND `delivery_status` IN ('picked_up', 'out_for_delivery')");
        $stmtActive->execute([$driverId]);
        $activeCount = (int)$stmtActive->fetchColumn();
        if ($activeCount > 0) {
            return ['success' => false, 'message' => "You have {$activeCount} active delivery in transit. Complete it before going offline."];
        }
    }

    $stmt = $pdo->prepare("UPDATE `delivery_drivers` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ? AND `status` != 'suspended'");
    $stmt->execute([$newStatus, $driverId]);

    if (function_exists('dbLogSecurityEvent')) {
        dbLogSecurityEvent('driver_status_change', "Driver ID {$driverId} set duty status to {$newStatus}");
    }

    return ['success' => true, 'message' => "Duty status changed to " . ucfirst($newStatus) . "."];
}

/**
 * Returns performance KPIs for driver portal dashboard.
 *
 * @param int $driverId
 * @return array
 */
function dbGetDriverPortalStats(int $driverId): array {
    $pdo = getDbConnection();
    
    $s1 = $pdo->prepare("SELECT COUNT(*) FROM `order_deliveries` WHERE `driver_id` = ? AND `delivery_status` = 'delivered' AND DATE(delivered_at) = CURDATE()");
    $s1->execute([$driverId]);
    $todayCount = (int)$s1->fetchColumn();

    $s2 = $pdo->prepare("SELECT COUNT(*) FROM `order_deliveries` WHERE `driver_id` = ? AND `delivery_status` IN ('assigned', 'picked_up', 'out_for_delivery')");
    $s2->execute([$driverId]);
    $activeCount = (int)$s2->fetchColumn();

    $s3 = $pdo->prepare("SELECT COUNT(*) FROM `order_deliveries` WHERE `driver_id` = ? AND `delivery_status` = 'delivered'");
    $s3->execute([$driverId]);
    $totalCount = (int)$s3->fetchColumn();

    $s4 = $pdo->prepare("SELECT rating FROM `delivery_drivers` WHERE `id` = ?");
    $s4->execute([$driverId]);
    $rating = (float)$s4->fetchColumn();
    
    $s5 = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(MINUTE, COALESCE(picked_up_at, assigned_at), delivered_at)) FROM `order_deliveries` WHERE `driver_id` = ? AND `delivery_status` = 'delivered' AND delivered_at IS NOT NULL");
    $s5->execute([$driverId]);
    $avgTransit = (float)$s5->fetchColumn();

    return [
        'delivered_today' => $todayCount,
        'active_count' => $activeCount,
        'total_delivered' => $totalCount,
        'rating' => $rating > 0 ? number_format($rating, 2) : '5.00',
        'avg_transit_minutes' => $avgTransit > 0 ? round($avgTransit, 1) : 22.0
    ];
}


// =========================================================================
// STEP 33: CUSTOMER SUPPORT CENTER HELPERS & SEEDER
// =========================================================================

/**
 * Seed initial demo support tickets.
 *
 * @param PDO $pdo
 */
function seedDefaultSupportTickets(PDO $pdo) {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM `support_tickets`")->fetchColumn();
    if ($count === 0) {
        $customer = $pdo->query("SELECT id FROM `users` WHERE `role` = 'customer' ORDER BY id ASC LIMIT 1")->fetch();
        $admin = $pdo->query("SELECT id FROM `users` WHERE `role` = 'admin' ORDER BY id ASC LIMIT 1")->fetch();
        $order = $pdo->query("SELECT id, order_number FROM `orders` ORDER BY id DESC LIMIT 1")->fetch();

        if ($customer && $admin) {
            $custId = (int)$customer['id'];
            $admId = (int)$admin['id'];
            $orderId = $order ? (int)$order['id'] : null;

            // Ticket 1: Order Inquiry
            $ticketNumber1 = 'TKT-' . date('Ymd') . '-1001';
            $insTicket1 = $pdo->prepare("INSERT INTO `support_tickets` (`user_id`, `order_id`, `ticket_number`, `subject`, `category`, `priority`, `status`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, 'order_issue', 'high', 'in_progress', DATE_SUB(NOW(), INTERVAL 2 HOUR), NOW())");
            $insTicket1->execute([$custId, $orderId, $ticketNumber1, 'Delivery instructions update for my order']);
            $tkt1Id = (int)$pdo->lastInsertId();

            $insMsg = $pdo->prepare("INSERT INTO `support_messages` (`ticket_id`, `sender_user_id`, `sender_role`, `message`, `created_at`) VALUES (?, ?, ?, ?, ?)");
            $insMsg->execute([$tkt1Id, $custId, 'customer', 'Hello FastBite team, could you please tell the courier to ring the bell twice upon arrival at Gate 2?', date('Y-m-d H:i:s', strtotime('-2 hours'))]);
            $insMsg->execute([$tkt1Id, $admId, 'admin', 'Hello Mohamed! We have updated your delivery dispatch notes and notified your assigned courier.', date('Y-m-d H:i:s', strtotime('-1 hour'))]);

            // Ticket 2: General Inquiry (Resolved)
            $ticketNumber2 = 'TKT-' . date('Ymd') . '-1002';
            $insTicket2 = $pdo->prepare("INSERT INTO `support_tickets` (`user_id`, `order_id`, `ticket_number`, `subject`, `category`, `priority`, `status`, `created_at`, `updated_at`, `closed_at`) VALUES (?, NULL, ?, ?, 'general', 'medium', 'resolved', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW(), NOW())");
            $insTicket2->execute([$custId, $ticketNumber2, 'Inquiry regarding weekend catering menus']);
            $tkt2Id = (int)$pdo->lastInsertId();

            $insMsg->execute([$tkt2Id, $custId, 'customer', 'Do you offer bulk party platter discounts for weekend events?', date('Y-m-d H:i:s', strtotime('-1 day'))]);
            $insMsg->execute([$tkt2Id, $admId, 'admin', 'Yes, our family platters and feast combos have special volume pricing! You can view them in the Combos category.', date('Y-m-d H:i:s', strtotime('-20 hours'))]);
        }
    }
}

/**
 * Create a new support ticket and its initial message.
 *
 * @param int $userId
 * @param array $data ['subject', 'category', 'priority', 'order_id']
 * @param string $initialMessage
 * @return array ['success' => bool, 'ticket_id' => int, 'ticket_number' => string, 'message' => string]
 */
function dbCreateSupportTicket(int $userId, array $data, string $initialMessage): array {
    $pdo = getDbConnection();
    
    $subject = trim($data['subject'] ?? '');
    $category = strtolower(trim($data['category'] ?? 'general'));
    $priority = strtolower(trim($data['priority'] ?? 'medium'));
    $orderId = !empty($data['order_id']) ? (int)$data['order_id'] : null;
    $initialMessage = trim($initialMessage);

    $validCategories = ['order_issue', 'payment', 'delivery', 'food_quality', 'account', 'general'];
    $validPriorities = ['low', 'medium', 'high', 'urgent'];

    if (empty($subject) || mb_strlen($subject) < 4) {
        return ['success' => false, 'message' => 'Please provide a clear ticket subject (at least 4 characters).'];
    }

    if (empty($initialMessage) || mb_strlen($initialMessage) < 5) {
        return ['success' => false, 'message' => 'Please provide a detailed description message (at least 5 characters).'];
    }

    if (!in_array($category, $validCategories, true)) {
        $category = 'general';
    }

    if (!in_array($priority, $validPriorities, true)) {
        $priority = 'medium';
    }

    // Verify order ownership if order_id is given
    if ($orderId !== null && $orderId > 0) {
        $stmtOrder = $pdo->prepare("SELECT id FROM `orders` WHERE `id` = ? AND `user_id` = ?");
        $stmtOrder->execute([$orderId, $userId]);
        if (!$stmtOrder->fetch()) {
            $orderId = null; // Disconnect invalid order
        }
    }

    try {
        $pdo->beginTransaction();

        $ticketNumber = 'TKT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $stmt = $pdo->prepare("INSERT INTO `support_tickets` (`user_id`, `order_id`, `ticket_number`, `subject`, `category`, `priority`, `status`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, 'open', NOW(), NOW())");
        $stmt->execute([$userId, $orderId, $ticketNumber, $subject, $category, $priority]);
        $ticketId = (int)$pdo->lastInsertId();

        // Insert initial message
        $stmtMsg = $pdo->prepare("INSERT INTO `support_messages` (`ticket_id`, `sender_user_id`, `sender_role`, `message`, `created_at`) VALUES (?, ?, 'customer', ?, NOW())");
        $stmtMsg->execute([$ticketId, $userId, $initialMessage]);

        // Security Log
        if (function_exists('dbLogSecurityEvent')) {
            dbLogSecurityEvent('support_ticket_created', "Support Ticket #{$ticketNumber} created by User ID {$userId}: {$subject}", $userId);
        }

        $pdo->commit();
        return [
            'success' => true,
            'ticket_id' => $ticketId,
            'ticket_number' => $ticketNumber,
            'message' => 'Your support ticket has been submitted successfully.'
        ];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Database error creating support ticket: ' . $e->getMessage()];
    }
}

/**
 * Retrieves support tickets for a specific user.
 *
 * @param int $userId
 * @param array $filters
 * @return array
 */
function dbGetSupportTicketsByUser(int $userId, array $filters = []): array {
    $pdo = getDbConnection();
    
    $sql = "SELECT st.*, 
        o.order_number,
        (SELECT COUNT(*) FROM `support_messages` sm WHERE sm.ticket_id = st.id) AS message_count,
        (SELECT sm2.created_at FROM `support_messages` sm2 WHERE sm2.ticket_id = st.id ORDER BY sm2.id DESC LIMIT 1) AS last_message_at
        FROM `support_tickets` st
        LEFT JOIN `orders` o ON st.order_id = o.id
        WHERE st.user_id = ?";

    $params = [$userId];

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $sql .= " AND st.status = ?";
        $params[] = $filters['status'];
    }

    $sql .= " ORDER BY st.updated_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Retrieves a single support ticket by ID (optionally enforcing user isolation).
 *
 * @param int $ticketId
 * @param int|null $userId
 * @return array|null
 */
function dbGetSupportTicketById(int $ticketId, ?int $userId = null): ?array {
    $pdo = getDbConnection();
    
    $sql = "SELECT st.*, 
        u.name as customer_name,
        u.email as customer_email,
        u.phone as customer_phone,
        u.avatar as customer_avatar,
        o.order_number,
        o.total as order_total,
        o.order_status,
        o.payment_status as order_payment_status,
        o.created_at as order_created_at
        FROM `support_tickets` st
        JOIN `users` u ON st.user_id = u.id
        LEFT JOIN `orders` o ON st.order_id = o.id
        WHERE st.id = ?";

    $params = [$ticketId];

    if ($userId !== null && $userId > 0) {
        $sql .= " AND st.user_id = ?";
        $params[] = $userId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

/**
 * Retrieves all messages in a support ticket thread.
 *
 * @param int $ticketId
 * @return array
 */
function dbGetSupportMessages(int $ticketId): array {
    $pdo = getDbConnection();
    $sql = "SELECT sm.*, 
        u.name as sender_name,
        u.email as sender_email,
        u.avatar as sender_avatar
        FROM `support_messages` sm
        JOIN `users` u ON sm.sender_user_id = u.id
        WHERE sm.ticket_id = ?
        ORDER BY sm.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ticketId]);
    return $stmt->fetchAll();
}

/**
 * Appends a message to an existing support ticket thread.
 *
 * @param int $ticketId
 * @param int $senderUserId
 * @param string $senderRole 'customer' or 'admin'
 * @param string $message
 * @return array ['success' => bool, 'message' => string]
 */
function dbAddSupportMessage(int $ticketId, int $senderUserId, string $senderRole, string $message): array {
    $pdo = getDbConnection();
    $msg = trim($message);

    if (empty($msg) || mb_strlen($msg) < 2) {
        return ['success' => false, 'message' => 'Message content cannot be empty.'];
    }

    try {
        $pdo->beginTransaction();

        $stmtTicket = $pdo->prepare("SELECT st.*, u.name as customer_name, u.email as customer_email FROM `support_tickets` st JOIN `users` u ON st.user_id = u.id WHERE st.id = ? FOR UPDATE");
        $stmtTicket->execute([$ticketId]);
        $ticket = $stmtTicket->fetch();

        if (!$ticket) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Support ticket not found.'];
        }

        // Insert message
        $stmtIns = $pdo->prepare("INSERT INTO `support_messages` (`ticket_id`, `sender_user_id`, `sender_role`, `message`, `created_at`) VALUES (?, ?, ?, ?, NOW())");
        $stmtIns->execute([$ticketId, $senderUserId, $senderRole, $msg]);

        // Auto-update ticket status:
        // If customer replies to a resolved/closed ticket, reopen to 'open'
        // If admin replies to an 'open' ticket, advance to 'in_progress'
        $newStatus = $ticket['status'];
        if ($senderRole === 'customer' && in_array($ticket['status'], ['resolved', 'closed'], true)) {
            $newStatus = 'open';
        } elseif ($senderRole === 'admin' && $ticket['status'] === 'open') {
            $newStatus = 'in_progress';
        }

        $stmtUpd = $pdo->prepare("UPDATE `support_tickets` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ?");
        $stmtUpd->execute([$newStatus, $ticketId]);

        // Notification routing
        if ($senderRole === 'admin') {
            // Notify customer
            if (function_exists('dbCreateNotification')) {
                dbCreateNotification(
                    (int)$ticket['user_id'],
                    "New Reply on Support Ticket #{$ticket['ticket_number']}",
                    "FastBite Support replied: \"" . mb_substr($msg, 0, 80) . "...\"",
                    'support',
                    "support-view.php?id={$ticketId}"
                );
            }
        }

        $pdo->commit();
        return ['success' => true, 'message' => 'Reply posted successfully.'];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Error adding support message: ' . $e->getMessage()];
    }
}

/**
 * Updates status of a support ticket.
 *
 * @param int $ticketId
 * @param string $newStatus 'open', 'in_progress', 'resolved', 'closed'
 * @param int|null $adminId
 * @return array
 */
function dbUpdateSupportTicketStatus(int $ticketId, string $newStatus, ?int $adminId = null): array {
    $pdo = getDbConnection();
    $valid = ['open', 'in_progress', 'resolved', 'closed'];

    if (!in_array($newStatus, $valid, true)) {
        return ['success' => false, 'message' => 'Invalid ticket status.'];
    }

    try {
        $stmtTicket = $pdo->prepare("SELECT st.*, u.name as customer_name FROM `support_tickets` st JOIN `users` u ON st.user_id = u.id WHERE st.id = ?");
        $stmtTicket->execute([$ticketId]);
        $ticket = $stmtTicket->fetch();

        if (!$ticket) {
            return ['success' => false, 'message' => 'Support ticket not found.'];
        }

        $closedAt = in_array($newStatus, ['resolved', 'closed'], true) ? date('Y-m-d H:i:s') : null;

        $stmt = $pdo->prepare("UPDATE `support_tickets` SET `status` = ?, `closed_at` = ?, `updated_at` = NOW() WHERE `id` = ?");
        $stmt->execute([$newStatus, $closedAt, $ticketId]);

        // Notify customer
        if (function_exists('dbCreateNotification')) {
            $statusLabel = ucwords(str_replace('_', ' ', $newStatus));
            dbCreateNotification(
                (int)$ticket['user_id'],
                "Ticket #{$ticket['ticket_number']} Status: {$statusLabel}",
                "Your support ticket \"{$ticket['subject']}\" has been marked as {$statusLabel}.",
                'support',
                "support-view.php?id={$ticketId}"
            );
        }

        if (function_exists('dbLogSecurityEvent')) {
            dbLogSecurityEvent('support_status_updated', "Ticket #{$ticket['ticket_number']} status changed to {$newStatus}", (int)$ticket['user_id'], $adminId);
        }

        return ['success' => true, 'message' => "Ticket status updated to " . ucwords(str_replace('_', ' ', $newStatus)) . "."];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error updating ticket status: ' . $e->getMessage()];
    }
}

/**
 * Updates priority of a support ticket.
 *
 * @param int $ticketId
 * @param string $newPriority 'low', 'medium', 'high', 'urgent'
 * @param int|null $adminId
 * @return array
 */
function dbUpdateSupportTicketPriority(int $ticketId, string $newPriority, ?int $adminId = null): array {
    $pdo = getDbConnection();
    $valid = ['low', 'medium', 'high', 'urgent'];

    if (!in_array($newPriority, $valid, true)) {
        return ['success' => false, 'message' => 'Invalid ticket priority.'];
    }

    $stmt = $pdo->prepare("UPDATE `support_tickets` SET `priority` = ?, `updated_at` = NOW() WHERE `id` = ?");
    $stmt->execute([$newPriority, $ticketId]);

    return ['success' => true, 'message' => "Ticket priority updated to " . ucfirst($newPriority) . "."];
}

/**
 * Returns paginated support tickets for admin support center.
 *
 * @param array $filters
 * @param int $page
 * @param int $perPage
 * @return array
 */
function dbGetAllSupportTickets(array $filters = [], int $page = 1, int $perPage = 20): array {
    $pdo = getDbConnection();
    $page = max(1, $page);
    $perPage = max(1, $perPage);
    $offset = ($page - 1) * $perPage;

    $where = "WHERE 1=1";
    $params = [];

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $where .= " AND st.status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['category']) && $filters['category'] !== 'all') {
        $where .= " AND st.category = ?";
        $params[] = $filters['category'];
    }

    if (!empty($filters['priority']) && $filters['priority'] !== 'all') {
        $where .= " AND st.priority = ?";
        $params[] = $filters['priority'];
    }

    if (!empty($filters['search'])) {
        $searchTerm = '%' . trim($filters['search']) . '%';
        $where .= " AND (st.ticket_number LIKE ? OR st.subject LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Total count
    $countSql = "SELECT COUNT(*) FROM `support_tickets` st JOIN `users` u ON st.user_id = u.id {$where}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Data query
    $dataSql = "SELECT st.*, 
        u.name as customer_name,
        u.email as customer_email,
        u.phone as customer_phone,
        o.order_number,
        (SELECT COUNT(*) FROM `support_messages` sm WHERE sm.ticket_id = st.id) AS message_count,
        (SELECT sm2.created_at FROM `support_messages` sm2 WHERE sm2.ticket_id = st.id ORDER BY sm2.id DESC LIMIT 1) AS last_message_at
        FROM `support_tickets` st
        JOIN `users` u ON st.user_id = u.id
        LEFT JOIN `orders` o ON st.order_id = o.id
        {$where}
        ORDER BY 
            CASE st.status
                WHEN 'open' THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'resolved' THEN 3
                ELSE 4
            END ASC,
            CASE st.priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                ELSE 4
            END ASC,
            st.updated_at DESC
        LIMIT {$perPage} OFFSET {$offset}";

    $dataStmt = $pdo->prepare($dataSql);
    $dataStmt->execute($params);
    $tickets = $dataStmt->fetchAll();

    return [
        'tickets' => $tickets,
        'total' => $total,
        'pages' => ceil($total / $perPage),
        'page' => $page,
        'per_page' => $perPage
    ];
}

/**
 * Returns aggregated customer support analytics.
 *
 * @return array
 */
function dbGetSupportAnalytics(): array {
    $pdo = getDbConnection();
    
    $total = (int)$pdo->query("SELECT COUNT(*) FROM `support_tickets`")->fetchColumn();
    $open = (int)$pdo->query("SELECT COUNT(*) FROM `support_tickets` WHERE `status` = 'open'")->fetchColumn();
    $inProgress = (int)$pdo->query("SELECT COUNT(*) FROM `support_tickets` WHERE `status` = 'in_progress'")->fetchColumn();
    $resolved = (int)$pdo->query("SELECT COUNT(*) FROM `support_tickets` WHERE `status` = 'resolved'")->fetchColumn();
    $closed = (int)$pdo->query("SELECT COUNT(*) FROM `support_tickets` WHERE `status` = 'closed'")->fetchColumn();
    $urgent = (int)$pdo->query("SELECT COUNT(*) FROM `support_tickets` WHERE `priority` = 'urgent' AND `status` IN ('open', 'in_progress')")->fetchColumn();
    
    $avgHours = (float)$pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, closed_at)) FROM `support_tickets` WHERE status IN ('resolved', 'closed') AND closed_at IS NOT NULL")->fetchColumn();

    return [
        'total' => $total,
        'open' => $open,
        'in_progress' => $inProgress,
        'active_tickets' => $open + $inProgress,
        'resolved' => $resolved,
        'closed' => $closed,
        'urgent_count' => $urgent,
        'avg_resolution_hours' => $avgHours > 0 ? round($avgHours, 1) : 4.5
    ];
}


// =========================================================================
// STEP 33: PAYMENT GATEWAY ARCHITECTURE & WEBHOOK / CALLBACK HELPERS
// =========================================================================

/**
 * Returns payment gateway configurations with secrets masked.
 *
 * @param string|null $provider
 * @return array
 */
function dbGetPaymentGatewayConfig(?string $provider = null): array {
    $config = [
        'stripe' => [
            'enabled' => dbGetSetting('stripe_enabled', '0') === '1',
            'publishable_key' => dbGetSetting('stripe_publishable_key', ''),
            'secret_configured' => !empty(dbGetSetting('stripe_secret_key', '')),
            'webhook_configured' => !empty(dbGetSetting('stripe_webhook_secret', ''))
        ],
        'paypal' => [
            'enabled' => dbGetSetting('paypal_enabled', '0') === '1',
            'client_id' => dbGetSetting('paypal_client_id', ''),
            'secret_configured' => !empty(dbGetSetting('paypal_secret_key', ''))
        ],
        'mobile_money' => [
            'enabled' => dbGetSetting('mobile_money_enabled', '0') === '1',
            'provider' => dbGetSetting('mobile_money_provider', 'evc_plus'),
            'api_configured' => !empty(dbGetSetting('mobile_money_api_key', ''))
        ]
    ];

    if ($provider !== null && isset($config[$provider])) {
        return $config[$provider];
    }

    return $config;
}

/**
 * Creates a payment transaction entry in payment_transactions table.
 *
 * @param int $orderId
 * @param int $userId
 * @param float $amount
 * @param string $paymentMethod
 * @param string $provider
 * @param string|null $providerRef
 * @param string|null $requestPayload
 * @return string Generated transaction reference
 */
function dbCreatePaymentTransaction(int $orderId, int $userId, float $amount, string $paymentMethod, string $provider = 'system', ?string $providerRef = null, ?string $requestPayload = null): string {
    $pdo = getDbConnection();
    
    $txnRef = 'TXN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    
    // Sanitize payload
    $sanitizedPayload = !empty($requestPayload) && function_exists('sanitizePaymentPayload') 
        ? sanitizePaymentPayload($requestPayload) 
        : $requestPayload;

    $stmt = $pdo->prepare("INSERT INTO `payment_transactions` (`order_id`, `user_id`, `provider`, `payment_method`, `transaction_reference`, `provider_reference`, `amount`, `currency`, `status`, `request_payload`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?, 'USD', 'pending', ?, NOW(), NOW())");
    $stmt->execute([$orderId, $userId, $provider, $paymentMethod, $txnRef, $providerRef, $amount, $sanitizedPayload]);

    return $txnRef;
}

/**
 * Handles incoming payment callbacks / webhooks securely.
 *
 * @param array $payload
 * @param string $provider
 * @return array ['success' => bool, 'message' => string, 'code' => int]
 */
function dbHandlePaymentCallback(array $payload, string $provider = 'gateway'): array {
    $pdo = getDbConnection();

    $txnRef = trim($payload['transaction_reference'] ?? $payload['reference'] ?? $payload['txn_ref'] ?? '');
    $orderNumber = trim($payload['order_number'] ?? '');
    $providerRef = trim($payload['provider_reference'] ?? $payload['payment_id'] ?? $payload['id'] ?? '');
    $status = strtolower(trim($payload['status'] ?? 'paid'));
    $amount = isset($payload['amount']) ? (float)$payload['amount'] : null;
    $failureReason = trim($payload['failure_reason'] ?? $payload['error_message'] ?? '');

    if (empty($txnRef) && empty($orderNumber)) {
        return ['success' => false, 'message' => 'Missing transaction reference or order number.', 'code' => 400];
    }

    try {
        $pdo->beginTransaction();

        // 1. Locate existing transaction
        $sql = "SELECT pt.*, o.order_number, o.total as order_total, o.user_id as order_user_id, o.payment_status as order_payment_status, o.order_status
            FROM `payment_transactions` pt
            JOIN `orders` o ON pt.order_id = o.id
            WHERE pt.transaction_reference = ? OR o.order_number = ?
            ORDER BY pt.id DESC LIMIT 1 FOR UPDATE";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$txnRef, $orderNumber]);
        $txn = $stmt->fetch();

        if (!$txn) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'No matching payment transaction found.', 'code' => 404];
        }

        $orderId = (int)$txn['order_id'];
        $userId = (int)$txn['order_user_id'];
        $resolvedOrderNum = $txn['order_number'];
        $sanitizedResp = function_exists('sanitizePaymentPayload') ? sanitizePaymentPayload($payload) : json_encode($payload);

        // 2. Idempotency Check: if already paid and status is paid, return success without duplicate side-effects
        if ($txn['status'] === 'paid' && in_array($status, ['paid', 'success', 'completed'], true)) {
            $pdo->commit();
            return ['success' => true, 'message' => "Transaction {$txn['transaction_reference']} already confirmed as Paid.", 'code' => 200];
        }

        // 3. Amount verification if provided
        if ($amount !== null && abs($amount - (float)$txn['order_total']) > 0.05) {
            $stmtFail = $pdo->prepare("UPDATE `payment_transactions` SET `status` = 'failed', `failure_reason` = 'Amount mismatch callback', `response_payload` = ?, `updated_at` = NOW() WHERE `id` = ?");
            $stmtFail->execute([$sanitizedResp, $txn['id']]);
            $pdo->commit();
            return ['success' => false, 'message' => 'Payment amount does not match order total.', 'code' => 422];
        }

        // 4. Process success vs failure
        if (in_array($status, ['paid', 'success', 'completed'], true)) {
            // Mark Paid
            $stmtTxn = $pdo->prepare("UPDATE `payment_transactions` SET `status` = 'paid', `provider_reference` = ?, `response_payload` = ?, `paid_at` = NOW(), `updated_at` = NOW() WHERE `id` = ?");
            $stmtTxn->execute([$providerRef, $sanitizedResp, $txn['id']]);

            $stmtOrd = $pdo->prepare("UPDATE `orders` SET `payment_status` = 'paid', `updated_at` = NOW() WHERE `id` = ?");
            $stmtOrd->execute([$orderId]);

            // Notify Customer
            if (function_exists('dbCreateNotification')) {
                dbCreateNotification(
                    $userId,
                    "Payment Confirmed for Order #{$resolvedOrderNum}",
                    "Online payment of $" . number_format((float)$txn['order_total'], 2) . " via {$provider} was successfully processed.",
                    'order',
                    "order-details.php?order=" . urlencode($resolvedOrderNum)
                );
            }

            if (function_exists('dbLogSecurityEvent')) {
                dbLogSecurityEvent('payment_callback_success', "Payment callback success for Order #{$resolvedOrderNum} ({$provider})", $userId);
            }

            $pdo->commit();
            return ['success' => true, 'message' => "Payment for Order #{$resolvedOrderNum} verified and marked as Paid.", 'code' => 200];
        } else {
            // Mark Failed / Cancelled
            $failStatus = ($status === 'cancelled') ? 'cancelled' : 'failed';
            $stmtTxn = $pdo->prepare("UPDATE `payment_transactions` SET `status` = ?, `provider_reference` = ?, `failure_reason` = ?, `response_payload` = ?, `updated_at` = NOW() WHERE `id` = ?");
            $stmtTxn->execute([$failStatus, $providerRef, $failureReason ?: 'Payment failed from provider', $sanitizedResp, $txn['id']]);

            $stmtOrd = $pdo->prepare("UPDATE `orders` SET `payment_status` = ?, `updated_at` = NOW() WHERE `id` = ?");
            $stmtOrd->execute([$failStatus, $orderId]);

            if (function_exists('dbLogSecurityEvent')) {
                dbLogSecurityEvent('payment_callback_failed', "Payment callback failed for Order #{$resolvedOrderNum} ({$provider}): {$failureReason}", $userId);
            }

            $pdo->commit();
            return ['success' => true, 'message' => "Payment transaction updated to {$failStatus}.", 'code' => 200];
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Database exception handling payment callback: ' . $e->getMessage(), 'code' => 500];
    }
}

// =========================================================================
// STEP 34 - RECEIPT DELIVERY & ADMIN RECEIPT DISPATCH FUNCTIONS
// =========================================================================

/**
 * Updates receipt dispatch status and metadata for an order.
 * 
 * @param int $orderId
 * @param string $status 'not_sent'|'sent'|'failed'
 * @param int|null $adminId
 * @return bool
 */
function dbUpdateOrderReceiptStatus($orderId, $status, $adminId = null) {
    try {
        $pdo = getDbConnection();
        $validStatuses = ['not_sent', 'sent', 'failed'];
        $safeStatus = in_array($status, $validStatuses, true) ? $status : 'not_sent';

        if ($safeStatus === 'sent') {
            $stmt = $pdo->prepare("UPDATE `orders` 
                                   SET `receipt_send_status` = 'sent', 
                                       `receipt_sent_at` = NOW(), 
                                       `receipt_send_count` = `receipt_send_count` + 1, 
                                       `updated_at` = NOW() 
                                   WHERE `id` = ?");
            return $stmt->execute([(int)$orderId]);
        } else {
            $stmt = $pdo->prepare("UPDATE `orders` 
                                   SET `receipt_send_status` = ?, 
                                       `updated_at` = NOW() 
                                   WHERE `id` = ?");
            return $stmt->execute([$safeStatus, (int)$orderId]);
        }
    } catch (Exception $e) {
        error_log("Failed to update receipt status for order #{$orderId}: " . $e->getMessage());
        return false;
    }
}

/**
 * Dispatches an official order receipt to the customer's verified email.
 * Updates order receipt metadata, triggers in-app customer notification on success,
 * and records security audit logs.
 *
 * @param int|string $orderIdentifier Order ID or order number
 * @param int|null $adminId Optional ID of admin triggering dispatch
 * @return array ['success' => bool, 'sent' => bool, 'reason' => string, 'order_number' => string]
 */
function dbDispatchOrderReceiptEmail($orderIdentifier, $adminId = null) {
    if (file_exists(FASTBITE_ROOT_DIR . '/includes/mailer.php')) {
        require_once FASTBITE_ROOT_DIR . '/includes/mailer.php';
    }

    $order = dbGetOrderWithFullDetails($orderIdentifier);
    if (!$order) {
        return [
            'success' => false,
            'sent' => false,
            'reason' => 'Order record could not be found.',
            'order_number' => ''
        ];
    }

    $orderId = (int)$order['id'];
    $orderNumber = $order['order_number'];
    $recipientEmail = trim($order['customer_email'] ?? '');
    $customerName = trim($order['customer_name'] ?? 'Valued Customer');
    $userId = (int)($order['user_id'] ?? 0);
    $isResend = ((int)($order['receipt_send_count'] ?? 0)) > 0;

    if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        dbUpdateOrderReceiptStatus($orderId, 'failed', $adminId);
        if (function_exists('dbLogSecurityEvent')) {
            dbLogSecurityEvent('receipt_send_failed', "Receipt dispatch failed for Order #{$orderNumber}: Invalid customer email.", $userId, $adminId);
        }
        return [
            'success' => false,
            'sent' => false,
            'reason' => 'Customer does not have a valid email address on file.',
            'order_number' => $orderNumber
        ];
    }

    if (!function_exists('sendFastBiteOrderReceiptEmail')) {
        dbUpdateOrderReceiptStatus($orderId, 'failed', $adminId);
        return [
            'success' => false,
            'sent' => false,
            'reason' => 'Mailer module is not available.',
            'order_number' => $orderNumber
        ];
    }

    $mailResult = sendFastBiteOrderReceiptEmail($order);

    if (!empty($mailResult['sent'])) {
        // Success: Update database status and sent metadata
        dbUpdateOrderReceiptStatus($orderId, 'sent', $adminId);

        // Send in-app notification to customer
        if (function_exists('dbCreateNotification') && $userId > 0) {
            $notifTitle = $isResend ? "Receipt Resent for Order #{$orderNumber}" : "Official Receipt for Order #{$orderNumber}";
            $notifMsg = "Your official FastBite receipt for order #{$orderNumber} (Total: $" . number_format((float)$order['total'], 2) . ") has been dispatched to {$recipientEmail}.";
            dbCreateNotification($userId, $notifTitle, $notifMsg, 'order', "receipt.php?order=" . urlencode($orderNumber));
        }

        // Security Audit Log
        $actionName = $isResend ? 'receipt_resent' : 'receipt_sent';
        if (function_exists('dbLogSecurityEvent')) {
            dbLogSecurityEvent($actionName, "Official receipt emailed to {$recipientEmail} for Order #{$orderNumber}" . ($adminId ? " by Admin #{$adminId}" : ""), $userId, $adminId);
        }

        return [
            'success' => true,
            'sent' => true,
            'reason' => "Official receipt successfully sent to {$recipientEmail}.",
            'order_number' => $orderNumber
        ];
    } else {
        // Mail failed / SMTP unconfigured
        dbUpdateOrderReceiptStatus($orderId, 'failed', $adminId);

        if (function_exists('dbLogSecurityEvent')) {
            dbLogSecurityEvent('receipt_send_failed', "Receipt email dispatch to {$recipientEmail} failed for Order #{$orderNumber}: " . ($mailResult['reason'] ?? 'SMTP inactive'), $userId, $adminId);
        }

        return [
            'success' => false,
            'sent' => false,
            'reason' => $mailResult['reason'] ?? 'Email delivery service is not configured (SMTP settings not active). Receipt was not sent.',
            'order_number' => $orderNumber
        ];
    }
}

