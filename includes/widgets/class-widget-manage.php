<?php
/**
 * Widget Elementor per la gestione documenti
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Widget_Manage extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'docmanager_manage';
    }
    
    public function get_title() {
        return 'DocManager - Gestisci Referti';
    }
    
    public function get_icon() {
        return 'eicon-posts-grid';
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
                'default' => 'Gestisci Referti',
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
            'admin_only',
            [
                'label' => 'Solo Amministratori',
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
            'table_border_color',
            [
                'label' => 'Colore Bordi Tabella',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ddd',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-table' => 'border-color: {{VALUE}}',
                    '{{WRAPPER}} .docmanager-table td, {{WRAPPER}} .docmanager-table th' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'header_background',
            [
                'label' => 'Sfondo Header',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f9f9f9',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-table th' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (!is_user_logged_in()) {
            echo '<p>Devi essere loggato per gestire i documenti.</p>';
            return;
        }
        
        if ($settings['admin_only'] === 'yes' && !current_user_can('manage_options')) {
            echo '<p>Non hai i permessi per accedere a questa sezione.</p>';
            return;
        }
        
        ?>
        <div class="docmanager-manage-widget">
            <h3><?php echo esc_html($settings['title']); ?></h3>
            
            <?php if ($settings['show_search'] === 'yes'): ?>
            <div class="docmanager-search-bar">
                <input type="text" id="docmanager-search" placeholder="Cerca documenti...">
                <button type="button" id="docmanager-search-btn">Cerca</button>
                <button type="button" id="docmanager-reset-btn">Reset</button>
            </div>
            <?php endif; ?>
            
            <div class="docmanager-manage-container">
                <div class="docmanager-loading" style="display: none;">Caricamento...</div>
                
                <table class="docmanager-table" style="display: none;">
                    <thead>
                        <tr>
                            <th>Titolo</th>
                            <?php if (current_user_can('manage_options')): ?>
                            <th>Utente</th>
                            <?php endif; ?>
                            <th>Tipo</th>
                            <th>Dimensione</th>
                            <th>Data</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody id="docmanager-documents-list">
                    </tbody>
                </table>
                
                <div class="docmanager-pagination"></div>
                <div class="docmanager-no-results" style="display: none;">Nessun documento trovato.</div>
            </div>
        </div>
        
        <!-- Modal per modifica -->
        <div id="docmanager-edit-modal" class="docmanager-modal" style="display: none;">
            <div class="docmanager-modal-content">
                <span class="docmanager-close">&times;</span>
                <h3>Modifica Documento</h3>
                <form id="docmanager-edit-form">
                    <div class="form-group">
                        <label for="edit-title">Titolo</label>
                        <input type="text" id="edit-title" name="title" required>
                    </div>
                    
                    <?php if (current_user_can('manage_options')): ?>
                    <div class="form-group">
                        <label for="edit-user">Utente</label>
                        <?php 
                        wp_dropdown_users(array(
                            'name' => 'user_id',
                            'id' => 'edit-user'
                        )); 
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="edit-notes">Note</label>
                        <textarea id="edit-notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <input type="hidden" id="edit-doc-id" name="doc_id">
                    
                    <div class="form-group">
                        <button type="submit">Aggiorna</button>
                        <button type="button" class="docmanager-cancel">Annulla</button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var currentPage = 1;
            var itemsPerPage = <?php echo intval($settings['items_per_page']); ?>;
            var isSearching = false;
            
            function loadDocuments(page = 1, search = '') {
                $('.docmanager-loading').show();
                $('.docmanager-table').hide();
                $('.docmanager-no-results').hide();
                
                var data = {
                    action: 'docmanager_get_documents',
                    nonce: docmanager_ajax.nonce,
                    page: page
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
                        
                        if (response.success) {
                            displayDocuments(response.data.documents);
                            
                            if (!search) {
                                displayPagination(response.data.pages, response.data.current_page);
                            } else {
                                $('.docmanager-pagination').empty();
                            }
                        } else {
                            $('.docmanager-no-results').show();
                        }
                    },
                    error: function() {
                        $('.docmanager-loading').hide();
                        $('.docmanager-no-results').show();
                    }
                });
            }
            
            function displayDocuments(documents) {
                var html = '';
                
                if (documents.length === 0) {
                    $('.docmanager-no-results').show();
                    return;
                }
                
                documents.forEach(function(doc) {
                    html += '<tr>';
                    html += '<td><strong>' + doc.title + '</strong>';
                    if (doc.notes) {
                        html += '<br><small>' + doc.notes + '</small>';
                    }
                    html += '</td>';
                    
                    <?php if (current_user_can('manage_options')): ?>
                    html += '<td>' + (doc.user_name || 'N/A') + '</td>';
                    <?php endif; ?>
                    
                    html += '<td>' + doc.file_type + '</td>';
                    html += '<td>' + doc.file_size + '</td>';
                    html += '<td>' + doc.upload_date + '</td>';
                    html += '<td>';
                    html += '<a href="' + doc.download_url + '" class="btn-download">Download</a> ';
                    
                    <?php if (current_user_can('edit_posts')): ?>
                    html += '<button class="btn-edit" data-id="' + doc.id + '" data-title="' + doc.title + '" data-notes="' + (doc.notes || '') + '">Modifica</button> ';
                    html += '<button class="btn-delete" data-id="' + doc.id + '">Elimina</button>';
                    <?php endif; ?>
                    
                    html += '</td>';
                    html += '</tr>';
                });
                
                $('#docmanager-documents-list').html(html);
                $('.docmanager-table').show();
            }
            
            function displayPagination(totalPages, currentPage) {
                var html = '';
                
                if (totalPages <= 1) {
                    $('.docmanager-pagination').empty();
                    return;
                }
                
                html += '<div class="pagination-info">Pagina ' + currentPage + ' di ' + totalPages + '</div>';
                html += '<div class="pagination-buttons">';
                
                if (currentPage > 1) {
                    html += '<button class="page-btn" data-page="' + (currentPage - 1) + '">← Precedente</button>';
                }
                
                for (var i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
                    if (i === currentPage) {
                        html += '<button class="page-btn active" data-page="' + i + '">' + i + '</button>';
                    } else {
                        html += '<button class="page-btn" data-page="' + i + '">' + i + '</button>';
                    }
                }
                
                if (currentPage < totalPages) {
                    html += '<button class="page-btn" data-page="' + (currentPage + 1) + '">Successiva →</button>';
                }
                
                html += '</div>';
                $('.docmanager-pagination').html(html);
            }
            
            $(document).on('click', '.page-btn', function() {
                var page = $(this).data('page');
                currentPage = page;
                loadDocuments(page);
            });
            
            $('#docmanager-search-btn').on('click', function() {
                var searchTerm = $('#docmanager-search').val().trim();
                if (searchTerm.length >= 2) {
                    loadDocuments(1, searchTerm);
                }
            });
            
            $('#docmanager-reset-btn').on('click', function() {
                $('#docmanager-search').val('');
                currentPage = 1;
                loadDocuments(1);
            });
            
            $('#docmanager-search').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#docmanager-search-btn').click();
                }
            });
            
            $(document).on('click', '.btn-edit', function() {
                var id = $(this).data('id');
                var title = $(this).data('title');
                var notes = $(this).data('notes');
                
                $('#edit-doc-id').val(id);
                $('#edit-title').val(title);
                $('#edit-notes').val(notes);
                $('#docmanager-edit-modal').show();
            });
            
            $(document).on('click', '.btn-delete', function() {
                var id = $(this).data('id');
                
                if (confirm('Sei sicuro di voler eliminare questo documento?')) {
                    $.ajax({
                        url: docmanager_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'docmanager_delete',
                            nonce: docmanager_ajax.nonce,
                            doc_id: id
                        },
                        success: function(response) {
                            if (response.success) {
                                if (isSearching) {
                                    $('#docmanager-search-btn').click();
                                } else {
                                    loadDocuments(currentPage);
                                }
                            } else {
                                alert('Errore nell\'eliminazione del documento');
                            }
                        }
                    });
                }
            });
            
            $('.docmanager-close, .docmanager-cancel').on('click', function() {
                $('#docmanager-edit-modal').hide();
            });
            
            $('#docmanager-edit-form').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'docmanager_update',
                        nonce: docmanager_ajax.nonce,
                        doc_id: $('#edit-doc-id').val(),
                        title: $('#edit-title').val(),
                        notes: $('#edit-notes').val(),
                        user_id: $('#edit-user').val() || 0
                    },
                    success: function(response) {
                        $('#docmanager-edit-modal').hide();
                        if (response.success) {
                            if (isSearching) {
                                $('#docmanager-search-btn').click();
                            } else {
                                loadDocuments(currentPage);
                            }
                        } else {
                            alert('Errore nell\'aggiornamento del documento');
                        }
                    }
                });
            });
            
            loadDocuments(1);
        });
        </script>
        <?php
    }
}