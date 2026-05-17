/**
 * Acharya Books — secure note / exam anti-piracy layer.
 * Expects: data-watermark on .secure-viewport (optional; falls back to ఆచార్య బుక్స్).
 */
(function () {
  'use strict';

  var FALLBACK = 'ఆచార్య బుక్స్';
  var toastTimer;

  function isNativeSecure() {
    return window.__ACHARYA_NATIVE_SECURE__ === true
      || /AcharyaApp|AcharyaBooksApp/i.test(navigator.userAgent || '');
  }

  function showToast(msg) {
    var el = document.getElementById('secureShieldToast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'secureShieldToast';
      el.className = 'secure-shield-toast font-telugu';
      el.setAttribute('role', 'status');
      el.setAttribute('aria-live', 'polite');
      document.body.appendChild(el);
    }
    el.textContent = msg;
    el.classList.add('is-visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () {
      el.classList.remove('is-visible');
    }, 2200);
  }

  function blockEvent(e, msg) {
    e.preventDefault();
    e.stopPropagation();
    if (msg) showToast(msg);
    return false;
  }

  function isFormControl(target) {
    if (!target || !target.closest) return false;
    return !!target.closest('input, button, textarea, select, a, label, [role="button"]');
  }

  function onKeyDown(e) {
    if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
      blockEvent(e, 'స్క్రీన్ షాట్ నిషేధించబడింది — ఆచార్య బుక్స్');
      if (isNativeSecure()) {
        document.documentElement.classList.add('secure-capture-active');
        setTimeout(function () {
          document.documentElement.classList.remove('secure-capture-active');
        }, 1200);
      }
      return;
    }
    if (e.ctrlKey || e.metaKey) {
      var k = (e.key || '').toLowerCase();
      if (k === 'p' || k === 's' || k === 'u' || k === 'c' || k === 'a' || k === 'x') {
        if (!isFormControl(e.target) || k === 'p' || k === 's' || k === 'u') {
          blockEvent(e, k === 'p' ? 'ప్రింట్ నిషేధం — ఆచార్య బుక్స్' : 'ఈ చర్య అనుమతించబడదు');
        }
      }
    }
  }

  function onKeyUp(e) {
    if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
      blockEvent(e);
    }
  }

  function init() {
    var root = document.querySelector('.secure-viewport');
    if (!root) return;

    if (isNativeSecure()) {
      document.documentElement.classList.add('secure-native-mode');
    }

    document.addEventListener('contextmenu', function (e) {
      if (root.contains(e.target)) {
        blockEvent(e, 'రైట్-క్లిక్ నిషేధం');
      }
    }, true);

    document.addEventListener('copy', function (e) {
      if (root.contains(e.target)) blockEvent(e, 'కాపీ నిషేధం');
    }, true);

    document.addEventListener('cut', function (e) {
      if (root.contains(e.target)) blockEvent(e, 'కాపీ నిషేధం');
    }, true);

    document.addEventListener('selectstart', function (e) {
      if (root.contains(e.target) && !isFormControl(e.target)) {
        blockEvent(e);
      }
    }, true);

    document.addEventListener('dragstart', function (e) {
      if (root.contains(e.target)) blockEvent(e);
    }, true);

    document.addEventListener('keydown', onKeyDown, true);
    document.addEventListener('keyup', onKeyUp, true);

    window.addEventListener('beforeprint', function (e) {
      blockEvent(e, 'ప్రింట్ నిషేధం');
    });

    document.body.classList.add('secure-guard-active');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
