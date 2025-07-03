jQuery(document).ready(function($) {
    
    // Handle document deletion
    $('.button-link-delete').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Sei sicuro di voler eliminare questo documento?')) {
            return;
        }
        
        const docId = $(this).data('doc-id');
        const row = $(this).closest('tr');
        
        $.ajax({
            url: docmanager_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'docmanager_delete_document',
                document_id: docId,
                nonce: docmanager_ajax.nonce
            },
            success: function(response) {
                let data;
                try {
                    data = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    alert('Errore nel parsing della risposta');
                    return;
                }
                
                if (data.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    showNotice('success', 'Documento eliminato con successo');
                } else {
                    alert('Errore: ' + data.message);
                }
            },
            error: function() {
                alert('Errore di connessione');
            }
        });
    });
    
    // Handle bulk actions
    $('#bulk-action-form').on('submit', function(e) {
        const action = $('#bulk-action-selector-top').val();
        const selectedDocs = $('input[name="document[]"]:checked');
        
        if (action === '-1') {
            e.preventDefault();
            alert('Seleziona un\'azione');
            return;
        }
        
        if (selectedDocs.length === 0) {
            e.preventDefault();
            alert('Seleziona almeno un documento');
            return;
        }
        
        if (action === 'delete') {
            if (!confirm(`Sei sicuro di voler eliminare ${selectedDocs.length} documenti?`)) {
                e.preventDefault();
                return;
            }
        }
    });
    
    // Select all checkbox functionality
    $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('input[name="document[]"]').prop('checked', isChecked);
    });
    
    // Individual checkbox change
    $('input[name="document[]"]').on('change', function() {
        const totalCheckboxes = $('input[name="document[]"]').length;
        const checkedCheckboxes = $('input[name="document[]"]:checked').length;
        
        $('#cb-select-all-1, #cb-select-all-2').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    // File upload validation
    $('#doc_file').on('change', function() {
        const file = this.files[0];
        if (file) {
            validateAdminFile(file);
        }
    });
    
    // Settings form enhancements
    $('.docmanager-settings-form').on('submit', function() {
        showLoadingSpinner();
    });
    
    // Permissions management
    $('.add-permission-btn').on('click', function() {
        showPermissionModal();
    });
    
    $('.remove-permission').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Rimuovere questo permesso?')) {
            return;
        }
        
        const permissionId = $(this).data('permission-id');
        
        $.ajax({
            url: docmanager_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'docmanager_remove_permission',
                permission_id: permissionId,
                nonce: docmanager_ajax.nonce
            },
            success: function(response) {
                let data;
                try {
                    data = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    alert('Errore nel parsing della risposta');
                    return;
                }
                
                if (data.success) {
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                }
            },
            error: function() {
                alert('Errore di connessione');
            }
        });
    });
    
    // Document preview functionality
    $('.preview-document').on('click', function(e) {
        e.preventDefault();
        
        const documentUrl = $(this).data('document-url');
        const documentTitle = $(this).data('document-title');
        
        showDocumentPreview(documentUrl, documentTitle);
    });
    
    // Search and filter functionality
    $('#document-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterDocuments(searchTerm);
    });
    
    $('#category-filter').on('change', function() {
        const category = $(this).val();
        filterByCategory(category);
    });
    
    // Analytics and stats
    if ($('.docmanager-stats').length) {
        loadDashboardStats();
    }
    
    function validateAdminFile(file) {
        const maxSize = 50 * 1024 * 1024; // 50MB per admin
        const allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
        
        if (file.size > maxSize) {
            alert('File troppo grande. Dimensione massima: 50MB');
            $('#doc_file').val('');
            return false;
        }
        
        const fileExtension = file.name.split('.').pop().toLowerCase();
        if (!allowedTypes.includes(fileExtension)) {
            alert(`Tipo di file non consentito. Tipi permessi: ${allowedTypes.join(', ')}`);
            $('#doc_file').val('');
            return false;
        }
        
        showFileInfo(file);
        return true;
    }
    
    function showFileInfo(file) {
        const fileInfo = `
            <div class="file-info">
                <strong>File selezionato:</strong> ${file.name}<br>
                <strong>Dimensione:</strong> ${formatFileSize(file.size)}<br>
                <strong>Tipo:</strong> ${file.type}
            </div>
        `;
        
        $('.file-info').remove();
        $('#doc_file').after(fileInfo);
    }
    
    function filterDocuments(searchTerm) {
        $('tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.includes(searchTerm));
        });
        
        updateRowCount();
    }
    
    function filterByCategory(category) {
        if (category === '') {
            $('tbody tr').show();
        } else {
            $('tbody tr').each(function() {
                const rowCategory = $(this).find('.category-cell').text();
                $(this).toggle(rowCategory === category);
            });
        }
        
        updateRowCount();
    }
    
    function updateRowCount() {
        const visibleRows = $('tbody tr:visible').length;
        $('.displaying-num').text(`${visibleRows} elementi`);
    }
    
    function showNotice(type, message) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $(`
            <div class="notice ${noticeClass} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Chiudi questo avviso.</span>
                </button>
            </div>
        `);
        
        $('.wrap h1').after(notice);
        
        // Auto-hide dopo 5 secondi
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
        
        // Handle dismiss button
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut();
        });
    }
    
    function showLoadingSpinner() {
        $('.submit .button-primary').prop('disabled', true).text('Salvataggio...');
    }
    
    function showPermissionModal() {
        // Implementazione modale per aggiungere permessi
        const modal = $(`
            <div class="docmanager-modal-overlay">
                <div class="docmanager-modal">
                    <div class="docmanager-modal-header">
                        <h3>Aggiungi Permesso</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="docmanager-modal-body">
                        <form id="add-permission-form">
                            <table class="form-table">
                                <tr>
                                    <th><label for="permission-type">Tipo Permesso</label></th>
                                    <td>
                                        <select id="permission-type" name="permission_type">
                                            <option value="user">Utente Specifico</option>
                                            <option value="role">Ruolo</option>
                                            <option value="group">Gruppo</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="permission-target">Assegna a</label></th>
                                    <td>
                                        <select id="permission-target" name="permission_target">
                                            <option value="">Seleziona...</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="permission-level">Livello Permesso</label></th>
                                    <td>
                                        <select id="permission-level" name="permission_level">
                                            <option value="view">Visualizzazione</option>
                                            <option value="download">Download</option>
                                            <option value="manage">Gestione</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <button type="submit" class="button-primary">Aggiungi Permesso</button>
                                <button type="button" class="button modal-cancel">Annulla</button>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        // Handle modal close
        modal.find('.modal-close, .modal-cancel').on('click', function() {
            modal.remove();
        });
        
        // Handle permission type change
        modal.find('#permission-type').on('change', function() {
            updatePermissionTargets($(this).val());
        });
        
        // Handle form submission
        modal.find('#add-permission-form').on('submit', function(e) {
            e.preventDefault();
            // Implementazione salvataggio permesso
            modal.remove();
        });
    }
    
    function updatePermissionTargets(type) {
        const targetSelect = $('#permission-target');
        targetSelect.empty().append('<option value="">Caricamento...</option>');
        
        $.ajax({
            url: docmanager_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'docmanager_get_permission_targets',
                type: type,
                nonce: docmanager_ajax.nonce
            },
            success: function(response) {
                let data;
                try {
                    data = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    targetSelect.html('<option value="">Errore caricamento</option>');
                    return;
                }
                
                if (data.success) {
                    targetSelect.empty().append('<option value="">Seleziona...</option>');
                    data.targets.forEach(function(target) {
                        targetSelect.append(`<option value="${target.value}">${target.label}</option>`);
                    });
                } else {
                    targetSelect.html('<option value="">Errore caricamento</option>');
                }
            },
            error: function() {
                targetSelect.html('<option value="">Errore connessione</option>');
            }
        });
    }
    
    function showDocumentPreview(url, title) {
        const previewModal = $(`
            <div class="docmanager-preview-overlay">
                <div class="docmanager-preview-modal">
                    <div class="docmanager-preview-header">
                        <h3>${title}</h3>
                        <button class="preview-close">&times;</button>
                    </div>
                    <div class="docmanager-preview-body">
                        <iframe src="${url}" width="100%" height="600px" frameborder="0"></iframe>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(previewModal);
        
        previewModal.find('.preview-close').on('click', function() {
            previewModal.remove();
        });
        
        // Close on overlay click
        previewModal.on('click', function(e) {
            if (e.target === this) {
                previewModal.remove();
            }
        });
    }
    
    function loadDashboardStats() {
        $.ajax({
            url: docmanager_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'docmanager_get_stats',
                nonce: docmanager_ajax.nonce
            },
            success: function(response) {
                let data;
                try {
                    data = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    console.error('Errore parsing stats');
                    return;
                }
                
                if (data.success) {
                    updateStatsDisplay(data.stats);
                }
            },
            error: function() {
                console.error('Errore caricamento statistiche');
            }
        });
    }
    
    function updateStatsDisplay(stats) {
        $('.total-documents .stat-number').text(stats.total_documents || 0);
        $('.total-downloads .stat-number').text(stats.total_downloads || 0);
        $('.active-users .stat-number').text(stats.active_users || 0);
        $('.storage-used .stat-number').text(formatFileSize(stats.storage_used || 0));
        
        // Update recent activity
        if (stats.recent_activity) {
            const activityList = $('.recent-activity-list');
            activityList.empty();
            
            stats.recent_activity.forEach(function(activity) {
                activityList.append(`
                    <li class="activity-item">
                        <span class="activity-icon">${getActivityIcon(activity.action)}</span>
                        <span class="activity-text">${activity.description}</span>
                        <span class="activity-time">${formatTimeAgo(activity.timestamp)}</span>
                    </li>
                `);
            });
        }
    }
    
    function getActivityIcon(action) {
        const icons = {
            'upload': '📤',
            'download': '📥',
            'delete': '🗑️',
            'view': '👁️',
            'share': '🔗'
        };
        return icons[action] || '📋';
    }
    
    function formatTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diffInSeconds = Math.floor((now - time) / 1000);
        
        if (diffInSeconds < 60) {
            return 'ora';
        } else if (diffInSeconds < 3600) {
            return Math.floor(diffInSeconds / 60) + ' min fa';
        } else if (diffInSeconds < 86400) {
            return Math.floor(diffInSeconds / 3600) + ' ore fa';
        } else {
            return Math.floor(diffInSeconds / 86400) + ' giorni fa';
        }
    }
    
    // Utility functions
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Enhanced table sorting
    $('.column-header.sortable').on('click', function() {
        const column = $(this).data('column');
        const direction = $(this).hasClass('asc') ? 'desc' : 'asc';
        
        // Remove all sorting classes
        $('.column-header').removeClass('asc desc');
        
        // Add current sorting class
        $(this).addClass(direction);
        
        sortTable(column, direction);
    });
    
    function sortTable(column, direction) {
        const table = $('.wp-list-table tbody');
        const rows = table.find('tr').toArray();
        
        rows.sort(function(a, b) {
            const aValue = $(a).find(`[data-column="${column}"]`).text().trim();
            const bValue = $(b).find(`[data-column="${column}"]`).text().trim();
            
            // Handle different data types
            if (column === 'file_size') {
                const aSize = parseFileSize(aValue);
                const bSize = parseFileSize(bValue);
                return direction === 'asc' ? aSize - bSize : bSize - aSize;
            } else if (column === 'upload_date') {
                const aDate = new Date(aValue);
                const bDate = new Date(bValue);
                return direction === 'asc' ? aDate - bDate : bDate - aDate;
            } else {
                return direction === 'asc' ? 
                    aValue.localeCompare(bValue) : 
                    bValue.localeCompare(aValue);
            }
        });
        
        table.empty().append(rows);
    }
    
    function parseFileSize(sizeString) {
        const units = {
            'B': 1,
            'KB': 1024,
            'MB': 1024 * 1024,
            'GB': 1024 * 1024 * 1024
        };
        
        const match = sizeString.match(/^([\d.]+)\s*(\w+)$/);
        if (!match) return 0;
        
        const value = parseFloat(match[1]);
        const unit = match[2];
        
        return value * (units[unit] || 1);
    }
    
    // Auto-save functionality for settings
    $('.docmanager-settings input, .docmanager-settings select, .docmanager-settings textarea').on('change', function() {
        const setting = $(this).attr('name');
        const value = $(this).val();
        
        // Visual feedback
        $(this).addClass('saving');
        
        $.ajax({
            url: docmanager_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'docmanager_auto_save_setting',
                setting: setting,
                value: value,
                nonce: docmanager_ajax.nonce
            },
            success: function(response) {
                $(this).removeClass('saving').addClass('saved');
                setTimeout(() => {
                    $(this).removeClass('saved');
                }, 2000);
            }.bind(this),
            error: function() {
                $(this).removeClass('saving').addClass('error');
                setTimeout(() => {
                    $(this).removeClass('error');
                }, 2000);
            }.bind(this)
        });
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+S per salvare
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('.button-primary[name="save_settings"]').click();
        }
        
        // Esc per chiudere modali
        if (e.key === 'Escape') {
            $('.docmanager-modal-overlay, .docmanager-preview-overlay').remove();
        }
    });
    
    // Tooltip initialization
    $('[data-tooltip]').on('mouseenter', function() {
        const tooltip = $(this).data('tooltip');
        const tooltipEl = $(`<div class="docmanager-tooltip">${tooltip}</div>`);
        
        $('body').append(tooltipEl);
        
        const rect = this.getBoundingClientRect();
        tooltipEl.css({
            top: rect.top - tooltipEl.outerHeight() - 5,
            left: rect.left + (rect.width / 2) - (tooltipEl.outerWidth() / 2)
        });
    }).on('mouseleave', function() {
        $('.docmanager-tooltip').remove();
    });

});