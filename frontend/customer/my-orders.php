<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'My Orders | AfriSense';
$customerTitle = 'My Orders';
$activeCustomerPage = 'orders';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/customer-my-orders.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/public_settings.php';

\AfriSense\Backend\Helpers\Session::start();
$authUser = afrisense_require_customer();

// Defines the afrisense_my_orders_customer helper used by this module.
function afrisense_my_orders_customer(PDO $pdo, array $user): ?array
{
    $email = trim((string) ($user['email'] ?? ''));
    $phone = preg_replace('/\s+/', '', trim((string) ($user['phonenumber'] ?? $user['phone'] ?? '')));

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

// Defines the afrisense_my_orders_status_class helper used by this module.
function afrisense_my_orders_status_class(string $status): string
{
    return match (strtolower($status)) {
        'confirmed' => 'confirmed',
        'preparing' => 'preparing',
        'ready', 'out for delivery' => 'way',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
        default => 'pending',
    };
}

// Defines the afrisense_my_orders_payment_class helper used by this module.
function afrisense_my_orders_payment_class(string $status): string
{
    return strtolower($status) === 'paid' ? 'paid' : 'pending';
}

// Defines the afrisense_my_orders_image helper used by this module.
function afrisense_my_orders_image(string $frontendBase, ?string $image): string
{
    $filename = basename(trim((string) $image));

    // Guard this block so it only runs when the required condition is met.
    if ($filename !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
        return $frontendBase . '/assets/images/foods/' . $filename;
    }

    return $frontendBase . '/assets/images/foods/jollof-rice.png';
}

// Defines the afrisense_my_orders_fetch_items helper used by this module.
function afrisense_my_orders_fetch_items(PDO $pdo, int $customerId, int $groupId): array
{
    // Guard this block so it only runs when the required condition is met.
    if ($customerId <= 0 || $groupId <= 0) {
        return [];
    }

    $base = $pdo->prepare(
        'SELECT `ordered_at`, `delivery_address`, `payment_method`
         FROM `orders`
         WHERE `id` = :id AND `customer_id` = :customer_id
         LIMIT 1'
    );
    $base->execute([
        'id' => $groupId,
        'customer_id' => $customerId,
    ]);
    $baseOrder = $base->fetch(PDO::FETCH_ASSOC);

    // Guard this block so it only runs when the required condition is met.
    if ($baseOrder === false) {
        return [];
    }

    $items = $pdo->prepare(
        'SELECT
            o.`id`,
            o.`quantity`,
            o.`total_price`,
            o.`delivery_address`,
            o.`payment_method`,
            o.`payment_status`,
            o.`order_status`,
            o.`ordered_at`,
            f.`food_name`,
            f.`image`
         FROM `orders` o
         INNER JOIN `foods` f ON f.`id` = o.`food_id`
         WHERE o.`customer_id` = :customer_id
           AND o.`ordered_at` = :ordered_at
           AND o.`delivery_address` = :delivery_address
           AND o.`payment_method` = :payment_method
         ORDER BY o.`id` ASC'
    );
    $items->execute([
        'customer_id' => $customerId,
        'ordered_at' => (string) $baseOrder['ordered_at'],
        'delivery_address' => (string) $baseOrder['delivery_address'],
        'payment_method' => (string) $baseOrder['payment_method'],
    ]);

    return $items->fetchAll(PDO::FETCH_ASSOC);
}

$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$paymentFilter = trim((string) ($_GET['payment'] ?? ''));
$viewGroupId = (int) ($_GET['view'] ?? 0);
$validStatuses = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Out for Delivery', 'Delivered', 'Cancelled'];
$validPaymentStatuses = ['Pending', 'Paid', 'Failed', 'Refunded'];
$flashMessage = '';
$flashType = 'success';

// Run database/action work inside a guarded block so the page can fail gracefully.
try {
    $pdo = afrisense_pdo();
    $customer = afrisense_my_orders_customer($pdo, $authUser);
    $customerId = (int) ($customer['id'] ?? 0);

    // Handle submitted form actions before rendering the page.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'reorder') {
        $reorderGroupId = (int) ($_POST['group_id'] ?? 0);
        $items = afrisense_my_orders_fetch_items($pdo, $customerId, $reorderGroupId);

        // Guard this block so it only runs when the required condition is met.
        if ($items === []) {
            $flashType = 'error';
            $flashMessage = 'That order could not be added to your cart.';
        } else {
            $cart = $_SESSION['afrisense_customer_cart'] ?? [];
            $cart = is_array($cart) ? $cart : [];

            // Iterate through the data needed for this block.
            foreach ($items as $item) {
                $foodIdStatement = $pdo->prepare(
                    'SELECT `id`
                     FROM `foods`
                     WHERE `food_name` = :food_name
                     LIMIT 1'
                );
                $foodIdStatement->execute(['food_name' => (string) $item['food_name']]);
                $foodId = (int) $foodIdStatement->fetchColumn();

                // Guard this block so it only runs when the required condition is met.
                if ($foodId > 0) {
                    $cart[$foodId] = min(20, (int) ($cart[$foodId] ?? 0) + (int) ($item['quantity'] ?? 1));
                }
            }

            $_SESSION['afrisense_customer_cart'] = $cart;
            header('Location: orders.php');
            exit;
        }
    }

    $where = ['o.`customer_id` = :customer_id'];
    $params = ['customer_id' => $customerId];

    // Guard this block so it only runs when the required condition is met.
    if (in_array($statusFilter, $validStatuses, true)) {
        $where[] = 'o.`order_status` = :status';
        $params['status'] = $statusFilter;
    }

    // Guard this block so it only runs when the required condition is met.
    if (in_array($paymentFilter, $validPaymentStatuses, true)) {
        $where[] = 'o.`payment_status` = :payment_status';
        $params['payment_status'] = $paymentFilter;
    }

    // Guard this block so it only runs when the required condition is met.
    if ($search !== '') {
        $where[] = '(CAST(o.`id` AS CHAR) LIKE :search OR f.`food_name` LIKE :search OR o.`delivery_address` LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $orders = [];
    $selectedItems = [];

    // Guard this block so it only runs when the required condition is met.
    if ($customerId > 0) {
        $sql = 'SELECT
                    MIN(o.`id`) AS group_id,
                    o.`ordered_at`,
                    o.`delivery_address`,
                    o.`payment_method`,
                    o.`payment_status`,
                    o.`order_status`,
                    SUM(o.`total_price`) AS total_amount,
                    COUNT(o.`id`) AS item_count,
                    GROUP_CONCAT(f.`food_name` ORDER BY o.`id` SEPARATOR ", ") AS food_names,
                    MIN(f.`image`) AS image
                FROM `orders` o
                INNER JOIN `foods` f ON f.`id` = o.`food_id`
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY o.`ordered_at`, o.`delivery_address`, o.`payment_method`, o.`payment_status`, o.`order_status`
                ORDER BY o.`ordered_at` DESC, group_id DESC
                LIMIT 25';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        $orders = $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Guard this block so it only runs when the required condition is met.
    if ($viewGroupId <= 0 && $orders !== []) {
        $viewGroupId = (int) $orders[0]['group_id'];
    }

    // Guard this block so it only runs when the required condition is met.
    if ($viewGroupId > 0 && $customerId > 0) {
        $selectedItems = afrisense_my_orders_fetch_items($pdo, $customerId, $viewGroupId);
    }

    $counts = [
        'all' => 0,
        'Pending' => 0,
        'Preparing' => 0,
        'Out for Delivery' => 0,
        'Delivered' => 0,
        'Cancelled' => 0,
    ];

    // Guard this block so it only runs when the required condition is met.
    if ($customerId > 0) {
        $countStatement = $pdo->prepare(
            'SELECT `order_status`, COUNT(*) AS count_value
             FROM (
                SELECT MIN(`id`) AS group_id, `order_status`
                FROM `orders`
                WHERE `customer_id` = :customer_id
                GROUP BY `ordered_at`, `delivery_address`, `payment_method`, `payment_status`, `order_status`
             ) grouped_orders
             GROUP BY `order_status`'
        );
        $countStatement->execute(['customer_id' => $customerId]);

        // Iterate through the data needed for this block.
        foreach ($countStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[(string) $row['order_status']] = (int) $row['count_value'];
            $counts['all'] += (int) $row['count_value'];
        }
    }

    $loadError = '';
} catch (Throwable $exception) {
    $customer = null;
    $orders = [];
    $selectedItems = [];
    $counts = ['all' => 0, 'Pending' => 0, 'Preparing' => 0, 'Out for Delivery' => 0, 'Delivered' => 0, 'Cancelled' => 0];
    $loadError = 'Orders could not be loaded. Check that MySQL is running.';
}

$selectedFirst = $selectedItems[0] ?? null;
$selectedSubtotal = array_reduce(
    $selectedItems,
    static fn (float $total, array $item): float => $total + (float) ($item['total_price'] ?? 0),
    0.00
);
$selectedDeliveryFee = $selectedItems !== [] ? min($selectedSubtotal, afrisense_public_delivery_fee($selectedSubtotal, (string) ($selectedFirst['delivery_address'] ?? ''))) : 0.00;
$selectedDisplaySubtotal = max(0.00, $selectedSubtotal - $selectedDeliveryFee);
$selectedGroupId = (int) ($selectedFirst['id'] ?? $viewGroupId);

ob_start();
?>
<!-- Page section for this part of the AfriSense interface. -->
<section class="af-my-orders-page">
    <!-- Header block for this interface section. -->
    <header class="af-my-orders-heading">
        <div>
            <h1>My Orders</h1>
            <p>View and track all your food orders in one place.</p>
        </div>
        <a href="orders.php"><i class="bi bi-bag-plus" aria-hidden="true"></i> Place Order</a>
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
    <section class="af-my-order-metrics">
        <article><span><i class="bi bi-receipt" aria-hidden="true"></i></span><div><small>All Orders</small><strong><?php echo htmlspecialchars((string) $counts['all'], ENT_QUOTES, 'UTF-8'); ?></strong><p>View all orders</p></div></article>
        <article><span><i class="bi bi-clock" aria-hidden="true"></i></span><div><small>Pending</small><strong><?php echo htmlspecialchars((string) $counts['Pending'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Awaiting confirmation</p></div></article>
        <article><span><i class="bi bi-egg-fried" aria-hidden="true"></i></span><div><small>Preparing</small><strong><?php echo htmlspecialchars((string) $counts['Preparing'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Being prepared</p></div></article>
        <article><span><i class="bi bi-truck" aria-hidden="true"></i></span><div><small>On the Way</small><strong><?php echo htmlspecialchars((string) $counts['Out for Delivery'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Out for delivery</p></div></article>
        <article><span><i class="bi bi-check-circle" aria-hidden="true"></i></span><div><small>Delivered</small><strong><?php echo htmlspecialchars((string) $counts['Delivered'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Successfully delivered</p></div></article>
        <article><span><i class="bi bi-x-circle" aria-hidden="true"></i></span><div><small>Cancelled</small><strong><?php echo htmlspecialchars((string) $counts['Cancelled'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Cancelled orders</p></div></article>
    </section>

    <!-- Page section for this part of the AfriSense interface. -->
    <section class="af-my-orders-layout">
        <!-- Main content area for this page. -->
        <main class="af-my-orders-main">
            <!-- Form block that submits this page workflow. -->
            <form class="af-my-orders-filters" action="my-orders.php" method="get">
                <label><i class="bi bi-search" aria-hidden="true"></i><input type="search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search orders by ID, items, or address..."></label>
                <label><select name="status"><option value="">All Status</option><?php foreach ($validStatuses as $status): ?><option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select><i class="bi bi-chevron-down" aria-hidden="true"></i></label>
                <label><select name="payment"><option value="">All Payment Status</option><?php foreach ($validPaymentStatuses as $status): ?><option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $paymentFilter === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select><i class="bi bi-chevron-down" aria-hidden="true"></i></label>
                <button type="submit"><i class="bi bi-filter" aria-hidden="true"></i> Filter</button>
            </form>

            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-my-orders-list">
                <?php // Render this conditional/dynamic template block. ?>
                <?php if ($orders === []): ?>
                    <article class="af-my-order-empty"><i class="bi bi-bag" aria-hidden="true"></i><h2>No orders yet</h2><p>Place your first order and it will appear here.</p><a href="orders.php">Place Order</a></article>
                <?php endif; ?>
                <?php // Render this conditional/dynamic template block. ?>
                <?php foreach ($orders as $order): ?>
                    <?php
                    $orderedAt = strtotime((string) ($order['ordered_at'] ?? '')) ?: time();
                    $status = (string) ($order['order_status'] ?? 'Pending');
                    $paymentStatus = (string) ($order['payment_status'] ?? 'Pending');
                    ?>
                    <article id="order-row-<?php echo htmlspecialchars((string) ($order['group_id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="af-my-order-row <?php echo (int) $order['group_id'] === $selectedGroupId ? 'is-active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars(afrisense_my_orders_image($frontendBase, (string) ($order['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        <div class="af-my-order-info">
                            <h2>ORD-<?php echo htmlspecialchars(str_pad((string) ($order['group_id'] ?? 0), 6, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?> <span class="<?php echo htmlspecialchars(afrisense_my_orders_status_class($status), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span></h2>
                            <p><?php echo htmlspecialchars(date('j M Y', $orderedAt), ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars(date('h:i A', $orderedAt), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><?php echo htmlspecialchars((string) ($order['food_names'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <small><i class="bi bi-geo-alt" aria-hidden="true"></i> <?php echo htmlspecialchars((string) ($order['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                        </div>
                        <div class="af-my-order-price">
                            <strong><?php echo htmlspecialchars(afrisense_public_money((float) ($order['total_amount'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span class="<?php echo htmlspecialchars(afrisense_my_orders_payment_class($paymentStatus), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($paymentStatus, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="af-my-order-actions">
                            <a href="my-orders.php?view=<?php echo htmlspecialchars((string) ($order['group_id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>#order-details">View Details</a>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        </main>

        <!-- Side panel with supporting information and actions. -->
        <aside class="af-my-order-details" id="order-details">
            <!-- Header block for this interface section. -->
            <header>
                <h2>Order Details</h2>
                <?php // Render this conditional/dynamic template block. ?>
                <?php if ($selectedFirst !== null): ?><span class="<?php echo htmlspecialchars(afrisense_my_orders_status_class((string) ($selectedFirst['order_status'] ?? 'Pending')), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($selectedFirst['order_status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
            </header>
            <?php // Render this conditional/dynamic template block. ?>
            <?php if ($selectedFirst === null): ?>
                <p class="af-my-order-empty-side">Select an order to view details.</p>
            <?php else: ?>
                <?php $selectedDate = strtotime((string) ($selectedFirst['ordered_at'] ?? '')) ?: time(); ?>
                <!-- Page section for this part of the AfriSense interface. -->
                <section class="af-detail-highlight">
                    <i class="bi bi-receipt" aria-hidden="true"></i>
                    <div><strong>ORD-<?php echo htmlspecialchars(str_pad((string) ($selectedFirst['id'] ?? 0), 6, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?></strong><p>Placed on <?php echo htmlspecialchars(date('j M Y', $selectedDate), ENT_QUOTES, 'UTF-8'); ?> at <?php echo htmlspecialchars(date('h:i A', $selectedDate), ENT_QUOTES, 'UTF-8'); ?></p></div>
                </section>
                <!-- Page section for this part of the AfriSense interface. -->
                <section class="af-detail-section">
                    <h3>Delivery Information</h3>
                    <p><i class="bi bi-geo-alt" aria-hidden="true"></i> <?php echo htmlspecialchars((string) ($selectedFirst['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><i class="bi bi-telephone" aria-hidden="true"></i> <?php echo htmlspecialchars((string) ($customer['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                </section>
                <!-- Page section for this part of the AfriSense interface. -->
                <section class="af-detail-items">
                    <h3>Order Items</h3>
                    <?php // Render this conditional/dynamic template block. ?>
                    <?php foreach ($selectedItems as $item): ?>
                        <article>
                            <img src="<?php echo htmlspecialchars(afrisense_my_orders_image($frontendBase, (string) ($item['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                            <strong><?php echo htmlspecialchars((string) ($item['food_name'] ?? 'Food'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?php echo htmlspecialchars(afrisense_public_money((float) ($item['total_price'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?><small>x<?php echo htmlspecialchars((string) ($item['quantity'] ?? 1), ENT_QUOTES, 'UTF-8'); ?></small></span>
                        </article>
                    <?php endforeach; ?>
                </section>
                <dl class="af-detail-total">
                    <div><dt>Subtotal</dt><dd><?php echo htmlspecialchars(afrisense_public_money($selectedDisplaySubtotal), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>Delivery Fee</dt><dd><?php echo htmlspecialchars(afrisense_public_money($selectedDeliveryFee), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>Total</dt><dd><?php echo htmlspecialchars(afrisense_public_money($selectedSubtotal), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                </dl>
                <!-- Page section for this part of the AfriSense interface. -->
                <section class="af-detail-payment">
                    <h3>Payment Method</h3>
                    <p><i class="bi bi-credit-card" aria-hidden="true"></i> <?php echo htmlspecialchars((string) ($selectedFirst['payment_method'] ?? 'Cash'), ENT_QUOTES, 'UTF-8'); ?> <span class="<?php echo htmlspecialchars(afrisense_my_orders_payment_class((string) ($selectedFirst['payment_status'] ?? 'Pending')), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($selectedFirst['payment_status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8'); ?></span></p>
                </section>
                <!-- Form block that submits this page workflow. -->
                <form action="my-orders.php" method="post">
                    <input type="hidden" name="action" value="reorder">
                    <input type="hidden" name="group_id" value="<?php echo htmlspecialchars((string) $viewGroupId, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Reorder</button>
                </form>
            <?php endif; ?>
        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/customer_layout.php';
?>
