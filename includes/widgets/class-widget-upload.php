<?php
/**
 * Widget Elementor per il caricamento documenti
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Widget_Upload extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'docmanager_upload';
    }
    
    public function get_title() {
        return 'DocManager - Carica Referto';
    }
    
    public function get_icon() {
        return 'eicon-upload';
    }
    
    public function get_categories() {
        return ['general'];
    }
    
    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Impostazioni',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'title',
            [
                'label' => 'Titolo',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Carica Nuovo Referto',
            ]
        );
        
        $this->add_control(
            'show_user_select',
            [
                'label' => 'Mostra Selezione Utente',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sì',
                'label_off' => 'No',
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );
        
        $this->add_control(
            'allowed_roles',
            [
                'label' => 'Ruoli Autorizzati',
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_user_roles(),
                'default' => ['administrator'],
            ]
        );
        
        $this->end_controls_section();
        
        $this->start_controls_section(
            'style_section',
            [
                'label' => 'Stile',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'form_background',
            [
                'label' => 'Sfondo Form',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-upload-form' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'button_color',
            [
                'label' => 'Colore Pulsante',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-upload-btn' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (!is_user_logged_in()) {
            echo '<p>Devi essere loggato per caricare documenti.</p>';
            return;
        }
        
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $allowed_roles = $settings['allowed_roles'];
        
        $can_upload = false;
        foreach ($user_roles as $role) {
            if (in_array($role, $allowed_roles)) {
                $can_upload = true;
                break;
            }
        }
        
        if (!$can_upload) {
            echo '<p>Non hai i permessi per caricare documenti.</p>';
            return;
        }
        
        ?>
        <div class="docmanager-upload-widget">
            <h3><?php echo esc_html($settings['title']); ?></h3>
            
            <form class="docmanager-upload-form" id="docmanager-upload-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="doc-title">Titolo Documento *</label>
                    <input type="text" id="doc-title" name="title" required>
                </div>
                
                <?php if ($settings['show_user_select'] === 'yes' && current_user_can('manage_options')): ?>
                <div class="form-group">
                    <label for="doc-user">Assegna a Utente *</label>
                    <?php 
                    wp_dropdown_users(array(
                        'name' => 'user_id',
                        'id' => 'doc-user',
                        'show_option_none' => 'Seleziona utente...',
                        'option_none_value' => ''
                    )); 
                    ?>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="doc-file">File *</label>
                    <input type="file" id="doc-file" name="file" accept=".<?php echo implode(',.', DocManager::get_allowed_file_types()); ?>" required>
                    <small>Tipi consentiti: <?php echo implode(', ', DocManager::get_allowed_file_types()); ?></small>
                    <small>Dimensione massima: <?php echo DocManager::format_file_size(DocManager::get_max_file_size()); ?></small>
                </div>
                
                <div class="form-group">
                    <label for="doc-notes">Note</label>
                    <textarea id="doc-notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="docmanager-upload-btn">Carica Documento</button>
                </div>
                
                <div class="docmanager-messages"></div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#docmanager-upload-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'docmanager_upload');
                formData.append('nonce', docmanager_ajax.nonce);
                
                <?php if ($settings['show_user_select'] !== 'yes' || !current_user_can('manage_options')): ?>
                formData.append('user_id', '<?php echo get_current_user_id(); ?>');
                <?php endif; ?>
                
                var $button = $(this).find('button[type="submit"]');
                var originalText = $button.text();
                $button.text('Caricamento...').prop('disabled', true);
                
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('.docmanager-messages').html('<div class="success">' + response.data.message + '</div>');
                            $('#docmanager-upload-form')[0].reset();
                        } else {
                            $('.docmanager-messages').html('<div class="error">' + response.data + '</div>');
                        }
                    },
                    error: function() {
                        $('.docmanager-messages').html('<div class="error">Errore durante il caricamento</div>');
                    },
                    complete: function() {
                        $button.text(originalText).prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    private function get_user_roles() {
        global $wp_roles;
        $roles = array();
        
        foreach ($wp_roles->roles as $role_key => $role) {
            $roles[$role_key] = $role['name'];
        }
        
        return $roles;
    }
}