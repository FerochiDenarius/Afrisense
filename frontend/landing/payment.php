<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Payment | AfriSense';
$activePage = 'menu';
$publicHeaderMode = 'shop';
$extraStyles = [$frontendBase . '/assets/css/order-payment.css'];
$extraScripts = [$frontendBase . '/assets/js/payment-method.js'];

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/public_settings.php';

\AfriSense\Backend\Helpers\Session::start();
afrisense_enforce_public_site_status($frontendBase);
afrisense_enforce_guest_checkout_enabled($frontendBase);
afrisense_enforce_public_delivery_available();

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
$availableMobileMoneyNetworks = afrisense_public_mobile_money_networks();
$selectedPaymentMethod = afrisense_public_payment_method_allowed((string) ($details['payment_method'] ?? ''))
    ? (string) $details['payment_method']
    : ($availablePaymentMethods[0] ?? 'Cash');
$orderCompleted = false;
$completedPaymentMethod = '';

try {
    $pdo = afrisense_pdo();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'pay_now') {
        $paymentMethod = trim((string) ($_POST['payment_method'] ?? ($availablePaymentMethods[0] ?? 'Cash')));
        $details['payment_method'] = $paymentMethod;
        $selectedPaymentMethod = $paymentMethod;
        $requiresOnlinePayment = $paymentMethod !== 'Cash';

        if ($cart === [] || empty($details['fullname']) || !filter_var((string) ($details['email'] ?? ''), FILTER_VALIDATE_EMAIL) || empty($details['phone']) || empty($details['delivery_address']) || !afrisense_public_payment_method_allowed($paymentMethod)) {
            $message = ['type' => 'error', 'text' => 'Complete your cart and delivery details before continuing.'];
        } elseif ($requiresOnlinePayment && empty($_POST['accept_payment_terms'])) {
            $message = ['type' => 'error', 'text' => 'Accept the payment terms before continuing to online payment.'];
        } elseif ($paymentMethod === 'Mobile Money' && trim((string) ($_POST['momo_number'] ?? '')) === '') {
            $message = ['type' => 'error', 'text' => 'Enter your mobile money number before continuing to payment.'];
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
            if ($orderIds !== []) {
                afrisense_public_send_order_customer_email_for_order(
                    $pdo,
                    $orderIds[0],
                    'Order Received',
                    'Your AfriSense order #' . str_pad((string) $orderIds[0], 5, '0', STR_PAD_LEFT) . ' has been received. We will notify you when it is confirmed.'
                );
            }
            $_SESSION['afrisense_guest_cart'] = [];
            $_SESSION['afrisense_guest_cart_note'] = '';
            $_SESSION['afrisense_guest_checkout'] = [];
            $cart = [];
            $orderCompleted = true;
            $completedPaymentMethod = $paymentMethod;
            $message = ['type' => 'success', 'text' => $paymentMethod === 'Cash' ? 'Your order has been placed successfully. Please pay when your food arrives.' : 'Your payment has been received and your order has been placed successfully.'];
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
        <li class="<?php echo $orderCompleted ? 'done' : 'active'; ?>"><span><i class="bi bi-credit-card"></i></span>3. Payment<?php if ($orderCompleted): ?> <i class="bi bi-check-circle-fill"></i><?php endif; ?></li>
        <li class="<?php echo $orderCompleted ? 'active' : ''; ?>"><span><i class="bi bi-check-lg"></i></span>4. Confirmation</li>
    </ol>
    <?php if ($loadError !== ''): ?><div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($message !== null): ?><div class="af-admin-alert <?php echo htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($message['text'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <div class="af-payment-grid">
        <main class="af-payment-card">
            <?php if ($orderCompleted): ?>
                <header class="af-payment-heading"><i class="bi bi-check-circle"></i><div><h1>Order Confirmed</h1><p><?php echo $completedPaymentMethod === 'Cash' ? 'Your order is on its way to the kitchen. Payment will be collected on delivery.' : 'Your online payment has been recorded and the order is moving to preparation.'; ?></p></div></header>
                <section class="af-payment-section">
                    <h2>Confirmation</h2>
                    <p class="af-payment-warning"><i class="bi bi-info-circle"></i> We will contact you if the restaurant needs to confirm any delivery details.</p>
                    <a class="af-pay-now" href="order.php"><i class="bi bi-bag-plus"></i> Continue Shopping</a>
                </section>
            <?php else: ?>
            <header class="af-payment-heading" data-payment-heading data-cash-title="Confirm Order" data-cash-copy="No upfront payment is needed. Place your order and pay when your food arrives." data-online-title="Secure Payment" data-online-copy="Complete your payment to confirm your order."><i class="bi bi-lock"></i><div><h1><?php echo $selectedPaymentMethod === 'Cash' ? 'Confirm Order' : 'Secure Payment'; ?></h1><p><?php echo $selectedPaymentMethod === 'Cash' ? 'No upfront payment is needed. Place your order and pay when your food arrives.' : 'Complete your payment to confirm your order.'; ?></p></div></header>
            <p class="af-payment-alert" data-payment-online-note <?php echo $selectedPaymentMethod === 'Cash' ? 'hidden' : ''; ?>><i class="bi bi-shield-check"></i> Your payment is 100% secure and encrypted.</p>
            <form method="post" data-payment-flow>
                <input type="hidden" name="action" value="pay_now">
                <section class="af-payment-section">
                    <h2>Choose Payment Method</h2>
                    <?php foreach ($availablePaymentMethods as $method): ?>
                        <label class="af-payment-method <?php echo $selectedPaymentMethod === $method ? 'is-active' : ''; ?>" data-payment-choice>
                            <input type="radio" name="payment_method" value="<?php echo htmlspecialchars($method, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedPaymentMethod === $method ? 'checked' : ''; ?>>
                            <i class="bi <?php echo htmlspecialchars(afrisense_public_payment_method_icon($method), ENT_QUOTES, 'UTF-8'); ?>"></i>
                            <span><strong><?php echo htmlspecialchars(afrisense_public_payment_method_label($method), ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars(afrisense_public_payment_method_hint($method), ENT_QUOTES, 'UTF-8'); ?></small></span>
                        </label>
                    <?php endforeach; ?>
                </section>
                <?php if (in_array('Cash', $availablePaymentMethods, true)): ?>
                    <section class="af-payment-section" data-payment-panel="Cash" <?php echo $selectedPaymentMethod === 'Cash' ? '' : 'hidden'; ?>>
                        <h2>Cash on Delivery</h2>
                        <p class="af-payment-warning"><i class="bi bi-cash-coin"></i> No online payment is required. Place the order now and pay the rider when your food arrives.</p>
                    </section>
                <?php endif; ?>
                <?php if (in_array('Mobile Money', $availablePaymentMethods, true)): ?>
                    <section class="af-payment-section" data-payment-panel="Mobile Money" <?php echo $selectedPaymentMethod === 'Mobile Money' ? '' : 'hidden'; ?>>
                        <h2>Pay with Mobile Money</h2>
                        <div class="af-billing-grid single"><label>Select Network<select name="network" <?php echo $selectedPaymentMethod === 'Mobile Money' ? '' : 'disabled'; ?>><?php foreach ($availableMobileMoneyNetworks as $network): ?><option><?php echo htmlspecialchars($network, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label></div>
                        <label class="af-payment-input">Mobile Money Number<span><i class="bi bi-telephone"></i><input type="tel" name="momo_number" placeholder="Enter mobile money number" <?php echo $selectedPaymentMethod === 'Mobile Money' ? '' : 'disabled'; ?>></span></label>
                        <p class="af-payment-warning"><i class="bi bi-info-circle"></i> <?php echo htmlspecialchars(afrisense_public_payment_instruction(), ENT_QUOTES, 'UTF-8'); ?></p>
                    </section>
                <?php endif; ?>
                <?php if (in_array('Card', $availablePaymentMethods, true)): ?>
                    <section class="af-payment-section" data-payment-panel="Card" <?php echo $selectedPaymentMethod === 'Card' ? '' : 'hidden'; ?>>
                        <h2>Card Payment</h2>
                        <p class="af-payment-warning"><i class="bi bi-credit-card"></i> Continue to the enabled card gateway to complete payment before the order is confirmed.</p>
                    </section>
                <?php endif; ?>
                <section class="af-payment-section">
                    <h2>Billing Information</h2>
                    <div class="af-billing-grid"><label>Full Name<input type="text" value="<?php echo htmlspecialchars((string) ($details['fullname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></label><label>Email Address<input type="email" value="<?php echo htmlspecialchars((string) ($details['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></label><label>Phone Number<input type="tel" value="<?php echo htmlspecialchars((string) ($details['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></label></div>
                    <label class="af-terms" data-payment-terms <?php echo $selectedPaymentMethod === 'Cash' ? 'hidden' : ''; ?>><input type="checkbox" name="accept_payment_terms" value="1" data-payment-terms-checkbox> I have read and agree to the <a href="terms.php">Terms &amp; Conditions</a></label>
                    <button class="af-pay-now" type="submit" data-payment-submit data-cash-label="Place Order" data-online-label="Pay Now" <?php echo $cartFoods === [] ? 'disabled' : ''; ?>><i class="bi <?php echo $selectedPaymentMethod === 'Cash' ? 'bi-check2-circle' : 'bi-lock'; ?>" data-payment-submit-icon></i> <span><?php echo $selectedPaymentMethod === 'Cash' ? 'Place Order' : 'Pay Now'; ?></span></button>
                    <a class="af-back-delivery" href="cart.php"><i class="bi bi-arrow-left"></i> Back to Cart</a>
                </section>
            </form>
            <?php endif; ?>
        </main>
        <aside class="af-payment-side"><section class="af-summary-card"><h2>Order Summary</h2><?php foreach ($cartFoods as $item): ?><article><img src="<?php echo htmlspecialchars(afrisense_guest_payment_image($frontendBase, (string) ($item['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt=""><span><strong><?php echo htmlspecialchars((string) $item['food_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small>Qty: <?php echo (int) $item['quantity']; ?></small></span><b><?php echo htmlspecialchars(afrisense_public_money((float) $item['price'] * (int) $item['quantity']), ENT_QUOTES, 'UTF-8'); ?></b></article><?php endforeach; ?><dl><div><dt>Subtotal</dt><dd><?php echo htmlspecialchars(afrisense_public_money($subtotal), ENT_QUOTES, 'UTF-8'); ?></dd></div><div><dt>Delivery Fee</dt><dd><?php echo htmlspecialchars(afrisense_public_money($deliveryFee), ENT_QUOTES, 'UTF-8'); ?></dd></div><div class="total"><dt>Total Amount</dt><dd><?php echo htmlspecialchars(afrisense_public_money($total), ENT_QUOTES, 'UTF-8'); ?></dd></div></dl></section><section class="af-summary-card"><h2>Why Pay with AfriSense?</h2><ul class="af-pay-reasons"><li><i class="bi bi-shield-check"></i><span><strong>100% Secure Payments</strong>Your payment details are safe with us.</span></li><li><i class="bi bi-hand-thumbs-up"></i><span><strong>Fast &amp; Reliable</strong>Quick payment confirmation and order processing.</span></li><li><i class="bi bi-credit-card"></i><span><strong>Multiple Payment Options</strong>Choose the payment method that works for you.</span></li></ul></section><section class="af-summary-card af-payment-help"><i class="bi bi-headset"></i><div><h2>Need Help?</h2><p>Our support team is here to assist you.</p><strong><a href="support.php">Chat with Support</a></strong><strong>Email: <?php echo htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8'); ?></strong></div></section></aside>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
