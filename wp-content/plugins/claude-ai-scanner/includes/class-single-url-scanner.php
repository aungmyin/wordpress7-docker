<?php
/**
 * Single URL Scanner Class
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_Single_URL_Scanner extends Claude_AI_Scanner {
    protected $type = 'single-url';

    /**
     * URL to scan
     *
     * @var string
     */
    private $url;

    /**
     * Constructor
     *
     * @param string $api_key Claude API key.
     * @param string $url URL to scan.
     */
    public function __construct($api_key, $url = '') {
        parent::__construct($api_key);
        $this->url = sanitize_url($url);
    }

    /**
     * Run single URL scan
     *
     * @return string|WP_Error
     */
    public function scan() {
        if (empty($this->url)) {
            return new WP_Error('no_url', 'No URL provided');
        }

        // Verify URL is valid and part of this site
        if (strpos($this->url, home_url()) !== 0) {
            return new WP_Error('invalid_url', 'URL must be from this site');
        }

        $url_data = $this->analyze_url();

        if (is_wp_error($url_data)) {
            return $url_data;
        }

        $prompt = $this->prepare_prompt($url_data);

        return $this->call_api($prompt);
    }

    /**
     * Analyze single URL
     *
     * @return array|WP_Error
     */
    private function analyze_url() {
        $data = [
            'url' => $this->url,
            'title' => 'Loading...',
            'performance' => [],
            'seo' => [],
            'links' => [],
            'errors' => [],
        ];

        // Fetch page
        $time_start = microtime(true);
        $response = wp_remote_get($this->url, [
            'timeout' => 10,
            'sslverify' => apply_filters('claude_ai_scanner_sslverify', true),
        ]);
        $load_time = microtime(true) - $time_start;

        if (is_wp_error($response)) {
            return new WP_Error('fetch_error', 'Could not fetch URL: ' . $response->get_error_message());
        }

        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code !== 200) {
            return new WP_Error('http_error', 'URL returned HTTP ' . $http_code);
        }

        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);

        // Performance data
        $data['performance'] = [
            'load_time' => round($load_time, 3),
            'size' => round(strlen($body) / 1024, 2),
            'http_code' => $http_code,
            'cache_control' => $headers['cache-control'] ?? 'Not set',
            'gzip' => stripos($headers['content-encoding'] ?? '', 'gzip') !== false ? 'Yes' : 'No',
        ];

        // Parse HTML
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8'));
        libxml_use_internal_errors(false);

        // Extract title
        $title_nodes = $dom->getElementsByTagName('title');
        if ($title_nodes->length > 0) {
            $data['title'] = $title_nodes->item(0)->nodeValue;
        }

        // SEO Analysis
        $data['seo'] = $this->analyze_seo($dom);

        // Link analysis
        $data['links'] = $this->analyze_links($dom);

        return $data;
    }

    /**
     * Analyze SEO on page
     *
     * @param DOMDocument $dom DOM document.
     * @return array
     */
    private function analyze_seo($dom) {
        $seo = [];

        // Meta tags
        $meta_nodes = $dom->getElementsByTagName('meta');
        foreach ($meta_nodes as $meta) {
            $name = $meta->getAttribute('name');
            $content = $meta->getAttribute('content');

            if (strtolower($name) === 'description') {
                $seo['meta_description'] = [
                    'value' => substr($content, 0, 50) . '...',
                    'length' => strlen($content),
                    'status' => strlen($content) >= 50 && strlen($content) <= 160 ? 'Good' : 'Needs improvement',
                ];
            }
            if (strtolower($name) === 'keywords') {
                $seo['keywords'] = $content ?: 'Not set';
            }
        }

        // Title tag
        $title_nodes = $dom->getElementsByTagName('title');
        if ($title_nodes->length > 0) {
            $title = $title_nodes->item(0)->nodeValue;
            $seo['title'] = [
                'value' => $title,
                'length' => strlen($title),
                'status' => strlen($title) >= 30 && strlen($title) <= 60 ? 'Good' : 'Needs improvement',
            ];
        }

        // H1 tags
        $h1_nodes = $dom->getElementsByTagName('h1');
        $seo['h1_count'] = $h1_nodes->length;
        if ($h1_nodes->length > 0) {
            $seo['h1'] = $h1_nodes->item(0)->nodeValue;
        }

        // Canonical link
        $links = $dom->getElementsByTagName('link');
        foreach ($links as $link) {
            if ($link->getAttribute('rel') === 'canonical') {
                $seo['canonical'] = $link->getAttribute('href');
            }
        }

        // Open Graph tags
        foreach ($meta_nodes as $meta) {
            $property = $meta->getAttribute('property');
            if (stripos($property, 'og:') === 0) {
                $seo['open_graph'] = 'Yes';
                break;
            }
        }

        // Structured data (JSON-LD)
        $scripts = $dom->getElementsByTagName('script');
        foreach ($scripts as $script) {
            if ($script->getAttribute('type') === 'application/ld+json') {
                $seo['structured_data'] = 'Yes';
                break;
            }
        }

        return $seo;
    }

    /**
     * Analyze links on page
     *
     * @param DOMDocument $dom DOM document.
     * @return array
     */
    private function analyze_links($dom) {
        $links = [
            'internal' => [],
            'external' => [],
            'broken' => [],
        ];

        $a_tags = $dom->getElementsByTagName('a');
        foreach ($a_tags as $a) {
            $href = $a->getAttribute('href');
            if (empty($href) || $href === '#') {
                continue;
            }

            // Check if internal or external
            if (strpos($href, 'http') !== 0) {
                $href = home_url($href);
            }

            if (strpos($href, home_url()) === 0) {
                $links['internal'][] = $href;
            } else {
                $links['external'][] = $href;
            }
        }

        // Check broken links
        foreach (array_slice($links['internal'], 0, 10) as $link) {
            $response = wp_remote_head($link, ['sslverify' => false]);
            $code = wp_remote_retrieve_response_code($response);

            if ($code >= 400) {
                $links['broken'][] = [
                    'url' => $link,
                    'status' => $code,
                ];
            }
        }

        return [
            'total_internal' => count(array_unique($links['internal'])),
            'total_external' => count(array_unique($links['external'])),
            'broken_links' => count($links['broken']),
        ];
    }

    /**
     * Prepare analysis prompt
     *
     * @param array $url_data URL analysis data.
     * @return string
     */
    private function prepare_prompt($url_data) {
        $prompt = <<<PROMPT
Analyze this specific webpage and provide detailed optimization recommendations:

URL: {$url_data['url']}
Title: {$url_data['title']}

PERFORMANCE METRICS:
- Load Time: {$url_data['performance']['load_time']}s
- Page Size: {$url_data['performance']['size']}KB
- HTTP Code: {$url_data['performance']['http_code']}
- Cache Control: {$url_data['performance']['cache_control']}
- Gzip: {$url_data['performance']['gzip']}

SEO ANALYSIS:
PROMPT;

        if (!empty($url_data['seo'])) {
            foreach ($url_data['seo'] as $key => $value) {
                if (is_array($value)) {
                    $prompt .= "\n- " . ucfirst(str_replace('_', ' ', $key)) . ": " . json_encode($value);
                } else {
                    $prompt .= "\n- " . ucfirst(str_replace('_', ' ', $key)) . ": " . $value;
                }
            }
        }

        $prompt .= <<<PROMPT

LINK ANALYSIS:
- Internal Links: {$url_data['links']['total_internal']}
- External Links: {$url_data['links']['total_external']}
- Broken Links Found: {$url_data['links']['broken_links']}

Provide detailed analysis covering:
1. Page load performance assessment (quick wins for speedup)
2. SEO optimization score (out of 100)
3. Meta tags quality and recommendations
4. Title tag optimization suggestions
5. Content structure analysis (H1, headers)
6. Link health assessment
7. Missing SEO elements (canonical, structured data, og tags)
8. Specific action items (code examples if needed)
9. Priority ranking of improvements

Be specific and actionable with exact changes needed.
PROMPT;

        return $prompt;
    }

    /**
     * Get export data for single URL
     *
     * @return array
     */
    public function get_export_data() {
        $data = $this->analyze_url();

        if (is_wp_error($data)) {
            return [];
        }

        return [
            [
                'Category' => 'URL',
                'Metric' => 'Address',
                'Value' => $data['url'],
            ],
            [
                'Category' => 'Page Title',
                'Metric' => 'Title Tag',
                'Value' => $data['title'],
            ],
            [
                'Category' => 'Performance',
                'Metric' => 'Load Time',
                'Value' => $data['performance']['load_time'] . 's',
            ],
            [
                'Category' => 'Performance',
                'Metric' => 'Page Size',
                'Value' => $data['performance']['size'] . 'KB',
            ],
            [
                'Category' => 'Performance',
                'Metric' => 'Gzip Enabled',
                'Value' => $data['performance']['gzip'],
            ],
            [
                'Category' => 'Links',
                'Metric' => 'Internal Links',
                'Value' => $data['links']['total_internal'],
            ],
            [
                'Category' => 'Links',
                'Metric' => 'External Links',
                'Value' => $data['links']['total_external'],
            ],
            [
                'Category' => 'Links',
                'Metric' => 'Broken Links',
                'Value' => $data['links']['broken_links'],
            ],
        ];
    }
}
