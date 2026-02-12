<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLLM_Admin {
    /**
     * Returns shortcode definitions used by the admin shortcode builder UI.
     *
     * @return array<string,array<string,mixed>>
     */
    private static function get_shortcode_definition_map() {
        return array(
            'lllm_schedule' => array(
                'display_label' => __('Schedule Table', 'lllm'),
                'attributes' => array('season', 'division', 'team_code', 'show_past', 'show_future', 'limit'),
                'attribute_meta' => array(
                    'season' => array(
                        'label' => __('Season', 'lllm'),
                        'control_type' => 'select',
                        'value_source' => array('type' => 'dynamic', 'key' => 'season_slugs'),
                        'default_value' => '',
                        'optional' => true,
                    ),
                    'division' => array(
                        'label' => __('Division', 'lllm'),
                        'control_type' => 'select',
                        'value_source' => array('type' => 'dynamic', 'key' => 'division_slugs'),
                        'default_value' => '',
                        'optional' => true,
                    ),
                    'team_code' => array(
                        'label' => __('Team Code', 'lllm'),
                        'control_type' => 'select',
                        'value_source' => array('type' => 'dynamic', 'key' => 'team_codes'),
                        'default_value' => '',
                        'optional' => true,
                    ),
                    'show_past' => array(
                        'label' => __('Show Past Games', 'lllm'),
                        'control_type' => 'checkbox',
                        'value_source' => array(
                            'type' => 'static',
                            'options' => array(
                                array('label' => __('Enabled', 'lllm'), 'value' => '1'),
                                array('label' => __('Disabled', 'lllm'), 'value' => '0'),
                            ),
                        ),
                        'default_value' => '1',
                        'optional' => true,
                    ),
                    'show_future' => array(
                        'label' => __('Show Future Games', 'lllm'),
                        'control_type' => 'checkbox',
                        'value_source' => array(
                            'type' => 'static',
                            'options' => array(
                                array('label' => __('Enabled', 'lllm'), 'value' => '1'),
                                array('label' => __('Disabled', 'lllm'), 'value' => '0'),
                            ),
                        ),
                        'default_value' => '1',
                        'optional' => true,
                    ),
                    'limit' => array(
                        'label' => __('Row Limit', 'lllm'),
                        'control_type' => 'number',
                        'value_source' => array('type' => 'manual'),
                        'default_value' => '50',
                        'optional' => true,
                    ),
                ),
            ),
            'lllm_standings' => array(
                'display_label' => __('Standings Table', 'lllm'),
                'attributes' => array('season', 'division'),
                'attribute_meta' => array(
                    'season' => array(
                        'label' => __('Season', 'lllm'),
                        'control_type' => 'select',
                        'value_source' => array('type' => 'dynamic', 'key' => 'season_slugs'),
                        'default_value' => '',
                        'optional' => true,
                    ),
                    'division' => array(
                        'label' => __('Division', 'lllm'),
                        'control_type' => 'select',
                        'value_source' => array('type' => 'dynamic', 'key' => 'division_slugs'),
                        'default_value' => '',
                        'optional' => true,
                    ),
                ),
            ),
            'lllm_teams' => array(
                'display_label' => __('Team List', 'lllm'),
                'attributes' => array('season', 'division', 'show_logos'),
                'attribute_meta' => array(
                    'season' => array(
                        'label' => __('Season', 'lllm'),
                        'control_type' => 'select',
                        'value_source' => array('type' => 'dynamic', 'key' => 'season_slugs'),
                        'default_value' => '',
                        'optional' => true,
                    ),
                    'division' => array(
                        'label' => __('Division', 'lllm'),
                        'control_type' => 'select',
                        'value_source' => array('type' => 'dynamic', 'key' => 'division_slugs'),
                        'default_value' => '',
                        'optional' => true,
                    ),
                    'show_logos' => array(
                        'label' => __('Show Team Logos', 'lllm'),
                        'control_type' => 'checkbox',
                        'value_source' => array(
                            'type' => 'static',
                            'options' => array(
                                array('label' => __('Enabled', 'lllm'), 'value' => '1'),
                                array('label' => __('Disabled', 'lllm'), 'value' => '0'),
                            ),
                        ),
                        'default_value' => '0',
                        'optional' => true,
                    ),
                ),
            ),
            'lllm_playoff_bracket' => array(
                'display_label' => __('Playoff Bracket', 'lllm'),
                'attributes' => array('season', 'division'),
                'attribute_meta' => array(
                    'season' => array(
                        'label' => __('Season', 'lllm'),
                        'control_type' => 'select',
                        'value_source' => array('type' => 'dynamic', 'key' => 'season_slugs'),
                        'default_value' => '',
                        'optional' => true,
                    ),
                    'division' => array(
                        'label' => __('Division', 'lllm'),
                        'control_type' => 'select',
                        'value_source' => array('type' => 'dynamic', 'key' => 'division_slugs'),
                        'default_value' => '',
                        'optional' => true,
                    ),
                ),
            ),
        );
    }

    /**
     * Returns dynamic value-source options used by shortcode attribute schema.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return array<string,array<int,array{label:string,value:string}>>
     */
    private static function get_shortcode_generator_value_sources() {
        global $wpdb;

        $sources = array(
            'season_slugs' => array(),
            'division_slugs' => array(),
            'team_codes' => array(),
        );

        $season_rows = $wpdb->get_results('SELECT slug, name FROM ' . self::table('seasons') . ' ORDER BY name ASC');
        foreach ($season_rows as $season_row) {
            $sources['season_slugs'][] = array(
                'label' => $season_row->name . ' (' . $season_row->slug . ')',
                'value' => $season_row->slug,
            );
        }

        $division_rows = $wpdb->get_results(
            'SELECT d.slug, d.name, s.name AS season_name
             FROM ' . self::table('divisions') . ' d
             LEFT JOIN ' . self::table('seasons') . ' s ON s.id = d.season_id
             ORDER BY s.name ASC, d.name ASC'
        );
        foreach ($division_rows as $division_row) {
            $season_name = isset($division_row->season_name) ? $division_row->season_name : '';
            $label = $division_row->name . ' (' . $division_row->slug . ')';
            if ($season_name !== '') {
                $label .= ' — ' . $season_name;
            }

            $sources['division_slugs'][] = array(
                'label' => $label,
                'value' => $division_row->slug,
            );
        }

        $team_rows = $wpdb->get_results('SELECT team_code, name FROM ' . self::table('team_masters') . ' ORDER BY name ASC');
        foreach ($team_rows as $team_row) {
            $sources['team_codes'][] = array(
                'label' => $team_row->name . ' (' . $team_row->team_code . ')',
                'value' => $team_row->team_code,
            );
        }

        return $sources;
    }

    /**
     * Registers the League Manager admin menu and submenus.
     *
     * @return void
     */
    public static function register_menu() {
        add_menu_page(
            __('League Manager', 'lllm'),
            __('League Manager', 'lllm'),
            'lllm_manage_seasons',
            'lllm-seasons',
            array(__CLASS__, 'render_seasons'),
            'dashicons-clipboard',
            56
        );

        add_submenu_page(
            'lllm-seasons',
            __('Welcome', 'lllm'),
            __('Welcome', 'lllm'),
            'lllm_manage_seasons',
            'lllm-welcome',
            array(__CLASS__, 'render_welcome')
        );

        add_submenu_page(
            'lllm-seasons',
            __('Seasons', 'lllm'),
            __('Seasons', 'lllm'),
            'lllm_manage_seasons',
            'lllm-seasons',
            array(__CLASS__, 'render_seasons')
        );

        add_submenu_page(
            'lllm-seasons',
            __('Divisions', 'lllm'),
            __('Divisions', 'lllm'),
            'lllm_manage_divisions',
            'lllm-divisions',
            array(__CLASS__, 'render_divisions')
        );

        add_submenu_page(
            'lllm-seasons',
            __('Franchises', 'lllm'),
            __('Franchises', 'lllm'),
            'lllm_manage_teams',
            'lllm-franchises',
            array(__CLASS__, 'render_teams')
        );

        add_submenu_page(
            'lllm-seasons',
            __('Teams', 'lllm'),
            __('Teams', 'lllm'),
            'lllm_manage_teams',
            'lllm-division-teams',
            array(__CLASS__, 'render_division_teams')
        );

        add_submenu_page(
            'lllm-seasons',
            __('Games', 'lllm'),
            __('Games', 'lllm'),
            'lllm_manage_games',
            'lllm-games',
            array(__CLASS__, 'render_games')
        );

        add_submenu_page(
            'lllm-seasons',
            __('Import Logs', 'lllm'),
            __('Import Logs', 'lllm'),
            'lllm_view_logs',
            'lllm-import-logs',
            array(__CLASS__, 'render_import_logs')
        );

        add_submenu_page(
            'lllm-seasons',
            __('Shortcode Generator', 'lllm'),
            __('Shortcode Generator', 'lllm'),
            'lllm_manage_seasons',
            'lllm-shortcode-generator',
            array(__CLASS__, 'render_shortcode_generator')
        );

        // Remove the duplicate auto-generated first submenu item that points to the parent slug.
        remove_submenu_page('lllm-seasons', 'lllm-seasons');
    }

    /**
     * Registers `admin_post_*` handlers for CRUD, CSV, and import actions.
     *
     * @return void
     */
    public static function register_actions() {
        add_action('admin_post_lllm_save_season', array(__CLASS__, 'handle_save_season'));
        add_action('admin_post_lllm_save_division', array(__CLASS__, 'handle_save_division'));
        add_action('admin_post_lllm_save_team', array(__CLASS__, 'handle_save_team'));
        add_action('admin_post_lllm_update_division_teams', array(__CLASS__, 'handle_update_division_teams'));
        add_action('admin_post_lllm_delete_season', array(__CLASS__, 'handle_delete_season'));
        add_action('admin_post_lllm_bulk_delete_seasons', array(__CLASS__, 'handle_bulk_delete_seasons'));
        add_action('admin_post_lllm_delete_division', array(__CLASS__, 'handle_delete_division'));
        add_action('admin_post_lllm_bulk_delete_divisions', array(__CLASS__, 'handle_bulk_delete_divisions'));
        add_action('admin_post_lllm_delete_team', array(__CLASS__, 'handle_delete_team'));
        add_action('admin_post_lllm_bulk_delete_teams', array(__CLASS__, 'handle_bulk_delete_teams'));
        add_action('admin_post_lllm_delete_game', array(__CLASS__, 'handle_delete_game'));
        add_action('admin_post_lllm_bulk_delete_games', array(__CLASS__, 'handle_bulk_delete_games'));
        add_action('admin_post_lllm_quick_edit_game', array(__CLASS__, 'handle_quick_edit_game'));
        add_action('admin_post_lllm_import_validate', array(__CLASS__, 'handle_import_validate'));
        add_action('admin_post_lllm_import_commit', array(__CLASS__, 'handle_import_commit'));
        add_action('admin_post_lllm_download_template', array(__CLASS__, 'handle_download_template'));
        add_action('admin_post_lllm_download_current_games', array(__CLASS__, 'handle_download_current_games'));
        add_action('admin_post_lllm_generate_playoff_bracket', array(__CLASS__, 'handle_generate_playoff_bracket'));
        add_action('admin_post_lllm_reset_playoff_bracket', array(__CLASS__, 'handle_reset_playoff_bracket'));
        add_action('admin_post_lllm_download_divisions_template', array(__CLASS__, 'handle_download_divisions_template'));
        add_action('admin_post_lllm_validate_divisions_csv', array(__CLASS__, 'handle_validate_divisions_csv'));
        add_action('admin_post_lllm_import_divisions_csv', array(__CLASS__, 'handle_import_divisions_csv'));
        add_action('admin_post_lllm_download_teams_template', array(__CLASS__, 'handle_download_teams_template'));
        add_action('admin_post_lllm_export_franchises_csv', array(__CLASS__, 'handle_export_franchises_csv'));
        add_action('admin_post_lllm_validate_teams_csv', array(__CLASS__, 'handle_validate_teams_csv'));
        add_action('admin_post_lllm_import_teams_csv', array(__CLASS__, 'handle_import_teams_csv'));
        add_action('admin_post_lllm_download_division_teams_template', array(__CLASS__, 'handle_download_division_teams_template'));
        add_action('admin_post_lllm_validate_division_teams_csv', array(__CLASS__, 'handle_validate_division_teams_csv'));
        add_action('admin_post_lllm_import_division_teams_csv', array(__CLASS__, 'handle_import_division_teams_csv'));
    }

    /**
     * Enqueues assets used by League Manager admin pages.
     *
     * On the Franchises page, this wires the WordPress media picker for logo selection.
     *
     * @param string $hook Current admin hook suffix.
     * @return void
     */
    public static function enqueue_assets($hook) {
        if (!empty($_GET['page']) && $_GET['page'] === 'lllm-franchises') {
            self::enqueue_franchise_assets();
        }

        if (!empty($_GET['page']) && $_GET['page'] === 'lllm-shortcode-generator') {
            self::enqueue_shortcode_generator_assets();
        }
    }

    /**
     * Enqueues media picker assets for the Franchises admin screen.
     *
     * @return void
     */
    private static function enqueue_franchise_assets() {
        wp_enqueue_media();
        wp_add_inline_script(
            'jquery',
            '(function($){$(function(){var frame;function setLogo(id,url){$("#lllm-team-logo-id").val(id||"");if(url){$("#lllm-team-logo-preview").attr("src",url).show();}else{$("#lllm-team-logo-preview").attr("src","").hide();}}$("#lllm-team-logo-select").on("click",function(e){e.preventDefault();if(frame){frame.open();return;}frame=wp.media({title:"' . esc_js(__('Select Franchise Logo', 'lllm')) . '",button:{text:"' . esc_js(__('Use this logo', 'lllm')) . '"},multiple:false});frame.on("select",function(){var attachment=frame.state().get("selection").first().toJSON();setLogo(attachment.id,attachment.sizes&&attachment.sizes.thumbnail?attachment.sizes.thumbnail.url:attachment.url);});frame.open();});$("#lllm-team-logo-remove").on("click",function(e){e.preventDefault();setLogo("", "");});});})(jQuery);'
        );
    }

    /**
     * Enqueues script and styles only for the Shortcode Generator admin screen.
     *
     * @return void
     */
    private static function enqueue_shortcode_generator_assets() {
        $asset_base_url = plugin_dir_url(LLLM_PLUGIN_FILE) . 'assets/';

        wp_enqueue_style(
            'lllm-shortcode-generator-admin',
            $asset_base_url . 'shortcode-generator-admin.css',
            array(),
            LLLM_VERSION
        );

        wp_enqueue_script(
            'lllm-shortcode-generator-admin',
            $asset_base_url . 'shortcode-generator-admin.js',
            array(),
            LLLM_VERSION,
            true
        );

        wp_localize_script(
            'lllm-shortcode-generator-admin',
            'lllmShortcodeGeneratorConfig',
            array(
                'shortcodeMap' => self::get_shortcode_definition_map(),
                'valueSources' => self::get_shortcode_generator_value_sources(),
                'messages' => array(
                    'optional' => __('optional', 'lllm'),
                    'copied' => __('Copied!', 'lllm'),
                    'copyFallback' => __('Press Ctrl/Cmd+C to copy.', 'lllm'),
                ),
            )
        );
    }

    /**
     * Resolves a plugin table name using the WordPress table prefix.
     *
     * @param string $name Base table suffix (without `lllm_`).
     * @return string Full table name.
     */
    private static function table($name) {
        global $wpdb;
        return $wpdb->prefix . 'lllm_' . $name;
    }

    /**
     * Returns all seasons ordered by creation date descending.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return array<int,object> Season rows.
     */
    private static function get_seasons() {
        global $wpdb;
        return $wpdb->get_results('SELECT * FROM ' . self::table('seasons') . ' ORDER BY created_at DESC');
    }

    /**
     * Returns divisions for a season.
     *
     * @param int $season_id Season primary key.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return array<int,object> Division rows.
     */
    private static function get_divisions($season_id) {
        if (!$season_id) {
            return array();
        }

        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . self::table('divisions') . ' WHERE season_id = %d ORDER BY name ASC',
                $season_id
            )
        );
    }

    /**
     * Ensures a string value is unique in a table column by appending a suffix.
     *
     * Example progression: `slug`, `slug-2`, `slug-3`, ...
     *
     * @param string $table      Full table name.
     * @param string $column     Column name to check.
     * @param string $value      Candidate value.
     * @param int    $exclude_id Optional row id to ignore during updates.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return string Unique value.
     */
    private static function unique_value($table, $column, $value, $exclude_id = 0) {
        global $wpdb;
        $base = $value;
        $suffix = 1;
        while (true) {
            if ($exclude_id) {
                $query = $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE {$column} = %s AND id != %d",
                    $value,
                    $exclude_id
                );
            } else {
                $query = $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE {$column} = %s",
                    $value
                );
            }
            $existing = $wpdb->get_var($query);
            if (!$existing) {
                return $value;
            }
            $suffix++;
            $value = $base . '-' . $suffix;
        }
    }


    /**
     * Normalizes team codes to lowercase alphanumeric characters only.
     *
     * @param string $value Raw team code input.
     * @return string Normalized team code.
     */
    private static function normalize_team_code($value) {
        $value = strtolower((string) $value);
        return preg_replace('/[^a-z0-9]+/', '', $value);
    }

    /**
     * Normalizes a CSV matrix header token for case-insensitive matching.
     *
     * @param string $value Raw header value.
     * @return string Normalized header key.
     */
    private static function normalize_csv_matrix_header($value) {
        return strtolower(trim((string) $value));
    }

    /**
     * Returns whether a matrix cell should be treated as unassigned.
     *
     * Blank cells and the literal token FALSE (case-insensitive) are considered
     * unassigned; any other non-empty token is treated as assigned.
     *
     * @param string $value Raw cell value.
     * @return bool True when the cell means "unassigned".
     */
    private static function matrix_cell_is_unassigned($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return true;
        }

        return strtolower($value) === 'false';
    }


    /**
     * Normalizes a game UID to uppercase alphanumeric value.
     *
     * @param string $value Raw game UID input.
     * @return string Normalized game UID.
     */
    private static function normalize_game_uid($value) {
        $value = strtoupper((string) $value);
        return preg_replace('/[^A-Z0-9]+/', '', $value);
    }

    /**
     * Normalizes playoff metadata values to lowercase slug-like tokens.
     *
     * @param string $value Raw metadata value.
     * @return string Normalized metadata token.
     */
    private static function normalize_playoff_meta_value($value) {
        $value = strtolower((string) $value);
        return preg_replace('/[^a-z0-9_-]+/', '', $value);
    }

    /**
     * Validates and sanitizes game competition/playoff metadata.
     *
     * @param array<string,mixed> $data Candidate game payload.
     * @return array{valid:bool,error:string,data:array<string,mixed>} Validation result.
     */
    private static function validate_game_competition_data($data) {
        $competition_type = isset($data['competition_type']) ? self::normalize_playoff_meta_value($data['competition_type']) : 'regular';
        if ($competition_type === '') {
            $competition_type = 'regular';
        }

        $playoff_round = isset($data['playoff_round']) ? self::normalize_playoff_meta_value($data['playoff_round']) : '';
        $playoff_slot = isset($data['playoff_slot']) ? self::normalize_playoff_meta_value($data['playoff_slot']) : '';

        $source_game_uid_1 = isset($data['source_game_uid_1']) ? self::normalize_game_uid($data['source_game_uid_1']) : '';
        $source_game_uid_2 = isset($data['source_game_uid_2']) ? self::normalize_game_uid($data['source_game_uid_2']) : '';

        if (!in_array($competition_type, array('regular', 'playoff'), true)) {
            return array(
                'valid' => false,
                'error' => __('Invalid competition type.', 'lllm'),
                'data' => $data,
            );
        }

        if ($competition_type === 'regular') {
            if ($playoff_round !== '' || $playoff_slot !== '' || $source_game_uid_1 !== '' || $source_game_uid_2 !== '') {
                return array(
                    'valid' => false,
                    'error' => __('Regular games cannot include playoff metadata.', 'lllm'),
                    'data' => $data,
                );
            }

            $data['competition_type'] = 'regular';
            $data['playoff_round'] = null;
            $data['playoff_slot'] = null;
            $data['source_game_uid_1'] = null;
            $data['source_game_uid_2'] = null;

            return array(
                'valid' => true,
                'error' => '',
                'data' => $data,
            );
        }

        // Central playoff round/slot allowlist shared by imports and quick edit writes.
        $round_slots = array(
            'r1' => array('1', '2', '3', '4'),
            'r2' => array('1', '2'),
            'championship' => array('1'),
        );

        if ($playoff_round === '' || !isset($round_slots[$playoff_round])) {
            return array(
                'valid' => false,
                'error' => __('Playoff games require a valid playoff round.', 'lllm'),
                'data' => $data,
            );
        }

        if ($playoff_slot === '' || !in_array($playoff_slot, $round_slots[$playoff_round], true)) {
            return array(
                'valid' => false,
                'error' => __('Playoff games require a valid playoff slot for the selected round.', 'lllm'),
                'data' => $data,
            );
        }

        $allow_source_uids = in_array($playoff_round, array('r2', 'championship'), true);
        if (!$allow_source_uids && ($source_game_uid_1 !== '' || $source_game_uid_2 !== '')) {
            return array(
                'valid' => false,
                'error' => __('Feeder source game UIDs are only allowed for R2 or Championship playoff games.', 'lllm'),
                'data' => $data,
            );
        }

        if ($source_game_uid_1 !== '' && strlen($source_game_uid_1) !== 12) {
            return array(
                'valid' => false,
                'error' => __('source_game_uid_1 must be exactly 12 characters when provided.', 'lllm'),
                'data' => $data,
            );
        }

        if ($source_game_uid_2 !== '' && strlen($source_game_uid_2) !== 12) {
            return array(
                'valid' => false,
                'error' => __('source_game_uid_2 must be exactly 12 characters when provided.', 'lllm'),
                'data' => $data,
            );
        }

        $data['competition_type'] = 'playoff';
        $data['playoff_round'] = $playoff_round;
        $data['playoff_slot'] = $playoff_slot;
        $data['source_game_uid_1'] = $source_game_uid_1 !== '' ? $source_game_uid_1 : null;
        $data['source_game_uid_2'] = $source_game_uid_2 !== '' ? $source_game_uid_2 : null;

        return array(
            'valid' => true,
            'error' => '',
            'data' => $data,
        );
    }

    /**
     * Returns quick-edit game type options keyed by form value.
     *
     * @return array<string,string> Game type options.
     */
    private static function get_quick_edit_game_types() {
        // Keep values stable; form posts persist these keys directly.
        return array(
            'regular' => __('Regular Game', 'lllm'),
            'playoff_r1' => __('Playoff R1', 'lllm'),
            'playoff_r2' => __('Playoff R2', 'lllm'),
            'playoff_championship' => __('Championship', 'lllm'),
        );
    }

    /**
     * Determines quick-edit type key from a game row.
     *
     * @param object $game Game row object.
     * @return string Quick-edit game type key.
     */
    private static function get_quick_edit_game_type_value($game) {
        if ($game->competition_type !== 'playoff') {
            return 'regular';
        }

        if ($game->playoff_round === 'r1') {
            return 'playoff_r1';
        }

        if ($game->playoff_round === 'r2') {
            return 'playoff_r2';
        }

        if ($game->playoff_round === 'championship') {
            return 'playoff_championship';
        }

        return 'regular';
    }

    /**
     * Returns true when a playoff game row was generated by bracket generation.
     *
     * @param object $game Game row object.
     * @return bool True when generated playoff structure marker is present.
     */
    private static function is_generated_playoff_game($game) {
        if (!is_object($game) || $game->competition_type !== 'playoff') {
            return false;
        }

        return in_array((string) $game->notes, array('r1_g1', 'r1_g2', 'r2_g1', 'r2_g2', 'ch_g1'), true);
    }

    /**
     * Redirects to an admin URL with standard League Manager notice args.
     *
     * @param string $url     Destination URL.
     * @param string $notice  Notice key consumed by `render_notices()`.
     * @param string $message Optional notice message.
     * @return void
     */
    private static function redirect_with_notice($url, $notice, $message = '') {
        $url = add_query_arg('lllm_notice', $notice, $url);
        if ($message !== '') {
            $url = add_query_arg('lllm_message', rawurlencode($message), $url);
        }
        wp_safe_redirect($url);
        exit;
    }

    /**
     * Renders notice banners from query-string notice values.
     *
     * @return void
     */
    private static function render_notices() {
        if (empty($_GET['lllm_notice'])) {
            return;
        }

        $notice = sanitize_text_field(wp_unslash($_GET['lllm_notice']));
        $message = '';
        if (!empty($_GET['lllm_message'])) {
            $message = sanitize_text_field(wp_unslash($_GET['lllm_message']));
        }

        $class = 'notice notice-success';
        $text = '';

        switch ($notice) {
            case 'season_saved':
                $text = __('Season saved.', 'lllm');
                break;
            case 'division_saved':
                $text = __('Division saved.', 'lllm');
                break;
            case 'team_saved':
                $text = __('Franchise saved.', 'lllm');
                break;
            case 'division_teams_updated':
                $text = __('Division teams updated.', 'lllm');
                break;
            case 'division_teams_blocked':
                $class = 'notice notice-warning';
                $text = __('Some teams could not be removed because games exist:', 'lllm') . ' ' . $message;
                break;
            case 'season_deleted':
                $text = __('Season deleted.', 'lllm');
                break;
            case 'division_deleted':
                $text = __('Division deleted.', 'lllm');
                break;
            case 'team_deleted':
                $text = __('Franchise deleted.', 'lllm');
                break;
            case 'game_deleted':
                $text = __('Game deleted.', 'lllm');
                break;
            case 'delete_blocked':
                $class = 'notice notice-warning';
                $text = $message ? $message : __('Delete blocked.', 'lllm');
                break;
            case 'game_saved':
                $text = __('Game updated.', 'lllm');
                break;
            case 'import_complete':
                $text = __('Import complete.', 'lllm');
                break;
            case 'playoff_generated':
                $text = __('Playoff bracket generated.', 'lllm');
                break;
            case 'playoff_reset':
                $text = __('Playoff games reset.', 'lllm');
                break;
            case 'csv_validated':
                $text = $message ? $message : __('CSV validated successfully.', 'lllm');
                break;
            case 'error':
                $class = 'notice notice-error';
                $text = $message ? $message : __('An error occurred.', 'lllm');
                break;
        }

        if ($text) {
            echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($text) . '</p></div>';
        }
    }

    /**
     * Parses an uploaded CSV file from `$_FILES`.
     *
     * @param string $file_key Input field key in `$_FILES`.
     * @return array<string,mixed>|WP_Error Parsed CSV payload or error.
     */
    private static function parse_uploaded_csv($file_key = 'csv_file') {
        if (empty($_FILES[$file_key]) || empty($_FILES[$file_key]['tmp_name'])) {
            return new WP_Error('lllm_csv_required', __('CSV file is required.', 'lllm'));
        }

        return LLLM_Import::parse_csv($_FILES[$file_key]['tmp_name']);
    }

    /**
     * Creates a game row using shared UID/timestamp behavior.
     *
     * @param array<string,mixed> $payload Candidate game payload.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return array{ok:bool,uid:string,error:string} Insert result details.
     */
    private static function create_game_record($payload) {
        global $wpdb;

        $payload['game_uid'] = isset($payload['game_uid']) && $payload['game_uid'] !== ''
            ? self::normalize_game_uid($payload['game_uid'])
            : LLLM_Import::unique_game_uid();

        $validation = self::validate_game_competition_data($payload);
        if (!$validation['valid']) {
            return array(
                'ok' => false,
                'uid' => '',
                'error' => $validation['error'],
            );
        }

        $payload = $validation['data'];
        $payload['created_at'] = current_time('mysql', true);
        $payload['updated_at'] = current_time('mysql', true);

        $inserted = $wpdb->insert(self::table('games'), $payload);
        if (!$inserted) {
            $last_error = (string) $wpdb->last_error;

            // If the row already exists by natural unique key, treat this as an update.
            // This avoids hard-failing commits when duplicate creates slip through preview.
            if (
                strpos($last_error, 'Duplicate entry') !== false
                && strpos($last_error, 'game_unique') !== false
            ) {
                $existing_id = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        'SELECT id FROM ' . self::table('games') . ' WHERE division_id = %d AND start_datetime_utc = %s AND home_team_instance_id = %d AND away_team_instance_id = %d',
                        $payload['division_id'],
                        $payload['start_datetime_utc'],
                        $payload['home_team_instance_id'],
                        $payload['away_team_instance_id']
                    )
                );

                if ($existing_id > 0) {
                    $update_payload = $payload;
                    unset($update_payload['game_uid']);
                    $update_payload['updated_at'] = current_time('mysql', true);
                    $updated = $wpdb->update(self::table('games'), $update_payload, array('id' => $existing_id));
                    if ($updated !== false) {
                        $resolved_uid = (string) $wpdb->get_var(
                            $wpdb->prepare('SELECT game_uid FROM ' . self::table('games') . ' WHERE id = %d', $existing_id)
                        );

                        return array(
                            'ok' => true,
                            'uid' => $resolved_uid,
                            'error' => '',
                        );
                    }
                }
            }

            $error_message = __('Unable to create game record.', 'lllm');
            if ($last_error !== '') {
                $error_message .= ' ' . sprintf(__('Database error: %s', 'lllm'), $last_error);
            }

            return array(
                'ok' => false,
                'uid' => '',
                'error' => $error_message,
            );
        }

        return array(
            'ok' => true,
            'uid' => $payload['game_uid'],
            'error' => '',
        );
    }

    /**
     * Returns whether playoff games already exist in a division.
     *
     * @param int $division_id Division primary key.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return bool True when at least one playoff game exists.
     */
    private static function division_has_playoff_games($division_id) {
        global $wpdb;
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . self::table('games') . ' WHERE division_id = %d AND competition_type = %s',
                $division_id,
                'playoff'
            )
        );

        return $count > 0;
    }

    /**
     * Validates that a parsed CSV includes all required headers.
     *
     * @param array<string,mixed>|WP_Error $parsed           Parsed CSV payload.
     * @param array<int,string>            $expected_headers Required header names.
     * @return array<int,string>|WP_Error Lowercased CSV headers or error.
     */
    private static function validate_csv_headers($parsed, $expected_headers) {
        if (!is_array($parsed) || empty($parsed['headers'])) {
            return new WP_Error('lllm_csv_invalid', __('CSV headers are required.', 'lllm'));
        }

        $headers_lower = array_map('strtolower', $parsed['headers']);
        $expected_lower = array_map('strtolower', $expected_headers);
        $missing = array_diff($expected_lower, $headers_lower);
        if ($missing) {
            return new WP_Error(
                'lllm_csv_missing_headers',
                sprintf(__('Missing required headers: %s', 'lllm'), implode(', ', $missing))
            );
        }

        return $headers_lower;
    }

    /**
     * Renders the Welcome admin screen.
     *
     * @return void
     */
    public static function render_welcome() {
        if (!current_user_can('lllm_manage_seasons')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Welcome to League Manager', 'lllm') . '</h1>';
        echo '<p>' . esc_html__('League Manager helps you run your Little League season with a simple workflow: set up seasons/divisions/franchises, assign teams, import schedules, and post weekly scores.', 'lllm') . '</p>';
        echo '<p>' . esc_html__('Managers can keep data consistent by using the CSV templates and import tools, while viewing game results and standings in one place for each division.', 'lllm') . '</p>';
        echo '<p>' . esc_html__('To get started, use the submenu on the left beginning with Seasons, then Divisions, Franchises, Teams, and Games.', 'lllm') . '</p>';
        echo '<p>' . esc_html__('Need shortcode help? Open the Shortcode Generator submenu to build and copy shortcode snippets.', 'lllm') . '</p>';
        echo '</div>';
    }

    /**
     * Renders the Shortcode Generator admin screen.
     *
     * @return void
     */
    public static function render_shortcode_generator() {
        if (!current_user_can('lllm_manage_seasons')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Shortcode Generator', 'lllm') . '</h1>';
        echo '<p>' . esc_html__('Build a shortcode by selecting a type and filling in attributes. Leave optional fields blank to omit them.', 'lllm') . '</p>';
        echo '<div class="lllm-shortcode-generator" data-lllm-shortcode-generator="true">';
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row"><label for="lllm-shortcode-type">' . esc_html__('Shortcode Type', 'lllm') . '</label></th>';
        echo '<td><select id="lllm-shortcode-type"><option value="">' . esc_html__('Select a shortcode', 'lllm') . '</option></select></td></tr>';
        echo '</tbody></table>';
        echo '<div id="lllm-shortcode-attributes"></div>';
        echo '<p><label for="lllm-shortcode-output"><strong>' . esc_html__('Generated Shortcode', 'lllm') . '</strong></label></p>';
        echo '<p><textarea id="lllm-shortcode-output" class="large-text code" readonly rows="3"></textarea></p>';
        echo '<p><button type="button" id="lllm-shortcode-copy" class="button button-secondary">' . esc_html__('Copy Shortcode', 'lllm') . '</button></p>';
        echo '<p id="lllm-shortcode-copy-notice" class="description" aria-live="polite"></p>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Renders the Seasons admin screen (create/edit/list/delete).
     *
     * @return void
     */
    public static function render_seasons() {
        if (!current_user_can('lllm_manage_seasons')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        global $wpdb;
        $table = self::table('seasons');
        $seasons = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC");
        $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $editing = null;

        if ($edit_id) {
            $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $edit_id));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Seasons', 'lllm') . '</h1>';
        self::render_notices();

        echo '<h2>' . esc_html($editing ? __('Edit Season', 'lllm') : __('Add Season', 'lllm')) . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lllm_save_season');
        echo '<input type="hidden" name="action" value="lllm_save_season">';
        if ($editing) {
            echo '<input type="hidden" name="id" value="' . esc_attr($editing->id) . '">';
        }

        $season_name = $editing ? $editing->name : '';
        $timezone = $editing ? $editing->timezone : wp_timezone_string();
        $is_active = $editing ? (int) $editing->is_active : 0;

        echo '<table class="form-table"><tbody>';
        echo '<tr><th scope="row"><label for="lllm-season-name">' . esc_html__('Season Name', 'lllm') . '</label></th>';
        echo '<td><input name="name" id="lllm-season-name" type="text" class="regular-text" value="' . esc_attr($season_name) . '" placeholder="' . esc_attr__('Fall 2026', 'lllm') . '" required></td></tr>';

        echo '<tr><th scope="row"><label for="lllm-season-timezone">' . esc_html__('Timezone', 'lllm') . '</label></th>';
        echo '<td><input name="timezone" id="lllm-season-timezone" type="text" class="regular-text" value="' . esc_attr($timezone) . '"></td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Active', 'lllm') . '</th>';
        echo '<td><label><input name="is_active" type="checkbox" value="1" ' . checked(1, $is_active, false) . '> ' . esc_html__('Set as active season', 'lllm') . '</label></td></tr>';
        echo '</tbody></table>';

        submit_button($editing ? __('Update Season', 'lllm') : __('Create Season', 'lllm'));
        echo '</form>';

        echo '<h2>' . esc_html__('All Seasons', 'lllm') . '</h2>';
        if (!$seasons) {
            echo '<p>' . esc_html__('No seasons yet.', 'lllm') . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th><input type="checkbox" onclick="document.querySelectorAll(\'.lllm-season-select\').forEach(el => el.checked = this.checked);"></th>';
            echo '<th>' . esc_html__('Name', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Timezone', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Status', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Actions', 'lllm') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($seasons as $season) {
                $status = $season->is_active ? __('Active', 'lllm') : __('Inactive', 'lllm');
                $edit_link = add_query_arg(
                    array('page' => 'lllm-seasons', 'edit' => $season->id),
                    admin_url('admin.php')
                );
                echo '<tr>';
                echo '<td><input class="lllm-season-select" type="checkbox" name="season_ids[]" value="' . esc_attr($season->id) . '" form="lllm-bulk-seasons"></td>';
                echo '<td>' . esc_html($season->name) . '</td>';
                echo '<td>' . esc_html($season->timezone) . '</td>';
                echo '<td>' . esc_html($status) . '</td>';
                echo '<td><a href="' . esc_url($edit_link) . '">' . esc_html__('Edit', 'lllm') . '</a>';
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin-left:8px;">';
                wp_nonce_field('lllm_delete_season', 'lllm_delete_season_nonce');
                echo '<input type="hidden" name="action" value="lllm_delete_season">';
                echo '<input type="hidden" name="id" value="' . esc_attr($season->id) . '">';
                echo '<input type="text" name="confirm_text[' . esc_attr($season->id) . ']" placeholder="' . esc_attr__('Type DELETE', 'lllm') . '" class="small-text"> ';
                echo '<button class="button-link delete">' . esc_html__('Delete', 'lllm') . '</button>';
                echo '</form></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '<form id="lllm-bulk-seasons" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('lllm_bulk_delete_seasons');
            echo '<input type="hidden" name="action" value="lllm_bulk_delete_seasons">';
    

        echo '<p>' . esc_html__('Type DELETE to confirm bulk deletion:', 'lllm') . '</p>';
            echo '<input type="text" name="confirm_text_bulk" class="regular-text"> ';
            submit_button(__('Bulk Delete Selected', 'lllm'), 'delete', 'submit', false);
            echo '</form>';
        }

        echo '</div>';
    }

    /**
     * Renders the Divisions admin screen and related CSV helpers.
     *
     * @return void
     */
    public static function render_divisions() {
        if (!current_user_can('lllm_manage_divisions')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        $seasons = self::get_seasons();
        $season_id = isset($_GET['season_id']) ? absint($_GET['season_id']) : 0;
        if (!$season_id && $seasons) {
            $season_id = (int) $seasons[0]->id;
        }

        $divisions = self::get_divisions($season_id);
        $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $editing = null;

        if ($edit_id) {
            global $wpdb;
            $editing = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table('divisions') . ' WHERE id = %d', $edit_id));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Divisions', 'lllm') . '</h1>';
        self::render_notices();

        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="lllm-divisions">';
        echo '<label for="lllm-season-filter">' . esc_html__('Season', 'lllm') . '</label> ';
        echo '<select id="lllm-season-filter" name="season_id" onchange="this.form.submit()">';
        foreach ($seasons as $season) {
            echo '<option value="' . esc_attr($season->id) . '" ' . selected($season_id, $season->id, false) . '>' . esc_html($season->name) . '</option>';
        }
        echo '</select>';
        echo '</form>';

        echo '<h2>' . esc_html($editing ? __('Edit Division', 'lllm') : __('Add Division', 'lllm')) . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lllm_save_division');
        echo '<input type="hidden" name="action" value="lllm_save_division">';
        if ($editing) {
            echo '<input type="hidden" name="id" value="' . esc_attr($editing->id) . '">';
        }
        echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';

        $division_name = $editing ? $editing->name : '';
        echo '<table class="form-table"><tbody>';
        echo '<tr><th scope="row"><label for="lllm-division-name">' . esc_html__('Division Name', 'lllm') . '</label></th>';
        echo '<td><input name="name" id="lllm-division-name" type="text" class="regular-text" value="' . esc_attr($division_name) . '" placeholder="' . esc_attr__('7U Major', 'lllm') . '" required></td></tr>';
        echo '</tbody></table>';

        submit_button($editing ? __('Update Division', 'lllm') : __('Add Division', 'lllm'));
        echo '</form>';

        echo '<h2>' . esc_html__('Divisions', 'lllm') . '</h2>';
        if (!$season_id) {
            echo '<p>' . esc_html__('Create a season before adding divisions.', 'lllm') . '</p>';
        } elseif (!$divisions) {
            echo '<p>' . esc_html__('No divisions for this season yet.', 'lllm') . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th><input type="checkbox" onclick="document.querySelectorAll(\'.lllm-division-select\').forEach(el => el.checked = this.checked);"></th>';
            echo '<th>' . esc_html__('Name', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Actions', 'lllm') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($divisions as $division) {
                $edit_link = add_query_arg(
                    array('page' => 'lllm-divisions', 'season_id' => $season_id, 'edit' => $division->id),
                    admin_url('admin.php')
                );
                echo '<tr>';
                echo '<td><input class="lllm-division-select" type="checkbox" name="division_ids[]" value="' . esc_attr($division->id) . '" form="lllm-bulk-divisions"></td>';
                echo '<td>' . esc_html($division->name) . '</td>';
                echo '<td><a href="' . esc_url($edit_link) . '">' . esc_html__('Edit', 'lllm') . '</a>';
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin-left:8px;">';
                wp_nonce_field('lllm_delete_division', 'lllm_delete_division_nonce');
                echo '<input type="hidden" name="action" value="lllm_delete_division">';
                echo '<input type="hidden" name="id" value="' . esc_attr($division->id) . '">';
                echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';
                echo '<input type="text" name="confirm_text[' . esc_attr($division->id) . ']" placeholder="' . esc_attr__('Type DELETE', 'lllm') . '" class="small-text"> ';
                echo '<button class="button-link delete">' . esc_html__('Delete', 'lllm') . '</button>';
                echo '</form></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '<form id="lllm-bulk-divisions" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('lllm_bulk_delete_divisions');
            echo '<input type="hidden" name="action" value="lllm_bulk_delete_divisions">';
            echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';
    

        echo '<p>' . esc_html__('Type DELETE to confirm bulk deletion:', 'lllm') . '</p>';
            echo '<input type="text" name="confirm_text_bulk" class="regular-text"> ';
            submit_button(__('Bulk Delete Selected', 'lllm'), 'delete', 'submit', false);
            echo '</form>';
        }

        if ($season_id) {
            $template_url = wp_nonce_url(
                admin_url('admin-post.php?action=lllm_download_divisions_template'),
                'lllm_download_divisions_template'
            );
            echo '<h2>' . esc_html__('Division CSV Import', 'lllm') . '</h2>';
            echo '<p>' . esc_html__('Use this template to validate division names before importing.', 'lllm') . '</p>';
            echo '<p><a class="button" href="' . esc_url($template_url) . '">' . esc_html__('Download Template', 'lllm') . '</a></p>';
            echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('lllm_validate_divisions_csv');
            echo '<input type="hidden" name="action" value="lllm_validate_divisions_csv">';
            echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';
            echo '<input type="file" name="csv_file" accept=".csv" required> ';
            submit_button(__('Validate CSV', 'lllm'), 'secondary', 'submit', false);
            echo '</form>';
            echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('lllm_import_divisions_csv');
            echo '<input type="hidden" name="action" value="lllm_import_divisions_csv">';
            echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';
            echo '<input type="file" name="csv_file" accept=".csv" required> ';
            submit_button(__('Import CSV', 'lllm'), 'primary', 'submit', false);
            echo '</form>';
        }

        echo '</div>';
    }

    /**
     * Renders the Franchises admin screen and franchise CSV helpers.
     *
     * @return void
     */
    public static function render_teams() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        global $wpdb;
        $teams = $wpdb->get_results('SELECT * FROM ' . self::table('team_masters') . ' ORDER BY name ASC');
        $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $editing = null;
        if ($edit_id) {
            $editing = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table('team_masters') . ' WHERE id = %d', $edit_id));
        }

        $can_edit_code = current_user_can('manage_options');

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Franchises', 'lllm') . '</h1>';
        self::render_notices();

        echo '<h2>' . esc_html($editing ? __('Edit Franchise', 'lllm') : __('Add Franchise', 'lllm')) . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lllm_save_team');
        echo '<input type="hidden" name="action" value="lllm_save_team">';
        if ($editing) {
            echo '<input type="hidden" name="id" value="' . esc_attr($editing->id) . '">';
        }

        $team_name = $editing ? $editing->name : '';
        $team_code = $editing ? $editing->team_code : '';
        $logo_id = $editing ? (int) $editing->logo_attachment_id : 0;
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'thumbnail') : '';

        echo '<table class="form-table"><tbody>';
        echo '<tr><th scope="row"><label for="lllm-team-name">' . esc_html__('Franchise Name', 'lllm') . '</label></th>';
        echo '<td><input name="name" id="lllm-team-name" type="text" class="regular-text" value="' . esc_attr($team_name) . '" placeholder="' . esc_attr__('Bambinos', 'lllm') . '" required></td></tr>';

        echo '<tr><th scope="row"><label for="lllm-team-code">' . esc_html__('Franchise Code', 'lllm') . '</label></th>';
        echo '<td><input name="team_code" id="lllm-team-code" type="text" class="regular-text" value="' . esc_attr($team_code) . '" placeholder="' . esc_attr__("Leave this blank if you're not sure.", 'lllm') . '" ' . ($can_edit_code ? '' : 'readonly') . '></td></tr>';
        echo '<tr><th scope="row">' . esc_html__('Franchise Logo', 'lllm') . '</th><td>';
        echo '<input type="hidden" name="logo_attachment_id" id="lllm-team-logo-id" value="' . esc_attr($logo_id) . '">';
        echo '<img id="lllm-team-logo-preview" src="' . esc_url($logo_url) . '" style="' . ($logo_url ? 'max-width:150px;height:auto;display:block;margin-bottom:8px;' : 'max-width:150px;height:auto;display:none;margin-bottom:8px;') . '" alt="">';
        echo '<button class="button" id="lllm-team-logo-select" type="button">' . esc_html__('Select Logo', 'lllm') . '</button> ';
        echo '<button class="button" id="lllm-team-logo-remove" type="button">' . esc_html__('Remove Logo', 'lllm') . '</button>';
        echo '</td></tr>';
        echo '</tbody></table>';

        submit_button($editing ? __('Update Franchise', 'lllm') : __('Add Franchise', 'lllm'));
        echo '</form>';

        $export_url = wp_nonce_url(
            admin_url('admin-post.php?action=lllm_export_franchises_csv'),
            'lllm_export_franchises_csv'
        );
        echo '<p><a class="button" href="' . esc_url($export_url) . '">' . esc_html__('Export all Franchises', 'lllm') . '</a></p>';

        echo '<h2>' . esc_html__('All Franchises', 'lllm') . '</h2>';
        if (!$teams) {
            echo '<p>' . esc_html__('No franchises yet.', 'lllm') . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th><input type="checkbox" onclick="document.querySelectorAll(\'.lllm-team-select\').forEach(el => el.checked = this.checked);"></th>';
            echo '<th>' . esc_html__('Franchise Name', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Franchise Code', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Actions', 'lllm') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($teams as $team) {
                $edit_link = add_query_arg(
                    array('page' => 'lllm-franchises', 'edit' => $team->id),
                    admin_url('admin.php')
                );
                echo '<tr>';
                echo '<td><input class="lllm-team-select" type="checkbox" name="team_ids[]" value="' . esc_attr($team->id) . '" form="lllm-bulk-teams"></td>';
                echo '<td>' . esc_html($team->name) . '</td>';
                echo '<td>' . esc_html($team->team_code) . '</td>';
                echo '<td><a href="' . esc_url($edit_link) . '">' . esc_html__('Edit', 'lllm') . '</a>';
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin-left:8px;">';
                wp_nonce_field('lllm_delete_team', 'lllm_delete_team_nonce');
                echo '<input type="hidden" name="action" value="lllm_delete_team">';
                echo '<input type="hidden" name="id" value="' . esc_attr($team->id) . '">';
                echo '<input type="text" name="confirm_text[' . esc_attr($team->id) . ']" placeholder="' . esc_attr__('Type DELETE', 'lllm') . '" class="small-text"> ';
                echo '<button class="button-link delete">' . esc_html__('Delete', 'lllm') . '</button>';
                echo '</form></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '<form id="lllm-bulk-teams" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('lllm_bulk_delete_teams');
            echo '<input type="hidden" name="action" value="lllm_bulk_delete_teams">';
    

        echo '<p>' . esc_html__('Type DELETE to confirm bulk deletion:', 'lllm') . '</p>';
            echo '<input type="text" name="confirm_text_bulk" class="regular-text"> ';
            submit_button(__('Bulk Delete Selected', 'lllm'), 'delete', 'submit', false);
            echo '</form>';
        }

        $show_franchises_csv_import = false;
        if ($show_franchises_csv_import) {
            $template_url = wp_nonce_url(
                admin_url('admin-post.php?action=lllm_download_teams_template'),
                'lllm_download_teams_template'
            );
            echo '<h2>' . esc_html__('Franchises CSV Import', 'lllm') . '</h2>';
            echo '<p>' . esc_html__('Use the template to validate franchises before importing.', 'lllm') . '</p>';
            echo '<p><a class="button" href="' . esc_url($template_url) . '">' . esc_html__('Download Template', 'lllm') . '</a></p>';
            echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('lllm_validate_teams_csv');
            echo '<input type="hidden" name="action" value="lllm_validate_teams_csv">';
            echo '<input type="file" name="csv_file" accept=".csv" required> ';
            submit_button(__('Validate CSV', 'lllm'), 'secondary', 'submit', false);
            echo '</form>';
            echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('lllm_import_teams_csv');
            echo '<input type="hidden" name="action" value="lllm_import_teams_csv">';
            echo '<input type="file" name="csv_file" accept=".csv" required> ';
            submit_button(__('Import CSV', 'lllm'), 'primary', 'submit', false);
            echo '</form>';
        }

        echo '</div>';
    }

    /**
     * Renders the Teams-by-Division assignment screen.
     *
     * Supports manual assign/remove actions plus CSV validate/import helpers.
     *
     * @return void
     */
    public static function render_division_teams() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        global $wpdb;
        $seasons = self::get_seasons();
        $season_id = isset($_GET['season_id']) ? absint($_GET['season_id']) : 0;
        if (!$season_id && $seasons) {
            $season_id = (int) $seasons[0]->id;
        }

        $divisions = self::get_divisions($season_id);
        $division_id = isset($_GET['division_id']) ? absint($_GET['division_id']) : 0;
        if (!$division_id && $divisions) {
            $division_id = (int) $divisions[0]->id;
        }

        $teams = $wpdb->get_results('SELECT * FROM ' . self::table('team_masters') . ' ORDER BY name ASC');
        $assigned = array();
        if ($division_id) {
            $instances = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT id, team_master_id FROM ' . self::table('team_instances') . ' WHERE division_id = %d',
                    $division_id
                )
            );
            foreach ($instances as $instance) {
                $assigned[(int) $instance->team_master_id] = (int) $instance->id;
            }
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Teams', 'lllm') . '</h1>';
        self::render_notices();

        if (!$seasons) {
            echo '<p>' . esc_html__('Create a season before assigning teams.', 'lllm') . '</p>';
            echo '</div>';
            return;
        }

        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="lllm-division-teams">';
        echo '<label for="lllm-division-season">' . esc_html__('Season', 'lllm') . '</label> ';
        echo '<select id="lllm-division-season" name="season_id" onchange="this.form.submit()">';
        foreach ($seasons as $season) {
            echo '<option value="' . esc_attr($season->id) . '" ' . selected($season_id, $season->id, false) . '>' . esc_html($season->name) . '</option>';
        }
        echo '</select> ';

        echo '<label for="lllm-division-select">' . esc_html__('Division', 'lllm') . '</label> ';
        echo '<select id="lllm-division-select" name="division_id" onchange="this.form.submit()">';
        foreach ($divisions as $division) {
            echo '<option value="' . esc_attr($division->id) . '" ' . selected($division_id, $division->id, false) . '>' . esc_html($division->name) . '</option>';
        }
        echo '</select>';
        echo '</form>';

        if (!$division_id) {
            echo '<p>' . esc_html__('Create a division before assigning teams.', 'lllm') . '</p>';
            echo '</div>';
            return;
        }

        $template_url = wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'lllm_download_division_teams_template',
                    'season_id' => $season_id,
                ),
                admin_url('admin-post.php')
            ),
            'lllm_download_division_teams_template'
        );
        echo '<h2>' . esc_html__('Teams CSV Import', 'lllm') . '</h2>';
        echo '<p>' . esc_html__('Import team assignments for all divisions in this season. Any non-empty value means assigned; leave blank or enter FALSE to unassign.', 'lllm') . '</p>';
        echo '<p><a class="button" href="' . esc_url($template_url) . '">' . esc_html__('Download Template', 'lllm') . '</a></p>';
        echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lllm_import_division_teams_csv');
        echo '<input type="hidden" name="action" value="lllm_import_division_teams_csv">';
        echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';
        echo '<input type="hidden" name="division_id" value="' . esc_attr($division_id) . '">';
        echo '<input type="file" name="csv_file" accept=".csv" required> ';
        submit_button(__('Import CSV', 'lllm'), 'primary', 'submit', false);
        echo '</form>';

        echo '<h2>' . esc_html__('Assign Franchises', 'lllm') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lllm_update_division_teams');
        echo '<input type="hidden" name="action" value="lllm_update_division_teams">';
        echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';
        echo '<input type="hidden" name="division_id" value="' . esc_attr($division_id) . '">';

        if (!$teams) {
            echo '<p>' . esc_html__('No franchises available. Add franchises first.', 'lllm') . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>' . esc_html__('Assigned', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Franchise Name', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Franchise Code', 'lllm') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($teams as $team) {
                $is_assigned = isset($assigned[(int) $team->id]);
                echo '<tr>';
                echo '<td><label><input type="checkbox" name="team_master_ids[]" value="' . esc_attr($team->id) . '" ' . checked(true, $is_assigned, false) . '> ' . esc_html__('Assigned', 'lllm') . '</label></td>';
                echo '<td>' . esc_html($team->name) . '</td>';
                echo '<td>' . esc_html($team->team_code) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '<p>';
        echo '<button class="button button-primary" type="submit" name="lllm_action" value="assign">' . esc_html__('Assign Selected Franchises', 'lllm') . '</button> ';
        echo '<button class="button" type="submit" name="lllm_action" value="remove">' . esc_html__('Remove Selected Franchises', 'lllm') . '</button>';
        echo '</p>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Renders the Games admin screen with quick-edit and import wizard sections.
     *
     * @return void
     */
    public static function render_games() {
        if (!current_user_can('lllm_manage_games')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        $seasons = self::get_seasons();
        $season_id = isset($_GET['season_id']) ? absint($_GET['season_id']) : 0;
        if (!$season_id && $seasons) {
            $season_id = (int) $seasons[0]->id;
        }

        $divisions = self::get_divisions($season_id);
        $division_id = isset($_GET['division_id']) ? absint($_GET['division_id']) : 0;
        if (!$division_id && $divisions) {
            $division_id = (int) $divisions[0]->id;
        }

        global $wpdb;
        $games = array();
        if ($division_id) {
            $games = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT g.*, home.name AS home_name, away.name AS away_name
                     FROM ' . self::table('games') . ' g
                     JOIN ' . self::table('team_instances') . ' hi ON g.home_team_instance_id = hi.id
                     JOIN ' . self::table('team_instances') . ' ai ON g.away_team_instance_id = ai.id
                     JOIN ' . self::table('team_masters') . ' home ON hi.team_master_id = home.id
                     JOIN ' . self::table('team_masters') . ' away ON ai.team_master_id = away.id
                     WHERE g.division_id = %d
                     ORDER BY g.start_datetime_utc ASC',
                    $division_id
                )
            );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Games', 'lllm') . '</h1>';
        self::render_notices();

        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="lllm-games">';
        echo '<label for="lllm-games-season">' . esc_html__('Season', 'lllm') . '</label> ';
        echo '<select id="lllm-games-season" name="season_id" onchange="this.form.submit()">';
        foreach ($seasons as $season) {
            echo '<option value="' . esc_attr($season->id) . '" ' . selected($season_id, $season->id, false) . '>' . esc_html($season->name) . '</option>';
        }
        echo '</select> ';

        echo '<label for="lllm-games-division">' . esc_html__('Division', 'lllm') . '</label> ';
        echo '<select id="lllm-games-division" name="division_id" onchange="this.form.submit()">';
        foreach ($divisions as $division) {
            echo '<option value="' . esc_attr($division->id) . '" ' . selected($division_id, $division->id, false) . '>' . esc_html($division->name) . '</option>';
        }
        echo '</select>';
        echo '</form>';

        if (current_user_can('lllm_import_csv')) {
            self::render_import_wizard_inline($seasons, $divisions, $season_id, $division_id);
        }

        if (!$division_id) {
            echo '<p>' . esc_html__('Select a division to view games.', 'lllm') . '</p>';
            echo '</div>';
            return;
        }

        if (!$games) {
            echo '<p>' . esc_html__('No games yet for this division.', 'lllm') . '</p>';
            echo '<p>' . esc_html__('To add Games, please go to the Import Wizard.', 'lllm') . '</p>';
            echo '</div>';
            return;
        }

        $export_url = wp_nonce_url(
            admin_url('admin-post.php?action=lllm_download_current_games&division_id=' . $division_id),
            'lllm_download_current_games'
        );
        $import_url = admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id . '&step=1');

        echo '<p>';
        echo '<a class="button" href="' . esc_url($export_url) . '">' . esc_html__('Export Current Games CSV', 'lllm') . '</a> ';
        echo '<a class="button button-primary" href="' . esc_url($import_url) . '">' . esc_html__('Import Games', 'lllm') . '</a>';
        echo '</p>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:10px 0;display:flex;gap:8px;align-items:center;">';
        wp_nonce_field('lllm_generate_playoff_bracket');
        echo '<input type="hidden" name="action" value="lllm_generate_playoff_bracket">';
        echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';
        echo '<input type="hidden" name="division_id" value="' . esc_attr($division_id) . '">';
        echo '<button class="button button-secondary" type="submit">' . esc_html__('Generate Playoff Bracket', 'lllm') . '</button>';
        echo '</form>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:0 0 16px;display:flex;gap:8px;align-items:center;">';
        wp_nonce_field('lllm_reset_playoff_bracket');
        echo '<input type="hidden" name="action" value="lllm_reset_playoff_bracket">';
        echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';
        echo '<input type="hidden" name="division_id" value="' . esc_attr($division_id) . '">';
        echo '<button class="button" type="submit">' . esc_html__('Reset Playoff Games', 'lllm') . '</button>';
        echo '<span style="color:#646970;">' . esc_html__('Use reset before regenerating playoff games.', 'lllm') . '</span>';
        echo '</form>';

        echo '<form id="lllm-bulk-games" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lllm_bulk_delete_games');
        wp_nonce_field('lllm_delete_game', 'lllm_delete_game_nonce');
        echo '<input type="hidden" name="action" value="lllm_bulk_delete_games">';
        echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';
        echo '<input type="hidden" name="division_id" value="' . esc_attr($division_id) . '">';
        echo '</form>';

        $site_timezone = wp_timezone();
        $site_timezone_label = wp_timezone_string();
        if (!$site_timezone_label) {
            $site_timezone_label = 'UTC';
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th><input type="checkbox" onclick="document.querySelectorAll(\'.lllm-game-select\').forEach(el => el.checked = this.checked);"></th>';
        echo '<th>' . esc_html(sprintf(__('Date/Time (%s)', 'lllm'), $site_timezone_label)) . '</th>';
        echo '<th>' . esc_html__('Location', 'lllm') . '</th>';
        echo '<th>' . esc_html__('Away', 'lllm') . '</th>';
        echo '<th>' . esc_html__('Home', 'lllm') . '</th>';
        echo '<th>' . esc_html__('Status', 'lllm') . '</th>';
        echo '<th>' . esc_html__('Score', 'lllm') . '</th>';
        echo '<th>' . esc_html__('Quick Edit', 'lllm') . '</th>';
        echo '<th>' . esc_html__('Actions', 'lllm') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($games as $game) {
            $game_type_value = self::get_quick_edit_game_type_value($game);
            $is_generated_playoff = self::is_generated_playoff_game($game);
            $slot_readonly_attr = $is_generated_playoff ? 'readonly aria-readonly="true"' : '';
            $slot_hint = $is_generated_playoff ? __('Slot is locked for generated playoff games.', 'lllm') : '';
            $score = $game->status === 'played' ? sprintf('%d - %d', $game->away_score, $game->home_score) : '—';
            $start_datetime_display = $game->start_datetime_utc;
            if (!empty($game->start_datetime_utc)) {
                $start_datetime = new DateTime($game->start_datetime_utc, new DateTimeZone('UTC'));
                $start_datetime->setTimezone($site_timezone);
                $start_datetime_display = $start_datetime->format('m/d/Y H:i');
            }
            echo '<tr>';
            echo '<td><input class="lllm-game-select" type="checkbox" name="game_ids[]" value="' . esc_attr($game->id) . '" form="lllm-bulk-games"></td>';
            echo '<td>' . esc_html($start_datetime_display) . '</td>';
            echo '<td>' . esc_html($game->location) . '</td>';
            echo '<td>' . esc_html($game->away_name) . '</td>';
            echo '<td>' . esc_html($game->home_name) . '</td>';
            echo '<td>' . esc_html($game->status) . '</td>';
            echo '<td>' . esc_html($score) . '</td>';
            echo '<td>';
            echo '<button class="button lllm-quick-edit-toggle" type="button">' . esc_html__('Edit', 'lllm') . '</button>';
            echo '<form class="lllm-quick-edit-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:none;margin-top:8px;">';
            wp_nonce_field('lllm_quick_edit_game');
            echo '<input type="hidden" name="action" value="lllm_quick_edit_game">';
            echo '<input type="hidden" name="game_id" value="' . esc_attr($game->id) . '">';
            echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';
            echo '<input type="hidden" name="division_id" value="' . esc_attr($division_id) . '">';
            echo '<input type="hidden" name="generated_playoff" value="' . esc_attr($is_generated_playoff ? '1' : '0') . '">';
            echo '<select name="status">';
            foreach (array('scheduled', 'played', 'canceled', 'postponed') as $status) {
                echo '<option value="' . esc_attr($status) . '" ' . selected($game->status, $status, false) . '>' . esc_html($status) . '</option>';
            }
            echo '</select> ';
            echo '<label style="display:inline-flex;align-items:center;gap:4px;">' . esc_html__('Type', 'lllm') . ' ';
            echo '<select name="game_type" class="lllm-game-type">';
            foreach (self::get_quick_edit_game_types() as $type_value => $type_label) {
                echo '<option value="' . esc_attr($type_value) . '" ' . selected($game_type_value, $type_value, false) . '>' . esc_html($type_label) . '</option>';
            }
            echo '</select>';
            echo '</label> ';
            echo '<span class="lllm-playoff-slot-wrap" style="display:' . esc_attr($game_type_value === 'regular' ? 'none' : 'inline-flex') . ';align-items:center;gap:4px;">';
            echo '<label>' . esc_html__('Slot', 'lllm') . ' <input type="number" class="small-text lllm-playoff-slot" name="playoff_slot" min="1" max="4" value="' . esc_attr($game->playoff_slot) . '" ' . $slot_readonly_attr . '></label>';
            if ($slot_hint !== '') {
                echo '<small style="color:#646970;">' . esc_html($slot_hint) . '</small>';
            }
            echo '</span> ';
            echo '<input type="number" class="small-text" name="away_score" value="' . esc_attr($game->away_score) . '" placeholder="Away"> ';
            echo '<input type="number" class="small-text" name="home_score" value="' . esc_attr($game->home_score) . '" placeholder="Home"> ';
            echo '<input type="text" class="regular-text" name="notes" value="' . esc_attr($game->notes) . '" placeholder="' . esc_attr__('Notes', 'lllm') . '"> ';
            echo '<button class="button" type="submit">' . esc_html__('Save', 'lllm') . '</button>';
            echo '<p class="lllm-quick-edit-unsaved" style="display:none;color:#b32d2e;margin:8px 0 0;">' . esc_html__('You have unsaved changes.', 'lllm') . '</p>';
            echo '</form>';
            echo '</td>';
            echo '<td>';
            echo '<input type="text" name="confirm_text[' . esc_attr($game->id) . ']" placeholder="' . esc_attr__('Type DELETE', 'lllm') . '" class="small-text" form="lllm-bulk-games"> ';
            echo '<button class="button-link delete" form="lllm-bulk-games" formaction="' . esc_url(admin_url('admin-post.php?action=lllm_delete_game')) . '" formmethod="post" name="id" value="' . esc_attr($game->id) . '">' . esc_html__('Delete', 'lllm') . '</button>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '<script>';
        echo '(function(){';
        echo 'var toggles=document.querySelectorAll(".lllm-quick-edit-toggle");';
        echo 'toggles.forEach(function(btn){btn.addEventListener("click",function(){var form=btn.parentElement.querySelector(".lllm-quick-edit-form");if(!form){return;}var open=form.style.display!=="none";form.style.display=open?"none":"block";});});';
        echo 'var forms=document.querySelectorAll(".lllm-quick-edit-form");';
        echo 'forms.forEach(function(form){var warning=form.querySelector(".lllm-quick-edit-unsaved");var gameType=form.querySelector("select[name=\"game_type\"]");var slotWrap=form.querySelector(".lllm-playoff-slot-wrap");var slotField=form.querySelector("input[name=\"playoff_slot\"]");var generatedFlag=form.querySelector("input[name=\"generated_playoff\"]");var generatedLocked=generatedFlag&&generatedFlag.value==="1";var syncPlayoffFields=function(){if(!gameType||!slotWrap){return;}var isPlayoff=gameType.value!=="regular";slotWrap.style.display=isPlayoff?"inline-flex":"none";if(slotField&&!isPlayoff){slotField.value="";}};syncPlayoffFields();if(gameType){gameType.addEventListener("change",syncPlayoffFields);}var fields=form.querySelectorAll("select[name=\"status\"], select[name=\"game_type\"], input[name=\"playoff_slot\"], input[name=\"away_score\"], input[name=\"home_score\"], input[name=\"notes\"]");var initial={};fields.forEach(function(field){initial[field.name]=field.value;});var focusedCount=0;var update=function(){if(!warning){return;}var changed=false;fields.forEach(function(field){if(field.value!==initial[field.name]){changed=true;}});warning.style.display=(changed && focusedCount===0)?"block":"none";};fields.forEach(function(field){field.addEventListener("focus",function(){focusedCount++;update();});field.addEventListener("blur",function(){focusedCount=Math.max(0,focusedCount-1);setTimeout(update,0);});field.addEventListener("input",update);field.addEventListener("change",update);});form.addEventListener("submit",function(){if(generatedLocked&&slotField){slotField.value=initial.playoff_slot||slotField.value;}if(warning){warning.style.display="none";}});});';
        echo '})();';
        echo '</script>';


        echo '<p>' . esc_html__('Type DELETE to confirm bulk deletion:', 'lllm') . '</p>';
        echo '<input type="text" name="confirm_text_bulk" class="regular-text" form="lllm-bulk-games"> ';
        echo '<button class="button delete" form="lllm-bulk-games" type="submit">' . esc_html__('Bulk Delete Selected', 'lllm') . '</button>';
        echo '</div>';
    }

    /**
     * Renders the inline import wizard block for the Games screen.
     *
     * @param array<int,object> $seasons     Available season rows.
     * @param array<int,object> $divisions   Available division rows for selected season.
     * @param int               $season_id   Selected season id.
     * @param int               $division_id Selected division id.
     * @return void
     */
    private static function render_import_wizard_inline($seasons, $divisions, $season_id, $division_id) {
        $step = isset($_GET['step']) ? absint($_GET['step']) : 1;
        $import_type = isset($_GET['import_type']) ? sanitize_text_field(wp_unslash($_GET['import_type'])) : 'full';
        $types = LLLM_Import::get_import_types();

        echo '<hr>';
        echo '<h2>' . esc_html__('Import Wizard', 'lllm') . '</h2>';

        if ($step === 1) {
            echo '<form method="get">';
            echo '<input type="hidden" name="page" value="lllm-games">';
            echo '<input type="hidden" name="step" value="2">';
            echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';
            echo '<input type="hidden" name="division_id" value="' . esc_attr($division_id) . '">';

            echo '<h3>' . esc_html__('Choose Import Type', 'lllm') . '</h3>';
            foreach ($types as $key => $label) {
                echo '<label style="display:block;margin:8px 0;">';
                echo '<input type="radio" name="import_type" value="' . esc_attr($key) . '" ' . checked($import_type, $key, false) . '> ';
                echo esc_html($label);
                echo '</label>';
            }

            submit_button(__('Continue', 'lllm'));
            echo '</form>';
            return;
        }

        if ($step === 2) {
            $template_url = wp_nonce_url(
                admin_url('admin-post.php?action=lllm_download_template&import_type=' . $import_type),
                'lllm_download_template'
            );
            echo '<h3>' . esc_html__('Upload CSV', 'lllm') . '</h3>';
            echo '<p>' . esc_html__('CSV must be UTF-8 with headers and date/time format MM/DD/YYYY and HH:MM (24-hour).', 'lllm') . '</p>';
            echo '<p>';
            echo '<a class="button" href="' . esc_url($template_url) . '">' . esc_html__('Download Template', 'lllm') . '</a>';
            echo '</p>';

            echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('lllm_import_validate');
            echo '<input type="hidden" name="action" value="lllm_import_validate">';
            echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';
            echo '<input type="hidden" name="division_id" value="' . esc_attr($division_id) . '">';
            echo '<input type="hidden" name="import_type" value="' . esc_attr($import_type) . '">';
            echo '<input type="file" name="csv_file" accept=".csv" required> ';
            submit_button(__('Validate CSV', 'lllm'), 'primary', 'submit', false);
            echo '</form>';
            return;
        }

        if ($step === 3) {
            $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
            $data = $token ? get_transient('lllm_import_' . $token) : null;
            if (!$data) {
                echo '<p>' . esc_html__('Import data not found. Please re-upload your CSV.', 'lllm') . '</p>';
                return;
            }

            echo '<h3>' . esc_html__('Review Changes', 'lllm') . '</h3>';
            echo '<ul>';
            echo '<li>' . esc_html__('Rows read:', 'lllm') . ' ' . esc_html($data['summary']['rows']) . '</li>';
            echo '<li>' . esc_html__('Creates:', 'lllm') . ' ' . esc_html($data['summary']['creates']) . '</li>';
            echo '<li>' . esc_html__('Updates:', 'lllm') . ' ' . esc_html($data['summary']['updates']) . '</li>';
            echo '<li>' . esc_html__('Unchanged:', 'lllm') . ' ' . esc_html($data['summary']['unchanged']) . '</li>';
            echo '<li>' . esc_html__('Errors:', 'lllm') . ' ' . esc_html($data['summary']['errors']) . '</li>';
            echo '</ul>';

            if (!empty($data['summary']['errors'])) {
                if (!empty($data['error_report_url'])) {
                    echo '<p><a class="button" href="' . esc_url($data['error_report_url']) . '">' . esc_html__('Download Error Report CSV', 'lllm') . '</a></p>';
                    $retry_url = admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id . '&step=2&import_type=' . rawurlencode($import_type));
                    echo '<p><a class="button button-secondary" href="' . esc_url($retry_url) . '">' . esc_html__('Try Again', 'lllm') . '</a></p>';
                }
                echo '<p>' . esc_html__('Fix errors before importing.', 'lllm') . '</p>';
                return;
            }

            if (!empty($data['preview'])) {
                echo '<table class="widefat striped"><thead><tr>';
                foreach (array_keys($data['preview'][0]) as $header) {
                    echo '<th>' . esc_html($header) . '</th>';
                }
                echo '</tr></thead><tbody>';
                foreach ($data['preview'] as $row) {
                    echo '<tr>';
                    foreach ($row as $value) {
                        echo '<td>' . esc_html($value) . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }

            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('lllm_import_commit');
            echo '<input type="hidden" name="action" value="lllm_import_commit">';
            echo '<input type="hidden" name="token" value="' . esc_attr($token) . '">';
            submit_button(__('Import Now', 'lllm'));
            echo '</form>';
        }
    }

    /**
     * Renders the standalone Import Wizard admin page wrapper.
     *
     * @return void
     */
    public static function render_import_wizard() {
        $season_id = isset($_GET['season_id']) ? absint($_GET['season_id']) : 0;
        $division_id = isset($_GET['division_id']) ? absint($_GET['division_id']) : 0;
        $step = isset($_GET['step']) ? absint($_GET['step']) : 1;
        $import_type = isset($_GET['import_type']) ? sanitize_text_field(wp_unslash($_GET['import_type'])) : 'full';

        $target_url = admin_url(
            'admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id . '&step=' . $step . '&import_type=' . rawurlencode($import_type)
        );
        wp_safe_redirect($target_url);
        exit;
    }

    /**
     * Renders the Import Logs screen with filter controls and details table.
     *
     * @return void
     */
    public static function render_import_logs() {
        if (!current_user_can('lllm_view_logs')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        global $wpdb;
        $logs = $wpdb->get_results('SELECT * FROM ' . self::table('import_logs') . ' ORDER BY created_at DESC LIMIT 100');

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Import Logs', 'lllm') . '</h1>';
        if (!$logs) {
            echo '<p>' . esc_html__('No import logs yet.', 'lllm') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Date', 'lllm') . '</th>';
        echo '<th>' . esc_html__('User', 'lllm') . '</th>';
        echo '<th>' . esc_html__('Season', 'lllm') . '</th>';
        echo '<th>' . esc_html__('Division', 'lllm') . '</th>';
        echo '<th>' . esc_html__('Type', 'lllm') . '</th>';
        echo '<th>' . esc_html__('File', 'lllm') . '</th>';
        echo '<th>' . esc_html__('Results', 'lllm') . '</th>';
        echo '<th>' . esc_html__('Errors', 'lllm') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($logs as $log) {
            $user = get_userdata($log->user_id);
            $season = $wpdb->get_var($wpdb->prepare('SELECT name FROM ' . self::table('seasons') . ' WHERE id = %d', $log->season_id));
            $division = $wpdb->get_var($wpdb->prepare('SELECT name FROM ' . self::table('divisions') . ' WHERE id = %d', $log->division_id));
            $error_link = '';
            if ($log->error_report_path) {
                $upload = wp_upload_dir();
                $error_link = str_replace($upload['basedir'], $upload['baseurl'], $log->error_report_path);
            }
            $results = sprintf(
                'C:%d U:%d N:%d',
                $log->total_created,
                $log->total_updated,
                $log->total_unchanged
            );

            echo '<tr>';
            echo '<td>' . esc_html($log->created_at) . '</td>';
            echo '<td>' . esc_html($user ? $user->display_name : __('Unknown', 'lllm')) . '</td>';
            echo '<td>' . esc_html($season) . '</td>';
            echo '<td>' . esc_html($division) . '</td>';
            echo '<td>' . esc_html($log->import_type) . '</td>';
            echo '<td>' . esc_html($log->original_filename) . '</td>';
            echo '<td>' . esc_html($results) . '</td>';
            if ($error_link) {
                echo '<td><a href="' . esc_url($error_link) . '">' . esc_html__('Download', 'lllm') . '</a></td>';
            } else {
                echo '<td>' . esc_html($log->total_errors) . '</td>';
            }
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * Handles create/update submissions for seasons.
     *
     * @return void
     */
    public static function handle_save_season() {
        if (!current_user_can('lllm_manage_seasons')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_save_season');
        global $wpdb;
        $table = self::table('seasons');

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $timezone = isset($_POST['timezone']) ? sanitize_text_field(wp_unslash($_POST['timezone'])) : '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!$name) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-seasons'), 'error', __('Season name is required.', 'lllm'));
        }

        if (!$timezone) {
            $timezone = wp_timezone_string();
        }

        $slug = sanitize_title($name);
        $slug = self::unique_value($table, 'slug', $slug, $id);

        $timestamp = current_time('mysql', true);
        $data = array(
            'name' => $name,
            'slug' => $slug,
            'timezone' => $timezone,
            'is_active' => $is_active,
            'updated_at' => $timestamp,
        );

        if ($id) {
            $wpdb->update($table, $data, array('id' => $id));
        } else {
            $data['created_at'] = $timestamp;
            $wpdb->insert($table, $data);
            $id = (int) $wpdb->insert_id;
        }

        if ($is_active) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$table} SET is_active = 0 WHERE id != %d",
                    $id
                )
            );
        }

        self::redirect_with_notice(admin_url('admin.php?page=lllm-seasons'), 'season_saved');
    }

    /**
     * Handles create/update submissions for divisions.
     *
     * @return void
     */
    public static function handle_save_division() {
        if (!current_user_can('lllm_manage_divisions')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_save_division');
        global $wpdb;
        $table = self::table('divisions');

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';

        if (!$season_id || !$name) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-divisions'), 'error', __('Season and division name are required.', 'lllm'));
        }

        $season_slug = $wpdb->get_var($wpdb->prepare('SELECT slug FROM ' . self::table('seasons') . ' WHERE id = %d', $season_id));
        $base_slug = sanitize_title($season_slug . '-' . $name);
        $slug = self::unique_value($table, 'slug', $base_slug, $id);

        $timestamp = current_time('mysql', true);
        $data = array(
            'season_id' => $season_id,
            'name' => $name,
            'slug' => $slug,
            'updated_at' => $timestamp,
        );

        if ($id) {
            $wpdb->update($table, $data, array('id' => $id));
        } else {
            $data['created_at'] = $timestamp;
            $wpdb->insert($table, $data);
        }

        self::redirect_with_notice(
            admin_url('admin.php?page=lllm-divisions&season_id=' . $season_id),
            'division_saved'
        );
    }

    /**
     * Handles create/update submissions for franchises.
     *
     * @return void
     */
    public static function handle_save_team() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_save_team');
        global $wpdb;
        $table = self::table('team_masters');

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $team_code = isset($_POST['team_code']) ? self::normalize_team_code(sanitize_text_field(wp_unslash($_POST['team_code']))) : '';
        $logo_id = isset($_POST['logo_attachment_id']) ? absint($_POST['logo_attachment_id']) : 0;

        if (!$name) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-franchises'), 'error', __('Franchise name is required.', 'lllm'));
        }

        $slug = sanitize_title($name);
        $slug = self::unique_value($table, 'slug', $slug, $id);

        $can_edit_code = current_user_can('manage_options');
        if (!$can_edit_code || !$team_code) {
            $team_code = self::normalize_team_code($name);
        }
        if (!$team_code) {
            $team_code = 'team';
        }
        $team_code = self::unique_value($table, 'team_code', $team_code, $id);

        $timestamp = current_time('mysql', true);
        $data = array(
            'name' => $name,
            'slug' => $slug,
            'team_code' => $team_code,
            'logo_attachment_id' => $logo_id ?: null,
            'updated_at' => $timestamp,
        );

        if ($id) {
            $wpdb->update($table, $data, array('id' => $id));
        } else {
            $data['created_at'] = $timestamp;
            $wpdb->insert($table, $data);
        }

        self::redirect_with_notice(admin_url('admin.php?page=lllm-franchises'), 'team_saved');
    }

    /**
     * Handles assign/remove actions for teams within a division.
     *
     * @return void
     */
    public static function handle_update_division_teams() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_update_division_teams');
        global $wpdb;

        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $division_id = isset($_POST['division_id']) ? absint($_POST['division_id']) : 0;
        $action = isset($_POST['lllm_action']) ? sanitize_text_field(wp_unslash($_POST['lllm_action'])) : '';
        $team_master_ids = isset($_POST['team_master_ids']) ? array_map('absint', (array) $_POST['team_master_ids']) : array();

        if (!$division_id) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-division-teams'), 'error', __('Division is required.', 'lllm'));
        }

        $team_instances_table = self::table('team_instances');
        $games_table = self::table('games');
        $team_masters_table = self::table('team_masters');

        if ($action === 'assign') {
            foreach ($team_master_ids as $team_master_id) {
                $exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM {$team_instances_table} WHERE division_id = %d AND team_master_id = %d",
                        $division_id,
                        $team_master_id
                    )
                );
                if (!$exists) {
                    $timestamp = current_time('mysql', true);
                    $wpdb->insert(
                        $team_instances_table,
                        array(
                            'division_id' => $division_id,
                            'team_master_id' => $team_master_id,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        )
                    );
                }
            }
        }

        if ($action === 'remove') {
            $blocked = array();
            foreach ($team_master_ids as $team_master_id) {
                $instance_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM {$team_instances_table} WHERE division_id = %d AND team_master_id = %d",
                        $division_id,
                        $team_master_id
                    )
                );
                if (!$instance_id) {
                    continue;
                }

                $game_count = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$games_table} WHERE division_id = %d AND (home_team_instance_id = %d OR away_team_instance_id = %d)",
                        $division_id,
                        $instance_id,
                        $instance_id
                    )
                );

                if ($game_count > 0) {
                    $team_name = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT name FROM {$team_masters_table} WHERE id = %d",
                            $team_master_id
                        )
                    );
                    $blocked[] = $team_name;
                    continue;
                }

                $wpdb->delete(
                    $team_instances_table,
                    array('id' => $instance_id)
                );
            }

            if ($blocked) {
                self::redirect_with_notice(
                    admin_url('admin.php?page=lllm-division-teams&season_id=' . $season_id . '&division_id=' . $division_id),
                    'division_teams_blocked',
                    implode(', ', $blocked)
                );
            }
        }

        self::redirect_with_notice(
            admin_url('admin.php?page=lllm-division-teams&season_id=' . $season_id . '&division_id=' . $division_id),
            'division_teams_updated'
        );
    }

    /**
     * Handles inline quick-edit updates for a single game row.
     *
     * @return void
     */
    public static function handle_quick_edit_game() {
        if (!current_user_can('lllm_manage_games')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_quick_edit_game');
        global $wpdb;

        $game_id = isset($_POST['game_id']) ? absint($_POST['game_id']) : 0;
        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $division_id = isset($_POST['division_id']) ? absint($_POST['division_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'scheduled';
        $home_score_raw = isset($_POST['home_score']) ? trim((string) wp_unslash($_POST['home_score'])) : '';
        $away_score_raw = isset($_POST['away_score']) ? trim((string) wp_unslash($_POST['away_score'])) : '';
        $home_score = $home_score_raw === '' ? null : intval($home_score_raw);
        $away_score = $away_score_raw === '' ? null : intval($away_score_raw);
        $notes = isset($_POST['notes']) ? sanitize_text_field(wp_unslash($_POST['notes'])) : '';
        $game_type = isset($_POST['game_type']) ? sanitize_text_field(wp_unslash($_POST['game_type'])) : 'regular';
        $playoff_slot = isset($_POST['playoff_slot']) ? sanitize_text_field(wp_unslash($_POST['playoff_slot'])) : '';

        $games_table = self::table('games');
        $game_row = null;
        if ($game_id) {
            $game_row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, division_id, competition_type, playoff_round, playoff_slot, source_game_uid_1, source_game_uid_2, notes FROM {$games_table} WHERE id = %d",
                    $game_id
                )
            );
        }

        if (!$game_row) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id), 'error', __('Game not found.', 'lllm'));
        }

        if ($division_id && (int) $game_row->division_id !== $division_id) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id), 'error', __('Invalid game context.', 'lllm'));
        }

        $allowed = array('scheduled', 'played', 'canceled', 'postponed');
        if (!in_array($status, $allowed, true)) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id), 'error', __('Invalid status.', 'lllm'));
        }

        if ($status === 'played') {
            if ($home_score === null || $away_score === null) {
                self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id), 'error', __('Scores are required for played games.', 'lllm'));
            }
        } else {
            if ($home_score !== null || $away_score !== null) {
                $status = 'played';
                if ($home_score === null || $away_score === null) {
                    self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id), 'error', __('Both away and home scores are required when entering scores.', 'lllm'));
                }
            } else {
                $home_score = null;
                $away_score = null;
            }
        }

        $competition_payload = array(
            'competition_type' => 'regular',
            'playoff_round' => null,
            'playoff_slot' => null,
            'source_game_uid_1' => $game_row->source_game_uid_1,
            'source_game_uid_2' => $game_row->source_game_uid_2,
        );

        if ($game_type === 'playoff_r1') {
            $competition_payload['competition_type'] = 'playoff';
            $competition_payload['playoff_round'] = 'r1';
            $competition_payload['playoff_slot'] = $playoff_slot;
        } elseif ($game_type === 'playoff_r2') {
            $competition_payload['competition_type'] = 'playoff';
            $competition_payload['playoff_round'] = 'r2';
            $competition_payload['playoff_slot'] = $playoff_slot;
        } elseif ($game_type === 'playoff_championship') {
            $competition_payload['competition_type'] = 'playoff';
            $competition_payload['playoff_round'] = 'championship';
            $competition_payload['playoff_slot'] = $playoff_slot;
        } elseif ($game_type !== 'regular') {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id), 'error', __('Invalid game type.', 'lllm'));
        }

        $is_generated_playoff = self::is_generated_playoff_game($game_row);
        if ($is_generated_playoff && $competition_payload['competition_type'] === 'playoff') {
            $competition_payload['playoff_round'] = $game_row->playoff_round;
            $competition_payload['playoff_slot'] = $game_row->playoff_slot;
        }

        $competition_validation = self::validate_game_competition_data($competition_payload);
        if (!$competition_validation['valid']) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id), 'error', $competition_validation['error']);
        }

        $competition_data = $competition_validation['data'];

        $wpdb->update(
            $games_table,
            array(
                'status' => $status,
                'home_score' => $home_score,
                'away_score' => $away_score,
                'notes' => $notes,
                'competition_type' => $competition_data['competition_type'],
                'playoff_round' => $competition_data['playoff_round'],
                'playoff_slot' => $competition_data['playoff_slot'],
                'source_game_uid_1' => $competition_data['source_game_uid_1'],
                'source_game_uid_2' => $competition_data['source_game_uid_2'],
                'updated_at' => current_time('mysql', true),
            ),
            array('id' => $game_id)
        );

        if ($division_id) {
            LLLM_Standings::bust_cache($division_id);
        }

        self::redirect_with_notice(
            admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id),
            'game_saved'
        );
    }

    /**
     * Streams the games import CSV template download.
     *
     * @return void
     */
    public static function handle_download_template() {
        if (!current_user_can('lllm_import_csv')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_download_template');
        $type = isset($_GET['import_type']) ? sanitize_text_field(wp_unslash($_GET['import_type'])) : 'full';

        if ($type === 'score') {
            $headers = array('game_uid', 'away_score', 'home_score', 'status', 'notes');
            $filename = 'score-update-template.csv';
        } else {
            $headers = array('game_uid', 'start_date(mm/dd/yyyy)', 'start_time(24HR)', 'location', 'away_team_code', 'home_team_code', 'status', 'away_score', 'home_score', 'notes');
            $filename = 'full-schedule-template.csv';
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        fclose($output);
        exit;
    }

    /**
     * Streams current games as CSV for the selected season/division.
     *
     * @return void
     */
    public static function handle_download_current_games() {
        if (!current_user_can('lllm_manage_games')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_download_current_games');
        $division_id = isset($_GET['division_id']) ? absint($_GET['division_id']) : 0;
        if (!$division_id) {
            wp_die(esc_html__('Division is required.', 'lllm'));
        }

        global $wpdb;
        $games = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT g.*, home.team_code AS home_code, away.team_code AS away_code
                 FROM ' . self::table('games') . ' g
                 JOIN ' . self::table('team_instances') . ' hi ON g.home_team_instance_id = hi.id
                 JOIN ' . self::table('team_instances') . ' ai ON g.away_team_instance_id = ai.id
                 JOIN ' . self::table('team_masters') . ' home ON hi.team_master_id = home.id
                 JOIN ' . self::table('team_masters') . ' away ON ai.team_master_id = away.id
                 WHERE g.division_id = %d
                 ORDER BY g.start_datetime_utc ASC',
                $division_id
            )
        );

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=current-games.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('game_uid', 'start_date(mm/dd/yyyy)', 'start_time(24HR)', 'location', 'away_team_code', 'home_team_code', 'status', 'away_score', 'home_score', 'notes'));
        foreach ($games as $game) {
            $datetime = new DateTime($game->start_datetime_utc, new DateTimeZone('UTC'));
            fputcsv($output, array(
                $game->game_uid,
                $datetime->format('m/d/Y'),
                $datetime->format('H:i'),
                $game->location,
                $game->away_code,
                $game->home_code,
                $game->status,
                $game->away_score,
                $game->home_score,
                $game->notes,
            ));
        }
        fclose($output);
        exit;
    }

    /**
     * Streams the divisions CSV template download.
     *
     * @return void
     */
    public static function handle_download_divisions_template() {
        if (!current_user_can('lllm_manage_divisions')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_download_divisions_template');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=divisions-template.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('division_name'));
        fclose($output);
        exit;
    }

    /**
     * Streams the franchises CSV template download.
     *
     * @return void
     */
    public static function handle_download_teams_template() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_download_teams_template');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=franchises-template.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('franchise_name', 'franchise_code'));
        fclose($output);
        exit;
    }

    /**
     * Exports all franchises as CSV.
     *
     * @return void
     */
    public static function handle_export_franchises_csv() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_export_franchises_csv');
        global $wpdb;
        $franchises = $wpdb->get_results('SELECT team_code, name FROM ' . self::table('team_masters') . ' ORDER BY name ASC');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=franchises.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('franchise_code', 'franchise_name'));
        foreach ($franchises as $franchise) {
            fputcsv($output, array($franchise->team_code, $franchise->name));
        }
        fclose($output);
        exit;
    }

    /**
     * Streams the division-team assignment CSV template.
     *
     * @return void
     */
    public static function handle_download_division_teams_template() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_download_division_teams_template');
        $season_id = isset($_GET['season_id']) ? absint($_GET['season_id']) : 0;
        if (!$season_id) {
            wp_die(esc_html__('Season is required.', 'lllm'));
        }

        global $wpdb;
        $divisions = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT id, name FROM ' . self::table('divisions') . ' WHERE season_id = %d ORDER BY name ASC',
                $season_id
            )
        );
        $teams = $wpdb->get_results('SELECT team_code FROM ' . self::table('team_masters') . ' ORDER BY team_code ASC');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=teams-template.csv');
        $output = fopen('php://output', 'w');

        $headers = array('franchise_code');
        foreach ($divisions as $division) {
            $headers[] = $division->name;
        }
        fputcsv($output, $headers);

        foreach ($teams as $team) {
            $row = array($team->team_code);
            foreach ($divisions as $division) {
                $row[] = '';
            }
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }


    /**
     * Validates uploaded divisions CSV without applying database writes.
     *
     * @return void
     */
    public static function handle_validate_divisions_csv() {
        if (!current_user_can('lllm_manage_divisions')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_validate_divisions_csv');
        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $return_url = admin_url('admin.php?page=lllm-divisions' . ($season_id ? '&season_id=' . $season_id : ''));

        $parsed = self::parse_uploaded_csv();
        if (is_wp_error($parsed)) {
            self::redirect_with_notice($return_url, 'error', $parsed->get_error_message());
        }

        $headers_check = self::validate_csv_headers($parsed, array('division_name'));
        if (is_wp_error($headers_check)) {
            self::redirect_with_notice($return_url, 'error', $headers_check->get_error_message());
        }

        $errors = array();
        foreach ($parsed['rows'] as $index => $row) {
            $row_lower = array_change_key_case($row, CASE_LOWER);
            $name = isset($row_lower['division_name']) ? trim($row_lower['division_name']) : '';
            if ($name === '') {
                $errors[] = $index + 2;
            }
        }

        if ($errors) {
            self::redirect_with_notice(
                $return_url,
                'error',
                sprintf(__('CSV validation failed: %d rows are missing a division name.', 'lllm'), count($errors))
            );
        }

        self::redirect_with_notice(
            $return_url,
            'csv_validated',
            sprintf(__('CSV validated: %d rows checked.', 'lllm'), count($parsed['rows']))
        );
    }

    /**
     * Validates uploaded franchises CSV without applying database writes.
     *
     * @return void
     */
    public static function handle_validate_teams_csv() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_validate_teams_csv');
        $return_url = admin_url('admin.php?page=lllm-franchises');

        $parsed = self::parse_uploaded_csv();
        if (is_wp_error($parsed)) {
            self::redirect_with_notice($return_url, 'error', $parsed->get_error_message());
        }

        $headers_check = self::validate_csv_headers($parsed, array('franchise_name', 'franchise_code'));
        if (is_wp_error($headers_check)) {
            self::redirect_with_notice($return_url, 'error', $headers_check->get_error_message());
        }

        $errors = array();
        $team_codes = array();
        foreach ($parsed['rows'] as $index => $row) {
            $row_lower = array_change_key_case($row, CASE_LOWER);
            $name = isset($row_lower['franchise_name']) ? trim($row_lower['franchise_name']) : '';
            $code = isset($row_lower['franchise_code']) ? self::normalize_team_code(trim($row_lower['franchise_code'])) : '';
            if ($name === '') {
                $errors[] = $index + 2;
                continue;
            }
            if ($code !== '') {
                $code_key = $code;
                if (isset($team_codes[$code_key])) {
                    $errors[] = $index + 2;
                }
                $team_codes[$code_key] = true;
            }
        }

        if ($errors) {
            self::redirect_with_notice(
                $return_url,
                'error',
                sprintf(__('CSV validation failed: %d rows have missing names or duplicate franchise codes.', 'lllm'), count($errors))
            );
        }

        self::redirect_with_notice(
            $return_url,
            'csv_validated',
            sprintf(__('CSV validated: %d rows checked.', 'lllm'), count($parsed['rows']))
        );
    }

    /**
     * Validates uploaded division-team assignment CSV.
     *
     * @return void
     */
    public static function handle_validate_division_teams_csv() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_validate_division_teams_csv');
        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $division_id = isset($_POST['division_id']) ? absint($_POST['division_id']) : 0;
        $return_url = admin_url('admin.php?page=lllm-division-teams&season_id=' . $season_id . '&division_id=' . $division_id);
        if (!$season_id) {
            self::redirect_with_notice($return_url, 'error', __('Season is required.', 'lllm'));
        }

        global $wpdb;
        $divisions = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT id, name FROM ' . self::table('divisions') . ' WHERE season_id = %d ORDER BY name ASC',
                $season_id
            )
        );
        if (!$divisions) {
            self::redirect_with_notice($return_url, 'error', __('No divisions found for this season.', 'lllm'));
        }

        $division_map = array();
        foreach ($divisions as $division) {
            $key = self::normalize_csv_matrix_header($division->name);
            if ($key === '' || isset($division_map[$key])) {
                self::redirect_with_notice($return_url, 'error', __('Division names must be unique for CSV import.', 'lllm'));
            }
            $division_map[$key] = (int) $division->id;
        }

        $parsed = self::parse_uploaded_csv();
        if (is_wp_error($parsed)) {
            self::redirect_with_notice($return_url, 'error', $parsed->get_error_message());
        }

        $headers_check = self::validate_csv_headers($parsed, array('franchise_code'));
        if (is_wp_error($headers_check)) {
            self::redirect_with_notice($return_url, 'error', $headers_check->get_error_message());
        }

        $headers = array_map(array(__CLASS__, 'normalize_csv_matrix_header'), $parsed['headers']);
        $csv_division_headers = array();
        foreach ($headers as $header) {
            if ($header === 'franchise_code') {
                continue;
            }
            if (isset($csv_division_headers[$header])) {
                self::redirect_with_notice($return_url, 'error', __('Duplicate division columns in CSV.', 'lllm'));
            }
            $csv_division_headers[$header] = true;
        }

        $expected_headers = array_keys($division_map);
        $missing_headers = array_diff($expected_headers, array_keys($csv_division_headers));
        if ($missing_headers) {
            self::redirect_with_notice(
                $return_url,
                'error',
                sprintf(__('Missing required division columns: %s', 'lllm'), implode(', ', $missing_headers))
            );
        }

        $unknown_headers = array_diff(array_keys($csv_division_headers), $expected_headers);
        if ($unknown_headers) {
            self::redirect_with_notice(
                $return_url,
                'error',
                sprintf(__('Unknown division columns in CSV: %s', 'lllm'), implode(', ', $unknown_headers))
            );
        }

        $known_codes = $wpdb->get_col('SELECT team_code FROM ' . self::table('team_masters'));
        $known_map = array_fill_keys(array_map('strval', $known_codes), true);

        $errors = array();
        $seen_codes = array();
        foreach ($parsed['rows'] as $index => $row) {
            $row_lower = array_change_key_case($row, CASE_LOWER);
            $code = isset($row_lower['franchise_code']) ? self::normalize_team_code(trim($row_lower['franchise_code'])) : '';
            if ($code === '' || isset($seen_codes[$code]) || !isset($known_map[$code])) {
                $errors[] = $index + 2;
                continue;
            }
            $seen_codes[$code] = true;
        }

        if ($errors) {
            self::redirect_with_notice(
                $return_url,
                'error',
                sprintf(__('CSV validation failed: %d rows have missing, duplicate, or unknown franchise codes.', 'lllm'), count($errors))
            );
        }

        self::redirect_with_notice(
            $return_url,
            'csv_validated',
            sprintf(__('CSV validated: %d rows checked.', 'lllm'), count($parsed['rows']))
        );
    }

    /**
     * Imports divisions from CSV into a season.
     *
     * @return void
     */
    public static function handle_import_divisions_csv() {
        if (!current_user_can('lllm_manage_divisions')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_import_divisions_csv');
        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $return_url = admin_url('admin.php?page=lllm-divisions' . ($season_id ? '&season_id=' . $season_id : ''));
        if (!$season_id) {
            self::redirect_with_notice($return_url, 'error', __('Season is required.', 'lllm'));
        }

        $parsed = self::parse_uploaded_csv();
        if (is_wp_error($parsed)) {
            self::redirect_with_notice($return_url, 'error', $parsed->get_error_message());
        }

        $headers_check = self::validate_csv_headers($parsed, array('division_name'));
        if (is_wp_error($headers_check)) {
            self::redirect_with_notice($return_url, 'error', $headers_check->get_error_message());
        }

        global $wpdb;
        $table = self::table('divisions');
        $season_slug = $wpdb->get_var($wpdb->prepare('SELECT slug FROM ' . self::table('seasons') . ' WHERE id = %d', $season_id));
        $created = 0;
        $skipped = 0;
        foreach ($parsed['rows'] as $row) {
            $row_lower = array_change_key_case($row, CASE_LOWER);
            $name = isset($row_lower['division_name']) ? trim($row_lower['division_name']) : '';
            if ($name === '') {
                $skipped++;
                continue;
            }
            $exists = $wpdb->get_var(
                $wpdb->prepare('SELECT id FROM ' . $table . ' WHERE season_id = %d AND name = %s', $season_id, $name)
            );
            if ($exists) {
                $skipped++;
                continue;
            }
            $base_slug = sanitize_title($season_slug . '-' . $name);
            $slug = self::unique_value($table, 'slug', $base_slug);
            $timestamp = current_time('mysql', true);
            $wpdb->insert(
                $table,
                array(
                    'season_id' => $season_id,
                    'name' => $name,
                    'slug' => $slug,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                )
            );
            $created++;
        }

        self::redirect_with_notice(
            $return_url,
            'import_complete',
            sprintf(__('Divisions imported: %d created, %d skipped.', 'lllm'), $created, $skipped)
        );
    }

    /**
     * Imports franchises from CSV.
     *
     * @return void
     */
    public static function handle_import_teams_csv() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_import_teams_csv');
        $return_url = admin_url('admin.php?page=lllm-franchises');

        $parsed = self::parse_uploaded_csv();
        if (is_wp_error($parsed)) {
            self::redirect_with_notice($return_url, 'error', $parsed->get_error_message());
        }

        $headers_check = self::validate_csv_headers($parsed, array('franchise_name', 'franchise_code'));
        if (is_wp_error($headers_check)) {
            self::redirect_with_notice($return_url, 'error', $headers_check->get_error_message());
        }

        global $wpdb;
        $table = self::table('team_masters');
        $created = 0;
        $updated = 0;
        $skipped = 0;
        foreach ($parsed['rows'] as $row) {
            $row_lower = array_change_key_case($row, CASE_LOWER);
            $name = isset($row_lower['franchise_name']) ? trim($row_lower['franchise_name']) : '';
            $code = isset($row_lower['franchise_code']) ? self::normalize_team_code(trim($row_lower['franchise_code'])) : '';
            if ($name === '') {
                $skipped++;
                continue;
            }
            if ($code === '') {
                $code = self::normalize_team_code($name);
            }
            if ($code === '') {
                $code = 'team';
            }
            $existing = $wpdb->get_row($wpdb->prepare('SELECT id, team_code FROM ' . $table . ' WHERE team_code = %s', $code));
            $slug = sanitize_title($name);
            if ($existing) {
                $slug = self::unique_value($table, 'slug', $slug, (int) $existing->id);
                $wpdb->update(
                    $table,
                    array(
                        'name' => $name,
                        'slug' => $slug,
                        'updated_at' => current_time('mysql', true),
                    ),
                    array('id' => (int) $existing->id)
                );
                $updated++;
                continue;
            }
            $code = self::unique_value($table, 'team_code', $code);
            $slug = self::unique_value($table, 'slug', $slug);
            $timestamp = current_time('mysql', true);
            $wpdb->insert(
                $table,
                array(
                    'name' => $name,
                    'slug' => $slug,
                    'team_code' => $code,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                )
            );
            $created++;
        }

        self::redirect_with_notice(
            $return_url,
            'import_complete',
            sprintf(__('Franchises imported: %d created, %d updated, %d skipped.', 'lllm'), $created, $updated, $skipped)
        );
    }

    /**
     * Imports division-team assignments from CSV.
     *
     * @return void
     */
    public static function handle_import_division_teams_csv() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_import_division_teams_csv');
        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $division_id = isset($_POST['division_id']) ? absint($_POST['division_id']) : 0;
        $return_url = admin_url('admin.php?page=lllm-division-teams&season_id=' . $season_id . '&division_id=' . $division_id);
        if (!$season_id) {
            self::redirect_with_notice($return_url, 'error', __('Season is required.', 'lllm'));
        }

        global $wpdb;
        $divisions = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT id, name FROM ' . self::table('divisions') . ' WHERE season_id = %d ORDER BY name ASC',
                $season_id
            )
        );
        if (!$divisions) {
            self::redirect_with_notice($return_url, 'error', __('No divisions found for this season.', 'lllm'));
        }

        $division_map = array();
        foreach ($divisions as $division) {
            $key = self::normalize_csv_matrix_header($division->name);
            if ($key === '' || isset($division_map[$key])) {
                self::redirect_with_notice($return_url, 'error', __('Division names must be unique for CSV import.', 'lllm'));
            }
            $division_map[$key] = (int) $division->id;
        }

        $parsed = self::parse_uploaded_csv();
        if (is_wp_error($parsed)) {
            self::redirect_with_notice($return_url, 'error', $parsed->get_error_message());
        }

        $headers_check = self::validate_csv_headers($parsed, array('franchise_code'));
        if (is_wp_error($headers_check)) {
            self::redirect_with_notice($return_url, 'error', $headers_check->get_error_message());
        }

        $headers = array_map(array(__CLASS__, 'normalize_csv_matrix_header'), $parsed['headers']);
        $csv_division_headers = array();
        foreach ($headers as $header) {
            if ($header === 'franchise_code') {
                continue;
            }
            $csv_division_headers[$header] = true;
        }

        $expected_headers = array_keys($division_map);
        $missing_headers = array_diff($expected_headers, array_keys($csv_division_headers));
        if ($missing_headers) {
            self::redirect_with_notice(
                $return_url,
                'error',
                sprintf(__('Missing required division columns: %s', 'lllm'), implode(', ', $missing_headers))
            );
        }

        $unknown_headers = array_diff(array_keys($csv_division_headers), $expected_headers);
        if ($unknown_headers) {
            self::redirect_with_notice(
                $return_url,
                'error',
                sprintf(__('Unknown division columns in CSV: %s', 'lllm'), implode(', ', $unknown_headers))
            );
        }

        $team_rows = $wpdb->get_results('SELECT id, team_code FROM ' . self::table('team_masters'));
        $team_map = array();
        foreach ($team_rows as $team_row) {
            $team_map[$team_row->team_code] = (int) $team_row->id;
        }

        $division_ids = array_values($division_map);
        $placeholders = implode(',', array_fill(0, count($division_ids), '%d'));
        $existing_rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT id, division_id, team_master_id FROM ' . self::table('team_instances') . " WHERE division_id IN ($placeholders)",
                $division_ids
            )
        );
        $existing = array();
        foreach ($existing_rows as $existing_row) {
            $mapped_division_id = (int) $existing_row->division_id;
            $mapped_team_id = (int) $existing_row->team_master_id;
            if (!isset($existing[$mapped_division_id])) {
                $existing[$mapped_division_id] = array();
            }
            $existing[$mapped_division_id][$mapped_team_id] = (int) $existing_row->id;
        }

        $desired = array();
        $seen_codes = array();
        $skipped = 0;
        foreach ($parsed['rows'] as $row) {
            $row_lower = array_change_key_case($row, CASE_LOWER);
            $code = isset($row_lower['franchise_code']) ? self::normalize_team_code(trim($row_lower['franchise_code'])) : '';
            if ($code === '' || isset($seen_codes[$code]) || !isset($team_map[$code])) {
                $skipped++;
                continue;
            }
            $seen_codes[$code] = true;
            $team_id = (int) $team_map[$code];

            foreach ($division_map as $division_header => $mapped_division_id) {
                $cell_value = isset($row_lower[$division_header]) ? $row_lower[$division_header] : '';
                if (self::matrix_cell_is_unassigned($cell_value)) {
                    continue;
                }

                if (!isset($desired[$mapped_division_id])) {
                    $desired[$mapped_division_id] = array();
                }
                $desired[$mapped_division_id][$team_id] = true;
            }
        }

        $created = 0;
        $removed = 0;
        $blocked = 0;
        $touched_divisions = array();
        $timestamp = current_time('mysql', true);

        foreach ($division_ids as $mapped_division_id) {
            $existing_for_division = isset($existing[$mapped_division_id]) ? $existing[$mapped_division_id] : array();
            $desired_for_division = isset($desired[$mapped_division_id]) ? $desired[$mapped_division_id] : array();

            foreach ($desired_for_division as $team_id => $assigned_true) {
                if (isset($existing_for_division[$team_id])) {
                    continue;
                }

                $wpdb->insert(
                    self::table('team_instances'),
                    array(
                        'division_id' => $mapped_division_id,
                        'team_master_id' => $team_id,
                        'display_name' => null,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    )
                );
                $created++;
                $touched_divisions[$mapped_division_id] = true;
            }

            foreach ($existing_for_division as $team_id => $instance_id) {
                if (isset($desired_for_division[$team_id])) {
                    continue;
                }

                $game_count = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        'SELECT COUNT(*) FROM ' . self::table('games') . ' WHERE division_id = %d AND (home_team_instance_id = %d OR away_team_instance_id = %d)',
                        $mapped_division_id,
                        $instance_id,
                        $instance_id
                    )
                );
                if ($game_count > 0) {
                    $blocked++;
                    continue;
                }

                $wpdb->delete(self::table('team_instances'), array('id' => $instance_id));
                $removed++;
                $touched_divisions[$mapped_division_id] = true;
            }
        }

        foreach (array_keys($touched_divisions) as $touched_division_id) {
            LLLM_Standings::bust_cache((int) $touched_division_id);
        }

        self::redirect_with_notice(
            $return_url,
            'import_complete',
            sprintf(
                __('Teams import synced: %d assigned, %d unassigned, %d blocked, %d skipped.', 'lllm'),
                $created,
                $removed,
                $blocked,
                $skipped
            )
        );
    }


    /**
     * Persists an import-log entry for audit/history reporting.
     *
     * @param array<string,mixed> $data Import log payload.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return void
     */
    private static function log_import($data) {
        global $wpdb;
        $wpdb->insert(
            self::table('import_logs'),
            array(
                'user_id' => get_current_user_id(),
                'season_id' => $data['season_id'],
                'division_id' => $data['division_id'],
                'import_type' => $data['import_type'],
                'original_filename' => $data['filename'],
                'total_rows' => $data['total_rows'],
                'total_created' => $data['total_created'],
                'total_updated' => $data['total_updated'],
                'total_unchanged' => $data['total_unchanged'],
                'total_errors' => $data['total_errors'],
                'error_report_path' => $data['error_report_path'],
                'created_at' => current_time('mysql', true),
            )
        );
    }

    /**
     * Builds a normalized team-code to team-instance-id map for a division.
     *
     * @param int $division_id Division primary key.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return array<string,int> Team code map.
     */
    private static function build_team_map($division_id) {
        global $wpdb;
        $map = array();
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT tm.team_code, ti.id AS instance_id
                 FROM ' . self::table('team_instances') . ' ti
                 JOIN ' . self::table('team_masters') . ' tm ON ti.team_master_id = tm.id
                 WHERE ti.division_id = %d',
                $division_id
            )
        );
        foreach ($rows as $row) {
            $map[$row->team_code] = (int) $row->instance_id;
        }
        return $map;
    }

    /**
     * Validates import rows and builds create/update operations for commit.
     *
     * @param int                 $season_id   Season primary key.
     * @param int                 $division_id Division primary key.
     * @param string              $import_type Import type key (`full` or `score`).
     * @param array<int,array>    $rows        Parsed CSV rows.
     * @return array{operations: array<int,array<string,mixed>>, errors: array<int,array<string,mixed>>}|WP_Error
     *         Planned operations/errors payload or error.
     */
    private static function validate_import($season_id, $division_id, $import_type, $rows) {
        global $wpdb;
        $errors = array();
        $operations = array();
        $team_map = self::build_team_map($division_id);
        $timezone = LLLM_Import::get_season_timezone($season_id);
        $allowed_status = array('scheduled', 'played', 'canceled', 'postponed');
        $seen_keys = array();

        foreach ($rows as $index => $row) {
            $row_number = $index + 2;
            $status = isset($row['status']) ? strtolower(trim($row['status'])) : '';

            if ($import_type === 'score') {
                if ($status === '') {
                    $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: status is required for score updates.', 'lllm'), $row_number));
                    continue;
                }
                if (!in_array($status, $allowed_status, true)) {
                    $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: invalid status.', 'lllm'), $row_number));
                    continue;
                }

                $game_uid = isset($row['game_uid']) ? self::normalize_game_uid(trim($row['game_uid'])) : '';
                if (!$game_uid) {
                    $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: game_uid is required.', 'lllm'), $row_number));
                    continue;
                }

                $game = $wpdb->get_row(
                    $wpdb->prepare(
                        'SELECT * FROM ' . self::table('games') . ' WHERE game_uid = %s AND division_id = %d',
                        $game_uid,
                        $division_id
                    )
                );
                if (!$game) {
                    $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: game_uid not found.', 'lllm'), $row_number));
                    continue;
                }

                $home_score = $row['home_score'] !== '' ? intval($row['home_score']) : null;
                $away_score = $row['away_score'] !== '' ? intval($row['away_score']) : null;
                if ($status === 'played' && ($home_score === null || $away_score === null)) {
                    $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: scores required for played games.', 'lllm'), $row_number));
                    continue;
                }
                if ($status !== 'played' && ($row['home_score'] !== '' || $row['away_score'] !== '')) {
                    $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: scores must be blank unless played.', 'lllm'), $row_number));
                    continue;
                }

                $operations[] = array(
                    'action' => 'update',
                    'game_id' => (int) $game->id,
                    'data' => array(
                        'status' => $status,
                        'home_score' => $home_score,
                        'away_score' => $away_score,
                        'notes' => isset($row['notes']) ? sanitize_text_field($row['notes']) : '',
                    ),
                );
                continue;
            }

            $start_date = isset($row['start_date']) ? trim($row['start_date']) : '';
            $start_time = isset($row['start_time']) ? trim($row['start_time']) : '';
            $location = isset($row['location']) ? trim($row['location']) : '';
            $home_code = isset($row['home_team_code']) ? trim($row['home_team_code']) : '';
            $away_code = isset($row['away_team_code']) ? trim($row['away_team_code']) : '';
            if ($status === '') {
                $status = 'scheduled';
            }
            if (!in_array($status, $allowed_status, true)) {
                $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: invalid status.', 'lllm'), $row_number));
                continue;
            }

            if (!$start_date || !$start_time || !$location || !$home_code || !$away_code) {
                $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: required fields missing.', 'lllm'), $row_number));
                continue;
            }

            if (!isset($team_map[$home_code]) || !isset($team_map[$away_code])) {
                $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: team codes must be assigned to this division.', 'lllm'), $row_number));
                continue;
            }

            if ($home_code === $away_code) {
                $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: home and away teams must differ.', 'lllm'), $row_number));
                continue;
            }

            $datetime_utc = LLLM_Import::parse_datetime_to_utc($start_date . ' ' . $start_time, $timezone);
            if (!$datetime_utc) {
                $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: invalid datetime format.', 'lllm'), $row_number));
                continue;
            }

            $home_score = $row['home_score'] !== '' ? intval($row['home_score']) : null;
            $away_score = $row['away_score'] !== '' ? intval($row['away_score']) : null;
            if ($status === 'played' && ($home_score === null || $away_score === null)) {
                $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: scores required for played games.', 'lllm'), $row_number));
                continue;
            }
            if ($status !== 'played' && ($row['home_score'] !== '' || $row['away_score'] !== '')) {
                $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: scores must be blank unless played.', 'lllm'), $row_number));
                continue;
            }

            $key = $datetime_utc . '|' . $home_code . '|' . $away_code;
            if (isset($seen_keys[$key])) {
                $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d duplicates another row.', 'lllm'), $row_number));
                continue;
            }
            $seen_keys[$key] = true;

            $game_uid = isset($row['game_uid']) ? self::normalize_game_uid(trim($row['game_uid'])) : '';
            $existing = null;
            if ($game_uid) {
                $existing = $wpdb->get_row(
                    $wpdb->prepare(
                        'SELECT * FROM ' . self::table('games') . ' WHERE game_uid = %s AND division_id = %d',
                        $game_uid,
                        $division_id
                    )
                );
                if (!$existing) {
                    $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: game_uid not found for this division.', 'lllm'), $row_number));
                    continue;
                }
            } else {
                $existing = $wpdb->get_row(
                    $wpdb->prepare(
                        'SELECT * FROM ' . self::table('games') . ' WHERE division_id = %d AND start_datetime_utc = %s AND home_team_instance_id = %d AND away_team_instance_id = %d',
                        $division_id,
                        $datetime_utc,
                        $team_map[$home_code],
                        $team_map[$away_code]
                    )
                );
            }

            $data = array(
                'division_id' => $division_id,
                'home_team_instance_id' => $team_map[$home_code],
                'away_team_instance_id' => $team_map[$away_code],
                'location' => $location,
                'start_datetime_utc' => $datetime_utc,
                'status' => $status,
                'home_score' => $home_score,
                'away_score' => $away_score,
                'notes' => isset($row['notes']) ? sanitize_text_field($row['notes']) : '',
                'competition_type' => isset($row['competition_type']) ? $row['competition_type'] : 'regular',
                'playoff_round' => isset($row['playoff_round']) ? $row['playoff_round'] : '',
                'playoff_slot' => isset($row['playoff_slot']) ? $row['playoff_slot'] : '',
                'source_game_uid_1' => isset($row['source_game_uid_1']) ? $row['source_game_uid_1'] : '',
                'source_game_uid_2' => isset($row['source_game_uid_2']) ? $row['source_game_uid_2'] : '',
            );

            $competition_validation = self::validate_game_competition_data($data);
            if (!$competition_validation['valid']) {
                $errors[] = array('row' => $row_number, 'message' => sprintf(__('Row %d: %s', 'lllm'), $row_number, $competition_validation['error']));
                continue;
            }
            $data = $competition_validation['data'];

            if ($existing) {
                $operations[] = array(
                    'action' => 'update',
                    'game_id' => (int) $existing->id,
                    'data' => $data,
                );
            } else {
                $operations[] = array(
                    'action' => 'create',
                    'data' => $data,
                );
            }
        }

        return array(
            'errors' => $errors,
            'operations' => $operations,
        );
    }

    /**
     * Handles import wizard validation (step 2 -> review payload).
     *
     * Stores validation results in a transient token for later commit.
     *
     * @return void
     */
    public static function handle_import_validate() {
        if (!current_user_can('lllm_import_csv')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_import_validate');

        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $division_id = isset($_POST['division_id']) ? absint($_POST['division_id']) : 0;
        $import_type = isset($_POST['import_type']) ? sanitize_text_field(wp_unslash($_POST['import_type'])) : 'full';

        if (empty($_FILES['csv_file']['tmp_name'])) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id . '&step=2&import_type=' . rawurlencode($import_type)), 'error', __('CSV file is required.', 'lllm'));
        }

        $parsed = LLLM_Import::parse_csv($_FILES['csv_file']['tmp_name']);
        if (is_wp_error($parsed)) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id . '&step=2&import_type=' . rawurlencode($import_type)), 'error', $parsed->get_error_message());
        }

        $required_headers = $import_type === 'score'
            ? array('game_uid', 'away_score', 'home_score', 'status')
            : array('start_date', 'start_time', 'location', 'away_team_code', 'home_team_code');
        foreach ($required_headers as $header) {
            if (!in_array($header, $parsed['headers'], true)) {
                self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id . '&step=2&import_type=' . rawurlencode($import_type)), 'error', sprintf(__('Missing required header: %s', 'lllm'), $header));
            }
        }

        $validation = self::validate_import($season_id, $division_id, $import_type, $parsed['rows']);
        $operations = $validation['operations'];
        $errors = $validation['errors'];

        $summary = array(
            'rows' => count($parsed['rows']),
            'creates' => count(array_filter($operations, function ($op) { return $op['action'] === 'create'; })),
            'updates' => count(array_filter($operations, function ($op) { return $op['action'] === 'update'; })),
            'unchanged' => 0,
            'errors' => count($errors),
        );

        $error_report_path = '';
        $error_report_url = '';
        if ($errors) {
            $error_report_path = LLLM_Import::save_error_report($errors);
            if ($error_report_path) {
                $upload = wp_upload_dir();
                $error_report_url = str_replace($upload['basedir'], $upload['baseurl'], $error_report_path);
            }
        }

        self::log_import(array(
            'season_id' => $season_id,
            'division_id' => $division_id,
            'import_type' => $import_type,
            'filename' => sanitize_file_name($_FILES['csv_file']['name']),
            'total_rows' => $summary['rows'],
            'total_created' => $summary['creates'],
            'total_updated' => $summary['updates'],
            'total_unchanged' => $summary['unchanged'],
            'total_errors' => $summary['errors'],
            'error_report_path' => $error_report_path,
        ));

        $token = wp_generate_password(12, false);
        set_transient('lllm_import_' . $token, array(
            'season_id' => $season_id,
            'division_id' => $division_id,
            'import_type' => $import_type,
            'operations' => $operations,
            'summary' => $summary,
            'preview' => array_slice($parsed['rows'], 0, 20),
            'error_report_url' => $error_report_url,
        ), 30 * MINUTE_IN_SECONDS);

        wp_safe_redirect(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id . '&step=3&token=' . $token));
        exit;
    }

    /**
     * Commits a previously-validated import token to the database.
     *
     * @return void
     */
    public static function handle_import_commit() {
        if (!current_user_can('lllm_import_csv')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_import_commit');
        $token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
        $data = $token ? get_transient('lllm_import_' . $token) : null;
        if (!$data) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-games&step=1'), 'error', __('Import session expired.', 'lllm'));
        }

        $operations = $data['operations'];
        $season_id = $data['season_id'];
        $division_id = $data['division_id'];
        $import_type = $data['import_type'];

        global $wpdb;
        $created = 0;
        $updated = 0;

        $wpdb->query('START TRANSACTION');
        foreach ($operations as $operation) {
            if ($operation['action'] === 'create') {
                $create_result = self::create_game_record($operation['data']);
                if (!$create_result['ok']) {
                    $wpdb->query('ROLLBACK');
                    self::redirect_with_notice(
                        admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id),
                        'error',
                        $create_result['error']
                    );
                }
                $created++;
            } elseif ($operation['action'] === 'update') {
                $payload = $operation['data'];
                $payload['updated_at'] = current_time('mysql', true);
                $wpdb->update(self::table('games'), $payload, array('id' => $operation['game_id']));
                $updated++;
            }
        }
        $wpdb->query('COMMIT');

        if ($division_id) {
            LLLM_Standings::bust_cache($division_id);
        }

        self::log_import(array(
            'season_id' => $season_id,
            'division_id' => $division_id,
            'import_type' => $import_type,
            'filename' => __('Commit', 'lllm'),
            'total_rows' => count($operations),
            'total_created' => $created,
            'total_updated' => $updated,
            'total_unchanged' => 0,
            'total_errors' => 0,
            'error_report_path' => '',
        ));

        delete_transient('lllm_import_' . $token);
        self::redirect_with_notice(
            admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id),
            'import_complete'
        );
    }

    /**
     * Generates a seeded playoff bracket for the selected division.
     *
     * @return void
     */
    public static function handle_generate_playoff_bracket() {
        if (!current_user_can('lllm_manage_games')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_generate_playoff_bracket');
        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $division_id = isset($_POST['division_id']) ? absint($_POST['division_id']) : 0;
        $redirect_url = admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id);

        if (!$division_id) {
            self::redirect_with_notice($redirect_url, 'error', __('Division is required for playoff generation.', 'lllm'));
        }

        if (self::division_has_playoff_games($division_id)) {
            self::redirect_with_notice($redirect_url, 'error', __('Playoff games already exist. Use Reset Playoff Games before generating again.', 'lllm'));
        }

        $standings = LLLM_Standings::get_standings($division_id);
        if (count($standings) < 6) {
            self::redirect_with_notice($redirect_url, 'error', __('Playoff bracket generation requires at least 6 teams in official standings order.', 'lllm'));
        }

        // Seed map is fixed for a 6-team bracket and follows standings rank 1..6.
        $seed_ids = array_map(
            'intval',
            array_column(array_slice($standings, 0, 6), 'team_instance_id')
        );

        $base_datetime = gmdate('Y-m-d 17:00:00', strtotime('+1 day'));
        // Slot map defines the generated bracket skeleton and feeder linkage targets.
        $slot_payloads = array(
            'r1_g1' => array(
                'competition_type' => 'playoff',
                'playoff_round' => 'r1',
                'playoff_slot' => '1',
                'home_team_instance_id' => $seed_ids[2],
                'away_team_instance_id' => $seed_ids[5],
                'location' => __('TBD', 'lllm'),
                'start_datetime_utc' => $base_datetime,
                'status' => 'scheduled',
            ),
            'r1_g2' => array(
                'competition_type' => 'playoff',
                'playoff_round' => 'r1',
                'playoff_slot' => '2',
                'home_team_instance_id' => $seed_ids[3],
                'away_team_instance_id' => $seed_ids[4],
                'location' => __('TBD', 'lllm'),
                'start_datetime_utc' => gmdate('Y-m-d 17:00:00', strtotime($base_datetime . ' +1 day')),
                'status' => 'scheduled',
            ),
            'r2_g1' => array(
                'competition_type' => 'playoff',
                'playoff_round' => 'r2',
                'playoff_slot' => '1',
                'home_team_instance_id' => $seed_ids[0],
                'away_team_instance_id' => $seed_ids[3],
                'location' => __('TBD', 'lllm'),
                'start_datetime_utc' => gmdate('Y-m-d 17:00:00', strtotime($base_datetime . ' +3 day')),
                'status' => 'scheduled',
            ),
            'r2_g2' => array(
                'competition_type' => 'playoff',
                'playoff_round' => 'r2',
                'playoff_slot' => '2',
                'home_team_instance_id' => $seed_ids[1],
                'away_team_instance_id' => $seed_ids[2],
                'location' => __('TBD', 'lllm'),
                'start_datetime_utc' => gmdate('Y-m-d 17:00:00', strtotime($base_datetime . ' +4 day')),
                'status' => 'scheduled',
            ),
            'ch_g1' => array(
                'competition_type' => 'playoff',
                'playoff_round' => 'championship',
                'playoff_slot' => '1',
                'home_team_instance_id' => $seed_ids[0],
                'away_team_instance_id' => $seed_ids[1],
                'location' => __('TBD', 'lllm'),
                'start_datetime_utc' => gmdate('Y-m-d 17:00:00', strtotime($base_datetime . ' +6 day')),
                'status' => 'scheduled',
            ),
        );

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        foreach ($slot_payloads as $slot => &$payload) {
            $payload['division_id'] = $division_id;
            $payload['home_score'] = null;
            $payload['away_score'] = null;
            $payload['notes'] = $slot;
        }
        unset($payload);

        $r1_g1 = self::create_game_record($slot_payloads['r1_g1']);
        $r1_g2 = self::create_game_record($slot_payloads['r1_g2']);
        if (!$r1_g1['ok'] || !$r1_g2['ok']) {
            $wpdb->query('ROLLBACK');
            self::redirect_with_notice($redirect_url, 'error', $r1_g1['error'] ? $r1_g1['error'] : $r1_g2['error']);
        }

        $slot_payloads['r2_g1']['source_game_uid_1'] = $r1_g2['uid'];
        $slot_payloads['r2_g1']['source_game_uid_2'] = null;
        $slot_payloads['r2_g2']['source_game_uid_1'] = $r1_g1['uid'];
        $slot_payloads['r2_g2']['source_game_uid_2'] = null;

        $r2_g1 = self::create_game_record($slot_payloads['r2_g1']);
        $r2_g2 = self::create_game_record($slot_payloads['r2_g2']);
        if (!$r2_g1['ok'] || !$r2_g2['ok']) {
            $wpdb->query('ROLLBACK');
            self::redirect_with_notice($redirect_url, 'error', $r2_g1['error'] ? $r2_g1['error'] : $r2_g2['error']);
        }

        $slot_payloads['ch_g1']['source_game_uid_1'] = $r2_g1['uid'];
        $slot_payloads['ch_g1']['source_game_uid_2'] = $r2_g2['uid'];
        $ch_g1 = self::create_game_record($slot_payloads['ch_g1']);
        if (!$ch_g1['ok']) {
            $wpdb->query('ROLLBACK');
            self::redirect_with_notice($redirect_url, 'error', $ch_g1['error']);
        }

        $wpdb->query('COMMIT');
        LLLM_Standings::bust_cache($division_id);
        self::redirect_with_notice($redirect_url, 'playoff_generated');
    }

    /**
     * Deletes generated playoff games for a selected division.
     *
     * @return void
     */
    public static function handle_reset_playoff_bracket() {
        if (!current_user_can('lllm_manage_games')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_reset_playoff_bracket');
        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $division_id = isset($_POST['division_id']) ? absint($_POST['division_id']) : 0;
        $redirect_url = admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id);

        if (!$division_id) {
            self::redirect_with_notice($redirect_url, 'error', __('Division is required for playoff reset.', 'lllm'));
        }

        global $wpdb;
        $wpdb->delete(
            self::table('games'),
            array(
                'division_id' => $division_id,
                'competition_type' => 'playoff',
            ),
            array('%d', '%s')
        );

        LLLM_Standings::bust_cache($division_id);
        self::redirect_with_notice($redirect_url, 'playoff_reset');
    }

    /**
     * Checks whether delete confirmation text matches required sentinel value.
     *
     * @param string $text User-entered confirmation text.
     * @return bool True when text equals `DELETE` case-insensitively.
     */
    private static function is_delete_confirmed($text) {
        return strtoupper(trim($text)) === 'DELETE';
    }

    /**
     * Reads per-row delete confirmation text from request payload.
     *
     * @param int $id Entity id used as confirmation input key.
     * @return string Sanitized confirmation text.
     */
    private static function get_confirm_text($id) {
        if (!isset($_POST['confirm_text'])) {
            return '';
        }
        $confirm = $_POST['confirm_text'];
        if (is_array($confirm) && isset($confirm[$id])) {
            return sanitize_text_field(wp_unslash($confirm[$id]));
        }
        if (is_string($confirm)) {
            return sanitize_text_field(wp_unslash($confirm));
        }
        return '';
    }

    /**
     * Deletes a season row by id.
     *
     * @param int $season_id Season primary key.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return void
     */
    private static function delete_season_by_id($season_id) {
        global $wpdb;
        $division_ids = $wpdb->get_col($wpdb->prepare('SELECT id FROM ' . self::table('divisions') . ' WHERE season_id = %d', $season_id));
        if ($division_ids) {
            $placeholders = implode(',', array_fill(0, count($division_ids), '%d'));
            $wpdb->query($wpdb->prepare('DELETE FROM ' . self::table('games') . ' WHERE division_id IN (' . $placeholders . ')', $division_ids));
            $wpdb->query($wpdb->prepare('DELETE FROM ' . self::table('team_instances') . ' WHERE division_id IN (' . $placeholders . ')', $division_ids));
            $wpdb->query($wpdb->prepare('DELETE FROM ' . self::table('divisions') . ' WHERE id IN (' . $placeholders . ')', $division_ids));
        }
        $wpdb->delete(self::table('seasons'), array('id' => $season_id));
    }

    /**
     * Handles single-season deletion with confirmation enforcement.
     *
     * @return void
     */
    public static function handle_delete_season() {
        if (!current_user_can('lllm_manage_seasons')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_delete_season', 'lllm_delete_season_nonce');
        $season_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $confirm = self::get_confirm_text($season_id);
        if (!$season_id || !self::is_delete_confirmed($confirm)) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-seasons'), 'delete_blocked', __('Confirmation required.', 'lllm'));
        }

        self::delete_season_by_id($season_id);

        self::redirect_with_notice(admin_url('admin.php?page=lllm-seasons'), 'season_deleted');
    }

    /**
     * Handles bulk season deletion with shared confirmation text.
     *
     * @return void
     */
    public static function handle_bulk_delete_seasons() {
        if (!current_user_can('lllm_manage_seasons')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_bulk_delete_seasons');
        $confirm = isset($_POST['confirm_text_bulk']) ? sanitize_text_field(wp_unslash($_POST['confirm_text_bulk'])) : '';
        $season_ids = isset($_POST['season_ids']) ? array_map('absint', (array) $_POST['season_ids']) : array();
        if (!$season_ids || !self::is_delete_confirmed($confirm)) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-seasons'), 'delete_blocked', __('Confirmation required.', 'lllm'));
        }

        foreach ($season_ids as $season_id) {
            self::delete_season_by_id($season_id);
        }

        self::redirect_with_notice(admin_url('admin.php?page=lllm-seasons'), 'season_deleted');
    }

    /**
     * Deletes a division row by id.
     *
     * @param int $division_id Division primary key.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return void
     */
    private static function delete_division_by_id($division_id) {
        global $wpdb;
        $wpdb->delete(self::table('games'), array('division_id' => $division_id));
        $wpdb->delete(self::table('team_instances'), array('division_id' => $division_id));
        $wpdb->delete(self::table('divisions'), array('id' => $division_id));
    }

    /**
     * Handles single-division deletion and cache busting.
     *
     * @return void
     */
    public static function handle_delete_division() {
        if (!current_user_can('lllm_manage_divisions')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_delete_division', 'lllm_delete_division_nonce');
        $division_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $confirm = self::get_confirm_text($division_id);
        if (!$division_id || !self::is_delete_confirmed($confirm)) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-divisions&season_id=' . $season_id), 'delete_blocked', __('Confirmation required.', 'lllm'));
        }

        self::delete_division_by_id($division_id);

        LLLM_Standings::bust_cache($division_id);

        self::redirect_with_notice(admin_url('admin.php?page=lllm-divisions&season_id=' . $season_id), 'division_deleted');
    }

    /**
     * Handles bulk division deletion and standings cache invalidation.
     *
     * @return void
     */
    public static function handle_bulk_delete_divisions() {
        if (!current_user_can('lllm_manage_divisions')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_bulk_delete_divisions');
        $confirm = isset($_POST['confirm_text_bulk']) ? sanitize_text_field(wp_unslash($_POST['confirm_text_bulk'])) : '';
        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $division_ids = isset($_POST['division_ids']) ? array_map('absint', (array) $_POST['division_ids']) : array();
        if (!$division_ids || !self::is_delete_confirmed($confirm)) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-divisions&season_id=' . $season_id), 'delete_blocked', __('Confirmation required.', 'lllm'));
        }

        foreach ($division_ids as $division_id) {
            self::delete_division_by_id($division_id);
            LLLM_Standings::bust_cache($division_id);
        }

        self::redirect_with_notice(admin_url('admin.php?page=lllm-divisions&season_id=' . $season_id), 'division_deleted');
    }

    /**
     * Deletes a franchise row by id.
     *
     * @param int $team_id Franchise primary key.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return void
     */
    private static function delete_team_by_id($team_id) {
        global $wpdb;
        $wpdb->delete(self::table('team_masters'), array('id' => $team_id));
    }

    /**
     * Handles single-franchise deletion with assignment safety checks.
     *
     * @return void
     */
    public static function handle_delete_team() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_delete_team', 'lllm_delete_team_nonce');
        $team_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $confirm = self::get_confirm_text($team_id);
        if (!$team_id || !self::is_delete_confirmed($confirm)) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-franchises'), 'delete_blocked', __('Confirmation required.', 'lllm'));
        }

        global $wpdb;
        $in_use = $wpdb->get_var(
            $wpdb->prepare('SELECT id FROM ' . self::table('team_instances') . ' WHERE team_master_id = %d', $team_id)
        );
        if ($in_use) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-franchises'), 'delete_blocked', __('Franchise is assigned to a division.', 'lllm'));
        }

        self::delete_team_by_id($team_id);
        self::redirect_with_notice(admin_url('admin.php?page=lllm-franchises'), 'team_deleted');
    }

    /**
     * Handles bulk-franchise deletion, skipping rows still assigned to divisions.
     *
     * @return void
     */
    public static function handle_bulk_delete_teams() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_bulk_delete_teams');
        global $wpdb;
        $confirm = isset($_POST['confirm_text_bulk']) ? sanitize_text_field(wp_unslash($_POST['confirm_text_bulk'])) : '';
        $team_ids = isset($_POST['team_ids']) ? array_map('absint', (array) $_POST['team_ids']) : array();
        if (!$team_ids || !self::is_delete_confirmed($confirm)) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-franchises'), 'delete_blocked', __('Confirmation required.', 'lllm'));
        }

        foreach ($team_ids as $team_id) {
            $in_use = $wpdb->get_var(
                $wpdb->prepare('SELECT id FROM ' . self::table('team_instances') . ' WHERE team_master_id = %d', $team_id)
            );
            if ($in_use) {
                continue;
            }
            self::delete_team_by_id($team_id);
        }

        self::redirect_with_notice(admin_url('admin.php?page=lllm-franchises'), 'team_deleted');
    }

    /**
     * Deletes a game row by id.
     *
     * @param int $game_id Game primary key.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return void
     */
    private static function delete_game_by_id($game_id) {
        global $wpdb;
        $wpdb->delete(self::table('games'), array('id' => $game_id));
    }

    /**
     * Handles single-game deletion and standings cache busting.
     *
     * @return void
     */
    public static function handle_delete_game() {
        if (!current_user_can('lllm_manage_games')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_delete_game', 'lllm_delete_game_nonce');
        $game_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $division_id = isset($_POST['division_id']) ? absint($_POST['division_id']) : 0;
        $confirm = self::get_confirm_text($game_id);
        if (!$game_id || !self::is_delete_confirmed($confirm)) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id), 'delete_blocked', __('Confirmation required.', 'lllm'));
        }

        self::delete_game_by_id($game_id);

        if ($division_id) {
            LLLM_Standings::bust_cache($division_id);
        }

        self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id), 'game_deleted');
    }

    /**
     * Handles bulk-game deletion and standings cache busting.
     *
     * @return void
     */
    public static function handle_bulk_delete_games() {
        if (!current_user_can('lllm_manage_games')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_bulk_delete_games');
        $confirm = isset($_POST['confirm_text_bulk']) ? sanitize_text_field(wp_unslash($_POST['confirm_text_bulk'])) : '';
        $season_id = isset($_POST['season_id']) ? absint($_POST['season_id']) : 0;
        $division_id = isset($_POST['division_id']) ? absint($_POST['division_id']) : 0;
        $game_ids = isset($_POST['game_ids']) ? array_map('absint', (array) $_POST['game_ids']) : array();
        if (!$game_ids || !self::is_delete_confirmed($confirm)) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id), 'delete_blocked', __('Confirmation required.', 'lllm'));
        }

        foreach ($game_ids as $game_id) {
            self::delete_game_by_id($game_id);
        }

        if ($division_id) {
            LLLM_Standings::bust_cache($division_id);
        }

        self::redirect_with_notice(admin_url('admin.php?page=lllm-games&season_id=' . $season_id . '&division_id=' . $division_id), 'game_deleted');
    }
}
