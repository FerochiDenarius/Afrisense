<?php
require_once __DIR__ . '/../includes/public_settings.php';

$adminTitle = $adminTitle ?? 'Dashboard';
$adminName = $adminName ?? 'Admin User';
$adminRole = $adminRole ?? 'Super Admin';
$frontendBase = $frontendBase ?? '/Afrisense/frontend';
$publicSettings = afrisense_public_settings();
$adminSiteName = (string) ($publicSettings['website']['site_name'] ?? 'AfriSense Food Services');
$adminBrandName = str_replace(' Food Services', '', $adminSiteName);
$adminSiteTagline = (string) ($publicSettings['website']['site_tagline'] ?? 'Food Services');
$adminUnreadNotifications = 0;
$adminUnreadEnquiries = 0;

try {
    if (isset($authUser['id'])) {
        $notificationStatement = afrisense_pdo()->prepare(
            'SELECT COUNT(*) AS count_value
             FROM `notifications`
             WHERE `user_id` = :user_id AND `is_read` = 0'
        );
        $notificationStatement->execute(['user_id' => (int) $authUser['id']]);
        $adminUnreadNotifications = (int) ($notificationStatement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0);
    }

    $enquiryStatement = afrisense_pdo()->prepare(
        'SELECT COUNT(*) AS count_value
         FROM `enquiries`
         WHERE `status` = :status'
    );
    $enquiryStatement->execute(['status' => 'Pending']);
    $adminUnreadEnquiries = (int) ($enquiryStatement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0);
} catch (Throwable $exception) {
    $adminUnreadNotifications = 0;
    $adminUnreadEnquiries = 0;
}
?>
<header class="af-dashboard-header af-admin-header">
    <a class="af-header-brand" href="<?php echo htmlspecialchars($frontendBase . '/admin/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="AfriSense admin dashboard">
        <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
        <span>
            <strong><?php echo htmlspecialchars($adminBrandName, ENT_QUOTES, 'UTF-8'); ?></strong>
            <small><?php echo htmlspecialchars($adminSiteTagline, ENT_QUOTES, 'UTF-8'); ?></small>
        </span>
    </a>

    <button class="af-sidebar-toggle" type="button" aria-label="Toggle sidebar" aria-expanded="false" data-sidebar-toggle>
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    <strong class="af-header-title"><?php echo htmlspecialchars($adminTitle, ENT_QUOTES, 'UTF-8'); ?></strong>

    <label class="af-header-search" for="admin_global_search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="admin_global_search" name="admin_global_search" placeholder="Search anything...">
        <kbd>Ctrl + /</kbd>
    </label>

    <div class="af-header-actions">
        <a class="af-header-action" href="<?php echo htmlspecialchars($frontendBase . '/admin/notifications.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Notifications">
            <span class="af-action-icon">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <?php if ($adminUnreadNotifications > 0): ?><em><?php echo htmlspecialchars((string) min(99, $adminUnreadNotifications), ENT_QUOTES, 'UTF-8'); ?></em><?php endif; ?>
            </span>
            <small>Notifications</small>
        </a>
        <a class="af-header-action" href="<?php echo htmlspecialchars($frontendBase . '/admin/enquiries.php?status=Pending', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Unread enquiries">
            <span class="af-action-icon">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <?php if ($adminUnreadEnquiries > 0): ?><em class="is-green"><?php echo htmlspecialchars((string) min(99, $adminUnreadEnquiries), ENT_QUOTES, 'UTF-8'); ?></em><?php endif; ?>
            </span>
            <small>Enquiries</small>
        </a>
        <button class="af-header-action" type="button" aria-label="Fullscreen">
            <span class="af-action-icon">
                <i class="bi bi-fullscreen" aria-hidden="true"></i>
            </span>
            <small>Fullscreen</small>
        </button>
        <button class="af-profile-menu" type="button" aria-label="Profile menu">
            <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="">
            <span>
                <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></strong>
                <small><?php echo htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8'); ?></small>
            </span>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </button>
    </div>
</header>
