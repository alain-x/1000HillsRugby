(function () {
  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  function debounce(fn, delayMs) {
    var t;
    return function () {
      var args = arguments;
      clearTimeout(t);
      t = setTimeout(function () {
        fn.apply(null, args);
      }, delayMs);
    };
  }

  onReady(function () {
    var button = document.getElementById('mobile-menu-button');
    var menu = document.getElementById('mobile-menu');

    if (button && menu) {
      button.addEventListener('click', function () {
        menu.classList.toggle('hidden');
      });

      menu.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
          menu.classList.add('hidden');
        });
      });
    }

    var form = document.getElementById('filtersForm');
    if (form) {
      var tabInput = form.querySelector('input[name="tab"]');
      if (tabInput) {
        var urlTab = new URLSearchParams(window.location.search).get('tab');
        if (urlTab) tabInput.value = urlTab;
      }

      var navigateWithFilters = debounce(function () {
        var params = new URLSearchParams(window.location.search);

        var tab = (tabInput && tabInput.value) ? tabInput.value : (params.get('tab') || 'fixtures');
        params.set('tab', tab);

        var seasonSel = form.querySelector('select[name="season"]');
        var compSel = form.querySelector('select[name="competition"]');
        var genderSel = form.querySelector('select[name="gender"]');

        if (seasonSel && seasonSel.value) params.set('season', seasonSel.value);
        if (compSel) params.set('competition', compSel.value || '');
        if (genderSel) params.set('gender', genderSel.value || '');

        window.location.href = window.location.pathname + '?' + params.toString();
      }, 150);

      form.querySelectorAll('select').forEach(function (sel) {
        sel.addEventListener('change', navigateWithFilters);
      });
    }
  });
})();
