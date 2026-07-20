<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Orders | AfriSense';
$adminTitle = 'Orders';
$activeAdminPage = 'orders_all';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

$adminUser = afrisense_require_admin();
$adminUserId = (int) ($adminUser['id'] ?? 0);

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

function afrisense_payment_status_class(string $status): string
{
    return match (strtolower($status)) {
        'paid' => 'paid',
        'failed', 'refunded' => 'failed',
        default => 'pending',
    };
}

function afrisense_order_image(string $frontendBase, ?string $image): string
{
    $image = trim((string) $image);
    $filename = basename($image);

    if ($image !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
        return $frontendBase . '/assets/images/foods/' . $filename;
    }

    if ($image !== '' && is_file(__DIR__ . '/../uploads/' . $filename)) {
        return $frontendBase . '/uploads/' . $filename;
    }

    return $frontendBase . '/assets/images/foods/jollof-rice.png';
}

function afrisense_count_orders(PDO $pdo, ?string $status = null): int
{
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

function afrisense_order_status_actions(string $status): array
{
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

function afrisense_order_payment_actions(string $status): array
{
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

function afrisense_order_action_form(int $orderId, string $action, string $field, array $config): string
{
    $class = trim((string) ($config['class'] ?? ''));
    $classAttribute = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';

    return sprintf(
        '<form action="orders.php" method="post"><input type="hidden" name="action" value="%s"><input type="hidden" name="order_id" value="%d"><input type="hidden" name="%s" value="%s"><button%s type="submit" title="%s" aria-label="%s"><i class="bi %s" aria-hidden="true"></i></button></form>',
        htmlspecialchars($action, ENT_QUOTES, 'UTF-8'),
        $orderId,
        htmlspecialchars($field, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string) $config['status'], ENT_QUOTES, 'UTF-8'),
        $classAttribute,
        htmlspecialchars((string) $config['label'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string) $config['label'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string) $config['icon'], ENT_QUOTES, 'UTF-8')
    );
}

function afrisense_order_customer_user_id(PDO $pdo, int $orderId): ?int
{
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

function afrisense_order_notify_customer(PDO $pdo, int $orderId, string $title, string $message, int $createdBy): void
{
    $userId = afrisense_order_customer_user_id($pdo, $orderId);

    if ($userId === null || $userId <= 0) {
        return;
    }

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

$validStatuses = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Out for Delivery', 'Delivered', 'Cancelled'];
$validPaymentStatuses = ['Pending', 'Paid', 'Failed', 'Refunded'];
$statusFilter = (string) ($_GET['status'] ?? '');
$search = trim((string) ($_GET['search'] ?? ''));
$viewOrderId = (int) ($_GET['view'] ?? 0);
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

try {
    $pdo = afrisense_pdo();
    $where = [];
    $params = [];

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $orderId = (int) ($_POST['order_id'] ?? 0);

        if ($action === 'update_order_status') {
            $nextStatus = (string) ($_POST['order_status'] ?? '');

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

                if ($update->rowCount() > 0) {
                    afrisense_order_notify_customer(
                        $pdo,
                        $orderId,
                        'Order Status Updated',
                        'Your order #' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) . ' is now ' . $nextStatus . '.',
                        $adminUserId
                    );
                }
            }
        }

        if ($action === 'update_payment_status') {
            $nextPaymentStatus = (string) ($_POST['payment_status'] ?? '');

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

                if ($update->rowCount() > 0) {
                    afrisense_order_notify_customer(
                        $pdo,
                        $orderId,
                        'Payment Status Updated',
                        'Payment for your order #' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) . ' is now ' . $nextPaymentStatus . '.',
                        $adminUserId
                    );
                }
            }
        }
    }

    if (in_array($statusFilter, $validStatuses, true)) {
        $where[] = 'o.`order_status` = :status';
        $params['status'] = $statusFilter;
    }

    if ($search !== '') {
        $where[] = '(CAST(o.`id` AS CHAR) LIKE :search OR c.`fullname` LIKE :search OR c.`phone_number` LIKE :search OR f.`food_name` LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

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
                c.`phone_number`,
                f.`food_name`,
                f.`image`
            FROM `orders` o
            INNER JOIN `customers` c ON c.`id` = o.`customer_id`
            INNER JOIN `foods` f ON f.`id` = o.`food_id`';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY o.`ordered_at` DESC, o.`id` DESC LIMIT 25';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $orders = $statement->fetchAll(PDO::FETCH_ASSOC);
    $selectedOrder = null;

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
    $loadError = '';
} catch (Throwable $exception) {
    $orders = [];
    $totalOrders = 0;
    $pendingOrders = 0;
    $preparingOrders = 0;
    $deliveredOrders = 0;
    $cancelledOrders = 0;
    $confirmedOrders = 0;
    $selectedOrder = null;
    $loadError = 'Orders could not be loaded. Check that MySQL is running.';
}

ob_start();
?>
<section class="af-admin-menu-page af-orders-page">
    <header class="af-admin-page-heading">
        <div>
            <h1>All Orders</h1>
            <p>Dashboard / Orders / All Orders</p>
        </div>
        <button class="af-add-menu-btn af-new-order-btn" type="button">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            New Order
        </button>
    </header>

    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

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

    <section class="af-orders-workspace">
        <section class="af-menu-table-card">
            <form class="af-menu-filters af-orders-filters" action="orders.php" method="get">
                <label class="af-menu-search" for="order_search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="order_search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by order ID, customer, phone...">
                </label>
                <label class="af-menu-select" for="order_status_filter">
                    <select id="order_status_filter" name="status">
                        <option value="">All Status</option>
                        <?php foreach ($validStatuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
                <label class="af-menu-select" for="order_date_range">
                    <select id="order_date_range" name="range">
                        <option>This Month</option>
                        <option>This Week</option>
                        <option>Today</option>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
                <button type="submit"><i class="bi bi-filter" aria-hidden="true"></i> Filter</button>
                <button type="button"><i class="bi bi-download" aria-hidden="true"></i> Export</button>
            </form>

            <div class="af-menu-table af-orders-table">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date &amp; Time</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders === []): ?>
                            <tr>
                                <td colspan="8"><div class="af-empty-state">No orders found.</div></td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $orderedAt = strtotime((string) ($order['ordered_at'] ?? '')) ?: time();
                            $orderStatus = (string) ($order['order_status'] ?? 'Pending');
                            $paymentStatus = (string) ($order['payment_status'] ?? 'Pending');
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
                                <td>GHc <?php echo htmlspecialchars(number_format((float) ($order['total_price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="af-order-status <?php echo htmlspecialchars(afrisense_order_status_class($orderStatus), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($orderStatus, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><span class="af-payment-status <?php echo htmlspecialchars(afrisense_payment_status_class($paymentStatus), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($paymentStatus, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td>
                                    <div class="af-row-actions af-order-row-actions">
                                        <a href="orders.php?view=<?php echo htmlspecialchars((string) ($order['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>#order-row-<?php echo htmlspecialchars((string) ($order['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" aria-label="View order <?php echo htmlspecialchars((string) ($order['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                        <?php foreach (afrisense_order_status_actions($orderStatus) as $actionConfig): ?>
                                            <?php echo afrisense_order_action_form((int) ($order['id'] ?? 0), 'update_order_status', 'order_status', $actionConfig); ?>
                                        <?php endforeach; ?>
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

            <footer class="af-menu-pagination">
                <p>Showing 1 to <?php echo htmlspecialchars((string) count($orders), ENT_QUOTES, 'UTF-8'); ?> of <?php echo htmlspecialchars((string) $totalOrders, ENT_QUOTES, 'UTF-8'); ?> orders</p>
                <nav aria-label="Orders pagination">
                    <a href="#" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                    <a class="active" href="#">1</a>
                    <a href="#">2</a>
                    <a href="#">3</a>
                    <span>...</span>
                    <a href="#" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                </nav>
            </footer>
        </section>

        <aside class="af-orders-side">
            <?php if ($selectedOrder !== null): ?>
                <section class="af-menu-panel af-selected-order-panel" id="order-details">
                    <h2>Order Details</h2>
                    <strong>ORD-<?php echo htmlspecialchars(str_pad((string) ($selectedOrder['id'] ?? 0), 5, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <dl>
                        <div><dt>Customer</dt><dd><?php echo htmlspecialchars((string) ($selectedOrder['fullname'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div><dt>Phone</dt><dd><?php echo htmlspecialchars((string) ($selectedOrder['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div><dt>Item</dt><dd><?php echo htmlspecialchars((string) ($selectedOrder['food_name'] ?? 'Food item'), ENT_QUOTES, 'UTF-8'); ?> x <?php echo htmlspecialchars((string) ($selectedOrder['quantity'] ?? 1), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div><dt>Amount</dt><dd>GHc <?php echo htmlspecialchars(number_format((float) ($selectedOrder['total_price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div><dt>Status</dt><dd><?php echo htmlspecialchars((string) ($selectedOrder['order_status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div><dt>Payment</dt><dd><?php echo htmlspecialchars((string) ($selectedOrder['payment_status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars((string) ($selectedOrder['payment_method'] ?? 'Cash'), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    </dl>
                    <p><b>Delivery:</b> <?php echo htmlspecialchars((string) ($selectedOrder['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if (trim((string) ($selectedOrder['special_instructions'] ?? '')) !== ''): ?>
                        <p><b>Note:</b> <?php echo htmlspecialchars((string) ($selectedOrder['special_instructions'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

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
