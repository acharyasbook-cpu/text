/**
 * Freemium lock → Razorpay checkout overlay (or demo checkout fallback).
 */
(function () {
  'use strict';

  var modal = document.getElementById('freemiumCheckoutModal');
  if (!modal) return;

  var keyId = modal.getAttribute('data-razorpay-key') || '';
  var orderUrl = modal.getAttribute('data-order-url') || '';
  var verifyUrl = modal.getAttribute('data-verify-url') || '';
  var checkoutReturn = modal.getAttribute('data-checkout-return') || '';
  var statusEl = document.getElementById('freemiumModalStatus');
  var razorpayScriptLoaded = false;

  function setStatus(msg) {
    if (statusEl) statusEl.textContent = msg || '';
  }

  function openModal() {
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('freemium-modal-open');
    setStatus('');
  }

  function closeModal() {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('freemium-modal-open');
  }

  function loadRazorpay(cb) {
    if (window.Razorpay) {
      cb();
      return;
    }
    if (razorpayScriptLoaded) {
      var t = setInterval(function () {
        if (window.Razorpay) {
          clearInterval(t);
          cb();
        }
      }, 80);
      return;
    }
    razorpayScriptLoaded = true;
    var s = document.createElement('script');
    s.src = 'https://checkout.razorpay.com/v1/checkout.js';
    s.onload = cb;
    s.onerror = function () {
      setStatus('Razorpay లోడ్ కాలేదు — డెమో చెక్కౌట్ ఉపయోగించండి.');
    };
    document.head.appendChild(s);
  }

  function showFallbackForms() {
    modal.querySelectorAll('.freemium-fallback-form').forEach(function (f) {
      f.classList.remove('hidden');
    });
  }

  function postJson(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: JSON.stringify(body),
    }).then(function (r) { return r.json(); });
  }

  function startRazorpay(planId, amountPaise) {
    if (!keyId) {
      showFallbackForms();
      setStatus('Razorpay కాన్ఫిగర్ కాలేదు — క్రింద డెమో చెక్కౌట్.');
      return;
    }
    setStatus('ఆర్డర్ సృష్టిస్తోంది…');
    postJson(orderUrl, { plan_id: planId, return: checkoutReturn })
      .then(function (data) {
        if (!data.ok) {
          if (data.fallback) {
            showFallbackForms();
            setStatus(data.error || 'డెమో చెక్కౌట్ ఉపయోగించండి.');
            return;
          }
          throw new Error(data.error || 'Order failed');
        }
        loadRazorpay(function () {
          var opts = {
            key: data.key_id || keyId,
            amount: data.amount || amountPaise,
            currency: data.currency || 'INR',
            name: 'ఆచార్య బుక్స్',
            description: data.description || 'Sub-course access',
            order_id: data.order_id,
            handler: function (resp) {
              setStatus('ధృవీకరిస్తోంది…');
              postJson(verifyUrl, {
                plan_id: planId,
                return: checkoutReturn,
                razorpay_payment_id: resp.razorpay_payment_id,
                razorpay_order_id: resp.razorpay_order_id,
                razorpay_signature: resp.razorpay_signature,
              }).then(function (v) {
                if (v.ok && v.redirect) {
                  window.location.href = v.redirect;
                  return;
                }
                setStatus(v.error || 'Verification failed');
              });
            },
            modal: { ondismiss: function () { setStatus('చెల్లింపు రద్దు'); } },
            theme: { color: '#1e3a8a' },
          };
          var rzp = new window.Razorpay(opts);
          rzp.on('payment.failed', function () {
            setStatus('చెల్లింపు విఫలమైంది');
          });
          rzp.open();
          closeModal();
        });
      })
      .catch(function (e) {
        showFallbackForms();
        setStatus(e.message || 'ఆర్డర్ లోపం');
      });
  }

  document.addEventListener('click', function (e) {
    var locked = e.target.closest('.freemium-locked-cta');
    if (locked) {
      e.preventDefault();
      openModal();
      return;
    }
    if (e.target.closest('[data-freemium-close]')) {
      closeModal();
    }
  });

  modal.querySelectorAll('.freemium-plan-pay').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var planId = parseInt(btn.getAttribute('data-plan-id'), 10);
      var card = btn.closest('.freemium-plan-card');
      var paise = card ? parseInt(card.getAttribute('data-amount-paise'), 10) : 0;
      if (!planId) return;
      startRazorpay(planId, paise);
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) closeModal();
  });
})();
