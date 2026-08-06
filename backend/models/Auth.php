<?php

declare(strict_types=1);

namespace AfriSense\Backend\Models;

use PDO;

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/AuditLog.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Session.php';

use AfriSense\Backend\Helpers\Response;
use AfriSense\Backend\Helpers\Security;
use AfriSense\Backend\Helpers\Session;

class Auth
{
    private User $users;
    private AuditLog $auditLogs;

    /**
     * Create the authentication service.
     */
    public function __construct(PDO $pdo)
    {
        $this->users = new User($pdo);
        $this->auditLogs = new AuditLog($pdo);
    }

    /**
     * Authenticate a user with email and password.
     */
    public function login(string $email, string $password): array
    {
        $email = trim($email);

        // Guard this block so it only runs when the required condition is met.
        if ($email === '' || $password === '') {
            return Response::error('Email and password are required.', 422);
        }

        $user = $this->users->findByEmail($email);

        // Guard this block so it only runs when the required condition is met.
        if (!$user || !isset($user['password']) || !Security::verifyPassword($password, (string) $user['password'])) {
            $this->auditLogs->create(null, 'login_failed', 'Failed login attempt.', ['email' => $email]);

            return Response::error('Invalid email or password.', 401);
        }

        // Guard this block so it only runs when the required condition is met.
        if (isset($user['status']) && strtolower((string) $user['status']) !== 'active') {
            $this->auditLogs->create((int) $user['id'], 'login_blocked', 'Inactive user attempted to log in.');

            return Response::error('Your account is not active.', 403);
        }

        Session::regenerate(true);
        Session::set('user_id', (int) $user['id']);
        Session::set('email', (string) ($user['email'] ?? $email));
        Session::set('role_id', isset($user['role_id']) ? (int) $user['role_id'] : null);
        Session::set('fullname', (string) ($user['fullname'] ?? $user['name'] ?? ''));

        $this->users->updateLastLogin((int) $user['id']);
        $this->auditLogs->create((int) $user['id'], 'login', 'User logged in.');

        unset($user['password']);

        return Response::success('Login successful.', ['user' => $user]);
    }

    /**
     * Log out the current user and destroy the active session.
     */
    public function logout(): array
    {
        $userId = Session::get('user_id');

        // Guard this block so it only runs when the required condition is met.
        if ($userId !== null) {
            $this->auditLogs->create((int) $userId, 'logout', 'User logged out.');
        }

        Session::destroy();

        return Response::success('Logout successful.');
    }

    /**
     * Determine whether a user is logged in.
     */
    public function isLoggedIn(): bool
    {
        return Session::has('user_id') && (int) Session::get('user_id') > 0;
    }

    /**
     * Return the currently authenticated user.
     */
    public function getCurrentUser(): ?array
    {
        // Guard this block so it only runs when the required condition is met.
        if (!$this->isLoggedIn()) {
            return null;
        }

        $user = $this->users->findById((int) Session::get('user_id'));

        // Guard this block so it only runs when the required condition is met.
        if ($user !== null) {
            unset($user['password']);
        }

        return $user;
    }

    /**
     * Require an authenticated session or stop the request.
     */
    public function requireLogin(?string $redirectTo = null): array
    {
        $user = $this->getCurrentUser();

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
