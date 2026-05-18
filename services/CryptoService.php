<?php

declare(strict_types=1);

/** AES-256-GCM encryption for API keys at rest. */
final class CryptoService
{
    private const CIPHER = 'aes-256-gcm';

    public static function encrypt(string $plaintext, ?string $key = null): string
    {
        $key = self::normalizeKey($key);
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $payload, ?string $key = null): string
    {
        if ($payload === '') {
            return '';
        }
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 29) {
            return '';
        }
        $key = self::normalizeKey($key);
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $plain = openssl_decrypt($cipher, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $plain === false ? '' : $plain;
    }

    private static function normalizeKey(?string $key): string
    {
        if ($key === null || $key === '') {
            $cfg = require dirname(__DIR__) . '/config/mcq_ai.php';
            $key = (string) ($cfg['encryption_key'] ?? '');
        }

        return hash('sha256', $key, true);
    }
}
