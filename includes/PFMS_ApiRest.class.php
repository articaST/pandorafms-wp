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


$plugin_dir_path = plugin_dir_path(__FILE__);

//=== INIT === INCLUDES ================================================
require_once($plugin_dir_path . "/includes/PandoraFMS_WP.class.php");
//require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_AdminPages.class.php");
//require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_Widget_Dashboard.class.php");
//require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_Footer.class.php");
//require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_GoogleAnalytics.class.php");
//require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_Hooks.class.php");

//require_once(ABSPATH . "wp-admin/includes/class-wp-upgrader.php");
//require_once(ABSPATH . "wp-admin/includes/file.php");
//require_once(ABSPATH . "wp-admin/includes/template.php");
//=== END ==== INCLUDES ================================================


	
	//=== INIT === API REST CODE =======================================
	private function apirest_check_authentication() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$return = 0;
		
		$username = "";
		if (isset($_SERVER['PHP_AUTH_USER']))
			$username = $_SERVER['PHP_AUTH_USER'];
		$password = "";
		if (isset($_SERVER['PHP_AUTH_PW']))
			$password = $_SERVER['PHP_AUTH_PW'];
		
		if (($options['api_password'] === $password)
			&& ('admin' === $username)) {
			
			$remote_ip = $_SERVER['REMOTE_ADDR'];
			
			$list_api_ips = $options['api_ip'];
			
			$list_api_ips = str_replace("\r", "\n", $list_api_ips);
			$list_api_ips = explode("\n", $list_api_ips);
			if (empty($list_api_ips))
				$list_api_ips = array();
			$list_api_ips = array_filter($list_api_ips);
			
			if (array_search("*", $list_api_ips) !== false) {
				$return = 1;
			}
			elseif (array_search($remote_ip, $list_api_ips) !== false) {
				$return = 1;
			}
		}
		
		return $return;
	}
	
	private function apirest_error_authentication() {
		$error = new WP_Error(
			'Unauthorized',
			'Unauthorized',
			array( 'status' => 401 ));
		
		return $error;
	}
	
	public static function apirest_online($data) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!$pfms_wp->apirest_check_authentication()) {
			return $pfms_wp->apirest_error_authentication();
		}
		else {
			return 1;
		}
	}
	
	public static function apirest_site_name($data) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!$pfms_wp->apirest_check_authentication()) {
			return $pfms_wp->apirest_error_authentication();
		}
		else {
			return get_bloginfo('name');
		}
	}
	
	public static function apirest_version($data) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!$pfms_wp->apirest_check_authentication()) {
			return $pfms_wp->apirest_error_authentication();
		}
		else {
			$plugins = get_plugins();
			$pfms_wp->debug($plugins);
			return $plugins[$pfms_wp->name_dir_plugin . '/pandorafms-wp.php']['Version'];
		}
	}
	
	public static function apirest_wp_version($data) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!$pfms_wp->apirest_check_authentication()) {
			return $pfms_wp->apirest_error_authentication();
		}
		else {
			return get_bloginfo('version');
		}
	}
	
	public static function apirest_admin_user($data) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!$pfms_wp->check_admin_user_enabled()) {
			return 1;
		}
		else {
			return 0;
		}
	}
	
	public static function apirest_upload_code_protect($data) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		return (int)get_option($pfms_wp->prefix . "installed_htaccess", 0);
	}
	
	public static function apirest_robots_protect($data) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		return (int)get_option($pfms_wp->prefix . "installed_robot_txt", 0);
	}
	
	public static function apirest_wp_generator_protect($data) {
		$options_system_security = get_option('pfmswp-options-system_security');
		
		return (int)$options_system_security['wp_generator_disable'];
	}
	

	public static function apirest_failed_login_lockout($data) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$return = array();
		$return['status'] = 0;
		$return['users'] = array();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		
		$rows = $wpdb->get_results(
			"SELECT *
			FROM `" . $tablename . "`
			WHERE type = 'login_lockout' AND
				timestamp > date_sub(NOW(), INTERVAL 5 MINUTE)
			ORDER BY timestamp DESC");
		
		if (empty($rows)) {
			$return['status'] = 1;
		}
		else {
			$return['status'] = 0;
			
			foreach ($rows as $row) {
				preg_match(
					"/User \[(.*)\] login lockout after \[([0-9]+)\] attempts./",
					$row->data, $matches);
				
				$return['users'][] = $matches[1];
			}
		}
		
		return $return;
	}
	
	public static function apirest_password_audit($data) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$return = array();
		$return['status'] = 0;
		$return['users'] = array();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "audit_users_weak_password";
		$users = $wpdb->get_results("SELECT user FROM `" . $tablename . "`");
		if (empty($users)) {
			$users = array();
			$return['status'] = 1;
		}
		
		foreach ($users as $user) {
			$return['users'][] = $user->user;
		}
		
		return $return;
	}
	
	public static function apirest_new_account($data) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$return = array();
		
		$user = get_userdata($user_id);
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$users = $wpdb->get_results("
			SELECT user
			FROM `" . $tablename . "`
			WHERE type= 'user_register' AND
				timestamp > date_sub(NOW(), INTERVAL 5 MINUTE)");
		
		foreach ($rows as $row) {
			preg_match(
				"/User \[(.*)\] register./",
				$row->data, $matches);
			
			$return[] = $matches[1];
		}
		
		return $return;
	}
	
	public static function apirest_user_login($data) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$return = array();
		
		$user = get_userdata($user_id);
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$users = $wpdb->get_results("
			SELECT user
			FROM `" . $tablename . "`
			WHERE type= 'user_login' AND
				timestamp > date_sub(NOW(), INTERVAL 5 MINUTE)");
		
		foreach ($rows as $row) {
			preg_match(
				"/User \[(.*)\] login./",
				$row->data, $matches);
			
			$return[] = $matches[1];
		}
		
		return $return;
	}
	
	public static function apirest_failed_login($data) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$return = array();
		
		$user = get_userdata($user_id);
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$users = $wpdb->get_results("
			SELECT user
			FROM `" . $tablename . "`
			WHERE type= 'failed_login' AND
				timestamp > date_sub(NOW(), INTERVAL 5 MINUTE)");
		
		foreach ($rows as $row) {
			preg_match(
				"/User \[(.*)\] failed login./",
				$row->data, $matches);
			
			$return[] = $matches[1];
		}
		
		return $return;
	}
	//=== END ==== API REST CODE =======================================
	


	?>