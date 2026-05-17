<?php

declare(strict_types=1);

/**
 * Optional image_path with text-avatar fallback (Noto Sans Telugu on public UI).
 */
final class MediaAvatarHelper
{
    /** @param array<string,mixed> $row */
    public static function displayLabel(array $row, string $nameKey = 'name', ?string $teKey = 'name_te'): string
    {
        if ($teKey !== null) {
            $te = trim((string) ($row[$teKey] ?? ''));
            if ($te !== '') {
                return $te;
            }
        }

        return trim((string) ($row[$nameKey] ?? ''));
    }

    public static function initials(string $label, int $maxChars = 2): string
    {
        $label = trim($label);
        if ($label === '') {
            return '—';
        }

        if (preg_match('/\p{L}/u', $label, $m)) {
            $first = $m[0];
            $rest = mb_substr($label, mb_strlen($first));
            if (preg_match('/\p{L}/u', $rest, $m2)) {
                return mb_strtoupper($first . $m2[0]);
            }

            return mb_strtoupper($first);
        }

        return mb_strtoupper(mb_substr($label, 0, min($maxChars, mb_strlen($label))));
    }

    /**
     * Public URL only when stored path exists on disk (avoids broken &lt;img&gt;).
     */
    public static function resolvedUrl(?string $storedPath): string
    {
        $storedPath = trim((string) $storedPath);
        if ($storedPath === '') {
            return '';
        }

        if (function_exists('public_media_url')) {
            $url = public_media_url($storedPath);
        } elseif (function_exists('acharya_media_url')) {
            $url = acharya_media_url($storedPath);
        } else {
            $url = $storedPath;
        }

        if (str_starts_with($storedPath, 'http://') || str_starts_with($storedPath, 'https://')) {
            return $url;
        }

        $root = defined('ACHARYA_ROOT') ? ACHARYA_ROOT : dirname(__DIR__);
        $abs = $root . '/' . ltrim(str_replace('\\', '/', $storedPath), '/');
        if (!is_file($abs)) {
            return '';
        }

        return $url;
    }

    public static function cacheVersion(?string $storedPath): int
    {
        if (function_exists('public_media_cache_version')) {
            return public_media_cache_version($storedPath);
        }

        return 0;
    }

    /** @return array{background:string,color:string} */
    public static function palette(string $seed): array
    {
        $palettes = [
            ['#eef2ff', '#1e3a8a'],
            ['#ecfdf5', '#047857'],
            ['#fff7ed', '#c2410c'],
            ['#fdf4ff', '#7e22ce'],
            ['#f0f9ff', '#0369a1'],
            ['#fef2f2', '#b91c1c'],
            ['#f8fafc', '#334155'],
            ['#faf8f5', '#92400e'],
        ];
        $hash = crc32(mb_strtolower(trim($seed)));

        return $palettes[abs($hash) % count($palettes)];
    }
}
