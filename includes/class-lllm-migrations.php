<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLLM_Migrations {
    public static function run() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $seasons_table = $wpdb->prefix . 'lllm_seasons';
        $divisions_table = $wpdb->prefix . 'lllm_divisions';
        $team_masters_table = $wpdb->prefix . 'lllm_team_masters';
        $team_instances_table = $wpdb->prefix . 'lllm_team_instances';
        $games_table = $wpdb->prefix . 'lllm_games';
        $import_logs_table = $wpdb->prefix . 'lllm_import_logs';

        $seasons_sql = "CREATE TABLE {$seasons_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(140) NOT NULL,
            timezone VARCHAR(64) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY is_active (is_active)
        ) {$charset_collate};";

        $divisions_sql = "CREATE TABLE {$divisions_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            name VARCHAR(80) NOT NULL,
            slug VARCHAR(160) NOT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY season_id (season_id),
            UNIQUE KEY season_name (season_id, name)
        ) {$charset_collate};";

        $team_masters_sql = "CREATE TABLE {$team_masters_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(140) NOT NULL,
            team_code VARCHAR(60) NOT NULL,
            logo_attachment_id BIGINT(20) UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY team_code (team_code),
            UNIQUE KEY slug (slug)
        ) {$charset_collate};";

        $team_instances_sql = "CREATE TABLE {$team_instances_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            division_id BIGINT(20) UNSIGNED NOT NULL,
            team_master_id BIGINT(20) UNSIGNED NOT NULL,
            display_name VARCHAR(120) NULL,
            display_name VARCHAR(120) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY  (id),
            KEY division_id (division_id),
            KEY team_master_id (team_master_id),
            UNIQUE KEY division_team (division_id, team_master_id)
        ) {$charset_collate};";

        $games_sql = "CREATE TABLE {$games_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            game_uid CHAR(12) NOT NULL,
            division_id BIGINT(20) UNSIGNED NOT NULL,
            home_team_instance_id BIGINT(20) UNSIGNED NOT NULL,
            away_team_instance_id BIGINT(20) UNSIGNED NOT NULL,
            location VARCHAR(160) NOT NULL,
            start_datetime_utc DATETIME NOT NULL,
            home_score INT NULL,
            away_score INT NULL,
            status VARCHAR(20) NOT NULL,
            notes VARCHAR(255) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY game_uid (game_uid),
            KEY division_datetime (division_id, start_datetime_utc),
            KEY home_team_instance_id (home_team_instance_id),
            KEY away_team_instance_id (away_team_instance_id),
            UNIQUE KEY game_unique (division_id, start_datetime_utc, home_team_instance_id, away_team_instance_id)
        ) {$charset_collate};";

        $import_logs_sql = "CREATE TABLE {$import_logs_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            division_id BIGINT(20) UNSIGNED NOT NULL,
            import_type VARCHAR(40) NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            total_rows INT NOT NULL DEFAULT 0,
            total_created INT NOT NULL DEFAULT 0,
            total_updated INT NOT NULL DEFAULT 0,
            total_unchanged INT NOT NULL DEFAULT 0,
            total_errors INT NOT NULL DEFAULT 0,
            error_report_path VARCHAR(255) NULL,
            created_at DATETIME NULL,
            PRIMARY KEY  (id),
            KEY season_division (season_id, division_id),
            KEY user_id (user_id)
        ) {$charset_collate};";

        dbDelta($seasons_sql);
        dbDelta($divisions_sql);
        dbDelta($team_masters_sql);
        dbDelta($team_instances_sql);
        dbDelta($games_sql);
        dbDelta($import_logs_sql);
    }
}
