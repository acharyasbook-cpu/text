<?php

declare(strict_types=1);

/**
 * Canonical main-course / sub-course catalog — single source for sort_order and labels.
 * Future programmes (APPSC, TSPSC, EAMCET, NEET) extend {@see futureMainCourseSlots()}.
 */
final class CourseCatalogRegistry
{
    /** @return list<array{slug:string,name:string,name_te:string,sort_order:int}> */
    public static function mainCourses(): array
    {
        return [
            ['slug' => 'ap-dsc', 'name' => 'AP DSC', 'name_te' => 'ఏపీ డీఎస్సీ', 'sort_order' => 1],
            ['slug' => 'ts-dsc', 'name' => 'TS DSC', 'name_te' => 'టీఎస్ డీఎస్సీ', 'sort_order' => 2],
            ['slug' => 'ap-tet', 'name' => 'AP TET', 'name_te' => 'ఏపీ టెట్', 'sort_order' => 3],
            ['slug' => 'ts-tet', 'name' => 'TS TET', 'name_te' => 'తెలంగాణ టెట్', 'sort_order' => 4],
            ['slug' => 'ctet', 'name' => 'CTET', 'name_te' => 'సీటెట్', 'sort_order' => 5],
        ];
    }

    /** Reserved slots for later expansion (not seeded until enabled). */
    public static function futureMainCourseSlots(): array
    {
        return ['appsc', 'tspsc', 'eamcet', 'neet'];
    }

    /** @return list<string> */
    public static function mainCourseSlugs(): array
    {
        return array_column(self::mainCourses(), 'slug');
    }

    /**
     * Sub-courses for a main course in strict display order (sort_order = 1..n).
     *
     * @return list<array{slug:string,name:string,name_te:string,sort_order:int}>
     */
    public static function subCoursesFor(string $mainSlug): array
    {
        $rows = match ($mainSlug) {
            'ap-dsc' => self::apDscSubCourses(),
            'ts-dsc' => self::tsDscSubCourses(),
            'ap-tet' => self::apTetSubCourses(),
            'ts-tet' => self::tsTetSubCourses(),
            'ctet' => self::ctetSubCourses(),
            default => [],
        };
        $out = [];
        $n = 1;
        foreach ($rows as $row) {
            $out[] = [
                'slug' => $row['slug'],
                'name' => $row['name'],
                'name_te' => $row['name_te'],
                'sort_order' => $n++,
            ];
        }

        return $out;
    }

    /** @return list<string> */
    public static function subCourseSlugsFor(string $mainSlug): array
    {
        return array_column(self::subCoursesFor($mainSlug), 'slug');
    }

    /** @return list<string> */
    public static function allSubCourseSlugs(): array
    {
        $all = [];
        foreach (self::mainCourseSlugs() as $mc) {
            $all = array_merge($all, self::subCourseSlugsFor($mc));
        }

        return array_values(array_unique($all));
    }

    /** Whitelist used by hierarchy seed / purge (includes structural slugs for sync). */
    public static function hierarchyWhitelistFor(string $mainSlug): array
    {
        return self::subCourseSlugsFor($mainSlug);
    }

    /** @return list<array{slug:string,name:string,name_te:string}> */
    private static function apDscSubCourses(): array
    {
        return [
            ['slug' => 'sgt', 'name' => 'SGT', 'name_te' => 'ఎస్జీటీ'],
            ['slug' => 'ap-sa-telugu', 'name' => 'AP SA Telugu', 'name_te' => 'ఏపీ ఎస్సే తెలుగు'],
            ['slug' => 'ap-sa-english', 'name' => 'AP SA English', 'name_te' => 'ఏపీ ఎస్సే ఇంగ్లీష్'],
            ['slug' => 'ap-sa-hindi', 'name' => 'AP SA Hindi', 'name_te' => 'ఏపీ ఎస్ఏ హిందీ'],
            ['slug' => 'ap-sa-maths', 'name' => 'AP SA Mathematics', 'name_te' => 'ఏపీ ఎస్ఏ మ్యాథమెటిక్స్'],
            ['slug' => 'ap-sa-physical-science', 'name' => 'AP SA Physical Science', 'name_te' => 'ఏపీ ఎస్ ఏ ఫిజికల్ సైన్స్'],
            ['slug' => 'ap-sa-biological-science', 'name' => 'AP SA Biological Science', 'name_te' => 'ఏపీ ఎస్ ఏ బయోలాజికల్ సైన్స్'],
            ['slug' => 'ap-sa-social-studies', 'name' => 'AP SA Social Studies', 'name_te' => 'ఏపీ ఎస్ ఏ సోషల్'],
            ['slug' => 'ap-tgt-telugu', 'name' => 'AP DSC TGT Telugu', 'name_te' => 'ఏపీ TGT తెలుగు'],
            ['slug' => 'ap-tgt-english', 'name' => 'AP DSC TGT English', 'name_te' => 'ఏపీ TGT ఇంగ్లీష్'],
            ['slug' => 'ap-tgt-hindi', 'name' => 'AP DSC TGT Hindi', 'name_te' => 'ఏపీ TGT హిందీ'],
            ['slug' => 'ap-tgt-maths', 'name' => 'AP DSC TGT Mathematics', 'name_te' => 'ఏపీ TGT మ్యాథమెటిక్స్'],
            ['slug' => 'ap-tgt-physical-science', 'name' => 'AP DSC TGT Physical Science', 'name_te' => 'ఏపీ TGT ఫిజికల్ సైన్స్'],
            ['slug' => 'ap-tgt-biological-science', 'name' => 'AP DSC TGT Biological Science', 'name_te' => 'ఏపీ TGT బయోలాజికల్ సైన్స్'],
            ['slug' => 'ap-tgt-social-studies', 'name' => 'AP DSC TGT Social Studies', 'name_te' => 'ఏపీ TGT సోషల్'],
            ['slug' => 'ap-pgt-telugu', 'name' => 'AP DSC PGT Telugu', 'name_te' => 'ఏపీ PGT తెలుగు'],
            ['slug' => 'ap-pgt-english', 'name' => 'AP DSC PGT English', 'name_te' => 'ఏపీ PGT ఇంగ్లీష్'],
            ['slug' => 'ap-pgt-hindi', 'name' => 'AP DSC PGT Hindi', 'name_te' => 'ఏపీ PGT హిందీ'],
            ['slug' => 'ap-pgt-maths', 'name' => 'AP DSC PGT Mathematics', 'name_te' => 'ఏపీ PGT మ్యాథమెటిక్స్'],
            ['slug' => 'ap-pgt-physical-science', 'name' => 'AP DSC PGT Physical Science', 'name_te' => 'ఏపీ PGT ఫిజికల్ సైన్స్'],
            ['slug' => 'ap-pgt-biological-science', 'name' => 'AP DSC PGT Biological Science', 'name_te' => 'ఏపీ PGT బయోలాజికల్ సైన్స్'],
            ['slug' => 'ap-pgt-social-studies', 'name' => 'AP DSC PGT Social Studies', 'name_te' => 'ఏపీ PGT సోషల్'],
            ['slug' => 'ap-pgt-commerce', 'name' => 'AP DSC PGT Commerce', 'name_te' => 'ఏపీ PGT కామర్స్'],
            ['slug' => 'ap-pgt-zoology', 'name' => 'AP DSC PGT Zoology', 'name_te' => 'ఏపీ PGT జూవాలజీ'],
            ['slug' => 'ap-pgt-botany', 'name' => 'AP DSC PGT Botany', 'name_te' => 'ఏపీ PGT బోటనీ'],
        ];
    }

    /** @return list<array{slug:string,name:string,name_te:string}> */
    private static function tsDscSubCourses(): array
    {
        return [
            ['slug' => 'sgt', 'name' => 'SGT', 'name_te' => 'ఎస్జీటీ'],
            ['slug' => 'ts-sa-telugu', 'name' => 'TS DSC SA Telugu', 'name_te' => 'టీఎస్ ఎస్సే తెలుగు'],
            ['slug' => 'ts-sa-english', 'name' => 'TS DSC SA English', 'name_te' => 'టీఎస్ ఎస్సే ఇంగ్లీష్'],
            ['slug' => 'ts-sa-hindi', 'name' => 'TS DSC SA Hindi', 'name_te' => 'టీఎస్ SA హిందీ'],
            ['slug' => 'ts-sa-maths', 'name' => 'TS DSC SA Mathematics', 'name_te' => 'టీఎస్ SA మ్యాథమెటిక్స్'],
            ['slug' => 'ts-sa-physical-science', 'name' => 'TS DSC SA Physical Science', 'name_te' => 'టీఎస్ ఎస్ ఏ ఫిజికల్ సైన్స్'],
            ['slug' => 'ts-sa-biological-science', 'name' => 'TS DSC SA Biological Science', 'name_te' => 'టీఎస్ ఎస్ ఏ బయోలాజికల్ సైన్స్'],
            ['slug' => 'ts-sa-social-studies', 'name' => 'TS DSC SA Social Studies', 'name_te' => 'టీఎస్ ఎస్ ఏ సోషల్'],
        ];
    }

    /** @return list<array{slug:string,name:string,name_te:string}> */
    private static function apTetSubCourses(): array
    {
        return [
            ['slug' => 'ap-tet-paper-1', 'name' => 'AP TET Paper I', 'name_te' => 'పేపర్ వన్ ఏ'],
            ['slug' => 'ap-tet-paper-1-special', 'name' => 'AP TET Paper I Special', 'name_te' => 'పేపర్ వన్ బి స్పెషల్'],
            ['slug' => 'ap-tet-paper-2', 'name' => 'AP TET Paper II', 'name_te' => 'పేపర్ 2 ఏ'],
            ['slug' => 'ap-tet-p2-telugu', 'name' => 'AP TET Paper II — Telugu', 'name_te' => 'పేపర్ టు ఏ తెలుగు'],
            ['slug' => 'ap-tet-p2-hindi', 'name' => 'AP TET Paper II — Hindi', 'name_te' => 'పేపర్ టు ఏ హిందీ'],
            ['slug' => 'ap-tet-p2-english', 'name' => 'AP TET Paper II — English', 'name_te' => 'పేపర్ టు ఏ ఇంగ్లీష్'],
            ['slug' => 'ap-tet-p2-maths-science', 'name' => 'AP TET Paper II — Maths & Science', 'name_te' => 'పేపర్ టు ఏ మ్యాథమెటిక్స్ అండ్ సైన్స్'],
            ['slug' => 'ap-tet-p2-social', 'name' => 'AP TET Paper II — Social Studies', 'name_te' => 'పేపర్ టు ఏ సోషల్ స్టడీస్'],
        ];
    }

    /** @return list<array{slug:string,name:string,name_te:string}> */
    private static function tsTetSubCourses(): array
    {
        return [
            ['slug' => 'ts-tet-paper-1', 'name' => 'TS TET Paper I', 'name_te' => 'పేపర్ వన్ ఏ'],
            ['slug' => 'ts-tet-paper-1-special', 'name' => 'TS TET Paper I Special', 'name_te' => 'పేపర్ వన్ బి స్పెషల్'],
            ['slug' => 'ts-tet-p2-maths-science', 'name' => 'TS TET Paper II — Mathematics & Science', 'name_te' => 'పేపర్ 2a మ్యాథమెటిక్స్ అండ్ సైన్స్'],
            ['slug' => 'ts-tet-p2-social-studies', 'name' => 'TS TET Paper II — Social Studies', 'name_te' => 'పేపర్ టు ఏ సోషల్ స్టడీస్'],
        ];
    }

    /** @return list<array{slug:string,name:string,name_te:string}> */
    private static function ctetSubCourses(): array
    {
        return [
            ['slug' => 'ctet-paper-1', 'name' => 'CTET Paper I', 'name_te' => 'పేపర్ వన్'],
            ['slug' => 'ctet-paper-2', 'name' => 'CTET Paper II', 'name_te' => 'పేపర్ 2'],
            ['slug' => 'ctet-p2-maths-science', 'name' => 'CTET Paper II — Maths & Science', 'name_te' => 'పేపర్-2 మ్యాథమెటిక్స్ అండ్ సైన్స్'],
            ['slug' => 'ctet-p2-social-studies', 'name' => 'CTET Paper II — Social Studies', 'name_te' => 'పేపర్ టు సోషల్ స్టడీస్'],
        ];
    }

    /** Map registry sort_order onto programme rows from catalog classes. */
    public static function applySortToProgrammes(string $mainSlug, array $programmes): array
    {
        $order = array_flip(self::subCourseSlugsFor($mainSlug));
        foreach ($programmes as &$p) {
            $slug = (string) ($p['slug'] ?? '');
            if (isset($order[$slug])) {
                $p['sort'] = $order[$slug] + 1;
            }
        }
        unset($p);

        return $programmes;
    }
}
