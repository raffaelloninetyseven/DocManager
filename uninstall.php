<?php
/**
 * Document Manager Pro Uninstall Script
 * 
 * Questo file viene eseguito quando il plugin viene disinstallato da WordPress.
 * Rimuove tutti i dati del plugin dal database e dal filesystem.
 * 
 * @package DocManager
 * @version 1.0.0
 */

// Se uninstall non è chiamato da WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Verifica che sia davvero la disinstallazione del nostro plugin
if (!defined('ABSPATH')) {
    exit;
}

// Classe per gestire la disinstallazione
class DocManager_Uninstaller {
    
    public static function uninstall() {
        global $wpdb;
        
        // Verifica permessi
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // Log inizio disinstallazione
        error_log('DocManager: Iniziando disinstallazione plugin');
        
        // Rimuovi tabelle database
        self::drop_database_tables();
        
        // Rimuovi opzioni
        self::delete_plugin_options();
        
        // Rimuovi capabilities personalizzate
        self::remove_custom_capabilities();
        
        // Rimuovi ruoli personalizzati
        self::remove_custom_roles();
        
        // Rimuovi file caricati (opzionale)
        self::cleanup_uploaded_files();
        
        // Rimuovi transients e cache
        self::cleanup_transients();
        
        // Rimuovi metadati utenti
        self::cleanup_user_meta();
        
        // Pulisci cron jobs
        self::cleanup_cron_jobs();
        
        // Log fine disinstallazione
        error_log('DocManager: Disinstallazione completata');
    }
    
    /**
     * Rimuove le tabelle del database
     */
    private static function drop_database_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'docmanager_documents',
            $wpdb->prefix . 'docmanager_permissions',
            $wpdb->prefix . 'docmanager_logs'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
            error_log("DocManager: Tabella {$table} rimossa");
        }
    }
    
    /**
     * Rimuove tutte le opzioni del plugin
     */
    private static function delete_plugin_options() {
        $options = array(
            // Opzioni principali
            'docmanager_version',
            'docmanager_db_version',
            'docmanager_install_date',
            
            // Impostazioni generali
            'docmanager_block_admin_access',
            'docmanager_blocked_roles',
            'docmanager_enable_logs',
            'docmanager_max_upload_size',
            'docmanager_allowed_file_types',
            'docmanager_upload_directory',
            
            // Impostazioni permessi
            'docmanager_default_permissions',
            'docmanager_auto_assign_permissions',
            'docmanager_guest_access',
            
            // Impostazioni notifiche
            'docmanager_email_notifications',
            'docmanager_notification_recipients',
            
            // Impostazioni sicurezza
            'docmanager_security_settings',
            'docmanager_access_logs',
            
            // Impostazioni cache
            'docmanager_cache_enabled',
            'docmanager_cache_duration',
            
            // Impostazioni avanzate
            'docmanager_advanced_settings',
            'docmanager_custom_css',
            'docmanager_integration_settings'
        );
        
        foreach ($options as $option) {
            delete_option($option);
            delete_site_option($option); // Per multisite
        }
        
        error_log('DocManager: Opzioni del plugin rimosse');
    }
    
    /**
     * Rimuove capabilities personalizzate dai ruoli
     */
    private static function remove_custom_capabilities() {
        $capabilities = array(
            'view_documents',
            'upload_documents',
            'manage_documents',
            'delete_documents',
            'assign_document_permissions',
            'view_document_logs',
            'manage_document_settings'
        );
        
        // Rimuovi capabilities da tutti i ruoli
        $roles = wp_roles()->roles;
        
        foreach ($roles as $role_name => $role_info) {
            $role = get_role($role_name);
            
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
        
        error_log('DocManager: Capabilities personalizzate rimosse');
    }
    
    /**
     * Rimuove ruoli personalizzati
     */
    private static function remove_custom_roles() {
        $custom_roles = array(
            'doc_manager',
            'document_viewer',
            'document_uploader'
        );
        
        foreach ($custom_roles as $role) {
            remove_role($role);
        }
        
        error_log('DocManager: Ruoli personalizzati rimossi');
    }
    
    /**
     * Rimuove file caricati (OPZIONALE - chiedi conferma all'utente)
     */
    private static function cleanup_uploaded_files() {
        // Verifica se l'utente vuole rimuovere i file
        $remove_files = get_option('docmanager_remove_files_on_uninstall', false);
        
        if (!$remove_files) {
            error_log('DocManager: File conservati (come configurato)');
            return;
        }
        
        $upload_dir = wp_upload_dir();
        $docmanager_dir = $upload_dir['basedir'] . '/docmanager/';
        
        if (is_dir($docmanager_dir)) {
            self::recursive_delete($docmanager_dir);
            error_log('DocManager: Directory uploads rimossa');
        }
    }
    
    /**
     * Rimuove directory ricorsivamente
     */
    private static function recursive_delete($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                self::recursive_delete($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Rimuove transients e cache
     */
    private static function cleanup_transients() {
        global $wpdb;
        
        // Rimuovi transients del plugin
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_docmanager_%' 
             OR option_name LIKE '_transient_timeout_docmanager_%'"
        );
        
        // Rimuovi transients multisite
        if (is_multisite()) {
            $wpdb->query(
                "DELETE FROM {$wpdb->sitemeta} 
                 WHERE meta_key LIKE '_site_transient_docmanager_%' 
                 OR meta_key LIKE '_site_transient_timeout_docmanager_%'"
            );
        }
        
        // Rimuovi cache object cache se presente
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group('docmanager');
        }
        
        error_log('DocManager: Cache e transients rimossi');
    }
    
    /**
     * Rimuove metadati utenti relativi al plugin
     */
    private static function cleanup_user_meta() {
        global $wpdb;
        
        $meta_keys = array(
            'docmanager_preferences',
            'docmanager_last_upload',
            'docmanager_upload_count',
            'docmanager_viewed_documents',
            'docmanager_favorites',
            'docmanager_dashboard_hidden',
            'docmanager_notification_settings'
        );
        
        foreach ($meta_keys as $meta_key) {
            $wpdb->delete(
                $wpdb->usermeta,
                array('meta_key' => $meta_key),
                array('%s')
            );
        }
        
        error_log('DocManager: Metadati utenti rimossi');
    }
    
    /**
     * Rimuove cron jobs schedulati
     */
    private static function cleanup_cron_jobs() {
        $cron_hooks = array(
            'docmanager_cleanup_logs',
            'docmanager_cleanup_temp_files',
            'docmanager_send_notifications',
            'docmanager_backup_documents',
            'docmanager_update_statistics'
        );
        
        foreach ($cron_hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }
        
        error_log('DocManager: Cron jobs rimossi');
    }
    
    /**
     * Rimuove post meta correlati (se il plugin usa post types)
     */
    private static function cleanup_post_meta() {
        global $wpdb;
        
        $meta_keys = array(
            '_docmanager_document_id',
            '_docmanager_file_path',
            '_docmanager_permissions',
            '_docmanager_upload_user'
        );
        
        foreach ($meta_keys as $meta_key) {
            $wpdb->delete(
                $wpdb->postmeta,
                array('meta_key' => $meta_key),
                array('%s')
            );
        }
    }
    
    /**
     * Pulizia avanzata per multisite
     */
    private static function cleanup_multisite() {
        if (!is_multisite()) {
            return;
        }
        
        global $wpdb;
        
        // Ottieni tutti i blog
        $blogs = $wpdb->get_results(
            "SELECT blog_id FROM {$wpdb->blogs} WHERE deleted = 0"
        );
        
        foreach ($blogs as $blog) {
            switch_to_blog($blog->blog_id);
            
            // Ripeti la pulizia per ogni sito
            self::delete_plugin_options();
            self::cleanup_transients();
            self::cleanup_user_meta();
            
            restore_current_blog();
        }
        
        error_log('DocManager: Pulizia multisite completata');
    }
    
    /**
     * Backup dati prima della rimozione (opzionale)
     */
    private static function create_backup() {
        $backup_enabled = get_option('docmanager_backup_on_uninstall', false);
        
        if (!$backup_enabled) {
            return;
        }
        
        global $wpdb;
        
        $backup_data = array(
            'timestamp' => current_time('mysql'),
            'version' => get_option('docmanager_version'),
            'documents' => $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}docmanager_documents"
            ),
            'permissions' => $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}docmanager_permissions"
            ),
            'logs' => $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}docmanager_logs LIMIT 1000"
            ),
            'settings' => array(
                'blocked_roles' => get_option('docmanager_blocked_roles'),
                'enable_logs' => get_option('docmanager_enable_logs'),
                'allowed_file_types' => get_option('docmanager_allowed_file_types')
            )
        );
        
        $upload_dir = wp_upload_dir();
        $backup_file = $upload_dir['basedir'] . '/docmanager_backup_' . date('Y-m-d_H-i-s') . '.json';
        
        file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
        
        error_log("DocManager: Backup creato in {$backup_file}");
    }
    
    /**
     * Notifica agli amministratori della disinstallazione
     */
    private static function notify_administrators() {
        $notify_on_uninstall = get_option('docmanager_notify_on_uninstall', false);
        
        if (!$notify_on_uninstall) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $site_name = get_option('blogname');
        
        $subject = sprintf(
            '[%s] Document Manager Pro - Plugin Disinstallato',
            $site_name
        );
        
        $message = sprintf(
            "Il plugin Document Manager Pro è stato disinstallato dal sito %s.\n\n" .
            "Data: %s\n" .
            "Utente: %s\n" .
            "IP: %s\n\n" .
            "Tutti i dati del plugin sono stati rimossi come configurato.\n\n" .
            "Se questa disinstallazione non era prevista, contatta immediatamente l'amministratore del sistema.",
            home_url(),
            current_time('mysql'),
            wp_get_current_user()->user_login ?? 'Sconosciuto',
            $_SERVER['REMOTE_ADDR'] ?? 'IP non disponibile'
        );
        
        wp_mail($admin_email, $subject, $message);
    }
}

// Esegui la disinstallazione
try {
    // Crea backup se richiesto
    DocManager_Uninstaller::create_backup();
    
    // Notifica amministratori se richiesto
    DocManager_Uninstaller::notify_administrators();
    
    // Esegui disinstallazione principale
    DocManager_Uninstaller::uninstall();
    
    // Pulizia multisite se necessario
    DocManager_Uninstaller::cleanup_multisite();
    
} catch (Exception $e) {
    error_log('DocManager Uninstall Error: ' . $e->getMessage());
}

// Flush rewrite rules
flush_rewrite_rules();