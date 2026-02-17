document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('donate-fab-button')) return;

  var CONFIG = {
    brandName: '1000 Hills Rugby',
    primaryColor: '#006838',
    accentColor: '#dcbb26',
    buttonText: 'Donate',
    // Update these URLs/details to match your real payment setup
    donationOptions: {
      individual: [
        {
          id: 'flutterwave',
          title: 'Card / Mobile Money',
          description: 'Pay securely using card or mobile money.',
          type: 'link',
          href: './checkouts.html'
        },
        {
          id: 'bank',
          title: 'Bank Transfer',
          description: 'Use your bank app to transfer directly.',
          type: 'details',
          detailsHtml:
            '<div style="display:grid;gap:10px">' +
            '<div style="display:grid;grid-template-columns:140px 1fr;gap:8px">' +
            '<div style="color:#6b7280">Account name</div><div style="font-weight:600">1000 Hills Rugby</div>' +
            '<div style="color:#6b7280">Bank</div><div style="font-weight:600"> </div>' +
            '<div style="color:#6b7280">Account number</div><div style="font-weight:600">  </div>' +
            '<div style="color:#6b7280">Reference</div><div style="font-weight:600">Donation</div>' +
            '</div>' +
            '<div style="font-size:12px;color:#6b7280">After paying, you can email proof to <strong>info@1000hillsrugby.rw</strong>.</div>' +
            '</div>'
        }
      ],
      organisation: [
        {
          id: 'partnership',
          title: 'Corporate / Partnership Support',
          description: 'Sponsor a program or make a corporate contribution.',
          type: 'details',
          detailsHtml:
            '<div style="display:grid;gap:10px">' +
            '<div style="font-size:14px">For organisation donations, please contact us so we can share an invoice and bank details.</div>' +
            '<div style="display:grid;gap:6px">' +
            '<a style="text-decoration:none" href="mailto:info@1000hillsrugby.rw"><span style="font-weight:700">Email:</span> info@1000hillsrugby.rw</a>' +
            '<a style="text-decoration:none" href="tel:+250788261386"><span style="font-weight:700">Phone:</span> +250 788 261 386</a>' +
            '</div>' +
            '</div>'
        },
        {
          id: 'bank_org',
          title: 'Bank Transfer (Organisation)',
          description: 'Transfer from your company account.',
          type: 'details',
          detailsHtml:
            '<div style="display:grid;gap:10px">' +
            '<div style="display:grid;grid-template-columns:140px 1fr;gap:8px">' +
            '<div style="color:#6b7280">Account name</div><div style="font-weight:600">1000 Hills Rugby</div>' +
            '<div style="color:#6b7280">Bank</div><div style="font-weight:600">      </div>' +
            '<div style="color:#6b7280">Account number</div><div style="font-weight:600">      </div>' +
            '<div style="color:#6b7280">Reference</div><div style="font-weight:600">Corporate donation</div>' +
            '</div>' +
            '</div>'
        }
      ]
    }
  };

  function esc(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function injectStyles() {
    if (document.getElementById('donate-widget-styles')) return;

    var css =
      ':root{--donate-primary:' +
      CONFIG.primaryColor +
      ';--donate-accent:' +
      CONFIG.accentColor +
      ';}' +
      '#donate-fab-button{position:fixed;right:16px;top:96px;z-index:9999;display:inline-flex;align-items:center;gap:10px;justify-content:center;padding:0 16px;min-width:120px;height:46px;border-radius:9999px;background:linear-gradient(135deg,var(--donate-primary),#0b8748,var(--donate-accent));box-shadow:0 10px 18px rgba(0,0,0,.25);color:#fff;text-decoration:none;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;cursor:pointer;border:none;transition:transform .18s ease,box-shadow .18s ease,opacity .18s ease}' +
      '#donate-fab-button:hover{transform:translateY(-2px) scale(1.03);box-shadow:0 14px 22px rgba(0,0,0,.32)}' +
      '#donate-fab-icon{width:18px;height:18px;display:inline-block}' +
      '#donate-modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.62);backdrop-filter:blur(3px);z-index:10000;display:none;align-items:center;justify-content:center;padding:18px}' +
      '#donate-modal{width:min(860px,100%);background:#fff;border-radius:16px;box-shadow:0 25px 60px rgba(0,0,0,.35);overflow:hidden}' +
      '#donate-modal header{display:flex;align-items:center;justify-content:space-between;padding:18px 18px 12px 18px;border-bottom:1px solid #eef2f7}' +
      '#donate-modal-title{font-size:18px;font-weight:800;color:#111827}' +
      '#donate-modal-subtitle{font-size:13px;color:#6b7280;margin-top:2px}' +
      '#donate-modal-close{border:none;background:transparent;font-size:18px;line-height:1;cursor:pointer;color:#6b7280;padding:8px;border-radius:10px}' +
      '#donate-modal-close:hover{background:#f3f4f6;color:#111827}' +
      '#donate-modal-body{padding:18px}' +
      '.donate-step{display:none}' +
      '.donate-step[data-active="true"]{display:block}' +
      '.donate-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}' +
      '@media(max-width:720px){.donate-grid{grid-template-columns:1fr}}' +
      '.donate-choice{border:1px solid #e5e7eb;border-radius:14px;padding:14px;cursor:pointer;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;transition:transform .15s ease,box-shadow .15s ease,border-color .15s ease;background:#fff}' +
      '.donate-choice:hover{transform:translateY(-1px);box-shadow:0 12px 22px rgba(17,24,39,.08);border-color:#d1d5db}' +
      '.donate-choice h3{margin:0;font-size:14px;font-weight:800;color:#111827}' +
      '.donate-choice p{margin:6px 0 0 0;font-size:13px;color:#6b7280;line-height:1.4}' +
      '.donate-badge{display:inline-flex;align-items:center;justify-content:center;border-radius:9999px;padding:6px 10px;font-size:11px;font-weight:800;background:rgba(0,104,56,.10);color:var(--donate-primary)}' +
      '.donate-actions{display:flex;gap:10px;align-items:center;justify-content:flex-end;margin-top:14px}' +
      '.donate-btn{border:none;border-radius:12px;padding:10px 14px;font-weight:800;cursor:pointer;font-size:13px}' +
      '.donate-btn-primary{background:var(--donate-primary);color:#fff}' +
      '.donate-btn-primary:hover{filter:brightness(.95)}' +
      '.donate-btn-ghost{background:#f3f4f6;color:#111827}' +
      '.donate-btn-ghost:hover{background:#e5e7eb}' +
      '.donate-methods{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}' +
      '@media(max-width:720px){.donate-methods{grid-template-columns:1fr}}' +
      '.donate-method{border:1px solid #e5e7eb;border-radius:14px;padding:14px;cursor:pointer;background:#fff;display:flex;gap:12px;align-items:flex-start;transition:transform .15s ease,box-shadow .15s ease,border-color .15s ease}' +
      '.donate-method:hover{transform:translateY(-1px);box-shadow:0 12px 22px rgba(17,24,39,.08);border-color:#d1d5db}' +
      '.donate-method-icon{width:36px;height:36px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:rgba(220,187,38,.18);color:#111827;font-weight:900;font-size:14px;flex:0 0 auto}' +
      '.donate-method-title{margin:0;font-size:14px;font-weight:900;color:#111827}' +
      '.donate-method-desc{margin:6px 0 0 0;font-size:13px;color:#6b7280;line-height:1.4}' +
      '.donate-divider{height:1px;background:#eef2f7;margin:14px 0}' +
      '.donate-details{border:1px solid #e5e7eb;border-radius:14px;padding:14px;background:#fff}' +
      '.donate-details h3{margin:0 0 6px 0;font-size:14px;font-weight:900;color:#111827}' +
      '.donate-details .donate-details-body{font-size:13px;color:#111827;line-height:1.5}' +
      '.donate-kicker{font-size:12px;color:#6b7280;margin:0 0 10px 0}';

    var style = document.createElement('style');
    style.id = 'donate-widget-styles';
    style.textContent = css;
    document.head.appendChild(style);
  }

  function createFab() {
    var btn = document.createElement('button');
    btn.id = 'donate-fab-button';
    btn.type = 'button';
    btn.setAttribute('aria-label', 'Donate');

    var icon = document.createElement('span');
    icon.id = 'donate-fab-icon';
    icon.innerHTML =
      '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg">' +
      '<path d="M12 21s-7-4.35-9.33-8.21C.93 9.86 2.34 6.5 5.83 6.5c1.94 0 3.33 1.12 4.17 2.2.84-1.08 2.23-2.2 4.17-2.2 3.49 0 4.9 3.36 3.16 6.29C19 16.65 12 21 12 21Z" stroke="white" stroke-width="2" stroke-linejoin="round"/>' +
      '</svg>';

    var label = document.createElement('span');
    label.textContent = CONFIG.buttonText;

    btn.appendChild(icon);
    btn.appendChild(label);

    btn.addEventListener('click', openModal);

    document.body.appendChild(btn);
  }

  function createModal() {
    var backdrop = document.createElement('div');
    backdrop.id = 'donate-modal-backdrop';
    backdrop.setAttribute('role', 'dialog');
    backdrop.setAttribute('aria-modal', 'true');
    backdrop.setAttribute('aria-label', 'Donation options');

    backdrop.innerHTML =
      '<div id="donate-modal">' +
      '<header>' +
      '<div>' +
      '<div id="donate-modal-title">Support ' +
      esc(CONFIG.brandName) +
      '</div>' +
      '<div id="donate-modal-subtitle">Choose a donation type, then select a payment method.</div>' +
      '</div>' +
      '<button id="donate-modal-close" type="button" aria-label="Close">✕</button>' +
      '</header>' +
      '<div id="donate-modal-body">' +
      '<div class="donate-step" id="donate-step-type" data-active="true">' +
      '<p class="donate-kicker">How would you like to donate?</p>' +
      '<div class="donate-grid">' +
      '<div class="donate-choice" data-donate-type="individual">' +
      '<div><h3>As an individual</h3><p>Quick payments using card/mobile money, or bank transfer.</p></div>' +
      '<span class="donate-badge">Individual</span>' +
      '</div>' +
      '<div class="donate-choice" data-donate-type="organisation">' +
      '<div><h3>As an organisation</h3><p>Corporate giving, sponsorships, invoices, and bank transfer.</p></div>' +
      '<span class="donate-badge">Organisation</span>' +
      '</div>' +
      '</div>' +
      '</div>' +
      '<div class="donate-step" id="donate-step-methods" data-active="false">' +
      '<div style="display:flex;align-items:center;justify-content:space-between;gap:12px">' +
      '<div>' +
      '<div style="font-size:12px;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.06em">Payment methods</div>' +
      '<div id="donate-methods-title" style="font-size:16px;font-weight:900;color:#111827;margin-top:3px">—</div>' +
      '</div>' +
      '<button class="donate-btn donate-btn-ghost" type="button" id="donate-back-to-type">Back</button>' +
      '</div>' +
      '<div class="donate-divider"></div>' +
      '<div class="donate-methods" id="donate-methods-list"></div>' +
      '</div>' +
      '<div class="donate-step" id="donate-step-details" data-active="false">' +
      '<div style="display:flex;align-items:center;justify-content:space-between;gap:12px">' +
      '<div>' +
      '<div style="font-size:12px;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.06em">Payment method</div>' +
      '<div id="donate-details-title" style="font-size:16px;font-weight:900;color:#111827;margin-top:3px">—</div>' +
      '</div>' +
      '<button class="donate-btn donate-btn-ghost" type="button" id="donate-back-to-methods">Back</button>' +
      '</div>' +
      '<div class="donate-divider"></div>' +
      '<div class="donate-details">' +
      '<h3 id="donate-details-heading"></h3>' +
      '<div class="donate-details-body" id="donate-details-body"></div>' +
      '</div>' +
      '<div class="donate-actions">' +
      '<button class="donate-btn donate-btn-ghost" type="button" id="donate-close">Close</button>' +
      '</div>' +
      '</div>' +
      '</div>' +
      '</div>';

    document.body.appendChild(backdrop);

    backdrop.addEventListener('click', function (e) {
      if (e.target === backdrop) closeModal();
    });

    var closeBtn = backdrop.querySelector('#donate-modal-close');
    closeBtn.addEventListener('click', closeModal);

    var closeBtn2 = backdrop.querySelector('#donate-close');
    closeBtn2.addEventListener('click', closeModal);

    backdrop.querySelector('#donate-back-to-type').addEventListener('click', function () {
      setActiveStep('type');
    });

    backdrop.querySelector('#donate-back-to-methods').addEventListener('click', function () {
      setActiveStep('methods');
    });

    Array.prototype.forEach.call(backdrop.querySelectorAll('[data-donate-type]'), function (el) {
      el.addEventListener('click', function () {
        var donateType = el.getAttribute('data-donate-type');
        showMethods(donateType);
      });
    });

    document.addEventListener('keydown', function (e) {
      var isOpen = backdrop.style.display === 'flex';
      if (!isOpen) return;
      if (e.key === 'Escape') closeModal();
    });
  }

  function setActiveStep(step) {
    var backdrop = document.getElementById('donate-modal-backdrop');
    if (!backdrop) return;

    var typeStep = backdrop.querySelector('#donate-step-type');
    var methodsStep = backdrop.querySelector('#donate-step-methods');
    var detailsStep = backdrop.querySelector('#donate-step-details');

    typeStep.setAttribute('data-active', step === 'type' ? 'true' : 'false');
    methodsStep.setAttribute('data-active', step === 'methods' ? 'true' : 'false');
    detailsStep.setAttribute('data-active', step === 'details' ? 'true' : 'false');
  }

  function showMethods(donateType) {
    var backdrop = document.getElementById('donate-modal-backdrop');
    if (!backdrop) return;

    var titleEl = backdrop.querySelector('#donate-methods-title');
    titleEl.textContent = donateType === 'individual' ? 'Individual donation' : 'Organisation donation';

    var list = backdrop.querySelector('#donate-methods-list');
    list.innerHTML = '';

    var methods = (CONFIG.donationOptions && CONFIG.donationOptions[donateType]) || [];

    methods.forEach(function (m) {
      var card = document.createElement('div');
      card.className = 'donate-method';
      card.setAttribute('role', 'button');
      card.setAttribute('tabindex', '0');
      card.setAttribute('data-method-id', m.id);

      var icon = document.createElement('div');
      icon.className = 'donate-method-icon';
      icon.textContent = (m.title || 'Pay').slice(0, 1).toUpperCase();

      var content = document.createElement('div');
      var h = document.createElement('h4');
      h.className = 'donate-method-title';
      h.textContent = m.title;
      var p = document.createElement('p');
      p.className = 'donate-method-desc';
      p.textContent = m.description || '';

      content.appendChild(h);
      content.appendChild(p);

      card.appendChild(icon);
      card.appendChild(content);

      function openMethod() {
        if (m.type === 'link' && m.href) {
          window.open(m.href, '_blank', 'noopener,noreferrer');
          return;
        }
        showDetails(m);
      }

      card.addEventListener('click', openMethod);
      card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          openMethod();
        }
      });

      list.appendChild(card);
    });

    setActiveStep('methods');
  }

  function showDetails(method) {
    var backdrop = document.getElementById('donate-modal-backdrop');
    if (!backdrop) return;

    backdrop.querySelector('#donate-details-title').textContent = method.title || 'Payment method';
    backdrop.querySelector('#donate-details-heading').textContent = method.title || 'Payment method';

    var body = backdrop.querySelector('#donate-details-body');
    if (method.detailsHtml) {
      body.innerHTML = method.detailsHtml;
    } else {
      body.textContent = 'Details coming soon.';
    }

    setActiveStep('details');
  }

  function openModal() {
    var backdrop = document.getElementById('donate-modal-backdrop');
    if (!backdrop) return;

    setActiveStep('type');
    backdrop.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    var closeBtn = backdrop.querySelector('#donate-modal-close');
    if (closeBtn) closeBtn.focus();
  }

  function closeModal() {
    var backdrop = document.getElementById('donate-modal-backdrop');
    if (!backdrop) return;

    backdrop.style.display = 'none';
    document.body.style.overflow = '';
  }

  injectStyles();
  createModal();
  createFab();
});
