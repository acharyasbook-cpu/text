<?php

declare(strict_types=1);

/** AP / TS DSC & TET difficulty tiers for AI MCQ generation. */
final class McqDifficultyProfiles
{
    public const BATCH_CHUNK_SIZE = 12;

    public const MAX_QUESTIONS_PER_PAGE = 500;

    /** @var array<string,array{label:string,exam_context:string,instruction:string}> */
    public const SCALES = [
        'SGT' => [
            'label' => 'SGT — Secondary Grade Teacher',
            'exam_context' => 'AP/TS DSC & TET — Secondary Grade Teacher (Matric / Intermediate standard)',
            'instruction' => 'Use Matric and Intermediate level rigor. Focus on foundational concepts, direct recall, '
                . 'straightforward application, and clear wording suitable for secondary-grade pedagogy. '
                . 'Avoid postgraduate jargon or highly abstract traps.',
        ],
        'SA' => [
            'label' => 'SA — School Assistant',
            'exam_context' => 'AP/TS DSC & TET — School Assistant (Graduate standard)',
            'instruction' => 'Use graduate-level depth with moderate analysis. Questions should reflect B.Ed / '
                . 'school-assistant competency: applied understanding, curriculum-aligned reasoning, and '
                . 'one-step multi-concept links without excessive trickery.',
        ],
        'TGT' => [
            'label' => 'TGT — Trained Graduate Teacher',
            'exam_context' => 'AP/TS DSC & TET — Trained Graduate Teacher (Upper secondary rigor)',
            'instruction' => 'Use trained-graduate-teacher standard: upper-secondary difficulty, stronger analytical '
                . 'steps, syllabus integration across units, and distractors that test conceptual clarity rather than trivia.',
        ],
        'PGT' => [
            'label' => 'PGT — Post Graduate Teacher',
            'exam_context' => 'AP/TS DSC & TET — Post Graduate Teacher (Post-graduate rigor)',
            'instruction' => 'Use post-graduate rigor: higher-order thinking, subtle distinctions between close options, '
                . 'multi-step reasoning, and examination traps appropriate for PGT / senior competitive tiers. '
                . 'Maintain factual accuracy and non-ambiguous keys.',
        ],
    ];

    public static function normalize(string $scale): string
    {
        $scale = strtoupper(trim($scale));

        return array_key_exists($scale, self::SCALES) ? $scale : 'SGT';
    }

    /** @return list<array{code:string,label:string,exam_context:string}> */
    public static function listForUi(): array
    {
        $out = [];
        foreach (self::SCALES as $code => $meta) {
            $out[] = [
                'code' => $code,
                'label' => $meta['label'],
                'exam_context' => $meta['exam_context'],
            ];
        }

        return $out;
    }

    public static function systemInstruction(string $scale): string
    {
        $scale = self::normalize($scale);
        $meta = self::SCALES[$scale];

        return $meta['instruction'] . ' Exam track: ' . $meta['exam_context'] . '.';
    }

    public static function normalizeQuestionCount(int $n): int
    {
        return max(1, min(self::MAX_QUESTIONS_PER_PAGE, $n));
    }

    /** @return list<int> Batch sizes summing to $total */
    public static function batchSizes(int $total): array
    {
        $total = self::normalizeQuestionCount($total);
        $chunk = self::BATCH_CHUNK_SIZE;
        $batches = [];
        $left = $total;
        while ($left > 0) {
            $take = min($chunk, $left);
            $batches[] = $take;
            $left -= $take;
        }

        return $batches;
    }
}
