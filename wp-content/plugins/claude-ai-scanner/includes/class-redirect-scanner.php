<?php
/**
 * Redirect Scanner Class
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_Redirect_Scanner extends Claude_AI_Scanner {
    protected $type = 'redirects';

    /**
     * Run redirect scan
     *
     * @return string|WP_Error
     */
    public function scan() {
        $redirect_data = $this->check_redirects();

        $prompt = $this->prepare_prompt($redirect_data);

        return $this->call_api($prompt);
    }

    /**
     * Check site redirects
     *
     * @return array
     */
    private function check_redirects() {
        $data = [
            'htaccess_rules' => 0,
            'direct_redirects' => 0,
            'active_redirects' => [],
        ];

        // Check .htaccess
        $htaccess_file = ABSPATH . '.htaccess';
        if (file_exists($htaccess_file) && is_readable($htaccess_file)) {
            $content = file_get_contents($htaccess_file);

            if (preg_match_all('/RewriteRule\s+(.+)/i', $content, $matches)) {
                $data['htaccess_rules'] = count($matches[0]);
            }

            if (preg_match_all('/Redirect\s+301\s+(.+)\s+(.+)/i', $content, $matches)) {
                $data['direct_redirects'] = count($matches[0]);
            }
        }

        // Test common URLs
        $test_urls = [
            home_url('/old-page') => 'Testing old-page redirect',
            home_url('/index.php') => 'Testing index.php behavior',
        ];

        foreach ($test_urls as $url => $desc) {
            $response = wp_remote_head($url, [
                'sslverify' => false,
                'follow_redirects' => false,
            ]);

            $code = wp_remote_retrieve_response_code($response);
            if ($code >= 300 && $code < 400) {
                $location = wp_remote_retrieve_header($response, 'location');
                $data['active_redirects'][] = [
                    'from' => $url,
                    'status' => $code,
                    'to' => $location ?? 'Unknown',
                ];
            }
        }

        return $data;
    }

    /**
     * Prepare analysis prompt
     *
     * @param array $redirect_data Redirect data.
     * @return string
     */
    private function prepare_prompt($redirect_data) {
        $prompt = <<<PROMPT
Analyze the redirect configuration and provide recommendations:

Configuration Found:
- .htaccess Rules: " . $redirect_data['htaccess_rules'] . "
- Direct Redirects: " . $redirect_data['direct_redirects'] . "
- Active Redirects Detected: " . count($redirect_data['active_redirects']) . "

Active Redirects:
PROMPT;

        foreach ($redirect_data['active_redirects'] as $redir) {
            $prompt .= "\n- Status {$redir['status']}: {$redir['from']} → {$redir['to']}";
        }

        $prompt .= <<<PROMPT

Provide analysis on:
1. Redirect chain health (unnecessary intermediate redirects?)
2. Performance impact (each redirect adds ~100ms)
3. SEO impact (301 vs 302 usage assessment)
4. Opportunities to consolidate redirects
5. Best practices for WordPress redirects
6. Code examples for any recommendations
7. Monitoring strategy

Format: Clear sections with specific recommendations.
PROMPT;

        return $prompt;
    }

    /**
     * Get redirect data for export
     *
     * @return array
     */
    public function get_export_data() {
        return $this->check_redirects();
    }
}
