<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Payment | AfriSense';
$customerTitle = 'Payment';
$activeCustomerPage = 'cart';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/order-payment.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/public_settings.php';

\AfriSense\Backend\Helpers\Session::start();
$authUser = afrisense_require_customer();
afrisense_enforce_public_delivery_available();

function afrisense_customer_payment_image(string $frontendBase, ?string $image): string
{
    $filename = basename(trim((string) $image));

    if ($filename !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
        return $frontendBase . '/assets/images/foods/' . $filename;
    }

    return $frontendBase . '/assets/images/foods/jollof-rice.png';
}

function afrisense_customer_payment_customer_id(PDO $pdo, array $user, string $address): int
{
    $email = trim((string) ($user['email'] ?? ''));
    $phone = preg_replace('/\s+/', '', trim((string) ($user['phonenumber'] ?? $user['phone'] ?? '')));
    $fullname = trim((string) ($user['fullname'] ?? 'Customer'));
    $statement = $pdo->prepare('SELECT `id` FROM `customers` WHERE `email` = :email OR REPLACE(`phone_number`, " ", "") = :phone ORDER BY `id` ASC LIMIT 1');
    $statement->execute(['email' => $email, 'phone' => $phone]);
    $customerId = $statement->fetchColumn();

    if ($customerId !== false) {
        $update = $pdo->prepare('UPDATE `customers` SET `fullname` = :fullname, `email` = :email, `phone_number` = :phone, `address` = :address, `updated_at` = NOW() WHERE `id` = :id');
        $update->execute(['fullname' => $fullname, 'email' => $email, 'phone' => $phone, 'address' => $address, 'id' => (int) $customerId]);
        return (int) $customerId;
    }

    $insert = $pdo->prepare('INSERT INTO `customers` (`fullname`, `email`, `phone_number`, `address`) VALUES (:fullname, :email, :phone, :address)');
    $insert->execute(['fullname' => $fullname, 'email' => $email, 'phone' => $phone, 'address' => $address]);
    return (int) $pdo->lastInsertId();
}

function afrisense_customer_payment_notify_admins(PDO $pdo, array $orderIds, string $customerName, int $createdBy): void
{
    if ($orderIds === [] || !afrisense_public_setting_bool('order_notifications', true)) {
        return;
    }

    $admins = $pdo->prepare("SELECT u.`id` FROM `users` u INNER JOIN `roles` r ON r.`id` = u.`role_id` WHERE LOWER(COALESCE(r.`rolename`, '')) IN ('administrator', 'admin', 'super admin')");
    $admins->execute();
    $notification = $pdo->prepare('INSERT INTO `notifications` (`user_id`, `title`, `message`, `notification_type`, `action_url`, `created_by`) VALUES (:user_id, :title, :message, :notification_type, :action_url, :created_by)');

    foreach ($admins->fetchAll(PDO::FETCH_COLUMN) as $adminId) {
        $notification->execute([
            'user_id' => (int) $adminId,
            'title' => 'New Order Received',
            'message' => count($orderIds) . ' order item(s) have been placed by ' . $customerName . '.',
            'notification_type' => 'Order',
            'action_url' => '/Afrisense/frontend/admin/orders.php?view=' . (int) $orderIds[0] . '#order-row-' . (int) $orderIds[0],
            'created_by' => $createdBy > 0 ? $createdBy : null,
        ]);
    }
}

$cart = $_SESSION['afrisense_customer_cart'] ?? [];
$cart = is_array($cart) ? $cart : [];
$checkout = $_SESSION['afrisense_customer_checkout'] ?? [];
$checkout = is_array($checkout) ? $checkout : [];
$message = null;
$availablePaymentMethods = afrisense_public_payment_methods();
$availableMobileMoneyNetworks = afrisense_public_mobile_money_networks();

try {
    $pdo = afrisense_pdo();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'pay_now') {
        $paymentMethod = (string) ($_POST['payment_method'] ?? ($availablePaymentMethods[0] ?? 'Cash'));
        $deliveryAddress = trim((string) ($checkout['delivery_address'] ?? ''));
        $cartNote = trim((string) ($checkout['cart_note'] ?? $_SESSION['afrisense_customer_cart_note'] ?? ''));

        if ($cart === [] || $deliveryAddress === '' || !afrisense_public_payment_method_allowed($paymentMethod)) {
            $message = ['type' => 'error', 'text' => 'Cart, delivery address, and payment method are required.'];
        } else {
            $ids = array_keys($cart);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $foodLookup = $pdo->prepare('SELECT `id`, `price` FROM `foods` WHERE `availability` = "Available" AND `id` IN (' . $placeholders . ')');
            $foodLookup->execute(array_map('intval', $ids));
            $prices = [];
            foreach ($foodLookup->fetchAll(PDO::FETCH_ASSOC) as $food) {
                $prices[(int) $food['id']] = (float) $food['price'];
            }

            $orderSubtotal = 0.00;
            foreach ($cart as $foodId => $quantity) {
                if (isset($prices[(int) $foodId])) {
                    $orderSubtotal += $prices[(int) $foodId] * max(1, min(20, (int) $quantity));
                }
            }
            $orderDeliveryFee = afrisense_public_delivery_fee($orderSubtotal, $deliveryAddress);

            $pdo->beginTransaction();
            $customerId = afrisense_customer_payment_customer_id($pdo, $authUser, $deliveryAddress);
            $insert = $pdo->prepare('INSERT INTO `orders` (`customer_id`, `food_id`, `quantity`, `total_price`, `delivery_address`, `special_instructions`, `payment_method`, `payment_status`, `order_status`) VALUES (:customer_id, :food_id, :quantity, :total_price, :delivery_address, :special_instructions, :payment_method, :payment_status, :order_status)');
            $orderIds = [];
            $deliveryFeeApplied = false;

            foreach ($cart as $foodId => $quantity) {
                if (!isset($prices[(int) $foodId])) {
                    continue;
                }

                $quantity = max(1, min(20, (int) $quantity));
                $lineTotal = $prices[(int) $foodId] * $quantity;
                if (!$deliveryFeeApplied) {
                    $lineTotal += $orderDeliveryFee;
                    $deliveryFeeApplied = true;
                }
                $insert->execute([
                    'customer_id' => $customerId,
                    'food_id' => (int) $foodId,
                    'quantity' => $quantity,
                    'total_price' => $lineTotal,
                    'delivery_address' => $deliveryAddress,
                    'special_instructions' => $cartNote,
                    'payment_method' => $paymentMethod === 'Card' ? 'Card' : ($paymentMethod === 'Mobile Money' ? 'Mobile Money' : 'Cash'),
                    'payment_status' => $paymentMethod === 'Cash' ? 'Pending' : 'Paid',
                    'order_status' => afrisense_public_paid_order_status($paymentMethod),
                ]);
                $orderIds[] = (int) $pdo->lastInsertId();
            }

            afrisense_customer_payment_notify_admins($pdo, $orderIds, (string) ($authUser['fullname'] ?? 'Customer'), (int) ($authUser['id'] ?? 0));
            $pdo->commit();
            if ($orderIds !== []) {
                afrisense_public_send_order_customer_email_for_order(
                    $pdo,
                    $orderIds[0],
                    'Order Received',
                    'Your AfriSense order #' . str_pad((string) $orderIds[0], 5, '0', STR_PAD_LEFT) . ' has been received. We will notify you when it is confirmed.'
                );
            }
            $_SESSION['afrisense_customer_cart'] = [];
            $_SESSION['afrisense_customer_cart_note'] = '';
            $_SESSION['afrisense_customer_checkout'] = [];
            header('Location: my-orders.php');
            exit;
        }
    }

    $cartFoods = [];
    if ($cart !== []) {
        $ids = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $pdo->prepare('SELECT `id`, `food_name`, `price`, `image` FROM `foods` WHERE `id` IN (' . $placeholders . ')');
        $statement->execute(array_map('intval', $ids));
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $food) {
            $food['quantity'] = max(1, (int) ($cart[(int) $food['id']] ?? 1));
            $cartFoods[] = $food;
        }
    }
    $loadError = '';
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $cartFoods = [];
    $loadError = 'Payment could not be loaded. Check that MySQL is running.';
}

$subtotal = array_reduce($cartFoods, static fn (float $total, array $food): float => $total + ((float) $food['price'] * (int) $food['quantity']), 0.00);
$deliveryFee = $cartFoods === [] ? 0.00 : afrisense_public_delivery_fee($subtotal, (string) ($checkout['delivery_address'] ?? ''));
$total = $subtotal + $deliveryFee;
$publicSettings = afrisense_public_settings();
$supportPhone = (string) ($publicSettings['company']['phone_number_1'] ?? '+233 24 123 4567');
$supportEmail = (string) ($publicSettings['company']['support_email'] ?? 'support@afrisense.com');

ob_start();
?>
<section class="af-payment-page af-customer-payment-page">
    <ol class="af-checkout-steps">
        <li class="done"><span><i class="bi bi-cart3"></i></span>1. Cart <i class="bi bi-check-circle-fill"></i></li>
        <li class="done"><span><i class="bi bi-truck"></i></span>2. Delivery <i class="bi bi-check-circle-fill"></i></li>
        <li class="active"><span><i class="bi bi-credit-card"></i></span>3. Payment</li>
        <li><span><i class="bi bi-check-lg"></i></span>4. Confirmation</li>
    </ol>
    <?php if ($loadError !== ''): ?><div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($message !== null): ?><div class="af-admin-alert <?php echo htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($message['text'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <div class="af-payment-grid">
        <main class="af-payment-card">
            <header class="af-payment-heading"><i class="bi bi-lock"></i><div><h1>Secure Payment</h1><p>Complete your payment to confirm your order.</p></div></header>
            <p class="af-payment-alert"><i class="bi bi-shield-check"></i> Your payment is 100% secure and encrypted.</p>
            <form action="payment.php" method="post">
                <input type="hidden" name="action" value="pay_now">
                <section class="af-payment-section">
                    <h2>Choose Payment Method</h2>
                    <?php foreach ($availablePaymentMethods as $index => $method): ?>
                        <label class="af-payment-method <?php echo $index === 0 ? 'is-active' : ''; ?>">
                            <input type="radio" name="payment_method" value="<?php echo htmlspecialchars($method, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                            <i class="bi <?php echo htmlspecialchars(afrisense_public_payment_method_icon($method), ENT_QUOTES, 'UTF-8'); ?>"></i>
                            <span>
                                <strong><?php echo htmlspecialchars(afrisense_public_payment_method_label($method), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars(afrisense_public_payment_method_hint($method), ENT_QUOTES, 'UTF-8'); ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </section>
                <?php if (in_array('Mobile Money', $availablePaymentMethods, true)): ?>
                <section class="af-payment-section">
                    <h2>Pay with Mobile Money</h2>
                    <div class="af-billing-grid single"><label>Select Network<select name="network"><?php foreach ($availableMobileMoneyNetworks as $network): ?><option><?php echo htmlspecialchars($network, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label></div>
                    <label class="af-payment-input">Mobile Money Number<span><i class="bi bi-telephone"></i><input type="tel" name="momo_number" placeholder="Enter mobile money number"></span></label>
                    <p class="af-payment-warning"><i class="bi bi-info-circle"></i> <?php echo htmlspecialchars(afrisense_public_payment_instruction(), ENT_QUOTES, 'UTF-8'); ?></p>
                </section>
                <?php endif; ?>
                <section class="af-payment-section">
                    <h2>Billing Information</h2>
                    <div class="af-billing-grid">
                        <label>Full Name<input type="text" value="<?php echo htmlspecialchars((string) ($authUser['fullname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></label>
                        <label>Email Address<input type="email" value="<?php echo htmlspecialchars((string) ($authUser['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></label>
                        <label>Phone Number<input type="tel" value="<?php echo htmlspecialchars((string) ($authUser['phonenumber'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></label>
                    </div>
                    <label class="af-terms"><input type="checkbox" required> I have read and agree to the <a href="<?php echo htmlspecialchars($frontendBase . '/landing/terms.php', ENT_QUOTES, 'UTF-8'); ?>">Terms &amp; Conditions</a></label>
                    <button class="af-pay-now" type="submit" <?php echo $cartFoods === [] ? 'disabled' : ''; ?>><i class="bi bi-lock"></i> Pay Now</button>
                    <a class="af-back-delivery" href="cart.php"><i class="bi bi-arrow-left"></i> Back to Cart</a>
                </section>
            </form>
        </main>
        <aside class="af-payment-side">
            <section class="af-summary-card">
                <h2>Order Summary</h2>
                <?php foreach ($cartFoods as $item): ?><article><img src="<?php echo htmlspecialchars(afrisense_customer_payment_image($frontendBase, (string) ($item['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt=""><span><strong><?php echo htmlspecialchars((string) $item['food_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small>Qty: <?php echo (int) $item['quantity']; ?></small></span><b><?php echo htmlspecialchars(afrisense_public_money((float) $item['price'] * (int) $item['quantity']), ENT_QUOTES, 'UTF-8'); ?></b></article><?php endforeach; ?>
                <dl><div><dt>Subtotal</dt><dd><?php echo htmlspecialchars(afrisense_public_money($subtotal), ENT_QUOTES, 'UTF-8'); ?></dd></div><div><dt>Delivery Fee</dt><dd><?php echo htmlspecialchars(afrisense_public_money($deliveryFee), ENT_QUOTES, 'UTF-8'); ?></dd></div><div class="total"><dt>Total Amount</dt><dd><?php echo htmlspecialchars(afrisense_public_money($total), ENT_QUOTES, 'UTF-8'); ?></dd></div></dl>
            </section>
            <section class="af-summary-card"><h2>Why Pay with AfriSense?</h2><ul class="af-pay-reasons"><li><i class="bi bi-shield-check"></i><span><strong>100% Secure Payments</strong>Your payment details are safe with us.</span></li><li><i class="bi bi-hand-thumbs-up"></i><span><strong>Fast &amp; Reliable</strong>Quick payment confirmation and order processing.</span></li><li><i class="bi bi-credit-card"></i><span><strong>Multiple Payment Options</strong>Choose the payment method that works for you.</span></li></ul></section>
            <section class="af-summary-card af-payment-help"><i class="bi bi-headset"></i><div><h2>Need Help?</h2><p>Our support team is here to assist you.</p><strong><a href="support.php">Chat with Support</a></strong><strong>Email: <?php echo htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8'); ?></strong></div></section>
        </aside>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/customer_layout.php';
?>
