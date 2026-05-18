<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/CryptoService.php';

final class AiApiSlotRepository
{
    public static function ready(): bool
    {
        return SchemaHelper::hasTable('st_ai_api_slots');
    }

    /** @return list<array<string,mixed>> */
    public function listSlots(): array
    {
        if (!self::ready()) {
            return [];
        }
        $rows = db()->query('SELECT * FROM st_ai_api_slots ORDER BY slot_index')->fetchAll() ?: [];
        foreach ($rows as &$r) {
            $enc = (string) ($r['api_key_encrypted'] ?? '');
            $r['has_key'] = $enc !== '' && CryptoService::decrypt($enc) !== '';
            unset($r['api_key_encrypted']);
        }
        unset($r);

        return $rows;
    }

    /** @return array<string,mixed>|null */
    public function activeSlot(): ?array
    {
        if (!self::ready()) {
            return null;
        }
        $st = db()->query('SELECT * FROM st_ai_api_slots WHERE is_active=1 ORDER BY slot_index LIMIT 1');
        $row = $st->fetch();
        if (!$row) {
            return null;
        }
        $row['api_key'] = CryptoService::decrypt((string) ($row['api_key_encrypted'] ?? ''));

        return $row;
    }

    /** @param list<array<string,mixed>> $slots */
    public function saveSlots(array $slots): void
    {
        if (!self::ready()) {
            throw new RuntimeException('Run migrate_mcq_ai_engine.php');
        }
        $st = db()->prepare(
            'UPDATE st_ai_api_slots SET provider=?, model_name=?, api_key_encrypted=?, is_active=? WHERE slot_index=?'
        );
        db()->exec('UPDATE st_ai_api_slots SET is_active=0');
        foreach ($slots as $slot) {
            $idx = (int) ($slot['slot_index'] ?? 0);
            if ($idx < 1 || $idx > 8) {
                continue;
            }
            $key = trim((string) ($slot['api_key'] ?? ''));
            $existing = db()->prepare('SELECT api_key_encrypted FROM st_ai_api_slots WHERE slot_index=?');
            $existing->execute([$idx]);
            $encExisting = (string) $existing->fetchColumn();
            if ($key === '' && $encExisting !== '') {
                $enc = $encExisting;
            } elseif ($key !== '') {
                $enc = CryptoService::encrypt($key);
            } else {
                $enc = base64_encode('');
            }
            $active = !empty($slot['is_active']) ? 1 : 0;
            $st->execute([
                (string) ($slot['provider'] ?? 'openai'),
                (string) ($slot['model_name'] ?? 'gpt-4o-mini'),
                $enc,
                $active,
                $idx,
            ]);
        }
    }
}
