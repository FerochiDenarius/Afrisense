<?php
require_once __DIR__ . '/../auth/auth_bootstrap.php';

$authUser = afrisense_require_admin();
$frontendBase = $frontendBase ?? '/Afrisense/frontend';
$pageTitle = $pageTitle ?? 'Admin | AfriSense';
$adminTitle = $adminTitle ?? 'Dashboard';
$activeAdminPage = $activeAdminPage ?? '';
$adminName = $adminName ?? (string) ($authUser['fullname'] ?? $authUser['email'] ?? 'Admin User');
$adminRole = $adminRole ?? ucwords(afrisense_role_name($authUser) ?: 'Staff');
$extraStyles = $extraStyles ?? [];
$extraScripts = $extraScripts ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($frontendBase . '/assets/css/main.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($frontendBase . '/assets/css/dashboard.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php foreach ($extraStyles as $style): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endforeach; ?>
</head>
<body class="af-page af-dashboard-page">
    <?php require __DIR__ . '/../components/admin_sidebar.php'; ?>

    <div class="af-dashboard-shell">
        <?php require __DIR__ . '/../components/admin_header.php'; ?>
        <main class="af-dashboard-content">
            <?php
            if (isset($contentView)) {
                require $contentView;
            } elseif (isset($content)) {
                echo $content;
            }
            ?>
        </main>
    </div>

    <?php require __DIR__ . '/../components/alerts.php'; ?>
    <?php require __DIR__ . '/../components/modal.php'; afrisense_modal(); ?>
    <?php require __DIR__ . '/../components/loader.php'; ?>

    <script src="<?php echo htmlspecialchars($frontendBase . '/assets/js/sidebar.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars($frontendBase . '/assets/js/alerts.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars($frontendBase . '/assets/js/modal.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars($frontendBase . '/assets/js/main.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?php echo htmlspecialchars($script, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
