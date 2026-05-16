<?php

/**
 * CLI alias: AP DSC SA + TGT + PGT standardisation (same as standardize_all_sa_fields.php).
 *
 * php database/standardize_all_sa_tgt_pgt.php
 */

declare(strict_types=1);

require_once __DIR__ . '/standardize_all_sa_fields.php';

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    run_standardize_all_sa_fields(true);
}
