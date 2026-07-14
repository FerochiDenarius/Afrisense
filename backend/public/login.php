<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../helpers/Security.php';

use AfriSense\Backend\Config\Database;
use AfriSense\Backend\Controllers\AuthController;
use AfriSense\Backend\Helpers\Security;

$pdo ??= (new Database())->getConnection();
$message = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? null)) {
        $message = 'Invalid form token.';
    } else {
        $response = (new AuthController($pdo))->login($_POST);
        $message = $response['message'];

        if ($response['success'] === true) {
            header('Location: dashboard.php');
            exit;
        }
    }
}

$token = Security::generateCsrfToken();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AfriSense Login</title>
</head>
<body>
    <main>
        <h1>AfriSense Login</h1>
        <?php if ($message !== ''): ?>
            <p><?= Security::sanitizeString($message) ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="_csrf_token" value="<?= Security::sanitizeString($token) ?>">
            <label>
                Email
                <input type="email" name="email" required>
            </label>
            <label>
                Password
                <input type="password" name="password" required>
            </label>
            <button type="submit">Login</button>
        </form>
    </main>
</body>
</html>
