<?php
require_once __DIR__ . '/../includes/public_settings.php';

$frontendBase = $frontendBase ?? '/Afrisense/frontend';
$publicSettings = afrisense_public_settings();
$websiteSettings = $publicSettings['website'];
$companySettings = $publicSettings['company'];
$siteName = (string) ($websiteSettings['site_name'] ?? 'AfriSense Food Services');
$siteTagline = (string) ($websiteSettings['site_tagline'] ?? 'Delicious meals, delivered with love.');
$footerText = (string) ($websiteSettings['footer_text'] ?? '(c) 2026 AfriSense Food Services. All rights reserved.');
$primaryPhone = (string) ($companySettings['phone_number_1'] ?? '+233 24 123 4567');
$secondaryPhone = (string) ($companySettings['phone_number_2'] ?? '');
$companyEmail = (string) ($companySettings['company_email'] ?? 'info@afrisense.com');
$companyAddress = (string) ($companySettings['address'] ?? 'Accra, Ghana');
$businessHours = (string) ($companySettings['business_hours'] ?? 'Mon - Sun: 8:00 AM - 10:00 PM');
$footerOrderHref = afrisense_public_order_url($frontendBase);
$footerBookingHref = afrisense_public_booking_url($frontendBase);
$footerSupportHref = afrisense_public_support_url($frontendBase);
?>
<!-- Footer block for this interface section. -->
<footer class="af-footer">
    <div class="af-footer-grid">
        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-footer-brand">
            <a class="af-brand" href="<?php echo htmlspecialchars($frontendBase . '/landing/index.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="AfriSense home">
                <span class="af-brand-icon" aria-hidden="true"><?php echo afrisense_public_brand_icon_html($frontendBase); ?></span>
                <span>
                    <strong><?php echo htmlspecialchars(str_replace(' Food Services', '', $siteName), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small>Food Services</small>
                </span>
            </a>
            <p><?php echo htmlspecialchars($siteTagline, ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="af-social-links" aria-label="Social links">
                <?php // Render this conditional/dynamic template block. ?>
                <?php foreach (afrisense_public_social_links() as $social): ?>
                    <a href="<?php echo htmlspecialchars($social['url'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($social['label'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi <?php echo htmlspecialchars($social['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></a>
                <?php endforeach; ?>
                <a href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/\D+/', '', $primaryPhone), ENT_QUOTES, 'UTF-8'); ?>" aria-label="WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
            </div>
        </section>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-footer-section" data-footer-section>
            <button type="button" data-footer-toggle>
                <span>Quick Links</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
            <ul>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/index.php', ENT_QUOTES, 'UTF-8'); ?>">Home</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/about.php', ENT_QUOTES, 'UTF-8'); ?>">About Us</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/menu.php', ENT_QUOTES, 'UTF-8'); ?>">Our Menu</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/gallery.php', ENT_QUOTES, 'UTF-8'); ?>">Gallery</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/services.php', ENT_QUOTES, 'UTF-8'); ?>">Catering Packages</a></li>
                <li><a href="<?php echo htmlspecialchars($footerBookingHref, ENT_QUOTES, 'UTF-8'); ?>">Book a Service</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/remarks.php', ENT_QUOTES, 'UTF-8'); ?>">Reviews &amp; Remarks</a></li>
                <li><a href="<?php echo htmlspecialchars($footerSupportHref, ENT_QUOTES, 'UTF-8'); ?>">Support</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/contact.php', ENT_QUOTES, 'UTF-8'); ?>">Contact Us</a></li>
            </ul>
        </section>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-footer-section" data-footer-section>
            <button type="button" data-footer-toggle>
                <span>Our Services</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
            <ul>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/menu.php', ENT_QUOTES, 'UTF-8'); ?>">Food Ordering</a></li>
                <li><a href="<?php echo htmlspecialchars($footerBookingHref, ENT_QUOTES, 'UTF-8'); ?>">Service Booking</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/services.php', ENT_QUOTES, 'UTF-8'); ?>">Event Catering</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/services.php', ENT_QUOTES, 'UTF-8'); ?>">Custom Menus</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/services.php', ENT_QUOTES, 'UTF-8'); ?>">Corporate Meals</a></li>
                <li><a href="<?php echo htmlspecialchars($footerOrderHref, ENT_QUOTES, 'UTF-8'); ?>">Fast Delivery</a></li>
            </ul>
        </section>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-footer-section" data-footer-section>
            <button type="button" data-footer-toggle>
                <span>Contact Us</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
            <ul class="af-contact-list">
                <li><i class="bi bi-telephone" aria-hidden="true"></i> <span><?php echo htmlspecialchars($primaryPhone, ENT_QUOTES, 'UTF-8'); ?><?php echo $secondaryPhone !== '' ? '<br>' . htmlspecialchars($secondaryPhone, ENT_QUOTES, 'UTF-8') : ''; ?></span></li>
                <li><i class="bi bi-envelope" aria-hidden="true"></i> <?php echo htmlspecialchars($companyEmail, ENT_QUOTES, 'UTF-8'); ?></li>
                <li><i class="bi bi-geo-alt" aria-hidden="true"></i> <span><?php echo nl2br(htmlspecialchars($companyAddress, ENT_QUOTES, 'UTF-8')); ?></span></li>
                <li><i class="bi bi-clock" aria-hidden="true"></i> <?php echo nl2br(htmlspecialchars($businessHours, ENT_QUOTES, 'UTF-8')); ?></li>
            </ul>
        </section>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-footer-section af-footer-newsletter is-open" data-footer-section>
            <button type="button" data-footer-toggle>
                <span>Newsletter</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
            <p>Subscribe to get the latest updates, offers and news.</p>
            <!-- Form block that submits this page workflow. -->
            <form class="af-newsletter" action="<?php echo htmlspecialchars($frontendBase . '/landing/contact.php', ENT_QUOTES, 'UTF-8'); ?>" method="post">
                <label class="sr-only" for="footer_newsletter_email">Email address</label>
                <input type="email" id="footer_newsletter_email" name="email" placeholder="Enter your email">
                <button type="submit" aria-label="Subscribe"><i class="bi bi-send-fill" aria-hidden="true"></i></button>
            </form>
            <small class="af-privacy-note"><i class="bi bi-lock-fill" aria-hidden="true"></i> We respect your privacy.</small>
        </section>
    </div>

    <div class="af-footer-bottom">
        <p><?php echo htmlspecialchars($footerText, ENT_QUOTES, 'UTF-8'); ?></p>
        <!-- Navigation links for this interface. -->
        <nav aria-label="Footer links">
            <a href="<?php echo htmlspecialchars($frontendBase . '/landing/privacy.php', ENT_QUOTES, 'UTF-8'); ?>">Privacy Policy</a>
            <a href="<?php echo htmlspecialchars($frontendBase . '/landing/terms.php', ENT_QUOTES, 'UTF-8'); ?>">Terms &amp; Conditions</a>
        </nav>
    </div>
</footer>
