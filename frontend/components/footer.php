<?php
$frontendBase = $frontendBase ?? '/Afrisense/frontend';
?>
<footer class="af-footer">
    <div class="af-footer-grid">
        <section class="af-footer-brand">
            <a class="af-brand" href="<?php echo htmlspecialchars($frontendBase . '/landing/index.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="AfriSense home">
                <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
                <span>
                    <strong>AfriSense</strong>
                    <small>Food Services</small>
                </span>
            </a>
            <p>Providing delicious meals and exceptional catering services for all occasions. Taste, quality and excellence you can trust.</p>
            <div class="af-social-links" aria-label="Social links">
                <a href="#" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                <a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x" aria-hidden="true"></i></a>
                <a href="#" aria-label="WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
            </div>
        </section>

        <section class="af-footer-section" data-footer-section>
            <button type="button" data-footer-toggle>
                <span>Quick Links</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
            <ul>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/index.php', ENT_QUOTES, 'UTF-8'); ?>">Home</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/about.php', ENT_QUOTES, 'UTF-8'); ?>">About Us</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/menu.php', ENT_QUOTES, 'UTF-8'); ?>">Our Menu</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/services.php', ENT_QUOTES, 'UTF-8'); ?>">Catering Packages</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/booking.php', ENT_QUOTES, 'UTF-8'); ?>">Book a Service</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/contact.php', ENT_QUOTES, 'UTF-8'); ?>">Contact Us</a></li>
            </ul>
        </section>

        <section class="af-footer-section" data-footer-section>
            <button type="button" data-footer-toggle>
                <span>Our Services</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
            <ul>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/menu.php', ENT_QUOTES, 'UTF-8'); ?>">Food Ordering</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/booking.php', ENT_QUOTES, 'UTF-8'); ?>">Service Booking</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/services.php', ENT_QUOTES, 'UTF-8'); ?>">Event Catering</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/services.php', ENT_QUOTES, 'UTF-8'); ?>">Custom Menus</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/services.php', ENT_QUOTES, 'UTF-8'); ?>">Corporate Meals</a></li>
                <li><a href="<?php echo htmlspecialchars($frontendBase . '/landing/order.php', ENT_QUOTES, 'UTF-8'); ?>">Fast Delivery</a></li>
            </ul>
        </section>

        <section class="af-footer-section" data-footer-section>
            <button type="button" data-footer-toggle>
                <span>Contact Us</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
            <ul class="af-contact-list">
                <li><i class="bi bi-telephone" aria-hidden="true"></i> <span>+233 24 123 4567<br>+233 20 987 6543</span></li>
                <li><i class="bi bi-envelope" aria-hidden="true"></i> info@afrisense.com</li>
                <li><i class="bi bi-geo-alt" aria-hidden="true"></i> <span>15 Senchi Street,<br>Airport Residential Area<br>Accra, Ghana</span></li>
                <li><i class="bi bi-clock" aria-hidden="true"></i> Mon - Sun: 8:00 AM - 10:00 PM</li>
            </ul>
        </section>

        <section class="af-footer-section af-footer-newsletter is-open" data-footer-section>
            <button type="button" data-footer-toggle>
                <span>Newsletter</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
            <p>Subscribe to get the latest updates, offers and news.</p>
            <form class="af-newsletter" action="#" method="post">
                <label class="sr-only" for="footer_newsletter_email">Email address</label>
                <input type="email" id="footer_newsletter_email" name="email" placeholder="Enter your email">
                <button type="submit" aria-label="Subscribe"><i class="bi bi-send-fill" aria-hidden="true"></i></button>
            </form>
            <small class="af-privacy-note"><i class="bi bi-lock-fill" aria-hidden="true"></i> We respect your privacy.</small>
        </section>
    </div>

    <div class="af-footer-bottom">
        <p>&copy; 2024 AfriSense Food Services. All Rights Reserved.</p>
        <nav aria-label="Footer links">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms &amp; Conditions</a>
        </nav>
    </div>
</footer>
