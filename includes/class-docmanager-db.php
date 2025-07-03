<?php
if (!defined('ABSPATH')) {
    exit;
}

class DocManager_DB {
    
    private $wpdb;
    private $documents_table;
    private $permissions_table;
    private $logs_table;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->documents_table = $wpdb->prefix . 'docmanager_documents';
        $this->permissions_table = $wpdb->prefix . 'docmanager_permissions';
        $this->logs_table = $wpdb->prefix . 'docmanager_logs';
    }
    
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Tabella documenti
        $sql_documents = "CREATE TABLE IF NOT EXISTS {$this->documents_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size int(11) NOT NULL,
            file_type varchar(100) NOT NULL,
            category varchar(100),
            tags text,
            uploaded_by int(11) NOT NULL,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY uploaded_by (uploaded_by),
            KEY category (category),
            KEY status (status)
        ) $charset_collate;";
        
        // Tabella permessi
        $sql_permissions = "CREATE TABLE IF NOT EXISTS {$this->permissions_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            document_id int(11) NOT NULL,
            user_id int(11) DEFAULT NULL,
            user_role varchar(50) DEFAULT NULL,
            user_group varchar(100) DEFAULT NULL,
            permission_type varchar(20) DEFAULT 'view',
            granted_by int(11) NOT NULL,
            granted_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY user_id (user_id),
            KEY user_role (user_role)
        ) $charset_collate;";
        
        // Tabella logs
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$this->logs_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            document_id int(11) NOT NULL,
            action varchar(50) NOT NULL,
            ip_address varchar(45),
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY document_id (document_id),
            KEY action (action)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_documents);
        dbDelta($sql_permissions);
        dbDelta($sql_logs);
    }
    
    public function insert_document($data) {
        return $this->wpdb->insert(
            $this->documents_table,
            $data,
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );
    }
    
    public function get_user_documents($user_id, $user_roles = array()) {
        $sql = "SELECT DISTINCT d.* FROM {$this->documents_table} d
                LEFT JOIN {$this->permissions_table} p ON d.id = p.document_id
                WHERE d.status = 'active' AND (
                    p.user_id = %d";
        
        $params = array($user_id);
        
        if (!empty($user_roles)) {
            $placeholders = implode(',', array_fill(0, count($user_roles), '%s'));
            $sql .= " OR p.user_role IN ($placeholders)";
            $params = array_merge($params, $user_roles);
        }
        
        $sql .= ") ORDER BY d.upload_date DESC";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }
    
    public function log_action($user_id, $document_id, $action) {
        return $this->wpdb->insert(
            $this->logs_table,
            array(
                'user_id' => $user_id,
                'document_id' => $document_id,
                'action' => $action,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    public function get_documents_by_category($category = null, $user_id = null) {
        $sql = "SELECT d.* FROM {$this->documents_table} d";
        $where = array("d.status = 'active'");
        $params = array();
        
        if ($category) {
            $where[] = "d.category = %s";
            $params[] = $category;
        }
        
        if ($user_id) {
            $sql .= " LEFT JOIN {$this->permissions_table} p ON d.id = p.document_id";
            $where[] = "p.user_id = %d";
            $params[] = $user_id;
        }
        
        $sql .= " WHERE " . implode(" AND ", $where) . " ORDER BY d.upload_date DESC";
        
        if (!empty($params)) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_results($sql);
    }
}