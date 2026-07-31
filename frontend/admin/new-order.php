<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'New Order | AfriSense';
$adminTitle = 'New Order';
$adminSearchPlaceholder = 'Search customers, foods, orders...';
$activeAdminPage = 'orders_all';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

require_once __DIR__ . '/../includes/public_settings.php';

$authUser = afrisense_require_admin();
$adminUserId = (int) ($authUser['id'] ?? 0);
$message = null;
$customers = [];
$foods = [];
$selectedCustomerId = (int) ($_POST['customer_id'] ?? 0);
$selectedFoodId = (int) ($_POST['food_id'] ?? 0);
$quantity = max(1, min(20, (int) ($_POST['quantity'] ?? 1)));
$deliveryAddress = trim((string) ($_POST['delivery_address'] ?? ''));
$specialInstructions = trim((string) ($_POST['special_instructions'] ?? ''));
$paymentMethod = trim((string) ($_POST['payment_method'] ?? 'Cash'));

try {
    $pdo = afrisense_pdo();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $customerStatement = $pdo->prepare('SELECT * FROM `customers` WHERE `id` = :id LIMIT 1');
        $customerStatement->execute(['id' => $selectedCustomerId]);
        $customer = $customerStatement->fetch(PDO::FETCH_ASSOC) ?: null;

        $foodStatement = $pdo->prepare('SELECT * FROM `foods` WHERE `id` = :id AND `availability` = "Available" LIMIT 1');
        $foodStatement->execute(['id' => $selectedFoodId]);
        $food = $foodStatement->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($customer === null || $food === null || $deliveryAddress === '' || !afrisense_public_payment_method_allowed($paymentMethod)) {
            $message = ['type' => 'error', 'text' => 'Choose a customer, food item, delivery address, and valid payment method.'];
        } else {
            $lineTotal = ((float) ($food['price'] ?? 0)) * $quantity;
            $lineTotal += afrisense_public_delivery_fee($lineTotal, $deliveryAddress);
            $paymentStatus = $paymentMethod === 'Cash' ? 'Pending' : 'Paid';
            $orderStatus = afrisense_public_paid_order_status($paymentMethod);

            $insert = $pdo->prepare(
                'INSERT INTO `orders`
                    (`customer_id`, `food_id`, `quantity`, `total_price`, `delivery_address`, `special_instructions`, `payment_method`, `payment_status`, `order_status`)
                 VALUES
                    (:customer_id, :food_id, :quantity, :total_price, :delivery_address, :special_instructions, :payment_method, :payment_status, :order_status)'
            );
            $insert->execute([
                'customer_id' => $selectedCustomerId,
                'food_id' => $selectedFoodId,
                'quantity' => $quantity,
                'total_price' => $lineTotal,
                'delivery_address' => $deliveryAddress,
                'special_instructions' => $specialInstructions,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'order_status' => $orderStatus,
            ]);

            $orderId = (int) $pdo->lastInsertId();
            afrisense_public_send_order_customer_email_for_order(
                $pdo,
                $orderId,
                'Order Created',
                'Your AfriSense order #' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) . ' has been created by AfriSense.'
            );

            $notificationUser = $pdo->prepare(
                'SELECT u.`id`
                 FROM `users` u
                 WHERE u.`email` = :email OR REPLACE(u.`phonenumber`, " ", "") = REPLACE(:phone, " ", "")
                 ORDER BY u.`id` ASC
                 LIMIT 1'
            );
            $notificationUser->execute([
                'email' => (string) ($customer['email'] ?? ''),
                'phone' => (string) ($customer['phone_number'] ?? ''),
            ]);
            $customerUserId = (int) ($notificationUser->fetchColumn() ?: 0);

            if ($customerUserId > 0) {
                $notify = $pdo->prepare(
                    'INSERT INTO `notifications`
                        (`user_id`, `title`, `message`, `notification_type`, `action_url`, `created_by`)
                     VALUES
                        (:user_id, :title, :message, :notification_type, :action_url, :created_by)'
                );
                $notify->execute([
                    'user_id' => $customerUserId,
                    'title' => 'Order Created',
                    'message' => 'AfriSense created order #' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) . ' for you.',
                    'notification_type' => 'Order',
                    'action_url' => '/Afrisense/frontend/customer/my-orders.php?view=' . $orderId . '#order-details',
                    'created_by' => $adminUserId > 0 ? $adminUserId : null,
                ]);
            }

            header('Location: orders.php?view=' . $orderId . '#order-row-' . $orderId);
            exit;
        }
    }

    $customers = $pdo->query('SELECT `id`, `fullname`, `email`, `phone_number`, `address` FROM `customers` ORDER BY `fullname` ASC, `id` ASC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
    $foods = $pdo->query('SELECT `id`, `food_name`, `price` FROM `foods` WHERE `availability` = "Available" ORDER BY `food_name` ASC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
    $loadError = '';
} catch (Throwable $exception) {
    $customers = [];
    $foods = [];
    $loadError = 'New order form could not be loaded. Check that MySQL is running.';
}

ob_start();
?>
<section class="af-admin-menu-page af-orders-page">
    <header class="af-admin-page-heading">
        <div>
            <h1>New Order</h1>
            <p>Dashboard / Orders / New Order</p>
        </div>
        <a class="af-add-menu-btn" href="orders.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Orders</a>
    </header>

    <?php if ($loadError !== ''): ?><div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($message !== null): ?><div class="af-admin-alert <?php echo htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($message['text'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <section class="af-menu-table-card af-user-create-card">
        <div class="af-table-toolbar">
            <div>
                <h2>Create Customer Order</h2>
                <p>Select an existing customer and available menu item.</p>
            </div>
        </div>
        <form class="af-food-management-form af-user-create-form" action="new-order.php" method="post">
            <label>
                <span>Customer</span>
                <select name="customer_id" required>
                    <option value="">Select customer</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo (int) $customer['id']; ?>" <?php echo $selectedCustomerId === (int) $customer['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $customer['fullname'] . ' - ' . (string) $customer['email'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Food Item</span>
                <select name="food_id" required>
                    <option value="">Select food</option>
                    <?php foreach ($foods as $food): ?>
                        <option value="<?php echo (int) $food['id']; ?>" <?php echo $selectedFoodId === (int) $food['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $food['food_name'] . ' - ' . afrisense_public_money((float) $food['price']), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Quantity</span>
                <input type="number" name="quantity" min="1" max="20" value="<?php echo htmlspecialchars((string) $quantity, ENT_QUOTES, 'UTF-8'); ?>" required>
            </label>
            <label>
                <span>Payment Method</span>
                <select name="payment_method" required>
                    <?php foreach (afrisense_public_payment_methods() as $method): ?>
                        <option value="<?php echo htmlspecialchars($method, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $paymentMethod === $method ? 'selected' : ''; ?>><?php echo htmlspecialchars(afrisense_public_payment_method_label($method), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Delivery Address</span>
                <input type="text" name="delivery_address" value="<?php echo htmlspecialchars($deliveryAddress, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Customer delivery address" required>
            </label>
            <label>
                <span>Instructions</span>
                <input type="text" name="special_instructions" value="<?php echo htmlspecialchars($specialInstructions, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional kitchen or delivery note">
            </label>
            <button type="submit"><i class="bi bi-plus-lg" aria-hidden="true"></i> Create Order</button>
        </form>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
