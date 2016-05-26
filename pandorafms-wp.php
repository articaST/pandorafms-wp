<?php
/**
 * @package PandoraFMS WP
 * @version 0.0
 */
/*
Plugin Name: PandoraFMS WP
Plugin URI: https://github.com/articaST/pandorafms-wp
Description: Hardening, monitoring and security plugin for Wordpress.
Author: Artica Soluciones Tecnologicas
Version: 0.0
Author URI: http://artica.es/
Text Domain: pandorafms-wp
License: AGPLv3
Copyright: (c) 2016-2016 Artica Soluciones Tecnologicas
*/

if ( ! defined( 'ABSPATH' ) ) die();

//=== INIT === INCLUDES ================================================
require_once(plugin_dir_path(__FILE__) . "/includes/PandoraFMS_WP.class.php");
require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_AdminPages.class.php");
require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_Widget_Dashboard.class.php");
require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_Footer.class.php");
//=== END ==== INCLUDES ================================================


//=== INIT === HOOKS FOR INSTALL (OR REGISTER) AND UNINSTALL ===========
register_activation_hook(__FILE__, array('PandoraFMS_WP', 'activation'));
register_deactivation_hook(__FILE__, array('PandoraFMS_WP', 'deactivation'));
//=== END ==== HOOKS FOR INSTALL (OR REGISTER) AND UNINSTALL ===========


//=== INIT === AJAX HOOKS ==============================================
add_action('admin_footer', array('PandoraFMS_WP', 'ajax'));
add_action('wp_ajax_check_admin_user_enabled', array('PandoraFMS_WP', 'ajax_check_admin_user_enabled'));
add_action('wp_ajax_check_audit_password', array('PandoraFMS_WP', 'ajax_check_audit_password'));
add_action('wp_ajax_check_audit_files', array('PandoraFMS_WP', 'ajax_check_audit_files'));
add_action('wp_ajax_get_list_users_with_weak_password', array('PandoraFMS_WP', 'ajax_get_list_users_with_weak_password'));
add_action('wp_ajax_get_list_audit_files', array('PandoraFMS_WP', 'ajax_get_list_audit_files'));
//=== END ==== AJAX HOOKS ==============================================


//=== INIT === CRON HOOKS ==============================================
if (!wp_next_scheduled('cron_audit_passwords_strength')) {
	wp_schedule_event(time(), 'daily', 'cron_audit_passwords_strength');
}
add_action('cron_audit_passwords_strength', array('PandoraFMS_WP', 'cron_audit_passwords_strength'));
if (!wp_next_scheduled('cron_audit_files')) {
	wp_schedule_event(time(), 'daily', 'cron_audit_files');
}
add_action('cron_audit_files', array('PandoraFMS_WP', 'cron_audit_files'));
//=== END ==== CRON HOOKS ==============================================


//=== INIT === ANOTHER HOOKS ===========================================
add_action('init', array('PandoraFMS_WP', 'init'));
add_action('admin_init', array('PandoraFMS_WP', 'admin_init'));
add_action('admin_menu', array('PandoraFMS_WP', 'add_admin_menu_entries'));
//=== END ==== ANOTHER HOOKS ===========================================
?>