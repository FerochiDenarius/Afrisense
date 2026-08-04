<?php
$frontendBase = '/Afrisense/frontend';
require_once __DIR__ . '/../includes/public_settings.php';
require_once __DIR__ . '/../includes/theme.php';
require_once __DIR__ . '/../includes/remarks_helpers.php';

$publicSettings = afrisense_public_settings();
$websiteSettings = $publicSettings['website'];
$companySettings = $publicSettings['company'];
$siteName = (string) ($websiteSettings['site_name'] ?? 'AfriSense Food Services');
$brandName = str_replace(' Food Services', '', $siteName);
$siteTagline = (string) ($websiteSettings['site_tagline'] ?? 'Food Services');
$heroTitle = (string) ($websiteSettings['hero_title'] ?? 'Exceptional Food Memorable Moments');
$heroSubtitle = (string) ($websiteSettings['hero_subtitle'] ?? 'We provide delicious meals and professional catering services for all occasions.');
$footerText = (string) ($websiteSettings['footer_text'] ?? '(c) 2026 AfriSense Food Services. All rights reserved.');
$primaryPhone = (string) ($companySettings['phone_number_1'] ?? '+233 24 123 4567');
$primaryColor = (string) ($websiteSettings['primary_color'] ?? '#b77b1a');
$faviconUrl = afrisense_public_favicon_url($frontendBase);
$heroTitleWords = preg_split('/\s+/', trim($heroTitle)) ?: [];
$heroHighlightWords = count($heroTitleWords) >= 2 ? array_splice($heroTitleWords, -2) : [];
$heroTitleStart = implode(' ', $heroTitleWords);
$heroTitleHighlight = implode(' ', $heroHighlightWords);
$homepageRemarks = [];
$homepageRemarkMessage = null;
$homepageFoodOptions = array_map(static fn (array $remark): string => (string) $remark['food_service'], afrisense_remarks_samples());
$homepageUser = null;
$homepageOrderHref = afrisense_public_order_url($frontendBase);
$homepageBookingHref = afrisense_public_booking_url($frontendBase);
$homepageSupportHref = afrisense_public_support_url($frontendBase);

try {
    $pdo = afrisense_pdo();
    afrisense_remarks_seed_samples($pdo);
    $homepageFoodOptions = afrisense_remarks_food_options($pdo);

    try {
        $homepageUser = afrisense_current_user();
    } catch (Throwable) {
        $homepageUser = null;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'submit_home_remark') {
        $homepageRemarkMessage = afrisense_remarks_submit($pdo, $_POST, $homepageUser, $homepageUser !== null ? 'Customer' : 'Guest');
    }

    $homepageRemarks = afrisense_remarks_fetch($pdo, ['status' => 'Published'], 3, false);
} catch (Throwable) {
    $homepageRemarks = [];
}

afrisense_enforce_public_site_status($frontendBase);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($heroSubtitle !== '' ? $heroSubtitle : $siteTagline, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-color" content="<?php echo htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="canonical" href="/Afrisense/frontend/landing/index.php">
    <?php if ($faviconUrl !== ''): ?>
        <link rel="icon" href="<?php echo htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <title><?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></title>

    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php afrisense_print_theme_style(); ?>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="AfriSense home">
            <span class="brand-icon" aria-hidden="true"><?php echo afrisense_public_brand_icon_html($frontendBase); ?></span>
            <span>
                <strong><?php echo htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8'); ?></strong>
                <small>Food Services</small>
            </span>
        </a>

        <nav class="site-nav" aria-label="Primary navigation">
            <ul>
                <li><a class="active" href="index.php">Home</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="gallery.php">Gallery</a></li>
                <li><a href="services.php">Catering Packages</a></li>
                <li><a href="<?php echo htmlspecialchars($homepageBookingHref, ENT_QUOTES, 'UTF-8'); ?>">Book a Service</a></li>
                <li><a href="remarks.php">Reviews</a></li>
                <li><a href="<?php echo htmlspecialchars($homepageSupportHref, ENT_QUOTES, 'UTF-8'); ?>">Support</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="contact.php">Contact Us</a></li>
            </ul>
        </nav>

        <div class="header-actions">
            <a class="phone-link" href="<?php echo htmlspecialchars(afrisense_public_tel_href($primaryPhone), ENT_QUOTES, 'UTF-8'); ?>">
                <span aria-hidden="true"><i class="bi bi-telephone"></i></span>
                <b><?php echo htmlspecialchars($primaryPhone, ENT_QUOTES, 'UTF-8'); ?></b>
            </a>
            <a class="order-link" href="<?php echo htmlspecialchars($homepageOrderHref, ENT_QUOTES, 'UTF-8'); ?>">Order Now</a>
            <?php if ($homepageUser === null): ?>
                <a class="auth-link" href="../auth/login.php">Login</a>
                <a class="auth-link register" href="../auth/register.php">Register</a>
            <?php else: ?>
                <a class="auth-link register" href="<?php echo htmlspecialchars(afrisense_dashboard_url($homepageUser), ENT_QUOTES, 'UTF-8'); ?>">Account</a>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <section class="hero-section" aria-labelledby="hero-title">
            <div class="hero-copy">
                <p class="eyebrow">Taste. Quality. Excellence</p>
                <h1 id="hero-title">
                    <?php echo htmlspecialchars($heroTitleStart !== '' ? $heroTitleStart : $heroTitle, ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($heroTitleHighlight !== ''): ?><span><?php echo htmlspecialchars($heroTitleHighlight, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                </h1>
                <p class="hero-description">
                    <?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, 'UTF-8'); ?>
                </p>

                <div class="hero-actions">
                    <a class="primary-action" href="<?php echo htmlspecialchars($homepageOrderHref, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="bi bi-basket2-fill" aria-hidden="true"></i>
                        Order Now
                    </a>
                    <a class="secondary-action" href="<?php echo htmlspecialchars($homepageBookingHref, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        Book a Service
                    </a>
                </div>

                <div class="trust-strip" aria-label="AfriSense benefits">
                    <article class="trust-item">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                        <span>
                            <strong>Hygienic &amp; Safe</strong>
                            <small>Food safety is our top priority</small>
                        </span>
                    </article>

                    <article class="trust-item">
                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                        <span>
                            <strong>On-Time Delivery</strong>
                            <small>We respect your time</small>
                        </span>
                    </article>

                    <article class="trust-item">
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        <span>
                            <strong>Quality Ingredients</strong>
                            <small>Fresh ingredients, great taste</small>
                        </span>
                    </article>

                    <a class="trust-item trust-support-link" href="<?php echo htmlspecialchars($homepageSupportHref, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Chat with AfriSense support agent">
                        <i class="bi bi-headset" aria-hidden="true"></i>
                        <span>
                            <strong>24/7 Support</strong>
                            <small>We are always here to help</small>
                        </span>
                    </a>
                </div>
            </div>

            <form class="booking-card" id="booking" action="<?php echo htmlspecialchars($homepageBookingHref, ENT_QUOTES, 'UTF-8'); ?>" method="get">
                <h2>Book Your Service</h2>
                <span class="gold-line" aria-hidden="true"></span>

                <label class="field-shell" for="service">
                    <i class="bi bi-person" aria-hidden="true"></i>
                    <select id="service" name="service" required>
                        <option value="">Select Service</option>
                        <option value="food_ordering">Food Ordering</option>
                        <option value="service_booking">Service Booking</option>
                        <option value="catering">Catering Packages</option>
                        <option value="custom_menu">Custom Menus</option>
                    </select>
                </label>

                <div class="booking-row">
                    <label class="field-shell" for="booking_date">
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        <input type="date" id="booking_date" name="booking_date" required>
                    </label>

                    <label class="field-shell" for="booking_time">
                        <i class="bi bi-clock" aria-hidden="true"></i>
                        <input type="time" id="booking_time" name="booking_time" required>
                    </label>
                </div>

                <button type="submit">
                    <span>Book Now</span>
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </form>
        </section>

        <section class="stats-panel" aria-label="AfriSense achievements">
            <article>
                <i class="bi bi-people" aria-hidden="true"></i>
                <strong>2,500+</strong>
                <span>Happy Customers</span>
            </article>
            <article>
                <i class="bi bi-bag-check" aria-hidden="true"></i>
                <strong>10,000+</strong>
                <span>Orders Delivered</span>
            </article>
            <article>
                <i class="bi bi-patch-check" aria-hidden="true"></i>
                <strong>5+</strong>
                <span>Years Experience</span>
            </article>
            <article>
                <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>
                <strong>98%</strong>
                <span>Customer Satisfaction</span>
            </article>
        </section>

        <section class="services-section" id="services" aria-labelledby="services-title">
            <div class="section-heading">
                <p>What We Offer</p>
                <h2 id="services-title">Our Services</h2>
                <span aria-hidden="true"></span>
                <small>We offer a wide range of food and catering services tailored to your needs.</small>
            </div>

            <div class="service-grid">
                <a class="service-card service-card-link" href="<?php echo htmlspecialchars($homepageOrderHref, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Open food ordering">
                    <span><i class="bi bi-bell" aria-hidden="true"></i></span>
                    <h3>Food Ordering</h3>
                    <p>Order delicious meals online with ease.</p>
                    <i class="bi bi-arrow-right service-card-arrow" aria-hidden="true"></i>
                </a>

                <a class="service-card service-card-link" href="<?php echo htmlspecialchars($homepageBookingHref, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Open service booking">
                    <span><i class="bi bi-calendar3" aria-hidden="true"></i></span>
                    <h3>Service Booking</h3>
                    <p>Book our catering services for any event.</p>
                    <i class="bi bi-arrow-right service-card-arrow" aria-hidden="true"></i>
                </a>

                <a class="service-card service-card-link" href="services.php" aria-label="Open catering packages">
                    <span><i class="bi bi-gift" aria-hidden="true"></i></span>
                    <h3>Catering Packages</h3>
                    <p>Explore our affordable catering packages.</p>
                    <i class="bi bi-arrow-right service-card-arrow" aria-hidden="true"></i>
                </a>

                <a class="service-card service-card-link" href="services.php" aria-label="Open custom menus">
                    <span><i class="bi bi-cup-hot" aria-hidden="true"></i></span>
                    <h3>Custom Menus</h3>
                    <p>We customize menus to fit your occasion.</p>
                    <i class="bi bi-arrow-right service-card-arrow" aria-hidden="true"></i>
                </a>

                <a class="service-card service-card-link" href="<?php echo htmlspecialchars($homepageOrderHref, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Open fast delivery ordering">
                    <span><i class="bi bi-truck" aria-hidden="true"></i></span>
                    <h3>Fast Delivery</h3>
                    <p>We deliver fresh and hot meals to you.</p>
                    <i class="bi bi-arrow-right service-card-arrow" aria-hidden="true"></i>
                </a>

                <a class="service-card service-card-link" href="<?php echo htmlspecialchars($homepageSupportHref, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Open AfriSense support agent chat">
                    <span><i class="bi bi-headset" aria-hidden="true"></i></span>
                    <h3>24/7 Support</h3>
                    <p>Our team is always ready to assist.</p>
                    <i class="bi bi-arrow-right service-card-arrow" aria-hidden="true"></i>
                </a>
            </div>
        </section>

        <section class="steps-section" aria-labelledby="steps-title">
            <div class="section-heading dark">
                <p>How It Works</p>
                <h2 id="steps-title">Simple Steps to Get Your Food</h2>
            </div>

            <div class="steps-line">
                <article>
                    <span>01</span>
                    <h3>Choose Service</h3>
                    <p>Select the service or meal you need.</p>
                </article>
                <article>
                    <span>02</span>
                    <h3>Pick Date &amp; Time</h3>
                    <p>Choose your preferred date and time.</p>
                </article>
                <article>
                    <span>03</span>
                    <h3>Confirm Booking</h3>
                    <p>Provide details and confirm your booking.</p>
                </article>
                <article>
                    <span>04</span>
                    <h3>We Prepare</h3>
                    <p>Our team prepares your order with care.</p>
                </article>
                <article>
                    <span>05</span>
                    <h3>Enjoy Your Meal</h3>
                    <p>We deliver or serve you a great experience.</p>
                </article>
            </div>
        </section>

        <section class="popular-section" id="popular-meals" aria-labelledby="popular-title">
            <div class="popular-heading">
                <div>
                    <h2 id="popular-title">Popular Meals</h2>
                    <p>Check out some of our most loved meals.</p>
                </div>
                <a href="menu.php">View Full Menu <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>

            <div class="meal-grid">
                <article class="meal-card">
                    <img src="../assets/images/foods/jollof-rice.png" alt="Jollof rice with grilled chicken and salad">
                    <div>
                        <h3>Jollof Rice &amp; Grilled Chicken</h3>
                        <p>Freshly prepared with salad and signature spices.</p>
                    </div>
                </article>
                <article class="meal-card">
                    <img src="../assets/images/foods/grilled-chicken.png" alt="AfriSense grilled chicken catering plate">
                    <div>
                        <h3>Family Catering Plate</h3>
                        <p>Balanced portions for small groups and events.</p>
                    </div>
                </article>
                <article class="meal-card">
                    <img src="../assets/images/foods/waakye.png" alt="AfriSense waakye meal package">
                    <div>
                        <h3>Corporate Lunch Package</h3>
                        <p>Reliable meal options for meetings and teams.</p>
                    </div>
                </article>
            </div>
        </section>

        <section class="reviews-section" aria-labelledby="reviews-title">
            <div class="reviews-section-heading">
                <div>
                    <p>Customer Feedback</p>
                    <h2 id="reviews-title">Reviews &amp; Remarks</h2>
                    <small>Published reviews from customers and guests.</small>
                </div>
                <a href="#landing-remark-form">
                    Give a Remark
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
            <div class="home-review-grid">
                <?php if ($homepageRemarks === []): ?>
                    <article class="home-review-empty">
                        <strong>No published remarks yet</strong>
                        <span>Be the first to give feedback.</span>
                    </article>
                <?php endif; ?>
                <?php foreach ($homepageRemarks as $remark): ?>
                    <article class="home-review-card">
                        <img src="<?php echo htmlspecialchars(afrisense_remarks_image($frontendBase, (string) ($remark['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        <div>
                            <header>
                                <strong><?php echo htmlspecialchars((string) ($remark['customer_name'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php echo afrisense_remarks_stars((float) ($remark['rating'] ?? 0)); ?>
                            </header>
                            <p><?php echo htmlspecialchars(afrisense_remarks_excerpt((string) ($remark['remark'] ?? ''), 92), ENT_QUOTES, 'UTF-8'); ?></p>
                            <small><?php echo htmlspecialchars((string) ($remark['food_service'] ?? 'AfriSense'), ENT_QUOTES, 'UTF-8'); ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <form class="home-remark-form" id="landing-remark-form" action="index.php#landing-remark-form" method="post">
                <input type="hidden" name="action" value="submit_home_remark">
                <header>
                    <strong>Give a Remark</strong>
                    <span>Submitted remarks appear publicly after saving.</span>
                </header>
                <?php if ($homepageRemarkMessage !== null): ?>
                    <p class="home-remark-alert <?php echo $homepageRemarkMessage['success'] ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($homepageRemarkMessage['message'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                <?php endif; ?>
                <div>
                    <label>
                        <span>Name</span>
                        <input type="text" name="customer_name" value="<?php echo htmlspecialchars((string) ($_POST['customer_name'] ?? $homepageUser['fullname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="<?php echo htmlspecialchars((string) ($_POST['email'] ?? $homepageUser['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>
                    <label>
                        <span>Food / Service</span>
                        <select name="food_service" required>
                            <option value="">Select</option>
                            <?php foreach ($homepageFoodOptions as $option): ?>
                                <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) ($_POST['food_service'] ?? '') === $option ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Rating</span>
                        <select name="rating" required>
                            <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                                <option value="<?php echo $rating; ?>" <?php echo (string) ($_POST['rating'] ?? '5') === (string) $rating ? 'selected' : ''; ?>><?php echo $rating; ?> Stars</option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label class="home-remark-textarea">
                        <span>Remark</span>
                        <textarea name="remark" minlength="10" maxlength="500" rows="3" required><?php echo htmlspecialchars((string) ($_POST['remark'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </label>
                    <button type="submit"><i class="bi bi-send" aria-hidden="true"></i> Submit Remark</button>
                </div>
            </form>
        </section>
    </main>

    <footer class="site-footer">
        <p><?php echo htmlspecialchars($footerText, ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="site-footer-social" aria-label="Social media links">
            <?php foreach (afrisense_public_social_links() as $social): ?>
                <a href="<?php echo htmlspecialchars($social['url'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($social['label'], ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi <?php echo htmlspecialchars($social['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                </a>
            <?php endforeach; ?>
        </div>
        <nav aria-label="Footer links">
            <a href="privacy.php">Privacy Policy</a>
            <a href="terms.php">Terms &amp; Conditions</a>
            <a href="remarks.php">Reviews &amp; Remarks</a>
        </nav>
    </footer>

    <?php if ($homepageUser === null): ?>
        <a class="guest-support-float" href="<?php echo htmlspecialchars($homepageSupportHref, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Chat with support as a guest">
            <i class="bi bi-headset" aria-hidden="true"></i>
            <span>Guest Support</span>
        </a>
    <?php endif; ?>

    <script src="../assets/js/index.js" defer></script>
</body>
</html>
