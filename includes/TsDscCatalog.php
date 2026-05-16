<?php

declare(strict_types=1);

/**
 * TS DSC — mirrors AP DSC structured programmes: ts-sa-*, ts-tgt-*, ts-pgt-*.
 * TGT includes English Proficiency Test paper (same ordering as AP TGT).
 * Does not modify AP TET / TS TET / CTET flows.
 */
final class TsDscCatalog
{
    /** Slugs treated as standard programmes under TS DSC (whitelist / resets). */
    public static function structuredProgrammeSlugs(): array
    {
        return array_merge(
            array_column(self::tsSaProgrammes(), 'slug'),
            array_column(self::tsTgtProgrammes(), 'slug'),
            array_column(self::tsPgtProgrammes(), 'slug'),
        );
    }

    /** @return list<array{slug:string,name:string,name_te:string,sort:int,subject_key:string,label_en:string,content_te:string}> */
    public static function tsSaProgrammes(): array
    {
        return [
            ['slug' => 'ts-sa-telugu', 'name' => 'TS DSC SA Telugu', 'name_te' => 'టీఎస్ డీఎస్‌సీ ఎస్‌ఏ తెలుగు', 'sort' => 22, 'subject_key' => 'telugu', 'label_en' => 'Telugu', 'content_te' => 'తెలుగు'],
            ['slug' => 'ts-sa-hindi', 'name' => 'TS DSC SA Hindi', 'name_te' => 'టీఎస్ డీఎస్‌సీ ఎస్‌ఏ హిందీ', 'sort' => 23, 'subject_key' => 'hindi', 'label_en' => 'Hindi', 'content_te' => 'హిందీ'],
            ['slug' => 'ts-sa-english', 'name' => 'TS DSC SA English', 'name_te' => 'టీఎస్ డీఎస్‌సీ ఎస్‌ఏ ఇంగ్లీష్', 'sort' => 24, 'subject_key' => 'english', 'label_en' => 'English', 'content_te' => 'ఇంగ్లీష్'],
            ['slug' => 'ts-sa-maths', 'name' => 'TS DSC SA Mathematics', 'name_te' => 'టీఎస్ డీఎస్‌సీ ఎస్‌ఏ గణితం', 'sort' => 25, 'subject_key' => 'maths', 'label_en' => 'Mathematics', 'content_te' => 'గణితం'],
            ['slug' => 'ts-sa-physical-science', 'name' => 'TS DSC SA Physical Science', 'name_te' => 'టీఎస్ డీఎస్‌సీ ఎస్‌ఏ భౌతిక శాస్త్రం', 'sort' => 26, 'subject_key' => 'physical-science', 'label_en' => 'Physical Science', 'content_te' => 'భౌతిక శాస్త్రం'],
            ['slug' => 'ts-sa-biological-science', 'name' => 'TS DSC SA Biological Science', 'name_te' => 'టీఎస్ డీఎస్‌సీ ఎస్‌ఏ జీవ శాస్త్రం', 'sort' => 27, 'subject_key' => 'biological-science', 'label_en' => 'Biological Science', 'content_te' => 'జీవ శాస్త్రం'],
            ['slug' => 'ts-sa-social-studies', 'name' => 'TS DSC SA Social Studies', 'name_te' => 'టీఎస్ డీఎస్‌సీ ఎస్‌ఏ సామాజిక శాస్త్రం', 'sort' => 28, 'subject_key' => 'social-studies', 'label_en' => 'Social Studies', 'content_te' => 'సామాజిక శాస్త్రం'],
        ];
    }

    /** @return list<array{slug:string,name:string,name_te:string,sort:int,subject_key:string,label_en:string,content_te:string}> */
    public static function tsTgtProgrammes(): array
    {
        return [
            ['slug' => 'ts-tgt-telugu', 'name' => 'TS DSC TGT Telugu', 'name_te' => 'టీఎస్ డీఎస్‌సీ టీజీటీ తెలుగు', 'sort' => 41, 'subject_key' => 'telugu', 'label_en' => 'Telugu', 'content_te' => 'తెలుగు'],
            ['slug' => 'ts-tgt-english', 'name' => 'TS DSC TGT English', 'name_te' => 'టీఎస్ డీఎస్‌సీ టీజీటీ ఇంగ్లీష్', 'sort' => 42, 'subject_key' => 'english', 'label_en' => 'English', 'content_te' => 'ఇంగ్లీష్'],
            ['slug' => 'ts-tgt-hindi', 'name' => 'TS DSC TGT Hindi', 'name_te' => 'టీఎస్ డీఎస్‌సీ టీజీటీ హిందీ', 'sort' => 43, 'subject_key' => 'hindi', 'label_en' => 'Hindi', 'content_te' => 'హిందీ'],
            ['slug' => 'ts-tgt-maths', 'name' => 'TS DSC TGT Mathematics', 'name_te' => 'టీఎస్ డీఎస్‌సీ టీజీటీ గణితం', 'sort' => 44, 'subject_key' => 'maths', 'label_en' => 'Mathematics', 'content_te' => 'గణితం'],
            ['slug' => 'ts-tgt-physical-science', 'name' => 'TS DSC TGT Physical Science', 'name_te' => 'టీఎస్ డీఎస్‌సీ టీజీటీ భౌతిక శాస్త్రం', 'sort' => 45, 'subject_key' => 'physical-science', 'label_en' => 'Physical Science', 'content_te' => 'భౌతిక శాస్త్రం'],
            ['slug' => 'ts-tgt-biological-science', 'name' => 'TS DSC TGT Biological Science', 'name_te' => 'టీఎస్ డీఎస్‌సీ టీజీటీ జీవ శాస్త్రం', 'sort' => 46, 'subject_key' => 'biological-science', 'label_en' => 'Biological Science', 'content_te' => 'జీవ శాస్త్రం'],
            ['slug' => 'ts-tgt-social-studies', 'name' => 'TS DSC TGT Social Studies', 'name_te' => 'టీఎస్ డీఎస్‌సీ టీజీటీ సామాజిక శాస్త్రం', 'sort' => 47, 'subject_key' => 'social-studies', 'label_en' => 'Social Studies', 'content_te' => 'సామాజిక శాస్త్రం'],
        ];
    }

    /** @return list<array{slug:string,name:string,name_te:string,sort:int,subject_key:string,label_en:string,content_te:string}> */
    public static function tsPgtProgrammes(): array
    {
        return [
            ['slug' => 'ts-pgt-telugu', 'name' => 'TS DSC PGT Telugu', 'name_te' => 'టీఎస్ డీఎస్‌సీ పీజీటీ తెలుగు', 'sort' => 51, 'subject_key' => 'telugu', 'label_en' => 'Telugu', 'content_te' => 'తెలుగు'],
            ['slug' => 'ts-pgt-english', 'name' => 'TS DSC PGT English', 'name_te' => 'టీఎస్ డీఎస్‌సీ పీజీటీ ఇంగ్లీష్', 'sort' => 52, 'subject_key' => 'english', 'label_en' => 'English', 'content_te' => 'ఇంగ్లీష్'],
            ['slug' => 'ts-pgt-hindi', 'name' => 'TS DSC PGT Hindi', 'name_te' => 'టీఎస్ డీఎస్‌సీ పీజీటీ హిందీ', 'sort' => 53, 'subject_key' => 'hindi', 'label_en' => 'Hindi', 'content_te' => 'హిందీ'],
            ['slug' => 'ts-pgt-maths', 'name' => 'TS DSC PGT Mathematics', 'name_te' => 'టీఎస్ డీఎస్‌సీ పీజీటీ గణితం', 'sort' => 54, 'subject_key' => 'maths', 'label_en' => 'Mathematics', 'content_te' => 'గణితం'],
            ['slug' => 'ts-pgt-physical-science', 'name' => 'TS DSC PGT Physical Science', 'name_te' => 'టీఎస్ డీఎస్‌సీ పీజీటీ భౌతిక శాస్త్రం', 'sort' => 55, 'subject_key' => 'physical-science', 'label_en' => 'Physical Science', 'content_te' => 'భౌతిక శాస్త్రం'],
            ['slug' => 'ts-pgt-biological-science', 'name' => 'TS DSC PGT Biological Science', 'name_te' => 'టీఎస్ డీఎస్‌సీ పీజీటీ జీవ శాస్త్రం', 'sort' => 56, 'subject_key' => 'biological-science', 'label_en' => 'Biological Science', 'content_te' => 'జీవ శాస్త్రం'],
            ['slug' => 'ts-pgt-social-studies', 'name' => 'TS DSC PGT Social Studies', 'name_te' => 'టీఎస్ డీఎస్‌సీ పీజీటీ సామాజిక శాస్త్రం', 'sort' => 57, 'subject_key' => 'social-studies', 'label_en' => 'Social Studies', 'content_te' => 'సామాజిక శాస్త్రం'],
            ['slug' => 'ts-pgt-commerce', 'name' => 'TS DSC PGT Commerce', 'name_te' => 'టీఎస్ డీఎస్‌సీ పీజీటీ వాణిజ్యం', 'sort' => 58, 'subject_key' => 'commerce', 'label_en' => 'Commerce', 'content_te' => 'వాణిజ్యం'],
            ['slug' => 'ts-pgt-economics', 'name' => 'TS DSC PGT Economics', 'name_te' => 'టీఎస్ డీఎస్‌సీ పీజీటీ ఆర్థిక శాస్త్రం', 'sort' => 59, 'subject_key' => 'economics', 'label_en' => 'Economics', 'content_te' => 'ఆర్థిక శాస్త్రం'],
        ];
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:int}>
     */
    public static function saPaperRowsForProgramme(array $prog): array
    {
        $le = $prog['label_en'];
        $ct = $prog['content_te'];

        return [
            ['gk-current-affairs', 'GK & Current Affairs', 'జీకే & కరెంట్ అఫైర్స్', 1],
            ['perspective-education', 'Perspective in Education', 'పర్ స్పెక్టివ్ ఇన్ ఎడ్యుకేషన్', 2],
            ['classroom-psychology', 'Classroom Psychology', 'క్లాస్ రూమ్ సైకాలజీ', 3],
            ['content', 'Content (' . $le . ')', 'కంటెంట్ (' . $ct . ')', 4],
            ['methodology', 'Methodology (' . $le . ')', 'మెథడాలజీ (' . $ct . ')', 5],
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
            ['gk-current-affairs', 'GK & Current Affairs', 'జీకే & కరెంట్ అఫైర్స్', 1],
            ['perspective-education', 'Perspective in Education', 'పర్ స్పెక్టివ్ ఇన్ ఎడ్యుకేషన్', 2],
            ['classroom-psychology', 'Classroom Psychology', 'క్లాస్ రూమ్ సైకాలజీ', 3],
            ['english-proficiency-test', 'English Proficiency Test', 'ఇంగ్లీష్ ప్రొఫిషియన్సీ టెస్ట్', 4],
            ['content', 'Content (' . $le . ')', 'కంటెంట్ (' . $ct . ')', 5],
            ['methodology', 'Methodology (' . $le . ')', 'మెథడాలజీ (' . $ct . ')', 6],
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
            ['gk-current-affairs', 'GK & Current Affairs', 'జీకే & కరెంట్ అఫైర్స్', 1],
            ['perspective-education', 'Perspective in Education', 'పర్ స్పెక్టివ్ ఇన్ ఎడ్యుకేషన్', 2],
            ['classroom-psychology', 'Classroom Psychology', 'క్లాస్ రూమ్ సైకాలజీ', 3],
            ['content', 'Content (' . $le . ')', 'కంటెంట్ (' . $ct . ')', 4],
            ['methodology', 'Methodology (' . $le . ')', 'మెథడాలజీ (' . $ct . ')', 5],
        ];
    }

    public static function tsSaSubjectSlug(string $subjectKey, string $paperSuffix): string
    {
        return 'ts-sa-' . $subjectKey . '-' . $paperSuffix;
    }

    public static function tsTgtSubjectSlug(string $subjectKey, string $paperSuffix): string
    {
        return 'ts-tgt-' . $subjectKey . '-' . $paperSuffix;
    }

    public static function tsPgtSubjectSlug(string $subjectKey, string $paperSuffix): string
    {
        return 'ts-pgt-' . $subjectKey . '-' . $paperSuffix;
    }

    public static function ensureSubjects(PDO $pdo): void
    {
        $ins = $pdo->prepare(
            'INSERT INTO subjects (course_id, category_id, slug, name, name_te, description, sort_order, status, is_active)
             VALUES (NULL, NULL, ?, ?, ?, NULL, ?, 1, 1)
             ON DUPLICATE KEY UPDATE name = VALUES(name), name_te = VALUES(name_te), sort_order = VALUES(sort_order), status = 1, is_active = 1'
        );
        foreach (self::tsSaProgrammes() as $prog) {
            foreach (self::saPaperRowsForProgramme($prog) as $row) {
                $slug = self::tsSaSubjectSlug($prog['subject_key'], $row[0]);
                $ins->execute([$slug, $row[1], $row[2], $row[3]]);
            }
        }
        foreach (self::tsTgtProgrammes() as $prog) {
            foreach (self::tgtPaperRowsForProgramme($prog) as $row) {
                $slug = self::tsTgtSubjectSlug($prog['subject_key'], $row[0]);
                $ins->execute([$slug, $row[1], $row[2], $row[3]]);
            }
        }
        foreach (self::tsPgtProgrammes() as $prog) {
            foreach (self::pgtPaperRowsForProgramme($prog) as $row) {
                $slug = self::tsPgtSubjectSlug($prog['subject_key'], $row[0]);
                $ins->execute([$slug, $row[1], $row[2], $row[3]]);
            }
        }
    }

    /** Upsert ts-sa-* programme rows under TS DSC main course. */
    public static function ensureSaProgrammes(PDO $pdo, int $courseId): void
    {
        self::upsertProgrammeRows($pdo, $courseId, self::tsSaProgrammes());
    }

    public static function ensureTgtProgrammes(PDO $pdo, int $courseId): void
    {
        self::upsertProgrammeRows($pdo, $courseId, self::tsTgtProgrammes());
    }

    public static function ensurePgtProgrammes(PDO $pdo, int $courseId): void
    {
        self::upsertProgrammeRows($pdo, $courseId, self::tsPgtProgrammes());
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

    public static function purgeGenericSchoolAssistantPivots(PDO $pdo, int $courseId): void
    {
        $pdo->prepare(
            'DELETE scs FROM sub_course_subjects scs
             INNER JOIN sub_courses sc ON sc.id = scs.sub_course_id
             INNER JOIN courses c ON c.id = sc.course_id
             WHERE c.id = ? AND sc.slug = ?'
        )->execute([$courseId, 'school-assistant']);
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

    /** Rebuild pivots for ts-sa-* programmes. */
    public static function syncSaProgrammePivots(PDO $pdo, int $courseId): void
    {
        self::syncProgrammePivotsFor(
            $pdo,
            $courseId,
            self::tsSaProgrammes(),
            static fn (array $prog): array => self::saPaperRowsForProgramme($prog),
            static fn (string $key, string $paper): string => self::tsSaSubjectSlug($key, $paper)
        );
    }

    public static function syncTgtProgrammePivots(PDO $pdo, int $courseId): void
    {
        self::syncProgrammePivotsFor(
            $pdo,
            $courseId,
            self::tsTgtProgrammes(),
            static fn (array $prog): array => self::tgtPaperRowsForProgramme($prog),
            static fn (string $key, string $paper): string => self::tsTgtSubjectSlug($key, $paper)
        );
    }

    public static function syncPgtProgrammePivots(PDO $pdo, int $courseId): void
    {
        self::syncProgrammePivotsFor(
            $pdo,
            $courseId,
            self::tsPgtProgrammes(),
            static fn (array $prog): array => self::pgtPaperRowsForProgramme($prog),
            static fn (string $key, string $paper): string => self::tsPgtSubjectSlug($key, $paper)
        );
    }

    /**
     * @param list<array<string,mixed>> $programmes
     * @param callable(array):list<array{0:string,1:string,2:string,3:int}> $paperRowsFn
     * @param callable(string,string):string $slugFn
     */
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

    public static function ensurePlansForTsStructuredProgrammes(PDO $pdo, int $courseId): void
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
        foreach (['ts-sa-%', 'ts-tgt-%', 'ts-pgt-%'] as $likePattern) {
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
    }

    /**
     * One placeholder test per tier per subject linked under TS DSC (draft skeleton).
     * Idempotent via stable slug skel-{subject_id}-{tier}.
     */
    public static function ensureSkeletonFiveTierTests(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'tests') || !self::columnExists($pdo, 'tests', 'test_type')) {
            return;
        }

        $courseId = self::resolveTsDscCourseId($pdo);
        if ($courseId < 1) {
            return;
        }

        $hasStatus = self::columnExists($pdo, 'tests', 'status');
        $tiers = ['topic', 'division', 'revision', 'grand', 'model'];
        $tierTe = [
            'topic' => 'టాపిక్ టెస్ట్‌లు',
            'division' => 'డివిజన్ టెస్ట్‌లు',
            'revision' => 'రివిజన్ టెస్ట్‌లు',
            'grand' => 'గ్రాండ్ టెస్ట్‌లు',
            'model' => 'మోడల్ పేపర్లు',
        ];

        $subSt = $pdo->prepare(
            'SELECT DISTINCT s.id, s.slug, s.name FROM subjects s
             INNER JOIN sub_course_subjects scs ON scs.subject_id = s.id
             INNER JOIN sub_courses sc ON sc.id = scs.sub_course_id
             INNER JOIN courses c ON c.id = sc.course_id
             WHERE c.slug = \'ts-dsc\''
        );
        $subSt->execute();

        $hasTitleTe = self::columnExists($pdo, 'tests', 'title_te');

        while ($sub = $subSt->fetch(PDO::FETCH_ASSOC)) {
            $sid = (int) $sub['id'];
            foreach ($tiers as $tier) {
                $slug = 'skel-' . $sid . '-' . $tier;
                $title = ($sub['name'] ?? 'Subject') . ' · ' . $tierTe[$tier] . ' (skeleton)';
                $titleTe = $tierTe[$tier] . ' · స్కెలటన్';

                $exists = $pdo->prepare('SELECT id FROM tests WHERE course_id = ? AND slug = ? LIMIT 1');
                $exists->execute([$courseId, $slug]);
                if ($exists->fetchColumn()) {
                    continue;
                }

                if ($hasStatus && $hasTitleTe) {
                    $pdo->prepare(
                        'INSERT INTO tests (course_id, subject_id, slug, title, title_te, test_type, duration_mins, total_questions, total_marks, status, is_active)
                         VALUES (?,?,?,?,?,?,60,1,1,0,0)'
                    )->execute([$courseId, $sid, $slug, $title, $titleTe, $tier]);
                } elseif ($hasStatus) {
                    $pdo->prepare(
                        'INSERT INTO tests (course_id, subject_id, slug, title, test_type, duration_mins, total_questions, total_marks, status, is_active)
                         VALUES (?,?,?,?,?,60,1,1,0,0)'
                    )->execute([$courseId, $sid, $slug, $title, $tier]);
                } elseif ($hasTitleTe) {
                    $pdo->prepare(
                        'INSERT INTO tests (course_id, subject_id, slug, title, title_te, test_type, duration_mins, total_questions, total_marks, is_active)
                         VALUES (?,?,?,?,?,?,60,1,1,0)'
                    )->execute([$courseId, $sid, $slug, $title, $titleTe, $tier]);
                } else {
                    $pdo->prepare(
                        'INSERT INTO tests (course_id, subject_id, slug, title, test_type, duration_mins, total_questions, total_marks, is_active)
                         VALUES (?,?,?,?,?,60,1,1,0)'
                    )->execute([$courseId, $sid, $slug, $title, $tier]);
                }
            }
        }
    }

    /**
     * TS DSC — PGT Commerce & Economics only: paper subjects, programme rows, pivots.
     * For pricing slabs use {@see ApDscTgtPgtCatalog::ensurePlansForSlugPattern} with ts-dsc course id.
     *
     * @return int ts-dsc course id
     */
    public static function seedPgtCommerceEconomicsTsDsc(PDO $pdo): int
    {
        $courseId = self::resolveTsDscCourseId($pdo);
        if ($courseId < 1) {
            throw new RuntimeException('Course ts-dsc not found (courses / main_courses).');
        }

        self::ensureSubjects($pdo);
        $subset = array_values(array_filter(
            self::tsPgtProgrammes(),
            static fn (array $p): bool => in_array($p['subject_key'], ['commerce', 'economics'], true)
        ));
        self::upsertProgrammeRows($pdo, $courseId, $subset);
        self::syncProgrammePivotsFor(
            $pdo,
            $courseId,
            $subset,
            static fn (array $prog): array => self::pgtPaperRowsForProgramme($prog),
            static fn (string $key, string $paper): string => self::tsPgtSubjectSlug($key, $paper)
        );

        return $courseId;
    }

    /** Full TS DSC standardisation (subjects, programmes, pivots, plans). */
    public static function standardizeTsDsc(PDO $pdo): void
    {
        $courseId = self::resolveTsDscCourseId($pdo);
        if ($courseId < 1) {
            throw new RuntimeException('Course ts-dsc not found (courses / main_courses).');
        }

        self::ensureSubjects($pdo);
        self::ensureSaProgrammes($pdo, $courseId);
        self::ensureTgtProgrammes($pdo, $courseId);
        self::ensurePgtProgrammes($pdo, $courseId);
        self::purgeGenericSchoolAssistantPivots($pdo, $courseId);
        self::purgeGenericTgtPivots($pdo, $courseId);
        self::purgeGenericPgtPivots($pdo, $courseId);
        self::syncSaProgrammePivots($pdo, $courseId);
        self::syncTgtProgrammePivots($pdo, $courseId);
        self::syncPgtProgrammePivots($pdo, $courseId);
        self::ensurePlansForTsStructuredProgrammes($pdo, $courseId);
    }

    private static function resolveTsDscCourseId(PDO $pdo): int
    {
        foreach (['courses', 'main_courses'] as $table) {
            try {
                $st = $pdo->prepare("SELECT id FROM `{$table}` WHERE slug = 'ts-dsc' LIMIT 1");
                $st->execute();
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
