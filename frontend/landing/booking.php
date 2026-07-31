<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Book a Service | AfriSense';
$activePage = 'booking';
$extraStyles = [$frontendBase . '/assets/css/booking-contact.css'];
$extraScripts = [$frontendBase . '/assets/js/booking-contact.js'];

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/public_settings.php';

afrisense_enforce_public_site_status($frontendBase);

function afrisense_booking_post(string $key, string $fallback = ''): string
{
    return trim((string) ($_POST[$key] ?? $fallback));
}

function afrisense_booking_customer_id(PDO $pdo, string $fullname, string $email, string $phone): int
{
    $statement = $pdo->prepare(
        'SELECT `id`
         FROM `customers`
         WHERE `email` = :email OR `phone_number` = :phone
         ORDER BY `id` ASC
         LIMIT 1'
    );
    $statement->execute(['email' => $email, 'phone' => $phone]);
    $customerId = $statement->fetchColumn();

    if ($customerId !== false) {
        $update = $pdo->prepare(
            'UPDATE `customers`
             SET `fullname` = :fullname,
                 `email` = :email,
                 `phone_number` = :phone,
                 `updated_at` = NOW()
             WHERE `id` = :id'
        );
        $update->execute([
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'id' => (int) $customerId,
        ]);

        return (int) $customerId;
    }

    $insert = $pdo->prepare(
        'INSERT INTO `customers` (`fullname`, `email`, `phone_number`, `address`)
         VALUES (:fullname, :email, :phone, :address)'
    );
    $insert->execute([
        'fullname' => $fullname,
        'email' => $email,
        'phone' => $phone,
        'address' => 'Provided during booking',
    ]);

    return (int) $pdo->lastInsertId();
}

$bookingMessage = null;
$services = [];

try {
    $pdo = afrisense_pdo();
    $serviceStatement = $pdo->prepare(
        'SELECT `id`, `service_name`, `description`, `price`, `availability`
         FROM `services`
         WHERE `availability` = :availability
         ORDER BY `id` ASC'
    );
    $serviceStatement->execute(['availability' => 'Available']);
    $services = $serviceStatement->fetchAll(PDO::FETCH_ASSOC);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $fullname = afrisense_booking_post('full_name');
        $email = afrisense_booking_post('email');
        $phone = preg_replace('/\s+/', '', afrisense_booking_post('phone'));
        $serviceId = (int) ($_POST['service_id'] ?? 0);
        $eventDate = afrisense_booking_post('booking_date');
        $eventTime = afrisense_booking_post('booking_time');
        $guestText = afrisense_booking_post('guests');
        $guests = max(1, (int) preg_replace('/\D+/', '', $guestText));
        $location = afrisense_booking_post('event_location', 'AfriSense Restaurant');
        $specialRequests = afrisense_booking_post('special_requests');

        $serviceCheck = $pdo->prepare('SELECT `id` FROM `services` WHERE `id` = :id LIMIT 1');
        $serviceCheck->execute(['id' => $serviceId]);

        if ($fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $serviceCheck->fetchColumn() === false || $eventDate === '' || $eventTime === '') {
            $bookingMessage = ['type' => 'error', 'text' => 'Please complete all required booking fields.'];
        } else {
            $pdo->beginTransaction();
            $customerId = afrisense_booking_customer_id($pdo, $fullname, $email, $phone);
            $insert = $pdo->prepare(
                'INSERT INTO `bookings`
                    (`customer_id`, `service_id`, `event_date`, `event_time`, `event_location`, `number_of_guests`, `special_requests`, `booking_status`)
                 VALUES
                    (:customer_id, :service_id, :event_date, :event_time, :event_location, :number_of_guests, :special_requests, :booking_status)'
            );
            $insert->execute([
                'customer_id' => $customerId,
                'service_id' => $serviceId,
                'event_date' => $eventDate,
                'event_time' => $eventTime,
                'event_location' => $location,
                'number_of_guests' => $guests,
                'special_requests' => $specialRequests,
                'booking_status' => 'Pending',
            ]);
            $bookingId = (int) $pdo->lastInsertId();
            afrisense_public_create_admin_notifications(
                $pdo,
                'booking_notifications',
                'New Booking Received',
                $fullname . ' submitted a booking for ' . $eventDate . ' at ' . $eventTime . '.',
                'Booking',
                '/Afrisense/frontend/admin/bookings.php?view=' . $bookingId,
                null
            );
            $pdo->commit();
            $bookingMessage = ['type' => 'success', 'text' => 'Booking submitted. Our team will confirm it shortly.'];
        }
    }
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $bookingMessage = ['type' => 'error', 'text' => 'Booking could not be submitted. Please try again.'];
}

if ($services === []) {
    $services = [
        ['id' => 0, 'service_name' => 'Service Unavailable', 'description' => 'Please contact AfriSense to book manually.', 'price' => 0],
    ];
}

$requestedService = strtolower(preg_replace('/[^a-z0-9]+/', '', (string) ($_GET['service'] ?? '')));
$requestedDate = (string) ($_GET['booking_date'] ?? '');
$requestedTime = (string) ($_GET['booking_time'] ?? '');
$prefillDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate) === 1 ? $requestedDate : '';
$prefillTime = preg_match('/^\d{2}:\d{2}$/', $requestedTime) === 1 ? $requestedTime : '';
$selectedServiceIndex = 0;

if ($requestedService !== '') {
    foreach ($services as $index => $service) {
        $serviceNameKey = strtolower(preg_replace('/[^a-z0-9]+/', '', (string) ($service['service_name'] ?? '')));

        if ($serviceNameKey !== '' && ($serviceNameKey === $requestedService || str_contains($serviceNameKey, $requestedService) || str_contains($requestedService, $serviceNameKey))) {
            $selectedServiceIndex = (int) $index;
            break;
        }
    }
}

$serviceIcons = ['bi-calendar3', 'bi-gift', 'bi-people', 'bi-heart'];

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

            <form class="af-service-form" action="booking.php" method="post" data-booking-form data-enhanced-form>
                <fieldset class="af-service-types">
                    <legend>Select Service</legend>
                    <?php foreach ($services as $index => $service): ?>
                        <?php $serviceName = (string) ($service['service_name'] ?? 'Service'); ?>
                        <label class="<?php echo $index === $selectedServiceIndex ? 'is-active' : ''; ?>" data-service-option data-price="<?php echo (float) ($service['price'] ?? 0); ?>">
                            <input type="radio" name="service_id" value="<?php echo htmlspecialchars((string) ($service['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" data-service-name="<?php echo htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $index === $selectedServiceIndex ? 'checked' : ''; ?> required>
                            <span class="af-type-check"><i class="bi bi-check" aria-hidden="true"></i></span>
                            <i class="bi <?php echo htmlspecialchars($serviceIcons[$index % count($serviceIcons)], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                            <strong><?php echo htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <small><?php echo htmlspecialchars((string) ($service['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
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
                            <input type="date" id="booking_date" name="booking_date" value="<?php echo htmlspecialchars($prefillDate, ENT_QUOTES, 'UTF-8'); ?>" data-booking-date required>
                        </div>
                        <small class="af-field-error">Please select a booking date.</small>
                    </div>

                    <div class="af-form-group">
                        <label for="booking_time">Time <strong>*</strong></label>
                        <div class="af-input-icon">
                            <i class="bi bi-clock" aria-hidden="true"></i>
                            <input type="time" id="booking_time" name="booking_time" value="<?php echo htmlspecialchars($prefillTime, ENT_QUOTES, 'UTF-8'); ?>" data-booking-time required>
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
                        <label for="booking_location">Event / Reservation Location <strong>*</strong></label>
                        <div class="af-input-icon">
                            <i class="bi bi-geo-alt" aria-hidden="true"></i>
                            <input type="text" id="booking_location" name="event_location" placeholder="AfriSense Restaurant, Oyarifa, Accra..." autocomplete="street-address" required>
                        </div>
                        <small class="af-field-error">Please enter the reservation or event location.</small>
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
                <p class="af-form-status <?php echo $bookingMessage !== null ? 'is-' . htmlspecialchars($bookingMessage['type'], ENT_QUOTES, 'UTF-8') : ''; ?>" data-form-status aria-live="polite">
                    <?php echo $bookingMessage !== null ? htmlspecialchars($bookingMessage['text'], ENT_QUOTES, 'UTF-8') : ''; ?>
                </p>
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
