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

// Get submissions with user data and count responses
$stmt = $pdo->prepare("SELECT fs.*, u.username, u.email, 
                      (SELECT COUNT(*) FROM submission_data WHERE submission_id = fs.id) as response_count
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
        <a href="../submit_form.php?id=<?php echo $form_id; ?>" class="btn btn-primary" target="_blank">
            <i class="fas fa-external-link-alt"></i> View Live Form
        </a>
        <a href="/admin/forms.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Forms
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (count($submissions) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Submitted By</th>
                            <th>Email</th>
                            <th>Responses</th>
                            <th>Submission Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?php echo $submission['id']; ?></td>
                                <td><?php echo htmlspecialchars($submission['username'] ?? 'Guest'); ?></td>
                                <td><?php echo htmlspecialchars($submission['email'] ?? 'N/A'); ?></td>
                                <td><?php echo $submission['response_count']; ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($submission['submitted_at'])); ?></td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-primary view-submission" 
                                       data-bs-toggle="modal" data-bs-target="#submissionModal" 
                                       data-id="<?php echo $submission['id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <a href="delete_submission.php?id=<?php echo $submission['id']; ?>&form_id=<?php echo $form_id; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to permanently delete this submission?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No submissions yet for this form.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Submission Details Modal -->
<div class="modal fade" id="submissionModal" tabindex="-1" aria-labelledby="submissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="submissionModalLabel">Submission Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="submissionDetails">
                <div class="text-center my-5 py-4">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading submission details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
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
            const modalBody = document.getElementById('submissionDetails');
            
            // Show loading state
            modalBody.innerHTML = `
                <div class="text-center my-5 py-4">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading submission details...</p>
                </div>
            `;
            
            // Fetch submission details via AJAX
            fetch(`../get_submission.php?id=${submissionId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        let html = `
                            <div class="mb-4">
                                <h4 class="mb-3">${data.submission.title}</h4>
                                <div class="d-flex flex-wrap gap-3 mb-2">
                                    <span class="badge bg-info text-dark">
                                        <i class="fas fa-user"></i> ${data.submission.username || 'Guest'}
                                    </span>
                                    ${data.submission.email ? `
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-envelope"></i> ${data.submission.email}
                                    </span>` : ''}
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-clock"></i> ${new Date(data.submission.submitted_at).toLocaleString()}
                                    </span>
                                </div>
                            </div>
                            <hr>
                            <div class="submission-data">
                        `;
                        
                        data.fields.forEach(field => {
                            if (field.file_path) {
                                // Handle file uploads
                                const fileExt = field.file_path.split('.').pop().toLowerCase();
                                let fileIcon = 'fa-file';
                                if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) fileIcon = 'fa-file-image';
                                else if (['pdf'].includes(fileExt)) fileIcon = 'fa-file-pdf';
                                else if (['doc', 'docx'].includes(fileExt)) fileIcon = 'fa-file-word';
                                
                                html += `
                                    <div class="mb-4 p-3 border rounded">
                                        <h6 class="fw-bold mb-2">${field.label}</h6>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="../uploads/${field.file_path}" 
                                               target="_blank" 
                                               class="btn btn-sm btn-success">
                                                <i class="fas ${fileIcon}"></i> ${field.file_path.split('_').pop()}
                                            </a>
                                            <span class="small text-muted">${formatFileSize(field.file_size)}</span>
                                        </div>
                                    </div>
                                `;
                            } else {
                                // Handle regular fields
                                html += `
                                    <div class="mb-4 p-3 border rounded">
                                        <h6 class="fw-bold mb-2">${field.label}</h6>
                                        <div class="p-2 bg-light rounded">
                                            ${field.value ? field.value.replace(/\n/g, '<br>') : '<em class="text-muted">No response</em>'}
                                        </div>
                                    </div>
                                `;
                            }
                        });
                        
                        html += '</div>';
                        modalBody.innerHTML = html;
                    } else {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${data.message || 'Error loading submission'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Failed to load submission details. Please try again.
                        </div>
                    `;
                });
        });
    });
    
    // Helper function to format file sizes
    function formatFileSize(bytes) {
        if (typeof bytes !== 'number' || isNaN(bytes)) return '';
        if (bytes < 1024) return bytes + ' bytes';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>