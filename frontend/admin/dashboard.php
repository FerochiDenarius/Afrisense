<?php
require_once __DIR__ . '/../auth/auth_bootstrap.php';

$authUser = afrisense_require_admin();
$adminName = (string) ($authUser['fullname'] ?? $authUser['email'] ?? 'Admin User');
$adminRole = ucwords(afrisense_role_name($authUser) ?: 'Staff');

function afrisense_dashboard_count(PDO $pdo, string $table): int
{
    $allowedTables = [
        'orders',
        'bookings',
        'customers',
        'enquiries',
        'notifications',
        'foods',
        'services',
    ];

    if (!in_array($table, $allowedTables, true)) {
        return 0;
    }

    $statement = $pdo->prepare(sprintf('SELECT COUNT(*) AS count_value FROM `%s`', $table));
    $statement->execute();
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['count_value'] ?? 0);
}

function afrisense_dashboard_order_status_count(PDO $pdo, string $status): int
{
    $statement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `orders` WHERE `order_status` = :status');
    $statement->execute(['status' => $status]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['count_value'] ?? 0);
}

function afrisense_dashboard_percent(int $value, int $total): string
{
    if ($total <= 0) {
        return '0%';
    }

    return number_format(($value / $total) * 100, 1) . '%';
}

function afrisense_dashboard_food_image(?string $image): string
{
    $image = trim((string) $image);
    $filename = basename($image);

    if ($image !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
        return '../assets/images/foods/' . $filename;
    }

    return '../assets/images/foods/jollof-rice.png';
}

try {
    $pdo = afrisense_pdo();
    $dashboardError = '';

    $totalOrders = afrisense_dashboard_count($pdo, 'orders');
    $totalBookings = afrisense_dashboard_count($pdo, 'bookings');
    $totalCustomers = afrisense_dashboard_count($pdo, 'customers');
    $totalEnquiries = afrisense_dashboard_count($pdo, 'enquiries');
    $unreadNotifications = 0;

    $notificationStatement = $pdo->prepare(
        'SELECT COUNT(*) AS count_value
         FROM `notifications`
         WHERE `user_id` = :user_id AND `is_read` = 0'
    );
    $notificationStatement->execute(['user_id' => (int) ($authUser['id'] ?? 0)]);
    $unreadNotifications = (int) ($notificationStatement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0);

    $pendingEnquiriesStatement = $pdo->prepare(
        'SELECT COUNT(*) AS count_value
         FROM `enquiries`
         WHERE `status` = :status'
    );
    $pendingEnquiriesStatement->execute(['status' => 'Pending']);
    $pendingEnquiries = (int) ($pendingEnquiriesStatement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0);

    $revenueStatement = $pdo->prepare('SELECT COALESCE(SUM(`total_price`), 0) AS total_value FROM `orders`');
    $revenueStatement->execute();
    $totalRevenue = (float) ($revenueStatement->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0);

    $orderStatusCounts = [
        'Pending' => afrisense_dashboard_order_status_count($pdo, 'Pending'),
        'Confirmed' => afrisense_dashboard_order_status_count($pdo, 'Confirmed'),
        'Preparing' => afrisense_dashboard_order_status_count($pdo, 'Preparing'),
        'Delivered' => afrisense_dashboard_order_status_count($pdo, 'Delivered'),
        'Cancelled' => afrisense_dashboard_order_status_count($pdo, 'Cancelled'),
    ];

    $recentOrdersStatement = $pdo->prepare(
        'SELECT
            o.`id`,
            o.`total_price`,
            o.`order_status`,
            o.`ordered_at`,
            c.`fullname`,
            f.`image`
         FROM `orders` o
         INNER JOIN `customers` c ON c.`id` = o.`customer_id`
         LEFT JOIN `foods` f ON f.`id` = o.`food_id`
         ORDER BY o.`ordered_at` DESC, o.`id` DESC
         LIMIT 5'
    );
    $recentOrdersStatement->execute();
    $recentOrders = $recentOrdersStatement->fetchAll(PDO::FETCH_ASSOC);

    $servicesStatement = $pdo->prepare(
        'SELECT `service_name`, `price`, `availability`
         FROM `services`
         ORDER BY `price` DESC, `id` ASC
         LIMIT 5'
    );
    $servicesStatement->execute();
    $topServices = $servicesStatement->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    $dashboardError = 'Dashboard figures could not be loaded. Check that MySQL is running.';
    $totalOrders = 0;
    $totalBookings = 0;
    $totalCustomers = 0;
    $totalEnquiries = 0;
    $pendingEnquiries = 0;
    $unreadNotifications = 0;
    $totalRevenue = 0.0;
    $orderStatusCounts = ['Pending' => 0, 'Confirmed' => 0, 'Preparing' => 0, 'Delivered' => 0, 'Cancelled' => 0];
    $recentOrders = [];
    $topServices = [];
}

$dateLabel = date('M j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | AfriSense</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <aside class="sidebar" aria-label="Admin navigation">
        <a class="brand" href="index.php" aria-label="AfriSense home">
            <span class="brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
            <span>
                <strong>AfriSense</strong>
                <small>Food Services</small>
            </span>
        </a>

        <nav class="side-nav">
            <a class="active" href="dashboard.php"><i class="bi bi-house-fill" aria-hidden="true"></i> Dashboard</a>

            <p>Management</p>
            <a href="orders.php"><i class="bi bi-box-seam" aria-hidden="true"></i> Orders <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="booking.php"><i class="bi bi-calendar3" aria-hidden="true"></i> Bookings <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="enquiries.php"><i class="bi bi-chat-square-text" aria-hidden="true"></i> Enquiries <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="foods.php"><i class="bi bi-clipboard2" aria-hidden="true"></i> Menu &amp; Packages <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="customers.php"><i class="bi bi-people" aria-hidden="true"></i> Customers <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="users.php"><i class="bi bi-person-badge" aria-hidden="true"></i> Staff Management <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>

            <p>Administration</p>
            <a href="roles.php"><i class="bi bi-person-gear" aria-hidden="true"></i> User Roles <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="roles.php"><i class="bi bi-shield-check" aria-hidden="true"></i> Permissions <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="settings.php"><i class="bi bi-gear" aria-hidden="true"></i> Settings</a>
            <a href="notifications.php"><i class="bi bi-envelope" aria-hidden="true"></i> Email Templates</a>

            <p>Reports</p>
            <a href="reports.php"><i class="bi bi-bar-chart" aria-hidden="true"></i> Analytics <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="reports.php"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Reports <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
        </nav>

        <a class="logout-link" href="../auth/logout.php"><i class="bi bi-box-arrow-left" aria-hidden="true"></i> Logout</a>
    </aside>

    <div class="dashboard-shell">
        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Open navigation">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <strong class="top-title">Dashboard</strong>

            <label class="top-search" for="dashboard_search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="dashboard_search" name="dashboard_search" placeholder="Search anything...">
                <kbd>Ctrl + /</kbd>
            </label>

            <div class="top-actions">
                <button type="button" aria-label="Notifications">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars((string) $unreadNotifications, ENT_QUOTES, 'UTF-8'); ?></span>
                </button>
                <button type="button" aria-label="Messages">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                    <span class="green"><?php echo htmlspecialchars((string) $pendingEnquiries, ENT_QUOTES, 'UTF-8'); ?></span>
                </button>
                <div class="admin-profile">
                    <img src="../assets/images/foodimage.jpeg" alt="">
                    <span>
                        <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?php echo htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8'); ?></small>
                    </span>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </div>
            </div>
        </header>

        <main class="content">
            <section class="page-heading">
                <div>
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?>! Here's today's business summary.</p>
                </div>
                <button type="button" class="date-filter">
                    <i class="bi bi-calendar4-week" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8'); ?>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>
            </section>

            <?php if ($dashboardError !== ''): ?>
                <p class="dashboard-alert"><?php echo htmlspecialchars($dashboardError, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <section class="metric-grid" aria-label="Business summary">
                <article class="metric-card green">
                    <span><i class="bi bi-cart-check" aria-hidden="true"></i></span>
                    <div>
                        <small>Total Orders</small>
                        <strong><?php echo htmlspecialchars((string) $totalOrders, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p><i class="bi bi-cart3" aria-hidden="true"></i> Live</p>
                        <em>from orders table</em>
                    </div>
                </article>

                <article class="metric-card gold">
                    <span><i class="bi bi-calendar-event" aria-hidden="true"></i></span>
                    <div>
                        <small>Total Bookings</small>
                        <strong><?php echo htmlspecialchars((string) $totalBookings, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p><i class="bi bi-calendar3" aria-hidden="true"></i> Live</p>
                        <em>from bookings table</em>
                    </div>
                </article>

                <article class="metric-card blue">
                    <span><i class="bi bi-people" aria-hidden="true"></i></span>
                    <div>
                        <small>Total Customers</small>
                        <strong><?php echo htmlspecialchars((string) $totalCustomers, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p><i class="bi bi-people" aria-hidden="true"></i> Live</p>
                        <em>from customers table</em>
                    </div>
                </article>

                <article class="metric-card purple">
                    <span><i class="bi bi-currency-dollar" aria-hidden="true"></i></span>
                    <div>
                        <small>Total Revenue</small>
                        <strong>GH₵ <?php echo htmlspecialchars(number_format($totalRevenue, 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p><i class="bi bi-cash-stack" aria-hidden="true"></i> Live</p>
                        <em>sum of orders</em>
                    </div>
                </article>
            </section>

            <section class="analytics-grid">
                <article class="panel chart-panel">
                    <header class="panel-header">
                        <h2>Order Statistics</h2>
                        <button type="button">This Week <i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                    </header>
                    <div class="chart-legend" aria-hidden="true">
                        <span><i class="this-week"></i> This Week</span>
                        <span><i class="last-week"></i> Last Week</span>
                    </div>

                    <div class="line-chart" role="img" aria-label="Orders rose from Monday to Thursday before dropping through Sunday.">
                        <svg viewBox="0 0 720 260" focusable="false" aria-hidden="true">
                            <line x1="46" y1="26" x2="46" y2="216"></line>
                            <line x1="46" y1="216" x2="690" y2="216"></line>
                            <line class="grid-line" x1="46" y1="170" x2="690" y2="170"></line>
                            <line class="grid-line" x1="46" y1="124" x2="690" y2="124"></line>
                            <line class="grid-line" x1="46" y1="78" x2="690" y2="78"></line>
                            <line class="grid-line" x1="46" y1="32" x2="690" y2="32"></line>
                            <path class="area" d="M70 166 L170 143 L270 119 L370 74 L470 107 L570 132 L670 155 L670 216 L70 216 Z"></path>
                            <polyline class="last-path" points="70,190 170,174 270,154 370,119 470,150 570,176 670,194"></polyline>
                            <polyline class="current-path" points="70,166 170,143 270,119 370,74 470,107 570,132 670,155"></polyline>
                            <g class="points">
                                <circle cx="70" cy="166" r="5"></circle>
                                <circle cx="170" cy="143" r="5"></circle>
                                <circle cx="270" cy="119" r="5"></circle>
                                <circle cx="370" cy="74" r="5"></circle>
                                <circle cx="470" cy="107" r="5"></circle>
                                <circle cx="570" cy="132" r="5"></circle>
                                <circle cx="670" cy="155" r="5"></circle>
                            </g>
                        </svg>
                        <div class="chart-days" aria-hidden="true">
                            <span>Mon</span>
                            <span>Tue</span>
                            <span>Wed</span>
                            <span>Thu</span>
                            <span>Fri</span>
                            <span>Sat</span>
                            <span>Sun</span>
                        </div>
                    </div>
                </article>

                <article class="panel status-panel">
                    <header class="panel-header">
                        <h2>Orders by Status</h2>
                    </header>
                    <div class="status-content">
                        <div class="donut-chart" role="img" aria-label="<?php echo htmlspecialchars((string) $totalOrders, ENT_QUOTES, 'UTF-8'); ?> total orders by status">
                            <strong><?php echo htmlspecialchars((string) $totalOrders, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span>Total</span>
                        </div>
                        <ul class="status-list">
                            <?php foreach ($orderStatusCounts as $status => $count): ?>
                                <li>
                                    <i class="<?php echo htmlspecialchars(strtolower($status), ENT_QUOTES, 'UTF-8'); ?>"></i>
                                    <span><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <strong><?php echo htmlspecialchars((string) $count, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars(afrisense_dashboard_percent($count, $totalOrders), ENT_QUOTES, 'UTF-8'); ?>)</strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <a class="panel-link" href="orders.php">View all orders <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </article>
            </section>

            <section class="bottom-grid">
                <article class="panel table-panel">
                    <header class="panel-header">
                        <h2>Recent Orders</h2>
                        <a href="orders.php">View All</a>
                    </header>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentOrders === []): ?>
                                    <tr>
                                        <td colspan="5">No recent orders yet.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <?php $orderedAt = strtotime((string) ($order['ordered_at'] ?? '')) ?: time(); ?>
                                    <tr>
                                        <td>#ORD-<?php echo htmlspecialchars(str_pad((string) ($order['id'] ?? 0), 6, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><img src="<?php echo htmlspecialchars(afrisense_dashboard_food_image((string) ($order['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt=""> <?php echo htmlspecialchars((string) ($order['fullname'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars(date('M j, Y', $orderedAt), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>GH₵ <?php echo htmlspecialchars(number_format((float) ($order['total_price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><span class="badge <?php echo htmlspecialchars(strtolower(str_replace(' ', '-', (string) ($order['order_status'] ?? 'Pending'))), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($order['order_status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="panel package-panel">
                    <header class="panel-header">
                        <h2>Available Services</h2>
                        <a href="services.php">View All</a>
                    </header>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($topServices === []): ?>
                                    <tr>
                                        <td colspan="3">No services found.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($topServices as $service): ?>
                                    <tr>
                                        <td><img src="../assets/images/foods/grilled-chicken.png" alt=""> <?php echo htmlspecialchars((string) ($service['service_name'] ?? 'Service'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) ($service['availability'] ?? 'Unavailable'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>GH₵ <?php echo htmlspecialchars(number_format((float) ($service['price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="panel quick-panel">
                    <header class="panel-header">
                        <h2>Quick Actions</h2>
                    </header>
                    <div class="quick-actions">
                        <a href="orders.php"><i class="bi bi-plus-circle-fill green-action" aria-hidden="true"></i> Manage Orders</a>
                        <a href="booking.php"><i class="bi bi-calendar-plus-fill gold-action" aria-hidden="true"></i> Manage Bookings</a>
                        <a href="foods.php"><i class="bi bi-fork-knife green-light-action" aria-hidden="true"></i> Manage Foods Sold</a>
                        <a href="services.php"><i class="bi bi-bag-plus-fill blue-action" aria-hidden="true"></i> Manage Services</a>
                        <a href="users.php"><i class="bi bi-person-plus-fill purple-action" aria-hidden="true"></i> Manage Users</a>
                        <a href="notifications.php"><i class="bi bi-send-fill orange-action" aria-hidden="true"></i> View Notifications</a>
                    </div>
                </article>
            </section>
        </main>
    </div>
</body>
</html>
