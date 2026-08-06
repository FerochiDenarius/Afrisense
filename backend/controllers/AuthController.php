<?php

declare(strict_types=1);

namespace AfriSense\Backend\Controllers;

use PDO;

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../models/Auth.php';

use AfriSense\Backend\Helpers\Response;
use AfriSense\Backend\Helpers\Validator;
use AfriSense\Backend\Models\Auth;

class AuthController
{
    private Auth $auth;

    /**
     * Create the authentication controller.
     */
    public function __construct(PDO $pdo)
    {
        $this->auth = new Auth($pdo);
    }

    /**
     * Handle a login request.
     */
    public function login(array $request): array
    {
        $validator = new Validator();

        // Guard this block so it only runs when the required condition is met.
        if (!$validator->validate($request, ['email' => 'required|email', 'password' => 'required'])) {
            return Response::error('Validation failed.', 422, $validator->getErrors());
        }

        return $this->auth->login((string) $request['email'], (string) $request['password']);
    }

    /**
     * Handle a logout request.
     */
    public function logout(): array
    {
        return $this->auth->logout();
    }

    /**
     * Return the authenticated user.
     */
    public function currentUser(): array
    {
        $user = $this->auth->getCurrentUser();

        // Guard this block so it only runs when the required condition is met.
        if ($user === null) {
            return Response::error('Authentication required.', 401);
        }

        return Response::success('Current user loaded.', ['user' => $user]);
    }
}
