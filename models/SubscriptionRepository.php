<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/SchemaHelper.php';
require_once dirname(__DIR__) . '/models/CouponRepository.php';

class SubscriptionRepository
{
    public function activePackagesForUser(int $userId): array
    {
        if (SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id')) {
            $sql = 'SELECT us.*, p.slug, p.name, p.name_te, p.package_type, p.price_inr, p.includes_division_tests,
                           c.slug AS course_slug, s.slug AS subject_slug,
                           sp.label AS plan_label, sp.plan_code, scp.name AS plan_sub_course_name,
                           scp.slug AS sub_course_slug
                    FROM user_subscriptions us
                    LEFT JOIN sub_course_packages p ON p.id = us.package_id
                    LEFT JOIN sub_course_plans sp ON sp.id = us.sub_course_plan_id
                    LEFT JOIN sub_courses scp ON scp.id = sp.sub_course_id
                    LEFT JOIN courses c ON c.id = COALESCE(p.course_id, scp.course_id)
                    LEFT JOIN subjects s ON s.id = p.subject_id
                    WHERE us.user_id = ? AND us.status = "active"
                      AND (us.expires_at IS NULL OR us.expires_at > NOW())
                    ORDER BY us.purchased_at DESC';
            $stmt = db()->prepare($sql);
            $stmt->execute([$userId]);

            return $stmt->fetchAll();
        }

        $sql = 'SELECT us.*, p.slug, p.name, p.name_te, p.package_type, p.price_inr, p.includes_division_tests,
                       c.slug AS course_slug, s.slug AS subject_slug
                FROM user_subscriptions us
                JOIN sub_course_packages p ON p.id = us.package_id
                LEFT JOIN courses c ON c.id = p.course_id
                LEFT JOIN subjects s ON s.id = p.subject_id
                WHERE us.user_id = ? AND us.status = "active"
                  AND (us.expires_at IS NULL OR us.expires_at > NOW())
                ORDER BY us.purchased_at DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function userHasSubjectAccess(int $userId, int $subjectId): bool
    {
        if (SchemaHelper::hasTable('sub_course_plans') && SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id')) {
            $spLive = SchemaHelper::columnExists('sub_course_plans', 'status')
                ? 'sp.status = 1 AND sp.is_active = 1' : 'sp.is_active = 1';
            $pcs = SchemaHelper::columnExists('sub_course_subjects', 'status')
                ? 'scs.status = 1 AND scs.is_active = 1' : 'scs.is_active = 1';
            $sql = "SELECT 1 FROM user_subscriptions us
                JOIN sub_course_plans sp ON sp.id = us.sub_course_plan_id AND {$spLive}
                JOIN sub_course_subjects scs ON scs.sub_course_id = sp.sub_course_id AND scs.subject_id = ? AND {$pcs}
                WHERE us.user_id = ? AND us.status = \"active\"
                  AND (us.expires_at IS NULL OR us.expires_at > NOW())
                LIMIT 1";
            $stmt = db()->prepare($sql);
            $stmt->execute([$subjectId, $userId]);
            if ($stmt->fetch()) {
                return true;
            }
        }

        if (!SchemaHelper::columnExists('subjects', 'course_id')) {
            return false;
        }
        $sql = 'SELECT 1 FROM user_subscriptions us
                JOIN sub_course_packages p ON p.id = us.package_id
                WHERE us.user_id = ? AND us.status = "active"
                  AND (us.expires_at IS NULL OR us.expires_at > NOW())
                  AND (p.subject_id = ? OR (p.package_type = "course_bundle" AND p.course_id = (SELECT course_id FROM subjects WHERE id = ? LIMIT 1)))
                LIMIT 1';
        $stmt = db()->prepare($sql);
        $stmt->execute([$userId, $subjectId, $subjectId]);

        return (bool) $stmt->fetch();
    }

    public function userHasTestAccess(int $userId, int $testId): bool
    {
        $test = db()->prepare('SELECT * FROM tests WHERE id = ?');
        $test->execute([$testId]);
        $t = $test->fetch();
        if (!$t) {
            return false;
        }
        if (empty($t['package_id'])) {
            return true;
        }
        $sql = 'SELECT 1 FROM user_subscriptions us
                WHERE us.user_id = ? AND us.package_id = ? AND us.status = "active"
                  AND (us.expires_at IS NULL OR us.expires_at > NOW()) LIMIT 1';
        $stmt = db()->prepare($sql);
        $stmt->execute([$userId, $t['package_id']]);
        return (bool) $stmt->fetch();
    }

    /**
     * Package / subscription OR 2-day demo window for schedule-linked exams.
     */
    public function userMayAccessScheduledTest(int $userId, int $testId, ?int $scheduleRowId): bool
    {
        if ($userId < 1) {
            return false;
        }
        if ($this->userHasTestAccess($userId, $testId)) {
            return true;
        }
        if ($scheduleRowId === null || $scheduleRowId < 1 || !SchemaHelper::scheduleTestManagerEnabled()) {
            return false;
        }
        require_once dirname(__DIR__) . '/models/ScheduleTestRepository.php';
        require_once dirname(__DIR__) . '/models/SubCourseDemoAccess.php';
        $gate = (new ScheduleTestRepository())->rowScheduleGate($scheduleRowId);
        if (!$gate || (int) $gate['sub_course_id'] < 1) {
            return false;
        }
        $scId = (int) $gate['sub_course_id'];
        $dayIx = (int) ($gate['day_index'] ?? 0);
        if ($dayIx < 1) {
            return false;
        }
        // First two sequential schedule days (day_index 1–2): free for every logged-in user.
        if ($dayIx <= SubCourseDemoAccess::FREE_SCHEDULE_DAY_INDEX) {
            return true;
        }

        return $this->userHasActivePlanForSubCourse($userId, $scId);
    }

    public function packagesForCourse(int $courseId): array
    {
        $extra = SchemaHelper::columnExists('sub_course_packages', 'status') ? ' AND status = 1' : '';
        $stmt = db()->prepare('SELECT * FROM sub_course_packages WHERE course_id = ? AND is_active = 1' . $extra . ' ORDER BY price_inr');
        $stmt->execute([$courseId]);

        return $stmt->fetchAll();
    }

    public function findPlanById(int $planId): ?array
    {
        if ($planId < 1 || !SchemaHelper::hasTable('sub_course_plans')) {
            return null;
        }
        $st = db()->prepare(
            'SELECT sp.*, sc.id AS sub_course_id, sc.slug AS sub_course_slug, sc.name AS sub_course_name,
                    c.id AS course_id, c.slug AS course_slug, c.name AS course_name
             FROM sub_course_plans sp
             JOIN sub_courses sc ON sc.id = sp.sub_course_id
             JOIN courses c ON c.id = sc.course_id
             WHERE sp.id = ? LIMIT 1'
        );
        $st->execute([$planId]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public function enrollmentAnchorForSubCourse(int $userId, int $subCourseId): ?string
    {
        if ($userId < 1 || $subCourseId < 1 || !SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id')) {
            return null;
        }
        $st = db()->prepare(
            'SELECT us.purchased_at FROM user_subscriptions us
             INNER JOIN sub_course_plans sp ON sp.id = us.sub_course_plan_id
             WHERE us.user_id = ? AND sp.sub_course_id = ? AND us.status = "active"
               AND (us.expires_at IS NULL OR us.expires_at > NOW())
             ORDER BY us.purchased_at ASC LIMIT 1'
        );
        $st->execute([$userId, $subCourseId]);
        $val = $st->fetchColumn();

        return is_string($val) && $val !== '' ? $val : null;
    }

    /** Any active paid plan or legacy package subscription. */
    public function userHasAnyActivePaidSubscription(int $userId): bool
    {
        if ($userId < 1) {
            return false;
        }
        $packages = $this->activePackagesForUser($userId);

        return $packages !== [];
    }

    public function userHasActivePlanForSubCourse(int $userId, int $subCourseId): bool
    {
        if ($userId < 1 || $subCourseId < 1) {
            return false;
        }
        if (!SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id')) {
            return false;
        }
        $spLive = SchemaHelper::columnExists('sub_course_plans', 'status')
            ? 'sp.status = 1 AND sp.is_active = 1' : 'sp.is_active = 1';
        $sql = "SELECT 1 FROM user_subscriptions us
                JOIN sub_course_plans sp ON sp.id = us.sub_course_plan_id AND {$spLive}
                WHERE us.user_id = ? AND sp.sub_course_id = ? AND us.status = 'active'
                  AND (us.expires_at IS NULL OR us.expires_at > NOW())
                LIMIT 1";
        $st = db()->prepare($sql);
        $st->execute([$userId, $subCourseId]);

        return (bool) $st->fetch();
    }

    /** Complete checkout: payment row + active subscription for sub-course plan.
     *
     * @return array{payment_id:int,transaction_ref:string,plan:array,expires_at:?string,amount_inr:float}
     */
    public function purchaseSubCoursePlan(int $userId, int $planId, string $paymentMethod = 'gateway', ?string $couponCode = null): array
    {
        $plan = $this->findPlanById($planId);
        if (!$plan) {
            throw new InvalidArgumentException('Subscription plan not found.');
        }

        $price = (float) ($plan['price_inr'] ?? 0);
        if ($price < 0) {
            throw new InvalidArgumentException('Invalid plan price.');
        }

        $couponId = null;
        $discountInr = 0.0;
        if ($couponCode !== null && trim($couponCode) !== '' && CouponRepository::tableReady()) {
            $repo = new CouponRepository();
            $v = $repo->validateForPlan($planId, $couponCode);
            if (!$v['ok']) {
                throw new InvalidArgumentException($v['error_te'] ?? 'Invalid coupon.');
            }
            $price = (float) ($v['final_inr'] ?? $price);
            $discountInr = (float) ($v['discount_inr'] ?? 0);
            $couponId = (int) ($v['coupon_id'] ?? 0);
        }

        $expiresAt = null;
        $months = $plan['duration_months'] ?? null;
        if ($months !== null && (int) $months > 0) {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int) $months . ' months'));
        }

        $this->ensurePaymentsPlanColumn();

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $txnRef = 'AB-' . strtoupper(bin2hex(random_bytes(4))) . '-' . time();
            $note = 'Sub-course plan enrolment via checkout';
            if ($couponId !== null && $couponId > 0) {
                $note .= ' | coupon_id=' . $couponId;
            }
            $paymentId = $this->insertPayment($userId, $planId, $price, $paymentMethod, $txnRef, $note);

            if ($couponId !== null && $couponId > 0) {
                $subCourseId = (int) ($plan['sub_course_id'] ?? 0);
                if (CouponRepository::usageLogsReady() && $subCourseId > 0) {
                    (new CouponRepository())->logRedemption($couponId, $userId, $subCourseId, $discountInr, $price);
                }
                (new CouponRepository())->incrementUsage($couponId);
            }

            if (SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id')) {
                $subCourseId = (int) ($plan['sub_course_id'] ?? 0);
                $pdo->prepare(
                    'UPDATE user_subscriptions us
                     INNER JOIN sub_course_plans sp ON sp.id = us.sub_course_plan_id
                     SET us.status = "cancelled"
                     WHERE us.user_id = ? AND sp.sub_course_id = ? AND us.status = "active"'
                )->execute([$userId, $subCourseId]);
                $pdo->prepare(
                    'INSERT INTO user_subscriptions (user_id, package_id, sub_course_plan_id, status, expires_at)
                     VALUES (?, NULL, ?, "active", ?)'
                )->execute([$userId, $planId, $expiresAt]);
            }

            $pdo->commit();

            return [
                'payment_id' => $paymentId,
                'transaction_ref' => $txnRef,
                'plan' => $plan,
                'expires_at' => $expiresAt,
                'amount_inr' => $price,
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function ensurePaymentsPlanColumn(): void
    {
        if (!SchemaHelper::hasTable('payments')) {
            return;
        }
        if (!SchemaHelper::columnExists('payments', 'sub_course_plan_id')) {
            try {
                db()->exec(
                    'ALTER TABLE payments ADD COLUMN sub_course_plan_id INT UNSIGNED NULL DEFAULT NULL AFTER package_id'
                );
            } catch (Throwable $e) {
                // Column may already exist under different migration state.
            }
        }
    }

    private function insertPayment(int $userId, int $planId, float $amount, string $method, string $txnRef, string $notes = 'Sub-course plan enrolment via checkout'): int
    {
        if (!SchemaHelper::hasTable('payments')) {
            return 0;
        }

        $hasPlanCol = SchemaHelper::columnExists('payments', 'sub_course_plan_id');
        if ($hasPlanCol) {
            $st = db()->prepare(
                'INSERT INTO payments (user_id, package_id, sub_course_plan_id, amount_inr, payment_method, transaction_ref, status, notes)
                 VALUES (?, NULL, ?, ?, ?, ?, "completed", ?)'
            );
            $st->execute([
                $userId,
                $planId,
                $amount,
                $method,
                $txnRef,
                $notes,
            ]);
        } else {
            $st = db()->prepare(
                'INSERT INTO payments (user_id, package_id, amount_inr, payment_method, transaction_ref, status, notes)
                 VALUES (?, NULL, ?, ?, ?, "completed", ?)'
            );
            $st->execute([
                $userId,
                $amount,
                $method,
                $txnRef,
                $notes,
            ]);
        }

        return (int) db()->lastInsertId();
    }
}
