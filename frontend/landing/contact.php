<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Contact Us | AfriSense';
$activePage = 'contact';
$extraStyles = [$frontendBase . '/assets/css/booking-contact.css'];
$extraScripts = [$frontendBase . '/assets/js/booking-contact.js'];

require_once __DIR__ . '/enquiry_helpers.php';
require_once __DIR__ . '/../includes/public_settings.php';

afrisense_enforce_public_site_status($frontendBase);

$publicSettings = afrisense_public_settings();
$companySettings = $publicSettings['company'];
$primaryPhone = (string) ($companySettings['phone_number_1'] ?? '+233 24 123 4567');
$secondaryPhone = (string) ($companySettings['phone_number_2'] ?? '');
$companyEmail = (string) ($companySettings['company_email'] ?? 'info@afrisense.com');
$supportEmail = (string) ($companySettings['support_email'] ?? 'support@afrisense.com');
$companyAddress = (string) ($companySettings['address'] ?? '15 Senchi Street, Airport Residential Area, Accra, Ghana');
$businessHours = (string) ($companySettings['business_hours'] ?? 'Mon - Sun: 8:00 AM - 10:00 PM');
$mapEmbed = afrisense_public_safe_map_embed((string) ($companySettings['google_map_iframe'] ?? ''));
$contactSupportHref = afrisense_public_support_url($frontendBase);
$phoneLines = array_values(array_filter([$primaryPhone, $secondaryPhone], static fn (string $value): bool => trim($value) !== ''));
$emailLines = array_values(array_filter([$companyEmail, $supportEmail], static fn (string $value): bool => trim($value) !== ''));
$hoursLines = array_values(array_filter(array_map('trim', preg_split('/\R+/', $businessHours) ?: [])));
$contactItems = [
    ['title' => 'Phone', 'icon' => 'bi-telephone', 'lines' => $phoneLines !== [] ? $phoneLines : ['+233 24 123 4567']],
    ['title' => 'Email', 'icon' => 'bi-envelope', 'lines' => $emailLines !== [] ? $emailLines : ['info@afrisense.com']],
    ['title' => 'Address', 'icon' => 'bi-geo-alt', 'lines' => [$companyAddress]],
    ['title' => 'Opening Hours', 'icon' => 'bi-clock', 'lines' => $hoursLines !== [] ? $hoursLines : ['Mon - Sun: 8:00 AM - 10:00 PM']],
];

$faqs = [
    ['question' => 'How can I place an order?', 'answer' => 'You can order directly from our order page or call our team for assistance.'],
    ['question' => 'Do you offer home delivery?', 'answer' => 'Yes, we deliver meals across selected areas in Accra.'],
    ['question' => 'Can I book a table for a large group?', 'answer' => 'Yes. Use the booking page and include your guest count and any special request.'],
    ['question' => 'What payment methods do you accept?', 'answer' => 'We accept cash, card payments, and mobile money.'],
    ['question' => 'Do you cater for special events?', 'answer' => 'Yes, our catering team handles private, corporate, wedding, and custom events.'],
];

$contactMessage = null;

// Handle submitted form actions before rendering the page.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $contactMessage = afrisense_submit_public_enquiry($_POST, 'Contact');
}

ob_start();
?>
<!-- Page section for this part of the AfriSense interface. -->
<section class="af-service-hero af-contact-hero">
    <div class="af-service-hero-inner">
        <!-- Navigation links for this interface. -->
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

<!-- Page section for this part of the AfriSense interface. -->
<section class="af-contact-page" id="contact_form">
    <div class="af-contact-grid">
        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-service-card af-contact-form-card" aria-labelledby="contact_title">
            <!-- Header block for this interface section. -->
            <header class="af-section-heading">
                <span><i class="bi bi-envelope-paper" aria-hidden="true"></i></span>
                <div>
                    <h2 id="contact_title">Send Us a Message</h2>
                    <p>Fill out the form below and we will get back to you as soon as possible.</p>
                </div>
            </header>

            <!-- Form block that submits this page workflow. -->
            <form class="af-service-form" action="contact.php#contact_form" method="post" data-enhanced-form>
                <div class="af-field-grid">
                    <div class="af-form-group">
                        <label for="contact_full_name">Full Name <strong>*</strong></label>
                        <div class="af-input-icon">
                            <i class="bi bi-person" aria-hidden="true"></i>
                            <input type="text" id="contact_full_name" name="full_name" value="<?php echo htmlspecialchars((string) ($_POST['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your full name" autocomplete="name" minlength="2" required>
                        </div>
                        <small class="af-field-error">Please enter your full name.</small>
                    </div>

                    <div class="af-form-group">
                        <label for="contact_email">Email Address <strong>*</strong></label>
                        <div class="af-input-icon">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <input type="email" id="contact_email" name="email" value="<?php echo htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your email address" autocomplete="email" required>
                        </div>
                        <small class="af-field-error">Please enter a valid email address.</small>
                    </div>

                    <div class="af-form-group">
                        <label for="contact_phone">Phone Number</label>
                        <div class="af-phone-field">
                            <span class="af-country-code"><span class="af-gh-flag" aria-hidden="true"></span> +233</span>
                            <input type="tel" id="contact_phone" name="phone" value="<?php echo htmlspecialchars((string) ($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="24 123 4567" autocomplete="tel-national" inputmode="tel" pattern="[0-9\s]{9,12}">
                        </div>
                        <small class="af-field-error">Please enter a valid phone number.</small>
                    </div>

                    <div class="af-form-group">
                        <label for="contact_subject">Subject <strong>*</strong></label>
                        <div class="af-select-wrap">
                            <select id="contact_subject" name="subject" required>
                                <option value="" <?php echo empty($_POST['subject']) ? 'selected' : ''; ?> disabled>Select a subject</option>
                                <?php // Render this conditional/dynamic template block. ?>
                                <?php foreach (['Booking Support', 'Order Enquiry', 'Catering Request', 'General Feedback'] as $subjectOption): ?>
                                    <option value="<?php echo htmlspecialchars($subjectOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) ($_POST['subject'] ?? '') === $subjectOption ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subjectOption, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </div>
                        <small class="af-field-error">Please choose a subject.</small>
                    </div>

                    <div class="af-form-group af-full-field">
                        <label for="contact_message">Message <strong>*</strong></label>
                        <div class="af-textarea-wrap">
                            <textarea id="contact_message" name="message" minlength="10" maxlength="500" placeholder="Type your message here..." data-character-source required><?php echo htmlspecialchars((string) ($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <span class="af-character-count" data-character-count>0/500</span>
                        </div>
                        <small class="af-field-error">Please enter a message with at least 10 characters.</small>
                    </div>
                </div>

                <button class="af-send-btn" type="submit">
                    <span>Send Message</span>
                    <i class="bi bi-send" aria-hidden="true"></i>
                </button>
                <p class="af-form-status <?php echo $contactMessage !== null ? ($contactMessage['success'] ? 'is-success' : 'is-error') : ''; ?>" data-form-status aria-live="polite">
                    <?php echo $contactMessage !== null ? htmlspecialchars((string) $contactMessage['message'], ENT_QUOTES, 'UTF-8') : ''; ?>
                </p>
                <p class="af-privacy-line"><i class="bi bi-lock-fill" aria-hidden="true"></i> We respect your privacy. Your information is safe with us.</p>
            </form>
        </section>

        <!-- Side panel with supporting information and actions. -->
        <aside class="af-service-card af-contact-info-card" aria-labelledby="contact_info_title">
            <h2 id="contact_info_title">Contact Information</h2>
            <div class="af-side-list">
                <?php // Render this conditional/dynamic template block. ?>
                <?php foreach ($contactItems as $item): ?>
                    <article>
                        <span><i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                        <div>
                            <h3><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <?php // Render this conditional/dynamic template block. ?>
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
                        <!-- Navigation links for this interface. -->
                        <nav class="af-help-socials" aria-label="Social links">
                            <?php // Render this conditional/dynamic template block. ?>
                            <?php foreach (afrisense_public_social_links() as $social): ?>
                                <a href="<?php echo htmlspecialchars($social['url'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($social['label'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi <?php echo htmlspecialchars($social['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></a>
                            <?php endforeach; ?>
                            <a href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/\D+/', '', $primaryPhone), ENT_QUOTES, 'UTF-8'); ?>" aria-label="WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
                        </nav>
                    </div>
                </article>
            </div>
        </aside>
    </div>

    <!-- Page section for this part of the AfriSense interface. -->
    <section class="af-service-card af-location-card">
        <div>
            <!-- Header block for this interface section. -->
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
            <?php // Render this conditional/dynamic template block. ?>
            <?php if ($mapEmbed !== ''): ?>
                <?php echo $mapEmbed; ?>
            <?php else: ?>
                <div class="af-map-pin">
                    <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                    <strong>AfriSense Food Services</strong>
                    <span><?php echo htmlspecialchars($companyAddress, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="af-contact-bottom-grid">
        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-service-card af-faq-card" aria-labelledby="faq_title">
            <!-- Header block for this interface section. -->
            <header class="af-section-heading">
                <span><i class="bi bi-question-circle" aria-hidden="true"></i></span>
                <div>
                    <h2 id="faq_title">Frequently Asked Questions</h2>
                    <p>Quick answers to common questions about AfriSense services.</p>
                </div>
            </header>
            <div class="af-faq-list">
                <?php // Render this conditional/dynamic template block. ?>
                <?php foreach ($faqs as $index => $faq): ?>
                    <details <?php echo $index === 0 ? 'open' : ''; ?>>
                        <summary><?php echo htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8'); ?></summary>
                        <p><?php echo htmlspecialchars($faq['answer'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
            <a class="af-outline-link" href="<?php echo htmlspecialchars($contactSupportHref, ENT_QUOTES, 'UTF-8'); ?>">Chat with Support <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </section>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-help-cta">
            <h2>We're Here to Help!</h2>
            <p>Whether you have a question about our menu, need help with a booking, or just want to say hello, do not hesitate to reach out to us.</p>
            <div>
                <a href="<?php echo htmlspecialchars(afrisense_public_tel_href($primaryPhone), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-telephone" aria-hidden="true"></i> Call Us Now</a>
                <a href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/\D+/', '', $primaryPhone), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp Us</a>
            </div>
        </section>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
