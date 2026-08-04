<?php
require_once __DIR__ . '/../includes/public_settings.php';

$frontendBase = $frontendBase ?? '/Afrisense/frontend';
$activeAdminPage = $activeAdminPage ?? '';
$adminName = $adminName ?? 'Admin User';
$adminRole = $adminRole ?? 'Super Admin';
$publicSettings = afrisense_public_settings();
$adminSiteName = (string) ($publicSettings['website']['site_name'] ?? 'AfriSense Food Services');
$adminBrandName = str_replace(' Food Services', '', $adminSiteName);
$adminSiteTagline = (string) ($publicSettings['website']['site_tagline'] ?? 'Food Services');
$adminOrderNavCounts = is_array($adminOrderNavCounts ?? null) ? $adminOrderNavCounts : [];

$adminGroups = [
    [
        'label' => '',
        'items' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-house-fill', 'href' => $frontendBase . '/admin/dashboard.php'],
        ],
    ],
    [
        'label' => 'Management',
        'items' => [
            [
                'key' => 'orders',
                'label' => 'Orders',
                'icon' => 'bi-cart3',
                'href' => $frontendBase . '/admin/orders.php',
                'expandable' => true,
                'children' => [
                    ['key' => 'orders_all', 'label' => 'All Orders', 'href' => $frontendBase . '/admin/orders.php'],
                    ['key' => 'orders_pending', 'label' => 'Pending Orders', 'href' => $frontendBase . '/admin/orders.php?status=Pending', 'badge_class' => 'pending'],
                    ['key' => 'orders_confirmed', 'label' => 'Confirmed Orders', 'href' => $frontendBase . '/admin/orders.php?status=Confirmed', 'badge_class' => 'confirmed'],
                    ['key' => 'orders_preparing', 'label' => 'Preparing Orders', 'href' => $frontendBase . '/admin/orders.php?status=Preparing', 'badge_class' => 'preparing'],
                    ['key' => 'orders_delivered', 'label' => 'Delivered Orders', 'href' => $frontendBase . '/admin/orders.php?status=Delivered', 'badge_class' => 'delivered'],
                    ['key' => 'orders_cancelled', 'label' => 'Cancelled Orders', 'href' => $frontendBase . '/admin/orders.php?status=Cancelled', 'badge_class' => 'cancelled'],
                ],
            ],
            ['key' => 'bookings', 'label' => 'Bookings', 'icon' => 'bi-calendar3', 'href' => $frontendBase . '/admin/bookings.php', 'expandable' => true],
            ['key' => 'enquiries', 'label' => 'Enquiries', 'icon' => 'bi-chat-square-text', 'href' => $frontendBase . '/admin/enquiries.php', 'expandable' => true],
            ['key' => 'support', 'label' => 'Support Inbox', 'icon' => 'bi-headset', 'href' => $frontendBase . '/admin/support.php'],
            ['key' => 'remarks', 'label' => 'Reviews & Remarks', 'icon' => 'bi-chat-square-quote', 'href' => $frontendBase . '/admin/remarks.php'],
            ['key' => 'customers', 'label' => 'Customers', 'icon' => 'bi-people', 'href' => $frontendBase . '/admin/customers.php', 'expandable' => true],
            ['key' => 'users', 'label' => 'Users', 'icon' => 'bi-person-badge', 'href' => $frontendBase . '/admin/users.php', 'expandable' => true],
        ],
    ],
    [
        'label' => 'Food Management',
        'items' => [
            ['key' => 'foods', 'label' => 'Foods Sold', 'icon' => 'bi-clipboard2-data', 'href' => $frontendBase . '/admin/foods.php'],
            ['key' => 'gallery', 'label' => 'Gallery', 'icon' => 'bi-images', 'href' => $frontendBase . '/admin/gallery.php'],
        ],
    ],
    [
        'label' => 'Delivery',
        'items' => [
            ['key' => 'delivery_management', 'label' => 'Delivery Management', 'icon' => 'bi-truck', 'href' => $frontendBase . '/admin/delivery-management.php'],
            ['key' => 'delivery_riders', 'label' => 'Delivery Riders', 'icon' => 'bi-person-vcard', 'href' => $frontendBase . '/admin/users.php#add_user_form'],
            ['key' => 'delivery_settings', 'label' => 'Delivery Settings', 'icon' => 'bi-gear-wide-connected', 'href' => $frontendBase . '/admin/settings/index.php?section=delivery'],
        ],
    ],
    [
        'label' => 'Administration',
        'items' => [
            ['key' => 'roles', 'label' => 'Roles', 'icon' => 'bi-person-gear', 'href' => $frontendBase . '/admin/roles.php', 'expandable' => true],
            ['key' => 'permissions', 'label' => 'Permissions', 'icon' => 'bi-shield-check', 'href' => $frontendBase . '/admin/permissions.php', 'expandable' => true],
            ['key' => 'settings', 'label' => 'Settings', 'icon' => 'bi-gear', 'href' => $frontendBase . '/admin/settings/index.php'],
            ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'bi-bell', 'href' => $frontendBase . '/admin/notifications.php'],
        ],
    ],
    [
        'label' => 'Reports',
        'items' => [
            ['key' => 'analytics', 'label' => 'Analytics', 'icon' => 'bi-bar-chart', 'href' => $frontendBase . '/admin/reports.php', 'expandable' => true],
            ['key' => 'reports', 'label' => 'Reports', 'icon' => 'bi-file-earmark-text', 'href' => $frontendBase . '/admin/reports.php', 'expandable' => true],
        ],
    ],
];

if (isset($authUser) && function_exists('afrisense_is_administrator') && !afrisense_is_administrator($authUser)) {
    $adminGroups = [
        [
            'label' => 'Support',
            'items' => [
                ['key' => 'support', 'label' => 'Support Inbox', 'icon' => 'bi-headset', 'href' => $frontendBase . '/admin/support.php'],
            ],
        ],
    ];
}
?>
<aside class="af-dashboard-sidebar af-admin-sidebar" data-sidebar>
    <button class="af-sidebar-close" type="button" aria-label="Close sidebar" data-sidebar-close>
        <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>

    <a class="af-brand" href="<?php echo htmlspecialchars($frontendBase . '/admin/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="AfriSense admin dashboard">
        <span class="af-brand-icon" aria-hidden="true"><?php echo afrisense_public_brand_icon_html($frontendBase); ?></span>
        <span>
            <strong><?php echo htmlspecialchars($adminBrandName, ENT_QUOTES, 'UTF-8'); ?></strong>
            <small><?php echo htmlspecialchars($adminSiteTagline, ENT_QUOTES, 'UTF-8'); ?></small>
        </span>
    </a>

    <section class="af-sidebar-profile" aria-label="Signed in user">
        <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="">
        <div>
            <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></strong>
            <small><?php echo htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8'); ?></small>
            <span><i aria-hidden="true"></i> Online</span>
        </div>
        <button type="button" title="Profile options" aria-label="Profile options">
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </button>
    </section>

    <nav class="af-side-nav" aria-label="Admin navigation">
        <?php foreach ($adminGroups as $group): ?>
            <?php if ($group['label'] !== ''): ?>
                <p><?php echo htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <?php foreach ($group['items'] as $item): ?>
                <?php $isActiveParent = $activeAdminPage === $item['key'] || (!empty($item['children']) && in_array($activeAdminPage, array_column($item['children'], 'key'), true)); ?>
                <a class="<?php echo $isActiveParent ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if (!empty($item['expandable'])): ?>
                        <i class="bi bi-chevron-down af-nav-chevron" aria-hidden="true"></i>
                    <?php endif; ?>
                </a>
                <?php if (!empty($item['children']) && $isActiveParent): ?>
                    <div class="af-subnav">
                        <?php foreach ($item['children'] as $child): ?>
                            <a class="<?php echo $activeAdminPage === $child['key'] ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($child['href'], ENT_QUOTES, 'UTF-8'); ?>">
                                <span><?php echo htmlspecialchars($child['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if (array_key_exists($child['key'], $adminOrderNavCounts)): ?>
                                    <em class="af-nav-badge <?php echo htmlspecialchars((string) ($child['badge_class'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) min(99, (int) $adminOrderNavCounts[$child['key']]), ENT_QUOTES, 'UTF-8'); ?></em>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <a class="af-sidebar-logout" href="<?php echo htmlspecialchars($frontendBase . '/auth/logout.php', ENT_QUOTES, 'UTF-8'); ?>">
        <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
        <span>Logout</span>
    </a>

    <section class="af-sidebar-promo">
        <span aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
        <h2>Delicious Meals, Happy Customers</h2>
        <p>Providing exceptional food and unforgettable experiences.</p>
    </section>
</aside>
