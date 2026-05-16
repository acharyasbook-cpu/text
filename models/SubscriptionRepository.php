<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/SchemaHelper.php';

class SubscriptionRepository
{
    public function activePackagesForUser(int $userId): array
    {
        if (SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id')) {
            $sql = 'SELECT us.*, p.slug, p.name, p.name_te, p.package_type, p.price_inr, p.includes_division_tests,
                           c.slug AS course_slug, s.slug AS subject_slug,
                           sp.label AS plan_label, sp.plan_code, scp.name AS plan_sub_course_name
                    FROM user_subscriptions us
                    LEFT JOIN sub_course_packages p ON p.id = us.package_id
                    LEFT JOIN sub_course_plans sp ON sp.id = us.sub_course_plan_id
                    LEFT JOIN sub_courses scp ON scp.id = sp.sub_course_id
                    LEFT JOIN courses c ON c.id = COALESCE(p.course_id, scp.course_id)
                    LEFT JOIN subjects s ON s.id = p.subject_id
                    WHERE us.user_id = ? AND us.status = "active"
                      AND (us.expires_at IS NULL OR us.expires_at > NOW())
                    ORDER BY us.purchased_at DESC';
            $stmt = db()->prepare($sql);
            $stmt->execute([$userId]);

            return $stmt->fetchAll();
        }

        $sql = 'SELECT us.*, p.slug, p.name, p.name_te, p.package_type, p.price_inr, p.includes_division_tests,
                       c.slug AS course_slug, s.slug AS subject_slug
                FROM user_subscriptions us
                JOIN sub_course_packages p ON p.id = us.package_id
                LEFT JOIN courses c ON c.id = p.course_id
                LEFT JOIN subjects s ON s.id = p.subject_id
                WHERE us.user_id = ? AND us.status = "active"
                  AND (us.expires_at IS NULL OR us.expires_at > NOW())
                ORDER BY us.purchased_at DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function userHasSubjectAccess(int $userId, int $subjectId): bool
    {
        if (SchemaHelper::hasTable('sub_course_plans') && SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id')) {
            $spLive = SchemaHelper::columnExists('sub_course_plans', 'status')
                ? 'sp.status = 1 AND sp.is_active = 1' : 'sp.is_active = 1';
            $pcs = SchemaHelper::columnExists('sub_course_subjects', 'status')
                ? 'scs.status = 1 AND scs.is_active = 1' : 'scs.is_active = 1';
            $sql = "SELECT 1 FROM user_subscriptions us
                JOIN sub_course_plans sp ON sp.id = us.sub_course_plan_id AND {$spLive}
                JOIN sub_course_subjects scs ON scs.sub_course_id = sp.sub_course_id AND scs.subject_id = ? AND {$pcs}
                WHERE us.user_id = ? AND us.status = \"active\"
                  AND (us.expires_at IS NULL OR us.expires_at > NOW())
                LIMIT 1";
            $stmt = db()->prepare($sql);
            $stmt->execute([$subjectId, $userId]);
            if ($stmt->fetch()) {
                return true;
            }
        }

        if (!SchemaHelper::columnExists('subjects', 'course_id')) {
            return false;
        }
        $sql = 'SELECT 1 FROM user_subscriptions us
                JOIN sub_course_packages p ON p.id = us.package_id
                WHERE us.user_id = ? AND us.status = "active"
                  AND (us.expires_at IS NULL OR us.expires_at > NOW())
                  AND (p.subject_id = ? OR (p.package_type = "course_bundle" AND p.course_id = (SELECT course_id FROM subjects WHERE id = ? LIMIT 1)))
                LIMIT 1';
        $stmt = db()->prepare($sql);
        $stmt->execute([$userId, $subjectId, $subjectId]);

        return (bool) $stmt->fetch();
    }

    public function userHasTestAccess(int $userId, int $testId): bool
    {
        $test = db()->prepare('SELECT * FROM tests WHERE id = ?');
        $test->execute([$testId]);
        $t = $test->fetch();
        if (!$t) {
            return false;
        }
        if (empty($t['package_id'])) {
            return true;
        }
        $sql = 'SELECT 1 FROM user_subscriptions us
                WHERE us.user_id = ? AND us.package_id = ? AND us.status = "active"
                  AND (us.expires_at IS NULL OR us.expires_at > NOW()) LIMIT 1';
        $stmt = db()->prepare($sql);
        $stmt->execute([$userId, $t['package_id']]);
        return (bool) $stmt->fetch();
    }

    public function packagesForCourse(int $courseId): array
    {
        $extra = SchemaHelper::columnExists('sub_course_packages', 'status') ? ' AND status = 1' : '';
        $stmt = db()->prepare('SELECT * FROM sub_course_packages WHERE course_id = ? AND is_active = 1' . $extra . ' ORDER BY price_inr');
        $stmt->execute([$courseId]);

        return $stmt->fetchAll();
    }
}
