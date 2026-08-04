<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/support_helpers.php';

if (class_exists('\AfriSense\Backend\Helpers\Session')) {
    \AfriSense\Backend\Helpers\Session::start();
}

header('Content-Type: application/json; charset=utf-8');

function afrisense_support_live_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function afrisense_support_live_user(): ?array
{
    if (!function_exists('afrisense_current_user')) {
        return null;
    }

    try {
        return afrisense_current_user();
    } catch (Throwable) {
        return null;
    }
}

function afrisense_support_live_response(PDO $pdo, array $conversation, array $ownSenderTypes, bool $sent = false, int $sentMessageId = 0): array
{
    $messages = afrisense_support_messages($pdo, (int) ($conversation['id'] ?? 0));

    return [
        'success' => true,
        'sent' => $sent,
        'sent_message_id' => $sentMessageId,
        'conversation_id' => (int) ($conversation['id'] ?? 0),
        'status' => (string) ($conversation['status'] ?? 'Open'),
        'messages_html' => afrisense_support_messages_html($conversation, $messages, $ownSenderTypes),
        'last_message_id' => afrisense_support_last_message_id($messages),
        'last_incoming_message_id' => afrisense_support_last_incoming_message_id($messages, $ownSenderTypes),
    ];
}

function afrisense_support_live_latest_admin_conversation(PDO $pdo, array $user): ?array
{
    $isAdmin = afrisense_is_administrator($user);

    if ($isAdmin) {
        $statement = $pdo->query('SELECT `id` FROM `support_conversations` ORDER BY `updated_at` DESC, `id` DESC LIMIT 1');
        $conversationId = (int) ($statement->fetchColumn() ?: 0);
    } else {
        $statement = $pdo->prepare(
            'SELECT `id`
             FROM `support_conversations`
             WHERE `agent_user_id` = :agent_user_id
             ORDER BY `updated_at` DESC, `id` DESC
             LIMIT 1'
        );
        $statement->execute(['agent_user_id' => (int) ($user['id'] ?? 0)]);
        $conversationId = (int) ($statement->fetchColumn() ?: 0);
    }

    return $conversationId > 0 ? afrisense_support_get_conversation($pdo, $conversationId) : null;
}

try {
    $pdo = afrisense_pdo();
    afrisense_support_tables($pdo);

    $action = (string) ($_GET['action'] ?? $_POST['live_action'] ?? $_POST['action'] ?? 'poll');
    $context = (string) ($_GET['context'] ?? $_POST['context'] ?? '');
    $conversationId = (int) ($_GET['conversation_id'] ?? $_POST['conversation_id'] ?? 0);
    $user = afrisense_support_live_user();
    $conversation = null;
    $ownSenderTypes = [];
    $senderType = '';
    $senderUserId = null;
    $senderName = '';

    if ($context === 'admin') {
        if ($user === null || (!afrisense_is_administrator($user) && !afrisense_support_is_agent($user))) {
            afrisense_support_live_json(['success' => false, 'message' => 'Unauthorized support access.'], 403);
        }

        $conversation = $conversationId > 0
            ? afrisense_support_get_conversation($pdo, $conversationId)
            : afrisense_support_live_latest_admin_conversation($pdo, $user);

        if ($conversation !== null && !afrisense_is_administrator($user) && (int) ($conversation['agent_user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            $conversation = null;
        }

        $ownSenderTypes = ['agent'];
        $senderType = 'agent';
        $senderUserId = (int) ($user['id'] ?? 0);
        $senderName = trim((string) ($user['fullname'] ?? 'Admin'));
        $senderName = $senderName !== '' ? $senderName : 'Admin';
    } elseif ($context === 'customer') {
        if ($user === null || !afrisense_is_customer($user)) {
            afrisense_support_live_json(['success' => false, 'message' => 'Customer login is required.'], 403);
        }

        if ($conversationId > 0) {
            $candidate = afrisense_support_get_conversation($pdo, $conversationId);
            if ($candidate !== null && (int) ($candidate['user_id'] ?? 0) === (int) ($user['id'] ?? 0)) {
                $conversation = $candidate;
            }
        }

        $conversation ??= afrisense_support_find_conversation($pdo, $user, 'user-' . (int) ($user['id'] ?? 0));
        $ownSenderTypes = ['customer', 'guest'];
        $senderType = 'customer';
        $senderUserId = (int) ($user['id'] ?? 0);
        $senderName = trim((string) ($user['fullname'] ?? 'Customer'));
        $senderName = $senderName !== '' ? $senderName : 'Customer';
    } elseif ($context === 'guest') {
        $token = afrisense_support_guest_token();

        if ($conversationId > 0) {
            $candidate = afrisense_support_get_conversation($pdo, $conversationId);
            if ($candidate !== null && hash_equals((string) ($candidate['public_token'] ?? ''), $token)) {
                $conversation = $candidate;
            }
        }

        $conversation ??= afrisense_support_find_conversation($pdo, null, $token);
        $ownSenderTypes = ['customer', 'guest'];
        $senderType = 'guest';
        $senderName = trim((string) ($_POST['guest_name'] ?? 'Guest User'));
        $senderName = $senderName !== '' ? $senderName : 'Guest User';
    } else {
        afrisense_support_live_json(['success' => false, 'message' => 'Invalid support context.'], 400);
    }

    if ($action !== 'send') {
        if ($conversation === null) {
            afrisense_support_live_json([
                'success' => true,
                'conversation_id' => 0,
                'messages_html' => '',
                'last_message_id' => 0,
                'last_incoming_message_id' => 0,
            ]);
        }

        afrisense_support_live_json(afrisense_support_live_response($pdo, $conversation, $ownSenderTypes));
    }

    $message = trim((string) ($_POST['message'] ?? ''));
    $attachment = $_FILES['attachment'] ?? null;
    $hasAttachment = is_array($attachment) && (int) ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($context === 'guest') {
        $guestName = trim((string) ($_POST['guest_name'] ?? ''));
        $guestEmail = trim((string) ($_POST['guest_email'] ?? ''));
        $guestPhone = trim((string) ($_POST['guest_phone'] ?? ''));

        if ($guestName === '' || $guestEmail === '' || $guestPhone === '' || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            afrisense_support_live_json(['success' => false, 'message' => 'Please provide valid guest contact details.'], 422);
        }
    }

    if ($message === '' && !$hasAttachment) {
        afrisense_support_live_json(['success' => false, 'message' => 'Please type a message or choose an attachment before sending.'], 422);
    }

    if ($conversation === null && $context === 'customer') {
        $conversation = afrisense_support_create_conversation($pdo, $user, 'user-' . (int) ($user['id'] ?? 0), $_POST);
    }

    if ($conversation === null && $context === 'guest') {
        $conversation = afrisense_support_create_conversation($pdo, null, afrisense_support_guest_token(), $_POST);
    }

    if ($conversation === null) {
        afrisense_support_live_json(['success' => false, 'message' => 'Support conversation could not be found.'], 404);
    }

    $messageBody = $message !== '' ? $message : 'Attachment sent.';
    $messageId = afrisense_support_add_message($pdo, (int) $conversation['id'], $senderType, $senderUserId, $senderName, $messageBody);
    $attachmentResult = is_array($attachment) ? afrisense_support_upload_attachment($pdo, $messageId, $attachment) : ['success' => true];
    $conversation = afrisense_support_get_conversation($pdo, (int) $conversation['id']) ?? $conversation;

    if ($context === 'admin') {
        afrisense_support_notify_customer_reply($pdo, $conversation, 'AfriSense Support replied to your conversation.', (int) ($senderUserId ?? 0));
    } else {
        afrisense_support_notify_agent($pdo, $conversation, $senderName);
    }

    $response = afrisense_support_live_response($pdo, $conversation, $ownSenderTypes, true, $messageId);

    if (!($attachmentResult['success'] ?? false)) {
        $response['success'] = false;
        $response['message'] = (string) ($attachmentResult['message'] ?? 'Attachment could not be uploaded.');
    }

    afrisense_support_live_json($response);
} catch (Throwable $exception) {
    afrisense_support_live_json(['success' => false, 'message' => 'Live support could not be loaded.'], 500);
}
