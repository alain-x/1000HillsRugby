// Basic admin UI interactions
(function () {
  // Confirm destructive actions
  document.addEventListener('click', function (e) {
    const el = e.target.closest('[data-confirm]');
    if (el) {
      const msg = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    }
  });

  // Auto-dismiss alerts
  setTimeout(() => {
    document.querySelectorAll('.alert').forEach((a) => {
      a.style.transition = 'opacity .4s';
      a.style.opacity = '0';
      setTimeout(() => a.remove(), 400);
    });
  }, 4000);
})();
