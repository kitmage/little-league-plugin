<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLLM_Shortcodes {
    public static function register() {
        add_shortcode('lllm_schedule', array(__CLASS__, 'render_schedule'));
        add_shortcode('lllm_standings', array(__CLASS__, 'render_standings'));
        add_shortcode('lllm_teams', array(__CLASS__, 'render_teams'));
    }

    private static function get_season_by_slug($slug) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'lllm_seasons WHERE slug = %s',
                $slug
            )
        );
    }

    private static function get_division_by_slug($slug) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'lllm_divisions WHERE slug = %s',
                $slug
            )
        );
    }

    private static function get_active_season() {
        global $wpdb;
        return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'lllm_seasons WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
    }

    private static function get_first_division($season_id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'lllm_divisions WHERE season_id = %d ORDER BY name ASC LIMIT 1',
                $season_id
            )
        );
    }

    private static function resolve_context($atts) {
        $season = null;
        $division = null;

        if (!empty($atts['season'])) {
            $season = self::get_season_by_slug($atts['season']);
        }
        if (!$season) {
            $season = self::get_active_season();
        }

        if (!empty($atts['division'])) {
            $division = self::get_division_by_slug($atts['division']);
        }

        if (!$division && $season) {
            $division = self::get_first_division($season->id);
        }

        return array($season, $division);
    }

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
        $sql = 'SELECT g.*, home.name AS home_name, away.name AS away_name
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

        $output = '<table class="lllm-schedule"><thead><tr>';
        $output .= '<th>' . esc_html__('Date/Time', 'lllm') . '</th>';
        $output .= '<th>' . esc_html__('Location', 'lllm') . '</th>';
        $output .= '<th>' . esc_html__('Home', 'lllm') . '</th>';
        $output .= '<th>' . esc_html__('Away', 'lllm') . '</th>';
        $output .= '<th>' . esc_html__('Status', 'lllm') . '</th>';
        $output .= '<th>' . esc_html__('Score', 'lllm') . '</th>';
        $output .= '</tr></thead><tbody>';

        foreach ($games as $game) {
            $dt = new DateTime($game->start_datetime_utc, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone($timezone));
            $status = esc_html($game->status);
            $score = '—';
            if ($game->status === 'played') {
                $score = esc_html($game->home_score . ' - ' . $game->away_score);
            }
            $output .= '<tr>';
            $output .= '<td>' . esc_html($dt->format('Y-m-d H:i')) . '</td>';
            $output .= '<td>' . esc_html($game->location) . '</td>';
            $output .= '<td>' . esc_html($game->home_name) . '</td>';
            $output .= '<td>' . esc_html($game->away_name) . '</td>';
            $output .= '<td>' . $status . '</td>';
            $output .= '<td>' . $score . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody></table>';
        $output .= '<p class="lllm-updated">' . esc_html__('Last updated:', 'lllm') . ' ' . esc_html(wp_date('Y-m-d H:i', time(), new DateTimeZone($timezone))) . '</p>';

        return $output;
    }

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

        $output = '<table class="lllm-standings"><thead><tr>';
        $output .= '<th>' . esc_html__('Team', 'lllm') . '</th>';
        $output .= '<th>' . esc_html__('GP', 'lllm') . '</th>';
        $output .= '<th>' . esc_html__('W', 'lllm') . '</th>';
        $output .= '<th>' . esc_html__('L', 'lllm') . '</th>';
        $output .= '<th>' . esc_html__('T', 'lllm') . '</th>';
        $output .= '<th>' . esc_html__('RF', 'lllm') . '</th>';
        $output .= '<th>' . esc_html__('RA', 'lllm') . '</th>';
        $output .= '<th>' . esc_html__('RD', 'lllm') . '</th>';
        $output .= '<th>' . esc_html__('Win%', 'lllm') . '</th>';
        $output .= '</tr></thead><tbody>';

        foreach ($standings as $row) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($row['team_name']) . '</td>';
            $output .= '<td>' . esc_html($row['gp']) . '</td>';
            $output .= '<td>' . esc_html($row['wins']) . '</td>';
            $output .= '<td>' . esc_html($row['losses']) . '</td>';
            $output .= '<td>' . esc_html($row['ties']) . '</td>';
            $output .= '<td>' . esc_html($row['rf']) . '</td>';
            $output .= '<td>' . esc_html($row['ra']) . '</td>';
            $output .= '<td>' . esc_html($row['rd']) . '</td>';
            $output .= '<td>' . esc_html(number_format($row['win_pct'], 3)) . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody></table>';
        $output .= '<p class="lllm-updated">' . esc_html__('Last updated:', 'lllm') . ' ' . esc_html(wp_date('Y-m-d H:i', time(), new DateTimeZone($timezone))) . '</p>';

        return $output;
    }

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
        $output = '<ul class="lllm-teams">';
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
