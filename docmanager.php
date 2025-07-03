<?php
/**
 * Plugin Name: Document Manager Pro
 * Description: Sistema di gestione documentale con integrazione Elementor e area riservata
 * Version: 0.1.2
 * Author: SilverStudioDM
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DOCMANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DOCMANAGER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DOCMANAGER_VERSION', '0.1.2');

class DocManagerPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        $files = array(
            'includes/class-docmanager-db.php',
            'includes/class-docmanager-admin.php',
            'includes/class-docmanager-frontend.php',
            'includes/class-docmanager-permissions.php', // Nome corretto
            'includes/class-docmanager-elementor.php',
            'includes/class-docmanager-ajax.php'
        );
        
        foreach ($files as $file) {
            $filepath = DOCMANAGER_PLUGIN_PATH . $file;
            if (file_exists($filepath)) {
                require_once $filepath;
            } else {
                error_log("DocManager: File mancante - {$file}");
            }
        }
    }
    
    private function init_hooks() {
        if (class_exists('DocManager_DB')) {
            new DocManager_DB();
        }
        if (class_exists('DocManager_Admin')) {
            new DocManager_Admin();
        }
        if (class_exists('DocManager_Frontend')) {
            new DocManager_Frontend();
        }
        if (class_exists('DocManager_Permissions')) { // Nome corretto
            new DocManager_Permissions();
        }
        if (class_exists('DocManager_Elementor')) {
            new DocManager_Elementor();
        }
        if (class_exists('DocManager_Ajax')) {
            new DocManager_Ajax();
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('docmanager', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    public function activate() {
        // Crea le directory necessarie
        $includes_dir = DOCMANAGER_PLUGIN_PATH . 'includes/';
        if (!file_exists($includes_dir)) {
            wp_mkdir_p($includes_dir);
            wp_mkdir_p($includes_dir . 'widgets/');
        }
        
        $assets_dir = DOCMANAGER_PLUGIN_PATH . 'assets/';
        if (!file_exists($assets_dir)) {
            wp_mkdir_p($assets_dir);
            wp_mkdir_p($assets_dir . 'css/');
            wp_mkdir_p($assets_dir . 'js/');
        }
        
        $this->load_dependencies();
        
        // Crea le tabelle
        if (class_exists('DocManager_DB')) {
            $db = new DocManager_DB();
            $db->create_tables();
        } else {
            $this->create_tables_manually();
        }
        
        // Crea ruoli e capabilities
        $this->setup_roles_and_capabilities();
        
        // Opzioni default
        $this->set_default_options();
        
        flush_rewrite_rules();
    }
    
    private function setup_roles_and_capabilities() {
        // Crea ruolo document manager
        add_role('doc_manager', 'Document Manager', array(
            'read' => true,
            'manage_documents' => true,
        ));
        
        // Aggiungi capabilities agli amministratori
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $capabilities = array(
                'view_documents',
                'upload_documents',
                'manage_documents',
                'delete_documents',
                'assign_document_permissions'
            );
            
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
    }
    
    private function set_default_options() {
        add_option('docmanager_version', DOCMANAGER_VERSION);
        add_option('docmanager_db_version', '1.0');
        add_option('docmanager_install_date', current_time('mysql'));
        add_option('docmanager_enable_logs', 'yes');
        add_option('docmanager_max_upload_size', '10');
        add_option('docmanager_allowed_file_types', 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png');
    }
    
    private function create_tables_manually() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql_documents = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}docmanager_documents (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size int(11) NOT NULL,
            file_type varchar(100) NOT NULL,
            category varchar(100),
            tags text,
            uploaded_by int(11) NOT NULL,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY uploaded_by (uploaded_by),
            KEY category (category),
            KEY status (status)
        ) $charset_collate;";
        
        $sql_permissions = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}docmanager_permissions (
            id int(11) NOT NULL AUTO_INCREMENT,
            document_id int(11) NOT NULL,
            user_id int(11) DEFAULT NULL,
            user_role varchar(50) DEFAULT NULL,
            user_group varchar(100) DEFAULT NULL,
            permission_type varchar(20) DEFAULT 'view',
            granted_by int(11) NOT NULL,
            granted_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY user_id (user_id),
            KEY user_role (user_role)
        ) $charset_collate;";
        
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}docmanager_logs (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            document_id int(11) NOT NULL,
            action varchar(50) NOT NULL,
            ip_address varchar(45),
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY document_id (document_id),
            KEY action (action)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_documents);
        dbDelta($sql_permissions);
        dbDelta($sql_logs);
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
}

new DocManagerPlugin();