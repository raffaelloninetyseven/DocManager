<?php
/**
 * Classe per riparare la struttura del database DocManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Repair {
    
    public static function repair_database() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'docmanager_documents';
        $charset_collate = $wpdb->get_charset_collate();
        
        // Elimina tabella esistente se danneggiata
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // Ricrea tabella completa
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_type varchar(50) NOT NULL,
            file_size int NOT NULL,
            user_id int NOT NULL,
            uploaded_by int NOT NULL,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            notes text,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY uploaded_by (uploaded_by),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        return !empty($result);
    }
    
    public static function check_table_structure() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'docmanager_documents';
        
        $columns = $wpdb->get_col("DESCRIBE $table_name");
        
        $required_columns = array(
            'id', 'title', 'file_path', 'file_type', 'file_size',
            'user_id', 'uploaded_by', 'upload_date', 'notes', 'status'
        );
        
        $missing_columns = array_diff($required_columns, $columns);
        
        return array(
            'table_exists' => !empty($columns),
            'missing_columns' => $missing_columns,
            'is_valid' => empty($missing_columns)
        );
    }
    
    public static function add_admin_notice() {
        add_action('admin_notices', function() {
            $status = self::check_table_structure();
            
            if (!$status['is_valid']) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>DocManager:</strong> La tabella del database necessita riparazione.</p>';
                echo '<p><a href="' . admin_url('admin.php?page=docmanager-repair') . '" class="button">Ripara Database</a></p>';
                echo '</div>';
            }
        });
    }
}