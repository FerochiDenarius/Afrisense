<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Reviews & Remarks | AfriSense';
$adminTitle = 'Reviews & Remarks';
$activeAdminPage = 'remarks';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
    $frontendBase . '/assets/css/admin-remarks.css',
];
$extraScripts = [$frontendBase . '/assets/js/admin-remarks.js'];

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/remarks_helpers.php';

afrisense_require_admin();

function afrisense_remark_image(string $frontendBase, string $image): string
{
    $filename = basename(str_replace('\\', '/', $image));

    if ($filename !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
        return $frontendBase . '/assets/images/foods/' . $filename;
    }

    return $frontendBase . '/assets/images/foodimage.jpeg';
}

function afrisense_remark_status_class(string $status): string
{
    return match (strtolower($status)) {
        'published' => 'published',
        'rejected' => 'rejected',
        default => 'pending',
    };
}

function afrisense_remark_url(array $params = [], string $anchor = ''): string
{
    $query = http_build_query(array_filter($params, static fn (string $value): bool => $value !== ''));

    return 'remarks.php' . ($query !== '' ? '?' . $query : '') . $anchor;
}

function afrisense_remark_stars(float $rating): string
{
    $html = '<span class="af-remark-stars" aria-label="' . htmlspecialchars(number_format($rating, 1), ENT_QUOTES, 'UTF-8') . ' out of 5">';

    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="bi ' . ($i <= round($rating) ? 'bi-star-fill' : 'bi-star') . '" aria-hidden="true"></i>';
    }

    return $html . '</span>';
}

$remarks = [
    ['id' => 1, 'customer' => 'Kofi Mensah', 'email' => 'kofi.mensah@gmail.com', 'food' => 'Jollof Rice', 'category' => 'Rice Dishes', 'image' => 'jollof-rice.png', 'rating' => 5.0, 'remark' => 'The jollof rice was absolutely delicious! Great taste and generous portion.', 'date' => '2025-05-15 14:30:00', 'status' => 'Published'],
    ['id' => 2, 'customer' => 'Akosua Boateng', 'email' => 'akosua.b@gmail.com', 'food' => 'Grilled Chicken', 'category' => 'Main Dishes', 'image' => 'grilled-chicken.png', 'rating' => 4.0, 'remark' => 'Very well grilled and seasoned. Will order again.', 'date' => '2025-05-14 19:45:00', 'status' => 'Published'],
    ['id' => 3, 'customer' => 'Yaw Addo', 'email' => 'yaw.addo@gmail.com', 'food' => 'Light Soup', 'category' => 'Soups', 'image' => 'light-soup.png', 'rating' => 5.0, 'remark' => 'Fresh ingredients and perfectly prepared.', 'date' => '2025-05-14 13:15:00', 'status' => 'Published'],
    ['id' => 4, 'customer' => 'Ama Serwaa', 'email' => 'ama.serwaa@gmail.com', 'food' => 'Cheese Burger', 'category' => 'Snacks & Sides', 'image' => 'cheese-burger.png', 'rating' => 2.0, 'remark' => 'The burger was good but the delivery was late.', 'date' => '2025-05-13 20:20:00', 'status' => 'Pending'],
    ['id' => 5, 'customer' => 'Kwame Nkrumah', 'email' => 'kwame.nk@gmail.com', 'food' => 'Banku with Tilapia', 'category' => 'Local Dishes', 'image' => 'waakye.png', 'rating' => 5.0, 'remark' => 'Authentic taste! Reminds me of home.', 'date' => '2025-05-13 17:10:00', 'status' => 'Published'],
    ['id' => 6, 'customer' => 'Abena Owusu', 'email' => 'abena.owusu@gmail.com', 'food' => 'Fried Rice', 'category' => 'Rice Dishes', 'image' => 'fried-rice.png', 'rating' => 2.0, 'remark' => 'It was okay, could be better with more veggies.', 'date' => '2025-05-12 11:05:00', 'status' => 'Rejected'],
    ['id' => 7, 'customer' => 'Isaac Asare', 'email' => 'isaac.asare@gmail.com', 'food' => 'Catering Service', 'category' => 'Catering Packages', 'image' => 'foodimage.jpeg', 'rating' => 5.0, 'remark' => 'Excellent service for our event. Everything was perfect!', 'date' => '2025-05-11 09:30:00', 'status' => 'Published'],
];

try {
    $pdo = afrisense_pdo();
    afrisense_remarks_seed_samples($pdo);
    $dbRemarks = afrisense_remarks_fetch($pdo, [], 128, false);
    $remarks = array_map(static fn (array $remark): array => [
        'id' => (int) ($remark['id'] ?? 0),
        'customer' => (string) ($remark['customer_name'] ?? 'Customer'),
        'email' => (string) ($remark['email'] ?? ''),
        'food' => (string) ($remark['food_service'] ?? 'AfriSense'),
        'category' => (string) ($remark['category'] ?? 'Customer Feedback'),
        'image' => (string) ($remark['image'] ?? ''),
        'rating' => (float) ($remark['rating'] ?? 0),
        'remark' => (string) ($remark['remark'] ?? ''),
        'date' => (string) ($remark['created_at'] ?? date('Y-m-d H:i:s')),
        'status' => (string) ($remark['status'] ?? 'Pending'),
    ], $dbRemarks);
} catch (Throwable) {
    // Keep the representative remarks above when the database is unavailable.
}

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'rating' => trim((string) ($_GET['rating'] ?? '')),
    'food' => trim((string) ($_GET['food'] ?? '')),
];
$itemsPerPage = min(8, afrisense_admin_items_per_page());
$page = max(1, (int) ($_GET['page'] ?? 1));
$validStatuses = ['Published', 'Pending', 'Rejected'];
$foodOptions = array_values(array_unique(array_map(static fn (array $remark): string => (string) $remark['food'], $remarks)));
$filteredRemarks = array_values(array_filter($remarks, static function (array $remark) use ($filters, $validStatuses): bool {
    if ($filters['status'] !== '' && in_array($filters['status'], $validStatuses, true) && $remark['status'] !== $filters['status']) {
        return false;
    }

    if ($filters['rating'] !== '' && (int) $filters['rating'] > 0 && (int) round((float) $remark['rating']) !== (int) $filters['rating']) {
        return false;
    }

    if ($filters['food'] !== '' && $remark['food'] !== $filters['food']) {
        return false;
    }

    if ($filters['search'] !== '') {
        $haystack = strtolower(implode(' ', [$remark['customer'], $remark['email'], $remark['food'], $remark['category'], $remark['remark']]));

        return str_contains($haystack, strtolower($filters['search']));
    }

    return true;
}));

$totalFilteredRemarks = count($filteredRemarks);
$totalPages = max(1, (int) ceil($totalFilteredRemarks / max(1, $itemsPerPage)));
$page = min($page, $totalPages);
$offset = ($page - 1) * $itemsPerPage;
$paginatedRemarks = array_slice($filteredRemarks, $offset, $itemsPerPage);

$selectedRemark = null;
$selectedRemarkId = (int) ($_GET['view'] ?? 0);
foreach ($remarks as $remark) {
    if ((int) $remark['id'] === $selectedRemarkId) {
        $selectedRemark = $remark;
        break;
    }
}

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="afrisense-remarks.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['#', 'Customer', 'Email', 'Food / Service', 'Category', 'Rating', 'Remark', 'Date', 'Status']);

    foreach ($filteredRemarks as $remark) {
        fputcsv($output, [
            $remark['id'],
            $remark['customer'],
            $remark['email'],
            $remark['food'],
            $remark['category'],
            $remark['rating'],
            $remark['remark'],
            date('d M Y h:i A', strtotime((string) $remark['date']) ?: time()),
            $remark['status'],
        ]);
    }

    fclose($output);
    exit;
}

$metricTotals = [
    'total' => 128,
    'average' => 4.6,
    'positive' => 98,
    'positive_rate' => '76.6%',
    'negative' => 14,
    'negative_rate' => '10.9%',
    'pending' => 16,
    'pending_rate' => '12.5%',
];
$ratingOverview = [
    5 => ['count' => 84, 'rate' => '65.6%'],
    4 => ['count' => 28, 'rate' => '21.9%'],
    3 => ['count' => 8, 'rate' => '6.3%'],
    2 => ['count' => 5, 'rate' => '3.9%'],
    1 => ['count' => 3, 'rate' => '2.3%'],
];
$topFoods = [
    ['name' => 'Jollof Rice', 'rating' => 4.8, 'image' => 'jollof-rice.png'],
    ['name' => 'Grilled Chicken', 'rating' => 4.7, 'image' => 'grilled-chicken.png'],
    ['name' => 'Banku with Tilapia', 'rating' => 4.7, 'image' => 'waakye.png'],
    ['name' => 'Light Soup', 'rating' => 4.6, 'image' => 'light-soup.png'],
    ['name' => 'Fried Rice', 'rating' => 4.5, 'image' => 'fried-rice.png'],
];

ob_start();
?>
<section class="af-admin-menu-page af-remarks-page">
    <header class="af-admin-page-heading af-remarks-heading">
        <div>
            <h1>Reviews &amp; Remarks</h1>
            <p>Manage customer feedback, ratings and remarks about our foods and services.</p>
        </div>
        <a class="af-add-menu-btn af-remark-add-btn" href="<?php echo htmlspecialchars($frontendBase . '/landing/remarks.php#give-remark', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
            Public Remark Form
        </a>
    </header>

    <section class="af-remark-metrics" aria-label="Review summary">
        <article class="green"><span><i class="bi bi-chat-square-text" aria-hidden="true"></i></span><div><small>Total Remarks</small><strong><?php echo htmlspecialchars((string) $metricTotals['total'], ENT_QUOTES, 'UTF-8'); ?></strong><p>All time reviews</p></div></article>
        <article class="gold"><span><i class="bi bi-star" aria-hidden="true"></i></span><div><small>Average Rating</small><strong><?php echo htmlspecialchars(number_format((float) $metricTotals['average'], 1), ENT_QUOTES, 'UTF-8'); ?></strong><?php echo afrisense_remark_stars((float) $metricTotals['average']); ?></div></article>
        <article class="green"><span><i class="bi bi-hand-thumbs-up" aria-hidden="true"></i></span><div><small>Positive</small><strong><?php echo htmlspecialchars((string) $metricTotals['positive'], ENT_QUOTES, 'UTF-8'); ?></strong><p><?php echo htmlspecialchars($metricTotals['positive_rate'], ENT_QUOTES, 'UTF-8'); ?></p></div></article>
        <article class="red"><span><i class="bi bi-hand-thumbs-down" aria-hidden="true"></i></span><div><small>Negative</small><strong><?php echo htmlspecialchars((string) $metricTotals['negative'], ENT_QUOTES, 'UTF-8'); ?></strong><p><?php echo htmlspecialchars($metricTotals['negative_rate'], ENT_QUOTES, 'UTF-8'); ?></p></div></article>
        <article class="blue"><span><i class="bi bi-chat-square" aria-hidden="true"></i></span><div><small>Pending</small><strong><?php echo htmlspecialchars((string) $metricTotals['pending'], ENT_QUOTES, 'UTF-8'); ?></strong><p><?php echo htmlspecialchars($metricTotals['pending_rate'], ENT_QUOTES, 'UTF-8'); ?></p></div></article>
    </section>

    <section class="af-remarks-workspace">
        <section class="af-menu-table-card af-remarks-table-card" id="remarks-table">
            <form class="af-remarks-filters" action="remarks.php" method="get">
                <label class="af-menu-search" for="remark_search">
                    <input type="search" id="remark_search" name="search" value="<?php echo htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search reviews, customers, foods...">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </label>
                <label class="af-menu-select" for="remark_status">
                    <select id="remark_status" name="status">
                        <option value="">All Status</option>
                        <?php foreach ($validStatuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filters['status'] === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
                <label class="af-menu-select" for="remark_rating">
                    <select id="remark_rating" name="rating">
                        <option value="">All Ratings</option>
                        <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                            <option value="<?php echo $rating; ?>" <?php echo $filters['rating'] === (string) $rating ? 'selected' : ''; ?>><?php echo $rating; ?> Stars</option>
                        <?php endfor; ?>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
                <label class="af-menu-select" for="remark_food">
                    <select id="remark_food" name="food">
                        <option value="">All Foods/Services</option>
                        <?php foreach ($foodOptions as $foodOption): ?>
                            <option value="<?php echo htmlspecialchars($foodOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filters['food'] === $foodOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($foodOption, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
                <button type="submit"><i class="bi bi-filter" aria-hidden="true"></i> Filter</button>
                <a href="<?php echo htmlspecialchars(afrisense_remark_url(array_merge($filters, ['export' => 'csv'])), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-download" aria-hidden="true"></i> Export</a>
            </form>

            <div class="af-menu-table af-remarks-table">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Food / Service</th>
                            <th>Rating</th>
                            <th>Remark</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($paginatedRemarks === []): ?>
                            <tr><td colspan="8"><div class="af-empty-state">No remarks found.</div></td></tr>
                        <?php endif; ?>
                        <?php foreach ($paginatedRemarks as $index => $remark): ?>
                            <?php $date = strtotime((string) $remark['date']) ?: time(); ?>
                            <tr id="remark-row-<?php echo htmlspecialchars((string) $remark['id'], ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo (int) $remark['id'] === $selectedRemarkId ? 'is-selected' : ''; ?>">
                                <td><?php echo htmlspecialchars((string) ($offset + $index + 1), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="af-remark-customer">
                                        <em><?php echo htmlspecialchars(strtoupper(substr((string) $remark['customer'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></em>
                                        <span><strong><?php echo htmlspecialchars((string) $remark['customer'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars((string) $remark['email'], ENT_QUOTES, 'UTF-8'); ?></small></span>
                                    </span>
                                </td>
                                <td>
                                    <span class="af-remark-food">
                                        <img src="<?php echo htmlspecialchars(afrisense_remark_image($frontendBase, (string) $remark['image']), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                                        <span><strong><?php echo htmlspecialchars((string) $remark['food'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars((string) $remark['category'], ENT_QUOTES, 'UTF-8'); ?></small></span>
                                    </span>
                                </td>
                                <td><?php echo afrisense_remark_stars((float) $remark['rating']); ?></td>
                                <td><p class="af-remark-text"><?php echo htmlspecialchars((string) $remark['remark'], ENT_QUOTES, 'UTF-8'); ?></p></td>
                                <td><span class="af-remark-date"><?php echo htmlspecialchars(date('d M Y', $date), ENT_QUOTES, 'UTF-8'); ?><small><?php echo htmlspecialchars(date('h:i A', $date), ENT_QUOTES, 'UTF-8'); ?></small></span></td>
                                <td><span class="af-remark-status <?php echo htmlspecialchars(afrisense_remark_status_class((string) $remark['status']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $remark['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td>
                                    <div class="af-row-actions af-remark-actions">
                                        <a href="<?php echo htmlspecialchars(afrisense_remark_url(array_merge($filters, ['view' => (string) $remark['id']]), '#remark-row-' . (int) $remark['id']), ENT_QUOTES, 'UTF-8'); ?>" title="View remark details" aria-label="View remark"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                        <a href="mailto:<?php echo htmlspecialchars((string) $remark['email'], ENT_QUOTES, 'UTF-8'); ?>?subject=AfriSense%20review%20response" title="Reply to customer" aria-label="Reply to customer"><i class="bi bi-reply" aria-hidden="true"></i></a>
                                        <a href="<?php echo htmlspecialchars(afrisense_remark_url(['status' => (string) $remark['status']], '#remarks-table'), ENT_QUOTES, 'UTF-8'); ?>" title="More remark actions" aria-label="More remark actions"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <footer class="af-menu-pagination af-remarks-pagination">
                <p>Showing <?php echo htmlspecialchars((string) ($totalFilteredRemarks > 0 ? $offset + 1 : 0), ENT_QUOTES, 'UTF-8'); ?> to <?php echo htmlspecialchars((string) min($offset + count($paginatedRemarks), $totalFilteredRemarks), ENT_QUOTES, 'UTF-8'); ?> of <?php echo htmlspecialchars((string) $totalFilteredRemarks, ENT_QUOTES, 'UTF-8'); ?> remarks</p>
                <nav aria-label="Remarks pagination">
                    <a class="<?php echo $page <= 1 ? 'is-disabled' : ''; ?>" href="<?php echo htmlspecialchars($page <= 1 ? '#' : afrisense_remark_url(array_merge($filters, ['page' => (string) ($page - 1)]), '#remarks-table'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Previous page" title="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                    <?php for ($number = max(1, $page - 1); $number <= min($totalPages, $page + 1); $number++): ?>
                        <a class="<?php echo $number === $page ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(afrisense_remark_url(array_merge($filters, ['page' => (string) $number]), '#remarks-table'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $number, ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endfor; ?>
                    <?php if ($totalPages > $page + 1): ?>
                        <span>...</span>
                        <a href="<?php echo htmlspecialchars(afrisense_remark_url(array_merge($filters, ['page' => (string) $totalPages]), '#remarks-table'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $totalPages, ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endif; ?>
                    <a class="<?php echo $page >= $totalPages ? 'is-disabled' : ''; ?>" href="<?php echo htmlspecialchars($page >= $totalPages ? '#' : afrisense_remark_url(array_merge($filters, ['page' => (string) ($page + 1)]), '#remarks-table'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Next page" title="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                </nav>
            </footer>
        </section>

        <aside class="af-remarks-side">
            <section class="af-menu-panel af-rating-card">
                <h2>Rating Overview</h2>
                <div class="af-rating-overview">
                    <div class="af-rating-donut"><strong>4.6</strong><small>Average</small></div>
                    <ul>
                        <?php foreach ($ratingOverview as $stars => $data): ?>
                            <li><span class="tone-<?php echo htmlspecialchars((string) $stars, ENT_QUOTES, 'UTF-8'); ?>"></span><?php echo htmlspecialchars((string) $stars, ENT_QUOTES, 'UTF-8'); ?> Stars <strong><?php echo htmlspecialchars((string) $data['count'] . ' (' . $data['rate'] . ')', ENT_QUOTES, 'UTF-8'); ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

            <section class="af-menu-panel af-top-foods-card">
                <h2>Top Rated Foods</h2>
                <ul>
                    <?php foreach ($topFoods as $food): ?>
                        <li>
                            <img src="<?php echo htmlspecialchars(afrisense_remark_image($frontendBase, (string) $food['image']), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                            <strong><?php echo htmlspecialchars((string) $food['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?php echo htmlspecialchars(number_format((float) $food['rating'], 1), ENT_QUOTES, 'UTF-8'); ?> <i class="bi bi-star-fill" aria-hidden="true"></i></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo htmlspecialchars(afrisense_remark_url([], '#remarks-table'), ENT_QUOTES, 'UTF-8'); ?>">View All</a>
            </section>

            <section class="af-menu-panel af-quick-actions-card">
                <h2>Quick Actions</h2>
                <a href="<?php echo htmlspecialchars(afrisense_remark_url(['status' => 'Published'], '#remarks-table'), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-chat-square-text" aria-hidden="true"></i> All Published Reviews</a>
                <a href="<?php echo htmlspecialchars(afrisense_remark_url(['status' => 'Pending'], '#remarks-table'), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-clock" aria-hidden="true"></i> Pending Reviews <span>16</span></a>
                <a href="<?php echo htmlspecialchars(afrisense_remark_url(['status' => 'Rejected'], '#remarks-table'), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-x-octagon" aria-hidden="true"></i> Rejected Reviews <span>4</span></a>
                <a href="<?php echo htmlspecialchars($frontendBase . '/landing/remarks.php#give-remark', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Open Public Remark Form</a>
            </section>

            <?php if ($selectedRemark !== null): ?>
                <section class="af-menu-panel af-selected-remark">
                    <h2>Selected Remark</h2>
                    <strong><?php echo htmlspecialchars((string) $selectedRemark['customer'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small><?php echo htmlspecialchars((string) $selectedRemark['food'] . ' · ' . $selectedRemark['status'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php echo afrisense_remark_stars((float) $selectedRemark['rating']); ?>
                    <p><?php echo htmlspecialchars((string) $selectedRemark['remark'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <a href="mailto:<?php echo htmlspecialchars((string) $selectedRemark['email'], ENT_QUOTES, 'UTF-8'); ?>?subject=AfriSense%20review%20response">Reply by Email</a>
                </section>
            <?php endif; ?>

        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
