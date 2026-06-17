(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle scan button clicks
        $(document).on('click', '.scan-btn', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const type = $btn.data('type');
            const nonce = claudeAiScanner.nonce;

            // Disable button and show loading state
            $btn.prop('disabled', true).text('Starting scan...');

            const data = {
                action: 'run_advanced_scan',
                scan_type: type,
                _ajax_nonce: nonce,
            };

            // Add URL for single-url scanner
            if (type === 'single-url') {
                const $urlInput = $('#scan-url-input');
                const url = $urlInput.val().trim();
                if (!url) {
                    alert('Please enter a URL to scan');
                    $btn.prop('disabled', false).text('Start Scan');
                    return;
                }
                data.url = url;
            }

            // Send AJAX request
            $.ajax({
                url: claudeAiScanner.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        if (response.data.async) {
                            // Job queued - start polling
                            showProgressModal(type, response.data.job_id);
                            pollJobProgress(response.data.job_id);
                        } else {
                            // Sync result - show immediately
                            showResultsModal(type, response.data.result);
                        }
                    } else {
                        // Handle rate limit or other errors
                        let errorMsg = 'Error: ';
                        if (typeof response.data === 'object' && response.data.message) {
                            errorMsg += response.data.message;
                            if (response.data.reset_in) {
                                const minutes = Math.ceil(response.data.reset_in / 60);
                                errorMsg += ' (Try again in ' + minutes + ' minute' + (minutes !== 1 ? 's' : '') + ')';
                            }
                        } else {
                            errorMsg += response.data;
                        }
                        alert(errorMsg);
                        $btn.prop('disabled', false).text('Start Scan');
                    }
                },
                error: function() {
                    alert('Error starting scan. Check your API key.');
                    $btn.prop('disabled', false).text('Start Scan');
                },
            });
        });
    });

    function showProgressModal(type, jobId) {
        const html = `
            <div class="claude-modal-overlay">
                <div class="claude-modal">
                    <div class="claude-modal-header">
                        <h2>${type.charAt(0).toUpperCase() + type.slice(1)} Scan Progress</h2>
                    </div>
                    <div class="claude-modal-body">
                        <div style="text-align: center; padding: 40px;">
                            <div style="margin-bottom: 20px;">
                                <div style="display: inline-block; width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid #0073aa; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                            </div>
                            <p id="progress-message">Initializing scanner...</p>
                            <div style="margin-top: 20px;">
                                <div style="background: #f3f3f3; border-radius: 6px; overflow: hidden; height: 20px;">
                                    <div id="progress-bar" style="background: #0073aa; height: 100%; width: 0%; transition: width 0.3s ease;"></div>
                                </div>
                                <p id="progress-percent" style="margin-top: 10px; color: #666; font-size: 14px;">0%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `;

        const $modal = $(html);
        $('body').append($modal);
        $modal.fadeIn(300);

        return $modal;
    }

    function pollJobProgress(jobId) {
        const poll = setInterval(function() {
            $.ajax({
                url: claudeAiScanner.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'check_scan_progress',
                    job_id: jobId,
                    _ajax_nonce: claudeAiScanner.nonce,
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;

                        if (data.progress) {
                            $('#progress-message').text(data.progress.message || 'Processing...');
                            $('#progress-bar').css('width', data.progress.percent + '%');
                            $('#progress-percent').text(data.progress.percent + '%');
                        }

                        if (data.status === 'completed') {
                            clearInterval(poll);
                            $('.claude-modal-overlay').fadeOut(300, function() {
                                $(this).remove();
                            });
                            showResultsModal(jobId, data.result);
                        } else if (data.status === 'failed') {
                            clearInterval(poll);
                            $('.claude-modal-overlay').fadeOut(300, function() {
                                $(this).remove();
                            });
                            alert('Scan failed: ' + (data.error || 'Unknown error'));
                        }
                    }
                },
            });
        }, 2000); // Poll every 2 seconds
    }

    function showResultsModal(type, result) {
        const html = `
            <div class="claude-modal-overlay">
                <div class="claude-modal">
                    <div class="claude-modal-header">
                        <h2>${typeof type === 'string' && type.length > 20 ? 'Scan Results' : (type.charAt(0).toUpperCase() + type.slice(1)) + ' Scan Results'}</h2>
                        <button class="claude-modal-close">&times;</button>
                    </div>
                    <div class="claude-modal-body">
                        <div class="claude-analysis-content" style="white-space: pre-wrap; font-family: monospace; font-size: 12px; max-height: 400px; overflow-y: auto; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                            ${escapeHtml(result)}
                        </div>
                    </div>
                    <div class="claude-modal-footer">
                        <button class="button button-primary claude-close-btn">Close</button>
                    </div>
                </div>
            </div>
        `;

        const $modal = $(html);
        $('body').append($modal);

        // Handle close
        $modal.on('click', '.claude-modal-close, .claude-close-btn', function() {
            $modal.fadeOut(300, function() {
                $modal.remove();
            });
        });

        $modal.on('click', '.claude-modal-overlay', function(e) {
            if (e.target === this) {
                $modal.fadeOut(300, function() {
                    $modal.remove();
                });
            }
        });

        $modal.fadeIn(300);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})(jQuery);
