<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/SchemaHelper.php';

/** Reads/writes st_site_settings with platform_settings fallback. */
final class SiteSettingsRepository
{
    private static ?array $cache = null;

    public static function ready(): bool
    {
        return SchemaHelper::hasTable('st_site_settings')
            || SchemaHelper::hasTable('platform_settings');
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $all = $this->all();

        return $all[$key] ?? $default;
    }

    /** @return array<string,string|null> */
    public function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $out = [];
        if (SchemaHelper::hasTable('st_site_settings')) {
            foreach (db()->query('SELECT setting_key, setting_value FROM st_site_settings')->fetchAll() as $r) {
                $out[$r['setting_key']] = $r['setting_value'];
            }
        }
        if (SchemaHelper::hasTable('platform_settings')) {
            foreach (db()->query('SELECT setting_key, setting_value FROM platform_settings')->fetchAll() as $r) {
                if (!array_key_exists($r['setting_key'], $out)) {
                    $out[$r['setting_key']] = $r['setting_value'];
                }
            }
        }
        self::$cache = $out;

        return $out;
    }

    public function set(string $key, ?string $value): void
    {
        if (SchemaHelper::hasTable('st_site_settings')) {
            $st = db()->prepare(
                'INSERT INTO st_site_settings (setting_key, setting_value) VALUES (?,?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            );
            $st->execute([$key, $value]);
        }
        if (SchemaHelper::hasTable('platform_settings') && str_starts_with($key, 'home_')) {
            $st = db()->prepare(
                'INSERT INTO platform_settings (setting_key, setting_value) VALUES (?,?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            );
            $st->execute([$key, $value]);
        }
        self::$cache = null;
        if (class_exists('PlatformRepository')) {
            $ref = new \ReflectionClass(PlatformRepository::class);
            if ($ref->hasProperty('cache')) {
                $prop = $ref->getProperty('cache');
                $prop->setAccessible(true);
                $prop->setValue(null, null);
            }
        }
    }
}
