<?php
/**
 * Widget Elementor per il caricamento documenti
 */

if (!defined('ABSPATH')) {
    exit;
}

class DocManager_Widget_Upload extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'docmanager_upload';
    }
    
    public function get_title() {
        return 'DocManager - Carica Referto';
    }
    
    public function get_icon() {
        return 'eicon-upload';
    }
    
    public function get_categories() {
        return ['general'];
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
                'default' => 'Carica Nuovo Referto',
            ]
        );
        
        $this->add_control(
            'subtitle',
            [
                'label' => 'Sottotitolo',
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => 'Seleziona e carica il tuo documento in modo sicuro',
                'rows' => 2,
            ]
        );
        
        $this->add_control(
            'show_user_select',
            [
                'label' => 'Mostra Selezione Utente',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sì',
                'label_off' => 'No',
                'return_value' => 'yes',
                'default' => 'no',
                'description' => 'Solo per amministratori',
            ]
        );
        
        $this->add_control(
            'show_notes_field',
            [
                'label' => 'Mostra Campo Note',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sì',
                'label_off' => 'No',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'allowed_roles',
            [
                'label' => 'Ruoli Autorizzati',
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_user_roles(),
                'default' => ['administrator'],
                'description' => 'Seleziona i ruoli che possono caricare documenti',
            ]
        );
        
        $this->add_control(
            'success_message',
            [
                'label' => 'Messaggio di Successo',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Documento caricato con successo!',
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
                    '{{WRAPPER}} .docmanager-upload-widget h3' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => 'Tipografia Titolo',
                'selector' => '{{WRAPPER}} .docmanager-upload-widget h3',
            ]
        );
        
        $this->add_control(
            'subtitle_color',
            [
                'label' => 'Colore Sottotitolo',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .upload-subtitle' => 'color: {{VALUE}}',
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
                'selector' => '{{WRAPPER}} .upload-subtitle',
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
                    '{{WRAPPER}} .docmanager-upload-widget' => 'background-color: {{VALUE}}',
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
                    '{{WRAPPER}} .docmanager-upload-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Form
        $this->start_controls_section(
            'form_style_section',
            [
                'label' => 'Stile Form',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'form_background',
            [
                'label' => 'Sfondo Form',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-upload-form' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'form_border_color',
            [
                'label' => 'Colore Bordo Form',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#e1e1e1',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-upload-form' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'form_border_width',
            [
                'label' => 'Spessore Bordo Form',
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
                    '{{WRAPPER}} .docmanager-upload-form' => 'border-width: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->add_control(
            'form_border_radius',
            [
                'label' => 'Raggio Bordo Form',
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
                    '{{WRAPPER}} .docmanager-upload-form' => 'border-radius: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'form_box_shadow',
                'label' => 'Ombra Form',
                'selector' => '{{WRAPPER}} .docmanager-upload-form',
            ]
        );
        
        $this->add_responsive_control(
            'form_padding',
            [
                'label' => 'Padding Form',
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
                    '{{WRAPPER}} .docmanager-upload-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Campi
        $this->start_controls_section(
            'fields_style_section',
            [
                'label' => 'Stile Campi',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'label_color',
            [
                'label' => 'Colore Etichette',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .form-group label' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'label' => 'Tipografia Etichette',
                'selector' => '{{WRAPPER}} .form-group label',
            ]
        );
        
        $this->add_control(
            'input_background',
            [
                'label' => 'Sfondo Campi',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .form-group input, {{WRAPPER}} .form-group select, {{WRAPPER}} .form-group textarea' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'input_border_color',
            [
                'label' => 'Colore Bordo Campi',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#dddddd',
                'selectors' => [
                    '{{WRAPPER}} .form-group input, {{WRAPPER}} .form-group select, {{WRAPPER}} .form-group textarea' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'input_focus_border_color',
            [
                'label' => 'Colore Bordo Focus',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .form-group input:focus, {{WRAPPER}} .form-group select:focus, {{WRAPPER}} .form-group textarea:focus' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'input_text_color',
            [
                'label' => 'Colore Testo Campi',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .form-group input, {{WRAPPER}} .form-group select, {{WRAPPER}} .form-group textarea' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'input_typography',
                'label' => 'Tipografia Campi',
                'selector' => '{{WRAPPER}} .form-group input, {{WRAPPER}} .form-group select, {{WRAPPER}} .form-group textarea',
            ]
        );
        
        $this->add_control(
            'input_border_radius',
            [
                'label' => 'Raggio Bordo Campi',
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
                    '{{WRAPPER}} .form-group input, {{WRAPPER}} .form-group select, {{WRAPPER}} .form-group textarea' => 'border-radius: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'input_padding',
            [
                'label' => 'Padding Campi',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default' => [
                    'top' => 10,
                    'right' => 10,
                    'bottom' => 10,
                    'left' => 10,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .form-group input, {{WRAPPER}} .form-group select, {{WRAPPER}} .form-group textarea' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'help_text_color',
            [
                'label' => 'Colore Testo Aiuto',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .form-group small' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'help_text_typography',
                'label' => 'Tipografia Testo Aiuto',
                'selector' => '{{WRAPPER}} .form-group small',
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Drag & Drop
        $this->start_controls_section(
            'dragdrop_style_section',
            [
                'label' => 'Stile Area Drag & Drop',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->start_controls_tabs('dropzone_style_tabs');

		$this->start_controls_tab(
			'dropzone_normal_tab',
			[
				'label' => 'Normal',
			]
		);

		$this->add_control(
			'dropzone_background',
			[
				'label' => 'Sfondo Area Drop',
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#f9f9f9',
				'selectors' => [
					'{{WRAPPER}} .file-drop-zone' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'dropzone_border_color',
			[
				'label' => 'Colore Bordo Area Drop',
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#dddddd',
				'selectors' => [
					'{{WRAPPER}} .file-drop-zone' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'dropzone_text_color',
			[
				'label' => 'Colore Testo Area Drop',
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#666666',
				'selectors' => [
					'{{WRAPPER}} .file-drop-zone' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'dropzone_active_tab',
			[
				'label' => 'Active',
			]
		);

		$this->add_control(
			'dropzone_active_background',
			[
				'label' => 'Sfondo Area Drop',
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#e8f4fd',
				'selectors' => [
					'{{WRAPPER}} .file-drop-zone.dragover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'dropzone_active_border_color',
			[
				'label' => 'Colore Bordo Area Drop',
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#0073aa',
				'selectors' => [
					'{{WRAPPER}} .file-drop-zone.dragover' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'dropzone_typography',
                'label' => 'Tipografia Area Drop',
                'selector' => '{{WRAPPER}} .file-drop-zone',
            ]
        );
        
        $this->add_control(
            'dropzone_border_radius',
            [
                'label' => 'Raggio Bordo Area Drop',
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
                    'size' => 8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .file-drop-zone' => 'border-radius: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'dropzone_padding',
            [
                'label' => 'Padding Area Drop',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default' => [
                    'top' => 40,
                    'right' => 20,
                    'bottom' => 40,
                    'left' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .file-drop-zone' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sezione Stile Pulsante
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => 'Stile Pulsante Carica',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->start_controls_tabs('button_style_tabs');

		$this->start_controls_tab(
			'button_normal_tab',
			[
				'label' => 'Normal',
			]
		);

		$this->add_control(
			'button_background',
			[
				'label' => 'Sfondo Pulsante',
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#0073aa',
				'selectors' => [
					'{{WRAPPER}} .docmanager-upload-btn' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'button_color',
			[
				'label' => 'Colore Testo Pulsante',
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .docmanager-upload-btn' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'button_hover_tab',
			[
				'label' => 'Hover',
			]
		);

		$this->add_control(
			'button_hover_background',
			[
				'label' => 'Sfondo Pulsante',
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#005a87',
				'selectors' => [
					'{{WRAPPER}} .docmanager-upload-btn:hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'button_hover_color',
			[
				'label' => 'Colore Testo Pulsante',
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .docmanager-upload-btn:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();
		
		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name' => 'button_border',
				'label' => 'Bordo Pulsante',
				'selector' => '{{WRAPPER}} .docmanager-upload-btn',
			]
		);
        
        $this->add_control(
            'button_border_radius',
            [
                'label' => 'Raggio Bordo Pulsante',
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
                    'size' => 4,
                ],
                'selectors' => [
                    '{{WRAPPER}} .docmanager-upload-btn' => 'border-radius: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'label' => 'Tipografia Pulsante',
                'selector' => '{{WRAPPER}} .docmanager-upload-btn',
            ]
        );
        
        $this->add_responsive_control(
            'button_padding',
            [
                'label' => 'Padding Pulsante',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default' => [
                    'top' => 12,
                    'right' => 24,
                    'bottom' => 12,
                    'left' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .docmanager-upload-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'button_width',
            [
                'label' => 'Larghezza Pulsante',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto' => 'Automatica',
                    'full' => 'Larghezza Completa',
                    'custom' => 'Personalizzata',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'button_custom_width',
            [
                'label' => 'Larghezza Personalizzata',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 500,
                        'step' => 10,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                        'step' => 5,
                    ],
                ],
                'condition' => [
                    'button_width' => 'custom',
                ],
                'selectors' => [
                    '{{WRAPPER}} .docmanager-upload-btn' => 'width: {{SIZE}}{{UNIT}};',
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
            'success_message_background',
            [
                'label' => 'Sfondo Messaggio Successo',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#d4edda',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-message.success' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'success_message_color',
            [
                'label' => 'Colore Messaggio Successo',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#155724',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-message.success' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'error_message_background',
            [
                'label' => 'Sfondo Messaggio Errore',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8d7da',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-message.error' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'error_message_color',
            [
                'label' => 'Colore Messaggio Errore',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#721c24',
                'selectors' => [
                    '{{WRAPPER}} .docmanager-message.error' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'message_typography',
                'label' => 'Tipografia Messaggi',
                'selector' => '{{WRAPPER}} .docmanager-message',
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (!is_user_logged_in()) {
            echo '<p>Devi essere loggato per caricare documenti.</p>';
            return;
        }
        
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $allowed_roles = $settings['allowed_roles'];
        
        $can_upload = false;
        foreach ($user_roles as $role) {
            if (in_array($role, $allowed_roles)) {
                $can_upload = true;
                break;
            }
        }
        
        if (!$can_upload) {
            echo '<p>Non hai i permessi per caricare documenti.</p>';
            return;
        }
        
        $button_width_class = '';
        if ($settings['button_width'] === 'full') {
            $button_width_class = 'full-width';
        }
        ?>
        
        <style>
        .file-drop-zone {
            border: 2px dashed #ddd;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .file-drop-zone.dragover {
            transform: scale(1.02);
        }
        
        .file-drop-zone .drop-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.7;
        }
        
        .file-drop-zone .drop-text {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .file-drop-zone .drop-subtext {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .file-selected {
            background-color: #e8f5e8 !important;
            border-color: #28a745 !important;
        }
        
        .file-selected .drop-icon {
            color: #28a745;
        }
        
        .docmanager-upload-btn.full-width {
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .upload-progress {
            display: none;
            margin-top: 15px;
        }
        
        .progress-bar {
            background: #f1f1f1;
            border-radius: 4px;
            overflow: hidden;
            height: 20px;
            position: relative;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #00a0d2, #0073aa);
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
            font-weight: 600;
            color: white;
            text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
        }
        </style>
        
        <div class="docmanager-upload-widget">
            <?php if (!empty($settings['title'])): ?>
            <h3><?php echo esc_html($settings['title']); ?></h3>
            <?php endif; ?>
            
            <?php if (!empty($settings['subtitle'])): ?>
            <p class="upload-subtitle"><?php echo esc_html($settings['subtitle']); ?></p>
            <?php endif; ?>
            
            <form class="docmanager-upload-form" id="docmanager-upload-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="doc-title">Titolo Documento *</label>
                    <input type="text" id="doc-title" name="title" required>
                </div>
                
                <?php if ($settings['show_user_select'] === 'yes' && current_user_can('manage_options')): ?>
                <div class="form-group">
                    <label for="doc-user">Assegna a Utente *</label>
                    <?php 
                    wp_dropdown_users(array(
                        'name' => 'user_id',
                        'id' => 'doc-user',
                        'show_option_none' => 'Seleziona utente...',
                        'option_none_value' => ''
                    )); 
                    ?>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="doc-file">File *</label>
                    <div class="file-drop-zone" id="file-drop-zone">
                        <div class="drop-icon">📁</div>
                        <div class="drop-text">Trascina qui il file o clicca per selezionare</div>
                        <div class="drop-subtext">
                            Tipi consentiti: <?php echo implode(', ', DocManager::get_allowed_file_types()); ?><br>
                            Dimensione massima: <?php echo DocManager::format_file_size(DocManager::get_max_file_size()); ?>
                        </div>
                    </div>
                    <input type="file" id="doc-file" name="file" accept=".<?php echo implode(',.', DocManager::get_allowed_file_types()); ?>" required style="display: none;">
                </div>
                
                <?php if ($settings['show_notes_field'] === 'yes'): ?>
                <div class="form-group">
                    <label for="doc-notes">Note</label>
                    <textarea id="doc-notes" name="notes" rows="3" placeholder="Aggiungi eventuali note o commenti..."></textarea>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <button type="submit" class="docmanager-upload-btn <?php echo esc_attr($button_width_class); ?>">
                        <span class="btn-text">Carica Documento</span>
                        <span class="btn-loading" style="display: none;">⏳ Caricamento...</span>
                    </button>
                </div>
                
                <div class="upload-progress">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                        <div class="progress-text">0%</div>
                    </div>
                </div>
                
                <div class="docmanager-messages"></div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var maxFileSize = <?php echo DocManager::get_max_file_size(); ?>;
            var allowedTypes = <?php echo json_encode(DocManager::get_allowed_file_types()); ?>;
            var $dropZone = $('#file-drop-zone');
            var $fileInput = $('#doc-file');
            var $form = $('#docmanager-upload-form');
            
            // Drag and Drop functionality
            $dropZone.on('click', function() {
                $fileInput.click();
            });
            
            $dropZone.on('dragenter dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });
            
            $dropZone.on('dragleave drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });
            
            $dropZone.on('drop', function(e) {
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelection(files[0]);
                }
            });
            
            $fileInput.on('change', function() {
                if (this.files.length > 0) {
                    handleFileSelection(this.files[0]);
                }
            });
            
            function handleFileSelection(file) {
                var validation = validateFile(file);
                
                if (validation.valid) {
                    $dropZone.addClass('file-selected');
                    $dropZone.find('.drop-icon').text('✅');
                    $dropZone.find('.drop-text').html('<strong>File selezionato:</strong> ' + file.name);
                    $dropZone.find('.drop-subtext').text(formatFileSize(file.size));
                    
                    // Auto-fill title if empty
                    if (!$('#doc-title').val()) {
                        var fileName = file.name.replace(/\.[^/.]+$/, "");
                        $('#doc-title').val(fileName);
                    }
                } else {
                    showMessage(validation.error, 'error');
                    resetDropZone();
                }
            }
            
            function validateFile(file) {
                var fileExtension = file.name.split('.').pop().toLowerCase();
                
                if (!allowedTypes.includes(fileExtension)) {
                    return {
                        valid: false,
                        error: 'Tipo di file non consentito. Tipi permessi: ' + allowedTypes.join(', ')
                    };
                }
                
                if (file.size > maxFileSize) {
                    return {
                        valid: false,
                        error: 'File troppo grande. Dimensione massima: ' + formatFileSize(maxFileSize)
                    };
                }
                
                return { valid: true };
            }
            
            function formatFileSize(bytes) {
                if (bytes >= 1073741824) {
                    return (bytes / 1073741824).toFixed(2) + ' GB';
                } else if (bytes >= 1048576) {
                    return (bytes / 1048576).toFixed(2) + ' MB';
                } else if (bytes >= 1024) {
                    return (bytes / 1024).toFixed(2) + ' KB';
                } else {
                    return bytes + ' bytes';
                }
            }
            
            function resetDropZone() {
                $dropZone.removeClass('file-selected');
                $dropZone.find('.drop-icon').text('📁');
                $dropZone.find('.drop-text').text('Trascina qui il file o clicca per selezionare');
                $dropZone.find('.drop-subtext').html(
                    'Tipi consentiti: <?php echo implode(', ', DocManager::get_allowed_file_types()); ?><br>' +
                    'Dimensione massima: <?php echo DocManager::format_file_size(DocManager::get_max_file_size()); ?>'
                );
                $fileInput.val('');
            }
            
            function showMessage(message, type) {
                var html = '<div class="docmanager-message ' + type + '">' + message + '</div>';
                $('.docmanager-messages').html(html);
                
                // Auto-hide success messages
                if (type === 'success') {
                    setTimeout(function() {
                        $('.docmanager-message').fadeOut();
                    }, 5000);
                }
            }
            
            function showProgress(percent) {
                $('.upload-progress').show();
                $('.progress-fill').css('width', percent + '%');
                $('.progress-text').text(percent + '%');
            }
            
            function hideProgress() {
                $('.upload-progress').hide();
                $('.progress-fill').css('width', '0%');
            }
            
            // Form submission
            $form.on('submit', function(e) {
                e.preventDefault();
                
                // Validation
                if (!$('#doc-title').val().trim()) {
                    showMessage('Il titolo del documento è obbligatorio', 'error');
                    return;
                }
                
                if (!$fileInput[0].files.length) {
                    showMessage('Seleziona un file da caricare', 'error');
                    return;
                }
                
                <?php if ($settings['show_user_select'] === 'yes' && current_user_can('manage_options')): ?>
                if (!$('#doc-user').val()) {
                    showMessage('Seleziona un utente', 'error');
                    return;
                }
                <?php endif; ?>
                
                var formData = new FormData(this);
                formData.append('action', 'docmanager_upload');
                formData.append('nonce', docmanager_ajax.nonce);
                
                <?php if ($settings['show_user_select'] !== 'yes' || !current_user_can('manage_options')): ?>
                formData.append('user_id', '<?php echo get_current_user_id(); ?>');
                <?php endif; ?>
                
                var $button = $('.docmanager-upload-btn');
                $button.prop('disabled', true);
                $button.find('.btn-text').hide();
                $button.find('.btn-loading').show();
                
                $('.docmanager-messages').empty();
                showProgress(0);
                
                // Simulate progress for better UX
                var progress = 0;
                var progressInterval = setInterval(function() {
                    progress += Math.random() * 15;
                    if (progress > 90) progress = 90;
                    showProgress(Math.round(progress));
                }, 200);
                
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        clearInterval(progressInterval);
                        showProgress(100);
                        
                        setTimeout(function() {
                            hideProgress();
                            
                            if (response.success) {
                                showMessage('<?php echo esc_js($settings['success_message']); ?>', 'success');
                                $form[0].reset();
                                resetDropZone();
                            } else {
                                showMessage(response.data || 'Errore durante il caricamento', 'error');
                            }
                        }, 500);
                    },
                    error: function(xhr) {
                        clearInterval(progressInterval);
                        hideProgress();
                        
                        var errorMessage = 'Errore durante il caricamento';
                        if (xhr.responseJSON && xhr.responseJSON.data) {
                            errorMessage = xhr.responseJSON.data;
                        }
                        showMessage(errorMessage, 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                        $button.find('.btn-text').show();
                        $button.find('.btn-loading').hide();
                    }
                });
            });
            
            // Form validation on blur
            $('#doc-title').on('blur', function() {
                if (!$(this).val().trim()) {
                    $(this).css('border-color', '#dc3545');
                } else {
                    $(this).css('border-color', '');
                }
            });
        });
        </script>
        <?php
    }
    
    private function get_user_roles() {
        global $wp_roles;
        $roles = array();
        
        foreach ($wp_roles->roles as $role_key => $role) {
            $roles[$role_key] = $role['name'];
        }
        
        return $roles;
    }
}