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
    var currencySelect = document.querySelector('select[name="currency"]');

    function getPresets(currency) {
      switch ((currency || '').toUpperCase()) {
        case 'USD':
          return [10, 25, 50, 100];
        case 'KES':
          return [500, 1000, 2500, 5000];
        case 'UGX':
          return [20000, 50000, 100000, 200000];
        case 'TZS':
          return [5000, 10000, 25000, 50000];
        case 'RWF':
        default:
          return [2000, 5000, 10000, 20000];
      }
    }

    function formatPresetLabel(currency, amount) {
      var c = (currency || '').toUpperCase();
      if (c === 'USD') return '$' + amount;

      // Use k-suffix for large numbers (local currencies)
      if (amount >= 1000 && amount % 1000 === 0) {
        return String(amount / 1000) + 'k';
      }
      return String(amount);
    }

    function applyCurrencyPresets(currency) {
      var presets = getPresets(currency);
      var presetButtons = document.querySelectorAll('button[data-amount]');
      for (var i = 0; i < presetButtons.length && i < presets.length; i++) {
        presetButtons[i].setAttribute('data-amount', String(presets[i]));
        presetButtons[i].textContent = formatPresetLabel(currency, presets[i]);
      }
      return presets;
    }

    // Preset amount buttons -> update amount input
    var amountInput = document.getElementById('donate-amount');
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

    // Currency change -> update presets + set a sensible default amount
    if (currencySelect && amountInput) {
      var initialPresets = applyCurrencyPresets(currencySelect.value);
      if (amountInput.value === '' || amountInput.value === '0') {
        amountInput.value = String(initialPresets[0]);
      }

      currencySelect.addEventListener('change', function () {
        var presets = applyCurrencyPresets(currencySelect.value);
        amountInput.value = String(presets[0]);
        try {
          amountInput.dispatchEvent(new Event('input', { bubbles: true }));
          amountInput.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (_) {}
      });
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
