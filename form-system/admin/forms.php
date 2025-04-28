<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

// Handle form deletion
if (isset($_GET['delete'])) {
    $form_id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM forms WHERE id = ? AND user_id = ?");
        $stmt->execute([$form_id, getUserId()]);
        $_SESSION['success'] = 'Form deleted successfully';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error deleting form: ' . $e->getMessage();
    }
    
    redirect('/admin/forms.php');
}

// Get all forms created by the current user
$stmt = $pdo->prepare("SELECT * FROM forms WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([getUserId()]);
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>



<!-- Add Bootstrap CSS link -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Forms</h2>
    <a href="create_form.php" class="btn btn-primary">Create New Form</a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (count($forms) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($forms as $form): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($form['title']); ?></td>
                                <td><?php echo htmlspecialchars($form['description']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($form['created_at'])); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($form['updated_at'])); ?></td>
                                <td>
                                    <a href="edit_form.php?id=<?php echo $form['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="../submit_form.php?id=<?php echo $form['id']; ?>" class="btn btn-sm btn-success">View</a>
                                    <a href="view_submissions.php?id=<?php echo $form['id']; ?>" class="btn btn-sm btn-info">Submissions</a>
                                    <a href="forms.php?delete=<?php echo $form['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this form?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>You haven't created any forms yet. <a href="create_form.php">Create your first form</a>.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>