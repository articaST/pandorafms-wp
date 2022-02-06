<?php
/*
Copyright (c) 2021 Artica PFMS 

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
require_once($plugin_dir_path . "PFMS_ApiRest.class.php");

class PandoraFMS_WP {
	//=== INIT === ATRIBUTES ===========================================
	public $prefix = 'pfms-wp::';
	private $acl_user_menu_entry = "manage_options"; // acl settings
	private $position_menu_entry = 75; //Under tools
	private $items_per_page = 25;

	/*
	 * DEBUG == 0
	 *  - The force the cron task execute now.
	 * DEBUG == 1
	 *  - The force the cron task for to execute the next time
	 */
	public $debug = 1;
	
	public $wp_login_php = false;
	
	public $name_dir_plugin = '';
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
	
	private function install() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$installed = get_option("installed", false);
		
		if (!$installed) {
			add_option($pfms_wp->prefix . "installed", true);
			$pfmswp_options = array(
								'api_password' => "pandora",
								'api_ip' => "*",
								'api_data_newer_minutes' => 60,
								'deleted_time' => 7,
								'new_time' => 7
							  ); 
			update_option("pfmswp-options", $pfmswp_options); //Por defecto, pero no se si se debe hacer aqui ?!! Es que sino no las crea al inicio
			
		}
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');		
				
		// Table "access_control"
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$sql = "CREATE TABLE IF NOT EXISTS `$tablename` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`type` varchar(60) NOT NULL DEFAULT '',
			`data` varchar(255) NOT NULL DEFAULT '',
			`timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (`id`)
			);";
		dbDelta($sql);
		
		
		// Table "user_stats"
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "user_stats";
		$sql = "CREATE TABLE IF NOT EXISTS `$tablename` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`ip_user` varchar(60) NOT NULL DEFAULT '',
			`user` varchar(60) NOT NULL DEFAULT '',
			`action` varchar(60) NOT NULL DEFAULT '',
			`count` INT NOT NULL DEFAULT 0,
			`timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (`id`)
			);";
		dbDelta($sql);
	}
	

	public function get_last_access_control() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$rows = $wpdb->get_results(
			"SELECT *
			FROM `" . $tablename . "`
			ORDER BY `timestamp` DESC
			LIMIT " . $pfms_wp->items_per_page);
		if (empty($rows))
			$rows = array();
		
		return $rows;
	}
	

	public function store_user_login($user_login, $login) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "user_stats";


		if (!empty($_SERVER['HTTP_CLIENT_IP'])){
			$ip = $_SERVER['HTTP_CLIENT_IP']; //Para IP Compartido
		}
		else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR']; //Para IP Proxy
		}
		else if (!empty($_SERVER['REMOTE_ADDR'])){
			$ip = $_SERVER['REMOTE_ADDR']; //Para IP Normal
		}
		else {
			$ip = 'unknown';
		}
		//return $ip; 

		
		if ($login) {
			$action = 'login_ok';
		}
		else {
			$action = 'login_fail';
		}
		
		$rows = $wpdb->get_results(
			"SELECT *, UNIX_TIMESTAMP(timestamp) AS unix_timestamp
			FROM `" . $tablename . "`
			WHERE user = '" . esc_sql($user_login) ."'
			ORDER BY timestamp DESC");
		
		$now = date("Ymd");
		$yesterday = date("Ymd", (time() - (24 * 60 * 60)));
		
		// --- Delete old stats ----------------------------------------
		foreach ($rows as $i => $row) {
			$row = (array)$row;
			
			$date = date("Ymd", $row['unix_timestamp']);
			
			if (($now == $date) || ($now == $yesterday)) {
				continue;
			}
			
			// Delete the row in the array and in the db
			$wpdb->delete($tablename,
				array('id' => $row['id']),
				"%d");
			
			unset($rows[$i]);
		}
		// -------------------------------------------------------------

		
		$actual_stats = null;
		foreach ($rows as $row) {
			$row = (array)$row;
			
			$date = date("Ymd", $row['unix_timestamp']);
			
			if (($now == $date) && ($row['action'] == $action)) {
				$actual_stats = $row;
			}
		}
		

		if (empty($actual_stats)) {
			$actual_stats = array();
			$actual_stats['user'] = $user_login;
			$actual_stats['ip_user'] = $ip;
			$actual_stats['action'] = $action;
			$actual_stats['count'] = 1;
			$actual_stats['timestamp'] = date('Y-m-d H:i:s');

			$id = $wpdb->insert(
				$tablename,
				$actual_stats,
				array('%s', '%s', '%s','%d', '%s'));
			$wpdb->flush();
	
		}
		else {
			$id = $actual_stats['id'];
			unset($actual_stats['id']);
			unset($actual_stats['unix_timestamp']);

			// Refresh the data
			$actual_stats['ip_user'] = $ip;
			$actual_stats['count'] = $actual_stats['count'] + 1;
			$actual_stats['timestamp'] = date('Y-m-d H:i:s');
		
			$wpdb->update(
				$tablename, //table
				$actual_stats, //values
				array('id' => $id), //where
				array('%s', '%s', '%s','%d', '%s'), //formats values
				array('%d')); //formats where

		}


	}
		
	
	//=== INIT === HOOKS CODE ==========================================
	public static function activation() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		// Check if installed
		$pfms_wp->install();
		
		$options_access_control = get_option("pfmswp-options-access_control");
		$options_filesystem = get_option("pfmswp-options-filesystem");


		if(!$options_access_control){

			$access_control_default_options = array(
												'email_new_account' => 1,
												'email_user_login' => 1,
												'email_change_email' => 1,
												'email_plugin_new' => 1,
												'email_theme_new' => 1,										
												'activate_login_rename' => 0,
												'login_rename_page' => "",
												'bruteforce_attempts' => 4,
												'bruteforce_attack_protection' => 1,
												'bruteforce_attack_attempts' => 3,
												'wait_protect_bruteforce_login_seconds' => 120, 	
												'h_recent_brute_force' => 90,																							
												'blacklist_ips' => "",											
												'url_redirect_ip_banned' => "",
												'activate_login_recaptcha' => 0,
												'site_key' => "",
												'secret' => "",									
												'disable_xmlrpc' => 0									
											); 

			update_option("pfmswp-options-access_control", $access_control_default_options);

		}


		if(!$options_filesystem){

			$filesystem_default_options = array(
												'check_filehash_svn' => 1,
												'blacklist_files' => 'PandoraFMS_WP.class.php
/plugins/akismet/
.png
.jpg
.gif',
												'scan_infected_files' => 1,
												'send_email_files_modified' => 1								
											); 

			update_option("pfmswp-options-filesystem", $filesystem_default_options);

		} 
	}
	
	
	public static function deactivation() {
		error_log( "Deactivation" );
	}
	
	
	public static function rest_api_init() {
		error_log("rest_api_init");	

		register_rest_route('pandorafms_wp', '/online', 
			array(
				'methods' => 'GET', 
				'callback' => array('PFMS_ApiRest', 'apirest_online'), 
			)
		);
		
		register_rest_route('pandorafms_wp', '/site_name',
			array(
				'methods' => 'GET',
				'callback' => array('PFMS_ApiRest','apirest_site_name') 
			)
		);
		
		register_rest_route('pandorafms_wp', '/version',
			array(
				'methods' => 'GET',
				'callback' => array('PFMS_ApiRest', 'apirest_version')
			)
		);
		
		register_rest_route('pandorafms_wp', '/wp_version',
			array(
				'methods' => 'GET',
				'callback' => array('PFMS_ApiRest', 'apirest_wp_version')
			)
		);
		
		register_rest_route('pandorafms_wp', '/admin',
			array(
				'methods' => 'GET',
				'callback' => array('PFMS_ApiRest', 'apirest_admin_user')
			)
		);
								
		
		register_rest_route('pandorafms_wp', '/new_account',
			array(
				'methods' => 'GET',
				'callback' => array('PFMS_ApiRest', 'apirest_new_account')
			)
		);

		register_rest_route('pandorafms_wp', '/theme_registered',
			array(
				'methods' => 'GET',
				'callback' => array('PFMS_ApiRest', 'apirest_theme_registered')
			)
		);
		
		register_rest_route('pandorafms_wp', '/plugin_registered',
			array(
				'methods' => 'GET',
				'callback' => array('PFMS_ApiRest', 'apirest_plugin_registered')
			)
		);

		register_rest_route('pandorafms_wp', '/new_posts',
			array(
				'methods' => 'GET',
				'callback' => array('PFMS_ApiRest', 'apirest_check_new_posts')
			)
		);

		register_rest_route('pandorafms_wp', '/new_comments',
			array(
				'methods' => 'GET',
				'callback' => array('PFMS_ApiRest', 'apirest_check_new_comments')
			)
		);

		register_rest_route('pandorafms_wp', '/plugin_update',
			array(
				'methods' => 'GET',
				'callback' => array('PFMS_ApiRest', 'apirest_check_plugin_update')
			)
		);

		register_rest_route('pandorafms_wp', '/core_update',
			array(
				'methods' => 'GET',
				'callback' => array('PFMS_ApiRest', 'apirest_check_core_update')
			)
		);

		register_rest_route('pandorafms_wp', '/user_login',
			array(
				'methods' => 'GET',
				'callback' => array('PFMS_ApiRest', 'apirest_user_login')
			)
		);
		
		register_rest_route('pandorafms_wp', '/failed_login',
			array(
				'methods' => 'GET',
				'callback' => array('PFMS_ApiRest', 'apirest_failed_login')
			)
		);

		// Total users

				
	}


	public static function init() {
		$pfms_wp = PandoraFMS_WP::getInstance();

		$pfms_wp->check_new_themes();
		$pfms_wp->check_new_plugins();
	
		$options_system_security = get_option('pfmswp-options-system_security');
		$options_access_control = get_option('pfmswp-options-access_control');

		
		// === INIT === Ban the IPs blacklist_ips ======================    


		if (!empty($_SERVER['HTTP_CLIENT_IP'])){
			$ip = $_SERVER['HTTP_CLIENT_IP']; //Para IP Compartido
		}
		else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR']; //Para IP Proxy
		}
		else if (!empty($_SERVER['REMOTE_ADDR'])){
			$ip = $_SERVER['REMOTE_ADDR']; //Para IP Normal
		}
		else {
			$ip = 'unknown';
		}		

		//Code footer
	
		
		//=== INIT === EVENT HOOKS =====================================
		add_action("user_register", array('PandoraFMS_WP', 'user_register'));
		add_action("wp_login", array('PandoraFMS_WP', 'user_login'));	
		add_action("wp_login_failed", array('PandoraFMS_WP', 'user_login_failed'));
		
		//=== END ==== EVENT HOOKS =====================================	
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
		
		register_setting(
			"pfmswp-settings-group-options-monitoring",
			"pfmswp-options-monitoring",
			array("PandoraFMS_WP", "sanitize_options_monitoring"));
	
	
	
		// Added script
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_style("wp-jquery-ui-dialog");
		
		wp_enqueue_script(
			'my_custom_script',
			plugin_dir_url( __FILE__ ) . '../js/jquery.scrollTableBody-1.0.0.js');
	}
	

	// Added script
	public static function my_wp_enqueue_script(){
			
	    wp_enqueue_script(
			'admin_scripts',
			plugin_dir_url( __FILE__ ) . '../js/pfms_admin_js.js'); //My JQuery functions

	}


	// Minimum version of Wordpress to run the API
	public static function show_message_version_wp() {		
		$pfms_wp = PandoraFMS_WP::getInstance();
			
		if( substr(get_bloginfo('version'), 0, 3) < '4.6' ){
    	    echo '<div id="message" class="notice notice-warning is-dismissible">	   
        			<p>To use the Wordpress API REST, you need the version 4.6 as a minimum.</p>
        	     </div>';
	    }
	    elseif ( substr(get_bloginfo('version'), 0, 3) < '4.7' ){
	    	echo '<div id="message" class="notice notice-warning is-dismissible">
        			<p>To use the Wordpress API REST, you need to install the plugin <a href="https://es.wordpress.org/plugins/rest-api/">WP REST API (Version 2)</a> </p>
        	     </div>';
	    }
	 
	}


	public static function user_register($user_id) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();		
		$user = get_userdata($user_id);
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$return = $wpdb->insert(
			$tablename,
			array(
				'type' => 'user_register',
				'data' =>
					sprintf("User [%s] register.",
						esc_sql($user->user_login)),
				'timestamp' => date('Y-m-d H:i:s')),
			array('%s', '%s', '%s'));
	}
	

	public static function verify_user_exists($user_login){
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();

		$tablename_users = $wpdb->prefix . "users";
		$users = $wpdb->get_results( "SELECT user_login FROM `" . $tablename_users . "` " );
		$users = json_decode(json_encode($users), True); //convertir stdclass en array
		

		$array = array();

		foreach ($users as $key => $value) {
			$index = 'user_login';
			$array_users[] = $value[$index];
		}

		$array_users = array_merge($array_users,$array);		 
		$verify_user_exists = in_array($user_login, $array_users);

		return $verify_user_exists;
	}


	public static function user_login_failed($user_login) {
		global $wpdb,$msg;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		
		$verify_user_exists = $pfms_wp->verify_user_exists($user_login);

		if($verify_user_exists == true){

			$pfms_wp->store_user_login($user_login, false);
			
			$return = $wpdb->insert(
				$tablename,
				array(
					'type' => 'failed_login',
					'data' =>
						sprintf("User [%s] failed login.",
							esc_sql($user_login)),
					'timestamp' => date('Y-m-d H:i:s')),
				array('%s', '%s', '%s'));
			
			error_log("user_login_failed");
		}// If user exists
	}

	public function check_new_plugins() {
		require_once(ABSPATH . "/wp-admin/includes/plugin.php");
		
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$last_installed_plugins = get_option($pfms_wp->prefix . "installed_plugins", false);
		
		$installed_plugins = get_plugins();
		$temp = array();
		foreach ($installed_plugins as $plugin) {
			$temp[] = $plugin['Name'];
		}
		$installed_plugins = $temp;
		
		if (empty($last_installed_plugins)) {
			add_option($pfms_wp->prefix . "installed_plugins", $installed_plugins);
		}
		else {
			$new_plugins = array();
			foreach ($installed_plugins as $plugin) {
				if (array_search($plugin, $last_installed_plugins) === false)
					$new_plugins[] = $plugin;
			}
			
			update_option($pfms_wp->prefix . "installed_plugins", $installed_plugins);
		}
		
		if (!empty($new_plugins)) {
			foreach ($new_plugins as $new_plugin) {
				$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
				$return = $wpdb->insert(
					$tablename,
					array(
						'type' => 'new_plugin',
						'data' =>
							sprintf("New plugin [%s].",	$new_plugin),
						'timestamp' => date('Y-m-d H:i:s')),
					array('%s', '%s', '%s'));
			}
		}
	}

	
	public function check_new_themes() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		
		
		$last_installed_themes = get_option($pfms_wp->prefix . "installed_themes", false);
		
		$installed_themes = wp_get_themes();
		$temp = array();
		foreach ($installed_themes as $theme) {
			$temp[] = $theme->get('Name');
		}
		$installed_themes = $temp;
		
		if (empty($last_installed_themes)) {
			add_option($pfms_wp->prefix . "installed_themes", $installed_themes);
		}
		else {
			$new_themes = array();
			foreach ($installed_themes as $theme) {
				if (array_search($theme, $last_installed_themes) === false)
					$new_themes[] = $theme;
			}
			
			update_option($pfms_wp->prefix . "installed_themes", $installed_themes);
		}
		
		if (!empty($new_themes)) {
			foreach ($new_themes as $new_theme) {
				$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
				$return = $wpdb->insert(
					$tablename,
					array(
						'type' => 'new_theme',
						'data' =>
							sprintf( "New theme [%s]",
								esc_sql($new_theme)),
						'timestamp' => date('Y-m-d H:i:s')),
					array('%s', '%s', '%s'));
			}
		}
	}
	
	
	public function use_trailing_slashes() {
		return '/' === substr( get_option( 'permalink_structure' ), -1, 1 );
	}
	

	public function user_trailingslashit( $string ) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		return $pfms_wp->use_trailing_slashes() ?
			trailingslashit( $string ) : untrailingslashit( $string );
	}
	
	private function set_default_options() {
		$default_options = array();
		
		$default_options['api_password'] = "pandora";
		$default_options['api_ip'] = "";
		$default_options['api_data_newer_minutes'] = 60;
		$default_options['bruteforce_attack_protection'] = 1;
		$default_options['bruteforce_attack_attempts'] = 5;
		$default_options['wait_protect_bruteforce_login_seconds'] = 120; 
		$default_options['h_recent_brute_force'] = 90;
		$default_options['blacklist_ips'] = "";
		$default_options['enabled_check_admin'] = 1;
		$default_options['enabled_wordpress_updated'] = 1;
		$default_options['enabled_plugins_updated'] = 1;
		$default_options['blacklist_plugins_check_update'] = "";
		return $default_options;
	}

	
	public static function sanitize_options($options) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!is_array($options) || empty($options) || (false === $options))
			return $pfms_wp->set_default_options();
		
	
		if (!isset($options['api_password']))
			$options['api_password'] = "";

		if (!isset($options['api_ip']))
			$options['api_ip'] = "";

		if (!isset($options['api_data_newer_minutes']))
			$options['api_data_newer_minutes'] = 60;

		return $options;
	}
	
	

	public static function sanitize_options_monitoring($options) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!is_array($options) || empty($options) || (false === $options))
			return $pfms_wp->set_default_options();	
		
		return $options;
	}

	public function debug($var) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!$pfms_wp->debug)
			return;
		
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
		
		$icon = plugins_url("images/icon.png", str_replace( "includes/", "", __FILE__));
		
		add_menu_page(
			_("PandoraFMS WP : Dashboard"),
			_("PandoraFMS WP"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu",
			array("PFMS_AdminPages", "show_dashboard"),
			$icon,
			$pfms_wp->position_menu_entry);
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			_("PandoraFMS WP : Dashboard"), 
			_("Dashboard"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu",
			array("PFMS_AdminPages", "show_dashboard"));
		
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			_("PandoraFMS WP : Audit records"),
			_("Audit records"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu_access_control",
			array("PFMS_AdminPages", "show_access_control"));
		

		add_submenu_page(
			"pfms_wp_admin_menu",
			_("PandoraFMS WP : General Setup"),
			_("General Setup"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu_general_setup",
			array("PFMS_AdminPages", "show_general_setup"));

	}

	
	public function get_list_login_lockout() {
		global $wpdb;
		
		$return = array();
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$rows = $wpdb->get_results(
			"SELECT *
			FROM `" . $tablename . "`
			WHERE type = 'login_lockout'
			ORDER BY `timestamp` DESC");
		if (empty($rows))
			$rows = array();
		
		foreach ($rows as $row) {
			preg_match(
				"/User \[(.*)\] login lockout after \[([0-9]+)\] attempts./",
				$row->data, $matches);
			
			$return[] = array(
				'user' => $matches[1],
				'count' => $matches[2],
				'time' => $row->timestamp);
		}
		
		return $return;
	}
	
	public function brute_force_attempts($api_data_newer_minutes) { 

		global $wpdb;
		$pfms_wp = PandoraFMS_WP::getInstance();

		//error_log('brute_force_attempts');

		// pfmswp-options-access_control[bruteforce_attack_attempts] = Maximum number of attempts
		// $h_recent_brute_force = Time in which these attempts happen
		// pfmswp-options-access_control[wait_protect_bruteforce_login_seconds] = Locks the user during this time (120 seconds default)

		$time_in_seconds = $api_data_newer_minutes * 60;

		$options_access_control = get_option('pfmswp-options-access_control');
		
		$return = 0;


		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";

		$fails_in_interval = $wpdb->get_results(
			"SELECT *
			FROM `" . $tablename . "`
			WHERE 
			(type = 'failed_login' AND timestamp > date_sub(NOW(), INTERVAL $time_in_seconds SECOND) )
			OR
			(type = 'login_lockout' AND timestamp > date_sub(NOW(), INTERVAL $time_in_seconds SECOND) )
			ORDER BY timestamp DESC");

			if ( count($fails_in_interval) >= $options_access_control['bruteforce_attack_attempts']  ) {
				$return = 0; //Return 0 (verde)
			}
			else{
				$return = 1; //Return 1 (rojo) if there aren't lockouts in the last $api_data_newer_minutes seconds.
			}

			
		return $return;
		
	}
	

	//Submenu Dashboard
	public function get_dashboard_data() {
		$pfms_wp = PandoraFMS_WP::getInstance();		
		$pfms_api = PFMS_ApiRest::getInstance();

		$options_system_security = get_option('pfmswp-options-system_security');
		$options_access_control = get_option('pfmswp-options-access_control');
		$options = get_option('pfmswp-options');
		
		$return = array();
		

		// === Monitoring ==============================================
		
		
		$return['monitoring'] = array();
		$return['monitoring']['enabled_check_admin'] =
			$options_system_security['enabled_check_admin'];
		if ($options_system_security['enabled_check_admin']) {
			$return['monitoring']['check_admin'] = $this->check_admin_user_enabled();
		}
		
		// Check is there any wordpress update.
		wp_version_check(array(), true);
		$update = get_site_transient('update_core');
		
		$return['monitoring']['wordpress_updated'] = 0;
		if (!empty($update)) {
			if (!empty($update->updates)) {
				
				$update->updates = (array)$update->updates;
				$updates = reset($update->updates);
				
				if (version_compare($updates->version, $update->version_checked) == 0) {
					$return['monitoring']['wordpress_updated'] = 1;
				}
			}
		}
		
		$pending_plugins_update = $pfms_wp->check_plugins_pending_update();
		$return['monitoring']['plugins_updated'] = empty($pending_plugins_update);
		$return['monitoring']['api_rest_plugin'] = $pfms_wp->check_api_rest_plugin(); 
		$return['monitoring']['wordpress_version'] = get_bloginfo('version');
		$plugins = get_plugins();
		$return['monitoring']['pandorafms_wp_version'] =
		$plugins[$pfms_wp->name_dir_plugin . '/pandorafms-wp.php']['Version'];
		$return['monitoring']['wordpress_sitename'] = get_bloginfo('name');
		$return['monitoring']['brute_force_attempts'] = $pfms_wp->brute_force_attempts($options['api_data_newer_minutes']);

		return $return;

	}
	
	//=== INIT === CHECKS ==============================================


 	public function get_user_count() {
 		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$sql = "select count(ID) AS count FROM `" . $wpdb->prefix . "users" . "` WHERE user_status = 0;";
		$count = $wpdb->get_results($sql);
		return $count[0]->count;
 	}

	public function get_count_posts_last_day() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$sql = "
			SELECT COUNT(*) AS count
			FROM `" . $wpdb->prefix . "posts" . "`
			WHERE TIMESTAMPDIFF(HOUR, post_date, now()) < 25 AND
				post_status = 'publish'";
		
		$count = $wpdb->get_results($sql);
		
		return $count[0]->count;
	}

	public function get_count_comments_last_day() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$sql = "
			SELECT COUNT(*) AS count
			FROM `" . $wpdb->prefix . "comments" . "`
			WHERE TIMESTAMPDIFF(HOUR, comment_date, now()) < 25 AND comment_approved = 1";
		
		$count = $wpdb->get_results($sql);
		
		return $count[0]->count;
	}


	// This is public for the calls from PFMS_ApiRest
	public function check_admin_user_enabled() {  
		//Check all users (included the disabled users because they can return to enabled)
		$user = get_user_by('login', 'admin');
		
		return empty($user);
	}


	// This is public for the calls from PFMS_ApiRest
	public function check_plugins_pending_update() {
		
		$pending_update_plugins = array();
		
		wp_update_plugins();
		$update_plugins = get_site_transient( 'update_plugins' );
		
		if (!empty($update_plugins)) {
			if (!empty($update_plugins->response)) {
				$plugins = (array)$update_plugins->response;
				
				$options = get_option('pfmswp-options-system_security');
				$blacklist_plugins_check_update =
					$options['blacklist_plugins_check_update'];
				$blacklist_plugins_check_update = str_replace(
					"\r", "\n", $blacklist_plugins_check_update);
				$blacklist_plugins_check_update = explode("\n",
					$blacklist_plugins_check_update);
				if (empty($blacklist_plugins_check_update))
					$blacklist_plugins_check_update = array();
				$blacklist_plugins_check_update =
					array_filter($blacklist_plugins_check_update);
				
				foreach ($plugins as $plugin) {
					$plugin = (array)$plugin;
					$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin['plugin']);
					$plugin_name = $plugin_data['Name'];
					
					if (array_search($plugin_name, $blacklist_plugins_check_update) !== false) {
						continue;
					}
					
					$pending_update_plugins[] = $plugin_name;
				}
			}
		}
		
		return $pending_update_plugins;
	}

	// This is public for the calls from PFMS_ApiRest
	public function api_new_themes() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$api_data_newer_minutes = $options['api_data_newer_minutes'];
		$return = array();

		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$themes = $wpdb->get_results("
			SELECT data
			FROM `" . $tablename . "`
			WHERE type= 'new_theme' AND
				timestamp > date_sub(NOW(), INTERVAL $api_data_newer_minutes MINUTE)"); 

		foreach ($themes as $row) {
			preg_match(
				"/New theme \[(.*)\]./",
				$row->data, $matches);
			
			$return[] = $matches[1]; 
		}
		
		if(empty($return)){
			return 1;  
		}
		else{
			return 0; //There are new themes
		}
	}

	// This is public for the calls from PFMS_ApiRest
	public function api_new_plugins() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$api_data_newer_minutes = $options['api_data_newer_minutes'];
		$return = array();

		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$plugins = $wpdb->get_results("
			SELECT data
			FROM `" . $tablename . "`
			WHERE type= 'new_plugin' AND
				timestamp > date_sub(NOW(), INTERVAL $api_data_newer_minutes MINUTE)"); 

		foreach ($plugins as $row) {
			preg_match(
				"/New plugin \[(.*)\]./",
				$row->data, $matches);
			
			$return[] = $matches[1]; 
		}

		//$pfms_wp->debug($return);
		if(empty($return)){
			return 1;
		}
		else{
			return 0; //There are new plugins
		}
	}


	// Checks if the API REST plugin is installed. From version 4.7 is not necessary
	private function check_api_rest_plugin() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$plugins = get_plugins();
		
		$return = 0;

		if(get_bloginfo('version') >= '4.7'){
			$return = 1;
		}

		foreach ($plugins as $plugin) {
			if ($plugin['Name'] == "WP REST API") {
				$return = 1;
				break;
			}
		}
		

		return $return; //0 if is not installed
	}
	//=== END ==== CHECKS ==============================================
	
	
	//=== INIT === CRON HOOKS CODE =====================================

	
	public static function cron_clean_logs() {
		global $wpdb;
		
		error_log("cron_clean_logs"); // This cron is executed once a day
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$deleted_time = $options['deleted_time'];
		$new_time = $options['new_time'];


		//Delete table user_stats after 7 days
		$table_user_stats = $wpdb->prefix . $pfms_wp->prefix . "user_stats";	
		$sql = "DELETE
			FROM `" . $table_user_stats . "`
			WHERE timestamp < date_sub(NOW(), INTERVAL 7 DAY);";
		$result = $wpdb->query($sql);
		

		//Delete table access_control once a week
		$table_access_control = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$sql = "DELETE
			FROM `" . $table_access_control . "`
			WHERE timestamp < date_sub(NOW(), INTERVAL 7 DAY);";
		$result = $wpdb->query($sql);
	}


	public static function user_login($user_login) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$pfms_wp->store_user_login($user_login, true);
		
		delete_transient("pfms_wp::bruteforce_attempts-".$user_login);
		//Delete the transient (attemps) because the login is correct 
		
		$user = get_user_by('login', $user_login);
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$return = $wpdb->insert(
			$tablename,
			array(
				'type' => 'user_login',
				'data' =>
					sprintf("User [%s] login.",
						esc_sql($user->user_login)),
				'timestamp' => date('Y-m-d H:i:s')),
			array('%s', '%s', '%s'));
	}
	

	//Send an email when any user change the email
	public static function user_change_email($user_id, $old_user_data) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$user = get_userdata($user_id);
		
		$old_email = $old_user_data->data->user_email;
		$new_email = $user->data->user_email;
		
		if ($old_email === $new_email)
			return;
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$return = $wpdb->insert(
			$tablename,
			array(
				'type' => 'user_change_email',
				'data' =>
					sprintf(
						("User [%s] with old email [%s] and new email [%s]."),
						esc_sql($user->user_login),
						esc_sql($old_email),
						esc_sql($new_email)),
				'timestamp' => date('Y-m-d H:i:s')),
			array('%s', '%s', '%s'));
	}

	//=== END ==== CRON HOOKS CODE =====================================
	
	
	//=== INIT === AJAX HOOKS CODE =====================================
	public static function ajax() {
		error_log("ajax");
		?>

		<script type="text/javascript" >

			function show_api_rest_plugin() {
				var dialog_weak_user =
					jQuery("<div id='dialog_' title='<?php esc_attr_e("API REST Plugin Installation");?>' />")
						.html('<?php esc_attr_e("You need to install the JSON REST API plugin. Or if you have version 4.7 of Wordpress or higher, the API REST is included in the core, and you don't need to install anything."); ?>')
						.appendTo("body");
				
				dialog_weak_user.dialog({
					'dialogClass' : 'wp-dialog',
					'height': 200,
					'width' : '25%',
					'modal' : true,
					'autoOpen' : false,
					'closeOnEscape' : true})
					.dialog('open');
			}
			
			function show_activated_rename_login() {
				var dialog_weak_user =
					jQuery("<div id='dialog_' title='<?php esc_attr_e("Help rename login plugin");?>' />")
						.html('<?php esc_html_e("If it is activated and there is a cross, maybe do you check the Apache (or whatever that installed as the http server) configuration for to enable AllowOverride.");?>')
						.appendTo("body");
				
				dialog_weak_user.dialog({
					'dialogClass' : 'wp-dialog',
					'height': 200,
					'width' : '25%',
					'modal' : true,
					'autoOpen' : false,
					'closeOnEscape' : true})
					.dialog('open');
			}

		</script>

		<?php
	}
	

	public static function ajax_send_test_email(){

		$pfms_wp = PandoraFMS_WP::getInstance();

		$pfms_wp->send_test_email(); 

		wp_die();

	}


	private function send_test_email(){
		error_log('send_test_email');

		$pfms_wp = PandoraFMS_WP::getInstance();

		$pfms_wp->send_email_files_changed(); 

	}


	

	public static function ajax_check_admin_user_enabled() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if ($pfms_wp->check_admin_user_enabled()) {
			echo json_encode(array('result' => 1));
		}
		else {
			echo json_encode(array('result' => 0));
		}
		
		wp_die();
	}
	

	public static function ajax_check_plugins_pending_update() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$plugins = $pfms_wp->check_plugins_pending_update();
		
		if (empty($plugins)) {
			echo json_encode(array('result' => 1, 'plugins' => $plugins));
		}
		else {
			echo json_encode(array('result' => 0, 'plugins' => $plugins));
		}
		
		wp_die();
	}
	
	//=== END ==== AJAX HOOKS CODE =====================================

}



?>