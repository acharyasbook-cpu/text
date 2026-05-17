/**
 * Content Manager — Search-or-Create combobox for Subject / Topic / Sub-topic.
 */
(function (global) {
  'use strict';

  function esc(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function debounce(fn, ms) {
    var t;
    return function () {
      var args = arguments;
      var ctx = this;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(ctx, args); }, ms);
    };
  }

  /**
   * @param {object} cfg
   * @param {HTMLElement} cfg.mount
   * @param {HTMLSelectElement} cfg.selectEl
   * @param {string} cfg.type subject|topic|subtopic
   * @param {function():number} cfg.getParentId
   * @param {function(string):string} cfg.fetchUrl
   * @param {function(object):string} cfg.labelFn
   * @param {function(object):void} [cfg.onSelected]
   * @param {function():Promise<object>} [cfg.onCreate]
   * @param {function(string):void} [cfg.onStatus]
   */
  function CmSearchOrCreate(cfg) {
    this.cfg = cfg;
    this.mount = cfg.mount;
    this.selectEl = cfg.selectEl;
    this.items = [];
    this.open = false;
    this.disabled = true;
    this.highlight = -1;
    this._build();
    this._bind();
  }

  CmSearchOrCreate.prototype._build = function () {
    this.mount.classList.add('cm-soc');
    this.mount.innerHTML =
      '<div class="cm-soc-field">' +
      '  <input type="text" class="cm-soc-search cm-input font-telugu" autocomplete="off" spellcheck="false" disabled placeholder="ముందు పై స్థాయి ఎంచుకోండి…" />' +
      '  <button type="button" class="cm-soc-create" disabled title="క్రియేట్">+ క్రియేట్</button>' +
      '</div>' +
      '<ul class="cm-soc-list hidden" role="listbox"></ul>' +
      '<p class="cm-soc-hint text-[11px] text-slate-500 font-telugu mt-1 min-h-[1rem]"></p>';
    this.searchInput = this.mount.querySelector('.cm-soc-search');
    this.createBtn = this.mount.querySelector('.cm-soc-create');
    this.listEl = this.mount.querySelector('.cm-soc-list');
    this.hintEl = this.mount.querySelector('.cm-soc-hint');
    if (this.selectEl) {
      this.selectEl.classList.add('cm-soc-native-hidden');
      this.selectEl.setAttribute('aria-hidden', 'true');
      this.selectEl.tabIndex = -1;
    }
  };

  CmSearchOrCreate.prototype.setDisabled = function (on, reason) {
    this.disabled = !!on;
    this.searchInput.disabled = !!on;
    this.createBtn.disabled = !!on;
    if (on) {
      this.closeList();
      this.searchInput.value = '';
      this.searchInput.placeholder = reason || 'ముందు పై స్థాయి ఎంచుకోండి…';
      this.clearSelection(false);
    } else {
      this.searchInput.placeholder = this._placeholder();
    }
  };

  CmSearchOrCreate.prototype._placeholder = function () {
    var t = this.cfg.type;
    if (t === 'subject') return 'విషయం వెతకండి లేదా పేరు టైప్ చేయండి…';
    if (t === 'topic') return 'టాపిక్ వెతకండి…';
    return 'సబ్-టాపిక్ వెతకండి…';
  };

  CmSearchOrCreate.prototype.setStatus = function (msg) {
    if (this.hintEl) this.hintEl.textContent = msg || '';
    if (this.cfg.onStatus) this.cfg.onStatus(msg || '');
  };

  CmSearchOrCreate.prototype.clearSelection = function (dispatch) {
    if (this.selectEl) {
      this.selectEl.innerHTML = '<option value="">—</option>';
      this.selectEl.value = '';
      if (dispatch) this.selectEl.dispatchEvent(new Event('change', { bubbles: true }));
    }
    this.searchInput.value = '';
  };

  CmSearchOrCreate.prototype.ensureOption = function (id, label) {
    if (!this.selectEl) return;
    var sid = String(id);
    var found = false;
    for (var i = 0; i < this.selectEl.options.length; i++) {
      if (this.selectEl.options[i].value === sid) {
        found = true;
        this.selectEl.options[i].textContent = label;
        break;
      }
    }
    if (!found) {
      var opt = document.createElement('option');
      opt.value = sid;
      opt.textContent = label;
      this.selectEl.appendChild(opt);
    }
    this.selectEl.value = sid;
  };

  CmSearchOrCreate.prototype.selectItem = function (item) {
    var self = this;
    var id = item.id || item.topic_id || item.sub_topic_id;
    var label = this.cfg.labelFn(item);
    var finish = function () {
      self.ensureOption(id, label);
      self.searchInput.value = label;
      self.closeList();
      self.setStatus('');
      if (self.selectEl) {
        self.selectEl.dispatchEvent(new Event('change', { bubbles: true }));
      }
    };
    if (this.cfg.onSelected) {
      Promise.resolve(this.cfg.onSelected(item)).then(finish).catch(function (e) {
        self.setStatus(e.message || 'దోషం');
      });
    } else {
      finish();
    }
  };

  CmSearchOrCreate.prototype.closeList = function () {
    this.open = false;
    this.listEl.classList.add('hidden');
    this.highlight = -1;
  };

  CmSearchOrCreate.prototype.openList = function () {
    this.open = true;
    this.listEl.classList.remove('hidden');
  };

  CmSearchOrCreate.prototype.renderList = function () {
    var self = this;
    if (!this.items.length) {
      this.listEl.innerHTML = '<li class="cm-soc-empty font-telugu">ఫలితాలు లేవు — + క్రియేట్ నొక్కండి</li>';
      this.openList();
      return;
    }
    var html = '';
    this.items.forEach(function (it, idx) {
      var badge = '';
      if (self.cfg.type === 'subject' && parseInt(it.is_linked, 10) === 1) {
        badge = '<span class="cm-soc-badge">లింక్</span>';
      }
      html += '<li class="cm-soc-item' + (idx === self.highlight ? ' is-active' : '') + '" data-idx="' + idx + '" role="option">' +
        '<span class="cm-soc-item-label font-telugu">' + esc(self.cfg.labelFn(it)) + '</span>' + badge + '</li>';
    });
    this.listEl.innerHTML = html;
    this.openList();
    this.listEl.querySelectorAll('.cm-soc-item').forEach(function (row) {
      row.addEventListener('mousedown', function (e) {
        e.preventDefault();
        var i = parseInt(row.getAttribute('data-idx'), 10);
        if (!isNaN(i) && self.items[i]) self.selectItem(self.items[i]);
      });
    });
  };

  CmSearchOrCreate.prototype.load = function (q) {
    var self = this;
    if (this.disabled) return Promise.resolve();
    var parentId = this.cfg.getParentId();
    if (!parentId) {
      this.setDisabled(true);
      return Promise.resolve();
    }
    var url = this.cfg.fetchUrl(q || '');
    this.setStatus('లోడ్…');
    return this.cfg.fetchJson(url).then(function (d) {
      if (!d.ok) throw new Error(d.error || 'Load failed');
      self.items = d.items || [];
      self.renderList();
      self.setStatus(self.items.length ? self.items.length + ' ఫలితాలు' : 'ఫలితాలు లేవు');
    }).catch(function (e) {
      self.setStatus(e.message || 'లోడ్ విఫలం');
      self.items = [];
      self.listEl.innerHTML = '';
      self.closeList();
    });
  };

  CmSearchOrCreate.prototype.reload = function () {
    return this.load(this.searchInput.value.trim());
  };

  CmSearchOrCreate.prototype.runCreate = function () {
    var self = this;
    var name = this.searchInput.value.trim();
    if (!name) {
      this.setStatus('పేరు టైప్ చేయండి');
      this.searchInput.focus();
      return;
    }
    if (!this.cfg.onCreate) return;
    this.createBtn.disabled = true;
    this.setStatus('సేవ్…');
    this.cfg.onCreate(name).then(function (item) {
      self.createBtn.disabled = self.disabled;
      if (item) self.selectItem(item);
      return self.reload();
    }).catch(function (e) {
      self.createBtn.disabled = self.disabled;
      self.setStatus(e.message || 'సేవ్ విఫలం');
    });
  };

  CmSearchOrCreate.prototype._bind = function () {
    var self = this;
    var debouncedSearch = debounce(function () {
      self.load(self.searchInput.value.trim());
    }, 220);

    this.searchInput.addEventListener('focus', function () {
      if (!self.disabled) self.load(self.searchInput.value.trim());
    });
    this.searchInput.addEventListener('input', debouncedSearch);
    this.searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!self.open) self.load(self.searchInput.value.trim());
        self.highlight = Math.min(self.highlight + 1, self.items.length - 1);
        self.renderList();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        self.highlight = Math.max(self.highlight - 1, 0);
        self.renderList();
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (self.highlight >= 0 && self.items[self.highlight]) {
          self.selectItem(self.items[self.highlight]);
        } else if (self.searchInput.value.trim()) {
          self.runCreate();
        }
      } else if (e.key === 'Escape') {
        self.closeList();
      }
    });

    this.createBtn.addEventListener('click', function () {
      self.runCreate();
    });

    document.addEventListener('click', function (e) {
      if (!self.mount.contains(e.target)) self.closeList();
    });
  };

  CmSearchOrCreate.prototype.syncFromSelect = function () {
    if (!this.selectEl || !this.selectEl.value) {
      this.searchInput.value = '';
      return;
    }
    var opt = this.selectEl.options[this.selectEl.selectedIndex];
    if (opt) this.searchInput.value = opt.textContent || '';
  };

  CmSearchOrCreate.prototype.setItemsFromSelect = function () {
    if (!this.selectEl) return;
    var items = [];
    for (var i = 0; i < this.selectEl.options.length; i++) {
      var o = this.selectEl.options[i];
      if (!o.value) continue;
      items.push({ id: parseInt(o.value, 10), name: o.textContent, title: o.textContent });
    }
    this.items = items;
  };

  global.CmSearchOrCreate = CmSearchOrCreate;
})(typeof window !== 'undefined' ? window : global);
