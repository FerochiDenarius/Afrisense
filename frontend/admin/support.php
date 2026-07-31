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

function afrisense_admin_support_notify_customer(PDO $pdo, array $conversation, string $message, int $adminUserId): void
{
    $userId = (int) ($conversation['user_id'] ?? 0);

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

try {
    $pdo = afrisense_pdo();
    afrisense_support_tables($pdo);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $conversationId = (int) ($_POST['conversation_id'] ?? 0);
        $selectedConversation = $conversationId > 0 ? afrisense_support_get_conversation($pdo, $conversationId) : null;

        if ($selectedConversation !== null && !$isAdminUser && (int) ($selectedConversation['agent_user_id'] ?? 0) !== $adminUserId) {
            $selectedConversation = null;
        }

        if ($selectedConversation === null) {
            $flashType = 'error';
            $flashMessage = 'Support conversation could not be found.';
        } elseif ($action === 'reply') {
            $message = trim((string) ($_POST['message'] ?? ''));
            $attachment = $_FILES['attachment'] ?? null;
            $hasAttachment = is_array($attachment) && (int) ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

            if ($message === '' && !$hasAttachment) {
                $flashType = 'error';
                $flashMessage = 'Please type a reply or choose an attachment before sending.';
            } else {
                $messageBody = $message !== '' ? $message : 'Attachment sent.';
                $messageId = afrisense_support_add_message($pdo, $conversationId, 'agent', $adminUserId, $adminName, $messageBody);
                $attachmentResult = is_array($attachment) ? afrisense_support_upload_attachment($pdo, $messageId, $attachment) : ['success' => true];
                afrisense_admin_support_notify_customer($pdo, $selectedConversation, 'AfriSense Support replied to your conversation.', $adminUserId);
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

    if ($viewConversationId <= 0 && $conversations !== []) {
        $viewConversationId = (int) $conversations[0]['id'];
    }

    if ($viewConversationId > 0) {
        $selectedConversation = afrisense_support_get_conversation($pdo, $viewConversationId);

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
<section class="af-admin-support-page" data-support-page>
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

    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="af-admin-support-grid">
        <aside class="af-support-conversations" aria-label="Support conversations">
            <header><h2>Conversations</h2></header>
            <?php if ($conversations === []): ?>
                <p class="af-support-empty">No support conversations yet.</p>
            <?php endif; ?>
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

        <section class="af-support-chat" aria-label="Selected support chat">
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
                <header class="af-chat-header">
                    <span class="af-agent-avatar"><i class="bi bi-person-circle" aria-hidden="true"></i></span>
                    <div>
                        <h2><?php echo htmlspecialchars($selectedName, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p><?php echo htmlspecialchars((string) ($selectedConversation['status'] ?? 'Open'), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </header>

                <div class="af-chat-body" aria-live="polite">
                    <time class="af-chat-date"><?php echo htmlspecialchars(afrisense_support_conversation_label($selectedConversation), ENT_QUOTES, 'UTF-8'); ?></time>
                    <?php foreach ($selectedMessages as $message): ?>
                        <?php $isAgent = (string) ($message['sender_type'] ?? '') === 'agent'; ?>
                        <article class="af-chat-message <?php echo $isAgent ? 'is-own' : 'is-agent'; ?>">
                            <?php if (!$isAgent): ?><span class="af-message-avatar"><i class="bi bi-person" aria-hidden="true"></i></span><?php endif; ?>
                            <div>
                                <p><?php echo nl2br(htmlspecialchars((string) ($message['body'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
                                <?php if (!empty($message['attachments']) && is_array($message['attachments'])): ?>
                                    <div class="af-message-attachments">
                                        <?php foreach ($message['attachments'] as $attachment): ?>
                                            <?php echo afrisense_support_attachment_html($attachment); ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <time><?php echo htmlspecialchars(afrisense_support_time((string) ($message['created_at'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></time>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <form class="af-support-composer af-admin-reply-composer" action="support.php?view=<?php echo (int) $selectedConversation['id']; ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="conversation_id" value="<?php echo (int) $selectedConversation['id']; ?>">
                    <label class="af-message-input" for="admin_support_message">
                        <input id="admin_support_message" name="message" type="text" placeholder="Type your reply..." autocomplete="off">
                    </label>
                    <button class="af-composer-icon" type="button" aria-label="Add emoji" data-support-emoji-toggle><i class="bi bi-emoji-smile" aria-hidden="true"></i></button>
                    <button class="af-composer-icon" type="button" aria-label="Attach file" data-support-attach-toggle><i class="bi bi-paperclip" aria-hidden="true"></i></button>
                    <input class="af-support-file-input" type="file" name="attachment" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.webm,.mov,.m4v,.pdf,.doc,.docx,.txt,image/*,video/*" data-support-file-input>
                    <span class="af-attachment-name" data-support-attachment-name></span>
                    <button class="af-send-chat af-send-reply" type="submit" aria-label="Send reply" style="display:inline-flex;align-items:center;justify-content:center;min-width:118px;height:42px;border:0;border-radius:8px;background:#b77b1a;color:#ffffff;font-weight:850;gap:8px;padding:0 14px;box-shadow:0 10px 18px rgba(183,123,26,.24);">
                        <i class="bi bi-send" aria-hidden="true"></i><span>Send Reply</span>
                    </button>
                </form>
            <?php endif; ?>
        </section>

        <aside class="af-support-side">
            <?php if ($selectedConversation !== null): ?>
                <section class="af-support-info">
                    <h2><i class="bi bi-info-circle" aria-hidden="true"></i> Conversation Details</h2>
                    <article><span><i class="bi bi-hash" aria-hidden="true"></i></span><div><strong>Reference</strong><p><?php echo htmlspecialchars(afrisense_support_conversation_label($selectedConversation), ENT_QUOTES, 'UTF-8'); ?></p></div></article>
                    <article><span><i class="bi bi-envelope" aria-hidden="true"></i></span><div><strong>Email</strong><p><?php echo htmlspecialchars((string) ($selectedConversation['guest_email'] ?? 'Not provided'), ENT_QUOTES, 'UTF-8'); ?></p></div></article>
                    <article><span><i class="bi bi-telephone" aria-hidden="true"></i></span><div><strong>Phone</strong><p><?php echo htmlspecialchars((string) ($selectedConversation['guest_phone'] ?? 'Not provided'), ENT_QUOTES, 'UTF-8'); ?></p></div></article>
                    <article><span><i class="bi bi-person-headset" aria-hidden="true"></i></span><div><strong>Agent</strong><p><?php echo htmlspecialchars((string) ($selectedConversation['agent_name'] ?? $adminName), ENT_QUOTES, 'UTF-8'); ?></p></div></article>
                </section>

                <section class="af-help-topics">
                    <h2><i class="bi bi-check2-circle" aria-hidden="true"></i> Update Status</h2>
                    <?php foreach (['Open', 'Waiting', 'Resolved', 'Closed'] as $status): ?>
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
