<?php

declare(strict_types=1);

/** Grade Current Affairs exam (25 MCQs, 1 mark each, optional negative). */
final class CurrentAffairsExamEngine
{
    public const MARKS_PER_QUESTION = 1;

    public const NEGATIVE_MARK = 0.25;

    /**
     * @param list<array<string,mixed>> $questions from pool rows (must include id)
     * @param array<string,string> $answers keyed by question id
     * @return array<string,mixed>
     */
    public static function grade(array $questions, array $answers, int $timeTaken, string $examDate): array
    {
        $correct = 0;
        $wrong = 0;
        $unanswered = 0;
        $score = 0.0;
        $maxScore = 0.0;
        $sheet = [];

        foreach ($questions as $idx => $q) {
            $qid = (string) ($q['id'] ?? '');
            $marks = self::MARKS_PER_QUESTION;
            $maxScore += $marks;
            $correctOpt = strtoupper((string) ($q['correct_option'] ?? 'A'));
            $selected = isset($answers[$qid]) && $answers[$qid] !== ''
                ? strtoupper((string) $answers[$qid])
                : null;

            $isCorrect = $selected !== null && $selected === $correctOpt;
            if ($selected === null) {
                $unanswered++;
            } elseif ($isCorrect) {
                $correct++;
                $score += $marks;
            } else {
                $wrong++;
                $score -= self::NEGATIVE_MARK;
            }

            $sheet[] = [
                'num' => $idx + 1,
                'question_text' => (string) ($q['question_text'] ?? ''),
                'options' => [
                    'A' => (string) ($q['option_a'] ?? ''),
                    'B' => (string) ($q['option_b'] ?? ''),
                    'C' => (string) ($q['option_c'] ?? ''),
                    'D' => (string) ($q['option_d'] ?? ''),
                ],
                'selected' => $selected,
                'correct_option' => $correctOpt,
                'is_correct' => $isCorrect,
                'unanswered' => $selected === null,
            ];
        }

        $score = max(0, $score);
        $totalQ = count($questions);

        return [
            'score' => $score,
            'max_score' => $maxScore > 0 ? $maxScore : (float) $totalQ,
            'correct' => $correct,
            'wrong' => $wrong,
            'unanswered' => $unanswered,
            'total_questions' => $totalQ,
            'time_taken' => $timeTaken,
            'answer_sheet' => $sheet,
            'exam_date' => $examDate,
            'test_title' => 'Daily Current Affairs · ' . date('d M Y', strtotime($examDate)),
        ];
    }
}
