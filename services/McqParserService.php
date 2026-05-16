<?php

declare(strict_types=1);

/**
 * Lenient MCQ parser for plain text, CSV, XLSX, DOCX uploads.
 *
 * @phpstan-type McqRow array{
 *   question_text:string,
 *   option_a:string,
 *   option_b:string,
 *   option_c:string,
 *   option_d:string,
 *   correct_option:string
 * }
 */
final class McqParserService
{
    /** @return list<McqRow> */
    public function parseUploadedFile(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('File upload failed.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('Invalid upload.');
        }
        $name = strtolower((string) ($file['name'] ?? ''));
        $ext = pathinfo($name, PATHINFO_EXTENSION);

        return match ($ext) {
            'csv', 'txt' => $this->parseCsvFile($tmp),
            'xlsx' => $this->parseXlsxFile($tmp),
            'docx' => $this->parseDocxFile($tmp),
            'doc' => $this->parseFromText((string) file_get_contents($tmp)),
            default => throw new InvalidArgumentException('Supported formats: .csv, .txt, .xlsx, .docx'),
        };
    }

    /** @return list<McqRow> */
    public function parseFromText(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        if ($text === '') {
            return [];
        }

        if (str_contains($text, ',') && preg_match('/^question[,;]/mi', $text)) {
            $rows = $this->parseCsvString($text);
            if ($rows !== []) {
                return $rows;
            }
        }

        $blocks = preg_split('/\n\s*(?=\d+[\.\)]\s+)/u', $text) ?: [];
        if (count($blocks) <= 1 && !preg_match('/^\d+[\.\)]/mu', $text)) {
            $blocks = preg_split('/\n{2,}/', $text) ?: [$text];
        }

        $out = [];
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }
            $row = $this->parseQuestionBlock($block);
            if ($row !== null) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /** @return list<McqRow> */
    public function parseCsvFile(string $path): array
    {
        $raw = (string) file_get_contents($path);

        return $this->parseCsvString($raw);
    }

    /** @return list<McqRow> */
    public function parseCsvString(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", trim($raw));
        if ($raw === '') {
            return [];
        }
        $lines = explode("\n", $raw);
        $delimiter = str_contains($lines[0] ?? '', ';') ? ';' : ',';
        $rows = [];
        $header = null;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $cols = str_getcsv($line, $delimiter);
            if ($header === null) {
                $header = $this->normalizeHeader($cols);
                if ($this->rowLooksLikeQuestion($cols, $header)) {
                    $parsed = $this->mapCsvRow($cols, $header);
                    if ($parsed !== null) {
                        $rows[] = $parsed;
                    }
                    $header = null;
                }
                continue;
            }
            $parsed = $this->mapCsvRow($cols, $header);
            if ($parsed !== null) {
                $rows[] = $parsed;
            }
        }

        return $rows;
    }

    /** @return list<McqRow> */
    public function parseXlsxFile(string $path): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive required for .xlsx parsing.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('Could not open XLSX file.');
        }
        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $shared = $this->xlsxSharedStrings($sharedXml);
        }
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheetXml === false) {
            throw new InvalidArgumentException('XLSX sheet1 not found.');
        }
        $grid = $this->xlsxSheetRows($sheetXml, $shared);
        if ($grid === []) {
            return [];
        }
        $header = $this->normalizeHeader($grid[0]);
        $out = [];
        $start = 1;
        if (!$this->headerMapsQuestions($header)) {
            $start = 0;
            $header = ['question', 'a', 'b', 'c', 'd', 'answer'];
        }
        for ($i = $start, $n = count($grid); $i < $n; ++$i) {
            $parsed = $this->mapCsvRow($grid[$i], $header);
            if ($parsed !== null) {
                $out[] = $parsed;
            }
        }

        return $out;
    }

    /** @return list<McqRow> */
    public function parseDocxFile(string $path): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive required for .docx parsing.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('Could not open DOCX file.');
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) {
            throw new InvalidArgumentException('DOCX document body missing.');
        }
        $text = preg_replace('/<w:tab[^>]*\/>/', "\t", $xml) ?? $xml;
        $text = preg_replace('/<\/w:p>/', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $this->parseFromText($text);
    }

    /** @return McqRow|null */
    private function parseQuestionBlock(string $block): ?array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $block)), static fn ($l) => $l !== ''));
        if ($lines === []) {
            return null;
        }
        $question = preg_replace('/^\d+[\.\)]\s*/u', '', $lines[0]) ?? $lines[0];
        $options = ['A' => '', 'B' => '', 'C' => '', 'D' => ''];
        $answer = '';

        for ($i = 1, $n = count($lines); $i < $n; ++$i) {
            $line = $lines[$i];
            if (preg_match('/^(?:సమాధానం|జవాబు|answer|correct)\s*[:：\-]?\s*([A-Da-d])/ui', $line, $m)) {
                $answer = strtoupper($m[1]);

                continue;
            }
            if (preg_match('/^([A-D])[)\.\:]\s*(.+)$/u', $line, $m)) {
                $options[strtoupper($m[1])] = trim($m[2]);

                continue;
            }
            if ($answer === '' && preg_match('/^([A-D])\s*[\)\.\:]/u', $line, $m) === 0) {
                $question .= ' ' . $line;
            }
        }

        if ($answer === '') {
            foreach ($lines as $line) {
                if (preg_match('/(?:సమాధానం|answer)\s*[:：]?\s*([A-D])/ui', $line, $m)) {
                    $answer = strtoupper($m[1]);
                    break;
                }
            }
        }

        if ($question === '' || $answer === '' || !isset($options[$answer])) {
            return null;
        }
        foreach (['A', 'B', 'C', 'D'] as $k) {
            if ($options[$k] === '') {
                return null;
            }
        }

        return [
            'question_text' => $question,
            'option_a' => $options['A'],
            'option_b' => $options['B'],
            'option_c' => $options['C'],
            'option_d' => $options['D'],
            'correct_option' => $answer,
        ];
    }

    /** @param list<string> $cols */
  private function normalizeHeader(array $cols): array
    {
        return array_map(static function ($c) {
            $c = strtolower(trim((string) $c));
            $c = preg_replace('/[^a-z0-9_]/', '_', $c) ?? $c;

            return $c;
        }, $cols);
    }

    /** @param list<string> $header */
    private function headerMapsQuestions(array $header): bool
    {
        $joined = implode('|', $header);

        return str_contains($joined, 'question') || str_contains($joined, 'option');
    }

    /** @param list<string> $cols @param list<string> $header */
    private function rowLooksLikeQuestion(array $cols, array $header): bool
    {
        return !$this->headerMapsQuestions($header) && count($cols) >= 6;
    }

    /** @param list<string> $cols @param list<string> $header */
    private function mapCsvRow(array $cols, array $header): ?array
    {
        $map = [];
        foreach ($header as $i => $key) {
            $map[$key] = trim((string) ($cols[$i] ?? ''));
        }
        $q = $map['question'] ?? $map['question_text'] ?? $map['q'] ?? ($cols[0] ?? '');
        $a = $map['option_a'] ?? $map['a'] ?? ($cols[1] ?? '');
        $b = $map['option_b'] ?? $map['b'] ?? ($cols[2] ?? '');
        $c = $map['option_c'] ?? $map['c'] ?? ($cols[3] ?? '');
        $d = $map['option_d'] ?? $map['d'] ?? ($cols[4] ?? '');
        $ans = $map['answer'] ?? $map['correct'] ?? $map['correct_option'] ?? ($cols[5] ?? '');
        $ans = strtoupper(preg_replace('/[^A-D]/', '', strtoupper((string) $ans)) ?? '');
        if ($q === '' || strlen($ans) !== 1) {
            return null;
        }

        return [
            'question_text' => $q,
            'option_a' => $a,
            'option_b' => $b,
            'option_c' => $c,
            'option_d' => $d,
            'correct_option' => $ans,
        ];
    }

    /** @return list<string> */
    private function xlsxSharedStrings(string $xml): array
    {
        $out = [];
        if (preg_match_all('/<si>(.*?)<\/si>/s', $xml, $matches)) {
            foreach ($matches[1] as $chunk) {
                $text = strip_tags($chunk);
                $out[] = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return $out;
    }

    /**
     * @param list<string> $shared
     * @return list<list<string>>
     */
    private function xlsxSheetRows(string $xml, array $shared): array
    {
        $rows = [];
        if (!preg_match_all('/<row[^>]*>(.*?)<\/row>/s', $xml, $rowMatches)) {
            return [];
        }
        foreach ($rowMatches[1] as $rowXml) {
            $cells = [];
            if (preg_match_all('/<c([^>]*)>(?:<v>(.*?)<\/v>)?/s', $rowXml, $cellMatches, PREG_SET_ORDER)) {
                foreach ($cellMatches as $cm) {
                    $ref = $cm[1];
                    $val = $cm[2] ?? '';
                    $text = $val;
                    if (str_contains($ref, 't="s"') && $val !== '' && isset($shared[(int) $val])) {
                        $text = $shared[(int) $val];
                    }
                    $col = preg_replace('/\d+/', '', $cm[0]);
                    $col = is_string($col) ? $col : '';
                    $cells[] = $text;
                }
            }
            if ($cells !== []) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }
}
