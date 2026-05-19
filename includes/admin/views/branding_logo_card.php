<?php
/**
 * White Box Media Component — Website Branding & Logo Management
 * @var string $brandingReturnView overview|content
 */
$brandingReturnView = $brandingReturnView ?? 'overview';
$platform = new PlatformRepository();
$logoPath = $platform->logoPath();
$logoUrl = $logoPath ? admin_media_url($logoPath) : '';
$logoVer = 0;
if ($logoPath) {
    $abs = ACHARYA_ROOT . '/' . ltrim($logoPath, '/');
    $logoVer = is_file($abs) ? (int) filemtime($abs) : 0;
}
?>
<section class="ab-branding-box bg-white border border-[#E3E6F0] rounded-2xl shadow-sm mb-8 overflow-hidden" id="adminBrandingBox">
  <div class="px-6 py-4 border-b border-[#E3E6F0] bg-gradient-to-r from-slate-50 to-white flex flex-wrap items-center justify-between gap-3">
    <div>
      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Website Branding</p>
      <h2 class="font-telugu text-xl font-bold text-slate-900 mt-0.5">లోగో &amp; బ్రాండింగ్ నిర్వహణ</h2>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'home_banner'])) ?>" class="admin-btn admin-btn-secondary text-xs font-telugu whitespace-nowrap">
        హోమ్ బ్యానర్ బ్రాండింగ్ నిర్వహణ
      </a>
      <span class="text-xs text-slate-500 font-telugu">పబ్లిక్ సైట్ తక్షణ సింక్</span>
    </div>
  </div>

  <div class="p-6 sm:p-8">
    <form method="post" action="actions.php" enctype="multipart/form-data" class="flex flex-col lg:flex-row lg:items-center gap-6">
      <input type="hidden" name="action" value="save_site_logo" />
      <input type="hidden" name="return_view" value="<?= admin_e($brandingReturnView) ?>" />
      <?= admin_csrf_field() ?>

      <label class="relative shrink-0 mx-auto lg:mx-0 cursor-pointer group" title="కొత్త లోగో అప్‌లోడ్ చేయండి">
        <span class="block w-28 h-28 sm:w-32 sm:h-32 rounded-full border-[3px] border-dashed border-slate-300 bg-white shadow-inner flex items-center justify-center overflow-hidden transition-colors group-hover:border-blue-600 group-hover:bg-blue-50/30">
          <?php if ($logoUrl): ?>
          <img src="<?= admin_e($logoUrl) ?>?v=<?= (int) $logoVer ?>" alt="Site logo" class="w-full h-full object-contain p-1" id="adminLogoPreview" />
          <?php else: ?>
          <span class="flex flex-col items-center justify-center text-slate-400 group-hover:text-blue-700 transition-colors" id="adminLogoPlaceholder">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="text-2xl font-light leading-none mt-1">+</span>
          </span>
          <?php endif; ?>
        </span>
        <input type="file" name="site_logo" accept="<?= admin_e(ImageUploadService::acceptAttribute()) ?>" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" id="adminLogoFileInput" />
      </label>

      <div class="flex-1 min-w-0 text-center lg:text-left">
        <p class="font-telugu text-lg font-bold text-slate-900">కొత్త లోగో అప్‌లోడ్ చేయండి</p>
        <p class="font-telugu text-sm text-slate-600 mt-2 leading-relaxed">
          వృత్తాకార ఫ్రేమ్‌పై క్లిక్ చేసి JPG, PNG, GIF, SVG, WEBP ఎంచుకోండి.<br class="hidden sm:inline" />
          <span class="text-slate-500">(.PNG / .JPG అపర్ కేస్ సహా · గరిష్టం 10 MB)</span>
        </p>
        <div class="mt-5 flex flex-wrap items-center justify-center lg:justify-start gap-3">
          <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl shadow-md transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Upload New Logo
          </button>
          <?php if ($logoUrl): ?>
          <button type="button" onclick="document.getElementById('adminLogoFileInput').click()" class="px-5 py-3 text-sm font-semibold text-blue-800 bg-blue-50 border border-blue-200 rounded-xl hover:bg-blue-100 font-telugu">
            మార్చు (+)
          </button>
          <?php endif; ?>
        </div>
      </div>
    </form>

    <?php if ($logoUrl): ?>
    <form method="post" action="actions.php" class="mt-5 pt-5 border-t border-[#E3E6F0]">
      <input type="hidden" name="action" value="clear_site_logo" />
      <input type="hidden" name="return_view" value="<?= admin_e($brandingReturnView) ?>" />
      <?= admin_csrf_field() ?>
      <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 font-telugu">లోగో తొలగించు (Clear)</button>
    </form>
    <?php endif; ?>
  </div>
</section>
<script>
(function () {
  var inp = document.getElementById('adminLogoFileInput');
  if (!inp) return;
  inp.addEventListener('change', function () {
    if (!inp.files || !inp.files[0]) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      var ring = inp.closest('label');
      if (!ring) return;
      var img = ring.querySelector('#adminLogoPreview');
      if (!img) {
        var ph = ring.querySelector('#adminLogoPlaceholder');
        if (ph) ph.remove();
        img = document.createElement('img');
        img.id = 'adminLogoPreview';
        img.className = 'w-full h-full object-contain p-1';
        img.alt = 'Site logo preview';
        ring.querySelector('span').appendChild(img);
      }
      img.src = e.target.result;
    };
    reader.readAsDataURL(inp.files[0]);
  });
})();
</script>
