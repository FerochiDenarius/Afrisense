<?php
require_once __DIR__ . '/auth_bootstrap.php';

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Forgot Password | AfriSense';
$activePage = '';
$extraStyles = [$frontendBase . '/assets/css/auth-recovery.css'];
$extraScripts = [$frontendBase . '/assets/js/auth-recovery.js'];
$authMessage = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $result = afrisense_request_password_reset((string) ($_POST['email'] ?? ''));
    $authMessage = [
        'type' => $result['success'] ? 'success' : 'error',
        'message' => (string) $result['message'],
    ];
}

ob_start();
?>
<section class="af-auth-recovery-page">
    <div class="af-recovery-card">
        <section class="af-recovery-form-panel" aria-labelledby="forgot_password_title">
            <div class="af-recovery-icon question">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <span>?</span>
            </div>

            <h1 id="forgot_password_title">Forgot <span>Password?</span></h1>
            <p class="af-recovery-subtitle">No worries! Enter your email address and we'll send you a link to reset your password.</p>
            <span class="af-gold-divider" aria-hidden="true"></span>

            <?php if ($authMessage !== null): ?>
                <p class="auth-message <?php echo htmlspecialchars($authMessage['type'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($authMessage['message'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>

            <form class="af-recovery-form" action="forgot-password.php" method="post">
                <div class="af-form-group">
                    <label for="forgot_email">Email Address</label>
                    <div class="af-input-icon">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <input type="email" id="forgot_email" name="email" placeholder="Enter your email address" required>
                    </div>
                </div>

                <button class="af-submit-btn" type="submit">
                    <span>Send Reset Link</span>
                    <i class="bi bi-send" aria-hidden="true"></i>
                </button>
            </form>

            <p class="af-login-note">Remember your password? <a href="login.php">Login here</a></p>

            <div class="af-or-divider">
                <span></span>
                <em>OR</em>
                <span></span>
            </div>

            <a class="af-back-login" href="login.php">
                <i class="bi bi-person" aria-hidden="true"></i>
                Back to Login
            </a>
        </section>

        <aside class="af-recovery-info-panel" aria-label="Password reset information">
            <div class="af-recovery-image">
                <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="AfriSense meal">
            </div>

            <div class="af-info-content">
                <div class="af-info-lead">
                    <span><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                    <div>
                        <h2>We've Got You Covered!</h2>
                        <p>Resetting your password helps keep your account secure. Please ensure you use an email address associated with your account.</p>
                    </div>
                </div>

                <ul class="af-info-list">
                    <li>
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <span><strong>Check your inbox</strong> We'll send you a secure reset link.</span>
                    </li>
                    <li>
                        <i class="bi bi-clock" aria-hidden="true"></i>
                        <span><strong>Link expires in 15 minutes</strong> For security, the link will expire.</span>
                    </li>
                    <li>
                        <i class="bi bi-question-circle" aria-hidden="true"></i>
                        <span><strong>Need help?</strong> Contact our support team.</span>
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
