<?php

declare(strict_types=1);

/**
 * Seeds the 5-tier exam skeleton (topic → division → revision → grand → model)
 * for AP DSC structured papers (`ap-sa-*`, `ap-tgt-*`, `ap-pgt-*` under course `ap-dsc` only).
 * Idempotent per course+subject. Not used for TET/CTET programmes.
 *
 * Topic / topic-test mapping follows the "Acharyas book" 15-topic, 28-test, 20-grand layout.
 * All created tests default to Draft (is_active=0, status=0) if status column exists.
 */
final class SaExamPattern
{
    /** @var array<int, list<int>> topicNum => list of global test indices 1..28 */
    private const TOPIC_TEST_MAP = [
        1 => [1],
        2 => [2],
        3 => [3, 4],
        4 => [5, 6],
        5 => [7, 8, 9],
        6 => [10],
        7 => [11, 12],
        8 => [13, 14],
        9 => [15, 16],
        10 => [17, 18],
        11 => [19, 20],
        12 => [21, 22],
        13 => [23, 24],
        14 => [25, 26],
        15 => [27, 28],
    ];

    /** Grand test num => list of topic-test indices (1..28) */
    private const GRAND_BUNDLES = [
        1 => [1],
        2 => [2],
        3 => [2],
        4 => [3],
        5 => [4],
        6 => [5],
        7 => [6],
        8 => [7, 8, 9],
        9 => [10],
        10 => [11],
        11 => [12],
        12 => [13],
        13 => [14],
        14 => [15],
        15 => [16, 17, 18, 19, 20],
        16 => [21, 22],
        17 => [23],
        18 => [24],
        19 => [25, 26],
        20 => [27, 28],
    ];

    /** @param array<string,string> $subjectSlugToAbbr e.g. telugu => tel */
    public static function subjectAbbrev(string $subjectSlug): string
    {
        $structured = self::abbrevStructuredApTgtSubject($subjectSlug)
            ?? self::abbrevStructuredApPgtSubject($subjectSlug)
            ?? self::abbrevStructuredApSaSubject($subjectSlug);
        if ($structured !== null) {
            return $structured;
        }

        $map = [
            'telugu' => 'tel',
            'hindi' => 'hin',
            'english' => 'eng',
            'maths' => 'mat',
            'physical-science' => 'phy',
            'biological-science' => 'bio',
            'social-studies' => 'soc',
        ];

        return $map[$subjectSlug] ?? preg_replace('/[^a-z0-9]+/', '', substr($subjectSlug, 0, 6));
    }

    /** @return array<string,string> */
    private static function dscTrackAbbrevMap(): array
    {
        return [
            'telugu' => 'tl',
            'hindi' => 'hin',
            'english' => 'eng',
            'maths' => 'mat',
            'physical-science' => 'phy',
            'biological-science' => 'bio',
            'social-studies' => 'soc',
            'commerce' => 'com',
            'economics' => 'eco',
        ];
    }

    /** @return array<string,string> */
    private static function dscPaperAbbrevMap(bool $includeEpt): array
    {
        $p = [
            'gk-current-affairs' => 'gk',
            'perspective-education' => 'pe',
            'classroom-psychology' => 'cp',
            'content' => 'co',
            'methodology' => 'md',
        ];
        if ($includeEpt) {
            $p['english-proficiency-test'] = 'ept';
        }

        return $p;
    }

    private static function abbrevStructuredApTgtSubject(string $subjectSlug): ?string
    {
        if (
            !preg_match(
                '/^ap-tgt-(telugu|hindi|english|maths|physical-science|biological-science|social-studies)-(gk-current-affairs|perspective-education|classroom-psychology|english-proficiency-test|content|methodology)$/',
                $subjectSlug,
                $m
            )
        ) {
            return null;
        }
        $tb = self::dscTrackAbbrevMap();
        $pb = self::dscPaperAbbrevMap(true);

        return 'tgt' . ($tb[$m[1]] ?? '') . ($pb[$m[2]] ?? '');
    }

    private static function abbrevStructuredApPgtSubject(string $subjectSlug): ?string
    {
        if (
            !preg_match(
                '/^ap-pgt-(telugu|hindi|english|maths|physical-science|biological-science|social-studies|commerce|economics)-(gk-current-affairs|perspective-education|classroom-psychology|content|methodology)$/',
                $subjectSlug,
                $m
            )
        ) {
            return null;
        }
        $tb = self::dscTrackAbbrevMap();
        $pb = self::dscPaperAbbrevMap(false);

        return 'pgt' . ($tb[$m[1]] ?? '') . ($pb[$m[2]] ?? '');
    }

    /** Stable abbreviations for ap-sa-* paper slugs (avoids marker collisions across specialisations). */
    private static function abbrevStructuredApSaSubject(string $subjectSlug): ?string
    {
        if (
            !preg_match(
                '/^ap-sa-(telugu|hindi|english|maths|physical-science|biological-science|social-studies)-(gk-current-affairs|perspective-education|classroom-psychology|content|methodology)$/',
                $subjectSlug,
                $m
            )
        ) {
            return null;
        }

        $trackAbbr = [
            'telugu' => 'tl',
            'hindi' => 'hin',
            'english' => 'eng',
            'maths' => 'mat',
            'physical-science' => 'phy',
            'biological-science' => 'bio',
            'social-studies' => 'soc',
        ];
        $paperAbbr = [
            'gk-current-affairs' => 'gk',
            'perspective-education' => 'pe',
            'classroom-psychology' => 'cp',
            'content' => 'co',
            'methodology' => 'md',
        ];

        return ($trackAbbr[$m[1]] ?? '') . ($paperAbbr[$m[2]] ?? '');
    }

    /**
     * Seed one AP DSC structured paper (`ap-sa-*`, `ap-tgt-*`, `ap-pgt-*`). Safe to run multiple times (skips if marker test exists).
     *
     * @return bool True when inserts ran; false when already seeded (skipped).
     */
    public static function seedForApDscSubject(PDO $pdo, int $courseId, int $subjectId, string $subjectSlug): bool
    {
        require_once dirname(__DIR__) . '/models/SchemaHelper.php';

        if (!SchemaHelper::hasTable('test_bundle_items') || !SchemaHelper::columnExists('tests', 'topic_id')) {
            throw new RuntimeException('Run migrate_exam_hierarchy.php first.');
        }

        $abbr = self::subjectAbbrev($subjectSlug);
        $marker = "sa5-ap-dsc-{$abbr}-t01";
        $chk = $pdo->prepare('SELECT id FROM tests WHERE course_id = ? AND subject_id = ? AND slug = ? LIMIT 1');
        $chk->execute([$courseId, $subjectId, $marker]);
        if ($chk->fetch()) {
            return false;
        }

        $topicsTable = SchemaHelper::topicsTable();
        $hasTopicStatus = SchemaHelper::columnExists($topicsTable, 'status');
        $hasTestStatus = SchemaHelper::testsHasStatus();
        $draftActive = 0;
        $draftStatus = 0;

        $pdo->beginTransaction();
        try {
            $topicIds = [];
            for ($tn = 1; $tn <= 15; ++$tn) {
                $slug = sprintf('sa5-ap-dsc-%s-topic-%02d', $abbr, $tn);
                $insT = $pdo->prepare(
                    "INSERT INTO `{$topicsTable}` (subject_id, slug, title, title_te, summary, duration_mins, sort_order, is_free_preview"
                    . ($hasTopicStatus ? ', status' : '')
                    . ") VALUES (?,?,?,?,?,?,?,?"
                    . ($hasTopicStatus ? ',?' : '')
                    . ')'
                );
                $title = 'Topic ' . $tn;
                $params = [$subjectId, $slug, $title, null, null, 30, $tn, 0];
                if ($hasTopicStatus) {
                    $params[] = 1;
                }
                $insT->execute($params);
                $topicIds[$tn] = (int) $pdo->lastInsertId();
            }

            /** @var array<int,int> testNum 1..28 => tests.id */
            $testIdsByNum = [];

            $insTest = self::buildInsertTestSql($pdo, $hasTestStatus);
            foreach (range(1, 28) as $testNum) {
                $topicNum = self::topicNumForTestIndex($testNum);
                $topicId = $topicIds[$topicNum] ?? null;
                $slug = sprintf('sa5-ap-dsc-%s-t%02d', $abbr, $testNum);
                $title = 'Test ' . $testNum;
                $params = [
                    $courseId, $subjectId, $topicId, $slug, $title, null,
                    'topic', null, 60, 50, 50, 25, 0.25, null,
                ];
                if ($hasTestStatus) {
                    $params[] = $draftStatus;
                    $params[] = $draftActive;
                } else {
                    $params[] = $draftActive;
                }
                $insTest->execute($params);
                $testIdsByNum[$testNum] = (int) $pdo->lastInsertId();
            }

            // Division tests (bundle topic tests)
            $divRanges = [
                1 => range(1, 4),
                2 => range(5, 10),
                3 => range(11, 16),
                4 => range(17, 22),
                5 => range(23, 28),
            ];
            $divBundleIds = [];
            foreach ($divRanges as $dn => $testNums) {
                $slug = "sa5-ap-dsc-{$abbr}-div-{$dn}";
                $tid = self::insertBundleTest($pdo, $courseId, $subjectId, $slug, "Division {$dn}", 'division', $hasTestStatus, $draftStatus, $draftActive);
                $divBundleIds[$dn] = $tid;
                self::insertBundleItems($pdo, $tid, array_map(static fn (int $n) => $testIdsByNum[$n], $testNums));
            }

            // Revision tests (bundle division tests)
            $revGroups = [
                1 => [1, 2],
                2 => [3, 4],
                3 => [5],
            ];
            $revBundleIds = [];
            foreach ($revGroups as $rn => $divNums) {
                $slug = "sa5-ap-dsc-{$abbr}-rev-{$rn}";
                $tid = self::insertBundleTest($pdo, $courseId, $subjectId, $slug, "Revision {$rn}", 'revision', $hasTestStatus, $draftStatus, $draftActive);
                $revBundleIds[$rn] = $tid;
                $comp = [];
                foreach ($divNums as $d) {
                    $comp[] = $divBundleIds[$d];
                }
                self::insertBundleItems($pdo, $tid, $comp);
            }

            // Grand tests
            foreach (self::GRAND_BUNDLES as $gn => $testNums) {
                $slug = "sa5-ap-dsc-{$abbr}-grand-{$gn}";
                $tid = self::insertBundleTest($pdo, $courseId, $subjectId, $slug, 'Grand test ' . $gn, 'grand', $hasTestStatus, $draftStatus, $draftActive);
                self::insertBundleItems($pdo, $tid, array_map(static fn (int $n) => $testIdsByNum[$n], $testNums));
            }

            // Model papers (shells — enable after adding questions)
            foreach ([1, 2, 3] as $mn) {
                $slug = "sa5-ap-dsc-{$abbr}-model-{$mn}";
                self::insertBundleTest($pdo, $courseId, $subjectId, $slug, 'Model paper ' . $mn, 'model', $hasTestStatus, $draftStatus, $draftActive);
            }

            $pdo->commit();

            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function topicNumForTestIndex(int $testIndex): int
    {
        foreach (self::TOPIC_TEST_MAP as $topicNum => $tests) {
            if (in_array($testIndex, $tests, true)) {
                return $topicNum;
            }
        }

        return 1;
    }

    private static function buildInsertTestSql(PDO $pdo, bool $hasTestStatus): PDOStatement
    {
        if ($hasTestStatus) {
            return $pdo->prepare(
                'INSERT INTO tests (course_id, subject_id, topic_id, slug, title, title_te, test_type, division_label, duration_mins, total_questions, total_marks, passing_marks, negative_marking, package_id, status, is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
        }

        return $pdo->prepare(
            'INSERT INTO tests (course_id, subject_id, topic_id, slug, title, title_te, test_type, division_label, duration_mins, total_questions, total_marks, passing_marks, negative_marking, package_id, is_active)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
    }

    private static function insertBundleTest(
        PDO $pdo,
        int $courseId,
        int $subjectId,
        string $slug,
        string $title,
        string $type,
        bool $hasTestStatus,
        int $draftStatus,
        int $draftActive
    ): int {
        if ($hasTestStatus) {
            $st = $pdo->prepare(
                'INSERT INTO tests (course_id, subject_id, topic_id, slug, title, title_te, test_type, division_label, duration_mins, total_questions, total_marks, passing_marks, negative_marking, package_id, status, is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $st->execute([
                $courseId, $subjectId, null, $slug, $title, null, $type, null, 90, 100, 100, 40, 0.25, null, $draftStatus, $draftActive,
            ]);
        } else {
            $st = $pdo->prepare(
                'INSERT INTO tests (course_id, subject_id, topic_id, slug, title, title_te, test_type, division_label, duration_mins, total_questions, total_marks, passing_marks, negative_marking, package_id, is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $st->execute([
                $courseId, $subjectId, null, $slug, $title, null, $type, null, 90, 100, 100, 40, 0.25, null, $draftActive,
            ]);
        }

        return (int) $pdo->lastInsertId();
    }

    /** @param list<int> $componentTestIds */
    private static function insertBundleItems(PDO $pdo, int $bundleId, array $componentTestIds): void
    {
        $ins = $pdo->prepare('INSERT INTO test_bundle_items (bundle_test_id, component_test_id, sort_order) VALUES (?,?,?)');
        $ord = 0;
        foreach ($componentTestIds as $cid) {
            $ins->execute([$bundleId, $cid, $ord]);
            ++$ord;
        }
    }
}
