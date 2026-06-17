<?php
/**
 * Settings Template
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

$api_key = get_option('claude_ai_scanner_api_key', '');
$masked_key = $api_key ? substr($api_key, 0, 10) . '...' . substr($api_key, -4) : 'Not set';

// Save rate limit settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claude_rate_limits'])) {
    check_admin_referer('claude_ai_scanner_settings');
    update_option('claude_ai_scanner_limits', [
        'per_hour' => intval($_POST['rate_limit_per_hour'] ?? 10),
        'per_day' => intval($_POST['rate_limit_per_day'] ?? 50),
        'concurrent' => intval($_POST['rate_limit_concurrent'] ?? 3),
    ]);
    echo '<div class="notice notice-success"><p>Rate limits updated successfully!</p></div>';
}

$limits = get_option('claude_ai_scanner_limits', [
    'per_hour' => 10,
    'per_day' => 50,
    'concurrent' => 3,
]);
?>

<div class="wrap">
    <h1>Claude AI Scanner Settings</h1>
    <div style="max-width: 600px; margin-top: 20px;">
        <h2>Claude API Configuration</h2>
        <p>Get your Claude API key from: <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></p>

        <form method="post">
            <?php wp_nonce_field('claude_ai_scanner_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="claude_api_key">API Key</label></th>
                    <td>
                        <input type="password" id="claude_api_key" name="claude_api_key" value="<?php echo esc_attr($api_key); ?>" style="width: 300px;">
                        <p class="description">Your current key: <?php echo esc_html($masked_key); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save API Key'); ?>
        </form>

        <hr style="margin: 30px 0;">

        <h2>Rate Limiting & API Quotas</h2>
        <p>Configure limits to protect your API quota and prevent abuse.</p>

        <form method="post">
            <?php wp_nonce_field('claude_ai_scanner_settings'); ?>
            <input type="hidden" name="claude_rate_limits" value="1">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="rate_limit_per_hour">Scans per Hour (per user)</label></th>
                    <td>
                        <input type="number" id="rate_limit_per_hour" name="rate_limit_per_hour" value="<?php echo esc_attr($limits['per_hour']); ?>" min="1" max="100" style="width: 100px;">
                        <p class="description">Maximum scans allowed per user per hour</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rate_limit_per_day">Scans per Day (per user)</label></th>
                    <td>
                        <input type="number" id="rate_limit_per_day" name="rate_limit_per_day" value="<?php echo esc_attr($limits['per_day']); ?>" min="1" max="500" style="width: 100px;">
                        <p class="description">Maximum scans allowed per user per day</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rate_limit_concurrent">Concurrent Scans</label></th>
                    <td>
                        <input type="number" id="rate_limit_concurrent" name="rate_limit_concurrent" value="<?php echo esc_attr($limits['concurrent']); ?>" min="1" max="10" style="width: 100px;">
                        <p class="description">Maximum number of simultaneous scans per user</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Rate Limits'); ?>
        </form>

        <hr style="margin: 30px 0;">

        <h2>How to Use</h2>
        <ol>
            <li>Get your API key from <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a></li>
            <li>Enter your API key above and save</li>
            <li>Go to "Advanced Scan" tab to analyze your site</li>
            <li>Choose scan type:
                <ul>
                    <li><strong>⚡ Performance</strong> — Load times, optimization tips</li>
                    <li><strong>🔍 404 Detection</strong> — Find broken links</li>
                    <li><strong>🔄 Redirects</strong> — Analyze redirections</li>
                    <li><strong>📊 SEO</strong> — Meta tags, content, structure</li>
                </ul>
            </li>
            <li>Download reports as CSV or Excel</li>
        </ol>

        <hr style="margin: 30px 0;">

        <h2>Security & Privacy</h2>
        <ul>
            <li>✅ Your API key is stored securely in WordPress</li>
            <li>✅ Plugin only runs in admin area</li>
            <li>✅ All requests require admin authentication</li>
            <li>✅ Rotate your key in Anthropic Console anytime</li>
            <li>⚠️ Never share your API key publicly</li>
        </ul>

        <hr style="margin: 30px 0;">

        <h2>Cost Considerations</h2>
        <p>Each scan uses the Claude API and will incur costs based on your Anthropic account plan.</p>
        <p><a href="https://console.anthropic.com/" target="_blank">Check your current plan →</a></p>
    </div>
</div>
<?php
