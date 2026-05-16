<?php

declare(strict_types=1);

/**
 * AP DSC — School Assistant specialisations (ap-sa-*): shared subjects, pivots, plans helpers.
 * Five papers per programme: GK, Perspective in Education, Classroom Psychology, Content (track-specific), Methodology (track-specific).
 */
final class ApSaCatalog
{
    /** @return list<array{slug:string,name:string,name_te:string,sort:int,subject_key:string,label_en:string,content_te:string}> */
    public static function apDscProgrammes(): array
    {
        return [
            ['slug' => 'ap-sa-telugu', 'name' => 'AP SA Telugu', 'name_te' => 'ఏపీ ఎస్‌ఏ తెలుగు', 'sort' => 22, 'subject_key' => 'telugu', 'label_en' => 'Telugu', 'content_te' => 'తెలుగు'],
            ['slug' => 'ap-sa-hindi', 'name' => 'AP SA Hindi', 'name_te' => 'ఏపీ ఎస్‌ఏ హిందీ', 'sort' => 23, 'subject_key' => 'hindi', 'label_en' => 'Hindi', 'content_te' => 'హిందీ'],
            ['slug' => 'ap-sa-english', 'name' => 'AP SA English', 'name_te' => 'ఏపీ ఎస్‌ఏ ఇంగ్లీష్', 'sort' => 24, 'subject_key' => 'english', 'label_en' => 'English', 'content_te' => 'ఇంగ్లీష్'],
            ['slug' => 'ap-sa-maths', 'name' => 'AP SA Mathematics', 'name_te' => 'ఏపీ ఎస్‌ఏ గణితం', 'sort' => 25, 'subject_key' => 'maths', 'label_en' => 'Mathematics', 'content_te' => 'గణితం'],
            ['slug' => 'ap-sa-physical-science', 'name' => 'AP SA Physical Science', 'name_te' => 'ఏపీ ఎస్‌ఏ భౌతిక శాస్త్రం', 'sort' => 26, 'subject_key' => 'physical-science', 'label_en' => 'Physical Science', 'content_te' => 'భౌతిక శాస్త్రం'],
            ['slug' => 'ap-sa-biological-science', 'name' => 'AP SA Biological Science', 'name_te' => 'ఏపీ ఎస్‌ఏ జీవ శాస్త్రం', 'sort' => 27, 'subject_key' => 'biological-science', 'label_en' => 'Biological Science', 'content_te' => 'జీవ శాస్త్రం'],
            ['slug' => 'ap-sa-social-studies', 'name' => 'AP SA Social Studies', 'name_te' => 'ఏపీ ఎస్‌ఏ సామాజిక శాస్త్రం', 'sort' => 28, 'subject_key' => 'social-studies', 'label_en' => 'Social Studies', 'content_te' => 'సామాజిక శాస్త్రం'],
        ];
    }

    /** @return list<string> */
    public static function programmeSlugs(): array
    {
        return array_column(self::apDscProgrammes(), 'slug');
    }

    /** @return list<string> */
    /** Slugs recognised as valid flagship programmes (AP DSC + TS DSC + TET base cards + AP DSC structured tracks). */
    public static function standardSubCourseSlugs(): array
    {
        require_once __DIR__ . '/ApDscTgtPgtCatalog.php';

        return ApDscTgtPgtCatalog::standardFlagshipSlugs();
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:int}> slug, name, name_te, sort_order segment within programme
     */
    public static function paperRowsForProgramme(array $prog): array
    {
        $le = $prog['label_en'];
        $ct = $prog['content_te'];

        return [
            ['gk-current-affairs', 'GK & Current Affairs', 'జి.కె. & కరెంట్ అఫైర్స్', 1],
            ['perspective-education', 'Perspective in Education', 'విద్యా దృక్కోణాలు', 2],
            ['classroom-psychology', 'Classroom Psychology', 'తరగతి గది మనోవిజ్ఞానం', 3],
            ['content', 'Content (' . $le . ')', 'కంటెంట్ (' . $ct . ')', 4],
            ['methodology', 'Methodology (' . $le . ')', 'పద్ధతిశాస్త్రం (' . $ct . ')', 5],
        ];
    }

    /** Full subject slug for pivot resolution */
    public static function subjectSlug(string $subjectKey, string $paperSuffix): string
    {
        return 'ap-sa-' . $subjectKey . '-' . $paperSuffix;
    }

    /** Upsert all AP SA paper subjects (global subjects library). */
    public static function ensureSubjects(PDO $pdo): void
    {
        $ins = $pdo->prepare(
            'INSERT INTO subjects (course_id, category_id, slug, name, name_te, description, sort_order, status, is_active)
             VALUES (NULL, NULL, ?, ?, ?, NULL, ?, 1, 1)
             ON DUPLICATE KEY UPDATE name = VALUES(name), name_te = VALUES(name_te), sort_order = VALUES(sort_order), status = 1, is_active = 1'
        );
        foreach (self::apDscProgrammes() as $prog) {
            foreach (self::paperRowsForProgramme($prog) as $row) {
                $slug = self::subjectSlug($prog['subject_key'], $row[0]);
                $ins->execute([$slug, $row[1], $row[2], $row[3]]);
            }
        }
    }

    /** Upsert AP SA programme rows under AP DSC main course. */
    public static function ensureProgrammes(PDO $pdo, int $courseId): void
    {
        $hasSt = self::columnExists($pdo, 'sub_courses', 'status');
        $hasTe = self::columnExists($pdo, 'sub_courses', 'name_te');

        foreach (self::apDscProgrammes() as $prog) {
            if ($hasSt && $hasTe) {
                $pdo->prepare(
                    'INSERT INTO sub_courses (course_id, slug, name, name_te, description, sort_order, status, is_active)
                     VALUES (?, ?, ?, ?, NULL, ?, 1, 1)
                     ON DUPLICATE KEY UPDATE name = VALUES(name), name_te = VALUES(name_te), sort_order = VALUES(sort_order), status = 1, is_active = 1'
                )->execute([$courseId, $prog['slug'], $prog['name'], $prog['name_te'], $prog['sort']]);
            } elseif ($hasSt) {
                $pdo->prepare(
                    'INSERT INTO sub_courses (course_id, slug, name, description, sort_order, status, is_active)
                     VALUES (?, ?, ?, NULL, ?, 1, 1)
                     ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order), status = 1, is_active = 1'
                )->execute([$courseId, $prog['slug'], $prog['name'], $prog['sort']]);
            } elseif ($hasTe) {
                $pdo->prepare(
                    'INSERT INTO sub_courses (course_id, slug, name, name_te, description, sort_order, is_active)
                     VALUES (?, ?, ?, ?, NULL, ?, 1)
                     ON DUPLICATE KEY UPDATE name = VALUES(name), name_te = VALUES(name_te), sort_order = VALUES(sort_order), is_active = 1'
                )->execute([$courseId, $prog['slug'], $prog['name'], $prog['name_te'], $prog['sort']]);
            } else {
                $pdo->prepare(
                    'INSERT INTO sub_courses (course_id, slug, name, description, sort_order, is_active)
                     VALUES (?, ?, ?, NULL, ?, 1)
                     ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order), is_active = 1'
                )->execute([$courseId, $prog['slug'], $prog['name'], $prog['sort']]);
            }
        }
    }

    /** Remove every subject pivot from generic School Assistant on AP DSC (specialisations use ap-sa-*). */
    public static function purgeGenericSchoolAssistantPivots(PDO $pdo, int $courseId): void
    {
        $pdo->prepare(
            'DELETE scs FROM sub_course_subjects scs
             INNER JOIN sub_courses sc ON sc.id = scs.sub_course_id
             INNER JOIN courses c ON c.id = sc.course_id
             WHERE c.id = ? AND sc.slug = ?'
        )->execute([$courseId, 'school-assistant']);
    }

    /** Rebuild pivots: each ap-sa-* programme → its five paper subjects. */
    public static function syncProgrammePivots(PDO $pdo, int $courseId): void
    {
        $scIdSt = $pdo->prepare('SELECT id FROM sub_courses WHERE course_id = ? AND slug = ? LIMIT 1');
        $insPivot = $pdo->prepare(
            'INSERT INTO sub_course_subjects (sub_course_id, subject_id, sort_order, status, is_active) VALUES (?,?,?,?,?)'
        );
        $subIdSt = $pdo->prepare('SELECT id FROM subjects WHERE slug = ? LIMIT 1');

        foreach (self::apDscProgrammes() as $prog) {
            $scIdSt->execute([$courseId, $prog['slug']]);
            $scid = (int) $scIdSt->fetchColumn();
            if ($scid < 1) {
                continue;
            }
            $managedSlugs = [];
            foreach (self::paperRowsForProgramme($prog) as $row) {
                $managedSlugs[] = self::subjectSlug($prog['subject_key'], $row[0]);
            }
            if ($managedSlugs !== []) {
                $in = implode(',', array_fill(0, count($managedSlugs), '?'));
                $delManaged = $pdo->prepare(
                    "DELETE scs FROM sub_course_subjects scs
                     INNER JOIN subjects s ON s.id = scs.subject_id
                     WHERE scs.sub_course_id = ? AND s.slug IN ({$in})"
                );
                $delManaged->execute(array_merge([$scid], $managedSlugs));
            }
            $ord = 0;
            foreach (self::paperRowsForProgramme($prog) as $row) {
                $slug = self::subjectSlug($prog['subject_key'], $row[0]);
                $subIdSt->execute([$slug]);
                $sid = $subIdSt->fetchColumn();
                if (!$sid) {
                    continue;
                }
                $insPivot->execute([$scid, (int) $sid, $ord, 1, 1]);
                ++$ord;
            }
        }
    }

    /** Default pricing slabs for all ap-sa-* programmes under course. */
    public static function ensurePlansForApSaProgrammes(PDO $pdo, int $courseId): void
    {
        if (!self::tableExists($pdo, 'sub_course_plans')) {
            return;
        }
        $plans = $pdo->prepare(
            'INSERT IGNORE INTO sub_course_plans (sub_course_id, plan_code, label, price_inr, duration_months, status, is_active)
             VALUES (?,?,?,?,?,1,1)'
        );
        $defaults = [
            ['6_months', '6 Months (₹499)', 499.00, 6],
            ['1_year', '1 Year (₹699)', 699.00, 12],
            ['until_exam', 'Up to Exam (₹999)', 999.00, null],
        ];
        $st = $pdo->prepare(
            "SELECT sc.id FROM sub_courses sc
             JOIN courses c ON c.id = sc.course_id
             WHERE c.id = ? AND sc.slug LIKE 'ap-sa-%'"
        );
        $st->execute([$courseId]);
        while ($rid = $st->fetchColumn()) {
            $scid = (int) $rid;
            foreach ($defaults as [$code, $label, $price, $months]) {
                $plans->execute([$scid, $code, $label, $price, $months]);
            }
        }
    }

    /** Full AP DSC SA standardisation (idempotent). */
    public static function standardizeApDsc(PDO $pdo): void
    {
        $st = $pdo->prepare('SELECT id FROM courses WHERE slug = ? LIMIT 1');
        $st->execute(['ap-dsc']);
        $courseId = (int) ($st->fetchColumn() ?: 0);
        if ($courseId < 1) {
            throw new RuntimeException('Course ap-dsc not found.');
        }

        self::ensureSubjects($pdo);
        self::ensureProgrammes($pdo, $courseId);
        self::purgeGenericSchoolAssistantPivots($pdo, $courseId);
        self::syncProgrammePivots($pdo, $courseId);
        self::ensurePlansForApSaProgrammes($pdo, $courseId);
    }

    /** Seed SaExamPattern for every AP SA paper under AP DSC. */
    public static function seedAllExamPatterns(PDO $pdo): void
    {
        require_once dirname(__DIR__) . '/includes/SaExamPattern.php';

        $st = $pdo->prepare('SELECT id FROM courses WHERE slug = ? LIMIT 1');
        $st->execute(['ap-dsc']);
        $courseId = (int) ($st->fetchColumn() ?: 0);
        if ($courseId < 1) {
            return;
        }

        $subSt = $pdo->prepare(
            'SELECT s.id, s.slug FROM subjects s
             INNER JOIN sub_course_subjects scs ON scs.subject_id = s.id
             INNER JOIN sub_courses sc ON sc.id = scs.sub_course_id
             INNER JOIN courses c ON c.id = sc.course_id
             WHERE c.id = ? AND sc.slug LIKE ? ORDER BY sc.sort_order, sc.slug, scs.sort_order'
        );
        $subSt->execute([$courseId, 'ap-sa-%']);
        while ($row = $subSt->fetch(PDO::FETCH_ASSOC)) {
            SaExamPattern::seedForApDscSubject($pdo, $courseId, (int) $row['id'], (string) $row['slug']);
        }
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        try {
            $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $st = $pdo->prepare(
                "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND TABLE_TYPE = 'BASE TABLE'"
            );
            $st->execute([$db, $table]);

            return (int) $st->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        try {
            $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $st = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $st->execute([$db, $table, $column]);

            return (int) $st->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }
}
