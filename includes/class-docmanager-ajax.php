<?php
if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Ajax {
    
    private $db;
    
    public function __construct() {
        $this->db = new DocManager_DB();
        
        // AJAX hooks per utenti loggati
        add_action('wp_ajax_docmanager_upload_frontend', array($this, 'handle_frontend_upload'));
        add_action('wp_ajax_docmanager_delete_document', array($this, 'handle_delete_document'));
        add_action('wp_ajax_docmanager_update_document', array($this, 'handle_update_document'));
        add_action('wp_ajax_docmanager_search_documents', array($this, 'handle_search_documents'));
        add_action('wp_ajax_docmanager_get_user_documents', array($this, 'get_user_documents'));
        
        // AJAX hooks per utenti non loggati (se necessario)
        add_action('wp_ajax_nopriv_docmanager_search_documents', array($this, 'handle_search_documents'));
    }
    
    public function handle_frontend_upload() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['upload_nonce'], 'docmanager_upload_nonce')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Security check failed')));
        }
        
        // Verifica che l'utente sia loggato
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('success' => false, 'message' => 'User not logged in')));
        }
        
        // Verifica permessi
        if (!current_user_can('read')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        // Gestione upload file
        $upload_result = $this->process_file_upload();
        
        if ($upload_result['success']) {
            // Salva i dati nel database
            $document_data = array(
                'title' => sanitize_text_field($_POST['doc_title']),
                'description' => sanitize_textarea_field($_POST['doc_description']),
                'file_name' => $upload_result['filename'],
                'file_path' => $upload_result['url'],
                'file_size' => $upload_result['file_size'],
                'file_type' => $upload_result['file_type'],
                'category' => sanitize_text_field($_POST['doc_category']),
                'tags' => sanitize_text_field($_POST['doc_tags']),
                'uploaded_by' => get_current_user_id(),
                'status' => 'active'
            );
            
            $insert_result = $this->db->insert_document($document_data);
            
            if ($insert_result) {
                $document_id = $this->db->wpdb->insert_id;
                
                // Assegna permessi basati sulla visibilità
                $this->assign_permissions_by_visibility($document_id);
                
                // Log dell'azione
                if (get_option('docmanager_enable_logs', 'yes') === 'yes') {
                    $this->db->log_action(get_current_user_id(), $document_id, 'upload');
                }
                
                wp_die(json_encode(array(
                    'success' => true, 
                    'message' => 'Document uploaded successfully',
                    'document_id' => $document_id
                )));
            } else {
                wp_die(json_encode(array('success' => false, 'message' => 'Database error')));
            }
        } else {
            wp_die(json_encode(array('success' => false, 'message' => $upload_result['message'])));
        }
    }
    
    public function handle_update_document() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['update_nonce'], 'docmanager_update_nonce')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Security check failed')));
        }
        
        // Verifica che l'utente sia loggato
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('success' => false, 'message' => 'User not logged in')));
        }
        
        $document_id = intval($_POST['document_id']);
        $user_id = get_current_user_id();
        
        // Verifica che l'utente sia il proprietario del documento
        global $wpdb;
        $documents_table = $wpdb->prefix . 'docmanager_documents';
        
        $document = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$documents_table} WHERE id = %d AND uploaded_by = %d",
            $document_id, $user_id
        ));
        
        if (!$document) {
            wp_die(json_encode(array('success' => false, 'message' => 'Document not found or access denied')));
        }
        
        // Aggiorna il documento
        $update_data = array(
            'title' => sanitize_text_field($_POST['doc_title']),
            'description' => sanitize_textarea_field($_POST['doc_description']),
            'category' => sanitize_text_field($_POST['doc_category']),
            'tags' => sanitize_text_field($_POST['doc_tags'])
        );
        
        $result = $wpdb->update(
            $documents_table,
            $update_data,
            array('id' => $document_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Log dell'azione
            if (get_option('docmanager_enable_logs', 'yes') === 'yes') {
                $this->db->log_action($user_id, $document_id, 'update');
            }
            
            wp_die(json_encode(array(
                'success' => true, 
                'message' => 'Document updated successfully',
                'document' => $update_data
            )));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => 'Failed to update document')));
        }
    }
    
    public function handle_delete_document() {
        if (!wp_verify_nonce($_POST['nonce'], 'docmanager_manage_nonce')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Security check failed')));
        }
        
        $document_id = intval($_POST['document_id']);
        $user_id = get_current_user_id();
        
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('success' => false, 'message' => 'User not logged in')));
        }
        
        global $wpdb;
        $documents_table = $wpdb->prefix . 'docmanager_documents';
        
        // Verifica che l'utente sia il proprietario del documento o amministratore
        $document = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$documents_table} WHERE id = %d",
            $document_id
        ));
        
        if (!$document) {
            wp_die(json_encode(array('success' => false, 'message' => 'Document not found')));
        }
        
        // Verifica permessi
        if ($document->uploaded_by != $user_id && !current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        // Soft delete - cambia solo lo status
        $result = $wpdb->update(
            $documents_table,
            array('status' => 'deleted'),
            array('id' => $document_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Log dell'azione
            if (get_option('docmanager_enable_logs', 'yes') === 'yes') {
                $this->db->log_action($user_id, $document_id, 'delete');
            }
            
            wp_die(json_encode(array('success' => true, 'message' => 'Document deleted successfully')));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => 'Failed to delete document')));
        }
    }
    
    public function handle_search_documents() {
        $search_term = sanitize_text_field($_POST['search_term']);
        $category = sanitize_text_field($_POST['category']);
        
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('success' => false, 'message' => 'User not logged in')));
        }
        
        global $wpdb;
        $documents_table = $wpdb->prefix . 'docmanager_documents';
        $permissions_table = $wpdb->prefix . 'docmanager_permissions';
        
        $user_id = get_current_user_id();
        $user_roles = wp_get_current_user()->roles;
        
        $sql = "SELECT DISTINCT d.* FROM {$documents_table} d
                LEFT JOIN {$permissions_table} p ON d.id = p.document_id
                WHERE d.status = 'active' AND (
                    p.user_id = %d OR d.uploaded_by = %d";
        
        $params = array($user_id, $user_id);
        
        if (!empty($user_roles)) {
            $placeholders = implode(',', array_fill(0, count($user_roles), '%s'));
            $sql .= " OR p.user_role IN ($placeholders)";
            $params = array_merge($params, $user_roles);
        }
        
        $sql .= ")";
        
        if (!empty($search_term)) {
            $sql .= " AND (d.title LIKE %s OR d.description LIKE %s OR d.tags LIKE %s)";
            $search_like = '%' . $wpdb->esc_like($search_term) . '%';
            $params[] = $search_like;
            $params[] = $search_like;
            $params[] = $search_like;
        }
        
        if (!empty($category)) {
            $sql .= " AND d.category = %s";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY d.upload_date DESC";
        
        $documents = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        // Log della ricerca
        if (get_option('docmanager_enable_logs', 'yes') === 'yes') {
            $this->db->log_action($user_id, 0, 'search: ' . $search_term);
        }
        
        wp_die(json_encode(array('success' => true, 'documents' => $documents)));
    }
    
    public function get_user_documents() {
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('success' => false, 'message' => 'User not logged in')));
        }
        
        $user_id = get_current_user_id();
        $user_roles = wp_get_current_user()->roles;
        
        $documents = $this->db->get_user_documents($user_id, $user_roles);
        
        wp_die(json_encode(array('success' => true, 'documents' => $documents)));
    }
    
    private function process_file_upload() {
        if (!isset($_FILES['doc_file']) || $_FILES['doc_file']['error'] !== UPLOAD_ERR_OK) {
            return array('success' => false, 'message' => 'File upload error');
        }
        
        $file = $_FILES['doc_file'];
        
        // Verifica dimensione file
        $max_size = 50 * 1024 * 1024; // 50MB per admin
        if ($file['size'] > $max_size) {
            return array('success' => false, 'message' => 'File too large');
        }
        
        // Verifica tipo file
        $allowed_types = array(
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'text/plain'
        );
        
        if (!in_array($file['type'], $allowed_types)) {
            return array('success' => false, 'message' => 'File type not allowed');
        }
        
        // Crea directory se non esiste
        $upload_dir = wp_upload_dir();
        $docmanager_dir = $upload_dir['basedir'] . '/docmanager/';
        $docmanager_url = $upload_dir['baseurl'] . '/docmanager/';
        
        if (!file_exists($docmanager_dir)) {
            wp_mkdir_p($docmanager_dir);
            
            // Crea file .htaccess per sicurezza
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\nDeny from all\n</Files>";
            file_put_contents($docmanager_dir . '.htaccess', $htaccess_content);
        }
        
        // Genera nome file unico
        $filename = time() . '_' . sanitize_file_name($file['name']);
        $filepath = $docmanager_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return array(
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'url' => $docmanager_url . $filename,
                'file_size' => $file['size'],
                'file_type' => $file['type']
            );
        }
        
        return array('success' => false, 'message' => 'Failed to move uploaded file');
    }
    
    private function assign_permissions_by_visibility($document_id) {
        global $wpdb;
        $permissions_table = $wpdb->prefix . 'docmanager_permissions';
        
        $visibility = sanitize_text_field($_POST['document_visibility'] ?? 'uploader_only');
        $current_user_id = get_current_user_id();
        
        // Assegna sempre il permesso all'utente che carica
        $wpdb->insert(
            $permissions_table,
            array(
                'document_id' => $document_id,
                'user_id' => $current_user_id,
                'permission_type' => 'view',
                'granted_by' => $current_user_id
            ),
            array('%d', '%d', '%s', '%d')
        );
        
        // Assegna permessi aggiuntivi basati sulla visibilità
        switch ($visibility) {
            case 'everyone':
                // Crea un permesso pubblico (nessun user_id o ruolo specifico)
                $wpdb->insert(
                    $permissions_table,
                    array(
                        'document_id' => $document_id,
                        'user_id' => null,
                        'user_role' => null,
                        'permission_type' => 'view',
                        'granted_by' => $current_user_id
                    ),
                    array('%d', null, null, '%s', '%d')
                );
                break;
                
            case 'logged_users':
                // Assegna a tutti i ruoli di utenti registrati
                $roles = wp_roles()->roles;
                foreach ($roles as $role_key => $role) {
                    $wpdb->insert(
                        $permissions_table,
                        array(
                            'document_id' => $document_id,
                            'user_role' => $role_key,
                            'permission_type' => 'view',
                            'granted_by' => $current_user_id
                        ),
                        array('%d', '%s', '%s', '%d')
                    );
                }
                break;
                
            case 'specific_user':
                $specific_user_id = intval($_POST['specific_user_id'] ?? 0);
                if ($specific_user_id > 0) {
                    $wpdb->insert(
                        $permissions_table,
                        array(
                            'document_id' => $document_id,
                            'user_id' => $specific_user_id,
                            'permission_type' => 'view',
                            'granted_by' => $current_user_id
                        ),
                        array('%d', '%d', '%s', '%d')
                    );
                }
                break;
                
            case 'specific_role':
                $specific_role = sanitize_text_field($_POST['specific_role'] ?? '');
                if (!empty($specific_role)) {
                    $wpdb->insert(
                        $permissions_table,
                        array(
                            'document_id' => $document_id,
                            'user_role' => $specific_role,
                            'permission_type' => 'view',
                            'granted_by' => $current_user_id
                        ),
                        array('%d', '%s', '%s', '%d')
                    );
                }
                break;
                
            case 'uploader_only':
            default:
                break;
        }
    }
}