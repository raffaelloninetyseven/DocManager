<?php
/**
 * Widget Elementor per gestire i documenti (Admin/Staff)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Widget_Manage_Documents extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'docmanager-manage-documents';
    }
    
    public function get_title() {
        return __('Gestisci Documenti', 'docmanager');
    }
    
    public function get_icon() {
        return 'fa fa-cogs';
    }
    
    public function get_categories() {
        return array('docmanager');
    }
    
    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Contenuto', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'widget_title',
            array(
                'label' => __('Titolo Widget', 'docmanager'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Gestione Documenti', 'docmanager'),
                'placeholder' => __('Inserisci il titolo del widget', 'docmanager'),
            )
        );
        
        $this->add_control(
            'display_mode',
            array(
                'label' => __('Modalità Visualizzazione', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'accordion',
                'options' => array(
                    'accordion' => __('Accordion per Utente', 'docmanager'),
                    'table' => __('Tabella Completa', 'docmanager'),
                    'cards' => __('Cards', 'docmanager'),
                ),
            )
        );
        
        $this->add_control(
            'show_user_filter',
            array(
                'label' => __('Mostra Filtro Utente', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sì', 'docmanager'),
                'label_off' => __('No', 'docmanager'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_category_filter',
            array(
                'label' => __('Mostra Filtro Categoria', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sì', 'docmanager'),
                'label_off' => __('No', 'docmanager'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_status_filter',
            array(
                'label' => __('Mostra Filtro Stato', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sì', 'docmanager'),
                'label_off' => __('No', 'docmanager'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'documents_per_page',
            array(
                'label' => __('Documenti per Pagina', 'docmanager'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 20,
                'min' => 5,
                'max' => 100,
                'step' => 5,
            )
        );
        
        $this->add_control(
            'allowed_roles',
            array(
                'label' => __('Ruoli Autorizzati', 'docmanager'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->getUserRoles(),
                'default' => array('administrator'),
                'description' => __('Seleziona i ruoli che possono gestire i documenti', 'docmanager'),
            )
        );
        
        $this->end_controls_section();
        
        // Sezione Style
        $this->start_controls_section(
            'style_section',
            array(
                'label' => __('Stile', 'docmanager'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'header_bg_color',
            array(
                'label' => __('Colore Sfondo Header', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-manage-header' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'button_color',
            array(
                'label' => __('Colore Pulsanti', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-btn' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'danger_button_color',
            array(
                'label' => __('Colore Pulsanti Elimina', 'docmanager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#dc3545',
                'selectors' => array(
                    '{{WRAPPER}} .docmanager-btn-danger' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    private function getUserRoles() {
        $roles = wp_roles()->roles;
        $role_options = array();
        
        foreach ($roles as $role_key => $role) {
            $role_options[$role_key] = $role['name'];
        }
        
        return $role_options;
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Verificare se l'utente è loggato
        if (!is_user_logged_in()) {
            echo '<div class="docmanager-notice">' . __('Devi effettuare il login per gestire i documenti.', 'docmanager') . '</div>';
            return;
        }
        
        // Verificare i permessi
        $user = wp_get_current_user();
        $allowed_roles = $settings['allowed_roles'];
        
        if (!current_user_can('manage_options') && !array_intersect($user->roles, $allowed_roles)) {
            echo '<div class="docmanager-notice">' . __('Non hai i permessi per gestire i documenti.', 'docmanager') . '</div>';
            return;
        }
        
        // Gestire le azioni AJAX
        if (isset($_POST['action']) && $_POST['action'] === 'docmanager_action') {
            $this->handleAction();
        }
        
        $this->renderManageInterface($settings);
    }
    
    private function handleAction() {
        // Verificare nonce
        if (!isset($_POST['docmanager_manage_nonce']) || !wp_verify_nonce($_POST['docmanager_manage_nonce'], 'docmanager_manage')) {
            echo '<div class="docmanager-error">' . __('Errore di sicurezza', 'docmanager') . '</div>';
            return;
        }
        
        $action_type = sanitize_text_field($_POST['action_type']);
        $document_id = intval($_POST['document_id']);
        
        switch ($action_type) {
            case 'delete':
                $this->deleteDocument($document_id);
                break;
            case 'toggle_status':
                $this->toggleDocumentStatus($document_id);
                break;
            case 'reassign':
                $this->reassignDocument($document_id);
                break;
        }
    }
    
    private function deleteDocument($document_id) {
        $file_handler = new DocManager_FileHandler();
        $file_handler->deleteFile($document_id);
        
        $result = wp_delete_post($document_id, true);
        
        if ($result) {
            echo '<div class="docmanager-success">' . __('Documento eliminato con successo', 'docmanager') . '</div>';
        } else {
            echo '<div class="docmanager-error">' . __('Errore durante l\'eliminazione', 'docmanager') . '</div>';
        }
    }
    
    private function toggleDocumentStatus($document_id) {
        $current_status = get_post_status($document_id);
        $new_status = ($current_status === 'publish') ? 'draft' : 'publish';
        
        $result = wp_update_post(array(
            'ID' => $document_id,
            'post_status' => $new_status
        ));
        
        if ($result) {
            echo '<div class="docmanager-success">' . __('Stato documento aggiornato', 'docmanager') . '</div>';
        } else {
            echo '<div class="docmanager-error">' . __('Errore durante l\'aggiornamento', 'docmanager') . '</div>';
        }
    }
    
    private function reassignDocument($document_id) {
        $new_user = intval($_POST['new_user_id']);
        
        if ($new_user > 0) {
            update_post_meta($document_id, '_docmanager_assigned_user', $new_user);
            echo '<div class="docmanager-success">' . __('Documento riassegnato con successo', 'docmanager') . '</div>';
        } else {
            echo '<div class="docmanager-error">' . __('Utente non valido', 'docmanager') . '</div>';
        }
    }
    
    private function renderManageInterface($settings) {
        $display_mode = $settings['display_mode'];
        $per_page = $settings['documents_per_page'];
        
        // Ottenere parametri filtro
        $filter_user = isset($_GET['filter_user']) ? intval($_GET['filter_user']) : 0;
        $filter_category = isset($_GET['filter_category']) ? sanitize_text_field($_GET['filter_category']) : '';
        $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        
        ?>
        <div class="docmanager-manage-widget">
            <?php if (!empty($settings['widget_title'])): ?>
                <div class="docmanager-manage-header">
                    <h3><?php echo esc_html($settings['widget_title']); ?></h3>
                </div>
            <?php endif; ?>
            
            <?php $this->renderFilters($settings, $filter_user, $filter_category, $filter_status); ?>
            
            <div class="docmanager-manage-content">
                <?php
                if ($display_mode === 'accordion') {
                    $this->renderAccordionMode($per_page, $current_page, $filter_user, $filter_category, $filter_status);
                } elseif ($display_mode === 'table') {
                    $this->renderTableMode($per_page, $current_page, $filter_user, $filter_category, $filter_status);
                } else {
                    $this->renderCardsMode($per_page, $current_page, $filter_user, $filter_category, $filter_status);
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    private function renderFilters($settings, $filter_user, $filter_category, $filter_status) {
        ?>
        <div class="docmanager-filters">
            <form method="get">
                <?php
                // Preservare altri parametri URL
                foreach ($_GET as $key => $value) {
                    if (!in_array($key, array('filter_user', 'filter_category', 'filter_status', 'paged'))) {
                        echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                    }
                }
                ?>
                
                <?php if ($settings['show_user_filter'] === 'yes'): ?>
                    <div class="docmanager-filter">
                        <label for="filter_user"><?php _e('Filtra per Utente', 'docmanager'); ?></label>
                        <select name="filter_user" id="filter_user">
                            <option value=""><?php _e('Tutti gli utenti', 'docmanager'); ?></option>
                            <?php
                            $users = get_users(array('orderby' => 'display_name'));
                            foreach ($users as $user) {
                                echo '<option value="' . $user->ID . '"' . selected($filter_user, $user->ID, false) . '>';
                                echo esc_html($user->display_name);
                                echo '</option>';
                            }
                            ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <?php if ($settings['show_category_filter'] === 'yes'): ?>
                    <div class="docmanager-filter">
                        <label for="filter_category"><?php _e('Filtra per Categoria', 'docmanager'); ?></label>
                        <select name="filter_category" id="filter_category">
                            <option value=""><?php _e('Tutte le categorie', 'docmanager'); ?></option>
                            <?php
                            $categories = get_terms(array('taxonomy' => 'doc_category', 'hide_empty' => false));
                            foreach ($categories as $category) {
                                if (is_object($category)) {
                                    echo '<option value="' . $category->slug . '"' . selected($filter_category, $category->slug, false) . '>';
                                    echo esc_html($category->name);
                                    echo '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <?php if ($settings['show_status_filter'] === 'yes'): ?>
                    <div class="docmanager-filter">
                        <label for="filter_status"><?php _e('Filtra per Stato', 'docmanager'); ?></label>
                        <select name="filter_status" id="filter_status">
                            <option value=""><?php _e('Tutti gli stati', 'docmanager'); ?></option>
                            <option value="publish"<?php selected($filter_status, 'publish'); ?>><?php _e('Pubblicato', 'docmanager'); ?></option>
                            <option value="draft"<?php selected($filter_status, 'draft'); ?>><?php _e('Bozza', 'docmanager'); ?></option>
                            <option value="private"<?php selected($filter_status, 'private'); ?>><?php _e('Privato', 'docmanager'); ?></option>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="docmanager-filter">
                    <button type="submit" class="docmanager-btn"><?php _e('Filtra', 'docmanager'); ?></button>
                    <a href="<?php echo remove_query_arg(array('filter_user', 'filter_category', 'filter_status', 'paged')); ?>" class="docmanager-btn docmanager-btn-secondary"><?php _e('Reset', 'docmanager'); ?></a>
                </div>
            </form>
        </div>
        <?php
    }
    
    private function getDocuments($per_page, $current_page, $filter_user, $filter_category, $filter_status) {
        $args = array(
            'post_type' => 'referto',
            'posts_per_page' => $per_page,
            'paged' => $current_page,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => array('publish', 'draft', 'private'),
        );
        
        // Filtro per utente
        if ($filter_user) {
            $args['meta_query'] = array(
                array(
                    'key' => '_docmanager_assigned_user',
                    'value' => $filter_user,
                    'compare' => '='
                )
            );
        }
        
        // Filtro per categoria
        if ($filter_category) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'doc_category',
                    'field' => 'slug',
                    'terms' => $filter_category,
                )
            );
        }
        
        // Filtro per stato
        if ($filter_status) {
            $args['post_status'] = $filter_status;
        }
        
        return new WP_Query($args);
    }
    
    private function renderAccordionMode($per_page, $current_page, $filter_user, $filter_category, $filter_status) {
        // Raggruppare documenti per utente
        $users_with_docs = array();
        
        if ($filter_user) {
            $users_with_docs[] = get_userdata($filter_user);
        } else {
            $users = get_users(array('orderby' => 'display_name'));
            foreach ($users as $user) {
                $docs = get_posts(array(
                    'post_type' => 'referto',
                    'meta_key' => '_docmanager_assigned_user',
                    'meta_value' => $user->ID,
                    'posts_per_page' => 1,
                    'post_status' => array('publish', 'draft', 'private'),
                ));
                
                if (!empty($docs)) {
                    $users_with_docs[] = $user;
                }
            }
        }
        
        echo '<div class="docmanager-accordion">';
        
        foreach ($users_with_docs as $user) {
            $user_docs = get_posts(array(
                'post_type' => 'referto',
                'meta_key' => '_docmanager_assigned_user',
                'meta_value' => $user->ID,
                'posts_per_page' => -1,
                'post_status' => array('publish', 'draft', 'private'),
                'orderby' => 'date',
                'order' => 'DESC',
            ));
            
            if (!empty($user_docs)) {
                echo '<div class="docmanager-accordion-item">';
                echo '<div class="docmanager-accordion-header">';
                echo '<h4>' . esc_html($user->display_name) . ' <span class="docmanager-count">(' . count($user_docs) . ')</span></h4>';
                echo '</div>';
                echo '<div class="docmanager-accordion-content">';
                
                foreach ($user_docs as $doc) {
                    $this->renderDocumentItem($doc);
                }
                
                echo '</div>';
                echo '</div>';
            }
        }
        
        echo '</div>';
    }
    
    private function renderTableMode($per_page, $current_page, $filter_user, $filter_category, $filter_status) {
        $query = $this->getDocuments($per_page, $current_page, $filter_user, $filter_category, $filter_status);
        
        ?>
        <div class="docmanager-table-wrapper">
            <table class="docmanager-table">
                <thead>
                    <tr>
                        <th><?php _e('Documento', 'docmanager'); ?></th>
                        <th><?php _e('Utente', 'docmanager'); ?></th>
                        <th><?php _e('Categoria', 'docmanager'); ?></th>
                        <th><?php _e('Stato', 'docmanager'); ?></th>
                        <th><?php _e('Data', 'docmanager'); ?></th>
                        <th><?php _e('Azioni', 'docmanager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query->have_posts()): ?>
                        <?php while ($query->have_posts()): $query->the_post(); ?>
                            <tr>
                                <td><?php the_title(); ?></td>
                                <td>
                                    <?php
                                    $user_id = get_post_meta(get_the_ID(), '_docmanager_assigned_user', true);
                                    $user = get_userdata($user_id);
                                    echo $user ? esc_html($user->display_name) : __('Non assegnato', 'docmanager');
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $categories = get_the_terms(get_the_ID(), 'doc_category');
                                    if ($categories) {
                                        $cat_names = array();
                                        foreach ($categories as $cat) {
                                            if (is_object($cat)) {
                                                $cat_names[] = $cat->name;
                                            }
                                        }
                                        echo implode(', ', $cat_names);
                                    } else {
                                        echo __('Nessuna', 'docmanager');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="docmanager-status docmanager-status-<?php echo get_post_status(); ?>">
                                        <?php
                                        $status = get_post_status();
                                        echo $status === 'publish' ? __('Pubblicato', 'docmanager') : 
                                            ($status === 'draft' ? __('Bozza', 'docmanager') : __('Privato', 'docmanager'));
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo get_the_date(); ?></td>
                                <td>
                                    <?php $this->renderDocumentActions(get_the_ID()); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6"><?php _e('Nessun documento trovato', 'docmanager'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php
        // Paginazione
        $total_pages = $query->max_num_pages;
        if ($total_pages > 1) {
            echo '<div class="docmanager-pagination">';
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo; Precedente'),
                'next_text' => __('Successivo &raquo;'),
                'total' => $total_pages,
                'current' => $current_page
            ));
            echo '</div>';
        }
        
        wp_reset_postdata();
    }
    
    private function renderCardsMode($per_page, $current_page, $filter_user, $filter_category, $filter_status) {
        $query = $this->getDocuments($per_page, $current_page, $filter_user, $filter_category, $filter_status);
        
        echo '<div class="docmanager-cards-grid">';
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $this->renderDocumentCard(get_the_ID());
            }
        } else {
            echo '<div class="docmanager-no-results">' . __('Nessun documento trovato', 'docmanager') . '</div>';
        }
        
        echo '</div>';
        
        wp_reset_postdata();
    }
    
    private function renderDocumentItem($document) {
        $file_handler = new DocManager_FileHandler();
        $file_info = $file_handler->getFileInfo($document->ID);
        
        ?>
        <div class="docmanager-document-item">
            <div class="docmanager-document-info">
                <h5><?php echo esc_html($document->post_title); ?></h5>
                <div class="docmanager-document-meta">
                    <span><?php echo get_the_date('', $document); ?></span>
                    <?php if ($file_info): ?>
                        <span><?php echo size_format($file_info['size']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="docmanager-document-actions">
                <?php $this->renderDocumentActions($document->ID); ?>
            </div>
        </div>
        <?php
    }
    
    private function renderDocumentCard($document_id) {
        $document = get_post($document_id);
        $file_handler = new DocManager_FileHandler();
        $file_info = $file_handler->getFileInfo($document_id);
        $user_id = get_post_meta($document_id, '_docmanager_assigned_user', true);
        $user = get_userdata($user_id);
        
        ?>
        <div class="docmanager-document-card">
            <div class="docmanager-card-header">
                <h4><?php echo esc_html($document->post_title); ?></h4>
                <span class="docmanager-status docmanager-status-<?php echo $document->post_status; ?>">
                    <?php
                    $status = $document->post_status;
                    echo $status === 'publish' ? __('Pubblicato', 'docmanager') : 
                        ($status === 'draft' ? __('Bozza', 'docmanager') : __('Privato', 'docmanager'));
                    ?>
                </span>
            </div>
            
            <div class="docmanager-card-body">
                <div class="docmanager-card-meta">
                    <p><strong><?php _e('Utente:', 'docmanager'); ?></strong> <?php echo $user ? esc_html($user->display_name) : __('Non assegnato', 'docmanager'); ?></p>
                    <p><strong><?php _e('Data:', 'docmanager'); ?></strong> <?php echo get_the_date('', $document); ?></p>
                    <?php if ($file_info): ?>
                        <p><strong><?php _e('Dimensione:', 'docmanager'); ?></strong> <?php echo size_format($file_info['size']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="docmanager-card-actions">
                <?php $this->renderDocumentActions($document_id); ?>
            </div>
        </div>
        <?php
    }
    
    private function renderDocumentActions($document_id) {
        $file_handler = new DocManager_FileHandler();
        $download_url = $file_handler->getDownloadUrl($document_id);
        $view_url = $file_handler->getViewUrl($document_id);
        $is_viewable = $file_handler->isViewable($document_id);
        
        ?>
        <div class="docmanager-actions">
            <?php if ($download_url): ?>
                <a href="<?php echo esc_url($download_url); ?>" class="docmanager-btn docmanager-btn-sm" target="_blank">
                    <i class="fa fa-download"></i> <?php _e('Scarica', 'docmanager'); ?>
                </a>
            <?php endif; ?>
            
            <?php if ($is_viewable && $view_url): ?>
                <a href="<?php echo esc_url($view_url); ?>" class="docmanager-btn docmanager-btn-sm" target="_blank">
                    <i class="fa fa-eye"></i> <?php _e('Visualizza', 'docmanager'); ?>
                </a>
            <?php endif; ?>
            
            <a href="<?php echo admin_url('post.php?post=' . $document_id . '&action=edit'); ?>" class="docmanager-btn docmanager-btn-sm">
                <i class="fa fa-edit"></i> <?php _e('Modifica', 'docmanager'); ?>
            </a>
            
            <button type="button" class="docmanager-btn docmanager-btn-sm docmanager-btn-danger" onclick="docmanagerDeleteDocument(<?php echo $document_id; ?>)">
                <i class="fa fa-trash"></i> <?php _e('Elimina', 'docmanager'); ?>
            </button>
        </div>
        
        <script>
        function docmanagerDeleteDocument(documentId) {
            if (confirm('<?php _e('Sei sicuro di voler eliminare questo documento?', 'docmanager'); ?>')) {
                // Implementare chiamata AJAX per eliminazione
                var form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = '<input type="hidden" name="action" value="docmanager_action">' +
                               '<input type="hidden" name="action_type" value="delete">' +
                               '<input type="hidden" name="document_id" value="' + documentId + '">' +
                               '<input type="hidden" name="docmanager_manage_nonce" value="<?php echo wp_create_nonce('docmanager_manage'); ?>">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        </script>
        <?php
    }
}