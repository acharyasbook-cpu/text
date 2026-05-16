<?php

declare(strict_types=1);

/**
 * Admin authentication & session matrix (auth:admin layer for web + API).
 */
final class AdminAuthController
{
    /** Session lifetime after login (8 hours). */
    public const SESSION_TTL_SECONDS = 28800;

    private const CSRF_SESSION_KEY = 'admin_csrf_token';

    private const AUTH_AT_KEY = 'admin_authenticated_at';

    private const FINGERPRINT_KEY = 'admin_session_fingerprint';

    public static function establishSession(array $admin): void
    {
        session_regenerate_id(true);
        $_SESSION['admin'] = $admin;
        $_SESSION[self::AUTH_AT_KEY] = time();
        $_SESSION[self::FINGERPRINT_KEY] = self::fingerprint();
        self::rotateCsrfToken();
    }

    public static function destroySession(): void
    {
        unset(
            $_SESSION['admin'],
            $_SESSION[self::AUTH_AT_KEY],
            $_SESSION[self::FINGERPRINT_KEY],
            $_SESSION[self::CSRF_SESSION_KEY]
        );
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION[self::CSRF_SESSION_KEY])) {
            self::rotateCsrfToken();
        }

        return (string) $_SESSION[self::CSRF_SESSION_KEY];
    }

    public static function rotateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::CSRF_SESSION_KEY] = $token;

        return $token;
    }

    public static function verifyCsrf(?string $token): void
    {
        $expected = $_SESSION[self::CSRF_SESSION_KEY] ?? '';
        $provided = trim((string) $token);
        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            throw new RuntimeException('Invalid or missing CSRF token. Refresh the page and sign in again.');
        }
    }

    /** Read CSRF from header, POST field, or JSON body. */
    public static function csrfFromRequest(?array $jsonBody = null): ?string
    {
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_CSRF-TOKEN'] ?? null;
        if (is_string($header) && $header !== '') {
            return $header;
        }
        if (!empty($_POST['_csrf'])) {
            return (string) $_POST['_csrf'];
        }
        if (is_array($jsonBody) && !empty($jsonBody['_csrf'])) {
            return (string) $jsonBody['_csrf'];
        }

        return null;
    }

    /**
     * Strict admin gate for JSON/API routes (Content Manager, uploads, admin_api).
     *
     * @return array<string,mixed>
     */
    public static function requireAdminApi(bool $requireCsrf = false, ?array $jsonBody = null): array
    {
        $admin = self::requireValidAdminSession();
        if ($requireCsrf) {
            self::verifyCsrf(self::csrfFromRequest($jsonBody));
        }

        return $admin;
    }

    /**
     * Strict admin gate for HTML panel pages.
     *
     * @return array<string,mixed>
     */
    public static function requireAdminWeb(): array
    {
        $admin = admin_user();
        if (!$admin || ($admin['role'] ?? '') !== 'admin' || !self::sessionIsValid()) {
            self::destroySession();
            admin_redirect(admin_login_path());
        }

        $_SESSION[self::AUTH_AT_KEY] = time();

        return $admin;
    }

    /** @return array<string,mixed> */
    private static function requireValidAdminSession(): array
    {
        $admin = admin_user();
        if (!$admin || ($admin['role'] ?? '') !== 'admin') {
            self::rejectUnauthenticatedApi();
        }

        if (!self::sessionIsValid()) {
            self::destroySession();
            self::rejectUnauthenticatedApi();
        }

        $_SESSION[self::AUTH_AT_KEY] = time();

        return $admin;
    }

    private static function sessionIsValid(): bool
    {
        $authAt = (int) ($_SESSION[self::AUTH_AT_KEY] ?? 0);
        if ($authAt < 1 || (time() - $authAt) > self::SESSION_TTL_SECONDS) {
            return false;
        }

        $fp = $_SESSION[self::FINGERPRINT_KEY] ?? '';
        if ($fp === '' || !hash_equals($fp, self::fingerprint())) {
            return false;
        }

        return true;
    }

    private static function fingerprint(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        return hash('sha256', $ua . '|' . $ip);
    }

    private static function rejectUnauthenticatedApi(): never
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isJson = str_contains($accept, 'json')
            || str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'content_api')
            || str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'admin_api');

        if ($isJson) {
            http_response_code(401);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'error' => 'Unauthorized — admin session required']);
            exit;
        }

        admin_redirect(admin_login_path());
    }
}
