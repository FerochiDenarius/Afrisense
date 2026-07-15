<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Customer Dashboard | AfriSense';
$customerTitle = 'Dashboard';
$activeCustomerPage = 'dashboard';

ob_start();
?>
<section class="af-admin-menu-page">
    <header class="af-admin-page-heading">
        <div>
            <h1>Customer Dashboard</h1>
            <p>Manage your orders, bookings, notifications, and AfriSense profile.</p>
        </div>
        <a class="af-add-menu-btn" href="<?php echo htmlspecialchars($frontendBase . '/customer/orders.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-bag-plus" aria-hidden="true"></i>
            Order Food
        </a>
    </header>

    <section class="af-menu-metrics" aria-label="Customer summary">
        <article class="green">
            <span><i class="bi bi-bag-check" aria-hidden="true"></i></span>
            <div><small>Total Orders</small><strong>0</strong><p>Start your first order</p></div>
        </article>
        <article class="gold">
            <span><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
            <div><small>Bookings</small><strong>0</strong><p>No active bookings</p></div>
        </article>
        <article class="blue">
            <span><i class="bi bi-bell" aria-hidden="true"></i></span>
            <div><small>Notifications</small><strong>0</strong><p>You're all caught up</p></div>
        </article>
        <article class="purple">
            <span><i class="bi bi-heart" aria-hidden="true"></i></span>
            <div><small>Saved Items</small><strong>0</strong><p>Explore the menu</p></div>
        </article>
    </section>

    <section class="af-menu-workspace">
        <div class="af-menu-main">
            <section class="af-menu-table-card">
                <div class="af-menu-panel">
                    <h2>Quick Start</h2>
                    <div class="af-menu-actions">
                        <a href="<?php echo htmlspecialchars($frontendBase . '/landing/menu.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-grid green" aria-hidden="true"></i> Browse Menu</a>
                        <a href="<?php echo htmlspecialchars($frontendBase . '/landing/booking.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-calendar-plus gold" aria-hidden="true"></i> Book a Service</a>
                        <a href="<?php echo htmlspecialchars($frontendBase . '/landing/contact.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-chat-dots blue" aria-hidden="true"></i> Contact Support</a>
                    </div>
                </div>
            </section>
        </div>

        <aside class="af-menu-side">
            <section class="af-menu-panel">
                <h2>Your Account</h2>
                <ul class="af-category-list">
                    <li><i class="bi bi-person green" aria-hidden="true"></i><span>Profile</span><strong>New</strong></li>
                    <li><i class="bi bi-shield-check gold" aria-hidden="true"></i><span>Verification</span><strong>0%</strong></li>
                    <li><i class="bi bi-clock blue" aria-hidden="true"></i><span>Member Since</span><strong>Now</strong></li>
                </ul>
            </section>
        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
$extraStyles = [$frontendBase . '/assets/css/admin-menu.css'];
require __DIR__ . '/../layouts/customer_layout.php';
?>
