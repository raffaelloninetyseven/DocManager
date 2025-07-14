<?php
/**
 * Widget Elementor per la gestione documenti - Versione migliorata
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
                'default' => 'Gestisci Referti',
            ]
        );
        
        $this->add_control(
            'subtitle',
            [
                'label' => 'Sottotitolo',
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => 'Gestisci, modifica ed elimina i documenti caricati',
                'rows' => 2,
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
                'description' => 'Se attivo, solo gli amministratori possono vedere tutti i documenti',
            ]
        );
        
        $this->add_control(
            'show_user_column',
            [
                'label' => 'Mostra Colonna Utente',
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
            'view_mode',
            [
                'label' => 'Modalità Visualizzazione',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'table',
                'options' => [
                    'table' => 'Tabella',
                    'cards' => 'Cards',
                    'list' => 'Lista',
                ],
            ]
        );
        
        $this->add_control(
            'table_striped',
            [
                'label' => 'Tabella a Righe Alternate',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sì',
                'label_off' => 'No',
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'view_mode' => 'table',
                ],
            ]
        );
        
        $this->add_control(
            'cards_columns',
            [
                'label' => 'Colonne Cards',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => '1 Colonna',
                    '2' => '2 Colonne',
                    '3' => '3 Colonne',
                    '4' => '4 Colonne',
                ],
                'condition' => [
                    'view_mode' => 'cards',
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
                'label' => 'Colore Titolo',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-manage-widget h3' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => 'Tipografia Titolo',
                'selector' => '{{WRAPPER}} .docmanager-manage-widget h3',
            ]
        );
        
        $this->add_control(
            'subtitle_color',
            [
                'label' => 'Colore Sottotitolo',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .manage-subtitle' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'subtitle!' => '',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'subtitle_typography',
                'label' => 'Tipografia Sottotitolo',
                'selector' => '{{WRAPPER}} .manage-subtitle',
                'condition' => [
                    'subtitle!' => '',
                ],
            ]
        );
        
        $this->add_control(
            'widget_background',
            [
                'label' => 'Sfondo Widget',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-manage-widget' => 'background-color: {{VALUE}}',
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
                    '{{WRAPPER}} .docmanager-manage-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Ricerca
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
                    '{{WRAPPER}} #docmanager-search' => 'background-color: {{VALUE}}',
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
                    '{{WRAPPER}} #docmanager-search' => 'border-color: {{VALUE}}',
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
        
        // Sezione Stile Tabella
        $this->start_controls_section(
            'table_style_section',
            [
                'label' => 'Stile Tabella',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'view_mode' => 'table',
                ],
            ]
        );
        
        $this->add_control(
            'table_background',
            [
                'label' => 'Sfondo Tabella',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-table' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'table_border_color',
            [
                'label' => 'Colore Bordi Tabella',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#e1e1e1',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-table, {{WRAPPER}} .docmanager-table td, {{WRAPPER}} .docmanager-table th' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'header_background',
            [
                'label' => 'Sfondo Header',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-table th' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'header_text_color',
            [
                'label' => 'Colore Testo Header',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-table th' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'header_typography',
                'label' => 'Tipografia Header',
                'selector' => '{{WRAPPER}} .docmanager-table th',
            ]
        );
        
        $this->add_control(
            'row_text_color',
            [
                'label' => 'Colore Testo Righe',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-table td' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'row_typography',
                'label' => 'Tipografia Righe',
                'selector' => '{{WRAPPER}} .docmanager-table td',
            ]
        );
        
        $this->add_control(
            'row_hover_background',
            [
                'label' => 'Sfondo Riga (Hover)',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-table tr:hover' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'striped_row_background',
            [
                'label' => 'Sfondo Righe Alternate',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f9f9f9',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-table.striped tbody tr:nth-child(even)' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'table_striped' => 'yes',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Cards
        $this->start_controls_section(
            'cards_style_section',
            [
                'label' => 'Stile Cards',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'view_mode' => 'cards',
                ],
            ]
        );
        
        $this->add_control(
            'card_background',
            [
                'label' => 'Sfondo Card',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .manage-card' => 'background-color: {{VALUE}}',
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
                    '{{WRAPPER}} .manage-card' => 'border-color: {{VALUE}}',
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
                    '{{WRAPPER}} .manage-card' => 'border-radius: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_box_shadow',
                'label' => 'Ombra Card',
                'selector' => '{{WRAPPER}} .manage-card',
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
                    '{{WRAPPER}} .manage-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'card_spacing',
            [
                'label' => 'Spaziatura tra Cards',
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
                    '{{WRAPPER}} .manage-cards-grid' => 'gap: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Pulsanti
        $this->start_controls_section(
            'buttons_style_section',
            [
                'label' => 'Stile Pulsanti',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'download_button_background',
            [
                'label' => 'Sfondo Pulsante Download',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#28a745',
                'selectors' => [
                    '{{WRAPPER}} .btn-download' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'download_button_color',
            [
                'label' => 'Colore Testo Download',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .btn-download' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'edit_button_background',
            [
                'label' => 'Sfondo Pulsante Modifica',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .btn-edit' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'edit_button_color',
            [
                'label' => 'Colore Testo Modifica',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .btn-edit' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'delete_button_background',
            [
                'label' => 'Sfondo Pulsante Elimina',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#dc3545',
                'selectors' => [
                    '{{WRAPPER}} .btn-delete' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'delete_button_color',
            [
                'label' => 'Colore Testo Elimina',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .btn-delete' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'button_border_radius',
            [
                'label' => 'Raggio Bordo Pulsanti',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 25,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 4,
                ],
                'selectors' => [
                    '{{WRAPPER}} .btn-download, {{WRAPPER}} .btn-edit, {{WRAPPER}} .btn-delete' => 'border-radius: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'label' => 'Tipografia Pulsanti',
                'selector' => '{{WRAPPER}} .btn-download, {{WRAPPER}} .btn-edit, {{WRAPPER}} .btn-delete',
            ]
        );
        
        $this->add_responsive_control(
            'button_padding',
            [
                'label' => 'Padding Pulsanti',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default' => [
                    'top' => 6,
                    'right' => 12,
                    'bottom' => 6,
                    'left' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .btn-download, {{WRAPPER}} .btn-edit, {{WRAPPER}} .btn-delete' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .page-btn' => 'background-color: {{VALUE}}',
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
                    '{{WRAPPER}} .page-btn' => 'color: {{VALUE}}',
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
                    '{{WRAPPER}} .page-btn.active' => 'background-color: {{VALUE}}',
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
                    '{{WRAPPER}} .page-btn.active' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'pagination_button_border_color',
            [
                'label' => 'Colore Bordo Paginazione',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#dddddd',
                'selectors' => [
                    '{{WRAPPER}} .page-btn' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Messaggi
        $this->start_controls_section(
            'messages_style_section',
            [
                'label' => 'Stile Messaggi',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'no_results_background',
            [
                'label' => 'Sfondo Messaggio Vuoto',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-no-results' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'no_results_color',
            [
                'label' => 'Colore Testo Messaggio Vuoto',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-no-results' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'no_results_typography',
                'label' => 'Tipografia Messaggio Vuoto',
                'selector' => '{{WRAPPER}} .docmanager-no-results',
            ]
        );
        
        $this->add_control(
            'loading_color',
            [
                'label' => 'Colore Loading',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .loading-spinner' => 'border-top-color: {{VALUE}}',
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
        
        $view_class = 'view-' . $settings['view_mode'];
        $table_class = $settings['table_striped'] === 'yes' ? 'striped' : '';
        $cards_class = 'grid-cols-' . $settings['cards_columns'];
        ?>
        
        <style>
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
        
        .manage-cards-grid {
            display: grid;
            margin-bottom: 20px;
        }
        
        .manage-cards-grid.grid-cols-1 { grid-template-columns: 1fr; }
        .manage-cards-grid.grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
        .manage-cards-grid.grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
        .manage-cards-grid.grid-cols-4 { grid-template-columns: repeat(4, 1fr); }
        
        .manage-card {
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 20px;
            background: white;
        }
        
        .manage-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .manage-card-title {
            font-weight: 600;
            color: #333;
            margin: 0;
            flex: 1;
        }
        
        .manage-card-type {
            background: #007cba;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .manage-card-meta {
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }
        
        .manage-card-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .manage-list-view .manage-item {
            border-bottom: 1px solid #e1e1e1;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .manage-list-view .manage-item:last-child {
            border-bottom: none;
        }
        
        .manage-item-info h4 {
            margin: 0 0 5px 0;
            font-weight: 600;
        }
        
        .manage-item-meta {
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .manage-cards-grid.grid-cols-2,
            .manage-cards-grid.grid-cols-3,
            .manage-cards-grid.grid-cols-4 {
                grid-template-columns: 1fr;
            }
            
            .manage-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .manage-card-actions {
                justify-content: center;
                width: 100%;
            }
        }
        
        @media (min-width: 769px) and (max-width: 1024px) {
            .manage-cards-grid.grid-cols-3,
            .manage-cards-grid.grid-cols-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        </style>
        
        <div class="docmanager-manage-widget <?php echo esc_attr($view_class); ?>">
            <?php if (!empty($settings['title'])): ?>
            <h3><?php echo esc_html($settings['title']); ?></h3>
            <?php endif; ?>
            
            <?php if (!empty($settings['subtitle'])): ?>
            <p class="manage-subtitle"><?php echo esc_html($settings['subtitle']); ?></p>
            <?php endif; ?>
            
            <?php if ($settings['show_search'] === 'yes'): ?>
            <div class="search-container">
                <input type="text" id="docmanager-search" placeholder="Cerca documenti...">
                <button type="button" class="search-reset-btn" id="docmanager-reset-btn">✕</button>
            </div>
            <?php endif; ?>
            
            <div class="docmanager-manage-container">
                <div class="docmanager-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p>Caricamento...</p>
                </div>
                
                <?php if ($settings['view_mode'] === 'table'): ?>
                <table class="docmanager-table <?php echo esc_attr($table_class); ?>" style="display: none;">
                    <thead>
                        <tr>
                            <th>Titolo</th>
                            <?php if ($settings['show_user_column'] === 'yes' && current_user_can('manage_options')): ?>
                            <th>Utente</th>
                            <?php endif; ?>
                            <?php if ($settings['show_file_info'] === 'yes'): ?>
                            <th>Tipo</th>
                            <th>Dimensione</th>
                            <?php endif; ?>
                            <th>Data</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody id="docmanager-documents-list">
                    </tbody>
                </table>
                <?php elseif ($settings['view_mode'] === 'cards'): ?>
                <div class="manage-cards-grid <?php echo esc_attr($cards_class); ?>" id="docmanager-documents-list" style="display: none;">
                </div>
                <?php else: ?>
                <div class="manage-list-view" id="docmanager-documents-list" style="display: none;">
                </div>
                <?php endif; ?>
                
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
                        <select id="edit-user" name="user_id">
                            <option value="">Seleziona utente...</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_notes'] === 'yes'): ?>
                    <div class="form-group">
                        <label for="edit-notes">Note</label>
                        <textarea id="edit-notes" name="notes" rows="3"></textarea>
                    </div>
                    <?php endif; ?>
                    
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
            var viewMode = '<?php echo esc_js($settings['view_mode']); ?>';
            var showUserColumn = <?php echo $settings['show_user_column'] === 'yes' && current_user_can('manage_options') ? 'true' : 'false'; ?>;
            var showFileInfo = <?php echo $settings['show_file_info'] === 'yes' ? 'true' : 'false'; ?>;
            var showNotes = <?php echo $settings['show_notes'] === 'yes' ? 'true' : 'false'; ?>;
            var isAdmin = <?php echo current_user_can('manage_options') ? 'true' : 'false'; ?>;
            
            function loadDocuments(page = 1, search = '') {
                $('.docmanager-loading').show();
                $('.docmanager-table, .manage-cards-grid, .manage-list-view').hide();
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
                if (documents.length === 0) {
                    $('.docmanager-no-results').show();
                    return;
                }
                
                if (viewMode === 'table') {
                    displayTableView(documents);
                } else if (viewMode === 'cards') {
                    displayCardsView(documents);
                } else {
                    displayListView(documents);
                }
            }
            
            function displayTableView(documents) {
                var html = '';
                
                documents.forEach(function(doc) {
                    html += '<tr>';
                    html += '<td><strong>' + doc.title + '</strong>';
                    if (showNotes && doc.notes) {
                        html += '<br><small>' + doc.notes + '</small>';
                    }
                    html += '</td>';
                    
                    if (showUserColumn) {
                        html += '<td>' + (doc.user_name || 'N/A') + '</td>';
                    }
                    
                    if (showFileInfo) {
                        html += '<td>' + doc.file_type + '</td>';
                        html += '<td>' + doc.file_size + '</td>';
                    }
                    
                    html += '<td>' + doc.upload_date + '</td>';
                    html += '<td>';
                    html += getActionButtons(doc);
                    html += '</td>';
                    html += '</tr>';
                });
                
                $('#docmanager-documents-list').html(html);
                $('.docmanager-table').show();
            }
            
            function displayCardsView(documents) {
                var html = '';
                
                documents.forEach(function(doc) {
                    html += '<div class="manage-card">';
                    html += '<div class="manage-card-header">';
                    html += '<h4 class="manage-card-title">' + doc.title + '</h4>';
                    if (showFileInfo) {
                        html += '<span class="manage-card-type">' + doc.file_type + '</span>';
                    }
                    html += '</div>';
                    
                    html += '<div class="manage-card-meta">';
                    if (showUserColumn && doc.user_name) {
                        html += '<div>👤 ' + doc.user_name + '</div>';
                    }
                    if (showFileInfo) {
                        html += '<div>📊 ' + doc.file_size + '</div>';
                    }
                    html += '<div>📅 ' + doc.upload_date + '</div>';
                    if (showNotes && doc.notes) {
                        html += '<div>📝 ' + doc.notes + '</div>';
                    }
                    html += '</div>';
                    
                    html += '<div class="manage-card-actions">';
                    html += getActionButtons(doc);
                    html += '</div>';
                    
                    html += '</div>';
                });
                
                $('#docmanager-documents-list').html(html);
                $('.manage-cards-grid').show();
            }
            
            function displayListView(documents) {
                var html = '';
                
                documents.forEach(function(doc) {
                    html += '<div class="manage-item">';
                    html += '<div class="manage-item-info">';
                    html += '<h4>' + doc.title + '</h4>';
                    html += '<div class="manage-item-meta">';
                    
                    var metaParts = [];
                    if (showUserColumn && doc.user_name) {
                        metaParts.push('👤 ' + doc.user_name);
                    }
                    if (showFileInfo) {
                        metaParts.push(doc.file_type + ' • ' + doc.file_size);
                    }
                    metaParts.push('📅 ' + doc.upload_date);
                    
                    html += metaParts.join(' • ');
                    
                    if (showNotes && doc.notes) {
                        html += '<br>📝 ' + doc.notes;
                    }
                    html += '</div>';
                    html += '</div>';
                    
                    html += '<div class="manage-card-actions">';
                    html += getActionButtons(doc);
                    html += '</div>';
                    
                    html += '</div>';
                });
                
                $('#docmanager-documents-list').html(html);
                $('.manage-list-view').show();
            }
            
            function getActionButtons(doc) {
                var html = '';
                html += '<a href="' + doc.download_url + '" class="btn-download">Download</a> ';
                
                if (isAdmin) {
                    html += '<button class="btn-edit" data-id="' + doc.id + '" data-title="' + doc.title + '" data-notes="' + (doc.notes || '') + '" data-user="' + (doc.user_id || '') + '">Modifica</button> ';
                    html += '<button class="btn-delete" data-id="' + doc.id + '" onclick="return confirm(\'Sei sicuro di voler eliminare questo documento?\')">Elimina</button>';
                }
                
                return html;
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
            
            // Event handlers
            $(document).on('click', '.page-btn', function() {
                var page = $(this).data('page');
                currentPage = page;
                loadDocuments(page);
            });
            
            $('#docmanager-search').on('input', function() {
                var searchTerm = $(this).val().trim();
                var $resetBtn = $('#docmanager-reset-btn');
                
                if (searchTerm.length > 0) {
                    $resetBtn.addClass('show');
                    
                    if (searchTerm.length >= 2) {
                        currentPage = 1;
                        clearTimeout(window.searchTimeout);
                        window.searchTimeout = setTimeout(function() {
                            loadDocuments(1, searchTerm);
                        }, 500);
                    }
                } else {
                    $resetBtn.removeClass('show');
                    currentPage = 1;
                    isSearching = false;
                    clearTimeout(window.searchTimeout);
                    loadDocuments(1);
                }
            });
            
            $('#docmanager-reset-btn').on('click', function() {
                $('#docmanager-search').val('').trigger('input');
            });
            
            $(document).on('click', '.btn-edit', function() {
                var id = $(this).data('id');
                var title = $(this).data('title');
                var notes = $(this).data('notes');
                var userId = $(this).data('user');
                
                $('#edit-doc-id').val(id);
                $('#edit-title').val(title);
                $('#edit-notes').val(notes);
                
                if (isAdmin) {
                    loadUsers(userId);
                }
                
                $('#docmanager-edit-modal').show();
            });
            
            function loadUsers(selectedUserId) {
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'docmanager_get_users',
                        nonce: docmanager_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<option value="">Seleziona utente...</option>';
                            response.data.users.forEach(function(user) {
                                var selected = user.ID == selectedUserId ? 'selected' : '';
                                html += '<option value="' + user.ID + '" ' + selected + '>' + user.display_name + '</option>';
                            });
                            $('#edit-user').html(html);
                        }
                    }
                });
            }
            
            $(document).on('click', '.btn-delete', function() {
                var id = $(this).data('id');
                
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
                                var searchTerm = $('#docmanager-search').val().trim();
                                loadDocuments(1, searchTerm);
                            } else {
                                loadDocuments(currentPage);
                            }
                        } else {
                            alert('Errore nell\'eliminazione del documento');
                        }
                    }
                });
            });
            
            $('.docmanager-close, .docmanager-cancel').on('click', function() {
                $('#docmanager-edit-modal').hide();
            });
            
            $('#docmanager-edit-form').on('submit', function(e) {
                e.preventDefault();
                
                var data = {
                    action: 'docmanager_update',
                    nonce: docmanager_ajax.nonce,
                    doc_id: $('#edit-doc-id').val(),
                    title: $('#edit-title').val(),
                    notes: $('#edit-notes').val()
                };
                
                if (isAdmin) {
                    data.user_id = $('#edit-user').val() || 0;
                }
                
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        $('#docmanager-edit-modal').hide();
                        if (response.success) {
                            if (isSearching) {
                                var searchTerm = $('#docmanager-search').val().trim();
                                loadDocuments(1, searchTerm);
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