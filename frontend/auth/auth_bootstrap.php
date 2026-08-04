<?php

declare(strict_types=1);

use AfriSense\Backend\Config\Database;
use AfriSense\Backend\Helpers\Session;
use AfriSense\Backend\Models\Auth;
use AfriSense\Backend\Models\User;

require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/Session.php';
require_once __DIR__ . '/../../backend/models/Auth.php';
require_once __DIR__ . '/../../backend/models/User.php';

function afrisense_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = (new Database())->getConnection();

    return $pdo;
}

function afrisense_auth(): Auth
{
    static $auth = null;

    if ($auth instanceof Auth) {
        return $auth;
    }

    $auth = new Auth(afrisense_pdo());

    return $auth;
}

function afrisense_current_user(): ?array
{
    return afrisense_auth()->getCurrentUser();
}

function afrisense_role_name(?array $user): string
{
    if ($user === null || !isset($user['id'])) {
        return '';
    }

    $role = (new User(afrisense_pdo()))->getRole((int) $user['id']);

    return strtolower((string) ($role['rolename'] ?? $role['name'] ?? ''));
}

function afrisense_is_customer(?array $user): bool
{
    return afrisense_role_name($user) === 'customer';
}

function afrisense_is_administrator(?array $user): bool
{
    return in_array(afrisense_role_name($user), ['administrator', 'admin', 'super admin'], true);
}

function afrisense_is_support_staff(?array $user): bool
{
    return in_array(afrisense_role_name($user), ['support agent', 'agent', 'customer support', 'support'], true);
}

function afrisense_dashboard_url(?array $user): string
{
    if (afrisense_is_administrator($user)) {
        return '/Afrisense/frontend/admin/dashboard.php';
    }

    if (afrisense_is_support_staff($user)) {
        return '/Afrisense/frontend/admin/support.php';
    }

    return '/Afrisense/frontend/customer/dashboard.php';
}

function afrisense_require_user(): array
{
    $user = afrisense_current_user();

    if ($user === null) {
        header('Location: /Afrisense/frontend/auth/login.php');
        exit;
    }

    return $user;
}

function afrisense_require_customer(): array
{
    $user = afrisense_require_user();

    if (!afrisense_is_customer($user)) {
        header('Location: ' . afrisense_dashboard_url($user));
        exit;
    }

    return $user;
}

function afrisense_require_admin(): array
{
    $user = afrisense_require_user();

    if (!afrisense_is_administrator($user)) {
        header('Location: /Afrisense/frontend/customer/dashboard.php');
        exit;
    }

    return $user;
}

function afrisense_ensure_role(PDO $pdo, string $roleName, string $description = ''): int
{
    $statement = $pdo->prepare('SELECT `id` FROM `roles` WHERE LOWER(`rolename`) = LOWER(:role) LIMIT 1');
    $statement->execute(['role' => $roleName]);
    $role = $statement->fetch(PDO::FETCH_ASSOC);

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

function afrisense_customer_role_id(PDO $pdo): int
{
    return afrisense_ensure_role($pdo, 'Customer', 'Public customer account');
}

function afrisense_delivery_rider_role_id(PDO $pdo): int
{
    return afrisense_ensure_role($pdo, 'Delivery Rider', 'Handles delivery assignments, pickup and order delivery updates.');
}

function afrisense_unique_username(PDO $pdo, string $email, string $fallbackName): string
{
    $base = preg_replace('/[^a-z0-9_]/', '', strtolower(strtok($email, '@') ?: $fallbackName));
    $base = substr($base !== '' ? $base : 'customer', 0, 40);
    $candidate = $base;
    $counter = 1;

    $statement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `users` WHERE `username` = :username');

    while (true) {
        $statement->execute(['username' => $candidate]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (((int) ($row['count_value'] ?? 0)) === 0) {
            return $candidate;
        }

        $candidate = substr($base, 0, 35) . $counter;
        $counter++;
    }
}

function afrisense_register_customer(array $request): array
{
    $pdo = afrisense_pdo();
    $required = ['fullname', 'email', 'phone', 'password', 'confirm_password'];

    foreach ($required as $field) {
        if (trim((string) ($request[$field] ?? '')) === '') {
            return ['success' => false, 'message' => 'Please complete all required fields.'];
        }
    }

    if (!filter_var((string) $request['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    if ((string) $request['password'] !== (string) $request['confirm_password']) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }

    if (strlen((string) $request['password']) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    }

    if (($request['agree'] ?? '') !== '1') {
        return ['success' => false, 'message' => 'Please accept the terms and privacy policy.'];
    }

    $pdo->beginTransaction();

    try {
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

        if (!$mailResult['success']) {
            return [
                'success' => true,
                'message' => 'Account created, but the verification email could not be sent. Check your Resend sender domain.',
            ];
        }

        return ['success' => true, 'message' => 'Account created. Please check your email to verify your account.'];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if (str_contains($exception->getMessage(), 'Duplicate entry')) {
            return ['success' => false, 'message' => 'An account with this email or phone already exists.'];
        }

        return ['success' => false, 'message' => 'Account could not be created.'];
    }
}

function afrisense_flash_set(string $type, string $message): void
{
    Session::set('flash', ['type' => $type, 'message' => $message]);
}

function afrisense_flash_get(): ?array
{
    $flash = Session::get('flash');
    Session::remove('flash');

    return is_array($flash) ? $flash : null;
}

function afrisense_app_url(): string
{
    require_once __DIR__ . '/../../backend/config/env.php';

    return rtrim((string) ($_ENV['APP_URL'] ?? 'http://localhost/Afrisense'), '/');
}

function afrisense_admin_items_per_page(int $fallback = 25): int
{
    try {
        $statement = afrisense_pdo()->prepare('SELECT `items_per_page` FROM `system_settings` ORDER BY `id` ASC LIMIT 1');
        $statement->execute();
        $value = (int) ($statement->fetchColumn() ?: $fallback);

        return max(5, min(100, $value));
    } catch (Throwable $exception) {
        return max(5, min(100, $fallback));
    }
}

function afrisense_mail_database_settings(): array
{
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

function afrisense_smtp_response($socket): string
{
    $response = '';

    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;

        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }

    return $response;
}

function afrisense_smtp_command($socket, string $command, array $acceptedCodes): string
{
    if ($command !== '') {
        fwrite($socket, $command . "\r\n");
    }

    $response = afrisense_smtp_response($socket);
    $code = substr($response, 0, 3);

    if (!in_array($code, $acceptedCodes, true)) {
        throw new RuntimeException(trim($response) !== '' ? trim($response) : 'SMTP server did not respond as expected.');
    }

    return $response;
}

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

function afrisense_send_smtp_email(string $toEmail, string $toName, string $subject, string $html, string $text, array $system, array $company): array
{
    $host = trim((string) ($system['smtp_host'] ?? ''));
    $port = (int) ($system['smtp_port'] ?? 587);
    $encryption = strtolower(trim((string) ($system['smtp_encryption'] ?? 'tls')));
    $username = trim((string) ($system['smtp_username'] ?? ''));
    $password = (string) ($system['smtp_password'] ?? '');
    $fromEmail = trim((string) ($company['company_email'] ?? $username));
    $fromName = trim((string) ($company['company_name'] ?? 'AfriSense Food Services'));

    if ($host === '' || $fromEmail === '') {
        return ['success' => false, 'message' => 'SMTP host and sender email are required.'];
    }

    $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . max(1, $port);
    $socket = @stream_socket_client($remote, $errorCode, $errorMessage, 20, STREAM_CLIENT_CONNECT);

    if (!$socket) {
        return ['success' => false, 'message' => $errorMessage !== '' ? $errorMessage : 'SMTP connection failed.', 'status_code' => $errorCode];
    }

    stream_set_timeout($socket, 20);

    try {
        afrisense_smtp_command($socket, '', ['220']);
        afrisense_smtp_command($socket, 'EHLO localhost', ['250']);

        if ($encryption === 'tls') {
            afrisense_smtp_command($socket, 'STARTTLS', ['220']);

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('SMTP TLS negotiation failed.');
            }

            afrisense_smtp_command($socket, 'EHLO localhost', ['250']);
        }

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

function afrisense_send_email(string $toEmail, string $toName, string $subject, string $html, string $text): array
{
    $databaseMailSettings = afrisense_mail_database_settings();
    $systemMailSettings = $databaseMailSettings['system'];

    if (trim((string) ($systemMailSettings['smtp_host'] ?? '')) !== '') {
        return afrisense_send_smtp_email($toEmail, $toName, $subject, $html, $text, $systemMailSettings, $databaseMailSettings['company']);
    }

    $mail = require __DIR__ . '/../../backend/config/mail.php';
    $apiKey = (string) ($mail['resend']['api_key'] ?? '');

    if ($apiKey === '') {
        return ['success' => false, 'message' => 'Resend API key is missing.'];
    }

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

    if (is_string($body) && $body !== '') {
        $decoded = json_decode($body, true);
        $decodedBody = is_array($decoded) ? $decoded : [];
    }

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

function afrisense_verify_email_token(string $token): array
{
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

function afrisense_request_password_reset(string $email): array
{
    $email = trim($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    $pdo = afrisense_pdo();
    $statement = $pdo->prepare('SELECT `id`, `fullname`, `email_verified` FROM `users` WHERE `email` = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    if ($user === false) {
        return ['success' => true, 'message' => 'If the email exists, a reset link will be sent.'];
    }

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

function afrisense_reset_password(string $token, string $password, string $confirmPassword): array
{
    if ($token === '') {
        return ['success' => false, 'message' => 'Reset token is missing.'];
    }

    if ($password !== $confirmPassword) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }

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
