<?php

declare(strict_types=1);

class AdminRepository
{
    public function verifyAdmin(string $email, string $password): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND role = "admin" LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($password, $row['password_hash'])) {
            return null;
        }
        unset($row['password_hash']);
        return $row;
    }

    public function dashboardStats(): array
    {
        $pdo = db();
        $row = $pdo->query("SELECT
            (SELECT COUNT(*) FROM users WHERE role='student') AS total_students,
            (SELECT COUNT(*) FROM courses) AS total_courses,
            (SELECT COUNT(*) FROM test_attempts WHERE status='submitted') AS total_exams
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $revenue = 0.0;
        if (SchemaHelper::hasTable('payments')) {
            $revenue = (float) $pdo->query(
                "SELECT COALESCE(SUM(amount_inr),0) FROM payments WHERE status='completed'"
            )->fetchColumn();
        }

        $enrollment = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS count
            FROM users WHERE role='student' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY month ORDER BY month")->fetchAll();

        return [
            'stats' => [
                'total_students' => (int) ($row['total_students'] ?? 0),
                'total_courses' => (int) ($row['total_courses'] ?? 0),
                'total_exams' => (int) ($row['total_exams'] ?? 0),
                'revenue' => $revenue,
            ],
            'enrollment' => $enrollment,
        ];
    }

    public function allCourses(): array
    {
        $order = SchemaHelper::sqlOrderBySort('sort_order', 'id');

        return db()->query("SELECT * FROM courses ORDER BY {$order}, name")->fetchAll();
    }

    public function saveCourse(array $data, ?int $id = null): int
    {
        $active = (int) ($data['is_active'] ?? 1);

        if (SchemaHelper::coursesHasStatus()) {
            if ($id) {
                $stmt = db()->prepare('UPDATE courses SET slug=?, name=?, name_te=?, region=?, description=?, sort_order=?, status=?, is_active=? WHERE id=?');
                $stmt->execute([
                    $data['slug'], $data['name'], $data['name_te'], $data['region'],
                    $data['description'], $data['sort_order'], $active, $active, $id,
                ]);

                return $id;
            }
            $stmt = db()->prepare('INSERT INTO courses (slug,name,name_te,region,description,sort_order,status,is_active) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $data['slug'], $data['name'], $data['name_te'], $data['region'],
                $data['description'], $data['sort_order'], $active, $active,
            ]);

            return (int) db()->lastInsertId();
        }

        if ($id) {
            $stmt = db()->prepare('UPDATE courses SET slug=?, name=?, name_te=?, region=?, description=?, sort_order=?, is_active=? WHERE id=?');
            $stmt->execute([
                $data['slug'], $data['name'], $data['name_te'], $data['region'],
                $data['description'], $data['sort_order'], $active, $id,
            ]);

            return $id;
        }
        $stmt = db()->prepare('INSERT INTO courses (slug,name,name_te,region,description,sort_order,is_active) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['slug'], $data['name'], $data['name_te'], $data['region'],
            $data['description'], $data['sort_order'], $active,
        ]);

        return (int) db()->lastInsertId();
    }

    public function deleteCourse(int $id): void
    {
        if ($id < 1) {
            return;
        }
        if (SchemaHelper::hierarchyFourTier()) {
            $st = db()->prepare('SELECT id FROM sub_courses WHERE course_id=?');
            $st->execute([$id]);
            foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $scid) {
                $this->deleteSubCourse((int) $scid);
            }
        }
        $st = db()->prepare('SELECT id FROM subjects WHERE course_id=?');
        $st->execute([$id]);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $sid) {
            $this->deleteSubject((int) $sid);
        }
        if (SchemaHelper::hasTable('main_courses')) {
            $slugSt = db()->prepare('SELECT slug FROM courses WHERE id=? LIMIT 1');
            $slugSt->execute([$id]);
            $slug = $slugSt->fetchColumn();
            if (is_string($slug) && $slug !== '') {
                db()->prepare('DELETE FROM main_courses WHERE slug=?')->execute([$slug]);
            }
        }
        db()->prepare('DELETE FROM courses WHERE id=?')->execute([$id]);
    }

    public function allSubjectsWithCourse(): array
    {
        if (SchemaHelper::hierarchyFourTier()) {
            return db()->query(
                'SELECT s.*,
                    GROUP_CONCAT(DISTINCT CONCAT(c.name, " — ", sc.name) ORDER BY c.sort_order, sc.sort_order SEPARATOR "; ") AS linked_summary
                FROM subjects s
                LEFT JOIN sub_course_subjects scs ON scs.subject_id = s.id
                LEFT JOIN sub_courses sc ON sc.id = scs.sub_course_id
                LEFT JOIN courses c ON c.id = sc.course_id
                GROUP BY s.id
                ORDER BY s.sort_order, s.name'
            )->fetchAll();
        }

        if (SchemaHelper::hasTable('course_categories')) {
            return db()->query(
                'SELECT s.*, c.name AS course_name, cc.name AS category_name
                FROM subjects s
                JOIN courses c ON c.id = s.course_id
                LEFT JOIN course_categories cc ON cc.id = s.category_id
                ORDER BY c.sort_order, cc.sort_order, s.sort_order, s.name'
            )->fetchAll();
        }

        return db()->query('SELECT s.*, c.name AS course_name FROM subjects s JOIN courses c ON c.id=s.course_id ORDER BY c.sort_order, s.sort_order')->fetchAll();
    }

    /** @param list<int|string> $subCourseIds */
    public function syncSubjectSubCourses(int $subjectId, array $subCourseIds): void
    {
        if (!SchemaHelper::hierarchyFourTier()) {
            return;
        }
        db()->prepare('DELETE FROM sub_course_subjects WHERE subject_id=?')->execute([$subjectId]);
        $hasStatus = SchemaHelper::columnExists('sub_course_subjects', 'status');
        $hasActive = SchemaHelper::columnExists('sub_course_subjects', 'is_active');
        if ($hasStatus && $hasActive) {
            $ins = db()->prepare(
                'INSERT INTO sub_course_subjects (sub_course_id, subject_id, sort_order, status, is_active) VALUES (?,?,?,1,1)'
            );
        } elseif ($hasActive) {
            $ins = db()->prepare(
                'INSERT INTO sub_course_subjects (sub_course_id, subject_id, sort_order, is_active) VALUES (?,?,?,1)'
            );
        } else {
            $ins = db()->prepare(
                'INSERT INTO sub_course_subjects (sub_course_id, subject_id, sort_order) VALUES (?,?,?)'
            );
        }
        $sort = 0;
        foreach ($subCourseIds as $scid) {
            $subCourseId = (int) $scid;
            if ($subCourseId < 1) {
                continue;
            }
            $ins->execute([$subCourseId, $subjectId, $sort]);
            ++$sort;
        }

        if (SchemaHelper::columnExists('subjects', 'sub_course_id')) {
            $primary = null;
            $st = db()->prepare('SELECT sub_course_id FROM sub_course_subjects WHERE subject_id=? ORDER BY sort_order, id LIMIT 1');
            $st->execute([$subjectId]);
            $primary = $st->fetchColumn();
            db()->prepare('UPDATE subjects SET sub_course_id=? WHERE id=?')->execute(
                [$primary !== false && $primary !== null ? (int) $primary : null, $subjectId]
            );
        }
    }

    public function allSubCoursesForSelect(): array
    {
        if (!SchemaHelper::hierarchyFourTier()) {
            return [];
        }

        return db()->query(
            'SELECT sc.id, sc.course_id, sc.slug, sc.name, sc.is_active'
            . (SchemaHelper::columnExists('sub_courses', 'status') ? ', sc.status' : '')
            . ', c.name AS course_name, c.slug AS course_slug
            FROM sub_courses sc JOIN courses c ON c.id = sc.course_id
            ORDER BY c.sort_order, sc.sort_order, sc.name'
        )->fetchAll();
    }

    public function saveSubject(array $data, ?int $id = null): int
    {
        $active = (int) ($data['is_active'] ?? 1);
        $categoryId = isset($data['category_id']) && $data['category_id'] !== '' ? (int) $data['category_id'] : null;
        $marks = array_key_exists('marks_allocated', $data) && $data['marks_allocated'] !== '' && $data['marks_allocated'] !== null
            ? (int) $data['marks_allocated'] : null;

        $hasCat = SchemaHelper::columnExists('subjects', 'category_id');
        $hasMarks = SchemaHelper::columnExists('subjects', 'marks_allocated');
        $hasSt = SchemaHelper::subjectsHasStatus();

        if (SchemaHelper::hierarchyFourTier()) {
            $courseId = isset($data['course_id']) && $data['course_id'] !== '' ? (int) $data['course_id'] : null;

            if ($hasCat && $hasMarks && $hasSt) {
                if ($id) {
                    $stmt = db()->prepare('UPDATE subjects SET course_id=?, category_id=?, slug=?, name=?, name_te=?, description=?, marks_allocated=?, sort_order=?, status=?, is_active=? WHERE id=?');
                    $stmt->execute([
                        $courseId, $categoryId, $data['slug'], $data['name'], $data['name_te'],
                        $data['description'], $marks, $data['sort_order'], $active, $active, $id,
                    ]);
                    $this->syncSubjectSubCourses($id, $data['sub_course_ids'] ?? []);

                    return $id;
                }
                $stmt = db()->prepare('INSERT INTO subjects (course_id,category_id,slug,name,name_te,description,marks_allocated,sort_order,status,is_active) VALUES (?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([
                    $courseId, null, $data['slug'], $data['name'], $data['name_te'],
                    $data['description'], $marks, $data['sort_order'], $active, $active,
                ]);
                $nid = (int) db()->lastInsertId();
                $this->syncSubjectSubCourses($nid, $data['sub_course_ids'] ?? []);

                return $nid;
            }

            if ($id) {
                $stmt = db()->prepare('UPDATE subjects SET course_id=?, slug=?, name=?, name_te=?, description=?, sort_order=?, status=?, is_active=? WHERE id=?');
                $stmt->execute([
                    $courseId, $data['slug'], $data['name'], $data['name_te'],
                    $data['description'], $data['sort_order'], $active, $active, $id,
                ]);
                $this->syncSubjectSubCourses($id, $data['sub_course_ids'] ?? []);

                return $id;
            }
            $stmt = db()->prepare('INSERT INTO subjects (course_id,slug,name,name_te,description,sort_order,status,is_active) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $courseId, $data['slug'], $data['name'], $data['name_te'],
                $data['description'], $data['sort_order'], $active, $active,
            ]);
            $nid = (int) db()->lastInsertId();
            $this->syncSubjectSubCourses($nid, $data['sub_course_ids'] ?? []);

            return $nid;
        }

        if ($hasCat && $hasMarks && $hasSt) {
            if ($id) {
                $stmt = db()->prepare('UPDATE subjects SET course_id=?, category_id=?, slug=?, name=?, name_te=?, description=?, marks_allocated=?, sort_order=?, status=?, is_active=? WHERE id=?');
                $stmt->execute([
                    $data['course_id'], $categoryId, $data['slug'], $data['name'], $data['name_te'],
                    $data['description'], $marks, $data['sort_order'], $active, $active, $id,
                ]);

                return $id;
            }
            $stmt = db()->prepare('INSERT INTO subjects (course_id,category_id,slug,name,name_te,description,marks_allocated,sort_order,status,is_active) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $data['course_id'], $categoryId, $data['slug'], $data['name'], $data['name_te'],
                $data['description'], $marks, $data['sort_order'], $active, $active,
            ]);

            return (int) db()->lastInsertId();
        }

        if ($id) {
            $stmt = db()->prepare('UPDATE subjects SET course_id=?, slug=?, name=?, name_te=?, description=?, sort_order=?, is_active=? WHERE id=?');
            $stmt->execute([
                $data['course_id'], $data['slug'], $data['name'], $data['name_te'],
                $data['description'], $data['sort_order'], $active, $id,
            ]);

            return $id;
        }
        $stmt = db()->prepare('INSERT INTO subjects (course_id,slug,name,name_te,description,sort_order,is_active) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['course_id'], $data['slug'], $data['name'], $data['name_te'],
            $data['description'], $data['sort_order'], $active,
        ]);

        return (int) db()->lastInsertId();
    }

    public function deleteSubject(int $id): void
    {
        if ($id < 1) {
            return;
        }
        $tbl = SchemaHelper::topicsTable();
        $tSt = db()->prepare("SELECT id FROM `{$tbl}` WHERE subject_id=?");
        $tSt->execute([$id]);
        foreach ($tSt->fetchAll(PDO::FETCH_COLUMN) as $tid) {
            $this->cmDeleteTopic((int) $tid);
        }
        if (SchemaHelper::hasTable('study_materials')) {
            db()->prepare('DELETE FROM study_materials WHERE subject_id=?')->execute([$id]);
        }
        if (SchemaHelper::hierarchyFourTier()) {
            db()->prepare('DELETE FROM sub_course_subjects WHERE subject_id=?')->execute([$id]);
        }
        db()->prepare('DELETE FROM subjects WHERE id=?')->execute([$id]);
    }

    public function saveSubCourse(array $data, ?int $id = null): int
    {
        if (!SchemaHelper::hierarchyFourTier()) {
            throw new RuntimeException('sub_courses not available');
        }
        $active = (int) ($data['is_active'] ?? 1);
        $hasSt = SchemaHelper::columnExists('sub_courses', 'status');
        $scId = null;
        if ($hasSt) {
            if ($id) {
                $stmt = db()->prepare('UPDATE sub_courses SET course_id=?, slug=?, name=?, name_te=?, description=?, sort_order=?, status=?, is_active=? WHERE id=?');
                $stmt->execute([
                    $data['course_id'], $data['slug'], $data['name'], $data['name_te'],
                    $data['description'], $data['sort_order'], $active, $active, $id,
                ]);
                $scId = $id;
            } else {
                $stmt = db()->prepare('INSERT INTO sub_courses (course_id,slug,name,name_te,description,sort_order,status,is_active) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->execute([
                    $data['course_id'], $data['slug'], $data['name'], $data['name_te'],
                    $data['description'], $data['sort_order'], $active, $active,
                ]);
                $scId = (int) db()->lastInsertId();
            }
        } elseif ($id) {
            db()->prepare('UPDATE sub_courses SET course_id=?, slug=?, name=?, name_te=?, description=?, sort_order=?, is_active=? WHERE id=?')->execute([
                $data['course_id'], $data['slug'], $data['name'], $data['name_te'],
                $data['description'], $data['sort_order'], $active, $id,
            ]);
            $scId = $id;
        } else {
            db()->prepare('INSERT INTO sub_courses (course_id,slug,name,name_te,description,sort_order,is_active) VALUES (?,?,?,?,?,?,?)')->execute([
                $data['course_id'], $data['slug'], $data['name'], $data['name_te'],
                $data['description'], $data['sort_order'], $active,
            ]);
            $scId = (int) db()->lastInsertId();
        }

        if ($scId !== null && !empty($data['sync_subject_links'])) {
            $this->syncSubCourseSubjects($scId, $data['subject_ids'] ?? []);
        }

        return (int) $scId;
    }

    public function deleteSubCourse(int $id): void
    {
        if ($id < 1) {
            return;
        }
        if (SchemaHelper::hasTable('sub_course_subjects')) {
            db()->prepare('DELETE FROM sub_course_subjects WHERE sub_course_id=?')->execute([$id]);
        }
        if (SchemaHelper::hasTable('sub_course_plans')) {
            db()->prepare('DELETE FROM sub_course_plans WHERE sub_course_id=?')->execute([$id]);
        }
        if (SchemaHelper::hasTable('sub_course_term_boxes')) {
            db()->prepare('DELETE FROM sub_course_term_boxes WHERE sub_course_id=?')->execute([$id]);
        }
        if (SchemaHelper::hasTable('sub_course_term_schedule')) {
            db()->prepare('DELETE FROM sub_course_term_schedule WHERE sub_course_id=?')->execute([$id]);
        }
        db()->prepare('DELETE FROM sub_courses WHERE id=?')->execute([$id]);
    }

    public function getSubCourse(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM sub_courses WHERE id=? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch();

        return $r ?: null;
    }

    public function subCourseSubjectIds(int $subjectId): array
    {
        if (!SchemaHelper::hierarchyFourTier()) {
            return [];
        }
        $st = db()->prepare('SELECT sub_course_id FROM sub_course_subjects WHERE subject_id=? ORDER BY sort_order');
        $st->execute([$subjectId]);

        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @return list<int> Subject IDs linked to this sub-course (pivot order). */
    public function subjectIdsForSubCourse(int $subCourseId): array
    {
        if (!SchemaHelper::hierarchyFourTier() || $subCourseId < 1) {
            return [];
        }
        $st = db()->prepare('SELECT subject_id FROM sub_course_subjects WHERE sub_course_id=? ORDER BY sort_order, id');
        $st->execute([$subCourseId]);

        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Replace pivots for one sub-course only (other programmes unchanged). */
    public function syncSubCourseSubjects(int $subCourseId, array $subjectIds): void
    {
        if (!SchemaHelper::hierarchyFourTier() || $subCourseId < 1) {
            return;
        }
        db()->prepare('DELETE FROM sub_course_subjects WHERE sub_course_id=?')->execute([$subCourseId]);
        $ins = db()->prepare(
            'INSERT INTO sub_course_subjects (sub_course_id, subject_id, sort_order, status, is_active) VALUES (?,?,?,?,?)'
        );
        $ord = 0;
        foreach ($subjectIds as $sid) {
            $sid = (int) $sid;
            if ($sid < 1) {
                continue;
            }
            $ins->execute([$subCourseId, $sid, $ord, 1, 1]);
            ++$ord;
        }
    }

    /** @param array<int, array{price_inr:float|int|string, active?:bool}> $rows plan_id keyed */
    public function savePlanPrices(array $rows): void
    {
        if (!SchemaHelper::hasTable('sub_course_plans')) {
            return;
        }
        $hasSt = SchemaHelper::columnExists('sub_course_plans', 'status');
        $upd = $hasSt
            ? db()->prepare('UPDATE sub_course_plans SET price_inr=?, status=?, is_active=? WHERE id=?')
            : db()->prepare('UPDATE sub_course_plans SET price_inr=?, is_active=? WHERE id=?');
        foreach ($rows as $planId => $row) {
            $pid = (int) $planId;
            if ($pid < 1) {
                continue;
            }
            $price = (float) ($row['price_inr'] ?? 0);
            $on = empty($row['active']) ? 0 : 1;
            if ($hasSt) {
                $upd->execute([$price, $on, $on, $pid]);
            } else {
                $upd->execute([$price, $on, $pid]);
            }
        }
    }

    public function saveTopic(array $data, ?int $id = null): int
    {
        $tbl = SchemaHelper::topicsTable();
        $examLink = isset($data['exam_link']) ? trim((string) $data['exam_link']) : null;
        $examTestId = isset($data['exam_test_id']) && $data['exam_test_id'] !== '' ? (int) $data['exam_test_id'] : null;
        $hasExamLink = SchemaHelper::columnExists($tbl, 'exam_link');
        $hasExamTest = SchemaHelper::columnExists($tbl, 'exam_test_id');
        $vis = array_key_exists('is_active', $data)
            ? (!empty($data['is_active']) ? 1 : 0)
            : 1;

        if ($hasExamLink && $hasExamTest) {
            if ($id) {
                $stmt = db()->prepare("UPDATE `{$tbl}` SET subject_id=?, slug=?, title=?, title_te=?, summary=?, duration_mins=?, sort_order=?, is_free_preview=?, exam_link=?, exam_test_id=? WHERE id=?");
                $stmt->execute([
                    $data['subject_id'], $data['slug'], $data['title'], $data['title_te'],
                    $data['summary'], $data['duration_mins'], $data['sort_order'], $data['is_free_preview'],
                    $examLink, $examTestId ?: null, $id,
                ]);
                $newId = $id;
            } else {
                $stmt = db()->prepare("INSERT INTO `{$tbl}` (subject_id,slug,title,title_te,summary,duration_mins,sort_order,is_free_preview,exam_link,exam_test_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $data['subject_id'], $data['slug'], $data['title'], $data['title_te'],
                    $data['summary'], $data['duration_mins'], $data['sort_order'], $data['is_free_preview'],
                    $examLink, $examTestId ?: null,
                ]);
                $newId = (int) db()->lastInsertId();
            }
        } elseif ($id) {
            $stmt = db()->prepare("UPDATE `{$tbl}` SET subject_id=?, slug=?, title=?, title_te=?, summary=?, duration_mins=?, sort_order=?, is_free_preview=? WHERE id=?");
            $stmt->execute([
                $data['subject_id'], $data['slug'], $data['title'], $data['title_te'],
                $data['summary'], $data['duration_mins'], $data['sort_order'], $data['is_free_preview'], $id,
            ]);
            $newId = $id;
        } else {
            $stmt = db()->prepare("INSERT INTO `{$tbl}` (subject_id,slug,title,title_te,summary,duration_mins,sort_order,is_free_preview) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $data['subject_id'], $data['slug'], $data['title'], $data['title_te'],
                $data['summary'], $data['duration_mins'], $data['sort_order'], $data['is_free_preview'],
            ]);
            $newId = (int) db()->lastInsertId();
        }

        if (SchemaHelper::columnExists($tbl, 'status')) {
            db()->prepare("UPDATE `{$tbl}` SET status=? WHERE id=?")->execute([$vis, $newId]);
        }

        return $newId;
    }

    /** @deprecated Topics-only surface — delegates to saveTopic() */
    public function saveLesson(array $data, ?int $id = null): int
    {
        return $this->saveTopic($data, $id);
    }

    /** Admin dropdown: all topics with subject label */
    public function topicsCatalogForAdmin(): array
    {
        if (!SchemaHelper::topicExamsEnabled()) {
            return [];
        }
        $tbl = SchemaHelper::topicsTable();

        return db()->query(
            "SELECT t.id, t.title, t.slug AS topic_slug, s.id AS subject_id, s.name AS subject_name
            FROM `{$tbl}` t
            JOIN subjects s ON s.id = t.subject_id
            ORDER BY s.sort_order, s.name, t.sort_order, t.title"
        )->fetchAll();
    }

    /** All topic-linked exams for admin table */
    public function allTopicExamsAdmin(): array
    {
        if (!SchemaHelper::topicExamsEnabled()) {
            return [];
        }
        $tbl = SchemaHelper::topicsTable();

        return db()->query(
            "SELECT e.*, t.title AS topic_title, s.name AS subject_name
            FROM exams e
            JOIN `{$tbl}` t ON t.id = e.topic_id
            JOIN subjects s ON s.id = t.subject_id
            ORDER BY s.sort_order, t.sort_order, e.sort_order, e.id"
        )->fetchAll();
    }

    public function saveTopicExam(array $data, ?int $id = null): int
    {
        if (!SchemaHelper::topicExamsEnabled()) {
            throw new RuntimeException('exams table not installed');
        }
        $topicId = (int) ($data['topic_id'] ?? 0);
        if ($topicId < 1) {
            throw new InvalidArgumentException('topic_id required');
        }
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('title required');
        }
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = slugify($title);
        }
        $slug = slugify($slug);
        $titleTe = trim((string) ($data['title_te'] ?? ''));
        $external = isset($data['external_url']) ? trim((string) $data['external_url']) : null;
        $external = $external !== '' ? $external : null;
        $testId = isset($data['test_id']) && $data['test_id'] !== '' ? (int) $data['test_id'] : null;
        if ($testId !== null && $testId < 1) {
            $testId = null;
        }
        $sortOrder = (int) ($data['sort_order'] ?? 0);
        $active = (int) ($data['is_active'] ?? 1);
        $hasSt = SchemaHelper::columnExists('exams', 'status');

        if ($id) {
            if ($hasSt) {
                $stmt = db()->prepare(
                    'UPDATE exams SET topic_id=?, title=?, title_te=?, slug=?, external_url=?, test_id=?, sort_order=?, status=?, is_active=? WHERE id=?'
                );
                $stmt->execute([$topicId, $title, $titleTe ?: null, $slug, $external, $testId, $sortOrder, $active, $active, $id]);
            } else {
                $stmt = db()->prepare(
                    'UPDATE exams SET topic_id=?, title=?, title_te=?, slug=?, external_url=?, test_id=?, sort_order=?, is_active=? WHERE id=?'
                );
                $stmt->execute([$topicId, $title, $titleTe ?: null, $slug, $external, $testId, $sortOrder, $active, $id]);
            }
            $this->applyExamMetaColumns($id, $data);

            return $id;
        }

        $suffix = 0;
        $baseSlug = $slug;
        while (true) {
            $chk = db()->prepare('SELECT id FROM exams WHERE topic_id=? AND slug=? LIMIT 1');
            $chk->execute([$topicId, $slug]);
            if (!$chk->fetch()) {
                break;
            }
            ++$suffix;
            $slug = $baseSlug . '-' . $suffix;
        }

        if ($hasSt) {
            $stmt = db()->prepare(
                'INSERT INTO exams (topic_id,title,title_te,slug,external_url,test_id,sort_order,status,is_active)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([$topicId, $title, $titleTe ?: null, $slug, $external, $testId, $sortOrder, $active, $active]);
        } else {
            $stmt = db()->prepare(
                'INSERT INTO exams (topic_id,title,title_te,slug,external_url,test_id,sort_order,is_active)
                 VALUES (?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([$topicId, $title, $titleTe ?: null, $slug, $external, $testId, $sortOrder, $active]);
        }

        $newId = (int) db()->lastInsertId();
        $this->applyExamMetaColumns($newId, $data);

        return $newId;
    }

    /**
     * Optional exams.test_type + exams.material_url (after migrate_exam_hierarchy).
     *
     * @param array<string,mixed> $data
     */
    private function applyExamMetaColumns(int $examId, array $data): void
    {
        $hasType = SchemaHelper::columnExists('exams', 'test_type');
        $hasMat = SchemaHelper::columnExists('exams', 'material_url');
        if (!$hasType && !$hasMat) {
            return;
        }
        $allowed = ['topic', 'division', 'revision', 'grand', 'model'];
        $tv = (string) ($data['exam_test_type'] ?? $data['test_type'] ?? 'topic');
        $typeVal = in_array($tv, $allowed, true) ? $tv : 'topic';
        $matRaw = isset($data['material_url']) ? trim((string) $data['material_url']) : '';
        $matVal = $matRaw !== '' ? $matRaw : null;

        if ($hasType && $hasMat) {
            db()->prepare('UPDATE exams SET test_type=?, material_url=? WHERE id=?')->execute([$typeVal, $matVal, $examId]);
        } elseif ($hasType) {
            db()->prepare('UPDATE exams SET test_type=? WHERE id=?')->execute([$typeVal, $examId]);
        } else {
            db()->prepare('UPDATE exams SET material_url=? WHERE id=?')->execute([$matVal, $examId]);
        }
    }

    public function deleteTopicExam(int $id): void
    {
        if (!SchemaHelper::topicExamsEnabled()) {
            return;
        }
        db()->prepare('DELETE FROM exams WHERE id=?')->execute([$id]);
    }

    public function saveMaterial(array $data, ?int $id = null): int
    {
        $topicId = isset($data['topic_id']) && $data['topic_id'] !== '' ? (int) $data['topic_id'] : null;
        $hasTopic = SchemaHelper::columnExists('study_materials', 'topic_id');
        $col = SchemaHelper::materialsTopicFkColumn();

        if ($hasTopic && $col === 'topic_id') {
            if ($id) {
                $stmt = db()->prepare('UPDATE study_materials SET subject_id=?, topic_id=?, title=?, material_type=?, file_url=?, description=? WHERE id=?');
                $stmt->execute([$data['subject_id'], $topicId, $data['title'], $data['material_type'], $data['file_url'], $data['description'], $id]);

                return $id;
            }
            $stmt = db()->prepare('INSERT INTO study_materials (subject_id,topic_id,title,material_type,file_url,description) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$data['subject_id'], $topicId, $data['title'], $data['material_type'], $data['file_url'], $data['description']]);

            return (int) db()->lastInsertId();
        }

        if ($id) {
            $stmt = db()->prepare('UPDATE study_materials SET subject_id=?, title=?, material_type=?, file_url=?, description=? WHERE id=?');
            $stmt->execute([$data['subject_id'], $data['title'], $data['material_type'], $data['file_url'], $data['description'], $id]);

            return $id;
        }
        $stmt = db()->prepare('INSERT INTO study_materials (subject_id,title,material_type,file_url,description) VALUES (?,?,?,?,?)');
        $stmt->execute([$data['subject_id'], $data['title'], $data['material_type'], $data['file_url'], $data['description']]);

        return (int) db()->lastInsertId();
    }

    public function allPackages(): array
    {
        return db()->query('SELECT p.*, c.name AS course_name, s.name AS subject_name
            FROM sub_course_packages p
            LEFT JOIN courses c ON c.id=p.course_id
            LEFT JOIN subjects s ON s.id=p.subject_id
            ORDER BY p.id DESC')->fetchAll();
    }

    public function savePackage(array $data, ?int $id = null): int
    {
        $active = (int) ($data['is_active'] ?? 1);
        $pkgStatus = SchemaHelper::columnExists('sub_course_packages', 'status');

        if ($pkgStatus) {
            if ($id) {
                $stmt = db()->prepare('UPDATE sub_course_packages SET slug=?, package_type=?, course_id=?, subject_id=?, name=?, name_te=?, description=?, price_inr=?, includes_division_tests=?, status=?, is_active=? WHERE id=?');
                $stmt->execute([
                    $data['slug'], $data['package_type'], $data['course_id'], $data['subject_id'],
                    $data['name'], $data['name_te'], $data['description'], $data['price_inr'],
                    $data['includes_division_tests'], $active, $active, $id,
                ]);

                return $id;
            }
            $stmt = db()->prepare('INSERT INTO sub_course_packages (slug,package_type,course_id,subject_id,name,name_te,description,price_inr,includes_division_tests,status,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $data['slug'], $data['package_type'], $data['course_id'], $data['subject_id'],
                $data['name'], $data['name_te'], $data['description'], $data['price_inr'],
                $data['includes_division_tests'], $active, $active,
            ]);

            return (int) db()->lastInsertId();
        }

        if ($id) {
            $stmt = db()->prepare('UPDATE sub_course_packages SET slug=?, package_type=?, course_id=?, subject_id=?, name=?, name_te=?, description=?, price_inr=?, includes_division_tests=?, is_active=? WHERE id=?');
            $stmt->execute([
                $data['slug'], $data['package_type'], $data['course_id'], $data['subject_id'],
                $data['name'], $data['name_te'], $data['description'], $data['price_inr'],
                $data['includes_division_tests'], $active, $id,
            ]);

            return $id;
        }
        $stmt = db()->prepare('INSERT INTO sub_course_packages (slug,package_type,course_id,subject_id,name,name_te,description,price_inr,includes_division_tests,is_active) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['slug'], $data['package_type'], $data['course_id'], $data['subject_id'],
            $data['name'], $data['name_te'], $data['description'], $data['price_inr'],
            $data['includes_division_tests'], $active,
        ]);

        return (int) db()->lastInsertId();
    }

    public function allTests(): array
    {
        $tpTbl = SchemaHelper::topicsTable();
        $topicJoin = SchemaHelper::columnExists('tests', 'topic_id')
            ? "LEFT JOIN `{$tpTbl}` tp ON tp.id = t.topic_id" : '';
        $topicField = SchemaHelper::columnExists('tests', 'topic_id') ? ', tp.title AS topic_title' : '';

        return db()->query(
            "SELECT t.*, c.name AS course_name, s.name AS subject_name{$topicField},
            (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) AS question_count
            FROM tests t JOIN courses c ON c.id=t.course_id
            LEFT JOIN subjects s ON s.id=t.subject_id
            {$topicJoin}
            ORDER BY t.id DESC"
        )->fetchAll();
    }

    /** Topics for one subject (exam form picker) */
    public function topicsForSubjectAdmin(int $subjectId): array
    {
        if ($subjectId < 1) {
            return [];
        }
        $tbl = SchemaHelper::topicsTable();
        $hasCustom = SchemaHelper::columnExists($tbl, 'is_custom');
        $customCols = $hasCustom ? ', is_custom, created_by_admin' : '';
        $order = $hasCustom ? 'is_custom DESC, sort_order, id' : 'sort_order, id';
        $st = db()->prepare(
            "SELECT id, title, slug{$customCols} FROM `{$tbl}` WHERE subject_id=? ORDER BY {$order}"
        );
        $st->execute([$subjectId]);

        return $st->fetchAll() ?: [];
    }

    /**
     * Tests that can be included in a composite (division / revision / grand / model).
     *
     * @return list<array<string,mixed>>
     */
    public function testsForBundlePicker(int $courseId, ?int $subjectId, ?int $excludeTestId = null): array
    {
        $statusCond = SchemaHelper::testsHasStatus() ? 't.status = 1 AND ' : '';
        $sql = 'SELECT t.id, t.title, t.slug, t.test_type, t.subject_id
            FROM tests t
            WHERE t.course_id = ? AND ' . $statusCond . ' t.is_active = 1';
        $params = [$courseId];
        if ($subjectId !== null && $subjectId > 0) {
            $sql .= ' AND (t.subject_id = ? OR t.subject_id IS NULL)';
            $params[] = $subjectId;
        }
        if ($excludeTestId !== null && $excludeTestId > 0) {
            $sql .= ' AND t.id <> ?';
            $params[] = $excludeTestId;
        }
        $sql .= ' ORDER BY FIELD(t.test_type, "topic","division","revision","grand","model"), t.title';
        $st = db()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll();
    }

    /** Ordered component test IDs for a composite exam */
    public function bundleComponentIds(int $bundleTestId): array
    {
        if (!SchemaHelper::testBundleEnabled()) {
            return [];
        }
        $st = db()->prepare('SELECT component_test_id FROM test_bundle_items WHERE bundle_test_id=? ORDER BY sort_order, id');
        $st->execute([$bundleTestId]);

        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    /** @param list<int> $componentIds */
    public function syncTestBundle(int $bundleTestId, array $componentIds): void
    {
        if (!SchemaHelper::testBundleEnabled()) {
            return;
        }
        db()->prepare('DELETE FROM test_bundle_items WHERE bundle_test_id=?')->execute([$bundleTestId]);
        $ins = db()->prepare('INSERT INTO test_bundle_items (bundle_test_id, component_test_id, sort_order) VALUES (?,?,?)');
        $ord = 0;
        foreach ($componentIds as $cid) {
            $cid = (int) $cid;
            if ($cid < 1 || $cid === $bundleTestId) {
                continue;
            }
            $ins->execute([$bundleTestId, $cid, $ord]);
            ++$ord;
        }
    }

    public function saveTest(array $data, ?int $id = null): int
    {
        $active = (int) ($data['is_active'] ?? 1);
        $tst = SchemaHelper::testsHasStatus();

        if ($tst) {
            if ($id) {
                $stmt = db()->prepare('UPDATE tests SET course_id=?, subject_id=?, slug=?, title=?, title_te=?, test_type=?, division_label=?, duration_mins=?, total_questions=?, total_marks=?, passing_marks=?, negative_marking=?, package_id=?, status=?, is_active=? WHERE id=?');
                $stmt->execute([
                    $data['course_id'], $data['subject_id'], $data['slug'], $data['title'], $data['title_te'],
                    $data['test_type'], $data['division_label'], $data['duration_mins'], $data['total_questions'],
                    $data['total_marks'], $data['passing_marks'], $data['negative_marking'], $data['package_id'],
                    $active, $active, $id,
                ]);
                $tid = $id;
            } else {
                $stmt = db()->prepare('INSERT INTO tests (course_id,subject_id,slug,title,title_te,test_type,division_label,duration_mins,total_questions,total_marks,passing_marks,negative_marking,package_id,status,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([
                    $data['course_id'], $data['subject_id'], $data['slug'], $data['title'], $data['title_te'],
                    $data['test_type'], $data['division_label'], $data['duration_mins'], $data['total_questions'],
                    $data['total_marks'], $data['passing_marks'], $data['negative_marking'], $data['package_id'],
                    $active, $active,
                ]);
                $tid = (int) db()->lastInsertId();
            }
        } elseif ($id) {
            $stmt = db()->prepare('UPDATE tests SET course_id=?, subject_id=?, slug=?, title=?, title_te=?, test_type=?, division_label=?, duration_mins=?, total_questions=?, total_marks=?, passing_marks=?, negative_marking=?, package_id=?, is_active=? WHERE id=?');
            $stmt->execute([
                $data['course_id'], $data['subject_id'], $data['slug'], $data['title'], $data['title_te'],
                $data['test_type'], $data['division_label'], $data['duration_mins'], $data['total_questions'],
                $data['total_marks'], $data['passing_marks'], $data['negative_marking'], $data['package_id'],
                $active, $id,
            ]);
            $tid = $id;
        } else {
            $stmt = db()->prepare('INSERT INTO tests (course_id,subject_id,slug,title,title_te,test_type,division_label,duration_mins,total_questions,total_marks,passing_marks,negative_marking,package_id,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $data['course_id'], $data['subject_id'], $data['slug'], $data['title'], $data['title_te'],
                $data['test_type'], $data['division_label'], $data['duration_mins'], $data['total_questions'],
                $data['total_marks'], $data['passing_marks'], $data['negative_marking'], $data['package_id'],
                $active,
            ]);
            $tid = (int) db()->lastInsertId();
        }

        if (SchemaHelper::columnExists('tests', 'topic_id')) {
            $tt = $data['test_type'] ?? 'topic';
            if ($tt !== 'topic') {
                db()->prepare('UPDATE tests SET topic_id=NULL WHERE id=?')->execute([$tid]);
            } else {
                $rawTopic = $data['topic_id'] ?? null;
                $topicId = $rawTopic !== null && $rawTopic !== '' ? (int) $rawTopic : null;
                if ($topicId !== null && $topicId < 1) {
                    $topicId = null;
                }
                db()->prepare('UPDATE tests SET topic_id=? WHERE id=?')->execute([$topicId, $tid]);
            }
        }

        if (SchemaHelper::testBundleEnabled()) {
            $tt = $data['test_type'] ?? 'topic';
            if (in_array($tt, ['division', 'sub_grand', 'revision', 'grand', 'model'], true)) {
                $raw = $data['component_test_ids'] ?? [];
                $ids = is_array($raw) ? array_map('intval', $raw) : [];
                $this->syncTestBundle($tid, $ids);
            } else {
                $this->syncTestBundle($tid, []);
            }
        }

        return $tid;
    }

    public function deleteTest(int $id): void
    {
        db()->prepare('DELETE FROM tests WHERE id=?')->execute([$id]);
    }

    public function saveQuestion(array $data, ?int $id = null): int
    {
        if ($id) {
            $stmt = db()->prepare('UPDATE test_questions SET test_id=?, question_order=?, question_text=?, question_text_te=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_option=?, explanation=?, marks=?, topic_tag=? WHERE id=?');
            $stmt->execute([
                $data['test_id'], $data['question_order'], $data['question_text'], $data['question_text_te'],
                $data['option_a'], $data['option_b'], $data['option_c'], $data['option_d'],
                $data['correct_option'], $data['explanation'], $data['marks'], $data['topic_tag'], $id,
            ]);
            return $id;
        }
        $stmt = db()->prepare('INSERT INTO test_questions (test_id,question_order,question_text,question_text_te,option_a,option_b,option_c,option_d,correct_option,explanation,marks,topic_tag) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['test_id'], $data['question_order'], $data['question_text'], $data['question_text_te'],
            $data['option_a'], $data['option_b'], $data['option_c'], $data['option_d'],
            $data['correct_option'], $data['explanation'], $data['marks'], $data['topic_tag'],
        ]);
        return (int) db()->lastInsertId();
    }

    public function questionsForTest(int $testId): array
    {
        $stmt = db()->prepare('SELECT * FROM test_questions WHERE test_id=? ORDER BY question_order');
        $stmt->execute([$testId]);
        return $stmt->fetchAll();
    }

    public function deleteQuestion(int $id): void
    {
        db()->prepare('DELETE FROM test_questions WHERE id=?')->execute([$id]);
    }

    public function allStudents(): array
    {
        return db()->query("SELECT u.*,
            (SELECT COUNT(*) FROM user_subscriptions us WHERE us.user_id=u.id AND us.status='active') AS active_subs
            FROM users u WHERE u.role='student' ORDER BY u.created_at DESC")->fetchAll();
    }

    public function studentSubscriptions(int $userId): array
    {
        $stmt = db()->prepare('SELECT us.*, p.name AS package_name, p.slug FROM user_subscriptions us LEFT JOIN sub_course_packages p ON p.id = us.package_id WHERE us.user_id=?');
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function toggleSubscription(int $userId, int $packageId, bool $enable): void
    {
        if ($enable) {
            $stmt = db()->prepare('INSERT INTO user_subscriptions (user_id, package_id, status) VALUES (?,?,?) ON DUPLICATE KEY UPDATE status="active"');
            $stmt->execute([$userId, $packageId, 'active']);
        } else {
            db()->prepare('UPDATE user_subscriptions SET status="cancelled" WHERE user_id=? AND package_id=?')->execute([$userId, $packageId]);
        }
    }

    public function allPayments(): array
    {
        if (!SchemaHelper::hasTable('payments')) {
            return [];
        }

        return db()->query('SELECT pay.*, u.name AS student_name, u.email, p.name AS package_name
            FROM payments pay JOIN users u ON u.id=pay.user_id
            LEFT JOIN sub_course_packages p ON p.id=pay.package_id
            ORDER BY pay.paid_at DESC LIMIT 100')->fetchAll();
    }

    public function recordPayment(array $data): int
    {
        $stmt = db()->prepare('INSERT INTO payments (user_id, package_id, amount_inr, payment_method, transaction_ref, status, notes) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['user_id'], $data['package_id'], $data['amount_inr'],
            $data['payment_method'], $data['transaction_ref'], $data['status'], $data['notes'],
        ]);
        return (int) db()->lastInsertId();
    }

    public function allCategoriesFlat(): array
    {
        if (!SchemaHelper::hasTable('course_categories')) {
            return [];
        }

        return db()->query(
            'SELECT cc.*, c.name AS course_name, c.slug AS course_slug
            FROM course_categories cc
            JOIN courses c ON c.id = cc.course_id
            ORDER BY c.sort_order, cc.sort_order'
        )->fetchAll();
    }

    public function setEntityStatus(string $entity, int $id, bool $on): void
    {
        $v = $on ? 1 : 0;
        switch ($entity) {
            case 'course':
                if (SchemaHelper::coursesHasStatus()) {
                    db()->prepare('UPDATE courses SET status=?, is_active=? WHERE id=?')->execute([$v, $v, $id]);
                } else {
                    db()->prepare('UPDATE courses SET is_active=? WHERE id=?')->execute([$v, $id]);
                }
                break;
            case 'category':
                if (!SchemaHelper::hasTable('course_categories')) {
                    throw new InvalidArgumentException('course_categories missing');
                }
                db()->prepare('UPDATE course_categories SET status=? WHERE id=?')->execute([$v, $id]);
                break;
            case 'sub_course':
                if (!SchemaHelper::hierarchyFourTier()) {
                    throw new InvalidArgumentException('sub_courses missing');
                }
                if (SchemaHelper::columnExists('sub_courses', 'status')) {
                    db()->prepare('UPDATE sub_courses SET status=?, is_active=? WHERE id=?')->execute([$v, $v, $id]);
                } else {
                    db()->prepare('UPDATE sub_courses SET is_active=? WHERE id=?')->execute([$v, $id]);
                }
                break;
            case 'scs':
                if (!SchemaHelper::hasTable('sub_course_subjects')) {
                    throw new InvalidArgumentException('pivot missing');
                }
                if (SchemaHelper::columnExists('sub_course_subjects', 'status')) {
                    db()->prepare('UPDATE sub_course_subjects SET status=?, is_active=? WHERE id=?')->execute([$v, $v, $id]);
                } else {
                    db()->prepare('UPDATE sub_course_subjects SET is_active=? WHERE id=?')->execute([$v, $id]);
                }
                break;
            case 'subject':
                if (SchemaHelper::subjectsHasStatus()) {
                    db()->prepare('UPDATE subjects SET status=?, is_active=? WHERE id=?')->execute([$v, $v, $id]);
                } else {
                    db()->prepare('UPDATE subjects SET is_active=? WHERE id=?')->execute([$v, $id]);
                }
                break;
            case 'module':
                if (!SchemaHelper::hasTable('subject_modules')) {
                    throw new InvalidArgumentException('subject_modules missing');
                }
                db()->prepare('UPDATE subject_modules SET status=? WHERE id=?')->execute([$v, $id]);
                break;
            case 'test':
                if (SchemaHelper::testsHasStatus()) {
                    db()->prepare('UPDATE tests SET status=?, is_active=? WHERE id=?')->execute([$v, $v, $id]);
                } else {
                    db()->prepare('UPDATE tests SET is_active=? WHERE id=?')->execute([$v, $id]);
                }
                break;
            case 'topic':
                $tbl = SchemaHelper::topicsTable();
                if (!SchemaHelper::columnExists($tbl, 'status')) {
                    throw new InvalidArgumentException('topics.status not available; run migrate_dynamic_hierarchy.php');
                }
                db()->prepare("UPDATE `{$tbl}` SET status=? WHERE id=?")->execute([$v, $id]);
                break;
            default:
                throw new InvalidArgumentException('Invalid entity type');
        }
    }

    /** Full tree for hierarchy admin (includes Draft items). */
    public function adminHierarchyTree(): array
    {
        $courses = $this->allCourses();
        if (SchemaHelper::hierarchyFourTier()) {
            foreach ($courses as &$c) {
                $st = db()->prepare('SELECT * FROM sub_courses WHERE course_id=? ORDER BY sort_order ASC, id ASC');
                $st->execute([(int) $c['id']]);
                $c['sub_courses_list'] = $st->fetchAll();
                foreach ($c['sub_courses_list'] as &$sc) {
                    $st2 = db()->prepare(
                        'SELECT scs.id AS scs_row_id, scs.sort_order AS scs_sort_order, scs.status AS scs_status, scs.is_active AS scs_is_active,
                            s.*
                         FROM sub_course_subjects scs JOIN subjects s ON s.id = scs.subject_id
                         WHERE scs.sub_course_id = ? ORDER BY scs.sort_order, s.name'
                    );
                    $st2->execute([(int) $sc['id']]);
                    $sc['linked_subjects'] = $st2->fetchAll();
                    foreach ($sc['linked_subjects'] as &$row) {
                        $sid = (int) $row['id'];
                        $row['modules_list'] = [];
                        if (SchemaHelper::hasTable('subject_modules')) {
                            $st3 = db()->prepare('SELECT * FROM subject_modules WHERE subject_id=? ORDER BY sort_order, id');
                            $st3->execute([$sid]);
                            $row['modules_list'] = $st3->fetchAll();
                        }
                    }
                }
                unset($sc);
                $c['categories_list'] = [];
                $c['orphan_subjects'] = [];
            }

            return $courses;
        }

        foreach ($courses as &$c) {
            $c['categories_list'] = [];
            if (SchemaHelper::hasTable('course_categories')) {
                $st = db()->prepare('SELECT * FROM course_categories WHERE course_id=? ORDER BY sort_order, name');
                $st->execute([(int) $c['id']]);
                $c['categories_list'] = $st->fetchAll();
            }
            foreach ($c['categories_list'] as &$cat) {
                $st2 = db()->prepare('SELECT * FROM subjects WHERE category_id=? ORDER BY sort_order, name');
                $st2->execute([(int) $cat['id']]);
                $cat['subjects_list'] = $st2->fetchAll();
                foreach ($cat['subjects_list'] as &$sub) {
                    $sub['modules_list'] = [];
                    if (SchemaHelper::hasTable('subject_modules')) {
                        $st3 = db()->prepare('SELECT * FROM subject_modules WHERE subject_id=? ORDER BY sort_order, id');
                        $st3->execute([(int) $sub['id']]);
                        $sub['modules_list'] = $st3->fetchAll();
                    }
                }
            }
            $st4 = db()->prepare('SELECT * FROM subjects WHERE course_id=? AND category_id IS NULL ORDER BY sort_order, name');
            $st4->execute([(int) $c['id']]);
            $c['orphan_subjects'] = $st4->fetchAll();
            foreach ($c['orphan_subjects'] as &$os) {
                $os['modules_list'] = [];
                if (SchemaHelper::hasTable('subject_modules')) {
                    $st5 = db()->prepare('SELECT * FROM subject_modules WHERE subject_id=? ORDER BY sort_order, id');
                    $st5->execute([(int) $os['id']]);
                    $os['modules_list'] = $st5->fetchAll();
                }
            }
        }

        return $courses;
    }

    /** Sub-course row joined with parent course (slug + names). */
    public function resolveSubCourseByCourseAndSlug(string $courseSlug, string $subCourseSlug): ?array
    {
        if (!SchemaHelper::hierarchyFourTier()) {
            return null;
        }
        $st = db()->prepare(
            'SELECT sc.*, c.id AS parent_course_id, c.name AS course_name, c.slug AS course_slug,
                    c.name_te AS course_name_te
             FROM sub_courses sc
             INNER JOIN courses c ON c.id = sc.course_id
             WHERE c.slug = ? AND sc.slug = ?
             LIMIT 1'
        );
        $st->execute([$courseSlug, $subCourseSlug]);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    /**
     * Programme workspace: subjects linked to sub-course, each with tests grouped by 5-tier type.
     *
     * @return list<array<string,mixed>>
     */
    public function subjectsWithTieredTestsForWorkspace(int $subCourseId): array
    {
        $st = db()->prepare(
            'SELECT s.*, scs.id AS scs_row_id,
                    scs.status AS scs_status, scs.is_active AS scs_is_active
             FROM sub_course_subjects scs
             INNER JOIN subjects s ON s.id = scs.subject_id
             WHERE scs.sub_course_id = ?
             ORDER BY scs.sort_order, s.sort_order, s.name'
        );
        $st->execute([$subCourseId]);
        $subjects = $st->fetchAll(PDO::FETCH_ASSOC);
        $tierOrder = ['topic', 'division', 'revision', 'grand', 'model'];

        $testSt = db()->prepare(
            'SELECT * FROM tests WHERE subject_id = ? ORDER BY test_type, sort_order, id'
        );

        foreach ($subjects as &$sub) {
            $sid = (int) $sub['id'];
            $testSt->execute([$sid]);
            $tests = $testSt->fetchAll(PDO::FETCH_ASSOC);
            $grouped = [];
            foreach ($tierOrder as $t) {
                $grouped[$t] = [];
            }
            foreach ($tests as $t) {
                $ty = isset($t['test_type']) && is_string($t['test_type']) ? $t['test_type'] : 'topic';
                if (!isset($grouped[$ty])) {
                    $grouped[$ty] = [];
                }
                $grouped[$ty][] = $t;
            }
            $sub['tests_by_tier'] = $grouped;
        }
        unset($sub);

        return $subjects;
    }

    public function setAllTestsForSubjectLive(int $subjectId, bool $on): void
    {
        $v = $on ? 1 : 0;
        if (SchemaHelper::testsHasStatus()) {
            db()->prepare('UPDATE tests SET status=?, is_active=? WHERE subject_id=?')->execute([$v, $v, $subjectId]);
        } else {
            db()->prepare('UPDATE tests SET is_active=? WHERE subject_id=?')->execute([$v, $subjectId]);
        }
    }

    public function setTestsLiveForSubjectTier(int $subjectId, string $testType, bool $on): void
    {
        $allowed = ['topic', 'division', 'revision', 'grand', 'model'];
        if (!in_array($testType, $allowed, true)) {
            throw new InvalidArgumentException('Invalid test tier');
        }
        $v = $on ? 1 : 0;
        if (SchemaHelper::testsHasStatus()) {
            db()->prepare('UPDATE tests SET status=?, is_active=? WHERE subject_id=? AND test_type=?')->execute([$v, $v, $subjectId, $testType]);
        } else {
            db()->prepare('UPDATE tests SET is_active=? WHERE subject_id=? AND test_type=?')->execute([$v, $subjectId, $testType]);
        }
    }

    /** Subject visibility plus all tests under that subject (same visibility). */
    public function setSubjectVisibilityWithCascade(int $subjectId, bool $on): void
    {
        $this->setEntityStatus('subject', $subjectId, $on);
        $this->setAllTestsForSubjectLive($subjectId, $on);
    }

    public function handleUpload(string $field, string $subdir): ?string
    {
        if (empty($_FILES[$field]['name'])) {
            return null;
        }

        if ($subdir === 'branding') {
            return ImageUploadService::storeFromFileArray(
                $_FILES[$field],
                ImageUploadService::PURPOSE_BRANDING,
                null
            );
        }

        if (in_array($subdir, ['course', 'sub_course', 'subject'], true)) {
            return ImageUploadService::storeFromFileArray(
                $_FILES[$field],
                ImageUploadService::PURPOSE_COVER,
                $subdir
            );
        }

        return $this->handleLegacyUpload($field, $subdir);
    }

    private function handleLegacyUpload(string $field, string $subdir): ?string
    {
        if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $safe = preg_replace('/[^a-z0-9_-]/', '', strtolower($subdir)) ?: 'misc';
        $dir = dirname(__DIR__) . '/uploads/' . $safe;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $ext = strtolower(pathinfo((string) $_FILES[$field]['name'], PATHINFO_EXTENSION));
        $name = uniqid('file_', true) . ($ext !== '' ? '.' . $ext : '');
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
            return null;
        }
        @chmod($dest, 0644);

        return 'uploads/' . $safe . '/' . $name;
    }

    // ─── Content Manager (4-tier cascade + topic notes / sub-topics) ───

    /** @return list<array<string,mixed>> */
    public function contentManagerMainCourses(): array
    {
        return $this->contentManagerMainCoursesForSort();
    }

    /** All main courses for admin sort panel (includes inactive). */
    public function contentManagerMainCoursesForSort(): array
    {
        $mainTable = SchemaHelper::mainCourseImageTable();
        $img = SchemaHelper::imagePathEnabled($mainTable) ? ', image_path' : '';
        $from = $mainTable === 'main_courses' && SchemaHelper::hasTable('main_courses')
            ? 'main_courses'
            : 'courses';

        $order = SchemaHelper::sqlOrderBySort('sort_order', 'id');

        return db()->query(
            "SELECT id, slug, name, name_te, description, sort_order{$img} FROM `{$from}` ORDER BY {$order}"
        )->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function contentManagerSubCourses(int $courseId): array
    {
        if ($courseId < 1 || !SchemaHelper::hierarchyFourTier()) {
            return [];
        }
        $courseId = $this->resolveContentManagerCourseId($courseId);
        $img = SchemaHelper::imagePathEnabled('sub_courses') ? ', image_path' : '';
        $st = SchemaHelper::columnExists('sub_courses', 'status')
            ? db()->prepare(
                "SELECT id, slug, name, name_te, description, sort_order{$img} FROM sub_courses
                 WHERE course_id=? AND COALESCE(status, is_active, 1)=1 ORDER BY " . SchemaHelper::sqlOrderBySort('sort_order', 'id')
            )
            : db()->prepare(
                "SELECT id, slug, name, name_te, description, sort_order{$img} FROM sub_courses
                 WHERE course_id=? AND is_active=1 ORDER BY " . SchemaHelper::sqlOrderBySort('sort_order', 'id')
            );
        $st->execute([$courseId]);

        return $st->fetchAll();
    }

    /** All sub-courses for admin sort panel (includes inactive). */
    public function contentManagerSubCoursesForSort(int $courseId): array
    {
        if ($courseId < 1 || !SchemaHelper::hierarchyFourTier()) {
            return [];
        }
        $courseId = $this->resolveContentManagerCourseId($courseId);
        $img = SchemaHelper::imagePathEnabled('sub_courses') ? ', image_path' : '';
        $order = SchemaHelper::sqlOrderBySort('sort_order', 'id');
        $st = db()->prepare(
            "SELECT id, slug, name, name_te, description, sort_order{$img}
             FROM sub_courses WHERE course_id=? ORDER BY {$order}"
        );
        $st->execute([$courseId]);

        return $st->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function contentManagerSubjects(int $subCourseId): array
    {
        if ($subCourseId < 1 || !SchemaHelper::hierarchyFourTier()) {
            return [];
        }
        $img = SchemaHelper::imagePathEnabled('subjects') ? ', s.image_path' : '';
        $st = db()->prepare(
            "SELECT s.id, s.slug, s.name, s.name_te, scs.sort_order,
                    COALESCE(scs.status, scs.is_active, 1) AS is_live{$img}
             FROM subjects s
             INNER JOIN sub_course_subjects scs ON scs.subject_id = s.id
             WHERE scs.sub_course_id = ?
             ORDER BY " . SchemaHelper::sqlOrderBySort('scs.sort_order', 's.id')
        );
        $st->execute([$subCourseId]);

        return $st->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function contentManagerTopics(int $subjectId): array
    {
        if ($subjectId < 1) {
            return [];
        }
        $tbl = SchemaHelper::topicsTable();
        $status = SchemaHelper::columnExists($tbl, 'status') ? '' : '';
        $st = db()->prepare(
            "SELECT id, slug, title, title_te, sort_order,
                    COALESCE(question_count, 50) AS question_count,
                    COALESCE(has_sub_topics, 0) AS has_sub_topics
             FROM `{$tbl}` WHERE subject_id=? ORDER BY " . SchemaHelper::sqlOrderBySort('sort_order', 'id')
        );
        $st->execute([$subjectId]);

        return $st->fetchAll();
    }

    /**
     * Master subject catalog search (optionally scoped to sub-course link state).
     *
     * @return list<array<string,mixed>>
     */
    public function searchSubjectsMaster(string $q, int $subCourseId = 0, int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        $q = trim($q);
        $like = '%' . $q . '%';
        $order = SchemaHelper::sqlOrderBySort('s.sort_order', 's.id');

        if ($subCourseId > 0 && SchemaHelper::hierarchyFourTier() && SchemaHelper::hasTable('sub_course_subjects')) {
            $sql = "SELECT s.id, s.slug, s.name, s.name_te,
                    CASE WHEN scs.subject_id IS NOT NULL THEN 1 ELSE 0 END AS is_linked
                    FROM subjects s
                    LEFT JOIN sub_course_subjects scs ON scs.subject_id = s.id AND scs.sub_course_id = ?
                    WHERE 1=1";
            $params = [$subCourseId];
            if ($q !== '') {
                $sql .= " AND (s.name LIKE ? OR COALESCE(s.name_te, '') LIKE ? OR s.slug LIKE ?)";
                array_push($params, $like, $like, $like);
            }
            $sql .= " ORDER BY is_linked DESC, {$order} LIMIT {$limit}";
            $st = db()->prepare($sql);
            $st->execute($params);

            return $st->fetchAll();
        }

        $sql = "SELECT s.id, s.slug, s.name, s.name_te, 0 AS is_linked FROM subjects s WHERE 1=1";
        $params = [];
        if ($q !== '') {
            $sql .= " AND (s.name LIKE ? OR COALESCE(s.name_te, '') LIKE ? OR s.slug LIKE ?)";
            array_push($params, $like, $like, $like);
        }
        $sql .= " ORDER BY {$order} LIMIT {$limit}";
        $st = db()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll();
    }

    public function linkSubjectToSubCourse(int $subCourseId, int $subjectId): void
    {
        if ($subCourseId < 1 || $subjectId < 1) {
            throw new InvalidArgumentException('sub_course_id and subject_id required');
        }
        if (!SchemaHelper::hierarchyFourTier() || !SchemaHelper::hasTable('sub_course_subjects')) {
            throw new RuntimeException('Four-tier hierarchy required to link subjects.');
        }

        $chk = db()->prepare(
            'SELECT 1 FROM sub_course_subjects WHERE sub_course_id=? AND subject_id=? LIMIT 1'
        );
        $chk->execute([$subCourseId, $subjectId]);
        if ($chk->fetchColumn()) {
            $this->ensureSubjectPivotLive($subCourseId, $subjectId);

            return;
        }

        $sortSt = db()->prepare(
            'SELECT COALESCE(MAX(sort_order), -1) + 1 FROM sub_course_subjects WHERE sub_course_id=?'
        );
        $sortSt->execute([$subCourseId]);
        $sort = (int) $sortSt->fetchColumn();

        if (SchemaHelper::columnExists('sub_course_subjects', 'status')
            && SchemaHelper::columnExists('sub_course_subjects', 'is_active')) {
            db()->prepare(
                'INSERT INTO sub_course_subjects (sub_course_id, subject_id, sort_order, status, is_active) VALUES (?,?,?,1,1)'
            )->execute([$subCourseId, $subjectId, $sort]);
        } elseif (SchemaHelper::columnExists('sub_course_subjects', 'is_active')) {
            db()->prepare(
                'INSERT INTO sub_course_subjects (sub_course_id, subject_id, sort_order, is_active) VALUES (?,?,?,1)'
            )->execute([$subCourseId, $subjectId, $sort]);
        } else {
            db()->prepare(
                'INSERT INTO sub_course_subjects (sub_course_id, subject_id, sort_order) VALUES (?,?,?)'
            )->execute([$subCourseId, $subjectId, $sort]);
        }

        $this->ensureSubjectPivotLive($subCourseId, $subjectId);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function searchTopicsForSubject(int $subjectId, string $q, int $limit = 50): array
    {
        if ($subjectId < 1) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        $q = trim($q);
        $tbl = SchemaHelper::topicsTable();
        $order = SchemaHelper::sqlOrderBySort('sort_order', 'id');
        $sql = "SELECT id, slug, title, title_te, sort_order,
                COALESCE(has_sub_topics, 0) AS has_sub_topics
                FROM `{$tbl}` WHERE subject_id=?";
        $params = [$subjectId];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql .= " AND (title LIKE ? OR COALESCE(title_te, '') LIKE ? OR slug LIKE ?)";
            array_push($params, $like, $like, $like);
        }
        $sql .= " ORDER BY {$order} LIMIT {$limit}";
        $st = db()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function searchSubTopicsForTopic(int $topicId, string $q, int $limit = 50): array
    {
        if ($topicId < 1 || !SchemaHelper::hasTable('sub_topics')) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        $q = trim($q);
        $sql = 'SELECT id, slug, sub_topic_name, sub_topic_name_te, sort_order, question_count
                FROM sub_topics WHERE topic_id=?';
        $params = [$topicId];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql .= " AND (sub_topic_name LIKE ? OR COALESCE(sub_topic_name_te, '') LIKE ? OR slug LIKE ?)";
            array_push($params, $like, $like, $like);
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC LIMIT ' . $limit;
        $st = db()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll();
    }

    public function createSubTopicQuick(int $topicId, string $name, ?string $nameTe = null): int
    {
        if ($topicId < 1 || trim($name) === '') {
            throw new InvalidArgumentException('topic_id and sub_topic name required');
        }
        if (!SchemaHelper::hasTable('sub_topics')) {
            throw new RuntimeException('sub_topics table not available.');
        }
        $name = trim($name);
        $nameTe = $nameTe !== null && trim($nameTe) !== '' ? trim($nameTe) : null;
        $slug = slugify($name);
        $sortSt = db()->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM sub_topics WHERE topic_id=?');
        $sortSt->execute([$topicId]);
        $sort = (int) $sortSt->fetchColumn();
        db()->prepare(
            'INSERT INTO sub_topics (topic_id, sub_topic_name, sub_topic_name_te, slug, question_count, sort_order, status)
             VALUES (?,?,?,?,50,?,1)'
        )->execute([$topicId, $name, $nameTe, $slug, $sort]);
        $newId = (int) db()->lastInsertId();
        $tbl = SchemaHelper::topicsTable();
        if (SchemaHelper::columnExists($tbl, 'has_sub_topics')) {
            db()->prepare("UPDATE `{$tbl}` SET has_sub_topics=1 WHERE id=?")->execute([$topicId]);
        }

        return $newId;
    }

    /** @return array<string,mixed>|null */
    public function getTopicContentManager(int $topicId): ?array
    {
        if ($topicId < 1) {
            return null;
        }
        $tbl = SchemaHelper::topicsTable();
        $st = db()->prepare("SELECT * FROM `{$tbl}` WHERE id=? LIMIT 1");
        $st->execute([$topicId]);
        $topic = $st->fetch();
        if (!$topic) {
            return null;
        }
        if (SchemaHelper::contentManagerEnabled()) {
            if (empty($topic['notes_content']) && !empty($topic['content'])) {
                $topic['notes_content'] = $topic['content'];
            }
            $topic['sub_topics'] = $this->listSubTopicsForTopic($topicId);
            $bindSubId = (int) ($topic['notes_bind_sub_topic_id'] ?? 0);
            $topic['notes_bind'] = 'topic';
            $topic['active_sub_topic_id'] = null;
            if (!empty($topic['has_sub_topics']) && $topic['sub_topics']) {
                $primary = null;
                if ($bindSubId > 0) {
                    foreach ($topic['sub_topics'] as $st) {
                        if ((int) $st['id'] === $bindSubId) {
                            $primary = $st;
                            break;
                        }
                    }
                }
                if (!$primary) {
                    $primary = $topic['sub_topics'][0];
                }
                $topic['active_sub_topic_id'] = (int) $primary['id'];
                $topic['notes_content'] = (string) ($primary['sub_notes_content'] ?? '');
                $topic['notes_bind'] = 'sub_topic';
            }
            $topic['exam_suite'] = $this->getTopicExamSuite($topicId, null);
            if (SchemaHelper::topicMcqContentEnabled() && empty($topic['mcq_content'])) {
                $topic['mcq_content'] = '';
            }
            if (SchemaHelper::topicNotesEnabledColumn()) {
                $topic['notes_enabled'] = (int) ($topic['notes_enabled'] ?? 1);
            } else {
                $topic['notes_enabled'] = !empty($topic['notes_content']) || $topic['notes_bind'] === 'sub_topic' ? 1 : 0;
            }
        } else {
            $topic['notes_content'] = $topic['content'] ?? '';
            $topic['has_sub_topics'] = 0;
            $topic['question_count'] = 50;
            $topic['sub_topics'] = [];
            $topic['exam_suite'] = $this->defaultTopicExamSuiteRows();
        }

        return $topic;
    }

    /** @return list<array<string,mixed>> */
    public function listSubTopicsForTopic(int $topicId): array
    {
        if ($topicId < 1 || !SchemaHelper::hasTable('sub_topics')) {
            return [];
        }
        $st = db()->prepare(
            'SELECT * FROM sub_topics WHERE topic_id=? ORDER BY sort_order ASC, id ASC'
        );
        $st->execute([$topicId]);

        return $st->fetchAll();
    }

    /**
     * @param array{has_sub_topics?:bool|int,question_count?:int,notes_content?:string,mcq_content?:string,sub_topics?:list<array<string,mixed>>} $data
     */
    public function saveTopicContentManager(int $topicId, array $data): void
    {
        if ($topicId < 1) {
            throw new InvalidArgumentException('topic_id required');
        }
        $tbl = SchemaHelper::topicsTable();
        $hasSub = !empty($data['has_sub_topics']) ? 1 : 0;
        $notesEnabled = array_key_exists('notes_enabled', $data)
            ? (!empty($data['notes_enabled']) ? 1 : 0)
            : 1;
        $qCount = max(1, min(999, (int) ($data['question_count'] ?? 50)));
        $notes = $notesEnabled ? (string) ($data['notes_content'] ?? '') : '';
        $mcq = array_key_exists('mcq_content', $data) ? (string) $data['mcq_content'] : null;
        $incoming = $data['sub_topics'] ?? [];
        if (!is_array($incoming)) {
            $incoming = [];
        }

        $topicNotes = $hasSub ? null : ($notes !== '' ? $notes : null);
        $bindSubId = null;

        if ($hasSub && $notes !== '' && $incoming !== []) {
            $incoming[0]['sub_notes_content'] = $notes;
        } elseif ($hasSub && $notes !== '' && $incoming === []) {
            $name = trim((string) ($data['primary_sub_topic_name'] ?? 'Sub-topic'));
            $incoming[] = [
                'sub_topic_name' => $name,
                'sub_topic_name_te' => $name,
                'sub_notes_content' => $notes,
                'question_count' => 50,
            ];
        }

        if (SchemaHelper::contentManagerEnabled()) {
            $sets = ['has_sub_topics=?', 'question_count=?', 'notes_content=?'];
            $params = [$hasSub, $qCount, $topicNotes];
            if (SchemaHelper::topicMcqContentEnabled() && $mcq !== null) {
                $sets[] = 'mcq_content=?';
                $params[] = $mcq !== '' ? $mcq : null;
            }
            if (SchemaHelper::topicNotesEnabledColumn()) {
                $sets[] = 'notes_enabled=?';
                $params[] = $notesEnabled;
            }
            if (SchemaHelper::topicCanDownloadEnabled() && array_key_exists('can_download', $data)) {
                $sets[] = 'can_download=?';
                $params[] = !empty($data['can_download']) ? 1 : 0;
            }
            if (SchemaHelper::topicNotesBindEnabled()) {
                $sets[] = 'notes_bind_sub_topic_id=?';
                $params[] = null;
            }
            if (SchemaHelper::columnExists($tbl, 'content')) {
                $sets[] = 'content=?';
                $params[] = $topicNotes;
            }
            $params[] = $topicId;
            db()->prepare('UPDATE `' . $tbl . '` SET ' . implode(', ', $sets) . ' WHERE id=?')->execute($params);
        } elseif (SchemaHelper::columnExists($tbl, 'content')) {
            db()->prepare("UPDATE `{$tbl}` SET content=? WHERE id=?")->execute([$topicNotes, $topicId]);
        }

        if (isset($data['exam_suite']) && is_array($data['exam_suite']) && SchemaHelper::topicExamSuiteEnabled()) {
            $this->saveTopicExamSuite($topicId, null, $data['exam_suite']);
        }

        if (!SchemaHelper::hasTable('sub_topics')) {
            return;
        }

        if (!$hasSub) {
            db()->prepare('DELETE FROM sub_topics WHERE topic_id=?')->execute([$topicId]);
            if (SchemaHelper::topicNotesBindEnabled()) {
                db()->prepare("UPDATE `{$tbl}` SET notes_bind_sub_topic_id=NULL WHERE id=?")->execute([$topicId]);
            }

            return;
        }

        $keepIds = [];
        $sort = 0;
        foreach ($incoming as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['sub_topic_name'] ?? $row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $subId = isset($row['id']) && (int) $row['id'] > 0 ? (int) $row['id'] : null;
            $subQ = max(1, min(999, (int) ($row['question_count'] ?? 50)));
            $subNotes = (string) ($row['sub_notes_content'] ?? $row['notes'] ?? '');
            $slug = slugify((string) ($row['slug'] ?? $name));
            $nameTe = trim((string) ($row['sub_topic_name_te'] ?? ''));

            if ($subId) {
                db()->prepare(
                    'UPDATE sub_topics SET sub_topic_name=?, sub_topic_name_te=?, slug=?, question_count=?, sub_notes_content=?, sort_order=? WHERE id=? AND topic_id=?'
                )->execute([
                    $name, $nameTe !== '' ? $nameTe : null, $slug, $subQ,
                    $subNotes !== '' ? $subNotes : null, $sort, $subId, $topicId,
                ]);
                $keepIds[] = $subId;
            } else {
                db()->prepare(
                    'INSERT INTO sub_topics (topic_id, sub_topic_name, sub_topic_name_te, slug, question_count, sub_notes_content, sort_order, status)
                     VALUES (?,?,?,?,?,?,?,1)'
                )->execute([
                    $topicId, $name, $nameTe !== '' ? $nameTe : null, $slug, $subQ,
                    $subNotes !== '' ? $subNotes : null, $sort,
                ]);
                $keepIds[] = (int) db()->lastInsertId();
            }
            ++$sort;
        }

        if ($keepIds === []) {
            db()->prepare('DELETE FROM sub_topics WHERE topic_id=?')->execute([$topicId]);
            if (SchemaHelper::topicNotesBindEnabled()) {
                db()->prepare("UPDATE `{$tbl}` SET notes_bind_sub_topic_id=NULL WHERE id=?")->execute([$topicId]);
            }

            return;
        }
        $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
        $params = array_merge([$topicId], $keepIds);
        db()->prepare("DELETE FROM sub_topics WHERE topic_id=? AND id NOT IN ({$placeholders})")->execute($params);

        $bindSubId = (int) $keepIds[0];
        if (SchemaHelper::topicNotesBindEnabled() && $bindSubId > 0) {
            db()->prepare("UPDATE `{$tbl}` SET notes_bind_sub_topic_id=? WHERE id=?")->execute([$bindSubId, $topicId]);
        }
    }

    public function createTopicQuick(int $subjectId, string $title, ?string $titleTe = null): int
    {
        if ($subjectId < 1 || trim($title) === '') {
            throw new InvalidArgumentException('subject_id and title required');
        }
        $data = [
            'subject_id' => $subjectId,
            'slug' => slugify($title),
            'title' => trim($title),
            'title_te' => $titleTe ?? '',
            'summary' => '',
            'duration_mins' => 30,
            'sort_order' => 0,
            'is_free_preview' => 0,
            'is_active' => 1,
        ];

        return $this->saveTopic($data, null);
    }

    /** @return array{course_id:int,subject_id:int,topic_id:int,course_slug:string,topic_title:string}|null */
    public function resolveTopicHierarchy(int $topicId): ?array
    {
        if ($topicId < 1) {
            return null;
        }
        $tbl = SchemaHelper::topicsTable();
        $st = db()->prepare(
            "SELECT t.id AS topic_id, t.title AS topic_title, t.subject_id, s.slug AS subject_slug,
                    COALESCE(s.course_id, sc.course_id) AS course_id, c.slug AS course_slug
             FROM `{$tbl}` t
             JOIN subjects s ON s.id = t.subject_id
             LEFT JOIN sub_course_subjects scs ON scs.subject_id = s.id
             LEFT JOIN sub_courses sc ON sc.id = scs.sub_course_id
             LEFT JOIN courses c ON c.id = COALESCE(s.course_id, sc.course_id)
             WHERE t.id = ?
             LIMIT 1"
        );
        $st->execute([$topicId]);
        $row = $st->fetch();
        if (!$row || empty($row['course_id'])) {
            return null;
        }

        return [
            'course_id' => (int) $row['course_id'],
            'subject_id' => (int) $row['subject_id'],
            'topic_id' => (int) $row['topic_id'],
            'course_slug' => (string) ($row['course_slug'] ?? ''),
            'topic_title' => (string) ($row['topic_title'] ?? ''),
        ];
    }

    /** @return list<array<string,mixed>> */
    public function getTopicExamSuite(int $topicId, ?int $subTopicId = null): array
    {
        if ($topicId < 1 || !SchemaHelper::topicExamSuiteEnabled()) {
            return $this->defaultTopicExamSuiteRows();
        }
        $sql = 'SELECT * FROM topic_exam_suite WHERE topic_id=? AND ';
        if ($subTopicId !== null && $subTopicId > 0) {
            $sql .= 'sub_topic_id=?';
            $params = [$topicId, $subTopicId];
        } else {
            $sql .= 'sub_topic_id IS NULL';
            $params = [$topicId];
        }
        $sql .= ' ORDER BY ' . SchemaHelper::sqlOrderBySort('sort_order', 'id');
        $st = db()->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
        if ($rows) {
            return $rows;
        }

        return $this->defaultTopicExamSuiteRows();
    }

    /** @return list<array<string,mixed>> */
    public function defaultTopicExamSuiteRows(): array
    {
        require_once dirname(__DIR__) . '/includes/admin/content_manager_defaults.php';
        $out = [];
        foreach (content_manager_exam_suite_templates() as $tpl) {
            $out[] = [
                'id' => null,
                'suite_key' => $tpl['suite_key'],
                'custom_title' => $tpl['label_en'],
                'custom_title_te' => $tpl['label_te'],
                'question_count' => 50,
                'total_marks' => 50,
                'test_id' => null,
                'is_enabled' => 1,
                'is_required' => 1,
                'sort_order' => $tpl['sort_order'],
            ];
        }

        return $out;
    }

    /**
     * @param list<array<string,mixed>> $suites
     */
    public function saveTopicExamSuite(int $topicId, ?int $subTopicId, array $suites): void
    {
        if ($topicId < 1 || !SchemaHelper::topicExamSuiteEnabled()) {
            return;
        }
        $ctx = $this->resolveTopicHierarchy($topicId);
        if (!$ctx) {
            throw new RuntimeException('Could not resolve course/subject for topic.');
        }

        require_once dirname(__DIR__) . '/includes/admin/content_manager_defaults.php';
        $typeMap = content_manager_suite_key_to_test_type();
        $allowedKeys = array_keys($typeMap);

        if ($subTopicId !== null && $subTopicId > 0) {
            db()->prepare('DELETE FROM topic_exam_suite WHERE topic_id=? AND sub_topic_id=?')->execute([$topicId, $subTopicId]);
        } else {
            db()->prepare('DELETE FROM topic_exam_suite WHERE topic_id=? AND sub_topic_id IS NULL')->execute([$topicId]);
        }

        $hasRequiredCol = SchemaHelper::columnExists('topic_exam_suite', 'is_required');
        $insSql = 'INSERT INTO topic_exam_suite (topic_id, sub_topic_id, suite_key, custom_title, custom_title_te, question_count, total_marks, test_id, is_enabled, sort_order';
        if ($hasRequiredCol) {
            $insSql .= ', is_required';
        }
        $insSql .= ') VALUES (' . implode(',', array_fill(0, $hasRequiredCol ? 11 : 10, '?')) . ')';
        $ins = db()->prepare($insSql);

        foreach ($suites as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = preg_replace('/[^a-z_]/', '', (string) ($row['suite_key'] ?? ''));
            if (!in_array($key, $allowedKeys, true)) {
                continue;
            }
            if (array_key_exists('is_required', $row)) {
                $enabled = !empty($row['is_required']) ? 1 : 0;
            } else {
                $enabled = !empty($row['is_enabled']) ? 1 : 0;
            }
            $title = trim((string) ($row['custom_title'] ?? ''));
            $titleTe = trim((string) ($row['custom_title_te'] ?? ''));
            if ($title === '') {
                foreach (content_manager_exam_suite_templates() as $tpl) {
                    if ($tpl['suite_key'] === $key) {
                        $title = $tpl['label_en'];
                        $titleTe = $titleTe !== '' ? $titleTe : $tpl['label_te'];
                        break;
                    }
                }
            }
            $qCount = max(1, min(999, (int) ($row['question_count'] ?? 50)));
            $marks = max(1, min(999, (int) ($row['total_marks'] ?? $qCount)));
            $testId = isset($row['test_id']) && (int) $row['test_id'] > 0 ? (int) $row['test_id'] : null;

            if ($enabled) {
                $testType = $typeMap[$key] ?? 'topic';
                $slugBase = slugify($title ?: $key) . '-t' . $topicId . '-' . $key;
                $testId = $this->upsertSuitePlatformTest(
                    $testId,
                    $ctx,
                    $topicId,
                    $testType,
                    $title,
                    $titleTe,
                    $slugBase,
                    $qCount,
                    $marks
                );
            }

            $params = [
                $topicId,
                ($subTopicId !== null && $subTopicId > 0) ? $subTopicId : null,
                $key,
                $title,
                $titleTe !== '' ? $titleTe : null,
                $qCount,
                $marks,
                $testId,
                $enabled,
                (int) ($row['sort_order'] ?? $i),
            ];
            if ($hasRequiredCol) {
                $params[] = $enabled;
            }
            $ins->execute($params);
        }
    }

    private function upsertSuitePlatformTest(
        ?int $existingTestId,
        array $ctx,
        int $topicId,
        string $testType,
        string $title,
        string $titleTe,
        string $slug,
        int $questionCount,
        int $totalMarks
    ): int {
        $data = [
            'course_id' => $ctx['course_id'],
            'subject_id' => $ctx['subject_id'],
            'topic_id' => $testType === 'topic' ? $topicId : null,
            'slug' => $slug,
            'title' => $title,
            'title_te' => $titleTe,
            'test_type' => $testType,
            'division_label' => null,
            'duration_mins' => max(15, (int) ceil($questionCount * 0.75)),
            'total_questions' => $questionCount,
            'total_marks' => $totalMarks,
            'passing_marks' => (int) max(1, floor($totalMarks * 0.4)),
            'negative_marking' => 0.25,
            'package_id' => null,
            'is_active' => 1,
        ];

        return $this->saveTest($data, $existingTestId);
    }

    public function cmSetImagePath(string $entity, int $id, string $path): void
    {
        if ($id < 1 || $path === '') {
            throw new InvalidArgumentException('Invalid image target');
        }
        $map = [
            'course' => SchemaHelper::mainCourseImageTable(),
            'sub_course' => 'sub_courses',
            'subject' => 'subjects',
        ];
        if (!isset($map[$entity]) || !SchemaHelper::imagePathEnabled($map[$entity])) {
            throw new RuntimeException('image_path column not available for ' . $entity);
        }
        $table = $map[$entity];
        $st = db()->prepare("SELECT image_path FROM `{$table}` WHERE id=? LIMIT 1");
        $st->execute([$id]);
        $oldPath = $st->fetchColumn();
        if (is_string($oldPath) && $oldPath !== '' && $oldPath !== $path) {
            ImageUploadService::deleteIfStored($oldPath);
        }
        db()->prepare("UPDATE `{$table}` SET image_path=? WHERE id=?")->execute([$path, $id]);
    }

    /** Map Content Manager main-course selection to writable courses.id */
    public function resolveContentManagerCourseId(int $selectedId): int
    {
        if ($selectedId < 1) {
            throw new InvalidArgumentException('Main course selection required');
        }

        return SchemaHelper::resolveCatalogCourseId($selectedId);
    }

    private function preserveEntitySlug(string $table, ?int $id, string $requestedSlug, string $name): string
    {
        $requestedSlug = trim($requestedSlug);
        if ($id !== null && $id > 0 && $requestedSlug === '') {
            $st = db()->prepare("SELECT slug FROM `{$table}` WHERE id=? LIMIT 1");
            $st->execute([$id]);
            $existing = $st->fetchColumn();
            if (is_string($existing) && $existing !== '') {
                return $existing;
            }
        }
        if ($requestedSlug !== '') {
            return slugify($requestedSlug);
        }

        return slugify($name);
    }

    public function cmSaveMainCourse(array $data, ?int $id = null): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('name required');
        }

        return $this->saveCourse([
            'slug' => slugify((string) ($data['slug'] ?? $name)),
            'name' => $name,
            'name_te' => trim((string) ($data['name_te'] ?? '')),
            'region' => trim((string) ($data['region'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ], $id);
    }

    public function cmSaveSubCourse(array $data, ?int $id = null): int
    {
        $rawCourseId = (int) ($data['course_id'] ?? 0);
        $courseId = $this->resolveContentManagerCourseId($rawCourseId);
        $name = trim((string) ($data['name'] ?? ''));
        if ($courseId < 1 || $name === '') {
            throw new InvalidArgumentException('course_id and name required');
        }

        $slug = $this->preserveEntitySlug('sub_courses', $id, (string) ($data['slug'] ?? ''), $name);

        return $this->saveSubCourse([
            'course_id' => $courseId,
            'slug' => $slug,
            'name' => $name,
            'name_te' => trim((string) ($data['name_te'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => array_key_exists('is_active', $data) ? (!empty($data['is_active']) ? 1 : 0) : 1,
        ], $id);
    }

    public function cmSaveSubjectForSubCourse(array $data, ?int $id = null): int
    {
        $subCourseId = (int) ($data['sub_course_id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        if ($subCourseId < 1 || $name === '') {
            throw new InvalidArgumentException('sub_course_id and name required');
        }
        $st = db()->prepare('SELECT course_id FROM sub_courses WHERE id=? LIMIT 1');
        $st->execute([$subCourseId]);
        $courseId = (int) $st->fetchColumn();
        if ($courseId < 1) {
            throw new InvalidArgumentException('sub_course not found');
        }
        $slug = $this->preserveEntitySlug('subjects', $id, (string) ($data['slug'] ?? ''), $name);
        $subjectId = $this->saveSubject([
            'course_id' => $courseId,
            'slug' => $slug,
            'name' => $name,
            'name_te' => trim((string) ($data['name_te'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => array_key_exists('is_active', $data) ? (!empty($data['is_active']) ? 1 : 0) : 1,
            'sub_course_ids' => [$subCourseId],
        ], $id);

        $this->ensureSubjectPivotLive($subCourseId, $subjectId);

        return $subjectId;
    }

    public function ensureSubjectPivotLive(int $subCourseId, int $subjectId): void
    {
        if ($subCourseId < 1 || $subjectId < 1 || !SchemaHelper::hierarchyFourTier()) {
            return;
        }
        if (SchemaHelper::columnExists('sub_course_subjects', 'status')) {
            db()->prepare(
                'UPDATE sub_course_subjects SET status=1, is_active=1 WHERE sub_course_id=? AND subject_id=?'
            )->execute([$subCourseId, $subjectId]);
        } elseif (SchemaHelper::columnExists('sub_course_subjects', 'is_active')) {
            db()->prepare(
                'UPDATE sub_course_subjects SET is_active=1 WHERE sub_course_id=? AND subject_id=?'
            )->execute([$subCourseId, $subjectId]);
        }
    }

    public function cmSetSubjectLive(int $subCourseId, int $subjectId, bool $live): void
    {
        if ($subCourseId < 1 || $subjectId < 1) {
            throw new InvalidArgumentException('sub_course_id and subject_id required');
        }
        $val = $live ? 1 : 0;
        if (SchemaHelper::columnExists('sub_course_subjects', 'status')) {
            db()->prepare(
                'UPDATE sub_course_subjects SET status=?, is_active=? WHERE sub_course_id=? AND subject_id=?'
            )->execute([$val, $val, $subCourseId, $subjectId]);
        } else {
            db()->prepare(
                'UPDATE sub_course_subjects SET is_active=? WHERE sub_course_id=? AND subject_id=?'
            )->execute([$val, $subCourseId, $subjectId]);
        }
    }

    public function cmDeleteTopic(int $topicId): void
    {
        if ($topicId < 1) {
            throw new InvalidArgumentException('topic_id required');
        }
        $tbl = SchemaHelper::topicsTable();
        if (SchemaHelper::hasTable('sub_topics')) {
            db()->prepare('DELETE FROM sub_topics WHERE topic_id=?')->execute([$topicId]);
        }
        if (SchemaHelper::topicExamSuiteEnabled()) {
            db()->prepare('DELETE FROM topic_exam_suite WHERE topic_id=?')->execute([$topicId]);
        }
        db()->prepare("DELETE FROM `{$tbl}` WHERE id=?")->execute([$topicId]);
    }

    public function cmEntityRow(string $entity, int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        $map = [
            'course' => [SchemaHelper::mainCourseImageTable(), 'id, slug, name, name_te, description, sort_order, image_path'],
            'sub_course' => ['sub_courses', 'id, course_id, slug, name, name_te, description, sort_order, image_path'],
            'subject' => ['subjects', 'id, slug, name, name_te, description, sort_order, image_path'],
        ];
        if (!isset($map[$entity])) {
            return null;
        }
        [$table, $cols] = $map[$entity];
        if (!SchemaHelper::hasTable($table)) {
            return null;
        }
        if (!SchemaHelper::imagePathEnabled($table)) {
            $cols = preg_replace('/, image_path/', '', $cols);
        }
        $st = db()->prepare("SELECT {$cols} FROM `{$table}` WHERE id=? LIMIT 1");
        $st->execute([$id]);

        return $st->fetch() ?: null;
    }

    public function cmSaveTopicMeta(int $topicId, array $data): void
    {
        if ($topicId < 1) {
            throw new InvalidArgumentException('topic_id required');
        }
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('title required');
        }
        $tbl = SchemaHelper::topicsTable();
        $titleTe = trim((string) ($data['title_te'] ?? ''));
        db()->prepare("UPDATE `{$tbl}` SET title=?, title_te=? WHERE id=?")->execute([
            $title,
            $titleTe !== '' ? $titleTe : null,
            $topicId,
        ]);
    }

    /** @param array<string,mixed> $data */
    public function saveTopicNotesOnly(int $topicId, array $data): void
    {
        $data = $this->applyNotesBinding($topicId, $data);
        $this->saveTopicContentManager($topicId, [
            'has_sub_topics' => !empty($data['has_sub_topics']),
            'notes_enabled' => !empty($data['notes_enabled']),
            'can_download' => !empty($data['can_download']),
            'notes_content' => (string) ($data['notes_content'] ?? ''),
            'sub_topics' => $data['sub_topics'] ?? [],
            'question_count' => (int) ($data['question_count'] ?? 50),
        ]);
    }

    public function saveTopicMcqTextOnly(int $topicId, string $mcqContent): void
    {
        if ($topicId < 1) {
            throw new InvalidArgumentException('topic_id required');
        }
        if (!SchemaHelper::topicMcqContentEnabled()) {
            throw new RuntimeException('mcq_content column not migrated.');
        }
        $tbl = SchemaHelper::topicsTable();
        db()->prepare("UPDATE `{$tbl}` SET mcq_content=? WHERE id=?")->execute([
            $mcqContent !== '' ? $mcqContent : null,
            $topicId,
        ]);
    }

    /** @param list<array<string,mixed>> $suites */
    public function saveTopicExamSuiteOnly(int $topicId, array $suites): void
    {
        if ($topicId < 1) {
            throw new InvalidArgumentException('topic_id required');
        }
        if (!SchemaHelper::topicExamSuiteEnabled()) {
            throw new RuntimeException('topic_exam_suite not migrated.');
        }
        $this->saveTopicExamSuite($topicId, null, $suites);
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function applyNotesBinding(int $topicId, array $data): array
    {
        $mode = (string) ($data['notes_bind_mode'] ?? 'topic');
        $tbl = SchemaHelper::topicsTable();

        if ($mode === 'new_subtopic') {
            $name = trim((string) ($data['new_sub_topic_name'] ?? ''));
            if ($name === '') {
                throw new InvalidArgumentException('new_sub_topic_name required');
            }
            $data['has_sub_topics'] = 1;
            $data['sub_topics'] = [[
                'sub_topic_name' => $name,
                'sub_topic_name_te' => trim((string) ($data['new_sub_topic_name_te'] ?? $name)),
                'sub_notes_content' => (string) ($data['notes_content'] ?? ''),
                'question_count' => 50,
            ]];

            return $data;
        }

        if ($mode === 'existing_subtopic') {
            $subId = (int) ($data['bind_sub_topic_id'] ?? 0);
            if ($subId < 1) {
                throw new InvalidArgumentException('bind_sub_topic_id required');
            }
            $data['has_sub_topics'] = 1;
            if (SchemaHelper::topicNotesBindEnabled()) {
                db()->prepare("UPDATE `{$tbl}` SET notes_bind_sub_topic_id=? WHERE id=?")->execute([$subId, $topicId]);
            }
        }

        return $data;
    }

    /**
     * Parse MCQs into test_questions for a suite slot; also stores raw text in mcq_content.
     *
     * @param list<array{question_text:string,option_a:string,option_b:string,option_c:string,option_d:string,correct_option:string}> $questions
     * @return array{test_id:int,imported:int,suite_key:string}
     */
    public function importMcqBankForTopicSuite(
        int $topicId,
        string $suiteKey,
        array $questions,
        ?string $rawText = null,
        ?array $suiteMeta = null
    ): array {
        if ($topicId < 1 || $questions === []) {
            throw new InvalidArgumentException('topic_id and questions required');
        }
        require_once dirname(__DIR__) . '/includes/admin/content_manager_defaults.php';
        $allowed = array_column(content_manager_exam_suite_templates(), 'suite_key');
        if (!in_array($suiteKey, $allowed, true)) {
            throw new InvalidArgumentException('Invalid suite_key');
        }

        if ($rawText !== null && SchemaHelper::topicMcqContentEnabled()) {
            $this->saveTopicMcqTextOnly($topicId, $rawText);
        }

        $suites = $this->getTopicExamSuite($topicId, null);
        $merged = [];
        $found = false;
        foreach ($suites as $s) {
            if (($s['suite_key'] ?? '') === $suiteKey) {
                $found = true;
                if (is_array($suiteMeta)) {
                    $s = array_merge($s, $suiteMeta);
                }
                $s['question_count'] = count($questions);
                $s['total_marks'] = count($questions);
                $s['is_required'] = $s['is_required'] ?? 1;
                $s['is_enabled'] = 1;
            }
            $merged[] = $s;
        }
        if (!$found) {
            $row = ['suite_key' => $suiteKey, 'is_required' => 1, 'is_enabled' => 1];
            if (is_array($suiteMeta)) {
                $row = array_merge($row, $suiteMeta);
            }
            $row['question_count'] = count($questions);
            $row['total_marks'] = count($questions);
            $merged[] = $row;
        }

        $this->saveTopicExamSuite($topicId, null, $merged);
        $fresh = $this->getTopicExamSuite($topicId, null);
        $testId = 0;
        foreach ($fresh as $s) {
            if (($s['suite_key'] ?? '') === $suiteKey && !empty($s['test_id'])) {
                $testId = (int) $s['test_id'];
                break;
            }
        }
        if ($testId < 1) {
            throw new RuntimeException('Could not create linked test for suite.');
        }

        db()->prepare('DELETE FROM test_questions WHERE test_id=?')->execute([$testId]);
        $order = 1;
        foreach ($questions as $q) {
            $letter = strtoupper(substr((string) ($q['correct_option'] ?? 'A'), 0, 1));
            if (!in_array($letter, ['A', 'B', 'C', 'D'], true)) {
                continue;
            }
            $this->saveQuestion([
                'test_id' => $testId,
                'question_order' => $order,
                'question_text' => (string) ($q['question_text'] ?? ''),
                'question_text_te' => null,
                'option_a' => (string) ($q['option_a'] ?? ''),
                'option_b' => (string) ($q['option_b'] ?? ''),
                'option_c' => (string) ($q['option_c'] ?? ''),
                'option_d' => (string) ($q['option_d'] ?? ''),
                'correct_option' => $letter,
                'explanation' => null,
                'marks' => 1,
                'topic_tag' => null,
            ]);
            ++$order;
        }
        $count = $order - 1;
        db()->prepare('UPDATE tests SET total_questions=?, total_marks=? WHERE id=?')->execute([$count, $count, $testId]);

        return ['test_id' => $testId, 'imported' => $count, 'suite_key' => $suiteKey];
    }
}
