<?php
/**
 * Claude API Helper Class
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_API {
    /**
     * API key
     *
     * @var string
     */
    private $api_key;

    /**
     * API endpoint
     *
     * @var string
     */
    private $endpoint = 'https://api.anthropic.com/v1/messages';

    /**
     * Model
     *
     * @var string
     */
    private $model = 'claude-3-5-sonnet-20241022';

    /**
     * Max tokens
     *
     * @var int
     */
    private $max_tokens = 2000;

    /**
     * Constructor
     *
     * @param string $api_key Claude API key.
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Call Claude API
     *
     * @param string $prompt The prompt to send.
     * @return string|WP_Error
     */
    public function call($prompt) {
        $body = [
            'model' => $this->model,
            'max_tokens' => $this->max_tokens,
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

        $response = wp_remote_post($this->endpoint, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message']);
        }

        if (isset($body['content'][0]['text'])) {
            return $body['content'][0]['text'];
        }

        return new WP_Error('unexpected_response', 'Unexpected response from Claude API');
    }

    /**
     * Test API connection
     *
     * @return bool
     */
    public function test_connection() {
        $result = $this->call('Hello, what is your name?');
        return !is_wp_error($result);
    }

    /**
     * Set model
     *
     * @param string $model Model name.
     * @return self
     */
    public function set_model($model) {
        $this->model = $model;
        return $this;
    }

    /**
     * Set max tokens
     *
     * @param int $tokens Max tokens.
     * @return self
     */
    public function set_max_tokens($tokens) {
        $this->max_tokens = $tokens;
        return $this;
    }
}
