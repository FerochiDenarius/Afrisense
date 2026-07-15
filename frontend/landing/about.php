<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'About Us | AfriSense';
$activePage = 'about';
$extraStyles = [$frontendBase . '/assets/css/menu-services.css'];

$values = [
    ['title' => 'Quality Food', 'desc' => 'Fresh ingredients and careful preparation guide every meal we serve.', 'icon' => 'bi-award'],
    ['title' => 'Reliable Service', 'desc' => 'Our team supports dine-in guests, delivery customers, and event clients with care.', 'icon' => 'bi-people'],
    ['title' => 'Memorable Moments', 'desc' => 'We design food experiences for everyday meals and special occasions.', 'icon' => 'bi-heart'],
];

ob_start();
?>
<section class="af-services-hero">
    <div class="af-services-hero-inner">
        <nav aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>About Us</span>
        </nav>
        <p class="af-kicker">About AfriSense</p>
        <h1>Food Made for <span>Good Moments</span></h1>
        <p>AfriSense Food Services provides meals, catering, and hospitality experiences for customers across Accra.</p>
    </div>
</section>

<section class="af-services-page">
    <header class="af-page-heading">
        <p>Who We Are</p>
        <h2>Serving Taste, Quality, and Excellence</h2>
        <small>From daily meals to private events, our work is built around fresh food, thoughtful service, and dependable delivery.</small>
    </header>

    <div class="af-service-card-grid">
        <?php foreach ($values as $value): ?>
            <article class="af-public-service-card">
                <div class="af-service-image">
                    <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($value['title'], ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="af-service-icon"><i class="bi <?php echo htmlspecialchars($value['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                </div>
                <div class="af-public-service-card-body">
                    <h3><?php echo htmlspecialchars($value['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?php echo htmlspecialchars($value['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <a href="contact.php">Contact Us <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <section class="af-catering-band">
        <p class="af-kicker">Work With Us</p>
        <h2>Planning Food for Your <span>Next Event?</span></h2>
        <p>Tell us what you need and our team will help you choose the right service, menu, and schedule.</p>
        <a href="booking.php">Book a Service <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
