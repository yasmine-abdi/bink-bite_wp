<?php
/**
 * FastBite - Central Email & Communication Helper Module
 * STEP 28: FastBite Notifications & Communication Center
 * 
 * Provides safe email dispatching, SMTP provider abstraction,
 * template generation, and graceful fallback when email is unconfigured.
 */

if (!defined('FASTBITE_ROOT_DIR')) {
    define('FASTBITE_ROOT_DIR', dirname(__DIR__));
}

require_once FASTBITE_ROOT_DIR . '/config/database.php';

/**
 * Dispatches an email using configured SMTP/mail provider or reports not-configured safely.
 *
 * @param string $toEmail Recipient email address
 * @param string $toName Recipient full name
 * @param string $subject Email subject line
 * @param string $htmlBody HTML email content
 * @param string $plainText Optional plain text alternative
 * @return array ['success' => bool, 'sent' => bool, 'reason' => string]
 */
function sendFastBiteEmail($toEmail, $toName = '', $subject = '', $htmlBody = '', $plainText = '') {
    // Polymorphic handling: If called as sendFastBiteEmail($toEmail, $subject, $htmlBody)
    if (func_num_args() === 3 || (empty($htmlBody) && !empty($subject) && !empty($toName))) {
        if (strpos($toName, '<') !== false || strpos($subject, '<') !== false || strlen($subject) > 50) {
            $actualSubject = $toName;
            $actualBody = $subject;
            $toName = '';
            $subject = $actualSubject;
            $htmlBody = $actualBody;
        }
    }

    $toEmail = trim($toEmail);
    $toName = trim($toName);
    $subject = trim($subject);

    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'sent' => false,
            'reason' => 'Invalid recipient email address.'
        ];
    }

    // Check system settings for SMTP configuration
    $smtpEnabled = function_exists('dbGetSetting') ? (bool)dbGetSetting('smtp_enabled', '0') : false;
    $smtpHost = function_exists('dbGetSetting') ? trim((string)dbGetSetting('smtp_host', '')) : '';
    $fromEmail = function_exists('dbGetSetting') ? trim((string)dbGetSetting('mail_from_address', 'support@fastbite.com')) : 'support@fastbite.com';
    $fromName = function_exists('dbGetSetting') ? trim((string)dbGetSetting('mail_from_name', 'FastBite Fast Food')) : 'FastBite Fast Food';

    // If SMTP is not actively configured or enabled
    if (!$smtpEnabled || empty($smtpHost)) {
        return [
            'success' => false,
            'sent' => false,
            'reason' => 'Email delivery service is not configured (SMTP settings not active). In-app notification delivered.'
        ];
    }

    // If SMTP is enabled and configured, attempt sending via standard mail() or socket transport
    try {
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "From: " . sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($fromName), $fromEmail);
        $headers[] = "Reply-To: " . $fromEmail;
        $headers[] = "X-Mailer: FastBiteMailer/1.0";

        $mailSent = @mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers));
        if ($mailSent) {
            return [
                'success' => true,
                'sent' => true,
                'reason' => 'Email dispatched successfully.'
            ];
        } else {
            return [
                'success' => false,
                'sent' => false,
                'reason' => 'Mail delivery transport returned failure.'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'sent' => false,
            'reason' => 'Mail transport exception: ' . $e->getMessage()
        ];
    }
}

/**
 * Returns a FastBite Pink & White branded HTML email template.
 *
 * @param string $title Header headline
 * @param string $message Main message body
 * @param string|null $actionText Optional call to action button label
 * @param string|null $actionUrl Optional call to action button URL
 * @return string Full HTML document
 */
function getFastBiteEmailTemplate($title, $message, $actionText = null, $actionUrl = null) {
    $restaurantName = function_exists('dbGetSetting') ? dbGetSetting('restaurant_name', 'FastBite Fast Food') : 'FastBite Fast Food';
    $restaurantPhone = function_exists('dbGetSetting') ? dbGetSetting('restaurant_phone', '+252 63 444 5555') : '+252 63 444 5555';
    
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    
    $buttonHtml = '';
    if (!empty($actionText) && !empty($actionUrl)) {
        $safeActionText = htmlspecialchars($actionText, ENT_QUOTES, 'UTF-8');
        $safeActionUrl = htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8');
        $buttonHtml = <<<HTML
        <table border="0" cellpadding="0" cellspacing="0" style="margin: 28px 0 10px 0;">
            <tr>
                <td align="center" style="background-color: #ff2b85; border-radius: 50px;">
                    <a href="{$safeActionUrl}" target="_blank" style="display: inline-block; padding: 12px 28px; font-family: 'Plus Jakarta Sans', Arial, sans-serif; font-size: 14px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 50px;">
                        {$safeActionText} &rarr;
                    </a>
                </td>
            </tr>
        </table>
HTML;
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$safeTitle} - FastBite</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, Arial, sans-serif; color: #212529;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8f9fa; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #f0f0f0;">
                    <!-- Brand Header -->
                    <tr>
                        <td align="center" style="background-color: #fff0f6; padding: 25px 20px; border-bottom: 2px solid #ffdeeb;">
                            <span style="font-size: 24px; font-weight: 800; color: #1a1a1a; letter-spacing: -0.5px;">
                                Fast<span style="color: #ff2b85;">Bite</span>
                            </span>
                        </td>
                    </tr>

                    <!-- Email Body -->
                    <tr>
                        <td style="padding: 35px 30px;">
                            <h1 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #1a1a1a; line-height: 1.3;">
                                {$safeTitle}
                            </h1>
                            <div style="font-size: 15px; line-height: 1.6; color: #495057;">
                                {$safeMessage}
                            </div>
                            {$buttonHtml}
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #fafafa; padding: 20px 30px; border-top: 1px solid #f0f0f0; text-align: center; font-size: 12px; color: #868e96; line-height: 1.5;">
                            <p style="margin: 0 0 4px 0; font-weight: 600; color: #495057;">{$restaurantName}</p>
                            <p style="margin: 0 0 4px 0;">Customer Support: {$restaurantPhone}</p>
                            <p style="margin: 0;">&copy; 2026 FastBite. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
 * Returns dynamic base URL for FastBite links.
 * 
 * @return string
 */
function getFastBiteBaseUrl() {
    if (!empty($_SERVER['HTTP_HOST'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base = rtrim($protocol . $_SERVER['HTTP_HOST'] . $scriptDir, '/\\');
        // Clean any subfolder paths (admin, driver, etc.)
        $base = preg_replace('/(\/admin(\/orders|\/foods|\/categories|\/coupons|\/delivery|\/reports|\/support)?|\/driver)$/', '', $base);
        return $base;
    }
    return 'http://localhost/bink%20bite_fast%20food';
}

/**
 * Generates an official FastBite Pink & White branded HTML receipt email.
 *
 * @param array $order Order record with items and customer data
 * @param array|null $customer Optional customer record
 * @return string Full HTML document
 */
function getFastBiteReceiptEmailHtml($order, $customer = null) {
    $restName = function_exists('dbGetSetting') ? dbGetSetting('restaurant_name', 'FastBite Fast Food') : 'FastBite Fast Food';
    $restPhone = function_exists('dbGetSetting') ? dbGetSetting('restaurant_phone', '+252 63 444 5555') : '+252 63 444 5555';
    $restEmail = function_exists('dbGetSetting') ? dbGetSetting('restaurant_email', 'support@fastbite.com') : 'support@fastbite.com';
    $restAddress = function_exists('dbGetSetting') ? dbGetSetting('restaurant_address', 'Main Hospitality Blvd, City Center, Hargeisa') : 'Main Hospitality Blvd, City Center, Hargeisa';

    $orderNumber = htmlspecialchars($order['order_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
    $customerName = htmlspecialchars($customer['name'] ?? $order['customer_name'] ?? 'Valued Customer', ENT_QUOTES, 'UTF-8');
    $orderDate = !empty($order['created_at']) ? date('F j, Y  h:i A', strtotime($order['created_at'])) : date('F j, Y  h:i A');
    $payMethod = function_exists('getPaymentMethodLabel') ? getPaymentMethodLabel($order['payment_method'] ?? 'cash_on_delivery') : ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'Cash on Delivery'));
    $payStatus = strtoupper($order['payment_status'] ?? 'PENDING');
    $deliveryAddress = htmlspecialchars($order['delivery_address'] ?? 'Pickup / On-site', ENT_QUOTES, 'UTF-8');

    $baseUrl = getFastBiteBaseUrl();
    $receiptUrl = $baseUrl . '/receipt.php?order=' . urlencode($order['order_number'] ?? '');

    // Build Itemized Rows
    $itemsHtml = '';
    $items = $order['items'] ?? [];
    if (!empty($items)) {
        foreach ($items as $item) {
            $foodName = htmlspecialchars($item['food_name'] ?? 'Food Item', ENT_QUOTES, 'UTF-8');
            $category = htmlspecialchars($item['food_category'] ?? '', ENT_QUOTES, 'UTF-8');
            $qty = (int)($item['quantity'] ?? 1);
            $unitPrice = number_format((float)($item['unit_price'] ?? 0), 2);
            $discount = (float)($item['discount'] ?? 0);
            $subtotal = number_format((float)($item['subtotal'] ?? 0), 2);

            $discountBadge = ($discount > 0) ? "<span style='color: #28a745; font-size: 11px; display: block;'>-$" . number_format($discount, 2) . " discount</span>" : "";
            $catBadge = !empty($category) ? "<span style='color: #888; font-size: 11px; display: block;'>{$category}</span>" : "";

            $itemsHtml .= <<<HTML
            <tr>
                <td style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; vertical-align: top;">
                    <strong style="color: #212529; font-size: 14px;">{$foodName}</strong>
                    {$catBadge}
                    {$discountBadge}
                </td>
                <td style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; text-align: center; color: #495057; font-size: 13px; vertical-align: top;">
                    &times; {$qty}
                </td>
                <td style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; text-align: right; color: #495057; font-size: 13px; vertical-align: top;">
                    \${$unitPrice}
                </td>
                <td style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; text-align: right; font-weight: bold; color: #212529; font-size: 14px; vertical-align: top;">
                    \${$subtotal}
                </td>
            </tr>
HTML;
        }
    } else {
        $itemsHtml = '<tr><td colspan="4" style="padding: 15px; text-align: center; color: #888;">No itemized records available.</td></tr>';
    }

    // Financial Rows
    $subtotalVal = number_format((float)($order['subtotal'] ?? 0), 2);
    $itemDiscountVal = (float)($order['discount'] ?? 0);
    $couponDiscountVal = (float)($order['coupon_discount'] ?? 0);
    $couponCode = htmlspecialchars($order['coupon_code'] ?? 'PROMO', ENT_QUOTES, 'UTF-8');
    $deliveryFeeVal = (float)($order['delivery_fee'] ?? 0);
    $taxVal = (float)($order['tax'] ?? 0);
    $totalVal = number_format((float)($order['total'] ?? 0), 2);

    $extraRows = '';
    if ($itemDiscountVal > 0) {
        $extraRows .= '<tr><td style="padding: 4px 0; color: #28a745; font-size: 13px;">Menu Discounts:</td><td style="padding: 4px 0; text-align: right; color: #28a745; font-size: 13px; font-weight: bold;">-$' . number_format($itemDiscountVal, 2) . '</td></tr>';
    }
    if ($couponDiscountVal > 0) {
        $extraRows .= '<tr><td style="padding: 4px 0; color: #ff2b85; font-size: 13px; font-weight: bold;">Coupon (' . $couponCode . '):</td><td style="padding: 4px 0; text-align: right; color: #ff2b85; font-size: 13px; font-weight: bold;">-$' . number_format($couponDiscountVal, 2) . '</td></tr>';
    }

    $deliveryDisplay = ($deliveryFeeVal > 0) ? '$' . number_format($deliveryFeeVal, 2) : '<span style="color: #28a745; font-weight: bold;">FREE</span>';
    $taxRow = ($taxVal > 0) ? '<tr><td style="padding: 4px 0; color: #6c757d; font-size: 13px;">Tax:</td><td style="padding: 4px 0; text-align: right; color: #212529; font-size: 13px;">$' . number_format($taxVal, 2) . '</td></tr>' : '';

    $notesHtml = '';
    if (!empty($order['notes'])) {
        $safeNotes = htmlspecialchars($order['notes'], ENT_QUOTES, 'UTF-8');
        $notesHtml = <<<HTML
        <div style="background-color: #f8f9fa; border-left: 3px solid #ff2b85; padding: 10px 14px; margin: 20px 0; border-radius: 4px; font-size: 12px; color: #495057;">
            <strong style="color: #1a1a1a;">Customer Notes:</strong> {$safeNotes}
        </div>
HTML;
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Receipt #{$orderNumber} - FastBite</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, Arial, sans-serif; color: #212529;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f6f8; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #eaeaea;">
                    <!-- Brand Banner -->
                    <tr>
                        <td style="background-color: #fff0f6; padding: 25px 30px; border-bottom: 2px solid #ffdeeb;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td>
                                        <span style="font-size: 24px; font-weight: 800; color: #1a1a1a; letter-spacing: -0.5px;">
                                            Fast<span style="color: #ff2b85;">Bite</span>
                                        </span>
                                        <span style="display: block; font-size: 11px; color: #ff2b85; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px;">
                                            Official Order Receipt
                                        </span>
                                    </td>
                                    <td align="right">
                                        <span style="display: inline-block; background-color: #ffffff; color: #ff2b85; border: 1px solid #ffdeeb; font-weight: 800; font-size: 13px; padding: 6px 14px; border-radius: 50px;">
                                            #{$orderNumber}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Receipt Header Details -->
                    <tr>
                        <td style="padding: 25px 30px 15px 30px;">
                            <h2 style="margin: 0 0 6px 0; font-size: 18px; font-weight: 700; color: #1a1a1a;">
                                Thank you for your order, {$customerName}!
                            </h2>
                            <p style="margin: 0 0 15px 0; font-size: 13px; color: #6c757d;">
                                Here is the complete breakdown and payment receipt for your recent order.
                            </p>

                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fafbfc; border-radius: 10px; padding: 12px 16px; border: 1px solid #ededed; font-size: 12px; margin-bottom: 20px;">
                                <tr>
                                    <td style="padding: 4px 0; color: #6c757d; width: 50%;">
                                        <strong style="color: #212529;">Order Date:</strong> {$orderDate}
                                    </td>
                                    <td style="padding: 4px 0; color: #6c757d; text-align: right; width: 50%;">
                                        <strong style="color: #212529;">Payment Method:</strong> {$payMethod}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 0; color: #6c757d;">
                                        <strong style="color: #212529;">Payment Status:</strong> <span style="font-weight: bold; color: #212529;">{$payStatus}</span>
                                    </td>
                                    <td style="padding: 4px 0; color: #6c757d; text-align: right;">
                                        <strong style="color: #212529;">Delivery:</strong> {$deliveryAddress}
                                    </td>
                                </tr>
                            </table>

                            <!-- Items Table -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 15px;">
                                <thead>
                                    <tr style="background-color: #f8f9fa; border-bottom: 2px solid #eaeaea;">
                                        <th style="padding: 10px; text-align: left; font-size: 11px; text-transform: uppercase; color: #6c757d; font-weight: 700;">Item</th>
                                        <th style="padding: 10px; text-align: center; font-size: 11px; text-transform: uppercase; color: #6c757d; font-weight: 700;">Qty</th>
                                        <th style="padding: 10px; text-align: right; font-size: 11px; text-transform: uppercase; color: #6c757d; font-weight: 700;">Price</th>
                                        <th style="padding: 10px; text-align: right; font-size: 11px; text-transform: uppercase; color: #6c757d; font-weight: 700;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$itemsHtml}
                                </tbody>
                            </table>

                            <!-- Financial Summary -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
                                <tr>
                                    <td width="45%"></td>
                                    <td width="55%">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td style="padding: 4px 0; color: #6c757d; font-size: 13px;">Subtotal:</td>
                                                <td style="padding: 4px 0; text-align: right; color: #212529; font-size: 13px; font-weight: 600;">\${$subtotalVal}</td>
                                            </tr>
                                            {$extraRows}
                                            <tr>
                                                <td style="padding: 4px 0; color: #6c757d; font-size: 13px;">Delivery Fee:</td>
                                                <td style="padding: 4px 0; text-align: right; color: #212529; font-size: 13px;">{$deliveryDisplay}</td>
                                            </tr>
                                            {$taxRow}
                                            <tr>
                                                <td style="padding: 10px 0 4px 0; border-top: 2px solid #212529; font-size: 15px; font-weight: 800; color: #1a1a1a;">GRAND TOTAL:</td>
                                                <td style="padding: 10px 0 4px 0; border-top: 2px solid #212529; text-align: right; font-size: 18px; font-weight: 800; color: #ff2b85;">\${$totalVal}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {$notesHtml}

                            <!-- View Receipt CTA Button -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 25px 0 10px 0;">
                                <tr>
                                    <td align="center">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="background-color: #ff2b85; border-radius: 50px;">
                                                    <a href="{$receiptUrl}" target="_blank" style="display: inline-block; padding: 13px 32px; font-family: 'Plus Jakarta Sans', Arial, sans-serif; font-size: 14px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 50px;">
                                                        View Official Printable Receipt &rarr;
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #fafafa; padding: 20px 30px; border-top: 1px solid #f0f0f0; text-align: center; font-size: 12px; color: #868e96; line-height: 1.5;">
                            <p style="margin: 0 0 4px 0; font-weight: 600; color: #495057;">{$restName}</p>
                            <p style="margin: 0 0 4px 0;">{$restAddress} &bull; Tel: {$restPhone} &bull; Email: {$restEmail}</p>
                            <p style="margin: 0;">&copy; 2026 FastBite Fast Food. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
 * Dispatches an official order receipt email to the customer.
 * 
 * @param array $order
 * @param array|null $customer
 * @return array ['success' => bool, 'sent' => bool, 'reason' => string]
 */
function sendFastBiteOrderReceiptEmail($order, $customer = null) {
    $toEmail = trim($order['customer_email'] ?? $customer['email'] ?? '');
    $toName = trim($order['customer_name'] ?? $customer['name'] ?? 'Valued Customer');
    $orderNumber = trim($order['order_number'] ?? '');

    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'sent' => false,
            'reason' => 'Recipient email address is invalid or missing.'
        ];
    }

    $subject = "Official Receipt for FastBite Order #{$orderNumber}";
    $htmlBody = getFastBiteReceiptEmailHtml($order, $customer);

    return sendFastBiteEmail($toEmail, $toName, $subject, $htmlBody);
}

