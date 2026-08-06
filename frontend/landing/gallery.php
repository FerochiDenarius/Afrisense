<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Gallery | AfriSense';
$activePage = 'gallery';
$extraStyles = [$frontendBase . '/assets/css/public-gallery.css'];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

// Defines the afrisense_public_gallery_image helper used by this module.
function afrisense_public_gallery_image(string $frontendBase, ?string $image): string
{
    $image = trim((string) $image);
    $relativeImage = ltrim(str_replace('\\', '/', $image), '/');
    $filename = basename($relativeImage);

    // Guard this block so it only runs when the required condition is met.
    if ($filename !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
        return $frontendBase . '/assets/images/foods/' . $filename;
    }

    // Guard this block so it only runs when the required condition is met.
    if ($relativeImage !== '' && is_file(__DIR__ . '/../uploads/' . $relativeImage)) {
        return $frontendBase . '/uploads/' . $relativeImage;
    }

    // Guard this block so it only runs when the required condition is met.
    if ($filename !== '' && is_file(__DIR__ . '/../uploads/' . $filename)) {
        return $frontendBase . '/uploads/' . $filename;
    }

    return $frontendBase . '/assets/images/foods/jollof-rice.png';
}

// Defines the afrisense_public_gallery_tag_class helper used by this module.
function afrisense_public_gallery_tag_class(string $category): string
{
    $category = strtolower($category);

    return match (true) {
        str_contains($category, 'drink') => 'drinks',
        str_contains($category, 'soup') => 'soups',
        str_contains($category, 'event'), str_contains($category, 'cater') => 'events',
        str_contains($category, 'package') => 'packages',
        default => 'food',
    };
}

$search = trim((string) ($_GET['search'] ?? ''));
$categoryFilter = trim((string) ($_GET['category'] ?? ''));
$galleryItems = [];
$categories = [];
$galleryMessage = '';

// Run database/action work inside a guarded block so the page can fail gracefully.
try {
    $pdo = afrisense_pdo();

    $categoryStatement = $pdo->prepare(
        'SELECT category_name, SUM(item_count) AS item_count
         FROM (
            SELECT COALESCE(c.`category_name`, "Food") AS category_name, COUNT(f.`id`) AS item_count
            FROM `foods` f
            LEFT JOIN `food_categories` c ON c.`id` = f.`category_id`
            WHERE f.`availability` = :availability
            GROUP BY COALESCE(c.`category_name`, "Food")
            UNION ALL
            SELECT g.`category` AS category_name, COUNT(g.`id`) AS item_count
            FROM `gallery` g
            GROUP BY g.`category`
         ) grouped_categories
         GROUP BY category_name
         ORDER BY category_name ASC'
    );
    $categoryStatement->execute(['availability' => 'Available']);
    $categories = $categoryStatement->fetchAll(PDO::FETCH_ASSOC);

    $foodWhere = ['f.`availability` = :availability'];
    $foodParams = ['availability' => 'Available'];

    // Guard this block so it only runs when the required condition is met.
    if ($search !== '') {
        $foodWhere[] = '(f.`food_name` LIKE :food_search OR f.`description` LIKE :food_search OR COALESCE(c.`category_name`, "") LIKE :food_search)';
        $foodParams['food_search'] = '%' . $search . '%';
    }

    // Guard this block so it only runs when the required condition is met.
    if ($categoryFilter !== '') {
        $foodWhere[] = 'COALESCE(c.`category_name`, "") = :food_category';
        $foodParams['food_category'] = $categoryFilter;
    }

    $foodStatement = $pdo->prepare(
        'SELECT
            f.`food_name` AS title,
            f.`description`,
            COALESCE(c.`category_name`, "Food") AS category,
            f.`image`,
            f.`created_at` AS created_at,
            "Menu" AS source
         FROM `foods` f
         LEFT JOIN `food_categories` c ON c.`id` = f.`category_id`
         WHERE ' . implode(' AND ', $foodWhere) . '
         ORDER BY f.`created_at` DESC, f.`id` DESC
         LIMIT 80'
    );
    $foodStatement->execute($foodParams);
    $galleryItems = $foodStatement->fetchAll(PDO::FETCH_ASSOC);

    // Guard this block so it only runs when the required condition is met.
    if ($categoryFilter === '' || in_array($categoryFilter, ['Food', 'Events', 'Services', 'Team'], true)) {
        $galleryWhere = [];
        $galleryParams = [];

        // Guard this block so it only runs when the required condition is met.
        if ($search !== '') {
            $galleryWhere[] = '(g.`title` LIKE :gallery_search OR g.`description` LIKE :gallery_search OR g.`category` LIKE :gallery_search)';
            $galleryParams['gallery_search'] = '%' . $search . '%';
        }

        // Guard this block so it only runs when the required condition is met.
        if ($categoryFilter !== '') {
            $galleryWhere[] = 'g.`category` = :gallery_category';
            $galleryParams['gallery_category'] = $categoryFilter;
        }

        $galleryWhereSql = $galleryWhere !== [] ? 'WHERE ' . implode(' AND ', $galleryWhere) : '';
        $extraStatement = $pdo->prepare(
            'SELECT
                g.`title`,
                g.`description`,
                g.`category`,
                g.`image`,
                g.`uploaded_at` AS created_at,
                "Gallery" AS source
             FROM `gallery` g
             ' . $galleryWhereSql . '
             ORDER BY g.`uploaded_at` DESC, g.`id` DESC
             LIMIT 80'
        );
        $extraStatement->execute($galleryParams);
        $galleryItems = array_merge($extraStatement->fetchAll(PDO::FETCH_ASSOC), $galleryItems);
    }

    $galleryItems = array_slice($galleryItems, 0, 80);
} catch (Throwable $exception) {
    $galleryMessage = 'Gallery could not be loaded. Check that MySQL is running.';
}

ob_start();
?>
<!-- Page section for this part of the AfriSense interface. -->
<section class="af-public-gallery-hero">
    <div>
        <!-- Navigation links for this interface. -->
        <nav aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Gallery</span>
        </nav>
        <p>AfriSense Moments</p>
        <h1>Food, Drinks &amp; Event Gallery</h1>
        <span>See the meals, drinks, packages, and services available from AfriSense.</span>
    </div>
</section>

<!-- Page section for this part of the AfriSense interface. -->
<section class="af-public-gallery-page">
    <!-- Header block for this interface section. -->
    <header class="af-public-gallery-heading">
        <p>Browse Gallery</p>
        <h2>Freshly Prepared, Beautifully Served</h2>
        <small>Food Sold items and admin gallery uploads are shown from the same database-backed image source.</small>
    </header>

    <!-- Form block that submits this page workflow. -->
    <form class="af-public-gallery-toolbar" action="gallery.php" method="get">
        <label>
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search gallery...">
        </label>
        <select name="category">
            <option value="">All Categories</option>
            <?php // Render this conditional/dynamic template block. ?>
            <?php foreach ($categories as $category): ?>
                <?php $categoryName = (string) ($category['category_name'] ?? 'Food'); ?>
                <option value="<?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $categoryFilter === $categoryName ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit"><i class="bi bi-filter" aria-hidden="true"></i> Filter</button>
        <a href="gallery.php">Reset</a>
    </form>

    <?php // Render this conditional/dynamic template block. ?>
    <?php if ($galleryMessage !== ''): ?>
        <div class="af-public-gallery-empty"><?php echo htmlspecialchars($galleryMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="af-public-gallery-grid">
        <?php // Render this conditional/dynamic template block. ?>
        <?php if ($galleryItems === [] && $galleryMessage === ''): ?>
            <div class="af-public-gallery-empty">No gallery items match your filters.</div>
        <?php endif; ?>

        <?php // Render this conditional/dynamic template block. ?>
        <?php foreach ($galleryItems as $item): ?>
            <?php
            $category = (string) ($item['category'] ?? 'Food');
            $createdAt = strtotime((string) ($item['created_at'] ?? '')) ?: time();
            ?>
            <article class="af-public-gallery-card">
                <img src="<?php echo htmlspecialchars(afrisense_public_gallery_image($frontendBase, (string) ($item['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($item['title'] ?? 'Gallery item'), ENT_QUOTES, 'UTF-8'); ?>">
                <div>
                    <span class="<?php echo htmlspecialchars(afrisense_public_gallery_tag_class($category), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3><?php echo htmlspecialchars((string) ($item['title'] ?? 'Gallery item'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?php echo htmlspecialchars((string) ($item['description'] ?? 'AfriSense food service gallery image.'), ENT_QUOTES, 'UTF-8'); ?></p>
                    <small><?php echo htmlspecialchars((string) ($item['source'] ?? 'Gallery'), ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars(date('M j, Y', $createdAt), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
