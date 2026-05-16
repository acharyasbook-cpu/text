<?php

declare(strict_types=1);

/** POST action handler — included from admin_dashboard.php */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action'])) {
    return;
}

require_admin();
AdminAuthController::verifyCsrf(AdminAuthController::csrfFromRequest());

$repo = new AdminRepository();
$action = $_POST['action'];
$view = $_POST['return_view'] ?? 'overview';

try {
    switch ($action) {
        case 'save_course':
            $repo->saveCourse([
                'slug' => slugify($_POST['slug'] ?: $_POST['name']),
                'name' => trim($_POST['name']),
                'name_te' => trim($_POST['name_te'] ?? ''),
                'region' => trim($_POST['region'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ], !empty($_POST['id']) ? (int) $_POST['id'] : null);
            admin_flash('success', 'Course saved.');
            $view = 'courses';
            break;

        case 'delete_course':
            $repo->deleteCourse((int) $_POST['id']);
            admin_flash('success', 'Course deleted.');
            $view = 'courses';
            break;

        case 'save_subject':
            $scIds = isset($_POST['sub_course_ids']) && is_array($_POST['sub_course_ids'])
                ? array_map('intval', $_POST['sub_course_ids']) : [];
            $repo->saveSubject([
                'course_id' => isset($_POST['course_id']) && $_POST['course_id'] !== '' ? (int) $_POST['course_id'] : null,
                'category_id' => isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null,
                'sub_course_ids' => $scIds,
                'marks_allocated' => $_POST['marks_allocated'] ?? null,
                'slug' => slugify($_POST['slug'] ?: $_POST['name']),
                'name' => trim($_POST['name']),
                'name_te' => trim($_POST['name_te'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ], !empty($_POST['id']) ? (int) $_POST['id'] : null);
            admin_flash('success', 'Subject saved.');
            $view = 'courses';
            break;

        case 'save_sub_course':
            $syncSubjects = !empty($_POST['sub_course_subjects_sent']);
            $subCourseSubjectIds = $syncSubjects
                ? (isset($_POST['sub_course_subject_ids']) && is_array($_POST['sub_course_subject_ids'])
                    ? array_map('intval', $_POST['sub_course_subject_ids']) : [])
                : [];
            $repo->saveSubCourse([
                'course_id' => (int) ($_POST['course_id'] ?? 0),
                'slug' => slugify($_POST['slug'] ?: $_POST['name']),
                'name' => trim($_POST['name'] ?? ''),
                'name_te' => trim($_POST['name_te'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sync_subject_links' => $syncSubjects,
                'subject_ids' => $subCourseSubjectIds,
            ], !empty($_POST['id']) ? (int) $_POST['id'] : null);
            admin_flash('success', 'Sub-course saved.');
            $view = 'courses';
            break;

        case 'delete_sub_course':
            $repo->deleteSubCourse((int) $_POST['id']);
            admin_flash('success', 'Sub-course deleted.');
            $view = 'courses';
            break;

        case 'save_pricing_bulk':
            $rows = [];
            foreach (($_POST['plan'] ?? []) as $pid => $row) {
                $rows[(int) $pid] = [
                    'price_inr' => $row['price_inr'] ?? 0,
                    'active' => !empty($row['active']),
                ];
            }
            $repo->savePlanPrices($rows);
            admin_flash('success', 'Pricing updated.');
            $view = 'courses';
            break;

        case 'delete_subject':
            $repo->deleteSubject((int) $_POST['id']);
            admin_flash('success', 'Subject deleted.');
            $view = 'courses';
            break;

        case 'save_content_topic':
            $repo->saveTopicContentManager((int) ($_POST['topic_id'] ?? 0), [
                'has_sub_topics' => isset($_POST['has_sub_topics']),
                'question_count' => (int) ($_POST['question_count'] ?? 50),
                'notes_content' => trim($_POST['notes_content'] ?? ''),
            ]);
            admin_flash('success', 'Topic content saved.');
            $view = 'content';
            break;

        case 'save_topic':
        case 'save_lesson':
            $topicVisible = array_key_exists('topic_visible', $_POST)
                ? (isset($_POST['topic_visible']) ? 1 : 0)
                : 1;
            $repo->saveTopic([
                'subject_id' => (int) $_POST['subject_id'],
                'slug' => slugify($_POST['slug'] ?: $_POST['title']),
                'title' => trim($_POST['title']),
                'title_te' => trim($_POST['title_te'] ?? ''),
                'summary' => trim($_POST['summary'] ?? ''),
                'duration_mins' => (int) ($_POST['duration_mins'] ?? 30),
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'is_free_preview' => isset($_POST['is_free_preview']) ? 1 : 0,
                'is_active' => $topicVisible,
                'exam_link' => trim($_POST['exam_link'] ?? ''),
                'exam_test_id' => $_POST['exam_test_id'] !== '' && isset($_POST['exam_test_id']) ? (int) $_POST['exam_test_id'] : null,
            ], !empty($_POST['id']) ? (int) $_POST['id'] : null);
            admin_flash('success', 'Topic saved.');
            $view = 'courses';
            break;

        case 'save_topic_exam':
            $repo->saveTopicExam([
                'topic_id' => (int) ($_POST['topic_id'] ?? 0),
                'title' => trim($_POST['title'] ?? ''),
                'title_te' => trim($_POST['title_te'] ?? ''),
                'slug' => trim($_POST['slug'] ?? ''),
                'external_url' => trim($_POST['external_url'] ?? ''),
                'test_id' => $_POST['test_id'] !== '' && isset($_POST['test_id']) ? (int) $_POST['test_id'] : null,
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'exam_test_type' => trim((string) ($_POST['exam_test_type'] ?? 'topic')),
                'material_url' => trim((string) ($_POST['material_url'] ?? '')),
            ], !empty($_POST['id']) ? (int) $_POST['id'] : null);
            admin_flash('success', 'Topic exam saved.');
            $view = 'courses';
            break;

        case 'delete_topic_exam':
            $repo->deleteTopicExam((int) $_POST['id']);
            admin_flash('success', 'Topic exam removed.');
            $view = 'courses';
            break;

        case 'save_material':
            $fileUrl = $repo->handleUpload('material_file', 'materials') ?: trim($_POST['file_url'] ?? '');
            $repo->saveMaterial([
                'subject_id' => (int) $_POST['subject_id'],
                'topic_id' => isset($_POST['topic_id']) && $_POST['topic_id'] !== '' ? (int) $_POST['topic_id'] : null,
                'title' => trim($_POST['title']),
                'material_type' => $_POST['material_type'] ?? 'pdf',
                'file_url' => $fileUrl,
                'description' => trim($_POST['description'] ?? ''),
            ], !empty($_POST['id']) ? (int) $_POST['id'] : null);
            admin_flash('success', 'Study material saved.');
            $view = 'courses';
            break;

        case 'save_package':
            $repo->savePackage([
                'slug' => slugify($_POST['slug'] ?: $_POST['name']),
                'package_type' => $_POST['package_type'],
                'course_id' => $_POST['course_id'] ? (int) $_POST['course_id'] : null,
                'subject_id' => $_POST['subject_id'] ? (int) $_POST['subject_id'] : null,
                'name' => trim($_POST['name']),
                'name_te' => trim($_POST['name_te'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'price_inr' => (float) ($_POST['price_inr'] ?? 0),
                'includes_division_tests' => isset($_POST['includes_division_tests']) ? 1 : 0,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ], !empty($_POST['id']) ? (int) $_POST['id'] : null);
            admin_flash('success', 'Sub-course package saved.');
            $view = 'courses';
            break;

        case 'save_test':
            $comp = isset($_POST['component_test_ids']) && is_array($_POST['component_test_ids'])
                ? array_map('intval', $_POST['component_test_ids']) : [];
            $repo->saveTest([
                'course_id' => (int) $_POST['course_id'],
                'subject_id' => $_POST['subject_id'] ? (int) $_POST['subject_id'] : null,
                'topic_id' => isset($_POST['topic_id']) && $_POST['topic_id'] !== '' ? (int) $_POST['topic_id'] : null,
                'slug' => slugify($_POST['slug'] ?: $_POST['title']),
                'title' => trim($_POST['title']),
                'title_te' => trim($_POST['title_te'] ?? ''),
                'test_type' => $_POST['test_type'] ?? 'topic',
                'division_label' => trim($_POST['division_label'] ?? '') ?: null,
                'duration_mins' => (int) $_POST['duration_mins'],
                'total_questions' => (int) $_POST['total_questions'],
                'total_marks' => (int) $_POST['total_marks'],
                'passing_marks' => (int) $_POST['passing_marks'],
                'negative_marking' => (float) ($_POST['negative_marking'] ?? 0.25),
                'package_id' => $_POST['package_id'] ? (int) $_POST['package_id'] : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'component_test_ids' => $comp,
            ], !empty($_POST['id']) ? (int) $_POST['id'] : null);
            admin_flash('success', 'Exam saved.');
            $view = 'exams';
            break;

        case 'delete_test':
            $repo->deleteTest((int) $_POST['id']);
            admin_flash('success', 'Exam deleted.');
            $view = 'exams';
            break;

        case 'save_question':
            $repo->saveQuestion([
                'test_id' => (int) $_POST['test_id'],
                'question_order' => (int) ($_POST['question_order'] ?? 1),
                'question_text' => trim($_POST['question_text']),
                'question_text_te' => trim($_POST['question_text_te'] ?? ''),
                'option_a' => trim($_POST['option_a']),
                'option_b' => trim($_POST['option_b']),
                'option_c' => trim($_POST['option_c']),
                'option_d' => trim($_POST['option_d']),
                'correct_option' => strtoupper($_POST['correct_option']),
                'explanation' => trim($_POST['explanation'] ?? ''),
                'marks' => (int) ($_POST['marks'] ?? 1),
                'topic_tag' => trim($_POST['topic_tag'] ?? ''),
            ], !empty($_POST['id']) ? (int) $_POST['id'] : null);
            admin_flash('success', 'MCQ saved.');
            $view = 'exams';
            $_GET['test_id'] = (int) $_POST['test_id'];
            break;

        case 'delete_question':
            $repo->deleteQuestion((int) $_POST['id']);
            admin_flash('success', 'Question removed.');
            $view = 'exams';
            break;

        case 'toggle_subscription':
            $repo->toggleSubscription((int) $_POST['user_id'], (int) $_POST['package_id'], $_POST['enable'] === '1');
            admin_flash('success', 'Subscription updated.');
            $view = 'students';
            break;

        case 'record_payment':
            $repo->recordPayment([
                'user_id' => (int) $_POST['user_id'],
                'package_id' => $_POST['package_id'] ? (int) $_POST['package_id'] : null,
                'amount_inr' => (float) $_POST['amount_inr'],
                'payment_method' => trim($_POST['payment_method'] ?? 'manual'),
                'transaction_ref' => trim($_POST['transaction_ref'] ?? ''),
                'status' => $_POST['status'] ?? 'completed',
                'notes' => trim($_POST['notes'] ?? ''),
            ]);
            admin_flash('success', 'Payment recorded.');
            $view = 'students';
            break;

        case 'save_site_logo':
            $plat = new PlatformRepository();
            $oldPath = $plat->logoPath();
            $path = $repo->handleUpload('site_logo', 'branding');
            if (!$path) {
                throw new InvalidArgumentException('Please choose an image file (JPG, PNG, GIF, SVG, WEBP — up to 10 MB).');
            }
            $plat->set('site_logo_path', $path);
            if ($oldPath !== null && $oldPath !== $path) {
                ImageUploadService::deleteIfStored($oldPath);
            }
            admin_flash('success', 'Site logo updated — visible on public site immediately.');
            break;

        case 'clear_site_logo':
            $plat = new PlatformRepository();
            $oldPath = $plat->logoPath();
            $plat->set('site_logo_path', null);
            ImageUploadService::deleteIfStored($oldPath);
            admin_flash('success', 'Site logo cleared.');
            break;
    }
} catch (Throwable $e) {
    admin_flash('error', $e->getMessage());
}

$_GET['view'] = $view;

$tabRedirect = $_POST['tab_redirect'] ?? '';
$allowedTabs = ['courses', 'subcourses', 'subjects', 'topics', 'pricing', 'packages', 'content'];
if (is_string($tabRedirect) && in_array($tabRedirect, $allowedTabs, true)) {
    $_GET['tab'] = $tabRedirect;
}
