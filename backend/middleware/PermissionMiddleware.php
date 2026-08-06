<?php

declare(strict_types=1);

namespace AfriSense\Backend\Middleware;

use PDO;

require_once __DIR__ . '/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../models/User.php';

use AfriSense\Backend\Helpers\Response;
use AfriSense\Backend\Models\User;

class PermissionMiddleware
{
    private AuthMiddleware $authMiddleware;
    private User $users;

    /**
     * Create the permission middleware.
     */
    public function __construct(PDO $pdo)
    {
        $this->authMiddleware = new AuthMiddleware($pdo);
        $this->users = new User($pdo);
    }

    /**
     * Require a named permission.
     */
    public function handle(string $permission): array
    {
        $user = $this->authMiddleware->handle();

        // Guard this block so it only runs when the required condition is met.
        if ($this->users->hasPermission((int) $user['id'], $permission)) {
            return $user;
        }

        Response::json(Response::error('Permission denied.', 403));
    }
}
