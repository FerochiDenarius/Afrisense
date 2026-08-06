<?php

declare(strict_types=1);

use AfriSense\Backend\Controllers\AuthController;
use AfriSense\Backend\Controllers\DashboardController;
use AfriSense\Backend\Controllers\NotificationController;
use AfriSense\Backend\Controllers\PermissionController;
use AfriSense\Backend\Controllers\RoleController;
use AfriSense\Backend\Controllers\SettingsController;
use AfriSense\Backend\Controllers\UserController;
use AfriSense\Backend\Helpers\Response;
use AfriSense\Backend\Middleware\AdminMiddleware;
use AfriSense\Backend\Middleware\AuthMiddleware;

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/NotificationController.php';
require_once __DIR__ . '/../controllers/PermissionController.php';
require_once __DIR__ . '/../controllers/RoleController.php';
require_once __DIR__ . '/../controllers/SettingsController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../middleware/AdminMiddleware.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// Guard this block so it only runs when the required condition is met.
if (!function_exists('afrisenseRequestPayload')) {
    // Defines the afrisenseRequestPayload helper used by this module.
    function afrisenseRequestPayload(): array
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        $rawBody = file_get_contents('php://input') ?: '';

        // Guard this block so it only runs when the required condition is met.
        if (str_contains($contentType, 'application/json') && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);

            return is_array($decoded) ? $decoded : [];
        }

        // Guard this block so it only runs when the required condition is met.
        if ($_POST !== []) {
            return $_POST;
        }

        parse_str($rawBody, $parsed);

        return is_array($parsed) ? $parsed : [];
    }
}

return static function (PDO $pdo): void {
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $apiPosition = strpos($path, '/api');
    $path = $apiPosition === false ? '/' : substr($path, $apiPosition);
    $payload = afrisenseRequestPayload();

    $auth = new AuthController($pdo);
    $users = new UserController($pdo);
    $roles = new RoleController($pdo);
    $permissions = new PermissionController($pdo);
    $notifications = new NotificationController($pdo);
    $settings = new SettingsController($pdo);
    $dashboard = new DashboardController($pdo);
    $authMiddleware = new AuthMiddleware($pdo);
    $adminMiddleware = new AdminMiddleware($pdo);

    // Guard this block so it only runs when the required condition is met.
    if ($method === 'POST' && $path === '/api/login') {
        Response::json($auth->login($payload));
    }

    // Guard this block so it only runs when the required condition is met.
    if ($method === 'POST' && $path === '/api/logout') {
        $authMiddleware->handle();
        Response::json($auth->logout());
    }

    // Guard this block so it only runs when the required condition is met.
    if ($method === 'GET' && $path === '/api/me') {
        $authMiddleware->handle();
        Response::json($auth->currentUser());
    }

    // Guard this block so it only runs when the required condition is met.
    if ($method === 'GET' && $path === '/api/dashboard') {
        $user = $authMiddleware->handle();
        Response::json($dashboard->index((int) $user['id']));
    }

    // Guard this block so it only runs when the required condition is met.
    if (preg_match('#^/api/users/?(\d+)?$#', $path, $matches)) {
        $adminMiddleware->handle();
        $id = isset($matches[1]) ? (int) $matches[1] : null;

        Response::json(match ($method) {
            'GET' => $id === null ? $users->index() : $users->show($id),
            'POST' => $users->store($payload),
            'PUT', 'PATCH' => $id !== null ? $users->update($id, $payload) : Response::error('User ID is required.', 422),
            'DELETE' => $id !== null ? $users->destroy($id) : Response::error('User ID is required.', 422),
            default => Response::error('Method not allowed.', 405),
        });
    }

    // Guard this block so it only runs when the required condition is met.
    if (preg_match('#^/api/roles/?(\d+)?(?:/permissions/(\d+))?$#', $path, $matches)) {
        $adminMiddleware->handle();
        $roleId = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : null;
        $permissionId = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : null;

        Response::json(match ($method) {
            'GET' => $roleId === null ? $roles->index() : $roles->show($roleId),
            'POST' => $roleId !== null && $permissionId !== null
                ? $roles->assignPermission($roleId, $permissionId)
                : $roles->store($payload),
            'PUT', 'PATCH' => $roleId !== null ? $roles->update($roleId, $payload) : Response::error('Role ID is required.', 422),
            'DELETE' => $roleId !== null && $permissionId !== null
                ? $roles->removePermission($roleId, $permissionId)
                : ($roleId !== null ? $roles->destroy($roleId) : Response::error('Role ID is required.', 422)),
            default => Response::error('Method not allowed.', 405),
        });
    }

    // Guard this block so it only runs when the required condition is met.
    if (preg_match('#^/api/permissions/?(\d+)?$#', $path, $matches)) {
        $adminMiddleware->handle();
        $id = isset($matches[1]) ? (int) $matches[1] : null;

        Response::json(match ($method) {
            'GET' => $id === null ? $permissions->index() : $permissions->show($id),
            'POST' => $permissions->store($payload),
            'PUT', 'PATCH' => $id !== null ? $permissions->update($id, $payload) : Response::error('Permission ID is required.', 422),
            'DELETE' => $id !== null ? $permissions->destroy($id) : Response::error('Permission ID is required.', 422),
            default => Response::error('Method not allowed.', 405),
        });
    }

    // Guard this block so it only runs when the required condition is met.
    if (preg_match('#^/api/notifications/?(\d+)?(?:/(read))?$#', $path, $matches)) {
        $user = $authMiddleware->handle();
        $id = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : null;
        $readAction = isset($matches[2]) && $matches[2] === 'read';

        Response::json(match ($method) {
            'GET' => $notifications->index((int) $user['id']),
            'POST' => $id !== null && $readAction
                ? $notifications->markAsRead($id, (int) $user['id'])
                : $notifications->store(array_merge(['user_id' => (int) $user['id']], $payload)),
            'PATCH' => $id === null ? $notifications->markAllAsRead((int) $user['id']) : $notifications->markAsRead($id, (int) $user['id']),
            'DELETE' => $id !== null ? $notifications->destroy($id, (int) $user['id']) : Response::error('Notification ID is required.', 422),
            default => Response::error('Method not allowed.', 405),
        });
    }

    // Guard this block so it only runs when the required condition is met.
    if (preg_match('#^/api/settings/?([^/]+)?$#', $path, $matches)) {
        $adminMiddleware->handle();
        $key = isset($matches[1]) && $matches[1] !== '' ? urldecode($matches[1]) : null;

        Response::json(match ($method) {
            'GET' => $key === null ? $settings->index() : $settings->show($key),
            'POST', 'PUT', 'PATCH' => $key !== null
                ? $settings->update($key, $payload['value'] ?? null)
                : Response::error('Setting key is required.', 422),
            'DELETE' => $key !== null ? $settings->destroy($key) : Response::error('Setting key is required.', 422),
            default => Response::error('Method not allowed.', 405),
        });
    }

    Response::json(Response::error('Route not found.', 404));
};
