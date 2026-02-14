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
        add_filter('pre_do_shortcode_tag', array(__CLASS__, 'maybe_render_deprecated_shortcode'), 10, 4);
    }

    /**
     * Handles deprecated shortcode tags with migration guidance.
     *
     * @param string|false $output Short-circuit output from previous callback.
     * @param string       $tag Shortcode tag.
     * @param array        $attr Parsed shortcode attributes.
     * @param array        $m Full regex match array.
     * @return string|false
     */
    public static function maybe_render_deprecated_shortcode($output, $tag, $attr, $m) {
        if ($tag !== 'lllm_playoff_bracket') {
            return $output;
        }

        return self::render_deprecated_playoff_shortcode(is_array($attr) ? $attr : array());
    }

    /**
     * Soft-aliases legacy playoff shortcode usage to schedule type=playoff output.
     *
     * @param array<string,string> $atts Legacy shortcode attributes.
     * @return string
     */
    private static function render_deprecated_playoff_shortcode($atts) {
        _deprecated_function(
            'lllm_playoff_bracket shortcode',
            '1.1.0',
            '[lllm_schedule type="playoff"]'
        );

        $message = '';
        if (current_user_can('manage_options')) {
            $message = '<p class="lllm-shortcode-deprecation">'
                . esc_html__('Deprecated shortcode: [lllm_playoff_bracket] now aliases to [lllm_schedule type="playoff"]. Please migrate existing content.', 'lllm')
                . '</p>';
        }

        $atts = is_array($atts) ? $atts : array();
        $atts['type'] = 'playoff';
        return $message . self::render_schedule($atts);
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
    private static function render_context_heading($season, $division, $suffix, $context = '') {
        $parts = array();
        $parts[] = sprintf(__('%s', 'lllm'), (string) $season->name);
        $parts[] = sprintf(__('%s', 'lllm'), (string) $division->name);
        if ($context !== '') {
            $parts[] = $context;
        }
        $parts[] = $suffix;
        return '<h2 class="lllm-shortcode-heading">' . esc_html(implode(' / ', $parts)) . '</h2>';
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
     * Renders a team logo/name line with score on the next line.
     *
     * @param string     $team_name Team name text.
     * @param int        $logo_attachment_id Logo attachment id.
     * @param int|string $score Team score value.
     * @return string HTML team/score block.
     */
    private static function render_team_logo_name_with_score($team_name, $logo_attachment_id, $score) {
        return '<div class="lllm-team">' . self::render_team_with_logo($team_name, $logo_attachment_id) . '</div>'
            . '<br><span class="lllm-team-score">' . esc_html((string) $score) . '</span>';
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
     * - `type` (`regular` default, `playoff` for playoff games)
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
                'type' => 'regular',
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
        $schedule_type = strtolower(self::sanitize_shortcode_token($atts['type']));
        if (!in_array($schedule_type, array('regular', 'playoff'), true)) {
            $schedule_type = 'regular';
        }
        $show_past = $atts['show_past'] === '1';
        $show_future = $atts['show_future'] === '1';
        $limit = max(1, intval($atts['limit']));

        global $wpdb;
        $filters = array('g.division_id = %d');
        $params = array($division->id);
        if ($schedule_type === 'playoff') {
            $filters[] = "(g.competition_type = %s OR (g.playoff_round IS NOT NULL AND g.playoff_round <> '') OR (g.playoff_slot IS NOT NULL AND g.playoff_slot <> ''))";
            $params[] = 'playoff';
        } else {
            $filters[] = "(g.competition_type <> %s AND (g.playoff_round IS NULL OR g.playoff_round = '') AND (g.playoff_slot IS NULL OR g.playoff_slot = ''))";
            $params[] = 'playoff';
        }

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

        $schedule_label = $schedule_type === 'playoff' ? __('Playoff', 'lllm') : __('Regular', 'lllm');
        $output = self::render_context_heading($season, $division, __('Schedule', 'lllm'), $schedule_label);
        $output .= '<div class="lllm-table-wrap">';
        $output .= '<table class="lllm-schedule"><thead><tr>';
        $output .= '<th class="away">' . esc_html__('Away', 'lllm') . '</th>';
        $output .= '<th class="home">' . esc_html__('Home', 'lllm') . '</th>';
        $output .= '<th class="date-time">' . esc_html__('Date/Time', 'lllm') . '</th>';
        $output .= '<th class="location">' . esc_html__('Location', 'lllm') . '</th>';
        $output .= '<th class="win">' . esc_html__('Win', 'lllm') . '</th>';
        $output .= '</tr></thead><tbody>';

        foreach ($games as $game) {
            $dt = new DateTime($game->start_datetime_utc, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone($timezone));
            $home_score = '—';
            $away_score = '—';
            $winner = '—';
            if ($game->status === 'played') {
                $home_score = (string) $game->home_score;
                $away_score = (string) $game->away_score;
                if ((int) $game->home_score > (int) $game->away_score) {
                    $winner = (string) $game->home_name;
                } elseif ((int) $game->away_score > (int) $game->home_score) {
                    $winner = (string) $game->away_name;
                }
            }

            $output .= '<tr>';
            $output .= '<td class="away" data-label="' . esc_attr__('Away', 'lllm') . '">' . self::render_team_logo_name_with_score((string) $game->away_name, (int) $game->away_logo_attachment_id, $away_score) . '</td>';
            $output .= '<td class="home" data-label="' . esc_attr__('Home', 'lllm') . '">' . self::render_team_logo_name_with_score((string) $game->home_name, (int) $game->home_logo_attachment_id, $home_score) . '</td>';
            $output .= '<td class="date-time" data-label="' . esc_attr__('Date/Time', 'lllm') . '">' . self::render_date_parts($dt) . '</td>';
            $output .= '<td class="location" data-label="' . esc_attr__('Location', 'lllm') . '">' . esc_html($game->location) . '</td>';
            $output .= '<td class="win" data-label="' . esc_attr__('Win', 'lllm') . '">' . esc_html($winner) . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody></table>';
        $output .= '</div>';
        $output .= '<p class="lllm-updated">' . esc_html__('Last updated:', 'lllm') . ' ' . esc_html(wp_date('Y-m-d H:i', time(), new DateTimeZone($timezone))) . '</p>';

        return $output;
    }

    /**
     * Renders the standings table shortcode output.
     *
     * Supported attributes:
     * - `season`, `division` (slug filters)
     * - `type` (`regular` default, `playoff` for playoff standings)
     * - `mobile_mode` (`compact`/`full`; compact hides low-priority stats on narrow screens)
     *
     * @param array<string,string> $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function render_standings($atts) {
        $atts = shortcode_atts(
            array(
                'season' => '',
                'division' => '',
                'type' => 'regular',
                'mobile_mode' => 'compact',
            ),
            $atts,
            'lllm_standings'
        );

        list($season, $division) = self::resolve_context($atts);
        if (!$season || !$division) {
            return '<p>' . esc_html__('Standings are not available yet.', 'lllm') . '</p>';
        }

        $timezone = $season->timezone ? $season->timezone : wp_timezone_string();
        $standings_type = strtolower(self::sanitize_shortcode_token($atts['type']));
        if (!in_array($standings_type, array('regular', 'playoff'), true)) {
            $standings_type = 'regular';
        }

        $standings = LLLM_Standings::get_standings($division->id, $standings_type);

        if (!$standings) {
            return '<p>' . esc_html__('No standings available.', 'lllm') . '</p>';
        }

        $mobile_mode = strtolower((string) $atts['mobile_mode']);
        $show_full_mobile = $mobile_mode === 'full';
        $standings_classes = 'lllm-standings';
        if ($show_full_mobile) {
            $standings_classes .= ' lllm-standings--show-full';
        }

        $standings_label = $standings_type === 'playoff' ? __('Playoff', 'lllm') : __('Regular', 'lllm');
        $output = self::render_context_heading($season, $division, __('Standings', 'lllm'), $standings_label);
        $output .= '<div class="lllm-table-wrap">';
        $output .= '<table class="' . esc_attr($standings_classes) . '"><thead><tr>';
        $output .= '<th class="team is-priority-high">' . esc_html__('Team', 'lllm') . '</th>';
        $output .= '<th class="gp is-priority-medium">' . esc_html__('GP', 'lllm') . '</th>';
        $output .= '<th class="w is-priority-high">' . esc_html__('W', 'lllm') . '</th>';
        $output .= '<th class="l is-priority-high">' . esc_html__('L', 'lllm') . '</th>';
        $output .= '<th class="t is-priority-medium">' . esc_html__('T', 'lllm') . '</th>';
        $output .= '<th class="rf is-priority-low">' . esc_html__('RF', 'lllm') . '</th>';
        $output .= '<th class="ra is-priority-low">' . esc_html__('RA', 'lllm') . '</th>';
        $output .= '<th class="rd is-priority-low">' . esc_html__('RD', 'lllm') . '</th>';
        $output .= '<th class="win-pct is-priority-high">' . esc_html__('Win%', 'lllm') . '</th>';
        $output .= '</tr></thead><tbody>';

        foreach ($standings as $row) {
            $team_logo = isset($row['logo_attachment_id']) ? (int) $row['logo_attachment_id'] : 0;
            $output .= '<tr>';
            $output .= '<td class="team is-priority-high" data-label="' . esc_attr__('Team', 'lllm') . '">' . self::render_team_with_logo((string) $row['team_name'], $team_logo) . '</td>';
            $output .= '<td class="gp is-priority-medium" data-label="' . esc_attr__('GP', 'lllm') . '">' . esc_html($row['gp']) . '</td>';
            $output .= '<td class="w is-priority-high" data-label="' . esc_attr__('W', 'lllm') . '">' . esc_html($row['wins']) . '</td>';
            $output .= '<td class="l is-priority-high" data-label="' . esc_attr__('L', 'lllm') . '">' . esc_html($row['losses']) . '</td>';
            $output .= '<td class="t is-priority-medium" data-label="' . esc_attr__('T', 'lllm') . '">' . esc_html($row['ties']) . '</td>';
            $output .= '<td class="rf is-priority-low" data-label="' . esc_attr__('RF', 'lllm') . '">' . esc_html($row['rf']) . '</td>';
            $output .= '<td class="ra is-priority-low" data-label="' . esc_attr__('RA', 'lllm') . '">' . esc_html($row['ra']) . '</td>';
            $output .= '<td class="rd is-priority-low" data-label="' . esc_attr__('RD', 'lllm') . '">' . esc_html($row['rd']) . '</td>';
            $output .= '<td class="win-pct is-priority-high" data-label="' . esc_attr__('Win%', 'lllm') . '">' . esc_html(number_format($row['win_pct'], 3)) . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody></table>';
        $output .= '</div>';
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

}
