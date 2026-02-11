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
add_action('admin_enqueue_scripts', array('LLLM_Admin', 'enqueue_assets'));
add_action('admin_bar_menu', 'lllm_add_welcome_admin_bar_link', 100);
add_filter('login_redirect', 'lllm_manager_login_redirect', 10, 3);
add_filter('ajax_query_attachments_args', 'lllm_manager_media_library_query');
add_action('pre_get_posts', 'lllm_manager_media_library_list_query');
add_action('init', array('LLLM_Shortcodes', 'register'));

function autoload_lllm() {
    require_once __DIR__ . '/includes/class-lllm-activator.php';
    require_once __DIR__ . '/includes/class-lllm-roles.php';
    require_once __DIR__ . '/includes/class-lllm-migrations.php';
    require_once __DIR__ . '/includes/class-lllm-import.php';
    require_once __DIR__ . '/includes/class-lllm-standings.php';
    require_once __DIR__ . '/includes/class-lllm-shortcodes.php';
    require_once __DIR__ . '/includes/class-lllm-admin.php';
}

function lllm_maybe_upgrade() {
    // Always resync role capabilities so existing Manager users receive new caps immediately.
    LLLM_Roles::sync_roles();

    $stored_version = get_option('lllm_plugin_version');
    if ($stored_version === LLLM_VERSION) {
        return;
    }

    LLLM_Migrations::run();
    update_option('lllm_plugin_version', LLLM_VERSION);
}


function lllm_add_welcome_admin_bar_link($wp_admin_bar) {
    if (!is_admin_bar_showing()) {
        return;
    }

    if (!is_user_logged_in() || !current_user_can('lllm_manage_seasons')) {
        return;
    }

    $wp_admin_bar->add_node(array(
        'id' => 'lllm-welcome',
        'title' => __('⚾ League Manager', 'lllm'),
        'href' => admin_url('admin.php?page=lllm-welcome'),
    ));
}

function lllm_manager_login_redirect($redirect_to, $requested_redirect_to, $user) {
    if (!($user instanceof WP_User)) {
        return $redirect_to;
    }

    if (in_array('lllm_manager', (array) $user->roles, true)) {
        return admin_url('admin.php?page=lllm-welcome');
    }

    return $redirect_to;
}


function lllm_manager_media_library_query($query) {
    if (!is_user_logged_in() || !current_user_can('lllm_manage_media_library')) {
        return $query;
    }

    $user = wp_get_current_user();
    if (!in_array('lllm_manager', (array) $user->roles, true)) {
        return $query;
    }

    if (isset($query['author'])) {
        unset($query['author']);
    }

    return $query;
}

function lllm_manager_media_library_list_query($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if (!isset($GLOBALS['pagenow']) || 'upload.php' !== $GLOBALS['pagenow']) {
        return;
    }

    if (!is_user_logged_in() || !current_user_can('lllm_manage_media_library')) {
        return;
    }

    $user = wp_get_current_user();
    if (!in_array('lllm_manager', (array) $user->roles, true)) {
        return;
    }

    $query->set('author', '');
}
