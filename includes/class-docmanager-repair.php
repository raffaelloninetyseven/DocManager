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
		$logs_table = $wpdb->prefix . 'docmanager_logs';
		$charset_collate = $wpdb->get_charset_collate();
		
		// Elimina tabelle esistenti se danneggiate
		$wpdb->query("DROP TABLE IF EXISTS $table_name");
		$wpdb->query("DROP TABLE IF EXISTS $logs_table");
		
		// Ricrea tabella documenti
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
		
		// Ricrea tabella log
		$sql2 = "CREATE TABLE $logs_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			document_id mediumint(9) NOT NULL,
			user_id mediumint(9) NOT NULL,
			action varchar(50) NOT NULL,
			ip_address varchar(45),
			user_agent text,
			download_date datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY document_id (document_id),
			KEY user_id (user_id),
			KEY action (action)
		) $charset_collate;";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$result1 = dbDelta($sql);
		$result2 = dbDelta($sql2);
		
		return !empty($result1) && !empty($result2);
	}
    
    public static function check_table_structure() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'docmanager_documents';
		$logs_table = $wpdb->prefix . 'docmanager_logs';
		
		// Controlla tabella documenti
		$columns = $wpdb->get_col("DESCRIBE $table_name");
		$required_columns = array(
			'id', 'title', 'file_path', 'file_type', 'file_size',
			'user_id', 'uploaded_by', 'upload_date', 'notes', 'status'
		);
		$missing_columns = array_diff($required_columns, $columns);
		
		// Controlla tabella log
		$logs_exists = $wpdb->get_var("SHOW TABLES LIKE '$logs_table'") == $logs_table;
		$logs_columns = array();
		$missing_logs_columns = array();
		
		if ($logs_exists) {
			$logs_columns = $wpdb->get_col("DESCRIBE $logs_table");
			$required_logs_columns = array(
				'id', 'document_id', 'user_id', 'action', 'ip_address', 'user_agent', 'download_date'
			);
			$missing_logs_columns = array_diff($required_logs_columns, $logs_columns);
		}
		
		return array(
			'table_exists' => !empty($columns),
			'logs_table_exists' => $logs_exists,
			'missing_columns' => $missing_columns,
			'missing_logs_columns' => $missing_logs_columns,
			'is_valid' => empty($missing_columns) && $logs_exists && empty($missing_logs_columns)
		);
	}
    
    public static function add_admin_notice() {
		add_action('admin_notices', function() {
			$status = self::check_table_structure();
			
			if (!$status['is_valid']) {
				echo '<div class="notice notice-error is-dismissible">';
				echo '<p><strong>DocManager:</strong> Le tabelle del database necessitano riparazione.</p>';
				
				if (!$status['table_exists']) {
					echo '<p>• Tabella documenti mancante o danneggiata</p>';
				} elseif (!empty($status['missing_columns'])) {
					echo '<p>• Colonne mancanti nella tabella documenti: ' . implode(', ', $status['missing_columns']) . '</p>';
				}
				
				if (!$status['logs_table_exists']) {
					echo '<p>• Tabella log mancante</p>';
				} elseif (!empty($status['missing_logs_columns'])) {
					echo '<p>• Colonne mancanti nella tabella log: ' . implode(', ', $status['missing_logs_columns']) . '</p>';
				}
				
				echo '<p><a href="' . admin_url('admin.php?page=docmanager-repair') . '" class="button">Ripara Database</a></p>';
				echo '</div>';
			}
		});
	}
}