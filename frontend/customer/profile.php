<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Profile | AfriSense';
$customerTitle = 'Profile';
$activeCustomerPage = 'profile';

ob_start();
?>
<!-- Page section for this part of the AfriSense interface. -->
<section class="af-admin-menu-page">
    <!-- Header block for this interface section. -->
    <header class="af-admin-page-heading">
        <div>
            <h1>Profile</h1>
            <p>Your account profile is ready for backend-backed editing.</p>
        </div>
    </header>
    <!-- Page section for this part of the AfriSense interface. -->
    <section class="af-menu-panel">
        <h2>Profile details</h2>
        <p>Profile editing, address management, and preferences are the next pieces to connect.</p>
    </section>
</section>
<?php
$content = ob_get_clean();
$extraStyles = [$frontendBase . '/assets/css/admin-menu.css'];
require __DIR__ . '/../layouts/customer_layout.php';
?>
