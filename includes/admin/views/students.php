<?php
/** @var AdminRepository $repo */
$students = $repo->allStudents();
$packages = $repo->allPackages();
$payments = $repo->allPayments();
$focusId = !empty($_GET['student_id']) ? (int) $_GET['student_id'] : null;
$focusSubs = $focusId ? $repo->studentSubscriptions($focusId) : [];
$activePackageIds = array_column(array_filter($focusSubs, fn($s) => $s['status'] === 'active'), 'package_id');
?>

<div class="grid lg:grid-cols-3 gap-6">
  <div class="lg:col-span-1 bg-white rounded-xl border border-slate-200 overflow-hidden max-h-[28rem] overflow-y-auto">
    <div class="px-5 py-4 border-b font-semibold text-slate-800 sticky top-0 bg-white">Students (<?= count($students) ?>)</div>
    <?php foreach ($students as $st): ?>
    <a href="?view=students&student_id=<?= (int)$st['id'] ?>"
       class="block px-5 py-3 border-b border-slate-50 hover:bg-blue-50/50 <?= $focusId === (int)$st['id'] ? 'bg-blue-50 border-l-4 border-l-brand' : '' ?>">
      <p class="font-medium text-sm text-slate-800"><?= admin_e($st['name']) ?></p>
      <p class="text-xs text-slate-500"><?= admin_e($st['email']) ?></p>
      <p class="text-xs text-brand mt-1"><?= (int)$st['active_subs'] ?> active package(s)</p>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="lg:col-span-2 space-y-6">
    <?php if ($focusId): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
      <h3 class="font-semibold text-slate-800 mb-4">Sub-course Access Control</h3>
      <p class="text-xs text-slate-500 mb-4">Enable or disable specific subject / division packages for this student.</p>
      <div class="space-y-2">
        <?php foreach ($packages as $pkg):
          $has = in_array((int)$pkg['id'], $activePackageIds, true);
        ?>
        <div class="flex items-center justify-between p-3 rounded-lg border border-slate-100">
          <div>
            <p class="text-sm font-medium"><?= admin_e($pkg['name']) ?></p>
            <p class="text-xs text-slate-500">₹<?= number_format((float)$pkg['price_inr'],0) ?> · <?= admin_e($pkg['package_type']) ?></p>
          </div>
          <form method="post" class="flex gap-2">
            <input type="hidden" name="action" value="toggle_subscription" />
            <input type="hidden" name="return_view" value="students" />
            <input type="hidden" name="user_id" value="<?= $focusId ?>" />
            <input type="hidden" name="package_id" value="<?= (int)$pkg['id'] ?>" />
            <?php if ($has): ?>
              <input type="hidden" name="enable" value="0" />
              <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-red-100 text-red-700 rounded-lg">Revoke</button>
            <?php else: ?>
              <input type="hidden" name="enable" value="1" />
              <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-emerald-600 text-white rounded-lg">Grant Access</button>
            <?php endif; ?>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
      <h3 class="font-semibold mb-4">Record Payment</h3>
      <form method="post" class="grid sm:grid-cols-2 gap-3">
        <input type="hidden" name="action" value="record_payment" />
        <input type="hidden" name="return_view" value="students" />
        <input type="hidden" name="user_id" value="<?= $focusId ?>" />
        <div><label class="text-xs font-medium">Package</label>
          <select name="package_id" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
            <option value="">— General —</option>
            <?php foreach ($packages as $p): ?><option value="<?= (int)$p['id'] ?>"><?= admin_e($p['name']) ?></option><?php endforeach; ?>
          </select></div>
        <div><label class="text-xs font-medium">Amount (INR)</label>
          <input type="number" step="0.01" name="amount_inr" required class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
        <div><label class="text-xs font-medium">Method</label>
          <input name="payment_method" value="manual" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
        <div><label class="text-xs font-medium">Transaction ref</label>
          <input name="transaction_ref" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
        <div class="sm:col-span-2"><button type="submit" class="px-5 py-2 bg-brand text-white text-sm font-semibold rounded-lg">Record Payment</button></div>
      </form>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500 text-sm">
      Select a student from the list to manage subscriptions and payments.
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <div class="px-5 py-4 border-b font-semibold">Payment History</div>
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
          <tr><th class="px-5 py-2">Student</th><th class="px-5 py-2">Amount</th><th class="px-5 py-2">Status</th><th class="px-5 py-2">Date</th></tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $pay): ?>
          <tr class="border-t border-slate-100">
            <td class="px-5 py-2"><?= admin_e($pay['student_name']) ?></td>
            <td class="px-5 py-2 font-medium">₹<?= number_format((float)$pay['amount_inr'],0) ?></td>
            <td class="px-5 py-2"><span class="text-xs px-2 py-0.5 rounded bg-slate-100"><?= admin_e($pay['status']) ?></span></td>
            <td class="px-5 py-2 text-slate-500"><?= admin_e(date('d M Y', strtotime($pay['paid_at']))) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
