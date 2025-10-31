/**
 * DocManager Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize drag and drop for file upload
        if ($('#document_file').length) {
            initFileUploadDragDrop();
        }
        
        // Initialize data tables enhancement
        if ($('.wp-list-table').length) {
            initTableEnhancements();
        }
        
        // Initialize settings validation
        if ($('.docmanager-settings-form').length) {
            initSettingsValidation();
        }
        
        // Initialize bulk actions
        initBulkActions();
        
        // Initialize search functionality
        initAdminSearch();
        
        // Initialize tooltips for admin
        initAdminTooltips();
    });
    
    // File upload drag and drop functionality
    function initFileUploadDragDrop() {
        var $fileInput = $('#document_file');
        var $form = $fileInput.closest('form');
        
        // Create drop zone
        var $dropZone = $('<div class="docmanager-dropzone" id="file-drop-zone">' +
            '<div class="docmanager-dropzone-text">📁 Trascina qui il file o clicca per selezionare</div>' +
            '<div class="docmanager-dropzone-subtext">Dimensione massima: ' + 
            (window.docmanager_admin ? window.docmanager_admin.max_file_size : '10MB') + '</div>' +
            '</div>');
        
        $fileInput.after($dropZone);
        $fileInput.hide();
        
        // Handle drag and drop
        $dropZone.on('dragenter dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });
        
        $dropZone.on('dragleave drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        });
        
        $dropZone.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
            
            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                $fileInput[0].files = files;
                updateDropZoneText(files[0].name);
            }
        });
        
        $dropZone.on('click', function() {
            $fileInput.click();
        });
        
        $fileInput.on('change', function() {
            if (this.files.length > 0) {
                updateDropZoneText(this.files[0].name);
            }
        });
        
        function updateDropZoneText(filename) {
            $dropZone.find('.docmanager-dropzone-text').html('✅ File selezionato: <strong>' + filename + '</strong>');
        }
    }
    
    // Table enhancements
    function initTableEnhancements() {
        // Add sorting capabilities
        $('.wp-list-table th').each(function() {
            var $th = $(this);
            if (!$th.hasClass('no-sort')) {
                $th.css('cursor', 'pointer').on('click', function() {
                    sortTable($th);
                });
            }
        });
        
        // Add row selection
        if ($('.wp-list-table tbody tr').length) {
            addRowSelection();
        }
        
        // Add filters
        addTableFilters();
    }
    
    function sortTable($header) {
        var table = $header.closest('table');
        var index = $header.index();
        var rows = table.find('tbody tr').toArray();
        var isAsc = $header.hasClass('sorted-asc');
        
        // Clear all sort classes
        $header.siblings().removeClass('sorted-asc sorted-desc');
        
        // Sort rows
        rows.sort(function(a, b) {
            var aVal = $(a).find('td').eq(index).text().trim();
            var bVal = $(b).find('td').eq(index).text().trim();
            
            // Try to parse as numbers
            var aNum = parseFloat(aVal);
            var bNum = parseFloat(bVal);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAsc ? bNum - aNum : aNum - bNum;
            } else {
                return isAsc ? bVal.localeCompare(aVal) : aVal.localeCompare(bVal);
            }
        });
        
        // Update header class
        $header.removeClass('sorted-asc sorted-desc').addClass(isAsc ? 'sorted-desc' : 'sorted-asc');
        
        // Rebuild table
        table.find('tbody').empty().append(rows);
    }
    
    function addRowSelection() {
        var $table = $('.wp-list-table');
        
        // Add header checkbox
        $table.find('thead tr').prepend('<th><input type="checkbox" id="select-all-docs"></th>');
        
        // Add row checkboxes
        $table.find('tbody tr').each(function() {
            var $row = $(this);
            var docId = $row.find('.btn-delete').data('id') || $row.find('.btn-edit').data('id');
            $row.prepend('<td><input type="checkbox" class="doc-checkbox" value="' + docId + '"></td>');
        });
        
        // Handle select all
        $('#select-all-docs').on('change', function() {
            $('.doc-checkbox').prop('checked', this.checked);
            updateBulkActions();
        });
        
        // Handle individual selection
        $('.doc-checkbox').on('change', function() {
            updateBulkActions();
            
            var totalBoxes = $('.doc-checkbox').length;
            var checkedBoxes = $('.doc-checkbox:checked').length;
            
            $('#select-all-docs').prop('indeterminate', checkedBoxes > 0 && checkedBoxes < totalBoxes);
            $('#select-all-docs').prop('checked', checkedBoxes === totalBoxes);
        });
    }
    
    function addTableFilters() {
        var $table = $('.wp-list-table');
        if (!$table.length) return;
        
        var $filterRow = $('<tr class="filter-row"></tr>');
        
        $table.find('thead th').each(function(index) {
            var $th = $(this);
            var $filter = $('<td></td>');
            
            if ($th.text().trim() === 'Utente') {
                $filter.html('<select class="table-filter" data-column="' + index + '">' +
                    '<option value="">Tutti gli utenti</option>' +
                    '</select>');
                
                // Populate user filter
                var users = [];
                $table.find('tbody tr').each(function() {
                    var user = $(this).find('td').eq(index).text().trim();
                    if (user && users.indexOf(user) === -1) {
                        users.push(user);
                    }
                });
                
                users.forEach(function(user) {
                    $filter.find('select').append('<option value="' + user + '">' + user + '</option>');
                });
                
            } else if ($th.text().trim() === 'Tipo File') {
                $filter.html('<select class="table-filter" data-column="' + index + '">' +
                    '<option value="">Tutti i tipi</option>' +
                    '</select>');
                
                // Populate file type filter
                var types = [];
                $table.find('tbody tr').each(function() {
                    var type = $(this).find('td').eq(index).text().trim();
                    if (type && types.indexOf(type) === -1) {
                        types.push(type);
                    }
                });
                
                types.forEach(function(type) {
                    $filter.find('select').append('<option value="' + type + '">' + type + '</option>');
                });
                
            } else if (index > 0) {
                $filter.html('<input type="text" class="table-filter" data-column="' + index + '" placeholder="Filtra...">');
            }
            
            $filterRow.append($filter);
        });
        
        $table.find('thead').append($filterRow);
        
        // Handle filtering
        $('.table-filter').on('change keyup', function() {
            filterTable();
        });
    }
    
    function filterTable() {
        var $table = $('.wp-list-table');
        var filters = {};
        
        $('.table-filter').each(function() {
            var column = $(this).data('column');
            var value = $(this).val().toLowerCase();
            if (value) {
                filters[column] = value;
            }
        });
        
        $table.find('tbody tr').each(function() {
            var $row = $(this);
            var show = true;
            
            Object.keys(filters).forEach(function(column) {
                var cellText = $row.find('td').eq(column).text().toLowerCase();
                if (cellText.indexOf(filters[column]) === -1) {
                    show = false;
                }
            });
            
            $row.toggle(show);
        });
    }
    
    // Bulk actions
    function initBulkActions() {
        if ($('.tablenav-top').length) {
            var $bulkActions = $('<div class="alignleft actions bulkactions">' +
                '<select id="bulk-action-selector-top">' +
                '<option value="">Azioni di gruppo</option>' +
                '<option value="delete">Elimina selezionati</option>' +
                '<option value="change-user">Cambia utente</option>' +
                '</select>' +
                '<button type="button" id="doaction" class="button action" disabled>Applica</button>' +
                '</div>');
            
            $('.tablenav-top').prepend($bulkActions);
        }
        
        $('#doaction').on('click', function() {
            var action = $('#bulk-action-selector-top').val();
            var selectedIds = $('.doc-checkbox:checked').map(function() {
                return this.value;
            }).get();
            
            if (!action || selectedIds.length === 0) {
                alert('Seleziona un\'azione e almeno un documento');
                return;
            }
            
            if (action === 'delete') {
                if (confirm('Sei sicuro di voler eliminare i ' + selectedIds.length + ' documenti selezionati?')) {
                    bulkDelete(selectedIds);
                }
            } else if (action === 'change-user') {
                showBulkUserChangeDialog(selectedIds);
            }
        });
    }
    
    function updateBulkActions() {
        var checkedCount = $('.doc-checkbox:checked').length;
        $('#doaction').prop('disabled', checkedCount === 0);
        
        if (checkedCount > 0) {
            $('#bulk-action-selector-top option:first').text('Azioni di gruppo (' + checkedCount + ' selezionati)');
        } else {
            $('#bulk-action-selector-top option:first').text('Azioni di gruppo');
        }
    }
    
    function bulkDelete(docIds) {
        var completed = 0;
        var total = docIds.length;
        
        showProgressDialog('Eliminazione in corso...', 0);
        
        docIds.forEach(function(docId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'docmanager_delete',
                    nonce: $('#docmanager_nonce').val(),
                    doc_id: docId
                },
                success: function() {
                    completed++;
                    updateProgressDialog(Math.round((completed / total) * 100));
                    
                    if (completed === total) {
                        hideProgressDialog();
                        location.reload();
                    }
                },
                error: function() {
                    completed++;
                    updateProgressDialog(Math.round((completed / total) * 100));
                    
                    if (completed === total) {
                        hideProgressDialog();
                        alert('Alcuni documenti non sono stati eliminati. Ricarica la pagina per vedere lo stato aggiornato.');
                    }
                }
            });
        });
    }
    
    function showBulkUserChangeDialog(docIds) {
        var $dialog = $('<div id="bulk-user-change-dialog" class="docmanager-modal">' +
            '<div class="docmanager-modal-content">' +
            '<span class="docmanager-close">&times;</span>' +
            '<h3>Cambia Utente per ' + docIds.length + ' Documenti</h3>' +
            '<form id="bulk-user-change-form">' +
            '<div class="form-group">' +
            '<label for="bulk-new-user">Nuovo Utente:</label>' +
            '<select id="bulk-new-user" name="user_id" required></select>' +
            '</div>' +
            '<div class="form-group">' +
            '<button type="submit">Cambia Utente</button>' +
            '<button type="button" class="docmanager-cancel">Annulla</button>' +
            '</div>' +
            '</form>' +
            '</div>' +
            '</div>');
        
        $('body').append($dialog);
        
        // Populate users dropdown
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'docmanager_get_users',
                nonce: $('#docmanager_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    response.data.users.forEach(function(user) {
                        $('#bulk-new-user').append('<option value="' + user.ID + '">' + user.display_name + '</option>');
                    });
                }
            }
        });
        
        $dialog.show();
        
        $('#bulk-user-change-form').on('submit', function(e) {
            e.preventDefault();
            var newUserId = $('#bulk-new-user').val();
            
            if (newUserId) {
                bulkChangeUser(docIds, newUserId);
                $dialog.remove();
            }
        });
        
        $dialog.find('.docmanager-close, .docmanager-cancel').on('click', function() {
            $dialog.remove();
        });
    }
    
    function bulkChangeUser(docIds, newUserId) {
        var completed = 0;
        var total = docIds.length;
        
        showProgressDialog('Aggiornamento utenti...', 0);
        
        docIds.forEach(function(docId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'docmanager_update',
                    nonce: $('#docmanager_nonce').val(),
                    doc_id: docId,
                    user_id: newUserId
                },
                success: function() {
                    completed++;
                    updateProgressDialog(Math.round((completed / total) * 100));
                    
                    if (completed === total) {
                        hideProgressDialog();
                        location.reload();
                    }
                },
                error: function() {
                    completed++;
                    updateProgressDialog(Math.round((completed / total) * 100));
                    
                    if (completed === total) {
                        hideProgressDialog();
                        alert('Alcuni documenti non sono stati aggiornati. Ricarica la pagina per vedere lo stato aggiornato.');
                    }
                }
            });
        });
    }
    
    // Progress dialog functions
    function showProgressDialog(title, progress) {
        var $dialog = $('<div id="progress-dialog" class="docmanager-modal">' +
            '<div class="docmanager-modal-content">' +
            '<h3>' + title + '</h3>' +
            '<div class="docmanager-upload-progress">' +
            '<div class="docmanager-upload-progress-bar">' +
            '<div class="docmanager-upload-progress-text">' + progress + '%</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>');
        
        $('body').append($dialog);
        $dialog.show();
        $('.docmanager-upload-progress').show();
        updateProgressDialog(progress);
    }
    
    function updateProgressDialog(progress) {
        $('#progress-dialog .docmanager-upload-progress-bar').css('width', progress + '%');
        $('#progress-dialog .docmanager-upload-progress-text').text(progress + '%');
    }
    
    function hideProgressDialog() {
        $('#progress-dialog').remove();
    }
    
    // Settings validation
    function initSettingsValidation() {
        $('form[action="options.php"]').on('submit', function(e) {
            var maxSize = parseInt($('input[name="docmanager_max_file_size"]').val());
            var allowedTypes = $('input[name="docmanager_allowed_types"]').val().trim();
            
            if (isNaN(maxSize) || maxSize <= 0) {
                alert('La dimensione massima del file deve essere un numero positivo');
                e.preventDefault();
                return false;
            }
            
            if (!allowedTypes) {
                alert('Devi specificare almeno un tipo di file consentito');
                e.preventDefault();
                return false;
            }
            
            // Validate file types format
            var types = allowedTypes.split(',');
            var validTypes = true;
            types.forEach(function(type) {
                if (!/^[a-zA-Z0-9]+$/.test(type.trim())) {
                    validTypes = false;
                }
            });
            
            if (!validTypes) {
                alert('I tipi di file devono essere separati da virgola e contenere solo lettere e numeri');
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Admin search
    function initAdminSearch() {
        var $searchInput = $('#admin-search-input');
        if ($searchInput.length) {
            var searchTimeout;
            
            $searchInput.on('input', function() {
                clearTimeout(searchTimeout);
                var searchTerm = $(this).val().toLowerCase();
                
                searchTimeout = setTimeout(function() {
                    $('.wp-list-table tbody tr').each(function() {
                        var $row = $(this);
                        var rowText = $row.text().toLowerCase();
                        $row.toggle(rowText.indexOf(searchTerm) !== -1);
                    });
                }, 300);
            });
        }
    }
    
    // Admin tooltips
    function initAdminTooltips() {
        $('[title]').each(function() {
            var $element = $(this);
            var title = $element.attr('title');
            
            $element.removeAttr('title').attr('data-tooltip', title);
            
            $element.hover(
                function() {
                    var $tooltip = $('<div class="docmanager-admin-tooltip">' + title + '</div>');
                    $('body').append($tooltip);
                    
                    var offset = $element.offset();
                    $tooltip.css({
                        top: offset.top - $tooltip.outerHeight() - 5,
                        left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2),
                        position: 'absolute',
                        background: '#333',
                        color: 'white',
                        padding: '5px 10px',
                        borderRadius: '4px',
                        fontSize: '12px',
                        zIndex: 9999
                    });
                },
                function() {
                    $('.docmanager-admin-tooltip').remove();
                }
            );
        });
    }
    
})(jQuery);

jQuery(document).ready(function($) {
    let searchTimeout;
    
    $('#dashboard-user-search').on('input', function() {
        clearTimeout(searchTimeout);
        const search = $(this).val();
        
        if (search.length < 2) {
            $('#user-search-results').hide();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: docmanagerAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'docmanager_search_users',
                    nonce: docmanagerAdmin.nonce,
                    search: search
                },
                success: function(response) {
                    if (response.success) {
                        displayUserResults(response.data);
                    }
                }
            });
        }, 300);
    });
    
    function displayUserResults(users) {
        const resultsDiv = $('#user-search-results');
        resultsDiv.empty();
        
        if (users.length === 0) {
            resultsDiv.html('<div class="no-results">Nessun utente trovato</div>');
            resultsDiv.show();
            return;
        }
        
        users.forEach(function(user) {
            if ($('.selected-user-tag[data-user-id="' + user.id + '"]').length > 0) {
                return;
            }
            
            const userItem = $('<div class="user-search-item"></div>');
            userItem.html('<div class="user-info"><strong>' + user.name + '</strong><span class="user-meta">' + user.email + '</span></div>');
            
            userItem.on('click', function() {
                addUser(user);
                $('#dashboard-user-search').val('');
                resultsDiv.hide();
            });
            
            resultsDiv.append(userItem);
        });
        
        resultsDiv.show();
    }
    
    function addUser(user) {
        const container = $('#selected-users-container');
        const userTag = $('<div class="selected-user-tag" data-user-id="' + user.id + '"></div>');
        
        userTag.html(
            '<span class="user-name">' + user.name + '</span>' +
            '<span class="user-email">(' + user.email + ')</span>' +
            '<button type="button" class="remove-user" onclick="removeUser(' + user.id + ')">' +
            '<span class="dashicons dashicons-no-alt"></span>' +
            '</button>' +
            '<input type="hidden" name="docmanager_dashboard_users[]" value="' + user.id + '">'
        );
        
        container.append(userTag);
    }
    
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.user-search-container').length) {
            $('#user-search-results').hide();
        }
    });
});

function removeUser(userId) {
    jQuery('.selected-user-tag[data-user-id="' + userId + '"]').remove();
}
