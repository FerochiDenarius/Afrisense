<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Book a Service | AfriSense';
$activePage = 'booking';
$extraStyles = [$frontendBase . '/assets/css/booking-contact.css'];
$extraScripts = [$frontendBase . '/assets/js/booking-contact.js'];

$serviceTypes = [
    ['name' => 'Table Booking', 'desc' => 'Reserve a table at our restaurant', 'icon' => 'bi-calendar3', 'price' => 120],
    ['name' => 'Event Catering', 'desc' => 'Catering for events and parties', 'icon' => 'bi-gift', 'price' => 450],
    ['name' => 'Private Dining', 'desc' => 'Private room reservations', 'icon' => 'bi-people', 'price' => 300],
    ['name' => 'Custom Request', 'desc' => 'Special requests and arrangements', 'icon' => 'bi-heart', 'price' => 200],
];

$benefits = [
    ['title' => 'Easy Booking', 'desc' => 'Quick and simple booking process', 'icon' => 'bi-calendar-check'],
    ['title' => 'Best Experience', 'desc' => 'Exceptional service and memorable moments', 'icon' => 'bi-patch-check'],
    ['title' => 'Instant Confirmation', 'desc' => 'Get confirmation for your booking instantly', 'icon' => 'bi-clipboard-check'],
    ['title' => '24/7 Support', 'desc' => "We're here to help you anytime, anywhere", 'icon' => 'bi-headset'],
];

$features = [
    ['title' => 'Premium Ambience', 'desc' => 'Enjoy our cozy and elegant environment', 'icon' => 'bi-shop'],
    ['title' => 'Delicious Cuisine', 'desc' => 'Savor our expertly crafted meals', 'icon' => 'bi-cup-hot'],
    ['title' => 'Excellent Service', 'desc' => 'Experience top-notch hospitality', 'icon' => 'bi-person-hearts'],
    ['title' => 'Memorable Moments', 'desc' => 'Create memories that last a lifetime', 'icon' => 'bi-heart'],
];

ob_start();
?>
<section class="af-service-hero af-booking-hero">
    <div class="af-service-hero-inner">
        <nav class="af-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="bi bi-house-door" aria-hidden="true"></i> Home</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Book a Service</span>
        </nav>
        <p class="af-hero-kicker">Book a Service</p>
        <h1>Reserve Your Table<br>Create <span>Memorable Moments</span></h1>
        <p>Book a table or one of our premium services for any occasion.</p>
        <div class="af-hero-actions">
            <a class="af-hero-primary" href="#booking_form"><i class="bi bi-calendar-check" aria-hidden="true"></i> Book Now</a>
            <a class="af-hero-secondary" href="services.php"><i class="bi bi-gift" aria-hidden="true"></i> View Packages</a>
        </div>
    </div>
</section>

<section class="af-booking-page" id="booking_form">
    <div class="af-booking-grid">
        <section class="af-service-card af-booking-form-card" aria-labelledby="booking_title">
            <header class="af-section-heading">
                <span><i class="bi bi-calendar3" aria-hidden="true"></i></span>
                <div>
                    <h2 id="booking_title">Booking Information</h2>
                    <p>Select a service, share your details, and our team will confirm your reservation.</p>
                </div>
            </header>

            <form class="af-service-form" action="#" method="post" data-booking-form data-enhanced-form>
                <fieldset class="af-service-types">
                    <legend>Select Service Type</legend>
                    <?php foreach ($serviceTypes as $index => $service): ?>
                        <label class="<?php echo $index === 0 ? 'is-active' : ''; ?>" data-service-option data-price="<?php echo (int) $service['price']; ?>">
                            <input type="radio" name="service_type" value="<?php echo htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                            <span class="af-type-check"><i class="bi bi-check" aria-hidden="true"></i></span>
                            <i class="bi <?php echo htmlspecialchars($service['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                            <strong><?php echo htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <small><?php echo htmlspecialchars($service['desc'], ENT_QUOTES, 'UTF-8'); ?></small>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <div class="af-field-grid">
                    <div class="af-form-group">
                        <label for="booking_full_name">Full Name <strong>*</strong></label>
                        <div class="af-input-icon">
                            <i class="bi bi-person" aria-hidden="true"></i>
                            <input type="text" id="booking_full_name" name="full_name" placeholder="Enter your full name" autocomplete="name" minlength="2" required>
                        </div>
                        <small class="af-field-error">Please enter your full name.</small>
                    </div>

                    <div class="af-form-group">
                        <label for="booking_phone">Phone Number <strong>*</strong></label>
                        <div class="af-phone-field">
                            <span class="af-country-code"><span class="af-gh-flag" aria-hidden="true"></span> +233</span>
                            <input type="tel" id="booking_phone" name="phone" placeholder="24 123 4567" autocomplete="tel-national" inputmode="tel" pattern="[0-9\s]{9,12}" required>
                        </div>
                        <small class="af-field-error">Please enter a valid phone number.</small>
                    </div>

                    <div class="af-form-group">
                        <label for="booking_email">Email Address <strong>*</strong></label>
                        <div class="af-input-icon">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <input type="email" id="booking_email" name="email" placeholder="Enter your email address" autocomplete="email" required>
                        </div>
                        <small class="af-field-error">Please enter a valid email address.</small>
                    </div>

                    <div class="af-form-group">
                        <label for="booking_date">Date <strong>*</strong></label>
                        <div class="af-input-icon">
                            <i class="bi bi-calendar-event" aria-hidden="true"></i>
                            <input type="date" id="booking_date" name="booking_date" data-booking-date required>
                        </div>
                        <small class="af-field-error">Please select a booking date.</small>
                    </div>

                    <div class="af-form-group">
                        <label for="booking_time">Time <strong>*</strong></label>
                        <div class="af-input-icon">
                            <i class="bi bi-clock" aria-hidden="true"></i>
                            <input type="time" id="booking_time" name="booking_time" data-booking-time required>
                        </div>
                        <small class="af-field-error">Please select a booking time.</small>
                    </div>

                    <div class="af-form-group">
                        <label for="booking_guests">Number of Guests <strong>*</strong></label>
                        <div class="af-select-wrap">
                            <select id="booking_guests" name="guests" data-booking-guests required>
                                <option value="">Select number of guests</option>
                                <option value="2 Guests">2 Guests</option>
                                <option value="4 Guests" selected>4 Guests</option>
                                <option value="6 Guests">6 Guests</option>
                                <option value="8 Guests">8 Guests</option>
                                <option value="10 Guests">10 Guests</option>
                                <option value="20 Guests">20 Guests</option>
                            </select>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </div>
                        <small class="af-field-error">Please select the number of guests.</small>
                    </div>

                    <div class="af-form-group af-full-field">
                        <label for="booking_package">Service / Package (Optional)</label>
                        <div class="af-select-wrap">
                            <select id="booking_package" name="package">
                                <option value="">Select a package or service</option>
                                <option>Classic Ghanaian Buffet</option>
                                <option>Executive Lunch Service</option>
                                <option>Wedding Catering Package</option>
                                <option>Corporate Event Package</option>
                            </select>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </div>
                    </div>

                    <div class="af-form-group af-full-field">
                        <label for="booking_requests">Special Requests (Optional)</label>
                        <div class="af-textarea-wrap">
                            <textarea id="booking_requests" name="special_requests" maxlength="200" placeholder="Any special requests or additional information..." data-character-source></textarea>
                            <span class="af-character-count" data-character-count>0/200</span>
                        </div>
                    </div>
                </div>

                <section class="af-booking-summary" aria-live="polite">
                    <h3><i class="bi bi-calendar-check" aria-hidden="true"></i> Booking Summary</h3>
                    <div class="af-summary-grid">
                        <div>
                            <span>Service Type</span>
                            <strong data-summary-service>Table Booking</strong>
                        </div>
                        <div>
                            <span>Date &amp; Time</span>
                            <strong data-summary-date>Select date and time</strong>
                        </div>
                        <div>
                            <span>Guests</span>
                            <strong data-summary-guests>4 Guests</strong>
                        </div>
                    </div>
                    <footer>
                        <span>Total Amount</span>
                        <strong data-summary-total>GHC 120.00</strong>
                    </footer>
                </section>

                <button class="af-send-btn" type="submit">
                    <span>Confirm Booking</span>
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
                <p class="af-form-status" data-form-status aria-live="polite"></p>
                <p class="af-privacy-line"><i class="bi bi-lock-fill" aria-hidden="true"></i> Your information is secure and will only be used for booking purposes.</p>
            </form>
        </section>

        <aside class="af-booking-side">
            <section class="af-service-card af-benefits-card">
                <h2>Why Book With Us?</h2>
                <div class="af-side-list">
                    <?php foreach ($benefits as $benefit): ?>
                        <article>
                            <span><i class="bi <?php echo htmlspecialchars($benefit['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                            <div>
                                <h3><?php echo htmlspecialchars($benefit['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars($benefit['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="af-service-card af-hours-card">
                <h2>Opening Hours</h2>
                <dl>
                    <div><dt>Monday - Thursday</dt><dd>8:00 AM - 10:00 PM</dd></div>
                    <div><dt>Friday - Saturday</dt><dd>8:00 AM - 11:00 PM</dd></div>
                    <div><dt>Sunday</dt><dd>10:00 AM - 9:00 PM</dd></div>
                </dl>
            </section>

            <section class="af-event-card">
                <h2>Planning an Event?</h2>
                <p>Let us make your special occasion unforgettable with our premium catering services.</p>
                <a href="services.php">View Catering Packages <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </section>
        </aside>
    </div>

    <section class="af-feature-strip" aria-label="AfriSense booking benefits">
        <?php foreach ($features as $feature): ?>
            <article>
                <span><i class="bi <?php echo htmlspecialchars($feature['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                <div>
                    <h3><?php echo htmlspecialchars($feature['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?php echo htmlspecialchars($feature['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
