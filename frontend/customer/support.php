<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Support Agent | AfriSense';
$customerTitle = 'Support Agent';
$activeCustomerPage = 'support';
$supportCssVersion = (string) (filemtime(__DIR__ . '/../assets/css/support.css') ?: time());
$supportJsVersion = (string) (filemtime(__DIR__ . '/../assets/js/support.js') ?: time());
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
    $frontendBase . '/assets/css/support.css?v=' . $supportCssVersion,
];
$extraScripts = [$frontendBase . '/assets/js/support.js?v=' . $supportJsVersion];

require_once __DIR__ . '/../includes/support_helpers.php';

$authUser = afrisense_require_customer();
$supportFlash = null;
$supportConversation = [];
$supportMessages = [];
$supportAgent = null;
$supportToken = 'user-' . (int) ($authUser['id'] ?? 0);
$supportPrefill = [
    'name' => (string) ($authUser['fullname'] ?? 'Customer'),
    'email' => (string) ($authUser['email'] ?? ''),
    'phone' => (string) ($authUser['phonenumber'] ?? $authUser['phone'] ?? ''),
];

// Run database/action work inside a guarded block so the page can fail gracefully.
try {
    $pdo = afrisense_pdo();
    afrisense_support_tables($pdo);
    $supportAgent = afrisense_support_default_agent($pdo);
    $supportConversation = afrisense_support_find_conversation($pdo, $authUser, $supportToken) ?? [];

    // Handle submitted form actions before rendering the page.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $message = trim((string) ($_POST['message'] ?? ''));
        $attachment = $_FILES['attachment'] ?? null;
        $hasAttachment = is_array($attachment) && (int) ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        // Guard this block so it only runs when the required condition is met.
        if ($message === '' && !$hasAttachment) {
            $supportFlash = ['success' => false, 'message' => 'Please type a message or choose an attachment before sending.'];
        } else {
            // Guard this block so it only runs when the required condition is met.
            if ($supportConversation === []) {
                $supportConversation = afrisense_support_create_conversation($pdo, $authUser, $supportToken, $_POST);
            }

            $customerName = trim((string) ($authUser['fullname'] ?? 'Customer'));
            $messageBody = $message !== '' ? $message : 'Attachment sent.';
            $messageId = afrisense_support_add_message($pdo, (int) $supportConversation['id'], 'customer', (int) $authUser['id'], $customerName, $messageBody);
            $attachmentResult = is_array($attachment) ? afrisense_support_upload_attachment($pdo, $messageId, $attachment) : ['success' => true];
            $supportConversation = afrisense_support_get_conversation($pdo, (int) $supportConversation['id']) ?? $supportConversation;
            afrisense_support_notify_agent($pdo, $supportConversation, $customerName !== '' ? $customerName : 'Customer');
            $supportFlash = ($attachmentResult['success'] ?? false)
                ? ['success' => true, 'message' => 'Message sent. AfriSense Support has been notified.']
                : ['success' => false, 'message' => (string) ($attachmentResult['message'] ?? 'Attachment could not be uploaded.')];
            $_POST['message'] = '';
        }
    }

    // Guard this block so it only runs when the required condition is met.
    if ($supportConversation !== []) {
        $supportMessages = afrisense_support_messages($pdo, (int) $supportConversation['id']);
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

$supportIsGuest = false;
$supportAction = 'support.php';

ob_start();
require __DIR__ . '/../includes/support_view.php';
$content = ob_get_clean();
require __DIR__ . '/../layouts/customer_layout.php';
