<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__));
require_once ACHARYA_ROOT . '/includes/admin/bootstrap.php';
require_once ACHARYA_ROOT . '/models/CurrentAffairsRepository.php';
require_once ACHARYA_ROOT . '/includes/CurrentAffairsManualParser.php';
require_once ACHARYA_ROOT . '/services/CurrentAffairsAiService.php';
require_once ACHARYA_ROOT . '/includes/HomeBannerSettings.php';
require_once ACHARYA_ROOT . '/includes/CaPublicSiteSync.php';

header('Content-Type: application/json; charset=utf-8');

require_admin();

if (!CurrentAffairsRepository::ready()) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'Run php database/migrate_current_affairs.php']);
    exit;
}

$repo = new CurrentAffairsRepository();
$action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');

try {
    switch ($action) {
        case 'stats':
            echo json_encode(['ok' => true, 'dates' => $repo->adminStatsByDate(), 'purge_months' => $repo->monthsEligibleForPurge()]);
            break;

        case 'pool':
            $date = normalize_ca_date((string) ($_GET['date'] ?? ''));
            echo json_encode(['ok' => true, 'date' => $date, 'count' => $repo->poolCountForDate($date), 'ready' => $repo->dateHasExam($date)]);
            break;

        case 'manual_save':
            verify_csrf();
            $date = normalize_ca_date((string) ($_POST['exam_date'] ?? ''));
            $rows = CurrentAffairsManualParser::parseBulk((string) ($_POST['bulk_text'] ?? ''));
            $parsed = count($rows);
            if ($parsed < CurrentAffairsRepository::EXAM_QUESTION_COUNT) {
                throw new InvalidArgumentException(
                    'కనీసం 25 ప్రశ్నలు అవసరం. పార్స్ అయినవి: ' . $parsed
                );
            }
            if ($parsed > HomeBannerSettings::MAX_MANUAL_POOL) {
                $rows = array_slice($rows, 0, HomeBannerSettings::MAX_MANUAL_POOL);
            }
            $repo->clearPoolForDate($date);
            $inserted = $repo->insertPoolBatch($date, $rows, 'manual');
            if ($repo->dateHasExam($date)) {
                CaPublicSiteSync::touch();
            }
            echo json_encode([
                'ok' => true,
                'inserted' => $inserted,
                'parsed' => $parsed,
                'date' => $date,
                'lottery' => $inserted > CurrentAffairsRepository::EXAM_QUESTION_COUNT,
            ]);
            break;

        case 'ai_job_start':
            verify_csrf();
            $date = normalize_ca_date((string) ($_POST['exam_date'] ?? ''));
            $jobId = bin2hex(random_bytes(8));
            $_SESSION['ca_ai_jobs'] = $_SESSION['ca_ai_jobs'] ?? [];
            $_SESSION['ca_ai_jobs'][$jobId] = [
                'exam_date' => $date,
                'batch' => 0,
                'total_batches' => 5,
                'percent' => 0,
                'status' => 'running',
                'message' => 'ప్రారంభం…',
                'rows' => [],
            ];
            echo json_encode(['ok' => true, 'job_id' => $jobId, 'percent' => 0]);
            break;

        case 'ai_job_tick':
            verify_csrf();
            $jobId = (string) ($_POST['job_id'] ?? '');
            $jobs = $_SESSION['ca_ai_jobs'] ?? [];
            if ($jobId === '' || !isset($jobs[$jobId])) {
                throw new RuntimeException('AI job not found');
            }
            $job = $jobs[$jobId];
            if (($job['status'] ?? '') === 'complete') {
                echo json_encode(['ok' => true, 'percent' => 100, 'status' => 'complete', 'inserted' => count($job['rows'] ?? [])]);
                break;
            }
            $svc = new CurrentAffairsAiService();
            $batchRows = $svc->generateBatch((string) $job['exam_date'], (int) $job['batch']);
            $job['rows'] = array_merge($job['rows'] ?? [], $batchRows);
            $job['batch'] = (int) $job['batch'] + 1;
            $job['percent'] = (int) min(100, round($job['batch'] / $job['total_batches'] * 100));
            $job['message'] = 'బ్యాచ్ ' . $job['batch'] . '/' . $job['total_batches'] . ' పూర్తి';
            if ($job['batch'] >= $job['total_batches']) {
                $repo->clearPoolForDate((string) $job['exam_date']);
                $inserted = $repo->insertPoolBatch((string) $job['exam_date'], array_slice($job['rows'], 0, 25), 'ai');
                if ($repo->dateHasExam((string) $job['exam_date'])) {
                    CaPublicSiteSync::touch();
                }
                $job['status'] = 'complete';
                $job['percent'] = 100;
                $job['inserted'] = $inserted;
            }
            $_SESSION['ca_ai_jobs'][$jobId] = $job;
            echo json_encode([
                'ok' => true,
                'percent' => $job['percent'],
                'status' => $job['status'],
                'message' => $job['message'],
                'inserted' => $job['inserted'] ?? 0,
            ]);
            break;

        case 'ai_generate':
            verify_csrf();
            $date = normalize_ca_date((string) ($_POST['exam_date'] ?? ''));
            $res = (new CurrentAffairsAiService())->generateForDate($date);
            if (!empty($res['ok']) && $repo->dateHasExam($date)) {
                CaPublicSiteSync::touch();
            }
            echo json_encode($res + ['date' => $date]);
            break;

        case 'purge_month':
            verify_csrf();
            $ym = (string) ($_POST['ym'] ?? '');
            $deleted = $repo->purgeMonth($ym);
            echo json_encode(['ok' => true, 'deleted' => $deleted, 'ym' => $ym]);
            break;

        case 'clear_date':
            verify_csrf();
            $date = normalize_ca_date((string) ($_POST['exam_date'] ?? ''));
            $repo->clearPoolForDate($date);
            echo json_encode(['ok' => true, 'date' => $date]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    }
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

function normalize_ca_date(string $d): string
{
    $ts = strtotime($d);
    if ($ts === false) {
        throw new InvalidArgumentException('Invalid date');
    }

    return date('Y-m-d', $ts);
}

function verify_csrf(): void
{
    $token = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($token === '' || !hash_equals(admin_csrf_token(), $token)) {
        throw new RuntimeException('CSRF validation failed');
    }
}
