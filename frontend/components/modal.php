<?php
// Guard this block so it only runs when the required condition is met.
if (!function_exists('afrisense_modal')) {
    // Defines the afrisense_modal helper used by this module.
    function afrisense_modal(
        string $id = 'afConfirmModal',
        string $title = 'Confirm Action',
        string $message = 'Are you sure you want to continue?',
        string $confirmText = 'Confirm',
        string $cancelText = 'Cancel'
    ): void {
        ?>
        <div class="af-modal" id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true" data-modal>
            <div class="af-modal-backdrop" data-modal-close></div>
            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>_title">
                <button class="af-modal-close" type="button" aria-label="Close modal" data-modal-close>
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
                <span class="af-modal-icon" aria-hidden="true"><i class="bi bi-question-circle"></i></span>
                <h2 id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>_title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
                <p data-modal-message><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="af-modal-actions">
                    <button class="af-btn af-btn-outline" type="button" data-modal-close><?php echo htmlspecialchars($cancelText, ENT_QUOTES, 'UTF-8'); ?></button>
                    <button class="af-btn af-btn-danger" type="button" data-modal-confirm><?php echo htmlspecialchars($confirmText, ENT_QUOTES, 'UTF-8'); ?></button>
                </div>
            </section>
        </div>
        <?php
    }
}
?>
