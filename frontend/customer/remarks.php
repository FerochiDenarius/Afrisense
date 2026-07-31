<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'My Remarks | AfriSense';
$customerTitle = 'My Remarks';
$activeCustomerPage = 'remarks';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/public-remarks.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/remarks_helpers.php';

$authUser = afrisense_require_customer();
$remarkMessage = null;
$remarks = [];
$foodOptions = array_map(static fn (array $remark): string => (string) $remark['food_service'], afrisense_remarks_samples());
$counts = ['all' => 0, 'Pending' => 0, 'Published' => 0, 'Rejected' => 0];
$loadError = '';

try {
    $pdo = afrisense_pdo();
    $customer = afrisense_remarks_customer_for_user($pdo, $authUser);
    $foodOptions = afrisense_remarks_food_options($pdo);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'submit_remark') {
        $remarkMessage = afrisense_remarks_submit($pdo, $_POST, $authUser, 'Customer');
    }

    $filters = [];
    if ($customer !== null) {
        $filters['customer_id'] = (string) ((int) $customer['id']);
    } else {
        $filters['email'] = (string) ($authUser['email'] ?? '');
    }

    $remarks = afrisense_remarks_fetch($pdo, $filters, 40, false);
    $counts['all'] = count($remarks);

    foreach ($remarks as $remark) {
        $status = (string) ($remark['status'] ?? 'Pending');
        if (isset($counts[$status])) {
            $counts[$status]++;
        }
    }
} catch (Throwable) {
    $loadError = 'Your remarks could not be loaded. Check that MySQL is running.';
}

ob_start();
?>
<section class="af-customer-remarks-page">
    <header class="af-customer-remarks-heading">
        <div>
            <h1>My Reviews &amp; Remarks</h1>
            <p>Give feedback on meals and services. Submitted remarks appear publicly after saving.</p>
        </div>
        <a href="#customer-remark-form"><i class="bi bi-pencil-square" aria-hidden="true"></i> Give Remark</a>
    </header>

    <?php if ($loadError !== ''): ?>
        <div class="af-remark-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($remarkMessage !== null): ?>
        <div class="af-remark-alert <?php echo $remarkMessage['success'] ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($remarkMessage['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section class="af-public-remark-metrics" aria-label="My remarks summary">
        <article><span><i class="bi bi-chat-square-text" aria-hidden="true"></i></span><div><small>Total Remarks</small><strong><?php echo htmlspecialchars((string) $counts['all'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Submitted by you</p></div></article>
        <article><span><i class="bi bi-globe2" aria-hidden="true"></i></span><div><small>Public</small><strong><?php echo htmlspecialchars((string) $counts['Published'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Visible publicly</p></div></article>
        <article><span><i class="bi bi-star" aria-hidden="true"></i></span><div><small>Rated</small><strong><?php echo htmlspecialchars((string) $counts['all'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Your feedback</p></div></article>
        <article><span><i class="bi bi-x-circle" aria-hidden="true"></i></span><div><small>Rejected</small><strong><?php echo htmlspecialchars((string) $counts['Rejected'], ENT_QUOTES, 'UTF-8'); ?></strong><p>Not published</p></div></article>
    </section>

    <section class="af-customer-remarks-grid">
        <section class="af-give-remark-card" id="customer-remark-form">
            <h2>Give a Remark</h2>
            <p>Your name and email are filled from your account. Choose what you are reviewing and submit your feedback.</p>
            <form action="remarks.php#customer-remark-form" method="post">
                <input type="hidden" name="action" value="submit_remark">
                <label>
                    <span>Full Name</span>
                    <input type="text" name="customer_name" value="<?php echo htmlspecialchars((string) ($_POST['customer_name'] ?? $authUser['fullname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="<?php echo htmlspecialchars((string) ($_POST['email'] ?? $authUser['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>
                <label>
                    <span>Phone</span>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars((string) ($_POST['phone'] ?? $authUser['phonenumber'] ?? $authUser['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
                <label>
                    <span>Food / Service</span>
                    <select name="food_service" required>
                        <option value="">Select food or service</option>
                        <?php foreach ($foodOptions as $option): ?>
                            <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) ($_POST['food_service'] ?? '') === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Rating</span>
                    <select name="rating" required>
                        <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                            <option value="<?php echo $rating; ?>" <?php echo (string) ($_POST['rating'] ?? '5') === (string) $rating ? 'selected' : ''; ?>><?php echo $rating; ?> Stars</option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>
                    <span>Remark</span>
                    <textarea name="remark" rows="4" minlength="10" maxlength="500" required><?php echo htmlspecialchars((string) ($_POST['remark'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </label>
                <button type="submit"><i class="bi bi-send" aria-hidden="true"></i> Submit Remark</button>
            </form>
        </section>

        <section class="af-my-remarks-list">
            <header>
                <h2>My Submitted Remarks</h2>
                <p>Your remarks appear on the public reviews page after submission.</p>
            </header>
            <?php if ($remarks === []): ?>
                <article class="af-empty-remarks">
                    <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                    <h3>No remarks yet</h3>
                    <p>Give your first remark using the form.</p>
                </article>
            <?php endif; ?>
            <?php foreach ($remarks as $remark): ?>
                <article class="af-my-remark-row">
                    <img src="<?php echo htmlspecialchars(afrisense_remarks_image($frontendBase, (string) ($remark['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                    <div>
                        <header>
                            <strong><?php echo htmlspecialchars((string) ($remark['food_service'] ?? 'AfriSense'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span class="af-remark-status <?php echo htmlspecialchars(afrisense_remarks_status_class((string) ($remark['status'] ?? 'Pending')), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($remark['status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </header>
                        <?php echo afrisense_remarks_stars((float) ($remark['rating'] ?? 0)); ?>
                        <p><?php echo htmlspecialchars((string) ($remark['remark'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <small><?php echo htmlspecialchars(date('d M Y h:i A', strtotime((string) ($remark['created_at'] ?? '')) ?: time()), ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/customer_layout.php';
?>
