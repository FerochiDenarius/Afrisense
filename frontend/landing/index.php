<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AfriSense Food Services</title>

    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="AfriSense home">
            <span class="brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
            <span>
                <strong>AfriSense</strong>
                <small>Food Services</small>
            </span>
        </a>

        <nav class="site-nav" aria-label="Primary navigation">
            <ul>
                <li><a class="active" href="index.php">Home</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="services.php">Catering Packages</a></li>
                <li><a href="booking.php">Book a Service</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="contact.php">Contact Us</a></li>
            </ul>
        </nav>

        <div class="header-actions">
            <a class="phone-link" href="tel:+233241234567">
                <span aria-hidden="true"><i class="bi bi-telephone"></i></span>
                +233 24 123 4567
            </a>
            <a class="order-link" href="order.php">Order Now</a>
        </div>
    </header>

    <main>
        <section class="hero-section" aria-labelledby="hero-title">
            <div class="hero-copy">
                <p class="eyebrow">Taste. Quality. Excellence</p>
                <h1 id="hero-title">Exceptional Food <span>Memorable Moments</span></h1>
                <p class="hero-description">
                    We provide delicious meals and professional catering services for all occasions.
                    From small gatherings to big events, we&apos;ve got you covered.
                </p>

                <div class="hero-actions">
                    <a class="primary-action" href="order.php">
                        <i class="bi bi-basket2-fill" aria-hidden="true"></i>
                        Order Now
                    </a>
                    <a class="secondary-action" href="booking.php">
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

                    <article class="trust-item">
                        <i class="bi bi-headset" aria-hidden="true"></i>
                        <span>
                            <strong>24/7 Support</strong>
                            <small>We are always here to help</small>
                        </span>
                    </article>
                </div>
            </div>

            <form class="booking-card" id="booking" action="booking.php" method="get">
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
                <article class="service-card">
                    <span><i class="bi bi-bell" aria-hidden="true"></i></span>
                    <h3>Food Ordering</h3>
                    <p>Order delicious meals online with ease.</p>
                    <a href="order.php" aria-label="View food ordering"><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </article>

                <article class="service-card">
                    <span><i class="bi bi-calendar3" aria-hidden="true"></i></span>
                    <h3>Service Booking</h3>
                    <p>Book our catering services for any event.</p>
                    <a href="booking.php" aria-label="View service booking"><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </article>

                <article class="service-card">
                    <span><i class="bi bi-gift" aria-hidden="true"></i></span>
                    <h3>Catering Packages</h3>
                    <p>Explore our affordable catering packages.</p>
                    <a href="services.php" aria-label="View catering packages"><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </article>

                <article class="service-card">
                    <span><i class="bi bi-cup-hot" aria-hidden="true"></i></span>
                    <h3>Custom Menus</h3>
                    <p>We customize menus to fit your occasion.</p>
                    <a href="services.php" aria-label="View custom menus"><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </article>

                <article class="service-card">
                    <span><i class="bi bi-truck" aria-hidden="true"></i></span>
                    <h3>Fast Delivery</h3>
                    <p>We deliver fresh and hot meals to you.</p>
                    <a href="order.php" aria-label="View fast delivery"><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </article>

                <article class="service-card">
                    <span><i class="bi bi-headset" aria-hidden="true"></i></span>
                    <h3>24/7 Support</h3>
                    <p>Our team is always ready to assist.</p>
                    <a href="contact.php" aria-label="View support"><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </article>
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
                    <img src="../assets/images/foodimage.jpeg" alt="Jollof rice with grilled chicken and salad">
                    <div>
                        <h3>Jollof Rice &amp; Grilled Chicken</h3>
                        <p>Freshly prepared with salad and signature spices.</p>
                    </div>
                </article>
                <article class="meal-card">
                    <img src="../assets/images/foodimage.jpeg" alt="AfriSense catering plate">
                    <div>
                        <h3>Family Catering Plate</h3>
                        <p>Balanced portions for small groups and events.</p>
                    </div>
                </article>
                <article class="meal-card">
                    <img src="../assets/images/foodimage.jpeg" alt="AfriSense meal package">
                    <div>
                        <h3>Corporate Lunch Package</h3>
                        <p>Reliable meal options for meetings and teams.</p>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2024 AfriSense Food Services. All Rights Reserved.</p>
        <nav aria-label="Footer links">
            <a href="privacy.php">Privacy Policy</a>
            <a href="terms.php">Terms &amp; Conditions</a>
        </nav>
    </footer>

    <script src="../assets/js/index.js" defer></script>
</body>
</html>
