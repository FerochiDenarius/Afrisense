<?php
require_once __DIR__ . '/../includes/public_settings.php';

$frontendBase = $frontendBase ?? '/Afrisense/frontend';
$activeCustomerPage = $activeCustomerPage ?? '';
$publicSettings = afrisense_public_settings();
$customerSiteName = (string) ($publicSettings['website']['site_name'] ?? 'AfriSense Food Services');
$customerBrandName = str_replace(' Food Services', '', $customerSiteName);
$customerSiteTagline = (string) ($publicSettings['website']['site_tagline'] ?? 'Food Services');

$customerItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-house', 'href' => $frontendBase . '/customer/dashboard.php'],
    ['key' => 'place_order', 'label' => 'Place Order', 'icon' => 'bi-bag-plus', 'href' => $frontendBase . '/customer/orders.php'],
    ['key' => 'orders', 'label' => 'My Orders', 'icon' => 'bi-bag-check', 'href' => $frontendBase . '/customer/my-orders.php'],
    ['key' => 'bookings', 'label' => 'My Bookings', 'icon' => 'bi-calendar-check', 'href' => $frontendBase . '/customer/my-bookings.php'],
    ['key' => 'enquiries', 'label' => 'My Enquiries', 'icon' => 'bi-chat-square-text', 'href' => $frontendBase . '/customer/enquiries.php'],
    ['key' => 'remarks', 'label' => 'My Remarks', 'icon' => 'bi-chat-square-quote', 'href' => $frontendBase . '/customer/remarks.php'],
    ['key' => 'cart', 'label' => 'Cart', 'icon' => 'bi-cart3', 'href' => $frontendBase . '/customer/cart.php'],
    ['key' => 'wishlist', 'label' => 'Wishlist', 'icon' => 'bi-heart', 'href' => $frontendBase . '/landing/menu.php'],
    ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'bi-bell', 'href' => $frontendBase . '/customer/notifications.php'],
    ['key' => 'support', 'label' => 'Support Agent', 'icon' => 'bi-headset', 'href' => $frontendBase . '/customer/support.php'],
    ['key' => 'profile', 'label' => 'Profile', 'icon' => 'bi-person', 'href' => $frontendBase . '/customer/profile.php'],
    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'bi-gear', 'href' => $frontendBase . '/customer/settings.php'],
];
?>
<!-- Side panel with supporting information and actions. -->
<aside class="af-dashboard-sidebar af-customer-sidebar" data-sidebar>
    <a class="af-brand" href="<?php echo htmlspecialchars($frontendBase . '/customer/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="AfriSense customer dashboard">
        <span class="af-brand-icon" aria-hidden="true"><?php echo afrisense_public_brand_icon_html($frontendBase); ?></span>
        <span>
            <strong><?php echo htmlspecialchars($customerBrandName, ENT_QUOTES, 'UTF-8'); ?></strong>
            <small><?php echo htmlspecialchars($customerSiteTagline, ENT_QUOTES, 'UTF-8'); ?></small>
        </span>
    </a>

    <!-- Navigation links for this interface. -->
    <nav class="af-side-nav" aria-label="Customer navigation">
        <p>Account</p>
        <?php // Render this conditional/dynamic template block. ?>
        <?php foreach ($customerItems as $item): ?>
            <a class="<?php echo $activeCustomerPage === $item['key'] ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <a class="af-sidebar-logout" href="<?php echo htmlspecialchars($frontendBase . '/auth/logout.php', ENT_QUOTES, 'UTF-8'); ?>">
        <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
        <span>Logout</span>
    </a>
</aside>
