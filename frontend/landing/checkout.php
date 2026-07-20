<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Checkout | AfriSense';
$activePage = 'menu';
$publicHeaderMode = 'shop';
$extraStyles = [$frontendBase . '/assets/css/order-payment.css'];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

\AfriSense\Backend\Helpers\Session::start();

if (!function_exists('afrisense_checkout_image')) {
    function afrisense_checkout_image(string $frontendBase, ?string $image): string
    {
        $filename = basename(trim((string) $image));

        if ($filename !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
            return $frontendBase . '/assets/images/foods/' . $filename;
        }

        return $frontendBase . '/assets/images/foods/jollof-rice.png';
    }
}

if (!function_exists('afrisense_checkout_cart')) {
    function afrisense_checkout_cart(): array
    {
        $cart = $_SESSION['afrisense_guest_cart'] ?? [];

        return is_array($cart) ? $cart : [];
    }
}

if (!function_exists('afrisense_checkout_customer_id')) {
    function afrisense_checkout_customer_id(PDO $pdo, array $details): int
    {
        $statement = $pdo->prepare(
            'SELECT `id`
             FROM `customers`
             WHERE `email` = :email OR `phone_number` = :phone
             ORDER BY `id` ASC
             LIMIT 1'
        );
        $statement->execute([
            'email' => $details['email'],
            'phone' => $details['phone'],
        ]);
        $customerId = $statement->fetchColumn();

        if ($customerId !== false) {
            $update = $pdo->prepare(
                'UPDATE `customers`
                 SET `fullname` = :fullname,
                     `email` = :email,
                     `phone_number` = :phone,
                     `address` = :address,
                     `updated_at` = NOW()
                 WHERE `id` = :id'
            );
            $update->execute([
                'fullname' => $details['fullname'],
                'email' => $details['email'],
                'phone' => $details['phone'],
                'address' => $details['delivery_address'],
                'id' => (int) $customerId,
            ]);

            return (int) $customerId;
        }

        $insert = $pdo->prepare(
            'INSERT INTO `customers` (`fullname`, `email`, `phone_number`, `address`)
             VALUES (:fullname, :email, :phone, :address)'
        );
        $insert->execute([
            'fullname' => $details['fullname'],
            'email' => $details['email'],
            'phone' => $details['phone'],
            'address' => $details['delivery_address'],
        ]);

        return (int) $pdo->lastInsertId();
    }
}

if (!function_exists('afrisense_checkout_notify_admins')) {
    function afrisense_checkout_notify_admins(PDO $pdo, int $count, string $customerName): void
    {
        if ($count <= 0) {
            return;
        }

        $admins = $pdo->prepare(
            "SELECT u.`id`
             FROM `users` u
             INNER JOIN `roles` r ON r.`id` = u.`role_id`
             WHERE LOWER(COALESCE(r.`rolename`, '')) IN ('administrator', 'admin', 'super admin')"
        );
        $admins->execute();
        $adminIds = $admins->fetchAll(PDO::FETCH_COLUMN);

        $notification = $pdo->prepare(
            'INSERT INTO `notifications`
                (`user_id`, `title`, `message`, `notification_type`, `action_url`, `created_by`)
             VALUES
                (:user_id, :title, :message, :notification_type, :action_url, :created_by)'
        );

        foreach ($adminIds as $adminId) {
            $notification->execute([
                'user_id' => (int) $adminId,
                'title' => 'New Order Received',
                'message' => $count . ' order item(s) have been placed by ' . $customerName . '.',
                'notification_type' => 'Order',
                'action_url' => '/Afrisense/frontend/admin/orders.php',
                'created_by' => null,
            ]);
        }
    }
}

if (!function_exists('afrisense_checkout_details_complete')) {
    function afrisense_checkout_details_complete(array $details): bool
    {
        return trim((string) ($details['fullname'] ?? '')) !== ''
            && filter_var((string) ($details['email'] ?? ''), FILTER_VALIDATE_EMAIL) !== false
            && trim((string) ($details['phone'] ?? '')) !== ''
            && trim((string) ($details['delivery_address'] ?? '')) !== ''
            && in_array((string) ($details['payment_method'] ?? ''), ['Cash', 'Mobile Money', 'Card'], true);
    }
}

$message = null;
$cart = afrisense_checkout_cart();
$details = $_SESSION['afrisense_guest_checkout'] ?? [
    'fullname' => '',
    'email' => '',
    'phone' => '',
    'delivery_address' => '',
    'payment_method' => 'Cash',
    'cart_note' => (string) ($_SESSION['afrisense_guest_cart_note'] ?? ''),
];
$showReview = afrisense_checkout_details_complete($details);

try {
    $pdo = afrisense_pdo();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = trim((string) ($_POST['action'] ?? ''));

        if ($action === 'review_checkout') {
            $details = [
                'fullname' => trim((string) ($_POST['fullname'] ?? '')),
                'email' => trim((string) ($_POST['email'] ?? '')),
                'phone' => preg_replace('/\s+/', '', trim((string) ($_POST['phone'] ?? ''))),
                'delivery_address' => trim((string) ($_POST['delivery_address'] ?? '')),
                'payment_method' => trim((string) ($_POST['payment_method'] ?? 'Cash')),
                'cart_note' => trim((string) ($_POST['cart_note'] ?? ($_SESSION['afrisense_guest_cart_note'] ?? ''))),
            ];
            $_SESSION['afrisense_guest_checkout'] = $details;
            $_SESSION['afrisense_guest_cart_note'] = $details['cart_note'];

            if (!afrisense_checkout_details_complete($details)) {
                $message = ['type' => 'error', 'text' => 'Enter your name, valid email, phone, delivery address and payment method.'];
                $showReview = false;
            } else {
                $showReview = true;
            }
        }

        if ($action === 'place_order') {
            $details = $_SESSION['afrisense_guest_checkout'] ?? $details;

            if ($cart === [] || $details['fullname'] === '' || !filter_var($details['email'], FILTER_VALIDATE_EMAIL) || $details['phone'] === '' || $details['delivery_address'] === '' || !in_array($details['payment_method'], ['Cash', 'Mobile Money', 'Card'], true)) {
                $message = ['type' => 'error', 'text' => 'Complete your checkout details before placing the order.'];
                $showReview = false;
            } else {
                $foodIds = array_keys($cart);
                $placeholders = implode(',', array_fill(0, count($foodIds), '?'));
                $foodLookup = $pdo->prepare(
                    'SELECT `id`, `price`
                     FROM `foods`
                     WHERE `availability` = \'Available\' AND `id` IN (' . $placeholders . ')'
                );
                $foodLookup->execute(array_map('intval', $foodIds));
                $prices = [];

                foreach ($foodLookup->fetchAll(PDO::FETCH_ASSOC) as $food) {
                    $prices[(int) $food['id']] = (float) $food['price'];
                }

                $pdo->beginTransaction();
                $customerId = afrisense_checkout_customer_id($pdo, $details);
                $insert = $pdo->prepare(
                    'INSERT INTO `orders`
                        (`customer_id`, `food_id`, `quantity`, `total_price`, `delivery_address`, `special_instructions`, `payment_method`, `payment_status`, `order_status`)
                     VALUES
                        (:customer_id, :food_id, :quantity, :total_price, :delivery_address, :special_instructions, :payment_method, :payment_status, :order_status)'
                );
                $createdOrders = 0;
                $deliveryFeeApplied = false;

                foreach ($cart as $foodId => $quantity) {
                    if (!isset($prices[(int) $foodId])) {
                        continue;
                    }

                    $quantity = max(1, min(20, (int) $quantity));
                    $lineTotal = $prices[(int) $foodId] * $quantity;

                    if (!$deliveryFeeApplied) {
                        $lineTotal += 10.00;
                        $deliveryFeeApplied = true;
                    }

                    $insert->execute([
                        'customer_id' => $customerId,
                        'food_id' => (int) $foodId,
                        'quantity' => $quantity,
                        'total_price' => $lineTotal,
                        'delivery_address' => $details['delivery_address'],
                        'special_instructions' => $details['cart_note'],
                        'payment_method' => $details['payment_method'],
                        'payment_status' => 'Pending',
                        'order_status' => 'Pending',
                    ]);
                    $createdOrders++;
                }

                afrisense_checkout_notify_admins($pdo, $createdOrders, $details['fullname']);
                $pdo->commit();
                $_SESSION['afrisense_guest_cart'] = [];
                $_SESSION['afrisense_guest_cart_note'] = '';
                $_SESSION['afrisense_guest_checkout'] = [];
                $cart = [];
                $message = ['type' => 'success', 'text' => 'Your order has been placed successfully.'];
                $showReview = false;
            }
        }
    }

    $cartFoods = [];
    if ($cart !== []) {
        $cartIds = array_keys($cart);
        $cartPlaceholders = implode(',', array_fill(0, count($cartIds), '?'));
        $cartStatement = $pdo->prepare(
            'SELECT `id`, `food_name`, `price`, `image`
             FROM `foods`
             WHERE `id` IN (' . $cartPlaceholders . ')'
        );
        $cartStatement->execute(array_map('intval', $cartIds));

        foreach ($cartStatement->fetchAll(PDO::FETCH_ASSOC) as $food) {
            $food['quantity'] = max(1, (int) ($cart[(int) $food['id']] ?? 1));
            $cartFoods[] = $food;
        }
    }
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $cartFoods = [];
    $message = ['type' => 'error', 'text' => 'Checkout could not load. Check that MySQL is running.'];
}

$subtotal = array_reduce(
    $cartFoods,
    static fn (float $total, array $food): float => $total + ((float) $food['price'] * (int) $food['quantity']),
    0.00
);
$deliveryFee = $cartFoods === [] ? 0.00 : 10.00;
$total = $subtotal + $deliveryFee;

ob_start();
?>
<section class="af-payment-page af-checkout-review-page">
    <ol class="af-checkout-steps">
        <li class="done"><span><i class="bi bi-cart3"></i></span>1. Cart <i class="bi bi-check-circle-fill"></i></li>
        <li class="active"><span><i class="bi bi-truck"></i></span>2. Checkout</li>
        <li><span><i class="bi bi-check-lg"></i></span>3. Confirmation</li>
    </ol>

    <?php if ($message !== null): ?>
        <p class="af-payment-alert <?php echo htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi <?php echo $message['type'] === 'success' ? 'bi-check-circle' : 'bi-info-circle'; ?>"></i>
            <?php echo htmlspecialchars($message['text'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <div class="af-payment-grid">
        <main class="af-payment-card">
            <header class="af-payment-heading">
                <i class="bi bi-bag-check"></i>
                <div><h1>Review Your Order</h1><p>Confirm your details before we send the order to the kitchen.</p></div>
            </header>

            <?php if ($cartFoods === []): ?>
                <section class="af-payment-section">
                    <h2>Your cart is empty</h2>
                    <p><a class="af-back-delivery" href="order.php"><i class="bi bi-arrow-left"></i> Back to Order Page</a></p>
                </section>
            <?php elseif (!$showReview): ?>
                <form class="af-payment-section af-checkout-details-form" action="checkout.php" method="post">
                    <input type="hidden" name="action" value="review_checkout">
                    <h2>Contact &amp; Delivery Details</h2>
                    <div class="af-billing-grid">
                        <label>Full Name<input type="text" name="fullname" value="<?php echo htmlspecialchars((string) $details['fullname'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your full name" required></label>
                        <label>Email Address<input type="email" name="email" value="<?php echo htmlspecialchars((string) $details['email'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="you@example.com" required></label>
                        <label>Phone Number<input type="tel" name="phone" value="<?php echo htmlspecialchars((string) $details['phone'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="+233 24 123 4567" required></label>
                    </div>
                    <label class="af-payment-input">Delivery Address<span><i class="bi bi-geo-alt"></i><input type="text" name="delivery_address" value="<?php echo htmlspecialchars((string) $details['delivery_address'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="House number, street, area" required></span></label>
                    <div class="af-billing-grid single">
                        <label>Payment Method<select name="payment_method" required>
                            <?php foreach (['Cash', 'Mobile Money', 'Card'] as $paymentMethod): ?>
                                <option value="<?php echo htmlspecialchars($paymentMethod, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) $details['payment_method'] === $paymentMethod ? 'selected' : ''; ?>><?php echo htmlspecialchars($paymentMethod, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select></label>
                    </div>
                    <label class="af-payment-input">Order Note<span class="af-payment-textarea"><i class="bi bi-journal-text"></i><textarea name="cart_note" rows="4" placeholder="Kitchen or delivery note, optional"><?php echo htmlspecialchars((string) $details['cart_note'], ENT_QUOTES, 'UTF-8'); ?></textarea></span></label>
                    <p class="af-payment-warning"><i class="bi bi-shield-lock"></i> Your details are only used to process this AfriSense order.</p>
                    <button class="af-pay-now" type="submit"><i class="bi bi-arrow-right"></i> Review Order</button>
                </form>
            <?php else: ?>
                <section class="af-payment-section">
                    <h2>Contact &amp; Delivery Details</h2>
                    <div class="af-billing-grid">
                        <label>Full Name<input type="text" value="<?php echo htmlspecialchars((string) $details['fullname'], ENT_QUOTES, 'UTF-8'); ?>" readonly></label>
                        <label>Email Address<input type="email" value="<?php echo htmlspecialchars((string) $details['email'], ENT_QUOTES, 'UTF-8'); ?>" readonly></label>
                        <label>Phone Number<input type="tel" value="<?php echo htmlspecialchars((string) $details['phone'], ENT_QUOTES, 'UTF-8'); ?>" readonly></label>
                    </div>
                    <label class="af-payment-input">Delivery Address<span><i class="bi bi-geo-alt"></i><input type="text" value="<?php echo htmlspecialchars((string) $details['delivery_address'], ENT_QUOTES, 'UTF-8'); ?>" readonly></span></label>
                    <p class="af-payment-warning"><i class="bi bi-wallet2"></i> Payment method: <?php echo htmlspecialchars((string) $details['payment_method'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <a class="af-back-delivery" href="order.php"><i class="bi bi-pencil"></i> Edit Order Details</a>
                </section>

                <form class="af-payment-section" action="checkout.php" method="post">
                    <input type="hidden" name="action" value="place_order">
                    <button class="af-pay-now" type="submit"><i class="bi bi-lock"></i> Place Order</button>
                </form>
            <?php endif; ?>
        </main>

        <aside class="af-payment-side">
            <section class="af-summary-card">
                <h2>Order Summary</h2>
                <?php foreach ($cartFoods as $item): ?>
                    <article>
                        <img src="<?php echo htmlspecialchars(afrisense_checkout_image($frontendBase, (string) ($item['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $item['food_name'], ENT_QUOTES, 'UTF-8'); ?>">
                        <span><strong><?php echo htmlspecialchars((string) $item['food_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small>Qty: <?php echo htmlspecialchars((string) $item['quantity'], ENT_QUOTES, 'UTF-8'); ?></small></span>
                        <b>GH₵ <?php echo htmlspecialchars(number_format((float) $item['price'] * (int) $item['quantity'], 2), ENT_QUOTES, 'UTF-8'); ?></b>
                    </article>
                <?php endforeach; ?>
                <dl><div><dt>Subtotal</dt><dd>GH₵ <?php echo htmlspecialchars(number_format($subtotal, 2), ENT_QUOTES, 'UTF-8'); ?></dd></div><div><dt>Delivery Fee</dt><dd>GH₵ <?php echo htmlspecialchars(number_format($deliveryFee, 2), ENT_QUOTES, 'UTF-8'); ?></dd></div><div class="total"><dt>Total Amount</dt><dd>GH₵ <?php echo htmlspecialchars(number_format($total, 2), ENT_QUOTES, 'UTF-8'); ?></dd></div></dl>
            </section>

            <section class="af-summary-card af-payment-help">
                <i class="bi bi-headset"></i><div><h2>Need Help?</h2><p>Our support team is here to assist you.</p><strong>Call / WhatsApp: +233 24 123 4567</strong></div>
            </section>
        </aside>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
