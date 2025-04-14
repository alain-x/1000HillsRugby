<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get attendance record to edit
$attendance = null;
$session_types = [];
$attendance_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($attendance_id > 0) {
    // Fetch the attendance record
    $sql = "SELECT * FROM attendance WHERE id = $attendance_id";
    $result = $conn->query($sql);
    $attendance = $result->fetch_assoc();
    
    // Get session types (if stored as comma-separated string)
    if ($attendance && !empty($attendance['session_type'])) {
        $session_types = explode(', ', $attendance['session_type']);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_id = intval($_POST['attendance_id']);
    
    // Validate and sanitize input
    $date = $conn->real_escape_string($_POST['date']);
    $category = $conn->real_escape_string($_POST['category']);
    $player_name = $conn->real_escape_string($_POST['player_name']);
    $reg_number = $conn->real_escape_string($_POST['reg_number']);
    
    // Handle session_type array
    $session_types = [];
    if (isset($_POST['session_type']) && is_array($_POST['session_type'])) {
        foreach ($_POST['session_type'] as $type) {
            $session_types[] = $conn->real_escape_string($type);
        }
    }
    $session_type_str = implode(', ', $session_types);
    
    $attendance_status = $conn->real_escape_string($_POST['attendance_status']);
    $arriving_time = isset($_POST['arriving_time']) ? $conn->real_escape_string($_POST['arriving_time']) : '';
    $notes = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
    
    // Handle file upload
    $file_path = $attendance['file_path'] ?? '';
    if (isset($_FILES['attendance_file']) && $_FILES['attendance_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Delete old file if exists
        if ($file_path && file_exists($file_path)) {
            unlink($file_path);
        }
        
        $file_name = basename($_FILES['attendance_file']['name']);
        $target_path = $upload_dir . uniqid() . '_' . $file_name;
        
        if (move_uploaded_file($_FILES['attendance_file']['tmp_name'], $target_path)) {
            $file_path = $target_path;
        }
    }
    
    // Update the record
    $sql = "UPDATE attendance SET 
            date = '$date',
            category = '$category',
            player_name = '$player_name',
            reg_number = '$reg_number',
            session_type = '$session_type_str',
            attendance_status = '$attendance_status',
            arriving_time = '$arriving_time',
            notes = '$notes',
            file_path = '$file_path'
            WHERE id = $attendance_id";
    
    if ($conn->query($sql)) {
        header("Location: " . (isAdmin() ? "admin_dashboard.php" : "view_my_attendance.php"));
        exit();
    } else {
        $error = "Error updating record: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attendance Record</title>
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

        .current-file {
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--primary-color);
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

        .error {
            color: var(--danger-color);
            margin-bottom: 20px;
            padding: 10px;
            background-color: rgba(231, 76, 60, 0.1);
            border-radius: var(--border-radius);
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
            <h1><i class="fas fa-edit"></i> Edit Attendance Record</h1>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($attendance): ?>
            <form id="attendanceForm" action="edit_attendance.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="attendance_id" value="<?php echo $attendance_id; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date" class="required-field">Date</label>
                        <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($attendance['date']); ?>" required>
                        <div class="invalid-feedback">Please select a date</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="category" class="required-field">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Senior Men Team" <?php echo $attendance['category'] === 'Senior Men Team' ? 'selected' : ''; ?>>Senior Men Team</option>
                            <option value="Senior Women Team" <?php echo $attendance['category'] === 'Senior Women Team' ? 'selected' : ''; ?>>Senior Women Team</option>
                            <option value="U19 Boys" <?php echo $attendance['category'] === 'U19 Boys' ? 'selected' : ''; ?>>U19 Boys</option>
                            <option value="U19 Girls" <?php echo $attendance['category'] === 'U19 Girls' ? 'selected' : ''; ?>>U19 Girls</option>
                            <option value="Visitor" <?php echo $attendance['category'] === 'Visitor' ? 'selected' : ''; ?>>Visitor</option>
                        </select>
                        <div class="invalid-feedback">Please select a category</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="player_name" class="required-field">Player Name</label>
                        <input type="text" id="player_name" name="player_name" value="<?php echo htmlspecialchars($attendance['player_name']); ?>" required>
                        <div class="invalid-feedback">Please enter player name</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reg_number" class="required-field">Registration Number</label>
                        <input type="text" id="reg_number" name="reg_number" value="<?php echo htmlspecialchars($attendance['reg_number']); ?>" required>
                        <div class="invalid-feedback">Please enter registration number</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="session_type">Session Type</label>
                        <select id="session_type" name="session_type[]" multiple>
                            <option value="Practice" <?php echo in_array('Practice', $session_types) ? 'selected' : ''; ?>>Practice</option>
                            <option value="Strength & Conditioning" <?php echo in_array('Strength & Conditioning', $session_types) ? 'selected' : ''; ?>>Strength & Conditioning</option>
                            <option value="Fun Rugby" <?php echo in_array('Fun Rugby', $session_types) ? 'selected' : ''; ?>>Fun Rugby</option>
                            <option value="Game" <?php echo in_array('Game', $session_types) ? 'selected' : ''; ?>>Game</option>
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple options</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="attendance_status" class="required-field">Attendance Status</label>
                        <select id="attendance_status" name="attendance_status" required>
                            <option value="">Select Status</option>
                            <option value="Present" <?php echo $attendance['attendance_status'] === 'Present' ? 'selected' : ''; ?>>Present</option>
                            <option value="Late" <?php echo $attendance['attendance_status'] === 'Late' ? 'selected' : ''; ?>>Late</option>
                            <option value="Absent" <?php echo $attendance['attendance_status'] === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                            <option value="Excused" <?php echo $attendance['attendance_status'] === 'Excused' ? 'selected' : ''; ?>>Excused</option>
                        </select>
                        <div class="invalid-feedback">Please select attendance status</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="arriving_time">Arriving Time</label>
                    <input type="time" id="arriving_time" name="arriving_time" value="<?php echo htmlspecialchars($attendance['arriving_time']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="notes">Injury/Excuse Notes</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Enter any injury details or excuses..."><?php echo htmlspecialchars($attendance['notes']); ?></textarea>
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
                    <?php if (!empty($attendance['file_path'])): ?>
                        <div class="current-file">
                            <i class="fas fa-paperclip"></i> Current file: 
                            <a href="<?php echo htmlspecialchars($attendance['file_path']); ?>" target="_blank">
                                <?php echo basename($attendance['file_path']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-block" id="submitBtn">
                    <i class="fas fa-save"></i> Update Attendance
                </button>
            </form>
            <?php else: ?>
                <div class="error">Attendance record not found.</div>
            <?php endif; ?>
            
            <div class="nav-links">
                <a href="<?php echo isAdmin() ? 'admin_dashboard.php' : 'view_my_attendance.php'; ?>">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('attendanceForm')?.addEventListener('submit', function(e) {
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
            submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Updating...';
        });

        // Enhance multiple select functionality
        const sessionTypeSelect = document.getElementById('session_type');
        if (sessionTypeSelect) {
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
        }
    </script>
</body>
</html>