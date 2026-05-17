<?php

declare(strict_types=1);

final class PricingAdminRepository
{
    /** @var array<string,array{label:string,label_te:string,offer:float,original:float,months:?int}> */
    public const PLAN_DEFAULTS = [
        '6_months' => [
            'label' => '6 Months Plan',
            'label_te' => '6 నెలల ప్లాన్',
            'offer' => 499.0,
            'original' => 1000.0,
            'months' => 6,
        ],
        '1_year' => [
            'label' => '1 Year Plan',
            'label_te' => '1 సంవత్సర ప్లాన్',
            'offer' => 699.0,
            'original' => 1500.0,
            'months' => 12,
        ],
        'until_exam' => [
            'label' => 'Up to Exam Plan',
            'label_te' => 'పరీక్ష వరకు ప్లాన్',
            'offer' => 999.0,
            'original' => 2500.0,
            'months' => null,
        ],
    ];

    public function ensureSchema(): void
    {
        if (!SchemaHelper::hasTable('sub_course_plans')) {
            return;
        }
        if (!SchemaHelper::columnExists('sub_course_plans', 'original_price_inr')) {
            db()->exec(
                'ALTER TABLE sub_course_plans ADD COLUMN original_price_inr DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price_inr'
            );
        }
        if (!SchemaHelper::columnExists('sub_course_plans', 'label_te')) {
            db()->exec(
                'ALTER TABLE sub_course_plans ADD COLUMN label_te VARCHAR(120) NULL DEFAULT NULL AFTER label'
            );
        }
    }

    public function ensurePlansForAllSubCourses(): void
    {
        $this->ensureSchema();
        if (!SchemaHelper::hasTable('sub_course_plans') || !SchemaHelper::hasTable('sub_courses')) {
            return;
        }

        $scIds = db()->query('SELECT id FROM sub_courses')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $ins = db()->prepare(
            'INSERT IGNORE INTO sub_course_plans (sub_course_id, plan_code, label, label_te, price_inr, original_price_inr, duration_months, status, is_active)
             VALUES (?,?,?,?,?,?,?,1,1)'
        );

        foreach ($scIds as $scId) {
            $scId = (int) $scId;
            foreach (self::PLAN_DEFAULTS as $code => $def) {
                $ins->execute([
                    $scId,
                    $code,
                    $def['label'],
                    $def['label_te'],
                    $def['offer'],
                    $def['original'],
                    $def['months'],
                ]);
            }
        }
    }

    /** @return list<array<string,mixed>> */
    public function subscriptionPricingMatrix(): array
    {
        $this->ensurePlansForAllSubCourses();
        if (!SchemaHelper::hasTable('sub_courses') || !SchemaHelper::hasTable('sub_course_plans')) {
            return [];
        }

        $hasOrig = SchemaHelper::columnExists('sub_course_plans', 'original_price_inr');
        $hasTe = SchemaHelper::columnExists('sub_course_plans', 'label_te');
        $origCol = $hasOrig ? 'sp.original_price_inr' : 'sp.price_inr';
        $teCol = $hasTe ? 'sp.label_te' : 'NULL AS label_te';

        $sql = "SELECT sc.id AS sub_course_id, sc.slug AS sub_course_slug, sc.name AS sub_course_name, sc.name_te AS sub_course_name_te,
                       c.name AS course_name, c.name_te AS course_name_te,
                       sp.id AS plan_id, sp.plan_code, sp.label, {$teCol}, sp.price_inr, {$origCol} AS original_price_inr,
                       sp.duration_months, sp.is_active, sp.status
                FROM sub_courses sc
                INNER JOIN courses c ON c.id = sc.course_id
                LEFT JOIN sub_course_plans sp ON sp.sub_course_id = sc.id
                ORDER BY c.sort_order, sc.sort_order, FIELD(sp.plan_code, '6_months', '1_year', 'until_exam')";
        $rows = db()->query($sql)->fetchAll() ?: [];

        $grouped = [];
        foreach ($rows as $row) {
            $scId = (int) $row['sub_course_id'];
            if (!isset($grouped[$scId])) {
                $grouped[$scId] = [
                    'sub_course_id' => $scId,
                    'sub_course_slug' => $row['sub_course_slug'],
                    'sub_course_name' => $row['sub_course_name'],
                    'sub_course_name_te' => $row['sub_course_name_te'],
                    'course_name' => $row['course_name'],
                    'course_name_te' => $row['course_name_te'],
                    'plans' => [],
                ];
            }
            $code = (string) ($row['plan_code'] ?? '');
            if ($code === '') {
                continue;
            }
            $def = self::PLAN_DEFAULTS[$code] ?? null;
            $grouped[$scId]['plans'][$code] = [
                'plan_id' => (int) ($row['plan_id'] ?? 0),
                'plan_code' => $code,
                'label' => $row['label'] ?? ($def['label'] ?? $code),
                'label_te' => $row['label_te'] ?? ($def['label_te'] ?? ''),
                'price_inr' => (float) ($row['price_inr'] ?? ($def['offer'] ?? 0)),
                'original_price_inr' => (float) ($row['original_price_inr'] ?? ($def['original'] ?? 0)),
                'duration_months' => $row['duration_months'] !== null ? (int) $row['duration_months'] : null,
                'is_active' => (int) ($row['is_active'] ?? 1),
            ];
        }

        foreach ($grouped as &$row) {
            foreach (self::PLAN_DEFAULTS as $code => $def) {
                if (!isset($row['plans'][$code])) {
                    $row['plans'][$code] = [
                        'plan_id' => 0,
                        'plan_code' => $code,
                        'label' => $def['label'],
                        'label_te' => $def['label_te'],
                        'price_inr' => $def['offer'],
                        'original_price_inr' => $def['original'],
                        'duration_months' => $def['months'],
                        'is_active' => 1,
                    ];
                }
            }
        }
        unset($row);

        return array_values($grouped);
    }

    /** @param list<array<string,mixed>> $rows */
    public function saveSubscriptionPlans(array $rows): void
    {
        $this->ensureSchema();
        if (!SchemaHelper::hasTable('sub_course_plans')) {
            return;
        }

        $hasOrig = SchemaHelper::columnExists('sub_course_plans', 'original_price_inr');
        $hasTe = SchemaHelper::columnExists('sub_course_plans', 'label_te');
        $hasSt = SchemaHelper::columnExists('sub_course_plans', 'status');

        foreach ($rows as $row) {
            $planId = (int) ($row['plan_id'] ?? 0);
            $scId = (int) ($row['sub_course_id'] ?? 0);
            $code = (string) ($row['plan_code'] ?? '');
            if ($scId < 1 || $code === '' || !isset(self::PLAN_DEFAULTS[$code])) {
                continue;
            }

            $offer = max(0, (float) ($row['price_inr'] ?? 0));
            $original = max($offer, (float) ($row['original_price_inr'] ?? 0));
            $label = trim((string) ($row['label'] ?? '')) ?: self::PLAN_DEFAULTS[$code]['label'];
            $labelTe = trim((string) ($row['label_te'] ?? '')) ?: self::PLAN_DEFAULTS[$code]['label_te'];
            $months = array_key_exists('duration_months', $row) && $row['duration_months'] !== '' && $row['duration_months'] !== null
                ? (int) $row['duration_months']
                : self::PLAN_DEFAULTS[$code]['months'];
            $active = !empty($row['is_active']) ? 1 : 0;

            if ($planId > 0) {
                $sets = ['label=?', 'price_inr=?', 'duration_months=?', 'is_active=?'];
                $params = [$label, $offer, $months, $active];
                if ($hasTe) {
                    $sets[] = 'label_te=?';
                    $params[] = $labelTe;
                }
                if ($hasOrig) {
                    $sets[] = 'original_price_inr=?';
                    $params[] = $original;
                }
                if ($hasSt) {
                    $sets[] = 'status=?';
                    $params[] = $active;
                }
                $params[] = $planId;
                $params[] = $scId;
                db()->prepare(
                    'UPDATE sub_course_plans SET ' . implode(', ', $sets) . ' WHERE id=? AND sub_course_id=?'
                )->execute($params);
            } else {
                $cols = ['sub_course_id', 'plan_code', 'label', 'price_inr', 'duration_months', 'is_active'];
                $vals = [$scId, $code, $label, $offer, $months, $active];
                $placeholders = array_fill(0, count($vals), '?');
                if ($hasTe) {
                    array_splice($cols, 3, 0, ['label_te']);
                    array_splice($vals, 3, 0, [$labelTe]);
                    array_splice($placeholders, 3, 0, ['?']);
                }
                if ($hasOrig) {
                    $cols[] = 'original_price_inr';
                    $vals[] = $original;
                    $placeholders[] = '?';
                }
                if ($hasSt) {
                    $cols[] = 'status';
                    $vals[] = $active;
                    $placeholders[] = '?';
                }
                db()->prepare(
                    'INSERT INTO sub_course_plans (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')'
                )->execute($vals);
            }
        }
    }

    public static function planDisplayLabel(string $code): string
    {
        return match ($code) {
            '6_months' => '6 Months',
            '1_year' => '1 Year',
            'until_exam' => 'Up to Exam',
            default => $code,
        };
    }

    public static function planDisplayLabelTe(string $code): string
    {
        return self::PLAN_DEFAULTS[$code]['label_te'] ?? self::planDisplayLabel($code);
    }
}
