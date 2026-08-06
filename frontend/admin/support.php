<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Support Inbox | AfriSense';
$adminTitle = 'Support Inbox';
$activeAdminPage = 'support';
$supportCssVersion = (string) (filemtime(__DIR__ . '/../assets/css/support.css') ?: time());
$supportJsVersion = (string) (filemtime(__DIR__ . '/../assets/js/support.js') ?: time());
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
    $frontendBase . '/assets/css/support.css?v=' . $supportCssVersion,
];
$extraScripts = [$frontendBase . '/assets/js/support.js?v=' . $supportJsVersion];

require_once __DIR__ . '/../includes/support_helpers.php';

$authUser = afrisense_require_user();

// Guard this block so it only runs when the required condition is met.
if (!afrisense_is_administrator($authUser) && !afrisense_support_is_agent($authUser)) {
    header('Location: /Afrisense/frontend/customer/dashboard.php');
    exit;
}

$allowSupportStaff = true;
$adminUserId = (int) ($authUser['id'] ?? 0);
$adminName = (string) ($authUser['fullname'] ?? 'Admin');
$isAdminUser = afrisense_is_administrator($authUser);
$flashMessage = '';
$flashType = 'success';
$conversations = [];
$selectedConversation = null;
$selectedMessages = [];
$viewConversationId = (int) ($_GET['view'] ?? 0);

// Defines the afrisense_admin_support_notify_customer helper used by this module.
function afrisense_admin_support_notify_customer(PDO $pdo, array $conversation, string $message, int $adminUserId): void
{
    $userId = (int) ($conversation['user_id'] ?? 0);

    // Guard this block so it only runs when the required condition is met.
    if ($userId <= 0) {
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO `notifications`
            (`user_id`, `title`, `message`, `notification_type`, `action_url`, `created_by`)
         VALUES
            (:user_id, :title, :message, :notification_type, :action_url, :created_by)'
    );
    $statement->execute([
        'user_id' => $userId,
        'title' => 'Support Reply',
        'message' => $message,
        'notification_type' => 'Enquiry',
        'action_url' => '/Afrisense/frontend/customer/support.php',
        'created_by' => $adminUserId,
    ]);
}

// Run database/action work inside a guarded block so the page can fail gracefully.
try {
    $pdo = afrisense_pdo();
    afrisense_support_tables($pdo);

    // Handle submitted form actions before rendering the page.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $conversationId = (int) ($_POST['conversation_id'] ?? 0);
        $selectedConversation = $conversationId > 0 ? afrisense_support_get_conversation($pdo, $conversationId) : null;

        // Guard this block so it only runs when the required condition is met.
        if ($selectedConversation !== null && !$isAdminUser && (int) ($selectedConversation['agent_user_id'] ?? 0) !== $adminUserId) {
            $selectedConversation = null;
        }

        // Guard this block so it only runs when the required condition is met.
        if ($selectedConversation === null) {
            $flashType = 'error';
            $flashMessage = 'Support conversation could not be found.';
        } elseif ($action === 'reply') {
            $message = trim((string) ($_POST['message'] ?? ''));
            $attachment = $_FILES['attachment'] ?? null;
            $hasAttachment = is_array($attachment) && (int) ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

            // Guard this block so it only runs when the required condition is met.
            if ($message === '' && !$hasAttachment) {
                $flashType = 'error';
                $flashMessage = 'Please type a reply or choose an attachment before sending.';
            } else {
                $messageBody = $message !== '' ? $message : 'Attachment sent.';
                $messageId = afrisense_support_add_message($pdo, $conversationId, 'agent', $adminUserId, $adminName, $messageBody);
                $attachmentResult = is_array($attachment) ? afrisense_support_upload_attachment($pdo, $messageId, $attachment) : ['success' => true];
                afrisense_admin_support_notify_customer($pdo, $selectedConversation, 'AfriSense Support replied to your conversation.', $adminUserId);
                // Guard this block so it only runs when the required condition is met.
                if ($attachmentResult['success'] ?? false) {
                    $flashMessage = 'Reply sent.';
                } else {
                    $flashType = 'error';
                    $flashMessage = (string) ($attachmentResult['message'] ?? 'Attachment could not be uploaded.');
                }
                $viewConversationId = $conversationId;
            }
        } elseif ($action === 'status') {
            $nextStatus = (string) ($_POST['status'] ?? 'Open');
            $allowedStatuses = ['Open', 'Waiting', 'Resolved', 'Closed'];

            // Guard this block so it only runs when the required condition is met.
            if (!in_array($nextStatus, $allowedStatuses, true)) {
                $flashType = 'error';
                $flashMessage = 'Support status could not be updated.';
            } else {
                $update = $pdo->prepare('UPDATE `support_conversations` SET `status` = :status, `updated_at` = NOW() WHERE `id` = :id');
                $update->execute(['status' => $nextStatus, 'id' => $conversationId]);
                $flashMessage = 'Support conversation updated to ' . $nextStatus . '.';
                $viewConversationId = $conversationId;
            }
        }
    }

    $statement = $pdo->prepare(
        'SELECT sc.*, u.`fullname` AS agent_name
         FROM `support_conversations` sc
         LEFT JOIN `users` u ON u.`id` = sc.`agent_user_id`
         ORDER BY sc.`updated_at` DESC, sc.`id` DESC
         LIMIT 40'
    );
    $statement->execute();
    $conversations = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Guard this block so it only runs when the required condition is met.
    if ($viewConversationId <= 0 && $conversations !== []) {
        $viewConversationId = (int) $conversations[0]['id'];
    }

    // Guard this block so it only runs when the required condition is met.
    if ($viewConversationId > 0) {
        $selectedConversation = afrisense_support_get_conversation($pdo, $viewConversationId);

        // Guard this block so it only runs when the required condition is met.
        if ($selectedConversation !== null && !$isAdminUser && (int) ($selectedConversation['agent_user_id'] ?? 0) !== $adminUserId) {
            $selectedConversation = null;
        }

        $selectedMessages = $selectedConversation !== null ? afrisense_support_messages($pdo, $viewConversationId) : [];
    }
} catch (Throwable $exception) {
    $flashType = 'error';
    $flashMessage = 'Support inbox could not be loaded. Check that MySQL is running.';
}

ob_start();
?>
<!-- Page section for this part of the AfriSense interface. -->
<section
    class="af-admin-support-page"
    data-support-page
    data-support-context="admin"
    data-support-live-url="<?php echo htmlspecialchars($frontendBase . '/support/live.php', ENT_QUOTES, 'UTF-8'); ?>"
    data-support-sent-sound-url="<?php echo htmlspecialchars($frontendBase . '/assets/audio/sentSound.wav', ENT_QUOTES, 'UTF-8'); ?>"
    data-support-received-sound-url="<?php echo htmlspecialchars($frontendBase . '/assets/audio/receivedSound.wav', ENT_QUOTES, 'UTF-8'); ?>"
    data-support-conversation-id="<?php echo (int) ($selectedConversation['id'] ?? 0); ?>"
>
    <!-- Header block for this interface section. -->
    <header class="af-admin-page-heading">
        <div>
            <h1>Support Inbox</h1>
            <p>Dashboard / Support / Conversations</p>
        </div>
        <a class="af-add-menu-btn" href="<?php echo htmlspecialchars($frontendBase . '/admin/users.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-person-plus" aria-hidden="true"></i>
            Manage Agents
        </a>
    </header>

    <?php // Render this conditional/dynamic template block. ?>
    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="af-admin-support-grid">
        <!-- Side panel with supporting information and actions. -->
        <aside class="af-support-conversations" aria-label="Support conversations">
            <!-- Header block for this interface section. -->
            <header><h2>Conversations</h2></header>
            <?php // Render this conditional/dynamic template block. ?>
            <?php if ($conversations === []): ?>
                <p class="af-support-empty">No support conversations yet.</p>
            <?php endif; ?>
            <?php // Render this conditional/dynamic template block. ?>
            <?php foreach ($conversations as $conversation): ?>
                <?php
                $conversationId = (int) ($conversation['id'] ?? 0);
                $customerName = trim((string) ($conversation['guest_name'] ?? ''));
                $customerName = $customerName !== '' ? $customerName : 'Customer';
                ?>
                <a class="af-support-thread <?php echo $viewConversationId === $conversationId ? 'is-active' : ''; ?>" href="support.php?view=<?php echo $conversationId; ?>">
                    <span class="af-thread-icon"><i class="bi bi-headset" aria-hidden="true"></i></span>
                    <div>
                        <strong><?php echo htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?php echo htmlspecialchars((string) ($conversation['subject'] ?? 'Support'), ENT_QUOTES, 'UTF-8'); ?></small>
                        <p><?php echo htmlspecialchars((string) ($conversation['last_message'] ?? 'No messages yet.'), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <time><?php echo htmlspecialchars(afrisense_support_time((string) ($conversation['last_message_at'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></time>
                </a>
            <?php endforeach; ?>
        </aside>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-support-chat" aria-label="Selected support chat">
            <?php // Render this conditional/dynamic template block. ?>
            <?php if ($selectedConversation === null): ?>
                <div class="af-support-empty-state">
                    <i class="bi bi-headset" aria-hidden="true"></i>
                    <h2>Select a conversation</h2>
                    <p>Customer and guest support requests will appear here.</p>
                </div>
            <?php else: ?>
                <?php
                $selectedName = trim((string) ($selectedConversation['guest_name'] ?? ''));
                $selectedName = $selectedName !== '' ? $selectedName : 'Customer';
                ?>
                <!-- Header block for this interface section. -->
                <header class="af-chat-header">
                    <span class="af-agent-avatar"><i class="bi bi-person-circle" aria-hidden="true"></i></span>
                    <div>
                        <h2><?php echo htmlspecialchars($selectedName, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p><?php echo htmlspecialchars((string) ($selectedConversation['status'] ?? 'Open'), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </header>

                <div class="af-chat-body" aria-live="polite">
                    <?php echo afrisense_support_messages_html($selectedConversation, $selectedMessages, ['agent']); ?>
                </div>

                <!-- Form block that submits this page workflow. -->
                <form class="af-support-composer af-admin-reply-composer" action="support.php?view=<?php echo (int) $selectedConversation['id']; ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="conversation_id" value="<?php echo (int) $selectedConversation['id']; ?>">
                    <label class="af-message-input" for="admin_support_message">
                        <input id="admin_support_message" name="message" type="text" placeholder="Type your reply..." autocomplete="off">
                    </label>
                    <button class="af-composer-icon" type="button" title="Add emoji" aria-label="Add emoji" data-support-emoji-toggle><i class="bi bi-emoji-smile" aria-hidden="true"></i></button>
                    <button class="af-composer-icon" type="button" title="Attach file" aria-label="Attach file" data-support-attach-toggle><i class="bi bi-paperclip" aria-hidden="true"></i></button>
                    <input class="af-support-file-input" type="file" name="attachment" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.webm,.mov,.m4v,.pdf,.doc,.docx,.txt,image/*,video/*" data-support-file-input>
                    <span class="af-attachment-name" data-support-attachment-name></span>
                    <button class="af-send-chat af-send-reply" type="submit" title="Send reply" aria-label="Send reply" style="display:inline-flex;align-items:center;justify-content:center;min-width:118px;height:42px;border:0;border-radius:8px;background:#b77b1a;color:#ffffff;font-weight:850;gap:8px;padding:0 14px;box-shadow:0 10px 18px rgba(183,123,26,.24);">
                        <i class="bi bi-send" aria-hidden="true"></i><span>Send Reply</span>
                    </button>
                </form>
            <?php endif; ?>
        </section>

        <!-- Side panel with supporting information and actions. -->
        <aside class="af-support-side">
            <?php // Render this conditional/dynamic template block. ?>
            <?php if ($selectedConversation !== null): ?>
                <!-- Page section for this part of the AfriSense interface. -->
                <section class="af-support-info">
                    <h2><i class="bi bi-info-circle" aria-hidden="true"></i> Conversation Details</h2>
                    <article><span><i class="bi bi-hash" aria-hidden="true"></i></span><div><strong>Reference</strong><p><?php echo htmlspecialchars(afrisense_support_conversation_label($selectedConversation), ENT_QUOTES, 'UTF-8'); ?></p></div></article>
                    <article><span><i class="bi bi-envelope" aria-hidden="true"></i></span><div><strong>Email</strong><p><?php echo htmlspecialchars((string) ($selectedConversation['guest_email'] ?? 'Not provided'), ENT_QUOTES, 'UTF-8'); ?></p></div></article>
                    <article><span><i class="bi bi-telephone" aria-hidden="true"></i></span><div><strong>Phone</strong><p><?php echo htmlspecialchars((string) ($selectedConversation['guest_phone'] ?? 'Not provided'), ENT_QUOTES, 'UTF-8'); ?></p></div></article>
                    <article><span><i class="bi bi-person-headset" aria-hidden="true"></i></span><div><strong>Agent</strong><p><?php echo htmlspecialchars((string) ($selectedConversation['agent_name'] ?? $adminName), ENT_QUOTES, 'UTF-8'); ?></p></div></article>
                </section>

                <!-- Page section for this part of the AfriSense interface. -->
                <section class="af-help-topics">
                    <h2><i class="bi bi-check2-circle" aria-hidden="true"></i> Update Status</h2>
                    <?php // Render this conditional/dynamic template block. ?>
                    <?php foreach (['Open', 'Waiting', 'Resolved', 'Closed'] as $status): ?>
                        <!-- Form block that submits this page workflow. -->
                        <form action="support.php?view=<?php echo (int) $selectedConversation['id']; ?>" method="post">
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="conversation_id" value="<?php echo (int) $selectedConversation['id']; ?>">
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit"><i class="bi bi-chevron-right" aria-hidden="true"></i><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></button>
                        </form>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
