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

afrisense_require_admin();

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

$validStatuses = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Out for Delivery', 'Delivered', 'Cancelled'];
$statusFilter = (string) ($_GET['status'] ?? '');
$search = trim((string) ($_GET['search'] ?? ''));
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
                            <tr>
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
                                    <div class="af-row-actions">
                                        <button type="button" aria-label="View order <?php echo htmlspecialchars((string) ($order['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                        <button type="button" aria-label="More order actions"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>
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
