<?php

declare(strict_types=1);

final class WhatsAppHubRepository
{
    /** @return list<array<string,mixed>> */
    public function subCoursesForHub(?int $courseId = null): array
    {
        if (!SchemaHelper::hasTable('sub_courses')) {
            return [];
        }

        $hasLink = SchemaHelper::columnExists('sub_courses', 'whatsapp_group_link');
        $linkCol = $hasLink ? ', sc.whatsapp_group_link' : '';
        $img = SchemaHelper::imagePathEnabled('sub_courses') ? ', sc.image_path' : '';
        $live = SchemaHelper::columnExists('sub_courses', 'status')
            ? 'AND COALESCE(sc.status, sc.is_active, 1) = 1'
            : 'AND sc.is_active = 1';

        $sql = "SELECT sc.id, sc.slug, sc.name, sc.name_te, sc.course_id, c.slug AS course_slug, c.name AS course_name{$linkCol}{$img}
                FROM sub_courses sc
                INNER JOIN courses c ON c.id = sc.course_id
                WHERE 1=1 {$live}";
        $params = [];
        if ($courseId !== null && $courseId > 0) {
            $courseId = (new AdminRepository())->resolveContentManagerCourseId($courseId);
            $sql .= ' AND sc.course_id = ?';
            $params[] = $courseId;
        }
        $sql .= ' ORDER BY ' . SchemaHelper::sqlOrderBySort('sc.sort_order', 'sc.id');

        $st = db()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll() ?: [];
    }

    /** @return array<string,mixed>|null */
    public function subCourseRow(int $subCourseId): ?array
    {
        if ($subCourseId < 1) {
            return null;
        }
        $hasLink = SchemaHelper::columnExists('sub_courses', 'whatsapp_group_link');
        $linkCol = $hasLink ? ', sc.whatsapp_group_link' : '';
        $st = db()->prepare(
            "SELECT sc.id, sc.slug, sc.name, sc.name_te, sc.course_id, c.slug AS course_slug, c.name AS course_name{$linkCol}
             FROM sub_courses sc
             INNER JOIN courses c ON c.id = sc.course_id
             WHERE sc.id = ?
             LIMIT 1"
        );
        $st->execute([$subCourseId]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public function saveGroupLink(int $subCourseId, string $link): void
    {
        if ($subCourseId < 1) {
            throw new InvalidArgumentException('sub_course_id required');
        }
        if (!SchemaHelper::columnExists('sub_courses', 'whatsapp_group_link')) {
            throw new RuntimeException('Run: php database/migrate_whatsapp_sub_course_groups.php');
        }

        $normalized = WhatsAppDispatchService::normalizeGroupInviteLink($link);
        db()->prepare('UPDATE sub_courses SET whatsapp_group_link = ? WHERE id = ?')
            ->execute([$normalized !== '' ? $normalized : null, $subCourseId]);
    }

    /** @return list<array<string,mixed>> */
    public function mainCoursesForPicker(): array
    {
        return (new AdminRepository())->contentManagerMainCourses();
    }
}
