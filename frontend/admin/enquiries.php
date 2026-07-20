<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Enquiries Management | AfriSense';
$adminTitle = 'Enquiries';
$activeAdminPage = 'enquiries';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];
$extraScripts = [$frontendBase . '/assets/js/admin-enquiries.js'];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

afrisense_require_admin();

/**
 * @return array<string, string>
 */
function afrisense_enquiry_statuses(): array
{
    return [
        'Pending' => 'Unread',
        'Read' => 'Reading',
        'Replied' => 'Replied',
        'Closed' => 'Closed',
    ];
}

function afrisense_enquiry_status_class(string $status): string
{
    return match (strtolower($status)) {
        'read' => 'read',
        'replied' => 'replied',
        'closed' => 'closed',
        default => 'pending',
    };
}

function afrisense_enquiry_status_label(string $status): string
{
    $statuses = afrisense_enquiry_statuses();

    return $statuses[$status] ?? $status;
}

function afrisense_enquiry_type(string $subject): string
{
    $subject = strtolower($subject);

    return match (true) {
        str_contains($subject, 'cater'), str_contains($subject, 'event'), str_contains($subject, 'wedding') => 'Catering & Events',
        str_contains($subject, 'order'), str_contains($subject, 'delivery'), str_contains($subject, 'payment') => 'Orders & Delivery',
        str_contains($subject, 'book'), str_contains($subject, 'reservation') => 'Booking Support',
        str_contains($subject, 'support'), str_contains($subject, 'issue') => 'Customer Support',
        default => 'General Enquiry',
    };
}

function afrisense_enquiry_type_class(string $type): string
{
    return match ($type) {
        'Catering & Events' => 'catering',
        'Orders & Delivery' => 'orders',
        'Booking Support', 'Customer Support' => 'support',
        default => 'general',
    };
}

/**
 * @return array<string, string>
 */
function afrisense_enquiry_filters(): array
{
    $statusAliases = [
        'Unread' => 'Pending',
        'Reading' => 'Read',
        'Resolved' => 'Closed',
    ];
    $status = (string) ($_GET['status'] ?? '');
    $status = $statusAliases[$status] ?? $status;

    return [
        'search' => trim((string) ($_GET['search'] ?? '')),
        'email' => trim((string) ($_GET['email'] ?? '')),
        'phone' => trim((string) ($_GET['phone'] ?? '')),
        'subject' => trim((string) ($_GET['subject'] ?? '')),
        'type' => trim((string) ($_GET['type'] ?? '')),
        'status' => $status,
        'date_from' => trim((string) ($_GET['date_from'] ?? '')),
        'sort' => trim((string) ($_GET['sort'] ?? 'newest')),
    ];
}

/**
 * @param array<string, string|null> $overrides
 */
function afrisense_enquiry_url(array $filters, array $overrides = [], string $anchor = ''): string
{
    $params = $filters;

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
            continue;
        }

        $params[$key] = $value;
    }

    $params = array_filter(
        $params,
        static fn (string $value): bool => $value !== '' && $value !== 'newest'
    );
    $query = http_build_query($params);

    return 'enquiries.php' . ($query !== '' ? '?' . $query : '') . $anchor;
}

/**
 * @return array{0: string, 1: array<string, mixed>}
 */
function afrisense_enquiry_where_sql(array $filters): array
{
    $where = [];
    $params = [];
    $validStatuses = array_keys(afrisense_enquiry_statuses());

    if (in_array($filters['status'], $validStatuses, true)) {
        $where[] = 'e.`status` = :status';
        $params['status'] = $filters['status'];
    }

    if ($filters['search'] !== '') {
        $where[] = '(
            CAST(e.`id` AS CHAR) LIKE :search
            OR COALESCE(c.`fullname`, "") LIKE :search
            OR COALESCE(c.`email`, "") LIKE :search
            OR COALESCE(c.`phone_number`, "") LIKE :search
            OR e.`subject` LIKE :search
            OR e.`message` LIKE :search
        )';
        $params['search'] = '%' . $filters['search'] . '%';
    }

    if ($filters['email'] !== '') {
        $where[] = 'COALESCE(c.`email`, "") LIKE :email';
        $params['email'] = '%' . $filters['email'] . '%';
    }

    if ($filters['phone'] !== '') {
        $where[] = 'COALESCE(c.`phone_number`, "") LIKE :phone';
        $params['phone'] = '%' . preg_replace('/\s+/', '', $filters['phone']) . '%';
    }

    if ($filters['subject'] !== '') {
        $where[] = 'e.`subject` LIKE :subject';
        $params['subject'] = '%' . $filters['subject'] . '%';
    }

    if ($filters['date_from'] !== '') {
        $where[] = 'DATE(e.`created_at`) = :date_from';
        $params['date_from'] = $filters['date_from'];
    }

    if ($filters['type'] !== '') {
        $type = strtolower($filters['type']);
        $typePatterns = match ($type) {
            'catering' => ['cater', 'event', 'wedding'],
            'orders' => ['order', 'delivery', 'payment'],
            'support' => ['support', 'issue', 'book', 'reservation'],
            'general' => ['general'],
            default => [],
        };

        if ($typePatterns !== []) {
            $typeParts = [];
            foreach ($typePatterns as $index => $pattern) {
                $key = 'type_' . $index;
                $typeParts[] = 'LOWER(e.`subject`) LIKE :' . $key;
                $params[$key] = '%' . $pattern . '%';
            }

            $where[] = '(' . implode(' OR ', $typeParts) . ')';
        }
    }

    return [$where !== [] ? 'WHERE ' . implode(' AND ', $where) : '', $params];
}

function afrisense_count_enquiries(PDO $pdo, ?string $status = null): int
{
    if ($status === null) {
        $statement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `enquiries`');
        $statement->execute();
    } else {
        $statement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `enquiries` WHERE `status` = :status');
        $statement->execute(['status' => $status]);
    }

    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['count_value'] ?? 0);
}

/**
 * @return array<string, mixed>|null
 */
function afrisense_find_enquiry(PDO $pdo, int $id): ?array
{
    $statement = $pdo->prepare(
        'SELECT
            e.`id`,
            e.`customer_id`,
            e.`subject`,
            e.`message`,
            e.`status`,
            e.`admin_response`,
            e.`created_at`,
            e.`updated_at`,
            COALESCE(c.`fullname`, "Unknown Customer") AS fullname,
            COALESCE(c.`email`, "") AS email,
            COALESCE(c.`phone_number`, "") AS phone_number
         FROM `enquiries` e
         LEFT JOIN `customers` c ON c.`id` = e.`customer_id`
         WHERE e.`id` = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $id]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function afrisense_redirect_enquiries(array $filters, int $viewId = 0): never
{
    $overrides = $viewId > 0 ? ['view' => (string) $viewId] : ['view' => null];
    $anchor = $viewId > 0 ? '#enquiry-row-' . $viewId : '#enquiries-table';

    header('Location: ' . afrisense_enquiry_url($filters, $overrides, $anchor));
    exit;
}

$filters = afrisense_enquiry_filters();
$validStatuses = array_keys(afrisense_enquiry_statuses());
$viewEnquiryId = (int) ($_GET['view'] ?? 0);
$loadError = '';
$flash = afrisense_flash_get();

try {
    $pdo = afrisense_pdo();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $enquiryId = (int) ($_POST['enquiry_id'] ?? 0);

        if ($action === 'update_status') {
            $nextStatus = (string) ($_POST['status'] ?? '');

            if ($enquiryId <= 0 || !in_array($nextStatus, $validStatuses, true)) {
                afrisense_flash_set('error', 'Enquiry status could not be updated.');
                afrisense_redirect_enquiries($filters, $enquiryId);
            }

            $update = $pdo->prepare(
                'UPDATE `enquiries`
                 SET `status` = :status,
                     `updated_at` = NOW()
                 WHERE `id` = :id'
            );
            $update->execute([
                'status' => $nextStatus,
                'id' => $enquiryId,
            ]);

            afrisense_flash_set(
                'success',
                'ENQ-' . str_pad((string) $enquiryId, 5, '0', STR_PAD_LEFT) . ' updated to ' . afrisense_enquiry_status_label($nextStatus) . '.'
            );
            afrisense_redirect_enquiries($filters, $enquiryId);
        }

        if ($action === 'save_response') {
            $response = trim((string) ($_POST['admin_response'] ?? ''));

            if ($enquiryId <= 0 || $response === '') {
                afrisense_flash_set('error', 'Enter a response before saving.');
                afrisense_redirect_enquiries($filters, $enquiryId);
            }

            $update = $pdo->prepare(
                'UPDATE `enquiries`
                 SET `admin_response` = :admin_response,
                     `status` = :status,
                     `updated_at` = NOW()
                 WHERE `id` = :id'
            );
            $update->execute([
                'admin_response' => $response,
                'status' => 'Replied',
                'id' => $enquiryId,
            ]);

            afrisense_flash_set('success', 'Reply saved and enquiry marked as replied.');
            afrisense_redirect_enquiries($filters, $enquiryId);
        }

        if ($action === 'bulk_update_status') {
            $nextStatus = (string) ($_POST['bulk_status'] ?? '');
            $ids = array_values(array_filter(array_map('intval', (array) ($_POST['selected_enquiries'] ?? []))));

            if ($ids === [] || !in_array($nextStatus, $validStatuses, true)) {
                afrisense_flash_set('error', 'Select at least one enquiry and a valid status.');
                afrisense_redirect_enquiries($filters);
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $update = $pdo->prepare('UPDATE `enquiries` SET `status` = ?, `updated_at` = NOW() WHERE `id` IN (' . $placeholders . ')');
            $update->execute(array_merge([$nextStatus], $ids));

            afrisense_flash_set('success', count($ids) . ' enquiry record(s) updated.');
            afrisense_redirect_enquiries($filters, $ids[0] ?? 0);
        }

        if ($action === 'delete_enquiry') {
            if ($enquiryId <= 0) {
                afrisense_flash_set('error', 'Enquiry could not be deleted.');
                afrisense_redirect_enquiries($filters);
            }

            $delete = $pdo->prepare('DELETE FROM `enquiries` WHERE `id` = :id');
            $delete->execute(['id' => $enquiryId]);

            afrisense_flash_set('success', 'Enquiry deleted.');
            afrisense_redirect_enquiries($filters);
        }
    }

    [$whereSql, $params] = afrisense_enquiry_where_sql($filters);
    $orderSql = $filters['sort'] === 'oldest'
        ? 'ORDER BY e.`created_at` ASC, e.`id` ASC'
        : 'ORDER BY e.`created_at` DESC, e.`id` DESC';

    $statement = $pdo->prepare(
        'SELECT
            e.`id`,
            e.`customer_id`,
            e.`subject`,
            e.`message`,
            e.`status`,
            e.`admin_response`,
            e.`created_at`,
            e.`updated_at`,
            COALESCE(c.`fullname`, "Unknown Customer") AS fullname,
            COALESCE(c.`email`, "") AS email,
            COALESCE(c.`phone_number`, "") AS phone_number
         FROM `enquiries` e
         LEFT JOIN `customers` c ON c.`id` = e.`customer_id`
         ' . $whereSql . '
         ' . $orderSql . '
         LIMIT 25'
    );
    $statement->execute($params);
    $enquiries = $statement->fetchAll(PDO::FETCH_ASSOC);

    $selectedEnquiry = $viewEnquiryId > 0 ? afrisense_find_enquiry($pdo, $viewEnquiryId) : null;
    $totalEnquiries = afrisense_count_enquiries($pdo);
    $pendingEnquiries = afrisense_count_enquiries($pdo, 'Pending');
    $readEnquiries = afrisense_count_enquiries($pdo, 'Read');
    $repliedEnquiries = afrisense_count_enquiries($pdo, 'Replied');
    $closedEnquiries = afrisense_count_enquiries($pdo, 'Closed');
} catch (Throwable $exception) {
    $enquiries = [];
    $selectedEnquiry = null;
    $totalEnquiries = 0;
    $pendingEnquiries = 0;
    $readEnquiries = 0;
    $repliedEnquiries = 0;
    $closedEnquiries = 0;
    $loadError = 'Enquiries could not be loaded. Check that MySQL is running and the enquiries table exists.';
}

$selectedEnquiryId = (int) ($selectedEnquiry['id'] ?? 0);
$statusCounts = [
    '' => $totalEnquiries,
    'Pending' => $pendingEnquiries,
    'Read' => $readEnquiries,
    'Replied' => $repliedEnquiries,
    'Closed' => $closedEnquiries,
];

ob_start();
?>
<section class="af-admin-menu-page af-enquiries-management-page">
    <header class="af-admin-page-heading af-enquiries-heading">
        <div>
            <h1>Enquiries Management</h1>
            <p>View, manage and respond to all customer enquiries from one place.</p>
        </div>
        <div class="af-enquiries-header-actions">
            <button type="button"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Export PDF</button>
            <button type="button"><i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Export Excel</button>
            <button type="button"><i class="bi bi-printer" aria-hidden="true"></i> Print</button>
            <a class="af-add-menu-btn" href="<?php echo htmlspecialchars($selectedEnquiryId > 0 ? '#enquiry-reply-' . $selectedEnquiryId : '#enquiries-table', ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi bi-send" aria-hidden="true"></i>
                Compose Reply
            </a>
        </div>
    </header>

    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($flash !== null): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars((string) ($flash['type'] ?? 'success'), ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section class="af-menu-table-card af-enquiries-filter-card">
        <form class="af-enquiries-filters" action="enquiries.php" method="get">
            <label>
                <span>Search Enquiry</span>
                <div class="af-enquiry-input-icon">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" name="search" value="<?php echo htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by ID, email, phone, subject...">
                </div>
            </label>
            <label>
                <span>Email</span>
                <input type="search" name="email" value="<?php echo htmlspecialchars($filters['email'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Select email">
            </label>
            <label>
                <span>Phone Number</span>
                <input type="search" name="phone" value="<?php echo htmlspecialchars($filters['phone'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter phone number">
            </label>
            <label>
                <span>Subject</span>
                <input type="search" name="subject" value="<?php echo htmlspecialchars($filters['subject'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Select subject">
            </label>
            <label>
                <span>Enquiry Type</span>
                <select name="type">
                    <option value="">All Types</option>
                    <option value="general" <?php echo $filters['type'] === 'general' ? 'selected' : ''; ?>>General Enquiry</option>
                    <option value="orders" <?php echo $filters['type'] === 'orders' ? 'selected' : ''; ?>>Orders &amp; Delivery</option>
                    <option value="catering" <?php echo $filters['type'] === 'catering' ? 'selected' : ''; ?>>Catering &amp; Events</option>
                    <option value="support" <?php echo $filters['type'] === 'support' ? 'selected' : ''; ?>>Support</option>
                </select>
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="">All Status</option>
                    <?php foreach (afrisense_enquiry_statuses() as $statusValue => $statusLabel): ?>
                        <option value="<?php echo htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filters['status'] === $statusValue ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Date Range</span>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8'); ?>">
            </label>
            <div class="af-enquiries-filter-actions">
                <button type="submit">Search</button>
                <a href="enquiries.php">Reset</a>
            </div>
        </form>
    </section>

    <nav class="af-enquiry-tabs" aria-label="Enquiry status filters">
        <?php foreach (['' => 'All Enquiries', 'Pending' => 'Unread', 'Read' => 'Reading', 'Replied' => 'Replied', 'Closed' => 'Closed'] as $tabStatus => $tabLabel): ?>
            <a class="<?php echo $filters['status'] === $tabStatus ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(afrisense_enquiry_url($filters, ['status' => $tabStatus, 'view' => null]), ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($tabLabel, ENT_QUOTES, 'UTF-8'); ?>
                <span><?php echo htmlspecialchars((string) ($statusCounts[$tabStatus] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <section class="af-menu-table-card af-enquiries-table-card" id="enquiries-table">
        <form id="af-enquiries-bulk-form" action="<?php echo htmlspecialchars(afrisense_enquiry_url($filters, ['view' => $selectedEnquiryId > 0 ? (string) $selectedEnquiryId : null], '#enquiries-table'), ENT_QUOTES, 'UTF-8'); ?>" method="post"></form>
        <form id="af-enquiries-sort-form" action="enquiries.php" method="get">
            <?php foreach ($filters as $filterKey => $filterValue): ?>
                <?php if ($filterKey !== 'sort' && $filterValue !== ''): ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($filterKey, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($filterValue, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
            <?php endforeach; ?>
        </form>
        <header class="af-enquiries-bulkbar">
            <label>
                <input type="checkbox" data-select-all-enquiries>
                <strong><span data-selected-enquiry-count>0</span> Selected</strong>
            </label>
            <button type="submit" form="af-enquiries-bulk-form" name="action" value="bulk_update_status" data-bulk-status="Read">Mark as Read</button>
            <label class="af-bulk-status-select">
                <span class="sr-only">Change Status</span>
                <select name="bulk_status" form="af-enquiries-bulk-form">
                    <option value="">Change Status</option>
                    <?php foreach (afrisense_enquiry_statuses() as $statusValue => $statusLabel): ?>
                        <option value="<?php echo htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" form="af-enquiries-bulk-form" name="action" value="bulk_update_status">Apply</button>
            <button type="button">Export</button>
            <label class="af-enquiry-sort">
                <span>Sort by:</span>
                <select name="sort" form="af-enquiries-sort-form" data-enquiry-sort>
                    <option value="newest" <?php echo $filters['sort'] !== 'oldest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $filters['sort'] === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                </select>
            </label>
        </header>

        <div class="af-menu-table af-enquiries-table table-responsive">
            <table>
                <colgroup>
                    <col class="af-enquiry-col-select">
                    <col class="af-enquiry-col-id">
                    <col class="af-enquiry-col-email">
                    <col class="af-enquiry-col-phone">
                    <col class="af-enquiry-col-subject">
                    <col class="af-enquiry-col-type">
                    <col class="af-enquiry-col-status">
                    <col class="af-enquiry-col-date">
                    <col class="af-enquiry-col-date">
                    <col class="af-enquiry-col-actions">
                </colgroup>
                <thead>
                    <tr>
                        <th><span class="sr-only">Select</span></th>
                        <th>Enquiry ID</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Received Date</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($enquiries === []): ?>
                        <tr>
                            <td colspan="10"><div class="af-empty-state">No enquiries found.</div></td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($enquiries as $enquiry): ?>
                        <?php
                        $enquiryId = (int) ($enquiry['id'] ?? 0);
                        $createdAt = strtotime((string) ($enquiry['created_at'] ?? '')) ?: time();
                        $updatedAt = strtotime((string) ($enquiry['updated_at'] ?? '')) ?: $createdAt;
                        $status = (string) ($enquiry['status'] ?? 'Pending');
                        $type = afrisense_enquiry_type((string) ($enquiry['subject'] ?? ''));
                        $isSelected = $selectedEnquiryId === $enquiryId;
                        $detailEnquiry = $isSelected ? ($selectedEnquiry ?? $enquiry) : null;
                        ?>
                        <tr id="enquiry-row-<?php echo htmlspecialchars((string) $enquiryId, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $isSelected ? 'is-selected' : ''; ?>">
                            <td><input type="checkbox" form="af-enquiries-bulk-form" name="selected_enquiries[]" value="<?php echo htmlspecialchars((string) $enquiryId, ENT_QUOTES, 'UTF-8'); ?>" data-enquiry-checkbox></td>
                            <td>
                                <a class="af-enquiry-id-link" href="<?php echo htmlspecialchars(afrisense_enquiry_url($filters, ['view' => (string) $enquiryId], '#enquiry-row-' . $enquiryId), ENT_QUOTES, 'UTF-8'); ?>">
                                    ENQ-<?php echo htmlspecialchars(str_pad((string) $enquiryId, 5, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </td>
                            <td>
                                <span class="af-enquiry-contact-text" title="<?php echo htmlspecialchars((string) ($enquiry['email'] ?: 'No email'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars((string) ($enquiry['email'] ?: 'No email'), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="af-enquiry-contact-text" title="<?php echo htmlspecialchars((string) ($enquiry['phone_number'] ?: 'No phone'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars((string) ($enquiry['phone_number'] ?: 'No phone'), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="af-enquiry-subject-text" title="<?php echo htmlspecialchars((string) ($enquiry['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars((string) ($enquiry['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td><span class="af-enquiry-type <?php echo htmlspecialchars(afrisense_enquiry_type_class($type), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><span class="af-enquiry-status <?php echo htmlspecialchars(afrisense_enquiry_status_class($status), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(afrisense_enquiry_status_label($status), ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars(date('j M Y', $createdAt), ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars(date('h:i A', $createdAt), ENT_QUOTES, 'UTF-8'); ?></small></td>
                            <td><?php echo htmlspecialchars(date('j M Y', $updatedAt), ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars(date('h:i A', $updatedAt), ENT_QUOTES, 'UTF-8'); ?></small></td>
                            <td>
                                <div class="af-row-actions af-enquiry-row-actions">
                                    <a href="<?php echo htmlspecialchars(afrisense_enquiry_url($filters, ['view' => (string) $enquiryId], '#enquiry-row-' . $enquiryId), ENT_QUOTES, 'UTF-8'); ?>" title="View enquiry" aria-label="View enquiry"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                    <a href="<?php echo htmlspecialchars(afrisense_enquiry_url($filters, ['view' => (string) $enquiryId], '#enquiry-reply-' . $enquiryId), ENT_QUOTES, 'UTF-8'); ?>" title="Reply" aria-label="Reply"><i class="bi bi-reply" aria-hidden="true"></i></a>
                                    <?php if (($enquiry['email'] ?? '') !== ''): ?>
                                        <a href="mailto:<?php echo htmlspecialchars((string) $enquiry['email'], ENT_QUOTES, 'UTF-8'); ?>" title="Email customer" aria-label="Email customer"><i class="bi bi-envelope" aria-hidden="true"></i></a>
                                    <?php endif; ?>
                                    <?php if ($status !== 'Closed'): ?>
                                        <form action="<?php echo htmlspecialchars(afrisense_enquiry_url($filters, ['view' => (string) $enquiryId], '#enquiry-row-' . $enquiryId), ENT_QUOTES, 'UTF-8'); ?>" method="post">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="enquiry_id" value="<?php echo htmlspecialchars((string) $enquiryId, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="status" value="Closed">
                                            <button type="submit" title="Close enquiry" aria-label="Close enquiry"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <?php if ($detailEnquiry !== null): ?>
                            <tr class="af-enquiry-detail-row">
                                <td colspan="10">
                                    <section class="af-enquiry-detail-card">
                                        <header>
                                            <div>
                                                <small>Selected Enquiry</small>
                                                <h2>ENQ-<?php echo htmlspecialchars(str_pad((string) $enquiryId, 5, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?></h2>
                                            </div>
                                            <span class="af-enquiry-status <?php echo htmlspecialchars(afrisense_enquiry_status_class((string) $detailEnquiry['status']), ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars(afrisense_enquiry_status_label((string) $detailEnquiry['status']), ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </header>
                                        <div class="af-enquiry-detail-grid">
                                            <article>
                                                <h3>Customer</h3>
                                                <p><strong><?php echo htmlspecialchars((string) ($detailEnquiry['fullname'] ?? 'Unknown Customer'), ENT_QUOTES, 'UTF-8'); ?></strong></p>
                                                <p><?php echo htmlspecialchars((string) ($detailEnquiry['email'] ?: 'No email'), ENT_QUOTES, 'UTF-8'); ?></p>
                                                <p><?php echo htmlspecialchars((string) ($detailEnquiry['phone_number'] ?: 'No phone'), ENT_QUOTES, 'UTF-8'); ?></p>
                                            </article>
                                            <article>
                                                <h3>Subject</h3>
                                                <p><strong><?php echo htmlspecialchars((string) ($detailEnquiry['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></p>
                                                <p><?php echo htmlspecialchars(date('j M Y, h:i A', $createdAt), ENT_QUOTES, 'UTF-8'); ?></p>
                                            </article>
                                            <article class="af-enquiry-message-box">
                                                <h3>Message</h3>
                                                <p><?php echo nl2br(htmlspecialchars((string) ($detailEnquiry['message'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
                                            </article>
                                            <?php if (trim((string) ($detailEnquiry['admin_response'] ?? '')) !== ''): ?>
                                                <article class="af-enquiry-message-box is-response">
                                                    <h3>Latest Admin Response</h3>
                                                    <p><?php echo nl2br(htmlspecialchars((string) $detailEnquiry['admin_response'], ENT_QUOTES, 'UTF-8')); ?></p>
                                                </article>
                                            <?php endif; ?>
                                        </div>
                                        <div class="af-enquiry-detail-actions">
                                            <?php if ((string) $detailEnquiry['status'] === 'Pending'): ?>
                                                <form action="<?php echo htmlspecialchars(afrisense_enquiry_url($filters, ['view' => (string) $enquiryId], '#enquiry-row-' . $enquiryId), ENT_QUOTES, 'UTF-8'); ?>" method="post">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="enquiry_id" value="<?php echo htmlspecialchars((string) $enquiryId, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="status" value="Read">
                                                    <button type="submit"><i class="bi bi-check2" aria-hidden="true"></i> Mark as Read</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ((string) $detailEnquiry['status'] !== 'Closed'): ?>
                                                <form action="<?php echo htmlspecialchars(afrisense_enquiry_url($filters, ['view' => (string) $enquiryId], '#enquiry-row-' . $enquiryId), ENT_QUOTES, 'UTF-8'); ?>" method="post">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="enquiry_id" value="<?php echo htmlspecialchars((string) $enquiryId, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="status" value="Closed">
                                                    <button class="secondary" type="submit"><i class="bi bi-archive" aria-hidden="true"></i> Close Enquiry</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                        <section class="af-enquiry-reply-card" id="enquiry-reply-<?php echo htmlspecialchars((string) $enquiryId, ENT_QUOTES, 'UTF-8'); ?>">
                                            <h3>Compose Reply</h3>
                                            <form action="<?php echo htmlspecialchars(afrisense_enquiry_url($filters, ['view' => (string) $enquiryId], '#enquiry-row-' . $enquiryId), ENT_QUOTES, 'UTF-8'); ?>" method="post">
                                                <input type="hidden" name="action" value="save_response">
                                                <input type="hidden" name="enquiry_id" value="<?php echo htmlspecialchars((string) $enquiryId, ENT_QUOTES, 'UTF-8'); ?>">
                                                <textarea name="admin_response" rows="5" placeholder="Write a clear response for the customer..." required><?php echo htmlspecialchars((string) ($detailEnquiry['admin_response'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                <button type="submit"><i class="bi bi-send" aria-hidden="true"></i> Save Reply</button>
                                            </form>
                                        </section>
                                    </section>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <footer class="af-menu-pagination">
            <p>Showing 1 to <?php echo htmlspecialchars((string) count($enquiries), ENT_QUOTES, 'UTF-8'); ?> of <?php echo htmlspecialchars((string) $totalEnquiries, ENT_QUOTES, 'UTF-8'); ?> enquiries</p>
            <div>
                <a class="is-active" href="<?php echo htmlspecialchars(afrisense_enquiry_url($filters), ENT_QUOTES, 'UTF-8'); ?>">1</a>
            </div>
        </footer>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
