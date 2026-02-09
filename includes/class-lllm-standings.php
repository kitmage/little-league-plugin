<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLLM_Standings {
    public static function get_cache_key($division_id) {
        return 'lllm_standings_' . (int) $division_id;
    }

    public static function bust_cache($division_id) {
        delete_transient(self::get_cache_key($division_id));
    }

    public static function get_standings($division_id) {
        $cache_key = self::get_cache_key($division_id);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $teams = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT ti.id AS team_instance_id, tm.name AS team_name
                 FROM ' . $wpdb->prefix . 'lllm_team_instances ti
                 JOIN ' . $wpdb->prefix . 'lllm_team_masters tm ON ti.team_master_id = tm.id
                 WHERE ti.division_id = %d
                 ORDER BY team_name ASC',
                $division_id
            )
        );

        $stats = array();
        foreach ($teams as $team) {
            $stats[$team->team_instance_id] = array(
                'team_instance_id' => (int) $team->team_instance_id,
                'team_name' => $team->team_name,
                'gp' => 0,
                'wins' => 0,
                'losses' => 0,
                'ties' => 0,
                'rf' => 0,
                'ra' => 0,
                'rd' => 0,
                'win_pct' => 0.0,
            );
        }

        $games = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT home_team_instance_id, away_team_instance_id, home_score, away_score
                 FROM ' . $wpdb->prefix . 'lllm_games
                 WHERE division_id = %d AND status = %s',
                $division_id,
                'played'
            )
        );

        foreach ($games as $game) {
            $home_id = (int) $game->home_team_instance_id;
            $away_id = (int) $game->away_team_instance_id;

            if (!isset($stats[$home_id]) || !isset($stats[$away_id])) {
                continue;
            }

            $home_score = (int) $game->home_score;
            $away_score = (int) $game->away_score;

            $stats[$home_id]['gp']++;
            $stats[$away_id]['gp']++;

            $stats[$home_id]['rf'] += $home_score;
            $stats[$home_id]['ra'] += $away_score;
            $stats[$away_id]['rf'] += $away_score;
            $stats[$away_id]['ra'] += $home_score;

            if ($home_score > $away_score) {
                $stats[$home_id]['wins']++;
                $stats[$away_id]['losses']++;
            } elseif ($away_score > $home_score) {
                $stats[$away_id]['wins']++;
                $stats[$home_id]['losses']++;
            } else {
                $stats[$home_id]['ties']++;
                $stats[$away_id]['ties']++;
            }
        }

        foreach ($stats as &$row) {
            $row['rd'] = $row['rf'] - $row['ra'];
            if ($row['gp'] > 0) {
                $row['win_pct'] = ($row['wins'] + 0.5 * $row['ties']) / $row['gp'];
            }
        }
        unset($row);

        $standings = array_values($stats);
        usort($standings, function ($a, $b) {
            if ($a['win_pct'] !== $b['win_pct']) {
                return ($a['win_pct'] < $b['win_pct']) ? 1 : -1;
            }
            if ($a['wins'] !== $b['wins']) {
                return ($a['wins'] < $b['wins']) ? 1 : -1;
            }
            if ($a['rd'] !== $b['rd']) {
                return ($a['rd'] < $b['rd']) ? 1 : -1;
            }
            if ($a['ra'] !== $b['ra']) {
                return ($a['ra'] > $b['ra']) ? 1 : -1;
            }
            return strcasecmp($a['team_name'], $b['team_name']);
        });

        set_transient($cache_key, $standings, HOUR_IN_SECONDS);

        return $standings;
    }
}
