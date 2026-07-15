<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'My Bookings | AfriSense';
$customerTitle = 'My Bookings';
$activeCustomerPage = 'bookings';

ob_start();
?>
<section class="af-admin-menu-page">
    <header class="af-admin-page-heading">
        <div>
            <h1>My Bookings</h1>
            <p>Your service bookings will appear here once booking records are connected to your account.</p>
        </div>
        <a class="af-add-menu-btn" href="<?php echo htmlspecialchars($frontendBase . '/landing/booking.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-calendar-plus" aria-hidden="true"></i>
            New Booking
        </a>
    </header>
    <section class="af-menu-panel">
        <h2>No bookings yet</h2>
        <p>Book a table, private dining, catering, or a custom request.</p>
    </section>
</section>
<?php
$content = ob_get_clean();
$extraStyles = [$frontendBase . '/assets/css/admin-menu.css'];
require __DIR__ . '/../layouts/customer_layout.php';
?>
