<?php
/**
 * Widget Elementor per caricare documenti
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Widget_Upload_Documents extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'docmanager-upload-documents';
    }
    
    public function get_title() {
        return __('Carica Documenti', 'docmanager');
    }
    
    public function get_icon() {
        return 'fa fa-upload';
    }
    
    public function get_categories() {
        return array('docmanager');
    }
    
    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Contenuto', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'form_title',
            array(
                'label' => __('Titolo Form', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Carica Documento', 'docmanager'),
                'placeholder' => __('Inserisci il titolo del form', 'docmanager'),
            )
        );
        
        $this->add_control(
            'show_user_select',
            array(
                'label' => __('Mostra Selezione Utente', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sì', 'docmanager'),
                'label_off' => __('No', 'docmanager'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => __('Solo gli amministratori possono vedere questa opzione', 'docmanager'),
            )
        );
        
        $this->add_control(
            'show_categories',
            array(
                'label' => __('Mostra Categorie', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sì', 'docmanager'),
                'label_off' => __('No', 'docmanager'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_tags',
            array(
                'label' => __('Mostra Tag', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sì', 'docmanager'),
                'label_off' => __('No', 'docmanager'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_notes',
            array(
                'label' => __('Mostra Campo Note', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sì', 'docmanager'),
                'label_off' => __('No', 'docmanager'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_expiry',
            array(
                'label' => __('Mostra Data Scadenza', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sì', 'docmanager'),
                'label_off' => __('No', 'docmanager'),
                'return_value' => 'yes',
                'default' => 'no',
            )
        );
        
        $this->add_control(
            'allowed_roles',
            array(
                'label' => __('Ruoli Autorizzati', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->getUserRoles(),
                'default' => array('administrator'),
                'description' => __('Seleziona i ruoli che possono caricare documenti', 'docmanager'),
            )
        );
        
        $this->add_control(
            'success_message',
            array(
                'label' => __('Messaggio Successo', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Documento caricato con successo!', 'docmanager'),
                'placeholder' => __('Messaggio mostrato dopo il caricamento', 'docmanager'),
            )
        );
        
        $this->end_controls_section();
        
        // Sezione Style
        $this->start_controls_section(
            'style_section',
            array(
                'label' => __('Stile', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'form_bg_color',
            array(
                'label' => __('Colore Sfondo Form', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-upload-form' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'button_color',
            array(
                'label' => __('Colore Pulsante', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-upload-btn' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    private function getUserRoles() {
        $roles = wp_roles()->roles;
        $role_options = array();
        
        foreach ($roles as $role_key => $role) {
            $role_options[$role_key] = $role['name'];
        }
        
        return $role_options;
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Verificare se l'utente è loggato
        if (!is_user_logged_in()) {
            echo '<div class="docmanager-notice">' . __('Devi effettuare il login per caricare documenti.', 'docmanager') . '</div>';
            return;
        }
        
        // Verificare i permessi
        $user = wp_get_current_user();
        $allowed_roles = $settings['allowed_roles'];
        
        if (!current_user_can('manage_options') && !array_intersect($user->roles, $allowed_roles)) {
            echo '<div class="docmanager-notice">' . __('Non hai i permessi per caricare documenti.', 'docmanager') . '</div>';
            return;
        }
        
        // Gestire l'upload
        if (isset($_POST['docmanager_upload_submit'])) {
            $this->handleUpload();
        }
        
        $this->renderUploadForm($settings);
    }
    
    private function handleUpload() {
        // Verificare nonce
        if (!isset($_POST['docmanager_upload_nonce']) || !wp_verify_nonce($_POST['docmanager_upload_nonce'], 'docmanager_upload')) {
            echo '<div class="docmanager-error">' . __('Errore di sicurezza', 'docmanager') . '</div>';
            return;
        }
        
        // Verificare che sia stato caricato un file
        if (!isset($_FILES['docmanager_file']) || $_FILES['docmanager_file']['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="docmanager-error">' . __('Nessun file selezionato o errore durante il caricamento', 'docmanager') . '</div>';
            return;
        }
        
        // Creare il post
        $post_data = array(
            'post_title' => sanitize_text_field($_POST['document_title']),
            'post_type' => 'referto',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            echo '<div class="docmanager-error">' . __('Errore durante la creazione del documento', 'docmanager') . '</div>';
            return;
        }
        
        // Caricare il file
        $file_handler = new DocManager_FileHandler();
        $file_result = $file_handler->uploadFile($_FILES['docmanager_file'], $post_id);
        
        if (!$file_result) {
            wp_delete_post($post_id, true);
            echo '<div class="docmanager-error">' . __('Errore durante il caricamento del file', 'docmanager') . '</div>';
            return;
        }
        
        // Salvare i metadati
        update_post_meta($post_id, '_docmanager_file_id', $file_result['file_id']);
        update_post_meta($post_id, '_docmanager_file_name', $file_result['file_name']);
        update_post_meta($post_id, '_docmanager_file_size', $file_result['file_size']);
        update_post_meta($post_id, '_docmanager_file_type', $file_result['file_type']);
        update_post_meta($post_id, '_docmanager_upload_date', current_time('mysql'));
        
        // Assegnare l'utente
        $assigned_user = current_user_can('manage_options') && isset($_POST['assigned_user']) ? intval($_POST['assigned_user']) : get_current_user_id();
        update_post_meta($post_id, '_docmanager_assigned_user', $assigned_user);
        
        // Salvare note
        if (isset($_POST['document_notes'])) {
            update_post_meta($post_id, '_docmanager_notes', sanitize_textarea_field($_POST['document_notes']));
        }
        
        // Salvare data scadenza
        if (isset($_POST['expiry_date']) && !empty($_POST['expiry_date'])) {
            update_post_meta($post_id, '_docmanager_expiry_date', sanitize_text_field($_POST['expiry_date']));
        }
        
        // Assegnare categorie
        if (isset($_POST['doc_categories']) && is_array($_POST['doc_categories'])) {
            wp_set_post_terms($post_id, array_map('intval', $_POST['doc_categories']), 'doc_category');
        }
        
        // Assegnare tag
        if (isset($_POST['doc_tags']) && !empty($_POST['doc_tags'])) {
            wp_set_post_terms($post_id, sanitize_text_field($_POST['doc_tags']), 'doc_tag');
        }
        
        echo '<div class="docmanager-success">' . esc_html($this->get_settings_for_display()['success_message']) . '</div>';
        
        // Log dell'azione
        $security = new DocManager_Security();
        $security->logAccess($post_id, 'upload');
    }
    
    private function renderUploadForm($settings) {
        $categories = get_terms(array(
            'taxonomy' => 'doc_category',
            'hide_empty' => false,
        ));
        
        $users = get_users(array('orderby' => 'display_name'));
        
        ?>
        <div class="docmanager-upload-form">
            <?php if (!empty($settings['form_title'])): ?>
                <h3><?php echo esc_html($settings['form_title']); ?></h3>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('docmanager_upload', 'docmanager_upload_nonce'); ?>
                
                <div class="docmanager-field">
                    <label for="document_title"><?php _e('Titolo Documento', 'docmanager'); ?> *</label>
                    <input type="text" id="document_title" name="document_title" required />
                </div>
                
                <div class="docmanager-field">
                    <label for="docmanager_file"><?php _e('File', 'docmanager'); ?> *</label>
                    <input type="file" id="docmanager_file" name="docmanager_file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" />
                    <small class="docmanager-help"><?php _e('Formati supportati: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF', 'docmanager'); ?></small>
                </div>
                
                <?php if ($settings['show_user_select'] === 'yes' && current_user_can('manage_options')): ?>
                    <div class="docmanager-field">
                        <label for="assigned_user"><?php _e('Assegna a Utente', 'docmanager'); ?></label>
                        <select id="assigned_user" name="assigned_user">
                            <option value=""><?php _e('Seleziona utente...', 'docmanager'); ?></option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user->ID; ?>">
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <?php if ($settings['show_categories'] === 'yes' && !empty($categories)): ?>
                    <div class="docmanager-field">
                        <label><?php _e('Categorie', 'docmanager'); ?></label>
                        <div class="docmanager-checkbox-group">
                            <?php foreach ($categories as $category): ?>
                                <label>
                                    <input type="checkbox" name="doc_categories[]" value="<?php echo $category->term_id; ?>" />
									<?php echo esc_html($category->name); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($settings['show_tags'] === 'yes'): ?>
                    <div class="docmanager-field">
                        <label for="doc_tags"><?php _e('Tag', 'docmanager'); ?></label>
                        <input type="text" id="doc_tags" name="doc_tags" placeholder="<?php _e('Separare i tag con virgole', 'docmanager'); ?>" />
                    </div>
                <?php endif; ?>
                
                <?php if ($settings['show_notes'] === 'yes'): ?>
                    <div class="docmanager-field">
                        <label for="document_notes"><?php _e('Note', 'docmanager'); ?></label>
                        <textarea id="document_notes" name="document_notes" rows="3"></textarea>
                    </div>
                <?php endif; ?>
                
                <?php if ($settings['show_expiry'] === 'yes'): ?>
                    <div class="docmanager-field">
                        <label for="expiry_date"><?php _e('Data Scadenza', 'docmanager'); ?></label>
                        <input type="date" id="expiry_date" name="expiry_date" />
                    </div>
                <?php endif; ?>
                
                <div class="docmanager-field">
                    <button type="submit" name="docmanager_upload_submit" class="docmanager-upload-btn">
                        <?php _e('Carica Documento', 'docmanager'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
}