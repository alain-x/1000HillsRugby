(function () {
  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
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
  });
})();
