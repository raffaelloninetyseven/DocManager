jQuery(document).ready(function($) {
    
    // Upload Form Handler
    $('.docmanager-upload-form').on('submit', function(e) {
        e.preventDefault();
        handleFileUpload($(this));
    });
    
    // Drag and Drop functionality
    setupDragAndDrop();
    
    // Search functionality
    $('.docmanager-search-form').on('submit', function(e) {
        e.preventDefault();
        handleDocumentSearch($(this));
    });
    
    // File input change handler
    $('input[type="file"][name="doc_file"]').on('change', function() {
        const file = this.files[0];
        if (file) {
            validateFile(file);
            updateDropZoneText(file.name);
        }
    });
    
    function handleFileUpload(form) {
        const fileInput = form.find('input[type="file"]')[0];
        const file = fileInput.files[0];
        
        if (!file) {
            showMessage('error', 'Seleziona un file da caricare.');
            return;
        }
        
        if (!validateFile(file)) {
            return;
        }
        
        const formData = new FormData();
        const progressContainer = form.find('.docmanager-upload-progress');
        const submitBtn = form.find('.docmanager-upload-submit');
        const messagesDiv = form.find('.docmanager-upload-messages');
        
        // Aggiungi tutti i campi del form
        formData.append('action', 'docmanager_upload_frontend');
        formData.append('upload_nonce', form.find('input[name="upload_nonce"]').val());
        formData.append('doc_file', file);
        formData.append('doc_title', form.find('input[name="doc_title"]').val());
        formData.append('doc_description', form.find('textarea[name="doc_description"]').val());
        formData.append('doc_category', form.find('input[name="doc_category"]').val());
        formData.append('doc_tags', form.find('input[name="doc_tags"]').val());
        
        // Mostra progress bar
        progressContainer.show();
        submitBtn.prop('disabled', true).text('Caricamento...');
        messagesDiv.empty();
        
        $.ajax({
            url: docmanager_frontend.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        updateProgress(percentComplete);
                    }
                });
                return xhr;
            },
            success: function(response) {
                let data;
                try {
                    data = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    showMessage('error', 'Errore nel parsing della risposta.');
                    return;
                }
                
                if (data.success) {
                    showMessage('success', 'Documento caricato con successo!');
                    form[0].reset();
                    resetDropZone();
                    
                    // Redirect se specificato
                    if (docmanager_upload_widget && docmanager_upload_widget.redirect_url) {
                        setTimeout(function() {
                            window.location.href = docmanager_upload_widget.redirect_url;
                        }, 2000);
                    }
                } else {
                    showMessage('error', data.message || 'Errore durante il caricamento.');
                }
            },
            error: function() {
                showMessage('error', 'Errore di connessione. Riprova.');
            },
            complete: function() {
                progressContainer.hide();
                submitBtn.prop('disabled', false).text('Carica Documento');
                resetProgress();
            }
        });
    }
    
    function validateFile(file) {
        // Controlla dimensione
        const maxSize = docmanager_upload_widget ? 
            docmanager_upload_widget.max_size : 10 * 1024 * 1024;
        
        if (file.size > maxSize) {
            const maxSizeMB = Math.round(maxSize / (1024 * 1024));
            showMessage('error', `File troppo grande. Dimensione massima: ${maxSizeMB}MB`);
            return false;
        }
        
        // Controlla tipo file
        const allowedTypes = docmanager_upload_widget ? 
            docmanager_upload_widget.allowed_types : 
            ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(fileExtension)) {
            showMessage('error', `Tipo di file non consentito. Tipi permessi: ${allowedTypes.join(', ')}`);
            return false;
        }
        
        return true;
    }
    
    function setupDragAndDrop() {
        const dropZones = $('.docmanager-drop-zone');
        
        dropZones.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });
        
        dropZones.on('dragleave dragend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });
        
        dropZones.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = $(this).siblings('input[type="file"]')[0];
                fileInput.files = files;
                $(fileInput).trigger('change');
            }
        });
    }
    
    function handleDocumentSearch(form) {
        const searchTerm = form.find('input[name="search_term"]').val();
        const category = form.find('select[name="category"]').val() || '';
        
        $.ajax({
            url: docmanager_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'docmanager_search_documents',
                search_term: searchTerm,
                category: category,
                nonce: docmanager_frontend.nonce
            },
            success: function(response) {
                let data;
                try {
                    data = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    console.error('Errore parsing risposta ricerca');
                    return;
                }
                
                if (data.success) {
                    updateDocumentsList(data.documents);
                }
            },
            error: function() {
                console.error('Errore nella ricerca documenti');
            }
        });
    }
    
    function updateDocumentsList(documents) {
        const container = $('.docmanager-documents-wrapper');
        const layout = container.data('layout') || 'list';
        
        if (documents.length === 0) {
            container.html('<div class="docmanager-no-documents"><p>Nessun documento trovato.</p></div>');
            return;
        }
        
        let html = '';
        
        switch (layout) {
            case 'grid':
                html = '<div class="docmanager-documents-grid">';
                documents.forEach(function(doc) {
                    html += generateGridItem(doc);
                });
                html += '</div>';
                break;
                
            case 'table':
                html = generateTable(documents);
                break;
                
            case 'cards':
                html = '<div class="docmanager-documents-cards">';
                documents.forEach(function(doc) {
                    html += generateCardItem(doc);
                });
                html += '</div>';
                break;
                
            default:
                html = '<div class="docmanager-documents-list">';
                documents.forEach(function(doc) {
                    html += generateListItem(doc);
                });
                html += '</div>';
        }
        
        container.html(html);
    }
    
    function generateListItem(doc) {
        return `
            <div class="docmanager-document-item" data-doc-id="${doc.id}">
                <div class="docmanager-doc-header">
                    <h3 class="docmanager-doc-title">${escapeHtml(doc.title)}</h3>
                    ${doc.category ? `<span class="docmanager-doc-category">${escapeHtml(doc.category)}</span>` : ''}
                </div>
                ${doc.description ? `<div class="docmanager-doc-description">${escapeHtml(doc.description)}</div>` : ''}
                <div class="docmanager-doc-meta">
                    <span class="docmanager-doc-size">${formatFileSize(doc.file_size)}</span>
                    <span class="docmanager-doc-date">${formatDate(doc.upload_date)}</span>
                </div>
                <div class="docmanager-doc-actions">
                    <a href="${getDownloadUrl(doc.id)}" class="docmanager-btn docmanager-btn-download" target="_blank">
                        Download
                    </a>
                </div>
            </div>
        `;
    }
    
    function generateGridItem(doc) {
        return `
            <div class="docmanager-document-card" data-doc-id="${doc.id}">
                <div class="docmanager-card-icon">${getFileIcon(doc.file_type)}</div>
                <div class="docmanager-card-content">
                    <h4 class="docmanager-card-title">${escapeHtml(doc.title)}</h4>
                    ${doc.category ? `<span class="docmanager-card-category">${escapeHtml(doc.category)}</span>` : ''}
                    <div class="docmanager-card-meta">
                        <small>${formatFileSize(doc.file_size)}</small>
                    </div>
                </div>
                <div class="docmanager-card-actions">
                    <a href="${getDownloadUrl(doc.id)}" class="docmanager-btn docmanager-btn-download" target="_blank">
                        Download
                    </a>
                </div>
            </div>
        `;
    }
    
    function updateProgress(percent) {
        $('.progress-fill').css('width', percent + '%');
        $('.progress-percentage').text(Math.round(percent) + '%');
    }
    
    function resetProgress() {
        $('.progress-fill').css('width', '0%');
        $('.progress-percentage').text('0%');
    }
    
    function updateDropZoneText(fileName) {
        $('.docmanager-drop-zone p').text(`File selezionato: ${fileName}`);
    }
    
    function resetDropZone() {
        $('.docmanager-drop-zone p').text('Trascina e rilascia il tuo file qui, o clicca per sfogliare');
    }
    
    function showMessage(type, message) {
        const messagesDiv = $('.docmanager-upload-messages');
        const alertClass = type === 'success' ? 'success' : 'error';
        
        messagesDiv.html(`<div class="${alertClass}">${message}</div>`);
        
        // Auto-hide dopo 5 secondi
        setTimeout(function() {
            messagesDiv.empty();
        }, 5000);
    }
    
    // Utility functions
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
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('it-IT');
    }
    
    function getFileIcon(fileType) {
        const icons = {
            'application/pdf': '📄',
            'application/msword': '📝',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': '📝',
            'application/vnd.ms-excel': '📊',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': '📊',
            'image/jpeg': '🖼️',
            'image/jpg': '🖼️',
            'image/png': '🖼️'
        };
        return icons[fileType] || '📁';
    }
    
    function getDownloadUrl(documentId) {
        return `${window.location.origin}/?docmanager_download=${documentId}&nonce=${generateNonce(documentId)}`;
    }
    
    function generateNonce(documentId) {
        // Questa è una versione semplificata - in realtà il nonce viene generato lato server
        return docmanager_frontend.nonce;
    }
    
});