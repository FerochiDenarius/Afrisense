<?php

declare(strict_types=1);

require_once __DIR__ . '/auth_bootstrap.php';

afrisense_auth()->logout();
header('Location: /Afrisense/frontend/auth/login.php');
exit;
