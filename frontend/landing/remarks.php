<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Reviews & Remarks | AfriSense';
$activePage = 'remarks';
$extraStyles = [$frontendBase . '/assets/css/public-remarks.css'];

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/remarks_helpers.php';

afrisense_enforce_public_site_status($frontendBase);

$currentUser = null;
try {
    $currentUser = afrisense_current_user();
} catch (Throwable) {
    $currentUser = null;
}
$remarkMessage = null;
$remarks = [];
$foodOptions = array_map(static fn (array $remark): string => (string) $remark['food_service'], afrisense_remarks_samples());
$loadError = '';

try {
    $pdo = afrisense_pdo();
    afrisense_remarks_seed_samples($pdo);
    $foodOptions = afrisense_remarks_food_options($pdo);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'submit_remark') {
        $remarkMessage = afrisense_remarks_submit($pdo, $_POST, $currentUser, $currentUser !== null ? 'Customer' : 'Guest');
    }

    $remarks = afrisense_remarks_fetch($pdo, ['status' => 'Published'], 9, false);
} catch (Throwable) {
    $loadError = 'Reviews could not be loaded. Please try again shortly.';
}

$publishedCount = count(array_filter($remarks, static fn (array $remark): bool => (string) $remark['status'] === 'Published'));
$averageRating = $remarks !== []
    ? array_sum(array_map(static fn (array $remark): float => (float) $remark['rating'], $remarks)) / count($remarks)
    : 0.0;

ob_start();
?>
<section class="af-remarks-hero">
    <div>
        <nav class="af-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="bi bi-house-door" aria-hidden="true"></i> Home</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Reviews &amp; Remarks</span>
        </nav>
        <p class="af-hero-kicker">Customer Feedback</p>
        <h1>Reviews &amp; Remarks</h1>
        <p>Read what customers say about AfriSense meals and services, or leave your own remark.</p>
        <div class="af-remarks-hero-actions">
            <a href="#give-remark"><i class="bi bi-pencil-square" aria-hidden="true"></i> Give a Remark</a>
            <a href="#customer-reviews"><i class="bi bi-star" aria-hidden="true"></i> Read Reviews</a>
        </div>
    </div>
</section>

<section class="af-public-remarks-page">
    <?php if ($loadError !== ''): ?>
        <div class="af-remark-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="af-public-remark-metrics" aria-label="Reviews summary">
        <article><span><i class="bi bi-chat-square-text" aria-hidden="true"></i></span><div><small>Published Remarks</small><strong><?php echo htmlspecialchars((string) $publishedCount, ENT_QUOTES, 'UTF-8'); ?></strong><p>Customer voices</p></div></article>
        <article><span><i class="bi bi-star" aria-hidden="true"></i></span><div><small>Average Rating</small><strong><?php echo htmlspecialchars(number_format($averageRating, 1), ENT_QUOTES, 'UTF-8'); ?></strong><?php echo afrisense_remarks_stars($averageRating); ?></div></article>
        <article><span><i class="bi bi-shield-check" aria-hidden="true"></i></span><div><small>Public Submission</small><strong>Live</strong><p>Remarks appear publicly</p></div></article>
    </section>

    <section class="af-public-remarks-grid">
        <main class="af-public-remarks-list" id="customer-reviews">
            <header>
                <div>
                    <h2>Customer Reviews</h2>
                    <p>Latest published feedback from customers and guests.</p>
                </div>
                <a href="#give-remark">Give a Remark</a>
            </header>

            <div class="af-review-card-grid">
                <?php if ($remarks === []): ?>
                    <article class="af-empty-remarks">
                        <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                        <h3>No published remarks yet</h3>
                        <p>Be the first to leave a remark. New remarks appear after review.</p>
                    </article>
                <?php endif; ?>
                <?php foreach ($remarks as $remark): ?>
                    <article class="af-review-card">
                        <img src="<?php echo htmlspecialchars(afrisense_remarks_image($frontendBase, (string) ($remark['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        <div>
                            <header>
                                <span>
                                    <strong><?php echo htmlspecialchars((string) ($remark['customer_name'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo htmlspecialchars((string) ($remark['food_service'] ?? 'AfriSense'), ENT_QUOTES, 'UTF-8'); ?></small>
                                </span>
                                <?php echo afrisense_remarks_stars((float) ($remark['rating'] ?? 0)); ?>
                            </header>
                            <p><?php echo htmlspecialchars(afrisense_remarks_excerpt((string) ($remark['remark'] ?? ''), 150), ENT_QUOTES, 'UTF-8'); ?></p>
                            <footer>
                                <span><?php echo htmlspecialchars(date('d M Y', strtotime((string) ($remark['created_at'] ?? '')) ?: time()), ENT_QUOTES, 'UTF-8'); ?></span>
                                <em><?php echo htmlspecialchars((string) ($remark['source'] ?? 'Guest'), ENT_QUOTES, 'UTF-8'); ?></em>
                            </footer>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </main>

        <aside class="af-give-remark-card" id="give-remark">
            <h2>Give a Remark</h2>
            <p>Guests and customers can rate any meal or service. New remarks appear publicly after submission.</p>

            <?php if ($remarkMessage !== null): ?>
                <div class="af-remark-alert <?php echo $remarkMessage['success'] ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($remarkMessage['message'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="remarks.php#give-remark" method="post">
                <input type="hidden" name="action" value="submit_remark">
                <label>
                    <span>Full Name</span>
                    <input type="text" name="customer_name" value="<?php echo htmlspecialchars((string) ($_POST['customer_name'] ?? $currentUser['fullname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="<?php echo htmlspecialchars((string) ($_POST['email'] ?? $currentUser['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>
                <label>
                    <span>Phone</span>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars((string) ($_POST['phone'] ?? $currentUser['phonenumber'] ?? $currentUser['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="+233 24 123 4567">
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

            <section class="af-form-recent-reviews" aria-label="Recent public reviews">
                <header>
                    <strong>Recent Public Reviews</strong>
                    <a href="#customer-reviews">View All</a>
                </header>
                <?php if ($remarks === []): ?>
                    <p>No published remarks yet.</p>
                <?php endif; ?>
                <?php foreach (array_slice($remarks, 0, 4) as $remark): ?>
                    <article>
                        <span>
                            <strong><?php echo htmlspecialchars((string) ($remark['customer_name'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <small><?php echo htmlspecialchars((string) ($remark['food_service'] ?? 'AfriSense'), ENT_QUOTES, 'UTF-8'); ?></small>
                        </span>
                        <?php echo afrisense_remarks_stars((float) ($remark['rating'] ?? 0)); ?>
                        <p><?php echo htmlspecialchars(afrisense_remarks_excerpt((string) ($remark['remark'] ?? ''), 72), ENT_QUOTES, 'UTF-8'); ?></p>
                    </article>
                <?php endforeach; ?>
            </section>
        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
