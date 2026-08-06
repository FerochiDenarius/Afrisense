<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Orders | AfriSense';
$adminTitle = 'Orders';
$adminSearchPlaceholder = 'Search orders, customers, ID...';
$activeAdminPage = 'orders_all';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/public_settings.php';

$ordersCssPath = __DIR__ . '/../assets/css/admin-orders.css';
// Guard this block so it only runs when the required condition is met.
if (is_file($ordersCssPath)) {
    $extraStyles[] = $frontendBase . '/assets/css/admin-orders.css?v=' . filemtime($ordersCssPath);
}

$adminUser = afrisense_require_admin();
$adminUserId = (int) ($adminUser['id'] ?? 0);
$itemsPerPage = min(8, afrisense_admin_items_per_page());

// CSS badge tones are intentionally normalized here so database status labels
// can stay human-readable while classes remain predictable.
function afrisense_order_status_class(string $status): string
{
    return match (strtolower($status)) {
        'pending' => 'pending',
        'confirmed' => 'confirmed',
        'preparing' => 'preparing',
        'ready', 'out for delivery' => 'ready',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
        default => 'pending',
    };
}

// Defines the afrisense_payment_status_class helper used by this module.
function afrisense_payment_status_class(string $status): string
{
    return match (strtolower($status)) {
        'paid' => 'paid',
        'failed', 'refunded' => 'failed',
        default => 'pending',
    };
}

// Defines the afrisense_order_image helper used by this module.
function afrisense_order_image(string $frontendBase, ?string $image): string
{
    $image = trim((string) $image);
    $filename = basename($image);

    // Guard this block so it only runs when the required condition is met.
    if ($image !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
        return $frontendBase . '/assets/images/foods/' . $filename;
    }

    // Guard this block so it only runs when the required condition is met.
    if ($image !== '' && is_file(__DIR__ . '/../uploads/' . $filename)) {
        return $frontendBase . '/uploads/' . $filename;
    }

    return $frontendBase . '/assets/images/foods/jollof-rice.png';
}

// Defines the afrisense_count_orders helper used by this module.
function afrisense_count_orders(PDO $pdo, ?string $status = null): int
{
    // Guard this block so it only runs when the required condition is met.
    if ($status === null) {
        $statement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `orders`');
        $statement->execute();
    } else {
        $statement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `orders` WHERE `order_status` = :status');
        $statement->execute(['status' => $status]);
    }

    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['count_value'] ?? 0);
}

// Defines the afrisense_order_url helper used by this module.
function afrisense_order_url(array $overrides = [], string $anchor = ''): string
{
    // Preserve current filters/search/page while changing one query parameter.
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

    return 'orders.php' . ($query !== '' ? '?' . $query : '') . $anchor;
}

// Defines the afrisense_order_status_actions helper used by this module.
function afrisense_order_status_actions(string $status): array
{
    // Allowed order transitions for the admin action buttons. Keeping this as
    // data avoids scattering workflow rules through the HTML table.
    return match ($status) {
        'Pending' => [
            ['status' => 'Confirmed', 'icon' => 'bi-check2', 'label' => 'Confirm order', 'class' => 'success'],
            ['status' => 'Cancelled', 'icon' => 'bi-x-lg', 'label' => 'Cancel order', 'class' => 'danger'],
        ],
        'Confirmed' => [
            ['status' => 'Preparing', 'icon' => 'bi-egg-fried', 'label' => 'Send to kitchen', 'class' => 'warning'],
            ['status' => 'Cancelled', 'icon' => 'bi-x-lg', 'label' => 'Cancel order', 'class' => 'danger'],
        ],
        'Preparing' => [
            ['status' => 'Ready', 'icon' => 'bi-bag-check', 'label' => 'Mark ready', 'class' => 'success'],
            ['status' => 'Cancelled', 'icon' => 'bi-x-lg', 'label' => 'Cancel order', 'class' => 'danger'],
        ],
        'Ready' => [
            ['status' => 'Out for Delivery', 'icon' => 'bi-truck', 'label' => 'Send out for delivery', 'class' => 'warning'],
            ['status' => 'Delivered', 'icon' => 'bi-check2-circle', 'label' => 'Mark delivered', 'class' => 'success'],
        ],
        'Out for Delivery' => [
            ['status' => 'Delivered', 'icon' => 'bi-check2-circle', 'label' => 'Mark delivered', 'class' => 'success'],
            ['status' => 'Cancelled', 'icon' => 'bi-x-lg', 'label' => 'Cancel order', 'class' => 'danger'],
        ],
        'Cancelled' => [
            ['status' => 'Pending', 'icon' => 'bi-arrow-counterclockwise', 'label' => 'Reopen order', 'class' => 'warning'],
        ],
        default => [],
    };
}

// Defines the afrisense_order_payment_actions helper used by this module.
function afrisense_order_payment_actions(string $status): array
{
    // Payment transitions are separate from kitchen/delivery transitions so COD
    // and online payment flows can be managed independently.
    return match ($status) {
        'Pending' => [
            ['status' => 'Paid', 'icon' => 'bi-cash-coin', 'label' => 'Mark payment paid', 'class' => 'success'],
            ['status' => 'Failed', 'icon' => 'bi-exclamation-triangle', 'label' => 'Mark payment failed', 'class' => 'danger'],
        ],
        'Paid' => [
            ['status' => 'Refunded', 'icon' => 'bi-arrow-counterclockwise', 'label' => 'Mark payment refunded', 'class' => 'warning'],
        ],
        'Failed' => [
            ['status' => 'Paid', 'icon' => 'bi-cash-coin', 'label' => 'Mark payment paid', 'class' => 'success'],
        ],
        default => [],
    };
}

// Defines the afrisense_order_action_form helper used by this module.
function afrisense_order_action_form(int $orderId, string $action, string $field, array $config): string
{
    // Action buttons are rendered as tiny forms so each state change remains a
    // normal POST, while title/aria-label give hover tooltips and accessibility.
    $class = trim((string) ($config['class'] ?? ''));
    $classAttribute = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';

    return sprintf(
        '<form action="orders.php" method="post"><input type="hidden" name="action" value="%s"><input type="hidden" name="order_id" value="%d"><input type="hidden" name="%s" value="%s"><button%s type="submit" title="%s" aria-label="%s"><i class="bi %s" aria-hidden="true"></i><span>%s</span></button></form>',
        htmlspecialchars($action, ENT_QUOTES, 'UTF-8'),
        $orderId,
        htmlspecialchars($field, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string) $config['status'], ENT_QUOTES, 'UTF-8'),
        $classAttribute,
        htmlspecialchars((string) $config['label'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string) $config['label'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string) $config['icon'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string) $config['label'], ENT_QUOTES, 'UTF-8')
    );
}

// Defines the afrisense_order_customer_user_id helper used by this module.
function afrisense_order_customer_user_id(PDO $pdo, int $orderId): ?int
{
    // Guest orders may not have a user account, but registered customers can be
    // matched through the customer contact record for dashboard notifications.
    $statement = $pdo->prepare(
        'SELECT u.`id`
         FROM `orders` o
         INNER JOIN `customers` c ON c.`id` = o.`customer_id`
         INNER JOIN `users` u
            ON u.`email` = c.`email`
            OR REPLACE(u.`phonenumber`, " ", "") = REPLACE(c.`phone_number`, " ", "")
         WHERE o.`id` = :order_id
         ORDER BY u.`id` ASC
         LIMIT 1'
    );
    $statement->execute(['order_id' => $orderId]);
    $userId = $statement->fetchColumn();

    return $userId !== false ? (int) $userId : null;
}

// Defines the afrisense_order_customer_contact helper used by this module.
function afrisense_order_customer_contact(PDO $pdo, int $orderId): ?array
{
    // Central contact lookup for both email and notification messages.
    $statement = $pdo->prepare(
        'SELECT
            o.`id`,
            o.`order_status`,
            o.`payment_status`,
            o.`payment_method`,
            o.`total_price`,
            o.`ordered_at`,
            c.`fullname`,
            c.`email`,
            f.`food_name`
         FROM `orders` o
         INNER JOIN `customers` c ON c.`id` = o.`customer_id`
         INNER JOIN `foods` f ON f.`id` = o.`food_id`
         WHERE o.`id` = :order_id
         LIMIT 1'
    );
    $statement->execute(['order_id' => $orderId]);
    $contact = $statement->fetch(PDO::FETCH_ASSOC);

    return $contact !== false ? $contact : null;
}

// Defines the afrisense_order_email_customer helper used by this module.
function afrisense_order_email_customer(array $contact, string $title, string $message): array
{
    $email = trim((string) ($contact['email'] ?? ''));

    // Email delivery is optional and controlled from system settings; failures
    // are returned to the caller instead of blocking the order status update.
    if (!afrisense_public_setting_bool('email_notifications', true)) {
        return ['success' => false, 'message' => 'Email notifications are disabled in settings.'];
    }

    // Guard this block so it only runs when the required condition is met.
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Customer email is missing or invalid.'];
    }

    // Guard this block so it only runs when the required condition is met.
    if (!function_exists('afrisense_send_email')) {
        return ['success' => false, 'message' => 'Email sender is unavailable.'];
    }

    $customerName = trim((string) ($contact['fullname'] ?? 'Customer'));
    $safeName = htmlspecialchars($customerName !== '' ? $customerName : 'Customer', ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeOrderId = htmlspecialchars(str_pad((string) ($contact['id'] ?? 0), 5, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8');
    $safeFoodName = htmlspecialchars((string) ($contact['food_name'] ?? 'Your order'), ENT_QUOTES, 'UTF-8');
    $safePayment = htmlspecialchars((string) ($contact['payment_method'] ?? 'Cash'), ENT_QUOTES, 'UTF-8');
    $safeStatus = htmlspecialchars((string) ($contact['order_status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8');
    $safeTotal = htmlspecialchars(afrisense_public_money((float) ($contact['total_price'] ?? 0)), ENT_QUOTES, 'UTF-8');
    $html = <<<HTML
        <h2>{$safeTitle}</h2>
        <p>Hello {$safeName},</p>
        <p>{$safeMessage}</p>
        <table role="presentation" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:18px 0;width:100%;max-width:520px;">
            <tr><td style="padding:8px 0;color:#667085;">Order ID</td><td style="padding:8px 0;font-weight:700;">#{$safeOrderId}</td></tr>
            <tr><td style="padding:8px 0;color:#667085;">Item</td><td style="padding:8px 0;font-weight:700;">{$safeFoodName}</td></tr>
            <tr><td style="padding:8px 0;color:#667085;">Status</td><td style="padding:8px 0;font-weight:700;">{$safeStatus}</td></tr>
            <tr><td style="padding:8px 0;color:#667085;">Payment</td><td style="padding:8px 0;font-weight:700;">{$safePayment}</td></tr>
            <tr><td style="padding:8px 0;color:#667085;">Total</td><td style="padding:8px 0;font-weight:700;">{$safeTotal}</td></tr>
        </table>
        <p>Thank you for ordering from AfriSense.</p>
    HTML;
    $text = $title . "\n\nHello " . ($customerName !== '' ? $customerName : 'Customer') . ",\n\n" . $message . "\n\nOrder #" . str_pad((string) ($contact['id'] ?? 0), 5, '0', STR_PAD_LEFT) . "\nItem: " . (string) ($contact['food_name'] ?? 'Your order') . "\nStatus: " . (string) ($contact['order_status'] ?? 'Pending') . "\nPayment: " . (string) ($contact['payment_method'] ?? 'Cash') . "\nTotal: " . afrisense_public_money((float) ($contact['total_price'] ?? 0));

    return afrisense_send_email($email, $customerName, $title, $html, $text);
}

// Defines the afrisense_order_notify_customer helper used by this module.
function afrisense_order_notify_customer(PDO $pdo, int $orderId, string $title, string $message, int $createdBy): array
{
    // Guard this block so it only runs when the required condition is met.
    if (!afrisense_public_setting_bool('order_notifications', true)) {
        return ['success' => false, 'message' => 'Order notifications are disabled in settings.'];
    }

    // Notification and email share the same message content, but account
    // notifications are skipped when the order came from a pure guest checkout.
    $userId = afrisense_order_customer_user_id($pdo, $orderId);
    $contact = afrisense_order_customer_contact($pdo, $orderId);

    // Guard this block so it only runs when the required condition is met.
    if ($userId !== null && $userId > 0) {
        $statement = $pdo->prepare(
            'INSERT INTO `notifications`
                (`user_id`, `title`, `message`, `notification_type`, `action_url`, `created_by`)
             VALUES
                (:user_id, :title, :message, :notification_type, :action_url, :created_by)'
        );
        $statement->execute([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'notification_type' => 'Order',
            'action_url' => '/Afrisense/frontend/customer/my-orders.php?view=' . $orderId . '#order-details',
            'created_by' => $createdBy > 0 ? $createdBy : null,
        ]);
    }

    // Guard this block so it only runs when the required condition is met.
    if ($contact === null) {
        return ['success' => false, 'message' => 'Order customer details could not be found.'];
    }

    return afrisense_order_email_customer($contact, $title, $message);
}

$validStatuses = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Out for Delivery', 'Delivered', 'Cancelled'];
$validPaymentStatuses = ['Pending', 'Paid', 'Failed', 'Refunded'];
$statusFilter = (string) ($_GET['status'] ?? '');
$search = trim((string) ($_GET['search'] ?? ''));
$viewOrderId = (int) ($_GET['view'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $itemsPerPage;
$flashMessage = '';
$flashType = 'success';
$activeAdminPage = match ($statusFilter) {
    'Pending' => 'orders_pending',
    'Confirmed' => 'orders_confirmed',
    'Preparing' => 'orders_preparing',
    'Delivered' => 'orders_delivered',
    'Cancelled' => 'orders_cancelled',
    default => 'orders_all',
};

// Run database/action work inside a guarded block so the page can fail gracefully.
try {
    $pdo = afrisense_pdo();
    $where = [];
    $params = [];

    // Handle submitted form actions before rendering the page.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $orderId = (int) ($_POST['order_id'] ?? 0);

        // Guard this block so it only runs when the required condition is met.
        if ($action === 'update_order_status') {
            $nextStatus = (string) ($_POST['order_status'] ?? '');

            // Guard this block so it only runs when the required condition is met.
            if ($orderId <= 0 || !in_array($nextStatus, $validStatuses, true)) {
                $flashType = 'error';
                $flashMessage = 'Order status could not be updated.';
            } else {
                $update = $pdo->prepare(
                    'UPDATE `orders`
                     SET `order_status` = :order_status,
                         `updated_at` = NOW()
                     WHERE `id` = :id'
                );
                $update->execute([
                    'order_status' => $nextStatus,
                    'id' => $orderId,
                ]);

                $flashMessage = $update->rowCount() > 0
                    ? 'Order #' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) . ' updated to ' . $nextStatus . '.'
                    : 'Order was not changed.';

                // Guard this block so it only runs when the required condition is met.
                if ($update->rowCount() > 0) {
                    $emailResult = afrisense_order_notify_customer(
                        $pdo,
                        $orderId,
                        'Order Status Updated',
                        'Your order #' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) . ' is now ' . $nextStatus . '.',
                        $adminUserId
                    );
                    // Guard this block so it only runs when the required condition is met.
                    if (!$emailResult['success']) {
                        $flashMessage .= ' Email not sent: ' . (string) ($emailResult['message'] ?? 'Unknown email error.');
                    }
                }
            }
        }

        // Guard this block so it only runs when the required condition is met.
        if ($action === 'update_payment_status') {
            $nextPaymentStatus = (string) ($_POST['payment_status'] ?? '');

            // Guard this block so it only runs when the required condition is met.
            if ($orderId <= 0 || !in_array($nextPaymentStatus, $validPaymentStatuses, true)) {
                $flashType = 'error';
                $flashMessage = 'Payment status could not be updated.';
            } else {
                $update = $pdo->prepare(
                    'UPDATE `orders`
                     SET `payment_status` = :payment_status,
                         `updated_at` = NOW()
                     WHERE `id` = :id'
                );
                $update->execute([
                    'payment_status' => $nextPaymentStatus,
                    'id' => $orderId,
                ]);

                $flashMessage = $update->rowCount() > 0
                    ? 'Payment for order #' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) . ' updated to ' . $nextPaymentStatus . '.'
                    : 'Payment was not changed.';

                // Guard this block so it only runs when the required condition is met.
                if ($update->rowCount() > 0) {
                    $emailResult = afrisense_order_notify_customer(
                        $pdo,
                        $orderId,
                        'Payment Status Updated',
                        'Payment for your order #' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) . ' is now ' . $nextPaymentStatus . '.',
                        $adminUserId
                    );
                    // Guard this block so it only runs when the required condition is met.
                    if (!$emailResult['success']) {
                        $flashMessage .= ' Email not sent: ' . (string) ($emailResult['message'] ?? 'Unknown email error.');
                    }
                }
            }
        }
    }

    // Guard this block so it only runs when the required condition is met.
    if (in_array($statusFilter, $validStatuses, true)) {
        $where[] = 'o.`order_status` = :status';
        $params['status'] = $statusFilter;
    }

    // Guard this block so it only runs when the required condition is met.
    if ($search !== '') {
        $where[] = '(CAST(o.`id` AS CHAR) LIKE :search OR c.`fullname` LIKE :search OR c.`email` LIKE :search OR c.`phone_number` LIKE :search OR f.`food_name` LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $fromSql = ' FROM `orders` o
            INNER JOIN `customers` c ON c.`id` = o.`customer_id`
            INNER JOIN `foods` f ON f.`id` = o.`food_id`';
    $whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

    $countStatement = $pdo->prepare('SELECT COUNT(*)' . $fromSql . $whereSql);
    $countStatement->execute($params);
    $filteredOrderCount = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($filteredOrderCount / max(1, $itemsPerPage)));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $itemsPerPage;

    $sql = 'SELECT
                o.`id`,
                o.`quantity`,
                o.`total_price`,
                o.`payment_method`,
                o.`payment_status`,
                o.`order_status`,
                o.`ordered_at`,
                o.`delivery_address`,
                o.`special_instructions`,
                c.`fullname`,
                c.`email`,
                c.`phone_number`,
                f.`food_name`,
                f.`image`
            ' . $fromSql . $whereSql . '
            ORDER BY o.`ordered_at` DESC, o.`id` DESC
            LIMIT ' . $itemsPerPage . ' OFFSET ' . $offset;
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $orders = $statement->fetchAll(PDO::FETCH_ASSOC);
    $selectedOrder = null;

    // Guard this block so it only runs when the required condition is met.
    if ($viewOrderId > 0) {
        $selectedStatement = $pdo->prepare(
            'SELECT
                o.`id`,
                o.`quantity`,
                o.`total_price`,
                o.`payment_method`,
                o.`payment_status`,
                o.`order_status`,
                o.`ordered_at`,
                o.`delivery_address`,
                o.`special_instructions`,
                c.`fullname`,
                c.`email`,
                c.`phone_number`,
                f.`food_name`
             FROM `orders` o
             INNER JOIN `customers` c ON c.`id` = o.`customer_id`
             INNER JOIN `foods` f ON f.`id` = o.`food_id`
             WHERE o.`id` = :id
             LIMIT 1'
        );
        $selectedStatement->execute(['id' => $viewOrderId]);
        $selectedOrder = $selectedStatement->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $totalOrders = afrisense_count_orders($pdo);
    $pendingOrders = afrisense_count_orders($pdo, 'Pending');
    $preparingOrders = afrisense_count_orders($pdo, 'Preparing');
    $deliveredOrders = afrisense_count_orders($pdo, 'Delivered');
    $cancelledOrders = afrisense_count_orders($pdo, 'Cancelled');
    $confirmedOrders = afrisense_count_orders($pdo, 'Confirmed');
    $adminOrderNavCounts = [
        'orders_pending' => $pendingOrders,
        'orders_confirmed' => $confirmedOrders,
        'orders_preparing' => $preparingOrders,
        'orders_delivered' => $deliveredOrders,
        'orders_cancelled' => $cancelledOrders,
    ];
    $loadError = '';
} catch (Throwable $exception) {
    $orders = [];
    $totalOrders = 0;
    $filteredOrderCount = 0;
    $totalPages = 1;
    $page = 1;
    $offset = 0;
    $pendingOrders = 0;
    $preparingOrders = 0;
    $deliveredOrders = 0;
    $cancelledOrders = 0;
    $confirmedOrders = 0;
    $adminOrderNavCounts = [
        'orders_pending' => 0,
        'orders_confirmed' => 0,
        'orders_preparing' => 0,
        'orders_delivered' => 0,
        'orders_cancelled' => 0,
    ];
    $selectedOrder = null;
    $loadError = 'Orders could not be loaded. Check that MySQL is running.';
}

ob_start();
?>
<!-- Page section for this part of the AfriSense interface. -->
<section class="af-admin-menu-page af-orders-page">
    <!-- Header block for this interface section. -->
    <header class="af-admin-page-heading">
        <div>
            <h1>All Orders</h1>
            <p>Dashboard / Orders / All Orders</p>
        </div>
        <a class="af-add-menu-btn af-new-order-btn" href="<?php echo htmlspecialchars($frontendBase . '/admin/new-order.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            New Order
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
    <section class="af-menu-metrics af-order-metrics" aria-label="Order summary">
        <article class="green">
            <span><i class="bi bi-cart3" aria-hidden="true"></i></span>
            <div><small>Total Orders</small><strong><?php echo htmlspecialchars((string) $totalOrders, ENT_QUOTES, 'UTF-8'); ?></strong><p>This month</p></div>
        </article>
        <article class="gold">
            <span><i class="bi bi-clock" aria-hidden="true"></i></span>
            <div><small>Pending</small><strong><?php echo htmlspecialchars((string) $pendingOrders, ENT_QUOTES, 'UTF-8'); ?></strong><p>Awaiting confirmation</p></div>
        </article>
        <article class="blue">
            <span><i class="bi bi-egg-fried" aria-hidden="true"></i></span>
            <div><small>Preparing</small><strong><?php echo htmlspecialchars((string) $preparingOrders, ENT_QUOTES, 'UTF-8'); ?></strong><p>In kitchen</p></div>
        </article>
        <article class="purple">
            <span><i class="bi bi-truck" aria-hidden="true"></i></span>
            <div><small>Delivered</small><strong><?php echo htmlspecialchars((string) $deliveredOrders, ENT_QUOTES, 'UTF-8'); ?></strong><p>Completed</p></div>
        </article>
        <article class="red">
            <span><i class="bi bi-x-circle" aria-hidden="true"></i></span>
            <div><small>Cancelled</small><strong><?php echo htmlspecialchars((string) $cancelledOrders, ENT_QUOTES, 'UTF-8'); ?></strong><p>This month</p></div>
        </article>
    </section>

    <!-- Page section for this part of the AfriSense interface. -->
    <section class="af-orders-workspace">
        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-menu-table-card" id="orders-table">
            <!-- Form block that submits this page workflow. -->
            <form class="af-menu-filters af-orders-filters" action="orders.php" method="get">
                <label class="af-menu-search" for="order_search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="order_search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by order ID, customer, email, phone...">
                </label>
                <label class="af-menu-select" for="order_status_filter">
                    <span>Status</span>
                    <select id="order_status_filter" name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <?php // Render this conditional/dynamic template block. ?>
                        <?php foreach ($validStatuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
                <label class="af-menu-select" for="order_date_range">
                    <span>Date Range</span>
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <select id="order_date_range" name="range" onchange="this.form.submit()">
                        <option>May 18, 2025 - May 24, 2025</option>
                        <option>This Month</option>
                        <option>This Week</option>
                        <option>Today</option>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
                <button type="button"><i class="bi bi-download" aria-hidden="true"></i> Export</button>
            </form>

            <div class="af-menu-table af-orders-table">
                <!-- Table block for displaying structured records. -->
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Date &amp; Time</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php // Render this conditional/dynamic template block. ?>
                        <?php if ($orders === []): ?>
                            <tr>
                                <td colspan="9"><div class="af-empty-state">No orders found.</div></td>
                            </tr>
                        <?php endif; ?>
                        <?php // Render this conditional/dynamic template block. ?>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $orderedAt = strtotime((string) ($order['ordered_at'] ?? '')) ?: time();
                            $orderStatus = (string) ($order['order_status'] ?? 'Pending');
                            $paymentStatus = (string) ($order['payment_status'] ?? 'Pending');
                            $paymentMethod = (string) ($order['payment_method'] ?? 'Cash');
                            $paymentLabel = strtolower($paymentMethod) === 'cash' && strtolower($paymentStatus) === 'pending'
                                ? 'Cash on Delivery'
                                : $paymentStatus;
                            $paymentClass = afrisense_payment_status_class($paymentStatus) . (strtolower($paymentMethod) === 'cash' ? ' cash' : '');
                            ?>
                            <?php $isSelectedOrder = (int) ($order['id'] ?? 0) === $viewOrderId; ?>
                            <tr id="order-row-<?php echo htmlspecialchars((string) ($order['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $isSelectedOrder ? 'is-selected' : ''; ?>">
                                <td><strong class="af-order-id">ORD-<?php echo htmlspecialchars(str_pad((string) ($order['id'] ?? 0), 5, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td>
                                    <span class="af-order-customer">
                                        <strong><?php echo htmlspecialchars((string) ($order['fullname'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <small><?php echo htmlspecialchars((string) ($order['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </span>
                                </td>
                                <td>
                                    <?php // Render this conditional/dynamic template block. ?>
                                    <?php if (trim((string) ($order['email'] ?? '')) !== ''): ?>
                                        <a class="af-order-email" href="mailto:<?php echo htmlspecialchars((string) ($order['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($order['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a>
                                    <?php else: ?>
                                        <span class="af-order-email muted">No email</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="af-order-date">
                                        <strong><?php echo htmlspecialchars(date('M j, Y', $orderedAt), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <small><?php echo htmlspecialchars(date('h:i A', $orderedAt), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </span>
                                </td>
                                <td>
                                    <div class="af-order-item-cell">
                                        <img src="<?php echo htmlspecialchars(afrisense_order_image($frontendBase, (string) ($order['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                                        <span>
                                            <strong><?php echo htmlspecialchars((string) ($order['food_name'] ?? 'Food item'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <small><?php echo htmlspecialchars((string) ($order['quantity'] ?? 1), ENT_QUOTES, 'UTF-8'); ?> item(s)</small>
                                        </span>
                                    </div>
                                </td>
                                <td>GH₵ <?php echo htmlspecialchars(number_format((float) ($order['total_price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="af-order-status <?php echo htmlspecialchars(afrisense_order_status_class($orderStatus), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($orderStatus, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><span class="af-payment-status <?php echo htmlspecialchars($paymentClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($paymentLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td>
                                    <div class="af-row-actions af-order-row-actions">
                                        <a href="orders.php?view=<?php echo htmlspecialchars((string) ($order['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>#order-row-<?php echo htmlspecialchars((string) ($order['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" title="View order details" aria-label="View order <?php echo htmlspecialchars((string) ($order['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                        <?php // Render this conditional/dynamic template block. ?>
                                        <?php foreach (afrisense_order_status_actions($orderStatus) as $actionConfig): ?>
                                            <?php echo afrisense_order_action_form((int) ($order['id'] ?? 0), 'update_order_status', 'order_status', $actionConfig); ?>
                                        <?php endforeach; ?>
                                        <?php // Render this conditional/dynamic template block. ?>
                                        <?php foreach (afrisense_order_payment_actions($paymentStatus) as $actionConfig): ?>
                                            <?php echo afrisense_order_action_form((int) ($order['id'] ?? 0), 'update_payment_status', 'payment_status', $actionConfig); ?>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer block for this interface section. -->
            <footer class="af-menu-pagination">
                <p>Showing <?php echo htmlspecialchars((string) ($filteredOrderCount > 0 ? $offset + 1 : 0), ENT_QUOTES, 'UTF-8'); ?> to <?php echo htmlspecialchars((string) min($offset + count($orders), $filteredOrderCount), ENT_QUOTES, 'UTF-8'); ?> of <?php echo htmlspecialchars((string) $filteredOrderCount, ENT_QUOTES, 'UTF-8'); ?> orders</p>
                <!-- Navigation links for this interface. -->
                <nav aria-label="Orders pagination">
                    <a class="<?php echo $page <= 1 ? 'is-disabled' : ''; ?>" href="<?php echo htmlspecialchars($page <= 1 ? '#' : afrisense_order_url(['page' => (string) ($page - 1), 'view' => null], '#orders-table'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Previous page" title="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                    <?php // Render this conditional/dynamic template block. ?>
                    <?php for ($number = max(1, $page - 1); $number <= min($totalPages, $page + 1); $number++): ?>
                        <a class="<?php echo $number === $page ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(afrisense_order_url(['page' => (string) $number, 'view' => null], '#orders-table'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $number, ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endfor; ?>
                    <?php // Render this conditional/dynamic template block. ?>
                    <?php if ($totalPages > $page + 1): ?>
                        <span>...</span>
                        <a href="<?php echo htmlspecialchars(afrisense_order_url(['page' => (string) $totalPages, 'view' => null], '#orders-table'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $totalPages, ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endif; ?>
                    <a class="<?php echo $page >= $totalPages ? 'is-disabled' : ''; ?>" href="<?php echo htmlspecialchars($page >= $totalPages ? '#' : afrisense_order_url(['page' => (string) ($page + 1), 'view' => null], '#orders-table'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Next page" title="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                </nav>
            </footer>
        </section>

        <!-- Side panel with supporting information and actions. -->
        <aside class="af-orders-side">
            <?php // Render this conditional/dynamic template block. ?>
            <?php if ($selectedOrder !== null): ?>
                <!-- Page section for this part of the AfriSense interface. -->
                <section class="af-menu-panel af-selected-order-panel" id="order-details">
                    <h2>Order Details</h2>
                    <strong>ORD-<?php echo htmlspecialchars(str_pad((string) ($selectedOrder['id'] ?? 0), 5, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <dl>
                        <div><dt>Customer</dt><dd><?php echo htmlspecialchars((string) ($selectedOrder['fullname'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div><dt>Email</dt><dd><?php if (trim((string) ($selectedOrder['email'] ?? '')) !== ''): ?><a href="mailto:<?php echo htmlspecialchars((string) ($selectedOrder['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($selectedOrder['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a><?php else: ?>No email<?php endif; ?></dd></div>
                        <div><dt>Phone</dt><dd><?php echo htmlspecialchars((string) ($selectedOrder['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div><dt>Item</dt><dd><?php echo htmlspecialchars((string) ($selectedOrder['food_name'] ?? 'Food item'), ENT_QUOTES, 'UTF-8'); ?> x <?php echo htmlspecialchars((string) ($selectedOrder['quantity'] ?? 1), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div><dt>Amount</dt><dd>GH₵ <?php echo htmlspecialchars(number_format((float) ($selectedOrder['total_price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div><dt>Status</dt><dd><?php echo htmlspecialchars((string) ($selectedOrder['order_status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div><dt>Payment</dt><dd><?php echo htmlspecialchars((string) ($selectedOrder['payment_status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars((string) ($selectedOrder['payment_method'] ?? 'Cash'), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    </dl>
                    <p><b>Delivery:</b> <?php echo htmlspecialchars((string) ($selectedOrder['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php // Render this conditional/dynamic template block. ?>
                    <?php if (trim((string) ($selectedOrder['special_instructions'] ?? '')) !== ''): ?>
                        <p><b>Note:</b> <?php echo htmlspecialchars((string) ($selectedOrder['special_instructions'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-menu-panel">
                <h2>Order Pipeline</h2>
                <ul class="af-order-pipeline">
                    <li><span class="pending"></span>Pending <strong><?php echo htmlspecialchars((string) $pendingOrders, ENT_QUOTES, 'UTF-8'); ?></strong></li>
                    <li><span class="confirmed"></span>Confirmed <strong><?php echo htmlspecialchars((string) $confirmedOrders, ENT_QUOTES, 'UTF-8'); ?></strong></li>
                    <li><span class="preparing"></span>Preparing <strong><?php echo htmlspecialchars((string) $preparingOrders, ENT_QUOTES, 'UTF-8'); ?></strong></li>
                    <li><span class="delivered"></span>Delivered <strong><?php echo htmlspecialchars((string) $deliveredOrders, ENT_QUOTES, 'UTF-8'); ?></strong></li>
                    <li><span class="cancelled"></span>Cancelled <strong><?php echo htmlspecialchars((string) $cancelledOrders, ENT_QUOTES, 'UTF-8'); ?></strong></li>
                </ul>
            </section>

            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-menu-panel af-food-help">
                <h2><i class="bi bi-question-circle" aria-hidden="true"></i> Help</h2>
                <p>Orders use the existing `orders` table. Each order currently contains one food item and quantity.</p>
                <a href="#">View Order Guide <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </section>
        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
