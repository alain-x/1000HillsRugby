<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

if (isAdmin()) {
    redirect('/admin/dashboard.php');
}

// Get available forms
$stmt = $pdo->prepare("SELECT f.*, u.username 
                      FROM forms f 
                      JOIN users u ON f.user_id = u.id 
                      ORDER BY f.created_at DESC 
                      LIMIT 10");
$stmt->execute();
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's submissions
$stmt = $pdo->prepare("SELECT fs.*, f.title 
                      FROM form_submissions fs 
                      JOIN forms f ON fs.form_id = f.id 
                      WHERE fs.user_id = ? 
                      ORDER BY fs.submitted_at DESC 
                      LIMIT 5");
$stmt->execute([getUserId()]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>


<!-- Add Bootstrap CSS link -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">



<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h4>Available Forms</h4>
            </div>
            <div class="card-body">
                <?php if (count($forms) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($forms as $form): ?>
                            <a href="../submit_form.php?id=<?php echo $form['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($form['title']); ?></h5>
                                    <small>Created by: <?php echo htmlspecialchars($form['username']); ?></small>
                                </div>
                                <?php if ($form['description']): ?>
                                    <p class="mb-1"><?php echo htmlspecialchars($form['description']); ?></p>
                                <?php endif; ?>
                                <small>Created on: <?php echo date('M d, Y', strtotime($form['created_at'])); ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No forms available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h4>My Recent Submissions</h4>
            </div>
            <div class="card-body">
                <?php if (count($submissions) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($submissions as $submission): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($submission['title']); ?></h6>
                                    <small><?php echo date('M d, Y', strtotime($submission['submitted_at'])); ?></small>
                                </div>
                                <a href="#" class="btn btn-sm btn-primary mt-2 view-submission" data-bs-toggle="modal" data-bs-target="#submissionModal" data-id="<?php echo $submission['id']; ?>">View Details</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>You haven't submitted any forms yet.</p>
                <?php endif; ?>
            </div>
        </div>
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