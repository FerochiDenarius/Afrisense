<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Cart | AfriSense';
$customerTitle = 'Cart';
$activeCustomerPage = 'cart';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/order-payment.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/public_settings.php';

\AfriSense\Backend\Helpers\Session::start();
$authUser = afrisense_require_customer();

function afrisense_customer_cart_image(string $frontendBase, ?string $image): string
{
    $relativeImage = ltrim(str_replace('\\', '/', trim((string) $image)), '/');
    $filename = basename($relativeImage);

    if ($filename !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
        return $frontendBase . '/assets/images/foods/' . $filename;
    }

    if ($relativeImage !== '' && is_file(__DIR__ . '/../uploads/' . $relativeImage)) {
        return $frontendBase . '/uploads/' . $relativeImage;
    }

    if ($filename !== '' && is_file(__DIR__ . '/../uploads/' . $filename)) {
        return $frontendBase . '/uploads/' . $filename;
    }

    return $frontendBase . '/assets/images/foods/jollof-rice.png';
}

function afrisense_customer_cart_customer(PDO $pdo, array $user): ?array
{
    $email = trim((string) ($user['email'] ?? ''));
    $phone = preg_replace('/\s+/', '', trim((string) ($user['phonenumber'] ?? $user['phone'] ?? '')));
    $statement = $pdo->prepare(
        'SELECT *
         FROM `customers`
         WHERE `email` = :email OR REPLACE(`phone_number`, " ", "") = :phone
         ORDER BY `id` ASC
         LIMIT 1'
    );
    $statement->execute(['email' => $email, 'phone' => $phone]);
    $customer = $statement->fetch(PDO::FETCH_ASSOC);

    return $customer ?: null;
}

$cart = $_SESSION['afrisense_customer_cart'] ?? [];
$cart = is_array($cart) ? $cart : [];
$checkout = $_SESSION['afrisense_customer_checkout'] ?? [];
$checkout = is_array($checkout) ? $checkout : [];
$message = null;

try {
    $pdo = afrisense_pdo();
    $customer = afrisense_customer_cart_customer($pdo, $authUser);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = trim((string) ($_POST['action'] ?? ''));
        $foodId = (int) ($_POST['food_id'] ?? 0);

        if ($action === 'increase' && isset($cart[$foodId])) {
            $cart[$foodId] = min(20, (int) $cart[$foodId] + 1);
        }

        if ($action === 'decrease' && isset($cart[$foodId])) {
            $cart[$foodId] = max(0, (int) $cart[$foodId] - 1);
        }

        if ($action === 'remove') {
            unset($cart[$foodId]);
        }

        if ($action === 'clear_cart') {
            $cart = [];
            $_SESSION['afrisense_customer_cart_note'] = '';
            $_SESSION['afrisense_customer_checkout'] = [];
        }

        if ($action === 'save_checkout') {
            $deliveryAddress = trim((string) ($_POST['delivery_address'] ?? ''));
            $cartNote = trim((string) ($_POST['cart_note'] ?? ''));

            if ($cart === []) {
                $message = ['type' => 'error', 'text' => 'Add food items before checkout.'];
            } elseif ($deliveryAddress === '') {
                $message = ['type' => 'error', 'text' => 'Enter a delivery address before checkout.'];
            } else {
                $_SESSION['afrisense_customer_cart_note'] = $cartNote;
                $_SESSION['afrisense_customer_checkout'] = [
                    'delivery_address' => $deliveryAddress,
                    'cart_note' => $cartNote,
                ];
                header('Location: payment.php');
                exit;
            }
        }

        $_SESSION['afrisense_customer_cart'] = array_filter($cart, static fn (int $quantity): bool => $quantity > 0);
        $cart = $_SESSION['afrisense_customer_cart'];
    }

    $cartFoods = [];
    if ($cart !== []) {
        $ids = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $pdo->prepare(
            'SELECT `id`, `food_name`, `description`, `price`, `image`
             FROM `foods`
             WHERE `id` IN (' . $placeholders . ')
             ORDER BY FIELD(`id`, ' . $placeholders . ')'
        );
        $statement->execute(array_merge(array_map('intval', $ids), array_map('intval', $ids)));

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $food) {
            $food['quantity'] = max(1, (int) ($cart[(int) $food['id']] ?? 1));
            $cartFoods[] = $food;
        }
    }

    $loadError = '';
} catch (Throwable $exception) {
    $cartFoods = [];
    $customer = null;
    $loadError = 'Cart could not be loaded. Check that MySQL is running.';
}

$subtotal = array_reduce($cartFoods, static fn (float $total, array $food): float => $total + ((float) $food['price'] * (int) $food['quantity']), 0.00);
$defaultAddress = (string) ($checkout['delivery_address'] ?? $customer['address'] ?? '');
$deliveryFee = $cartFoods === [] ? 0.00 : afrisense_public_delivery_fee($subtotal, $defaultAddress);
$serviceFee = $cartFoods === [] ? 0.00 : afrisense_public_service_fee();
$discount = $subtotal >= 200 ? 15.00 : 0.00;
$total = max(0.00, $subtotal + $deliveryFee + $serviceFee - $discount);
$freeDeliveryOver = afrisense_public_free_delivery_over();
$freeDeliveryRemaining = max(0.00, $freeDeliveryOver - $subtotal);
$cartNote = (string) ($_SESSION['afrisense_customer_cart_note'] ?? $checkout['cart_note'] ?? '');

ob_start();
?>
<section class="af-customer-cart-page">
    <header class="af-cart-page-heading">
        <h1>Your Cart</h1>
        <p>Review your items, update quantities, and proceed to checkout.</p>
    </header>

    <?php if ($loadError !== ''): ?><div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($message !== null): ?><div class="af-admin-alert <?php echo htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($message['text'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <section class="af-cart-layout">
        <main>
            <div class="af-free-delivery">
                <span><i class="bi bi-check-circle" aria-hidden="true"></i> You're only <?php echo htmlspecialchars(afrisense_public_money($freeDeliveryRemaining), ENT_QUOTES, 'UTF-8'); ?> away from FREE delivery!</span>
                <strong><?php echo htmlspecialchars(afrisense_public_money($freeDeliveryRemaining), ENT_QUOTES, 'UTF-8'); ?></strong>
                <i style="--progress: <?php echo htmlspecialchars((string) ($freeDeliveryOver > 0 ? min(100, ($subtotal / $freeDeliveryOver) * 100) : 100), ENT_QUOTES, 'UTF-8'); ?>%"></i>
            </div>

            <section class="af-cart-table-card">
                <table>
                    <thead><tr><th>Item</th><th>Price</th><th>Quantity</th><th>Total</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if ($cartFoods === []): ?><tr><td colspan="5"><div class="af-empty-state">Your cart is empty.</div></td></tr><?php endif; ?>
                        <?php foreach ($cartFoods as $food): ?>
                            <tr>
                                <td>
                                    <div class="af-cart-product">
                                        <img src="<?php echo htmlspecialchars(afrisense_customer_cart_image($frontendBase, (string) ($food['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                                        <span><strong><?php echo htmlspecialchars((string) $food['food_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars((string) ($food['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small></span>
                                    </div>
                                </td>
                                <td>GHC <?php echo htmlspecialchars(number_format((float) $food['price'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><div class="af-cart-stepper"><form method="post"><input type="hidden" name="action" value="decrease"><input type="hidden" name="food_id" value="<?php echo (int) $food['id']; ?>"><button type="submit">−</button></form><span><?php echo (int) $food['quantity']; ?></span><form method="post"><input type="hidden" name="action" value="increase"><input type="hidden" name="food_id" value="<?php echo (int) $food['id']; ?>"><button type="submit">+</button></form></div></td>
                                <td>GHC <?php echo htmlspecialchars(number_format((float) $food['price'] * (int) $food['quantity'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><form method="post"><input type="hidden" name="action" value="remove"><input type="hidden" name="food_id" value="<?php echo (int) $food['id']; ?>"><button class="af-cart-icon-button" type="submit" aria-label="Remove item"><i class="bi bi-trash"></i></button></form></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <footer>
                    <form method="post"><input type="hidden" name="action" value="clear_cart"><button type="submit"><i class="bi bi-trash"></i> Clear Cart</button></form>
                    <a href="orders.php"><i class="bi bi-arrow-clockwise"></i> Update Cart</a>
                </footer>
            </section>

            <section class="af-cart-benefits">
                <article><i class="bi bi-shield-check"></i><strong>Secure Payment</strong><span>100% safe and secure payments.</span></article>
                <article><i class="bi bi-scooter"></i><strong>Fast Delivery</strong><span>Quick delivery to your doorstep.</span></article>
                <article><i class="bi bi-award"></i><strong>Best Quality</strong><span>Fresh and delicious meals always.</span></article>
                <article><i class="bi bi-headset"></i><strong>24/7 Support</strong><span>We're here to help you anytime.</span></article>
            </section>
        </main>

        <aside class="af-cart-summary-panel">
            <h2>Order Summary</h2>
            <dl>
                <div><dt>Subtotal (<?php echo htmlspecialchars((string) array_sum(array_map('intval', $cart)), ENT_QUOTES, 'UTF-8'); ?> items)</dt><dd><?php echo htmlspecialchars(afrisense_public_money($subtotal), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                <div><dt>Delivery Fee</dt><dd><?php echo htmlspecialchars(afrisense_public_money($deliveryFee), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                <div><dt>Service Fee <i class="bi bi-info-circle"></i></dt><dd><?php echo htmlspecialchars(afrisense_public_money($serviceFee), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                <div><dt>Discount <i class="bi bi-tags"></i></dt><dd class="discount">- <?php echo htmlspecialchars(afrisense_public_money($discount), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                <div class="total"><dt>Total</dt><dd><?php echo htmlspecialchars(afrisense_public_money($total), ENT_QUOTES, 'UTF-8'); ?></dd></div>
            </dl>
            <p class="af-delivery-estimate"><i class="bi bi-scooter"></i><span>Estimated Delivery Time <strong><?php echo htmlspecialchars(afrisense_public_delivery_time(), ENT_QUOTES, 'UTF-8'); ?></strong></span></p>
            <form class="af-cart-checkout-box" method="post">
                <input type="hidden" name="action" value="save_checkout">
                <label>Delivering to<input type="text" name="delivery_address" value="<?php echo htmlspecialchars($defaultAddress, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Home - East Legon, Accra" required></label>
                <label>Order note<textarea name="cart_note" maxlength="200" placeholder="E.g. No onions, extra spicy..."><?php echo htmlspecialchars($cartNote, ENT_QUOTES, 'UTF-8'); ?></textarea></label>
                <button type="submit" <?php echo $cartFoods === [] ? 'disabled' : ''; ?>><i class="bi bi-lock"></i> Proceed to Checkout</button>
            </form>
            <small><i class="bi bi-lock"></i> Secure checkout. Your data is protected.</small>
        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/customer_layout.php';
?>
