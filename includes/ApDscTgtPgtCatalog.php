<?php

declare(strict_types=1);

/**
 * AP DSC — TGT / PGT track programmes (`ap-tgt-*`, `ap-pgt-*`): paper subjects, pivots, plans.
 * Does not apply to structured TS DSC / TET paper programmes (those use dedicated catalogs).
 */
final class ApDscTgtPgtCatalog
{
    /** Sub-course slug whitelist used by migrate_dynamic_hierarchy (HIERARCHY_RESEED). */
    public static function standardFlagshipSlugs(): array
    {
        require_once __DIR__ . '/ApSaCatalog.php';
        require_once __DIR__ . '/TsDscCatalog.php';
        require_once __DIR__ . '/ApTetCatalog.php';
        require_once __DIR__ . '/TsTetCatalog.php';
        require_once __DIR__ . '/CtetCatalog.php';

        return array_merge(
            ['sgt', 'pet'],
            ApSaCatalog::programmeSlugs(),
            TsDscCatalog::structuredProgrammeSlugs(),
            ApTetCatalog::structuredProgrammeSlugs(),
            TsTetCatalog::structuredProgrammeSlugs(),
            CtetCatalog::structuredProgrammeSlugs(),
            ['school-assistant'],
            array_column(self::tgtProgrammes(), 'slug'),
            ['tgt'],
            array_column(self::pgtProgrammes(), 'slug'),
            ['pgt'],
        );
    }

    /** @return list<array{slug:string,name:string,name_te:string,sort:int,subject_key:string,label_en:string,content_te:string}> */
    public static function tgtProgrammes(): array
    {
        return [
            ['slug' => 'ap-tgt-telugu', 'name' => 'AP DSC TGT Telugu', 'name_te' => 'ఏపీ డీఎస్‌సీ టీజీటీ తెలుగు', 'sort' => 41, 'subject_key' => 'telugu', 'label_en' => 'Telugu', 'content_te' => 'తెలుగు'],
            ['slug' => 'ap-tgt-english', 'name' => 'AP DSC TGT English', 'name_te' => 'ఏపీ డీఎస్‌సీ టీజీటీ ఇంగ్లీష్', 'sort' => 42, 'subject_key' => 'english', 'label_en' => 'English', 'content_te' => 'ఇంగ్లీష్'],
            ['slug' => 'ap-tgt-hindi', 'name' => 'AP DSC TGT Hindi', 'name_te' => 'ఏపీ డీఎస్‌సీ టీజీటీ హిందీ', 'sort' => 43, 'subject_key' => 'hindi', 'label_en' => 'Hindi', 'content_te' => 'హిందీ'],
            ['slug' => 'ap-tgt-maths', 'name' => 'AP DSC TGT Mathematics', 'name_te' => 'ఏపీ డీఎస్‌సీ టీజీటీ గణితం', 'sort' => 44, 'subject_key' => 'maths', 'label_en' => 'Mathematics', 'content_te' => 'గణితం'],
            ['slug' => 'ap-tgt-physical-science', 'name' => 'AP DSC TGT Physical Science', 'name_te' => 'ఏపీ డీఎస్‌సీ టీజీటీ భౌతిక శాస్త్రం', 'sort' => 45, 'subject_key' => 'physical-science', 'label_en' => 'Physical Science', 'content_te' => 'భౌతిక శాస్త్రం'],
            ['slug' => 'ap-tgt-biological-science', 'name' => 'AP DSC TGT Biological Science', 'name_te' => 'ఏపీ డీఎస్‌సీ టీజీటీ జీవ శాస్త్రం', 'sort' => 46, 'subject_key' => 'biological-science', 'label_en' => 'Biological Science', 'content_te' => 'జీవ శాస్త్రం'],
            ['slug' => 'ap-tgt-social-studies', 'name' => 'AP DSC TGT Social Studies', 'name_te' => 'ఏపీ డీఎస్‌సీ టీజీటీ సామాజిక శాస్త్రం', 'sort' => 47, 'subject_key' => 'social-studies', 'label_en' => 'Social Studies', 'content_te' => 'సామాజిక శాస్త్రం'],
        ];
    }

    /** @return list<array{slug:string,name:string,name_te:string,sort:int,subject_key:string,label_en:string,content_te:string}> */
    public static function pgtProgrammes(): array
    {
        return [
            ['slug' => 'ap-pgt-telugu', 'name' => 'AP DSC PGT Telugu', 'name_te' => 'ఏపీ డీఎస్‌సీ పీజీటీ తెలుగు', 'sort' => 51, 'subject_key' => 'telugu', 'label_en' => 'Telugu', 'content_te' => 'తెలుగు'],
            ['slug' => 'ap-pgt-english', 'name' => 'AP DSC PGT English', 'name_te' => 'ఏపీ డీఎస్‌సీ పీజీటీ ఇంగ్లీష్', 'sort' => 52, 'subject_key' => 'english', 'label_en' => 'English', 'content_te' => 'ఇంగ్లీష్'],
            ['slug' => 'ap-pgt-hindi', 'name' => 'AP DSC PGT Hindi', 'name_te' => 'ఏపీ డీఎస్‌సీ పీజీటీ హిందీ', 'sort' => 53, 'subject_key' => 'hindi', 'label_en' => 'Hindi', 'content_te' => 'హిందీ'],
            ['slug' => 'ap-pgt-maths', 'name' => 'AP DSC PGT Mathematics', 'name_te' => 'ఏపీ డీఎస్‌సీ పీజీటీ గణితం', 'sort' => 54, 'subject_key' => 'maths', 'label_en' => 'Mathematics', 'content_te' => 'గణితం'],
            ['slug' => 'ap-pgt-physical-science', 'name' => 'AP DSC PGT Physical Science', 'name_te' => 'ఏపీ డీఎస్‌సీ పీజీటీ భౌతిక శాస్త్రం', 'sort' => 55, 'subject_key' => 'physical-science', 'label_en' => 'Physical Science', 'content_te' => 'భౌతిక శాస్త్రం'],
            ['slug' => 'ap-pgt-biological-science', 'name' => 'AP DSC PGT Biological Science', 'name_te' => 'ఏపీ డీఎస్‌సీ పీజీటీ జీవ శాస్త్రం', 'sort' => 56, 'subject_key' => 'biological-science', 'label_en' => 'Biological Science', 'content_te' => 'జీవ శాస్త్రం'],
            ['slug' => 'ap-pgt-social-studies', 'name' => 'AP DSC PGT Social Studies', 'name_te' => 'ఏపీ డీఎస్‌సీ పీజీటీ సామాజిక శాస్త్రం', 'sort' => 57, 'subject_key' => 'social-studies', 'label_en' => 'Social Studies', 'content_te' => 'సామాజిక శాస్త్రం'],
            ['slug' => 'ap-pgt-commerce', 'name' => 'AP DSC PGT Commerce', 'name_te' => 'ఏపీ డీఎస్‌సీ పీజీటీ వాణిజ్యం', 'sort' => 58, 'subject_key' => 'commerce', 'label_en' => 'Commerce', 'content_te' => 'వాణిజ్యం'],
            ['slug' => 'ap-pgt-economics', 'name' => 'AP DSC PGT Economics', 'name_te' => 'ఏపీ డీఎస్‌సీ పీజీటీ ఆర్థిక శాస్త్రం', 'sort' => 59, 'subject_key' => 'economics', 'label_en' => 'Economics', 'content_te' => 'ఆర్థిక శాస్త్రం'],
        ];
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:int}>
     */
    public static function tgtPaperRowsForProgramme(array $prog): array
    {
        $le = $prog['label_en'];
        $ct = $prog['content_te'];

        return [
            ['gk-current-affairs', 'GK & Current Affairs', 'జి.కె. & కరెంట్ అఫైర్స్', 1],
            ['perspective-education', 'Perspective in Education', 'విద్యా దృక్కోణాలు', 2],
            ['classroom-psychology', 'Classroom Psychology', 'తరగతి గది మనోవిజ్ఞానం', 3],
            ['english-proficiency-test', 'English Proficiency Test', 'ఇంగ్లీష్ ప్రొఫిషెన్సీ టెస్ట్', 4],
            ['content', 'Content (' . $le . ')', 'కంటెంట్ (' . $ct . ')', 5],
            ['methodology', 'Methodology (' . $le . ')', 'పద్ధతిశాస్త్రం (' . $ct . ')', 6],
        ];
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:int}>
     */
    public static function pgtPaperRowsForProgramme(array $prog): array
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

    public static function tgtSubjectSlug(string $subjectKey, string $paperSuffix): string
    {
        return 'ap-tgt-' . $subjectKey . '-' . $paperSuffix;
    }

    public static function pgtSubjectSlug(string $subjectKey, string $paperSuffix): string
    {
        return 'ap-pgt-' . $subjectKey . '-' . $paperSuffix;
    }

    public static function ensureSubjects(PDO $pdo): void
    {
        $ins = $pdo->prepare(
            'INSERT INTO subjects (course_id, category_id, slug, name, name_te, description, sort_order, status, is_active)
             VALUES (NULL, NULL, ?, ?, ?, NULL, ?, 1, 1)
             ON DUPLICATE KEY UPDATE name = VALUES(name), name_te = VALUES(name_te), sort_order = VALUES(sort_order), status = 1, is_active = 1'
        );
        foreach (self::tgtProgrammes() as $prog) {
            foreach (self::tgtPaperRowsForProgramme($prog) as $row) {
                $slug = self::tgtSubjectSlug($prog['subject_key'], $row[0]);
                $ins->execute([$slug, $row[1], $row[2], $row[3]]);
            }
        }
        foreach (self::pgtProgrammes() as $prog) {
            foreach (self::pgtPaperRowsForProgramme($prog) as $row) {
                $slug = self::pgtSubjectSlug($prog['subject_key'], $row[0]);
                $ins->execute([$slug, $row[1], $row[2], $row[3]]);
            }
        }
    }

    /** @param list<array{slug:string,name:string,name_te:string,sort:int}> $programmes */
    private static function upsertProgrammeRows(PDO $pdo, int $courseId, array $programmes): void
    {
        $hasSt = self::columnExists($pdo, 'sub_courses', 'status');
        $hasTe = self::columnExists($pdo, 'sub_courses', 'name_te');

        foreach ($programmes as $prog) {
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

    public static function ensureTgtProgrammes(PDO $pdo, int $courseId): void
    {
        self::upsertProgrammeRows($pdo, $courseId, self::tgtProgrammes());
    }

    public static function ensurePgtProgrammes(PDO $pdo, int $courseId): void
    {
        self::upsertProgrammeRows($pdo, $courseId, self::pgtProgrammes());
    }

    public static function purgeGenericTgtPivots(PDO $pdo, int $courseId): void
    {
        $pdo->prepare(
            'DELETE scs FROM sub_course_subjects scs
             INNER JOIN sub_courses sc ON sc.id = scs.sub_course_id
             INNER JOIN courses c ON c.id = sc.course_id
             WHERE c.id = ? AND sc.slug = ?'
        )->execute([$courseId, 'tgt']);
    }

    public static function purgeGenericPgtPivots(PDO $pdo, int $courseId): void
    {
        $pdo->prepare(
            'DELETE scs FROM sub_course_subjects scs
             INNER JOIN sub_courses sc ON sc.id = scs.sub_course_id
             INNER JOIN courses c ON c.id = sc.course_id
             WHERE c.id = ? AND sc.slug = ?'
        )->execute([$courseId, 'pgt']);
    }

    private static function syncProgrammePivotsFor(
        PDO $pdo,
        int $courseId,
        array $programmes,
        callable $paperRowsFn,
        callable $slugFn
    ): void {
        $scIdSt = $pdo->prepare('SELECT id FROM sub_courses WHERE course_id = ? AND slug = ? LIMIT 1');
        $insPivot = $pdo->prepare(
            'INSERT INTO sub_course_subjects (sub_course_id, subject_id, sort_order, status, is_active) VALUES (?,?,?,?,?)'
        );
        $subIdSt = $pdo->prepare('SELECT id FROM subjects WHERE slug = ? LIMIT 1');

        foreach ($programmes as $prog) {
            $scIdSt->execute([$courseId, $prog['slug']]);
            $scid = (int) $scIdSt->fetchColumn();
            if ($scid < 1) {
                continue;
            }
            $managedSlugs = [];
            foreach ($paperRowsFn($prog) as $row) {
                $managedSlugs[] = $slugFn($prog['subject_key'], $row[0]);
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
            foreach ($paperRowsFn($prog) as $row) {
                $slug = $slugFn($prog['subject_key'], $row[0]);
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

    public static function syncTgtProgrammePivots(PDO $pdo, int $courseId): void
    {
        self::syncProgrammePivotsFor(
            $pdo,
            $courseId,
            self::tgtProgrammes(),
            static fn (array $prog): array => self::tgtPaperRowsForProgramme($prog),
            static fn (string $key, string $paper): string => self::tgtSubjectSlug($key, $paper)
        );
    }

    public static function syncPgtProgrammePivots(PDO $pdo, int $courseId): void
    {
        self::syncProgrammePivotsFor(
            $pdo,
            $courseId,
            self::pgtProgrammes(),
            static fn (array $prog): array => self::pgtPaperRowsForProgramme($prog),
            static fn (string $key, string $paper): string => self::pgtSubjectSlug($key, $paper)
        );
    }

    private static function resolveCourseIdBySlug(PDO $pdo, string $slug): int
    {
        foreach (['courses', 'main_courses'] as $table) {
            try {
                $st = $pdo->prepare("SELECT id FROM `{$table}` WHERE slug = ? LIMIT 1");
                $st->execute([$slug]);
                $id = (int) ($st->fetchColumn() ?: 0);
                if ($id > 0) {
                    return $id;
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return 0;
    }

    /**
     * AP DSC — PGT Commerce & Economics only: paper subjects, programme rows, pivots, plans.
     *
     * @return int ap-dsc course id
     */
    public static function seedPgtCommerceEconomicsApDsc(PDO $pdo): int
    {
        $courseId = self::resolveCourseIdBySlug($pdo, 'ap-dsc');
        if ($courseId < 1) {
            throw new RuntimeException('Course ap-dsc not found.');
        }

        self::ensureSubjects($pdo);
        $subset = array_values(array_filter(
            self::pgtProgrammes(),
            static fn (array $p): bool => in_array($p['subject_key'], ['commerce', 'economics'], true)
        ));
        self::upsertProgrammeRows($pdo, $courseId, $subset);
        self::syncProgrammePivotsFor(
            $pdo,
            $courseId,
            $subset,
            static fn (array $prog): array => self::pgtPaperRowsForProgramme($prog),
            static fn (string $key, string $paper): string => self::pgtSubjectSlug($key, $paper)
        );
        self::ensurePlansForSlugPattern($pdo, $courseId, 'ap-pgt-commerce');
        self::ensurePlansForSlugPattern($pdo, $courseId, 'ap-pgt-economics');

        return $courseId;
    }

    public static function ensurePlansForSlugPattern(PDO $pdo, int $courseId, string $likePattern): void
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
            'SELECT sc.id FROM sub_courses sc
             JOIN courses c ON c.id = sc.course_id
             WHERE c.id = ? AND sc.slug LIKE ?'
        );
        $st->execute([$courseId, $likePattern]);
        while ($rid = $st->fetchColumn()) {
            $scid = (int) $rid;
            foreach ($defaults as [$code, $label, $price, $months]) {
                $plans->execute([$scid, $code, $label, $price, $months]);
            }
        }
    }

    /** AP DSC only — TGT/PGT structured programmes + pivots + plans. */
    public static function standardizeApDscTgtPgt(PDO $pdo): void
    {
        $st = $pdo->prepare('SELECT id FROM courses WHERE slug = ? LIMIT 1');
        $st->execute(['ap-dsc']);
        $courseId = (int) ($st->fetchColumn() ?: 0);
        if ($courseId < 1) {
            throw new RuntimeException('Course ap-dsc not found.');
        }

        self::ensureSubjects($pdo);
        self::ensureTgtProgrammes($pdo, $courseId);
        self::ensurePgtProgrammes($pdo, $courseId);
        self::purgeGenericTgtPivots($pdo, $courseId);
        self::purgeGenericPgtPivots($pdo, $courseId);
        self::syncTgtProgrammePivots($pdo, $courseId);
        self::syncPgtProgrammePivots($pdo, $courseId);
        self::ensurePlansForSlugPattern($pdo, $courseId, 'ap-tgt-%');
        self::ensurePlansForSlugPattern($pdo, $courseId, 'ap-pgt-%');
    }

    /**
     * Remove AP DSC–style tier markers accidentally tied to TET/CTET courses (must stay distinct).
     */
    public static function purgeErroneousTierMarkersFromNonDsc(PDO $pdo): void
    {
        try {
            $pdo->prepare(
                'DELETE t FROM tests t
                 INNER JOIN courses c ON c.id = t.course_id
                 WHERE t.slug LIKE ? AND c.slug IN (\'ap-tet\',\'ts-tet\',\'ctet\')'
            )->execute(['sa5-ap-dsc-%']);
        } catch (Throwable $e) {
            // FK constraints or legacy schema — keep migration resilient
        }
    }

    /** Seed SaExamPattern rows for every pivoted ap-tgt / ap-pgt paper under AP DSC. */
    public static function seedAllExamPatterns(PDO $pdo): void
    {
        require_once __DIR__ . '/SaExamPattern.php';

        $st = $pdo->prepare('SELECT id FROM courses WHERE slug = ? LIMIT 1');
        $st->execute(['ap-dsc']);
        $courseId = (int) ($st->fetchColumn() ?: 0);
        if ($courseId < 1) {
            return;
        }

        foreach (['ap-tgt-%', 'ap-pgt-%'] as $pat) {
            $subSt = $pdo->prepare(
                'SELECT s.id, s.slug FROM subjects s
                 INNER JOIN sub_course_subjects scs ON scs.subject_id = s.id
                 INNER JOIN sub_courses sc ON sc.id = scs.sub_course_id
                 INNER JOIN courses c ON c.id = sc.course_id
                 WHERE c.id = ? AND sc.slug LIKE ? ORDER BY sc.sort_order, sc.slug, scs.sort_order'
            );
            $subSt->execute([$courseId, $pat]);
            while ($row = $subSt->fetch(PDO::FETCH_ASSOC)) {
                SaExamPattern::seedForApDscSubject($pdo, $courseId, (int) $row['id'], (string) $row['slug']);
            }
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
