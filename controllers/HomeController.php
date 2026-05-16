<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/controllers/HeaderController.php';

final class HomeController
{
    public function __construct(
        private CourseRepository $courses = new CourseRepository(),
        private HeaderController $header = new HeaderController(),
    ) {
    }

    /** Home: all active main courses in card grid */
    public function index(): void
    {
        $header = $this->header->build('home', null);
        $catalog = $header['catalog'];
        $pageTitle = 'Acharya Books | AP DSC, TS DSC, TET & CTET';
        $view = 'home';
        require dirname(__DIR__) . '/includes/public/layout_start.php';
        require dirname(__DIR__) . '/includes/public/views/home_grid.php';
        require dirname(__DIR__) . '/includes/public/layout_end.php';
    }

    /** Course learn hub: sub-course card matrix */
    public function learn(string $courseSlug): void
    {
        $course = $this->courses->findBySlug($courseSlug);
        if (!$course) {
            http_response_code(404);
            exit('Course not found');
        }
        $header = $this->header->build($courseSlug, $courseSlug);
        $subCourses = $this->courses->subCoursesForCourse((int) $course['id']);
        $pageTitle = ($course['name_te'] ?: $course['name']) . ' | Learn';
        $view = 'learn';
        require dirname(__DIR__) . '/includes/public/layout_start.php';
        require dirname(__DIR__) . '/includes/public/views/learn_grid.php';
        require dirname(__DIR__) . '/includes/public/layout_end.php';
    }
}
