<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

$form_id = $_GET['id'] ?? 0;

// Get form details
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ? AND user_id = ?");
$stmt->execute([$form_id, getUserId()]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    $_SESSION['error'] = 'Form not found or you don\'t have permission to edit it';
    redirect('/admin/forms.php');
}

// Get form fields
$stmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY sort_order");
$stmt->execute([$form_id]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $existing_fields = $_POST['existing_fields'] ?? [];
    $new_fields = $_POST['fields'] ?? [];
    
    if (empty($title)) {
        $error = 'Form title is required';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update form
            $stmt = $pdo->prepare("UPDATE forms SET title = ?, description = ? WHERE id = ?");
            $stmt->execute([$title, $description, $form_id]);
            
            // Handle existing fields
            foreach ($existing_fields as $field_id => $field) {
                if (!empty($field['label'])) {
                    $options = ($field['field_type'] === 'select' || $field['field_type'] === 'radio' || $field['field_type'] === 'checkbox') 
                             ? implode("\n", $field['options']) 
                             : null;
                    $stmt = $pdo->prepare("UPDATE form_fields 
                                         SET field_type = ?, label = ?, placeholder = ?, options = ?, is_required = ?, sort_order = ?
                                         WHERE id = ?");
                    $stmt->execute([
                        $field['field_type'],
                        $field['label'],
                        $field['placeholder'] ?? '',
                        $options,
                        isset($field['is_required']) ? 1 : 0,
                        $field['sort_order'] ?? 0,
                        $field_id
                    ]);
                } else {
                    // Delete field if label is empty
                    $stmt = $pdo->prepare("DELETE FROM form_fields WHERE id = ?");
                    $stmt->execute([$field_id]);
                }
            }
            
            // Handle new fields
            foreach ($new_fields as $field) {
                if (!empty($field['label'])) {
                    $options = ($field['field_type'] === 'select' || $field['field_type'] === 'radio' || $field['field_type'] === 'checkbox') 
                             ? implode("\n", $field['options']) 
                             : null;
                    $stmt = $pdo->prepare("INSERT INTO form_fields 
                                         (form_id, field_type, label, placeholder, options, is_required, sort_order) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $form_id,
                        $field['field_type'],
                        $field['label'],
                        $field['placeholder'] ?? '',
                        $options,
                        isset($field['is_required']) ? 1 : 0,
                        $field['sort_order'] ?? 0
                    ]);
                }
            }
            
            $pdo->commit();
            $success = 'Form updated successfully!';
            $_SESSION['success'] = $success;
            redirect('/admin/forms.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error updating form: ' . $e->getMessage();
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
                <h4>Edit Form: <?php echo htmlspecialchars($form['title']); ?></h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form id="form-builder" method="POST">
                    <div class="mb-3">
                        <label for="title" class="form-label">Form Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($form['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($form['description']); ?></textarea>
                    </div>
                    
                    <h5 class="mt-4">Form Fields</h5>
                    <div id="fields-container">
                        <?php foreach ($fields as $field): ?>
                            <div class="field-group card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span class="field-title"><?php echo htmlspecialchars($field['label']); ?></span>
                                    <button type="button" class="btn btn-sm btn-danger remove-field">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Field Type</label>
                                            <select class="form-select field-type" name="existing_fields[<?php echo $field['id']; ?>][field_type]">
                                                <option value="text" <?php echo $field['field_type'] === 'text' ? 'selected' : ''; ?>>Text</option>
                                                <option value="textarea" <?php echo $field['field_type'] === 'textarea' ? 'selected' : ''; ?>>Textarea</option>
                                                <option value="email" <?php echo $field['field_type'] === 'email' ? 'selected' : ''; ?>>Email</option>
                                                <option value="number" <?php echo $field['field_type'] === 'number' ? 'selected' : ''; ?>>Number</option>
                                                <option value="date" <?php echo $field['field_type'] === 'date' ? 'selected' : ''; ?>>Date</option>
                                                <option value="select" <?php echo $field['field_type'] === 'select' ? 'selected' : ''; ?>>Dropdown</option>
                                                <option value="radio" <?php echo $field['field_type'] === 'radio' ? 'selected' : ''; ?>>Radio Buttons</option>
                                                <option value="checkbox" <?php echo $field['field_type'] === 'checkbox' ? 'selected' : ''; ?>>Checkbox</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Label</label>
                                            <input type="text" class="form-control field-label" name="existing_fields[<?php echo $field['id']; ?>][label]" value="<?php echo htmlspecialchars($field['label']); ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Placeholder</label>
                                            <input type="text" class="form-control field-placeholder" name="existing_fields[<?php echo $field['id']; ?>][placeholder]" value="<?php echo htmlspecialchars($field['placeholder']); ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input field-required" type="checkbox" name="existing_fields[<?php echo $field['id']; ?>][is_required]" id="field-required-<?php echo $field['id']; ?>" <?php echo $field['is_required'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="field-required-<?php echo $field['id']; ?>">Required</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Sort Order</label>
                                            <input type="number" class="form-control field-order" name="existing_fields[<?php echo $field['id']; ?>][sort_order]" value="<?php echo $field['sort_order']; ?>">
                                        </div>
                                    </div>
                                    <div class="options-container <?php echo in_array($field['field_type'], ['select', 'radio', 'checkbox']) ? '' : 'd-none'; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Options (one per line)</label>
                                            <textarea class="form-control field-options" name="existing_fields[<?php echo $field['id']; ?>][options][]" rows="3"><?php echo $field['options'] ? htmlspecialchars($field['options']) : ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mb-3">
                        <button type="button" id="add-field" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Add Field
                        </button>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Update Form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- New field template (hidden) -->
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
    
    // Initialize existing fields
    document.querySelectorAll('.field-group').forEach(fieldElement => {
        initField(fieldElement);
    });
    
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
        }
        
        typeSelect.addEventListener('change', toggleOptions);
        toggleOptions(); // Initial check
        
        // Remove field
        removeButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to remove this field?')) {
                fieldElement.remove();
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>