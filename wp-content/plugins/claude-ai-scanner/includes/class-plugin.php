<?php
/**
 * Main Plugin Class
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_Scanner_Plugin {
    /**
     * Plugin instance
     *
     * @var self
     */
    private static $instance;

    /**
     * API key
     *
     * @var string
     */
    private $api_key;

    /**
     * Registered scanners
     *
     * @var array
     */
    private $scanners = [];

    /**
     * Get plugin instance
     *
     * @return self
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->api_key = get_option('claude_ai_scanner_api_key', '');

        // Load includes
        $this->load_includes();

        // Register hooks
        $this->register_hooks();

        // Register scanners
        $this->register_scanners();
    }

    /**
     * Load include files
     *
     * @return void
     */
    private function load_includes() {
        $includes_dir = plugin_dir_path(__FILE__);

        require_once $includes_dir . 'class-database.php';
        require_once $includes_dir . 'class-storage.php';
        require_once $includes_dir . 'class-cache.php';
        require_once $includes_dir . 'class-rate-limiter.php';
        require_once $includes_dir . 'class-job-queue.php';
        require_once $includes_dir . 'class-report-generator.php';
        require_once $includes_dir . 'class-scanner.php';
        require_once $includes_dir . 'class-performance-scanner.php';
        require_once $includes_dir . 'class-link-scanner.php';
        require_once $includes_dir . 'class-seo-scanner.php';
        require_once $includes_dir . 'class-redirect-scanner.php';
        require_once $includes_dir . 'class-single-url-scanner.php';
        require_once $includes_dir . 'class-export.php';
    }

    /**
     * Register WordPress hooks
     *
     * @return void
     */
    private function register_hooks() {
        add_action('admin_init', [$this, 'init_database']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_run_advanced_scan', [$this, 'ajax_run_scan']);
        add_action('wp_ajax_download_report', [$this, 'ajax_download_report']);
        add_action('wp_ajax_check_scan_progress', [$this, 'ajax_check_progress']);
        add_action('claude_ai_process_queue', ['Claude_AI_Job_Queue', 'process_queue']);
    }

    /**
     * Register scanners
     *
     * @return void
     */
    private function register_scanners() {
        $this->scanners = [
            'performance' => 'Claude_AI_Performance_Scanner',
            '404' => 'Claude_AI_Link_Scanner',
            'seo' => 'Claude_AI_SEO_Scanner',
            'redirects' => 'Claude_AI_Redirect_Scanner',
            'single-url' => 'Claude_AI_Single_URL_Scanner',
        ];
    }

    /**
     * Add admin menu
     *
     * @return void
     */
    public function add_admin_menu() {
        add_menu_page(
            'Claude AI Scanner',
            'AI Scanner',
            'manage_options',
            'claude-ai-scanner-dashboard',
            [$this, 'render_dashboard_page'],
            'dashicons-search',
            80
        );

        add_submenu_page(
            'claude-ai-scanner-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'claude-ai-scanner-dashboard',
            [$this, 'render_dashboard_page']
        );

        add_submenu_page(
            'claude-ai-scanner-dashboard',
            'Advanced Scan',
            'Advanced Scan',
            'manage_options',
            'claude-ai-scanner',
            [$this, 'render_advanced_page']
        );

        add_submenu_page(
            'claude-ai-scanner-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'claude-ai-scanner-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Enqueue admin assets
     *
     * @return void
     */
    public function enqueue_admin_assets() {
        wp_enqueue_script('claude-ai-scanner', plugin_dir_url(CLAUDE_AI_SCANNER_FILE) . 'js/scanner.js', ['jquery'], CLAUDE_AI_SCANNER_VERSION);
        wp_localize_script('claude-ai-scanner', 'claudeAiScanner', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(CLAUDE_AI_SCANNER_NONCE_ACTION),
        ]);

        wp_enqueue_style('claude-ai-scanner', plugin_dir_url(CLAUDE_AI_SCANNER_FILE) . 'css/scanner.css', [], CLAUDE_AI_SCANNER_VERSION);
    }

    /**
     * Render dashboard page
     *
     * @return void
     */
    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        include plugin_dir_path(CLAUDE_AI_SCANNER_FILE) . 'templates/dashboard.php';
    }

    /**
     * Render advanced scanning page
     *
     * @return void
     */
    public function render_advanced_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        if (!$this->api_key) {
            echo '<div class="wrap"><div class="notice notice-error"><p>⚠️ Claude API key not configured. <a href="' . esc_url(admin_url('admin.php?page=claude-ai-scanner-settings')) . '">Configure it here</a></p></div></div>';
            return;
        }

        include plugin_dir_path(CLAUDE_AI_SCANNER_FILE) . 'templates/advanced-scan.php';
    }

    /**
     * Render settings page
     *
     * @return void
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claude_api_key'])) {
            check_admin_referer('claude_ai_scanner_settings');
            $api_key = sanitize_text_field($_POST['claude_api_key']);
            update_option('claude_ai_scanner_api_key', $api_key);
            echo '<div class="notice notice-success"><p>API Key saved successfully!</p></div>';
        }

        include plugin_dir_path(CLAUDE_AI_SCANNER_FILE) . 'templates/settings.php';
    }

    /**
     * AJAX run scan (sync or async based on site size)
     *
     * @return void
     */
    public function ajax_run_scan() {
        if (!is_admin() || !current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        check_ajax_referer(CLAUDE_AI_SCANNER_NONCE_ACTION);

        // Check rate limits first
        $user_limit = Claude_AI_Rate_Limiter::check_user_scan_limit();
        if (!$user_limit['allowed']) {
            wp_send_json_error([
                'message' => $user_limit['message'],
                'reset_in' => $user_limit['reset_in'] ?? null,
            ]);
        }

        $api_limit = Claude_AI_Rate_Limiter::check_api_rate_limit();
        if (!$api_limit['allowed']) {
            wp_send_json_error([
                'message' => $api_limit['message'],
                'wait_seconds' => $api_limit['wait_seconds'] ?? null,
            ]);
        }

        $scan_type = isset($_POST['scan_type']) ? sanitize_text_field($_POST['scan_type']) : '';

        if (!isset($this->scanners[$scan_type])) {
            wp_send_json_error('Invalid scan type');
        }

        // Check site size to determine sync vs async
        $post_count = $this->get_post_count();
        $use_async = $post_count > 500;

        if ($use_async) {
            // Queue async job for large sites
            $options = [];
            if ($scan_type === 'single-url') {
                $url = isset($_POST['url']) ? sanitize_url($_POST['url']) : '';
                if (empty($url)) {
                    wp_send_json_error('Please provide a URL');
                }
                $options['url'] = $url;
            }

            $job_id = Claude_AI_Job_Queue::enqueue($scan_type, $options);
            Claude_AI_Rate_Limiter::record_api_call($scan_type);

            wp_send_json_success([
                'async' => true,
                'job_id' => $job_id,
                'message' => 'Scan queued for background processing. Checking progress...',
            ]);
        } else {
            // Run sync for smaller sites
            $this->run_scan_sync($scan_type);
        }
    }

    /**
     * Run scan synchronously
     *
     * @param string $scan_type Scan type.
     * @return void
     */
    private function run_scan_sync($scan_type) {
        $scanner_class = $this->scanners[$scan_type];

        // Special handling for single URL scanner
        if ($scan_type === 'single-url') {
            $url = isset($_POST['url']) ? sanitize_url($_POST['url']) : '';
            if (empty($url)) {
                wp_send_json_error('Please provide a URL');
            }
            $scanner = new $scanner_class($this->api_key, $url);
        } else {
            $scanner = new $scanner_class($this->api_key);
        }

        $result = $scanner->scan();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        // Record API call for rate limiting
        Claude_AI_Rate_Limiter::record_api_call($scan_type);

        // Save result to database
        $scan_data = [];
        if (method_exists($scanner, 'get_export_data')) {
            $scan_data = $scanner->get_export_data();
        }
        Claude_AI_Storage::save_result($scan_type, $result, $scan_data);

        // Generate markdown report
        $url = isset($_POST['url']) ? sanitize_url($_POST['url']) : '';
        $markdown = Claude_AI_Report_Generator::generate_markdown($scan_type, $result, $scan_data, $url);
        $report_path = Claude_AI_Report_Generator::save_report($scan_type, $markdown);

        if (!is_wp_error($report_path)) {
            $result .= "\n\n📄 **Report saved:** " . basename($report_path);
        }

        wp_send_json_success([
            'async' => false,
            'result' => $result,
        ]);
    }

    /**
     * AJAX check scan progress
     *
     * @return void
     */
    public function ajax_check_progress() {
        if (!is_admin() || !current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $job_id = isset($_POST['job_id']) ? sanitize_text_field($_POST['job_id']) : '';

        if (empty($job_id)) {
            wp_send_json_error('Job ID required');
        }

        $job = Claude_AI_Job_Queue::get_job($job_id);

        if (!$job) {
            wp_send_json_error('Job not found');
        }

        $progress = Claude_AI_Job_Queue::get_progress($job_id);

        wp_send_json_success([
            'status' => $job['status'],
            'progress' => $progress,
            'result' => $job['result'] ?? null,
            'error' => $job['error'] ?? null,
        ]);
    }

    /**
     * Get total post count
     *
     * @return int
     */
    private function get_post_count() {
        $counts = wp_count_posts('post');
        return $counts->publish + $counts->draft + $counts->pending;
    }

    /**
     * AJAX download report
     *
     * @return void
     */
    public function ajax_download_report() {
        if (!is_admin() || !current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // Verify nonce from GET or POST
        $nonce = isset($_REQUEST['_ajax_nonce']) ? sanitize_text_field($_REQUEST['_ajax_nonce']) : '';
        if (!$nonce || !wp_verify_nonce($nonce, CLAUDE_AI_SCANNER_NONCE_ACTION)) {
            wp_send_json_error('Nonce verification failed');
        }

        $scan_type = isset($_POST['scan_type']) ? sanitize_text_field($_POST['scan_type']) : '';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';

        if (!isset($this->scanners[$scan_type])) {
            wp_send_json_error('Invalid scan type');
        }

        $scanner_class = $this->scanners[$scan_type];

        // Special handling for single URL scanner
        if ($scan_type === 'single-url') {
            $url = isset($_POST['url']) ? sanitize_url($_POST['url']) : '';
            if (empty($url)) {
                wp_send_json_error('Please provide a URL');
            }
            $scanner = new $scanner_class($this->api_key, $url);
        } else {
            $scanner = new $scanner_class($this->api_key);
        }

        if (!method_exists($scanner, 'get_export_data')) {
            wp_send_json_error('Scanner does not support exports');
        }

        $data = $scanner->get_export_data();
        $filename = 'scan-' . $scan_type . '-' . current_time('Y-m-d-His');

        if ($format === 'excel') {
            Claude_AI_Export::download_excel($data, $filename . '.csv');
        } else {
            Claude_AI_Export::download_csv($data, $filename . '.csv');
        }
    }

    /**
     * Initialize database (called on admin_init)
     *
     * @return void
     */
    public function init_database() {
        // Run migrations if needed
        Claude_AI_Database::init();

        // Cleanup old migration backups (weekly)
        if (wp_cache_get('claude_ai_scanner_cleanup') === false) {
            Claude_AI_Database::cleanup_migration_backups();
            wp_cache_set('claude_ai_scanner_cleanup', 1, '', WEEK_IN_SECONDS);
        }
    }

    /**
     * Plugin activation
     *
     * @return void
     */
    public static function activate() {
        if (!current_user_can('activate_plugins')) {
            wp_die('Insufficient permissions to activate plugin.');
        }

        // Initialize database and run migrations
        Claude_AI_Database::init();
    }

    /**
     * Uninstall plugin
     *
     * @return void
     */
    public static function uninstall() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Drop database table
        Claude_AI_Database::uninstall();

        // Delete configuration
        delete_option('claude_ai_scanner_api_key');
        delete_option('claude_ai_pending_jobs');

        // Clear WP Cron event
        wp_clear_scheduled_hook('claude_ai_process_queue');

        // Clear all cache
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like('claude_ai_cache_%') . '%'
        ));
    }

    /**
     * Get scanner by type
     *
     * @param string $type Scanner type.
     * @return Claude_AI_Scanner|null
     */
    public function get_scanner($type) {
        if (!isset($this->scanners[$type]) || !$this->api_key) {
            return null;
        }

        $scanner_class = $this->scanners[$type];
        return new $scanner_class($this->api_key);
    }
}
