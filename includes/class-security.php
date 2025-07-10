<?php
/**
 * Classe per gestire la sicurezza e la protezione delle pagine
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Security {
    
    public function __construct() {
        add_action('wp', array($this, 'checkPageAccess'));
        add_action('init', array($this, 'handleFileDownload'));
        add_action('wp_login', array($this, 'handleLoginRedirect'), 10, 2);
        add_filter('login_redirect', array($this, 'loginRedirectFilter'), 10, 3);
    }
    
    /**
     * Verifica accesso alle pagine protette
     */
    public function checkPageAccess() {
        if (is_admin()) {
            return;
        }
        
        $options = get_option('docmanager_options');
        $protected_pages = isset($options['protected_pages']) ? $options['protected_pages'] : array();
        
        if (empty($protected_pages)) {
            return;
        }
        
        $current_page_id = get_queried_object_id();
        
        // Verificare se la pagina corrente è protetta
        if (in_array($current_page_id, $protected_pages)) {
            if (!is_user_logged_in()) {
                // Salvare l'URL di destinazione in sessione
                if (!session_id()) {
                    session_start();
                }
                $_SESSION['docmanager_redirect_to'] = home_url(add_query_arg(array()));
                
                // Reindirizzare alla pagina di login
                wp_redirect(wp_login_url());
                exit;
            }
        }
    }
    
    /**
     * Gestisce il download sicuro dei file
     */
    public function handleFileDownload() {
        if (isset($_GET['docmanager_download']) && isset($_GET['file_id'])) {
            $file_id = sanitize_text_field($_GET['file_id']);
            $nonce = sanitize_text_field($_GET['nonce']);
            
            // Verificare nonce
            if (!wp_verify_nonce($nonce, 'docmanager_download_' . $file_id)) {
                wp_die(__('Accesso non autorizzato', 'docmanager'));
            }
            
            // Verificare se l'utente è loggato
            if (!is_user_logged_in()) {
                wp_die(__('Devi effettuare il login per scaricare i file', 'docmanager'));
            }
            
            // Trovare il documento
            $document = $this->getDocumentByFileId($file_id);
            
            if (!$document) {
                wp_die(__('File non trovato', 'docmanager'));
            }
            
            // Verificare i permessi
            if (!$this->userCanAccessDocument($document->ID, get_current_user_id())) {
                wp_die(__('Non hai i permessi per accedere a questo file', 'docmanager'));
            }
            
            // Log dell'accesso
            $this->logAccess($document->ID, 'download');
            
            // Servire il file
            $file_handler = new DocManager_FileHandler();
            $file_handler->serveFile($file_id);
        }
    }
    
    /**
     * Gestisce il redirect dopo il login
     */
    public function handleLoginRedirect($user_login, $user) {
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION['docmanager_redirect_to'])) {
            $redirect_to = $_SESSION['docmanager_redirect_to'];
            unset($_SESSION['docmanager_redirect_to']);
            wp_redirect($redirect_to);
            exit;
        }
    }
    
    /**
     * Filtro per il redirect di login
     */
    public function loginRedirectFilter($redirect_to, $request, $user) {
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION['docmanager_redirect_to'])) {
            $redirect_to = $_SESSION['docmanager_redirect_to'];
            unset($_SESSION['docmanager_redirect_to']);
        }
        
        return $redirect_to;
    }
    
    /**
     * Verifica se un utente può accedere a un documento
     */
    public function userCanAccessDocument($document_id, $user_id) {
        // Gli admin possono accedere a tutto
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Verificare se il documento è assegnato all'utente
        $assigned_user = get_post_meta($document_id, '_docmanager_assigned_user', true);
        
        if ($assigned_user && $assigned_user == $user_id) {
            return true;
        }
        
        // Verificare se il documento è scaduto
        $expiry_date = get_post_meta($document_id, '_docmanager_expiry_date', true);
        if ($expiry_date && strtotime($expiry_date) < current_time('timestamp')) {
            return false;
        }
        
        return false;
    }
    
    /**
     * Trova un documento tramite file ID
     */
    private function getDocumentByFileId($file_id) {
        $posts = get_posts(array(
            'post_type' => 'referto',
            'meta_key' => '_docmanager_file_id',
            'meta_value' => $file_id,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        
        return !empty($posts) ? $posts[0] : null;
    }
    
    /**
     * Registra l'accesso ai documenti
     */
    public function logAccess($document_id, $action) {
        $options = get_option('docmanager_options');
        
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
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Ottiene l'IP dell'utente
     */
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
        
        return $_SERVER['REMOTE_ADDR'];
    }
    
    /**
     * Verifica se un file è sicuro
     */
    public function isFileSecure($file_path, $file_type) {
        $options = get_option('docmanager_options');
        $allowed_types = isset($options['allowed_file_types']) ? $options['allowed_file_types'] : 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif';
        $allowed_types = array_map('trim', explode(',', $allowed_types));
        
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            return false;
        }
        
        // Verificare la dimensione del file
        $max_size = isset($options['max_file_size']) ? $options['max_file_size'] : 10;
        $max_size_bytes = $max_size * 1024 * 1024; // Convertire in bytes
        
        if (filesize($file_path) > $max_size_bytes) {
            return false;
        }
        
        // Verificare il MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        $allowed_mime_types = array(
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        );
        
        $expected_mime = isset($allowed_mime_types[$file_extension]) ? $allowed_mime_types[$file_extension] : '';
        
        if ($expected_mime && $detected_type !== $expected_mime) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitizza il nome del file
     */
    public function sanitizeFileName($filename) {
        // Rimuovere caratteri speciali
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Limitare la lunghezza
        if (strlen($filename) > 255) {
            $filename = substr($filename, 0, 255);
        }
        
        return $filename;
    }
    
    /**
     * Genera un nome file unico
     */
    public function generateUniqueFileName($original_filename) {
        $file_info = pathinfo($original_filename);
        $name = $this->sanitizeFileName($file_info['filename']);
        $extension = isset($file_info['extension']) ? $file_info['extension'] : '';
        
        $unique_id = uniqid() . '_' . time();
        
        return $name . '_' . $unique_id . '.' . $extension;
    }
}