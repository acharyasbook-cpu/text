<?php

declare(strict_types=1);

/**
 * Anti-piracy watermark label + secure view helpers for notes and exams.
 */
final class SecureContentGuard
{
    public static function watermarkLabel(?array $user): string
    {
        $phone = trim((string) ($user['phone'] ?? ''));
        if ($phone !== '') {
            return 'ఆచార్య బుక్స్ - ' . $phone;
        }

        return 'ఆచార్య బుక్స్';
    }

    public static function watermarkLabelEscaped(?array $user): string
    {
        return htmlspecialchars(self::watermarkLabel($user), ENT_QUOTES, 'UTF-8');
    }

    /** Inline SVG tile for repeating diagonal watermark (CSS background). */
    public static function watermarkPatternStyle(?array $user): string
    {
        $label = self::watermarkLabel($user);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="340" height="200" viewBox="0 0 340 200">'
            . '<text x="170" y="100" fill="#64748b" fill-opacity="0.04" font-family="Noto Sans Telugu, Inter, sans-serif"'
            . ' font-size="15" font-weight="600" text-anchor="middle" dominant-baseline="middle"'
            . ' transform="rotate(-30 170 100)">' . htmlspecialchars($label, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</text></svg>';

        return '--secure-watermark:url("data:image/svg+xml,' . rawurlencode($svg) . '");';
    }

    public static function registerAssets(): void
    {
        if (!defined('SECURE_CONTENT_ASSETS')) {
            define('SECURE_CONTENT_ASSETS', true);
        }
    }
}
