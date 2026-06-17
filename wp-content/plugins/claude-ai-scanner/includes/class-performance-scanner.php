<?php
/**
 * Performance Scanner Class
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_Performance_Scanner extends Claude_AI_Scanner {
    protected $type = 'performance';

    /**
     * Run performance scan across top pages
     *
     * @return string|WP_Error
     */
    public function scan() {
        $perf_data = $this->collect_page_metrics();

        $prompt = $this->prepare_prompt($perf_data);

        return $this->call_api($prompt);
    }

    /**
     * Collect performance metrics from pages (batch processing)
     *
     * @return array
     */
    private function collect_page_metrics() {
        $total_posts = wp_count_posts('post');
        $post_count = $total_posts->publish + $total_posts->page;

        // Determine sample size based on post count
        $limit = 20;
        if ($post_count > 100) {
            $limit = min(100, intval($post_count / 10));
        }

        $args = [
            'post_type' => ['post', 'page'],
            'numberposts' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $posts = get_posts($args);
        $perf_data = [];

        foreach ($posts as $post) {
            $url = get_permalink($post->ID);
            $time_start = microtime(true);

            // Use HEAD request (much faster) for initial check
            $response = wp_remote_head($url, [
                'timeout' => 5,
                'sslverify' => apply_filters('claude_ai_scanner_sslverify', true),
            ]);

            $load_time = microtime(true) - $time_start;

            if (!is_wp_error($response)) {
                $size = 0;
                $headers = wp_remote_retrieve_headers($response);
                if (isset($headers['content-length'])) {
                    $size = intval($headers['content-length']) / 1024;
                }

                $perf_data[] = [
                    'title' => $post->post_title,
                    'url' => $url,
                    'load_time' => round($load_time, 3),
                    'size' => round($size, 2),
                ];
            }
        }

        return $perf_data;
    }

    /**
     * Prepare analysis prompt
     *
     * @param array $perf_data Performance metrics.
     * @return string
     */
    private function prepare_prompt($perf_data) {
        if (empty($perf_data)) {
            return 'No pages could be scanned. Ensure your site has published posts or pages.';
        }

        $avg_time = array_sum(array_column($perf_data, 'load_time')) / count($perf_data);
        $avg_size = array_sum(array_column($perf_data, 'size')) / count($perf_data);

        $prompt = <<<PROMPT
Analyze these page performance metrics and provide optimization recommendations:

Pages Analyzed: " . count($perf_data) . "
Average Load Time: {$avg_time}s
Average Page Size: {$avg_size}KB

Performance Data:
PROMPT;

        foreach ($perf_data as $page) {
            $prompt .= "\n- {$page['title']}: {$page['load_time']}s, {$page['size']}KB";
        }

        $prompt .= <<<PROMPT

Provide analysis covering:
1. Average load time and performance tier (Good/Fair/Poor)
2. Page size analysis and optimization opportunities
3. Specific recommendations for slow pages (Quick wins first)
4. Caching strategy (object, page, browser)
5. Asset optimization (CSS, JS, images, lazy loading)
6. Database query optimization
7. Priority list of optimizations with expected impact

Format: Clear sections with actionable code examples.
PROMPT;

        return $prompt;
    }
}
