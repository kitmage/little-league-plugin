<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLLM_Activator {
    /**
     * Runs plugin setup tasks on activation.
     *
     * Side effects:
     * - creates/updates database schema
     * - records current plugin version
     * - syncs custom role capabilities
     *
     * @return void
     */
    public static function activate() {
        LLLM_Migrations::run();
        update_option('lllm_plugin_version', LLLM_VERSION);
        LLLM_Roles::sync_roles();
    }
}
