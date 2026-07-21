<?php
require_once __DIR__ . '/../includes/theme.php';

$frontendBase = $frontendBase ?? '/Afrisense/frontend';
$pageTitle = $pageTitle ?? 'AfriSense Food Services';
$activePage = $activePage ?? '';
$extraStyles = $extraStyles ?? [];
$extraScripts = $extraScripts ?? [];
$mainScriptVersion = filemtime(__DIR__ . '/../assets/js/main.js') ?: time();
$themeSettings = afrisense_public_settings();
$themeColor = afrisense_theme_color((string) ($themeSettings['website']['primary_color'] ?? ''), '#b77b1a');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="<?php echo htmlspecialchars($themeColor, ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($frontendBase . '/assets/css/main.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php foreach ($extraStyles as $style): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endforeach; ?>
    <?php afrisense_print_theme_style(); ?>
</head>
<body class="af-page af-public-page">
    <?php require __DIR__ . '/../components/navbar.php'; ?>

    <main>
        <?php
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

    <script src="<?php echo htmlspecialchars($frontendBase . '/assets/js/navbar.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars($frontendBase . '/assets/js/alerts.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars($frontendBase . '/assets/js/modal.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars($frontendBase . '/assets/js/main.js?v=' . $mainScriptVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?php echo htmlspecialchars($script, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
