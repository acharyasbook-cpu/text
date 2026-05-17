<?php
/** @var string $adminPageTitle */
/** @var string|null $adminPageSubtitle */
/** @var string|null $adminPageBackUrl */
/** @var string|null $adminPageBackLabel */
?>
<header class="admin-page-header flex flex-wrap items-start justify-between gap-4">
  <div class="min-w-0">
    <h1 class="font-telugu"><?= admin_e($adminPageTitle) ?></h1>
    <?php if (!empty($adminPageSubtitle)): ?>
    <p class="font-telugu"><?= admin_e($adminPageSubtitle) ?></p>
    <?php endif; ?>
  </div>
  <?php if (!empty($adminPageBackUrl)): ?>
  <a href="<?= admin_e($adminPageBackUrl) ?>" class="admin-btn admin-btn-secondary text-sm shrink-0">
    <?= admin_e($adminPageBackLabel ?? '← Back') ?>
  </a>
  <?php endif; ?>
</header>
