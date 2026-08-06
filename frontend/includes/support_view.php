<?php

declare(strict_types=1);

$supportMessages = $supportMessages ?? [];
$supportConversation = $supportConversation ?? [];
$supportFlash = $supportFlash ?? null;
$supportIsGuest = (bool) ($supportIsGuest ?? false);
$supportAction = (string) ($supportAction ?? '');
$supportAgent = $supportAgent ?? null;
$supportSettings = afrisense_public_settings();
$supportCompany = $supportSettings['company'];
$supportEmail = (string) ($supportCompany['support_email'] ?? 'support@afrisense.com');
$supportPhone = (string) ($supportCompany['phone_number_1'] ?? '+233 24 123 4567');
$supportAgentName = trim((string) ($supportConversation['agent_name'] ?? $supportAgent['fullname'] ?? 'AfriSense Support'));
$supportAgentName = $supportAgentName !== '' ? $supportAgentName : 'AfriSense Support';
$supportLabel = $supportConversation !== [] ? afrisense_support_conversation_label($supportConversation) : '#SUP0001';
$supportSubject = (string) ($supportConversation['subject'] ?? ($_POST['subject'] ?? 'General Support'));
$supportLastTime = afrisense_support_time((string) ($supportConversation['last_message_at'] ?? ''));
$supportName = (string) ($supportPrefill['name'] ?? ($_POST['guest_name'] ?? ''));
$supportGuestEmail = (string) ($supportPrefill['email'] ?? ($_POST['guest_email'] ?? ''));
$supportGuestPhone = (string) ($supportPrefill['phone'] ?? ($_POST['guest_phone'] ?? ''));
$supportTopics = [
    'How do I track my order?',
    'What are your delivery areas?',
    'How do I cancel my order?',
    'Refund and return policy',
    'Payment methods',
];
?>
<!-- Page section for this part of the AfriSense interface. -->
<section
    class="af-support-page <?php echo $supportIsGuest ? 'is-guest' : 'is-customer'; ?>"
    data-support-page
    data-support-context="<?php echo $supportIsGuest ? 'guest' : 'customer'; ?>"
    data-support-live-url="<?php echo htmlspecialchars(($frontendBase ?? '/Afrisense/frontend') . '/support/live.php', ENT_QUOTES, 'UTF-8'); ?>"
    data-support-sent-sound-url="<?php echo htmlspecialchars(($frontendBase ?? '/Afrisense/frontend') . '/assets/audio/sentSound.wav', ENT_QUOTES, 'UTF-8'); ?>"
    data-support-received-sound-url="<?php echo htmlspecialchars(($frontendBase ?? '/Afrisense/frontend') . '/assets/audio/receivedSound.wav', ENT_QUOTES, 'UTF-8'); ?>"
    data-support-conversation-id="<?php echo (int) ($supportConversation['id'] ?? 0); ?>"
>
    <!-- Header block for this interface section. -->
    <header class="af-support-heading">
        <div>
            <span class="af-support-title-icon"><i class="bi bi-headset" aria-hidden="true"></i></span>
            <div>
                <h1>Support Agent</h1>
                <p>Chat with our live support team. We're here to help.</p>
            </div>
        </div>
        <span class="af-agent-online"><i class="bi bi-circle-fill" aria-hidden="true"></i> Agents Online</span>
    </header>

    <?php // Render this conditional/dynamic template block. ?>
    <?php if ($supportFlash !== null): ?>
        <p class="af-support-flash <?php echo ($supportFlash['success'] ?? false) ? 'is-success' : 'is-error'; ?>">
            <?php echo htmlspecialchars((string) ($supportFlash['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <div class="af-support-grid">
        <!-- Side panel with supporting information and actions. -->
        <aside class="af-support-conversations" aria-label="Support conversations">
            <!-- Header block for this interface section. -->
            <header>
                <h2>Conversations</h2>
            </header>

            <article class="af-support-thread is-active">
                <span class="af-thread-icon"><i class="bi bi-headset" aria-hidden="true"></i></span>
                <div>
                    <strong>AfriSense Support</strong>
                    <small>Live Agent</small>
                    <p><?php echo htmlspecialchars((string) ($supportConversation['last_message'] ?? 'Hello! How can I assist you?'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <time><?php echo htmlspecialchars($supportLastTime, ENT_QUOTES, 'UTF-8'); ?></time>
            </article>

            <article class="af-support-thread">
                <span class="af-thread-icon is-blue"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                <div>
                    <strong>Order Help</strong>
                    <small>Delivery issue</small>
                    <p>Track, cancel, or ask about a recent order.</p>
                </div>
                <time>Today</time>
            </article>

            <article class="af-support-thread">
                <span class="af-thread-icon is-gold"><i class="bi bi-cash-coin" aria-hidden="true"></i></span>
                <div>
                    <strong>Refund Request</strong>
                    <small>Refund and payment</small>
                    <p>Get help with a failed or delayed refund.</p>
                </div>
                <time>Help</time>
            </article>

            <a class="af-start-chat-btn" href="#support_message">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                Start New Chat
            </a>
        </aside>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-support-chat" aria-labelledby="support_chat_title">
            <!-- Header block for this interface section. -->
            <header class="af-chat-header">
                <span class="af-agent-avatar"><i class="bi bi-person-headset" aria-hidden="true"></i></span>
                <div>
                    <h2 id="support_chat_title"><?php echo htmlspecialchars($supportAgentName, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p>Live Agent</p>
                </div>
                <button type="button" aria-label="More options"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>
            </header>

            <div class="af-chat-body" aria-live="polite">
                <?php echo afrisense_support_messages_html($supportConversation, $supportMessages, ['customer', 'guest']); ?>

                <article class="af-chat-message is-agent is-typing" data-support-typing>
                    <span class="af-message-avatar"><i class="bi bi-person-headset" aria-hidden="true"></i></span>
                    <div>
                        <p>Agent is typing... <i></i><i></i><i></i></p>
                    </div>
                </article>
            </div>

            <!-- Form block that submits this page workflow. -->
            <form class="af-support-composer" action="<?php echo htmlspecialchars($supportAction, ENT_QUOTES, 'UTF-8'); ?>" method="post" enctype="multipart/form-data">
                <?php // Render this conditional/dynamic template block. ?>
                <?php if ($supportIsGuest): ?>
                    <div class="af-guest-fields">
                        <label>
                            <span>Name</span>
                            <input type="text" name="guest_name" value="<?php echo htmlspecialchars($supportName, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Your name" required>
                        </label>
                        <label>
                            <span>Email</span>
                            <input type="email" name="guest_email" value="<?php echo htmlspecialchars($supportGuestEmail, ENT_QUOTES, 'UTF-8'); ?>" placeholder="you@example.com" required>
                        </label>
                        <label>
                            <span>Phone</span>
                            <input type="tel" name="guest_phone" value="<?php echo htmlspecialchars($supportGuestPhone, ENT_QUOTES, 'UTF-8'); ?>" placeholder="+233..." required>
                        </label>
                    </div>
                <?php endif; ?>
                <input type="hidden" name="subject" value="<?php echo htmlspecialchars($supportSubject, ENT_QUOTES, 'UTF-8'); ?>">
                <label class="af-message-input" for="support_message">
                    <input id="support_message" name="message" type="text" placeholder="Type your message..." autocomplete="off">
                </label>
                <button class="af-composer-icon" type="button" title="Add emoji" aria-label="Add emoji" data-support-emoji-toggle><i class="bi bi-emoji-smile" aria-hidden="true"></i></button>
                <button class="af-composer-icon" type="button" title="Attach file" aria-label="Attach file" data-support-attach-toggle><i class="bi bi-paperclip" aria-hidden="true"></i></button>
                <input class="af-support-file-input" type="file" name="attachment" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.webm,.mov,.m4v,.pdf,.doc,.docx,.txt,image/*,video/*" data-support-file-input>
                <span class="af-attachment-name" data-support-attachment-name></span>
                <button class="af-send-chat" type="submit" title="Send message" aria-label="Send message"><i class="bi bi-send" aria-hidden="true"></i><span>Send</span></button>
            </form>
        </section>

        <!-- Side panel with supporting information and actions. -->
        <aside class="af-support-side">
            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-support-info">
                <h2><i class="bi bi-headset" aria-hidden="true"></i> Support Information</h2>
                <p>We're here to help you with any issues or questions you may have.</p>
                <article><span><i class="bi bi-chat-dots" aria-hidden="true"></i></span><div><strong>Live Chat</strong><p>9:00 AM - 9:00 PM (Daily)</p></div></article>
                <article><span><i class="bi bi-envelope" aria-hidden="true"></i></span><div><strong>Email Support</strong><p><?php echo htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8'); ?></p></div></article>
                <article><span><i class="bi bi-telephone" aria-hidden="true"></i></span><div><strong>Phone Support</strong><p><?php echo htmlspecialchars($supportPhone, ENT_QUOTES, 'UTF-8'); ?></p></div></article>
                <article><span><i class="bi bi-clock" aria-hidden="true"></i></span><div><strong>Response Time</strong><p>Typically replies in a few minutes</p></div></article>
            </section>

            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-help-topics">
                <h2><i class="bi bi-question-circle" aria-hidden="true"></i> Quick Help Topics</h2>
                <?php // Render this conditional/dynamic template block. ?>
                <?php foreach ($supportTopics as $topic): ?>
                    <button type="button" data-support-topic="<?php echo htmlspecialchars($topic, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        <?php echo htmlspecialchars($topic, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                <?php endforeach; ?>
                <a href="<?php echo htmlspecialchars($frontendBase . '/landing/contact.php', ENT_QUOTES, 'UTF-8'); ?>">View Help Center</a>
            </section>

            <!-- Page section for this part of the AfriSense interface. -->
            <section class="af-rate-support" aria-labelledby="rate_support_title">
                <h2 id="rate_support_title"><i class="bi bi-stars" aria-hidden="true"></i> Rate Our Support</h2>
                <p>How was your support experience today?</p>
                <div class="af-stars" data-support-rating>
                    <?php // Render this conditional/dynamic template block. ?>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" aria-label="Rate <?php echo $i; ?> stars"><i class="bi bi-star" aria-hidden="true"></i></button>
                    <?php endfor; ?>
                </div>
            </section>
        </aside>
    </div>
</section>
