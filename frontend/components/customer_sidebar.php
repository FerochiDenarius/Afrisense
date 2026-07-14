<?php
$frontendBase = $frontendBase ?? '/Afrisense/frontend';
$activeCustomerPage = $activeCustomerPage ?? '';

$customerItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-house', 'href' => $frontendBase . '/customer/dashboard.php'],
    ['key' => 'orders', 'label' => 'My Orders', 'icon' => 'bi-bag-check', 'href' => $frontendBase . '/customer/my-orders.php'],
    ['key' => 'bookings', 'label' => 'My Bookings', 'icon' => 'bi-calendar-check', 'href' => $frontendBase . '/customer/my-bookings.php'],
    ['key' => 'wishlist', 'label' => 'Wishlist', 'icon' => 'bi-heart', 'href' => '#'],
    ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'bi-bell', 'href' => $frontendBase . '/customer/notifications.php'],
    ['key' => 'profile', 'label' => 'Profile', 'icon' => 'bi-person', 'href' => $frontendBase . '/customer/profile.php'],
    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'bi-gear', 'href' => '#'],
];
?>
<aside class="af-dashboard-sidebar af-customer-sidebar" data-sidebar>
    <a class="af-brand" href="<?php echo htmlspecialchars($frontendBase . '/customer/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="AfriSense customer dashboard">
        <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
        <span>
            <strong>AfriSense</strong>
            <small>Food Services</small>
        </span>
    </a>

    <nav class="af-side-nav" aria-label="Customer navigation">
        <p>Account</p>
        <?php foreach ($customerItems as $item): ?>
            <a class="<?php echo $activeCustomerPage === $item['key'] ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <a class="af-sidebar-logout" href="<?php echo htmlspecialchars($frontendBase . '/auth/login.php', ENT_QUOTES, 'UTF-8'); ?>">
        <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
        <span>Logout</span>
    </a>
</aside>
