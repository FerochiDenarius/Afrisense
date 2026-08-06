<?php

declare(strict_types=1);

// Guard this block so it only runs when the required condition is met.
if (!function_exists('afrisense_load_env')) {
    /**
     * Load key/value pairs from the project .env file into $_ENV.
     */
    function afrisense_load_env(?string $path = null): void
    {
        $path ??= dirname(__DIR__, 2) . '/.env';

        // Guard this block so it only runs when the required condition is met.
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Guard this block so it only runs when the required condition is met.
        if ($lines === false) {
            return;
        }

        // Iterate through the data needed for this block.
        foreach ($lines as $line) {
            $line = trim($line);

            // Guard this block so it only runs when the required condition is met.
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Guard this block so it only runs when the required condition is met.
            if ($key === '') {
                continue;
            }

            // Guard this block so it only runs when the required condition is met.
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}

afrisense_load_env();
