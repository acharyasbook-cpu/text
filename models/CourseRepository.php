<?php

declare(strict_types=1);

class CourseRepository
{
    public function allActive(): array
    {
        SchemaHelper::ensureCoursesViewIncludesImagePath();
        $table = SchemaHelper::publicMainCoursesTable();
        $w = SchemaHelper::coursesHasStatus() ? 'COALESCE(status, is_active, 1) = 1' : 'is_active = 1';
        $order = SchemaHelper::sqlOrderBySort('sort_order', 'id');
        $stmt = db()->query("SELECT * FROM `{$table}` WHERE {$w} ORDER BY {$order}");

        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        SchemaHelper::ensureCoursesViewIncludesImagePath();
        $table = SchemaHelper::publicMainCoursesTable();
        $w = SchemaHelper::coursesHasStatus() ? 'COALESCE(status, is_active, 1) = 1' : 'is_active = 1';
        $stmt = db()->prepare("SELECT * FROM `{$table}` WHERE slug = ? AND {$w}");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** Level-2 rows under a main course */
    public function subCoursesForCourse(int $courseId): array
    {
        if (!SchemaHelper::hierarchyFourTier()) {
            return [];
        }
        $courseId = SchemaHelper::resolveCatalogCourseId($courseId);
        $w = SchemaHelper::columnExists('sub_courses', 'status')
            ? 'COALESCE(status, is_active, 1) = 1'
            : 'is_active = 1';
        $order = SchemaHelper::sqlOrderBySort('sort_order', 'id');
        $stmt = db()->prepare("SELECT * FROM sub_courses WHERE course_id = ? AND {$w} ORDER BY {$order}");
        $stmt->execute([$courseId]);

        return $stmt->fetchAll();
    }

    public function findSubCourseBySlugs(string $courseSlug, string $subSlug): ?array
    {
        if (!SchemaHelper::hierarchyFourTier()) {
            return null;
        }
        $cLive = SchemaHelper::coursesHasStatus() ? 'COALESCE(c.status, c.is_active, 1) = 1' : 'c.is_active = 1';
        $scLive = SchemaHelper::columnExists('sub_courses', 'status')
            ? 'COALESCE(sc.status, sc.is_active, 1) = 1'
            : 'sc.is_active = 1';
        $sql = "SELECT sc.*, c.slug AS course_slug, c.name AS course_name, c.name_te AS course_name_te
            FROM sub_courses sc JOIN courses c ON c.id = sc.course_id
            WHERE c.slug = ? AND sc.slug = ? AND {$cLive} AND {$scLive}";
        $stmt = db()->prepare($sql);
        $stmt->execute([$courseSlug, $subSlug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** Subjects linked to a sub-course (pivot), live chain */
    public function subjectsForSubCourse(int $subCourseId): array
    {
        if (!SchemaHelper::hierarchyFourTier()) {
            return [];
        }
        $sLive = SchemaHelper::subjectsHasStatus() ? 's.status = 1' : 's.is_active = 1';
        $pcs = SchemaHelper::columnExists('sub_course_subjects', 'status')
            ? 'scs.status = 1 AND scs.is_active = 1' : 'scs.is_active = 1';
        $sql = "SELECT s.*, sc.slug AS sub_course_slug, c.slug AS course_slug, c.name AS course_name, c.name_te AS course_name_te
            FROM subjects s
            JOIN sub_course_subjects scs ON scs.subject_id = s.id AND {$pcs}
            JOIN sub_courses sc ON sc.id = scs.sub_course_id
            JOIN courses c ON c.id = sc.course_id
            WHERE scs.sub_course_id = ? AND {$sLive}";
        $stmt = db()->prepare($sql);
        $stmt->execute([$subCourseId]);

        return $stmt->fetchAll();
    }

    public function subjectsForCourse(int $courseId): array
    {
        if (SchemaHelper::hierarchyFourTier()) {
            $sLive = SchemaHelper::subjectsHasStatus() ? 's.status = 1' : 's.is_active = 1';
            $pcs = SchemaHelper::columnExists('sub_course_subjects', 'status')
                ? 'scs.status = 1 AND scs.is_active = 1' : 'scs.is_active = 1';
            $scCol = SchemaHelper::columnExists('sub_courses', 'status') ? 'sc.status = 1 AND sc.is_active = 1' : 'sc.is_active = 1';

            $sql = "SELECT DISTINCT s.*, sc.slug AS sub_course_slug, sc.name AS sub_course_name
                FROM subjects s
                JOIN sub_course_subjects scs ON scs.subject_id = s.id AND {$pcs}
                JOIN sub_courses sc ON sc.id = scs.sub_course_id AND {$scCol}
                WHERE sc.course_id = ? AND {$sLive}
                ORDER BY sc.sort_order ASC, s.sort_order ASC, s.id ASC";
            $stmt = db()->prepare($sql);
            $stmt->execute([$courseId]);

            return $stmt->fetchAll();
        }

        $sLive = SchemaHelper::subjectsHasStatus() ? 's.status = 1' : 's.is_active = 1';

        if (!SchemaHelper::hasTable('course_categories')) {
            $stmt = db()->prepare(
                "SELECT s.*, NULL AS category_slug, NULL AS category_name FROM subjects s
                WHERE s.course_id = ? AND {$sLive} ORDER BY s.sort_order ASC, s.id ASC"
            );
            $stmt->execute([$courseId]);

            return $stmt->fetchAll();
        }

        $sql = "SELECT s.*, cc.slug AS category_slug, cc.name AS category_name
            FROM subjects s
            LEFT JOIN course_categories cc ON cc.id = s.category_id AND cc.status = 1
            WHERE s.course_id = ? AND {$sLive}
              AND (s.category_id IS NULL OR cc.id IS NOT NULL)
            ORDER BY cc.sort_order, s.sort_order, s.name";
        $stmt = db()->prepare($sql);
        $stmt->execute([$courseId]);

        return $stmt->fetchAll();
    }

    public function subjectsGroupedByCategory(int $courseId): array
    {
        $subjects = $this->subjectsForCourse($courseId);
        $grouped = [];
        foreach ($subjects as $s) {
            if (SchemaHelper::hierarchyFourTier() && !empty($s['sub_course_slug'])) {
                $key = 'sc-' . ($s['sub_course_slug'] ?? '_');
                $label = $s['sub_course_name'] ?? 'Overview';
            } else {
                $key = $s['category_slug'] ?? '_uncat';
                $label = $s['category_name'] ?? 'General';
            }
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['label' => $label, 'subjects' => []];
            }
            $grouped[$key]['subjects'][] = $s;
        }

        return $grouped;
    }

    /** Group by sub-course (Level 2) for course detail page — preferred four-tier UX */
    public function subjectsGroupedBySubCourse(int $courseId): array
    {
        if (!SchemaHelper::hierarchyFourTier()) {
            return $this->subjectsGroupedByCategory($courseId);
        }
        $out = [];
        foreach ($this->subCoursesForCourse($courseId) as $sc) {
            $out[$sc['slug']] = [
                'label' => $sc['name'],
                'sub_course' => $sc,
                'subjects' => $this->subjectsForSubCourse((int) $sc['id']),
            ];
        }

        return $out;
    }

    public function activeCategories(int $courseId): array
    {
        if (!SchemaHelper::hasTable('course_categories')) {
            return [];
        }
        $stmt = db()->prepare('SELECT * FROM course_categories WHERE course_id = ? AND status = 1 ORDER BY sort_order, name');
        $stmt->execute([$courseId]);

        return $stmt->fetchAll();
    }

    public function findSubject(int $subjectId): ?array
    {
        $sLive = SchemaHelper::subjectsHasStatus() ? 's.status = 1' : 's.is_active = 1';
        $stmt = db()->prepare("SELECT s.* FROM subjects s WHERE s.id = ? AND {$sLive}");
        $stmt->execute([$subjectId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** Live subject global slug (+ course context when four-tier) */
    public function findSubjectBySlugs(string $courseSlug, string $subjectSlug): ?array
    {
        if (SchemaHelper::hierarchyFourTier()) {
            return $this->findSubjectByPath($courseSlug, null, $subjectSlug);
        }

        $cLive = SchemaHelper::coursesHasStatus() ? 'c.status = 1' : 'c.is_active = 1';
        $sLive = SchemaHelper::subjectsHasStatus() ? 's.status = 1' : 's.is_active = 1';

        if (!SchemaHelper::hasTable('course_categories')) {
            $stmt = db()->prepare("SELECT s.*, c.slug AS course_slug, c.name AS course_name, c.name_te AS course_name_te
                FROM subjects s JOIN courses c ON c.id = s.course_id
                WHERE c.slug = ? AND s.slug = ? AND {$cLive} AND {$sLive}");
            $stmt->execute([$courseSlug, $subjectSlug]);
            $row = $stmt->fetch();

            return $row ?: null;
        }

        $sql = 'SELECT s.*, c.slug AS course_slug, c.name AS course_name, c.name_te AS course_name_te
            FROM subjects s
            JOIN courses c ON c.id = s.course_id
            LEFT JOIN course_categories cc ON cc.id = s.category_id
            WHERE c.slug = ? AND s.slug = ? AND ' . $cLive . ' AND ' . $sLive . '
              AND (s.category_id IS NULL OR (cc.id IS NOT NULL AND cc.status = 1))';
        $stmt = db()->prepare($sql);
        $stmt->execute([$courseSlug, $subjectSlug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** course + optional sub-course + subject slug */
    public function findSubjectByPath(?string $courseSlug, ?string $subSlug, string $subjectSlug): ?array
    {
        $sLive = SchemaHelper::subjectsHasStatus() ? 's.status = 1' : 's.is_active = 1';
        if (!SchemaHelper::hierarchyFourTier()) {
            if ($courseSlug) {
                return $this->findSubjectBySlugs($courseSlug, $subjectSlug);
            }

            return null;
        }
        $cLive = SchemaHelper::coursesHasStatus() ? 'c.status = 1' : 'c.is_active = 1';
        $scLive = SchemaHelper::columnExists('sub_courses', 'status')
            ? 'sc.status = 1 AND sc.is_active = 1' : 'sc.is_active = 1';
        $pcs = SchemaHelper::columnExists('sub_course_subjects', 'status')
            ? 'scs.status = 1 AND scs.is_active = 1' : 'scs.is_active = 1';

        if ($courseSlug !== null && $courseSlug !== '' && $subSlug !== null && $subSlug !== '') {
            $sql = "SELECT s.*, sc.slug AS sub_course_slug, sc.name AS sub_course_name, c.slug AS course_slug, c.name AS course_name, c.name_te AS course_name_te
                FROM subjects s
                JOIN sub_course_subjects scs ON scs.subject_id = s.id AND {$pcs}
                JOIN sub_courses sc ON sc.id = scs.sub_course_id AND {$scLive}
                JOIN courses c ON c.id = sc.course_id AND {$cLive}
                WHERE c.slug = ? AND sc.slug = ? AND s.slug = ? AND {$sLive}
                LIMIT 1";
            $stmt = db()->prepare($sql);
            $stmt->execute([$courseSlug, $subSlug, $subjectSlug]);
            $row = $stmt->fetch();

            return $row ?: null;
        }

        if ($courseSlug !== null && $courseSlug !== '') {
            $sql = "SELECT s.*, sc.slug AS sub_course_slug, sc.name AS sub_course_name, c.slug AS course_slug, c.name AS course_name, c.name_te AS course_name_te
                FROM subjects s
                JOIN sub_course_subjects scs ON scs.subject_id = s.id AND {$pcs}
                JOIN sub_courses sc ON sc.id = scs.sub_course_id AND {$scLive}
                JOIN courses c ON c.id = sc.course_id AND {$cLive}
                WHERE c.slug = ? AND s.slug = ? AND {$sLive}
                ORDER BY sc.sort_order, sc.id
                LIMIT 1";
            $stmt = db()->prepare($sql);
            $stmt->execute([$courseSlug, $subjectSlug]);
            $row = $stmt->fetch();

            return $row ?: null;
        }

        $sql = "SELECT s.*, sc.slug AS sub_course_slug, sc.name AS sub_course_name, c.slug AS course_slug, c.name AS course_name, c.name_te AS course_name_te
            FROM subjects s
            JOIN sub_course_subjects scs ON scs.subject_id = s.id AND {$pcs}
            JOIN sub_courses sc ON sc.id = scs.sub_course_id AND {$scLive}
            JOIN courses c ON c.id = sc.course_id AND {$cLive}
            WHERE s.slug = ? AND {$sLive}
            LIMIT 1";
        $stmt = db()->prepare($sql);
        $stmt->execute([$subjectSlug]);

        return $stmt->fetch() ?: null;
    }

    public function modulesForSubject(int $subjectId): array
    {
        if (!SchemaHelper::hasTable('subject_modules')) {
            return [];
        }
        $stmt = db()->prepare('SELECT * FROM subject_modules WHERE subject_id = ? AND status = 1 ORDER BY sort_order, id');
        $stmt->execute([$subjectId]);

        return $stmt->fetchAll();
    }

    public function topicsForSubject(int $subjectId): array
    {
        $t = SchemaHelper::topicsTable();
        $live = SchemaHelper::columnExists($t, 'status') ? ' AND status = 1' : '';
        $order = SchemaHelper::sqlOrderBySort('sort_order', 'id');
        $stmt = db()->prepare("SELECT * FROM `{$t}` WHERE subject_id = ?{$live} ORDER BY {$order}");
        $stmt->execute([$subjectId]);

        return $stmt->fetchAll();
    }

    /** @deprecated use topicsForSubject */
    public function lessonsForSubject(int $subjectId): array
    {
        return $this->topicsForSubject($subjectId);
    }

    public function materialsForSubject(int $subjectId): array
    {
        $stmt = db()->prepare('SELECT * FROM study_materials WHERE subject_id = ? ORDER BY sort_order, title');
        $stmt->execute([$subjectId]);

        return $stmt->fetchAll();
    }

    public function materialsForTopic(int $topicId): array
    {
        if (!SchemaHelper::columnExists('study_materials', 'topic_id')) {
            return [];
        }
        $stmt = db()->prepare('SELECT * FROM study_materials WHERE topic_id = ? ORDER BY sort_order, title');
        $stmt->execute([$topicId]);

        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function subTopicsForTopic(int $topicId): array
    {
        if ($topicId < 1 || !SchemaHelper::hasTable('sub_topics')) {
            return [];
        }
        $live = SchemaHelper::columnExists('sub_topics', 'status') ? ' AND status = 1' : '';
        $st = db()->prepare(
            "SELECT id, sub_topic_name, sub_topic_name_te, sub_notes_content, question_count
             FROM sub_topics WHERE topic_id = ?{$live} ORDER BY sort_order, id"
        );
        $st->execute([$topicId]);

        return $st->fetchAll();
    }

    /** Platform / external exams attached to a topic (Live only). */
    public function examsForTopic(int $topicId): array
    {
        if (!SchemaHelper::topicExamsEnabled()) {
            return [];
        }
        $live = SchemaHelper::columnExists('exams', 'status')
            ? 'status = 1 AND is_active = 1'
            : 'is_active = 1';
        $stmt = db()->prepare("SELECT * FROM exams WHERE topic_id = ? AND {$live} ORDER BY sort_order, id");
        $stmt->execute([$topicId]);

        return $stmt->fetchAll();
    }

    public function testById(int $testId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM tests WHERE id = ? LIMIT 1');
        $stmt->execute([$testId]);

        return $stmt->fetch() ?: null;
    }

    public function findTopicBySlug(int $subjectId, string $slug): ?array
    {
        if ($subjectId < 1 || $slug === '') {
            return null;
        }
        $t = SchemaHelper::topicsTable();
        $st = db()->prepare("SELECT * FROM `{$t}` WHERE subject_id=? AND slug=? LIMIT 1");
        $st->execute([$subjectId, $slug]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public function topicNotesForDisplay(array $topic): string
    {
        if (SchemaHelper::topicNotesEnabledColumn() && empty($topic['notes_enabled'])) {
            return '';
        }
        if (!empty($topic['has_sub_topics']) && SchemaHelper::hasTable('sub_topics')) {
            $bindId = (int) ($topic['notes_bind_sub_topic_id'] ?? 0);
            $subs = $this->subTopicsForTopic((int) $topic['id']);
            foreach ($subs as $st) {
                if ($bindId > 0 && (int) $st['id'] === $bindId) {
                    return trim((string) ($st['sub_notes_content'] ?? ''));
                }
            }
            if ($subs) {
                return trim((string) ($subs[0]['sub_notes_content'] ?? ''));
            }

            return '';
        }

        return trim((string) ($topic['notes_content'] ?? $topic['content'] ?? ''));
    }

    public function topicMcqForDisplay(array $topic): string
    {
        if (SchemaHelper::topicMcqContentEnabled()) {
            return trim((string) ($topic['mcq_content'] ?? ''));
        }

        return '';
    }

    public function topicNotesPlaceholder(array $topic): string
    {
        $te = trim((string) ($topic['title_te'] ?? ''));
        $en = trim((string) ($topic['title'] ?? 'Topic'));
        $label = $te !== '' ? $te : $en;

        return "📘 {$label}\n\nఈ టాపిక్ కోసం సంక్షిప్త స్టడీ మెటీరియల్ త్వరలో జోడించబడుతుంది. ముఖ్య అంశాలు, నిర్వచనాలు మరియు పరీక్షకు సంబంధించిన కీ పాయింట్లు ఇక్కడ కనిపిస్తాయి.";
    }

    /**
     * @param list<array<string,mixed>> $topics
     * @return list<array<string,mixed>>
     */
    public function enrichTopicsForSubjectWorkspace(
        array $topics,
        array $subject,
        bool $programmeHasAccess
    ): array {
        $courseSlug = (string) ($subject['course_slug'] ?? '');
        $subSlug = $subject['sub_course_slug'] ?? null;
        $subjectSlug = (string) ($subject['slug'] ?? '');
        $out = [];

        foreach ($topics as $topic) {
            $locked = !$programmeHasAccess && empty($topic['is_free_preview']);
            $notesText = $this->topicNotesForDisplay($topic);
            $mcqText = $this->topicMcqForDisplay($topic);
            $suite = $this->examSuiteTestsForTopic((int) ($topic['id'] ?? 0));
            $notesPreview = $notesText !== '' ? $notesText : $this->topicNotesPlaceholder($topic);
            $hasNotes = !$locked;
            $hasExam = (!$locked && ($mcqText !== '' || $suite !== []));

            $out[] = $topic + [
                'workspace_locked' => $locked,
                'has_notes' => $hasNotes,
                'has_exam' => $hasExam,
                'notes_preview' => mb_substr(strip_tags($notesPreview), 0, 160),
                'notes_url' => $hasNotes
                    ? public_topic_notes_url($courseSlug, $subSlug !== '' ? (string) $subSlug : null, $subjectSlug, (string) $topic['slug'])
                    : null,
                'exam_suite' => $suite,
                'exam_return_path' => public_subject_exam_return_path(
                    $courseSlug,
                    $subSlug !== '' ? (string) $subSlug : null,
                    $subjectSlug
                ),
                'mcq_preview' => $mcqText !== '' ? mb_substr($mcqText, 0, 120) : '',
            ];
        }

        return $out;
    }

    /** @return list<array<string,mixed>> */
    public function examSuiteTestsForTopic(int $topicId): array
    {
        if ($topicId < 1 || !SchemaHelper::topicExamSuiteEnabled()) {
            return [];
        }
        $reqCol = SchemaHelper::columnExists('topic_exam_suite', 'is_required') ? 'COALESCE(tes.is_required, tes.is_enabled, 1)' : 'COALESCE(tes.is_enabled, 1)';
        $st = db()->prepare(
            "SELECT tes.*, t.slug AS test_slug, t.title AS test_title, t.title_te AS test_title_te
             FROM topic_exam_suite tes
             LEFT JOIN tests t ON t.id = tes.test_id
             WHERE tes.topic_id = ? AND tes.sub_topic_id IS NULL
               AND {$reqCol} = 1 AND COALESCE(tes.is_enabled, 1) = 1
             ORDER BY tes.sort_order, tes.id"
        );
        $st->execute([$topicId]);

        return $st->fetchAll();
    }

    public function plansForSubCourse(int $subCourseId): array
    {
        if (!SchemaHelper::hasTable('sub_course_plans')) {
            return [];
        }
        $live = SchemaHelper::columnExists('sub_course_plans', 'status') ? 'status = 1 AND is_active = 1' : 'is_active = 1';
        $stmt = db()->prepare("SELECT * FROM sub_course_plans WHERE sub_course_id = ? AND {$live} ORDER BY FIELD(plan_code,'6_months','1_year','until_exam')");
        $stmt->execute([$subCourseId]);

        return $stmt->fetchAll();
    }

    /** @return array{subjects:int,topics:int,tests:int,materials:int} */
    public function programmeStatsForSubCourse(int $subCourseId): array
    {
        $stats = ['subjects' => 0, 'topics' => 0, 'tests' => 0, 'materials' => 0];
        if ($subCourseId < 1) {
            return $stats;
        }

        $subjects = $this->subjectsForSubCourse($subCourseId);
        $stats['subjects'] = count($subjects);
        $subjectIds = array_map(static fn (array $s): int => (int) $s['id'], $subjects);
        if ($subjectIds === []) {
            return $stats;
        }

        $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
        $tcol = SchemaHelper::topicsTable();
        $tLive = SchemaHelper::columnExists($tcol, 'status') ? ' AND status = 1' : '';
        $st = db()->prepare("SELECT COUNT(*) FROM `{$tcol}` WHERE subject_id IN ({$placeholders}){$tLive}");
        $st->execute($subjectIds);
        $stats['topics'] = (int) $st->fetchColumn();

        $testLive = SchemaHelper::testsHasStatus() ? 'status = 1 AND is_active = 1' : 'is_active = 1';
        $st2 = db()->prepare("SELECT COUNT(*) FROM tests WHERE subject_id IN ({$placeholders}) AND {$testLive}");
        $st2->execute($subjectIds);
        $stats['tests'] = (int) $st2->fetchColumn();

        if (SchemaHelper::hasTable('study_materials')) {
            $fk = SchemaHelper::materialsTopicFkColumn();
            $st3 = db()->prepare("SELECT COUNT(*) FROM study_materials WHERE subject_id IN ({$placeholders})");
            $st3->execute($subjectIds);
            $stats['materials'] = (int) $st3->fetchColumn();
        }

        return $stats;
    }

    /** @return array{topics:int,tests:int,materials:int} */
    public function programmeStatsForSubject(int $subjectId): array
    {
        $stats = ['topics' => 0, 'tests' => 0, 'materials' => 0];
        if ($subjectId < 1) {
            return $stats;
        }

        $tcol = SchemaHelper::topicsTable();
        $tLive = SchemaHelper::columnExists($tcol, 'status') ? ' AND status = 1' : '';
        $st = db()->prepare("SELECT COUNT(*) FROM `{$tcol}` WHERE subject_id = ?{$tLive}");
        $st->execute([$subjectId]);
        $stats['topics'] = (int) $st->fetchColumn();

        $testLive = SchemaHelper::testsHasStatus() ? 'status = 1 AND is_active = 1' : 'is_active = 1';
        $st2 = db()->prepare("SELECT COUNT(*) FROM tests WHERE subject_id = ? AND {$testLive}");
        $st2->execute([$subjectId]);
        $stats['tests'] = (int) $st2->fetchColumn();

        if (SchemaHelper::hasTable('study_materials')) {
            $st3 = db()->prepare('SELECT COUNT(*) FROM study_materials WHERE subject_id = ?');
            $st3->execute([$subjectId]);
            $stats['materials'] = (int) $st3->fetchColumn();
        }

        return $stats;
    }

    public function allPlansWithContext(): array
    {
        if (!SchemaHelper::hasTable('sub_course_plans')) {
            return [];
        }
        return db()->query(
            'SELECT sp.*, sc.name AS sub_course_name, sc.slug AS sub_course_slug, c.name AS course_name, c.slug AS course_slug
            FROM sub_course_plans sp
            JOIN sub_courses sc ON sc.id = sp.sub_course_id
            JOIN courses c ON c.id = sc.course_id
            ORDER BY c.sort_order, sc.sort_order, sp.plan_code'
        )->fetchAll();
    }

    public function allWithSubjects(): array
    {
        $courses = $this->allActive();
        foreach ($courses as &$course) {
            if (SchemaHelper::hierarchyFourTier()) {
                $course['sub_courses'] = $this->subCoursesForCourse((int) $course['id']);
                $flat = [];
                foreach ($course['sub_courses'] as $sc) {
                    foreach ($this->subjectsForSubCourse((int) $sc['id']) as $sub) {
                        $flat[] = $sub;
                    }
                }
                $course['subjects'] = $flat;
            } else {
                $course['subjects'] = $this->subjectsForCourse((int) $course['id']);
            }
        }

        return $courses;
    }

    /**
     * Public site: active main courses with nested live programmes and subjects (four-tier),
     * or flat legacy subjects when hierarchy tables are absent.
     *
     * @return list<array<string,mixed>>
     */
    public function catalogForPublicSite(): array
    {
        $courses = $this->allActive();
        foreach ($courses as &$course) {
            $cid = SchemaHelper::resolveCatalogCourseId((int) $course['id']);
            if (SchemaHelper::hierarchyFourTier()) {
                $course['programmes'] = [];
                foreach ($this->subCoursesForCourse($cid) as $sc) {
                    $course['programmes'][] = [
                        'sub_course' => $sc,
                        'subjects' => $this->subjectsForSubCourse((int) $sc['id']),
                    ];
                }
                $course['legacy_subjects'] = [];
            } else {
                $course['programmes'] = [];
                $course['legacy_subjects'] = $this->subjectsForCourse($cid);
            }
        }
        unset($course);

        return $courses;
    }

    public function counts(): array
    {
        $c = SchemaHelper::coursesHasStatus() ? 'status=1' : 'is_active=1';
        $s = SchemaHelper::subjectsHasStatus() ? 'status=1' : 'is_active=1';
        $tcol = SchemaHelper::topicsTable();
        $tLive = SchemaHelper::columnExists($tcol, 'status') ? ' WHERE status=1' : '';
        $t = SchemaHelper::testsHasStatus()
            ? 'status=1 AND is_active=1'
            : 'is_active=1';
        $row = db()->query("SELECT
            (SELECT COUNT(*) FROM courses WHERE {$c}) AS courses,
            (SELECT COUNT(*) FROM subjects WHERE {$s}) AS subjects,
            (SELECT COUNT(*) FROM `{$tcol}`{$tLive}) AS topics,
            (SELECT COUNT(*) FROM tests WHERE {$t}) AS tests")->fetch();

        if (!$row) {
            return ['courses' => 0, 'subjects' => 0, 'topics' => 0, 'lessons' => 0, 'tests' => 0];
        }
        $row['lessons'] = $row['topics'];

        return $row;
    }
}
