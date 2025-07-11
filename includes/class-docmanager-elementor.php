<?php
/**
 * Integrazione con Elementor per DocManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Elementor {
    
    public function __construct() {
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
    }
    
    public function register_widgets() {
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Widget_Upload());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Widget_Manage());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DocManager_Widget_View());
    }
}