<?php

declare(strict_types=1);

require_once __DIR__ . '/public_settings.php';

// Defines the afrisense_theme_color helper used by this module.
function afrisense_theme_color(string $value, string $fallback): string
{
    $value = trim($value);

    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $fallback;
}

// Defines the afrisense_theme_style_block helper used by this module.
function afrisense_theme_style_block(): string
{
    $settings = afrisense_public_settings();
    $website = $settings['website'] ?? [];
    $primaryGold = afrisense_theme_color((string) ($website['primary_color'] ?? ''), '#b77b1a');
    $secondaryGold = afrisense_theme_color((string) ($website['secondary_color'] ?? ''), '#cc8f25');
    $forestGreen = afrisense_theme_color((string) ($website['forest_green'] ?? ''), '#0d241e');
    $forestGreen2 = afrisense_theme_color((string) ($website['forest_green_2'] ?? ''), '#0c231d');

    return sprintf(
        '<style id="afrisense-dynamic-theme">
            :root {
                --af-gold: %1$s;
                --af-gold-2: %2$s;
                --primary-gold: %1$s;
                --secondary-gold: %2$s;
                --gold: %1$s;
                --gold-2: %2$s;
                --af-order-gold: %1$s;
                --af-order-gold-2: %2$s;
                --af-green: %3$s;
                --af-green-2: %4$s;
                --af-forest-green: %3$s;
                --af-forest-green-2: %4$s;
                --primary-green: %3$s;
                --secondary-green: %4$s;
                --green: %3$s;
                --green-2: %4$s;
                --af-order-green: %3$s;
                --af-order-green-2: %4$s;
            }
        </style>',
        htmlspecialchars($primaryGold, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($secondaryGold, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($forestGreen, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($forestGreen2, ENT_QUOTES, 'UTF-8')
    );
}

// Defines the afrisense_print_theme_style helper used by this module.
function afrisense_print_theme_style(): void
{
    echo afrisense_theme_style_block();
}
