<?php
if (!function_exists('afrisense_alert')) {
    function afrisense_alert(string $type, string $message, bool $dismissible = true): void
    {
        $allowedTypes = ['success', 'error', 'warning', 'info'];
        $safeType = in_array($type, $allowedTypes, true) ? $type : 'info';
        ?>
        <div class="af-alert af-alert-<?php echo htmlspecialchars($safeType, ENT_QUOTES, 'UTF-8'); ?>" role="alert" data-alert>
            <i class="bi <?php echo afrisense_alert_icon($safeType); ?>" aria-hidden="true"></i>
            <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if ($dismissible): ?>
                <button type="button" aria-label="Dismiss alert" data-alert-dismiss>
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('afrisense_alert_icon')) {
    function afrisense_alert_icon(string $type): string
    {
        return match ($type) {
            'success' => 'bi-check-circle-fill',
            'error' => 'bi-x-circle-fill',
            'warning' => 'bi-exclamation-triangle-fill',
            default => 'bi-info-circle-fill',
        };
    }
}
?>
<div class="af-alert-stack" data-alert-stack></div>
