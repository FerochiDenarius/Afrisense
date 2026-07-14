<?php

declare(strict_types=1);

namespace AfriSense\Backend\Helpers;

class Security
{
    /**
     * Hash a plaintext password.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a plaintext password against a stored hash.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Sanitize a string for safe HTML output.
     */
    public static function sanitizeString(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate and store a CSRF token.
     */
    public static function generateCsrfToken(): string
    {
        Session::start();
        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token'] = $token;

        return $token;
    }

    /**
     * Validate a submitted CSRF token.
     */
    public static function validateCsrfToken(?string $token): bool
    {
        Session::start();

        return is_string($token)
            && isset($_SESSION['_csrf_token'])
            && hash_equals((string) $_SESSION['_csrf_token'], $token);
    }

    /**
     * Return the current request IP address.
     */
    public static function currentIp(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /**
     * Return the current request user agent.
     */
    public static function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 255);
    }
}
