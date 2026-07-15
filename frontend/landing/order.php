<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Order | AfriSense';
$activePage = 'menu';
$publicHeaderMode = 'shop';
$extraStyles = [$frontendBase . '/assets/css/order-payment.css'];
$extraScripts = [$frontendBase . '/assets/js/order.js'];
$foodImageBase = $frontendBase . '/assets/images/foods';

require_once __DIR__ . '/../auth/auth_bootstrap.php';

function afrisense_order_post(string $key, string $fallback = ''): string
{
    return trim((string) ($_POST[$key] ?? $fallback));
}

function afrisense_public_food_image(string $frontendBase, ?string $image): string
{
    $image = trim((string) $image);
    $filename = basename($image);

    if ($image !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
        return $frontendBase . '/assets/images/foods/' . $filename;
    }

    return $frontendBase . '/assets/images/foods/jollof-rice.png';
}

function afrisense_order_customer_id(PDO $pdo, string $fullname, string $email, string $phone, string $address): int
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

function afrisense_create_order_notification(PDO $pdo, int $orderId, string $customerName): void
{
    $adminStatement = $pdo->prepare(
        "SELECT u.`id`
         FROM `users` u
         INNER JOIN `roles` r ON r.`id` = u.`role_id`
         WHERE LOWER(COALESCE(r.`rolename`, '')) IN ('administrator', 'admin', 'super admin')"
    );
    $adminStatement->execute();
    $adminIds = $adminStatement->fetchAll(PDO::FETCH_COLUMN);

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
            'message' => 'Order #ORD-' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT) . ' has been placed by ' . $customerName . '.',
            'notification_type' => 'Order',
            'action_url' => '/Afrisense/frontend/admin/orders.php',
            'created_by' => null,
        ]);
    }
}

$orderMessage = null;
$foods = [];
$categories = [];
$selectedFoodId = (int) ($_POST['food_id'] ?? $_GET['food_id'] ?? 0);
$quantity = max(1, min(20, (int) ($_POST['quantity'] ?? 1)));
$search = trim((string) ($_GET['search'] ?? ''));
$categoryFilter = trim((string) ($_GET['category'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? ''));
$currentUser = afrisense_current_user();
$defaultName = (string) ($currentUser['fullname'] ?? '');
$defaultEmail = (string) ($currentUser['email'] ?? '');
$defaultPhone = (string) ($currentUser['phonenumber'] ?? $currentUser['phone'] ?? '');

try {
    $pdo = afrisense_pdo();

    $categoryStatement = $pdo->prepare(
        'SELECT c.`id`, c.`category_name`, COUNT(f.`id`) AS food_count
         FROM `food_categories` c
         INNER JOIN `foods` f ON f.`category_id` = c.`id` AND f.`availability` = :availability
         GROUP BY c.`id`, c.`category_name`
         ORDER BY c.`category_name` ASC'
    );
    $categoryStatement->execute(['availability' => 'Available']);
    $categories = $categoryStatement->fetchAll(PDO::FETCH_ASSOC);

    $where = ['f.`availability` = :availability'];
    $params = ['availability' => 'Available'];

    if ($search !== '') {
        $where[] = '(f.`food_name` LIKE :search OR f.`description` LIKE :search OR c.`category_name` LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    if ($categoryFilter !== '') {
        $where[] = 'c.`category_name` = :category';
        $params['category'] = $categoryFilter;
    }

    $orderBy = match ($sort) {
        'price_asc' => 'f.`price` ASC, f.`food_name` ASC',
        'price_desc' => 'f.`price` DESC, f.`food_name` ASC',
        'newest' => 'f.`created_at` DESC, f.`id` DESC',
        default => 'f.`id` ASC',
    };

    $foodStatement = $pdo->prepare(
        'SELECT
            f.`id`,
            f.`food_name`,
            f.`description`,
            f.`price`,
            f.`image`,
            f.`preparation_time`,
            c.`category_name`
         FROM `foods` f
         INNER JOIN `food_categories` c ON c.`id` = f.`category_id`
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY ' . $orderBy
    );
    $foodStatement->execute($params);
    $foods = $foodStatement->fetchAll(PDO::FETCH_ASSOC);

    if ($selectedFoodId <= 0 && $foods !== []) {
        $selectedFoodId = (int) ($foods[0]['id'] ?? 0);
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $fullname = afrisense_order_post('fullname', $defaultName);
        $email = afrisense_order_post('email', $defaultEmail);
        $phone = preg_replace('/\s+/', '', afrisense_order_post('phone', $defaultPhone));
        $deliveryAddress = afrisense_order_post('delivery_address');
        $specialInstructions = afrisense_order_post('special_instructions');
        $paymentMethod = afrisense_order_post('payment_method', 'Cash');
        $selectedFoodId = (int) ($_POST['food_id'] ?? 0);
        $quantity = max(1, min(20, (int) ($_POST['quantity'] ?? 1)));

        $foodCheck = $pdo->prepare(
            'SELECT `id`, `food_name`, `price`
             FROM `foods`
             WHERE `id` = :id AND `availability` = :availability
             LIMIT 1'
        );
        $foodCheck->execute(['id' => $selectedFoodId, 'availability' => 'Available']);
        $selectedFood = $foodCheck->fetch(PDO::FETCH_ASSOC);

        if (
            $selectedFood === false
            || $fullname === ''
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || $phone === ''
            || $deliveryAddress === ''
            || !in_array($paymentMethod, ['Cash', 'Mobile Money', 'Card'], true)
        ) {
            $orderMessage = ['type' => 'error', 'text' => 'Please complete the food, contact, delivery and payment fields.'];
        } else {
            $totalPrice = ((float) $selectedFood['price'] * $quantity) + 10.00;
            $pdo->beginTransaction();
            $customerId = afrisense_order_customer_id($pdo, $fullname, $email, $phone, $deliveryAddress);
            $insert = $pdo->prepare(
                'INSERT INTO `orders`
                    (`customer_id`, `food_id`, `quantity`, `total_price`, `delivery_address`, `special_instructions`, `payment_method`, `payment_status`, `order_status`)
                 VALUES
                    (:customer_id, :food_id, :quantity, :total_price, :delivery_address, :special_instructions, :payment_method, :payment_status, :order_status)'
            );
            $insert->execute([
                'customer_id' => $customerId,
                'food_id' => (int) $selectedFood['id'],
                'quantity' => $quantity,
                'total_price' => $totalPrice,
                'delivery_address' => $deliveryAddress,
                'special_instructions' => $specialInstructions,
                'payment_method' => $paymentMethod,
                'payment_status' => 'Pending',
                'order_status' => 'Pending',
            ]);
            $orderId = (int) $pdo->lastInsertId();
            afrisense_create_order_notification($pdo, $orderId, $fullname);
            $pdo->commit();

            $orderMessage = [
                'type' => 'success',
                'text' => 'Order #ORD-' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT) . ' submitted. Our team will confirm it shortly.',
            ];
        }
    }
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $orderMessage = ['type' => 'error', 'text' => 'Order could not be submitted. Check that MySQL is running and try again.'];
}

$selectedFood = null;
foreach ($foods as $food) {
    if ((int) ($food['id'] ?? 0) === $selectedFoodId) {
        $selectedFood = $food;
        break;
    }
}

$selectedPrice = (float) ($selectedFood['price'] ?? 0);
$deliveryFee = $selectedFood !== null ? 10.00 : 0.00;
$orderTotal = ($selectedPrice * $quantity) + $deliveryFee;

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

<section class="af-order-page">
    <aside class="af-order-left">
        <section class="af-order-panel">
            <h2>Categories</h2>
            <span class="af-panel-line"></span>
            <nav class="af-category-menu" aria-label="Food categories">
                <a class="<?php echo $categoryFilter === '' ? 'is-active' : ''; ?>" href="order.php"><i class="bi bi-grid"></i> All Categories</a>
                <?php foreach ($categories as $category): ?>
                    <?php $categoryName = (string) $category['category_name']; ?>
                    <a class="<?php echo $categoryFilter === $categoryName ? 'is-active' : ''; ?>" href="order.php?category=<?php echo urlencode($categoryName); ?>">
                        <i class="bi bi-basket"></i>
                        <?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>
                        <small><?php echo htmlspecialchars((string) $category['food_count'], ENT_QUOTES, 'UTF-8'); ?></small>
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
        <?php if ($orderMessage !== null): ?>
            <div class="af-order-alert <?php echo htmlspecialchars($orderMessage['type'], ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi <?php echo $orderMessage['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($orderMessage['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form class="af-order-toolbar" action="order.php" method="get">
            <label><i class="bi bi-search"></i><input type="search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search for food..."></label>
            <?php if ($categoryFilter !== ''): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            <label>
                <select name="sort">
                    <option value="" <?php echo $sort === '' ? 'selected' : ''; ?>>Sort by: Available</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                </select>
                <i class="bi bi-chevron-down"></i>
            </label>
        </form>

        <h2>Available Meals</h2>
        <div class="af-dish-grid">
            <?php if ($foods === []): ?>
                <article class="af-order-empty">
                    <i class="bi bi-basket"></i>
                    <h3>No foods available</h3>
                    <p>Add foods from the admin Foods page to start accepting orders.</p>
                </article>
            <?php endif; ?>
            <?php foreach ($foods as $food): ?>
                <?php
                $foodId = (int) ($food['id'] ?? 0);
                $isSelected = $foodId === $selectedFoodId;
                ?>
                <article class="af-dish-card <?php echo $isSelected ? 'is-selected' : ''; ?>">
                    <div class="af-dish-image">
                        <img src="<?php echo htmlspecialchars(afrisense_public_food_image($frontendBase, (string) ($food['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($food['food_name'] ?? 'Food'), ENT_QUOTES, 'UTF-8'); ?>">
                        <span><?php echo htmlspecialchars((string) ($food['category_name'] ?? 'Food'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <button type="button" aria-label="Add to wishlist"><i class="bi bi-heart"></i></button>
                    </div>
                    <div class="af-dish-body">
                        <h3><?php echo htmlspecialchars((string) ($food['food_name'] ?? 'Food'), ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo htmlspecialchars((string) ($food['description'] ?? 'Freshly prepared AfriSense meal.'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <small><i class="bi bi-clock"></i> <?php echo htmlspecialchars((string) ($food['preparation_time'] ?? 15), ENT_QUOTES, 'UTF-8'); ?> mins</small>
                        <strong>GHc <?php echo htmlspecialchars(number_format((float) ($food['price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <button
                            type="button"
                            data-order-select
                            data-food-id="<?php echo htmlspecialchars((string) $foodId, ENT_QUOTES, 'UTF-8'); ?>"
                            data-food-name="<?php echo htmlspecialchars((string) ($food['food_name'] ?? 'Food'), ENT_QUOTES, 'UTF-8'); ?>"
                            data-food-price="<?php echo htmlspecialchars((string) ($food['price'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                            data-food-image="<?php echo htmlspecialchars(afrisense_public_food_image($frontendBase, (string) ($food['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <i class="bi bi-plus-lg"></i> <?php echo $isSelected ? 'Selected' : 'Order This'; ?>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

    <aside class="af-order-right">
        <form class="af-cart-panel af-order-submit-panel" action="order.php" method="post" data-order-form>
            <header><h2><i class="bi bi-cart3"></i> Order Details</h2><a href="order.php">Reset</a></header>
            <div class="af-selected-order">
                <small>Selected item</small>
                <strong data-order-name><?php echo htmlspecialchars((string) ($selectedFood['food_name'] ?? 'Select a food item'), ENT_QUOTES, 'UTF-8'); ?></strong>
                <div>
                    <span data-order-price>GHc <?php echo htmlspecialchars(number_format($selectedPrice, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                    <em data-order-summary><?php echo htmlspecialchars((string) $quantity, ENT_QUOTES, 'UTF-8'); ?> item<?php echo $quantity === 1 ? '' : 's'; ?> selected</em>
                </div>
            </div>

            <label class="af-order-field" for="order_food_id">
                <span>Food Item</span>
                <select id="order_food_id" name="food_id" required data-order-food-select>
                    <?php foreach ($foods as $food): ?>
                        <option value="<?php echo htmlspecialchars((string) ($food['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" data-price="<?php echo htmlspecialchars((string) ($food['price'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" data-image="<?php echo htmlspecialchars(afrisense_public_food_image($frontendBase, (string) ($food['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int) ($food['id'] ?? 0) === $selectedFoodId ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) ($food['food_name'] ?? 'Food'), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="af-order-field" for="quantity">
                <span>Quantity</span>
                <input type="number" id="quantity" name="quantity" min="1" max="20" value="<?php echo htmlspecialchars((string) $quantity, ENT_QUOTES, 'UTF-8'); ?>" required data-order-quantity>
            </label>

            <label class="af-order-field" for="fullname">
                <span>Full Name</span>
                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars(afrisense_order_post('fullname', $defaultName), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your full name" required>
            </label>

            <label class="af-order-field" for="email">
                <span>Email Address</span>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars(afrisense_order_post('email', $defaultEmail), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your email" required>
            </label>

            <label class="af-order-field" for="phone">
                <span>Phone Number</span>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars(afrisense_order_post('phone', $defaultPhone), ENT_QUOTES, 'UTF-8'); ?>" placeholder="+233 24 123 4567" required>
            </label>

            <label class="af-order-field" for="delivery_address">
                <span>Delivery Address</span>
                <textarea id="delivery_address" name="delivery_address" rows="3" placeholder="Enter your delivery address" required><?php echo htmlspecialchars(afrisense_order_post('delivery_address'), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>

            <label class="af-order-field" for="payment_method">
                <span>Payment Method</span>
                <select id="payment_method" name="payment_method" required>
                    <?php foreach (['Cash', 'Mobile Money', 'Card'] as $method): ?>
                        <option value="<?php echo htmlspecialchars($method, ENT_QUOTES, 'UTF-8'); ?>" <?php echo afrisense_order_post('payment_method', 'Cash') === $method ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($method, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="af-order-field" for="special_instructions">
                <span>Special Instructions</span>
                <textarea id="special_instructions" name="special_instructions" rows="2" placeholder="Optional notes for the kitchen or rider"><?php echo htmlspecialchars(afrisense_order_post('special_instructions'), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>

            <dl class="af-order-total">
                <div><dt>Item Total</dt><dd data-order-subtotal>GHc <?php echo htmlspecialchars(number_format($selectedPrice * $quantity, 2), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                <div><dt>Delivery Fee</dt><dd>GHc <?php echo htmlspecialchars(number_format($deliveryFee, 2), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                <div><dt>Total</dt><dd data-order-total>GHc <?php echo htmlspecialchars(number_format($orderTotal, 2), ENT_QUOTES, 'UTF-8'); ?></dd></div>
            </dl>

            <button class="af-checkout-btn" type="submit" <?php echo $foods === [] ? 'disabled' : ''; ?>>
                <i class="bi bi-bag-check"></i> Place Order
            </button>
            <p class="af-secure-note"><i class="bi bi-lock"></i> Your order is saved securely and will appear in the admin orders page.</p>
        </form>

        <section class="af-delivery-info">
            <i class="bi bi-scooter"></i>
            <div><h2>Delivery Information</h2><p>Fast delivery within Accra and environs. Estimated delivery time 30 - 60 minutes.</p><a href="contact.php">Change Location <i class="bi bi-arrow-right"></i></a></div>
        </section>
    </aside>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
