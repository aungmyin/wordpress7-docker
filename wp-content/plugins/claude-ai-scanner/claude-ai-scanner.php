<?php
/**
 * Plugin Name: Claude AI Scanner
 * Plugin URI: https://example.com/claude-ai-scanner
 * Description: Scan your WordPress plugins and themes with Claude AI for security, code quality, documentation, and compatibility analysis.
 * Version: 1.0.0
 * Author: WordPress Developer
 * License: GPL v2 or later
 * Text Domain: claude-ai-scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CLAUDE_AI_SCANNER_DIR', plugin_dir_path(__FILE__));
define('CLAUDE_AI_SCANNER_URL', plugin_dir_url(__FILE__));
define('CLAUDE_AI_SCANNER_VERSION', '1.0.0');

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

// AJAX handler for scanning
add_action('wp_ajax_scan_plugin_theme', 'claude_ai_scanner_ajax_scan');
function claude_ai_scanner_ajax_scan() {
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

    // Prepare prompt for Claude
    $prompt = claude_ai_scanner_prepare_prompt($type, $data, $file_contents);

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

function claude_ai_scanner_prepare_prompt($type, $data, $file_contents) {
    $type_label = $type === 'plugin' ? 'Plugin' : 'Theme';

    $prompt = <<<PROMPT
Analyze this WordPress $type_label and provide a comprehensive report covering:

1. **Security**: Identify any potential security vulnerabilities, unsafe functions, or security best practices not followed.
2. **Code Quality**: Evaluate code structure, best practices, and maintainability.
3. **Documentation**: Review the quality and completeness of documentation.
4. **WordPress 7.0 Compatibility**: Check if it appears compatible with WordPress 7.0 (check for deprecated functions, new API usage opportunities).

$type_label Information:
Name: {$data['Name']}
Version: {$data['Version']}
Description: {$data['Description'] ?? 'N/A'}
Author: {$data['Author'] ?? 'N/A'}

Code Preview:
$file_contents

Provide your analysis in a structured format with clear sections. Be specific and actionable.
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
