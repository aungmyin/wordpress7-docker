<?php
/**
 * SEO Scanner Class
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_SEO_Scanner extends Claude_AI_Scanner {
    protected $type = 'seo';

    /**
     * Run SEO scan
     *
     * @return string|WP_Error
     */
    public function scan() {
        $seo_data = $this->collect_seo_metrics();

        $prompt = $this->prepare_prompt($seo_data);

        return $this->call_api($prompt);
    }

    /**
     * Collect SEO metrics from pages (batch processing)
     *
     * @return array
     */
    private function collect_seo_metrics() {
        // Determine sample size based on post count
        $total_posts = wp_count_posts('post');
        $post_count = $total_posts->publish + $total_posts->page;
        $sample_size = min(100, max(30, intval($post_count / 5)));

        $args = [
            'post_type' => ['post', 'page'],
            'numberposts' => $sample_size,
        ];

        $posts = get_posts($args);
        $seo_data = [];

        foreach ($posts as $post) {
            $meta_desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true) ?: '';
            $focus_kw = get_post_meta($post->ID, '_yoast_wpseo_focuskw', true) ?: '';
            $title_len = strlen($post->post_title);
            $content_len = strlen(strip_tags($post->post_content));
            $has_meta = !empty($meta_desc);
            $has_kw = !empty($focus_kw);

            $seo_data[] = [
                'title' => substr($post->post_title, 0, 50),
                'url' => get_permalink($post->ID),
                'meta_desc' => $has_meta ? 'Yes' : 'Missing',
                'focus_kw' => $has_kw ? 'Yes' : 'Not set',
                'title_len' => $title_len,
                'content_len' => $content_len,
                'quality' => $this->assess_seo_quality($title_len, $content_len, $has_meta, $has_kw),
            ];
        }

        return $seo_data;
    }

    /**
     * Assess SEO quality score
     *
     * @param int  $title_len Title length.
     * @param int  $content_len Content length.
     * @param bool $has_meta Has meta description.
     * @param bool $has_kw Has focus keyword.
     * @return string
     */
    private function assess_seo_quality($title_len, $content_len, $has_meta, $has_kw) {
        $score = 0;

        if ($title_len >= 30 && $title_len <= 60) {
            $score += 25;
        }
        if ($content_len >= 300) {
            $score += 25;
        }
        if ($has_meta) {
            $score += 25;
        }
        if ($has_kw) {
            $score += 25;
        }

        if ($score >= 75) {
            return 'Good';
        } elseif ($score >= 50) {
            return 'Fair';
        } else {
            return 'Poor';
        }
    }

    /**
     * Prepare analysis prompt
     *
     * @param array $seo_data SEO metrics.
     * @return string
     */
    private function prepare_prompt($seo_data) {
        if (empty($seo_data)) {
            return 'No pages could be analyzed. Ensure your site has published posts or pages.';
        }

        $good_count = array_sum(array_map(fn($d) => ($d['quality'] === 'Good') ? 1 : 0, $seo_data));
        $fair_count = array_sum(array_map(fn($d) => ($d['quality'] === 'Fair') ? 1 : 0, $seo_data));
        $poor_count = array_sum(array_map(fn($d) => ($d['quality'] === 'Poor') ? 1 : 0, $seo_data));

        $prompt = <<<PROMPT
Analyze SEO across these pages and provide optimization recommendations:

Pages Analyzed: " . count($seo_data) . "
SEO Quality: Good={$good_count}, Fair={$fair_count}, Poor={$poor_count}

Page-by-Page Data:
PROMPT;

        foreach ($seo_data as $page) {
            $prompt .= "\n- {$page['title']}: {$page['quality']} (Meta={$page['meta_desc']}, Keywords={$page['focus_kw']}, Title={$page['title_len']}ch, Content={$page['content_len']}ch)";
        }

        $prompt .= <<<PROMPT

Provide analysis covering:
1. Overall SEO health score (A-F grade)
2. Meta description completeness and quality
3. Title tag optimization (length, keywords)
4. Content depth analysis
5. Keyword strategy recommendations
6. Priority pages to optimize first
7. Meta tag templates
8. Structured data opportunities

Give specific, actionable recommendations.
PROMPT;

        return $prompt;
    }

    /**
     * Get SEO data for export
     *
     * @return array
     */
    public function get_export_data() {
        return $this->collect_seo_metrics();
    }
}
