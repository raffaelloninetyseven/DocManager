<?php
if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Elementor {
    
    public function __construct() {
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
        add_action('elementor/elements/categories_registered', array($this, 'add_widget_categories'));
    }
    
    public function add_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'docmanager',
            array(
                'title' => 'DocManager',
                'icon' => 'fa fa-file',
            )
        );
    }
    
    public function register_widgets() {
        if (class_exists('\Elementor\Widget_Base')) {
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Widget_Upload());
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Widget_Manage());
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Widget_View());
        }
    }
}