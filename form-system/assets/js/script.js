// General form handling
document.addEventListener("DOMContentLoaded", function () {
  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Handle form submissions with confirmation
  const forms = document.querySelectorAll("form[data-confirm]");
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!confirm(this.getAttribute("data-confirm"))) {
        e.preventDefault();
      }
    });
  });

  // Auto-focus first form field
  const firstField = document.querySelector(
    'form input[type="text"], form input[type="email"], form input[type="password"]'
  );
  if (firstField) {
    firstField.focus();
  }
});
