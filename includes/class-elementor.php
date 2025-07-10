<?php
/**
 * Classe per l'integrazione con Elementor
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Elementor {
    
    public function __construct() {
        add_action('elementor/widgets/widgets_registered', array($this, 'registerWidgets'));
        add_action('elementor/elements/categories_registered', array($this, 'addCategory'));
        add_action('elementor/frontend/after_enqueue_styles', array($this, 'enqueueStyles'));
    }
    
    /**
     * Registra i widget Elementor
     */
    public function registerWidgets() {
        // Widget per visualizzare i documenti
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Widget_View_Documents());
        
        // Widget per caricare i documenti
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Widget_Upload_Documents());
        
        // Widget per gestire i documenti (admin)
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Widget_Manage_Documents());
    }
    
    /**
     * Aggiunge categoria DocManager
     */
    public function addCategory($elements_manager) {
        $elements_manager->add_category(
            'docmanager',
            array(
                'title' => __('DocManager', 'docmanager'),
                'icon' => 'fa fa-file-text',
            )
        );
    }
    
    /**
     * Carica gli stili per Elementor
     */
    public function enqueueStyles() {
        wp_enqueue_style('docmanager-elementor', DOCMANAGER_PLUGIN_URL . 'assets/css/elementor.css', array(), DOCMANAGER_VERSION);
    }
}