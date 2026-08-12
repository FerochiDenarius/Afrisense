<?php

declare(strict_types=1);

use AfriSense\Backend\Config\Database;
use AfriSense\Backend\Helpers\Session;
use AfriSense\Backend\Models\Auth;
use AfriSense\Backend\Models\User;

require_once __DIR__ . '/../includes/app_urls.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/Session.php';
require_once __DIR__ . '/../../backend/models/Auth.php';
require_once __DIR__ . '/../../backend/models/User.php';

/**
 * Shared authentication bootstrap for public, customer, and admin pages.
 *
 * Pages include this file to reuse one PDO connection, one Auth service, and
 * the same role checks/redirect rules across the application.
 */
function afrisense_pdo(): PDO
{
    static $pdo = null;

    // Guard this block so it only runs when the required condition is met.
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = (new Database())->getConnection();

    return $pdo;
}

// Defines the afrisense_auth helper used by this module.
function afrisense_auth(): Auth
{
    static $auth = null;

    // Guard this block so it only runs when the required condition is met.
    if ($auth instanceof Auth) {
        return $auth;
    }

    $auth = new Auth(afrisense_pdo());

    return $auth;
}

// Defines the afrisense_current_user helper used by this module.
function afrisense_current_user(): ?array
{
    return afrisense_auth()->getCurrentUser();
}

// Defines the afrisense_role_name helper used by this module.
function afrisense_role_name(?array $user): string
{
    // Guard this block so it only runs when the required condition is met.
    if ($user === null || !isset($user['id'])) {
        return '';
    }

    // Role names live in the roles table, so resolve them from the current
    // user id instead of trusting a stale value stored in the session.
    $role = (new User(afrisense_pdo()))->getRole((int) $user['id']);

    return strtolower((string) ($role['rolename'] ?? $role['name'] ?? ''));
}

// Defines the afrisense_is_customer helper used by this module.
function afrisense_is_customer(?array $user): bool
{
    return afrisense_role_name($user) === 'customer';
}

// Defines the afrisense_is_administrator helper used by this module.
function afrisense_is_administrator(?array $user): bool
{
    return in_array(afrisense_role_name($user), ['administrator', 'admin', 'super admin'], true);
}

// Defines the afrisense_is_support_staff helper used by this module.
function afrisense_is_support_staff(?array $user): bool
{
    return in_array(afrisense_role_name($user), ['support agent', 'agent', 'customer support', 'support'], true);
}

// Defines the afrisense_dashboard_url helper used by this module.
function afrisense_dashboard_url(?array $user): string
{
    // Keep post-login redirects centralized so new roles do not scatter
    // special cases across login/register pages.
    if (afrisense_is_administrator($user)) {
        return afrisense_admin_url('dashboard.php');
    }

    // Guard this block so it only runs when the required condition is met.
    if (afrisense_is_support_staff($user)) {
        return afrisense_admin_url('support.php');
    }

    return afrisense_customer_url('dashboard.php');
}

// Defines the afrisense_require_user helper used by this module.
function afrisense_require_user(): array
{
    $user = afrisense_current_user();

    // Guard this block so it only runs when the required condition is met.
    if ($user === null) {
        // Page-level guards redirect instead of returning errors because these
        // scripts render browser pages, not API responses.
        header('Location: ' . afrisense_auth_url('login.php'));
        exit;
    }

    return $user;
}

// Defines the afrisense_require_customer helper used by this module.
function afrisense_require_customer(): array
{
    $user = afrisense_require_user();

    // Guard this block so it only runs when the required condition is met.
    if (!afrisense_is_customer($user)) {
        header('Location: ' . afrisense_dashboard_url($user));
        exit;
    }

    return $user;
}

// Defines the afrisense_require_admin helper used by this module.
function afrisense_require_admin(): array
{
    $user = afrisense_require_user();

    // Guard this block so it only runs when the required condition is met.
    if (!afrisense_is_administrator($user)) {
        header('Location: ' . afrisense_customer_url('dashboard.php'));
        exit;
    }

    return $user;
}

// Defines the afrisense_ensure_role helper used by this module.
function afrisense_ensure_role(PDO $pdo, string $roleName, string $description = ''): int
{
    $statement = $pdo->prepare('SELECT `id` FROM `roles` WHERE LOWER(`rolename`) = LOWER(:role) LIMIT 1');
    $statement->execute(['role' => $roleName]);
    $role = $statement->fetch(PDO::FETCH_ASSOC);

    // Guard this block so it only runs when the required condition is met.
    if ($role !== false) {
        return (int) $role['id'];
    }

    $statement = $pdo->prepare('INSERT INTO `roles` (`rolename`, `description`) VALUES (:role, :description)');
    $statement->execute([
        'role' => $roleName,
        'description' => $description !== '' ? $description : null,
    ]);

    return (int) $pdo->lastInsertId();
}

// Defines the afrisense_customer_role_id helper used by this module.
function afrisense_customer_role_id(PDO $pdo): int
{
    return afrisense_ensure_role($pdo, 'Customer', 'Public customer account');
}

// Defines the afrisense_delivery_rider_role_id helper used by this module.
function afrisense_delivery_rider_role_id(PDO $pdo): int
{
    return afrisense_ensure_role($pdo, 'Delivery Rider', 'Handles delivery assignments, pickup and order delivery updates.');
}

// Defines the afrisense_unique_username helper used by this module.
function afrisense_unique_username(PDO $pdo, string $email, string $fallbackName): string
{
    // Usernames are derived from the email prefix, then made unique with a
    // numeric suffix. This keeps guest/customer registration friction low.
    $base = preg_replace('/[^a-z0-9_]/', '', strtolower(strtok($email, '@') ?: $fallbackName));
    $base = substr($base !== '' ? $base : 'customer', 0, 40);
    $candidate = $base;
    $counter = 1;

    $statement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `users` WHERE `username` = :username');

    // Iterate through the data needed for this block.
    while (true) {
        $statement->execute(['username' => $candidate]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        // Guard this block so it only runs when the required condition is met.
        if (((int) ($row['count_value'] ?? 0)) === 0) {
            return $candidate;
        }

        $candidate = substr($base, 0, 35) . $counter;
        $counter++;
    }
}

// Defines the afrisense_register_customer helper used by this module.
function afrisense_register_customer(array $request): array
{
    $pdo = afrisense_pdo();
    $required = ['fullname', 'email', 'phone', 'password', 'confirm_password'];

    // Iterate through the data needed for this block.
    foreach ($required as $field) {
        // Guard this block so it only runs when the required condition is met.
        if (trim((string) ($request[$field] ?? '')) === '') {
            return ['success' => false, 'message' => 'Please complete all required fields.'];
        }
    }

    // Guard this block so it only runs when the required condition is met.
    if (!filter_var((string) $request['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    // Guard this block so it only runs when the required condition is met.
    if ((string) $request['password'] !== (string) $request['confirm_password']) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }

    // Guard this block so it only runs when the required condition is met.
    if (strlen((string) $request['password']) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    }

    // Guard this block so it only runs when the required condition is met.
    if (($request['agree'] ?? '') !== '1') {
        return ['success' => false, 'message' => 'Please accept the terms and privacy policy.'];
    }

    $pdo->beginTransaction();

    // Run database/action work inside a guarded block so the page can fail gracefully.
    try {
        // Registration writes both users and customers so login/auth and order
        // history can reference the same person from their preferred table.
        $roleId = afrisense_customer_role_id($pdo);
        $email = trim((string) $request['email']);
        $fullname = trim((string) $request['fullname']);
        $phone = preg_replace('/\s+/', '', trim((string) $request['phone']));
        $username = afrisense_unique_username($pdo, $email, $fullname);
        $verificationToken = bin2hex(random_bytes(32));
        $verificationExpires = date('Y-m-d H:i:s', time() + 3600);

        $userId = (new User($pdo))->create([
            'fullname' => $fullname,
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'password' => (string) $request['password'],
            'role_id' => $roleId,
            'email_verified' => 0,
            'verification_token' => $verificationToken,
            'verification_token_expires' => $verificationExpires,
        ]);

        // Guard this block so it only runs when the required condition is met.
        if ($userId === null) {
            throw new RuntimeException('Account could not be created.');
        }

        $customerStatement = $pdo->prepare(
            'INSERT INTO `customers`
                (`fullname`, `email`, `phone_number`, `address`, `email_verified`, `verification_token`, `verification_token_expires`)
             VALUES
                (:fullname, :email, :phone, :address, :email_verified, :verification_token, :verification_token_expires)
             ON DUPLICATE KEY UPDATE
                `fullname` = VALUES(`fullname`),
                `address` = VALUES(`address`),
                `verification_token` = VALUES(`verification_token`),
                `verification_token_expires` = VALUES(`verification_token_expires`)'
        );
        $customerStatement->execute([
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'address' => trim((string) ($request['address'] ?? '')),
            'email_verified' => 0,
            'verification_token' => $verificationToken,
            'verification_token_expires' => $verificationExpires,
        ]);

        $pdo->commit();
        $mailResult = afrisense_send_verification_email($email, $fullname, $verificationToken);

        // Guard this block so it only runs when the required condition is met.
        if (!$mailResult['success']) {
            return [
                'success' => true,
                'message' => 'Account created, but the verification email could not be sent. Check your Resend sender domain.',
            ];
        }

        return ['success' => true, 'message' => 'Account created. Please check your email to verify your account.'];
    } catch (Throwable $exception) {
        // Guard this block so it only runs when the required condition is met.
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Guard this block so it only runs when the required condition is met.
        if (str_contains($exception->getMessage(), 'Duplicate entry')) {
            return ['success' => false, 'message' => 'An account with this email or phone already exists.'];
        }

        return ['success' => false, 'message' => 'Account could not be created.'];
    }
}

// Defines the afrisense_flash_set helper used by this module.
function afrisense_flash_set(string $type, string $message): void
{
    Session::set('flash', ['type' => $type, 'message' => $message]);
}

// Defines the afrisense_flash_get helper used by this module.
function afrisense_flash_get(): ?array
{
    $flash = Session::get('flash');
    Session::remove('flash');

    return is_array($flash) ? $flash : null;
}

// Defines the afrisense_app_url helper used by this module.
function afrisense_app_url(): string
{
    require_once __DIR__ . '/../../backend/config/env.php';

    return rtrim((string) ($_ENV['APP_URL'] ?? 'http://localhost/Afrisense'), '/');
}

// Defines the afrisense_admin_items_per_page helper used by this module.
function afrisense_admin_items_per_page(int $fallback = 25): int
{
    // Run database/action work inside a guarded block so the page can fail gracefully.
    try {
        $statement = afrisense_pdo()->prepare('SELECT `items_per_page` FROM `system_settings` ORDER BY `id` ASC LIMIT 1');
        $statement->execute();
        $value = (int) ($statement->fetchColumn() ?: $fallback);

        return max(5, min(100, $value));
    } catch (Throwable $exception) {
        return max(5, min(100, $fallback));
    }
}

// Defines the afrisense_mail_database_settings helper used by this module.
function afrisense_mail_database_settings(): array
{
    // Run database/action work inside a guarded block so the page can fail gracefully.
    try {
        $pdo = afrisense_pdo();
        $systemStatement = $pdo->prepare('SELECT * FROM `system_settings` ORDER BY `id` ASC LIMIT 1');
        $systemStatement->execute();
        $system = $systemStatement->fetch(PDO::FETCH_ASSOC) ?: [];

        $companyStatement = $pdo->prepare('SELECT * FROM `company_information` ORDER BY `id` ASC LIMIT 1');
        $companyStatement->execute();
        $company = $companyStatement->fetch(PDO::FETCH_ASSOC) ?: [];

        return ['system' => $system, 'company' => $company];
    } catch (Throwable $exception) {
        return ['system' => [], 'company' => []];
    }
}

// Defines the afrisense_smtp_response helper used by this module.
function afrisense_smtp_response($socket): string
{
    $response = '';

    // Iterate through the data needed for this block.
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;

        // Guard this block so it only runs when the required condition is met.
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }

    return $response;
}

// Defines the afrisense_smtp_command helper used by this module.
function afrisense_smtp_command($socket, string $command, array $acceptedCodes): string
{
    // Guard this block so it only runs when the required condition is met.
    if ($command !== '') {
        fwrite($socket, $command . "\r\n");
    }

    $response = afrisense_smtp_response($socket);
    $code = substr($response, 0, 3);

    // Guard this block so it only runs when the required condition is met.
    if (!in_array($code, $acceptedCodes, true)) {
        throw new RuntimeException(trim($response) !== '' ? trim($response) : 'SMTP server did not respond as expected.');
    }

    return $response;
}

// Defines the afrisense_smtp_message helper used by this module.
function afrisense_smtp_message(string $from, string $toEmail, string $toName, string $subject, string $html, string $text): string
{
    $boundary = 'afrisense_' . bin2hex(random_bytes(12));
    $toHeader = $toName !== '' ? sprintf('"%s" <%s>', addcslashes($toName, '"\\'), $toEmail) : $toEmail;

    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . $from,
        'To: ' . $toHeader,
        'Subject: ' . $subject,
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];

    $body = implode("\r\n", $headers) . "\r\n\r\n";
    $body .= '--' . $boundary . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $text . "\r\n\r\n";
    $body .= '--' . $boundary . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $html . "\r\n\r\n";
    $body .= '--' . $boundary . "--\r\n";

    return preg_replace('/^\./m', '..', $body) ?? $body;
}

// Defines the afrisense_send_smtp_email helper used by this module.
function afrisense_send_smtp_email(string $toEmail, string $toName, string $subject, string $html, string $text, array $system, array $company): array
{
    $host = trim((string) ($system['smtp_host'] ?? ''));
    $port = (int) ($system['smtp_port'] ?? 587);
    $encryption = strtolower(trim((string) ($system['smtp_encryption'] ?? 'tls')));
    $username = trim((string) ($system['smtp_username'] ?? ''));
    $password = (string) ($system['smtp_password'] ?? '');
    $fromEmail = trim((string) ($company['company_email'] ?? $username));
    $fromName = trim((string) ($company['company_name'] ?? 'AfriSense Food Services'));

    // Guard this block so it only runs when the required condition is met.
    if ($host === '' || $fromEmail === '') {
        return ['success' => false, 'message' => 'SMTP host and sender email are required.'];
    }

    $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . max(1, $port);
    $socket = @stream_socket_client($remote, $errorCode, $errorMessage, 20, STREAM_CLIENT_CONNECT);

    // Guard this block so it only runs when the required condition is met.
    if (!$socket) {
        return ['success' => false, 'message' => $errorMessage !== '' ? $errorMessage : 'SMTP connection failed.', 'status_code' => $errorCode];
    }

    stream_set_timeout($socket, 20);

    // Run database/action work inside a guarded block so the page can fail gracefully.
    try {
        afrisense_smtp_command($socket, '', ['220']);
        afrisense_smtp_command($socket, 'EHLO localhost', ['250']);

        // Guard this block so it only runs when the required condition is met.
        if ($encryption === 'tls') {
            afrisense_smtp_command($socket, 'STARTTLS', ['220']);

            // Guard this block so it only runs when the required condition is met.
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('SMTP TLS negotiation failed.');
            }

            afrisense_smtp_command($socket, 'EHLO localhost', ['250']);
        }

        // Guard this block so it only runs when the required condition is met.
        if ($username !== '') {
            afrisense_smtp_command($socket, 'AUTH LOGIN', ['334']);
            afrisense_smtp_command($socket, base64_encode($username), ['334']);
            afrisense_smtp_command($socket, base64_encode($password), ['235']);
        }

        $from = sprintf('"%s" <%s>', addcslashes($fromName, '"\\'), $fromEmail);
        afrisense_smtp_command($socket, 'MAIL FROM:<' . $fromEmail . '>', ['250']);
        afrisense_smtp_command($socket, 'RCPT TO:<' . $toEmail . '>', ['250', '251']);
        afrisense_smtp_command($socket, 'DATA', ['354']);
        fwrite($socket, afrisense_smtp_message($from, $toEmail, $toName, $subject, $html, $text) . "\r\n.\r\n");
        afrisense_smtp_command($socket, '', ['250']);
        afrisense_smtp_command($socket, 'QUIT', ['221']);
        fclose($socket);

        return ['success' => true, 'message' => 'Email sent.'];
    } catch (Throwable $exception) {
        fclose($socket);

        return ['success' => false, 'message' => $exception->getMessage()];
    }
}

// Defines the afrisense_send_email helper used by this module.
function afrisense_send_email(string $toEmail, string $toName, string $subject, string $html, string $text): array
{
    $databaseMailSettings = afrisense_mail_database_settings();
    $systemMailSettings = $databaseMailSettings['system'];

    // Guard this block so it only runs when the required condition is met.
    if (trim((string) ($systemMailSettings['smtp_host'] ?? '')) !== '') {
        return afrisense_send_smtp_email($toEmail, $toName, $subject, $html, $text, $systemMailSettings, $databaseMailSettings['company']);
    }

    $mail = require __DIR__ . '/../../backend/config/mail.php';
    $apiKey = (string) ($mail['resend']['api_key'] ?? '');

    // Guard this block so it only runs when the required condition is met.
    if ($apiKey === '') {
        return ['success' => false, 'message' => 'Resend API key is missing.'];
    }

    // Guard this block so it only runs when the required condition is met.
    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'PHP cURL extension is not available.'];
    }

    $from = sprintf(
        '%s <%s>',
        (string) ($mail['resend']['from_name'] ?? 'AfriSense Food Services'),
        (string) ($mail['resend']['from_email'] ?? 'no-reply@afrisense.com')
    );

    $payload = json_encode([
        'from' => $from,
        'to' => [$toName !== '' ? sprintf('%s <%s>', $toName, $toEmail) : $toEmail],
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
    ], JSON_THROW_ON_ERROR);

    $curl = curl_init('https://api.resend.com/emails');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);

    $body = curl_exec($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    $decodedBody = [];

    // Guard this block so it only runs when the required condition is met.
    if (is_string($body) && $body !== '') {
        $decoded = json_decode($body, true);
        $decodedBody = is_array($decoded) ? $decoded : [];
    }

    // Guard this block so it only runs when the required condition is met.
    if ($body === false || $statusCode < 200 || $statusCode >= 300) {
        $providerMessage = (string) (
            $decodedBody['message']
            ?? $decodedBody['error']
            ?? $decodedBody['name']
            ?? ''
        );

        return [
            'success' => false,
            'message' => $error !== '' ? $error : ($providerMessage !== '' ? $providerMessage : 'Resend rejected the email request.'),
            'status_code' => $statusCode,
        ];
    }

    return [
        'success' => true,
        'message' => 'Email sent.',
        'provider_id' => (string) ($decodedBody['id'] ?? ''),
        'status_code' => $statusCode,
    ];
}

// Defines the afrisense_send_verification_email helper used by this module.
function afrisense_send_verification_email(string $email, string $fullname, string $token): array
{
    $url = afrisense_app_url() . '/frontend/auth/verify-email.php?token=' . urlencode($token);
    $safeName = htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $html = <<<HTML
        <h2>Verify your AfriSense account</h2>
        <p>Hello {$safeName},</p>
        <p>Click the button below to verify your email address.</p>
        <p><a href="{$safeUrl}" style="background:#b77b1a;color:#ffffff;padding:12px 18px;text-decoration:none;border-radius:6px;">Verify Email</a></p>
        <p>If the button does not work, copy this link into your browser:</p>
        <p>{$safeUrl}</p>
    HTML;
    $text = "Hello {$fullname}, verify your AfriSense account using this link: {$url}";

    return afrisense_send_email($email, $fullname, 'Verify your AfriSense account', $html, $text);
}

// Defines the afrisense_verify_email_token helper used by this module.
function afrisense_verify_email_token(string $token): array
{
    // Guard this block so it only runs when the required condition is met.
    if ($token === '') {
        return ['success' => false, 'message' => 'Verification token is missing.'];
    }

    $pdo = afrisense_pdo();
    $statement = $pdo->prepare(
        'SELECT `id`, `email`
         FROM `users`
         WHERE `verification_token` = :token
           AND `verification_token_expires` >= NOW()
         LIMIT 1'
    );
    $statement->execute(['token' => $token]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    // Guard this block so it only runs when the required condition is met.
    if ($user === false) {
        return ['success' => false, 'message' => 'This verification link is invalid or expired.'];
    }

    $updateUser = $pdo->prepare(
        'UPDATE `users`
         SET `email_verified` = 1,
             `verification_token` = NULL,
             `verification_token_expires` = NULL
         WHERE `id` = :id'
    );
    $updateUser->execute(['id' => (int) $user['id']]);

    $updateCustomer = $pdo->prepare(
        'UPDATE `customers`
         SET `email_verified` = 1,
             `verification_token` = NULL,
             `verification_token_expires` = NULL
         WHERE `email` = :email'
    );
    $updateCustomer->execute(['email' => (string) $user['email']]);

    return ['success' => true, 'message' => 'Email verified. You can now request password resets.'];
}

// Defines the afrisense_request_password_reset helper used by this module.
function afrisense_request_password_reset(string $email): array
{
    $email = trim($email);

    // Guard this block so it only runs when the required condition is met.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    $pdo = afrisense_pdo();
    $statement = $pdo->prepare('SELECT `id`, `fullname`, `email_verified` FROM `users` WHERE `email` = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    // Guard this block so it only runs when the required condition is met.
    if ($user === false) {
        return ['success' => true, 'message' => 'If the email exists, a reset link will be sent.'];
    }

    // Guard this block so it only runs when the required condition is met.
    if ((int) ($user['email_verified'] ?? 0) !== 1) {
        return ['success' => false, 'message' => 'Please verify your email before requesting a password reset.'];
    }

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + (15 * 60));
    $update = $pdo->prepare('UPDATE `users` SET `reset_token` = :token, `reset_token_expires` = :expires WHERE `id` = :id');
    $update->execute(['token' => $token, 'expires' => $expires, 'id' => (int) $user['id']]);

    $mail = require __DIR__ . '/../../backend/config/mail.php';
    $url = rtrim((string) $mail['password_reset']['url'], '?') . '?token=' . urlencode($token);
    $safeName = htmlspecialchars((string) $user['fullname'], ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $html = <<<HTML
        <h2>Reset your AfriSense password</h2>
        <p>Hello {$safeName},</p>
        <p>Use the link below to reset your password. It expires in 15 minutes.</p>
        <p><a href="{$safeUrl}" style="background:#0d241e;color:#ffffff;padding:12px 18px;text-decoration:none;border-radius:6px;">Reset Password</a></p>
        <p>{$safeUrl}</p>
    HTML;
    $text = "Reset your AfriSense password using this link: {$url}";

    return afrisense_send_email($email, (string) $user['fullname'], 'Reset your AfriSense password', $html, $text);
}

// Defines the afrisense_reset_password helper used by this module.
function afrisense_reset_password(string $token, string $password, string $confirmPassword): array
{
    // Guard this block so it only runs when the required condition is met.
    if ($token === '') {
        return ['success' => false, 'message' => 'Reset token is missing.'];
    }

    // Guard this block so it only runs when the required condition is met.
    if ($password !== $confirmPassword) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }

    // Guard this block so it only runs when the required condition is met.
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    }

    $pdo = afrisense_pdo();
    $statement = $pdo->prepare(
        'SELECT `id`
         FROM `users`
         WHERE `reset_token` = :token
           AND `reset_token_expires` >= NOW()
           AND `email_verified` = 1
         LIMIT 1'
    );
    $statement->execute(['token' => $token]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    // Guard this block so it only runs when the required condition is met.
    if ($user === false) {
        return ['success' => false, 'message' => 'This reset link is invalid, expired, or the email is not verified.'];
    }

    $update = $pdo->prepare(
        'UPDATE `users`
         SET `password` = :password,
             `reset_token` = NULL,
             `reset_token_expires` = NULL
         WHERE `id` = :id'
    );
    $update->execute([
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'id' => (int) $user['id'],
    ]);

    return ['success' => true, 'message' => 'Password updated. You can now log in.'];
}
