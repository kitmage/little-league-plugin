<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLLM_Roles {
    /**
     * Returns capabilities granted to both Manager and Administrator roles.
     *
     * @return array<string,bool> Map of capability => enabled.
     */
    public static function get_caps() {
        return array(
            'lllm_manage_seasons' => true,
            'lllm_manage_divisions' => true,
            'lllm_manage_teams' => true,
            'lllm_manage_games' => true,
            'lllm_import_csv' => true,
            'lllm_view_logs' => true,
            'upload_files' => true,
            'lllm_manage_media_library' => true,
        );
    }

    /**
     * Ensures plugin roles exist and include all required capabilities.
     *
     * Creates the custom Manager role when missing, then syncs capabilities onto
     * both Manager and Administrator roles.
     *
     * @return void
     */
    public static function sync_roles() {
        $caps = self::get_caps();

        $manager_role = get_role('lllm_manager');
        if (!$manager_role) {
            $manager_role = add_role('lllm_manager', __('Manager', 'lllm'), $caps);
        }

        if ($manager_role) {
            foreach ($caps as $cap => $enabled) {
                $manager_role->add_cap($cap, $enabled);
            }
        }

        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($caps as $cap => $enabled) {
                $admin_role->add_cap($cap, $enabled);
            }
        }
    }
}
