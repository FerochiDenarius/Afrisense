<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Permissions Management | AfriSense';
$adminTitle = 'Permissions';
$adminSearchPlaceholder = 'Search for users, roles, permissions...';
$activeAdminPage = 'permissions';
$permissionsCssPath = __DIR__ . '/../assets/css/admin-permissions.css';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

// Guard this block so it only runs when the required condition is met.
if (is_file($permissionsCssPath)) {
    $extraStyles[] = $frontendBase . '/assets/css/admin-permissions.css?v=' . filemtime($permissionsCssPath);
}

require_once __DIR__ . '/../auth/auth_bootstrap.php';

$authUser = afrisense_require_admin();

/**
 * Complete permission catalog used by the permissions UI.
 *
 * The database stores slugs, but the page owns the human labels, grouping,
 * icons, and descriptions so new modules can be added in one place.
 */
function afrisense_permissions_catalog(): array
{
    return [
        ['Dashboard', 'bi-house-door', 'green', [
            ['view_dashboard', 'View Dashboard', 'Can view the dashboard'],
            ['view_dashboard_statistics', 'View Statistics', 'Can view system statistics and analytics'],
            ['view_reports_summary', 'View Reports Summary', 'Can view reports summary on dashboard'],
            ['view_sales_overview', 'View Sales Overview', 'Can view sales overview'],
            ['view_recent_orders', 'View Recent Orders', 'Can view recent orders'],
            ['view_recent_bookings', 'View Recent Bookings', 'Can view recent bookings'],
            ['view_notifications', 'View Notifications', 'Can view system notifications'],
            ['export_dashboard_data', 'Export Dashboard Data', 'Can export dashboard data'],
        ]],
        ['Users', 'bi-people', 'purple', [
            ['view_users', 'View Users', 'Can view user accounts'],
            ['create_users', 'Create Users', 'Can create user accounts'],
            ['edit_users', 'Edit Users', 'Can edit user accounts'],
            ['delete_users', 'Delete Users', 'Can delete user accounts'],
            ['verify_user_email', 'Verify User Email', 'Can mark user email as verified'],
            ['assign_user_roles', 'Assign User Roles', 'Can assign roles to users'],
            ['filter_users', 'Filter Users', 'Can filter and search users'],
            ['export_users', 'Export Users', 'Can export user records'],
        ]],
        ['Roles & Permissions', 'bi-shield-lock', 'orange', [
            ['view_roles', 'View Roles', 'Can view system roles'],
            ['create_roles', 'Create Roles', 'Can create roles'],
            ['edit_roles', 'Edit Roles', 'Can edit roles'],
            ['delete_roles', 'Delete Roles', 'Can delete roles'],
            ['view_permissions', 'View Permissions', 'Can view permission assignments'],
            ['assign_permissions', 'Assign Permissions', 'Can assign permissions to roles'],
            ['create_custom_permissions', 'Create Custom Permissions', 'Can create custom permissions'],
            ['clone_role_permissions', 'Clone Role Permissions', 'Can copy permissions between roles'],
        ]],
        ['Customers', 'bi-person-lines-fill', 'blue', [
            ['view_customers', 'View Customers', 'Can view customer accounts and information'],
            ['search_customers', 'Search Customers', 'Can search customer records'],
            ['view_customer_activity', 'View Customer Activity', 'Can view orders and bookings by customer'],
            ['view_customer_contact', 'View Customer Contact', 'Can view customer email and phone'],
            ['view_customer_addresses', 'View Customer Addresses', 'Can view customer addresses'],
            ['export_customers', 'Export Customers', 'Can export customer reports'],
            ['manage_customer_status', 'Manage Customer Status', 'Can manage customer status'],
        ]],
        ['Orders', 'bi-cart-check', 'green', [
            ['view_orders', 'View Orders', 'Can view customer orders'],
            ['create_orders', 'Create Orders', 'Can create admin orders'],
            ['confirm_orders', 'Confirm Orders', 'Can confirm pending orders'],
            ['cancel_orders', 'Cancel Orders', 'Can cancel orders'],
            ['update_order_status', 'Update Order Status', 'Can move orders through workflow'],
            ['update_payment_status', 'Update Payment Status', 'Can update payment records'],
            ['view_order_customer_email', 'View Order Customer Email', 'Can view order email details'],
            ['export_orders', 'Export Orders', 'Can export order data'],
            ['send_order_notifications', 'Send Order Notifications', 'Can notify customers about orders'],
        ]],
        ['Bookings', 'bi-calendar-check', 'blue', [
            ['view_bookings', 'View Bookings', 'Can view bookings'],
            ['create_bookings', 'Create Bookings', 'Can create manual bookings'],
            ['confirm_bookings', 'Confirm Bookings', 'Can confirm bookings'],
            ['cancel_bookings', 'Cancel Bookings', 'Can cancel bookings'],
            ['complete_bookings', 'Complete Bookings', 'Can mark bookings completed'],
            ['export_bookings', 'Export Bookings', 'Can export booking records'],
            ['print_bookings', 'Print Bookings', 'Can print booking reports'],
        ]],
        ['Enquiries & Support', 'bi-headset', 'teal', [
            ['view_enquiries', 'View Enquiries', 'Can view enquiries'],
            ['reply_enquiries', 'Reply Enquiries', 'Can save enquiry responses'],
            ['close_enquiries', 'Close Enquiries', 'Can close enquiries'],
            ['delete_enquiries', 'Delete Enquiries', 'Can delete enquiries'],
            ['view_support_inbox', 'View Support Inbox', 'Can view support conversations'],
            ['reply_support_chats', 'Reply Support Chats', 'Can reply to support chats'],
            ['send_support_attachments', 'Send Support Attachments', 'Can send support attachments'],
            ['assign_support_agents', 'Assign Support Agents', 'Can manage support agents'],
        ]],
        ['Food Management', 'bi-egg-fried', 'green', [
            ['view_foods', 'View Foods', 'Can view food items'],
            ['create_foods', 'Create Foods', 'Can add food items'],
            ['edit_foods', 'Edit Foods', 'Can edit food items'],
            ['delete_foods', 'Delete Foods', 'Can delete food items'],
            ['toggle_food_availability', 'Toggle Food Availability', 'Can enable or disable foods'],
            ['manage_categories', 'Manage Categories', 'Can manage food categories'],
            ['upload_gallery_images', 'Upload Gallery Images', 'Can upload gallery images'],
            ['manage_gallery', 'Manage Gallery', 'Can manage gallery items'],
            ['manage_services', 'Manage Services', 'Can manage catering services'],
        ]],
        ['Delivery Management', 'bi-truck', 'red', [
            ['view_deliveries', 'View Deliveries', 'Can view delivery management'],
            ['assign_delivery_riders', 'Assign Delivery Riders', 'Can assign riders to orders'],
            ['update_delivery_status', 'Update Delivery Status', 'Can update delivery status'],
            ['collect_delivery_payments', 'Collect Delivery Payments', 'Can record payment on delivery'],
            ['print_delivery_receipts', 'Print Delivery Receipts', 'Can print delivery receipts'],
            ['download_delivery_invoices', 'Download Delivery Invoices', 'Can download delivery invoices'],
            ['manage_delivery_settings', 'Manage Delivery Settings', 'Can manage delivery settings'],
        ]],
        ['Reports & Analytics', 'bi-bar-chart', 'orange', [
            ['view_reports', 'View Reports', 'Can view reports'],
            ['filter_reports', 'Filter Reports', 'Can filter reports'],
            ['export_reports', 'Export Reports', 'Can export reports'],
            ['view_sales_analytics', 'View Sales Analytics', 'Can view sales analytics'],
            ['view_payment_reports', 'View Payment Reports', 'Can view payment reports'],
            ['download_recent_reports', 'Download Recent Reports', 'Can download generated reports'],
        ]],
        ['Notifications', 'bi-bell', 'purple', [
            ['view_admin_notifications', 'View Admin Notifications', 'Can view admin notifications'],
            ['open_notifications', 'Open Notifications', 'Can open notification links'],
            ['mark_notifications_read', 'Mark Notifications Read', 'Can mark notifications as read'],
            ['delete_notifications', 'Delete Notifications', 'Can delete notifications'],
        ]],
        ['System Settings', 'bi-gear', 'gray', [
            ['view_settings', 'View Settings', 'Can view settings'],
            ['edit_website_settings', 'Edit Website Settings', 'Can edit website settings'],
            ['edit_company_settings', 'Edit Company Settings', 'Can edit company information'],
            ['view_audit_logs', 'View Audit Logs', 'Can view audit logs'],
            ['manage_security_settings', 'Manage Security Settings', 'Can manage security settings'],
        ]],
    ];
}

// Defines the afrisense_permission_tables helper used by this module.
function afrisense_permission_tables(PDO $pdo): void
{
    // These tables are safe to create at runtime because permissions were added
    // after the original app schema and may be missing on older installations.
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `permissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `module` VARCHAR(80) NOT NULL,
            `name` VARCHAR(120) NOT NULL,
            `slug` VARCHAR(120) NOT NULL UNIQUE,
            `description` VARCHAR(255) NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_permissions_module` (`module`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `role_permissions` (
            `role_id` INT NOT NULL,
            `permission_id` INT NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`role_id`, `permission_id`),
            INDEX `idx_role_permissions_permission` (`permission_id`),
            CONSTRAINT `fk_role_permissions_role`
                FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
                ON DELETE CASCADE,
            CONSTRAINT `fk_role_permissions_permission`
                FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

// Defines the afrisense_permission_seed helper used by this module.
function afrisense_permission_seed(PDO $pdo): void
{
    // Seed/update by slug so editing labels or descriptions in the catalog
    // updates existing installs without duplicating permissions.
    $insert = $pdo->prepare(
        'INSERT INTO `permissions` (`module`, `name`, `slug`, `description`)
         VALUES (:module, :name, :slug, :description)
         ON DUPLICATE KEY UPDATE
            `module` = VALUES(`module`),
            `name` = VALUES(`name`),
            `description` = VALUES(`description`)'
    );

    // Iterate through the data needed for this block.
    foreach (afrisense_permissions_catalog() as $module) {
        // Iterate through the data needed for this block.
        foreach ($module[3] as $permission) {
            $insert->execute([
                'module' => $module[0],
                'name' => $permission[1],
                'slug' => $permission[0],
                'description' => $permission[2],
            ]);
        }
    }
}

// Defines the afrisense_permission_icon helper used by this module.
function afrisense_permission_icon(string $roleName): string
{
    $roleName = strtolower($roleName);

    return match (true) {
        str_contains($roleName, 'admin'), str_contains($roleName, 'super') => 'bi-shield-check',
        str_contains($roleName, 'manager') => 'bi-people',
        str_contains($roleName, 'kitchen'), str_contains($roleName, 'chef') => 'bi-egg-fried',
        str_contains($roleName, 'cashier') => 'bi-cash-coin',
        str_contains($roleName, 'delivery'), str_contains($roleName, 'rider') => 'bi-truck',
        str_contains($roleName, 'support') => 'bi-headset',
        str_contains($roleName, 'customer') => 'bi-person',
        default => 'bi-person-gear',
    };
}

// Defines the afrisense_permission_tone helper used by this module.
function afrisense_permission_tone(string $value): string
{
    $value = strtolower($value);

    return match (true) {
        str_contains($value, 'admin'), str_contains($value, 'dashboard'), str_contains($value, 'food') => 'green',
        str_contains($value, 'manager'), str_contains($value, 'reports'), str_contains($value, 'roles') => 'orange',
        str_contains($value, 'user'), str_contains($value, 'notification') => 'purple',
        str_contains($value, 'customer'), str_contains($value, 'booking') => 'blue',
        str_contains($value, 'support'), str_contains($value, 'enquir') => 'teal',
        str_contains($value, 'delivery'), str_contains($value, 'rider') => 'red',
        default => 'gray',
    };
}

// Defines the afrisense_permission_default_slugs helper used by this module.
function afrisense_permission_default_slugs(string $roleName, array $allSlugs): array
{
    $roleName = strtolower($roleName);

    // Guard this block so it only runs when the required condition is met.
    if (str_contains($roleName, 'admin') || str_contains($roleName, 'super')) {
        return $allSlugs;
    }

    $prefixes = match (true) {
        str_contains($roleName, 'manager') => ['view_', 'filter_', 'export_', 'confirm_', 'update_', 'reply_', 'assign_'],
        str_contains($roleName, 'cashier') => ['view_orders', 'update_payment_status', 'view_reports', 'view_payment_reports', 'view_customers', 'view_admin_notifications'],
        str_contains($roleName, 'chef'), str_contains($roleName, 'kitchen') => ['view_orders', 'update_order_status', 'view_foods', 'toggle_food_availability', 'view_admin_notifications'],
        str_contains($roleName, 'delivery'), str_contains($roleName, 'rider') => ['view_deliveries', 'update_delivery_status', 'collect_delivery_payments', 'print_delivery_receipts', 'download_delivery_invoices', 'view_orders', 'view_admin_notifications'],
        str_contains($roleName, 'support') => ['view_support_inbox', 'reply_support_chats', 'send_support_attachments', 'view_enquiries', 'reply_enquiries', 'close_enquiries', 'view_customers', 'view_admin_notifications'],
        str_contains($roleName, 'customer') => [],
        default => ['view_dashboard', 'view_orders', 'view_bookings', 'view_customers', 'view_reports'],
    };

    return array_values(array_filter($allSlugs, static function (string $slug) use ($prefixes): bool {
        // Iterate through the data needed for this block.
        foreach ($prefixes as $prefix) {
            // Guard this block so it only runs when the required condition is met.
            if ($slug === $prefix || str_starts_with($slug, $prefix)) {
                return true;
            }
        }

        return false;
    }));
}

// Defines the afrisense_permission_url helper used by this module.
function afrisense_permission_url(array $overrides = [], string $anchor = ''): string
{
    $params = $_GET;

    // Iterate through the data needed for this block.
    foreach ($overrides as $key => $value) {
        // Guard this block so it only runs when the required condition is met.
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = (string) $value;
        }
    }

    $query = http_build_query($params);

    return 'permissions.php' . ($query !== '' ? '?' . $query : '') . $anchor;
}

$selectedRoleId = (int) ($_GET['role'] ?? 0);
$roleSearch = trim((string) ($_GET['role_search'] ?? ''));
$permissionSearch = trim((string) ($_GET['permission_search'] ?? ''));
$flashMessage = '';
$flashType = 'success';
$explicitlySavedRoleIds = [];

// Run database/action work inside a guarded block so the page can fail gracefully.
try {
    $pdo = afrisense_pdo();
    afrisense_delivery_rider_role_id($pdo);
    afrisense_permission_tables($pdo);
    afrisense_permission_seed($pdo);

    // Handle submitted form actions before rendering the page.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $postedRoleId = (int) ($_POST['role_id'] ?? 0);

        // Guard this block so it only runs when the required condition is met.
        if ($action === 'save_permissions' && $postedRoleId > 0) {
            $permissionIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['permission_ids'] ?? [])))));
            $pdo->beginTransaction();
            $delete = $pdo->prepare('DELETE FROM `role_permissions` WHERE `role_id` = :role_id');
            $delete->execute(['role_id' => $postedRoleId]);

            // Guard this block so it only runs when the required condition is met.
            if ($permissionIds !== []) {
                $validStatement = $pdo->query('SELECT `id` FROM `permissions`');
                $validIds = array_flip(array_map('intval', $validStatement->fetchAll(PDO::FETCH_COLUMN)));
                $insert = $pdo->prepare('INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (:role_id, :permission_id)');

                // Iterate through the data needed for this block.
                foreach ($permissionIds as $permissionId) {
                    // Guard this block so it only runs when the required condition is met.
                    if (isset($validIds[$permissionId])) {
                        $insert->execute(['role_id' => $postedRoleId, 'permission_id' => $permissionId]);
                    }
                }
            }

            $pdo->commit();
            $selectedRoleId = $postedRoleId;
            $explicitlySavedRoleIds[$postedRoleId] = true;
            $flashMessage = 'Permissions saved successfully.';
        }

        // Guard this block so it only runs when the required condition is met.
        if ($action === 'create_custom_permission') {
            $customModule = trim((string) ($_POST['module'] ?? 'Custom Permissions'));
            $customName = trim((string) ($_POST['name'] ?? ''));
            $customDescription = trim((string) ($_POST['description'] ?? ''));
            $customSlug = strtolower(preg_replace('/[^a-z0-9]+/', '_', $customName));
            $customSlug = trim((string) $customSlug, '_');

            // Guard this block so it only runs when the required condition is met.
            if ($customName === '' || $customSlug === '') {
                $flashType = 'error';
                $flashMessage = 'Permission name is required.';
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO `permissions` (`module`, `name`, `slug`, `description`)
                     VALUES (:module, :name, :slug, :description)
                     ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`)'
                );
                $statement->execute([
                    'module' => $customModule !== '' ? $customModule : 'Custom Permissions',
                    'name' => $customName,
                    'slug' => $customSlug,
                    'description' => $customDescription !== '' ? $customDescription : 'Custom permission',
                ]);
                $flashMessage = 'Custom permission saved.';
            }
        }
    }

    $roleWhere = '';
    $roleParams = [];
    // Guard this block so it only runs when the required condition is met.
    if ($roleSearch !== '') {
        $roleWhere = 'WHERE r.`rolename` LIKE :search OR r.`description` LIKE :search';
        $roleParams['search'] = '%' . $roleSearch . '%';
    }
    $roleStatement = $pdo->prepare(
        'SELECT r.`id`, r.`rolename`, r.`description`, r.`created_at`, COUNT(u.`id`) AS user_count
         FROM `roles` r
         LEFT JOIN `users` u ON u.`role_id` = r.`id`
         ' . $roleWhere . '
         GROUP BY r.`id`, r.`rolename`, r.`description`, r.`created_at`
         ORDER BY
            CASE
                WHEN LOWER(r.`rolename`) IN ("super admin", "administrator", "admin") THEN 0
                WHEN LOWER(r.`rolename`) = "customer" THEN 9
                ELSE 1
            END,
            r.`id` ASC'
    );
    $roleStatement->execute($roleParams);
    $roles = $roleStatement->fetchAll(PDO::FETCH_ASSOC);

    // Guard this block so it only runs when the required condition is met.
    if ($selectedRoleId <= 0 && $roles !== []) {
        $selectedRoleId = (int) $roles[0]['id'];
    }

    $permissionRows = $pdo->query('SELECT * FROM `permissions` ORDER BY `module` ASC, `id` ASC')->fetchAll(PDO::FETCH_ASSOC);
    $allPermissionIds = array_map(static fn (array $row): int => (int) $row['id'], $permissionRows);
    $allPermissionSlugs = array_map(static fn (array $row): string => (string) $row['slug'], $permissionRows);
    $slugToId = [];
    // Iterate through the data needed for this block.
    foreach ($permissionRows as $row) {
        $slugToId[(string) $row['slug']] = (int) $row['id'];
    }

    // Iterate through the data needed for this block.
    foreach ($roles as $role) {
        $roleId = (int) $role['id'];
        // Guard this block so it only runs when the required condition is met.
        if (isset($explicitlySavedRoleIds[$roleId])) {
            continue;
        }
        $count = (int) $pdo->query('SELECT COUNT(*) FROM `role_permissions` WHERE `role_id` = ' . $roleId)->fetchColumn();
        // Guard this block so it only runs when the required condition is met.
        if ($count === 0) {
            $defaultSlugs = afrisense_permission_default_slugs((string) $role['rolename'], $allPermissionSlugs);
            // Guard this block so it only runs when the required condition is met.
            if ($defaultSlugs !== []) {
                $insert = $pdo->prepare('INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (:role_id, :permission_id)');
                // Iterate through the data needed for this block.
                foreach ($defaultSlugs as $slug) {
                    // Guard this block so it only runs when the required condition is met.
                    if (isset($slugToId[$slug])) {
                        $insert->execute(['role_id' => $roleId, 'permission_id' => $slugToId[$slug]]);
                    }
                }
            }
        }
    }

    $selectedRole = null;
    // Iterate through the data needed for this block.
    foreach ($roles as $role) {
        // Guard this block so it only runs when the required condition is met.
        if ((int) $role['id'] === $selectedRoleId) {
            $selectedRole = $role;
            break;
        }
    }
    $selectedRole = $selectedRole ?? ($roles[0] ?? null);
    $selectedRoleId = (int) ($selectedRole['id'] ?? 0);

    $assignedStatement = $pdo->prepare('SELECT `permission_id` FROM `role_permissions` WHERE `role_id` = :role_id');
    $assignedStatement->execute(['role_id' => $selectedRoleId]);
    $assignedPermissionIds = array_map('intval', $assignedStatement->fetchAll(PDO::FETCH_COLUMN));
    $assignedLookup = array_flip($assignedPermissionIds);

    $permissionModules = [];
    // Iterate through the data needed for this block.
    foreach ($permissionRows as $row) {
        // Guard this block so it only runs when the required condition is met.
        if ($permissionSearch !== '') {
            $haystack = strtolower((string) $row['module'] . ' ' . (string) $row['name'] . ' ' . (string) $row['description'] . ' ' . (string) $row['slug']);
            // Guard this block so it only runs when the required condition is met.
            if (!str_contains($haystack, strtolower($permissionSearch))) {
                continue;
            }
        }
        $permissionModules[(string) $row['module']][] = $row;
    }

    $totalPermissions = count($permissionRows);
    $assignedCount = count($assignedPermissionIds);
    $unassignedCount = max(0, $totalPermissions - $assignedCount);
    $loadError = '';
} catch (Throwable $exception) {
    // Guard this block so it only runs when the required condition is met.
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $roles = [];
    $selectedRole = null;
    $permissionModules = [];
    $totalPermissions = 0;
    $assignedCount = 0;
    $unassignedCount = 0;
    $assignedLookup = [];
    $loadError = 'Permissions could not be loaded. Check that MySQL is running.';
}

$roleName = (string) ($selectedRole['rolename'] ?? 'Role');
$roleDescription = (string) ($selectedRole['description'] ?? 'Access assigned modules and permissions.');
$assignedRate = $totalPermissions > 0 ? round(($assignedCount / $totalPermissions) * 100, 1) : 0;
$updatedAt = date('d M Y');

ob_start();
?>
<!-- Page section for this part of the AfriSense interface. -->
<section class="af-permissions-page">
    <!-- Header block for this interface section. -->
    <header class="af-admin-page-heading af-permissions-heading">
        <div>
            <h1>Permissions Management</h1>
            <p>Manage roles and assign permissions to control system access.</p>
        </div>
        <a class="af-permission-settings-btn" href="<?php echo htmlspecialchars($frontendBase . '/admin/settings/index.php?section=security', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-gear" aria-hidden="true"></i>
            Permission Settings
        </a>
    </header>

    <?php // Render this conditional/dynamic template block. ?>
    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php // Render this conditional/dynamic template block. ?>
    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- Page section for this part of the AfriSense interface. -->
    <section class="af-permissions-layout">
        <!-- Side panel with supporting information and actions. -->
        <aside class="af-permission-roles-card">
            <!-- Header block for this interface section. -->
            <header>
                <h2>Roles</h2>
                <a href="<?php echo htmlspecialchars($frontendBase . '/admin/roles.php#add_role_form', ENT_QUOTES, 'UTF-8'); ?>" title="Add new role"><i class="bi bi-plus-circle" aria-hidden="true"></i></a>
            </header>
            <!-- Form block that submits this page workflow. -->
            <form class="af-permission-role-search" action="permissions.php" method="get">
                <?php // Render this conditional/dynamic template block. ?>
                <?php if ($selectedRoleId > 0): ?><input type="hidden" name="role" value="<?php echo htmlspecialchars((string) $selectedRoleId, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
                <input type="search" name="role_search" value="<?php echo htmlspecialchars($roleSearch, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search roles...">
                <button type="submit" title="Search roles"><i class="bi bi-search" aria-hidden="true"></i></button>
            </form>
            <div class="af-permission-role-list">
                <?php // Render this conditional/dynamic template block. ?>
                <?php foreach ($roles as $role): ?>
                    <?php
                    $listRoleId = (int) $role['id'];
                    $listRoleName = (string) ($role['rolename'] ?? 'Role');
                    $isActiveRole = $listRoleId === $selectedRoleId;
                    ?>
                    <a class="<?php echo $isActiveRole ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(afrisense_permission_url(['role' => (string) $listRoleId, 'role_search' => null]), ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="<?php echo htmlspecialchars(afrisense_permission_tone($listRoleName), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi <?php echo htmlspecialchars(afrisense_permission_icon($listRoleName), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                        <strong><?php echo htmlspecialchars($listRoleName, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?php echo htmlspecialchars((string) ($role['user_count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> users</small>
                        <em><?php echo str_contains(strtolower($listRoleName), 'customer') ? 'Default' : (str_contains(strtolower($listRoleName), 'admin') ? 'System Role' : 'Active'); ?></em>
                    </a>
                <?php endforeach; ?>
            </div>
            <!-- Footer block for this interface section. -->
            <footer>
                <strong>Total Roles: <?php echo htmlspecialchars((string) count($roles), ENT_QUOTES, 'UTF-8'); ?></strong>
                <span><i class="bi bi-chevron-left" aria-hidden="true"></i></span>
                <b>1</b>
                <span><i class="bi bi-chevron-right" aria-hidden="true"></i></span>
            </footer>
        </aside>

        <!-- Form block that submits this page workflow. -->
        <form class="af-permission-editor" id="permission-editor" action="<?php echo htmlspecialchars(afrisense_permission_url(['role' => (string) $selectedRoleId]), ENT_QUOTES, 'UTF-8'); ?>" method="post">
            <input type="hidden" name="action" value="save_permissions">
            <input type="hidden" name="role_id" value="<?php echo htmlspecialchars((string) $selectedRoleId, ENT_QUOTES, 'UTF-8'); ?>">
            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-permission-main-card">
                <!-- Header block for this interface section. -->
                <header class="af-permission-selected-role">
                    <span class="<?php echo htmlspecialchars(afrisense_permission_tone($roleName), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi <?php echo htmlspecialchars(afrisense_permission_icon($roleName), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                    <div>
                        <small>Selected Role</small>
                        <h2><?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?> <em><?php echo str_contains(strtolower($roleName), 'admin') ? 'System Role' : 'Active'; ?></em></h2>
                        <p><?php echo htmlspecialchars($roleDescription, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <dl>
                        <div><dt><i class="bi bi-people" aria-hidden="true"></i></dt><dd><strong><?php echo htmlspecialchars((string) ($selectedRole['user_count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong><small>Users</small></dd></div>
                        <div><dt><i class="bi bi-calendar3" aria-hidden="true"></i></dt><dd><strong><?php echo htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8'); ?></strong><small>Last Updated</small></dd></div>
                    </dl>
                </header>

                <!-- Navigation links for this interface. -->
                <nav class="af-permission-tabs">
                    <button class="active" type="button" data-permission-no-jump>Module Permissions</button>
                    <button type="button" data-permission-custom-focus>Custom Permissions</button>
                </nav>

                <!-- Page section for this part of the AfriSense interface. -->
                <section class="af-permission-tools">
                    <label>
                        <input type="search" name="permission_search" value="<?php echo htmlspecialchars($permissionSearch, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search permissions...">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </label>
                    <button type="submit" formmethod="get" formaction="permissions.php" name="role" value="<?php echo htmlspecialchars((string) $selectedRoleId, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-filter" aria-hidden="true"></i> Filter</button>
                    <button type="button" data-permission-expand><i class="bi bi-chevron-down" aria-hidden="true"></i> Expand All</button>
                    <button type="button" data-permission-collapse><i class="bi bi-chevron-up" aria-hidden="true"></i> Collapse All</button>
                </section>

                <!-- Page section for this part of the AfriSense interface. -->
                <section class="af-permission-modules" id="module-permissions">
                    <?php // Render this conditional/dynamic template block. ?>
                    <?php foreach ($permissionModules as $moduleName => $modulePermissions): ?>
                        <?php
                        $moduleAssigned = count(array_filter($modulePermissions, static fn (array $permission): bool => isset($assignedLookup[(int) $permission['id']])));
                        $moduleTone = afrisense_permission_tone($moduleName);
                        ?>
                        <details class="af-permission-module" open>
                            <summary>
                                <span class="<?php echo htmlspecialchars($moduleTone, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi <?php echo htmlspecialchars((string) (array_values(array_filter(afrisense_permissions_catalog(), static fn (array $item): bool => $item[0] === $moduleName))[0][1] ?? 'bi-shield-check'), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                                <strong><?php echo htmlspecialchars($moduleName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars(match ($moduleName) {
                                    'Dashboard' => 'Manage dashboard and overview',
                                    'Users' => 'Manage users and user accounts',
                                    'Roles & Permissions' => 'Manage roles and permissions',
                                    'Customers' => 'Manage customer accounts and information',
                                    'Food Management' => 'Manage foods, categories and menu',
                                    default => 'Manage ' . strtolower($moduleName),
                                }, ENT_QUOTES, 'UTF-8'); ?></small>
                                <em><?php echo htmlspecialchars((string) $moduleAssigned, ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars((string) count($modulePermissions), ENT_QUOTES, 'UTF-8'); ?></em>
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </summary>
                            <div>
                                <?php // Render this conditional/dynamic template block. ?>
                                <?php foreach ($modulePermissions as $permission): ?>
                                    <?php $permissionId = (int) $permission['id']; ?>
                                    <label class="af-permission-check">
                                        <input type="checkbox" name="permission_ids[]" value="<?php echo htmlspecialchars((string) $permissionId, ENT_QUOTES, 'UTF-8'); ?>" <?php echo isset($assignedLookup[$permissionId]) ? 'checked' : ''; ?>>
                                        <span><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                                        <strong><?php echo htmlspecialchars((string) $permission['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <small><?php echo htmlspecialchars((string) ($permission['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </section>

                <!-- Footer block for this interface section. -->
                <footer>
                    <a href="<?php echo htmlspecialchars(afrisense_permission_url(['role' => (string) $selectedRoleId, 'permission_search' => null]), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Reset Changes</a>
                    <button type="submit"><i class="bi bi-floppy" aria-hidden="true"></i> Save Permissions</button>
                </footer>
            </section>
        </form>

        <!-- Side panel with supporting information and actions. -->
        <aside class="af-permission-side">
            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-permission-side-card">
                <h2>Permission Summary</h2>
                <div class="af-permission-donut" style="--assigned: <?php echo htmlspecialchars((string) $assignedRate, ENT_QUOTES, 'UTF-8'); ?>%;">
                    <strong><?php echo htmlspecialchars((string) $totalPermissions, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small>Total Permissions</small>
                </div>
                <ul>
                    <li><i class="assigned"></i><strong>Assigned</strong><span><?php echo htmlspecialchars((string) $assignedCount, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string) $assignedRate, ENT_QUOTES, 'UTF-8'); ?>%)</span></li>
                    <li><i class="unassigned"></i><strong>Unassigned</strong><span><?php echo htmlspecialchars((string) $unassignedCount, ENT_QUOTES, 'UTF-8'); ?></span></li>
                </ul>
            </section>

            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-permission-side-card">
                <h2>Role Information</h2>
                <dl>
                    <div><dt>Role Name</dt><dd><?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>Role Type</dt><dd><?php echo str_contains(strtolower($roleName), 'admin') ? 'System Role' : 'Custom Role'; ?></dd></div>
                    <div><dt>Description</dt><dd><?php echo htmlspecialchars($roleDescription, ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>Users</dt><dd><?php echo htmlspecialchars((string) ($selectedRole['user_count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>Created At</dt><dd><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime((string) ($selectedRole['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>Last Updated</dt><dd><?php echo htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8'); ?></dd></div>
                </dl>
            </section>

            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-permission-side-card af-permission-quick">
                <h2>Quick Actions</h2>
                <a href="<?php echo htmlspecialchars($frontendBase . '/admin/roles.php#add_role_form', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add New Role</a>
                <a href="#" data-permission-custom-focus><i class="bi bi-shield-plus" aria-hidden="true"></i> Add Custom Permission</a>
                <a href="<?php echo htmlspecialchars(afrisense_permission_url(['role' => (string) $selectedRoleId]), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-copy" aria-hidden="true"></i> Clone Role Permissions</a>
                <a href="<?php echo htmlspecialchars($frontendBase . '/admin/roles.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-diagram-3" aria-hidden="true"></i> View Role Hierarchy</a>
                <!-- Form block that submits this page workflow. -->
                <form id="custom-permission-form" action="<?php echo htmlspecialchars(afrisense_permission_url(['role' => (string) $selectedRoleId]), ENT_QUOTES, 'UTF-8'); ?>" method="post">
                    <input type="hidden" name="action" value="create_custom_permission">
                    <input type="text" name="name" placeholder="Custom permission name">
                    <input type="text" name="module" placeholder="Module">
                    <input type="text" name="description" placeholder="Description">
                    <button type="submit">Save Custom Permission</button>
                </form>
            </section>

            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-permission-side-card af-permission-legend">
                <h2>Permission Legend</h2>
                <p><i class="granted"></i><strong>Granted</strong><span>Permission is allowed</span></p>
                <p><i class="denied"></i><strong>Denied</strong><span>Permission is denied</span></p>
                <p><i class="unset"></i><strong>Not Set</strong><span>Permission is not configured</span></p>
            </section>
        </aside>
    </section>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modules = document.querySelectorAll('.af-permission-module');
    document.querySelector('[data-permission-expand]')?.addEventListener('click', () => modules.forEach((module) => module.open = true));
    document.querySelector('[data-permission-collapse]')?.addEventListener('click', () => modules.forEach((module) => module.open = false));
    document.querySelectorAll('[data-permission-no-jump]').forEach((control) => {
        control.addEventListener('click', (event) => event.preventDefault());
    });
    document.querySelectorAll('[data-permission-custom-focus]').forEach((control) => {
        control.addEventListener('click', (event) => {
            event.preventDefault();
            document.querySelector('#custom-permission-form input[name="name"]')?.focus({ preventScroll: true });
        });
    });
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
