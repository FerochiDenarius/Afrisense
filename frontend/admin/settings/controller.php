<?php
require_once __DIR__ . '/../../auth/auth_bootstrap.php';
require_once __DIR__ . '/../../includes/public_settings.php';

afrisense_require_admin();

function afrisense_fetch_first_row(PDO $pdo, string $table): array
{
    $statement = $pdo->prepare(sprintf('SELECT * FROM `%s` ORDER BY `id` ASC LIMIT 1', $table));
    $statement->execute();
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return $row ?: [];
}

function afrisense_column_exists(PDO $pdo, string $table, string $column): bool
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) AS count_value
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name'
    );
    $statement->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return ((int) ($row['count_value'] ?? 0)) > 0;
}

function afrisense_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (afrisense_column_exists($pdo, $table, $column)) {
        return;
    }

    $statement = $pdo->prepare(sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $table, $column, $definition));
    $statement->execute();
}

function afrisense_ensure_settings_rows(PDO $pdo): void
{
    $websiteColumns = [
        'logo' => 'VARCHAR(255) NULL AFTER `site_tagline`',
        'favicon' => 'VARCHAR(255) NULL AFTER `logo`',
        'hero_image' => 'VARCHAR(255) NULL AFTER `hero_subtitle`',
    ];

    foreach ($websiteColumns as $column => $definition) {
        afrisense_ensure_column($pdo, 'website_settings', $column, $definition);
    }

    $companyColumns = [
        'city' => 'VARCHAR(100) NULL AFTER `address`',
        'region' => 'VARCHAR(100) NULL AFTER `city`',
        'country' => 'VARCHAR(100) NULL AFTER `region`',
        'google_map_iframe' => 'TEXT NULL AFTER `business_hours`',
        'linkedin_url' => 'VARCHAR(255) NULL AFTER `twitter_url`',
        'youtube_url' => 'VARCHAR(255) NULL AFTER `linkedin_url`',
        'tiktok_url' => 'VARCHAR(255) NULL AFTER `youtube_url`',
    ];

    foreach ($companyColumns as $column => $definition) {
        afrisense_ensure_column($pdo, 'company_information', $column, $definition);
    }

    $systemColumns = [
        'site_status' => 'VARCHAR(50) NOT NULL DEFAULT "Online"',
        'default_currency' => 'VARCHAR(10) NOT NULL DEFAULT "GHS"',
        'timezone' => 'VARCHAR(80) NOT NULL DEFAULT "Africa/Accra"',
        'smtp_host' => 'VARCHAR(255) NULL',
        'smtp_port' => 'INT NOT NULL DEFAULT 587',
        'smtp_username' => 'VARCHAR(255) NULL',
        'smtp_password' => 'VARCHAR(255) NULL',
        'smtp_encryption' => 'VARCHAR(20) NOT NULL DEFAULT "tls"',
        'email_notifications' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'booking_notifications' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'order_notifications' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'items_per_page' => 'INT NOT NULL DEFAULT 10',
        'payment_gateway' => 'VARCHAR(50) NOT NULL DEFAULT "Paystack"',
        'paystack_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'mtn_momo_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'vodafone_cash_enabled' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'flutterwave_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'mobile_money_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'card_payment_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'cash_payment_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'guest_checkout_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'auto_confirm_paid_orders' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'payment_test_mode' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'payment_public_key' => 'VARCHAR(255) NULL',
        'payment_secret_key' => 'VARCHAR(255) NULL',
        'payment_webhook_secret' => 'VARCHAR(255) NULL',
        'payment_instruction' => 'TEXT NULL',
        'refund_policy' => 'VARCHAR(100) NOT NULL DEFAULT "Allow refund within 7 days"',
        'cancellation_policy' => 'VARCHAR(100) NOT NULL DEFAULT "Allow cancellation before delivery"',
        'refund_process_message' => 'TEXT NULL',
        'delivery_fee' => 'DECIMAL(10,2) NOT NULL DEFAULT 10.00',
        'service_fee' => 'DECIMAL(10,2) NOT NULL DEFAULT 5.00',
        'free_delivery_over' => 'DECIMAL(10,2) NOT NULL DEFAULT 275.00',
        'delivery_zones' => 'TEXT NULL',
        'default_delivery_time' => 'VARCHAR(50) NOT NULL DEFAULT "30 - 45 minutes"',
        'maximum_delivery_time' => 'VARCHAR(50) NOT NULL DEFAULT "90 minutes"',
        'order_cutoff_time' => 'VARCHAR(20) NOT NULL DEFAULT "22:00"',
        'same_day_delivery' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'weekend_delivery' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'real_time_tracking' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'standard_delivery_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'express_delivery_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'scheduled_delivery_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'pickup_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'delivery_instructions' => 'TEXT NULL',
    ];

    foreach ($systemColumns as $column => $definition) {
        afrisense_ensure_column($pdo, 'system_settings', $column, $definition);
    }

    if (afrisense_fetch_first_row($pdo, 'website_settings') === []) {
        $statement = $pdo->prepare(
            'INSERT INTO `website_settings`
                (`site_name`, `site_tagline`, `primary_color`, `secondary_color`, `hero_title`, `hero_subtitle`, `footer_text`)
             VALUES
                (:site_name, :site_tagline, :primary_color, :secondary_color, :hero_title, :hero_subtitle, :footer_text)'
        );
        $statement->execute([
            'site_name' => 'AfriSense Food Services',
            'site_tagline' => 'Delicious meals, delivered with love.',
            'primary_color' => '#b77b1a',
            'secondary_color' => '#cc8f25',
            'hero_title' => 'Exceptional Food Memorable Moments',
            'hero_subtitle' => 'We provide delicious meals and professional catering services for all occasions.',
            'footer_text' => '(c) 2026 AfriSense Food Services. All rights reserved.',
        ]);
    }

    if (afrisense_fetch_first_row($pdo, 'company_information') === []) {
        $statement = $pdo->prepare(
            'INSERT INTO `company_information`
                (`company_name`, `company_email`, `support_email`, `phone_number_1`, `phone_number_2`, `address`, `city`, `region`, `country`, `business_hours`, `facebook_url`, `instagram_url`, `twitter_url`)
             VALUES
                (:company_name, :company_email, :support_email, :phone_number_1, :phone_number_2, :address, :city, :region, :country, :business_hours, :facebook_url, :instagram_url, :twitter_url)'
        );
        $statement->execute([
            'company_name' => 'AfriSense Food Services',
            'company_email' => 'info@afrisense.com',
            'support_email' => 'support@afrisense.com',
            'phone_number_1' => '+233 24 123 4567',
            'phone_number_2' => '+233 20 987 6543',
            'address' => 'East Legon, Accra, Ghana',
            'city' => 'Accra',
            'region' => 'Greater Accra Region',
            'country' => 'Ghana',
            'business_hours' => "Monday - Friday: 8:00 AM - 10:00 PM\nSaturday: 9:00 AM - 11:00 PM\nSunday: 10:00 AM - 9:00 PM",
            'facebook_url' => 'https://www.facebook.com/',
            'instagram_url' => 'https://www.instagram.com/',
            'twitter_url' => 'https://twitter.com/',
        ]);
    }

    if (afrisense_fetch_first_row($pdo, 'system_settings') === []) {
        $statement = $pdo->prepare(
            'INSERT INTO `system_settings`
                (`site_status`, `default_currency`, `timezone`, `email_notifications`, `booking_notifications`, `order_notifications`)
             VALUES
                (:site_status, :default_currency, :timezone, :email_notifications, :booking_notifications, :order_notifications)'
        );
        $statement->execute([
            'site_status' => 'Online',
            'default_currency' => 'GHS',
            'timezone' => 'Africa/Accra',
            'email_notifications' => 1,
            'booking_notifications' => 1,
            'order_notifications' => 1,
        ]);
    }
}

function afrisense_post_value(array $source, string $key, string $fallback = ''): string
{
    return trim((string) ($source[$key] ?? $fallback));
}

function afrisense_social_post_value(array $company, string $key, string $activeSection): string
{
    if ($activeSection !== 'social') {
        return (string) ($company[$key] ?? '');
    }

    $enabled = isset($_POST['social_enabled']) && is_array($_POST['social_enabled']) && isset($_POST['social_enabled'][$key]);

    if (!$enabled) {
        return '';
    }

    $url = afrisense_post_value($_POST, $key);

    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) === false) {
        throw new RuntimeException('Please enter a valid URL for ' . str_replace('_url', '', $key) . '.');
    }

    return $url;
}

function afrisense_post_bool_setting(string $key, array $system, string $activeSection, string $section): int
{
    if ($activeSection !== $section) {
        return (int) ($system[$key] ?? 0);
    }

    return isset($_POST[$key]) ? 1 : 0;
}

function afrisense_post_text_setting(string $key, array $system, string $activeSection, string $section, string $fallback = ''): string
{
    if ($activeSection !== $section) {
        return (string) ($system[$key] ?? $fallback);
    }

    return afrisense_post_value($_POST, $key, $fallback);
}

function afrisense_post_decimal_setting(string $key, array $system, string $activeSection, string $section, float $fallback): float
{
    if ($activeSection !== $section) {
        return (float) ($system[$key] ?? $fallback);
    }

    return max(0.00, (float) afrisense_post_value($_POST, $key, (string) $fallback));
}

function afrisense_default_delivery_zones(float $baseDeliveryFee = 10.00): array
{
    return [
        [
            'name' => 'Accra Central',
            'areas' => 'Osu, Airport, Labone, Cantonments, East Legon',
            'fee' => $baseDeliveryFee,
            'min_order' => 60.00,
            'status' => 'Active',
        ],
        [
            'name' => 'Accra Surrounding',
            'areas' => 'Madina, Adenta, Achimota, Dansoman, Nungua',
            'fee' => $baseDeliveryFee + 5.00,
            'min_order' => 80.00,
            'status' => 'Active',
        ],
        [
            'name' => 'Greater Accra',
            'areas' => 'Tema, Prampram, Kasoa, Amasaman, Teshie, Bortianor',
            'fee' => $baseDeliveryFee + 15.00,
            'min_order' => 120.00,
            'status' => 'Inactive',
        ],
    ];
}

function afrisense_delivery_zones(array $system): array
{
    $baseDeliveryFee = (float) ($system['delivery_fee'] ?? 10.00);
    $decoded = json_decode((string) ($system['delivery_zones'] ?? ''), true);

    if (!is_array($decoded)) {
        return afrisense_default_delivery_zones($baseDeliveryFee);
    }

    $zones = [];
    foreach ($decoded as $zone) {
        if (!is_array($zone)) {
            continue;
        }

        $name = trim((string) ($zone['name'] ?? ''));
        $areas = trim((string) ($zone['areas'] ?? ''));

        if ($name === '' && $areas === '') {
            continue;
        }

        $zones[] = [
            'name' => $name !== '' ? $name : 'Delivery Zone',
            'areas' => $areas,
            'fee' => max(0.00, (float) ($zone['fee'] ?? $baseDeliveryFee)),
            'min_order' => max(0.00, (float) ($zone['min_order'] ?? 0.00)),
            'status' => in_array((string) ($zone['status'] ?? 'Active'), ['Active', 'Inactive'], true) ? (string) $zone['status'] : 'Active',
        ];
    }

    return $zones !== [] ? $zones : afrisense_default_delivery_zones($baseDeliveryFee);
}

function afrisense_post_delivery_zones(array $system, string $activeSection): string
{
    if ($activeSection !== 'delivery') {
        return (string) ($system['delivery_zones'] ?? '');
    }

    $postedZones = $_POST['delivery_zones'] ?? [];

    if (!is_array($postedZones)) {
        return json_encode(afrisense_default_delivery_zones(), JSON_THROW_ON_ERROR);
    }

    $names = is_array($postedZones['name'] ?? null) ? $postedZones['name'] : [];
    $areasList = is_array($postedZones['areas'] ?? null) ? $postedZones['areas'] : [];
    $fees = is_array($postedZones['fee'] ?? null) ? $postedZones['fee'] : [];
    $minimums = is_array($postedZones['min_order'] ?? null) ? $postedZones['min_order'] : [];
    $statuses = is_array($postedZones['status'] ?? null) ? $postedZones['status'] : [];
    $zones = [];

    foreach ($names as $index => $name) {
        $zoneName = trim((string) $name);
        $zoneAreas = trim((string) ($areasList[$index] ?? ''));

        if ($zoneName === '' && $zoneAreas === '') {
            continue;
        }

        $zones[] = [
            'name' => $zoneName !== '' ? $zoneName : 'Delivery Zone',
            'areas' => $zoneAreas,
            'fee' => max(0.00, (float) ($fees[$index] ?? 0.00)),
            'min_order' => max(0.00, (float) ($minimums[$index] ?? 0.00)),
            'status' => in_array((string) ($statuses[$index] ?? 'Active'), ['Active', 'Inactive'], true) ? (string) $statuses[$index] : 'Active',
        ];
    }

    return json_encode($zones !== [] ? $zones : afrisense_default_delivery_zones(), JSON_THROW_ON_ERROR);
}

function afrisense_upload_settings_asset(string $field, string $prefix): ?string
{
    $file = $_FILES[$field] ?? null;

    if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The selected image could not be uploaded.');
    }

    if ((int) ($file['size'] ?? 0) > 3 * 1024 * 1024) {
        throw new RuntimeException('Settings images must be 3MB or smaller.');
    }

    $temporaryName = (string) ($file['tmp_name'] ?? '');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo !== false ? (string) finfo_file($finfo, $temporaryName) : '';

    if ($finfo !== false) {
        finfo_close($finfo);
    }

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
    ];

    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new RuntimeException('Settings images must be JPG, PNG, WebP, GIF or ICO.');
    }

    $uploadRoot = __DIR__ . '/../../uploads';
    $uploadDirectory = $uploadRoot . '/settings';

    foreach ([$uploadRoot, $uploadDirectory] as $directory) {
        if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
            throw new RuntimeException('Settings upload folder could not be created.');
        }

        if (!is_writable($directory)) {
            throw new RuntimeException('Settings upload folder is not writable by XAMPP.');
        }
    }

    $filename = $prefix . '-' . bin2hex(random_bytes(12)) . '.' . $allowedMimeTypes[$mimeType];
    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($temporaryName, $destination)) {
        throw new RuntimeException('Settings image could not be saved.');
    }

    return 'settings/' . $filename;
}

function afrisense_settings_asset_url(string $frontendBase, ?string $image): string
{
    $image = trim((string) $image);

    if ($image === '') {
        return '';
    }

    $relativeImage = ltrim(str_replace('\\', '/', $image), '/');

    if (is_file(__DIR__ . '/../uploads/' . $relativeImage)) {
        return $frontendBase . '/uploads/' . $relativeImage;
    }

    return '';
}

$settingTabs = [
    'general' => ['label' => 'General Settings', 'icon' => 'bi-gear'],
    'company' => ['label' => 'Company Information', 'icon' => 'bi-buildings'],
    'social' => ['label' => 'Social Media', 'icon' => 'bi-share'],
    'payment' => ['label' => 'Payment Settings', 'icon' => 'bi-credit-card'],
    'delivery' => ['label' => 'Delivery Settings', 'icon' => 'bi-truck'],
    'seo' => ['label' => 'SEO & Analytics', 'icon' => 'bi-bar-chart-line'],
    'email' => ['label' => 'Email Settings', 'icon' => 'bi-envelope'],
    'maintenance' => ['label' => 'Maintenance Mode', 'icon' => 'bi-tools'],
];
$activeSettingSection = (string) ($_POST['settings_section'] ?? $_GET['section'] ?? 'general');
if (!array_key_exists($activeSettingSection, $settingTabs)) {
    $activeSettingSection = 'general';
}

$flashMessage = '';
$flashType = 'success';

try {
    $pdo = afrisense_pdo();
    afrisense_ensure_settings_rows($pdo);
    $website = afrisense_fetch_first_row($pdo, 'website_settings');
    $company = afrisense_fetch_first_row($pdo, 'company_information');
    $system = afrisense_fetch_first_row($pdo, 'system_settings');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $logoValue = (string) ($website['logo'] ?? '');
        $faviconValue = (string) ($website['favicon'] ?? '');

        if ($activeSettingSection === 'company') {
            if (isset($_POST['remove_logo'])) {
                $logoValue = '';
            }

            if (isset($_POST['remove_favicon'])) {
                $faviconValue = '';
            }

            $uploadedLogo = afrisense_upload_settings_asset('logo_file', 'logo');
            if ($uploadedLogo !== null) {
                $logoValue = $uploadedLogo;
            }

            $uploadedFavicon = afrisense_upload_settings_asset('favicon_file', 'favicon');
            if ($uploadedFavicon !== null) {
                $faviconValue = $uploadedFavicon;
            }
        }

        $siteStatus = $activeSettingSection === 'maintenance'
            ? (($_POST['maintenance_mode'] ?? '') === '1' ? 'Maintenance' : 'Online')
            : (string) ($system['site_status'] ?? 'Online');
        $emailNotifications = $activeSettingSection === 'payment'
            ? (($_POST['email_notifications'] ?? '') === '1' ? 1 : 0)
            : (int) ($system['email_notifications'] ?? 1);
        $bookingNotifications = $activeSettingSection === 'maintenance'
            ? (($_POST['booking_notifications'] ?? '') === '1' ? 1 : 0)
            : (int) ($system['booking_notifications'] ?? 1);
        $orderNotifications = $activeSettingSection === 'payment'
            ? (($_POST['order_notifications'] ?? '') === '1' ? 1 : 0)
            : (int) ($system['order_notifications'] ?? 1);
        $smtpPassword = afrisense_post_value($_POST, 'smtp_password');
        if ($smtpPassword === '') {
            $smtpPassword = (string) ($system['smtp_password'] ?? '');
        }

        $pdo->beginTransaction();

        $statement = $pdo->prepare(
            'UPDATE `website_settings`
             SET `site_name` = :site_name,
                 `site_tagline` = :site_tagline,
                 `logo` = :logo,
                 `favicon` = :favicon,
                 `primary_color` = :primary_color,
                 `secondary_color` = :secondary_color,
                 `hero_title` = :hero_title,
                 `hero_subtitle` = :hero_subtitle,
                 `footer_text` = :footer_text,
                 `updated_at` = NOW()
             ORDER BY `id` ASC
             LIMIT 1'
        );
        $statement->execute([
            'site_name' => afrisense_post_value($_POST, 'site_name', (string) ($website['site_name'] ?? 'AfriSense Food Services')),
            'site_tagline' => afrisense_post_value($_POST, 'site_tagline', (string) ($website['site_tagline'] ?? '')),
            'logo' => $logoValue,
            'favicon' => $faviconValue,
            'primary_color' => afrisense_post_value($_POST, 'primary_color', (string) ($website['primary_color'] ?? '#b77b1a')),
            'secondary_color' => afrisense_post_value($_POST, 'secondary_color', (string) ($website['secondary_color'] ?? '#cc8f25')),
            'hero_title' => afrisense_post_value($_POST, 'hero_title', (string) ($website['hero_title'] ?? 'Exceptional Food Memorable Moments')),
            'hero_subtitle' => afrisense_post_value($_POST, 'hero_subtitle', (string) ($website['hero_subtitle'] ?? '')),
            'footer_text' => afrisense_post_value($_POST, 'footer_text', (string) ($website['footer_text'] ?? '')),
        ]);

        $statement = $pdo->prepare(
            'UPDATE `company_information`
             SET `company_name` = :company_name,
                 `company_email` = :company_email,
                 `support_email` = :support_email,
                 `phone_number_1` = :phone_number_1,
                 `phone_number_2` = :phone_number_2,
                 `address` = :address,
                 `city` = :city,
                 `region` = :region,
                 `country` = :country,
                 `business_hours` = :business_hours,
                 `google_map_iframe` = :google_map_iframe,
                 `facebook_url` = :facebook_url,
                 `instagram_url` = :instagram_url,
                 `twitter_url` = :twitter_url,
                 `linkedin_url` = :linkedin_url,
                 `youtube_url` = :youtube_url,
                 `tiktok_url` = :tiktok_url,
                 `updated_at` = NOW()
             ORDER BY `id` ASC
             LIMIT 1'
        );
        $statement->execute([
            'company_name' => afrisense_post_value($_POST, 'company_name', (string) ($company['company_name'] ?? 'AfriSense Food Services')),
            'company_email' => afrisense_post_value($_POST, 'company_email', (string) ($company['company_email'] ?? 'info@afrisense.com')),
            'support_email' => afrisense_post_value($_POST, 'support_email', (string) ($company['support_email'] ?? 'support@afrisense.com')),
            'phone_number_1' => afrisense_post_value($_POST, 'phone_number_1', (string) ($company['phone_number_1'] ?? '+233 24 123 4567')),
            'phone_number_2' => afrisense_post_value($_POST, 'phone_number_2', (string) ($company['phone_number_2'] ?? '')),
            'address' => afrisense_post_value($_POST, 'address', (string) ($company['address'] ?? 'Accra, Ghana')),
            'city' => afrisense_post_value($_POST, 'city', (string) ($company['city'] ?? 'Accra')),
            'region' => afrisense_post_value($_POST, 'region', (string) ($company['region'] ?? 'Greater Accra Region')),
            'country' => afrisense_post_value($_POST, 'country', (string) ($company['country'] ?? 'Ghana')),
            'business_hours' => afrisense_post_value($_POST, 'business_hours', (string) ($company['business_hours'] ?? '')),
            'google_map_iframe' => afrisense_post_value($_POST, 'google_map_iframe', (string) ($company['google_map_iframe'] ?? '')),
            'facebook_url' => afrisense_social_post_value($company, 'facebook_url', $activeSettingSection),
            'instagram_url' => afrisense_social_post_value($company, 'instagram_url', $activeSettingSection),
            'twitter_url' => afrisense_social_post_value($company, 'twitter_url', $activeSettingSection),
            'linkedin_url' => afrisense_social_post_value($company, 'linkedin_url', $activeSettingSection),
            'youtube_url' => afrisense_social_post_value($company, 'youtube_url', $activeSettingSection),
            'tiktok_url' => afrisense_social_post_value($company, 'tiktok_url', $activeSettingSection),
        ]);

        $statement = $pdo->prepare(
            'UPDATE `system_settings`
             SET `site_status` = :site_status,
                 `default_currency` = :default_currency,
                 `timezone` = :timezone,
                 `smtp_host` = :smtp_host,
                 `smtp_port` = :smtp_port,
                 `smtp_username` = :smtp_username,
                 `smtp_password` = :smtp_password,
                 `smtp_encryption` = :smtp_encryption,
                 `email_notifications` = :email_notifications,
                 `booking_notifications` = :booking_notifications,
                 `order_notifications` = :order_notifications,
                 `items_per_page` = :items_per_page,
                 `payment_gateway` = :payment_gateway,
                 `paystack_enabled` = :paystack_enabled,
                 `mtn_momo_enabled` = :mtn_momo_enabled,
                 `vodafone_cash_enabled` = :vodafone_cash_enabled,
                 `flutterwave_enabled` = :flutterwave_enabled,
                 `mobile_money_enabled` = :mobile_money_enabled,
                 `card_payment_enabled` = :card_payment_enabled,
                 `cash_payment_enabled` = :cash_payment_enabled,
                 `guest_checkout_enabled` = :guest_checkout_enabled,
                 `auto_confirm_paid_orders` = :auto_confirm_paid_orders,
                 `payment_test_mode` = :payment_test_mode,
                 `payment_public_key` = :payment_public_key,
                 `payment_secret_key` = :payment_secret_key,
                 `payment_webhook_secret` = :payment_webhook_secret,
                 `payment_instruction` = :payment_instruction,
                 `refund_policy` = :refund_policy,
                 `cancellation_policy` = :cancellation_policy,
                 `refund_process_message` = :refund_process_message,
                 `delivery_fee` = :delivery_fee,
                 `service_fee` = :service_fee,
                 `free_delivery_over` = :free_delivery_over,
                 `delivery_zones` = :delivery_zones,
                 `default_delivery_time` = :default_delivery_time,
                 `maximum_delivery_time` = :maximum_delivery_time,
                 `order_cutoff_time` = :order_cutoff_time,
                 `same_day_delivery` = :same_day_delivery,
                 `weekend_delivery` = :weekend_delivery,
                 `real_time_tracking` = :real_time_tracking,
                 `standard_delivery_enabled` = :standard_delivery_enabled,
                 `express_delivery_enabled` = :express_delivery_enabled,
                 `scheduled_delivery_enabled` = :scheduled_delivery_enabled,
                 `pickup_enabled` = :pickup_enabled,
                 `delivery_instructions` = :delivery_instructions,
                 `updated_at` = NOW()
             ORDER BY `id` ASC
             LIMIT 1'
        );
        $statement->execute([
            'site_status' => $siteStatus,
            'default_currency' => afrisense_post_value($_POST, 'default_currency', (string) ($system['default_currency'] ?? 'GHS')),
            'timezone' => afrisense_post_value($_POST, 'timezone', (string) ($system['timezone'] ?? 'Africa/Accra')),
            'smtp_host' => afrisense_post_value($_POST, 'smtp_host', (string) ($system['smtp_host'] ?? '')),
            'smtp_port' => (int) afrisense_post_value($_POST, 'smtp_port', (string) ($system['smtp_port'] ?? '587')),
            'smtp_username' => afrisense_post_value($_POST, 'smtp_username', (string) ($system['smtp_username'] ?? '')),
            'smtp_password' => $smtpPassword,
            'smtp_encryption' => afrisense_post_value($_POST, 'smtp_encryption', (string) ($system['smtp_encryption'] ?? 'tls')),
            'email_notifications' => $emailNotifications,
            'booking_notifications' => $bookingNotifications,
            'order_notifications' => $orderNotifications,
            'items_per_page' => max(5, min(100, (int) afrisense_post_value($_POST, 'items_per_page', (string) ($system['items_per_page'] ?? '10')))),
            'payment_gateway' => afrisense_post_text_setting('payment_gateway', $system, $activeSettingSection, 'payment', 'Paystack'),
            'paystack_enabled' => afrisense_post_bool_setting('paystack_enabled', $system, $activeSettingSection, 'payment'),
            'mtn_momo_enabled' => afrisense_post_bool_setting('mtn_momo_enabled', $system, $activeSettingSection, 'payment'),
            'vodafone_cash_enabled' => afrisense_post_bool_setting('vodafone_cash_enabled', $system, $activeSettingSection, 'payment'),
            'flutterwave_enabled' => afrisense_post_bool_setting('flutterwave_enabled', $system, $activeSettingSection, 'payment'),
            'mobile_money_enabled' => afrisense_post_bool_setting('mobile_money_enabled', $system, $activeSettingSection, 'payment'),
            'card_payment_enabled' => afrisense_post_bool_setting('card_payment_enabled', $system, $activeSettingSection, 'payment'),
            'cash_payment_enabled' => afrisense_post_bool_setting('cash_payment_enabled', $system, $activeSettingSection, 'payment'),
            'guest_checkout_enabled' => afrisense_post_bool_setting('guest_checkout_enabled', $system, $activeSettingSection, 'payment'),
            'auto_confirm_paid_orders' => afrisense_post_bool_setting('auto_confirm_paid_orders', $system, $activeSettingSection, 'payment'),
            'payment_test_mode' => afrisense_post_bool_setting('payment_test_mode', $system, $activeSettingSection, 'payment'),
            'payment_public_key' => afrisense_post_text_setting('payment_public_key', $system, $activeSettingSection, 'payment'),
            'payment_secret_key' => afrisense_post_text_setting('payment_secret_key', $system, $activeSettingSection, 'payment'),
            'payment_webhook_secret' => afrisense_post_text_setting('payment_webhook_secret', $system, $activeSettingSection, 'payment'),
            'payment_instruction' => afrisense_post_text_setting('payment_instruction', $system, $activeSettingSection, 'payment', 'You can make payments securely using any of the available payment methods. Your payment is protected with 256-bit SSL encryption.'),
            'refund_policy' => afrisense_post_text_setting('refund_policy', $system, $activeSettingSection, 'payment', 'Allow refund within 7 days'),
            'cancellation_policy' => afrisense_post_text_setting('cancellation_policy', $system, $activeSettingSection, 'payment', 'Allow cancellation before delivery'),
            'refund_process_message' => afrisense_post_text_setting('refund_process_message', $system, $activeSettingSection, 'payment', 'Refunds are processed within 3-5 working days to your original payment method.'),
            'delivery_fee' => afrisense_post_decimal_setting('delivery_fee', $system, $activeSettingSection, 'delivery', 10.00),
            'service_fee' => afrisense_post_decimal_setting('service_fee', $system, $activeSettingSection, 'delivery', 5.00),
            'free_delivery_over' => afrisense_post_decimal_setting('free_delivery_over', $system, $activeSettingSection, 'delivery', 275.00),
            'delivery_zones' => afrisense_post_delivery_zones($system, $activeSettingSection),
            'default_delivery_time' => afrisense_post_text_setting('default_delivery_time', $system, $activeSettingSection, 'delivery', '30 - 45 minutes'),
            'maximum_delivery_time' => afrisense_post_text_setting('maximum_delivery_time', $system, $activeSettingSection, 'delivery', '90 minutes'),
            'order_cutoff_time' => afrisense_post_text_setting('order_cutoff_time', $system, $activeSettingSection, 'delivery', '22:00'),
            'same_day_delivery' => afrisense_post_bool_setting('same_day_delivery', $system, $activeSettingSection, 'delivery'),
            'weekend_delivery' => afrisense_post_bool_setting('weekend_delivery', $system, $activeSettingSection, 'delivery'),
            'real_time_tracking' => afrisense_post_bool_setting('real_time_tracking', $system, $activeSettingSection, 'delivery'),
            'standard_delivery_enabled' => afrisense_post_bool_setting('standard_delivery_enabled', $system, $activeSettingSection, 'delivery'),
            'express_delivery_enabled' => afrisense_post_bool_setting('express_delivery_enabled', $system, $activeSettingSection, 'delivery'),
            'scheduled_delivery_enabled' => afrisense_post_bool_setting('scheduled_delivery_enabled', $system, $activeSettingSection, 'delivery'),
            'pickup_enabled' => afrisense_post_bool_setting('pickup_enabled', $system, $activeSettingSection, 'delivery'),
            'delivery_instructions' => afrisense_post_text_setting('delivery_instructions', $system, $activeSettingSection, 'delivery', 'Please ensure someone is available to receive the order at the delivery address. We will contact you when we are on our way.'),
        ]);

        $pdo->commit();
        $flashMessage = 'Website settings saved successfully.';
    }

    $website = afrisense_fetch_first_row($pdo, 'website_settings');
    $company = afrisense_fetch_first_row($pdo, 'company_information');
    $system = afrisense_fetch_first_row($pdo, 'system_settings');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $website = [];
    $company = [];
    $system = [];
    $flashType = 'error';
    $flashMessage = 'Settings could not be loaded. Check that MySQL is running.';
}
