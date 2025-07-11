<?php
/**
 * Widget Elementor per visualizzare documenti dell'utente
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Widget_View extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'docmanager_view';
    }
    
    public function get_title() {
        return 'DocManager - Visualizza Referti';
    }
    
    public function get_icon() {
        return 'eicon-document-file';
    }
    
    public function get_categories() {
        return ['general'];
    }
    
    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Impostazioni',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'title',
            [
                'label' => 'Titolo',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'I Miei Referti',
            ]
        );
        
        $this->add_control(
            'show_search',
            [
                'label' => 'Mostra Ricerca',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sì',
                'label_off' => 'No',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'items_per_page',
            [
                'label' => 'Elementi per Pagina',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 10,
                'min' => 5,
                'max' => 50,
            ]
        );
        
        $this->add_control(
            'show_upload_date',
            [
                'label' => 'Mostra Data Caricamento',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sì',
                'label_off' => 'No',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_file_info',
            [
                'label' => 'Mostra Info File',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sì',
                'label_off' => 'No',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        $this->start_controls_section(
            'style_section',
            [
                'label' => 'Stile',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'card_background',
            [
                'label' => 'Sfondo Card',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .document-card' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'card_border_color',
            [
                'label' => 'Colore Bordo Card',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#e1e1e1',
                'selectors' => [
                    '{{WRAPPER}} .document-card' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'download_button_color',
            [
                'label' => 'Colore Pulsante Download',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .download-btn' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (!is_user_logged_in()) {
            echo '<p>Devi essere loggato per visualizzare i tuoi documenti.</p>';
            return;
        }
        
        ?>
        <div class="docmanager-view-widget">
            <h3><?php echo esc_html($settings['title']); ?></h3>
            
            <?php if ($settings['show_search'] === 'yes'): ?>
            <div class="docmanager-search-bar">
                <input type="text" id="docmanager-user-search" placeholder="Cerca nei tuoi documenti...">
                <button type="button" id="docmanager-user-search-btn">Cerca</button>
                <button type="button" id="docmanager-user-reset-btn">Reset</button>
            </div>
            <?php endif; ?>
            
            <div class="docmanager-view-container">
                <div class="docmanager-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p>Caricamento documenti...</p>
                </div>
                
                <div class="docmanager-documents-grid" id="docmanager-user-documents"></div>
                
                <div class="docmanager-pagination" id="docmanager-user-pagination"></div>
                
                <div class="docmanager-no-documents" style="display: none;">
                    <div class="no-docs-icon">📄</div>
                    <h4>Nessun documento trovato</h4>
                    <p>Non hai ancora documenti caricati o nessun documento corrisponde alla tua ricerca.</p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var currentPage = 1;
            var itemsPerPage = <?php echo intval($settings['items_per_page']); ?>;
            var isSearching = false;
            var currentUserId = <?php echo get_current_user_id(); ?>;
            
            function loadUserDocuments(page = 1, search = '') {
                $('.docmanager-loading').show();
                $('.docmanager-documents-grid').hide();
                $('.docmanager-no-documents').hide();
                $('.docmanager-pagination').hide();
                
                var data = {
                    action: 'docmanager_get_documents',
                    nonce: docmanager_ajax.nonce,
                    page: page,
                    user_id: currentUserId
                };
                
                if (search) {
                    data.action = 'docmanager_search';
                    data.search = search;
                    isSearching = true;
                } else {
                    isSearching = false;
                }
                
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        $('.docmanager-loading').hide();
                        
                        if (response.success && response.data.documents.length > 0) {
                            displayUserDocuments(response.data.documents);
                            
                            if (!search && response.data.pages > 1) {
                                displayUserPagination(response.data.pages, response.data.current_page);
                            }
                        } else {
                            $('.docmanager-no-documents').show();
                        }
                    },
                    error: function() {
                        $('.docmanager-loading').hide();
                        $('.docmanager-no-documents').show();
                    }
                });
            }
            
            function displayUserDocuments(documents) {
                var html = '';
                
                documents.forEach(function(doc) {
                    html += '<div class="document-card">';
                    html += '<div class="document-header">';
                    html += '<h4 class="document-title">' + doc.title + '</h4>';
                    html += '<span class="document-type">' + doc.file_type + '</span>';
                    html += '</div>';
                    
                    if (doc.notes) {
                        html += '<div class="document-notes">' + doc.notes + '</div>';
                    }
                    
                    html += '<div class="document-meta">';
                    
                    <?php if ($settings['show_file_info'] === 'yes'): ?>
                    html += '<div class="document-size">📊 ' + doc.file_size + '</div>';
                    <?php endif; ?>
                    
                    <?php if ($settings['show_upload_date'] === 'yes'): ?>
                    html += '<div class="document-date">📅 ' + doc.upload_date + '</div>';
                    <?php endif; ?>
                    
                    html += '</div>';
                    
                    html += '<div class="document-actions">';
                    html += '<a href="' + doc.download_url + '" class="download-btn" target="_blank">';
                    html += '⬇️ Scarica Documento';
                    html += '</a>';
                    html += '</div>';
                    
                    html += '</div>';
                });
                
                $('#docmanager-user-documents').html(html).show();
            }
            
            function displayUserPagination(totalPages, currentPage) {
                var html = '';
                
                if (totalPages <= 1) {
                    return;
                }
                
                html += '<div class="pagination-wrapper">';
                html += '<div class="pagination-info">Pagina ' + currentPage + ' di ' + totalPages + '</div>';
                html += '<div class="pagination-buttons">';
                
                if (currentPage > 1) {
                    html += '<button class="user-page-btn" data-page="1" title="Prima pagina">««</button>';
                    html += '<button class="user-page-btn" data-page="' + (currentPage - 1) + '" title="Pagina precedente">‹</button>';
                }
                
                var startPage = Math.max(1, currentPage - 2);
                var endPage = Math.min(totalPages, currentPage + 2);
                
                for (var i = startPage; i <= endPage; i++) {
                    if (i === currentPage) {
                        html += '<button class="user-page-btn active" data-page="' + i + '">' + i + '</button>';
                    } else {
                        html += '<button class="user-page-btn" data-page="' + i + '">' + i + '</button>';
                    }
                }
                
                if (currentPage < totalPages) {
                    html += '<button class="user-page-btn" data-page="' + (currentPage + 1) + '" title="Pagina successiva">›</button>';
                    html += '<button class="user-page-btn" data-page="' + totalPages + '" title="Ultima pagina">»»</button>';
                }
                
                html += '</div>';
                html += '</div>';
                
                $('#docmanager-user-pagination').html(html).show();
            }
            
            $(document).on('click', '.user-page-btn', function() {
                var page = parseInt($(this).data('page'));
                currentPage = page;
                loadUserDocuments(page);
            });
            
            $('#docmanager-user-search-btn').on('click', function() {
                var searchTerm = $('#docmanager-user-search').val().trim();
                if (searchTerm.length >= 2) {
                    currentPage = 1;
                    loadUserDocuments(1, searchTerm);
                } else {
                    alert('Inserisci almeno 2 caratteri per la ricerca');
                }
            });
            
            $('#docmanager-user-reset-btn').on('click', function() {
                $('#docmanager-user-search').val('');
                currentPage = 1;
                isSearching = false;
                loadUserDocuments(1);
            });
            
            $('#docmanager-user-search').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#docmanager-user-search-btn').click();
                }
            });
            
            $(document).on('click', '.download-btn', function(e) {
                var $btn = $(this);
                var originalText = $btn.text();
                
                $btn.text('⏳ Download in corso...');
                
                setTimeout(function() {
                    $btn.text(originalText);
                }, 2000);
            });
            
            loadUserDocuments(1);
        });
        </script>
        <?php
    }
}