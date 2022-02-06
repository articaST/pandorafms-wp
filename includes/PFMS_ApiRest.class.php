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

$plugin_dir_path = plugin_dir_path(__FILE__);

//=== INIT === INCLUDES ================================================
require_once($plugin_dir_path . "PandoraFMS_WP.class.php");
require_once($plugin_dir_path . "PFMS_AdminPages.class.php");
require_once($plugin_dir_path . "PFMS_Widget_Dashboard.class.php");

require_once(ABSPATH . "wp-admin/includes/class-wp-upgrader.php");
require_once(ABSPATH . "wp-admin/includes/file.php");
require_once(ABSPATH . "wp-admin/includes/template.php");
//=== END ==== INCLUDES ================================================


class PFMS_ApiRest {


	//=== INIT === SINGLETON CODE ======================================
	private static $instance = null;
	
	public static function getInstance() {
		if (!self::$instance instanceof self) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	//=== END ==== SINGLETON CODE ======================================
	

	
	//=== INIT === API REST CODE =======================================
	private function apirest_check_authentication() {
		$pfms_api = PFMS_ApiRest::getInstance();
		$pfms_wp = PandoraFMS_WP::getInstance();

		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);

		$return = 0;

		$remote_ip = $_SERVER['REMOTE_ADDR'];	
		$list_api_ips = $options['api_ip'];

		$list_api_ips = str_replace("\r", "\n", $list_api_ips);
		$list_api_ips = explode("\n", $list_api_ips);


		if( in_array($remote_ip, $list_api_ips) || array_search("*", $list_api_ips) !== false ){				

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
		return '[{"code":"rest_no_route","message":"No route was found matching the URL and request method","data":{"status":404}}]';
	}
	

	public static function apirest_online($data) {
		$pfms_api = PFMS_ApiRest::getInstance();

		if (!$pfms_api->apirest_check_authentication()) {
			return $pfms_api->apirest_error_authentication();
		}
		else {
			return 1;
		}

	}
	

	public static function apirest_site_name($data) {
		$pfms_api = PFMS_ApiRest::getInstance();
		
		if (!$pfms_api->apirest_check_authentication()) {
			return $pfms_api->apirest_error_authentication();
		}
		else {
			return get_bloginfo('name');
		}
	}
	

	public static function apirest_version($data) {
		$pfms_api = PFMS_ApiRest::getInstance();
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!$pfms_api->apirest_check_authentication()) {
			return $pfms_api->apirest_error_authentication();
		}
		else {
			$plugins = get_plugins();
			return $plugins[$pfms_wp->name_dir_plugin . '/pandorafms-wp.php']['Version'];
		}
	}

	
	public static function apirest_wp_version($data) {
		$pfms_api = PFMS_ApiRest::getInstance();
		
		if (!$pfms_api->apirest_check_authentication()) {
			return $pfms_api->apirest_error_authentication();
		}
		else {
			return get_bloginfo('version');
		}
	}
	

	public static function apirest_admin_user($data) {
		$pfms_api = PFMS_ApiRest::getInstance();
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if ($pfms_wp->check_admin_user_enabled()) { 
			return 1;
		}
		else {
			return 0; //User admin exists
		}
	}
	

	public static function apirest_new_account($data) {
		global $wpdb;

		$pfms_api = PFMS_ApiRest::getInstance();
		$pfms_wp = PandoraFMS_WP::getInstance();

		$options = get_option('pfmswp-options');
		$api_data_newer_minutes = $options['api_data_newer_minutes'];
		
		if (!$pfms_api->apirest_check_authentication()) {
			return $pfms_api->apirest_error_authentication();
		}
		else {

			$return = array();
			
			$user = get_userdata($data);
			
			$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
			$users = $wpdb->get_results("
				SELECT data
				FROM `" . $tablename . "`
				WHERE type= 'user_register' AND
					timestamp > date_sub(NOW(), INTERVAL $api_data_newer_minutes MINUTE)"); 
			
			foreach ($users as $row) {
				preg_match(
					"/User \[(.*)\] register./",
					$row->data, $matches);
				
				$return[] = $matches[1]; //If a user has registered in the last 60 minutes
			}
			
			//$pfms_wp->debug($return);
			if(empty($return)){
				return 1;
			}
			else{
				return 0; //There are new accounts
			}

		}

	}

	
	public static function apirest_theme_registered($data){
		global $wpdb;

		$pfms_api = PFMS_ApiRest::getInstance();
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$api_data_newer_minutes = $options['api_data_newer_minutes'];

		if (!$pfms_api->apirest_check_authentication()) {
			return $pfms_api->apirest_error_authentication();
		}
		
		return $pfms_wp->api_new_themes();
	}


	public static function apirest_plugin_registered($data){
		global $wpdb;

		$pfms_api = PFMS_ApiRest::getInstance();
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$api_data_newer_minutes = $options['api_data_newer_minutes'];		

		if (!$pfms_api->apirest_check_authentication()) {
			return $pfms_api->apirest_error_authentication();
		}

		return $pfms_wp->api_new_plugins();
	}


	public static function apirest_check_new_posts($data){
		global $wpdb;

		$pfms_api = PFMS_ApiRest::getInstance();
		$pfms_wp = PandoraFMS_WP::getInstance();;

		$options = get_option('pfmswp-options');
		$api_data_newer_minutes = $options['api_data_newer_minutes'];	

		if (!$pfms_api->apirest_check_authentication()) {
			return $pfms_api->apirest_error_authentication();
		}
		else {

			$tablename = $wpdb->prefix . "posts";
			$posts = "
				SELECT COUNT(*) AS count
				FROM `" . $tablename . "`
				WHERE post_status = 'publish' AND
					TIMESTAMPDIFF(MINUTE, post_date, now()) < $api_data_newer_minutes "; 

			$count = $wpdb->get_results($posts);
			return (int)$count[0]->count;	

		}

	}

	public static function apirest_check_new_comments($data){
		global $wpdb;

		$pfms_api = PFMS_ApiRest::getInstance();
		$pfms_wp = PandoraFMS_WP::getInstance();;

		$options = get_option('pfmswp-options');
		$api_data_newer_minutes = $options['api_data_newer_minutes'];	

		if (!$pfms_api->apirest_check_authentication()) {
			return $pfms_api->apirest_error_authentication();
		}
		else {

			$tablename = $wpdb->prefix . "comments";
			$comments = "
				SELECT COUNT(*) AS count
				FROM `" . $tablename . "`
				WHERE comment_approved = 1 AND
					TIMESTAMPDIFF(MINUTE, comment_date, now()) < $api_data_newer_minutes "; 

			$count = $wpdb->get_results($comments);
			return (int)$count[0]->count;			

		}

	}


	public static function apirest_check_plugin_update($data){
		$pfms_wp = PandoraFMS_WP::getInstance();
		$pfms_api = PFMS_ApiRest::getInstance();

		if (!$pfms_api->apirest_check_authentication()) {
			return $pfms_api->apirest_error_authentication();
		}
		else {
		
			$check_plugins_pending_update = $pfms_wp->check_plugins_pending_update();

			if(empty($check_plugins_pending_update)) {
				return 1; // all plugins updated
			}
			else{
				return 0; // This doesn't expire with time, if it is not updated it will be always false
			}

		}

	}


	public static function apirest_check_core_update($data){
		$pfms_wp = PandoraFMS_WP::getInstance();
		$pfms_api = PFMS_ApiRest::getInstance();

		global $wp_version; // (string) The installed version of WordPress

		if (!$pfms_api->apirest_check_authentication()) {
			return $pfms_api->apirest_error_authentication();
		}
		else {

			$update_core = get_site_transient( 'update_core' );			

			if (!empty($update_core)) {
				if (!empty($update_core->updates)) {
					$cores = (array)$update_core->updates; 

					foreach ($cores as $core) {
						$core = (array)$core;
						$core_current = $core['current']; 						
					}

				}
			}

			if($core_current == $wp_version){ 
				return 1; // cores updated			
			}
			else{
				return 0; // This doesn't expire with time, if it is not updated it will be always false
			}
 
		} 

	}


	public static function apirest_user_login($data) {
		global $wpdb;

		$pfms_api = PFMS_ApiRest::getInstance();
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$api_data_newer_minutes = $options['api_data_newer_minutes'];	

		if (!$pfms_api->apirest_check_authentication()) {
			return $pfms_api->apirest_error_authentication();
		}
		else {

			$return = array();
			
			$user = get_userdata($data);
			
			$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
			$users = $wpdb->get_results("
				SELECT data
				FROM `" . $tablename . "`
				WHERE type= 'user_login' AND
					timestamp > date_sub(NOW(), INTERVAL $api_data_newer_minutes MINUTE)"); 
			
			foreach ($users as $row) {
				preg_match(
					"/User \[(.*)\] login./",
					$row->data, $matches);
				
				$return[] = $matches[1];
			}

			if(empty($return)){
				return 1;
			}
			else{
				return 0;
			}
		}

	}

	
	public static function apirest_failed_login($data) {
		global $wpdb;

		$pfms_api = PFMS_ApiRest::getInstance();
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$api_data_newer_minutes = $options['api_data_newer_minutes'];	

		if (!$pfms_api->apirest_check_authentication()) {
			return $pfms_api->apirest_error_authentication();
		}
		else {

			$return = array();
			
			$user = get_userdata($data);
			
			$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
			$users = $wpdb->get_results("
				SELECT data
				FROM `" . $tablename . "`
				WHERE type= 'failed_login' AND
					timestamp > date_sub(NOW(), INTERVAL $api_data_newer_minutes MINUTE)"); 
			
			foreach ($users as $row) {
				preg_match(
					"/User \[(.*)\] failed login./",
					$row->data, $matches);
				
				$return[] = $matches[1];
			}
			

			if(empty($return)){
				return 1;
			}
			else{
				return 0;
			}

		}

	}

	//=== END ==== API REST CODE =======================================
	
}

	?>