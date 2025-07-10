<?php
if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Admin {
    
    private $db;
    private $permissions;
    
    public function __construct() {
        $this->db = new DocManager_DB();
        $this->permissions = new DocManager_Permissions();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'hide_admin_bar'));
        
        // AJAX handlers
        add_action('wp_ajax_docmanager_delete_document', array($this, 'ajax_delete_document'));
        add_action('wp_ajax_docmanager_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_docmanager_get_permission_targets', array($this, 'ajax_get_permission_targets'));
        add_action('wp_ajax_docmanager_remove_permission', array($this, 'ajax_remove_permission'));
        add_action('wp_ajax_docmanager_auto_save_setting', array($this, 'ajax_auto_save_setting'));
        add_action('wp_ajax_docmanager_bulk_action', array($this, 'ajax_bulk_action'));
		add_action('wp_ajax_docmanager_update_permissions', array($this, 'ajax_update_permissions'));
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
        
        add_submenu_page(
            'docmanager',
            'Statistiche',
            'Statistiche',
            'manage_options',
            'docmanager-stats',
            array($this, 'stats_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'docmanager') !== false) {
            wp_enqueue_media();
            wp_enqueue_script('docmanager-admin', DOCMANAGER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DOCMANAGER_VERSION, true);
            wp_enqueue_style('docmanager-admin', DOCMANAGER_PLUGIN_URL . 'assets/css/admin.css', array(), DOCMANAGER_VERSION);
            
            wp_localize_script('docmanager-admin', 'docmanager_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('docmanager_nonce'),
                'messages' => array(
                    'confirm_delete' => __('Are you sure you want to delete this document?', 'docmanager'),
                    'confirm_bulk_delete' => __('Are you sure you want to delete {count} documents?', 'docmanager'),
                    'document_deleted' => __('Document deleted successfully', 'docmanager'),
                    'documents_deleted' => __('Documents deleted successfully', 'docmanager'),
                    'error_occurred' => __('An error occurred', 'docmanager'),
                    'loading' => __('Loading...', 'docmanager'),
                    'saved' => __('Saved', 'docmanager'),
                    'saving' => __('Saving...', 'docmanager'),
                )
            ));
        }
    }
    
    public function hide_admin_bar() {
        if (!get_option('docmanager_hide_admin_bar')) {
            return;
        }
        
        $hidden_roles = get_option('docmanager_hidden_bar_roles', array());
        
        if (empty($hidden_roles)) {
            return;
        }
        
        $current_user = wp_get_current_user();
        
        // Non nascondere la barra agli amministratori
        if (in_array('administrator', $current_user->roles)) {
            return;
        }
        
        // Verifica se l'utente ha un ruolo per cui nascondere la barra
        foreach ($current_user->roles as $role) {
            if (in_array($role, $hidden_roles)) {
                show_admin_bar(false);
                break;
            }
        }
    }
    
    public function admin_page() {
        if (isset($_POST['upload_document'])) {
            $this->process_upload();
        }
        
        if (isset($_POST['bulk_action']) && isset($_POST['document']) && !empty($_POST['document'])) {
            $this->process_bulk_action();
        }
        
        $documents = $this->get_paginated_documents();
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wrap">
            <h1>Document Manager <span class="title-count">(<?php echo $stats['total_documents']; ?>)</span></h1>
            
            <!-- Dashboard Stats -->
            <div class="docmanager-stats">
                <div class="docmanager-stat-card">
                    <span class="stat-number"><?php echo $stats['total_documents']; ?></span>
                    <span class="stat-label">Documenti Totali</span>
                </div>
                <div class="docmanager-stat-card">
                    <span class="stat-number"><?php echo $stats['total_downloads']; ?></span>
                    <span class="stat-label">Download Totali</span>
                </div>
                <div class="docmanager-stat-card">
                    <span class="stat-number"><?php echo $stats['this_month']; ?></span>
                    <span class="stat-label">Questo Mese</span>
                </div>
                <div class="docmanager-stat-card">
                    <span class="stat-number"><?php echo $this->format_file_size($stats['storage_used']); ?></span>
                    <span class="stat-label">Spazio Utilizzato</span>
                </div>
            </div>
            
            <!-- Upload Form -->
            <div class="docmanager-upload-section">
                <h2>Carica Nuovo Documento</h2>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('docmanager_upload', 'docmanager_nonce'); ?>
                    
                    <div class="docmanager-upload-grid">
                        <div class="upload-left">
                            <table class="form-table">
                                <tr>
                                    <th><label for="doc_title">Titolo *</label></th>
                                    <td><input type="text" id="doc_title" name="doc_title" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label for="doc_description">Descrizione</label></th>
                                    <td><textarea id="doc_description" name="doc_description" rows="3" class="large-text"></textarea></td>
                                </tr>
                                <tr>
                                    <th><label for="doc_category">Categoria</label></th>
                                    <td>
                                        <input type="text" id="doc_category" name="doc_category" class="regular-text" list="categories">
                                        <datalist id="categories">
                                            <?php foreach ($this->get_existing_categories() as $category): ?>
                                                <option value="<?php echo esc_attr($category); ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="doc_tags">Tags</label></th>
                                    <td><input type="text" id="doc_tags" name="doc_tags" class="regular-text" placeholder="Separati da virgola"></td>
                                </tr>
                                <tr>
                                    <th><label for="document_visibility">Visibilità Documento</label></th>
                                    <td>
                                        <select id="document_visibility" name="document_visibility" class="regular-text">
                                            <option value="uploader_only">Solo chi carica</option>
                                            <option value="logged_users">Tutti gli utenti loggati</option>
                                            <option value="everyone">Tutti (pubblico)</option>
                                            <option value="specific_user">Utente specifico</option>
                                            <option value="specific_role">Ruolo specifico</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr id="specific_user_row" style="display: none;">
                                    <th><label for="specific_user_id">Seleziona Utente</label></th>
                                    <td>
                                        <select id="specific_user_id" name="specific_user_id" class="regular-text">
                                            <option value="">Seleziona un utente...</option>
                                            <?php
                                            $users = get_users();
                                            foreach ($users as $user) {
                                                echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . ' (' . $user->user_login . ')</option>';
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr id="specific_role_row" style="display: none;">
                                    <th><label for="specific_role">Seleziona Ruolo</label></th>
                                    <td>
                                        <select id="specific_role" name="specific_role" class="regular-text">
                                            <option value="">Seleziona un ruolo...</option>
                                            <?php
                                            $roles = wp_roles()->roles;
                                            foreach ($roles as $role_key => $role) {
                                                echo '<option value="' . $role_key . '">' . esc_html($role['name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="upload-right">
                            <div class="docmanager-upload-area">
                                <input type="file" id="doc_file" name="doc_file" required>
                                <div class="upload-drop-zone">
                                    <span class="upload-icon">📁</span>
                                    <p class="upload-text">Trascina qui il file o clicca per sfogliare</p>
                                    <p class="upload-hint">Massimo 50MB. Formati supportati: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <p class="submit">
                        <input type="submit" name="upload_document" class="button-primary" value="Carica Documento">
                    </p>
                </form>
            </div>
            
            <!-- Documents List -->
            <div class="docmanager-documents-list">
                <div class="docmanager-list-header">
                    <h2>Documenti Caricati</h2>
                    <div class="docmanager-filter-bar">
                        <input type="text" id="document-search" placeholder="Cerca documenti..." class="search-input">
                        <select id="category-filter" class="filter-select">
                            <option value="">Tutte le categorie</option>
                            <?php foreach ($this->get_existing_categories() as $category): ?>
                                <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html($category); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="sort-documents" class="filter-select">
                            <option value="date_desc">Data (più recente)</option>
                            <option value="date_asc">Data (più vecchia)</option>
                            <option value="title_asc">Titolo (A-Z)</option>
                            <option value="title_desc">Titolo (Z-A)</option>
                            <option value="size_desc">Dimensione (maggiore)</option>
                            <option value="size_asc">Dimensione (minore)</option>
                        </select>
                    </div>
                </div>
                
                <form id="bulk-action-form" method="post">
                    <?php wp_nonce_field('docmanager_bulk', 'bulk_nonce'); ?>
                    
                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <select name="bulk_action" id="bulk-action-selector-top">
                                <option value="-1">Azioni in blocco</option>
                                <option value="delete">Elimina</option>
                                <option value="assign_category">Assegna categoria</option>
                                <option value="change_permissions">Cambia permessi</option>
                            </select>
                            <input type="submit" id="doaction" class="button action" value="Applica">
                        </div>
                        <div class="alignright">
                            <span class="displaying-num"><?php echo count($documents); ?> elementi</span>
                        </div>
                    </div>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column">
                                    <input id="cb-select-all-1" type="checkbox">
                                </td>
                                <th class="column-header sortable" data-column="title">
                                    Titolo
                                    <span class="sorting-indicator"></span>
                                </th>
                                <th class="column-header sortable" data-column="category">
                                    Categoria
                                    <span class="sorting-indicator"></span>
                                </th>
                                <th class="column-header">Assegnato a</th>
                                <th class="column-header sortable" data-column="file_type">
                                    Tipo
                                    <span class="sorting-indicator"></span>
                                </th>
                                <th class="column-header sortable" data-column="file_size">
                                    Dimensione
                                    <span class="sorting-indicator"></span>
                                </th>
                                <th class="column-header sortable" data-column="upload_date">
                                    Data Upload
                                    <span class="sorting-indicator"></span>
                                </th>
                                <th class="column-header">Utente</th>
                                <th class="column-header">Download</th>
                                <th class="column-header">Azioni</th>
                            </tr>
                        </thead>
                        <tbody id="documents-table-body">
                            <?php foreach ($documents as $doc): ?>
                            <tr data-doc-id="<?php echo $doc->id; ?>">
                                <th class="check-column">
                                    <input type="checkbox" name="document[]" value="<?php echo $doc->id; ?>">
                                </th>
                                <td class="title column-title" data-column="title">
                                    <strong><?php echo esc_html($doc->title); ?></strong>
                                    <?php if (!empty($doc->description)): ?>
                                        <p class="description"><?php echo esc_html(wp_trim_words($doc->description, 15)); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td data-column="category">
                                    <?php if (!empty($doc->category)): ?>
                                        <span class="category-tag"><?php echo esc_html($doc->category); ?></span>
                                    <?php else: ?>
                                        <span class="no-category">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $this->get_document_assignments($doc->id); ?>
                                </td>
                                <td data-column="file_type">
                                    <span class="file-type"><?php echo $this->get_file_type_icon($doc->file_type); ?> <?php echo $this->get_file_type_label($doc->file_type); ?></span>
                                </td>
                                <td data-column="file_size">
                                    <?php echo $this->format_file_size($doc->file_size); ?>
                                </td>
                                <td data-column="upload_date">
                                    <?php echo date_i18n('d/m/Y H:i', strtotime($doc->upload_date)); ?>
                                </td>
                                <td>
                                    <?php 
                                    $user = get_userdata($doc->uploaded_by);
                                    echo $user ? esc_html($user->display_name) : 'Utente eliminato';
                                    ?>
                                </td>
                                <td>
                                    <span class="download-count"><?php echo $this->get_download_count($doc->id); ?></span>
                                </td>
                                <td>
                                    <div class="docmanager-action-buttons">
                                        <a href="<?php echo esc_url($doc->file_path); ?>" 
                                           class="docmanager-action-btn view" 
                                           target="_blank" 
                                           title="Visualizza">
                                            👁️
                                        </a>
                                        <a href="<?php echo esc_url($doc->file_path); ?>" 
                                           class="docmanager-action-btn download" 
                                           download="<?php echo esc_attr($doc->file_name); ?>"
                                           title="Download">
                                            📥
                                        </a>
                                        <button class="docmanager-action-btn edit" 
                                                data-doc-id="<?php echo $doc->id; ?>"
                                                title="Modifica Permessi"
                                                onclick="openPermissionsModal(<?php echo $doc->id; ?>)">
                                            ✏️
                                        </button>
                                        <button class="docmanager-action-btn delete" 
                                                data-doc-id="<?php echo $doc->id; ?>"
                                                title="Elimina"
                                                onclick="deleteDocument(<?php echo $doc->id; ?>)">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="tablenav bottom">
                        <div class="alignleft actions bulkactions">
                            <select name="bulk_action2" id="bulk-action-selector-bottom">
                                <option value="-1">Azioni in blocco</option>
                                <option value="delete">Elimina</option>
                                <option value="assign_category">Assegna categoria</option>
                                <option value="change_permissions">Cambia permessi</option>
                            </select>
                            <input type="submit" id="doaction2" class="button action" value="Applica">
                        </div>
                        <div class="alignright">
                            <td class="manage-column column-cb check-column">
                                <input id="cb-select-all-2" type="checkbox">
                            </td>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Modal Permessi -->
        <div id="permissions-modal" class="docmanager-modal-overlay" style="display: none;">
            <div class="docmanager-modal">
                <div class="docmanager-modal-header">
                    <h3>Gestisci Permessi Documento</h3>
                    <button class="modal-close" onclick="closePermissionsModal()">&times;</button>
                </div>
                <div class="docmanager-modal-body">
                    <form id="permissions-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="permission-visibility">Visibilità</label></th>
                                <td>
                                    <select id="permission-visibility" name="visibility">
                                        <option value="uploader_only">Solo chi ha caricato</option>
                                        <option value="logged_users">Tutti gli utenti loggati</option>
                                        <option value="everyone">Tutti (pubblico)</option>
                                        <option value="specific_user">Utente specifico</option>
                                        <option value="specific_role">Ruolo specifico</option>
                                    </select>
                                </td>
                            </tr>
                            <tr id="permission-user-row" style="display: none;">
                                <th><label for="permission-user">Utente</label></th>
                                <td>
                                    <select id="permission-user" name="user_id">
                                        <option value="">Seleziona utente...</option>
                                        <?php
                                        foreach ($users as $user) {
                                            echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . ' (' . $user->user_login . ')</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr id="permission-role-row" style="display: none;">
                                <th><label for="permission-role">Ruolo</label></th>
                                <td>
                                    <select id="permission-role" name="role">
                                        <option value="">Seleziona ruolo...</option>
                                        <?php
                                        foreach ($roles as $role_key => $role) {
                                            echo '<option value="' . $role_key . '">' . esc_html($role['name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button-primary">Salva Permessi</button>
                            <button type="button" class="button" onclick="closePermissionsModal()">Annulla</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Gestione visibilità campi upload
            $('#document_visibility').on('change', function() {
                const value = $(this).val();
                $('#specific_user_row, #specific_role_row').hide();
                
                if (value === 'specific_user') {
                    $('#specific_user_row').show();
                } else if (value === 'specific_role') {
                    $('#specific_role_row').show();
                }
            });
            
            // Gestione visibilità campi modal permessi
            $('#permission-visibility').on('change', function() {
                const value = $(this).val();
                $('#permission-user-row, #permission-role-row').hide();
                
                if (value === 'specific_user') {
                    $('#permission-user-row').show();
                } else if (value === 'specific_role') {
                    $('#permission-role-row').show();
                }
            });
            
            // Form permessi
            $('#permissions-form').on('submit', function(e) {
                e.preventDefault();
                
                const docId = $(this).data('doc-id');
                const formData = new FormData(this);
                formData.append('action', 'docmanager_update_permissions');
                formData.append('document_id', docId);
                formData.append('nonce', '<?php echo wp_create_nonce("docmanager_permissions"); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Errore: ' + response.message);
                        }
                    }
                });
            });
        });
        
        function openPermissionsModal(docId) {
            $('#permissions-form').data('doc-id', docId);
            $('#permissions-modal').show();
        }
        
        function closePermissionsModal() {
            $('#permissions-modal').hide();
        }
        
        function deleteDocument(docId) {
            if (!confirm('Sei sicuro di voler eliminare questo documento?')) {
                return;
            }
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'docmanager_delete_document',
                    document_id: docId,
                    nonce: '<?php echo wp_create_nonce("docmanager_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Errore: ' + response.message);
                    }
                }
            });
        }
        </script>
        
        <style>
        .docmanager-upload-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .upload-drop-zone {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            background: #fafafa;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-drop-zone:hover {
            border-color: #0073aa;
            background: #f0f8ff;
        }
        
        .upload-icon {
            font-size: 48px;
            display: block;
            margin-bottom: 15px;
        }
        
        .upload-text {
            font-size: 16px;
            color: #333;
            margin: 0 0 10px 0;
        }
        
        .upload-hint {
            font-size: 12px;
            color: #666;
            margin: 0;
        }
        
        .docmanager-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .docmanager-modal {
            background: white;
            border-radius: 4px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .docmanager-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #c3c4c7;
            background: #f6f7f7;
        }
        
        .docmanager-modal-body {
            padding: 20px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #646970;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .docmanager-action-buttons {
            display: flex;
            gap: 4px;
        }
        
        .docmanager-action-btn {
            padding: 4px 6px;
            border: none;
            background: #f0f0f0;
            cursor: pointer;
            border-radius: 3px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
            position: relative;
        }
        
        .docmanager-action-btn:hover {
            background: #e0e0e0;
        }
        
        .docmanager-action-btn:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
            margin-bottom: 5px;
        }
        
        .docmanager-action-btn.delete:hover {
            background: #d63638;
            color: white;
        }
        
        .assignment-badge {
            display: inline-block;
            background: #e7f3ff;
            color: #0073aa;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 11px;
            margin: 1px;
        }
        
        .assignment-badge.user {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .assignment-badge.role {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .assignment-badge.public {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        </style>
        <?php
    }
	
	private function get_document_assignments($document_id) {
        global $wpdb;
        $permissions_table = $wpdb->prefix . 'docmanager_permissions';
        
        $permissions = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name as user_name 
             FROM {$permissions_table} p
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
             WHERE p.document_id = %d",
            $document_id
        ));
        
        if (empty($permissions)) {
            return '<span class="assignment-badge">Solo uploader</span>';
        }
        
        $output = '';
        foreach ($permissions as $perm) {
            if ($perm->user_id) {
                $output .= '<span class="assignment-badge user">👤 ' . esc_html($perm->user_name) . '</span>';
            } elseif ($perm->user_role) {
                $output .= '<span class="assignment-badge role">👥 ' . esc_html($perm->user_role) . '</span>';
            } else {
                $output .= '<span class="assignment-badge public">🌐 Pubblico</span>';
            }
        }
        
        return $output ?: '<span class="assignment-badge">Solo uploader</span>';
    }
    
    public function settings_page() {
        if (isset($_POST['save_settings'])) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>Impostazioni salvate con successo!</p></div>';
        }
        
        $settings = $this->get_plugin_settings();
        ?>
        <div class="wrap">
            <h1>Impostazioni Document Manager</h1>
            
            <form method="post" class="docmanager-settings-form">
                <?php wp_nonce_field('docmanager_settings', 'docmanager_nonce'); ?>
                
                <div class="docmanager-settings-section">
                    <div class="docmanager-settings-header">
                        <h3>Impostazioni Generali</h3>
                    </div>
                    <div class="docmanager-settings-body">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Dimensione Massima File (MB)</th>
                                <td>
                                    <input type="number" name="max_file_size" value="<?php echo esc_attr($settings['max_file_size']); ?>" min="1" max="100" class="small-text">
                                    <p class="description">Dimensione massima per i file caricati (in megabytes)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Tipi di File Consentiti</th>
                                <td>
                                    <input type="text" name="allowed_file_types" value="<?php echo esc_attr($settings['allowed_file_types']); ?>" class="large-text">
                                    <p class="description">Estensioni separate da virgola (es: pdf,doc,docx,jpg,png)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Abilita Logs</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_logs" value="1" <?php checked($settings['enable_logs']); ?>>
                                        Registra le azioni sui documenti (download, visualizzazioni, ecc.)
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Pulizia Automatica</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="auto_cleanup" value="1" <?php checked($settings['auto_cleanup']); ?>>
                                        Elimina automaticamente i log più vecchi di 90 giorni
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="docmanager-settings-section">
                    <div class="docmanager-settings-header">
                        <h3>Controllo Accessi</h3>
                    </div>
                    <div class="docmanager-settings-body">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Blocca Accesso Dashboard</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="block_admin_access" value="1" <?php checked($settings['block_admin_access']); ?>>
                                        Blocca l'accesso a /wp-admin per ruoli specifici
                                    </label>
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
                                                $checked = in_array($role_key, $settings['blocked_roles']) ? 'checked' : '';
                                                echo '<label style="display: block; margin-bottom: 8px;">
                                                    <input type="checkbox" name="blocked_roles[]" value="' . $role_key . '" ' . $checked . '> 
                                                    ' . $role['name'] . '
                                                </label>';
                                            }
                                        }
                                        ?>
                                    </fieldset>
                                    <p class="description">Seleziona i ruoli che non possono accedere alla dashboard WordPress</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Nascondi Barra Admin</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="hide_admin_bar" value="1" <?php checked($settings['hide_admin_bar']); ?>>
                                        Nascondi la barra amministrativa per ruoli specifici
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Ruoli Senza Barra</th>
                                <td>
                                    <fieldset>
                                        <?php
                                        foreach ($roles as $role_key => $role) {
                                            if ($role_key !== 'administrator') {
                                                $checked = in_array($role_key, $settings['hidden_bar_roles']) ? 'checked' : '';
                                                echo '<label style="display: block; margin-bottom: 8px;">
                                                    <input type="checkbox" name="hidden_bar_roles[]" value="' . $role_key . '" ' . $checked . '> 
                                                    ' . $role['name'] . '
                                                </label>';
                                            }
                                        }
                                        ?>
                                    </fieldset>
                                    <p class="description">Seleziona i ruoli per cui nascondere la barra amministrativa</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="docmanager-settings-section">
                    <div class="docmanager-settings-header">
                        <h3>Impostazioni Email</h3>
                    </div>
                    <div class="docmanager-settings-body">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Notifiche Email</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="email_notifications" value="1" <?php checked($settings['email_notifications']); ?>>
                                        Invia notifiche email per nuovi documenti
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Destinatari Notifiche</th>
                                <td>
                                    <input type="text" name="notification_recipients" value="<?php echo esc_attr($settings['notification_recipients']); ?>" class="large-text">
                                    <p class="description">Email separate da virgola per le notifiche</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="docmanager-settings-section">
                    <div class="docmanager-settings-header">
                        <h3>Impostazioni Avanzate</h3>
                    </div>
                    <div class="docmanager-settings-body">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Cache Documenti</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_cache" value="1" <?php checked($settings['enable_cache']); ?>>
                                        Abilita cache per migliorare le prestazioni
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Debug Mode</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="debug_mode" value="1" <?php checked($settings['debug_mode']); ?>>
                                        Abilita modalità debug (solo per sviluppatori)
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="save_settings" class="button-primary" value="Salva Impostazioni">
                    <button type="button" class="button" onclick="location.reload()">Annulla</button>
                </p>
            </form>
        </div>
        <?php
    }
    
    public function permissions_page() {
        $permissions = $this->get_all_permissions();
        ?>
        <div class="wrap">
            <h1>Gestione Permessi</h1>
            
            <div class="docmanager-permissions-section">
                <div class="docmanager-permissions-header">
                    <h3>Permessi Documenti</h3>
                    <button class="button button-primary add-permission-btn">Aggiungi Permesso</button>
                </div>
                
                <div class="docmanager-permissions-list">
                    <?php if (empty($permissions)): ?>
                        <p>Nessun permesso configurato.</p>
                    <?php else: ?>
                        <?php foreach ($permissions as $permission): ?>
                            <div class="permission-item">
                                <div class="permission-info">
                                    <div class="permission-target">
                                        <?php if ($permission->user_id): ?>
                                            👤 <?php echo esc_html($permission->user_name); ?>
                                        <?php elseif ($permission->user_role): ?>
                                            👥 <?php echo esc_html($permission->user_role); ?>
                                        <?php else: ?>
                                            🌐 Pubblico
                                        <?php endif; ?>
                                    </div>
                                    <div class="permission-details">
                                        Documento: <?php echo esc_html($permission->document_title); ?> | 
                                        Permesso: <?php echo esc_html($permission->permission_type); ?> | 
                                        Assegnato: <?php echo date_i18n('d/m/Y', strtotime($permission->granted_date)); ?>
                                    </div>
                                </div>
                                <div class="permission-actions">
                                    <button class="button button-small remove-permission" 
                                            data-permission-id="<?php echo $permission->id; ?>">
                                        Rimuovi
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function stats_page() {
        $stats = $this->get_detailed_stats();
        ?>
        <div class="wrap">
            <h1>Statistiche Document Manager</h1>
            
            <div class="docmanager-stats-dashboard">
                <div class="stats-row">
                    <div class="stat-card">
                        <h3>Documenti</h3>
                        <div class="stat-number"><?php echo $stats['total_documents']; ?></div>
                        <div class="stat-change">+<?php echo $stats['new_this_month']; ?> questo mese</div>
                    </div>
                    <div class="stat-card">
                        <h3>Download</h3>
                        <div class="stat-number"><?php echo $stats['total_downloads']; ?></div>
                        <div class="stat-change">+<?php echo $stats['downloads_this_month']; ?> questo mese</div>
                    </div>
                    <div class="stat-card">
                        <h3>Utenti Attivi</h3>
                        <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                        <div class="stat-change">Ultimi 30 giorni</div>
                    </div>
                    <div class="stat-card">
                        <h3>Spazio Utilizzato</h3>
                        <div class="stat-number"><?php echo $this->format_file_size($stats['total_storage']); ?></div>
                        <div class="stat-change">di <?php echo $this->format_file_size($stats['max_storage']); ?></div>
                    </div>
                </div>
                
                <div class="stats-row">
                    <div class="stat-section">
                        <h3>Documenti per Categoria</h3>
                        <div class="category-stats">
                            <?php foreach ($stats['categories'] as $category => $count): ?>
                                <div class="category-item">
                                    <span class="category-name"><?php echo esc_html($category ?: 'Senza categoria'); ?></span>
                                    <span class="category-count"><?php echo $count; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="stat-section">
                        <h3>Top 10 Download</h3>
                        <div class="top-downloads">
                            <?php foreach ($stats['top_downloads'] as $doc): ?>
                                <div class="download-item">
                                    <span class="doc-title"><?php echo esc_html($doc->title); ?></span>
                                    <span class="download-count"><?php echo $doc->download_count; ?> download</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="stats-row">
                    <div class="stat-section full-width">
                        <h3>Attività Recente</h3>
                        <div class="recent-activity">
                            <?php foreach ($stats['recent_activity'] as $activity): ?>
                                <div class="activity-item">
                                    <span class="activity-icon"><?php echo $this->get_activity_icon($activity->action); ?></span>
                                    <span class="activity-text"><?php echo esc_html($activity->description); ?></span>
                                    <span class="activity-time"><?php echo $this->time_ago($activity->timestamp); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .docmanager-stats-dashboard {
            margin-top: 20px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 5px;
        }
        
        .stat-change {
            font-size: 12px;
            color: #666;
        }
        
        .stat-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-section.full-width {
            grid-column: 1 / -1;
        }
        
        .category-item, .download-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .category-item:last-child, .download-item:last-child {
            border-bottom: none;
        }
        
        .category-count, .download-count {
            font-weight: bold;
            color: #0073aa;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-icon {
            font-size: 16px;
            width: 24px;
            text-align: center;
        }
        
        .activity-text {
            flex: 1;
        }
        
        .activity-time {
            font-size: 12px;
            color: #666;
        }
        </style>
        <?php
    }
    
    // AJAX Methods
    public function ajax_delete_document() {
        if (!wp_verify_nonce($_POST['nonce'], 'docmanager_nonce')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Nonce verification failed')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $document_id = intval($_POST['document_id']);
        $result = $this->delete_document($document_id);
        
        wp_die(json_encode($result));
    }
    
    public function ajax_get_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'docmanager_nonce')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Nonce verification failed')));
        }
        
        $stats = $this->get_dashboard_stats();
        wp_die(json_encode(array('success' => true, 'stats' => $stats)));
    }
    
    public function ajax_auto_save_setting() {
        if (!wp_verify_nonce($_POST['nonce'], 'docmanager_nonce')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Nonce verification failed')));
        }
        
        $setting = sanitize_text_field($_POST['setting']);
        $value = sanitize_text_field($_POST['value']);
        
        $allowed_settings = array(
            'docmanager_max_file_size',
            'docmanager_enable_logs',
            'docmanager_auto_cleanup',
            'docmanager_hide_admin_bar',
            'docmanager_email_notifications',
            'docmanager_enable_cache',
            'docmanager_debug_mode'
        );
        
        if (in_array($setting, $allowed_settings)) {
            update_option($setting, $value);
            wp_die(json_encode(array('success' => true)));
        }
        
        wp_die(json_encode(array('success' => false, 'message' => 'Invalid setting')));
    }
    
    public function ajax_bulk_action() {
        if (!wp_verify_nonce($_POST['bulk_nonce'], 'docmanager_bulk')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Nonce verification failed')));
        }
        
        $action = sanitize_text_field($_POST['bulk_action']);
        $document_ids = array_map('intval', $_POST['document_ids']);
        
        $result = $this->process_bulk_action($action, $document_ids);
        wp_die(json_encode($result));
    }
	
	public function ajax_update_permissions() {
        if (!wp_verify_nonce($_POST['nonce'], 'docmanager_permissions')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Nonce verification failed')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $document_id = intval($_POST['document_id']);
        $visibility = sanitize_text_field($_POST['visibility']);
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : null;
        
        // Rimuovi permessi esistenti
        global $wpdb;
        $permissions_table = $wpdb->prefix . 'docmanager_permissions';
        
        $wpdb->delete($permissions_table, array('document_id' => $document_id), array('%d'));
        
        // Aggiungi nuovi permessi
        $current_user_id = get_current_user_id();
        
        switch ($visibility) {
            case 'everyone':
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
                $roles = wp_roles()->roles;
                foreach ($roles as $role_key => $role_data) {
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
                if ($user_id) {
                    $wpdb->insert(
                        $permissions_table,
                        array(
                            'document_id' => $document_id,
                            'user_id' => $user_id,
                            'permission_type' => 'view',
                            'granted_by' => $current_user_id
                        ),
                        array('%d', '%d', '%s', '%d')
                    );
                }
                break;
                
            case 'specific_role':
                if ($role) {
                    $wpdb->insert(
                        $permissions_table,
                        array(
                            'document_id' => $document_id,
                            'user_role' => $role,
                            'permission_type' => 'view',
                            'granted_by' => $current_user_id
                        ),
                        array('%d', '%s', '%s', '%d')
                    );
                }
                break;
        }
    }
    
    // Helper Methods
    private function get_dashboard_stats() {
        global $wpdb;
        
        $documents_table = $wpdb->prefix . 'docmanager_documents';
        $logs_table = $wpdb->prefix . 'docmanager_logs';
        
        $stats = array();
        
        $stats['total_documents'] = $wpdb->get_var("SELECT COUNT(*) FROM {$documents_table} WHERE status = 'active'");
        $stats['total_downloads'] = $wpdb->get_var("SELECT COUNT(*) FROM {$logs_table} WHERE action = 'download'");
        $stats['this_month'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$documents_table} WHERE status = 'active' AND upload_date >= %s",
            date('Y-m-01')
        ));
        $stats['storage_used'] = $wpdb->get_var("SELECT SUM(file_size) FROM {$documents_table} WHERE status = 'active'") ?: 0;
        
        return $stats;
    }
    
    private function get_detailed_stats() {
        global $wpdb;
        
        $documents_table = $wpdb->prefix . 'docmanager_documents';
        $logs_table = $wpdb->prefix . 'docmanager_logs';
        
        $stats = $this->get_dashboard_stats();
        
        // Statistiche aggiuntive
        $stats['new_this_month'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$documents_table} WHERE status = 'active' AND upload_date >= %s",
            date('Y-m-01')
        )) ?: 0;
        
        $stats['downloads_this_month'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$logs_table} WHERE action = 'download' AND timestamp >= %s",
            date('Y-m-01')
        )) ?: 0;
        
        $stats['active_users'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$logs_table} WHERE timestamp >= %s",
            date('Y-m-d', strtotime('-30 days'))
        )) ?: 0;
        
        $stats['max_storage'] = 1024 * 1024 * 1024; // 1GB
        $stats['total_storage'] = $stats['storage_used'];
        
        // Categorie - Fix per l'errore Object to string
        $categories_raw = $wpdb->get_results(
            "SELECT category, COUNT(*) as count FROM {$documents_table} WHERE status = 'active' GROUP BY category ORDER BY count DESC"
        );
        $stats['categories'] = array();
        foreach ($categories_raw as $cat) {
            $stats['categories'][$cat->category ?: 'Senza categoria'] = $cat->count;
        }
        
        // Top downloads
        $stats['top_downloads'] = $wpdb->get_results(
            "SELECT d.title, COUNT(l.id) as download_count 
             FROM {$documents_table} d 
             LEFT JOIN {$logs_table} l ON d.id = l.document_id AND l.action = 'download'
             WHERE d.status = 'active' 
             GROUP BY d.id 
             ORDER BY download_count DESC 
             LIMIT 10"
        ) ?: array();
        
        // Attività recente
        $stats['recent_activity'] = $wpdb->get_results(
            "SELECT l.*, d.title as document_title, u.display_name as user_name,
                    CASE 
                        WHEN l.action = 'upload' THEN CONCAT(u.display_name, ' ha caricato ', d.title)
                        WHEN l.action = 'download' THEN CONCAT(u.display_name, ' ha scaricato ', d.title)
                        WHEN l.action = 'delete' THEN CONCAT(u.display_name, ' ha eliminato ', d.title)
                        WHEN l.action = 'view' THEN CONCAT(u.display_name, ' ha visualizzato ', d.title)
                        ELSE CONCAT(u.display_name, ' - ', l.action, ' - ', d.title)
                    END as description
             FROM {$logs_table} l
             LEFT JOIN {$documents_table} d ON l.document_id = d.id
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
             WHERE l.timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             ORDER BY l.timestamp DESC
             LIMIT 20"
        ) ?: array();
        
        return $stats;
    }
    
    private function get_paginated_documents($page = 1, $per_page = 20) {
        global $wpdb;
        
        $documents_table = $wpdb->prefix . 'docmanager_documents';
        $offset = ($page - 1) * $per_page;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$documents_table} 
             WHERE status = 'active' 
             ORDER BY upload_date DESC 
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
    }
    
    private function get_existing_categories() {
        global $wpdb;
        
        $documents_table = $wpdb->prefix . 'docmanager_documents';
        
        return $wpdb->get_col(
            "SELECT DISTINCT category 
             FROM {$documents_table} 
             WHERE status = 'active' AND category IS NOT NULL AND category != '' 
             ORDER BY category ASC"
        );
    }
    
    private function get_download_count($document_id) {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'docmanager_logs';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$logs_table} WHERE document_id = %d AND action = 'download'",
            $document_id
        ));
    }
    
    private function get_plugin_settings() {
        return array(
            'max_file_size' => get_option('docmanager_max_file_size', '10'),
            'allowed_file_types' => get_option('docmanager_allowed_file_types', 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png'),
            'enable_logs' => get_option('docmanager_enable_logs', 'yes') === 'yes',
            'auto_cleanup' => get_option('docmanager_auto_cleanup', 'no') === 'yes',
            'block_admin_access' => get_option('docmanager_block_admin_access', 'no') === 'yes',
            'blocked_roles' => get_option('docmanager_blocked_roles', array()),
            'hide_admin_bar' => get_option('docmanager_hide_admin_bar', 'no') === 'yes',
            'hidden_bar_roles' => get_option('docmanager_hidden_bar_roles', array()),
            'email_notifications' => get_option('docmanager_email_notifications', 'no') === 'yes',
            'notification_recipients' => get_option('docmanager_notification_recipients', ''),
            'enable_cache' => get_option('docmanager_enable_cache', 'no') === 'yes',
            'debug_mode' => get_option('docmanager_debug_mode', 'no') === 'yes',
        );
    }
    
    private function save_settings() {
        if (!wp_verify_nonce($_POST['docmanager_nonce'], 'docmanager_settings')) {
            return false;
        }
        
        update_option('docmanager_max_file_size', intval($_POST['max_file_size']));
        update_option('docmanager_allowed_file_types', sanitize_text_field($_POST['allowed_file_types']));
        update_option('docmanager_enable_logs', isset($_POST['enable_logs']) ? 'yes' : 'no');
        update_option('docmanager_auto_cleanup', isset($_POST['auto_cleanup']) ? 'yes' : 'no');
        update_option('docmanager_block_admin_access', isset($_POST['block_admin_access']) ? 'yes' : 'no');
        update_option('docmanager_blocked_roles', $_POST['blocked_roles'] ?? array());
        update_option('docmanager_hide_admin_bar', isset($_POST['hide_admin_bar']) ? 'yes' : 'no');
        update_option('docmanager_hidden_bar_roles', $_POST['hidden_bar_roles'] ?? array());
        update_option('docmanager_email_notifications', isset($_POST['email_notifications']) ? 'yes' : 'no');
        update_option('docmanager_notification_recipients', sanitize_text_field($_POST['notification_recipients']));
        update_option('docmanager_enable_cache', isset($_POST['enable_cache']) ? 'yes' : 'no');
        update_option('docmanager_debug_mode', isset($_POST['debug_mode']) ? 'yes' : 'no');
        
        return true;
    }
    
    private function process_upload() {
        if (!wp_verify_nonce($_POST['docmanager_nonce'], 'docmanager_upload')) {
            return false;
        }
        
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $docmanager_dir = $upload_dir['basedir'] . '/docmanager/';
        
        if (!file_exists($docmanager_dir)) {
            wp_mkdir_p($docmanager_dir);
        }
        
        $file = $_FILES['doc_file'];
        $filename = time() . '_' . sanitize_file_name($file['name']);
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
            
            $result = $this->db->insert_document($document_data);
            
            if ($result) {
                $document_id = $this->db->wpdb->insert_id;
                
                // Gestisci permessi
                $this->assign_upload_permissions($document_id, $_POST);
                
                echo '<div class="notice notice-success"><p>Documento caricato con successo!</p></div>';
            }
        }
    }
    
    private function process_bulk_action($action, $document_ids) {
        if (empty($document_ids)) {
            return array('success' => false, 'message' => 'No documents selected');
        }
        
        switch ($action) {
            case 'delete':
                return $this->bulk_delete_documents($document_ids);
            case 'assign_category':
                return $this->bulk_assign_category($document_ids);
            case 'change_permissions':
                return $this->bulk_change_permissions($document_ids);
            default:
                return array('success' => false, 'message' => 'Invalid action');
        }
    }
    
    private function bulk_delete_documents($document_ids) {
        global $wpdb;
        
        $documents_table = $wpdb->prefix . 'docmanager_documents';
        $placeholders = implode(',', array_fill(0, count($document_ids), '%d'));
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$documents_table} SET status = 'deleted' WHERE id IN ({$placeholders})",
            $document_ids
        ));
        
        if ($result !== false) {
            return array('success' => true, 'message' => 'Documents deleted successfully');
        }
        
        return array('success' => false, 'message' => 'Failed to delete documents');
    }
    
    private function delete_document($document_id) {
        global $wpdb;
        
        $documents_table = $wpdb->prefix . 'docmanager_documents';
        
        $result = $wpdb->update(
            $documents_table,
            array('status' => 'deleted'),
            array('id' => $document_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            return array('success' => true, 'message' => 'Document deleted successfully');
        }
        
        return array('success' => false, 'message' => 'Failed to delete document');
    }
    
    private function get_all_permissions() {
        global $wpdb;
        
        $permissions_table = $wpdb->prefix . 'docmanager_permissions';
        $documents_table = $wpdb->prefix . 'docmanager_documents';
        
        return $wpdb->get_results(
            "SELECT p.*, d.title as document_title, u.display_name as user_name
             FROM {$permissions_table} p
             LEFT JOIN {$documents_table} d ON p.document_id = d.id
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
             ORDER BY p.granted_date DESC"
        );
    }
    
    private function get_file_type_icon($file_type) {
        $icons = array(
            'application/pdf' => '📄',
            'application/msword' => '📝',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '📝',
            'application/vnd.ms-excel' => '📊',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '📊',
            'image/jpeg' => '🖼️',
            'image/jpg' => '🖼️',
            'image/png' => '🖼️',
        );
        
        return isset($icons[$file_type]) ? $icons[$file_type] : '📁';
    }
    
    private function get_file_type_label($file_type) {
        $labels = array(
            'application/pdf' => 'PDF',
            'application/msword' => 'DOC',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
            'application/vnd.ms-excel' => 'XLS',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
            'image/jpeg' => 'JPG',
            'image/jpg' => 'JPG',
            'image/png' => 'PNG',
        );
        
        return isset($labels[$file_type]) ? $labels[$file_type] : strtoupper(pathinfo($file_type, PATHINFO_EXTENSION));
    }
    
    private function format_file_size($bytes) {
        if ($bytes === 0) return '0 Bytes';
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
    
    private function get_activity_icon($action) {
        $icons = array(
            'upload' => '📤',
            'download' => '📥',
            'delete' => '🗑️',
            'view' => '👁️',
            'share' => '🔗',
            'edit' => '✏️'
        );
        
        return isset($icons[$action]) ? $icons[$action] : '📋';
    }
    
    private function time_ago($timestamp) {
        $time = time() - strtotime($timestamp);
        
        if ($time < 60) return 'ora';
        if ($time < 3600) return floor($time/60) . ' min fa';
        if ($time < 86400) return floor($time/3600) . ' ore fa';
        return floor($time/86400) . ' giorni fa';
    }
}