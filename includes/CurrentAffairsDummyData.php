<?php

declare(strict_types=1);

/** Placeholder CA questions & archive months for demo / empty pool. */
final class CurrentAffairsDummyData
{
    public const EXAM_DURATION_SEC = 25 * 60;

    /**
     * @return list<array{id:string,question_text:string,option_a:string,option_b:string,option_c:string,option_d:string,correct_option:string}>
     */
    public static function questions(): array
    {
        $topics = [
            'రాష్ట్రపతి ఎన్నికలు', 'RBI ద్రవ్య విధానం', 'ISRO శాట్‌లైట్', 'క్రీడా విజయం',
            'అంతర్జాతీయ ఉచ్చవేడిక', 'రాష్ట్ర పథకం', 'సుప్రీంకోర్టు తీర్పు', 'ఆర్థిక సర్వే',
            'రక్షణ ఒప్పందం', 'వాతావరణ లక్ష్యం', 'డిజిటల్ ఇండియా', 'ఆరోగ్య సంస్కరణ',
            'విద్యా విధానం', 'వ్యవసాయ MSP', 'మెట్రో ప్రాజెక్ట్', 'స్టార్టప్ ఇన్వెస్ట్',
            'పురస్కారం', 'ద్వైపక్షిక ఒప్పందం', 'ఎన్నికలు', 'జాతీయ క్రీడా',
            'సాంకేతిక ఆవిష్కరణ', 'సామాజిక న్యాయం', 'పర్యావరణ లక్ష్యం', 'బ్యాంకింగ్ నిబంధన',
            'స్థానిక ఎన్నికలు',
        ];
        $letters = ['A', 'B', 'C', 'D'];
        $out = [];
        foreach ($topics as $i => $topic) {
            $n = $i + 1;
            $correct = $letters[$i % 4];
            $out[] = [
                'id' => 'dummy_' . $n,
                'question_text' => "Q{$n}. డెమో కరెంట్ అఫైర్స్ — {$topic}పై ఈ కాలంలో ముఖ్యమైన అంశం ఏది?",
                'option_a' => 'ప్రధానమంత్రి కార్యక్రమం',
                'option_b' => 'రాష్ట్రపతి ఉత్తర్వు',
                'option_c' => 'సుప్రీంకోర్టు తీర్పు',
                'option_d' => 'అంతర్జాతీయ ఒప్పందం',
                'correct_option' => $correct,
            ];
        }

        return $out;
    }

    /**
     * @return list<array{ym:string,label:string,count:int}>
     */
    public static function demoArchiveMonths(int $count = 12): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $ts = strtotime("-{$i} months");
            $ym = date('Y-m', $ts);
            $label = date('F Y', $ts);
            $teluguMonths = [
                'January' => 'జనవరి', 'February' => 'ఫిబ్రవరి', 'March' => 'మార్చి',
                'April' => 'ఏప్రిల్', 'May' => 'మే', 'June' => 'జూన్',
                'July' => 'జులై', 'August' => 'ఆగస్టు', 'September' => 'సెప్టెంబర్',
                'October' => 'అక్టోబర్', 'November' => 'నవంబర్', 'December' => 'డిసెంబర్',
            ];
            $en = date('F', $ts);
            $te = $teluguMonths[$en] ?? $en;
            $out[] = [
                'ym' => $ym,
                'label' => $te . ' ' . date('Y', $ts) . ' Current Affairs',
                'count' => (int) date('t', $ts),
            ];
        }

        return $out;
    }

    /**
     * @return list<string> Y-m-d dates for a month (demo: every day has test)
     */
    public static function demoDatesInMonth(string $ym): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
            return [];
        }
        [$y, $m] = array_map('intval', explode('-', $ym));
        $days = (int) date('t', mktime(0, 0, 0, $m, 1, $y));
        $today = date('Y-m-d');
        $dates = [];
        for ($d = $days; $d >= 1; $d--) {
            $date = sprintf('%04d-%02d-%02d', $y, $m, $d);
            if ($date <= $today) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    public static function isDummyId(string $id): bool
    {
        return str_starts_with($id, 'dummy_');
    }

    public static function teluguMonthYear(string $ym): string
    {
        if (!preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) {
            return $ym;
        }
        $teluguMonths = [
            1 => 'జనవరి', 2 => 'ఫిబ్రవరి', 3 => 'మార్చి', 4 => 'ఏప్రిల్',
            5 => 'మే', 6 => 'జూన్', 7 => 'జులై', 8 => 'ఆగస్టు',
            9 => 'సెప్టెంబర్', 10 => 'అక్టోబర్', 11 => 'నవంబర్', 12 => 'డిసెంబర్',
        ];
        $month = (int) $m[2];
        $te = $teluguMonths[$month] ?? date('F', mktime(0, 0, 0, $month, 1));

        return $te . ' ' . $m[1];
    }
}
