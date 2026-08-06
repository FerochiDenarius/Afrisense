<?php

declare(strict_types=1);

require_once __DIR__ . '/auth_bootstrap.php';

// End the active frontend session and return the user to the login screen.
afrisense_auth()->logout();
header('Location: /Afrisense/frontend/auth/login.php');
exit;
