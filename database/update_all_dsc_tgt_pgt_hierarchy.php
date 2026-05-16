<?php

/**
 * AP DSC — full hierarchy refresh alias (SA + TGT + PGT + exams). Same as standardize_all_sa_fields.php.
 *
 * CLI: php database/update_all_dsc_tgt_pgt_hierarchy.php
 */

declare(strict_types=1);

require_once __DIR__ . '/standardize_all_sa_fields.php';

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    fwrite(STDOUT, "update_all_dsc_tgt_pgt_hierarchy: AP DSC structured programmes\n");
    run_standardize_all_sa_fields(true);
}
