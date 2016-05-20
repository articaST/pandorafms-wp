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
		
		//Code copied from footer-putter plugin
		switch (basename( TEMPLATEPATH ) ) {  
			case 'twentyten':
				add_action('twentyten_credits', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'twentyeleven':
				add_action('twentyeleven_credits', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'twentytwelve':
				add_action('twentytwelve_credits', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'twentythirteen':
				add_action('twentythirteen_credits', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'twentyfourteen':
				add_action('twentyfourteen_credits', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'delicate':
				add_action('get_footer', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'genesis':
				add_action('genesis_footer', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'graphene':
				add_action('graphene_footer', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'pagelines':
				add_action('pagelines_leaf', array('PandoraFMS_WP', 'show_footer'));
				break;
			default:
				add_action('wp_footer', array('PandoraFMS_WP', 'show_footer'));
				break;
		}
		
		// Added action for footer
		//~ add_action('wp_footer', array('PandoraFMS_WP', 'show_footer'));
		add_action('twentyfourteen_credits', array('PandoraFMS_WP', 'show_footer'));
	}
	
	public static function admin_init() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		// Create the widget
		add_action('wp_dashboard_setup',
			array("PFMS_Widget_Dashboard", "show_dashboard"));
		
		//Added settings
		register_setting(
			"pfmswp-settings-group",
			"pfmswp-options",
			array("PandoraFMS_WP", "sanitize_options"));
		
		error_log( "Admin Init" );
	}
	
	public static function show_footer() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		if ($options['show_footer']) {
			$pfms_footer = PFMS_Footer::getInstance();
			$pfms_footer->show_footer();
		}
	}
	//=== END ==== HOOKS CODE ==========================================
	
	private function set_default_options() {
		$default_options = array();
		
		$default_options['show_footer'] = 0;
		
		return $default_options;
	}
	
	public static function sanitize_options($options) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!is_array($options) || empty($options) || (false === $options))
			return $pfms_wp->set_default_options();
		
		return $options;
	}
	
	public function debug($var) {
		$more_info = '';
		if (is_string($var)) {
			$more_info = 'size: ' . strlen($var);
		}
		elseif (is_bool($var)) {
			$more_info = 'val: ' . 
				($var ? 'true' : 'false');
		}
		elseif (is_null($var)) {
			$more_info = 'is null';
		}
		elseif (is_array($var)) {
			$more_info = count($var);
		}
		
		ob_start();
		echo "(" . gettype($var) . ") " . $more_info . "\n";
		print_r($var);
		echo "\n\n";
		$output = ob_get_clean();
		
		error_log($output);
	}
	
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