<?php
$frontendBase = $frontendBase ?? '/Afrisense/frontend';
$activeAdminPage = $activeAdminPage ?? '';
$adminName = $adminName ?? 'Admin User';
$adminRole = $adminRole ?? 'Super Admin';

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
                    ['key' => 'orders_pending', 'label' => 'Pending Orders', 'href' => $frontendBase . '/admin/orders.php?status=Pending'],
                    ['key' => 'orders_confirmed', 'label' => 'Confirmed Orders', 'href' => $frontendBase . '/admin/orders.php?status=Confirmed'],
                    ['key' => 'orders_preparing', 'label' => 'Preparing Orders', 'href' => $frontendBase . '/admin/orders.php?status=Preparing'],
                    ['key' => 'orders_delivered', 'label' => 'Delivered Orders', 'href' => $frontendBase . '/admin/orders.php?status=Delivered'],
                    ['key' => 'orders_cancelled', 'label' => 'Cancelled Orders', 'href' => $frontendBase . '/admin/orders.php?status=Cancelled'],
                ],
            ],
            ['key' => 'bookings', 'label' => 'Bookings', 'icon' => 'bi-calendar3', 'href' => $frontendBase . '/admin/bookings.php', 'expandable' => true],
            ['key' => 'enquiries', 'label' => 'Enquiries', 'icon' => 'bi-chat-square-text', 'href' => $frontendBase . '/admin/enquiries.php', 'expandable' => true],
            ['key' => 'customers', 'label' => 'Customers', 'icon' => 'bi-people', 'href' => $frontendBase . '/admin/customers.php', 'expandable' => true],
            ['key' => 'users', 'label' => 'Users', 'icon' => 'bi-person-badge', 'href' => $frontendBase . '/admin/users.php', 'expandable' => true],
        ],
    ],
    [
        'label' => 'Food Management',
        'items' => [
            ['key' => 'foods', 'label' => 'Foods Sold', 'icon' => 'bi-clipboard2-data', 'href' => $frontendBase . '/admin/foods.php'],
        ],
    ],
    [
        'label' => 'Administration',
        'items' => [
            ['key' => 'roles', 'label' => 'Roles', 'icon' => 'bi-person-gear', 'href' => $frontendBase . '/admin/roles.php', 'expandable' => true],
            ['key' => 'permissions', 'label' => 'Permissions', 'icon' => 'bi-shield-check', 'href' => $frontendBase . '/admin/roles.php', 'expandable' => true],
            ['key' => 'settings', 'label' => 'Settings', 'icon' => 'bi-gear', 'href' => $frontendBase . '/admin/settings.php'],
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
?>
<aside class="af-dashboard-sidebar af-admin-sidebar" data-sidebar>
    <button class="af-sidebar-close" type="button" aria-label="Close sidebar" data-sidebar-close>
        <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>

    <a class="af-brand" href="<?php echo htmlspecialchars($frontendBase . '/admin/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="AfriSense admin dashboard">
        <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
        <span>
            <strong>AfriSense</strong>
            <small>Food Services</small>
        </span>
    </a>

    <section class="af-sidebar-profile" aria-label="Signed in user">
        <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="">
        <div>
            <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></strong>
            <small><?php echo htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8'); ?></small>
            <span><i aria-hidden="true"></i> Online</span>
        </div>
        <button type="button" aria-label="Profile options">
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
                                <?php echo htmlspecialchars($child['label'], ENT_QUOTES, 'UTF-8'); ?>
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
