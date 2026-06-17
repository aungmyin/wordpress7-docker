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
     * Find broken links in content
     *
     * @return array
     */
    private function find_broken_links() {
        $broken_links = [];
        $urls_checked = [];

        $args = [
            'post_type' => ['post', 'page'],
            'numberposts' => 50,
        ];

        $posts = get_posts($args);

        foreach ($posts as $post) {
            $url = get_permalink($post->ID);
            $content = $post->post_content;

            if (preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $matches)) {
                foreach ($matches[1] as $link) {
                    if (in_array($link, $urls_checked)) {
                        continue;
                    }
                    $urls_checked[] = $link;

                    // Skip external links
                    if (strpos($link, 'http') === 0 && strpos($link, home_url()) === false) {
                        continue;
                    }

                    // Make full URL if relative
                    if (strpos($link, 'http') !== 0) {
                        $link = home_url($link);
                    }

                    // Check link
                    $response = wp_remote_head($link, ['sslverify' => false]);
                    $code = wp_remote_retrieve_response_code($response);

                    if ($code == 404 || $code == 410) {
                        $broken_links[] = [
                            'url' => $link,
                            'status' => $code,
                            'source' => $post->post_title,
                            'severity' => 'Critical',
                        ];
                    }

                    if (count($urls_checked) >= 200) {
                        break 2;
                    }
                }
            }
        }

        return $broken_links;
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
