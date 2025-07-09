<?php
/**
 * Plugin Name: Document Manager Pro
 * Description: Sistema di gestione documentale con integrazione Elementor e area riservata
 * Version: 0.2.0
 * Author: SilverStudioDM
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DOCMANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DOCMANAGER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DOCMANAGER_VERSION', '0.2.0');

class DocManagerPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('wp_ajax_docmanager_apply_language_preset', array($this, 'ajax_apply_language_preset'));
        add_action('wp_ajax_nopriv_docmanager_apply_language_preset', array($this, 'ajax_apply_language_preset'));
        add_action('elementor/editor/after_enqueue_scripts', array($this, 'enqueue_elementor_scripts'));
        add_action('elementor/editor/after_enqueue_styles', array($this, 'enqueue_elementor_styles'));
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
            'includes/class-docmanager-permissions.php',
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
        if (class_exists('DocManager_Permissions')) {
            new DocManager_Permissions();
        }
        if (class_exists('DocManager_Elementor')) {
            new DocManager_Elementor();
        }
        if (class_exists('DocManager_Ajax')) {
            new DocManager_Ajax();
        }
    }
    
    public function ajax_apply_language_preset() {
        if (!wp_verify_nonce($_POST['nonce'], 'docmanager_elementor_nonce')) {
            wp_die('Security check failed');
        }
        
        $language = sanitize_text_field($_POST['language']);
        $widget_type = sanitize_text_field($_POST['widget_type']);
        
        $presets = $this->get_language_presets();
        
        if (isset($presets[$widget_type][$language])) {
            wp_send_json_success($presets[$widget_type][$language]);
        } else {
            wp_send_json_error('Language preset not found');
        }
    }
    
    private function get_language_presets() {
        return array(
            'docmanager_documents' => array(
                'it' => array(
                    'label_search_placeholder' => 'Cerca documenti...',
                    'label_search_button' => 'Cerca',
                    'label_download_button' => 'Scarica',
                    'label_preview_button' => 'Anteprima',
                    'label_no_documents' => 'Nessun documento trovato.',
                    'label_login_required' => 'Effettua il login per visualizzare i documenti.',
                    'label_table_title' => 'Titolo',
                    'label_table_category' => 'Categoria',
                    'label_table_type' => 'Tipo',
                    'label_table_size' => 'Dimensione',
                    'label_table_date' => 'Data',
                    'label_table_actions' => 'Azioni'
                ),
                'en' => array(
                    'label_search_placeholder' => 'Search documents...',
                    'label_search_button' => 'Search',
                    'label_download_button' => 'Download',
                    'label_preview_button' => 'Preview',
                    'label_no_documents' => 'No documents found.',
                    'label_login_required' => 'Please login to view documents.',
                    'label_table_title' => 'Title',
                    'label_table_category' => 'Category',
                    'label_table_type' => 'Type',
                    'label_table_size' => 'Size',
                    'label_table_date' => 'Date',
                    'label_table_actions' => 'Actions'
                )
            ),
            'docmanager_upload' => array(
                'it' => array(
                    'label_title_field' => 'Titolo Documento',
                    'label_description_field' => 'Descrizione',
                    'label_category_field' => 'Categoria',
                    'label_tags_field' => 'Tag',
                    'label_file_field' => 'Seleziona File',
                    'label_upload_button' => 'Carica Documento',
                    'label_drop_zone' => 'Trascina qui il file, o clicca per sfogliare',
                    'label_file_info' => 'Dimensione massima: %sMB. Tipi consentiti: %s',
                    'label_login_required' => 'Effettua il login per caricare documenti.',
                    'label_upload_success' => 'Documento caricato con successo!',
                    'label_upload_error' => 'Errore durante il caricamento. Riprova.',
                    'placeholder_title' => 'Inserisci il titolo del documento',
                    'placeholder_description' => 'Inserisci una descrizione',
                    'placeholder_category' => 'es. Contratti, Report, ecc.',
                    'placeholder_tags' => 'Separa i tag con virgole'
                ),
                'en' => array(
                    'label_title_field' => 'Document Title',
                    'label_description_field' => 'Description',
                    'label_category_field' => 'Category',
                    'label_tags_field' => 'Tags',
                    'label_file_field' => 'Select File',
                    'label_upload_button' => 'Upload Document',
                    'label_drop_zone' => 'Drag and drop file here, or click to browse',
                    'label_file_info' => 'Max size: %sMB. Allowed types: %s',
                    'label_login_required' => 'Please login to upload documents.',
                    'label_upload_success' => 'Document uploaded successfully!',
                    'label_upload_error' => 'Error uploading document. Please try again.',
                    'placeholder_title' => 'Enter document title',
                    'placeholder_description' => 'Enter description',
                    'placeholder_category' => 'e.g. Contracts, Reports, etc.',
                    'placeholder_tags' => 'Separate tags with commas'
                )
            ),
            'docmanager_manage' => array(
                'it' => array(
                    'label_search_placeholder' => 'Cerca i tuoi documenti...',
                    'label_search_button' => 'Cerca',
                    'label_edit_button' => 'Modifica',
                    'label_delete_button' => 'Elimina',
                    'label_download_button' => 'Scarica',
                    'label_preview_button' => 'Anteprima',
                    'label_share_button' => 'Condividi',
                    'label_save_button' => 'Salva Modifiche',
                    'label_cancel_button' => 'Annulla',
                    'label_no_documents' => 'Non hai ancora caricato documenti.',
                    'label_login_required' => 'Effettua il login per gestire i documenti.',
                    'label_confirm_delete' => 'Sei sicuro di voler eliminare questo documento?',
                    'label_document_updated' => 'Documento aggiornato con successo!',
                    'label_document_deleted' => 'Documento eliminato con successo!',
                    'label_title_field' => 'Titolo Documento',
                    'label_description_field' => 'Descrizione',
                    'label_category_field' => 'Categoria',
                    'label_tags_field' => 'Tag'
                ),
                'en' => array(
                    'label_search_placeholder' => 'Search your documents...',
                    'label_search_button' => 'Search',
                    'label_edit_button' => 'Edit',
                    'label_delete_button' => 'Delete',
                    'label_download_button' => 'Download',
                    'label_preview_button' => 'Preview',
                    'label_share_button' => 'Share',
                    'label_save_button' => 'Save Changes',
                    'label_cancel_button' => 'Cancel',
                    'label_no_documents' => 'You haven\'t uploaded any documents yet.',
                    'label_login_required' => 'Please login to manage your documents.',
                    'label_confirm_delete' => 'Are you sure you want to delete this document?',
                    'label_document_updated' => 'Document updated successfully!',
                    'label_document_deleted' => 'Document deleted successfully!',
                    'label_title_field' => 'Document Title',
                    'label_description_field' => 'Description',
                    'label_category_field' => 'Category',
                    'label_tags_field' => 'Tags'
                )
            )
        );
    }
    
    public function enqueue_elementor_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            
            // Funzione globale per applicare preset lingua
            window.docmanagerApplyLanguagePreset = function(language, widgetType, panelView) {
                
                if (!language || language === 'custom') {
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'docmanager_apply_language_preset',
                        language: language,
                        widget_type: widgetType,
                        nonce: '<?php echo wp_create_nonce('docmanager_elementor_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var preset = response.data;
                            var elementSettings = panelView.getElementSettingsModel();
                            
                            // Applica ogni valore del preset
                            Object.keys(preset).forEach(function(key) {
                                elementSettings.set(key, preset[key]);
                            });
                            
                            // Notifica all'utente
                            elementor.notifications.showToast({
                                message: 'Preset lingua applicato con successo!',
                                type: 'success'
                            });
                            
                            // Forza il refresh del pannello
                            setTimeout(function() {
                                panelView.refreshPanel();
                            }, 100);
                        }
                    },
                    error: function() {
                        elementor.notifications.showToast({
                            message: 'Errore nell\'applicazione del preset lingua.',
                            type: 'error'
                        });
                    }
                });
            };
            
            // Hook per tutti i widget docmanager
            ['docmanager_documents', 'docmanager_upload', 'docmanager_manage'].forEach(function(widgetType) {
                
                elementor.hooks.addAction('panel/open_editor/widget/' + widgetType, function(panel, model, view) {
                    
                    // Listener per il cambio di preset
                    view.listenTo(model.get('settings'), 'change:language_preset', function(settingsModel) {
                        var language = settingsModel.get('language_preset');
                        if (language && language !== 'custom') {
                            // Auto-apply dopo un delay
                            setTimeout(function() {
                                docmanagerApplyLanguagePreset(language, widgetType, view);
                            }, 300);
                        }
                    });
                });
            });
        });
        </script>
        <?php
    }
    
    public function enqueue_elementor_styles() {
        ?>
        <style>
        .elementor-control-apply_language_preset .elementor-control-input-wrapper {
            text-align: center;
        }
        
        .elementor-control-apply_language_preset button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            min-width: 100px;
        }
        
        .elementor-control-apply_language_preset button:hover {
            background: #005a87;
        }
        
        .elementor-control-language_preset {
            border-bottom: 1px solid #e6e9ec;
            margin-bottom: 15px;
            padding-bottom: 15px;
        }
        
        .elementor-control-language_preset .elementor-control-title {
            font-weight: bold;
            color: #0073aa;
        }
        
        .elementor-control-language_preset_section {
            background: #f8f9fa;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .elementor-control-language_preset_section .elementor-control-section_heading .elementor-control-title {
            color: #0073aa;
            font-weight: bold;
        }
        </style>
        <?php
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
        add_option('docmanager_hide_admin_bar', 'no');
        add_option('docmanager_hidden_bar_roles', array());
        add_option('docmanager_block_admin_access', 'no');
        add_option('docmanager_blocked_roles', array());
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