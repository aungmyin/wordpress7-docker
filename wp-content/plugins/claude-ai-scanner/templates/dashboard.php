<?php
/**
 * Dashboard Template - Show scan results by category
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

$results = Claude_AI_Storage::get_all_results();
$latest = Claude_AI_Storage::get_latest_result();
$stats = Claude_AI_Storage::get_dashboard_stats();
?>

<div class="wrap">
    <h1>Claude AI Scanner Dashboard</h1>

    <?php
    // Show rate limit status
    $rate_stats = Claude_AI_Rate_Limiter::get_stats();
    ?>
    <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid <?php echo $rate_stats['api_quota_status'] === 'Critical' ? '#dc3545' : ($rate_stats['api_quota_status'] === 'Warning' ? '#ffc107' : '#28a745'); ?>; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <div>
                <div style="font-size: 11px; color: #666; text-transform: uppercase;">Scans This Hour</div>
                <div style="font-size: 18px; font-weight: bold;"><?php echo esc_html($rate_stats['scans_this_hour']); ?></div>
            </div>
            <div>
                <div style="font-size: 11px; color: #666; text-transform: uppercase;">Scans Today</div>
                <div style="font-size: 18px; font-weight: bold;"><?php echo esc_html($rate_stats['scans_today']); ?></div>
            </div>
            <div>
                <div style="font-size: 11px; color: #666; text-transform: uppercase;">API Quota Status</div>
                <div style="font-size: 18px; font-weight: bold; color: <?php echo $rate_stats['api_quota_status'] === 'Critical' ? '#dc3545' : ($rate_stats['api_quota_status'] === 'Warning' ? '#ffc107' : '#28a745'); ?>">
                    <?php echo esc_html($rate_stats['api_quota_status']); ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($results)): ?>
        <div style="background: white; padding: 40px; text-align: center; border-radius: 8px;">
            <p style="font-size: 16px; color: #666;">No scans yet. Start a scan to see results here.</p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=claude-ai-scanner')); ?>" class="button button-primary">Go to Scanner</a>
        </div>
    <?php else: ?>

        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #0073aa; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 12px; color: #666; text-transform: uppercase;">Total Scans</div>
                <div style="font-size: 32px; font-weight: bold; color: #0073aa;"><?php echo esc_html($stats['total_scans']); ?></div>
            </div>

            <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 12px; color: #666; text-transform: uppercase;">Last Scan</div>
                <div style="font-size: 14px; font-weight: bold; color: #28a745;">
                    <?php
                    if ($latest) {
                        echo esc_html(human_time_diff(strtotime($latest['date']), current_time('U')) . ' ago');
                    } else {
                        echo 'Never';
                    }
                    ?>
                </div>
            </div>

            <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 12px; color: #666; text-transform: uppercase;">Scan Types</div>
                <div style="font-size: 28px; font-weight: bold; color: #17a2b8;"><?php echo esc_html(count($stats['scan_types'])); ?></div>
            </div>
        </div>

        <!-- Latest Scan Results -->
        <?php if ($latest): ?>
            <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2>Latest Scan Results</h2>
                <p style="color: #666;">
                    <strong><?php echo esc_html(ucfirst(str_replace('-', ' ', $latest['type']))); ?> Scan</strong>
                    <span style="color: #999; font-size: 13px;">
                        • <?php echo esc_html(human_time_diff(strtotime($latest['date']), current_time('U')) . ' ago'); ?>
                    </span>
                </p>

                <!-- Category Cards -->
                <?php if (!empty($latest['categories'])): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 20px;">
                        <?php foreach ($latest['categories'] as $category => $count): ?>
                            <div style="background: #f9f9f9; padding: 15px; border-radius: 6px; text-align: center; border: 1px solid #e5e7eb; cursor: pointer; transition: all 0.3s ease;"
                                 onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'; this.style.transform='translateY(-2px)';"
                                 onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                                 onclick="viewCategory('<?php echo esc_attr($latest['type']); ?>', '<?php echo esc_attr($category); ?>')">
                                <div style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo esc_html($count); ?></div>
                                <div style="font-size: 12px; color: #666; margin-top: 5px;"><?php echo esc_html($category); ?></div>
                                <div style="font-size: 11px; color: #999; margin-top: 8px;">Click to view</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Result Preview -->
                <div style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 6px; border-left: 4px solid #0073aa;">
                    <h3 style="margin-top: 0;">Analysis Summary</h3>
                    <div style="white-space: pre-wrap; font-family: monospace; font-size: 12px; line-height: 1.6; color: #333; max-height: 300px; overflow-y: auto;">
                        <?php echo esc_html(substr($latest['result'], 0, 1000)); ?>...
                    </div>
                    <p style="margin-top: 15px; margin-bottom: 0;">
                        <a href="#" onclick="viewFullResult('<?php echo esc_attr($latest['type']); ?>'); return false;" class="button button-small">View Full Report</a>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Reports Section -->
        <?php
        $reports = Claude_AI_Report_Generator::get_reports();
        if (!empty($reports)):
        ?>
        <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>📄 Reports (Markdown for Claude Code)</h2>
            <p style="color: #666; font-size: 13px;">Download reports to open in Claude Code for AI-assisted fixes.</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                <?php foreach (array_slice($reports, 0, 6) as $report): ?>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 6px; border: 1px solid #e5e7eb;">
                    <div style="font-size: 13px; color: #666; margin-bottom: 10px;">
                        <strong><?php echo esc_html(basename($report['name'], '.md')); ?></strong>
                    </div>
                    <div style="font-size: 12px; color: #999; margin-bottom: 10px;">
                        <?php echo esc_html(date_i18n('M d, Y g:i A', $report['date'])); ?><br>
                        <?php echo esc_html(size_format($report['size'])); ?>
                    </div>
                    <a href="<?php echo esc_url($report['url']); ?>" class="button button-small" download>📥 Download</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Scan History -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>Scan History</h2>
            <table class="wp-list-table widefat striped" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Scan Type</th>
                        <th>Items Scanned</th>
                        <th>Categories</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($results, 0, 10) as $result): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html(date_i18n('M d, Y', strtotime($result['date']))); ?></strong><br>
                                <small style="color: #666;"><?php echo esc_html(date_i18n('g:i A', strtotime($result['date']))); ?></small>
                            </td>
                            <td><?php echo esc_html(ucfirst(str_replace('-', ' ', $result['type']))); ?></td>
                            <td><?php echo esc_html($result['summary']['items_scanned'] ?? '-'); ?></td>
                            <td>
                                <?php
                                $categories = array_keys($result['categories'] ?? []);
                                echo esc_html(implode(', ', array_slice($categories, 0, 2)));
                                if (count($categories) > 2) {
                                    echo ' +' . (count($categories) - 2);
                                }
                                ?>
                            </td>
                            <td>
                                <a href="#" onclick="viewFullResult('<?php echo esc_attr($result['type']); ?>', '<?php echo esc_attr(htmlspecialchars(json_encode($result), ENT_QUOTES, 'UTF-8')); ?>'); return false;" class="button button-small">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

</div>

<script>
function viewCategory(scanType, category) {
    alert('Category: ' + category + '\nScan Type: ' + scanType + '\n\nFull details coming soon!');
}

function viewFullResult(scanType, resultData) {
    alert('Full Report for: ' + scanType + '\n\nDetailed view coming soon!');
}
</script>

<style>
.wrap h1 {
    margin-bottom: 30px;
}

.wp-list-table {
    background: white;
}
</style>
<?php
