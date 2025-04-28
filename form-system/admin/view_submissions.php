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
    $_SESSION['error'] = 'Form not found or you don\'t have permission to view submissions';
    redirect('/admin/forms.php');
}

// Get form fields
$stmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY sort_order");
$stmt->execute([$form_id]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get submissions
$stmt = $pdo->prepare("SELECT fs.*, u.username 
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
    <a href="../submit_form.php?id=<?php echo $form_id; ?>" class="btn btn-primary">View Form</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (count($submissions) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Submitted By</th>
                            <th>Submission Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?php echo $submission['id']; ?></td>
                                <td><?php echo $submission['username'] ?? 'Guest'; ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($submission['submitted_at'])); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-primary view-submission" data-bs-toggle="modal" data-bs-target="#submissionModal" data-id="<?php echo $submission['id']; ?>">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No submissions yet for this form.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Submission Modal -->
<div class="modal fade" id="submissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submission Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="submissionDetails">
                <!-- Content will be loaded via AJAX -->
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
            
            // Fetch submission details via AJAX
            fetch(`../get_submission.php?id=${submissionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = `<h6>Submitted on: ${new Date(data.submission.submitted_at).toLocaleString()}</h6>`;
                        
                        if (data.submission.username) {
                            html += `<h6>Submitted by: ${data.submission.username}</h6>`;
                        }
                        
                        html += '<hr><div class="submission-data">';
                        
                        data.fields.forEach(field => {
                            html += `<div class="mb-3">
                                <label class="form-label"><strong>${field.label}</strong></label>
                                <div>${field.value || '<em>No response</em>'}</div>
                            </div>`;
                        });
                        
                        html += '</div>';
                        document.getElementById('submissionDetails').innerHTML = html;
                    } else {
                        document.getElementById('submissionDetails').innerHTML = 
                            `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('submissionDetails').innerHTML = 
                        `<div class="alert alert-danger">Error loading submission details</div>`;
                });
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>