<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Order Food | AfriSense';
$customerTitle = 'Order Food';
$activeCustomerPage = 'place_order';
$extraStyles = [$frontendBase . '/assets/css/orders.css'];
$extraScripts = [$frontendBase . '/assets/js/orders.js'];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

\AfriSense\Backend\Helpers\Session::start();

function afrisense_customer_order_image(string $frontendBase, ?string $image): string
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

function afrisense_customer_cart(): array
{
    $cart = $_SESSION['afrisense_customer_cart'] ?? [];

    return is_array($cart) ? $cart : [];
}

function afrisense_save_customer_cart(array $cart): void
{
    $_SESSION['afrisense_customer_cart'] = array_filter(
        $cart,
        static fn (int $quantity): bool => $quantity > 0
    );
}

function afrisense_customer_cart_count(array $cart): int
{
    return array_sum(array_map('intval', $cart));
}

function afrisense_customer_filter_sql(string $filter): array
{
    return match ($filter) {
        'main' => ['c.`category_name` IN (\'Main Course\', \'Main Dishes\')', []],
        'rice' => ['f.`food_name` LIKE :rice_name', ['rice_name' => '%Rice%']],
        'soups' => ['f.`food_name` LIKE :soup_name', ['soup_name' => '%Soup%']],
        'snacks' => ['c.`category_name` IN (\'Fast Food\', \'Snacks & Sides\')', []],
        'drinks' => ['c.`category_name` = :drink_category', ['drink_category' => 'Drinks']],
        'desserts' => ['c.`category_name` = :dessert_category', ['dessert_category' => 'Desserts']],
        default => ['', []],
    };
}

function afrisense_customer_id_for_order(PDO $pdo, array $user, string $address): int
{
    $fullname = trim((string) ($user['fullname'] ?? $user['name'] ?? 'Customer'));
    $email = trim((string) ($user['email'] ?? ''));
    $phone = preg_replace('/\s+/', '', trim((string) ($user['phonenumber'] ?? $user['phone'] ?? '0240000000')));

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

function afrisense_customer_admin_notifications(PDO $pdo, array $orderIds, string $customerName): void
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
            'created_by' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
        ]);
    }
}

$authUser = afrisense_require_customer();
$cart = afrisense_customer_cart();
$cartNote = (string) ($_SESSION['afrisense_customer_cart_note'] ?? '');
$flashMessage = '';
$flashType = 'success';

try {
    $pdo = afrisense_pdo();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = trim((string) ($_POST['action'] ?? ''));
        $foodId = (int) ($_POST['food_id'] ?? 0);

        if ($action === 'add_to_cart' && $foodId > 0) {
            $cart[$foodId] = min(20, ((int) ($cart[$foodId] ?? 0)) + 1);
            $flashMessage = 'Item added to your order.';
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
            $_SESSION['afrisense_customer_cart_note'] = '';
        }

        if ($action === 'save_note') {
            $_SESSION['afrisense_customer_cart_note'] = trim((string) ($_POST['cart_note'] ?? ''));
        }

        if ($action === 'checkout') {
            $deliveryAddress = trim((string) ($_POST['delivery_address'] ?? ''));
            $paymentMethod = trim((string) ($_POST['payment_method'] ?? 'Cash'));

            if ($cart === [] || $deliveryAddress === '' || !in_array($paymentMethod, ['Cash', 'Mobile Money', 'Card'], true)) {
                $flashType = 'error';
                $flashMessage = 'Add food items, delivery address and payment method before checkout.';
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
                $customerId = afrisense_customer_id_for_order($pdo, $authUser, $deliveryAddress);
                $insert = $pdo->prepare(
                    'INSERT INTO `orders`
                        (`customer_id`, `food_id`, `quantity`, `total_price`, `delivery_address`, `special_instructions`, `payment_method`, `payment_status`, `order_status`)
                     VALUES
                        (:customer_id, :food_id, :quantity, :total_price, :delivery_address, :special_instructions, :payment_method, :payment_status, :order_status)'
                );
                $orderIds = [];
                $deliveryFeeApplied = false;

                foreach ($cart as $cartFoodId => $quantity) {
                    if (!isset($prices[(int) $cartFoodId])) {
                        continue;
                    }

                    $quantity = max(1, min(20, (int) $quantity));
                    $lineTotal = ($prices[(int) $cartFoodId] * $quantity);

                    if (!$deliveryFeeApplied) {
                        $lineTotal += 10.00;
                        $deliveryFeeApplied = true;
                    }

                    $insert->execute([
                        'customer_id' => $customerId,
                        'food_id' => (int) $cartFoodId,
                        'quantity' => $quantity,
                        'total_price' => $lineTotal,
                        'delivery_address' => $deliveryAddress,
                        'special_instructions' => $cartNote,
                        'payment_method' => $paymentMethod,
                        'payment_status' => 'Pending',
                        'order_status' => 'Pending',
                    ]);
                    $orderIds[] = (int) $pdo->lastInsertId();
                }

                afrisense_customer_admin_notifications($pdo, $orderIds, (string) ($authUser['fullname'] ?? $authUser['email'] ?? 'Customer'));
                $pdo->commit();
                $cart = [];
                $_SESSION['afrisense_customer_cart_note'] = '';
                $flashMessage = 'Your order has been placed successfully.';
            }
        }

        afrisense_save_customer_cart($cart);
        $cartNote = (string) ($_SESSION['afrisense_customer_cart_note'] ?? $cartNote);
    }

    $search = trim((string) ($_GET['search'] ?? ''));
    $category = trim((string) ($_GET['category'] ?? 'all'));
    $sort = trim((string) ($_GET['sort'] ?? 'popular'));
    $where = ['f.`availability` = :availability'];
    $params = ['availability' => 'Available'];
    [$categorySql, $categoryParams] = afrisense_customer_filter_sql($category);

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
        $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
        $cartStatement = $pdo->prepare(
            'SELECT `id`, `food_name`, `price`, `image`
             FROM `foods`
             WHERE `id` IN (' . $placeholders . ')'
        );
        $cartStatement->execute(array_map('intval', $cartIds));

        foreach ($cartStatement->fetchAll(PDO::FETCH_ASSOC) as $food) {
            $foodId = (int) $food['id'];
            $food['quantity'] = max(1, (int) ($cart[$foodId] ?? 1));
            $cartFoods[] = $food;
        }
    }
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $foods = [];
    $cartFoods = [];
    $flashType = 'error';
    $flashMessage = 'Orders could not be loaded. Check that MySQL is running.';
}

$deliveryFee = $cartFoods === [] ? 0.00 : 10.00;
$subtotal = array_reduce(
    $cartFoods,
    static fn (float $total, array $food): float => $total + ((float) $food['price'] * (int) $food['quantity']),
    0.00
);
$total = $subtotal + $deliveryFee;
$cartCount = afrisense_customer_cart_count($cart);
$defaultAddress = '';

if (isset($pdo)) {
    $addressStatement = $pdo->prepare('SELECT `address` FROM `customers` WHERE `email` = :email LIMIT 1');
    $addressStatement->execute(['email' => (string) ($authUser['email'] ?? '')]);
    $defaultAddress = (string) ($addressStatement->fetchColumn() ?: '');
}

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
<section class="af-customer-order-page">
    <section class="af-order-hero">
        <div class="af-order-hero-overlay">
            <nav aria-label="Breadcrumb">
                <a href="<?php echo htmlspecialchars($frontendBase . '/customer/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>">Home</a>
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                <span>Orders</span>
            </nav>
            <h1>Place Your <span>Order</span></h1>
            <p>Delicious meals, delivered fresh to your doorstep.</p>
            <div class="af-order-features">
                <article><i class="bi bi-award" aria-hidden="true"></i><strong>Freshly Prepared</strong><span>Quality ingredients</span></article>
                <article><i class="bi bi-flower1" aria-hidden="true"></i><strong>Fast Delivery</strong><span>On-time guarantee</span></article>
                <article><i class="bi bi-shield-check" aria-hidden="true"></i><strong>Secure Payment</strong><span>100% safe &amp; secure</span></article>
            </div>
        </div>
    </section>

    <?php if ($flashMessage !== ''): ?>
        <div class="af-order-flash <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi <?php echo $flashType === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>" aria-hidden="true"></i>
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section class="af-order-layout">
        <aside class="af-order-left">
            <section class="af-order-panel">
                <h2>Categories</h2>
                <span class="af-panel-rule"></span>
                <nav class="af-category-list" aria-label="Food categories">
                    <?php foreach ($categoryItems as $item): ?>
                        <a class="<?php echo $category === $item['key'] || ($category === '' && $item['key'] === 'all') ? 'is-active' : ''; ?>" href="orders.php?category=<?php echo urlencode($item['key']); ?>">
                            <i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </section>

            <section class="af-help-card">
                <i class="bi bi-headset" aria-hidden="true"></i>
                <h2>Need Help?</h2>
                <p>Our customer support team is ready to help you.</p>
                <a href="tel:+233241234567"><i class="bi bi-telephone" aria-hidden="true"></i> +233 24 123 4567</a>
                <small>Mon - Sun: 8:00 AM - 10:00 PM</small>
            </section>
        </aside>

        <main class="af-order-center">
            <form class="af-order-toolbar" action="orders.php" method="get">
                <label for="food_search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="food_search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search for food...">
                </label>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>">
                <label for="food_sort">
                    <select id="food_sort" name="sort">
                        <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Sort by: Popular</option>
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price Low to High</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price High to Low</option>
                        <option value="best" <?php echo $sort === 'best' ? 'selected' : ''; ?>>Best Selling</option>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
            </form>

            <h2>Popular Dishes</h2>
            <div class="af-food-grid">
                <?php if ($foods === []): ?>
                    <article class="af-empty-foods">
                        <i class="bi bi-basket" aria-hidden="true"></i>
                        <h3>No foods found</h3>
                        <p>Try another category or search term.</p>
                    </article>
                <?php endif; ?>
                <?php foreach ($foods as $food): ?>
                    <article class="af-food-card">
                        <div class="af-food-image">
                            <img src="<?php echo htmlspecialchars(afrisense_customer_order_image($frontendBase, (string) ($food['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $food['food_name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button class="af-favorite-btn" type="button" aria-label="Add to favourites" data-favorite>
                                <i class="bi bi-heart" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="af-food-body">
                            <h3><?php echo htmlspecialchars((string) $food['food_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><?php echo htmlspecialchars((string) ($food['description'] ?? 'Freshly prepared meal.'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <strong>GH₵ <?php echo htmlspecialchars(number_format((float) $food['price'], 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <form action="orders.php?category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" method="post">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="food_id" value="<?php echo htmlspecialchars((string) $food['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add to Order</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </main>

        <aside class="af-order-right">
            <section class="af-cart-card">
                <header>
                    <h2><i class="bi bi-cart3" aria-hidden="true"></i> Your Order (<?php echo htmlspecialchars((string) $cartCount, ENT_QUOTES, 'UTF-8'); ?>)</h2>
                    <form action="orders.php" method="post">
                        <input type="hidden" name="action" value="clear_cart">
                        <button type="submit">Clear All</button>
                    </form>
                </header>

                <div class="af-cart-items">
                    <?php if ($cartFoods === []): ?>
                        <p class="af-empty-cart">Your cart is empty. Add a meal to start your order.</p>
                    <?php endif; ?>
                    <?php foreach ($cartFoods as $item): ?>
                        <article>
                            <img src="<?php echo htmlspecialchars(afrisense_customer_order_image($frontendBase, (string) ($item['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $item['food_name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div>
                                <h3><?php echo htmlspecialchars((string) $item['food_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <strong>GH₵ <?php echo htmlspecialchars(number_format((float) $item['price'], 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <div class="af-cart-controls">
                                    <form action="orders.php" method="post"><input type="hidden" name="action" value="decrease"><input type="hidden" name="food_id" value="<?php echo htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8'); ?>"><button type="submit" aria-label="Decrease quantity">−</button></form>
                                    <span><?php echo htmlspecialchars((string) $item['quantity'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <form action="orders.php" method="post"><input type="hidden" name="action" value="increase"><input type="hidden" name="food_id" value="<?php echo htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8'); ?>"><button type="submit" aria-label="Increase quantity">+</button></form>
                                </div>
                            </div>
                            <form action="orders.php" method="post">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="food_id" value="<?php echo htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button class="af-remove-item" type="submit" aria-label="Remove item"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>

                <form class="af-note-form" action="orders.php" method="post">
                    <input type="hidden" name="action" value="save_note">
                    <button type="button" data-note-toggle><i class="bi bi-journal-text" aria-hidden="true"></i> Add a note (optional) <i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                    <textarea name="cart_note" rows="3" placeholder="Add kitchen or delivery notes..."><?php echo htmlspecialchars($cartNote, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <button type="submit">Save Note</button>
                </form>

                <form class="af-checkout-form" action="cart.php" method="get">
                    <label for="delivery_address">Delivery Address</label>
                    <textarea id="delivery_address" name="delivery_address" rows="2" placeholder="Enter delivery address" required><?php echo htmlspecialchars($defaultAddress, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="Cash">Cash</option>
                        <option value="Mobile Money">Mobile Money</option>
                        <option value="Card">Card</option>
                    </select>
                    <dl class="af-cart-summary">
                        <div><dt>Subtotal</dt><dd>GH₵ <?php echo htmlspecialchars(number_format($subtotal, 2), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div><dt>Delivery Fee</dt><dd>GH₵ <?php echo htmlspecialchars(number_format($deliveryFee, 2), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div><dt>Total</dt><dd>GH₵ <?php echo htmlspecialchars(number_format($total, 2), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    </dl>
                    <button class="af-checkout-btn" type="submit" <?php echo $cartFoods === [] ? 'disabled' : ''; ?>><i class="bi bi-bag-check" aria-hidden="true"></i> View Cart &amp; Checkout</button>
                </form>

                <p class="af-secure-note"><i class="bi bi-lock" aria-hidden="true"></i> Your payment information is secure and encrypted.</p>
            </section>

            <section class="af-delivery-card">
                <i class="bi bi-scooter" aria-hidden="true"></i>
                <div>
                    <h2>Delivery Information</h2>
                    <p>Fast delivery within Accra and environs. Estimated delivery time 30 - 60 minutes.</p>
                    <a href="<?php echo htmlspecialchars($frontendBase . '/customer/profile.php', ENT_QUOTES, 'UTF-8'); ?>">Change Location <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
            </section>
        </aside>
    </section>
</section>
<?php require __DIR__ . '/../components/footer.php'; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/customer_layout.php';
?>
