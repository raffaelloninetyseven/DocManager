<?php
/**
 * Classe per gestire il Custom Post Type "referto"
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_PostType {
    
    public function __construct() {
        add_action('init', array($this, 'registerPostType'));
        add_action('init', array($this, 'registerTaxonomies'));
        add_action('add_meta_boxes', array($this, 'addMetaBoxes'));
        add_action('save_post', array($this, 'saveMetaBox'));
        add_action('manage_referto_posts_columns', array($this, 'customColumns'));
        add_action('manage_referto_posts_custom_column', array($this, 'customColumnContent'), 10, 2);
    }
    
    public function registerPostType() {
        $labels = array(
            'name' => __('Referti', 'docmanager'),
            'singular_name' => __('Referto', 'docmanager'),
            'add_new' => __('Aggiungi Nuovo', 'docmanager'),
            'add_new_item' => __('Aggiungi Nuovo Referto', 'docmanager'),
            'edit_item' => __('Modifica Referto', 'docmanager'),
            'new_item' => __('Nuovo Referto', 'docmanager'),
            'view_item' => __('Visualizza Referto', 'docmanager'),
            'search_items' => __('Cerca Referti', 'docmanager'),
            'not_found' => __('Nessun referto trovato', 'docmanager'),
            'not_found_in_trash' => __('Nessun referto nel cestino', 'docmanager'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => 'manage_options',
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'publish_posts' => 'manage_options',
                'read_private_posts' => 'manage_options',
                'delete_posts' => 'manage_options',
                'delete_private_posts' => 'manage_options',
                'delete_published_posts' => 'manage_options',
                'delete_others_posts' => 'manage_options',
                'edit_private_posts' => 'manage_options',
                'edit_published_posts' => 'manage_options',
            ),
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-media-document',
            'supports' => array('title', 'author'),
        );
        
        register_post_type('referto', $args);
    }
    
    public function registerTaxonomies() {
        // Categoria documenti
        $labels = array(
            'name' => __('Categorie Documenti', 'docmanager'),
            'singular_name' => __('Categoria Documento', 'docmanager'),
            'search_items' => __('Cerca Categorie', 'docmanager'),
            'all_items' => __('Tutte le Categorie', 'docmanager'),
            'parent_item' => __('Categoria Padre', 'docmanager'),
            'parent_item_colon' => __('Categoria Padre:', 'docmanager'),
            'edit_item' => __('Modifica Categoria', 'docmanager'),
            'update_item' => __('Aggiorna Categoria', 'docmanager'),
            'add_new_item' => __('Aggiungi Nuova Categoria', 'docmanager'),
            'new_item_name' => __('Nome Nuova Categoria', 'docmanager'),
            'menu_name' => __('Categorie', 'docmanager'),
        );
        
        register_taxonomy('doc_category', 'referto', array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
        ));
        
        // Tag documenti
        $labels = array(
            'name' => __('Tag Documenti', 'docmanager'),
            'singular_name' => __('Tag Documento', 'docmanager'),
            'search_items' => __('Cerca Tag', 'docmanager'),
            'popular_items' => __('Tag Popolari', 'docmanager'),
            'all_items' => __('Tutti i Tag', 'docmanager'),
            'edit_item' => __('Modifica Tag', 'docmanager'),
            'update_item' => __('Aggiorna Tag', 'docmanager'),
            'add_new_item' => __('Aggiungi Nuovo Tag', 'docmanager'),
            'new_item_name' => __('Nome Nuovo Tag', 'docmanager'),
            'menu_name' => __('Tag', 'docmanager'),
        );
        
        register_taxonomy('doc_tag', 'referto', array(
            'labels' => $labels,
            'hierarchical' => false,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
        ));
    }
    
    public function addMetaBoxes() {
        add_meta_box(
            'docmanager_file_info',
            __('Informazioni Documento', 'docmanager'),
            array($this, 'fileInfoMetaBox'),
            'referto',
            'normal',
            'high'
        );
        
        add_meta_box(
            'docmanager_user_assignment',
            __('Assegnazione Utente', 'docmanager'),
            array($this, 'userAssignmentMetaBox'),
            'referto',
            'side',
            'default'
        );
    }
    
    public function fileInfoMetaBox($post) {
        wp_nonce_field('docmanager_file_info_nonce', 'docmanager_file_info_nonce');
        
        $file_id = get_post_meta($post->ID, '_docmanager_file_id', true);
        $file_name = get_post_meta($post->ID, '_docmanager_file_name', true);
        $file_size = get_post_meta($post->ID, '_docmanager_file_size', true);
        $file_type = get_post_meta($post->ID, '_docmanager_file_type', true);
        $upload_date = get_post_meta($post->ID, '_docmanager_upload_date', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('File', 'docmanager'); ?></th>
                <td>
                    <input type="file" name="docmanager_file" id="docmanager_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" />
                    <?php if ($file_name): ?>
                        <p><strong><?php _e('File corrente:', 'docmanager'); ?></strong> <?php echo esc_html($file_name); ?></p>
                        <p><strong><?php _e('Dimensione:', 'docmanager'); ?></strong> <?php echo size_format($file_size); ?></p>
                        <p><strong><?php _e('Tipo:', 'docmanager'); ?></strong> <?php echo esc_html($file_type); ?></p>
                        <p><strong><?php _e('Data caricamento:', 'docmanager'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($upload_date)); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Note', 'docmanager'); ?></th>
                <td>
                    <textarea name="docmanager_notes" id="docmanager_notes" rows="3" class="large-text"><?php echo esc_textarea(get_post_meta($post->ID, '_docmanager_notes', true)); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function userAssignmentMetaBox($post) {
        wp_nonce_field('docmanager_user_assignment_nonce', 'docmanager_user_assignment_nonce');
        
        $assigned_user = get_post_meta($post->ID, '_docmanager_assigned_user', true);
        $users = get_users(array('orderby' => 'display_name'));
        ?>
        <p>
            <label for="docmanager_assigned_user"><?php _e('Assegna a utente:', 'docmanager'); ?></label>
            <select name="docmanager_assigned_user" id="docmanager_assigned_user" style="width: 100%;">
                <option value=""><?php _e('Seleziona utente...', 'docmanager'); ?></option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user->ID; ?>" <?php selected($assigned_user, $user->ID); ?>>
                        <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p>
            <label for="docmanager_expiry_date"><?php _e('Data scadenza (opzionale):', 'docmanager'); ?></label>
            <input type="date" name="docmanager_expiry_date" id="docmanager_expiry_date" value="<?php echo esc_attr(get_post_meta($post->ID, '_docmanager_expiry_date', true)); ?>" />
        </p>
        <?php
    }
    
    public function saveMetaBox($post_id) {
        if (!isset($_POST['docmanager_file_info_nonce']) || !wp_verify_nonce($_POST['docmanager_file_info_nonce'], 'docmanager_file_info_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Salvare assegnazione utente
        if (isset($_POST['docmanager_assigned_user'])) {
            update_post_meta($post_id, '_docmanager_assigned_user', sanitize_text_field($_POST['docmanager_assigned_user']));
        }
        
        // Salvare data scadenza
        if (isset($_POST['docmanager_expiry_date'])) {
            update_post_meta($post_id, '_docmanager_expiry_date', sanitize_text_field($_POST['docmanager_expiry_date']));
        }
        
        // Salvare note
        if (isset($_POST['docmanager_notes'])) {
            update_post_meta($post_id, '_docmanager_notes', sanitize_textarea_field($_POST['docmanager_notes']));
        }
        
        // Gestire upload file
        if (isset($_FILES['docmanager_file']) && $_FILES['docmanager_file']['error'] === UPLOAD_ERR_OK) {
            $file_handler = new DocManager_FileHandler();
            $result = $file_handler->uploadFile($_FILES['docmanager_file'], $post_id);
            
            if ($result) {
                update_post_meta($post_id, '_docmanager_file_id', $result['file_id']);
                update_post_meta($post_id, '_docmanager_file_name', $result['file_name']);
                update_post_meta($post_id, '_docmanager_file_size', $result['file_size']);
                update_post_meta($post_id, '_docmanager_file_type', $result['file_type']);
                update_post_meta($post_id, '_docmanager_upload_date', current_time('mysql'));
            }
        }
    }
    
    public function customColumns($columns) {
        $columns['assigned_user'] = __('Utente Assegnato', 'docmanager');
        $columns['file_info'] = __('File', 'docmanager');
        $columns['upload_date'] = __('Data Caricamento', 'docmanager');
        return $columns;
    }
    
    public function customColumnContent($column, $post_id) {
        switch ($column) {
            case 'assigned_user':
                $user_id = get_post_meta($post_id, '_docmanager_assigned_user', true);
                if ($user_id) {
                    $user = get_userdata($user_id);
                    echo $user ? esc_html($user->display_name) : __('Utente non trovato', 'docmanager');
                } else {
                    echo __('Non assegnato', 'docmanager');
                }
                break;
                
            case 'file_info':
                $file_name = get_post_meta($post_id, '_docmanager_file_name', true);
                $file_size = get_post_meta($post_id, '_docmanager_file_size', true);
                if ($file_name) {
                    echo esc_html($file_name) . '<br><small>' . size_format($file_size) . '</small>';
                } else {
                    echo __('Nessun file', 'docmanager');
                }
                break;
                
            case 'upload_date':
                $upload_date = get_post_meta($post_id, '_docmanager_upload_date', true);
                if ($upload_date) {
                    echo date_i18n(get_option('date_format'), strtotime($upload_date));
                } else {
                    echo __('Non specificata', 'docmanager');
                }
                break;
        }
    }
}