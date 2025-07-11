<?php
/**
 * Classe per gestire la dashboard principale di DocManager - versione completa
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Dashboard {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'addDashboardMenu'));
        add_action('wp_ajax_docmanager_dashboard_stats', array($this, 'getDashboardStats'));
        add_action('wp_ajax_docmanager_test_upload', array($this, 'testUpload'));
    }
    
    public function addDashboardMenu() {
        add_menu_page(
            __('DocManager Dashboard', 'docmanager'),
            __('DocManager', 'docmanager'),
            'manage_options',
            'docmanager-dashboard',
            array($this, 'dashboardPage'),
            'dashicons-media-document',
            25
        );
        
        add_submenu_page(
            'docmanager-dashboard',
            __('Dashboard', 'docmanager'),
            __('Dashboard', 'docmanager'),
            'manage_options',
            'docmanager-dashboard',
            array($this, 'dashboardPage')
        );
        
        add_submenu_page(
            'docmanager-dashboard',
            __('Tutti i Documenti', 'docmanager'),
            __('Tutti i Documenti', 'docmanager'),
            'manage_options',
            'edit.php?post_type=referto'
        );
        
        add_submenu_page(
            'docmanager-dashboard',
            __('Test Sistema', 'docmanager'),
            __('Test Sistema', 'docmanager'),
            'manage_options',
            'docmanager-test',
            array($this, 'testPage')
        );
    }
    
    public function dashboardPage() {
        ?>
        <div class="wrap">
            <h1><?php _e('DocManager Dashboard', 'docmanager'); ?></h1>
            
            <div class="docmanager-dashboard">
                <div class="docmanager-stats-row">
                    <?php $this->renderStats(); ?>
                </div>
                
                <div class="docmanager-quick-actions">
                    <div class="docmanager-card">
                        <h3><?php _e('Azioni Rapide', 'docmanager'); ?></h3>
                        <div class="docmanager-actions-grid">
                            <a href="<?php echo admin_url('post-new.php?post_type=referto'); ?>" class="docmanager-action-btn">
                                <i class="dashicons dashicons-plus"></i>
                                <?php _e('Nuovo Documento', 'docmanager'); ?>
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=referto'); ?>" class="docmanager-action-btn">
                                <i class="dashicons dashicons-list-view"></i>
                                <?php _e('Tutti i Documenti', 'docmanager'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=docmanager-settings'); ?>" class="docmanager-action-btn">
                                <i class="dashicons dashicons-admin-settings"></i>
                                <?php _e('Impostazioni', 'docmanager'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=docmanager-test'); ?>" class="docmanager-action-btn">
                                <i class="dashicons dashicons-performance"></i>
                                <?php _e('Test Sistema', 'docmanager'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="docmanager-recent-activity">
                    <div class="docmanager-card">
                        <h3><?php _e('Documenti Recenti', 'docmanager'); ?></h3>
                        <?php $this->renderRecentDocuments(); ?>
                    </div>
                </div>
                
                <div class="docmanager-system-status">
                    <div class="docmanager-card">
                        <h3><?php _e('Stato Sistema', 'docmanager'); ?></h3>
                        <?php $this->renderSystemStatus(); ?>
                    </div>
                </div>
            </div>
            
            <style>
            .docmanager-dashboard {
                display: grid;
                gap: 20px;
                margin-top: 20px;
            }
            
            .docmanager-stats-row {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
            }
            
            .docmanager-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            .docmanager-stat-card {
                text-align: center;
                padding: 30px 20px;
            }
            
            .docmanager-stat-number {
                font-size: 48px;
                font-weight: bold;
                color: #0073aa;
                margin-bottom: 10px;
            }
            
            .docmanager-stat-label {
                font-size: 14px;
                color: #666;
                text-transform: uppercase;
                font-weight: 600;
            }
            
            .docmanager-actions-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin-top: 15px;
            }
            
            .docmanager-action-btn {
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 20px;
                background: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 4px;
                text-decoration: none;
                color: #333;
                transition: all 0.3s ease;
            }
            
            .docmanager-action-btn:hover {
                background: #0073aa;
                color: #fff;
                text-decoration: none;
            }
            
            .docmanager-action-btn .dashicons {
                font-size: 32px;
                margin-bottom: 10px;
            }
            
            .docmanager-recent-list {
                margin-top: 15px;
            }
            
            .docmanager-recent-item {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid #eee;
            }
            
            .docmanager-recent-item:last-child {
                border-bottom: none;
            }
            
            .docmanager-status-list {
                margin-top: 15px;
            }
            
            .docmanager-status-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }
            
            .docmanager-status-item:last-child {
                border-bottom: none;
            }
            
            .docmanager-status-ok {
                color: #46b450;
            }
            
            .docmanager-status-warning {
                color: #ffb900;
            }
            
            .docmanager-status-error {
                color: #dc3232;
            }
            </style>
        </div>
        <?php
    }
    
    public function testPage() {
        if (isset($_POST['test_upload'])) {
            $this->runUploadTest();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Test Sistema DocManager', 'docmanager'); ?></h1>
            
            <div class="docmanager-test-section">
                <div class="docmanager-card">
                    <h3><?php _e('Test Configurazione', 'docmanager'); ?></h3>
                    <?php $this->renderConfigTest(); ?>
                </div>
                
                <div class="docmanager-card">
                    <h3><?php _e('Test Upload File', 'docmanager'); ?></h3>
                    <form method="post" enctype="multipart/form-data">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('File di Test', 'docmanager'); ?></th>
                                <td>
                                    <input type="file" name="test_file" accept=".pdf,.jpg,.png,.doc,.docx">
                                    <p class="description"><?php _e('Carica un file per testare il sistema di upload', 'docmanager'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(__('Testa Upload', 'docmanager'), 'primary', 'test_upload'); ?>
                    </form>
                </div>
                
                <div class="docmanager-card">
                    <h3><?php _e('Log Debug', 'docmanager'); ?></h3>
                    <textarea readonly style="width: 100%; height: 200px;" id="debug-log"><?php echo $this->getDebugLog(); ?></textarea>
                    <button type="button" class="button" onclick="location.reload()"><?php _e('Aggiorna Log', 'docmanager'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function renderStats() {
        $counts = wp_count_posts('referto');
        $total_docs = 0;
        
        if (isset($counts->publish)) {
            $total_docs += intval($counts->publish);
        }
        if (isset($counts->draft)) {
            $total_docs += intval($counts->draft);
        }
        if (isset($counts->private)) {
            $total_docs += intval($counts->private);
        }
        
        $total_users = count(get_users());
        $upload_dir_size = $this->getUploadDirSize();
        
        $recent_uploads = get_posts(array(
            'post_type' => 'referto',
            'posts_per_page' => -1,
            'meta_key' => '_docmanager_upload_date',
            'meta_query' => array(
                array(
                    'key' => '_docmanager_upload_date',
                    'value' => date('Y-m-d', strtotime('-7 days')),
                    'compare' => '>='
                )
            )
        ));
        
        $stats = array(
            array(
                'number' => $total_docs,
                'label' => __('Documenti Totali', 'docmanager')
            ),
            array(
                'number' => $total_users,
                'label' => __('Utenti Totali', 'docmanager')
            ),
            array(
                'number' => size_format($upload_dir_size),
                'label' => __('Spazio Utilizzato', 'docmanager')
            ),
            array(
                'number' => count($recent_uploads),
                'label' => __('Upload Questa Settimana', 'docmanager')
            )
        );
        
        foreach ($stats as $stat) {
            echo '<div class="docmanager-card docmanager-stat-card">';
            echo '<div class="docmanager-stat-number">' . esc_html($stat['number']) . '</div>';
            echo '<div class="docmanager-stat-label">' . esc_html($stat['label']) . '</div>';
            echo '</div>';
        }
    }
    
    private function renderRecentDocuments() {
        $recent_docs = get_posts(array(
            'post_type' => 'referto',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($recent_docs)) {
            echo '<p>' . __('Nessun documento trovato', 'docmanager') . '</p>';
            return;
        }
        
        echo '<div class="docmanager-recent-list">';
        foreach ($recent_docs as $doc) {
            $user_id = get_post_meta($doc->ID, '_docmanager_assigned_user', true);
            $user = $user_id ? get_userdata($user_id) : null;
            
            echo '<div class="docmanager-recent-item">';
            echo '<div>';
            echo '<strong>' . esc_html($doc->post_title) . '</strong><br>';
            echo '<small>' . __('Assegnato a:', 'docmanager') . ' ' . ($user ? esc_html($user->display_name) : __('Non assegnato', 'docmanager')) . '</small>';
            echo '</div>';
            echo '<div>';
            echo '<small>' . get_the_date('', $doc) . '</small>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    private function renderSystemStatus() {
        $status_items = array(
            array(
                'label' => __('Directory Upload', 'docmanager'),
                'status' => is_writable(DOCMANAGER_UPLOAD_DIR) ? 'ok' : 'error',
                'value' => is_writable(DOCMANAGER_UPLOAD_DIR) ? __('Scrivibile', 'docmanager') : __('Non Scrivibile', 'docmanager')
            ),
            array(
                'label' => __('Limite Upload PHP', 'docmanager'),
                'status' => 'ok',
                'value' => ini_get('upload_max_filesize')
            ),
            array(
                'label' => __('Post Max Size', 'docmanager'),
                'status' => 'ok',
                'value' => ini_get('post_max_size')
            ),
            array(
                'label' => __('Memory Limit', 'docmanager'),
                'status' => 'ok',
                'value' => ini_get('memory_limit')
            ),
            array(
                'label' => __('Elementor Attivo', 'docmanager'),
                'status' => is_plugin_active('elementor/elementor.php') ? 'ok' : 'warning',
                'value' => is_plugin_active('elementor/elementor.php') ? __('Sì', 'docmanager') : __('No', 'docmanager')
            )
        );
        
        echo '<div class="docmanager-status-list">';
        foreach ($status_items as $item) {
            echo '<div class="docmanager-status-item">';
            echo '<span>' . esc_html($item['label']) . '</span>';
            echo '<span class="docmanager-status-' . $item['status'] . '">' . esc_html($item['value']) . '</span>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    private function renderConfigTest() {
        $tests = array(
            'upload_dir' => array(
                'label' => __('Directory Upload Exists', 'docmanager'),
                'test' => file_exists(DOCMANAGER_UPLOAD_DIR),
                'fix' => __('Creare directory manualmente', 'docmanager')
            ),
            'upload_writable' => array(
                'label' => __('Directory Upload Writable', 'docmanager'),
                'test' => is_writable(DOCMANAGER_UPLOAD_DIR),
                'fix' => __('Impostare permessi 755 o 777', 'docmanager')
            ),
            'htaccess' => array(
                'label' => __('.htaccess Protection', 'docmanager'),
                'test' => file_exists(DOCMANAGER_UPLOAD_DIR . '.htaccess'),
                'fix' => __('File .htaccess verrà creato automaticamente', 'docmanager')
            ),
            'db_table' => array(
                'label' => __('Tabella Log Database', 'docmanager'),
                'test' => $this->checkLogTable(),
                'fix' => __('Disattivare e riattivare il plugin', 'docmanager')
            )
        );
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>' . __('Test', 'docmanager') . '</th><th>' . __('Stato', 'docmanager') . '</th><th>' . __('Soluzione', 'docmanager') . '</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($tests as $test) {
            $status_class = $test['test'] ? 'ok' : 'error';
            $status_text = $test['test'] ? '✓ ' . __('OK', 'docmanager') : '✗ ' . __('ERRORE', 'docmanager');
            
            echo '<tr>';
            echo '<td>' . esc_html($test['label']) . '</td>';
            echo '<td><span class="docmanager-status-' . $status_class . '">' . $status_text . '</span></td>';
            echo '<td>' . esc_html($test['fix']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    private function runUploadTest() {
        if (!isset($_FILES['test_file']) || $_FILES['test_file']['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="notice notice-error"><p>' . __('Errore: Nessun file caricato o errore durante l\'upload', 'docmanager') . '</p></div>';
            return;
        }
        
        $file_handler = new DocManager_FileHandler();
        
        $post_data = array(
            'post_title' => 'Test Upload - ' . date('Y-m-d H:i:s'),
            'post_type' => 'referto',
            'post_status' => 'draft',
            'post_author' => get_current_user_id(),
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            echo '<div class="notice notice-error"><p>' . __('Errore nella creazione del post:', 'docmanager') . ' ' . $post_id->get_error_message() . '</p></div>';
            return;
        }
        
        $file_result = $file_handler->uploadFile($_FILES['test_file'], $post_id);
        
        if ($file_result) {
            update_post_meta($post_id, '_docmanager_file_id', $file_result['file_id']);
            update_post_meta($post_id, '_docmanager_file_name', $file_result['file_name']);
            update_post_meta($post_id, '_docmanager_file_size', $file_result['file_size']);
            update_post_meta($post_id, '_docmanager_file_type', $file_result['file_type']);
            update_post_meta($post_id, '_docmanager_unique_filename', $file_result['unique_filename']);
            update_post_meta($post_id, '_docmanager_upload_date', current_time('mysql'));
            update_post_meta($post_id, '_docmanager_assigned_user', get_current_user_id());
            
            echo '<div class="notice notice-success"><p>' . __('Test completato con successo! Documento creato con ID:', 'docmanager') . ' ' . $post_id . '</p></div>';
            echo '<div class="notice notice-info"><p>' . __('File salvato come:', 'docmanager') . ' ' . $file_result['unique_filename'] . '</p></div>';
        } else {
            wp_delete_post($post_id, true);
            echo '<div class="notice notice-error"><p>' . __('Errore durante il caricamento del file', 'docmanager') . '</p></div>';
        }
    }
    
    private function getUploadDirSize() {
        $size = 0;
        if (is_dir(DOCMANAGER_UPLOAD_DIR)) {
            $files = glob(DOCMANAGER_UPLOAD_DIR . '*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $size += filesize($file);
                    }
                }
            }
        }
        return $size;
    }
    
    private function checkLogTable() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'docmanager_logs';
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    }
    
    private function getDebugLog() {
        $log_entries = array();
        
        $log_entries[] = '[' . current_time('mysql') . '] DocManager Debug Log';
        $log_entries[] = 'Plugin Version: ' . DOCMANAGER_VERSION;
        $log_entries[] = 'WordPress Version: ' . get_bloginfo('version');
        $log_entries[] = 'PHP Version: ' . PHP_VERSION;
        $log_entries[] = 'Upload Dir: ' . DOCMANAGER_UPLOAD_DIR;
        $log_entries[] = 'Upload Dir Exists: ' . (file_exists(DOCMANAGER_UPLOAD_DIR) ? 'Yes' : 'No');
        $log_entries[] = 'Upload Dir Writable: ' . (is_writable(DOCMANAGER_UPLOAD_DIR) ? 'Yes' : 'No');
        $log_entries[] = 'Max Upload Size: ' . ini_get('upload_max_filesize');
        $log_entries[] = 'Post Max Size: ' . ini_get('post_max_size');
        $log_entries[] = 'Memory Limit: ' . ini_get('memory_limit');
        
        $recent_uploads = get_posts(array(
            'post_type' => 'referto',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $log_entries[] = 'Recent Documents Count: ' . count($recent_uploads);
        
        return implode("\n", $log_entries);
    }
    
    public function getDashboardStats() {
        check_ajax_referer('docmanager_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti', 'docmanager'));
        }
        
        $counts = wp_count_posts('referto');
        $total_docs = 0;
        
        if (isset($counts->publish)) {
            $total_docs += intval($counts->publish);
        }
        if (isset($counts->draft)) {
            $total_docs += intval($counts->draft);
        }
        if (isset($counts->private)) {
            $total_docs += intval($counts->private);
        }
        
        $stats = array(
            'total_documents' => $total_docs,
            'total_users' => count(get_users()),
            'upload_size' => $this->getUploadDirSize(),
        );
        
        wp_send_json_success($stats);
    }
}