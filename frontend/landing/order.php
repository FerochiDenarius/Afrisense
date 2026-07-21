<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Order | AfriSense';
$activePage = 'menu';
$publicHeaderMode = 'shop';
$extraStyles = [$frontendBase . '/assets/css/order-payment.css'];
$extraScripts = [$frontendBase . '/assets/js/order.js'];

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/public_settings.php';

\AfriSense\Backend\Helpers\Session::start();

function afrisense_guest_food_image(string $frontendBase, ?string $image): string
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

function afrisense_guest_cart(): array
{
    $cart = $_SESSION['afrisense_guest_cart'] ?? [];

    return is_array($cart) ? $cart : [];
}

function afrisense_save_guest_cart(array $cart): void
{
    $_SESSION['afrisense_guest_cart'] = array_filter($cart, static fn (int $quantity): bool => $quantity > 0);
}

function afrisense_guest_cart_count(array $cart): int
{
    return array_sum(array_map('intval', $cart));
}

function afrisense_guest_post(string $key, string $fallback = ''): string
{
    return trim((string) ($_POST[$key] ?? $fallback));
}

function afrisense_guest_category_filter(string $category): array
{
    return match ($category) {
        'main' => ['c.`category_name` IN (\'Main Course\', \'Main Dishes\')', []],
        'rice' => ['f.`food_name` LIKE :rice_name', ['rice_name' => '%Rice%']],
        'soups' => ['f.`food_name` LIKE :soup_name', ['soup_name' => '%Soup%']],
        'snacks' => ['c.`category_name` IN (\'Fast Food\', \'Snacks & Sides\')', []],
        'drinks' => ['c.`category_name` = :drink_category', ['drink_category' => 'Drinks']],
        'desserts' => ['c.`category_name` = :dessert_category', ['dessert_category' => 'Desserts']],
        default => ['', []],
    };
}

function afrisense_guest_customer_id(PDO $pdo, string $fullname, string $email, string $phone, string $address): int
{
    $statement = $pdo->prepare(
        'SELECT `id`
         FROM `customers`
         WHERE `email` = :email OR `phone_number` = :phone
         ORDER BY `id` ASC
         LIMIT 1'
    );
    $statement->execute(['email' => $email, 'phone' => $phone]);
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
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'id' => (int) $customerId,
        ]);

        return (int) $customerId;
    }

    $insert = $pdo->prepare(
        'INSERT INTO `customers` (`fullname`, `email`, `phone_number`, `address`)
         VALUES (:fullname, :email, :phone, :address)'
    );
    $insert->execute([
        'fullname' => $fullname,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
    ]);

    return (int) $pdo->lastInsertId();
}

function afrisense_guest_order_notifications(PDO $pdo, array $orderIds, string $customerName): void
{
    if ($orderIds === []) {
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

    if ($adminIds === []) {
        return;
    }

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
            'message' => count($orderIds) . ' order item(s) have been placed by ' . $customerName . '.',
            'notification_type' => 'Order',
            'action_url' => '/Afrisense/frontend/admin/orders.php?view=' . (int) $orderIds[0] . '#order-row-' . (int) $orderIds[0],
            'created_by' => null,
        ]);
    }
}

$cart = afrisense_guest_cart();
$note = (string) ($_SESSION['afrisense_guest_cart_note'] ?? '');
$message = null;
$search = trim((string) ($_GET['search'] ?? ''));
$category = trim((string) ($_GET['category'] ?? 'all'));
$sort = trim((string) ($_GET['sort'] ?? 'popular'));

try {
    $pdo = afrisense_pdo();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = afrisense_guest_post('action');
        $foodId = (int) ($_POST['food_id'] ?? 0);

        if ($action === 'add_to_cart' && $foodId > 0) {
            $cart[$foodId] = min(20, ((int) ($cart[$foodId] ?? 0)) + 1);
            $message = ['type' => 'success', 'text' => 'Item added to your order.'];
        }

        if ($action === 'increase' && isset($cart[$foodId])) {
            $cart[$foodId] = min(20, (int) $cart[$foodId] + 1);
        }

        if ($action === 'decrease' && isset($cart[$foodId])) {
            $cart[$foodId] = (int) $cart[$foodId] - 1;
        }

        if ($action === 'remove') {
            unset($cart[$foodId]);
        }

        if ($action === 'clear_cart') {
            $cart = [];
            $_SESSION['afrisense_guest_cart_note'] = '';
        }

        if ($action === 'save_note') {
            $_SESSION['afrisense_guest_cart_note'] = afrisense_guest_post('cart_note');
            $note = (string) $_SESSION['afrisense_guest_cart_note'];
        }

        if ($action === 'checkout') {
            $fullname = afrisense_guest_post('fullname');
            $email = afrisense_guest_post('email');
            $phone = preg_replace('/\s+/', '', afrisense_guest_post('phone'));
            $address = afrisense_guest_post('delivery_address');
            $availablePaymentMethods = afrisense_public_payment_methods();
            $paymentMethod = afrisense_guest_post('payment_method', $availablePaymentMethods[0] ?? 'Cash');
            $note = afrisense_guest_post('cart_note', $note);

            if ($cart === [] || $fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $address === '' || !afrisense_public_payment_method_allowed($paymentMethod)) {
                $message = ['type' => 'error', 'text' => 'Complete your contact, delivery and payment details before checkout.'];
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

                $orderSubtotal = 0.00;
                foreach ($cart as $cartFoodId => $quantity) {
                    if (isset($prices[(int) $cartFoodId])) {
                        $orderSubtotal += $prices[(int) $cartFoodId] * max(1, min(20, (int) $quantity));
                    }
                }
                $orderDeliveryFee = afrisense_public_delivery_fee($orderSubtotal, $address);

                $pdo->beginTransaction();
                $customerId = afrisense_guest_customer_id($pdo, $fullname, $email, $phone, $address);
                $insert = $pdo->prepare(
                    'INSERT INTO `orders`
                        (`customer_id`, `food_id`, `quantity`, `total_price`, `delivery_address`, `special_instructions`, `payment_method`, `payment_status`, `order_status`)
                     VALUES
                        (:customer_id, :food_id, :quantity, :total_price, :delivery_address, :special_instructions, :payment_method, :payment_status, :order_status)'
                );
                $createdOrderIds = [];
                $deliveryFeeApplied = false;

                foreach ($cart as $cartFoodId => $quantity) {
                    if (!isset($prices[(int) $cartFoodId])) {
                        continue;
                    }

                    $quantity = max(1, min(20, (int) $quantity));
                    $lineTotal = $prices[(int) $cartFoodId] * $quantity;

                    if (!$deliveryFeeApplied) {
                        $lineTotal += $orderDeliveryFee;
                        $deliveryFeeApplied = true;
                    }

                    $insert->execute([
                        'customer_id' => $customerId,
                        'food_id' => (int) $cartFoodId,
                        'quantity' => $quantity,
                        'total_price' => $lineTotal,
                        'delivery_address' => $address,
                        'special_instructions' => $note,
                        'payment_method' => $paymentMethod,
                        'payment_status' => $paymentMethod === 'Cash' ? 'Pending' : 'Paid',
                        'order_status' => afrisense_public_paid_order_status($paymentMethod),
                    ]);
                    $createdOrderIds[] = (int) $pdo->lastInsertId();
                }

                afrisense_guest_order_notifications($pdo, $createdOrderIds, $fullname);
                $pdo->commit();
                $cart = [];
                $_SESSION['afrisense_guest_cart_note'] = '';
                $note = '';
                $message = ['type' => 'success', 'text' => 'Your order has been placed successfully.'];
            }
        }

        afrisense_save_guest_cart($cart);
    }

    $where = ['f.`availability` = :availability'];
    $params = ['availability' => 'Available'];
    [$categorySql, $categoryParams] = afrisense_guest_category_filter($category);

    if ($categorySql !== '') {
        $where[] = $categorySql;
        $params = array_merge($params, $categoryParams);
    }

    if ($search !== '') {
        $where[] = '(f.`food_name` LIKE :search OR f.`description` LIKE :search OR c.`category_name` LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $orderBy = match ($sort) {
        'newest' => 'f.`created_at` DESC, f.`id` DESC',
        'price_asc' => 'f.`price` ASC, f.`food_name` ASC',
        'price_desc' => 'f.`price` DESC, f.`food_name` ASC',
        'best' => 'f.`id` ASC',
        default => 'f.`id` ASC',
    };

    $foodsStatement = $pdo->prepare(
        'SELECT
            f.`id`,
            f.`food_name`,
            f.`description`,
            f.`price`,
            f.`image`,
            c.`category_name`
         FROM `foods` f
         INNER JOIN `food_categories` c ON c.`id` = f.`category_id`
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY ' . $orderBy
    );
    $foodsStatement->execute($params);
    $foods = $foodsStatement->fetchAll(PDO::FETCH_ASSOC);

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

    $foods = [];
    $cartFoods = [];
    $message = ['type' => 'error', 'text' => 'Order page could not load. Check that MySQL is running.'];
}

$subtotal = array_reduce(
    $cartFoods,
    static fn (float $total, array $food): float => $total + ((float) $food['price'] * (int) $food['quantity']),
    0.00
);
$deliveryFee = $cartFoods === [] ? 0.00 : afrisense_public_delivery_fee($subtotal);
$total = $subtotal + $deliveryFee;
$cartCount = afrisense_guest_cart_count($cart);
$categoryItems = [
    ['key' => 'all', 'label' => 'All Categories', 'icon' => 'bi-grid'],
    ['key' => 'popular', 'label' => 'Popular', 'icon' => 'bi-star'],
    ['key' => 'main', 'label' => 'Main Dishes', 'icon' => 'bi-egg-fried'],
    ['key' => 'rice', 'label' => 'Rice Dishes', 'icon' => 'bi-basket'],
    ['key' => 'soups', 'label' => 'Soups', 'icon' => 'bi-cup-hot'],
    ['key' => 'snacks', 'label' => 'Snacks & Sides', 'icon' => 'bi-cookie'],
    ['key' => 'drinks', 'label' => 'Drinks', 'icon' => 'bi-cup-straw'],
    ['key' => 'desserts', 'label' => 'Desserts', 'icon' => 'bi-cake2'],
];

ob_start();
?>
<section class="af-order-hero">
    <div class="af-order-hero-inner">
        <nav aria-label="Breadcrumb"><a href="index.php">Home</a><i class="bi bi-chevron-right"></i><span>Orders</span></nav>
        <h1>Place Your <span>Order</span></h1>
        <p>Delicious meals, delivered fresh to your doorstep.</p>
        <div class="af-order-benefits">
            <article><i class="bi bi-award"></i><strong>Freshly Prepared</strong><span>Quality ingredients</span></article>
            <article><i class="bi bi-flower1"></i><strong>Fast Delivery</strong><span>On-time guarantee</span></article>
            <article><i class="bi bi-shield-check"></i><strong>Secure Payment</strong><span>100% safe &amp; secure</span></article>
        </div>
    </div>
</section>

<section class="af-order-page af-public-order-page">
    <aside class="af-order-left">
        <section class="af-order-panel">
            <h2>Categories</h2>
            <span class="af-panel-line"></span>
            <nav class="af-category-menu" aria-label="Food categories">
                <?php foreach ($categoryItems as $item): ?>
                    <a class="<?php echo $category === $item['key'] || ($category === '' && $item['key'] === 'all') ? 'is-active' : ''; ?>" href="order.php?category=<?php echo urlencode($item['key']); ?>">
                        <i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                        <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </section>

        <section class="af-help-box">
            <i class="bi bi-headset"></i>
            <h2>Need Help?</h2>
            <p>Our customer support team is ready to help you.</p>
            <a href="tel:+233241234567"><i class="bi bi-telephone"></i> +233 24 123 4567</a>
            <small>Mon - Sun: 8:00 AM - 10:00 PM</small>
        </section>
    </aside>

    <main class="af-order-main">
        <?php if ($message !== null): ?>
            <div class="af-order-alert <?php echo htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi <?php echo $message['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form class="af-order-toolbar" action="order.php" method="get">
            <label><i class="bi bi-search"></i><input type="search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search for food..."></label>
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>">
            <label>
                <select name="sort" data-auto-submit>
                    <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Sort by: Popular</option>
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price Low to High</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price High to Low</option>
                    <option value="best" <?php echo $sort === 'best' ? 'selected' : ''; ?>>Best Selling</option>
                </select>
                <i class="bi bi-chevron-down"></i>
            </label>
        </form>

        <h2>Popular Dishes</h2>
        <div class="af-dish-grid">
            <?php if ($foods === []): ?>
                <article class="af-order-empty"><i class="bi bi-basket"></i><h3>No foods found</h3><p>Try another category or search term.</p></article>
            <?php endif; ?>
            <?php foreach ($foods as $food): ?>
                <article class="af-dish-card">
                    <div class="af-dish-image">
                        <img src="<?php echo htmlspecialchars(afrisense_guest_food_image($frontendBase, (string) ($food['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($food['food_name'] ?? 'Food'), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="button" aria-label="Add to wishlist" data-favorite><i class="bi bi-heart"></i></button>
                    </div>
                    <div class="af-dish-body">
                        <h3><?php echo htmlspecialchars((string) ($food['food_name'] ?? 'Food'), ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo htmlspecialchars((string) ($food['description'] ?? 'Freshly prepared AfriSense meal.'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <strong>GH₵ <?php echo htmlspecialchars(number_format((float) ($food['price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <form action="order.php?category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" method="post">
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="food_id" value="<?php echo htmlspecialchars((string) ($food['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit"><i class="bi bi-plus-lg"></i> Add to Order</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

    <aside class="af-order-right">
        <section class="af-cart-panel af-public-cart-panel">
            <header>
                <h2><i class="bi bi-cart3"></i> Your Order (<?php echo htmlspecialchars((string) $cartCount, ENT_QUOTES, 'UTF-8'); ?>)</h2>
                <form action="order.php" method="post">
                    <input type="hidden" name="action" value="clear_cart">
                    <button type="submit">Clear All</button>
                </form>
            </header>

            <div class="af-cart-items">
                <?php if ($cartFoods === []): ?>
                    <p class="af-empty-cart">Your cart is empty. Add a meal to start your order.</p>
                <?php endif; ?>
                <?php foreach ($cartFoods as $cartFood): ?>
                    <article>
                        <img src="<?php echo htmlspecialchars(afrisense_guest_food_image($frontendBase, (string) ($cartFood['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $cartFood['food_name'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div>
                            <h3><?php echo htmlspecialchars((string) $cartFood['food_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <strong>GH₵ <?php echo htmlspecialchars(number_format((float) $cartFood['price'], 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <div class="af-qty">
                                <form action="order.php" method="post"><input type="hidden" name="action" value="decrease"><input type="hidden" name="food_id" value="<?php echo htmlspecialchars((string) $cartFood['id'], ENT_QUOTES, 'UTF-8'); ?>"><button type="submit">−</button></form>
                                <span><?php echo htmlspecialchars((string) $cartFood['quantity'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <form action="order.php" method="post"><input type="hidden" name="action" value="increase"><input type="hidden" name="food_id" value="<?php echo htmlspecialchars((string) $cartFood['id'], ENT_QUOTES, 'UTF-8'); ?>"><button type="submit">+</button></form>
                            </div>
                        </div>
                        <form action="order.php" method="post">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="food_id" value="<?php echo htmlspecialchars((string) $cartFood['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button class="af-remove" type="submit" aria-label="Remove item"><i class="bi bi-trash"></i></button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>

            <form class="af-public-cart-note" action="order.php" method="post">
                <input type="hidden" name="action" value="save_note">
                <button type="button" data-note-toggle><i class="bi bi-journal-text"></i> Add a note (optional) <i class="bi bi-chevron-down"></i></button>
                <textarea name="cart_note" rows="3" placeholder="Add kitchen or delivery notes..."><?php echo htmlspecialchars($note, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <button type="submit">Save Note</button>
            </form>

            <form class="af-public-checkout-form" action="cart.php" method="get">
                <dl class="af-order-total">
                    <div><dt>Subtotal</dt><dd><?php echo htmlspecialchars(afrisense_public_money($subtotal), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>Delivery Fee</dt><dd><?php echo htmlspecialchars(afrisense_public_money($deliveryFee), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>Total</dt><dd><?php echo htmlspecialchars(afrisense_public_money($total), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                </dl>
                <button class="af-checkout-btn" type="submit" <?php echo $cartFoods === [] ? 'disabled' : ''; ?>><i class="bi bi-bag-check"></i> View Cart &amp; Checkout</button>
            </form>
            <p class="af-secure-note"><i class="bi bi-lock"></i> Your payment information is secure and encrypted.</p>
        </section>

        <section class="af-delivery-info">
            <i class="bi bi-scooter"></i>
            <div><h2>Delivery Information</h2><p><?php echo htmlspecialchars(afrisense_public_delivery_instructions(), ENT_QUOTES, 'UTF-8'); ?> Estimated delivery time <?php echo htmlspecialchars(afrisense_public_delivery_time(), ENT_QUOTES, 'UTF-8'); ?>.</p><a href="contact.php">Change Location <i class="bi bi-arrow-right"></i></a></div>
        </section>
    </aside>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
