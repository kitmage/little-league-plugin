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

/**
 * Loads all plugin class files used during runtime.
 *
 * @return void
 */
function autoload_lllm() {
    require_once __DIR__ . '/includes/class-lllm-activator.php';
    require_once __DIR__ . '/includes/class-lllm-roles.php';
    require_once __DIR__ . '/includes/class-lllm-migrations.php';
    require_once __DIR__ . '/includes/class-lllm-import.php';
    require_once __DIR__ . '/includes/class-lllm-standings.php';
    require_once __DIR__ . '/includes/class-lllm-shortcodes.php';
    require_once __DIR__ . '/includes/class-lllm-admin.php';
}

/**
 * Runs plugin upgrade tasks when the stored version is out of date.
 *
 * Always re-syncs role capabilities so existing users receive any newly-added caps.
 *
 * @return void
 */
function lllm_maybe_upgrade() {
    // Always resync role capabilities so existing Manager users receive new caps immediately.
    LLLM_Roles::sync_roles();

    $stored_version = get_option('lllm_plugin_version');
    $schema_outdated = !LLLM_Migrations::has_required_game_columns();

    if ($stored_version === LLLM_VERSION && !$schema_outdated) {
        return;
    }

    LLLM_Migrations::run();
    update_option('lllm_plugin_version', LLLM_VERSION);
}


/**
 * Adds a quick League Manager link to the WordPress admin bar.
 *
 * The link is shown only for logged-in users who can manage seasons.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar object provided by WordPress.
 * @return void
 */
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

/**
 * Redirects manager users to the League Manager welcome screen after login.
 *
 * @param string               $redirect_to           Default destination URL.
 * @param string               $requested_redirect_to Requested redirect URL.
 * @param WP_User|WP_Error     $user                  Authenticated user object when available.
 * @return string Redirect URL.
 */
function lllm_manager_login_redirect($redirect_to, $requested_redirect_to, $user) {
    if (!($user instanceof WP_User)) {
        return $redirect_to;
    }

    if (in_array('lllm_manager', (array) $user->roles, true)) {
        return admin_url('admin.php?page=lllm-welcome');
    }

    return $redirect_to;
}


/**
 * Allows managers to browse sitewide media in the attachment modal query.
 *
 * WordPress commonly limits attachment queries to current author for lower-privileged users.
 * This removes that author constraint for managers with media-library capability.
 *
 * @param array<string,mixed> $query Attachment query args for AJAX media requests.
 * @return array<string,mixed> Filtered attachment query args.
 */
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

/**
 * Allows managers to browse all media items in the Uploads list table.
 *
 * @param WP_Query $query The main admin query object.
 * @return void
 */
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
