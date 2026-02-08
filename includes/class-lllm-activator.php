<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLLM_Activator {
    public static function activate() {
        LLLM_Migrations::run();
        update_option('lllm_plugin_version', LLLM_VERSION);
        LLLM_Roles::sync_roles();
    }
}
