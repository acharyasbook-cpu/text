(function () {
  const grid = document.getElementById('homeCourseGrid') || document.getElementById('learnSubCourseGrid');
  if (!grid) return;

  const scope = grid.dataset.mediaScope || 'courses';
  const courseId = grid.dataset.courseId || '';
  const pollUrl =
    'catalog_media.php?scope=' +
    encodeURIComponent(scope) +
    (courseId ? '&course_id=' + encodeURIComponent(courseId) : '');

  const placeholderHtml =
    '<div class="text-center p-6 text-slate-400">' +
    '<svg class="w-12 h-12 mx-auto opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>' +
    '<p class="text-xs mt-2 font-medium">No image</p></div>';

  function renderMedia(mediaEl, item) {
    if (!item.url) {
      mediaEl.innerHTML = placeholderHtml;
      return;
    }
    const img = document.createElement('img');
    img.src = item.url + (item.v ? '?v=' + item.v : '');
    img.alt = '';
    img.loading = 'lazy';
    img.className = 'course-cover-img w-full h-full object-cover';
    mediaEl.innerHTML = '';
    mediaEl.appendChild(img);
  }

  async function refresh() {
    try {
      const res = await fetch(pollUrl, { cache: 'no-store' });
      if (!res.ok) return;
      const data = await res.json();
      const items = data.items || [];
      items.forEach(function (item) {
        const card = grid.querySelector('[data-entity-id="' + item.id + '"]');
        if (!card) return;
        const mediaEl = card.querySelector('.classical-card-media');
        if (!mediaEl) return;
        const prev = card.getAttribute('data-image-path') || '';
        if (prev !== item.image_path) {
          card.setAttribute('data-image-path', item.image_path);
          renderMedia(mediaEl, item);
        } else {
          const img = mediaEl.querySelector('.course-cover-img, img');
          if (img && item.url && item.v) {
            const next = item.url + '?v=' + item.v;
            if (img.getAttribute('src') !== next) img.setAttribute('src', next);
          }
        }
      });
    } catch (e) {
      /* silent */
    }
  }

  const logoImg = document.getElementById('siteLogoImg');
  async function refreshLogo() {
    if (!logoImg) return;
    try {
      const res = await fetch('catalog_media.php?scope=logo', { cache: 'no-store' });
      if (!res.ok) return;
      const data = await res.json();
      if (!data.url) return;
      const next = data.url + (data.v ? '?v=' + data.v : '');
      if (logoImg.getAttribute('src') !== next) logoImg.setAttribute('src', next);
    } catch (e) {
      /* silent */
    }
  }

  refresh();
  refreshLogo();
  setInterval(refresh, 15000);
  setInterval(refreshLogo, 20000);
})();
