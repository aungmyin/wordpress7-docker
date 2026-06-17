<?php
/**
 * Job Queue Class - Async scanning with WP Cron
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_Job_Queue {
    /**
     * Queue option prefix
     *
     * @var string
     */
    private static $prefix = 'claude_ai_scan_queue_';

    /**
     * Progress option prefix
     *
     * @var string
     */
    private static $progress_prefix = 'claude_ai_scan_progress_';

    /**
     * Add scan job to queue
     *
     * @param string $scan_type Scan type.
     * @param array  $options Scan options (e.g., URL for single-url scans).
     * @return string Job ID
     */
    public static function enqueue($scan_type, $options = []) {
        $job_id = wp_generate_uuid4();
        $site_id = get_current_blog_id();

        $job = [
            'id' => $job_id,
            'site_id' => $site_id,
            'type' => $scan_type,
            'options' => $options,
            'status' => 'pending',
            'created' => current_time('mysql'),
            'started' => null,
            'completed' => null,
        ];

        set_transient(self::$prefix . $job_id, $job, HOUR_IN_SECONDS * 4);
        update_option('claude_ai_pending_jobs', array_merge(
            get_option('claude_ai_pending_jobs', []),
            [$job_id]
        ));

        // Schedule WP Cron event if not already scheduled
        if (!wp_next_scheduled('claude_ai_process_queue')) {
            wp_schedule_event(time(), 'five_minutes', 'claude_ai_process_queue');
        }

        return $job_id;
    }

    /**
     * Get job status
     *
     * @param string $job_id Job ID.
     * @return array|null Job object or null if not found.
     */
    public static function get_job($job_id) {
        return get_transient(self::$prefix . $job_id);
    }

    /**
     * Update job progress
     *
     * @param string $job_id Job ID.
     * @param int    $current Current item count.
     * @param int    $total Total items to process.
     * @param string $message Status message.
     * @return bool
     */
    public static function update_progress($job_id, $current, $total, $message = '') {
        $progress = [
            'job_id' => $job_id,
            'current' => $current,
            'total' => $total,
            'percent' => $total > 0 ? intval(($current / $total) * 100) : 0,
            'message' => $message,
            'updated' => current_time('mysql'),
        ];

        return set_transient(self::$progress_prefix . $job_id, $progress, HOUR_IN_SECONDS);
    }

    /**
     * Get job progress
     *
     * @param string $job_id Job ID.
     * @return array|null Progress object or null if not found.
     */
    public static function get_progress($job_id) {
        return get_transient(self::$progress_prefix . $job_id);
    }

    /**
     * Mark job as complete
     *
     * @param string $job_id Job ID.
     * @param string $result Scan result.
     * @return bool
     */
    public static function complete_job($job_id, $result) {
        $job = self::get_job($job_id);

        if (!$job) {
            return false;
        }

        $job['status'] = 'completed';
        $job['completed'] = current_time('mysql');
        $job['result'] = $result;

        set_transient(self::$prefix . $job_id, $job, HOUR_IN_SECONDS * 24);

        // Remove from pending list
        $pending = get_option('claude_ai_pending_jobs', []);
        $pending = array_filter($pending, function($id) use ($job_id) {
            return $id !== $job_id;
        });
        update_option('claude_ai_pending_jobs', $pending);

        return true;
    }

    /**
     * Mark job as failed
     *
     * @param string $job_id Job ID.
     * @param string $error Error message.
     * @return bool
     */
    public static function fail_job($job_id, $error) {
        $job = self::get_job($job_id);

        if (!$job) {
            return false;
        }

        $job['status'] = 'failed';
        $job['error'] = $error;
        $job['completed'] = current_time('mysql');

        set_transient(self::$prefix . $job_id, $job, HOUR_IN_SECONDS * 24);

        // Remove from pending list
        $pending = get_option('claude_ai_pending_jobs', []);
        $pending = array_filter($pending, function($id) use ($job_id) {
            return $id !== $job_id;
        });
        update_option('claude_ai_pending_jobs', $pending);

        return true;
    }

    /**
     * Process queue (called by WP Cron)
     *
     * @return void
     */
    public static function process_queue() {
        $pending = get_option('claude_ai_pending_jobs', []);

        if (empty($pending)) {
            return;
        }

        // Process first pending job
        $job_id = reset($pending);
        $job = self::get_job($job_id);

        if (!$job) {
            return;
        }

        self::process_job($job);
    }

    /**
     * Process single job
     *
     * @param array $job Job object.
     * @return void
     */
    private static function process_job($job) {
        $job_id = $job['id'];
        $api_key = get_option('claude_ai_scanner_api_key', '');

        if (empty($api_key)) {
            self::fail_job($job_id, 'Claude API key not configured');
            return;
        }

        switch_to_blog($job['site_id']);

        try {
            $scanner_class = self::get_scanner_class($job['type']);

            if (!$scanner_class || !class_exists($scanner_class)) {
                throw new Exception('Invalid scanner type: ' . $job['type']);
            }

            self::update_progress($job_id, 0, 100, 'Initializing scanner...');

            // Instantiate scanner
            if ($job['type'] === 'single-url' && isset($job['options']['url'])) {
                $scanner = new $scanner_class($api_key, $job['options']['url']);
            } else {
                $scanner = new $scanner_class($api_key);
            }

            self::update_progress($job_id, 25, 100, 'Running analysis...');

            // Run scan
            $result = $scanner->scan();

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            self::update_progress($job_id, 75, 100, 'Saving results...');

            // Save result
            $scan_data = [];
            if (method_exists($scanner, 'get_export_data')) {
                $scan_data = $scanner->get_export_data();
            }
            Claude_AI_Storage::save_result($job['type'], $result, $scan_data);

            // Generate report
            $url = isset($job['options']['url']) ? $job['options']['url'] : '';
            $markdown = Claude_AI_Report_Generator::generate_markdown($job['type'], $result, $scan_data, $url);
            Claude_AI_Report_Generator::save_report($job['type'], $markdown);

            self::update_progress($job_id, 100, 100, 'Complete');
            self::complete_job($job_id, $result);

        } catch (Exception $e) {
            self::fail_job($job_id, $e->getMessage());
        } finally {
            restore_current_blog();
        }
    }

    /**
     * Get scanner class for type
     *
     * @param string $type Scanner type.
     * @return string|null Scanner class name.
     */
    private static function get_scanner_class($type) {
        $scanners = [
            'performance' => 'Claude_AI_Performance_Scanner',
            '404' => 'Claude_AI_Link_Scanner',
            'seo' => 'Claude_AI_SEO_Scanner',
            'redirects' => 'Claude_AI_Redirect_Scanner',
            'single-url' => 'Claude_AI_Single_URL_Scanner',
        ];

        return $scanners[$type] ?? null;
    }

    /**
     * Clear expired jobs
     *
     * @return int Number of cleared jobs.
     */
    public static function cleanup_old_jobs() {
        global $wpdb;

        // Delete jobs older than 24 hours
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name < %s",
            $wpdb->esc_like(self::$prefix) . '%',
            date('Y-m-d H:i:s', time() - HOUR_IN_SECONDS * 24)
        ));
    }
}
