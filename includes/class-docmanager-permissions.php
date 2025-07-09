<?php
if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Permissions {
    
    public function __construct() {
        add_action('init', array($this, 'init_permissions'));
        add_action('admin_init', array($this, 'block_admin_access'));
        add_filter('user_has_cap', array($this, 'check_document_permissions'), 10, 3);
    }
    
    public function init_permissions() {
        $this->add_custom_capabilities();
    }
    
    private function add_custom_capabilities() {
        $capabilities = array(
            'view_documents',
            'upload_documents', 
            'manage_documents',
            'delete_documents',
            'assign_document_permissions'
        );
        
        // Aggiungi capabilities agli amministratori
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Aggiungi capabilities ai document managers
        $doc_manager_role = get_role('doc_manager');
        if ($doc_manager_role) {
            $doc_manager_role->add_cap('view_documents');
            $doc_manager_role->add_cap('upload_documents');
            $doc_manager_role->add_cap('manage_documents');
        }
    }
    
    public function block_admin_access() {
        if (!get_option('docmanager_block_admin_access')) {
            return;
        }
        
        $blocked_roles = get_option('docmanager_blocked_roles', array());
        
        if (empty($blocked_roles)) {
            return;
        }
        
        $current_user = wp_get_current_user();
        
        // Non bloccare gli amministratori
        if (in_array('administrator', $current_user->roles)) {
            return;
        }
        
        // Verifica se l'utente ha un ruolo bloccato
        $user_blocked = false;
        foreach ($current_user->roles as $role) {
            if (in_array($role, $blocked_roles)) {
                $user_blocked = true;
                break;
            }
        }
        
        if ($user_blocked && is_admin() && !wp_doing_ajax()) {
            wp_redirect(home_url());
            exit;
        }
    }
    
    public function check_document_permissions($allcaps, $caps, $args) {
        // Se non è una richiesta relativa ai documenti, non modificare nulla
        if (!isset($args[0]) || strpos($args[0], 'docmanager_') !== 0) {
            return $allcaps;
        }
        
        $capability = $args[0];
        $user_id = $args[1];
        $document_id = isset($args[2]) ? $args[2] : null;
        
        switch ($capability) {
            case 'docmanager_view_document':
                $allcaps[$capability] = $this->user_can_view_document($user_id, $document_id);
                break;
                
            case 'docmanager_download_document':
                $allcaps[$capability] = $this->user_can_download_document($user_id, $document_id);
                break;
                
            case 'docmanager_delete_document':
                $allcaps[$capability] = $this->user_can_delete_document($user_id, $document_id);
                break;
        }
        
        return $allcaps;
    }
    
    public function user_can_view_document($user_id, $document_id) {
        if (!$document_id || !$user_id) {
            return false;
        }
        
        // Gli amministratori possono sempre vedere tutto
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        return $this->check_user_document_permission($user_id, $document_id, 'view');
    }
    
    public function user_can_download_document($user_id, $document_id) {
        return $this->user_can_view_document($user_id, $document_id);
    }
    
    public function user_can_delete_document($user_id, $document_id) {
        if (!$document_id || !$user_id) {
            return false;
        }
        
        // Solo amministratori possono eliminare
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // O l'utente che ha caricato il documento
        global $wpdb;
        $documents_table = $wpdb->prefix . 'docmanager_documents';
        
        $uploaded_by = $wpdb->get_var($wpdb->prepare(
            "SELECT uploaded_by FROM {$documents_table} WHERE id = %d",
            $document_id
        ));
        
        return $uploaded_by == $user_id;
    }
    
    private function check_user_document_permission($user_id, $document_id, $permission_type = 'view') {
		global $wpdb;
		
		$user = get_userdata($user_id);
		if (!$user) {
			return false;
		}
		
		$user_roles = $user->roles;
		$permissions_table = $wpdb->prefix . 'docmanager_permissions';
		
		// Query separata per permesso diretto
		$direct_permission = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$permissions_table} 
			 WHERE document_id = %d AND user_id = %d AND permission_type = %s",
			$document_id, $user_id, $permission_type
		));
		
		if ($direct_permission) {
			return true;
		}
		
		// Query separata per permessi ruolo
		if (!empty($user_roles)) {
			foreach ($user_roles as $role) {
				$role_permission = $wpdb->get_var($wpdb->prepare(
					"SELECT id FROM {$permissions_table} 
					 WHERE document_id = %d AND user_role = %s AND permission_type = %s",
					$document_id, $role, $permission_type
				));
				
				if ($role_permission) {
					return true;
				}
			}
		}
		
		return false;
	}
    
    public function assign_permission($document_id, $user_id = null, $user_role = null, $user_group = null, $permission_type = 'view') {
        if (!current_user_can('assign_document_permissions') && !current_user_can('manage_options')) {
            return false;
        }
        
        global $wpdb;
        $permissions_table = $wpdb->prefix . 'docmanager_permissions';
        
        $data = array(
            'document_id' => $document_id,
            'permission_type' => $permission_type,
            'granted_by' => get_current_user_id()
        );
        
        $format = array('%d', '%s', '%d');
        
        if ($user_id) {
            $data['user_id'] = $user_id;
            $format[] = '%d';
        } elseif ($user_role) {
            $data['user_role'] = $user_role;
            $format[] = '%s';
        } elseif ($user_group) {
            $data['user_group'] = $user_group;
            $format[] = '%s';
        }
        
        return $wpdb->insert($permissions_table, $data, $format);
    }
    
    public function remove_permission($permission_id) {
        if (!current_user_can('assign_document_permissions') && !current_user_can('manage_options')) {
            return false;
        }
        
        global $wpdb;
        $permissions_table = $wpdb->prefix . 'docmanager_permissions';
        
        return $wpdb->delete($permissions_table, array('id' => $permission_id), array('%d'));
    }
    
    public function get_document_permissions($document_id) {
        global $wpdb;
        $permissions_table = $wpdb->prefix . 'docmanager_permissions';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name as user_name 
             FROM {$permissions_table} p
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
             WHERE p.document_id = %d
             ORDER BY p.granted_date DESC",
            $document_id
        ));
    }
    
    public function bulk_assign_permissions($document_ids, $user_ids = array(), $user_roles = array(), $permission_type = 'view') {
        if (!current_user_can('assign_document_permissions') && !current_user_can('manage_options')) {
            return false;
        }
        
        $results = array();
        
        foreach ($document_ids as $document_id) {
            foreach ($user_ids as $user_id) {
                $results[] = $this->assign_permission($document_id, $user_id, null, null, $permission_type);
            }
            
            foreach ($user_roles as $user_role) {
                $results[] = $this->assign_permission($document_id, null, $user_role, null, $permission_type);
            }
        }
        
        return $results;
    }
    
    public function get_user_accessible_documents($user_id, $permission_type = 'view') {
        $db = new DocManager_DB();
        $user = get_userdata($user_id);
        $user_roles = $user ? $user->roles : array();
        
        return $db->get_user_documents($user_id, $user_roles);
    }
}