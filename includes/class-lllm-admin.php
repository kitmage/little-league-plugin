<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLLM_Admin {
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
            __('Teams', 'lllm'),
            __('Teams', 'lllm'),
            'lllm_manage_teams',
            'lllm-teams',
            array(__CLASS__, 'render_teams')
        );

        add_submenu_page(
            'lllm-seasons',
            __('Division Teams', 'lllm'),
            __('Division Teams', 'lllm'),
            'lllm_manage_teams',
            'lllm-division-teams',
            array(__CLASS__, 'render_division_teams')
        );
    }

    public static function register_actions() {
        add_action('admin_post_lllm_save_season', array(__CLASS__, 'handle_save_season'));
        add_action('admin_post_lllm_save_division', array(__CLASS__, 'handle_save_division'));
        add_action('admin_post_lllm_save_team', array(__CLASS__, 'handle_save_team'));
        add_action('admin_post_lllm_update_division_teams', array(__CLASS__, 'handle_update_division_teams'));
    }

    private static function table($name) {
        global $wpdb;
        return $wpdb->prefix . 'lllm_' . $name;
    }

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

    private static function redirect_with_notice($url, $notice, $message = '') {
        $url = add_query_arg('lllm_notice', $notice, $url);
        if ($message !== '') {
            $url = add_query_arg('lllm_message', rawurlencode($message), $url);
        }
        wp_safe_redirect($url);
        exit;
    }

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
                $text = __('Team saved.', 'lllm');
                break;
            case 'division_teams_updated':
                $text = __('Division teams updated.', 'lllm');
                break;
            case 'division_teams_blocked':
                $class = 'notice notice-warning';
                $text = __('Some teams could not be removed because games exist:', 'lllm') . ' ' . $message;
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
        echo '<td><input name="name" id="lllm-season-name" type="text" class="regular-text" value="' . esc_attr($season_name) . '" required></td></tr>';

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
                echo '<td>' . esc_html($season->name) . '</td>';
                echo '<td>' . esc_html($season->timezone) . '</td>';
                echo '<td>' . esc_html($status) . '</td>';
                echo '<td><a href="' . esc_url($edit_link) . '">' . esc_html__('Edit', 'lllm') . '</a></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>';
    }

    public static function render_divisions() {
        if (!current_user_can('lllm_manage_divisions')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        global $wpdb;
        $seasons = $wpdb->get_results("SELECT * FROM " . self::table('seasons') . ' ORDER BY created_at DESC');
        $season_id = isset($_GET['season_id']) ? absint($_GET['season_id']) : 0;
        if (!$season_id && $seasons) {
            $season_id = (int) $seasons[0]->id;
        }

        $divisions = array();
        if ($season_id) {
            $divisions = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM ' . self::table('divisions') . ' WHERE season_id = %d ORDER BY sort_order ASC, name ASC',
                    $season_id
                )
            );
        }

        $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $editing = null;
        if ($edit_id) {
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
        $sort_order = $editing ? (int) $editing->sort_order : 0;

        echo '<table class="form-table"><tbody>';
        echo '<tr><th scope="row"><label for="lllm-division-name">' . esc_html__('Division Name', 'lllm') . '</label></th>';
        echo '<td><input name="name" id="lllm-division-name" type="text" class="regular-text" value="' . esc_attr($division_name) . '" required></td></tr>';

        echo '<tr><th scope="row"><label for="lllm-division-sort">' . esc_html__('Sort Order', 'lllm') . '</label></th>';
        echo '<td><input name="sort_order" id="lllm-division-sort" type="number" class="small-text" value="' . esc_attr($sort_order) . '"></td></tr>';
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
            echo '<th>' . esc_html__('Name', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Sort Order', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Actions', 'lllm') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($divisions as $division) {
                $edit_link = add_query_arg(
                    array('page' => 'lllm-divisions', 'season_id' => $season_id, 'edit' => $division->id),
                    admin_url('admin.php')
                );
                echo '<tr>';
                echo '<td>' . esc_html($division->name) . '</td>';
                echo '<td>' . esc_html($division->sort_order) . '</td>';
                echo '<td><a href="' . esc_url($edit_link) . '">' . esc_html__('Edit', 'lllm') . '</a></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>';
    }

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
        echo '<h1>' . esc_html__('Teams', 'lllm') . '</h1>';
        self::render_notices();

        echo '<h2>' . esc_html($editing ? __('Edit Team', 'lllm') : __('Add Team', 'lllm')) . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lllm_save_team');
        echo '<input type="hidden" name="action" value="lllm_save_team">';
        if ($editing) {
            echo '<input type="hidden" name="id" value="' . esc_attr($editing->id) . '">';
        }

        $team_name = $editing ? $editing->name : '';
        $team_code = $editing ? $editing->team_code : '';

        echo '<table class="form-table"><tbody>';
        echo '<tr><th scope="row"><label for="lllm-team-name">' . esc_html__('Team Name', 'lllm') . '</label></th>';
        echo '<td><input name="name" id="lllm-team-name" type="text" class="regular-text" value="' . esc_attr($team_name) . '" required></td></tr>';

        echo '<tr><th scope="row"><label for="lllm-team-code">' . esc_html__('Team Code', 'lllm') . '</label></th>';
        echo '<td><input name="team_code" id="lllm-team-code" type="text" class="regular-text" value="' . esc_attr($team_code) . '" ' . ($can_edit_code ? '' : 'readonly') . '></td></tr>';
        echo '</tbody></table>';

        submit_button($editing ? __('Update Team', 'lllm') : __('Add Team', 'lllm'));
        echo '</form>';

        echo '<h2>' . esc_html__('All Teams', 'lllm') . '</h2>';
        if (!$teams) {
            echo '<p>' . esc_html__('No teams yet.', 'lllm') . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>' . esc_html__('Team Name', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Team Code', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Actions', 'lllm') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($teams as $team) {
                $edit_link = add_query_arg(
                    array('page' => 'lllm-teams', 'edit' => $team->id),
                    admin_url('admin.php')
                );
                echo '<tr>';
                echo '<td>' . esc_html($team->name) . '</td>';
                echo '<td>' . esc_html($team->team_code) . '</td>';
                echo '<td><a href="' . esc_url($edit_link) . '">' . esc_html__('Edit', 'lllm') . '</a></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>';
    }

    public static function render_division_teams() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        global $wpdb;
        $seasons = $wpdb->get_results('SELECT * FROM ' . self::table('seasons') . ' ORDER BY created_at DESC');
        $season_id = isset($_GET['season_id']) ? absint($_GET['season_id']) : 0;
        if (!$season_id && $seasons) {
            $season_id = (int) $seasons[0]->id;
        }

        $divisions = array();
        if ($season_id) {
            $divisions = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM ' . self::table('divisions') . ' WHERE season_id = %d ORDER BY sort_order ASC, name ASC',
                    $season_id
                )
            );
        }

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
        echo '<h1>' . esc_html__('Division Teams', 'lllm') . '</h1>';
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

        echo '<h2>' . esc_html__('Assign Teams', 'lllm') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lllm_update_division_teams');
        echo '<input type="hidden" name="action" value="lllm_update_division_teams">';
        echo '<input type="hidden" name="season_id" value="' . esc_attr($season_id) . '">';
        echo '<input type="hidden" name="division_id" value="' . esc_attr($division_id) . '">';

        if (!$teams) {
            echo '<p>' . esc_html__('No teams available. Add teams first.', 'lllm') . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>' . esc_html__('Assigned', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Team Name', 'lllm') . '</th>';
            echo '<th>' . esc_html__('Team Code', 'lllm') . '</th>';
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
        echo '<button class="button button-primary" type="submit" name="lllm_action" value="assign">' . esc_html__('Assign Selected Teams', 'lllm') . '</button> ';
        echo '<button class="button" type="submit" name="lllm_action" value="remove">' . esc_html__('Remove Selected Teams', 'lllm') . '</button>';
        echo '</p>';
        echo '</form>';
        echo '</div>';
    }

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
        $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;

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
            'sort_order' => $sort_order,
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

    public static function handle_save_team() {
        if (!current_user_can('lllm_manage_teams')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lllm'));
        }

        check_admin_referer('lllm_save_team');
        global $wpdb;
        $table = self::table('team_masters');

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $team_code = isset($_POST['team_code']) ? sanitize_text_field(wp_unslash($_POST['team_code'])) : '';

        if (!$name) {
            self::redirect_with_notice(admin_url('admin.php?page=lllm-teams'), 'error', __('Team name is required.', 'lllm'));
        }

        $slug = sanitize_title($name);
        $slug = self::unique_value($table, 'slug', $slug, $id);

        $can_edit_code = current_user_can('manage_options');
        if (!$can_edit_code || !$team_code) {
            $team_code = sanitize_title($name);
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
            'updated_at' => $timestamp,
        );

        if ($id) {
            $wpdb->update($table, $data, array('id' => $id));
        } else {
            $data['created_at'] = $timestamp;
            $wpdb->insert($table, $data);
        }

        self::redirect_with_notice(admin_url('admin.php?page=lllm-teams'), 'team_saved');
    }

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
}
