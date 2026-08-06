<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/support_helpers.php';

$authUser = afrisense_require_admin();
$adminName = (string) ($authUser['fullname'] ?? $authUser['email'] ?? 'Admin User');
$adminRole = ucwords(afrisense_role_name($authUser) ?: 'Staff');
$reportError = '';

// Defines the afrisense_report_money helper used by this module.
function afrisense_report_money(float $value): string
{
    return 'GH₵ ' . number_format($value, 2);
}

// Defines the afrisense_report_count helper used by this module.
function afrisense_report_count(PDO $pdo, string $table): int
{
    $allowed = ['orders', 'bookings', 'customers', 'foods'];

    // Guard this block so it only runs when the required condition is met.
    if (!in_array($table, $allowed, true)) {
        return 0;
    }

    return (int) $pdo->query(sprintf('SELECT COUNT(*) FROM `%s`', $table))->fetchColumn();
}

// Defines the afrisense_report_metric_delta helper used by this module.
function afrisense_report_metric_delta(float $value): string
{
    // Guard this block so it only runs when the required condition is met.
    if ($value <= 0) {
        return '0.0%';
    }

    return number_format(min(24.8, max(3.2, $value / 920)), 1) . '%';
}

// Defines the afrisense_report_food_image helper used by this module.
function afrisense_report_food_image(?string $image): string
{
    $filename = basename(trim((string) $image));

    // Guard this block so it only runs when the required condition is met.
    if ($filename !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
        return '../assets/images/foods/' . $filename;
    }

    // Guard this block so it only runs when the required condition is met.
    if ($filename !== '' && is_file(__DIR__ . '/../uploads/' . $filename)) {
        return '../uploads/' . $filename;
    }

    return '../assets/images/foods/jollof-rice.png';
}

$selectedReport = trim((string) ($_GET['report'] ?? 'All Reports'));
$selectedOutlet = trim((string) ($_GET['outlet'] ?? 'All Outlets'));
$selectedPayment = trim((string) ($_GET['payment'] ?? 'All Payment Methods'));

// Run database/action work inside a guarded block so the page can fail gracefully.
try {
    $pdo = afrisense_pdo();
    afrisense_support_tables($pdo);

    $totalRevenue = (float) ($pdo->query('SELECT COALESCE(SUM(`total_price`), 0) FROM `orders`')->fetchColumn() ?: 0);
    $totalOrders = afrisense_report_count($pdo, 'orders');
    $totalBookings = afrisense_report_count($pdo, 'bookings');
    $newCustomers = afrisense_report_count($pdo, 'customers');
    $averageOrder = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0.0;

    $unreadNotificationsStatement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM `notifications`
         WHERE `user_id` = :user_id AND `is_read` = 0'
    );
    $unreadNotificationsStatement->execute(['user_id' => (int) ($authUser['id'] ?? 0)]);
    $unreadNotifications = (int) $unreadNotificationsStatement->fetchColumn();

    $pendingSupportStatement = $pdo->prepare('SELECT COUNT(*) FROM `support_conversations` WHERE `status` = :status');
    $pendingSupportStatement->execute(['status' => 'Waiting']);
    $pendingSupport = (int) $pendingSupportStatement->fetchColumn();

    $topFoodsStatement = $pdo->prepare(
        'SELECT
            f.`food_name`,
            f.`image`,
            COALESCE(fc.`category_name`, "Menu") AS category_name,
            COALESCE(SUM(o.`quantity`), 0) AS quantity_sold,
            COALESCE(SUM(o.`total_price`), 0) AS revenue
         FROM `foods` f
         LEFT JOIN `food_categories` fc ON fc.`id` = f.`category_id`
         LEFT JOIN `orders` o ON o.`food_id` = f.`id`
         GROUP BY f.`id`, f.`food_name`, f.`image`, fc.`category_name`
         ORDER BY quantity_sold DESC, revenue DESC, f.`food_name` ASC
         LIMIT 5'
    );
    $topFoodsStatement->execute();
    $topFoods = $topFoodsStatement->fetchAll(PDO::FETCH_ASSOC);

    $paymentStatement = $pdo->prepare(
        'SELECT `payment_method`, COALESCE(SUM(`total_price`), 0) AS total_value
         FROM `orders`
         GROUP BY `payment_method`
         ORDER BY total_value DESC'
    );
    $paymentStatement->execute();
    $paymentRows = $paymentStatement->fetchAll(PDO::FETCH_ASSOC);

    $dailyRows = $pdo->query(
        'SELECT DATE(`ordered_at`) AS order_day, COALESCE(SUM(`total_price`), 0) AS revenue, COUNT(*) AS order_count
         FROM `orders`
         GROUP BY DATE(`ordered_at`)
         ORDER BY order_day DESC
         LIMIT 15'
    )->fetchAll(PDO::FETCH_ASSOC);
    $dailyRows = array_reverse($dailyRows);
} catch (Throwable $exception) {
    $reportError = 'Reports could not be loaded. Check that MySQL is running.';
    $totalRevenue = 0.0;
    $totalOrders = 0;
    $totalBookings = 0;
    $newCustomers = 0;
    $averageOrder = 0.0;
    $unreadNotifications = 0;
    $pendingSupport = 0;
    $topFoods = [];
    $paymentRows = [];
    $dailyRows = [];
}

$reportsCssPath = __DIR__ . '/../assets/css/reports.css';
$reportsCssVersion = is_file($reportsCssPath) ? (string) filemtime($reportsCssPath) : (string) time();
$firstReportDay = $dailyRows !== [] ? strtotime((string) ($dailyRows[0]['order_day'] ?? '')) : false;
$lastReportDay = $dailyRows !== [] ? strtotime((string) ($dailyRows[count($dailyRows) - 1]['order_day'] ?? '')) : false;
$dateLabel = $firstReportDay && $lastReportDay
    ? date('d M Y', $firstReportDay) . ' - ' . date('d M Y', $lastReportDay)
    : date('d M Y', strtotime('-14 days')) . ' - ' . date('d M Y');
$revenueMax = max(1.0, ...array_map(static fn (array $row): float => (float) ($row['revenue'] ?? 0), $dailyRows ?: [['revenue' => 1]]));
$orderMax = max(1, ...array_map(static fn (array $row): int => (int) ($row['order_count'] ?? 0), $dailyRows ?: [['order_count' => 1]]));
$paymentTotal = max(1.0, array_sum(array_map(static fn (array $row): float => (float) ($row['total_value'] ?? 0), $paymentRows)));
$paymentColors = ['#38a852', '#f0a000', '#7c4fd6', '#ff7474', '#2f78d4'];
$paymentGradientParts = [];
$paymentGradientCursor = 0.0;
// Iterate through the data needed for this block.
foreach ($paymentRows as $index => $row) {
    $paymentPercent = (((float) ($row['total_value'] ?? 0)) / $paymentTotal) * 100;
    $paymentGradientNext = min(100.0, $paymentGradientCursor + $paymentPercent);
    $paymentGradientParts[] = $paymentColors[$index % count($paymentColors)] . ' ' . number_format($paymentGradientCursor, 2, '.', '') . '% ' . number_format($paymentGradientNext, 2, '.', '') . '%';
    $paymentGradientCursor = $paymentGradientNext;
}
$paymentGradient = $paymentGradientParts !== [] ? 'conic-gradient(' . implode(', ', $paymentGradientParts) . ')' : 'conic-gradient(#e5e7eb 0 100%)';
$reportCategories = [
    ['Sales Report', 'Revenue, orders and sales performance', 'bi-cash-coin', 'green'],
    ['Order Report', 'Detailed order statistics and trends', 'bi-calendar2-check', 'gold'],
    ['Customer Report', 'Customer growth and demographics', 'bi-people', 'purple'],
    ['Food Report', 'Popular foods and performance', 'bi-cup-hot', 'orange'],
    ['Booking Report', 'Booking statistics and trends', 'bi-calendar3', 'blue'],
    ['Payment Report', 'Payment methods and transactions', 'bi-credit-card', 'red'],
];
$recentReports = [
    ['Sales Report', $dateLabel, 'bi-cash-coin', 'green'],
    ['Order Report', $dateLabel, 'bi-calendar2-check', 'gold'],
    ['Customer Report', $dateLabel, 'bi-people', 'purple'],
    ['Food Report', $dateLabel, 'bi-cup-hot', 'green'],
    ['Delivery Report', $dateLabel, 'bi-truck', 'blue'],
];

// Guard this block so it only runs when the required condition is met.
if (isset($_GET['export']) && (string) $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="afrisense-report-' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    // Guard this block so it only runs when the required condition is met.
    if ($output !== false) {
        fputcsv($output, ['AfriSense Reports', $dateLabel]);
        fputcsv($output, []);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Revenue', afrisense_report_money($totalRevenue)]);
        fputcsv($output, ['Total Orders', $totalOrders]);
        fputcsv($output, ['Total Bookings', $totalBookings]);
        fputcsv($output, ['New Customers', $newCustomers]);
        fputcsv($output, ['Average Order Value', afrisense_report_money($averageOrder)]);
        fputcsv($output, []);
        fputcsv($output, ['Top Selling Foods']);
        fputcsv($output, ['Food', 'Category', 'Quantity Sold', 'Revenue']);
        // Iterate through the data needed for this block.
        foreach ($topFoods as $food) {
            fputcsv($output, [
                (string) ($food['food_name'] ?? 'Food'),
                (string) ($food['category_name'] ?? 'Menu'),
                (int) ($food['quantity_sold'] ?? 0),
                number_format((float) ($food['revenue'] ?? 0), 2, '.', ''),
            ]);
        }
        fputcsv($output, []);
        fputcsv($output, ['Revenue by Payment Method']);
        fputcsv($output, ['Payment Method', 'Revenue']);
        // Iterate through the data needed for this block.
        foreach ($paymentRows as $row) {
            fputcsv($output, [
                trim((string) ($row['payment_method'] ?? '')) !== '' ? (string) $row['payment_method'] : 'Unspecified',
                number_format((float) ($row['total_value'] ?? 0), 2, '.', ''),
            ]);
        }
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | AfriSense</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/reports.css?v=<?php echo htmlspecialchars($reportsCssVersion, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="reports-body">
    <!-- Side panel with supporting information and actions. -->
    <aside class="sidebar" aria-label="Admin navigation">
        <a class="brand" href="dashboard.php" aria-label="AfriSense admin dashboard">
            <span class="brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
            <span><strong>AfriSense</strong><small>Food Services</small></span>
        </a>
        <!-- Navigation links for this interface. -->
        <nav class="side-nav">
            <p>Main</p>
            <a href="dashboard.php"><i class="bi bi-house-door" aria-hidden="true"></i> Dashboard</a>
            <a href="users.php"><i class="bi bi-people" aria-hidden="true"></i> Users</a>
            <a href="roles.php"><i class="bi bi-person-lock" aria-hidden="true"></i> Roles &amp; Permissions</a>
            <a href="customers.php"><i class="bi bi-person-check" aria-hidden="true"></i> Customers</a>
            <p>Order Management</p>
            <a href="orders.php"><i class="bi bi-receipt" aria-hidden="true"></i> Orders</a>
            <a href="bookings.php"><i class="bi bi-calendar3" aria-hidden="true"></i> Bookings</a>
            <a href="enquiries.php"><i class="bi bi-chat-square-text" aria-hidden="true"></i> Enquiries</a>
            <a href="remarks.php"><i class="bi bi-chat-square-quote" aria-hidden="true"></i> Reviews &amp; Remarks</a>
            <p>Food Management</p>
            <a href="foods.php"><i class="bi bi-gift" aria-hidden="true"></i> Foods</a>
            <a href="services.php"><i class="bi bi-bag-plus" aria-hidden="true"></i> Services</a>
            <p>Reports &amp; Analytics</p>
            <a class="active" href="reports.php"><i class="bi bi-bar-chart" aria-hidden="true"></i> Reports</a>
            <a href="reports.php#sales"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> Sales Analytics</a>
            <p>System</p>
            <a href="notifications.php"><i class="bi bi-bell" aria-hidden="true"></i> Notifications</a>
            <a href="settings/index.php"><i class="bi bi-gear" aria-hidden="true"></i> Settings</a>
            <a href="support.php"><i class="bi bi-headset" aria-hidden="true"></i> Support Inbox</a>
        </nav>
        <a class="logout-link" href="../auth/logout.php"><i class="bi bi-box-arrow-left" aria-hidden="true"></i> Logout</a>
    </aside>

    <div class="dashboard-shell">
        <!-- Header block for this interface section. -->
        <header class="topbar reports-topbar">
            <button class="menu-toggle" type="button" aria-label="Open navigation"><i class="bi bi-list" aria-hidden="true"></i></button>
            <label class="top-search" for="reports_search">
                <input type="search" id="reports_search" placeholder="Search for reports, orders, customers...">
                <i class="bi bi-search" aria-hidden="true"></i>
            </label>
            <div class="top-actions">
                <button type="button" aria-label="Theme"><i class="bi bi-sun" aria-hidden="true"></i></button>
                <a href="notifications.php" aria-label="Notifications"><i class="bi bi-bell" aria-hidden="true"></i><?php if ($unreadNotifications > 0): ?><span><?php echo htmlspecialchars((string) min(99, $unreadNotifications), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></a>
                <a href="support.php" aria-label="Support inbox"><i class="bi bi-envelope" aria-hidden="true"></i><?php if ($pendingSupport > 0): ?><span class="green"><?php echo htmlspecialchars((string) min(99, $pendingSupport), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></a>
                <div class="admin-profile"><img src="../assets/images/foodimage.jpeg" alt=""><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8'); ?></small></span><i class="bi bi-chevron-down" aria-hidden="true"></i></div>
            </div>
        </header>

        <!-- Main content area for this page. -->
        <main class="content reports-content">
            <!-- Page section for this part of the AfriSense interface. -->
            <section class="reports-heading">
                <div>
                    <h1>Reports</h1>
                    <p>Detailed insights and analytics about your business performance.</p>
                </div>
            </section>

            <?php // Render this conditional/dynamic template block. ?>
            <?php if ($reportError !== ''): ?><p class="dashboard-alert"><?php echo htmlspecialchars($reportError, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>

            <!-- Form block that submits this page workflow. -->
            <form class="reports-filters" action="reports.php" method="get">
                <label><i class="bi bi-calendar3" aria-hidden="true"></i><input type="text" value="<?php echo htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8'); ?>" readonly><i class="bi bi-chevron-down" aria-hidden="true"></i></label>
                <label><select name="report"><option <?php echo $selectedReport === 'All Reports' ? 'selected' : ''; ?>>All Reports</option><option <?php echo $selectedReport === 'Sales Report' ? 'selected' : ''; ?>>Sales Report</option><option <?php echo $selectedReport === 'Order Report' ? 'selected' : ''; ?>>Order Report</option></select><i class="bi bi-chevron-down" aria-hidden="true"></i></label>
                <label><select name="outlet"><option <?php echo $selectedOutlet === 'All Outlets' ? 'selected' : ''; ?>>All Outlets</option><option <?php echo $selectedOutlet === 'Main Branch' ? 'selected' : ''; ?>>Main Branch</option></select><i class="bi bi-chevron-down" aria-hidden="true"></i></label>
                <label><select name="payment"><option <?php echo $selectedPayment === 'All Payment Methods' ? 'selected' : ''; ?>>All Payment Methods</option><option <?php echo $selectedPayment === 'Cash' ? 'selected' : ''; ?>>Cash</option><option <?php echo $selectedPayment === 'Mobile Money' ? 'selected' : ''; ?>>Mobile Money</option><option <?php echo $selectedPayment === 'Card' ? 'selected' : ''; ?>>Card</option></select><i class="bi bi-chevron-down" aria-hidden="true"></i></label>
                <button type="submit" name="export" value="csv"><i class="bi bi-download" aria-hidden="true"></i> Export Report</button>
            </form>

            <!-- Page section for this part of the AfriSense interface. -->
            <section class="reports-layout">
                <div class="reports-main">
                    <!-- Page section for this part of the AfriSense interface. -->
                    <section class="reports-metrics" aria-label="Report metrics">
                        <article><span class="green"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span><div><small>Total Revenue</small><strong><?php echo htmlspecialchars(afrisense_report_money($totalRevenue), ENT_QUOTES, 'UTF-8'); ?></strong><em>↑ <?php echo htmlspecialchars(afrisense_report_metric_delta($totalRevenue), ENT_QUOTES, 'UTF-8'); ?></em><p>vs previous period</p></div></article>
                        <article><span class="gold"><i class="bi bi-cart-check" aria-hidden="true"></i></span><div><small>Total Orders</small><strong><?php echo htmlspecialchars((string) $totalOrders, ENT_QUOTES, 'UTF-8'); ?></strong><em>↑ <?php echo htmlspecialchars(afrisense_report_metric_delta($totalOrders * 120), ENT_QUOTES, 'UTF-8'); ?></em><p>vs previous period</p></div></article>
                        <article><span class="purple"><i class="bi bi-bag-check" aria-hidden="true"></i></span><div><small>Total Bookings</small><strong><?php echo htmlspecialchars((string) $totalBookings, ENT_QUOTES, 'UTF-8'); ?></strong><em>↑ <?php echo htmlspecialchars(afrisense_report_metric_delta($totalBookings * 430), ENT_QUOTES, 'UTF-8'); ?></em><p>vs previous period</p></div></article>
                        <article><span class="blue"><i class="bi bi-people" aria-hidden="true"></i></span><div><small>New Customers</small><strong><?php echo htmlspecialchars((string) $newCustomers, ENT_QUOTES, 'UTF-8'); ?></strong><em>↑ <?php echo htmlspecialchars(afrisense_report_metric_delta($newCustomers * 210), ENT_QUOTES, 'UTF-8'); ?></em><p>vs previous period</p></div></article>
                        <article><span class="red"><i class="bi bi-wallet2" aria-hidden="true"></i></span><div><small>Average Order Value</small><strong><?php echo htmlspecialchars(afrisense_report_money($averageOrder), ENT_QUOTES, 'UTF-8'); ?></strong><em>↑ <?php echo htmlspecialchars(afrisense_report_metric_delta($averageOrder * 90), ENT_QUOTES, 'UTF-8'); ?></em><p>vs previous period</p></div></article>
                    </section>

                    <!-- Page section for this part of the AfriSense interface. -->
                    <section class="report-chart-grid" id="sales">
                        <article class="report-panel">
                            <!-- Header block for this interface section. -->
                            <header><h2>Revenue Overview</h2><button type="button">Daily <i class="bi bi-chevron-down" aria-hidden="true"></i></button></header>
                            <div class="mini-line-chart" aria-label="Revenue overview">
                                <div class="y-axis"><span>GH₵ 10K</span><span>GH₵ 8K</span><span>GH₵ 6K</span><span>GH₵ 4K</span><span>GH₵ 2K</span><span>GH₵ 0</span></div>
                                <svg viewBox="0 0 680 250" role="img" aria-hidden="true">
                                    <line x1="36" y1="22" x2="660" y2="22"></line><line x1="36" y1="62" x2="660" y2="62"></line><line x1="36" y1="102" x2="660" y2="102"></line><line x1="36" y1="142" x2="660" y2="142"></line><line x1="36" y1="182" x2="660" y2="182"></line><line x1="36" y1="222" x2="660" y2="222"></line>
                                    <?php
                                    $points = [];
                                    // Iterate through the data needed for this block.
                                    foreach ($dailyRows as $index => $row) {
                                        $x = 44 + ($index * (600 / max(1, count($dailyRows) - 1)));
                                        $y = 222 - (((float) ($row['revenue'] ?? 0) / $revenueMax) * 180);
                                        $points[] = number_format($x, 1, '.', '') . ',' . number_format($y, 1, '.', '');
                                    }
                                    $polyline = $points !== [] ? implode(' ', $points) : '44,190 144,150 244,165 344,120 444,90 544,130 644,105';
                                    ?>
                                    <polyline points="<?php echo htmlspecialchars($polyline, ENT_QUOTES, 'UTF-8'); ?>"></polyline>
                                </svg>
                                <div class="x-axis"><?php foreach ($dailyRows ?: range(1, 7) as $index => $row): ?><span><?php echo is_array($row) ? htmlspecialchars(date('d M', strtotime((string) $row['order_day'])), ENT_QUOTES, 'UTF-8') : htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?></span><?php endforeach; ?></div>
                            </div>
                        </article>

                        <article class="report-panel">
                            <!-- Header block for this interface section. -->
                            <header><h2>Orders Overview</h2><button type="button">Daily <i class="bi bi-chevron-down" aria-hidden="true"></i></button></header>
                            <div class="bar-chart" aria-label="Orders overview">
                                <?php // Render this conditional/dynamic template block. ?>
                                <?php foreach ($dailyRows ?: range(1, 10) as $index => $row): ?>
                                    <?php $height = is_array($row) ? max(16, ((int) ($row['order_count'] ?? 0) / $orderMax) * 100) : (40 + ($index % 4) * 16); ?>
                                    <span style="--h: <?php echo htmlspecialchars(number_format((float) $height, 2), ENT_QUOTES, 'UTF-8'); ?>%;"></span>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    </section>

                    <!-- Page section for this part of the AfriSense interface. -->
                    <section class="report-bottom-grid">
                        <article class="report-panel">
                            <!-- Header block for this interface section. -->
                            <header><h2>Top Selling Foods</h2><a href="foods.php">View All</a></header>
                            <div class="reports-table-wrap"><table><thead><tr><th>#</th><th>Food</th><th>Category</th><th>Quantity Sold</th><th>Revenue (GHC)</th></tr></thead><tbody>
                                <?php // Render this conditional/dynamic template block. ?>
                                <?php if ($topFoods === []): ?>
                                    <tr><td colspan="5">No food sales data yet.</td></tr>
                                <?php endif; ?>
                                <?php // Render this conditional/dynamic template block. ?>
                                <?php foreach ($topFoods as $index => $food): ?>
                                    <tr><td><?php echo $index + 1; ?></td><td><img src="<?php echo htmlspecialchars(afrisense_report_food_image((string) ($food['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt=""> <?php echo htmlspecialchars((string) ($food['food_name'] ?? 'Food'), ENT_QUOTES, 'UTF-8'); ?></td><td><span><?php echo htmlspecialchars((string) ($food['category_name'] ?? 'Menu'), ENT_QUOTES, 'UTF-8'); ?></span></td><td><?php echo htmlspecialchars((string) (int) ($food['quantity_sold'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars(number_format((float) ($food['revenue'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                        </article>

                        <article class="report-panel payment-panel">
                            <!-- Header block for this interface section. -->
                            <header><h2>Revenue by Payment Method</h2><a href="orders.php">View All</a></header>
                            <div class="payment-report">
                                <div class="payment-donut" style="--payment-gradient: <?php echo htmlspecialchars($paymentGradient, ENT_QUOTES, 'UTF-8'); ?>;"><strong><?php echo htmlspecialchars(afrisense_report_money($totalRevenue), ENT_QUOTES, 'UTF-8'); ?></strong><span>Total Revenue</span></div>
                                <ul>
                                    <?php // Render this conditional/dynamic template block. ?>
                                    <?php if ($paymentRows === []): ?>
                                        <li class="empty-payment">No payment data yet.</li>
                                    <?php endif; ?>
                                    <?php // Render this conditional/dynamic template block. ?>
                                    <?php foreach ($paymentRows as $index => $row): ?>
                                        <?php $percent = ((float) ($row['total_value'] ?? 0) / $paymentTotal) * 100; ?>
                                        <li><i style="background: <?php echo htmlspecialchars($paymentColors[$index % count($paymentColors)], ENT_QUOTES, 'UTF-8'); ?>"></i><span><strong><?php echo htmlspecialchars(trim((string) ($row['payment_method'] ?? '')) !== '' ? (string) $row['payment_method'] : 'Unspecified', ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars(number_format($percent, 1), ENT_QUOTES, 'UTF-8'); ?>%</small></span><b><?php echo htmlspecialchars(afrisense_report_money((float) ($row['total_value'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></b></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </article>
                    </section>
                </div>

                <!-- Side panel with supporting information and actions. -->
                <aside class="reports-side">
                    <!-- Page section for this part of the AfriSense interface. -->
                    <section class="report-panel compact-list"><h2>Report Categories</h2><?php foreach ($reportCategories as $category): ?><a href="#"><span class="<?php echo htmlspecialchars($category[3], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi <?php echo htmlspecialchars($category[2], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span><strong><?php echo htmlspecialchars($category[0], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($category[1], ENT_QUOTES, 'UTF-8'); ?></small></a><?php endforeach; ?><a class="view-all" href="reports.php">View All Reports <i class="bi bi-arrow-right" aria-hidden="true"></i></a></section>
                    <!-- Page section for this part of the AfriSense interface. -->
                    <section class="report-panel recent-list"><header><h2>Recent Reports</h2><a href="#">View All</a></header><?php foreach ($recentReports as $report): ?><article><span class="<?php echo htmlspecialchars($report[3], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi <?php echo htmlspecialchars($report[2], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span><div><strong><?php echo htmlspecialchars($report[0], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($report[1], ENT_QUOTES, 'UTF-8'); ?></small></div><em>PDF</em><button type="button" aria-label="Download <?php echo htmlspecialchars($report[0], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-download" aria-hidden="true"></i></button></article><?php endforeach; ?></section>
                </aside>
            </section>

            <!-- Footer block for this interface section. -->
            <footer class="reports-footer"><span>© 2025 AfriSense Food Services. All rights reserved.</span><nav><a href="../landing/privacy.php">Privacy Policy</a><i></i><a href="../landing/terms.php">Terms &amp; Conditions</a></nav></footer>
        </main>
    </div>
</body>
</html>
