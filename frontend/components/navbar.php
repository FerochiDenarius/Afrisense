<?php
$frontendBase = $frontendBase ?? '/Afrisense/frontend';
$activePage = $activePage ?? '';

$navItems = [
    'home' => ['label' => 'Home', 'href' => $frontendBase . '/landing/index.php'],
    'menu' => ['label' => 'Menu', 'href' => $frontendBase . '/landing/menu.php'],
    'catering' => ['label' => 'Catering Packages', 'href' => $frontendBase . '/landing/services.php'],
    'booking' => ['label' => 'Book a Service', 'href' => $frontendBase . '/landing/booking.php'],
    'about' => ['label' => 'About Us', 'href' => $frontendBase . '/landing/about.php'],
    'contact' => ['label' => 'Contact Us', 'href' => $frontendBase . '/landing/contact.php'],
];
?>
<header class="af-public-header" data-navbar>
    <nav class="af-navbar" aria-label="Primary navigation">
        <a class="af-brand" href="<?php echo htmlspecialchars($frontendBase . '/landing/index.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="AfriSense home">
            <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
            <span>
                <strong>AfriSense</strong>
                <small>Food Services</small>
            </span>
        </a>

        <button class="af-nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false" data-navbar-toggle>
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <div class="af-nav-menu" data-navbar-menu>
            <ul class="af-nav-links">
                <?php foreach ($navItems as $key => $item): ?>
                    <li>
                        <a class="<?php echo $activePage === $key ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="af-nav-actions">
                <a class="af-phone-link" href="tel:+233241234567">
                    <i class="bi bi-telephone" aria-hidden="true"></i>
                    <span>+233 24 123 4567</span>
                </a>
                <a class="af-order-btn" href="<?php echo htmlspecialchars($frontendBase . '/landing/order.php', ENT_QUOTES, 'UTF-8'); ?>">Order Now</a>
            </div>
        </div>
    </nav>
</header>
