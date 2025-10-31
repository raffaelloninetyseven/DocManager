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
            $this->get_dashboard_capability(),
            'docmanager',
            array($this, 'dashboard_page'),
            'dashicons-media-document',
            30
        );
        
        add_submenu_page(
            'docmanager',
            'Dashboard',
            'Dashboard',
            $this->get_dashboard_capability(),
            'docmanager',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'docmanager',
            'Tutti i Documenti',
            'Tutti i Documenti',
            $this->get_dashboard_capability(),
            'docmanager-documents',
            array($this, 'documents_page')
        );
        
        add_submenu_page(
            'docmanager',
            'Carica Documento',
            'Carica Documento',
            $this->get_dashboard_capability(),
            'docmanager-upload',
            array($this, 'upload_page')
        );
        
        add_submenu_page(
            'docmanager',
            'Statistiche',
            'Statistiche',
            $this->get_dashboard_capability(),
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
		
		add_submenu_page(
			null,
			'Riparazione Database',
			'Riparazione Database',
			'manage_options',
			'docmanager-repair',
			array($this, 'repair_page')
		);
    }
    
    public function admin_init() {
        register_setting('docmanager_settings', 'docmanager_max_file_size');
        register_setting('docmanager_settings', 'docmanager_allowed_types');
        register_setting('docmanager_settings', 'docmanager_enable_logs');
        register_setting('docmanager_settings', 'docmanager_auto_cleanup_days');
		register_setting('docmanager_settings', 'docmanager_hide_admin_bar_roles');
		register_setting('docmanager_settings', 'docmanager_login_page');
		register_setting('docmanager_settings', 'docmanager_dashboard_users');
    }
	
	private function get_dashboard_capability() {
		$user = wp_get_current_user();
		$allowed_users = get_option('docmanager_dashboard_users', array());
		
		if (in_array($user->ID, $allowed_users)) {
			return 'read';
		}
		
		return 'manage_options';
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
			$documents = $this->db->search_documents_with_downloads($search);
			$total_documents = count($documents);
			$documents = array_slice($documents, $offset, $per_page);
		} else {
			$documents = $this->db->get_all_documents_with_downloads($per_page, $offset);
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
		echo '<th class="sortable">Download</th>';
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
                echo '<span class="file-type-icon" data-type="' . strtoupper(substr($doc->file_type, 0, 3)) . '"></span>';
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
				echo '<td class="downloads-cell">';
				echo '<span class="download-count">' . ($doc->download_count ?: 0) . '</span>';
				echo '</td>';
				echo '<td class="actions-cell">';
                echo '<div class="action-buttons">';
                echo '<a href="' . esc_url(admin_url('admin.php?page=docmanager-upload&edit=' . $doc->id)) . '" class="action-btn edit" data-tooltip="Modifica">';
				echo '<span class="dashicons dashicons-edit"></span>';
				echo '</a>';
				echo '<button type="button" class="action-btn delete" onclick="deleteDocument(' . $doc->id . ')" data-tooltip="Elimina">';
				echo '<span class="dashicons dashicons-trash"></span>';
				echo '</button>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr class="no-items">';
            echo '<td colspan="8">';
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
		$stats = $this->get_analytics_stats();
		$chart_data = $this->get_analytics_chart_data();
		
		echo '<div class="docmanager-admin-wrap">';
		echo '<div class="docmanager-header">';
		echo '<h1><span class="dashicons dashicons-chart-area"></span> Statistiche e Analytics</h1>';
		echo '<div class="docmanager-header-actions">';
		echo '<button type="button" id="export-stats-btn" class="docmanager-btn secondary">';
		echo '<span class="dashicons dashicons-download"></span> Esporta Statistiche';
		echo '</button>';
		echo '</div>';
		echo '</div>';
		
		// Statistiche generali
		echo '<div class="docmanager-stats-container">';
		echo '<div class="docmanager-stats-grid">';
		
		$analytics_items = array(
			array(
				'title' => 'Download Totali',
				'value' => number_format($stats['total_downloads']),
				'icon' => 'download',
				'trend' => '+' . $stats['downloads_growth'] . '%'
			),
			array(
				'title' => 'Utenti Unici',
				'value' => number_format($stats['unique_users']),
				'icon' => 'admin-users',
				'trend' => $stats['users_active'] . ' attivi'
			),
			array(
				'title' => 'File Più Scaricato',
				'value' => $stats['top_file_downloads'],
				'icon' => 'star-filled',
				'trend' => $stats['top_file_title']
			),
			array(
				'title' => 'Spazio Medio/Utente',
				'value' => $stats['avg_storage_per_user'],
				'icon' => 'backup',
				'trend' => 'Media generale'
			)
		);
		
		foreach ($analytics_items as $item) {
			echo '<div class="docmanager-stat-card">';
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
		
		// Grafici e tabelle
		echo '<div class="docmanager-dashboard-grid">';
		
		// Grafico uploads nel tempo
		echo '<div class="docmanager-widget">';
		echo '<div class="widget-header">';
		echo '<h2><span class="dashicons dashicons-chart-line"></span> Upload nel Tempo</h2>';
		echo '<select id="chart-period">';
		echo '<option value="7">Ultimi 7 giorni</option>';
		echo '<option value="30" selected>Ultimi 30 giorni</option>';
		echo '<option value="90">Ultimi 3 mesi</option>';
		echo '<option value="365">Ultimo anno</option>';
		echo '</select>';
		echo '</div>';
		echo '<div class="widget-content">';
		echo '<canvas id="uploadsChart" width="400" height="200"></canvas>';
		echo '</div>';
		echo '</div>';
		
		// Top Files
		echo '<div class="docmanager-widget">';
		echo '<div class="widget-header">';
		echo '<h2><span class="dashicons dashicons-media-document"></span> File Più Scaricati</h2>';
		echo '</div>';
		echo '<div class="widget-content">';
		
		if ($stats['top_files']) {
			echo '<div class="top-files-list">';
			foreach ($stats['top_files'] as $file) {
				echo '<div class="top-file-item">';
				echo '<div class="file-rank">' . $file['rank'] . '</div>';
				echo '<div class="file-details">';
				echo '<h4>' . esc_html($file['title']) . '</h4>';
				echo '<p>' . $file['downloads'] . ' download • ' . esc_html($file['user_name']) . '</p>';
				echo '</div>';
				echo '<div class="file-type-mini">' . strtoupper($file['file_type']) . '</div>';
				echo '</div>';
			}
			echo '</div>';
		} else {
			echo '<div class="empty-state">';
			echo '<span class="dashicons dashicons-media-document"></span>';
			echo '<p>Nessun download registrato</p>';
			echo '</div>';
		}
		
		echo '</div>';
		echo '</div>';
		
		echo '</div>';
		
		// Seconda riga con più grafici
		echo '<div class="analytics-second-row">';
		
		// Distribuzione tipi file
		echo '<div class="docmanager-widget">';
		echo '<div class="widget-header">';
		echo '<h2><span class="dashicons dashicons-chart-pie"></span> Distribuzione Tipi File</h2>';
		echo '</div>';
		echo '<div class="widget-content">';
		echo '<canvas id="fileTypesChart" width="300" height="300"></canvas>';
		echo '</div>';
		echo '</div>';
		
		// Attività utenti
		echo '<div class="docmanager-widget">';
		echo '<div class="widget-header">';
		echo '<h2><span class="dashicons dashicons-admin-users"></span> Utenti Più Attivi</h2>';
		echo '</div>';
		echo '<div class="widget-content">';
		
		if ($stats['active_users_list']) {
			echo '<div class="active-users-list">';
			foreach ($stats['active_users_list'] as $user) {
				echo '<div class="active-user-item">';
				echo '<div class="user-avatar">' . substr($user['display_name'], 0, 1) . '</div>';
				echo '<div class="user-info">';
				echo '<h4>' . esc_html($user['display_name']) . '</h4>';
				echo '<p>' . $user['document_count'] . ' documenti • ' . $user['total_size'] . '</p>';
				echo '</div>';
				echo '<div class="user-activity">';
				echo '<span class="activity-score">' . $user['activity_score'] . '</span>';
				echo '</div>';
				echo '</div>';
			}
			echo '</div>';
		} else {
			echo '<div class="empty-state">';
			echo '<span class="dashicons dashicons-admin-users"></span>';
			echo '<p>Nessuna attività registrata</p>';
			echo '</div>';
		}
		
		echo '</div>';
		echo '</div>';
		
		// Storage per utente
		echo '<div class="docmanager-widget">';
		echo '<div class="widget-header">';
		echo '<h2><span class="dashicons dashicons-cloud"></span> Utilizzo Storage</h2>';
		echo '</div>';
		echo '<div class="widget-content">';
		echo '<canvas id="storageChart" width="400" height="200"></canvas>';
		echo '</div>';
		echo '</div>';
		
		echo '</div>';
		
		echo '</div>';
		
		// JavaScript per i grafici
		echo '<script>
		document.addEventListener("DOMContentLoaded", function() {
			// Grafico uploads nel tempo
			const uploadsCtx = document.getElementById("uploadsChart");
			if (uploadsCtx) {
				new Chart(uploadsCtx, {
					type: "line",
					data: {
						labels: ' . json_encode($chart_data['uploads']['labels']) . ',
						datasets: [{
							label: "Documenti Caricati",
							data: ' . json_encode($chart_data['uploads']['data']) . ',
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
						plugins: { legend: { display: false } },
						scales: { y: { beginAtZero: true } }
					}
				});
			}
			
			// Grafico tipi file
			const fileTypesCtx = document.getElementById("fileTypesChart");
			if (fileTypesCtx) {
				new Chart(fileTypesCtx, {
					type: "doughnut",
					data: {
						labels: ' . json_encode($chart_data['file_types']['labels']) . ',
						datasets: [{
							data: ' . json_encode($chart_data['file_types']['data']) . ',
							backgroundColor: [
								"#0073aa", "#000000", "#666666", "#999999", "#cccccc"
							]
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							legend: { position: "bottom" }
						}
					}
				});
			}
			
			// Grafico storage
			const storageCtx = document.getElementById("storageChart");
			if (storageCtx) {
				new Chart(storageCtx, {
					type: "bar",
					data: {
						labels: ' . json_encode($chart_data['storage']['labels']) . ',
						datasets: [{
							label: "Storage (MB)",
							data: ' . json_encode($chart_data['storage']['data']) . ',
							backgroundColor: "#0073aa"
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: { legend: { display: false } },
						scales: { y: { beginAtZero: true } }
					}
				});
			}
			
			// Export statistiche
			document.getElementById("export-stats-btn").addEventListener("click", function() {
				const stats = ' . json_encode($stats) . ';
				const csv = "Statistica,Valore\\n" + 
					"Documenti Totali," + stats.total_documents + "\\n" +
					"Download Totali," + stats.total_downloads + "\\n" +
					"Utenti Attivi," + stats.users_active + "\\n" +
					"Spazio Utilizzato," + stats.total_storage + "\\n";
				
				const blob = new Blob([csv], { type: "text/csv" });
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement("a");
				a.setAttribute("hidden", "");
				a.setAttribute("href", url);
				a.setAttribute("download", "docmanager-stats.csv");
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
			});
			
			// Aggiorna grafico per periodo
			document.getElementById("chart-period").addEventListener("change", function() {
				// Ricarica dati per nuovo periodo (implementare AJAX se necessario)
				console.log("Periodo cambiato:", this.value);
			});
		});
		</script>';
	}
	
	private function get_analytics_stats() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'docmanager_documents';
		$logs_table = $wpdb->prefix . 'docmanager_logs';
		
		// Statistiche base
		$total_documents = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'");
		$total_storage = $wpdb->get_var("SELECT SUM(file_size) FROM $table_name WHERE status = 'active'");
		$unique_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE status = 'active'");
		
		// Download reali (se tabella log esiste)
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$logs_table'") == $logs_table;
		
		if ($table_exists) {
			$total_downloads = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table WHERE action = 'download'");
			$monthly_downloads = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table WHERE action = 'download' AND download_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
			$last_month_downloads = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table WHERE action = 'download' AND download_date BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)");
			
			$downloads_growth = $last_month_downloads > 0 ? round((($monthly_downloads - $last_month_downloads) / $last_month_downloads) * 100) : 0;
			
			// File più scaricato reale
			$top_file_data = $wpdb->get_row("
				SELECT d.title, COUNT(l.id) as downloads
				FROM $logs_table l
				JOIN $table_name d ON l.document_id = d.id
				WHERE l.action = 'download' AND d.status = 'active'
				GROUP BY l.document_id
				ORDER BY downloads DESC
				LIMIT 1
			");
			
			$top_file_title = $top_file_data ? wp_trim_words($top_file_data->title, 3) : 'N/A';
			$top_file_downloads = $top_file_data ? $top_file_data->downloads : 0;
			
			// Top 5 files reali
			$top_files_query = $wpdb->get_results("
				SELECT d.title, d.file_type, d.user_id, u.display_name as user_name, COUNT(l.id) as downloads
				FROM $logs_table l
				JOIN $table_name d ON l.document_id = d.id
				LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID
				WHERE l.action = 'download' AND d.status = 'active'
				GROUP BY l.document_id
				ORDER BY downloads DESC
				LIMIT 5
			");
			
		} else {
			// Fallback se non ci sono log
			$total_downloads = $total_documents * 3;
			$downloads_growth = 15;
			$top_file_title = 'N/A';
			$top_file_downloads = 0;
			
			$top_files_query = $wpdb->get_results("
				SELECT d.title, d.file_type, d.user_id, u.display_name as user_name
				FROM $table_name d
				LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID
				WHERE d.status = 'active'
				ORDER BY d.upload_date DESC
				LIMIT 5
			");
		}
		
		// Storage medio per utente
		$avg_storage = $unique_users > 0 ? $total_storage / $unique_users : 0;
		
		// Prepara top files
		$top_files = array();
		$rank = 1;
		foreach ($top_files_query as $file) {
			$top_files[] = array(
				'rank' => $rank++,
				'title' => $file->title,
				'file_type' => $file->file_type,
				'user_name' => $file->user_name ?: 'N/A',
				'downloads' => isset($file->downloads) ? $file->downloads : 0
			);
		}
		
		// Utenti più attivi (rimane uguale)
		$active_users_query = $wpdb->get_results("
			SELECT u.display_name, u.ID, COUNT(d.id) as document_count, SUM(d.file_size) as total_size
			FROM {$wpdb->users} u
			INNER JOIN $table_name d ON u.ID = d.user_id
			WHERE d.status = 'active'
			GROUP BY u.ID
			ORDER BY document_count DESC
			LIMIT 5
		");
		
		$active_users_list = array();
		foreach ($active_users_query as $user) {
			$active_users_list[] = array(
				'display_name' => $user->display_name,
				'document_count' => $user->document_count,
				'total_size' => DocManager::format_file_size($user->total_size),
				'activity_score' => $user->document_count * 10
			);
		}
		
		return array(
			'total_documents' => $total_documents ?: 0,
			'total_downloads' => $total_downloads ?: 0,
			'downloads_growth' => $downloads_growth,
			'unique_users' => $unique_users ?: 0,
			'users_active' => min($unique_users, 5),
			'top_file_downloads' => $top_file_downloads,
			'top_file_title' => $top_file_title,
			'avg_storage_per_user' => DocManager::format_file_size($avg_storage),
			'total_storage' => DocManager::format_file_size($total_storage ?: 0),
			'top_files' => $top_files,
			'active_users_list' => $active_users_list
		);
	}

	private function get_analytics_chart_data() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'docmanager_documents';
		$logs_table = $wpdb->prefix . 'docmanager_logs';
		
		// Controlla se tabella log esiste
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$logs_table'") == $logs_table;
		
		// Dati uploads ultimi 30 giorni
		$uploads_data = $wpdb->get_results("
			SELECT DATE(upload_date) as date, COUNT(*) as count
			FROM $table_name
			WHERE status = 'active' AND upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			GROUP BY DATE(upload_date)
			ORDER BY date
		");
		
		$uploads_labels = array();
		$uploads_values = array();
		
		// Riempi ultimi 30 giorni
		for ($i = 29; $i >= 0; $i--) {
			$date = date('Y-m-d', strtotime("-$i days"));
			$uploads_labels[] = date('d/m', strtotime($date));
			
			$count = 0;
			foreach ($uploads_data as $upload) {
				if ($upload->date === $date) {
					$count = $upload->count;
					break;
				}
			}
			$uploads_values[] = $count;
		}
		
		// Distribuzione tipi file
		$file_types_data = $wpdb->get_results("
			SELECT file_type, COUNT(*) as count
			FROM $table_name
			WHERE status = 'active'
			GROUP BY file_type
			ORDER BY count DESC
			LIMIT 5
		");
		
		$file_types_labels = array();
		$file_types_values = array();
		
		foreach ($file_types_data as $type) {
			$file_types_labels[] = strtoupper($type->file_type);
			$file_types_values[] = $type->count;
		}
		
		// Storage per utente (top 10)
		$storage_data = $wpdb->get_results("
			SELECT u.display_name, SUM(d.file_size) as total_size
			FROM {$wpdb->users} u
			INNER JOIN $table_name d ON u.ID = d.user_id
			WHERE d.status = 'active'
			GROUP BY u.ID
			ORDER BY total_size DESC
			LIMIT 10
		");
		
		$storage_labels = array();
		$storage_values = array();
		
		foreach ($storage_data as $user) {
			$storage_labels[] = wp_trim_words($user->display_name, 2, '');
			$storage_values[] = round($user->total_size / 1048576, 1); // Convert to MB
		}
		
		return array(
			'uploads' => array(
				'labels' => $uploads_labels,
				'data' => $uploads_values
			),
			'file_types' => array(
				'labels' => $file_types_labels,
				'data' => $file_types_values
			),
			'storage' => array(
				'labels' => $storage_labels,
				'data' => $storage_values
			)
		);
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
			
			$dashboard_users = isset($_POST['docmanager_dashboard_users']) ? 
				array_map('intval', $_POST['docmanager_dashboard_users']) : array();
			update_option('docmanager_dashboard_users', $dashboard_users);
            
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
	
	echo '<div class="setting-field full-width">';
	echo '<label for="dashboard-user-search">Utenti con Accesso Dashboard</label>';
	echo '<div class="user-search-container">';
	echo '<input type="text" id="dashboard-user-search" placeholder="Cerca utente..." autocomplete="off">';
	echo '<div id="user-search-results" class="user-search-results" style="display:none;"></div>';
	echo '</div>';

	echo '<div id="selected-users-container" class="selected-users-container">';
	$dashboard_users_ids = get_option('docmanager_dashboard_users', array());
	if (!empty($dashboard_users_ids)) {
		$selected_users = get_users(array('include' => $dashboard_users_ids));
		foreach ($selected_users as $user) {
			echo '<div class="selected-user-tag" data-user-id="' . esc_attr($user->ID) . '">';
			echo '<span class="user-name">' . esc_html($user->display_name) . '</span>';
			echo '<span class="user-email">(' . esc_html($user->user_email) . ')</span>';
			echo '<button type="button" class="remove-user" onclick="removeUser(' . esc_attr($user->ID) . ')">';
			echo '<span class="dashicons dashicons-no-alt"></span>';
			echo '</button>';
			echo '<input type="hidden" name="docmanager_dashboard_users[]" value="' . esc_attr($user->ID) . '">';
			echo '</div>';
		}
	}
	echo '</div>';
	echo '<p class="field-description">Cerca e seleziona gli utenti</p>';
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
	
	public function repair_page() {
		if (!current_user_can('manage_options')) {
			wp_die('Non hai i permessi per accedere a questa pagina.');
		}
		
		$repair_result = null;
		
		if (isset($_POST['repair_database'])) {
			if (!wp_verify_nonce($_POST['repair_nonce'], 'docmanager_repair')) {
				wp_die('Errore di sicurezza.');
			}
			
			$repair_result = DocManager_Repair::repair_database();
		}
		
		$status = DocManager_Repair::check_table_structure();
		
		echo '<div class="docmanager-admin-wrap">';
		echo '<div class="docmanager-header">';
		echo '<h1><span class="dashicons dashicons-admin-tools"></span> Riparazione Database DocManager</h1>';
		echo '<div class="docmanager-header-actions">';
		echo '<a href="' . admin_url('admin.php?page=docmanager') . '" class="docmanager-btn secondary">';
		echo '<span class="dashicons dashicons-arrow-left-alt"></span> Torna alla Dashboard';
		echo '</a>';
		echo '</div>';
		echo '</div>';
		
		if ($repair_result !== null) {
			if ($repair_result) {
				echo '<div class="docmanager-notice success">';
				echo '<span class="dashicons dashicons-yes-alt"></span>';
				echo 'Database riparato con successo! Tutte le tabelle sono state ricreate.';
				echo '</div>';
				$status = DocManager_Repair::check_table_structure(); // Ricontrolla
			} else {
				echo '<div class="docmanager-notice error">';
				echo '<span class="dashicons dashicons-warning"></span>';
				echo 'Errore durante la riparazione del database. Controlla i permessi.';
				echo '</div>';
			}
		}
		
		echo '<div class="docmanager-form-container">';
		echo '<div class="docmanager-upload-form">';
		
		echo '<div class="form-section">';
		echo '<h2><span class="dashicons dashicons-database"></span> Stato Database</h2>';
		
		echo '<div class="database-status">';
		
		// Status tabella documenti
		echo '<div class="status-item">';
		echo '<h3>Tabella Documenti</h3>';
		if ($status['table_exists']) {
			if (empty($status['missing_columns'])) {
				echo '<span class="status-ok">✅ OK</span>';
			} else {
				echo '<span class="status-error">❌ Colonne mancanti: ' . implode(', ', $status['missing_columns']) . '</span>';
			}
		} else {
			echo '<span class="status-error">❌ Tabella mancante</span>';
		}
		echo '</div>';
		
		// Status tabella log
		echo '<div class="status-item">';
		echo '<h3>Tabella Log</h3>';
		if ($status['logs_table_exists']) {
			if (empty($status['missing_logs_columns'])) {
				echo '<span class="status-ok">✅ OK</span>';
			} else {
				echo '<span class="status-error">❌ Colonne mancanti: ' . implode(', ', $status['missing_logs_columns']) . '</span>';
			}
		} else {
			echo '<span class="status-error">❌ Tabella mancante</span>';
		}
		echo '</div>';
		
		echo '</div>';
		echo '</div>';
		
		if (!$status['is_valid']) {
			echo '<div class="form-section">';
			echo '<h2><span class="dashicons dashicons-admin-tools"></span> Riparazione</h2>';
			echo '<p><strong>Attenzione:</strong> La riparazione eliminerà tutte le tabelle esistenti e le ricreerà. Tutti i dati verranno persi!</p>';
			echo '<form method="post">';
			echo '<div class="form-actions">';
			echo '<button type="submit" name="repair_database" class="docmanager-btn danger large" onclick="return confirm(\'Sei sicuro? Tutti i dati verranno eliminati!\')">';
			echo '<span class="dashicons dashicons-admin-tools"></span> Ripara Database';
			echo '</button>';
			echo '</div>';
			echo wp_nonce_field('docmanager_repair', 'repair_nonce');
			echo '</form>';
			echo '</div>';
		} else {
			echo '<div class="form-section">';
			echo '<div class="all-ok">';
			echo '<span class="dashicons dashicons-yes-alt"></span>';
			echo '<h3>Database in perfetto stato!</h3>';
			echo '<p>Tutte le tabelle e colonne sono presenti e corrette.</p>';
			echo '</div>';
			echo '</div>';
		}
		
		echo '</div>';
		echo '</div>';
		
		echo '</div>';
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
