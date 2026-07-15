<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Notifications | AfriSense';
$customerTitle = 'Notifications';
$activeCustomerPage = 'notifications';

ob_start();
?>
<section class="af-admin-menu-page">
    <header class="af-admin-page-heading">
        <div>
            <h1>Notifications</h1>
            <p>Order updates, booking confirmations, and AfriSense messages will appear here.</p>
        </div>
    </header>
    <section class="af-menu-panel">
        <h2>No notifications</h2>
        <p>You are all caught up.</p>
    </section>
</section>
<?php
$content = ob_get_clean();
$extraStyles = [$frontendBase . '/assets/css/admin-menu.css'];
require __DIR__ . '/../layouts/customer_layout.php';
?>
