// JS for news-detail page: handle copy-link button without inline JS (CSP safe)

function initCopyLinkButtons() {
  const buttons = document.querySelectorAll('.copy-link-btn');
  if (!buttons.length) return;

  buttons.forEach((btn) => {
    if (btn.__hasCopyHandler) return;
    btn.addEventListener('click', async () => {
      const url = btn.getAttribute('data-url') || window.location.href;
      try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(url);
        } else {
          const tempInput = document.createElement('input');
          tempInput.value = url;
          document.body.appendChild(tempInput);
          tempInput.select();
          document.execCommand('copy');
          document.body.removeChild(tempInput);
        }
        alert('Link copied to clipboard');
      } catch (err) {
        console.error('Failed to copy link:', err);
        alert('Could not copy the link. Please copy it manually from the address bar.');
      }
    });
    btn.__hasCopyHandler = true;
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initCopyLinkButtons);
} else {
  initCopyLinkButtons();
}
