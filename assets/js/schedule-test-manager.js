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
    calendarDays: [],
    stagedRows: [],
    topicsRequestId: 0,
    tracker: null,
    previewDayId: 0
  };

  var el = {
    course: document.getElementById('stm-course'),
    subcourse: document.getElementById('stm-subcourse'),
    term: document.getElementById('stm-term'),
    month: document.getElementById('stm-month'),
    calendar: document.getElementById('stm-calendar'),
    saveDay: document.getElementById('stm-save-day'),
    copyLayout: document.getElementById('stm-copy-layout'),
    totalMarks: document.getElementById('stm-total-marks'),
    stagedCount: document.getElementById('stm-staged-count'),
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
    stagedPreview: document.getElementById('stm-staged-preview'),
    stagedPreviewBody: document.getElementById('stm-staged-preview-body'),
    previewDivider: document.getElementById('stm-preview-divider'),
    composerSubject: document.getElementById('stm-composer-subject'),
    composerTopics: document.getElementById('stm-composer-topics'),
    composerMarks: document.getElementById('stm-composer-marks'),
    addToList: document.getElementById('stm-add-to-list'),
    composerMsg: document.getElementById('stm-composer-msg'),
    stagedList: document.getElementById('stm-staged-list')
  };

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
        lbl.innerHTML = '<input type="checkbox" class="stm-composer-topic-cb" value="' + id + '" />' +
          '<span>' + esc(t.title) + '</span>';
        el.composerTopics.appendChild(lbl);
      });
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

  function addToStagedList() {
    var sel = getComposerSelection();
    if (!sel.subject_id) {
      setComposerMsg('విషయం ఎంచుకోండి', true);
      return;
    }
    if (!sel.topic_ids.length) {
      setComposerMsg('కనీసం ఒక టాపిక్ ఎంచుకోండి', true);
      return;
    }
    state.stagedRows.push({
      id: null,
      subject_id: sel.subject_id,
      subject_name: sel.subject_name,
      topic_ids: sel.topic_ids,
      topic_names: sel.topic_names,
      total_marks: sel.total_marks,
      question_mode: 'ai_pool',
      ai_pool_count: sel.total_marks
    });
    resetComposer();
    setComposerMsg('✓ జాబితాకు జోడించబడింది. తదుపరి విషయం ఎంచుకోండి.');
    renderStagedUi();
  }

  function removeStagedRow(index) {
    state.stagedRows.splice(index, 1);
    renderStagedUi();
  }

  function stagedTableHtml() {
    if (!state.stagedRows.length) {
      return '<p class="text-xs text-slate-500 font-telugu">ఇంకా విషయాలు జోడించలేదు.</p>';
    }
    var html = '<table class="stm-preview-table font-telugu"><thead><tr><th>#</th><th>విషయం</th><th>టాపిక్‌లు</th><th>మార్కులు</th></tr></thead><tbody>';
    state.stagedRows.forEach(function (r, i) {
      html += '<tr><td>' + (i + 1) + '</td><td>' + esc(r.subject_name) + '</td><td>' +
        esc((r.topic_names || []).join(', ')) + '</td><td>' + (r.total_marks || 0) + '</td></tr>';
    });
    html += '</tbody></table>';
    return html;
  }

  function renderStagedUi() {
    updateTotalMarks();
    if (el.stagedCount) el.stagedCount.textContent = String(state.stagedRows.length);

    if (el.stagedPreview && el.stagedPreviewBody) {
      var has = state.stagedRows.length > 0;
      el.stagedPreview.classList.toggle('hidden', !has);
      if (el.previewDivider) el.previewDivider.classList.toggle('hidden', !has);
      el.stagedPreviewBody.innerHTML = stagedTableHtml();
    }

    if (el.stagedList) {
      el.stagedList.innerHTML = '';
      if (!state.stagedRows.length) {
        el.stagedList.classList.add('hidden');
        return;
      }
      el.stagedList.classList.remove('hidden');
      state.stagedRows.forEach(function (r, idx) {
        var card = document.createElement('div');
        card.className = 'flex justify-between items-start gap-2 border border-slate-200 rounded-lg p-2 bg-white text-xs font-telugu';
        card.innerHTML =
          '<div><strong>' + esc(r.subject_name) + '</strong><br><span class="text-slate-600">' +
          esc((r.topic_names || []).join(', ')) + '</span><br><span class="text-brand">' +
          (r.total_marks || 0) + ' మార్కులు</span></div>' +
          '<button type="button" class="stm-staged-remove text-red-600 shrink-0">తొలగించు</button>';
        card.querySelector('.stm-staged-remove').addEventListener('click', function () {
          removeStagedRow(idx);
        });
        el.stagedList.appendChild(card);
      });
    }
  }

  function stagedRowsFromServer(rows) {
    return (rows || []).map(function (r) {
      var topics = r.topics || [];
      return {
        id: r.id ? parseInt(r.id, 10) : null,
        subject_id: parseInt(r.subject_id, 10),
        subject_name: (r.subject_name_te || r.subject_name || ''),
        topic_ids: (r.topic_ids || []).map(function (x) { return parseInt(x, 10); }),
        topic_names: topics.map(function (t) { return t.title || ''; }),
        total_marks: parseInt(r.total_marks, 10) || 25,
        question_mode: r.question_mode || 'ai_pool',
        ai_pool_count: parseInt(r.total_marks, 10) || 25
      };
    });
  }

  function clearDayEditor() {
    state.stagedRows = [];
    resetComposer();
    renderStagedUi();
  }

  function loadContext() {
    state.subCourseId = parseInt(el.subcourse.value, 10) || 0;
    state.termKey = el.term.value;
    state.plannerMode = getMode();
    if (state.subCourseId < 1) {
      setMsg('సబ్-కోర్స్ ఎంచుకోండి');
      return;
    }
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
      if (el.saveDay) el.saveDay.disabled = false;
      if (el.copyLayout) el.copyLayout.disabled = false;
      loadDualTracker();
      setMsg('రోజు ఎంచుకోండి — విషయం/టాపిక్‌లు జోడించి సేవ్ చేయండి');
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
      clearDayEditor();
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
      clearDayEditor();
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
      state.stagedRows = stagedRowsFromServer(d.rows || []);
      resetComposer();
      renderStagedUi();
      previewDay(dayId);
    });
  }

  function collectRows() {
    return state.stagedRows.map(function (r, idx) {
      return {
        id: r.id || null,
        subject_id: r.subject_id,
        topic_ids: r.topic_ids || [],
        total_marks: r.total_marks || 25,
        question_mode: r.question_mode || 'ai_pool',
        sort_order: idx + 1,
        ai_pool_count: r.ai_pool_count || r.total_marks || 25
      };
    });
  }

  function updateTotalMarks() {
    var sum = 0;
    state.stagedRows.forEach(function (r) {
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

  if (el.saveDay) {
    el.saveDay.addEventListener('click', function () {
      if (!state.stagedRows.length) {
        setMsg('కనీసం ఒక విషయం/టాపిక్ జాబితాకు జోడించండి', true);
        return;
      }
      var payload = {
        id: state.activeDayId || null,
        sub_course_id: state.subCourseId,
        term_key: state.termKey,
        day_index: state.plannerMode === 'day_wise' ? parseInt(el.dayIndex.value, 10) : null,
        schedule_date: state.plannerMode === 'date_wise' ? el.scheduleDate.value : null,
        rows: collectRows()
      };
      setMsg('సేవ్ అవుతోంది… (బహుళ విషయాలు ఒకే అభ్యర్థనలో)');
      post('save_day', payload).then(function (d) {
        if (d.ok) {
          state.activeDayId = d.day_id;
          state.stagedRows = stagedRowsFromServer(d.rows || []);
          renderStagedUi();
          setMsg('షెడ్యూల్ సేవ్ అయ్యింది');
          loadContext();
          loadDualTracker();
          loadDay(d.day_id);
        } else setMsg(d.error || 'లోపం', true);
      });
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
        state.stagedRows = (d.layout || []).map(function (item) {
          var sub = subjectById(item.subject_id);
          return {
            id: null,
            subject_id: parseInt(item.subject_id, 10),
            subject_name: subjectLabel(sub),
            topic_ids: (item.topic_ids || []).map(function (x) { return parseInt(x, 10); }),
            topic_names: [],
            total_marks: parseInt(item.total_marks, 10) || 25,
            question_mode: 'ai_pool',
            ai_pool_count: parseInt(item.total_marks, 10) || 25
          };
        });
        resetComposer();
        renderStagedUi();
        setMsg('లేఅవుట్ కాపీ అయ్యింది — టాపిక్ పేర్లు సేవ్ తర్వాత నవీకరించబడతాయి');
      });
    });
  }

  if (el.course) el.course.addEventListener('change', filterSubcourses);
  if (el.subcourse) el.subcourse.addEventListener('change', loadContext);
  if (el.term) el.term.addEventListener('change', loadContext);
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
  renderStagedUi();
})();
