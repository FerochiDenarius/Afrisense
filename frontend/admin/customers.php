<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Customers | AfriSense';
$adminTitle = 'Customers';
$activeAdminPage = 'customers';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

afrisense_require_admin();
$itemsPerPage = afrisense_admin_items_per_page(40);

// Defines the afrisense_customer_stage_label helper used by this module.
function afrisense_customer_stage_label(string $stage): string
{
    return match ($stage) {
        'orders' => 'Customers With Orders',
        'delivered' => 'Served / Delivered',
        'active_bookings' => 'Active Bookings',
        'pending_bookings' => 'Pending Bookings',
        default => 'All Customers',
    };
}

// Defines the afrisense_customer_stage_class helper used by this module.
function afrisense_customer_stage_class(string $stage): string
{
    return match ($stage) {
        'Delivered', 'Completed' => 'delivered',
        'Pending' => 'pending',
        'Confirmed', 'Active Booking' => 'confirmed',
        'Order Placed' => 'order',
        default => 'neutral',
    };
}

// Defines the afrisense_customer_count helper used by this module.
function afrisense_customer_count(PDO $pdo, string $sql, array $params = []): int
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return (int) $statement->fetchColumn();
}

$search = trim((string) ($_GET['search'] ?? ''));
$stage = trim((string) ($_GET['stage'] ?? ''));
$selectedDate = trim((string) ($_GET['date'] ?? date('Y-m-d')));
$dateFilterActive = array_key_exists('date', $_GET) && trim((string) ($_GET['date'] ?? '')) !== '';
$validStages = ['', 'orders', 'delivered', 'active_bookings', 'pending_bookings'];

// Guard this block so it only runs when the required condition is met.
if (!in_array($stage, $validStages, true)) {
    $stage = '';
}

// Guard this block so it only runs when the required condition is met.
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// Run database/action work inside a guarded block so the page can fail gracefully.
try {
    $pdo = afrisense_pdo();
    $params = [];
    $where = [];

    // Guard this block so it only runs when the required condition is met.
    if ($search !== '') {
        $where[] = '(c.`fullname` LIKE :search OR c.`email` LIKE :search OR c.`phone_number` LIKE :search OR c.`address` LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    // Guard this block so it only runs when the required condition is met.
    if ($stage === 'orders') {
        $where[] = 'COALESCE(o.`order_count`, 0) > 0';
    } elseif ($stage === 'delivered') {
        $where[] = 'COALESCE(o.`delivered_count`, 0) > 0';
    } elseif ($stage === 'active_bookings') {
        $where[] = 'COALESCE(b.`active_booking_count`, 0) > 0';
    } elseif ($stage === 'pending_bookings') {
        $where[] = 'COALESCE(b.`pending_booking_count`, 0) > 0';
    }

    // Guard this block so it only runs when the required condition is met.
    if ($dateFilterActive) {
        $where[] = '(
            EXISTS (
                SELECT 1 FROM `orders` od
                WHERE od.`customer_id` = c.`id`
                AND DATE(od.`ordered_at`) = :selected_date_orders
            )
            OR EXISTS (
                SELECT 1 FROM `bookings` bd
                WHERE bd.`customer_id` = c.`id`
                AND bd.`event_date` = :selected_date_bookings
            )
        )';
        $params['selected_date_orders'] = $selectedDate;
        $params['selected_date_bookings'] = $selectedDate;
    }

    $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

    $customerStatement = $pdo->prepare(
        'SELECT
            c.`id`,
            c.`fullname`,
            c.`email`,
            c.`phone_number`,
            c.`address`,
            c.`created_at`,
            COALESCE(c.`email_verified`, 0) AS email_verified,
            COALESCE(o.`order_count`, 0) AS order_count,
            COALESCE(o.`delivered_count`, 0) AS delivered_count,
            COALESCE(o.`pending_order_count`, 0) AS pending_order_count,
            COALESCE(o.`total_spent`, 0) AS total_spent,
            o.`last_order_at`,
            COALESCE(b.`booking_count`, 0) AS booking_count,
            COALESCE(b.`active_booking_count`, 0) AS active_booking_count,
            COALESCE(b.`pending_booking_count`, 0) AS pending_booking_count,
            b.`next_booking_date`
         FROM `customers` c
         LEFT JOIN (
            SELECT
                `customer_id`,
                COUNT(*) AS order_count,
                SUM(CASE WHEN `order_status` = "Delivered" THEN 1 ELSE 0 END) AS delivered_count,
                SUM(CASE WHEN `order_status` IN ("Pending", "Confirmed", "Preparing", "Ready", "Out for Delivery") THEN 1 ELSE 0 END) AS pending_order_count,
                COALESCE(SUM(`total_price`), 0) AS total_spent,
                MAX(`ordered_at`) AS last_order_at
            FROM `orders`
            GROUP BY `customer_id`
         ) o ON o.`customer_id` = c.`id`
         LEFT JOIN (
            SELECT
                `customer_id`,
                COUNT(*) AS booking_count,
                SUM(CASE WHEN `booking_status` IN ("Pending", "Confirmed") AND `event_date` >= CURDATE() THEN 1 ELSE 0 END) AS active_booking_count,
                SUM(CASE WHEN `booking_status` = "Pending" THEN 1 ELSE 0 END) AS pending_booking_count,
                MIN(CASE WHEN `booking_status` IN ("Pending", "Confirmed") AND `event_date` >= CURDATE() THEN `event_date` ELSE NULL END) AS next_booking_date
            FROM `bookings`
            GROUP BY `customer_id`
         ) b ON b.`customer_id` = c.`id`
         ' . $whereSql . '
         ORDER BY
            CASE WHEN b.`next_booking_date` IS NULL THEN 1 ELSE 0 END,
            b.`next_booking_date` ASC,
            o.`last_order_at` DESC,
            c.`created_at` DESC
         LIMIT ' . $itemsPerPage
    );
    $customerStatement->execute($params);
    $customers = $customerStatement->fetchAll(PDO::FETCH_ASSOC);

    $activityStatement = $pdo->prepare(
        'SELECT
            "Order Placed" AS activity_type,
            o.`id` AS item_id,
            c.`fullname`,
            c.`email`,
            c.`phone_number`,
            o.`order_status` AS status,
            o.`total_price` AS amount,
            DATE(o.`ordered_at`) AS activity_date,
            TIME(o.`ordered_at`) AS activity_time,
            f.`food_name` AS item_name
         FROM `orders` o
         INNER JOIN `customers` c ON c.`id` = o.`customer_id`
         LEFT JOIN `foods` f ON f.`id` = o.`food_id`
         WHERE DATE(o.`ordered_at`) = :selected_date_orders
         UNION ALL
         SELECT
            "Booking" AS activity_type,
            b.`id` AS item_id,
            c.`fullname`,
            c.`email`,
            c.`phone_number`,
            b.`booking_status` AS status,
            s.`price` AS amount,
            b.`event_date` AS activity_date,
            b.`event_time` AS activity_time,
            s.`service_name` AS item_name
         FROM `bookings` b
         INNER JOIN `customers` c ON c.`id` = b.`customer_id`
         LEFT JOIN `services` s ON s.`id` = b.`service_id`
         WHERE b.`event_date` = :selected_date_bookings
         ORDER BY activity_time ASC, item_id ASC'
    );
    $activityStatement->execute([
        'selected_date_orders' => $selectedDate,
        'selected_date_bookings' => $selectedDate,
    ]);
    $dateActivities = $activityStatement->fetchAll(PDO::FETCH_ASSOC);

    $totalCustomers = afrisense_customer_count($pdo, 'SELECT COUNT(*) FROM `customers`');
    $customersWithOrders = afrisense_customer_count($pdo, 'SELECT COUNT(DISTINCT `customer_id`) FROM `orders`');
    $servedCustomers = afrisense_customer_count($pdo, 'SELECT COUNT(DISTINCT `customer_id`) FROM `orders` WHERE `order_status` = :status', ['status' => 'Delivered']);
    $activeBookingCustomers = afrisense_customer_count(
        $pdo,
        'SELECT COUNT(DISTINCT `customer_id`) FROM `bookings` WHERE `booking_status` IN ("Pending", "Confirmed") AND `event_date` >= CURDATE()'
    );
    $pendingBookingCustomers = afrisense_customer_count($pdo, 'SELECT COUNT(DISTINCT `customer_id`) FROM `bookings` WHERE `booking_status` = :status', ['status' => 'Pending']);
    $dateCustomers = count(array_unique(array_map(static fn (array $activity): string => (string) ($activity['email'] ?? ''), $dateActivities)));
    $loadError = '';
} catch (Throwable $exception) {
    $customers = [];
    $dateActivities = [];
    $totalCustomers = 0;
    $customersWithOrders = 0;
    $servedCustomers = 0;
    $activeBookingCustomers = 0;
    $pendingBookingCustomers = 0;
    $dateCustomers = 0;
    $loadError = 'Customers could not be loaded. Check that MySQL is running.';
}

$selectedTimestamp = strtotime($selectedDate) ?: time();
$previousDate = date('Y-m-d', strtotime('-1 day', $selectedTimestamp));
$nextDate = date('Y-m-d', strtotime('+1 day', $selectedTimestamp));
$todayDate = date('Y-m-d');

ob_start();
?>
<!-- Page section for this part of the AfriSense interface. -->
<section class="af-admin-menu-page af-customers-page">
    <!-- Header block for this interface section. -->
    <header class="af-admin-page-heading">
        <div>
            <h1>Customers</h1>
            <p>View customer orders, served history, active bookings and pending reservations by date.</p>
        </div>
        <div class="af-customer-date-actions">
            <a href="customers.php?date=<?php echo htmlspecialchars($previousDate, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i> Previous Day</a>
            <a class="af-add-menu-btn" href="customers.php?date=<?php echo htmlspecialchars($todayDate, ENT_QUOTES, 'UTF-8'); ?>">Today</a>
            <a href="customers.php?date=<?php echo htmlspecialchars($nextDate, ENT_QUOTES, 'UTF-8'); ?>">Next Day <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
        </div>
    </header>

    <?php // Render this conditional/dynamic template block. ?>
    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- Page section for this part of the AfriSense interface. -->
    <section class="af-menu-metrics af-customer-metrics" aria-label="Customer summary">
        <article class="green">
            <span><i class="bi bi-people" aria-hidden="true"></i></span>
            <div><small>Total Customers</small><strong><?php echo htmlspecialchars((string) $totalCustomers, ENT_QUOTES, 'UTF-8'); ?></strong><p>All customer records</p></div>
        </article>
        <article class="gold">
            <span><i class="bi bi-cart-check" aria-hidden="true"></i></span>
            <div><small>Placed Orders</small><strong><?php echo htmlspecialchars((string) $customersWithOrders, ENT_QUOTES, 'UTF-8'); ?></strong><p>Customers with orders</p></div>
        </article>
        <article class="blue">
            <span><i class="bi bi-truck" aria-hidden="true"></i></span>
            <div><small>Served / Delivered</small><strong><?php echo htmlspecialchars((string) $servedCustomers, ENT_QUOTES, 'UTF-8'); ?></strong><p>Delivered order customers</p></div>
        </article>
        <article class="purple">
            <span><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
            <div><small>Active Bookings</small><strong><?php echo htmlspecialchars((string) $activeBookingCustomers, ENT_QUOTES, 'UTF-8'); ?></strong><p>Upcoming or due</p></div>
        </article>
        <article class="red">
            <span><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
            <div><small>Pending Bookings</small><strong><?php echo htmlspecialchars((string) $pendingBookingCustomers, ENT_QUOTES, 'UTF-8'); ?></strong><p>Waiting approval</p></div>
        </article>
    </section>

    <!-- Page section for this part of the AfriSense interface. -->
    <section class="af-menu-table-card">
        <!-- Form block that submits this page workflow. -->
        <form class="af-menu-filters af-customers-filters" action="customers.php" method="get">
            <label class="af-menu-search" for="customer_search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="customer_search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search customers, email, phone...">
            </label>
            <label class="af-menu-select" for="customer_stage">
                <select id="customer_stage" name="stage">
                    <?php // Render this conditional/dynamic template block. ?>
                    <?php foreach ($validStages as $stageValue): ?>
                        <option value="<?php echo htmlspecialchars($stageValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $stage === $stageValue ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(afrisense_customer_stage_label($stageValue), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </label>
            <label class="af-customer-date-filter" for="customer_date">
                <span>Activity Date</span>
                <input type="date" id="customer_date" name="date" value="<?php echo htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?>">
            </label>
            <button type="submit"><i class="bi bi-filter" aria-hidden="true"></i> Filter</button>
            <a href="customers.php"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Reset</a>
        </form>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-customer-date-summary">
            <div>
                <small><?php echo htmlspecialchars(date('l', $selectedTimestamp), ENT_QUOTES, 'UTF-8'); ?></small>
                <strong><?php echo htmlspecialchars(date('d M Y', $selectedTimestamp), ENT_QUOTES, 'UTF-8'); ?></strong>
                <p><?php echo htmlspecialchars((string) $dateCustomers, ENT_QUOTES, 'UTF-8'); ?> customer(s) have activity on this date.</p>
            </div>
            <span class="<?php echo $selectedDate < $todayDate ? 'past' : ($selectedDate > $todayDate ? 'future' : 'today'); ?>">
                <?php echo $selectedDate < $todayDate ? 'Previous Day' : ($selectedDate > $todayDate ? 'Future Booking Date' : 'Today'); ?>
            </span>
        </section>

        <div class="af-menu-table af-customers-table">
            <!-- Table block for displaying structured records. -->
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Orders</th>
                        <th>Served</th>
                        <th>Bookings</th>
                        <th>Next Booking</th>
                        <th>Total Spent</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php // Render this conditional/dynamic template block. ?>
                    <?php if ($customers === []): ?>
                        <tr>
                            <td colspan="8"><div class="af-empty-state">No customers match this date or filter.</div></td>
                        </tr>
                    <?php endif; ?>

                    <?php // Render this conditional/dynamic template block. ?>
                    <?php foreach ($customers as $customer): ?>
                        <?php
                        $nextBookingDate = (string) ($customer['next_booking_date'] ?? '');
                        $customerStatus = (int) ($customer['pending_booking_count'] ?? 0) > 0
                            ? 'Pending'
                            : ((int) ($customer['active_booking_count'] ?? 0) > 0 ? 'Active Booking' : ((int) ($customer['delivered_count'] ?? 0) > 0 ? 'Delivered' : 'Order Placed'));
                        ?>
                        <tr>
                            <td>
                                <div class="af-user-cell">
                                    <span class="af-customer-avatar"><?php echo htmlspecialchars(strtoupper(substr((string) ($customer['fullname'] ?? 'C'), 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="af-user-meta">
                                        <strong><?php echo htmlspecialchars((string) ($customer['fullname'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <small>Joined <?php echo htmlspecialchars(date('d M Y', strtotime((string) ($customer['created_at'] ?? '')) ?: time()), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="af-user-contact">
                                    <strong><?php echo htmlspecialchars((string) ($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo htmlspecialchars((string) ($customer['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars((string) (int) ($customer['order_count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) (int) ($customer['delivered_count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="af-customer-booking-stack">
                                    <strong><?php echo htmlspecialchars((string) (int) ($customer['booking_count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo htmlspecialchars((string) (int) ($customer['pending_booking_count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> pending</small>
                                </span>
                            </td>
                            <td><?php echo $nextBookingDate !== '' ? htmlspecialchars(date('d M Y', strtotime($nextBookingDate) ?: time()), ENT_QUOTES, 'UTF-8') : '<span class="af-muted">None</span>'; ?></td>
                            <td>GHc <?php echo htmlspecialchars(number_format((float) ($customer['total_spent'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="af-customer-status <?php echo htmlspecialchars(afrisense_customer_stage_class($customerStatus), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($customerStatus, ENT_QUOTES, 'UTF-8'); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Page section for this part of the AfriSense interface. -->
    <section class="af-menu-table-card af-customer-activity-card">
        <!-- Header block for this interface section. -->
        <header class="af-table-toolbar">
            <h2>Date Activity</h2>
            <p>Orders and bookings tied to <?php echo htmlspecialchars(date('d M Y', $selectedTimestamp), ENT_QUOTES, 'UTF-8'); ?>.</p>
        </header>
        <div class="af-customer-activity-list">
            <?php // Render this conditional/dynamic template block. ?>
            <?php if ($dateActivities === []): ?>
                <div class="af-empty-state">No customer activity for this date.</div>
            <?php endif; ?>

            <?php // Render this conditional/dynamic template block. ?>
            <?php foreach ($dateActivities as $activity): ?>
                <?php
                $activityTime = strtotime((string) ($activity['activity_time'] ?? '')) ?: time();
                $activityStatus = (string) ($activity['status'] ?? 'Pending');
                ?>
                <article>
                    <span class="af-customer-activity-icon"><i class="bi <?php echo (string) ($activity['activity_type'] ?? '') === 'Booking' ? 'bi-calendar3' : 'bi-cart3'; ?>" aria-hidden="true"></i></span>
                    <div>
                        <strong><?php echo htmlspecialchars((string) ($activity['fullname'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?php echo htmlspecialchars((string) ($activity['activity_type'] ?? 'Activity'), ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars((string) ($activity['item_name'] ?? 'Item'), ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                    <span><?php echo htmlspecialchars(date('h:i A', $activityTime), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="af-customer-status <?php echo htmlspecialchars(afrisense_customer_stage_class($activityStatus), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($activityStatus, ENT_QUOTES, 'UTF-8'); ?></span>
                    <strong>GHc <?php echo htmlspecialchars(number_format((float) ($activity['amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
