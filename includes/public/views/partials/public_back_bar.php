<?php
/**
 * @var string $backHref
 * @var string $backLabel
 */
$backLabel = $backLabel ?? '← వెనుకకు / Back';
?>
<a href="<?= e($backHref) ?>" class="public-back-bar font-telugu inline-flex items-center gap-2 mb-6 px-4 py-2.5 rounded-lg border-2 border-royal bg-white text-royal font-bold text-sm shadow-sm hover:bg-royal hover:text-white transition-colors">
  <?= e($backLabel) ?>
</a>
