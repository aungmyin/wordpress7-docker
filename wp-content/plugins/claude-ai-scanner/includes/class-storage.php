<?php
/**
 * Storage Class - Save and retrieve scan results from database
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_Storage {
    /**
     * Save scan result to database
     *
     * @param string $scan_type Type of scan.
     * @param string $result Scan result/analysis.
     * @param array  $data Raw scan data.
     * @return bool
     */
    public static function save_result($scan_type, $result, $data = []) {
        global $wpdb;

        $table_name = Claude_AI_Database::get_table_name();

        // Parse categories and summary
        $categories = self::parse_result_categories($result, $scan_type);
        $summary = self::generate_summary($scan_type, $data);

        $insert_data = [
            'site_id' => get_current_blog_id(),
            'scan_type' => $scan_type,
            'result' => $result,
            'data' => maybe_serialize($data),
            'categories' => maybe_serialize($categories),
            'summary' => maybe_serialize($summary),
            'timestamp' => current_time('mysql'),
        ];

        $formats = ['%d', '%s', '%s', '%s', '%s', '%s', '%s'];

        $result = $wpdb->insert($table_name, $insert_data, $formats);

        // Clean up old scans (keep last 100)
        self::cleanup_old_results();

        return $result !== false;
    }

    /**
     * Get all scan results
     *
     * @param int $limit Maximum results to return.
     * @return array
     */
    public static function get_all_results($limit = 10) {
        global $wpdb;

        $table_name = Claude_AI_Database::get_table_name();
        $site_id = get_current_blog_id();

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE site_id = %d ORDER BY timestamp DESC LIMIT %d",
                $site_id,
                $limit
            )
        );

        if (empty($results)) {
            return [];
        }

        return array_map(function($row) {
            return [
                'id' => $row->id,
                'type' => $row->scan_type,
                'result' => $row->result,
                'data' => maybe_unserialize($row->data),
                'categories' => maybe_unserialize($row->categories),
                'summary' => maybe_unserialize($row->summary),
                'timestamp' => $row->timestamp,
                'date' => $row->date_created,
            ];
        }, $results);
    }

    /**
     * Get latest scan result
     *
     * @return array|null
     */
    public static function get_latest_result() {
        $results = self::get_all_results(1);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Get scan result by type
     *
     * @param string $scan_type Scan type.
     * @return array|null
     */
    public static function get_result_by_type($scan_type) {
        global $wpdb;

        $table_name = Claude_AI_Database::get_table_name();
        $site_id = get_current_blog_id();

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE site_id = %d AND scan_type = %s ORDER BY timestamp DESC LIMIT 1",
                $site_id,
                $scan_type
            )
        );

        if (empty($result)) {
            return null;
        }

        return [
            'id' => $result->id,
            'type' => $result->scan_type,
            'result' => $result->result,
            'data' => maybe_unserialize($result->data),
            'categories' => maybe_unserialize($result->categories),
            'summary' => maybe_unserialize($result->summary),
            'timestamp' => $result->timestamp,
            'date' => $result->date_created,
        ];
    }

    /**
     * Parse result to extract categories
     *
     * @param string $result Analysis result.
     * @param string $scan_type Scan type.
     * @return array
     */
    private static function parse_result_categories($result, $scan_type) {
        $categories = [];

        switch ($scan_type) {
            case 'performance':
                $categories = [
                    'Good Load Time' => substr_count($result, 'Good'),
                    'Fair Load Time' => substr_count($result, 'Fair'),
                    'Poor Load Time' => substr_count($result, 'Poor'),
                ];
                break;

            case '404':
                $categories = [
                    'Critical Issues' => substr_count($result, 'CRITICAL'),
                    'High Issues' => substr_count($result, 'HIGH'),
                    'Warnings' => substr_count($result, 'recommendation'),
                ];
                break;

            case 'seo':
                $categories = [
                    'Meta Tags' => substr_count($result, 'meta'),
                    'Content' => substr_count($result, 'content'),
                    'Structure' => substr_count($result, 'heading'),
                ];
                break;

            case 'redirects':
                $categories = [
                    '301 Redirects' => substr_count($result, '301'),
                    '302 Redirects' => substr_count($result, '302'),
                    'Chains' => substr_count($result, 'chain'),
                ];
                break;

            case 'single-url':
                $categories = [
                    'Performance' => substr_count($result, 'load'),
                    'SEO' => substr_count($result, 'meta'),
                    'Links' => substr_count($result, 'link'),
                ];
                break;

            default:
                $categories = ['Results' => 1];
        }

        return array_filter($categories);
    }

    /**
     * Generate scan summary
     *
     * @param string $scan_type Scan type.
     * @param array  $data Raw scan data.
     * @return array
     */
    private static function generate_summary($scan_type, $data) {
        $summary = [
            'type' => $scan_type,
            'items_scanned' => 0,
            'issues_found' => 0,
        ];

        switch ($scan_type) {
            case 'performance':
                $summary['items_scanned'] = count($data);
                $summary['metrics'] = 'Page load analysis';
                break;

            case '404':
                $summary['items_scanned'] = count($data);
                $summary['issues_found'] = count($data);
                $summary['metrics'] = 'Broken links detected';
                break;

            case 'seo':
                $summary['items_scanned'] = count($data);
                $summary['metrics'] = 'SEO pages analyzed';
                break;

            case 'single-url':
                $summary['items_scanned'] = 1;
                $summary['metrics'] = 'Single page analysis';
                break;
        }

        return $summary;
    }

    /**
     * Clear all results
     *
     * @return bool
     */
    public static function clear_results() {
        global $wpdb;

        $table_name = Claude_AI_Database::get_table_name();
        $site_id = get_current_blog_id();

        return $wpdb->delete($table_name, ['site_id' => $site_id]) !== false;
    }

    /**
     * Get dashboard statistics
     *
     * @return array
     */
    public static function get_dashboard_stats() {
        global $wpdb;

        $table_name = Claude_AI_Database::get_table_name();
        $site_id = get_current_blog_id();

        $total_scans = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE site_id = %d",
                $site_id
            )
        );

        $results = self::get_all_results(100);

        $stats = [
            'total_scans' => intval($total_scans),
            'last_scan' => null,
            'scan_types' => [],
        ];

        if (!empty($results)) {
            $stats['last_scan'] = $results[0];
            foreach ($results as $result) {
                if (!isset($stats['scan_types'][$result['type']])) {
                    $stats['scan_types'][$result['type']] = 0;
                }
                $stats['scan_types'][$result['type']]++;
            }
        }

        return $stats;
    }

    /**
     * Cleanup old scan results (keep last 100 per site)
     *
     * @return int Number of deleted records
     */
    private static function cleanup_old_results() {
        global $wpdb;

        $table_name = Claude_AI_Database::get_table_name();
        $site_id = get_current_blog_id();

        // Keep last 100 scans per site
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE site_id = %d AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM {$table_name} WHERE site_id = %d ORDER BY timestamp DESC LIMIT 100
                    ) AS keep
                )",
                $site_id,
                $site_id
            )
        );

        return $result !== false ? $result : 0;
    }
}
