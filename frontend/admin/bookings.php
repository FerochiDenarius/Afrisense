<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Bookings | AfriSense';
$adminTitle = 'Bookings';
$activeAdminPage = 'bookings';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

afrisense_require_admin();

function afrisense_booking_status_class(string $status): string
{
    return match (strtolower($status)) {
        'confirmed' => 'confirmed',
        'completed' => 'delivered',
        'cancelled' => 'cancelled',
        default => 'pending',
    };
}

function afrisense_count_bookings(PDO $pdo, ?string $status = null): int
{
    if ($status === null) {
        $statement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `bookings`');
        $statement->execute();
    } else {
        $statement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `bookings` WHERE `booking_status` = :status');
        $statement->execute(['status' => $status]);
    }

    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['count_value'] ?? 0);
}

$validStatuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
$statusFilter = (string) ($_GET['status'] ?? '');
$search = trim((string) ($_GET['search'] ?? ''));
$flashMessage = '';
$flashType = 'success';

try {
    $pdo = afrisense_pdo();
    $where = [];
    $params = [];

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $nextStatus = (string) ($_POST['booking_status'] ?? '');

        if ($bookingId <= 0 || !in_array($nextStatus, $validStatuses, true)) {
            $flashType = 'error';
            $flashMessage = 'Booking status could not be updated.';
        } else {
            $update = $pdo->prepare(
                'UPDATE `bookings`
                 SET `booking_status` = :booking_status,
                     `updated_at` = NOW()
                 WHERE `id` = :id'
            );
            $update->execute([
                'booking_status' => $nextStatus,
                'id' => $bookingId,
            ]);

            $flashMessage = 'Booking status updated to ' . $nextStatus . '.';
        }
    }

    if (in_array($statusFilter, $validStatuses, true)) {
        $where[] = 'b.`booking_status` = :status';
        $params['status'] = $statusFilter;
    }

    if ($search !== '') {
        $where[] = '(CAST(b.`id` AS CHAR) LIKE :search OR c.`fullname` LIKE :search OR c.`phone_number` LIKE :search OR c.`email` LIKE :search OR s.`service_name` LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $sql = 'SELECT
                b.`id`,
                b.`event_date`,
                b.`event_time`,
                b.`event_location`,
                b.`number_of_guests`,
                b.`special_requests`,
                b.`booking_status`,
                b.`created_at`,
                COALESCE(c.`fullname`, \'Unknown Customer\') AS fullname,
                COALESCE(c.`email`, \'\') AS email,
                COALESCE(c.`phone_number`, \'\') AS phone_number,
                COALESCE(s.`service_name`, \'Unknown Service\') AS service_name,
                COALESCE(s.`price`, 0) AS price
            FROM `bookings` b
            LEFT JOIN `customers` c ON c.`id` = b.`customer_id`
            LEFT JOIN `services` s ON s.`id` = b.`service_id`';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY b.`event_date` ASC, b.`event_time` ASC, b.`id` DESC LIMIT 25';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $bookings = $statement->fetchAll(PDO::FETCH_ASSOC);

    $totalBookings = afrisense_count_bookings($pdo);
    $pendingBookings = afrisense_count_bookings($pdo, 'Pending');
    $confirmedBookings = afrisense_count_bookings($pdo, 'Confirmed');
    $completedBookings = afrisense_count_bookings($pdo, 'Completed');
    $cancelledBookings = afrisense_count_bookings($pdo, 'Cancelled');

    $revenueStatement = $pdo->prepare(
        'SELECT COALESCE(SUM(s.`price`), 0) AS total_value
         FROM `bookings` b
         INNER JOIN `services` s ON s.`id` = b.`service_id`
         WHERE b.`booking_status` <> :cancelled'
    );
    $revenueStatement->execute(['cancelled' => 'Cancelled']);
    $bookingRevenue = (float) ($revenueStatement->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0);

    $topServicesStatement = $pdo->prepare(
        'SELECT s.`service_name`, COUNT(b.`id`) AS booking_count
         FROM `services` s
         LEFT JOIN `bookings` b ON b.`service_id` = s.`id`
         GROUP BY s.`id`, s.`service_name`
         ORDER BY booking_count DESC, s.`service_name` ASC
         LIMIT 5'
    );
    $topServicesStatement->execute();
    $topServices = $topServicesStatement->fetchAll(PDO::FETCH_ASSOC);
    $loadError = '';
} catch (Throwable $exception) {
    $bookings = [];
    $topServices = [];
    $totalBookings = 0;
    $pendingBookings = 0;
    $confirmedBookings = 0;
    $completedBookings = 0;
    $cancelledBookings = 0;
    $bookingRevenue = 0.0;
    $loadError = 'Bookings could not be loaded. Check that MySQL is running.';
}

ob_start();
?>
<section class="af-admin-menu-page af-bookings-admin-page">
    <header class="af-admin-page-heading">
        <div>
            <h1>Bookings Management</h1>
            <p>Dashboard / Bookings</p>
        </div>
        <div class="af-booking-actions">
            <button type="button"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Export PDF</button>
            <button type="button"><i class="bi bi-printer" aria-hidden="true"></i> Print</button>
            <a class="af-add-menu-btn" href="<?php echo htmlspecialchars($frontendBase . '/landing/booking.php', ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Add Manual Booking
            </a>
        </div>
    </header>

    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section class="af-menu-metrics af-booking-metrics" aria-label="Booking summary">
        <article class="gold">
            <span><i class="bi bi-calendar3" aria-hidden="true"></i></span>
            <div><small>Total Bookings</small><strong><?php echo htmlspecialchars((string) $totalBookings, ENT_QUOTES, 'UTF-8'); ?></strong><p>All reservations</p></div>
        </article>
        <article class="green">
            <span><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
            <div><small>Confirmed</small><strong><?php echo htmlspecialchars((string) $confirmedBookings, ENT_QUOTES, 'UTF-8'); ?></strong><p>Approved bookings</p></div>
        </article>
        <article class="blue">
            <span><i class="bi bi-check-circle" aria-hidden="true"></i></span>
            <div><small>Completed</small><strong><?php echo htmlspecialchars((string) $completedBookings, ENT_QUOTES, 'UTF-8'); ?></strong><p>Finished events</p></div>
        </article>
        <article class="purple">
            <span><i class="bi bi-clock" aria-hidden="true"></i></span>
            <div><small>Pending Approval</small><strong><?php echo htmlspecialchars((string) $pendingBookings, ENT_QUOTES, 'UTF-8'); ?></strong><p>Awaiting review</p></div>
        </article>
        <article class="red">
            <span><i class="bi bi-x-circle" aria-hidden="true"></i></span>
            <div><small>Cancelled</small><strong><?php echo htmlspecialchars((string) $cancelledBookings, ENT_QUOTES, 'UTF-8'); ?></strong><p>Cancelled bookings</p></div>
        </article>
        <article class="gold">
            <span><i class="bi bi-wallet2" aria-hidden="true"></i></span>
            <div><small>Booking Value</small><strong>GHc <?php echo htmlspecialchars(number_format($bookingRevenue, 2), ENT_QUOTES, 'UTF-8'); ?></strong><p>From services</p></div>
        </article>
    </section>

    <section class="af-bookings-workspace">
        <section class="af-menu-table-card">
            <form class="af-bookings-filters" action="bookings.php" method="get">
                <label class="af-menu-search" for="booking_search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="booking_search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search booking ID, customer, phone, email...">
                </label>
                <label class="af-menu-select" for="booking_status_filter">
                    <select id="booking_status_filter" name="status">
                        <option value="">All Status</option>
                        <?php foreach ($validStatuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
                <button type="submit"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
                <a href="bookings.php">Reset</a>
            </form>

            <nav class="af-booking-tabs" aria-label="Booking status filters">
                <a class="<?php echo $statusFilter === '' ? 'active' : ''; ?>" href="bookings.php">All Bookings</a>
                <?php foreach ($validStatuses as $status): ?>
                    <a class="<?php echo $statusFilter === $status ? 'active' : ''; ?>" href="bookings.php?status=<?php echo urlencode($status); ?>">
                        <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="af-menu-table af-bookings-table">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" aria-label="Select all bookings"></th>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Guests</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bookings === []): ?>
                            <tr>
                                <td colspan="11"><div class="af-empty-state">No bookings found.</div></td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($bookings as $booking): ?>
                            <?php
                            $status = (string) ($booking['booking_status'] ?? 'Pending');
                            $eventDate = strtotime((string) ($booking['event_date'] ?? '')) ?: time();
                            $eventTime = strtotime((string) ($booking['event_time'] ?? '')) ?: time();
                            ?>
                            <tr>
                                <td><input type="checkbox" aria-label="Select booking <?php echo htmlspecialchars((string) ($booking['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></td>
                                <td><strong class="af-order-id">BK-<?php echo htmlspecialchars(str_pad((string) ($booking['id'] ?? 0), 6, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td>
                                    <span class="af-order-customer">
                                        <strong><?php echo htmlspecialchars((string) ($booking['fullname'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <small><?php echo htmlspecialchars((string) ($booking['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </span>
                                </td>
                                <td>
                                    <span class="af-order-customer">
                                        <strong><?php echo htmlspecialchars((string) ($booking['service_name'] ?? 'Service'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <small><?php echo htmlspecialchars((string) ($booking['special_requests'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </span>
                                </td>
                                <td><i class="bi bi-people" aria-hidden="true"></i> <?php echo htmlspecialchars((string) ($booking['number_of_guests'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars(date('d M Y', $eventDate), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars(date('h:i A', $eventTime), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>GHc <?php echo htmlspecialchars(number_format((float) ($booking['price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="af-order-status <?php echo htmlspecialchars(afrisense_booking_status_class($status), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo htmlspecialchars((string) ($booking['event_location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="af-row-actions af-booking-row-actions">
                                        <button type="button" aria-label="View booking"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                        <?php if ($status === 'Pending'): ?>
                                            <form action="bookings.php" method="post">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars((string) ($booking['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="booking_status" value="Confirmed">
                                                <button class="success" type="submit" aria-label="Confirm booking"><i class="bi bi-check2" aria-hidden="true"></i></button>
                                            </form>
                                            <form action="bookings.php" method="post">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars((string) ($booking['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="booking_status" value="Cancelled">
                                                <button class="danger" type="submit" aria-label="Cancel booking"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                                            </form>
                                        <?php elseif ($status === 'Confirmed'): ?>
                                            <form action="bookings.php" method="post">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars((string) ($booking['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="booking_status" value="Completed">
                                                <button class="success" type="submit" aria-label="Mark booking completed"><i class="bi bi-check2-circle" aria-hidden="true"></i></button>
                                            </form>
                                            <form action="bookings.php" method="post">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars((string) ($booking['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="booking_status" value="Cancelled">
                                                <button class="danger" type="submit" aria-label="Cancel booking"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                                            </form>
                                        <?php elseif ($status === 'Cancelled'): ?>
                                            <form action="bookings.php" method="post">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars((string) ($booking['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="booking_status" value="Pending">
                                                <button class="warning" type="submit" aria-label="Reopen booking"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <footer class="af-menu-pagination">
                <p>Showing 1 to <?php echo htmlspecialchars((string) count($bookings), ENT_QUOTES, 'UTF-8'); ?> of <?php echo htmlspecialchars((string) $totalBookings, ENT_QUOTES, 'UTF-8'); ?> bookings</p>
                <nav aria-label="Bookings pagination">
                    <a href="#" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                    <a class="active" href="#">1</a>
                    <a href="#">2</a>
                    <a href="#" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                </nav>
            </footer>
        </section>

        <aside class="af-bookings-side">
            <section class="af-menu-panel">
                <h2>Today's Overview</h2>
                <ul class="af-order-pipeline">
                    <li><span class="pending"></span>Total Bookings <strong><?php echo htmlspecialchars((string) $totalBookings, ENT_QUOTES, 'UTF-8'); ?></strong></li>
                    <li><span class="delivered"></span>Confirmed <strong><?php echo htmlspecialchars((string) $confirmedBookings, ENT_QUOTES, 'UTF-8'); ?></strong></li>
                    <li><span class="preparing"></span>Pending Approval <strong><?php echo htmlspecialchars((string) $pendingBookings, ENT_QUOTES, 'UTF-8'); ?></strong></li>
                    <li><span class="cancelled"></span>Cancelled <strong><?php echo htmlspecialchars((string) $cancelledBookings, ENT_QUOTES, 'UTF-8'); ?></strong></li>
                </ul>
            </section>

            <section class="af-menu-panel">
                <h2>Top Services</h2>
                <ul class="af-food-category-list">
                    <?php foreach ($topServices as $service): ?>
                        <li>
                            <span>
                                <strong><?php echo htmlspecialchars((string) ($service['service_name'] ?? 'Service'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars((string) ($service['booking_count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> bookings</small>
                            </span>
                            <em><?php echo htmlspecialchars((string) ($service['booking_count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></em>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
