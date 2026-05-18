(function () {
  var root = document.getElementById('stm-root');
  if (!root) return;

  var API = root.getAttribute('data-api');
  var CSRF = root.getAttribute('data-csrf');
  var state = {
    subCourseId: 0,
    termKey: 'short_term',
    plannerMode: 'day_wise',
    subjects: [],
    activeDayId: 0,
    shortDayId: 0,
    longDayId: 0,
    calendarDays: [],
    stagedShort: [],
    stagedLong: [],
    dirtyShort: false,
    dirtyLong: false,
    topicsRequestId: 0,
    tracker: null,
    previewDayId: 0,
    hybridEdit: null
  };

  var el = {
    course: document.getElementById('stm-course'),
    subcourse: document.getElementById('stm-subcourse'),
    term: document.getElementById('stm-term'),
    month: document.getElementById('stm-month'),
    calendar: document.getElementById('stm-calendar'),
    saveShort: document.getElementById('stm-save-short'),
    saveLong: document.getElementById('stm-save-long'),
    copyLayout: document.getElementById('stm-copy-layout'),
    totalMarks: document.getElementById('stm-total-marks'),
    stagedCount: document.getElementById('stm-staged-count'),
    activeTermLabel: document.getElementById('stm-active-term-label'),
    activeStaging: document.getElementById('stm-active-staging-body'),
    dayTitle: document.getElementById('stm-day-title'),
    dayMeta: document.getElementById('stm-day-meta'),
    dayIndex: document.getElementById('stm-day-index'),
    scheduleDate: document.getElementById('stm-schedule-date'),
    wrapDay: document.getElementById('stm-wrap-day-index'),
    wrapDate: document.getElementById('stm-wrap-schedule-date'),
    msg: document.getElementById('stm-msg'),
    dualTracker: document.getElementById('stm-dual-tracker'),
    matrixShort: document.getElementById('stm-matrix-short'),
    matrixLong: document.getElementById('stm-matrix-long'),
    previewBody: document.getElementById('stm-preview-body'),
    holdShort: document.getElementById('stm-hold-short'),
    holdLong: document.getElementById('stm-hold-long'),
    previewWa: document.getElementById('stm-preview-wa'),
    waDispatch: document.getElementById('stm-wa-dispatch'),
    composerSubject: document.getElementById('stm-composer-subject'),
    composerTopics: document.getElementById('stm-composer-topics'),
    composerMarks: document.getElementById('stm-composer-marks'),
    customTopicTitle: document.getElementById('stm-custom-topic-title'),
    createTopic: document.getElementById('stm-create-topic'),
    addToList: document.getElementById('stm-add-to-list'),
    composerMsg: document.getElementById('stm-composer-msg'),
    hybridModal: document.getElementById('stm-hybrid-modal'),
    hybridPool: document.getElementById('stm-hybrid-pool'),
    hybridSearch: document.getElementById('stm-hybrid-search'),
    hybridExternal: document.getElementById('stm-hybrid-external')
  };

  function markDirty(termKey) {
    if (termKey === 'long_term') state.dirtyLong = true;
    else state.dirtyShort = true;
  }

  function isDirty(termKey) {
    return termKey === 'long_term' ? state.dirtyLong : state.dirtyShort;
  }

  function readActiveTermFromUi() {
    if (el.term) state.termKey = el.term.value;
  }

  function stagedForTerm(termKey) {
    return termKey === 'long_term' ? state.stagedLong : state.stagedShort;
  }

  function setStagedForTerm(termKey, rows) {
    if (termKey === 'long_term') state.stagedLong = rows;
    else state.stagedShort = rows;
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
  }

  function getMode() {
    var r = document.querySelector('input[name="stm-mode"]:checked');
    return r ? r.value : 'day_wise';
  }

  function post(action, body) {
    body = body || {};
    body.action = action;
    body._csrf = CSRF;
    return fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(body)
    }).then(function (r) { return r.json(); });
  }

  function get(action, params) {
    var q = new URLSearchParams(params || {});
    q.set('action', action);
    return fetch(API + '?' + q.toString(), { credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }

  function setMsg(t, err) {
    if (!el.msg) return;
    el.msg.textContent = t || '';
    el.msg.className = 'text-xs mt-3 min-h-[1rem] font-telugu ' + (err ? 'text-red-600' : 'text-slate-500');
  }

  function setComposerMsg(t, err) {
    if (!el.composerMsg) return;
    el.composerMsg.textContent = t || '';
    el.composerMsg.className = 'text-xs mt-2 min-h-[1rem] ' + (err ? 'text-red-600' : 'text-slate-500');
  }

  function filterSubcourses() {
    var cid = el.course && el.course.value;
    Array.prototype.forEach.call(el.subcourse.options, function (o) {
      if (!o.value) return;
      var dc = o.getAttribute('data-course');
      o.hidden = !!(cid && dc && dc !== cid);
    });
  }

  function syncModeUi() {
    var dateMode = state.plannerMode === 'date_wise';
    if (el.wrapDay) el.wrapDay.classList.toggle('hidden', dateMode);
    if (el.wrapDate) el.wrapDate.classList.toggle('hidden', !dateMode);
    document.querySelectorAll('input[name="stm-mode"]').forEach(function (r) {
      r.checked = r.value === state.plannerMode;
    });
  }

  function subjectById(id) {
    var sid = parseInt(id, 10);
    for (var i = 0; i < state.subjects.length; i++) {
      if (parseInt(state.subjects[i].id, 10) === sid) return state.subjects[i];
    }
    return null;
  }

  function subjectLabel(s) {
    return (s && (s.name_te || s.name)) || '—';
  }

  function populateComposerSubjects() {
    if (!el.composerSubject) return;
    var html = '<option value="">— విషయం —</option>';
    state.subjects.forEach(function (s) {
      html += '<option value="' + s.id + '">' + esc(s.name_te || s.name) + '</option>';
    });
    el.composerSubject.innerHTML = html;
    el.composerSubject.disabled = state.subjects.length === 0;
    if (el.addToList) el.addToList.disabled = state.subjects.length === 0;
  }

  /** Clear topic list, then AJAX-load topics for the selected subject. */
  function loadTopicsForComposer() {
    if (!el.composerTopics) return;
    el.composerTopics.innerHTML = '';
    var sid = parseInt(el.composerSubject && el.composerSubject.value, 10) || 0;
    if (!sid) {
      el.composerTopics.innerHTML = '<p class="text-slate-400 font-telugu">ముందుగా విషయం ఎంచుకోండి</p>';
      return;
    }
    el.composerTopics.innerHTML = '<p class="text-slate-400 font-telugu">టాపిక్‌లు లోడ్ అవుతున్నాయి…</p>';
    var reqId = ++state.topicsRequestId;
    get('topics', { subject_id: sid }).then(function (d) {
      if (reqId !== state.topicsRequestId) return;
      el.composerTopics.innerHTML = '';
      if (!d.ok) {
        el.composerTopics.innerHTML = '<p class="text-red-600 text-xs">' + esc(d.error || 'లోపం') + '</p>';
        return;
      }
      var topics = d.topics || [];
      if (!topics.length) {
        el.composerTopics.innerHTML = '<p class="text-amber-700 text-xs font-telugu">ఈ విషయానికి టాపిక్‌లు లేవు.</p>';
        return;
      }
      topics.forEach(function (t) {
        var id = String(t.id);
        var lbl = document.createElement('label');
        lbl.className = 'flex items-center gap-2 py-0.5 cursor-pointer font-telugu';
        var custom = parseInt(t.is_custom, 10) === 1 ? ' <span class="text-indigo-600 text-[10px]">(custom)</span>' : '';
        lbl.innerHTML = '<input type="checkbox" class="stm-composer-topic-cb" value="' + id + '" />' +
          '<span>' + esc(t.title) + custom + '</span>';
        el.composerTopics.appendChild(lbl);
      });
      if (el.createTopic) el.createTopic.disabled = false;
    }).catch(function () {
      if (reqId !== state.topicsRequestId) return;
      el.composerTopics.innerHTML = '<p class="text-red-600 text-xs">టాపిక్‌లు లోడ్ కాలేదు.</p>';
    });
  }

  function resetComposer() {
    if (el.composerSubject) el.composerSubject.value = '';
    if (el.composerMarks) el.composerMarks.value = '25';
    if (el.composerTopics) {
      el.composerTopics.innerHTML = '<p class="text-slate-400 font-telugu">ముందుగా విషయం ఎంచుకోండి</p>';
    }
    setComposerMsg('');
  }

  function getComposerSelection() {
    var sid = parseInt(el.composerSubject && el.composerSubject.value, 10) || 0;
    var sub = subjectById(sid);
    var topicIds = [];
    var topicNames = [];
    if (el.composerTopics) {
      el.composerTopics.querySelectorAll('.stm-composer-topic-cb:checked').forEach(function (cb) {
        topicIds.push(parseInt(cb.value, 10));
        var span = cb.parentElement && cb.parentElement.querySelector('span');
        topicNames.push(span ? span.textContent : '');
      });
    }
    var marks = parseInt(el.composerMarks && el.composerMarks.value, 10) || 25;
    return {
      subject_id: sid,
      subject_name: subjectLabel(sub),
      topic_ids: topicIds,
      topic_names: topicNames,
      total_marks: marks
    };
  }

  function syncStagingFromDom(termKey) {
    var container = el.activeStaging;
    if (!container || state.termKey !== termKey) return;
    var rows = stagedForTerm(termKey);
    container.querySelectorAll('tr[data-idx]').forEach(function (tr) {
      var idx = parseInt(tr.getAttribute('data-idx'), 10);
      if (!rows[idx]) return;
      var topicInp = tr.querySelector('.stm-inline-topic');
      var marksInp = tr.querySelector('.stm-inline-marks');
      if (topicInp) {
        rows[idx].topic_label = topicInp.value;
        rows[idx].row_meta = rows[idx].row_meta || {};
        rows[idx].row_meta.topic_label = topicInp.value;
      }
      if (marksInp) {
        rows[idx].total_marks = parseInt(marksInp.value, 10) || 25;
        rows[idx].ai_pool_count = rows[idx].total_marks;
      }
    });
  }

  function addToStagedList() {
    readActiveTermFromUi();
    syncStagingFromDom(state.termKey);
    var sel = getComposerSelection();
    var termKey = state.termKey;
    if (!sel.subject_id) {
      setComposerMsg('విషయం ఎంచుకోండి', true);
      return;
    }
    if (!sel.topic_ids.length) {
      setComposerMsg('కనీసం ఒక టాపిక్ ఎంచుకోండి', true);
      return;
    }
    var rows = stagedForTerm(termKey);
    rows.push({
      id: null,
      subject_id: sel.subject_id,
      subject_name: sel.subject_name,
      topic_ids: sel.topic_ids,
      topic_names: sel.topic_names,
      topic_label: sel.topic_names.join(', '),
      total_marks: sel.total_marks,
      question_mode: 'ai_pool',
      ai_pool_count: sel.total_marks,
      pool_question_ids: [],
      external_mcq_text: '',
      row_meta: { topic_label: sel.topic_names.join(', ') }
    });
    setStagedForTerm(termKey, rows);
    markDirty(termKey);
    resetComposer();
    setComposerMsg('✓ జాబితాకు జోడించబడింది.');
    renderActiveTermView();
  }

  function removeStagedRow(termKey, index) {
    syncStagingFromDom(termKey);
    var rows = stagedForTerm(termKey);
    rows.splice(index, 1);
    setStagedForTerm(termKey, rows);
    markDirty(termKey);
    renderActiveTermView();
  }

  function stagingTableHtml(rows, termKey) {
    if (!rows.length) {
      return '<p class="text-sm text-slate-500 font-telugu py-8 text-center">No Topics Scheduled<br><span class="text-xs">టాపిక్‌లు షెడ్యూల్ చేయలేదు</span></p>';
    }
    var html = '<table class="stm-preview-table font-telugu w-full"><thead><tr><th>#</th><th>విషయం</th><th>టాపిక్</th><th>మార్కులు</th><th></th></tr></thead><tbody>';
    rows.forEach(function (r, i) {
      var label = r.topic_label || (r.topic_names || []).join(', ');
      var qc = (r.pool_question_ids || []).length;
      html += '<tr data-term="' + termKey + '" data-idx="' + i + '"><td>' + (i + 1) + '</td><td>' + esc(r.subject_name) + '</td>' +
        '<td><input type="text" class="admin-input w-full stm-inline-topic" value="' + esc(label) + '" /></td>' +
        '<td><input type="number" class="admin-input w-14 stm-inline-marks" min="1" value="' + (r.total_marks || 25) + '" /></td>' +
        '<td class="whitespace-nowrap"><button type="button" class="stm-hybrid-open text-indigo-600 text-[10px] mr-1">MCQ</button>' +
        '<button type="button" class="stm-staged-remove text-red-600 text-[10px]">×</button></td></tr>';
      if ((r.question_mode || '') === 'hybrid' || (r.question_mode || '') === 'manual') {
        html += '<tr><td colspan="5" class="text-[10px] text-slate-500 pb-1">ప్రశ్నలు: ' + qc + '</td></tr>';
      }
    });
    return html + '</tbody></table>';
  }

  function bindStagingContainer(container, termKey) {
    if (!container) return;
    container.innerHTML = stagingTableHtml(stagedForTerm(termKey), termKey);
    container.querySelectorAll('.stm-inline-topic').forEach(function (inp) {
      inp.addEventListener('change', function () {
        var tr = inp.closest('tr');
        var idx = parseInt(tr.getAttribute('data-idx'), 10);
        var rows = stagedForTerm(termKey);
        if (rows[idx]) {
          rows[idx].topic_label = inp.value;
          rows[idx].row_meta = rows[idx].row_meta || {};
          rows[idx].row_meta.topic_label = inp.value;
          markDirty(termKey);
        }
      });
    });
    container.querySelectorAll('.stm-inline-marks').forEach(function (inp) {
      inp.addEventListener('change', function () {
        var tr = inp.closest('tr');
        var idx = parseInt(tr.getAttribute('data-idx'), 10);
        var rows = stagedForTerm(termKey);
        if (rows[idx]) {
          rows[idx].total_marks = parseInt(inp.value, 10) || 25;
          rows[idx].ai_pool_count = rows[idx].total_marks;
          markDirty(termKey);
        }
        updateTotalMarks();
      });
    });
    container.querySelectorAll('.stm-staged-remove').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var tr = btn.closest('tr[data-idx]');
        removeStagedRow(termKey, parseInt(tr.getAttribute('data-idx'), 10));
      });
    });
    container.querySelectorAll('.stm-hybrid-open').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var tr = btn.closest('tr[data-idx]');
        openHybridModal(termKey, parseInt(tr.getAttribute('data-idx'), 10));
      });
    });
  }

  function updateTermChrome() {
    var isLong = state.termKey === 'long_term';
    if (el.activeTermLabel) {
      el.activeTermLabel.textContent = isLong
        ? '⏳ లాంగ్ టర్మ్ — సక్రియ జాబితా'
        : '⏱️ షార్ట్ టర్మ్ — సక్రియ జాబితా';
    }
    if (el.term) el.term.value = state.termKey;
  }

  function renderActiveTermView() {
    readActiveTermFromUi();
    updateTermChrome();
    updateTotalMarks();
    var active = stagedForTerm(state.termKey);
    if (el.stagedCount) el.stagedCount.textContent = String(active.length);
    bindStagingContainer(el.activeStaging, state.termKey);
  }

  function renderStagedUi() {
    renderActiveTermView();
  }

  function stagedRowsFromServer(rows) {
    return (rows || []).map(function (r) {
      var topics = r.topics || [];
      var meta = r.row_meta || {};
      var names = topics.map(function (t) { return t.title || ''; });
      return {
        id: r.id ? parseInt(r.id, 10) : null,
        subject_id: parseInt(r.subject_id, 10),
        subject_name: (r.subject_name_te || r.subject_name || ''),
        topic_ids: (r.topic_ids || []).map(function (x) { return parseInt(x, 10); }),
        topic_names: names,
        topic_label: meta.topic_label || names.join(', '),
        total_marks: parseInt(r.total_marks, 10) || 25,
        question_mode: r.question_mode || 'ai_pool',
        ai_pool_count: parseInt(r.total_marks, 10) || 25,
        pool_question_ids: r.pool_question_ids || [],
        external_mcq_text: r.external_mcq_text || '',
        row_meta: meta
      };
    });
  }

  function clearDayEditor() {
    state.stagedShort = [];
    state.stagedLong = [];
    state.dirtyShort = false;
    state.dirtyLong = false;
    state.shortDayId = 0;
    state.longDayId = 0;
    state.activeDayId = 0;
    resetComposer();
    renderActiveTermView();
  }

  function applyWorkspace(ws, forceReload) {
    var sd = ws.short_term && ws.short_term.day;
    var ld = ws.long_term && ws.long_term.day;
    if (forceReload || !state.dirtyShort) {
      state.stagedShort = stagedRowsFromServer((ws.short_term && ws.short_term.rows) || []);
      state.shortDayId = sd ? parseInt(sd.id, 10) : 0;
      if (el.holdShort) el.holdShort.checked = sd ? !parseInt(sd.is_active, 10) : false;
      if (forceReload) state.dirtyShort = false;
    }
    if (forceReload || !state.dirtyLong) {
      state.stagedLong = stagedRowsFromServer((ws.long_term && ws.long_term.rows) || []);
      state.longDayId = ld ? parseInt(ld.id, 10) : 0;
      if (el.holdLong) el.holdLong.checked = ld ? !parseInt(ld.is_active, 10) : false;
      if (forceReload) state.dirtyLong = false;
    }
    renderActiveTermView();
  }

  function loadDualWorkspace(forceReload) {
    if (!state.subCourseId) return;
    var dayIndex = state.plannerMode === 'day_wise' ? parseInt(el.dayIndex && el.dayIndex.value, 10) : null;
    var scheduleDate = state.plannerMode === 'date_wise' ? (el.scheduleDate && el.scheduleDate.value) : null;
    if (!dayIndex && !scheduleDate) return;
    return get('dual_day', {
      sub_course_id: state.subCourseId,
      day_index: dayIndex || '',
      schedule_date: scheduleDate || ''
    }).then(function (d) {
      if (!d.ok || !d.workspace) return;
      applyWorkspace(d.workspace, !!forceReload);
    });
  }

  function onActiveTermChange() {
    if (!state.subCourseId) return;
    var previousTerm = state.termKey;
    syncStagingFromDom(previousTerm);
    readActiveTermFromUi();
    renderActiveTermView();
    setMsg((state.termKey === 'long_term' ? 'లాంగ్' : 'షార్ట్') + ' టర్మ్ — సేవ్ చేయని మార్పులు ఉంచబడ్డాయి');
    post('save_config', {
      sub_course_id: state.subCourseId,
      term_key: state.termKey,
      planner_mode: state.plannerMode
    }).then(function () {
      loadCalendar();
    });
  }

  function saveTermSchedule(termKey) {
    if (state.termKey === termKey) {
      syncStagingFromDom(termKey);
    }
    readActiveTermFromUi();
    var rows = stagedForTerm(termKey);
    if (!rows.length) {
      setMsg('కనీసం ఒక విషయం/టాపిక్ జోడించండి (' + (termKey === 'long_term' ? 'Long' : 'Short') + ')', true);
      return;
    }
    var dayId = termKey === 'long_term' ? state.longDayId : state.shortDayId;
    var payload = {
      id: dayId || null,
      sub_course_id: state.subCourseId,
      term_key: termKey,
      day_index: state.plannerMode === 'day_wise' ? parseInt(el.dayIndex.value, 10) : null,
      schedule_date: state.plannerMode === 'date_wise' ? el.scheduleDate.value : null,
      rows: collectRows(termKey)
    };
    setMsg((termKey === 'long_term' ? 'లాంగ్' : 'షార్ట్') + ' టర్మ్ సేవ్ అవుతోంది…');
    return post('save_day', payload).then(function (d) {
      if (!d.ok) {
        setMsg(d.error || 'లోపం', true);
        return;
      }
      if (termKey === 'long_term') {
        state.longDayId = d.day_id;
        state.dirtyLong = false;
      } else {
        state.shortDayId = d.day_id;
        state.dirtyShort = false;
      }
      setStagedForTerm(termKey, stagedRowsFromServer(d.rows || []));
      if (state.termKey === termKey) state.activeDayId = d.day_id;
      renderActiveTermView();
      setMsg((termKey === 'long_term' ? 'లాంగ్' : 'షార్ట్') + ' టర్మ్ షెడ్యూల్ సేవ్ అయ్యింది ✓');
      loadDualTracker();
      loadCalendar();
    });
  }

  function openHybridModal(termKey, idx) {
    var rows = stagedForTerm(termKey);
    var row = rows[idx];
    if (!row) return;
    state.hybridEdit = { termKey: termKey, idx: idx };
    if (el.hybridModal) el.hybridModal.classList.add('is-open');
    if (el.hybridExternal) el.hybridExternal.value = row.external_mcq_text || '';
    loadHybridPool(row);
  }

  function loadHybridPool(row) {
    if (!el.hybridPool) return;
    el.hybridPool.innerHTML = '<p class="p-2 text-slate-400">లోడ్…</p>';
    post('pool_mcqs', {
      subject_id: row.subject_id,
      topic_ids: row.topic_ids || [],
      q: el.hybridSearch ? el.hybridSearch.value : ''
    }).then(function (d) {
      el.hybridPool.innerHTML = '';
      if (!d.ok || !(d.items || []).length) {
        el.hybridPool.innerHTML = '<p class="p-2 text-amber-700">ప్రశ్నలు లేవు</p>';
        return;
      }
      var selected = (row.pool_question_ids || []).map(String);
      d.items.forEach(function (q) {
        var lbl = document.createElement('label');
        lbl.className = 'flex gap-2 p-2 cursor-pointer hover:bg-slate-50';
        var checked = selected.indexOf(String(q.id)) >= 0 ? ' checked' : '';
        lbl.innerHTML = '<input type="checkbox" class="stm-hybrid-q" value="' + q.id + '"' + checked + ' />' +
          '<span>' + esc((q.question_text || '').slice(0, 120)) + '</span>';
        el.hybridPool.appendChild(lbl);
      });
    });
  }

  function closeHybridModal() {
    state.hybridEdit = null;
    if (el.hybridModal) el.hybridModal.classList.remove('is-open');
  }

  function applyHybridModal() {
    if (!state.hybridEdit) return;
    var rows = stagedForTerm(state.hybridEdit.termKey);
    var row = rows[state.hybridEdit.idx];
    if (!row) return;
    var ids = [];
    if (el.hybridPool) {
      el.hybridPool.querySelectorAll('.stm-hybrid-q:checked').forEach(function (cb) {
        ids.push(parseInt(cb.value, 10));
      });
    }
    row.pool_question_ids = ids;
    row.external_mcq_text = el.hybridExternal ? el.hybridExternal.value : '';
    row.question_mode = (ids.length || row.external_mcq_text) ? 'hybrid' : 'ai_pool';
    markDirty(state.hybridEdit.termKey);
    closeHybridModal();
    renderActiveTermView();
  }

  function loadContext() {
    state.subCourseId = parseInt(el.subcourse.value, 10) || 0;
    readActiveTermFromUi();
    state.plannerMode = getMode();
    if (state.subCourseId < 1) {
      setMsg('సబ్-కోర్స్ ఎంచుకోండి');
      return;
    }
    clearDayEditor();
    setMsg('లోడ్ అవుతోంది…');
    post('save_config', {
      sub_course_id: state.subCourseId,
      term_key: state.termKey,
      planner_mode: state.plannerMode
    }).then(function () {
      return get('context', { sub_course_id: state.subCourseId, term_key: state.termKey });
    }).then(function (d) {
      if (!d.ok) {
        setMsg(d.error || 'లోపం', true);
        return;
      }
      state.subjects = d.subjects || [];
      state.plannerMode = (d.config && d.config.planner_mode) || state.plannerMode;
      syncModeUi();
      state.calendarDays = d.days || [];
      renderCalendar();
      populateComposerSubjects();
      if (el.saveShort) el.saveShort.disabled = false;
      if (el.saveLong) el.saveLong.disabled = false;
      if (el.copyLayout) el.copyLayout.disabled = false;
      if (el.waDispatch) el.waDispatch.disabled = false;
      loadDualTracker();
      renderActiveTermView();
      setMsg('రోజు ఎంచుకోండి — టర్మ్ మార్చినప్పుడు సేవ్ చేయని డేటా ఉండవచ్చు');
    });
  }

  function loadDualTracker() {
    if (!state.subCourseId || !el.dualTracker) return;
    get('dual_tracker', { sub_course_id: state.subCourseId }).then(function (d) {
      if (!d.ok || !d.tracker) return;
      state.tracker = d.tracker;
      el.dualTracker.classList.remove('hidden');
      renderTrackerBoard('short_term', d.tracker.short_term, {
        label: document.getElementById('stm-short-label'),
        pct: document.getElementById('stm-short-coverage-pct'),
        bar: document.getElementById('stm-short-coverage-bar'),
        detail: document.getElementById('stm-short-coverage-detail'),
        matrix: el.matrixShort
      });
      renderTrackerBoard('long_term', d.tracker.long_term, {
        label: document.getElementById('stm-long-label'),
        pct: document.getElementById('stm-long-coverage-pct'),
        bar: document.getElementById('stm-long-coverage-bar'),
        detail: document.getElementById('stm-long-coverage-detail'),
        matrix: el.matrixLong
      });
    });
  }

  function renderTrackerBoard(termKey, board, refs) {
    if (!board || !refs.matrix) return;
    if (refs.label) refs.label.textContent = (board.label_te || board.label_en || termKey) + ' · ' + board.schedule_days + ' రోజులు';
    var pct = board.coverage_percent || 0;
    if (refs.pct) refs.pct.textContent = pct + '%';
    if (refs.bar) refs.bar.style.width = pct + '%';
    if (refs.detail) {
      refs.detail.textContent = (board.scheduled_topics || 0) + ' / ' + (board.total_topics || 0) + ' టాపిక్‌లు షెడ్యూల్';
    }
    refs.matrix.innerHTML = '';
    (board.slots || []).forEach(function (slot) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'stm-slot stm-slot--' + (slot.status || 'pending');
      btn.title = slot.label + ' · ' + (slot.status || '');
      btn.textContent = slot.day_index;
      btn.dataset.dayId = slot.day_id || '';
      btn.dataset.dayIndex = slot.day_index;
      if (parseInt(slot.day_id, 10) === state.activeDayId) btn.classList.add('is-selected');
      btn.addEventListener('click', function () {
        selectMatrixSlot(btn, slot);
      });
      btn.addEventListener('mouseenter', function () {
        if (slot.day_id) previewDay(parseInt(slot.day_id, 10));
      });
      refs.matrix.appendChild(btn);
    });
  }

  function selectMatrixSlot(btn, slot) {
    document.querySelectorAll('.stm-slot').forEach(function (s) { s.classList.remove('is-selected'); });
    btn.classList.add('is-selected');
    if (slot.day_id) {
      loadDay(parseInt(slot.day_id, 10));
      previewDay(parseInt(slot.day_id, 10));
    } else {
      state.activeDayId = 0;
      el.dayMeta.classList.remove('hidden');
      el.dayIndex.value = slot.day_index;
      el.scheduleDate.value = '';
      el.dayTitle.textContent = 'రోజు ' + slot.day_index;
      state.dirtyShort = false;
      state.dirtyLong = false;
      clearDayEditor();
      loadDualWorkspace(true);
      showPendingPreview(slot.day_index);
    }
  }

  function showPendingPreview(dayIndex) {
    if (!el.previewBody) return;
    el.previewBody.innerHTML =
      '<p class="text-red-700 font-semibold mb-2">🔴 పెండింగ్ రోజు D' + dayIndex + '</p>' +
      '<p class="text-xs text-slate-600">ఈ రోజు ఇంకా షెడ్యూల్ చేయబడలేదు. విషయాలు జోడించి సేవ్ చేయండి.</p>';
    renderStagedUi();
  }

  function previewDay(dayId) {
    if (!dayId || !el.previewBody) return;
    state.previewDayId = dayId;
    el.previewBody.innerHTML = '<p class="text-slate-400 text-xs">లోడ్…</p>';
    get('day_preview', { day_id: dayId }).then(function (d) {
      if (!d.ok || !d.preview) return;
      renderPreviewPanel(d.preview);
    });
  }

  function renderPreviewPanel(p) {
    var html = '';
    var statusClass = p.status === 'complete' ? 'complete' : (p.status === 'missing_mcq' ? 'missing_mcq' : 'pending');
    html += '<p class="mb-2"><span class="stm-slot stm-slot--' + statusClass + ' inline-block !w-auto !h-auto px-2 py-0.5 text-[10px]">' +
      esc(statusClass) + '</span></p>';
    html += '<p class="text-xs text-slate-600 mb-2"><strong>రకం:</strong> ' + esc(p.schedule_type) + '<br><strong>రూటింగ్:</strong> ' + esc(p.routing) + '</p>';
    if (p.fatigue_warning) {
      html += '<div class="stm-fatigue-badge font-telugu">గమనిక: ప్రశ్నల సంఖ్య (' + p.total_questions + ') అభ్యర్థి రోజువారీ సామర్థ్యం కంటే ఎక్కువ ఉంది</div>';
    }
    html += '<div class="stm-lock-toggle"><label class="cursor-pointer flex items-center gap-2">' +
      '<input type="checkbox" id="stm-lock-cb" ' + (p.is_locked ? 'checked' : '') + ' /> 🔒 హోల్డ్ (విద్యార్థులకు దాచు)</label></div>';
    if ((p.rows || []).length) {
      html += '<table class="stm-preview-table font-telugu"><thead><tr><th>విషయం</th><th>టాపిక్‌లు</th><th>మార్కులు</th></tr></thead><tbody>';
      p.rows.forEach(function (r) {
        var topics = (r.topics || []).map(function (t) { return t.title; }).join(', ');
        html += '<tr><td>' + esc(r.subject_name_te || r.subject_name) + '</td><td>' + esc(topics) + '</td><td>' + (r.total_marks || 0) + '</td></tr>';
      });
      html += '</tbody></table>';
      html += '<p class="text-xs text-slate-500 mt-2">మొత్తం ప్రశ్నలు: <strong>' + (p.total_questions || 0) + '</strong></p>';
    } else {
      html += '<p class="text-xs text-amber-700">ఈ రోజుకు విషయాలు లేవు.</p>';
    }
    if (p.whatsapp && p.whatsapp.ok && p.has_valid_schedule) {
      html += '<a href="' + esc(p.whatsapp.url) + '" target="_blank" rel="noopener" class="admin-btn admin-btn--primary w-full mt-4 text-sm text-center block font-telugu">Share Schedule on WhatsApp</a>';
    }
    el.previewBody.innerHTML = html;
    renderStagedUi();
    var lockCb = document.getElementById('stm-lock-cb');
    if (lockCb) {
      lockCb.addEventListener('change', function () {
        post('toggle_lock', { day_id: state.previewDayId, locked: lockCb.checked ? 1 : 0 }).then(function () {
          previewDay(state.previewDayId);
          loadDualTracker();
          loadCalendar();
        });
      });
    }
  }

  function loadCalendar() {
    if (!el.month || state.subCourseId < 1) return;
    var p = el.month.value.split('-');
    return get('calendar', {
      sub_course_id: state.subCourseId,
      term_key: state.termKey,
      year: p[0],
      month: p[1]
    }).then(function (d) {
      if (d.ok) {
        state.calendarDays = d.days;
        renderCalendar();
      }
    });
  }

  function renderCalendar() {
    if (!el.calendar || !el.month) return;
    var parts = el.month.value.split('-');
    var y = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10);
    var first = new Date(y, m - 1, 1);
    var daysInMonth = new Date(y, m, 0).getDate();
    var startWd = first.getDay();
    var byDate = {};
    var byIdx = {};
    state.calendarDays.forEach(function (d) {
      if (d.schedule_date) byDate[d.schedule_date] = d;
      if (d.day_index) byIdx[d.day_index] = d;
    });
    el.calendar.innerHTML = '';
    ['ఆ','సో','మం','బు','గు','శు','శ'].forEach(function (lbl) {
      var h = document.createElement('div');
      h.className = 'text-center text-slate-400 font-semibold py-1';
      h.textContent = lbl;
      el.calendar.appendChild(h);
    });
    for (var i = 0; i < startWd; i++) {
      var blank = document.createElement('div');
      blank.className = 'stm-cell opacity-0 pointer-events-none';
      el.calendar.appendChild(blank);
    }
    for (var day = 1; day <= daysInMonth; day++) {
      var cell = document.createElement('button');
      cell.type = 'button';
      cell.className = 'stm-cell';
      var iso = y + '-' + String(m).padStart(2, '0') + '-' + String(day).padStart(2, '0');
      cell.textContent = String(day);
      var rec = state.plannerMode === 'date_wise' ? byDate[iso] : (byIdx[day] || byDate[iso]);
      if (rec) {
        var st = rec.status_color || rec.status || 'pending';
        cell.classList.add('stm-cell--' + st);
        var b = document.createElement('span');
        b.className = 'stm-badge stm-badge--' + st;
        b.textContent = rec.badge_short || ('D' + (rec.day_index || day));
        cell.appendChild(b);
        cell.dataset.dayId = rec.id;
      }
      (function (c, dateIso, dayNum) {
        c.addEventListener('click', function () {
          selectCalendarCell(c, dateIso, dayNum);
        });
        c.addEventListener('mouseenter', function () {
          var did = parseInt(c.dataset.dayId, 10);
          if (did) previewDay(did);
        });
      })(cell, iso, day);
      el.calendar.appendChild(cell);
    }
  }

  function selectCalendarCell(cell, iso, dayNum) {
    Array.prototype.forEach.call(el.calendar.querySelectorAll('.stm-cell'), function (c) {
      c.classList.remove('is-selected');
    });
    cell.classList.add('is-selected');
    var dayId = parseInt(cell.dataset.dayId, 10) || 0;
    if (dayId > 0) {
      loadDay(dayId);
      previewDay(dayId);
    } else {
      state.activeDayId = 0;
      el.dayMeta.classList.remove('hidden');
      if (state.plannerMode === 'date_wise') {
        el.scheduleDate.value = iso;
        el.dayIndex.value = '';
        el.dayTitle.textContent = 'తేదీ ' + iso;
      } else {
        el.dayIndex.value = dayNum;
        el.scheduleDate.value = '';
        el.dayTitle.textContent = 'రోజు ' + dayNum;
      }
      state.dirtyShort = false;
      state.dirtyLong = false;
      clearDayEditor();
      loadDualWorkspace(true);
      showPendingPreview(dayNum);
    }
  }

  function loadDay(dayId) {
    get('day', { day_id: dayId }).then(function (d) {
      if (!d.ok) return;
      state.activeDayId = dayId;
      var day = d.day;
      el.dayMeta.classList.remove('hidden');
      el.dayIndex.value = day.day_index || '';
      el.scheduleDate.value = day.schedule_date || '';
      el.dayTitle.textContent = day.title_te || ('రోజు ' + (day.day_index || day.schedule_date || ''));
      state.dirtyShort = false;
      state.dirtyLong = false;
      loadDualWorkspace(true);
      resetComposer();
      previewDay(dayId);
    });
  }

  function collectRows(termKey) {
    return stagedForTerm(termKey).map(function (r, idx) {
      return {
        id: r.id || null,
        subject_id: r.subject_id,
        topic_ids: r.topic_ids || [],
        total_marks: r.total_marks || 25,
        question_mode: r.question_mode || 'ai_pool',
        sort_order: idx + 1,
        ai_pool_count: r.ai_pool_count || r.total_marks || 25,
        pool_question_ids: r.pool_question_ids || [],
        external_mcq_text: r.external_mcq_text || '',
        row_meta: r.row_meta || { topic_label: r.topic_label || '' }
      };
    });
  }

  function updateTotalMarks() {
    var sum = 0;
    stagedForTerm(state.termKey).forEach(function (r) {
      sum += parseInt(r.total_marks, 10) || 0;
    });
    if (el.totalMarks) el.totalMarks.textContent = sum;
  }

  if (el.composerSubject) {
    el.composerSubject.addEventListener('change', function () {
      state.topicsRequestId++;
      loadTopicsForComposer();
      setComposerMsg('');
    });
  }

  if (el.addToList) {
    el.addToList.addEventListener('click', addToStagedList);
  }

  if (el.saveShort) {
    el.saveShort.addEventListener('click', function () { saveTermSchedule('short_term'); });
  }
  if (el.saveLong) {
    el.saveLong.addEventListener('click', function () { saveTermSchedule('long_term'); });
  }

  if (el.waDispatch) {
    el.waDispatch.addEventListener('click', function () {
      syncStagingFromDom(state.termKey);
      setMsg('WhatsApp సందేశం పంపబడుతోంది…');
      post('dispatch_whatsapp', {
        sub_course_id: state.subCourseId,
        short_day_id: state.shortDayId,
        long_day_id: state.longDayId,
        day_index: state.plannerMode === 'day_wise' ? parseInt(el.dayIndex.value, 10) : null,
        schedule_date: state.plannerMode === 'date_wise' ? el.scheduleDate.value : null,
        hold_short: el.holdShort && el.holdShort.checked ? 1 : 0,
        hold_long: el.holdLong && el.holdLong.checked ? 1 : 0
      }).then(function (d) {
        if (!d.ok) {
          setMsg(d.error || 'లోపం', true);
          return;
        }
        var gw = d.whatsapp && d.whatsapp.gateway;
        if (gw && gw.success) {
          setMsg('ఏకైక డైలీ ప్లాన్ WhatsApp గ్రూప్‌కు పంపబడింది ✓');
        } else {
          setMsg('సందేశం సిద్ధం — గేట్‌వే: ' + ((gw && gw.error) || 'చెక్ చేయండి'), true);
        }
        if (d.notification && d.notification.text && el.previewBody) {
          el.previewBody.innerHTML = '<pre class="text-xs whitespace-pre-wrap bg-slate-50 p-3 rounded-lg border">' +
            esc(d.notification.text) + '</pre>';
        }
        loadDualTracker();
        loadCalendar();
      });
    });
  }

  if (el.createTopic) {
    el.createTopic.addEventListener('click', function () {
      var sid = parseInt(el.composerSubject && el.composerSubject.value, 10) || 0;
      var title = el.customTopicTitle ? el.customTopicTitle.value.trim() : '';
      if (!sid || !title) {
        setComposerMsg('విషయం + టాపిక్ పేరు అవసరం', true);
        return;
      }
      setComposerMsg('టాపిక్ సృష్టిస్తోంది…');
      post('create_custom_topic', { subject_id: sid, title: title }).then(function (d) {
        if (!d.ok) {
          setComposerMsg(d.error || 'లోపం', true);
          return;
        }
        if (el.customTopicTitle) el.customTopicTitle.value = '';
        loadTopicsForComposer();
        setComposerMsg('✓ కస్టమ్ టాపిక్ సృష్టించబడింది');
      });
    });
  }

  if (el.previewWa) {
    el.previewWa.addEventListener('click', function () {
      get('compile_notification', {
        sub_course_id: state.subCourseId,
        day_index: state.plannerMode === 'day_wise' ? parseInt(el.dayIndex.value, 10) : '',
        schedule_date: state.plannerMode === 'date_wise' ? el.scheduleDate.value : ''
      }).then(function (d) {
        if (d.ok && d.notification && d.notification.text) {
          window.open('https://wa.me/?text=' + encodeURIComponent(d.notification.text), '_blank');
        }
      });
    });
  }

  ['stm-hybrid-close', 'stm-hybrid-cancel'].forEach(function (id) {
    var btn = document.getElementById(id);
    if (btn) btn.addEventListener('click', closeHybridModal);
  });
  var hybridApply = document.getElementById('stm-hybrid-apply');
  if (hybridApply) hybridApply.addEventListener('click', applyHybridModal);
  if (el.hybridSearch) {
    el.hybridSearch.addEventListener('input', function () {
      if (state.hybridEdit) {
        var row = stagedForTerm(state.hybridEdit.termKey)[state.hybridEdit.idx];
        if (row) loadHybridPool(row);
      }
    });
  }

  if (el.copyLayout) {
    el.copyLayout.addEventListener('click', function () {
      post('copy_layout', {
        target_day_id: state.activeDayId,
        source_day_id: 0
      }).then(function (d) {
        if (!d.ok || !d.layout) {
          setMsg('మునుపటి రోజు లేదు', true);
          return;
        }
        var copied = (d.layout || []).map(function (item) {
          var sub = subjectById(item.subject_id);
          return {
            id: null,
            subject_id: parseInt(item.subject_id, 10),
            subject_name: subjectLabel(sub),
            topic_ids: (item.topic_ids || []).map(function (x) { return parseInt(x, 10); }),
            topic_names: [],
            topic_label: '',
            total_marks: parseInt(item.total_marks, 10) || 25,
            question_mode: 'ai_pool',
            ai_pool_count: parseInt(item.total_marks, 10) || 25,
            pool_question_ids: [],
            external_mcq_text: '',
            row_meta: {}
          };
        });
        setStagedForTerm(state.termKey, copied);
        markDirty(state.termKey);
        resetComposer();
        renderActiveTermView();
        setMsg('లేఅవుట్ కాపీ అయ్యింది — టాపిక్ పేర్లు సేవ్ తర్వాత నవీకరించబడతాయి');
      });
    });
  }

  if (el.course) el.course.addEventListener('change', filterSubcourses);
  if (el.subcourse) el.subcourse.addEventListener('change', loadContext);
  if (el.term) el.term.addEventListener('change', onActiveTermChange);
  document.querySelectorAll('input[name="stm-mode"]').forEach(function (r) {
    r.addEventListener('change', function () {
      state.plannerMode = getMode();
      if (state.subCourseId) loadContext();
    });
  });
  if (el.month) {
    el.month.value = new Date().toISOString().slice(0, 7);
    el.month.addEventListener('change', loadCalendar);
  }

  filterSubcourses();
  renderActiveTermView();
})();
