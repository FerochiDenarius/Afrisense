<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'My Orders | AfriSense';
$customerTitle = 'My Orders';
$activeCustomerPage = 'orders';

ob_start();
?>
<section class="af-admin-menu-page">
    <header class="af-admin-page-heading">
        <div>
            <h1>My Orders</h1>
            <p>Your food order history will appear here once orders are connected to your account.</p>
        </div>
        <a class="af-add-menu-btn" href="<?php echo htmlspecialchars($frontendBase . '/landing/order.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            New Order
        </a>
    </header>
    <section class="af-menu-panel">
        <h2>No orders yet</h2>
        <p>Browse the menu and place your first order.</p>
    </section>
</section>
<?php
$content = ob_get_clean();
$extraStyles = [$frontendBase . '/assets/css/admin-menu.css'];
require __DIR__ . '/../layouts/customer_layout.php';
?>
