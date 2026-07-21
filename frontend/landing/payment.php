<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Payment | AfriSense';
$activePage = 'menu';
$publicHeaderMode = 'shop';
$extraStyles = [$frontendBase . '/assets/css/order-payment.css'];

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/public_settings.php';

\AfriSense\Backend\Helpers\Session::start();

function afrisense_guest_payment_image(string $frontendBase, ?string $image): string
{
    $filename = basename(trim((string) $image));

    if ($filename !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
        return $frontendBase . '/assets/images/foods/' . $filename;
    }

    return $frontendBase . '/assets/images/foods/jollof-rice.png';
}

function afrisense_guest_payment_customer_id(PDO $pdo, array $details): int
{
    $statement = $pdo->prepare('SELECT `id` FROM `customers` WHERE `email` = :email OR `phone_number` = :phone ORDER BY `id` ASC LIMIT 1');
    $statement->execute(['email' => $details['email'], 'phone' => $details['phone']]);
    $customerId = $statement->fetchColumn();

    if ($customerId !== false) {
        $update = $pdo->prepare('UPDATE `customers` SET `fullname` = :fullname, `email` = :email, `phone_number` = :phone, `address` = :address, `updated_at` = NOW() WHERE `id` = :id');
        $update->execute(['fullname' => $details['fullname'], 'email' => $details['email'], 'phone' => $details['phone'], 'address' => $details['delivery_address'], 'id' => (int) $customerId]);
        return (int) $customerId;
    }

    $insert = $pdo->prepare('INSERT INTO `customers` (`fullname`, `email`, `phone_number`, `address`) VALUES (:fullname, :email, :phone, :address)');
    $insert->execute(['fullname' => $details['fullname'], 'email' => $details['email'], 'phone' => $details['phone'], 'address' => $details['delivery_address']]);
    return (int) $pdo->lastInsertId();
}

function afrisense_guest_payment_notify_admins(PDO $pdo, array $orderIds, string $customerName): void
{
    if ($orderIds === []) {
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
            'created_by' => null,
        ]);
    }
}

$cart = $_SESSION['afrisense_guest_cart'] ?? [];
$cart = is_array($cart) ? $cart : [];
$details = $_SESSION['afrisense_guest_checkout'] ?? [];
$details = is_array($details) ? $details : [];
$message = null;
$availablePaymentMethods = afrisense_public_payment_methods();

try {
    $pdo = afrisense_pdo();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'pay_now') {
        $paymentMethod = (string) ($_POST['payment_method'] ?? ($availablePaymentMethods[0] ?? 'Cash'));
        $details['payment_method'] = $paymentMethod;

        if ($cart === [] || empty($details['fullname']) || !filter_var((string) ($details['email'] ?? ''), FILTER_VALIDATE_EMAIL) || empty($details['phone']) || empty($details['delivery_address']) || !afrisense_public_payment_method_allowed($paymentMethod)) {
            $message = ['type' => 'error', 'text' => 'Complete your cart and delivery details before payment.'];
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
            $orderDeliveryFee = afrisense_public_delivery_fee($orderSubtotal, (string) ($details['delivery_address'] ?? ''));

            $pdo->beginTransaction();
            $customerId = afrisense_guest_payment_customer_id($pdo, $details);
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
                    'delivery_address' => (string) $details['delivery_address'],
                    'special_instructions' => (string) ($details['cart_note'] ?? ''),
                    'payment_method' => $paymentMethod === 'Card' ? 'Card' : ($paymentMethod === 'Mobile Money' ? 'Mobile Money' : 'Cash'),
                    'payment_status' => $paymentMethod === 'Cash' ? 'Pending' : 'Paid',
                    'order_status' => afrisense_public_paid_order_status($paymentMethod),
                ]);
                $orderIds[] = (int) $pdo->lastInsertId();
            }

            afrisense_guest_payment_notify_admins($pdo, $orderIds, (string) $details['fullname']);
            $pdo->commit();
            $_SESSION['afrisense_guest_cart'] = [];
            $_SESSION['afrisense_guest_cart_note'] = '';
            $_SESSION['afrisense_guest_checkout'] = [];
            $cart = [];
            $message = ['type' => 'success', 'text' => 'Your order has been placed successfully.'];
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
$deliveryFee = $cartFoods === [] ? 0.00 : afrisense_public_delivery_fee($subtotal, (string) ($details['delivery_address'] ?? ''));
$total = $subtotal + $deliveryFee;
$publicSettings = afrisense_public_settings();
$supportPhone = (string) ($publicSettings['company']['phone_number_1'] ?? '+233 24 123 4567');
$supportEmail = (string) ($publicSettings['company']['support_email'] ?? 'support@afrisense.com');

ob_start();
?>
<section class="af-payment-page">
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
            <form method="post">
                <input type="hidden" name="action" value="pay_now">
                <section class="af-payment-section"><h2>Choose Payment Method</h2><?php foreach ($availablePaymentMethods as $index => $method): ?><label class="af-payment-method <?php echo $index === 0 ? 'is-active' : ''; ?>"><input type="radio" name="payment_method" value="<?php echo htmlspecialchars($method, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $index === 0 ? 'checked' : ''; ?>><i class="bi <?php echo $method === 'Card' ? 'bi-credit-card' : ($method === 'Cash' ? 'bi-cash-coin' : 'bi-phone'); ?>"></i><span><strong><?php echo htmlspecialchars($method === 'Card' ? 'Card Payment' : ($method === 'Cash' ? 'Cash on Delivery' : 'Mobile Money'), ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($method === 'Card' ? 'Pay securely using your debit or credit card' : ($method === 'Cash' ? 'Pay when your food arrives' : 'Pay using enabled mobile money gateways'), ENT_QUOTES, 'UTF-8'); ?></small></span></label><?php endforeach; ?></section>
                <?php if (in_array('Mobile Money', $availablePaymentMethods, true)): ?><section class="af-payment-section"><h2>Pay with Mobile Money</h2><div class="af-billing-grid single"><label>Select Network<select name="network"><option>MTN Mobile Money</option><option>Vodafone Cash</option><option>AirtelTigo Money</option></select></label></div><label class="af-payment-input">Mobile Money Number<span><i class="bi bi-telephone"></i><input type="tel" name="momo_number" placeholder="Enter mobile money number"></span></label><p class="af-payment-warning"><i class="bi bi-info-circle"></i> <?php echo htmlspecialchars(afrisense_public_payment_instruction(), ENT_QUOTES, 'UTF-8'); ?></p></section><?php endif; ?>
                <section class="af-payment-section"><h2>Billing Information</h2><div class="af-billing-grid"><label>Full Name<input type="text" value="<?php echo htmlspecialchars((string) ($details['fullname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></label><label>Email Address<input type="email" value="<?php echo htmlspecialchars((string) ($details['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></label><label>Phone Number<input type="tel" value="<?php echo htmlspecialchars((string) ($details['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></label></div><label class="af-terms"><input type="checkbox" required> I have read and agree to the <a href="terms.php">Terms &amp; Conditions</a></label><button class="af-pay-now" type="submit" <?php echo $cartFoods === [] ? 'disabled' : ''; ?>><i class="bi bi-lock"></i> Pay Now</button><a class="af-back-delivery" href="cart.php"><i class="bi bi-arrow-left"></i> Back to Cart</a></section>
            </form>
        </main>
        <aside class="af-payment-side"><section class="af-summary-card"><h2>Order Summary</h2><?php foreach ($cartFoods as $item): ?><article><img src="<?php echo htmlspecialchars(afrisense_guest_payment_image($frontendBase, (string) ($item['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt=""><span><strong><?php echo htmlspecialchars((string) $item['food_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small>Qty: <?php echo (int) $item['quantity']; ?></small></span><b><?php echo htmlspecialchars(afrisense_public_money((float) $item['price'] * (int) $item['quantity']), ENT_QUOTES, 'UTF-8'); ?></b></article><?php endforeach; ?><dl><div><dt>Subtotal</dt><dd><?php echo htmlspecialchars(afrisense_public_money($subtotal), ENT_QUOTES, 'UTF-8'); ?></dd></div><div><dt>Delivery Fee</dt><dd><?php echo htmlspecialchars(afrisense_public_money($deliveryFee), ENT_QUOTES, 'UTF-8'); ?></dd></div><div class="total"><dt>Total Amount</dt><dd><?php echo htmlspecialchars(afrisense_public_money($total), ENT_QUOTES, 'UTF-8'); ?></dd></div></dl></section><section class="af-summary-card"><h2>Why Pay with AfriSense?</h2><ul class="af-pay-reasons"><li><i class="bi bi-shield-check"></i><span><strong>100% Secure Payments</strong>Your payment details are safe with us.</span></li><li><i class="bi bi-hand-thumbs-up"></i><span><strong>Fast &amp; Reliable</strong>Quick payment confirmation and order processing.</span></li><li><i class="bi bi-credit-card"></i><span><strong>Multiple Payment Options</strong>Choose the payment method that works for you.</span></li></ul></section><section class="af-summary-card af-payment-help"><i class="bi bi-headset"></i><div><h2>Need Help?</h2><p>Our support team is here to assist you.</p><strong>Call / WhatsApp: <?php echo htmlspecialchars($supportPhone, ENT_QUOTES, 'UTF-8'); ?></strong><strong>Email: <?php echo htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8'); ?></strong></div></section></aside>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
