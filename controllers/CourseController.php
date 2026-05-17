<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/controllers/HeaderController.php';

final class CourseController
{
    public function __construct(
        private CourseRepository $courses = new CourseRepository(),
        private TestRepository $tests = new TestRepository(),
        private SubscriptionRepository $subscriptions = new SubscriptionRepository(),
        private HeaderController $header = new HeaderController(),
    ) {
    }

    public function detail(string $slug): void
    {
        $course = $this->courses->findBySlug($slug);
        if (!$course) {
            http_response_code(404);
            exit('Course not found');
        }

        $fourTier = SchemaHelper::hierarchyFourTier();
        $subCourseBlocks = $fourTier ? $this->courses->subjectsGroupedBySubCourse((int) $course['id']) : [];
        $subjectsGrouped = $fourTier ? [] : $this->courses->subjectsGroupedByCategory((int) $course['id']);
        $tests = $this->tests->forCourse((int) $course['id']);
        $packages = $this->subscriptions->packagesForCourse((int) $course['id']);
        $user = current_user();

        $testsByType = ['topic' => [], 'division' => [], 'revision' => [], 'grand' => [], 'model' => []];
        foreach ($tests as $t) {
            $k = $t['test_type'] ?? 'topic';
            if (!isset($testsByType[$k])) {
                $testsByType[$k] = [];
            }
            $testsByType[$k][] = $t;
        }

        $header = $this->header->build($slug, $slug);
        $pageTitle = ($course['name_te'] ?: $course['name']) . ' | Acharya Books';
        $view = 'course';

        require dirname(__DIR__) . '/includes/public/layout_start.php';
        require dirname(__DIR__) . '/includes/public/views/course_detail.php';
        require dirname(__DIR__) . '/includes/public/layout_end.php';
    }

    public function subCourse(string $courseSlug, string $subSlug): void
    {
        $subCourse = $this->courses->findSubCourseBySlugs($courseSlug, $subSlug);
        if (!$subCourse) {
            http_response_code(404);
            exit('Sub-course not found');
        }

        $course = $this->courses->findBySlug($courseSlug);
        $subjects = $this->courses->subjectsForSubCourse((int) $subCourse['id']);
        $plans = $this->courses->plansForSubCourse((int) $subCourse['id']);
        $user = current_user();
        $hasAccess = $user && $this->subscriptions->userHasActivePlanForSubCourse(
            (int) $user['id'],
            (int) $subCourse['id']
        );

        $scheduleService = new SubjectScheduleService();
        $termMatrix = $scheduleService->buildSubCourseMatrixView(
            (int) $subCourse['id'],
            $user ? (int) $user['id'] : null,
            $courseSlug
        );

        $scheduleDaily = null;
        if (SchemaHelper::scheduleTestManagerEnabled()) {
            require_once dirname(__DIR__) . '/models/ScheduleTestRepository.php';
            require_once dirname(__DIR__) . '/models/ScheduleTestStudentService.php';
            $scheduleDaily = (new ScheduleTestStudentService())->buildDailyWorkspace(
                (int) $subCourse['id'],
                $user ? (int) $user['id'] : null,
                $courseSlug,
                $subSlug,
                ScheduleTestRepository::TERM_SHORT
            );
        }

        $tierTe = public_five_tier_labels_te();
        $activeSlug = (string) ($subCourse['course_slug'] ?? $courseSlug);
        $header = $this->header->build($activeSlug, $activeSlug);
        $pageTitle = ($subCourse['name_te'] ?: $subCourse['name']) . ' | ' . ($subCourse['course_name'] ?? 'Acharya Books');
        $view = 'sub_course';
        $checkoutReturn = '/sub_course.php?course=' . rawurlencode($courseSlug) . '&sub=' . rawurlencode($subSlug);

        require dirname(__DIR__) . '/includes/public/layout_start.php';
        require dirname(__DIR__) . '/includes/public/views/sub_course_detail.php';
        require dirname(__DIR__) . '/includes/public/layout_end.php';
    }

    public function subject(string $courseSlug, ?string $subSlug, string $subjectSlug): void
    {
        $subject = $this->courses->findSubjectByPath(
            $courseSlug !== '' ? $courseSlug : null,
            $subSlug !== null && $subSlug !== '' ? $subSlug : null,
            $subjectSlug
        );
        if (!$subject && $courseSlug !== '' && $subSlug !== '' && $subjectSlug !== '') {
            $subject = $this->courses->findSubjectByPath($courseSlug, null, $subjectSlug);
        }
        if (!$subject && $courseSlug !== '' && $subjectSlug !== '') {
            $subject = $this->courses->findSubjectBySlugs($courseSlug, $subjectSlug);
        }
        if (!$subject) {
            http_response_code(404);
            $header = $this->header->build($courseSlug, $courseSlug);
            $pageTitle = 'Subject Not Found | Acharya Books';
            $view = 'subject';
            $subSlug = $subSlug ?? '';
            require dirname(__DIR__) . '/includes/public/layout_start.php';
            require dirname(__DIR__) . '/includes/public/views/errors/subject_not_found.php';
            require dirname(__DIR__) . '/includes/public/layout_end.php';
            return;
        }

        require_once dirname(__DIR__) . '/includes/FreemiumAccess.php';
        require_once dirname(__DIR__) . '/includes/TwentyItemBootstrapSeeder.php';
        require_once dirname(__DIR__) . '/includes/SecureContentGuard.php';
        TwentyItemBootstrapSeeder::ensureForSubject((int) $subject['id']);

        $topics = $this->courses->topicsForSubject((int) $subject['id']);
        $user = current_user();
        $courseRepo = $this->courses;

        $subCourseId = 0;
        $programmeHasAccess = false;
        $scRow = null;
        $plans = [];
        $checkoutReturn = '';
        if (!empty($subject['sub_course_slug'])) {
            $scRow = $this->courses->findSubCourseBySlugs(
                (string) $subject['course_slug'],
                (string) $subject['sub_course_slug']
            );
            if ($scRow) {
                $subCourseId = (int) $scRow['id'];
                $programmeHasAccess = $user && $this->subscriptions->userHasActivePlanForSubCourse(
                    (int) $user['id'],
                    $subCourseId
                );
                $plans = $this->courses->plansForSubCourse($subCourseId);
                require_once dirname(__DIR__) . '/includes/public_site_helpers.php';
                $checkoutReturn = public_subject_workspace_url(
                    (string) $subject['course_slug'],
                    (string) $subject['sub_course_slug'],
                    (string) $subject['slug']
                );
            }
        }

        $topicsWorkspace = $this->courses->enrichTopicsForSubjectWorkspace(
            $topics,
            $subject,
            (bool) $programmeHasAccess
        );

        $bannerPath = trim((string) ($subject['image_path'] ?? ''));
        if ($bannerPath === '' && !empty($scRow['image_path'] ?? '')) {
            $bannerPath = trim((string) $scRow['image_path']);
        }

        $activeSlug = (string) $subject['course_slug'];
        $header = $this->header->build($activeSlug, $activeSlug);
        $pageTitle = $subject['name'] . ' | ' . $subject['course_name'];
        $view = 'subject';
        $courseSlug = $courseSlug;

        require dirname(__DIR__) . '/includes/public/layout_start.php';
        require dirname(__DIR__) . '/includes/public/views/subject_detail.php';
        require dirname(__DIR__) . '/includes/public/layout_end.php';
    }

    public function exams(): void
    {
        $user = require_login();
        $courses = $this->courses->allActive();
        $allTests = [];
        foreach ($courses as $c) {
            foreach ($this->tests->forCourse((int) $c['id']) as $t) {
                $t['course_name'] = $c['name'];
                $t['course_slug'] = $c['slug'];
                $t['can_access'] = $this->subscriptions->userHasTestAccess((int) $user['id'], (int) $t['id']);
                $allTests[] = $t;
            }
        }

        $header = $this->header->build('exams', null);
        $pageTitle = 'Online Exams | Acharya Books';
        $view = 'exams';

        require dirname(__DIR__) . '/includes/public/layout_start.php';
        require dirname(__DIR__) . '/includes/public/views/exams_list.php';
        require dirname(__DIR__) . '/includes/public/layout_end.php';
    }
}
