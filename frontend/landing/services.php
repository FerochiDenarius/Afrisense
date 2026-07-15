<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Services | AfriSense';
$activePage = 'catering';
$extraStyles = [$frontendBase . '/assets/css/menu-services.css'];
$foodImageBase = $frontendBase . '/assets/images/foods';

$services = [
    ['title' => 'Dine-In Experience', 'desc' => 'Enjoy a luxurious and comfortable dining experience in our warm and elegant restaurant.', 'icon' => 'bi-shop', 'image' => 'jollof-rice.png'],
    ['title' => 'Food Delivery', 'desc' => 'Order your favourite meals and enjoy fast, reliable delivery right to your doorstep.', 'icon' => 'bi-truck', 'image' => 'fried-rice.png'],
    ['title' => 'Event Catering', 'desc' => 'Make your events unforgettable with professional catering for all occasions.', 'icon' => 'bi-bell', 'image' => 'grilled-chicken.png'],
    ['title' => 'Private Dining', 'desc' => 'Host private gatherings in exclusive rooms with personalized service.', 'icon' => 'bi-people', 'image' => 'waakye.png'],
    ['title' => 'Personal Chef', 'desc' => 'Enjoy restaurant-quality meals at home with chef service tailored to your taste.', 'icon' => 'bi-cup-hot', 'image' => 'light-soup.png'],
    ['title' => 'Corporate Meal Plans', 'desc' => 'Healthy and delicious office meal plans delivered on schedule for teams.', 'icon' => 'bi-calendar-check', 'image' => 'fruit-drink.png'],
];

$reasons = [
    ['title' => 'Fresh Ingredients', 'desc' => 'We use fresh, high-quality ingredients.', 'icon' => 'bi-flower1'],
    ['title' => 'Expert Chefs', 'desc' => 'Experienced chefs prepare every dish.', 'icon' => 'bi-award'],
    ['title' => 'Fast Delivery', 'desc' => 'Reliable delivery straight to your door.', 'icon' => 'bi-truck'],
    ['title' => 'Affordable Prices', 'desc' => 'Premium meals at prices that fit.', 'icon' => 'bi-tags'],
];

ob_start();
?>
<section class="af-services-hero">
    <div class="af-services-hero-inner">
        <nav aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Services</span>
        </nav>
        <p class="af-kicker">Catering & Dining</p>
        <h1>Our <span>Services</span></h1>
        <p>From fine dining to catering, AfriSense delivers exceptional culinary experiences tailored to your needs.</p>
        <p><a class="af-menu-order-btn" href="booking.php">Book a Service <i class="bi bi-arrow-right" aria-hidden="true"></i></a></p>
    </div>
</section>

<section class="af-services-page">
    <header class="af-page-heading">
        <p>What We Offer</p>
        <h2>Our Services</h2>
        <small>We offer a wide range of food and catering services designed to give you the best experience.</small>
    </header>

    <div class="af-service-card-grid">
        <?php foreach ($services as $service): ?>
            <article class="af-public-service-card">
                <div class="af-service-image">
                    <img src="<?php echo htmlspecialchars($foodImageBase . '/' . $service['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="af-service-icon"><i class="bi <?php echo htmlspecialchars($service['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                </div>
                <div class="af-public-service-card-body">
                    <h3><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?php echo htmlspecialchars($service['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <a href="booking.php">Learn More <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <section class="af-why-band">
        <p class="af-kicker">Why Choose Us</p>
        <h2>Why Choose AfriSense?</h2>
        <div class="af-why-grid">
            <?php foreach ($reasons as $reason): ?>
                <article>
                    <i class="bi <?php echo htmlspecialchars($reason['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                    <h3><?php echo htmlspecialchars($reason['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?php echo htmlspecialchars($reason['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="af-catering-band">
        <p class="af-kicker">Planning an Event?</p>
        <h2>Let Us Cater Your <span>Special Day</span></h2>
        <p>From birthday parties to corporate events, we provide customized menus and professional service to make your event memorable.</p>
        <a href="booking.php">Book Catering <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </section>
</section>

<section class="af-services-final">
    <h2>Ready to Enjoy Our Services?</h2>
    <p>Book a service or place an order and let AfriSense handle the food experience.</p>
    <div>
        <a href="booking.php">Book a Service <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        <a href="order.php">Order Food Now <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
