<?php
/**
 * Rate Limiter Class - Prevent API quota exhaustion and abuse
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_Rate_Limiter {
    /**
     * Rate limit key prefix
     *
     * @var string
     */
    private static $prefix = 'claude_ai_ratelimit_';

    /**
     * API call tracking
     *
     * @var string
     */
    private static $api_calls_key = 'claude_ai_api_calls';

    /**
     * Check if user can perform scan
     *
     * @param int $user_id WordPress user ID.
     * @return array Result with 'allowed' bool and 'message' string.
     */
    public static function check_user_scan_limit($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $limits = get_option('claude_ai_scanner_limits', [
            'per_hour' => 10,
            'per_day' => 50,
            'concurrent' => 3,
        ]);

        $limits = apply_filters('claude_ai_scanner_scan_limits', $limits);

        // Check hourly limit
        $hourly_scans = self::count_scans_in_window($user_id, HOUR_IN_SECONDS);
        if ($hourly_scans >= $limits['per_hour']) {
            return [
                'allowed' => false,
                'message' => sprintf(
                    'Hourly scan limit reached (%d/%d). Try again in a few minutes.',
                    $hourly_scans,
                    $limits['per_hour']
                ),
                'reset_in' => self::get_window_reset_time($user_id, HOUR_IN_SECONDS),
            ];
        }

        // Check daily limit
        $daily_scans = self::count_scans_in_window($user_id, DAY_IN_SECONDS);
        if ($daily_scans >= $limits['per_day']) {
            return [
                'allowed' => false,
                'message' => sprintf(
                    'Daily scan limit reached (%d/%d). Try again tomorrow.',
                    $daily_scans,
                    $limits['per_day']
                ),
                'reset_in' => self::get_window_reset_time($user_id, DAY_IN_SECONDS),
            ];
        }

        // Check concurrent scans
        $concurrent = self::count_concurrent_scans($user_id);
        if ($concurrent >= $limits['concurrent']) {
            return [
                'allowed' => false,
                'message' => sprintf(
                    'Too many concurrent scans (%d/%d). Wait for current scans to complete.',
                    $concurrent,
                    $limits['concurrent']
                ),
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Check if API rate limit is safe
     *
     * @return array Result with 'allowed' bool and 'message' string.
     */
    public static function check_api_rate_limit() {
        $limits = apply_filters('claude_ai_scanner_api_limits', [
            'per_minute' => 5,
            'per_hour' => 100,
            'requests_per_second' => 3,
        ]);

        // Check per-minute limit
        $minute_calls = self::count_api_calls(MINUTE_IN_SECONDS);
        if ($minute_calls >= $limits['per_minute']) {
            $wait_time = self::calculate_backoff_time($minute_calls, $limits['per_minute']);
            return [
                'allowed' => false,
                'message' => 'API rate limit approaching. Please wait before scanning again.',
                'wait_seconds' => $wait_time,
                'status' => 'rate_limit_minute',
            ];
        }

        // Check per-hour limit
        $hour_calls = self::count_api_calls(HOUR_IN_SECONDS);
        if ($hour_calls >= $limits['per_hour']) {
            return [
                'allowed' => false,
                'message' => 'Hourly API quota nearly reached. Please try again in an hour.',
                'status' => 'rate_limit_hour',
            ];
        }

        // Check requests per second
        if (!self::check_request_spacing($limits['requests_per_second'])) {
            return [
                'allowed' => false,
                'message' => 'Requests too frequent. Please wait a moment.',
                'wait_seconds' => 1,
                'status' => 'rate_limit_second',
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Record API call
     *
     * @param string $scan_type Scan type.
     * @param int    $user_id User ID.
     * @param int    $tokens Tokens used (estimate).
     * @return bool
     */
    public static function record_api_call($scan_type, $user_id = null, $tokens = 2000) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $key = self::$prefix . 'user_' . $user_id;
        $scans = get_transient($key) ?: [];

        $scans[] = [
            'timestamp' => time(),
            'type' => $scan_type,
            'tokens' => $tokens,
        ];

        set_transient($key, $scans, DAY_IN_SECONDS);

        // Record global API call
        $api_key = self::$api_calls_key;
        $calls = get_transient($api_key) ?: [];
        $calls[] = [
            'timestamp' => time(),
            'user_id' => $user_id,
            'type' => $scan_type,
        ];
        set_transient($api_key, $calls, HOUR_IN_SECONDS);

        return true;
    }

    /**
     * Record job completion
     *
     * @param string $job_id Job ID.
     * @return bool
     */
    public static function record_job_completion($job_id) {
        $key = self::$prefix . 'job_' . $job_id;
        return set_transient($key, 'completed', HOUR_IN_SECONDS);
    }

    /**
     * Count scans in time window
     *
     * @param int $user_id User ID.
     * @param int $window Time window in seconds.
     * @return int
     */
    private static function count_scans_in_window($user_id, $window) {
        $key = self::$prefix . 'user_' . $user_id;
        $scans = get_transient($key) ?: [];

        $now = time();
        return count(array_filter($scans, function($scan) use ($now, $window) {
            return ($now - $scan['timestamp']) < $window;
        }));
    }

    /**
     * Count concurrent scans
     *
     * @param int $user_id User ID.
     * @return int
     */
    private static function count_concurrent_scans($user_id) {
        global $wpdb;

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options}
            WHERE option_name LIKE %s AND option_value LIKE %s",
            $wpdb->esc_like(self::$prefix . 'job_') . '%',
            '%pending%'
        )));
    }

    /**
     * Count API calls in time window
     *
     * @param int $window Time window in seconds.
     * @return int
     */
    private static function count_api_calls($window) {
        $key = self::$api_calls_key;
        $calls = get_transient($key) ?: [];

        $now = time();
        return count(array_filter($calls, function($call) use ($now, $window) {
            return ($now - $call['timestamp']) < $window;
        }));
    }

    /**
     * Check request spacing (rate per second)
     *
     * @param int $requests_per_second Max requests per second.
     * @return bool
     */
    private static function check_request_spacing($requests_per_second) {
        $key = self::$prefix . 'last_request';
        $last_request = get_transient($key);

        if (!$last_request) {
            set_transient($key, microtime(true), MINUTE_IN_SECONDS);
            return true;
        }

        $min_interval = 1 / $requests_per_second;
        $elapsed = microtime(true) - $last_request;

        if ($elapsed < $min_interval) {
            return false;
        }

        set_transient($key, microtime(true), MINUTE_IN_SECONDS);
        return true;
    }

    /**
     * Calculate exponential backoff time
     *
     * @param int $current Current count.
     * @param int $limit Limit.
     * @return int Seconds to wait.
     */
    private static function calculate_backoff_time($current, $limit) {
        $over_limit = $current - $limit;
        return min(pow(2, $over_limit), 60); // Max 60 second wait
    }

    /**
     * Get time until window resets
     *
     * @param int $user_id User ID.
     * @param int $window Window size in seconds.
     * @return int Seconds until reset.
     */
    private static function get_window_reset_time($user_id, $window) {
        $key = self::$prefix . 'user_' . $user_id;
        $scans = get_transient($key) ?: [];

        if (empty($scans)) {
            return 0;
        }

        $oldest = min(array_column($scans, 'timestamp'));
        $reset_time = $oldest + $window;

        return max(0, $reset_time - time());
    }

    /**
     * Get rate limit stats for admin dashboard
     *
     * @param int $user_id User ID (default current user).
     * @return array
     */
    public static function get_stats($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $limits = get_option('claude_ai_scanner_limits', [
            'per_hour' => 10,
            'per_day' => 50,
        ]);

        $limits = apply_filters('claude_ai_scanner_scan_limits', $limits);

        $hourly = self::count_scans_in_window($user_id, HOUR_IN_SECONDS);
        $daily = self::count_scans_in_window($user_id, DAY_IN_SECONDS);
        $api_hour = self::count_api_calls(HOUR_IN_SECONDS);

        return [
            'scans_this_hour' => $hourly . '/' . $limits['per_hour'],
            'scans_today' => $daily . '/' . $limits['per_day'],
            'api_calls_hour' => $api_hour,
            'hourly_remaining' => max(0, $limits['per_hour'] - $hourly),
            'daily_remaining' => max(0, $limits['per_day'] - $daily),
            'api_quota_status' => $api_hour < 80 ? 'Good' : ($api_hour < 95 ? 'Warning' : 'Critical'),
        ];
    }

    /**
     * Clear user rate limit data (admin only)
     *
     * @param int $user_id User ID.
     * @return bool
     */
    public static function clear_user_limits($user_id) {
        if (!current_user_can('manage_options')) {
            return false;
        }

        $key = self::$prefix . 'user_' . $user_id;
        return delete_transient($key);
    }
}
