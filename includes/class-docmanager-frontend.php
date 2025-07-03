<?php
if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Frontend {
    
    private $db;
    
    public function __construct() {
        $this->db = new DocManager_DB();
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('docmanager_documents', array($this, 'documents_shortcode'));
        add_shortcode('docmanager_upload', array($this, 'upload_shortcode'));
        add_action('init', array($this, 'handle_download'));
    }
    
    public function enqueue_scripts() {
        if (is_user_logged_in()) {
            wp_enqueue_script('docmanager-frontend', DOCMANAGER_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), DOCMANAGER_VERSION, true);
            wp_enqueue_style('docmanager-frontend', DOCMANAGER_PLUGIN_URL . 'assets/css/frontend.css', array(), DOCMANAGER_VERSION);
            
            wp_localize_script('docmanager-frontend', 'docmanager_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('docmanager_frontend_nonce'),
                'messages' => array(
                    'confirm_delete' => __('Are you sure you want to delete this document?', 'docmanager'),
                    'upload_success' => __('Document uploaded successfully!', 'docmanager'),
                    'upload_error' => __('Error uploading document. Please try again.', 'docmanager')
                )
            ));
        }
    }
    
    public function documents_shortcode($atts) {
        $atts = shortcode_atts(array(
            'layout' => 'list',
            'category' => '',
            'show_search' => 'true',
            'show_category' => 'true',
            'show_description' => 'true',
            'posts_per_page' => 10
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="docmanager-login-required">' . 
                   __('Please login to view documents.', 'docmanager') . 
                   '</div>';
        }
        
        $user_id = get_current_user_id();
        $user_roles = wp_get_current_user()->roles;
        
        $documents = $this->db->get_user_documents($user_id, $user_roles);
        
        if (!empty($atts['category'])) {
            $documents = array_filter($documents, function($doc) use ($atts) {
                return $doc->category === $atts['category'];
            });
        }
        
        ob_start();
        ?>
        <div class="docmanager-documents-container" data-layout="<?php echo esc_attr($atts['layout']); ?>">
            
            <?php if ($atts['show_search'] === 'true'): ?>
            <div class="docmanager-search-container">
                <form class="docmanager-search-form">
                    <input type="text" 
                           class="docmanager-search-input" 
                           placeholder="<?php _e('Search documents...', 'docmanager'); ?>" 
                           name="search_term">
                    <button type="submit" class="docmanager-search-btn">
                        <?php _e('Search', 'docmanager'); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="docmanager-documents-wrapper layout-<?php echo esc_attr($atts['layout']); ?>">
                <?php if (empty($documents)): ?>
                    <p class="docmanager-no-documents"><?php _e('No documents found.', 'docmanager'); ?></p>
                <?php else: ?>
                    <?php $this->render_documents($documents, $atts); ?>
                <?php endif; ?>
            </div>
            
            <div class="docmanager-pagination">
                <!-- Pagination sarà gestita via AJAX -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function upload_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Upload Document', 'docmanager'),
            'show_category' => 'true',
            'allowed_types' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
            'max_size' => '10'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="docmanager-login-required">' . 
                   __('Please login to upload documents.', 'docmanager') . 
                   '</div>';
        }
        
        ob_start();
        ?>
        <div class="docmanager-upload-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            
            <form class="docmanager-upload-form" enctype="multipart/form-data">
                <?php wp_nonce_field('docmanager_upload_nonce', 'upload_nonce'); ?>
                
                <div class="docmanager-form-row">
                    <label for="doc_title"><?php _e('Document Title', 'docmanager'); ?> *</label>
                    <input type="text" id="doc_title" name="doc_title" required>
                </div>
                
                <div class="docmanager-form-row">
                    <label for="doc_description"><?php _e('Description', 'docmanager'); ?></label>
                    <textarea id="doc_description" name="doc_description" rows="4"></textarea>
                </div>
                
                <?php if ($atts['show_category'] === 'true'): ?>
                <div class="docmanager-form-row">
                    <label for="doc_category"><?php _e('Category', 'docmanager'); ?></label>
                    <input type="text" id="doc_category" name="doc_category" 
                           placeholder="<?php _e('e.g. Contracts, Reports, etc.', 'docmanager'); ?>">
                </div>
                <?php endif; ?>
                
                <div class="docmanager-form-row">
                    <label for="doc_tags"><?php _e('Tags', 'docmanager'); ?></label>
                    <input type="text" id="doc_tags" name="doc_tags" 
                           placeholder="<?php _e('Separate tags with commas', 'docmanager'); ?>">
                </div>
                
                <div class="docmanager-form-row">
                    <label for="doc_file"><?php _e('Select File', 'docmanager'); ?> *</label>
                    <div class="docmanager-file-upload-area">
                        <input type="file" id="doc_file" name="doc_file" required 
                               accept=".<?php echo str_replace(',', ',.', $atts['allowed_types']); ?>">
                        <div class="docmanager-drop-zone">
                            <span class="docmanager-drop-icon">📁</span>
                            <p><?php _e('Drag and drop your file here, or click to browse', 'docmanager'); ?></p>
                            <small>
                                <?php printf(
                                    __('Max size: %sMB. Allowed types: %s', 'docmanager'), 
                                    $atts['max_size'], 
                                    $atts['allowed_types']
                                ); ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="docmanager-upload-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <span class="progress-percentage">0%</span>
                </div>
                
                <div class="docmanager-form-row">
                    <button type="submit" class="docmanager-upload-submit">
                        <?php _e('Upload Document', 'docmanager'); ?>
                    </button>
                </div>
            </form>
            
            <div class="docmanager-upload-messages"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_documents($documents, $atts) {
        switch ($atts['layout']) {
            case 'grid':
                $this->render_grid_layout($documents, $atts);
                break;
            case 'table':
                $this->render_table_layout($documents, $atts);
                break;
            case 'cards':
                $this->render_cards_layout($documents, $atts);
                break;
            default:
                $this->render_list_layout($documents, $atts);
                break;
        }
    }
    
    private function render_list_layout($documents, $atts) {
        echo '<div class="docmanager-documents-list">';
        foreach ($documents as $doc) {
            ?>
            <div class="docmanager-document-item" data-doc-id="<?php echo $doc->id; ?>">
                <div class="docmanager-doc-header">
                    <h4 class="docmanager-doc-title"><?php echo esc_html($doc->title); ?></h4>
                    <?php if ($atts['show_category'] === 'true' && !empty($doc->category)): ?>
                        <span class="docmanager-doc-category"><?php echo esc_html($doc->category); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($atts['show_description'] === 'true' && !empty($doc->description)): ?>
                    <div class="docmanager-doc-description">
                        <?php echo esc_html($doc->description); ?>
                    </div>
                <?php endif; ?>
                
                <div class="docmanager-doc-meta">
                    <span class="docmanager-doc-size"><?php echo size_format($doc->file_size); ?></span>
                    <span class="docmanager-doc-date"><?php echo date_i18n(get_option('date_format'), strtotime($doc->upload_date)); ?></span>
                </div>
                
                <div class="docmanager-doc-actions">
                    <a href="<?php echo esc_url($this->get_download_url($doc->id)); ?>" 
                       class="docmanager-btn docmanager-btn-download" 
                       target="_blank">
                        <?php _e('Download', 'docmanager'); ?>
                    </a>
                    <?php if ($this->user_can_preview($doc->file_type)): ?>
                        <a href="<?php echo esc_url($doc->file_path); ?>" 
                           class="docmanager-btn docmanager-btn-preview" 
                           target="_blank">
                            <?php _e('Preview', 'docmanager'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    }
    
    private function render_grid_layout($documents, $atts) {
        echo '<div class="docmanager-documents-grid">';
        foreach ($documents as $doc) {
            ?>
            <div class="docmanager-document-card" data-doc-id="<?php echo $doc->id; ?>">
                <div class="docmanager-card-icon">
                    <?php echo $this->get_file_icon($doc->file_type); ?>
                </div>
                
                <div class="docmanager-card-content">
                    <h4 class="docmanager-card-title"><?php echo esc_html($doc->title); ?></h4>
                    
                    <?php if ($atts['show_category'] === 'true' && !empty($doc->category)): ?>
                        <span class="docmanager-card-category"><?php echo esc_html($doc->category); ?></span>
                    <?php endif; ?>
                    
                    <div class="docmanager-card-meta">
                        <small><?php echo size_format($doc->file_size); ?></small>
                    </div>
                </div>
                
                <div class="docmanager-card-actions">
                    <a href="<?php echo esc_url($this->get_download_url($doc->id)); ?>" 
                       class="docmanager-btn-icon" 
                       title="<?php _e('Download', 'docmanager'); ?>"
                       target="_blank">
                        ⬇️
                    </a>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    }
    
    private function render_table_layout($documents, $atts) {
        ?>
        <table class="docmanager-documents-table">
            <thead>
                <tr>
                    <th><?php _e('Title', 'docmanager'); ?></th>
                    <?php if ($atts['show_category'] === 'true'): ?>
                        <th><?php _e('Category', 'docmanager'); ?></th>
                    <?php endif; ?>
                    <th><?php _e('Type', 'docmanager'); ?></th>
                    <th><?php _e('Size', 'docmanager'); ?></th>
                    <th><?php _e('Date', 'docmanager'); ?></th>
                    <th><?php _e('Actions', 'docmanager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                <tr data-doc-id="<?php echo $doc->id; ?>">
                    <td class="docmanager-table-title"><?php echo esc_html($doc->title); ?></td>
                    <?php if ($atts['show_category'] === 'true'): ?>
                        <td><?php echo esc_html($doc->category); ?></td>
                    <?php endif; ?>
                    <td><?php echo $this->get_file_type_label($doc->file_type); ?></td>
                    <td><?php echo size_format($doc->file_size); ?></td>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($doc->upload_date)); ?></td>
                    <td>
                        <a href="<?php echo esc_url($this->get_download_url($doc->id)); ?>" 
                           class="docmanager-btn-small" target="_blank">
                            <?php _e('Download', 'docmanager'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    private function render_cards_layout($documents, $atts) {
        echo '<div class="docmanager-documents-cards">';
        foreach ($documents as $doc) {
            ?>
            <div class="docmanager-document-card-full" data-doc-id="<?php echo $doc->id; ?>">
                <div class="docmanager-card-header">
                    <div class="docmanager-card-icon-large">
                        <?php echo $this->get_file_icon($doc->file_type); ?>
                    </div>
                    <div class="docmanager-card-info">
                        <h4><?php echo esc_html($doc->title); ?></h4>
                        <?php if ($atts['show_category'] === 'true' && !empty($doc->category)): ?>
                            <span class="docmanager-category-tag"><?php echo esc_html($doc->category); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($atts['show_description'] === 'true' && !empty($doc->description)): ?>
                    <div class="docmanager-card-description">
                        <?php echo esc_html($doc->description); ?>
                    </div>
                <?php endif; ?>
                
                <div class="docmanager-card-footer">
                    <div class="docmanager-card-meta">
                        <span><?php echo size_format($doc->file_size); ?></span>
                        <span><?php echo date_i18n(get_option('date_format'), strtotime($doc->upload_date)); ?></span>
                    </div>
                    <div class="docmanager-card-actions">
                        <a href="<?php echo esc_url($this->get_download_url($doc->id)); ?>" 
                           class="docmanager-btn docmanager-btn-primary" 
                           target="_blank">
                            <?php _e('Download', 'docmanager'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    }
    
    private function get_file_icon($file_type) {
        $icons = array(
            'application/pdf' => '📄',
            'application/msword' => '📝',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '📝',
            'application/vnd.ms-excel' => '📊',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '📊',
            'image/jpeg' => '🖼️',
            'image/jpg' => '🖼️',
            'image/png' => '🖼️',
        );
        
        return isset($icons[$file_type]) ? $icons[$file_type] : '📁';
    }
    
    private function get_file_type_label($file_type) {
        $labels = array(
            'application/pdf' => 'PDF',
            'application/msword' => 'DOC',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
            'application/vnd.ms-excel' => 'XLS',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
            'image/jpeg' => 'JPG',
            'image/jpg' => 'JPG',
            'image/png' => 'PNG',
        );
        
        return isset($labels[$file_type]) ? $labels[$file_type] : strtoupper(pathinfo($file_type, PATHINFO_EXTENSION));
    }
    
    private function user_can_preview($file_type) {
        $previewable_types = array(
            'application/pdf',
            'image/jpeg',
            'image/jpg', 
            'image/png'
        );
        
        return in_array($file_type, $previewable_types);
    }
    
    private function get_download_url($document_id) {
        return add_query_arg(array(
            'docmanager_download' => $document_id,
            'nonce' => wp_create_nonce('docmanager_download_' . $document_id)
        ), home_url());
    }
    
    public function handle_download() {
        if (!isset($_GET['docmanager_download'])) {
            return;
        }
        
        $document_id = intval($_GET['docmanager_download']);
        $nonce = $_GET['nonce'];
        
        if (!wp_verify_nonce($nonce, 'docmanager_download_' . $document_id)) {
            wp_die(__('Security check failed', 'docmanager'));
        }
        
        if (!is_user_logged_in()) {
            wp_die(__('Please login to download documents', 'docmanager'));
        }
        
        // Verifica permessi
        $permissions = new DocManager_Permissions();
        if (!$permissions->user_can_view_document(get_current_user_id(), $document_id)) {
            wp_die(__('You do not have permission to download this document', 'docmanager'));
        }
        
        // Ottieni informazioni documento
        global $wpdb;
        $documents_table = $wpdb->prefix . 'docmanager_documents';
        
        $document = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$documents_table} WHERE id = %d AND status = 'active'",
            $document_id
        ));
        
        if (!$document) {
            wp_die(__('Document not found', 'docmanager'));
        }
        
        // Log del download
        if (get_option('docmanager_enable_logs', 'yes') === 'yes') {
            $this->db->log_action(get_current_user_id(), $document_id, 'download');
        }
        
        // Forza il download
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $document->file_path);
        
        if (file_exists($file_path)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $document->file_name . '"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            wp_die(__('File not found on server', 'docmanager'));
        }
    }
}