<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Customer Settings | AfriSense';
$customerTitle = 'Customer Settings';
$activeCustomerPage = 'settings';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
    $frontendBase . '/assets/css/customer-settings.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

\AfriSense\Backend\Helpers\Session::start();
$authUser = afrisense_require_customer();

function afrisense_customer_settings_phone(array $user): string
{
    return (string) ($user['phonenumber'] ?? $user['phone'] ?? '');
}

function afrisense_customer_settings_customer(PDO $pdo, array $user): ?array
{
    $email = (string) ($user['email'] ?? '');
    $phone = afrisense_customer_settings_phone($user);
    $statement = $pdo->prepare(
        'SELECT *
         FROM `customers`
         WHERE `email` = :email OR `phone_number` = :phone
         ORDER BY `id` ASC
         LIMIT 1'
    );
    $statement->execute([
        'email' => $email,
        'phone' => $phone,
    ]);
    $customer = $statement->fetch(PDO::FETCH_ASSOC);

    return $customer ?: null;
}

function afrisense_customer_settings_sync_customer(PDO $pdo, array $user, string $fullname, string $phone): void
{
    $email = (string) ($user['email'] ?? '');
    $customer = afrisense_customer_settings_customer($pdo, $user);

    if ($customer !== null) {
        $update = $pdo->prepare(
            'UPDATE `customers`
             SET `fullname` = :fullname,
                 `email` = :email,
                 `phone_number` = :phone,
                 `updated_at` = NOW()
             WHERE `id` = :id'
        );
        $update->execute([
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'id' => (int) $customer['id'],
        ]);

        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO `customers` (`fullname`, `email`, `phone_number`)
         VALUES (:fullname, :email, :phone)'
    );
    $insert->execute([
        'fullname' => $fullname,
        'email' => $email,
        'phone' => $phone,
    ]);
}

$flashMessage = '';
$flashType = 'success';
$preferences = $_SESSION['customer_settings_preferences'] ?? [
    'email_notifications' => true,
    'sms_notifications' => true,
    'order_updates' => true,
    'booking_reminders' => true,
    'promotions' => false,
    'login_alerts' => true,
];

try {
    $pdo = afrisense_pdo();
    $userStatement = $pdo->prepare('SELECT * FROM `users` WHERE `id` = :id LIMIT 1');
    $userStatement->execute(['id' => (int) ($authUser['id'] ?? 0)]);
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    if ($user === false) {
        header('Location: /Afrisense/frontend/auth/logout.php');
        exit;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'send_verification') {
            if ((int) ($user['email_verified'] ?? 0) === 1) {
                $flashMessage = 'Your email address is already verified.';
            } else {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600);
                $update = $pdo->prepare(
                    'UPDATE `users`
                     SET `verification_token` = :token,
                         `verification_token_expires` = :expires
                     WHERE `id` = :id'
                );
                $update->execute([
                    'token' => $token,
                    'expires' => $expires,
                    'id' => (int) $user['id'],
                ]);

                $customerUpdate = $pdo->prepare(
                    'UPDATE `customers`
                     SET `verification_token` = :token,
                         `verification_token_expires` = :expires
                     WHERE `email` = :email'
                );
                $customerUpdate->execute([
                    'token' => $token,
                    'expires' => $expires,
                    'email' => (string) $user['email'],
                ]);

                $mailResult = afrisense_send_verification_email((string) $user['email'], (string) $user['fullname'], $token);
                $flashType = $mailResult['success'] ? 'success' : 'error';
                $flashMessage = $mailResult['success']
                    ? 'Verification email sent. Check your inbox.'
                    : 'Verification email could not be sent: ' . (string) ($mailResult['message'] ?? 'Unknown email error.');
            }
        }

        if ($action === 'change_password') {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            if (!password_verify($currentPassword, (string) ($user['password'] ?? ''))) {
                $flashType = 'error';
                $flashMessage = 'Current password is incorrect.';
            } elseif ($newPassword !== $confirmPassword) {
                $flashType = 'error';
                $flashMessage = 'New password and confirmation do not match.';
            } elseif (strlen($newPassword) < 8) {
                $flashType = 'error';
                $flashMessage = 'New password must be at least 8 characters.';
            } else {
                $update = $pdo->prepare('UPDATE `users` SET `password` = :password WHERE `id` = :id');
                $update->execute([
                    'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'id' => (int) $user['id'],
                ]);
                $flashMessage = 'Password updated successfully.';
            }
        }

        if ($action === 'update_profile') {
            $fullname = trim((string) ($_POST['fullname'] ?? ''));
            $phone = preg_replace('/\s+/', '', trim((string) ($_POST['phonenumber'] ?? '')));

            if ($fullname === '' || strlen($fullname) < 2 || $phone === '') {
                $flashType = 'error';
                $flashMessage = 'Full name and phone number are required.';
            } elseif (strlen($phone) < 10 || strlen($phone) > 13) {
                $flashType = 'error';
                $flashMessage = 'Phone number must be between 10 and 13 digits.';
            } else {
                $duplicate = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `users` WHERE `phonenumber` = :phone AND `id` <> :id');
                $duplicate->execute([
                    'phone' => $phone,
                    'id' => (int) $user['id'],
                ]);

                if (((int) ($duplicate->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0)) > 0) {
                    $flashType = 'error';
                    $flashMessage = 'That phone number is already used by another account.';
                } else {
                    $update = $pdo->prepare(
                        'UPDATE `users`
                         SET `fullname` = :fullname,
                             `phonenumber` = :phone
                         WHERE `id` = :id'
                    );
                    $update->execute([
                        'fullname' => $fullname,
                        'phone' => $phone,
                        'id' => (int) $user['id'],
                    ]);
                    afrisense_customer_settings_sync_customer($pdo, $user, $fullname, $phone);
                    $_SESSION['fullname'] = $fullname;
                    $flashMessage = 'Profile updated successfully.';
                }
            }
        }

        if ($action === 'save_preferences') {
            $preferences = [
                'email_notifications' => isset($_POST['email_notifications']),
                'sms_notifications' => isset($_POST['sms_notifications']),
                'order_updates' => isset($_POST['order_updates']),
                'booking_reminders' => isset($_POST['booking_reminders']),
                'promotions' => isset($_POST['promotions']),
                'login_alerts' => isset($_POST['login_alerts']),
            ];
            $_SESSION['customer_settings_preferences'] = $preferences;
            $flashMessage = 'Preferences saved for this session.';
        }

        $userStatement->execute(['id' => (int) ($authUser['id'] ?? 0)]);
        $user = $userStatement->fetch(PDO::FETCH_ASSOC);
    }

    $customer = afrisense_customer_settings_customer($pdo, $user);
    $customerId = (int) ($customer['id'] ?? 0);
    $ordersCount = 0;
    $bookingsCount = 0;
    $totalSpent = 0.00;

    if ($customerId > 0) {
        $ordersStatement = $pdo->prepare('SELECT COUNT(*) AS count_value, COALESCE(SUM(`total_price`), 0) AS total_spent FROM `orders` WHERE `customer_id` = :customer_id');
        $ordersStatement->execute(['customer_id' => $customerId]);
        $ordersSummary = $ordersStatement->fetch(PDO::FETCH_ASSOC) ?: [];
        $ordersCount = (int) ($ordersSummary['count_value'] ?? 0);
        $totalSpent = (float) ($ordersSummary['total_spent'] ?? 0);

        $bookingsStatement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `bookings` WHERE `customer_id` = :customer_id');
        $bookingsStatement->execute(['customer_id' => $customerId]);
        $bookingsCount = (int) ($bookingsStatement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0);
    }

    $loadError = '';
} catch (Throwable $exception) {
    $user = $authUser;
    $customer = null;
    $ordersCount = 0;
    $bookingsCount = 0;
    $totalSpent = 0.00;
    $loadError = 'Settings could not be loaded. Check that MySQL is running.';
}

$isVerified = (int) ($user['email_verified'] ?? 0) === 1;
$memberSince = strtotime((string) ($user['created_at'] ?? '')) ?: time();

ob_start();
?>
<section class="af-admin-menu-page af-customer-settings-page">
    <header class="af-admin-page-heading af-customer-settings-heading">
        <div>
            <h1>Customer Settings</h1>
            <p>Manage your account preferences and security settings.</p>
        </div>
        <nav aria-label="Breadcrumb">
            <a href="dashboard.php">Home</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Customer Settings</span>
        </nav>
    </header>

    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="af-customer-settings-grid">
        <section class="af-customer-setting-card">
            <header><span><i class="bi bi-envelope" aria-hidden="true"></i></span><div><h2>Email Verification</h2><p>Verify your email address to unlock all features and account recovery.</p></div></header>
            <div class="af-settings-readonly-field"><i class="bi bi-envelope" aria-hidden="true"></i><strong><?php echo htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong><em class="<?php echo $isVerified ? 'verified' : 'pending'; ?>"><?php echo $isVerified ? 'Verified' : 'Pending'; ?> <i class="bi <?php echo $isVerified ? 'bi-check' : 'bi-clock'; ?>" aria-hidden="true"></i></em></div>
            <p class="af-settings-meta"><?php echo $isVerified ? 'Verified email addresses can request password resets.' : 'Verification is required before password reset will work.'; ?></p>
            <?php if ($isVerified): ?>
                <p class="af-settings-success"><i class="bi bi-check-circle" aria-hidden="true"></i> Your email is verified. Thank you!</p>
            <?php else: ?>
                <form action="settings.php" method="post">
                    <input type="hidden" name="action" value="send_verification">
                    <button class="af-settings-primary" type="submit"><i class="bi bi-send" aria-hidden="true"></i> Send Verification Email</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="af-customer-setting-card">
            <header><span><i class="bi bi-lock" aria-hidden="true"></i></span><div><h2>Change Password</h2><p>Use a long, unique password to keep your account secure.</p></div></header>
            <form class="af-customer-settings-form" action="settings.php" method="post">
                <input type="hidden" name="action" value="change_password">
                <label><span>Current Password</span><em><i class="bi bi-lock" aria-hidden="true"></i><input type="password" name="current_password" placeholder="Enter your current password" required></em></label>
                <label><span>New Password</span><em><i class="bi bi-lock" aria-hidden="true"></i><input type="password" name="new_password" placeholder="Enter new password" minlength="8" required></em></label>
                <label><span>Confirm New Password</span><em><i class="bi bi-lock" aria-hidden="true"></i><input type="password" name="confirm_password" placeholder="Confirm new password" minlength="8" required></em></label>
                <button class="af-settings-primary" type="submit"><i class="bi bi-lock" aria-hidden="true"></i> Update Password</button>
            </form>
        </section>

        <aside class="af-customer-setting-card af-security-card">
            <header><span class="green"><i class="bi bi-shield-check" aria-hidden="true"></i></span><div><h2>Account Security</h2></div></header>
            <form action="settings.php" method="post" class="af-security-list">
                <input type="hidden" name="action" value="save_preferences">
                <label><i class="bi bi-shield-lock" aria-hidden="true"></i><span><strong>Two-Factor Authentication</strong><small>Add an extra layer of security</small></span><input type="checkbox" disabled></label>
                <label><i class="bi bi-bell" aria-hidden="true"></i><span><strong>Login Alerts</strong><small>Get notified about new logins</small></span><input type="checkbox" name="login_alerts" <?php echo !empty($preferences['login_alerts']) ? 'checked' : ''; ?>></label>
                <a href="notifications.php"><i class="bi bi-clock-history" aria-hidden="true"></i><span><strong>Active Sessions</strong><small>Manage your notifications</small></span><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            </form>
        </aside>

        <section class="af-customer-setting-card">
            <header><span><i class="bi bi-person" aria-hidden="true"></i></span><div><h2>Profile Information</h2><p>Update your personal information and customer account details.</p></div></header>
            <form class="af-customer-settings-form" action="settings.php" method="post">
                <input type="hidden" name="action" value="update_profile">
                <label><span>Full Name</span><em><i class="bi bi-person" aria-hidden="true"></i><input type="text" name="fullname" value="<?php echo htmlspecialchars((string) ($user['fullname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required></em></label>
                <label><span>Phone Number</span><em><i class="bi bi-telephone" aria-hidden="true"></i><input type="tel" name="phonenumber" value="<?php echo htmlspecialchars(afrisense_customer_settings_phone($user), ENT_QUOTES, 'UTF-8'); ?>" required></em></label>
                <label><span>Email Address</span><em><i class="bi bi-envelope" aria-hidden="true"></i><input type="email" value="<?php echo htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" disabled></em></label>
                <button class="af-settings-primary" type="submit"><i class="bi bi-person-check" aria-hidden="true"></i> Update Profile</button>
            </form>
        </section>

        <section class="af-customer-setting-card">
            <header><span><i class="bi bi-bell" aria-hidden="true"></i></span><div><h2>Notification Preferences</h2><p>Choose how you want to receive updates and notifications.</p></div></header>
            <form class="af-preference-list" action="settings.php" method="post">
                <input type="hidden" name="action" value="save_preferences">
                <?php
                $preferenceLabels = [
                    'email_notifications' => ['Email Notifications', 'Receive updates via email', 'bi-envelope'],
                    'sms_notifications' => ['SMS Notifications', 'Receive updates via SMS', 'bi-phone'],
                    'order_updates' => ['Order Updates', 'Get notified about your orders', 'bi-bag-check'],
                    'booking_reminders' => ['Booking Reminders', 'Get reminders for your bookings', 'bi-calendar'],
                    'promotions' => ['Promotions & Offers', 'Receive offers and promotions', 'bi-tags'],
                ];
                ?>
                <?php foreach ($preferenceLabels as $key => $item): ?>
                    <label><i class="bi <?php echo htmlspecialchars($item[2], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i><span><strong><?php echo htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8'); ?></small></span><input type="checkbox" name="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo !empty($preferences[$key]) ? 'checked' : ''; ?>></label>
                <?php endforeach; ?>
                <button class="af-settings-primary" type="submit"><i class="bi bi-bell" aria-hidden="true"></i> Save Preferences</button>
            </form>
        </section>

        <aside class="af-customer-side-stack">
            <section class="af-customer-setting-card">
                <header><span class="blue"><i class="bi bi-person" aria-hidden="true"></i></span><div><h2>Account Summary</h2></div></header>
                <dl class="af-account-summary">
                    <div><dt>Member Since</dt><dd><?php echo htmlspecialchars(date('j M Y', $memberSince), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>Account Type</dt><dd>Customer</dd></div>
                    <div><dt>Total Orders</dt><dd><?php echo htmlspecialchars((string) $ordersCount, ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>Total Bookings</dt><dd><?php echo htmlspecialchars((string) $bookingsCount, ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>Total Spent</dt><dd>GHC <?php echo htmlspecialchars(number_format($totalSpent, 2), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                </dl>
            </section>

            <section class="af-customer-setting-card af-delete-card">
                <header><span class="red"><i class="bi bi-trash" aria-hidden="true"></i></span><div><h2>Delete Account</h2><p>Account deletion is intentionally disabled for this exam build.</p></div></header>
                <button type="button" disabled>Delete My Account</button>
            </section>
        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/customer_layout.php';
?>
