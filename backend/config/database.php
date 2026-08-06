<?php

declare(strict_types=1);

namespace AfriSense\Backend\Config;

use PDO;
use PDOException;
use RuntimeException;

require_once __DIR__ . '/env.php';

/**
 * Central PDO factory for the whole AfriSense app.
 *
 * This class also performs a lightweight first-run install for XAMPP copies:
 * when the configured database is missing or empty, it imports the bundled
 * schema and inserts starter lookup data. Existing databases are left alone.
 */
class Database
{
    private string $host;
    private string $database;
    private string $username;
    private string $password;
    private string $charset;
    private ?PDO $connection = null;

    /**
     * Create a database connection factory.
     */
    public function __construct(
        ?string $host = null,
        ?string $database = null,
        ?string $username = null,
        ?string $password = null,
        string $charset = 'utf8mb4'
    ) {
        $this->host = $host ?? (string) ($_ENV['DB_HOST'] ?? 'localhost');
        $this->database = $database ?? (string) ($_ENV['DB_NAME'] ?? 'afrisense_db');
        $this->username = $username ?? (string) ($_ENV['DB_USER'] ?? 'root');
        $this->password = $password ?? (string) ($_ENV['DB_PASS'] ?? '');
        $this->charset = $charset;
    }

    /**
     * Return a configured PDO connection.
     *
     * @throws RuntimeException When the database connection fails.
     */
    public function getConnection(): PDO
    {
        // Guard this block so it only runs when the required condition is met.
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        // Try the normal configured database first; this is the only path used
        // on an already-installed AfriSense instance.
        try {
            $this->connection = $this->connectToDatabase();
        } catch (PDOException $exception) {
            // Guard this block so it only runs when the required condition is met.
            if (!$this->isUnknownDatabaseError($exception)) {
                throw new RuntimeException('Database connection failed.', 0, $exception);
            }

            // Fresh XAMPP copies often fail here because afrisense_db has not
            // been created yet. Connect without a db name and install it.
            $server = $this->connectToServer();
            $this->installDatabase($server);
            $this->connection = $this->connectToDatabase();
        }

        // Covers the phpMyAdmin case where the database exists but no schema
        // has been imported.
        if ($this->databaseIsEmpty($this->connection)) {
            $this->installDatabase($this->connection);
        }

        return $this->connection;
    }

    private function connectToDatabase(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $this->host,
            $this->database,
            $this->charset
        );

        return $this->newPdo($dsn);
    }

    private function connectToServer(): PDO
    {
        $dsn = sprintf('mysql:host=%s;charset=%s', $this->host, $this->charset);

        return $this->newPdo($dsn);
    }

    private function newPdo(string $dsn): PDO
    {
        return new PDO($dsn, $this->username, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    private function isUnknownDatabaseError(PDOException $exception): bool
    {
        return (int) ($exception->errorInfo[1] ?? 0) === 1049
            || str_contains(strtolower($exception->getMessage()), 'unknown database');
    }

    private function databaseIsEmpty(PDO $pdo): bool
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :database'
        );
        $statement->execute(['database' => $this->database]);

        return (int) $statement->fetchColumn() === 0;
    }

    private function installDatabase(PDO $pdo): void
    {
        // The schema import rewrites the bundled afrisense_db name to whatever
        // DB_NAME is configured as, so keep the value strictly identifier-safe.
        if (!preg_match('/^[A-Za-z0-9_]+$/', $this->database)) {
            throw new RuntimeException('Database name contains unsupported characters.');
        }

        $schemaPath = dirname(__DIR__, 2) . '/sql/afrisense_db_schema.sql';

        // Guard this block so it only runs when the required condition is met.
        if (!is_file($schemaPath) || !is_readable($schemaPath)) {
            throw new RuntimeException('Database schema file is missing.');
        }

        $sql = (string) file_get_contents($schemaPath);
        $sql = str_replace('`afrisense_db`', '`' . $this->database . '`', $sql);

        // The schema file contains CREATE DATABASE/USE statements, so exec()
        // can run it from either a server-level or database-level connection.
        $pdo->exec($sql);
        $pdo->exec('USE `' . $this->database . '`');
        $this->seedStarterData($pdo);
    }

    private function seedStarterData(PDO $pdo): void
    {
        $roleIds = $this->seedRoles($pdo);
        $this->seedAdminUser($pdo, (int) ($roleIds['Administrator'] ?? 1));
        $this->seedSettings($pdo);
        $this->seedFoodCatalog($pdo);
        $this->seedServiceCatalog($pdo);
    }

    /**
     * @return array<string, int>
     */
    private function seedRoles(PDO $pdo): array
    {
        $roles = [
            'Administrator' => 'Full administrative access to the AfriSense system.',
            'Manager' => 'Manages orders, bookings, reports, and daily operations.',
            'Kitchen Staff' => 'Handles food preparation and order workflow updates.',
            'Cashier' => 'Handles payment records and customer payment checks.',
            'Delivery Rider' => 'Handles delivery assignments, pickup, and delivery updates.',
            'Customer Support' => 'Responds to support chats and customer enquiries.',
            'Customer' => 'Public customer account for orders and bookings.',
            'Corporate Customer' => 'Customer account for organizations and bulk orders.',
        ];

        $insert = $pdo->prepare(
            'INSERT IGNORE INTO `roles` (`rolename`, `description`) VALUES (:rolename, :description)'
        );
        $select = $pdo->prepare('SELECT `id` FROM `roles` WHERE `rolename` = :rolename LIMIT 1');
        $ids = [];

        // Iterate through the data needed for this block.
        foreach ($roles as $role => $description) {
            $insert->execute(['rolename' => $role, 'description' => $description]);
            $select->execute(['rolename' => $role]);
            $ids[$role] = (int) $select->fetchColumn();
        }

        return $ids;
    }

    private function seedAdminUser(PDO $pdo, int $adminRoleId): void
    {
        // Default credentials are only for first-run testing and should be
        // changed before any real deployment.
        $statement = $pdo->prepare(
            'INSERT IGNORE INTO `users`
                (`fullname`, `username`, `email`, `phonenumber`, `password`, `role_id`, `email_verified`)
             VALUES
                (:fullname, :username, :email, :phonenumber, :password, :role_id, 1)'
        );
        $statement->execute([
            'fullname' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@afrisense.com',
            'phonenumber' => '+233240000000',
            'password' => password_hash('Admin@123', PASSWORD_DEFAULT),
            'role_id' => $adminRoleId,
        ]);
    }

    private function seedSettings(PDO $pdo): void
    {
        // These rows allow the public pages and settings pages to render on a
        // brand-new database without requiring manual admin setup first.
        $pdo->exec(
            "INSERT INTO `website_settings`
                (`site_name`, `site_tagline`, `primary_color`, `secondary_color`, `hero_title`, `hero_subtitle`, `footer_text`)
             SELECT 'AfriSense Food Services', 'Delicious meals, delivered with care.', '#b77b1a', '#cc8f25',
                'Exceptional Food Memorable Moments',
                'We provide delicious meals and professional catering services for all occasions.',
                '(c) 2026 AfriSense Food Services. All rights reserved.'
             WHERE NOT EXISTS (SELECT 1 FROM `website_settings` LIMIT 1)"
        );

        $pdo->exec(
            "INSERT INTO `company_information`
                (`company_name`, `company_email`, `support_email`, `phone_number_1`, `phone_number_2`, `address`, `city`, `region`, `country`, `business_hours`)
             SELECT 'AfriSense Food Services', 'info@afrisense.com', 'support@afrisense.com',
                '+233 24 123 4567', '+233 20 987 6543',
                '15 Senchi Street, Airport Residential Area, Accra, Ghana',
                'Accra', 'Greater Accra', 'Ghana', 'Mon - Sun: 8:00 AM - 10:00 PM'
             WHERE NOT EXISTS (SELECT 1 FROM `company_information` LIMIT 1)"
        );

        $pdo->exec(
            "INSERT INTO `system_settings` (`site_status`)
             SELECT 'Online'
             WHERE NOT EXISTS (SELECT 1 FROM `system_settings` LIMIT 1)"
        );
    }

    private function seedFoodCatalog(PDO $pdo): void
    {
        $categories = [
            'Rice Dishes' => 'Local and continental rice meals.',
            'Main Dishes' => 'Popular full meals and house specials.',
            'Local Dishes' => 'Traditional Ghanaian meals.',
            'Snacks & Sides' => 'Light foods, sides, and extras.',
        ];

        $categoryIds = [];
        $insertCategory = $pdo->prepare(
            'INSERT IGNORE INTO `food_categories` (`category_name`, `description`) VALUES (:name, :description)'
        );
        $selectCategory = $pdo->prepare(
            'SELECT `id` FROM `food_categories` WHERE `category_name` = :name LIMIT 1'
        );

        // Iterate through the data needed for this block.
        foreach ($categories as $name => $description) {
            $insertCategory->execute(['name' => $name, 'description' => $description]);
            $selectCategory->execute(['name' => $name]);
            $categoryIds[$name] = (int) $selectCategory->fetchColumn();
        }

        $foods = [
            ['Jollof Rice', 'Rice Dishes', 'Spiced rice served with salad and sauce.', 20.00, 25],
            ['Fried Rice', 'Rice Dishes', 'Fried rice with vegetables and protein option.', 20.00, 20],
            ['Banku with Tilapia', 'Local Dishes', 'Banku served with grilled tilapia and pepper.', 45.00, 35],
            ['Grilled Chicken', 'Main Dishes', 'Seasoned grilled chicken with side option.', 35.00, 30],
            ['Waakye Special', 'Local Dishes', 'Waakye served with egg, gari, spaghetti, and stew.', 25.00, 25],
        ];

        $insertFood = $pdo->prepare(
            'INSERT INTO `foods`
                (`category_id`, `food_name`, `description`, `price`, `preparation_time`, `availability`)
             SELECT :category_id, :food_name, :description, :price, :preparation_time, "Available"
             WHERE NOT EXISTS (SELECT 1 FROM `foods` WHERE `food_name` = :food_name_check LIMIT 1)'
        );

        // Iterate through the data needed for this block.
        foreach ($foods as [$name, $category, $description, $price, $preparationTime]) {
            $insertFood->execute([
                'category_id' => $categoryIds[$category] ?? reset($categoryIds),
                'food_name' => $name,
                'description' => $description,
                'price' => $price,
                'preparation_time' => $preparationTime,
                'food_name_check' => $name,
            ]);
        }
    }

    private function seedServiceCatalog(PDO $pdo): void
    {
        $categories = [
            'Event Catering' => 'Catering packages for events and offices.',
            'Private Dining' => 'Small group and private meal services.',
            'Corporate Services' => 'Office meals and business catering.',
        ];

        $categoryIds = [];
        $insertCategory = $pdo->prepare(
            'INSERT IGNORE INTO `service_categories` (`category_name`, `description`) VALUES (:name, :description)'
        );
        $selectCategory = $pdo->prepare(
            'SELECT `id` FROM `service_categories` WHERE `category_name` = :name LIMIT 1'
        );

        // Iterate through the data needed for this block.
        foreach ($categories as $name => $description) {
            $insertCategory->execute(['name' => $name, 'description' => $description]);
            $selectCategory->execute(['name' => $name]);
            $categoryIds[$name] = (int) $selectCategory->fetchColumn();
        }

        $services = [
            ['Corporate Lunch Package', 'Corporate Services', 'Office lunch catering for teams and meetings.', 950.00, '2 - 4 hours'],
            ['Wedding Catering', 'Event Catering', 'Full-service catering for wedding receptions.', 4500.00, 'Full day'],
            ['Private Dinner Service', 'Private Dining', 'Chef-supported dining for small groups.', 1800.00, '3 - 5 hours'],
        ];

        $insertService = $pdo->prepare(
            'INSERT INTO `services`
                (`category_id`, `service_name`, `description`, `price`, `estimated_duration`, `availability`)
             SELECT :category_id, :service_name, :description, :price, :estimated_duration, "Available"
             WHERE NOT EXISTS (SELECT 1 FROM `services` WHERE `service_name` = :service_name_check LIMIT 1)'
        );

        // Iterate through the data needed for this block.
        foreach ($services as [$name, $category, $description, $price, $duration]) {
            $insertService->execute([
                'category_id' => $categoryIds[$category] ?? reset($categoryIds),
                'service_name' => $name,
                'description' => $description,
                'price' => $price,
                'estimated_duration' => $duration,
                'service_name_check' => $name,
            ]);
        }
    }
}
