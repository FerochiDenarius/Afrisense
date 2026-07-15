<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Menu | AfriSense';
$activePage = 'menu';
$extraStyles = [$frontendBase . '/assets/css/menu-services.css'];

$categories = ['All Menu', 'Main Dishes', 'Local Dishes', 'Grills', 'Starters', 'Drinks'];
$menuItems = [
    ['name' => 'Jollof Rice', 'category' => 'Main Dishes', 'desc' => 'Spicy jollof rice served with grilled chicken and fresh salad.', 'price' => 'GHC 45.00', 'icon' => 'bi-basket'],
    ['name' => 'Fried Rice', 'category' => 'Main Dishes', 'desc' => 'Special fried rice with mixed vegetables, beef, and signature sauce.', 'price' => 'GHC 42.00', 'icon' => 'bi-egg-fried'],
    ['name' => 'Banku & Tilapia', 'category' => 'Local Dishes', 'desc' => 'Traditional banku served with grilled tilapia and hot pepper.', 'price' => 'GHC 40.00', 'icon' => 'bi-flower1'],
    ['name' => 'Grilled Chicken', 'category' => 'Grills', 'desc' => 'Well-seasoned grilled chicken with chips and crisp coleslaw.', 'price' => 'GHC 38.00', 'icon' => 'bi-fire'],
    ['name' => 'Chicken Wings', 'category' => 'Starters', 'desc' => 'Crispy fried chicken wings tossed in spicy house sauce.', 'price' => 'GHC 25.00', 'icon' => 'bi-stars'],
    ['name' => 'Sobolo Drink', 'category' => 'Drinks', 'desc' => 'Refreshing hibiscus drink served chilled with natural spices.', 'price' => 'GHC 10.00', 'icon' => 'bi-cup-straw'],
];

ob_start();
?>
<section class="af-menu-hero">
    <div class="af-menu-hero-inner">
        <nav aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Menu</span>
        </nav>
        <p class="af-kicker">Freshly Prepared</p>
        <h1>Explore Our <span>Menu</span></h1>
        <p>Choose from rich Ghanaian dishes, grilled favourites, refreshing drinks, and event-ready meals prepared with care.</p>
    </div>
</section>

<section class="af-menu-page">
    <header class="af-page-heading">
        <p>Our Food Selection</p>
        <h2>Popular Menu Items</h2>
        <small>Browse customer favourites and order meals for dine-in, delivery, or private events.</small>
    </header>

    <form class="af-menu-toolbar" action="menu.php" method="get">
        <label class="search" for="public_menu_search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="public_menu_search" name="search" placeholder="Search menu items...">
        </label>
        <label class="select" for="public_menu_category">
            <select id="public_menu_category" name="category">
                <option>All Categories</option>
                <option>Main Dishes</option>
                <option>Local Dishes</option>
                <option>Grills</option>
                <option>Drinks</option>
            </select>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </label>
        <label class="select" for="public_menu_sort">
            <select id="public_menu_sort" name="sort">
                <option>Popular</option>
                <option>Price: Low to High</option>
                <option>Price: High to Low</option>
            </select>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </label>
    </form>

    <nav class="af-menu-category-tabs" aria-label="Menu categories">
        <?php foreach ($categories as $index => $category): ?>
            <a class="<?php echo $index === 0 ? 'is-active' : ''; ?>" href="menu.php?category=<?php echo urlencode($category); ?>">
                <i class="bi <?php echo $index === 0 ? 'bi-grid' : 'bi-circle-fill'; ?>" aria-hidden="true"></i>
                <?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="af-public-menu-grid">
        <?php foreach ($menuItems as $item): ?>
            <article class="af-public-menu-card">
                <div class="af-menu-image">
                    <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <span><i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                </div>
                <div class="af-public-menu-card-body">
                    <h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?php echo htmlspecialchars($item['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <footer class="af-menu-card-footer">
                        <strong class="af-menu-price"><?php echo htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <a class="af-menu-order-btn" href="order.php">Order Now <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </footer>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <section class="af-menu-strip">
        <div>
            <h2>Need Catering for a Group?</h2>
            <p>Let AfriSense prepare generous portions, buffet trays, and custom menu packages for your meeting, party, or celebration.</p>
        </div>
        <a class="af-menu-order-btn" href="booking.php">Book a Service <i class="bi bi-calendar3" aria-hidden="true"></i></a>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
