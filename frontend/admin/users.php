<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Users | AfriSense';
$adminTitle = 'Users';
$activeAdminPage = 'users';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

afrisense_require_admin();

function afrisense_count_users(PDO $pdo, string $condition = '1 = 1', array $params = []): int
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) AS count_value
         FROM `users` u
         LEFT JOIN `roles` r ON r.`id` = u.`role_id`
         WHERE ' . $condition
    );
    $statement->execute($params);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['count_value'] ?? 0);
}

function afrisense_user_role_class(string $role): string
{
    $role = strtolower($role);

    return match (true) {
        str_contains($role, 'admin') => 'admin',
        str_contains($role, 'staff'), str_contains($role, 'manager'), str_contains($role, 'chef'), str_contains($role, 'cashier') => 'staff',
        str_contains($role, 'delivery'), str_contains($role, 'rider') => 'rider',
        default => 'customer',
    };
}

function afrisense_admin_user_form_value(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

try {
    $pdo = afrisense_pdo();
    $search = trim((string) ($_GET['search'] ?? ''));
    $roleFilter = (int) ($_GET['role'] ?? 0);
    $verifiedFilter = trim((string) ($_GET['verified'] ?? ''));
    $flashMessage = '';
    $flashType = 'success';

    $rolesStatement = $pdo->prepare('SELECT `id`, `rolename` FROM `roles` ORDER BY `rolename` ASC');
    $rolesStatement->execute();
    $roles = $rolesStatement->fetchAll(PDO::FETCH_ASSOC);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'create_user') {
        $fullname = afrisense_admin_user_form_value('fullname');
        $username = afrisense_admin_user_form_value('username');
        $email = strtolower(afrisense_admin_user_form_value('email'));
        $phone = preg_replace('/\s+/', '', afrisense_admin_user_form_value('phonenumber'));
        $password = (string) ($_POST['password'] ?? '');
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $emailVerified = isset($_POST['email_verified']) ? 1 : 0;

        if ($fullname === '' || $email === '' || $phone === '' || $password === '' || $roleId <= 0) {
            $flashType = 'error';
            $flashMessage = 'Full name, email, phone, password and role are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flashType = 'error';
            $flashMessage = 'Enter a valid email address.';
        } elseif (strlen($phone) < 10 || strlen($phone) > 13) {
            $flashType = 'error';
            $flashMessage = 'Phone number must be between 10 and 13 digits.';
        } elseif (strlen($password) < 8) {
            $flashType = 'error';
            $flashMessage = 'Password must be at least 8 characters.';
        } else {
            $roleCheck = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `roles` WHERE `id` = :id');
            $roleCheck->execute(['id' => $roleId]);
            $roleExists = ((int) ($roleCheck->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0)) > 0;

            $duplicate = $pdo->prepare(
                'SELECT COUNT(*) AS count_value
                 FROM `users`
                 WHERE `email` = :email OR `username` = :username OR `phonenumber` = :phone'
            );
            $username = $username !== '' ? $username : afrisense_unique_username($pdo, $email, $fullname);
            $duplicate->execute([
                'email' => $email,
                'username' => $username,
                'phone' => $phone,
            ]);

            if (!$roleExists) {
                $flashType = 'error';
                $flashMessage = 'Selected role does not exist.';
            } elseif (((int) ($duplicate->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0)) > 0) {
                $flashType = 'error';
                $flashMessage = 'A user with that email, username or phone already exists.';
            } else {
                $insert = $pdo->prepare(
                    'INSERT INTO `users`
                        (`fullname`, `username`, `email`, `phonenumber`, `password`, `role_id`, `email_verified`)
                     VALUES
                        (:fullname, :username, :email, :phone, :password, :role_id, :email_verified)'
                );
                $insert->execute([
                    'fullname' => $fullname,
                    'username' => $username,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role_id' => $roleId,
                    'email_verified' => $emailVerified,
                ]);

                $flashMessage = 'User "' . $fullname . '" created successfully.';
            }
        }
    }

    $totalUsers = afrisense_count_users($pdo);
    $customerUsers = afrisense_count_users($pdo, 'LOWER(COALESCE(r.`rolename`, \'\')) = :role', ['role' => 'customer']);
    $adminUsers = afrisense_count_users($pdo, 'LOWER(COALESCE(r.`rolename`, \'\')) IN (\'administrator\', \'admin\', \'super admin\')');
    $riderUsers = afrisense_count_users($pdo, 'LOWER(COALESCE(r.`rolename`, \'\')) LIKE :role', ['role' => '%rider%']);
    $staffUsers = max(0, $totalUsers - $customerUsers - $adminUsers - $riderUsers);

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(u.`fullname` LIKE :search OR u.`username` LIKE :search OR u.`email` LIKE :search OR u.`phonenumber` LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    if ($roleFilter > 0) {
        $where[] = 'u.`role_id` = :role_id';
        $params['role_id'] = $roleFilter;
    }

    if ($verifiedFilter === 'verified') {
        $where[] = 'u.`email_verified` = 1';
    }

    if ($verifiedFilter === 'pending') {
        $where[] = 'COALESCE(u.`email_verified`, 0) = 0';
    }

    $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';
    $statement = $pdo->prepare(
        'SELECT
            u.`id`,
            u.`fullname`,
            u.`username`,
            u.`email`,
            u.`phonenumber`,
            u.`created_at`,
            u.`email_verified`,
            COALESCE(r.`rolename`, \'Unassigned\') AS role_name
         FROM `users` u
         LEFT JOIN `roles` r ON r.`id` = u.`role_id`
         ' . $whereSql . '
         ORDER BY u.`id` DESC
         LIMIT 25'
    );
    $statement->execute($params);
    $users = $statement->fetchAll(PDO::FETCH_ASSOC);
    $loadError = '';
} catch (Throwable $exception) {
    $totalUsers = 0;
    $customerUsers = 0;
    $staffUsers = 0;
    $riderUsers = 0;
    $adminUsers = 0;
    $users = [];
    $roles = [];
    $search = '';
    $roleFilter = 0;
    $verifiedFilter = '';
    $flashMessage = '';
    $flashType = 'error';
    $loadError = 'Users could not be loaded. Check that MySQL is running.';
}

ob_start();
?>
<section class="af-admin-menu-page af-admin-users-page">
    <header class="af-admin-page-heading">
        <div>
            <h1>Users</h1>
            <p>Manage all users in the system. View, edit and manage accounts and their roles.</p>
        </div>
        <a class="af-add-menu-btn" href="#add_user_form">
            <i class="bi bi-person-plus" aria-hidden="true"></i>
            Add New User
        </a>
    </header>

    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="af-menu-metrics af-user-metrics" aria-label="User summary">
        <article class="green">
            <span><i class="bi bi-people" aria-hidden="true"></i></span>
            <div><small>Total Users</small><strong><?php echo htmlspecialchars((string) $totalUsers, ENT_QUOTES, 'UTF-8'); ?></strong><p>All registered users</p></div>
        </article>
        <article class="gold">
            <span><i class="bi bi-person-heart" aria-hidden="true"></i></span>
            <div><small>Customers</small><strong><?php echo htmlspecialchars((string) $customerUsers, ENT_QUOTES, 'UTF-8'); ?></strong><p>Active customers</p></div>
        </article>
        <article class="blue">
            <span><i class="bi bi-person-workspace" aria-hidden="true"></i></span>
            <div><small>Staff Members</small><strong><?php echo htmlspecialchars((string) $staffUsers, ENT_QUOTES, 'UTF-8'); ?></strong><p>System staff</p></div>
        </article>
        <article class="purple">
            <span><i class="bi bi-bicycle" aria-hidden="true"></i></span>
            <div><small>Delivery Riders</small><strong><?php echo htmlspecialchars((string) $riderUsers, ENT_QUOTES, 'UTF-8'); ?></strong><p>Active riders</p></div>
        </article>
        <article class="red">
            <span><i class="bi bi-shield-check" aria-hidden="true"></i></span>
            <div><small>Administrators</small><strong><?php echo htmlspecialchars((string) $adminUsers, ENT_QUOTES, 'UTF-8'); ?></strong><p>System admins</p></div>
        </article>
    </section>

    <section class="af-menu-table-card af-user-create-card">
        <header class="af-table-toolbar">
            <h2>Add User / Staff</h2>
            <p>Create a staff, rider, customer or admin account using the roles already in the system.</p>
        </header>
        <form id="add_user_form" class="af-food-management-form af-user-create-form" action="users.php#add_user_form" method="post">
            <input type="hidden" name="action" value="create_user">
            <label>
                <span>Full Name</span>
                <input type="text" name="fullname" value="<?php echo htmlspecialchars(afrisense_admin_user_form_value('fullname'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter full name" required>
            </label>
            <label>
                <span>Username</span>
                <input type="text" name="username" value="<?php echo htmlspecialchars(afrisense_admin_user_form_value('username'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Auto from email if blank">
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?php echo htmlspecialchars(afrisense_admin_user_form_value('email'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="name@example.com" required>
            </label>
            <label>
                <span>Phone</span>
                <input type="tel" name="phonenumber" value="<?php echo htmlspecialchars(afrisense_admin_user_form_value('phonenumber'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="0240000000" required>
            </label>
            <label>
                <span>Role</span>
                <select name="role_id" required>
                    <option value="">Select role</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo htmlspecialchars((string) ($role['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int) ($_POST['role_id'] ?? 0) === (int) ($role['id'] ?? 0) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) ($role['rolename'] ?? 'Role'), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" placeholder="At least 8 characters" required>
            </label>
            <label class="af-user-verify-check">
                <input type="checkbox" name="email_verified" value="1" checked>
                <span>Email verified</span>
            </label>
            <button type="submit"><i class="bi bi-person-plus" aria-hidden="true"></i> Create User</button>
        </form>
    </section>

    <section class="af-menu-table-card">
        <form class="af-menu-filters af-users-filters" action="users.php" method="get">
            <label class="af-menu-search" for="user_search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="user_search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by name, email or phone...">
            </label>
            <label class="af-menu-select" for="role_filter">
                <select id="role_filter" name="role">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo htmlspecialchars((string) ($role['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" <?php echo $roleFilter === (int) ($role['id'] ?? 0) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) ($role['rolename'] ?? 'Role'), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </label>
            <label class="af-menu-select" for="status_filter">
                <select id="status_filter" name="status">
                    <option>All Status</option>
                    <option>Active</option>
                    <option>Inactive</option>
                </select>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </label>
            <label class="af-menu-select" for="verified_filter">
                <select id="verified_filter" name="verified">
                    <option value="">All Verified</option>
                    <option value="verified" <?php echo $verifiedFilter === 'verified' ? 'selected' : ''; ?>>Email Verified</option>
                    <option value="pending" <?php echo $verifiedFilter === 'pending' ? 'selected' : ''; ?>>Pending Verification</option>
                </select>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </label>
            <button type="submit"><i class="bi bi-filter" aria-hidden="true"></i> Filter</button>
            <button type="button"><i class="bi bi-download" aria-hidden="true"></i> Export</button>
        </form>

        <div class="af-menu-table af-users-table">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Email / Phone</th>
                        <th>Status</th>
                        <th>Verified</th>
                        <th>Joined On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users === []): ?>
                        <tr>
                            <td colspan="8">
                                <div class="af-empty-state">No users found yet.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($users as $index => $user): ?>
                        <?php
                        $roleName = (string) ($user['role_name'] ?? 'Unassigned');
                        $isVerified = (int) ($user['email_verified'] ?? 0) === 1;
                        $joined = strtotime((string) ($user['created_at'] ?? '')) ?: time();
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) ($index + 1), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div class="af-user-cell">
                                    <img class="af-user-avatar" src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="">
                                    <span class="af-user-meta">
                                        <strong><?php echo htmlspecialchars((string) ($user['fullname'] ?? 'Unknown User'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <small>@<?php echo htmlspecialchars((string) ($user['username'] ?? 'user'), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </span>
                                </div>
                            </td>
                            <td><span class="af-user-role <?php echo htmlspecialchars(afrisense_user_role_class($roleName), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td>
                                <span class="af-user-contact">
                                    <strong><?php echo htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo htmlspecialchars((string) ($user['phonenumber'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                                </span>
                            </td>
                            <td><span class="af-status active"><i class="bi bi-circle-fill" aria-hidden="true"></i> Active</span></td>
                            <td>
                                <span class="af-verified-stack">
                                    <i class="bi <?php echo $isVerified ? 'bi-check-circle' : 'bi-clock'; ?>" aria-hidden="true"></i>
                                    <small><?php echo $isVerified ? 'Email' : 'Pending'; ?></small>
                                </span>
                            </td>
                            <td>
                                <span class="af-user-date">
                                    <strong><?php echo htmlspecialchars(date('j M Y', $joined), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo htmlspecialchars(date('h:i A', $joined), ENT_QUOTES, 'UTF-8'); ?></small>
                                </span>
                            </td>
                            <td>
                                <div class="af-row-actions">
                                    <button type="button" aria-label="View <?php echo htmlspecialchars((string) ($user['fullname'] ?? 'user'), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                    <button type="button" aria-label="Edit <?php echo htmlspecialchars((string) ($user['fullname'] ?? 'user'), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
                                    <button type="button" aria-label="More actions"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <footer class="af-menu-pagination">
            <p>Showing 1 to <?php echo htmlspecialchars((string) count($users), ENT_QUOTES, 'UTF-8'); ?> of <?php echo htmlspecialchars((string) $totalUsers, ENT_QUOTES, 'UTF-8'); ?> users</p>
            <nav aria-label="Users pagination">
                <a href="#" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                <a class="active" href="#">1</a>
                <a href="#">2</a>
                <a href="#">3</a>
                <span>...</span>
                <a href="#">19</a>
                <a href="#" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            </nav>
        </footer>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
