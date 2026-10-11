<?php
/**
 * FastBite - Central Image Management Helper
 * Step 22: FastBite Image Management & Real Image Integration
 * 
 * Provides unified, safe helper functions to resolve real image paths
 * with automated fallbacks to appropriate placeholders when files are absent.
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(__DIR__));
}

/**
 * Checks whether an image file exists in the file system.
 * Supports checking with alternative extensions (.jpg, .jpeg, .png, .webp).
 *
 * @param string $relativePath Path relative to the project root (e.g. 'assets/images/foods/pizza.jpg')
 * @param string $fallbackPlaceholder Fallback placeholder relative path
 * @return string|null Safe relative web path to the existing image or fallback placeholder (or null)
 */
function getImageOrPlaceholder($relativePath, $fallbackPlaceholder = 'assets/images/placeholders/default-placeholder.jpg') {
    if (empty($relativePath)) {
        return $fallbackPlaceholder;
    }

    $cleanPath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $fullPath = FASTBITE_ROOT_DIR . '/' . $cleanPath;

    // 1. Direct file check
    if (file_exists($fullPath) && is_file($fullPath)) {
        return $cleanPath;
    }

    // 2. Alternative extension check if file not directly found
    $pathInfo = pathinfo($cleanPath);
    $dirName = !empty($pathInfo['dirname']) && $pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] : '';
    $fileName = $pathInfo['filename'];
    $extensions = ['jpg', 'jpeg', 'png', 'webp'];

    foreach ($extensions as $ext) {
        $testRelative = ($dirName ? $dirName . '/' : '') . $fileName . '.' . $ext;
        $testFull = FASTBITE_ROOT_DIR . '/' . $testRelative;
        if (file_exists($testFull) && is_file($testFull)) {
            return $testRelative;
        }
    }

    // 3. Fallback to placeholder
    return $fallbackPlaceholder;
}

/**
 * Converts a string into a clean URL / filename slug.
 *
 * @param string $text
 * @return string
 */
function fastbiteSlugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

/**
 * Resolves a food item image path.
 * Checks in `assets/images/foods/` and `uploads/foods/`.
 * Supports filename, slug, or full name.
 *
 * @param string $nameOrFilename File name, slug, or food title (e.g. 'chicken-supreme-pizza.jpg', 'Chicken Supreme Pizza')
 * @return string Safe relative path
 */
function getFoodImage($nameOrFilename) {
    $placeholder = 'assets/images/placeholders/food-placeholder.jpg';
    if (empty($nameOrFilename)) {
        return $placeholder;
    }

    // If full relative path was already passed
    if (strpos($nameOrFilename, '/') !== false) {
        $resolved = getImageOrPlaceholder($nameOrFilename, null);
        if ($resolved !== null) {
            return $resolved;
        }
    }

    $candidates = [
        $nameOrFilename,
        fastbiteSlugify(pathinfo($nameOrFilename, PATHINFO_FILENAME)) . '.jpg',
        fastbiteSlugify($nameOrFilename) . '.jpg'
    ];

    foreach ($candidates as $cand) {
        if (empty($cand)) continue;

        // Check in assets/images/foods/
        $assetsPath = 'assets/images/foods/' . $cand;
        $resolvedAssets = getImageOrPlaceholder($assetsPath, null);
        if ($resolvedAssets !== null) {
            return $resolvedAssets;
        }

        // Check in uploads/foods/
        $uploadsPath = 'uploads/foods/' . $cand;
        $resolvedUploads = getImageOrPlaceholder($uploadsPath, null);
        if ($resolvedUploads !== null) {
            return $resolvedUploads;
        }
    }

    return $placeholder;
}

/**
 * Resolves a category thumbnail image path.
 * Checks in `assets/images/categories/`.
 *
 * @param string $nameOrFilename File name, slug, or category title (e.g. 'pizza.jpg', 'Pizza')
 * @return string Safe relative path
 */
function getCategoryImage($nameOrFilename) {
    $placeholder = 'assets/images/placeholders/category-placeholder.jpg';
    if (empty($nameOrFilename)) {
        return $placeholder;
    }

    if (strpos($nameOrFilename, '/') !== false) {
        $resolved = getImageOrPlaceholder($nameOrFilename, null);
        if ($resolved !== null) {
            return $resolved;
        }
    }

    $candidates = [
        $nameOrFilename,
        fastbiteSlugify(pathinfo($nameOrFilename, PATHINFO_FILENAME)) . '.jpg',
        fastbiteSlugify($nameOrFilename) . '.jpg'
    ];

    foreach ($candidates as $cand) {
        if (empty($cand)) continue;
        $categoryPath = 'assets/images/categories/' . $cand;
        $resolved = getImageOrPlaceholder($categoryPath, null);
        if ($resolved !== null) {
            return $resolved;
        }
    }

    return $placeholder;
}

/**
 * Resolves a banner image path.
 * Checks in `assets/images/banners/`.
 *
 * @param string $nameOrFilename File name or slug (e.g. 'home-banner.jpg')
 * @return string Safe relative path
 */
function getBannerImage($nameOrFilename) {
    $placeholder = 'assets/images/placeholders/banner-placeholder.jpg';
    if (empty($nameOrFilename)) {
        return $placeholder;
    }

    if (strpos($nameOrFilename, '/') !== false) {
        $resolved = getImageOrPlaceholder($nameOrFilename, null);
        if ($resolved !== null) {
            return $resolved;
        }
    }

    $candidates = [
        $nameOrFilename,
        fastbiteSlugify(pathinfo($nameOrFilename, PATHINFO_FILENAME)) . '.jpg',
        fastbiteSlugify($nameOrFilename) . '.jpg'
    ];

    foreach ($candidates as $cand) {
        if (empty($cand)) continue;
        $bannerPath = 'assets/images/banners/' . $cand;
        $resolved = getImageOrPlaceholder($bannerPath, null);
        if ($resolved !== null) {
            return $resolved;
        }
    }

    return $placeholder;
}

/**
 * Resolves an about section image path.
 * Checks in `assets/images/banners/` and `assets/images/`.
 *
 * @param string $filename File name or relative path (e.g. 'about-banner.jpg')
 * @return string Safe relative path
 */
function getAboutImage($filename = 'about-banner.jpg') {
    $placeholder = 'assets/images/placeholders/about-placeholder.jpg';
    if (empty($filename)) {
        return $placeholder;
    }

    if (strpos($filename, '/') !== false) {
        $resolved = getImageOrPlaceholder($filename, null);
        if ($resolved !== null) {
            return $resolved;
        }
    }

    $bannerPath = 'assets/images/banners/' . $filename;
    $resolved = getImageOrPlaceholder($bannerPath, null);
    if ($resolved !== null) {
        return $resolved;
    }

    return $placeholder;
}

/**
 * Resolves a user avatar image path.
 * Checks in `assets/images/avatars/` and `assets/images/`.
 *
 * @param string $filename File name or relative path
 * @return string Safe relative path
 */
function getAvatarImage($filename = 'avatar-placeholder.jpg') {
    $placeholder = 'assets/images/placeholders/avatar-placeholder.jpg';
    if (empty($filename)) {
        return $placeholder;
    }

    if (strpos($filename, '/') !== false) {
        $resolved = getImageOrPlaceholder($filename, null);
        if ($resolved !== null) {
            return $resolved;
        }
    }

    $avatarPath = 'assets/images/avatars/' . $filename;
    $resolved = getImageOrPlaceholder($avatarPath, null);
    if ($resolved !== null) {
        return $resolved;
    }

    return $placeholder;
}
