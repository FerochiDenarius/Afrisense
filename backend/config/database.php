<?php

declare(strict_types=1);

namespace AfriSense\Backend\Config;

use PDO;
use PDOException;
use RuntimeException;

require_once __DIR__ . '/env.php';

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
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $this->host,
            $this->database,
            $this->charset
        );

        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed.', 0, $exception);
        }

        return $this->connection;
    }
}
