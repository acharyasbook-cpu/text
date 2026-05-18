<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__));
require ACHARYA_ROOT . '/includes/admin/bootstrap.php';

require_admin();

$pageTitle = 'Coupon Manager | Admin';
$activeView = 'coupons';
$navProgramme = null;

$pricingApi = admin_url('pricing_api.php');
$adminPageTitle = 'కూపన్ మేనేజర్';
$adminPageSubtitle = 'చెకౌట్ కోడ్‌లు — శాతం లేదా స్థిర తగ్గింపు · కోర్స్ పరిధి · గడువు';
$adminPageBackUrl = admin_dashboard_url(['view' => 'pricing']);
$adminPageBackLabel = '← ప్రైసింగ్';

require ACHARYA_ROOT . '/includes/admin/layout_start.php';
require __DIR__ . '/../includes/admin/partials/page_header.php';
?>
<div class="max-w-[96rem] mx-auto pb-10">
  <?php require ACHARYA_ROOT . '/includes/admin/partials/coupon_admin_panel.php'; ?>
</div>
<?php
require ACHARYA_ROOT . '/includes/admin/layout_end.php';
