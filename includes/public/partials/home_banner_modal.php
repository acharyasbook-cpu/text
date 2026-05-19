<?php if (!empty($header['is_admin_viewer'])):
    $bannerApi = base_url('admin/home_banner_api.php');
?>
<div id="homeBannerModal" class="home-banner-modal font-telugu" hidden>
  <div class="home-banner-modal__backdrop" data-banner-close></div>
  <div class="home-banner-modal__panel">
    <button type="button" class="absolute top-2 right-3 text-xl" data-banner-close>×</button>
    <h3 class="font-bold text-slate-900 mb-3">బ్యానర్ కస్టమైజ్</h3>
    <form id="homeBannerForm" class="space-y-3 text-sm">
      <label class="block">బ్యాక్‌గ్రౌండ్ రంగు / గ్రేడియంట్
        <input type="text" name="bg_color" id="hbBgColor" class="w-full border rounded px-2 py-1.5 mt-1 font-mono text-xs"
               placeholder="linear-gradient(...) లేదా #f5c842" />
      </label>
      <label class="block">బ్యాక్‌గ్రౌండ్ చిత్రం
        <input type="file" name="bg_image" accept="image/*" class="w-full mt-1 text-xs" />
      </label>
      <label class="block">లైన్ 1
        <input type="text" name="line1" id="hbLine1" class="w-full border rounded px-2 py-1.5 mt-1" />
      </label>
      <label class="block">లైన్ 2
        <input type="text" name="line2" id="hbLine2" class="w-full border rounded px-2 py-1.5 mt-1" />
      </label>
      <label class="block">లైన్ 3
        <input type="text" name="line3" id="hbLine3" class="w-full border rounded px-2 py-1.5 mt-1" />
      </label>
      <button type="submit" class="classical-btn-primary w-full py-2 mt-2">సేవ్</button>
    </form>
  </div>
</div>
<script>
(function () {
  var gear = document.getElementById('homeBannerGear');
  var modal = document.getElementById('homeBannerModal');
  var form = document.getElementById('homeBannerForm');
  if (!gear || !modal || !form) return;
  var API = <?= json_encode($bannerApi, JSON_THROW_ON_ERROR) ?>;
  var CSRF = <?= json_encode(csrf_token(), JSON_THROW_ON_ERROR) ?>;
  gear.addEventListener('click', function () {
    fetch(API + '?action=get', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
      if (!j.ok) return;
      var s = j.settings || {};
      document.getElementById('hbBgColor').value = s.bg_color || '';
      document.getElementById('hbLine1').value = s.line1 || '';
      document.getElementById('hbLine2').value = s.line2 || '';
      document.getElementById('hbLine3').value = s.line3 || '';
      modal.hidden = false;
    });
  });
  modal.querySelectorAll('[data-banner-close]').forEach(function (el) {
    el.addEventListener('click', function () { modal.hidden = true; });
  });
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(form);
    fd.append('action', 'save');
    fd.append('_csrf', CSRF);
    fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
      if (j.ok) location.reload();
      else alert(j.error || 'Error');
    });
  });
})();
</script>
<?php endif; ?>
