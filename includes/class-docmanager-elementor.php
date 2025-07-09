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
        $manage_widget_file = DOCMANAGER_PLUGIN_PATH . 'includes/widgets/class-docmanager-manage-widget.php';
        
        if (file_exists($documents_widget_file)) {
            require_once $documents_widget_file;
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Documents_Widget());
        }
        
        if (file_exists($upload_widget_file)) {
            require_once $upload_widget_file;
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Upload_Widget());
        }
        
        if (file_exists($manage_widget_file)) {
            require_once $manage_widget_file;
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Manage_Widget());
        }
    }
}