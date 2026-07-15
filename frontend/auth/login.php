<?php
require_once __DIR__ . '/auth_bootstrap.php';

$existingUser = afrisense_current_user();

if ($existingUser !== null) {
    header('Location: ' . afrisense_dashboard_url($existingUser));
    exit;
}

$authMessage = afrisense_flash_get();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $response = afrisense_auth()->login((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));

    if (($response['success'] ?? false) === true) {
        $user = afrisense_current_user();
        header('Location: ' . afrisense_dashboard_url($user));
        exit;
    }

    $authMessage = ['type' => 'error', 'message' => (string) ($response['message'] ?? 'Login failed.')];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AfriSense</title>

    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="../landing/index.php" aria-label="AfriSense home">
            <span class="brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
            <span>
                <strong>AfriSense</strong>
                <small>Food Services</small>
            </span>
        </a>

        <nav class="site-nav" aria-label="Primary navigation">
            <ul>
                <li><a href="../landing/index.php">Home</a></li>
                <li><a href="../landing/menu.php">Menu</a></li>
                <li><a href="../landing/services.php">Catering Packages</a></li>
                <li><a href="../landing/booking.php">Book a Service</a></li>
                <li><a href="../landing/about.php">About Us</a></li>
                <li><a href="../landing/contact.php">Contact Us</a></li>
            </ul>
        </nav>

        <div class="header-actions">
            <a class="phone-link" href="tel:+233241234567">
                <span aria-hidden="true"><i class="bi bi-telephone"></i></span>
                +233 24 123 4567
            </a>
            <a class="order-button-link" href="../landing/order.php">Order Now</a>
        </div>
    </header>

    <main>
        <section class="login-hero" aria-labelledby="login-page-title">
            <div class="hero-panel">
                <div class="hero-overlay"></div>
                <div class="hero-copy">
                    <p class="eyebrow">Welcome Back</p>
                    <h1 id="login-page-title">Login to Your <span>AfriSense</span> Account</h1>
                    <p class="hero-description">
                        Access your account to manage orders, bookings, enquiries and more.
                    </p>

                    <div class="hero-features" aria-label="Login benefits">
                        <article class="hero-feature">
                            <span class="feature-icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
                            <span>
                                <strong>Secure &amp; Private</strong>
                                <small>Your data is protected with enterprise-grade security.</small>
                            </span>
                        </article>

                        <article class="hero-feature">
                            <span class="feature-icon" aria-hidden="true"><i class="bi bi-person"></i></span>
                            <span>
                                <strong>Personalized Experience</strong>
                                <small>Manage your orders, bookings and preferences in one place.</small>
                            </span>
                        </article>

                        <article class="hero-feature">
                            <span class="feature-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                            <span>
                                <strong>Save Time</strong>
                                <small>Quick access to your account anytime, anywhere.</small>
                            </span>
                        </article>
                    </div>
                </div>
            </div>

            <div class="login-panel">
                <section class="login-card" aria-labelledby="login-form-title">
                    <div class="card-header">
                        <span class="card-icon" aria-hidden="true"><i class="bi bi-person"></i></span>
                        <h2 id="login-form-title">Login to Your Account</h2>
                        <p>Enter your credentials to continue</p>
                        <span class="gold-line" aria-hidden="true"></span>
                    </div>

                    <?php if ($authMessage !== null): ?>
                        <p class="auth-message <?php echo htmlspecialchars((string) $authMessage['type'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string) $authMessage['message'], ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    <?php endif; ?>

                    <form class="login-form" action="login.php" method="post">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-shell">
                                <span class="input-icon" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                                <input type="email" id="email" name="email" placeholder="Enter your email address" autocomplete="email" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-shell password-shell">
                                <span class="input-icon" aria-hidden="true"><i class="bi bi-lock"></i></span>
                                <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                                <button type="button" class="password-toggle" aria-label="Show password" aria-controls="password">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-options">
                            <label class="remember-option" for="remember">
                                <input type="checkbox" id="remember" name="remember" value="1">
                                <span>Remember Me</span>
                            </label>
                            <a href="forgot-password.php">Forgot Password?</a>
                        </div>

                        <button class="login-button" type="submit">
                            <span>Login</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>

                        <div class="divider" role="separator">
                            <span></span>
                            <small>OR</small>
                            <span></span>
                        </div>

                        <button class="google-button" type="button">
                            <span class="google-mark" aria-hidden="true">G</span>
                            <span>Login with Google</span>
                        </button>

                        <p class="register-link">
                            Don't have an account? <a href="register.php">Register here</a>
                        </p>
                    </form>
                </section>
            </div>
        </section>
    </main>

    <footer class="login-footer">
        <div class="trust-items" aria-label="AfriSense service guarantees">
            <article class="trust-item">
                <span aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                <div>
                    <strong>100% Secure</strong>
                    <small>Your information is safe with us</small>
                </div>
            </article>

            <article class="trust-item">
                <span aria-hidden="true"><i class="bi bi-headset"></i></span>
                <div>
                    <strong>24/7 Support</strong>
                    <small>We're here to help you</small>
                </div>
            </article>

            <article class="trust-item">
                <span aria-hidden="true"><i class="bi bi-truck"></i></span>
                <div>
                    <strong>Fast Delivery</strong>
                    <small>Fresh meals to your doorstep</small>
                </div>
            </article>

            <article class="trust-item">
                <span aria-hidden="true"><i class="bi bi-patch-check"></i></span>
                <div>
                    <strong>Quality Guaranteed</strong>
                    <small>Best ingredients, best taste</small>
                </div>
            </article>
        </div>

        <p>&copy; 2024 AfriSense Food Services. All Rights Reserved.</p>
    </footer>

    <script src="../assets/js/login.js" defer></script>
</body>
</html>
