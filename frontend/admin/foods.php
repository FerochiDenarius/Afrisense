<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Foods Sold | AfriSense';
$adminTitle = 'Foods';
$activeAdminPage = 'foods';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

require_once __DIR__ . '/../auth/auth_bootstrap.php';

afrisense_require_admin();
$itemsPerPage = afrisense_admin_items_per_page();

function afrisense_post_string(string $key, string $fallback = ''): string
{
    return trim((string) ($_POST[$key] ?? $fallback));
}

function afrisense_food_tag_class(string $category): string
{
    $category = strtolower($category);

    return match (true) {
        str_contains($category, 'main') => 'main',
        str_contains($category, 'rice'), str_contains($category, 'local') => 'local',
        str_contains($category, 'drink') => 'drinks',
        str_contains($category, 'dessert') => 'starters',
        default => 'salads',
    };
}

function afrisense_food_image(string $frontendBase, ?string $image): string
{
    $image = trim((string) $image);

    if ($image === '') {
        return $frontendBase . '/assets/images/foods/jollof-rice.png';
    }

    $relativeImage = ltrim(str_replace('\\', '/', $image), '/');
    $filename = basename($relativeImage);
    $assetCandidate = __DIR__ . '/../assets/images/foods/' . $filename;
    $uploadCandidate = __DIR__ . '/../uploads/' . $relativeImage;
    $legacyUploadCandidate = __DIR__ . '/../uploads/' . $filename;

    if (is_file($assetCandidate)) {
        return $frontendBase . '/assets/images/foods/' . $filename;
    }

    if (is_file($uploadCandidate)) {
        return $frontendBase . '/uploads/' . $relativeImage;
    }

    if (is_file($legacyUploadCandidate)) {
        return $frontendBase . '/uploads/' . $filename;
    }

    return $frontendBase . '/assets/images/foods/jollof-rice.png';
}

function afrisense_food_url(array $overrides = [], string $anchor = ''): string
{
    $params = $_GET;

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = (string) $value;
        }
    }

    $query = http_build_query($params);

    return 'foods.php' . ($query !== '' ? '?' . $query : '') . $anchor;
}

function afrisense_food_upload_image(): ?string
{
    $file = $_FILES['food_image'] ?? null;

    if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Food image could not be uploaded.');
    }

    if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Food image must be 5MB or smaller.');
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
        throw new RuntimeException('Food image must be JPG, PNG, WebP or GIF.');
    }

    $uploadRoot = __DIR__ . '/../uploads';
    $uploadDirectory = $uploadRoot . '/foods';

    foreach ([$uploadRoot, $uploadDirectory] as $directory) {
        if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
            throw new RuntimeException('Food image upload folder could not be created.');
        }

        if (!is_writable($directory)) {
            throw new RuntimeException('Food image upload folder is not writable by XAMPP.');
        }
    }

    $filename = 'food-' . bin2hex(random_bytes(12)) . '.' . $allowedMimeTypes[$mimeType];
    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($temporaryName, $destination)) {
        throw new RuntimeException('Food image could not be saved.');
    }

    return 'foods/' . $filename;
}

$flashMessage = '';
$flashType = 'success';

try {
    $pdo = afrisense_pdo();
    $editFoodId = max(0, (int) ($_GET['edit'] ?? 0));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $itemsPerPage;

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = afrisense_post_string('action');

        if ($action === 'add_category') {
            $categoryName = afrisense_post_string('category_name');
            $categoryDescription = afrisense_post_string('category_description');

            if ($categoryName === '') {
                $flashType = 'error';
                $flashMessage = 'Category name is required.';
            } else {
                $duplicate = $pdo->prepare(
                    'SELECT COUNT(*) AS count_value
                     FROM `food_categories`
                     WHERE LOWER(`category_name`) = LOWER(:category_name)'
                );
                $duplicate->execute(['category_name' => $categoryName]);
                $duplicateRow = $duplicate->fetch(PDO::FETCH_ASSOC);

                if ((int) ($duplicateRow['count_value'] ?? 0) > 0) {
                    $flashType = 'error';
                    $flashMessage = 'This food category already exists.';
                } else {
                    $statement = $pdo->prepare(
                        'INSERT INTO `food_categories` (`category_name`, `description`)
                         VALUES (:category_name, :description)'
                    );
                    $statement->execute([
                        'category_name' => $categoryName,
                        'description' => $categoryDescription,
                    ]);
                    $flashMessage = 'Food category added.';
                }
            }
        }

        if ($action === 'add_food') {
            $foodName = afrisense_post_string('food_name');
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $price = (float) ($_POST['price'] ?? 0);
            $preparationTime = max(1, (int) ($_POST['preparation_time'] ?? 15));
            $availability = afrisense_post_string('availability', 'Available');

            if ($foodName === '' || $categoryId <= 0 || $price <= 0) {
                $flashType = 'error';
                $flashMessage = 'Food name, category and valid price are required.';
            } else {
                try {
                    $imagePath = afrisense_food_upload_image();
                    $statement = $pdo->prepare(
                        'INSERT INTO `foods`
                            (`category_id`, `food_name`, `description`, `price`, `image`, `preparation_time`, `availability`)
                         VALUES
                            (:category_id, :food_name, :description, :price, :image, :preparation_time, :availability)'
                    );
                    $statement->execute([
                        'category_id' => $categoryId,
                        'food_name' => $foodName,
                        'description' => afrisense_post_string('description'),
                        'price' => $price,
                        'image' => $imagePath,
                        'preparation_time' => $preparationTime,
                        'availability' => in_array($availability, ['Available', 'Unavailable'], true) ? $availability : 'Available',
                    ]);
                    $flashMessage = 'Food item added.';
                } catch (RuntimeException $exception) {
                    $flashType = 'error';
                    $flashMessage = $exception->getMessage();
                }
            }
        }

        if ($action === 'update_food') {
            $foodId = (int) ($_POST['food_id'] ?? 0);
            $foodName = afrisense_post_string('food_name');
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $price = (float) ($_POST['price'] ?? 0);
            $preparationTime = max(1, (int) ($_POST['preparation_time'] ?? 15));
            $availability = afrisense_post_string('availability', 'Available');

            if ($foodId <= 0 || $foodName === '' || $categoryId <= 0 || $price <= 0) {
                $flashType = 'error';
                $flashMessage = 'Food name, category and valid price are required.';
                $editFoodId = $foodId;
            } else {
                try {
                    $imagePath = afrisense_food_upload_image();

                    if ($imagePath !== null) {
                        $statement = $pdo->prepare(
                            'UPDATE `foods`
                             SET `category_id` = :category_id,
                                 `food_name` = :food_name,
                                 `description` = :description,
                                 `price` = :price,
                                 `image` = :image,
                                 `preparation_time` = :preparation_time,
                                 `availability` = :availability,
                                 `updated_at` = NOW()
                             WHERE `id` = :id'
                        );
                        $statement->execute([
                            'category_id' => $categoryId,
                            'food_name' => $foodName,
                            'description' => afrisense_post_string('description'),
                            'price' => $price,
                            'image' => $imagePath,
                            'preparation_time' => $preparationTime,
                            'availability' => in_array($availability, ['Available', 'Unavailable'], true) ? $availability : 'Available',
                            'id' => $foodId,
                        ]);
                    } else {
                        $statement = $pdo->prepare(
                            'UPDATE `foods`
                             SET `category_id` = :category_id,
                                 `food_name` = :food_name,
                                 `description` = :description,
                                 `price` = :price,
                                 `preparation_time` = :preparation_time,
                                 `availability` = :availability,
                                 `updated_at` = NOW()
                             WHERE `id` = :id'
                        );
                        $statement->execute([
                            'category_id' => $categoryId,
                            'food_name' => $foodName,
                            'description' => afrisense_post_string('description'),
                            'price' => $price,
                            'preparation_time' => $preparationTime,
                            'availability' => in_array($availability, ['Available', 'Unavailable'], true) ? $availability : 'Available',
                            'id' => $foodId,
                        ]);
                    }

                    $flashMessage = 'Food item updated.';
                    $editFoodId = $foodId;
                } catch (RuntimeException $exception) {
                    $flashType = 'error';
                    $flashMessage = $exception->getMessage();
                    $editFoodId = $foodId;
                }
            }
        }

        if ($action === 'toggle_availability') {
            $foodId = (int) ($_POST['food_id'] ?? 0);

            if ($foodId > 0) {
                $statement = $pdo->prepare(
                    'UPDATE `foods`
                     SET `availability` = CASE WHEN `availability` = "Available" THEN "Unavailable" ELSE "Available" END,
                         `updated_at` = NOW()
                     WHERE `id` = :id'
                );
                $statement->execute(['id' => $foodId]);
                $flashMessage = 'Food availability updated.';
            }
        }

        if ($action === 'delete_food') {
            $foodId = (int) ($_POST['food_id'] ?? 0);

            if ($foodId > 0) {
                $statement = $pdo->prepare('DELETE FROM `foods` WHERE `id` = :id');
                $statement->execute(['id' => $foodId]);
                $flashMessage = 'Food item deleted.';
                $editFoodId = 0;
            }
        }
    }

    $totalFoods = (int) $pdo->query('SELECT COUNT(*) FROM `foods`')->fetchColumn();
    $totalPages = max(1, (int) ceil($totalFoods / max(1, $itemsPerPage)));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $itemsPerPage;

    $statement = $pdo->prepare(
        'SELECT
            f.`id`,
            f.`food_name`,
            f.`description`,
            f.`price`,
            f.`image`,
            f.`preparation_time`,
            f.`availability`,
            COALESCE(c.`category_name`, \'Uncategorized\') AS category_name
         FROM `foods` f
         LEFT JOIN `food_categories` c ON c.`id` = f.`category_id`
         ORDER BY f.`id` DESC
         LIMIT ' . $itemsPerPage . ' OFFSET ' . $offset
    );
    $statement->execute();
    $foods = $statement->fetchAll(PDO::FETCH_ASSOC);

    $categoryStatement = $pdo->prepare(
        'SELECT
            c.`id`,
            c.`category_name`,
            c.`description`,
            COUNT(f.`id`) AS food_count
         FROM `food_categories` c
         LEFT JOIN `foods` f ON f.`category_id` = c.`id`
         GROUP BY c.`id`, c.`category_name`, c.`description`
         ORDER BY c.`category_name` ASC'
    );
    $categoryStatement->execute();
    $categories = $categoryStatement->fetchAll(PDO::FETCH_ASSOC);

    $selectedFood = null;

    if ($editFoodId > 0) {
        $editStatement = $pdo->prepare(
            'SELECT
                f.`id`,
                f.`category_id`,
                f.`food_name`,
                f.`description`,
                f.`price`,
                f.`image`,
                f.`preparation_time`,
                f.`availability`,
                COALESCE(c.`category_name`, "Uncategorized") AS category_name
             FROM `foods` f
             LEFT JOIN `food_categories` c ON c.`id` = f.`category_id`
             WHERE f.`id` = :id
             LIMIT 1'
        );
        $editStatement->execute(['id' => $editFoodId]);
        $selectedFood = $editStatement->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $loadError = '';
} catch (Throwable $exception) {
    $foods = [];
    $categories = [];
    $selectedFood = null;
    $editFoodId = 0;
    $totalFoods = 0;
    $totalPages = 1;
    $page = 1;
    $offset = 0;
    $loadError = 'Foods could not be loaded. Check that MySQL is running.';
}

$availableFoods = count(array_filter($foods, static fn (array $food): bool => (string) ($food['availability'] ?? '') === 'Available'));
$unavailableFoods = count(array_filter($foods, static fn (array $food): bool => (string) ($food['availability'] ?? '') !== 'Available'));
$fastPrepFoods = count(array_filter($foods, static fn (array $food): bool => (int) ($food['preparation_time'] ?? 0) <= 15));
$longPrepFoods = count(array_filter($foods, static fn (array $food): bool => (int) ($food['preparation_time'] ?? 0) >= 25));

ob_start();
?>
<section class="af-admin-menu-page af-foods-page">
    <header class="af-admin-page-heading">
        <div>
            <h1>Foods Sold</h1>
            <p>Manage the food items sold by AfriSense, their categories, prices and availability.</p>
        </div>
        <button class="af-add-menu-btn" type="submit" form="add_food_form">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            Add Food
        </button>
    </header>

    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="af-menu-metrics af-food-metrics" aria-label="Food summary">
        <article class="green">
            <span><i class="bi bi-fork-knife" aria-hidden="true"></i></span>
            <div><small>Total Foods</small><strong><?php echo htmlspecialchars((string) $totalFoods, ENT_QUOTES, 'UTF-8'); ?></strong><p>All foods in menu</p></div>
        </article>
        <article class="gold">
            <span><i class="bi bi-bell" aria-hidden="true"></i></span>
            <div><small>Available</small><strong><?php echo htmlspecialchars((string) $availableFoods, ENT_QUOTES, 'UTF-8'); ?></strong><p>Ready for orders</p></div>
        </article>
        <article class="red">
            <span><i class="bi bi-eye-slash" aria-hidden="true"></i></span>
            <div><small>Unavailable</small><strong><?php echo htmlspecialchars((string) $unavailableFoods, ENT_QUOTES, 'UTF-8'); ?></strong><p>Hidden from ordering</p></div>
        </article>
        <article class="blue">
            <span><i class="bi bi-stopwatch" aria-hidden="true"></i></span>
            <div><small>Fast Prep</small><strong><?php echo htmlspecialchars((string) $fastPrepFoods, ENT_QUOTES, 'UTF-8'); ?></strong><p>15 minutes or less</p></div>
        </article>
        <article class="purple">
            <span><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
            <div><small>Long Prep</small><strong><?php echo htmlspecialchars((string) $longPrepFoods, ENT_QUOTES, 'UTF-8'); ?></strong><p>25 minutes or more</p></div>
        </article>
    </section>

    <section class="af-foods-workspace">
        <section class="af-menu-table-card" id="foods-table">
            <form class="af-menu-filters af-foods-filters" action="foods.php" method="get">
                <label class="af-menu-search" for="food_search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="food_search" name="search" placeholder="Search foods...">
                </label>
                <label class="af-menu-select" for="food_category_filter">
                    <select id="food_category_filter" name="category">
                        <option>All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option><?php echo htmlspecialchars((string) $category['category_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
                <label class="af-menu-select" for="food_status_filter">
                    <select id="food_status_filter" name="status">
                        <option>All Status</option>
                        <option>Available</option>
                        <option>Unavailable</option>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
                <label class="af-menu-select" for="food_time_filter">
                    <select id="food_time_filter" name="prep">
                        <option>All Prep Times</option>
                        <option>Fast Prep</option>
                        <option>Long Prep</option>
                    </select>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </label>
                <button type="submit"><i class="bi bi-filter" aria-hidden="true"></i> Filter</button>
                <button type="button"><i class="bi bi-download" aria-hidden="true"></i> Export</button>
            </form>

            <div class="af-menu-table af-foods-table">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" aria-label="Select all foods"></th>
                            <th>Food</th>
                            <th>Category</th>
                            <th>Price (GHS)</th>
                            <th>Prep Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($foods === []): ?>
                            <tr>
                                <td colspan="7"><div class="af-empty-state">No foods found yet.</div></td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($foods as $food): ?>
                            <?php
                            $foodName = (string) ($food['food_name'] ?? 'Food item');
                            $categoryName = (string) ($food['category_name'] ?? 'Uncategorized');
                            $isAvailable = (string) ($food['availability'] ?? '') === 'Available';
                            ?>
                            <tr>
                                <td><input type="checkbox" aria-label="Select <?php echo htmlspecialchars($foodName, ENT_QUOTES, 'UTF-8'); ?>"></td>
                                <td>
                                    <div class="af-food-cell">
                                        <img src="<?php echo htmlspecialchars(afrisense_food_image($frontendBase, (string) ($food['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                                        <span>
                                            <strong><?php echo htmlspecialchars($foodName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <small><?php echo htmlspecialchars((string) ($food['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                                        </span>
                                    </div>
                                </td>
                                <td><span class="af-menu-tag <?php echo htmlspecialchars(afrisense_food_tag_class($categoryName), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo htmlspecialchars(number_format((float) ($food['price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ((int) ($food['preparation_time'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?> min</td>
                                <td><span class="af-status <?php echo $isAvailable ? 'active' : 'out'; ?>"><?php echo htmlspecialchars((string) ($food['availability'] ?? 'Unavailable'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td>
                                    <div class="af-row-actions">
                                        <a href="foods.php?edit=<?php echo htmlspecialchars((string) ($food['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>#edit_food_form" title="Edit food" aria-label="Edit <?php echo htmlspecialchars($foodName, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                        <form action="foods.php" method="post">
                                            <input type="hidden" name="action" value="toggle_availability">
                                            <input type="hidden" name="food_id" value="<?php echo htmlspecialchars((string) ($food['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                            <button class="<?php echo $isAvailable ? 'warning' : 'success'; ?>" type="submit" title="<?php echo $isAvailable ? 'Disable food' : 'Enable food'; ?>" aria-label="<?php echo $isAvailable ? 'Disable' : 'Enable'; ?> <?php echo htmlspecialchars($foodName, ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="bi <?php echo $isAvailable ? 'bi-slash-circle' : 'bi-check2-circle'; ?>" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                        <form action="foods.php" method="post">
                                            <input type="hidden" name="action" value="delete_food">
                                            <input type="hidden" name="food_id" value="<?php echo htmlspecialchars((string) ($food['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                            <button class="danger" type="submit" title="Delete food" aria-label="Delete <?php echo htmlspecialchars($foodName, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <footer class="af-menu-pagination">
                <p>Showing <?php echo htmlspecialchars((string) ($totalFoods > 0 ? $offset + 1 : 0), ENT_QUOTES, 'UTF-8'); ?> to <?php echo htmlspecialchars((string) min($offset + count($foods), $totalFoods), ENT_QUOTES, 'UTF-8'); ?> of <?php echo htmlspecialchars((string) $totalFoods, ENT_QUOTES, 'UTF-8'); ?> foods</p>
                <nav aria-label="Food pagination">
                    <a class="<?php echo $page <= 1 ? 'is-disabled' : ''; ?>" href="<?php echo htmlspecialchars($page <= 1 ? '#' : afrisense_food_url(['page' => (string) ($page - 1), 'edit' => null], '#foods-table'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Previous page" title="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                    <?php for ($number = max(1, $page - 1); $number <= min($totalPages, $page + 1); $number++): ?>
                        <a class="<?php echo $number === $page ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(afrisense_food_url(['page' => (string) $number, 'edit' => null], '#foods-table'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $number, ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endfor; ?>
                    <?php if ($totalPages > $page + 1): ?>
                        <span>...</span>
                        <a href="<?php echo htmlspecialchars(afrisense_food_url(['page' => (string) $totalPages, 'edit' => null], '#foods-table'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $totalPages, ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endif; ?>
                    <a class="<?php echo $page >= $totalPages ? 'is-disabled' : ''; ?>" href="<?php echo htmlspecialchars($page >= $totalPages ? '#' : afrisense_food_url(['page' => (string) ($page + 1), 'edit' => null], '#foods-table'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Next page" title="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                </nav>
            </footer>
        </section>

        <aside class="af-foods-side">
            <section class="af-menu-panel af-stock-panel">
                <h2>Availability Overview</h2>
                <div class="af-stock-donut">
                    <strong><?php echo htmlspecialchars((string) $totalFoods, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span>Total</span>
                </div>
                <ul class="af-overview-list">
                    <li><i class="main"></i>Available <span><?php echo htmlspecialchars((string) $availableFoods, ENT_QUOTES, 'UTF-8'); ?></span></li>
                    <li><i class="grills"></i>Unavailable <span><?php echo htmlspecialchars((string) $unavailableFoods, ENT_QUOTES, 'UTF-8'); ?></span></li>
                    <li><i class="local"></i>Fast Prep <span><?php echo htmlspecialchars((string) $fastPrepFoods, ENT_QUOTES, 'UTF-8'); ?></span></li>
                    <li><i class="starters"></i>Long Prep <span><?php echo htmlspecialchars((string) $longPrepFoods, ENT_QUOTES, 'UTF-8'); ?></span></li>
                </ul>
            </section>

            <?php if ($selectedFood !== null): ?>
                <section class="af-menu-panel af-food-edit-panel" id="edit_food_form">
                    <h2>Edit Food / Drink</h2>
                    <div class="af-food-edit-preview">
                        <img src="<?php echo htmlspecialchars(afrisense_food_image($frontendBase, (string) ($selectedFood['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        <span>
                            <strong><?php echo htmlspecialchars((string) ($selectedFood['food_name'] ?? 'Food item'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <small><?php echo htmlspecialchars((string) ($selectedFood['category_name'] ?? 'Category'), ENT_QUOTES, 'UTF-8'); ?></small>
                        </span>
                    </div>
                    <form class="af-food-management-form" action="foods.php?edit=<?php echo htmlspecialchars((string) ($selectedFood['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>#edit_food_form" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_food">
                        <input type="hidden" name="food_id" value="<?php echo htmlspecialchars((string) ($selectedFood['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                        <label class="af-food-image-upload">
                            <span>Replace Image</span>
                            <input type="file" name="food_image" accept="image/jpeg,image/png,image/webp,image/gif">
                            <strong><i class="bi bi-image" aria-hidden="true"></i> Choose new image</strong>
                            <small>Leave empty to keep the current image.</small>
                        </label>
                        <label>
                            <span>Food Name</span>
                            <input type="text" name="food_name" value="<?php echo htmlspecialchars((string) ($selectedFood['food_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                        </label>
                        <label>
                            <span>Category</span>
                            <select name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars((string) ($category['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int) ($selectedFood['category_id'] ?? 0) === (int) ($category['id'] ?? 0) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars((string) $category['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <span>Price (GHS)</span>
                            <input type="number" name="price" min="0.01" step="0.01" value="<?php echo htmlspecialchars((string) ($selectedFood['price'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>" required>
                        </label>
                        <label>
                            <span>Preparation Time</span>
                            <input type="number" name="preparation_time" min="1" step="1" value="<?php echo htmlspecialchars((string) ((int) ($selectedFood['preparation_time'] ?? 15)), ENT_QUOTES, 'UTF-8'); ?>" required>
                        </label>
                        <label>
                            <span>Availability</span>
                            <select name="availability">
                                <option value="Available" <?php echo (string) ($selectedFood['availability'] ?? '') === 'Available' ? 'selected' : ''; ?>>Available</option>
                                <option value="Unavailable" <?php echo (string) ($selectedFood['availability'] ?? '') === 'Unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                            </select>
                        </label>
                        <label>
                            <span>Description</span>
                            <textarea name="description" rows="3"><?php echo htmlspecialchars((string) ($selectedFood['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </label>
                        <button type="submit"><i class="bi bi-save" aria-hidden="true"></i> Update Food</button>
                    </form>
                </section>
            <?php endif; ?>

            <section class="af-menu-panel">
                <h2>Add Food</h2>
                <form id="add_food_form" class="af-food-management-form" action="foods.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_food">
                    <label class="af-food-image-upload">
                        <span>Food / Drink Image</span>
                        <input type="file" name="food_image" accept="image/jpeg,image/png,image/webp,image/gif">
                        <strong><i class="bi bi-image" aria-hidden="true"></i> Choose image</strong>
                        <small>JPG, PNG, WebP or GIF. Max 5MB.</small>
                    </label>
                    <label>
                        <span>Food Name</span>
                        <input type="text" name="food_name" placeholder="e.g. Jollof Rice" required>
                    </label>
                    <label>
                        <span>Category</span>
                        <select name="category_id" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars((string) ($category['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars((string) $category['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Price (GHS)</span>
                        <input type="number" name="price" min="0.01" step="0.01" placeholder="35.00" required>
                    </label>
                    <label>
                        <span>Preparation Time</span>
                        <input type="number" name="preparation_time" min="1" step="1" value="15" required>
                    </label>
                    <label>
                        <span>Availability</span>
                        <select name="availability">
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                    </label>
                    <label>
                        <span>Description</span>
                        <textarea name="description" rows="3" placeholder="Short menu description"></textarea>
                    </label>
                    <button type="submit"><i class="bi bi-plus-lg" aria-hidden="true"></i> Save Food</button>
                </form>
            </section>

            <section class="af-menu-panel">
                <h2>Food Categories</h2>
                <ul class="af-food-category-list">
                    <?php foreach ($categories as $category): ?>
                        <li>
                            <span>
                                <strong><?php echo htmlspecialchars((string) $category['category_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars((string) ($category['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                            </span>
                            <em><?php echo htmlspecialchars((string) ($category['food_count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></em>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <form class="af-food-management-form compact" action="foods.php" method="post">
                    <input type="hidden" name="action" value="add_category">
                    <label>
                        <span>New Category</span>
                        <input type="text" name="category_name" placeholder="Category name" required>
                    </label>
                    <label>
                        <span>Description</span>
                        <textarea name="category_description" rows="2" placeholder="What this category contains"></textarea>
                    </label>
                    <button type="submit"><i class="bi bi-folder-plus" aria-hidden="true"></i> Save Category</button>
                </form>
            </section>

            <section class="af-menu-panel af-food-help">
                <h2><i class="bi bi-question-circle" aria-hidden="true"></i> Help</h2>
                <p>This is now the single admin page for foods sold by AfriSense and their categories.</p>
                <a href="#">View Help Center <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </section>
        </aside>
    </section>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
