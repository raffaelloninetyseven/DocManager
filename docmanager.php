<?php
/**
 * Plugin Name: DocManager
 * Description: Plugin per la gestione di referti medici con area riservata
 * Version: 0.4.0
 * Author: SilverStudioDM
 * Requires PHP: 7.4
 * Requires at least: 5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DOCMANAGER_VERSION', '0.4.0');
define('DOCMANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DOCMANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));

class DocManager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        $this->load_dependencies();
        $this->setup_database();
        $this->init_admin();
        $this->init_elementor_widgets();
    }
    
    private function load_dependencies() {
        require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-docmanager-database.php';
        require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-docmanager-admin.php';
        require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-docmanager-ajax.php';
        require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-docmanager-elementor.php';
        require_once DOCMANAGER_PLUGIN_DIR . 'widgets/class-widget-upload.php';
        require_once DOCMANAGER_PLUGIN_DIR . 'widgets/class-widget-manage.php';
        require_once DOCMANAGER_PLUGIN_DIR . 'widgets/class-widget-view.php';
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
        if (did_action('elementor/loaded')) {
            new DocManager_Elementor();
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
        }
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
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        $upload_dir = wp_upload_dir();
        $docmanager_dir = $upload_dir['basedir'] . '/docmanager';
        if (!file_exists($docmanager_dir)) {
            wp_mkdir_p($docmanager_dir);
        }
        
        $htaccess_content = "Options -Indexes\n";
        $htaccess_content .= "<FilesMatch \"\\.(php|php3|php4|php5|phtml)$\">\n";
        $htaccess_content .= "    Require all denied\n";
        $htaccess_content .= "</FilesMatch>\n";
        
        file_put_contents($docmanager_dir . '/.htaccess', $htaccess_content);
        
        add_option('docmanager_version', DOCMANAGER_VERSION);
        add_option('docmanager_max_file_size', 10485760); // 10MB
        add_option('docmanager_allowed_types', 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip');
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