<?php

declare(strict_types=1);

namespace AfriSense\Backend\Middleware;

use PDO;

require_once __DIR__ . '/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../models/User.php';

use AfriSense\Backend\Helpers\Response;
use AfriSense\Backend\Models\User;

class AdminMiddleware
{
    private AuthMiddleware $authMiddleware;
    private User $users;

    /**
     * Create the admin middleware.
     */
    public function __construct(PDO $pdo)
    {
        $this->authMiddleware = new AuthMiddleware($pdo);
        $this->users = new User($pdo);
    }

    /**
     * Require an authenticated administrator.
     */
    public function handle(): array
    {
        $user = $this->authMiddleware->handle();
        $role = $this->users->getRole((int) $user['id']);
        $roleName = strtolower((string) ($role['name'] ?? $role['slug'] ?? $role['rolename'] ?? $user['role'] ?? ''));

        if ($roleName === 'admin' || $roleName === 'administrator') {
            return $user;
        }

        Response::json(Response::error('Administrator access required.', 403));
    }
}
