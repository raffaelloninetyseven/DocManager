// DocManager Frontend JavaScript (versione corretta)

jQuery(document).ready(function($) {
    
    initAccordion();
    initUploadForm();
    initFilters();
    initDocumentActions();
    
    /**
     * Inizializza funzionalità accordion
     */
    function initAccordion() {
        $('.docmanager-accordion-header').on('click', function() {
            var $item = $(this).closest('.docmanager-accordion-item');
            var $content = $item.find('.docmanager-accordion-content');
            
            $('.docmanager-accordion-item').not($item).removeClass('active');
            $('.docmanager-accordion-content').not($content).slideUp();
            
            $item.toggleClass('active');
            $content.slideToggle();
        });
    }
    
    /**
     * Inizializza form upload con validazione migliorata
     */
    function initUploadForm() {
        var $uploadForm = $('.docmanager-upload-form form');
        var $uploadBtn = $('.docmanager-upload-btn');
        var originalBtnText = $uploadBtn.text();
        
        $uploadForm.on('submit', function(e) {
            var isValid = validateForm($(this));
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            $uploadBtn.text(docmanager_ajax.uploading).prop('disabled', true);
            
            if (window.FormData && window.XMLHttpRequest) {
                e.preventDefault();
                uploadWithProgress(this);
            }
        });
        
        $uploadForm.on('reset', function() {
            $uploadBtn.text(originalBtnText).prop('disabled', false);
            removeMessages();
            clearValidationErrors();
        });
        
        $('.docmanager-field input[required], .docmanager-field select[required]').on('blur', function() {
            validateField($(this));
        });
        
        $('input[type="file"]').on('change', function() {
            validateFileInput($(this));
        });
    }
    
    /**
     * Validazione completa del form
     */
    function validateForm($form) {
        var isValid = true;
        
        $form.find('input[required], select[required]').each(function() {
            if (!validateField($(this))) {
                isValid = false;
            }
        });
        
        var $fileInput = $form.find('input[type="file"]');
        if ($fileInput.length && !validateFileInput($fileInput)) {
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Validazione singolo campo
     */
    function validateField($field) {
        var value = $field.val().trim();
        var isValid = true;
        
        $field.removeClass('docmanager-field-error');
        $field.next('.docmanager-error-message').remove();
        
        if ($field.prop('required') && !value) {
            showFieldError($field, 'Questo campo è obbligatorio');
            isValid = false;
        }
        
        if ($field.attr('type') === 'email' && value && !isValidEmail(value)) {
            showFieldError($field, 'Inserisci un indirizzo email valido');
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Validazione file input
     */
    function validateFileInput($fileInput) {
        var files = $fileInput[0].files;
        var isValid = true;
        
        $fileInput.removeClass('docmanager-field-error');
        $fileInput.next('.docmanager-error-message').remove();
        
        if (!files || files.length === 0) {
            if ($fileInput.prop('required')) {
                showFieldError($fileInput, 'Seleziona un file da caricare');
                isValid = false;
            }
            return isValid;
        }
        
        var file = files[0];
        var maxSize = 10 * 1024 * 1024; // 10MB default
        
        if (file.size > maxSize) {
            showFieldError($fileInput, 'Il file è troppo grande. Dimensione massima: 10MB');
            isValid = false;
        }
        
        var allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
        var fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (allowedTypes.indexOf(fileExtension) === -1) {
            showFieldError($fileInput, 'Tipo di file non supportato. Tipi consentiti: ' + allowedTypes.join(', '));
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Mostra errore campo
     */
    function showFieldError($field, message) {
        $field.addClass('docmanager-field-error');
        $field.after('<span class="docmanager-error-message">' + message + '</span>');
    }
    
    /**
     * Pulisce errori validazione
     */
    function clearValidationErrors() {
        $('.docmanager-field-error').removeClass('docmanager-field-error');
        $('.docmanager-error-message').remove();
    }
    
    /**
     * Upload con progress bar migliorato
     */
    function uploadWithProgress(form) {
        var formData = new FormData(form);
        var xhr = new XMLHttpRequest();
        
        var progressHtml = '<div class="docmanager-progress-wrapper">' +
                          '<div class="docmanager-progress-bar">' +
                          '<div class="docmanager-progress-fill" style="width: 0%"></div>' +
                          '</div>' +
                          '<div class="docmanager-progress-text">0%</div>' +
                          '</div>';
        
        $(form).find('.docmanager-upload-btn').after(progressHtml);
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                var percentComplete = Math.round((e.loaded / e.total) * 100);
                $('.docmanager-progress-fill').css('width', percentComplete + '%');
                $('.docmanager-progress-text').text(percentComplete + '%');
            }
        });
        
        xhr.onload = function() {
            $('.docmanager-progress-wrapper').remove();
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response && response.success !== undefined) {
                        if (response.success) {
                            showMessage(docmanager_ajax.upload_success, 'success');
                            $(form)[0].reset();
                        } else {
                            showMessage(response.data || docmanager_ajax.upload_error, 'error');
                        }
                    } else {
                        // Risposta HTML normale (non AJAX)
                        if (xhr.responseText.indexOf('docmanager-success') !== -1) {
                            showMessage(docmanager_ajax.upload_success, 'success');
                            $(form)[0].reset();
                        } else {
                            location.reload();
                        }
                    }
                } catch (e) {
                    // Probabilmente risposta HTML, ricaricare la pagina
                    location.reload();
                }
            } else {
                showMessage(docmanager_ajax.upload_error, 'error');
            }
            
            $('.docmanager-upload-btn').text('Carica Documento').prop('disabled', false);
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
        $('.docmanager-filters select').on('change', function() {
            var $form = $(this).closest('form');
            $form.submit();
        });
        
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
        $(document).on('click', '.docmanager-btn-danger', function(e) {
            if (!confirm(docmanager_ajax.confirm_delete)) {
                e.preventDefault();
                return false;
            }
        });
        
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
        
        $(document).on('click', '.docmanager-btn-download', function() {
            var $btn = $(this);
            var originalText = $btn.text();
            
            $btn.text('Download...');
            
            setTimeout(function() {
                $btn.text(originalText);
            }, 2000);
        });
    }
    
    /**
     * Utility functions
     */
    function showMessage(message, type) {
        removeMessages();
        
        var className = 'docmanager-' + (type || 'info');
        var messageHtml = '<div class="' + className + '">' + message + '</div>';
        
        $('.docmanager-upload-form, .docmanager-manage-widget, .docmanager-documents').first().prepend(messageHtml);
        
        setTimeout(function() {
            $('.' + className).fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    function removeMessages() {
        $('.docmanager-success, .docmanager-error, .docmanager-notice, .docmanager-info').remove();
    }
    
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
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
    
    makeTablesResponsive();
    initDragDrop();
    
    $(window).on('resize', function() {
        makeTablesResponsive();
    });
    
    if (typeof(Storage) !== "undefined") {
        $('.docmanager-accordion-header').on('click', function() {
            var accordionId = $(this).closest('.docmanager-accordion-item').index();
            var isActive = $(this).closest('.docmanager-accordion-item').hasClass('active');
            
            if (isActive) {
                localStorage.setItem('docmanager_accordion_' + accordionId, 'open');
            } else {
                localStorage.removeItem('docmanager_accordion_' + accordionId);
            }
        });
        
        $('.docmanager-accordion-item').each(function(index) {
            if (localStorage.getItem('docmanager_accordion_' + index) === 'open') {
                $(this).addClass('active');
                $(this).find('.docmanager-accordion-content').show();
            }
        });
    }
});

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