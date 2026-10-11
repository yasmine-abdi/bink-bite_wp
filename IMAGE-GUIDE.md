# FastBite Image Management & Real Image Integration Guide

This guide explains how images are structured, resolved, and managed throughout the **FastBite** Fast Food website.

---

## 1. Directory Structure

All images in the project are organized under clear, dedicated directories:

```text
bink bite_fast food/
├── assets/
│   └── images/
│       ├── banners/                 <-- Hero & promotional banners
│       │   ├── home-banner.jpg
│       │   └── about-banner.jpg
│       ├── categories/              <-- Category thumbnails
│       │   ├── pizza.jpg
│       │   ├── burgers.jpg
│       │   ├── shawarma.jpg
│       │   ├── drinks.jpg
│       │   ├── coffee.jpg
│       │   ├── chicken.jpg
│       │   ├── pasta.jpg
│       │   ├── sandwiches.jpg
│       │   ├── fries.jpg
│       │   └── desserts.jpg
│       ├── foods/                   <-- Food item real images
│       │   ├── chicken-supreme-pizza.jpg
│       │   ├── classic-beef-burger.jpg
│       │   ├── double-beef-burger.jpg
│       │   ├── classic-pepperoni-pizza.jpg
│       │   ├── chicken-shawarma.jpg
│       │   ├── crispy-chicken-bucket.jpg
│       │   ├── fresh-mango-juice.jpg
│       │   ├── fresh-strawberry-juice.jpg
│       │   ├── cappuccino.jpg
│       │   ├── iced-coffee.jpg
│       │   ├── creamy-chicken-penne.jpg
│       │   └── loaded-cheese-fries.jpg
│       ├── avatars/                 <-- Customer profile pictures
│       │   └── avatar-placeholder.jpg
│       └── placeholders/            <-- Zero-breakage fallbacks
│           ├── about-placeholder.jpg
│           ├── avatar-placeholder.jpg
│           ├── banner-placeholder.jpg
│           ├── category-placeholder.jpg
│           ├── default-placeholder.jpg
│           └── food-placeholder.jpg
└── uploads/
    └── foods/                       <-- Customer/admin uploaded food images
```

---

## 2. Central Image Helper (`includes/image-helper.php`)

All PHP templates load images via helper functions that dynamically check if a real image exists on disk:
- If a real file is found, it is automatically served.
- If the real file is absent, it seamlessly falls back to the appropriate placeholder without any broken image icons or PHP errors.
- Checks multiple extensions automatically: `.jpg`, `.jpeg`, `.png`, and `.webp`.
- Automatically slugifies food and category names (e.g., `'Chicken Supreme Pizza'` -> `'chicken-supreme-pizza.jpg'`).

### Helper Functions:

| Function | Destination Folder | Fallback Placeholder |
| :--- | :--- | :--- |
| `getFoodImage($nameOrFile)` | `assets/images/foods/` or `uploads/foods/` | `assets/images/placeholders/food-placeholder.jpg` |
| `getCategoryImage($nameOrFile)` | `assets/images/categories/` | `assets/images/placeholders/category-placeholder.jpg` |
| `getBannerImage($nameOrFile)` | `assets/images/banners/` | `assets/images/placeholders/banner-placeholder.jpg` |
| `getAboutImage($nameOrFile)` | `assets/images/banners/` or `assets/images/` | `assets/images/placeholders/about-placeholder.jpg` |
| `getAvatarImage($nameOrFile)` | `assets/images/avatars/` or `assets/images/` | `assets/images/placeholders/avatar-placeholder.jpg` |
| `getImageOrPlaceholder($path, $fallback)` | Relative path test | Custom or default placeholder |

---

## 3. How to Add New Real Images

### A. How to Add a Food Image
1. Prepare your food image (recommended format: `.jpg`, `.png`, or `.webp`, recommended dimensions: `600x450` or `800x600`).
2. Save it directly in:
   ```text
   assets/images/foods/chicken-supreme-pizza.jpg
   ```
   *(or `assets/images/foods/classic-beef-burger.jpg`, `assets/images/foods/double-beef-burger.jpg`, etc.)*
3. Refresh the page in your browser. The system will immediately detect the file and show your real photo instead of the placeholder!

### B. How to Add a Category Image
1. Prepare your category thumbnail (recommended dimensions: `300x300` or `400x400`).
2. Save it in:
   ```text
   assets/images/categories/pizza.jpg
   ```
   *(or `burgers.jpg`, `shawarma.jpg`, `drinks.jpg`, `coffee.jpg`, `chicken.jpg`, `pasta.jpg`, `sandwiches.jpg`, `fries.jpg`, `desserts.jpg`)*
3. Refresh the website.

### C. How to Add Banners
1. Prepare your hero banner image (recommended dimensions: `1200x800` or `800x600`).
2. Save it in:
   ```text
   assets/images/banners/home-banner.jpg
   assets/images/banners/about-banner.jpg
   ```
3. Refresh the website.

---

## 4. Frontend Resilience & Performance

1. **Global JavaScript Fallback (`assets/js/main.js`)**:
   An event listener captures any client-side image load failures and swaps broken URLs with the right placeholder, preventing broken image icons or endless retry loops.
2. **Performance Optimizations**:
   - `loading="lazy"` on below-the-fold food grids and cards.
   - `loading="eager"` on hero banners for rapid Largest Contentful Paint (LCP).
   - `decoding="async"` across images for smoother rendering.
3. **Accessibility**:
   - Descriptive `alt` tags preserved on all food cards, category tiles, and modal views.
