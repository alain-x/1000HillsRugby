<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $session_type_str = implode(', ', $session_types); // Convert array to string
    
    $attendance_status = $conn->real_escape_string($_POST['attendance_status']);
    $arriving_time = isset($_POST['arriving_time']) ? $conn->real_escape_string($_POST['arriving_time']) : '';
    $notes = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
    $user_id = $_SESSION['user_id'];
    
    // Handle file upload
    $file_path = '';
    if (isset($_FILES['attendance_file']) && $_FILES['attendance_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = basename($_FILES['attendance_file']['name']);
        $target_path = $upload_dir . uniqid() . '_' . $file_name;
        
        if (move_uploaded_file($_FILES['attendance_file']['tmp_name'], $target_path)) {
            $file_path = $target_path;
        }
    }
    
    // Insert into database
    $sql = "INSERT INTO attendance (date, category, player_name, reg_number, session_type, 
            attendance_status, arriving_time, notes, file_path, user_id, created_at) 
            VALUES ('$date', '$category', '$player_name', '$reg_number', '$session_type_str', 
            '$attendance_status', '$arriving_time', '$notes', '$file_path', $user_id, NOW())";
    
    if ($conn->query($sql)) {
        header("Location: attendance_success.php");
        exit();
    } else {
        die("Error: " . $sql . "<br>" . $conn->error);
    }
}

header("Location: index.php");
exit();
?>