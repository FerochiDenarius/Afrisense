<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'My Bookings | AfriSense';
$customerTitle = 'My Bookings';
$activeCustomerPage = 'bookings';
$extraStyles = [
    $frontendBase . '/assets/css/booking-contact.css',
    $frontendBase . '/assets/css/customer-bookings.css',
];
$extraScripts = [$frontendBase . '/assets/js/booking-contact.js'];

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/public_settings.php';

\AfriSense\Backend\Helpers\Session::start();
$authUser = afrisense_require_customer();

function afrisense_customer_booking_post(string $key, string $fallback = ''): string
{
    return trim((string) ($_POST[$key] ?? $fallback));
}

function afrisense_customer_booking_customer(PDO $pdo, array $user): ?array
{
    $email = trim((string) ($user['email'] ?? ''));
    $phone = preg_replace('/\s+/', '', trim((string) ($user['phonenumber'] ?? $user['phone'] ?? '')));

    $statement = $pdo->prepare(
        'SELECT *
         FROM `customers`
         WHERE `email` = :email OR `phone_number` = :phone
         ORDER BY `id` ASC
         LIMIT 1'
    );
    $statement->execute([
        'email' => $email,
        'phone' => $phone,
    ]);
    $customer = $statement->fetch(PDO::FETCH_ASSOC);

    return $customer ?: null;
}

function afrisense_customer_booking_customer_id(PDO $pdo, array $user, string $fullname, string $email, string $phone): int
{
    $existingCustomer = afrisense_customer_booking_customer($pdo, $user);

    if ($existingCustomer !== null) {
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
            'id' => (int) $existingCustomer['id'],
        ]);

        return (int) $existingCustomer['id'];
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

function afrisense_customer_booking_status_class(string $status): string
{
    return match (strtolower($status)) {
        'confirmed' => 'confirmed',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
        default => 'pending',
    };
}

$bookingMessage = null;
$services = [];
$bookings = [];
$counts = [
    'all' => 0,
    'Pending' => 0,
    'Confirmed' => 0,
    'Completed' => 0,
    'Cancelled' => 0,
];
$customer = null;
$loadError = '';

try {
    $pdo = afrisense_pdo();
    $customer = afrisense_customer_booking_customer($pdo, $authUser);

    $serviceStatement = $pdo->prepare(
        'SELECT `id`, `service_name`, `description`, `price`, `availability`
         FROM `services`
         WHERE `availability` = :availability
         ORDER BY `id` ASC'
    );
    $serviceStatement->execute(['availability' => 'Available']);
    $services = $serviceStatement->fetchAll(PDO::FETCH_ASSOC);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $fullname = afrisense_customer_booking_post('full_name', (string) ($authUser['fullname'] ?? ''));
        $email = afrisense_customer_booking_post('email', (string) ($authUser['email'] ?? ''));
        $phone = preg_replace('/\s+/', '', afrisense_customer_booking_post('phone', (string) ($authUser['phonenumber'] ?? $authUser['phone'] ?? '')));
        $serviceId = (int) ($_POST['service_id'] ?? 0);
        $eventDate = afrisense_customer_booking_post('booking_date');
        $eventTime = afrisense_customer_booking_post('booking_time');
        $guestText = afrisense_customer_booking_post('guests');
        $guests = max(1, (int) preg_replace('/\D+/', '', $guestText));
        $location = afrisense_customer_booking_post('event_location', 'AfriSense Restaurant');
        $specialRequests = afrisense_customer_booking_post('special_requests');

        $serviceCheck = $pdo->prepare('SELECT `id` FROM `services` WHERE `id` = :id LIMIT 1');
        $serviceCheck->execute(['id' => $serviceId]);

        if ($fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $serviceCheck->fetchColumn() === false || $eventDate === '' || $eventTime === '') {
            $bookingMessage = ['type' => 'error', 'text' => 'Please complete all required booking fields.'];
        } else {
            $pdo->beginTransaction();
            $customerId = afrisense_customer_booking_customer_id($pdo, $authUser, $fullname, $email, $phone);
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
                (int) ($authUser['id'] ?? 0) ?: null
            );
            $pdo->commit();
            $bookingMessage = ['type' => 'success', 'text' => 'Booking submitted. Our team will confirm it shortly.'];
            $customer = afrisense_customer_booking_customer($pdo, $authUser);
        }
    }

    if ($customer !== null) {
        $bookingStatement = $pdo->prepare(
            'SELECT
                b.`id`,
                b.`event_date`,
                b.`event_time`,
                b.`event_location`,
                b.`number_of_guests`,
                b.`special_requests`,
                b.`booking_status`,
                COALESCE(s.`service_name`, "Service Booking") AS service_name,
                COALESCE(s.`price`, 0) AS service_price
             FROM `bookings` b
             LEFT JOIN `services` s ON s.`id` = b.`service_id`
             WHERE b.`customer_id` = :customer_id
             ORDER BY b.`event_date` DESC, b.`event_time` DESC, b.`id` DESC
             LIMIT 20'
        );
        $bookingStatement->execute(['customer_id' => (int) $customer['id']]);
        $bookings = $bookingStatement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($bookings as $booking) {
            $status = (string) ($booking['booking_status'] ?? 'Pending');
            $counts[$status] = ($counts[$status] ?? 0) + 1;
            $counts['all']++;
        }
    }
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $loadError = 'Bookings could not be loaded. Check that MySQL is running.';
}

if ($services === []) {
    $services = [
        ['id' => 0, 'service_name' => 'Service Unavailable', 'description' => 'Please contact AfriSense to book manually.', 'price' => 0],
    ];
}

$serviceIcons = ['bi-calendar3', 'bi-gift', 'bi-people', 'bi-heart'];
$customerName = (string) ($customer['fullname'] ?? $authUser['fullname'] ?? '');
$customerEmail = (string) ($customer['email'] ?? $authUser['email'] ?? '');
$customerPhone = (string) ($customer['phone_number'] ?? $authUser['phonenumber'] ?? $authUser['phone'] ?? '');

ob_start();
?>
<section class="af-customer-bookings-page">
    <header class="af-customer-bookings-heading">
        <div>
            <h1>My Bookings</h1>
            <p>Book a table or service, then track confirmation from your customer panel.</p>
        </div>
        <a href="#booking_form">
            <i class="bi bi-calendar-plus" aria-hidden="true"></i>
            New Booking
        </a>
    </header>

    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="af-customer-booking-metrics">
        <article><span><i class="bi bi-calendar3" aria-hidden="true"></i></span><div><small>Total Bookings</small><strong><?php echo htmlspecialchars((string) $counts['all'], ENT_QUOTES, 'UTF-8'); ?></strong><p>All reservations</p></div></article>
        <article><span><i class="bi bi-clock" aria-hidden="true"></i></span><div><small>Pending</small><strong><?php echo htmlspecialchars((string) $counts['Pending'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Awaiting approval</p></div></article>
        <article><span><i class="bi bi-check-circle" aria-hidden="true"></i></span><div><small>Confirmed</small><strong><?php echo htmlspecialchars((string) $counts['Confirmed'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Ready for you</p></div></article>
        <article><span><i class="bi bi-x-circle" aria-hidden="true"></i></span><div><small>Cancelled</small><strong><?php echo htmlspecialchars((string) $counts['Cancelled'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Cancelled bookings</p></div></article>
    </section>

    <section class="af-customer-bookings-grid">
        <section class="af-customer-bookings-list">
            <header>
                <h2>Recent Bookings</h2>
                <p>Your latest reservations and catering requests.</p>
            </header>
            <?php if ($bookings === []): ?>
                <article class="af-customer-empty-bookings">
                    <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                    <h3>No bookings yet</h3>
                    <p>Create a booking using the form on this page.</p>
                </article>
            <?php endif; ?>
            <?php foreach ($bookings as $booking): ?>
                <?php
                $bookingDate = strtotime((string) ($booking['event_date'] ?? '')) ?: time();
                $bookingTime = strtotime((string) ($booking['event_time'] ?? '')) ?: time();
                $status = (string) ($booking['booking_status'] ?? 'Pending');
                ?>
                <article class="af-customer-booking-row">
                    <span><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
                    <div>
                        <h3>
                            BK-<?php echo htmlspecialchars(str_pad((string) ($booking['id'] ?? 0), 6, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?>
                            <small class="<?php echo htmlspecialchars(afrisense_customer_booking_status_class($status), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></small>
                        </h3>
                        <p><?php echo htmlspecialchars((string) ($booking['service_name'] ?? 'Service Booking'), ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars((string) ($booking['number_of_guests'] ?? 1), ENT_QUOTES, 'UTF-8'); ?> guests</p>
                        <p><i class="bi bi-geo-alt" aria-hidden="true"></i> <?php echo htmlspecialchars((string) ($booking['event_location'] ?? 'AfriSense Restaurant'), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <time datetime="<?php echo htmlspecialchars((string) ($booking['event_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars(date('j M Y', $bookingDate), ENT_QUOTES, 'UTF-8'); ?>
                        <small><?php echo htmlspecialchars(date('h:i A', $bookingTime), ENT_QUOTES, 'UTF-8'); ?></small>
                    </time>
                </article>
            <?php endforeach; ?>
        </section>

        <aside class="af-customer-booking-help">
            <h2>Need Help?</h2>
            <p>Our team can adjust dates, guest count, or special requests before confirmation.</p>
            <a href="tel:+233241234567"><i class="bi bi-telephone" aria-hidden="true"></i> +233 24 123 4567</a>
        </aside>
    </section>

    <section class="af-booking-page af-customer-booking-form-wrap" id="booking_form">
        <div class="af-booking-grid">
            <section class="af-service-card af-booking-form-card" aria-labelledby="booking_title">
                <header class="af-section-heading">
                    <span><i class="bi bi-calendar3" aria-hidden="true"></i></span>
                    <div>
                        <h2 id="booking_title">New Booking</h2>
                        <p>Select a service, share your details, and our team will confirm your reservation.</p>
                    </div>
                </header>

                <form class="af-service-form" action="my-bookings.php#booking_form" method="post" data-booking-form data-enhanced-form>
                    <fieldset class="af-service-types">
                        <legend>Select Service</legend>
                        <?php foreach ($services as $index => $service): ?>
                            <?php $serviceName = (string) ($service['service_name'] ?? 'Service'); ?>
                            <label class="<?php echo $index === 0 ? 'is-active' : ''; ?>" data-service-option data-price="<?php echo (float) ($service['price'] ?? 0); ?>">
                                <input type="radio" name="service_id" value="<?php echo htmlspecialchars((string) ($service['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" data-service-name="<?php echo htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $index === 0 ? 'checked' : ''; ?> required>
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
                                <input type="text" id="booking_full_name" name="full_name" value="<?php echo htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your full name" autocomplete="name" minlength="2" required>
                            </div>
                            <small class="af-field-error">Please enter your full name.</small>
                        </div>

                        <div class="af-form-group">
                            <label for="booking_phone">Phone Number <strong>*</strong></label>
                            <div class="af-phone-field">
                                <span class="af-country-code"><span class="af-gh-flag" aria-hidden="true"></span> +233</span>
                                <input type="tel" id="booking_phone" name="phone" value="<?php echo htmlspecialchars(preg_replace('/^\+?233/', '', $customerPhone), ENT_QUOTES, 'UTF-8'); ?>" placeholder="24 123 4567" autocomplete="tel-national" inputmode="tel" pattern="[0-9\s]{9,12}" required>
                            </div>
                            <small class="af-field-error">Please enter a valid phone number.</small>
                        </div>

                        <div class="af-form-group">
                            <label for="booking_email">Email Address <strong>*</strong></label>
                            <div class="af-input-icon">
                                <i class="bi bi-envelope" aria-hidden="true"></i>
                                <input type="email" id="booking_email" name="email" value="<?php echo htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your email address" autocomplete="email" required>
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
                            <label for="booking_location">Event / Reservation Location <strong>*</strong></label>
                            <div class="af-input-icon">
                                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                <input type="text" id="booking_location" name="event_location" value="<?php echo htmlspecialchars((string) ($customer['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="AfriSense Restaurant, Oyarifa, Accra..." autocomplete="street-address" required>
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
                            <div><span>Service Type</span><strong data-summary-service>Table Booking</strong></div>
                            <div><span>Date &amp; Time</span><strong data-summary-date>Select date and time</strong></div>
                            <div><span>Guests</span><strong data-summary-guests>4 Guests</strong></div>
                        </div>
                        <footer><span>Total Amount</span><strong data-summary-total>GHC 120.00</strong></footer>
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
                    <h2>Booking Support</h2>
                    <div class="af-side-list">
                        <article><span><i class="bi bi-calendar-check" aria-hidden="true"></i></span><div><h3>Easy Booking</h3><p>Submit reservations without leaving your customer panel.</p></div></article>
                        <article><span><i class="bi bi-patch-check" aria-hidden="true"></i></span><div><h3>Admin Confirmation</h3><p>Your request appears in the admin bookings page for approval.</p></div></article>
                        <article><span><i class="bi bi-headset" aria-hidden="true"></i></span><div><h3>Support Available</h3><p>Call us if you need to adjust your booking details.</p></div></article>
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
            </aside>
        </div>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/customer_layout.php';
?>
