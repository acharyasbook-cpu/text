<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/SiteSettingsRepository.php';
require_once dirname(__DIR__) . '/includes/public_site_helpers.php';

/** Home page hero (blue) + gold CA block — st_site_settings. */
final class HomeBannerSettings
{
    public const MAX_MANUAL_POOL = 50;

    /** @return array<string,mixed> */
    public static function all(): array
    {
        if (!SiteSettingsRepository::ready()) {
            return self::defaults();
        }
        $s = new SiteSettingsRepository();

        return [
            'hero' => [
                'bg_color' => $s->get('home_hero_bg_color') ?: 'linear-gradient(to bottom right, #1e3a8a, #2f4fa8)',
                'bg_image_url' => self::mediaUrl($s->get('home_hero_bg_image')),
                'eyebrow' => self::normalizeHeroEyebrow($s->get('home_hero_eyebrow')),
                'line1' => $s->get('home_hero_line1') ?: 'మీ విజయం, మా లక్ష్యం. ఆచార్యతో సిద్ధమవ్వండి.',
                'line2' => self::normalizeHeroLine2($s->get('home_hero_line2')),
                'line1_size' => $s->get('home_hero_line1_size') ?: '1.875rem',
                'line2_size' => $s->get('home_hero_line2_size') ?: '0.875rem',
            ],
            'ca' => [
                'bg_color' => $s->get('home_ca_bg_color') ?: 'linear-gradient(145deg, #f5c842 0%, #e8a317 45%, #d4920a 100%)',
                'bg_image_url' => self::mediaUrl($s->get('home_ca_bg_image')),
                'line1' => $s->get('home_ca_line1') ?: 'డైలీ టెస్ట్',
                'line2' => $s->get('home_ca_line2') ?: 'డైలీ కరెంట్ అఫైర్స్',
                'line3' => $s->get('home_ca_line3') ?: 'నేటి పరీక్ష',
                'line1_size' => $s->get('home_ca_line1_size') ?: '0.65rem',
                'line2_size' => $s->get('home_ca_line2_size') ?: '1.35rem',
                'line3_size' => $s->get('home_ca_line3_size') ?: '0.8rem',
            ],
        ];
    }

    /** @param array<string,mixed> $payload */
    public static function saveHero(array $payload): void
    {
        $s = new SiteSettingsRepository();
        foreach ([
            'home_hero_bg_color' => $payload['bg_color'] ?? null,
            'home_hero_eyebrow' => $payload['eyebrow'] ?? null,
            'home_hero_line1' => $payload['line1'] ?? null,
            'home_hero_line2' => $payload['line2'] ?? null,
            'home_hero_line1_size' => $payload['line1_size'] ?? null,
            'home_hero_line2_size' => $payload['line2_size'] ?? null,
        ] as $k => $v) {
            if ($v !== null) {
                $s->set($k, trim((string) $v));
            }
        }
        if (array_key_exists('bg_image', $payload)) {
            $s->set('home_hero_bg_image', $payload['bg_image'] !== '' ? (string) $payload['bg_image'] : null);
        }
    }

    /** @param array<string,mixed> $payload */
    public static function saveCa(array $payload): void
    {
        $s = new SiteSettingsRepository();
        foreach ([
            'home_ca_bg_color' => $payload['bg_color'] ?? null,
            'home_ca_line1' => $payload['line1'] ?? null,
            'home_ca_line2' => $payload['line2'] ?? null,
            'home_ca_line3' => $payload['line3'] ?? null,
            'home_ca_line1_size' => $payload['line1_size'] ?? null,
            'home_ca_line2_size' => $payload['line2_size'] ?? null,
            'home_ca_line3_size' => $payload['line3_size'] ?? null,
        ] as $k => $v) {
            if ($v !== null) {
                $s->set($k, trim((string) $v));
            }
        }
        if (array_key_exists('bg_image', $payload)) {
            $s->set('home_ca_bg_image', $payload['bg_image'] !== '' ? (string) $payload['bg_image'] : null);
        }
    }

    private static function mediaUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        return $path !== '' ? public_media_url($path) : null;
    }

    private static function normalizeHeroEyebrow(?string $v): string
    {
        $v = trim((string) $v);
        if ($v === '' || stripos($v, 'ACHARYA BOOK') !== false) {
            return 'ACHARYASBOOK.COM';
        }

        return $v;
    }

    private static function normalizeHeroLine2(?string $v): string
    {
        $v = trim((string) $v);
        if ($v === '' || str_contains($v, 'అడ్మిన్ ప్యానెల్')) {
            return 'ప్రతిష్టాత్మక ఆన్‌లైన్ లెర్నింగ్ — పరీక్షల విజయానికి సరైన మార్గదర్శకత్వం.';
        }

        return $v;
    }

    /** @return array<string,mixed> */
    private static function defaults(): array
    {
        return [
            'hero' => [
                'bg_color' => 'linear-gradient(to bottom right, #1e3a8a, #2f4fa8)',
                'bg_image_url' => null,
                'eyebrow' => 'ACHARYASBOOK.COM',
                'line1' => 'మీ విజయం, మా లక్ష్యం. ఆచార్యతో సిద్ధమవ్వండి.',
                'line2' => 'ప్రతిష్టాత్మక ఆన్‌లైన్ లెర్నింగ్ — పరీక్షల విజయానికి సరైన మార్గదర్శకత్వం.',
                'line1_size' => '1.875rem',
                'line2_size' => '0.875rem',
            ],
            'ca' => [
                'bg_color' => 'linear-gradient(145deg, #f5c842 0%, #e8a317 45%, #d4920a 100%)',
                'bg_image_url' => null,
                'line1' => 'డైలీ టెస్ట్',
                'line2' => 'డైలీ కరెంట్ అఫైర్స్',
                'line3' => 'నేటి పరీక్ష',
                'line1_size' => '0.65rem',
                'line2_size' => '1.35rem',
                'line3_size' => '0.8rem',
            ],
        ];
    }
}
