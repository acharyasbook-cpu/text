<?php

declare(strict_types=1);

/**
 * Universal image upload validation & storage (logos, covers, admin CM).
 * Accepts jpeg, png, jpg, gif, svg, webp — any letter case extension.
 * Max 10 MB. SVG stored as-is (no GD recompression).
 */
final class ImageUploadService
{
    public const MAX_BYTES = 10485760; // 10 MB

    public const PURPOSE_BRANDING = 'branding';

    public const PURPOSE_COVER = 'cover';

    /** Content Manager entity key → public upload folder */
    public const COVER_ENTITY_FOLDERS = [
        'course' => 'main_courses',
        'sub_course' => 'sub_courses',
        'subject' => 'subjects',
    ];

    /** Target aspect ratio for card covers (16:10). */
    private const COVER_TARGET_W = 1280;

    private const COVER_TARGET_H = 800;

    /** @var list<string> */
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];

    /** @var list<string> */
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/svg',
    ];

    /** @var array<string, string> extension → canonical ext */
    private const EXT_ALIASES = [
        'jpeg' => 'jpg',
    ];

    /**
     * Store an uploaded file from $_FILES[$field] or a single file array.
     *
     * @param array<string,mixed> $file
     */
    public static function storeFromFileArray(array $file, string $purpose, ?string $subdir = null): string
    {
        if (!isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException('No file was uploaded.');
        }
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(self::uploadErrorMessage((int) $file['error']));
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('Invalid upload payload.');
        }

        $original = (string) ($file['name'] ?? 'upload');
        $size = (int) ($file['size'] ?? 0);
        if ($size < 1) {
            throw new InvalidArgumentException('Uploaded file is empty.');
        }
        if ($size > self::MAX_BYTES) {
            throw new InvalidArgumentException('Image must be 10 MB or smaller.');
        }

        $ext = self::resolveExtension($original, $tmp);
        $mime = self::detectMime($tmp);

        if (!self::isAllowedMime($mime, $ext)) {
            throw new InvalidArgumentException(
                'Unsupported image type. Allowed: JPG, JPEG, PNG, GIF, SVG, WEBP (any case).'
            );
        }

        $canonicalExt = self::EXT_ALIASES[$ext] ?? $ext;
        $baseName = self::sanitizedBaseName($original);
        $filename = $baseName . '_' . bin2hex(random_bytes(4)) . '.' . $canonicalExt;

        $destDir = self::destinationDirectory($purpose, $subdir);
        $destPath = $destDir . '/' . $filename;

        if ($ext === 'svg') {
            $raw = file_get_contents($tmp);
            if ($raw === false || trim($raw) === '') {
                throw new InvalidArgumentException('SVG file is empty or unreadable.');
            }
            $sanitized = self::sanitizeSvg($raw);
            if (file_put_contents($destPath, $sanitized) === false) {
                throw new RuntimeException('Failed to save SVG.');
            }
        } else {
            if (!move_uploaded_file($tmp, $destPath)) {
                throw new RuntimeException('Failed to store uploaded image.');
            }
            self::normalizeRasterCover($destPath, $canonicalExt);
        }

        self::applyFilePermissions($destPath);

        return self::relativePath($purpose, $subdir, $filename);
    }

    public static function coverFolderForEntity(string $entityKey): string
    {
        $key = preg_replace('/[^a-z_]/', '', strtolower($entityKey));

        return self::COVER_ENTITY_FOLDERS[$key] ?? 'misc';
    }

    public static function deleteIfStored(?string $relativePath): void
    {
        if ($relativePath === null || trim($relativePath) === '') {
            return;
        }
        $relative = ltrim(str_replace('\\', '/', $relativePath), '/');
        if (str_contains($relative, '..')) {
            return;
        }
        $abs = ACHARYA_ROOT . '/' . $relative;
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    public static function acceptAttribute(): string
    {
        return '.jpg,.jpeg,.png,.gif,.svg,.webp,.JPG,.JPEG,.PNG,.GIF,.SVG,.WEBP,.WebP,image/jpeg,image/png,image/gif,image/svg+xml,image/webp';
    }

    /** Pre-create upload roots with 0755 permissions (branding + course media). */
    public static function ensureStorageRoots(): void
    {
        foreach (self::brandingDirectories() as $dir) {
            self::ensureDirectory($dir);
        }
        self::ensureDirectory(ACHARYA_ROOT . '/public/uploads');
        foreach (self::COVER_ENTITY_FOLDERS as $folder) {
            self::ensureDirectory(ACHARYA_ROOT . '/public/uploads/' . $folder);
        }
    }

    /** @return list<string> */
    private static function brandingDirectories(): array
    {
        return [
            ACHARYA_ROOT . '/public/assets/images/branding',
            ACHARYA_ROOT . '/assets/images/branding',
        ];
    }

    private static function ensureDirectory(string $absolutePath): void
    {
        if (is_dir($absolutePath)) {
            @chmod($absolutePath, 0755);

            return;
        }
        if (!mkdir($absolutePath, 0755, true) && !is_dir($absolutePath)) {
            throw new RuntimeException('Could not create directory: ' . $absolutePath);
        }
        @chmod($absolutePath, 0755);
    }

    private static function destinationDirectory(string $purpose, ?string $subdir): string
    {
        if ($purpose === self::PURPOSE_BRANDING) {
            $dirs = self::brandingDirectories();
            foreach ($dirs as $dir) {
                self::ensureDirectory($dir);
            }

            return $dirs[0];
        }

        $folder = self::coverFolderForEntity((string) $subdir);
        $dir = ACHARYA_ROOT . '/public/uploads/' . $folder;
        self::ensureDirectory($dir);

        return $dir;
    }

    private static function relativePath(string $purpose, ?string $subdir, string $filename): string
    {
        if ($purpose === self::PURPOSE_BRANDING) {
            return 'public/assets/images/branding/' . $filename;
        }

        $folder = self::coverFolderForEntity((string) $subdir);

        return 'public/uploads/' . $folder . '/' . $filename;
    }

    /** GD center-crop resize for consistent 16:10 card grids (skipped for SVG). */
    private static function normalizeRasterCover(string $absolutePath, string $ext): void
    {
        if ($ext === 'svg' || !is_file($absolutePath)) {
            return;
        }
        if (!function_exists('imagecreatetruecolor')) {
            return;
        }

        $src = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($absolutePath),
            'png' => @imagecreatefrompng($absolutePath),
            'gif' => @imagecreatefromgif($absolutePath),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : false,
            default => false,
        };
        if ($src === false) {
            return;
        }

        $sw = imagesx($src);
        $sh = imagesy($src);
        if ($sw < 1 || $sh < 1) {
            imagedestroy($src);

            return;
        }

        $tw = self::COVER_TARGET_W;
        $th = self::COVER_TARGET_H;
        $srcRatio = $sw / $sh;
        $dstRatio = $tw / $th;

        if ($srcRatio > $dstRatio) {
            $cropH = $sh;
            $cropW = (int) round($sh * $dstRatio);
            $sx = (int) floor(($sw - $cropW) / 2);
            $sy = 0;
        } else {
            $cropW = $sw;
            $cropH = (int) round($sw / $dstRatio);
            $sx = 0;
            $sy = (int) floor(($sh - $cropH) / 2);
        }

        $dst = imagecreatetruecolor($tw, $th);
        if ($ext === 'png' || $ext === 'gif') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $tw, $th, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $tw, $th, $cropW, $cropH);
        imagedestroy($src);

        $ok = match ($ext) {
            'jpg', 'jpeg' => imagejpeg($dst, $absolutePath, 88),
            'png' => imagepng($dst, $absolutePath, 7),
            'gif' => imagegif($dst, $absolutePath),
            'webp' => function_exists('imagewebp') ? imagewebp($dst, $absolutePath, 85) : false,
            default => false,
        };
        imagedestroy($dst);

        if (!$ok) {
            throw new RuntimeException('Failed to optimize uploaded image.');
        }
    }

    private static function resolveExtension(string $originalName, string $tmpPath): string
    {
        $ext = self::normalizeExtension(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext !== '' && in_array($ext, self::ALLOWED_EXT, true)) {
            return $ext;
        }

        $mime = self::detectMime($tmpPath);
        $fromMime = self::extensionFromMime($mime);
        if ($fromMime !== '') {
            return $fromMime;
        }

        return '';
    }

    private static function detectMime(string $tmpPath): string
    {
        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $tmpPath);
                finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    $mime = strtolower($detected);
                }
            }
        }
        if ($mime === '' && function_exists('mime_content_type')) {
            $detected = mime_content_type($tmpPath);
            if (is_string($detected)) {
                $mime = strtolower($detected);
            }
        }

        return $mime;
    }

    private static function isAllowedMime(string $mime, string $ext): bool
    {
        if ($ext !== '' && in_array($ext, self::ALLOWED_EXT, true)) {
            if ($ext === 'svg') {
                return true;
            }
            if ($mime === '' || $mime === 'application/octet-stream') {
                return true;
            }
        }

        if ($mime === '') {
            return $ext !== '' && in_array($ext, self::ALLOWED_EXT, true);
        }

        if (in_array($mime, self::ALLOWED_MIMES, true)) {
            return true;
        }

        return str_starts_with($mime, 'image/') && $ext !== '' && in_array($ext, self::ALLOWED_EXT, true);
    }

    private static function extensionFromMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg', 'image/pjpeg' => 'jpg',
            'image/png', 'image/x-png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml', 'image/svg' => 'svg',
            default => '',
        };
    }

    private static function sanitizedBaseName(string $originalName): string
    {
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $base = strtolower((string) preg_replace('/[^a-z0-9_-]+/', '-', $base));
        $base = trim($base, '-');
        if ($base === '') {
            $base = 'image';
        }

        return substr($base, 0, 48);
    }

    private static function sanitizeSvg(string $contents): string
    {
        $contents = preg_replace('/<\?(php|=)/i', '', $contents) ?? $contents;
        $contents = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $contents) ?? $contents;
        $contents = preg_replace('/\s(on\w+)\s*=\s*["\'][^"\']*["\']/i', '', $contents) ?? $contents;

        return $contents;
    }

    private static function normalizeExtension(string $ext): string
    {
        $ext = strtolower(trim($ext));
        if ($ext === 'pjpeg') {
            return 'jpg';
        }

        return $ext;
    }

    private static function applyFilePermissions(string $absolutePath): void
    {
        @chmod($absolutePath, 0644);
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds maximum upload size (10 MB allowed).',
            UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temp folder missing. Contact administrator.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the file.',
            UPLOAD_ERR_EXTENSION => 'Server blocked this file type.',
            default => 'Upload failed (error ' . $code . ').',
        };
    }
}
