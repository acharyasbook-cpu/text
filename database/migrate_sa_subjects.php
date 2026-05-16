<?php

/**
 * Pre-seed the AP DSC — School Assistant (`ap-sa-*`) 5-tier exam skeleton for every paper subject.
 * Delegates structure + pivots to ApSaCatalog, then seeds SaExamPattern rows (idempotent).
 *
 * Prerequisites: migrate_exam_hierarchy.php (tests.topic_id, test_bundle_items).
 *
 * CLI: php database/migrate_sa_subjects.php
 */

declare(strict_types=1);

require_once __DIR__ . '/standardize_all_sa_fields.php';

echo "migrate_sa_subjects: AP DSC SA — all ap-sa-* programmes\n";

run_standardize_all_sa_fields(true);
