<?php 
require_once 'includes/auth.php';
require_once 'includes/form_builder.php';
require_once 'config/database.php'; // Add this line

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$formBuilder = new FormBuilder();
$user = $auth->getCurrentUser();
$form = null;
$isEdit = false;

// Check if we're editing an existing form
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $formId = $_GET['edit'];
    $form = $formBuilder->getForm($formId);
    
    // Verify user owns the form or is admin
    if (!$form || ($form['created_by'] != $user['id'] && !$auth->isAdmin())) {
        header('Location: forms.php');
        exit;
    }
    
    $isEdit = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $fields = $_POST['fields'] ?? [];
    
    if (empty($title)) {
        $error = "Form title is required";
    } else {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            if ($isEdit) {
                // Update existing form
                $formId = $_GET['edit'];
                
                // Update form title and description first
                $stmt = $conn->prepare("UPDATE forms SET title = ?, description = ? WHERE id = ?");
                if (!$stmt->execute([$title, $description, $formId])) {
                    throw new Exception("Failed to update form");
                }
                
                // Delete all existing fields
                $stmt = $conn->prepare("DELETE FROM form_fields WHERE form_id = ?");
                if (!$stmt->execute([$formId])) {
                    throw new Exception("Failed to delete existing fields");
                }
                
                // Add the new fields
                foreach ($fields as $fieldData) {
                    $options = isset($fieldData['options']) ? json_encode(json_decode($fieldData['options'])) : json_encode([]);
                    $formBuilder->addFieldToForm(
                        $formId,
                        $fieldData['type'],
                        $fieldData['label'],
                        $fieldData['description'],
                        isset($fieldData['required']) ? 1 : 0,
                        $options,
                        $fieldData['position'] ?? 0
                    );
                }
                
                $success = "Form updated successfully!";
            } else {
                // Create new form
                $formId = $formBuilder->createForm($title, $description, $user['id']);
                
                foreach ($fields as $fieldData) {
                    $options = isset($fieldData['options']) ? json_encode(json_decode($fieldData['options'])) : json_encode([]);
                    $formBuilder->addFieldToForm(
                        $formId,
                        $fieldData['type'],
                        $fieldData['label'],
                        $fieldData['description'],
                        isset($fieldData['required']) ? 1 : 0,
                        $options,
                        $fieldData['position'] ?? 0
                    );
                }
                
                $success = "Form created successfully!";
            }
            
            header("Location: forms.php");
            exit;
        } catch (Exception $e) {
            $error = "Error saving form: " . $e->getMessage();
            error_log("Form save error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit Form' : 'Create Form' ?> - SmartSheet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar sidebar-dark d-flex flex-column flex-shrink-0 p-3 text-white">
        <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <span class="fs-4">SmartSheet</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link text-white">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="forms.php" class="nav-link active">
                    <i class="fas fa-list-alt me-2"></i>
                    Forms
                </a>
            </li>
            <li>
                <a href="submissions.php" class="nav-link text-white">
                    <i class="fas fa-inbox me-2"></i>
                    Submissions
                </a>
            </li>
            <?php if ($auth->isAdmin()): ?>
            <li>
                <a href="users.php" class="nav-link text-white">
                    <i class="fas fa-users me-2"></i>
                    Users
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user-circle me-2 fs-4"></i>
                <strong><?= htmlspecialchars($user['username']) ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="#">Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
            </ul>
        </div>
    </div>

    <!-- Main content -->
    <div class="flex-grow-1">
        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow">
            <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle me-3">
                <i class="fa fa-bars"></i>
            </button>
            
            <h1 class="h3 mb-0 text-gray-800"><?= $isEdit ? 'Edit Form' : 'Create New Form' ?></h1>
            <div class="ms-auto">
                <button id="saveForm" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Form
                </button>
            </div>
        </nav>

        <!-- Content -->
        <div class="container-fluid">
            <div id="alertsContainer">
                <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Form Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="formTitle" class="form-label">Form Title</label>
                                <input type="text" class="form-control" id="formTitle" name="title" 
                                    value="<?= $form ? htmlspecialchars($form['title']) : '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="formDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="formDescription" name="description" rows="2"><?= $form ? htmlspecialchars($form['description']) : '' ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-builder-container">
                        <!-- Fields Palette -->
                        <div class="fields-palette">
                            <h5 class="mb-3">Form Fields</h5>
                            <div class="field-item" draggable="true" data-type="text">
                                <i class="fas fa-font me-2"></i> Text Input
                            </div>
                            <div class="field-item" draggable="true" data-type="number">
                                <i class="fas fa-hashtag me-2"></i> Number Input
                            </div>
                            <div class="field-item" draggable="true" data-type="date">
                                <i class="fas fa-calendar-alt me-2"></i> Date Picker
                            </div>
                            <div class="field-item" draggable="true" data-type="dropdown">
                                <i class="fas fa-caret-square-down me-2"></i> Dropdown
                            </div>
                            <div class="field-item" draggable="true" data-type="checkbox">
                                <i class="fas fa-check-square me-2"></i> Checkboxes
                            </div>
                            <div class="field-item" draggable="true" data-type="radio">
                                <i class="fas fa-dot-circle me-2"></i> Radio Buttons
                            </div>
                            <div class="field-item" draggable="true" data-type="file">
                                <i class="fas fa-file-upload me-2"></i> File Upload
                            </div>
                        </div>

                        <!-- Form Preview -->
                        <div class="form-preview">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Form Preview</h6>
                                </div>
                                <div class="card-body">
                                    <div id="formFieldsContainer">
                                        <?php if ($form && isset($form['fields'])): ?>
                                            <?php foreach ($form['fields'] as $index => $field): ?>
                                                <div class="form-field" data-field-id="field-<?= $index ?>">
                                                    <div class="field-actions">
                                                        <button class="btn btn-sm btn-outline-secondary edit-field me-1">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger delete-field">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                    <label class="form-label"><?= htmlspecialchars($field['label']) ?> <?= $field['is_required'] ? '<span class="text-danger">*</span>' : '' ?></label>
                                                    
                                                    <?php if ($field['field_type'] === 'text'): ?>
                                                        <input type="text" class="form-control" disabled>
                                                    <?php elseif ($field['field_type'] === 'number'): ?>
                                                        <input type="number" class="form-control" disabled>
                                                    <?php elseif ($field['field_type'] === 'date'): ?>
                                                        <input type="date" class="form-control" disabled>
                                                    <?php elseif ($field['field_type'] === 'dropdown'): ?>
                                                        <select class="form-select" disabled>
                                                            <option value="">Select an option</option>
                                                            <?php foreach ($field['options'] as $option): ?>
                                                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    <?php elseif ($field['field_type'] === 'checkbox'): ?>
                                                        <?php foreach ($field['options'] as $i => $option): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" disabled>
                                                                <label class="form-check-label"><?= htmlspecialchars($option) ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php elseif ($field['field_type'] === 'radio'): ?>
                                                        <?php foreach ($field['options'] as $i => $option): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="radio-<?= $index ?>" disabled>
                                                                <label class="form-check-label"><?= htmlspecialchars($option) ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php elseif ($field['field_type'] === 'file'): ?>
                                                        <input type="file" class="form-control" disabled>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($field['description']): ?>
                                                        <small class="text-muted d-block"><?= htmlspecialchars($field['description']) ?></small>
                                                    <?php endif; ?>
                                                    
                                                    <input type="hidden" name="fields[<?= $index ?>][type]" value="<?= $field['field_type'] ?>">
                                                    <input type="hidden" name="fields[<?= $index ?>][label]" value="<?= htmlspecialchars($field['label']) ?>">
                                                    <input type="hidden" name="fields[<?= $index ?>][description]" value="<?= htmlspecialchars($field['description']) ?>">
                                                    <input type="hidden" name="fields[<?= $index ?>][required]" value="<?= $field['is_required'] ? '1' : '0' ?>">
                                                    <input type="hidden" name="fields[<?= $index ?>][position]" value="<?= $index ?>">
                                                    
                                                    <?php if (in_array($field['field_type'], ['dropdown', 'checkbox', 'radio'])): ?>
                                                        <input type="hidden" name="fields[<?= $index ?>][options]" value='<?= json_encode($field['options']) ?>'>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!$form || empty($form['fields'])): ?>
                                        <div class="text-center py-5 text-muted">
                                            <i class="fas fa-arrow-left fa-2x mb-3"></i>
                                            <p>Drag fields from the left panel to start building your form</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Field Settings Modal -->
    <div class="modal fade" id="fieldSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Field Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="fieldLabel" class="form-label">Label</label>
                        <input type="text" class="form-control" id="fieldLabel" required>
                    </div>
                    <div class="mb-3">
                        <label for="fieldDescription" class="form-label">Description (optional)</label>
                        <input type="text" class="form-control" id="fieldDescription">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="fieldRequired">
                        <label class="form-check-label" for="fieldRequired">Required</label>
                    </div>
                    <div id="optionsContainer" style="display: none;">
                        <label class="form-label">Options</label>
                        <div id="optionsList">
                            <!-- Options will be added here -->
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary mt-2" id="addOption">
                            <i class="fas fa-plus me-1"></i> Add Option
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveFieldSettings">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>