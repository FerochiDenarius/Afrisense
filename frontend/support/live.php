<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/support_helpers.php';

// Guard this block so it only runs when the required condition is met.
if (class_exists('\AfriSense\Backend\Helpers\Session')) {
    \AfriSense\Backend\Helpers\Session::start();
}

header('Content-Type: application/json; charset=utf-8');

/**
 * Poll/send endpoint for live support chat.
 *
 * The same endpoint serves three contexts:
 * - admin: admins and support agents reply from the inbox
 * - customer: logged-in customers use their account conversation
 * - guest: anonymous users use a session-token conversation
 */
function afrisense_support_live_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Defines the afrisense_support_live_user helper used by this module.
function afrisense_support_live_user(): ?array
{
    // Guard this block so it only runs when the required condition is met.
    if (!function_exists('afrisense_current_user')) {
        return null;
    }

    // Run database/action work inside a guarded block so the page can fail gracefully.
    try {
        return afrisense_current_user();
    } catch (Throwable) {
        return null;
    }
}

// Defines the afrisense_support_live_response helper used by this module.
function afrisense_support_live_response(PDO $pdo, array $conversation, array $ownSenderTypes, bool $sent = false, int $sentMessageId = 0): array
{
    $messages = afrisense_support_messages($pdo, (int) ($conversation['id'] ?? 0));

    // Return both rendered HTML and message ids so the browser can refresh the
    // chat pane and decide whether to play incoming-message sounds.
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

// Defines the afrisense_support_live_latest_admin_conversation helper used by this module.
function afrisense_support_live_latest_admin_conversation(PDO $pdo, array $user): ?array
{
    $isAdmin = afrisense_is_administrator($user);

    // Guard this block so it only runs when the required condition is met.
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

// Run database/action work inside a guarded block so the page can fail gracefully.
try {
    $pdo = afrisense_pdo();
    afrisense_support_tables($pdo);

    // live_action is used by FormData posts; action is kept for older callers.
    $action = (string) ($_GET['action'] ?? $_POST['live_action'] ?? $_POST['action'] ?? 'poll');
    $context = (string) ($_GET['context'] ?? $_POST['context'] ?? '');
    $conversationId = (int) ($_GET['conversation_id'] ?? $_POST['conversation_id'] ?? 0);
    $user = afrisense_support_live_user();
    $conversation = null;
    $ownSenderTypes = [];
    $senderType = '';
    $senderUserId = null;
    $senderName = '';

    // Guard this block so it only runs when the required condition is met.
    if ($context === 'admin') {
        // Guard this block so it only runs when the required condition is met.
        if ($user === null || (!afrisense_is_administrator($user) && !afrisense_support_is_agent($user))) {
            afrisense_support_live_json(['success' => false, 'message' => 'Unauthorized support access.'], 403);
        }

        // Admins may open any conversation; support-agent users are limited to
        // conversations assigned to their account.
        $conversation = $conversationId > 0
            ? afrisense_support_get_conversation($pdo, $conversationId)
            : afrisense_support_live_latest_admin_conversation($pdo, $user);

        // Guard this block so it only runs when the required condition is met.
        if ($conversation !== null && !afrisense_is_administrator($user) && (int) ($conversation['agent_user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            $conversation = null;
        }

        $ownSenderTypes = ['agent'];
        $senderType = 'agent';
        $senderUserId = (int) ($user['id'] ?? 0);
        $senderName = trim((string) ($user['fullname'] ?? 'Admin'));
        $senderName = $senderName !== '' ? $senderName : 'Admin';
    } elseif ($context === 'customer') {
        // Guard this block so it only runs when the required condition is met.
        if ($user === null || !afrisense_is_customer($user)) {
            afrisense_support_live_json(['success' => false, 'message' => 'Customer login is required.'], 403);
        }

        // Customers can only poll/send against conversations owned by their
        // logged-in user id.
        if ($conversationId > 0) {
            $candidate = afrisense_support_get_conversation($pdo, $conversationId);
            // Guard this block so it only runs when the required condition is met.
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

        // Guests are authorized by their private support token, not by account.
        if ($conversationId > 0) {
            $candidate = afrisense_support_get_conversation($pdo, $conversationId);
            // Guard this block so it only runs when the required condition is met.
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

    // Guard this block so it only runs when the required condition is met.
    if ($action !== 'send') {
        // Polling an empty support state is valid; the first send creates the
        // conversation and the UI can keep polling afterwards.
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

    // Guard this block so it only runs when the required condition is met.
    if ($context === 'guest') {
        // Guest identity is captured on first message so admin can see who is
        // asking for help without requiring registration.
        $guestName = trim((string) ($_POST['guest_name'] ?? ''));
        $guestEmail = trim((string) ($_POST['guest_email'] ?? ''));
        $guestPhone = trim((string) ($_POST['guest_phone'] ?? ''));

        // Guard this block so it only runs when the required condition is met.
        if ($guestName === '' || $guestEmail === '' || $guestPhone === '' || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            afrisense_support_live_json(['success' => false, 'message' => 'Please provide valid guest contact details.'], 422);
        }
    }

    // Guard this block so it only runs when the required condition is met.
    if ($message === '' && !$hasAttachment) {
        afrisense_support_live_json(['success' => false, 'message' => 'Please type a message or choose an attachment before sending.'], 422);
    }

    // Guard this block so it only runs when the required condition is met.
    if ($conversation === null && $context === 'customer') {
        $conversation = afrisense_support_create_conversation($pdo, $user, 'user-' . (int) ($user['id'] ?? 0), $_POST);
    }

    // Guard this block so it only runs when the required condition is met.
    if ($conversation === null && $context === 'guest') {
        $conversation = afrisense_support_create_conversation($pdo, null, afrisense_support_guest_token(), $_POST);
    }

    // Guard this block so it only runs when the required condition is met.
    if ($conversation === null) {
        afrisense_support_live_json(['success' => false, 'message' => 'Support conversation could not be found.'], 404);
    }

    $messageBody = $message !== '' ? $message : 'Attachment sent.';
    $messageId = afrisense_support_add_message($pdo, (int) $conversation['id'], $senderType, $senderUserId, $senderName, $messageBody);
    $attachmentResult = is_array($attachment) ? afrisense_support_upload_attachment($pdo, $messageId, $attachment) : ['success' => true];
    $conversation = afrisense_support_get_conversation($pdo, (int) $conversation['id']) ?? $conversation;

    // Guard this block so it only runs when the required condition is met.
    if ($context === 'admin') {
        afrisense_support_notify_customer_reply($pdo, $conversation, 'AfriSense Support replied to your conversation.', (int) ($senderUserId ?? 0));
    } else {
        afrisense_support_notify_agent($pdo, $conversation, $senderName);
    }

    $response = afrisense_support_live_response($pdo, $conversation, $ownSenderTypes, true, $messageId);

    // Guard this block so it only runs when the required condition is met.
    if (!($attachmentResult['success'] ?? false)) {
        $response['success'] = false;
        $response['message'] = (string) ($attachmentResult['message'] ?? 'Attachment could not be uploaded.');
    }

    afrisense_support_live_json($response);
} catch (Throwable $exception) {
    afrisense_support_live_json(['success' => false, 'message' => 'Live support could not be loaded.'], 500);
}
