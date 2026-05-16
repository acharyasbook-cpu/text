<?php

declare(strict_types=1);

final class SubjectTermMatrixRepository
{
    public const TERM_SHORT = 'short_term';

    public const TERM_LONG = 'long_term';

    public const DEFAULT_SCHEDULE_DAYS = 250;

    public function tablesReady(): bool
    {
        return SchemaHelper::subCourseTermMatrixEnabled()
            || (SchemaHelper::hasTable('subject_term_boxes')
                && SchemaHelper::hasTable('subject_term_schedule'));
    }

    public function subCourseTablesReady(): bool
    {
        return SchemaHelper::subCourseTermMatrixEnabled();
    }

    /** @return array<string,string|null> */
    public function globalDefaults(): array
    {
        $platform = new PlatformRepository();
        $get = static function (string $primary, string $legacy, string $default) use ($platform): string {
            $v = $platform->get($primary, '');
            if ($v === '') {
                $v = $platform->get($legacy, $default);
            }

            return $v !== '' ? $v : $default;
        };

        return [
            'short_term_label_en' => $get('schedule_test.short_term_label_en', 'term_matrix.short_term_label_en', 'Short Term'),
            'short_term_label_te' => $get('schedule_test.short_term_label_te', 'term_matrix.short_term_label_te', 'షార్ట్ టర్మ్'),
            'long_term_label_en' => $get('schedule_test.long_term_label_en', 'term_matrix.long_term_label_en', 'Long Term'),
            'long_term_label_te' => $get('schedule_test.long_term_label_te', 'term_matrix.long_term_label_te', 'లాంగ్ టర్మ్'),
            'short_term_enabled' => $get('schedule_test.short_term_enabled', 'term_matrix.short_term_enabled', '1'),
            'long_term_enabled' => $get('schedule_test.long_term_enabled', 'term_matrix.long_term_enabled', '1'),
            'schedule_days' => $get('schedule_test.schedule_days', 'term_matrix.schedule_days', (string) self::DEFAULT_SCHEDULE_DAYS),
        ];
    }

    /** @param array<string,mixed> $payload */
    public function saveGlobalDefaults(array $payload): void
    {
        $platform = new PlatformRepository();
        $map = [
            'schedule_test.short_term_label_en' => trim((string) ($payload['short_term_label_en'] ?? '')),
            'schedule_test.short_term_label_te' => trim((string) ($payload['short_term_label_te'] ?? '')),
            'schedule_test.long_term_label_en' => trim((string) ($payload['long_term_label_en'] ?? '')),
            'schedule_test.long_term_label_te' => trim((string) ($payload['long_term_label_te'] ?? '')),
            'schedule_test.short_term_enabled' => !empty($payload['short_term_enabled']) ? '1' : '0',
            'schedule_test.long_term_enabled' => !empty($payload['long_term_enabled']) ? '1' : '0',
            'schedule_test.schedule_days' => (string) max(1, min(365, (int) ($payload['schedule_days'] ?? self::DEFAULT_SCHEDULE_DAYS))),
        ];
        foreach ($map as $key => $val) {
            $platform->set($key, $val);
        }
        $legacy = [
            'term_matrix.short_term_label_en' => $map['schedule_test.short_term_label_en'],
            'term_matrix.short_term_label_te' => $map['schedule_test.short_term_label_te'],
            'term_matrix.long_term_label_en' => $map['schedule_test.long_term_label_en'],
            'term_matrix.long_term_label_te' => $map['schedule_test.long_term_label_te'],
            'term_matrix.short_term_enabled' => $map['schedule_test.short_term_enabled'],
            'term_matrix.long_term_enabled' => $map['schedule_test.long_term_enabled'],
            'term_matrix.schedule_days' => $map['schedule_test.schedule_days'],
        ];
        foreach ($legacy as $key => $val) {
            $platform->set($key, $val);
        }
    }

    /** @return list<array<string,mixed>> */
    public function boxesForSubCourse(int $subCourseId): array
    {
        if ($subCourseId < 1 || !$this->subCourseTablesReady()) {
            return [];
        }

        $globals = $this->globalDefaults();
        $st = db()->prepare(
            'SELECT * FROM sub_course_term_boxes WHERE sub_course_id = ? ORDER BY sort_order, term_key'
        );
        $st->execute([$subCourseId]);
        $rows = $st->fetchAll() ?: [];
        $byKey = [];
        foreach ($rows as $row) {
            $byKey[(string) $row['term_key']] = $row;
        }

        $out = [];
        foreach ([self::TERM_SHORT, self::TERM_LONG] as $termKey) {
            $row = $byKey[$termKey] ?? null;
            $globalEnabled = ($termKey === self::TERM_SHORT)
                ? ($globals['short_term_enabled'] === '1')
                : ($globals['long_term_enabled'] === '1');
            $labelEn = $termKey === self::TERM_SHORT
                ? ($globals['short_term_label_en'] ?? 'Short Term')
                : ($globals['long_term_label_en'] ?? 'Long Term');
            $labelTe = $termKey === self::TERM_SHORT
                ? ($globals['short_term_label_te'] ?? 'షార్ట్ టర్మ్')
                : ($globals['long_term_label_te'] ?? 'లాంగ్ టర్మ్');

            $out[] = [
                'term_key' => $termKey,
                'sub_course_id' => $subCourseId,
                'label_en' => ($row['label_en'] ?? '') !== '' ? (string) $row['label_en'] : (string) $labelEn,
                'label_te' => ($row['label_te'] ?? '') !== '' ? (string) $row['label_te'] : (string) $labelTe,
                'is_enabled' => $row !== null ? (int) ($row['is_enabled'] ?? 0) : ($globalEnabled ? 1 : 0),
                'schedule_days' => (int) ($row['schedule_days'] ?? $globals['schedule_days'] ?? self::DEFAULT_SCHEDULE_DAYS),
                'id' => $row ? (int) $row['id'] : 0,
            ];
        }

        return $out;
    }

    /**
     * Auto-map short_term / long_term routing keys and labels from the sub-course row (no manual slug typing).
     *
     * @return list<array<string,mixed>>
     */
    public function syncSubCourseTermBoxesFromRecord(int $subCourseId): array
    {
        if ($subCourseId < 1 || !$this->subCourseTablesReady()) {
            return [];
        }

        $st = db()->prepare('SELECT id, name, name_te, slug FROM sub_courses WHERE id = ? LIMIT 1');
        $st->execute([$subCourseId]);
        $row = $st->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Sub-course not found');
        }

        $globals = $this->globalDefaults();
        $nameEn = trim((string) ($row['name'] ?? 'Programme'));
        $nameTe = trim((string) ($row['name_te'] ?? ''));
        if ($nameTe === '') {
            $nameTe = $nameEn;
        }
        $shortGlobalTe = (string) ($globals['short_term_label_te'] ?? 'షార్ట్ టర్మ్');
        $longGlobalTe = (string) ($globals['long_term_label_te'] ?? 'లాంగ్ టర్మ్');
        $shortGlobalEn = (string) ($globals['short_term_label_en'] ?? 'Short Term');
        $longGlobalEn = (string) ($globals['long_term_label_en'] ?? 'Long Term');
        $days = max(1, (int) ($globals['schedule_days'] ?? self::DEFAULT_SCHEDULE_DAYS));

        $pairs = [
            self::TERM_SHORT => [
                'label_en' => $nameEn . ' · ' . $shortGlobalEn,
                'label_te' => $nameTe . ' · ' . $shortGlobalTe,
                'route_key' => 'short',
            ],
            self::TERM_LONG => [
                'label_en' => $nameEn . ' · ' . $longGlobalEn,
                'label_te' => $nameTe . ' · ' . $longGlobalTe,
                'route_key' => 'long',
            ],
        ];

        foreach ($pairs as $termKey => $meta) {
            $existing = db()->prepare(
                'SELECT is_enabled FROM sub_course_term_boxes WHERE sub_course_id = ? AND term_key = ? LIMIT 1'
            );
            $existing->execute([$subCourseId, $termKey]);
            $enabled = $existing->fetchColumn();
            $enabled = $enabled === false ? 1 : (int) $enabled;

            $this->saveSubCourseBox($subCourseId, [
                'term_key' => $termKey,
                'label_en' => $meta['label_en'],
                'label_te' => $meta['label_te'],
                'is_enabled' => $enabled,
                'schedule_days' => $days,
            ]);
            $this->ensureSubCourseScheduleSeeded($subCourseId, $termKey);
        }

        return $this->boxesForSubCourse($subCourseId);
    }

    /**
     * @param list<array<string,mixed>> $boxes Only term_key + is_enabled required from admin UI.
     * @return list<array<string,mixed>>
     */
    public function applySubCourseTermToggles(int $subCourseId, array $boxes): array
    {
        $synced = $this->syncSubCourseTermBoxesFromRecord($subCourseId);
        if ($boxes === []) {
            return $synced;
        }

        foreach ($boxes as $box) {
            if (!is_array($box)) {
                continue;
            }
            $termKey = (string) ($box['term_key'] ?? '');
            if (!in_array($termKey, [self::TERM_SHORT, self::TERM_LONG], true)) {
                continue;
            }
            db()->prepare(
                'UPDATE sub_course_term_boxes SET is_enabled = ? WHERE sub_course_id = ? AND term_key = ?'
            )->execute([
                !empty($box['is_enabled']) ? 1 : 0,
                $subCourseId,
                $termKey,
            ]);
        }

        return $this->boxesForSubCourse($subCourseId);
    }

    /** @param array<string,mixed> $box */
    public function saveSubCourseBox(int $subCourseId, array $box): void
    {
        if ($subCourseId < 1 || !$this->subCourseTablesReady()) {
            throw new RuntimeException('Sub-course schedule test tables not migrated.');
        }

        $termKey = (string) ($box['term_key'] ?? '');
        if (!in_array($termKey, [self::TERM_SHORT, self::TERM_LONG], true)) {
            throw new InvalidArgumentException('Invalid term_key');
        }

        $labelEn = trim((string) ($box['label_en'] ?? ''));
        $labelTe = trim((string) ($box['label_te'] ?? ''));
        $enabled = !empty($box['is_enabled']) ? 1 : 0;
        $days = max(1, min(365, (int) ($box['schedule_days'] ?? self::DEFAULT_SCHEDULE_DAYS)));
        $sort = $termKey === self::TERM_SHORT ? 0 : 1;

        db()->prepare(
            'INSERT INTO sub_course_term_boxes (sub_course_id, term_key, label_en, label_te, is_enabled, schedule_days, sort_order)
             VALUES (?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE label_en=VALUES(label_en), label_te=VALUES(label_te),
               is_enabled=VALUES(is_enabled), schedule_days=VALUES(schedule_days), sort_order=VALUES(sort_order)'
        )->execute([
            $subCourseId,
            $termKey,
            $labelEn !== '' ? $labelEn : null,
            $labelTe !== '' ? $labelTe : null,
            $enabled,
            $days,
            $sort,
        ]);
    }

    public function subCourseScheduleSlot(int $subCourseId, string $termKey, int $dayNumber): ?array
    {
        if ($subCourseId < 1 || !$this->subCourseTablesReady() || $dayNumber < 1) {
            return null;
        }
        $st = db()->prepare(
            'SELECT scts.*, t.slug AS test_slug, t.title AS test_title, t.duration_mins, t.total_questions
             FROM sub_course_term_schedule scts
             LEFT JOIN tests t ON t.id = scts.test_id
             WHERE scts.sub_course_id = ? AND scts.term_key = ? AND scts.day_number = ? AND scts.is_active = 1
             LIMIT 1'
        );
        $st->execute([$subCourseId, $termKey, $dayNumber]);

        return $st->fetch() ?: null;
    }

    public function ensureSubCourseScheduleSeeded(int $subCourseId, string $termKey): void
    {
        if (!$this->subCourseTablesReady() || $subCourseId < 1) {
            return;
        }
        $cnt = db()->prepare(
            'SELECT COUNT(*) FROM sub_course_term_schedule WHERE sub_course_id = ? AND term_key = ?'
        );
        $cnt->execute([$subCourseId, $termKey]);
        if ((int) $cnt->fetchColumn() > 0) {
            return;
        }

        $shortTypes = ['topic', 'division'];
        $longTypes = ['revision', 'grand', 'model'];
        $types = $termKey === self::TERM_SHORT ? $shortTypes : $longTypes;
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $live = SchemaHelper::testsHasStatus() ? ' AND t.status = 1 AND t.is_active = 1' : ' AND t.is_active = 1';
        $sql = "SELECT t.id, t.title, t.title_te, t.slug, t.test_type
                FROM tests t
                INNER JOIN subjects s ON s.id = t.subject_id
                INNER JOIN sub_course_subjects scs ON scs.subject_id = s.id
                WHERE scs.sub_course_id = ? AND t.test_type IN ({$placeholders}){$live}
                ORDER BY scs.sort_order, s.sort_order, t.id";
        $st = db()->prepare($sql);
        $st->execute(array_merge([$subCourseId], $types));
        $tests = $st->fetchAll() ?: [];
        if ($tests === []) {
            return;
        }

        $ins = db()->prepare(
            'INSERT INTO sub_course_term_schedule (sub_course_id, term_key, day_number, test_id, title, title_te)
             VALUES (?,?,?,?,?,?)'
        );
        $day = 1;
        foreach ($tests as $t) {
            if ($day > self::DEFAULT_SCHEDULE_DAYS) {
                break;
            }
            $ins->execute([
                $subCourseId,
                $termKey,
                $day,
                (int) $t['id'],
                $t['title'],
                $t['title_te'],
            ]);
            ++$day;
        }
    }

    /** @return list<array<string,mixed>> */
    public function boxesForSubject(int $subjectId): array
    {
        if ($subjectId < 1 || !$this->tablesReady()) {
            return [];
        }

        $globals = $this->globalDefaults();
        $st = db()->prepare(
            'SELECT * FROM subject_term_boxes WHERE subject_id = ? ORDER BY sort_order, term_key'
        );
        $st->execute([$subjectId]);
        $rows = $st->fetchAll() ?: [];
        $byKey = [];
        foreach ($rows as $row) {
            $byKey[(string) $row['term_key']] = $row;
        }

        $out = [];
        foreach ([self::TERM_SHORT, self::TERM_LONG] as $termKey) {
            $row = $byKey[$termKey] ?? null;
            $globalEnabled = ($termKey === self::TERM_SHORT)
                ? ($globals['short_term_enabled'] === '1')
                : ($globals['long_term_enabled'] === '1');
            $labelEn = $termKey === self::TERM_SHORT
                ? ($globals['short_term_label_en'] ?? 'Short Term')
                : ($globals['long_term_label_en'] ?? 'Long Term');
            $labelTe = $termKey === self::TERM_SHORT
                ? ($globals['short_term_label_te'] ?? 'షార్ట్ టర్మ్')
                : ($globals['long_term_label_te'] ?? 'లాంగ్ టర్మ్');

            $out[] = [
                'term_key' => $termKey,
                'subject_id' => $subjectId,
                'label_en' => ($row['label_en'] ?? '') !== '' ? (string) $row['label_en'] : (string) $labelEn,
                'label_te' => ($row['label_te'] ?? '') !== '' ? (string) $row['label_te'] : (string) $labelTe,
                'is_enabled' => $row !== null ? (int) ($row['is_enabled'] ?? 0) : ($globalEnabled ? 1 : 0),
                'schedule_days' => (int) ($row['schedule_days'] ?? $globals['schedule_days'] ?? self::DEFAULT_SCHEDULE_DAYS),
                'id' => $row ? (int) $row['id'] : 0,
            ];
        }

        return $out;
    }

    /** @param array<string,mixed> $box */
    public function saveSubjectBox(int $subjectId, array $box): void
    {
        if ($subjectId < 1 || !$this->tablesReady()) {
            throw new RuntimeException('Subject term matrix tables not migrated.');
        }

        $termKey = (string) ($box['term_key'] ?? '');
        if (!in_array($termKey, [self::TERM_SHORT, self::TERM_LONG], true)) {
            throw new InvalidArgumentException('Invalid term_key');
        }

        $labelEn = trim((string) ($box['label_en'] ?? ''));
        $labelTe = trim((string) ($box['label_te'] ?? ''));
        $enabled = !empty($box['is_enabled']) ? 1 : 0;
        $days = max(1, min(365, (int) ($box['schedule_days'] ?? self::DEFAULT_SCHEDULE_DAYS)));
        $sort = $termKey === self::TERM_SHORT ? 0 : 1;

        db()->prepare(
            'INSERT INTO subject_term_boxes (subject_id, term_key, label_en, label_te, is_enabled, schedule_days, sort_order)
             VALUES (?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE label_en=VALUES(label_en), label_te=VALUES(label_te),
               is_enabled=VALUES(is_enabled), schedule_days=VALUES(schedule_days), sort_order=VALUES(sort_order)'
        )->execute([
            $subjectId,
            $termKey,
            $labelEn !== '' ? $labelEn : null,
            $labelTe !== '' ? $labelTe : null,
            $enabled,
            $days,
            $sort,
        ]);
    }

    public function scheduleSlot(int $subjectId, string $termKey, int $dayNumber): ?array
    {
        if ($subjectId < 1 || !$this->tablesReady() || $dayNumber < 1) {
            return null;
        }
        $st = db()->prepare(
            'SELECT sts.*, t.slug AS test_slug, t.title AS test_title, t.duration_mins, t.total_questions
             FROM subject_term_schedule sts
             LEFT JOIN tests t ON t.id = sts.test_id
             WHERE sts.subject_id = ? AND sts.term_key = ? AND sts.day_number = ? AND sts.is_active = 1
             LIMIT 1'
        );
        $st->execute([$subjectId, $termKey, $dayNumber]);

        return $st->fetch() ?: null;
    }

    /** Auto-build schedule from subject tests when empty (sequential day mapping). */
    public function ensureScheduleSeeded(int $subjectId, string $termKey): void
    {
        if (!$this->tablesReady() || $subjectId < 1) {
            return;
        }
        $cnt = db()->prepare(
            'SELECT COUNT(*) FROM subject_term_schedule WHERE subject_id = ? AND term_key = ?'
        );
        $cnt->execute([$subjectId, $termKey]);
        if ((int) $cnt->fetchColumn() > 0) {
            return;
        }

        $shortTypes = ['topic', 'division'];
        $longTypes = ['revision', 'grand', 'model'];
        $types = $termKey === self::TERM_SHORT ? $shortTypes : $longTypes;
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $live = SchemaHelper::testsHasStatus() ? ' AND status = 1 AND is_active = 1' : ' AND is_active = 1';
        $sql = "SELECT id, title, title_te, slug, test_type FROM tests WHERE subject_id = ? AND test_type IN ({$placeholders}){$live} ORDER BY id";
        $st = db()->prepare($sql);
        $st->execute(array_merge([$subjectId], $types));
        $tests = $st->fetchAll() ?: [];
        if ($tests === []) {
            return;
        }

        $ins = db()->prepare(
            'INSERT INTO subject_term_schedule (subject_id, term_key, day_number, test_id, title, title_te)
             VALUES (?,?,?,?,?,?)'
        );
        $day = 1;
        foreach ($tests as $t) {
            if ($day > self::DEFAULT_SCHEDULE_DAYS) {
                break;
            }
            $ins->execute([
                $subjectId,
                $termKey,
                $day,
                (int) $t['id'],
                $t['title'],
                $t['title_te'],
            ]);
            ++$day;
        }
    }
}
