<?php
/**
 * Claude AI Scanner - WordPress Site Health & Performance Analyzer
 *
 * @package Claude_AI_Scanner
 * @author Aung My In
 * @license GPL v2 or later
 *
 * Plugin Name: Claude AI Scanner
 * Plugin URI: https://github.com/aungmyin/wordpress7-docker
 * Description: Comprehensive AI-powered WordPress site analyzer. Scan for security issues, performance bottlenecks, broken links, SEO problems, and redirects. Get specific recommendations with Claude AI.
 * Version: 3.0.0
 * Author: Aung My In
 * Author URI: https://github.com/aungmyin
 * License: GPL v2 or later
 * Text Domain: claude-ai-scanner
 * Domain Path: /languages
 * Requires: 7.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CLAUDE_AI_SCANNER_FILE', __FILE__);
define('CLAUDE_AI_SCANNER_DIR', plugin_dir_path(__FILE__));
define('CLAUDE_AI_SCANNER_URL', plugin_dir_url(__FILE__));
define('CLAUDE_AI_SCANNER_VERSION', '3.0.0');
define('CLAUDE_AI_SCANNER_NONCE_ACTION', 'claude_ai_scanner_action');

// Only load plugin in admin area
if (!is_admin()) {
    return;
}

// Load main plugin class
require_once CLAUDE_AI_SCANNER_DIR . 'includes/class-plugin.php';

// Initialize plugin when WordPress loads
add_action('plugins_loaded', function() {
    Claude_AI_Scanner_Plugin::get_instance();
}, 10);
