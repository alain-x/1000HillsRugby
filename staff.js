function onReady(fn) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fn);
  } else {
    fn();
  }
}

onReady(function () {
  // Dropdown toggles (mobile menu sections)
  document.querySelectorAll('[data-dropdown-target]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetId = btn.getAttribute('data-dropdown-target');
      if (!targetId) return;
      var dropdown = document.getElementById(targetId);
      if (!dropdown) return;
      dropdown.classList.toggle('hidden');
    });
  });

  // Toggle Menu Visibility
  var menuToggle = document.getElementById('menu-toggle');
  var menu = document.getElementById('menu');
  var openIcon = document.getElementById('menu-open-icon');
  var closeIcon = document.getElementById('menu-close-icon');

  if (menuToggle && menu && openIcon && closeIcon) {
    menuToggle.addEventListener('change', function () {
      if (this.checked) {
        menu.classList.remove('hidden');
        openIcon.classList.add('hidden');
        closeIcon.classList.remove('hidden');
      } else {
        menu.classList.add('hidden');
        openIcon.classList.remove('hidden');
        closeIcon.classList.add('hidden');
      }
    });
  }
});
