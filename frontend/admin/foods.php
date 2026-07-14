<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Menu Items | AfriSense';
$adminTitle = 'Menu';
$activeAdminPage = 'menu_items';
$extraStyles = [$frontendBase . '/assets/css/admin-menu.css'];

$menuItems = [
    ['name' => 'Jollof Rice', 'desc' => 'Spicy jollof rice served with grilled chicken and salad.', 'category' => 'Main Dishes', 'price' => 'GHc 45.00', 'status' => 'Active', 'tag' => 'main'],
    ['name' => 'Fried Rice', 'desc' => 'Special fried rice with mixed vegetables and beef.', 'category' => 'Main Dishes', 'price' => 'GHc 42.00', 'status' => 'Active', 'tag' => 'main'],
    ['name' => 'Banku & Tilapia', 'desc' => 'Traditional banku served with grilled tilapia and pepper.', 'category' => 'Local Dishes', 'price' => 'GHc 40.00', 'status' => 'Active', 'tag' => 'local'],
    ['name' => 'Grilled Chicken', 'desc' => 'Well seasoned grilled chicken with chips and coleslaw.', 'category' => 'Grills', 'price' => 'GHc 38.00', 'status' => 'Active', 'tag' => 'grills'],
    ['name' => 'Beef Waakye', 'desc' => 'Waakye with tender beef, gari and wele stew.', 'category' => 'Local Dishes', 'price' => 'GHc 30.00', 'status' => 'Active', 'tag' => 'local'],
    ['name' => 'Chicken Wings', 'desc' => 'Crispy fried chicken wings with spicy sauce.', 'category' => 'Starters', 'price' => 'GHc 25.00', 'status' => 'Active', 'tag' => 'starters'],
    ['name' => 'Fruit Salad', 'desc' => 'Fresh seasonal fruits with honey and yogurt.', 'category' => 'Salads', 'price' => 'GHc 20.00', 'status' => 'Active', 'tag' => 'salads'],
    ['name' => 'Sobolo Drink', 'desc' => 'Refreshing hibiscus drink served chilled.', 'category' => 'Drinks', 'price' => 'GHc 10.00', 'status' => 'Active', 'tag' => 'drinks'],
    ['name' => 'Zobo Drink', 'desc' => 'Sweet and refreshing zobo drink.', 'category' => 'Drinks', 'price' => 'GHc 10.00', 'status' => 'Out of Stock', 'tag' => 'drinks'],
    ['name' => 'Grilled Tilapia', 'desc' => 'Fresh tilapia grilled to perfection with spices.', 'category' => 'Grills', 'price' => 'GHc 35.00', 'status' => 'Active', 'tag' => 'grills'],
];

$categories = [
    ['name' => 'Main Dishes', 'count' => 28, 'icon' => 'bi-basket', 'class' => 'green'],
    ['name' => 'Local Dishes', 'count' => 22, 'icon' => 'bi-bag-heart', 'class' => 'gold'],
    ['name' => 'Grills', 'count' => 18, 'icon' => 'bi-scissors', 'class' => 'red'],
    ['name' => 'Starters', 'count' => 16, 'icon' => 'bi-cup-straw', 'class' => 'purple'],
    ['name' => 'Salads', 'count' => 12, 'icon' => 'bi-flower1', 'class' => 'green'],
    ['name' => 'Drinks', 'count' => 20, 'icon' => 'bi-cup', 'class' => 'blue'],
    ['name' => 'Soups', 'count' => 8, 'icon' => 'bi-cup-hot', 'class' => 'orange'],
    ['name' => 'Desserts', 'count' => 4, 'icon' => 'bi-cake2', 'class' => 'red'],
];

ob_start();
?>
<section class="af-admin-menu-page">
    <header class="af-admin-page-heading">
        <div>
            <h1>Menu Items</h1>
            <p>Manage all your food and drink items. Add, edit or remove items from your menu.</p>
        </div>
        <button class="af-add-menu-btn" type="button">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            Add New Menu Item
        </button>
    </header>

    <section class="af-menu-metrics" aria-label="Menu summary">
        <article class="green">
            <span><i class="bi bi-basket" aria-hidden="true"></i></span>
            <div><small>Total Menu Items</small><strong>128</strong><p>+ 12 this week</p></div>
        </article>
        <article class="gold">
            <span><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
            <div><small>Active Items</small><strong>115</strong><p>89.8% of total</p></div>
        </article>
        <article class="blue">
            <span><i class="bi bi-people" aria-hidden="true"></i></span>
            <div><small>Categories</small><strong>12</strong><p>+ 2 this week</p></div>
        </article>
        <article class="purple">
            <span><i class="bi bi-bag-x" aria-hidden="true"></i></span>
            <div><small>Out of Stock</small><strong>13</strong><p>10.2% of total</p></div>
        </article>
    </section>

    <section class="af-menu-workspace">
        <div class="af-menu-main">
            <form class="af-menu-filters" action="#" method="get">
                <label class="af-menu-search" for="menu_search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="menu_search" name="search" placeholder="Search menu items...">
                </label>
                <label class="af-menu-select" for="category_filter">
                    <select id="category_filter" name="category">
                        <option>All Categories</option>
                        <option>Main Dishes</option>
                        <option>Local Dishes</option>
                        <option>Drinks</option>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
                <label class="af-menu-select" for="status_filter">
                    <select id="status_filter" name="status">
                        <option>All Status</option>
                        <option>Active</option>
                        <option>Out of Stock</option>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
                <button type="submit"><i class="bi bi-filter" aria-hidden="true"></i> Filter</button>
            </form>

            <section class="af-menu-table-card">
                <div class="af-menu-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menuItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="af-menu-item-cell">
                                            <img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="">
                                            <span>
                                                <strong><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <small><?php echo htmlspecialchars($item['desc'], ENT_QUOTES, 'UTF-8'); ?></small>
                                            </span>
                                        </div>
                                    </td>
                                    <td><span class="af-menu-tag <?php echo htmlspecialchars($item['tag'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?php echo htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="af-status <?php echo $item['status'] === 'Out of Stock' ? 'out' : 'active'; ?>"><?php echo htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td>
                                        <div class="af-row-actions">
                                            <button type="button" aria-label="Edit <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                                            <button class="danger" type="button" aria-label="Delete <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <footer class="af-menu-pagination">
                    <p>Showing 1 to 10 of 128 items</p>
                    <nav aria-label="Menu pagination">
                        <a href="#" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                        <a class="active" href="#">1</a>
                        <a href="#">2</a>
                        <a href="#">3</a>
                        <span>...</span>
                        <a href="#">13</a>
                        <a href="#" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                    </nav>
                </footer>
            </section>
        </div>

        <aside class="af-menu-side">
            <section class="af-menu-panel">
                <h2>Categories</h2>
                <ul class="af-category-list">
                    <?php foreach ($categories as $category): ?>
                        <li>
                            <i class="bi <?php echo htmlspecialchars($category['icon'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($category['class'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <strong><?php echo htmlspecialchars((string) $category['count'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button class="af-manage-categories" type="button"><i class="bi bi-gear" aria-hidden="true"></i> Manage Categories</button>
            </section>

            <section class="af-menu-panel">
                <h2>Quick Actions</h2>
                <div class="af-menu-actions">
                    <button type="button"><i class="bi bi-plus-lg green" aria-hidden="true"></i> Add New Menu Item</button>
                    <button type="button"><i class="bi bi-upload green" aria-hidden="true"></i> Import Menu Items</button>
                    <button type="button"><i class="bi bi-download gold" aria-hidden="true"></i> Export Menu Items</button>
                    <button type="button"><i class="bi bi-arrow-repeat blue" aria-hidden="true"></i> Bulk Update Prices</button>
                    <button type="button"><i class="bi bi-list-ol green" aria-hidden="true"></i> Reorder Items</button>
                </div>
            </section>

            <section class="af-menu-panel">
                <h2>Menu Overview</h2>
                <div class="af-menu-donut">
                    <strong>128</strong>
                    <span>Total Items</span>
                </div>
                <ul class="af-overview-list">
                    <li><i class="main"></i>Main Dishes <span>28 (21.9%)</span></li>
                    <li><i class="local"></i>Local Dishes <span>22 (17.2%)</span></li>
                    <li><i class="grills"></i>Grills <span>18 (14.1%)</span></li>
                    <li><i class="starters"></i>Starters <span>16 (12.5%)</span></li>
                    <li><i class="salads"></i>Salads <span>12 (9.4%)</span></li>
                    <li><i class="drinks"></i>Drinks <span>20 (15.6%)</span></li>
                    <li><i class="soups"></i>Soups <span>8 (6.2%)</span></li>
                    <li><i class="desserts"></i>Desserts <span>4 (3.1%)</span></li>
                </ul>
            </section>
        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
