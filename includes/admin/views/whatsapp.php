<?php
$waApi = admin_url('whatsapp_api.php');
?>
<div class="font-telugu max-w-4xl mx-auto" id="waHubRoot" data-api="<?= admin_e($waApi) ?>">
  <header class="mb-6 pb-4 border-b border-[#E3E6F0]">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">WhatsApp హబ్</h1>
    <p class="text-sm text-slate-600 mt-1">సబ్-కోర్స్ వారీ గ్రూప్ లింక్ · మీడియా/టెక్స్ట్ బ్రాడ్కాస్ట్ (API కీ అవసరం లేదు)</p>
  </header>

  <p id="waMigrateHint" class="hidden mb-4 text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 font-telugu">
    డేటాబేస్ మైగ్రేషన్: <code class="text-xs">php database/migrate_whatsapp_sub_course_groups.php</code>
  </p>

  <section class="cm-card mb-6 overflow-hidden border-2 border-slate-200">
    <div class="px-5 py-3 border-b border-[#E3E6F0] bg-slate-50">
      <h2 class="text-sm font-bold text-slate-900 font-telugu">1. సబ్-కోర్స్ గ్రూప్ మ్యాపింగ్</h2>
      <p class="text-[11px] text-slate-600 mt-0.5 font-telugu">ప్రతి ప్రోగ్రామ్‌కు WhatsApp గ్రూప్ ఇన్వైట్ లింక్</p>
    </div>
    <div class="p-5 grid sm:grid-cols-2 gap-4 bg-white">
      <div>
        <label class="cm-label block text-xs mb-1.5 font-telugu">మెయిన్ కోర్స్</label>
        <select id="waMainCourse" class="cm-input cm-select w-full rounded-lg px-3 py-2.5 text-sm font-medium"></select>
      </div>
      <div>
        <label class="cm-label block text-xs mb-1.5 font-telugu">సబ్ కోర్స్</label>
        <select id="waSubCourse" class="cm-input cm-select w-full rounded-lg px-3 py-2.5 text-sm" disabled></select>
      </div>
      <div class="sm:col-span-2">
        <label class="cm-label block text-xs mb-1.5 font-telugu">WhatsApp గ్రూప్ ఇన్వైట్ లింక్</label>
        <input type="url" id="waGroupLink" class="cm-input w-full rounded-lg px-3 py-2.5 text-sm font-telugu"
               placeholder="https://chat.whatsapp.com/…" disabled />
        <p class="text-[11px] text-slate-500 mt-1 font-telugu">ఉదా: chat.whatsapp.com లింక్ — ఈ సబ్-కోర్స్ విద్యార్థుల గ్రూప్</p>
        <p id="waInviteToken" class="text-[11px] text-emerald-700 mt-1 font-mono min-h-[1rem]"></p>
      </div>
      <div class="sm:col-span-2 flex flex-wrap gap-2">
        <button type="button" id="waSaveLink" class="px-5 py-2 rounded-lg bg-slate-900 text-white text-sm font-bold font-telugu" disabled>లింక్ సేవ్</button>
        <button type="button" id="waOpenGroup" class="px-5 py-2 rounded-lg border border-[#E3E6F0] text-sm font-semibold text-slate-800 font-telugu" disabled>గ్రూప్ తెరవండి</button>
      </div>
      <p id="waLinkMsg" class="sm:col-span-2 text-xs text-slate-600 min-h-[1rem] font-telugu"></p>
    </div>
  </section>

  <section class="cm-card mb-6 overflow-hidden border-2 border-slate-200">
    <div class="px-5 py-3 border-b border-[#E3E6F0] bg-slate-50">
      <h2 class="text-sm font-bold text-slate-900 font-telugu">2. మీడియా డిస్పాచ్ కంపోజర్</h2>
      <p class="text-[11px] text-slate-600 mt-0.5 font-telugu">టెక్స్ట్ · PDF/నోట్స్ · ఆడియో · వీడియో</p>
    </div>
    <div class="p-5 bg-white space-y-4">
      <div>
        <label class="cm-label block text-xs mb-1.5 font-telugu">సందేశం (టెక్స్ట్)</label>
        <textarea id="waMessage" rows="5" class="cm-input w-full rounded-lg px-3 py-2.5 text-sm font-telugu leading-relaxed resize-y"
                  placeholder="విద్యార్థులకు పంపే సందేశం ఇక్కడ రాయండి…"></textarea>
      </div>
      <div>
        <label class="cm-label block text-xs mb-1.5 font-telugu">మీడియా అటాచ్‌మెంట్</label>
        <input type="file" id="waFile" class="block w-full text-sm text-slate-700 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:font-semibold file:text-slate-800"
               accept=".pdf,.doc,.docx,.txt,.mp3,.m4a,.ogg,.wav,.mp4,.webm,.mov,.jpg,.jpeg,.png,.gif,.webp" />
        <p class="text-[11px] text-slate-500 mt-1 font-telugu">PDF · ఆడియో · వీడియో · చిత్రం (గరిష్ట 50 MB)</p>
      </div>
      <div id="waAttachmentPreview" class="hidden rounded-lg border border-[#E3E6F0] bg-slate-50 px-4 py-3 text-sm">
        <p class="font-semibold text-slate-800 font-telugu" id="waAttachmentName"></p>
        <a id="waAttachmentUrl" href="#" target="_blank" rel="noopener" class="text-xs text-royal break-all"></a>
      </div>
      <div class="flex flex-wrap gap-2 pt-2 border-t border-dashed border-[#E3E6F0]">
        <button type="button" id="waSend" class="px-6 py-2.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-bold font-telugu shadow-sm">
          WhatsApp గ్రూప్‌కు పంపండి
        </button>
        <button type="button" id="waCopyMsg" class="px-4 py-2.5 rounded-lg border border-[#E3E6F0] text-sm font-semibold font-telugu">సందేశం కాపీ</button>
        <button type="button" id="waShareNative" class="px-4 py-2.5 rounded-lg border border-[#E3E6F0] text-sm font-semibold font-telugu hidden">నేటివ్ షేర్</button>
      </div>
      <p id="waDispatchMsg" class="text-xs text-slate-600 min-h-[1.25rem] font-telugu"></p>
      <ol id="waDispatchSteps" class="hidden text-xs text-slate-700 space-y-1 list-decimal list-inside font-telugu bg-slate-50 rounded-lg p-3 border border-[#E3E6F0]"></ol>
    </div>
  </section>

  <section class="cm-card p-4 bg-slate-50 border border-[#E3E6F0]">
    <p class="text-xs text-slate-600 font-telugu leading-relaxed">
      <strong>గమనిక:</strong> అధికారిక WhatsApp API లేకుండా, సిస్టమ్ గ్రూప్ లింక్ తెరచి + సందేశం/ఫైల్‌లను మీ డివైస్ WhatsApp/Web షేర్ ద్వారా పంపుతుంది.
      మొబైల్‌లో <em>నేటివ్ షేర్</em> బటన్ ఫైల్‌ను నేరుగా WhatsAppకు అప్పగిస్తుంది.
    </p>
  </section>
</div>

<script>
(function () {
  var root = document.getElementById('waHubRoot');
  if (!root) return;
  var api = root.getAttribute('data-api');
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  var el = {
    migrate: document.getElementById('waMigrateHint'),
    main: document.getElementById('waMainCourse'),
    sub: document.getElementById('waSubCourse'),
    link: document.getElementById('waGroupLink'),
    token: document.getElementById('waInviteToken'),
    saveLink: document.getElementById('waSaveLink'),
    openGroup: document.getElementById('waOpenGroup'),
    linkMsg: document.getElementById('waLinkMsg'),
    message: document.getElementById('waMessage'),
    file: document.getElementById('waFile'),
    attachPreview: document.getElementById('waAttachmentPreview'),
    attachName: document.getElementById('waAttachmentName'),
    attachUrl: document.getElementById('waAttachmentUrl'),
    send: document.getElementById('waSend'),
    copyMsg: document.getElementById('waCopyMsg'),
    shareNative: document.getElementById('waShareNative'),
    dispatchMsg: document.getElementById('waDispatchMsg'),
    steps: document.getElementById('waDispatchSteps'),
  };

  var state = { subCourses: [], attachment: null };

  if (typeof navigator !== 'undefined' && navigator.share) {
    el.shareNative.classList.remove('hidden');
  }

  function headers(extra) {
    var h = { 'X-CSRF-Token': csrf };
    if (extra) Object.keys(extra).forEach(function (k) { h[k] = extra[k]; });
    return h;
  }

  function fetchJson(url) {
    return fetch(url, { credentials: 'same-origin', headers: headers() }).then(function (r) { return r.json(); });
  }

  function postJson(body) {
    body._csrf = csrf;
    return fetch(api, {
      method: 'POST', credentials: 'same-origin',
      headers: headers({ 'Content-Type': 'application/json' }),
      body: JSON.stringify(body),
    }).then(function (r) { return r.json(); });
  }

  function fillSelect(sel, items, valueKey, labelFn, placeholder) {
    sel.innerHTML = '';
    var o0 = document.createElement('option');
    o0.value = '';
    o0.textContent = placeholder || '— ఎంచుకోండి —';
    sel.appendChild(o0);
    (items || []).forEach(function (it) {
      var o = document.createElement('option');
      o.value = String(it[valueKey]);
      o.textContent = labelFn(it);
      sel.appendChild(o);
    });
  }

  function setLinkUi(sc) {
    var has = sc && parseInt(sc.id, 10) > 0;
    el.link.disabled = !has;
    el.saveLink.disabled = !has;
    el.openGroup.disabled = !has || !(sc.whatsapp_group_link);
    if (!has) {
      el.link.value = '';
      el.token.textContent = '';
      return;
    }
    el.link.value = sc.whatsapp_group_link || '';
    var tok = sc.whatsapp_group_link ? (sc.whatsapp_group_link.match(/chat\.whatsapp\.com\/(?:invite\/)?([A-Za-z0-9_-]+)/i) || [])[1] : '';
    el.token.textContent = tok ? ('టోకెన్: ' + tok) : '';
  }

  function loadBootstrap(courseId) {
    var q = api + '?action=bootstrap' + (courseId ? '&course_id=' + courseId : '');
    return fetchJson(q).then(function (d) {
      if (d.migration_required) {
        el.migrate.classList.remove('hidden');
        return;
      }
      el.migrate.classList.add('hidden');
      fillSelect(el.main, d.main_courses || [], 'id', function (c) {
        return (c.name_te ? c.name_te + ' · ' : '') + c.name;
      });
      state.subCourses = d.sub_courses || [];
      fillSelect(el.sub, state.subCourses, 'id', function (s) {
        return (s.name_te || s.name) + (s.whatsapp_group_link ? ' ✓' : '');
      }, '— సబ్ కోర్స్ —');
      el.sub.disabled = state.subCourses.length === 0;
      if (courseId && el.main.value !== String(courseId)) {
        el.main.value = String(courseId);
      }
    });
  }

  el.main.addEventListener('change', function () {
    var cid = el.main.value;
    el.sub.disabled = true;
    el.linkMsg.textContent = '';
    loadBootstrap(cid ? parseInt(cid, 10) : 0);
  });

  el.sub.addEventListener('change', function () {
    var id = parseInt(el.sub.value, 10);
    var sc = state.subCourses.find(function (s) { return parseInt(s.id, 10) === id; });
    setLinkUi(sc || null);
    el.linkMsg.textContent = sc ? 'ఎంచుకున్న సబ్-కోర్స్: ' + (sc.name_te || sc.name) : '';
  });

  el.saveLink.addEventListener('click', function () {
    var scid = parseInt(el.sub.value, 10);
    if (!scid) return;
    el.linkMsg.textContent = 'సేవ్ అవుతోంది…';
    postJson({
      action: 'save_group_link',
      sub_course_id: scid,
      whatsapp_group_link: el.link.value.trim(),
    }).then(function (d) {
      if (!d.ok) throw new Error(d.error || 'Save failed');
      var idx = state.subCourses.findIndex(function (s) { return parseInt(s.id, 10) === scid; });
      if (idx >= 0 && d.item) state.subCourses[idx] = d.item;
      setLinkUi(d.item);
      el.linkMsg.textContent = '✓ గ్రూప్ లింక్ సేవ్ అయింది.';
      fillSelect(el.sub, state.subCourses, 'id', function (s) {
        return (s.name_te || s.name) + (s.whatsapp_group_link ? ' ✓' : '');
      }, '— సబ్ కోర్స్ —');
      el.sub.value = String(scid);
    }).catch(function (e) { el.linkMsg.textContent = e.message; });
  });

  el.openGroup.addEventListener('click', function () {
    var url = el.link.value.trim();
    if (url) window.open(url, '_blank', 'noopener,noreferrer');
  });

  el.file.addEventListener('change', function () {
    var f = el.file.files && el.file.files[0];
    state.attachment = null;
    el.attachPreview.classList.add('hidden');
    if (!f) return;
    el.dispatchMsg.textContent = 'ఫైల్ అప్‌లోడ్ అవుతోంది…';
    var fd = new FormData();
    fd.append('dispatch_file', f);
    fd.append('_csrf', csrf);
    fetch(api, { method: 'POST', credentials: 'same-origin', headers: headers(), body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.ok) throw new Error(d.error || 'Upload failed');
        state.attachment = d.attachment;
        el.attachName.textContent = d.attachment.original_name + ' (' + d.attachment.kind + ')';
        el.attachUrl.textContent = d.attachment.public_url;
        el.attachUrl.href = d.attachment.public_url;
        el.attachPreview.classList.remove('hidden');
        el.dispatchMsg.textContent = '✓ ఫైల్ సిద్ధం.';
      })
      .catch(function (e) { el.dispatchMsg.textContent = e.message; el.file.value = ''; });
  });

  function runDispatch() {
    var scid = parseInt(el.sub.value, 10);
    if (!scid) {
      el.dispatchMsg.textContent = 'ముందుగా సబ్-కోర్స్ ఎంచుకోండి.';
      return Promise.reject();
    }
    var link = el.link.value.trim();
    if (!link) {
      el.dispatchMsg.textContent = 'ముందుగు గ్రూప్ ఇన్వైట్ లింక్ సేవ్ చేయండి.';
      return Promise.reject();
    }
    el.dispatchMsg.textContent = 'డిస్పాచ్ సిద్ధం చేస్తోంది…';
    var saveP = postJson({
      action: 'save_group_link',
      sub_course_id: scid,
      whatsapp_group_link: link,
    });
    var attachments = state.attachment ? [state.attachment] : [];
    return saveP.then(function () {
      return postJson({
        action: 'prepare_dispatch',
        sub_course_id: scid,
        message: el.message.value,
        attachments: attachments,
      });
    }).then(function (d) {
      if (!d.ok || !d.dispatch) throw new Error(d.error || 'Dispatch failed');
      return d.dispatch;
    });
  }

  function showSteps(plan) {
    el.steps.innerHTML = '';
    var steps = [
      'గ్రూప్ చాట్ తెరవండి (లింక్ బటన్).',
      'సందేశం కాపీ అయింది — గ్రూప్‌లో అతికించండి.',
    ];
    if (plan.attachments && plan.attachments.length) {
      steps.push('అటాచ్‌మెంట్ లింక్‌ను కూడా పంపండి (లేదా నేటివ్ షేర్ ఉపయోగించండి).');
    }
    steps.forEach(function (t) {
      var li = document.createElement('li');
      li.textContent = t;
      el.steps.appendChild(li);
    });
    el.steps.classList.remove('hidden');
  }

  el.send.addEventListener('click', function () {
    runDispatch().then(function (plan) {
      var text = plan.message || '';
      if (navigator.clipboard && navigator.clipboard.writeText) {
        return navigator.clipboard.writeText(text).then(function () { return plan; });
      }
      return plan;
    }).then(function (plan) {
      el.dispatchMsg.textContent = '✓ సందేశం కాపీ అయింది. WhatsApp తెరుస్తోంది…';
      showSteps(plan);
      if (plan.group_link) window.open(plan.group_link, 'wa_group', 'noopener,noreferrer');
      setTimeout(function () {
        window.open(plan.share_text_url || plan.wa_me_url, 'wa_share', 'noopener,noreferrer');
      }, 600);
      if (state.attachment && navigator.share && navigator.canShare) {
        fetch(state.attachment.public_url).then(function (r) { return r.blob(); })
          .then(function (blob) {
            var file = new File([blob], state.attachment.original_name, { type: state.attachment.mime });
            if (navigator.canShare({ files: [file], text: text })) {
              return navigator.share({ files: [file], text: text, title: 'Acharya Books' });
            }
          }).catch(function () { /* optional */ });
      }
    }).catch(function (e) {
      if (e && e.message) el.dispatchMsg.textContent = e.message;
    });
  });

  el.copyMsg.addEventListener('click', function () {
    runDispatch().then(function (plan) {
      var text = plan.message || el.message.value;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        return navigator.clipboard.writeText(text);
      }
      throw new Error('Clipboard not available');
    }).then(function () {
      el.dispatchMsg.textContent = '✓ సందేశం కాపీ అయింది.';
    }).catch(function (e) { el.dispatchMsg.textContent = e.message || 'కాపీ విఫలమైంది'; });
  });

  el.shareNative.addEventListener('click', function () {
    runDispatch().then(function (plan) {
      var text = plan.message || '';
      if (state.attachment && state.attachment.public_url) {
        return fetch(state.attachment.public_url).then(function (r) { return r.blob(); })
          .then(function (blob) {
            var file = new File([blob], state.attachment.original_name, { type: state.attachment.mime });
            return navigator.share({ files: [file], text: text, title: plan.sub_course_name || 'Acharya' });
          });
      }
      return navigator.share({ text: text, title: plan.sub_course_name || 'Acharya' });
    }).then(function () {
      el.dispatchMsg.textContent = '✓ షేర్ పూర్తయింది.';
    }).catch(function (e) {
      if (e && e.name !== 'AbortError') el.dispatchMsg.textContent = e.message || 'షేర్ విఫలమైంది';
    });
  });

  loadBootstrap(0).catch(function (e) {
    el.linkMsg.textContent = e.message || 'లోడ్ విఫలమైంది';
  });
})();
</script>
