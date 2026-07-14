<?php
$customerTitle = $customerTitle ?? 'Dashboard';
$customerName = $customerName ?? 'Customer';
$frontendBase = $frontendBase ?? '/Afrisense/frontend';
?>
<header class="af-dashboard-header af-customer-header">
    <button class="af-sidebar-toggle" type="button" aria-label="Toggle sidebar" aria-expanded="false" data-sidebar-toggle>
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    <strong class="af-header-title"><?php echo htmlspecialchars($customerTitle, ENT_QUOTES, 'UTF-8'); ?></strong>

    <label class="af-header-search" for="customer_global_search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="customer_global_search" name="customer_global_search" placeholder="Search orders, bookings...">
    </label>

    <div class="af-header-actions">
        <button type="button" aria-label="Notifications">
            <i class="bi bi-bell" aria-hidden="true"></i>
            <span>4</span>
        </button>
        <button class="af-profile-menu" type="button" aria-label="Profile menu">
            <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="">
            <span>
                <strong><?php echo htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'); ?></strong>
                <small>Customer</small>
            </span>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </button>
    </div>
</header>
