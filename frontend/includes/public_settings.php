<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth_bootstrap.php';

/**
 * @return array{website: array<string, mixed>, company: array<string, mixed>, system: array<string, mixed>}
 */
function afrisense_public_settings(): array
{
    static $settings = null;

    if (is_array($settings)) {
        return $settings;
    }

    $settings = [
        'website' => [
            'site_name' => 'AfriSense Food Services',
            'site_tagline' => 'Delicious meals, delivered with love.',
            'logo' => '',
            'favicon' => '',
            'hero_image' => '',
            'primary_color' => '#b77b1a',
            'secondary_color' => '#cc8f25',
            'hero_title' => 'Exceptional Food Memorable Moments',
            'hero_subtitle' => 'We provide delicious meals and professional catering services for all occasions.',
            'footer_text' => '(c) 2026 AfriSense Food Services. All rights reserved.',
        ],
        'company' => [
            'company_name' => 'AfriSense Food Services',
            'company_email' => 'info@afrisense.com',
            'support_email' => 'support@afrisense.com',
            'phone_number_1' => '+233 24 123 4567',
            'phone_number_2' => '+233 20 987 6543',
            'address' => '15 Senchi Street, Airport Residential Area, Accra, Ghana',
            'business_hours' => 'Mon - Sun: 8:00 AM - 10:00 PM',
            'google_map_iframe' => '',
            'facebook_url' => 'https://www.facebook.com/',
            'instagram_url' => 'https://www.instagram.com/',
            'twitter_url' => 'https://twitter.com/',
            'linkedin_url' => '',
            'youtube_url' => '',
            'tiktok_url' => '',
        ],
        'system' => [
            'site_status' => 'Online',
            'default_currency' => 'GHS',
            'timezone' => 'Africa/Accra',
            'email_notifications' => 1,
            'booking_notifications' => 1,
            'order_notifications' => 1,
            'payment_gateway' => 'Paystack',
            'paystack_enabled' => 1,
            'mtn_momo_enabled' => 1,
            'vodafone_cash_enabled' => 0,
            'flutterwave_enabled' => 1,
            'mobile_money_enabled' => 1,
            'card_payment_enabled' => 1,
            'cash_payment_enabled' => 1,
            'guest_checkout_enabled' => 1,
            'auto_confirm_paid_orders' => 1,
            'payment_test_mode' => 0,
            'payment_public_key' => '',
            'payment_secret_key' => '',
            'payment_webhook_secret' => '',
            'payment_instruction' => 'You can make payments securely using any of the available payment methods. Your payment is protected with 256-bit SSL encryption.',
            'refund_policy' => 'Allow refund within 7 days',
            'cancellation_policy' => 'Allow cancellation before delivery',
            'refund_process_message' => 'Refunds are processed within 3-5 working days to your original payment method.',
            'delivery_fee' => '10.00',
            'service_fee' => '5.00',
            'free_delivery_over' => '275.00',
            'delivery_zones' => '',
            'default_delivery_time' => '30 - 45 minutes',
            'maximum_delivery_time' => '90 minutes',
            'order_cutoff_time' => '22:00',
            'same_day_delivery' => 1,
            'weekend_delivery' => 1,
            'real_time_tracking' => 1,
            'standard_delivery_enabled' => 1,
            'express_delivery_enabled' => 1,
            'scheduled_delivery_enabled' => 1,
            'pickup_enabled' => 1,
            'delivery_instructions' => 'Please ensure someone is available to receive the order at the delivery address. We will contact you when we are on our way.',
        ],
    ];

    try {
        $pdo = afrisense_pdo();

        foreach (['website' => 'website_settings', 'company' => 'company_information', 'system' => 'system_settings'] as $key => $table) {
            $statement = $pdo->prepare(sprintf('SELECT * FROM `%s` ORDER BY `id` ASC LIMIT 1', $table));
            $statement->execute();
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if (is_array($row)) {
                $settings[$key] = array_replace($settings[$key], array_filter(
                    $row,
                    static fn (mixed $value): bool => $value !== null && $value !== ''
                ));
            }
        }
    } catch (Throwable $exception) {
        return $settings;
    }

    return $settings;
}

function afrisense_public_setting(string $group, string $key, string $fallback = ''): string
{
    $settings = afrisense_public_settings();

    return (string) ($settings[$group][$key] ?? $fallback);
}

function afrisense_public_upload_url(string $frontendBase, ?string $path): string
{
    $path = trim((string) $path);

    if ($path === '') {
        return '';
    }

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }

    $relativePath = ltrim(str_replace('\\', '/', $path), '/');

    if ($relativePath !== '' && is_file(__DIR__ . '/../uploads/' . $relativePath)) {
        return rtrim($frontendBase, '/') . '/uploads/' . $relativePath;
    }

    return '';
}

function afrisense_public_logo_url(string $frontendBase): string
{
    return afrisense_public_upload_url($frontendBase, afrisense_public_setting('website', 'logo'));
}

function afrisense_public_favicon_url(string $frontendBase): string
{
    return afrisense_public_upload_url($frontendBase, afrisense_public_setting('website', 'favicon'));
}

function afrisense_public_brand_icon_html(string $frontendBase, string $fallbackIcon = 'bi-cup-hot'): string
{
    $logoUrl = afrisense_public_logo_url($frontendBase);

    if ($logoUrl !== '') {
        return '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="">';
    }

    return '<i class="bi ' . htmlspecialchars($fallbackIcon, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i>';
}

function afrisense_public_safe_map_embed(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $value, $matches) === 1) {
        $value = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }

    if (!filter_var($value, FILTER_VALIDATE_URL)) {
        return '';
    }

    $host = strtolower((string) parse_url($value, PHP_URL_HOST));
    $allowedHosts = ['google.com', 'www.google.com', 'maps.google.com', 'maps.app.goo.gl'];

    if (!in_array($host, $allowedHosts, true) && !str_ends_with($host, '.google.com')) {
        return '';
    }

    return sprintf(
        '<iframe src="%s" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>',
        htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
    );
}

function afrisense_public_site_in_maintenance(): bool
{
    return strtolower(afrisense_public_setting('system', 'site_status', 'Online')) === 'maintenance';
}

function afrisense_enforce_public_site_status(string $frontendBase = '/Afrisense/frontend'): void
{
    if (!afrisense_public_site_in_maintenance()) {
        return;
    }

    if (!headers_sent()) {
        http_response_code(503);
        header('Retry-After: 3600');
    }

    $settings = afrisense_public_settings();
    $siteName = (string) ($settings['website']['site_name'] ?? 'AfriSense Food Services');
    $siteTagline = (string) ($settings['website']['site_tagline'] ?? 'We will be back shortly.');
    $primaryColor = htmlspecialchars((string) ($settings['website']['primary_color'] ?? '#b77b1a'), ENT_QUOTES, 'UTF-8');
    $logoUrl = afrisense_public_logo_url($frontendBase);
    $faviconUrl = afrisense_public_favicon_url($frontendBase);

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<meta name="robots" content="noindex"><meta name="theme-color" content="' . $primaryColor . '">';
    if ($faviconUrl !== '') {
        echo '<link rel="icon" href="' . htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8') . '">';
    }
    echo '<title>Maintenance | ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '</title>';
    echo '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:Arial,sans-serif;background:#f7f5ef;color:#17231d}.af-maintenance{width:min(560px,calc(100% - 32px));text-align:center}.af-maintenance-logo{display:inline-grid;place-items:center;width:84px;height:84px;margin-bottom:18px;border-radius:8px;background:' . $primaryColor . ';color:#fff;font-size:38px;font-weight:800}.af-maintenance-logo img{max-width:72px;max-height:72px;object-fit:contain}.af-maintenance h1{margin:0 0 10px;font-size:clamp(32px,6vw,52px);line-height:1}.af-maintenance p{margin:0;color:#506157;font-size:17px;line-height:1.6}</style>';
    echo '</head><body><main class="af-maintenance">';
    echo '<span class="af-maintenance-logo">';
    echo $logoUrl !== '' ? '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="">' : htmlspecialchars(substr($siteName, 0, 1), ENT_QUOTES, 'UTF-8');
    echo '</span><h1>We will be back shortly.</h1><p>' . htmlspecialchars($siteTagline, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</main></body></html>';
    exit;
}

function afrisense_public_currency(): string
{
    return match (afrisense_public_setting('system', 'default_currency', 'GHS')) {
        'USD' => 'USD',
        default => 'GHC',
    };
}

function afrisense_public_money(float $amount): string
{
    return afrisense_public_currency() . ' ' . number_format($amount, 2);
}

function afrisense_public_setting_bool(string $key, bool $fallback = false): bool
{
    $value = afrisense_public_settings()['system'][$key] ?? $fallback;

    return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
}

function afrisense_public_setting_float(string $key, float $fallback): float
{
    $value = afrisense_public_settings()['system'][$key] ?? $fallback;

    return is_numeric($value) ? (float) $value : $fallback;
}

function afrisense_public_delivery_zones(): array
{
    $zones = json_decode(afrisense_public_setting('system', 'delivery_zones'), true);

    if (!is_array($zones)) {
        return [];
    }

    return array_values(array_filter($zones, static fn (mixed $zone): bool => is_array($zone)));
}

function afrisense_public_delivery_fee(float $subtotal = 0.00, string $address = ''): float
{
    $freeDeliveryOver = afrisense_public_setting_float('free_delivery_over', 275.00);

    if ($freeDeliveryOver > 0 && $subtotal >= $freeDeliveryOver) {
        return 0.00;
    }

    $normalizedAddress = strtolower($address);

    if ($normalizedAddress !== '') {
        foreach (afrisense_public_delivery_zones() as $zone) {
            if ((string) ($zone['status'] ?? 'Active') !== 'Active') {
                continue;
            }

            $areas = array_filter(array_map('trim', explode(',', strtolower((string) ($zone['areas'] ?? '')))));

            foreach ($areas as $area) {
                if ($area !== '' && str_contains($normalizedAddress, $area)) {
                    return max(0.00, (float) ($zone['fee'] ?? 0.00));
                }
            }
        }
    }

    return max(0.00, afrisense_public_setting_float('delivery_fee', 10.00));
}

function afrisense_public_service_fee(): float
{
    return max(0.00, afrisense_public_setting_float('service_fee', 5.00));
}

function afrisense_public_free_delivery_over(): float
{
    return max(0.00, afrisense_public_setting_float('free_delivery_over', 275.00));
}

function afrisense_public_delivery_time(): string
{
    return afrisense_public_setting('system', 'default_delivery_time', '30 - 45 minutes');
}

function afrisense_public_delivery_instructions(): string
{
    return afrisense_public_setting('system', 'delivery_instructions', 'Please ensure someone is available to receive the order at the delivery address. We will contact you when we are on our way.');
}

function afrisense_public_delivery_block_reason(): string
{
    if (!afrisense_public_setting_bool('standard_delivery_enabled', true)) {
        return 'Delivery ordering is currently unavailable.';
    }

    if (!afrisense_public_setting_bool('same_day_delivery', true)) {
        return 'Same-day delivery is currently unavailable. Please contact AfriSense before placing an order.';
    }

    $timezone = afrisense_public_setting('system', 'timezone', 'Africa/Accra');

    try {
        $now = new DateTimeImmutable('now', new DateTimeZone($timezone));
    } catch (Throwable $exception) {
        $now = new DateTimeImmutable('now', new DateTimeZone('Africa/Accra'));
    }

    if (!afrisense_public_setting_bool('weekend_delivery', true) && in_array($now->format('N'), ['6', '7'], true)) {
        return 'Weekend delivery is currently unavailable.';
    }

    $cutoff = trim(afrisense_public_setting('system', 'order_cutoff_time', '22:00'));
    if (preg_match('/^\d{2}:\d{2}$/', $cutoff) === 1) {
        $cutoffTime = $now->setTime((int) substr($cutoff, 0, 2), (int) substr($cutoff, 3, 2));

        if ($now > $cutoffTime) {
            return 'Online orders are closed for today. Please order again during business hours.';
        }
    }

    return '';
}

function afrisense_public_delivery_available(): bool
{
    return afrisense_public_delivery_block_reason() === '';
}

function afrisense_enforce_public_delivery_available(): void
{
    $reason = afrisense_public_delivery_block_reason();

    if ($reason === '') {
        return;
    }

    if (!headers_sent()) {
        http_response_code(503);
    }

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Ordering Unavailable | AfriSense</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:Arial,sans-serif;background:#f7f5ef;color:#17231d}.box{width:min(560px,calc(100% - 32px));text-align:center}.box h1{margin:0 0 10px;font-size:clamp(30px,6vw,48px)}.box p{margin:0 0 20px;color:#506157;line-height:1.6}.box a{display:inline-flex;align-items:center;min-height:44px;padding:0 18px;border-radius:8px;background:#17231d;color:#fff;text-decoration:none;font-weight:800}</style></head><body><main class="box"><h1>Ordering is unavailable.</h1><p>' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</p><a href="/Afrisense/frontend/landing/contact.php">Contact Us</a></main></body></html>';
    exit;
}

/**
 * @return list<string>
 */
function afrisense_public_payment_methods(): array
{
    $methods = [];
    $hasMobileGateway = afrisense_public_setting_bool('paystack_enabled', true)
        || afrisense_public_setting_bool('mtn_momo_enabled', true)
        || afrisense_public_setting_bool('vodafone_cash_enabled', false)
        || afrisense_public_setting_bool('flutterwave_enabled', true);
    $hasCardGateway = afrisense_public_setting_bool('paystack_enabled', true)
        || afrisense_public_setting_bool('flutterwave_enabled', true);

    if (afrisense_public_setting_bool('mobile_money_enabled', true) && $hasMobileGateway) {
        $methods[] = 'Mobile Money';
    }

    if (afrisense_public_setting_bool('card_payment_enabled', true) && $hasCardGateway) {
        $methods[] = 'Card';
    }

    if (afrisense_public_setting_bool('cash_payment_enabled', true)) {
        $methods[] = 'Cash';
    }

    return $methods !== [] ? $methods : ['Cash'];
}

function afrisense_public_payment_method_allowed(string $method): bool
{
    return in_array($method, afrisense_public_payment_methods(), true);
}

function afrisense_public_paid_order_status(string $paymentMethod): string
{
    return $paymentMethod !== 'Cash' && afrisense_public_setting_bool('auto_confirm_paid_orders', true)
        ? 'Confirmed'
        : 'Pending';
}

function afrisense_public_payment_instruction(): string
{
    return afrisense_public_setting('system', 'payment_instruction', 'You can make payments securely using any of the available payment methods. Your payment is protected with 256-bit SSL encryption.');
}

function afrisense_public_guest_checkout_enabled(): bool
{
    return afrisense_public_setting_bool('guest_checkout_enabled', true);
}

function afrisense_public_order_url(string $frontendBase = '/Afrisense/frontend'): string
{
    $base = rtrim($frontendBase, '/');
    $user = null;

    if (function_exists('afrisense_current_user')) {
        try {
            $user = afrisense_current_user();
        } catch (Throwable) {
            $user = null;
        }
    }

    if ($user !== null && function_exists('afrisense_is_customer') && afrisense_is_customer($user)) {
        return $base . '/customer/orders.php';
    }

    return $base . '/landing/order.php';
}

function afrisense_public_cart_url(string $frontendBase = '/Afrisense/frontend'): string
{
    $base = rtrim($frontendBase, '/');
    $user = null;

    if (function_exists('afrisense_current_user')) {
        try {
            $user = afrisense_current_user();
        } catch (Throwable) {
            $user = null;
        }
    }

    if ($user !== null && function_exists('afrisense_is_customer') && afrisense_is_customer($user)) {
        return $base . '/customer/cart.php';
    }

    return $base . '/landing/cart.php';
}

function afrisense_public_booking_url(string $frontendBase = '/Afrisense/frontend'): string
{
    $base = rtrim($frontendBase, '/');
    $user = null;

    if (function_exists('afrisense_current_user')) {
        try {
            $user = afrisense_current_user();
        } catch (Throwable) {
            $user = null;
        }
    }

    if ($user !== null && function_exists('afrisense_is_customer') && afrisense_is_customer($user)) {
        return $base . '/customer/my-bookings.php#booking_form';
    }

    return $base . '/landing/booking.php';
}

function afrisense_public_support_url(string $frontendBase = '/Afrisense/frontend'): string
{
    $base = rtrim($frontendBase, '/');
    $user = null;

    if (function_exists('afrisense_current_user')) {
        try {
            $user = afrisense_current_user();
        } catch (Throwable) {
            $user = null;
        }
    }

    if ($user !== null && function_exists('afrisense_is_customer') && afrisense_is_customer($user)) {
        return $base . '/customer/support.php';
    }

    return $base . '/landing/support.php';
}

function afrisense_enforce_guest_checkout_enabled(string $frontendBase = '/Afrisense/frontend'): void
{
    if (afrisense_public_guest_checkout_enabled()) {
        return;
    }

    if (!headers_sent()) {
        header('Location: ' . rtrim($frontendBase, '/') . '/auth/login.php?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/Afrisense/frontend/landing/order.php'));
        exit;
    }
}

function afrisense_public_payment_method_icon(string $method): string
{
    return match ($method) {
        'Card' => 'bi-credit-card',
        'Cash' => 'bi-cash-coin',
        default => 'bi-phone',
    };
}

function afrisense_public_payment_method_label(string $method): string
{
    return match ($method) {
        'Card' => 'Card Payment',
        'Cash' => 'Cash on Delivery',
        default => 'Mobile Money',
    };
}

function afrisense_public_payment_method_hint(string $method): string
{
    return match ($method) {
        'Card' => 'Pay securely using your debit or credit card.',
        'Cash' => 'Pay when your food arrives.',
        default => 'Pay using an enabled mobile money gateway.',
    };
}

/**
 * @return list<string>
 */
function afrisense_public_mobile_money_networks(): array
{
    $networks = [];

    if (afrisense_public_setting_bool('mtn_momo_enabled', true)) {
        $networks[] = 'MTN Mobile Money';
    }

    if (afrisense_public_setting_bool('vodafone_cash_enabled', false)) {
        $networks[] = 'Vodafone Cash';
    }

    if (afrisense_public_setting_bool('paystack_enabled', true)) {
        $networks[] = 'Paystack Mobile Money';
    }

    if (afrisense_public_setting_bool('flutterwave_enabled', true)) {
        $networks[] = 'Flutterwave Mobile Money';
    }

    return array_values(array_unique($networks));
}

function afrisense_public_create_admin_notifications(
    PDO $pdo,
    string $settingKey,
    string $title,
    string $message,
    string $type,
    string $actionUrl,
    ?int $createdBy = null
): void {
    if (!afrisense_public_setting_bool($settingKey, true)) {
        return;
    }

    $admins = $pdo->prepare(
        "SELECT u.`id`
         FROM `users` u
         INNER JOIN `roles` r ON r.`id` = u.`role_id`
         WHERE LOWER(COALESCE(r.`rolename`, '')) IN ('administrator', 'admin', 'super admin')"
    );
    $admins->execute();
    $adminIds = $admins->fetchAll(PDO::FETCH_COLUMN);

    if ($adminIds === []) {
        return;
    }

    $notification = $pdo->prepare(
        'INSERT INTO `notifications`
            (`user_id`, `title`, `message`, `notification_type`, `action_url`, `created_by`)
         VALUES
            (:user_id, :title, :message, :notification_type, :action_url, :created_by)'
    );

    foreach ($adminIds as $adminId) {
        $notification->execute([
            'user_id' => (int) $adminId,
            'title' => $title,
            'message' => $message,
            'notification_type' => $type,
            'action_url' => $actionUrl,
            'created_by' => $createdBy,
        ]);
    }
}

function afrisense_public_order_customer_contact(PDO $pdo, int $orderId): ?array
{
    $statement = $pdo->prepare(
        'SELECT
            o.`id`,
            o.`order_status`,
            o.`payment_status`,
            o.`payment_method`,
            o.`total_price`,
            o.`ordered_at`,
            c.`fullname`,
            c.`email`,
            f.`food_name`
         FROM `orders` o
         INNER JOIN `customers` c ON c.`id` = o.`customer_id`
         INNER JOIN `foods` f ON f.`id` = o.`food_id`
         WHERE o.`id` = :order_id
         LIMIT 1'
    );
    $statement->execute(['order_id' => $orderId]);
    $contact = $statement->fetch(PDO::FETCH_ASSOC);

    return $contact !== false ? $contact : null;
}

function afrisense_public_send_order_customer_email(array $contact, string $title, string $message): array
{
    if (!afrisense_public_setting_bool('order_notifications', true)) {
        return ['success' => false, 'message' => 'Order notifications are disabled in settings.'];
    }

    if (!afrisense_public_setting_bool('email_notifications', true)) {
        return ['success' => false, 'message' => 'Email notifications are disabled in settings.'];
    }

    $email = trim((string) ($contact['email'] ?? ''));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Customer email is missing or invalid.'];
    }

    if (!function_exists('afrisense_send_email')) {
        return ['success' => false, 'message' => 'Email sender is unavailable.'];
    }

    $customerName = trim((string) ($contact['fullname'] ?? 'Customer'));
    $safeName = htmlspecialchars($customerName !== '' ? $customerName : 'Customer', ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeOrderId = htmlspecialchars(str_pad((string) ($contact['id'] ?? 0), 5, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8');
    $safeFoodName = htmlspecialchars((string) ($contact['food_name'] ?? 'Your order'), ENT_QUOTES, 'UTF-8');
    $safePayment = htmlspecialchars((string) ($contact['payment_method'] ?? 'Cash'), ENT_QUOTES, 'UTF-8');
    $safeStatus = htmlspecialchars((string) ($contact['order_status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8');
    $safeTotal = htmlspecialchars(afrisense_public_money((float) ($contact['total_price'] ?? 0)), ENT_QUOTES, 'UTF-8');
    $html = <<<HTML
        <h2>{$safeTitle}</h2>
        <p>Hello {$safeName},</p>
        <p>{$safeMessage}</p>
        <table role="presentation" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:18px 0;width:100%;max-width:520px;">
            <tr><td style="padding:8px 0;color:#667085;">Order ID</td><td style="padding:8px 0;font-weight:700;">#{$safeOrderId}</td></tr>
            <tr><td style="padding:8px 0;color:#667085;">Item</td><td style="padding:8px 0;font-weight:700;">{$safeFoodName}</td></tr>
            <tr><td style="padding:8px 0;color:#667085;">Status</td><td style="padding:8px 0;font-weight:700;">{$safeStatus}</td></tr>
            <tr><td style="padding:8px 0;color:#667085;">Payment</td><td style="padding:8px 0;font-weight:700;">{$safePayment}</td></tr>
            <tr><td style="padding:8px 0;color:#667085;">Total</td><td style="padding:8px 0;font-weight:700;">{$safeTotal}</td></tr>
        </table>
        <p>Thank you for ordering from AfriSense.</p>
    HTML;
    $text = $title . "\n\nHello " . ($customerName !== '' ? $customerName : 'Customer') . ",\n\n" . $message . "\n\nOrder #" . str_pad((string) ($contact['id'] ?? 0), 5, '0', STR_PAD_LEFT) . "\nItem: " . (string) ($contact['food_name'] ?? 'Your order') . "\nStatus: " . (string) ($contact['order_status'] ?? 'Pending') . "\nPayment: " . (string) ($contact['payment_method'] ?? 'Cash') . "\nTotal: " . afrisense_public_money((float) ($contact['total_price'] ?? 0));

    return afrisense_send_email($email, $customerName, $title, $html, $text);
}

function afrisense_public_send_order_customer_email_for_order(PDO $pdo, int $orderId, string $title, string $message): array
{
    $contact = afrisense_public_order_customer_contact($pdo, $orderId);

    if ($contact === null) {
        return ['success' => false, 'message' => 'Order customer details could not be found.'];
    }

    return afrisense_public_send_order_customer_email($contact, $title, $message);
}

function afrisense_public_tel_href(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);

    return $digits !== '' ? 'tel:+' . ltrim($digits, '+') : '#';
}

/**
 * @return array<string, array{url: string, icon: string, label: string}>
 */
function afrisense_public_social_links(): array
{
    $company = afrisense_public_settings()['company'];
    $links = [
        'facebook_url' => ['icon' => 'bi-facebook', 'label' => 'Facebook'],
        'instagram_url' => ['icon' => 'bi-instagram', 'label' => 'Instagram'],
        'twitter_url' => ['icon' => 'bi-twitter-x', 'label' => 'Twitter'],
        'linkedin_url' => ['icon' => 'bi-linkedin', 'label' => 'LinkedIn'],
        'youtube_url' => ['icon' => 'bi-youtube', 'label' => 'YouTube'],
        'tiktok_url' => ['icon' => 'bi-tiktok', 'label' => 'TikTok'],
    ];
    $result = [];

    foreach ($links as $key => $meta) {
        $url = trim((string) ($company[$key] ?? ''));

        if ($url !== '') {
            $result[$key] = [
                'url' => $url,
                'icon' => $meta['icon'],
                'label' => $meta['label'],
            ];
        }
    }

    return $result;
}
