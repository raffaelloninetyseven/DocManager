<?php
/**
 * Widget Elementor per visualizzare documenti dell'utente - Versione migliorata
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
		return ['docmanager'];
	}
    
    protected function _register_controls() {
        // Sezione Contenuto
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Contenuto',
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
        
        $this->add_control(
            'show_notes',
            [
                'label' => 'Mostra Note',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sì',
                'label_off' => 'No',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Layout
        $this->start_controls_section(
            'layout_section',
            [
                'label' => 'Layout',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'columns',
            [
                'label' => 'Colonne',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => '1 Colonna',
                    '2' => '2 Colonne',
                    '3' => '3 Colonne',
                    '4' => '4 Colonne',
                ],
            ]
        );
        
        $this->add_control(
            'card_spacing',
            [
                'label' => 'Spaziatura Card',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 50,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .docmanager-documents-grid' => 'gap: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Generale
        $this->start_controls_section(
            'general_style_section',
            [
                'label' => 'Stile Generale',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'title_color',
            [
                'label' => 'Colore Titolo Widget',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-view-widget h3' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => 'Tipografia Titolo',
                'selector' => '{{WRAPPER}} .docmanager-view-widget h3',
            ]
        );
        
        $this->add_control(
            'background_color',
            [
                'label' => 'Colore Sfondo Widget',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-view-widget' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'widget_padding',
            [
                'label' => 'Padding Widget',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .docmanager-view-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Barra di Ricerca
        $this->start_controls_section(
            'search_style_section',
            [
                'label' => 'Stile Barra Ricerca',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_search' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'search_input_background',
            [
                'label' => 'Sfondo Input Ricerca',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} #docmanager-user-search' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'search_input_border_color',
            [
                'label' => 'Colore Bordo Input',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#dddddd',
                'selectors' => [
                    '{{WRAPPER}} #docmanager-user-search' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'search_reset_color',
            [
                'label' => 'Colore Icona Reset',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#999999',
                'selectors' => [
                    '{{WRAPPER}} .search-reset-btn' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'search_reset_hover_color',
            [
                'label' => 'Colore Icona Reset (Hover)',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .search-reset-btn:hover' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Card
        $this->start_controls_section(
            'card_style_section',
            [
                'label' => 'Stile Card Documenti',
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
            'card_border_width',
            [
                'label' => 'Spessore Bordo Card',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 10,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 1,
                ],
                'selectors' => [
                    '{{WRAPPER}} .document-card' => 'border-width: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->add_control(
            'card_border_radius',
            [
                'label' => 'Raggio Bordo Card',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .document-card' => 'border-radius: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_box_shadow',
                'label' => 'Ombra Card',
                'selector' => '{{WRAPPER}} .document-card',
            ]
        );
        
        $this->add_responsive_control(
            'card_padding',
            [
                'label' => 'Padding Card',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default' => [
                    'top' => 20,
                    'right' => 20,
                    'bottom' => 20,
                    'left' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .document-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Titolo Documento
        $this->start_controls_section(
            'document_title_style_section',
            [
                'label' => 'Stile Titolo Documento',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'document_title_color',
            [
                'label' => 'Colore Titolo Documento',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .document-title' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'document_title_typography',
                'label' => 'Tipografia Titolo Documento',
                'selector' => '{{WRAPPER}} .document-title',
            ]
        );
        
        $this->add_control(
            'document_type_background',
            [
                'label' => 'Sfondo Badge Tipo File',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .document-type' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'document_type_color',
            [
                'label' => 'Colore Testo Badge Tipo File',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .document-type' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Note e Meta
        $this->start_controls_section(
            'meta_style_section',
            [
                'label' => 'Stile Note e Metadati',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'notes_color',
            [
                'label' => 'Colore Note',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .document-notes' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'show_notes' => 'yes',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'notes_typography',
                'label' => 'Tipografia Note',
                'selector' => '{{WRAPPER}} .document-notes',
                'condition' => [
                    'show_notes' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'meta_color',
            [
                'label' => 'Colore Metadati',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#888888',
                'selectors' => [
                    '{{WRAPPER}} .document-size, {{WRAPPER}} .document-date' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'meta_typography',
                'label' => 'Tipografia Metadati',
                'selector' => '{{WRAPPER}} .document-size, {{WRAPPER}} .document-date',
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Pulsante Download
        $this->start_controls_section(
            'download_button_style_section',
            [
                'label' => 'Stile Pulsante Download',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->start_controls_tabs('download_button_style_tabs');

		$this->start_controls_tab(
			'download_button_normal_tab',
			[
				'label' => 'Normal',
			]
		);

		$this->add_control(
			'download_button_background',
			[
				'label' => 'Sfondo Pulsante',
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#0073aa',
				'selectors' => [
					'{{WRAPPER}} .download-btn' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'download_button_color',
			[
				'label' => 'Colore Testo Pulsante',
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .download-btn' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'download_button_hover_tab',
			[
				'label' => 'Hover',
			]
		);

		$this->add_control(
			'download_button_hover_background',
			[
				'label' => 'Sfondo Pulsante',
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#005a87',
				'selectors' => [
					'{{WRAPPER}} .download-btn:hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'download_button_hover_color',
			[
				'label' => 'Colore Testo Pulsante',
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .download-btn:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();
		
		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name' => 'download_button_border',
				'label' => 'Bordo Pulsante',
				'selector' => '{{WRAPPER}} .download-btn',
			]
		);
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'download_button_typography',
                'label' => 'Tipografia Pulsante',
                'selector' => '{{WRAPPER}} .download-btn',
            ]
        );
        
        $this->add_responsive_control(
            'download_button_padding',
            [
                'label' => 'Padding Pulsante',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default' => [
                    'top' => 10,
                    'right' => 20,
                    'bottom' => 10,
                    'left' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .download-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
		
		$this->add_responsive_control(
			'download_button_border_radius',
			[
				'label' => 'Raggio Bordo Pulsante',
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'default' => [
					'top' => 5,
					'right' => 5,
					'bottom' => 5,
					'left' => 5,
					'unit' => 'px',
					'isLinked' => true,
				],
				'selectors' => [
					'{{WRAPPER}} .download-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
        
        $this->end_controls_section();
        
        // Sezione Stile Paginazione
        $this->start_controls_section(
            'pagination_style_section',
            [
                'label' => 'Stile Paginazione',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'pagination_button_background',
            [
                'label' => 'Sfondo Pulsanti Paginazione',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .user-page-btn' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'pagination_button_color',
            [
                'label' => 'Colore Testo Paginazione',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .user-page-btn' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'pagination_button_active_background',
            [
                'label' => 'Sfondo Pulsante Attivo',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .user-page-btn.active' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'pagination_button_active_color',
            [
                'label' => 'Colore Testo Pulsante Attivo',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .user-page-btn.active' => 'color: {{VALUE}}',
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
        
        $columns_class = 'grid-cols-' . $settings['columns'];
        ?>
        
        <style>
        .grid-cols-1 { grid-template-columns: 1fr; }
        .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-cols-4 { grid-template-columns: repeat(4, 1fr); }
        
        .search-container {
            position: relative;
            display: inline-block;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .search-container input {
            width: 100%;
            padding-right: 35px;
        }
        
        .search-reset-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            padding: 0;
            display: none;
        }
        
        .search-reset-btn.show {
            display: block;
        }
        
        @media (max-width: 768px) {
            .grid-cols-2, .grid-cols-3, .grid-cols-4 { 
                grid-template-columns: 1fr; 
            }
        }
        
        @media (min-width: 769px) and (max-width: 1024px) {
            .grid-cols-3, .grid-cols-4 { 
                grid-template-columns: repeat(2, 1fr); 
            }
        }
        </style>
        
        <div class="docmanager-view-widget">
            <?php if (!empty($settings['title'])): ?>
            <h3><?php echo esc_html($settings['title']); ?></h3>
            <?php endif; ?>
            
            <?php if ($settings['show_search'] === 'yes'): ?>
            <div class="search-container">
                <input type="text" id="docmanager-user-search" placeholder="Cerca nei tuoi documenti...">
                <button type="button" class="search-reset-btn" id="docmanager-user-reset-btn">✕</button>
            </div>
            <?php endif; ?>
            
            <div class="docmanager-view-container">
                <div class="docmanager-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p>Caricamento documenti...</p>
                </div>
                
                <div class="docmanager-documents-grid <?php echo esc_attr($columns_class); ?>" id="docmanager-user-documents"></div>
                
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
            var showNotes = <?php echo $settings['show_notes'] === 'yes' ? 'true' : 'false'; ?>;
            var showFileInfo = <?php echo $settings['show_file_info'] === 'yes' ? 'true' : 'false'; ?>;
            var showUploadDate = <?php echo $settings['show_upload_date'] === 'yes' ? 'true' : 'false'; ?>;
            
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
                    
                    if (showNotes && doc.notes) {
                        html += '<div class="document-notes">' + doc.notes + '</div>';
                    }
                    
                    html += '<div class="document-meta">';
                    
                    if (showFileInfo) {
                        html += '<div class="document-size">📊 ' + doc.file_size + '</div>';
                    }
                    
                    if (showUploadDate) {
                        html += '<div class="document-date">📅 ' + doc.upload_date + '</div>';
                    }
                    
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
            
            $('#docmanager-user-search').on('input', function() {
                var searchTerm = $(this).val().trim();
                var $resetBtn = $('#docmanager-user-reset-btn');
                
                if (searchTerm.length > 0) {
                    $resetBtn.addClass('show');
                    
                    if (searchTerm.length >= 2) {
                        currentPage = 1;
                        clearTimeout(window.searchTimeout);
                        window.searchTimeout = setTimeout(function() {
                            loadUserDocuments(1, searchTerm);
                        }, 500);
                    }
                } else {
                    $resetBtn.removeClass('show');
                    currentPage = 1;
                    isSearching = false;
                    clearTimeout(window.searchTimeout);
                    loadUserDocuments(1);
                }
            });
            
            $('#docmanager-user-reset-btn').on('click', function() {
                $('#docmanager-user-search').val('').trigger('input');
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