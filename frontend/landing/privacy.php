<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Privacy Policy | AfriSense';
$activePage = '';
$extraStyles = [$frontendBase . '/assets/css/menu-services.css'];

ob_start();
?>
<section class="af-services-hero">
    <div class="af-services-hero-inner">
        <nav aria-label="Breadcrumb"><a href="index.php">Home</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span>Privacy Policy</span></nav>
        <p class="af-kicker">Your Privacy</p>
        <h1>Privacy <span>Policy</span></h1>
        <p>We use customer information only to process orders, manage bookings, respond to enquiries, and improve AfriSense services.</p>
    </div>
</section>
<section class="af-services-page">
    <header class="af-page-heading">
        <p>Data Care</p>
        <h2>How We Handle Your Information</h2>
        <small>AfriSense keeps customer details confidential and uses them only for service-related communication and fulfilment.</small>
    </header>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
