<?php
/**
 * Classe per gestire l'area admin e le impostazioni
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenus'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendScripts'));
        add_action('init', array($this, 'hideAdminBar'));
        add_action('wp_ajax_docmanager_delete_document', array($this, 'ajaxDeleteDocument'));
        add_action('wp_ajax_docmanager_toggle_status', array($this, 'ajaxToggleStatus'));
    }
    
    public function addAdminMenus() {
        add_submenu_page(
            'edit.php?post_type=referto',
            __('Impostazioni DocManager', 'docmanager'),
            __('Impostazioni', 'docmanager'),
            'manage_options',
            'docmanager-settings',
            array($this, 'settingsPage')
        );
        
        add_submenu_page(
            'edit.php?post_type=referto',
            __('Log Accessi', 'docmanager'),
            __('Log Accessi', 'docmanager'),
            'manage_options',
            'docmanager-logs',
            array($this, 'logsPage')
        );
    }
    
    public function registerSettings() {
        register_setting('docmanager_settings', 'docmanager_options');
        
        add_settings_section(
            'docmanager_general',
            __('Impostazioni Generali', 'docmanager'),
            array($this, 'generalSectionCallback'),
            'docmanager_settings'
        );
        
        add_settings_field(
            'hide_admin_bar',
            __('Nascondi Barra Admin', 'docmanager'),
            array($this, 'hideAdminBarCallback'),
            'docmanager_settings',
            'docmanager_general'
        );
        
        add_settings_field(
            'allowed_file_types',
            __('Tipi File Consentiti', 'docmanager'),
            array($this, 'allowedFileTypesCallback'),
            'docmanager_settings',
            'docmanager_general'
        );
        
        add_settings_field(
            'max_file_size',
            __('Dimensione Massima File (MB)', 'docmanager'),
            array($this, 'maxFileSizeCallback'),
            'docmanager_settings',
            'docmanager_general'
        );
        
        add_settings_field(
            'enable_logging',
            __('Abilita Log degli Accessi', 'docmanager'),
            array($this, 'enableLoggingCallback'),
            'docmanager_settings',
            'docmanager_general'
        );
        
        add_settings_field(
            'protected_pages',
            __('Pagine Protette', 'docmanager'),
            array($this, 'protectedPagesCallback'),
            'docmanager_settings',
            'docmanager_general'
        );
    }
    
    public function settingsPage() {
        ?>
        <div class="wrap">
            <h1><?php _e('Impostazioni DocManager', 'docmanager'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('docmanager_settings');
                do_settings_sections('docmanager_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    public function generalSectionCallback() {
        echo '<p>' . __('Configura le impostazioni generali del plugin DocManager.', 'docmanager') . '</p>';
    }
    
    public function hideAdminBarCallback() {
        $options = get_option('docmanager_options');
        $roles = wp_roles()->roles;
        
        echo '<fieldset>';
        echo '<legend class="screen-reader-text">' . __('Nascondi Barra Admin', 'docmanager') . '</legend>';
        
        foreach ($roles as $role_key => $role) {
            $checked = isset($options['hide_admin_bar'][$role_key]) ? checked($options['hide_admin_bar'][$role_key], 1, false) : '';
            echo '<label>';
            echo '<input type="checkbox" name="docmanager_options[hide_admin_bar][' . $role_key . ']" value="1" ' . $checked . '>';
            echo ' ' . $role['name'];
            echo '</label><br>';
        }
        echo '</fieldset>';
    }
    
    public function allowedFileTypesCallback() {
        $options = get_option('docmanager_options');
        $allowed_types = isset($options['allowed_file_types']) ? $options['allowed_file_types'] : 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif';
        
        echo '<input type="text" name="docmanager_options[allowed_file_types]" value="' . esc_attr($allowed_types) . '" class="large-text" />';
        echo '<p class="description">' . __('Inserisci le estensioni consentite separate da virgola (es: pdf,doc,docx,jpg)', 'docmanager') . '</p>';
    }
    
    public function maxFileSizeCallback() {
        $options = get_option('docmanager_options');
        $max_size = isset($options['max_file_size']) ? $options['max_file_size'] : 10;
        
        echo '<input type="number" name="docmanager_options[max_file_size]" value="' . esc_attr($max_size) . '" min="1" max="100" />';
        echo '<p class="description">' . __('Dimensione massima in MB per ogni file caricato', 'docmanager') . '</p>';
    }
    
    public function enableLoggingCallback() {
        $options = get_option('docmanager_options');
        $enabled = isset($options['enable_logging']) ? $options['enable_logging'] : 0;
        
        echo '<input type="checkbox" name="docmanager_options[enable_logging]" value="1" ' . checked($enabled, 1, false) . ' />';
        echo '<label for="docmanager_options[enable_logging]">' . __('Abilita il log degli accessi ai documenti', 'docmanager') . '</label>';
    }
    
    public function protectedPagesCallback() {
        $options = get_option('docmanager_options');
        $protected_pages = isset($options['protected_pages']) ? $options['protected_pages'] : array();
        
        $pages = get_pages();
        
        echo '<fieldset>';
        echo '<legend class="screen-reader-text">' . __('Pagine Protette', 'docmanager') . '</legend>';
        
        foreach ($pages as $page) {
            $checked = in_array($page->ID, $protected_pages) ? 'checked="checked"' : '';
            echo '<label>';
            echo '<input type="checkbox" name="docmanager_options[protected_pages][]" value="' . $page->ID . '" ' . $checked . '>';
            echo ' ' . $page->post_title;
            echo '</label><br>';
        }
        echo '</fieldset>';
        echo '<p class="description">' . __('Seleziona le pagine che richiedono autenticazione. Gli utenti non loggati saranno reindirizzati alla pagina di login.', 'docmanager') . '</p>';
    }
    
    public function logsPage() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'docmanager_logs';
        
        // Paginazione
        $per_page = 50;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_items / $per_page);
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name, p.post_title 
             FROM $table_name l 
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID 
             LEFT JOIN {$wpdb->posts} p ON l.document_id = p.ID 
             ORDER BY l.timestamp DESC 
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        ?>
        <div class="wrap">
            <h1><?php _e('Log Accessi Documenti', 'docmanager'); ?></h1>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <a href="<?php echo admin_url('admin.php?page=docmanager-logs&action=clear'); ?>" class="button" onclick="return confirm('<?php _e('Sei sicuro di voler cancellare tutti i log?', 'docmanager'); ?>');">
                        <?php _e('Cancella Log', 'docmanager'); ?>
                    </a>
                </div>
                
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Data/Ora', 'docmanager'); ?></th>
                        <th><?php _e('Utente', 'docmanager'); ?></th>
                        <th><?php _e('Documento', 'docmanager'); ?></th>
                        <th><?php _e('Azione', 'docmanager'); ?></th>
                        <th><?php _e('IP', 'docmanager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->timestamp)); ?></td>
                                <td><?php echo $log->display_name ? esc_html($log->display_name) : __('Utente eliminato', 'docmanager'); ?></td>
                                <td><?php echo $log->post_title ? esc_html($log->post_title) : __('Documento eliminato', 'docmanager'); ?></td>
                                <td><?php echo esc_html($log->action); ?></td>
                                <td><?php echo esc_html($log->ip_address); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5"><?php _e('Nessun log disponibile', 'docmanager'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        
        // Gestire cancellazione log
        if (isset($_GET['action']) && $_GET['action'] === 'clear') {
            $wpdb->query("TRUNCATE TABLE $table_name");
            wp_redirect(admin_url('admin.php?page=docmanager-logs'));
            exit;
        }
    }
    
    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'referto') !== false || strpos($hook, 'docmanager') !== false) {
            wp_enqueue_script('docmanager-admin', DOCMANAGER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DOCMANAGER_VERSION, true);
            wp_enqueue_style('docmanager-admin', DOCMANAGER_PLUGIN_URL . 'assets/css/admin.css', array(), DOCMANAGER_VERSION);
            
            wp_localize_script('docmanager-admin', 'docmanager_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('docmanager_nonce'),
                'confirm_delete' => __('Sei sicuro di voler eliminare questo documento?', 'docmanager'),
            ));
        }
    }
    
    public function enqueueFrontendScripts() {
        wp_enqueue_script('docmanager-frontend', DOCMANAGER_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), DOCMANAGER_VERSION, true);
        wp_enqueue_style('docmanager-frontend', DOCMANAGER_PLUGIN_URL . 'assets/css/frontend.css', array(), DOCMANAGER_VERSION);
        
        wp_localize_script('docmanager-frontend', 'docmanager_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('docmanager_nonce'),
            'uploading' => __('Caricamento in corso...', 'docmanager'),
            'upload_error' => __('Errore durante il caricamento', 'docmanager'),
            'upload_success' => __('File caricato con successo', 'docmanager'),
        ));
    }
    
    public function hideAdminBar() {
        $options = get_option('docmanager_options');
        
        if (isset($options['hide_admin_bar']) && is_array($options['hide_admin_bar'])) {
            $current_user = wp_get_current_user();
            $user_roles = $current_user->roles;
            
            foreach ($user_roles as $role) {
                if (isset($options['hide_admin_bar'][$role]) && $options['hide_admin_bar'][$role] == 1) {
                    add_filter('show_admin_bar', '__return_false');
                    break;
                }
            }
        }
    }
    
    public function ajaxDeleteDocument() {
        check_ajax_referer('docmanager_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Non hai i permessi necessari', 'docmanager'));
        }
        
        $document_id = intval($_POST['document_id']);
        
        if ($document_id) {
            // Eliminare il file fisico
            $file_handler = new DocManager_FileHandler();
            $file_handler->deleteFile($document_id);
            
            // Eliminare il post
            $result = wp_delete_post($document_id, true);
            
            if ($result) {
                wp_send_json_success(__('Documento eliminato con successo', 'docmanager'));
            } else {
                wp_send_json_error(__('Errore durante l\'eliminazione', 'docmanager'));
            }
        } else {
            wp_send_json_error(__('ID documento non valido', 'docmanager'));
        }
    }
    
    public function ajaxToggleStatus() {
        check_ajax_referer('docmanager_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Non hai i permessi necessari', 'docmanager'));
        }
        
        $document_id = intval($_POST['document_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        
        if ($document_id && in_array($new_status, array('publish', 'draft', 'private'))) {
            $result = wp_update_post(array(
                'ID' => $document_id,
                'post_status' => $new_status
            ));
            
            if ($result) {
                wp_send_json_success(__('Status aggiornato con successo', 'docmanager'));
            } else {
                wp_send_json_error(__('Errore durante l\'aggiornamento', 'docmanager'));
            }
        } else {
            wp_send_json_error(__('Parametri non validi', 'docmanager'));
        }
    }
}