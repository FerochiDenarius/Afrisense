<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Website Settings | AfriSense';
$adminTitle = 'Website Settings';
$activeAdminPage = 'settings';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

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
            'INSERT INTO `website_settings` (`site_name`, `site_tagline`, `footer_text`)
             VALUES (:site_name, :site_tagline, :footer_text)'
        );
        $statement->execute([
            'site_name' => 'AfriSense Food Services',
            'site_tagline' => 'Delicious meals, delivered with love.',
            'footer_text' => '(c) 2026 AfriSense Food Services. All rights reserved.',
        ]);
    }

    if (afrisense_fetch_first_row($pdo, 'company_information') === []) {
        $statement = $pdo->prepare(
            'INSERT INTO `company_information`
                (`company_name`, `company_email`, `phone_number_1`, `address`, `business_hours`)
             VALUES
                (:company_name, :company_email, :phone_number_1, :address, :business_hours)'
        );
        $statement->execute([
            'company_name' => 'AfriSense Food Services',
            'company_email' => 'info@afrisense.com',
            'phone_number_1' => '+233 24 123 4567',
            'address' => 'East Legon, Accra, Ghana',
            'business_hours' => "Monday - Friday: 8:00 AM - 10:00 PM\nSaturday: 9:00 AM - 11:00 PM\nSunday: 10:00 AM - 9:00 PM",
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

$flashMessage = '';
$flashType = 'success';

try {
    $pdo = afrisense_pdo();
    afrisense_ensure_settings_rows($pdo);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $siteStatus = ($_POST['maintenance_mode'] ?? '') === '1' ? 'Maintenance' : 'Online';
        $emailNotifications = ($_POST['email_notifications'] ?? '') === '1' ? 1 : 0;
        $bookingNotifications = ($_POST['booking_notifications'] ?? '') === '1' ? 1 : 0;

        $pdo->beginTransaction();

        $statement = $pdo->prepare(
            'UPDATE `website_settings`
             SET `site_name` = :site_name,
                 `site_tagline` = :site_tagline,
                 `footer_text` = :footer_text,
                 `updated_at` = NOW()
             ORDER BY `id` ASC
             LIMIT 1'
        );
        $statement->execute([
            'site_name' => afrisense_post_value($_POST, 'site_name', 'AfriSense Food Services'),
            'site_tagline' => afrisense_post_value($_POST, 'site_tagline'),
            'footer_text' => afrisense_post_value($_POST, 'footer_text'),
        ]);

        $statement = $pdo->prepare(
            'UPDATE `company_information`
             SET `company_name` = :company_name,
                 `company_email` = :company_email,
                 `phone_number_1` = :phone_number_1,
                 `phone_number_2` = :phone_number_2,
                 `address` = :address,
                 `business_hours` = :business_hours,
                 `updated_at` = NOW()
             ORDER BY `id` ASC
             LIMIT 1'
        );
        $statement->execute([
            'company_name' => afrisense_post_value($_POST, 'company_name', 'AfriSense Food Services'),
            'company_email' => afrisense_post_value($_POST, 'company_email', 'info@afrisense.com'),
            'phone_number_1' => afrisense_post_value($_POST, 'phone_number_1', '+233 24 123 4567'),
            'phone_number_2' => afrisense_post_value($_POST, 'phone_number_2'),
            'address' => afrisense_post_value($_POST, 'address', 'Accra, Ghana'),
            'business_hours' => afrisense_post_value($_POST, 'business_hours'),
        ]);

        $statement = $pdo->prepare(
            'UPDATE `system_settings`
             SET `site_status` = :site_status,
                 `default_currency` = :default_currency,
                 `timezone` = :timezone,
                 `email_notifications` = :email_notifications,
                 `booking_notifications` = :booking_notifications,
                 `updated_at` = NOW()
             ORDER BY `id` ASC
             LIMIT 1'
        );
        $statement->execute([
            'site_status' => $siteStatus,
            'default_currency' => afrisense_post_value($_POST, 'default_currency', 'GHS'),
            'timezone' => afrisense_post_value($_POST, 'timezone', 'Africa/Accra'),
            'email_notifications' => $emailNotifications,
            'booking_notifications' => $bookingNotifications,
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
            <p>Dashboard / Website Settings</p>
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
        <a class="active" href="#"><i class="bi bi-gear" aria-hidden="true"></i> General Settings</a>
        <a href="#"><i class="bi bi-buildings" aria-hidden="true"></i> Company Information</a>
        <a href="#"><i class="bi bi-share" aria-hidden="true"></i> Social Media</a>
        <a href="#"><i class="bi bi-credit-card" aria-hidden="true"></i> Payment Settings</a>
        <a href="#"><i class="bi bi-truck" aria-hidden="true"></i> Delivery Settings</a>
        <a href="#"><i class="bi bi-envelope" aria-hidden="true"></i> Email Settings</a>
        <a href="#"><i class="bi bi-tools" aria-hidden="true"></i> Maintenance Mode</a>
    </nav>

    <form id="website_settings_form" class="af-settings-grid" action="" method="post">
        <section class="af-settings-card">
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

            <label class="af-settings-field" for="default_currency">
                <span>Default Currency</span>
                <select id="default_currency" name="default_currency">
                    <?php $currency = (string) ($system['default_currency'] ?? 'GHS'); ?>
                    <option value="GHS" <?php echo $currency === 'GHS' ? 'selected' : ''; ?>>GHS (GHc) - Ghana Cedi</option>
                    <option value="USD" <?php echo $currency === 'USD' ? 'selected' : ''; ?>>USD ($) - US Dollar</option>
                </select>
            </label>

            <label class="af-settings-field" for="timezone">
                <span>Timezone</span>
                <?php $timezone = (string) ($system['timezone'] ?? 'Africa/Accra'); ?>
                <select id="timezone" name="timezone">
                    <option value="Africa/Accra" <?php echo $timezone === 'Africa/Accra' ? 'selected' : ''; ?>>(GMT+00:00) Accra, Ghana</option>
                    <option value="UTC" <?php echo $timezone === 'UTC' ? 'selected' : ''; ?>>(GMT+00:00) UTC</option>
                </select>
            </label>

            <label class="af-toggle-row">
                <input type="checkbox" name="email_notifications" value="1" <?php echo (int) ($system['email_notifications'] ?? 1) === 1 ? 'checked' : ''; ?>>
                <span class="af-switch" aria-hidden="true"></span>
                <strong>Email Notifications</strong>
                <small>Send operational email updates.</small>
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
        </section>

        <section class="af-settings-card">
            <h2>Website Logo & Favicon</h2>
            <p>Upload your logo and favicon for the website.</p>
            <div class="af-logo-preview">
                <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
                <strong>Afri<span>Sense</span></strong>
                <small>Food Services</small>
            </div>
            <div class="af-settings-actions">
                <button type="button"><i class="bi bi-upload" aria-hidden="true"></i> Change Logo</button>
                <button class="danger" type="button"><i class="bi bi-trash" aria-hidden="true"></i> Remove</button>
            </div>
            <div class="af-favicon-preview">
                <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
            </div>
            <div class="af-settings-actions">
                <button type="button"><i class="bi bi-upload" aria-hidden="true"></i> Change Favicon</button>
                <button class="danger" type="button"><i class="bi bi-trash" aria-hidden="true"></i> Remove</button>
            </div>
        </section>

        <section class="af-settings-card">
            <h2>Contact Information</h2>
            <p>This information will be displayed on the website.</p>

            <label class="af-settings-field" for="company_name">
                <span>Company Name</span>
                <input id="company_name" name="company_name" type="text" value="<?php echo htmlspecialchars((string) ($company['company_name'] ?? 'AfriSense Food Services'), ENT_QUOTES, 'UTF-8'); ?>" required>
            </label>

            <label class="af-settings-field" for="phone_number_1">
                <span>Phone Number</span>
                <input id="phone_number_1" name="phone_number_1" type="tel" value="<?php echo htmlspecialchars((string) ($company['phone_number_1'] ?? '+233 24 123 4567'), ENT_QUOTES, 'UTF-8'); ?>" required>
            </label>

            <label class="af-settings-field" for="phone_number_2">
                <span>Alternative Phone</span>
                <input id="phone_number_2" name="phone_number_2" type="tel" value="<?php echo htmlspecialchars((string) ($company['phone_number_2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label class="af-settings-field" for="company_email">
                <span>Email Address</span>
                <input id="company_email" name="company_email" type="email" value="<?php echo htmlspecialchars((string) ($company['company_email'] ?? 'info@afrisense.com'), ENT_QUOTES, 'UTF-8'); ?>" required>
            </label>

            <label class="af-settings-field" for="address">
                <span>Address</span>
                <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars((string) ($company['address'] ?? 'East Legon, Accra, Ghana'), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>

            <label class="af-settings-field" for="business_hours">
                <span>Business Hours</span>
                <textarea id="business_hours" name="business_hours" rows="4"><?php echo htmlspecialchars((string) ($company['business_hours'] ?? "Monday - Friday: 8:00 AM - 10:00 PM\nSaturday: 9:00 AM - 11:00 PM\nSunday: 10:00 AM - 9:00 PM"), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
        </section>

        <section class="af-settings-card">
            <h2>Website Theme</h2>
            <p>Select your preferred theme for the website.</p>
            <div class="af-theme-grid">
                <label>
                    <input type="radio" name="theme" value="light" checked>
                    <span><i></i><strong>Light</strong></span>
                </label>
                <label>
                    <input type="radio" name="theme" value="dark">
                    <span><i></i><strong>Dark</strong></span>
                </label>
                <label>
                    <input type="radio" name="theme" value="auto">
                    <span><i></i><strong>Auto</strong></span>
                </label>
            </div>
        </section>

        <section class="af-settings-card">
            <h2>Footer Text</h2>
            <p>This text will be displayed in the website footer.</p>
            <label class="af-settings-field" for="footer_text">
                <span>Footer Text</span>
                <textarea id="footer_text" name="footer_text" rows="6"><?php echo htmlspecialchars((string) ($website['footer_text'] ?? '(c) 2026 AfriSense Food Services. All rights reserved.'), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
        </section>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
