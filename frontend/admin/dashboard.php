<?php
require_once __DIR__ . '/../auth/auth_bootstrap.php';

$authUser = afrisense_require_admin();
$adminName = (string) ($authUser['fullname'] ?? $authUser['email'] ?? 'Admin User');
$adminRole = ucwords(afrisense_role_name($authUser) ?: 'Staff');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | AfriSense</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <aside class="sidebar" aria-label="Admin navigation">
        <a class="brand" href="index.php" aria-label="AfriSense home">
            <span class="brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
            <span>
                <strong>AfriSense</strong>
                <small>Food Services</small>
            </span>
        </a>

        <nav class="side-nav">
            <a class="active" href="dashboard.php"><i class="bi bi-house-fill" aria-hidden="true"></i> Dashboard</a>

            <p>Management</p>
            <a href="orders.php"><i class="bi bi-box-seam" aria-hidden="true"></i> Orders <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="booking.php"><i class="bi bi-calendar3" aria-hidden="true"></i> Bookings <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="enquiries.php"><i class="bi bi-chat-square-text" aria-hidden="true"></i> Enquiries <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="foods.php"><i class="bi bi-clipboard2" aria-hidden="true"></i> Menu &amp; Packages <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="customers.php"><i class="bi bi-people" aria-hidden="true"></i> Customers <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="users.php"><i class="bi bi-person-badge" aria-hidden="true"></i> Staff Management <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>

            <p>Administration</p>
            <a href="roles.php"><i class="bi bi-person-gear" aria-hidden="true"></i> User Roles <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="roles.php"><i class="bi bi-shield-check" aria-hidden="true"></i> Permissions <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="settings.php"><i class="bi bi-gear" aria-hidden="true"></i> Settings</a>
            <a href="notifications.php"><i class="bi bi-envelope" aria-hidden="true"></i> Email Templates</a>

            <p>Reports</p>
            <a href="reports.php"><i class="bi bi-bar-chart" aria-hidden="true"></i> Analytics <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
            <a href="reports.php"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Reports <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i></a>
        </nav>

        <a class="logout-link" href="../auth/logout.php"><i class="bi bi-box-arrow-left" aria-hidden="true"></i> Logout</a>
    </aside>

    <div class="dashboard-shell">
        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Open navigation">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <strong class="top-title">Dashboard</strong>

            <label class="top-search" for="dashboard_search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="dashboard_search" name="dashboard_search" placeholder="Search anything...">
                <kbd>Ctrl + /</kbd>
            </label>

            <div class="top-actions">
                <button type="button" aria-label="Notifications">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    <span>8</span>
                </button>
                <button type="button" aria-label="Messages">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                    <span class="green">3</span>
                </button>
                <div class="admin-profile">
                    <img src="../assets/images/foodimage.jpeg" alt="">
                    <span>
                        <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?php echo htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8'); ?></small>
                    </span>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </div>
            </div>
        </header>

        <main class="content">
            <section class="page-heading">
                <div>
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?>! Here's today's business summary.</p>
                </div>
                <button type="button" class="date-filter">
                    <i class="bi bi-calendar4-week" aria-hidden="true"></i>
                    May 18 - May 24, 2025
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>
            </section>

            <section class="metric-grid" aria-label="Business summary">
                <article class="metric-card green">
                    <span><i class="bi bi-cart-check" aria-hidden="true"></i></span>
                    <div>
                        <small>Total Orders</small>
                        <strong>248</strong>
                        <p><i class="bi bi-arrow-up" aria-hidden="true"></i> 18.6%</p>
                        <em>vs last week</em>
                    </div>
                </article>

                <article class="metric-card gold">
                    <span><i class="bi bi-calendar-event" aria-hidden="true"></i></span>
                    <div>
                        <small>Total Bookings</small>
                        <strong>67</strong>
                        <p><i class="bi bi-arrow-up" aria-hidden="true"></i> 12.4%</p>
                        <em>vs last week</em>
                    </div>
                </article>

                <article class="metric-card blue">
                    <span><i class="bi bi-people" aria-hidden="true"></i></span>
                    <div>
                        <small>Total Customers</small>
                        <strong>532</strong>
                        <p><i class="bi bi-arrow-up" aria-hidden="true"></i> 14.2%</p>
                        <em>vs last week</em>
                    </div>
                </article>

                <article class="metric-card purple">
                    <span><i class="bi bi-currency-dollar" aria-hidden="true"></i></span>
                    <div>
                        <small>Total Revenue</small>
                        <strong>GH₵ 24,560</strong>
                        <p><i class="bi bi-arrow-up" aria-hidden="true"></i> 23.7%</p>
                        <em>vs last week</em>
                    </div>
                </article>
            </section>

            <section class="analytics-grid">
                <article class="panel chart-panel">
                    <header class="panel-header">
                        <h2>Order Statistics</h2>
                        <button type="button">This Week <i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                    </header>
                    <div class="chart-legend" aria-hidden="true">
                        <span><i class="this-week"></i> This Week</span>
                        <span><i class="last-week"></i> Last Week</span>
                    </div>

                    <div class="line-chart" role="img" aria-label="Orders rose from Monday to Thursday before dropping through Sunday.">
                        <svg viewBox="0 0 720 260" focusable="false" aria-hidden="true">
                            <line x1="46" y1="26" x2="46" y2="216"></line>
                            <line x1="46" y1="216" x2="690" y2="216"></line>
                            <line class="grid-line" x1="46" y1="170" x2="690" y2="170"></line>
                            <line class="grid-line" x1="46" y1="124" x2="690" y2="124"></line>
                            <line class="grid-line" x1="46" y1="78" x2="690" y2="78"></line>
                            <line class="grid-line" x1="46" y1="32" x2="690" y2="32"></line>
                            <path class="area" d="M70 166 L170 143 L270 119 L370 74 L470 107 L570 132 L670 155 L670 216 L70 216 Z"></path>
                            <polyline class="last-path" points="70,190 170,174 270,154 370,119 470,150 570,176 670,194"></polyline>
                            <polyline class="current-path" points="70,166 170,143 270,119 370,74 470,107 570,132 670,155"></polyline>
                            <g class="points">
                                <circle cx="70" cy="166" r="5"></circle>
                                <circle cx="170" cy="143" r="5"></circle>
                                <circle cx="270" cy="119" r="5"></circle>
                                <circle cx="370" cy="74" r="5"></circle>
                                <circle cx="470" cy="107" r="5"></circle>
                                <circle cx="570" cy="132" r="5"></circle>
                                <circle cx="670" cy="155" r="5"></circle>
                            </g>
                        </svg>
                        <div class="chart-days" aria-hidden="true">
                            <span>Mon</span>
                            <span>Tue</span>
                            <span>Wed</span>
                            <span>Thu</span>
                            <span>Fri</span>
                            <span>Sat</span>
                            <span>Sun</span>
                        </div>
                    </div>
                </article>

                <article class="panel status-panel">
                    <header class="panel-header">
                        <h2>Orders by Status</h2>
                    </header>
                    <div class="status-content">
                        <div class="donut-chart" role="img" aria-label="248 total orders by status">
                            <strong>248</strong>
                            <span>Total</span>
                        </div>
                        <ul class="status-list">
                            <li><i class="pending"></i><span>Pending</span><strong>38 (15.3%)</strong></li>
                            <li><i class="confirmed"></i><span>Confirmed</span><strong>112 (45.2%)</strong></li>
                            <li><i class="preparing"></i><span>Preparing</span><strong>58 (23.4%)</strong></li>
                            <li><i class="delivered"></i><span>Delivered</span><strong>32 (12.9%)</strong></li>
                            <li><i class="cancelled"></i><span>Cancelled</span><strong>8 (3.2%)</strong></li>
                        </ul>
                    </div>
                    <a class="panel-link" href="#">View all orders <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </article>
            </section>

            <section class="bottom-grid">
                <article class="panel table-panel">
                    <header class="panel-header">
                        <h2>Recent Orders</h2>
                        <a href="#">View All</a>
                    </header>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#ORD-000248</td>
                                    <td><img src="../assets/images/foodimage.jpeg" alt=""> Kwame Mensah</td>
                                    <td>May 24, 2025</td>
                                    <td>GH₵ 850</td>
                                    <td><span class="badge delivered">Delivered</span></td>
                                </tr>
                                <tr>
                                    <td>#ORD-000247</td>
                                    <td><img src="../assets/images/foodimage.jpeg" alt=""> Ama Serwaa</td>
                                    <td>May 24, 2025</td>
                                    <td>GH₵ 450</td>
                                    <td><span class="badge preparing">Preparing</span></td>
                                </tr>
                                <tr>
                                    <td>#ORD-000246</td>
                                    <td><img src="../assets/images/foodimage.jpeg" alt=""> Kofi Boateng</td>
                                    <td>May 23, 2025</td>
                                    <td>GH₵ 670</td>
                                    <td><span class="badge delivered">Delivered</span></td>
                                </tr>
                                <tr>
                                    <td>#ORD-000245</td>
                                    <td><img src="../assets/images/foodimage.jpeg" alt=""> Akosua Adom</td>
                                    <td>May 23, 2025</td>
                                    <td>GH₵ 930</td>
                                    <td><span class="badge pending">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>#ORD-000244</td>
                                    <td><img src="../assets/images/foodimage.jpeg" alt=""> Yaw Baffour</td>
                                    <td>May 23, 2025</td>
                                    <td>GH₵ 360</td>
                                    <td><span class="badge preparing">Preparing</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="panel package-panel">
                    <header class="panel-header">
                        <h2>Top Catering Packages</h2>
                        <a href="#">View All</a>
                    </header>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><img src="../assets/images/foodimage.jpeg" alt=""> Wedding Package</td>
                                    <td>45</td>
                                    <td>GH₵ 8,550</td>
                                </tr>
                                <tr>
                                    <td><img src="../assets/images/foodimage.jpeg" alt=""> Corporate Package</td>
                                    <td>38</td>
                                    <td>GH₵ 6,840</td>
                                </tr>
                                <tr>
                                    <td><img src="../assets/images/foodimage.jpeg" alt=""> Birthday Package</td>
                                    <td>29</td>
                                    <td>GH₵ 4,350</td>
                                </tr>
                                <tr>
                                    <td><img src="../assets/images/foodimage.jpeg" alt=""> Small Event Package</td>
                                    <td>21</td>
                                    <td>GH₵ 2,940</td>
                                </tr>
                                <tr>
                                    <td><img src="../assets/images/foodimage.jpeg" alt=""> Funeral Package</td>
                                    <td>15</td>
                                    <td>GH₵ 1,880</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="panel quick-panel">
                    <header class="panel-header">
                        <h2>Quick Actions</h2>
                    </header>
                    <div class="quick-actions">
                        <a href="#"><i class="bi bi-plus-circle-fill green-action" aria-hidden="true"></i> Create New Order</a>
                        <a href="#"><i class="bi bi-calendar-plus-fill gold-action" aria-hidden="true"></i> Add New Booking</a>
                        <a href="#"><i class="bi bi-fork-knife green-light-action" aria-hidden="true"></i> Add New Menu Item</a>
                        <a href="#"><i class="bi bi-bag-plus-fill blue-action" aria-hidden="true"></i> Add New Package</a>
                        <a href="#"><i class="bi bi-person-plus-fill purple-action" aria-hidden="true"></i> Add New User</a>
                        <a href="#"><i class="bi bi-send-fill orange-action" aria-hidden="true"></i> Send Notification</a>
                    </div>
                </article>
            </section>
        </main>
    </div>
</body>
</html>
