<?php
/**
 * Plugin Name: DocManager
 * Description: Plugin per la gestione documentale con area riservata integrato con Elementor
 * Version: 0.2.5
 * Author: SilverStudioDM
 * Text Domain: docmanager
 * Domain Path: /languages
 */

// Prevenire accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Definire costanti
define('DOCMANAGER_VERSION', '0.2.5');
define('DOCMANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DOCMANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DOCMANAGER_UPLOAD_DIR', WP_CONTENT_DIR . '/docmanager-files/');

class DocManager {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'loadTextDomain'));
        
        // Hook per attivazione/disattivazione
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Caricare le classi principali
        $this->loadClasses();
        
        // Inizializzare componenti
        new DocManager_PostType();
        new DocManager_Admin();
        new DocManager_Security();
        new DocManager_FileHandler();
        
        // Verificare se Elementor è attivo
        if (did_action('elementor/loaded')) {
            new DocManager_Elementor();
        }
    }
    
    private function loadClasses() {
        require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-post-type.php';
        require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-admin.php';
        require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-security.php';
        require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-file-handler.php';
        require_once DOCMANAGER_PLUGIN_DIR . 'includes/class-elementor.php';
        require_once DOCMANAGER_PLUGIN_DIR . 'includes/widgets/class-widget-view-documents.php';
        require_once DOCMANAGER_PLUGIN_DIR . 'includes/widgets/class-widget-upload-documents.php';
        require_once DOCMANAGER_PLUGIN_DIR . 'includes/widgets/class-widget-manage-documents.php';
    }
    
    public function loadTextDomain() {
        load_plugin_textdomain('docmanager', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    public function activate() {
        // Creare directory per i file
        if (!file_exists(DOCMANAGER_UPLOAD_DIR)) {
            wp_mkdir_p(DOCMANAGER_UPLOAD_DIR);
            
            // Creare file .htaccess per proteggere la directory
            $htaccess_content = "deny from all\n";
            file_put_contents(DOCMANAGER_UPLOAD_DIR . '.htaccess', $htaccess_content);
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Creare tabelle custom se necessario
        $this->createTables();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function createTables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'docmanager_logs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            document_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45),
            user_agent text,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY document_id (document_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Inizializzare il plugin
DocManager::getInstance();