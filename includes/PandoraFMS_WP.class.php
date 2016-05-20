<?php
/*
Copyright (c) 2016-2016 Artica Soluciones Tecnologicas

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

class PandoraFMS_WP {
	//=== INIT === ATRIBUTES ===========================================
	//=== INIT === ACL SETTINGS ========================================
	private $acl_user_menu_entry = "manage_options";
	//=== END ==== ACL SETTINGS ========================================
	private $position_menu_entry = 75; //Under tools
	//=== END ==== ATRIBUTES ===========================================
	
	
	//=== INIT === SINGLETON CODE ======================================
	private static $instance = null;
	
	public static function getInstance() {
		if (!self::$instance instanceof self) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	//=== END ==== SINGLETON CODE ======================================
	
	
	private function __construct() {
	}
	
	
	//=== INIT === HOOKS CODE ==========================================
	public static function activation() {
		// Check if installed
			error_log( "Install" );
		// Only active the plugin again
	}
	
	public static function deactivation() {
		error_log( "Deactivation" );
	}
	
	public static function uninstall() {
		PandoraFMS_WP::deactivation();
		error_log( "Uninstall" );
	}
	
	public static function init() {
		error_log( "Init" );
		
		// Added action for footer
		add_action('wp_footer', array('PFMS_Footer', 'show_footer'));
	}
	
	public static function admin_init() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		// Create the widget
		add_action('wp_dashboard_setup',
			array("PFMS_Widget_Dashboard", "show_dashboard"));
		
		error_log( "Admin Init" );
	}
	//=== END ==== HOOKS CODE ==========================================
	
	public static function add_admin_menu_entries() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		add_menu_page(
			__("PandoraFMS WP : Dashboard"),
			__("PandoraFMS WP"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu",
			array("PFMS_AdminPages", "show_dashboard"),
			"dashicons-admin-tools",
			$pfms_wp->position_menu_entry);
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			__("PandoraFMS WP : Dashboard"),
			__("Dashboard"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu",
			array("PFMS_AdminPages", "show_dashboard"));
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			__("PandoraFMS WP : Monitoring"),
			__("Monitoring"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu_monitoring",
			array("PFMS_AdminPages", "show_monitoring"));
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			__("PandoraFMS WP : Acccess control"),
			__("Acccess control"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu_acccess_control",
			array("PFMS_AdminPages", "show_acccess_control"));
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			__("PandoraFMS WP : System Security"),
			__("System Security"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu_system_security",
			array("PFMS_AdminPages", "show_system_security"));
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			__("PandoraFMS WP : General Setup"),
			__("General Setup"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu_general_setup",
			array("PFMS_AdminPages", "show_general_setup"));
	}
}
?>