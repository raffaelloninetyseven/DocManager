<?php
if (!defined('ABSPATH')) {
    exit;
}

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
    
    public function get_keywords() {
        return array('documents', 'files', 'download', 'docmanager');
    }
    
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Content Settings', 'docmanager'),
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
                    'cards' => __('Cards', 'docmanager'),
                ),
            )
        );
        
        $this->add_control(
            'posts_per_page',
            array(
                'label' => __('Documents per Page', 'docmanager'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 10,
                'min' => 1,
                'max' => 50,
            )
        );
        
        $this->add_control(
            'category_filter',
            array(
                'label' => __('Filter by Category', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __('Enter category name', 'docmanager'),
                'description' => __('Leave empty to show all categories', 'docmanager'),
            )
        );
        
        $this->add_control(
            'show_search',
            array(
                'label' => __('Show Search Box', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
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
            'show_description',
            array(
                'label' => __('Show Description', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_file_info',
            array(
                'label' => __('Show File Info', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show file size and upload date', 'docmanager'),
            )
        );
        
        $this->add_control(
            'enable_preview',
            array(
                'label' => __('Enable Preview', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show preview button for supported files', 'docmanager'),
            )
        );
        
        $this->end_controls_section();
        
        // Labels Section - Sezione per personalizzare le etichette
        $this->start_controls_section(
            'labels_section',
            array(
                'label' => __('Custom Labels', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'label_search_placeholder',
            array(
                'label' => __('Search Placeholder', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Search documents...', 'docmanager'),
                'condition' => array('show_search' => 'yes'),
            )
        );
        
        $this->add_control(
            'label_search_button',
            array(
                'label' => __('Search Button', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Search', 'docmanager'),
                'condition' => array('show_search' => 'yes'),
            )
        );
        
        $this->add_control(
            'label_download_button',
            array(
                'label' => __('Download Button', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Download', 'docmanager'),
            )
        );
        
        $this->add_control(
            'label_preview_button',
            array(
                'label' => __('Preview Button', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Preview', 'docmanager'),
                'condition' => array('enable_preview' => 'yes'),
            )
        );
        
        $this->add_control(
            'label_no_documents',
            array(
                'label' => __('No Documents Message', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('No documents found.', 'docmanager'),
            )
        );
        
        $this->add_control(
            'label_login_required',
            array(
                'label' => __('Login Required Message', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Please login to view documents.', 'docmanager'),
            )
        );
        
        // Table Headers
        $this->add_control(
            'label_table_title',
            array(
                'label' => __('Table Header: Title', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Title', 'docmanager'),
                'condition' => array('layout' => 'table'),
            )
        );
        
        $this->add_control(
            'label_table_category',
            array(
                'label' => __('Table Header: Category', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Category', 'docmanager'),
                'condition' => array('layout' => 'table', 'show_category' => 'yes'),
            )
        );
        
        $this->add_control(
            'label_table_type',
            array(
                'label' => __('Table Header: Type', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Type', 'docmanager'),
                'condition' => array('layout' => 'table'),
            )
        );
        
        $this->add_control(
            'label_table_size',
            array(
                'label' => __('Table Header: Size', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Size', 'docmanager'),
                'condition' => array('layout' => 'table', 'show_file_info' => 'yes'),
            )
        );
        
        $this->add_control(
            'label_table_date',
            array(
                'label' => __('Table Header: Date', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Date', 'docmanager'),
                'condition' => array('layout' => 'table', 'show_file_info' => 'yes'),
            )
        );
        
        $this->add_control(
            'label_table_actions',
            array(
                'label' => __('Table Header: Actions', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Actions', 'docmanager'),
                'condition' => array('layout' => 'table'),
            )
        );
        
        $this->end_controls_section();
        
        // Grid Settings (only for grid layout)
        $this->start_controls_section(
            'grid_section',
            array(
                'label' => __('Grid Settings', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                'condition' => array(
                    'layout' => array('grid', 'cards'),
                ),
            )
        );
        
        $this->add_responsive_control(
            'columns',
            array(
                'label' => __('Columns', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-documents-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                    '{{WRAPPER}} .docmanager-documents-cards' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ),
            )
        );
        
        $this->add_responsive_control(
            'column_gap',
            array(
                'label' => __('Column Gap', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'default' => array(
                    'size' => 20,
                ),
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 50,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-documents-grid' => 'gap: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .docmanager-documents-cards' => 'gap: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Style Section
        $this->start_controls_section(
            'style_section',
            array(
                'label' => __('General Style', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'title_color',
            array(
                'label' => __('Title Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-doc-title, {{WRAPPER}} .docmanager-card-title, {{WRAPPER}} .docmanager-table-title' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .docmanager-doc-title, {{WRAPPER}} .docmanager-card-title, {{WRAPPER}} .docmanager-table-title',
            )
        );
        
        $this->add_control(
            'description_color',
            array(
                'label' => __('Description Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-doc-description, {{WRAPPER}} .docmanager-card-description' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'description_typography',
                'selector' => '{{WRAPPER}} .docmanager-doc-description, {{WRAPPER}} .docmanager-card-description',
            )
        );
        
        $this->end_controls_section();
        
        // Button Style Section
        $this->start_controls_section(
            'button_style_section',
            array(
                'label' => __('Button Style', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'button_background_color',
            array(
                'label' => __('Background Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-btn-download' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'button_text_color',
            array(
                'label' => __('Text Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-btn-download' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'button_hover_background',
            array(
                'label' => __('Hover Background', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#005a87',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-btn-download:hover' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .docmanager-btn-download',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (!is_user_logged_in()) {
            echo '<div class="docmanager-login-required">';
            echo '<p>' . esc_html($settings['label_login_required']) . '</p>';
            echo '</div>';
            return;
        }
        
        // Enqueue necessari scripts e styles
        wp_enqueue_script('docmanager-frontend');
        wp_enqueue_style('docmanager-frontend');
        
        $db = new DocManager_DB();
        $user_id = get_current_user_id();
        $user_roles = wp_get_current_user()->roles;
        
        // Ottieni documenti per l'utente corrente
        $documents = $db->get_user_documents($user_id, $user_roles);
        
        // Applica filtro categoria se specificato
        if (!empty($settings['category_filter'])) {
            $documents = array_filter($documents, function($doc) use ($settings) {
                return $doc->category === $settings['category_filter'];
            });
        }
        
        // Limita il numero di documenti
        if (!empty($settings['posts_per_page']) && $settings['posts_per_page'] > 0) {
            $documents = array_slice($documents, 0, intval($settings['posts_per_page']));
        }
        
        // Wrapper principale
        echo '<div class="docmanager-documents-wrapper elementor-widget-docmanager" data-layout="' . esc_attr($settings['layout']) . '">';
        
        // Search box
        if ($settings['show_search'] === 'yes') {
            $this->render_search_box($settings);
        }
        
        // Contenuto documenti
        if (empty($documents)) {
            echo '<div class="docmanager-no-documents">';
            echo '<p>' . esc_html($settings['label_no_documents']) . '</p>';
            echo '</div>';
        } else {
            $this->render_documents($documents, $settings);
        }
        
        echo '</div>';
    }
    
    private function render_search_box($settings) {
        ?>
        <div class="docmanager-search-container">
            <form class="docmanager-search-form">
                <input type="text" 
                       class="docmanager-search-input" 
                       placeholder="<?php echo esc_attr($settings['label_search_placeholder']); ?>" 
                       name="search_term">
                <button type="submit" class="docmanager-search-btn">
                    <?php echo esc_html($settings['label_search_button']); ?>
                </button>
            </form>
        </div>
        <?php
    }
    
    private function render_documents($documents, $settings) {
        switch ($settings['layout']) {
            case 'grid':
                $this->render_grid_layout($documents, $settings);
                break;
            case 'table':
                $this->render_table_layout($documents, $settings);
                break;
            case 'cards':
                $this->render_cards_layout($documents, $settings);
                break;
            default:
                $this->render_list_layout($documents, $settings);
                break;
        }
    }
    
    private function render_list_layout($documents, $settings) {
        echo '<div class="docmanager-documents-list">';
        foreach ($documents as $doc) {
            ?>
            <div class="docmanager-document-item" data-doc-id="<?php echo esc_attr($doc->id); ?>">
                <div class="docmanager-doc-header">
                    <h3 class="docmanager-doc-title"><?php echo esc_html($doc->title); ?></h3>
                    <?php if ($settings['show_category'] === 'yes' && !empty($doc->category)): ?>
                        <span class="docmanager-doc-category"><?php echo esc_html($doc->category); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($settings['show_description'] === 'yes' && !empty($doc->description)): ?>
                    <div class="docmanager-doc-description">
                        <?php echo esc_html($doc->description); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($settings['show_file_info'] === 'yes'): ?>
                    <div class="docmanager-doc-meta">
                        <span class="docmanager-doc-size"><?php echo size_format($doc->file_size); ?></span>
                        <span class="docmanager-doc-date"><?php echo date_i18n(get_option('date_format'), strtotime($doc->upload_date)); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="docmanager-doc-actions">
                    <a href="<?php echo esc_url($this->get_download_url($doc->id)); ?>" 
                       class="docmanager-btn docmanager-btn-download" 
                       target="_blank">
                        <?php echo esc_html($settings['label_download_button']); ?>
                    </a>
                    <?php if ($settings['enable_preview'] === 'yes' && $this->can_preview($doc->file_type)): ?>
                        <a href="<?php echo esc_url($doc->file_path); ?>" 
                           class="docmanager-btn docmanager-btn-preview" 
                           target="_blank">
                            <?php echo esc_html($settings['label_preview_button']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    }
    
    private function render_table_layout($documents, $settings) {
        ?>
        <div class="docmanager-table-wrapper">
            <table class="docmanager-documents-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html($settings['label_table_title']); ?></th>
                        <?php if ($settings['show_category'] === 'yes'): ?>
                            <th><?php echo esc_html($settings['label_table_category']); ?></th>
                        <?php endif; ?>
                        <th><?php echo esc_html($settings['label_table_type']); ?></th>
                        <?php if ($settings['show_file_info'] === 'yes'): ?>
                            <th><?php echo esc_html($settings['label_table_size']); ?></th>
                            <th><?php echo esc_html($settings['label_table_date']); ?></th>
                        <?php endif; ?>
                        <th><?php echo esc_html($settings['label_table_actions']); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <tr data-doc-id="<?php echo esc_attr($doc->id); ?>">
                        <td class="docmanager-table-title"><?php echo esc_html($doc->title); ?></td>
                        <?php if ($settings['show_category'] === 'yes'): ?>
                            <td><?php echo esc_html($doc->category); ?></td>
                        <?php endif; ?>
                        <td><?php echo $this->get_file_type_label($doc->file_type); ?></td>
                        <?php if ($settings['show_file_info'] === 'yes'): ?>
                            <td><?php echo size_format($doc->file_size); ?></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($doc->upload_date)); ?></td>
                        <?php endif; ?>
                        <td>
                            <a href="<?php echo esc_url($this->get_download_url($doc->id)); ?>" 
                               class="docmanager-btn docmanager-btn-small" 
                               target="_blank">
                                <?php echo esc_html($settings['label_download_button']); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    private function render_grid_layout($documents, $settings) {
        echo '<div class="docmanager-documents-grid">';
        foreach ($documents as $doc) {
            ?>
            <div class="docmanager-document-card" data-doc-id="<?php echo esc_attr($doc->id); ?>">
                <div class="docmanager-card-icon">
                    <?php echo $this->get_file_icon($doc->file_type); ?>
                </div>
                
                <div class="docmanager-card-content">
                    <h4 class="docmanager-card-title"><?php echo esc_html($doc->title); ?></h4>
                    
                    <?php if ($settings['show_category'] === 'yes' && !empty($doc->category)): ?>
                        <span class="docmanager-card-category"><?php echo esc_html($doc->category); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_file_info'] === 'yes'): ?>
                        <div class="docmanager-card-meta">
                            <small><?php echo size_format($doc->file_size); ?></small>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="docmanager-card-actions">
                    <a href="<?php echo esc_url($this->get_download_url($doc->id)); ?>" 
                       class="docmanager-btn docmanager-btn-download" 
                       target="_blank">
                        <?php echo esc_html($settings['label_download_button']); ?>
                    </a>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    }
    
    private function render_cards_layout($documents, $settings) {
        echo '<div class="docmanager-documents-cards">';
        foreach ($documents as $doc) {
            ?>
            <div class="docmanager-document-card-full" data-doc-id="<?php echo esc_attr($doc->id); ?>">
                <div class="docmanager-card-header">
                    <div class="docmanager-card-icon-large">
                        <?php echo $this->get_file_icon($doc->file_type); ?>
                    </div>
                    <div class="docmanager-card-info">
                        <h4><?php echo esc_html($doc->title); ?></h4>
                        <?php if ($settings['show_category'] === 'yes' && !empty($doc->category)): ?>
                            <span class="docmanager-category-tag"><?php echo esc_html($doc->category); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($settings['show_description'] === 'yes' && !empty($doc->description)): ?>
                    <div class="docmanager-card-description">
                        <?php echo esc_html($doc->description); ?>
                    </div>
                <?php endif; ?>
                
                <div class="docmanager-card-footer">
                    <?php if ($settings['show_file_info'] === 'yes'): ?>
                        <div class="docmanager-card-meta">
                            <span><?php echo size_format($doc->file_size); ?></span>
                            <span><?php echo date_i18n(get_option('date_format'), strtotime($doc->upload_date)); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="docmanager-card-actions">
                        <a href="<?php echo esc_url($this->get_download_url($doc->id)); ?>" 
                           class="docmanager-btn docmanager-btn-primary" 
                           target="_blank">
                            <?php echo esc_html($settings['label_download_button']); ?>
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
    
    private function can_preview($file_type) {
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
}