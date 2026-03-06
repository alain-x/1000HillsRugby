(function () {
  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  // Backward-compatible helper (in case any page still uses inline handlers)
  window.toggleDropdown = function (menuId) {
    var dropdown = document.getElementById(menuId);
    if (!dropdown) return;
    dropdown.classList.toggle('hidden');
  };

  onReady(function () {
    // Preset amount buttons -> update amount input
    var amountInput = document.getElementById('donate-amount');
    var currencySelect = document.querySelector('select[name="currency"]');

    function formatPresetLabel(currency, amount) {
      if (currency === 'RWF') {
        if (amount >= 1000 && amount % 1000 === 0) return String(amount / 1000) + 'k';
        return String(amount);
      }
      return String(amount);
    }

    function getPresetsForCurrency(currency) {
      switch ((currency || 'RWF').toUpperCase()) {
        case 'USD':
        case 'EUR':
          return [5, 10, 25, 50];
        case 'GBP':
          return [5, 10, 20, 50];
        case 'KES':
          return [200, 500, 1000, 2000];
        case 'UGX':
          return [5000, 10000, 20000, 50000];
        case 'TZS':
          return [2000, 5000, 10000, 20000];
        case 'RWF':
        default:
          return [2000, 5000, 10000, 20000];
      }
    }

    function setPresetButtons(currency) {
      var buttons = document.querySelectorAll('button[data-amount]');
      if (!buttons || buttons.length === 0) return;

      var oldPresetValues = [];
      for (var i = 0; i < buttons.length; i++) {
        var oldVal = buttons[i].getAttribute('data-amount');
        if (oldVal) oldPresetValues.push(String(oldVal));
      }

      var presets = getPresetsForCurrency(currency);
      for (var j = 0; j < buttons.length; j++) {
        var v = presets[j] != null ? presets[j] : presets[presets.length - 1];
        buttons[j].setAttribute('data-amount', String(v));
        buttons[j].textContent = formatPresetLabel((currency || 'RWF').toUpperCase(), v);
      }

      // Only update the amount input if user was using preset values (avoid overwriting custom entry)
      if (amountInput) {
        var current = String(amountInput.value || '');
        var wasPreset = oldPresetValues.indexOf(current) !== -1;
        if (wasPreset) {
          amountInput.value = String(presets[1] != null ? presets[1] : presets[0]);
          try {
            amountInput.dispatchEvent(new Event('input', { bubbles: true }));
            amountInput.dispatchEvent(new Event('change', { bubbles: true }));
          } catch (_) {}
        }
      }
    }

    if (currencySelect) {
      setPresetButtons(currencySelect.value);
      currencySelect.addEventListener('change', function (e) {
        setPresetButtons(e.target.value);
      });
    }

    if (amountInput) {
      var buttons = document.querySelectorAll('button[data-amount]');
      for (var i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', function (e) {
          e.preventDefault();
          var v = e.currentTarget.getAttribute('data-amount');
          if (!v) return;

          amountInput.value = v;
          // Trigger any listeners/validation UI
          try {
            amountInput.dispatchEvent(new Event('input', { bubbles: true }));
            amountInput.dispatchEvent(new Event('change', { bubbles: true }));
          } catch (_) {}

          amountInput.focus();
        });
      }
    }

    // CSP-safe dropdown binding (no inline onclick)
    var dropdownToggles = document.querySelectorAll('[data-dropdown-toggle]');
    for (var j = 0; j < dropdownToggles.length; j++) {
      dropdownToggles[j].addEventListener('click', function (e) {
        e.preventDefault();
        var id = e.currentTarget.getAttribute('data-dropdown-toggle');
        if (!id) return;
        window.toggleDropdown(id);
      });
    }

    // Mobile menu icon swap
    var menuToggle = document.getElementById('menu-toggle');
    var openIcon = document.getElementById('menu-open-icon');
    var closeIcon = document.getElementById('menu-close-icon');
    if (menuToggle && openIcon && closeIcon) {
      function syncIcons() {
        if (menuToggle.checked) {
          openIcon.classList.add('hidden');
          closeIcon.classList.remove('hidden');
        } else {
          openIcon.classList.remove('hidden');
          closeIcon.classList.add('hidden');
        }
      }

      menuToggle.addEventListener('change', syncIcons);
      syncIcons();
    }
  });
})();
