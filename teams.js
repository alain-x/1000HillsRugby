(function () {
  function initLightbox() {
    const lightbox = document.getElementById('imageLightbox');
    const lightboxImg = document.getElementById('imageLightboxImg');
    const closeBtn = document.getElementById('imageLightboxClose');
    if (!lightbox || !lightboxImg) return;

    function open(src, alt) {
      if (!src) return;
      lightboxImg.src = src;
      lightboxImg.alt = alt || '';
      lightbox.classList.remove('hidden');
      lightbox.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function close() {
      lightbox.classList.add('hidden');
      lightbox.setAttribute('aria-hidden', 'true');
      lightboxImg.src = '';
      lightboxImg.alt = '';
      document.body.style.overflow = '';
    }

    document.addEventListener('click', function (e) {
      const img = e.target && e.target.closest ? e.target.closest('img.js-detail-image') : null;
      if (!img) return;
      e.preventDefault();
      const src = img.getAttribute('data-full-src') || img.getAttribute('src') || '';
      open(src, img.getAttribute('alt') || '');
    });

    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        close();
      });
    }

    lightbox.addEventListener('click', function (e) {
      // close when clicking outside the image
      const content = e.target && e.target.closest ? e.target.closest('.image-lightbox-content') : null;
      if (!content) {
        close();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !lightbox.classList.contains('hidden')) {
        close();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLightbox);
  } else {
    initLightbox();
  }
})();
