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
	
	public $wait_protect_bruteforce_login_seconds = 120;
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
			
			$audit_password = array(
				'last_execution' => null,
				'status' => null);
			add_option($pfms_wp->prefix . "audit_passwords", $audit_password);
		}
		
		// The wordpress has the function dbDelta that create (or update
		// if it created previously).
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		
		// Table "audit_users_weak_password"
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "audit_users_weak_password";
		$sql = "CREATE TABLE `$tablename` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`user` varchar(60) NOT NULL DEFAULT '',
			PRIMARY KEY (`id`)
			);";
		dbDelta($sql);
		
		
		// Table "access_control"
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$sql = "CREATE TABLE `$tablename` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`type` varchar(60) NOT NULL DEFAULT '',
			`data` varchar(255) NOT NULL DEFAULT '',
			`timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`id`)
			);";
		dbDelta($sql);
		
		
		// Table "user_stats"
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "user_stats";
		$sql = "CREATE TABLE `$tablename` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`user` varchar(60) NOT NULL DEFAULT '',
			`action` varchar(60) NOT NULL DEFAULT '',
			`count` INT NOT NULL DEFAULT 0,
			`timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`id`)
			);";
		dbDelta($sql);
		
		
		// Table "list_files"
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "filesystem";
		$sql = "CREATE TABLE `$tablename` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`path` longtext NOT NULL,
			`writable_others` INT NOT NULL DEFAULT 0,
			`type` varchar(60) NOT NULL DEFAULT '',
			`status` varchar(60) NOT NULL DEFAULT '',
			`original` varchar(60) NOT NULL DEFAULT '',
			`infected` varchar(60) NOT NULL DEFAULT '',
			`sha1` varchar(60) NOT NULL DEFAULT '',
			PRIMARY KEY (`id`)
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
			$actual_stats['action'] = $action;
			$actual_stats['count'] = 1;
			$actual_stats['timestamp'] = date('Y-m-d H:i:s');
			
			$id = $wpdb->insert(
				$tablename,
				$actual_stats,
				array('%s', '%s', '%d', '%s'));
			$wpdb->flush();
		}
		else {
			$id = $actual_stats['id'];
			unset($actual_stats['id']);
			unset($actual_stats['unix_timestamp']);
			
			// Refresh the data
			$actual_stats['count'] = $actual_stats['count'] + 1;
			$actual_stats['timestamp'] = date('Y-m-d H:i:s');
			
			$wpdb->update(
				$tablename,
				$actual_stats,
				array('id' => $id),
				array('%s', '%s', '%d', '%s'),
				array('%d'));
		}
	}
	
	
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
			return $plugins['pandorafms-wp/pandorafms-wp.php']['Version'];
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
	//=== END ==== API REST CODE =======================================
	
	
	//=== INIT === HOOKS CODE ==========================================
	public static function activation() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		// Check if installed
		$pfms_wp->install();
		
		// Only active the plugin again
	}
	
	public static function deactivation() {
		error_log( "Deactivation" );
	}
	
	public static function uninstall() {
		PandoraFMS_WP::deactivation();
		error_log( "Uninstall" );
	}
	
	public static function rest_api_init() {
		error_log("rest_api_init");
		
		/*
		 * EXAMPLE A PHP CALL OF API
		 * 
		
		$process = curl_init("https://192.168.70.155/wordpress/wp-json/pandorafms_wp/online");
		$headers = array(
			'Content-Type:application/json',
			'Authorization: Basic '. base64_encode("admin:password") // <---
		);
		curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$return = curl_exec($process);
		var_dump(curl_getinfo($process));
		curl_close($process);
		
		var_dump($return);
		
		*/
		
		
		// https://<URL_WORDPRESS>/wp-json/pandorafms_wp/online
		// HTTP header
		// Authentication: Basic ' . base64_encode( 'admin' . ':' . '<PANDORA FMS API PASSWORD>' ),
		register_rest_route('pandorafms_wp', '/online',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_online')
			)
		);
		
		register_rest_route('pandorafms_wp', '/site_name',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_site_name')
			)
		);
		
		register_rest_route('pandorafms_wp', '/version',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_version')
			)
		);
		
		register_rest_route('pandorafms_wp', '/wp_version',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_wp_version')
			)
		);
		
		register_rest_route('pandorafms_wp', '/admin',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_admin_user')
			)
		);
		
		register_rest_route('pandorafms_wp', '/upload_code_protect',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_upload_code_protect')
			)
		);
		
		register_rest_route('pandorafms_wp', '/robots_protect',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_robots_protect')
			)
		);
		
		register_rest_route('pandorafms_wp', '/wp_generator_protect',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_wp_generator_protect')
			)
		);
		
		register_rest_route('pandorafms_wp', '/failed_login_lockout',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_failed_login_lockout')
			)
		);
	}
	
	public static function init() {
		error_log( "Init" );
		
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		$pfms_wp->check_new_themes();
		$pfms_wp->check_new_plugins();
		
		$options_system_security = get_option('pfmswp-options-system_security');
		
		// === INIT === Ban the IPs blacklist_ips ======================
		$ip = $_SERVER['REMOTE_ADDR'];
		$blacklist_ips = $options_system_security['blacklist_ips'];
		$blacklist_ips = str_replace("\r", "\n", $blacklist_ips);
		$blacklist_ips = explode("\n", $blacklist_ips);
		if (empty($blacklist_ips))
			$blacklist_ips = array();
		$blacklist_ips = array_filter($blacklist_ips);
		if (array_search($ip, $blacklist_ips) !== false) {
			if (empty($options_system_security['url_redirect_ip_banned']))
				die("Banned IP : " . $ip);
			else
				wp_redirect($options_system_security['url_redirect_ip_banned']);
		}
		// === END ==== Ban the IPs blacklist_ips ======================
		
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
		add_action('twentyfourteen_credits', array('PandoraFMS_WP', 'show_footer'));
		
		
		//=== INIT === EVENT HOOKS =====================================
		add_action("user_register", array('PandoraFMS_WP', 'user_register'));
		add_action("wp_login", array('PandoraFMS_WP', 'user_login'));
		add_action("profile_update", array('PandoraFMS_WP', 'user_change_email'), 10, 2);
		add_action("wp_login_failed", array('PandoraFMS_WP', 'user_login_failed'));
		add_action('login_init', array('PandoraFMS_WP', 'login_init'));
		add_action('login_enqueue_scripts', array('PandoraFMS_WP', 'login_js'));
		add_action('login_form', array('PandoraFMS_WP', 'login_form'));
		add_action('wp_authenticate', array('PandoraFMS_WP', 'login_authenticate'), 1, 2);
		//=== END ==== EVENT HOOKS =====================================
		
		if ($options_system_security['upload_htaccess']) {
			$pfms_wp->install_htaccess();
		}
		else {
			$installed_htaccess = get_option($pfms_wp->prefix . "installed_htaccess", 0);
			
			if ($installed_htaccess) {
				$pfms_wp->uninstall_htaccess();
			}
			else {
				// None
			}
		}
		
		if ($options_system_security['upload_robots_txt']) {
			$pfms_wp->install_robots_txt();
		}
		else {
			$installed_robot_txt = get_option($pfms_wp->prefix . "installed_robot_txt", 0);
			
			if ($installed_robot_txt) {
				$pfms_wp->uninstall_robots_txt();
			}
			else {
				// None
			}
		}
		
		if ($options_system_security['wp_generator_disable']) {
			for ($i = 0; $i < 11; $i++) {
				remove_action('wp_head', 'wp_generator', $i);
			}
		}
		
		if ($options_system_security['activate_login_rename']) {
			$pfms_wp->activate_login_rename($options_system_security['login_rename_page']);
		}
		else {
			$pfms_wp->deactivate_login_rename();
		}
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
			"pfmswp-settings-group-access_control",
			"pfmswp-options-access_control",
			array("PandoraFMS_WP", "sanitize_options_access_control"));
		register_setting(
			"pfmswp-settings-group-system_security",
			"pfmswp-options-system_security",
			array("PandoraFMS_WP", "sanitize_options_system_security"));
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
	
	public static function user_register($user_id) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$options_access_control = get_option('pfmswp-options-access_control');
		$options_access_control = $pfms_wp->sanitize_options_access_control($options_access_control);
		
		$user = get_userdata($user_id);
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$return = $wpdb->insert(
			$tablename,
			array(
				'type' => 'user_login',
				'data' =>
					sprintf(
						esc_sql(__("User [%s] register.")),
						$user->user_login),
				'timestamp' => date('Y-m-d H:i:s')),
			array('%s', '%s', '%s'));
		
		if (!$options_access_control['email_new_account'])
			return;
		
		$blog = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		
		if (empty($options['email_notifications']))
			$email_to = get_option('admin_email');
		else
			$email_to = $options['email_notifications'];
		
		
		$message  = sprintf(__('New account in %s:'), $blog) . "\r\n\r\n";
		$message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
		$message .= sprintf(__('Email: %s'), $user->user_email) . "\r\n";
		
		$result = wp_mail($email_to,
			sprintf(__('[%s] New account creation'), $blog),
			$message);
	}
	
	public static function user_login_failed($user_login) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$pfms_wp->store_user_login($user_login, false);
		
		
		$options_system_security = get_option('pfmswp-options-system_security');
		if ($options_system_security['bruteforce_attack_protection']) {
			
			$attempts = get_transient("pfms_wp::bruteforce_attempts");
			if ($attempts === false)
				$attempts = 0;
			else
				$attempts = (int)$attempts;
			
			$attempts++;
			
			set_transient("pfms_wp::bruteforce_attempts", $attempts, DAY_IN_SECONDS);
			
			if ($attempts >= $options_system_security['bruteforce_attack_attempts']) {
				$pfms_wp = PandoraFMS_WP::getInstance();
				
				$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
				
				$return = $wpdb->insert(
					$tablename,
					array(
						'type' => 'login_lockout',
						'data' =>
							sprintf(
								esc_sql(__("User [%s] login lockout after [%d] attempts.")),
								$user_login, $attempts),
						'timestamp' => date('Y-m-d H:i:s')),
					array('%s', '%s', '%s'));
			}
		}
		
		error_log("user_login_failed");
	}
	
	public static function user_login($user_login) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$pfms_wp->store_user_login($user_login, true);
		
		$options_system_security = get_option('pfmswp-options-system_security');
		if ($options_system_security['bruteforce_attack_protection']) {
			set_transient("pfms_wp::bruteforce_attempts", 0, DAY_IN_SECONDS);
		}
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$options_access_control = get_option('pfmswp-options-access_control');
		$options_access_control = $pfms_wp->sanitize_options_access_control($options_access_control);
		
		$user = get_user_by('login', $user_login);
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$return = $wpdb->insert(
			$tablename,
			array(
				'type' => 'user_login',
				'data' =>
					sprintf(
						esc_sql(__("User [%s] login.")),
						$user->user_login),
				'timestamp' => date('Y-m-d H:i:s')),
			array('%s', '%s', '%s'));
		
		if (!$options_access_control['email_user_login'])
			return;
		
		$blog = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		
		if (empty($options['email_notifications']))
			$email_to = get_option('admin_email');
		else
			$email_to = $options['email_notifications'];
		
		
		$message  = sprintf(__('Login user in %s:'), $blog) . "\r\n\r\n";
		$message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
		
		$result = wp_mail($email_to,
			sprintf(__('[%s] Login user %s'), $blog, $user->user_login),
			$message);
	}
	
	public static function user_change_email($user_id, $old_user_data) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$options_access_control = get_option('pfmswp-options-access_control');
		$options_access_control = $pfms_wp->sanitize_options_access_control($options_access_control);
		
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
						esc_sql(__("User [%s] with old email [%s] and new email [%s].")),
						$user->user_login,
						$old_email,
						$new_email),
				'timestamp' => date('Y-m-d H:i:s')),
			array('%s', '%s', '%s'));
		
		if (!$options_access_control['email_change_email'])
			return;
		
		$blog = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		
		if (empty($options['email_notifications']))
			$email_to = get_option('admin_email');
		else
			$email_to = $options['email_notifications'];
		
		
		$message  = sprintf(__('User email change in %s:'), $blog) . "\r\n\r\n";
		$message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
		$message .= sprintf(__('Old email: %s'), $old_email) . "\r\n\r\n";
		$message .= sprintf(__('New email: %s'), $new_email) . "\r\n\r\n";
		
		$result = wp_mail($email_to,
			sprintf(__('[%s] %s change the email'), $blog, $user->user_login),
			$message);
	}
	
	public static function login_init() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options_system_security = get_option('pfmswp-options-system_security');
		if ($options_system_security['bruteforce_attack_protection']) {
			
			$attempts = get_transient("pfms_wp::bruteforce_attempts");
			if ($attempts === false)
				$attempts = 0;
			else
				$attempts = (int)$attempts;
			
			if ($attempts >= $options_system_security['bruteforce_attack_attempts']) {
				error_log("protect bruteforce");
				set_transient("pfms_wp::bruteforce_attempts", 0, DAY_IN_SECONDS);
				sleep($pfms_wp->wait_protect_bruteforce_login_seconds);
			}
		}
		
	}
	
	public static function login_js() {
		$options = get_option('pfmswp-options-system_security');
		
		if (!$options['activate_login_recaptcha'])
			return;
		
		error_log("login_js");
		$lang = get_locale();
		?>
		<script type="text/javascript"
			src="https://www.google.com/recaptcha/api.js?hl=<?php echo $lang; ?>"></script>
		<?php
	}
	
	public static function login_form() {
		$options = get_option('pfmswp-options-system_security');
		
		if (!$options['activate_login_recaptcha'])
			return;
		
		?>
		<div class="g-recaptcha" data-sitekey="<?php echo $options['site_key']; ?>"></div>
		<?php 
	}
	
	public static function login_authenticate(&$user_login, &$user_pass) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options-system_security');
		
		if (!$options['activate_login_recaptcha'])
			return;
		
		$sitekey = $options['site_key'];
		$secret = $options['secret'];
		
		$parameters = array(
			'secret' => trim($secret),
			'response' => isset($_POST['g-recaptcha-response']) ?
				$_POST['g-recaptcha-response'] : "",
			'remoteip' => $_SERVER['REMOTE_ADDR']
		);
		$url = 'https://www.google.com/recaptcha/api/siteverify?' .
			http_build_query($parameters);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		curl_close($ch);
		
		$json_response = json_decode($response, true);
		
		$pfms_wp->debug($json_response);
		
		if (isset($json_response['success']) && true !== $json_response['success']) {
			// Delete the user_login and user_password to stop the login process
			$user_login = null;
			$user_pass = null;
			return;
		}
	}
	//=== END ==== HOOKS CODE ==========================================
	
	private function install_htaccess() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options_system_security = get_option('pfmswp-options-system_security');
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];
		$destination_dir = $upload_dir;
		
		$htacess_file = plugin_dir_path(__FILE__) .
			"../data/htaccess_file";
		
		$installed = false;
		
		// The file is from data directory of plugin
		if (!empty($destination_dir)) {
			if (!is_dir($destination_dir)) {
				$destination_dir = realpath(
					ABSPATH . $destination_dir);
			}
			
			if (is_dir($destination_dir)) {
				$installed_htaccess_file =
					$destination_dir . "/.htaccess";
				$installed = copy($htacess_file, $installed_htaccess_file);
			}
		}
		
		if ($installed) {
			update_option($pfms_wp->prefix . "installed_htaccess", (int)$installed);
			update_option($pfms_wp->prefix . "installed_htaccess_file",
				$installed_htaccess_file);
		}
	}
	
	private function uninstall_htaccess() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$installed_file = get_option($pfms_wp->prefix . "installed_htaccess_file",
			null);
		
		$install = 0;
		if (!empty($installed_file)) {
			$install = !unlink($installed_file);
		}
		
		if (!$install) {
			update_option($pfms_wp->prefix . "installed_htaccess_file", "");
		}
		update_option($pfms_wp->prefix . "installed_htaccess", (int)$install);
	}
	
	public function install_robots_txt() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options_system_security = get_option('pfmswp-options-system_security');
		$destination_dir = ABSPATH;
		
		$robots_txt_file = plugin_dir_path(__FILE__) .
			"../data/robots_txt_file";
		
		$installed = false;
		
		// The file is from data directory of plugin
		if (!empty($destination_dir)) {
			if (!is_dir($destination_dir)) {
				$destination_dir = realpath(
					ABSPATH . $destination_dir);
			}
			
			if (is_dir($destination_dir)) {
				$installed_htaccess_file =
					$destination_dir . "/robots.txt";
				$installed = copy($robots_txt_file, $installed_htaccess_file);
			}
		}
		
		if ($installed) {
			update_option($pfms_wp->prefix . "installed_robot_txt", (int)$installed);
			update_option($pfms_wp->prefix . "installed_robots_txt_file",
				$installed_htaccess_file);
		}
	}
	
	private function uninstall_robots_txt() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$installed_file = get_option($pfms_wp->prefix . "installed_robots_txt_file",
			null);
		
		$install = 0;
		if (!empty($installed_file)) {
			$install = !unlink($installed_file);
		}
		
		if (!$install) {
			update_option($pfms_wp->prefix . "installed_robots_txt_file", "");
		}
		update_option($pfms_wp->prefix . "installed_robot_txt", (int)$install);
	}
	
	public function check_new_plugins() {
		require_once(ABSPATH . "/wp-admin/includes/plugin.php");
		
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$options_access_control = get_option('pfmswp-options-access_control');
		$options_access_control = $pfms_wp->sanitize_options_access_control($options_access_control);
		
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
							sprintf(
								esc_sql(__("New plugin [%s].")),
								$new_plugin),
						'timestamp' => date('Y-m-d H:i:s')),
					array('%s', '%s', '%s'));
				
				if (!$options_access_control['email_plugin_new'])
					continue;
				
				$blog = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
				
				if (empty($options['email_notifications']))
					$email_to = get_option('admin_email');
				else
					$email_to = $options['email_notifications'];
				
				
				$message  = sprintf(__('New plugin in %s:'), $blog) . "\r\n\r\n";
				$message .= sprintf(__('Plugin: %s'), $new_plugin) . "\r\n\r\n";
				
				$result = wp_mail($email_to,
					sprintf(__('[%s] New plugin'), $blog),
					$message);
			}
		}
	}
	
	public function check_new_themes() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$options_access_control = get_option('pfmswp-options-access_control');
		$options_access_control = $pfms_wp->sanitize_options_access_control($options_access_control);
		
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
							sprintf(
								esc_sql(__("New theme [%s].")),
								$new_theme),
						'timestamp' => date('Y-m-d H:i:s')),
					array('%s', '%s', '%s'));
				
				if (!$options_access_control['email_theme_new'])
					continue;
				
				$blog = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
				
				if (empty($options['email_notifications']))
					$email_to = get_option('admin_email');
				else
					$email_to = $options['email_notifications'];
				
				
				$message  = sprintf(__('New theme in %s:'), $blog) . "\r\n\r\n";
				$message .= sprintf(__('Theme: %s'), $new_theme) . "\r\n\r\n";
				
				$result = wp_mail($email_to,
					sprintf(__('[%s] New theme'), $blog),
					$message);
			}
		}
	}
	
	private function installed_login_rename() {
		$plugins = get_plugins();
		
		$return = 0;
		foreach ($plugins as $plugin) {
			if ($plugin['Name'] == "Rename wp-login.php") {
				$return = 1;
				break;
			}
		}
		
		return $return;
	}
	
	public function use_trailing_slashes() {
			return '/' === substr( get_option( 'permalink_structure' ), -1, 1 );
		}
	
	public function user_trailingslashit( $string ) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		return $pfms_wp->use_trailing_slashes() ?
			trailingslashit( $string ) : untrailingslashit( $string );
	}
	
	public function new_url_login($url, $scheme = null) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options-system_security');
		
		if (get_option('permalink_structure')) {
			$new_url =
				$pfms_wp->user_trailingslashit(home_url('/', $scheme) .
				$options['login_rename_page']);
		}
		else {
			$new_url = home_url('/', $scheme) . '?' .
				$options['login_rename_page'];
		}
		
		
		if (strpos($url, 'wp-login.php') !== false) {
			if (is_ssl()) {
				$scheme = 'https';
			}
			
			$args = explode('?', $url);
			
			if (isset($args[1])) {
				parse_str($args[1], $args);
				$url = add_query_arg($args, $new_url);
			}
			else {
				$url = $new_url;
			}
		}
		
		return $url;
	}
	
	public function login_rename_wp_loaded() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options-system_security');
		
		if (get_option('permalink_structure')) {
			$index_wp = 
				$pfms_wp->user_trailingslashit(home_url('/'));
			
			$new_url =
				$pfms_wp->user_trailingslashit(home_url('/') .
				$options['login_rename_page']);
		}
		else {
			$index_wp = 
				home_url('/');
			
			$new_url = home_url('/') . '?' .
				$options['login_rename_page'];
		}
		
		
		if (get_option('permalink_structure')) {
			$new_url =
				$pfms_wp->user_trailingslashit(home_url('/') .
				$options['login_rename_page']);
		}
		else {
			$new_url = home_url('/') . '?' .
				$options['login_rename_page'];
		}
		
		global $pagenow;
		
		if (is_admin() &&
			!is_user_logged_in() &&
			!defined('DOING_AJAX')) {
			wp_die(
				__( 'You must log in to access the admin area.'));
		}
		
		$request = parse_url( $_SERVER['REQUEST_URI'] );
		
		if (
			$pagenow === 'wp-login.php' &&
			$request['path'] !==
				$pfms_wp->user_trailingslashit($request['path']) &&
			get_option('permalink_structure')
		) {
			wp_safe_redirect(
				$pfms_wp->user_trailingslashit($index_wp) .
					(!empty($_SERVER['QUERY_STRING']) ?
						'?' . $_SERVER['QUERY_STRING'] :
						''));
			die;
		}
		elseif ($pfms_wp->wp_login_php) {
			if (
				($referer = wp_get_referer()) &&
				strpos($referer, 'wp-activate.php') !== false &&
				($referer = parse_url($referer)) &&
				! empty($referer['query'])
			) {
				parse_str($referer['query'], $referer );
				
				if (
					! empty($referer['key']) &&
					( $result = wpmu_activate_signup($referer['key']))  &&
					is_wp_error($result) && (
						$result->get_error_code() === 'already_active' ||
						$result->get_error_code() === 'blog_taken'
				)) {
					wp_safe_redirect(
						$new_url .
						(!empty($_SERVER['QUERY_STRING']) ?
							'?' . $_SERVER['QUERY_STRING'] :
							''));
					die;
				}
			}
			
			$pagenow = 'index.php';
			
			if ( ! defined( 'WP_USE_THEMES' ) ) {
				define( 'WP_USE_THEMES', true );
			}
			
			wp();
			
			if ($_SERVER['REQUEST_URI'] ===
				$pfms_wp->user_trailingslashit(str_repeat('-/', 10))) {
				
				$_SERVER['REQUEST_URI'] =
					$pfms_wp->user_trailingslashit('/wp-login-php/');
			}
			
			require_once(ABSPATH . WPINC . '/template-loader.php');
			
			die;
		}
		elseif ($pagenow === 'wp-login.php' ) {
			global $error, $interim_login, $action, $user_login;
			
			@require_once ABSPATH . 'wp-login.php';
			
			die;
		}
	}
	
	public static function login_rename_plugins_loaded() {
		$options = get_option('pfmswp-options-system_security');
		if (!$options['activate_login_rename']) {
			return;
		}
		
		global $pagenow;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options-system_security');
		
		$request = parse_url( $_SERVER['REQUEST_URI'] );
		
		if ((
			strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false ||
			untrailingslashit($request['path']) ===
				site_url('wp-login', 'relative')
			) &&
			! is_admin()
		) {
			$pfms_wp->wp_login_php = true;
			$_SERVER['REQUEST_URI'] =
				$pfms_wp->user_trailingslashit('/' . str_repeat('-/', 10));
			$pagenow = 'index.php';
		}
		elseif (
			untrailingslashit($request['path']) ===
				home_url($options['login_rename_page'], 'relative') || (
					! get_option( 'permalink_structure' ) &&
					isset( $_GET[$options['login_rename_page']] ) &&
					empty( $_GET[$options['login_rename_page']])
			)) {
			$pagenow = 'wp-login.php';
		}
	}
	
	private function activate_login_rename($login_page) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		// === INIT === Custom hooks ===================================
		add_filter('site_url', 
			function($url, $path, $scheme, $blog_id) {
				$pfms_wp = PandoraFMS_WP::getInstance();
				
				return $pfms_wp->new_url_login($url, $scheme);
			}, 10, 4);
		
		add_filter('network_site_url',
			function($url, $path, $scheme) {
				$pfms_wp = PandoraFMS_WP::getInstance();
				
				return $pfms_wp->new_url_login($url, $scheme);
			}, 10, 3);
		
		add_filter('wp_redirect',
			function($location, $status) {
				$pfms_wp = PandoraFMS_WP::getInstance();
				
				return $pfms_wp->new_url_login($location);
			}, 10, 2);
		
		add_filter('site_option_welcome_email',
			function($value) {
				$options = get_option('pfmswp-options-system_security');
				
				return $value =
					str_replace( 'wp-login.php',
						trailingslashit($options['login_rename_page']),
						$value );
			});
		
		add_action('wp_loaded',
			function() {
				$pfms_wp = PandoraFMS_WP::getInstance();
				
				$pfms_wp->login_rename_wp_loaded();
			});
		// === END ==== Custom hooks ===================================
		
		update_option($pfms_wp->prefix . "activated_rename_login",
			array('status' => 1));
	}
	
	private function deactivate_login_rename() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		update_option($pfms_wp->prefix . "activated_rename_login",
			array('status' => 0));
	}
	
	
	private function set_default_options() {
		$default_options = array();
		
		$default_options['show_footer'] = 0;
		$default_options['email_notifications'] = "";
		$default_options['api_password'] = "";
		$default_options['api_ip'] = "";
		$default_options['api_data_newer_minutes'] = 90;
		$default_options['email_new_account'] = 1;
		$default_options['email_user_login'] = 1;
		$default_options['email_change_email'] = 1;
		$default_options['email_plugin_new'] = 1;
		$default_options['email_theme_new'] = 1;
		$default_options['enabled_check_admin'] = 0;
		$default_options['enabled_wordpress_updated'] = 0;
		$default_options['enabled_plugins_updated'] = 0;
		$default_options['upload_htaccess'] = 0;
		$default_options['upload_robots_txt'] = 0;
		$default_options['wp_generator_disable'] = 0;
		$default_options['activate_login_rename'] = 0;
		$default_options['login_rename_page'] = "login";
		$default_options['check_filehash_svn'] = 0;
		$default_options['bruteforce_attack_protection'] = 0;
		$default_options['bruteforce_attack_attempts'] = 3;
		$default_options['blacklist_plugins_check_update'] = "";
		$default_options['blacklist_ips'] = "";
		$default_options['url_redirect_ip_banned'] = "";
		$default_options['scan_infected_files'] = "";
		$default_options['activate_login_recaptcha'] = 0;
		$default_options['site_key'] = "";
		$default_options['secret'] = "";
		
		return $default_options;
	}
	
	public static function sanitize_options($options) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!is_array($options) || empty($options) || (false === $options))
			return $pfms_wp->set_default_options();
		
		$options['email_notifications'] =
			sanitize_email($options['email_notifications']);
		
		if (!isset($options['show_footer']))
			$options['show_footer'] = 0;
		
		return $options;
	}
	
	public static function sanitize_options_access_control($options) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!is_array($options) || empty($options) || (false === $options))
			return $pfms_wp->set_default_options();
		
		if (!isset($options['email_new_account']))
			$options['email_new_account'] = 0;
		if (!isset($options['email_user_login']))
			$options['email_user_login'] = 0;
		if (!isset($options['email_change_email']))
			$options['email_change_email'] = 0;
		if (!isset($options['email_plugin_new']))
			$options['email_plugin_new'] = 0;
		if (!isset($options['email_theme_new']))
			$options['email_theme_new'] = 0;
		
		return $options;
	}
	
	public static function sanitize_options_system_security($options) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!is_array($options) || empty($options) || (false === $options))
			return $pfms_wp->set_default_options();
		
		if (!isset($options['enabled_check_admin']))
			$options['enabled_check_admin'] = 0;
		
		if (!isset($options['enabled_wordpress_updated']))
			$options['enabled_wordpress_updated'] = 0;
		
		if (!isset($options['enabled_plugins_updated']))
			$options['enabled_plugins_updated'] = 0;
		
		if (!isset($options['blacklist_plugins_check_update']))
			$options['blacklist_plugins_check_update'] = "";
		
		if (!isset($options['upload_htaccess']))
			$options['upload_htaccess'] = 0;
		
		if (!isset($options['upload_robots_txt']))
			$options['upload_robots_txt'] = 0;
			
		if (!isset($options['wp_generator_disable']))
			$options['wp_generator_disable'] = 0;
		
		if (!isset($options['activate_login_rename']))
			$options['activate_login_rename'] = 0;
		
		if (!isset($options['login_rename_page']))
			$options['login_rename_page'] = "login";
		
		if (!isset($options['check_filehash_svn']))
			$options['check_filehash_svn'] = 0;
		
		if (!isset($options['bruteforce_attack_protection']))
			$options['bruteforce_attack_protection'] = 0;
		
		if (!isset($options['bruteforce_attack_attempts']))
			$options['bruteforce_attack_attempts'] = 3;
		
		if (!isset($options['blacklist_ips']))
			$options['blacklist_ips'] = "";
		
		if (!isset($options['url_redirect_ip_banned']))
			$options['url_redirect_ip_banned'] = "";
		
		if (!isset($options['scan_infected_files']))
			$options['scan_infected_files'] = 0;
		
		if (!isset($options['activate_login_recaptcha']))
			$options['activate_login_recaptcha'] = 0;
		
		if (!isset($options['site_key']))
			$options['site_key'] = "";
		
		if (!isset($options['secret']))
			$options['secret'] = "";
		
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
			_("PandoraFMS WP : Monitoring"),
			_("Monitoring"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu_monitoring",
			array("PFMS_AdminPages", "show_monitoring"));
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			_("PandoraFMS WP : Access control"),
			_("Access control"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu_access_control",
			array("PFMS_AdminPages", "show_access_control"));
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			_("PandoraFMS WP : System Security"),
			_("System Security"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu_system_security",
			array("PFMS_AdminPages", "show_system_security"));
		
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
	
	public function get_dashboard_data() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options_system_security = get_option('pfmswp-options-system_security');
		
		$return = array();
		
		// === Monitoring ==============================================
		
		
		$return['monitoring'] = array();
		$return['monitoring']['enabled_check_admin'] =
			$options_system_security['enabled_check_admin'];
		if ($options_system_security['enabled_check_admin']) {
			$return['monitoring']['check_admin'] = $this->check_admin_user_enabled();
		}
		
		// audit_passwords_strength
		$audit_password = get_option($pfms_wp->prefix . "audit_passwords",
			array(
				'last_execution' => null,
				'status' => null));
		$return['monitoring']['audit_password'] = $audit_password;
		
		// audit_files
		$audit_password = get_option($pfms_wp->prefix . "audit_files",
			array(
				'last_execution' => null,
				'status' => null));
		$return['monitoring']['audit_files'] = $audit_password;
		
		
		// Check is there any wordpress update.
		$return['monitoring']['enabled_wordpress_updated'] =
			$options_system_security['enabled_wordpress_updated'];
		if ($options_system_security['enabled_wordpress_updated']) {
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
		}
		
		
		$return['monitoring']['enabled_plugins_updated'] =
			$options_system_security['enabled_plugins_updated'];
		if ($options_system_security['enabled_plugins_updated']) {
			$pending_plugins_update = $pfms_wp->check_plugins_pending_update();
			$return['monitoring']['plugins_updated'] = empty($pending_plugins_update);
		}
		
		
		$return['monitoring']['api_rest_plugin'] = $pfms_wp->check_api_rest_plugin();
		
		$return['monitoring']['wordpress_version'] = get_bloginfo('version');
		$plugins = get_plugins();
		$return['monitoring']['pandorafms_wp_version'] =
			$plugins['pandorafms-wp/pandorafms-wp.php']['Version'];
		
		$return['monitoring']['wordpress_sitename'] = get_bloginfo('name');
		// === System security =========================================
		
		$return['system_security'] = array();
		$return['system_security']['protect_upload_php_code'] =
			(int)get_option($pfms_wp->prefix . "installed_htaccess", 0);
		$return['system_security']['installed_robot_txt'] =
			(int)get_option($pfms_wp->prefix . "installed_robot_txt", 0);
		$return['system_security']['wp_generator_disable'] =
			$options_system_security['wp_generator_disable'];
		
		$activated_rename_login = get_option(
			$pfms_wp->prefix . "activated_rename_login",
			array('status' => 0));
		if ($activated_rename_login) {
			$activated_rename_login['status'] = $pfms_wp->check_new_page_login_online();
		}
		$return['system_security']['activated_rename_login'] =
			$activated_rename_login['status'];
		
		$return['system_security']['activated_recaptcha'] =
			$options_system_security['activate_login_recaptcha'];
		
		return $return;
	}
	
	private function get_filesystem_status($directory = null) {
		$filesystem = array();
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (empty($directory))
			$directory = ABSPATH;
		
		$dir = dir($directory);
		
		while (false !== ($entry = $dir->read())) {
			if (($entry === '..'))
				continue;
			
			$path = realpath($directory . '/' . $entry);
			$perms = fileperms($path);
			
			$entry_filesystem = array();
			
			$entry_filesystem['path'] = $path;
			$entry_filesystem['writable_others'] = ($perms & 0x0002)? 1 : 0;
			
			if ($entry === '.') {
				$entry_filesystem['type'] = 'dir';
				$entry_filesystem['sha1'] = '';
				
				$filesystem[] = $entry_filesystem;
			}
			elseif (is_dir($path)) {
				$filesystem_subdir = $pfms_wp->get_filesystem_status($path);
				$filesystem = array_merge($filesystem, $filesystem_subdir);
			}
			else {
				$entry_filesystem['type'] = 'file';
				$entry_filesystem['sha1'] = sha1_file($path);
				
				$filesystem[] = $entry_filesystem;
			}
		}
		
		$dir->close();
		
		return $filesystem;
	}
	
	//=== INIT === CHECKS ==============================================
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
			WHERE TIMESTAMPDIFF(HOUR, comment_date, now()) < 25";
		
		$count = $wpdb->get_results($sql);
		
		return $count[0]->count;
	}
	
	private function audit_files_infected() {
		error_log("audit_files_infected");
		
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "filesystem";
		
		$store_filesystem = $wpdb->get_results("
			SELECT * FROM `" . $tablename . "`");
		
		foreach ($store_filesystem as $i => $store_entry) {
			$store_entry = (array)$store_entry;
			
			if ($store_entry['type'] != "file")
				continue;
			
			$fileinfo = pathinfo($store_entry['path']);
			if (!isset($fileinfo['extension']))
				continue;
			if ($fileinfo['extension'] !== 'php')
				continue;
			
			$file = file_get_contents($store_entry['path']);
			
			$wpdb->update(
				$tablename,
				array('infected' => "no"),
				array('id' => $store_entry['id']),
				array('%s'),
				array('%d'));
			
			if ((strstr($file, "eval") !== false) ||
				(strstr($file, "base64_decode") !== false) ||
				(strstr($file, '\x5f') !== false) ||
				(strstr($file, '\x65') !== false)) {
				
				// Infected
				$wpdb->update(
					$tablename,
					array('infected' => "yes"),
					array('id' => $store_entry['id']),
					array('%s'),
					array('%d'));
			}
		}
	}
	
	private function audit_files_svn_repository() {
		global $wpdb;
		global $wp_filesystem;
		
		if (!$wp_filesystem) {
			WP_Filesystem();
		}
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		
		$last_version_downloaded_targz = get_option(
			$pfms_wp->prefix . "last_version_downloaded_targz", "");
		
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];
		
		$wordpress_file =
			$upload_dir . "/wordpress-" . get_bloginfo('version') . ".zip";
		
		if ($last_version_downloaded_targz != get_bloginfo('version') ||
			!is_readable($wordpress_file)) {
			
			$url_file =
				"http://wordpress.org/wordpress-" . get_bloginfo('version') . ".zip";
			
			// Download
			$fp = fopen($wordpress_file, "w");
				$ch = curl_init($url_file);
				curl_setopt($ch, CURLOPT_TIMEOUT, 50);
				curl_setopt($ch, CURLOPT_FILE, $fp);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_exec($ch);
				$r = curl_getinfo($ch);
				curl_close($ch);
			fclose($fp);
			
			update_option(
				$pfms_wp->prefix . "last_version_downloaded_targz",
				get_bloginfo('version'));
		}
		
		$result = unzip_file($wordpress_file, sys_get_temp_dir());
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "filesystem";
		
		$url = "http://core.svn.wordpress.org/tags/" .
			get_bloginfo('version') . "/";
		
		$store_filesystem = $wpdb->get_results("
				SELECT * FROM `" . $tablename . "`");
		
		foreach ($store_filesystem as $i => $store_entry) {
			$store_entry = (array)$store_entry;
			
			if ($store_entry['type'] != "file")
				continue;
			
			$wpdb->update(
				$tablename,
				array('original' => "yes"),
				array('id' => $store_entry['id']),
				array('%s'),
				array('%d'));
			
			$file = str_replace(
				ABSPATH, sys_get_temp_dir() . "/wordpress/",
				$store_entry['path']);
			
			$sha1_remote_file = sha1_file($file);
			if ($sha1_remote_file != $store_entry['sha1']) {
				
				//~ error_log("no original");
				
				$wpdb->update(
					$tablename,
					array('original' => "no"),
					array('id' => $store_entry['id']),
					array('%s'),
					array('%d'));
			}
			else {
				//~ error_log("original");
			}
		}
	}
	
	private function audit_files() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "filesystem";
		
		$audit_files = get_option($pfms_wp->prefix . "audit_files",
			array(
				'last_execution' => null,
				'status' => null));
		
		$filesystem = $pfms_wp->get_filesystem_status();
		
		$not_changes_filesystem = true;
		
		if (is_null($audit_files['last_execution'])) {
			// Save the files only
			foreach ($filesystem as $entry) {
				$value = array(
					'path' => $entry['path'],
					'writable_others' => $entry['writable_others'],
					'type' => $entry['type'],
					'status' => 'new',
					'sha1' => $entry['sha1']);
				
				$wpdb->insert(
					$tablename,
					$value);
			}
		}
		else {
			// Clean the audit_files table from the last execution
			$store_filesystem = $wpdb->get_results("
				SELECT * FROM `" . $tablename . "`");
			
			foreach ($store_filesystem as $i => $store_entry) {
				$store_entry = (array)$store_entry;
				
				switch ($store_entry['status']) {
					case 'deleted':
						$wpdb->delete(
							$tablename,
							array('id' => $store_entry['id']));
						unset($store_filesystem[$i]);
						break;
					case 'changed':
					case 'new':
						$wpdb->update(
							$tablename,
							array('status' => ""),
							array('id' => $store_entry['id']),
							array('%s'),
							array('%d'));
						$store_filesystem[$i]->status = "";
						break;
				}
			}
			
			foreach ($filesystem as $entry) {
				$found = false;
				foreach ($store_filesystem as $i => $store_entry) {
					$store_entry = (array)$store_entry;
					
					if ($entry['path'] === $store_entry['path']) {
						$found = true;
						
						if ($store_entry['sha1'] !== $entry['sha1']) {
							// Changed
							
							$wpdb->update(
								$tablename,
								array('status' => "changed"),
								array('id' => $store_entry['id']),
								array('%s'),
								array('%d'));
							
							$not_changes_filesystem = false;
						}
						
						unset($store_filesystem[$i]);
						
						break;
					}
				}
				
				if (!$found) {
					// New
					$value = array(
						'path' => $entry['path'],
						'status' => 'new',
						'sha1' => $entry['sha1']);
					
					$wpdb->insert(
						$tablename,
						$value);
					
					$not_changes_filesystem = false;
				}
			}
			
			
			
			// Check the files unpaired because they are deleted files
			foreach ($store_filesystem as $store_entry) {
				// Deleted
				
				$wpdb->update(
					$tablename,
					array('status' => "deleted"),
					array('id' => $store_entry->id),
					array('%s'),
					array('%d'));
				
				$not_changes_filesystem = false;
			}
		}
		
		$audit_files['status'] = (int)$not_changes_filesystem;
		$audit_files['last_execution'] = time();
		
		update_option($pfms_wp->prefix . "audit_files", $audit_files);
	}
	
	private function audit_passwords_strength() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$table_user_weak_password =
			$wpdb->prefix . $pfms_wp->prefix . "audit_users_weak_password";
		
		$audit_password = get_option($pfms_wp->prefix . "audit_passwords",
			array(
				'last_execution' => null,
				'status' => null));
		
		//For first versions it is store in data plugin directory.
		$weak_passwords_list = file(
			plugin_dir_path(__FILE__) . "../data/password_dictionary.default.txt");
		
		//Get all users (included the disabled users because they can return to enabled)
		$users = get_users();
		
		
		$not_exists_weak_users = true;
		foreach ($users as $user) {
			foreach ($weak_passwords_list as $weak_password) {
				$weak = wp_check_password(
					trim($weak_password), $user->data->user_pass, $user->ID);
				
				$user_login = $user->data->user_login;
				
				if ($weak) {
					$not_exists_weak_users = false;
					
					// Store the user with weak password.
					$wpdb->delete(
						$table_user_weak_password,
						array('user' => $user_login));
					$wpdb->insert(
						$table_user_weak_password,
						array('user' => $user_login));
					
					break;
				}
				else {
					// Delete user with previous weak password.
					$wpdb->delete(
						$table_user_weak_password,
						array('user' => $user_login));
				}
			}
		}
		
		$audit_password['status'] = (int)$not_exists_weak_users;
		$last_execution = time();
		$audit_password['last_execution'] = $last_execution;
		
		update_option($pfms_wp->prefix . "audit_passwords", $audit_password);
	}
	
	public function check_new_page_login_online() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options-system_security');
		
		if (get_option('permalink_structure')) {
			
			$new_login_url =
				trailingslashit(home_url()) .
				esc_attr($options['login_rename_page']) . 
				($pfms_wp->use_trailing_slashes() ?
					'/' :
					'');
		}
		else {
			$new_login_url = trailingslashit(home_url()) . '?' .
				$options['login_rename_page'];
		}
		
		$ch = curl_init($new_login_url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_exec($ch);
		$r = curl_getinfo($ch);
		curl_close($ch);
		
		error_log($new_login_url);
		
		if ($r['http_code'] != 404) {
			return 1;
		}
		else {
			return 0;
		}
	}
	
	private function check_admin_user_enabled() {
		//Check all users (included the disabled users because they can return to enabled)
		$user = get_user_by('login', 'admin');
		
		return empty($user);
	}
	
	private function check_plugins_pending_update() {
		
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
	
	private function check_api_rest_plugin() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$plugins = get_plugins();
		
		$return = 0;
		foreach ($plugins as $plugin) {
			if ($plugin['Name'] == "WP REST API") {
				$return = 1;
				break;
			}
		}
		
		return $return;
	}
	//=== END ==== CHECKS ==============================================
	
	
	//=== INIT === CRON HOOKS CODE =====================================
	public static function cron_audit_passwords_strength() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$pfms_wp->audit_passwords_strength();
	}
	
	public static function cron_audit_files() {
		error_log("cron_audit_files");
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$pfms_wp->audit_files();
		
		$options_system_security = get_option('pfmswp-options-system_security');
		
		if ($options_system_security['check_filehash_svn']) {
			$pfms_wp->audit_files_svn_repository();
		}
		if ($options_system_security['scan_infected_files']) {
			$pfms_wp->audit_files_infected();
		}
		
		
	}
	
	public static function cron_clean_logs() {
		global $wpdb;
		
		error_log("cron_clean_logs");
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "user_stats";
		
		$sql = "DELETE
			FROM `" . $tablename . "`
			WHERE timestamp < date_sub(NOW(), INTERVAL 45 DAY);";
		$result = $wpdb->query($sql);
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$sql = "DELETE
			FROM `" . $tablename . "`
			WHERE timestamp < date_sub(NOW(), INTERVAL 45 DAY);";
		$result = $wpdb->query($sql);
	}
	//=== END ==== CRON HOOKS CODE =====================================
	
	
	//=== INIT === AJAX HOOKS CODE =====================================
	public static function ajax() {
		error_log("ajax");
		?>
		<script type="text/javascript" >
			jQuery(document).ready(function($) {
				
			});
			
			function check_admin_user_enabled() {
				var data = {
					'action': 'check_admin_user_enabled'
				};
				
				jQuery("#admin_user_enabled").empty();
				jQuery("#admin_user_enabled").append(
					jQuery("#ajax_loading").clone());
				
				jQuery.post(ajaxurl, data, function(response) {
					jQuery("#admin_user_enabled").empty();
					
					if (response.result) {
						jQuery("#admin_user_enabled").append(
							jQuery("#ajax_result_ok").clone());
					}
					else {
						jQuery("#admin_user_enabled").append(
							jQuery("#ajax_result_fail").clone());
					}
				},
				"json");
			}
			
			function check_plugins_pending_update() {
				var data = {
					'action': 'check_plugins_pending_update'
				};
				
				jQuery("#ajax_result_fail_plugins_are_updated")
					.hide();
				jQuery("#ajax_result_ok_plugins_are_updated")
					.hide();
				jQuery("#ajax_result_loading_plugins_are_updated")
					.show();
				
				jQuery.post(ajaxurl, data, function(response) {
					jQuery("#ajax_result_loading_plugins_are_updated")
						.hide();
					
					if (response.result) {
						jQuery("#ajax_result_fail_plugins_are_updated")
							.hide();
						jQuery("#ajax_result_ok_plugins_are_updated")
							.show();
					}
					else {
						jQuery("#ajax_result_fail_plugins_are_updated")
							.show();
						jQuery("#ajax_result_ok_plugins_are_updated")
							.hide();
					}
					
					var dialog_plugins_pending_update =
						jQuery("<div id='dialog_plugins_pending_update' title='<?php esc_attr_e("List plugins pending update");?>' />")
							.html(response.plugins.join('<br />'))
							.appendTo("body");
					
					dialog_plugins_pending_update.dialog({
						'dialogClass' : 'wp-dialog',
						'height': 200,
						'modal' : true,
						'autoOpen' : false,
						'closeOnEscape' : true})
						.dialog('open');
				},
				"json");
			}
			
			function show_api_rest_plugin() {
				var dialog_weak_user =
					jQuery("<div id='dialog_' title='<?php esc_attr_e("API REST Plugin Installation");?>' />")
						.html('<?php
						esc_attr_e("The REST API is the newest WordPress API. You can install the JSON REST API plugin. There are plans for the REST API to be included in the core of WordPress, but for now it lives in a plugin.")
						?>')
						.appendTo("body");
				
				dialog_weak_user.dialog({
					'dialogClass' : 'wp-dialog',
					'height': 200,
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
					'modal' : true,
					'autoOpen' : false,
					'closeOnEscape' : true})
					.dialog('open');
			}
			
			function force_cron_audit_password() {
				var data = {
					'action': 'force_cron_audit_password'
				};
				
				jQuery("#audit_password_status").empty();
				jQuery("#audit_password_status").append(
					jQuery("#ajax_loading").clone());
				
				jQuery("#audit_password_last_execute").empty();
				
				jQuery.post(ajaxurl, data, function(response) {
					jQuery("#audit_password_status").empty();
					
					if (response.status) {
						jQuery("#audit_password_status").append(
							jQuery("#ajax_result_ok").clone());
					}
					else {
						jQuery("#audit_password_status").append(
							jQuery("#ajax_result_fail").clone());
					}
					
					jQuery("#audit_password_last_execute").append(
						response.last_execution);
				},
				"json");
			}
			
			function force_cron_audit_files() {
				var data = {
					'action': 'force_cron_audit_files'
				};
				
				jQuery("#audit_files_status").empty();
				jQuery("#audit_files_status").append(
					jQuery("#ajax_loading").clone());
				
				jQuery("#audit_files_last_execute").empty();
				
				jQuery.post(ajaxurl, data, function(response) {
					jQuery("#audit_files_status").empty();
					
					if (response.status) {
						jQuery("#audit_files_status").append(
							jQuery("#ajax_result_ok").clone());
					}
					else {
						jQuery("#audit_files_status").append(
							jQuery("#ajax_result_fail").clone());
					}
					
					jQuery("#audit_files_last_execute").append(
						response.last_execution);
				},
				"json");
			}
			
			function show_weak_user_dialog() {
				var status = jQuery("#audit_password_status img").attr("id");
				
				if (status !== "ajax_result_fail") {
					return;
				}
				
				var data = {
					'action': 'get_list_users_with_weak_password'
				};
				
				jQuery("#audit_password_status").empty();
				jQuery("#audit_password_status").append(
					jQuery("#ajax_loading").clone());
				
				jQuery.post(ajaxurl, data, function(response) {
					var list_users = jQuery.makeArray(response.list_users);
					
					jQuery("#audit_password_status").empty();
					jQuery("#audit_password_status").append(
							jQuery("#ajax_result_fail").clone());
					
					var dialog_weak_user =
						jQuery("<div id='dialog_weak_user' title='<?php esc_attr_e("List weak users");?>' />")
							.html(list_users.join('<br />'))
							.appendTo("body");
					
					dialog_weak_user.dialog({
						'dialogClass' : 'wp-dialog',
						'height': 200,
						'modal' : true,
						'autoOpen' : false,
						'closeOnEscape' : true})
						.dialog('open');
					
				},
				"json");
			}
			
			function show_files_dialog() {
				var status = jQuery("#audit_files_status img").attr("id");
				
				if (status !== "ajax_result_fail") {
					return;
				}
				
				var data = {
					'action': 'get_list_audit_files'
				};
				
				jQuery("#audit_files_status").empty();
				jQuery("#audit_files_status").append(
					jQuery("#ajax_loading").clone());
				
				jQuery.post(ajaxurl, data, function(response) {
					var list_files = jQuery.makeArray(response.list_files);
					
					jQuery("#audit_files_status").empty();
					jQuery("#audit_files_status").append(
							jQuery("#ajax_result_fail").clone());
					
					var $table = jQuery("<table width='100%'>")
						.append("<thead>" +
							"<tr>" +
								"<th><?php esc_html_e("Path");?></th>" +
								"<th><?php esc_html_e("Date");?></th>" +
								"<th><?php esc_html_e("Status");?></th>" +
								"<th><?php esc_html_e("No writable others");?></th>" +
								"<th><?php esc_html_e("Original");?></th>" +
								"<th><?php esc_html_e("Infected");?></th>" +
							"</tr>" +
							"</thead>");
					jQuery.each(list_files, function(i, file) {
						var tr = "<tr>";
						
						jQuery.each(file, function(i, item) {
							if ((i == "writable_others") || (i == "original") ||
								(i == "infected")) {
								
								tr = tr + "<td align='center'>";
							}
							else {
								tr = tr + "<td>";
							}
							tr = tr + item + "</td>";
						});
						tr = tr + "</tr>";
						
						$table.append(tr);
					});
					
					var dialog_weak_user =
						jQuery("<div id='dialog_list_files' title='<?php esc_attr_e("List change or new files");?>' />")
							.append($table)
							.appendTo("body");
					
					dialog_weak_user.dialog({
						'dialogClass' : 'wp-dialog',
						'height': 200,
						'minWidth': 1200,
						'modal' : true,
						'autoOpen' : false,
						'closeOnEscape' : true})
						.dialog('open');
					
				},
				"json");
			}
		</script>
		<?php
	}
	
	public static function ajax_force_cron_audit_files() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if ($pfms_wp->debug) {
			$pfms_wp->ajax_check_audit_files();
		}
		else {
			wp_reschedule_event(time(), 'daily', 'cron_audit_files');
			
			$audit_files = get_option($pfms_wp->prefix . "audit_files",
				array(
					'last_execution' => null,
					'status' => null));
			$audit_files['last_execution'] = esc_html(_("Scheduled"));
			echo json_encode($audit_files);
			
			wp_die();
		}
	}
	
	public static function ajax_check_audit_files() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$pfms_wp->audit_files();
		
		$audit_files = get_option($pfms_wp->prefix . "audit_files",
			array(
				'last_execution' => null,
				'status' => null));
		
		if (empty($audit_files['last_execution'])) {
			$audit_files['last_execution'] = esc_html(_("Never execute"));
		}
		else {
			$audit_files['last_execution'] = esc_html(
				date_i18n(get_option('date_format'),
					$audit_files['last_execution']));
		}
		
		echo json_encode($audit_files);
		
		wp_die();
	}
	
	public static function ajax_force_cron_audit_password() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if ($pfms_wp->debug) {
			$pfms_wp->ajax_check_audit_password();
		}
		else {
			wp_reschedule_event(time(), 'daily', 'cron_audit_passwords_strength');
			
			$audit_password = get_option($pfms_wp->prefix . "audit_passwords",
				array(
					'last_execution' => null,
					'status' => null));
			$audit_password['last_execution'] = esc_html(_("Scheduled"));
			echo json_encode($audit_password);
			
			wp_die();
		}
	}
	
	public static function ajax_check_audit_password() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$pfms_wp->audit_passwords_strength();
		
		$audit_password = get_option($pfms_wp->prefix . "audit_passwords",
			array(
				'last_execution' => null,
				'status' => null));
		
		if (empty($audit_password['last_execution'])) {
			$audit_password['last_execution'] = esc_html(_("Never execute"));
		}
		else {
			$audit_password['last_execution'] = esc_html(
				date_i18n(get_option('date_format'),
					$audit_password['last_execution']));
		}
		
		echo json_encode($audit_password);
		
		wp_die();
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
	
	public static function ajax_get_list_users_with_weak_password() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "audit_users_weak_password";
		$users = $wpdb->get_results("SELECT user FROM `" . $tablename . "`");
		if (empty($users))
			$users = array();
		
		$return = array();
		foreach ($users as $user) {
			$return[] = $user->user;
		}
		
		echo json_encode(array('list_users' => $return));
		
		wp_die();
	}
	
	public static function ajax_get_list_audit_files() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "filesystem";
		$filesystem = $wpdb->get_results("
			SELECT path, status, writable_others, original, infected
			FROM `" . $tablename . "`
			WHERE status != '' or writable_others = 1
			ORDER BY status DESC");
		if (empty($filesystem))
			$filesystem = array();
		
		$return = array();
		foreach ($filesystem as $entry) {
			$icon = "";
			if ($entry->writable_others) {
				$icon = "<img src='" . esc_url(admin_url( 'images/yes.png')) . "' alt='' />";
			}
			else {
				$icon = "<img src='" . esc_url(admin_url( 'images/no.png')) . "' alt='' />";
			}
			
			$icon_original = "";
			if ($entry->original == "no") {
				$icon_original = "<img src='" . esc_url(admin_url( 'images/no.png')) . "' alt='' />";
			}
			else {
				$icon_original = "<img src='" . esc_url(admin_url( 'images/yes.png')) . "' alt='' />";
			}
			
			$icon_infected = "";
			if ($entry->infected == "yes") {
				$icon_infected = "<img src='" . esc_url(admin_url( 'images/no.png')) . "' alt='' />";
			}
			else {
				$icon_infected = "<img src='" . esc_url(admin_url( 'images/yes.png')) . "' alt='' />";
			}
			
			$return[] = array(
				'path' => $entry->path,
				'date' => date_i18n(get_option('date_format'), filemtime($entry->path)),
				'status' => $entry->status,
				'writable_others' => $icon,
				'original' => $icon_original,
				'infected' => $icon_original);
		}
		
		echo json_encode(array('list_files' => $return));
		
		wp_die();
	}
	//=== END ==== AJAX HOOKS CODE =====================================
}
?>