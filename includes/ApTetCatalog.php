<?php

declare(strict_types=1);

/**
 * AP TET — structured programmes under course slug `ap-tet` (Paper I, Paper I Special, Paper II tracks).
 * Aligns with DSC-style programme workspace (five-tier tests, Live/Draft via admin_api).
 */
final class ApTetCatalog
{
    /** @return list<string> */
    public static function structuredProgrammeSlugs(): array
    {
        return array_column(self::programmes(), 'slug');
    }

    /**
     * @return list<array{slug:string,name:string,name_te:string,sort:int}>
     */
    public static function programmes(): array
    {
        return [
            ['slug' => 'ap-tet-paper-1', 'name' => 'AP TET Paper I', 'name_te' => 'ఏపీ టెట్ పేపర్ 1', 'sort' => 10],
            ['slug' => 'ap-tet-paper-1-special', 'name' => 'AP TET Paper I Special', 'name_te' => 'ఏపీ టెట్ పేపర్ 1 స్పెషల్', 'sort' => 20],
            ['slug' => 'ap-tet-p2-telugu', 'name' => 'AP TET Paper II — Telugu', 'name_te' => 'ఏపీ టెట్ పేపర్ 2 తెలుగు', 'sort' => 30],
            ['slug' => 'ap-tet-p2-english', 'name' => 'AP TET Paper II — English', 'name_te' => 'ఏపీ టెట్ పేపర్ 2 ఇంగ్లీష్', 'sort' => 40],
            ['slug' => 'ap-tet-p2-hindi', 'name' => 'AP TET Paper II — Hindi', 'name_te' => 'ఏపీ టెట్ పేపర్ 2 హిందీ', 'sort' => 50],
            ['slug' => 'ap-tet-p2-maths-science', 'name' => 'AP TET Paper II — Maths & Science', 'name_te' => 'ఏపీ టెట్ పేపర్ 2 మ్యాథ్స్ & సైన్స్', 'sort' => 60],
            ['slug' => 'ap-tet-p2-social', 'name' => 'AP TET Paper II — Social Studies', 'name_te' => 'ఏపీ టెట్ పేపర్ 2 సోషల్ స్టడీస్', 'sort' => 70],
        ];
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:int}>
     */
    public static function paper1SubjectRows(): array
    {
        return [
            ['child-development-pedagogy', 'Child Development & Pedagogy', 'బాల వికాసం మరియు బోధనాశాస్త్రం', 1],
            ['language-i', 'Language I', 'భాషా I', 2],
            ['language-ii-english', 'Language II (English)', 'భాషా II (ఇంగ్లీష్)', 3],
            ['mathematics', 'Mathematics', 'గణితం', 4],
            ['environmental-studies', 'Environmental Studies', 'పర్యావరణ అధ్యయనాలు', 5],
        ];
    }

    /**
     * Mirror Paper I + explicit Special Education methodology paper.
     *
     * @return list<array{0:string,1:string,2:string,3:int}>
     */
    public static function paper1SpecialSubjectRows(): array
    {
        return [
            ['child-development-pedagogy', 'Child Development & Pedagogy (Special Education)', 'బాల వికాసం & బోధనాశాస్త్రం (ప్రత్యేక విద్య)', 1],
            ['language-i', 'Language I (Special Education)', 'భాషా I (ప్రత్యేక విద్య)', 2],
            ['language-ii-english', 'Language II — English (Special Education)', 'భాషా II — ఇంగ్లీష్ (ప్రత్యేక విద్య)', 3],
            ['mathematics', 'Mathematics (Special Education)', 'గణితం (ప్రత్యేక విద్య)', 4],
            ['environmental-studies', 'Environmental Studies (Special Education)', 'పర్యావరణ అధ్యయనాలు (ప్రత్యేక విద్య)', 5],
            ['special-education-methodology', 'Special Education Methodology', 'ప్రత్యేక విద్యా పద్ధతిశాస్త్రం', 6],
        ];
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:int}>
     */
    public static function paper2SubjectRows(string $labelEn, string $labelTe): array
    {
        return [
            ['child-development-pedagogy', 'Child Development & Pedagogy', 'బాల వికాసం మరియు బోధనాశాస్త్రం', 1],
            ['language-i', 'Language I', 'భాషా I', 2],
            ['language-ii-english', 'Language II (English)', 'భాషా II (ఇంగ్లీష్)', 3],
            ['content', 'Content (' . $labelEn . ')', 'కంటెంట్ (' . $labelTe . ')', 4],
            ['methodology', 'Methodology (' . $labelEn . ')', 'మెథడాలజీ (' . $labelTe . ')', 5],
        ];
    }

    public static function paper1SubjectSlug(string $suffix): string
    {
        return 'ap-tet-p1-' . $suffix;
    }

    public static function paper1SpecialSubjectSlug(string $suffix): string
    {
        return 'ap-tet-p1s-' . $suffix;
    }

    public static function paper2SubjectSlug(string $trackSlug, string $suffix): string
    {
        return 'ap-tet-p2-' . $trackSlug . '-' . $suffix;
    }

    public static function ensureSubjects(PDO $pdo): void
    {
        $ins = $pdo->prepare(
            'INSERT INTO subjects (course_id, category_id, slug, name, name_te, description, sort_order, status, is_active)
             VALUES (NULL, NULL, ?, ?, ?, NULL, ?, 1, 1)
             ON DUPLICATE KEY UPDATE name = VALUES(name), name_te = VALUES(name_te), sort_order = VALUES(sort_order), status = 1, is_active = 1'
        );

        foreach (self::paper1SubjectRows() as $row) {
            $ins->execute([self::paper1SubjectSlug($row[0]), $row[1], $row[2], $row[3]]);
        }
        foreach (self::paper1SpecialSubjectRows() as $row) {
            $ins->execute([self::paper1SpecialSubjectSlug($row[0]), $row[1], $row[2], $row[3]]);
        }

        $tracks = [
            ['telugu', 'Telugu', 'తెలుగు'],
            ['english', 'English', 'ఇంగ్లీష్'],
            ['hindi', 'Hindi', 'హిందీ'],
            ['maths-science', 'Maths & Science', 'మ్యాథ్స్ & సైన్స్'],
            ['social', 'Social Studies', 'సామాజిక శాస్త్రం'],
        ];
        foreach ($tracks as [$tslug, $len, $lte]) {
            foreach (self::paper2SubjectRows($len, $lte) as $row) {
                $ins->execute([self::paper2SubjectSlug($tslug, $row[0]), $row[1], $row[2], $row[3]]);
            }
        }
    }

    public static function ensureProgrammes(PDO $pdo, int $courseId): void
    {
        foreach (self::programmes() as $prog) {
            self::upsertProgrammeRow($pdo, $courseId, $prog);
        }
    }

    /** @param array{slug:string,name:string,name_te:string,sort:int} $prog */
    private static function upsertProgrammeRow(PDO $pdo, int $courseId, array $prog): void
    {
        $hasSt = self::columnExists($pdo, 'sub_courses', 'status');
        $hasTe = self::columnExists($pdo, 'sub_courses', 'name_te');

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

    public static function syncAllProgrammePivots(PDO $pdo, int $courseId): void
    {
        self::syncPivotForSlug($pdo, $courseId, 'ap-tet-paper-1', self::paper1SubjectRows(), static fn (array $r): string => self::paper1SubjectSlug($r[0]));
        self::syncPivotForSlug($pdo, $courseId, 'ap-tet-paper-1-special', self::paper1SpecialSubjectRows(), static fn (array $r): string => self::paper1SpecialSubjectSlug($r[0]));

        $tracks = [
            ['ap-tet-p2-telugu', 'telugu', 'Telugu', 'తెలుగు'],
            ['ap-tet-p2-english', 'english', 'English', 'ఇంగ్లీష్'],
            ['ap-tet-p2-hindi', 'hindi', 'Hindi', 'హిందీ'],
            ['ap-tet-p2-maths-science', 'maths-science', 'Maths & Science', 'మ్యాథ్స్ & సైన్స్'],
            ['ap-tet-p2-social', 'social', 'Social Studies', 'సామాజిక శాస్త్రం'],
        ];
        foreach ($tracks as $t) {
            [$scSlug, $trackKey, $len, $lte] = $t;
            $slugFn = static function (array $r) use ($trackKey): string {
                return self::paper2SubjectSlug($trackKey, $r[0]);
            };
            self::syncPivotForSlug(
                $pdo,
                $courseId,
                $scSlug,
                self::paper2SubjectRows($len, $lte),
                $slugFn
            );
        }
    }

    /**
     * @param list<array{0:string,1:string,2:string,3:int}> $rows
     * @param callable(array):string $slugFn
     */
    private static function syncPivotForSlug(PDO $pdo, int $courseId, string $subCourseSlug, array $rows, callable $slugFn): void
    {
        $scIdSt = $pdo->prepare('SELECT id FROM sub_courses WHERE course_id = ? AND slug = ? LIMIT 1');
        $scIdSt->execute([$courseId, $subCourseSlug]);
        $scid = (int) $scIdSt->fetchColumn();
        if ($scid < 1) {
            return;
        }

        $managedSlugs = [];
        foreach ($rows as $row) {
            $managedSlugs[] = $slugFn($row);
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

        $insPivot = $pdo->prepare(
            'INSERT INTO sub_course_subjects (sub_course_id, subject_id, sort_order, status, is_active) VALUES (?,?,?,?,?)'
        );
        $subIdSt = $pdo->prepare('SELECT id FROM subjects WHERE slug = ? LIMIT 1');

        $ord = 0;
        foreach ($rows as $row) {
            $slug = $slugFn($row);
            $subIdSt->execute([$slug]);
            $sid = $subIdSt->fetchColumn();
            if (!$sid) {
                continue;
            }
            $insPivot->execute([$scid, (int) $sid, $ord, 1, 1]);
            ++$ord;
        }
    }

    public static function ensurePlansForProgrammes(PDO $pdo, int $courseId): void
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
        $st->execute([$courseId, 'ap-tet-%']);
        while ($rid = $st->fetchColumn()) {
            $scid = (int) $rid;
            foreach ($defaults as [$code, $label, $price, $months]) {
                $plans->execute([$scid, $code, $label, $price, $months]);
            }
        }
    }

    /** Full AP TET sync (idempotent). */
    public static function standardizeApTet(PDO $pdo): void
    {
        $courseId = self::resolveApTetCourseId($pdo);
        if ($courseId < 1) {
            throw new RuntimeException('Course ap-tet not found.');
        }

        self::ensureSubjects($pdo);
        self::ensureProgrammes($pdo, $courseId);
        self::syncAllProgrammePivots($pdo, $courseId);
        self::ensurePlansForProgrammes($pdo, $courseId);
    }

    /**
     * Draft skeleton tests per tier for AP TET pivoted subjects (matches TsDscCatalog behaviour).
     */
    public static function ensureSkeletonFiveTierTests(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'tests') || !self::columnExists($pdo, 'tests', 'test_type')) {
            return;
        }

        $courseId = self::resolveApTetCourseId($pdo);
        if ($courseId < 1) {
            return;
        }

        $hasStatus = self::columnExists($pdo, 'tests', 'status');
        $hasTitleTe = self::columnExists($pdo, 'tests', 'title_te');
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
             WHERE c.slug = \'ap-tet\''
        );
        $subSt->execute();

        while ($sub = $subSt->fetch(PDO::FETCH_ASSOC)) {
            $sid = (int) $sub['id'];
            foreach ($tiers as $tier) {
                $slug = 'skel-' . $sid . '-' . $tier;
                $title = ($sub['name'] ?? 'Subject') . ' · ' . $tierTe[$tier] . ' (skeleton)';
                $tte = $tierTe[$tier] . ' · స్కెలటన్';

                $exists = $pdo->prepare('SELECT id FROM tests WHERE course_id = ? AND slug = ? LIMIT 1');
                $exists->execute([$courseId, $slug]);
                if ($exists->fetchColumn()) {
                    continue;
                }

                if ($hasStatus && $hasTitleTe) {
                    $pdo->prepare(
                        'INSERT INTO tests (course_id, subject_id, slug, title, title_te, test_type, duration_mins, total_questions, total_marks, status, is_active)
                         VALUES (?,?,?,?,?,?,60,1,1,0,0)'
                    )->execute([$courseId, $sid, $slug, $title, $tte, $tier]);
                } elseif ($hasStatus) {
                    $pdo->prepare(
                        'INSERT INTO tests (course_id, subject_id, slug, title, test_type, duration_mins, total_questions, total_marks, status, is_active)
                         VALUES (?,?,?,?,?,60,1,1,0,0)'
                    )->execute([$courseId, $sid, $slug, $title, $tier]);
                } elseif ($hasTitleTe) {
                    $pdo->prepare(
                        'INSERT INTO tests (course_id, subject_id, slug, title, title_te, test_type, duration_mins, total_questions, total_marks, is_active)
                         VALUES (?,?,?,?,?,?,60,1,1,0)'
                    )->execute([$courseId, $sid, $slug, $title, $tte, $tier]);
                } else {
                    $pdo->prepare(
                        'INSERT INTO tests (course_id, subject_id, slug, title, test_type, duration_mins, total_questions, total_marks, is_active)
                         VALUES (?,?,?,?,?,60,1,1,0)'
                    )->execute([$courseId, $sid, $slug, $title, $tier]);
                }
            }
        }
    }

    private static function resolveApTetCourseId(PDO $pdo): int
    {
        foreach (['courses', 'main_courses'] as $table) {
            try {
                $st = $pdo->prepare("SELECT id FROM `{$table}` WHERE slug = 'ap-tet' LIMIT 1");
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
