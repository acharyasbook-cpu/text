<?php
require_once dirname(__DIR__, 2) . '/HomeBannerSettings.php';
$adminPageTitle = 'హోమ్ బ్యానర్ బ్రాండింగ్ నిర్వహణ';
$adminPageSubtitle = 'నీలం హీరో బ్యానర్ + గోల్డ్ కరెంట్ అఫైర్స్ బ్లాక్ · తక్షణ సింక్';
$apiUrl = admin_url('home_banner_api.php');
$csrf = admin_csrf_token();
$b = HomeBannerSettings::all();
$hero = $b['hero'] ?? [];
$ca = $b['ca'] ?? [];
require __DIR__ . '/../partials/page_header.php';
?>

<div id="homeBannerAdmin" class="space-y-6 font-telugu" data-api="<?= admin_e($apiUrl) ?>" data-csrf="<?= admin_e($csrf) ?>">
  <div class="admin-card p-5">
    <h2 class="text-sm font-bold text-slate-900">నీలం హోమ్ హీరో బ్యానర్</h2>
    <form id="heroBannerForm" class="grid md:grid-cols-2 gap-4 mt-4 text-sm" enctype="multipart/form-data">
      <label class="block md:col-span-2">బ్యాక్‌గ్రౌండ్ (రంగు / గ్రేడియంట్)
        <input type="text" name="bg_color" class="admin-input w-full mt-1 font-mono text-xs" value="<?= admin_e((string) ($hero['bg_color'] ?? '')) ?>" />
      </label>
      <label class="block md:col-span-2">బ్యాక్‌గ్రౌండ్ చిత్రం
        <input type="file" name="bg_image" accept="image/*" class="admin-input w-full mt-1" />
      </label>
      <label class="block">ఎయిబ్రో
        <input type="text" name="eyebrow" class="admin-input w-full mt-1" value="<?= admin_e((string) ($hero['eyebrow'] ?? '')) ?>" />
      </label>
      <label class="block">లైన్ 1 ఫాంట్ సైజ్
        <input type="text" name="line1_size" class="admin-input w-full mt-1" value="<?= admin_e((string) ($hero['line1_size'] ?? '1.875rem')) ?>" />
      </label>
      <label class="block md:col-span-2">లైన్ 1 (ప్రధాన శీర్షిక)
        <textarea name="line1" rows="2" class="admin-input w-full mt-1"><?= admin_e((string) ($hero['line1'] ?? '')) ?></textarea>
      </label>
      <label class="block">లైన్ 2 ఫాంట్ సైజ్
        <input type="text" name="line2_size" class="admin-input w-full mt-1" value="<?= admin_e((string) ($hero['line2_size'] ?? '0.875rem')) ?>" />
      </label>
      <label class="block md:col-span-2">లైన్ 2 (ఉప శీర్షిక)
        <textarea name="line2" rows="2" class="admin-input w-full mt-1"><?= admin_e((string) ($hero['line2'] ?? '')) ?></textarea>
      </label>
      <input type="hidden" name="section" value="hero" />
      <div class="md:col-span-2">
        <button type="submit" class="admin-btn admin-btn-primary">హీరో బ్యానర్ సేవ్ → లైవ్ సింక్</button>
      </div>
    </form>
  </div>

  <div class="admin-card p-5">
    <h2 class="text-sm font-bold text-slate-900">గోల్డ్ కరెంట్ అఫైర్స్ బ్లాక్</h2>
    <form id="caBannerForm" class="grid md:grid-cols-2 gap-4 mt-4 text-sm" enctype="multipart/form-data">
      <label class="block md:col-span-2">బ్యాక్‌గ్రౌండ్
        <input type="text" name="bg_color" class="admin-input w-full mt-1 font-mono text-xs" value="<?= admin_e((string) ($ca['bg_color'] ?? '')) ?>" />
      </label>
      <label class="block md:col-span-2">బ్యాక్‌గ్రౌండ్ చిత్రం
        <input type="file" name="bg_image" accept="image/*" class="admin-input w-full mt-1" />
      </label>
      <?php foreach ([1 => 'డైలీ టెస్ట్', 2 => 'డైలీ కరెంట్ అఫైర్స్', 3 => 'నేటి పరీక్ష'] as $n => $label): ?>
      <label class="block">లైన్ <?= $n ?> (<?= admin_e($label) ?>)
        <input type="text" name="line<?= $n ?>" class="admin-input w-full mt-1" value="<?= admin_e((string) ($ca['line' . $n] ?? '')) ?>" />
      </label>
      <label class="block">లైన్ <?= $n ?> ఫాంట్
        <input type="text" name="line<?= $n ?>_size" class="admin-input w-full mt-1" value="<?= admin_e((string) ($ca['line' . $n . '_size'] ?? '')) ?>" />
      </label>
      <?php endforeach; ?>
      <input type="hidden" name="section" value="ca" />
      <div class="md:col-span-2">
        <button type="submit" class="admin-btn admin-btn-primary">గోల్డ్ బ్లాక్ సేవ్ → లైవ్ సింక్</button>
      </div>
    </form>
  </div>

  <p class="text-xs text-slate-500">సేవ్ చేసిన వెంటనే <a href="<?= admin_e(base_url('index.php')) ?>" target="_blank" class="text-royal font-semibold">పబ్లిక్ హోమ్</a> అప్‌డేట్ అవుతుంది — రీబూట్ అవసరం లేదు.</p>
</div>

<script>
(function () {
  var API = document.getElementById('homeBannerAdmin').getAttribute('data-api');
  var CSRF = document.getElementById('homeBannerAdmin').getAttribute('data-csrf');
  function bindForm(id) {
    var form = document.getElementById(id);
    if (!form) return;
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var fd = new FormData(form);
      fd.append('action', 'save');
      fd.append('_csrf', CSRF);
      fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          alert(j.ok ? 'సేవ్ అయింది — హోమ్‌పేజ్ సింక్ అయింది' : (j.error || 'Error'));
          if (j.ok) window.open('<?= admin_e(base_url('index.php')) ?>', '_blank');
        });
    });
  }
  bindForm('heroBannerForm');
  bindForm('caBannerForm');
})();
</script>
