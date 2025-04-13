<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --border-radius: 6px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
            padding: 0;
            margin: 0;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 30px;
        }

        h1 {
            color: var(--dark-color);
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .required-field::after {
            content: " *";
            color: var(--danger-color);
        }

        input[type="text"],
        input[type="date"],
        input[type="time"],
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        select[multiple] {
            height: auto;
            min-height: 120px;
            padding: 8px 15px;
        }

        select[multiple] option {
            padding: 8px 12px;
            margin: 2px 0;
            border-radius: 4px;
            transition: var(--transition);
        }

        select[multiple] option:hover {
            background-color: var(--primary-color);
            color: white;
        }

        select[multiple] option:checked {
            background-color: var(--primary-color);
            color: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .file-upload {
            border: 2px dashed #ccc;
            border-radius: var(--border-radius);
            padding: 30px;
            text-align: center;
            margin: 15px 0;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .file-upload:hover {
            border-color: var(--primary-color);
            background-color: rgba(52, 152, 219, 0.05);
        }

        .file-upload input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .file-upload-text {
            color: #666;
            margin-bottom: 5px;
        }

        .file-upload-hint {
            font-size: 0.9rem;
            color: #999;
        }

        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: var(--transition);
            width: 100%;
        }

        .btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .nav-links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .nav-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        /* Responsive grid for larger screens */
        @media (min-width: 768px) {
            .form-row {
                display: flex;
                gap: 20px;
            }

            .form-row .form-group {
                flex: 1;
            }
        }

        /* Loading animation for form submission */
        .btn.loading {
            position: relative;
            pointer-events: none;
        }

        .btn.loading::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Form validation styles */
        .is-invalid {
            border-color: var(--danger-color) !important;
        }

        .invalid-feedback {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 5px;
            display: none;
        }

        .is-invalid + .invalid-feedback {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1><i class="fas fa-clipboard-check"></i> Attendance Form</h1>
            
            <form id="attendanceForm" action="process_attendance.php" method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="date" class="required-field">Date</label>
                        <input type="date" id="date" name="date" required>
                        <div class="invalid-feedback">Please select a date</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="category" class="required-field">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Training">Training</option>
                            <option value="Match">Match</option>
                            <option value="Meeting">Meeting</option>
                            <option value="Other">Other</option>
                        </select>
                        <div class="invalid-feedback">Please select a category</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="player_name" class="required-field">Player Name</label>
                        <input type="text" id="player_name" name="player_name" required>
                        <div class="invalid-feedback">Please enter player name</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reg_number" class="required-field">Registration Number</label>
                        <input type="text" id="reg_number" name="reg_number" required>
                        <div class="invalid-feedback">Please enter registration number</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="session_type">Session Type</label>
                        <select id="session_type" name="session_type[]" multiple>
                            <option value="Morning">Morning</option>
                            <option value="Afternoon">Afternoon</option>
                            <option value="Evening">Evening</option>
                            <option value="Full Day">Full Day</option>
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple options</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="attendance_status" class="required-field">Attendance Status</label>
                        <select id="attendance_status" name="attendance_status" required>
                            <option value="">Select Status</option>
                            <option value="Present">Present</option>
                            <option value="Late">Late</option>
                            <option value="Absent">Absent</option>
                            <option value="Excused">Excused</option>
                        </select>
                        <div class="invalid-feedback">Please select attendance status</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="arriving_time">Arriving Time</label>
                    <input type="time" id="arriving_time" name="arriving_time">
                </div>
                
                <div class="form-group">
                    <label for="notes">Injury/Excuse Notes</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Enter any injury details or excuses..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>File Upload</label>
                    <div class="file-upload">
                        <div class="file-upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p class="file-upload-text">Drag and drop files here or click to browse</p>
                        <p class="file-upload-hint">Supports: JPG, PNG, PDF (Max: 5MB)</p>
                        <input type="file" name="attendance_file" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-block" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Submit Attendance
                </button>
            </form>
            
            <div class="nav-links">
                <?php if (isAdmin()): ?>
                    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
                <?php endif; ?>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('attendanceForm').addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = document.querySelectorAll('[required]');
            
            // Reset validation
            requiredFields.forEach(field => {
                field.classList.remove('is-invalid');
            });
            
            // Validate required fields
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                    
                    // Scroll to first invalid field
                    if (isValid === false) {
                        field.focus();
                        isValid = true; // Prevent multiple focus changes
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Processing...';
        });

        // File upload feedback
        const fileInput = document.querySelector('input[type="file"]');
        const fileUploadText = document.querySelector('.file-upload-text');
        
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileUploadText.textContent = this.files[0].name;
            } else {
                fileUploadText.textContent = 'Drag and drop files here or click to browse';
            }
        });

        // Drag and drop functionality
        const fileUpload = document.querySelector('.file-upload');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUpload.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUpload.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUpload.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            fileUpload.classList.add('highlight');
        }

        function unhighlight() {
            fileUpload.classList.remove('highlight');
        }

        fileUpload.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            
            // Trigger change event
            const event = new Event('change');
            fileInput.dispatchEvent(event);
        }

        // Enhance multiple select functionality
        const sessionTypeSelect = document.getElementById('session_type');
        sessionTypeSelect.addEventListener('mousedown', function(e) {
            e.preventDefault();
            
            const option = e.target;
            if (option.tagName === 'OPTION') {
                option.selected = !option.selected;
                
                // Trigger change event
                const event = new Event('change');
                this.dispatchEvent(event);
            }
        });
    </script>
</body>
</html>