<?php
/**
 * @package PandoraFMS WP
 * @version 2.0
 */
/*
Plugin Name: PandoraFMS WP
Plugin URI: https://github.com/articaST/pandorafms-wp
Description: Plugin for monitoring Wordpress with Pandora FMS. Collect data from your wordpress and make it accessible from outside using the REST API. It makes available data to a remote Pandora FMS for monitoring this information.
Author: Artica PFMS
Version: 2.0
Author URI: https://artica.es/
Text Domain: pandorafms-wp
License: AGPLv3
Copyright: (c) 2022 Artica PFMS
*/

if ( ! defined( 'ABSPATH' ) ) die();

//=== INIT === INCLUDES ================================================
require_once(plugin_dir_path(__FILE__) . "/includes/PandoraFMS_WP.class.php");
require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_AdminPages.class.php");
require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_Widget_Dashboard.class.php");
require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_ApiRest.class.php");
//require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_Hooks.class.php");

require_once(ABSPATH . "wp-admin/includes/class-wp-upgrader.php");
require_once(ABSPATH . "wp-admin/includes/file.php");
require_once(ABSPATH . "wp-admin/includes/template.php");
//=== END ==== INCLUDES ================================================



//=== INIT === HOOKS FOR INSTALL (OR REGISTER) AND UNINSTALL ===========
register_activation_hook(__FILE__, array('PandoraFMS_WP', 'activation'));
register_deactivation_hook(__FILE__, array('PandoraFMS_WP', 'deactivation'));
//=== END ==== HOOKS FOR INSTALL (OR REGISTER) AND UNINSTALL ===========


//=== INIT === AJAX HOOKS ==============================================
add_action( 'wp_print_scripts', array('PandoraFMS_WP','my_wp_enqueue_script' ));

add_action('admin_footer', array('PandoraFMS_WP', 'ajax'));
add_action('wp_ajax_check_admin_user_enabled', array('PandoraFMS_WP', 'ajax_check_admin_user_enabled'));
add_action('wp_ajax_check_plugins_pending_update', array('PandoraFMS_WP', 'ajax_check_plugins_pending_update'));
//=== END ==== AJAX HOOKS ==============================================


//=== INIT === CRON HOOKS ==============================================
if (!wp_next_scheduled('cron_clean_logs')) {
	wp_schedule_event(time(), 'daily', 'cron_clean_logs');
}
add_action('cron_clean_logs', array('PandoraFMS_WP', 'cron_clean_logs'));
//=== END ==== CRON HOOKS ==============================================


//=== INIT === API REST HOOKS ==========================================
add_action('rest_api_init', array('PandoraFMS_WP', 'rest_api_init')); 
//=== END ==== API REST HOOKS ==========================================


//=== INIT === ADD NAME OF DIR PANDORA PLUGIN ==========================
$pfms_wp = PandoraFMS_WP::getInstance();

$pfms_wp->debug=0;

$plugin_path = explode('/' , untrailingslashit(plugin_dir_path(__FILE__)));
$pfms_wp->name_dir_plugin = array_pop( $plugin_path);
//=== END === ADD NAME OF DIR PANDORA PLUGIN ===========================


//=== INIT === ANOTHER HOOKS ===========================================
add_action('admin_notices', array('PandoraFMS_WP', 'show_message_version_wp'));
add_action('init', array('PandoraFMS_WP', 'init'));
add_action('admin_init', array('PandoraFMS_WP', 'admin_init'));
add_action('admin_menu', array('PandoraFMS_WP', 'add_admin_menu_entries'));
//=== END ==== ANOTHER HOOKS ===========================================





?>