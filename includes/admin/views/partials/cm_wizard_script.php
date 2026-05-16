<?php
/** Linear wizard engine — included inside content_manager.php main script. */
?>
  /* ── Linear Content Wizard (cascade ordering) ── */
  var wizardRoot = document.getElementById('cmWizard');
  if (wizardRoot) {

  var reorderApi = wizardRoot.getAttribute('data-reorder-api') || '';
  var sortMsg = document.getElementById('cmSortMsg');
  var wizardHint = document.getElementById('cmWizardHint');
  var wizardProgress = document.getElementById('cmWizardProgress');
  var wizardActiveTopicLabel = document.getElementById('cmWizardActiveTopicLabel');
  var wizardOpenContentTab = document.getElementById('cmWizardOpenContentTab');

  var wizardLists = {
    sub_course: document.getElementById('cmWizardListSub'),
    subject: document.getElementById('cmWizardListSubject'),
    topic: document.getElementById('cmWizardListTopic'),
    sub_topic: document.getElementById('cmWizardListSubTopic'),
    exam_suite: document.getElementById('cmWizardListExam'),
  };

  var wizardStages = {
    sub_course: document.getElementById('cmWizardStageSub'),
    subject: document.getElementById('cmWizardStageSubject'),
    topic: document.getElementById('cmWizardStageTopic'),
    sub_topic: document.getElementById('cmWizardStageSubTopic'),
    notes_exam: document.getElementById('cmWizardStageNotesExam'),
  };

  var wizardState = {
    current: null,
    unlocked: {},
    skipSubTopic: false,
    activeTopicId: 0,
  };

  var WIZARD_STORAGE_KEY = 'cmWizardState_v1';

  function wizardMsg(text, isErr) {
    if (!sortMsg) return;
    sortMsg.textContent = text || '';
    sortMsg.style.color = isErr ? '#b91c1c' : '#047857';
  }

  function wizardCtx() {
    return {
      course_id: parseInt(el.main.value, 10) || 0,
      sub_course_id: parseInt(el.sub.value, 10) || 0,
      subject_id: parseInt(el.subject.value, 10) || 0,
      topic_id: parseInt(el.topic.value, 10) || wizardState.activeTopicId || 0,
    };
  }

  function persistWizardState() {
    try {
      sessionStorage.setItem(WIZARD_STORAGE_KEY, JSON.stringify({
        main: el.main.value,
        sub: el.sub.value,
        subject: el.subject.value,
        topic: el.topic.value,
        current: wizardState.current,
        skipSubTopic: wizardState.skipSubTopic,
        activeTopicId: wizardState.activeTopicId,
      }));
    } catch (e) { /* ignore */ }
  }

  function wizardProgressUpdate() {
    if (!wizardProgress) return;
    wizardProgress.querySelectorAll('.cm-wizard-step').forEach(function (step) {
      var key = step.getAttribute('data-step');
      step.classList.remove('is-active', 'is-done', 'is-skipped');
      if (key === 'sub_topic' && wizardState.skipSubTopic) {
        step.classList.add('is-skipped');
        return;
      }
      if (wizardState.current === key) step.classList.add('is-active');
      else if (wizardState.unlocked[key]) step.classList.add('is-done');
    });
  }

  function setWizardStage(stage) {
    wizardState.current = stage;
    Object.keys(wizardStages).forEach(function (key) {
      var node = wizardStages[key];
      if (!node) return;
      node.classList.remove('is-active', 'is-complete', 'is-locked');
      if (key === stage) {
        node.classList.add('is-active');
        wizardState.unlocked[key] = true;
      } else if (wizardState.unlocked[key]) {
        node.classList.add('is-complete');
      } else {
        node.classList.add('is-locked');
      }
    });
    if (wizardHint) wizardHint.classList.add('is-hidden');
    wizardProgressUpdate();
    persistWizardState();
    var activeNode = wizardStages[stage];
    if (activeNode && activeNode.scrollIntoView) {
      setTimeout(function () { activeNode.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }, 80);
    }
  }

  function resetWizard() {
    wizardState.current = null;
    wizardState.unlocked = {};
    wizardState.skipSubTopic = false;
    wizardState.activeTopicId = 0;
    Object.keys(wizardStages).forEach(function (key) {
      var node = wizardStages[key];
      if (!node) return;
      node.classList.remove('is-active', 'is-complete');
      node.classList.add('is-locked');
    });
    if (wizardHint) wizardHint.classList.remove('is-hidden');
    wizardProgressUpdate();
    Object.keys(wizardLists).forEach(function (k) {
      var ul = wizardLists[k];
      if (ul) ul.innerHTML = '';
    });
  }

  function sortLabel(row, entity) {
    if (entity === 'topic') return row.title_te || row.title || ('#' + row.id);
    if (entity === 'sub_topic') return row.name_te || row.name || row.sub_topic_name || ('#' + row.id);
    if (entity === 'exam_suite') return row.name_te || row.name || row.suite_key || ('#' + row.id);
    return row.name_te || row.name || ('#' + row.id);
  }

  function collectOrderedIds(ul) {
    if (!ul) return [];
    return Array.prototype.map.call(ul.querySelectorAll('.cm-sort-row'), function (row) {
      return parseInt(row.dataset.id, 10);
    }).filter(function (id) { return id > 0; });
  }

  function localMoveRow(ul, row, direction) {
    var sib = direction === 'up' ? row.previousElementSibling : row.nextElementSibling;
    if (!sib || !sib.classList.contains('cm-sort-row')) return;
    if (direction === 'up') ul.insertBefore(row, sib);
    else ul.insertBefore(sib, row);
  }

  function localDragReorder(ul, dragId, targetId) {
    var dragRow = ul.querySelector('.cm-sort-row[data-id="' + dragId + '"]');
    var targetRow = ul.querySelector('.cm-sort-row[data-id="' + targetId + '"]');
    if (!dragRow || !targetRow || dragRow === targetRow) return;
    ul.insertBefore(dragRow, targetRow);
  }

  function bindWizardList(ul, entity) {
    var dragId = null;
    ul.querySelectorAll('.cm-sort-row').forEach(function (row) {
      row.addEventListener('dragstart', function (e) {
        dragId = row.dataset.id;
        row.classList.add('is-dragging');
        if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
      });
      row.addEventListener('dragend', function () {
        row.classList.remove('is-dragging');
        dragId = null;
      });
      row.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
      });
      row.addEventListener('drop', function (e) {
        e.preventDefault();
        if (!dragId || dragId === row.dataset.id) return;
        localDragReorder(ul, dragId, row.dataset.id);
      });
      row.querySelectorAll('.cm-sort-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          localMoveRow(ul, row, btn.getAttribute('data-dir'));
        });
      });
      row.addEventListener('click', function () {
        ul.querySelectorAll('.cm-sort-row').forEach(function (r) { r.classList.remove('is-selected'); });
        row.classList.add('is-selected');
        if (entity === 'sub_course' && el.sub) el.sub.value = row.dataset.id;
        if (entity === 'subject' && el.subject) el.subject.value = row.dataset.id;
        if (entity === 'topic' && el.topic) {
          el.topic.value = row.dataset.id;
          wizardState.activeTopicId = parseInt(row.dataset.id, 10) || 0;
        }
        updateContextSummary();
      });
    });
  }

  function renderWizardList(entity, items) {
    var ul = wizardLists[entity];
    if (!ul) return;
    ul.innerHTML = '';
    if (!items || !items.length) {
      ul.innerHTML = '<li class="cm-sort-empty font-telugu">అంశాలు లేవు</li>';
      return;
    }
    items.forEach(function (row, idx) {
      var li = document.createElement('li');
      li.className = 'cm-sort-row';
      li.draggable = true;
      li.dataset.id = String(row.id);
      li.dataset.entity = entity;
      li.dataset.index = String(idx);
      li.innerHTML =
        '<span class="cm-sort-handle" title="లాగండి">⋮⋮</span>' +
        '<span class="cm-sort-label">' + esc(sortLabel(row, entity)) + '</span>' +
        '<button type="button" class="cm-sort-btn" data-dir="up" title="పైకి">▲</button>' +
        '<button type="button" class="cm-sort-btn" data-dir="down" title="క్రిందికి">▼</button>';
      ul.appendChild(li);
    });
    bindWizardList(ul, entity);
  }

  function fetchWizardList(entity) {
    var ctx = wizardCtx();
    var q = 'action=sort_list&entity=' + encodeURIComponent(entity)
      + '&course_id=' + (ctx.course_id || '')
      + '&sub_course_id=' + (ctx.sub_course_id || '')
      + '&subject_id=' + (ctx.subject_id || '')
      + '&topic_id=' + (ctx.topic_id || '');
    return fetchJson(api + '?' + q).then(function (d) {
      if (d.ok) renderWizardList(entity, d.items || []);
      return d.items || [];
    });
  }

  function postWizardCommit(entity, orderedIds) {
    var ctx = wizardCtx();
    return postJson({
      action: 'wizard_commit',
      entity: entity,
      ordered_ids: orderedIds,
      course_id: ctx.course_id,
      sub_course_id: ctx.sub_course_id,
      subject_id: ctx.subject_id,
      topic_id: ctx.topic_id,
    });
  }

  function ensureSelectFirst(selectEl, afterLoadFn) {
    if (!selectEl || selectEl.value) return Promise.resolve();
    if (selectEl.options.length > 1) {
      selectEl.selectedIndex = 1;
      selectEl.dispatchEvent(new Event('change', { bubbles: true }));
    }
    return afterLoadFn ? afterLoadFn() : Promise.resolve();
  }

  function syncCascadeAfterStage(stage) {
    if (stage === 'subject' && el.main.value) {
      return fetchJson(api + '?action=sub_courses&course_id=' + el.main.value).then(function (d) {
        if (d.ok) fillSelect(el.sub, d.items, 'id', function (s) { return s.name_te || s.name; });
        return ensureSelectFirst(el.sub);
      });
    }
    if (stage === 'topic' && el.sub.value) {
      return fetchJson(api + '?action=subjects&sub_course_id=' + el.sub.value).then(function (d) {
        if (d.ok) fillSelect(el.subject, d.items, 'id', function (s) { return s.name_te || s.name; });
        return ensureSelectFirst(el.subject);
      });
    }
    if (stage === 'sub_topic' || stage === 'notes_exam') {
      return fetchJson(api + '?action=topics&subject_id=' + el.subject.value).then(function (d) {
        if (d.ok) {
          fillSelect(el.topic, d.items, 'id', function (t) { return t.title_te || t.title; });
          if (!el.topic.value && d.items && d.items[0]) {
            el.topic.value = String(d.items[0].id);
            wizardState.activeTopicId = parseInt(d.items[0].id, 10);
          } else {
            wizardState.activeTopicId = parseInt(el.topic.value, 10) || 0;
          }
        }
        return Promise.resolve();
      });
    }
    return Promise.resolve();
  }

  function updateWizardTopicLabel() {
    if (!wizardActiveTopicLabel) return;
    var tid = wizardState.activeTopicId || parseInt(el.topic.value, 10) || 0;
    if (!tid) {
      wizardActiveTopicLabel.textContent = '';
      return;
    }
    var opt = el.topic && el.topic.selectedOptions && el.topic.selectedOptions[0];
    wizardActiveTopicLabel.textContent = opt
      ? 'సక్రియ టాపిక్: ' + opt.textContent
      : 'సక్రియ టాపిక్ ID: ' + tid;
  }

  function openNotesExamEditor() {
    var tabBtn = document.querySelector('.cm-tab-btn[data-cm-tab="content"]');
    if (tabBtn) tabBtn.click();
    var tid = wizardState.activeTopicId || parseInt(el.topic.value, 10) || 0;
    if (tid && el.topic) {
      el.topic.value = String(tid);
      if (typeof loadTopicConfig === 'function') loadTopicConfig(tid);
    }
    wizardMsg('నోట్స్ & ఎగ్జామ్ ఎడిటర్ తెరవబడింది — పై కాంటెక్స్ట్ బార్‌లో టాపిక్ ఎంపిక చూడండి.', false);
  }

  function advanceWizard(nextStage, payload) {
    payload = payload || {};
    if (payload.skip_sub_topic) wizardState.skipSubTopic = true;
    if (payload.topic_id) {
      wizardState.activeTopicId = parseInt(payload.topic_id, 10) || 0;
      if (el.topic && wizardState.activeTopicId) el.topic.value = String(wizardState.activeTopicId);
    }

    return syncCascadeAfterStage(nextStage).then(function () {
      if (nextStage === 'subject') {
        setWizardStage('subject');
        return fetchWizardList('subject');
      }
      if (nextStage === 'topic') {
        setWizardStage('topic');
        return fetchWizardList('topic');
      }
      if (nextStage === 'sub_topic') {
        wizardState.skipSubTopic = false;
        setWizardStage('sub_topic');
        return fetchWizardList('sub_topic');
      }
      if (nextStage === 'notes_exam') {
        setWizardStage('notes_exam');
        updateWizardTopicLabel();
        return fetchWizardList('exam_suite').then(function () {
          if (typeof loadTopicConfig === 'function' && wizardState.activeTopicId) {
            loadTopicConfig(wizardState.activeTopicId);
          }
        });
      }
      if (nextStage === 'complete') {
        wizardState.unlocked.notes_exam = true;
        wizardStages.notes_exam && wizardStages.notes_exam.classList.add('is-complete');
        wizardMsg('✓ విజార్డ్ పూర్తయింది — అన్ని క్రమాలు డేటాబేస్‌లో సేవ్ అయ్యాయి.', false);
        persistWizardState();
        return;
      }
    });
  }

  function onWizardSave(entity) {
    var ul = wizardLists[entity];
    if (!ul) return;
    var ids = collectOrderedIds(ul);
    var btn = wizardRoot.querySelector('[data-wizard-commit="' + entity + '"]');
    if (btn) btn.disabled = true;
    wizardMsg('సేవ్ అవుతోంది…', false);

    if (entity === 'sub_course' && el.sub && !el.sub.value && ids.length) {
      el.sub.value = String(ids[0]);
    }
    if (entity === 'subject' && el.subject && !el.subject.value && ids.length) {
      el.subject.value = String(ids[0]);
    }
    if (entity === 'topic') {
      if (!wizardState.activeTopicId && ids.length) {
        wizardState.activeTopicId = ids[0];
      }
      if (el.topic && wizardState.activeTopicId) {
        el.topic.value = String(wizardState.activeTopicId);
      }
    }

    postWizardCommit(entity, ids).then(function (d) {
      if (!d.ok) throw new Error(d.error || 'Save failed');
      if (d.items) renderWizardList(entity, d.items);
      wizardMsg('✓ ' + entity + ' క్రమం సేవ్ అయింది', false);
      if (d.skip_sub_topic) wizardState.skipSubTopic = true;
      if (entity === 'sub_course' && typeof window.cmOpenScheduleTab === 'function') {
        window.cmOpenScheduleTab();
      }
      return advanceWizard(d.next_stage || 'subject', d);
    }).catch(function (err) {
      wizardMsg(err.message || 'సేవ్ విఫలమైంది', true);
    }).finally(function () {
      if (btn) btn.disabled = false;
    });
  }

  function bootWizardFromMain() {
    var cid = parseInt(el.main.value, 10) || 0;
    if (cid < 1) {
      resetWizard();
      wizardMsg('ముందుగా మెయిన్ కోర్స్ ఎంచుకోండి.', true);
      return;
    }
    resetWizard();
    wizardState.unlocked.sub_course = true;
    setWizardStage('sub_course');
    wizardMsg('సబ్ కోర్స్‌లు లోడ్ అవుతున్నాయి…', false);
    fetchWizardList('sub_course').then(function (items) {
      if (items.length && el.sub && !el.sub.value) {
        el.sub.value = String(items[0].id);
        el.sub.disabled = false;
      }
      wizardMsg('దశ 1: సబ్ కోర్స్ క్రమం సరిచూసి సేవ్ చేయండి.', false);
      updateContextSummary();
    });
  }

  wizardRoot.querySelectorAll('[data-wizard-commit]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      onWizardSave(btn.getAttribute('data-wizard-commit'));
    });
  });

  if (wizardOpenContentTab) {
    wizardOpenContentTab.addEventListener('click', openNotesExamEditor);
  }

  el.sub.addEventListener('change', function () {
    if (wizardState.unlocked.subject) fetchWizardList('subject');
    updateContextSummary();
  });
  el.subject.addEventListener('change', function () {
    if (wizardState.unlocked.topic) fetchWizardList('topic');
    wizardState.activeTopicId = parseInt(el.topic.value, 10) || 0;
    updateContextSummary();
  });
  el.topic.addEventListener('change', function () {
    wizardState.activeTopicId = parseInt(el.topic.value, 10) || 0;
    if (wizardState.current === 'notes_exam') {
      fetchWizardList('exam_suite');
      updateWizardTopicLabel();
      if (wizardState.activeTopicId && typeof loadTopicConfig === 'function') {
        loadTopicConfig(wizardState.activeTopicId);
      }
    }
    updateContextSummary();
  });

  window.cmRefreshWizard = function () {
    if (!wizardState.current) return;
    var map = { sub_course: 'sub_course', subject: 'subject', topic: 'topic', sub_topic: 'sub_topic', notes_exam: 'exam_suite' };
    var ent = map[wizardState.current];
    if (ent) fetchWizardList(ent);
  };

  window.bootWizardFromMain = bootWizardFromMain;
  window.cmResetWizard = resetWizard;
  } /* end wizardRoot */
