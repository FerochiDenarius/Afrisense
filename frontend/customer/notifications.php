<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Notifications | AfriSense';
$customerTitle = 'Notifications';
$activeCustomerPage = 'notifications';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

$authUser = afrisense_require_customer();
$authUserId = (int) ($authUser['id'] ?? 0);

function afrisense_customer_notification_icon(string $type): string
{
    return match (strtolower($type)) {
        'order' => 'bi-cart3',
        'booking' => 'bi-calendar3',
        'enquiry' => 'bi-envelope',
        'security' => 'bi-shield-check',
        default => 'bi-bell',
    };
}

function afrisense_customer_notification_tone(string $type): string
{
    return match (strtolower($type)) {
        'order' => 'green',
        'booking' => 'blue',
        'enquiry' => 'gold',
        'security' => 'purple',
        default => 'gray',
    };
}

function afrisense_customer_time_ago(string $dateTime): string
{
    $timestamp = strtotime($dateTime);

    if ($timestamp === false) {
        return '';
    }

    $diff = max(0, time() - $timestamp);

    if ($diff < 60) {
        return 'Just now';
    }

    if ($diff < 3600) {
        return (string) floor($diff / 60) . ' mins ago';
    }

    if ($diff < 86400) {
        return (string) floor($diff / 3600) . ' hours ago';
    }

    return (string) floor($diff / 86400) . ' days ago';
}

function afrisense_customer_notification_count(PDO $pdo, int $userId, ?string $type = null, ?int $read = null): int
{
    $where = ['`user_id` = :user_id'];
    $params = ['user_id' => $userId];

    if ($type !== null) {
        $where[] = '`notification_type` = :type';
        $params['type'] = $type;
    }

    if ($read !== null) {
        $where[] = '`is_read` = :is_read';
        $params['is_read'] = $read;
    }

    $statement = $pdo->prepare(
        'SELECT COUNT(*) AS count_value
         FROM `notifications`
         WHERE ' . implode(' AND ', $where)
    );
    $statement->execute($params);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['count_value'] ?? 0);
}

function afrisense_customer_notification_url(string $url): string
{
    $url = trim($url);

    if ($url === '' || str_starts_with($url, '/Afrisense/frontend/admin/')) {
        return '#';
    }

    if (str_contains($url, '/customer/my-orders.php?view=') && !str_contains($url, '#')) {
        $url .= '#order-details';
    }

    if (str_starts_with($url, '/Afrisense/frontend/')) {
        return $url;
    }

    if (str_starts_with($url, 'customer/') || str_starts_with($url, 'landing/')) {
        $url = '/Afrisense/frontend/' . $url;

        if (str_contains($url, '/customer/my-orders.php?view=') && !str_contains($url, '#')) {
            $url .= '#order-details';
        }

        return $url;
    }

    return '#';
}

function afrisense_redirect_customer_notifications(string $typeFilter, string $statusFilter): never
{
    $params = [];

    if ($typeFilter !== '') {
        $params['type'] = $typeFilter;
    }

    if ($statusFilter !== '') {
        $params['status'] = $statusFilter;
    }

    header('Location: notifications.php' . ($params !== [] ? '?' . http_build_query($params) : ''));
    exit;
}

$validTypes = ['Order', 'Booking', 'Enquiry', 'System', 'Security'];
$typeFilter = (string) ($_GET['type'] ?? '');
$statusFilter = (string) ($_GET['status'] ?? '');
$flashMessage = '';
$flashType = 'success';

try {
    $pdo = afrisense_pdo();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $notificationId = (int) ($_POST['notification_id'] ?? 0);

        if ($action === 'mark_all_read') {
            $statement = $pdo->prepare(
                'UPDATE `notifications`
                 SET `is_read` = 1
                 WHERE `user_id` = :user_id'
            );
            $statement->execute(['user_id' => $authUserId]);
            afrisense_flash_set('success', 'Notifications marked as read.');
            afrisense_redirect_customer_notifications($typeFilter, $statusFilter);
        }

        if ($notificationId > 0 && in_array($action, ['mark_read', 'delete', 'open'], true)) {
            $notificationStatement = $pdo->prepare(
                'SELECT `action_url`
                 FROM `notifications`
                 WHERE `id` = :id AND `user_id` = :user_id
                 LIMIT 1'
            );
            $notificationStatement->execute([
                'id' => $notificationId,
                'user_id' => $authUserId,
            ]);
            $notification = $notificationStatement->fetch(PDO::FETCH_ASSOC);

            if ($notification === false) {
                afrisense_flash_set('error', 'Notification could not be found.');
                afrisense_redirect_customer_notifications($typeFilter, $statusFilter);
            }

            if ($action === 'delete') {
                $statement = $pdo->prepare(
                    'DELETE FROM `notifications`
                     WHERE `id` = :id AND `user_id` = :user_id'
                );
                $statement->execute([
                    'id' => $notificationId,
                    'user_id' => $authUserId,
                ]);
                afrisense_flash_set('success', 'Notification deleted.');
                afrisense_redirect_customer_notifications($typeFilter, $statusFilter);
            }

            $statement = $pdo->prepare(
                'UPDATE `notifications`
                 SET `is_read` = 1
                 WHERE `id` = :id AND `user_id` = :user_id'
            );
            $statement->execute([
                'id' => $notificationId,
                'user_id' => $authUserId,
            ]);

            if ($action === 'open') {
                $actionUrl = afrisense_customer_notification_url((string) ($notification['action_url'] ?? ''));

                if ($actionUrl !== '#') {
                    header('Location: ' . $actionUrl);
                    exit;
                }
            }

            afrisense_flash_set('success', 'Notification marked as read.');
            afrisense_redirect_customer_notifications($typeFilter, $statusFilter);
        }
    }

    $flash = afrisense_flash_get();
    $flashMessage = (string) ($flash['message'] ?? '');
    $flashType = (string) ($flash['type'] ?? 'success');

    $where = ['n.`user_id` = :user_id'];
    $params = ['user_id' => $authUserId];

    if (in_array($typeFilter, $validTypes, true)) {
        $where[] = 'n.`notification_type` = :type';
        $params['type'] = $typeFilter;
    }

    if ($statusFilter === 'read') {
        $where[] = 'n.`is_read` = 1';
    }

    if ($statusFilter === 'unread') {
        $where[] = 'n.`is_read` = 0';
    }

    $statement = $pdo->prepare(
        'SELECT
            n.`id`,
            n.`title`,
            n.`message`,
            n.`notification_type`,
            n.`is_read`,
            n.`action_url`,
            n.`created_at`
         FROM `notifications` n
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY n.`created_at` DESC, n.`id` DESC
         LIMIT 30'
    );
    $statement->execute($params);
    $notifications = $statement->fetchAll(PDO::FETCH_ASSOC);

    $totalNotifications = afrisense_customer_notification_count($pdo, $authUserId);
    $unreadNotifications = afrisense_customer_notification_count($pdo, $authUserId, null, 0);
    $typeCounts = [];

    foreach ($validTypes as $type) {
        $typeCounts[$type] = afrisense_customer_notification_count($pdo, $authUserId, $type);
    }

    $loadError = '';
} catch (Throwable $exception) {
    $notifications = [];
    $totalNotifications = 0;
    $unreadNotifications = 0;
    $typeCounts = array_fill_keys($validTypes, 0);
    $loadError = 'Notifications could not be loaded. Check that MySQL is running.';
}

ob_start();
?>
<section class="af-admin-menu-page af-notifications-page">
    <header class="af-admin-page-heading">
        <div>
            <h1>Notifications</h1>
            <p>Track order updates, booking confirmations, account alerts, and AfriSense messages.</p>
        </div>
        <form action="notifications.php" method="post">
            <input type="hidden" name="action" value="mark_all_read">
            <button class="af-notification-read-btn" type="submit" <?php echo $unreadNotifications === 0 ? 'disabled' : ''; ?>>
                <i class="bi bi-check2" aria-hidden="true"></i>
                Mark all as read
            </button>
        </form>
    </header>

    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="af-notifications-workspace">
        <section class="af-menu-table-card af-notifications-card">
            <nav class="af-notification-tabs" aria-label="Notification filters">
                <a class="<?php echo $typeFilter === '' && $statusFilter === '' ? 'active' : ''; ?>" href="notifications.php">All <span><?php echo htmlspecialchars((string) $totalNotifications, ENT_QUOTES, 'UTF-8'); ?></span></a>
                <a class="<?php echo $statusFilter === 'unread' ? 'active' : ''; ?>" href="notifications.php?status=unread">Unread <span><?php echo htmlspecialchars((string) $unreadNotifications, ENT_QUOTES, 'UTF-8'); ?></span></a>
                <?php foreach (['Order', 'Booking', 'Enquiry', 'System', 'Security'] as $type): ?>
                    <a class="<?php echo $typeFilter === $type ? 'active' : ''; ?>" href="notifications.php?type=<?php echo urlencode($type); ?>">
                        <?php echo htmlspecialchars($type === 'Enquiry' ? 'Enquiries' : $type . 's', ENT_QUOTES, 'UTF-8'); ?>
                        <span><?php echo htmlspecialchars((string) ($typeCounts[$type] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="af-notification-list">
                <?php if ($notifications === []): ?>
                    <div class="af-empty-state">No notifications found.</div>
                <?php endif; ?>

                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $type = (string) ($notification['notification_type'] ?? 'System');
                    $isUnread = (int) ($notification['is_read'] ?? 0) === 0;
                    $actionUrl = afrisense_customer_notification_url((string) ($notification['action_url'] ?? ''));
                    ?>
                    <article class="af-notification-item <?php echo $isUnread ? 'unread' : ''; ?>">
                        <span class="af-notification-icon <?php echo htmlspecialchars(afrisense_customer_notification_tone($type), ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi <?php echo htmlspecialchars(afrisense_customer_notification_icon($type), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                        </span>
                        <i class="af-unread-dot" aria-hidden="true"></i>
                        <div>
                            <h2><?php echo htmlspecialchars((string) ($notification['title'] ?? 'Notification'), ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p><?php echo htmlspecialchars((string) ($notification['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <strong class="af-notification-type <?php echo htmlspecialchars(afrisense_customer_notification_tone($type), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>
                        </strong>
                        <time datetime="<?php echo htmlspecialchars((string) ($notification['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars(afrisense_customer_time_ago((string) ($notification['created_at'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                        </time>
                        <div class="af-customer-notification-actions">
                            <?php if ($actionUrl !== '#'): ?>
                                <form action="notifications.php" method="post">
                                    <input type="hidden" name="action" value="open">
                                    <input type="hidden" name="notification_id" value="<?php echo htmlspecialchars((string) ($notification['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" title="Open notification" aria-label="Open notification"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if ($isUnread): ?>
                                <form action="notifications.php" method="post">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="notification_id" value="<?php echo htmlspecialchars((string) ($notification['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" aria-label="Mark as read"><i class="bi bi-check2" aria-hidden="true"></i></button>
                                </form>
                            <?php endif; ?>
                            <form action="notifications.php" method="post">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="notification_id" value="<?php echo htmlspecialchars((string) ($notification['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" aria-label="Delete notification"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="af-notifications-side">
            <section class="af-menu-panel">
                <h2><i class="bi bi-funnel" aria-hidden="true"></i> Filter Notifications</h2>
                <form class="af-food-management-form" action="notifications.php" method="get">
                    <label>
                        <span>Type</span>
                        <select name="type">
                            <option value="">All Types</option>
                            <?php foreach ($validTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $typeFilter === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Status</span>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="unread" <?php echo $statusFilter === 'unread' ? 'selected' : ''; ?>>Unread</option>
                            <option value="read" <?php echo $statusFilter === 'read' ? 'selected' : ''; ?>>Read</option>
                        </select>
                    </label>
                    <button type="submit"><i class="bi bi-funnel" aria-hidden="true"></i> Apply Filters</button>
                    <a class="af-clear-filters" href="notifications.php">Clear Filters</a>
                </form>
            </section>

            <section class="af-menu-panel">
                <h2>Notification Summary</h2>
                <div class="af-notification-donut">
                    <strong><?php echo htmlspecialchars((string) $totalNotifications, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span>Total</span>
                </div>
                <ul class="af-overview-list">
                    <li><i class="main"></i>Orders <span><?php echo htmlspecialchars((string) ($typeCounts['Order'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span></li>
                    <li><i class="drinks"></i>Bookings <span><?php echo htmlspecialchars((string) ($typeCounts['Booking'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span></li>
                    <li><i class="local"></i>Enquiries <span><?php echo htmlspecialchars((string) ($typeCounts['Enquiry'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span></li>
                    <li><i class="grills"></i>System <span><?php echo htmlspecialchars((string) ($typeCounts['System'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span></li>
                    <li><i class="starters"></i>Security <span><?php echo htmlspecialchars((string) ($typeCounts['Security'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span></li>
                </ul>
            </section>
        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/customer_layout.php';
?>
