<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__));
require ACHARYA_ROOT . '/includes/admin/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

$adminUser = admin_user();
if (!$adminUser || ($adminUser['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$repo = new AdminRepository();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'main_courses':
                echo json_encode(['ok' => true, 'items' => $repo->contentManagerMainCourses()]);
                break;
            case 'sub_courses':
                $cid = (int) ($_GET['course_id'] ?? 0);
                echo json_encode(['ok' => true, 'items' => $repo->contentManagerSubCourses($cid)]);
                break;
            case 'subjects':
                $scid = (int) ($_GET['sub_course_id'] ?? 0);
                echo json_encode(['ok' => true, 'items' => $repo->contentManagerSubjects($scid)]);
                break;
            case 'topics':
                $sid = (int) ($_GET['subject_id'] ?? 0);
                echo json_encode(['ok' => true, 'items' => $repo->contentManagerTopics($sid)]);
                break;
            case 'topic':
                $tid = (int) ($_GET['topic_id'] ?? 0);
                $topic = $repo->getTopicContentManager($tid);
                if (!$topic) {
                    http_response_code(404);
                    echo json_encode(['ok' => false, 'error' => 'Topic not found']);
                    break;
                }
                echo json_encode(['ok' => true, 'topic' => $topic]);
                break;
            case 'entity':
                $entity = preg_replace('/[^a-z_]/', '', (string) ($_GET['entity'] ?? ''));
                $eid = (int) ($_GET['id'] ?? 0);
                $row = $repo->cmEntityRow($entity, $eid);
                if (!$row) {
                    http_response_code(404);
                    echo json_encode(['ok' => false, 'error' => 'Not found']);
                    break;
                }
                echo json_encode(['ok' => true, 'item' => $row]);
                break;
            case 'resolve_programme':
                $mc = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['mc'] ?? ''));
                $sc = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['sc'] ?? ''));
                $row = $repo->resolveSubCourseByCourseAndSlug($mc, $sc);
                if (!$row) {
                    http_response_code(404);
                    echo json_encode(['ok' => false, 'error' => 'Programme not found']);
                    break;
                }
                echo json_encode([
                    'ok' => true,
                    'course_id' => (int) ($row['parent_course_id'] ?? $row['course_id'] ?? 0),
                    'sub_course_id' => (int) ($row['id'] ?? 0),
                ]);
                break;
            case 'exam_templates':
                require_once ACHARYA_ROOT . '/includes/admin/content_manager_defaults.php';
                echo json_encode(['ok' => true, 'items' => content_manager_exam_suite_templates()]);
                break;
            default:
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Unknown action']);
        }
        exit;
    }

    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!empty($_FILES['image_file']) && ($_POST['action'] ?? '') === 'upload_image') {
        $entity = preg_replace('/[^a-z_]/', '', (string) ($_POST['entity'] ?? ''));
        $eid = (int) ($_POST['id'] ?? 0);
        $path = $repo->handleUpload('image_file', 'covers/' . $entity);
        if (!$path || $eid < 1) {
            throw new InvalidArgumentException('Upload failed');
        }
        $repo->cmSetImagePath($entity, $eid, $path);
        echo json_encode(['ok' => true, 'image_path' => $path]);
        exit;
    }

    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $action = (string) ($data['action'] ?? '');

    switch ($action) {
        case 'save_topic_config':
            $topicId = (int) ($data['topic_id'] ?? 0);
            if ($topicId < 1) {
                throw new InvalidArgumentException('topic_id required');
            }
            $repo->saveTopicContentManager($topicId, [
                'has_sub_topics' => !empty($data['has_sub_topics']),
                'notes_enabled' => !empty($data['notes_enabled']),
                'question_count' => (int) ($data['question_count'] ?? 50),
                'notes_content' => (string) ($data['notes_content'] ?? ''),
                'sub_topics' => $data['sub_topics'] ?? [],
                'exam_suite' => $data['exam_suite'] ?? [],
            ]);
            echo json_encode(['ok' => true, 'topic_id' => $topicId]);
            break;

        case 'create_topic':
            $subjectId = (int) ($data['subject_id'] ?? 0);
            $title = trim((string) ($data['title'] ?? ''));
            $titleTe = trim((string) ($data['title_te'] ?? ''));
            $newId = $repo->createTopicQuick($subjectId, $title, $titleTe !== '' ? $titleTe : null);
            echo json_encode(['ok' => true, 'topic_id' => $newId]);
            break;

        case 'delete_topic':
            $repo->cmDeleteTopic((int) ($data['topic_id'] ?? 0));
            echo json_encode(['ok' => true]);
            break;

        case 'save_main_course':
            $id = isset($data['id']) && (int) $data['id'] > 0 ? (int) $data['id'] : null;
            $newId = $repo->cmSaveMainCourse($data, $id);
            echo json_encode(['ok' => true, 'id' => $newId]);
            break;

        case 'delete_main_course':
            $repo->deleteCourse((int) ($data['id'] ?? 0));
            echo json_encode(['ok' => true]);
            break;

        case 'save_sub_course':
            $id = isset($data['id']) && (int) $data['id'] > 0 ? (int) $data['id'] : null;
            $newId = $repo->cmSaveSubCourse($data, $id);
            echo json_encode(['ok' => true, 'id' => $newId]);
            break;

        case 'delete_sub_course':
            $repo->deleteSubCourse((int) ($data['id'] ?? 0));
            echo json_encode(['ok' => true]);
            break;

        case 'save_subject':
            $id = isset($data['id']) && (int) $data['id'] > 0 ? (int) $data['id'] : null;
            $newId = $repo->cmSaveSubjectForSubCourse($data, $id);
            echo json_encode(['ok' => true, 'id' => $newId]);
            break;

        case 'delete_subject':
            $repo->deleteSubject((int) ($data['id'] ?? 0));
            echo json_encode(['ok' => true]);
            break;

        case 'set_subject_live':
            $repo->cmSetSubjectLive(
                (int) ($data['sub_course_id'] ?? 0),
                (int) ($data['subject_id'] ?? 0),
                !empty($data['is_live'])
            );
            echo json_encode(['ok' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
