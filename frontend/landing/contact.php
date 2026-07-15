<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Contact Us | AfriSense';
$activePage = 'contact';
$extraStyles = [$frontendBase . '/assets/css/booking-contact.css'];
$extraScripts = [$frontendBase . '/assets/js/booking-contact.js'];

$contactItems = [
    ['title' => 'Phone', 'icon' => 'bi-telephone', 'lines' => ['+233 24 123 4567', '+233 20 987 6543']],
    ['title' => 'Email', 'icon' => 'bi-envelope', 'lines' => ['info@afrisense.com', 'support@afrisense.com']],
    ['title' => 'Address', 'icon' => 'bi-geo-alt', 'lines' => ['15 Senchi Street, Airport Residential Area', 'Accra, Ghana']],
    ['title' => 'Opening Hours', 'icon' => 'bi-clock', 'lines' => ['Mon - Sun: 8:00 AM - 10:00 PM', 'We are open every day!']],
];

$faqs = [
    ['question' => 'How can I place an order?', 'answer' => 'You can order directly from our order page or call our team for assistance.'],
    ['question' => 'Do you offer home delivery?', 'answer' => 'Yes, we deliver meals across selected areas in Accra.'],
    ['question' => 'Can I book a table for a large group?', 'answer' => 'Yes. Use the booking page and include your guest count and any special request.'],
    ['question' => 'What payment methods do you accept?', 'answer' => 'We accept cash, card payments, and mobile money.'],
    ['question' => 'Do you cater for special events?', 'answer' => 'Yes, our catering team handles private, corporate, wedding, and custom events.'],
];

ob_start();
?>
<section class="af-service-hero af-contact-hero">
    <div class="af-service-hero-inner">
        <nav class="af-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="bi bi-house-door" aria-hidden="true"></i> Home</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Contact Us</span>
        </nav>
        <p class="af-hero-kicker">Get In Touch</p>
        <h1>We'd Love to<br>Hear From <span>You</span></h1>
        <p>Have a question, feedback, or need assistance? Our team is always ready to help.</p>
        <div class="af-hero-actions">
            <a class="af-hero-primary" href="#contact_form"><i class="bi bi-send" aria-hidden="true"></i> Send Message</a>
            <a class="af-hero-secondary" href="tel:+233241234567"><i class="bi bi-telephone" aria-hidden="true"></i> Call Us Now</a>
        </div>
    </div>
</section>

<section class="af-contact-page" id="contact_form">
    <div class="af-contact-grid">
        <section class="af-service-card af-contact-form-card" aria-labelledby="contact_title">
            <header class="af-section-heading">
                <span><i class="bi bi-envelope-paper" aria-hidden="true"></i></span>
                <div>
                    <h2 id="contact_title">Send Us a Message</h2>
                    <p>Fill out the form below and we will get back to you as soon as possible.</p>
                </div>
            </header>

            <form class="af-service-form" action="#" method="post" data-enhanced-form>
                <div class="af-field-grid">
                    <div class="af-form-group">
                        <label for="contact_full_name">Full Name <strong>*</strong></label>
                        <div class="af-input-icon">
                            <i class="bi bi-person" aria-hidden="true"></i>
                            <input type="text" id="contact_full_name" name="full_name" placeholder="Enter your full name" autocomplete="name" minlength="2" required>
                        </div>
                        <small class="af-field-error">Please enter your full name.</small>
                    </div>

                    <div class="af-form-group">
                        <label for="contact_email">Email Address <strong>*</strong></label>
                        <div class="af-input-icon">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <input type="email" id="contact_email" name="email" placeholder="Enter your email address" autocomplete="email" required>
                        </div>
                        <small class="af-field-error">Please enter a valid email address.</small>
                    </div>

                    <div class="af-form-group">
                        <label for="contact_phone">Phone Number</label>
                        <div class="af-phone-field">
                            <span class="af-country-code"><span class="af-gh-flag" aria-hidden="true"></span> +233</span>
                            <input type="tel" id="contact_phone" name="phone" placeholder="24 123 4567" autocomplete="tel-national" inputmode="tel" pattern="[0-9\s]{9,12}">
                        </div>
                        <small class="af-field-error">Please enter a valid phone number.</small>
                    </div>

                    <div class="af-form-group">
                        <label for="contact_subject">Subject <strong>*</strong></label>
                        <div class="af-select-wrap">
                            <select id="contact_subject" name="subject" required>
                                <option value="" selected disabled>Select a subject</option>
                                <option>Booking Support</option>
                                <option>Order Enquiry</option>
                                <option>Catering Request</option>
                                <option>General Feedback</option>
                            </select>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </div>
                        <small class="af-field-error">Please choose a subject.</small>
                    </div>

                    <div class="af-form-group af-full-field">
                        <label for="contact_message">Message <strong>*</strong></label>
                        <div class="af-textarea-wrap">
                            <textarea id="contact_message" name="message" minlength="10" maxlength="500" placeholder="Type your message here..." data-character-source required></textarea>
                            <span class="af-character-count" data-character-count>0/500</span>
                        </div>
                        <small class="af-field-error">Please enter a message with at least 10 characters.</small>
                    </div>
                </div>

                <button class="af-send-btn" type="submit">
                    <span>Send Message</span>
                    <i class="bi bi-send" aria-hidden="true"></i>
                </button>
                <p class="af-form-status" data-form-status aria-live="polite"></p>
                <p class="af-privacy-line"><i class="bi bi-lock-fill" aria-hidden="true"></i> We respect your privacy. Your information is safe with us.</p>
            </form>
        </section>

        <aside class="af-service-card af-contact-info-card" aria-labelledby="contact_info_title">
            <h2 id="contact_info_title">Contact Information</h2>
            <div class="af-side-list">
                <?php foreach ($contactItems as $item): ?>
                    <article>
                        <span><i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                        <div>
                            <h3><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <?php foreach ($item['lines'] as $line): ?>
                                <p><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
                <article>
                    <span><i class="bi bi-share" aria-hidden="true"></i></span>
                    <div>
                        <h3>Social Media</h3>
                        <nav class="af-help-socials" aria-label="Social links">
                            <a href="#" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                            <a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                            <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x" aria-hidden="true"></i></a>
                            <a href="#" aria-label="WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
                        </nav>
                    </div>
                </article>
            </div>
        </aside>
    </div>

    <section class="af-service-card af-location-card">
        <div>
            <header class="af-section-heading">
                <span><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
                <div>
                    <h2>Find Us</h2>
                    <p>Visit our restaurant or reach out to us directly. We are conveniently located in the heart of Accra.</p>
                </div>
            </header>
            <ul class="af-check-list">
                <li><i class="bi bi-check-circle" aria-hidden="true"></i> 5 minutes from Kotoka International Airport</li>
                <li><i class="bi bi-check-circle" aria-hidden="true"></i> Ample parking space available</li>
                <li><i class="bi bi-check-circle" aria-hidden="true"></i> Wheelchair accessible</li>
                <li><i class="bi bi-check-circle" aria-hidden="true"></i> Free Wi-Fi for all guests</li>
            </ul>
        </div>
        <div class="af-map-preview" aria-label="AfriSense location map preview">
            <div class="af-map-pin">
                <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                <strong>AfriSense Food Services</strong>
                <span>15 Senchi Street, Airport Residential Area, Accra</span>
            </div>
        </div>
    </section>

    <div class="af-contact-bottom-grid">
        <section class="af-service-card af-faq-card" aria-labelledby="faq_title">
            <header class="af-section-heading">
                <span><i class="bi bi-question-circle" aria-hidden="true"></i></span>
                <div>
                    <h2 id="faq_title">Frequently Asked Questions</h2>
                    <p>Quick answers to common questions about AfriSense services.</p>
                </div>
            </header>
            <div class="af-faq-list">
                <?php foreach ($faqs as $index => $faq): ?>
                    <details <?php echo $index === 0 ? 'open' : ''; ?>>
                        <summary><?php echo htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8'); ?></summary>
                        <p><?php echo htmlspecialchars($faq['answer'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
            <a class="af-outline-link" href="enquiries.php">Send an Enquiry <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </section>

        <section class="af-help-cta">
            <h2>We're Here to Help!</h2>
            <p>Whether you have a question about our menu, need help with a booking, or just want to say hello, do not hesitate to reach out to us.</p>
            <div>
                <a href="tel:+233241234567"><i class="bi bi-telephone" aria-hidden="true"></i> Call Us Now</a>
                <a href="#"><i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp Us</a>
            </div>
        </section>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
