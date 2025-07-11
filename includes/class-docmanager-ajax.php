<?php
/**
 * Gestione delle chiamate AJAX per DocManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Ajax {
    
    private $db;
    
    public function __construct() {
        $this->db = new DocManager_Database();
        
        add_action('wp_ajax_docmanager_upload', array($this, 'handle_upload'));
        add_action('wp_ajax_docmanager_delete', array($this, 'handle_delete'));
        add_action('wp_ajax_docmanager_get_documents', array($this, 'get_documents'));
        add_action('wp_ajax_docmanager_download', array($this, 'handle_download'));
        add_action('wp_ajax_docmanager_search', array($this, 'handle_search'));
        add_action('wp_ajax_docmanager_update', array($this, 'handle_update'));
        
        add_action('wp_ajax_nopriv_docmanager_get_documents', array($this, 'get_documents'));
        add_action('wp_ajax_nopriv_docmanager_download', array($this, 'handle_download'));
    }
    
    public function handle_upload() {
        check_ajax_referer('docmanager_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        if (!isset($_FILES['file'])) {
            wp_send_json_error('Nessun file selezionato');
        }
        
        $file = $_FILES['file'];
        $title = sanitize_text_field($_POST['title']);
        $user_id = intval($_POST['user_id']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        if (empty($title) || empty($user_id)) {
            wp_send_json_error('Titolo e utente sono obbligatori');
        }
        
        $upload_result = $this->process_file_upload($file);
        
        if (!$upload_result['success']) {
            wp_send_json_error($upload_result['error']);
        }
        
        $doc_data = array(
            'title' => $title,
            'file_path' => $upload_result['file_path'],
            'file_type' => $upload_result['file_type'],
            'file_size' => $upload_result['file_size'],
            'user_id' => $user_id,
            'uploaded_by' => get_current_user_id(),
            'notes' => $notes
        );
        
        $doc_id = $this->db->insert_document($doc_data);
        
        if ($doc_id) {
            wp_send_json_success(array(
                'message' => 'Documento caricato con successo',
                'doc_id' => $doc_id
            ));
        } else {
            wp_send_json_error('Errore nel salvataggio del documento');
        }
    }
    
    public function handle_delete() {
        check_ajax_referer('docmanager_nonce', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        $doc_id = intval($_POST['doc_id']);
        
        if ($this->db->delete_document($doc_id)) {
            wp_send_json_success('Documento eliminato con successo');
        } else {
            wp_send_json_error('Errore nell\'eliminazione del documento');
        }
    }
    
    public function get_documents() {
        check_ajax_referer('docmanager_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Accesso richiesto');
        }
        
        $user_id = get_current_user_id();
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 10;
        $offset = ($page - 1) * $per_page;
        
        if (current_user_can('manage_options')) {
            $documents = $this->db->get_all_documents($per_page, $offset);
            $total = $this->db->get_documents_count();
        } else {
            $documents = $this->db->get_documents_by_user($user_id);
            $total = count($documents);
            $documents = array_slice($documents, $offset, $per_page);
        }
        
        $formatted_docs = array();
        foreach ($documents as $doc) {
            $formatted_docs[] = array(
                'id' => $doc->id,
                'title' => $doc->title,
                'file_type' => strtoupper($doc->file_type),
                'file_size' => DocManager::format_file_size($doc->file_size),
                'upload_date' => date('d/m/Y H:i', strtotime($doc->upload_date)),
                'notes' => $doc->notes,
                'download_url' => add_query_arg(array(
                    'action' => 'docmanager_download',
                    'doc_id' => $doc->id,
                    'nonce' => wp_create_nonce('docmanager_download_' . $doc->id)
                ), admin_url('admin-ajax.php'))
            );
        }
        
        wp_send_json_success(array(
            'documents' => $formatted_docs,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page
        ));
    }
    
    public function handle_download() {
        $doc_id = intval($_GET['doc_id']);
        $nonce = $_GET['nonce'];
        
        if (!wp_verify_nonce($nonce, 'docmanager_download_' . $doc_id)) {
            wp_die('Accesso negato');
        }
        
        if (!is_user_logged_in()) {
            wp_die('Accesso richiesto');
        }
        
        $user_id = get_current_user_id();
        
        if (!$this->db->user_can_access_document($doc_id, $user_id)) {
            wp_die('Non hai i permessi per accedere a questo documento');
        }
        
        $document = $this->db->get_document_by_id($doc_id);
        
        if (!$document) {
            wp_die('Documento non trovato');
        }
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/' . $document->file_path;
        
        if (!file_exists($file_path)) {
            wp_die('File non trovato');
        }
        
        $filename = basename($file_path);
        $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        $content_types = array(
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'zip' => 'application/zip'
        );
        
        $content_type = isset($content_types[$file_extension]) ? $content_types[$file_extension] : 'application/octet-stream';
        
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $document->title . '.' . $file_extension . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: private');
        
        readfile($file_path);
        exit;
    }
    
    public function handle_search() {
        check_ajax_referer('docmanager_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Accesso richiesto');
        }
        
        $search_term = sanitize_text_field($_POST['search']);
        $user_id = get_current_user_id();
        
        if (strlen($search_term) < 2) {
            wp_send_json_error('Termine di ricerca troppo breve');
        }
        
        if (current_user_can('manage_options')) {
            $documents = $this->db->search_documents($search_term);
        } else {
            $documents = $this->db->search_documents($search_term, $user_id);
        }
        
        $formatted_docs = array();
        foreach ($documents as $doc) {
            $formatted_docs[] = array(
                'id' => $doc->id,
                'title' => $doc->title,
                'file_type' => strtoupper($doc->file_type),
                'file_size' => DocManager::format_file_size($doc->file_size),
                'upload_date' => date('d/m/Y H:i', strtotime($doc->upload_date)),
                'notes' => $doc->notes,
                'user_name' => $doc->user_name,
                'download_url' => add_query_arg(array(
                    'action' => 'docmanager_download',
                    'doc_id' => $doc->id,
                    'nonce' => wp_create_nonce('docmanager_download_' . $doc->id)
                ), admin_url('admin-ajax.php'))
            );
        }
        
        wp_send_json_success(array(
            'documents' => $formatted_docs,
            'total' => count($formatted_docs)
        ));
    }
    
    public function handle_update() {
        check_ajax_referer('docmanager_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        $doc_id = intval($_POST['doc_id']);
        $title = sanitize_text_field($_POST['title']);
        $notes = sanitize_textarea_field($_POST['notes']);
        $user_id = intval($_POST['user_id']);
        
        $update_data = array(
            'title' => $title,
            'notes' => $notes,
            'user_id' => $user_id
        );
        
        if ($this->db->update_document($doc_id, $update_data)) {
            wp_send_json_success('Documento aggiornato con successo');
        } else {
            wp_send_json_error('Errore nell\'aggiornamento del documento');
        }
    }
    
    private function process_file_upload($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array('success' => false, 'error' => 'Errore durante il caricamento del file');
        }
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_types = DocManager::get_allowed_file_types();
        
        if (!in_array($file_extension, $allowed_types)) {
            return array('success' => false, 'error' => 'Tipo di file non consentito');
        }
        
        if ($file['size'] > DocManager::get_max_file_size()) {
            return array('success' => false, 'error' => 'File troppo grande');
        }
        
        $upload_dir = wp_upload_dir();
        $docmanager_dir = $upload_dir['basedir'] . '/docmanager';
        
        if (!file_exists($docmanager_dir)) {
            wp_mkdir_p($docmanager_dir);
        }
        
        $filename = wp_unique_filename($docmanager_dir, $file['name']);
        $file_path = $docmanager_dir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            return array(
                'success' => true,
                'file_path' => 'docmanager/' . $filename,
                'file_type' => $file_extension,
                'file_size' => $file['size']
            );
        } else {
            return array('success' => false, 'error' => 'Errore nel salvataggio del file');
        }
    }
}