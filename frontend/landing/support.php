<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Support Agent | AfriSense';
$activePage = 'support';
$supportCssVersion = (string) (filemtime(__DIR__ . '/../assets/css/support.css') ?: time());
$supportJsVersion = (string) (filemtime(__DIR__ . '/../assets/js/support.js') ?: time());
$extraStyles = [$frontendBase . '/assets/css/support.css?v=' . $supportCssVersion];
$extraScripts = [$frontendBase . '/assets/js/support.js?v=' . $supportJsVersion];

require_once __DIR__ . '/../includes/support_helpers.php';

afrisense_enforce_public_site_status($frontendBase);
\AfriSense\Backend\Helpers\Session::start();

$supportFlash = null;
$supportConversation = [];
$supportMessages = [];
$supportAgent = null;
$supportToken = afrisense_support_guest_token();
$supportPrefill = [
    'name' => '',
    'email' => '',
    'phone' => '',
];

// Run database/action work inside a guarded block so the page can fail gracefully.
try {
    $pdo = afrisense_pdo();
    afrisense_support_tables($pdo);
    $supportAgent = afrisense_support_default_agent($pdo);
    $supportConversation = afrisense_support_find_conversation($pdo, null, $supportToken) ?? [];

    // Handle submitted form actions before rendering the page.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $name = trim((string) ($_POST['guest_name'] ?? ''));
        $email = trim((string) ($_POST['guest_email'] ?? ''));
        $phone = trim((string) ($_POST['guest_phone'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        $supportPrefill = ['name' => $name, 'email' => $email, 'phone' => $phone];

        $attachment = $_FILES['attachment'] ?? null;
        $hasAttachment = is_array($attachment) && (int) ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        // Guard this block so it only runs when the required condition is met.
        if ($name === '' || $email === '' || $phone === '' || ($message === '' && !$hasAttachment)) {
            $supportFlash = ['success' => false, 'message' => 'Please provide your contact details and message or attachment.'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $supportFlash = ['success' => false, 'message' => 'Please enter a valid email address.'];
        } else {
            // Guard this block so it only runs when the required condition is met.
            if ($supportConversation === []) {
                $supportConversation = afrisense_support_create_conversation($pdo, null, $supportToken, $_POST);
            }

            $messageBody = $message !== '' ? $message : 'Attachment sent.';
            $messageId = afrisense_support_add_message($pdo, (int) $supportConversation['id'], 'guest', null, $name, $messageBody);
            $attachmentResult = is_array($attachment) ? afrisense_support_upload_attachment($pdo, $messageId, $attachment) : ['success' => true];
            // Guard this block so it only runs when the required condition is met.
            if (!$attachmentResult['success']) {
                $supportFlash = ['success' => false, 'message' => (string) ($attachmentResult['message'] ?? 'Attachment could not be uploaded.')];
            } else {
                $supportFlash = ['success' => true, 'message' => 'Message sent. AfriSense Support has been notified.'];
            }
            $supportConversation = afrisense_support_get_conversation($pdo, (int) $supportConversation['id']) ?? $supportConversation;
            afrisense_support_notify_agent($pdo, $supportConversation, $name);
            $_POST['message'] = '';
        }
    }

    // Guard this block so it only runs when the required condition is met.
    if ($supportConversation !== []) {
        $supportMessages = afrisense_support_messages($pdo, (int) $supportConversation['id']);
        $supportPrefill = [
            'name' => (string) ($supportConversation['guest_name'] ?? ''),
            'email' => (string) ($supportConversation['guest_email'] ?? ''),
            'phone' => (string) ($supportConversation['guest_phone'] ?? ''),
        ];
    }
} catch (Throwable $exception) {
    $supportFlash = ['success' => false, 'message' => 'Support chat could not be loaded. Check that MySQL is running.'];
}

// Guard this block so it only runs when the required condition is met.
if ($supportMessages === []) {
    $supportMessages = [[
        'sender_type' => 'agent',
        'sender_name' => 'AfriSense Support',
        'body' => 'Hello! Thank you for contacting AfriSense Support. How can I assist you today?',
        'created_at' => date('Y-m-d H:i:s'),
    ]];
}

$supportIsGuest = true;
$supportAction = 'support.php';

ob_start();
require __DIR__ . '/../includes/support_view.php';
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
