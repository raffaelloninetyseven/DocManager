<?php
/**
 * Widget Elementor per visualizzare i documenti dell'utente
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Widget_View_Documents extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'docmanager-view-documents';
    }
    
    public function get_title() {
        return __('Visualizza Documenti', 'docmanager');
    }
    
    public function get_icon() {
        return 'fa fa-file-text';
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
            'layout',
            array(
                'label' => __('Layout', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'list',
                'options' => array(
                    'list' => __('Lista', 'docmanager'),
                    'grid' => __('Griglia', 'docmanager'),
                    'accordion' => __('Accordion', 'docmanager'),
                ),
            )
        );
        
        $this->add_control(
            'columns',
            array(
                'label' => __('Colonne', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '2',
                'options' => array(
                    '1' => __('1 Colonna', 'docmanager'),
                    '2' => __('2 Colonne', 'docmanager'),
                    '3' => __('3 Colonne', 'docmanager'),
                    '4' => __('4 Colonne', 'docmanager'),
                ),
                'condition' => array(
                    'layout' => 'grid',
                ),
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
            'show_dates',
            array(
                'label' => __('Mostra Date', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sì', 'docmanager'),
                'label_off' => __('No', 'docmanager'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_file_size',
            array(
                'label' => __('Mostra Dimensione File', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sì', 'docmanager'),
                'label_off' => __('No', 'docmanager'),
                'return_value' => 'yes',
                'default' => 'no',
            )
        );
        
        $this->add_control(
            'empty_message',
            array(
                'label' => __('Messaggio Vuoto', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Nessun documento disponibile', 'docmanager'),
                'placeholder' => __('Inserisci il messaggio da mostrare quando non ci sono documenti', 'docmanager'),
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
            'title_color',
            array(
                'label' => __('Colore Titolo', 'docmanager'),
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
                'label' => __('Tipografia Titolo', 'docmanager'),
                'selector' => '{{WRAPPER}} .docmanager-document-title',
            )
        );
        
        $this->add_control(
            'meta_color',
            array(
                'label' => __('Colore Metadati', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-document-meta' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'button_color',
            array(
                'label' => __('Colore Pulsanti', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-btn' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Verificare se l'utente è loggato
        if (!is_user_logged_in()) {
            echo '<div class="docmanager-notice">' . __('Devi effettuare il login per visualizzare i documenti.', 'docmanager') . '</div>';
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Ottenere i documenti dell'utente
        $documents = get_posts(array(
            'post_type' => 'referto',
            'meta_key' => '_docmanager_assigned_user',
            'meta_value' => $user_id,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($documents)) {
            echo '<div class="docmanager-empty">' . esc_html($settings['empty_message']) . '</div>';
            return;
        }
        
        $layout = $settings['layout'];
        $columns = $settings['columns'];
        
        echo '<div class="docmanager-documents docmanager-layout-' . esc_attr($layout) . '">';
        
        if ($layout === 'grid') {
            echo '<div class="docmanager-grid docmanager-columns-' . esc_attr($columns) . '">';
        }
        
        foreach ($documents as $document) {
            $this->renderDocument($document, $settings);
        }
        
        if ($layout === 'grid') {
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    private function renderDocument($document, $settings) {
        $file_handler = new DocManager_FileHandler();
        $file_info = $file_handler->getFileInfo($document->ID);
        $download_url = $file_handler->getDownloadUrl($document->ID);
        $view_url = $file_handler->getViewUrl($document->ID);
        $is_viewable = $file_handler->isViewable($document->ID);
        
        $categories = get_the_terms($document->ID, 'doc_category');
        $upload_date = get_post_meta($document->ID, '_docmanager_upload_date', true);
        $expiry_date = get_post_meta($document->ID, '_docmanager_expiry_date', true);
        $notes = get_post_meta($document->ID, '_docmanager_notes', true);
        
        $layout = $settings['layout'];
        
        if ($layout === 'accordion') {
            echo '<div class="docmanager-accordion-item">';
            echo '<div class="docmanager-accordion-header">';
            echo '<h3 class="docmanager-document-title">' . esc_html($document->post_title) . '</h3>';
            echo '</div>';
            echo '<div class="docmanager-accordion-content">';
        } else {
            echo '<div class="docmanager-document-item">';
        }
        
        if ($layout !== 'accordion') {
            echo '<h3 class="docmanager-document-title">' . esc_html($document->post_title) . '</h3>';
        }
        
        echo '<div class="docmanager-document-meta">';
        
        // Mostrare categorie
        if ($settings['show_categories'] === 'yes' && $categories) {
            echo '<span class="docmanager-categories">';
            echo '<i class="fa fa-folder"></i> ';
            $cat_names = array();
            foreach ($categories as $category) {
                if (is_object($category)) {
                    $cat_names[] = $category->name;
                }
            }
            echo implode(', ', $cat_names);
            echo '</span>';
        }
        
        // Mostrare date
        if ($settings['show_dates'] === 'yes' && $upload_date) {
            echo '<span class="docmanager-date">';
            echo '<i class="fa fa-calendar"></i> ';
            echo date_i18n(get_option('date_format'), strtotime($upload_date));
            echo '</span>';
        }
        
        // Mostrare dimensione file
        if ($settings['show_file_size'] === 'yes' && $file_info) {
            echo '<span class="docmanager-file-size">';
            echo '<i class="fa fa-file"></i> ';
            echo size_format($file_info['size']);
            echo '</span>';
        }
        
        // Mostrare scadenza
        if ($expiry_date) {
            $is_expired = strtotime($expiry_date) < current_time('timestamp');
            echo '<span class="docmanager-expiry ' . ($is_expired ? 'expired' : '') . '">';
            echo '<i class="fa fa-clock"></i> ';
            echo $is_expired ? __('Scaduto il', 'docmanager') : __('Scade il', 'docmanager');
            echo ' ' . date_i18n(get_option('date_format'), strtotime($expiry_date));
            echo '</span>';
        }
        
        echo '</div>';
        
        // Mostrare note
        if ($notes) {
            echo '<div class="docmanager-notes">' . esc_html($notes) . '</div>';
        }
        
        // Pulsanti azione
        echo '<div class="docmanager-actions">';
        
        if ($download_url) {
            echo '<a href="' . esc_url($download_url) . '" class="docmanager-btn docmanager-btn-download" target="_blank">';
            echo '<i class="fa fa-download"></i> ' . __('Scarica', 'docmanager');
            echo '</a>';
        }
        
        if ($is_viewable && $view_url) {
            echo '<a href="' . esc_url($view_url) . '" class="docmanager-btn docmanager-btn-view" target="_blank">';
            echo '<i class="fa fa-eye"></i> ' . __('Visualizza', 'docmanager');
            echo '</a>';
        }
        
        echo '</div>';
        
        echo '</div>';
        
        if ($layout === 'accordion') {
            echo '</div>';
        }
    }
}