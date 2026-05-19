<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/CurrentAffairsRepository.php';
require_once dirname(__DIR__) . '/models/SubscriptionRepository.php';
require_once dirname(__DIR__) . '/models/SchemaHelper.php';

/** Student tier gates for Daily Current Affairs. */
final class CurrentAffairsAccess
{
    public function __construct(
        private CurrentAffairsRepository $repo = new CurrentAffairsRepository(),
        private SubscriptionRepository $subscriptions = new SubscriptionRepository(),
    ) {
    }

    public function isModuleReady(): bool
    {
        return CurrentAffairsRepository::ready();
    }

    /** Premium = active sub-course purchase / subscription / completed payment. */
    public function isPremiumUser(int $userId): bool
    {
        if ($userId < 1) {
            return false;
        }
        if ($this->subscriptions->userHasAnyActivePaidSubscription($userId)) {
            return true;
        }
        if (SchemaHelper::hasTable('st_sub_course_purchases')) {
            $st = db()->prepare(
                'SELECT 1 FROM st_sub_course_purchases
                 WHERE user_id = ? AND status IN (\'active\',\'approved\',\'completed\')
                   AND (expires_at IS NULL OR expires_at > NOW())
                 LIMIT 1'
            );
            $st->execute([$userId]);
            if ($st->fetch()) {
                return true;
            }
        }
        if (SchemaHelper::hasTable('payments')) {
            $st = db()->prepare(
                "SELECT 1 FROM payments WHERE user_id = ? AND status IN ('paid','success','completed') LIMIT 1"
            );
            $st->execute([$userId]);
            if ($st->fetch()) {
                return true;
            }
        }

        return false;
    }

    public function canAccessDate(int $userId, string $examDate): bool
    {
        $today = $this->repo->todayDate();
        if ($examDate > $today) {
            return false;
        }
        if ($this->isPremiumUser($userId)) {
            $cutoff = $this->repo->retentionCutoff();

            return $examDate >= $cutoff;
        }

        return $examDate === $today;
    }

    /** True when DB has 25+ questions or demo pool may be used. */
    public function dateIsPlayable(string $examDate): bool
    {
        if ($this->isModuleReady() && $this->repo->dateHasExam($examDate)) {
            return true;
        }

        return true;
    }

    public function canStartExam(int $userId, string $examDate): bool
    {
        if (!$this->canAccessDate($userId, $examDate)) {
            return false;
        }
        if ($this->isPremiumUser($userId)) {
            return true;
        }
        $row = $this->repo->attemptRow($userId, $examDate);

        return !$row || (int) ($row['attempt_count'] ?? 0) < 1;
    }

    public function tierLabel(int $userId): string
    {
        return $this->isPremiumUser($userId) ? 'premium' : 'free';
    }

    /**
     * @return array{tier:string,today:string,today_ready:bool,can_take_today:bool,months:list<array>,show_archive:bool}
     */
    public function hubContext(int $userId): array
    {
        $today = $this->repo->todayDate();
        $premium = $this->isPremiumUser($userId);
        $dbTodayReady = $this->isModuleReady() && $this->repo->dateHasExam($today);
        $todayReady = $dbTodayReady || $this->dateIsPlayable($today);
        $canStartToday = $this->canStartExam($userId, $today);
        $months = $this->isModuleReady()
            ? $this->repo->monthsWithExams($this->repo->retentionCutoff(), $today)
            : [];

        return [
            'tier' => $premium ? 'premium' : 'free',
            'premium' => $premium,
            'today' => $today,
            'today_ready' => $todayReady,
            'today_db_ready' => $dbTodayReady,
            'can_take_today' => $todayReady && $canStartToday,
            'today_attempted' => !$premium && !$canStartToday,
            'months' => $months,
            'show_archive' => true,
            'retention_cutoff' => $this->repo->retentionCutoff(),
        ];
    }
}
