<?php
// Preserve old settings.php links by forwarding to the current settings module.
$section = trim((string) ($_GET['section'] ?? ''));
$target = 'settings/index.php' . ($section !== '' ? '?section=' . rawurlencode($section) : '');
header('Location: ' . $target, true, 302);
exit;
