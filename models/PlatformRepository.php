<?php

declare(strict_types=1);

class PlatformRepository
{
    private static ?array $cache = null;

    public static function enabled(): bool
    {
        return SchemaHelper::hasTable('platform_settings');
    }

    /** @return array<string,string|null> */
    public function all(): array
    {
        if (!self::enabled()) {
            return $this->defaults();
        }
        if (self::$cache !== null) {
            return self::$cache;
        }
        $rows = db()->query('SELECT setting_key, setting_value FROM platform_settings')->fetchAll();
        $out = $this->defaults();
        foreach ($rows as $r) {
            $out[$r['setting_key']] = $r['setting_value'];
        }
        self::$cache = $out;

        return $out;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $all = $this->all();

        return $all[$key] ?? $default;
    }

    public function set(string $key, ?string $value): void
    {
        if (!self::enabled()) {
            throw new RuntimeException('platform_settings table missing. Run update_frontend_user_and_media_core.php');
        }
        $st = db()->prepare(
            'INSERT INTO platform_settings (setting_key, setting_value) VALUES (?,?)
             ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)'
        );
        $st->execute([$key, $value]);
        self::$cache = null;
    }

    public function logoPath(): ?string
    {
        $p = trim((string) ($this->get('site_logo_path') ?? ''));

        return $p !== '' ? $p : null;
    }

    public function siteName(): string
    {
        return (string) ($this->get('site_name') ?? 'Acharya Books');
    }

    public function siteNameTe(): string
    {
        return (string) ($this->get('site_name_te') ?? 'ఆచార్య బుక్');
    }

    public function siteTaglineTe(): string
    {
        return (string) ($this->get('site_tagline_te') ?? 'మోడర్న్ గురుకుల్');
    }

    /** @return array<string,string|null> */
    private function defaults(): array
    {
        return [
            'site_logo_path' => null,
            'site_name' => 'Acharya Books',
            'site_name_te' => 'ఆచార్య బుక్',
            'site_tagline_te' => 'మోడర్న్ గురుకుల్',
        ];
    }
}
