<?php
require_once __DIR__ . '/../config/database.php';

class SubmissionHandler {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    public function handleSubmission($formId, $data, $files = [], $userId = null) {
        try {
            // Validate input parameters
            if (!is_numeric($formId)) {
                throw new InvalidArgumentException("Invalid form ID");
            }

            $this->db->beginTransaction();
            
            // Create submission record
            $stmt = $this->db->prepare("INSERT INTO form_submissions (form_id, submitted_by) VALUES (?, ?)");
            $stmt->execute([$formId, $userId]);
            $submissionId = $this->db->lastInsertId();
            
            // Process regular form fields
            if (isset($data['field']) && is_array($data['field'])) {
                foreach ($data['field'] as $fieldId => $value) {
                    if (!is_numeric($fieldId)) {
                        continue; // Skip invalid field IDs
                    }

                    $value = is_array($value) ? json_encode($value) : $value;
                    
                    $stmt = $this->db->prepare("INSERT INTO submission_data (submission_id, field_id, value) VALUES (?, ?, ?)");
                    $stmt->execute([$submissionId, $fieldId, $value]);
                }
            }
            
            // Process file uploads
            if (isset($files['field']) && is_array($files['field'])) {
                foreach ($files['field'] as $fieldId => $file) {
                    if (!is_numeric($fieldId)) {
                        continue; // Skip invalid field IDs
                    }

                    if (isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../uploads/' . $formId . '/' . $submissionId . '/';
                        
                        // Create upload directory if it doesn't exist
                        if (!file_exists($uploadDir)) {
                            if (!mkdir($uploadDir, 0777, true) ){
                                throw new RuntimeException("Failed to create upload directory");
                            }
                        }
                        
                        // Sanitize filename
                        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', basename($file['name']));
                        $filepath = $uploadDir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            $stmt = $this->db->prepare("INSERT INTO submission_data (submission_id, field_id, value) VALUES (?, ?, ?)");
                            $stmt->execute([$submissionId, $fieldId, $filepath]);
                        } else {
                            throw new RuntimeException("Failed to move uploaded file");
                        }
                    }
                }
            }
            
            $this->db->commit();
            return $submissionId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Submission error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getSubmissions($formId, $userId = null) {
        try {
            if (!is_numeric($formId)) {
                throw new InvalidArgumentException("Invalid form ID");
            }

            $query = "
                SELECT s.*, u.username 
                FROM form_submissions s 
                LEFT JOIN users u ON s.submitted_by = u.id 
                WHERE s.form_id = ?
            ";
            
            $params = [$formId];
            
            if ($userId && !$this->isAdmin()) {
                $query .= " AND (s.submitted_by = ? OR s.form_id IN (SELECT id FROM forms WHERE created_by = ?))";
                array_push($params, $userId, $userId);
            }
            
            $query .= " ORDER BY s.submitted_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($submissions as &$submission) {
                $stmt = $this->db->prepare("
                    SELECT sd.field_id, sd.value, ff.label, ff.field_type 
                    FROM submission_data sd
                    JOIN form_fields ff ON sd.field_id = ff.id
                    WHERE sd.submission_id = ?
                ");
                $stmt->execute([$submission['id']]);
                $submission['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $submissions;
        } catch (Exception $e) {
            error_log("Error getting submissions: " . $e->getMessage());
            throw $e;
        }
    }

    // Add this method to your existing SubmissionHandler class
public function getSubmission($submissionId) {
    try {
        // Get submission basic info
        $stmt = $this->db->prepare("
            SELECT s.*, u.username, f.title as form_title 
            FROM form_submissions s 
            LEFT JOIN users u ON s.submitted_by = u.id
            LEFT JOIN forms f ON s.form_id = f.id
            WHERE s.id = ?
        ");
        $stmt->execute([$submissionId]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$submission) {
            return null;
        }
        
        // Get all submission data
        $stmt = $this->db->prepare("
            SELECT sd.field_id, sd.value, ff.label, ff.field_type, ff.options
            FROM submission_data sd
            JOIN form_fields ff ON sd.field_id = ff.id
            WHERE sd.submission_id = ?
            ORDER BY ff.position
        ");
        $stmt->execute([$submissionId]);
        $submission['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode options JSON for fields that have options
        foreach ($submission['data'] as &$fieldData) {
            if (!empty($fieldData['options'])) {
                $fieldData['options'] = json_decode($fieldData['options'], true);
            }
        }
        
        return $submission;
    } catch (PDOException $e) {
        error_log("Error getting submission: " . $e->getMessage());
        return null;
    }
}
    
    public function getSubmissionCount($formId) {
        try {
            if (!is_numeric($formId)) {
                throw new InvalidArgumentException("Invalid form ID");
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM form_submissions WHERE form_id = ?");
            $stmt->execute([$formId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['count'] : 0;
        } catch (Exception $e) {
            error_log("Error getting submission count: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}