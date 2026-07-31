<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/public_settings.php';

function afrisense_support_tables(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `support_conversations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `public_token` VARCHAR(64) NOT NULL UNIQUE,
            `customer_id` INT NULL,
            `user_id` INT NULL,
            `agent_user_id` INT NULL,
            `guest_name` VARCHAR(120) NULL,
            `guest_email` VARCHAR(120) NULL,
            `guest_phone` VARCHAR(30) NULL,
            `subject` VARCHAR(160) NOT NULL DEFAULT "General Support",
            `status` ENUM("Open","Waiting","Resolved","Closed") NOT NULL DEFAULT "Open",
            `priority` ENUM("Low","Normal","High") NOT NULL DEFAULT "Normal",
            `last_message` TEXT NULL,
            `last_message_at` DATETIME NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_support_customer` (`customer_id`),
            INDEX `idx_support_user` (`user_id`),
            INDEX `idx_support_agent` (`agent_user_id`),
            INDEX `idx_support_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `support_messages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `conversation_id` INT NOT NULL,
            `sender_type` ENUM("customer","guest","agent","system") NOT NULL,
            `sender_user_id` INT NULL,
            `sender_name` VARCHAR(120) NOT NULL,
            `body` TEXT NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_support_messages_conversation` (`conversation_id`),
            CONSTRAINT `fk_support_messages_conversation`
                FOREIGN KEY (`conversation_id`) REFERENCES `support_conversations` (`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `support_attachments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `message_id` INT NOT NULL,
            `original_name` VARCHAR(255) NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `mime_type` VARCHAR(120) NULL,
            `file_size` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_support_attachments_message` (`message_id`),
            CONSTRAINT `fk_support_attachments_message`
                FOREIGN KEY (`message_id`) REFERENCES `support_messages` (`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function afrisense_support_default_agent(PDO $pdo): ?array
{
    $statement = $pdo->prepare(
        'SELECT u.`id`, u.`fullname`, u.`email`, r.`rolename`
         FROM `users` u
         INNER JOIN `roles` r ON r.`id` = u.`role_id`
         WHERE LOWER(r.`rolename`) IN ("support agent", "agent", "customer support", "support")
         ORDER BY u.`id` ASC
         LIMIT 1'
    );
    $statement->execute();
    $agent = $statement->fetch(PDO::FETCH_ASSOC);

    if ($agent !== false) {
        return $agent;
    }

    $statement = $pdo->prepare(
        'SELECT u.`id`, u.`fullname`, u.`email`, r.`rolename`
         FROM `users` u
         INNER JOIN `roles` r ON r.`id` = u.`role_id`
         WHERE LOWER(r.`rolename`) IN ("administrator", "admin", "super admin")
         ORDER BY u.`id` ASC
         LIMIT 1'
    );
    $statement->execute();
    $admin = $statement->fetch(PDO::FETCH_ASSOC);

    return $admin !== false ? $admin : null;
}

function afrisense_support_is_agent(?array $user): bool
{
    if ($user === null || !isset($user['id'])) {
        return false;
    }

    return in_array(afrisense_role_name($user), ['support agent', 'agent', 'customer support', 'support'], true);
}

function afrisense_support_customer_for_user(PDO $pdo, array $user): ?array
{
    $email = trim((string) ($user['email'] ?? ''));
    $phone = preg_replace('/\s+/', '', trim((string) ($user['phonenumber'] ?? $user['phone'] ?? '')));

    $statement = $pdo->prepare(
        'SELECT *
         FROM `customers`
         WHERE `email` = :email OR (:phone_check <> "" AND REPLACE(`phone_number`, " ", "") = :phone)
         ORDER BY `id` ASC
         LIMIT 1'
    );
    $statement->execute(['email' => $email, 'phone_check' => $phone, 'phone' => $phone]);
    $customer = $statement->fetch(PDO::FETCH_ASSOC);

    return $customer !== false ? $customer : null;
}

function afrisense_support_guest_token(): string
{
    \AfriSense\Backend\Helpers\Session::start();
    $token = (string) ($_SESSION['afrisense_support_guest_token'] ?? '');

    if ($token === '') {
        $token = bin2hex(random_bytes(24));
        $_SESSION['afrisense_support_guest_token'] = $token;
    }

    return $token;
}

function afrisense_support_unique_token(PDO $pdo, string $token): string
{
    $candidate = $token !== '' ? $token : bin2hex(random_bytes(24));
    $statement = $pdo->prepare('SELECT COUNT(*) AS count_value FROM `support_conversations` WHERE `public_token` = :token');

    while (true) {
        $statement->execute(['token' => $candidate]);
        $count = (int) ($statement->fetch(PDO::FETCH_ASSOC)['count_value'] ?? 0);

        if ($count === 0) {
            return $candidate;
        }

        $candidate = bin2hex(random_bytes(24));
    }
}

function afrisense_support_find_conversation(PDO $pdo, ?array $user, string $token): ?array
{
    if ($user !== null && isset($user['id'])) {
        $statement = $pdo->prepare(
            'SELECT *
             FROM `support_conversations`
             WHERE `user_id` = :user_id AND `status` <> "Closed"
             ORDER BY `updated_at` DESC, `id` DESC
             LIMIT 1'
        );
        $statement->execute(['user_id' => (int) $user['id']]);
    } else {
        $statement = $pdo->prepare(
            'SELECT *
             FROM `support_conversations`
             WHERE `public_token` = :token AND `status` <> "Closed"
             ORDER BY `updated_at` DESC, `id` DESC
             LIMIT 1'
        );
        $statement->execute(['token' => $token]);
    }

    $conversation = $statement->fetch(PDO::FETCH_ASSOC);

    return $conversation !== false ? $conversation : null;
}

function afrisense_support_create_conversation(PDO $pdo, ?array $user, string $token, array $request = []): array
{
    $token = afrisense_support_unique_token($pdo, $token);
    $agent = afrisense_support_default_agent($pdo);
    $customer = $user !== null ? afrisense_support_customer_for_user($pdo, $user) : null;
    $name = trim((string) ($request['guest_name'] ?? $user['fullname'] ?? 'Guest User'));
    $email = trim((string) ($request['guest_email'] ?? $user['email'] ?? ''));
    $phone = trim((string) ($request['guest_phone'] ?? $user['phonenumber'] ?? $user['phone'] ?? ''));
    $subject = trim((string) ($request['subject'] ?? 'General Support'));

    if ($subject === '') {
        $subject = 'General Support';
    }

    $statement = $pdo->prepare(
        'INSERT INTO `support_conversations`
            (`public_token`, `customer_id`, `user_id`, `agent_user_id`, `guest_name`, `guest_email`, `guest_phone`, `subject`, `last_message`, `last_message_at`)
         VALUES
            (:public_token, :customer_id, :user_id, :agent_user_id, :guest_name, :guest_email, :guest_phone, :subject, :last_message, NOW())'
    );
    $statement->execute([
        'public_token' => $token,
        'customer_id' => $customer !== null ? (int) $customer['id'] : null,
        'user_id' => $user !== null ? (int) ($user['id'] ?? 0) : null,
        'agent_user_id' => $agent !== null ? (int) $agent['id'] : null,
        'guest_name' => $name,
        'guest_email' => $email,
        'guest_phone' => $phone,
        'subject' => $subject,
        'last_message' => 'Support conversation started.',
    ]);

    $conversationId = (int) $pdo->lastInsertId();
    $agentName = trim((string) ($agent['fullname'] ?? 'AfriSense Support'));
    $agentName = $agentName !== '' ? $agentName : 'AfriSense Support';

    afrisense_support_add_message(
        $pdo,
        $conversationId,
        'agent',
        $agent !== null ? (int) $agent['id'] : null,
        $agentName,
        'Hello! Thank you for contacting AfriSense Support. How can I assist you today?'
    );

    return afrisense_support_get_conversation($pdo, $conversationId) ?? [];
}

function afrisense_support_get_conversation(PDO $pdo, int $conversationId): ?array
{
    $statement = $pdo->prepare(
        'SELECT sc.*, u.`fullname` AS agent_name, u.`email` AS agent_email, r.`rolename` AS agent_role
         FROM `support_conversations` sc
         LEFT JOIN `users` u ON u.`id` = sc.`agent_user_id`
         LEFT JOIN `roles` r ON r.`id` = u.`role_id`
         WHERE sc.`id` = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $conversationId]);
    $conversation = $statement->fetch(PDO::FETCH_ASSOC);

    return $conversation !== false ? $conversation : null;
}

function afrisense_support_add_message(PDO $pdo, int $conversationId, string $senderType, ?int $senderUserId, string $senderName, string $body): int
{
    $insert = $pdo->prepare(
        'INSERT INTO `support_messages`
            (`conversation_id`, `sender_type`, `sender_user_id`, `sender_name`, `body`)
         VALUES
            (:conversation_id, :sender_type, :sender_user_id, :sender_name, :body)'
    );
    $insert->execute([
        'conversation_id' => $conversationId,
        'sender_type' => $senderType,
        'sender_user_id' => $senderUserId,
        'sender_name' => $senderName,
        'body' => $body,
    ]);
    $messageId = (int) $pdo->lastInsertId();

    $nextStatus = in_array($senderType, ['customer', 'guest'], true) ? 'Waiting' : 'Open';
    $update = $pdo->prepare(
        'UPDATE `support_conversations`
         SET `last_message` = :last_message,
             `last_message_at` = NOW(),
             `updated_at` = NOW(),
             `status` = :status
         WHERE `id` = :id'
    );
    $update->execute([
        'last_message' => $body,
        'status' => $nextStatus,
        'id' => $conversationId,
    ]);

    return $messageId;
}

function afrisense_support_upload_attachment(PDO $pdo, int $messageId, array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'message' => 'No attachment selected.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Attachment could not be uploaded.'];
    }

    $originalName = basename((string) ($file['name'] ?? 'attachment'));
    $size = (int) ($file['size'] ?? 0);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov', 'm4v', 'pdf', 'doc', 'docx', 'txt'];

    if ($size > 25 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Attachment is too large. Maximum size is 25MB.'];
    }

    if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
        return ['success' => false, 'message' => 'Attachment type is not allowed.'];
    }

    $uploadDir = __DIR__ . '/../uploads/support';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        return ['success' => false, 'message' => 'Attachment folder could not be created.'];
    }

    $storedName = 'support-' . bin2hex(random_bytes(10)) . '.' . $extension;
    $targetPath = $uploadDir . '/' . $storedName;

    if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $targetPath)) {
        return ['success' => false, 'message' => 'Attachment could not be saved.'];
    }

    $publicPath = '/Afrisense/frontend/uploads/support/' . $storedName;
    $insert = $pdo->prepare(
        'INSERT INTO `support_attachments`
            (`message_id`, `original_name`, `file_path`, `mime_type`, `file_size`)
         VALUES
            (:message_id, :original_name, :file_path, :mime_type, :file_size)'
    );
    $insert->execute([
        'message_id' => $messageId,
        'original_name' => $originalName,
        'file_path' => $publicPath,
        'mime_type' => (string) ($file['type'] ?? ''),
        'file_size' => $size,
    ]);

    return ['success' => true, 'message' => 'Attachment uploaded.'];
}

function afrisense_support_attachment_kind(array $attachment): string
{
    $mimeType = strtolower((string) ($attachment['mime_type'] ?? ''));
    $extension = strtolower(pathinfo((string) ($attachment['original_name'] ?? $attachment['file_path'] ?? ''), PATHINFO_EXTENSION));

    if (str_starts_with($mimeType, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return 'image';
    }

    if (str_starts_with($mimeType, 'video/') || in_array($extension, ['mp4', 'webm', 'mov', 'm4v'], true)) {
        return 'video';
    }

    return 'file';
}

function afrisense_support_attachment_html(array $attachment): string
{
    $path = (string) ($attachment['file_path'] ?? '#');
    $name = trim((string) ($attachment['original_name'] ?? 'Attachment'));
    $safePath = htmlspecialchars($path !== '' ? $path : '#', ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars($name !== '' ? $name : 'Attachment', ENT_QUOTES, 'UTF-8');
    $kind = afrisense_support_attachment_kind($attachment);

    if ($kind === 'image') {
        return '<a class="af-media-attachment is-image" href="' . $safePath . '" target="_blank" rel="noopener"><img src="' . $safePath . '" alt="' . $safeName . '"><span>' . $safeName . '</span></a>';
    }

    if ($kind === 'video') {
        return '<div class="af-media-attachment is-video"><video controls preload="metadata"><source src="' . $safePath . '"></video><a href="' . $safePath . '" target="_blank" rel="noopener">' . $safeName . '</a></div>';
    }

    return '<a class="af-file-attachment" href="' . $safePath . '" target="_blank" rel="noopener"><i class="bi bi-paperclip" aria-hidden="true"></i>' . $safeName . '</a>';
}

function afrisense_support_notify_agent(PDO $pdo, array $conversation, string $customerName): void
{
    $agentId = (int) ($conversation['agent_user_id'] ?? 0);

    if ($agentId <= 0) {
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO `notifications`
            (`user_id`, `title`, `message`, `notification_type`, `action_url`, `created_by`)
         VALUES
            (:user_id, :title, :message, :notification_type, :action_url, :created_by)'
    );
    $statement->execute([
        'user_id' => $agentId,
        'title' => 'New Support Message',
        'message' => $customerName . ' sent a support message.',
        'notification_type' => 'Enquiry',
        'action_url' => '/Afrisense/frontend/admin/support.php?view=' . (int) ($conversation['id'] ?? 0),
        'created_by' => null,
    ]);
}

function afrisense_support_messages(PDO $pdo, int $conversationId): array
{
    $statement = $pdo->prepare(
        'SELECT *
         FROM `support_messages`
         WHERE `conversation_id` = :conversation_id
         ORDER BY `created_at` ASC, `id` ASC'
    );
    $statement->execute(['conversation_id' => $conversationId]);

    $messages = $statement->fetchAll(PDO::FETCH_ASSOC);
    $messageIds = array_map(static fn (array $message): int => (int) ($message['id'] ?? 0), $messages);
    $messageIds = array_values(array_filter($messageIds, static fn (int $id): bool => $id > 0));

    foreach ($messages as &$message) {
        $message['attachments'] = [];
    }
    unset($message);

    if ($messageIds === []) {
        return $messages;
    }

    $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
    $attachmentsStatement = $pdo->prepare(
        'SELECT *
         FROM `support_attachments`
         WHERE `message_id` IN (' . $placeholders . ')
         ORDER BY `id` ASC'
    );
    $attachmentsStatement->execute($messageIds);

    $messageIndex = [];
    foreach ($messages as $index => $message) {
        $messageIndex[(int) $message['id']] = $index;
    }

    foreach ($attachmentsStatement->fetchAll(PDO::FETCH_ASSOC) as $attachment) {
        $messageId = (int) ($attachment['message_id'] ?? 0);
        if (isset($messageIndex[$messageId])) {
            $messages[$messageIndex[$messageId]]['attachments'][] = $attachment;
        }
    }

    return $messages;
}

function afrisense_support_conversation_label(array $conversation): string
{
    $id = (int) ($conversation['id'] ?? 0);

    return '#SUP' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

function afrisense_support_time(?string $value): string
{
    $timestamp = strtotime((string) $value);

    return $timestamp ? date('g:i A', $timestamp) : date('g:i A');
}
