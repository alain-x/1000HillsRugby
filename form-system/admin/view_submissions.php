<?php
// admin/view_submissions.php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

$form_id = $_GET['id'] ?? 0;

// Get form details
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    $_SESSION['error'] = 'Form not found';
    redirect('/admin/forms.php');
}

// Get submissions with user data
$stmt = $pdo->prepare("SELECT fs.*, u.username, u.email 
                      FROM form_submissions fs 
                      LEFT JOIN users u ON fs.user_id = u.id 
                      WHERE fs.form_id = ? 
                      ORDER BY fs.submitted_at DESC");
$stmt->execute([$form_id]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Submissions for: <?php echo htmlspecialchars($form['title']); ?></h2>
    <div>
        <a href="../submit_form.php?id=<?php echo $form_id; ?>" class="btn btn-primary">View Form</a>
        <a href="/admin/forms.php" class="btn btn-secondary">Back to Forms</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (count($submissions) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Submitted By</th>
                            <th>User Email</th>
                            <th>Submission Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?php echo $submission['id']; ?></td>
                                <td><?php echo $submission['username'] ?? 'Guest'; ?></td>
                                <td><?php echo $submission['email'] ?? 'N/A'; ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($submission['submitted_at'])); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-primary view-submission" 
                                       data-bs-toggle="modal" data-bs-target="#submissionModal" 
                                       data-id="<?php echo $submission['id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="delete_submission.php?id=<?php echo $submission['id']; ?>&form_id=<?php echo $form_id; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this submission?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No submissions yet for this form.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Submission Details Modal -->
<div class="modal fade" id="submissionModal" tabindex="-1" aria-labelledby="submissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="submissionModalLabel">Submission Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="submissionDetails">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center my-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle view submission button clicks
    document.querySelectorAll('.view-submission').forEach(button => {
        button.addEventListener('click', function() {
            const submissionId = this.getAttribute('data-id');
            const modal = document.getElementById('submissionModal');
            const modalTitle = modal.querySelector('.modal-title');
            const modalBody = modal.querySelector('.modal-body');
            
            // Show loading spinner
            modalBody.innerHTML = `
                <div class="text-center my-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            // Fetch submission details via AJAX
            fetch(`../get_submission.php?id=${submissionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = `
                            <div class="mb-4">
                                <h6><strong>Form:</strong> ${data.submission.title}</h6>
                                <h6><strong>Submitted by:</strong> ${data.submission.username || 'Guest'}</h6>
                                ${data.submission.email ? `<h6><strong>Email:</strong> ${data.submission.email}</h6>` : ''}
                                <h6><strong>Submitted on:</strong> ${new Date(data.submission.submitted_at).toLocaleString()}</h6>
                            </div>
                            <hr>
                            <div class="submission-data">
                        `;
                        
                        data.fields.forEach(field => {
                            html += `
                                <div class="mb-3 p-3 bg-light rounded">
                                    <label class="form-label fw-bold">${field.label}</label>
                                    <div class="mt-1 p-2 bg-white rounded">${field.value || '<em class="text-muted">No response</em>'}</div>
                                </div>
                            `;
                        });
                        
                        html += '</div>';
                        modalBody.innerHTML = html;
                    } else {
                        modalBody.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            Error loading submission details. Please try again.
                        </div>
                    `;
                });
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>