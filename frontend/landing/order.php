<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Order | AfriSense';
$activePage = 'menu';
$publicHeaderMode = 'shop';
$customerName = 'Jane Mensah';
$extraStyles = [$frontendBase . '/assets/css/order-payment.css'];

$dishes = [
    ['name' => 'Jollof Rice', 'desc' => 'Classic Nigerian jollof rice served with fried chicken.', 'price' => 'GHc 60.00', 'badge' => 'Bestseller'],
    ['name' => 'Fried Rice', 'desc' => 'Special fried rice with mixed vegetables and chicken.', 'price' => 'GHc 55.00', 'badge' => ''],
    ['name' => 'Grilled Chicken', 'desc' => 'Juicy grilled chicken with our special spices.', 'price' => 'GHc 70.00', 'badge' => ''],
    ['name' => 'Cheese Burger', 'desc' => 'Beef burger with cheese, lettuce and special sauce.', 'price' => 'GHc 45.00', 'badge' => ''],
    ['name' => 'Light Soup', 'desc' => 'Traditional light soup with assorted meat and fish.', 'price' => 'GHc 65.00', 'badge' => ''],
    ['name' => 'Waakye', 'desc' => 'Waakye with stew, egg, wele and shito.', 'price' => 'GHc 50.00', 'badge' => ''],
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

<section class="af-order-page">
    <aside class="af-order-left">
        <section class="af-order-panel">
            <h2>Categories</h2>
            <span class="af-panel-line"></span>
            <nav class="af-category-menu" aria-label="Food categories">
                <a class="is-active" href="#"><i class="bi bi-grid"></i> All Categories</a>
                <a href="#"><i class="bi bi-star"></i> Popular</a>
                <a href="#"><i class="bi bi-bell"></i> Main Dishes</a>
                <a href="#"><i class="bi bi-basket"></i> Rice Dishes</a>
                <a href="#"><i class="bi bi-cup-hot"></i> Soups</a>
                <a href="#"><i class="bi bi-cake2"></i> Snacks &amp; Sides</a>
                <a href="#"><i class="bi bi-cup-straw"></i> Drinks</a>
                <a href="#"><i class="bi bi-gift"></i> Desserts</a>
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
        <form class="af-order-toolbar" action="#" method="get">
            <label><i class="bi bi-search"></i><input type="search" name="search" placeholder="Search for food..."></label>
            <label><select name="sort"><option>Sort by: Popular</option><option>Price: Low to High</option><option>Newest</option></select><i class="bi bi-chevron-down"></i></label>
        </form>

        <h2>Popular Dishes</h2>
        <div class="af-dish-grid">
            <?php foreach ($dishes as $dish): ?>
                <article class="af-dish-card">
                    <div class="af-dish-image">
                        <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        <?php if ($dish['badge'] !== ''): ?><span><?php echo htmlspecialchars($dish['badge'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                        <button type="button" aria-label="Add to wishlist"><i class="bi bi-heart"></i></button>
                    </div>
                    <div class="af-dish-body">
                        <h3><?php echo htmlspecialchars($dish['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo htmlspecialchars($dish['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <strong><?php echo htmlspecialchars($dish['price'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <button type="button"><i class="bi bi-plus-lg"></i> Add to Order</button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <button class="af-load-more" type="button">Load More <i class="bi bi-chevron-down"></i></button>
    </main>

    <aside class="af-order-right">
        <section class="af-cart-panel">
            <header><h2><i class="bi bi-cart3"></i> Your Order (3)</h2><a href="#">Clear All</a></header>
            <div class="af-cart-items">
                <?php foreach ([['Jollof Rice', 'GHc 60.00'], ['Grilled Chicken', 'GHc 70.00'], ['Coca Cola (50cl)', 'GHc 10.00']] as $cartItem): ?>
                    <article>
                        <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        <div><h3><?php echo htmlspecialchars($cartItem[0], ENT_QUOTES, 'UTF-8'); ?></h3><strong><?php echo htmlspecialchars($cartItem[1], ENT_QUOTES, 'UTF-8'); ?></strong><div class="af-qty"><button>-</button><span>1</span><button>+</button></div></div>
                        <button class="af-remove" type="button"><i class="bi bi-trash"></i></button>
                    </article>
                <?php endforeach; ?>
            </div>
            <button class="af-note-btn" type="button"><i class="bi bi-journal-text"></i> Add a note (optional) <i class="bi bi-chevron-down"></i></button>
            <dl class="af-order-total"><div><dt>Subtotal</dt><dd>GHc 140.00</dd></div><div><dt>Delivery Fee</dt><dd>GHc 10.00</dd></div><div><dt>Total</dt><dd>GHc 150.00</dd></div></dl>
            <a class="af-checkout-btn" href="payment.php"><i class="bi bi-bag"></i> Proceed to Checkout</a>
            <p class="af-secure-note"><i class="bi bi-lock"></i> Your payment information is secure and encrypted.</p>
        </section>

        <section class="af-delivery-info">
            <i class="bi bi-scooter"></i>
            <div><h2>Delivery Information</h2><p>Fast delivery within Accra and environs. Estimated delivery time 30 - 60 minutes.</p><a href="#">Change Location <i class="bi bi-arrow-right"></i></a></div>
        </section>
    </aside>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
