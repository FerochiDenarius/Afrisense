<?php
$adminTitle = $adminTitle ?? 'Dashboard';
$adminName = $adminName ?? 'Admin User';
$adminRole = $adminRole ?? 'Super Admin';
$frontendBase = $frontendBase ?? '/Afrisense/frontend';
?>
<header class="af-dashboard-header af-admin-header">
    <a class="af-header-brand" href="<?php echo htmlspecialchars($frontendBase . '/admin/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="AfriSense admin dashboard">
        <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
        <span>
            <strong>AfriSense</strong>
            <small>Food Services</small>
        </span>
    </a>

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
        <button class="af-header-action" type="button" aria-label="Notifications">
            <span class="af-action-icon">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <em>8</em>
            </span>
            <small>Notifications</small>
        </button>
        <button class="af-header-action" type="button" aria-label="Messages">
            <span class="af-action-icon">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <em class="is-green">5</em>
            </span>
            <small>Messages</small>
        </button>
        <button class="af-header-action" type="button" aria-label="Fullscreen">
            <span class="af-action-icon">
                <i class="bi bi-fullscreen" aria-hidden="true"></i>
            </span>
            <small>Fullscreen</small>
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
