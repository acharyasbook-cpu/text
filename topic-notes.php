<?php

declare(strict_types=1);

/** Legacy URL → secure note viewer. */
require __DIR__ . '/includes/init.php';

$qs = $_GET;
unset($qs['view']);
header('Location: ' . base_url('note_viewer.php?' . http_build_query($qs)));
exit;
