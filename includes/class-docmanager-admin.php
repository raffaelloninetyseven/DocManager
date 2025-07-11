<?php
/**
 * Classe per la gestione dell'area admin di DocManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Admin {
    
    private $db;
    
    public function __construct() {
        $this->db = new DocManager_Database();
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'DocManager',
            'DocManager',
            'manage_options',
            'docmanager',
            array($this, 'admin_page'),
            'dashicons-media-document',
            30
        );
        
        add_submenu_page(
            'docmanager',
            'Tutti i Documenti',
            'Tutti i Documenti',
            'manage_options',
            'docmanager',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'docmanager',
            'Carica Documento',
            'Carica Documento',
            'manage_options',
            'docmanager-upload',
            array($this, 'upload_page')
        );
        
        add_submenu_page(
            'docmanager',
            'Impostazioni',
            'Impostazioni',
            'manage_options',
            'docmanager-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_init() {
        register_setting('docmanager_settings', 'docmanager_max_file_size');
        register_setting('docmanager_settings', 'docmanager_allowed_types');
        
        add_settings_section(
            'docmanager_general',
            'Impostazioni Generali',
            array($this, 'settings_section_callback'),
            'docmanager_settings'
        );
        
        add_settings_field(
            'docmanager_max_file_size',
            'Dimensione Massima File (bytes)',
            array($this, 'max_file_size_callback'),
            'docmanager_settings',
            'docmanager_general'
        );
        
        add_settings_field(
            'docmanager_allowed_types',
            'Tipi di File Consentiti',
            array($this, 'allowed_types_callback'),
            'docmanager_settings',
            'docmanager_general'
        );
    }
    
    public function admin_page() {
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;
        
        $documents = $this->db->get_all_documents($per_page, $offset);
        $total_documents = $this->db->get_documents_count();
        $total_pages = ceil($total_documents / $per_page);
        
        if (isset($_POST['action']) && $_POST['action'] == 'delete_document') {
            $doc_id = intval($_POST['doc_id']);
            if ($this->db->delete_document($doc_id)) {
                echo '<div class="notice notice-success"><p>Documento eliminato con successo.</p></div>';
                $documents = $this->db->get_all_documents($per_page, $offset);
            }
        }
        
        echo '<div class="wrap">';
        echo '<h1>DocManager - Gestione Documenti</h1>';
        
        echo '<div class="docmanager-stats">';
        echo '<div class="docmanager-stat-box">';
        echo '<h3>Totale Documenti</h3>';
        echo '<span class="stat-number">' . $total_documents . '</span>';
        echo '</div>';
        echo '</div>';
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Titolo</th>';
        echo '<th>Utente</th>';
        echo '<th>Tipo File</th>';
        echo '<th>Dimensione</th>';
        echo '<th>Data Caricamento</th>';
        echo '<th>Caricato da</th>';
        echo '<th>Azioni</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        if ($documents) {
            foreach ($documents as $doc) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($doc->title) . '</strong>';
                if ($doc->notes) {
                    echo '<br><small>' . esc_html($doc->notes) . '</small>';
                }
                echo '</td>';
                echo '<td>' . esc_html($doc->user_name) . '</td>';
                echo '<td>' . esc_html(strtoupper($doc->file_type)) . '</td>';
                echo '<td>' . DocManager::format_file_size($doc->file_size) . '</td>';
                echo '<td>' . date('d/m/Y H:i', strtotime($doc->upload_date)) . '</td>';
                echo '<td>' . esc_html($doc->uploaded_by_name) . '</td>';
                echo '<td>';
                echo '<a href="' . esc_url(admin_url('admin.php?page=docmanager-upload&edit=' . $doc->id)) . '" class="button button-small">Modifica</a> ';
                echo '<button class="button button-small button-link-delete" onclick="deleteDocument(' . $doc->id . ')">Elimina</button>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7">Nessun documento trovato.</td></tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        if ($total_pages > 1) {
            echo '<div class="tablenav">';
            echo '<div class="tablenav-pages">';
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $current_page
            ));
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        echo '<form id="delete-document-form" method="post" style="display:none;">';
        echo '<input type="hidden" name="action" value="delete_document">';
        echo '<input type="hidden" name="doc_id" id="delete-doc-id" value="">';
        echo wp_nonce_field('docmanager_delete', 'docmanager_nonce');
        echo '</form>';
        
        echo '<script>';
        echo 'function deleteDocument(docId) {';
        echo '    if (confirm("Sei sicuro di voler eliminare questo documento?")) {';
        echo '        document.getElementById("delete-doc-id").value = docId;';
        echo '        document.getElementById("delete-document-form").submit();';
        echo '    }';
        echo '}';
        echo '</script>';
    }
    
    public function upload_page() {
        $edit_doc = null;
        if (isset($_GET['edit'])) {
            $edit_doc = $this->db->get_document_by_id(intval($_GET['edit']));
        }
        
        if (isset($_POST['submit'])) {
            $this->handle_upload();
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . ($edit_doc ? 'Modifica Documento' : 'Carica Nuovo Documento') . '</h1>';
        
        echo '<form method="post" enctype="multipart/form-data">';
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th scope="row">Titolo</th>';
        echo '<td><input type="text" name="title" value="' . ($edit_doc ? esc_attr($edit_doc->title) : '') . '" class="regular-text" required></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row">Utente</th>';
        echo '<td>';
        wp_dropdown_users(array(
            'name' => 'user_id',
            'selected' => $edit_doc ? $edit_doc->user_id : '',
            'show_option_none' => 'Seleziona utente...',
            'option_none_value' => ''
        ));
        echo '</td>';
        echo '</tr>';
        
        if (!$edit_doc) {
            echo '<tr>';
            echo '<th scope="row">File</th>';
            echo '<td>';
            echo '<input type="file" name="document_file" accept=".' . implode(',.', DocManager::get_allowed_file_types()) . '" required>';
            echo '<p class="description">Tipi consentiti: ' . implode(', ', DocManager::get_allowed_file_types()) . '</p>';
            echo '<p class="description">Dimensione massima: ' . DocManager::format_file_size(DocManager::get_max_file_size()) . '</p>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '<tr>';
        echo '<th scope="row">Note</th>';
        echo '<td><textarea name="notes" rows="4" cols="50" class="large-text">' . ($edit_doc ? esc_textarea($edit_doc->notes) : '') . '</textarea></td>';
        echo '</tr>';
        
        echo '</table>';
        
        if ($edit_doc) {
            echo '<input type="hidden" name="edit_id" value="' . $edit_doc->id . '">';
        }
        
        echo '<p class="submit">';
        echo '<input type="submit" name="submit" class="button-primary" value="' . ($edit_doc ? 'Aggiorna Documento' : 'Carica Documento') . '">';
        echo '</p>';
        
        echo wp_nonce_field('docmanager_upload', 'docmanager_nonce');
        echo '</form>';
        
        echo '</div>';
    }
    
    public function settings_page() {
        echo '<div class="wrap">';
        echo '<h1>Impostazioni DocManager</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('docmanager_settings');
        do_settings_sections('docmanager_settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }
    
    public function settings_section_callback() {
        echo '<p>Configura le impostazioni generali per DocManager.</p>';
    }
    
    public function max_file_size_callback() {
        $value = get_option('docmanager_max_file_size', 10485760);
        echo '<input type="number" name="docmanager_max_file_size" value="' . esc_attr($value) . '" />';
        echo '<p class="description">Dimensione massima del file in bytes (default: 10MB = 10485760 bytes)</p>';
    }
    
    public function allowed_types_callback() {
        $value = get_option('docmanager_allowed_types', 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip');
        echo '<input type="text" name="docmanager_allowed_types" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Tipi di file consentiti, separati da virgola (es: pdf,doc,jpg)</p>';
    }
    
    private function handle_upload() {
        if (!wp_verify_nonce($_POST['docmanager_nonce'], 'docmanager_upload')) {
            wp_die('Nonce non valido');
        }
        
        if (isset($_POST['edit_id'])) {
            $doc_id = intval($_POST['edit_id']);
            $update_data = array(
                'title' => $_POST['title'],
                'user_id' => $_POST['user_id'],
                'notes' => $_POST['notes']
            );
            
            if ($this->db->update_document($doc_id, $update_data)) {
                echo '<div class="notice notice-success"><p>Documento aggiornato con successo.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Errore nell\'aggiornamento del documento.</p></div>';
            }
        } else {
            $upload_result = $this->process_file_upload();
            
            if ($upload_result['success']) {
                $doc_data = array(
                    'title' => $_POST['title'],
                    'file_path' => $upload_result['file_path'],
                    'file_type' => $upload_result['file_type'],
                    'file_size' => $upload_result['file_size'],
                    'user_id' => $_POST['user_id'],
                    'uploaded_by' => get_current_user_id(),
                    'notes' => $_POST['notes']
                );
                
                if ($this->db->insert_document($doc_data)) {
                    echo '<div class="notice notice-success"><p>Documento caricato con successo.</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Errore nel salvataggio del documento.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>' . $upload_result['error'] . '</p></div>';
            }
        }
    }
    
    private function process_file_upload() {
        if (!isset($_FILES['document_file'])) {
            return array('success' => false, 'error' => 'Nessun file selezionato');
        }
        
        $file = $_FILES['document_file'];
        
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