<?php
require_once __DIR__ . '/../includes/theme.php';

$frontendBase = $frontendBase ?? afrisense_frontend_url();
$pageTitle = $pageTitle ?? 'AfriSense Food Services';
$activePage = $activePage ?? '';
$extraStyles = $extraStyles ?? [];
$extraScripts = $extraScripts ?? [];
$mainScriptVersion = filemtime(__DIR__ . '/../assets/js/main.js') ?: time();
$themeSettings = afrisense_public_settings();
$websiteSettings = $themeSettings['website'];
$themeColor = afrisense_theme_color((string) ($themeSettings['website']['primary_color'] ?? ''), '#b77b1a');
$metaDescription = $metaDescription ?? (string) ($websiteSettings['hero_subtitle'] ?? $websiteSettings['site_tagline'] ?? '');
$canonicalUrl = $canonicalUrl ?? '';
$faviconUrl = afrisense_public_favicon_url($frontendBase);
$enforcePublicStatus = $enforcePublicStatus ?? true;

// Guard this block so it only runs when the required condition is met.
if ($enforcePublicStatus) {
    afrisense_enforce_public_site_status($frontendBase);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php // Render this conditional/dynamic template block. ?>
    <?php if ($metaDescription !== ''): ?>
        <meta name="description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <meta name="theme-color" content="<?php echo htmlspecialchars($themeColor, ENT_QUOTES, 'UTF-8'); ?>">
    <?php // Render this conditional/dynamic template block. ?>
    <?php if ($canonicalUrl !== ''): ?>
        <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php // Render this conditional/dynamic template block. ?>
    <?php if ($faviconUrl !== ''): ?>
        <link rel="icon" href="<?php echo htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(afrisense_asset_url('css/main.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php // Render this conditional/dynamic template block. ?>
    <?php foreach ($extraStyles as $style): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endforeach; ?>
    <?php afrisense_print_theme_style(); ?>
</head>
<body class="af-page af-public-page">
    <?php require __DIR__ . '/../components/navbar.php'; ?>

    <!-- Main content area for this page. -->
    <main>
        <?php
        // Guard this block so it only runs when the required condition is met.
        if (isset($contentView)) {
            require $contentView;
        } elseif (isset($content)) {
            echo $content;
        }
        ?>
    </main>

    <?php require __DIR__ . '/../components/footer.php'; ?>
    <?php require __DIR__ . '/../components/alerts.php'; ?>
    <?php require __DIR__ . '/../components/modal.php'; afrisense_modal(); ?>
    <?php require __DIR__ . '/../components/loader.php'; ?>

    <script src="<?php echo htmlspecialchars(afrisense_asset_url('js/navbar.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars(afrisense_asset_url('js/alerts.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars(afrisense_asset_url('js/modal.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars(afrisense_asset_url('js/main.js') . '?v=' . $mainScriptVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <?php // Render this conditional/dynamic template block. ?>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?php echo htmlspecialchars($script, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
