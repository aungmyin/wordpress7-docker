<?php
/**
 * Plugin Name: Claude AI Scanner
 * Plugin URI: https://example.com/claude-ai-scanner
 * Description: Comprehensive WordPress site health analyzer with AI-powered security, performance, SEO, and code quality analysis.
 * Version: 2.0.0
 * Author: WordPress Developer
 * License: GPL v2 or later
 * Text Domain: claude-ai-scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CLAUDE_AI_SCANNER_DIR', plugin_dir_path(__FILE__));
define('CLAUDE_AI_SCANNER_URL', plugin_dir_url(__FILE__));
define('CLAUDE_AI_SCANNER_VERSION', '2.0.0');

// Security: Only load admin functionality if in admin area or AJAX request from admin
if (!is_admin()) {
    return;
}

// Admin menu and pages
add_action('admin_menu', 'claude_ai_scanner_add_admin_menu');
function claude_ai_scanner_add_admin_menu() {
    add_menu_page(
        'Claude AI Scanner',
        'AI Scanner',
        'manage_options',
        'claude-ai-scanner',
        'claude_ai_scanner_page',
        'dashicons-search',
        80
    );

    add_submenu_page(
        'claude-ai-scanner',
        'Scanner',
        'Scanner',
        'manage_options',
        'claude-ai-scanner',
        'claude_ai_scanner_page'
    );

    add_submenu_page(
        'claude-ai-scanner',
        'Site Health',
        'Site Health',
        'manage_options',
        'claude-ai-scanner-health',
        'claude_ai_scanner_health_page'
    );

    add_submenu_page(
        'claude-ai-scanner',
        'Performance',
        'Performance',
        'manage_options',
        'claude-ai-scanner-performance',
        'claude_ai_scanner_performance_page'
    );

    add_submenu_page(
        'claude-ai-scanner',
        'Settings',
        'Settings',
        'manage_options',
        'claude-ai-scanner-settings',
        'claude_ai_scanner_settings_page'
    );
}

// Settings page
function claude_ai_scanner_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claude_api_key'])) {
        check_admin_referer('claude_ai_scanner_settings');
        $api_key = sanitize_text_field($_POST['claude_api_key']);
        update_option('claude_ai_scanner_api_key', $api_key);
        echo '<div class="notice notice-success"><p>API Key saved successfully!</p></div>';
    }

    $api_key = get_option('claude_ai_scanner_api_key', '');
    $masked_key = $api_key ? substr($api_key, 0, 10) . '...' . substr($api_key, -4) : 'Not set';
    ?>
    <div class="wrap">
        <h1>Claude AI Scanner Settings</h1>
        <div style="max-width: 600px; margin-top: 20px;">
            <h2>Claude API Configuration</h2>
            <p>Get your Claude API key from: <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></p>

            <form method="post">
                <?php wp_nonce_field('claude_ai_scanner_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="claude_api_key">API Key</label></th>
                        <td>
                            <input type="password" id="claude_api_key" name="claude_api_key" value="<?php echo esc_attr($api_key); ?>" style="width: 300px;">
                            <p class="description">Your current key: <?php echo esc_html($masked_key); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save API Key'); ?>
            </form>

            <hr style="margin: 30px 0;">

            <h2>How to Use</h2>
            <ol>
                <li>Get your API key from <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a></li>
                <li>Enter your API key above and save</li>
                <li>Go to "Scanner" tab to analyze your plugins and themes</li>
                <li>Claude AI will analyze each one for:
                    <ul>
                        <li>🔍 Security issues and vulnerabilities</li>
                        <li>📋 Code quality and best practices</li>
                        <li>📚 Documentation and functionality</li>
                        <li>⚙️ WordPress 7.0 compatibility</li>
                    </ul>
                </li>
            </ol>
        </div>
    </div>
    <?php
}

// Scanner page
function claude_ai_scanner_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $api_key = get_option('claude_ai_scanner_api_key', '');
    if (!$api_key) {
        echo '<div class="wrap"><div class="notice notice-error"><p>⚠️ Claude API key not configured. <a href="' . esc_url(admin_url('admin.php?page=claude-ai-scanner-settings')) . '">Configure it here</a></p></div></div>';
        return;
    }
    ?>
    <div class="wrap">
        <h1>Claude AI Scanner</h1>
        <p>Scan your plugins and themes with AI analysis.</p>

        <div style="margin-top: 20px;">
            <h2>Plugins</h2>
            <?php claude_ai_scanner_display_plugins($api_key); ?>

            <h2 style="margin-top: 40px;">Themes</h2>
            <?php claude_ai_scanner_display_themes($api_key); ?>
        </div>
    </div>
    <?php
}

function claude_ai_scanner_display_plugins($api_key) {
    $plugins = get_plugins();
    if (empty($plugins)) {
        echo '<p>No plugins installed.</p>';
        return;
    }

    echo '<table class="wp-list-table widefat striped">';
    echo '<thead><tr><th>Plugin</th><th>Version</th><th>Action</th></tr></thead>';
    echo '<tbody>';

    foreach ($plugins as $plugin_file => $plugin_data) {
        $plugin_name = esc_html($plugin_data['Name']);
        $plugin_version = esc_html($plugin_data['Version']);
        $nonce = wp_create_nonce('scan_plugin_' . $plugin_file);

        echo '<tr>';
        echo '<td><strong>' . $plugin_name . '</strong><br><small>' . esc_html($plugin_file) . '</small></td>';
        echo '<td>' . $plugin_version . '</td>';
        echo '<td><button class="button scan-btn" data-type="plugin" data-path="' . esc_attr($plugin_file) . '" data-nonce="' . esc_attr($nonce) . '">Scan with AI</button></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}

function claude_ai_scanner_display_themes($api_key) {
    $themes = wp_get_themes();
    if (empty($themes)) {
        echo '<p>No themes installed.</p>';
        return;
    }

    echo '<table class="wp-list-table widefat striped">';
    echo '<thead><tr><th>Theme</th><th>Version</th><th>Action</th></tr></thead>';
    echo '<tbody>';

    foreach ($themes as $theme) {
        $theme_name = esc_html($theme->get('Name'));
        $theme_version = esc_html($theme->get('Version'));
        $theme_dir = basename($theme->get_stylesheet_directory());
        $nonce = wp_create_nonce('scan_theme_' . $theme_dir);

        echo '<tr>';
        echo '<td><strong>' . $theme_name . '</strong><br><small>' . esc_html($theme_dir) . '</small></td>';
        echo '<td>' . $theme_version . '</td>';
        echo '<td><button class="button scan-btn" data-type="theme" data-path="' . esc_attr($theme_dir) . '" data-nonce="' . esc_attr($nonce) . '">Scan with AI</button></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}

// AJAX handler for scanning (admin only)
add_action('wp_ajax_scan_plugin_theme', 'claude_ai_scanner_ajax_scan');
function claude_ai_scanner_ajax_scan() {
    if (!is_admin() || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
    }

    check_ajax_referer('scan_plugin_theme_nonce');

    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
    $path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
    $api_key = get_option('claude_ai_scanner_api_key', '');

    if (!$api_key) {
        wp_send_json_error('API key not configured');
    }

    if (empty($type) || empty($path)) {
        wp_send_json_error('Invalid type or path');
    }

    $analysis = claude_ai_scanner_analyze_with_claude($type, $path, $api_key);

    if (is_wp_error($analysis)) {
        wp_send_json_error($analysis->get_error_message());
    }

    wp_send_json_success($analysis);
}

// AJAX handler for full site health scan (admin only)
add_action('wp_ajax_scan_full_site_health', 'claude_ai_scanner_ajax_site_health');
function claude_ai_scanner_ajax_site_health() {
    if (!is_admin() || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
    }

    check_ajax_referer('scan_plugin_theme_nonce');

    $api_key = get_option('claude_ai_scanner_api_key', '');
    if (!$api_key) {
        wp_send_json_error('API key not configured');
    }

    // Collect comprehensive site data
    $site_data = claude_ai_scanner_collect_site_data();

    // Prepare comprehensive prompt
    $prompt = claude_ai_scanner_prepare_health_prompt($site_data);

    // Call Claude API
    $response = claude_ai_scanner_call_claude_api($prompt, $api_key);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    wp_send_json_success($response);
}

// AJAX handler for performance metrics (admin only)
add_action('wp_ajax_get_performance_metrics', 'claude_ai_scanner_ajax_performance_metrics');
function claude_ai_scanner_ajax_performance_metrics() {
    if (!is_admin() || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
    }

    check_ajax_referer('scan_plugin_theme_nonce');

    $metrics = claude_ai_scanner_get_performance_metrics();
    wp_send_json_success($metrics);
}

// AJAX handler for performance analysis (admin only)
add_action('wp_ajax_analyze_performance', 'claude_ai_scanner_ajax_performance_analysis');
function claude_ai_scanner_ajax_performance_analysis() {
    if (!is_admin() || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
    }

    check_ajax_referer('scan_plugin_theme_nonce');

    $api_key = get_option('claude_ai_scanner_api_key', '');
    if (!$api_key) {
        wp_send_json_error('API key not configured');
    }

    // Collect performance data
    $perf_data = claude_ai_scanner_get_performance_metrics();

    // Prepare performance analysis prompt
    $prompt = claude_ai_scanner_prepare_performance_prompt($perf_data);

    // Call Claude API
    $response = claude_ai_scanner_call_claude_api($prompt, $api_key);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    wp_send_json_success($response);
}

function claude_ai_scanner_analyze_with_claude($type, $path, $api_key) {
    // Get plugin/theme information
    if ($type === 'plugin') {
        $plugins = get_plugins();
        if (!isset($plugins[$path])) {
            return new WP_Error('not_found', 'Plugin not found');
        }
        $data = $plugins[$path];
        $full_path = WP_PLUGIN_DIR . '/' . $path;
    } else {
        $themes = wp_get_themes();
        $theme_dir = basename($path);
        $theme = $themes[$path] ?? null;
        if (!$theme) {
            return new WP_Error('not_found', 'Theme not found');
        }
        $data = [
            'Name' => $theme->get('Name'),
            'Version' => $theme->get('Version'),
            'Description' => $theme->get('Description'),
            'Author' => $theme->get('Author'),
        ];
        $full_path = $theme->get_stylesheet_directory();
    }

    // Read main file or readme
    $file_contents = claude_ai_scanner_read_file_info($full_path, $type);

    // Perform SEO and link analysis
    $seo_analysis = claude_ai_scanner_analyze_seo_links($full_path, $type);

    // Prepare prompt for Claude
    $prompt = claude_ai_scanner_prepare_prompt($type, $data, $file_contents, $seo_analysis);

    // Call Claude API
    $response = claude_ai_scanner_call_claude_api($prompt, $api_key);

    return $response;
}

function claude_ai_scanner_read_file_info($path, $type) {
    $info = '';

    if ($type === 'plugin') {
        // Read main plugin file header
        $main_file = $path;
        if (is_dir($path)) {
            $files = glob($path . '/*.php');
            if (!empty($files)) {
                $main_file = $files[0];
            }
        }

        if (file_exists($main_file)) {
            $info .= "=== Main Plugin File ===\n";
            $content = file_get_contents($main_file);
            $info .= substr($content, 0, 2000) . "...\n\n";
        }
    } else {
        // Read theme style.css
        $style_file = $path . '/style.css';
        if (file_exists($style_file)) {
            $info .= "=== Theme style.css ===\n";
            $content = file_get_contents($style_file);
            $info .= substr($content, 0, 1500) . "...\n\n";
        }
    }

    // Read README if exists
    $readme_file = $path . '/README.md';
    if (!file_exists($readme_file)) {
        $readme_file = $path . '/readme.txt';
    }
    if (file_exists($readme_file)) {
        $info .= "=== README ===\n";
        $content = file_get_contents($readme_file);
        $info .= substr($content, 0, 1500) . "...\n";
    }

    return $info;
}

function claude_ai_scanner_prepare_prompt($type, $data, $file_contents, $seo_analysis = null) {
    $type_label = $type === 'plugin' ? 'Plugin' : 'Theme';

    $seo_section = '';
    if ($seo_analysis) {
        $seo_section = "\n\n=== SEO & LINK ANALYSIS SCAN RESULTS ===\n";
        if (!empty($seo_analysis['issues'])) {
            $seo_section .= "Issues Found:\n";
            foreach ($seo_analysis['issues'] as $issue) {
                $seo_section .= "- $issue\n";
            }
        }
        if (!empty($seo_analysis['urls_found'])) {
            $seo_section .= "\nURLs Found in Code:\n";
            $unique_urls = array_unique($seo_analysis['urls_found']);
            foreach (array_slice($unique_urls, 0, 10) as $url) {
                $seo_section .= "- $url\n";
            }
            if (count($unique_urls) > 10) {
                $seo_section .= "- ... and " . (count($unique_urls) - 10) . " more URLs\n";
            }
        }
    }

    $prompt = <<<PROMPT
Analyze this WordPress $type_label and provide a comprehensive report covering:

1. **Security**: Identify any potential security vulnerabilities, unsafe functions, or security best practices not followed.
2. **Code Quality**: Evaluate code structure, best practices, and maintainability.
3. **Documentation**: Review the quality and completeness of documentation.
4. **WordPress 7.0 Compatibility**: Check if it appears compatible with WordPress 7.0 (check for deprecated functions, new API usage opportunities).
5. **SEO & Link Health**:
   - Analyze hardcoded URLs for 404 risks
   - Review redirect handling (301/302 implementations)
   - Check meta tags and structured data
   - Identify broken links or link rot patterns
   - Assess redirect chains
   - Review canonical tag handling

$type_label Information:
Name: {$data['Name']}
Version: {$data['Version']}
Description: {$data['Description'] ?? 'N/A'}
Author: {$data['Author'] ?? 'N/A'}

Code Preview:
$file_contents
$seo_section

Provide your analysis in a structured format with clear sections. For the SEO/Link Health section, specifically mention:
- Found URLs and their potential fragility (hardcoded, pointing to third-party services, etc.)
- Redirect implementation patterns (proper 301/302 usage)
- Potential 404 risks from URL structure
- Meta tag and structured data implementation
- Link validation mechanisms
- Any redirect chains or infinite loops

Be specific and actionable with recommendations for improvement.
PROMPT;

    return $prompt;
}

function claude_ai_scanner_call_claude_api($prompt, $api_key) {
    $url = 'https://api.anthropic.com/v1/messages';

    $body = [
        'model' => 'claude-3-5-sonnet-20241022',
        'max_tokens' => 2000,
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ],
    ];

    $args = [
        'body' => wp_json_encode($body),
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01',
        ],
        'timeout' => 30,
    ];

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        return $response;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['error'])) {
        return new WP_Error('api_error', $body['error']['message']);
    }

    if (isset($body['content'][0]['text'])) {
        return $body['content'][0]['text'];
    }

    return new WP_Error('unexpected_response', 'Unexpected response from Claude API');
}

// Site Health Page
function claude_ai_scanner_health_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $api_key = get_option('claude_ai_scanner_api_key', '');
    if (!$api_key) {
        echo '<div class="wrap"><div class="notice notice-error"><p>⚠️ Claude API key not configured. <a href="' . esc_url(admin_url('admin.php?page=claude-ai-scanner-settings')) . '">Configure it here</a></p></div></div>';
        return;
    }
    ?>
    <div class="wrap">
        <h1>WordPress Site Health Analysis</h1>
        <p>Comprehensive scan of your entire WordPress installation.</p>

        <div style="margin-top: 20px; padding: 20px; background: #f5f5f5; border-radius: 8px;">
            <h2>Full Site Scan</h2>
            <p>This will analyze your entire WordPress site for:</p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li>🔍 Security vulnerabilities</li>
                <li>⚡ Performance bottlenecks</li>
                <li>📚 Documentation quality</li>
                <li>🔗 SEO issues and broken links</li>
                <li>⚙️ WordPress 7.0 compatibility</li>
                <li>📋 Code quality issues</li>
            </ul>
            <button id="scan-site-health" class="button button-primary" style="margin-top: 20px; padding: 10px 20px; font-size: 16px;">
                Start Full Site Scan
            </button>
            <p style="margin-top: 20px; color: #666; font-size: 13px;">
                ⏱️ Estimated time: 30-60 seconds | 💰 Uses Claude API credits
            </p>
        </div>

        <div id="health-results" style="margin-top: 40px;"></div>
    </div>

    <script>
        (function($) {
            $('#scan-site-health').click(function() {
                const $btn = $(this);
                $btn.prop('disabled', true).text('Scanning... (this may take a minute)');
                $('#health-results').html('<p style="color: #666;">Analyzing your site...</p>');

                $.ajax({
                    url: claudeAiScanner.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'scan_full_site_health',
                        _ajax_nonce: claudeAiScanner.nonce,
                    },
                    success: function(response) {
                        if (response.success) {
                            displayHealthReport(response.data);
                        } else {
                            $('#health-results').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                        }
                        $btn.prop('disabled', false).text('Start Full Site Scan');
                    },
                    error: function() {
                        $('#health-results').html('<div class="notice notice-error"><p>Error communicating with Claude API. Check your API key.</p></div>');
                        $btn.prop('disabled', false).text('Start Full Site Scan');
                    },
                });
            });

            function displayHealthReport(analysis) {
                const html = `
                    <div class="claude-health-report">
                        <h2>Site Health Report</h2>
                        <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #0073aa;">
                            <div style="white-space: pre-wrap; font-family: monospace; font-size: 13px; line-height: 1.6;">
                                ${escapeHtml(analysis).replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    </div>
                `;
                $('#health-results').html(html);
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        })(jQuery);
    </script>
    <?php
}

// Performance Analysis Page
function claude_ai_scanner_performance_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $api_key = get_option('claude_ai_scanner_api_key', '');
    if (!$api_key) {
        echo '<div class="wrap"><div class="notice notice-error"><p>⚠️ Claude API key not configured. <a href="' . esc_url(admin_url('admin.php?page=claude-ai-scanner-settings')) . '">Configure it here</a></p></div></div>';
        return;
    }
    ?>
    <div class="wrap">
        <h1>Performance Analysis</h1>
        <p>Identify performance bottlenecks and optimization opportunities.</p>

        <div style="margin-top: 20px;">
            <h2>Current Performance Metrics</h2>
            <div id="performance-metrics" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #0073aa;">
                    <div style="font-size: 12px; color: #666;">Database Size</div>
                    <div style="font-size: 24px; font-weight: bold;" id="db-size">Calculating...</div>
                </div>
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #0073aa;">
                    <div style="font-size: 12px; color: #666;">Active Plugins</div>
                    <div style="font-size: 24px; font-weight: bold;" id="plugin-count">Calculating...</div>
                </div>
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #0073aa;">
                    <div style="font-size: 12px; color: #666;">Post Count</div>
                    <div style="font-size: 24px; font-weight: bold;" id="post-count">Calculating...</div>
                </div>
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #0073aa;">
                    <div style="font-size: 12px; color: #666;">Memory Limit</div>
                    <div style="font-size: 24px; font-weight: bold;" id="memory-limit">Calculating...</div>
                </div>
            </div>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h3>Performance Analysis with AI</h3>
                <p>Claude will analyze your site configuration and provide specific optimization recommendations.</p>
                <button id="analyze-performance" class="button button-primary" style="padding: 10px 20px; font-size: 16px;">
                    Analyze Performance Issues
                </button>
            </div>

            <div id="performance-results"></div>
        </div>
    </div>

    <script>
        (function($) {
            // Load metrics on page load
            loadPerformanceMetrics();

            $('#analyze-performance').click(function() {
                const $btn = $(this);
                $btn.prop('disabled', true).text('Analyzing...');

                $.ajax({
                    url: claudeAiScanner.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'analyze_performance',
                        _ajax_nonce: claudeAiScanner.nonce,
                    },
                    success: function(response) {
                        if (response.success) {
                            displayPerformanceReport(response.data);
                        } else {
                            $('#performance-results').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                        }
                        $btn.prop('disabled', false).text('Analyze Performance Issues');
                    },
                    error: function() {
                        $('#performance-results').html('<div class="notice notice-error"><p>Error communicating with Claude API.</p></div>');
                        $btn.prop('disabled', false).text('Analyze Performance Issues');
                    },
                });
            });

            function loadPerformanceMetrics() {
                $.ajax({
                    url: claudeAiScanner.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_performance_metrics',
                        _ajax_nonce: claudeAiScanner.nonce,
                    },
                    success: function(response) {
                        if (response.success) {
                            const metrics = response.data;
                            $('#db-size').text(metrics.db_size);
                            $('#plugin-count').text(metrics.plugin_count);
                            $('#post-count').text(metrics.post_count);
                            $('#memory-limit').text(metrics.memory_limit);
                        }
                    },
                });
            }

            function displayPerformanceReport(analysis) {
                const html = `
                    <div class="claude-performance-report">
                        <h2>Performance Optimization Report</h2>
                        <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545;">
                            <div style="white-space: pre-wrap; font-family: monospace; font-size: 13px; line-height: 1.6;">
                                ${escapeHtml(analysis).replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    </div>
                `;
                $('#performance-results').html(html);
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        })(jQuery);
    </script>
    <?php
}

// SEO and Link Analysis
function claude_ai_scanner_analyze_seo_links($path, $type) {
    $issues = [];
    $urls_found = [];

    // Scan files for URLs and patterns
    $files = claude_ai_scanner_get_php_files($path);

    foreach ($files as $file) {
        $content = file_get_contents($file);

        // Find hardcoded URLs
        if (preg_match_all('/https?:\/\/[^\s"\'<>\)]+/i', $content, $matches)) {
            $urls_found = array_merge($urls_found, $matches[0]);
        }

        // Check for redirect patterns
        if (preg_match('/wp_redirect|header\(\s*["\']Location/i', $content)) {
            $issues[] = "✓ Found redirect handling in code";
        }

        // Check for hardcoded domain references
        if (preg_match('/example\.com|yoursite\.com|localhost/i', $content)) {
            $issues[] = "⚠️ Found hardcoded example/test domain references that might cause 404 issues";
        }

        // Check for broken URL patterns
        if (preg_match('/\/wp-content\/.*\.(php|js|css)/i', $content)) {
            $issues[] = "ℹ️ Direct file references to wp-content detected (potential 404 risk if URLs change)";
        }

        // Check for HTTP vs HTTPS consistency
        $http_count = substr_count($content, 'http://');
        $https_count = substr_count($content, 'https://');
        if ($http_count > 0 && $https_count > 0) {
            $issues[] = "⚠️ Mixed HTTP and HTTPS URLs detected (may cause redirect chains)";
        }
    }

    // Check for meta tags and structured data
    $html_files = array_filter($files, function($f) {
        return preg_match('/\.(html|php)$/i', $f);
    });

    foreach ($html_files as $file) {
        $content = file_get_contents($file);

        if (preg_match('/<meta\s+name=["\'](description|keywords|robots)/i', $content)) {
            $issues[] = "✓ Meta tags found in code";
        }

        if (preg_match('/"@context".*schema\.org/i', $content) || preg_match('/\bitemscope\b/i', $content)) {
            $issues[] = "✓ Structured data (Schema.org) implemented";
        }

        if (preg_match('/<link[^>]*rel=["\'](canonical|alternate)/i', $content)) {
            $issues[] = "✓ Canonical/alternate link tags implemented";
        }
    }

    return [
        'issues' => $issues,
        'urls_found' => array_unique($urls_found),
    ];
}

function claude_ai_scanner_get_php_files($path, $depth = 0, $max_depth = 3) {
    $files = [];

    if ($depth > $max_depth) {
        return $files;
    }

    if (!is_dir($path)) {
        return $files;
    }

    try {
        $items = @scandir($path);
        if ($items === false) {
            return $files;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full_path = $path . '/' . $item;

            // Skip common directories
            if (is_dir($full_path) && in_array($item, ['node_modules', '.git', 'dist', 'build', 'vendor'])) {
                continue;
            }

            if (is_file($full_path) && preg_match('/\.(php|js|html)$/i', $full_path)) {
                $files[] = $full_path;
            } elseif (is_dir($full_path)) {
                $files = array_merge($files, claude_ai_scanner_get_php_files($full_path, $depth + 1, $max_depth));
            }
        }
    } catch (Exception $e) {
        // Silently ignore scan errors
    }

    return $files;
}

// Collect comprehensive site data for analysis
function claude_ai_scanner_collect_site_data() {
    global $wpdb;

    $data = [
        'wp_version' => get_bloginfo('version'),
        'php_version' => phpversion(),
        'mysql_version' => $wpdb->db_version(),
        'active_plugins' => count(get_option('active_plugins', [])),
        'total_posts' => wp_count_posts(),
        'total_users' => count_users(),
        'installed_themes' => count(wp_get_themes()),
        'active_theme' => wp_get_theme()->get('Name'),
        'db_tables' => count($wpdb->tables()),
        'posts_per_page' => get_option('posts_per_page'),
        'plugins' => [],
        'active_plugins_list' => [],
        'caching_plugins' => [],
        'security_issues' => [],
    ];

    // Get active plugins info
    $plugins = get_plugins();
    foreach (get_option('active_plugins', []) as $plugin) {
        if (isset($plugins[$plugin])) {
            $data['active_plugins_list'][] = $plugins[$plugin]['Name'] . ' v' . $plugins[$plugin]['Version'];

            // Check for caching plugins
            if (stripos($plugin, 'cache') !== false || stripos($plugin, 'performance') !== false) {
                $data['caching_plugins'][] = $plugins[$plugin]['Name'];
            }
        }
    }

    // Check for security issues
    $data['security_issues'] = claude_ai_scanner_scan_security_issues();

    // File system info
    $data['wp_filesystem'] = [
        'uploads_dir' => wp_get_upload_dir(),
        'content_dir' => WP_CONTENT_DIR,
    ];

    return $data;
}

// Scan for security issues
function claude_ai_scanner_scan_security_issues() {
    $issues = [];

    // Check for debug mode
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $issues[] = "WP_DEBUG is enabled (should be false in production)";
    }

    // Check for wp-config.php protection
    if (file_exists(ABSPATH . 'wp-config.php')) {
        $issues[] = "wp-config.php file is accessible";
    }

    // Check for .htaccess
    if (!file_exists(ABSPATH . '.htaccess')) {
        $issues[] = "No .htaccess file found (may impact security and SEO)";
    }

    // Check file permissions
    if (is_writable(ABSPATH . 'wp-config.php')) {
        $issues[] = "wp-config.php is writable (security risk)";
    }

    return $issues;
}

// Get performance metrics
function claude_ai_scanner_get_performance_metrics() {
    global $wpdb;

    $db_size = $wpdb->get_results("SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size FROM information_schema.TABLES WHERE table_schema = DATABASE()");
    $total_db_size = 0;
    foreach ($db_size as $table) {
        $total_db_size += $table->size;
    }

    $metrics = [
        'db_size' => round($total_db_size, 2) . ' MB',
        'plugin_count' => count(get_option('active_plugins', [])),
        'post_count' => wp_count_posts()->publish ?? 0,
        'memory_limit' => WP_MEMORY_LIMIT,
        'max_upload_size' => wp_max_upload_size() / (1024 * 1024) . ' MB',
        'php_version' => phpversion(),
        'wp_version' => get_bloginfo('version'),
        'table_count' => count($wpdb->tables()),
        'cron_jobs' => count(_get_cron_array()),
    ];

    return $metrics;
}

// Prepare health analysis prompt
function claude_ai_scanner_prepare_health_prompt($site_data) {
    $prompt = <<<PROMPT
Analyze this WordPress site and provide a comprehensive health report covering:

1. **Security Assessment**
   - Identify security vulnerabilities
   - Check configuration issues
   - Plugin security concerns
   - Recommendations for hardening

2. **Performance Analysis**
   - Database optimization opportunities
   - Query bottlenecks
   - Caching strategy
   - Resource usage recommendations

3. **SEO Health**
   - Meta tags and structured data
   - Broken links
   - Site structure
   - Content organization

4. **Code Quality**
   - Plugin/theme conflicts
   - Deprecated functions
   - Memory leaks
   - Best practices

5. **WordPress 7.0 Compatibility**
   - Deprecated plugin features
   - API updates needed
   - Upgrade readiness

WordPress Site Data:
- WordPress Version: {$site_data['wp_version']}
- PHP Version: {$site_data['php_version']}
- MySQL Version: {$site_data['mysql_version']}
- Active Theme: {$site_data['active_theme']}
- Active Plugins: {$site_data['active_plugins']}
- Database Size: (calculated during analysis)
- Total Posts: {$site_data['total_posts']->publish ?? 0}
- Total Users: {$site_data['total_users']['total_users']}

Active Plugins:
PROMPT;

    if (!empty($site_data['active_plugins_list'])) {
        $prompt .= implode("\n", array_map(fn($p) => "- $p", $site_data['active_plugins_list']));
    } else {
        $prompt .= "- None";
    }

    if (!empty($site_data['security_issues'])) {
        $prompt .= "\n\nIdentified Security Issues:\n";
        foreach ($site_data['security_issues'] as $issue) {
            $prompt .= "- $issue\n";
        }
    }

    $prompt .= <<<PROMPT

Caching Plugins Installed:
PROMPT;
    $prompt .= !empty($site_data['caching_plugins']) ? implode(", ", $site_data['caching_plugins']) : "None";

    $prompt .= <<<PROMPT

For each section, provide:
- Current status (Critical/Warning/Good)
- Specific issues found
- Exact steps to fix (with code snippets if needed)
- Expected impact (e.g., "20% faster load time")
- Priority (Critical/High/Medium/Low)

Be detailed and actionable with specific file paths and code changes.
PROMPT;

    return $prompt;
}

// Prepare performance analysis prompt
function claude_ai_scanner_prepare_performance_prompt($perf_data) {
    $prompt = <<<PROMPT
Analyze this WordPress site's performance and provide optimization recommendations:

Site Metrics:
- Database Size: {$perf_data['db_size']}
- Active Plugins: {$perf_data['plugin_count']}
- Published Posts: {$perf_data['post_count']}
- Memory Limit: {$perf_data['memory_limit']}
- Max Upload Size: {$perf_data['max_upload_size']}
- PHP Version: {$perf_data['php_version']}
- WordPress Version: {$perf_data['wp_version']}
- Database Tables: {$perf_data['table_count']}
- Cron Jobs: {$perf_data['cron_jobs']}

Provide optimization recommendations in these categories:

1. **Database Optimization**
   - Index optimization
   - Query optimization
   - Cleanup opportunities (revisions, transients, spam)
   - Specific SQL commands to run

2. **Caching Strategy**
   - Object caching recommendations
   - Page caching setup
   - Browser caching configuration
   - Plugin recommendations

3. **Image Optimization**
   - Size reduction strategies
   - Lazy loading
   - Format recommendations
   - Tools and plugins

4. **Code Performance**
   - Minification strategy
   - Asset loading order
   - Unused code removal
   - Database query reduction

5. **Resource Limits**
   - PHP memory optimization
   - Max upload size adjustments
   - Timeout configurations
   - Execution limits

For each recommendation:
- Explain WHY it will help (e.g., "Database size indicates need for cleanup")
- Provide EXACT steps with code/configuration
- Estimate impact (e.g., "30% faster page load")
- Priority level (Critical/High/Medium/Low)
- Implementation difficulty (Easy/Medium/Hard)

Focus on specific, actionable items that will have measurable impact.
PROMPT;

    return $prompt;
}

// Enqueue scripts
add_action('admin_enqueue_scripts', 'claude_ai_scanner_enqueue_scripts');
function claude_ai_scanner_enqueue_scripts($hook) {
    if (strpos($hook, 'claude-ai-scanner') === false) {
        return;
    }

    wp_enqueue_script('claude-ai-scanner', CLAUDE_AI_SCANNER_URL . 'js/scanner.js', ['jquery'], CLAUDE_AI_SCANNER_VERSION, true);
    wp_localize_script('claude-ai-scanner', 'claudeAiScanner', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('scan_plugin_theme_nonce'),
    ]);

    wp_enqueue_style('claude-ai-scanner', CLAUDE_AI_SCANNER_URL . 'css/scanner.css', [], CLAUDE_AI_SCANNER_VERSION);
}
