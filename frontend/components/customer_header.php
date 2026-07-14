<?php
$customerTitle = $customerTitle ?? 'Dashboard';
$customerName = $customerName ?? 'Customer';
$frontendBase = $frontendBase ?? '/Afrisense/frontend';
?>
<header class="af-dashboard-header af-customer-header">
    <a class="af-header-brand" href="<?php echo htmlspecialchars($frontendBase . '/customer/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="AfriSense customer dashboard">
        <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
        <span>
            <strong>AfriSense</strong>
            <small>Food Services</small>
        </span>
    </a>

    <button class="af-sidebar-toggle" type="button" aria-label="Toggle sidebar" aria-expanded="false" data-sidebar-toggle>
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    <strong class="af-header-title"><?php echo htmlspecialchars($customerTitle, ENT_QUOTES, 'UTF-8'); ?></strong>

    <label class="af-header-search" for="customer_global_search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="customer_global_search" name="customer_global_search" placeholder="Search menu, orders, services...">
        <kbd>Ctrl + /</kbd>
    </label>

    <div class="af-header-actions">
        <button class="af-header-action" type="button" aria-label="Notifications">
            <span class="af-action-icon">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <em>3</em>
            </span>
            <small>Notifications</small>
        </button>
        <button class="af-header-action" type="button" aria-label="Wishlist">
            <span class="af-action-icon">
                <i class="bi bi-heart" aria-hidden="true"></i>
                <em class="is-gold">2</em>
            </span>
            <small>Wishlist</small>
        </button>
        <button class="af-header-action" type="button" aria-label="Cart">
            <span class="af-action-icon">
                <i class="bi bi-cart3" aria-hidden="true"></i>
                <em class="is-green">1</em>
            </span>
            <small>Cart</small>
        </button>
        <button class="af-profile-menu" type="button" aria-label="Profile menu">
            <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="">
            <span>
                <strong><?php echo htmlspecialchars($customerName === 'Customer' ? 'Jane Mensah' : $customerName, ENT_QUOTES, 'UTF-8'); ?></strong>
                <small>Customer</small>
            </span>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </button>
    </div>
</header>
