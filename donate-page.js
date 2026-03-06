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
