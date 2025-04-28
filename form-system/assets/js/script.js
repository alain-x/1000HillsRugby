// Add this right before your custom script to ensure Bootstrap is loaded
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>;

document.addEventListener("DOMContentLoaded", function () {
  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Handle view submission button clicks
  document.querySelectorAll(".view-submission").forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      const submissionId = this.getAttribute("data-id");
      console.log("Viewing submission:", submissionId); // Debug

      const modalBody = document.getElementById("submissionDetails");

      // Show loading state
      modalBody.innerHTML = `
                <div class="text-center my-5 py-4">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading submission details...</p>
                </div>
            `;

      // Initialize and show modal
      const modal = new bootstrap.Modal(
        document.getElementById("submissionModal")
      );
      modal.show();

      // Fetch submission details via AJAX
      fetch(`get_submission.php?id=${submissionId}`)
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
          }
          return response.json();
        })
        .then((data) => {
          console.log("Received data:", data); // Debug
          if (data.success) {
            let html = `
                            <div class="mb-4">
                                <h4>${data.submission.title}</h4>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge bg-info">
                                        <i class="fas fa-user"></i> ${
                                          data.submission.username || "Guest"
                                        }
                                    </span>
                                    ${
                                      data.submission.email
                                        ? `
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-envelope"></i> ${data.submission.email}
                                    </span>`
                                        : ""
                                    }
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-clock"></i> ${new Date(
                                          data.submission.submitted_at
                                        ).toLocaleString()}
                                    </span>
                                </div>
                            </div>
                            <hr>
                            <div class="submission-responses">
                        `;

            data.fields.forEach((field) => {
              // Handle both field.value and field.field_value for compatibility
              const fieldValue = field.value || field.field_value || "";

              if (field.file_path) {
                const fileExt = field.file_path.split(".").pop().toLowerCase();
                let icon = "fa-file";
                const fileIcons = {
                  jpg: "fa-file-image",
                  jpeg: "fa-file-image",
                  png: "fa-file-image",
                  gif: "fa-file-image",
                  pdf: "fa-file-pdf",
                  doc: "fa-file-word",
                  docx: "fa-file-word",
                  xls: "fa-file-excel",
                  xlsx: "fa-file-excel",
                };
                if (fileIcons[fileExt]) icon = fileIcons[fileExt];

                html += `
                                    <div class="mb-3 p-3 border rounded">
                                        <h6 class="fw-bold">${field.label}</h6>
                                        <div class="d-flex align-items-center gap-2 mt-2">
                                            <a href="../uploads/${
                                              field.file_path
                                            }" 
                                               target="_blank" 
                                               class="btn btn-sm btn-success">
                                                <i class="fas ${icon}"></i> Download File
                                            </a>
                                            <span class="small text-muted">${formatFileSize(
                                              field.file_size
                                            )}</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">${
                                          field.file_path
                                        }</small>
                                    </div>
                                `;
              } else {
                html += `
                                    <div class="mb-3 p-3 border rounded">
                                        <h6 class="fw-bold">${field.label}</h6>
                                        <div class="p-2 bg-light rounded mt-2">
                                            ${
                                              fieldValue
                                                ? fieldValue.replace(
                                                    /\n/g,
                                                    "<br>"
                                                  )
                                                : '<em class="text-muted">No response</em>'
                                            }
                                        </div>
                                    </div>
                                `;
              }
            });

            html += `</div>`;
            modalBody.innerHTML = html;
          } else {
            modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> 
                                ${data.message || "Error loading submission"}
                            </div>
                        `;
          }
        })
        .catch((error) => {
          console.error("Fetch error:", error);
          modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Failed to load submission. Please try again.
                            <div class="mt-2 small">Error: ${error.message}</div>
                        </div>
                    `;
        });
    });
  });

  function formatFileSize(bytes) {
    if (!bytes) return "";
    if (bytes < 1024) return bytes + " bytes";
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + " KB";
    return (bytes / 1048576).toFixed(1) + " MB";
  }
});
