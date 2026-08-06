<?php
require_once __DIR__ . '/auth_bootstrap.php';

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Reset Password | AfriSense';
$activePage = '';
$extraStyles = [$frontendBase . '/assets/css/auth-recovery.css'];
$extraScripts = [$frontendBase . '/assets/js/auth-recovery.js'];
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$authMessage = null;

// Handle submitted form actions before rendering the page.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $result = afrisense_reset_password(
        $token,
        (string) ($_POST['new_password'] ?? ''),
        (string) ($_POST['confirm_password'] ?? '')
    );
    $authMessage = [
        'type' => $result['success'] ? 'success' : 'error',
        'message' => (string) $result['message'],
    ];
}

ob_start();
?>
<!-- Page section for this part of the AfriSense interface. -->
<section class="af-auth-recovery-page">
    <div class="af-recovery-card">
        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-recovery-form-panel" aria-labelledby="reset_password_title">
            <div class="af-recovery-icon success">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <span><i class="bi bi-check" aria-hidden="true"></i></span>
            </div>

            <h1 id="reset_password_title">Reset <span>Password</span></h1>
            <p class="af-recovery-subtitle">Enter your new password below. Make sure it's strong and secure.</p>

            <ol class="af-reset-steps" aria-label="Password reset progress">
                <li class="is-done">
                    <span><i class="bi bi-envelope-check" aria-hidden="true"></i></span>
                    Email Verified
                </li>
                <li class="is-active">
                    <span><i class="bi bi-lock" aria-hidden="true"></i></span>
                    Reset Password
                </li>
                <li>
                    <span><i class="bi bi-check" aria-hidden="true"></i></span>
                    Complete
                </li>
            </ol>

            <span class="af-gold-divider" aria-hidden="true"></span>

            <?php // Render this conditional/dynamic template block. ?>
            <?php if ($authMessage !== null): ?>
                <p class="auth-message <?php echo htmlspecialchars($authMessage['type'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($authMessage['message'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>

            <!-- Form block that submits this page workflow. -->
            <form class="af-recovery-form" action="reset-password.php" method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="af-form-group">
                    <label for="new_password">New Password</label>
                    <div class="af-input-icon af-password-field">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter your new password" minlength="8" required data-password-source>
                        <button type="button" aria-label="Show password" data-password-toggle>
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <small>Password must be at least 8 characters long.</small>
                </div>

                <div class="af-form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="af-input-icon af-password-field">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" minlength="8" required>
                        <button type="button" aria-label="Show password" data-password-toggle>
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="af-strength" aria-live="polite">
                    <div>
                        <strong>Password Strength:</strong>
                        <span data-strength-label>Strong</span>
                    </div>
                    <div class="af-strength-bars">
                        <span class="weak"></span>
                        <span class="medium"></span>
                        <span class="good"></span>
                        <span class="strong"></span>
                    </div>
                </div>

                <button class="af-submit-btn" type="submit">
                    <span>Reset Password</span>
                    <i class="bi bi-lock" aria-hidden="true"></i>
                </button>
            </form>

            <p class="af-login-note">Remember your password? <a href="login.php">Login here</a></p>
        </section>

        <!-- Side panel with supporting information and actions. -->
        <aside class="af-recovery-info-panel" aria-label="Password tips">
            <div class="af-recovery-image">
                <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="AfriSense meal">
            </div>

            <div class="af-info-content">
                <div class="af-info-lead">
                    <span><i class="bi bi-shield-lock-fill" aria-hidden="true"></i></span>
                    <div>
                        <h2>Password Tips</h2>
                        <p>A strong password keeps your account safe and protects your personal data.</p>
                    </div>
                </div>

                <ul class="af-info-list af-tips-list">
                    <li>
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <span><strong>Use at least 8 characters</strong> The longer, the better.</span>
                    </li>
                    <li>
                        <i class="bi bi-type" aria-hidden="true"></i>
                        <span><strong>Mix letters and numbers</strong> Use uppercase, lowercase and numbers.</span>
                    </li>
                    <li>
                        <i class="bi bi-hash" aria-hidden="true"></i>
                        <span><strong>Add special characters</strong> Include ! @ # $ % ^ &amp; * for extra security.</span>
                    </li>
                    <li>
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                        <span><strong>Avoid common passwords</strong> Don't use easily guessable passwords.</span>
                    </li>
                </ul>
            </div>
        </aside>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
