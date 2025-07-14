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
        add_action('admin_head', array($this, 'admin_head'));
        add_action('wp_ajax_docmanager_quick_search', array($this, 'handle_quick_search'));
        add_action('wp_ajax_docmanager_bulk_action', array($this, 'handle_bulk_action'));
    }
    
    public function add_admin_menu() {
        $main_page = add_menu_page(
            'DocManager',
            'DocManager',
            'manage_options',
            'docmanager',
            array($this, 'dashboard_page'),
            'dashicons-media-document',
            30
        );
        
        add_submenu_page(
            'docmanager',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'docmanager',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'docmanager',
            'Tutti i Documenti',
            'Tutti i Documenti',
            'manage_options',
            'docmanager-documents',
            array($this, 'documents_page')
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
            'Statistiche',
            'Statistiche',
            'manage_options',
            'docmanager-analytics',
            array($this, 'analytics_page')
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
        register_setting('docmanager_settings', 'docmanager_enable_logs');
        register_setting('docmanager_settings', 'docmanager_auto_cleanup_days');
		register_setting('docmanager_settings', 'docmanager_hide_admin_bar_roles');
		register_setting('docmanager_settings', 'docmanager_login_page');
    }
    
    public function admin_head() {
        $screen = get_current_screen();
        if (strpos($screen->id, 'docmanager') !== false) {
            echo '<style>
                .notice, .error, .updated { display: none !important; }
                .wrap { margin-top: 0; }
            </style>';
        }
    }
    
    public function dashboard_page() {
        $stats = $this->get_dashboard_stats();
        $recent_docs = $this->db->get_recent_documents(8);
        $chart_data = $this->get_chart_data();
        
        echo '<div class="docmanager-admin-wrap">';
        echo '<div class="docmanager-header">';
        echo '<h1><span class="docmanager-logo">📋</span> DocManager Dashboard</h1>';
        echo '<div class="docmanager-header-actions">';
        echo '<a href="' . admin_url('admin.php?page=docmanager-upload') . '" class="docmanager-btn primary">';
        echo '<span class="dashicons dashicons-plus-alt"></span> Nuovo Documento';
        echo '</a>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="docmanager-stats-container">';
        echo '<div class="docmanager-stats-grid">';
        
        $stat_items = array(
            array(
                'title' => 'Documenti Totali',
                'value' => $stats['total_documents'],
                'icon' => 'media-document',
                'color' => 'blue',
                'trend' => '+' . $stats['monthly_growth'] . '%'
            ),
            array(
                'title' => 'Spazio Utilizzato',
                'value' => $stats['total_storage'],
                'icon' => 'cloud',
                'color' => 'green',
                'trend' => $stats['storage_percentage'] . '%'
            ),
            array(
                'title' => 'Questo Mese',
                'value' => $stats['monthly_documents'],
                'icon' => 'calendar-alt',
                'color' => 'orange',
                'trend' => '+' . $stats['monthly_count']
            ),
            array(
                'title' => 'Utenti Attivi',
                'value' => $stats['active_users'],
                'icon' => 'groups',
                'color' => 'purple',
                'trend' => $stats['user_growth'] . '%'
            )
        );
        
        foreach ($stat_items as $item) {
            echo '<div class="docmanager-stat-card ' . $item['color'] . '">';
            echo '<div class="stat-header">';
            echo '<div class="stat-icon"><span class="dashicons dashicons-' . $item['icon'] . '"></span></div>';
            echo '<div class="stat-trend">' . $item['trend'] . '</div>';
            echo '</div>';
            echo '<div class="stat-content">';
            echo '<h3>' . $item['title'] . '</h3>';
            echo '<div class="stat-value">' . $item['value'] . '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
        echo '<div class="docmanager-dashboard-grid">';
        
        echo '<div class="docmanager-widget">';
        echo '<div class="widget-header">';
        echo '<h2><span class="dashicons dashicons-chart-line"></span> Andamento Documenti</h2>';
        echo '</div>';
        echo '<div class="widget-content">';
        echo '<canvas id="documentsChart" width="400" height="200"></canvas>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="docmanager-widget">';
        echo '<div class="widget-header">';
        echo '<h2><span class="dashicons dashicons-clock"></span> Documenti Recenti</h2>';
        echo '<a href="' . admin_url('admin.php?page=docmanager-documents') . '" class="widget-action">Vedi tutti</a>';
        echo '</div>';
        echo '<div class="widget-content">';
        
        if ($recent_docs) {
            echo '<div class="recent-docs-list">';
            foreach ($recent_docs as $doc) {
                echo '<div class="recent-doc-item">';
                echo '<div class="doc-icon">';
                echo '<span class="file-type-badge ' . $doc->file_type . '">' . strtoupper($doc->file_type) . '</span>';
                echo '</div>';
                echo '<div class="doc-info">';
                echo '<h4>' . esc_html($doc->title) . '</h4>';
                echo '<p>' . esc_html($doc->user_name) . ' • ' . human_time_diff(strtotime($doc->upload_date)) . ' fa</p>';
                echo '</div>';
                echo '<div class="doc-size">' . DocManager::format_file_size($doc->file_size) . '</div>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<div class="empty-state">';
            echo '<span class="dashicons dashicons-media-document"></span>';
            echo '<p>Nessun documento trovato</p>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        
        echo '</div>';
        
        $this->render_chart_script($chart_data);
    }
    
    public function documents_page() {
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        if ($search) {
            $documents = $this->db->search_documents($search);
            $total_documents = count($documents);
            $documents = array_slice($documents, $offset, $per_page);
        } else {
            $documents = $this->db->get_all_documents($per_page, $offset);
            $total_documents = $this->db->get_documents_count();
        }
        
        $total_pages = ceil($total_documents / $per_page);
        
        if (isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete' && isset($_POST['document_ids'])) {
            $deleted_count = 0;
            foreach ($_POST['document_ids'] as $doc_id) {
                if ($this->db->delete_document(intval($doc_id))) {
                    $deleted_count++;
                }
            }
            echo '<div class="docmanager-notice success">Eliminati ' . $deleted_count . ' documenti con successo.</div>';
        }
        
        echo '<div class="docmanager-admin-wrap">';
        echo '<div class="docmanager-header">';
        echo '<h1><span class="dashicons dashicons-media-document"></span> Gestione Documenti</h1>';
        echo '<div class="docmanager-header-actions">';
        echo '<a href="' . admin_url('admin.php?page=docmanager-upload') . '" class="docmanager-btn primary">';
        echo '<span class="dashicons dashicons-plus-alt"></span> Nuovo Documento';
        echo '</a>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="docmanager-toolbar">';
        echo '<form method="get" action="" class="docmanager-search-form">';
        echo '<input type="hidden" name="page" value="docmanager-documents">';
        echo '<div class="search-box">';
        echo '<input type="text" name="s" value="' . esc_attr($search) . '" placeholder="Cerca documenti..." class="search-input">';
        echo '<button type="submit" class="search-btn"><span class="dashicons dashicons-search"></span></button>';
        echo '</div>';
        echo '</form>';
        
        echo '<div class="toolbar-actions">';
        echo '<button type="button" id="bulk-delete-btn" class="docmanager-btn danger" disabled>';
        echo '<span class="dashicons dashicons-trash"></span> Elimina Selezionati';
        echo '</button>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="docmanager-table-container">';
        echo '<form id="documents-form" method="post">';
        echo '<table class="docmanager-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th class="check-column"><input type="checkbox" id="select-all-docs"></th>';
        echo '<th class="sortable">Documento</th>';
        echo '<th class="sortable">Utente</th>';
        echo '<th class="sortable">Tipo</th>';
        echo '<th class="sortable">Dimensione</th>';
        echo '<th class="sortable">Data</th>';
        echo '<th>Azioni</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        if ($documents) {
            foreach ($documents as $doc) {
                echo '<tr>';
                echo '<td class="check-column">';
                echo '<input type="checkbox" name="document_ids[]" value="' . $doc->id . '" class="doc-checkbox">';
                echo '</td>';
                echo '<td class="doc-title-cell">';
                echo '<div class="doc-title-container">';
                echo '<span class="file-type-icon ' . $doc->file_type . '"></span>';
                echo '<div class="doc-title-info">';
                echo '<strong>' . esc_html($doc->title) . '</strong>';
                if ($doc->notes) {
                    echo '<div class="doc-notes">' . esc_html(wp_trim_words($doc->notes, 10)) . '</div>';
                }
                echo '</div>';
                echo '</div>';
                echo '</td>';
                echo '<td>';
                echo '<div class="user-info">';
                echo '<span class="user-name">' . esc_html($doc->user_name ?: 'N/A') . '</span>';
                echo '</div>';
                echo '</td>';
                echo '<td><span class="file-type-badge ' . $doc->file_type . '">' . strtoupper($doc->file_type) . '</span></td>';
                echo '<td>' . DocManager::format_file_size($doc->file_size) . '</td>';
                echo '<td>';
                echo '<time datetime="' . $doc->upload_date . '">';
                echo date('d/m/Y', strtotime($doc->upload_date));
                echo '<span class="time-detail">' . date('H:i', strtotime($doc->upload_date)) . '</span>';
                echo '</time>';
                echo '</td>';
                echo '<td class="actions-cell">';
                echo '<div class="action-buttons">';
                echo '<a href="' . esc_url(admin_url('admin.php?page=docmanager-upload&edit=' . $doc->id)) . '" class="action-btn edit" title="Modifica">';
                echo '<span class="dashicons dashicons-edit"></span>';
                echo '</a>';
                echo '<button type="button" class="action-btn delete" onclick="deleteDocument(' . $doc->id . ')" title="Elimina">';
                echo '<span class="dashicons dashicons-trash"></span>';
                echo '</button>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr class="no-items">';
            echo '<td colspan="7">';
            echo '<div class="empty-state">';
            echo '<span class="dashicons dashicons-media-document"></span>';
            echo '<h3>Nessun documento trovato</h3>';
            echo '<p>Non ci sono documenti che corrispondono ai criteri di ricerca.</p>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '<input type="hidden" name="bulk_action" value="">';
        echo wp_nonce_field('docmanager_bulk', 'docmanager_nonce');
        echo '</form>';
        echo '</div>';
        
        if ($total_pages > 1) {
            echo '<div class="docmanager-pagination">';
            echo '<div class="pagination-info">';
            echo 'Pagina ' . $current_page . ' di ' . $total_pages . ' (' . $total_documents . ' documenti totali)';
            echo '</div>';
            echo '<div class="pagination-links">';
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> Precedente',
                'next_text' => 'Successiva <span class="dashicons dashicons-arrow-right-alt2"></span>',
                'total' => $total_pages,
                'current' => $current_page,
                'show_all' => false,
                'end_size' => 1,
                'mid_size' => 2
            ));
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        $this->render_bulk_actions_script();
    }
    
    public function upload_page() {
        $edit_doc = null;
        if (isset($_GET['edit'])) {
            $edit_doc = $this->db->get_document_by_id(intval($_GET['edit']));
        }
        
        if (isset($_POST['submit'])) {
            $result = $this->handle_upload();
            if ($result['success']) {
                echo '<div class="docmanager-notice success">' . $result['message'] . '</div>';
            } else {
                echo '<div class="docmanager-notice error">' . $result['message'] . '</div>';
            }
        }
        
        echo '<div class="docmanager-admin-wrap">';
        echo '<div class="docmanager-header">';
        echo '<h1><span class="dashicons dashicons-upload"></span> ' . ($edit_doc ? 'Modifica Documento' : 'Carica Nuovo Documento') . '</h1>';
        echo '<div class="docmanager-header-actions">';
        echo '<a href="' . admin_url('admin.php?page=docmanager-documents') . '" class="docmanager-btn secondary">';
        echo '<span class="dashicons dashicons-arrow-left-alt"></span> Torna alla Lista';
        echo '</a>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="docmanager-form-container">';
        echo '<form method="post" enctype="multipart/form-data" class="docmanager-upload-form">';
        
        echo '<div class="form-section">';
        echo '<h2>Informazioni Documento</h2>';
        echo '<div class="form-grid">';
        echo '<div class="form-field">';
        echo '<label for="doc-title">Titolo Documento *</label>';
        echo '<input type="text" id="doc-title" name="title" value="' . ($edit_doc ? esc_attr($edit_doc->title) : '') . '" required>';
        echo '</div>';
        
        echo '<div class="form-field">';
        echo '<label for="doc-user">Assegna a Utente *</label>';
        wp_dropdown_users(array(
            'name' => 'user_id',
            'id' => 'doc-user',
            'selected' => $edit_doc ? $edit_doc->user_id : '',
            'show_option_none' => 'Seleziona utente...',
            'option_none_value' => '',
            'class' => 'user-select'
        ));
        echo '</div>';
        echo '</div>';
        
        echo '<div class="form-field full-width">';
        echo '<label for="doc-notes">Note</label>';
        echo '<textarea id="doc-notes" name="notes" rows="4">' . ($edit_doc ? esc_textarea($edit_doc->notes) : '') . '</textarea>';
        echo '</div>';
        echo '</div>';
        
        if (!$edit_doc) {
            echo '<div class="form-section">';
            echo '<h2>Caricamento File</h2>';
            echo '<div class="upload-area" id="upload-dropzone">';
            echo '<div class="upload-icon"><span class="dashicons dashicons-cloud-upload"></span></div>';
            echo '<h3>Trascina il file qui o clicca per selezionare</h3>';
            echo '<p>Tipi consentiti: ' . implode(', ', DocManager::get_allowed_file_types()) . '</p>';
            echo '<p>Dimensione massima: ' . DocManager::format_file_size(DocManager::get_max_file_size()) . '</p>';
            echo '<input type="file" id="document_file" name="document_file" accept=".' . implode(',.', DocManager::get_allowed_file_types()) . '" required>';
            echo '<div class="file-info" style="display: none;"></div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '<div class="form-actions">';
        if ($edit_doc) {
            echo '<input type="hidden" name="edit_id" value="' . $edit_doc->id . '">';
            echo '<button type="submit" name="submit" class="docmanager-btn primary large">';
            echo '<span class="dashicons dashicons-update"></span> Aggiorna Documento';
            echo '</button>';
        } else {
            echo '<button type="submit" name="submit" class="docmanager-btn primary large">';
            echo '<span class="dashicons dashicons-upload"></span> Carica Documento';
            echo '</button>';
        }
        echo '</div>';
        
        echo wp_nonce_field('docmanager_upload', 'docmanager_nonce');
        echo '</form>';
        echo '</div>';
        
        echo '</div>';
        
        $this->render_upload_script();
    }
    
    public function analytics_page() {
        echo '<div class="docmanager-admin-wrap">';
        echo '<div class="docmanager-header">';
        echo '<h1><span class="dashicons dashicons-chart-area"></span> Statistiche e Analytics</h1>';
        echo '</div>';
        echo '<div class="analytics-placeholder">';
        echo '<div class="coming-soon">';
        echo '<span class="dashicons dashicons-chart-pie"></span>';
        echo '<h2>Statistiche Avanzate</h2>';
        echo '<p>Funzionalità in arrivo nella prossima versione</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    public function settings_page() {
    // Gestione salvataggio impostazioni
    if (isset($_POST['submit'])) {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['docmanager_nonce'], 'docmanager_settings')) {
            echo '<div class="docmanager-notice error">Errore di sicurezza. Riprova.</div>';
        } else {
            // Salva dimensione massima file (converti da MB a bytes)
            $max_file_size = intval($_POST['docmanager_max_file_size']) * 1048576;
            update_option('docmanager_max_file_size', $max_file_size);
            
            // Salva tipi file consentiti
            $allowed_types = sanitize_text_field($_POST['docmanager_allowed_types']);
            $allowed_types = preg_replace('/[^a-zA-Z0-9,]/', '', $allowed_types); // Solo lettere, numeri e virgole
            update_option('docmanager_allowed_types', $allowed_types);
            
            // Salva ruoli per nascondere barra admin
            $hide_admin_bar_roles = isset($_POST['docmanager_hide_admin_bar_roles']) ? 
                array_map('sanitize_text_field', $_POST['docmanager_hide_admin_bar_roles']) : array();
            update_option('docmanager_hide_admin_bar_roles', $hide_admin_bar_roles);
			
			// Salva pagina login
			$login_page = intval($_POST['docmanager_login_page']);
			update_option('docmanager_login_page', $login_page);
            
            // Salva abilitazione log
            $enable_logs = isset($_POST['docmanager_enable_logs']) ? 1 : 0;
            update_option('docmanager_enable_logs', $enable_logs);
            
            // Salva giorni auto-cleanup
            $auto_cleanup_days = intval($_POST['docmanager_auto_cleanup_days']);
            if ($auto_cleanup_days < 0) $auto_cleanup_days = 0;
            update_option('docmanager_auto_cleanup_days', $auto_cleanup_days);
            
            // Salva pagine protette
            $protected_pages = isset($_POST['docmanager_protected_pages']) ? 
                array_map('intval', $_POST['docmanager_protected_pages']) : array();
            update_option('docmanager_protected_pages', $protected_pages);
            
            echo '<div class="docmanager-notice success">
                <span class="dashicons dashicons-yes-alt"></span>
                Impostazioni salvate con successo.
            </div>';
        }
    }
    
    // Recupera valori correnti
    $max_file_size = get_option('docmanager_max_file_size', 10485760) / 1048576; // Converti in MB
    $allowed_types = get_option('docmanager_allowed_types', 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip');
    $hide_admin_bar_roles = get_option('docmanager_hide_admin_bar_roles', array());
    $enable_logs = get_option('docmanager_enable_logs', 0);
    $auto_cleanup_days = get_option('docmanager_auto_cleanup_days', 0);
    $protected_pages = get_option('docmanager_protected_pages', array());
    
    echo '<div class="docmanager-admin-wrap">';
    echo '<div class="docmanager-header">';
    echo '<h1><span class="dashicons dashicons-admin-settings"></span> Impostazioni DocManager</h1>';
    echo '<div class="docmanager-header-actions">';
    echo '<a href="' . admin_url('admin.php?page=docmanager') . '" class="docmanager-btn secondary">';
    echo '<span class="dashicons dashicons-arrow-left-alt"></span> Torna alla Dashboard';
    echo '</a>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="settings-container">';
    echo '<form method="post" class="docmanager-settings-form">';
    
    // Sezione File
    echo '<div class="settings-section">';
    echo '<h2><span class="dashicons dashicons-media-document"></span> Impostazioni File</h2>';
    echo '<div class="settings-grid">';
    
    echo '<div class="setting-field">';
    echo '<label for="max-file-size">Dimensione Massima File (MB)</label>';
    echo '<input type="number" id="max-file-size" name="docmanager_max_file_size" value="' . esc_attr($max_file_size) . '" min="1" max="100" step="0.1">';
    echo '<p class="field-description">Dimensione massima consentita per i file caricati (da 1 MB a 100 MB)</p>';
    echo '</div>';
    
    echo '<div class="setting-field">';
    echo '<label for="allowed-types">Tipi File Consentiti</label>';
    echo '<input type="text" id="allowed-types" name="docmanager_allowed_types" value="' . esc_attr($allowed_types) . '" placeholder="pdf,doc,docx,jpg,png">';
    echo '<p class="field-description">Estensioni file separate da virgola (es: pdf,doc,jpg). Solo lettere e numeri.</p>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
    
    // Sezione Sicurezza e Accesso
    echo '<div class="settings-section">';
    echo '<h2><span class="dashicons dashicons-shield"></span> Sicurezza e Accesso</h2>';
    
    // Barra Admin
    echo '<div class="setting-field full-width">';
    echo '<label for="hide-admin-bar-roles">Nascondi Barra Admin per Ruoli</label>';
    echo '<div class="roles-checkboxes">';
    
    global $wp_roles;
    foreach ($wp_roles->roles as $role_key => $role) {
        $checked = in_array($role_key, $hide_admin_bar_roles) ? 'checked' : '';
        echo '<label class="role-checkbox">';
        echo '<input type="checkbox" name="docmanager_hide_admin_bar_roles[]" value="' . esc_attr($role_key) . '" ' . $checked . '>';
        echo '<span class="checkmark"></span>';
        echo esc_html($role['name']);
        echo '</label>';
    }
    
    echo '</div>';
    echo '<p class="field-description">Seleziona i ruoli utente per cui nascondere la barra di amministrazione WordPress</p>';
    echo '</div>';
    
    // Pagine Protette
    echo '<div class="setting-field full-width">';
    echo '<label for="protected-pages">Pagine Protette</label>';
    echo '<div class="pages-checkboxes">';
    
    $pages = get_pages(array('post_status' => 'publish'));
    if ($pages) {
        foreach ($pages as $page) {
            $checked = in_array($page->ID, $protected_pages) ? 'checked' : '';
            echo '<label class="page-checkbox">';
            echo '<input type="checkbox" name="docmanager_protected_pages[]" value="' . esc_attr($page->ID) . '" ' . $checked . '>';
            echo '<span class="checkmark"></span>';
            echo esc_html($page->post_title);
            echo '</label>';
        }
    } else {
        echo '<p class="no-pages">Nessuna pagina pubblicata trovata.</p>';
    }
    
    echo '</div>';
    echo '<p class="field-description">Le pagine selezionate richiederanno il login per essere visualizzate</p>';
    echo '</div>';
	
	// Pagina di Login
	echo '<div class="setting-field full-width">';
	echo '<label for="login-page">Pagina di Login</label>';
	wp_dropdown_pages(array(
		'name' => 'docmanager_login_page',
		'id' => 'login-page',
		'selected' => get_option('docmanager_login_page', 0),
		'show_option_none' => 'Login WordPress predefinito',
		'option_none_value' => 0
	));
	echo '<p class="field-description">Seleziona la pagina personalizzata per il login (opzionale)</p>';
	echo '</div>';
    
    echo '</div>';
    
    // Sezione Sistema
    echo '<div class="settings-section">';
    echo '<h2><span class="dashicons dashicons-admin-tools"></span> Impostazioni Sistema</h2>';
    echo '<div class="settings-grid">';
    
    echo '<div class="setting-field">';
	echo '<label for="enable-logs">Abilita Log Accessi</label>';
	echo '<div class="toggle-switch">';
	echo '<input type="checkbox" id="enable-logs" name="docmanager_enable_logs" value="1" ' . checked($enable_logs, 1, false) . '>';
	echo '<div class="toggle-label">';
	echo '<div class="toggle-slider"></div>';
	echo '</div>';
	echo '</div>';
	echo '<p class="field-description">Registra tutti gli accessi e download dei documenti</p>';
	echo '</div>';
    
    echo '<div class="setting-field">';
    echo '<label for="auto-cleanup-days">Auto-Cleanup Documenti (giorni)</label>';
    echo '<input type="number" id="auto-cleanup-days" name="docmanager_auto_cleanup_days" value="' . esc_attr($auto_cleanup_days) . '" min="0" max="365">';
    echo '<p class="field-description">Elimina automaticamente documenti dopo X giorni (0 = disabilitato)</p>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
    
    // Sezione Info Sistema
    echo '<div class="settings-section">';
    echo '<h2><span class="dashicons dashicons-info"></span> Informazioni Sistema</h2>';
    echo '<div class="system-info-grid">';
    
    // Info PHP
    echo '<div class="info-card">';
    echo '<h3>Limiti PHP</h3>';
    echo '<div class="info-row">';
    echo '<span class="info-label">Max Upload Size:</span>';
    echo '<span class="info-value">' . ini_get('upload_max_filesize') . '</span>';
    echo '</div>';
    echo '<div class="info-row">';
    echo '<span class="info-label">Max Post Size:</span>';
    echo '<span class="info-value">' . ini_get('post_max_size') . '</span>';
    echo '</div>';
    echo '<div class="info-row">';
    echo '<span class="info-label">Memory Limit:</span>';
    echo '<span class="info-value">' . ini_get('memory_limit') . '</span>';
    echo '</div>';
    echo '</div>';
    
    // Info DocManager
    echo '<div class="info-card">';
    echo '<h3>DocManager</h3>';
    echo '<div class="info-row">';
    echo '<span class="info-label">Versione:</span>';
    echo '<span class="info-value">' . DOCMANAGER_VERSION . '</span>';
    echo '</div>';
    echo '<div class="info-row">';
    echo '<span class="info-label">Documenti Totali:</span>';
    echo '<span class="info-value">' . $this->get_total_documents_count() . '</span>';
    echo '</div>';
    echo '<div class="info-row">';
    echo '<span class="info-label">Spazio Utilizzato:</span>';
    echo '<span class="info-value">' . $this->get_total_storage_formatted() . '</span>';
    echo '</div>';
    echo '</div>';
    
    // Info Database
    echo '<div class="info-card">';
    echo '<h3>Database</h3>';
    echo '<div class="info-row">';
    echo '<span class="info-label">Tabella:</span>';
    echo '<span class="info-value">' . $GLOBALS['wpdb']->prefix . 'docmanager_documents</span>';
    echo '</div>';
    echo '<div class="info-row">';
    echo '<span class="info-label">Charset:</span>';
    echo '<span class="info-value">' . $GLOBALS['wpdb']->charset . '</span>';
    echo '</div>';
    echo '<div class="info-row">';
    echo '<span class="info-label">Collate:</span>';
    echo '<span class="info-value">' . $GLOBALS['wpdb']->collate . '</span>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
    
    // Pulsanti azione
    echo '<div class="form-actions">';
    echo '<button type="submit" name="submit" class="docmanager-btn primary large">';
    echo '<span class="dashicons dashicons-yes"></span> Salva Impostazioni';
    echo '</button>';
    
    echo '<button type="button" class="docmanager-btn secondary large" onclick="resetToDefaults()">';
    echo '<span class="dashicons dashicons-undo"></span> Ripristina Default';
    echo '</button>';
    
    echo '<a href="' . admin_url('admin.php?page=docmanager&action=export_settings') . '" class="docmanager-btn secondary large">';
    echo '<span class="dashicons dashicons-download"></span> Esporta Impostazioni';
    echo '</a>';
    echo '</div>';
    
    echo wp_nonce_field('docmanager_settings', 'docmanager_nonce');
    echo '</form>';
    echo '</div>';
    
    echo '</div>';
    
    // JavaScript per funzionalità aggiuntive
    echo '<script>
    function resetToDefaults() {
        if (confirm("Sei sicuro di voler ripristinare tutte le impostazioni ai valori predefiniti?")) {
            document.getElementById("max-file-size").value = "10";
            document.getElementById("allowed-types").value = "pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip";
            document.getElementById("enable-logs").checked = false;
            document.getElementById("auto-cleanup-days").value = "0";
            
            // Deseleziona tutti i checkbox
            document.querySelectorAll("input[type=checkbox]").forEach(function(checkbox) {
                if (checkbox.id !== "enable-logs") {
                    checkbox.checked = false;
                }
            });
        }
    }
    
    // Validazione form
    document.querySelector(".docmanager-settings-form").addEventListener("submit", function(e) {
        const maxSize = parseFloat(document.getElementById("max-file-size").value);
        const allowedTypes = document.getElementById("allowed-types").value.trim();
        
        if (maxSize <= 0 || maxSize > 100) {
            alert("La dimensione massima del file deve essere tra 1 e 100 MB");
            e.preventDefault();
            return false;
        }
        
        if (!allowedTypes) {
            alert("Devi specificare almeno un tipo di file consentito");
            e.preventDefault();
            return false;
        }
        
        // Valida formato tipi file
        const typePattern = /^[a-zA-Z0-9]+(,[a-zA-Z0-9]+)*$/;
        if (!typePattern.test(allowedTypes)) {
            alert("I tipi di file devono essere separati da virgola e contenere solo lettere e numeri");
            e.preventDefault();
            return false;
        }
    });
    </script>';
}

// Metodi helper per le info sistema
private function get_total_documents_count() {
    global $wpdb;
    return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}docmanager_documents WHERE status = 'active'") ?: 0;
}

private function get_total_storage_formatted() {
    global $wpdb;
    $total_bytes = $wpdb->get_var("SELECT SUM(file_size) FROM {$wpdb->prefix}docmanager_documents WHERE status = 'active'") ?: 0;
    return DocManager::format_file_size($total_bytes);
}
    
    private function get_dashboard_stats() {
        global $wpdb;
        
        $total_documents = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}docmanager_documents WHERE status = 'active'");
        $total_storage = $wpdb->get_var("SELECT SUM(file_size) FROM {$wpdb->prefix}docmanager_documents WHERE status = 'active'");
        $monthly_documents = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}docmanager_documents WHERE status = 'active' AND MONTH(upload_date) = MONTH(NOW()) AND YEAR(upload_date) = YEAR(NOW())");
        $active_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}docmanager_documents WHERE status = 'active'");
        
        $last_month_docs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}docmanager_documents WHERE status = 'active' AND MONTH(upload_date) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(upload_date) = YEAR(NOW())");
        $monthly_growth = $last_month_docs > 0 ? round((($monthly_documents - $last_month_docs) / $last_month_docs) * 100) : 0;
        
        return array(
            'total_documents' => number_format($total_documents ?: 0),
            'total_storage' => DocManager::format_file_size($total_storage ?: 0),
            'monthly_documents' => number_format($monthly_documents ?: 0),
            'active_users' => number_format($active_users ?: 0),
            'monthly_growth' => $monthly_growth,
            'monthly_count' => $monthly_documents ?: 0,
            'storage_percentage' => 75,
            'user_growth' => 12
        );
    }
    
    private function get_chart_data() {
        global $wpdb;
        
        $data = $wpdb->get_results("
            SELECT DATE_FORMAT(upload_date, '%Y-%m') as month, COUNT(*) as count 
            FROM {$wpdb->prefix}docmanager_documents 
            WHERE status = 'active' AND upload_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY month 
            ORDER BY month
        ");
        
        return $data;
    }
    
    private function handle_upload() {
        if (!wp_verify_nonce($_POST['docmanager_nonce'], 'docmanager_upload')) {
            return array('success' => false, 'message' => 'Nonce non valido');
        }
        
        if (isset($_POST['edit_id'])) {
            $doc_id = intval($_POST['edit_id']);
            $update_data = array(
                'title' => sanitize_text_field($_POST['title']),
                'user_id' => intval($_POST['user_id']),
                'notes' => sanitize_textarea_field($_POST['notes'])
            );
            
            if ($this->db->update_document($doc_id, $update_data)) {
                return array('success' => true, 'message' => 'Documento aggiornato con successo');
            } else {
                return array('success' => false, 'message' => 'Errore nell\'aggiornamento del documento');
            }
        } else {
            $upload_result = $this->process_file_upload();
            
            if ($upload_result['success']) {
                $doc_data = array(
                    'title' => sanitize_text_field($_POST['title']),
                    'file_path' => $upload_result['file_path'],
                    'file_type' => $upload_result['file_type'],
                    'file_size' => $upload_result['file_size'],
                    'user_id' => intval($_POST['user_id']),
                    'uploaded_by' => get_current_user_id(),
                    'notes' => sanitize_textarea_field($_POST['notes'])
                );
                
                if ($this->db->insert_document($doc_data)) {
                    return array('success' => true, 'message' => 'Documento caricato con successo');
                } else {
                    return array('success' => false, 'message' => 'Errore nel salvataggio del documento');
                }
            } else {
                return array('success' => false, 'message' => $upload_result['error']);
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
    
    private function render_chart_script($chart_data) {
        $labels = array();
        $data = array();
        
        foreach ($chart_data as $item) {
            $labels[] = date('M Y', strtotime($item->month . '-01'));
            $data[] = $item->count;
        }
        
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById("documentsChart");
            if (ctx) {
                new Chart(ctx, {
                    type: "line",
                    data: {
                        labels: ' . json_encode($labels) . ',
                        datasets: [{
                            label: "Documenti Caricati",
                            data: ' . json_encode($data) . ',
                            borderColor: "#0073aa",
                            backgroundColor: "rgba(0, 115, 170, 0.1)",
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        });
        </script>';
    }
    
    private function render_bulk_actions_script() {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const selectAll = document.getElementById("select-all-docs");
            const checkboxes = document.querySelectorAll(".doc-checkbox");
            const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
            const documentsForm = document.getElementById("documents-form");
            
            function updateBulkButton() {
                const checkedBoxes = document.querySelectorAll(".doc-checkbox:checked");
                bulkDeleteBtn.disabled = checkedBoxes.length === 0;
                bulkDeleteBtn.textContent = checkedBoxes.length > 0 ? 
                    `Elimina ${checkedBoxes.length} Selezionati` : "Elimina Selezionati";
            }
            
            selectAll.addEventListener("change", function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateBulkButton();
            });
            
            checkboxes.forEach(cb => {
                cb.addEventListener("change", updateBulkButton);
            });
            
            bulkDeleteBtn.addEventListener("click", function() {
                const checkedBoxes = document.querySelectorAll(".doc-checkbox:checked");
                if (checkedBoxes.length > 0 && confirm(`Sei sicuro di voler eliminare ${checkedBoxes.length} documenti?`)) {
                    documentsForm.querySelector("input[name=bulk_action]").value = "delete";
                    documentsForm.submit();
                }
            });
        });
        
        function deleteDocument(docId) {
            if (confirm("Sei sicuro di voler eliminare questo documento?")) {
                const form = document.createElement("form");
                form.method = "post";
                form.innerHTML = `
                    <input type="hidden" name="bulk_action" value="delete">
                    <input type="hidden" name="document_ids[]" value="${docId}">
                    ' . wp_nonce_field('docmanager_bulk', 'docmanager_nonce', true, false) . '
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        </script>';
    }
    
    private function render_upload_script() {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const dropzone = document.getElementById("upload-dropzone");
            const fileInput = document.getElementById("document_file");
            const fileInfo = dropzone.querySelector(".file-info");
            
            function handleFile(file) {
                const allowedTypes = ' . json_encode(DocManager::get_allowed_file_types()) . ';
                const maxSize = ' . DocManager::get_max_file_size() . ';
                const fileExt = file.name.split(".").pop().toLowerCase();
                
                if (!allowedTypes.includes(fileExt)) {
                    alert("Tipo di file non consentito");
                    return false;
                }
                
                if (file.size > maxSize) {
                    alert("File troppo grande");
                    return false;
                }
                
                fileInfo.innerHTML = `
                    <div class="selected-file">
                        <span class="file-icon dashicons dashicons-media-document"></span>
                        <div class="file-details">
                            <strong>${file.name}</strong>
                            <span class="file-size">${formatFileSize(file.size)}</span>
                        </div>
                    </div>
                `;
                fileInfo.style.display = "block";
                dropzone.classList.add("has-file");
                
                return true;
            }
            
            function formatFileSize(bytes) {
                if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + " MB";
                if (bytes >= 1024) return (bytes / 1024).toFixed(2) + " KB";
                return bytes + " bytes";
            }
            
            dropzone.addEventListener("click", () => fileInput.click());
            
            dropzone.addEventListener("dragover", function(e) {
                e.preventDefault();
                this.classList.add("dragover");
            });
            
            dropzone.addEventListener("dragleave", function(e) {
                e.preventDefault();
                this.classList.remove("dragover");
            });
            
            dropzone.addEventListener("drop", function(e) {
                e.preventDefault();
                this.classList.remove("dragover");
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    handleFile(files[0]);
                }
            });
            
            fileInput.addEventListener("change", function() {
                if (this.files.length > 0) {
                    handleFile(this.files[0]);
                }
            });
        });
        </script>';
    }
}