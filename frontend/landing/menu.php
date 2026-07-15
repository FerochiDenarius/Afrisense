<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Menu | AfriSense';
$activePage = 'menu';
$extraStyles = [$frontendBase . '/assets/css/menu-services.css'];
$foodImageBase = $frontendBase . '/assets/images/foods';

require_once __DIR__ . '/../auth/auth_bootstrap.php';

function afrisense_menu_food_image(string $frontendBase, ?string $image): string
{
    $image = trim((string) $image);
    $filename = basename($image);

    if ($image !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
        return $frontendBase . '/assets/images/foods/' . $filename;
    }

    return $frontendBase . '/assets/images/foods/jollof-rice.png';
}

function afrisense_menu_icon(string $category): string
{
    $category = strtolower($category);

    return match (true) {
        str_contains($category, 'drink') => 'bi-cup-straw',
        str_contains($category, 'fast') => 'bi-stars',
        str_contains($category, 'local') => 'bi-flower1',
        str_contains($category, 'seafood') => 'bi-water',
        str_contains($category, 'dessert') => 'bi-cake2',
        default => 'bi-basket',
    };
}

$search = trim((string) ($_GET['search'] ?? ''));
$categoryFilter = trim((string) ($_GET['category'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? 'popular'));
$menuItems = [];
$categories = [];
$menuMessage = '';

try {
    $pdo = afrisense_pdo();

    $categoryStatement = $pdo->prepare(
        'SELECT c.`category_name`, COUNT(f.`id`) AS food_count
         FROM `food_categories` c
         INNER JOIN `foods` f ON f.`category_id` = c.`id` AND f.`availability` = :availability
         GROUP BY c.`id`, c.`category_name`
         ORDER BY c.`category_name` ASC'
    );
    $categoryStatement->execute(['availability' => 'Available']);
    $categories = $categoryStatement->fetchAll(PDO::FETCH_ASSOC);

    $where = ['f.`availability` = :availability'];
    $params = ['availability' => 'Available'];

    if ($search !== '') {
        $where[] = '(f.`food_name` LIKE :search OR f.`description` LIKE :search OR c.`category_name` LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    if ($categoryFilter !== '') {
        $where[] = 'c.`category_name` = :category';
        $params['category'] = $categoryFilter;
    }

    $orderBy = match ($sort) {
        'price_asc' => 'f.`price` ASC, f.`food_name` ASC',
        'price_desc' => 'f.`price` DESC, f.`food_name` ASC',
        'newest' => 'f.`created_at` DESC, f.`id` DESC',
        default => 'f.`id` ASC',
    };

    $foodStatement = $pdo->prepare(
        'SELECT
            f.`id`,
            f.`food_name`,
            f.`description`,
            f.`price`,
            f.`image`,
            c.`category_name`
         FROM `foods` f
         INNER JOIN `food_categories` c ON c.`id` = f.`category_id`
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY ' . $orderBy
    );
    $foodStatement->execute($params);
    $menuItems = $foodStatement->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    $menuMessage = 'Menu items could not be loaded. Check that MySQL is running.';
}

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
            <input type="search" id="public_menu_search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search menu items...">
        </label>
        <label class="select" for="public_menu_category">
            <select id="public_menu_category" name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <?php $categoryName = (string) $category['category_name']; ?>
                    <option value="<?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $categoryFilter === $categoryName ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </label>
        <label class="select" for="public_menu_sort">
            <select id="public_menu_sort" name="sort">
                <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Popular</option>
                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
            </select>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </label>
    </form>

    <nav class="af-menu-category-tabs" aria-label="Menu categories">
        <a class="<?php echo $categoryFilter === '' ? 'is-active' : ''; ?>" href="menu.php">
            <i class="bi bi-grid" aria-hidden="true"></i>
            All Menu
        </a>
        <?php foreach ($categories as $category): ?>
            <?php $categoryName = (string) $category['category_name']; ?>
            <a class="<?php echo $categoryFilter === $categoryName ? 'is-active' : ''; ?>" href="menu.php?category=<?php echo urlencode($categoryName); ?>">
                <i class="bi bi-circle-fill" aria-hidden="true"></i>
                <?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($menuMessage !== ''): ?>
        <div class="af-menu-empty"><?php echo htmlspecialchars($menuMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="af-public-menu-grid">
        <?php if ($menuItems === [] && $menuMessage === ''): ?>
            <div class="af-menu-empty">No menu items match your filters.</div>
        <?php endif; ?>
        <?php foreach ($menuItems as $item): ?>
            <?php
            $itemName = (string) ($item['food_name'] ?? 'Food item');
            $categoryName = (string) ($item['category_name'] ?? 'Menu');
            ?>
            <article class="af-public-menu-card">
                <div class="af-menu-image">
                    <img src="<?php echo htmlspecialchars(afrisense_menu_food_image($frontendBase, (string) ($item['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8'); ?>">
                    <span><i class="bi <?php echo htmlspecialchars(afrisense_menu_icon($categoryName), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                </div>
                <div class="af-public-menu-card-body">
                    <h3><?php echo htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?php echo htmlspecialchars((string) ($item['description'] ?? 'Freshly prepared AfriSense meal.'), ENT_QUOTES, 'UTF-8'); ?></p>
                    <footer class="af-menu-card-footer">
                        <strong class="af-menu-price">GHC <?php echo htmlspecialchars(number_format((float) ($item['price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <a class="af-menu-order-btn" href="order.php?food_id=<?php echo urlencode((string) ($item['id'] ?? 0)); ?>">Order Now <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
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
