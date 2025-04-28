<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$form_id = $_GET['id'] ?? 0;

// Get form details
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    $_SESSION['error'] = 'Form not found';
    redirect(isAdmin() ? '/admin/forms.php' : '/user/dashboard.php');
}

// Get form fields
$stmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY sort_order");
$stmt->execute([$form_id]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Create submission record
        $stmt = $pdo->prepare("INSERT INTO form_submissions (form_id, user_id) VALUES (?, ?)");
        $stmt->execute([$form_id, getUserId()]);
        $submission_id = $pdo->lastInsertId();
        
        // Save submission data
        foreach ($fields as $field) {
            $value = $_POST['field_' . $field['id']] ?? '';
            
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            
            $stmt = $pdo->prepare("INSERT INTO submission_data (submission_id, field_id, field_value) VALUES (?, ?, ?)");
            $stmt->execute([$submission_id, $field['id'], $value]);
        }
        
        $pdo->commit();
        $success = 'Form submitted successfully!';
        $_SESSION['success'] = $success;
        redirect(isAdmin() ? '/admin/view_submissions.php?id='.$form_id : '/user/dashboard.php');
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Error submitting form: ' . $e->getMessage();
    }
}

require_once '../includes/header.php';
?>



<!-- Add Bootstrap CSS link -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">



<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header">
                <h3><?php echo htmlspecialchars($form['title']); ?></h3>
                <?php if ($form['description']): ?>
                    <p class="mb-0"><?php echo htmlspecialchars($form['description']); ?></p>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <?php foreach ($fields as $field): ?>
                        <div class="mb-3">
                            <label for="field_<?php echo $field['id']; ?>" class="form-label">
                                <?php echo htmlspecialchars($field['label']); ?>
                                <?php if ($field['is_required']): ?>
                                    <span class="text-danger">*</span>
                                <?php endif; ?>
                            </label>
                            
                            <?php if ($field['field_type'] === 'text'): ?>
                                <input type="text" class="form-control" id="field_<?php echo $field['id']; ?>" name="field_<?php echo $field['id']; ?>" 
                                       placeholder="<?php echo htmlspecialchars($field['placeholder']); ?>" 
                                       <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <?php elseif ($field['field_type'] === 'textarea'): ?>
                                <textarea class="form-control" id="field_<?php echo $field['id']; ?>" name="field_<?php echo $field['id']; ?>" 
                                          rows="3" placeholder="<?php echo htmlspecialchars($field['placeholder']); ?>"
                                          <?php echo $field['is_required'] ? 'required' : ''; ?>></textarea>
                            <?php elseif ($field['field_type'] === 'email'): ?>
                                <input type="email" class="form-control" id="field_<?php echo $field['id']; ?>" name="field_<?php echo $field['id']; ?>" 
                                       placeholder="<?php echo htmlspecialchars($field['placeholder']); ?>"
                                       <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <?php elseif ($field['field_type'] === 'number'): ?>
                                <input type="number" class="form-control" id="field_<?php echo $field['id']; ?>" name="field_<?php echo $field['id']; ?>" 
                                       placeholder="<?php echo htmlspecialchars($field['placeholder']); ?>"
                                       <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <?php elseif ($field['field_type'] === 'date'): ?>
                                <input type="date" class="form-control" id="field_<?php echo $field['id']; ?>" name="field_<?php echo $field['id']; ?>" 
                                       <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <?php elseif ($field['field_type'] === 'select'): ?>
                                <select class="form-select" id="field_<?php echo $field['id']; ?>" name="field_<?php echo $field['id']; ?>" 
                                        <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                    <option value="">Select an option</option>
                                    <?php foreach (explode("\n", $field['options']) as $option): ?>
                                        <?php if (trim($option)): ?>
                                            <option value="<?php echo htmlspecialchars(trim($option)); ?>"><?php echo htmlspecialchars(trim($option)); ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($field['field_type'] === 'radio'): ?>
                                <?php foreach (explode("\n", $field['options']) as $i => $option): ?>
                                    <?php if (trim($option)): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="field_<?php echo $field['id']; ?>" 
                                                   id="field_<?php echo $field['id']; ?>_<?php echo $i; ?>" 
                                                   value="<?php echo htmlspecialchars(trim($option)); ?>"
                                                   <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                            <label class="form-check-label" for="field_<?php echo $field['id']; ?>_<?php echo $i; ?>">
                                                <?php echo htmlspecialchars(trim($option)); ?>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php elseif ($field['field_type'] === 'checkbox'): ?>
                                <?php foreach (explode("\n", $field['options']) as $i => $option): ?>
                                    <?php if (trim($option)): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="field_<?php echo $field['id']; ?>[]" 
                                                   id="field_<?php echo $field['id']; ?>_<?php echo $i; ?>" 
                                                   value="<?php echo htmlspecialchars(trim($option)); ?>">
                                            <label class="form-check-label" for="field_<?php echo $field['id']; ?>_<?php echo $i; ?>">
                                                <?php echo htmlspecialchars(trim($option)); ?>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Submit Form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>