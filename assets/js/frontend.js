/**
 * DocManager Frontend JavaScript
 */

(function($) {
    'use strict';
    
    // Utility functions
    window.DocManager = {
        
        // Show loading state
        showLoading: function(container) {
            $(container).find('.docmanager-loading').show();
            $(container).find('.docmanager-table, .docmanager-documents-grid').hide();
        },
        
        // Hide loading state
        hideLoading: function(container) {
            $(container).find('.docmanager-loading').hide();
        },
        
        // Show success message
        showSuccess: function(container, message) {
            var html = '<div class="docmanager-message success">' + message + '</div>';
            $(container).find('.docmanager-messages').html(html);
            this.scrollToMessages(container);
        },
        
        // Show error message
        showError: function(container, message) {
            var html = '<div class="docmanager-message error">' + message + '</div>';
            $(container).find('.docmanager-messages').html(html);
            this.scrollToMessages(container);
        },
        
        // Scroll to messages
        scrollToMessages: function(container) {
            var $messages = $(container).find('.docmanager-messages');
            if ($messages.length) {
                $('html, body').animate({
                    scrollTop: $messages.offset().top - 100
                }, 500);
            }
        },
        
        // Format file size
        formatFileSize: function(bytes) {
            if (bytes >= 1073741824) {
                return (bytes / 1073741824).toFixed(2) + ' GB';
            } else if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + ' MB';
            } else if (bytes >= 1024) {
                return (bytes / 1024).toFixed(2) + ' KB';
            } else {
                return bytes + ' bytes';
            }
        },
        
        // Validate file before upload
        validateFile: function(file, allowedTypes, maxSize) {
            var fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (allowedTypes.indexOf(fileExtension) === -1) {
                return {
                    valid: false,
                    error: 'Tipo di file non consentito. Tipi permessi: ' + allowedTypes.join(', ')
                };
            }
            
            if (file.size > maxSize) {
                return {
                    valid: false,
                    error: 'File troppo grande. Dimensione massima: ' + this.formatFileSize(maxSize)
                };
            }
            
            return { valid: true };
        },
        
        // Handle AJAX errors
        handleAjaxError: function(xhr, status, error) {
            var message = 'Errore durante la comunicazione con il server';
            
            if (xhr.responseJSON && xhr.responseJSON.data) {
                message = xhr.responseJSON.data;
            } else if (xhr.responseText) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.data) {
                        message = response.data;
                    }
                } catch (e) {
                    // Use default message
                }
            }
            
            return message;
        },
        
        // Debounce function for search
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },
        
        // Initialize tooltips
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                var $this = $(this);
                var title = $this.data('tooltip');
                
                $this.hover(
                    function() {
                        var tooltip = $('<div class="docmanager-tooltip">' + title + '</div>');
                        $('body').append(tooltip);
                        
                        var offset = $this.offset();
                        tooltip.css({
                            top: offset.top - tooltip.outerHeight() - 5,
                            left: offset.left + ($this.outerWidth() / 2) - (tooltip.outerWidth() / 2)
                        });
                    },
                    function() {
                        $('.docmanager-tooltip').remove();
                    }
                );
            });
        },
        
        // Initialize file drag and drop
        initDragDrop: function(dropZone, fileInput, callback) {
            var $dropZone = $(dropZone);
            var $fileInput = $(fileInput);
            
            // Prevent default drag behaviors
            $(document).on('dragenter dragover drop', function(e) {
                e.preventDefault();
            });
            
            // Handle drag enter
            $dropZone.on('dragenter dragover', function(e) {
                e.preventDefault();
                $dropZone.addClass('dragover');
            });
            
            // Handle drag leave
            $dropZone.on('dragleave', function(e) {
                e.preventDefault();
                $dropZone.removeClass('dragover');
            });
            
            // Handle file drop
            $dropZone.on('drop', function(e) {
                e.preventDefault();
                $dropZone.removeClass('dragover');
                
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    $fileInput[0].files = files;
                    if (callback && typeof callback === 'function') {
                        callback(files[0]);
                    }
                }
            });
            
            // Handle click to open file dialog
            $dropZone.on('click', function() {
                $fileInput.click();
            });
        },
        
        // Initialize modal
        initModal: function(modalSelector) {
            var $modal = $(modalSelector);
            
            // Close modal on background click
            $modal.on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
            
            // Close modal on close button click
            $modal.find('.docmanager-close').on('click', function() {
                $modal.hide();
            });
            
            // Close modal on escape key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && $modal.is(':visible')) {
                    $modal.hide();
                }
            });
        },
        
        // Initialize confirm dialogs
        initConfirmDialogs: function() {
            $(document).on('click', '[data-confirm]', function(e) {
                var message = $(this).data('confirm');
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        },
        
        // Initialize search functionality
        initSearch: function(searchInput, searchButton, resetButton, callback) {
            var debouncedSearch = this.debounce(callback, 300);
            
            // Search on button click
            $(searchButton).on('click', function() {
                var searchTerm = $(searchInput).val().trim();
                if (searchTerm.length >= 2) {
                    callback(searchTerm);
                } else {
                    alert('Inserisci almeno 2 caratteri per la ricerca');
                }
            });
            
            // Search on enter key
            $(searchInput).on('keypress', function(e) {
                if (e.which === 13) {
                    $(searchButton).click();
                }
            });
            
            // Auto-search while typing (debounced)
            $(searchInput).on('input', function() {
                var searchTerm = $(this).val().trim();
                if (searchTerm.length >= 3) {
                    debouncedSearch(searchTerm);
                } else if (searchTerm.length === 0) {
                    callback(''); // Reset search
                }
            });
            
            // Reset search
            $(resetButton).on('click', function() {
                $(searchInput).val('');
                callback('');
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize tooltips
        DocManager.initTooltips();
        
        // Initialize confirm dialogs
        DocManager.initConfirmDialogs();
        
        // Initialize modals
        if ($('.docmanager-modal').length) {
            $('.docmanager-modal').each(function() {
                DocManager.initModal(this);
            });
        }
        
        // Auto-clear messages after 5 seconds
        setTimeout(function() {
            $('.docmanager-message').fadeOut();
        }, 5000);
        
        // Handle responsive tables
        $('.docmanager-table').each(function() {
            var $table = $(this);
            var $wrapper = $('<div class="table-responsive"></div>');
            $table.wrap($wrapper);
        });
        
        // Initialize smooth scrolling for anchor links
        $('a[href^="#"]').on('click', function(e) {
            var target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 500);
            }
        });
    });
    
})(jQuery);