<?php
/**
 * Classe per gestire l'upload e il download dei file
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_FileHandler {
    
    public function __construct() {
        // Assicurarsi che la directory esista
        if (!file_exists(DOCMANAGER_UPLOAD_DIR)) {
            wp_mkdir_p(DOCMANAGER_UPLOAD_DIR);
        }
    }
    
    /**
     * Carica un file
     */
    public function uploadFile($file_data, $document_id) {
        if (!isset($file_data['tmp_name']) || !is_uploaded_file($file_data['tmp_name'])) {
            return false;
        }
        
        $security = new DocManager_Security();
        
        // Verificare la sicurezza del file
        if (!$security->isFileSecure($file_data['tmp_name'], $file_data['type'])) {
            return false;
        }
        
        // Generare nome file unico
        $unique_filename = $security->generateUniqueFileName($file_data['name']);
        $file_path = DOCMANAGER_UPLOAD_DIR . $unique_filename;
        
        // Spostare il file
        if (move_uploaded_file($file_data['tmp_name'], $file_path)) {
            // Creare ID file unico
            $file_id = wp_generate_password(32, false);
            
            return array(
                'file_id' => $file_id,
                'file_name' => $file_data['name'],
                'file_path' => $file_path,
                'file_size' => $file_data['size'],
                'file_type' => $file_data['type'],
                'unique_filename' => $unique_filename
            );
        }
        
        return false;
    }
    
    /**
     * Serve un file per il download
     */
    public function serveFile($file_id) {
        // Trovare il documento
        $posts = get_posts(array(
            'post_type' => 'referto',
            'meta_key' => '_docmanager_file_id',
            'meta_value' => $file_id,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        
        if (empty($posts)) {
            wp_die(__('File non trovato', 'docmanager'));
        }
        
        $document = $posts[0];
        $file_name = get_post_meta($document->ID, '_docmanager_file_name', true);
        $file_path = $this->getFilePath($document->ID);
        
        if (!file_exists($file_path)) {
            wp_die(__('File fisico non trovato', 'docmanager'));
        }
        
        // Determinare il tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        // Impostare gli header
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Pulire l'output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Inviare il file
        readfile($file_path);
        exit;
    }
    
    /**
     * Elimina un file
     */
    public function deleteFile($document_id) {
        $file_path = $this->getFilePath($document_id);
        
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        
        return true;
    }
    
    /**
     * Ottiene il percorso del file
     */
    private function getFilePath($document_id) {
        $file_name = get_post_meta($document_id, '_docmanager_file_name', true);
        $file_id = get_post_meta($document_id, '_docmanager_file_id', true);
        
        // Cercare il file nella directory
        $files = glob(DOCMANAGER_UPLOAD_DIR . '*');
        
        foreach ($files as $file) {
            if (strpos(basename($file), $file_id) !== false) {
                return $file;
            }
        }
        
        return null;
    }
    
    /**
     * Ottiene informazioni sul file
     */
    public function getFileInfo($document_id) {
        $file_path = $this->getFilePath($document_id);
        
        if (!$file_path || !file_exists($file_path)) {
            return false;
        }
        
        $file_name = get_post_meta($document_id, '_docmanager_file_name', true);
        $file_size = get_post_meta($document_id, '_docmanager_file_size', true);
        $file_type = get_post_meta($document_id, '_docmanager_file_type', true);
        $upload_date = get_post_meta($document_id, '_docmanager_upload_date', true);
        
        return array(
            'name' => $file_name,
            'size' => $file_size,
            'type' => $file_type,
            'upload_date' => $upload_date,
            'path' => $file_path
        );
    }
    
    /**
     * Genera URL per il download
     */
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
    
    /**
     * Genera URL per la visualizzazione
     */
    public function getViewUrl($document_id) {
        $file_info = $this->getFileInfo($document_id);
        
        if (!$file_info) {
            return false;
        }
        
        // Solo per file PDF, immagini
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
    
    /**
     * Verifica se un file è visualizzabile
     */
    public function isViewable($document_id) {
        $file_info = $this->getFileInfo($document_id);
        
        if (!$file_info) {
            return false;
        }
        
        $viewable_types = array('application/pdf', 'image/jpeg', 'image/png', 'image/gif');
        
        return in_array($file_info['type'], $viewable_types);
    }
    
    /**
     * Ottiene l'icona per il tipo di file
     */
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
    
    /**
     * Pulizia file orfani
     */
    public function cleanupOrphanFiles() {
        $files = glob(DOCMANAGER_UPLOAD_DIR . '*');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $basename = basename($file);
                
                // Cercare documenti che usano questo file
                $posts = get_posts(array(
                    'post_type' => 'referto',
                    'meta_query' => array(
                        array(
                            'key' => '_docmanager_file_id',
                            'value' => $basename,
                            'compare' => 'LIKE'
                        )
                    ),
                    'posts_per_page' => 1
                ));
                
                // Se nessun documento usa questo file, eliminarlo
                if (empty($posts)) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
}