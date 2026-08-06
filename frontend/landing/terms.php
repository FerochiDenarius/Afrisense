<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Terms & Conditions | AfriSense';
$activePage = '';
$extraStyles = [$frontendBase . '/assets/css/menu-services.css'];

ob_start();
?>
<!-- Page section for this part of the AfriSense interface. -->
<section class="af-services-hero">
    <div class="af-services-hero-inner">
        <!-- Navigation links for this interface. -->
        <nav aria-label="Breadcrumb"><a href="index.php">Home</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span>Terms &amp; Conditions</span></nav>
        <p class="af-kicker">Service Terms</p>
        <h1>Terms &amp; <span>Conditions</span></h1>
        <p>Orders, bookings, and catering requests are subject to availability, confirmation, and AfriSense service policies.</p>
    </div>
</section>
<!-- Page section for this part of the AfriSense interface. -->
<section class="af-services-page">
    <!-- Header block for this interface section. -->
    <header class="af-page-heading">
        <p>Customer Agreement</p>
        <h2>Using AfriSense Services</h2>
        <small>Please confirm your order or booking details carefully. Our team may contact you to verify timing, menu choices, and delivery information.</small>
    </header>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
