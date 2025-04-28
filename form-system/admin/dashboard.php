<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

// Get form statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_forms FROM forms");
$stmt->execute();
$total_forms = $stmt->fetch(PDO::FETCH_ASSOC)['total_forms'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total_submissions FROM form_submissions");
$stmt->execute();
$total_submissions = $stmt->fetch(PDO::FETCH_ASSOC)['total_submissions'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users");
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

require_once '../includes/header.php';
?>

<!-- Add Bootstrap CSS link -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Forms</h5>
                <h2 class="card-text"><?php echo $total_forms; ?></h2>
                <a href="forms.php" class="text-white">View Forms</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Total Submissions</h5>
                <h2 class="card-text"><?php echo $total_submissions; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Total Users</h5>
                <h2 class="card-text"><?php echo $total_users; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4>Recent Forms</h4>
    </div>
    <div class="card-body">
        <?php
        $stmt = $pdo->prepare("SELECT forms.*, users.username 
                               FROM forms 
                               JOIN users ON forms.user_id = users.id 
                               ORDER BY forms.created_at DESC 
                               LIMIT 5");
        $stmt->execute();
        $recent_forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($recent_forms) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_forms as $form): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($form['title']); ?></td>
                                <td><?php echo htmlspecialchars($form['username']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($form['created_at'])); ?></td>
                                <td>
                                    <a href="edit_form.php?id=<?php echo $form['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="../submit_form.php?id=<?php echo $form['id']; ?>" class="btn btn-sm btn-success">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No forms created yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>