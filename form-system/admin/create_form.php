<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $fields = $_POST['fields'] ?? [];
    
    if (empty($title)) {
        $error = 'Form title is required';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert form
            $stmt = $pdo->prepare("INSERT INTO forms (user_id, title, description) VALUES (?, ?, ?)");
            $stmt->execute([getUserId(), $title, $description]);
            $form_id = $pdo->lastInsertId();
            
            // Insert fields
            foreach ($fields as $field) {
                if (!empty($field['label'])) {
                    $options = ($field['field_type'] === 'select' || $field['field_type'] === 'radio' || $field['field_type'] === 'checkbox') 
                             ? implode("\n", $field['options']) 
                             : null;
                    $stmt = $pdo->prepare("INSERT INTO form_fields 
                                         (form_id, field_type, label, placeholder, options, is_required, sort_order, allow_file_upload, file_types) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $form_id,
                        $field['field_type'],
                        $field['label'],
                        $field['placeholder'] ?? '',
                        $options,
                        isset($field['is_required']) ? 1 : 0,
                        $field['sort_order'] ?? 0,
                        isset($field['allow_file_upload']) ? 1 : 0,
                        $field['file_types'] ?? ''
                    ]);
                }
            }
            
            $pdo->commit();
            $success = 'Form created successfully!';
            $_SESSION['success'] = $success;
            redirect('/admin/forms.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error creating form: ' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<!-- Add Bootstrap CSS link -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-header">
                <h4>Create New Form</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form id="form-builder" method="POST">
                    <div class="mb-3">
                        <label for="title" class="form-label">Form Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    
                    <h5 class="mt-4">Form Fields</h5>
                    <div id="fields-container">
                        <!-- Fields will be added here -->
                    </div>
                    
                    <div class="mb-3">
                        <button type="button" id="add-field" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Add Field
                        </button>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Save Form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Field template (hidden) -->
<div id="field-template" class="field-group card mb-3 d-none">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="field-title">New Field</span>
        <button type="button" class="btn btn-sm btn-danger remove-field">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Field Type</label>
                <select class="form-select field-type" name="fields[0][field_type]">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="email">Email</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="select">Dropdown</option>
                    <option value="radio">Radio Buttons</option>
                    <option value="checkbox">Checkbox</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Label</label>
                <input type="text" class="form-control field-label" name="fields[0][label]" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Placeholder</label>
                <input type="text" class="form-control field-placeholder" name="fields[0][placeholder]">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="form-check">
                    <input class="form-check-input field-required" type="checkbox" name="fields[0][is_required]" id="field-required-0">
                    <label class="form-check-label" for="field-required-0">Required</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Sort Order</label>
                <input type="number" class="form-control field-order" name="fields[0][sort_order]" value="0">
            </div>
        </div>
        <div class="file-upload-container mb-3 d-none">
            <div class="form-check">
                <input class="form-check-input allow-file-upload" type="checkbox" name="fields[0][allow_file_upload]" id="allow_file_upload-0">
                <label class="form-check-label" for="allow_file_upload-0">Allow file upload for this field</label>
            </div>
            <div class="mt-2 file-types-container d-none">
                <label class="form-label">Allowed file types</label>
                <input type="text" class="form-control file-types" name="fields[0][file_types]" placeholder="jpg,png,pdf,docx (comma separated)">
            </div>
        </div>
        <div class="options-container d-none">
            <div class="mb-3">
                <label class="form-label">Options (one per line)</label>
                <textarea class="form-control field-options" name="fields[0][options][]" rows="3"></textarea>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let fieldCount = 0;
    const fieldsContainer = document.getElementById('fields-container');
    const addFieldButton = document.getElementById('add-field');
    
    // Add new field
    addFieldButton.addEventListener('click', function() {
        const template = document.getElementById('field-template').cloneNode(true);
        template.classList.remove('d-none');
        template.id = '';
        
        // Update field indices
        const html = template.innerHTML.replace(/fields\[0\]/g, `fields[${fieldCount}]`);
        template.innerHTML = html;
        
        fieldsContainer.appendChild(template);
        fieldCount++;
        
        // Initialize the new field
        initField(template);
    });
    
    // Initialize field functionality
    function initField(fieldElement) {
        const typeSelect = fieldElement.querySelector('.field-type');
        const optionsContainer = fieldElement.querySelector('.options-container');
        const fileUploadContainer = fieldElement.querySelector('.file-upload-container');
        const fileTypesContainer = fieldElement.querySelector('.file-types-container');
        const allowFileUploadCheckbox = fieldElement.querySelector('.allow-file-upload');
        const labelInput = fieldElement.querySelector('.field-label');
        const removeButton = fieldElement.querySelector('.remove-field');
        
        // Update title when label changes
        labelInput.addEventListener('input', function() {
            fieldElement.querySelector('.field-title').textContent = this.value || 'New Field';
        });
        
        // Toggle options container based on field type
        function toggleOptions() {
            const showOptions = ['select', 'radio', 'checkbox'].includes(typeSelect.value);
            optionsContainer.classList.toggle('d-none', !showOptions);
            
            const showFileUpload = ['text', 'textarea'].includes(typeSelect.value);
            fileUploadContainer.classList.toggle('d-none', !showFileUpload);
            
            if (showFileUpload) {
                fileTypesContainer.classList.toggle('d-none', !allowFileUploadCheckbox.checked);
            }
        }
        
        // Toggle file types when checkbox changes
        allowFileUploadCheckbox.addEventListener('change', function() {
            fileTypesContainer.classList.toggle('d-none', !this.checked);
        });
        
        typeSelect.addEventListener('change', toggleOptions);
        toggleOptions(); // Initial check
        
        // Remove field
        removeButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to remove this field?')) {
                fieldElement.remove();
            }
        });
    }
    
    // Add first field by default
    addFieldButton.click();
});
</script>

<?php require_once '../includes/footer.php'; ?>