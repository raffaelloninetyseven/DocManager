<?php
if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Manage_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'docmanager_manage';
    }
    
    public function get_title() {
        return __('Document Manager', 'docmanager');
    }
    
    public function get_icon() {
        return 'eicon-edit';
    }
    
    public function get_categories() {
        return array('docmanager');
    }
    
    private function get_language_presets() {
        return array(
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
        );
    }
    
    protected function register_controls() {
        // Language Preset Section
        $this->start_controls_section(
            'language_preset_section',
            array(
                'label' => __('Language Preset', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'language_preset',
            array(
                'label' => __('Select Language', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'it',
                'options' => array(
                    'it' => __('Italian', 'docmanager'),
                    'en' => __('English', 'docmanager'),
                    'custom' => __('Custom Labels', 'docmanager'),
                ),
                'description' => __('Choose a language preset or use custom labels', 'docmanager'),
            )
        );
        
        $this->add_control(
            'apply_language_preset',
            array(
                'label' => __('Apply Language Preset', 'docmanager'),
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => '<button type="button" onclick="docmanagerApplyLanguagePreset(jQuery(this).closest(\'.elementor-panel\').find(\'[data-setting=language_preset]\').val(), \'docmanager_manage\', elementor.getPanelView().getCurrentPageView())" style="background: #0073aa; color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; font-size: 13px; min-width: 100px;">Apply Preset</button>',
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'condition' => array(
                    'language_preset!' => 'custom'
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Content Settings', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'widget_title',
            array(
                'label' => __('Widget Title', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('My Documents', 'docmanager'),
            )
        );
        
        $this->add_control(
            'show_search',
            array(
                'label' => __('Show Search', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_filters',
            array(
                'label' => __('Show Filters', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
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
            'show_upload_form',
            array(
                'label' => __('Show Upload Form', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'no',
                'description' => __('Show quick upload form above the document list', 'docmanager'),
            )
        );
        
        $this->add_control(
            'allowed_actions',
            array(
                'label' => __('Allowed Actions', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'default' => array('edit', 'delete', 'download'),
                'options' => array(
                    'edit' => __('Edit', 'docmanager'),
                    'delete' => __('Delete', 'docmanager'),
                    'download' => __('Download', 'docmanager'),
                    'preview' => __('Preview', 'docmanager'),
                    'share' => __('Share', 'docmanager'),
                ),
            )
        );
        
        $this->add_control(
            'show_file_info',
            array(
                'label' => __('Show File Info', 'docmanager'),
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
        
        $this->end_controls_section();
        
        // Custom Labels Section
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
                'default' => 'Cerca i tuoi documenti...',
                'condition' => array('show_search' => 'yes'),
            )
        );
        
        $this->add_control(
            'label_search_button',
            array(
                'label' => __('Search Button', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Cerca',
                'condition' => array('show_search' => 'yes'),
            )
        );
        
        $this->add_control(
            'label_edit_button',
            array(
                'label' => __('Edit Button', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Modifica',
            )
        );
        
        $this->add_control(
            'label_delete_button',
            array(
                'label' => __('Delete Button', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Elimina',
            )
        );
        
        $this->add_control(
            'label_download_button',
            array(
                'label' => __('Download Button', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Scarica',
            )
        );
        
        $this->add_control(
            'label_preview_button',
            array(
                'label' => __('Preview Button', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Anteprima',
            )
        );
        
        $this->add_control(
            'label_share_button',
            array(
                'label' => __('Share Button', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Condividi',
            )
        );
        
        $this->add_control(
            'label_save_button',
            array(
                'label' => __('Save Button', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Salva Modifiche',
            )
        );
        
        $this->add_control(
            'label_cancel_button',
            array(
                'label' => __('Cancel Button', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Annulla',
            )
        );
        
        $this->add_control(
            'label_no_documents',
            array(
                'label' => __('No Documents Message', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Non hai ancora caricato documenti.',
            )
        );
        
        $this->add_control(
            'label_login_required',
            array(
                'label' => __('Login Required Message', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Effettua il login per gestire i documenti.',
            )
        );
        
        $this->add_control(
            'label_confirm_delete',
            array(
                'label' => __('Confirm Delete Message', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Sei sicuro di voler eliminare questo documento?',
            )
        );
        
        $this->add_control(
            'label_document_updated',
            array(
                'label' => __('Document Updated Message', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Documento aggiornato con successo!',
            )
        );
        
        $this->add_control(
            'label_document_deleted',
            array(
                'label' => __('Document Deleted Message', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Documento eliminato con successo!',
            )
        );
        
        // Form Labels
        $this->add_control(
            'form_labels_divider',
            array(
                'label' => __('Form Labels', 'docmanager'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );
        
        $this->add_control(
            'label_title_field',
            array(
                'label' => __('Title Field Label', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Titolo Documento',
            )
        );
        
        $this->add_control(
            'label_description_field',
            array(
                'label' => __('Description Field Label', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Descrizione',
            )
        );
        
        $this->add_control(
            'label_category_field',
            array(
                'label' => __('Category Field Label', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Categoria',
            )
        );
        
        $this->add_control(
            'label_tags_field',
            array(
                'label' => __('Tags Field Label', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Tag',
            )
        );
        
        $this->end_controls_section();
        
        // Style sections
        $this->register_style_controls();
    }
    
    private function register_style_controls() {
        // General Style
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
                    '{{WRAPPER}} .docmanager-manage-title' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .docmanager-manage-title',
            )
        );
        
        $this->add_control(
            'document_background',
            array(
                'label' => __('Document Background', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-manage-item' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name' => 'document_border',
                'selector' => '{{WRAPPER}} .docmanager-manage-item',
            )
        );
        
        $this->add_control(
            'document_border_radius',
            array(
                'label' => __('Border Radius', 'docmanager'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-manage-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
            'edit_button_color',
            array(
                'label' => __('Edit Button Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-btn-edit' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'delete_button_color',
            array(
                'label' => __('Delete Button Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#d63638',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-btn-delete' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'download_button_color',
            array(
                'label' => __('Download Button Color', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#00a32a',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-btn-download' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Apply language preset if selected
        if (!empty($settings['language_preset']) && $settings['language_preset'] !== 'custom') {
            $presets = $this->get_language_presets();
            if (isset($presets[$settings['language_preset']])) {
                $preset = $presets[$settings['language_preset']];
                foreach ($preset as $key => $value) {
                    if (empty($settings[$key])) {
                        $settings[$key] = $value;
                    }
                }
            }
        }
        
        if (!is_user_logged_in()) {
            echo '<div class="docmanager-login-required">';
            echo '<p>' . esc_html($settings['label_login_required']) . '</p>';
            echo '</div>';
            return;
        }
        
        wp_enqueue_script('docmanager-frontend');
        wp_enqueue_style('docmanager-frontend');
        
        wp_localize_script('docmanager-frontend', 'docmanager_manage_widget', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('docmanager_manage_nonce'),
            'labels' => array(
                'confirm_delete' => $settings['label_confirm_delete'],
                'document_updated' => $settings['label_document_updated'],
                'document_deleted' => $settings['label_document_deleted'],
            )
        ));
        
        $db = new DocManager_DB();
        $user_id = get_current_user_id();
        
        $documents = $db->get_user_documents($user_id, array());
        $user_documents = array_filter($documents, function($doc) use ($user_id) {
            return $doc->uploaded_by == $user_id;
        });
        
        ?>
        <div class="docmanager-manage-widget elementor-widget-docmanager-manage">
            <?php if (!empty($settings['widget_title'])): ?>
                <h3 class="docmanager-manage-title"><?php echo esc_html($settings['widget_title']); ?></h3>
            <?php endif; ?>
            
            <?php if ($settings['show_search'] === 'yes'): ?>
            <div class="docmanager-manage-search">
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
            <?php endif; ?>
            
            <?php if ($settings['show_upload_form'] === 'yes'): ?>
            <div class="docmanager-quick-upload">
                <h4><?php _e('Quick Upload', 'docmanager'); ?></h4>
                <form class="docmanager-upload-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('docmanager_upload_nonce', 'upload_nonce'); ?>
                    <div class="docmanager-upload-row">
                        <input type="text" name="doc_title" placeholder="<?php echo esc_attr($settings['label_title_field']); ?>" required>
                        <input type="file" name="doc_file" required>
                        <button type="submit" class="docmanager-btn-upload"><?php _e('Upload', 'docmanager'); ?></button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="docmanager-manage-list">
                <?php if (empty($user_documents)): ?>
                    <div class="docmanager-no-documents">
                        <p><?php echo esc_html($settings['label_no_documents']); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($user_documents as $doc): ?>
                        <div class="docmanager-manage-item" data-doc-id="<?php echo esc_attr($doc->id); ?>">
                            <div class="docmanager-manage-header">
                                <h4 class="docmanager-manage-doc-title"><?php echo esc_html($doc->title); ?></h4>
                                <div class="docmanager-manage-actions">
                                    <?php if (in_array('edit', $settings['allowed_actions'])): ?>
                                        <button class="docmanager-btn docmanager-btn-edit" data-action="edit">
                                            <?php echo esc_html($settings['label_edit_button']); ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array('download', $settings['allowed_actions'])): ?>
                                        <a href="<?php echo esc_url($this->get_download_url($doc->id)); ?>" 
                                           class="docmanager-btn docmanager-btn-download" 
                                           target="_blank">
                                            <?php echo esc_html($settings['label_download_button']); ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array('preview', $settings['allowed_actions']) && $this->can_preview($doc->file_type)): ?>
                                        <a href="<?php echo esc_url($doc->file_path); ?>" 
                                           class="docmanager-btn docmanager-btn-preview" 
                                           target="_blank">
                                            <?php echo esc_html($settings['label_preview_button']); ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array('delete', $settings['allowed_actions'])): ?>
                                        <button class="docmanager-btn docmanager-btn-delete" data-action="delete">
                                            <?php echo esc_html($settings['label_delete_button']); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="docmanager-manage-content">
                                <?php if ($settings['show_description'] === 'yes' && !empty($doc->description)): ?>
                                    <div class="docmanager-manage-description">
                                        <?php echo esc_html($doc->description); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($settings['show_file_info'] === 'yes'): ?>
                                    <div class="docmanager-manage-meta">
                                        <span class="docmanager-meta-size"><?php echo size_format($doc->file_size); ?></span>
                                        <span class="docmanager-meta-date"><?php echo date_i18n(get_option('date_format'), strtotime($doc->upload_date)); ?></span>
                                        <?php if ($settings['show_category'] === 'yes' && !empty($doc->category)): ?>
                                            <span class="docmanager-meta-category"><?php echo esc_html($doc->category); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="docmanager-edit-form" style="display: none;">
                                <form class="docmanager-update-form">
                                    <?php wp_nonce_field('docmanager_update_nonce', 'update_nonce'); ?>
                                    <input type="hidden" name="document_id" value="<?php echo esc_attr($doc->id); ?>">
                                    
                                    <div class="docmanager-form-row">
                                        <label><?php echo esc_html($settings['label_title_field']); ?></label>
                                        <input type="text" name="doc_title" value="<?php echo esc_attr($doc->title); ?>" required>
                                    </div>
                                    
                                    <div class="docmanager-form-row">
                                        <label><?php echo esc_html($settings['label_description_field']); ?></label>
                                        <textarea name="doc_description" rows="3"><?php echo esc_textarea($doc->description); ?></textarea>
                                    </div>
                                    
                                    <div class="docmanager-form-row">
                                        <label><?php echo esc_html($settings['label_category_field']); ?></label>
                                        <input type="text" name="doc_category" value="<?php echo esc_attr($doc->category); ?>">
                                    </div>
                                    
                                    <div class="docmanager-form-row">
                                        <label><?php echo esc_html($settings['label_tags_field']); ?></label>
                                        <input type="text" name="doc_tags" value="<?php echo esc_attr($doc->tags); ?>">
                                    </div>
                                    
                                    <div class="docmanager-form-actions">
                                        <button type="submit" class="docmanager-btn docmanager-btn-save">
                                            <?php echo esc_html($settings['label_save_button']); ?>
                                        </button>
                                        <button type="button" class="docmanager-btn docmanager-btn-cancel" data-action="cancel">
                                            <?php echo esc_html($settings['label_cancel_button']); ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="docmanager-manage-messages"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Edit button click
            $('.docmanager-btn-edit').on('click', function() {
                const item = $(this).closest('.docmanager-manage-item');
                const editForm = item.find('.docmanager-edit-form');
                const content = item.find('.docmanager-manage-content');
                
                content.hide();
                editForm.show();
            });
            
            // Cancel button click
            $('.docmanager-btn-cancel').on('click', function() {
                const item = $(this).closest('.docmanager-manage-item');
                const editForm = item.find('.docmanager-edit-form');
                const content = item.find('.docmanager-manage-content');
                
                editForm.hide();
                content.show();
            });
            
            // Delete button click
            $('.docmanager-btn-delete').on('click', function() {
                if (!confirm(docmanager_manage_widget.labels.confirm_delete)) {
                    return;
                }
                
                const item = $(this).closest('.docmanager-manage-item');
                const docId = item.data('doc-id');
                
                $.ajax({
                    url: docmanager_manage_widget.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'docmanager_delete_document',
                        document_id: docId,
                        nonce: docmanager_manage_widget.nonce
                    },
                    success: function(response) {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (data.success) {
                            item.fadeOut(300, function() {
                                $(this).remove();
                            });
                            showMessage('success', docmanager_manage_widget.labels.document_deleted);
                        } else {
                            showMessage('error', data.message || 'Errore durante l\'eliminazione');
                        }
                    },
                    error: function() {
                        showMessage('error', 'Errore di connessione');
                    }
                });
            });
            
            // Update form submission
            $('.docmanager-update-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const formData = new FormData(this);
                formData.append('action', 'docmanager_update_document');
                
                $.ajax({
                    url: docmanager_manage_widget.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (data.success) {
                            const item = form.closest('.docmanager-manage-item');
                            const editForm = item.find('.docmanager-edit-form');
                            const content = item.find('.docmanager-manage-content');
                            
                            // Update title
                            item.find('.docmanager-manage-doc-title').text(form.find('input[name="doc_title"]').val());
                            
                            // Update description if shown
                            const newDescription = form.find('textarea[name="doc_description"]').val();
                            if (newDescription) {
                                item.find('.docmanager-manage-description').text(newDescription);
                            }
                            
                            // Update category if shown
                            const newCategory = form.find('input[name="doc_category"]').val();
                            if (newCategory) {
                                item.find('.docmanager-meta-category').text(newCategory);
                            }
                            
                            editForm.hide();
                            content.show();
                            showMessage('success', docmanager_manage_widget.labels.document_updated);
                        } else {
                            showMessage('error', data.message || 'Errore durante l\'aggiornamento');
                        }
                    },
                    error: function() {
                        showMessage('error', 'Errore di connessione');
                    }
                });
            });
            
            // Quick upload form submission
            $('.docmanager-upload-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const formData = new FormData(this);
                formData.append('action', 'docmanager_upload_frontend');
                
                $.ajax({
                    url: docmanager_manage_widget.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (data.success) {
                            showMessage('success', 'Documento caricato con successo!');
                            form[0].reset();
                            // Refresh page to show new document
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showMessage('error', data.message || 'Errore durante il caricamento');
                        }
                    },
                    error: function() {
                        showMessage('error', 'Errore di connessione');
                    }
                });
            });
            
            // Search functionality
            $('.docmanager-search-form').on('submit', function(e) {
                e.preventDefault();
                
                const searchTerm = $(this).find('input[name="search_term"]').val().toLowerCase();
                const items = $('.docmanager-manage-item');
                
                items.each(function() {
                    const item = $(this);
                    const title = item.find('.docmanager-manage-doc-title').text().toLowerCase();
                    const description = item.find('.docmanager-manage-description').text().toLowerCase();
                    
                    if (title.includes(searchTerm) || description.includes(searchTerm)) {
                        item.show();
                    } else {
                        item.hide();
                    }
                });
            });
            
            function showMessage(type, message) {
                const messageDiv = $('<div class="docmanager-message ' + type + '">' + message + '</div>');
                $('.docmanager-manage-messages').html(messageDiv);
                
                setTimeout(function() {
                    messageDiv.fadeOut();
                }, 5000);
            }
        });
        </script>
        
        <style>
        .docmanager-manage-item {
            margin-bottom: 20px;
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            transition: box-shadow 0.2s;
        }
        
        .docmanager-manage-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .docmanager-manage-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .docmanager-manage-doc-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            color: #2c3e50;
        }
        
        .docmanager-manage-actions {
            display: flex;
            gap: 10px;
        }
        
        .docmanager-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            color: white;
            transition: opacity 0.2s;
        }
        
        .docmanager-btn:hover {
            opacity: 0.8;
        }
        
        .docmanager-btn-edit {
            background: #0073aa;
        }
        
        .docmanager-btn-delete {
            background: #d63638;
        }
        
        .docmanager-btn-download {
            background: #00a32a;
        }
        
        .docmanager-btn-preview {
            background: #666;
        }
        
        .docmanager-btn-save {
            background: #00a32a;
        }
        
        .docmanager-btn-cancel {
            background: #666;
        }
        
        .docmanager-manage-description {
            color: #666;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .docmanager-manage-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #666;
        }
        
        .docmanager-meta-category {
            background: #e7f3ff;
            color: #0073aa;
            padding: 2px 8px;
            border-radius: 12px;
        }
        
        .docmanager-edit-form {
            margin-top: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .docmanager-form-row {
            margin-bottom: 15px;
        }
        
        .docmanager-form-row label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .docmanager-form-row input,
        .docmanager-form-row textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .docmanager-form-actions {
            display: flex;
            gap: 10px;
        }
        
        .docmanager-quick-upload {
            margin-bottom: 20px;
            padding: 15px;
            background: #f0f8ff;
            border-radius: 4px;
        }
        
        .docmanager-quick-upload h4 {
            margin: 0 0 15px 0;
            color: #2c3e50;
        }
        
        .docmanager-upload-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .docmanager-upload-row input[type="text"] {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .docmanager-upload-row input[type="file"] {
            flex: 1;
        }
        
        .docmanager-btn-upload {
            background: #0073aa;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .docmanager-btn-upload:hover {
            background: #005a87;
        }
        
        .docmanager-manage-search {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .docmanager-search-form {
            display: flex;
            gap: 10px;
        }
        
        .docmanager-search-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .docmanager-search-btn {
            background: #0073aa;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .docmanager-search-btn:hover {
            background: #005a87;
        }
        
        .docmanager-message {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .docmanager-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .docmanager-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .docmanager-no-documents {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .docmanager-manage-header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .docmanager-manage-actions {
                flex-wrap: wrap;
            }
            
            .docmanager-upload-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .docmanager-upload-row input[type="text"],
            .docmanager-upload-row input[type="file"] {
                width: 100%;
            }
            
            .docmanager-form-actions {
                flex-direction: column;
            }
        }
        </style>
        <?php
    }
    
    private function get_download_url($document_id) {
        return add_query_arg(array(
            'docmanager_download' => $document_id,
            'nonce' => wp_create_nonce('docmanager_download_' . $document_id)
        ), home_url());
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
}