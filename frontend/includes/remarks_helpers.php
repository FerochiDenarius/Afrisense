<?php

declare(strict_types=1);

require_once __DIR__ . '/public_settings.php';

/**
 * @return list<array<string, mixed>>
 */
function afrisense_remarks_samples(): array
{
    return [
        ['id' => 1, 'customer_name' => 'Kofi Mensah', 'email' => 'kofi.mensah@gmail.com', 'phone' => '+233 24 123 4567', 'food_service' => 'Jollof Rice', 'category' => 'Rice Dishes', 'image' => 'jollof-rice.png', 'rating' => 5, 'remark' => 'The jollof rice was absolutely delicious! Great taste and generous portion.', 'status' => 'Published', 'source' => 'Customer', 'created_at' => '2025-05-15 14:30:00'],
        ['id' => 2, 'customer_name' => 'Akosua Boateng', 'email' => 'akosua.b@gmail.com', 'phone' => '+233 24 987 6543', 'food_service' => 'Grilled Chicken', 'category' => 'Main Dishes', 'image' => 'grilled-chicken.png', 'rating' => 4, 'remark' => 'Very well grilled and seasoned. Will order again.', 'status' => 'Published', 'source' => 'Customer', 'created_at' => '2025-05-14 19:45:00'],
        ['id' => 3, 'customer_name' => 'Yaw Addo', 'email' => 'yaw.addo@gmail.com', 'phone' => '+233 20 111 2233', 'food_service' => 'Light Soup', 'category' => 'Soups', 'image' => 'light-soup.png', 'rating' => 5, 'remark' => 'Fresh ingredients and perfectly prepared.', 'status' => 'Published', 'source' => 'Guest', 'created_at' => '2025-05-14 13:15:00'],
        ['id' => 4, 'customer_name' => 'Ama Serwaa', 'email' => 'ama.serwaa@gmail.com', 'phone' => '+233 55 000 1122', 'food_service' => 'Cheese Burger', 'category' => 'Snacks & Sides', 'image' => 'cheese-burger.png', 'rating' => 2, 'remark' => 'The burger was good but the delivery was late.', 'status' => 'Published', 'source' => 'Guest', 'created_at' => '2025-05-13 20:20:00'],
        ['id' => 5, 'customer_name' => 'Kwame Nkrumah', 'email' => 'kwame.nk@gmail.com', 'phone' => '+233 27 444 7788', 'food_service' => 'Banku with Tilapia', 'category' => 'Local Dishes', 'image' => 'waakye.png', 'rating' => 5, 'remark' => 'Authentic taste! Reminds me of home.', 'status' => 'Published', 'source' => 'Customer', 'created_at' => '2025-05-13 17:10:00'],
        ['id' => 6, 'customer_name' => 'Abena Owusu', 'email' => 'abena.owusu@gmail.com', 'phone' => '+233 50 321 9090', 'food_service' => 'Fried Rice', 'category' => 'Rice Dishes', 'image' => 'fried-rice.png', 'rating' => 2, 'remark' => 'It was okay, could be better with more veggies.', 'status' => 'Rejected', 'source' => 'Customer', 'created_at' => '2025-05-12 11:05:00'],
        ['id' => 7, 'customer_name' => 'Isaac Asare', 'email' => 'isaac.asare@gmail.com', 'phone' => '+233 26 555 2190', 'food_service' => 'Catering Service', 'category' => 'Catering Packages', 'image' => 'foodimage.jpeg', 'rating' => 5, 'remark' => 'Excellent service for our event. Everything was perfect!', 'status' => 'Published', 'source' => 'Customer', 'created_at' => '2025-05-11 09:30:00'],
    ];
}

function afrisense_remarks_ensure_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `customer_remarks` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `customer_id` INT NULL,
            `user_id` INT NULL,
            `customer_name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(180) NOT NULL,
            `phone` VARCHAR(50) NULL,
            `food_service` VARCHAR(180) NOT NULL,
            `category` VARCHAR(120) NULL,
            `image` VARCHAR(255) NULL,
            `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
            `remark` TEXT NOT NULL,
            `status` VARCHAR(30) NOT NULL DEFAULT "Pending",
            `source` VARCHAR(30) NOT NULL DEFAULT "Guest",
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_customer_remarks_status` (`status`),
            INDEX `idx_customer_remarks_email` (`email`),
            INDEX `idx_customer_remarks_customer` (`customer_id`),
            INDEX `idx_customer_remarks_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $columns = [];
    $columnStatement = $pdo->prepare('SHOW COLUMNS FROM `customer_remarks`');
    $columnStatement->execute();

    foreach ($columnStatement->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[(string) ($column['Field'] ?? '')] = true;
    }

    $requiredColumns = [
        'customer_id' => 'INT NULL AFTER `id`',
        'user_id' => 'INT NULL AFTER `customer_id`',
        'phone' => 'VARCHAR(50) NULL AFTER `email`',
        'category' => 'VARCHAR(120) NULL AFTER `food_service`',
        'image' => 'VARCHAR(255) NULL AFTER `category`',
        'source' => 'VARCHAR(30) NOT NULL DEFAULT "Guest" AFTER `status`',
        'updated_at' => 'DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`',
    ];

    foreach ($requiredColumns as $column => $definition) {
        if (!isset($columns[$column])) {
            $pdo->exec(sprintf('ALTER TABLE `customer_remarks` ADD COLUMN `%s` %s', $column, $definition));
        }
    }
}

function afrisense_remarks_seed_samples(PDO $pdo): void
{
    afrisense_remarks_ensure_table($pdo);

    $countStatement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `customer_remarks`');
    $countStatement->execute();

    if ((int) ($countStatement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0) > 0) {
        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO `customer_remarks`
            (`customer_name`, `email`, `phone`, `food_service`, `category`, `image`, `rating`, `remark`, `status`, `source`, `created_at`)
         VALUES
            (:customer_name, :email, :phone, :food_service, :category, :image, :rating, :remark, :status, :source, :created_at)'
    );

    foreach (afrisense_remarks_samples() as $remark) {
        $insert->execute([
            'customer_name' => (string) $remark['customer_name'],
            'email' => (string) $remark['email'],
            'phone' => (string) $remark['phone'],
            'food_service' => (string) $remark['food_service'],
            'category' => (string) $remark['category'],
            'image' => (string) $remark['image'],
            'rating' => (int) $remark['rating'],
            'remark' => (string) $remark['remark'],
            'status' => (string) $remark['status'],
            'source' => (string) $remark['source'],
            'created_at' => (string) $remark['created_at'],
        ]);
    }
}

function afrisense_remarks_customer_for_user(PDO $pdo, ?array $user): ?array
{
    if ($user === null) {
        return null;
    }

    $email = trim((string) ($user['email'] ?? ''));
    $phone = preg_replace('/\s+/', '', trim((string) ($user['phonenumber'] ?? $user['phone'] ?? '')));

    if ($email === '' && $phone === '') {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT *
         FROM `customers`
         WHERE (:email_guard <> "" AND `email` = :email_value)
            OR (:phone_guard <> "" AND REPLACE(`phone_number`, " ", "") = :phone_value)
         ORDER BY `id` ASC
         LIMIT 1'
    );
    $statement->execute([
        'email_guard' => $email,
        'email_value' => $email,
        'phone_guard' => $phone,
        'phone_value' => $phone,
    ]);
    $customer = $statement->fetch(PDO::FETCH_ASSOC);

    return $customer ?: null;
}

function afrisense_remarks_image(string $frontendBase, ?string $image): string
{
    $relativeImage = ltrim(str_replace('\\', '/', trim((string) $image)), '/');
    $filename = basename($relativeImage);

    if ($filename !== '' && is_file(__DIR__ . '/../assets/images/foods/' . $filename)) {
        return $frontendBase . '/assets/images/foods/' . $filename;
    }

    if ($relativeImage !== '' && is_file(__DIR__ . '/../uploads/' . $relativeImage)) {
        return $frontendBase . '/uploads/' . $relativeImage;
    }

    if ($filename !== '' && is_file(__DIR__ . '/../uploads/' . $filename)) {
        return $frontendBase . '/uploads/' . $filename;
    }

    return $frontendBase . '/assets/images/foodimage.jpeg';
}

function afrisense_remarks_status_class(string $status): string
{
    return match (strtolower($status)) {
        'published' => 'published',
        'rejected' => 'rejected',
        default => 'pending',
    };
}

function afrisense_remarks_excerpt(string $value, int $limit = 120): string
{
    $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

    if (strlen($value) <= $limit) {
        return $value;
    }

    return rtrim(substr($value, 0, $limit - 3)) . '...';
}

function afrisense_remarks_stars(float $rating): string
{
    $html = '<span class="af-remark-stars" aria-label="' . htmlspecialchars(number_format($rating, 1), ENT_QUOTES, 'UTF-8') . ' out of 5">';

    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="bi ' . ($i <= round($rating) ? 'bi-star-fill' : 'bi-star') . '" aria-hidden="true"></i>';
    }

    return $html . '</span>';
}

/**
 * @return list<string>
 */
function afrisense_remarks_food_options(PDO $pdo): array
{
    $options = [];

    try {
        $foods = $pdo->prepare('SELECT `food_name` FROM `foods` ORDER BY `food_name` ASC LIMIT 40');
        $foods->execute();
        $options = array_merge($options, array_map('strval', $foods->fetchAll(PDO::FETCH_COLUMN)));
    } catch (Throwable) {
        $options = [];
    }

    try {
        $services = $pdo->prepare('SELECT `service_name` FROM `services` ORDER BY `service_name` ASC LIMIT 20');
        $services->execute();
        $options = array_merge($options, array_map('strval', $services->fetchAll(PDO::FETCH_COLUMN)));
    } catch (Throwable) {
        // Foods alone are enough when services are unavailable.
    }

    if ($options === []) {
        $options = array_map(static fn (array $remark): string => (string) $remark['food_service'], afrisense_remarks_samples());
    }

    return array_values(array_unique(array_filter($options, static fn (string $value): bool => trim($value) !== '')));
}

/**
 * @param array<string, string> $filters
 * @return list<array<string, mixed>>
 */
function afrisense_remarks_fetch(PDO $pdo, array $filters = [], int $limit = 24, bool $includeSamples = false): array
{
    afrisense_remarks_ensure_table($pdo);

    $where = [];
    $params = [];

    if (($filters['status'] ?? '') !== '') {
        $where[] = '`status` = :status';
        $params['status'] = (string) $filters['status'];
    }

    if (($filters['email'] ?? '') !== '') {
        $where[] = '`email` = :email';
        $params['email'] = (string) $filters['email'];
    }

    if (($filters['customer_id'] ?? '') !== '') {
        $where[] = '`customer_id` = :customer_id';
        $params['customer_id'] = (int) $filters['customer_id'];
    }

    if (($filters['rating'] ?? '') !== '') {
        $where[] = '`rating` = :rating';
        $params['rating'] = (int) $filters['rating'];
    }

    if (($filters['food_service'] ?? '') !== '') {
        $where[] = '`food_service` = :food_service';
        $params['food_service'] = (string) $filters['food_service'];
    }

    if (($filters['search'] ?? '') !== '') {
        $where[] = '(`customer_name` LIKE :search OR `email` LIKE :search OR `food_service` LIKE :search OR `remark` LIKE :search)';
        $params['search'] = '%' . (string) $filters['search'] . '%';
    }

    $sql = 'SELECT * FROM `customer_remarks`';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY `created_at` DESC, `id` DESC LIMIT ' . max(1, $limit);
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $remarks = $statement->fetchAll(PDO::FETCH_ASSOC);

    unset($includeSamples);

    return array_slice($remarks, 0, $limit);
}

/**
 * @return array{success: bool, message: string}
 */
function afrisense_remarks_submit(PDO $pdo, array $request, ?array $user = null, string $source = 'Guest'): array
{
    afrisense_remarks_ensure_table($pdo);

    $customer = afrisense_remarks_customer_for_user($pdo, $user);
    $customerId = $customer !== null ? (int) $customer['id'] : null;
    $name = trim((string) ($request['customer_name'] ?? $request['full_name'] ?? $user['fullname'] ?? $customer['fullname'] ?? ''));
    $email = trim((string) ($request['email'] ?? $user['email'] ?? $customer['email'] ?? ''));
    $phone = trim((string) ($request['phone'] ?? $user['phonenumber'] ?? $user['phone'] ?? $customer['phone_number'] ?? ''));
    $foodService = trim((string) ($request['food_service'] ?? ''));
    $rating = max(1, min(5, (int) ($request['rating'] ?? 0)));
    $remark = trim((string) ($request['remark'] ?? ''));

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $foodService === '' || strlen($remark) < 10) {
        return ['success' => false, 'message' => 'Please provide your name, valid email, food or service, rating, and a remark of at least 10 characters.'];
    }

    $insert = $pdo->prepare(
        'INSERT INTO `customer_remarks`
            (`customer_id`, `user_id`, `customer_name`, `email`, `phone`, `food_service`, `category`, `image`, `rating`, `remark`, `status`, `source`)
         VALUES
            (:customer_id, :user_id, :customer_name, :email, :phone, :food_service, :category, :image, :rating, :remark, :status, :source)'
    );
    $insert->execute([
        'customer_id' => $customerId,
        'user_id' => isset($user['id']) ? (int) $user['id'] : null,
        'customer_name' => $name,
        'email' => $email,
        'phone' => $phone !== '' ? $phone : null,
        'food_service' => $foodService,
        'category' => 'Customer Feedback',
        'image' => null,
        'rating' => $rating,
        'remark' => $remark,
        'status' => 'Published',
        'source' => $source,
    ]);

    if (function_exists('afrisense_public_create_admin_notifications')) {
        try {
            afrisense_public_create_admin_notifications(
                $pdo,
                'remark_notifications',
                'New Customer Remark',
                $name . ' submitted a ' . $rating . '-star remark for ' . $foodService . '.',
                'Remark',
                '/Afrisense/frontend/admin/remarks.php',
                isset($user['id']) ? (int) $user['id'] : null
            );
        } catch (Throwable) {
            // The remark should remain saved even if notification delivery is unavailable.
        }
    }

    return ['success' => true, 'message' => 'Thank you. Your remark has been published.'];
}
