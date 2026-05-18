<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/SchemaHelper.php';

final class CouponRepository
{
    public static function tableReady(): bool
    {
        return SchemaHelper::hasTable('st_coupons');
    }

    public static function usageLogsReady(): bool
    {
        return SchemaHelper::hasTable('coupon_usage_logs');
    }

    /** @return list<array<string,mixed>> */
    public function listAll(): array
    {
        if (!self::tableReady()) {
            return [];
        }

        $hasLogs = self::usageLogsReady();
        $logRedemptions = $hasLogs
            ? '(SELECT COUNT(*) FROM coupon_usage_logs l WHERE l.coupon_id = c.id)'
            : '0';
        $logStudents = $hasLogs
            ? '(SELECT COUNT(DISTINCT l.student_id) FROM coupon_usage_logs l WHERE l.coupon_id = c.id)'
            : '0';
        $logRevenue = $hasLogs
            ? '(SELECT COALESCE(SUM(l.final_amount_paid), 0) FROM coupon_usage_logs l WHERE l.coupon_id = c.id)'
            : '0';
        $logNames = $hasLogs
            ? '(SELECT GROUP_CONCAT(DISTINCT COALESCE(u.name, CONCAT(\'#\', l.student_id)) ORDER BY l.student_id SEPARATOR ", ")
                FROM coupon_usage_logs l
                LEFT JOIN users u ON u.id = l.student_id
                WHERE l.coupon_id = c.id)'
            : 'NULL';

        return db()->query(
            "SELECT c.*,
                    sc.name AS sub_course_name, sc.name_te AS sub_course_name_te,
                    co.name AS main_course_name, co.name_te AS main_course_name_te,
                    {$logRedemptions} AS redemption_count,
                    {$logStudents} AS enrolled_student_count,
                    {$logRevenue} AS total_revenue_from_logs,
                    {$logNames} AS enrolled_student_names
             FROM st_coupons c
             LEFT JOIN sub_courses sc ON sc.id = c.applicable_sub_course_id
             LEFT JOIN courses co ON co.id = sc.course_id
             ORDER BY c.id DESC"
        )->fetchAll() ?: [];
    }

    /**
     * All sub-courses for coupon targeting dropdown (with main course label).
     *
     * @return list<array{id:int,name:string,name_te:string,course_name:string,course_name_te:string}>
     */
    public function subCoursesForSelect(): array
    {
        if (!SchemaHelper::hasTable('sub_courses')) {
            return [];
        }

        return db()->query(
            'SELECT sc.id, sc.name, sc.name_te, c.name AS course_name, c.name_te AS course_name_te
             FROM sub_courses sc
             INNER JOIN courses c ON c.id = sc.course_id
             ORDER BY c.id, sc.id'
        )->fetchAll() ?: [];
    }

    public function delete(int $id): void
    {
        if ($id < 1 || !self::tableReady()) {
            return;
        }
        db()->prepare('DELETE FROM st_coupons WHERE id=?')->execute([$id]);
    }

    /** @param array<string,mixed> $data */
    public function save(array $data, ?int $id = null): int
    {
        if (!self::tableReady()) {
            throw new RuntimeException('Run: php database/migrate_st_coupons.php');
        }
        $code = strtoupper(preg_replace('/\s+/', '', (string) ($data['coupon_code'] ?? '')));
        if ($code === '') {
            throw new InvalidArgumentException('coupon_code required');
        }
        $promoter = trim((string) ($data['promoter_name'] ?? ''));
        if (strlen($promoter) > 128) {
            $promoter = substr($promoter, 0, 128);
        }
        if ($promoter === '') {
            $promoter = null;
        }

        $type = ($data['discount_type'] ?? '') === 'fixed_amount' ? 'fixed_amount' : 'percentage';
        $val = max(0, (float) ($data['discount_value'] ?? 0));
        if ($type === 'percentage' && $val > 100) {
            $val = 100.0;
        }
        $subCourseId = isset($data['applicable_sub_course_id']) && $data['applicable_sub_course_id'] !== ''
            ? (int) $data['applicable_sub_course_id'] : null;
        if ($subCourseId !== null && $subCourseId < 1) {
            $subCourseId = null;
        }

        $expiry = !empty($data['expiry_date']) ? (string) $data['expiry_date'] : null;
        $usageLimit = array_key_exists('usage_limit', $data)
            ? ($data['usage_limit'] === '' || $data['usage_limit'] === null ? null : (int) $data['usage_limit'])
            : null;
        if ($usageLimit !== null && $usageLimit < 1) {
            $usageLimit = null;
        }
        $active = !empty($data['is_active']) ? 1 : 0;

        if ($id !== null && $id > 0) {
            db()->prepare(
                'UPDATE st_coupons SET coupon_code=?, promoter_name=?, discount_type=?, discount_value=?, applicable_sub_course_id=?,
                 expiry_date=?, usage_limit=?, is_active=? WHERE id=?'
            )->execute([$code, $promoter, $type, $val, $subCourseId, $expiry, $usageLimit, $active, $id]);

            return $id;
        }

        db()->prepare(
            'INSERT INTO st_coupons (coupon_code, promoter_name, discount_type, discount_value, applicable_sub_course_id, expiry_date, usage_limit, used_count, is_active)
             VALUES (?,?,?,?,?,?,?,0,?)'
        )->execute([$code, $promoter, $type, $val, $subCourseId, $expiry, $usageLimit, $active]);

        return (int) db()->lastInsertId();
    }

    /**
     * @return array{ok:bool,error_te?:string,original_inr?:float,discount_inr?:float,final_inr?:float,coupon_id?:int}
     */
    public function validateForPlan(int $planId, string $rawCode): array
    {
        $bad = [
            'ok' => false,
            'error_te' => 'ఈ కూపన్ చెల్లదు లేదా గడువు ముగిసింది.',
        ];
        $wrongSub = [
            'ok' => false,
            'error_te' => 'ఈ కూపన్ మీరు ఎంచుకున్న సబ్-కోర్సుకు వర్తించదు.',
        ];
        if (!self::tableReady() || $planId < 1) {
            return $bad;
        }

        $code = strtoupper(trim($rawCode));
        if ($code === '') {
            return $bad;
        }

        $plan = $this->loadPlanRow($planId);
        if (!$plan) {
            return $bad;
        }

        $original = (float) ($plan['price_inr'] ?? 0);
        if ($original < 0) {
            return $bad;
        }

        $planSubId = (int) ($plan['sub_course_id'] ?? 0);

        $st = db()->prepare(
            'SELECT * FROM st_coupons WHERE UPPER(TRIM(coupon_code)) = ? LIMIT 1'
        );
        $st->execute([$code]);
        $row = $st->fetch();
        if (!$row) {
            return $bad;
        }

        if ((int) ($row['is_active'] ?? 0) !== 1) {
            return $bad;
        }

        $targetSub = (int) ($row['applicable_sub_course_id'] ?? 0);
        if ($targetSub > 0 && $planSubId !== $targetSub) {
            return $wrongSub;
        }

        $exp = $row['expiry_date'] ?? null;
        if ($exp !== null && $exp !== '' && (string) $exp < date('Y-m-d')) {
            return $bad;
        }

        $used = (int) ($row['used_count'] ?? 0);
        $limit = $row['usage_limit'] ?? null;
        if ($limit !== null && (int) $limit > 0 && $used >= (int) $limit) {
            return $bad;
        }

        $discount = $this->computeDiscount($original, (string) ($row['discount_type'] ?? 'percentage'), (float) ($row['discount_value'] ?? 0));
        $final = max(0.0, round($original - $discount, 2));

        return [
            'ok' => true,
            'original_inr' => $original,
            'discount_inr' => round($discount, 2),
            'final_inr' => $final,
            'coupon_id' => (int) $row['id'],
        ];
    }

    /** @return array<string,mixed>|null */
    private function loadPlanRow(int $planId): ?array
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

    public function computeDiscount(float $originalInr, string $discountType, float $discountValue): float
    {
        if ($originalInr <= 0) {
            return 0.0;
        }
        if ($discountType === 'fixed_amount') {
            return min($originalInr, max(0, $discountValue));
        }
        $pct = max(0.0, min(100.0, $discountValue));

        return $originalInr * ($pct / 100.0);
    }

    public function incrementUsage(int $couponId): void
    {
        if ($couponId < 1 || !self::tableReady()) {
            return;
        }
        db()->prepare(
            'UPDATE st_coupons SET used_count = used_count + 1
             WHERE id = ? AND (usage_limit IS NULL OR usage_limit < 1 OR used_count < usage_limit)'
        )->execute([$couponId]);
    }

    public function logRedemption(int $couponId, int $studentId, int $subCourseId, float $discountApplied, float $finalAmountPaid): void
    {
        if (!self::usageLogsReady() || $couponId < 1 || $studentId < 1 || $subCourseId < 1) {
            return;
        }
        db()->prepare(
            'INSERT INTO coupon_usage_logs (coupon_id, student_id, sub_course_id, discount_applied, final_amount_paid)
             VALUES (?,?,?,?,?)'
        )->execute([
            $couponId,
            $studentId,
            $subCourseId,
            round($discountApplied, 2),
            round($finalAmountPaid, 2),
        ]);
    }
}
