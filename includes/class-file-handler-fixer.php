<?php
/**
 * Classe per gestire l'upload e il download dei file (versione corretta)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_FileHandler {
    
    public function __construct() {
        $this->ensureUploadDir();
        add_action('init', array($this, 'handleFileRequests'));
    }
    
    private function ensureUploadDir() {
        if (!file_exists(DOCMANAGER_UPLOAD_DIR)) {
            wp_mkdir_p(DOCMANAGER_UPLOAD_DIR);
            
            $htaccess_content = "deny from all\n";
            file_put_contents(DOCMANAGER_UPLOAD_DIR . '.htaccess', $htaccess_content);
            
            $index_content = "<?php\n// Silence is golden.\n";
            file_put_contents(DOCMANAGER_UPLOAD_DIR . 'index.php', $index_content);
        }
    }
    
    public function handleFileRequests() {
        if (isset($_GET['docmanager_download']) && isset($_GET['file_id'])) {
            $this->serveFileDownload();
        }
        
        if (isset($_GET['docmanager_view']) && isset($_GET['file_id'])) {
            $this->serveFileView();
        }
    }
    
    public function uploadFile($file_data, $document_id) {
        if (!isset($file_data['tmp_name']) || !is_uploaded_file($file_data['tmp_name'])) {
            error_log('DocManager: File non valido o non caricato');
            return false;
        }
        
        if (!$this->validateFile($file_data)) {
            error_log('DocManager: Validazione file fallita');
            return false;
        }
        
        $unique_filename = $this->generateUniqueFileName($file_data['name']);
        $file_path = DOCMANAGER_UPLOAD_DIR . $unique_filename;
        
        if (!move_uploaded_file($file_data['tmp_name'], $file_path)) {
            error_log('DocManager: Impossibile spostare file in ' . $file_path);
            return false;
        }
        
        chmod($file_path, 0644);
        
        $file_id = wp_generate_password(32, false);
        
        $result = array(
            'file_id' => $file_id,
            'file_name' => sanitize_file_name($file_data['name']),
            'file_path' => $file_path,
            'file_size' => $file_data['size'],
            'file_type' => $file_data['type'],
            'unique_filename' => $unique_filename
        );
        
        error_log('DocManager: File caricato con successo - ' . $unique_filename);
        return $result;
    }
    
    private function validateFile($file_data) {
        $options = get_option('docmanager_options', array());
        $allowed_types = isset($options['allowed_file_types']) ? $options['allowed_file_types'] : 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif';
        $max_size = isset($options['max_file_size']) ? intval($options['max_file_size']) : 10;
        
        $allowed_extensions = array_map('trim', explode(',', strtolower($allowed_types)));
        $file_extension = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            error_log('DocManager: Estensione file non consentita: ' . $file_extension);
            return false;
        }
        
        $max_size_bytes = $max_size * 1024 * 1024;
        if ($file_data['size'] > $max_size_bytes) {
            error_log('DocManager: File troppo grande: ' . $file_data['size'] . ' bytes');
            return false;
        }
        
        $allowed_mime_types = array(
            'pdf' => array('application/pdf'),
            'doc' => array('application/msword'),
            'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'xls' => array('application/vnd.ms-excel'),
            'xlsx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            'jpg' => array('image/jpeg'),
            'jpeg' => array('image/jpeg'),
            'png' => array('image/png'),
            'gif' => array('image/gif')
        );
        
        if (isset($allowed_mime_types[$file_extension])) {
            $valid_mimes = $allowed_mime_types[$file_extension];
            if (!in_array($file_data['type'], $valid_mimes)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $detected_type = finfo_file($finfo, $file_data['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($detected_type, $valid_mimes)) {
                    error_log('DocManager: MIME type non valido: ' . $file_data['type'] . ' / ' . $detected_type);
                    return false;
                }
            }
        }
        
        return true;
    }
    
    private function generateUniqueFileName($original_filename) {
        $file_info = pathinfo($original_filename);
        $name = sanitize_file_name($file_info['filename']);
        $extension = isset($file_info['extension']) ? $file_info['extension'] : '';
        
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $name = substr($name, 0, 50);
        
        $unique_id = uniqid() . '_' . time();
        
        return $name . '_' . $unique_id . '.' . $extension;
    }
    
    private function serveFileDownload() {
        $file_id = sanitize_text_field($_GET['file_id']);
        $nonce = sanitize_text_field($_GET['nonce']);
        
        if (!wp_verify_nonce($nonce, 'docmanager_download_' . $file_id)) {
            wp_die(__('Accesso non autorizzato', 'docmanager'));
        }
        
        if (!is_user_logged_in()) {
            wp_die(__('Devi effettuare il login per scaricare i file', 'docmanager'));
        }
        
        $document = $this->getDocumentByFileId($file_id);
        if (!$document) {
            wp_die(__('File non trovato', 'docmanager'));
        }
        
        if (!$this->userCanAccessDocument($document->ID, get_current_user_id())) {
            wp_die(__('Non hai i permessi per accedere a questo file', 'docmanager'));
        }
        
        $this->logAccess($document->ID, 'download');
        $this->outputFile($document->ID, false);
    }
    
    private function serveFileView() {
        $file_id = sanitize_text_field($_GET['file_id']);
        $nonce = sanitize_text_field($_GET['nonce']);
        
        if (!wp_verify_nonce($nonce, 'docmanager_view_' . $file_id)) {
            wp_die(__('Accesso non autorizzato', 'docmanager'));
        }
        
        if (!is_user_logged_in()) {
            wp_die(__('Devi effettuare il login per visualizzare i file', 'docmanager'));
        }
        
        $document = $this->getDocumentByFileId($file_id);
        if (!$document) {
            wp_die(__('File non trovato', 'docmanager'));
        }
        
        if (!$this->userCanAccessDocument($document->ID, get_current_user_id())) {
            wp_die(__('Non hai i permessi per accedere a questo file', 'docmanager'));
        }
        
        $this->logAccess($document->ID, 'view');
        $this->outputFile($document->ID, true);
    }
    
    private function outputFile($document_id, $inline = false) {
        $file_path = $this->getFilePath($document_id);
        $file_name = get_post_meta($document_id, '_docmanager_file_name', true);
        
        if (!file_exists($file_path)) {
            wp_die(__('File fisico non trovato', 'docmanager'));
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: ' . $mime_type);
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        if ($inline && in_array($mime_type, array('application/pdf', 'image/jpeg', 'image/png', 'image/gif'))) {
            header('Content-Disposition: inline; filename="' . $file_name . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
        }
        
        readfile($file_path);
        exit;
    }
    
    public function deleteFile($document_id) {
        $file_path = $this->getFilePath($document_id);
        
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        
        return true;
    }
    
    private function getFilePath($document_id) {
        $unique_filename = get_post_meta($document_id, '_docmanager_unique_filename', true);
        
        if ($unique_filename && file_exists(DOCMANAGER_UPLOAD_DIR . $unique_filename)) {
            return DOCMANAGER_UPLOAD_DIR . $unique_filename;
        }
        
        $file_id = get_post_meta($document_id, '_docmanager_file_id', true);
        if ($file_id) {
            $files = glob(DOCMANAGER_UPLOAD_DIR . '*' . $file_id . '*');
            if (!empty($files)) {
                return $files[0];
            }
        }
        
        return null;
    }
    
    public function getFileInfo($document_id) {
        $file_path = $this->getFilePath($document_id);
        
        if (!$file_path || !file_exists($file_path)) {
            return false;
        }
        
        $file_name = get_post_meta($document_id, '_docmanager_file_name', true);
        $file_size = get_post_meta($document_id, '_docmanager_file_size', true);
        $file_type = get_post_meta($document_id, '_docmanager_file_type', true);
        $upload_date = get_post_meta($document_id, '_docmanager_upload_date', true);
        
        if (!$file_size) {
            $file_size = filesize($file_path);
            update_post_meta($document_id, '_docmanager_file_size', $file_size);
        }
        
        if (!$file_type) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $file_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);
            update_post_meta($document_id, '_docmanager_file_type', $file_type);
        }
        
        return array(
            'name' => $file_name,
            'size' => $file_size,
            'type' => $file_type,
            'upload_date' => $upload_date,
            'path' => $file_path
        );
    }
    
    public function getDownloadUrl($document_id) {
        $file_id = get_post_meta($document_id, '_docmanager_file_id', true);
        
        if (!$file_id) {
            return false;
        }
        
        $nonce = wp_create_nonce('docmanager_download_' . $file_id);
        
        return add_query_arg(array(
            'docmanager_download' => '1',
            'file_id' => $file_id,
            'nonce' => $nonce
        ), home_url());
    }
    
    public function getViewUrl($document_id) {
        $file_info = $this->getFileInfo($document_id);
        
        if (!$file_info) {
            return false;
        }
        
        $viewable_types = array('application/pdf', 'image/jpeg', 'image/png', 'image/gif');
        
        if (in_array($file_info['type'], $viewable_types)) {
            $file_id = get_post_meta($document_id, '_docmanager_file_id', true);
            $nonce = wp_create_nonce('docmanager_view_' . $file_id);
            
            return add_query_arg(array(
                'docmanager_view' => '1',
                'file_id' => $file_id,
                'nonce' => $nonce
            ), home_url());
        }
        
        return false;
    }
    
    public function isViewable($document_id) {
        $file_info = $this->getFileInfo($document_id);
        
        if (!$file_info) {
            return false;
        }
        
        $viewable_types = array('application/pdf', 'image/jpeg', 'image/png', 'image/gif');
        
        return in_array($file_info['type'], $viewable_types);
    }
    
    public function getFileIcon($file_type) {
        $icons = array(
            'application/pdf' => 'fa-file-pdf',
            'application/msword' => 'fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
            'application/vnd.ms-excel' => 'fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fa-file-excel',
            'image/jpeg' => 'fa-file-image',
            'image/png' => 'fa-file-image',
            'image/gif' => 'fa-file-image',
        );
        
        return isset($icons[$file_type]) ? $icons[$file_type] : 'fa-file';
    }
    
    private function getDocumentByFileId($file_id) {
        $posts = get_posts(array(
            'post_type' => 'referto',
            'meta_key' => '_docmanager_file_id',
            'meta_value' => $file_id,
            'posts_per_page' => 1,
            'post_status' => array('publish', 'draft', 'private')
        ));
        
        return !empty($posts) ? $posts[0] : null;
    }
    
    private function userCanAccessDocument($document_id, $user_id) {
        if (current_user_can('manage_options')) {
            return true;
        }
        
        $assigned_user = get_post_meta($document_id, '_docmanager_assigned_user', true);
        
        if ($assigned_user && $assigned_user == $user_id) {
            return true;
        }
        
        $expiry_date = get_post_meta($document_id, '_docmanager_expiry_date', true);
        if ($expiry_date && strtotime($expiry_date) < current_time('timestamp')) {
            return false;
        }
        
        return false;
    }
    
    private function logAccess($document_id, $action) {
        $options = get_option('docmanager_options', array());
        
        if (!isset($options['enable_logging']) || !$options['enable_logging']) {
            return;
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'docmanager_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => get_current_user_id(),
                'document_id' => $document_id,
                'action' => $action,
                'ip_address' => $this->getUserIP(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    private function getUserIP() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
    
    public function cleanupOrphanFiles() {
        $files = glob(DOCMANAGER_UPLOAD_DIR . '*');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && !in_array(basename($file), array('.htaccess', 'index.php'))) {
                $posts = get_posts(array(
                    'post_type' => 'referto',
                    'meta_query' => array(
                        array(
                            'key' => '_docmanager_unique_filename',
                            'value' => basename($file),
                            'compare' => '='
                        )
                    ),
                    'posts_per_page' => 1
                ));
                
                if (empty($posts)) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
}