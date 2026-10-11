# FastBite — Fast Food Restaurant Ordering & Management Platform

[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B%20%7C%208.0%2B-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap 5](https://img.shields.io/badge/Bootstrap-5.3.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Status](https://img.shields.io/badge/Status-Production%20Ready-success)]()

FastBite is a full-featured, modern Fast Food Restaurant Ordering, Kitchen Fulfillment, Courier Delivery, and Invoicing Management System. Built using pure PHP, MySQL PDO, JavaScript, and Bootstrap 5 with FastBite's signature Pink + White visual identity.

---

## Table of Contents
1. [Key Features](#key-features)
2. [System Requirements](#system-requirements)
3. [Quick Start & Installation](#quick-start--installation)
4. [Environment Configuration](#environment-configuration)
5. [Database Architecture & Automated Migration](#database-architecture--automated-migration)
6. [Platform Modules](#platform-modules)
   - [Customer Experience Portal](#1-customer-experience-portal)
   - [Admin Control Center](#2-admin-control-center)
   - [Courier / Driver Portal](#3-courier--driver-portal)
   - [Receipt & Thermal Invoicing System](#4-receipt--thermal-invoicing-system)
7. [Third-Party Integrations](#third-party-integrations)
   - [Google OAuth 2.0](#google-oauth-20)
   - [SMTP Email Dispatcher](#smtp-email-dispatcher)
   - [Payment Processing](#payment-processing)
8. [Production Security Model](#production-security-model)
9. [Demo Accounts](#demo-accounts)
10. [License](#license)

---

## Key Features

- **Full Lifecycle Ordering:** Real-time food menu filtering, dynamic cart calculation, promotional discount scheduler, coupon codes, and multi-step checkout.
- **Role-Based Access Control (RBAC):** Distinct permission boundaries for **Customers**, **Couriers / Drivers**, and **System Administrators**.
- **Receipt & Thermal Invoicing System:** Step 31 standard printable invoices and Step 34 authentic 80mm thermal POS receipts with automated email dispatching.
- **Live Courier & Dispatch Management:** Delivery order assignments, status updates (`assigned` → `picked_up` → `out_for_delivery` → `delivered`), transit time analytics, and driver performance rating.
- **Discounts & Priority Pricing Engine:** Item-specific and category-wide promotional percentage discounts with automated countdown timers.
- **Customer Support Desk:** Ticket creation, ticket threading, and customer account dashboard.

---

## System Requirements

- **PHP:** `7.4.x` or `8.0+` (Recommended: PHP 8.1 / 8.2)
- **Web Server:** Apache 2.4+ (with `mod_rewrite` enabled) or Nginx
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **PHP Extensions:**
  - `pdo_mysql`
  - `mbstring`
  - `openssl`
  - `gd` or `imagick` (for avatar and food image handling)
  - `json`
  - `session`

---

## Quick Start & Installation

### 1. Clone or Copy the Repository
Clone into your web root directory (e.g. `C:\xampp\htdocs\bink bite_fast food` or `/var/www/html/fastbite`):
```bash
git clone https://github.com/your-username/fastbite-restaurant.git .
```

### 2. Configure Environment
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```
Edit `.env` to match your local MySQL database settings:
```ini
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=fastbite_db
DB_USER=root
DB_PASS=
```

### 3. Database Auto-Initialization
FastBite features **Automated Zero-Config Schema Migration**. Simply navigate to the application URL in your web browser:
```
http://localhost/bink%20bite_fast%20food/
```
The database connection layer (`config/database.php`) automatically creates the `fastbite_db` database, executes table migrations, and seeds synthetic default demonstration categories, foods, and users.

---

## Environment Configuration

| Variable | Description | Default |
|---|---|---|
| `DB_HOST` | MySQL hostname / IP | `127.0.0.1` |
| `DB_PORT` | MySQL connection port | `3306` |
| `DB_NAME` | Database schema name | `fastbite_db` |
| `DB_USER` | MySQL database user | `root` |
| `DB_PASS` | MySQL database password | `""` |
| `APP_ENV` | Application environment (`local` / `production`) | `production` |
| `GOOGLE_CLIENT_ID` | Google OAuth Client ID | `""` |
| `GOOGLE_CLIENT_SECRET` | Google OAuth Client Secret | `""` |
| `SMTP_ENABLED` | Enable outbound transactional email | `false` |
| `SMTP_HOST` | Outbound SMTP relay host | `""` |
| `SMTP_PORT` | Outbound SMTP relay port | `587` |
| `SMTP_USERNAME` | SMTP authentication user | `""` |
| `SMTP_PASSWORD` | SMTP authentication password | `""` |

---

## Platform Modules

### 1. Customer Experience Portal
- **Menu Browsing & Search (`menu.php`):** Filter dishes by category, price range, popularity, and active discounts.
- **Cart Management (`cart.php`):** Client and server synchronized cart with quantity updates.
- **Checkout (`checkout.php`):** Delivery address selection, coupon application, and payment method choice.
- **Order Tracking (`my-orders.php`, `order-details.php`):** Live status updates with receipt viewing.
- **Customer Dashboard (`customer-dashboard.php`):** Overview of recent orders, saved favorites, and saved delivery addresses.

### 2. Admin Control Center (`admin/`)
- **Dashboard (`admin/index.php`):** Real-time revenue analytics, order volume KPIs, and popular dish rankings.
- **Menu & Foods Management (`admin/foods/`):** Full CRUD for dishes, image uploads, stock availability toggle, and category assignment.
- **Promotions & Discounts (`admin/discounts/`):** Percentage discount scheduler with auto-expiration.
- **Orders & Receipts (`admin/orders/`):** Order status management, invoice printing, thermal POS printing, and receipt re-sending.
- **Customer Directory & Statements (`admin/customers/`):** Customer transaction history, account statements, and receipts.
- **Courier Logistics (`admin/delivery/`):** Driver creation, assignment, and delivery tracking.

### 3. Courier / Driver Portal (`driver/`)
- **Driver Dashboard (`driver/index.php`):** Active delivery route, order pickup verification, and status toggle.
- **Duty Toggle:** Courier switch between `available`, `busy`, and `offline`.
- **Delivery Workflow:** Update orders from `picked_up` to `out_for_delivery` to `delivered`.

### 4. Receipt & Thermal Invoicing System (`receipt.php`)
- **Standard A4/Letter Invoices:** Full customer invoice with header, breakdown, delivery fee, and payment stamp.
- **Thermal POS Receipts (`receipt.php?type=thermal`):** 80mm monospace receipt layout designed for restaurant receipt printers.
- **Automated Delivery:** Receipt dispatch via SMTP with tracking in `orders.receipt_sent_at` and `orders.receipt_send_status`.

---

## Third-Party Integrations

### Google OAuth 2.0
To enable Google Sign-In:
1. Navigate to [Google Cloud Console](https://console.cloud.google.com/).
2. Create an OAuth 2.0 Client ID for Web Applications.
3. Add `http://localhost/bink%20bite_fast%20food/google-auth.php` as an Authorized Redirect URI.
4. Set `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in your `.env` file.

### SMTP Email Dispatcher
Outbound transactional emails (welcome notifications, order confirmations, receipt deliveries) use `includes/mailer.php`. Configure SMTP parameters in `.env` or via Admin Settings (`admin/settings/`). If SMTP is disabled or unconfigured, the system safely records delivery attempts without throwing exceptions.

---

## Production Security Model

FastBite is built following industry security standards:

1. **PDO Prepared Statements:** 100% of dynamic database queries use bound parameters to eliminate SQL Injection.
2. **Strict CSRF Protection:** All state-modifying POST requests require cryptographic CSRF verification (`verifyCsrfToken()`).
3. **Role-Based Access Enforcement:** Access checks (`requireAdmin()`, `requireDriver()`, `requireCustomer()`, `requireLogin()`) enforce authorization boundaries before processing actions.
4. **Session Hardening:** Cookies configured with `HttpOnly`, `SameSite=Lax`, and custom session lifetimes.
5. **Brute-Force Rate Limiting:** Login rate limiter blocks repeated failed authentication attempts.
6. **XSS Protection:** Contextual `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` escaping across all views.
7. **Security Headers:** HTTP headers (`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection`) sent on every request.

---

## Demo Accounts

The database seeds demonstration user accounts for testing:

| Role | Email | Password | Access Area |
|---|---|---|---|
| **Administrator** | `admin@gmail.com` | `admin123` | `/admin/` |
| **Courier / Driver** | `driver@fastbite.com` | `Driver@123456` | `/driver/` |
| **Customer** | `mohamed@example.com` | `Customer@123456` | Customer Portal |

> **Note for Production:** Change all default demonstration passwords immediately upon deploying to a live server.

---

## License

This project is licensed under the MIT License.
