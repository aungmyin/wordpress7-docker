<?php
/**
 * Link Scanner Class - Detects broken links and 404s
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_Link_Scanner extends Claude_AI_Scanner {
    protected $type = '404';

    /**
     * Run link scan
     *
     * @return string|WP_Error
     */
    public function scan() {
        $broken_links = $this->find_broken_links();

        $prompt = $this->prepare_prompt($broken_links);

        return $this->call_api($prompt);
    }

    /**
     * Find broken links in content (optimized batch processing)
     *
     * @return array
     */
    private function find_broken_links() {
        $broken_links = [];
        $urls_checked = [];
        $all_links = [];

        // Determine post sample size
        $total_posts = wp_count_posts('post');
        $post_count = $total_posts->publish + $total_posts->page;
        $sample_size = min(100, max(50, intval($post_count / 5)));

        $args = [
            'post_type' => ['post', 'page'],
            'numberposts' => $sample_size,
        ];

        $posts = get_posts($args);

        // Extract all links first
        foreach ($posts as $post) {
            $content = $post->post_content;

            if (preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $matches)) {
                foreach ($matches[1] as $link) {
                    $normalized_link = $this->normalize_url($link);

                    if ($normalized_link && !isset($urls_checked[$normalized_link])) {
                        $all_links[$normalized_link] = $post->post_title;
                    }
                }
            }

            if (count($all_links) >= 200) {
                break;
            }
        }

        // Check links in batches
        $batch_size = 20;
        $link_chunks = array_chunk($all_links, $batch_size, true);

        foreach ($link_chunks as $links) {
            foreach ($links as $link => $source) {
                $urls_checked[$link] = true;

                $response = wp_remote_head($link, [
                    'timeout' => 3,
                    'sslverify' => false,
                ]);

                $code = wp_remote_retrieve_response_code($response);

                if ($code == 404 || $code == 410) {
                    $broken_links[] = [
                        'url' => $link,
                        'status' => $code,
                        'source' => $source,
                        'severity' => 'Critical',
                    ];
                }
            }
        }

        return $broken_links;
    }

    /**
     * Normalize URL for processing
     *
     * @param string $url URL to normalize.
     * @return string|false Normalized URL or false if external/invalid.
     */
    private function normalize_url($url) {
        // Skip external links
        if (strpos($url, 'http') === 0 && strpos($url, home_url()) === false) {
            return false;
        }

        // Make full URL if relative
        if (strpos($url, 'http') !== 0) {
            $url = home_url($url);
        }

        return $url;
    }

    /**
     * Prepare analysis prompt
     *
     * @param array $broken_links Broken links data.
     * @return string
     */
    private function prepare_prompt($broken_links) {
        $prompt = <<<PROMPT
Analyze these broken links and provide a remediation plan:

Total Links Checked: " . count($broken_links) . "
Broken Links Found: " . count($broken_links) . "

Critical Broken Links:
PROMPT;

        foreach (array_slice($broken_links, 0, 20) as $link) {
            $prompt .= "\n- {$link['url']} (Status: {$link['status']}, Found in: {$link['source']})";
        }

        $prompt .= <<<PROMPT

Provide:
1. Summary of broken links by category
2. High-priority items to fix immediately
3. Recommended fixes (update, redirect, or restore)
4. Prevention strategy for future broken links
5. Monitoring recommendations

Be specific with exact URL changes.
PROMPT;

        return $prompt;
    }

    /**
     * Get broken links data for export
     *
     * @return array
     */
    public function get_export_data() {
        return $this->find_broken_links();
    }
}
