<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLLM_Activator {
    public static function activate() {
        LLLM_Migrations::run();
        update_option('lllm_plugin_version', LLLM_VERSION);

        self::register_roles();
    }

    private static function register_roles() {
        $caps = array(
            'lllm_manage_seasons' => true,
            'lllm_manage_divisions' => true,
            'lllm_manage_teams' => true,
            'lllm_manage_games' => true,
            'lllm_import_csv' => true,
            'lllm_view_logs' => true,
        );

        add_role('lllm_manager', __('Manager', 'lllm'), $caps);

        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($caps as $cap => $enabled) {
                $admin_role->add_cap($cap, $enabled);
            }
        }
    }
}
