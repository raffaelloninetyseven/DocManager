<?php
if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Elementor {
    
    public function __construct() {
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
        add_action('elementor/elements/categories_registered', array($this, 'add_elementor_category'));
    }
    
    public function add_elementor_category($elements_manager) {
        $elements_manager->add_category(
            'docmanager',
            array(
                'title' => __('Document Manager', 'docmanager'),
                'icon' => 'fa fa-file-text-o',
            )
        );
    }
    
    public function register_widgets() {
        // Verifica che i file widget esistano prima di includerli
        $documents_widget_file = DOCMANAGER_PLUGIN_PATH . 'includes/widgets/class-docmanager-documents-widget.php';
        $upload_widget_file = DOCMANAGER_PLUGIN_PATH . 'includes/widgets/class-docmanager-upload-widget.php';
        
        if (file_exists($documents_widget_file)) {
            require_once $documents_widget_file;
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Documents_Widget());
        }
        
        if (file_exists($upload_widget_file)) {
            require_once $upload_widget_file;
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Upload_Widget());
        }
    }
}

// Widget per visualizzare i documenti
class DocManager_Documents_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'docmanager_documents';
    }
    
    public function get_title() {
        return __('Document List', 'docmanager');
    }
    
    public function get_icon() {
        return 'eicon-document-file';
    }
    
    public function get_categories() {
        return array('docmanager');
    }
    
    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Content', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'layout',
            array(
                'label' => __('Layout', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'list',
                'options' => array(
                    'list' => __('List', 'docmanager'),
                    'grid' => __('Grid', 'docmanager'),
                    'table' => __('Table', 'docmanager'),
                ),
            )
        );
        
        $this->add_control(
            'show_category',
            array(
                'label' => __('Show Category', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'category_filter',
            array(
                'label' => __('Filter by Category', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __('Enter category name', 'docmanager'),
            )
        );
        
        $this->add_control(
            'show_description',
            array(
                'label' => __('Show Description', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'enable_search',
            array(
                'label' => __('Enable Search', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'no',
            )
        );
        
        $this->end_controls_section();
        
        // Style Section
        $this->start_controls_section(
            'style_section',
            array(
                'label' => __('Style', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'title_color',
            array(
                'label' => __('Title Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-document-title' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .docmanager-document-title',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (!is_user_logged_in()) {
            echo '<p>' . __('Please login to view documents.', 'docmanager') . '</p>';
            return;
        }
        
        $db = new DocManager_DB();
        $user_id = get_current_user_id();
        $user_roles = wp_get_current_user()->roles;
        
        $documents = $db->get_user_documents($user_id, $user_roles);
        
        if ($settings['category_filter']) {
            $documents = array_filter($documents, function($doc) use ($settings) {
                return $doc->category === $settings['category_filter'];
            });
        }
        
        echo '<div class="docmanager-documents-wrapper layout-' . $settings['layout'] . '">';
        
        if ($settings['enable_search'] === 'yes') {
            echo '<div class="docmanager-search-box">';
            echo '<input type="text" id="docmanager-search" placeholder="' . __('Search documents...', 'docmanager') . '">';
            echo '</div>';
        }
        
        if ($settings['layout'] === 'table') {
            $this->render_table_layout($documents, $settings);
        } elseif ($settings['layout'] === 'grid') {
            $this->render_grid_layout($documents, $settings);
        } else {
            $this->render_list_layout($documents, $settings);
        }
        
        echo '</div>';
    }
    
    private function render_list_layout($documents, $settings) {
        echo '<div class="docmanager-documents-list">';
        foreach ($documents as $doc) {
            echo '<div class="docmanager-document-item">';
            echo '<h3 class="docmanager-document-title">' . esc_html($doc->title) . '</h3>';
            
            if ($settings['show_category'] === 'yes' && $doc->category) {
                echo '<span class="docmanager-category">' . esc_html($doc->category) . '</span>';
            }
            
            if ($settings['show_description'] === 'yes' && $doc->description) {
                echo '<p class="docmanager-description">' . esc_html($doc->description) . '</p>';
            }
            
            echo '<div class="docmanager-actions">';
            echo '<a href="' . esc_url($doc->file_path) . '" class="docmanager-download-btn" target="_blank">' . __('Download', 'docmanager') . '</a>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    private function render_grid_layout($documents, $settings) {
        echo '<div class="docmanager-documents-grid">';
        foreach ($documents as $doc) {
            echo '<div class="docmanager-document-card">';
            echo '<div class="docmanager-file-icon">';
            echo $this->get_file_icon($doc->file_type);
            echo '</div>';
            echo '<h4 class="docmanager-document-title">' . esc_html($doc->title) . '</h4>';
            
            if ($settings['show_category'] === 'yes' && $doc->category) {
                echo '<span class="docmanager-category">' . esc_html($doc->category) . '</span>';
            }
            
            echo '<div class="docmanager-actions">';
            echo '<a href="' . esc_url($doc->file_path) . '" class="docmanager-download-btn" target="_blank">' . __('Download', 'docmanager') . '</a>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    private function render_table_layout($documents, $settings) {
        echo '<table class="docmanager-documents-table">';
        echo '<thead><tr>';
        echo '<th>' . __('Title', 'docmanager') . '</th>';
        if ($settings['show_category'] === 'yes') {
            echo '<th>' . __('Category', 'docmanager') . '</th>';
        }
        echo '<th>' . __('Type', 'docmanager') . '</th>';
        echo '<th>' . __('Size', 'docmanager') . '</th>';
        echo '<th>' . __('Actions', 'docmanager') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($documents as $doc) {
            echo '<tr>';
            echo '<td class="docmanager-document-title">' . esc_html($doc->title) . '</td>';
            if ($settings['show_category'] === 'yes') {
                echo '<td>' . esc_html($doc->category) . '</td>';
            }
            echo '<td>' . esc_html($doc->file_type) . '</td>';
            echo '<td>' . size_format($doc->file_size) . '</td>';
            echo '<td><a href="' . esc_url($doc->file_path) . '" class="docmanager-download-btn" target="_blank">' . __('Download', 'docmanager') . '</a></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    private function get_file_icon($file_type) {
        $icons = array(
            'application/pdf' => '📄',
            'application/msword' => '📝',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '📝',
            'application/vnd.ms-excel' => '📊',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '📊',
            'image/jpeg' => '🖼️',
            'image/png' => '🖼️',
        );
        
        return isset($icons[$file_type]) ? $icons[$file_type] : '📁';
    }
}

// Widget per upload documenti
class DocManager_Upload_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'docmanager_upload';
    }
    
    public function get_title() {
        return __('Document Upload', 'docmanager');
    }
    
    public function get_icon() {
        return 'eicon-upload';
    }
    
    public function get_categories() {
        return array('docmanager');
    }
    
    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Content', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'upload_title',
            array(
                'label' => __('Title', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Upload Document', 'docmanager'),
            )
        );
        
        $this->add_control(
            'allowed_files',
            array(
                'label' => __('Allowed File Types', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'description' => __('Comma separated file extensions', 'docmanager'),
            )
        );
        
        $this->add_control(
            'max_file_size',
            array(
                'label' => __('Max File Size (MB)', 'docmanager'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 10,
            )
        );
        
        $this->add_control(
            'show_category_field',
            array(
                'label' => __('Show Category Field', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'auto_assign_permission',
            array(
                'label' => __('Auto Assign to Current User', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (!is_user_logged_in()) {
            echo '<p>' . __('Please login to upload documents.', 'docmanager') . '</p>';
            return;
        }
        
        wp_enqueue_script('docmanager-upload', DOCMANAGER_PLUGIN_URL . 'assets/js/upload.js', array('jquery'), DOCMANAGER_VERSION, true);
        wp_localize_script('docmanager-upload', 'docmanager_upload', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('docmanager_upload_nonce'),
            'max_size' => $settings['max_file_size'] * 1024 * 1024,
            'allowed_types' => explode(',', $settings['allowed_files'])
        ));
        ?>
        
        <div class="docmanager-upload-widget">
            <h3><?php echo esc_html($settings['upload_title']); ?></h3>
            
            <form id="docmanager-upload-form" enctype="multipart/form-data">
                <?php wp_nonce_field('docmanager_upload_nonce', 'upload_nonce'); ?>
                
                <div class="docmanager-form-group">
                    <label for="doc_title"><?php _e('Document Title', 'docmanager'); ?></label>
                    <input type="text" id="doc_title" name="doc_title" required>
                </div>
                
                <div class="docmanager-form-group">
                    <label for="doc_description"><?php _e('Description', 'docmanager'); ?></label>
                    <textarea id="doc_description" name="doc_description" rows="3"></textarea>
                </div>
                
                <?php if ($settings['show_category_field'] === 'yes'): ?>
                <div class="docmanager-form-group">
                    <label for="doc_category"><?php _e('Category', 'docmanager'); ?></label>
                    <input type="text" id="doc_category" name="doc_category">
                </div>
                <?php endif; ?>
                
                <div class="docmanager-form-group">
                    <label for="doc_file"><?php _e('Select File', 'docmanager'); ?></label>
                    <div class="docmanager-file-drop-zone">
                        <input type="file" id="doc_file" name="doc_file" required>
                        <p><?php _e('Drag and drop file here or click to browse', 'docmanager'); ?></p>
                        <small><?php printf(__('Max size: %dMB. Allowed types: %s', 'docmanager'), $settings['max_file_size'], $settings['allowed_files']); ?></small>
                    </div>
                </div>
                
                <div class="docmanager-upload-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <span class="progress-text">0%</span>
                </div>
                
                <button type="submit" class="docmanager-submit-btn"><?php _e('Upload Document', 'docmanager'); ?></button>
            </form>
            
            <div class="docmanager-upload-result"></div>
        </div>
        <?php
    }
}