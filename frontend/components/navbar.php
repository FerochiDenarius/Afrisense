<?php
require_once __DIR__ . '/../includes/public_settings.php';

$frontendBase = $frontendBase ?? afrisense_frontend_url();
$activePage = $activePage ?? '';
$publicHeaderMode = $publicHeaderMode ?? 'default';
$customerName = $customerName ?? 'Guest User';
$publicSettings = afrisense_public_settings();
$siteName = (string) ($publicSettings['website']['site_name'] ?? 'AfriSense Food Services');
$primaryPhone = (string) ($publicSettings['company']['phone_number_1'] ?? '+233 24 123 4567');
$publicUser = null;
// Guard this block so it only runs when the required condition is met.
if (function_exists('afrisense_current_user')) {
    // Run database/action work inside a guarded block so the page can fail gracefully.
    try {
        $publicUser = afrisense_current_user();
    } catch (Throwable) {
        $publicUser = null;
    }
}
$publicProfileName = $publicUser === null
    ? 'Guest User'
    : trim((string) ($publicUser['fullname'] ?? $publicUser['name'] ?? $customerName));
$publicProfileHref = $publicUser !== null && function_exists('afrisense_dashboard_url')
    ? afrisense_dashboard_url($publicUser)
    : afrisense_auth_url('login.php');
$publicCartCount = max(0, (int) ($cartCount ?? 0));
$publicOrderHref = afrisense_public_order_url($frontendBase);
$publicCartHref = afrisense_public_cart_url($frontendBase);
$publicBookingHref = afrisense_public_booking_url($frontendBase);
$publicSupportHref = afrisense_public_support_url($frontendBase);

$navItems = [
    'home' => ['label' => 'Home', 'href' => afrisense_landing_url('index.php')],
    'menu' => ['label' => 'Menu', 'href' => afrisense_landing_url('menu.php')],
    'gallery' => ['label' => 'Gallery', 'href' => afrisense_landing_url('gallery.php')],
    'catering' => ['label' => 'Catering Packages', 'href' => afrisense_landing_url('services.php')],
    'booking' => ['label' => 'Book a Service', 'href' => $publicBookingHref],
    'remarks' => ['label' => 'Reviews', 'href' => afrisense_landing_url('remarks.php')],
    'about' => ['label' => 'About Us', 'href' => afrisense_landing_url('about.php')],
    'support' => ['label' => 'Support', 'href' => $publicSupportHref],
    'contact' => ['label' => 'Contact Us', 'href' => afrisense_landing_url('contact.php')],
];
?>
<!-- Header block for this interface section. -->
<header class="af-public-header" data-navbar>
    <!-- Navigation links for this interface. -->
    <nav class="af-navbar" aria-label="Primary navigation">
        <a class="af-header-brand" href="<?php echo htmlspecialchars(afrisense_landing_url('index.php'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="AfriSense home">
            <span class="af-brand-icon" aria-hidden="true"><?php echo afrisense_public_brand_icon_html($frontendBase); ?></span>
            <span>
                <strong><?php echo htmlspecialchars(str_replace(' Food Services', '', $siteName), ENT_QUOTES, 'UTF-8'); ?></strong>
                <small>Food Services</small>
            </span>
        </a>

        <a class="af-public-phone-mobile" href="<?php echo htmlspecialchars(afrisense_public_tel_href($primaryPhone), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Call AfriSense">
            <i class="bi bi-telephone" aria-hidden="true"></i>
        </a>

        <button class="af-nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false" data-navbar-toggle>
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <div class="af-nav-menu" data-navbar-menu>
            <ul class="af-nav-links">
                <?php // Render this conditional/dynamic template block. ?>
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
                <?php // Render this conditional/dynamic template block. ?>
                <?php if ($publicHeaderMode === 'shop'): ?>
                    <a class="af-cart-link" href="<?php echo htmlspecialchars($publicCartHref, ENT_QUOTES, 'UTF-8'); ?>" aria-label="View cart">
                        <i class="bi bi-cart3" aria-hidden="true"></i>
                        <span><?php echo htmlspecialchars((string) $publicCartCount, ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                    <a class="af-public-profile" href="<?php echo htmlspecialchars($publicProfileHref, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Customer profile">
                        <img src="<?php echo htmlspecialchars(afrisense_asset_url('images/foodimage.jpeg'), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        <strong><?php echo htmlspecialchars($publicProfileName !== '' ? $publicProfileName : 'My Account', ENT_QUOTES, 'UTF-8'); ?></strong>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </a>
                <?php else: ?>
                    <a class="af-order-btn" href="<?php echo htmlspecialchars($publicOrderHref, ENT_QUOTES, 'UTF-8'); ?>">Order Now</a>
                    <?php // Render this conditional/dynamic template block. ?>
                    <?php if ($publicUser === null): ?>
                        <a class="af-auth-btn" href="<?php echo htmlspecialchars(afrisense_auth_url('login.php'), ENT_QUOTES, 'UTF-8'); ?>">Login</a>
                        <a class="af-auth-btn is-register" href="<?php echo htmlspecialchars(afrisense_auth_url('register.php'), ENT_QUOTES, 'UTF-8'); ?>">Register</a>
                    <?php else: ?>
                        <a class="af-auth-btn is-register" href="<?php echo htmlspecialchars($publicProfileHref, ENT_QUOTES, 'UTF-8'); ?>">Account</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>
