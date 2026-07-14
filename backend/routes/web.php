<?php

declare(strict_types=1);

use AfriSense\Backend\Helpers\Response;

require_once __DIR__ . '/../helpers/Response.php';

return static function (PDO $pdo): void {
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

    if (str_ends_with($path, '/dashboard')) {
        require __DIR__ . '/../public/dashboard.php';
        return;
    }

    if (str_ends_with($path, '/login')) {
        require __DIR__ . '/../public/login.php';
        return;
    }

    Response::json(Response::error('Web route not found.', 404));
};
