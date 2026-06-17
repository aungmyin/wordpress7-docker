<?php
/**
 * Cache Class - Smart caching for scan results
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_Cache {
    /**
     * Cache key prefix
     *
     * @var string
     */
    private static $prefix = 'claude_ai_cache_';

    /**
     * Cache expiration (30 days by default)
     *
     * @var int
     */
    private static $expiration = 30 * DAY_IN_SECONDS;

    /**
     * Get cached result by content hash
     *
     * @param string $scan_type Scan type.
     * @param string $content Content to hash.
     * @return string|null Cached result or null if not found/expired.
     */
    public static function get($scan_type, $content) {
        $hash = self::hash_content($content);
        $key = self::$prefix . $scan_type . '_' . $hash;

        return get_transient($key);
    }

    /**
     * Set cache for result
     *
     * @param string $scan_type Scan type.
     * @param string $content Content to hash.
     * @param string $result Scan result to cache.
     * @return bool
     */
    public static function set($scan_type, $content, $result) {
        $hash = self::hash_content($content);
        $key = self::$prefix . $scan_type . '_' . $hash;

        return set_transient($key, $result, self::$expiration);
    }

    /**
     * Hash content for cache key
     *
     * @param string|array $content Content to hash.
     * @return string
     */
    private static function hash_content($content) {
        if (is_array($content)) {
            $content = wp_json_encode($content);
        }
        return substr(md5($content), 0, 12);
    }

    /**
     * Clear cache for scan type
     *
     * @param string $scan_type Scan type to clear.
     * @return int Number of cleared items.
     */
    public static function clear($scan_type) {
        global $wpdb;

        $pattern = $wpdb->esc_like(self::$prefix . $scan_type) . '%';

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $pattern
        ));
    }

    /**
     * Clear all cache
     *
     * @return int Number of cleared items.
     */
    public static function clear_all() {
        global $wpdb;

        $pattern = $wpdb->esc_like(self::$prefix) . '%';

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $pattern
        ));
    }

    /**
     * Get cache stats
     *
     * @return array
     */
    public static function get_stats() {
        global $wpdb;

        $pattern = $wpdb->esc_like(self::$prefix) . '%';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
            $pattern
        ));

        $size = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(CHAR_LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE %s",
            $pattern
        ));

        return [
            'cached_results' => intval($count),
            'cache_size_kb' => round(intval($size) / 1024, 2),
        ];
    }
}
