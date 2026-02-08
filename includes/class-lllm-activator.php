<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLLM_Activator {
    public static function activate() {
        LLLM_Migrations::run();
        update_option('lllm_plugin_version', LLLM_VERSION);

        self::sync_roles();
    }

    public static function sync_roles() {
        self::sync_manager_role_caps();
        self::sync_admin_caps();
    }

    private static function get_manager_caps() {
        return array(
            'lllm_manage_seasons' => true,
            'lllm_manage_divisions' => true,
            'lllm_manage_teams' => true,
            'lllm_manage_games' => true,
            'lllm_import_csv' => true,
            'lllm_view_logs' => true,
        );
    }

    private static function sync_manager_role_caps() {
        $caps = self::get_manager_caps();
        $role = get_role('lllm_manager');
        if (!$role) {
            add_role('lllm_manager', __('Manager', 'lllm'), array());
            $role = get_role('lllm_manager');
        }

        if ($role) {
            foreach ($caps as $cap => $enabled) {
                $role->add_cap($cap, $enabled);
            }
        }
    }

    private static function sync_admin_caps() {
        $caps = self::get_manager_caps();
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($caps as $cap => $enabled) {
                $admin_role->add_cap($cap, $enabled);
            }
        }
    }
}
