<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | AfriSense</title>

    <link rel="stylesheet" href="../assets/css/register.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <header class="header">
        <a class="logo" href="../landing/index.php" aria-label="AfriSense home">
            <span class="logo-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
            <span>
                <strong>AfriSense</strong>
                <small>Food Services</small>
            </span>
        </a>

        <nav class="main-nav" aria-label="Primary navigation">
            <ul>
                <li><a href="../landing/index.php">Home</a></li>
                <li><a href="#">Menu</a></li>
                <li><a href="#">Catering Packages</a></li>
                <li><a href="#">Book A Service</a></li>
                <li><a href="#">About Us</a></li>
                <li><a href="#">Contact Us</a></li>
            </ul>
        </nav>

        <div class="header-actions">
            <a class="phone-link" href="tel:+233241234567">
                <span aria-hidden="true"><i class="bi bi-telephone"></i></span>
                +233 24 123 4567
            </a>
            <button type="button">Order Now</button>
        </div>
    </header>

    <main>
        <section class="registration-container" aria-labelledby="registration-title">
            <div class="left">
                <div class="hero-content">
                    <p class="welcome-Note">Welcome to Afrisense</p>
                    <h1 class="Page-Title" id="registration-title">Create Your <span>Account</span></h1>
                    <p class="description">
                        Join AfriSense Food Services today and enjoy a seamless experience in ordering delicious meals
                        and booking catering services.
                    </p>
                </div>

                <div class="features" aria-label="Registration benefits">
                    <article class="feature">
                        <div class="feature-icon" aria-hidden="true">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <div class="feature-content">
                            <h3>Secure &amp; Private</h3>
                            <p>Your information is safe with us and will never be shared.</p>
                        </div>
                    </article>

                    <article class="feature">
                        <div class="feature-icon" aria-hidden="true">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="feature-content">
                            <h3>Quick and Easy</h3>
                            <p>Create your account in less than a minute.</p>
                        </div>
                    </article>

                    <article class="feature">
                        <div class="feature-icon" aria-hidden="true">
                            <i class="bi bi-gift"></i>
                        </div>
                        <div class="feature-content">
                            <h3>Exclusive Benefits</h3>
                            <p>Get updates on offers, new menu items and special discounts.</p>
                        </div>
                    </article>
                </div>

                <figure class="hero-food-plate" aria-hidden="true">
                    <img src="../assets/images/foodimage.jpeg" alt="">
                </figure>
            </div>

            <div class="right">
                <section class="register-card" aria-labelledby="form-title">
                    <div class="register-header">
                        <div class="register-icon" aria-hidden="true">
                            <i class="bi bi-person"></i>
                        </div>
                        <h2 id="form-title">Create Your Account</h2>
                        <p>Start ordering meals and booking catering services with AfriSense.</p>
                        <span class="gold-divider" aria-hidden="true"></span>
                    </div>

                    <form class="register-form" action="" method="post">
                        <fieldset class="account-selection" aria-label="Account type">
                            <legend>Account Type</legend>

                            <label class="selection-card active" for="account_customer">
                                <input type="radio" id="account_customer" name="account_type" value="customer" checked>
                                <span class="selection-icon" aria-hidden="true"><i class="bi bi-person-check"></i></span>
                                <span>
                                    <strong>Customer</strong>
                                    <small>Order food &amp; book services</small>
                                </span>
                            </label>

                            <label class="selection-card" for="account_business">
                                <input type="radio" id="account_business" name="account_type" value="business">
                                <span class="selection-icon" aria-hidden="true"><i class="bi bi-buildings"></i></span>
                                <span>
                                    <strong>Business / Corporate</strong>
                                    <small>Book for your organization</small>
                                </span>
                            </label>
                        </fieldset>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="fullname">Full Name</label>
                                <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" autocomplete="name" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="Enter your email address" autocomplete="email" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="0537044801" autocomplete="tel" required>
                            </div>

                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="password-field">
                                    <input type="password" id="password" name="password" placeholder="Create your password" autocomplete="new-password" required>
                                    <button type="button" class="password-toggle" aria-label="Show password" aria-controls="password">
                                        <i class="bi bi-eye-slash" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <div class="password-field">
                                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" autocomplete="new-password" required>
                                    <button type="button" class="password-toggle" aria-label="Show confirm password" aria-controls="confirm_password">
                                        <i class="bi bi-eye-slash" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="address">Address</label>
                                <input type="text" id="address" name="address" placeholder="Enter your address" autocomplete="street-address" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" placeholder="Enter your city" autocomplete="address-level2" required>
                            </div>

                            <div class="form-group">
                                <label for="preferred_contact">Preferred Contact Method</label>
                                <select id="preferred_contact" name="preferred_contact" required>
                                    <option value="">Select preferred method</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                    <option value="phone_call">Phone Call</option>
                                </select>
                            </div>
                        </div>

                        <div class="terms">
                            <input type="checkbox" id="agree" name="agree" value="1" required>
                            <label for="agree">
                                I agree to the <a href="terms.php">Terms &amp; Conditions</a> and
                                <a href="privacy.php">Privacy Policy</a>.
                            </label>
                        </div>

                        <div class="submit-section">
                            <button type="submit">
                                <span>Create Account</span>
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </button>
                        </div>

                        <p class="login-link">
                            Already have an account? <a href="login.php">Login Here</a>
                        </p>
                    </form>
                </section>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-content">
            <section class="footer-brand" aria-label="AfriSense">
                <a class="footer-logo" href="../landing/index.php" aria-label="AfriSense home">
                    <span class="logo-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
                    <span>
                        <strong>AfriSense</strong>
                        <small>Food Services</small>
                    </span>
                </a>
                <p>
                    Providing delicious meals and exceptional catering services for all occasions.
                    Taste, quality and excellence you can trust.
                </p>
                <div class="social-links" aria-label="Social media links">
                    <a href="#" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                    <a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                    <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x" aria-hidden="true"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
                </div>
            </section>

            <nav class="footer-column" aria-label="Quick links">
                <h2>Quick Links</h2>
                <ul>
                    <li><a href="../landing/index.php">Home</a></li>
                    <li><a href="#">Menu</a></li>
                    <li><a href="#">Catering Packages</a></li>
                    <li><a href="#">Book a Service</a></li>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>
            </nav>

            <nav class="footer-column" aria-label="Services">
                <h2>Services</h2>
                <ul>
                    <li><a href="#">Food Ordering</a></li>
                    <li><a href="#">Service Booking</a></li>
                    <li><a href="#">Catering Packages</a></li>
                    <li><a href="#">Custom Menus</a></li>
                    <li><a href="#">Fast Delivery</a></li>
                </ul>
            </nav>

            <section class="footer-column footer-contact">
                <h2>Contact Us</h2>
                <ul>
                    <li><i class="bi bi-telephone" aria-hidden="true"></i><a href="tel:+233241234567">+233 24 123 4567</a></li>
                    <li><i class="bi bi-envelope" aria-hidden="true"></i><a href="mailto:info@afrisense.com">info@afrisense.com</a></li>
                    <li><i class="bi bi-geo-alt" aria-hidden="true"></i><span>Accra, Ghana</span></li>
                    <li><i class="bi bi-clock" aria-hidden="true"></i><span>Mon - Sun: 8:00 AM - 10:00 PM</span></li>
                </ul>
            </section>

            <section class="footer-column newsletter">
                <h2>Newsletter</h2>
                <p>Subscribe to get the latest updates, offers and news.</p>
                <form action="" method="post">
                    <label class="visually-hidden" for="newsletter_email">Email address</label>
                    <input type="email" id="newsletter_email" name="newsletter_email" placeholder="Enter your email">
                    <button type="submit" aria-label="Subscribe"><i class="bi bi-send" aria-hidden="true"></i></button>
                </form>
            </section>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2024 AfriSense Food Services. All Rights Reserved.</p>
            <nav aria-label="Legal links">
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms &amp; Conditions</a>
            </nav>
        </div>
    </footer>

    <script src="../assets/js/register.js" defer></script>
</body>
</html>
