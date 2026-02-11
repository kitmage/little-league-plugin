<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLLM_Import {
    private static function normalize_csv_header($header) {
        $header = strtolower(trim((string) $header));
        if (preg_match('/^start_date\s*\(.*\)$/', $header)) {
            return 'start_date';
        }
        if (preg_match('/^start_time\s*\(.*\)$/', $header)) {
            return 'start_time';
        }
        return $header;
    }

    public static function get_import_types() {
        return array(
            'full' => __('Full Schedule Import', 'lllm'),
            'score' => __('Score Update Import', 'lllm'),
        );
    }

    public static function parse_csv($file_path) {
        $rows = array();
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return new WP_Error('lllm_csv_open_failed', __('Unable to open CSV file.', 'lllm'));
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return new WP_Error('lllm_csv_headers_missing', __('CSV headers are required.', 'lllm'));
        }

        $headers = array_map(array(__CLASS__, 'normalize_csv_header'), $headers);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === 1 && $data[0] === null) {
                continue;
            }
            $row = array();
            foreach ($headers as $index => $header) {
                $row[$header] = isset($data[$index]) ? trim($data[$index]) : '';
            }
            $rows[] = $row;
        }

        fclose($handle);

        return array(
            'headers' => $headers,
            'rows' => $rows,
        );
    }

    public static function get_upload_dir() {
        $upload = wp_upload_dir();
        $dir = trailingslashit($upload['basedir']) . 'lllm';
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        return $dir;
    }

    public static function save_error_report($errors) {
        $dir = self::get_upload_dir();
        $filename = 'import-errors-' . gmdate('Ymd-His') . '-' . wp_generate_password(6, false) . '.csv';
        $path = trailingslashit($dir) . $filename;
        $handle = fopen($path, 'w');
        if (!$handle) {
            return '';
        }

        fputcsv($handle, array('row_number', 'error'));
        foreach ($errors as $error) {
            fputcsv($handle, array($error['row'], $error['message']));
        }
        fclose($handle);

        return $path;
    }

    public static function generate_game_uid() {
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $uid = '';
        $bytes = random_bytes(8);
        $value = 0;
        $bits = 0;
        foreach (str_split($bytes) as $byte) {
            $value = ($value << 8) | ord($byte);
            $bits += 8;
            while ($bits >= 5 && strlen($uid) < 12) {
                $index = ($value >> ($bits - 5)) & 31;
                $uid .= $alphabet[$index];
                $bits -= 5;
            }
        }

        while (strlen($uid) < 12) {
            $uid .= $alphabet[random_int(0, 31)];
        }

        return $uid;
    }

    public static function unique_game_uid() {
        global $wpdb;
        $table = $wpdb->prefix . 'lllm_games';
        do {
            $uid = self::generate_game_uid();
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE game_uid = %s", $uid));
        } while ($exists);

        return $uid;
    }

    public static function get_season_timezone($season_id) {
        global $wpdb;
        $timezone = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT timezone FROM ' . $wpdb->prefix . 'lllm_seasons WHERE id = %d',
                $season_id
            )
        );

        if (!$timezone) {
            $timezone = wp_timezone_string();
        }

        return $timezone;
    }

    public static function parse_datetime_to_utc($datetime, $timezone) {
        try {
            $dt = new DateTime($datetime, new DateTimeZone($timezone));
        } catch (Exception $e) {
            return false;
        }

        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
    }
}
