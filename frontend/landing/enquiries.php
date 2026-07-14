<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Enquiries | AfriSense';
$activePage = 'contact';
$extraStyles = [$frontendBase . '/assets/css/enquiries.css'];
$extraScripts = [$frontendBase . '/assets/js/enquiries.js'];

ob_start();
?>
<section class="af-enquiry-hero">
    <div class="af-enquiry-hero-inner">
        <nav class="af-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="bi bi-house-door" aria-hidden="true"></i> Home</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <a href="contact.php">Contact Us</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Enquiries</span>
        </nav>

        <h1>Send Us an <span>Enquiry</span></h1>
        <div class="af-title-divider" aria-hidden="true">
            <span></span>
            <i class="bi bi-cup-hot"></i>
            <span></span>
        </div>
        <p>Have a question, suggestion, or special request? We'd love to hear from you. Fill out the form below and our team will get back to you as soon as possible.</p>
    </div>
</section>

<section class="af-enquiry-section">
    <div class="af-enquiry-grid">
        <section class="af-enquiry-card af-form-card" aria-labelledby="enquiry_form_title">
            <header class="af-section-heading">
                <span><i class="bi bi-envelope-paper" aria-hidden="true"></i></span>
                <div>
                    <h2 id="enquiry_form_title">Enquiry Form</h2>
                    <p>Please fill in the details below and we will respond to you shortly.</p>
                </div>
            </header>

            <form class="af-enquiry-form" action="#" method="post">
                <div class="af-field-grid">
                    <div class="af-form-group">
                        <label for="full_name">Full Name <strong>*</strong></label>
                        <div class="af-input-icon">
                            <i class="bi bi-person" aria-hidden="true"></i>
                            <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
                        </div>
                    </div>

                    <div class="af-form-group">
                        <label for="email">Email Address <strong>*</strong></label>
                        <div class="af-input-icon">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                        </div>
                    </div>

                    <div class="af-form-group">
                        <label for="phone">Phone Number <strong>*</strong></label>
                        <div class="af-input-icon">
                            <i class="bi bi-telephone" aria-hidden="true"></i>
                            <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required>
                        </div>
                    </div>

                    <div class="af-form-group">
                        <label for="subject">Subject <strong>*</strong></label>
                        <div class="af-select-wrap">
                            <select id="subject" name="subject" required>
                                <option value="">Select enquiry subject</option>
                                <option value="general">General Enquiry</option>
                                <option value="catering">Catering &amp; Events</option>
                                <option value="orders">Orders &amp; Delivery</option>
                                <option value="support">Customer Support</option>
                            </select>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <fieldset class="af-enquiry-types">
                    <legend>Enquiry Type</legend>

                    <label class="is-active">
                        <input type="radio" name="enquiry_type" value="general" checked>
                        <span class="af-type-check"><i class="bi bi-check" aria-hidden="true"></i></span>
                        <i class="bi bi-question-circle" aria-hidden="true"></i>
                        <strong>General Enquiry</strong>
                    </label>

                    <label>
                        <input type="radio" name="enquiry_type" value="catering">
                        <span class="af-type-check"><i class="bi bi-check" aria-hidden="true"></i></span>
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        <strong>Catering &amp; Events</strong>
                    </label>

                    <label>
                        <input type="radio" name="enquiry_type" value="orders">
                        <span class="af-type-check"><i class="bi bi-truck" aria-hidden="true"></i></span>
                        <i class="bi bi-scooter" aria-hidden="true"></i>
                        <strong>Orders &amp; Delivery</strong>
                    </label>

                    <label>
                        <input type="radio" name="enquiry_type" value="other">
                        <span class="af-type-check"><i class="bi bi-check" aria-hidden="true"></i></span>
                        <i class="bi bi-three-dots" aria-hidden="true"></i>
                        <strong>Other</strong>
                    </label>
                </fieldset>

                <div class="af-form-group af-message-group">
                    <label for="message">Message <strong>*</strong></label>
                    <div class="af-textarea-wrap">
                        <textarea id="message" name="message" placeholder="Type your message here..." required></textarea>
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                    </div>
                </div>

                <button class="af-send-btn" type="submit">
                    <i class="bi bi-send" aria-hidden="true"></i>
                    <span>Send Enquiry</span>
                </button>

                <p class="af-privacy-line"><i class="bi bi-lock-fill" aria-hidden="true"></i> We respect your privacy. Your information will not be shared with third parties.</p>
            </form>
        </section>

        <aside class="af-enquiry-card af-help-card" aria-labelledby="help_title">
            <h2 id="help_title">We're Here to Help</h2>
            <span class="af-help-line" aria-hidden="true"></span>
            <p class="af-help-intro">Our friendly team is ready to assist you with any questions you may have.</p>

            <div class="af-help-list">
                <article>
                    <span><i class="bi bi-telephone" aria-hidden="true"></i></span>
                    <div>
                        <h3>Call Us</h3>
                        <p><strong>+233 24 123 4567</strong><br>Mon - Sun: 8:00 AM - 10:00 PM</p>
                    </div>
                </article>

                <article>
                    <span><i class="bi bi-envelope" aria-hidden="true"></i></span>
                    <div>
                        <h3>Email Us</h3>
                        <p><strong>info@afrisense.com</strong><br>We reply within 24 hours</p>
                    </div>
                </article>

                <article>
                    <span><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
                    <div>
                        <h3>Visit Us</h3>
                        <p>15 Senchi Street,<br>Airport Residential Area<br>Accra, Ghana</p>
                    </div>
                </article>

                <article>
                    <span><i class="bi bi-people" aria-hidden="true"></i></span>
                    <div>
                        <h3>Follow Us</h3>
                        <nav class="af-help-socials" aria-label="Social links">
                            <a href="#" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                            <a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                            <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x" aria-hidden="true"></i></a>
                            <a href="#" aria-label="WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
                        </nav>
                    </div>
                </article>
            </div>

            <section class="af-response-card">
                <span><i class="bi bi-cup-hot" aria-hidden="true"></i></span>
                <div>
                    <h3>Quick Response</h3>
                    <p>We aim to respond to all enquiries within 24 hours.</p>
                </div>
            </section>
        </aside>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
