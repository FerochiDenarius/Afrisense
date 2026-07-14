<?php
$adminTitle = $adminTitle ?? 'Dashboard';
$adminName = $adminName ?? 'Admin User';
$adminRole = $adminRole ?? 'Super Admin';
$frontendBase = $frontendBase ?? '/Afrisense/frontend';
?>
<header class="af-dashboard-header af-admin-header">
    <button class="af-sidebar-toggle" type="button" aria-label="Toggle sidebar" aria-expanded="false" data-sidebar-toggle>
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    <strong class="af-header-title"><?php echo htmlspecialchars($adminTitle, ENT_QUOTES, 'UTF-8'); ?></strong>

    <label class="af-header-search" for="admin_global_search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="admin_global_search" name="admin_global_search" placeholder="Search anything...">
        <kbd>Ctrl + /</kbd>
    </label>

    <div class="af-header-actions">
        <button type="button" aria-label="Notifications">
            <i class="bi bi-bell" aria-hidden="true"></i>
            <span>8</span>
        </button>
        <button type="button" aria-label="Messages">
            <i class="bi bi-envelope" aria-hidden="true"></i>
            <span class="is-green">3</span>
        </button>
        <button class="af-profile-menu" type="button" aria-label="Profile menu">
            <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="">
            <span>
                <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></strong>
                <small><?php echo htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8'); ?></small>
            </span>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </button>
    </div>
</header>
