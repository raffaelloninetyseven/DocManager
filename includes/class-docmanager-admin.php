<?php
if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Admin {
    
    private $db;
    
    public function __construct() {
        $this->db = new DocManager_DB();
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_docmanager_upload', array($this, 'handle_upload'));
        add_action('wp_ajax_docmanager_delete', array($this, 'handle_delete'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Document Manager',
            'Document Manager',
            'manage_options',
            'docmanager',
            array($this, 'admin_page'),
            'dashicons-media-document',
            26
        );
        
        add_submenu_page(
            'docmanager',
            'Documenti',
            'Documenti',
            'manage_options',
            'docmanager',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'docmanager',
            'Impostazioni',
            'Impostazioni',
            'manage_options',
            'docmanager-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'docmanager',
            'Permessi',
            'Permessi',
            'manage_options',
            'docmanager-permissions',
            array($this, 'permissions_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'docmanager') !== false) {
            wp_enqueue_media();
            wp_enqueue_script('docmanager-admin', DOCMANAGER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DOCMANAGER_VERSION, true);
            wp_enqueue_style('docmanager-admin', DOCMANAGER_PLUGIN_URL . 'assets/css/admin.css', array(), DOCMANAGER_VERSION);
            
            wp_localize_script('docmanager-admin', 'docmanager_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('docmanager_nonce')
            ));
        }
    }
    
    public function admin_page() {
        if (isset($_POST['upload_document'])) {
            $this->process_upload();
        }
        
        $documents = $this->db->get_documents_by_category();
        ?>
        <div class="wrap">
            <h1>Document Manager</h1>
            
            <div class="docmanager-upload-section">
                <h2>Carica Nuovo Documento</h2>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('docmanager_upload', 'docmanager_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="doc_title">Titolo</label></th>
                            <td><input type="text" id="doc_title" name="doc_title" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="doc_description">Descrizione</label></th>
                            <td><textarea id="doc_description" name="doc_description" rows="3" class="large-text"></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="doc_file">File</label></th>
                            <td><input type="file" id="doc_file" name="doc_file" required></td>
                        </tr>
                        <tr>
                            <th><label for="doc_category">Categoria</label></th>
                            <td><input type="text" id="doc_category" name="doc_category" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="doc_tags">Tags</label></th>
                            <td><input type="text" id="doc_tags" name="doc_tags" class="regular-text" placeholder="Separati da virgola"></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="upload_document" class="button-primary" value="Carica Documento">
                    </p>
                </form>
            </div>
            
            <div class="docmanager-documents-list">
                <h2>Documenti Caricati</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Titolo</th>
                            <th>Categoria</th>
                            <th>Tipo File</th>
                            <th>Dimensione</th>
                            <th>Data Upload</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><strong><?php echo esc_html($doc->title); ?></strong></td>
                            <td><?php echo esc_html($doc->category); ?></td>
                            <td><?php echo esc_html($doc->file_type); ?></td>
                            <td><?php echo size_format($doc->file_size); ?></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($doc->upload_date)); ?></td>
                            <td>
                                <a href="<?php echo esc_url($doc->file_path); ?>" class="button button-small" target="_blank">Visualizza</a>
                                <a href="#" class="button button-small button-link-delete" data-doc-id="<?php echo $doc->id; ?>">Elimina</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['save_settings'])) {
            update_option('docmanager_block_admin_access', $_POST['block_admin_access'] ?? '');
            update_option('docmanager_blocked_roles', $_POST['blocked_roles'] ?? array());
            update_option('docmanager_enable_logs', $_POST['enable_logs'] ?? '');
            echo '<div class="notice notice-success"><p>Impostazioni salvate!</p></div>';
        }
        
        $blocked_roles = get_option('docmanager_blocked_roles', array());
        $enable_logs = get_option('docmanager_enable_logs', 'yes');
        ?>
        <div class="wrap">
            <h1>Impostazioni Document Manager</h1>
            
            <form method="post">
                <?php wp_nonce_field('docmanager_settings', 'docmanager_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Blocca Accesso Admin</th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">Blocca accesso alla dashboard</legend>
                                <label for="block_admin_access">
                                    <input name="block_admin_access" type="checkbox" id="block_admin_access" value="1" <?php checked(get_option('docmanager_block_admin_access')); ?>>
                                    Blocca l'accesso a /wp-admin per ruoli specifici
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Ruoli Bloccati</th>
                        <td>
                            <fieldset>
                                <?php
                                $roles = wp_roles()->roles;
                                foreach ($roles as $role_key => $role) {
                                    if ($role_key !== 'administrator') {
                                        $checked = in_array($role_key, $blocked_roles) ? 'checked' : '';
                                        echo '<label><input type="checkbox" name="blocked_roles[]" value="' . $role_key . '" ' . $checked . '> ' . $role['name'] . '</label><br>';
                                    }
                                }
                                ?>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Abilita Logs</th>
                        <td>
                            <label for="enable_logs">
                                <input name="enable_logs" type="checkbox" id="enable_logs" value="yes" <?php checked($enable_logs, 'yes'); ?>>
                                Registra le azioni sui documenti
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_settings" class="button-primary" value="Salva Impostazioni">
                </p>
            </form>
        </div>
        <?php
    }
    
    public function permissions_page() {
        echo '<div class="wrap"><h1>Gestione Permessi</h1>';
        echo '<p>Interfaccia per gestire i permessi dei documenti per utenti e ruoli.</p>';
        echo '</div>';
    }
    
    private function process_upload() {
        if (!wp_verify_nonce($_POST['docmanager_nonce'], 'docmanager_upload')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $upload_dir = wp_upload_dir();
        $docmanager_dir = $upload_dir['basedir'] . '/docmanager/';
        
        if (!file_exists($docmanager_dir)) {
            wp_mkdir_p($docmanager_dir);
        }
        
        $file = $_FILES['doc_file'];
        $filename = sanitize_file_name($file['name']);
        $filepath = $docmanager_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $document_data = array(
                'title' => sanitize_text_field($_POST['doc_title']),
                'description' => sanitize_textarea_field($_POST['doc_description']),
                'file_name' => $filename,
                'file_path' => $upload_dir['baseurl'] . '/docmanager/' . $filename,
                'file_size' => $file['size'],
                'file_type' => $file['type'],
                'category' => sanitize_text_field($_POST['doc_category']),
                'tags' => sanitize_text_field($_POST['doc_tags']),
                'uploaded_by' => get_current_user_id(),
                'status' => 'active'
            );
            
            $this->db->insert_document($document_data);
            echo '<div class="notice notice-success"><p>Documento caricato con successo!</p></div>';
        }
    }
}