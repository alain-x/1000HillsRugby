document.addEventListener("DOMContentLoaded", function () {
  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Form Builder Functionality
  document.addEventListener("DOMContentLoaded", function () {
    const formFieldsContainer = document.getElementById("formFieldsContainer");
    const fieldSettingsModal = new bootstrap.Modal(
      document.getElementById("fieldSettingsModal")
    );
    let currentField = null;

    // Make field items draggable
    document.querySelectorAll(".field-item").forEach((item) => {
      item.addEventListener("dragstart", function (e) {
        e.dataTransfer.setData("text/plain", this.dataset.type);
      });
    });

    // Handle drop on form container
    formFieldsContainer.addEventListener("dragover", function (e) {
      e.preventDefault();
      this.classList.add("bg-light");
    });

    formFieldsContainer.addEventListener("dragleave", function () {
      this.classList.remove("bg-light");
    });

    formFieldsContainer.addEventListener("drop", function (e) {
      e.preventDefault();
      this.classList.remove("bg-light");
      const fieldType = e.dataTransfer.getData("text/plain");
      if (fieldType) {
        currentField = {
          type: fieldType,
          label:
            fieldType.charAt(0).toUpperCase() +
            fieldType.slice(1).replace(/-/g, " ") +
            " Field",
          description: "",
          required: false,
          options: ["Option 1", "Option 2"],
        };
        showFieldSettings();
      }
    });

    // Field settings modal
    function showFieldSettings() {
      document.getElementById("fieldLabel").value = currentField.label;
      document.getElementById("fieldDescription").value =
        currentField.description;
      document.getElementById("fieldRequired").checked = currentField.required;

      const optionsContainer = document.getElementById("optionsContainer");
      const optionsList = document.getElementById("optionsList");

      if (["dropdown", "checkbox", "radio"].includes(currentField.type)) {
        optionsContainer.style.display = "block";
        optionsList.innerHTML = "";

        currentField.options.forEach((option, index) => {
          const optionEl = document.createElement("div");
          optionEl.className = "input-group mb-2";
          optionEl.innerHTML = `
                  <input type="text" class="form-control option-input" value="${option}">
                  <button class="btn btn-outline-danger remove-option" type="button">&times;</button>
              `;
          optionsList.appendChild(optionEl);
        });
      } else {
        optionsContainer.style.display = "none";
      }

      fieldSettingsModal.show();
    }

    // Add option button
    document.getElementById("addOption").addEventListener("click", function () {
      const optionsList = document.getElementById("optionsList");
      const newOption = document.createElement("div");
      newOption.className = "input-group mb-2";
      newOption.innerHTML = `
          <input type="text" class="form-control option-input" placeholder="New option">
          <button class="btn btn-outline-danger remove-option" type="button">&times;</button>
      `;
      optionsList.appendChild(newOption);
    });

    // Remove option
    document
      .getElementById("optionsList")
      .addEventListener("click", function (e) {
        if (e.target.classList.contains("remove-option")) {
          e.target.closest(".input-group").remove();
        }
      });

    // Save field settings
    document
      .getElementById("saveFieldSettings")
      .addEventListener("click", function () {
        currentField.label = document.getElementById("fieldLabel").value;
        currentField.description =
          document.getElementById("fieldDescription").value;
        currentField.required =
          document.getElementById("fieldRequired").checked;

        if (["dropdown", "checkbox", "radio"].includes(currentField.type)) {
          currentField.options = [];
          document.querySelectorAll(".option-input").forEach((input) => {
            if (input.value.trim())
              currentField.options.push(input.value.trim());
          });
        }

        addFieldToForm(currentField);
        fieldSettingsModal.hide();
      });

    // Add field to form
    function addFieldToForm(field) {
      const fieldId = "field-" + Date.now();
      let fieldHtml = "";

      switch (field.type) {
        case "text":
          fieldHtml = `
                  <div class="form-field" data-field-id="${fieldId}">
                      <div class="field-actions">
                          <button class="btn btn-sm btn-outline-secondary edit-field me-1">
                              <i class="fas fa-edit"></i>
                          </button>
                          <button class="btn btn-sm btn-outline-danger delete-field">
                              <i class="fas fa-trash"></i>
                          </button>
                      </div>
                      <label class="form-label">${field.label} ${
            field.required ? '<span class="text-danger">*</span>' : ""
          }</label>
                      <input type="text" class="form-control" disabled>
                      ${
                        field.description
                          ? `<small class="text-muted d-block">${field.description}</small>`
                          : ""
                      }
                      <input type="hidden" name="fields[${fieldId}][type]" value="${
            field.type
          }">
                      <input type="hidden" name="fields[${fieldId}][label]" value="${
            field.label
          }">
                      <input type="hidden" name="fields[${fieldId}][description]" value="${
            field.description
          }">
                      <input type="hidden" name="fields[${fieldId}][required]" value="${
            field.required ? "1" : "0"
          }">
                      <input type="hidden" name="fields[${fieldId}][position]" value="${
            document.querySelectorAll(".form-field").length
          }">
                  </div>
              `;
          break;

        // Add cases for other field types similarly
        // ...
      }

      formFieldsContainer.insertAdjacentHTML("beforeend", fieldHtml);
    }

    // Save form
    document.getElementById("saveForm").addEventListener("click", function () {
      const formTitle = document.getElementById("formTitle").value;
      const formDescription = document.getElementById("formDescription").value;

      if (!formTitle) {
        alert("Please enter a form title");
        return;
      }

      const formData = new FormData();
      formData.append("title", formTitle);
      formData.append("description", formDescription);

      document.querySelectorAll(".form-field").forEach((fieldEl) => {
        const fieldId = fieldEl.dataset.fieldId;
        const prefix = `fields[${fieldId}]`;

        formData.append(
          `${prefix}[type]`,
          fieldEl.querySelector('input[type="hidden"][name*="[type]"]').value
        );
        formData.append(
          `${prefix}[label]`,
          fieldEl.querySelector('input[type="hidden"][name*="[label]"]').value
        );
        formData.append(
          `${prefix}[description]`,
          fieldEl.querySelector('input[type="hidden"][name*="[description]"]')
            .value
        );
        formData.append(
          `${prefix}[required]`,
          fieldEl.querySelector('input[type="hidden"][name*="[required]"]')
            .value
        );
        formData.append(
          `${prefix}[position]`,
          fieldEl.querySelector('input[type="hidden"][name*="[position]"]')
            .value
        );

        const optionsInput = fieldEl.querySelector(
          'input[type="hidden"][name*="[options]"]'
        );
        if (optionsInput) {
          formData.append(`${prefix}[options]`, optionsInput.value);
        }
      });

      fetch(window.location.href, {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (response.redirected) {
            window.location.href = response.url;
          } else {
            return response.text().then((text) => {
              throw new Error(text || "Error saving form");
            });
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert(error.message);
        });
    });

    // Edit/delete field
    formFieldsContainer.addEventListener("click", function (e) {
      if (e.target.closest(".delete-field")) {
        e.target.closest(".form-field").remove();
      } else if (e.target.closest(".edit-field")) {
        const fieldEl = e.target.closest(".form-field");
        currentField = {
          id: fieldEl.dataset.fieldId,
          type: fieldEl.querySelector('input[type="hidden"][name*="[type]"]')
            .value,
          label: fieldEl.querySelector('input[type="hidden"][name*="[label]"]')
            .value,
          description: fieldEl.querySelector(
            'input[type="hidden"][name*="[description]"]'
          ).value,
          required:
            fieldEl.querySelector('input[type="hidden"][name*="[required]"]')
              .value === "1",
          options: fieldEl.querySelector(
            'input[type="hidden"][name*="[options]"]'
          )
            ? JSON.parse(
                fieldEl.querySelector('input[type="hidden"][name*="[options]"]')
                  .value
              )
            : [],
        };
        showFieldSettings();
      }
    });
  });

  // Form validation
  const forms = document.querySelectorAll(".needs-validation");
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener(
      "submit",
      function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }

        form.classList.add("was-validated");
      },
      false
    );
  });

  // Dashboard charts (example with Chart.js)
  if (
    typeof Chart !== "undefined" &&
    document.getElementById("submissionsChart")
  ) {
    initSubmissionsChart();
  }
});

function initFormBuilder() {
  const formFieldsContainer = document.getElementById("formFieldsContainer");
  const fieldSettingsModal = new bootstrap.Modal(
    document.getElementById("fieldSettingsModal")
  );
  let currentField = null;
  let fieldCounter = 0;

  // Make field items draggable
  document.querySelectorAll(".field-item").forEach((item) => {
    item.addEventListener("dragstart", function (e) {
      e.dataTransfer.setData("text/plain", this.dataset.type);
    });
  });

  // Handle drop on form container
  formFieldsContainer.addEventListener("dragover", function (e) {
    e.preventDefault();
    this.classList.add("bg-light");
  });

  formFieldsContainer.addEventListener("dragleave", function () {
    this.classList.remove("bg-light");
  });

  formFieldsContainer.addEventListener("drop", function (e) {
    e.preventDefault();
    this.classList.remove("bg-light");
    const fieldType = e.dataTransfer.getData("text/plain");
    if (fieldType) {
      currentField = {
        type: fieldType,
        id: "field-" + ++fieldCounter,
        label:
          fieldType.charAt(0).toUpperCase() + fieldType.slice(1) + " Field",
        description: "",
        required: false,
        options:
          fieldType === "dropdown" ||
          fieldType === "checkbox" ||
          fieldType === "radio"
            ? ["Option 1", "Option 2"]
            : [],
      };
      showFieldSettings();
    }
  });

  // Field settings modal
  function showFieldSettings() {
    document.getElementById("fieldLabel").value = currentField.label;
    document.getElementById("fieldDescription").value =
      currentField.description;
    document.getElementById("fieldRequired").checked = currentField.required;

    const optionsContainer = document.getElementById("optionsContainer");
    const optionsList = document.getElementById("optionsList");

    if (["dropdown", "checkbox", "radio"].includes(currentField.type)) {
      optionsContainer.style.display = "block";
      optionsList.innerHTML = "";

      currentField.options.forEach((option, index) => {
        const optionId = "option-" + index;
        const optionEl = document.createElement("div");
        optionEl.className = "input-group mb-2";
        optionEl.innerHTML = `
                    <input type="text" class="form-control option-input" value="${option}" placeholder="Option ${
          index + 1
        }">
                    <button class="btn btn-outline-danger remove-option" type="button">&times;</button>
                `;
        optionsList.appendChild(optionEl);
      });
    } else {
      optionsContainer.style.display = "none";
    }

    fieldSettingsModal.show();
  }

  // Add option button
  document.getElementById("addOption").addEventListener("click", function () {
    const optionsList = document.getElementById("optionsList");
    const newOption = document.createElement("div");
    newOption.className = "input-group mb-2";
    newOption.innerHTML = `
            <input type="text" class="form-control option-input" placeholder="New option">
            <button class="btn btn-outline-danger remove-option" type="button">&times;</button>
        `;
    optionsList.appendChild(newOption);
  });

  // Remove option
  document
    .getElementById("optionsList")
    .addEventListener("click", function (e) {
      if (e.target.classList.contains("remove-option")) {
        e.target.closest(".input-group").remove();
      }
    });

  // Save field settings
  document
    .getElementById("saveFieldSettings")
    .addEventListener("click", function () {
      currentField.label = document.getElementById("fieldLabel").value;
      currentField.description =
        document.getElementById("fieldDescription").value;
      currentField.required = document.getElementById("fieldRequired").checked;

      if (["dropdown", "checkbox", "radio"].includes(currentField.type)) {
        currentField.options = [];
        document.querySelectorAll(".option-input").forEach((input) => {
          if (input.value.trim()) currentField.options.push(input.value.trim());
        });
      }

      addFieldToForm(currentField);
      fieldSettingsModal.hide();
    });

  // Add field to form
  function addFieldToForm(field) {
    const fieldId = "field-" + ++fieldCounter;
    let fieldHtml = "";

    switch (field.type) {
      case "text":
        fieldHtml = `
                    <div class="form-field" data-field-id="${fieldId}">
                        <div class="field-actions">
                            <button class="btn btn-sm btn-outline-secondary edit-field me-1">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-field">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <label class="form-label">${field.label} ${
          field.required ? '<span class="text-danger">*</span>' : ""
        }</label>
                        <input type="text" class="form-control" disabled>
                        ${
                          field.description
                            ? `<small class="text-muted d-block">${field.description}</small>`
                            : ""
                        }
                        <input type="hidden" name="fields[${fieldCounter}][type]" value="${
          field.type
        }">
                        <input type="hidden" name="fields[${fieldCounter}][label]" value="${
          field.label
        }">
                        <input type="hidden" name="fields[${fieldCounter}][description]" value="${
          field.description
        }">
                        <input type="hidden" name="fields[${fieldCounter}][required]" value="${
          field.required
        }">
                    </div>
                `;
        break;
      case "number":
        fieldHtml = `
                    <div class="form-field" data-field-id="${fieldId}">
                        <div class="field-actions">
                            <button class="btn btn-sm btn-outline-secondary edit-field me-1">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-field">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <label class="form-label">${field.label} ${
          field.required ? '<span class="text-danger">*</span>' : ""
        }</label>
                        <input type="number" class="form-control" disabled>
                        ${
                          field.description
                            ? `<small class="text-muted d-block">${field.description}</small>`
                            : ""
                        }
                        <input type="hidden" name="fields[${fieldCounter}][type]" value="${
          field.type
        }">
                        <input type="hidden" name="fields[${fieldCounter}][label]" value="${
          field.label
        }">
                        <input type="hidden" name="fields[${fieldCounter}][description]" value="${
          field.description
        }">
                        <input type="hidden" name="fields[${fieldCounter}][required]" value="${
          field.required
        }">
                    </div>
                `;
        break;
      case "date":
        fieldHtml = `
                    <div class="form-field" data-field-id="${fieldId}">
                        <div class="field-actions">
                            <button class="btn btn-sm btn-outline-secondary edit-field me-1">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-field">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <label class="form-label">${field.label} ${
          field.required ? '<span class="text-danger">*</span>' : ""
        }</label>
                        <input type="date" class="form-control" disabled>
                        ${
                          field.description
                            ? `<small class="text-muted d-block">${field.description}</small>`
                            : ""
                        }
                        <input type="hidden" name="fields[${fieldCounter}][type]" value="${
          field.type
        }">
                        <input type="hidden" name="fields[${fieldCounter}][label]" value="${
          field.label
        }">
                        <input type="hidden" name="fields[${fieldCounter}][description]" value="${
          field.description
        }">
                        <input type="hidden" name="fields[${fieldCounter}][required]" value="${
          field.required
        }">
                    </div>
                `;
        break;
      case "dropdown":
        const optionsHtml = field.options
          .map((opt) => `<option value="${opt}">${opt}</option>`)
          .join("");
        fieldHtml = `
                    <div class="form-field" data-field-id="${fieldId}">
                        <div class="field-actions">
                            <button class="btn btn-sm btn-outline-secondary edit-field me-1">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-field">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <label class="form-label">${field.label} ${
          field.required ? '<span class="text-danger">*</span>' : ""
        }</label>
                        <select class="form-select" disabled>
                            <option value="">Select an option</option>
                            ${optionsHtml}
                        </select>
                        ${
                          field.description
                            ? `<small class="text-muted d-block">${field.description}</small>`
                            : ""
                        }
                        <input type="hidden" name="fields[${fieldCounter}][type]" value="${
          field.type
        }">
                        <input type="hidden" name="fields[${fieldCounter}][label]" value="${
          field.label
        }">
                        <input type="hidden" name="fields[${fieldCounter}][description]" value="${
          field.description
        }">
                        <input type="hidden" name="fields[${fieldCounter}][required]" value="${
          field.required
        }">
                        <input type="hidden" name="fields[${fieldCounter}][options]" value='${JSON.stringify(
          field.options
        )}'>
                    </div>
                `;
        break;
      case "checkbox":
        const checkboxesHtml = field.options
          .map(
            (opt, i) => `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" disabled>
                        <label class="form-check-label">${opt}</label>
                    </div>
                `
          )
          .join("");
        fieldHtml = `
                    <div class="form-field" data-field-id="${fieldId}">
                        <div class="field-actions">
                            <button class="btn btn-sm btn-outline-secondary edit-field me-1">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-field">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <label class="form-label">${field.label} ${
          field.required ? '<span class="text-danger">*</span>' : ""
        }</label>
                        ${checkboxesHtml}
                        ${
                          field.description
                            ? `<small class="text-muted d-block">${field.description}</small>`
                            : ""
                        }
                        <input type="hidden" name="fields[${fieldCounter}][type]" value="${
          field.type
        }">
                        <input type="hidden" name="fields[${fieldCounter}][label]" value="${
          field.label
        }">
                        <input type="hidden" name="fields[${fieldCounter}][description]" value="${
          field.description
        }">
                        <input type="hidden" name="fields[${fieldCounter}][required]" value="${
          field.required
        }">
                        <input type="hidden" name="fields[${fieldCounter}][options]" value='${JSON.stringify(
          field.options
        )}'>
                    </div>
                `;
        break;
      case "radio":
        const radiosHtml = field.options
          .map(
            (opt, i) => `
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="radio-${fieldId}" disabled>
                        <label class="form-check-label">${opt}</label>
                    </div>
                `
          )
          .join("");
        fieldHtml = `
                    <div class="form-field" data-field-id="${fieldId}">
                        <div class="field-actions">
                            <button class="btn btn-sm btn-outline-secondary edit-field me-1">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-field">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <label class="form-label">${field.label} ${
          field.required ? '<span class="text-danger">*</span>' : ""
        }</label>
                        ${radiosHtml}
                        ${
                          field.description
                            ? `<small class="text-muted d-block">${field.description}</small>`
                            : ""
                        }
                        <input type="hidden" name="fields[${fieldCounter}][type]" value="${
          field.type
        }">
                        <input type="hidden" name="fields[${fieldCounter}][label]" value="${
          field.label
        }">
                        <input type="hidden" name="fields[${fieldCounter}][description]" value="${
          field.description
        }">
                        <input type="hidden" name="fields[${fieldCounter}][required]" value="${
          field.required
        }">
                        <input type="hidden" name="fields[${fieldCounter}][options]" value='${JSON.stringify(
          field.options
        )}'>
                    </div>
                `;
        break;
      case "file":
        fieldHtml = `
                    <div class="form-field" data-field-id="${fieldId}">
                        <div class="field-actions">
                            <button class="btn btn-sm btn-outline-secondary edit-field me-1">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-field">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <label class="form-label">${field.label} ${
          field.required ? '<span class="text-danger">*</span>' : ""
        }</label>
                        <input type="file" class="form-control" disabled>
                        ${
                          field.description
                            ? `<small class="text-muted d-block">${field.description}</small>`
                            : ""
                        }
                        <input type="hidden" name="fields[${fieldCounter}][type]" value="${
          field.type
        }">
                        <input type="hidden" name="fields[${fieldCounter}][label]" value="${
          field.label
        }">
                        <input type="hidden" name="fields[${fieldCounter}][description]" value="${
          field.description
        }">
                        <input type="hidden" name="fields[${fieldCounter}][required]" value="${
          field.required
        }">
                    </div>
                `;
        break;
    }

    formFieldsContainer.insertAdjacentHTML("beforeend", fieldHtml);
  }

  // Edit/delete field
  formFieldsContainer.addEventListener("click", function (e) {
    const fieldElement = e.target.closest(".form-field");
    if (!fieldElement) return;

    if (e.target.closest(".delete-field")) {
      fieldElement.remove();
    } else if (e.target.closest(".edit-field")) {
      currentField = {
        id: fieldElement.dataset.fieldId,
        type: fieldElement.querySelector('input[type="hidden"][name*="[type]"]')
          .value,
        label: fieldElement.querySelector(
          'input[type="hidden"][name*="[label]"]'
        ).value,
        description: fieldElement.querySelector(
          'input[type="hidden"][name*="[description]"]'
        ).value,
        required:
          fieldElement.querySelector('input[type="hidden"][name*="[required]"]')
            .value === "true",
        options: fieldElement.querySelector(
          'input[type="hidden"][name*="[options]"]'
        )
          ? JSON.parse(
              fieldElement.querySelector(
                'input[type="hidden"][name*="[options]"]'
              ).value
            )
          : [],
      };
      showFieldSettings();
    }
  });

  // Save form
  document.getElementById("saveForm").addEventListener("click", function () {
    const formTitle = document.getElementById("formTitle").value;
    const formDescription = document.getElementById("formDescription").value;

    if (!formTitle) {
      showAlert("Please enter a form title", "danger");
      return;
    }

    // Collect all fields data
    const formData = new FormData();
    formData.append("title", formTitle);
    formData.append("description", formDescription);

    document.querySelectorAll(".form-field").forEach((fieldEl, index) => {
      const prefix = `fields[${index}]`;
      formData.append(
        `${prefix}[type]`,
        fieldEl.querySelector('input[type="hidden"][name*="[type]"]').value
      );
      formData.append(
        `${prefix}[label]`,
        fieldEl.querySelector('input[type="hidden"][name*="[label]"]').value
      );
      formData.append(
        `${prefix}[description]`,
        fieldEl.querySelector('input[type="hidden"][name*="[description]"]')
          .value
      );
      formData.append(
        `${prefix}[required]`,
        fieldEl.querySelector('input[type="hidden"][name*="[required]"]').value
      );
      formData.append(`${prefix}[position]`, index);

      const optionsInput = fieldEl.querySelector(
        'input[type="hidden"][name*="[options]"]'
      );
      if (optionsInput) {
        formData.append(`${prefix}[options]`, optionsInput.value);
      }
    });

    // Add debug output
    console.log("Submitting form data:");
    for (let [key, value] of formData.entries()) {
      console.log(key, value);
    }

    // Send data to server
    fetch(window.location.href, {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.text();
      })
      .then((text) => {
        // Handle redirect or show success message
        window.location.href = "forms.php";
      })
      .catch((error) => {
        console.error("Error:", error);
        showAlert("Error saving form: " + error.message, "danger");
      });
  });

  function showAlert(message, type) {
    const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

    const alertsContainer =
      document.getElementById("alertsContainer") || document.body;
    alertsContainer.insertAdjacentHTML("afterbegin", alertHtml);

    setTimeout(() => {
      const alert = document.querySelector(".alert");
      if (alert) {
        bootstrap.Alert.getInstance(alert).close();
      }
    }, 5000);
  }
}

function initSubmissionsChart() {
  const ctx = document.getElementById("submissionsChart").getContext("2d");
  const chart = new Chart(ctx, {
    type: "line",
    data: {
      labels: [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
      ],
      datasets: [
        {
          label: "Form Submissions",
          data: [65, 59, 80, 81, 56, 55, 40, 72, 88, 94, 101, 115],
          backgroundColor: "rgba(78, 115, 223, 0.05)",
          borderColor: "rgba(78, 115, 223, 1)",
          pointBackgroundColor: "rgba(78, 115, 223, 1)",
          pointBorderColor: "#fff",
          pointHoverBackgroundColor: "#fff",
          pointHoverBorderColor: "rgba(78, 115, 223, 1)",
          borderWidth: 2,
          tension: 0.3,
        },
      ],
    },
    options: {
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: "rgba(0, 0, 0, 0.05)",
          },
        },
        x: {
          grid: {
            display: false,
          },
        },
      },
    },
  });
}
