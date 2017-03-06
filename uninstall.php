<?php
/*
Copyright (c) 2017-2017 Artica Soluciones Tecnologicas

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined("WP_UNINSTALL_PLUGIN")) die();

//=== INIT === INCLUDES ================================================
require_once(plugin_dir_path(__FILE__) . "/includes/PandoraFMS_WP.class.php");
//=== END ==== INCLUDES ================================================

	error_log( "Uninstall" );


	global $wpdb;
	$pfms_wp = PandoraFMS_WP::getInstance();
	
	error_log( "Uninstall" );

	//DELETE TABLES--------------------------------

	// Table "audit_users_weak_password"
	$tablename = $wpdb->prefix . $pfms_wp->prefix . "audit_users_weak_password";
	$sql = "DROP TABLE IF EXISTS `$tablename`";
	$wpdb->query($sql);
	
	// Table "access_control"
	$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
	$sql = "DROP TABLE IF EXISTS `$tablename`";
	$wpdb->query($sql);	
	
	// Table "user_stats"
	$tablename = $wpdb->prefix . $pfms_wp->prefix . "user_stats";
	$sql = "DROP TABLE IF EXISTS `$tablename`";
	$wpdb->query($sql);
	
	// Table "list_files"
	$tablename = $wpdb->prefix . $pfms_wp->prefix . "filesystem";
	$sql = "DROP TABLE IF EXISTS `$tablename`";
	$wpdb->query($sql);


	//DELETE OPTIONS------------------------------

	delete_option($pfms_wp->prefix . "installed");
	delete_option($pfms_wp->prefix . "audit_passwords");
	delete_option($pfms_wp->prefix . "installed_themes");
	delete_option($pfms_wp->prefix . "installed_plugins");
	delete_option($pfms_wp->prefix . "activated_rename_login");
	delete_option($pfms_wp->prefix . "audit_files");
	delete_option($pfms_wp->prefix . "installed_htaccess");
	delete_option($pfms_wp->prefix . "installed_htaccess_file");
	delete_option($pfms_wp->prefix . "installed_robot_txt");
	delete_option($pfms_wp->prefix . "installed_robots_txt_file");
	delete_option("pfmswp-options-filesystem");
	delete_option("pfmswp-options");		
	delete_option("pfmswp-options-access_control");
	delete_option($pfms_wp->prefix . "last_version_downloaded_targz");
	delete_option("pfmswp-options-system_security");











?>