<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/CurrentAffairsRepository.php';
require_once dirname(__DIR__) . '/models/SiteSettingsRepository.php';

/** Public gold-card “live sync” pulse after admin publishes a ready CA pool. */
final class CaPublicSiteSync
{
    public const SETTING_KEY = 'ca_public_sync_at';

    /** Blink window after admin save (seconds). */
    public const PULSE_TTL_SEC = 6 * 3600;

    public static function touch(): void
    {
        if (!SiteSettingsRepository::ready()) {
            return;
        }
        (new SiteSettingsRepository())->set(self::SETTING_KEY, (string) time());
    }

    /** True when today’s CA pool is exam-ready (25+ rows) — same condition as admin “Ready”. */
    public static function shouldBlinkGoldBadge(): bool
    {
        if (!CurrentAffairsRepository::ready()) {
            return false;
        }
        $repo = new CurrentAffairsRepository();

        return $repo->dateHasExam($repo->todayDate());
    }
}
