(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle scan button clicks
        $(document).on('click', '.scan-btn', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const type = $btn.data('type');
            const path = $btn.data('path');
            const nonce = $btn.data('nonce');

            // Disable button and show loading state
            $btn.prop('disabled', true).text('Scanning...');

            // Send AJAX request
            $.ajax({
                url: claudeAiScanner.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'scan_plugin_theme',
                    type: type,
                    path: path,
                    _ajax_nonce: claudeAiScanner.nonce,
                },
                success: function(response) {
                    if (response.success) {
                        showAnalysisModal(type, path, response.data);
                    } else {
                        alert('Error: ' + response.data);
                        $btn.prop('disabled', false).text('Scan with AI');
                    }
                },
                error: function() {
                    alert('Error communicating with Claude API. Check your API key.');
                    $btn.prop('disabled', false).text('Scan with AI');
                },
            });
        });
    });

    function showAnalysisModal(type, path, analysis) {
        const title = type === 'plugin' ? 'Plugin Analysis' : 'Theme Analysis';

        const html = `
            <div class="claude-modal-overlay">
                <div class="claude-modal">
                    <div class="claude-modal-header">
                        <h2>${title}: ${path}</h2>
                        <button class="claude-modal-close">&times;</button>
                    </div>
                    <div class="claude-modal-body">
                        <div class="claude-analysis-content">
                            ${escapeHtml(analysis).replace(/\n/g, '<br>')}
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
