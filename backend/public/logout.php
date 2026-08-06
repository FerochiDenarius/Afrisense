<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';

use AfriSense\Backend\Config\Database;
use AfriSense\Backend\Controllers\AuthController;

// Backend logout endpoint clears admin auth state before redirecting to login.
$pdo = (new Database())->getConnection();
(new AuthController($pdo))->logout();

header('Location: login.php');
exit;
