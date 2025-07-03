jQuery(document).ready(function($) {
    'use strict';
    
    // Inizializzazione upload avanzato
    initAdvancedUpload();
    
    function initAdvancedUpload() {
        initDragAndDrop();
        initFileValidation();
        initUploadProgress();
        initFormSubmission();
    }
    
    // Gestione Drag & Drop avanzata
    function initDragAndDrop() {
        let dragCounter = 0;
        
        $('.docmanager-drop-zone').each(function() {
            const dropZone = $(this);
            const fileInput = dropZone.siblings('input[type="file"]');
            
            // Prevent default behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone[0].addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });
            
            // Highlight drop zone when item is dragged over it
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone[0].addEventListener(eventName, () => {
                    dragCounter++;
                    dropZone.addClass('dragover');
                }, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone[0].addEventListener(eventName, () => {
                    dragCounter--;
                    if (dragCounter <= 0) {
                        dragCounter = 0;
                        dropZone.removeClass('dragover');
                    }
                }, false);
            });
            
            // Handle dropped files
            dropZone[0].addEventListener('drop', function(e) {
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelection(files[0], fileInput, dropZone);
                }
            }, false);
            
            // Handle click to browse
            dropZone.on('click', function() {
                fileInput.click();
            });
            
            // Handle file input change
            fileInput.on('change', function() {
                const file = this.files[0];
                if (file) {
                    handleFileSelection(file, $(this), dropZone);
                }
            });
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
    }
    
    function handleFileSelection(file, fileInput, dropZone) {
        // Validate file
        const validation = validateFile(file);
        
        if (!validation.valid) {
            showFileError(validation.message, dropZone);
            return;
        }
        
        // Update UI
        updateDropZoneUI(file, dropZone);
        
        // Set file to input (for form submission)
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput[0].files = dataTransfer.files;
        
        // Auto-fill title if empty
        const titleField = fileInput.closest('form').find('#doc_title');
        if (titleField.length && !titleField.val()) {
            const fileName = file.name.replace(/\.[^/.]+$/, ""); // Remove extension
            titleField.val(fileName);
        }
        
        // Show file preview if applicable
        showFilePreview(file, dropZone);
    }
    
    function validateFile(file) {
        const settings = window.docmanager_upload_widget || {};
        const maxSize = settings.max_size || (10 * 1024 * 1024); // 10MB default
        const allowedTypes = settings.allowed_types || ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        
        // Check file size
        if (file.size > maxSize) {
            return {
                valid: false,
                message: `File troppo grande. Dimensione massima: ${formatFileSize(maxSize)}`
            };
        }
        
        // Check file type
        const fileExtension = file.name.split('.').pop().toLowerCase();
        if (!allowedTypes.includes(fileExtension)) {
            return {
                valid: false,
                message: `Tipo di file non supportato. Tipi consentiti: ${allowedTypes.join(', ')}`
            };
        }
        
        // Check if file is empty
        if (file.size === 0) {
            return {
                valid: false,
                message: 'Il file selezionato è vuoto'
            };
        }
        
        return { valid: true };
    }
    
    function updateDropZoneUI(file, dropZone) {
        dropZone.addClass('file-selected');
        dropZone.find('p').text(`File selezionato: ${file.name}`);
        dropZone.find('small').text(`Dimensione: ${formatFileSize(file.size)}`);
        
        // Add file icon
        const fileIcon = getFileIcon(file.type, file.name);
        dropZone.find('.docmanager-drop-icon').text(fileIcon);
        
        // Add remove file option
        if (!dropZone.find('.docmanager-remove-file').length) {
            const removeBtn = $('<button type="button" class="docmanager-remove-file" style="position: absolute; top: 10px; right: 10px; background: #d63638; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer;">✕</button>');
            removeBtn.on('click', function(e) {
                e.stopPropagation();
                resetDropZone(dropZone);
            });
            dropZone.css('position', 'relative').append(removeBtn);
        }
    }
    
    function resetDropZone(dropZone) {
        dropZone.removeClass('file-selected dragover');
        dropZone.find('p').text('Trascina qui il file o clicca per sfogliare');
        dropZone.find('small').text('Dimensione massima e tipi consentiti...');
        dropZone.find('.docmanager-drop-icon').text('📁');
        dropZone.find('.docmanager-remove-file').remove();
        dropZone.find('.docmanager-file-preview').remove();
        
        // Reset file input
        const fileInput = dropZone.siblings('input[type="file"]');
        fileInput.val('');
    }
    
    function showFilePreview(file, dropZone) {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = $(`
                    <div class="docmanager-file-preview" style="margin-top: 10px;">
                        <img src="${e.target.result}" alt="Preview" style="max-width: 100px; max-height: 100px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                `);
                dropZone.find('.docmanager-file-preview').remove();
                dropZone.append(preview);
            };
            reader.readAsDataURL(file);
        }
    }
    
    function showFileError(message, dropZone) {
        const errorDiv = $(`<div class="docmanager-file-error" style="color: #d63638; font-size: 12px; margin-top: 5px; padding: 5px; background: #f8d7da; border-radius: 3px;">${message}</div>`);
        
        // Remove existing errors
        dropZone.find('.docmanager-file-error').remove();
        dropZone.after(errorDiv);
        
        // Auto-remove error after 5 seconds
        setTimeout(() => {
            errorDiv.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Gestione validazione form
    function initFileValidation() {
        $('.docmanager-upload-form').on('submit', function(e) {
            const form = $(this);
            const fileInput = form.find('input[type="file"]');
            const file = fileInput[0].files[0];
            
            if (!file) {
                e.preventDefault();
                showMessage('error', 'Seleziona un file da caricare', form.find('.docmanager-upload-messages'));
                return false;
            }
            
            const validation = validateFile(file);
            if (!validation.valid) {
                e.preventDefault();
                showMessage('error', validation.message, form.find('.docmanager-upload-messages'));
                return false;
            }
            
            return true;
        });
    }
    
    // Gestione progress bar
    function initUploadProgress() {
        $('.docmanager-upload-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const formData = new FormData(this);
            formData.append('action', 'docmanager_upload_frontend');
            
            uploadWithProgress(form, formData);
        });
    }
    
    function uploadWithProgress(form, formData) {
        const submitBtn = form.find('.docmanager-upload-submit');
        const progressContainer = form.find('.docmanager-upload-progress');
        const progressBar = progressContainer.find('.progress-fill');
        const progressText = progressContainer.find('.progress-percentage');
        const messagesContainer = form.find('.docmanager-upload-messages');
        
        // Reset UI
        messagesContainer.empty();
        submitBtn.prop('disabled', true).text('Caricamento...');
        progressContainer.show();
        progressBar.css('width', '0%');
        progressText.text('0%');
        
        // Create XMLHttpRequest for progress tracking
        const xhr = new XMLHttpRequest();
        
        // Track upload progress
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                progressBar.css('width', percentComplete + '%');
                progressText.text(percentComplete + '%');
            }
        });
        
        // Handle completion
        xhr.addEventListener('load', function() {
            try {
                const response = JSON.parse(xhr.responseText);
                handleUploadResponse(response, form, submitBtn, progressContainer, messagesContainer);
            } catch (error) {
                handleUploadError('Errore di parsing della risposta', form, submitBtn, progressContainer, messagesContainer);
            }
        });
        
        // Handle network errors
        xhr.addEventListener('error', function() {
            handleUploadError('Errore di connessione', form, submitBtn, progressContainer, messagesContainer);
        });
        
        // Handle timeout
        xhr.addEventListener('timeout', function() {
            handleUploadError('Timeout durante il caricamento', form, submitBtn, progressContainer, messagesContainer);
        });
        
        // Configure and send request
        xhr.timeout = 300000; // 5 minutes timeout
        xhr.open('POST', (window.docmanager_upload_widget && window.docmanager_upload_widget.ajax_url) || window.ajaxurl || '/wp-admin/admin-ajax.php');
        xhr.send(formData);
    }
    
    function handleUploadResponse(data, form, submitBtn, progressContainer, messagesContainer) {
        if (data.success) {
            showMessage('success', 'Documento caricato con successo!', messagesContainer);
            
            // Reset form
            form[0].reset();
            resetAllDropZones(form);
            
            // Redirect if specified
            const settings = window.docmanager_upload_widget || {};
            if (settings.redirect_url) {
                setTimeout(() => {
                    window.location.href = settings.redirect_url;
                }, 1500);
            }
            
            // Trigger custom event
            $(document).trigger('docmanager:upload:success', [data]);
            
        } else {
            showMessage('error', data.message || 'Errore durante il caricamento', messagesContainer);
        }
        
        // Reset UI
        resetUploadUI(submitBtn, progressContainer);
    }
    
    function handleUploadError(message, form, submitBtn, progressContainer, messagesContainer) {
        showMessage('error', message, messagesContainer);
        resetUploadUI(submitBtn, progressContainer);
        
        // Trigger custom event
        $(document).trigger('docmanager:upload:error', [message]);
    }
    
    function resetUploadUI(submitBtn, progressContainer) {
        submitBtn.prop('disabled', false).text('Carica Documento');
        progressContainer.hide();
    }
    
    function resetAllDropZones(form) {
        form.find('.docmanager-drop-zone').each(function() {
            resetDropZone($(this));
        });
    }
    
    // Gestione invio form avanzato
    function initFormSubmission() {
        // Form validation in real-time
        $('.docmanager-upload-form input[required], .docmanager-upload-form textarea[required]').on('blur', function() {
            validateField($(this));
        });
        
        // Character counter for text fields
        $('.docmanager-upload-form input[type="text"], .docmanager-upload-form textarea').on('input', function() {
            updateCharacterCounter($(this));
        });
    }
    
    function validateField(field) {
        const value = field.val().trim();
        const fieldName = field.attr('name');
        let isValid = true;
        let message = '';
        
        // Required field validation
        if (field.prop('required') && !value) {
            isValid = false;
            message = 'Questo campo è obbligatorio';
        }
        
        // Specific field validations
        switch (fieldName) {
            case 'doc_title':
                if (value && value.length < 3) {
                    isValid = false;
                    message = 'Il titolo deve essere di almeno 3 caratteri';
                } else if (value && value.length > 255) {
                    isValid = false;
                    message = 'Il titolo non può superare i 255 caratteri';
                }
                break;
            case 'doc_description':
                if (value && value.length > 1000) {
                    isValid = false;
                    message = 'La descrizione non può superare i 1000 caratteri';
                }
                break;
        }
        
        // Update field UI
        updateFieldValidation(field, isValid, message);
        
        return isValid;
    }
    
    function updateFieldValidation(field, isValid, message) {
        const formRow = field.closest('.docmanager-form-row');
        
        // Remove existing validation messages
        formRow.find('.docmanager-field-error').remove();
        formRow.removeClass('field-error field-valid');
        
        if (!isValid) {
            formRow.addClass('field-error');
            const errorDiv = $(`<div class="docmanager-field-error" style="color: #d63638; font-size: 12px; margin-top: 5px;">${message}</div>`);
            field.after(errorDiv);
        } else if (field.val().trim()) {
            formRow.addClass('field-valid');
        }
    }
    
    function updateCharacterCounter(field) {
        const maxLength = field.attr('maxlength');
        if (!maxLength) return;
        
        const currentLength = field.val().length;
        const remaining = maxLength - currentLength;
        
        let counterElement = field.siblings('.character-counter');
        if (!counterElement.length) {
            counterElement = $('<div class="character-counter" style="font-size: 11px; color: #666; text-align: right; margin-top: 2px;"></div>');
            field.after(counterElement);
        }
        
        counterElement.text(`${currentLength}/${maxLength}`);
        
        if (remaining < 10) {
            counterElement.css('color', '#d63638');
        } else if (remaining < 50) {
            counterElement.css('color', '#dba617');
        } else {
            counterElement.css('color', '#666');
        }
    }
    
    // Utility functions
    function showMessage(type, message, container) {
        const messageHtml = `<div class="docmanager-message ${type}" style="padding: 10px 15px; border-radius: 4px; margin-bottom: 10px; ${getMessageStyles(type)}">${escapeHtml(message)}</div>`;
        container.html(messageHtml);
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(function() {
                container.find('.docmanager-message').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Scroll to message if not visible
        if (!isElementInViewport(container[0])) {
            container[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    function getMessageStyles(type) {
        const styles = {
            'success': 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;',
            'error': 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;',
            'info': 'background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;'
        };
        return styles[type] || styles.info;
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function getFileIcon(fileType, fileName) {
        const icons = {
            'application/pdf': '📄',
            'application/msword': '📝',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': '📝',
            'application/vnd.ms-excel': '📊',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': '📊',
            'image/jpeg': '🖼️',
            'image/jpg': '🖼️',
            'image/png': '🖼️',
            'image/gif': '🖼️',
            'text/plain': '📄',
            'application/zip': '📦'
        };
        
        // Try by MIME type first
        if (icons[fileType]) {
            return icons[fileType];
        }
        
        // Try by file extension
        const extension = fileName.split('.').pop().toLowerCase();
        const extensionIcons = {
            'pdf': '📄',
            'doc': '📝',
            'docx': '📝',
            'xls': '📊',
            'xlsx': '📊',
            'jpg': '🖼️',
            'jpeg': '🖼️',
            'png': '🖼️',
            'gif': '🖼️',
            'txt': '📄',
            'zip': '📦'
        };
        
        return extensionIcons[extension] || '📁';
    }
    
    function isElementInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Paste upload (Ctrl+V)
    function initPasteUpload() {
        $(document).on('paste', '.docmanager-upload-form', function(e) {
            const items = e.originalEvent.clipboardData.items;
            
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                
                if (item.type.indexOf('image') !== -1) {
                    const file = item.getAsFile();
                    const form = $(e.target).closest('.docmanager-upload-form');
                    const dropZone = form.find('.docmanager-drop-zone');
                    const fileInput = form.find('input[type="file"]');
                    
                    handleFileSelection(file, fileInput, dropZone);
                    
                    // Show paste success message
                    const messagesContainer = form.find('.docmanager-upload-messages');
                    showMessage('info', 'Immagine incollata con successo!', messagesContainer);
                    break;
                }
            }
        });
    }
    
    // Initialize paste upload
    initPasteUpload();
    
    // Expose public API
    window.DocManagerUpload = {
        validateFile: validateFile,
        formatFileSize: formatFileSize,
        resetDropZone: resetDropZone,
        showMessage: showMessage
    };
    
    // Custom events
    $(document).on('docmanager:upload:success', function(e, data) {
        // Refresh any document lists on the page
        $('.docmanager-documents-wrapper').trigger('refresh');
    });
    
    // Global error handler
    window.addEventListener('error', function(e) {
        if (e.filename && e.filename.includes('upload.js')) {
            console.error('DocManager Upload Error:', e.error);
        }
    });
    
    // Initialize tooltips if available
    if (typeof $.fn.tooltip === 'function') {
        $('.docmanager-upload-form [title]').tooltip();
    }
});