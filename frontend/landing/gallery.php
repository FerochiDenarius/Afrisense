<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery | AfriSense</title>

    <link rel="stylesheet" href="../assets/css/gallery.css">
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
            <p>Main</p>
            <a href="#"><i class="bi bi-house" aria-hidden="true"></i> Dashboard</a>
            <a href="#"><i class="bi bi-cart" aria-hidden="true"></i> Orders</a>
            <a href="#"><i class="bi bi-calendar3" aria-hidden="true"></i> Bookings</a>
            <a href="#"><i class="bi bi-people" aria-hidden="true"></i> Customers</a>
            <a href="#"><i class="bi bi-card-list" aria-hidden="true"></i> Menu &amp; Packages</a>
            <a class="active" href="gallery.php"><i class="bi bi-image" aria-hidden="true"></i> Gallery</a>
            <a href="#"><i class="bi bi-megaphone" aria-hidden="true"></i> Promotions</a>
            <a href="#"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Enquiries</a>
            <a href="#"><i class="bi bi-bar-chart" aria-hidden="true"></i> Reports</a>

            <p>Management</p>
            <a href="#"><i class="bi bi-person-gear" aria-hidden="true"></i> Staff Management</a>
            <a href="#"><i class="bi bi-box" aria-hidden="true"></i> Categories</a>
            <a href="#"><i class="bi bi-tags" aria-hidden="true"></i> Tags</a>

            <p>Settings</p>
            <a href="#"><i class="bi bi-gear" aria-hidden="true"></i> Settings</a>
            <a href="#"><i class="bi bi-shield-lock" aria-hidden="true"></i> Roles &amp; Permissions</a>
        </nav>

        <div class="sidebar-user">
            <img src="../assets/images/foodimage.jpeg" alt="">
            <span>
                <strong>Admin User</strong>
                <small>Super Administrator</small>
            </span>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </div>

        <a class="logout-link" href="#"><i class="bi bi-box-arrow-left" aria-hidden="true"></i> Logout</a>
    </aside>

    <div class="dashboard-shell">
        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Open navigation">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <label class="top-search" for="global_search">
                <input type="search" id="global_search" name="global_search" placeholder="Search meals, images, albums...">
                <i class="bi bi-search" aria-hidden="true"></i>
            </label>

            <div class="top-actions">
                <button type="button" aria-label="Notifications">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    <span>6</span>
                </button>
                <button type="button" aria-label="Messages">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                    <span class="green">3</span>
                </button>
                <div class="admin-profile">
                    <img src="../assets/images/foodimage.jpeg" alt="">
                    <span>
                        <strong>Admin User</strong>
                        <small>Super Admin</small>
                    </span>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </div>
            </div>
        </header>

        <main class="content">
            <section class="page-heading">
                <div>
                    <h1>Gallery</h1>
                    <p>Dashboard <i class="bi bi-chevron-right" aria-hidden="true"></i> Gallery</p>
                </div>
                <div class="heading-actions">
                    <button type="button" class="outline-btn"><i class="bi bi-folder" aria-hidden="true"></i> Create Album</button>
                    <button type="button" class="solid-btn"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Upload Images</button>
                </div>
            </section>

            <section class="metric-grid" aria-label="Gallery summary">
                <article class="metric-card green">
                    <span><i class="bi bi-image" aria-hidden="true"></i></span>
                    <div>
                        <small>Total Images</small>
                        <strong>428</strong>
                        <p>All images</p>
                    </div>
                </article>

                <article class="metric-card gold">
                    <span><i class="bi bi-folder" aria-hidden="true"></i></span>
                    <div>
                        <small>Albums</small>
                        <strong>18</strong>
                        <p>Total albums</p>
                    </div>
                </article>

                <article class="metric-card blue">
                    <span><i class="bi bi-tag" aria-hidden="true"></i></span>
                    <div>
                        <small>Tags</small>
                        <strong>24</strong>
                        <p>Total tags</p>
                    </div>
                </article>

                <article class="metric-card purple">
                    <span><i class="bi bi-images" aria-hidden="true"></i></span>
                    <div>
                        <small>Storage Used</small>
                        <strong>2.45 GB</strong>
                        <p>of 10 GB Used (24.5%)</p>
                        <span class="progress"><span></span></span>
                    </div>
                </article>
            </section>

            <section class="filter-panel" aria-label="Gallery filters">
                <label class="gallery-search" for="gallery_search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="gallery_search" name="gallery_search" placeholder="Search images...">
                </label>

                <label>
                    <span>Album</span>
                    <select name="album">
                        <option>All Albums</option>
                        <option>Meals</option>
                        <option>Events</option>
                    </select>
                </label>

                <label>
                    <span>Category</span>
                    <select name="category">
                        <option>All Categories</option>
                        <option>Meals</option>
                        <option>Soups</option>
                        <option>Catering</option>
                    </select>
                </label>

                <label>
                    <span>Tag</span>
                    <select name="tag">
                        <option>All Tags</option>
                        <option>Meals</option>
                        <option>Events</option>
                    </select>
                </label>

                <div class="filter-actions">
                    <button type="button"><i class="bi bi-filter" aria-hidden="true"></i> Filter</button>
                    <button type="button"><i class="bi bi-arrow-down-up" aria-hidden="true"></i> Sort</button>
                </div>
            </section>

            <section class="gallery-grid" aria-label="Gallery images">
                <article class="gallery-card">
                    <div class="image-frame"><img src="../assets/images/foodimage.jpeg" alt="Jollof rice and chicken"><input type="checkbox" aria-label="Select Jollof Rice and Chicken"><button type="button" aria-label="More options"><i class="bi bi-three-dots-vertical"></i></button></div>
                    <div class="card-body"><h2>Jollof Rice &amp; Chicken</h2><p>May 24, 2025 &bull; 1.2 MB</p><span class="tag meals">Meals</span></div>
                </article>

                <article class="gallery-card">
                    <div class="image-frame"><img src="../assets/images/foodimage.jpeg" alt="Grilled Tilapia"><input type="checkbox" aria-label="Select Grilled Tilapia"><button type="button" aria-label="More options"><i class="bi bi-three-dots-vertical"></i></button></div>
                    <div class="card-body"><h2>Grilled Tilapia</h2><p>May 23, 2025 &bull; 1.1 MB</p><span class="tag meals">Meals</span></div>
                </article>

                <article class="gallery-card">
                    <div class="image-frame"><img src="../assets/images/foodimage.jpeg" alt="Banku with Tilapia"><input type="checkbox" aria-label="Select Banku with Tilapia"><button type="button" aria-label="More options"><i class="bi bi-three-dots-vertical"></i></button></div>
                    <div class="card-body"><h2>Banku with Tilapia</h2><p>May 22, 2025 &bull; 900 KB</p><span class="tag meals">Meals</span></div>
                </article>

                <article class="gallery-card">
                    <div class="image-frame"><img src="../assets/images/foodimage.jpeg" alt="Waakye Special"><input type="checkbox" aria-label="Select Waakye Special"><button type="button" aria-label="More options"><i class="bi bi-three-dots-vertical"></i></button></div>
                    <div class="card-body"><h2>Waakye Special</h2><p>May 22, 2025 &bull; 1.3 MB</p><span class="tag meals">Meals</span></div>
                </article>

                <article class="gallery-card">
                    <div class="image-frame"><img src="../assets/images/foodimage.jpeg" alt="Chicken Salad"><input type="checkbox" aria-label="Select Chicken Salad"><button type="button" aria-label="More options"><i class="bi bi-three-dots-vertical"></i></button></div>
                    <div class="card-body"><h2>Chicken Salad</h2><p>May 21, 2025 &bull; 980 KB</p><span class="tag salads">Salads</span></div>
                </article>

                <article class="gallery-card">
                    <div class="image-frame"><img src="../assets/images/foodimage.jpeg" alt="Groundnut Soup"><input type="checkbox" aria-label="Select Groundnut Soup"><button type="button" aria-label="More options"><i class="bi bi-three-dots-vertical"></i></button></div>
                    <div class="card-body"><h2>Groundnut Soup</h2><p>May 21, 2025 &bull; 870 KB</p><span class="tag soups">Soups</span></div>
                </article>

                <article class="gallery-card">
                    <div class="image-frame"><img src="../assets/images/foodimage.jpeg" alt="Fried Rice with Beef"><input type="checkbox" aria-label="Select Fried Rice with Beef"><button type="button" aria-label="More options"><i class="bi bi-three-dots-vertical"></i></button></div>
                    <div class="card-body"><h2>Fried Rice with Beef</h2><p>May 20, 2025 &bull; 1.0 MB</p><span class="tag meals">Meals</span></div>
                </article>

                <article class="gallery-card">
                    <div class="image-frame"><img src="../assets/images/foodimage.jpeg" alt="Okro Soup"><input type="checkbox" aria-label="Select Okro Soup"><button type="button" aria-label="More options"><i class="bi bi-three-dots-vertical"></i></button></div>
                    <div class="card-body"><h2>Okro Soup</h2><p>May 20, 2025 &bull; 950 KB</p><span class="tag soups">Soups</span></div>
                </article>

                <article class="gallery-card">
                    <div class="image-frame"><img src="../assets/images/foodimage.jpeg" alt="Fresh Fruit Juice"><input type="checkbox" aria-label="Select Fresh Fruit Juice"><button type="button" aria-label="More options"><i class="bi bi-three-dots-vertical"></i></button></div>
                    <div class="card-body"><h2>Fresh Fruit Juice</h2><p>May 19, 2025 &bull; 780 KB</p><span class="tag drinks">Drinks</span></div>
                </article>

                <article class="gallery-card">
                    <div class="image-frame"><img src="../assets/images/foodimage.jpeg" alt="Event Catering"><input type="checkbox" aria-label="Select Event Catering"><button type="button" aria-label="More options"><i class="bi bi-three-dots-vertical"></i></button></div>
                    <div class="card-body"><h2>Event Catering</h2><p>May 19, 2025 &bull; 1.6 MB</p><span class="tag catering">Catering</span></div>
                </article>

                <article class="gallery-card">
                    <div class="image-frame"><img src="../assets/images/foodimage.jpeg" alt="Wedding Setup"><input type="checkbox" aria-label="Select Wedding Setup"><button type="button" aria-label="More options"><i class="bi bi-three-dots-vertical"></i></button></div>
                    <div class="card-body"><h2>Wedding Setup</h2><p>May 18, 2025 &bull; 1.4 MB</p><span class="tag events">Events</span></div>
                </article>

                <article class="gallery-card">
                    <div class="image-frame"><img src="../assets/images/foodimage.jpeg" alt="Meal Packages"><input type="checkbox" aria-label="Select Meal Packages"><button type="button" aria-label="More options"><i class="bi bi-three-dots-vertical"></i></button></div>
                    <div class="card-body"><h2>Meal Packages</h2><p>May 18, 2025 &bull; 1.1 MB</p><span class="tag packages">Packages</span></div>
                </article>
            </section>

            <footer class="gallery-footer">
                <div class="bulk-actions">
                    <label><input type="checkbox" aria-label="Select all images"> <span>0 selected</span></label>
                    <select aria-label="Bulk actions"><option>Bulk Actions</option><option>Move to Album</option><option>Delete</option></select>
                    <button type="button">Delete</button>
                </div>

                <p>Showing 1 to 12 of 428 images</p>

                <nav class="pagination" aria-label="Pagination">
                    <a href="#" aria-label="Previous page"><i class="bi bi-chevron-left"></i></a>
                    <a class="active" href="#">1</a>
                    <a href="#">2</a>
                    <a href="#">3</a>
                    <span>...</span>
                    <a href="#">36</a>
                    <a href="#" aria-label="Next page"><i class="bi bi-chevron-right"></i></a>
                    <select aria-label="Items per page"><option>12 / page</option><option>24 / page</option></select>
                </nav>
            </footer>
        </main>
    </div>

    <script src="../assets/js/gallery.js" defer></script>
</body>
</html>
