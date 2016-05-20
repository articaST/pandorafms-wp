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


//=== INIT === ANOTHER HOOKS ===========================================
add_action('init', array('PandoraFMS_WP', 'init'));
add_action('admin_init', array('PandoraFMS_WP', 'admin_init'));
add_action('admin_menu', array('PandoraFMS_WP', 'add_admin_menu_entries'));
//=== END ==== ANOTHER HOOKS ===========================================
?>