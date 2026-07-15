<?php

declare(strict_types=1);

require_once __DIR__ . '/auth_bootstrap.php';

$result = afrisense_verify_email_token((string) ($_GET['token'] ?? ''));
afrisense_flash_set($result['success'] ? 'success' : 'error', $result['message']);

header('Location: /Afrisense/frontend/auth/login.php');
exit;
