<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Website Settings | AfriSense';
$adminTitle = 'Website Settings';
$activeAdminPage = 'settings';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];
$extraScripts = [
    $frontendBase . '/assets/js/settings.js',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/public_settings.php';

afrisense_require_admin();

function afrisense_fetch_first_row(PDO $pdo, string $table): array
{
    $statement = $pdo->prepare(sprintf('SELECT * FROM `%s` ORDER BY `id` ASC LIMIT 1', $table));
    $statement->execute();
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return $row ?: [];
}

function afrisense_ensure_settings_rows(PDO $pdo): void
{
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

    $uploadRoot = __DIR__ . '/../uploads';
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
            'facebook_url' => afrisense_post_value($_POST, 'facebook_url', (string) ($company['facebook_url'] ?? '')),
            'instagram_url' => afrisense_post_value($_POST, 'instagram_url', (string) ($company['instagram_url'] ?? '')),
            'twitter_url' => afrisense_post_value($_POST, 'twitter_url', (string) ($company['twitter_url'] ?? '')),
            'linkedin_url' => afrisense_post_value($_POST, 'linkedin_url', (string) ($company['linkedin_url'] ?? '')),
            'youtube_url' => afrisense_post_value($_POST, 'youtube_url', (string) ($company['youtube_url'] ?? '')),
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

ob_start();
?>
<section class="af-admin-menu-page af-settings-page">
    <header class="af-admin-page-heading">
        <div>
            <h1>Website Settings</h1>
            <p>Dashboard / Website Settings / <?php echo htmlspecialchars($settingTabs[$activeSettingSection]['label'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <button class="af-add-menu-btn" type="submit" form="website_settings_form">
            <i class="bi bi-floppy" aria-hidden="true"></i>
            Save Changes
        </button>
    </header>

    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <nav class="af-settings-tabs" aria-label="Settings sections">
        <?php foreach ($settingTabs as $sectionKey => $tab): ?>
            <a class="<?php echo $activeSettingSection === $sectionKey ? 'active' : ''; ?>" href="settings.php?section=<?php echo htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi <?php echo htmlspecialchars($tab['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                <?php echo htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form id="website_settings_form" class="af-settings-grid af-settings-grid-<?php echo htmlspecialchars($activeSettingSection, ENT_QUOTES, 'UTF-8'); ?>" action="settings.php?section=<?php echo htmlspecialchars($activeSettingSection, ENT_QUOTES, 'UTF-8'); ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="settings_section" value="<?php echo htmlspecialchars($activeSettingSection, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($activeSettingSection === 'general'): ?>
        <section class="af-settings-card" id="general-settings">
            <h2>General Settings</h2>
            <p>Manage your website general preferences and configurations.</p>

            <label class="af-settings-field" for="site_name">
                <span>Website Name</span>
                <input id="site_name" name="site_name" type="text" value="<?php echo htmlspecialchars((string) ($website['site_name'] ?? 'AfriSense Food Services'), ENT_QUOTES, 'UTF-8'); ?>" required>
            </label>

            <label class="af-settings-field" for="site_tagline">
                <span>Website Tagline</span>
                <input id="site_tagline" name="site_tagline" type="text" value="<?php echo htmlspecialchars((string) ($website['site_tagline'] ?? 'Delicious meals, delivered with love.'), ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label class="af-settings-field" for="hero_title">
                <span>Homepage Hero Title</span>
                <input id="hero_title" name="hero_title" type="text" value="<?php echo htmlspecialchars((string) ($website['hero_title'] ?? 'Exceptional Food Memorable Moments'), ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label class="af-settings-field" for="hero_subtitle">
                <span>Homepage Hero Subtitle</span>
                <textarea id="hero_subtitle" name="hero_subtitle" rows="3"><?php echo htmlspecialchars((string) ($website['hero_subtitle'] ?? 'We provide delicious meals and professional catering services for all occasions.'), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>

            <div class="af-settings-color-grid">
                <label class="af-settings-field" for="primary_color">
                    <span>Primary Gold</span>
                    <input id="primary_color" name="primary_color" type="color" value="<?php echo htmlspecialchars((string) ($website['primary_color'] ?? '#b77b1a'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
                <label class="af-settings-field" for="secondary_color">
                    <span>Secondary Gold</span>
                    <input id="secondary_color" name="secondary_color" type="color" value="<?php echo htmlspecialchars((string) ($website['secondary_color'] ?? '#cc8f25'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($activeSettingSection === 'company'): ?>
        <section class="af-settings-card af-settings-card-wide" id="company-information">
            <h2>Company Information</h2>
            <p>Update your company details and contact information shown across the website.</p>

            <div class="af-settings-two">
                <label class="af-settings-field" for="company_name">
                    <span>Company / Business Name</span>
                    <input id="company_name" name="company_name" type="text" value="<?php echo htmlspecialchars((string) ($company['company_name'] ?? 'AfriSense Food Services'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>

                <label class="af-settings-field" for="company_tagline">
                    <span>Tagline</span>
                    <input id="company_tagline" type="text" value="<?php echo htmlspecialchars((string) ($website['site_tagline'] ?? 'Delicious meals, delivered with love.'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </label>
            </div>

            <div class="af-settings-two">
                <label class="af-settings-field" for="company_email">
                    <span>Business Email</span>
                    <input id="company_email" name="company_email" type="email" value="<?php echo htmlspecialchars((string) ($company['company_email'] ?? 'info@afrisense.com'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>

                <label class="af-settings-field" for="support_email">
                    <span>Customer Support Email</span>
                    <input id="support_email" name="support_email" type="email" value="<?php echo htmlspecialchars((string) ($company['support_email'] ?? 'support@afrisense.com'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
            </div>

            <div class="af-settings-two">
                <label class="af-settings-field" for="phone_number_1">
                    <span>Business Phone</span>
                    <input id="phone_number_1" name="phone_number_1" type="tel" value="<?php echo htmlspecialchars((string) ($company['phone_number_1'] ?? '+233 24 123 4567'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>

                <label class="af-settings-field" for="phone_number_2">
                    <span>Alternative Phone</span>
                    <input id="phone_number_2" name="phone_number_2" type="tel" value="<?php echo htmlspecialchars((string) ($company['phone_number_2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
            </div>

            <div class="af-settings-two">
                <label class="af-settings-field" for="address">
                    <span>Business Address</span>
                    <input id="address" name="address" type="text" value="<?php echo htmlspecialchars((string) ($company['address'] ?? 'East Legon, Accra, Ghana'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>

                <label class="af-settings-field" for="city">
                    <span>City</span>
                    <input id="city" name="city" type="text" value="<?php echo htmlspecialchars((string) ($company['city'] ?? 'Accra'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
            </div>

            <div class="af-settings-two">
                <label class="af-settings-field" for="region">
                    <span>Region</span>
                    <input id="region" name="region" type="text" value="<?php echo htmlspecialchars((string) ($company['region'] ?? 'Greater Accra Region'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>

                <label class="af-settings-field" for="country">
                    <span>Country</span>
                    <input id="country" name="country" type="text" value="<?php echo htmlspecialchars((string) ($company['country'] ?? 'Ghana'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
            </div>

            <label class="af-settings-field" for="google_map_iframe">
                <span>Google Map Embed</span>
                <textarea id="google_map_iframe" name="google_map_iframe" rows="3" placeholder="Paste Google Maps iframe code or map URL"><?php echo htmlspecialchars((string) ($company['google_map_iframe'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
        </section>

        <section class="af-settings-card af-company-assets-card">
            <?php
            $logoUrl = afrisense_settings_asset_url($frontendBase, (string) ($website['logo'] ?? ''));
            $faviconUrl = afrisense_settings_asset_url($frontendBase, (string) ($website['favicon'] ?? ''));
            ?>
            <h2>Company Logo</h2>
            <p>Upload your company logo. Recommended size: 300 x 100px.</p>
            <div class="af-logo-preview">
                <?php if ($logoUrl !== ''): ?>
                    <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Company logo">
                <?php else: ?>
                    <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
                    <strong>Afri<span>Sense</span></strong>
                    <small>Food Services</small>
                <?php endif; ?>
            </div>
            <div class="af-settings-actions">
                <label class="af-settings-upload-btn" for="logo_file">
                    <i class="bi bi-upload" aria-hidden="true"></i>
                    Change Logo
                </label>
                <input id="logo_file" name="logo_file" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                <button class="danger" name="remove_logo" value="1" type="submit" formnovalidate>
                    <i class="bi bi-trash" aria-hidden="true"></i>
                    Remove
                </button>
            </div>

            <h2>Favicon</h2>
            <p>Upload favicon for your website. Recommended size: 32 x 32px.</p>
            <div class="af-favicon-preview">
                <?php if ($faviconUrl !== ''): ?>
                    <img src="<?php echo htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Website favicon">
                <?php else: ?>
                    <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
                <?php endif; ?>
            </div>
            <div class="af-settings-actions">
                <label class="af-settings-upload-btn" for="favicon_file">
                    <i class="bi bi-upload" aria-hidden="true"></i>
                    Change Favicon
                </label>
                <input id="favicon_file" name="favicon_file" type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/x-icon">
                <button class="danger" name="remove_favicon" value="1" type="submit" formnovalidate>
                    <i class="bi bi-trash" aria-hidden="true"></i>
                    Remove
                </button>
            </div>
        </section>

        <section class="af-settings-card af-settings-card-wide">
            <h2>Business Hours</h2>
            <p>Set your business operating hours as they appear on the website.</p>
            <div class="af-business-hours-editor">
                <?php
                $businessHoursText = (string) ($company['business_hours'] ?? "Monday - Friday: 8:00 AM - 10:00 PM\nSaturday: 9:00 AM - 11:00 PM\nSunday: 10:00 AM - 9:00 PM");
                ?>
                <textarea id="business_hours" name="business_hours" rows="7"><?php echo htmlspecialchars($businessHoursText, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <div class="af-business-hours-guide" aria-hidden="true">
                    <span><strong>Monday - Friday</strong><em>08:00 AM - 10:00 PM</em></span>
                    <span><strong>Saturday</strong><em>09:00 AM - 11:00 PM</em></span>
                    <span><strong>Sunday</strong><em>10:00 AM - 09:00 PM</em></span>
                </div>
            </div>
        </section>

        <section class="af-settings-card">
            <h2>Other Information</h2>
            <p>Additional company information available in the current database.</p>
            <label class="af-settings-field" for="other_currency">
                <span>Currency</span>
                <?php $companyCurrency = (string) ($system['default_currency'] ?? 'GHS'); ?>
                <select id="other_currency" name="default_currency">
                    <option value="GHS" <?php echo $companyCurrency === 'GHS' ? 'selected' : ''; ?>>GHS (GHc) - Ghana Cedi</option>
                    <option value="USD" <?php echo $companyCurrency === 'USD' ? 'selected' : ''; ?>>USD ($) - US Dollar</option>
                </select>
            </label>
            <label class="af-settings-field" for="other_timezone">
                <span>Default Timezone</span>
                <?php $companyTimezone = (string) ($system['timezone'] ?? 'Africa/Accra'); ?>
                <select id="other_timezone" name="timezone">
                    <option value="Africa/Accra" <?php echo $companyTimezone === 'Africa/Accra' ? 'selected' : ''; ?>>(GMT+00:00) Accra, Ghana</option>
                    <option value="UTC" <?php echo $companyTimezone === 'UTC' ? 'selected' : ''; ?>>(GMT+00:00) UTC</option>
                </select>
            </label>
            <label class="af-settings-field" for="other_support_email">
                <span>Support Email</span>
                <input id="other_support_email" type="email" value="<?php echo htmlspecialchars((string) ($company['support_email'] ?? 'support@afrisense.com'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </label>
        </section>
        <?php endif; ?>

        <?php if ($activeSettingSection === 'social'): ?>
        <?php
        $socialRows = [
            ['key' => 'facebook_url', 'name' => 'Facebook', 'hint' => 'Facebook Page URL', 'icon' => 'bi-facebook', 'class' => 'facebook', 'placeholder' => 'https://facebook.com/afrisensefoods'],
            ['key' => 'instagram_url', 'name' => 'Instagram', 'hint' => 'Instagram Profile URL', 'icon' => 'bi-instagram', 'class' => 'instagram', 'placeholder' => 'https://instagram.com/afrisense_gh'],
            ['key' => 'twitter_url', 'name' => 'Twitter (X)', 'hint' => 'Twitter Profile URL', 'icon' => 'bi-twitter-x', 'class' => 'twitter', 'placeholder' => 'https://twitter.com/afrisense_gh'],
            ['key' => 'linkedin_url', 'name' => 'LinkedIn', 'hint' => 'LinkedIn Company URL', 'icon' => 'bi-linkedin', 'class' => 'linkedin', 'placeholder' => 'https://linkedin.com/company/afrisense-foods'],
            ['key' => 'youtube_url', 'name' => 'YouTube', 'hint' => 'YouTube Channel URL', 'icon' => 'bi-youtube', 'class' => 'youtube', 'placeholder' => 'https://youtube.com/@afrisensefoods'],
            ['key' => 'tiktok_url', 'name' => 'TikTok', 'hint' => 'TikTok Profile URL', 'icon' => 'bi-tiktok', 'class' => 'tiktok', 'placeholder' => 'Add tiktok_url column to save this link', 'disabled' => true],
        ];
        ?>
        <section class="af-settings-card af-settings-card-wide af-social-accounts-card" id="social-media">
            <h2>Social Media Accounts</h2>
            <p>Add and manage your social media profiles. These links display on the public website footer.</p>

            <div class="af-social-account-list">
                <?php foreach ($socialRows as $row): ?>
                    <?php $socialValue = (string) ($company[$row['key']] ?? ''); ?>
                    <?php $socialDisabled = (bool) ($row['disabled'] ?? false); ?>
                    <article class="af-social-account <?php echo $socialDisabled ? 'is-disabled' : ''; ?>" <?php echo $socialDisabled ? '' : 'data-social-row'; ?> data-social-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>" data-social-icon="<?php echo htmlspecialchars($row['icon'], ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="af-social-platform <?php echo htmlspecialchars($row['class'], ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi <?php echo htmlspecialchars($row['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                        </span>
                        <div class="af-social-meta">
                            <strong><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <small><?php echo htmlspecialchars($row['hint'], ENT_QUOTES, 'UTF-8'); ?></small>
                        </div>
                        <input id="<?php echo htmlspecialchars($row['key'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $socialDisabled ? '' : 'name="' . htmlspecialchars($row['key'], ENT_QUOTES, 'UTF-8') . '"'; ?> type="url" value="<?php echo htmlspecialchars($socialValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars($row['placeholder'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $socialDisabled ? 'disabled' : ''; ?>>
                        <label class="af-social-toggle" aria-label="Enable <?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="checkbox" data-social-enabled <?php echo $socialValue !== '' ? 'checked' : ''; ?> <?php echo $socialDisabled ? 'disabled' : ''; ?>>
                            <span class="af-switch" aria-hidden="true"></span>
                        </label>
                        <button type="button" class="af-social-clear" data-clear-input="<?php echo htmlspecialchars($row['key'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="Clear <?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $socialDisabled ? 'disabled' : ''; ?>>
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </article>
                <?php endforeach; ?>

                <button type="button" class="af-settings-add-line" disabled>
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    Add New Social Media
                </button>
            </div>
        </section>

        <section class="af-settings-card af-social-preview-card">
            <h2>Social Media Preview</h2>
            <p>This is how your social media links will appear on your website.</p>
            <div class="af-social-preview-box">
                <strong>Follow Us</strong>
                <small>Stay connected with us on social media for updates and offers.</small>
                <div data-social-preview>
                    <?php foreach (afrisense_public_social_links() as $social): ?>
                        <span title="<?php echo htmlspecialchars($social['label'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi <?php echo htmlspecialchars($social['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <h2>Display Settings</h2>
            <div class="af-settings-two">
                <label class="af-settings-field">
                    <span>Display Position</span>
                    <select disabled><option>Footer</option></select>
                </label>
                <label class="af-settings-field">
                    <span>Display Style</span>
                    <select disabled><option>Icon Only</option></select>
                </label>
                <label class="af-settings-field">
                    <span>Icon Shape</span>
                    <select disabled><option>Rounded</option></select>
                </label>
                <label class="af-settings-field">
                    <span>Icon Size</span>
                    <select disabled><option>Medium</option></select>
                </label>
            </div>
            <label class="af-toggle-row">
                <input type="checkbox" checked disabled>
                <span class="af-switch" aria-hidden="true"></span>
                <strong>Open links in new tab</strong>
                <small>Links will open in a new browser tab.</small>
            </label>
        </section>

        <section class="af-settings-card">
            <h2>Social Share Settings</h2>
            <p>Allow users to share your content on social media.</p>
            <label class="af-toggle-row"><input type="checkbox" checked disabled><span class="af-switch" aria-hidden="true"></span><strong>Enable Social Share Buttons</strong><small>Show social share buttons on public pages.</small></label>
            <label class="af-toggle-row"><input type="checkbox" checked disabled><span class="af-switch" aria-hidden="true"></span><strong>Share on Facebook</strong><small>Allow sharing on Facebook.</small></label>
            <label class="af-toggle-row"><input type="checkbox" disabled><span class="af-switch" aria-hidden="true"></span><strong>Share on Twitter (X)</strong><small>Allow sharing on Twitter (X).</small></label>
            <label class="af-toggle-row"><input type="checkbox" checked disabled><span class="af-switch" aria-hidden="true"></span><strong>Share on WhatsApp</strong><small>Allow sharing on WhatsApp.</small></label>
        </section>

        <section class="af-settings-card af-settings-card-wide">
            <h2>Custom Links</h2>
            <p>Add any additional social media or custom links.</p>
            <div class="af-custom-social-table">
                <div><strong>Platform Name</strong><strong>Icon Class / URL</strong><strong>Link URL</strong><strong>Status</strong><strong>Action</strong></div>
                <div>
                    <input type="text" value="WhatsApp" readonly>
                    <input type="text" value="bi bi-whatsapp" readonly>
                    <input type="url" value="<?php echo htmlspecialchars('https://wa.me/' . preg_replace('/\D+/', '', (string) ($company['phone_number_1'] ?? '+233241234567')), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    <span class="af-switch is-on" aria-hidden="true"></span>
                    <button type="button" class="af-social-clear" disabled><i class="bi bi-trash" aria-hidden="true"></i></button>
                </div>
            </div>
            <button type="button" class="af-settings-add-line" disabled>
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Add Custom Link
            </button>
        </section>
        <p class="af-settings-note af-settings-card-wide"><i class="bi bi-info-circle" aria-hidden="true"></i> Social media links are saved only when the current database has a matching URL column.</p>
        <?php endif; ?>

        <?php if ($activeSettingSection === 'payment'): ?>
        <section class="af-settings-card af-settings-card-wide af-payment-gateways-card" id="payment-settings">
            <div class="af-settings-section-heading">
                <div>
                    <h2>Payment Gateways</h2>
                    <p>Enable and manage payment gateways on your website.</p>
                </div>
                <button type="button" disabled><i class="bi bi-plus-lg" aria-hidden="true"></i> Add New Gateway</button>
            </div>

            <div class="af-payment-gateway-list">
                <article>
                    <span class="af-payment-logo paystack"><i class="bi bi-stack" aria-hidden="true"></i></span>
                    <div><strong>Paystack</strong><small>Accept card payments, Mobile Money and bank transfers.</small></div>
                    <em>Enabled</em>
                    <span class="af-switch is-on" aria-hidden="true"></span>
                    <button type="button" class="af-icon-action" disabled><i class="bi bi-gear" aria-hidden="true"></i></button>
                </article>
                <article>
                    <span class="af-payment-logo mtn">MTN</span>
                    <div><strong>MTN Mobile Money</strong><small>Accept payments via MTN Mobile Money.</small></div>
                    <em>Enabled</em>
                    <span class="af-switch is-on" aria-hidden="true"></span>
                    <button type="button" class="af-icon-action" disabled><i class="bi bi-gear" aria-hidden="true"></i></button>
                </article>
                <article>
                    <span class="af-payment-logo vodafone"><i class="bi bi-circle-fill" aria-hidden="true"></i></span>
                    <div><strong>Vodafone Cash</strong><small>Accept payments via Vodafone Cash.</small></div>
                    <em class="muted">Disabled</em>
                    <span class="af-switch" aria-hidden="true"></span>
                    <button type="button" class="af-icon-action" disabled><i class="bi bi-gear" aria-hidden="true"></i></button>
                </article>
                <article>
                    <span class="af-payment-logo flutterwave"><i class="bi bi-wind" aria-hidden="true"></i></span>
                    <div><strong>Flutterwave</strong><small>Accept international card payments and more.</small></div>
                    <em>Enabled</em>
                    <span class="af-switch is-on" aria-hidden="true"></span>
                    <button type="button" class="af-icon-action" disabled><i class="bi bi-gear" aria-hidden="true"></i></button>
                </article>
            </div>
        </section>

        <section class="af-settings-card af-payment-config-card">
            <h2>Gateway Configuration</h2>
            <p>Configure the selected payment gateway.</p>
            <label class="af-settings-field">
                <span>Select Gateway</span>
                <select disabled><option>Paystack</option><option>MTN Mobile Money</option><option>Flutterwave</option></select>
            </label>
            <label class="af-settings-field">
                <span>Public Key</span>
                <input type="text" value="pk_test_51Hq...examplePublicKey" readonly>
            </label>
            <label class="af-settings-field">
                <span>Secret Key</span>
                <input type="password" value="not-saved-in-current-schema" readonly>
            </label>
            <label class="af-settings-field">
                <span>Webhook Secret (Optional)</span>
                <input type="password" value="not-saved-in-current-schema" readonly>
            </label>
            <button type="button" class="af-test-connection-btn" disabled>
                <i class="bi bi-broadcast" aria-hidden="true"></i>
                Test Connection
            </button>
        </section>

        <section class="af-settings-card">
            <h2>Currency Settings</h2>
            <p>Manage the currency used on your website.</p>
            <label class="af-settings-field" for="payment_default_currency">
                <span>Default Currency</span>
                <select id="payment_default_currency" name="default_currency">
                    <?php $paymentCurrency = (string) ($system['default_currency'] ?? 'GHS'); ?>
                    <option value="GHS" <?php echo $paymentCurrency === 'GHS' ? 'selected' : ''; ?>>GHS (GHc) - Ghana Cedi</option>
                    <option value="USD" <?php echo $paymentCurrency === 'USD' ? 'selected' : ''; ?>>USD ($) - US Dollar</option>
                </select>
            </label>
            <div class="af-payment-radio-row">
                <span>Display Currency Position</span>
                <label><input type="radio" checked disabled> Left (GHc 100.00)</label>
                <label><input type="radio" disabled> Right (100.00 GHc)</label>
                <label><input type="radio" disabled> Left with space (GHc 100.00)</label>
            </div>
        </section>

        <section class="af-settings-card">
            <h2>Payment Settings</h2>
            <p>Configure how payments work on your website.</p>
            <label class="af-toggle-row"><input type="checkbox" checked disabled><span class="af-switch" aria-hidden="true"></span><strong>Enable guest checkout</strong><small>Allow customers to checkout without an account.</small></label>
            <label class="af-toggle-row"><input type="checkbox" checked disabled><span class="af-switch" aria-hidden="true"></span><strong>Auto confirm paid orders</strong><small>Automatically confirm orders after successful payment.</small></label>
            <label class="af-toggle-row">
                <input type="checkbox" name="email_notifications" value="1" <?php echo (int) ($system['email_notifications'] ?? 1) === 1 ? 'checked' : ''; ?>>
                <span class="af-switch" aria-hidden="true"></span>
                <strong>Send payment confirmation email</strong>
                <small>Send email to customer after successful payment.</small>
            </label>
            <label class="af-toggle-row"><input type="checkbox" disabled><span class="af-switch" aria-hidden="true"></span><strong>Enable test mode</strong><small>Use gateway in test/sandbox mode.</small></label>
            <input type="hidden" name="order_notifications" value="<?php echo (int) ($system['order_notifications'] ?? 1) === 1 ? '1' : '0'; ?>">
        </section>

        <section class="af-settings-card">
            <h2>Payment Instructions</h2>
            <p>Add instructions for customers during checkout.</p>
            <label class="af-settings-field">
                <span>Instruction Message</span>
                <textarea rows="5" readonly>You can make payments securely using any of the available payment methods. Your payment is protected with 256-bit SSL encryption.</textarea>
            </label>
        </section>

        <section class="af-settings-card af-settings-card-wide">
            <h2>Refund &amp; Cancellation Policy</h2>
            <p>Manage refund and cancellation settings.</p>
            <div class="af-settings-two">
                <label class="af-settings-field"><span>Refund Policy</span><select disabled><option>Allow refund within 7 days</option></select></label>
                <label class="af-settings-field"><span>Cancellation Policy</span><select disabled><option>Allow cancellation before delivery</option></select></label>
            </div>
            <label class="af-settings-field"><span>Refund Process Message</span><textarea rows="3" readonly>Refunds are processed within 3-5 working days to your original payment method.</textarea></label>
        </section>

        <section class="af-settings-card">
            <h2>Recent Transactions</h2>
            <p>Use the Orders page for live payment records in the current schema.</p>
            <div class="af-recent-transactions">
                <article><span>Paid Orders</span><strong>Orders table</strong><em>Active</em></article>
                <article><span>Pending Payments</span><strong>Orders table</strong><em class="pending">Review</em></article>
                <article><span>Failed Payments</span><strong>No gateway logs</strong><em class="muted">N/A</em></article>
            </div>
        </section>
        <p class="af-settings-note af-settings-card-wide"><i class="bi bi-info-circle" aria-hidden="true"></i> Gateway API keys, transaction limits and gateway logs need dedicated database columns before they can be saved securely.</p>
        <?php endif; ?>

        <?php if ($activeSettingSection === 'delivery'): ?>
        <section class="af-settings-card af-settings-card-wide" id="delivery-settings">
            <h2>Delivery Settings</h2>
            <p>Review the delivery rules currently used by checkout. Persistent delivery configuration needs database columns before it can be saved.</p>

            <div class="af-delivery-settings-layout">
                <div class="af-delivery-zones">
                    <div class="af-settings-section-heading">
                        <h3>Delivery Zones</h3>
                        <button type="button" disabled><i class="bi bi-plus-lg" aria-hidden="true"></i> Add Zone</button>
                    </div>
                    <div class="af-delivery-zone-table">
                        <div><strong>Zone Name</strong><strong>Areas / Locations</strong><strong>Delivery Fee</strong><strong>Status</strong></div>
                        <div><span>Accra Central<small>Primary zone</small></span><span>Osu, Airport, Labone, East Legon</span><span><?php echo htmlspecialchars(afrisense_public_money(10.00), ENT_QUOTES, 'UTF-8'); ?></span><em>Active</em></div>
                        <div><span>Accra Surrounding<small>Secondary zone</small></span><span>Madina, Adenta, Achimota, Dansoman</span><span><?php echo htmlspecialchars(afrisense_public_money(15.00), ENT_QUOTES, 'UTF-8'); ?></span><em>Active</em></div>
                        <div><span>Greater Accra<small>Extended zone</small></span><span>Tema, Prampram, Kasoa, Amasaman</span><span><?php echo htmlspecialchars(afrisense_public_money(25.00), ENT_QUOTES, 'UTF-8'); ?></span><em class="muted">Inactive</em></div>
                    </div>
                </div>

                <div class="af-delivery-options">
                    <h3>Delivery Options</h3>
                    <label class="af-toggle-row">
                        <input type="checkbox" checked disabled>
                        <span class="af-switch" aria-hidden="true"></span>
                        <strong>Standard Delivery</strong>
                        <small>Regular delivery within estimated time.</small>
                    </label>
                    <label class="af-toggle-row">
                        <input type="checkbox" checked disabled>
                        <span class="af-switch" aria-hidden="true"></span>
                        <strong>Express Delivery</strong>
                        <small>Faster delivery in a shorter time.</small>
                    </label>
                    <label class="af-toggle-row">
                        <input type="checkbox" checked disabled>
                        <span class="af-switch" aria-hidden="true"></span>
                        <strong>Scheduled Delivery</strong>
                        <small>Allow customers to schedule delivery.</small>
                    </label>
                    <label class="af-toggle-row">
                        <input type="checkbox" checked disabled>
                        <span class="af-switch" aria-hidden="true"></span>
                        <strong>Pickup / Self Collection</strong>
                        <small>Allow customers to pick up their orders.</small>
                    </label>
                </div>
            </div>

            <div class="af-settings-two">
                <section class="af-delivery-mini-card">
                    <h3>Delivery Settings</h3>
                    <div class="af-settings-two">
                        <label class="af-settings-field">
                            <span>Default Delivery Time</span>
                            <input type="text" value="30 - 45 Minutes" readonly>
                        </label>
                        <label class="af-settings-field">
                            <span>Maximum Delivery Time</span>
                            <input type="text" value="90 Minutes" readonly>
                        </label>
                    </div>
                    <label class="af-settings-field">
                        <span>Order Cut-off Time</span>
                        <input type="text" value="10:00 PM" readonly>
                    </label>
                </section>

                <section class="af-delivery-mini-card">
                    <h3>Delivery Instructions</h3>
                    <textarea readonly rows="5">Please ensure someone is available to receive the order at the delivery address. We will contact you when we are on our way.</textarea>
                </section>
            </div>

            <p class="af-settings-note"><i class="bi bi-info-circle" aria-hidden="true"></i> Delivery fee is currently hardcoded in checkout as <?php echo htmlspecialchars(afrisense_public_money(10.00), ENT_QUOTES, 'UTF-8'); ?> because the existing schema has no delivery settings storage.</p>
        </section>
        <?php endif; ?>

        <?php if ($activeSettingSection === 'seo'): ?>
        <section class="af-settings-card af-settings-card-wide" id="seo-settings">
            <h2>SEO &amp; Analytics</h2>
            <p>Manage the SEO values currently supported by the existing website settings schema.</p>

            <div class="af-seo-settings-layout">
                <div class="af-seo-form-panel">
                    <h3>SEO Settings</h3>
                    <div class="af-settings-two">
                        <label class="af-settings-field" for="seo_site_title">
                            <span>Site Title</span>
                            <input id="seo_site_title" type="text" value="<?php echo htmlspecialchars((string) ($website['site_name'] ?? 'AfriSense Food Services'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            <small>Uses Website Name from General Settings.</small>
                        </label>

                        <label class="af-settings-field" for="seo_meta_description">
                            <span>Meta Description</span>
                            <textarea id="seo_meta_description" rows="3" readonly><?php echo htmlspecialchars((string) ($website['hero_subtitle'] ?? $website['site_tagline'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <small>Uses Homepage Hero Subtitle from General Settings.</small>
                        </label>
                    </div>

                    <div class="af-settings-two">
                        <label class="af-settings-field" for="seo_canonical">
                            <span>Canonical URL</span>
                            <input id="seo_canonical" type="text" value="/Afrisense/frontend/landing/index.php" readonly>
                        </label>

                        <label class="af-settings-field" for="seo_theme_color">
                            <span>Theme Color</span>
                            <input id="seo_theme_color" type="text" value="<?php echo htmlspecialchars((string) ($website['primary_color'] ?? '#b77b1a'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                        </label>
                    </div>

                    <label class="af-settings-field" for="seo_head_preview">
                        <span>Current Homepage Head Output</span>
                        <textarea id="seo_head_preview" rows="5" readonly><?php echo htmlspecialchars('<title>' . (string) ($website['site_name'] ?? 'AfriSense Food Services') . '</title>' . "\n" . '<meta name="description" content="' . (string) ($website['hero_subtitle'] ?? $website['site_tagline'] ?? '') . '">' . "\n" . '<meta name="theme-color" content="' . (string) ($website['primary_color'] ?? '#b77b1a') . '">', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </label>
                </div>

                <aside class="af-seo-analytics-panel">
                    <h3>Analytics Overview</h3>
                    <p>Live analytics storage is not present in the current database schema.</p>
                    <div class="af-seo-stats">
                        <article><i class="bi bi-people"></i><strong>0</strong><span>Total Visitors</span></article>
                        <article><i class="bi bi-eye"></i><strong>0</strong><span>Page Views</span></article>
                        <article><i class="bi bi-activity"></i><strong>N/A</strong><span>Bounce Rate</span></article>
                        <article><i class="bi bi-clock"></i><strong>N/A</strong><span>Avg. Session</span></article>
                    </div>
                    <div class="af-seo-chart-placeholder">
                        <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                        <span>Add analytics storage or a Google Analytics integration to populate this chart.</span>
                    </div>
                </aside>
            </div>

            <p class="af-settings-note"><i class="bi bi-info-circle" aria-hidden="true"></i> The database has no dedicated SEO columns, so this section maps to existing website fields instead of creating unsupported settings.</p>
        </section>
        <?php endif; ?>

        <?php if ($activeSettingSection === 'email'): ?>
        <section class="af-settings-card" id="email-settings">
            <h2>Email Settings</h2>
            <p>Configure SMTP values used by system emails and verification messages.</p>

            <label class="af-settings-field" for="smtp_host">
                <span>SMTP Host</span>
                <input id="smtp_host" name="smtp_host" type="text" value="<?php echo htmlspecialchars((string) ($system['smtp_host'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label class="af-settings-field" for="smtp_port">
                <span>SMTP Port</span>
                <input id="smtp_port" name="smtp_port" type="number" min="1" max="65535" value="<?php echo htmlspecialchars((string) ($system['smtp_port'] ?? '587'), ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label class="af-settings-field" for="smtp_username">
                <span>SMTP Username</span>
                <input id="smtp_username" name="smtp_username" type="text" value="<?php echo htmlspecialchars((string) ($system['smtp_username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label class="af-settings-field" for="smtp_password">
                <span>SMTP Password</span>
                <input id="smtp_password" name="smtp_password" type="password" value="<?php echo htmlspecialchars((string) ($system['smtp_password'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label class="af-settings-field" for="smtp_encryption">
                <span>SMTP Encryption</span>
                <?php $smtpEncryption = (string) ($system['smtp_encryption'] ?? 'tls'); ?>
                <select id="smtp_encryption" name="smtp_encryption">
                    <option value="tls" <?php echo $smtpEncryption === 'tls' ? 'selected' : ''; ?>>TLS</option>
                    <option value="ssl" <?php echo $smtpEncryption === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                    <option value="none" <?php echo $smtpEncryption === 'none' ? 'selected' : ''; ?>>None</option>
                </select>
            </label>
        </section>
        <?php endif; ?>

        <?php if ($activeSettingSection === 'maintenance'): ?>
        <section class="af-settings-card" id="maintenance-mode">
            <h2>Maintenance Mode</h2>
            <p>Control high-level public website availability and admin listing defaults.</p>

            <label class="af-settings-field" for="timezone">
                <span>Timezone</span>
                <?php $timezone = (string) ($system['timezone'] ?? 'Africa/Accra'); ?>
                <select id="timezone" name="timezone">
                    <option value="Africa/Accra" <?php echo $timezone === 'Africa/Accra' ? 'selected' : ''; ?>>(GMT+00:00) Accra, Ghana</option>
                    <option value="UTC" <?php echo $timezone === 'UTC' ? 'selected' : ''; ?>>(GMT+00:00) UTC</option>
                </select>
            </label>

            <label class="af-toggle-row">
                <input type="checkbox" name="booking_notifications" value="1" <?php echo (int) ($system['booking_notifications'] ?? 1) === 1 ? 'checked' : ''; ?>>
                <span class="af-switch" aria-hidden="true"></span>
                <strong>Booking Notifications</strong>
                <small>Notify administrators when customers book services.</small>
            </label>

            <label class="af-toggle-row">
                <input type="checkbox" name="maintenance_mode" value="1" <?php echo (string) ($system['site_status'] ?? 'Online') === 'Maintenance' ? 'checked' : ''; ?>>
                <span class="af-switch" aria-hidden="true"></span>
                <strong>Maintenance Mode</strong>
                <small>Temporarily pause public website activity.</small>
            </label>

            <label class="af-settings-field" for="items_per_page">
                <span>Admin Items Per Page</span>
                <input id="items_per_page" name="items_per_page" type="number" min="5" max="100" value="<?php echo htmlspecialchars((string) ($system['items_per_page'] ?? '10'), ENT_QUOTES, 'UTF-8'); ?>">
            </label>
        </section>
        <?php endif; ?>

        <?php if ($activeSettingSection === 'general'): ?>
        <section class="af-settings-card">
            <h2>Footer Text</h2>
            <p>This text will be displayed in the website footer.</p>
            <label class="af-settings-field" for="footer_text">
                <span>Footer Text</span>
                <textarea id="footer_text" name="footer_text" rows="6"><?php echo htmlspecialchars((string) ($website['footer_text'] ?? '(c) 2026 AfriSense Food Services. All rights reserved.'), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
        </section>
        <?php endif; ?>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
