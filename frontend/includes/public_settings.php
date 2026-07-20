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
        ],
        'system' => [
            'site_status' => 'Online',
            'default_currency' => 'GHS',
            'timezone' => 'Africa/Accra',
            'email_notifications' => 1,
            'booking_notifications' => 1,
            'order_notifications' => 1,
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
