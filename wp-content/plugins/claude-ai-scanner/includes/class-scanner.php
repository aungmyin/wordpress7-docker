<?php
/**
 * Base Scanner Class
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class Claude_AI_Scanner {
    /**
     * Scanner type identifier
     *
     * @var string
     */
    protected $type;

    /**
     * Claude API key
     *
     * @var string
     */
    protected $api_key;

    /**
     * Constructor
     *
     * @param string $api_key Claude API key.
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Run the scan
     *
     * @return string|WP_Error Analysis result or error.
     */
    abstract public function scan();

    /**
     * Get scanner type
     *
     * @return string
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * Call Claude API with prompt
     *
     * @param string $prompt The analysis prompt.
     * @return string|WP_Error API response or error.
     */
    protected function call_api($prompt) {
        $url = 'https://api.anthropic.com/v1/messages';

        $body = [
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 2000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        $args = [
            'body' => wp_json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'timeout' => 30,
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($body)) {
            return new WP_Error('invalid_json', 'Invalid JSON response from Claude API');
        }

        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message']);
        }

        if (isset($body['content'][0]['text'])) {
            return $body['content'][0]['text'];
        }

        return new WP_Error('unexpected_response', 'Unexpected response from Claude API');
    }
}
