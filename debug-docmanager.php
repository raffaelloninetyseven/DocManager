<?php
/**
 * Debug DocManager Plugin
 * 
 * Aggiungi questo file nella root del plugin per testare la funzionalità
 */

// Verifica se WordPress è caricato
if (!defined('ABSPATH')) {
    exit('WordPress not loaded');
}

// Debug class
class DocManager_Debug {
    
    public static function run_tests() {
        echo '<h2>DocManager Debug Test</h2>';
        
        // Test 1: Verifica classi
        self::test_classes();
        
        // Test 2: Verifica database
        self::test_database();
        
        // Test 3: Verifica permessi
        self::test_permissions();
        
        // Test 4: Verifica file upload
        self::test_upload_directory();
        
        // Test 5: Verifica AJAX
        self::test_ajax_endpoints();
    }
    
    private static function test_classes() {
        echo '<h3>Test Classes</h3>';
        
        $classes = [
            'DocManager_DB',
            'DocManager_Ajax',
            'DocManager_Frontend',
            'DocManager_Permissions',
            'DocManager_Admin',
            'DocManager_Elementor'
        ];
        
        foreach ($classes as $class) {
            if (class_exists($class)) {
                echo "✅ Class {$class} exists<br>";
            } else {
                echo "❌ Class {$class} NOT found<br>";
            }
        }
    }
    
    private static function test_database() {
        echo '<h3>Test Database</h3>';
        
        global $wpdb;
        
        $tables = [
            'docmanager_documents',
            'docmanager_permissions',
            'docmanager_logs'
        ];
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            
            if ($table_exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
                echo "✅ Table {$table_name} exists with {$count} records<br>";
            } else {
                echo "❌ Table {$table_name} NOT found<br>";
            }
        }
    }
    
    private static function test_permissions() {
        echo '<h3>Test Permissions</h3>';
        
        if (!is_user_logged_in()) {
            echo "❌ User not logged in<br>";
            return;
        }
        
        $user = wp_get_current_user();
        echo "✅ Current user: {$user->display_name} (ID: {$user->ID})<br>";
        echo "✅ User roles: " . implode(', ', $user->roles) . "<br>";
        
        $capabilities = [
            'read',
            'manage_options',
            'view_documents',
            'upload_documents'
        ];
        
        foreach ($capabilities as $cap) {
            if (current_user_can($cap)) {
                echo "✅ Has capability: {$cap}<br>";
            } else {
                echo "❌ Missing capability: {$cap}<br>";
            }
        }
    }
    
    private static function test_upload_directory() {
        echo '<h3>Test Upload Directory</h3>';
        
        $upload_dir = wp_upload_dir();
        $docmanager_dir = $upload_dir['basedir'] . '/docmanager/';
        
        if (is_dir($docmanager_dir)) {
            echo "✅ DocManager directory exists: {$docmanager_dir}<br>";
            
            if (is_writable($docmanager_dir)) {
                echo "✅ Directory is writable<br>";
            } else {
                echo "❌ Directory is NOT writable<br>";
            }
        } else {
            echo "❌ DocManager directory does not exist<br>";
            
            if (wp_mkdir_p($docmanager_dir)) {
                echo "✅ Directory created successfully<br>";
            } else {
                echo "❌ Failed to create directory<br>";
            }
        }
    }
    
    private static function test_ajax_endpoints() {
        echo '<h3>Test AJAX Endpoints</h3>';
        
        $ajax_actions = [
            'docmanager_upload_frontend',
            'docmanager_delete_document',
            'docmanager_search_documents',
            'docmanager_get_user_documents'
        ];
        
        foreach ($ajax_actions as $action) {
            if (has_action("wp_ajax_{$action}")) {
                echo "✅ AJAX action registered: {$action}<br>";
            } else {
                echo "❌ AJAX action NOT registered: {$action}<br>";
            }
        }
    }
    
    public static function test_widget_functionality() {
        echo '<h3>Test Widget Functionality</h3>';
        
        if (!is_user_logged_in()) {
            echo "❌ User not logged in - widgets won't work<br>";
            return;
        }
        
        // Test DB connection
        $db = new DocManager_DB();
        $user_id = get_current_user_id();
        $user_roles = wp_get_current_user()->roles;
        
        try {
            $documents = $db->get_user_documents($user_id, $user_roles);
            echo "✅ DB query successful, found " . count($documents) . " documents<br>";
            
            if (count($documents) > 0) {
                echo "✅ Sample document: " . $documents[0]->title . "<br>";
            } else {
                echo "ℹ️ No documents found for current user<br>";
            }
        } catch (Exception $e) {
            echo "❌ DB query failed: " . $e->getMessage() . "<br>";
        }
    }
}

// Esegui i test se richiesto
if (isset($_GET['docmanager_debug'])) {
    DocManager_Debug::run_tests();
    DocManager_Debug::test_widget_functionality();
}
?>