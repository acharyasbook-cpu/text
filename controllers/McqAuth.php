<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/ExaminerRepository.php';

/** Admin + examiner session helpers for MCQ AI engine. */
final class McqAuth
{
    public static function isSuperAdmin(): bool
    {
        $u = admin_user();

        return $u && ($u['role'] ?? '') === 'admin';
    }

    public static function isExaminer(): bool
    {
        $u = admin_user();

        return $u && ($u['role'] ?? '') === 'examiner';
    }

    public static function assignedSubject(): ?string
    {
        $u = admin_user();
        if (!$u || ($u['role'] ?? '') !== 'examiner') {
            return null;
        }

        return trim((string) ($u['assigned_subject'] ?? '')) ?: null;
    }

    public static function examinerId(): int
    {
        $u = admin_user();

        return (int) ($u['examiner_id'] ?? 0);
    }

    /**
     * Web gate: super-admin or active examiner.
     *
     * @return array<string,mixed>
     */
    public static function requireMcqWeb(): array
    {
        $u = admin_user();
        if (!$u || !in_array($u['role'] ?? '', ['admin', 'examiner'], true)) {
            AdminAuthController::destroySession();
            admin_redirect(admin_login_path()); // uses admin_core_url via admin_redirect
        }
        if (!AdminAuthController::sessionIsValid()) {
            AdminAuthController::destroySession();
            admin_redirect(admin_login_path());
        }

        return $u;
    }

    /**
     * API gate with optional CSRF for mutating routes.
     *
     * @return array<string,mixed>
     */
    public static function requireMcqApi(bool $csrf = false, ?array $json = null): array
    {
        header('Content-Type: application/json; charset=utf-8');

        $u = admin_user();
        if (!$u || !in_array($u['role'] ?? '', ['admin', 'examiner'], true)) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Unauthorized — sign in at /admin/login.php']);
            exit;
        }
        if (!AdminAuthController::sessionIsValid()) {
            AdminAuthController::destroySession();
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Session expired — refresh and sign in again']);
            exit;
        }
        $_SESSION['admin_authenticated_at'] = time();

        if ($csrf) {
            try {
                AdminAuthController::verifyCsrf(AdminAuthController::csrfFromRequest($json));
            } catch (RuntimeException $e) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
                exit;
            }
        }

        return $u;
    }

    public static function subjectFilterForUser(): ?string
    {
        if (self::isSuperAdmin()) {
            return null;
        }

        return self::assignedSubject();
    }

    public static function canManageExaminers(): bool
    {
        return self::isSuperAdmin();
    }

    public static function canSuperApprove(): bool
    {
        return self::isSuperAdmin();
    }

    public static function establishExaminerSession(array $examiner): void
    {
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id' => 0,
            'name' => $examiner['email'],
            'email' => $examiner['email'],
            'role' => 'examiner',
            'examiner_id' => (int) $examiner['id'],
            'assigned_subject' => $examiner['assigned_subject'],
        ];
        $_SESSION['admin_authenticated_at'] = time();
        $_SESSION['admin_session_fingerprint'] = hash(
            'sha256',
            ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . ($_SERVER['REMOTE_ADDR'] ?? '')
        );
        AdminAuthController::rotateCsrfToken();
    }
}
