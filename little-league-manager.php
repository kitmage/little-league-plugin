<?php
/**
 * Plugin Name: Little League League Manager
 * Description: Manage Little League seasons, divisions, teams, schedules, and standings.
 * Version: 0.1.0
 * Author: Little League
 * Text Domain: lllm
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LLLM_VERSION', '0.1.0');
define('LLLM_PLUGIN_FILE', __FILE__);

autoload_lllm();
register_activation_hook(__FILE__, array('LLLM_Activator', 'activate'));

add_action('plugins_loaded', 'lllm_maybe_upgrade');
add_action('admin_menu', array('LLLM_Admin', 'register_menu'));
add_action('admin_init', array('LLLM_Admin', 'register_actions'));

function autoload_lllm() {
    require_once __DIR__ . '/includes/class-lllm-activator.php';
    require_once __DIR__ . '/includes/class-lllm-roles.php';
    require_once __DIR__ . '/includes/class-lllm-migrations.php';
    require_once __DIR__ . '/includes/class-lllm-import.php';
    require_once __DIR__ . '/includes/class-lllm-admin.php';
}

function lllm_maybe_upgrade() {
    $stored_version = get_option('lllm_plugin_version');
    if ($stored_version === LLLM_VERSION) {
        return;
    }

    LLLM_Migrations::run();
    LLLM_Roles::sync_roles();
    update_option('lllm_plugin_version', LLLM_VERSION);
}
