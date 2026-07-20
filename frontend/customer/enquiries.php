<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'My Enquiries | AfriSense';
$customerTitle = 'My Enquiries';
$activeCustomerPage = 'enquiries';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

$authUser = afrisense_require_customer();

function afrisense_customer_enquiry_customer(PDO $pdo, array $user): ?array
{
    $email = trim((string) ($user['email'] ?? ''));
    $phone = preg_replace('/\s+/', '', trim((string) ($user['phonenumber'] ?? $user['phone'] ?? '')));
    $statement = $pdo->prepare(
        'SELECT *
         FROM `customers`
         WHERE `email` = :email OR (:phone <> "" AND REPLACE(`phone_number`, " ", "") = :phone)
         ORDER BY `id` ASC
         LIMIT 1'
    );
    $statement->execute(['email' => $email, 'phone' => $phone]);
    $customer = $statement->fetch(PDO::FETCH_ASSOC);

    return $customer ?: null;
}

function afrisense_customer_enquiry_customer_id(PDO $pdo, array $user): int
{
    $customer = afrisense_customer_enquiry_customer($pdo, $user);

    if ($customer !== null) {
        return (int) $customer['id'];
    }

    $insert = $pdo->prepare(
        'INSERT INTO `customers` (`fullname`, `email`, `phone_number`, `address`)
         VALUES (:fullname, :email, :phone, :address)'
    );
    $insert->execute([
        'fullname' => (string) ($user['fullname'] ?? 'Customer'),
        'email' => (string) ($user['email'] ?? ''),
        'phone' => (string) ($user['phonenumber'] ?? $user['phone'] ?? 'Not provided'),
        'address' => 'Provided through customer account',
    ]);

    return (int) $pdo->lastInsertId();
}

function afrisense_customer_enquiry_status_class(string $status): string
{
    return match (strtolower($status)) {
        'read' => 'read',
        'replied' => 'replied',
        'closed' => 'closed',
        default => 'pending',
    };
}

function afrisense_customer_enquiry_status_label(string $status): string
{
    return match ($status) {
        'Pending' => 'Unread',
        'Read' => 'Reading',
        default => $status,
    };
}

function afrisense_customer_enquiry_excerpt(string $value, int $limit = 86): string
{
    $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

    if (strlen($value) <= $limit) {
        return $value;
    }

    return rtrim(substr($value, 0, $limit - 3)) . '...';
}

/**
 * @return array<string, string>
 */
function afrisense_customer_enquiry_filters(): array
{
    $statusAliases = [
        'Unread' => 'Pending',
        'Reading' => 'Read',
    ];
    $status = trim((string) ($_GET['status'] ?? ''));

    return [
        'search' => trim((string) ($_GET['search'] ?? '')),
        'status' => $statusAliases[$status] ?? $status,
    ];
}

/**
 * @param array<string, string|null> $overrides
 */
function afrisense_customer_enquiry_url(array $filters, array $overrides = [], string $anchor = ''): string
{
    $params = $filters;

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
            continue;
        }

        $params[$key] = $value;
    }

    $params = array_filter($params, static fn (string $value): bool => $value !== '');
    $query = http_build_query($params);

    return 'enquiries.php' . ($query !== '' ? '?' . $query : '') . $anchor;
}

function afrisense_customer_notify_admins(PDO $pdo, int $enquiryId, string $customerName, string $subject): void
{
    $adminStatement = $pdo->prepare(
        'SELECT u.`id`
         FROM `users` u
         INNER JOIN `roles` r ON r.`id` = u.`role_id`
         WHERE LOWER(r.`rolename`) IN ("administrator", "admin", "super admin")
         ORDER BY u.`id` ASC'
    );
    $adminStatement->execute();
    $adminIds = array_map('intval', $adminStatement->fetchAll(PDO::FETCH_COLUMN));

    if ($adminIds === []) {
        return;
    }

    $notification = $pdo->prepare(
        'INSERT INTO `notifications`
            (`user_id`, `title`, `message`, `notification_type`, `action_url`, `created_by`)
         VALUES
            (:user_id, :title, :message, :notification_type, :action_url, :created_by)'
    );
    $actionUrl = '/Afrisense/frontend/admin/enquiries.php?view=' . $enquiryId . '#enquiry-row-' . $enquiryId;

    foreach ($adminIds as $adminId) {
        $notification->execute([
            'user_id' => $adminId,
            'title' => 'New Enquiry Received',
            'message' => $customerName . ' submitted: ' . $subject,
            'notification_type' => 'Enquiry',
            'action_url' => $actionUrl,
            'created_by' => null,
        ]);
    }
}

/**
 * @return array{0: string, 1: array<string, mixed>}
 */
function afrisense_customer_enquiry_where(array $filters, int $customerId): array
{
    $where = ['`customer_id` = :customer_id'];
    $params = ['customer_id' => $customerId];

    if (in_array($filters['status'], ['Pending', 'Read', 'Replied', 'Closed'], true)) {
        $where[] = '`status` = :status';
        $params['status'] = $filters['status'];
    }

    if ($filters['search'] !== '') {
        $where[] = '(`subject` LIKE :search OR `message` LIKE :search OR `admin_response` LIKE :search OR CAST(`id` AS CHAR) LIKE :search)';
        $params['search'] = '%' . $filters['search'] . '%';
    }

    return ['WHERE ' . implode(' AND ', $where), $params];
}

$filters = afrisense_customer_enquiry_filters();
$viewEnquiryId = (int) ($_GET['view'] ?? 0);
$enquiries = [];
$selectedEnquiry = null;
$counts = ['all' => 0, 'Pending' => 0, 'Read' => 0, 'Replied' => 0, 'Closed' => 0];
$loadError = '';
$flashMessage = '';
$flashType = 'success';

try {
    $pdo = afrisense_pdo();
    $customerId = afrisense_customer_enquiry_customer_id($pdo, $authUser);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($subject === '' || strlen($message) < 10) {
            afrisense_flash_set('error', 'Choose a subject and enter a message with at least 10 characters.');
            header('Location: enquiries.php#new_enquiry');
            exit;
        }

        $pdo->beginTransaction();
        $insert = $pdo->prepare(
            'INSERT INTO `enquiries` (`customer_id`, `subject`, `message`, `status`)
             VALUES (:customer_id, :subject, :message, :status)'
        );
        $insert->execute([
            'customer_id' => $customerId,
            'subject' => $subject,
            'message' => $message,
            'status' => 'Pending',
        ]);
        $newEnquiryId = (int) $pdo->lastInsertId();
        afrisense_customer_notify_admins(
            $pdo,
            $newEnquiryId,
            (string) ($authUser['fullname'] ?? $authUser['email'] ?? 'Customer'),
            $subject
        );
        $pdo->commit();

        afrisense_flash_set('success', 'Your enquiry has been submitted. Our team will respond shortly.');
        header('Location: enquiries.php?view=' . $newEnquiryId . '#enquiry-row-' . $newEnquiryId);
        exit;
    }

    $flash = afrisense_flash_get();
    $flashMessage = (string) ($flash['message'] ?? '');
    $flashType = (string) ($flash['type'] ?? 'success');

    foreach (['Pending', 'Read', 'Replied', 'Closed'] as $status) {
        $countStatement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `enquiries` WHERE `customer_id` = :customer_id AND `status` = :status');
        $countStatement->execute(['customer_id' => $customerId, 'status' => $status]);
        $counts[$status] = (int) ($countStatement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0);
        $counts['all'] += $counts[$status];
    }

    [$whereSql, $params] = afrisense_customer_enquiry_where($filters, $customerId);
    $statement = $pdo->prepare(
        'SELECT `id`, `subject`, `message`, `status`, `admin_response`, `created_at`, `updated_at`
         FROM `enquiries`
         ' . $whereSql . '
         ORDER BY `created_at` DESC, `id` DESC
         LIMIT 25'
    );
    $statement->execute($params);
    $enquiries = $statement->fetchAll(PDO::FETCH_ASSOC);

    if ($viewEnquiryId > 0) {
        $selectedStatement = $pdo->prepare(
            'SELECT `id`, `subject`, `message`, `status`, `admin_response`, `created_at`, `updated_at`
             FROM `enquiries`
             WHERE `id` = :id AND `customer_id` = :customer_id
             LIMIT 1'
        );
        $selectedStatement->execute(['id' => $viewEnquiryId, 'customer_id' => $customerId]);
        $selectedEnquiry = $selectedStatement->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $loadError = 'Enquiries could not be loaded. Check that MySQL is running.';
}

$selectedEnquiryId = (int) ($selectedEnquiry['id'] ?? 0);

ob_start();
?>
<section class="af-admin-menu-page af-enquiries-management-page af-customer-enquiries-page">
    <header class="af-admin-page-heading af-enquiries-heading">
        <div>
            <h1>My Enquiries</h1>
            <p>Send questions to AfriSense and track every response from your customer panel.</p>
        </div>
        <a class="af-add-menu-btn" href="#new_enquiry"><i class="bi bi-send" aria-hidden="true"></i> New Enquiry</a>
    </header>

    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="af-menu-metrics" aria-label="Enquiry summary">
        <article class="gold"><span><i class="bi bi-chat-square-text" aria-hidden="true"></i></span><div><small>Total</small><strong><?php echo htmlspecialchars((string) $counts['all'], ENT_QUOTES, 'UTF-8'); ?></strong><p>All enquiries</p></div></article>
        <article class="purple"><span><i class="bi bi-hourglass-split" aria-hidden="true"></i></span><div><small>Unread</small><strong><?php echo htmlspecialchars((string) $counts['Pending'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Awaiting review</p></div></article>
        <article class="blue"><span><i class="bi bi-eye" aria-hidden="true"></i></span><div><small>Reading</small><strong><?php echo htmlspecialchars((string) $counts['Read'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Seen by admin</p></div></article>
        <article class="green"><span><i class="bi bi-reply" aria-hidden="true"></i></span><div><small>Replied</small><strong><?php echo htmlspecialchars((string) $counts['Replied'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Answered</p></div></article>
    </section>

    <section class="af-menu-table-card af-enquiries-filter-card">
        <form class="af-customer-enquiry-filters" action="enquiries.php" method="get">
            <label class="af-enquiry-input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" value="<?php echo htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search your enquiries...">
            </label>
            <select name="status">
                <option value="">All Status</option>
                <option value="Pending" <?php echo $filters['status'] === 'Pending' ? 'selected' : ''; ?>>Unread</option>
                <option value="Read" <?php echo $filters['status'] === 'Read' ? 'selected' : ''; ?>>Reading</option>
                <option value="Replied" <?php echo $filters['status'] === 'Replied' ? 'selected' : ''; ?>>Replied</option>
                <option value="Closed" <?php echo $filters['status'] === 'Closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
            <button type="submit"><i class="bi bi-funnel" aria-hidden="true"></i> Filter</button>
            <a href="enquiries.php">Reset</a>
        </form>
    </section>

    <section class="af-customer-enquiry-layout">
        <section class="af-menu-table-card af-enquiries-table-card">
            <div class="af-menu-table af-enquiries-table">
                <table>
                    <thead>
                        <tr>
                            <th>Enquiry ID</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($enquiries === []): ?>
                            <tr><td colspan="6"><div class="af-empty-state">No enquiries found.</div></td></tr>
                        <?php endif; ?>

                        <?php foreach ($enquiries as $enquiry): ?>
                            <?php
                            $enquiryId = (int) ($enquiry['id'] ?? 0);
                            $createdAt = strtotime((string) ($enquiry['created_at'] ?? '')) ?: time();
                            $updatedAt = strtotime((string) ($enquiry['updated_at'] ?? '')) ?: $createdAt;
                            $status = (string) ($enquiry['status'] ?? 'Pending');
                            $isSelected = $selectedEnquiryId === $enquiryId;
                            $detailEnquiry = $isSelected ? ($selectedEnquiry ?? $enquiry) : null;
                            ?>
                            <tr id="enquiry-row-<?php echo htmlspecialchars((string) $enquiryId, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $isSelected ? 'is-selected' : ''; ?>">
                                <td>
                                    <a class="af-enquiry-id-link" href="<?php echo htmlspecialchars(afrisense_customer_enquiry_url($filters, ['view' => (string) $enquiryId], '#enquiry-row-' . $enquiryId), ENT_QUOTES, 'UTF-8'); ?>">
                                        ENQ-<?php echo htmlspecialchars(str_pad((string) $enquiryId, 5, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars((string) ($enquiry['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo htmlspecialchars(afrisense_customer_enquiry_excerpt((string) ($enquiry['message'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></small>
                                </td>
                                <td><span class="af-enquiry-status <?php echo htmlspecialchars(afrisense_customer_enquiry_status_class($status), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(afrisense_customer_enquiry_status_label($status), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo htmlspecialchars(date('j M Y', $createdAt), ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars(date('h:i A', $createdAt), ENT_QUOTES, 'UTF-8'); ?></small></td>
                                <td><?php echo htmlspecialchars(date('j M Y', $updatedAt), ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars(date('h:i A', $updatedAt), ENT_QUOTES, 'UTF-8'); ?></small></td>
                                <td>
                                    <div class="af-row-actions af-enquiry-row-actions">
                                        <a href="<?php echo htmlspecialchars(afrisense_customer_enquiry_url($filters, ['view' => (string) $enquiryId], '#enquiry-row-' . $enquiryId), ENT_QUOTES, 'UTF-8'); ?>" aria-label="View enquiry"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                        <a href="#new_enquiry" aria-label="Create another enquiry"><i class="bi bi-plus-lg" aria-hidden="true"></i></a>
                                    </div>
                                </td>
                            </tr>

                            <?php if ($detailEnquiry !== null): ?>
                                <tr class="af-enquiry-detail-row">
                                    <td colspan="6">
                                        <section class="af-enquiry-detail-card">
                                            <header>
                                                <div>
                                                    <small>Enquiry Details</small>
                                                    <h2>ENQ-<?php echo htmlspecialchars(str_pad((string) $enquiryId, 5, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?></h2>
                                                </div>
                                                <span class="af-enquiry-status <?php echo htmlspecialchars(afrisense_customer_enquiry_status_class((string) $detailEnquiry['status']), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars(afrisense_customer_enquiry_status_label((string) $detailEnquiry['status']), ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </header>
                                            <div class="af-enquiry-detail-grid af-customer-enquiry-detail-grid">
                                                <article>
                                                    <h3>Subject</h3>
                                                    <p><strong><?php echo htmlspecialchars((string) ($detailEnquiry['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></p>
                                                    <p>Submitted <?php echo htmlspecialchars(date('j M Y, h:i A', $createdAt), ENT_QUOTES, 'UTF-8'); ?></p>
                                                </article>
                                                <article class="af-enquiry-message-box">
                                                    <h3>Your Message</h3>
                                                    <p><?php echo nl2br(htmlspecialchars((string) ($detailEnquiry['message'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
                                                </article>
                                                <article class="af-enquiry-message-box is-response">
                                                    <h3>Admin Response</h3>
                                                    <p><?php echo nl2br(htmlspecialchars((string) (($detailEnquiry['admin_response'] ?? '') !== '' ? $detailEnquiry['admin_response'] : 'No response yet. Our team will update this enquiry soon.'), ENT_QUOTES, 'UTF-8')); ?></p>
                                                </article>
                                            </div>
                                        </section>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="af-menu-panel af-customer-enquiry-form" id="new_enquiry">
            <h2><i class="bi bi-send" aria-hidden="true"></i> New Enquiry</h2>
            <form class="af-food-management-form compact" action="enquiries.php#new_enquiry" method="post">
                <label>
                    <span>Subject</span>
                    <select name="subject" required>
                        <option value="">Select a subject</option>
                        <option value="Order Support">Order Support</option>
                        <option value="Booking Support">Booking Support</option>
                        <option value="Catering Request">Catering Request</option>
                        <option value="Payment Issue">Payment Issue</option>
                        <option value="General Feedback">General Feedback</option>
                    </select>
                </label>
                <label>
                    <span>Message</span>
                    <textarea name="message" rows="7" minlength="10" maxlength="800" placeholder="Type your message clearly..." required></textarea>
                </label>
                <button type="submit"><i class="bi bi-send" aria-hidden="true"></i> Submit Enquiry</button>
            </form>
        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/customer_layout.php';
