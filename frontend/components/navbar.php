<?php
require_once __DIR__ . '/../includes/public_settings.php';

$frontendBase = $frontendBase ?? '/Afrisense/frontend';
$activePage = $activePage ?? '';
$publicHeaderMode = $publicHeaderMode ?? 'default';
$customerName = $customerName ?? 'Jane Mensah';
$publicSettings = afrisense_public_settings();
$siteName = (string) ($publicSettings['website']['site_name'] ?? 'AfriSense Food Services');
$primaryPhone = (string) ($publicSettings['company']['phone_number_1'] ?? '+233 24 123 4567');

$navItems = [
    'home' => ['label' => 'Home', 'href' => $frontendBase . '/landing/index.php'],
    'menu' => ['label' => 'Menu', 'href' => $frontendBase . '/landing/menu.php'],
    'gallery' => ['label' => 'Gallery', 'href' => $frontendBase . '/landing/gallery.php'],
    'catering' => ['label' => 'Catering Packages', 'href' => $frontendBase . '/landing/services.php'],
    'booking' => ['label' => 'Book a Service', 'href' => $frontendBase . '/landing/booking.php'],
    'about' => ['label' => 'About Us', 'href' => $frontendBase . '/landing/about.php'],
    'contact' => ['label' => 'Contact Us', 'href' => $frontendBase . '/landing/contact.php'],
];
?>
<header class="af-public-header" data-navbar>
    <nav class="af-navbar" aria-label="Primary navigation">
        <a class="af-header-brand" href="<?php echo htmlspecialchars($frontendBase . '/landing/index.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="AfriSense home">
            <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
            <span>
                <strong><?php echo htmlspecialchars(str_replace(' Food Services', '', $siteName), ENT_QUOTES, 'UTF-8'); ?></strong>
                <small>Food Services</small>
            </span>
        </a>

        <button class="af-public-phone-mobile" type="button" aria-label="Call AfriSense">
            <i class="bi bi-telephone" aria-hidden="true"></i>
        </button>

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
                <a class="af-phone-link" href="<?php echo htmlspecialchars(afrisense_public_tel_href($primaryPhone), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-telephone" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($primaryPhone, ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
                <?php if ($publicHeaderMode === 'shop'): ?>
                    <a class="af-cart-link" href="<?php echo htmlspecialchars($frontendBase . '/landing/order.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="View cart">
                        <i class="bi bi-cart3" aria-hidden="true"></i>
                        <span>3</span>
                    </a>
                    <button class="af-public-profile" type="button" aria-label="Customer profile">
                        <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        <strong><?php echo htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                <?php else: ?>
                    <a class="af-order-btn" href="<?php echo htmlspecialchars($frontendBase . '/landing/order.php', ENT_QUOTES, 'UTF-8'); ?>">Order Now</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>
