<?php
/**
 * Classe per gestire il Custom Post Type "referto"
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_PostType {
    
    public function __construct() {
        add_action('init', array($this, 'registerPostType'), 0);
        add_action('init', array($this, 'registerTaxonomies'), 0);
        add_action('add_meta_boxes', array($this, 'addMetaBoxes'));
        add_action('save_post', array($this, 'saveMetaBox'));
        add_action('manage_referto_posts_columns', array($this, 'customColumns'));
        add_action('manage_referto_posts_custom_column', array($this, 'customColumnContent'), 10, 2);
        add_filter('post_updated_messages', array($this, 'updateMessages'));
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
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'docmanager-dashboard',
            'query_var' => true,
            'rewrite' => array('slug' => 'referto'),
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => 'edit_posts',
                'edit_posts' => 'edit_posts',
                'edit_others_posts' => 'edit_others_posts',
                'publish_posts' => 'publish_posts',
                'read_private_posts' => 'read_private_posts',
                'delete_posts' => 'delete_posts',
                'delete_private_posts' => 'delete_private_posts',
                'delete_published_posts' => 'delete_published_posts',
                'delete_others_posts' => 'delete_others_posts',
                'edit_private_posts' => 'edit_private_posts',
                'edit_published_posts' => 'edit_published_posts',
            ),
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'author'),
        );
        
        register_post_type('referto', $args);
    }
    
    public function registerTaxonomies() {
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
            'rewrite' => array('slug' => 'doc-category'),
            'show_in_menu' => true,
        ));
        
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
            'rewrite' => array('slug' => 'doc-tag'),
            'show_in_menu' => true,
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
        
        add_meta_box(
            'docmanager_file_preview',
            __('Anteprima File', 'docmanager'),
            array($this, 'filePreviewMetaBox'),
            'referto',
            'side',
            'low'
        );
    }
    
    public function fileInfoMetaBox($post) {
        wp_nonce_field('docmanager_file_info_nonce', 'docmanager_file_info_nonce');
        
        $file_id = get_post_meta($post->ID, '_docmanager_file_id', true);
        $file_name = get_post_meta($post->ID, '_docmanager_file_name', true);
        $file_size = get_post_meta($post->ID, '_docmanager_file_size', true);
        $file_type = get_post_meta($post->ID, '_docmanager_file_type', true);
        $upload_date = get_post_meta($post->ID, '_docmanager_upload_date', true);
        $notes = get_post_meta($post->ID, '_docmanager_notes', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('File', 'docmanager'); ?></th>
                <td>
                    <input type="file" name="docmanager_file" id="docmanager_file" 
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" />
                    
                    <?php if ($file_name): ?>
                        <div class="docmanager-file-info" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                            <p><strong><?php _e('File corrente:', 'docmanager'); ?></strong> <?php echo esc_html($file_name); ?></p>
                            <p><strong><?php _e('Dimensione:', 'docmanager'); ?></strong> <?php echo size_format($file_size); ?></p>
                            <p><strong><?php _e('Tipo:', 'docmanager'); ?></strong> <?php echo esc_html($file_type); ?></p>
                            <?php if ($upload_date): ?>
                                <p><strong><?php _e('Data caricamento:', 'docmanager'); ?></strong> 
                                   <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($upload_date)); ?></p>
                            <?php endif; ?>
                            
                            <?php
                            if (class_exists('DocManager_FileHandler')) {
                                $file_handler = new DocManager_FileHandler();
                                $download_url = $file_handler->getDownloadUrl($post->ID);
                                if ($download_url): ?>
                                    <p><a href="<?php echo esc_url($download_url); ?>" class="button button-secondary" target="_blank">
                                        <?php _e('Scarica File', 'docmanager'); ?>
                                    </a></p>
                                <?php endif;
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <p class="description"><?php _e('Nessun file caricato', 'docmanager'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Note', 'docmanager'); ?></th>
                <td>
                    <textarea name="docmanager_notes" id="docmanager_notes" rows="4" class="large-text"><?php echo esc_textarea($notes); ?></textarea>
                    <p class="description"><?php _e('Note aggiuntive sul documento', 'docmanager'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function userAssignmentMetaBox($post) {
        wp_nonce_field('docmanager_user_assignment_nonce', 'docmanager_user_assignment_nonce');
        
        $assigned_user = get_post_meta($post->ID, '_docmanager_assigned_user', true);
        $expiry_date = get_post_meta($post->ID, '_docmanager_expiry_date', true);
        $users = get_users(array('orderby' => 'display_name'));
        
        ?>
        <p>
            <label for="docmanager_assigned_user"><strong><?php _e('Assegna a utente:', 'docmanager'); ?></strong></label>
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
            <label for="docmanager_expiry_date"><strong><?php _e('Data scadenza (opzionale):', 'docmanager'); ?></strong></label>
            <input type="date" name="docmanager_expiry_date" id="docmanager_expiry_date" 
                   value="<?php echo esc_attr($expiry_date); ?>" style="width: 100%;" />
            <span class="description"><?php _e('Il documento non sarà accessibile dopo questa data', 'docmanager'); ?></span>
        </p>
        
        <hr>
        
        <p><strong><?php _e('Stato Documento:', 'docmanager'); ?></strong></p>
        <p>
            <label>
                <input type="radio" name="post_status" value="publish" <?php checked(get_post_status($post->ID), 'publish'); ?>>
                <?php _e('Pubblicato (visibile all\'utente)', 'docmanager'); ?>
            </label><br>
            
            <label>
                <input type="radio" name="post_status" value="draft" <?php checked(get_post_status($post->ID), 'draft'); ?>>
                <?php _e('Bozza (non visibile)', 'docmanager'); ?>
            </label><br>
            
            <label>
                <input type="radio" name="post_status" value="private" <?php checked(get_post_status($post->ID), 'private'); ?>>
                <?php _e('Privato (solo admin)', 'docmanager'); ?>
            </label>
        </p>
        <?php
    }
    
    public function filePreviewMetaBox($post) {
        if (!class_exists('DocManager_FileHandler')) {
            echo '<p>' . __('File handler non disponibile', 'docmanager') . '</p>';
            return;
        }
        
        $file_handler = new DocManager_FileHandler();
        $file_info = $file_handler->getFileInfo($post->ID);
        
        if (!$file_info) {
            echo '<p>' . __('Nessun file caricato', 'docmanager') . '</p>';
            return;
        }
        
        $view_url = $file_handler->getViewUrl($post->ID);
        $is_viewable = $file_handler->isViewable($post->ID);
        
        if ($is_viewable && $view_url) {
            if (strpos($file_info['type'], 'image/') === 0) {
                echo '<img src="' . esc_url($view_url) . '" style="max-width: 100%; max-height: 200px;">';
            } else {
                echo '<p><a href="' . esc_url($view_url) . '" target="_blank" class="button button-primary">' . __('Visualizza File', 'docmanager') . '</a></p>';
            }
        } else {
            $icon = $file_handler->getFileIcon($file_info['type']);
            echo '<p style="text-align: center; font-size: 48px;"><i class="fa ' . $icon . '"></i></p>';
            echo '<p style="text-align: center;"><strong>' . esc_html($file_info['name']) . '</strong></p>';
        }
    }
    
    public function saveMetaBox($post_id) {
        if (!isset($_POST['docmanager_file_info_nonce']) || 
            !wp_verify_nonce($_POST['docmanager_file_info_nonce'], 'docmanager_file_info_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'referto') {
            return;
        }
        
        if (isset($_POST['docmanager_assigned_user'])) {
            update_post_meta($post_id, '_docmanager_assigned_user', sanitize_text_field($_POST['docmanager_assigned_user']));
        }
        
        if (isset($_POST['docmanager_expiry_date'])) {
            update_post_meta($post_id, '_docmanager_expiry_date', sanitize_text_field($_POST['docmanager_expiry_date']));
        }
        
        if (isset($_POST['docmanager_notes'])) {
            update_post_meta($post_id, '_docmanager_notes', sanitize_textarea_field($_POST['docmanager_notes']));
        }
        
        if (isset($_FILES['docmanager_file']) && $_FILES['docmanager_file']['error'] === UPLOAD_ERR_OK) {
            if (!class_exists('DocManager_FileHandler')) {
                return;
            }
            
            $file_handler = new DocManager_FileHandler();
            
            $old_file_path = $file_handler->getFileInfo($post_id);
            if ($old_file_path && isset($old_file_path['path'])) {
                if (file_exists($old_file_path['path'])) {
                    unlink($old_file_path['path']);
                }
            }
            
            $result = $file_handler->uploadFile($_FILES['docmanager_file'], $post_id);
            
            if ($result) {
                update_post_meta($post_id, '_docmanager_file_id', $result['file_id']);
                update_post_meta($post_id, '_docmanager_file_name', $result['file_name']);
                update_post_meta($post_id, '_docmanager_file_size', $result['file_size']);
                update_post_meta($post_id, '_docmanager_file_type', $result['file_type']);
                update_post_meta($post_id, '_docmanager_unique_filename', $result['unique_filename']);
                update_post_meta($post_id, '_docmanager_upload_date', current_time('mysql'));
                
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('File caricato con successo!', 'docmanager') . '</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Errore durante il caricamento del file. Controlla i permessi e le impostazioni.', 'docmanager') . '</p></div>';
                });
            }
        }
    }
    
    public function customColumns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['assigned_user'] = __('Utente Assegnato', 'docmanager');
                $new_columns['file_info'] = __('File', 'docmanager');
            }
        }
        
        $new_columns['upload_date'] = __('Data Caricamento', 'docmanager');
        
        return $new_columns;
    }
    
    public function customColumnContent($column, $post_id) {
        switch ($column) {
            case 'assigned_user':
                $user_id = get_post_meta($post_id, '_docmanager_assigned_user', true);
                if ($user_id) {
                    $user = get_userdata($user_id);
                    if ($user) {
                        echo '<strong>' . esc_html($user->display_name) . '</strong><br>';
                        echo '<small>' . esc_html($user->user_email) . '</small>';
                    } else {
                        echo '<span style="color: #d63638;">' . __('Utente non trovato', 'docmanager') . '</span>';
                    }
                } else {
                    echo '<span style="color: #646970;">' . __('Non assegnato', 'docmanager') . '</span>';
                }
                break;
                
            case 'file_info':
                if (!class_exists('DocManager_FileHandler')) {
                    echo '<span style="color: #d63638;">' . __('File handler non disponibile', 'docmanager') . '</span>';
                    break;
                }
                
                $file_handler = new DocManager_FileHandler();
                $file_info = $file_handler->getFileInfo($post_id);
                
                if ($file_info) {
                    $icon = $file_handler->getFileIcon($file_info['type']);
                    echo '<div style="display: flex; align-items: center; gap: 8px;">';
                    echo '<i class="fa ' . $icon . '" style="font-size: 16px; color: #2271b1;"></i>';
                    echo '<div>';
                    echo '<strong>' . esc_html($file_info['name']) . '</strong><br>';
                    echo '<small>' . size_format($file_info['size']) . '</small>';
                    echo '</div>';
                    echo '</div>';
                    
                    $download_url = $file_handler->getDownloadUrl($post_id);
                    if ($download_url) {
                        echo '<div style="margin-top: 4px;">';
                        echo '<a href="' . esc_url($download_url) . '" target="_blank" class="button button-small">' . __('Scarica', 'docmanager') . '</a>';
                        echo '</div>';
                    }
                } else {
                    echo '<span style="color: #d63638;">' . __('Nessun file', 'docmanager') . '</span>';
                }
                break;
                
            case 'upload_date':
                $upload_date = get_post_meta($post_id, '_docmanager_upload_date', true);
                if ($upload_date) {
                    echo '<strong>' . date_i18n(get_option('date_format'), strtotime($upload_date)) . '</strong><br>';
                    echo '<small>' . date_i18n(get_option('time_format'), strtotime($upload_date)) . '</small>';
                    
                    $expiry_date = get_post_meta($post_id, '_docmanager_expiry_date', true);
                    if ($expiry_date) {
                        $is_expired = strtotime($expiry_date) < current_time('timestamp');
                        echo '<br><small style="color: ' . ($is_expired ? '#d63638' : '#135e96') . ';">';
                        echo ($is_expired ? '⚠ ' : '📅 ') . date_i18n(get_option('date_format'), strtotime($expiry_date));
                        echo '</small>';
                    }
                } else {
                    echo '<span style="color: #646970;">' . __('Non specificata', 'docmanager') . '</span>';
                }
                break;
        }
    }
    
    public function updateMessages($messages) {
        global $post;
        
        $messages['referto'] = array(
            0  => '',
            1  => __('Documento aggiornato.', 'docmanager'),
            2  => __('Campo personalizzato aggiornato.', 'docmanager'),
            3  => __('Campo personalizzato eliminato.', 'docmanager'),
            4  => __('Documento aggiornato.', 'docmanager'),
            5  => isset($_GET['revision']) ? sprintf(__('Documento ripristinato dalla revisione del %s', 'docmanager'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6  => __('Documento pubblicato.', 'docmanager'),
            7  => __('Documento salvato.', 'docmanager'),
            8  => __('Documento inviato.', 'docmanager'),
            9  => sprintf(__('Documento programmato per: <strong>%1$s</strong>.', 'docmanager'), date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date))),
            10 => __('Bozza documento aggiornata.', 'docmanager')
        );
        
        return $messages;
    }