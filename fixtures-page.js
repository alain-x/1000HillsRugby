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

      var submitNow = debounce(function () {
        form.submit();
      }, 150);

      form.querySelectorAll('select').forEach(function (sel) {
        sel.addEventListener('change', submitNow);
      });
    }
  });
})();
