<?php
/**
 * Plugin Name: DocManager
 * Description: Plugin per la gestione di referti medici con area riservata
 * Version: 0.6.0
 * Author: SilverStudioDM
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DOCMANAGER_VERSION', '0.6.0');
define('DOCMANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DOCMANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));

class DocManager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('wp_ajax_docmanager_search_users', array($this, 'handle_user_search'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        $this->load_dependencies();
        $this->setup_database();
        $this->init_admin();
        $this->init_elementor_widgets();
		
		new DocManager_Admin_Bar();
		new DocManager_Page_Protection();
    }
    
    private function load_dependencies() {
		require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-docmanager-database.php';
		require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-docmanager-repair.php';
		require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-docmanager-admin.php';
		require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-docmanager-admin-bar.php';
		require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-docmanager-ajax.php';
		require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-docmanager-elementor.php';
		require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-docmanager-page-protection.php';
		require_once DOCMANAGER_PLUGIN_DIR . 'includes/widgets/class-widget-upload.php';
		require_once DOCMANAGER_PLUGIN_DIR . 'includes/widgets/class-widget-manage.php';
		require_once DOCMANAGER_PLUGIN_DIR . 'includes/widgets/class-widget-view.php';
		
		// Controlla stato database all'init
		if (is_admin()) {
			DocManager_Repair::add_admin_notice();
		}
	}
    
    private function setup_database() {
        new DocManager_Database();
    }
    
    private function init_admin() {
        if (is_admin()) {
            new DocManager_Admin();
        }
        new DocManager_Ajax();
    }
    
    private function init_elementor_widgets() {
		add_action('elementor/widgets/widgets_registered', array($this, 'register_elementor_widgets'));
	}
	
	public function register_elementor_widgets() {
		if (class_exists('\Elementor\Widget_Base')) {
			\Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Widget_Upload());
			\Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Widget_Manage());
			\Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Widget_View());
		}
	}
    
    public function enqueue_scripts() {
        wp_enqueue_script('docmanager-frontend', DOCMANAGER_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), DOCMANAGER_VERSION, true);
        wp_enqueue_style('docmanager-frontend', DOCMANAGER_PLUGIN_URL . 'assets/css/frontend.css', array(), DOCMANAGER_VERSION);
        
        wp_localize_script('docmanager-frontend', 'docmanager_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('docmanager_nonce'),
            'strings' => array(
                'upload_success' => __('File caricato con successo', 'docmanager'),
                'upload_error' => __('Errore durante il caricamento', 'docmanager'),
                'delete_confirm' => __('Sei sicuro di voler eliminare questo documento?', 'docmanager')
            )
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
		if (strpos($hook, 'docmanager') !== false) {
			wp_enqueue_script('docmanager-admin', DOCMANAGER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DOCMANAGER_VERSION, true);
			wp_enqueue_style('docmanager-admin', DOCMANAGER_PLUGIN_URL . 'assets/css/admin.css', array(), DOCMANAGER_VERSION);
			wp_localize_script('docmanager-admin', 'docmanagerAdmin', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('docmanager_nonce')
			));
			
			wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
		}
	}
	
	public function handle_user_search() {
		check_ajax_referer('docmanager_nonce', 'nonce');
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Accesso negato');
		}
		
		$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
		
		$args = array(
			'search' => '*' . $search . '*',
			'search_columns' => array('user_login', 'user_email', 'display_name'),
			'role__not_in' => array('administrator'),
			'number' => 10
		);
		
		$users = get_users($args);
		$results = array();
		
		foreach ($users as $user) {
			$results[] = array(
				'id' => $user->ID,
				'name' => $user->display_name,
				'email' => $user->user_email,
				'login' => $user->user_login
			);
		}
		
		wp_send_json_success($results);
	}
    
    public function activate() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'docmanager_documents';
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			file_path varchar(500) NOT NULL,
			file_type varchar(50) NOT NULL,
			file_size int NOT NULL,
			user_id int NOT NULL,
			uploaded_by int NOT NULL,
			upload_date datetime DEFAULT CURRENT_TIMESTAMP,
			notes text,
			status varchar(20) DEFAULT 'active',
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY uploaded_by (uploaded_by)
		) $charset_collate;";
		
		// Tabella log
		$logs_table = $wpdb->prefix . 'docmanager_logs';
		$sql2 = "CREATE TABLE $logs_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			document_id mediumint(9) NOT NULL,
			user_id mediumint(9) NOT NULL,
			action varchar(50) NOT NULL,
			ip_address varchar(45),
			user_agent text,
			download_date datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY document_id (document_id),
			KEY user_id (user_id),
			KEY action (action)
		) $charset_collate;";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		dbDelta($sql2);
	
		// Forza aggiornamento se mancano colonne
		$columns = $wpdb->get_col("DESCRIBE {$table_name}");
		if (!in_array('user_id', $columns)) {
			$wpdb->query("ALTER TABLE {$table_name} ADD COLUMN user_id int NOT NULL AFTER file_size");
		}
		if (!in_array('uploaded_by', $columns)) {
			$wpdb->query("ALTER TABLE {$table_name} ADD COLUMN uploaded_by int NOT NULL AFTER user_id");
		}
		
		// Verifica se la tabella esiste e ha tutte le colonne
		$this->verify_table_structure();
		
		// Crea directory per upload
		$upload_dir = wp_upload_dir();
		$docmanager_dir = $upload_dir['basedir'] . '/docmanager';
		if (!file_exists($docmanager_dir)) {
			wp_mkdir_p($docmanager_dir);
		}
		
		// Proteggi directory
		$htaccess_content = "Options -Indexes\n";
		$htaccess_content .= "<FilesMatch \"\\.(php|php3|php4|php5|phtml)$\">\n";
		$htaccess_content .= "    Require all denied\n";
		$htaccess_content .= "</FilesMatch>\n";
		
		file_put_contents($docmanager_dir . '/.htaccess', $htaccess_content);
		
		// Imposta opzioni default
		add_option('docmanager_version', DOCMANAGER_VERSION);
		add_option('docmanager_max_file_size', 10485760);
		add_option('docmanager_allowed_types', 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip');
		add_option('docmanager_dashboard_users', array());
	}

	private function verify_table_structure() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'docmanager_documents';
		
		// Verifica se la tabella esiste
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
		
		if (!$table_exists) {
			return false;
		}
		
		// Verifica colonne necessarie
		$required_columns = array(
			'id', 'title', 'file_path', 'file_type', 'file_size', 
			'user_id', 'uploaded_by', 'upload_date', 'notes', 'status'
		);
		
		$existing_columns = $wpdb->get_col("DESCRIBE $table_name");
		
		foreach ($required_columns as $column) {
			if (!in_array($column, $existing_columns)) {
				// Aggiungi colonna mancante
				$this->add_missing_column($table_name, $column);
			}
		}
		
		return true;
	}

	private function add_missing_column($table_name, $column) {
		global $wpdb;
		
		$column_definitions = array(
			'user_id' => 'ADD COLUMN user_id int NOT NULL DEFAULT 0',
			'uploaded_by' => 'ADD COLUMN uploaded_by int NOT NULL DEFAULT 0',
			'notes' => 'ADD COLUMN notes text',
			'status' => 'ADD COLUMN status varchar(20) DEFAULT "active"'
		);
		
		if (isset($column_definitions[$column])) {
			$wpdb->query("ALTER TABLE $table_name " . $column_definitions[$column]);
		}
	}
    
    public function deactivate() {
        // Cleanup se necessario
    }
    
    public static function get_allowed_file_types() {
        $allowed_types = get_option('docmanager_allowed_types', 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip');
        return explode(',', $allowed_types);
    }
    
    public static function get_max_file_size() {
        return get_option('docmanager_max_file_size', 10485760);
    }
    
    public static function format_file_size($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}

new DocManager();
