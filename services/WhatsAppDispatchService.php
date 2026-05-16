<?php

declare(strict_types=1);

/**
 * WhatsApp Web / deep-link dispatch (no official Business API).
 */
final class WhatsAppDispatchService
{
    public const MAX_FILE_BYTES = 52428800; // 50 MB

    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'document' => ['pdf', 'doc', 'docx', 'txt'],
        'audio' => ['mp3', 'm4a', 'ogg', 'wav', 'aac'],
        'video' => ['mp4', 'webm', 'mov', 'mkv'],
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    ];

    public static function columnReady(): bool
    {
        return SchemaHelper::hasTable('sub_courses')
            && SchemaHelper::columnExists('sub_courses', 'whatsapp_group_link');
    }

    public static function normalizeGroupInviteLink(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            throw new InvalidArgumentException('Invalid WhatsApp group link');
        }
        $host = strtolower((string) $parsed['host']);
        $allowedHosts = ['chat.whatsapp.com', 'www.chat.whatsapp.com', 'whatsapp.com', 'www.whatsapp.com'];
        $ok = false;
        foreach ($allowedHosts as $h) {
            if ($host === $h || str_ends_with($host, '.whatsapp.com')) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            throw new InvalidArgumentException('Link must be a WhatsApp invite URL (chat.whatsapp.com/…)');
        }

        return substr($url, 0, 512);
    }

    public static function extractInviteToken(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (preg_match('#chat\.whatsapp\.com/(?:invite/)?([A-Za-z0-9_-]+)#i', $url, $m)) {
            return $m[1];
        }
        if (preg_match('#whatsapp\.com/channel/([A-Za-z0-9_-]+)#i', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * @param array<string,mixed> $subCourse
     * @param list<array<string,mixed>> $attachments
     * @return array<string,mixed>
     */
    public function buildDispatchPlan(array $subCourse, string $message, array $attachments = []): array
    {
        $groupLink = trim((string) ($subCourse['whatsapp_group_link'] ?? ''));
        $token = $groupLink !== '' ? self::extractInviteToken($groupLink) : null;
        $message = trim($message);
        $footer = "\n\n— Acharya Books · " . ($subCourse['name_te'] ?? $subCourse['name'] ?? 'Programme');
        $fullText = $message !== '' ? $message . $footer : trim($footer);

        $encoded = rawurlencode($fullText);
        $shareTextUrl = 'https://api.whatsapp.com/send?text=' . $encoded;
        $waMeUrl = 'https://wa.me/?text=' . $encoded;

        $attachmentLines = [];
        foreach ($attachments as $att) {
            if (!is_array($att)) {
                continue;
            }
            $label = (string) ($att['original_name'] ?? $att['name'] ?? 'file');
            $url = (string) ($att['public_url'] ?? '');
            if ($url !== '') {
                $attachmentLines[] = $label . ': ' . $url;
            }
        }
        if ($attachmentLines !== []) {
            $fullText .= "\n\n📎 " . implode("\n", $attachmentLines);
            $encoded = rawurlencode($fullText);
            $shareTextUrl = 'https://api.whatsapp.com/send?text=' . $encoded;
            $waMeUrl = 'https://wa.me/?text=' . $encoded;
        }

        return [
            'sub_course_id' => (int) ($subCourse['id'] ?? 0),
            'sub_course_name' => (string) ($subCourse['name_te'] ?? $subCourse['name'] ?? ''),
            'group_link' => $groupLink,
            'invite_token' => $token,
            'message' => $fullText,
            'share_text_url' => $shareTextUrl,
            'wa_me_url' => $waMeUrl,
            'web_whatsapp_url' => 'https://web.whatsapp.com/',
            'attachments' => $attachments,
            'instructions_te' => $groupLink === ''
                ? 'ముందుగు ఈ సబ్-కోర్స్ కోసం గ్రూప్ లింక్ సేవ్ చేయండి.'
                : 'గ్రూప్ తెరచి → సందేశం అతికించి లేదా షేర్ బటన్ ఉపయోగించండి.',
        ];
    }

    /**
     * @param array<string,mixed> $file $_FILES slice
     * @return array<string,mixed>
     */
    public function storeDispatchUpload(array $file): array
    {
        if (!isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException('No file uploaded');
        }
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Upload failed (code ' . (int) $file['error'] . ')');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('Invalid upload');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size < 1 || $size > self::MAX_FILE_BYTES) {
            throw new InvalidArgumentException('File must be between 1 byte and 50 MB');
        }

        $original = (string) ($file['name'] ?? 'attachment');
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $kind = $this->resolveKind($ext);
        if ($kind === null) {
            throw new InvalidArgumentException('Unsupported file type. Use PDF, audio, video, or image.');
        }

        $dir = ACHARYA_ROOT . '/storage/whatsapp_dispatch';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $this->purgeOldDispatchFiles($dir);

        $safe = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $dir . '/' . $safe;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('Could not store upload');
        }

        $relative = 'storage/whatsapp_dispatch/' . $safe;
        $mime = mime_content_type($dest) ?: 'application/octet-stream';

        return [
            'kind' => $kind,
            'path' => $relative,
            'public_url' => acharya_media_url($relative),
            'original_name' => $original,
            'mime' => $mime,
            'size' => $size,
        ];
    }

    private function resolveKind(string $ext): ?string
    {
        foreach (self::ALLOWED as $kind => $exts) {
            if (in_array($ext, $exts, true)) {
                return $kind;
            }
        }

        return null;
    }

    private function purgeOldDispatchFiles(string $dir): void
    {
        $cutoff = time() - 86400;
        foreach (glob($dir . '/*') ?: [] as $path) {
            if (is_file($path) && filemtime($path) < $cutoff) {
                @unlink($path);
            }
        }
    }
}
