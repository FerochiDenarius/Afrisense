<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

use AfriSense\Backend\Config\Database;

$pdo = (new Database())->getConnection();
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$dispatcher = str_contains($path, '/api')
    ? require __DIR__ . '/../routes/api.php'
    : require __DIR__ . '/../routes/web.php';

$dispatcher($pdo);
