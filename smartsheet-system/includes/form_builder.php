<?php
require_once __DIR__ . '/../config/database.php';

class FormBuilder {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    public function createForm($title, $description, $userId) {
        $stmt = $this->db->prepare("INSERT INTO forms (title, description, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $userId]);
        return $this->db->lastInsertId();
    }
    
    public function addFieldToForm($formId, $fieldType, $label, $description = '', $isRequired = false, $options = [], $position = 0) {
        $optionsJson = json_encode($options);
        $stmt = $this->db->prepare("INSERT INTO form_fields (form_id, field_type, label, description, is_required, options, position) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$formId, $fieldType, $label, $description, $isRequired, $optionsJson, $position]);
    }
    
    public function getForm($formId) {
        $stmt = $this->db->prepare("SELECT * FROM forms WHERE id = ?");
        $stmt->execute([$formId]);
        $form = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($form) {
            $stmt = $this->db->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY position");
            $stmt->execute([$formId]);
            $form['fields'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode options JSON
            foreach ($form['fields'] as &$field) {
                $field['options'] = json_decode($field['options'], true) ?: [];
            }
        }
        
        return $form;
    }
    
    public function getAllForms($userId = null) {
        $query = "SELECT f.*, u.username as creator, 
                 (SELECT COUNT(*) FROM form_submissions WHERE form_id = f.id) as submission_count
                 FROM forms f
                 JOIN users u ON f.created_by = u.id";
        
        if ($userId) {
            $query .= " WHERE f.created_by = ?";
        }
        
        $query .= " ORDER BY f.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($userId ? [$userId] : []);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function deleteForm($formId, $userId) {
        // Verify user owns the form or is admin
        $stmt = $this->db->prepare("SELECT created_by FROM forms WHERE id = ?");
        $stmt->execute([$formId]);
        $form = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$form || ($form['created_by'] != $userId && !$this->isAdmin())) {
            return false;
        }
        
        // Delete form and related data (cascading deletes should handle fields and submissions)
        $stmt = $this->db->prepare("DELETE FROM forms WHERE id = ?");
        return $stmt->execute([$formId]);
    }
    
    public function renderForm($formId) {
        $form = $this->getForm($formId);
        if (!$form) return '';
        
        $html = '<form id="form-'.$formId.'" class="needs-validation" method="post" enctype="multipart/form-data" novalidate>';
        $html .= '<input type="hidden" name="form_id" value="'.$formId.'">';
        $html .= '<div class="card mb-4">';
        $html .= '<div class="card-header bg-primary text-white">';
        $html .= '<h2 class="h4 mb-0">'.$form['title'].'</h2>';
        if ($form['description']) {
            $html .= '<p class="mb-0">'.$form['description'].'</p>';
        }
        $html .= '</div>';
        $html .= '<div class="card-body">';
        
        foreach ($form['fields'] as $field) {
            $html .= $this->renderField($field);
        }
        
        $html .= '</div>';
        $html .= '<div class="card-footer">';
        $html .= '<button type="submit" class="btn btn-primary">Submit Form</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</form>';
        
        return $html;
    }
    
    private function renderField($field) {
        $required = $field['is_required'] ? ' required' : '';
        $fieldId = 'field-'.$field['id'];
        $html = '<div class="mb-4">';
        $html .= '<label for="'.$fieldId.'" class="form-label">'.$field['label'];
        if ($field['is_required']) {
            $html .= '<span class="text-danger"> *</span>';
        }
        $html .= '</label>';
        
        switch ($field['field_type']) {
            case 'text':
                $html .= '<input type="text" class="form-control" id="'.$fieldId.'" name="field['.$field['id'].']"'.$required.'>';
                break;
            case 'number':
                $html .= '<input type="number" class="form-control" id="'.$fieldId.'" name="field['.$field['id'].']"'.$required.'>';
                break;
            case 'date':
                $html .= '<input type="date" class="form-control" id="'.$fieldId.'" name="field['.$field['id'].']"'.$required.'>';
                break;
            case 'dropdown':
                $html .= '<select class="form-select" id="'.$fieldId.'" name="field['.$field['id'].']"'.$required.'>';
                $html .= '<option value="">Select an option</option>';
                foreach ($field['options'] as $option) {
                    $html .= '<option value="'.htmlspecialchars($option).'">'.htmlspecialchars($option).'</option>';
                }
                $html .= '</select>';
                break;
            case 'checkbox':
                foreach ($field['options'] as $i => $option) {
                    $optionId = $fieldId.'-'.$i;
                    $html .= '<div class="form-check">';
                    $html .= '<input class="form-check-input" type="checkbox" id="'.$optionId.'" name="field['.$field['id'].'][]" value="'.htmlspecialchars($option).'">';
                    $html .= '<label class="form-check-label" for="'.$optionId.'">'.htmlspecialchars($option).'</label>';
                    $html .= '</div>';
                }
                break;
            case 'radio':
                foreach ($field['options'] as $i => $option) {
                    $optionId = $fieldId.'-'.$i;
                    $html .= '<div class="form-check">';
                    $html .= '<input class="form-check-input" type="radio" id="'.$optionId.'" name="field['.$field['id'].']" value="'.htmlspecialchars($option).'"'.($i === 0 ? $required : '').'>';
                    $html .= '<label class="form-check-label" for="'.$optionId.'">'.htmlspecialchars($option).'</label>';
                    $html .= '</div>';
                }
                break;
            case 'file':
                $html .= '<input type="file" class="form-control" id="'.$fieldId.'" name="field['.$field['id'].']"'.$required.'>';
                break;
        }
        
        if ($field['description']) {
            $html .= '<div class="form-text">'.$field['description'].'</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
}