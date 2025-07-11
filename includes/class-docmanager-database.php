<?php
/**
 * Gestione database per DocManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Database {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'docmanager_documents';
    }
    
    public function get_documents_by_user($user_id) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE user_id = %d AND status = 'active' 
            ORDER BY upload_date DESC",
            $user_id
        ));
        
        return $results;
    }
    
    public function get_document_by_id($doc_id) {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $doc_id
        ));
        
        return $result;
    }
    
    public function insert_document($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'title' => sanitize_text_field($data['title']),
                'file_path' => sanitize_text_field($data['file_path']),
                'file_type' => sanitize_text_field($data['file_type']),
                'file_size' => intval($data['file_size']),
                'user_id' => intval($data['user_id']),
                'uploaded_by' => intval($data['uploaded_by']),
                'notes' => sanitize_textarea_field($data['notes']),
                'status' => 'active'
            ),
            array('%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s')
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        return false;
    }
    
    public function update_document($doc_id, $data) {
        global $wpdb;
        
        $update_data = array();
        $update_format = array();
        
        if (isset($data['title'])) {
            $update_data['title'] = sanitize_text_field($data['title']);
            $update_format[] = '%s';
        }
        
        if (isset($data['user_id'])) {
            $update_data['user_id'] = intval($data['user_id']);
            $update_format[] = '%d';
        }
        
        if (isset($data['notes'])) {
            $update_data['notes'] = sanitize_textarea_field($data['notes']);
            $update_format[] = '%s';
        }
        
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $update_format[] = '%s';
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $doc_id),
            $update_format,
            array('%d')
        );
        
        return $result !== false;
    }
    
    public function delete_document($doc_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_name,
            array('status' => 'deleted'),
            array('id' => $doc_id),
            array('%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    public function get_all_documents($limit = 20, $offset = 0) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, u.display_name as user_name, a.display_name as uploaded_by_name 
            FROM {$this->table_name} d 
            LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID 
            LEFT JOIN {$wpdb->users} a ON d.uploaded_by = a.ID 
            WHERE d.status = 'active' 
            ORDER BY d.upload_date DESC 
            LIMIT %d OFFSET %d",
            $limit, $offset
        ));
        
        return $results;
    }
    
    public function get_documents_count() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'active'"
        );
        
        return intval($count);
    }
    
    public function search_documents($search_term, $user_id = null) {
        global $wpdb;
        
        $where_clause = "WHERE d.status = 'active' AND (d.title LIKE %s OR d.notes LIKE %s)";
        $search_param = '%' . $wpdb->esc_like($search_term) . '%';
        $params = array($search_param, $search_param);
        
        if ($user_id) {
            $where_clause .= " AND d.user_id = %d";
            $params[] = $user_id;
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, u.display_name as user_name, a.display_name as uploaded_by_name 
            FROM {$this->table_name} d 
            LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID 
            LEFT JOIN {$wpdb->users} a ON d.uploaded_by = a.ID 
            {$where_clause} 
            ORDER BY d.upload_date DESC",
            $params
        ));
        
        return $results;
    }
    
    public function user_can_access_document($doc_id, $user_id) {
        global $wpdb;
        
        $document = $this->get_document_by_id($doc_id);
        
        if (!$document) {
            return false;
        }
        
        if (current_user_can('manage_options')) {
            return true;
        }
        
        return $document->user_id == $user_id;
    }
    
    public function get_user_documents_count($user_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
        
        return intval($count);
    }
    
    public function get_recent_documents($limit = 10) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, u.display_name as user_name, a.display_name as uploaded_by_name 
            FROM {$this->table_name} d 
            LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID 
            LEFT JOIN {$wpdb->users} a ON d.uploaded_by = a.ID 
            WHERE d.status = 'active' 
            ORDER BY d.upload_date DESC 
            LIMIT %d",
            $limit
        ));
        
        return $results;
    }
}