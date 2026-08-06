<?php

declare(strict_types=1);

namespace AfriSense\Backend\Middleware;

use PDO;

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../models/Auth.php';

use AfriSense\Backend\Helpers\Response;
use AfriSense\Backend\Models\Auth;

class AuthMiddleware
{
    private Auth $auth;

    /**
     * Create the authentication middleware.
     */
    public function __construct(PDO $pdo)
    {
        $this->auth = new Auth($pdo);
    }

    /**
     * Require an authenticated user.
     */
    public function handle(?string $redirectTo = null): ?array
    {
        $user = $this->auth->getCurrentUser();

        // Guard this block so it only runs when the required condition is met.
        if ($user !== null) {
            return $user;
        }

        // Guard this block so it only runs when the required condition is met.
        if ($redirectTo !== null) {
            header('Location: ' . $redirectTo);
            exit;
        }

        Response::json(Response::error('Authentication required.', 401));
    }
}
