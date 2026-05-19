<?php

declare(strict_types=1);

/**
 * Lenient bulk MCQ parser — Telugu (ఎ/బి/సి/డి), English A-D, answer-key footer.
 */
final class CurrentAffairsManualParser
{
    /** @var array<string,string> */
    private const LETTER_MAP = [
        'a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D',
        'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D',
        'ఎ' => 'A', 'ఏ' => 'A', 'అ' => 'A',
        'బి' => 'B', 'బీ' => 'B', 'బ' => 'B',
        'సి' => 'C', 'సీ' => 'C', 'స' => 'C', 'చి' => 'C', 'చీ' => 'C', 'సి)' => 'C',
        'డి' => 'D', 'డీ' => 'D', 'డ' => 'D',
        '1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D',
    ];

    /**
     * @return list<array{question_text:string,option_a:string,option_b:string,option_c:string,option_d:string,correct_option:string}>
     */
    public static function parseBulk(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", trim($raw));
        if ($raw === '') {
            return [];
        }

        if ($raw[0] === '[' || $raw[0] === '{') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $list = $json['questions'] ?? $json;
                if (is_array($list)) {
                    return self::normalizeList($list);
                }
            }
        }

        [$answerKey, $body] = self::extractAnswerKey($raw);
        $blocks = self::splitQuestionBlocks($body);
        $out = [];

        foreach ($blocks as $num => $block) {
            $parsed = self::parseBlock($block, $answerKey[$num] ?? null);
            if ($parsed !== null) {
                $out[] = $parsed;
            }
        }

        if (count($out) < 5 && $body !== '') {
            $out = self::parseInlineOptionsFormat($body, $answerKey);
        }

        return $out;
    }

    /**
     * @return array{0: array<int,string>, 1: string}
     */
    private static function extractAnswerKey(string $raw): array
    {
        $key = [];
        $patterns = [
            '/సరైన\s*సమాధానాలు\s*[:：]?\s*(.+)$/ium',
            '/(?:answer\s*key|answers|సమాధానాలు)\s*[:：]?\s*(.+)$/ium',
        ];
        foreach ($patterns as $pat) {
            if (preg_match($pat, $raw, $m)) {
                $segment = (string) $m[1];
                if (preg_match_all('/(\d+)\s*[-–—:]\s*([^\s,;]+)/u', $segment, $hits, PREG_SET_ORDER)) {
                    foreach ($hits as $hit) {
                        $latin = self::toLatinOption((string) $hit[2]);
                        if ($latin !== null) {
                            $key[(int) $hit[1]] = $latin;
                        }
                    }
                }
                $raw = preg_replace($pat, '', $raw) ?? $raw;
                break;
            }
        }

        return [$key, trim($raw)];
    }

    /** @return array<int,string> */
    private static function splitQuestionBlocks(string $body): array
    {
        $blocks = [];
        if (!preg_match_all(
            '/(?:^|\n)\s*(\d+)\s*[\.\)\]:\-]?\s+/u',
            $body,
            $matches,
            PREG_OFFSET_CAPTURE
        )) {
            return [];
        }

        $count = count($matches[0]);
        for ($i = 0; $i < $count; $i++) {
            $num = (int) $matches[1][$i][0];
            $start = $matches[0][$i][1];
            $end = $i + 1 < $count ? $matches[0][$i + 1][1] : strlen($body);
            $chunk = trim(substr($body, $start, $end - $start));
            if ($chunk !== '') {
                $blocks[$num] = $chunk;
            }
        }

        return $blocks;
    }

    /**
     * @param array<int,string> $answerKey
     * @return list<array{question_text:string,option_a:string,option_b:string,option_c:string,option_d:string,correct_option:string}>
     */
    private static function parseInlineOptionsFormat(string $body, array $answerKey): array
    {
        $blocks = self::splitQuestionBlocks($body);
        $out = [];
        foreach ($blocks as $num => $block) {
            $p = self::parseBlock($block, $answerKey[$num] ?? null);
            if ($p !== null) {
                $out[] = $p;
            }
        }

        return $out;
    }

    /** @return array{question_text:string,option_a:string,option_b:string,option_c:string,option_d:string,correct_option:string}|null */
    private static function parseBlock(string $block, ?string $forcedCorrect): ?array
    {
        $block = preg_replace('/^\d+\s*[\.\)\]:\-]?\s*/u', '', $block) ?? $block;
        $lines = preg_split('/\n+/u', $block) ?: [];
        $questionParts = [];
        $opts = ['A' => '', 'B' => '', 'C' => '', 'D' => ''];
        $inlineCorrect = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^(?:answer|సమాధానం)\s*[:=]\s*(.+)$/iu', $line, $am)) {
                $inlineCorrect = self::toLatinOption(trim($am[1]));
                continue;
            }

            if ($opt = self::matchOptionLine($line)) {
                $opts[$opt['letter']] = $opt['text'];
                continue;
            }

            if (preg_match_all(
                '/(?:^|\s)([A-Da-dఎబిసిడచీఏబీసీడి]|[1-4])[\)\.\:]\s*([^\n]+?)(?=\s+(?:[A-Da-dఎబిసిడచీఏబీసీడి]|[1-4])[\)\.\:]|$)/u',
                $line,
                $inlineOpts,
                PREG_SET_ORDER
            ) && count($inlineOpts) >= 2) {
                foreach ($inlineOpts as $io) {
                    $letter = self::toLatinOption((string) $io[1]);
                    if ($letter !== null) {
                        $opts[$letter] = trim((string) $io[2]);
                    }
                }
                $before = preg_split('/\s*(?:[A-Da-dఎబిసిడ]|[1-4])[\)\.\:]/u', $line, 2);
                if (!empty($before[0]) && trim($before[0]) !== '') {
                    $questionParts[] = trim($before[0]);
                }
                continue;
            }

            $questionParts[] = $line;
        }

        $question = trim(implode(' ', $questionParts));
        if ($question === '') {
            return null;
        }

        $filled = array_filter($opts, static fn ($v) => $v !== '');
        if (count($filled) < 2) {
            return null;
        }
        foreach (['C', 'D'] as $L) {
            if ($opts[$L] === '') {
                $opts[$L] = 'ఎంపిక ' . $L;
            }
        }

        $correct = $forcedCorrect ?? $inlineCorrect ?? 'A';
        if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
            $correct = 'A';
        }

        return [
            'question_text' => $question,
            'option_a' => $opts['A'],
            'option_b' => $opts['B'],
            'option_c' => $opts['C'],
            'option_d' => $opts['D'],
            'correct_option' => $correct,
        ];
    }

    /** @return array{letter:string,text:string}|null */
    private static function matchOptionLine(string $line): ?array
    {
        $optPrefix = '(బి|బీ|ఎ|ఏ|సి|సీ|చి|చీ|డి|డీ|[A-Da-d]|[1-4])';
        if (preg_match('/^' . $optPrefix . '\s*[\)\.\:।]?\s*(.+)$/u', $line, $m)) {
            $letter = self::toLatinOption((string) $m[1]);
            if ($letter !== null) {
                return ['letter' => $letter, 'text' => trim((string) $m[2])];
            }
        }
        if (preg_match('/^[\(\（]?\s*' . $optPrefix . '\s*[\)\）\.]\s*(.+)$/u', $line, $m)) {
            $letter = self::toLatinOption((string) $m[1]);
            if ($letter !== null) {
                return ['letter' => $letter, 'text' => trim((string) $m[2])];
            }
        }

        return null;
    }

    private static function toLatinOption(string $token): ?string
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $token = preg_replace('/[\)\.\:।]+$/u', '', $token) ?? $token;
        $candidates = [$token, mb_strtolower($token, 'UTF-8')];
        foreach ([2, 1] as $len) {
            if (mb_strlen($token, 'UTF-8') >= $len) {
                $candidates[] = mb_substr($token, 0, $len, 'UTF-8');
            }
        }
        foreach ($candidates as $c) {
            if (isset(self::LETTER_MAP[$c])) {
                return self::LETTER_MAP[$c];
            }
        }

        return null;
    }

    /**
     * @param list<array<string,mixed>> $list
     * @return list<array{question_text:string,option_a:string,option_b:string,option_c:string,option_d:string,correct_option:string}>
     */
    private static function normalizeList(array $list): array
    {
        $out = [];
        foreach ($list as $q) {
            if (!is_array($q)) {
                continue;
            }
            $text = trim((string) ($q['question_text'] ?? $q['question'] ?? ''));
            if ($text === '') {
                continue;
            }
            $opt = self::toLatinOption((string) ($q['correct_option'] ?? $q['answer'] ?? 'A')) ?? 'A';
            $out[] = [
                'question_text' => $text,
                'option_a' => (string) ($q['option_a'] ?? $q['A'] ?? ''),
                'option_b' => (string) ($q['option_b'] ?? $q['B'] ?? ''),
                'option_c' => (string) ($q['option_c'] ?? $q['C'] ?? ''),
                'option_d' => (string) ($q['option_d'] ?? $q['D'] ?? ''),
                'correct_option' => $opt,
            ];
        }

        return $out;
    }
}
