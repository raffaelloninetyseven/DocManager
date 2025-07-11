<?php
/**
 * Plugin Name: DocManager
 * Description: Plugin per la gestione documentale con area riservata integrato con Elementor
 * Version: 0.2.9
 * Author: SilverStudioDM
 * Text Domain: docmanager
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DOCMANAGER_VERSION', '0.2.9');
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
        add_action('admin_notices', array($this, 'adminNotices'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        $this->loadClasses();
        
        new DocManager_Dashboard();
        new DocManager_PostType();
        new DocManager_Admin();
        new DocManager_Security();
        new DocManager_FileHandler();
        
        if (did_action('elementor/loaded')) {
            new DocManager_Elementor();
        }
        
        $this->checkSystemRequirements();
    }
    
    private function loadClasses() {
        $classes = array(
            'includes/class-dashboard.php',
            'includes/class-post-type.php',
            'includes/class-admin.php',
            'includes/class-security.php',
            'includes/class-file-handler.php',
            'includes/class-elementor.php',
            'includes/widgets/class-widget-view-documents.php',
            'includes/widgets/class-widget-upload-documents.php',
            'includes/widgets/class-widget-manage-documents.php',
        );
        
        foreach ($classes as $class) {
            $file_path = DOCMANAGER_PLUGIN_DIR . $class;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    public function loadTextDomain() {
        load_plugin_textdomain('docmanager', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    public function activate() {
        $this->createUploadDirectory();
        $this->createTables();
        $this->setDefaultOptions();
        
        flush_rewrite_rules();
        
        add_option('docmanager_activation_redirect', true);
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function createUploadDirectory() {
        if (!file_exists(DOCMANAGER_UPLOAD_DIR)) {
            wp_mkdir_p(DOCMANAGER_UPLOAD_DIR);
            
            $htaccess_content = "deny from all\n";
            file_put_contents(DOCMANAGER_UPLOAD_DIR . '.htaccess', $htaccess_content);
            
            $index_content = "<?php\n// Silence is golden.\n";
            file_put_contents(DOCMANAGER_UPLOAD_DIR . 'index.php', $index_content);
        }
        
        if (!is_writable(DOCMANAGER_UPLOAD_DIR)) {
            chmod(DOCMANAGER_UPLOAD_DIR, 0755);
        }
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
            KEY document_id (document_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function setDefaultOptions() {
        $default_options = array(
            'allowed_file_types' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
            'max_file_size' => 10,
            'enable_logging' => 1,
            'hide_admin_bar' => array(),
            'protected_pages' => array(),
        );
        
        $existing_options = get_option('docmanager_options', array());
        $merged_options = array_merge($default_options, $existing_options);
        
        update_option('docmanager_options', $merged_options);
    }
    
    private function checkSystemRequirements() {
        $requirements_met = true;
        $errors = array();
        
        if (!is_writable(DOCMANAGER_UPLOAD_DIR)) {
            $requirements_met = false;
            $errors[] = sprintf(__('La directory %s non è scrivibile. Controlla i permessi.', 'docmanager'), DOCMANAGER_UPLOAD_DIR);
        }
        
        $upload_max = $this->parseSize(ini_get('upload_max_filesize'));
        $post_max = $this->parseSize(ini_get('post_max_size'));
        
        if ($upload_max < 1048576) {
            $errors[] = __('upload_max_filesize è troppo basso (minimo 1MB raccomandato).', 'docmanager');
        }
        
        if ($post_max < 1048576) {
            $errors[] = __('post_max_size è troppo basso (minimo 1MB raccomandato).', 'docmanager');
        }
        
        if (!empty($errors)) {
            set_transient('docmanager_system_errors', $errors, 300);
        }
    }
    
    private function parseSize($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        
        return round($size);
    }
    
    public function adminNotices() {
        if (get_option('docmanager_activation_redirect', false)) {
            delete_option('docmanager_activation_redirect');
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('DocManager attivato!', 'docmanager'); ?></strong>
                    <?php _e('Vai alla', 'docmanager'); ?> 
                    <a href="<?php echo admin_url('admin.php?page=docmanager-dashboard'); ?>"><?php _e('Dashboard', 'docmanager'); ?></a>
                    <?php _e('per iniziare.', 'docmanager'); ?>
                </p>
            </div>
            <?php
        }
        
        $system_errors = get_transient('docmanager_system_errors');
        if ($system_errors && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-warning">
                <p><strong><?php _e('DocManager - Avvisi di Sistema:', 'docmanager'); ?></strong></p>
                <ul>
                    <?php foreach ($system_errors as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=docmanager-test'); ?>" class="button">
                        <?php _e('Vai al Test Sistema', 'docmanager'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
        
        if (!file_exists(DOCMANAGER_UPLOAD_DIR) && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php _e('DocManager - Errore:', 'docmanager'); ?></strong>
                    <?php printf(__('La directory di upload %s non esiste.', 'docmanager'), DOCMANAGER_UPLOAD_DIR); ?>
                    <a href="<?php echo admin_url('admin.php?page=docmanager-test'); ?>" class="button button-small">
                        <?php _e('Risolvi', 'docmanager'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
}

function docmanager_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("DocManager [{$level}]: {$message}");
    }
}

function docmanager_get_user_documents($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    return get_posts(array(
        'post_type' => 'referto',
        'meta_key' => '_docmanager_assigned_user',
        'meta_value' => $user_id,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
}

function docmanager_is_document_expired($document_id) {
    $expiry_date = get_post_meta($document_id, '_docmanager_expiry_date', true);
    
    if (!$expiry_date) {
        return false;
    }
    
    return strtotime($expiry_date) < current_time('timestamp');
}

function docmanager_can_user_access_document($document_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (current_user_can('manage_options')) {
        return true;
    }
    
    $assigned_user = get_post_meta($document_id, '_docmanager_assigned_user', true);
    
    if ($assigned_user && $assigned_user == $user_id) {
        if (docmanager_is_document_expired($document_id)) {
            return false;
        }
        return true;
    }
    
    return false;
}

DocManager::getInstance();