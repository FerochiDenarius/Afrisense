<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

// Central mail configuration read by authentication and notification helpers.
return [
    'provider' => $_ENV['MAIL_PROVIDER'] ?? 'resend',
    'resend' => [
        'api_key' => $_ENV['RESEND_API_KEY'] ?? '',
        'from_email' => $_ENV['RESEND_FROM_EMAIL'] ?? 'no-reply@afrisense.com',
        'from_name' => $_ENV['RESEND_FROM_NAME'] ?? 'AfriSense Food Services',
    ],
    'password_reset' => [
        'url' => $_ENV['PASSWORD_RESET_URL'] ?? 'http://localhost/Afrisense/frontend/auth/reset-password.php',
        'expiry_minutes' => (int) ($_ENV['PASSWORD_RESET_EXPIRY_MINUTES'] ?? 15),
    ],
];
