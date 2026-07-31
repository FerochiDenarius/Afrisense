<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Customer Dashboard | AfriSense';
$customerTitle = 'Dashboard';
$activeCustomerPage = 'dashboard';
$extraStyles = [$frontendBase . '/assets/css/admin-menu.css'];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

$authUser = afrisense_require_customer();

function afrisense_customer_dashboard_customer(PDO $pdo, array $user): ?array
{
    $email = trim((string) ($user['email'] ?? ''));
    $phone = preg_replace('/\s+/', '', trim((string) ($user['phonenumber'] ?? $user['phone'] ?? '')));
    $statement = $pdo->prepare(
        'SELECT *
         FROM `customers`
         WHERE `email` = :email OR REPLACE(`phone_number`, " ", "") = :phone
         ORDER BY `id` ASC
         LIMIT 1'
    );
    $statement->execute(['email' => $email, 'phone' => $phone]);
    $customer = $statement->fetch(PDO::FETCH_ASSOC);

    return $customer ?: null;
}

$ordersCount = 0;
$bookingsCount = 0;
$enquiriesCount = 0;
$unreadNotifications = 0;
$totalSpent = 0.00;
$memberSince = 'Now';
$verificationLabel = ((int) ($authUser['email_verified'] ?? 0) === 1) ? '100%' : '0%';
$loadError = '';

try {
    $pdo = afrisense_pdo();
    $customer = afrisense_customer_dashboard_customer($pdo, $authUser);
    $customerId = (int) ($customer['id'] ?? 0);

    if ($customerId > 0) {
        $ordersStatement = $pdo->prepare('SELECT COUNT(*) AS count_value, COALESCE(SUM(`total_price`), 0) AS total_spent FROM `orders` WHERE `customer_id` = :customer_id');
        $ordersStatement->execute(['customer_id' => $customerId]);
        $ordersSummary = $ordersStatement->fetch(PDO::FETCH_ASSOC) ?: [];
        $ordersCount = (int) ($ordersSummary['count_value'] ?? 0);
        $totalSpent = (float) ($ordersSummary['total_spent'] ?? 0);

        $bookingsStatement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `bookings` WHERE `customer_id` = :customer_id');
        $bookingsStatement->execute(['customer_id' => $customerId]);
        $bookingsCount = (int) ($bookingsStatement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0);

        $enquiriesStatement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `enquiries` WHERE `customer_id` = :customer_id');
        $enquiriesStatement->execute(['customer_id' => $customerId]);
        $enquiriesCount = (int) ($enquiriesStatement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0);
    }

    $notificationStatement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `notifications` WHERE `user_id` = :user_id AND `is_read` = 0');
    $notificationStatement->execute(['user_id' => (int) ($authUser['id'] ?? 0)]);
    $unreadNotifications = (int) ($notificationStatement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0);

    $createdAt = strtotime((string) ($authUser['created_at'] ?? ''));
    $memberSince = $createdAt !== false ? date('M Y', $createdAt) : 'Now';
} catch (Throwable $exception) {
    $loadError = 'Dashboard data could not be loaded. Check that MySQL is running.';
}

ob_start();
?>
<section class="af-admin-menu-page">
    <header class="af-admin-page-heading">
        <div>
            <h1>Customer Dashboard</h1>
            <p>Manage your orders, bookings, enquiries, notifications, and AfriSense profile.</p>
        </div>
        <a class="af-add-menu-btn" href="<?php echo htmlspecialchars($frontendBase . '/customer/orders.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-bag-plus" aria-hidden="true"></i>
            Order Food
        </a>
    </header>

    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="af-menu-metrics" aria-label="Customer summary">
        <article class="green">
            <span><i class="bi bi-bag-check" aria-hidden="true"></i></span>
            <div><small>Total Orders</small><strong><?php echo htmlspecialchars((string) $ordersCount, ENT_QUOTES, 'UTF-8'); ?></strong><p>GHC <?php echo htmlspecialchars(number_format($totalSpent, 2), ENT_QUOTES, 'UTF-8'); ?> spent</p></div>
        </article>
        <article class="gold">
            <span><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
            <div><small>Bookings</small><strong><?php echo htmlspecialchars((string) $bookingsCount, ENT_QUOTES, 'UTF-8'); ?></strong><p>Your reservations</p></div>
        </article>
        <article class="blue">
            <span><i class="bi bi-chat-square-text" aria-hidden="true"></i></span>
            <div><small>Enquiries</small><strong><?php echo htmlspecialchars((string) $enquiriesCount, ENT_QUOTES, 'UTF-8'); ?></strong><p>Support requests</p></div>
        </article>
        <article class="purple">
            <span><i class="bi bi-bell" aria-hidden="true"></i></span>
            <div><small>Notifications</small><strong><?php echo htmlspecialchars((string) $unreadNotifications, ENT_QUOTES, 'UTF-8'); ?></strong><p>Unread updates</p></div>
        </article>
    </section>

    <section class="af-menu-workspace">
        <div class="af-menu-main">
            <section class="af-menu-table-card">
                <div class="af-menu-panel">
                    <h2>Quick Start</h2>
                    <div class="af-menu-actions">
                        <a href="<?php echo htmlspecialchars($frontendBase . '/customer/orders.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-bag-plus green" aria-hidden="true"></i> Place Order</a>
                        <a href="<?php echo htmlspecialchars($frontendBase . '/customer/my-orders.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-receipt gold" aria-hidden="true"></i> My Orders</a>
                        <a href="<?php echo htmlspecialchars($frontendBase . '/customer/my-bookings.php#booking_form', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-calendar-plus blue" aria-hidden="true"></i> Book a Service</a>
                        <a href="<?php echo htmlspecialchars($frontendBase . '/customer/support.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-chat-dots purple" aria-hidden="true"></i> Support Agent</a>
                        <a href="<?php echo htmlspecialchars($frontendBase . '/customer/remarks.php#customer-remark-form', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-chat-square-quote gold" aria-hidden="true"></i> Give Remark</a>
                    </div>
                </div>
            </section>
        </div>

        <aside class="af-menu-side">
            <section class="af-menu-panel">
                <h2>Your Account</h2>
                <ul class="af-category-list">
                    <li><i class="bi bi-person green" aria-hidden="true"></i><span>Profile</span><strong><?php echo htmlspecialchars((string) ($authUser['fullname'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></strong></li>
                    <li><i class="bi bi-shield-check gold" aria-hidden="true"></i><span>Verification</span><strong><?php echo htmlspecialchars($verificationLabel, ENT_QUOTES, 'UTF-8'); ?></strong></li>
                    <li><i class="bi bi-clock blue" aria-hidden="true"></i><span>Member Since</span><strong><?php echo htmlspecialchars($memberSince, ENT_QUOTES, 'UTF-8'); ?></strong></li>
                </ul>
            </section>
        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/customer_layout.php';
?>
