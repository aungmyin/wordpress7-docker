<?php
/**
 * Export Class - Generate CSV/Excel exports
 *
 * @package Claude_AI_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_AI_Export {
    /**
     * Generate CSV export
     *
     * @param array  $data Array of data to export.
     * @param string $filename Export filename.
     * @return string CSV content
     */
    public static function to_csv($data, $filename = 'scan-report.csv') {
        if (empty($data)) {
            return '';
        }

        // Get headers from first row
        $headers = array_keys((array) reset($data));

        $csv = fopen('php://temp', 'r+');

        // Write headers
        fputcsv($csv, $headers);

        // Write data rows
        foreach ($data as $row) {
            fputcsv($csv, (array) $row);
        }

        rewind($csv);
        $output = stream_get_contents($csv);
        fclose($csv);

        return $output;
    }

    /**
     * Generate HTML table for export
     *
     * @param array  $data Array of data.
     * @param string $title Report title.
     * @return string HTML table
     */
    public static function to_html($data, $title = 'Scan Report') {
        if (empty($data)) {
            return '';
        }

        $headers = array_keys((array) reset($data));
        $html = '<table border="1">';
        $html .= '<thead><tr>';

        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ((array) $row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Download CSV file
     *
     * @param array  $data Array of data.
     * @param string $filename Export filename.
     * @return void
     */
    public static function download_csv($data, $filename = 'scan-report.csv') {
        $csv = self::to_csv($data, $filename);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csv));

        echo $csv;
        exit;
    }

    /**
     * Download Excel file (using CSV which Excel can read)
     *
     * @param array  $data Array of data.
     * @param string $filename Export filename.
     * @return void
     */
    public static function download_excel($data, $filename = 'scan-report.csv') {
        $csv = self::to_csv($data, $filename);

        // Use application/vnd.ms-excel for Excel compatibility
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . str_replace('.csv', '.xls', $filename) . '"');
        header('Content-Length: ' . strlen($csv));

        echo $csv;
        exit;
    }

    /**
     * Format data for display/export
     *
     * @param mixed  $value Value to format.
     * @param string $type Data type.
     * @return mixed
     */
    public static function format_value($value, $type = 'string') {
        switch ($type) {
            case 'time':
                return round($value, 3) . 's';
            case 'size':
                return round($value, 2) . 'KB';
            case 'percent':
                return round($value, 2) . '%';
            case 'status':
                return (int) $value;
            default:
                return $value;
        }
    }
}
