<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Gallery | AfriSense';
$adminTitle = 'Gallery';
$activeAdminPage = 'gallery';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

$authUser = afrisense_require_admin();

function afrisense_gallery_post(string $key, string $fallback = ''): string
{
    return trim((string) ($_POST[$key] ?? $fallback));
}

function afrisense_gallery_category_class(string $category): string
{
    return match (strtolower($category)) {
        'events', 'event' => 'events',
        'services', 'service' => 'services',
        'team' => 'team',
        'drinks', 'drink' => 'drinks',
        'soups', 'soup' => 'soups',
        'packages', 'package' => 'packages',
        default => 'food',
    };
}

/**
 * @return array{url: string, path: string|null, size: int}
 */
function afrisense_gallery_image_info(string $frontendBase, ?string $image): array
{
    $image = trim((string) $image);
    $relativeImage = ltrim(str_replace('\\', '/', $image), '/');
    $filename = basename($relativeImage);
    $candidates = [];

    if ($relativeImage !== '') {
        $candidates[] = [
            'path' => __DIR__ . '/../uploads/' . $relativeImage,
            'url' => $frontendBase . '/uploads/' . $relativeImage,
        ];
        $candidates[] = [
            'path' => __DIR__ . '/../uploads/' . $filename,
            'url' => $frontendBase . '/uploads/' . $filename,
        ];
        $candidates[] = [
            'path' => __DIR__ . '/../assets/images/foods/' . $filename,
            'url' => $frontendBase . '/assets/images/foods/' . $filename,
        ];
        $candidates[] = [
            'path' => __DIR__ . '/../assets/images/' . $filename,
            'url' => $frontendBase . '/assets/images/' . $filename,
        ];
    }

    foreach ($candidates as $candidate) {
        if (is_file($candidate['path'])) {
            return [
                'url' => $candidate['url'],
                'path' => $candidate['path'],
                'size' => (int) filesize($candidate['path']),
            ];
        }
    }

    $fallbackPath = __DIR__ . '/../assets/images/foods/jollof-rice.png';

    return [
        'url' => $frontendBase . '/assets/images/foods/jollof-rice.png',
        'path' => is_file($fallbackPath) ? $fallbackPath : null,
        'size' => is_file($fallbackPath) ? (int) filesize($fallbackPath) : 0,
    ];
}

function afrisense_gallery_upload_image(array $file): string
{
    if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Choose an image to upload.');
    }

    if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('One of the gallery images could not be uploaded.');
    }

    if ((int) ($file['size'] ?? 0) > 6 * 1024 * 1024) {
        throw new RuntimeException('Gallery images must be 6MB or smaller.');
    }

    $temporaryName = (string) ($file['tmp_name'] ?? '');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo !== false ? (string) finfo_file($finfo, $temporaryName) : '';

    if ($finfo !== false) {
        finfo_close($finfo);
    }

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new RuntimeException('Gallery images must be JPG, PNG, WebP or GIF.');
    }

    $uploadDirectory = __DIR__ . '/../uploads/gallery';

    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
        throw new RuntimeException('Gallery upload folder could not be created.');
    }

    $filename = 'gallery-' . bin2hex(random_bytes(12)) . '.' . $allowedMimeTypes[$mimeType];
    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($temporaryName, $destination)) {
        throw new RuntimeException('Gallery image could not be saved.');
    }

    return 'gallery/' . $filename;
}

/**
 * @return list<array{name: string, tmp_name: string, size: int, error: int}>
 */
function afrisense_gallery_uploaded_files(): array
{
    $files = $_FILES['gallery_images'] ?? null;

    if (!is_array($files) || !isset($files['name'])) {
        return [];
    }

    if (!is_array($files['name'])) {
        return [[
            'name' => (string) ($files['name'] ?? ''),
            'tmp_name' => (string) ($files['tmp_name'] ?? ''),
            'size' => (int) ($files['size'] ?? 0),
            'error' => (int) ($files['error'] ?? UPLOAD_ERR_NO_FILE),
        ]];
    }

    $normalized = [];

    foreach ($files['name'] as $index => $name) {
        $normalized[] = [
            'name' => (string) $name,
            'tmp_name' => (string) ($files['tmp_name'][$index] ?? ''),
            'size' => (int) ($files['size'][$index] ?? 0),
            'error' => (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE),
        ];
    }

    return $normalized;
}

function afrisense_gallery_format_size(int $bytes): string
{
    if ($bytes >= 1024 * 1024 * 1024) {
        return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }

    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 1) . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 0) . ' KB';
    }

    return $bytes . ' B';
}

$search = trim((string) ($_GET['search'] ?? ''));
$categoryFilter = trim((string) ($_GET['category'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? 'newest'));
$flashMessage = '';
$flashType = 'success';

try {
    $pdo = afrisense_pdo();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'upload_images') {
        $title = afrisense_gallery_post('title');
        $description = afrisense_gallery_post('description');
        $category = afrisense_gallery_post('category', 'Food');
        $validCategories = ['Food', 'Events', 'Services', 'Team'];

        if (!in_array($category, $validCategories, true)) {
            $category = 'Food';
        }

        $files = afrisense_gallery_uploaded_files();

        if ($files === []) {
            $flashType = 'error';
            $flashMessage = 'Choose at least one gallery image.';
        } else {
            $created = 0;

            foreach ($files as $index => $file) {
                if ((int) $file['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $imagePath = afrisense_gallery_upload_image($file);
                $imageTitle = $title !== '' ? $title : pathinfo((string) $file['name'], PATHINFO_FILENAME);

                if (count($files) > 1 && $title !== '') {
                    $imageTitle .= ' ' . ($index + 1);
                }

                $statement = $pdo->prepare(
                    'INSERT INTO `gallery`
                        (`title`, `description`, `category`, `image`, `uploaded_by`)
                     VALUES
                        (:title, :description, :category, :image, :uploaded_by)'
                );
                $statement->execute([
                    'title' => $imageTitle,
                    'description' => $description,
                    'category' => $category,
                    'image' => $imagePath,
                    'uploaded_by' => (int) ($authUser['id'] ?? 0),
                ]);
                $created++;
            }

            $flashMessage = $created > 0 ? $created . ' image(s) uploaded to the gallery.' : 'No image was uploaded.';
            $flashType = $created > 0 ? 'success' : 'error';
        }
    }

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(g.`title` LIKE :search OR g.`description` LIKE :search OR g.`category` LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    if ($categoryFilter !== '') {
        $where[] = 'g.`category` = :category';
        $params['category'] = $categoryFilter;
    }

    $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';
    $orderSql = $sort === 'oldest' ? 'ORDER BY g.`uploaded_at` ASC, g.`id` ASC' : 'ORDER BY g.`uploaded_at` DESC, g.`id` DESC';

    $galleryStatement = $pdo->prepare(
        'SELECT
            g.`id`,
            g.`title`,
            g.`description`,
            g.`category`,
            g.`image`,
            g.`uploaded_at`,
            COALESCE(u.`fullname`, "Admin User") AS uploaded_by_name
         FROM `gallery` g
         LEFT JOIN `users` u ON u.`id` = g.`uploaded_by`
         ' . $whereSql . '
         ' . $orderSql . '
         LIMIT 60'
    );
    $galleryStatement->execute($params);
    $galleryItems = $galleryStatement->fetchAll(PDO::FETCH_ASSOC);

    $foodParams = [];
    $foodWhere = ['1 = 1'];

    if ($search !== '') {
        $foodWhere[] = '(f.`food_name` LIKE :food_search OR f.`description` LIKE :food_search OR COALESCE(c.`category_name`, "") LIKE :food_search)';
        $foodParams['food_search'] = '%' . $search . '%';
    }

    if ($categoryFilter === 'Food') {
        $foodWhere[] = '1 = 1';
    } elseif ($categoryFilter !== '') {
        $foodWhere[] = 'COALESCE(c.`category_name`, "") = :food_category';
        $foodParams['food_category'] = $categoryFilter;
    }

    $foodStatement = $pdo->prepare(
        'SELECT
            f.`id`,
            f.`food_name` AS title,
            f.`description`,
            COALESCE(c.`category_name`, "Food") AS category,
            f.`image`,
            f.`created_at` AS uploaded_at
         FROM `foods` f
         LEFT JOIN `food_categories` c ON c.`id` = f.`category_id`
         WHERE ' . implode(' AND ', $foodWhere) . '
         ORDER BY f.`created_at` DESC, f.`id` DESC
         LIMIT 60'
    );
    $foodStatement->execute($foodParams);
    $foodItems = $foodStatement->fetchAll(PDO::FETCH_ASSOC);

    $categoryStatement = $pdo->prepare(
        'SELECT `category` AS category_name, COUNT(*) AS item_count
         FROM `gallery`
         GROUP BY `category`
         UNION
         SELECT COALESCE(c.`category_name`, "Food") AS category_name, COUNT(f.`id`) AS item_count
         FROM `foods` f
         LEFT JOIN `food_categories` c ON c.`id` = f.`category_id`
         GROUP BY COALESCE(c.`category_name`, "Food")
         ORDER BY category_name ASC'
    );
    $categoryStatement->execute();
    $categories = $categoryStatement->fetchAll(PDO::FETCH_ASSOC);

    $combinedItems = [];

    foreach ($galleryItems as $item) {
        $item['source'] = 'Gallery';
        $combinedItems[] = $item;
    }

    foreach ($foodItems as $item) {
        $item['source'] = 'Food Menu';
        $combinedItems[] = $item;
    }

    $displayItems = array_slice($combinedItems, 0, 60);
    $totalGalleryImages = count($combinedItems);
    $albums = count(array_unique(array_map(static fn (array $item): string => (string) ($item['category'] ?? 'Food'), $combinedItems)));
    $tags = count($categories);
    $storageBytes = 0;

    foreach ($combinedItems as $item) {
        $storageBytes += afrisense_gallery_image_info($frontendBase, (string) ($item['image'] ?? ''))['size'];
    }

    $loadError = '';
} catch (Throwable $exception) {
    $displayItems = [];
    $categories = [];
    $totalGalleryImages = 0;
    $albums = 0;
    $tags = 0;
    $storageBytes = 0;
    $loadError = 'Gallery could not be loaded. Check that MySQL is running.';
}

ob_start();
?>
<section class="af-admin-menu-page af-gallery-page">
    <header class="af-admin-page-heading af-gallery-heading">
        <div>
            <h1>Gallery</h1>
            <p>Dashboard / Gallery</p>
        </div>
        <div class="af-gallery-actions">
            <a href="#gallery_upload_form"><i class="bi bi-folder-plus" aria-hidden="true"></i> Create Album</a>
            <a class="af-add-menu-btn" href="#gallery_upload_form"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Upload Images</a>
        </div>
    </header>

    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section class="af-menu-metrics af-gallery-metrics" aria-label="Gallery summary">
        <article class="green">
            <span><i class="bi bi-images" aria-hidden="true"></i></span>
            <div><small>Total Images</small><strong><?php echo htmlspecialchars((string) $totalGalleryImages, ENT_QUOTES, 'UTF-8'); ?></strong><p>Gallery and menu images</p></div>
        </article>
        <article class="gold">
            <span><i class="bi bi-folder2-open" aria-hidden="true"></i></span>
            <div><small>Albums</small><strong><?php echo htmlspecialchars((string) $albums, ENT_QUOTES, 'UTF-8'); ?></strong><p>Grouped categories</p></div>
        </article>
        <article class="blue">
            <span><i class="bi bi-tags" aria-hidden="true"></i></span>
            <div><small>Tags</small><strong><?php echo htmlspecialchars((string) $tags, ENT_QUOTES, 'UTF-8'); ?></strong><p>Available labels</p></div>
        </article>
        <article class="purple">
            <span><i class="bi bi-hdd" aria-hidden="true"></i></span>
            <div><small>Storage Used</small><strong><?php echo htmlspecialchars(afrisense_gallery_format_size($storageBytes), ENT_QUOTES, 'UTF-8'); ?></strong><p>Local image files</p></div>
        </article>
    </section>

    <section class="af-menu-table-card af-gallery-upload-card" id="gallery_upload_form">
        <header class="af-table-toolbar">
            <h2>Upload Gallery Images</h2>
            <p>Add food, service, event or team images without changing the database structure.</p>
        </header>
        <form class="af-food-management-form af-gallery-upload-form" action="gallery.php#gallery_upload_form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_images">
            <label>
                <span>Image Title</span>
                <input type="text" name="title" placeholder="e.g. Wedding Setup">
            </label>
            <label>
                <span>Category</span>
                <select name="category">
                    <option value="Food">Food</option>
                    <option value="Events">Events</option>
                    <option value="Services">Services</option>
                    <option value="Team">Team</option>
                </select>
            </label>
            <label>
                <span>Description</span>
                <input type="text" name="description" placeholder="Short image description">
            </label>
            <label class="af-gallery-file-picker">
                <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
                <strong><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Choose images from device</strong>
                <small>JPG, PNG, WebP or GIF. Max 6MB each.</small>
            </label>
            <button type="submit"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Upload Images</button>
        </form>
    </section>

    <section class="af-menu-table-card">
        <form class="af-menu-filters af-gallery-filters" action="gallery.php" method="get">
            <label class="af-menu-search" for="gallery_search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="gallery_search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search images...">
            </label>
            <label class="af-menu-select" for="gallery_category">
                <select id="gallery_category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <?php $categoryName = (string) ($category['category_name'] ?? 'Food'); ?>
                        <option value="<?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $categoryFilter === $categoryName ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </label>
            <label class="af-menu-select" for="gallery_sort">
                <select id="gallery_sort" name="sort">
                    <option value="newest" <?php echo $sort !== 'oldest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                </select>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </label>
            <button type="submit"><i class="bi bi-filter" aria-hidden="true"></i> Filter</button>
            <a href="gallery.php"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Reset</a>
        </form>

        <section class="af-gallery-grid" aria-label="Gallery images">
            <?php if ($displayItems === []): ?>
                <div class="af-empty-state">No gallery images found.</div>
            <?php endif; ?>

            <?php foreach ($displayItems as $item): ?>
                <?php
                $imageInfo = afrisense_gallery_image_info($frontendBase, (string) ($item['image'] ?? ''));
                $uploadedAt = strtotime((string) ($item['uploaded_at'] ?? '')) ?: time();
                $category = (string) ($item['category'] ?? 'Food');
                ?>
                <article class="af-gallery-card">
                    <div class="af-gallery-image-wrap">
                        <input type="checkbox" aria-label="Select <?php echo htmlspecialchars((string) ($item['title'] ?? 'image'), ENT_QUOTES, 'UTF-8'); ?>">
                        <img src="<?php echo htmlspecialchars($imageInfo['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($item['title'] ?? 'Gallery image'), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="button" aria-label="Image actions"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>
                    </div>
                    <div class="af-gallery-card-body">
                        <strong><?php echo htmlspecialchars((string) ($item['title'] ?? 'Gallery image'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?php echo htmlspecialchars(date('M j, Y', $uploadedAt), ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars(afrisense_gallery_format_size($imageInfo['size']), ENT_QUOTES, 'UTF-8'); ?></small>
                        <span class="af-gallery-tag <?php echo htmlspecialchars(afrisense_gallery_category_class($category), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <footer class="af-menu-pagination">
            <p>Showing 1 to <?php echo htmlspecialchars((string) count($displayItems), ENT_QUOTES, 'UTF-8'); ?> of <?php echo htmlspecialchars((string) $totalGalleryImages, ENT_QUOTES, 'UTF-8'); ?> images</p>
            <nav aria-label="Gallery pagination">
                <a href="#" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                <a class="active" href="#">1</a>
                <a href="#">2</a>
                <a href="#">3</a>
                <span>...</span>
                <a href="#" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            </nav>
        </footer>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
