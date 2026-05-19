<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/HomeBannerSettings.php';

/** @deprecated Use HomeBannerSettings */
final class HomeCaBanner
{
    public const MAX_MANUAL_POOL = HomeBannerSettings::MAX_MANUAL_POOL;

    /** @return array<string,mixed> */
    public static function settings(): array
    {
        return HomeBannerSettings::all()['ca'];
    }

    /** @param array<string,?string> $data */
    public static function save(array $data): void
    {
        HomeBannerSettings::saveCa($data);
    }
}
