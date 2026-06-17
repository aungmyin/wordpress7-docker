<?php
/**
 * Advanced Scan Template
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Advanced Site Scanning</h1>
    <p>Run comprehensive scans for performance, SEO, broken links, and redirects across your site.</p>

    <!-- Single URL Scan Section -->
    <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #6f42c1; margin-bottom: 30px;">
        <h2 style="margin-top: 0;">🎯 Scan Single URL</h2>
        <p style="color: #666; margin-bottom: 15px;">Get detailed analysis for a specific page: performance, SEO, links, and optimization tips.</p>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" id="single-url-input" placeholder="https://yoursite.com/page" style="flex: 1; min-width: 300px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            <button id="scan-single-url" class="button button-primary" style="padding: 10px 20px;">Scan This URL</button>
        </div>
        <p style="font-size: 12px; color: #999; margin-top: 10px;">Analyzes: Performance • SEO • Links • Speed • Structure</p>
    </div>

    <h2>Site-Wide Scans</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">

        <!-- Performance Scan Card -->
        <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #0073aa; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">⚡ Performance Scan</h3>
            <p style="color: #666; font-size: 13px;">Analyze page load times, asset sizes, and optimization opportunities.</p>
            <button class="button button-primary scan-btn-adv" data-scan="performance" style="width: 100%; padding: 10px;">
                Start Performance Scan
            </button>
            <div style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 11px;">
                <button class="button button-small download-btn" data-scan="performance" data-format="csv" title="Download CSV">📥 CSV</button>
                <button class="button button-small download-btn" data-scan="performance" data-format="excel" title="Download Excel">📊 Excel</button>
            </div>
            <p style="font-size: 12px; color: #999; margin-top: 10px;">Scans top 20 pages | ~2 minutes</p>
        </div>

        <!-- 404 Detection Card -->
        <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">🔍 404 Detection</h3>
            <p style="color: #666; font-size: 13px;">Find broken internal links and missing pages across your site.</p>
            <button class="button button-primary scan-btn-adv" data-scan="404" style="width: 100%; padding: 10px; background-color: #dc3545 !important; border-color: #dc3545 !important;">
                Find Broken Links
            </button>
            <div style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 11px;">
                <button class="button button-small download-btn" data-scan="404" data-format="csv" title="Download CSV">📥 CSV</button>
                <button class="button button-small download-btn" data-scan="404" data-format="excel" title="Download Excel">📊 Excel</button>
            </div>
            <p style="font-size: 12px; color: #999; margin-top: 10px;">Crawls up to 200 URLs | ~2 minutes</p>
        </div>

        <!-- Redirect Check Card -->
        <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #fd7e14; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">🔄 Redirect Analysis</h3>
            <p style="color: #666; font-size: 13px;">Check for 301/302 redirects, chains, and configuration issues.</p>
            <button class="button button-primary scan-btn-adv" data-scan="redirects" style="width: 100%; padding: 10px; background-color: #fd7e14 !important; border-color: #fd7e14 !important;">
                Analyze Redirects
            </button>
            <div style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 11px;">
                <button class="button button-small download-btn" data-scan="redirects" data-format="csv" title="Download CSV">📥 CSV</button>
                <button class="button button-small download-btn" data-scan="redirects" data-format="excel" title="Download Excel">📊 Excel</button>
            </div>
            <p style="font-size: 12px; color: #999; margin-top: 10px;">Checks redirects | ~1 minute</p>
        </div>

        <!-- SEO Range Card -->
        <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">📊 SEO Analysis</h3>
            <p style="color: #666; font-size: 13px;">Analyze SEO across all pages: meta tags, keywords, structure.</p>
            <button class="button button-primary scan-btn-adv" data-scan="seo" style="width: 100%; padding: 10px; background-color: #28a745 !important; border-color: #28a745 !important;">
                Run SEO Scan
            </button>
            <div style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 11px;">
                <button class="button button-small download-btn" data-scan="seo" data-format="csv" title="Download CSV">📥 CSV</button>
                <button class="button button-small download-btn" data-scan="seo" data-format="excel" title="Download Excel">📊 Excel</button>
            </div>
            <p style="font-size: 12px; color: #999; margin-top: 10px;">Analyzes pages | ~1 minute</p>
        </div>

    </div>

    <div id="advanced-results" style="margin-top: 40px;"></div>
</div>

<script>
(function($) {
    'use strict';

    // Single URL scan
    $('#scan-single-url').on('click', function(e) {
        e.preventDefault();
        const url = $('#single-url-input').val().trim();

        if (!url) {
            alert('Please enter a URL');
            return;
        }

        const $btn = $(this);
        const originalText = $btn.text();
        $btn.prop('disabled', true).text('Analyzing URL...');
        $('#advanced-results').html('<p style="color: #666;">🔍 Analyzing URL... This may take a minute.</p>');

        $.ajax({
            url: claudeAiScanner.ajaxUrl,
            type: 'POST',
            data: {
                action: 'run_advanced_scan',
                scan_type: 'single-url',
                url: url,
                _ajax_nonce: claudeAiScanner.nonce,
            },
            success: function(response) {
                if (response.success) {
                    displayResults(response.data);
                } else {
                    $('#advanced-results').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                }
                $btn.prop('disabled', false).text(originalText);
            },
            error: function() {
                $('#advanced-results').html('<div class="notice notice-error"><p>Error scanning URL. Check that the URL is valid and accessible.</p></div>');
                $btn.prop('disabled', false).text(originalText);
            },
        });
    });

    // Allow Enter key in URL input
    $('#single-url-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#scan-single-url').click();
        }
    });

    // Run scan
    $(document).on('click', '.scan-btn-adv', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const scanType = $btn.data('scan');
        const originalText = $btn.text();

        $btn.prop('disabled', true).text('Scanning...');
        $('#advanced-results').html('<p style="color: #666;">🔍 Running ' + scanType + ' scan... This may take a few minutes.</p>');

        $.ajax({
            url: claudeAiScanner.ajaxUrl,
            type: 'POST',
            data: {
                action: 'run_advanced_scan',
                scan_type: scanType,
                _ajax_nonce: claudeAiScanner.nonce,
            },
            success: function(response) {
                if (response.success) {
                    displayResults(response.data);
                } else {
                    $('#advanced-results').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                }
                $btn.prop('disabled', false).text(originalText);
            },
            error: function() {
                $('#advanced-results').html('<div class="notice notice-error"><p>Error communicating with Claude API. Check your API key.</p></div>');
                $btn.prop('disabled', false).text(originalText);
            },
        });
    });

    // Download report
    $(document).on('click', '.download-btn', function(e) {
        e.preventDefault();
        const scanType = $(this).data('scan');
        const format = $(this).data('format');

        window.location.href = claudeAiScanner.ajaxUrl + '?action=download_report&scan_type=' + scanType + '&format=' + format + '&_ajax_nonce=' + claudeAiScanner.nonce;
    });

    function displayResults(analysis) {
        const html = `
            <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #0073aa; margin-top: 20px;">
                <h2>Scan Report</h2>
                <div style="white-space: pre-wrap; font-family: monospace; font-size: 13px; line-height: 1.8; color: #333;">
                    ${escapeHtml(analysis).replace(/\n/g, '<br>')}
                </div>
            </div>
        `;
        $('#advanced-results').html(html);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})(jQuery);
</script>
<?php
