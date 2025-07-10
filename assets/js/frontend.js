// DocManager Frontend JavaScript

jQuery(document).ready(function($) {
    
    // Inizializzazione accordion
    initAccordion();
    
    // Gestione upload form
    initUploadForm();
    
    // Gestione filtri
    initFilters();
    
    // Gestione azioni documento
    initDocumentActions();
    
    /**
     * Inizializza funzionalità accordion
     */
    function initAccordion() {
        $('.docmanager-accordion-header').on('click', function() {
            var $item = $(this).closest('.docmanager-accordion-item');
            var $content = $item.find('.docmanager-accordion-content');
            
            // Chiudere altri accordion aperti
            $('.docmanager-accordion-item').not($item).removeClass('active');
            $('.docmanager-accordion-content').not($content).slideUp();
            
            // Toggle accordion corrente
            $item.toggleClass('active');
            $content.slideToggle();
        });
    }
    
    /**
     * Inizializza form upload
     */
    function initUploadForm() {
        var $uploadForm = $('.docmanager-upload-form form');
        var $uploadBtn = $('.docmanager-upload-btn');
        var originalBtnText = $uploadBtn.text();
        
        $uploadForm.on('submit', function(e) {
            // Validazione file
            var fileInput = $uploadForm.find('input[type="file"]')[0];
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Seleziona un file da caricare');
                e.preventDefault();
                return false;
            }
            
            // Validazione dimensione file
            var file = fileInput.files[0];
            var maxSize = 10 * 1024 * 1024; // 10MB default
            
            if (file.size > maxSize) {
                alert('Il file è troppo grande. Dimensione massima: 10MB');
                e.preventDefault();
                return false;
            }
            
            // Validazione tipo file
            var allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
            var fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (allowedTypes.indexOf(fileExtension) === -1) {
                alert('Tipo di file non supportato. Tipi consentiti: ' + allowedTypes.join(', '));
                e.preventDefault();
                return false;
            }
            
            // Aggiornare testo pulsante
            $uploadBtn.text(docmanager_ajax.uploading).prop('disabled', true);
            
            // Aggiungere progress bar se supportato
            if (window.FormData && window.XMLHttpRequest) {
                e.preventDefault();
                uploadWithProgress(this);
            }
        });
        
        // Reset form dopo submit
        $uploadForm.on('reset', function() {
            $uploadBtn.text(originalBtnText).prop('disabled', false);
            removeMessages();
        });
    }
    
    /**
     * Upload con progress bar
     */
    function uploadWithProgress(form) {
        var formData = new FormData(form);
        var xhr = new XMLHttpRequest();
        
        // Creare progress bar
        var progressHtml = '<div class="docmanager-progress-wrapper">' +
                          '<div class="docmanager-progress-bar">' +
                          '<div class="docmanager-progress-fill" style="width: 0%"></div>' +
                          '</div>' +
                          '<div class="docmanager-progress-text">0%</div>' +
                          '</div>';
        
        $(form).find('.docmanager-upload-btn').after(progressHtml);
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                var percentComplete = (e.loaded / e.total) * 100;
                $('.docmanager-progress-fill').css('width', percentComplete + '%');
                $('.docmanager-progress-text').text(Math.round(percentComplete) + '%');
            }
        });
        
        xhr.onload = function() {
            $('.docmanager-progress-wrapper').remove();
            
            if (xhr.status === 200) {
                // Ricaricare la pagina o mostrare messaggio successo
                location.reload();
            } else {
                showMessage('Errore durante il caricamento', 'error');
                $('.docmanager-upload-btn').text('Carica Documento').prop('disabled', false);
            }
        };
        
        xhr.onerror = function() {
            $('.docmanager-progress-wrapper').remove();
            showMessage('Errore di connessione', 'error');
            $('.docmanager-upload-btn').text('Carica Documento').prop('disabled', false);
        };
        
        xhr.open('POST', form.action || window.location.href);
        xhr.send(formData);
    }
    
    /**
     * Inizializza filtri
     */
    function initFilters() {
        // Auto-submit filtri quando cambia selezione
        $('.docmanager-filters select').on('change', function() {
            var $form = $(this).closest('form');
            $form.submit();
        });
        
        // Ricerca in tempo reale (se presente campo di ricerca)
        var searchTimeout;
        $('.docmanager-search').on('input', function() {
            clearTimeout(searchTimeout);
            var $this = $(this);
            
            searchTimeout = setTimeout(function() {
                var searchTerm = $this.val();
                filterDocuments(searchTerm);
            }, 300);
        });
    }
    
    /**
     * Filtra documenti in tempo reale
     */
    function filterDocuments(searchTerm) {
        var $documents = $('.docmanager-document-item, .docmanager-document-card');
        
        if (!searchTerm) {
            $documents.show();
            return;
        }
        
        $documents.each(function() {
            var $doc = $(this);
            var title = $doc.find('.docmanager-document-title').text().toLowerCase();
            var meta = $doc.find('.docmanager-document-meta').text().toLowerCase();
            
            if (title.indexOf(searchTerm.toLowerCase()) !== -1 || 
                meta.indexOf(searchTerm.toLowerCase()) !== -1) {
                $doc.show();
            } else {
                $doc.hide();
            }
        });
    }
    
    /**
     * Inizializza azioni documento
     */
    function initDocumentActions() {
        // Conferma eliminazione
        $(document).on('click', '.docmanager-btn-danger', function(e) {
            if (!confirm('Sei sicuro di voler eliminare questo documento?')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Gestione toggle stato
        $(document).on('click', '.docmanager-toggle-status', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var documentId = $btn.data('document-id');
            var newStatus = $btn.data('new-status');
            
            $.ajax({
                url: docmanager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'docmanager_toggle_status',
                    document_id: documentId,
                    new_status: newStatus,
                    nonce: docmanager_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        showMessage('Errore durante l\'aggiornamento dello stato', 'error');
                    }
                },
                error: function() {
                    showMessage('Errore di connessione', 'error');
                }
            });
        });
        
        // Gestione download
        $(document).on('click', '.docmanager-btn-download', function() {
            var $btn = $(this);
            var originalText = $btn.text();
            
            $btn.text('Downloading...');
            
            // Ripristinare testo dopo 2 secondi
            setTimeout(function() {
                $btn.text(originalText);
            }, 2000);
        });
    }
    
    /**
     * Mostra messaggio
     */
    function showMessage(message, type) {
        removeMessages();
        
        var className = 'docmanager-' + (type || 'success');
        var messageHtml = '<div class="' + className + '">' + message + '</div>';
        
        $('.docmanager-upload-form, .docmanager-manage-widget').first().prepend(messageHtml);
        
        // Auto-hide dopo 5 secondi
        setTimeout(function() {
            $('.' + className).fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Rimuove messaggi esistenti
     */
    function removeMessages() {
        $('.docmanager-success, .docmanager-error, .docmanager-notice').remove();
    }
    
    /**
     * Gestione responsive per tabelle
     */
    function makeTablesResponsive() {
        $('.docmanager-table-wrapper').each(function() {
            var $wrapper = $(this);
            var $table = $wrapper.find('table');
            
            if ($table.outerWidth() > $wrapper.width()) {
                $wrapper.addClass('is-scrollable');
            }
        });
    }
    
    /**
     * Inizializza tooltips (se libreria disponibile)
     */
    function initTooltips() {
        if (typeof $.fn.tooltip === 'function') {
            $('.docmanager-btn[title], .docmanager-status[title]').tooltip({
                placement: 'top',
                container: 'body'
            });
        }
    }
    
    /**
     * Gestione drag & drop per upload
     */
    function initDragDrop() {
        var $uploadArea = $('.docmanager-upload-form');
        var $fileInput = $uploadArea.find('input[type="file"]');
        
        if ($uploadArea.length && $fileInput.length) {
            $uploadArea.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
            });
            
            $uploadArea.on('dragleave dragend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
            });
            
            $uploadArea.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
                
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    $fileInput[0].files = files;
                    $fileInput.trigger('change');
                }
            });
        }
    }
    
    /**
     * Gestione lazy loading per documenti
     */
    function initLazyLoading() {
        var $loadMoreBtn = $('.docmanager-load-more');
        
        if ($loadMoreBtn.length) {
            $loadMoreBtn.on('click', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var page = $btn.data('page') || 2;
                var originalText = $btn.text();
                
                $btn.text('Caricamento...').prop('disabled', true);
                
                $.ajax({
                    url: docmanager_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'docmanager_load_more',
                        page: page,
                        nonce: docmanager_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            $('.docmanager-documents').append(response.data.html);
                            
                            if (response.data.has_more) {
                                $btn.data('page', page + 1)
                                    .text(originalText)
                                    .prop('disabled', false);
                            } else {
                                $btn.hide();
                            }
                        } else {
                            $btn.text('Errore').prop('disabled', true);
                        }
                    },
                    error: function() {
                        $btn.text('Errore').prop('disabled', true);
                    }
                });
            });
        }
    }
    
    // Inizializzazioni finali
    makeTablesResponsive();
    initTooltips();
    initDragDrop();
    initLazyLoading();
    
    // Gestione ridimensionamento finestra
    $(window).on('resize', function() {
        makeTablesResponsive();
    });
    
    // Gestione stato accordion tramite localStorage
    if (typeof(Storage) !== "undefined") {
        // Salvare stato accordion
        $('.docmanager-accordion-header').on('click', function() {
            var accordionId = $(this).closest('.docmanager-accordion-item').index();
            var isActive = $(this).closest('.docmanager-accordion-item').hasClass('active');
            
            if (isActive) {
                localStorage.setItem('docmanager_accordion_' + accordionId, 'open');
            } else {
                localStorage.removeItem('docmanager_accordion_' + accordionId);
            }
        });
        
        // Ripristinare stato accordion
        $('.docmanager-accordion-item').each(function(index) {
            if (localStorage.getItem('docmanager_accordion_' + index) === 'open') {
                $(this).addClass('active');
                $(this).find('.docmanager-accordion-content').show();
            }
        });
    }
    
    // Gestione form validation in tempo reale
    $('.docmanager-upload-form input[required], .docmanager-upload-form select[required]').on('blur', function() {
        var $field = $(this);
        var value = $field.val();
        
        if (!value) {
            $field.addClass('error');
            if (!$field.next('.error-message').length) {
                $field.after('<span class="error-message">Questo campo è obbligatorio</span>');
            }
        } else {
            $field.removeClass('error');
            $field.next('.error-message').remove();
        }
    });
});

// Funzioni globali per compatibilità
window.docmanagerDeleteDocument = function(documentId) {
    if (confirm('Sei sicuro di voler eliminare questo documento?')) {
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

window.docmanagerToggleStatus = function(documentId, newStatus) {
    jQuery.ajax({
        url: docmanager_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'docmanager_toggle_status',
            document_id: documentId,
            new_status: newStatus,
            nonce: docmanager_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Errore durante l\'aggiornamento: ' + response.data);
            }
        },
        error: function() {
            alert('Errore di connessione');
        }
    });
};