<?php

declare(strict_types=1);

/** @return list<array{suite_key:string,label_te:string,label_en:string,test_type:string,sort_order:int}> */
function content_manager_exam_suite_templates(): array
{
    return [
        ['suite_key' => 'revision', 'label_te' => 'రివిజన్ టెస్ట్', 'label_en' => 'Revision Test', 'test_type' => 'revision', 'sort_order' => 0],
        ['suite_key' => 'division', 'label_te' => 'డివిజన్ టెస్ట్', 'label_en' => 'Division Test', 'test_type' => 'division', 'sort_order' => 1],
        ['suite_key' => 'sub_grand', 'label_te' => 'సబ్ గ్రాండ్ టెస్ట్', 'label_en' => 'Sub-Grand Test', 'test_type' => 'model', 'sort_order' => 2],
        ['suite_key' => 'grand', 'label_te' => 'గ్రాండ్ టెస్ట్', 'label_en' => 'Grand Test', 'test_type' => 'grand', 'sort_order' => 3],
    ];
}

/** @return array<string,string> */
function content_manager_suite_key_to_test_type(): array
{
    $map = [];
    foreach (content_manager_exam_suite_templates() as $t) {
        $map[$t['suite_key']] = $t['test_type'];
    }

    return $map;
}
