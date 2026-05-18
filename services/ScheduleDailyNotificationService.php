<?php

declare(strict_types=1);

/**
 * Compiles combined Short + Long term daily WhatsApp payloads for Schedule Test Manager.
 */
final class ScheduleDailyNotificationService
{
    public function __construct(
        private ?ScheduleTestRepository $scheduleRepo = null
    ) {
        $this->scheduleRepo = $scheduleRepo ?? new ScheduleTestRepository();
    }

    /**
     * @return array{text:string,short_lines:list<string>,long_lines:list<string>,date_label:string,course_name:string}
     */
    public function compileDailyNotification(
        int $subCourseId,
        ?int $dayIndex = null,
        ?string $scheduleDate = null
    ): array {
        $meta = $this->subCourseMeta($subCourseId);
        $courseName = (string) ($meta['course_label'] ?? 'Course');
        $dateLabel = $this->dateLabel($dayIndex, $scheduleDate);

        $shortDayId = $this->resolveDayId($subCourseId, ScheduleTestRepository::TERM_SHORT, $dayIndex, $scheduleDate);
        $longDayId = $this->resolveDayId($subCourseId, ScheduleTestRepository::TERM_LONG, $dayIndex, $scheduleDate);

        $shortLines = $shortDayId > 0 ? $this->planLinesForDay($shortDayId) : [];
        $longLines = $longDayId > 0 ? $this->planLinesForDay($longDayId) : [];

        $text = $this->formatMessage($courseName, $dateLabel, $shortLines, $longLines);

        return [
            'text' => $text,
            'short_lines' => $shortLines,
            'long_lines' => $longLines,
            'date_label' => $dateLabel,
            'course_name' => $courseName,
            'short_day_id' => $shortDayId,
            'long_day_id' => $longDayId,
        ];
    }

    /** @param list<string> $shortLines @param list<string> $longLines */
    public function formatMessage(string $courseName, string $dateLabel, array $shortLines, array $longLines): string
    {
        $out = "-----------------------------------------\n";
        $out .= "📚 ACHARYA BOOKS - DAILY PLAN ({$dateLabel}) 📚\n";
        $out .= "🎯 COURSE: {$courseName}\n";
        $out .= "-----------------------------------------\n";
        $out .= "⏱️ SHORT TERM PLAN:\n";
        $out .= $shortLines !== [] ? $this->bulletBlock($shortLines) : "• (ఇంకా షెడ్యూల్ లేదు)\n";
        $out .= "[Task / Test Link]\n";
        $out .= "-----------------------------------------\n";
        $out .= "⏳ LONG TERM PLAN:\n";
        $out .= $longLines !== [] ? $this->bulletBlock($longLines) : "• (ఇంకా షెడ్యూల్ లేదు)\n";
        $out .= "[Task / Study Guidelines]\n";
        $out .= "-----------------------------------------";

        return $out;
    }

    /** @param list<string> $lines */
    private function bulletBlock(array $lines): string
    {
        $block = '';
        foreach ($lines as $line) {
            $block .= '• ' . $line . "\n";
        }

        return $block;
    }

    /** @return list<string> */
    private function planLinesForDay(int $dayId): array
    {
        $lines = [];
        foreach ($this->scheduleRepo->rowsForDay($dayId) as $row) {
            $topicLabel = $this->topicLabelForRow($row);
            $qc = $this->scheduleRepo->questionCountForRow($row);
            $bits = $qc > 0 ? $qc : (int) ($row['total_marks'] ?? 0);
            $lines[] = $topicLabel . ' (' . $bits . ' Bits)';
        }

        return $lines;
    }

    /** @param array<string,mixed> $row */
    private function topicLabelForRow(array $row): string
    {
        $meta = $row['row_meta'] ?? [];
        if (is_string($meta)) {
            $meta = json_decode($meta, true) ?: [];
        }
        if (!empty($meta['topic_label'])) {
            return trim((string) $meta['topic_label']);
        }
        $names = [];
        foreach ($row['topics'] ?? [] as $t) {
            if (!empty($t['title'])) {
                $names[] = (string) $t['title'];
            }
        }
        if ($names !== []) {
            return implode(', ', $names);
        }
        $sub = (string) (($row['subject_name_te'] ?? '') !== '' ? $row['subject_name_te'] : ($row['subject_name'] ?? 'Subject'));

        return $sub;
    }

    private function dateLabel(?int $dayIndex, ?string $scheduleDate): string
    {
        if ($scheduleDate !== null && $scheduleDate !== '') {
            return $scheduleDate;
        }
        if ($dayIndex !== null && $dayIndex > 0) {
            return 'Day ' . $dayIndex;
        }

        return date('Y-m-d');
    }

    private function resolveDayId(int $subCourseId, string $termKey, ?int $dayIndex, ?string $scheduleDate): int
    {
        if ($scheduleDate !== null && $scheduleDate !== '') {
            $st = db()->prepare(
                'SELECT id FROM st_schedule_days WHERE sub_course_id=? AND term_key=? AND schedule_date=? LIMIT 1'
            );
            $st->execute([$subCourseId, $termKey, $scheduleDate]);
            $id = $st->fetchColumn();

            return $id ? (int) $id : 0;
        }
        if ($dayIndex !== null && $dayIndex > 0) {
            $day = $this->scheduleRepo->dayByIndex($subCourseId, $termKey, $dayIndex);

            return $day ? (int) ($day['id'] ?? 0) : 0;
        }

        return 0;
    }

    /** @return array{course_label:string,name_te:string,name:string} */
    private function subCourseMeta(int $subCourseId): array
    {
        $st = db()->prepare(
            'SELECT sc.name, sc.name_te, c.name AS course_name, c.name_te AS course_name_te
             FROM sub_courses sc
             JOIN courses c ON c.id = sc.course_id
             WHERE sc.id=? LIMIT 1'
        );
        $st->execute([$subCourseId]);
        $row = $st->fetch() ?: [];
        $courseTe = trim((string) ($row['course_name_te'] ?? ''));
        $courseEn = trim((string) ($row['course_name'] ?? ''));
        $scTe = trim((string) ($row['name_te'] ?? ''));
        $scEn = trim((string) ($row['name'] ?? ''));
        $courseLabel = $courseTe !== '' ? $courseTe : $courseEn;
        if ($scTe !== '' || $scEn !== '') {
            $sub = $scTe !== '' ? $scTe : $scEn;
            $courseLabel = $courseLabel !== '' ? $courseLabel . ' · ' . $sub : $sub;
        }

        return [
            'course_label' => $courseLabel !== '' ? $courseLabel : 'Acharya Books',
            'name_te' => $scTe,
            'name' => $scEn,
        ];
    }
}
