<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Website Settings | AfriSense';
$adminTitle = 'Website Settings';
$activeAdminPage = 'settings';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];
$extraScripts = [
    $frontendBase . '/assets/js/settings.js',
];

require __DIR__ . '/controller.php';

ob_start();
?>
<!-- Page section for this part of the AfriSense interface. -->
<section class="af-admin-menu-page af-settings-page">
    <!-- Header block for this interface section. -->
    <header class="af-admin-page-heading">
        <div>
            <h1>Website Settings</h1>
            <p>Dashboard / Website Settings / <?php echo htmlspecialchars($settingTabs[$activeSettingSection]['label'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <button class="af-add-menu-btn" type="submit" form="website_settings_form">
            <i class="bi bi-floppy" aria-hidden="true"></i>
            Save Changes
        </button>
    </header>

    <?php // Render this conditional/dynamic template block. ?>
    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- Navigation links for this interface. -->
    <nav class="af-settings-tabs" aria-label="Settings sections">
        <?php // Render this conditional/dynamic template block. ?>
        <?php foreach ($settingTabs as $sectionKey => $tab): ?>
            <a class="<?php echo $activeSettingSection === $sectionKey ? 'active' : ''; ?>" href="index.php?section=<?php echo htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi <?php echo htmlspecialchars($tab['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                <?php echo htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Form block that submits this page workflow. -->
    <form id="website_settings_form" class="af-settings-grid af-settings-grid-<?php echo htmlspecialchars($activeSettingSection, ENT_QUOTES, 'UTF-8'); ?>" action="index.php?section=<?php echo htmlspecialchars($activeSettingSection, ENT_QUOTES, 'UTF-8'); ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="settings_section" value="<?php echo htmlspecialchars($activeSettingSection, ENT_QUOTES, 'UTF-8'); ?>">
        <?php require __DIR__ . '/sections/' . $activeSettingSection . '.php'; ?>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin_layout.php';
?>
