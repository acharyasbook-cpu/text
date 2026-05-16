<?php

/**
 * Back-compat entrypoint: historically AP SA Telugu only; now standardises every AP DSC SA track (`ap-sa-*`).
 *
 * Prerequisites: migrate_exam_hierarchy.php, migrate_four_tier.php, migrate_dynamic_hierarchy.php (recommended).
 *
 * CLI: php database/migrate_sa_telugu_structure.php
 *
 * @see database/standardize_all_sa_fields.php
 */

declare(strict_types=1);

require_once __DIR__ . '/standardize_all_sa_fields.php';

echo "migrate_sa_telugu_structure: forwarding to full AP DSC SA standardization (all specialisations)\n";

run_standardize_all_sa_fields(true);
