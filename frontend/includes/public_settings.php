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

/**
 * @return list<string>
 */
function afrisense_public_payment_methods(): array
{
    $methods = [];

    if (afrisense_public_setting_bool('mobile_money_enabled', true)) {
        $methods[] = 'Mobile Money';
    }

    if (afrisense_public_setting_bool('card_payment_enabled', true)) {
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
