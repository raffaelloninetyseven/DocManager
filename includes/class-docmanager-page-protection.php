<?php
/**
 * Gestione protezione pagine per DocManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Page_Protection {
    
    public function __construct() {
        add_action('template_redirect', array($this, 'check_page_protection'));
    }
    
    public function check_page_protection() {
        // Solo per le pagine pubbliche, non per admin
        if (is_admin() || is_user_logged_in()) {
            return;
        }
        
        // Ottieni le pagine protette
        $protected_pages = get_option('docmanager_protected_pages', array());
        
        if (empty($protected_pages)) {
            return;
        }
        
        // Verifica se la pagina corrente è protetta
        $current_page_id = get_queried_object_id();
        
        if (in_array($current_page_id, $protected_pages)) {
            $this->redirect_to_login();
        }
    }
    
    private function redirect_to_login() {
        $login_page_id = get_option('docmanager_login_page', 0);
        
        if ($login_page_id && $login_page_id > 0) {
            // Redirect alla pagina di login personalizzata
            $login_url = get_permalink($login_page_id);
            
            // Aggiungi parametro redirect_to per tornare alla pagina dopo il login
            $current_url = home_url($_SERVER['REQUEST_URI']);
            $login_url = add_query_arg('redirect_to', urlencode($current_url), $login_url);
            
        } else {
            // Usa il login di WordPress
            $current_url = home_url($_SERVER['REQUEST_URI']);
            $login_url = wp_login_url($current_url);
        }
        
        wp_redirect($login_url);
        exit;
    }
    
    /**
     * Hook per gestire il redirect dopo login dalla pagina personalizzata
     */
    public static function handle_custom_login_redirect() {
        if (is_user_logged_in() && isset($_GET['redirect_to'])) {
            $redirect_url = urldecode($_GET['redirect_to']);
            
            // Verifica che l'URL sia del nostro sito per sicurezza
            if (strpos($redirect_url, home_url()) === 0) {
                wp_redirect($redirect_url);
                exit;
            }
        }
    }
}