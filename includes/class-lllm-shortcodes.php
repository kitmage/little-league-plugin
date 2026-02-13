<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLLM_Shortcodes {
    /**
     * Registers public-facing shortcodes.
     *
     * @return void
     */
    public static function register() {
        add_shortcode('lllm_schedule', array(__CLASS__, 'render_schedule'));
        add_shortcode('lllm_standings', array(__CLASS__, 'render_standings'));
        add_shortcode('lllm_teams', array(__CLASS__, 'render_teams'));
        add_shortcode('lllm_playoff_bracket', array(__CLASS__, 'render_playoff_bracket'));
    }

    /**
     * Builds a fallback label for feeder-based playoff teams.
     *
     * @param string               $source_game_uid Source game UID.
     * @param array<string,object> $games_by_uid    Game rows indexed by UID.
     * @return string Human-readable placeholder label.
     */
    private static function get_playoff_placeholder_label($source_game_uid, $games_by_uid) {
        $source_game_uid = (string) $source_game_uid;
        if ($source_game_uid === '') {
            return __('TBD', 'lllm');
        }

        if (!empty($games_by_uid[$source_game_uid])) {
            $source_game = $games_by_uid[$source_game_uid];
            $round_code = isset($source_game->playoff_round) ? (string) $source_game->playoff_round : '';
            $slot = isset($source_game->playoff_slot) ? (string) $source_game->playoff_slot : '';

            // Keep feeder placeholders readable without exposing raw UIDs.
            $round_label_map = array(
                'r1' => __('R1', 'lllm'),
                'r2' => __('R2', 'lllm'),
                'championship' => __('Championship', 'lllm'),
            );

            if (isset($round_label_map[$round_code]) && $slot !== '') {
                /* translators: 1: playoff round label, 2: playoff game slot. */
                $source_label = sprintf(__('Game %1$s-%2$s', 'lllm'), $round_label_map[$round_code], $slot);
            } else {
                $source_label = $source_game_uid;
            }
        } else {
            $source_label = $source_game_uid;
        }

        /* translators: %s: source game label. */
        return sprintf(__('Winner of %s', 'lllm'), $source_label);
    }

    /**
     * Resolves the display label for a playoff game side, including feeder placeholders.
     *
     * @param object               $game         Game row.
     * @param string               $side         Side key (`home` or `away`).
     * @param array<string,object> $games_by_uid Game rows indexed by UID.
     * @return string Team label for display.
     */
    private static function get_playoff_team_label($game, $side, $games_by_uid) {
        $team_name_field = $side . '_name';
        $source_uid_field = $side === 'home' ? 'source_game_uid_1' : 'source_game_uid_2';
        $source_game_uid = isset($game->{$source_uid_field}) ? (string) $game->{$source_uid_field} : '';
        $team_name = isset($game->{$team_name_field}) ? (string) $game->{$team_name_field} : '';

        if ($source_game_uid === '') {
            return $team_name;
        }

        if (!empty($games_by_uid[$source_game_uid])) {
            $source_game = $games_by_uid[$source_game_uid];
            $home_score = isset($source_game->home_score) ? $source_game->home_score : null;
            $away_score = isset($source_game->away_score) ? $source_game->away_score : null;
            $is_played = isset($source_game->status) && $source_game->status === 'played';

            if ($is_played && $home_score !== null && $away_score !== null && (int) $home_score !== (int) $away_score) {
                return (int) $home_score > (int) $away_score ? (string) $source_game->home_name : (string) $source_game->away_name;
            }
        }

        return self::get_playoff_placeholder_label($source_game_uid, $games_by_uid);
    }

    /**
     * Fetches a season row by slug.
     *
     * @param string $slug Season slug.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return object|null Season row or null when not found.
     */
    private static function get_season_by_slug($slug) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'lllm_seasons WHERE slug = %s',
                $slug
            )
        );
    }

    /**
     * Fetches a division row by slug.
     *
     * @param string $slug Division slug.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return object|null Division row or null when not found.
     */
    private static function get_division_by_slug($slug) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'lllm_divisions WHERE slug = %s',
                $slug
            )
        );
    }

    /**
     * Returns the latest active season.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return object|null Season row or null when no active season exists.
     */
    private static function get_active_season() {
        global $wpdb;
        return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'lllm_seasons WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
    }

    /**
     * Returns the first alphabetical division for a season.
     *
     * @param int $season_id Season primary key.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return object|null Division row or null when none exist.
     */
    private static function get_first_division($season_id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'lllm_divisions WHERE season_id = %d ORDER BY name ASC LIMIT 1',
                $season_id
            )
        );
    }

    /**
     * Sanitizes shortcode slug/code style inputs.
     *
     * @param string $value Raw shortcode attribute value.
     * @return string Sanitized value limited to letters, numbers, dashes, and underscores.
     */
    private static function sanitize_shortcode_token($value) {
        return preg_replace('/[^A-Za-z0-9_-]/', '', trim((string) $value));
    }

    /**
     * Builds a standardized shortcode heading block.
     *
     * @param object $season Season row.
     * @param object $division Division row.
     * @param string $suffix Heading suffix, e.g. Schedule.
     * @return string HTML heading.
     */
    private static function render_context_heading($season, $division, $suffix) {
        $parts = array();
        $parts[] = sprintf(__('%s', 'lllm'), (string) $season->name);
        $parts[] = sprintf(__('%s', 'lllm'), (string) $division->name);
        $parts[] = $suffix;
        return '<h2 class="lllm-shortcode-heading">' . esc_html(implode(' ', $parts)) . '</h2>';
    }

    /**
     * Renders date content split into day, date, and time spans.
     *
     * @param DateTime $dt Localized date instance.
     * @return string HTML date value.
     */
    private static function render_date_parts($dt) {
        return '<span class="day">' . esc_html($dt->format('l')) . '</span> '
            . '<span class="date">' . esc_html($dt->format('n/j/y')) . '</span> '
            . '<span class="time">' . esc_html($dt->format('g:i a')) . '</span>';
    }

    /**
     * Renders a team label with optional logo.
     *
     * @param string $name Team name text.
     * @param int    $logo_attachment_id Logo attachment id.
     * @return string HTML team display.
     */
    private static function render_team_with_logo($name, $logo_attachment_id) {
        $output = '';
        if ($logo_attachment_id > 0) {
            $output .= wp_get_attachment_image($logo_attachment_id, 'thumbnail', false, array('class' => 'lllm-team-logo'));
        }

        return $output . '<span class="lllm-team-name">' . esc_html($name) . '</span>';
    }
	
	 /**
     * Renders a team logo.
     *
     * @param string $name Team name text.
     * @param int    $logo_attachment_id Logo attachment id.
     * @return string HTML team display.
     */
    private static function render_team_logo($name, $logo_attachment_id) {
        $output = '';
        if ($logo_attachment_id > 0) {
            $output .= wp_get_attachment_image($logo_attachment_id, 'thumbnail', false, array('class' => 'lllm-team-logo'));
        }

        return $output;
    }
	
    /**
     * Resolves season/division context for shortcode rendering.
     *
     * Resolution order:
     * 1) Explicit `season` / `division` shortcode attributes (slug-based).
     * 2) Active season fallback.
     * 3) First division in resolved season fallback.
     *
     * @param array<string,string> $atts Parsed shortcode attributes.
     * @return array{0: object|null, 1: object|null} Season and division rows.
     */
    private static function resolve_context($atts) {
        $season = null;
        $division = null;

        $season_slug = self::sanitize_shortcode_token(isset($atts['season']) ? $atts['season'] : '');
        if ($season_slug !== '') {
            $season = self::get_season_by_slug($season_slug);
        }
        if (!$season) {
            $season = self::get_active_season();
        }

        $division_slug = self::sanitize_shortcode_token(isset($atts['division']) ? $atts['division'] : '');
        if ($division_slug !== '') {
            $division = self::get_division_by_slug($division_slug);
        }

        if (!$division && $season) {
            $division = self::get_first_division($season->id);
        }

        return array($season, $division);
    }

    /**
     * Renders the public schedule table shortcode output.
     *
     * Supported attributes:
     * - `season`, `division` (slug filters)
     * - `team_code` (limits to games involving the team)
     * - `show_past`, `show_future` (`1`/`0` flags)
     * - `limit` (max rows)
     *
     * @param array<string,string> $atts Shortcode attributes.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return string HTML output.
     */
    public static function render_schedule($atts) {
        $atts = shortcode_atts(
            array(
                'season' => '',
                'division' => '',
                'team_code' => '',
                'show_past' => '1',
                'show_future' => '1',
                'limit' => '50',
            ),
            $atts,
            'lllm_schedule'
        );

        list($season, $division) = self::resolve_context($atts);
        if (!$season || !$division) {
            return '<p>' . esc_html__('Schedule is not available yet.', 'lllm') . '</p>';
        }

        $timezone = $season->timezone ? $season->timezone : wp_timezone_string();
        $atts['team_code'] = self::sanitize_shortcode_token($atts['team_code']);
        $show_past = $atts['show_past'] === '1';
        $show_future = $atts['show_future'] === '1';
        $limit = max(1, intval($atts['limit']));

        global $wpdb;
        $filters = array('g.division_id = %d');
        $params = array($division->id);

        if ($atts['team_code']) {
            $filters[] = '(home.team_code = %s OR away.team_code = %s)';
            $params[] = $atts['team_code'];
            $params[] = $atts['team_code'];
        }

        if (!$show_past) {
            $filters[] = 'g.start_datetime_utc >= %s';
            $params[] = gmdate('Y-m-d H:i:s');
        }

        if (!$show_future) {
            $filters[] = 'g.start_datetime_utc <= %s';
            $params[] = gmdate('Y-m-d H:i:s');
        }

        $where = implode(' AND ', $filters);
        $sql = 'SELECT g.*, home.name AS home_name, away.name AS away_name, home.logo_attachment_id AS home_logo_attachment_id, away.logo_attachment_id AS away_logo_attachment_id
                FROM ' . $wpdb->prefix . 'lllm_games g
                JOIN ' . $wpdb->prefix . 'lllm_team_instances hi ON g.home_team_instance_id = hi.id
                JOIN ' . $wpdb->prefix . 'lllm_team_instances ai ON g.away_team_instance_id = ai.id
                JOIN ' . $wpdb->prefix . 'lllm_team_masters home ON hi.team_master_id = home.id
                JOIN ' . $wpdb->prefix . 'lllm_team_masters away ON ai.team_master_id = away.id
                WHERE ' . $where . '
                ORDER BY g.start_datetime_utc ASC
                LIMIT %d';
        $params[] = $limit;
        $prepared = $wpdb->prepare($sql, $params);
        $games = $wpdb->get_results($prepared);

        if (!$games) {
            return '<p>' . esc_html__('No games scheduled.', 'lllm') . '</p>';
        }

        $output = self::render_context_heading($season, $division, __('Schedule', 'lllm'));
        $output .= '<table class="lllm-schedule"><thead><tr>';
        $output .= '<th class="date-time">' . esc_html__('Date/Time', 'lllm') . '</th>';
        $output .= '<th class="location">' . esc_html__('Location', 'lllm') . '</th>';
        $output .= '<th class="home">' . esc_html__('Home', 'lllm') . '</th>';
        $output .= '<th class="away">' . esc_html__('Away', 'lllm') . '</th>';
        $output .= '<th class="status">' . esc_html__('Status', 'lllm') . '</th>';
        $output .= '<th class="score">' . esc_html__('Score', 'lllm') . '</th>';
        $output .= '</tr></thead><tbody>';

        foreach ($games as $game) {
            $dt = new DateTime($game->start_datetime_utc, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone($timezone));
            $status = esc_html($game->status);
            $score = '—';
            if ($game->status === 'played') {
                $score = esc_html($game->home_score . ' - ' . $game->away_score);
            }

            $first_col = self::render_team_logo((string) $game->home_name, (int) $game->home_logo_attachment_id);
            $first_col .= self::render_team_logo((string) $game->away_name, (int) $game->away_logo_attachment_id);
            $first_col .= self::render_date_parts($dt);

            $output .= '<tr>';
            $output .= '<td class="date-time">' . $first_col . '</td>';
            $output .= '<td class="location">' . esc_html($game->location) . '</td>';
            $output .= '<td class="home">' . /*self::render_team_with_logo((string)*/ $game->home_name/*, (int) $game->home_logo_attachment_id)*/ . '</td>';
            $output .= '<td class="away">' . /*self::render_team_with_logo((string)*/ $game->away_name/*, (int) $game->away_logo_attachment_id)*/ . '</td>';
            $output .= '<td class="status">' . $status . '</td>';
            $output .= '<td class="score">' . $score . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody></table>';
        $output .= '<p class="lllm-updated">' . esc_html__('Last updated:', 'lllm') . ' ' . esc_html(wp_date('Y-m-d H:i', time(), new DateTimeZone($timezone))) . '</p>';

        return $output;
    }

    /**
     * Renders the standings table shortcode output.
     *
     * Supported attributes:
     * - `season`, `division` (slug filters)
     *
     * @param array<string,string> $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function render_standings($atts) {
        $atts = shortcode_atts(
            array(
                'season' => '',
                'division' => '',
            ),
            $atts,
            'lllm_standings'
        );

        list($season, $division) = self::resolve_context($atts);
        if (!$season || !$division) {
            return '<p>' . esc_html__('Standings are not available yet.', 'lllm') . '</p>';
        }

        $timezone = $season->timezone ? $season->timezone : wp_timezone_string();
        $standings = LLLM_Standings::get_standings($division->id);

        if (!$standings) {
            return '<p>' . esc_html__('No standings available.', 'lllm') . '</p>';
        }

        $output = self::render_context_heading($season, $division, __('Standings', 'lllm'));
        $output .= '<table class="lllm-standings"><thead><tr>';
        $output .= '<th class="team">' . esc_html__('Team', 'lllm') . '</th>';
        $output .= '<th class="gp">' . esc_html__('GP', 'lllm') . '</th>';
        $output .= '<th class="w">' . esc_html__('W', 'lllm') . '</th>';
        $output .= '<th class="l">' . esc_html__('L', 'lllm') . '</th>';
        $output .= '<th class="t">' . esc_html__('T', 'lllm') . '</th>';
        $output .= '<th class="rf">' . esc_html__('RF', 'lllm') . '</th>';
        $output .= '<th class="ra">' . esc_html__('RA', 'lllm') . '</th>';
        $output .= '<th class="rd">' . esc_html__('RD', 'lllm') . '</th>';
        $output .= '<th class="win-pct">' . esc_html__('Win%', 'lllm') . '</th>';
        $output .= '</tr></thead><tbody>';

        foreach ($standings as $row) {
            $team_logo = isset($row['logo_attachment_id']) ? (int) $row['logo_attachment_id'] : 0;
            $output .= '<tr>';
            $output .= '<td class="team">' . self::render_team_with_logo((string) $row['team_name'], $team_logo) . '</td>';
            $output .= '<td class="gp">' . esc_html($row['gp']) . '</td>';
            $output .= '<td class="w">' . esc_html($row['wins']) . '</td>';
            $output .= '<td class="l">' . esc_html($row['losses']) . '</td>';
            $output .= '<td class="t">' . esc_html($row['ties']) . '</td>';
            $output .= '<td class="rf">' . esc_html($row['rf']) . '</td>';
            $output .= '<td class="ra">' . esc_html($row['ra']) . '</td>';
            $output .= '<td class="rd">' . esc_html($row['rd']) . '</td>';
            $output .= '<td class="win-pct">' . esc_html(number_format($row['win_pct'], 3)) . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody></table>';
        $output .= '<p class="lllm-updated">' . esc_html__('Last updated:', 'lllm') . ' ' . esc_html(wp_date('Y-m-d H:i', time(), new DateTimeZone($timezone))) . '</p>';

        return $output;
    }

    /**
     * Renders the teams list shortcode output.
     *
     * Supported attributes:
     * - `season`, `division` (slug filters)
     * - `show_logos` (`1`/`0` flag)
     *
     * @param array<string,string> $atts Shortcode attributes.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return string HTML output.
     */
    public static function render_teams($atts) {
        $atts = shortcode_atts(
            array(
                'season' => '',
                'division' => '',
                'show_logos' => '0',
            ),
            $atts,
            'lllm_teams'
        );

        list($season, $division) = self::resolve_context($atts);
        if (!$season || !$division) {
            return '<p>' . esc_html__('Teams are not available yet.', 'lllm') . '</p>';
        }

        global $wpdb;
        $teams = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT tm.name, tm.team_code, tm.logo_attachment_id
                 FROM ' . $wpdb->prefix . 'lllm_team_instances ti
                 JOIN ' . $wpdb->prefix . 'lllm_team_masters tm ON ti.team_master_id = tm.id
                 WHERE ti.division_id = %d
                 ORDER BY tm.name ASC',
                $division->id
            )
        );

        if (!$teams) {
            return '<p>' . esc_html__('No teams assigned.', 'lllm') . '</p>';
        }

        $show_logos = $atts['show_logos'] === '1';
        $output = self::render_context_heading($season, $division, __('Teams', 'lllm'));
        $output .= '<ul class="lllm-teams">';
        foreach ($teams as $team) {
            $output .= '<li>';
            if ($show_logos && $team->logo_attachment_id) {
                $output .= wp_get_attachment_image((int) $team->logo_attachment_id, 'thumbnail', false, array('class' => 'lllm-team-logo'));
            }
            $output .= esc_html($team->name);
            $output .= '</li>';
        }
        $output .= '</ul>';

        return $output;
    }

    /**
     * Renders the playoff bracket shortcode output.
     *
     * Supported attributes:
     * - `season`, `division` (slug filters)
     *
     * @param array<string,string> $atts Shortcode attributes.
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return string HTML output.
     */
    public static function render_playoff_bracket($atts) {
        $atts = shortcode_atts(
            array(
                'season' => '',
                'division' => '',
            ),
            $atts,
            'lllm_playoff_bracket'
        );

        list($season, $division) = self::resolve_context($atts);
        if (!$season || !$division) {
            return '<p>' . esc_html__('Playoff bracket is not available yet.', 'lllm') . '</p>';
        }

        global $wpdb;
        $games = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT g.*, home.name AS home_name, away.name AS away_name, home.logo_attachment_id AS home_logo_attachment_id, away.logo_attachment_id AS away_logo_attachment_id
                 FROM ' . $wpdb->prefix . 'lllm_games g
                 JOIN ' . $wpdb->prefix . 'lllm_team_instances hi ON g.home_team_instance_id = hi.id
                 JOIN ' . $wpdb->prefix . 'lllm_team_instances ai ON g.away_team_instance_id = ai.id
                 JOIN ' . $wpdb->prefix . 'lllm_team_masters home ON hi.team_master_id = home.id
                 JOIN ' . $wpdb->prefix . 'lllm_team_masters away ON ai.team_master_id = away.id
                 WHERE g.division_id = %d
                   AND g.competition_type = %s
                 ORDER BY FIELD(g.playoff_round, %s, %s, %s), CAST(g.playoff_slot AS UNSIGNED) ASC',
                $division->id,
                'playoff',
                'r1',
                'r2',
                'championship'
            )
        );

        if (!$games) {
            return '<p>' . esc_html__('No playoff bracket available.', 'lllm') . '</p>';
        }

        $games_by_uid = array();
        // Initialize every supported round so rendering stays deterministic.
        $games_by_round = array(
            'r1' => array(),
            'r2' => array(),
            'championship' => array(),
        );

        foreach ($games as $game) {
            $games_by_uid[$game->game_uid] = $game;
            if (isset($games_by_round[$game->playoff_round])) {
                $games_by_round[$game->playoff_round][] = $game;
            }
        }

        $round_labels = array(
            'r1' => __('Round 1', 'lllm'),
            'r2' => __('Round 2', 'lllm'),
            'championship' => __('Championship', 'lllm'),
        );

        $output = self::render_context_heading($season, $division, __('Playoff Bracket', 'lllm'));
        $output .= '<div class="lllm-playoff-bracket">';
        // Round order matches bracket progression from opening round to final.
        foreach (array('r1', 'r2', 'championship') as $round_code) {
            $output .= '<section class="lllm-playoff-round lllm-playoff-round-' . esc_attr($round_code) . '">';
            $output .= '<h3>' . esc_html($round_labels[$round_code]) . '</h3>';

            if (empty($games_by_round[$round_code])) {
                $output .= '<p>' . esc_html__('No games in this round.', 'lllm') . '</p>';
                $output .= '</section>';
                continue;
            }

            $output .= '<table><thead><tr>';
            $output .= '<th class="game">' . esc_html__('Game', 'lllm') . '</th>';
            $output .= '<th class="home">' . esc_html__('Home', 'lllm') . '</th>';
            $output .= '<th class="away">' . esc_html__('Away', 'lllm') . '</th>';
            $output .= '<th class="status">' . esc_html__('Status', 'lllm') . '</th>';
            $output .= '<th class="score">' . esc_html__('Score', 'lllm') . '</th>';
            $output .= '</tr></thead><tbody>';

            foreach ($games_by_round[$round_code] as $game) {
                $home_label = self::get_playoff_team_label($game, 'home', $games_by_uid);
                $away_label = self::get_playoff_team_label($game, 'away', $games_by_uid);
                $score = '—';
                if ($game->status === 'played') {
                    $score = $game->home_score . ' - ' . $game->away_score;
                }

                /* translators: %s: playoff game slot number. */
                $game_label = sprintf(__('Game %s', 'lllm'), (string) $game->playoff_slot);

                $first_col = self::render_team_logo($home_label, (int) $game->home_logo_attachment_id);
                $first_col .= self::render_team_logo($away_label, (int) $game->away_logo_attachment_id);
                $first_col .= '<span class="lllm-game-label">' . esc_html($game_label) . '</span>';

                $output .= '<tr>';
                $output .= '<td class="game">' . $first_col . '</td>';
                $output .= '<td class="home">' . self::render_team_logo($home_label, (int) $game->home_logo_attachment_id) . '</td>';
                $output .= '<td class="away">' . self::render_team_logo($away_label, (int) $game->away_logo_attachment_id) . '</td>';
                $output .= '<td class="status">' . esc_html((string) $game->status) . '</td>';
                $output .= '<td class="score">' . esc_html($score) . '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody></table>';
            $output .= '</section>';
        }
        $output .= '</div>';

        return $output;
    }
}
