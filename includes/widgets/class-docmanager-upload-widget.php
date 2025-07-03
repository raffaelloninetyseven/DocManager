<?php
if (!defined('ABSPATH')) {
    exit;
}

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
    
    public function get_keywords() {
        return array('upload', 'documents', 'files', 'docmanager');
    }
    
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Upload Settings', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'upload_title',
            array(
                'label' => __('Form Title', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Upload Document', 'docmanager'),
                'placeholder' => __('Enter form title', 'docmanager'),
            )
        );
        
        $this->add_control(
            'upload_description',
            array(
                'label' => __('Form Description', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => __('Enter form description', 'docmanager'),
                'description' => __('Optional description shown above the form', 'docmanager'),
            )
        );
        
        $this->add_control(
            'allowed_files',
            array(
                'label' => __('Allowed File Types', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'description' => __('Comma separated file extensions (without dots)', 'docmanager'),
            )
        );
        
        $this->add_control(
            'max_file_size',
            array(
                'label' => __('Max File Size (MB)', 'docmanager'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 10,
                'min' => 1,
                'max' => 100,
                'description' => __('Maximum file size in megabytes', 'docmanager'),
            )
        );
        
        $this->add_control(
            'show_title_field',
            array(
                'label' => __('Show Title Field', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_description_field',
            array(
                'label' => __('Show Description Field', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
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
            'show_tags_field',
            array(
                'label' => __('Show Tags Field', 'docmanager'),
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
                'description' => __('Automatically assign view permission to the uploading user', 'docmanager'),
            )
        );
        
        $this->add_control(
            'redirect_after_upload',
            array(
                'label' => __('Redirect After Upload', 'docmanager'),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __('https://your-site.com/thank-you', 'docmanager'),
                'description' => __('Leave empty to stay on the same page', 'docmanager'),
            )
        );
        
        $this->end_controls_section();
        
        // Style Section - Form
        $this->start_controls_section(
            'form_style_section',
            array(
                'label' => __('Form Style', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'form_background_color',
            array(
                'label' => __('Background Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-upload-container' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name' => 'form_border',
                'selector' => '{{WRAPPER}} .docmanager-upload-container',
            )
        );
        
        $this->add_control(
            'form_border_radius',
            array(
                'label' => __('Border Radius', 'docmanager'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-upload-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'form_padding',
            array(
                'label' => __('Padding', 'docmanager'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'default' => array(
                    'top' => '25',
                    'right' => '25',
                    'bottom' => '25',
                    'left' => '25',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-upload-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'form_box_shadow',
                'selector' => '{{WRAPPER}} .docmanager-upload-container',
            )
        );
        
        $this->end_controls_section();
        
        // Style Section - Title
        $this->start_controls_section(
            'title_style_section',
            array(
                'label' => __('Title Style', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'title_color',
            array(
                'label' => __('Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-upload-container h3' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .docmanager-upload-container h3',
            )
        );
        
        $this->add_responsive_control(
            'title_margin',
            array(
                'label' => __('Margin', 'docmanager'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-upload-container h3' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Style Section - Fields
        $this->start_controls_section(
            'fields_style_section',
            array(
                'label' => __('Fields Style', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'label_color',
            array(
                'label' => __('Label Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-form-row label' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'label_typography',
                'selector' => '{{WRAPPER}} .docmanager-form-row label',
            )
        );
        
        $this->add_control(
            'input_background_color',
            array(
                'label' => __('Input Background', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-form-row input[type="text"], {{WRAPPER}} .docmanager-form-row textarea' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'input_text_color',
            array(
                'label' => __('Input Text Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-form-row input[type="text"], {{WRAPPER}} .docmanager-form-row textarea' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name' => 'input_border',
                'selector' => '{{WRAPPER}} .docmanager-form-row input[type="text"], {{WRAPPER}} .docmanager-form-row textarea',
            )
        );
        
        $this->add_control(
            'input_border_radius',
            array(
                'label' => __('Border Radius', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 20,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-form-row input[type="text"], {{WRAPPER}} .docmanager-form-row textarea' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'input_padding',
            array(
                'label' => __('Input Padding', 'docmanager'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-form-row input[type="text"], {{WRAPPER}} .docmanager-form-row textarea' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Style Section - Drop Zone
        $this->start_controls_section(
            'dropzone_style_section',
            array(
                'label' => __('Drop Zone Style', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'dropzone_background_color',
            array(
                'label' => __('Background Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#fafafa',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-drop-zone' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'dropzone_border_color',
            array(
                'label' => __('Border Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#dddddd',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-drop-zone' => 'border-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'dropzone_hover_background',
            array(
                'label' => __('Hover Background', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f0f8ff',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-drop-zone:hover, {{WRAPPER}} .docmanager-drop-zone.dragover' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'dropzone_hover_border',
            array(
                'label' => __('Hover Border Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-drop-zone:hover, {{WRAPPER}} .docmanager-drop-zone.dragover' => 'border-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_responsive_control(
            'dropzone_padding',
            array(
                'label' => __('Padding', 'docmanager'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'default' => array(
                    'top' => '40',
                    'right' => '20',
                    'bottom' => '40',
                    'left' => '20',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-drop-zone' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Style Section - Button
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
                    '{{WRAPPER}} .docmanager-upload-submit' => 'background-color: {{VALUE}}',
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
                    '{{WRAPPER}} .docmanager-upload-submit' => 'color: {{VALUE}}',
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
                    '{{WRAPPER}} .docmanager-upload-submit:hover' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .docmanager-upload-submit',
            )
        );
        
        $this->add_control(
            'button_border_radius',
            array(
                'label' => __('Border Radius', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 50,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-upload-submit' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'button_padding',
            array(
                'label' => __('Padding', 'docmanager'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-upload-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (!is_user_logged_in()) {
            echo '<div class="docmanager-login-required">';
            echo '<p>' . __('Please login to upload documents.', 'docmanager') . '</p>';
            echo '</div>';
            return;
        }
        
        // Enqueue scripts
        wp_enqueue_script('docmanager-frontend');
        wp_enqueue_style('docmanager-frontend');
        
        // Localizza script con impostazioni widget
        wp_localize_script('docmanager-frontend', 'docmanager_upload_widget', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('docmanager_upload_nonce'),
            'max_size' => $settings['max_file_size'] * 1024 * 1024,
            'allowed_types' => explode(',', str_replace(' ', '', $settings['allowed_files'])),
            'redirect_url' => !empty($settings['redirect_after_upload']['url']) ? $settings['redirect_after_upload']['url'] : ''
        ));
        ?>
        
        <div class="docmanager-upload-widget elementor-widget-docmanager-upload">
            <div class="docmanager-upload-container">
                <?php if (!empty($settings['upload_title'])): ?>
                    <h3><?php echo esc_html($settings['upload_title']); ?></h3>
                <?php endif; ?>
                
                <?php if (!empty($settings['upload_description'])): ?>
                    <div class="docmanager-upload-description">
                        <p><?php echo esc_html($settings['upload_description']); ?></p>
                    </div>
                <?php endif; ?>
                
                <form class="docmanager-upload-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('docmanager_upload_nonce', 'upload_nonce'); ?>
                    
                    <?php if ($settings['show_title_field'] === 'yes'): ?>
                    <div class="docmanager-form-row">
                        <label for="doc_title"><?php _e('Document Title', 'docmanager'); ?> *</label>
                        <input type="text" id="doc_title" name="doc_title" required>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_description_field'] === 'yes'): ?>
                    <div class="docmanager-form-row">
                        <label for="doc_description"><?php _e('Description', 'docmanager'); ?></label>
                        <textarea id="doc_description" name="doc_description" rows="3"></textarea>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_category_field'] === 'yes'): ?>
                    <div class="docmanager-form-row">
                        <label for="doc_category"><?php _e('Category', 'docmanager'); ?></label>
                        <input type="text" id="doc_category" name="doc_category" 
                               placeholder="<?php _e('e.g. Contracts, Reports, etc.', 'docmanager'); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_tags_field'] === 'yes'): ?>
                    <div class="docmanager-form-row">
                        <label for="doc_tags"><?php _e('Tags', 'docmanager'); ?></label>
                        <input type="text" id="doc_tags" name="doc_tags" 
                               placeholder="<?php _e('Separate tags with commas', 'docmanager'); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <div class="docmanager-form-row">
                        <label for="doc_file"><?php _e('Select File', 'docmanager'); ?> *</label>
                        <div class="docmanager-file-upload-area">
                            <input type="file" id="doc_file" name="doc_file" required 
                                   accept=".<?php echo str_replace(',', ',.', $settings['allowed_files']); ?>">
                            <div class="docmanager-drop-zone">
                                <span class="docmanager-drop-icon">📁</span>
                                <p><?php _e('Drag and drop your file here, or click to browse', 'docmanager'); ?></p>
                                <small>
                                    <?php printf(
                                        __('Max size: %sMB. Allowed types: %s', 'docmanager'), 
                                        $settings['max_file_size'], 
                                        $settings['allowed_files']
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
        </div>
        <?php
    }
    
    protected function content_template() {
        ?>
        <#
        var allowedFiles = settings.allowed_files || 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png';
        var maxSize = settings.max_file_size || 10;
        #>
        
        <div class="docmanager-upload-widget elementor-widget-docmanager-upload">
            <div class="docmanager-upload-container">
                <# if (settings.upload_title) { #>
                    <h3>{{{ settings.upload_title }}}</h3>
                <# } #>
                
                <# if (settings.upload_description) { #>
                    <div class="docmanager-upload-description">
                        <p>{{{ settings.upload_description }}}</p>
                    </div>
                <# } #>
                
                <form class="docmanager-upload-form">
                    
                    <# if (settings.show_title_field === 'yes') { #>
                    <div class="docmanager-form-row">
                        <label><?php _e('Document Title', 'docmanager'); ?> *</label>
                        <input type="text" placeholder="<?php _e('Enter document title', 'docmanager'); ?>">
                    </div>
                    <# } #>
                    
                    <# if (settings.show_description_field === 'yes') { #>
                    <div class="docmanager-form-row">
                        <label><?php _e('Description', 'docmanager'); ?></label>
                        <textarea rows="3" placeholder="<?php _e('Enter description', 'docmanager'); ?>"></textarea>
                    </div>
                    <# } #>
                    
                    <# if (settings.show_category_field === 'yes') { #>
                    <div class="docmanager-form-row">
                        <label><?php _e('Category', 'docmanager'); ?></label>
                        <input type="text" placeholder="<?php _e('e.g. Contracts, Reports, etc.', 'docmanager'); ?>">
                    </div>
                    <# } #>
                    
                    <# if (settings.show_tags_field === 'yes') { #>
                    <div class="docmanager-form-row">
                        <label><?php _e('Tags', 'docmanager'); ?></label>
                        <input type="text" placeholder="<?php _e('Separate tags with commas', 'docmanager'); ?>">
                    </div>
                    <# } #>
                    
                    <div class="docmanager-form-row">
                        <label><?php _e('Select File', 'docmanager'); ?> *</label>
                        <div class="docmanager-file-upload-area">
                            <div class="docmanager-drop-zone">
                                <span class="docmanager-drop-icon">📁</span>
                                <p><?php _e('Drag and drop your file here, or click to browse', 'docmanager'); ?></p>
                                <small>
                                    <?php _e('Max size:', 'docmanager'); ?> {{{ maxSize }}}MB. 
                                    <?php _e('Allowed types:', 'docmanager'); ?> {{{ allowedFiles }}}
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="docmanager-form-row">
                        <button type="submit" class="docmanager-upload-submit">
                            <?php _e('Upload Document', 'docmanager'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}