<?php
/**
 * Database Class - Migrations and schema management
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_Database {
    /**
     * Database version option key
     *
     * @var string
     */
    private static $db_version_key = 'claude_ai_scanner_db_version';

    /**
     * Current database version
     *
     * @var int
     */
    private static $current_version = 1;

    /**
     * Initialize database
     *
     * @return bool
     */
    public static function init() {
        $installed_version = get_option(self::$db_version_key, 0);

        if ($installed_version < self::$current_version) {
            return self::migrate($installed_version);
        }

        return true;
    }

    /**
     * Run migrations
     *
     * @param int $from_version Starting version.
     * @return bool
     */
    private static function migrate($from_version) {
        // Version 0 → Version 1: Create results table
        if ($from_version < 1) {
            if (!self::create_results_table()) {
                return false;
            }

            // Migrate data from wp_options if it exists
            self::migrate_from_options();
        }

        update_option(self::$db_version_key, self::$current_version);
        return true;
    }

    /**
     * Create results table
     *
     * @return bool
     */
    private static function create_results_table() {
        global $wpdb;

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT NOT NULL AUTO_INCREMENT,
            site_id BIGINT NOT NULL,
            scan_type VARCHAR(50) NOT NULL,
            result LONGTEXT NOT NULL,
            data LONGTEXT,
            categories LONGTEXT,
            summary LONGTEXT,
            timestamp DATETIME NOT NULL,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY site_scan_type (site_id, scan_type),
            KEY timestamp_idx (timestamp),
            KEY site_timestamp (site_id, timestamp DESC)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $result = dbDelta($sql);

        return !empty($result) || self::table_exists($table_name);
    }

    /**
     * Check if table exists
     *
     * @param string $table_name Table name.
     * @return bool
     */
    private static function table_exists($table_name) {
        global $wpdb;

        $result = $wpdb->get_var($wpdb->prepare(
            'SHOW TABLES LIKE %s',
            $table_name
        ));

        return !empty($result);
    }

    /**
     * Migrate data from old wp_options storage to new table
     *
     * @return bool
     */
    private static function migrate_from_options() {
        global $wpdb;

        // Get old data from options
        $old_results = get_option('claude_ai_scanner_results', []);

        if (empty($old_results) || !is_array($old_results)) {
            return true;
        }

        $table_name = self::get_table_name();
        $site_id = get_current_blog_id();
        $inserted = 0;

        foreach ($old_results as $result) {
            $data = [
                'site_id' => $site_id,
                'scan_type' => isset($result['type']) ? $result['type'] : 'unknown',
                'result' => isset($result['result']) ? $result['result'] : '',
                'data' => isset($result['data']) ? maybe_serialize($result['data']) : null,
                'categories' => isset($result['categories']) ? maybe_serialize($result['categories']) : null,
                'summary' => isset($result['summary']) ? maybe_serialize($result['summary']) : null,
                'timestamp' => isset($result['timestamp']) ? $result['timestamp'] : current_time('mysql'),
            ];

            $formats = ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];

            if ($wpdb->insert($table_name, $data, $formats)) {
                $inserted++;
            }
        }

        // Clear old data from options (but keep it as backup for 7 days)
        update_option('claude_ai_scanner_results_migrated_' . time(), $old_results);

        return $inserted > 0 || count($old_results) === 0;
    }

    /**
     * Get full table name with prefix
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'claude_ai_scanner_results';
    }

    /**
     * Uninstall database (drop table)
     *
     * @return bool
     */
    public static function uninstall() {
        global $wpdb;

        $table_name = self::get_table_name();

        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");

        delete_option(self::$db_version_key);

        return true;
    }

    /**
     * Get database status
     *
     * @return array
     */
    public static function get_status() {
        global $wpdb;

        $table_name = self::get_table_name();
        $exists = self::table_exists($table_name);

        if ($exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            $size = $wpdb->get_var("SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) FROM information_schema.TABLES WHERE table_schema = DATABASE() AND table_name = '{$table_name}'");
        } else {
            $count = 0;
            $size = 0;
        }

        return [
            'table_exists' => $exists,
            'db_version' => get_option(self::$db_version_key, 0),
            'current_version' => self::$current_version,
            'result_count' => $count,
            'table_size_mb' => $size,
        ];
    }

    /**
     * Cleanup old backup data from migrations
     *
     * @return int Number of cleaned backups
     */
    public static function cleanup_migration_backups() {
        global $wpdb;

        $pattern = 'claude_ai_scanner_results_migrated_%';
        $options = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_id, option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                $pattern
            )
        );

        $cleaned = 0;

        foreach ($options as $option) {
            // Keep backups for 7 days
            if (preg_match('/migrated_(\d+)$/', $option->option_name, $matches)) {
                $timestamp = $matches[1];
                $age_days = (time() - $timestamp) / DAY_IN_SECONDS;

                if ($age_days > 7) {
                    delete_option($option->option_name);
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }
}
