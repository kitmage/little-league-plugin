<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLLM_Migrations {
    /**
     * Returns whether the games table contains newer competition metadata columns.
     *
     * Some installations can run code newer than their schema when plugin version
     * metadata is stale. This guard lets runtime upgrade checks self-heal.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return bool True when required columns are present.
     */
    public static function has_required_game_columns() {
        global $wpdb;

        $games_table = $wpdb->prefix . 'lllm_games';
        $required_columns = array(
            'competition_type',
            'playoff_round',
            'playoff_slot',
            'source_game_uid_1',
            'source_game_uid_2',
        );

        foreach ($required_columns as $column) {
            $exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$games_table} LIKE %s", $column));
            if (!$exists) {
                return false;
            }
        }

        return true;
    }

    /**
     * Creates or updates plugin database tables using WordPress dbDelta.
     *
     * Tables managed:
     * - seasons
     * - divisions
     * - team masters
     * - team instances
     * - games
     * - import logs
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return void
     */
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
            competition_type VARCHAR(20) NOT NULL DEFAULT 'regular',
            playoff_round VARCHAR(20) NULL,
            playoff_slot VARCHAR(20) NULL,
            source_game_uid_1 CHAR(12) NULL,
            source_game_uid_2 CHAR(12) NULL,
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
            KEY division_competition_playoff (division_id, competition_type, playoff_round, playoff_slot),
            KEY source_game_uid_1 (source_game_uid_1),
            KEY source_game_uid_2 (source_game_uid_2),
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

        // Apply schema changes idempotently so upgrades can safely run on every version bump.
        dbDelta($seasons_sql);
        dbDelta($divisions_sql);
        dbDelta($team_masters_sql);
        dbDelta($team_instances_sql);
        dbDelta($games_sql);
        dbDelta($import_logs_sql);
        self::normalize_legacy_playoff_competition_types($games_table);
    }

    /**
     * One-time normalization for legacy schemas that encoded playoff rows only
     * through bracket metadata columns.
     *
     * @param string $games_table Fully qualified games table name.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return void
     */
    private static function normalize_legacy_playoff_competition_types($games_table) {
        global $wpdb;

        $normalized_flag_option = 'lllm_legacy_playoff_competition_normalized';
        if (get_option($normalized_flag_option) === '1') {
            return;
        }

        $wpdb->query(
            "UPDATE {$games_table}
             SET competition_type = 'playoff'
             WHERE competition_type <> 'playoff'
               AND (
                    (playoff_round IS NOT NULL AND playoff_round <> '')
                    OR (playoff_slot IS NOT NULL AND playoff_slot <> '')
                    OR (source_game_uid_1 IS NOT NULL AND source_game_uid_1 <> '')
                    OR (source_game_uid_2 IS NOT NULL AND source_game_uid_2 <> '')
               )"
        );

        update_option($normalized_flag_option, '1', false);
    }
}

