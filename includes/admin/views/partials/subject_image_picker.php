<?php
/**
 * Optional subject cover — click placeholder to reveal uploader.
 *
 * @var int $subjectId 0 when creating new subject
 * @var array<string,mixed>|null $subjectForm
 */
require_once dirname(__DIR__, 3) . '/MediaAvatarHelper.php';

$subjectId = (int) ($subjectId ?? 0);
$subjectForm = $subjectForm ?? null;
$hasImageCol = SchemaHelper::imagePathEnabled('subjects');
if (!$hasImageCol) {
    return;
}

$label = $subjectForm
    ? MediaAvatarHelper::displayLabel($subjectForm)
    : 'విషయం';
$imagePath = trim((string) ($subjectForm['image_path'] ?? ''));
$imgUrl = MediaAvatarHelper::resolvedUrl($imagePath !== '' ? $imagePath : null);
$initials = MediaAvatarHelper::initials($label);
$palette = MediaAvatarHelper::palette($label);
$uploadUrl = admin_url('content_api.php');
$canUpload = $subjectId > 0;
?>
<div class="subject-image-picker" id="subjectImagePicker"
     data-subject-id="<?= $subjectId ?>"
     data-upload-url="<?= admin_e($uploadUrl) ?>"
     data-csrf="<?= admin_e(admin_csrf_token()) ?>">
  <label class="text-xs font-medium text-slate-600 block mb-1.5">విషయ చిత్రం <span class="text-slate-400 font-normal">(ఐచ్ఛికం)</span></label>

  <?php if (!$canUpload): ?>
  <p class="text-[11px] text-slate-500 mb-2 font-telugu">ముందుగా విషయాన్ని సేవ్ చేసిన తర్వాత చిత్రం జోడించవచ్చు.</p>
  <?php endif; ?>

  <div class="subject-image-hitbox <?= $canUpload ? 'is-clickable' : 'is-disabled' ?>"
       <?= $canUpload ? 'role="button" tabindex="0" aria-label="విషయ చిత్రం జోడించండి"' : '' ?>>
    <img id="subjectImagePreview" src="<?= $imgUrl !== '' ? admin_e($imgUrl) : '' ?>"
         alt="" class="subject-image-preview <?= $imgUrl === '' ? 'hidden' : '' ?>" />
    <div id="subjectImageAvatar" class="subject-image-avatar font-telugu <?= $imgUrl !== '' ? 'hidden' : '' ?>"
         style="background:<?= admin_e($palette['background']) ?>;color:<?= admin_e($palette['color']) ?>;">
      <span class="subject-image-avatar-initials" id="subjectImageInitials"><?= admin_e($initials) ?></span>
      <span class="subject-image-avatar-label" id="subjectImageLabel"><?= admin_e($label) ?></span>
      <?php if ($canUpload): ?>
      <span class="subject-image-hint">చిత్రం జోడించడానికి క్లిక్ చేయండి</span>
      <?php endif; ?>
    </div>
  </div>

  <div id="subjectImageUploader" class="subject-image-uploader hidden">
    <form id="subjectImageUploadForm" class="space-y-2" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload_image" />
      <input type="hidden" name="entity" value="subject" />
      <input type="hidden" name="id" value="<?= $subjectId ?>" />
      <input type="hidden" name="_csrf" value="<?= admin_e(admin_csrf_token()) ?>" />
      <input type="file" name="image_file" id="subjectImageFile"
        accept="<?= admin_e(ImageUploadService::acceptAttribute()) ?>"
        class="text-xs w-full border border-slate-200 rounded-lg px-2 py-2" />
      <div class="flex gap-2">
        <button type="submit" class="flex-1 py-2 text-xs font-bold rounded-lg bg-slate-900 text-white">అప్‌లోడ్</button>
        <button type="button" id="subjectImageCancel" class="px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700">రద్దు</button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  var root = document.getElementById('subjectImagePicker');
  if (!root) return;
  var hitbox = root.querySelector('.subject-image-hitbox');
  var uploader = document.getElementById('subjectImageUploader');
  var form = document.getElementById('subjectImageUploadForm');
  var fileInput = document.getElementById('subjectImageFile');
  var img = document.getElementById('subjectImagePreview');
  var avatar = document.getElementById('subjectImageAvatar');
  var cancelBtn = document.getElementById('subjectImageCancel');
  var nameInput = document.querySelector('input[name="name"]');
  var nameTeInput = document.querySelector('input[name="name_te"]');

  function displayLabel() {
    var te = nameTeInput && nameTeInput.value.trim();
    if (te) return te;
    return nameInput && nameInput.value.trim() ? nameInput.value.trim() : 'విషయం';
  }

  function initialsFromLabel(label) {
    label = (label || '').trim();
    if (!label) return '—';
    var chars = [];
    var re = /\p{L}/gu, m;
    while ((m = re.exec(label)) && chars.length < 2) chars.push(m[0]);
    if (chars.length) return chars.join('').toUpperCase();
    return label.slice(0, 2).toUpperCase();
  }

  function syncAvatarText() {
    var label = displayLabel();
    var el = document.getElementById('subjectImageLabel');
    var ini = document.getElementById('subjectImageInitials');
    if (el) el.textContent = label;
    if (ini) ini.textContent = initialsFromLabel(label);
  }

  function showImage(url) {
    if (!img || !avatar) return;
    if (url) {
      img.src = url;
      img.classList.remove('hidden');
      avatar.classList.add('hidden');
    } else {
      img.src = '';
      img.classList.add('hidden');
      avatar.classList.remove('hidden');
    }
  }

  if (nameInput) nameInput.addEventListener('input', syncAvatarText);
  if (nameTeInput) nameTeInput.addEventListener('input', syncAvatarText);

  function openUploader() {
    if (parseInt(root.getAttribute('data-subject-id') || '0', 10) < 1) return;
    uploader.classList.remove('hidden');
    if (fileInput) fileInput.click();
  }

  if (hitbox && hitbox.classList.contains('is-clickable')) {
    hitbox.addEventListener('click', openUploader);
    hitbox.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openUploader(); }
    });
  }

  if (cancelBtn) {
    cancelBtn.addEventListener('click', function () {
      uploader.classList.add('hidden');
      if (fileInput) fileInput.value = '';
    });
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var sid = parseInt(root.getAttribute('data-subject-id') || '0', 10);
      if (sid < 1) return;
      var fd = new FormData(form);
      fetch(root.getAttribute('data-upload-url'), { method: 'POST', credentials: 'same-origin', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d.ok) throw new Error(d.error || 'Upload failed');
          var url = d.url || '';
          if (url && d.v) url += (url.indexOf('?') >= 0 ? '&' : '?') + 'v=' + d.v;
          showImage(url);
          uploader.classList.add('hidden');
          if (fileInput) fileInput.value = '';
        })
        .catch(function (err) { alert(err.message || 'Upload failed'); });
    });
  }
})();
</script>
