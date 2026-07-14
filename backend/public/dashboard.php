<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

use AfriSense\Backend\Config\Database;
use AfriSense\Backend\Controllers\DashboardController;
use AfriSense\Backend\Helpers\Security;
use AfriSense\Backend\Middleware\AuthMiddleware;

$pdo ??= (new Database())->getConnection();
$user = (new AuthMiddleware($pdo))->handle('login.php');
$response = (new DashboardController($pdo))->index((int) $user['id']);
$counts = $response['data']['counts'] ?? [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AfriSense Dashboard</title>
</head>
<body>
    <main>
        <h1>Dashboard</h1>
        <p>Welcome, <?= Security::sanitizeString((string) ($user['fullname'] ?? $user['email'] ?? 'User')) ?>.</p>
        <ul>
            <li>Users: <?= (int) ($counts['users'] ?? 0) ?></li>
            <li>Roles: <?= (int) ($counts['roles'] ?? 0) ?></li>
            <li>Permissions: <?= (int) ($counts['permissions'] ?? 0) ?></li>
            <li>Unread notifications: <?= (int) ($counts['notifications_unread'] ?? 0) ?></li>
        </ul>
        <p><a href="logout.php">Logout</a></p>
    </main>
</body>
</html>
