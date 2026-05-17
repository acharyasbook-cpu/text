<?php
/** @var AdminRepository $repo */
$students = $repo->allStudents();
$packages = $repo->allPackages();
$payments = $repo->allPayments();
$focusId = !empty($_GET['student_id']) ? (int) $_GET['student_id'] : null;
$focusStudent = null;
foreach ($students as $st) {
    if ((int) $st['id'] === $focusId) {
        $focusStudent = $st;
        break;
    }
}
$focusSubs = $focusId ? $repo->studentSubscriptions($focusId) : [];
$activePackageIds = array_column(array_filter($focusSubs, fn ($s) => $s['status'] === 'active'), 'package_id');
$adminPageTitle = 'Students & Subscriptions';
$adminPageSubtitle = 'సబ్‌స్క్రిప్షన్ నియంత్రణ · చెల్లింపులు · WhatsApp సంప్రదింపు';
require __DIR__ . '/../partials/page_header.php';
?>

<div class="grid lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
  <div class="lg:col-span-1 admin-card overflow-hidden max-h-[28rem] overflow-y-auto">
    <div class="admin-card-header sticky top-0 z-10">
      <p class="font-semibold text-slate-800">Students (<?= count($students) ?>)</p>
    </div>
    <?php foreach ($students as $st):
        $wa = admin_whatsapp_chat_url((string) ($st['phone'] ?? ''), (string) ($st['name'] ?? ''), 'welcome');
    ?>
    <a href="?view=students&student_id=<?= (int) $st['id'] ?>"
       class="block px-5 py-3 border-b border-slate-50 hover:bg-indigo-50/40 <?= $focusId === (int) $st['id'] ? 'bg-indigo-50 border-l-4 border-l-indigo-600' : '' ?>">
      <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
          <p class="font-medium text-sm text-slate-800"><?= admin_e($st['name']) ?></p>
          <p class="text-xs text-slate-500 truncate"><?= admin_e($st['email']) ?></p>
          <p class="text-xs text-indigo-600 mt-1"><?= (int) $st['active_subs'] ?> active package(s)</p>
        </div>
        <?php if ($wa !== ''): ?>
        <span class="admin-wa-link shrink-0 text-[10px] px-1.5" onclick="event.preventDefault();event.stopPropagation();window.open('<?= admin_e($wa) ?>','_blank')">💬</span>
        <?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="lg:col-span-2 space-y-6">
    <?php if ($focusId && $focusStudent): ?>
    <div class="admin-card p-5">
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
          <h3 class="font-semibold text-slate-800"><?= admin_e($focusStudent['name']) ?></h3>
          <p class="text-xs text-slate-500"><?= admin_e($focusStudent['email']) ?> · <?= admin_e($focusStudent['phone'] ?? '—') ?></p>
        </div>
        <?php
        $waW = admin_whatsapp_chat_url((string) ($focusStudent['phone'] ?? ''), (string) ($focusStudent['name'] ?? ''), 'welcome');
        $waR = admin_whatsapp_chat_url((string) ($focusStudent['phone'] ?? ''), (string) ($focusStudent['name'] ?? ''), 'reminder');
        ?>
        <div class="flex flex-wrap gap-2">
          <?php if ($waW !== ''): ?>
          <a href="<?= admin_e($waW) ?>" target="_blank" rel="noopener" class="admin-wa-link font-telugu">💬 స్వాగత సందేశం</a>
          <?php endif; ?>
          <?php if ($waR !== ''): ?>
          <a href="<?= admin_e($waR) ?>" target="_blank" rel="noopener" class="admin-wa-link font-telugu">📋 రిమైండర్</a>
          <?php endif; ?>
        </div>
      </div>
      <h4 class="text-sm font-semibold text-slate-800 mb-3">Sub-course Access Control</h4>
      <div class="space-y-2">
        <?php foreach ($packages as $pkg):
          $has = in_array((int) $pkg['id'], $activePackageIds, true);
        ?>
        <div class="flex items-center justify-between p-3 rounded-xl border border-slate-100">
          <div>
            <p class="text-sm font-medium"><?= admin_e($pkg['name']) ?></p>
            <p class="text-xs text-slate-500">₹<?= number_format((float) $pkg['price_inr'], 0) ?> · <?= admin_e($pkg['package_type']) ?></p>
          </div>
          <form method="post" class="flex gap-2">
            <input type="hidden" name="action" value="toggle_subscription" />
            <input type="hidden" name="return_view" value="students" />
            <input type="hidden" name="user_id" value="<?= $focusId ?>" />
            <input type="hidden" name="package_id" value="<?= (int) $pkg['id'] ?>" />
            <?php if ($has): ?>
              <input type="hidden" name="enable" value="0" />
              <button type="submit" class="admin-btn text-xs bg-red-50 text-red-700 border-red-200">Revoke</button>
            <?php else: ?>
              <input type="hidden" name="enable" value="1" />
              <button type="submit" class="admin-btn admin-btn-primary text-xs">Grant</button>
            <?php endif; ?>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="admin-card p-5">
      <h3 class="font-semibold mb-4">Record Payment</h3>
      <form method="post" class="grid sm:grid-cols-2 gap-3">
        <input type="hidden" name="action" value="record_payment" />
        <input type="hidden" name="return_view" value="students" />
        <input type="hidden" name="user_id" value="<?= $focusId ?>" />
        <div><label class="admin-label block mb-1">Package</label>
          <select name="package_id" class="admin-select">
            <option value="">— General —</option>
            <?php foreach ($packages as $p): ?><option value="<?= (int) $p['id'] ?>"><?= admin_e($p['name']) ?></option><?php endforeach; ?>
          </select></div>
        <div><label class="admin-label block mb-1">Amount (INR)</label>
          <input type="number" step="0.01" name="amount_inr" required class="admin-input" /></div>
        <div><label class="admin-label block mb-1">Method</label>
          <input name="payment_method" value="manual" class="admin-input" /></div>
        <div><label class="admin-label block mb-1">Transaction ref</label>
          <input name="transaction_ref" class="admin-input" /></div>
        <div class="sm:col-span-2"><button type="submit" class="admin-btn admin-btn-primary">Record Payment</button></div>
      </form>
    </div>
    <?php else: ?>
    <div class="admin-card p-8 text-center text-slate-500 text-sm font-telugu">
      విద్యార్థిని ఎంచుకోండి — సబ్‌స్క్రిప్షన్, చెల్లింపు &amp; WhatsApp లింక్‌లు ఇక్కడ కనిపిస్తాయి.
    </div>
    <?php endif; ?>

    <div class="admin-card overflow-hidden">
      <div class="admin-card-header font-semibold">Payment History</div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>Student</th><th>Amount</th><th>Status</th><th>Date</th></tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $pay): ?>
            <tr>
              <td><?= admin_e($pay['student_name']) ?></td>
              <td class="font-medium">₹<?= number_format((float) $pay['amount_inr'], 0) ?></td>
              <td><span class="admin-badge admin-badge-slate"><?= admin_e($pay['status']) ?></span></td>
              <td class="text-slate-500"><?= admin_e(date('d M Y', strtotime($pay['paid_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
