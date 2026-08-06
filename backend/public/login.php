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

// Handle submitted form actions before rendering the page.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Guard this block so it only runs when the required condition is met.
    if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? null)) {
        $message = 'Invalid form token.';
    } else {
        $response = (new AuthController($pdo))->login($_POST);
        $message = $response['message'];

        // Guard this block so it only runs when the required condition is met.
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
    <!-- Main content area for this page. -->
    <main>
        <h1>AfriSense Login</h1>
        <?php // Render this conditional/dynamic template block. ?>
        <?php if ($message !== ''): ?>
            <p><?= Security::sanitizeString($message) ?></p>
        <?php endif; ?>
        <!-- Form block that submits this page workflow. -->
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
