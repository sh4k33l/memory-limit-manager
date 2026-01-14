/**
 * Memory Limit Manager - Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        /**
         * Check URL parameters and scroll to manual config if needed
         */
        var urlParams = new URLSearchParams(window.location.search);
        var status = urlParams.get('status');
        
        if (status === 'write_failed' || status === 'error') {
            // Scroll to error notice first
            setTimeout(function() {
                if ($('#mlm-error-notice').length > 0) {
                    $('html, body').animate({
                        scrollTop: $('#mlm-error-notice').offset().top - 100
                    }, 500, function() {
                        // Then after 2 seconds, scroll to manual config if it exists
                        setTimeout(function() {
                            if ($('#mlm-manual-config').length > 0) {
                                $('html, body').animate({
                                    scrollTop: $('#mlm-manual-config').offset().top - 80
                                }, 800, function() {
                                    // Highlight the manual config box
                                    $('#mlm-manual-config').addClass('mlm-highlight-box');
                                    setTimeout(function() {
                                        $('#mlm-manual-config').removeClass('mlm-highlight-box');
                                    }, 3000);
                                });
                            }
                        }, 2000);
                    });
                }
            }, 300);
        } else if (status === 'success') {
            // Scroll to success notice
            setTimeout(function() {
                if ($('.mlm-notice-success').length > 0) {
                    $('html, body').animate({
                        scrollTop: $('.mlm-notice-success').offset().top - 100
                    }, 500);
                }
            }, 300);
        }
        
        /**
         * Preset buttons functionality
         */
        $('.mlm-preset-btn').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var memoryValue = $btn.data('memory');
            var maxMemoryValue = $btn.data('max');
            
            // Animate the button
            $btn.addClass('mlm-preset-active');
            setTimeout(function() {
                $btn.removeClass('mlm-preset-active');
            }, 300);
            
            // Set the values with animation
            $('#wp_memory_limit').val(memoryValue).addClass('mlm-input-flash');
            $('#wp_max_memory_limit').val(maxMemoryValue).addClass('mlm-input-flash');
            
            setTimeout(function() {
                $('#wp_memory_limit, #wp_max_memory_limit').removeClass('mlm-input-flash');
            }, 500);
            
            // Validate the values
            validateMemoryValues();
        });
        
        /**
         * Real-time validation
         */
        $('#wp_memory_limit, #wp_max_memory_limit').on('input', function() {
            validateMemoryValues();
        });
        
        /**
         * Validate memory values
         */
        function validateMemoryValues() {
            var $memoryLimit = $('#wp_memory_limit');
            var $maxMemoryLimit = $('#wp_max_memory_limit');
            var $submitBtn = $('.mlm-submit-btn');
            
            var memoryValue = $memoryLimit.val().trim();
            var maxMemoryValue = $maxMemoryLimit.val().trim();
            
            var errors = [];
            
            // Validate format
            if (!isValidMemoryFormat(memoryValue)) {
                $memoryLimit.addClass('mlm-input-error');
                errors.push('Invalid WP Memory Limit format');
            } else {
                $memoryLimit.removeClass('mlm-input-error');
            }
            
            if (!isValidMemoryFormat(maxMemoryValue)) {
                $maxMemoryLimit.addClass('mlm-input-error');
                errors.push('Invalid WP Max Memory Limit format');
            } else {
                $maxMemoryLimit.removeClass('mlm-input-error');
            }
            
            // Check if max is greater than regular
            if (errors.length === 0) {
                var memoryBytes = parseMemoryValue(memoryValue);
                var maxMemoryBytes = parseMemoryValue(maxMemoryValue);
                
                if (maxMemoryBytes < memoryBytes) {
                    $maxMemoryLimit.addClass('mlm-input-error');
                    errors.push('Max Memory must be >= Memory Limit');
                }
            }
            
            // Enable/disable submit button
            if (errors.length > 0) {
                $submitBtn.prop('disabled', true).addClass('disabled');
            } else {
                $submitBtn.prop('disabled', false).removeClass('disabled');
            }
            
            return errors.length === 0;
        }
        
        /**
         * Check if memory format is valid
         */
        function isValidMemoryFormat(value) {
            return /^\d+[MG]$/i.test(value);
        }
        
        /**
         * Parse memory value to bytes
         */
        function parseMemoryValue(value) {
            var unit = value.slice(-1).toUpperCase();
            var number = parseInt(value.slice(0, -1), 10);
            
            if (unit === 'G') {
                return number * 1024 * 1024 * 1024;
            } else if (unit === 'M') {
                return number * 1024 * 1024;
            }
            
            return 0;
        }
        
        /**
         * Form submission with loading state
         */
        $('#memory-limit-manager-form').on('submit', function() {
            if (!validateMemoryValues()) {
                return false;
            }
            
            var $form = $(this);
            var $submitBtn = $('.mlm-submit-btn');
            
            // Show loading state
            $submitBtn.prop('disabled', true)
                     .addClass('mlm-btn-loading')
                     .html('<span class="dashicons dashicons-update"></span> Updating...');
            
            $form.addClass('mlm-loading');
            
            // Let the form submit naturally
            return true;
        });
        
        /**
         * Auto-format input on blur
         */
        $('#wp_memory_limit, #wp_max_memory_limit').on('blur', function() {
            var $input = $(this);
            var value = $input.val().trim().toUpperCase();
            
            // Auto-add M if just number
            if (/^\d+$/.test(value)) {
                $input.val(value + 'M');
            }
            
            validateMemoryValues();
        });
        
        /**
         * Keyboard shortcuts
         */
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                if (validateMemoryValues()) {
                    $('#memory-limit-manager-form').submit();
                }
            }
        });
        
        /**
         * Add tooltips for better UX
         */
        $('.mlm-status-item').each(function() {
            $(this).attr('title', 'Current value');
        });
        
        /**
         * Smooth scroll to errors
         */
        if ($('.notice-error').length > 0) {
            $('html, body').animate({
                scrollTop: $('.notice-error').first().offset().top - 100
            }, 500);
        }
        
        /**
         * Dismiss notices with animation
         */
        $(document).on('click', '.notice-dismiss', function() {
            $(this).parent().fadeOut(300);
        });
        
        /**
         * Copy manual configuration code
         */
        $(document).on('click', '.mlm-copy-config-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $pre = $btn.closest('.mlm-warning-box').find('pre code');
            var code = $pre.text();
            
            // Create temporary textarea
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(code).select();
            
            try {
                document.execCommand('copy');
                
                // Show feedback
                var originalText = $btn.html();
                $btn.html('<span class="dashicons dashicons-yes"></span> Copied!')
                    .addClass('mlm-btn-success')
                    .prop('disabled', true);
                
                setTimeout(function() {
                    $btn.html(originalText)
                        .removeClass('mlm-btn-success')
                        .prop('disabled', false);
                }, 2000);
            } catch (err) {
                alert('Please manually copy the code from the box.');
            }
            
            $temp.remove();
        });
        
        /**
         * Add copy functionality to status values
         */
        $('.mlm-status-value').on('click', function() {
            var $this = $(this);
            var text = $this.text();
            
            // Create temporary input
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            
            // Show feedback
            var originalText = $this.text();
            $this.text('Copied!').addClass('mlm-copied');
            
            setTimeout(function() {
                $this.text(originalText).removeClass('mlm-copied');
            }, 1500);
        });
        
        /**
         * Initialize validation on page load
         */
        validateMemoryValues();
        
        /**
         * Add loading animation to dashicons
         */
        $('.mlm-submit-btn .dashicons').addClass('mlm-icon-pulse');
        
    });
    
    /**
     * Add custom CSS for dynamic states
     */
    var customStyles = `
        <style>
            .mlm-input-error {
                border-color: #d63638 !important;
                box-shadow: 0 0 0 3px rgba(214, 54, 56, 0.1) !important;
            }
            
            .mlm-input-flash {
                animation: mlm-flash 0.5s ease;
            }
            
            @keyframes mlm-flash {
                0%, 100% { background-color: #fff; }
                50% { background-color: #e3f2fd; }
            }
            
            .mlm-preset-active {
                transform: scale(0.95) !important;
            }
            
            .mlm-btn-loading .dashicons {
                animation: mlm-rotate 1s linear infinite;
            }
            
            @keyframes mlm-rotate {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .mlm-copied {
                color: #00a32a !important;
                font-weight: 700 !important;
            }
            
            .mlm-status-value {
                cursor: pointer;
                user-select: none;
                transition: all 0.2s ease;
            }
            
            .mlm-status-value:hover {
                color: #2271b1;
                transform: scale(1.05);
            }
            
            .mlm-icon-pulse {
                animation: mlm-pulse 2s ease-in-out infinite;
            }
            
            @keyframes mlm-pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }
            
            .mlm-btn-success {
                background: #00a32a !important;
                border-color: #00a32a !important;
                color: #fff !important;
            }
            
            .mlm-copy-config-btn .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                vertical-align: middle;
            }
            
            .mlm-highlight-box {
                animation: mlm-highlight 1.5s ease-in-out 2;
                box-shadow: 0 0 20px rgba(255, 152, 0, 0.6) !important;
            }
            
            @keyframes mlm-highlight {
                0%, 100% {
                    box-shadow: 0 0 0 rgba(255, 152, 0, 0);
                }
                50% {
                    box-shadow: 0 0 20px rgba(255, 152, 0, 0.6);
                }
            }
            
            .mlm-notice-error ul {
                margin: 8px 0 8px 20px;
                list-style: disc;
            }
            
            .mlm-notice-error li {
                margin: 4px 0;
            }
        </style>
    `;
    
    $('head').append(customStyles);
    
})(jQuery);

