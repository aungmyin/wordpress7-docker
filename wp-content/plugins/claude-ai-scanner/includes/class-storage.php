<?php
/**
 * Storage Class - Save and retrieve scan results
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_Storage {
    /**
     * Option name for storing scan results
     *
     * @var string
     */
    private static $option_key = 'claude_ai_scanner_results';

    /**
     * Save scan result
     *
     * @param string $scan_type Type of scan.
     * @param string $result Scan result/analysis.
     * @param array  $data Raw scan data.
     * @return bool
     */
    public static function save_result($scan_type, $result, $data = []) {
        $results = self::get_all_results();

        $scan_result = [
            'type' => $scan_type,
            'result' => $result,
            'data' => $data,
            'timestamp' => current_time('mysql'),
            'date' => current_time('Y-m-d H:i:s'),
        ];

        // Parse result to extract categories and counts
        $scan_result['categories'] = self::parse_result_categories($result, $scan_type);
        $scan_result['summary'] = self::generate_summary($scan_type, $data);

        // Keep last 10 scans
        if (is_array($results)) {
            array_unshift($results, $scan_result);
            $results = array_slice($results, 0, 10);
        } else {
            $results = [$scan_result];
        }

        return update_option(self::$option_key, $results);
    }

    /**
     * Get all scan results
     *
     * @return array
     */
    public static function get_all_results() {
        $results = get_option(self::$option_key, []);
        return is_array($results) ? $results : [];
    }

    /**
     * Get latest scan result
     *
     * @return array|null
     */
    public static function get_latest_result() {
        $results = self::get_all_results();
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Get scan result by type
     *
     * @param string $scan_type Scan type.
     * @return array|null
     */
    public static function get_result_by_type($scan_type) {
        $results = self::get_all_results();
        foreach ($results as $result) {
            if ($result['type'] === $scan_type) {
                return $result;
            }
        }
        return null;
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
        return delete_option(self::$option_key);
    }

    /**
     * Get dashboard statistics
     *
     * @return array
     */
    public static function get_dashboard_stats() {
        $results = self::get_all_results();
        $stats = [
            'total_scans' => count($results),
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
}
