document.addEventListener('DOMContentLoaded', function () {
  // Avoid adding twice
  if (document.getElementById('resources-fab-button')) return;

  var fab = document.createElement('a');
  fab.id = 'resources-fab-button';
  fab.href = 'resources.php';
  fab.setAttribute('aria-label', 'Club resources');

  // Basic styles (no Tailwind dependency)
  fab.style.position = 'fixed';
  fab.style.right = '16px';
  fab.style.bottom = '20px';
  fab.style.zIndex = '9999';
  fab.style.display = 'inline-flex';
  fab.style.alignItems = 'center';
  fab.style.justifyContent = 'center';
  fab.style.width = '54px';
  fab.style.height = '54px';
  fab.style.borderRadius = '9999px';
  fab.style.background = 'linear-gradient(135deg, #006838, #0b8748, #dcbb26)';
  fab.style.boxShadow = '0 10px 18px rgba(0,0,0,0.25)';
  fab.style.color = '#ffffff';
  fab.style.textDecoration = 'none';
  fab.style.fontFamily = 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
  fab.style.fontSize = '11px';
  fab.style.fontWeight = '600';
  fab.style.letterSpacing = '0.03em';
  fab.style.textTransform = 'uppercase';
  fab.style.cursor = 'pointer';
  fab.style.transition = 'transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease';

  // Hover effect
  fab.addEventListener('mouseenter', function () {
    fab.style.transform = 'translateY(-2px) scale(1.03)';
    fab.style.boxShadow = '0 14px 22px rgba(0,0,0,0.32)';
  });
  fab.addEventListener('mouseleave', function () {
    fab.style.transform = 'translateY(0) scale(1)';
    fab.style.boxShadow = '0 10px 18px rgba(0,0,0,0.25)';
  });

  // Inner label
  var label = document.createElement('span');
  label.textContent = 'Resources';
  fab.appendChild(label);

  // Optional small badge icon
  var dot = document.createElement('span');
  dot.style.position = 'absolute';
  dot.style.top = '8px';
  dot.style.right = '8px';
  dot.style.width = '7px';
  dot.style.height = '7px';
  dot.style.borderRadius = '9999px';
  dot.style.backgroundColor = '#ffffff';
  dot.style.opacity = '0.9';
  fab.appendChild(dot);

  document.body.appendChild(fab);
});
