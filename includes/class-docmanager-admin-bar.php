<?php
/**
 * Gestione della barra di amministrazione WordPress
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Admin_Bar {
    
    public function __construct() {
		add_action('init', array($this, 'init_admin_bar_controls'));
		add_action('wp_loaded', array($this, 'remove_admin_bar_margin')); // Aggiunto
		add_action('wp_before_admin_bar_render', array($this, 'modify_admin_bar'));
		add_action('wp_head', array($this, 'hide_admin_bar_css'));
		add_action('admin_head', array($this, 'hide_admin_bar_css'));
		add_action('admin_init', array($this, 'redirect_non_admin_users'));
	}
    
    public function init_admin_bar_controls() {
        if ($this->should_hide_admin_bar()) {
            add_filter('show_admin_bar', '__return_false');
            remove_action('wp_head', '_admin_bar_bump_cb');
        }
    }
    
    public function modify_admin_bar() {
        global $wp_admin_bar;
        
        if ($this->should_hide_admin_bar()) {
            $wp_admin_bar->remove_menu('wp-logo');
            $wp_admin_bar->remove_menu('about');
            $wp_admin_bar->remove_menu('wporg');
            $wp_admin_bar->remove_menu('documentation');
            $wp_admin_bar->remove_menu('support-forums');
            $wp_admin_bar->remove_menu('feedback');
            $wp_admin_bar->remove_menu('view-site');
        }
    }
    
    public function hide_admin_bar_css() {
		if ($this->should_hide_admin_bar()) {
			echo '<style type="text/css">
				#wpadminbar { display: none !important; }
				html.wp-toolbar { padding-top: 0 !important; }
				body.admin-bar { margin-top: 0 !important; }
				body.admin-bar .elementor-editor-wrapper { top: 0 !important; }
				html { margin-top: 0 !important; }
				body { padding-top: 0 !important; }
			</style>';
		}
	}
    
    private function should_hide_admin_bar() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $hide_for_roles = get_option('docmanager_hide_admin_bar_roles', array());
        
        if (empty($hide_for_roles)) {
            return false;
        }
        
        // Se l'utente ha almeno uno dei ruoli selezionati, nascondi la barra
        foreach ($user_roles as $role) {
            if (in_array($role, $hide_for_roles)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Metodo pubblico per verificare se la barra deve essere nascosta
     */
    public static function is_admin_bar_hidden() {
        $instance = new self();
        return $instance->should_hide_admin_bar();
    }
	
	public function redirect_non_admin_users() {
		if (is_admin() && !defined('DOING_AJAX') && $this->should_hide_admin_bar()) {
			// Permetti solo alcune pagine admin essenziali
			$allowed_pages = array('profile.php', 'user-edit.php');
			$current_page = basename($_SERVER['REQUEST_URI']);
			
			if (!in_array($current_page, $allowed_pages) && !current_user_can('edit_posts')) {
				wp_redirect(home_url());
				exit;
			}
		}
	}
	
	public function remove_admin_bar_margin() {
		if ($this->should_hide_admin_bar()) {
			remove_action('wp_head', '_admin_bar_bump_cb');
			add_action('wp_head', array($this, 'remove_admin_bar_styles'));
		}
	}

	public function remove_admin_bar_styles() {
		echo '<style type="text/css">
			html { margin-top: 0 !important; }
			* html body { margin-top: 0 !important; }
			@media screen and (max-width: 782px) {
				html { margin-top: 0 !important; }
				* html body { margin-top: 0 !important; }
			}
		</style>';
	}
}