// DocManager Admin JavaScript

jQuery(document).ready(function($) {
    
    // Inizializzazione componenti admin
    initMetaboxes();
    initBulkActions();
    initSettings();
    initLogManagement();
    initFileManagement();
    initDashboardStats();
    initQuickActions();
    
    /**
     * Inizializza funzionalità metabox
     */
    function initMetaboxes() {
        // Gestione upload file nei metabox
        $('.docmanager-file-upload').on('change', function() {
            var $input = $(this);
            var $preview = $input.closest('.docmanager-metabox').find('.docmanager-file-preview');
            var file = $input[0].files[0];
            
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        $preview.html('<img src="' + e.target.result + '" style="max-width: 200px; max-height: 200px;">');
                    } else {
                        $preview.html('<p><strong>File selezionato:</strong> ' + file.name + '</p>');
                    }
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Rimozione file
        $('.docmanager-remove-file').on('click', function(e) {
            e.preventDefault();
            
            if (confirm('Sei sicuro di voler rimuovere questo file?')) {
                var $btn = $(this);
                var postId = $btn.data('post-id');
                
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'docmanager_remove_file',
                        post_id: postId,
                        nonce: docmanager_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $btn.closest('.docmanager-file-info').fadeOut(function() {
                                $(this).remove();
                            });
                            showNotice('File rimosso con successo', 'success');
                        } else {
                            showNotice('Errore durante la rimozione del file', 'error');
                        }
                    },
                    error: function() {
                        showNotice('Errore di connessione', 'error');
                    }
                });
            }
        });
    }
    
    /**
     * Inizializza azioni bulk
     */
    function initBulkActions() {
        // Selezione tutti i checkbox
        $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
            var checked = $(this).prop('checked');
            $('.wp-list-table tbody input[type="checkbox"]').prop('checked', checked);
            updateBulkActionsState();
        });
        
        // Gestione singoli checkbox
        $('.wp-list-table tbody input[type="checkbox"]').on('change', function() {
            updateBulkActionsState();
        });
        
        // Esecuzione azioni bulk
        $('#doaction, #doaction2').on('click', function(e) {
            var action = $(this).prev('select').val();
            var selected = $('.wp-list-table tbody input[type="checkbox"]:checked');
            
            if (action === '-1') {
                e.preventDefault();
                return false;
            }
            
            if (selected.length === 0) {
                e.preventDefault();
                alert('Seleziona almeno un elemento.');
                return false;
            }
            
            if (action === 'delete') {
                e.preventDefault();
                if (confirm('Sei sicuro di voler eliminare ' + selected.length + ' elementi?')) {
                    executeBulkAction(action, selected);
                }
                return false;
            }
            
            if (action === 'reassign') {
                e.preventDefault();
                var userId = prompt('Inserisci l\'ID dell\'utente a cui assegnare i documenti:');
                if (userId) {
                    executeBulkAction(action, selected, {user_id: userId});
                }
                return false;
            }
        });
    }
    
    /**
     * Aggiorna stato pulsanti azioni bulk
     */
    function updateBulkActionsState() {
        var selected = $('.wp-list-table tbody input[type="checkbox"]:checked').length;
        var total = $('.wp-list-table tbody input[type="checkbox"]').length;
        
        // Aggiorna stato "Seleziona tutto"
        if (selected === 0) {
            $('#cb-select-all-1, #cb-select-all-2').prop('indeterminate', false).prop('checked', false);
        } else if (selected === total) {
            $('#cb-select-all-1, #cb-select-all-2').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#cb-select-all-1, #cb-select-all-2').prop('indeterminate', true);
        }
        
        // Mostra/nascondi counter
        if (selected > 0) {
            $('.bulkactions .selected-count').remove();
            $('.bulkactions').append('<span class="selected-count">(' + selected + ' selezionati)</span>');
        } else {
            $('.bulkactions .selected-count').remove();
        }
    }
    
    /**
     * Esegue azione bulk
     */
    function executeBulkAction(action, selected, data) {
        var postIds = [];
        selected.each(function() {
            postIds.push($(this).val());
        });
        
        var ajaxData = {
            action: 'docmanager_bulk_action',
            bulk_action: action,
            post_ids: postIds,
            nonce: docmanager_ajax.nonce
        };
        
        if (data) {
            $.extend(ajaxData, data);
        }
        
        $.ajax({
            url: docmanager_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            beforeSend: function() {
                $('#doaction, #doaction2').prop('disabled', true);
                $('.bulkactions').append('<span class="spinner is-active"></span>');
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    location.reload();
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('Errore durante l\'esecuzione dell\'azione', 'error');
            },
            complete: function() {
                $('#doaction, #doaction2').prop('disabled', false);
                $('.bulkactions .spinner').remove();
            }
        });
    }
    
    /**
     * Inizializza pagina impostazioni
     */
    function initSettings() {
        // Tabs nelle impostazioni
        $('.docmanager-tab-nav button').on('click', function() {
            var target = $(this).data('tab');
            
            $('.docmanager-tab-nav button').removeClass('active');
            $('.docmanager-tab-content').removeClass('active');
            
            $(this).addClass('active');
            $('#' + target).addClass('active');
        });
        
        // Validazione form impostazioni
        $('#docmanager-settings-form').on('submit', function(e) {
            var maxSize = parseInt($('#max_file_size').val());
            if (maxSize <= 0 || maxSize > 100) {
                e.preventDefault();
                alert('La dimensione massima deve essere compresa tra 1 e 100 MB');
                return false;
            }
            
            var allowedTypes = $('#allowed_file_types').val().trim();
            if (!allowedTypes) {
                e.preventDefault();
                alert('Inserisci almeno un tipo di file consentito');
                return false;
            }
        });
        
        // Reset impostazioni
        $('.docmanager-reset-settings').on('click', function(e) {
            e.preventDefault();
            
            if (confirm('Sei sicuro di voler ripristinare le impostazioni predefinite?')) {
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'docmanager_reset_settings',
                        nonce: docmanager_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotice('Impostazioni ripristinate', 'success');
                            location.reload();
                        } else {
                            showNotice('Errore durante il ripristino', 'error');
                        }
                    },
                    error: function() {
                        showNotice('Errore di connessione', 'error');
                    }
                });
            }
        });
        
        // Test configurazione email
        $('.docmanager-test-email').on('click', function(e) {
            e.preventDefault();
            
            var email = prompt('Inserisci l\'email di test:');
            if (email) {
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'docmanager_test_email',
                        email: email,
                        nonce: docmanager_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotice('Email di test inviata', 'success');
                        } else {
                            showNotice('Errore invio email: ' + response.data.message, 'error');
                        }
                    },
                    error: function() {
                        showNotice('Errore di connessione', 'error');
                    }
                });
            }
        });
    }
    
    /**
     * Inizializza gestione log
     */
    function initLogManagement() {
        // Filtri log
        $('#log-filter-user, #log-filter-action, #log-filter-date').on('change', function() {
            var $form = $(this).closest('form');
            $form.submit();
        });
        
        // Esporta log
        $('.docmanager-export-logs').on('click', function(e) {
            e.preventDefault();
            
            var format = $(this).data('format') || 'csv';
            var url = docmanager_ajax.ajax_url + '?action=docmanager_export_logs&format=' + format + '&nonce=' + docmanager_ajax.nonce;
            
            // Aggiungere filtri correnti
            var filters = {};
            $('#log-filter-user, #log-filter-action, #log-filter-date').each(function() {
                if ($(this).val()) {
                    filters[$(this).attr('name')] = $(this).val();
                }
            });
            
            if (Object.keys(filters).length > 0) {
                url += '&' + $.param(filters);
            }
            
            window.location = url;
        });
        
        // Cancella log
        $('.docmanager-clear-logs').on('click', function(e) {
            e.preventDefault();
            
            if (confirm('Sei sicuro di voler cancellare tutti i log? Questa azione non può essere annullata.')) {
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'docmanager_clear_logs',
                        nonce: docmanager_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotice('Log cancellati con successo', 'success');
                            location.reload();
                        } else {
                            showNotice('Errore durante la cancellazione dei log', 'error');
                        }
                    },
                    error: function() {
                        showNotice('Errore di connessione', 'error');
                    }
                });
            }
        });
    }
    
    /**
     * Inizializza gestione file
     */
    function initFileManagement() {
        // Pulizia file orfani
        $('.docmanager-cleanup-files').on('click', function(e) {
            e.preventDefault();
            
            if (confirm('Questa operazione eliminerà i file non associati a nessun documento. Continuare?')) {
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'docmanager_cleanup_files',
                        nonce: docmanager_ajax.nonce
                    },
                    beforeSend: function() {
                        $('.docmanager-cleanup-files').prop('disabled', true).text('Pulizia in corso...');
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotice(response.data.message, 'success');
                        } else {
                            showNotice('Errore durante la pulizia', 'error');
                        }
                    },
                    error: function() {
                        showNotice('Errore di connessione', 'error');
                    },
                    complete: function() {
                        $('.docmanager-cleanup-files').prop('disabled', false).text('Pulisci File Orfani');
                    }
                });
            }
        });
        
        // Controllo spazio disco
        $('.docmanager-check-disk-space').on('click', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: docmanager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'docmanager_check_disk_space',
                    nonce: docmanager_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var html = '<div class="docmanager-disk-info">';
                        html += '<p><strong>Spazio totale:</strong> ' + data.total_space + '</p>';
                        html += '<p><strong>Spazio utilizzato:</strong> ' + data.used_space + '</p>';
                        html += '<p><strong>Spazio libero:</strong> ' + data.free_space + '</p>';
                        html += '<p><strong>File DocManager:</strong> ' + data.docmanager_files + '</p>';
                        html += '</div>';
                        
                        $('#docmanager-disk-info').html(html);
                    } else {
                        showNotice('Errore durante il controllo dello spazio', 'error');
                    }
                },
                error: function() {
                    showNotice('Errore di connessione', 'error');
                }
            });
        });
    }
    
    /**
     * Inizializza statistiche dashboard
     */
    function initDashboardStats() {
        // Ricarica statistiche
        $('.docmanager-refresh-stats').on('click', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: docmanager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'docmanager_refresh_stats',
                    nonce: docmanager_ajax.nonce
                },
                beforeSend: function() {
                    $('.docmanager-stats').addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        var stats = response.data;
                        updateStatsDisplay(stats);
                        showNotice('Statistiche aggiornate', 'success');
                    } else {
                        showNotice('Errore durante l\'aggiornamento delle statistiche', 'error');
                    }
                },
                error: function() {
                    showNotice('Errore di connessione', 'error');
                },
                complete: function() {
                    $('.docmanager-stats').removeClass('loading');
                }
            });
        });
        
        // Grafico statistiche
        if ($('#docmanager-stats-chart').length) {
            loadStatsChart();
        }
    }
    
    /**
     * Aggiorna visualizzazione statistiche
     */
    function updateStatsDisplay(stats) {
        $('.docmanager-stat-number').each(function() {
            var $stat = $(this);
            var key = $stat.data('stat');
            if (stats[key] !== undefined) {
                animateNumber($stat, stats[key]);
            }
        });
    }
    
    /**
     * Anima numero statistiche
     */
    function animateNumber($element, newValue) {
        var currentValue = parseInt($element.text()) || 0;
        var increment = (newValue - currentValue) / 20;
        
        var interval = setInterval(function() {
            currentValue += increment;
            if ((increment > 0 && currentValue >= newValue) || (increment < 0 && currentValue <= newValue)) {
                currentValue = newValue;
                clearInterval(interval);
            }
            $element.text(Math.floor(currentValue));
        }, 50);
    }
    
    /**
     * Carica grafico statistiche
     */
    function loadStatsChart() {
        $.ajax({
            url: docmanager_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'docmanager_get_chart_data',
                nonce: docmanager_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderStatsChart(response.data);
                }
            }
        });
    }
    
    /**
     * Renderizza grafico statistiche
     */
    function renderStatsChart(data) {
        var ctx = document.getElementById('docmanager-stats-chart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Documenti caricati',
                    data: data.uploads,
                    borderColor: '#007cba',
                    backgroundColor: 'rgba(0, 124, 186, 0.1)',
                    fill: true
                }, {
                    label: 'Download',
                    data: data.downloads,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    /**
     * Inizializza azioni rapide
     */
    function initQuickActions() {
        // Toggle stato documento
        $('.docmanager-toggle-status').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postId = $btn.data('post-id');
            var currentStatus = $btn.data('current-status');
            var newStatus = currentStatus === 'publish' ? 'draft' : 'publish';
            
            $.ajax({
                url: docmanager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'docmanager_toggle_status',
                    document_id: postId,
                    new_status: newStatus,
                    nonce: docmanager_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $btn.data('current-status', newStatus);
                        $btn.text(newStatus === 'publish' ? 'Pubblica' : 'Bozza');
                        
                        var $statusBadge = $btn.closest('tr').find('.docmanager-status-badge');
                        $statusBadge.removeClass('docmanager-status-publish docmanager-status-draft')
                                   .addClass('docmanager-status-' + newStatus)
                                   .text(newStatus === 'publish' ? 'Pubblicato' : 'Bozza');
                                   
                        showNotice('Stato aggiornato', 'success');
                    } else {
                        showNotice('Errore durante l\'aggiornamento dello stato', 'error');
                    }
                },
                error: function() {
                    showNotice('Errore di connessione', 'error');
                }
            });
        });
        
        // Duplica documento
        $('.docmanager-duplicate').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postId = $btn.data('post-id');
            
            $.ajax({
                url: docmanager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'docmanager_duplicate_document',
                    document_id: postId,
                    nonce: docmanager_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Documento duplicato con successo', 'success');
                        location.reload();
                    } else {
                        showNotice('Errore durante la duplicazione', 'error');
                    }
                },
                error: function() {
                    showNotice('Errore di connessione', 'error');
                }
            });
        });
        
        // Riassegna documento
        $('.docmanager-reassign').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postId = $btn.data('post-id');
            var currentUser = $btn.data('current-user');
            
            var newUser = prompt('Inserisci il nuovo utente (ID o email):', currentUser);
            if (newUser && newUser !== currentUser) {
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'docmanager_reassign_document',
                        document_id: postId,
                        new_user: newUser,
                        nonce: docmanager_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotice('Documento riassegnato con successo', 'success');
                            location.reload();
                        } else {
                            showNotice('Errore durante la riassegnazione: ' + response.data.message, 'error');
                        }
                    },
                    error: function() {
                        showNotice('Errore di connessione', 'error');
                    }
                });
            }
        });
    }
    
    /**
     * Mostra notifica
     */
    function showNotice(message, type) {
        var noticeClass = 'notice-' + (type || 'info');
        var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Drag & Drop per riordinamento
     */
    if ($('.docmanager-sortable').length) {
        $('.docmanager-sortable').sortable({
            handle: '.docmanager-drag-handle',
            update: function(event, ui) {
                var order = $(this).sortable('toArray', {attribute: 'data-id'});
                
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'docmanager_update_order',
                        order: order,
                        nonce: docmanager_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotice('Ordine aggiornato', 'success');
                        }
                    }
                });
            }
        });
    }
    
    /**
     * Ricerca in tempo reale
     */
    var searchTimeout;
    $('#docmanager-search').on('input', function() {
        clearTimeout(searchTimeout);
        var query = $(this).val();
        
        searchTimeout = setTimeout(function() {
            if (query.length >= 3 || query.length === 0) {
                performSearch(query);
            }
        }, 300);
    });
    
    /**
     * Esegue ricerca
     */
    function performSearch(query) {
        $.ajax({
            url: docmanager_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'docmanager_search',
                query: query,
                nonce: docmanager_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#docmanager-search-results').html(response.data.html);
                }
            }
        });
    }
    
    /**
     * Inizializza tooltips
     */
    if (typeof $.fn.tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip({
            container: 'body'
        });
    }
    
    /**
     * Gestione responsive tabelle
     */
    function makeTablesResponsive() {
        $('.wp-list-table').each(function() {
            var $table = $(this);
            var $wrapper = $table.closest('.table-responsive');
            
            if ($wrapper.length === 0) {
                $table.wrap('<div class="table-responsive"></div>');
            }
        });
    }
    
    makeTablesResponsive();
    
    /**
     * Auto-save bozze
     */
    var autoSaveInterval;
    function startAutoSave() {
        autoSaveInterval = setInterval(function() {
            var $form = $('#post');
            if ($form.length && $form.hasClass('docmanager-form')) {
                var formData = $form.serialize();
                
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: formData + '&action=docmanager_auto_save&nonce=' + docmanager_ajax.nonce,
                    success: function(response) {
                        if (response.success) {
                            $('#docmanager-auto-save-notice').text('Bozza salvata alle ' + new Date().toLocaleTimeString()).fadeIn();
                        }
                    }
                });
            }
        }, 60000); // Ogni minuto
    }
    
    if ($('#post').hasClass('docmanager-form')) {
        startAutoSave();
    }
    
    /**
     * Gestione avanzata file
     */
    $('.docmanager-file-actions').on('click', '.docmanager-action', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var action = $btn.data('action');
        var fileId = $btn.data('file-id');
        
        switch(action) {
            case 'preview':
                openFilePreview(fileId);
                break;
            case 'rename':
                renameFile(fileId);
                break;
            case 'move':
                moveFile(fileId);
                break;
            case 'copy':
                copyFile(fileId);
                break;
        }
    });
    
    /**
     * Apre anteprima file
     */
    function openFilePreview(fileId) {
        var modal = $('<div class="docmanager-modal"><div class="docmanager-modal-content"><span class="docmanager-modal-close">&times;</span><div class="docmanager-modal-body"></div></div></div>');
        
        $.ajax({
            url: docmanager_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'docmanager_get_file_preview',
                file_id: fileId,
                nonce: docmanager_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    modal.find('.docmanager-modal-body').html(response.data.html);
                    $('body').append(modal);
                    modal.fadeIn();
                }
            }
        });
        
        modal.on('click', '.docmanager-modal-close, .docmanager-modal', function(e) {
            if (e.target === this) {
                modal.fadeOut(function() {
                    modal.remove();
                });
            }
        });
    }
    
    /**
     * Cleanup al termine
     */
    $(window).on('beforeunload', function() {
        if (autoSaveInterval) {
            clearInterval(autoSaveInterval);
        }
    });
});

// Funzioni globali per compatibilità
window.docmanagerDeleteDocument = function(documentId) {
    if (confirm(docmanager_ajax.confirm_delete)) {
        jQuery.ajax({
            url: docmanager_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'docmanager_delete_document',
                document_id: documentId,
                nonce: docmanager_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Errore durante l\'eliminazione: ' + response.data);
                }
            },
            error: function() {
                alert('Errore di connessione');
            }
        });
    }
};