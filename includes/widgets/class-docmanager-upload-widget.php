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
    
    protected function register_controls() {
        // Upload Settings Section
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
            )
        );
        
        $this->add_control(
            'upload_description',
            array(
                'label' => __('Form Description', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => __('Enter form description', 'docmanager'),
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
                'min' => 1,
                'max' => 100,
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
            'show_user_selector',
            array(
                'label' => __('Show User Selector', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'no',
                'description' => __('Allow users to select a specific user when uploading', 'docmanager'),
            )
        );
        
        $this->add_control(
            'redirect_after_upload',
            array(
                'label' => __('Redirect After Upload', 'docmanager'),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __('https://your-site.com/thank-you', 'docmanager'),
            )
        );
        
        $this->end_controls_section();
        
        // Visibility Section
        $this->start_controls_section(
            'visibility_section',
            array(
                'label' => __('Document Visibility', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'document_visibility',
            array(
                'label' => __('Who can view this document?', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'uploader_only',
                'options' => array(
                    'everyone' => __('Everyone (Public)', 'docmanager'),
                    'logged_users' => __('All Logged Users', 'docmanager'),
                    'uploader_only' => __('Uploader Only', 'docmanager'),
                    'specific_user' => __('Specific User', 'docmanager'),
                    'specific_role' => __('Specific Role', 'docmanager'),
                ),
            )
        );
        
        $this->add_control(
            'specific_user_id',
            array(
                'label' => __('Specific User ID', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'condition' => array('document_visibility' => 'specific_user'),
            )
        );
        
        $this->add_control(
            'specific_role',
            array(
                'label' => __('Specific Role', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_user_roles(),
                'condition' => array('document_visibility' => 'specific_role'),
            )
        );
        
        $this->add_control(
            'show_visibility_field',
            array(
                'label' => __('Show Visibility Field to Users', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'no',
            )
        );
        
        $this->end_controls_section();
        
        // Labels Section
        $this->start_controls_section(
            'labels_section',
            array(
                'label' => __('Field Labels', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'label_title_field',
            array(
                'label' => __('Title Field Label', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Document Title', 'docmanager'),
                'condition' => array('show_title_field' => 'yes'),
            )
        );
        
        $this->add_control(
            'label_description_field',
            array(
                'label' => __('Description Field Label', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Description', 'docmanager'),
                'condition' => array('show_description_field' => 'yes'),
            )
        );
        
        $this->add_control(
            'label_category_field',
            array(
                'label' => __('Category Field Label', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Category', 'docmanager'),
                'condition' => array('show_category_field' => 'yes'),
            )
        );
        
        $this->add_control(
            'label_tags_field',
            array(
                'label' => __('Tags Field Label', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Tags', 'docmanager'),
                'condition' => array('show_tags_field' => 'yes'),
            )
        );
        
        $this->add_control(
            'label_file_field',
            array(
                'label' => __('File Field Label', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Select File', 'docmanager'),
            )
        );
        
        $this->add_control(
            'label_upload_button',
            array(
                'label' => __('Upload Button Text', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Upload Document', 'docmanager'),
            )
        );
        
        $this->add_control(
            'label_drop_zone',
            array(
                'label' => __('Drop Zone Text', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Drag and drop file here or click to browse', 'docmanager'),
            )
        );
        
        $this->add_control(
            'label_file_info',
            array(
                'label' => __('File Info Text', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Max size: %dMB. Allowed types: %s', 'docmanager'),
                'description' => __('Use %d for size and %s for types', 'docmanager'),
            )
        );
        
        $this->add_control(
            'label_login_required',
            array(
                'label' => __('Login Required Message', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Please login to upload documents.', 'docmanager'),
            )
        );
        
        $this->add_control(
            'label_upload_success',
            array(
                'label' => __('Upload Success Message', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Document uploaded successfully!', 'docmanager'),
            )
        );
        
        $this->add_control(
            'label_upload_error',
            array(
                'label' => __('Upload Error Message', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Error uploading document. Please try again.', 'docmanager'),
            )
        );
        
        // Placeholders
        $this->add_control(
            'placeholders_divider',
            array(
                'label' => __('Field Placeholders', 'docmanager'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );
        
        $this->add_control(
            'placeholder_title',
            array(
                'label' => __('Title Placeholder', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Enter document title', 'docmanager'),
                'condition' => array('show_title_field' => 'yes'),
            )
        );
        
        $this->add_control(
            'placeholder_description',
            array(
                'label' => __('Description Placeholder', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Enter description', 'docmanager'),
                'condition' => array('show_description_field' => 'yes'),
            )
        );
        
        $this->add_control(
            'placeholder_category',
            array(
                'label' => __('Category Placeholder', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('e.g. Contracts, Reports, etc.', 'docmanager'),
                'condition' => array('show_category_field' => 'yes'),
            )
        );
        
        $this->add_control(
            'placeholder_tags',
            array(
                'label' => __('Tags Placeholder', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Separate tags with commas', 'docmanager'),
                'condition' => array('show_tags_field' => 'yes'),
            )
        );
        
        $this->end_controls_section();
        
        // Style sections
        $this->register_style_controls();
    }
    
    private function register_style_controls() {
        // Form Style
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
        
        $this->end_controls_section();
        
        // Button Style
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
        
        $this->end_controls_section();
    }
    
    private function get_user_roles() {
        $roles = wp_roles()->roles;
        $role_options = array();
        
        foreach ($roles as $role_key => $role) {
            $role_options[$role_key] = $role['name'];
        }
        
        return $role_options;
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (!is_user_logged_in()) {
            echo '<div class="docmanager-login-required">';
            echo '<p>' . esc_html($settings['label_login_required']) . '</p>';
            echo '</div>';
            return;
        }
        
        wp_enqueue_script('docmanager-frontend');
        wp_enqueue_style('docmanager-frontend');
        
        wp_localize_script('docmanager-frontend', 'docmanager_upload_widget', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('docmanager_upload_nonce'),
            'max_size' => $settings['max_file_size'] * 1024 * 1024,
            'allowed_types' => explode(',', str_replace(' ', '', $settings['allowed_files'])),
            'redirect_url' => !empty($settings['redirect_after_upload']['url']) ? $settings['redirect_after_upload']['url'] : '',
            'visibility' => $settings['document_visibility'],
            'specific_user' => $settings['specific_user_id'] ?? '',
            'specific_role' => $settings['specific_role'] ?? '',
            'messages' => array(
                'upload_success' => $settings['label_upload_success'],
                'upload_error' => $settings['label_upload_error']
            )
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
                    
                    <input type="hidden" name="document_visibility" value="<?php echo esc_attr($settings['document_visibility']); ?>">
                    <input type="hidden" name="specific_user_id" value="<?php echo esc_attr($settings['specific_user_id']); ?>">
                    <input type="hidden" name="specific_role" value="<?php echo esc_attr($settings['specific_role']); ?>">
                    
                    <?php if ($settings['show_title_field'] === 'yes'): ?>
                    <div class="docmanager-form-row">
                        <label for="doc_title"><?php echo esc_html($settings['label_title_field']); ?> *</label>
                        <input type="text" id="doc_title" name="doc_title" 
                               placeholder="<?php echo esc_attr($settings['placeholder_title']); ?>" required>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_description_field'] === 'yes'): ?>
                    <div class="docmanager-form-row">
                        <label for="doc_description"><?php echo esc_html($settings['label_description_field']); ?></label>
                        <textarea id="doc_description" name="doc_description" rows="3"
                                  placeholder="<?php echo esc_attr($settings['placeholder_description']); ?>"></textarea>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_category_field'] === 'yes'): ?>
                    <div class="docmanager-form-row">
                        <label for="doc_category"><?php echo esc_html($settings['label_category_field']); ?></label>
                        <input type="text" id="doc_category" name="doc_category" 
                               placeholder="<?php echo esc_attr($settings['placeholder_category']); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_tags_field'] === 'yes'): ?>
                    <div class="docmanager-form-row">
                        <label for="doc_tags"><?php echo esc_html($settings['label_tags_field']); ?></label>
                        <input type="text" id="doc_tags" name="doc_tags" 
                               placeholder="<?php echo esc_attr($settings['placeholder_tags']); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_visibility_field'] === 'yes'): ?>
                    <div class="docmanager-form-row">
                        <label for="doc_visibility"><?php _e('Document Visibility', 'docmanager'); ?></label>
                        <select id="doc_visibility" name="doc_visibility">
                            <option value="uploader_only"><?php _e('Only Me', 'docmanager'); ?></option>
                            <option value="logged_users"><?php _e('All Logged Users', 'docmanager'); ?></option>
                            <option value="everyone"><?php _e('Everyone', 'docmanager'); ?></option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_user_selector'] === 'yes'): ?>
                    <div class="docmanager-form-row">
                        <label for="doc_specific_user"><?php _e('Assign to User', 'docmanager'); ?></label>
                        <select id="doc_specific_user" name="doc_specific_user">
                            <option value=""><?php _e('Select a user...', 'docmanager'); ?></option>
                            <option value="me"><?php _e('Only Me', 'docmanager'); ?></option>
                            <option value="logged_users"><?php _e('All Logged Users', 'docmanager'); ?></option>
                            <option value="everyone"><?php _e('Everyone', 'docmanager'); ?></option>
                            <?php
                            $users = get_users();
                            foreach ($users as $user) {
                                echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . ' (' . $user->user_login . ')</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="docmanager-form-row">
                        <label for="doc_file"><?php echo esc_html($settings['label_file_field']); ?> *</label>
                        <div class="docmanager-file-upload-area">
                            <input type="file" id="doc_file" name="doc_file" required 
                                   accept=".<?php echo str_replace(',', ',.', $settings['allowed_files']); ?>">
                            <div class="docmanager-drop-zone">
                                <span class="docmanager-drop-icon">📁</span>
                                <p><?php echo esc_html($settings['label_drop_zone']); ?></p>
                                <small>
                                    <?php printf(
                                        $settings['label_file_info'], 
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
                            <?php echo esc_html($settings['label_upload_button']); ?>
                        </button>
                    </div>
                </form>
                
                <div class="docmanager-upload-messages"></div>
            </div>
        </div>
        
        <?php
    }
}