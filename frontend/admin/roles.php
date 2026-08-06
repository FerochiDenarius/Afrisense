<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Roles & Permissions | AfriSense';
$adminTitle = 'Roles & Permissions';
$activeAdminPage = 'roles';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

afrisense_require_admin();

// Defines the afrisense_role_icon helper used by this module.
function afrisense_role_icon(string $roleName): string
{
    $roleName = strtolower($roleName);

    return match (true) {
        str_contains($roleName, 'admin') => 'bi-shield-check',
        str_contains($roleName, 'manager') => 'bi-person-gear',
        str_contains($roleName, 'chef') => 'bi-egg-fried',
        str_contains($roleName, 'cashier') => 'bi-cash-coin',
        str_contains($roleName, 'customer') => 'bi-person-heart',
        str_contains($roleName, 'rider'), str_contains($roleName, 'delivery') => 'bi-bicycle',
        default => 'bi-people',
    };
}

// Defines the afrisense_role_tone helper used by this module.
function afrisense_role_tone(string $roleName): string
{
    $roleName = strtolower($roleName);

    return match (true) {
        str_contains($roleName, 'admin') => 'green',
        str_contains($roleName, 'manager') => 'gold',
        str_contains($roleName, 'chef'), str_contains($roleName, 'staff') => 'blue',
        str_contains($roleName, 'cashier') => 'purple',
        str_contains($roleName, 'customer') => 'mint',
        str_contains($roleName, 'rider'), str_contains($roleName, 'delivery') => 'red',
        default => 'blue',
    };
}

// Defines the afrisense_role_permission_count helper used by this module.
function afrisense_role_permission_count(string $roleName): int
{
    $roleName = strtolower($roleName);

    return match (true) {
        str_contains($roleName, 'admin') => 72,
        str_contains($roleName, 'manager') => 41,
        str_contains($roleName, 'chef') => 18,
        str_contains($roleName, 'cashier') => 16,
        str_contains($roleName, 'customer') => 8,
        str_contains($roleName, 'rider'), str_contains($roleName, 'delivery') => 12,
        default => 26,
    };
}

// Defines the afrisense_role_permission_level helper used by this module.
function afrisense_role_permission_level(int $count): string
{
    return match (true) {
        $count >= 60 => 'All',
        $count >= 45 => 'High',
        $count >= 20 => 'Medium',
        default => 'Low',
    };
}

// Defines the afrisense_role_description helper used by this module.
function afrisense_role_description(string $roleName, string $description): string
{
    // Guard this block so it only runs when the required condition is met.
    if ($description !== '') {
        return $description;
    }

    $roleName = strtolower($roleName);

    return match (true) {
        str_contains($roleName, 'admin') => 'Full access to all features and system settings.',
        str_contains($roleName, 'manager') => 'Manage orders, bookings, reports and staff.',
        str_contains($roleName, 'chef') => 'Manage kitchen operations and food preparation.',
        str_contains($roleName, 'cashier') => 'Handle payments and customer transactions.',
        str_contains($roleName, 'customer') => 'Place orders, bookings and manage profile.',
        str_contains($roleName, 'rider'), str_contains($roleName, 'delivery') => 'Handle delivery assignments, pickups and delivery status updates.',
        default => 'Access assigned modules based on department.',
    };
}

// Run database/action work inside a guarded block so the page can fail gracefully.
try {
    $pdo = afrisense_pdo();
    afrisense_delivery_rider_role_id($pdo);

    $roleSearch = trim((string) ($_GET['search'] ?? ''));
    $flashMessage = '';
    $flashType = 'success';

    // Handle submitted form actions before rendering the page.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'create_role') {
        $roleName = trim((string) ($_POST['rolename'] ?? ''));
        $roleDescription = trim((string) ($_POST['description'] ?? ''));

        // Guard this block so it only runs when the required condition is met.
        if ($roleName === '' || strlen($roleName) < 2 || strlen($roleName) > 50) {
            $flashType = 'error';
            $flashMessage = 'Role name must be between 2 and 50 characters.';
        } elseif (strlen($roleDescription) > 500) {
            $flashType = 'error';
            $flashMessage = 'Role description must be 500 characters or less.';
        } else {
            $duplicate = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `roles` WHERE LOWER(`rolename`) = LOWER(:rolename)');
            $duplicate->execute(['rolename' => $roleName]);
            $duplicateRow = $duplicate->fetch(PDO::FETCH_ASSOC);

            // Guard this block so it only runs when the required condition is met.
            if (((int) ($duplicateRow['count_value'] ?? 0)) > 0) {
                $flashType = 'error';
                $flashMessage = 'A role with that name already exists.';
            } else {
                $insert = $pdo->prepare(
                    'INSERT INTO `roles` (`rolename`, `description`)
                     VALUES (:rolename, :description)'
                );
                $insert->execute([
                    'rolename' => $roleName,
                    'description' => $roleDescription !== '' ? $roleDescription : null,
                ]);

                $flashMessage = 'Role "' . $roleName . '" created successfully.';
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

    $hasPermissionTables = (bool) $pdo->query('SHOW TABLES LIKE "permissions"')->fetchColumn()
        && (bool) $pdo->query('SHOW TABLES LIKE "role_permissions"')->fetchColumn();

    $permissionJoinSql = $hasPermissionTables
        ? ', COUNT(DISTINCT rp.`permission_id`) AS assigned_permission_count'
        : ', 0 AS assigned_permission_count';
    $permissionJoin = $hasPermissionTables
        ? ' LEFT JOIN `role_permissions` rp ON rp.`role_id` = r.`id`'
        : '';

    $statement = $pdo->prepare(
        'SELECT
            r.`id`,
            r.`rolename`,
            r.`description`,
            r.`created_at`,
            COUNT(DISTINCT u.`id`) AS user_count
            ' . $permissionJoinSql . '
         FROM `roles` r
         LEFT JOIN `users` u ON u.`role_id` = r.`id`
         ' . $permissionJoin . '
         ' . $roleWhere . '
         GROUP BY r.`id`, r.`rolename`, r.`description`, r.`created_at`
         ORDER BY r.`id` ASC'
    );
    $statement->execute($roleParams);
    $roles = $statement->fetchAll(PDO::FETCH_ASSOC);

    $permissionCountStatement = $hasPermissionTables ? $pdo->query('SELECT COUNT(*) AS count_value FROM `permissions`') : null;
    $totalPermissionSlots = $permissionCountStatement !== null ? (int) ($permissionCountStatement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0) : 72;

    $userCountStatement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `users`');
    $userCountStatement->execute();
    $totalAssignedUsers = (int) ($userCountStatement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0);
    $loadError = '';
} catch (Throwable $exception) {
    $roles = [];
    $totalAssignedUsers = 0;
    $roleSearch = '';
    $flashMessage = '';
    $flashType = 'error';
    $hasPermissionTables = false;
    $totalPermissionSlots = 72;
    $loadError = 'Roles could not be loaded. Check that MySQL is running.';
}

$totalRoles = count($roles);
$selectedRole = $roles[0] ?? [
    'id' => 0,
    'rolename' => 'Administrator',
    'description' => 'Full access to all features and system settings.',
    'created_at' => date('Y-m-d H:i:s'),
    'user_count' => 0,
    'assigned_permission_count' => 0,
];
$selectedRolePermissionCount = $hasPermissionTables ? (int) ($selectedRole['assigned_permission_count'] ?? 0) : afrisense_role_permission_count((string) $selectedRole['rolename']);

ob_start();
?>
<!-- Page section for this part of the AfriSense interface. -->
<section class="af-admin-menu-page af-roles-page">
    <!-- Header block for this interface section. -->
    <header class="af-admin-page-heading">
        <div>
            <h1>Roles &amp; Permissions</h1>
            <p>Manage user roles and their access permissions across the system.</p>
        </div>
        <a class="af-add-menu-btn" href="#add_role_form">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            Add New Role
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
    <section class="af-menu-metrics" aria-label="Role summary">
        <article class="green">
            <span><i class="bi bi-shield-check" aria-hidden="true"></i></span>
            <div><small>Total Roles</small><strong><?php echo htmlspecialchars((string) $totalRoles, ENT_QUOTES, 'UTF-8'); ?></strong><p>All system roles</p></div>
        </article>
        <article class="blue">
            <span><i class="bi bi-people" aria-hidden="true"></i></span>
            <div><small>Users Assigned</small><strong><?php echo htmlspecialchars((string) $totalAssignedUsers, ENT_QUOTES, 'UTF-8'); ?></strong><p>Across all roles</p></div>
        </article>
        <article class="gold">
            <span><i class="bi bi-key" aria-hidden="true"></i></span>
            <div><small>Total Permissions</small><strong><?php echo htmlspecialchars((string) $totalPermissionSlots, ENT_QUOTES, 'UTF-8'); ?></strong><p>System privileges</p></div>
        </article>
        <article class="purple">
            <span><i class="bi bi-lock" aria-hidden="true"></i></span>
            <div><small>Permission Coverage</small><strong>95%</strong><p>Secure &amp; controlled</p></div>
        </article>
    </section>

    <!-- Page section for this part of the AfriSense interface. -->
    <section class="af-menu-table-card af-role-create-card">
        <!-- Header block for this interface section. -->
        <header class="af-table-toolbar">
            <h2>Create Role</h2>
            <p>Add a role that can be assigned to users from the Users page.</p>
        </header>
        <!-- Form block that submits this page workflow. -->
        <form id="add_role_form" class="af-food-management-form af-role-create-form" action="roles.php#add_role_form" method="post">
            <input type="hidden" name="action" value="create_role">
            <label>
                <span>Role Name</span>
                <input type="text" name="rolename" maxlength="50" placeholder="Example: Supervisor" required>
            </label>
            <label>
                <span>Description</span>
                <textarea name="description" rows="3" maxlength="500" placeholder="Describe what this role can manage"></textarea>
            </label>
            <button type="submit"><i class="bi bi-plus-lg" aria-hidden="true"></i> Create Role</button>
        </form>
    </section>

    <!-- Page section for this part of the AfriSense interface. -->
    <section class="af-roles-workspace">
        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-menu-table-card">
            <!-- Header block for this interface section. -->
            <header class="af-table-toolbar">
                <h2>All Roles</h2>
                <!-- Form block that submits this page workflow. -->
                <form class="af-role-search" action="roles.php" method="get">
                    <label class="af-menu-search" for="role_search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="role_search" name="search" value="<?php echo htmlspecialchars($roleSearch, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search roles...">
                    </label>
                    <button type="submit"><i class="bi bi-filter" aria-hidden="true"></i> Filter</button>
                </form>
            </header>

            <div class="af-menu-table af-roles-table">
                <!-- Table block for displaying structured records. -->
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Users</th>
                            <th>Permissions</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php // Render this conditional/dynamic template block. ?>
                        <?php if ($roles === []): ?>
                            <tr>
                                <td colspan="7"><div class="af-empty-state">No roles found yet.</div></td>
                            </tr>
                        <?php endif; ?>
                        <?php // Render this conditional/dynamic template block. ?>
                        <?php foreach ($roles as $index => $role): ?>
                            <?php
                            $roleId = (int) ($role['id'] ?? 0);
                            $roleName = (string) ($role['rolename'] ?? 'Role');
                            $permissionCount = $hasPermissionTables ? (int) ($role['assigned_permission_count'] ?? 0) : afrisense_role_permission_count($roleName);
                            $permissionLevel = afrisense_role_permission_level($permissionCount);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) ($index + 1), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="af-role-name">
                                        <span class="<?php echo htmlspecialchars(afrisense_role_tone($roleName), ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="bi <?php echo htmlspecialchars(afrisense_role_icon($roleName), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                        </span>
                                        <strong><?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php // Render this conditional/dynamic template block. ?>
                                        <?php if ($index === 0): ?><small>System</small><?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars(afrisense_role_description($roleName, (string) ($role['description'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($role['user_count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="af-permission-count"><?php echo htmlspecialchars((string) $permissionCount, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="af-permission-level <?php echo strtolower($permissionLevel); ?>"><?php echo htmlspecialchars($permissionLevel, ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td><span class="af-status active"><i class="bi bi-circle-fill" aria-hidden="true"></i> Active</span></td>
                                <td>
                                    <div class="af-row-actions">
                                        <a href="permissions.php?role=<?php echo htmlspecialchars((string) $roleId, ENT_QUOTES, 'UTF-8'); ?>#permission-editor" title="Manage role permissions" aria-label="Manage permissions for <?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-shield-check" aria-hidden="true"></i></a>
                                        <button type="button" title="Edit role" aria-label="Edit <?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer block for this interface section. -->
            <footer class="af-menu-pagination">
                <p>Showing 1 to <?php echo htmlspecialchars((string) $totalRoles, ENT_QUOTES, 'UTF-8'); ?> of <?php echo htmlspecialchars((string) $totalRoles, ENT_QUOTES, 'UTF-8'); ?> roles</p>
                <!-- Navigation links for this interface. -->
                <nav aria-label="Roles pagination">
                    <a class="is-disabled" href="#" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                    <a class="active" href="#">1</a>
                    <a class="is-disabled" href="#" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                </nav>
            </footer>
        </section>

        <!-- Side panel with supporting information and actions. -->
        <aside class="af-roles-side">
            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-role-details-card">
                <h2>Role Details</h2>
                <div class="af-role-detail-head">
                    <span class="<?php echo htmlspecialchars(afrisense_role_tone((string) $selectedRole['rolename']), ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="bi <?php echo htmlspecialchars(afrisense_role_icon((string) $selectedRole['rolename']), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                    </span>
                    <strong><?php echo htmlspecialchars((string) $selectedRole['rolename'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <em>Active</em>
                </div>
                <p><?php echo htmlspecialchars(afrisense_role_description((string) $selectedRole['rolename'], (string) ($selectedRole['description'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>
                <hr>
                <h3>Permissions Summary</h3>
                <ul>
                    <li>Total Permissions: <?php echo htmlspecialchars((string) $selectedRolePermissionCount, ENT_QUOTES, 'UTF-8'); ?></li>
                    <li>Module Access: <?php echo str_contains(strtolower((string) $selectedRole['rolename']), 'admin') ? 'All' : 'Assigned modules'; ?></li>
                    <li>User Management: <?php echo str_contains(strtolower((string) $selectedRole['rolename']), 'admin') ? 'Full Access' : 'Limited Access'; ?></li>
                    <li>System Settings: <?php echo str_contains(strtolower((string) $selectedRole['rolename']), 'admin') ? 'Full Access' : 'No Access'; ?></li>
                    <li>Created On: <?php echo htmlspecialchars(date('j M Y', strtotime((string) ($selectedRole['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></li>
                </ul>
                <a class="af-role-permission-link" href="permissions.php?role=<?php echo htmlspecialchars((string) ($selectedRole['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>#permission-editor">View Full Permissions</a>
            </section>

            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-role-help-card">
                <h2><i class="bi bi-question-circle" aria-hidden="true"></i> Quick Help</h2>
                <p>Roles help you control what each user can see and do in the system.</p>
                <a href="#">Learn more about roles <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </section>
        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
