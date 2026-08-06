<?php
require_once __DIR__ . '/../includes/public_settings.php';

$customerTitle = $customerTitle ?? 'Dashboard';
$customerName = $customerName ?? 'Customer';
$frontendBase = $frontendBase ?? '/Afrisense/frontend';
$publicSettings = afrisense_public_settings();
$customerSiteName = (string) ($publicSettings['website']['site_name'] ?? 'AfriSense Food Services');
$customerBrandName = str_replace(' Food Services', '', $customerSiteName);
$customerSiteTagline = (string) ($publicSettings['website']['site_tagline'] ?? 'Food Services');
$customerUnreadNotifications = 0;
$customerCartCount = 0;

// Run database/action work inside a guarded block so the page can fail gracefully.
try {
    // Guard this block so it only runs when the required condition is met.
    if (isset($authUser['id'])) {
        $notificationStatement = afrisense_pdo()->prepare(
            'SELECT COUNT(*) AS count_value
             FROM `notifications`
             WHERE `user_id` = :user_id AND `is_read` = 0'
        );
        $notificationStatement->execute(['user_id' => (int) $authUser['id']]);
        $customerUnreadNotifications = (int) ($notificationStatement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0);
    }
} catch (Throwable $exception) {
    $customerUnreadNotifications = 0;
}

$customerCart = $_SESSION['afrisense_customer_cart'] ?? [];
$customerCartCount = is_array($customerCart) ? array_sum(array_map('intval', $customerCart)) : 0;
?>
<!-- Header block for this interface section. -->
<header class="af-dashboard-header af-customer-header">
    <a class="af-header-brand" href="<?php echo htmlspecialchars($frontendBase . '/customer/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="AfriSense customer dashboard">
        <span class="af-brand-icon" aria-hidden="true"><?php echo afrisense_public_brand_icon_html($frontendBase); ?></span>
        <span>
            <strong><?php echo htmlspecialchars($customerBrandName, ENT_QUOTES, 'UTF-8'); ?></strong>
            <small><?php echo htmlspecialchars($customerSiteTagline, ENT_QUOTES, 'UTF-8'); ?></small>
        </span>
    </a>

    <button class="af-sidebar-toggle" type="button" aria-label="Toggle sidebar" aria-expanded="false" data-sidebar-toggle>
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    <strong class="af-header-title"><?php echo htmlspecialchars($customerTitle, ENT_QUOTES, 'UTF-8'); ?></strong>

    <label class="af-header-search" for="customer_global_search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="customer_global_search" name="customer_global_search" placeholder="Search menu, orders, services...">
        <kbd>Ctrl + /</kbd>
    </label>

    <div class="af-header-actions">
        <a class="af-header-action" href="<?php echo htmlspecialchars($frontendBase . '/customer/notifications.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Notifications">
            <span class="af-action-icon">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <?php // Render this conditional/dynamic template block. ?>
                <?php if ($customerUnreadNotifications > 0): ?><em><?php echo htmlspecialchars((string) min(99, $customerUnreadNotifications), ENT_QUOTES, 'UTF-8'); ?></em><?php endif; ?>
            </span>
            <small>Notifications</small>
        </a>
        <button class="af-header-action" type="button" aria-label="Wishlist">
            <span class="af-action-icon">
                <i class="bi bi-heart" aria-hidden="true"></i>
                <em class="is-gold">2</em>
            </span>
            <small>Wishlist</small>
        </button>
        <a class="af-header-action" href="<?php echo htmlspecialchars($frontendBase . '/customer/cart.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Cart">
            <span class="af-action-icon">
                <i class="bi bi-cart3" aria-hidden="true"></i>
                <?php // Render this conditional/dynamic template block. ?>
                <?php if ($customerCartCount > 0): ?><em class="is-green"><?php echo htmlspecialchars((string) min(99, $customerCartCount), ENT_QUOTES, 'UTF-8'); ?></em><?php endif; ?>
            </span>
            <small>Cart</small>
        </a>
        <button class="af-profile-menu" type="button" aria-label="Profile menu">
            <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="">
            <span>
                <strong><?php echo htmlspecialchars($customerName !== '' ? $customerName : 'Customer', ENT_QUOTES, 'UTF-8'); ?></strong>
                <small>Customer</small>
            </span>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </button>
    </div>
</header>
