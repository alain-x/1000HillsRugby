<?php
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

// Get submissions with user data and sample response
$stmt = $pdo->prepare("SELECT fs.*, u.username, u.email, 
                      (SELECT value FROM submission_data WHERE submission_id = fs.id LIMIT 1) as sample_response
                      FROM form_submissions fs 
                      LEFT JOIN users u ON fs.user_id = u.id 
                      WHERE fs.form_id = ? 
                      ORDER BY fs.submitted_at DESC");
$stmt->execute([$form_id]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Submissions for: <?= htmlspecialchars($form['title']) ?></h2>
        <div>
            <a href="../submit_form.php?id=<?= $form_id ?>" class="btn btn-primary" target="_blank">
                <i class="fas fa-external-link-alt"></i> View Form
            </a>
            <a href="/admin/forms.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Forms
            </a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <?php if (count($submissions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Submitted By</th>
                                <th>Email</th>
                                <th>Response Preview</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): 
                                // Truncate the response if it's too long
                                $responsePreview = $submission['sample_response'] ?? 'No response';
                                if (strlen($responsePreview) > 50) {
                                    $responsePreview = substr($responsePreview, 0, 50) . '...';
                                }
                            ?>
                                <tr>
                                    <td><?= $submission['id'] ?></td>
                                    <td><?= htmlspecialchars($submission['username'] ?? 'Guest') ?></td>
                                    <td><?= htmlspecialchars($submission['email'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($responsePreview) ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($submission['submitted_at'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary view-submission" 
                                           data-bs-toggle="modal" data-bs-target="#submissionModal" 
                                           data-id="<?= $submission['id'] ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <a href="delete_submission.php?id=<?= $submission['id'] ?>&form_id=<?= $form_id ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Permanently delete this submission?')">
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
            fetch(`get_submission.php?id=${submissionId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        let html = `
                            <div class="mb-4">
                                <h4>${data.submission.title}</h4>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge bg-info">
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
                            <div class="submission-responses">
                        `;
                        
                        data.fields.forEach(field => {
                            if (field.file_path) {
                                const fileExt = field.file_path.split('.').pop().toLowerCase();
                                let icon = 'fa-file';
                                const fileIcons = {
                                    'jpg': 'fa-file-image',
                                    'jpeg': 'fa-file-image',
                                    'png': 'fa-file-image',
                                    'gif': 'fa-file-image',
                                    'pdf': 'fa-file-pdf',
                                    'doc': 'fa-file-word',
                                    'docx': 'fa-file-word',
                                    'xls': 'fa-file-excel',
                                    'xlsx': 'fa-file-excel'
                                };
                                if (fileIcons[fileExt]) icon = fileIcons[fileExt];
                                
                                html += `
                                    <div class="mb-3 p-3 border rounded">
                                        <h6 class="fw-bold">${field.label}</h6>
                                        <div class="d-flex align-items-center gap-2 mt-2">
                                            <a href="../uploads/${field.file_path}" 
                                               target="_blank" 
                                               class="btn btn-sm btn-success">
                                                <i class="fas ${icon}"></i> Download File
                                            </a>
                                            <span class="small text-muted">${formatFileSize(field.file_size)}</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">${field.file_path}</small>
                                    </div>
                                `;
                            } else {
                                html += `
                                    <div class="mb-3 p-3 border rounded">
                                        <h6 class="fw-bold">${field.label}</h6>
                                        <div class="p-2 bg-light rounded mt-2">
                                            ${field.value ? field.value.replace(/\n/g, '<br>') : '<em class="text-muted">No response</em>'}
                                        </div>
                                    </div>
                                `;
                            }
                        });
                        
                        html += `</div>`;
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
                            <i class="fas fa-exclamation-triangle"></i> Failed to load submission. Please try again.
                        </div>
                    `;
                });
        });
    });
    
    function formatFileSize(bytes) {
        if (!bytes) return '';
        if (bytes < 1024) return bytes + ' bytes';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>